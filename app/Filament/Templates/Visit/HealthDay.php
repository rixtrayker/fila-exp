<?php

namespace App\Filament\Templates\Visit;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;

final class HealthDay
{
    public static function title()
    {
        return 'Health Day';
    }

    public static function schema()
    {
        return [
            Fieldset::make('Health Day Fields')
            ->schema([
                DatePicker::make('visit_date')
                    ->label('Health Day Date')
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
