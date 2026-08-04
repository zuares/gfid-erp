<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShopeeApiLog extends Model
{
    protected $table = 'shopee_api_logs';

    public function getConnectionName()
    {
        return config('database.shopee_api_log_connection', 'sqlite');
    }

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
