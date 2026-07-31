<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShopeeApiLog extends Model
{
    protected $table = 'shopee_api_logs';

    protected $fillable = [
        'method',
        'endpoint',
        'request_payload',
        'response_payload',
        'status_code',
        'duration',
    ];

    protected $casts = [
        'request_payload' => 'array',
        'response_payload' => 'array',
        'status_code' => 'integer',
        'duration' => 'float',
    ];
}
