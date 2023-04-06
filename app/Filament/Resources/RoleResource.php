<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RoleResource\Pages;
use App\Filament\Resources\RoleResource\RelationManagers\PermissionsRelationManager;
use App\Traits\RolesOnlyResources;
use Filament\Forms\Components\TextInput;
use Spatie\Permission\Models\Role;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\Relationship;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class RoleResource extends Resource
{
    use RolesOnlyResources;
    protected static ?string $model = Role::class;

    protected static ?string $navigationIcon = 'heroicon-s-cog';
    protected static ?string $navigationGroup = 'Admin management';
    protected static ?int $navigationSort = 2;


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->label('System name')
                    ->unique(ignoreRecord:true)
                    ->required(),
                TextInput::make('display_name')
                    ->label('Display name')
                    ->unique(ignoreRecord:true)
                    ->required(),
                TextInput::make('description')
                    ->label('Description')
                    ->required(),
                Select::make('permissions')
                    ->relationship('permissions','name')
                    ->multiple()
                    ->preload()
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->sortable(),
                TextColumn::make('name')
                    ->label('System name')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('display_name')
                    ->label('Display name')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('description')
                    ->label('Description')
                    ->wrap(),
                TextColumn::make('created_at')
                    ->dateTime('d-M-Y')
                    ->sortable()
                    ->searchable(),
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
            PermissionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRoles::route('/'),
            'create' => Pages\CreateRole::route('/create'),
            'edit' => Pages\EditRole::route('/{record}/edit'),
        ];
    }
}
