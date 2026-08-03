<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WebhookLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'provider',
        'event_type',
        'signature_verified',
        'payload',
        'ip_address'
    ];

    protected $casts = [
        'payload' => 'array',
        'signature_verified' => 'boolean',
    ];

    public function chatMessages(): HasMany
    {
        return $this->hasMany(MarketplaceChatMessage::class, 'webhook_log_id');
    }
}
