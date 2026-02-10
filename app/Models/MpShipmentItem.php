<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MpShipmentItem extends Model
{
    protected $table = 'mp_shipment_items';

    /**
     * Kalau tabel tidak punya created_at/updated_at, set false supaya gak error saat save().
     * Kalau tabel kamu punya timestamps, hapus baris ini.
     */
    public $timestamps = false;

    protected $fillable = [
        'mp_shipment_id',
        'sku_code',
        'sku_parent',
        'product_name',
        'variant_name',
        'qty',
        'unit_price',
        'subtotal',
        'raw_line',
    ];

    protected $casts = [
        'mp_shipment_id' => 'integer',
        'qty' => 'integer',

        // Rupiah biasanya 0 desimal. Kalau kamu butuh 2 desimal, ganti ke 'decimal:2'
        'unit_price' => 'decimal:0',
        'subtotal' => 'decimal:0',

        'raw_line' => 'array',
    ];

    /* =====================
     * RELATIONS
     * ===================== */

    public function mpShipment(): BelongsTo
    {
        return $this->belongsTo(MpShipment::class, 'mp_shipment_id');
    }

    /* =====================
     * ACCESSORS (UI friendly)
     * ===================== */

    public function getDisplayNameAttribute(): string
    {
        return trim((string) ($this->product_name ?? '')) !== ''
        ? (string) $this->product_name
        : '-';
    }

    public function getDisplayVariantAttribute(): string
    {
        $v = trim((string) ($this->variant_name ?? ''));
        return $v !== '' ? $v : '-';
    }

    public function getDisplaySkuAttribute(): string
    {
        $sku = trim((string) ($this->sku_code ?? ''));
        if ($sku !== '') {
            return $sku;
        }

        $parent = trim((string) ($this->sku_parent ?? ''));
        return $parent !== '' ? $parent : '-';
    }

    public function getLineTotalAttribute(): float
    {
        // kalau subtotal kosong, fallback qty*unit_price
        $sub = (float) ($this->subtotal ?? 0);
        if ($sub > 0) {
            return $sub;
        }

        return (float) ((int) ($this->qty ?? 0) * (float) ($this->unit_price ?? 0));
    }

    /* =====================
     * SCOPES (query helpers)
     * ===================== */

    public function scopeForShipments(Builder $q, array $shipmentIds): Builder
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $shipmentIds))));
        if (empty($ids)) {
            return $q->whereRaw('1=0');
        }

        return $q->whereIn('mp_shipment_id', $ids);
    }

    public function scopeSearch(Builder $q, string $term): Builder
    {
        $t = trim($term);
        if ($t === '') {
            return $q;
        }

        return $q->where(function (Builder $qq) use ($t) {
            $qq->where('sku_code', 'like', "%{$t}%")
                ->orWhere('sku_parent', 'like', "%{$t}%")
                ->orWhere('product_name', 'like', "%{$t}%")
                ->orWhere('variant_name', 'like', "%{$t}%");
        });
    }
}
