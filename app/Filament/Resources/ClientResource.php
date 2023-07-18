<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ClientResource\Pages;
use App\Filament\Resources\ClientResource\RelationManagers;
use App\Models\Brick;
use App\Models\City;
use App\Models\Client;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Str;

class ClientResource extends Resource
{
    protected static ?string $model = Client::class;
    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?int $navigationSort = 3;


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name_en')
                    ->label('Name')
                    ->required(),
                TextInput::make('name_ar')
                    ->label('Name (العربية)')
                    ->required(),
                TextInput::make('email')
                    ->email()
                    ->label('Email'),
                TextInput::make('phone')
                    ->label('Phone')
                    ->required(),
                TextInput::make('address')
                    ->label('Address')
                    ->required(),
                Select::make('brick_id')
                    ->label('Brick')
                    ->searchable()
                    ->options(Brick::get()->pluck('zone_code', 'id'))
                    ->getSearchResultsUsing(fn(string $search)=>ClientResource::searchCity($search))
                    ->preload()
                    ->required(),
                Select::make('grade')
                    ->label('Grade')
                    ->options(['A'=>'A','B'=>'B','C'=>'C','N'=>'N','PH'=>'PH'])
                    ->required(),
                Select::make('shift')
                    ->options(['AM'=>'AM','PM'=>'PM'])
                    ->label('Shift'),
                Select::make('client_type_id')
                    ->label('Type')
                    ->relationship('clientType','name')
                    ->required(),
                Select::make('speciality_id')
                    ->label('Speciality')
                    ->relationship('speciality','name')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name_en')
                    ->searchable()
                    ->sortable()
                    ->label('Name'),
                TextColumn::make('name_ar')
                    ->label('Name (العربية)')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault:true),
                TextColumn::make('email')
                    ->sortable()
                    ->searchable()
                    ->label('Email'),
                TextColumn::make('phone')
                    ->sortable()
                    ->searchable()
                    ->label('Phone'),
                TextColumn::make('address')
                    ->sortable()
                    ->searchable()
                    ->label('Address'),
                TextColumn::make('brick.name')
                    ->sortable()
                    ->label('Brick'),
                TextColumn::make('grade')
                    ->sortable()
                    ->searchable()
                    ->label('Grade'),
                TextColumn::make('shift')
                    ->sortable()
                    ->searchable()
                    ->label('Shift'),
                TextColumn::make('clientType.name')
                    ->sortable()
                    ->label('Type'),
                TextColumn::make('speciality.name')
                    ->sortable()
                    ->label('Speciality'),
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
            'index' => Pages\ListClients::route('/'),
            'create' => Pages\CreateClient::route('/create'),
            'view' => Pages\ViewClient::route('/{record}'),
            'edit' => Pages\EditClient::route('/{record}/edit'),
        ];
    }

    public static function searchBrick(string $search)
    {
        return collect(Brick::get())
            ->filter(
                function ($record) use ($search) {
                    return Str::contains($record->zone_code,$search);
                }
            )->pluck('zone_code', 'id');
    }
}
