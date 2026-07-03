<?php

namespace App\Services;

use App\Models\Item;
use App\Models\MarketplaceOrderItem;
use App\Models\SkuMapping;

class MarketplaceIssueService
{
    // ── Status constants ─────────────────────────────────────────────────────

    public const MAPPING_SKU_EMPTY = 'marketplace_sku_empty';
    public const MAPPING_NOT_FOUND = 'mapping_not_found';
    public const MAPPING_MAPPED    = 'mapped';

    public const COST_PENDING     = null;          // belum dicek (sku kosong / not found)
    public const COST_MISSING_HPP = 'missing_hpp';
    public const COST_COMPLETE    = 'complete';

    public const PROFIT_INCOMPLETE = 'incomplete';
    public const PROFIT_COMPLETE   = 'complete';

    public const DATA_VALID      = 'valid';
    public const DATA_INCOMPLETE = 'incomplete';

    // ─────────────────────────────────────────────────────────────────────────
    // Core: build attribute array (dipakai saat create item)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Bangun array attribute mapping untuk dipakai di create() / updateOrCreate().
     * TIDAK pernah default HPP ke 0.
     */
    public function buildMappingAttributes(
        ?string $modelSku,
        ?string $itemSku,
        ?string $externalSku,
        ?string $channelCode
    ): array {
        $sku = $modelSku ?? $itemSku ?? $externalSku ?? null;

        // ── 1. SKU kosong ────────────────────────────────────────────────────
        if (empty($sku)) {
            return [
                'marketplace_sku'  => null,
                'mapping_status'   => self::MAPPING_SKU_EMPTY,
                'cost_status'      => null,
                'profit_status'    => self::PROFIT_INCOMPLETE,
                'issue_reason'     => self::MAPPING_SKU_EMPTY,
                'data_status'      => self::DATA_INCOMPLETE,
                'internal_item_id' => null,
                'hpp_snapshot'     => null,
            ];
        }

        // ── 2. Cari SKU Mapping ──────────────────────────────────────────────
        $itemId = SkuMapping::resolve($sku, $channelCode);

        if (! $itemId) {
            return [
                'marketplace_sku'  => $sku,
                'mapping_status'   => self::MAPPING_NOT_FOUND,
                'cost_status'      => null,
                'profit_status'    => self::PROFIT_INCOMPLETE,
                'issue_reason'     => self::MAPPING_NOT_FOUND,
                'data_status'      => self::DATA_INCOMPLETE,
                'internal_item_id' => null,
                'hpp_snapshot'     => null,
            ];
        }

        // ── 3. Mapping ditemukan — cek HPP ───────────────────────────────────
        $internalItem = Item::find($itemId);
        $hpp          = $internalItem ? (float) $internalItem->effective_unit_cost : 0.0;

        if ($hpp > 0) {
            return [
                'marketplace_sku'  => $sku,
                'mapping_status'   => self::MAPPING_MAPPED,
                'cost_status'      => self::COST_COMPLETE,
                'profit_status'    => self::PROFIT_COMPLETE,
                'issue_reason'     => null,
                'data_status'      => self::DATA_VALID,
                'internal_item_id' => $itemId,
                'hpp_snapshot'     => $hpp,
            ];
        }

        return [
            'marketplace_sku'  => $sku,
            'mapping_status'   => self::MAPPING_MAPPED,
            'cost_status'      => self::COST_MISSING_HPP,
            'profit_status'    => self::PROFIT_INCOMPLETE,
            'issue_reason'     => self::COST_MISSING_HPP,
            'data_status'      => self::DATA_INCOMPLETE,
            'internal_item_id' => $itemId,
            'hpp_snapshot'     => null,   // JANGAN pernah simpan 0
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Core: resolve satu item yang sudah ada di DB
    // ─────────────────────────────────────────────────────────────────────────

    public function resolveItem(MarketplaceOrderItem $item, ?string $channelCode = null): void
    {
        $sku = $item->marketplace_sku
            ?? $item->model_sku
            ?? $item->item_sku
            ?? $item->external_sku
            ?? null;

        if (empty($sku)) {
            $item->update([
                'marketplace_sku'  => null,
                'mapping_status'   => self::MAPPING_SKU_EMPTY,
                'cost_status'      => null,
                'profit_status'    => self::PROFIT_INCOMPLETE,
                'issue_reason'     => self::MAPPING_SKU_EMPTY,
                'data_status'      => self::DATA_INCOMPLETE,
                'internal_item_id' => null,
                'hpp_snapshot'     => null,
            ]);
            return;
        }

        $itemId = SkuMapping::resolve($sku, $channelCode);

        if (! $itemId) {
            $item->update([
                'marketplace_sku'  => $sku,
                'mapping_status'   => self::MAPPING_NOT_FOUND,
                'cost_status'      => null,
                'profit_status'    => self::PROFIT_INCOMPLETE,
                'issue_reason'     => self::MAPPING_NOT_FOUND,
                'data_status'      => self::DATA_INCOMPLETE,
                'internal_item_id' => null,
                'hpp_snapshot'     => null,
            ]);
            return;
        }

        $internalItem = Item::find($itemId);
        $hpp          = $internalItem ? (float) $internalItem->effective_unit_cost : 0.0;

        if ($hpp > 0) {
            $item->update([
                'marketplace_sku'  => $sku,
                'mapping_status'   => self::MAPPING_MAPPED,
                'cost_status'      => self::COST_COMPLETE,
                'profit_status'    => self::PROFIT_COMPLETE,
                'issue_reason'     => null,
                'data_status'      => self::DATA_VALID,
                'internal_item_id' => $itemId,
                'hpp_snapshot'     => $hpp,
            ]);
        } else {
            $item->update([
                'marketplace_sku'  => $sku,
                'mapping_status'   => self::MAPPING_MAPPED,
                'cost_status'      => self::COST_MISSING_HPP,
                'profit_status'    => self::PROFIT_INCOMPLETE,
                'issue_reason'     => self::COST_MISSING_HPP,
                'data_status'      => self::DATA_INCOMPLETE,
                'internal_item_id' => $itemId,
                'hpp_snapshot'     => null,
            ]);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Quick actions (dipakai dari Data Perlu Diperbaiki)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Isi marketplace_sku yang kosong, opsional terapkan ke item serupa.
     */
    public function fillSku(MarketplaceOrderItem $item, string $sku, bool $applyToSimilar = false): array
    {
        $sku = trim($sku);
        if ($sku === '') {
            throw new \InvalidArgumentException('SKU tidak boleh kosong.');
        }

        $channelCode = $item->order?->store?->channel?->code;

        $item->update(['model_sku' => $sku, 'marketplace_sku' => $sku]);
        $this->resolveItem($item->fresh(), $channelCode);

        $affected = 1;

        if ($applyToSimilar && $item->item_name) {
            MarketplaceOrderItem::where('mapping_status', self::MAPPING_SKU_EMPTY)
                ->where('item_name', $item->item_name)
                ->where('variant_name', $item->variant_name)
                ->where('id', '!=', $item->id)
                ->with(['order.store.channel'])
                ->chunkById(100, function ($chunk) use ($sku, &$affected) {
                    foreach ($chunk as $s) {
                        $s->update(['model_sku' => $sku, 'marketplace_sku' => $sku]);
                        $this->resolveItem($s->fresh(), $s->order?->store?->channel?->code);
                        $affected++;
                    }
                });
        }

        return ['affected' => $affected];
    }

    /**
     * Buat SKU Mapping baru dan re-resolve item.
     * applyToAll: re-resolve semua item dengan SKU yang sama yang mapping_not_found.
     */
    public function mapSku(MarketplaceOrderItem $item, int $internalItemId, bool $applyToAll = false): array
    {
        $sku         = $item->marketplace_sku ?? $item->model_sku ?? $item->item_sku ?? $item->external_sku;
        $channelCode = $item->order?->store?->channel?->code;

        if (empty($sku)) {
            throw new \InvalidArgumentException('Item ini tidak punya marketplace SKU — isi SKU dulu.');
        }

        if (! Item::find($internalItemId)) {
            throw new \InvalidArgumentException("Item internal #{$internalItemId} tidak ditemukan.");
        }

        // Buat / update SKU Mapping
        SkuMapping::updateOrCreate(
            ['marketplace_sku' => $sku, 'channel_code' => $channelCode],
            ['item_id' => $internalItemId]
        );

        $this->resolveItem($item->fresh(), $channelCode);

        $affected = 1;

        if ($applyToAll) {
            MarketplaceOrderItem::where('mapping_status', self::MAPPING_NOT_FOUND)
                ->where(function ($q) use ($sku) {
                    $q->where('marketplace_sku', $sku)
                      ->orWhere('model_sku', $sku)
                      ->orWhere('item_sku', $sku);
                })
                ->where('id', '!=', $item->id)
                ->with(['order.store.channel'])
                ->chunkById(100, function ($chunk) use (&$affected) {
                    foreach ($chunk as $s) {
                        $this->resolveItem($s->fresh(), $s->order?->store?->channel?->code);
                        $affected++;
                    }
                });
        }

        return ['affected' => $affected];
    }

    /**
     * Isi HPP item internal, opsional update semua order item terdampak.
     * TIDAK pernah simpan HPP = 0.
     */
    public function fillHpp(MarketplaceOrderItem $item, float $hpp, bool $updateAffected = true): array
    {
        if ($hpp <= 0) {
            throw new \InvalidArgumentException('HPP harus lebih dari 0.');
        }

        $internalItemId = $item->internal_item_id;
        if (! $internalItemId) {
            throw new \InvalidArgumentException('Item belum ter-mapping ke produk internal — mapping SKU dulu.');
        }

        $internalItem = Item::findOrFail($internalItemId);
        $internalItem->update(['base_unit_cost' => $hpp]);

        $channelCode = $item->order?->store?->channel?->code;
        $this->resolveItem($item->fresh(), $channelCode);

        $affected = 1;

        if ($updateAffected) {
            MarketplaceOrderItem::where('internal_item_id', $internalItemId)
                ->where('cost_status', self::COST_MISSING_HPP)
                ->where('id', '!=', $item->id)
                ->with(['order.store.channel'])
                ->chunkById(100, function ($chunk) use (&$affected) {
                    foreach ($chunk as $s) {
                        $this->resolveItem($s->fresh(), $s->order?->store?->channel?->code);
                        $affected++;
                    }
                });
        }

        return ['affected' => $affected];
    }

    /**
     * Hitung ulang profit — hanya berhasil jika mapping dan HPP sudah lengkap.
     */
    public function recalcProfit(MarketplaceOrderItem $item): array
    {
        if ($item->mapping_status !== self::MAPPING_MAPPED) {
            throw new \InvalidArgumentException('Item belum ter-mapping ke produk internal.');
        }
        if ($item->cost_status !== self::COST_COMPLETE) {
            throw new \InvalidArgumentException('HPP belum diisi — isi HPP dulu sebelum menghitung profit.');
        }

        $channelCode = $item->order?->store?->channel?->code;
        $this->resolveItem($item->fresh(), $channelCode);

        return ['affected' => 1];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Auto-map by item code (retroaktif — untuk item mapping_not_found)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Untuk setiap item dengan mapping_not_found, coba cocokkan marketplace_sku
     * ke items.code dengan dua strategi:
     *   1. Exact match   → K5BLK-3 === item.code K5BLK-3
     *   2. Prefix match  → K5BLK-1 → strip trailing -\d+ → K5BLK === item.code K5BLK
     *
     * Kalau ketemu unique match → buat SkuMapping + resolve item + resolve semua
     * item lain dengan SKU yang sama.
     */
    public function autoMapByCode(?int $storeId = null): array
    {
        // Kumpulkan distinct SKU yang belum mapped
        $query = MarketplaceOrderItem::query()
            ->where('mapping_status', self::MAPPING_NOT_FOUND)
            ->when($storeId, fn ($q) => $q->whereHas(
                'order', fn ($q2) => $q2->where('store_id', $storeId)
            ))
            ->with(['order.store.channel']);

        // Collect distinct (sku, channelCode) pairs to avoid processing duplicates
        $pairs   = [];
        $mapped  = 0;
        $skipped = 0;
        $errors  = 0;

        $query->chunkById(200, function ($items) use (&$pairs, &$mapped, &$skipped, &$errors) {
            foreach ($items as $item) {
                $sku = $item->marketplace_sku
                    ?? $item->model_sku
                    ?? $item->item_sku
                    ?? null;

                if (! $sku) { $skipped++; continue; }

                $channelCode = $item->order?->store?->channel?->code;
                $pairKey     = $sku . '|' . ($channelCode ?? '');

                // Skip if already created mapping for this SKU in this run
                if (isset($pairs[$pairKey])) {
                    // Still need to resolve this item against the newly created mapping
                    try {
                        $this->resolveItem($item, $channelCode);
                        $mapped++;
                    } catch (\Throwable) { $errors++; }
                    continue;
                }

                $pairs[$pairKey] = true;

                // ── Strategy 1: exact code match ──────────────────────────────
                $internalItem = Item::where('code', $sku)->first();

                // ── Strategy 2: strip trailing -\d+ suffix ────────────────────
                if (! $internalItem) {
                    $prefix = preg_replace('/-\d+$/', '', $sku);
                    if ($prefix !== $sku) {
                        $internalItem = Item::where('code', $prefix)->first();
                    }
                }

                if (! $internalItem) { $skipped++; continue; }

                try {
                    // Create mapping
                    SkuMapping::updateOrCreate(
                        ['marketplace_sku' => $sku, 'channel_code' => $channelCode],
                        ['item_id' => $internalItem->id]
                    );

                    // Resolve this item
                    $this->resolveItem($item->fresh(), $channelCode);
                    $mapped++;

                    // Resolve all other items with same SKU (same channel)
                    MarketplaceOrderItem::where('mapping_status', self::MAPPING_NOT_FOUND)
                        ->where(function ($q) use ($sku) {
                            $q->where('marketplace_sku', $sku)
                              ->orWhere('model_sku', $sku)
                              ->orWhere('item_sku', $sku);
                        })
                        ->where('id', '!=', $item->id)
                        ->with(['order.store.channel'])
                        ->chunkById(100, function ($chunk) use (&$mapped, &$errors) {
                            foreach ($chunk as $s) {
                                try {
                                    $this->resolveItem($s->fresh(), $s->order?->store?->channel?->code);
                                    $mapped++;
                                } catch (\Throwable) { $errors++; }
                            }
                        });
                } catch (\Throwable) {
                    $errors++;
                }
            }
        });

        return ['mapped' => $mapped, 'skipped' => $skipped, 'errors' => $errors];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Re-map semua item (retroaktif)
    // ─────────────────────────────────────────────────────────────────────────

    public function remapItems(?int $storeId = null): array
    {
        $query = MarketplaceOrderItem::query()
            ->with(['order.store.channel'])
            ->when($storeId, fn ($q) => $q->whereHas(
                'order', fn ($q2) => $q2->where('store_id', $storeId)
            ));

        $updated = 0;
        $errors  = 0;

        $query->chunkById(200, function ($items) use (&$updated, &$errors) {
            foreach ($items as $item) {
                try {
                    $channelCode = $item->order?->store?->channel?->code;
                    $this->resolveItem($item, $channelCode);
                    $updated++;
                } catch (\Throwable) {
                    $errors++;
                }
            }
        });

        return ['updated' => $updated, 'errors' => $errors];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Summary (untuk KPI cards)
    // ─────────────────────────────────────────────────────────────────────────

    public function summary(?int $storeId = null): array
    {
        $base = MarketplaceOrderItem::query()
            ->when($storeId, fn ($q) => $q->whereHas(
                'order', fn ($q2) => $q2->where('store_id', $storeId)
            ));

        return [
            'sku_empty'         => (clone $base)->where('mapping_status', self::MAPPING_SKU_EMPTY)->count(),
            'mapping_not_found' => (clone $base)->where('mapping_status', self::MAPPING_NOT_FOUND)->count(),
            'missing_hpp'       => (clone $base)->where('cost_status', self::COST_MISSING_HPP)->count(),
            'profit_incomplete' => (clone $base)->where('profit_status', self::PROFIT_INCOMPLETE)->count(),
            'data_incomplete'   => (clone $base)->where('data_status', self::DATA_INCOMPLETE)->count(),
            'data_valid'        => (clone $base)->where('data_status', self::DATA_VALID)->count(),
            'total_issues'      => (clone $base)->hasIssues()->count(),
        ];
    }
}
