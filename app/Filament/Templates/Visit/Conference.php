<?php

namespace App\Filament\Templates\Visit;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;

final class Conference
{
    public static function title()
    {
        return 'Conference';
    }

    public static function schema()
    {
        return [
            Fieldset::make('Conference Fields')
            ->schema([
                DatePicker::make('visit_date')
                    ->label('Conference Date')
                    ->default(today()),
                TextInput::make('place')
                    ->label('Place'),
                Textarea::make('comment')
                    ->label('Comment')
                    ->columnSpan('full')
                    ->required(),
        ])
        ->columns(3)
    ];
    }
}
