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
        'is_active',
        'credentials',
        'token_expires_at',
        'last_synced_at',
        'meta',
    ];

    protected $casts = [
        'is_active' => 'boolean',
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

    /**
     * True only for credentials obtained through the official OAuth flow for
     * the Ads Dashboard read-only integration. Raw token input is deliberately
     * not supported by this integration.
     */
    public function hasReadOnlyAdsIntegration(): bool
    {
        try {
            return data_get($this->meta ?? [], 'api_access_mode') === 'read_only'
                && ! $this->readOnlyAdsIntegrationRevoked()
                && filled($this->credential('access_token'));
        } catch (\Illuminate\Contracts\Encryption\DecryptException) {
            return false;
        }
    }

    public function readOnlyAdsIntegrationRevoked(): bool
    {
        try {
            return filled(data_get($this->meta ?? [], 'api_revoked_at'));
        } catch (\Illuminate\Contracts\Encryption\DecryptException) {
            return true;
        }
    }

    public function readOnlyAdsIntegrationStatus(): string
    {
        try {
            if ($this->readOnlyAdsIntegrationRevoked()) {
                return 'revoked';
            }

            if (data_get($this->meta ?? [], 'api_access_mode') !== 'read_only') {
                return 'not_connected';
            }

            if (! filled($this->credential('access_token'))) {
                return 'not_connected';
            }

            if ($this->token_expires_at && $this->token_expires_at->isPast()) {
                return 'expired';
            }

            return 'connected';
        } catch (\Illuminate\Contracts\Encryption\DecryptException) {
            return 'revoked';
        }
    }

    public function readOnlyAdsIntegrationScopes(): array
    {
        try {
            return array_values(array_filter((array) data_get(
                $this->meta ?? [],
                'api_scopes',
                config('marketplace.read_only_api_scopes', ['ads.read', 'shop.read'])
            )));
        } catch (\Illuminate\Contracts\Encryption\DecryptException) {
            return [];
        }
    }

    public function getConnectionStatusAttribute(): string
    {
        try {
            $accessToken = $this->credential('access_token');
            if (empty($accessToken)) {
                return 'NOT_CONNECTED';
            }
        } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
            return 'INVALID_APP_KEY';
        }

        if ($this->token_expires_at && $this->token_expires_at->isPast()) {
            return 'TOKEN_EXPIRED';
        }

        return 'CONNECTED';
    }
}
