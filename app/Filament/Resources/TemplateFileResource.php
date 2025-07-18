<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TemplateFileResource\Pages;
use App\Models\TemplateFile;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;
use App\Traits\ResourceHasPermission;
use App\Filament\Forms\TemplateFileForm;

class TemplateFileResource extends Resource
{
    use ResourceHasPermission;
    protected static ?string $model = TemplateFile::class;
    protected static ?string $navigationIcon = 'heroicon-o-document-duplicate';
    protected static ?string $navigationGroup = 'Content';
    protected static ?string $modelLabel = 'Template File';
    protected static ?string $pluralModelLabel = 'Template Files';
    protected static ?string $slug = 'template-files';
    public static function shouldRegisterNavigation(): bool
    {
        return auth()->check();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema(TemplateFileForm::schema());
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\Layout\Stack::make([
                    Tables\Columns\TextColumn::make('name')
                        ->searchable()
                        ->icon(fn (TemplateFile $record) => match ($record->mime_type) {
                            'application/pdf' => 'heroicon-o-document',
                            'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'heroicon-o-document-text',
                            'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'heroicon-o-table-cells',
                            default => 'heroicon-o-document',
                        })
                        ->weight('bold'),

                    Tables\Columns\TextColumn::make('size')
                        ->formatStateUsing(fn ($state) => number_format($state / 1024, 2) . ' KB')
                        ->color('gray'),
                    Tables\Columns\TextColumn::make('uploadedBy.name')
                        ->label('Uploaded by')
                        ->color('gray')
                        ->badge(),
                ]),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('d-m-Y H:i')
                    ->color('gray'),
            ])
            ->contentGrid([
                'md' => 2,
                'lg' => 3,
            ])
            ->actions([
                Tables\Actions\Action::make('download')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->url(fn (TemplateFile $record) => route('template-files.download', $record->id))
                    ->color('primary')
                    ->label('Download')
                    ->openUrlInNewTab(),
                // Tables\Actions\EditAction::make()
                //     ->visible(auth()->user()->can('view template-file')),
                Tables\Actions\DeleteAction::make()
                    ->visible(auth()->user()->can('delete template-file')),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTemplateFiles::route('/'),
            'create' => Pages\CreateTemplateFile::route('/create'),
            // 'edit' => Pages\EditTemplateFile::route('/{record}/edit'),
        ];
    }
    public static function canViewAny(): bool
    {
        return auth()->check();
    }

    public static function canCreate(): bool
    {
        return auth()->check();
    }
}
