<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Filament\Resources\ProductResource\RelationManagers;
use App\Models\Product;
use App\Models\ProductCategory;
use Filament\Forms;
use Filament\Forms\Components;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Traits\ResourceHasPermission;
use App\Models\Scopes\ActiveScope;

class ProductResource extends Resource
{
    use ResourceHasPermission;
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-beaker';
    protected static ?string $navigationGroup = 'Admin management';
    protected static ?int $navigationSort = 2;


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->label('Name')
                    ->required(),
                Select::make('product_category_id')
                    ->label('Product Category')
                    ->relationship('productCategory','name')
                    ->searchable()
                    ->preload()
                    ->required(),
                TextInput::make('price')
                    ->label('Price')
                    ->numeric()
                    ->minValue(1)
                    ->required(),
                TextInput::make('market_price')
                    ->label('Market Price')
                    ->numeric()
                    ->minValue(0)
                    ->default(0)
                    ->reactive()
                    ->afterStateUpdated(function($get, $set) {
                        $market = floatval($get('market_price') ?? 0);
                        $percent = floatval($get('discount_percentage') ?? 0);
                        $percent = max(0, min(100, $percent));
                        $value = $market > 0 ? round($market * $percent / 100, 2) : 0;
                        $set('discount_value', $value);
                    }),
                TextInput::make('discount_percentage')
                    ->helperText('Discount percentage is applied on the market price')
                    ->label('Discount %')
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(100)
                    ->default(0)
                    ->reactive()
                    ->afterStateUpdated(function($get, $set) {
                        $market = floatval($get('market_price') ?? 0);
                        $percent = floatval($get('discount_percentage') ?? 0);
                        $percent = max(0, min(100, $percent));
                        $value = $market > 0 ? round($market * $percent / 100, 2) : 0;
                        $set('discount_value', $value);
                    }),
                TextInput::make('discount_value')
                    ->helperText('Discount value is applied on the market price')
                    ->label('Discount Value')
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(fn($get) => max(0, floatval($get('market_price') ?? 0)))
                    ->default(0)
                    ->reactive()
                    ->afterStateUpdated(function($get, $set) {
                        $market = floatval($get('market_price') ?? 0);
                        $value = floatval($get('discount_value') ?? 0);
                        $market = max(0.0, $market);
                        $value = max(0.0, min($value, $market));
                        $percent = $market > 0 ? round(($value / $market) * 100, 2) : 0;
                        $set('discount_percentage', $percent);
                    }),
                Toggle::make('active')
                    ->label('Active')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->label('Name'),
                TextColumn::make('productCategory.name'),
                TextColumn::make('price'),
                TextColumn::make('market_price'),
                TextColumn::make('discount_percentage'),
                TextColumn::make('discount_value'),
                IconColumn::make('active')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->label('Created At')
                    ->tooltip(fn($record) => $record->created_at->format('d-M-Y')),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                ActiveScope::class
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }

    public static function canEdit(Model $record): bool
    {
        /** @var \App\Models\User|null $user */
        $user = auth()->user();
        return (bool) ($user?->hasRole('super-admin'));
    }

    public static function canCreate(): bool
    {
        /** @var \App\Models\User|null $user */
        $user = auth()->user();
        return (bool) ($user?->hasRole('super-admin'));
    }
}
