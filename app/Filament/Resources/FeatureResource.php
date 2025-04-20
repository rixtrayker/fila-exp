<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FeatureResource\Pages;
use App\Models\Feature;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use App\Traits\ResourceHasPermission;

class FeatureResource extends Resource
{
    use ResourceHasPermission;

    protected static ?string $model = Feature::class;

    protected static ?string $navigationIcon = 'heroicon-o-sparkles';

    protected static ?string $navigationGroup = 'Admin management';

    protected static ?int $navigationSort = 7;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->disabled(),
                Forms\Components\TextInput::make('description')
                    ->disabled(),
                Forms\Components\TextInput::make('icon')
                    ->disabled(),
                Forms\Components\TextInput::make('color')
                    ->disabled(),
                Forms\Components\TextInput::make('version')
                    ->disabled(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('description'),
                Tables\Columns\IconColumn::make('enabled')
                    ->boolean(),
                Tables\Columns\TextColumn::make('version'),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\Action::make('enable')
                    ->action(fn (Feature $record) => $record->update(['enabled' => true]))
                    ->visible(fn (Feature $record) => !$record->enabled)
                    ->icon('heroicon-o-check')
                    ->requiresConfirmation()
                    ->modalDescription('Are you sure you want to enable this feature?')
                    ->color('success'),
                Tables\Actions\Action::make('disable')
                    ->action(fn (Feature $record) => $record->update(['enabled' => false]))
                    ->visible(fn (Feature $record) => $record->enabled)
                    ->icon('heroicon-o-x-mark')
                    ->requiresConfirmation()
                    ->modalDescription('Are you sure you want to disable this feature?')
                    ->color('danger'),
            ])
            ->bulkActions([
                // No bulk actions
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
            'index' => Pages\ListFeatures::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
