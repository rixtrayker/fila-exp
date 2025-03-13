<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EditRequestResource\Pages;
use App\Filament\Resources\EditRequestResource\RelationManagers;
use App\Models\EditRequest;
use App\Traits\ResouerceHasPermission;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Str;
class EditRequestResource extends Resource
{
    use ResouerceHasPermission;
    protected static ?string $slug = 'edit-requests';

    protected static ?string $navigationIcon = 'heroicon-o-pencil-square';
    protected static ?int $navigationSort = 9;

    public static function form(Form $form): Form
    {
        if(self::getModelId() == 0)
            return $form->schema([]);


        $resource = 'App\Filament\Resources\\'.self::getModelName().'Resource';
        return $resource::form($form);
        // }


    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label('Editor')
                    ->searchable(),
                TextColumn::make('changedFields')
                    ->Label('Changed Fields'),
                TextColumn::make('editable_type')
                    ->label('Edited Table')
                    ->getStateUsing(fn($record)=>self::getModelName2($record->editable_type))

            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('approve')
                    ->label('Approve')
                    ->color('success')
                    ->icon('heroicon-o-check')
                    ->action(fn($record)=> $record->approveBatch()),
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEditRequests::route('/'),
            // 'create' => Pages\CreateEditRequest::route('/create'),
            'view' => Pages\ViewEditRequest::route('/{record}'),
            // 'edit' => Pages\EditEditRequest::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->scopes([
                'pending'
            ]);
    }

    public static function getModelId()
    {
        $path = explode('/',request()->path());
        return (int) $path[count($path)-1];
    }
    public static function getModelName()
    {
        $editRequest = EditRequest::find(self::getModelId()); // todo: optimize

        if(!$editRequest)
            return 'EditRequest';

        $modelPath = $editRequest->editable_type;

        $modelArray = explode('\\',$modelPath);
        $modelName = $modelArray[count($modelArray)-1];
        return $modelName;
    }

    public static function getModelName2($string){
        $modelArray = explode('\\',$string);
        $modelName = $modelArray[count($modelArray)-1];
        return $modelName;
    }

    public static function getModel(): string
    {
        return 'App\\Models\\'.self::getModelName();
    }

    public static function getNavigationLabel(): string
    {
        return 'Edit Requests';
    }
    public static function canCreate(): bool
    {
        return false;
    }
    public static function canEdit(Model $record): bool
    {
        return false;
    }
}
