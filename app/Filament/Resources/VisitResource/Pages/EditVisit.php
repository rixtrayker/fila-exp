<?php

namespace App\Filament\Resources\VisitResource\Pages;

use App\Filament\Resources\VisitResource;
use App\Helpers\DateHelper;
use App\Models\ProductVisit;
use Filament\Pages\Actions;
use Filament\Resources\Pages\EditRecord;

class EditVisit extends EditRecord
{
    protected static string $resource = VisitResource::class;

    protected function getActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected  $isRegularVisit;
    protected static $templates = [
        1 =>'Regular',
        2 =>'GroupMeeting',
        3 =>'Conference',
        4 =>'HealthDay',
    ];

    protected function mutateFormDataBeforeFill(array $data): array
    {


        $data['template'] = $data['visit_type_id'];

        $data['temp_content'][self::$templates[$data['visit_type_id']]] = $data;

        // $data['temp_content'][] = $data['content'];
        // unset($data['content']);

        return $data;
    }

    protected function mutateFormDataBeforeSave($data): array
    {
        $templates = [
            'Regular' => 1,
            'HealthDay' => 2,
            'GroupMeeting' => 3,
            'Conference' => 4,
        ];

        $templatesRev = [
            1 => 'Regular',
            2 => 'HealthDay',
            3 => 'GroupMeeting',
            4 => 'Conference',
       ];

        foreach($data['temp_content'] as $key => $value)
        {
            $data['visit_type_id'] = $templates[$key];
            foreach($value as $key2 => $value2) {
                $data[$key2] = $value2;
            }
        }

        $this->isRegularVisit = $data['template'] == 1;

        $temp = $data['template'];
        $data = $data['temp_content'][$templatesRev[$temp]];
        $data['visit_type_id'] = $temp;

        $isRep = auth()->user()->hasRole(['medical-rep','area-manager']) ;

        if($isRep)
            $data['user_id'] = auth()->id();

        $data['status'] = 'visited';

        if($isRep && $this->record->plan_id){
            $data['visit_date'] = DateHelper::today();
        } elseif($isRep && !isset($data['visit_date']) && $data['visit_type_id'] == 1){
            $data['visit_date'] = today();
        }

        return $data;
    }

    public function afterValidate()
    {
        $data = $this->form->getRawState();
        if($this->isRegularVisit)
            $this->saveProducts($data, $this->record);
    }

    private function saveProducts($data, $visit)
    {
        if(!isset($data['products'])){
            return;
        }

        $products = $data['products'];
        $visitId = $visit->id;

        $insertData = [];
        $now = now();

        foreach($products as $product){
            $insertData[] = [
                'visit_id' => $visitId,
                'product_id' =>  $product['product_id'],
                'count' => $product['count'],
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        ProductVisit::insert($insertData);
    }
    // todo sync products
}
