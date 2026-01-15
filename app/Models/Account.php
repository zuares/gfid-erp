<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Account extends Model
{
    protected $fillable = [
        'code',
        'name',
        'type',
        'is_cash',
        'is_active',
    ];

    protected $casts = [
        'is_cash' => 'boolean',
        'is_active' => 'boolean',
    ];
}
