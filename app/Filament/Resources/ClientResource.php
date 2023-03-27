<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ClientResource\Pages;
use App\Filament\Resources\ClientResource\RelationManagers;
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
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ClientResource extends Resource
{
    protected static ?string $model = Client::class;

    protected static ?string $navigationIcon = 'heroicon-o-collection';

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
                Select::make('city_id')
                    ->label('City')
                    ->searchable()
                    ->relationship('city','name')
                    ->preload()
                    ->required(),
                Select::make('grade')
                    ->label('Grade')
                    ->options(['A'=>'A','B'=>'B','C'=>'C','N'=>'N','PH'=>'PH'])
                    ->getOptionLabelUsing(fn ($value): ?string => $value)
                    ->required(),
                Select::make('shift')
                    ->options(['AM'=>'AM','PM'=>'PM'])
                    ->getOptionLabelUsing(fn ($value): ?string => $value)
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
                TextColumn::make('city.name')
                    ->sortable()
                    ->label('City'),
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

    public static function getTranslatableLocales(): array
    {
        return ['en', 'ar'];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListClients::route('/'),
            'create' => Pages\CreateClient::route('/create'),
            'edit' => Pages\EditClient::route('/{record}/edit'),
        ];
    }
}
