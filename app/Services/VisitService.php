<?php

namespace App\Services;

use App\Models\ProductVisit;
use App\Models\Visit;
use Illuminate\Support\Collection;

class VisitService
{
    public function findExistingVisit(array $data): ?Visit
    {
        if (!isset($data['client_id'])) {
            return null;
        }

        if (!isset($data['user_id']) || !isset($data['visit_date']) || !isset($data['call_type_id'])) {
            return null;
        }

        return Visit::withTrashed()
            ->where('user_id', $data['user_id'])
            ->where('client_id', $data['client_id'])
            ->where('visit_date', $data['visit_date'])
            ->where('call_type_id', $data['call_type_id'])
            ->first();
    }

    public function updateExistingVisit(Visit $visit, array $data): void
    {
        $visit->second_user_id = $data['second_user_id'];
        $visit->call_type_id = $data['call_type_id'];
        $visit->next_visit = $data['next_visit'];
        $visit->comment = $data['comment'];
        $visit->save();

        if ($visit->status === 'visited') {
            if ($visit->deleted_at) {
                $visit->restore();
            }
            return;
        }

        $visit->status = 'visited';
        $visit->save();
    }

    public function saveProducts(Visit $visit, array $data): void
    {
        if (!isset($data['products'])) {
            return;
        }

        $products = $data['products'];
        $visitId = $visit->id;
        $now = now();

        $insertData = [];

        foreach ($products as $product) {
            if (!isset($product['product_id']) || !$product['product_id']) {
                continue;
            }

            $count = $product['count'] ?? 0;

            $insertData[] = [
                'visit_id' => $visitId,
                'product_id' => $product['product_id'],
                'count' => $count,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        ProductVisit::insert($insertData);
    }
}
