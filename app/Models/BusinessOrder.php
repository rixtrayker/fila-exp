<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\CanImport;

class BusinessOrder extends Model
{
    use HasFactory;
    use CanImport;
    protected $table = 'business_orders';
    protected $guarded = [];

    public function companyBranch()
    {
        return $this->belongsTo(CompanyBranch::class);
    }
    public static function getImportColumns(): array
    {
        return [
            'company_id',
            'company_branch_id',
            'product_id',
            'date',
            'quantity',
        ];
    }

    protected static function importRequired(): array
    {
        return [
            'date',
            'quantity',
            'product_id',
        ];
    }
    protected static function importCasts($column): string
    {
        $casts = [
            'date' => 'date',
        ];

        return isset($casts[$column]) ? $casts[$column] : '';
    }
}
