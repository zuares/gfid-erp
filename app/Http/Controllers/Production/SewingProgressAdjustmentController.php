<?php

namespace App\Http\Controllers\Production;

use App\Helpers\CodeGenerator;
use App\Http\Controllers\Controller;
use App\Models\SewingPickup;
use App\Models\SewingPickupLine;
use App\Models\SewingProgressAdjustment;
use App\Models\SewingProgressAdjustmentLine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SewingProgressAdjustmentController extends Controller
{
    /* ============================================================
     * INDEX
     * ============================================================
     */
    public function index(Request $request): View
    {
        $docs = SewingProgressAdjustment::query()
            ->with(['pickup', 'operator'])
            ->withCount('lines')
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('production.sewing_progress_adjustments.index', compact('docs'));
    }

    /* ============================================================
     * CREATE
     * ============================================================
     */
    public function create(Request $request): View
    {
        $pickups = SewingPickup::query()
            ->with(['operator', 'lines.bundle.finishedItem'])
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->get();

        $pickupId = $request->integer('pickup_id');

        $pickup = null;
        $lines = collect();

        if ($pickupId) {
            $pickup = SewingPickup::with([
                'operator',
                'lines.bundle.finishedItem',
            ])->find($pickupId);

            if ($pickup) {
                $lines = $pickup->lines
                    ->map(function (SewingPickupLine $pl) {
                        $qtyBundle = (float) ($pl->qty_bundle ?? 0);
                        $returnedOk = (float) ($pl->qty_returned_ok ?? 0);
                        $returnedRej = (float) ($pl->qty_returned_reject ?? 0);
                        $directPick = (float) ($pl->qty_direct_picked ?? 0);
                        $progressAdj = (float) ($pl->qty_progress_adjusted ?? 0);

                        $remaining = max(
                            $qtyBundle - ($returnedOk + $returnedRej + $directPick + $progressAdj),
                            0
                        );

                        $pl->remaining_qty = $remaining;
                        return $pl;
                    })
                    ->filter(fn($l) => (float) ($l->remaining_qty ?? 0) > 0.000001)
                    ->values();
            }
        }

        return view('production.sewing_progress_adjustments.create', [
            'pickups' => $pickups,
            'pickup' => $pickup,
            'pickupId' => $pickupId,
            'lines' => $lines,
        ]);
    }

    /* ============================================================
     * STORE
     * ============================================================
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'date' => ['required', 'date'],
            'pickup_id' => ['required', 'exists:sewing_pickups,id'],
            'lines' => ['required', 'array', 'min:1'],

            'lines.*.sewing_pickup_line_id' => ['required', 'exists:sewing_pickup_lines,id'],
            'lines.*.qty_adjust' => ['required', 'numeric', 'min:0.0001'],
            'lines.*.reason' => ['nullable', 'string', 'max:120'],

            'notes' => ['nullable', 'string'],
        ]);

        return DB::transaction(function () use ($validated) {

            $pickup = SewingPickup::with('lines')
                ->lockForUpdate()
                ->findOrFail((int) $validated['pickup_id']);

            // buat header dokumen
            $doc = SewingProgressAdjustment::create([
                'code' => CodeGenerator::generate('SPA'),
                'date' => $validated['date'],
                'sewing_pickup_id' => $pickup->id,
                'operator_id' => $pickup->operator_id,
                'notes' => $validated['notes'] ?? null,
                'created_by' => auth()->id(),
            ]);

            foreach ($validated['lines'] as $row) {
                /** @var SewingPickupLine $pl */
                $pl = SewingPickupLine::lockForUpdate()->findOrFail((int) $row['sewing_pickup_line_id']);

                // anti bypass
                if ((int) $pl->sewing_pickup_id !== (int) $pickup->id) {
                    throw ValidationException::withMessages([
                        'lines' => 'Ada baris pickup yang tidak sesuai.',
                    ]);
                }

                $qtyBundle = (float) ($pl->qty_bundle ?? 0);
                $returnedOk = (float) ($pl->qty_returned_ok ?? 0);
                $returnedRej = (float) ($pl->qty_returned_reject ?? 0);
                $directPick = (float) ($pl->qty_direct_picked ?? 0);
                $progressAdj = (float) ($pl->qty_progress_adjusted ?? 0);

                $remaining = max(
                    $qtyBundle - ($returnedOk + $returnedRej + $directPick + $progressAdj),
                    0
                );

                $adjust = (float) ($row['qty_adjust'] ?? 0);

                if ($adjust > $remaining + 0.000001) {
                    throw ValidationException::withMessages([
                        'lines' => "Qty adjustment melebihi sisa pickup (line #{$pl->id}). Sisa: {$remaining}",
                    ]);
                }

                // simpan detail
                SewingProgressAdjustmentLine::create([
                    'sewing_progress_adjustment_id' => $doc->id,
                    'sewing_pickup_line_id' => $pl->id,
                    'qty_adjust' => $adjust,
                    'reason' => $row['reason'] ?? null,
                ]);

                // INTI: update progress (tanpa inventory)
                $pl->qty_progress_adjusted = (float) ($pl->qty_progress_adjusted ?? 0) + $adjust;
                $pl->save();
            }

            return redirect()
                ->route('production.sewing.adjustments.show', $doc) // ✅ FIX ROUTE NAME
                ->with('success', 'Progress penjahit berhasil di-update (tanpa mutasi stok).');
        });
    }

    /* ============================================================
     * SHOW
     * ============================================================
     */
    public function show(SewingProgressAdjustment $doc): View
    {
        $doc->load([
            'operator', // kalau relasi operator ada di doc
            'pickup.operator',
            'pickup.lines.bundle.finishedItem', // ✅ penting untuk snapshot pickup (sesudah adjustment)
            'lines.sewingPickupLine.bundle.finishedItem', // detail baris adjustment
        ]);

        return view('production.sewing_progress_adjustments.show', [
            'doc' => $doc,
        ]);
    }

}
