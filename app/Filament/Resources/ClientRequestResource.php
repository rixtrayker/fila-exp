<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ClientRequestResource\Pages;
use App\Filament\Resources\ClientRequestResource\RelationManagers;
use App\Models\Client;
use App\Models\ClientRequest;
use App\Models\ClientRequestType;
use App\Models\User;
use App\Models\VisitType;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ClientRequestResource extends Resource
{
    protected static ?string $model = ClientRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-collection';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('client_id')
                    ->label('Client')
                    ->searchable()
                    ->placeholder('You can search both arabic and english name')
                    ->getSearchResultsUsing(fn (string $search) => Client::where('name_en', 'like', "%{$search}%")->orWhere('name_ar', 'like', "%{$search}%")->limit(50)->pluck('name_en', 'id'))
                    ->options(Client::pluck('name_en', 'id'))
                    ->getOptionLabelUsing(fn ($value): ?string => Client::find($value)?->name)
                    ->preload()
                    ->required(),
                Select::make('visit_type_id')
                    ->label('Visit Type')
                    ->options(VisitType::all()->pluck('name', 'id'))
                    ->preload()
                    ->required(),
                Select::make('client_tequest_type_id')
                    ->label('Request type')
                    ->options(ClientRequestType::all()->pluck('name', 'id'))
                    ->preload()
                    ->required(),
                TextInput::make('expected_revenue')
                    ->numeric()
                    ->minValue(1)
                    ->required(),
                TextInput::make('expected_cost')
                    ->numeric()
                    ->minValue(1)
                    ->required(),
                Select::make('ordered_before')
                    ->label('Previous orders')
                    ->options(['yes','no'])
                    ->preload()
                    ->required(),
                Select::make('rx_rate')
                    ->label('Previous rate of RX')
                    ->options(['yes','no'])
                    ->preload()
                    ->required(),
                DatePicker::make('response_date')
                    ->label('Expected response time')
                    ->closeOnDateSelection()
                    ->required(),
                Textarea::make('description')
                    ->label('Description')
                    ->columnSpan('full')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([

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
            'index' => Pages\ListClientRequests::route('/'),
            'create' => Pages\CreateClientRequest::route('/create'),
            'edit' => Pages\EditClientRequest::route('/{record}/edit'),
        ];
    }
}
