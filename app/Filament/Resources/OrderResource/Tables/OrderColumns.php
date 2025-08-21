<?php

namespace App\Filament\Resources\OrderResource\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;

class OrderColumns
{
    public static function getColumns(): array
    {
        return [
            TextColumn::make('user.name')
                ->label('M.Rep')
                ->hidden(auth()->user()->hasRole('medical-rep'))
                ->sortable(),
            TextColumn::make('client.name_en')
                ->label('Client')
                ->sortable(),
            TextColumn::make('total')
                ->label('Total')
                ->sortable(),
            TextColumn::make('product_list')
                ->label('Product List')
                // ->limit(length: 80)
                ->wrap()
                ->sortable(),
            IconColumn::make('approved')
                ->colors(function($record){
                    if($record->approved > 0)
                        return ['success' => $record->approved];
                    if($record->approved < 0)
                        return ['danger' => $record->approved];
                    return ['secondary'];
                })
                ->options(function($record){
                    if($record->approved > 0)
                            return ['heroicon-o-check-circle' => $record->approved];
                    if($record->approved < 0)
                        return ['heroicon-o-x-circle' =>  $record->approved];
                    return ['heroicon-o-clock'];
                }),
            TextColumn::make('approved_by')
                ->label('Approved By'),
            TextColumn::make('created_at')
                ->date('d-m-Y h:i A')
                ->tooltip(fn($record) => $record->created_at->format('d-M-Y'))
                ->label('Date')
                ->sortable()
                ->searchable(),
        ];
    }
}
