<?php

namespace App\Http\Controllers\Production;

use App\Http\Controllers\Controller;
use App\Models\CuttingJobBundle;
use App\Models\Warehouse;
use App\Models\WipFinAdjustment;
use App\Models\WipFinAdjustmentLine;
use App\Services\Inventory\InventoryService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class WipFinAdjustmentController extends Controller
{
    public function __construct(protected InventoryService $inventory)
    {}

    public function index(Request $request): View
    {
        $q = WipFinAdjustment::query()
            ->withCount('lines')
            ->orderByDesc('date')
            ->orderByDesc('id');

        if ($s = $request->query('search')) {
            $q->where(function ($qq) use ($s) {
                $qq->where('code', 'like', "%{$s}%")
                    ->orWhere('reason', 'like', "%{$s}%")
                    ->orWhere('notes', 'like', "%{$s}%");
            });
        }

        if ($status = $request->query('status')) {
            if (in_array($status, ['draft', 'posted', 'void'], true)) {
                $q->where('status', $status);
            }
        }

        $adjustments = $q->paginate(15)->withQueryString();

        return view('production.wip_fin_adjustments.index', compact('adjustments'));
    }

    public function create(): View
    {
        $wipFinWarehouseId = Warehouse::where('code', 'WIP-FIN')->value('id');

        if (!$wipFinWarehouseId) {
            return view('production.wip_fin_adjustments.create', [
                'bundles' => collect(),
            ])->withErrors(['warehouse' => 'Warehouse WIP-FIN belum dikonfigurasi.']);
        }

        $bundles = CuttingJobBundle::query()
            ->readyForFinishing($wipFinWarehouseId)
            ->with(['cuttingJob', 'finishedItem'])
            ->whereNotNull('finished_item_id')
            ->orderBy('cutting_job_id')
            ->orderBy('bundle_no')
            ->orderBy('id')
            ->get();

        return view('production.wip_fin_adjustments.create', compact('bundles'));
    }

    public function store(Request $request): RedirectResponse
    {
        // Header validate
        $validatedHeader = $request->validate([
            'date' => ['required', 'date'],
            'reason' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
        ]);

        $rawLines = (array) $request->input('lines', []);

        // Ambil baris yang enabled + punya type/qty
        $picked = [];
        foreach ($rawLines as $row) {
            $enabled = (int) ($row['enabled'] ?? 0);
            if ($enabled !== 1) {
                continue;
            }

            $bundleId = (int) ($row['bundle_id'] ?? 0);
            $itemId = (int) ($row['item_id'] ?? 0); // akan divalidasi keras vs bundle.finished_item_id
            $type = (string) ($row['type'] ?? '');
            $qty = (int) ($row['qty'] ?? 0);

            if ($bundleId <= 0 || $itemId <= 0 || $qty <= 0) {
                continue;
            }

            if (!in_array($type, ['in', 'out'], true)) {
                continue;
            }

            $picked[] = [
                'bundle_id' => $bundleId,
                'item_id' => $itemId,
                'type' => $type,
                'qty' => $qty,
                'line_notes' => $row['line_notes'] ?? null,
            ];
        }

        if (count($picked) < 1) {
            return back()->withInput()->withErrors([
                'lines' => 'Isi minimal 1 Qty Setelah Koreksi yang berbeda dari WIP Qty (muncul IN/OUT).',
            ]);
        }

        // Validasi exist
        $validator = \Validator::make(
            ['lines' => $picked],
            [
                'lines' => ['required', 'array', 'min:1'],
                'lines.*.bundle_id' => ['required', 'integer', 'exists:cutting_job_bundles,id'],
                'lines.*.item_id' => ['required', 'integer', 'exists:items,id'],
                'lines.*.type' => ['required', 'in:in,out'],
                'lines.*.qty' => ['required', 'integer', 'min:1'],
                'lines.*.line_notes' => ['nullable', 'string', 'max:255'],
            ]
        );
        $validator->validate();

        // ====== KUNCI: 1 bundle = 1 item (finished_item_id) ======
        $bundleIds = array_values(array_unique(array_map(fn($r) => (int) $r['bundle_id'], $picked)));

        $bundles = CuttingJobBundle::query()
            ->whereIn('id', $bundleIds)
            ->get(['id', 'finished_item_id', 'wip_qty'])
            ->keyBy('id');

        foreach ($picked as $idx => $row) {
            $bundle = $bundles[$row['bundle_id']] ?? null;
            if (!$bundle) {
                // harusnya tidak terjadi karena sudah exists
                throw ValidationException::withMessages([
                    'lines' => "Bundle {$row['bundle_id']} tidak ditemukan.",
                ]);
            }

            // ✅ item harus sama dengan finished_item_id bundle
            if ((int) $bundle->finished_item_id !== (int) $row['item_id']) {
                throw ValidationException::withMessages([
                    'lines' => "Invalid item untuk bundle {$row['bundle_id']}. Item harus mengikuti finished_item_id bundle.",
                ]);
            }

            // ✅ OUT tidak boleh lebih dari wip_qty bundle (fisik)
            if ($row['type'] === 'out') {
                $wip = (int) ($bundle->wip_qty ?? 0);
                if ((int) $row['qty'] > $wip) {
                    throw ValidationException::withMessages([
                        "lines.{$idx}.qty" => "Qty OUT melebihi WIP bundle. Bundle {$row['bundle_id']} saldo {$wip}, butuh {$row['qty']}.",
                    ]);
                }
            }
        }

        // Split IN/OUT
        $inLines = [];
        $outLines = [];
        foreach ($picked as $row) {
            if ($row['type'] === 'in') {
                $inLines[] = $row;
            }

            if ($row['type'] === 'out') {
                $outLines[] = $row;
            }

        }

        $created = [];

        DB::transaction(function () use ($validatedHeader, $inLines, $outLines, &$created) {

            $baseNotes = trim((string) ($validatedHeader['notes'] ?? ''));
            $suffix = '[AUTO: Koreksi Hasil Hitung WIP-FIN]';
            $finalNotes = $baseNotes ? ($baseNotes . "\n" . $suffix) : $suffix;

            $make = function (string $type, array $lines) use ($validatedHeader, $finalNotes, &$created) {
                if (count($lines) < 1) {
                    return;
                }

                $adj = \App\Models\WipFinAdjustment::create([
                    'code' => \App\Helpers\CodeGenerator::generate('WFA'),
                    'date' => $validatedHeader['date'],
                    'type' => $type,
                    'reason' => $validatedHeader['reason'] ?? null,
                    'notes' => $finalNotes,
                    'status' => 'draft',
                    'created_by' => \Illuminate\Support\Facades\Auth::id(),
                    'updated_by' => \Illuminate\Support\Facades\Auth::id(),
                ]);

                foreach ($lines as $row) {
                    \App\Models\WipFinAdjustmentLine::create([
                        'wip_fin_adjustment_id' => $adj->id,
                        'bundle_id' => (int) $row['bundle_id'],
                        'item_id' => (int) $row['item_id'],
                        'qty' => (int) $row['qty'],
                        'line_notes' => $row['line_notes'] ?? null,
                    ]);
                }

                $created[] = $adj;
            };

            $make('in', $inLines);
            $make('out', $outLines);
        });

        if (count($created) === 1) {
            return redirect()
                ->route('production.wip-fin-adjustments.show', $created[0]->id)
                ->with('success', 'Draft koreksi berhasil dibuat.');
        }

        $inAdj = collect($created)->firstWhere('type', 'in');
        $outAdj = collect($created)->firstWhere('type', 'out');

        $msg = 'Draft koreksi berhasil dibuat: '
            . ($inAdj ? "IN {$inAdj->code}" : '')
            . (($inAdj && $outAdj) ? ' dan ' : '')
            . ($outAdj ? "OUT {$outAdj->code}" : '')
            . '. Silakan POST masing-masing.';

        return redirect()
            ->route('production.wip-fin-adjustments.show', ($inAdj?->id ?? $created[0]->id))
            ->with('success', $msg);
    }

    public function show(WipFinAdjustment $wip_fin_adjustment): View
    {
        $wip_fin_adjustment->load(['lines.bundle.cuttingJob', 'lines.item']);

        return view('production.wip_fin_adjustments.show', [
            'adj' => $wip_fin_adjustment,
        ]);
    }

    public function edit(WipFinAdjustment $wip_fin_adjustment): RedirectResponse | View
    {
        if ($wip_fin_adjustment->status !== 'draft') {
            return redirect()
                ->route('production.wip-fin-adjustments.show', $wip_fin_adjustment->id)
                ->with('error', 'Hanya draft yang bisa di-edit.');
        }

        $wip_fin_adjustment->load(['lines']);

        return view('production.wip_fin_adjustments.edit', [
            'adj' => $wip_fin_adjustment,
            'dateDefault' => old('date', $wip_fin_adjustment->date?->toDateString()),
        ]);
    }

    public function update(Request $request, WipFinAdjustment $wip_fin_adjustment): RedirectResponse
    {
        if ($wip_fin_adjustment->status !== 'draft') {
            return redirect()
                ->route('production.wip-fin-adjustments.show', $wip_fin_adjustment->id)
                ->with('error', 'Hanya draft yang bisa di-update.');
        }

        $validated = $request->validate([
            'date' => ['required', 'date'],
            'type' => ['required', 'in:in,out'],
            'reason' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],

            'lines' => ['required', 'array', 'min:1'],
            'lines.*.bundle_id' => ['required', 'integer', 'exists:cutting_job_bundles,id'],
            'lines.*.item_id' => ['required', 'integer', 'exists:items,id'],
            'lines.*.qty' => ['required', 'integer', 'min:1'],
            'lines.*.line_notes' => ['nullable', 'string', 'max:255'],
        ]);

        DB::transaction(function () use ($validated, $wip_fin_adjustment) {
            $wip_fin_adjustment->update([
                'date' => $validated['date'],
                'type' => $validated['type'],
                'reason' => $validated['reason'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'updated_by' => Auth::id(),
            ]);

            $wip_fin_adjustment->lines()->delete();

            foreach ($validated['lines'] as $row) {
                WipFinAdjustmentLine::create([
                    'wip_fin_adjustment_id' => $wip_fin_adjustment->id,
                    'bundle_id' => (int) $row['bundle_id'],
                    'item_id' => (int) $row['item_id'],
                    'qty' => (int) $row['qty'],
                    'line_notes' => $row['line_notes'] ?? null,
                ]);
            }
        });

        return redirect()
            ->route('production.wip-fin-adjustments.show', $wip_fin_adjustment->id)
            ->with('success', 'Draft adjustment berhasil diperbarui.');
    }

    /**
     * Tidak dipakai (kita tidak delete posted/void).
     */
    public function destroy(WipFinAdjustment $wip_fin_adjustment)
    {
        abort(404);
    }

    public function post(WipFinAdjustment $adjustment): RedirectResponse
    {
        if ($adjustment->status !== 'draft') {
            return redirect()
                ->route('production.wip-fin-adjustments.show', $adjustment->id)
                ->with('info', 'Adjustment ini sudah diproses.');
        }

        $wipFinWarehouseId = Warehouse::where('code', 'WIP-FIN')->value('id');
        if (!$wipFinWarehouseId) {
            return back()->withErrors(['warehouse' => 'Warehouse WIP-FIN belum dikonfigurasi.']);
        }

        $adjustment->loadMissing('lines');

        // Validasi OUT: bundle.wip_qty harus cukup
        if ($adjustment->type === 'out') {
            foreach ($adjustment->lines as $i => $line) {
                $bundle = CuttingJobBundle::find($line->bundle_id);
                $wip = (int) ($bundle?->wip_qty ?? 0);

                if ($wip < (int) $line->qty) {
                    throw ValidationException::withMessages([
                        "lines.{$i}.qty" =>
                        "WIP bundle tidak cukup. Bundle {$line->bundle_id} saldo {$wip}, butuh {$line->qty}.",
                    ]);
                }
            }
        }

        $movementDate = Carbon::parse($adjustment->date);
        $notesPrefix = "WIP-FIN ADJ {$adjustment->code}";

        DB::transaction(function () use ($adjustment, $wipFinWarehouseId, $movementDate, $notesPrefix) {
            foreach ($adjustment->lines as $line) {
                $qty = (int) $line->qty;
                if ($qty <= 0) {
                    continue;
                }

                if ($adjustment->type === 'in') {
                    $this->inventory->stockIn(
                        warehouseId: $wipFinWarehouseId,
                        itemId: (int) $line->item_id,
                        qty: $qty,
                        date: $movementDate,
                        sourceType: WipFinAdjustment::class,
                        sourceId: $adjustment->id,
                        notes: $notesPrefix . ' IN',
                        lotId: null,
                        unitCost: null,
                        affectLotCost: false,
                    );

                    $bundle = CuttingJobBundle::find($line->bundle_id);
                    if ($bundle) {
                        $bundle->wip_qty = (int) ($bundle->wip_qty ?? 0) + $qty;
                        $bundle->save();
                    }
                } else {
                    $this->inventory->stockOut(
                        warehouseId: $wipFinWarehouseId,
                        itemId: (int) $line->item_id,
                        qty: $qty,
                        date: $movementDate,
                        sourceType: WipFinAdjustment::class,
                        sourceId: $adjustment->id,
                        notes: $notesPrefix . ' OUT',
                        allowNegative: false,
                        lotId: null,
                        unitCostOverride: null,
                        affectLotCost: false,
                    );

                    $bundle = CuttingJobBundle::find($line->bundle_id);
                    if ($bundle) {
                        $bundle->wip_qty = max(0, (int) ($bundle->wip_qty ?? 0) - $qty);
                        $bundle->save();
                    }
                }
            }

            $adjustment->update([
                'status' => 'posted',
                'posted_at' => now(),
                'posted_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);
        });

        return redirect()
            ->route('production.wip-fin-adjustments.show', $adjustment->id)
            ->with('success', 'Adjustment WIP-FIN berhasil di-POST.');
    }

    public function void(Request $request, WipFinAdjustment $adjustment): RedirectResponse
    {
        if ($adjustment->status !== 'posted') {
            return redirect()
                ->route('production.wip-fin-adjustments.show', $adjustment->id)
                ->with('error', 'Hanya adjustment POSTED yang bisa di-VOID.');
        }

        $request->validate([
            'void_reason' => ['required', 'string', 'max:255'],
        ]);

        $wipFinWarehouseId = Warehouse::where('code', 'WIP-FIN')->value('id');
        if (!$wipFinWarehouseId) {
            return back()->withErrors(['warehouse' => 'Warehouse WIP-FIN belum dikonfigurasi.']);
        }

        $adjustment->loadMissing('lines');

        // pakai now() biar jejak void jelas (atau boleh pakai date adjustment)
        $movementDate = now();
        $notesPrefix = "VOID WIP-FIN ADJ {$adjustment->code}";

        DB::transaction(function () use ($adjustment, $wipFinWarehouseId, $movementDate, $notesPrefix, $request) {
            foreach ($adjustment->lines as $line) {
                $qty = (int) $line->qty;
                if ($qty <= 0) {
                    continue;
                }

                if ($adjustment->type === 'in') {
                    // reverse IN => OUT
                    $this->inventory->stockOut(
                        warehouseId: $wipFinWarehouseId,
                        itemId: (int) $line->item_id,
                        qty: $qty,
                        date: $movementDate,
                        sourceType: WipFinAdjustment::class,
                        sourceId: $adjustment->id,
                        notes: $notesPrefix . ' (reverse IN)',
                        allowNegative: false,
                        lotId: null,
                        unitCostOverride: null,
                        affectLotCost: false,
                    );

                    $bundle = CuttingJobBundle::find($line->bundle_id);
                    if ($bundle) {
                        $bundle->wip_qty = max(0, (int) ($bundle->wip_qty ?? 0) - $qty);
                        $bundle->save();
                    }
                } else {
                    // reverse OUT => IN
                    $this->inventory->stockIn(
                        warehouseId: $wipFinWarehouseId,
                        itemId: (int) $line->item_id,
                        qty: $qty,
                        date: $movementDate,
                        sourceType: WipFinAdjustment::class,
                        sourceId: $adjustment->id,
                        notes: $notesPrefix . ' (reverse OUT)',
                        lotId: null,
                        unitCost: null,
                        affectLotCost: false,
                    );

                    $bundle = CuttingJobBundle::find($line->bundle_id);
                    if ($bundle) {
                        $bundle->wip_qty = (int) ($bundle->wip_qty ?? 0) + $qty;
                        $bundle->save();
                    }
                }
            }

            $adjustment->update([
                'status' => 'void',
                'voided_at' => now(),
                'voided_by' => Auth::id(),
                'void_reason' => $request->string('void_reason')->toString(),
                'updated_by' => Auth::id(),
            ]);
        });

        return redirect()
            ->route('production.wip-fin-adjustments.show', $adjustment->id)
            ->with('success', 'Adjustment berhasil di-VOID (mutasi dibalik).');
    }
}
