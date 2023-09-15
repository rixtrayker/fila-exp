<?php

namespace App\Models;

use App\Models\Scopes\GetMineScope;
use App\Traits\CanApprove;
use App\Traits\HasEditRequest;
use App\Traits\HasRoleScopeTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClientRequest extends Model
{
    use HasFactory;
    use CanApprove;
    use HasEditRequest;
    use HasRoleScopeTrait;

    // protected static bool $shouldRegisterNavigation = false;

    protected $fillable = [
        'user_id',
        'client_id',
        'client_request_type_id',
        'request_cost',
        'expected_revenue',
        'response_date',
        'from_date',
        'to_date',
        'rx_rate',
        'ordered_before',
        'description',
    ];

    public function requestType()
    {
        return $this->belongsTo(ClientRequestType::class, 'client_request_type_id');
    }
    public function client()
    {
        return $this->belongsTo(Client::class);
    }

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
