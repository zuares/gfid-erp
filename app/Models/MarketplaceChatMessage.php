<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketplaceChatMessage extends Model
{
    protected $fillable = [
        'marketplace_conversation_id',
        'store_id',
        'external_message_id',
        'from_role',
        'from_id',
        'message_type',
        'text',
        'content',
        'sent_at',
        'is_read',
    ];

    protected $casts = [
        'content' => 'array',
        'sent_at' => 'datetime',
        'is_read' => 'boolean',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(MarketplaceConversation::class, 'marketplace_conversation_id');
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }
}
