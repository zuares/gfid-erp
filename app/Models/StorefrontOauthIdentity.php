<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StorefrontOauthIdentity extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'provider',
        'provider_user_id',
        'email',
        'name',
        'avatar_url',
        'profile_json',
        'last_login_at',
    ];

    protected $casts = [
        'profile_json' => 'array',
        'last_login_at' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(StorefrontCustomer::class, 'customer_id');
    }
}
