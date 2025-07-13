<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OfficeWorkResource\Pages;
use App\Filament\Resources\OfficeWorkResource\RelationManagers;
use App\Models\OfficeWork;
use App\Traits\ResourceHasPermission;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Support\Str;

class OfficeWorkResource extends Resource
{
    use ResourceHasPermission;

    protected static ?string $model = OfficeWork::class;

    protected static ?string $navigationIcon = 'heroicon-o-inbox-stack';
    protected static ?string $navigationLabel = 'Office work';
    protected static ?string $navigationGroup = 'Visits';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('title')
                    ->required()
                    ->maxLength(255),
                Textarea::make('description')
                    ->required()
                    ->rows(3)
                    ->maxLength(65535),
                // Select shift am, pm, am/pm
                Select::make('shift')
                    ->options([
                        'am' => 'AM',
                        'pm' => 'PM',
                        'am/pm' => 'AM/PM',
                    ])
                    ->native(false)
                    ->default('am')
                    ->required(),
                // TimePicker::make('time_from')
                //     ->label('Time from')
                //     ->native(false)
                //     ->seconds(false)
                //     ->nullable(),
                // TimePicker::make('time_to')
                //     ->label('Time to')
                //     ->native(false)
                //     ->seconds(false)
                //     ->nullable(),
                // Select::make('status')
                //     ->options([
                //         'pending' => 'Pending',
                //         'approved' => 'Approved',
                //     ])
                //     ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title'),
                TextColumn::make('description')
                    ->wrap()
                    ->searchable(),
                // TextColumn::make('time_from')
                //     ->dateTime('d-m-Y H:i'),
                // TextColumn::make('time_to')
                //     ->dateTime('d-m-Y H:i'),
                TextColumn::make('shift')
                    ->sortable()
                    ->searchable()
                    ->label('Shift')
                    ->badge()
                    ->formatStateUsing(fn($state) => Str::upper($state)),
                TextColumn::make('status')
                    ->sortable()
                    ->badge()
                    ->color(fn($state) => match($state) {
                        'pending' => 'warning',
                        'approved' => 'success',
                        'rejected' => 'danger',
                    }),
                TextColumn::make('user.name')
                    ->label('Created by')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('created_at')
                    ->label('Created at')
                    ->searchable()
                    ->sortable()
                    ->dateTime('d-m-Y H:i'),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalDescription('Are you sure you want to approve this office work?')
                    ->visible(fn($record) => $record->status == 'pending' && $record->user->parent_id == auth()->id())
                    ->action(fn($record) => $record->approve()),
                Tables\Actions\Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalDescription('Are you sure you want to reject this office work?')
                    ->visible(fn($record) => $record->status == 'pending' && $record->user->parent_id == auth()->id())
                    ->action(fn($record) => $record->reject()),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
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
            'index' => Pages\ListOfficeWorks::route('/'),
            'create' => Pages\CreateOfficeWork::route('/create'),
            'edit' => Pages\EditOfficeWork::route('/{record}/edit'),
        ];
    }
}
