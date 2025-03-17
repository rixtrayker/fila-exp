<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrderResource\Pages;
use App\Models\Client;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Traits\ResouerceHasPermission;
use Awcodes\FilamentTableRepeater\Components\TableRepeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\RestoreBulkAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class OrderResource extends Resource
{
    use ResouerceHasPermission;

    protected static ?string $model = Order::class;
    protected static ?string $navigationLabel = 'Direct orders';
    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';
    protected static ?int $navigationSort = 5;

    public static function form(Form $form): Form
    {
        return $form->schema([
            self::buildUserSelect(),
            self::buildClientSelect(),
            self::buildProductsSection(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label('M.Rep')
                    ->hidden(auth()->user()->hasRole('medical-rep'))
                    ->sortable(),
                TextColumn::make('client.name_en')
                    ->label('Client')
                    ->sortable(),
                TextColumn::make('total')
                    ->label('Total')
                    ->sortable(),
                TextColumn::make('product_list')
                    ->label('Product List')
                    ->wrap()
                    ->sortable(),
                IconColumn::make('approved')
                    ->colors(function($record) {
                        if ($record->approved > 0) return ['success' => $record->approved];
                        if ($record->approved < 0) return ['danger' => $record->approved];
                        return ['secondary'];
                    })
                    ->options(function($record) {
                        if ($record->approved > 0) return ['heroicon-o-check-circle' => $record->approved];
                        if ($record->approved < 0) return ['heroicon-o-x-circle' => $record->approved];
                        return ['heroicon-o-clock'];
                    }),
                TextColumn::make('approved_by')
                    ->label('Approved By'),
                TextColumn::make('order_date')
                    ->dateTime('d-M-Y')
                    ->sortable()
                    ->searchable(),
            ])
            ->filters([])
            ->actions([
                ViewAction::make(),
                Action::make('approve')
                    ->label('Approve')
                    ->color('success')
                    ->icon('heroicon-o-check')
                    ->visible(fn($record) => $record->canApprove())
                    ->action(fn($record) => $record->approve()),
                Action::make('decline')
                    ->label('Decline')
                    ->color('danger')
                    ->icon('heroicon-m-x-mark')
                    ->visible(fn($record) => $record->canDecline())
                    ->action(fn($record) => $record->reject()),
            ])
            ->bulkActions([
                RestoreBulkAction::make(),
                DeleteBulkAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->withoutGlobalScopes([SoftDeletingScope::class]);

        if (auth()->user()->hasRole('accountant')) {
            return $query->scopes(['inMyAreas']);
        }

        return $query;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrders::route('/'),
            'create' => Pages\CreateOrder::route('/create'),
            'view' => Pages\ViewOrder::route('/{record}'),
            'edit' => Pages\EditOrder::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteKeyName(): string|null
    {
        return 'orders.id';
    }

    /**
     * Build the user select field
     */
    protected static function buildUserSelect(): Select
    {
        return Select::make('user_id')
            ->label('Medical Rep')
            ->searchable()
            ->placeholder('Search name')
            ->getSearchResultsUsing(fn (string $search) =>
                User::where('name', 'like', "%{$search}%")
                    ->limit(50)
                    ->pluck('name', 'id')
            )
            ->options(User::pluck('name', 'id'))
            ->getOptionLabelUsing(fn ($value): ?string => User::find($value)?->name)
            ->preload()
            ->hidden(auth()->user()->hasRole('medical-rep'));
    }

    /**
     * Build the client select field
     */
    protected static function buildClientSelect(): Select
    {
        return Select::make('client_id')
            ->label('Client')
            ->searchable()
            ->placeholder('Search by name or phone or speciality')
            ->getSearchResultsUsing(function(string $search) {
                return Client::where('name_en', 'like', "%{$search}%")
                    ->orWhere('name_ar', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhereHas('speciality', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    })
                    ->limit(50)
                    ->pluck('name_en', 'id');
            })
            ->options(Client::pluck('name_en', 'id'))
            ->getOptionLabelUsing(fn ($value): ?string => Client::find($value)?->name)
            ->preload()
            ->required();
    }

    /**
     * Build the products section with the table repeater
     */
    protected static function buildProductsSection(): Section
    {
        return Section::make('products')
            ->hiddenLabel()
            ->schema([
                self::buildProductsTableRepeater(),
                self::buildDiscountTypeSelect(),
                self::buildDiscountInput(),
                self::buildSubTotalField(),
                self::buildTotalField(),
            ])
            ->compact()
            ->columns(4);
    }

    /**
     * Build the products table repeater component
     */
    protected static function buildProductsTableRepeater(): TableRepeater
    {
        return TableRepeater::make('products')
            ->relationship('products')
            ->reactive()
            ->hiddenLabel()
            ->headers(['Product', 'Quantity'])
            ->emptyLabel('There is no product added.')
            ->columnWidths([
                'count' => '140px',
                'cost' => '140px',
                'price' => '140px',
                'product_id' => '440px',
                'row_actions' => '20px',
            ])
            ->schema([
                Select::make('product_id')
                    ->hiddenLabel()
                    ->placeholder('select a product')
                    ->options(Product::pluck('name', 'id'))
                    ->required()
                    ->reactive()
                    ->afterStateUpdated(function($set, $get) {
                        $product = Product::find($get('product_id'));
                        $price = $product ? $product->price : 0;
                        $count = $get('count') ?: 0;
                        $cost = $product ? $price * $count : 0;

                        $set('price', $price);
                        $set('cost', $cost);
                        $set('item_total', $cost);
                        self::updateTotals($get,$set);
                    }),
                TextInput::make('count')
                    ->numeric()
                    ->required()
                    ->minValue(0)
                    ->hiddenLabel()
                    ->reactive()
                    ->afterStateUpdated(function($set, $get) {
                        $product = Product::find($get('product_id'));
                        $count = $get('count') ?: 0;
                        $cost = $product ? $product->price * $count : 0;

                        $set('item_total', $cost);
                        $set('cost', $cost);
                        self::updateTotals($get,$set);
                    }),
                TextInput::make('price')
                    ->numeric()
                    ->minValue(1)
                    ->disabled()
                    ->hiddenLabel()
                    ->reactive(),
                TextInput::make('item_total')
                    ->label('Item total')
                    ->dehydrateStateUsing(function($get) {
                        $product = Product::find($get('product_id'));
                        $count = $get('count') ?: 0;
                        return $product ? $product->price * $count : 0;
                    })
                    ->numeric()
                    ->disabled()
                    ->hiddenLabel(),
            ])
            ->columnSpanFull()
            ->defaultItems(1);
    }

    /**
     * Build the discount type select field
     */
    protected static function buildDiscountTypeSelect(): Select
    {
        return Select::make('discount_type')
            ->options(['percentage' => 'Percentage', 'value' => 'Value'])
            ->default('value')
            ->reactive()
            ->label('Discount Type')
            ->hidden(auth()->user()->hasRole('medical-rep'))
            ->afterStateUpdated(function($get,$set) {
                self::updateTotals($get,$set,false);
            });
    }

    /**
     * Build the discount input field
     */
    protected static function buildDiscountInput(): TextInput
    {
        return TextInput::make('discount')
            ->label('Discount')
            ->reactive()
            ->numeric()
            ->minValue(0)
            ->maxValue(function($get) {
                return $get('discount_type') == 'percentage' ? 100 : $get('sub_total');
            })
            ->placeholder(function($get) {
                $type = $get('discount_type');
                $suffix = $type == 'percentage' ? ' %' : '';
                return "please enter discount {$type}{$suffix}";
            })
            ->hidden(auth()->user()->hasRole('medical-rep'))
            ->afterStateUpdated(function($get,$set) {
                self::updateTotals($get,$set,false);
            });
    }

    /**
     * Build the sub-total field
     */
    protected static function buildSubTotalField(): TextInput
    {
        return TextInput::make('sub_total')
            ->disabled()
            ->default(0);
    }

    /**
     * Build the total field
     */
    protected static function buildTotalField(): TextInput
    {
        return TextInput::make('total')
            ->disabled()
            ->default(0)
            ->hidden(auth()->user()->hasRole('medical-rep'));
    }

    /**
     * Update totals based on product changes or discount changes
     *
     * @param callable $get The getter function for retrieving state values
     * @param callable $set The setter function for setting state values
     * @param bool $isFromProducts Flag indicating if the update was triggered from within products repeater
     */
    protected static function updateTotals($get, $set, $isFromProducts = true)
    {
        // Calculate subtotal from products
        $subTotal = self::calculateSubtotal($get, $isFromProducts);

        // Set the subtotal using the appropriate path
        self::setFieldValue($set, 'sub_total', $subTotal, $isFromProducts);

        // Calculate final total after discount
        $total = self::calculateTotalAfterDiscount($get, $subTotal, $isFromProducts);

        // Set the total using the appropriate path
        self::setFieldValue($set, 'total', $total, $isFromProducts);
    }

    /**
     * Calculate subtotal based on products in the form
     *
     * @param callable $get The getter function
     * @param bool $isFromProducts Whether the function is called from within products
     * @return float The calculated subtotal
     */
    protected static function calculateSubtotal($get, $isFromProducts = true)
    {
        // Get products based on context
        $products = self::getProductsArray($get, $isFromProducts);

        $subTotal = 0;
        foreach ($products as $item) {
            if (empty($item['product_id']) || empty($item['count'])) {
                continue;
            }

            $product = Product::find($item['product_id']);
            if ($product) {
                $subTotal += $product->price * $item['count'];
            }
        }

        return $subTotal;
    }

    /**
     * Calculate total after applying discount
     *
     * @param callable $get The getter function
     * @param float $subTotal The calculated subtotal
     * @param bool $isFromProducts Whether the function is called from within products
     * @return float The total after discount
     */
    protected static function calculateTotalAfterDiscount($get, $subTotal, $isFromProducts = true)
    {
        // Get discount type and amount with proper path prefixing
        $discountType = self::getFieldValue($get, 'discount_type', $isFromProducts) ?? 'value';
        $discount = (float)(self::getFieldValue($get, 'discount', $isFromProducts) ?? 0);

        $total = $subTotal;

        if ($discountType == 'percentage') {
            $total = $total - ($total * ($discount / 100));
        } else {
            $total = $total - $discount;
        }

        // Ensure total is not negative
        return max(0, $total);
    }

    /**
     * Get field value with proper path resolution based on context
     *
     * @param callable $get The getter function
     * @param string $fieldName The field name to get
     * @param bool $isFromProducts Whether the function is called from within products
     * @return mixed The field value
     */
    protected static function getFieldValue($get, $fieldName, $isFromProducts = true)
    {
        $path = $isFromProducts ? "../../{$fieldName}" : $fieldName;
        return $get($path);
    }

    /**
     * Set field value with proper path resolution based on context
     *
     * @param callable $set The setter function
     * @param string $fieldName The field name to set
     * @param mixed $value The value to set
     * @param bool $isFromProducts Whether the function is called from within products
     */
    protected static function setFieldValue($set, $fieldName, $value, $isFromProducts = true)
    {
        $path = $isFromProducts ? "../../{$fieldName}" : $fieldName;
        $set($path, $value);
    }

    /**
     * Get products array with proper path resolution based on context
     *
     * @param callable $get The getter function
     * @param bool $isFromProducts Whether the function is called from within products
     * @return array The products array
     */
    protected static function getProductsArray($get, $isFromProducts = true)
    {
        $path = $isFromProducts ? '../../products' : 'products';
        return $get($path) ?? [];
    }
}
