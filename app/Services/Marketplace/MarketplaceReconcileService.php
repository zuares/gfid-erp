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

        // 1) MP shipments belum reconciled + tanggal shipped/order_created dalam window
        $mp = MpShipment::query()
            ->whereDoesntHave('reconciliation')
            ->when($channel, fn($q) => $q->where('channel', $channel))
            ->when($storeId, fn($q) => $q->where('store_id', $storeId))
            ->where(function ($q) use ($fromStart, $toEnd) {
                $q->whereBetween('shipped_at', [$fromStart, $toEnd])
                    ->orWhere(function ($qq) use ($fromStart, $toEnd) {
                        $qq->whereNull('shipped_at')
                            ->whereBetween('order_created_at', [$fromStart, $toEnd]);
                    });
            })
            ->orderByRaw('coalesce(shipped_at, order_created_at) asc')
            ->limit(2000)
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
        ];

        $matches = [];
        $reviews = [];

        // 2) Prefetch kandidat operasional dalam window tanggal
        $ops = Shipment::query()
            ->whereNull('cancelled_at')
            ->when($storeId, fn($q) => $q->where('store_id', $storeId))
            ->whereBetween('date', [$fromDay->toDateString(), $toDay->toDateString()])
            ->get();

        // Group by date string (karena Shipment.date cast ke Carbon)
        $opsByDate = $ops->groupBy(function ($s) {
            return $s->date ? $s->date->toDateString() : null;
        });

        // Optional AWB map (kalau kolom awb ada)
        $awbMap = collect();
        if ($hasAwbColumn) {
            $awbMap = $ops
                ->filter(fn($s) => !empty($s->awb))
                ->keyBy(fn($s) => (string) $s->awb);
        }

        foreach ($mp as $m) {
            $best = null;
            $bestScore = -1;
            $bestKey = null;

            // A) Prioritas match via AWB kalau tersedia
            if ($hasAwbColumn && !empty($m->tracking_no) && $awbMap->has($m->tracking_no)) {
                $best = $awbMap->get($m->tracking_no);
                $bestScore = 100;
                $bestKey = 'tracking';
            }

            // B) Kalau belum, scoring by date + qty
            if (!$best) {
                $mpDay = $this->effectiveMpDay($m);
                if (!$mpDay) {
                    $stats['skipped']++;
                    continue;
                }

                $candidateDays = $this->dateWindow($mpDay, $windowDays);
                $cands = new Collection();

                foreach ($candidateDays as $d) {
                    if (isset($opsByDate[$d])) {
                        $cands = $cands->merge($opsByDate[$d]);
                    }
                }

                foreach ($cands as $s) {
                    $shipDay = $s->date ? $s->date->toDateString() : null;
                    $score = 0;

                    $score += $this->scoreDateDistance($mpDay, $shipDay);
                    $score += $this->scoreQty($m->total_qty, $s->total_qty);

                    // prefer posted/submitted
                    if (!empty($s->posted_at)) {
                        $score += 10;
                    }

                    if (!empty($s->submitted_at)) {
                        $score += 5;
                    }

                    if ($score > $bestScore) {
                        $best = $s;
                        $bestScore = $score;
                        $bestKey = 'date_qty';
                    }
                }
            }

            if (!$best) {
                $stats['skipped']++;
                continue;
            }

            // Guardrail: jangan auto-match kalau qty salah satu null (masuk review saja)
            $hasQty = ($m->total_qty !== null && $best->total_qty !== null);

            if ($bestScore >= $threshold && $hasQty) {
                $matches[] = [
                    'mp_shipment_id' => $m->id,
                    'shipment_id' => $best->id,
                    'confidence' => min(100, $bestScore),
                    'match_key' => $bestKey,
                ];
            } elseif ($bestScore >= 50) {
                $reviews[] = [
                    'mp_shipment_id' => $m->id,
                    'suggested_shipment_id' => $best->id,
                    'confidence' => min(100, $bestScore),
                    'match_key' => $bestKey,
                ];
            } else {
                $stats['skipped']++;
            }
        }

        $stats['matched'] = count($matches);
        $stats['needs_review'] = count($reviews);

        if ($dryRun) {
            return compact('stats', 'matches', 'reviews');
        }

        // Persist matches safely (avoid race conditions)
        DB::transaction(function () use ($matches) {
            foreach ($matches as $m) {
                MpReconciliation::firstOrCreate(
                    ['mp_shipment_id' => $m['mp_shipment_id']],
                    [
                        'shipment_id' => $m['shipment_id'],
                        'match_key' => $m['match_key'],
                        'match_confidence' => $m['confidence'],
                        'matched_at' => now(),
                        'matched_by' => null, // system
                        'notes' => 'auto reconcile',
                    ]
                );
            }
        });

        return compact('stats', 'matches', 'reviews');
    }

    private function effectiveMpDay(MpShipment $m): ?string
    {
        $dt = $m->shipped_at ?? $m->order_created_at;
        return $dt ? Carbon::parse($dt)->toDateString() : null;
    }

    private function dateWindow(string $ymd, int $days): array
    {
        $d = Carbon::createFromFormat('Y-m-d', $ymd);
        $out = [];
        for ($i = -$days; $i <= $days; $i++) {
            $out[] = $d->copy()->addDays($i)->toDateString();
        }
        return $out;
    }

    private function scoreDateDistance(?string $mpDay, ?string $shipDay): int
    {
        if (!$mpDay || !$shipDay) {
            return 0;
        }

        $a = Carbon::createFromFormat('Y-m-d', $mpDay);
        $b = Carbon::createFromFormat('Y-m-d', $shipDay);
        $diff = abs($a->diffInDays($b));

        return match (true) {
            $diff === 0 => 35,
            $diff === 1 => 20,
            $diff === 2 => 5,
            default => 0,
        };
    }

    private function scoreQty($mpQty, $shipQty): int
    {
        if ($mpQty === null || $shipQty === null) {
            return 0;
        }

        $diff = abs((int) $mpQty - (int) $shipQty);

        return match (true) {
            $diff === 0 => 25,
            $diff === 1 => 15,
            $diff === 2 => 8,
            default => 0,
        };
    }
}
