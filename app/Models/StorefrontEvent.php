<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StorefrontEvent extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'visitor_token',
        'event_type',
        'payload',
        'created_at',
    ];

    protected $casts = [
        'payload'    => 'array',
        'created_at' => 'datetime',
    ];
}
