<?php

namespace App\Services;

use App\Models\Item;
use App\Models\MarketplaceBooking;
use App\Models\MarketplaceOrder;
use App\Models\MarketplaceOrderItem;
use App\Models\ProductNameSkuMapping;
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
        ?string $channelCode,
        ?string $itemName = null,
        ?string $variantName = null
    ): array {
        $sku = $modelSku ?? $itemSku ?? $externalSku ?? null;

        // ── 0. SKU kosong → coba auto-fill dari mapping nama produk+variant ─
        if (empty($sku)) {
            $sku = $this->autoFillSkuFromNameMapping($itemName, $variantName, $channelCode);
        }

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

        // SKU kosong → coba auto-fill dari mapping nama produk+variant
        // (hasil perbaikan manual sebelumnya di Data Perlu Diperbaiki)
        if (empty($sku)) {
            $sku = $this->autoFillSkuFromNameMapping($item->item_name, $item->variant_name, $channelCode);
            if ($sku) {
                $item->update(['model_sku' => $sku, 'marketplace_sku' => $sku]);
            }
        }

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
            // Coba auto-map berdasarkan item_name & variant_name yang identik dari data masa lalu
            if (!empty($item->item_name)) {
                $previouslyMapped = MarketplaceOrderItem::where('item_name', $item->item_name)
                    ->where(function ($q) use ($item) {
                        if (empty($item->variant_name)) {
                            $q->whereNull('variant_name')->orWhere('variant_name', '');
                        } else {
                            $q->where('variant_name', $item->variant_name);
                        }
                    })
                    ->whereNotNull('internal_item_id')
                    ->where('mapping_status', self::MAPPING_MAPPED)
                    ->first();

                if ($previouslyMapped) {
                    $itemId = $previouslyMapped->internal_item_id;
                    SkuMapping::updateOrCreate(
                        ['marketplace_sku' => $sku, 'channel_code' => $channelCode],
                        ['item_id' => $itemId, 'notes' => 'Auto-mapped by system (identical product & variant name)']
                    );
                }
            }
        }

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
    // Auto-fill SKU kosong dari mapping nama produk + variant
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Cari SKU untuk produk+variant yang SKU-nya kosong.
     *   1. Cek tabel product_name_sku_mappings (hasil perbaikan manual).
     *   2. Fallback: cari order item lama dengan nama+variant identik yang
     *      sudah punya SKU & ter-mapping — lalu simpan ke tabel supaya
     *      lookup berikutnya cepat.
     */
    private function autoFillSkuFromNameMapping(
        ?string $itemName,
        ?string $variantName,
        ?string $channelCode
    ): ?string {
        if (empty(trim((string) $itemName))) {
            return null;
        }

        // 1. Tabel mapping nama → SKU
        $sku = ProductNameSkuMapping::resolveSku($itemName, $variantName, $channelCode);
        if ($sku) {
            return $sku;
        }

        // 2. Fallback: data historis yang sudah pernah diperbaiki / ter-mapping
        $previous = MarketplaceOrderItem::where('item_name', $itemName)
            ->where(function ($q) use ($variantName) {
                if (empty($variantName)) {
                    $q->whereNull('variant_name')->orWhere('variant_name', '');
                } else {
                    $q->where('variant_name', $variantName);
                }
            })
            ->whereNotNull('marketplace_sku')
            ->where('marketplace_sku', '!=', '')
            ->where('mapping_status', self::MAPPING_MAPPED)
            ->orderByDesc('id')
            ->first();

        if ($previous) {
            ProductNameSkuMapping::remember(
                $itemName,
                $variantName,
                $channelCode,
                $previous->marketplace_sku,
                'Auto-learned dari order item lama yang sudah ter-mapping'
            );

            return $previous->marketplace_sku;
        }

        return null;
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

        // Ingat mapping nama produk+variant → SKU, supaya order berikutnya
        // dengan produk & variant yang sama otomatis terisi SKU ini.
        ProductNameSkuMapping::remember(
            $item->item_name,
            $item->variant_name,
            $channelCode,
            $sku,
            'Diisi manual dari Data Perlu Diperbaiki'
        );

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

    public function syncHpp(?int $storeId = null): array
    {
        $query = MarketplaceOrderItem::query()
            ->where('mapping_status', self::MAPPING_MAPPED)
            ->when($storeId, fn ($q) => $q->whereHas(
                'order', fn ($q2) => $q2->where('store_id', $storeId)
            ))
            ->with('internalItem');

        $updated = 0;
        $errors  = 0;

        $query->chunkById(200, function ($items) use (&$updated, &$errors) {
            foreach ($items as $item) {
                try {
                    if (!$item->internalItem) continue;

                    $hpp = (float) $item->internalItem->effective_unit_cost;
                    
                    if ($hpp > 0) {
                        $item->update([
                            'hpp_snapshot' => $hpp,
                            'cost_status'  => self::COST_COMPLETE,
                            'profit_status'=> self::PROFIT_COMPLETE,
                            'data_status'  => self::DATA_VALID,
                            'issue_reason' => null
                        ]);
                    } else {
                        $item->update([
                            'cost_status'  => self::COST_MISSING_HPP,
                            'profit_status'=> self::PROFIT_INCOMPLETE,
                            'data_status'  => self::DATA_INCOMPLETE,
                            'issue_reason' => self::COST_MISSING_HPP
                        ]);
                    }
                    $updated++;
                } catch (\Throwable) {
                    $errors++;
                }
            }
        });

        return ['updated' => $updated, 'errors' => $errors];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Pesanan Kilat (booking) — item yang belum ter-mapping ikut masuk Issues
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Booking kilat yang BELUM punya order lokal (belum MATCHED / belum
     * di-backfill) — item-nya hanya ada sebagai JSON di marketplace_bookings,
     * jadi tidak lewat pipeline MarketplaceOrderItem. Method ini mengevaluasi
     * item tersebut secara live agar tetap muncul di Data Perlu Diperbaiki.
     *
     * $tab: all|sku_empty|mapping_not_found|missing_hpp
     */
    public function bookingIssueRows(?int $storeId = null, ?string $q = null, string $tab = 'all'): array
    {
        if (! \Illuminate\Support\Facades\Schema::hasTable('marketplace_bookings')) {
            return [];
        }

        $bookings = MarketplaceBooking::with('store.channel')
            ->whereNotNull('items')
            ->whereNotIn('booking_status', ['CANCELLED', 'IN_CANCEL'])
            ->when($storeId, fn ($query) => $query->where('store_id', $storeId))
            ->orderByDesc('create_time')
            ->limit(300)
            ->get();

        if ($bookings->isEmpty()) {
            return [];
        }

        // Booking yang sudah punya order lokal → item-nya sudah lewat pipeline
        // normal (muncul sebagai MarketplaceOrderItem), skip agar tidak dobel.
        $sns = $bookings->map(fn ($b) => $b->order_sn ?: $b->booking_sn)->filter()->values();
        $knownSns = MarketplaceOrder::where(function ($query) use ($sns) {
                $query->whereIn('channel_order_id', $sns)
                      ->orWhereIn('external_order_id', $sns);
            })
            ->get(['channel_order_id', 'external_order_id'])
            ->flatMap(fn ($o) => [$o->channel_order_id, $o->external_order_id])
            ->filter()->flip();

        $rows = [];

        foreach ($bookings as $booking) {
            $sn = $booking->order_sn ?: $booking->booking_sn;
            if ($sn && $knownSns->has($sn)) {
                continue;
            }

            $channelCode = $booking->store?->channel?->code;
            $items       = is_array($booking->items) ? $booking->items : [];

            foreach ($items as $idx => $item) {
                $itemName    = $item['item_name'] ?? null;
                $variantName = $item['model_name'] ?? null;

                $attrs = $this->buildMappingAttributes(
                    modelSku:    $item['model_sku'] ?? null,
                    itemSku:     $item['item_sku']  ?? null,
                    externalSku: null,
                    channelCode: $channelCode,
                    itemName:    $itemName,
                    variantName: $variantName,
                );

                // Hanya tampilkan yang bermasalah
                if (($attrs['data_status'] ?? null) !== self::DATA_INCOMPLETE) {
                    continue;
                }

                $issue = $attrs['issue_reason'];

                $match = match ($tab) {
                    'sku_empty'         => $issue === self::MAPPING_SKU_EMPTY,
                    'mapping_not_found' => $issue === self::MAPPING_NOT_FOUND,
                    'missing_hpp'       => $issue === self::COST_MISSING_HPP,
                    'all'               => true,
                    default             => false,
                };
                if (! $match) {
                    continue;
                }

                // Search filter
                if ($q) {
                    $haystack = mb_strtolower(implode(' ', array_filter([
                        $itemName, $variantName, $attrs['marketplace_sku'],
                        $booking->booking_sn, $booking->order_sn,
                    ])));
                    if (! str_contains($haystack, mb_strtolower($q))) {
                        continue;
                    }
                }

                $internalItem = $attrs['internal_item_id'] ? Item::find($attrs['internal_item_id']) : null;

                $rows[] = [
                    'id'                 => 'b' . $booking->id . '_' . $idx,
                    'is_booking'         => true,
                    'booking_id'         => $booking->id,
                    'item_index'         => $idx,
                    'order_id'           => null,
                    'order_number'       => $sn,
                    'ordered_at'         => $booking->create_time
                        ? \Carbon\Carbon::createFromTimestamp($booking->create_time)->toIso8601String()
                        : optional($booking->created_at)->toIso8601String(),
                    'store_name'         => $booking->store?->name,
                    'channel_code'       => $channelCode,
                    'item_name'          => $itemName,
                    'variant_name'       => $variantName,
                    'marketplace_sku'    => $attrs['marketplace_sku'],
                    'qty'                => (int) ($item['quantity'] ?? $item['model_quantity_purchased'] ?? 1),
                    'mapping_status'     => $attrs['mapping_status'],
                    'cost_status'        => $attrs['cost_status'],
                    'profit_status'      => $attrs['profit_status'],
                    'data_status'        => $attrs['data_status'],
                    'issue_reason'       => $issue,
                    'internal_item_id'   => $attrs['internal_item_id'],
                    'internal_item_name' => $internalItem?->name,
                    'internal_item_code' => $internalItem?->code,
                    'hpp_current'        => $internalItem ? (float) ($internalItem->base_unit_cost ?: $internalItem->hpp ?: 0) : 0,
                    'hpp_snapshot'       => $attrs['hpp_snapshot'],
                    'recommended_item'   => null,
                ];
            }
        }

        return $rows;
    }

    /** Ringkasan jumlah issue dari booking kilat (untuk digabung ke KPI). */
    public function bookingIssueSummary(?int $storeId = null): array
    {
        $counts = ['sku_empty' => 0, 'mapping_not_found' => 0, 'missing_hpp' => 0, 'total_issues' => 0];

        foreach ($this->bookingIssueRows($storeId) as $row) {
            $counts['total_issues']++;
            match ($row['issue_reason']) {
                self::MAPPING_SKU_EMPTY => $counts['sku_empty']++,
                self::MAPPING_NOT_FOUND => $counts['mapping_not_found']++,
                self::COST_MISSING_HPP  => $counts['missing_hpp']++,
                default                 => null,
            };
        }

        return $counts;
    }

    /**
     * Isi SKU untuk satu item booking kilat (item tersimpan sebagai JSON).
     * Juga menyimpan mapping nama→SKU supaya order/booking lain otomatis terisi.
     */
    public function fillBookingItemSku(MarketplaceBooking $booking, int $index, string $sku): array
    {
        $sku = trim($sku);
        if ($sku === '') {
            throw new \InvalidArgumentException('SKU tidak boleh kosong.');
        }

        $items = is_array($booking->items) ? $booking->items : [];
        if (! array_key_exists($index, $items)) {
            throw new \InvalidArgumentException('Item booking tidak ditemukan.');
        }

        $items[$index]['model_sku'] = $sku;
        $booking->update(['items' => $items]);

        $channelCode = $booking->store?->channel?->code;

        ProductNameSkuMapping::remember(
            $items[$index]['item_name'] ?? null,
            $items[$index]['model_name'] ?? null,
            $channelCode,
            $sku,
            'Diisi manual dari Data Perlu Diperbaiki (Pesanan Kilat)'
        );

        return ['affected' => 1];
    }

    /**
     * Mapping SKU item booking kilat ke item internal (buat SkuMapping).
     */
    public function mapBookingItemSku(MarketplaceBooking $booking, int $index, int $internalItemId): array
    {
        $items = is_array($booking->items) ? $booking->items : [];
        if (! array_key_exists($index, $items)) {
            throw new \InvalidArgumentException('Item booking tidak ditemukan.');
        }

        $sku = $items[$index]['model_sku'] ?? $items[$index]['item_sku'] ?? null;
        if (empty($sku)) {
            throw new \InvalidArgumentException('Item ini tidak punya marketplace SKU — isi SKU dulu.');
        }

        if (! Item::find($internalItemId)) {
            throw new \InvalidArgumentException("Item internal #{$internalItemId} tidak ditemukan.");
        }

        $channelCode = $booking->store?->channel?->code;

        SkuMapping::updateOrCreate(
            ['marketplace_sku' => $sku, 'channel_code' => $channelCode],
            ['item_id' => $internalItemId]
        );

        ProductNameSkuMapping::remember(
            $items[$index]['item_name'] ?? null,
            $items[$index]['model_name'] ?? null,
            $channelCode,
            $sku,
            'Mapping dari Data Perlu Diperbaiki (Pesanan Kilat)'
        );

        return ['affected' => 1];
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
            'sku_empty'         => (clone $base)->skuEmpty()->count(),
            'mapping_not_found' => (clone $base)->mappingNotFound()->count(),
            'missing_hpp'       => (clone $base)->missingHpp()->count(),
            'profit_incomplete' => (clone $base)->where('profit_status', self::PROFIT_INCOMPLETE)->count(),
            'data_incomplete'   => (clone $base)->where('data_status', self::DATA_INCOMPLETE)->count(),
            'data_valid'        => (clone $base)->where('data_status', self::DATA_VALID)->count(),
            'total_issues'      => (clone $base)->hasIssues()->count(),
        ];
    }
}
