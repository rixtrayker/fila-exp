<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use App\Models\OrderProduct;
use App\Models\Product;
use Filament\Pages\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateOrder extends CreateRecord
{
    protected static string $resource = OrderResource::class;

    protected function mutateFormDataBeforeCreate($data): array
    {
        if(auth()->user()->hasRole('medical-rep'))
            $data['user_id'] = auth()->id();
        $data['order_date'] = today();
        return $data;
    }

    public function afterCreate()
    {
        $data = $this->form->getRawState();
        $products = $data['products'];
        $orderId = $this->record->id;

        $insertData = [];
        $now = now();
        foreach($products as $product){
            $insertData[] = [
                'order_id' => $orderId,
                'product_id' =>  $product['product_id'],
                'count' => $product['count'],
                'cost' => Product::find( $product['product_id'])->price * $product['count'],
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        OrderProduct::insert($insertData);
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

    protected function getRedirectUrl(): string
    {
        return OrderResource::getUrl('index');
    }
}
