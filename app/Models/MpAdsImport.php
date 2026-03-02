<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MpAdsImport extends Model
{
    protected $table = 'mp_ads_imports';

    protected $fillable = [
        'channel',
        'report_type',
        'shop_platform_id',
        'shop_name',
        'period_start',
        'period_end',
        'report_generated_at',
        'file_name',
        'file_hash',
        'status',
        'created_by',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'report_generated_at' => 'datetime',
        'created_by' => 'integer',
    ];

    public function rows(): HasMany
    {
        return $this->hasMany(MpAdsRow::class, 'import_id');
    }

    /**
     * Natural key (anti duplikat dataset per periode).
     */
    public function scopeDataset($q, string $channel, ?string $shopPlatformId, string $reportType, $periodStart, $periodEnd)
    {
        return $q->where('channel', $channel)
            ->where('shop_platform_id', $shopPlatformId)
            ->where('report_type', $reportType)
            ->whereDate('period_start', $periodStart)
            ->whereDate('period_end', $periodEnd);
    }

    /**
     * Convenience untuk status check.
     */
    public function isCommitted(): bool
    {
        return ($this->status ?? null) === 'committed';
    }
}
