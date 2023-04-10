<?php

namespace App\Filament\Templates\Visit;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;

final class GroupMeeting
{
    public static function title()
    {
        return 'Group Meeting';
    }

    public static function schema()
    {
        return [
            Fieldset::make('Group Meeting Fields')
            ->schema([
                DatePicker::make('visit_date')
                    ->label('Meeting Date')
                    ->default(today()),
                TextInput::make('place')
                    ->label('Place'),
                TextInput::make('atendees_number')
                    ->label('Number of Atendee')
                    ->numeric()
                    ->minValue(1),
                Textarea::make('comment')
                    ->label('Comment')
                    ->columnSpan('full')
                    ->required(),
        ])
        ->columns(3)
    ];
    }
}
