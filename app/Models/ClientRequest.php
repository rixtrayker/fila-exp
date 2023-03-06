<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClientRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'client_id',
        'client_request_type_id',
        'request_cost',
        'expected_revenue',
        'response_date',
        'rx_rate',
        'ordered_before',
        'description',
    ];

    public function requestType()
    {
        return $this->belongsTo(ClientRequestType::class);
    }
    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
