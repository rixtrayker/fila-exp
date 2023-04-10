<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VisitResource\Pages;
use App\Filament\Resources\VisitResource\RelationManagers;
use App\Models\Visit;
use App\Models\VisitType;
use Awcodes\FilamentTableRepeater\Components\TableRepeater;
use Closure;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Collection;
use SplFileInfo;
use Str;
class VisitResource extends Resource
{
    protected static ?string $model = Visit::class;

    protected static ?string $navigationIcon = 'heroicon-o-office-building';

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
                    ->options(VisitType::pluck('name','id')),
                ...static::getTemplateSchemas(),
            ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->sortable(),
                TextColumn::make('user.name')
                    ->label('M.Rep')
                    ->hidden(auth()->user()->hasRole('medical-rep'))
                    ->sortable(),
                TextColumn::make('client.name_en')
                    ->label('Client')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('secondRep.name')
                    ->label('M.Rep 2nd'),
                TextColumn::make('created_at')
                    ->dateTime('d-M-Y')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('comment')
                    ->wrap(),
            ])
            ->filters([
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
                        if($context === 'create')
                            return Str::contains($class,self::$templates[$get('template')]);
                        else{
                            return Str::contains($class,self::$templates[$get('template')]);
                        }
                    })
            )
            ->toArray();
    }
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
        ->withoutGlobalScopes([
            SoftDeletingScope::class,
        ])->scopes([
                'all'
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
