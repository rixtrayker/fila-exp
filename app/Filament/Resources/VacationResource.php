<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VacationResource\Pages;
use App\Filament\Resources\VacationResource\RelationManagers;
use App\Models\User;
use App\Models\Vacation;
use App\Models\VacationRequest;
use App\Models\VacationType;
use Awcodes\FilamentTableRepeater\Components\TableRepeater;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
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
                // Select::make('user_id')
                //     ->label('Medical Rep')
                //     ->searchable()
                //     ->placeholder('Search name')
                //     ->getSearchResultsUsing(fn (string $search) => User::mine()->where('name', 'like', "%{$search}%")->limit(50)->pluck('name', 'id'))
                //     ->options(User::role('medical-rep')->pluck('name', 'id'))
                //     ->getOptionLabelUsing(fn ($value): ?string => User::find($value)?->name)
                //     ->preload()
                //     ->hidden(auth()->user()->hasRole('medical-rep')),
                Select::make('vacation_type_id')
                    ->label('Vacation Type')
                    ->options(VacationType::all()->pluck('name', 'id'))
                    ->preload()
                    ->required(),
                Section::make('Durations')
                    ->disableLabel()
                    ->schema([
                        TableRepeater::make('vacationDurations')
                            ->relationship('vacationDurations')
                            ->reactive()
                            ->disableLabel()
                            ->headers(['Start date','End date' , 'From shift', 'To Shift'])
                            ->emptyLabel('There is no vacation duration added.')
                            ->columnWidths([
                                'start' => '240px',
                                'end' => '240px',
                                'start_shift' => '140px',
                                'end_shift' => '140px',
                                'row_actions' => '20px',
                            ])
                        ->schema([
                            DatePicker::make('start')
                                ->disableLabel()
                                ->closeOnDateSelection()
                                ->required(),
                            DatePicker::make('end')
                                ->disableLabel()
                                ->closeOnDateSelection()
                                ->required(),
                            Select::make('start_shift')
                                ->disableLabel()
                                ->default('AM')
                                ->options(['AM'=>'AM','PM'=>'PM'])
                                ->required(),
                            Select::make('end_shift')
                                ->disableLabel()
                                ->default('PM')
                                ->options(['AM'=>'AM','PM'=>'PM'])
                                ->required(),
                        ])->disableItemMovement()
                        ->defaultItems(1),
                    ])->compact(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('repUser.name')
                    ->label('Medical Rep')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('start_at')
                    ->label('Start date') // todo:
                    ->dateTime('d-M-Y')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('end_at')
                    ->label('End date')
                    ->dateTime('d-M-Y')
                    ->sortable()
                    ->searchable(),
                IconColumn::make('approved')
                    ->colors(function($record){
                        if($record->approved > 0)
                            return ['success' => $record->approved];
                        if($record->approved < 0)
                            return ['danger' => $record->approved];
                        return ['secondary'];
                    })
                    ->options(function($record){
                        if($record->approved > 0)
                                return ['heroicon-o-check-circle' => $record->approved];
                        if($record->approved < 0)
                            return ['heroicon-o-x-circle' =>  $record->approved];
                        return ['heroicon-o-clock'];
                    }),
                TextColumn::make('approved_by')
                    ->label('Approved By'),
                TextColumn::make('approved_at')
                    ->label('Approved at')
                    ->dateTime('d-M-Y')
                    ->sortable()
                    ->searchable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('approve')
                    ->label('Approve')
                    ->color('success')
                    ->icon('heroicon-o-check')
                    ->visible(fn($record) => $record->canApprove())
                    ->action(fn($record) => $record->approve()),
                Tables\Actions\Action::make('reject')
                    ->label('Reject')
                    ->color('danger')
                    ->icon('heroicon-s-x')
                    ->visible(fn($record) => $record->canDecline())
                    ->action(fn($record) => $record->reject()),
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
