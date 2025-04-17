<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VisitResource\Pages;
use App\Filament\Resources\VisitResource\Forms\VisitForm;
use App\Filament\Resources\VisitResource\Tables\VisitTable;
use App\Models\Visit;
use App\Traits\ResouerceHasPermission;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class VisitResource extends Resource
{
    use ResouerceHasPermission;

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
        return parent::getEloquentQuery()
            ->orderBy('visit_date','desc')
            ->visited()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
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
