<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DailyVisitResource\Pages;
use App\Filament\Resources\DailyVisitResource\RelationManagers;
use App\Models\Visit;
use Filament\Forms;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class DailyVisitResource extends Resource
{
    protected static ?string $model = Visit::class;
    protected static ?string $navigationLabel = 'Daily visits';
    protected static ?string $label = 'Daily visits';

    protected static ?string $navigationIcon = 'heroicon-o-collection';
    protected static ?string $slug = 'daily-visits';

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
                TextColumn::make('id')->sortable(),
                TextColumn::make('user.name')
                    ->label('M.Rep')
                    ->hidden(auth()->user()->hasRole('medical-rep'))
                    ->sortable(),
                TextColumn::make('client.name_en')
                    ->label('Client')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('secondRep.name')
                    ->label('M.Rep 2nd'),
                TextColumn::make('created_at')
                    ->dateTime('d-M-Y')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('comment')
                    ->limit(100)
                    ->wrap(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                ->color('success')
                ->visible(fn($record) => auth()->id() === $record->user_id),
                // Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                // Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->scopes([
                'pending',
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageDailyVisits::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
