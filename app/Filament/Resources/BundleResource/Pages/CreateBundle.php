<?php

namespace App\Filament\Resources\BundleResource\Pages;

use App\Filament\Resources\BundleResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateBundle extends CreateRecord
{
    protected static string $resource = BundleResource::class;

    public function afterCreate(): void
    {
        $bundleItems = $this->getBundleItems();

        // Attach items to the bundle
        foreach ($bundleItems as $item) {
            $this->record->items()->attach($item['item_id'], [
                'quantity' => $item['quantity'],
                'created_at' => $item['created_at'],
                'updated_at' => $item['updated_at'],
            ]);
        }
    }

    public function getBundleItems(): array
    {
        $bundleItems = [];
        $now = now();

        foreach ($this->form->getRawState()['bundle_items'] as $item) {
            if (!isset($item['item_id']) || !$item['item_id']) {
                continue;
            }

            $bundleItems[] = [
                'item_id' => $item['item_id'],
                'quantity' => $item['quantity'] ?? 1,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        return $bundleItems;
    }
}
