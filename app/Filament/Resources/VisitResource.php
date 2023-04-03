<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VisitResource\Pages;
use App\Filament\Resources\VisitResource\RelationManagers;
use App\Models\CallType;
use App\Models\Client;
use App\Models\Product;
use App\Models\User;
use App\Models\Visit;
use App\Models\VisitType;
use Awcodes\FilamentTableRepeater\Components\TableRepeater;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class VisitResource extends Resource
{
    protected static ?string $model = Visit::class;

    protected static ?string $navigationIcon = 'heroicon-o-office-building';

    // protected static bool $shouldRegisterNavigation = false;
    protected static ?int $navigationSort = 1;


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('user_id')
                    ->label('Medical Rep')
                    ->searchable()
                    ->placeholder('Search name')
                    ->getSearchResultsUsing(fn (string $search) => User::role('medical-rep')->where('name', 'like', "%{$search}%")->limit(50)->pluck('name', 'id'))
                    ->options(User::role('medical-rep')->pluck('name', 'id'))
                    ->getOptionLabelUsing(fn ($value): ?string => User::find($value)?->name)
                    ->preload()
                    ->hidden(auth()->user()->hasRole('medical-rep')),
                Select::make('second_user_id')
                    ->label('2nd Medical Rep')
                    ->searchable()
                    ->placeholder('Search name')
                    ->getSearchResultsUsing(fn (string $search) => User::role('medical-rep')->where('name', 'like', "%{$search}%")->limit(50)->pluck('name', 'id'))
                    ->options(User::role('medical-rep')->pluck('name', 'id'))
                    ->getOptionLabelUsing(fn ($value): ?string => User::find($value)?->name)
                    ->preload(),
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
                Select::make('call_type_id')
                    ->label('Call Type')
                    ->options(CallType::all()->pluck('name', 'id'))
                    ->preload()
                    ->required(),
                DatePicker::make('next_visit')
                    ->label('Next call time')
                    ->closeOnDateSelection()
                    ->required(),
                DatePicker::make('visit_date')
                    ->default(today())
                    ->hidden(),
                Section::make('products')
                    ->disableLabel()
                    ->schema([
                        TableRepeater::make('products')
                        ->relationship('products')
                        ->disableLabel()
                        ->headers(['Product', 'Sample Count'])
                        ->emptyLabel('There is no product added.')
                        ->columnWidths([
                            'count' => '40px',
                            'product_id' => '180px',
                            'row_actions' => '20px',
                        ])
                        ->schema([
                            Select::make('product_id')
                                ->disableLabel()
                                ->placeholder('select a product')
                                ->options(Product::pluck('name','id')),
                            TextInput::make('count')
                                ->numeric()
                                ->minValue(1)
                                ->disableLabel(),
                        ])
                        ->disableItemMovement()
                        ->defaultItems(1),
                    ])->compact(),
                Textarea::make('comment')
                    ->label('Comment')
                    ->columnSpan('full')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->sortable(),
                TextColumn::make('user.name')
                    ->label('M.Rep')
                    ->hidden(auth()->user()->hasRole('medical-rep'))
                    ->sortable(),
                TextColumn::make('client.name_en')
                    ->label('Client')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('secondRep.name')
                    ->label('M.Rep 2nd'),
                TextColumn::make('created_at')
                    ->dateTime('d-M-Y')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('comment')
                    ->wrap(),
            ])
            ->filters([

            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\DeleteAction::make()->hidden(
                    auth()->user()->hasRole('medical-rep')
                )
            ])
            ->bulkActions([
                // Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [

        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVisits::route('/'),
            'create' => Pages\CreateVisit::route('/create'),
            'view' => Pages\ViewVist::route('/{record}'),
        ];
    }
}
