<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SOPsAndCallRateResource\Pages;
use App\Filament\Resources\SOPsAndCallRateResource\Tables\SOPsAndCallRateTable;
use App\Traits\ResourceHasPermission;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class SOPsAndCallRateResource extends Resource
{
    use ResourceHasPermission;

    protected static ?string $model = \App\Models\SOPsAndCallRate::class;
    protected static ?string $label = 'SOPs And Call Rate';
    protected static ?string $navigationLabel = 'SOPs And Call Rate';
    protected static ?string $navigationGroup = 'Reports';
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    protected static ?string $slug = 'sops-and-call-rate';
    protected static ?string $permissionName = 'sops-and-call-rate';

    public static function table(Table $table): Table
    {
        return SOPsAndCallRateTable::table($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSOPsAndCallRates::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }
}
