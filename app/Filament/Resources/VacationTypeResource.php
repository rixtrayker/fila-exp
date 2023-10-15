<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VacationTypeResource\Pages;
use App\Filament\Resources\VacationTypeResource\RelationManagers;
use App\Models\VacationType;
use App\Traits\RolesOnlyResources;
use Filament\Forms;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class VacationTypeResource extends Resource
{
    use RolesOnlyResources;
    protected static ?string $model = VacationType::class;

    protected static ?string $navigationIcon = 'heroicon-o-tag';
    protected static ?string $navigationGroup = 'Types management';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->unique()
                    ->required()
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                ->label('Name'),
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

    public static function canAccessMe(): array
    {
        return ['super-admin','moderator', 'district-manager'];
    }
}
