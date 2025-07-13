<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Scopes\GetMineScope;

class OfficeWork extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'time_from',
        'time_to',
        'shift',
        'status',
        'user_id',
    ];


    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function approve()
    {
        $this->status = 'approved';
        $this->save();
    }

    public function reject()
    {
        $this->status = 'rejected';
        $this->save();
    }

    public static function boot()
    {
        parent::boot();
        static::addGlobalScope(new GetMineScope);
    }
}
