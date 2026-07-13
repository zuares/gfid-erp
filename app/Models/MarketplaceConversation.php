<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MarketplaceConversation extends Model
{
    protected $fillable = [
        'store_id',
        'conversation_id',
        'buyer_user_id',
        'buyer_username',
        'buyer_avatar',
        'last_message_type',
        'last_message_text',
        'last_message_at',
        'unread_count',
        'meta',
    ];

    protected $casts = [
        'meta'            => 'array',
        'last_message_at' => 'datetime',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(MarketplaceChatMessage::class);
    }
}
