<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Filament\Resources\UserResource\RelationManagers\RolesRelationManager;
use App\Models\Area;
use App\Models\Brick;
use App\Models\User;
use App\Traits\RolesOnlyResources;
use Awcodes\FilamentTableRepeater\Components\TableRepeater;
use Filament\Forms;
use Filament\Forms\Components\CheckboxList;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\DeleteAction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class UserResource extends Resource
{
    use RolesOnlyResources;
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-s-users';
    protected static ?string $navigationGroup = 'Admin management';
    protected static ?int $navigationSort = 1;


    // protected static bool $shouldRegisterNavigation = false;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Toggle::make('is_admin')
                    ->required(),
                TextInput::make('email')
                    ->email()
                    ->required()
                    ->maxLength(255),
                Select::make('cities')
                    ->relationship('cities','name')
                    ->multiple()
                    ->preload(),
                Select::make('parent_id')
                    ->label('Manager')
                    ->searchable()
                    ->placeholder('Search name')
                    ->getSearchResultsUsing(fn (string $search) => User::where('name', 'like', "%{$search}%")->limit(50)->pluck('name', 'id'))
                    ->options(User::pluck('name', 'id'))
                    // ->getOptionLabelUsing(fn ($value): ?string => User::find($value)?->name)
                    ->preload(),
                TextInput::make('password')
                    ->password()
                    ->dehydrateStateUsing(static fn(null|string $state): null|string=> filled($state)?bcrypt($state) : null)
                    ->required(static fn(Page $livewire): string => $livewire instanceof Pages\CreateUser)
                    ->dehydrated(static fn(null|string $state): bool => filled($state))
                    ->label(static fn(Page $livewire): string => ($livewire instanceof Pages\EditUser) ? 'New Password' : 'Password')
                    ->maxLength(32),
                CheckboxList::make('roles')
                    ->relationship('roles','display_name')
                    ->columns(2)
                    ->helperText('Only choose one!')
                    ->required(),
                Select::make('bricks')
                    ->label('Bricks')
                    ->visible(fn($record) => $record->hasRole('medical-rep') && !Str::contains(request()->path(),'create'))
                    ->multiple()
                    ->preload()
                    ->relationship('bricks','name'),
                Select::make('areas')
                    ->label('Areas')
                    ->visible(fn($record) => !$record->hasRole('medical-rep') && !Str::contains(request()->path(),'create'))
                    ->multiple()
                    ->preload()
                    ->relationship('areas','name'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name'),
                IconColumn::make('is_admin')
                    ->boolean(),
                TextColumn::make('roles.display_name'),
                TextColumn::make('email')
                    ->searchable(),
                TextColumn::make('manager.name')
                        ->label('Manager'),
                TextColumn::make('current_team_id'),
                TextColumn::make('created_at')
                    ->dateTime('d-M-Y g:i A'),
                TextColumn::make('deleted_at')
                    ->dateTime('d-M-Y g:i A'),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
                Tables\Actions\RestoreAction::make()
                    ->hidden(fn($record) => $record->deleted_at == null),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
                Tables\Actions\RestoreBulkAction::make(),
                Tables\Actions\ForceDeleteBulkAction::make(),
            ]);
    }
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getRelations(): array
    {
        return [
            // RolesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }

    public static function canAccessMe(): array
    {
        return ['super-admin','moderator'];
    }
}
