<?php

namespace App\Models;

use App\Models\Scopes\GetMineScope;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class EditRequest extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status','pending');
    }
    public function editable()
    {
        return $this->morphTo();
    }
    public function user()
    {
        return $this->belongsTo(User::class,'user_id');
    }
    public function getChangedFieldsAttribute()
    {
        $attributes = EditRequest::where('batch',$this->batch)->pluck('attribute');
        $fields = '';

        foreach ($attributes as $key => $attribute) {
            $fields .= ucfirst($attribute).' ';
            if($key < count($attributes)-1){
                $fields .= ', ';
            }
        }
        return $fields;
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

    public static function boot()
    {
        parent::boot();
        static::addGlobalScope(new GetMineScope);
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
