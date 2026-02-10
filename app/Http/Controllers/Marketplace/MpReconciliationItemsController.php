<?php

namespace App\Http\Controllers\Marketplace;

use App\Http\Controllers\Controller;
use App\Models\Shipment;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class MpReconciliationItemsController extends Controller
{
    private string $tz = 'Asia/Jakarta';

    public function index(Request $request)
    {
        $shipmentId = (int) $request->get('shipment_id', 0);
        $mode = (string) $request->get('mode', 'replace'); // replace|add
        $q = trim((string) $request->get('q', ''));

        // ✅ shipped_at window (±N hari) supaya tidak “nyasar tanggal”
        $window = (int) $request->get('window', 1);
        if ($window < 0) {
            $window = 0;
        }

        if ($window > 7) {
            $window = 7;
        }

        $shipments = Shipment::query()
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->limit(150)
            ->get(['id', 'code', 'store_id', 'date', 'status']);

        $shipment = $shipmentId
        ? Shipment::with('store')->find($shipmentId)
        : null;

        $rows = collect();
        $meta = null;

        if ($shipment) {
            // 1) channel
            $channel = $this->guessChannelFromShipment((string) $shipment->code);

            // 2) shipment date local
            $shipDateYmd = $this->shipmentDateYmd($shipment);

            // 3) window range
            $from = Carbon::parse($shipDateYmd, $this->tz)->subDays($window)->toDateString();
            $to = Carbon::parse($shipDateYmd, $this->tz)->addDays($window)->toDateString();

            // 4) MP aggregate: shipped_at within window (fallback order_created_at)
            $mpAggMap = DB::table('mp_shipments as ms')
                ->join('mp_packet_items as mpi', function ($j) {
                    $j->on(DB::raw("CAST(mpi.mp_shipment_id AS TEXT)"), '=', DB::raw("CAST(ms.id AS TEXT)"));
                })
                ->where('ms.channel', $channel)
                ->whereBetween(DB::raw("date(COALESCE(ms.shipped_at, ms.order_created_at))"), [$from, $to])
                ->whereNotNull('mpi.item_id')
                ->when($q !== '', function ($qq) use ($q) {
                    $qq->join('items as it', 'it.id', '=', 'mpi.item_id')
                        ->where(function ($w) use ($q) {
                            $w->where('it.code', 'like', "%{$q}%")
                                ->orWhere('it.name', 'like', "%{$q}%");
                        });
                })
                ->selectRaw('mpi.item_id as item_id, SUM(mpi.qty) as mp_qty')
                ->groupBy('mpi.item_id')
                ->pluck('mp_qty', 'item_id'); // [item_id => qty]

            // 5) Shipment aggregate
            $shipAggMap = DB::table('shipment_lines as sl')
                ->where('sl.shipment_id', $shipment->id)
                ->selectRaw('sl.item_id as item_id, SUM(sl.qty_scanned) as ship_qty')
                ->groupBy('sl.item_id')
                ->pluck('ship_qty', 'item_id'); // [item_id => qty]

            // 6) union items
            $itemIds = collect(array_keys($mpAggMap->toArray()))
                ->merge(array_keys($shipAggMap->toArray()))
                ->unique()
                ->values();

            if ($itemIds->isEmpty()) {
                $meta = [
                    'channel' => $channel,
                    'ship_date' => $shipDateYmd,
                    'window' => $window,
                    'from' => $from,
                    'to' => $to,
                    'mp_total' => 0,
                    'ship_total' => 0,
                    'delta_total' => 0,
                    'lines' => 0,
                ];

                return view('marketplace.reconcile_items', compact(
                    'shipments', 'shipment', 'shipmentId', 'mode', 'q', 'rows', 'meta', 'window'
                ));
            }

            $items = DB::table('items')
                ->whereIn('id', $itemIds)
                ->select('id', 'code', 'name', 'unit')
                ->get()
                ->keyBy('id');

            $rows = $itemIds->map(function ($id) use ($items, $mpAggMap, $shipAggMap) {
                $it = $items[$id] ?? null;
                $mpQty = (int) ($mpAggMap[$id] ?? 0);
                $shipQty = (int) ($shipAggMap[$id] ?? 0);
                $diff = $shipQty - $mpQty;

                $status = 'ok';
                if ($diff < 0) {
                    $status = 'missing';
                }
                // shipment kurang vs MP
                elseif ($diff > 0) {
                    $status = 'extra';
                }
                // shipment lebih vs MP

                return [
                    'item_id' => (int) $id,
                    'code' => $it?->code ?? ('#' . $id),
                    'name' => $it?->name ?? '-',
                    'unit' => $it?->unit ?? null,
                    'mp_qty' => $mpQty,
                    'ship_qty' => $shipQty,
                    'diff' => $diff,
                    'status' => $status,
                ];
            })->sortByDesc(fn($r) => abs($r['diff']))->values();

            $meta = [
                'channel' => $channel,
                'ship_date' => $shipDateYmd,
                'window' => $window,
                'from' => $from,
                'to' => $to,
                'mp_total' => (int) $rows->sum('mp_qty'),
                'ship_total' => (int) $rows->sum('ship_qty'),
                'delta_total' => (int) $rows->sum('diff'),
                'lines' => (int) $rows->count(),
            ];
        }

        return view('marketplace.reconcile_items', compact(
            'shipments', 'shipment', 'shipmentId', 'mode', 'q', 'rows', 'meta', 'window'
        ));
    }

    /**
     * Apply MP -> shipment_lines based on shipped_at (fallback order_created_at).
     * mode:
     * - replace: qty_scanned = mp_qty
     * - add: qty_scanned += mp_qty
     */
    public function apply(Request $request)
    {
        $data = $request->validate([
            'shipment_id' => ['required', 'integer', 'exists:shipments,id'],
            'mode' => ['required', Rule::in(['replace', 'add'])],
        ]);

        $shipmentId = (int) $data['shipment_id'];
        $mode = (string) $data['mode'];

        $shipment = Shipment::find($shipmentId);
        if (!$shipment) {
            return back()->with('error', 'Shipment tidak ditemukan.');
        }

        $channel = $this->guessChannelFromShipment((string) $shipment->code);
        $shipDateYmd = $this->shipmentDateYmd($shipment);

        // aggregate mp items
        $mpItems = DB::table('mp_shipments as ms')
            ->join('mp_packet_items as mpi', function ($j) {
                $j->on(DB::raw("CAST(mpi.mp_shipment_id AS TEXT)"), '=', DB::raw("CAST(ms.id AS TEXT)"));
            })
            ->where('ms.channel', $channel)
            ->where(function ($dq) use ($shipDateYmd) {
                $dq->whereDate('ms.shipped_at', $shipDateYmd)
                    ->orWhere(function ($qq) use ($shipDateYmd) {
                        $qq->whereNull('ms.shipped_at')
                            ->whereDate('ms.order_created_at', $shipDateYmd);
                    });
            })
            ->whereNotNull('mpi.item_id')
            ->selectRaw('mpi.item_id as item_id, SUM(mpi.qty) as mp_qty')
            ->groupBy('mpi.item_id')
            ->get();

        if ($mpItems->isEmpty()) {
            return back()->with('error', "MP items kosong untuk {$channel} tanggal {$shipDateYmd}. Pastikan mp_shipments.shipped_at terisi & mp_packet_items sudah mapped.");
        }

        $now = now();

        DB::transaction(function () use ($shipmentId, $mpItems, $mode, $now) {
            foreach ($mpItems as $r) {
                $itemId = (int) $r->item_id;
                $qty = (int) $r->mp_qty;

                $lineId = DB::table('shipment_lines')
                    ->where('shipment_id', $shipmentId)
                    ->where('item_id', $itemId)
                    ->value('id');

                if ($lineId) {
                    if ($mode === 'add') {
                        DB::table('shipment_lines')->where('id', $lineId)->update([
                            'qty_scanned' => DB::raw('COALESCE(qty_scanned,0) + ' . $qty),
                            'updated_at' => $now,
                        ]);
                    } else {
                        DB::table('shipment_lines')->where('id', $lineId)->update([
                            'qty_scanned' => $qty,
                            'updated_at' => $now,
                        ]);
                    }
                } else {
                    DB::table('shipment_lines')->insert([
                        'shipment_id' => $shipmentId,
                        'item_id' => $itemId,
                        'qty_scanned' => $qty,
                        'notes' => null,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }
        });

        return back()->with('ok', "Applied MP → shipment_lines ({$mode}) untuk {$channel} {$shipDateYmd}.");
    }

    /**
     * (Optional) audit: show packets contributing to an item.
     */
    public function packets(Request $request)
    {
        $data = $request->validate([
            'shipment_id' => ['required', 'integer', 'exists:shipments,id'],
            'item_id' => ['required', 'integer', 'exists:items,id'],
        ]);

        $shipment = Shipment::find((int) $data['shipment_id']);
        if (!$shipment) {
            return response()->json(['ok' => false, 'message' => 'Shipment not found'], 404);
        }

        $channel = $this->guessChannelFromShipment((string) $shipment->code);
        $shipDateYmd = $this->shipmentDateYmd($shipment);

        $rows = DB::table('mp_shipments as ms')
            ->join('mp_packet_items as mpi', function ($j) {
                $j->on(DB::raw("CAST(mpi.mp_shipment_id AS TEXT)"), '=', DB::raw("CAST(ms.id AS TEXT)"));
            })
            ->where('ms.channel', $channel)
            ->where(function ($dq) use ($shipDateYmd) {
                $dq->whereDate('ms.shipped_at', $shipDateYmd)
                    ->orWhere(function ($qq) use ($shipDateYmd) {
                        $qq->whereNull('ms.shipped_at')
                            ->whereDate('ms.order_created_at', $shipDateYmd);
                    });
            })
            ->where('mpi.item_id', (int) $data['item_id'])
            ->selectRaw('ms.id as mp_shipment_id, ms.platform_order_id, ms.tracking_no, SUM(mpi.qty) as qty')
            ->groupBy('ms.id', 'ms.platform_order_id', 'ms.tracking_no')
            ->orderByRaw('SUM(mpi.qty) desc')
            ->limit(200)
            ->get();

        return response()->json(['ok' => true, 'rows' => $rows]);
    }

    // =========================
    // Helpers
    // =========================

    private function guessChannelFromShipment(string $shipmentCode): string
    {
        $code = strtoupper(trim($shipmentCode));
        if (str_starts_with($code, 'TTK-')) {
            return 'tiktok';
        }

        if (str_starts_with($code, 'SHP-')) {
            return 'shopee';
        }

        // fallback
        return 'shopee';
    }

    private function shipmentDateYmd(Shipment $shipment): string
    {
        // shipment->date adalah datetime; ambil date local
        try {
            return Carbon::parse($shipment->date)->setTimezone($this->tz)->toDateString();
        } catch (\Throwable $e) {
            // fallback: sqlite date() extraction
            $d = DB::table('shipments')
                ->where('id', $shipment->id)
                ->selectRaw("date(date) as d")
                ->value('d');
            return $d ?: now($this->tz)->toDateString();
        }
    }

    private function mpAggByDateChannel(string $channel, string $dateYmd, string $q = '', int $windowDays = 1)
    {
        $from = Carbon::parse($dateYmd, $this->tz)->subDays($windowDays)->toDateString();
        $to = Carbon::parse($dateYmd, $this->tz)->addDays($windowDays)->toDateString();

        $mpAgg = DB::table('mp_shipments as ms')
            ->join('mp_packet_items as mpi', function ($j) {
                $j->on(DB::raw("CAST(mpi.mp_shipment_id AS TEXT)"), '=', DB::raw("CAST(ms.id AS TEXT)"));
            })
            ->where('ms.channel', $channel)
            ->whereBetween(DB::raw("date(COALESCE(ms.shipped_at, ms.order_created_at))"), [$from, $to])
            ->whereNotNull('mpi.item_id')
            ->selectRaw('mpi.item_id as item_id, SUM(mpi.qty) as mp_qty')
            ->groupBy('mpi.item_id');

        if ($q !== '') {
            $mpAgg->join('items as it', 'it.id', '=', 'mpi.item_id')
                ->where(function ($w) use ($q) {
                    $w->where('it.code', 'like', "%{$q}%")
                        ->orWhere('it.name', 'like', "%{$q}%");
                });
        }

        return $mpAgg->pluck('mp_qty', 'item_id');
    }

}
