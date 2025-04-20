<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ClientResource\Pages;
use App\Filament\Resources\ClientResource\RelationManagers;
use ArberMustafa\FilamentLocationPickrField\Forms\Components\LocationPickr;
use App\Models\Brick;
use App\Models\City;
use App\Models\Client;
use App\Models\ClientType;
use App\Traits\ResourceHasPermission;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use Cheesegrits\FilamentGoogleMaps\Fields\Map;

use Str;

class ClientResource extends Resource
{
    use ResourceHasPermission;
    protected static ?string $model = Client::class;
    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $navigationGroup = 'Admin management';
    protected static ?int $navigationSort = 1;
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
                    ->toggleable(isToggledHiddenByDefault:true)
                    ->label('Email'),
                TextColumn::make('phone')
                    ->sortable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault:true)
                    ->label('Phone'),
                TextColumn::make('am_work')
                    ->sortable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault:true)
                    ->label('AM work'),
                IconColumn::make('mapUrl')
                    ->label('Map URL')
                    ->url(fn($record) => $record->mapUrl ?? '#')
                    ->visible(true)
                    ->openUrlInNewTab()
                    ->icon('heroicon-o-map-pin')
                    ->tooltip(fn($record) => $record->mapUrl ? 'Open in Google Maps' : 'Capture location first')
                    ->color(fn($record) => $record->mapUrl ? 'success' : 'danger'),
                TextColumn::make('address')
                    ->sortable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault:true)
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
                    ->toggleable(isToggledHiddenByDefault:true)
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
                self::captureLocation(),
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
    public static function captureLocation():Action
    {
        return Action::make('captureLocation')
            ->icon('heroicon-o-map-pin')
            ->modalHeading('Capture Location')
            ->modalDescription('Are you sure you want to capture the location?')
            ->label(fn($record)=>$record->mapUrl ? 'Edit Location' : 'Add Location')
            ->form([
                LocationPickr::make('location')
                    ->mapControls([
                        'mapTypeControl'    => false,
                        'scaleControl'      => true,
                        'streetViewControl' => false,
                        'rotateControl'     => false,
                        'fullscreenControl' => true,
                        'searchBoxControl'  => true,
                        'zoomControl'       => true,
                    ])
                    ->defaultZoom(15)
                    ->defaultView('roadmap')
                    ->defaultLocation([30.608837,32.3063521])
                    ->draggable()
                    ->clickable()
                    ->height('40vh'),
            ])
            ->action(fn($record,array $data)=> $record->setLocation($data['location']))
            ->color(fn($record)=>$record->mapUrl ? 'secondary' : 'success');
    }
}
