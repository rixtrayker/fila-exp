<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CoverageReportResource\Pages;
use App\Filament\Resources\CoverageReportResource\Tables\CoverageReportTable;
use App\Traits\ResourceHasPermission;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class CoverageReportResource extends Resource
{
    use ResourceHasPermission;

    protected static ?string $model = \App\Models\CoverageReport::class;
    protected static ?string $label = 'Coverage Report';
    protected static ?string $navigationLabel = 'Coverage Report';
    protected static ?string $navigationGroup = 'Reports';
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    protected static ?string $slug = 'coverage-report';
    protected static ?string $permissionName = 'coverage-report';

    public static function table(Table $table): Table
    {
        return CoverageReportTable::table($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCoverageReports::route('/'),
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
