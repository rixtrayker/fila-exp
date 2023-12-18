<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrderResource\Pages;
use App\Filament\Resources\OrderResource\RelationManagers;
use App\Models\Client;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Awcodes\FilamentTableRepeater\Components\TableRepeater;
use Closure;
use Filament\Forms;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;
    protected static ?string $navigationLabel = 'Direct orders';

    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';
    protected static ?int $navigationSort = 5;
    protected static $subTotalField;
    protected static $totalField;

    protected static function makeSubTotalField(){
        self::$subTotalField = TextInput::make('sub_total')
            ->disabled()->default(0);
    }
    protected static function makeTotalField(){
        self::$totalField = TextInput::make('total')
            ->disabled()
            ->default(0)
            ->hidden(auth()->user()->hasRole('medical-rep'));
    }
    public static function form(Form $form): Form
    {
        self::makeSubTotalField();
        self::makeTotalField();
        return $form
            ->schema([
                Select::make('user_id')
                    ->label('Medical Rep')
                    ->searchable()
                    ->placeholder('Search name')
                    ->getSearchResultsUsing(fn (string $search) => User::where('name', 'like', "%{$search}%")->limit(50)->pluck('name', 'id'))
                    ->options(User::pluck('name', 'id'))
                    ->getOptionLabelUsing(fn ($value): ?string => User::find($value)?->name)
                    ->preload()
                    ->hidden(auth()->user()->hasRole('medical-rep')),
                Select::make('client_id')
                ->label('Client')
                ->searchable()
                ->placeholder('Search by name or phone or speciality')
                ->getSearchResultsUsing(function(string $search){
                    return Client::where('name_en', 'like', "%{$search}%")
                        ->orWhere('name_ar', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhereHas('speciality', function ($q) use ($search) {
                            $q->where('name','like', "%{$search}%");
                        })->limit(50)->pluck('name_en', 'id');
                })
                ->options(Client::pluck('name_en', 'id'))
                ->getOptionLabelUsing(fn ($value): ?string => Client::find($value)?->name)
                ->preload()
                ->required(),
                Section::make('products')
                    ->hiddenLabel()
                    ->schema([
                        TableRepeater::make('products')
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
                                ->options(Product::pluck('name','id'))
                                ->required()
                                ->reactive()
                                ->afterStateUpdated(
                                    function($set, $get){
                                        $product = Product::find($get('product_id'));
                                        $cost = 0;
                                        if($product && $get('count'))
                                            $cost = $product->price * $get('count');

                                        $price = 0;
                                        if($product)
                                            $price = $product->price;

                                        $set('price',$price);
                                        $set('cost',$cost);
                                        $set('item_total',$cost);
                                        self::updateSubTotal($get);
                                    }
                                ),
                            TextInput::make('count')
                                ->numeric()
                                ->required()
                                ->minValue(1)
                                ->hiddenLabel()
                                ->reactive()
                                ->afterStateUpdated(
                                    function($set, $get){
                                        $product = Product::find($get('product_id'));
                                        $cost = 0;
                                        if($product && $get('count'))
                                            $cost = $product->price * $get('count');

                                        $set('item_total',$cost);
                                        $set('cost',$cost);
                                        self::updateSubTotal($get);
                                    }
                                ),
                            TextInput::make('price')
                                ->numeric()
                                ->minValue(1)
                                ->disabled()
                                ->hiddenLabel()
                                ->reactive(),
                            TextInput::make('item_total')
                                ->label('Item total')
                                // ->default(function($get) {
                                //     $product = Product::find($get('product_id'));
                                //     return $product && $get('count')? $product->price * $get('count') : 0;
                                // })
                                ->dehydrateStateUsing(function($get) {
                                    $product = Product::find($get('product_id'));
                                    return $product && $get('count')? $product->price * $get('count') : 0;
                                }) // todo load from DB directly if found
                                ->numeric()
                                ->disabled()
                                ->hiddenLabel(),
                        ])->disableItemMovement()
                        ->columnSpanFull()
                        ->defaultItems(1),
                        Select::make('discount_type')
                                ->options(['percentage'=>'Percentage','value'=>'Value'])
                                ->default('value')
                                ->reactive()
                                ->label('Discount Type')
                                ->hidden(auth()->user()->hasRole('medical-rep'))
                                ->afterStateUpdated(
                                    function($get){
                                        self::updateSubTotal($get, false);
                                    }
                                ),
                        TextInput::make('discount')
                            ->label('Discount')
                            ->reactive()
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(
                                function ($get)
                                {
                                    return $get('discount_type') == 'percentage' ? 100 : $get('sub_total');
                                }
                            )
                            ->placeholder(function($get) {
                                $result = 'please enter discount '
                                    . $get('discount_type')
                                    . $get('discount_type') == 'percentage' ? ' %' : '';
                                return $result;
                            })
                            ->hidden(auth()->user()->hasRole('medical-rep'))
                            ->afterStateUpdated(
                                function($get){
                                    self::updateSubTotal($get, false);
                                }
                            ),
                        self::$subTotalField,
                        self::$totalField,
                    ])->compact()->columns(4),
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
                    ->sortable(),
                IconColumn::make('approved')
                    ->colors(function($record){
                        if($record->approved > 0)
                            return ['success' => $record->approved];
                        if($record->approved < 0)
                            return ['danger' => $record->approved];
                        return ['secondary'];
                    })
                    ->options(function($record){
                        if($record->approved > 0)
                                return ['heroicon-o-check-circle' => $record->approved];
                        if($record->approved < 0)
                            return ['heroicon-o-x-circle' =>  $record->approved];
                        return ['heroicon-o-clock'];
                    }),
                TextColumn::make('approved_by')
                    ->label('Approved By'),
                TextColumn::make('order_date')
                    ->dateTime('d-M-Y')
                    ->sortable()
                    ->searchable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('approve')
                    ->label('Approve')
                    ->color('success')
                    ->icon('heroicon-o-check')
                    ->visible(fn($record) => $record->canApprove())
                    ->action(fn($record) => $record->approve()),
                Tables\Actions\Action::make('decline')
                    ->label('Decline')
                    ->color('danger')
                    ->icon('heroicon-m-x-mark')
                    ->visible(fn($record) => $record->canDecline())
                    ->action(fn($record) => $record->reject()),
            ])
            ->bulkActions([
                Tables\Actions\RestoreBulkAction::make(),
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
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
    public static function updateSubTotal($get, $isFromProducts = true)
    {
        $subTotal = 0;
        $productsArray = $isFromProducts ? $get('../../products') : $get('products');
        foreach($productsArray as $item){
            $product = Product::find($item['product_id']);
            if($product && $item['count'])
            $subTotal += $product->price * $item['count'];
        }

        self::$subTotalField->state($subTotal);

        $discountType = $get('discount_type');
        $discount = $get('discount')?  $get('discount') : 0;
        $total = $subTotal;
        if($discountType == 'percentage'){
            $total = $total - ($total * ($discount/100));
        } else {
            $total = $total - $discount;
        }
        self::$totalField->state($total);
    }
    // public static function updateTotal($get)
    // {
    //     $total = 0;

    //     foreach($get('../../products') as $item){
    //         $product = Product::find($item['product_id']);
    //         if($product && $item['count'])
    //         $total += $product->price * $item['count'];
    //     }

    //     self::$subTotalField->state($total);
    // }
}
