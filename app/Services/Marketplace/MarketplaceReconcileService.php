<?php

namespace App\Services\Marketplace;

use App\Models\MpReconciliation;
use App\Models\MpShipment;
use App\Models\Shipment;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MarketplaceReconcileService
{
    public function reconcileByDate(
        string $dateYmd,
        ?string $channel = null,
        ?int $storeId = null,
        int $windowDays = 1,
        int $threshold = 80,
        bool $dryRun = false
    ): array {
        $date = Carbon::createFromFormat('Y-m-d', $dateYmd)->startOfDay();
        $fromDay = $date->copy()->subDays($windowDays);
        $toDay = $date->copy()->addDays($windowDays);

        $fromStart = $fromDay->copy()->startOfDay();
        $toEnd = $toDay->copy()->endOfDay();

        $hasAwbColumn = Schema::hasColumn('shipments', 'awb');
        $reconShipmentNullable = $this->isColumnNullable('mp_reconciliations', 'shipment_id');

        // Load recipe map + ERP item maps
        $recipeMap = $this->loadRecipeMap($channel);
        $itemIdToCode = $this->loadItemIdToCodeMap();

        // MpPacketItem sync service (Tahap 2)
        $packetSync = app(MpPacketItemSyncService::class);

        $mp = MpShipment::query()
            ->whereDoesntHave('reconciliation', function ($q) {
                $q->whereNotNull('matched_at');
            })
            ->when($channel, fn($q) => $q->where('channel', $channel))
            ->when($storeId, fn($q) => $q->where('store_id', $storeId))
            ->where(function ($q) use ($fromStart, $toEnd) {
                $q->whereBetween('shipped_at', [$fromStart, $toEnd])
                    ->orWhere(function ($qq) use ($fromStart, $toEnd) {
                        $qq->whereNull('shipped_at')
                            ->whereBetween('order_created_at', [$fromStart, $toEnd]);
                    });
            })
            ->with(['items', 'reconciliation'])
            ->orderByRaw('coalesce(shipped_at, order_created_at) asc')
            ->limit(5000)
            ->get();

        $stats = [
            'date' => $dateYmd,
            'window' => $fromDay->toDateString() . '..' . $toDay->toDateString(),
            'scanned' => $mp->count(),
            'matched' => 0,
            'needs_review' => 0,
            'skipped' => 0,
            'dry_run' => $dryRun,
            'awb_enabled' => $hasAwbColumn,
            'reviews_persistable' => $reconShipmentNullable,
        ];

        if ($mp->isEmpty()) {
            return ['stats' => $stats, 'matches' => [], 'reviews' => []];
        }

        $ops = Shipment::query()
            ->whereNull('cancelled_at')
            ->whereNotNull('posted_at')
            ->when($storeId, fn($q) => $q->where('store_id', $storeId))
            ->whereBetween('date', [$fromDay->toDateString(), $toDay->toDateString()])
            ->with(['lines.item'])
            ->orderBy('date')
            ->orderBy('id')
            ->get();

        if ($ops->isEmpty()) {
            $reviews = $mp->map(fn($m) => [
                'mp_shipment_id' => $m->id,
                'shipment_id' => null,
                'confidence' => 0,
                'match_key' => 'no_ops_batch',
                'reasons' => ['no_ops_batch'],
            ])->values()->all();

            $stats['needs_review'] = count($reviews);

            if ($dryRun) {
                return compact('stats', 'reviews') + ['matches' => []];
            }

            $this->persistResults(matches: [], reviews: $reviews, persistReviews: $reconShipmentNullable);

            return compact('stats', 'reviews') + ['matches' => []];
        }

        $opsByDay = $ops->groupBy(fn($s) => $s->date ? $s->date->toDateString() : null);

        $awbMap = collect();
        if ($hasAwbColumn) {
            $awbMap = $ops
                ->filter(fn($s) => !empty($s->awb))
                ->keyBy(fn($s) => (string) $s->awb);
        }

        $mpByDay = $mp->groupBy(fn($m) => $this->effectiveMpDay($m) ?? 'UNKNOWN');

        $matches = [];
        $reviews = [];

        foreach ($mpByDay as $day => $mpRows) {
            if ($day === 'UNKNOWN') {
                foreach ($mpRows as $m) {
                    $stats['skipped']++;
                }
                continue;
            }

            /** @var Collection $dayOps */
            $dayOps = $opsByDay->get($day, collect());

            if ($dayOps->isEmpty()) {
                foreach ($mpRows as $m) {
                    $reviews[] = [
                        'mp_shipment_id' => $m->id,
                        'shipment_id' => null,
                        'confidence' => 0,
                        'match_key' => 'no_ops_on_day',
                        'reasons' => ['no_ops_on_day'],
                    ];
                }
                continue;
            }

            $bins = $dayOps->map(function (Shipment $s) {
                $skuMap = $this->buildShipmentSkuMap($s);

                $cap = (int) ($s->total_qty ?? 0);
                if ($cap <= 0) {
                    $cap = array_sum($skuMap);
                }

                return [
                    'shipment' => $s,
                    'cap' => $cap,
                    'used_total' => 0,
                    'remain_sku' => $skuMap,
                ];
            })->values()->all();

            $mpSorted = $mpRows->sortByDesc(fn($m) => (int) ($m->total_qty ?? 0))->values();

            foreach ($mpSorted as $m) {

                // 3.1 AWB priority
                if ($hasAwbColumn && !empty($m->tracking_no) && $awbMap->has($m->tracking_no)) {
                    $s = $awbMap->get($m->tracking_no);

                    $matches[] = [
                        'mp_shipment_id' => $m->id,
                        'shipment_id' => $s->id,
                        'confidence' => 100,
                        'match_key' => 'tracking',
                        'reasons' => ['tracking_no'],
                    ];
                    continue;
                }

                // 3.2 Convert MP items -> ERP sku map via recipe (untuk matching)
                $mpErpSku = $this->buildMpErpSkuMap($m, $channel, $recipeMap, $itemIdToCode);

                // ===== Opsi A: Persist packet items pakai MP SKU raw, multiplier diaplikasikan di sync service =====
                if (!$dryRun) {
                    $mpSkuRaw = $this->buildMpSkuMapRaw($m);

                    $packetSync->syncMpSkuMap(
                        mpShipmentId: (string) $m->id,
                        mpSkuQtyMap: $mpSkuRaw,
                        meta: [
                            'channel' => (string) ($m->channel ?? ($channel ?? 'shopee')),
                            'store' => $m->store_id ? ('store#' . $m->store_id) : null,
                        ],
                    );
                }
                // ================================================================================================

                $mpQty = array_sum($mpErpSku);

                if ($mpQty <= 0 || empty($mpErpSku)) {
                    $reviews[] = [
                        'mp_shipment_id' => $m->id,
                        'shipment_id' => $bins[0]['shipment']->id ?? null,
                        'confidence' => 0,
                        'match_key' => 'recipe_missing',
                        'reasons' => [
                            'recipe_missing',
                            'tracking_no' => $m->tracking_no,
                            'platform_order_id' => $m->platform_order_id,
                        ],
                    ];
                    continue;
                }

                // 3.3 Find best bin by SKU overlap on REMAINING
                $bestIdx = null;
                $bestConfidence = -1;
                $bestReasons = [];

                foreach ($bins as $idx => $b) {
                    $cap = (int) $b['cap'];
                    if ($cap <= 0) {
                        continue;
                    }

                    $tolerance = max(2, (int) round($cap * 0.03));
                    if (($b['used_total'] + $mpQty) > ($cap + $tolerance)) {
                        continue;
                    }

                    [$skuScore, $overlapQty] = $this->scoreSkuOverlap($mpErpSku, $b['remain_sku']);
                    if ($skuScore <= 0) {
                        continue;
                    }

                    $remainAfter = max(0, $cap - ($b['used_total'] + $mpQty));
                    $fitPct = $cap > 0 ? (int) round(100 * (1 - ($remainAfter / $cap))) : 0;
                    $qtyScore = max(0, min(100, $fitPct));

                    $confidence = (int) round(($skuScore * 0.8) + ($qtyScore * 0.2));

                    if ($confidence > $bestConfidence) {
                        $bestConfidence = $confidence;
                        $bestIdx = $idx;
                        $bestReasons = [
                            'sku_overlap' => $skuScore,
                            'overlap_qty' => $overlapQty,
                            'mp_qty' => $mpQty,
                            'cap' => $cap,
                            'qty_fit' => $qtyScore,
                            'ship_id' => $b['shipment']->id,
                        ];
                    }
                }

                if ($bestIdx === null) {
                    $reviews[] = [
                        'mp_shipment_id' => $m->id,
                        'shipment_id' => $bins[0]['shipment']->id ?? null,
                        'confidence' => 40,
                        'match_key' => 'no_fit_bin',
                        'reasons' => ['no_fit_bin'],
                    ];
                    continue;
                }

                $chosenShipment = $bins[$bestIdx]['shipment'];
                $bins[$bestIdx]['used_total'] += $mpQty;
                $this->consumeRemainingSku($bins[$bestIdx]['remain_sku'], $mpErpSku);

                if ($bestConfidence >= $threshold) {
                    $matches[] = [
                        'mp_shipment_id' => $m->id,
                        'shipment_id' => $chosenShipment->id,
                        'confidence' => min(100, $bestConfidence),
                        'match_key' => 'sku_alloc_recipe',
                        'reasons' => $bestReasons,
                    ];
                } elseif ($bestConfidence >= 50) {
                    $reviews[] = [
                        'mp_shipment_id' => $m->id,
                        'shipment_id' => $chosenShipment->id,
                        'confidence' => min(100, $bestConfidence),
                        'match_key' => 'sku_alloc_recipe_low',
                        'reasons' => $bestReasons,
                    ];
                } else {
                    $stats['skipped']++;
                }
            }
        }

        $stats['matched'] = count($matches);
        $stats['needs_review'] = count($reviews);

        if ($dryRun) {
            return compact('stats', 'matches', 'reviews');
        }

        $this->persistResults(matches: $matches, reviews: $reviews, persistReviews: $reconShipmentNullable);

        return compact('stats', 'matches', 'reviews');
    }

    // =========================================================
    // Persist
    // =========================================================
    protected function persistResults(array $matches, array $reviews, bool $persistReviews = true): void
    {
        DB::transaction(function () use ($matches, $reviews, $persistReviews) {

            foreach ($matches as $m) {
                MpReconciliation::updateOrCreate(
                    ['mp_shipment_id' => $m['mp_shipment_id']],
                    [
                        'shipment_id' => $m['shipment_id'],
                        'status' => 'auto_matched',
                        'match_key' => $m['match_key'] ?? 'auto',
                        'match_confidence' => (int) ($m['confidence'] ?? 0),
                        'matched_at' => now(),
                        'matched_by' => null,
                        'notes' => 'auto reconcile',
                    ]
                );
            }

            if (!$persistReviews) {
                return;
            }

            foreach ($reviews as $r) {
                MpReconciliation::updateOrCreate(
                    ['mp_shipment_id' => $r['mp_shipment_id']],
                    [
                        'shipment_id' => $r['shipment_id'] ?? null,
                        'status' => 'needs_review',
                        'match_key' => $r['match_key'] ?? 'review',
                        'match_confidence' => (int) ($r['confidence'] ?? 0),
                        'matched_at' => null,
                        'matched_by' => null,
                        'notes' => 'needs review',
                    ]
                );
            }
        });
    }

    // =========================================================
    // Helpers
    // =========================================================
    private function effectiveMpDay(MpShipment $m): ?string
    {
        $dt = $m->shipped_at ?? $m->order_created_at;
        return $dt ? Carbon::parse($dt)->toDateString() : null;
    }

    private function buildShipmentSkuMap(Shipment $s): array
    {
        $out = [];
        foreach ($s->lines as $line) {
            $qty = (int) ($line->qty_scanned ?? 0);
            if ($qty <= 0) {
                continue;
            }

            $sku = $this->normSku($line->item?->code ?? null);
            if ($sku === '') {
                continue;
            }

            $out[$sku] = ($out[$sku] ?? 0) + $qty;
        }
        return $out;
    }

    /**
     * NEW: raw MP SKU map (sebelum recipe), untuk disimpan ke mp_packet_items.
     * Ini yang akan dipetakan + multiplier di MpPacketItemSyncService (Opsi A).
     */
    private function buildMpSkuMapRaw(MpShipment $m): array
    {
        $out = [];

        foreach ($m->items as $it) {
            $qty = (int) ($it->qty ?? 0);
            if ($qty <= 0) {
                continue;
            }

            // prefer sku_code (lebih spesifik)
            $code = $this->normSku($it->sku_code ?? null);
            if ($code !== '') {
                $out[$code] = ($out[$code] ?? 0) + $qty;
                continue;
            }

            // fallback parent
            $parent = $this->normSku($it->sku_parent ?? null);
            if ($parent !== '') {
                $out[$parent] = ($out[$parent] ?? 0) + $qty;
            }
        }

        return $out;
    }

    private function buildMpErpSkuMap(MpShipment $m, ?string $channel, array $recipeMap, array $itemIdToCode): array
    {
        $out = [];

        foreach ($m->items as $it) {
            $qtyOrder = (int) ($it->qty ?? 0);
            if ($qtyOrder <= 0) {
                continue;
            }

            $parent = $this->normSku($it->sku_parent ?? null);
            $code = $this->normSku($it->sku_code ?? null);

            $recipes = [];

            // 1) coba sku_code dulu
            if ($code !== '' && isset($recipeMap['CODE|' . $code])) {
                $recipes = $recipeMap['CODE|' . $code];
            }

            // 2) fallback parent
            if (empty($recipes) && $parent !== '' && isset($recipeMap['PARENT|' . $parent])) {
                $recipes = $recipeMap['PARENT|' . $parent];
            }

            if (empty($recipes)) {
                continue;
            }

            foreach ($recipes as $rcp) {
                $itemId = (int) $rcp['item_id'];
                $mult = max(1, (int) $rcp['mult']);
                $erpQty = $qtyOrder * $mult;

                $erpCode = $itemIdToCode[$itemId] ?? null;
                if (!$erpCode) {
                    continue;
                }

                $erpCode = $this->normSku($erpCode);
                if ($erpCode === '') {
                    continue;
                }

                $out[$erpCode] = ($out[$erpCode] ?? 0) + $erpQty;
            }
        }

        return $out;
    }

    private function scoreSkuOverlap(array $mpSku, array $shipRemainSku): array
    {
        $mpQty = 0;
        $overlapQty = 0;

        foreach ($mpSku as $sku => $q) {
            $q = (int) $q;
            if ($q <= 0) {
                continue;
            }

            $mpQty += $q;

            $avail = (int) ($shipRemainSku[$sku] ?? 0);
            if ($avail <= 0) {
                continue;
            }

            $overlapQty += min($q, $avail);
        }

        if ($mpQty <= 0) {
            return [0, 0];
        }

        $score = (int) round(100 * ($overlapQty / $mpQty));
        return [$score, $overlapQty];
    }

    private function consumeRemainingSku(array &$remain, array $mpSku): void
    {
        foreach ($mpSku as $sku => $q) {
            $q = (int) $q;
            if ($q <= 0) {
                continue;
            }

            $cur = (int) ($remain[$sku] ?? 0);
            if ($cur <= 0) {
                continue;
            }

            $remain[$sku] = max(0, $cur - $q);
        }
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

    // =========================================================
    // Recipe & maps
    // =========================================================
    private function loadRecipeMap(?string $channel): array
    {
        if (!Schema::hasTable('mp_sku_recipes')) {
            return [];
        }

        $rows = DB::table('mp_sku_recipes')
            ->select('channel', 'mp_sku_parent', 'mp_sku_code', 'item_id', 'multiplier')
            ->where(function ($q) use ($channel) {
                $q->whereNull('channel');
                if ($channel) {
                    $q->orWhere('channel', $channel);
                }
            })
            ->get();

        $map = [];
        foreach ($rows as $r) {
            if (!empty($r->mp_sku_parent)) {
                $k = 'PARENT|' . $this->normSku($r->mp_sku_parent);
                $map[$k][] = ['item_id' => (int) $r->item_id, 'mult' => (int) $r->multiplier];
            }
            if (!empty($r->mp_sku_code)) {
                $k = 'CODE|' . $this->normSku($r->mp_sku_code);
                $map[$k][] = ['item_id' => (int) $r->item_id, 'mult' => (int) $r->multiplier];
            }
        }

        return $map;
    }

    private function loadItemIdToCodeMap(): array
    {
        return DB::table('items')
            ->where('type', 'finished_good')
            ->pluck('code', 'id')
            ->mapWithKeys(fn($code, $id) => [(int) $id => (string) $code])
            ->toArray();
    }

    private function isColumnNullable(string $table, string $column): bool
    {
        try {
            $cols = DB::select("PRAGMA table_info('$table')");
            if (is_array($cols) && !empty($cols)) {
                foreach ($cols as $c) {
                    if (($c->name ?? null) === $column) {
                        return ((int) ($c->notnull ?? 1)) === 0;
                    }
                }
            }
        } catch (\Throwable $e) {
            // ignore
        }
        return false;
    }
}
