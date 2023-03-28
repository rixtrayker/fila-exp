<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrderResource\Pages;
use App\Filament\Resources\OrderResource\RelationManagers;
use App\Models\Client;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Awcodes\FilamentTableRepeater\Components\TableRepeater;
use Filament\Forms;
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

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;
    protected static ?string $navigationLabel = 'Direct orders';

    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';
    protected static ?int $navigationSort = 5;

    public static function form(Form $form): Form
    {
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
                        ->disableLabel()
                        ->headers(['Product', 'Sample Count'])
                        ->emptyLabel('There is no product added.')
                        ->columnWidths([
                            'count' => '40px',
                            'product_id' => '180px',
                            'row_actions' => '20px',
                        ])
                        ->schema([
                            Select::make('product_id')
                                ->disableLabel()
                                ->placeholder('select a product')
                                ->options(Product::pluck('name','id')),
                            TextInput::make('count')
                                ->numeric()
                                ->minValue(1)
                                ->disableLabel(),
                        ])
                        ->disableItemMovement()
                        ->defaultItems(2),
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
            'edit' => Pages\EditOrder::route('/{record}/edit'),
        ];
    }

}
