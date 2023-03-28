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
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-beaker';
    protected static ?int $navigationSort = 6;


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name_en')
                    ->label('Name')
                    ->required(),
                TextInput::make('name_ar')
                    ->label('Name (العربية)')
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
                Toggle::make('active')
                    ->label('Active')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name_en')
                ->searchable()
                ->sortable()
                ->label('Name'),
                TextColumn::make('name_ar')
                    ->label('Name (العربية)')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('name')
                    ->label('Name'),
                TextColumn::make('arabic_name')
                    ->label('Arabic Name'),
                TextColumn::make('category.name'),
                TextColumn::make('price'),
                IconColumn::make('active')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->dateTime(),
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
}
