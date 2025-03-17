<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrderResource\Fields;
use App\Filament\Resources\OrderResource\Pages;
use App\Filament\Resources\OrderResource\Tables;
use App\Models\Order;
use App\Traits\ResouerceHasPermission;
use Filament\Forms\Form;
use Filament\Resources\Resource;
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
            Fields\UserSelectField::make(),
            Fields\ClientSelectField::make(),
            Fields\ProductsSection::make(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns(Tables\OrderColumns::getColumns())
            ->filters([])
            ->actions(Tables\OrderActions::getActions())
            ->bulkActions(Tables\OrderBulkActions::getActions());
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
}