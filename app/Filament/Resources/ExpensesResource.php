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
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables;

use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;

use Illuminate\Database\Eloquent\Model;

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
                    ->minDate(today())
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
                Textarea::make('comment')
                    ->label('Comment')
                    ->columnSpan('full'),
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
                TextColumn::make('date')
                    ->dateTime('d-M-Y')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('description')
                    ->limit(60),
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

    public static function canView(Model $record): bool
    {
        return auth()->user()->hasRole(['medical-rep', 'super-admin', 'accountant', 'accountant-manager']);
    }

    public static function canViewAny(): bool
    {
        return auth()->user()->hasRole(['medical-rep', 'super-admin', 'accountant', 'accountant-manager']);
    }

    public static function canCreate(): bool
    {
        return auth()->user()->hasRole(['medical-rep', 'super-admin']);
    }

    public static function canEdit(Model $record): bool
    {
        // Users can edit their own expenses
        if (auth()->user()->hasRole('medical-rep') && $record->user_id === auth()->id()) {
            return true;
        }

        // Super admin can edit any expense
        return auth()->user()->hasRole('super-admin');
    }

    public static function canDelete(Model $record): bool
    {
        // Users can delete their own expenses
        if (auth()->user()->hasRole('medical-rep') && $record->user_id === auth()->id()) {
            return true;
        }

        // Super admin can delete any expense
        return auth()->user()->hasRole('super-admin');
    }
}
