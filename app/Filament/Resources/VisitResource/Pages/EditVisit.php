<?php

namespace App\Filament\Resources\VisitResource\Pages;

use App\Filament\Resources\VisitResource;
use App\Helpers\DateHelper;
use App\Models\ProductVisit;
use Filament\Pages\Actions;
use Filament\Resources\Pages\EditRecord;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Auth;

class EditVisit extends EditRecord
{
    protected static string $resource = VisitResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave($data): array
    {
        $isMedicalRep = Auth::check() && Auth::user() instanceof \App\Models\User &&
            Role::where('name', 'medical-rep')->whereHas('users', function($q) {
                $q->where('id', Auth::id());
            })->exists();

        $isAreaManager = Auth::check() && Auth::user() instanceof \App\Models\User &&
            Role::where('name', 'area-manager')->whereHas('users', function($q) {
                $q->where('id', Auth::id());
            })->exists();

        if($isMedicalRep || $isAreaManager)
            $data['user_id'] = Auth::id();

        $data['status'] = 'visited';

        if($isMedicalRep && $this->record->plan_id){
            $data['visit_date'] = DateHelper::today();
        } elseif($isMedicalRep && !isset($data['visit_date'])){
            $data['visit_date'] = today();
        }

        return $data;
    }

    public function afterValidate()
    {
        $data = $this->form->getRawState();
        $this->saveProducts($data);
    }

    private function saveProducts($data)
    {
        if(!isset($data['products'])){
            return;
        }

        $products = $data['products'];
        $visitId = $this->record->id;

        $insertData = [];
        $now = now();

        foreach($products as $product){
            $count = 0;
            if(isset($product['count']) && $product['count'])
                $count = $product['count'];
            if(!isset($product['product_id']) || !$product['product_id'])
                continue;

            $insertData[] = [
                'visit_id' => $visitId,
                'product_id' =>  $product['product_id'],
                'count' => $count,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        ProductVisit::insert($insertData);
    }
}
