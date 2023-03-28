<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ExpensesResource\Pages;
use App\Filament\Resources\ExpensesResource\RelationManagers;
use App\Models\Expenses;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
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

class ExpensesResource extends Resource
{
    protected static ?string $model = Expenses::class;

    protected static ?string $navigationIcon = 'heroicon-o-cash';

    protected static ?int $navigationSort = 8;


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('user_id')
                    ->label('Medical Rep')
                    ->searchable()
                    ->placeholder('Search name')
                    ->getSearchResultsUsing(fn (string $search) => User::role('medical-rep')->where('name', 'like', "%{$search}%")->limit(50)->pluck('name', 'id'))
                    ->options(User::role('medical-rep')->pluck('name', 'id'))
                    ->getOptionLabelUsing(fn ($value): ?string => User::find($value)?->name)
                    ->preload()
                    ->hidden(auth()->user()->hasRole('medical-rep')),
                DatePicker::make('date')
                    ->label('Date')
                    ->closeOnDateSelection()
                    ->required(),
                TextInput::make('distance')
                    ->numeric()
                    ->requiredWithout('amount'),
                TextInput::make('amount')
                    ->numeric()
                    ->minValue(1)
                    ->requiredWithout('distance'),
                Toggle::make('is_trasporation')
                    ->default(1)
                    ->requiredWith('amount'),
                Textarea::make('comment')
                    ->label('Comment')
                    ->columnSpan('full')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label('M.Rep')
                    ->hidden(auth()->user()->hasRole('medical-rep'))
                    ->sortable(),
                TextColumn::make('distance')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('amount')
                    ->sortable()
                    ->searchable(),
                IconColumn::make('is_trasporation')
                    ->sortable(),
                TextColumn::make('date')
                    ->dateTime('d-M-Y')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('comment')
                    ->limit(60),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
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
            'index' => Pages\ListExpenses::route('/'),
            'create' => Pages\CreateExpenses::route('/create'),
            'view' => Pages\ViewExpenses::route('/{record}'),
            'edit' => Pages\EditExpenses::route('/{record}/edit'),
        ];
    }
}
