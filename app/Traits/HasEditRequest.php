<?php

namespace App\Traits;

use App\Models\EditRequest;
use Carbon\Carbon;

trait HasEditRequest{

    public function myEditRequests(){
        return $this->hasMany(EditRequest::class, 'added_by_id');
    }

    public function editRequests()
    {
        return $this->morphMany(EditRequest::class, 'editable');
    }

    public static function bootHasEditRequest(){
        static::updating(function ($model)
        {
            $user = auth()->user();

            if( !$user->hasRole('super-admin')){
                return true;
            }

            $editable = isset($model->editable) ? $model->editable : $model->fillable;

            $updatesList = [];
            $batchNumber = 'bt_'.time();
            foreach($model->getDirty() as $key => $value)
            {
                if(!in_array($key,$editable)){
                    continue;
                }

                array_push($updatesList,self::prepareEditRequest('changed', $model, $batchNumber, $key));
            }

            EditRequest::insert($updatesList);

            return false;
        });
    }
    public static function prepareEditRequest($event, $model, $batchNumber ,$key = null)
    {
        $from = $to = null;

        if($key)
        {
            $actionEntity = $key;
            $from = isset($model->getOriginal()[$key]) ? $model->getOriginal()[$key] : null;
            $to = $model->$key;
        }

        $modelName = get_class($model);

        $preparedObject = [
            'attribute' => "{$actionEntity}",
            'editable_type' => $modelName,
            'editable_id' => $model->id,
            'from' => $from,
            'to' => $to,
            'batch' => $batchNumber,
            'added_by_id' => auth()->id(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];

        return $preparedObject;
    }
}
