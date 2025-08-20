<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VisitResource\Pages;
use App\Filament\Resources\VisitResource\Forms\VisitForm;
use App\Filament\Resources\VisitResource\Tables\VisitTable;
use App\Models\Visit;
use App\Traits\ResourceHasPermission;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class VisitResource extends Resource
{
    use ResourceHasPermission;

    protected static ?string $model = Visit::class;
    protected static $doubleCallTypeId;

    protected static ?string $navigationIcon = 'heroicon-o-building-office';

    protected static ?string $navigationLabel = 'Visits';
    protected static ?string $navigationGroup = 'Visits';
    protected static ?int $navigationSort = 1;


    public static function form(Form $form): Form
    {
        $isMedicalRep = auth()->user()?->hasRole('medical-rep');
        $isDistrictManager = auth()->user()?->hasRole('district-manager');

        return $form->schema(VisitForm::schema())->columns(3);
    }

    public static function table(Table $table): Table
    {
        return VisitTable::table($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->orderBy('visit_date','desc');

        $isBreakdown = request()->get('breakdown') === 'true';
        $clientId = request()->get('client_id');

        if ($isBreakdown) {
            // In breakdown mode: remove most scopes except MineScope, allow all statuses
            $query->withoutGlobalScopes([
                SoftDeletingScope::class,
                // Remove other scopes but keep GetMineScope for security
            ]);
            // Don't apply the visited() scope in breakdown mode to show all statuses

            // Apply Medical Rep filtering coming from table filters (Coverage/Frequency breakdown links)
            $tableFilters = request()->get('tableFilters', []);
            $userIds = (array) data_get($tableFilters, 'id.user_id', []);
            $secondUserIds = (array) data_get($tableFilters, 'id.second_user_id', []);

            // Also support direct user_id param (e.g., from alternate links)
            $directUserId = request()->get('user_id');
            if (!empty($directUserId)) {
                $directIds = (array) $directUserId;
                $userIds = array_merge($userIds, $directIds);
            }

            if (!empty($userIds) || !empty($secondUserIds)) {
                $query->where(function (Builder $subQuery) use ($userIds, $secondUserIds) {
                    if (!empty($userIds)) {
                        $subQuery->whereIn('user_id', $userIds);
                    }
                    if (!empty($secondUserIds)) {
                        if (!empty($userIds)) {
                            $subQuery->orWhereIn('second_user_id', $secondUserIds);
                        } else {
                            $subQuery->whereIn('second_user_id', $secondUserIds);
                        }
                    }
                });
            }

            // Add client filter for frequency report breakdown
            if ($clientId) {
                $query->where('client_id', $clientId);
            }
        } else {
            // Normal mode: apply visited scope and remove soft delete scope
            $query->visited()
                ->withoutGlobalScopes([
                    SoftDeletingScope::class,
                ]);
        }

        return $query;
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
