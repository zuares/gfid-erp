<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\StockOpname;
use App\Models\StockOpnameLine;
use App\Models\Warehouse;
use App\Services\Inventory\StockOpnameService;
use Illuminate\Http\JsonResponse;
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
        $warehouses = Warehouse::query()->orderBy('code')->get();

        $query = StockOpname::query()
            ->with(['warehouse', 'creator'])
            ->withCount('lines')
            ->orderByDesc('date')
            ->orderByDesc('id');

        // Warehouse
        if ($request->filled('warehouse_id')) {
            $query->where('warehouse_id', $request->integer('warehouse_id'));
        }

        // Status: draft/counting/reviewed/finalized/cancelled/all
        $status = $request->string('status')->toString();
        if ($status !== '' && $status !== 'all') {
            $allowedStatuses = [
                StockOpname::STATUS_DRAFT,
                StockOpname::STATUS_COUNTING,
                StockOpname::STATUS_REVIEWED,
                StockOpname::STATUS_FINALIZED,
                StockOpname::STATUS_CANCELLED,
            ];

            if (in_array($status, $allowedStatuses, true)) {
                $query->where('status', $status);
            }
        }

        // Type: periodic/opening/all
        $type = $request->string('type')->toString();
        if ($type !== '' && $type !== 'all') {
            $allowedTypes = [StockOpname::TYPE_PERIODIC, StockOpname::TYPE_OPENING];
            if (in_array($type, $allowedTypes, true)) {
                $query->where('type', $type);
            }
        }

        // Date range
        if ($request->filled('date_from')) {
            $query->whereDate('date', '>=', Carbon::parse($request->input('date_from'))->toDateString());
        }

        if ($request->filled('date_to')) {
            $query->whereDate('date', '<=', Carbon::parse($request->input('date_to'))->toDateString());
        }

        // Search (optional): code / notes
        $q = trim((string) $request->get('q', ''));
        if ($q !== '') {
            $query->where(function ($w) use ($q) {
                $w->where('code', 'like', "%{$q}%")
                    ->orWhere('notes', 'like', "%{$q}%");
            });
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

        // ✅ Hanya aktif kalau mode PERIODIC dan checkbox dicentang
        $autoGenerate = $type === StockOpname::TYPE_PERIODIC
        ? $request->boolean('auto_generate_lines')
        : false;

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

            if ($autoGenerate) {
                // Gudang RM (id=2 / code='RM'): pakai lot-based method untuk bahan baku
                $warehouse = \App\Models\Warehouse::find($opname->warehouse_id);
                $isRmWarehouse = $warehouse && strtoupper((string) $warehouse->code) === 'RM';

                if ($isRmWarehouse) {
                    $this->stockOpnameService->generateRawMaterialLines(
                        opname: $opname,
                        warehouseId: $opname->warehouse_id,
                    );
                } else {
                    $this->stockOpnameService->generateLinesFromWarehouse(
                        opname: $opname,
                        warehouseId: $opname->warehouse_id,
                        onlyWithStock: true,
                    );
                }
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

    /**
     * Detail (read-only) + tombol Simpan & Selesai Hitung + Reopen.
     */

    public function show(Request $request, StockOpname $stockOpname): View | JsonResponse
    {
        $stockOpname->load(['warehouse', 'creator', 'reviewer', 'finalizer', 'lines.item']);

        // ==========================================================
        // Lines query (filter + sorting)
        // ==========================================================
        $linesQ = \App\Models\StockOpnameLine::query()
            ->where('stock_opname_id', $stockOpname->id)
            ->with(['item']);

        $search = trim((string) $request->get('q', ''));
        if ($search !== '') {
            $linesQ->whereHas('item', function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%");
            });
        }

        $counted = (string) $request->get('counted', 'all'); // all|yes|no
        if ($counted === 'yes') {
            $linesQ->where('is_counted', 1);
        } elseif ($counted === 'no') {
            $linesQ->where(function ($q) {
                $q->whereNull('is_counted')->orWhere('is_counted', 0);
            });
        }

        $diffOnly = (bool) $request->boolean('diff_only');
        if ($diffOnly) {
            $linesQ->where('is_counted', 1)
                ->whereRaw('ABS(COALESCE(difference_qty,0)) > 0.0000001');
        }

        $diffSign = (string) $request->get('diff_sign', 'all'); // all|plus|minus
        if ($diffSign === 'plus') {
            $linesQ->where('is_counted', 1)->where('difference_qty', '>', 0);
        } elseif ($diffSign === 'minus') {
            $linesQ->where('is_counted', 1)->where('difference_qty', '<', 0);
        }

        $sort = (string) $request->get('sort', 'item'); // item|system|physical|diff|value|updated
        $dir = strtolower((string) $request->get('dir', 'asc')) === 'desc' ? 'desc' : 'asc';

        switch ($sort) {
            case 'updated':
                $linesQ->orderBy('updated_at', $dir)->orderBy('id', $dir);
                break;
            case 'system':
                $linesQ->orderBy('system_qty', $dir)->orderBy('id', $dir);
                break;
            case 'physical':
                $linesQ->orderByRaw("CASE WHEN physical_qty IS NULL THEN 1 ELSE 0 END")
                    ->orderBy('physical_qty', $dir)->orderBy('id', $dir);
                break;
            case 'diff':
                $linesQ->orderByRaw("CASE WHEN difference_qty IS NULL THEN 1 ELSE 0 END")
                    ->orderBy('difference_qty', $dir)->orderBy('id', $dir);
                break;
            case 'value':
                $linesQ->orderByRaw("ABS(COALESCE(difference_qty,0)) * COALESCE(unit_cost,0) {$dir}")
                    ->orderBy('id', $dir);
                break;
            case 'item':
            default:
                $linesQ->join('items', 'items.id', '=', 'stock_opname_lines.item_id')
                    ->orderBy('items.code', $dir)
                    ->select('stock_opname_lines.*');
                break;
        }

        $lines = $linesQ->get();

        $adjustment = \App\Models\InventoryAdjustment::query()
            ->where('source_type', \App\Models\StockOpname::class)
            ->where('source_id', $stockOpname->id)

            ->latest('id')
            ->first();

        $filters = [
            'q' => $search,
            'counted' => $counted,
            'diff_only' => $diffOnly,
            'diff_sign' => $diffSign,
            'sort' => $sort,
            'dir' => $dir,
        ];

        // ==========================================================
        // Prepare JSON rows + summary (filtered)
        // ==========================================================
        $rows = [];
        $plusQty = 0.0;
        $minusQty = 0.0;
        $plusValue = 0.0;
        $minusValue = 0.0;
        $systemValue = 0.0;
        $physicalValue = 0.0;
        $missingCostCount = 0;

        foreach ($lines as $idx => $line) {
            $itemCode = $line->item?->code ?? '-';
            $itemName = $line->item?->name ?? '';

            $systemQty = (float) ($line->system_qty ?? 0);
            $physicalQty = $line->physical_qty !== null ? (float) $line->physical_qty : null;

            $isCounted = (bool) ($line->is_counted ?? false);

            // diff hanya meaningful kalau counted & physical ada
            $diff = null;
            if ($isCounted && $physicalQty !== null) {
                $diff = (float) ($line->difference_qty ?? ($physicalQty - $systemQty));
            }

            // cost untuk nilai: line.unit_cost -> fallback item.hpp
            $unitCost = 0.0;
            if ($line->unit_cost !== null && (float) $line->unit_cost > 0) {
                $unitCost = (float) $line->unit_cost;
            } elseif ($line->item && (float) ($line->item->hpp ?? 0) > 0) {
                $unitCost = (float) $line->item->hpp;
            } elseif ((float) ($line->effective_unit_cost ?? 0) > 0) {
                $unitCost = (float) $line->effective_unit_cost;
            }

            $value = null;
            if ($diff !== null && abs($diff) >= 0.0000001 && $unitCost > 0) {
                $value = $diff * $unitCost;
            }

            $lineSystemValue = $unitCost > 0 ? $systemQty * $unitCost : null;
            $linePhysicalValue = ($physicalQty !== null && $unitCost > 0) ? $physicalQty * $unitCost : null;

            if ($unitCost > 0) {
                $systemValue += (float) ($lineSystemValue ?? 0);
                if ($physicalQty !== null) {
                    $physicalValue += (float) ($linePhysicalValue ?? 0);
                }
            } else {
                $missingCostCount++;
            }

            // update summary (filtered, counted only)
            if ($diff !== null && abs($diff) >= 0.0000001) {
                if ($diff > 0) {
                    $plusQty += $diff;
                    $plusValue += abs((float) ($value ?? 0));
                } else {
                    $minusQty += $diff;
                    $minusValue += ((float) ($value ?? 0)) <= 0 ? (float) ($value ?? 0) : -abs((float) ($value ?? 0));
                }
            }

            $tone = '';
            if ($diff !== null) {
                $tone = $diff < 0 ? 'diff-danger' : ($diff > 0 ? 'diff-warning' : 'diff-success');
            }

            $rows[] = [
                'no' => $idx + 1,
                'item_code' => $itemCode,
                'item_name' => $itemName,
                'system_qty' => $systemQty,
                'physical_qty' => $physicalQty,
                'diff_qty' => $diff,
                'tone' => $tone,
                'line_id' => $line->id,
                'unit_cost' => $unitCost > 0 ? $unitCost : null,
                'system_value' => $lineSystemValue,
                'physical_value' => $linePhysicalValue,
                'value' => $value,
                'notes' => (string) ($line->notes ?? ''),
                'set_cost_url' => route('inventory.stock_opnames.lines.unit_cost', [
                    'stockOpname' => $stockOpname,
                    'line' => $line,
                ]),
            ];
        }

        $netQty = $plusQty + $minusQty;
        $netValue = $plusValue + $minusValue;

        $netQtyClass = $netQty < 0 ? 'diff-danger' : ($netQty > 0 ? 'diff-warning' : 'diff-success');
        $netValueClass = $netValue < 0 ? 'diff-danger' : ($netValue > 0 ? 'diff-warning' : 'diff-success');

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'ok' => true,
                'count' => count($rows),
                'lines' => $rows,
                'summary' => [
                    'plus_qty' => $plusQty,
                    'minus_qty' => $minusQty,
                    'plus_value' => $plusValue,
                    'minus_value' => $minusValue,
                    'net_qty' => $netQty,
                    'net_value' => $netValue,
                    'system_value' => $systemValue,
                    'physical_value' => $physicalValue,
                    'missing_cost_count' => $missingCostCount,
                    'net_qty_class' => $netQtyClass,
                    'net_value_class' => $netValueClass,
                ],
            ]);
        }

        return view('inventory.stock_opnames.show', [
            'opname' => $stockOpname,
            'adjustment' => $adjustment,
            'lines' => $lines,
            'filters' => $filters,
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
     *
     * PERIODIK:
     * - Baris yang tidak diisi tetap physical_qty = null, is_counted = false.
     * - Tidak ada auto-fill 0 saat simpan / selesai hitung.
     *
     * OPENING:
     * - Baris yang tidak diisi tetap physical_qty = null, is_counted = false.
     *
     * JUGA dipakai oleh:
     * - tombol "Simpan & Selesai Hitung" di SHOW (mark_reviewed = 1, tanpa kirim lines).
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
        $isOpening = $stockOpname->isOpening();

        if ($markReviewed) {
            $userRole = auth()->user()->role ?? null;
            if (!in_array($userRole, ['operating', 'admin', 'owner'], true)) {
                throw ValidationException::withMessages([
                    'mark_reviewed' => 'Anda tidak memiliki hak untuk menandai counting selesai.',
                ]);
            }
        }

        DB::transaction(function () use ($stockOpname, $validated, $markReviewed, $isOpening) {
            $stockOpname->notes = $validated['notes'] ?? $stockOpname->notes;

            if ($stockOpname->status === StockOpname::STATUS_DRAFT) {
                $stockOpname->status = StockOpname::STATUS_COUNTING;
            }

            // load sekali: lines + item (buat fallback cost)
            $stockOpname->load('lines.item');

            $linesInput = $validated['lines'] ?? [];

            // ==========================================================
            // 1) Apply input lines (kalau ada)
            // ==========================================================
            foreach ($linesInput as $lineId => $data) {
                /** @var \App\Models\StockOpnameLine|null $line */
                $line = $stockOpname->lines->firstWhere('id', (int) $lineId);
                if (!$line) {
                    continue;
                }

                if (array_key_exists('physical_qty', $data)) {
                    $systemQty = (float) ($line->system_qty ?? 0);

                    // normalize physical qty
                    $rawPhysical = $data['physical_qty'];

                    if ($rawPhysical === '' || $rawPhysical === null) {
                        $physicalQty = null;
                    } else {
                        $physicalQty = (float) $rawPhysical;
                    }

                    // counted rule (simple & benar)
                    $isCounted = ($physicalQty !== null);

                    $difference = $isCounted ? ($physicalQty - $systemQty) : 0.0;

                    $line->physical_qty = $physicalQty;
                    $line->difference_qty = $difference;
                    $line->is_counted = $isCounted;
                }

                $line->notes = $data['notes'] ?? $line->notes;

                // unit cost (kalau key ada, set; kalau tidak ada, biarkan)
                if (array_key_exists('unit_cost', $data)) {
                    $line->unit_cost = ($data['unit_cost'] !== null && $data['unit_cost'] !== '')
                    ? (float) $data['unit_cost']
                    : null;
                }

                // fallback cost untuk PERIODIC (biar konsisten dengan service kamu)
                if (!$isOpening) {
                    if ($line->unit_cost === null || (float) $line->unit_cost <= 0) {
                        // ✅ rekomendasi: pakai items.hpp karena finalize/generate kamu pakai ini
                        $fallback = (float) ($line->item->hpp ?? 0);

                        // kalau kamu mau base_unit_cost, ganti jadi:
                        // $fallback = (float) ($line->item->base_unit_cost ?? 0);

                        if ($fallback > 0) {
                            $line->unit_cost = $fallback;
                        }
                    }
                }

                $line->save();
            }

            // ==========================================================
            // 2) Mark reviewed validation (harus semua counted)
            // ==========================================================
            if ($markReviewed) {
                if (!$isOpening) {
                    foreach ($stockOpname->lines as $line) {
                        if (!$line->is_counted || $line->physical_qty === null) {
                            $line->physical_qty = 0;
                            $line->difference_qty = 0 - (float) ($line->system_qty ?? 0);
                            $line->is_counted = true;

                            if ($line->unit_cost === null || (float) $line->unit_cost <= 0) {
                                $fallback = (float) ($line->item->hpp ?? 0);
                                if ($fallback > 0) {
                                    $line->unit_cost = $fallback;
                                }
                            }
                            $line->save();
                        }
                    }
                }

                $notCountedExists = $stockOpname->lines->contains(
                    fn($line) => !$line->is_counted || $line->physical_qty === null
                );

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

        if ($request->boolean('mark_reviewed')) {
            return redirect()
                ->route('inventory.stock_opnames.show', $stockOpname)
                ->with('success', 'Counting selesai. Dokumen dikirim untuk review.');
        }

        if ($request->boolean('save_and_view')) {
            return redirect()
                ->route('inventory.stock_opnames.show', $stockOpname)
                ->with('success', 'Perubahan berhasil disimpan.');
        }

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
                ->route('inventory.adjustments.show', ['inventoryAdjustment' => $adjustment->getKey()])
                ->with('status', 'success')
                ->with('message', 'Stock opname difinalkan. Adjustment: ' . $adjustment->code);
        }

        // fallback (harusnya opening/periodic sekarang selalu menghasilkan adjustment)
        return redirect()
            ->route('inventory.stock_opnames.show', $stockOpname)
            ->with('status', 'success')
            ->with('message', 'Stock opname berhasil difinalkan.');
    }

    /**
     * Tambah / update baris opname via AJAX.
     * Sekarang dipakai untuk:
     * - OPENING: tambah item saldo awal
     * - PERIODIK: tambah / update item hasil count (item manual di luar generate stok juga boleh)
     */
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

        $isOpening = $stockOpname->isOpening();

        $validated = $request->validate([
            'item_id' => ['required', 'exists:items,id'],
            'physical_qty' => ['nullable', 'numeric', 'gte:0'],
            'unit_cost' => ['nullable', 'numeric', 'gte:0'],
            'notes' => ['nullable', 'string', 'max:255'],
            'update_existing' => ['nullable', 'boolean'],
        ]);

        $updateExisting = $request->boolean('update_existing');

        DB::transaction(function () use ($stockOpname, $validated, $updateExisting, $isOpening) {
            $itemId = (int) $validated['item_id'];

            $existingLine = $stockOpname->lines()->where('item_id', $itemId)->first();

            if ($existingLine && !$updateExisting) {
                throw ValidationException::withMessages([
                    'item_id' => 'Item ini sudah ada di daftar opname. Edit baris yang sudah ada.',
                ]);
            }

            // PERLAKUAN physical_qty sama seperti di update():
            // - Opening & periodic: kosong tetap null (belum dihitung)
            // - Tidak ada auto-0 saat simpan / selesai hitung
            $rawPhysical = $validated['physical_qty'] ?? null;
            if ($rawPhysical === '' || $rawPhysical === null) {
                $physicalQty = null;
            } else {
                $physicalQty = (float) $rawPhysical;
            }

            $unitCost = array_key_exists('unit_cost', $validated) ? $validated['unit_cost'] : null;
            $notes = $validated['notes'] ?? null;

            if ($existingLine) {
                $systemQty = (float) ($existingLine->system_qty ?? 0);
                $difference = 0.0;
                $isCounted = false;

                if ($physicalQty !== null) {
                    $difference = $physicalQty - $systemQty;
                    $isCounted = ($physicalQty !== null);
                }

                if ($isOpening && $physicalQty === null) {
                    $isCounted = false;
                    $difference = 0.0;
                }

                $existingLine->physical_qty = $physicalQty;
                $existingLine->difference_qty = $difference;
                $existingLine->is_counted = $isCounted;
                // Jangan timpa HPP lama dengan NULL kalau input kosong
                $existingLine->unit_cost = $unitCost !== null ? (float) $unitCost : $existingLine->unit_cost;
                $existingLine->notes = $notes ?? $existingLine->notes;
                $existingLine->save();
            } else {
                // Periodik: item baru manual yang tidak ada di generate
                // dianggap stok sistem 0 (jadi selisih = Qty Fisik - 0)
                $systemQty = 0.0;
                $difference = 0.0;
                $isCounted = false;

                if ($physicalQty !== null) {
                    $difference = $physicalQty - $systemQty;
                    $isCounted = ($physicalQty !== null);

                }

                if ($isOpening && $physicalQty === null) {
                    $isCounted = false;
                    $difference = 0.0;
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
            return response()->json(['status' => 'ok', 'message' => 'Item opname berhasil disimpan.']);
        }

        return redirect()
            ->route('inventory.stock_opnames.edit', $stockOpname)
            ->with('status', 'success')
            ->with('message', 'Item opname berhasil disimpan.');
    }

    public function updateLineUnitCost(Request $request, StockOpname $stockOpname, StockOpnameLine $line): RedirectResponse
    {
        if ((int) $line->stock_opname_id !== (int) $stockOpname->id) {
            abort(404);
        }

        $adjustmentExists = \App\Models\InventoryAdjustment::query()
            ->where('source_type', StockOpname::class)
            ->where('source_id', $stockOpname->id)
            ->exists();

        if (
            $adjustmentExists ||
            $stockOpname->isCancelled() ||
            $stockOpname->status === StockOpname::STATUS_FINALIZED
        ) {
            return redirect()
                ->route('inventory.stock_opnames.show', $stockOpname)
                ->with('status', 'error')
                ->with('message', 'HPP tidak bisa diubah karena dokumen sudah final, dibatalkan, atau sudah punya adjustment.');
        }

        $validated = $request->validate([
            'unit_cost' => ['required', 'numeric', 'gt:0'],
        ]);

        $unitCost = round((float) $validated['unit_cost'], 4);
        $masterUpdated = false;

        DB::transaction(function () use ($line, $unitCost, &$masterUpdated) {
            $line->unit_cost = $unitCost;
            $line->save();

            if (!$line->item_id) {
                return;
            }

            $item = Item::query()->whereKey($line->item_id)->lockForUpdate()->first();
            if (!$item) {
                return;
            }

            $item->hpp = $unitCost;

            if ((float) ($item->base_unit_cost ?? 0) <= 0) {
                $item->base_unit_cost = $unitCost;
            }

            $item->save();
            $masterUpdated = true;
        });

        return redirect()
            ->route('inventory.stock_opnames.show', $stockOpname)
            ->with('status', 'success')
            ->with('message', $masterUpdated
                ? 'HPP baris disimpan dan HPP master item berhasil diperbarui.'
                : 'HPP baris berhasil disimpan.');
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

    /**
     * Reopen Counting:
     * - hanya boleh kalau canReopen() = true (biasanya Owner & status REVIEWED)
     * - tidak menghapus qty fisik, hanya buka status ke COUNTING lagi
     */
    public function reopen(StockOpname $stockOpname)
    {
        if (!$stockOpname->canReopen()) {
            return back()
                ->with('status', 'error')
                ->with('message', 'Dokumen tidak bisa direopen.');
        }

        DB::transaction(function () use ($stockOpname) {
            $stockOpname->status = StockOpname::STATUS_COUNTING;
            $stockOpname->reviewed_by = null;
            $stockOpname->reviewed_at = null;
            $stockOpname->save();
        });

        return back()
            ->with('status', 'success')
            ->with('message', 'Stock Opname dibuka kembali untuk counting.');
    }

    public function cancel(Request $request, StockOpname $stockOpname): RedirectResponse
    {
        // rules aman:
        // - tidak boleh cancel kalau finalized
        if ($stockOpname->status === StockOpname::STATUS_FINALIZED) {
            return back()
                ->with('status', 'error')
                ->with('message', 'SO sudah FINALIZED. Tidak bisa dibatalkan karena stok sudah berubah (ada Adjustment).');
        }

        // tidak perlu cancel kalau sudah cancelled
        if (($stockOpname->status ?? null) === StockOpname::STATUS_CANCELLED) {
            return back()
                ->with('status', 'info')
                ->with('message', 'SO ini sudah dibatalkan sebelumnya.');
        }

        // role: minimal admin/owner (kalau kamu mau operating boleh, tinggal tambah)
        $role = auth()->user()->role ?? null;
        if (!in_array($role, ['admin', 'owner'], true)) {
            return back()
                ->with('status', 'error')
                ->with('message', 'Anda tidak punya akses untuk membatalkan SO.');
        }

        $validated = $request->validate([
            'cancel_reason' => ['nullable', 'string', 'max:255'],
        ]);

        DB::transaction(function () use ($stockOpname, $validated) {
            // extra guard: kalau sudah sempat bikin adjustment (harusnya tidak di flow kamu),
            // tetap jangan dibatalkan
            $adj = \App\Models\InventoryAdjustment::query()
                ->where('source_type', \App\Models\StockOpname::class)
                ->where('source_id', $stockOpname->id)
                ->latest('id')
                ->first();

            if ($adj) {
                throw ValidationException::withMessages([
                    'cancel_reason' => 'SO ini sudah punya Adjustment. Batalkan/void Adjustment dulu, baru bisa batalkan SO.',
                ]);
            }

            $stockOpname->status = StockOpname::STATUS_CANCELLED;
            $stockOpname->cancelled_at = now();
            $stockOpname->cancelled_by = auth()->id();
            $stockOpname->cancel_reason = $validated['cancel_reason'] ?? null;
            $stockOpname->save();
        });

        return redirect()
            ->route('inventory.stock_opnames.show', $stockOpname)
            ->with('status', 'success')
            ->with('message', 'SO berhasil dibatalkan.');
    }

}
