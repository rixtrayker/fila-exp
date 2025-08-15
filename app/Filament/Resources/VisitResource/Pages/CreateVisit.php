<?php

namespace App\Filament\Resources\VisitResource\Pages;

use App\Filament\Resources\VisitResource;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Resources\VisitResource\Pages\Traits\ServiceInitializer;
use App\Filament\Resources\VisitResource\Pages\Traits\LocationValidator;
use App\Filament\Resources\VisitResource\Pages\Traits\UserDataHandler;
use App\Filament\Resources\VisitResource\Pages\Traits\VisitHandler;
use App\Helpers\DateHelper;
use Livewire\Attributes\On;

class CreateVisit extends CreateRecord
{
    protected static bool $canCreateAnother = false;

    use ServiceInitializer, LocationValidator, UserDataHandler, VisitHandler;
    protected static string $view = 'vendor.filament.pages.create-visit';
    protected static string $resource = VisitResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['visit_date'] = DateHelper::today();
        $this->validateLocation($data);
        $this->setUserData($data);
        return $data;
    }
    public function beforeCreate(): void
    {
        $data = $this->form->getRawState();
        $visit = $this->visitService->findExistingVisit($data);

        if ($visit) {
            $this->respondToExistingVisit($visit);
        }
    }
    public function afterCreate(): void
    {
        $products = $this->getProducts();
        $existingProductIds = $this->record->products->pluck('product_id')->toArray();

        // Update or insert products
        foreach ($products as $product) {
            if (in_array($product['product_id'], $existingProductIds)) {
                // Update existing product
                $this->record->products()->updateExistingPivot($product['product_id'], [
                    'count' => $product['count'],
                    'updated_at' => $product['updated_at'],
                ]);
            } else {
                // Attach new product
                $this->record->products()->attach($product['product_id'], [
                    'count' => $product['count'],
                    'created_at' => $product['created_at'],
                    'updated_at' => $product['updated_at'],
                ]);
            }
        }

        // Detach products that are no longer associated
        $newProductIds = array_column($products, 'product_id');
        $productsToDetach = array_diff($existingProductIds, $newProductIds);
        if (!empty($productsToDetach)) {
            $this->record->products()->detach($productsToDetach);
        }
    }

    public function getProducts(): array{
        $products = [];
        $now = now();
        foreach ($this->form->getRawState()['products'] as $product) {
            $products[] = [
                'product_id' => $product['product_id'],
                'count' => $product['count'],
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        return $products;
    }

    public function afterCreateAndEdit(): void
    {
        $this->redirect(VisitResource::getUrl('index'));
    }

    #[On('location-fetched')]
    public function updateLocation($data): void
    {
        $data = collect($data);
        if ($data->has('latitude') && $data->has('longitude')) {
            $this->locationService->setLocation($this->getId(), $data);
        }
    }
}
