<?php

namespace App\Filament\Forms;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use App\Filament\Resources\TemplateFileResource\Pages\CreateTemplateFile;
use App\Models\Country;

class TemplateFileForm
{
    public static function schema(): array
    {
        $countries = Country::pluck('name', 'id')->toArray();
        $defaultCountry = array_key_first($countries);

        return [
            FileUpload::make('path')
                ->label('File')
                ->disk('private')
                ->directory('template-files')
                ->maxSize(10240) // 10MB
                ->acceptedFileTypes(['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])
                ->downloadable()
                ->preserveFilenames()
                ->afterStateUpdated(function ($state, $set) {
                    if ($state) {
                        $set('name', $state->getClientOriginalName());
                        $set('size', $state->getSize());
                        $set('mime_type', $state->getMimeType());
                    }
                })
                ->required(),
            TextInput::make('name')
                ->required()
                ->maxLength(255),
            Select::make('country_id')
            // ->relationship('country', 'name')
                ->options($countries)
                ->default($defaultCountry)
                ->visible(fn ($livewire) => self::isRequired($livewire))
                ->required(fn ($livewire) => self::isRequired($livewire)),
        ];
    }

    public static function isRequired($livewire): bool
    {
        return auth()->user()->hasRole('super-admin')
            && $livewire instanceof CreateTemplateFile;
    }
}