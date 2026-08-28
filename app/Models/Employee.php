<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    protected $fillable = [
        'code',
        'name',
        'role',
        'payment_type',
        'weekly_fixed_salary',
        'daily_rate',
        'default_piece_rate',
        'active',
        'phone',
        'address',
    ];

    protected $casts = [
        'active' => 'boolean',
        'weekly_fixed_salary' => 'decimal:2',
        'daily_rate' => 'decimal:2',
        'default_piece_rate' => 'decimal:2',
    ];

    public function user()
    {
        return $this->hasOne(User::class);
    }

    public function sewingPickups()
    {
        return $this->hasMany(SewingPickup::class, 'operator_id');
    }
}
