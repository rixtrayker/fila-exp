<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VacationTypeResource\Pages;
use App\Models\VacationType;
use App\Traits\ResourceHasPermission;
use Filament\Forms;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class VacationTypeResource extends Resource
{
    use ResourceHasPermission;

    protected static ?string $model = VacationType::class;

    protected static ?string $navigationIcon = 'heroicon-o-tag';

    protected static ?string $navigationGroup = 'Types management';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->unique()
                    ->required(),
                Forms\Components\Toggle::make('consumes_annual_entitlement')
                    ->label('Deducts from annual balance')
                    ->default(true)
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Name'),
                Tables\Columns\IconColumn::make('consumes_annual_entitlement')
                    ->label('Deducts Annual Balance')
                    ->boolean(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageVacationTypes::route('/'),
        ];
    }
}
