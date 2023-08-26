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

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->label('Name')
                    ->unique(ignoreRecord: true)
                    ->required(),
                Select::make('bricks')
                    ->label('Bricks')
                    ->multiple()
                    ->preload()
                    ->relationship('bricks','name'),

                    // Section::make('bricks')
                    //     ->disableLabel()
                    //     ->schema([
                    //         TableRepeater::make('bricks')
                    //             ->createItemButtonLabel('Add Brick')
                    //             ->relationship('areaBricks')
                    //             ->disableLabel()
                    //             ->emptyLabel('There is no Bricks added.')
                    //             ->schema([
                    //                 Select::make('brick_id')
                    //                     ->label('Brick')
                    //                     ->placeholder('select a brick')
                    //                     ->options(Brick::pluck('name','id')),
                    //             ])
                    //             ->disableItemMovement()
                    //             ->defaultItems(1),
                    //     ])->compact(),
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
}
