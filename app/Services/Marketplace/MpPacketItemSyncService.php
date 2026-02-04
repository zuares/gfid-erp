<?php

namespace App\Services\Marketplace;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MpPacketItemSyncService
{
    /**
     * AUTO SYNC
     *
     * Rules (FINAL):
     * 1) SKU suffix "-<n>" = multiplier
     * 2) Jika base SKU ada di items.code → ERP (HARD PRIORITY)
     * 3) Jika tidak, cek mp_sku_recipes → MP SKU
     * 4) Fallback legacy
     *
     * Catatan:
     * - item_id HARUS terisi saat insert jika SKU adalah ERP item
     * - Tidak ada safety-net remap (anti silent bug)
     */
    public function syncAutoSkuMap(
        string $mpShipmentId,
        array $skuQtyMap,
        array $meta = []
    ): void {
        if (empty($skuQtyMap)) {
            return;
        }

        $channel = $this->normChannel($meta['channel'] ?? 'shopee');

        $erpSkuMap = [];
        $mpSkuMap = [];

        // cache recipe base SKU
        $recipeBaseSet = $this->loadRecipeBaseSet($channel);

        foreach ($skuQtyMap as $rawSku => $qtyInput) {
            $rawSku = $this->normSku($rawSku);
            $qtyInput = (int) $qtyInput;

            if ($rawSku === '' || $qtyInput <= 0) {
                continue;
            }

            // ALWAYS parse suffix
            [$baseSku, $mult] = $this->splitSkuSuffix($rawSku);
            $finalQty = $qtyInput * $mult;

            /**
             * 1) HARD PRIORITY: ERP ITEM
             */
            $itemId = $this->resolveItemIdBySku($baseSku);
            if ($itemId) {
                $erpSkuMap[$baseSku] = ($erpSkuMap[$baseSku] ?? 0) + $finalQty;
                continue;
            }

            /**
             * 2) MP SKU via recipe
             */
            if (isset($recipeBaseSet[$baseSku])) {
                // kirim raw sku → suffix diproses di syncMpSkuMap
                $mpSkuMap[$rawSku] = ($mpSkuMap[$rawSku] ?? 0) + $qtyInput;
                continue;
            }

            /**
             * 3) Fallback legacy
             */
            if ($this->looksLikeMpSku($rawSku)) {
                $mpSkuMap[$rawSku] = ($mpSkuMap[$rawSku] ?? 0) + $qtyInput;
            } else {
                // treat as ERP walau item belum ada (akan terlihat unmapped di UI)
                $erpSkuMap[$baseSku] = ($erpSkuMap[$baseSku] ?? 0) + $finalQty;
            }
        }

        if ($erpSkuMap) {
            $this->syncErpSkuMap($mpShipmentId, $erpSkuMap, $meta);
        }

        if ($mpSkuMap) {
            $this->syncMpSkuMap($mpShipmentId, $mpSkuMap, $meta);
        }
    }

    /**
     * ERP SKU → mp_packet_items
     * qty sudah FINAL ERP qty
     * item_id WAJIB terisi (kalau tidak, berarti item memang tidak ada)
     */
    public function syncErpSkuMap(
        string $mpShipmentId,
        array $erpSkuQtyMap,
        array $meta = []
    ): void {
        if (!$erpSkuQtyMap) {
            return;
        }

        $channel = $this->normChannel($meta['channel'] ?? 'shopee');
        $store = $meta['store'] ?? null;
        $now = now();
        $userId = Auth::id();

        $rows = [];

        foreach ($erpSkuQtyMap as $sku => $qty) {
            $sku = $this->normSku($sku);
            $qty = (int) $qty;

            if ($sku === '' || $qty <= 0) {
                continue;
            }

            // HARD lookup
            $itemId = $this->resolveItemIdBySku($sku);

            $rows[] = [
                'channel' => $channel,
                'store' => $store,
                'mp_shipment_id' => (string) $mpShipmentId,
                'sku' => $sku,
                'name' => null,
                'qty' => $qty,
                'item_id' => $itemId, // NULL = benar-benar unmapped
                'mapped_at' => $itemId ? $now : null,
                'mapped_by' => $itemId ? $userId : null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if ($rows) {
            DB::table('mp_packet_items')->upsert(
                $rows,
                ['mp_shipment_id', 'sku'],
                ['channel', 'store', 'name', 'qty', 'item_id', 'mapped_at', 'mapped_by', 'updated_at']
            );
        }
    }

    /**
     * MP SKU → mp_packet_items
     * - suffix "-n" → multiplier
     * - recipe multiplier ikut dikali
     * - disimpan sebagai base SKU
     */
    public function syncMpSkuMap(
        string $mpShipmentId,
        array $mpSkuQtyMap,
        array $meta = []
    ): void {
        if (!$mpSkuQtyMap) {
            return;
        }

        $channel = $this->normChannel($meta['channel'] ?? 'shopee');
        $store = $meta['store'] ?? null;
        $now = now();
        $userId = Auth::id();

        $recipes = DB::table('mp_sku_recipes')
            ->where('channel', $channel)
            ->get(['mp_sku_code', 'mp_sku_parent', 'item_id', 'multiplier']);

        $byCode = [];
        $byParent = [];

        foreach ($recipes as $r) {
            if ($r->mp_sku_code) {
                $byCode[$this->normSku($r->mp_sku_code)] = $r;
            }
            if ($r->mp_sku_parent) {
                $byParent[$this->normSku($r->mp_sku_parent)] = $r;
            }
        }

        $rows = [];

        foreach ($mpSkuQtyMap as $rawSku => $qtyInput) {
            $rawSku = $this->normSku($rawSku);
            $qtyInput = (int) $qtyInput;

            if ($rawSku === '' || $qtyInput <= 0) {
                continue;
            }

            [$baseSku, $packMult] = $this->splitSkuSuffix($rawSku);
            $rawQty = $qtyInput * $packMult;

            $r = $byCode[$baseSku] ?? $byParent[$baseSku] ?? null;

            $itemId = $r ? (int) $r->item_id : null;
            $mult = $r ? (int) ($r->multiplier ?? 1) : 1;

            $erpQty = $rawQty * $mult;

            $rows[] = [
                'channel' => $channel,
                'store' => $store,
                'mp_shipment_id' => (string) $mpShipmentId,
                'sku' => $baseSku,
                'name' => null,
                'qty' => $erpQty,
                'item_id' => $itemId, // boleh NULL → benar-benar butuh recipe
                'mapped_at' => $itemId ? $now : null,
                'mapped_by' => $itemId ? $userId : null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if ($rows) {
            DB::table('mp_packet_items')->upsert(
                $rows,
                ['mp_shipment_id', 'sku'],
                ['channel', 'store', 'name', 'qty', 'item_id', 'mapped_at', 'mapped_by', 'updated_at']
            );
        }
    }

    /* =====================================================
    HELPERS
    ===================================================== */

    /**
     * HARD lookup ERP item
     */
    private function resolveItemIdBySku(string $sku): ?int
    {
        return DB::table('items')
            ->where('code', $sku)
            ->value('id');
    }

    private function loadRecipeBaseSet(string $channel): array
    {
        return DB::table('mp_sku_recipes')
            ->where('channel', $channel)
            ->get(['mp_sku_code', 'mp_sku_parent'])
            ->reduce(function ($set, $r) {
                if ($r->mp_sku_code) {
                    $set[$this->normSku($r->mp_sku_code)] = true;
                }
                if ($r->mp_sku_parent) {
                    $set[$this->normSku($r->mp_sku_parent)] = true;
                }
                return $set;
            }, []);
    }

    private function looksLikeMpSku(string $sku): bool
    {
        return (bool) preg_match('/-\d+$/', $sku);
    }

    private function splitSkuSuffix(string $sku): array
    {
        if (preg_match('/^(.+?)-(\d+)$/', $sku, $m)) {
            return [$m[1], max(1, (int) $m[2])];
        }
        return [$sku, 1];
    }

    private function normChannel(?string $channel): string
    {
        $c = strtolower(trim((string) $channel));
        if ($c === '') {
            return 'shopee';
        }

        if ($c === 'ttk' || $c === 'tiktokshop') {
            return 'tiktok';
        }

        return $c;
    }

    private function normSku(?string $sku): string
    {
        if ($sku === null) {
            return '';
        }
        $sku = trim(str_replace("\xc2\xa0", ' ', $sku));
        $sku = strtoupper($sku);
        $sku = preg_replace('/\s+/', '', $sku);
        return $sku ?: '';
    }
}
