<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ExpensesResource\Pages;
use App\Filament\Resources\ExpensesResource\RelationManagers;
use App\Models\Expenses;
use App\Models\User;
use App\Traits\ResouerceHasPermission;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ExpensesResource extends Resource
{
    use ResouerceHasPermission;
    protected static ?string $model = Expenses::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationGroup = 'Requests';
    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('user_id')
                    ->label('Medical Rep')
                    ->searchable()
                    ->placeholder('Search name')
                    ->getSearchResultsUsing(fn (string $search) => User::where('name', 'like', "%{$search}%")->limit(50)->pluck('name', 'id'))
                    ->options(User::pluck('name', 'id'))
                    ->preload()
                    ->hidden(auth()->user()->hasRole('medical-rep')),
                DatePicker::make('date')
                    ->label('Date')
                    ->default(today())
                    ->closeOnDateSelection()
                    ->required(),
                TextInput::make('transportation')
                    ->label('Transportation')
                    ->numeric()
                    ->helperText('Money value of transportation')
                    ->minValue(1),
                TextInput::make('lodging')
                    ->label('Lodging')
                    ->numeric()
                    ->minValue(1),
                TextInput::make('mileage')
                    ->label('Mileage')
                    ->numeric()
                    ->minValue(1),
                TextInput::make('meal')
                    ->label('Meal')
                    ->numeric()
                    ->minValue(1),
                TextInput::make('telephone_postage')
                    ->label('Postage/Telephone/Fax')
                    ->numeric()
                    ->minValue(1),
                TextInput::make('medical_expenses')
                    ->label('Medical Expenses')
                    ->numeric()
                    ->minValue(1),
                TextInput::make('others')
                    ->label('Others')
                    ->numeric()
                    ->minValue(1),
                TextInput::make('others_description')
                    ->label('Others description')
                    ->requiredWith('others'),
                TextInput::make('total')
                    ->label('Total')
                    ->hidden(fn($context)=>$context !== 'view'),
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
                TextColumn::make('total')
                    ->label('Total')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('transportation')
                    ->label('Transportation')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('lodging')
                    ->label('Lodging')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('mileage')
                    ->label('Mileage')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('meal')
                    ->label('Meal')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('telephone_postage')
                    ->label('Postage/Telephone/Fax')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('daily_allowance')
                    ->label('Daily Allowance')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('medical_expenses')
                    ->label('Medical Expenses')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('others')
                    ->label('Others')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('others_description')
                    ->label('Others description')
                    ->sortable()
                    ->searchable(),
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
