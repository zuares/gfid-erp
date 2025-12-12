<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\StockOpname;
use App\Models\StockOpnameLine;
use App\Models\Warehouse;
use App\Services\Inventory\StockOpnameService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class StockOpnameController extends Controller
{
    public function __construct(
        protected StockOpnameService $stockOpnameService
    ) {
        // $this->middleware('auth');
    }

    /**
     * Index daftar stock opname.
     */
    public function index(Request $request): View
    {
        $warehouses = Warehouse::orderBy('code')->get();

        $query = StockOpname::with(['warehouse', 'creator'])
            ->withCount('lines')
            ->orderByDesc('date')
            ->orderByDesc('id');

        if ($request->filled('warehouse_id')) {
            $query->where('warehouse_id', $request->integer('warehouse_id'));
        }

        // status: draft/counting/reviewed/finalized/all
        if ($request->filled('status')) {
            $status = $request->string('status')->toString();
            if ($status !== 'all' && in_array($status, [
                StockOpname::STATUS_DRAFT,
                StockOpname::STATUS_COUNTING,
                StockOpname::STATUS_REVIEWED,
                StockOpname::STATUS_FINALIZED,
            ], true)) {
                $query->where('status', $status);
            }
        }

        if ($request->filled('type')) {
            $type = $request->string('type')->toString();
            if ($type !== 'all' && in_array($type, [StockOpname::TYPE_PERIODIC, StockOpname::TYPE_OPENING], true)) {
                $query->where('type', $type);
            }
        }

        if ($request->filled('date_from')) {
            $query->whereDate('date', '>=', Carbon::parse($request->input('date_from'))->toDateString());
        }

        if ($request->filled('date_to')) {
            $query->whereDate('date', '<=', Carbon::parse($request->input('date_to'))->toDateString());
        }

        $opnames = $query->paginate(20)->appends($request->query());

        return view('inventory.stock_opnames.index', compact('opnames', 'warehouses'));
    }

    /**
     * Form buat sesi stock opname baru.
     * Bisa dipanggil dengan ?type=periodic / ?type=opening
     */
    public function create(Request $request): View
    {
        $warehouses = Warehouse::orderBy('code')->get();

        $mode = $request->get('type', StockOpname::TYPE_PERIODIC);
        if (!in_array($mode, [StockOpname::TYPE_PERIODIC, StockOpname::TYPE_OPENING], true)) {
            $mode = StockOpname::TYPE_PERIODIC;
        }

        return view('inventory.stock_opnames.create', [
            'warehouses' => $warehouses,
            'mode' => $mode,
        ]);
    }

    /**
     * Simpan sesi stock opname baru.
     * - periodic: generate lines dari stok sistem
     * - opening: tidak generate, user input manual stok awal + HPP
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],
            'date' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
            'auto_generate_lines' => ['nullable', 'boolean'],
            'type' => ['nullable', 'in:' . StockOpname::TYPE_PERIODIC . ',' . StockOpname::TYPE_OPENING],
        ]);

        $type = $validated['type'] ?? StockOpname::TYPE_PERIODIC;
        $autoGenerate = $request->boolean('auto_generate_lines', true);

        $opname = null;

        DB::transaction(function () use ($validated, $type, $autoGenerate, &$opname) {
            $opname = new StockOpname();
            $opname->code = $this->generateOpnameCodeForDate($validated['date']);
            $opname->type = $type;
            $opname->warehouse_id = (int) $validated['warehouse_id'];
            $opname->date = $validated['date'];
            $opname->notes = $validated['notes'] ?? null;

            // langsung masuk counting
            $opname->status = StockOpname::STATUS_COUNTING;
            $opname->created_by = auth()->id();
            $opname->save();

            if ($type === StockOpname::TYPE_PERIODIC && $autoGenerate) {
                $this->stockOpnameService->generateLinesFromWarehouse(
                    opname: $opname,
                    warehouseId: $opname->warehouse_id,
                    onlyWithStock: true,
                );
            }
        });

        return redirect()
            ->route('inventory.stock_opnames.edit', $opname)
            ->with('status', 'success')
            ->with('message', 'Sesi stock opname berhasil dibuat.');
    }

    /**
     * Form edit / input hasil counting.
     */
    public function edit(StockOpname $stockOpname): View
    {
        $stockOpname->load(['warehouse', 'lines.item', 'creator']);
        return view('inventory.stock_opnames.edit', ['opname' => $stockOpname]);
    }

    public function show(StockOpname $stockOpname): View
    {
        $stockOpname->load(['warehouse', 'lines.item', 'creator', 'reviewer', 'finalizer']);

        $adjustment = \App\Models\InventoryAdjustment::query()
            ->where('source_type', \App\Models\StockOpname::class)
            ->where('source_id', $stockOpname->id)
            ->latest('id')
            ->first();

        return view('inventory.stock_opnames.show', [
            'opname' => $stockOpname,
            'adjustment' => $adjustment,
        ]);
    }

    /**
     * Helper generate kode SO-YYYYMMDD-###
     */
    private function generateOpnameCodeForDate(string $date): string
    {
        $d = Carbon::parse($date);
        $dateStr = $d->format('Ymd');
        $prefix = 'SO-' . $dateStr . '-';

        $last = StockOpname::where('code', 'like', $prefix . '%')
            ->orderByDesc('code')
            ->first();

        $nextNumber = 1;
        if ($last) {
            $lastNumber = (int) substr($last->code, strlen($prefix));
            $nextNumber = $lastNumber + 1;
        }

        return sprintf('%s%03d', $prefix, $nextNumber);
    }

    /**
     * Update hasil counting (physical_qty / unit_cost / notes).
     */
    public function update(Request $request, StockOpname $stockOpname): RedirectResponse
    {
        if (!$stockOpname->canModifyLines()) {
            return redirect()
                ->route('inventory.stock_opnames.show', $stockOpname)
                ->with('status', 'error')
                ->with('message', 'Dokumen ini sudah tidak bisa diubah lagi.');
        }

        $validated = $request->validate([
            'notes' => ['nullable', 'string'],
            'lines' => ['nullable', 'array'],
            'lines.*.physical_qty' => ['nullable', 'numeric', 'gte:0'],
            'lines.*.unit_cost' => ['nullable', 'numeric', 'gte:0'],
            'lines.*.notes' => ['nullable', 'string'],
            'mark_reviewed' => ['nullable', 'boolean'],
        ]);

        $markReviewed = $request->boolean('mark_reviewed');

        DB::transaction(function () use ($stockOpname, $validated, $markReviewed) {
            $stockOpname->notes = $validated['notes'] ?? $stockOpname->notes;

            if ($stockOpname->status === StockOpname::STATUS_DRAFT) {
                $stockOpname->status = StockOpname::STATUS_COUNTING;
            }

            $linesInput = $validated['lines'] ?? [];

            if (!empty($linesInput)) {
                $stockOpname->load('lines');

                foreach ($linesInput as $lineId => $data) {
                    /** @var \App\Models\StockOpnameLine|null $line */
                    $line = $stockOpname->lines->firstWhere('id', (int) $lineId);
                    if (!$line) {
                        continue;
                    }

                    $systemQty = (float) ($line->system_qty ?? 0);

                    $physicalQty = (($data['physical_qty'] ?? '') !== '')
                    ? (float) $data['physical_qty']
                    : null;

                    $difference = 0.0;
                    $isCounted = false;

                    if ($physicalQty !== null) {
                        $difference = $physicalQty - $systemQty;
                        $isCounted = true;
                    }

                    $line->physical_qty = $physicalQty;
                    $line->difference_qty = $difference;
                    $line->is_counted = $isCounted;
                    $line->notes = $data['notes'] ?? $line->notes;

                    if (array_key_exists('unit_cost', $data)) {
                        $line->unit_cost = $data['unit_cost'] !== null ? (float) $data['unit_cost'] : null;
                    }

                    $line->save();
                }
            }

            if ($markReviewed) {
                $stockOpname->load('lines');

                $notCountedExists = $stockOpname->lines->contains(fn($line) => !$line->is_counted);

                if ($notCountedExists) {
                    throw ValidationException::withMessages([
                        'mark_reviewed' => 'Masih ada item yang belum di-count. Lengkapi dulu sebelum menandai counting selesai.',
                    ]);
                }

                $stockOpname->status = StockOpname::STATUS_REVIEWED;
                $stockOpname->reviewed_by = auth()->id();
                $stockOpname->reviewed_at = now();
            }

            $stockOpname->save();
        });

        // ✅ kalau klik tombol selesai counting
        if ($request->boolean('mark_reviewed')) {
            return redirect()
                ->route('inventory.stock_opnames.show', $stockOpname)
                ->with('success', 'Counting selesai. Dokumen dikirim untuk review.');
        }

        // ✅ kalau klik simpan biasa
        if ($request->boolean('save_and_view')) {
            return redirect()
                ->route('inventory.stock_opnames.show', $stockOpname)
                ->with('success', 'Perubahan berhasil disimpan.');
        }

        // fallback default (kalau ada submit lain)
        return redirect()
            ->back()
            ->with('success', 'Perubahan berhasil disimpan.');
    }

    /**
     * Finalisasi dokumen stock opname.
     *
     * NOTE:
     * Dengan service yang baru:
     * - opening  -> menghasilkan InventoryAdjustment (approved) -> muncul di adjustments
     * - periodic -> menghasilkan InventoryAdjustment (approved/pending tergantung role)
     */
    public function finalize(Request $request, StockOpname $stockOpname): RedirectResponse
    {
        if (!$stockOpname->canFinalize()) {
            return redirect()
                ->route('inventory.stock_opnames.show', $stockOpname)
                ->with('status', 'error')
                ->with('message', 'Dokumen hanya bisa difinalkan jika status sudah reviewed dan belum final.');
        }

        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        try {
            $adjustment = $this->stockOpnameService->finalize(
                $stockOpname,
                $validated['reason'] ?? null,
                $validated['notes'] ?? null,
            );
        } catch (\Throwable $e) {
            report($e);

            return redirect()
                ->route('inventory.stock_opnames.show', $stockOpname)
                ->with('status', 'error')
                ->with('message', 'Gagal finalize stock opname: ' . $e->getMessage());
        }

        // SELALU redirect ke adjustment kalau ada
        if ($adjustment) {
            return redirect()
                ->route('inventory.adjustments.show', $adjustment)
                ->with('status', 'success')
                ->with('message', 'Stock opname difinalkan. Adjustment: ' . $adjustment->code);
        }

        // fallback (harusnya opening/periodic sekarang selalu menghasilkan adjustment)
        return redirect()
            ->route('inventory.stock_opnames.show', $stockOpname)
            ->with('status', 'success')
            ->with('message', 'Stock opname berhasil difinalkan.');
    }

    public function addLine(Request $request, StockOpname $stockOpname): RedirectResponse | \Illuminate\Http\JsonResponse
    {
        if (!$stockOpname->canModifyLines()) {
            $message = 'Tidak bisa menambah item pada dokumen yang sudah direview/final.';
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['status' => 'error', 'message' => $message], 422);
            }

            return redirect()
                ->route('inventory.stock_opnames.edit', $stockOpname)
                ->with('status', 'error')
                ->with('message', $message);
        }

        if (!$stockOpname->isOpening()) {
            $message = 'Penambahan item manual hanya diizinkan untuk mode Opening Balance.';
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['status' => 'error', 'message' => $message], 422);
            }

            return redirect()
                ->route('inventory.stock_opnames.edit', $stockOpname)
                ->with('status', 'error')
                ->with('message', $message);
        }

        $validated = $request->validate([
            'item_id' => ['required', 'exists:items,id'],
            'physical_qty' => ['nullable', 'numeric', 'gte:0'],
            'unit_cost' => ['nullable', 'numeric', 'gte:0'],
            'notes' => ['nullable', 'string', 'max:255'],
            'update_existing' => ['nullable', 'boolean'],
        ]);

        $updateExisting = $request->boolean('update_existing');

        DB::transaction(function () use ($stockOpname, $validated, $updateExisting) {
            $itemId = (int) $validated['item_id'];

            $existingLine = $stockOpname->lines()->where('item_id', $itemId)->first();

            if ($existingLine && !$updateExisting) {
                throw ValidationException::withMessages([
                    'item_id' => 'Item ini sudah ada di daftar opname. Edit baris yang sudah ada.',
                ]);
            }

            $physicalQty = array_key_exists('physical_qty', $validated) && $validated['physical_qty'] !== null
            ? (float) $validated['physical_qty']
            : null;

            $unitCost = array_key_exists('unit_cost', $validated) ? $validated['unit_cost'] : null;
            $notes = $validated['notes'] ?? null;

            if ($existingLine) {
                $systemQty = (float) ($existingLine->system_qty ?? 0);
                $difference = 0.0;
                $isCounted = false;

                if ($physicalQty !== null) {
                    $difference = $physicalQty - $systemQty;
                    $isCounted = true;
                }

                $existingLine->physical_qty = $physicalQty;
                $existingLine->difference_qty = $difference;
                $existingLine->is_counted = $isCounted;
                $existingLine->unit_cost = $unitCost !== null ? (float) $unitCost : null;
                $existingLine->notes = $notes ?? $existingLine->notes;
                $existingLine->save();
            } else {
                $systemQty = 0.0;
                $difference = 0.0;
                $isCounted = false;

                if ($physicalQty !== null) {
                    $difference = $physicalQty - $systemQty;
                    $isCounted = true;
                }

                $line = new StockOpnameLine();
                $line->stock_opname_id = $stockOpname->id;
                $line->item_id = $itemId;
                $line->system_qty = $systemQty;
                $line->physical_qty = $physicalQty;
                $line->difference_qty = $difference;
                $line->is_counted = $isCounted;
                $line->unit_cost = $unitCost !== null ? (float) $unitCost : null;
                $line->notes = $notes;
                $line->save();
            }
        });

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['status' => 'ok', 'message' => 'Item saldo awal berhasil disimpan.']);
        }

        return redirect()
            ->route('inventory.stock_opnames.edit', $stockOpname)
            ->with('status', 'success')
            ->with('message', 'Item saldo awal berhasil disimpan.');
    }

    public function deleteLine(Request $request, StockOpname $stockOpname, StockOpnameLine $line)
    {
        if ($line->stock_opname_id !== $stockOpname->id) {
            abort(404);
        }

        if (!$stockOpname->canModifyLines()) {
            $message = 'Tidak bisa menghapus item pada dokumen yang sudah direview/final.';
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['status' => 'error', 'message' => $message], 422);
            }

            return redirect()
                ->route('inventory.stock_opnames.edit', $stockOpname)
                ->with('status', 'error')
                ->with('message', $message);
        }

        $line->delete();

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['status' => 'ok', 'message' => 'Item berhasil dihapus dari opname.']);
        }

        return redirect()
            ->route('inventory.stock_opnames.edit', $stockOpname)
            ->with('status', 'success')
            ->with('message', 'Item berhasil dihapus dari opname.');
    }

    public function resetLines(Request $request, StockOpname $stockOpname): RedirectResponse
    {
        if (!$stockOpname->canModifyLines()) {
            return redirect()
                ->route('inventory.stock_opnames.edit', $stockOpname)
                ->with('status', 'error')
                ->with('message', 'Tidak bisa reset baris pada dokumen yang sudah direview/final.');
        }

        if (!$stockOpname->isOpening()) {
            return redirect()
                ->route('inventory.stock_opnames.edit', $stockOpname)
                ->with('status', 'error')
                ->with('message', 'Reset baris hanya diizinkan untuk mode Opening Balance.');
        }

        DB::transaction(function () use ($stockOpname) {
            $lines = $stockOpname->lines()->get();

            foreach ($lines as $line) {
                $line->physical_qty = null;
                $line->difference_qty = 0;
                $line->is_counted = false;
                $line->unit_cost = null;
                $line->save();
            }
        });

        return redirect()
            ->route('inventory.stock_opnames.edit', $stockOpname)
            ->with('status', 'success')
            ->with('message', 'Qty fisik dan HPP semua baris telah di-reset. Daftar item tetap dipertahankan.');
    }

    public function resetAllLines(StockOpname $stockOpname): RedirectResponse
    {
        if (!$stockOpname->isOpening()) {
            return back()->with('status', 'error')->with('message', 'Reset semua baris hanya diperbolehkan untuk mode Opening.');
        }

        if (!$stockOpname->canModifyLines()) {
            return back()->with('status', 'error')->with('message', 'Tidak dapat reset: dokumen sudah direview atau final.');
        }

        DB::transaction(function () use ($stockOpname) {
            $stockOpname->lines()->delete();
        });

        return back()->with('status', 'success')->with('message', 'Semua baris berhasil dihapus. Anda dapat mulai input kembali.');
    }
}
