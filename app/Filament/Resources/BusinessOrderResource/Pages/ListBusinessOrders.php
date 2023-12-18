<?php

namespace App\Filament\Resources\BusinessOrderResource\Pages;

use App\Filament\Resources\BusinessOrderResource;
use App\Helpers\ImportHelper;
use App\Models\BusinessOrder;
use Filament\Forms\Components\FileUpload;
use Filament\Pages\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Livewire\TemporaryUploadedFile;

class ListBusinessOrders extends ListRecords
{
    protected static string $resource = BusinessOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('Import')
                ->color('success')
                ->icon('heroicon-m-cloud-arrow-up')
                ->action(function (array $data): void {
                    $importer = new ImportHelper();
                    $importer->importMultipleFiles(BusinessOrder::class ,$data['files']);
                })
                ->form([
                    FileUpload::make('files')
                        ->multiple()
                        ->directory('business-orders-sheets')
                        ->visibility('private')
                        ->acceptedFileTypes(['application/vnd.ms-excel','text/csv','application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])
                        ->getUploadedFileNameForStorageUsing(function (TemporaryUploadedFile $file): string {
                            return (string) 'bzns-'.time().'.'.$file->guessExtension();
                        }),
                ]),
        ];
    }
}
