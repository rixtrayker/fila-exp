<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OfficeWorkResource\Pages;
use App\Filament\Resources\OfficeWorkResource\RelationManagers;
use App\Models\OfficeWork;
use App\Traits\ResouerceHasPermission;
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

class OfficeWorkResource extends Resource
{
    use ResouerceHasPermission;

    protected static ?string $model = OfficeWork::class;

    protected static ?string $navigationIcon = 'heroicon-o-inbox-stack';
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('title')
                    ->required()
                    ->maxLength(255),
                Textarea::make('description')
                    ->required()
                    ->maxLength(65535),
                TimePicker::make('time_from')
                    ->label('Time from')
                    ->native(false)
                    ->required(),
                TimePicker::make('time_to')
                    ->label('Time to')
                    ->native(false)
                    ->required(),
                Select::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'approved' => 'Approved',
                    ])
                    ->required(),
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
                TextColumn::make('time_from')
                    ->dateTime('d-m-Y H:i'),
                TextColumn::make('time_to')
                    ->dateTime('d-m-Y H:i'),
                TextColumn::make('status')
                    ->sortable()
                    ->badge()
                    ->color(fn($state) => $state == 'pending' ? 'warning' : 'success'),
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
