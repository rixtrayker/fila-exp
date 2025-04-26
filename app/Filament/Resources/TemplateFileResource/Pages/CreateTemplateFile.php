<?php

namespace App\Filament\Resources\TemplateFileResource\Pages;

use App\Filament\Resources\TemplateFileResource;
use Filament\Resources\Pages\CreateRecord;
use App\Models\Country;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;

class CreateTemplateFile extends CreateRecord
{
    protected static string $resource = TemplateFileResource::class;

    private static string $pathPrefix = 'template-files';

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // The file is already stored by Filament's FileUpload component
        // We just need to set the additional data
        $data['uploaded_by'] = auth()->user()->id;

        // Set country_id based on user's country or default
        $countryId = Country::first()->id;
        if (auth()->user()->country_id) {
            $countryId = auth()->user()->country_id;
        }
        $data['country_id'] = $countryId;

        return $data;
    }
}
