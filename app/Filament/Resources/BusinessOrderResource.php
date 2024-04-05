<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BusinessOrderResource\Pages;
use App\Filament\Resources\BusinessOrderResource\RelationManagers;
use App\Helpers\ImportHelper;
use App\Models\BusinessOrder;
use App\Traits\RepRoleResources;
use Filament\Forms;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class BusinessOrderResource extends Resource
{
    use RepRoleResources;

    protected static ?string $model = BusinessOrder::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('company.name')
                    ->label('Company')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('companyBranch.name')
                    ->label('Company Branch')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('quantity')
                    ->label('Quantity')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('date')
                    ->label('Date')
                    ->dateTime('d-M-Y')
                    ->sortable()
                    ->searchable(),
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

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBusinessOrders::route('/'),
            'create' => Pages\CreateBusinessOrder::route('/create'),
            'edit' => Pages\EditBusinessOrder::route('/{record}/edit'),
        ];
    }
}
