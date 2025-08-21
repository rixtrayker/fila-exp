<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use App\Models\OrderProduct;
use App\Models\Product;
use Filament\Pages\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateOrder extends CreateRecord
{
    protected static bool $canCreateAnother = false;

    protected static string $resource = OrderResource::class;

    protected function mutateFormDataBeforeCreate($data): array
    {
        $data['user_id'] = auth()->id();
        $data['order_date'] = today();

        $formData = $this->form->getRawState();
        $subtotal = $this->calculateSubtotal($formData);
        $total = $this->calculateTotal($data, $subtotal);

        $data['sub_total'] = $subtotal;
        $data['total'] = $total;

        return $data;
    }
    public function afterCreate()
    {
        $data = $this->form->getRawState();
        $systemProducts = Product::pluck('price','id')->toArray();
        $products = $data['products'];
        $orderId = $this->record->id;

        $insertData = [];
        $now = now();
        foreach($products as $product){
            if(!$product['product_id']){
                continue;
            }
            $cost = $systemProducts[$product['product_id']];
            $insertData[] = [
                'order_id' => $orderId,
                'product_id' =>  $product['product_id'],
                'count' => $product['count'],
                'cost' => $cost,
                'item_total' => intval($cost * $product['count']),
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
    private function calculateSubtotal($data){
        $systemProducts = Product::pluck('price','id')->toArray();
        $subtotal = 0;
        $products = $data['products'];
        foreach($products as $product){
            $subtotal += $systemProducts[$product['product_id']] * $product['count'];
        }
        return $subtotal;
    }
    private function calculateTotal($data, $subtotal){
        if(!auth()->user()->hasRole('medical-rep')){
            $discount = $this->calculateDiscount($subtotal, $data['discount_type'], $data['discount']);
            $total = $subtotal - $discount;
        }else{
            $total = $subtotal;
        }
        return $total;
    }
    private function calculateDiscount($subtotal, $discountType, $discount){
        if($discountType == 'percentage'){
            $discount = $subtotal * $discount / 100;
        }
        return floatval($discount);
    }
}
