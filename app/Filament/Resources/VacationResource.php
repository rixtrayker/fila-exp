<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VacationResource\Pages;
use App\Filament\Resources\VacationResource\RelationManagers;
use App\Models\User;
use App\Models\Vacation;
use App\Models\VacationRequest;
use App\Models\VacationType;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class VacationResource extends Resource
{
    protected static ?string $model = VacationRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-collection';
    protected static ?int $navigationSort = 7;


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('rep_id')
                    ->label('Medical Rep')
                    ->searchable()
                    ->placeholder('Search name')
                    ->getSearchResultsUsing(fn (string $search) => User::role('medical-rep')->where('name', 'like', "%{$search}%")->limit(50)->pluck('name', 'id'))
                    ->options(User::role('medical-rep')->pluck('name', 'id'))
                    ->getOptionLabelUsing(fn ($value): ?string => User::find($value)?->name)
                    ->preload()
                    ->hidden(auth()->user()->hasRole('medical-rep')),
                Select::make('vacation_type_id')
                    ->label('Vacation Type')
                    ->options(VacationType::all()->pluck('name', 'id'))
                    ->preload()
                    ->required(),
                DatePicker::make('start')
                    ->label('Start date')
                    ->closeOnDateSelection(),
                DatePicker::make('end')
                    ->label('End date')
                    ->closeOnDateSelection(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('repUser.name')
                    ->label('Client')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('start')
                    ->label('Start date')
                    ->dateTime('d-M-Y')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('end')
                    ->label('End date')
                    ->dateTime('d-M-Y')
                    ->sortable()
                    ->searchable(),
                IconColumn::make('approved')
                    ->boolean(),
                TextColumn::make('approved_at')
                    ->label('End date')
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
            'index' => Pages\ListVacations::route('/'),
            'create' => Pages\CreateVacation::route('/create'),
            'edit' => Pages\EditVacation::route('/{record}/edit'),
        ];
    }
}
