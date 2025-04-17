<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ClientRequestResource\Pages;
use App\Models\Client;
use App\Models\ClientRequest;
use App\Models\ClientRequestType;
use App\Models\User;
use App\Traits\ResouerceHasPermission;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;

class ClientRequestResource extends Resource
{
    use ResouerceHasPermission;
    protected static ?string $model = ClientRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-receipt-percent';
    protected static ?int $navigationSort = 1;
    protected static ?string $navigationGroup = 'Requests';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('user_id')
                    ->label('Medical Rep')
                    ->searchable()
                    ->placeholder('Search name')
                    ->getSearchResultsUsing(fn (string $search) => User::where('name', 'like', "%{$search}%")->limit(50)->pluck('name', 'id'))
                    ->options(User::pluck('name', 'id'))
                    ->getOptionLabelUsing(fn ($value): ?string => User::find($value)?->name)
                    ->preload()
                    ->hidden(auth()->user()->hasRole('medical-rep')),
                Select::make('client_id')
                    ->label('Client')
                    ->searchable()
                    ->placeholder('Search by name or phone or speciality')
                    ->getSearchResultsUsing(function(string $search){
                        return Client::where('name_en', 'like', "%{$search}%")
                            ->orWhere('name_ar', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%")
                            ->orWhereHas('speciality', function ($q) use ($search) {
                                $q->where('name','like', "%{$search}%");
                            })->limit(50)->pluck('name_en', 'id');
                    })
                    ->options(Client::pluck('name_en', 'id'))
                    ->getOptionLabelUsing(fn ($value): ?string => Client::find($value)?->name)
                    ->preload()
                    ->required(),
                Select::make('client_request_type_id')
                    ->label('Request type')
                    ->options(ClientRequestType::all()->pluck('name', 'id'))
                    ->preload()
                    ->required(),
                TextInput::make('expected_revenue')
                    ->label('Expected Revenue')
                    ->numeric()
                    ->minValue(1)
                    ->required(),
                TextInput::make('request_cost')
                    ->label('Expected Cost')
                    ->numeric()
                    ->minValue(1)
                    ->required(),
                Select::make('ordered_before')
                    ->label('Previous orders')
                    ->options(['yes'=>'Yes','no'=>'No'])
                    ->preload()
                    ->required(),
                Select::make('rx_rate')
                    ->label('Previous rate of RX')
                    ->options(['yes'=>'Yes','no'=>'No'])
                    ->preload()
                    ->required(),
                DatePicker::make('response_date')
                    ->label('Max approval date')
                    ->closeOnDateSelection()
                    ->required(),
                DatePicker::make('from_date')
                    ->label('Expected from')
                    ->closeOnDateSelection()
                    ->required(),
                DatePicker::make('to_date')
                    ->label('Expected to')
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
                TextColumn::make('user.name')
                    ->label('M.Rep')
                    ->hidden(auth()->user()->hasRole('medical-rep')),
                TextColumn::make('client.name_en')
                    ->searchable()
                    ->sortable()
                    ->label('Client Name'),
                TextColumn::make('client.name_ar')
                    ->label('Client Name (العربية)')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('requestType.name')
                    ->label('Request Type'),
                TextColumn::make('request_cost')
                    ->label('Expected Cost'),
                TextColumn::make('expected_revenue')
                    ->label('Expected Revenue'),
                TextColumn::make('response_date')
                    ->label('Expected response time')
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
                TextColumn::make('description')
                    ->label('Description')
                    ->limit(60),
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
                Tables\Actions\Action::make('decline')
                    ->label('Decline')
                    ->color('danger')
                    ->icon('heroicon-m-x-mark')
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

    // public static function getEloquentQuery(): Builder
    // {
    //     return parent::getEloquentQuery()
    //         ->withoutGlobalScopes([
    //             GetMineScope::class
    //         ]);
    // }
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListClientRequests::route('/'),
            'create' => Pages\CreateClientRequest::route('/create'),
            'edit' => Pages\EditClientRequest::route('/{record}/edit'),
        ];
    }
}
