<?php

namespace App\Http\Controllers\Production;

use App\Helpers\CodeGenerator;
use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\SewingPickup;
use App\Models\SewingPickupLine;
use App\Models\SewingReturn;
use App\Models\SewingReturnLine;
use App\Models\Warehouse;
use App\Services\Inventory\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SewingReturnController extends Controller
{
    public function __construct(
        protected InventoryService $inventory,
    ) {}

    public function index(Request $request)
    {
        $q = SewingReturn::query()
            ->with([
                'warehouse',
                'operator',
                'lines',
            ]);

        // Filter tanggal (optional)
        if ($request->filled('date_from')) {
            $q->whereDate('date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $q->whereDate('date', '<=', $request->date_to);
        }

        // Filter operator (optional)
        if ($request->filled('operator_id')) {
            $q->where('operator_id', $request->operator_id);
        }

        $returns = $q->orderByDesc('date')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        // List operator jahit untuk filter
        $operators = Employee::query()
            ->where('role', 'sewing')
            ->orderBy('code')
            ->get();

        return view('production.sewing_returns.index', [
            'returns' => $returns,
            'operators' => $operators,
            'filters' => $request->only(['date_from', 'date_to', 'operator_id']),
        ]);
    }

    public function show(SewingReturn $sewingReturn)
    {
        $sewingReturn->load([
            'warehouse',
            'operator',
            'lines.sewingPickupLine.sewingPickup',
            'lines.sewingPickupLine.bundle.finishedItem',
            'lines.sewingPickupLine.bundle.cuttingJob.lot.item',
        ]);

        // Asumsi semua line berasal dari pickup yang sama
        $pickup = optional($sewingReturn->lines->first()?->sewingPickupLine)->sewingPickup;

        return view('production.sewing_returns.show', [
            'return' => $sewingReturn,
            'pickup' => $pickup,
        ]);
    }

    /**
     * Form Sewing Return untuk satu Sewing Pickup
     * GET /production/sewing/returns/create?pickup_id=XX
     */
    public function create(Request $request)
    {
        $pickupId = $request->get('pickup_id');

        /** @var SewingPickup $pickup */
        $pickup = SewingPickup::with([
            'warehouse',
            'operator',
            'lines.bundle.finishedItem',
            'lines.bundle.cuttingJob.lot.item',
        ])
            ->findOrFail($pickupId);

        // Hanya lines yang masih in_progress
        $lines = $pickup->lines()
            ->where('status', 'in_progress')
            ->get();

        if ($lines->isEmpty()) {
            return redirect()
                ->route('production.sewing_pickups.show', $pickup)
                ->with('warning', 'Semua bundle pada pickup ini sudah selesai dikembalikan.');
        }

        return view('production.sewing_returns.create', [
            'pickup' => $pickup,
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
        $validated = $request->validate([
            'pickup_id' => ['required', 'exists:sewing_pickups,id'],
            'date' => ['required', 'date'],
            'notes' => ['nullable', 'string'],

            'results' => ['required', 'array', 'min:1'],
            'results.*.line_id' => ['required', 'exists:sewing_pickup_lines,id'],
            'results.*.qty_ok' => ['nullable', 'numeric', 'min:0'],
            'results.*.qty_reject' => ['nullable', 'numeric', 'min:0'],
            'results.*.notes' => ['nullable', 'string'],
        ], [
            'results.required' => 'Minimal satu baris hasil jahit harus diisi.',
            'results.*.line_id.required' => 'Baris bundle tidak valid.',
        ]);

        DB::transaction(function () use ($validated) {

            /** @var SewingPickup $pickup */
            $pickup = SewingPickup::with([
                'warehouse',
                'operator',
            ])
                ->findOrFail($validated['pickup_id']);

            $date = $validated['date'];

            // Cari gudang tujuan: WIP-FIN & REJECT
            $wipFinWarehouseId = Warehouse::where('code', 'WIP-FIN')->value('id');
            $rejectWarehouseId = Warehouse::where('code', 'REJECT')->value('id'); // optional boleh kamu bikin tidak wajib

            if (!$wipFinWarehouseId) {
                throw ValidationException::withMessages([
                    'pickup_id' => 'Gudang WIP-FIN belum dikonfigurasi. Pastikan ada warehouse dengan code "WIP-FIN".',
                ]);
            }

            // Header Sewing Return
            /** @var SewingReturn $return */
            $return = SewingReturn::create([
                'code' => CodeGenerator::generate('SWR'),
                'date' => $date,
                'warehouse_id' => $pickup->warehouse_id,
                'operator_id' => $pickup->operator_id,
                'status' => 'draft',
                'notes' => $validated['notes'] ?? null,
            ]);

            $processedLines = 0;

            foreach ($validated['results'] as $row) {
                $qtyOk = (float) ($row['qty_ok'] ?? 0);
                $qtyReject = (float) ($row['qty_reject'] ?? 0);
                $totalReturn = $qtyOk + $qtyReject;

                if ($totalReturn <= 0) {
                    continue;
                }

                /** @var SewingPickupLine $line */
                $line = SewingPickupLine::with([
                    'bundle.finishedItem',
                    'bundle.cuttingJob.lot',
                ])
                    ->findOrFail($row['line_id']);

                $bundle = $line->bundle;
                $lot = $bundle?->cuttingJob?->lot;
                $lotId = $lot?->id; // cuma buat info / future use

                // Cek sisa yang masih boleh direturn:
                $alreadyReturned = (float) (($line->qty_returned_ok ?? 0) + ($line->qty_returned_reject ?? 0));
                $remaining = (float) ($line->qty_bundle - $alreadyReturned);

                if ($remaining <= 0) {
                    continue;
                }

                // Clamp: jangan boleh lebih dari remaining
                if ($totalReturn > $remaining) {
                    $excess = $totalReturn - $remaining;

                    if ($qtyReject >= $excess) {
                        $qtyReject -= $excess;
                    } else {
                        $excess -= $qtyReject;
                        $qtyReject = 0;
                        $qtyOk = max(0, $qtyOk - $excess);
                    }

                    $totalReturn = $qtyOk + $qtyReject;
                    if ($totalReturn <= 0) {
                        continue;
                    }
                }

                // 🔹 Simpan SewingReturnLine
                SewingReturnLine::create([
                    'sewing_return_id' => $return->id,
                    'sewing_pickup_line_id' => $line->id,
                    'qty_ok' => $qtyOk,
                    'qty_reject' => $qtyReject,
                    'notes' => $row['notes'] ?? null,
                ]);

                $notes = "Sewing return {$return->code} - bundle {$bundle->bundle_code}";

                // 🔹 INVENTORY:
                // OUT dari gudang sewing (pickup->warehouse_id)
                $this->inventory->stockOut(
                    warehouseId: $pickup->warehouse_id,
                    itemId: $bundle->finished_item_id,
                    qty: $totalReturn,
                    date: $date,
                    sourceType: 'sewing_return',
                    sourceId: $return->id,
                    notes: $notes,
                    allowNegative: false,
                    lotId: null, // ⬅️ WIP move, JANGAN konsumsi lot cost lagi
                );

                // IN ke WIP-FIN (OK)
                if ($qtyOk > 0) {
                    $this->inventory->stockIn(
                        warehouseId: $wipFinWarehouseId,
                        itemId: $bundle->finished_item_id,
                        qty: $qtyOk,
                        date: $date,
                        sourceType: 'sewing_return_ok',
                        sourceId: $return->id,
                        notes: $notes,
                        lotId: null,
                        unitCost: null,
                    );
                }

                // IN ke REJECT (Reject)
                if ($qtyReject > 0 && $rejectWarehouseId) {
                    $this->inventory->stockIn(
                        warehouseId: $rejectWarehouseId,
                        itemId: $bundle->finished_item_id,
                        qty: $qtyReject,
                        date: $date,
                        sourceType: 'sewing_return_reject',
                        sourceId: $return->id,
                        notes: $notes,
                        lotId: null,
                        unitCost: null,
                    );
                }

                // 🔹 Update progress line
                $line->qty_returned_ok = (float) ($line->qty_returned_ok ?? 0) + $qtyOk;
                $line->qty_returned_reject = (float) ($line->qty_returned_reject ?? 0) + $qtyReject;

                $totalAfter = ($line->qty_returned_ok ?? 0) + ($line->qty_returned_reject ?? 0);
                if ($totalAfter >= $line->qty_bundle) {
                    $line->status = 'done';
                }

                $line->save();

                $processedLines++;
            }

            if ($processedLines === 0) {
                throw ValidationException::withMessages([
                    'results' => 'Tidak ada baris Sewing Return yang valid. Pastikan Qty OK/Reject diisi dan tidak melebihi sisa.',
                ]);
            }

            // Optional: kalau semua line pickup sudah done → tutup pickup
            $stillInProgress = $pickup->lines()
                ->where('status', 'in_progress')
                ->exists();

            if (!$stillInProgress) {
                $pickup->status = 'closed';
                $pickup->save();
            }
        });

        return redirect()
            ->route('production.sewing_pickups.index')
            ->with('success', 'Sewing return berhasil disimpan dan stok sudah dipindahkan ke WIP-FIN / REJECT.');
    }

    public function operatorSummary(Request $request)
    {
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');
        $operatorId = $request->get('operator_id');

        $q = SewingReturn::query()
            ->join('sewing_return_lines as l', 'l.sewing_return_id', '=', 'sewing_returns.id')
            ->join('employees as e', 'e.id', '=', 'sewing_returns.operator_id')
            ->selectRaw('
            sewing_returns.operator_id,
            e.code as operator_code,
            e.name as operator_name,
            SUM(l.qty_ok) as total_ok,
            SUM(l.qty_reject) as total_reject,
            COUNT(DISTINCT sewing_returns.id) as total_returns,
            COUNT(l.id) as total_lines
        ')
            ->groupBy('sewing_returns.operator_id', 'e.code', 'e.name');

        if ($dateFrom) {
            $q->whereDate('sewing_returns.date', '>=', $dateFrom);
        }

        if ($dateTo) {
            $q->whereDate('sewing_returns.date', '<=', $dateTo);
        }

        if ($operatorId) {
            $q->where('sewing_returns.operator_id', $operatorId);
        }

        $rows = $q->orderBy('operator_code')->get();

        // list operator buat filter dropdown
        $operators = Employee::query()
            ->where('role', 'sewing')
            ->orderBy('code')
            ->get();

        return view('production.sewing_returns.report_operators', [
            'rows' => $rows,
            'operators' => $operators,
            'filters' => $request->only(['date_from', 'date_to', 'operator_id']),
        ]);
    }

}
