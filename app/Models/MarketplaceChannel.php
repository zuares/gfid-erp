<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MarketplaceChannel extends Model
{
    protected $fillable = [
        'code',
        'name',
        'is_active',
    ];

    public function stores()
    {
        return $this->hasMany(MarketplaceStore::class, 'channel_id');
    }
}
