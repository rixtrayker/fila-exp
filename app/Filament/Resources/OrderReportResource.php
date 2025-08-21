<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrderReportResource\Pages\ListOrdersReport;
use App\Filament\Resources\OrderResource\Pages\ViewOrder;
use App\Models\Client;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;
use App\Traits\ResourceHasPermission;
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
    use ResourceHasPermission;
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
                    ->wrap()
                    ->label('Products List'),
                TextColumn::make('categories_list')
                    ->label('Categories List'),
                TextColumn::make('created_at')
                    ->date('d-m-Y h:i A')
                    ->tooltip(fn($record) => $record->created_at->format('d-M-Y'))
                    ->label('Date'),
            ])
            ->filters([
                Filter::make('dates')
                    ->form([
                        DatePicker::make('from_date')
                            ->default(null),
                        DatePicker::make('to_date')
                            ->default(null),
                    ])->columns(2)
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                !empty($data['from_date']),
                                fn (Builder $query, $date): Builder => $query->whereDate('orders.created_at', '>=', $data['from_date'])
                            )
                            ->when(
                                !empty($data['to_date']),
                                fn (Builder $query, $date): Builder => $query->whereDate('orders.created_at', '<=', $data['to_date'])
                            );
                    }),
                SelectFilter::make('product_id')
                    ->multiple()
                    ->options(self::getProducts())
                    ->label('Products')
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            !empty($data['values']),
                            fn (Builder $query) => $query->whereIn('order_products.product_id', $data['values'])
                        );
                    }),
                SelectFilter::make('product_category_id')
                    ->multiple()
                    ->options(self::getCategories())
                    ->label('Categories')
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            !empty($data['values']),
                            fn (Builder $query) => $query->whereIn('products.product_category_id', $data['values'])
                        );
                    }),
                Filter::make('quantity_filter')
                    ->form([
                        Select::make('comparison')
                            ->label('Comparison')
                            ->options([
                                '>' => 'Greater than',
                                '>=' => 'Greater than or equal',
                                '<' => 'Less than',
                                '<=' => 'Less than or equal',
                                '=' => 'Equals',
                            ])
                            ->nullable(),
                        TextInput::make('quantity')
                            ->nullable()
                            ->minValue(1)
                            ->numeric(),
                    ])->columns(2)
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            !empty($data['comparison']) && !empty($data['quantity']),
                            fn (Builder $query) => $query->where('order_products.count', $data['comparison'], $data['quantity'])
                        );
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
                                !empty($data['user_id']),
                                fn (Builder $query) => $query->whereIn('orders.user_id', $data['user_id'])
                            )
                            ->when(
                                !empty($data['client_id']),
                                fn (Builder $query) => $query->whereIn('orders.client_id', $data['client_id'])
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
            'orders.created_at as created_at',
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

        self::$medicalReps = User::allMine()->pluck('name','id')->toArray();
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
