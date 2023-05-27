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
    protected $isRegularVisit;
    protected function mutateFormDataBeforeCreate($data): array
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
            foreach($value as $key2 => $value2) {
                $data[$key2] = $value2;
            }
        }

        $this->isRegularVisit = $data['template'] == 1;

        $temp = $data['template'];
        $data = $data['temp_content'][$templatesRev[$temp]];
        $data['visit_type_id'] = $temp;

        if(auth()->user()->hasRole('medical-rep') )
            $data['user_id'] = auth()->id();
        $data['status'] = 'visited';


        if(auth()->user()->hasRole('medical-rep') &&  $data['visit_type_id'] == 1){
            $data['visit_date'] = today();
        }

        return $data;
    }
    public function afterCreate()
    {
        if($this->isRegularVisit)
            $this->saveProducts($this->record);
    }

    public function create(bool $another = false): void
    {
        $this->authorizeAccess();

        try {
            $this->callHook('beforeValidate');

            $data = $this->form->getState();

            $this->callHook('afterValidate');

            $data = $this->mutateFormDataBeforeCreate($data);

            $visit = null;

            if(isset($data['client_id'])){
                $visit = Visit::withTrashed()
                    ->where('user_id',$data['user_id'])
                    ->where('client_id',$data['client_id'])
                    ->where('visit_date',$data['visit_date'])
                    ->first();
            }

            if($visit){
                $this->record = $visit;

                $visit->second_user_id = $data['second_user_id'];
                $visit->visit_type_id = $data['visit_type_id'];
                $visit->call_type_id = $data['call_type_id'];
                $visit->next_visit = $data['next_visit'];
                $visit->comment = $data['comment'];
                $visit->save();

                if($visit->status == 'visited'){
                    if($visit->deleted_at)
                        $visit->restore();
                    $this->getCreatedNotification()?->send();
                    $this->redirect($this->getRedirectUrl());
                    return;
                }

                $visit->status = 'visited';
                $visit->save();
                $data = $this->form->getRawState();
                $this->saveProducts($data, $visit);
                $this->getCreatedNotification()?->send();
                $this->redirect($this->getRedirectUrl());
                return;
            }

            $this->callHook('beforeCreate');
            $data['status'] = 'visited';
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

    private function saveProducts($visit)
    {
        $data = $this->form->getRawState()['temp_content']['Regular'];

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
}
