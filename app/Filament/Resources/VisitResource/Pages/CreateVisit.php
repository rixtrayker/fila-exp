<?php

namespace App\Filament\Resources\VisitResource\Pages;

use App\Filament\Resources\VisitResource;
use App\Models\ProductVisit;
use App\Models\Visit;
use Filament\Pages\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Exceptions\Halt;

class CreateVisit extends CreateRecord
{
    protected static string $resource = VisitResource::class;

    protected function mutateFormDataBeforeCreate($data): array
    {
        $data['user_id'] = auth()->id();
        $data['visit_date'] = today();
        return $data;
    }
    public function afterCreate()
    {
        $data = $this->form->getRawState();
        $products = $data['products'];
        $visitId = $this->record->id;

        $insertData = [];

        foreach($products as $product){
            $insertData[] = [
                'visit_id' => $visitId,
                'product_id' =>  $product['product_id'],
                'count' => $product['count'],
            ];
        }

        ProductVisit::insert($insertData);
    }

    public function create(bool $another = false): void
    {
        $this->authorizeAccess();

        try {
            $this->callHook('beforeValidate');

            $data = $this->form->getState();

            $this->callHook('afterValidate');

            $data = $this->mutateFormDataBeforeCreate($data);

            $this->callHook('beforeCreate');

            $this->record = $this->handleRecordCreation($data);

            // $this->form->model($this->record)->saveRelationships();

            $this->callHook('afterCreate');
        } catch (Halt $exception) {
            return;
        }

        $this->getCreatedNotification()?->send();

        if ($another) {
            // Ensure that the form record is anonymized so that relationships aren't loaded.
            $this->form->model($this->record::class);
            $this->record = null;

            $this->fillForm();

            return;
        }

        $this->redirect($this->getRedirectUrl());
    }
}
