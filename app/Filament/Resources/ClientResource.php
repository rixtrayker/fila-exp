<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ClientResource\Pages;
use App\Filament\Resources\ClientResource\RelationManagers;
use App\Models\Brick;
use App\Models\City;
use App\Models\Client;
use App\Models\ClientType;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use Str;

class ClientResource extends Resource
{
    protected static ?string $model = Client::class;
    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?int $navigationSort = 3;
    protected static $clientTypes = [];

    protected static $bricks;
    public static function form(Form $form): Form
    {
        self::$bricks = Brick::get()->pluck('zone_code', 'id')->toArray();
        self::$clientTypes = ClientType::pluck('name','id')->toArray();

        return $form
            ->schema([
                TextInput::make('name_en')
                    ->label('Name')
                    ->required(),
                TextInput::make('name_ar')
                    ->label('Name (العربية)'),
                TextInput::make('email')
                    ->email()
                    ->label('Email'),
                TextInput::make('phone')
                    ->label('Phone'),
                TextInput::make('am_work')
                    ->label('AM work'),
                TextInput::make('address')
                    ->label('Address'),
                Select::make('brick_id')
                    ->label('Brick')
                    ->searchable()
                    ->options(self::$bricks)
                    ->getSearchResultsUsing(fn(string $search)=>ClientResource::searchBrick($search))
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
                TextColumn::make('am_work')
                    ->sortable()
                    ->searchable()
                    ->label('AM work'),
                TextColumn::make('address')
                    ->sortable()
                    ->searchable()
                    ->label('Address'),
                TextColumn::make('related_pharmacy')
                    ->sortable()
                    ->searchable()
                    ->label('Related Pharmacy'),
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
                Tables\Filters\SelectFilter::make('brick')
                    ->searchable()
                    ->options(self::$bricks)
                    ->relationship('brick','name'),
                Tables\Filters\SelectFilter::make('clientType')
                    ->relationship('clientType','name'),
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
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
          ->with(['clientType','brick','speciality'])
          ->scopes([
            'inMyAreas',
          ]);
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
        $result = array_filter(self::$bricks, function ($value) use ($search) {
            return strpos($value, $search) !== false;
        });

        return $result;
    }
}
