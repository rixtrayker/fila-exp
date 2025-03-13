<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BrickResource\Pages;
use App\Filament\Resources\BrickResource\RelationManagers;
use App\Models\Brick;
use App\Traits\ResouerceHasPermission;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\DB;

class BrickResource extends Resource
{
    use ResouerceHasPermission;
    protected static ?string $model = Brick::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationGroup = 'Zone management';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->required(),
                Select::make('city_id')
                    ->label('City name')
                    ->relationship('city','name')
                    ->preload()
                    ->required(),
                Select::make('area_id')
                    ->label('Area name')
                    ->relationship('area','name')
                    ->preload()
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                ->label('Name'),
                TextColumn::make('city_name')
                    ->label('City name'),
                TextColumn::make('area_name')
                    ->label('Area name'),
                TextColumn::make('medical_reps')
                    ->label('Medical Reps'),
            ])
            ->paginated([10,50,100,250,500,1000])
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

    public static function getEloquentQuery(): Builder
    {
        DB::statement("SET SESSION sql_mode=''");

        return Brick::with(['city','area'])->select(
            'bricks.id as id',
            'bricks.name as name',
            'areas.name as area_name',
            'cities.name as city_name',
            DB::raw('GROUP_CONCAT( users.name SEPARATOR ", ") AS medical_reps'),
        )
            ->leftJoin('cities', 'cities.id', '=', 'bricks.city_id')
            ->leftJoin('areas', 'areas.id', '=', 'bricks.area_id')
            ->leftJoin('area_user', 'area_user.area_id', '=', 'areas.id')
            ->leftJoin('users', 'users.id', '=', 'area_user.user_id')
            ->groupBy('bricks.id')
            ->orderBy('bricks.id', 'DESC');
    }

    public static function getRecordRouteKeyName(): string|null {
        return 'bricks.id';
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
            'index' => Pages\ListBricks::route('/'),
            'create' => Pages\CreateBrick::route('/create'),
            'edit' => Pages\EditBrick::route('/{record}/edit'),
        ];
    }
}
