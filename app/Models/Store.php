<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Store extends Model
{
    protected $fillable = [
        'code',
        'channel_id',
        'default_warehouse_id',
        'name',
        'external_shop_id',
        'region',
        'status',
        'credentials',
        'token_expires_at',
        'last_synced_at',
        'meta',
    ];

    protected $casts = [
        'credentials' => 'encrypted:array',
        'meta' => 'array',
        'token_expires_at' => 'datetime',
        'last_synced_at' => 'datetime',
    ];

    protected $hidden = ['credentials'];

    public function channel(): BelongsTo
    {
        return $this->belongsTo(Channel::class);
    }

    public function defaultWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'default_warehouse_id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(MarketplaceOrder::class);
    }

    public function credential(string $key, mixed $default = null): mixed
    {
        return data_get($this->credentials ?? [], $key, $default);
    }
}
