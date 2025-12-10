<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\StockOpname;
use App\Models\StockOpnameLine;
use App\Models\Warehouse;
use App\Services\Inventory\StockOpnameService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        // optional: filter type (periodic / opening)
        if ($request->filled('type')) {
            $query->where('type', $request->string('type')->toString());
        }

        if ($request->filled('date_from')) {
            $query->whereDate('date', '>=', $request->date('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('date', '<=', $request->date('date_to'));
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

        // default periodic, bisa override via query string
        $mode = $request->get('type', 'periodic');

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
            'type' => ['nullable', 'in:periodic,opening'],
        ]);

        $type = $validated['type'] ?? 'periodic';
        $autoGenerate = $request->boolean('auto_generate_lines', true);

        $opname = null;

        DB::transaction(function () use ($validated, $type, $autoGenerate, &$opname) {
            $opname = new StockOpname();
            $opname->code = $this->generateOpnameCodeForDate($validated['date']);
            $opname->type = $type;
            $opname->warehouse_id = $validated['warehouse_id'];
            $opname->date = $validated['date'];
            $opname->notes = $validated['notes'] ?? null;
            $opname->status = 'counting';
            $opname->created_by = auth()->id();
            $opname->save();

            // periodic: generate lines dari stok sistem
            if ($type === 'periodic' && $autoGenerate) {
                $this->stockOpnameService->generateLinesFromWarehouse(
                    opname: $opname,
                    warehouseId: $opname->warehouse_id,
                    onlyWithStock: true,
                );
            }

            // opening: tidak generate apa-apa → user akan tambah baris manual via addLine()
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

        return view('inventory.stock_opnames.edit', [
            'opname' => $stockOpname,
        ]);
    }

    /**
     * Detail 1 dokumen stock opname.
     */
    public function show(StockOpname $stockOpname): View
    {
        $stockOpname->load(['warehouse', 'lines.item', 'creator', 'finalizer']);

        return view('inventory.stock_opnames.show', [
            'opname' => $stockOpname,
        ]);
    }

    /**
     * Helper generate kode SO-YYYYMMDD-###
     * Menggunakan tanggal dokumen opname (bukan always today),
     * supaya lebih konsisten kalau backdate.
     */
    private function generateOpnameCodeForDate(string $date): string
    {
        $d = Carbon::parse($date);
        $dateStr = $d->format('Ymd');
        $prefix = 'SO-' . $dateStr . '-';

        // Cari code terakhir untuk tanggal tersebut
        $last = StockOpname::where('code', 'like', $prefix . '%')
            ->orderByDesc('code')
            ->first();

        if ($last) {
            // ambil angka setelah prefix, misal SO-20251202-007 → 7
            $lastNumber = (int) substr($last->code, strlen($prefix));
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }

        return sprintf('%s%03d', $prefix, $nextNumber);
    }

    /**
     * Backward compat (kalau ada pemanggilan lama), tidak wajib dipakai.
     */
    private function generateOpnameCode(): string
    {
        return $this->generateOpnameCodeForDate(Carbon::today()->toDateString());
    }

    /**
     * Update hasil counting (system_qty / physical_qty / unit_cost / notes).
     */
    public function update(Request $request, StockOpname $stockOpname): RedirectResponse
    {
        $validated = $request->validate([
            'notes' => ['nullable', 'string'],
            'lines' => ['nullable', 'array'],
            'lines.*.physical_qty' => ['nullable', 'numeric'],
            'lines.*.system_qty' => ['nullable', 'numeric'],
            'lines.*.unit_cost' => ['nullable', 'numeric'], // HPP / unit (untuk opening)
            'lines.*.notes' => ['nullable', 'string'],
            'mark_reviewed' => ['nullable', 'boolean'],
        ]);

        $markReviewed = $request->boolean('mark_reviewed');

        DB::transaction(function () use ($stockOpname, $validated, $markReviewed) {
            // 1️⃣ update header
            $stockOpname->notes = $validated['notes'] ?? $stockOpname->notes;

            // kalau masih draft → counting
            if ($stockOpname->status === 'draft') {
                $stockOpname->status = 'counting';
            }

            // kalau user klik "Simpan & Tandai Selesai Counting"
            if ($markReviewed) {
                $stockOpname->status = 'reviewed';
            }

            $stockOpname->save();

            // 2️⃣ update lines
            $linesInput = $validated['lines'] ?? [];

            if (!empty($linesInput)) {
                $stockOpname->load('lines');

                foreach ($linesInput as $lineId => $data) {
                    $line = $stockOpname->lines->firstWhere('id', (int) $lineId);
                    if (!$line) {
                        continue;
                    }

                    $systemQty = isset($data['system_qty'])
                    ? (float) $data['system_qty']
                    : (float) $line->system_qty;

                    $physicalQty = ($data['physical_qty'] ?? '') !== ''
                    ? (float) $data['physical_qty']
                    : null;

                    $difference = 0;
                    $isCounted = false;

                    if ($physicalQty !== null) {
                        $difference = $physicalQty - $systemQty;
                        $isCounted = true;
                    }

                    $line->system_qty = $systemQty;
                    $line->physical_qty = $physicalQty;
                    $line->difference_qty = $difference;
                    $line->is_counted = $isCounted;
                    $line->notes = $data['notes'] ?? $line->notes;

                    // simpan unit_cost kalau dikirim (mode opening)
                    if (array_key_exists('unit_cost', $data)) {
                        $line->unit_cost = $data['unit_cost'] !== null
                        ? (float) $data['unit_cost']
                        : null;
                    }

                    $line->save();
                }
            }
        });

        // 3️⃣ redirect tergantung tombol
        if ($markReviewed) {
            // Selesai counting → ke halaman review (show)
            return redirect()
                ->route('inventory.stock_opnames.show', $stockOpname)
                ->with('status', 'success')
                ->with('message', 'Counting selesai. Dokumen sudah ditandai sebagai reviewed.');
        }

        // Hanya simpan → tetap di halaman edit
        return redirect()
            ->route('inventory.stock_opnames.edit', $stockOpname)
            ->with('status', 'success')
            ->with('message', 'Perubahan hasil counting berhasil disimpan.');
    }

    /**
     * Finalisasi dokumen stock opname:
     * - periodic  → generate InventoryAdjustment + mutasi stok selisih.
     * - opening   → generate saldo awal (stockIn dengan HPP), bisa tanpa ADJ.
     */
    public function finalize(Request $request, StockOpname $stockOpname): RedirectResponse
    {
        // optional: hanya boleh finalize kalau status sudah reviewed
        if (!in_array($stockOpname->status, ['reviewed'])) {
            return redirect()
                ->route('inventory.stock_opnames.show', $stockOpname)
                ->with('status', 'error')
                ->with('message', 'Dokumen hanya bisa difinalkan jika status sudah reviewed.');
        }

        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        try {
            $adjustment = $this->stockOpnameService->finalize(
                $stockOpname,
                $validated['reason'] ?? ('Penyesuaian stok dari stock opname ' . $stockOpname->code),
                $validated['notes'] ?? null,
            );
        } catch (\Throwable $e) {
            report($e);

            return redirect()
                ->route('inventory.stock_opnames.show', $stockOpname)
                ->with('status', 'error')
                ->with('message', 'Gagal finalize stock opname: ' . $e->getMessage());
        }

        // 🔀 Behavior beda untuk opening vs periodic
        if ($stockOpname->type === 'opening' || !$adjustment) {
            // Untuk opening balance, fokus di saldo awal.
            // adjustment bisa null kalau finalize memutuskan tidak buat dokumen ADJ.
            return redirect()
                ->route('inventory.stock_opnames.show', $stockOpname)
                ->with('status', 'success')
                ->with('message', 'Stock opname (opening balance) berhasil difinalkan.');
        }

        // periodic: redirect ke dokumen adjustment yang dibuat
        return redirect()
            ->route('inventory.adjustments.show', $adjustment) // sesuaikan dengan nama route-mu
            ->with('status', 'success')
            ->with('message', 'Stock opname difinalkan. Adjustment: ' . $adjustment->code);
    }

    public function addLine(Request $request, StockOpname $stockOpname): RedirectResponse | \Illuminate\Http\JsonResponse
    {
        // Hanya boleh tambah baris kalau belum finalized
        if (!in_array($stockOpname->status, ['draft', 'counting'])) {
            $message = 'Tidak bisa menambah item pada dokumen yang sudah direview/final.';
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['status' => 'error', 'message' => $message], 422);
            }

            return redirect()
                ->route('inventory.stock_opnames.edit', $stockOpname)
                ->with('status', 'error')
                ->with('message', $message);
        }

        // Batasi hanya mode opening
        if ($stockOpname->type !== 'opening') {
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

            $existingLine = $stockOpname->lines()
                ->where('item_id', $itemId)
                ->first();

            // Kalau sudah ada & user TIDAK minta update → error lama
            if ($existingLine && !$updateExisting) {
                throw ValidationException::withMessages([
                    'item_id' => 'Item ini sudah ada di daftar opname. Edit baris yang sudah ada.',
                ]);
            }

            $physicalQty = array_key_exists('physical_qty', $validated) && $validated['physical_qty'] !== null
            ? (float) $validated['physical_qty']
            : null;

            $unitCost = $validated['unit_cost'] ?? null;
            $notes = $validated['notes'] ?? null;

            if ($existingLine) {
                // 🔁 UPDATE BARIS EXISTING
                $systemQty = (float) ($existingLine->system_qty ?? 0);
                $difference = 0;
                $isCounted = false;

                if ($physicalQty !== null) {
                    $difference = $physicalQty - $systemQty;
                    $isCounted = true;
                }

                $existingLine->physical_qty = $physicalQty;
                $existingLine->difference_qty = $difference;
                $existingLine->is_counted = $isCounted;
                $existingLine->unit_cost = $unitCost;
                $existingLine->notes = $notes ?? $existingLine->notes;
                $existingLine->save();
            } else {
                // ➕ TAMBAH BARU
                $systemQty = 0.0; // opening dari 0
                $difference = 0;
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
                $line->unit_cost = $unitCost;
                $line->notes = $notes;
                $line->save();
            }
        });

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'status' => 'ok',
                'message' => 'Item saldo awal berhasil disimpan.',
            ]);
        }

        return redirect()
            ->route('inventory.stock_opnames.edit', $stockOpname)
            ->with('status', 'success')
            ->with('message', 'Item saldo awal berhasil disimpan.');
    }

    public function deleteLine(Request $request, StockOpname $stockOpname, StockOpnameLine $line)
    {
        // pastikan line memang milik opname ini
        if ($line->stock_opname_id !== $stockOpname->id) {
            abort(404);
        }

        // tidak boleh hapus kalau sudah reviewed/final
        if (!in_array($stockOpname->status, ['draft', 'counting'])) {
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
            return response()->json([
                'status' => 'ok',
                'message' => 'Item berhasil dihapus dari opname.',
            ]);
        }

        return redirect()
            ->route('inventory.stock_opnames.edit', $stockOpname)
            ->with('status', 'success')
            ->with('message', 'Item berhasil dihapus dari opname.');
    }

    public function resetLines(Request $request, StockOpname $stockOpname)
    {
        // Hanya boleh reset kalau masih draft / counting
        if (!in_array($stockOpname->status, ['draft', 'counting'])) {
            return redirect()
                ->route('inventory.stock_opnames.edit', $stockOpname)
                ->with('status', 'error')
                ->with('message', 'Tidak bisa reset baris pada dokumen yang sudah direview/final.');
        }

        // Batasi hanya untuk mode opening
        if ($stockOpname->type !== 'opening') {
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

                // kalau tabel punya kolom unit_cost, kita reset juga
                if (\Schema::hasColumn($line->getTable(), 'unit_cost')) {
                    $line->unit_cost = null;
                }

                // Catatan tetap dibiarkan, supaya info tambahan tidak hilang
                $line->save();
            }
        });

        return redirect()
            ->route('inventory.stock_opnames.edit', $stockOpname)
            ->with('status', 'success')
            ->with('message', 'Qty fisik dan HPP semua baris telah di-reset. Daftar item tetap dipertahankan.');
    }

    public function resetAllLines(StockOpname $stockOpname)
    {
        // Hanya untuk opening & belum finalize
        if ($stockOpname->type !== 'opening') {
            return back()
                ->with('status', 'error')
                ->with('message', 'Reset semua baris hanya diperbolehkan untuk mode Opening.');
        }

        if (in_array($stockOpname->status, ['reviewed', 'finalized'])) {
            return back()
                ->with('status', 'error')
                ->with('message', 'Tidak dapat reset: dokumen sudah direview atau final.');
        }

        DB::transaction(function () use ($stockOpname) {
            $stockOpname->lines()->delete();
        });

        return back()
            ->with('status', 'success')
            ->with('message', 'Semua baris berhasil dihapus. Anda dapat mulai input kembali.');
    }

}
