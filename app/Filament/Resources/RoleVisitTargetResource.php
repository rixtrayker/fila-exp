<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RoleVisitTargetResource\Pages;
use App\Models\ClientType;
use App\Models\Role;
use App\Models\RoleVisitTarget;
use App\Traits\ResourceHasPermission;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Validation\Rules\Unique;

class RoleVisitTargetResource extends Resource
{
    use ResourceHasPermission;

    protected static ?string $model = RoleVisitTarget::class;

    protected static ?string $label = 'Role Visit Target';
    protected static ?string $navigationLabel = 'Role Visit Targets';
    protected static ?string $navigationIcon = 'heroicon-o-adjustments-horizontal';
    protected static ?string $navigationGroup = 'Admin management';
    protected static ?int $navigationSort = 7;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('role_id')
                    ->label('Role')
                    ->options(fn () => Role::query()->orderBy('name')->pluck('name', 'id')->toArray())
                    ->searchable()
                    ->preload()
                    ->required(),
                Forms\Components\Select::make('client_type_id')
                    ->label('Client Type')
                    ->options(fn () => ClientType::query()->pluck('name', 'id')->toArray())
                    ->required()
                    ->unique(
                        ignoreRecord: true,
                        modifyRuleUsing: fn (Unique $rule, Forms\Get $get) => $rule->where('role_id', $get('role_id')),
                    )
                    ->validationMessages([
                        'unique' => 'A target for this role and client type already exists.',
                    ]),
                Forms\Components\TextInput::make('daily_target')
                    ->label('Daily Visit Target')
                    ->numeric()
                    ->minValue(0)
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('role.name')
                    ->label('Role')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('clientType.name')
                    ->label('Client Type')
                    ->sortable(),
                Tables\Columns\TextColumn::make('daily_target')
                    ->label('Daily Visit Target')
                    ->numeric(decimalPlaces: 2)
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('role_id')
                    ->label('Role')
                    ->options(fn () => Role::query()->orderBy('name')->pluck('name', 'id')->toArray()),
                Tables\Filters\SelectFilter::make('client_type_id')
                    ->label('Client Type')
                    ->options(fn () => ClientType::query()->pluck('name', 'id')->toArray()),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRoleVisitTargets::route('/'),
            'create' => Pages\CreateRoleVisitTarget::route('/create'),
            'edit' => Pages\EditRoleVisitTarget::route('/{record}/edit'),
        ];
    }
}
