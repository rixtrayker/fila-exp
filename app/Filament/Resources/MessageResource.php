<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MessageResource\Pages;
use App\Filament\Resources\MessageResource\RelationManagers;
use App\Models\Message;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\TernaryFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Http\UploadedFile;
use Livewire\TemporaryUploadedFile;
use Str;

class MessageResource extends Resource
{
    protected static ?string $model = Message::class;

    protected static ?string $navigationIcon = 'heroicon-o-user';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('visibleUsers')
                    ->label('Send to users')
                    ->validationAttribute('users')
                    ->relationship('visibleUsers','name')
                    ->multiple()
                    ->searchable()
                    ->placeholder('Search name')
                    // ->getSearchResultsUsing(fn (string $search) => User::where('name', 'like', "%{$search}%")->limit(50)->pluck('name', 'id'))
                    // ->getOptionLabelUsing(fn ($value): ?string => User::find($value)?->name)
                    ->preload()
                    ->requiredWithout('roles'),
                Select::make('roles')
                    ->label('Send to roles')
                    ->validationAttribute('roles')
                    ->relationship('roles','name')
                    ->multiple()
                    ->searchable()
                    ->preload()
                    ->requiredWithout('visibleUsers'),
                TextInput::make('title')
                    ->label('Title'),
                RichEditor::make('message')
                    ->label('Message')
                    ->columnSpan('full')
                    ->required(),
                FileUpload::make('files')
                    ->multiple()
                    // ->getUploadedFileNameForStorageUsing(function (TemporaryUploadedFile $file): string {
                    //     return (string) str($file->getClientOriginalExtension())->prepend('msg-'.time().Str::random(8).'.');
                    // })
                    ->directory('messages')
                    ->maxFiles(5)
                    ->enableOpen()
                    ->enableDownload()
                    ->visibility('private'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label('Title')
                    ->searchable(),
                TextColumn::make('message')
                    ->label('Message')
                    ->searchable()
                    ->wrap()
                    ->html()
                    ->extraAttributes(['class' => 'w-80']),
                TextColumn::make('sender.name')
                    ->label('Sender')
                    // ->searchable(isIndividual: true)
                    ->sortable(),
                TextColumn::make('sender_role')
                    ->toggleable(isToggledHiddenByDefault:true)
                    ->label('Role'),
                IconColumn::make('files')
                    ->getStateUsing(
                        fn($record)=>  $record->hasFiles()
                    )
                    ->falseIcon('')
                    ->trueIcon('heroicon-o-document-duplicate'),
                TextColumn::make('created_at')
                    ->dateTime('d-M-Y')
                    ->sortable()
                    ->searchable()
                    ->since(),
            ])->defaultSort('created_at','desc')
            ->filters([
                TernaryFilter::make('myMessages')
                    ->placeholder('Received')
                    ->trueLabel('Unread')
                    ->falseLabel('Sent')
                    ->queries(
                        true: fn (Builder $query) => $query->received()->unread(),
                        false: fn (Builder $query) => $query->sent(),
                        blank: fn (Builder $query) => $query->received(),
                    ),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->visible( fn($record) => now()->subMinutes(5)->isBefore($record->created_at)),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }
    protected static function getNavigationBadge(): ?string
    {
        return static::getModel()::received()->unread()->count();
    }

    protected static function getNavigationBadgeColor(): ?string
    {
        return 'primary';
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->scopes([
                // 'received',
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMessages::route('/'),
            'create' => Pages\CreateMessage::route('/create'),
            'view' => Pages\ViewMessage::route('/{record}'),
            'edit' => Pages\EditMessage::route('/{record}/edit'),
        ];
    }
}
