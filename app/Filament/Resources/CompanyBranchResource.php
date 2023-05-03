<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CompanyBranchResource\Pages;
use App\Filament\Resources\CompanyBranchResource\RelationManagers;
use App\Models\CompanyBranch;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CompanyBranchResource extends Resource
{
    protected static ?string $model = CompanyBranch::class;

    protected static ?string $navigationIcon = 'heroicon-o-collection';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->label('Name')
                    ->required(),
                TextInput::make('code')
                    ->label('Code')
                    ->required(),
                Select::make('company_id')
                    ->label('Company')
                    ->relationship('company','name')
                    ->preload()
                    ->required(),

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->label('Name'),
                TextColumn::make('code')
                    ->searchable()
                    ->label('Code'),
                TextColumn::make('company.name')
                    ->searchable()
                    ->label('Company name'),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
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
            'index' => Pages\ListCompanyBranches::route('/'),
            'create' => Pages\CreateCompanyBranch::route('/create'),
            'edit' => Pages\EditCompanyBranch::route('/{record}/edit'),
        ];
    }
}
