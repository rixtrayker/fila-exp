<?php

namespace App\Filament\Resources\BundleResource\Pages;

use App\Filament\Resources\BundleResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBundle extends EditRecord
{
    protected static string $resource = BundleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Populate bundle_items with existing data
        $data['bundle_items'] = $this->record->items->map(function ($item) {
            return [
                'item_id' => $item->id,
                'quantity' => $item->pivot->quantity,
            ];
        })->toArray();

        return $data;
    }

    public function afterSave(): void
    {
        $bundleItems = $this->getBundleItems();
        $existingItemIds = $this->record->items->pluck('id')->toArray();

        // Update or insert bundle items
        foreach ($bundleItems as $item) {
            if (in_array($item['item_id'], $existingItemIds)) {
                // Update existing item
                $this->record->items()->updateExistingPivot($item['item_id'], [
                    'quantity' => $item['quantity'],
                    'updated_at' => $item['updated_at'],
                ]);
            } else {
                // Attach new item
                $this->record->items()->attach($item['item_id'], [
                    'quantity' => $item['quantity'],
                    'created_at' => $item['created_at'],
                    'updated_at' => $item['updated_at'],
                ]);
            }
        }

        // Detach items that are no longer associated
        $newItemIds = array_column($bundleItems, 'item_id');
        $itemsToDetach = array_diff($existingItemIds, $newItemIds);
        if (!empty($itemsToDetach)) {
            $this->record->items()->detach($itemsToDetach);
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
