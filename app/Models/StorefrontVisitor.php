<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StorefrontVisitor extends Model
{
    protected $fillable = [
        'visitor_token',
        'ip_address',
        'user_agent',
        'referrer',
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'customer_name',
        'customer_phone',
        'province',
        'city',
        'first_seen_at',
        'last_seen_at',
    ];

    protected $casts = [
        'first_seen_at' => 'datetime',
        'last_seen_at'  => 'datetime',
    ];

    public function events()
    {
        return $this->hasMany(StorefrontEvent::class, 'visitor_token', 'visitor_token');
    }

    public function orders()
    {
        return $this->hasMany(StorefrontOrder::class, 'visitor_token', 'visitor_token');
    }
}
