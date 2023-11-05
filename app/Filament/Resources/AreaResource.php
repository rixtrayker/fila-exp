<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AreaResource\Pages;
use App\Filament\Resources\AreaResource\RelationManagers;
use App\Models\Area;
use App\Models\Brick;
use Awcodes\FilamentTableRepeater\Components\TableRepeater;
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

class AreaResource extends Resource
{
    protected static ?string $model = Area::class;

    protected static ?string $navigationIcon = 'heroicon-o-collection';
    protected static ?string $navigationGroup = 'Zone management';
    protected static $bricks;

    public static function form(Form $form): Form
    {
        self::$bricks = Brick::get()->pluck('full_name', 'id')->toArray();

        return $form
            ->schema([
                TextInput::make('name')
                    ->label('Name')
                    ->unique(ignoreRecord: true)
                    ->required(),
                Select::make('bricks')
                    ->label('Bricks')
                    ->preload()
                    ->relationship('bricks','name')
                    ->getOptionLabelFromRecordUsing(fn ($record): ?string => $record->full_name)
                    ->multiple()
                    ->required(),
                    //bad preformance
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('bricksNames')
                    ->label('Bricks'),
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
            'index' => Pages\ListAreas::route('/'),
            'create' => Pages\CreateArea::route('/create'),
            'edit' => Pages\EditArea::route('/{record}/edit'),
        ];
    }

    private static function searchBrick(string $search)
    {
        $result = array_filter(self::$bricks, function ($value) use ($search) {
            return strpos($value, $search) !== false;
        });

        return $result;
    }
}
