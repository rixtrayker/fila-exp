<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrderReportResource\Pages\ListOrdersReport;
use App\Filament\Resources\OrderResource\Pages\ViewOrder;
use App\Models\Client;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;
use App\Traits\RepRoleResources;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\DB;

class OrderReportResource extends Resource
{
    use RepRoleResources;
    protected static ?string $model = Order::class;

    protected static ?string $navigationLabel = 'Orders report';
    protected static ?string $navigationGroup = 'Reports';

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $slug = 'orders-report';
    protected static $products;
    protected static $categories;
    protected static $medicalReps;
    protected static $clients;

    public static function table(Table $table): Table
    {
        return $table
            ->defaultPaginationPageOption(50)
            ->paginated([10, 25, 50, 100, 1000, 'all'])
            ->columns([
                // TextColumn::make('id')
                //     ->label('ID'),
                TextColumn::make('medical_rep')
                    ->label('Medical Rep'),
                TextColumn::make('client_name')
                    ->label('Client Name'),
                TextColumn::make('brick_name')
                    ->label('Brick Name'),
                TextColumn::make('product_list_report')
                    ->label('Products List'),
                TextColumn::make('categories_list')
                    ->label('Categories List'),
                TextColumn::make('date')
                    ->label('Date'),
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
                                fn (Builder $query, $date): Builder => $query->whereDate('order_date', '>=', $date)
                            )
                            ->when(
                                $data['to_date'],
                                fn (Builder $query, $date): Builder => $query->whereDate('order_date', '<=', $date));
                    }),
                    SelectFilter::make('product_id')
                        ->multiple()
                        ->options(self::getProducts())
                        ->label('Products'),
                    SelectFilter::make('product_category_id')
                        ->multiple()
                        ->options(self::getCategories())
                        ->label('Categories')
                        ->query(fn(Builder $query, array $data):Builder => $data['values'] ? $query->where('product_category_id', $data):$query ),
                    Filter::make('quantity_filter')
                        ->form([
                            Select::make('comparasion')
                                ->label('Comparasion')
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
                        ])->columns(2)
                    ->query(function (Builder $query, array $data): Builder {
                        if(!$data['comparasion'] || !$data['quantity'])
                            return $query;
                        return $query->where('order_products.count', $data['comparasion'], $data['quantity']);
                    }),
                    Filter::make('id')
                    ->form([
                        Select::make('user_id')
                            ->label('Medical Rep')
                            ->multiple()
                            ->options(self::getMedicalReps()),
                        Select::make('client_id')
                            ->label('Client')
                            ->multiple()
                            ->options(self::getClients()),
                    ])->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['user_id'],
                                fn (Builder $query, $userIds): Builder => $query->whereIn('orders.user_id', $userIds)
                            )
                            ->when(
                                $data['client_id'],
                                fn (Builder $query, $secondIds): Builder => $query->whereIn('orders.client_id', $secondIds)
                            );
                    }),
            ])
            ->actions([
                ViewAction::make(),
            ])
            ->bulkActions([
            ]);
    }
    public static function getEloquentQuery(): Builder
    {
        DB::statement("SET SESSION sql_mode=''");

        return Order::select(
            'orders.id as id',
            'orders.total as total',
            'orders.order_date as date',
            'bricks.name as brick_name',
            'clients.name_en as client_name',
            'users.name as medical_rep',
            'areas.name as area_name',
            'areas.id as area_id',
            'orders.user_id as user_id',
            'orders.client_id as client_id',
            DB::raw('GROUP_CONCAT( products.name SEPARATOR ", ") AS product_list_report'),
        )
            ->leftJoin('users', 'users.id', '=', 'orders.user_id')
            ->leftJoin('clients', 'clients.id', '=', 'orders.client_id')
            ->leftJoin('bricks', 'bricks.id', '=', 'clients.brick_id')
            ->leftJoin('areas', 'areas.id', '=', 'bricks.area_id')
            ->leftJoin('order_products', 'order_products.order_id', '=', 'orders.id')
            ->leftJoin('products', 'products.id', '=', 'order_products.product_id')
            ->leftJoin('product_categories', 'product_categories.id', '=', 'products.product_category_id')
            ->groupBy('orders.id')
            ->orderBy('orders.id', 'DESC');
    }

    public static function getRecordRouteKeyName(): string|null {
        return 'orders.id';
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOrdersReport::route('/'),
            'view' => ViewOrder::route('/{record}'),
        ];
    }

    private static function getProducts(): array{
        if(self::$products){
            return self::$products;
        }

        self::$products = Product::pluck('name', 'id')->toArray();
        return self::$products;
    }
    private static function getCategories(): array{
        if(self::$categories){
            return self::$categories;
        }

        self::$categories = ProductCategory::pluck('name', 'id')->toArray();
        return self::$categories;
    }


    private static function getMedicalReps(): array
    {
        if(self::$medicalReps)
            return self::$medicalReps;

        self::$medicalReps = User::getMine()->pluck('name','id')->toArray();
        return self::$medicalReps;
    }
    private static function getClients(): array
    {
        if(self::$clients)
            return self::$clients;

        self::$clients = Client::inMyAreas()->pluck('name_en', 'id')->toArray();
        return self::$clients;
    }
}
