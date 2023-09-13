<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use App\Models\Order;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewOrder extends ViewRecord
{
    protected static string $resource = OrderResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $record = Order::find($data['id']);
        $total = 0;
        $orderProducts = $record->orderProducts()->with('product')->get();

        foreach($orderProducts as $item){
            $total += $item->product?->price * $item->count;
        }

        $data['total'] = $total;
        return $data;
    }
}
