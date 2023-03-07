<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VisitResource\Pages;
use App\Filament\Resources\VisitResource\RelationManagers;
use App\Models\CallType;
use App\Models\Client;
use App\Models\User;
use App\Models\Visit;
use App\Models\VisitType;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\DatePicker;
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

    protected static ?string $navigationIcon = 'heroicon-o-collection';

    // protected static bool $shouldRegisterNavigation = false;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('second_user_id')
                    ->label('2nd Medical Rep')
                    ->searchable()
                    ->placeholder('You can search both arabic and english name')
                    ->getSearchResultsUsing(fn (string $search) => User::role('medical-rep')->where('name', 'like', "%{$search}%")->limit(50)->pluck('name_en', 'id'))
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
                TextColumn::make('client.name_en')
                    ->label('Client')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('user.name')
                    ->label('M.Rep')
                    ->sortable(),
                TextColumn::make('secondRep.name')
                    ->label('M.Rep 2nd'),
                TextColumn::make('created_at')
                ->dateTime('d-M-Y')
                ->sortable()
                ->searchable(),
                TextColumn::make('comment')
                ->limit(60),
            ])
            ->filters([

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

        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVisits::route('/'),
            'create' => Pages\CreateVisit::route('/create'),
            'edit' => Pages\EditVisit::route('/{record}/edit'),
        ];
    }
}
