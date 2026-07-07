<?php

namespace App\Filament\Resources;

use App\Exports\ClientListExport;
use App\Filament\Resources\MyClientListResource\Pages;
use App\Models\Client;
use App\Models\Scopes\GetMineScope;
use App\Models\User;
use App\Models\UserBricksView;
use App\Traits\ResourceHasPermission;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Personal client lists.
 *
 * Every employee covering a region shares the same area-derived client base
 * (Client::scopeInMyAreas). This resource lets each employee build a
 * personal, accountable list selected from that pool. Coverage reporting
 * evaluates against the personal list when one exists and falls back to the
 * area-derived pool otherwise (Client::scopeAccountablePool).
 *
 * Medical reps manage only their own list. Managers (and super admins) can
 * pick a team member (hierarchy descendants only) to view and manage that
 * member's list.
 */
class MyClientListResource extends Resource
{
    use ResourceHasPermission;

    protected static ?string $model = Client::class;
    protected static ?string $permissionName = 'my-client-list';
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';
    protected static ?string $navigationGroup = 'Admin management';
    protected static ?string $navigationLabel = 'My Client List';
    protected static ?string $pluralModelLabel = 'My Client List';
    protected static ?string $slug = 'my-client-list';
    protected static ?int $navigationSort = 2;

    protected static ?array $manageableUsers = null;

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name_en')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('name_ar')
                    ->label('Name (العربية)')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('clientType.name')
                    ->label('Type')
                    ->sortable(),
                TextColumn::make('grade')
                    ->label('Class')
                    ->badge()
                    ->sortable(),
                TextColumn::make('brick.name')
                    ->label('Brick')
                    ->sortable(),
                TextColumn::make('brick.city.governorate.name')
                    ->label('Governorate')
                    ->toggleable(),
                TextColumn::make('brick.city.governorate.region.name')
                    ->label('Region')
                    ->toggleable(),
                IconColumn::make('in_list')
                    ->label('In My List')
                    ->boolean()
                    ->getStateUsing(
                        fn (Client $record, $livewire): bool => self::isInList($record, $livewire),
                    ),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('user')
                    ->label("Team Member (View / Manage a Team Member's List)")
                    ->searchable()
                    ->placeholder('My own list')
                    ->options(fn () => self::getManageableUsers())
                    ->visible(fn () => count(self::getManageableUsers()) > 1)
                    ->query(function (Builder $query, array $data) {
                        $userId = $data['value'] ?? null;

                        if (!empty($userId) && self::canManageUser((int) $userId)) {
                            // Show the selected team member's eligible pool
                            // (their bricks) instead of the viewer's own.
                            $query->whereIn(
                                'brick_id',
                                UserBricksView::getUserBrickIds((int) $userId),
                            );
                        }

                        return $query;
                    }),
                Tables\Filters\TernaryFilter::make('list_membership')
                    ->label('List Membership')
                    ->placeholder('All eligible clients')
                    ->trueLabel('In the list')
                    ->falseLabel('Not in the list')
                    ->queries(
                        true: fn (Builder $query, $livewire) => self::applyMembershipConstraint($query, $livewire, true),
                        false: fn (Builder $query, $livewire) => self::applyMembershipConstraint($query, $livewire, false),
                        blank: fn (Builder $query) => $query,
                    ),
                Tables\Filters\SelectFilter::make('clientType')
                    ->relationship('clientType', 'name'),
                Tables\Filters\SelectFilter::make('grade')
                    ->label('Class')
                    ->options([
                        'A' => 'A',
                        'B' => 'B',
                        'C' => 'C',
                        'N' => 'N',
                        'PH' => 'PH',
                    ]),
            ])
            ->headerActions([
                Tables\Actions\Action::make('exportList')
                    ->label('Export List to Excel')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('success')
                    ->action(function ($livewire) {
                        try {
                            $userId = self::getDisplayedUserId($livewire);
                            $user = User::withoutGlobalScopes()->find($userId);

                            $query = Client::query()
                                ->whereExists(function ($sub) use ($userId) {
                                    $sub->select(DB::raw(1))
                                        ->from('client_user')
                                        ->whereColumn('client_user.client_id', 'clients.id')
                                        ->where('client_user.user_id', $userId);
                                })
                                ->with(['clientType', 'brick.city.governorate.region'])
                                ->orderBy('name_en');

                            $export = new ClientListExport($query, $user?->name);

                            return $export->download($export->getFilename());
                        } catch (\Exception $e) {
                            return redirect()
                                ->back()
                                ->with('error', 'Export failed: ' . $e->getMessage());
                        }
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('addToList')
                    ->label('Add to List')
                    ->icon('heroicon-o-plus-circle')
                    ->color('success')
                    ->visible(
                        fn (Client $record, $livewire): bool => !self::isInList($record, $livewire),
                    )
                    ->action(function (Client $record, $livewire) {
                        $userId = self::getDisplayedUserId($livewire);
                        $record->users()->syncWithoutDetaching([$userId]);

                        Notification::make()
                            ->title('Client added to the list')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\Action::make('removeFromList')
                    ->label('Remove from List')
                    ->icon('heroicon-o-minus-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Remove Client from List')
                    ->modalDescription('Are you sure you want to remove this client from the list?')
                    ->visible(
                        fn (Client $record, $livewire): bool => self::isInList($record, $livewire),
                    )
                    ->action(function (Client $record, $livewire) {
                        $userId = self::getDisplayedUserId($livewire);
                        $record->users()->detach($userId);

                        Notification::make()
                            ->title('Client removed from the list')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('addSelectedToList')
                    ->label('Add Selected to List')
                    ->icon('heroicon-o-plus-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Add Selected Clients to List')
                    ->modalDescription('This will add all selected clients to the displayed list.')
                    ->deselectRecordsAfterCompletion()
                    ->action(function ($records, $livewire) {
                        $userId = self::getDisplayedUserId($livewire);

                        foreach ($records as $record) {
                            $record->users()->syncWithoutDetaching([$userId]);
                        }

                        Notification::make()
                            ->title('Selected clients added to the list')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\BulkAction::make('removeSelectedFromList')
                    ->label('Remove Selected from List')
                    ->icon('heroicon-o-minus-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Remove Selected Clients from List')
                    ->modalDescription('This will remove all selected clients from the displayed list.')
                    ->deselectRecordsAfterCompletion()
                    ->action(function ($records, $livewire) {
                        $userId = self::getDisplayedUserId($livewire);

                        foreach ($records as $record) {
                            $record->users()->detach($userId);
                        }

                        Notification::make()
                            ->title('Selected clients removed from the list')
                            ->success()
                            ->send();
                    }),
            ])
            ->defaultPaginationPageOption(25)
            ->paginationPageOptions([25, 50, 100, 250]);
    }

    /**
     * The eligible pool: clients in the viewer's areas. When a manager picks
     * a team member in the "user" filter, the pool is narrowed to that
     * member's bricks (see the filter query above).
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with([
                'clientType',
                'brick',
                'users' => fn ($query) => $query->select('users.id'),
            ])
            ->scopes(['inMyAreas']);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMyClientLists::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    /**
     * Resolve whose list is being displayed / managed: the team member
     * selected in the "user" filter (when allowed), or the viewer.
     */
    public static function getDisplayedUserId($livewire = null): int
    {
        $userId = null;

        if ($livewire && method_exists($livewire, 'getTableFilterState')) {
            $userId = $livewire->getTableFilterState('user')['value'] ?? null;
        }

        if (empty($userId)) {
            $userId = request()->input('tableFilters.user.value');
        }

        if (!empty($userId) && self::canManageUser((int) $userId)) {
            return (int) $userId;
        }

        return (int) auth()->id();
    }

    public static function isInList(Client $record, $livewire = null): bool
    {
        return $record->users->contains('id', self::getDisplayedUserId($livewire));
    }

    /**
     * Users whose lists the viewer may see and manage: themselves and their
     * hierarchy descendants (same visibility rule as GetMineScope).
     */
    public static function getManageableUsers(): array
    {
        if (self::$manageableUsers !== null) {
            return self::$manageableUsers;
        }

        return self::$manageableUsers = User::getMine()
            ->pluck('name', 'id')
            ->toArray();
    }

    public static function canManageUser(int $userId): bool
    {
        return in_array($userId, GetMineScope::getUserIds());
    }

    protected static function applyMembershipConstraint(Builder $query, $livewire, bool $inList): Builder
    {
        $userId = self::getDisplayedUserId($livewire);
        $method = $inList ? 'whereExists' : 'whereNotExists';

        return $query->{$method}(function ($sub) use ($userId) {
            $sub->select(DB::raw(1))
                ->from('client_user')
                ->whereColumn('client_user.client_id', 'clients.id')
                ->where('client_user.user_id', $userId);
        });
    }
}
