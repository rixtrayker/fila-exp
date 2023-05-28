<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FrequencyReportResource\Pages;
use App\Filament\Resources\FrequencyReportResource\RelationManagers;
use App\Models\Client;
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
                    ->searchable()
                    ->label('Name'),
                    TextColumn::make('done_visits_count')
                        ->label('Done Visits Count'),
                    TextColumn::make('pending_visits_count')
                        ->label('Pending Visits Count'),
                    TextColumn::make('missed_visits_count')
                        ->label('Missed Visits Count'),
                    TextColumn::make('visits_count')
                        ->label('Total Visits Count'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('grade')
                    ->options(static::gradeAVG()),
                Tables\Filters\Filter::make('visit_date')
                    ->form([
                        Forms\Components\DatePicker::make('from_date'),
                        Forms\Components\DatePicker::make('to_date'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from_date'],
                                fn (Builder $query, $date): Builder => $query->whereHas('visits',function ($q) use ($date){
                                    $q->whereDate('visit_date', '>=', $date);
                                }),
                            )
                            ->when(
                                $data['to_date'],
                                fn (Builder $query, $date): Builder => $query->whereHas('visits',function ($q) use ($date){
                                    $q->whereDate('visit_date', '<=', $date);
                                }),
                            );
                    })
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

    private static function gradeAVG(): array
    {
        $result = [];
        foreach(['A','B','C','N','PH'] as $grade){
            $visits = Visit::select('status')->whereHas('client', function ($q) use ($grade)
            {
                $q->where('grade', $grade);
            })->get();

            $doneVisits = $visits->where('status','visited')->count();
            $missedVisits = $visits->where('status','cancelled')->count();

            $total = $doneVisits+$missedVisits;
            if($total)
                $result[$grade] = $grade.' - '.round($doneVisits/$total,4)*100 . ' %' ;
            else
                $result[$grade] = $grade.' - 0 %';
        }
        return $result;
    }
}
