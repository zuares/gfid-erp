<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\PrdDispatchCorrection;
use App\Models\PrdDispatchCorrectionLine;
use App\Models\StockRequest;
use App\Models\Warehouse;
use App\Services\Inventory\InventoryService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PrdDispatchCorrectionController extends Controller
{
    public function __construct(
        protected InventoryService $inventory
    ) {}

    public function create(StockRequest $stockRequest): View
    {
        abort_unless($stockRequest->purpose === 'rts_replenish', 404);
        abort_if($stockRequest->status === 'completed', 404);

        $stockRequest->load(['lines.item', 'sourceWarehouse', 'destinationWarehouse']);

        $transit = Warehouse::where('code', 'WH-TRANSIT')->firstOrFail();

        // untuk guard UI: berapa maksimal boleh dibalikin dari TRANSIT?
        // maxRevert = dispatched - received (yang belum keluar ke RTS)
        $maxRevertByLine = [];
        foreach ($stockRequest->lines as $line) {
            $disp = (float) ($line->qty_dispatched ?? 0);
            $recv = (float) ($line->qty_received ?? 0);
            $maxRevertByLine[$line->id] = max($disp - $recv, 0);
        }

        // live transit stock per line (optional bantu UX)
        $liveTransitByLine = [];
        foreach ($stockRequest->lines as $line) {
            $liveTransitByLine[$line->id] = $this->inventory->getAvailableStock((int) $transit->id, (int) $line->item_id);
        }

        return view('inventory.prd_stock_requests.dispatch_corrections.create', [
            'stockRequest' => $stockRequest,
            'transit' => $transit,
            'maxRevertByLine' => $maxRevertByLine,
            'liveTransitByLine' => $liveTransitByLine,
        ]);
    }

    public function store(Request $request, StockRequest $stockRequest): RedirectResponse
    {
        abort_unless($stockRequest->purpose === 'rts_replenish', 404);
        abort_if($stockRequest->status === 'completed', 404);

        $validated = $request->validate([
            'date' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
            'lines' => ['required', 'array'],
            'lines.*.qty_adjust' => ['nullable', 'numeric'], // signed
            'lines.*.notes' => ['nullable', 'string'],
        ]);

        $transit = Warehouse::where('code', 'WH-TRANSIT')->firstOrFail();

        $srcPrdId = (int) $stockRequest->source_warehouse_id; // PRD
        $transitId = (int) $transit->id;

        $any = false;
        $correction = null;

        DB::transaction(function () use (
            &$any,
            &$correction,
            $stockRequest,
            $validated,
            $srcPrdId,
            $transitId
        ) {
            // lock header + lines biar aman dari double submit/race
            $sr = StockRequest::whereKey($stockRequest->id)->lockForUpdate()->first();
            $sr->load(['lines']);

            $correction = PrdDispatchCorrection::create([
                'stock_request_id' => $sr->id,
                'date' => $validated['date'],
                'notes' => $validated['notes'] ?? null,
                'created_by_user_id' => Auth::id(),
            ]);

            foreach ($sr->lines as $line) {
                $input = $validated['lines'][$line->id] ?? null;
                $qtyAdj = $input && array_key_exists('qty_adjust', $input) ? (float) $input['qty_adjust'] : 0.0;

                if (abs($qtyAdj) <= 0.0000001) {
                    continue;
                }

                // =========================
                // GUARDS (TERUTAMA MINUS)
                // =========================
                $disp = (float) ($line->qty_dispatched ?? 0);
                $recv = (float) ($line->qty_received ?? 0);

                // yang aman dibalikin hanya yang BELUM diterima RTS:
                $maxRevert = max($disp - $recv, 0);

                if ($qtyAdj < 0) {
                    $wantRevert = abs($qtyAdj);

                    if ($wantRevert > $maxRevert + 0.0000001) {
                        throw ValidationException::withMessages([
                            "lines.{$line->id}.qty_adjust" =>
                            "Balikkan melebihi sisa di TRANSIT (maks: {$maxRevert}). Sudah ada yang diterima RTS.",
                        ]);
                    }

                    // Transit tidak boleh minus → move TRANSIT->PRD allowNegative=false
                    try {
                        $this->inventory->move(
                            itemId: (int) $line->item_id,
                            fromWarehouseId: $transitId,
                            toWarehouseId: $srcPrdId,
                            qty: (float) $wantRevert,
                            referenceType: 'prd_dispatch_correction',
                            referenceId: $correction->id,
                            notes: "PRD dispatch correction (revert) — SR {$sr->code}",
                            date: $validated['date'],
                            allowNegative: false
                        );
                    } catch (\RuntimeException $e) {
                        throw ValidationException::withMessages(['stock' => $e->getMessage()]);
                    }

                    // update dispatched: turun
                    $line->qty_dispatched = max($disp - $wantRevert, 0);
                    $line->save();
                } else {
                    // PLUS: tambah dispatch PRD->TRANSIT (PRD boleh minus)
                    try {
                        $this->inventory->move(
                            itemId: (int) $line->item_id,
                            fromWarehouseId: $srcPrdId,
                            toWarehouseId: $transitId,
                            qty: (float) $qtyAdj,
                            referenceType: 'prd_dispatch_correction',
                            referenceId: $correction->id,
                            notes: "PRD dispatch correction (add) — SR {$sr->code}",
                            date: $validated['date'],
                            allowNegative: true
                        );
                    } catch (\RuntimeException $e) {
                        throw ValidationException::withMessages(['stock' => $e->getMessage()]);
                    }

                    $line->qty_dispatched = (float) $disp + (float) $qtyAdj;
                    $line->save();
                }

                PrdDispatchCorrectionLine::create([
                    'prd_dispatch_correction_id' => $correction->id,
                    'stock_request_line_id' => $line->id,
                    'item_id' => $line->item_id,
                    'qty_adjust' => $qtyAdj,
                    'notes' => $input['notes'] ?? null,
                ]);

                $any = true;
            }

            if (!$any) {
                throw ValidationException::withMessages([
                    'lines' => 'Tidak ada baris koreksi yang diisi (qty adjust ≠ 0).',
                ]);
            }

            // =========================
            // Update status SR (minimal)
            // - kalau ada dispatched > 0 => shipped
            // - kalau 0 dispatched => balik ke submitted (optional)
            // =========================
            $sr->refresh()->load('lines');

            $hasAnyDispatched = $sr->lines->contains(fn($l) => (float) ($l->qty_dispatched ?? 0) > 0.0000001);
            if ($hasAnyDispatched && in_array($sr->status, ['submitted', 'shipped'], true)) {
                $sr->status = 'shipped';
            }
            if (!$hasAnyDispatched && $sr->status === 'shipped') {
                $sr->status = 'submitted'; // optional
            }

            $sr->save();
        });

        return redirect()
            ->route('prd.stock-requests.dispatch_corrections.show', $correction)
            ->with('status', 'PRD Dispatch Correction berhasil dibuat.');
    }

    public function show(PrdDispatchCorrection $correction): View
    {
        $correction->load(['stockRequest', 'lines.item']);

        return view('inventory.prd_stock_requests.dispatch_corrections.show', [
            'correction' => $correction,
        ]);
    }
}
