<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SalesReportResource\Pages\ListSalesReport;
use App\Models\BusinessOrder;
use App\Models\Company;
use App\Models\CompanyBranch;
use App\Models\Product;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\DB;

class SalesReportResource extends Resource
{
    protected static ?string $model = BusinessOrder::class;

    protected static ?string $navigationLabel = 'Sales report';
    protected static ?string $navigationGroup = 'Reports';

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $slug = 'sales-report';
    protected static $branches;
    protected static $companies;
    protected static $products;

    public static function table(Table $table): Table
    {
        return $table
            ->defaultPaginationPageOption(50)
            ->paginated([10, 25, 50, 100, 1000, 'all'])
            ->columns([
                TextColumn::make('company_name')
                    ->label('Company Name'),
                TextColumn::make('branch_name')
                    ->label('Branch Name'),
                // todo:check this
                // TextColumn::make('product_list')
                //     ->label('Product List'),
                TextColumn::make('date')
                    ->label('Date'),
                TextColumn::make('quantity')
                    ->label('Quantity'),
            ])
            ->filters([
                Filter::make('dates')
                    ->form([
                            DatePicker::make('from_date')
                                ->default(today()->startOfMonth()),
                            DatePicker::make('to_date')
                                ->default(today()),
                        ])->columns(2)
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from_date'],
                                fn (Builder $query, $date): Builder => $query->whereDate('date', '>=', $date)
                            )
                            ->when(
                                $data['to_date'],
                                fn (Builder $query, $date): Builder => $query->whereDate('date', '<=', $date));
                    }),
                    SelectFilter::make('product_id')
                        ->multiple()
                        ->options(self::getProducts())
                        ->label('Products'),
                    SelectFilter::make('company_branch_id')
                        ->multiple()
                        ->options(self::getBranches())
                        ->label('Branches'),
                    SelectFilter::make('company_id')
                        ->multiple()
                        ->options(self::getCompanies())
                        ->label('Companies'),
                    Filter::make('quantity_filter')
                        ->form([
                            Select::make('comparasion')
                                ->label('Quantity Comparasion')
                                ->options([
                                    '>' => 'Greater than',
                                    '>=' => 'Greater than or equal',
                                    '<' => 'Less than',
                                    '<=' => 'Less than or equal',
                                    '=' => 'Equals',
                                ])
                                ->default('>'),
                            TextInput::make('quantity')
                                ->nullable()
                                ->minValue(1)
                                ->numeric(),
                        ])
                    ->query(function (Builder $query, array $data): Builder {
                        if(!$data['comparasion'] || !$data['quantity'])
                            return $query;
                        return $query->where('quantity', $data['comparasion'], $data['quantity']);
                    }),
            ])
            ->actions([
            ])
            ->bulkActions([
            ]);
    }
    public static function getEloquentQuery(): Builder
    {
        DB::statement("SET SESSION sql_mode=''");

        return BusinessOrder::select(
            'business_orders.id as id',
            'companies.name as company_name',
            'company_branches.name as branch_name',
            'products.name as product_name',
            'business_orders.date',
            'business_orders.quantity',
            'business_orders.company_id',
            'business_orders.company_branch_id',
            // DB::raw('GROUP_CONCAT( products.name SEPARATOR ", ") AS product_list')
        )
            ->leftJoin('companies', 'companies.id', '=', 'business_orders.company_id')
            ->leftJoin('company_branches', 'company_branches.id', '=', 'business_orders.company_branch_id')
            ->leftJoin('products', 'products.id', '=', 'business_orders.product_id')
            ->groupBy('business_orders.id')
            ->orderBy('business_orders.id', 'DESC');
    }
    public static function getPages(): array
    {
        return [
            'index' => ListSalesReport::route('/'),
        ];
    }

    private static function getProducts(): array{
        if(self::$products){
            return self::$products;
        }

        self::$products = Product::pluck('name', 'id')->toArray();
        return self::$products;
    }
    private static function getBranches(): array{
        if(self::$branches){
            return self::$branches;
        }

        self::$branches = CompanyBranch::pluck('name', 'id')->toArray();
        return self::$branches;
    }
    private static function getCompanies(): array{
        if(self::$companies){
            return self::$companies;
        }

        self::$companies = Company::pluck('name', 'id')->toArray();
        return self::$companies;
    }
}
