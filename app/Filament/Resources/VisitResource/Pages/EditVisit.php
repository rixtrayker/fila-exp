<?php

namespace App\Filament\Resources\VisitResource\Pages;

use App\Filament\Resources\VisitResource;
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

        foreach($data['temp_content'] as $key => $value)
        {
            $data['visit_type_id'] = $templates[$key];
            foreach($value as $key2 => $value2) {
                $data[$key2] = $value2;
            }
        }

        unset($data['temp_content']);
        unset($data['template']);

        $data['user_id'] = auth()->id();
        $data['status'] = 'visited';


        if(auth()->user()->hasRole('medical-rep') &&  $data['visit_type_id'] == 1){
            $data['visit_date'] = today();
        }

        return $data;
    }

    // todo sync products
}
