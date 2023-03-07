<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class EditRequest extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function editable()
    {
        return $this->morphTo();
    }
    public function approve()
    {
        $this->status = 'approved';
        $this->save();
    }

    public function approveBatch()
    {
        $requests = EditRequest::where('batch',$this->batch)->get();

        foreach ($requests as $request) {
            $request->approve();
        }
    }
    protected static function booted()
    {
        static::updating(function ($editRequest) {
            $status = $editRequest->getOriginal()['status'];

            if($status == 'approved' || $status == 'refused' ){
                return false;
            }
        });

        static::updated(function ($editRequest) {
            $model = $editRequest->editable;
            if($editRequest->status == 'approved'){
                $model->{$editRequest->attribute} = $editRequest->to;
                $model->saveQuietly();
            }
        });
    }
}
