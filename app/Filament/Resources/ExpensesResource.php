<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ExpensesResource\Pages;
use App\Filament\Resources\ExpensesResource\RelationManagers;
use App\Models\Expenses;
use App\Models\User;
use App\Traits\ResourceHasPermission;
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
    use ResourceHasPermission;
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
                    ->getSearchResultsUsing(fn (string $search) => User::allMine()->where('name', 'like', "%{$search}%")->limit(50)->pluck('name', 'id'))
                    ->options(User::allMine()->pluck('name', 'id'))
                    ->preload()
                    ->hidden(auth()->user()->hasRole('medical-rep')),
                DatePicker::make('date')
                    ->label('Date')
                    ->default(today())
                    ->closeOnDateSelection()
                    ->required(),
                TextInput::make('from')
                    ->label('From')
                    ->required(),
                TextInput::make('to')
                    ->label('To')
                    ->required(),
                Textarea::make('description')
                    ->label('Description')
                    ->required(),
                TextInput::make('distance')
                    ->label('Distance (km)')
                    ->numeric()
                    ->minValue(0),
                TextInput::make('transportation')
                    ->label('Transportation (if no car)')
                    ->numeric()
                    ->helperText('Money value of transportation if no car')
                    ->minValue(0),
                TextInput::make('accommodation')
                    ->label('Accommodation (Hotel)')
                    ->numeric()
                    ->minValue(0),
                TextInput::make('meal')
                    ->label('Meals')
                    ->numeric()
                    ->helperText('Money value of meals')
                    ->minValue(0),
                TextInput::make('telephone_postage')
                    ->label('Postage/Telephone/Fax')
                    ->numeric()
                    ->minValue(0),
                TextInput::make('daily_allowance')
                    ->label('Daily Allowance')
                    ->numeric()
                    ->helperText('Daily allowance amount')
                    ->minValue(0),
                TextInput::make('medical_expenses')
                    ->label('Medical Expenses')
                    ->numeric()
                    ->minValue(0),
                TextInput::make('others')
                    ->label('Others')
                    ->numeric()
                    ->minValue(0),
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
                TextColumn::make('from')
                    ->label('From')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('to')
                    ->label('To')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('distance')
                    ->label('Distance (km)')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('transportation')
                    ->label('Transportation')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('accommodation')
                    ->label('Accommodation')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('meal')
                    ->label('Meals')
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
                TextColumn::make('total')
                    ->label('Total')
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
