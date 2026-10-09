<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InstagramConnection extends Model
{
    protected $fillable = [
        'user_id',
        'instagram_user_id',
        'username',
        'access_token',
        'token_expires_at',
        'scopes',
        'status',
        'last_connected_at',
        'revoked_at',
        'metadata',
    ];

    protected $hidden = [
        'access_token',
    ];

    protected $casts = [
        'access_token' => 'encrypted',
        'token_expires_at' => 'datetime',
        'scopes' => 'array',
        'last_connected_at' => 'datetime',
        'revoked_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active' && $this->revoked_at === null;
    }
}
