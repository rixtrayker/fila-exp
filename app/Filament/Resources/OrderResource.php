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
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Log;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;
    protected static ?string $navigationLabel = 'Direct orders';

    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';
    protected static ?int $navigationSort = 5;
    protected static $totalField;

    protected static function makeTotalField(){
        self::$totalField = TextInput::make('total')->disabled()->default(0);
    }
    public static function form(Form $form): Form
    {
        self::makeTotalField();
        return $form
            ->schema([
                Select::make('user_id')
                    ->label('Medical Rep')
                    ->searchable()
                    ->placeholder('Search name')
                    ->getSearchResultsUsing(fn (string $search) => User::role('medical-rep')->where('name', 'like', "%{$search}%")->limit(50)->pluck('name', 'id'))
                    ->options(User::role('medical-rep')->pluck('name', 'id'))
                    ->getOptionLabelUsing(fn ($value): ?string => User::find($value)?->name)
                    ->preload()
                    ->hidden(auth()->user()->hasRole('medical-rep')),
                Select::make('client_id')
                ->label('Client')
                ->searchable()
                ->placeholder('You can search both arabic and english name')
                ->getSearchResultsUsing(fn (string $search) => Client::where('name_en', 'like', "%{$search}%")->orWhere('name_ar', 'like', "%{$search}%")->limit(50)->pluck('name_en', 'id'))
                ->options(Client::pluck('name_en', 'id'))
                ->getOptionLabelUsing(fn ($value): ?string => Client::find($value)?->name)
                ->preload()
                ->required(),
                Section::make('products')
                    ->disableLabel()
                    ->schema([
                        TableRepeater::make('products')
                        ->relationship('products')
                        ->reactive()
                        ->disableLabel()
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
                                ->disableLabel()
                                ->placeholder('select a product')
                                ->options(Product::pluck('name','id'))
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
                                        self::updateTotal($get);
                                    }
                                ),
                            TextInput::make('count')
                                ->numeric()
                                ->minValue(1)
                                ->disableLabel()
                                ->reactive()
                                ->afterStateUpdated(
                                    function($set, $get){
                                        $product = Product::find($get('product_id'));
                                        $cost = 0;
                                        if($product && $get('count'))
                                            $cost = $product->price * $get('count');

                                        $set('item_total',$cost);
                                        $set('cost',$cost);
                                        self::updateTotal($get);
                                    }
                                ),
                            TextInput::make('price')
                                ->numeric()
                                ->minValue(1)
                                ->disabled()
                                ->disableLabel()
                                ->reactive(),
                            TextInput::make('item_total')
                                ->label('Item total')
                                ->dehydrateStateUsing(function($get) {
                                    $product = Product::find($get('product_id'));
                                    return $product && $get('count')? $product->price * $get('count') : 0;
                                }) // todo load from DB directly if found
                                ->numeric()
                                ->minValue(1)
                                ->disabled()
                                ->disableLabel(),
                        ])->disableItemMovement()
                        ->defaultItems(1),
                        self::$totalField,
                    ])->compact(),
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
                TextColumn::make('client.name')
                    ->label('Client')
                    ->sortable(),
                TextColumn::make('branch.name')
                    ->label('Branch name')
                    ->sortable(),
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
    public static function updateTotal($get)
    {
        $total = 0;

        foreach($get('../../products') as $item){
            $product = Product::find($item['product_id']);
            if($product && $item['count'])
            $total += $product->price * $item['count'];
        }

        self::$totalField->state($total);
    }
}
