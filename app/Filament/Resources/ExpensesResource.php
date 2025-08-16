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
use Filament\Tables\Actions\Action;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
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
                // Approval and payment fields (read-only in form)
                Forms\Components\Section::make('Approval & Payment')
                    ->schema([
                        TextInput::make('approved')
                            ->label('Approval Status')
                            ->disabled()
                            ->formatStateUsing(fn ($state) => $state > 0 ? 'Approved' : ($state < 0 ? 'Rejected' : 'Pending'))
                            ->hidden(fn($context)=>$context !== 'view'),
                        DatePicker::make('approved_at')
                            ->label('Approved At')
                            ->disabled()
                            ->hidden(fn($context)=>$context !== 'view'),
                        Toggle::make('is_paid')
                            ->label('Is Paid')
                            ->disabled()
                            ->hidden(fn($context)=>$context !== 'view'),
                        DatePicker::make('paid_at')
                            ->label('Paid At')
                            ->disabled()
                            ->hidden(fn($context)=>$context !== 'view'),
                        Select::make('paid_by')
                            ->label('Paid By')
                            ->disabled()
                            ->options(User::pluck('name', 'id'))
                            ->hidden(fn($context)=>$context !== 'view'),
                    ])
                    ->hidden(fn($context)=>$context === 'create')
                    ->collapsible(),
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
                // Approval and payment status columns
                IconColumn::make('approved')
                    ->label('Approval')
                    ->boolean()
                    ->getStateUsing(fn ($record) => $record->approved > 0)
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-clock')
                    ->trueColor('success')
                    ->falseColor('warning'),
                IconColumn::make('is_paid')
                    ->label('Paid')
                    ->boolean()
                    ->trueIcon('heroicon-o-banknotes')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('approved')
                    ->label('Approval Status')
                    ->placeholder('All')
                    ->trueLabel('Approved')
                    ->falseLabel('Pending/Rejected'),
                Tables\Filters\TernaryFilter::make('is_paid')
                    ->label('Payment Status')
                    ->placeholder('All')
                    ->trueLabel('Paid')
                    ->falseLabel('Unpaid'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                // Approval actions
                Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn ($record) => $record->canApprove())
                    ->action(fn ($record) => $record->approve())
                    ->requiresConfirmation()
                    ->modalHeading('Approve Expense')
                    ->modalDescription('Are you sure you want to approve this expense?')
                    ->modalSubmitActionLabel('Yes, approve'),
                Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn ($record) => $record->canDecline())
                    ->action(fn ($record) => $record->reject())
                    ->requiresConfirmation()
                    ->modalHeading('Reject Expense')
                    ->modalDescription('Are you sure you want to reject this expense?')
                    ->modalSubmitActionLabel('Yes, reject'),
                // Payment action
                Action::make('markAsPaid')
                    ->label('Mark as Paid')
                    ->icon('heroicon-o-banknotes')
                    ->color('success')
                    ->visible(fn ($record) => $record->canBePaid() && auth()->user()->hasRole('accountant'))
                    ->action(fn ($record) => $record->markAsPaid())
                    ->requiresConfirmation()
                    ->modalHeading('Mark as Paid')
                    ->modalDescription('Are you sure you want to mark this expense as paid?')
                    ->modalSubmitActionLabel('Yes, mark as paid'),
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

    public static function canCreate(): bool
    {
        return auth()->user()->hasRole(['medical-rep', 'super-admin']);
    }

    public static function canEdit(Model $record): bool
    {
        // Users can edit their own expenses if not approved yet
        if (auth()->user()->hasRole('medical-rep') && $record->user_id === auth()->id()) {
            return $record->approved === 0;
        }

        // Super admin can edit any expense
        return auth()->user()->hasRole('super-admin');
    }

    public static function canDelete(Model $record): bool
    {
        // Users can delete their own expenses if not approved yet
        if (auth()->user()->hasRole('medical-rep') && $record->user_id === auth()->id()) {
            return $record->approved === 0;
        }

        // Super admin can delete any expense
        return auth()->user()->hasRole('super-admin');
    }
}
