<?php

namespace App\Models;

use App\Models\Scopes\GetMineScope;
use App\Traits\CanApprove;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Expenses extends Model
{
    use HasFactory, CanApprove;

    protected $table = 'expenses';

    protected $fillable = [
        'user_id',
        'date',
        'from',
        'to',
        'description',
        'distance',
        'transportation',
        'accommodation',
        'meal',
        'telephone_postage',
        'daily_allowance',
        'medical_expenses',
        'others',
        'others_description',
        'total',
        'comment',
        'approved',
        'approved_at',
        'is_paid',
        'paid_at',
        'paid_by',
    ];

    protected $casts = [
        'date' => 'date',
        'approved_at' => 'datetime',
        'paid_at' => 'datetime',
        'is_paid' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function paidBy()
    {
        return $this->belongsTo(User::class, 'paid_by');
    }

    public function isApproved(): bool
    {
        return $this->approved > 0;
    }

    public function canBePaid(): bool
    {
        return $this->isApproved() && !$this->is_paid;
    }

    public function markAsPaid()
    {
        if ($this->canBePaid()) {
            $this->update([
                'is_paid' => true,
                'paid_at' => now(),
                'paid_by' => auth()->id(),
            ]);
        }
    }

    public static function boot()
    {
        parent::boot();
        if (!auth()->user()->hasRole('accountant')) {
            static::addGlobalScope(new GetMineScope);
        }
    }
}
