<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MpAdsRow extends Model
{
    protected $table = 'mp_ads_rows';

    protected $fillable = [
        'import_id',
        'row_no',

        'ad_name',
        'ad_status',
        'product_code',
        'bidding_mode',
        'placement',

        'search_term',
        'match_type',

        'start_at',
        'end_at',
        'end_at_raw',

        'impressions',
        'clicks',
        'ctr',

        'conversions',
        'conversions_direct',
        'cvr',
        'cvr_direct',

        'cpa',
        'cpa_direct',

        'items_sold',
        'items_sold_direct',

        'gmv',
        'gmv_direct',
        'spend',

        'roas',
        'roas_direct',

        'acos',
        'acos_direct',

        'row_fingerprint',
        'raw_json',
    ];

    protected $casts = [
        'import_id' => 'integer',
        'row_no' => 'integer',

        'start_at' => 'datetime',
        'end_at' => 'datetime',

        'impressions' => 'integer',
        'clicks' => 'integer',
        'conversions' => 'integer',
        'conversions_direct' => 'integer',
        'items_sold' => 'integer',
        'items_sold_direct' => 'integer',

        // decimals
        'ctr' => 'decimal:6',
        'cvr' => 'decimal:6',
        'cvr_direct' => 'decimal:6',

        'cpa' => 'decimal:6',
        'cpa_direct' => 'decimal:6',

        'gmv' => 'decimal:2',
        'gmv_direct' => 'decimal:2',
        'spend' => 'decimal:2',

        'roas' => 'decimal:6',
        'roas_direct' => 'decimal:6',

        'acos' => 'decimal:6',
        'acos_direct' => 'decimal:6',
    ];

    public function import(): BelongsTo
    {
        return $this->belongsTo(MpAdsImport::class, 'import_id');
    }

    /**
     * Helper: bikin fingerprint konsisten untuk anti-duplikat row di import yang sama.
     * Pakai ini di importer sebelum insert.
     */
    public static function makeFingerprint(array $row): string
    {
        $norm = function ($v) {
            $v = (string) ($v ?? '');
            $v = trim(mb_strtolower($v));
            $v = preg_replace('/\s+/', ' ', $v);
            return $v;
        };

        $key = implode('|', [
            $norm($row['ad_name'] ?? null),
            $norm($row['product_code'] ?? null),
            $norm($row['placement'] ?? null),
            $norm($row['search_term'] ?? null),
            $norm($row['match_type'] ?? null),
        ]);

        // sha1 cukup (40 chars), kolom kita 64 jadi aman
        return sha1($key);
    }
}
