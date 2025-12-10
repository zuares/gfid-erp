<?php

namespace App\Http\Controllers\Production;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\SewingPickup;
use App\Models\SewingPickupLine;
use App\Models\SewingReturn;
use App\Models\SewingReturnLine;
use App\Models\Warehouse;
use App\Services\Inventory\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SewingReturnController extends Controller
{
    public function __construct(
        protected InventoryService $inventory,
    ) {}

    public function index(Request $request)
    {
        $filters = [
            'status' => $request->get('status'),
            'operator_id' => $request->get('operator_id'),
            'from_date' => $request->get('from_date'),
            'to_date' => $request->get('to_date'),
            'q' => $request->get('q'),
        ];

        $query = SewingReturn::with(['operator', 'warehouse', 'pickup'])
            ->when($filters['status'], function ($q, $status) {
                $q->where('status', $status);
            })
            ->when($filters['operator_id'], function ($q, $opId) {
                $q->where('operator_id', $opId);
            })
            ->when($filters['from_date'], function ($q, $from) {
                $q->whereDate('date', '>=', $from);
            })
            ->when($filters['to_date'], function ($q, $to) {
                $q->whereDate('date', '<=', $to);
            })
            ->when($filters['q'], function ($q, $search) {
                $search = trim($search);
                $q->where(function ($inner) use ($search) {
                    $inner->where('code', 'like', "%{$search}%")
                        ->orWhereHas('pickup', function ($qq) use ($search) {
                            $qq->where('code', 'like', "%{$search}%");
                        })
                        ->orWhereHas('operator', function ($qq) use ($search) {
                            $qq->where('code', 'like', "%{$search}%")
                                ->orWhere('name', 'like', "%{$search}%");
                        });
                });
            })
            ->orderByDesc('date')
            ->orderByDesc('id');

        $returns = $query->paginate(20)->withQueryString();

        // kalau mau: semua operator jahit (buat filter)
        $operators = Employee::orderBy('code')->get(); // bisa difilter role sewing nanti

        return view('production.sewing_returns.index', [
            'returns' => $returns,
            'operators' => $operators,
            'filters' => $filters,
        ]);
    }

    public function show(SewingReturn $return)
    {
        $return->load([
            'warehouse',
            'operator',
            'pickup.operator',
            'lines.sewingPickupLine.sewingPickup',
            'lines.sewingPickupLine.bundle.finishedItem',
            'lines.sewingPickupLine.bundle.cuttingJob.lot.item',
        ]);

        $lines = $return->lines;

        // Total ambil (qty_bundle per sewing_pickup_line)
        $totalPickup = $lines->sum(function ($line) {
            $pickupLine = $line->sewingPickupLine;
            return (float) ($pickupLine->qty_bundle ?? 0);
        });

        // Total OK & Reject di return ini
        $totalOk = (float) $lines->sum('qty_ok');
        $totalReject = (float) $lines->sum('qty_reject');
        $totalProcessed = $totalOk + $totalReject;

        // Persentase distribusi OK vs Reject
        $okPercent = $totalProcessed > 0 ? round(($totalOk / $totalProcessed) * 100, 1) : 0.0;
        $rejectPercent = $totalProcessed > 0 ? round(($totalReject / $totalProcessed) * 100, 1) : 0.0;

        // Sisa "belum setor" setelah Sewing Return ini (lihat saldo di SewingPickupLine)
        $totalRemaining = $lines->sum(function ($line) {
            $pickupLine = $line->sewingPickupLine;
            if (!$pickupLine) {
                return 0;
            }

            $qtyBundle = (float) ($pickupLine->qty_bundle ?? 0);
            $returnedOk = (float) ($pickupLine->qty_returned_ok ?? 0);
            $returnedRej = (float) ($pickupLine->qty_returned_reject ?? 0);

            return max($qtyBundle - ($returnedOk + $returnedRej), 0);
        });

        return view('production.sewing_returns.show', [
            'return' => $return,
            'totalPickup' => $totalPickup,
            'totalOk' => $totalOk,
            'totalReject' => $totalReject,
            'totalProcessed' => $totalProcessed,
            'okPercent' => $okPercent,
            'rejectPercent' => $rejectPercent,
            'totalRemaining' => $totalRemaining,
        ]);
    }

    /**
     * Form Sewing Return untuk satu Sewing Pickup
     * GET /production/sewing/returns/create?pickup_id=XX
     */

    public function create(Request $request): View
    {
        // 1. List semua Sewing Pickup (bisa nanti difilter hanya yg status posted/in_progress)
        $pickups = SewingPickup::query()
            ->with('operator')
            ->orderByDesc('date')
            ->orderBy('code')
            ->get();

        // 2. Ambil ?pickup_id=... dari query string
        $pickupId = $request->integer('pickup_id') ?: null;

        $lines = collect();
        $selectedPickup = null;

        if ($pickupId) {
            $selectedPickup = SewingPickup::query()
                ->with([
                    'operator',
                    'warehouse',
                    'lines.bundle.finishedItem',
                    'lines.bundle.cuttingJob.lot',
                ])
                ->find($pickupId);

            if ($selectedPickup) {
                $lines = $selectedPickup->lines
                    ->map(function (SewingPickupLine $line) {
                        // HITUNG SISA SESUAI SCHEMA:
                        // qty_bundle - (qty_returned_ok + qty_returned_reject)
                        $qtyBundle = (float) ($line->qty_bundle ?? 0);
                        $returnedOk = (float) ($line->qty_returned_ok ?? 0);
                        $returnedRej = (float) ($line->qty_returned_reject ?? 0);

// pastikan ga negatif
                        $remaining = max($qtyBundle - ($returnedOk + $returnedRej), 0);
                        $line->remaining_qty = max($remaining, 0);

                        return $line;
                    })
                // hanya tampilkan yang masih ada sisa
                    ->filter(function ($line) {
                        return ($line->remaining_qty ?? 0) > 0;
                    })
                    ->values();
            }
        }

        return view('production.sewing_returns.create', [
            'pickups' => $pickups,
            'selectedPickup' => $selectedPickup,
            'pickupId' => $pickupId,
            'lines' => $lines,
        ]);
    }

    /**
     * Simpan Sewing Return + gerakkan inventory:
     * - OUT dari gudang sewing (WIP-SEW / sesuai pickup->warehouse_id)
     * - IN ke WIP-FIN (OK)
     * - IN ke REJECT (Reject)
     */

    public function store(Request $request)
    {
        $data = $request->validate([
            'pickup_id' => ['required', 'exists:sewing_pickups,id'],
            'date' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
            'operator_id' => ['required', 'exists:employees,id'],

            'results' => ['required', 'array', 'min:1'],

            'results.*.sewing_pickup_line_id' => ['required', 'exists:sewing_pickup_lines,id'],
            'results.*.qty_ok' => ['nullable', 'numeric', 'min:0'],
            'results.*.qty_reject' => ['nullable', 'numeric', 'min:0'],
            'results.*.notes' => ['nullable', 'string'],
        ]);

        /** @var SewingPickup $pickup */
        $pickup = SewingPickup::with(['lines', 'warehouse', 'operator'])
            ->findOrFail($data['pickup_id']);

        // Gudang WIP-FIN (hasil OK)
        $wipFinWarehouse = Warehouse::where('code', 'WIP-FIN')->first();
        if (!$wipFinWarehouse) {
            throw ValidationException::withMessages([
                'pickup_id' => 'Gudang WIP-FIN belum diset di master gudang.',
            ]);
        }

        // Gudang REJ-SEW (hasil jahit REJECT)
        $rejSewWarehouse = Warehouse::where('code', 'REJ-SEW')->first();
        if (!$rejSewWarehouse) {
            throw ValidationException::withMessages([
                'pickup_id' => 'Gudang REJ-SEW belum diset di master gudang.',
            ]);
        }

        if (!$pickup->warehouse_id) {
            throw ValidationException::withMessages([
                'pickup_id' => 'Sewing Pickup ini belum memiliki gudang asal (warehouse_id).',
            ]);
        }

        $return = null;

        DB::transaction(function () use (&$return, $data, $pickup, $wipFinWarehouse, $rejSewWarehouse) {
            // ======================
            // HEADER SEWING RETURN
            // ======================
            $date = Carbon::parse($data['date'] ?? now());
            $dateString = $date->toDateString();
            $prefix = 'SWR-' . $date->format('Ymd') . '-';

            $lastCode = SewingReturn::whereDate('date', $dateString)
                ->where('code', 'like', $prefix . '%')
                ->lockForUpdate()
                ->orderByDesc('code')
                ->value('code');

            $nextNumber = 1;
            if ($lastCode && preg_match('/(\d+)$/', $lastCode, $m)) {
                $nextNumber = (int) $m[1] + 1;
            }

            $code = $prefix . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);

            $return = SewingReturn::create([
                'code' => $code,
                'date' => $dateString,
                'warehouse_id' => $pickup->warehouse_id, // WIP-SEW
                'operator_id' => $data['operator_id'],
                'pickup_id' => $pickup->id,
                'status' => 'posted',
                'notes' => $data['notes'] ?? null,
            ]);

            $adaBaris = false;
            $movementDate = $dateString;

            foreach ($data['results'] as $idx => $row) {
                /** @var SewingPickupLine $line */
                $line = SewingPickupLine::lockForUpdate()
                    ->with(['bundle.finishedItem'])
                    ->findOrFail($row['sewing_pickup_line_id']);

                // Baris harus milik pickup yang sama
                if ((int) $line->sewing_pickup_id !== (int) $pickup->id) {
                    throw ValidationException::withMessages([
                        "results.$idx.sewing_pickup_line_id" =>
                        'Baris ini tidak termasuk Sewing Pickup yang dipilih.',
                    ]);
                }

                $bundle = $line->bundle;
                $item = $bundle?->finishedItem;

                if (!$item) {
                    throw ValidationException::withMessages([
                        "results.$idx.qty_ok" => "Item jadi untuk bundle ini belum di-set.",
                    ]);
                }

                $qtyBundle = (float) ($line->qty_bundle ?? 0);
                $returnedOk = (float) ($line->qty_returned_ok ?? 0);
                $returnedRej = (float) ($line->qty_returned_reject ?? 0);

                // Sisa sebelum setor
                $remaining = max($qtyBundle - ($returnedOk + $returnedRej), 0);

                $qtyOk = (float) ($row['qty_ok'] ?? 0);
                $qtyReject = (float) ($row['qty_reject'] ?? 0);

                // Baris kosong -> skip
                if ($qtyOk <= 0 && $qtyReject <= 0) {
                    continue;
                }

                if ($remaining <= 0) {
                    throw ValidationException::withMessages([
                        "results.$idx.qty_ok" => "Qty sisa bundle sudah 0, tidak bisa setor lagi.",
                    ]);
                }

                if (($qtyOk + $qtyReject) - $remaining > 0.000001) {
                    $max = number_format($remaining, 2, ',', '.');
                    throw ValidationException::withMessages([
                        "results.$idx.qty_ok" => "Qty OK + Reject melebihi qty sisa (maks $max).",
                    ]);
                }

                if ($qtyReject > 0 && empty($row['notes'])) {
                    throw ValidationException::withMessages([
                        "results.$idx.notes" => "Harap isi catatan jika ada qty reject.",
                    ]);
                }

                $adaBaris = true;

                $totalProcessed = $qtyOk + $qtyReject;

                // Detail Sewing Return Line
                $returnLine = SewingReturnLine::create([
                    'sewing_return_id' => $return->id,
                    'sewing_pickup_line_id' => $line->id,
                    'qty_ok' => $qtyOk,
                    'qty_reject' => $qtyReject,
                    'notes' => $row['notes'] ?? null,
                    'finished_qty' => $totalProcessed,
                ]);

                // Update akumulasi di SewingPickupLine
                $line->qty_returned_ok = $returnedOk + $qtyOk;
                $line->qty_returned_reject = $returnedRej + $qtyReject;

                $totalReturned = $line->qty_returned_ok + $line->qty_returned_reject;

                $line->status = ($totalReturned >= $qtyBundle - 0.000001)
                ? 'done'
                : 'in_progress';

                $line->save();

                // ============================
                //  MUTASI STOK (WIP ONLY)
                // ============================
                // Ambil unit_cost per pcs di WIP-SEW
                $unitCostPerPiece = $this->inventory->getItemIncomingUnitCost(
                    warehouseId: $pickup->warehouse_id, // WIP-SEW
                    itemId: $item->id,
                );

                $movementUnitCost = ($unitCostPerPiece !== null && $unitCostPerPiece > 0)
                ? $unitCostPerPiece
                : null;

                // 1) OUT dari WIP-SEW (OK + Reject)
                if ($totalProcessed > 0) {
                    $this->inventory->stockOut(
                        warehouseId: $pickup->warehouse_id,
                        itemId: $item->id,
                        qty: $totalProcessed,
                        date: $movementDate,
                        sourceType: 'sewing_returns',
                        sourceId: $returnLine->id,
                        notes: "Keluar dari WIP-SEW (OK {$qtyOk}, Reject {$qtyReject})",
                        allowNegative: false,
                        lotId: null, // ✅ WIP tidak pakai LOT
                        unitCostOverride: $movementUnitCost,
                        affectLotCost: false, // ✅ tidak sentuh LotCost kain
                    );
                }

                // 2) IN ke WIP-FIN (OK)
                if ($qtyOk > 0) {
                    $this->inventory->stockIn(
                        warehouseId: $wipFinWarehouse->id,
                        itemId: $item->id,
                        qty: $qtyOk,
                        date: $movementDate,
                        sourceType: 'sewing_returns',
                        sourceId: $returnLine->id,
                        notes: 'Masuk WIP-FIN (hasil jahit OK)',
                        lotId: null,
                        unitCost: $movementUnitCost,
                        affectLotCost: false,
                    );

                    // Optional info di bundle (bukan stok real, hanya indikator)
                    $currentWipQty = $bundle->wip_warehouse_id === $wipFinWarehouse->id
                    ? (float) ($bundle->wip_qty ?? 0)
                    : 0;
                    $bundle->wip_warehouse_id = $wipFinWarehouse->id;
                    $bundle->wip_qty = $currentWipQty + $qtyOk;
                    $bundle->save();
                }

                // 3) IN ke REJ-SEW (Reject)
                if ($qtyReject > 0) {
                    $this->inventory->stockIn(
                        warehouseId: $rejSewWarehouse->id,
                        itemId: $item->id,
                        qty: $qtyReject,
                        date: $movementDate,
                        sourceType: 'sewing_returns',
                        sourceId: $returnLine->id,
                        notes: 'Masuk REJ-SEW (hasil jahit reject)',
                        lotId: null,
                        unitCost: $movementUnitCost,
                        affectLotCost: false,
                    );
                }
            }

            if (!$adaBaris) {
                throw ValidationException::withMessages([
                    'results' => 'Isi minimal satu baris Qty OK / Reject.',
                ]);
            }

            // Update status header SewingPickup
            $stillInProgress = $pickup->lines()
                ->where('status', 'in_progress')
                ->exists();

            $pickup->status = $stillInProgress ? 'posted' : 'closed';
            $pickup->save();
        });

        return redirect()
            ->route('production.sewing_returns.show', $return)
            ->with('success', 'Sewing Return + mutasi stok berhasil disimpan.');
    }

}
