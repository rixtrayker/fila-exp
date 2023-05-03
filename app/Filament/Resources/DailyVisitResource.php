<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DailyVisitResource\Pages;
use App\Filament\Resources\DailyVisitResource\RelationManagers;
use App\Models\Visit;
use App\Models\VisitType;
use Filament\Forms;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Closure;
use Filament\Forms\Components\Select;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Collection;
use SplFileInfo;
use Str;
class DailyVisitResource extends Resource
{
    protected static ?string $model = Visit::class;
    protected static ?string $navigationLabel = 'Daily visits';
    protected static ?string $label = 'Daily visits';

    protected static ?string $navigationIcon = 'heroicon-o-collection';
    protected static ?string $slug = 'daily-visits';

    protected static $templates = [
        1 =>'Regular',
        2 =>'HealthDay',
        3 =>'GroupMeeting',
        4 =>'Conference',
    ];
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
                    ->limit(100)
                    ->wrap(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                ->color('success')
                ->visible(fn($record) => auth()->id() === $record->user_id),
                // Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                // Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

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
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->scopes([
                'pending',
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageDailyVisits::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
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
    protected static function getTemplateName($class = null){
        if(!$class){
            return 'Regular';
        }
        $array = explode('\\',$class);

        return $array[count($array)-1];
    }
}
