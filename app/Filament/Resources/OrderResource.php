<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrderResource\Fields;
use App\Filament\Resources\OrderResource\Pages;
use App\Filament\Resources\OrderResource\Tables\OrderColumns;
use App\Models\Order;
use App\Traits\ResourceHasPermission;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Actions\Action;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class OrderResource extends Resource
{
    use ResourceHasPermission;

    protected static ?string $model = Order::class;
    protected static ?string $navigationLabel = 'Direct orders';
    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';
    protected static ?int $navigationSort = 1;
    protected static ?string $navigationGroup = 'Orders';
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
            ->columns(OrderColumns::getColumns())
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
                Tables\Actions\RestoreBulkAction::make(),
                Tables\Actions\DeleteBulkAction::make(),
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
}
