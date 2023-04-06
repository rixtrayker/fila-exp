<?php

namespace App\Models;

use App\Models\Scopes\GetMineScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Expenses extends Model
{
    use HasFactory;

    protected $table = 'expenses';

    protected $fillable = [
        'user_id',
        'distance',
        'amount',
        'date',
        'is_trasporation',
        'comment',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public static function boot()
    {
        parent::boot();
        static::addGlobalScope(new GetMineScope);
    }
}
