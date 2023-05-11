<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FrequencyReportResource\Pages;
use App\Filament\Resources\FrequencyReportResource\RelationManagers;
use App\Models\Client;
use App\Models\FrequencyReport;
use App\Models\Visit;
use Filament\Forms;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class FrequencyReportResource extends Resource
{
    protected static ?string $model = Client::class;
    protected static ?string $navigationLabel = 'Frequency Report';
    protected static ?string $navigationGroup = 'Reports';

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    // public static $gradeAVG = [
    //     'A'=>static::getGradeAVG('A'),
    //     'B'=>static::getGradeAVG('B'),
    //     'C'=>static::getGradeAVG('C'),
    //     'N'=>static::getGradeAVG('N'),
    //     'PH'=>static::getGradeAVG('PH'),
    // ];

    public static function getGradeAVG($grade)
    {
        $count = Visit::whereHas('client',function($q) use($grade){
            $q->where('grade',$grade);
        })->count();

        return $count;
    }


    // public static function form(Form $form): Form
    // {
    //     return $form
    //         ->schema([
    //             //
    //         ]);
    // }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Name'),
                TextColumn::make('visits_count')
                    ->label('Total Visits Count'),
                TextColumn::make('missed_visits_count')
                    ->label('Missed Visits Count'),
            ])
            ->filters([
                //
            ])
            ->actions([
                // Tables\Actions\EditAction::make(),
                // Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                // Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageFrequencyReports::route('/'),
        ];
    }
    public static function canCreate(): bool
    {
        return false;
    }
}
