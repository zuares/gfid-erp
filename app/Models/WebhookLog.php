<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WebhookLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'provider',
        'event_type',
        'signature_verified',
        'payload',
        'ip_address'
    ];

    protected $casts = [
        'payload' => 'array',
        'signature_verified' => 'boolean',
    ];
}
