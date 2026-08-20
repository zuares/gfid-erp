<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdsCampaignSchedule extends Model
{
    protected $table = 'marketplace_ads_campaign_schedules';

    protected $fillable = [
        'store_id',
        'channel_campaign_id',
        'action',
        'scheduled_at',
        'status',
        'executed_at',
        'error_message',
        'created_by',
        'meta',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'executed_at' => 'datetime',
        'meta' => 'array',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
