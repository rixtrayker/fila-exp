<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VisitResource\Pages;
use App\Filament\Resources\VisitResource\RelationManagers;
use App\Models\ClientType;
use App\Models\User;
use App\Models\Visit;
use App\Models\VisitType;
use Awcodes\FilamentTableRepeater\Components\TableRepeater;
use Closure;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Collection;
use SplFileInfo;
use Str;
class VisitResource extends Resource
{
    protected static ?string $model = Visit::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office';

    // protected static bool $shouldRegisterNavigation = false;
    protected static ?int $navigationSort = 1;

    protected static $templates = [
        1 =>'Regular',
        2 =>'HealthDay',
        3 =>'GroupMeeting',
        4 =>'Conference',
    ];

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('template')
                    ->label('Visit Type')
                    ->reactive()
                    ->default(1)
                    ->columnSpan(1)
                    ->disabled(Str::contains(request()->path(),'daily-visits'))
                    ->options(VisitType::pluck('name','id')),
                ...static::getTemplateSchemas(),
            ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label('M.Rep')
                    ->hidden(auth()->user()->hasRole('medical-rep'))
                    ->sortable(),
                TextColumn::make('secondRep.name')
                    ->label('Double name'),
                TextColumn::make('client.name_en')
                    ->label('Client')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('clientType.name')
                    ->label('Client Type'),
                TextColumn::make('client.grade')
                    ->label('Client Grade'),
                TextColumn::make('visit_date')
                    ->dateTime('d-M-Y')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('clientType.name')
                    ->label('Client Type'),
                TextColumn::make('visitType.name')
                    ->label('Visit Type'),
                TextColumn::make('comment')
                    ->limit(100)
                    ->wrap(),
            ])
            ->filters([

                Tables\Filters\Filter::make('id')
                    ->form([
                        Select::make('user_id')
                            ->label('Medical Rep')
                            ->multiple()
                            ->options(User::getMine()->pluck('name','id')),
                        Select::make('second_user_id')
                            ->label('Manager')
                            ->multiple()
                            ->options(User::role('district-manager')->getMine()->pluck('name','id')),
                    ])->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['user_id'],
                                fn (Builder $query, $userIds): Builder => $query->whereIn('user_id', $userIds)
                            )
                            ->when(
                                $data['second_user_id'],
                                fn (Builder $query, $secondIds): Builder => $query->orWhereIn('second_user_id', $secondIds)
                            );
                    }),
                Tables\Filters\Filter::make('visit_date')
                    ->form([
                        Forms\Components\DatePicker::make('from_date'),
                        Forms\Components\DatePicker::make('to_date'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from_date'],
                                fn (Builder $query, $date): Builder => $query->whereDate('visit_date', '>=', $date)
                            )
                            ->when(
                                $data['to_date'],
                                fn (Builder $query, $date): Builder => $query->whereDate('visit_date', '<=', $date));
                    }),
                    SelectFilter::make('grade')
                    ->label('Grade')
                    ->multiple()
                    ->options(['A'=>'A','B'=>'B','C'=>'C','N'=>'N','PH'=>'PH'])
                    ->query(function (Builder $query, array $data): Builder {
                        if($data['values']){
                            return $query->whereHas('client',function ($q) use ($data){
                                $q->whereIn('grade', $data['values']);
                            });
                        }
                        else
                            return $query;
                        }),
                SelectFilter::make('visit_type_id')
                    ->label('Visit Type')
                    ->multiple()
                    ->options(VisitType::pluck('name','id'))
                    ->query(function (Builder $query, array $data): Builder {
                        if($data['values']){
                            return $query->whereHas('client',function ($q) use ($data){
                                $q->whereIn('visit_type_id', $data['values']);
                            });
                        }
                        else
                            return $query;
                        }),
                SelectFilter::make('client_type_id')
                    ->label('Client Type')
                    ->multiple()
                    ->options(ClientType::pluck('name','id'))
                    ->query(function (Builder $query, array $data): Builder {
                        if($data['values']){
                            return $query->whereHas('client',function ($q) use ($data){
                                $q->whereIn('client_type_id', $data['values']);
                            });
                        }
                        else
                            return $query;
                        }),
                TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\DeleteAction::make()->hidden(
                    auth()->user()->hasRole('medical-rep')
                ),
                Tables\Actions\RestoreAction::make()
                    ->hidden(fn($record) => $record->deleted_at == null)
                ])
                ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
                Tables\Actions\RestoreBulkAction::make(),
                Tables\Actions\ForceDeleteBulkAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [

        ];
    }

    public static function getTemplateClasses(): Collection
    {
        $filesystem = app(Filesystem::class);

        return collect($filesystem->allFiles(app_path('Filament/Templates/Visit')))
            ->map(function (SplFileInfo $file): string {
                return (string) Str::of('App\\Filament\\Templates\\Visit')
                    ->append('\\', $file->getRelativePathname())
                    ->replace(['/', '.php'], ['\\', '']);
            });
    }
    public static function getTemplates(): Collection
    {
        return static::getTemplateClasses()->mapWithKeys(fn ($class) => [$class => $class::title()]);
    }
    public static function getTemplateSchemas(): array
    {
        return static::getTemplateClasses()
            ->map(fn ($class) =>
                Forms\Components\Group::make($class::schema())
                    ->columnSpanFull(1)
                    ->afterStateHydrated(fn ($component, $state) => $component->getChildComponentContainer()->fill($state))
                    ->statePath('temp_content.' . static::getTemplateName($class))
                    ->visible(function ($get,$context)use($class){
                        $index = $get('template');
                        return Str::contains($class, $index ? self::$templates[$index] : 'Regular');
                    })
            )
            ->toArray();
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

    protected static function getTemplateName($class = null){
        if(!$class){
            return 'Regular';
        }
        $array = explode('\\',$class);

        return $array[count($array)-1];
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
