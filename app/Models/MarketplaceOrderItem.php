<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketplaceOrderItem extends Model
{
    protected $fillable = [
        // Omnichannel (primary)
        'marketplace_order_id',
        'external_item_id',
        'external_model_id',
        'item_name',
        'item_sku',
        'model_sku',
        'variant_name',
        'qty',
        'price',
        'image_url',
        'raw_json',

        // Legacy — digunakan modul marketplace lama
        'order_id',
        'line_no',
        'external_sku',
        'item_id',
        'item_code_snapshot',
        'item_name_snapshot',
        'variant_snapshot',
        'price_original',
        'price_after_discount',
        'line_discount',
        'line_gross_amount',
        'line_net_amount',
        'hpp_unit_snapshot',
        'hpp_total_snapshot',

        // Mapping & Cost tracking
        'marketplace_sku',
        'mapping_status',
        'cost_status',
        'profit_status',
        'issue_reason',
        'data_status',
        'internal_item_id',
        'hpp_snapshot',
    ];

    protected $casts = [
        'price'               => 'decimal:2',
        'price_original'      => 'decimal:2',
        'price_after_discount'=> 'decimal:2',
        'line_gross_amount'   => 'decimal:2',
        'line_net_amount'     => 'decimal:2',
        'hpp_snapshot'        => 'decimal:4',
        'raw_json'            => 'array',
    ];

    // ── Relations ─────────────────────────────────────────────────────────────

    public function order(): BelongsTo
    {
        return $this->belongsTo(MarketplaceOrder::class, 'marketplace_order_id');
    }

    /**
     * Legacy order relation for rows created before marketplace_order_id was
     * introduced. Both schemas point to marketplace_orders.
     */
    public function legacyOrder(): BelongsTo
    {
        return $this->belongsTo(MarketplaceOrder::class, 'order_id');
    }

    /**
     * Item internal yang sudah di-resolve dari SKU Mapping.
     */
    public function internalItem(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'internal_item_id');
    }

    // ── Scopes ────────────────────────────────────────────────────────────────

    /**
     * Semua item yang punya masalah mapping / HPP.
     */
    public function scopeHasIssues(Builder $query): Builder
    {
        return $query->where(function (Builder $q) {
            $q->whereIn('mapping_status', [
                    'marketplace_sku_empty',
                    'mapping_not_found',
                ])
                ->orWhere('cost_status', 'missing_hpp');
        });
    }

    /** marketplace_sku kosong. */
    public function scopeSkuEmpty(Builder $query): Builder
    {
        return $query->where('mapping_status', 'marketplace_sku_empty')
            ->where(function (Builder $q) {
                $q->whereNull('marketplace_sku')->orWhere('marketplace_sku', '');
            })
            ->where(function (Builder $q) {
                $q->whereNull('model_sku')->orWhere('model_sku', '');
            })
            ->where(function (Builder $q) {
                $q->whereNull('item_sku')->orWhere('item_sku', '');
            })
            ->where(function (Builder $q) {
                $q->whereNull('external_sku')->orWhere('external_sku', '');
            });
    }

    /** SKU ada tapi belum ada mapping ke item internal. */
    public function scopeMappingNotFound(Builder $query): Builder
    {
        return $query->where(function (Builder $q) {
            $q->where('mapping_status', 'mapping_not_found')
              ->orWhere(function (Builder $q2) {
                  $q2->where('mapping_status', 'marketplace_sku_empty')
                     ->where(function (Builder $q3) {
                         $q3->where(function ($q4) { $q4->whereNotNull('marketplace_sku')->where('marketplace_sku', '!=', ''); })
                            ->orWhere(function ($q4) { $q4->whereNotNull('model_sku')->where('model_sku', '!=', ''); })
                            ->orWhere(function ($q4) { $q4->whereNotNull('item_sku')->where('item_sku', '!=', ''); })
                            ->orWhere(function ($q4) { $q4->whereNotNull('external_sku')->where('external_sku', '!=', ''); });
                     });
              });
        });
    }

    /** Mapping OK tapi HPP belum diisi. */
    public function scopeMissingHpp(Builder $query): Builder
    {
        return $query->where('cost_status', 'missing_hpp');
    }

    /** Profit belum bisa dihitung. */
    public function scopeProfitIncomplete(Builder $query): Builder
    {
        return $query->where('profit_status', 'incomplete');
    }

    /** Hanya item yang sudah fully mapped & HPP terisi (siap hitung profit). */
    public function scopeReadyForProfit(Builder $query): Builder
    {
        return $query->where('mapping_status', 'mapped')
                     ->where('cost_status', 'complete')
                     ->whereNotNull('hpp_snapshot')
                     ->where('hpp_snapshot', '>', 0);
    }

    /** Item dengan data_status = valid (mapping + HPP lengkap). */
    public function scopeDataValid(Builder $query): Builder
    {
        return $query->where('data_status', 'valid');
    }

    /** Item dengan data_status = incomplete. */
    public function scopeDataIncomplete(Builder $query): Builder
    {
        return $query->where('data_status', 'incomplete');
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /**
     * marketplace_sku yang dipakai untuk lookup di SKU Mapping.
     * Prioritas: marketplace_sku (stored) → model_sku → item_sku → external_sku.
     */
    public function getMarketplaceSkuAttribute(): ?string
    {
        return $this->attributes['marketplace_sku']
            ?? $this->model_sku
            ?? $this->item_sku
            ?? $this->external_sku
            ?? null;
    }
}
