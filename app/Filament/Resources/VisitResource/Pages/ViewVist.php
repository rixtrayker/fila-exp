<?php

namespace App\Filament\Resources\VisitResource\Pages;

use App\Filament\Resources\VisitResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewVist extends ViewRecord
{
    protected static string $resource = VisitResource::class;

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

    // protected static function getTemplateName($class = null){
    //     if(!$class){
    //         return 'Regular';
    //     }
    //     $array = explode('\\',$class);

    //     return $array[count($array)-1];
    // }
}
