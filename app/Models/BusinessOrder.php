<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BusinessOrder extends Model
{
    use HasFactory;

    protected $table = 'business_orders';
    protected $guarded = [];

    public function companyBranch()
    {
        return $this->belongsTo(CompanyBranch::class);
    }
}
