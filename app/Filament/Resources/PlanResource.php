<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PlanResource\ClientManager;
use App\Filament\Resources\PlanResource\FormBuilder;
use App\Filament\Resources\PlanResource\Pages;
use App\Helpers\DateHelper;
use App\Models\Plan;
use App\Traits\ResourceHasPermission;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
class PlanResource extends Resource
{
    use ResourceHasPermission;

    protected static ?string $model = Plan::class;
    protected static ?string $navigationLabel = 'Weekly plans';
    protected static ?string $navigationIcon = 'heroicon-o-calendar';
    protected static ?string $navigationGroup = 'Visits';
    protected static ?string $slug = 'plans';
    protected static ?int $navigationSort = 2;

    /**
     * Define the form for creating/editing a Plan
     */
    public static function form(Form $form): Form
    {
        return FormBuilder::buildForm($form);
    }

    /**
     * Define the table for displaying Plans
     */
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make("user.name")->sortable()->searchable(),
                TextColumn::make("approved_by")->label("Approved By"),
                TextColumn::make("start_at")
                    ->dateTime("d-M-Y")
                    ->sortable()
                    ->searchable(),
                TextColumn::make('approved_by')
                    ->label('Approved By'),
                TextColumn::make('start_at')
                    ->dateTime('d-M-Y')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('end_date')
                    ->dateTime('d-M-Y')
                    ->sortable()
                    ->searchable(),
            ])
            ->filters([])
            ->actions([
                Actions\ViewAction::make(),
                Actions\EditAction::make(),
                Actions\Action::make('approve')
                    ->label('Approve')
                    ->color('success')
                    ->icon('heroicon-o-check')
                    ->visible(fn($record) => $record->canApprove())
                    ->action(fn($record) => $record->approvePlan()),
                Actions\Action::make('reject')
                    ->label('Reject & Delete')
                    ->color('danger')
                    ->icon('heroicon-m-x-mark')
                    ->visible(fn($record) => $record->canDecline())
                    ->requiresConfirmation()
                    ->action(fn($record) => $record->rejectPlan()),
            ])
            ->bulkActions([
                Actions\DeleteBulkAction::make(),
            ]);
    }

    /**
     * Get clients by type from the ClientManager
     */
    public static function getClients($type = null, $day = null): array
    {
        return ClientManager::getClients($type, $day);
    }

    /**
     * Get visit dates
     */
    public static function visitDates(): array
    {
        return [];
    }

    /**
     * Get the clients for a specific day in a plan
     */
    public static function getPlanDayState($record, $day)
    {
        return FormBuilder::getPlanDayState($record, $day);
    }

    /**
     * Define resource routes
     */
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPlans::route('/'),
            'create' => Pages\CreatePlan::route('/create'),
            'edit' => Pages\EditPlan::route('/{record}/edit'),
            'view' => Pages\ViewPlan::route('/{record}'),
        ];
    }

    /**
     * Check if new plans can be created
     */
    public static function canCreate(): bool
    {
        $visitDates = DateHelper::calculateVisitDates();

        return !Plan::query()
            ->where('user_id', auth()->id())
            ->where('start_at', $visitDates[0])
            ->exists();
    }

    /**
     * Check if a plan can be edited
     */
    public static function canEdit(Model $record): bool
    {
        if (!$record->user) {
            return false;
        }

        $lastPlanId = $record->user->plans()->latest()->first()?->id;
        return $record->id == $lastPlanId && !$record->approved;
    }

    /**
     * Custom query to get the latest plans first
     */
    public static function getEloquentQuery(): Builder
    {
        return static::getModel()::query()->latest();
    }

    /**
     * Helper method to determine if we're on create page
     */
    public static function isCreatePage(): bool
    {
        // Check if the current route matches the create route pattern
        $currentRoute = request()->route()->getName();
        return Str::endsWith($currentRoute, 'create');
    }

    /**
     * Get relationships for the resource
     */
    public static function getRelations(): array
    {
        return [];
    }
}
