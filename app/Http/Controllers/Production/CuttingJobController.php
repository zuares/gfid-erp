<?php

namespace App\Http\Controllers\Production;

use App\Http\Controllers\Controller;
use App\Models\CuttingJob;
use App\Models\Employee;
use App\Models\Item;
use App\Models\ItemRole;
use App\Models\Lot;
use App\Models\QcResult;
use App\Models\Warehouse;
use App\Models\CuttingJobLot;
use App\Services\Accounting\JournalService;
use App\Services\Inventory\InventoryService;
use App\Services\Inventory\LotCostService;
use App\Services\Production\CuttingService;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

// <-- sesuaikan nama model saldo stok kamu

class CuttingJobController extends Controller
{
    public function __construct(
        protected InventoryService $inventory,
        protected CuttingService $cutting,
        protected JournalService $journal,
        protected LotCostService $lotCost,
    ) {}

    /**
     * List Cutting Job.
     */
    public function index(Request $request)
    {
        $q = CuttingJob::query()
            ->with([
                'warehouse',
                'lot.item',
                'bundles.finishedItem',
                'operator',
            ])
            ->withCount('bundles')
            ->withSum('bundles', 'qty_pcs')
            ->orderByDesc('date')
            ->orderByDesc('id');

        if ($request->filled('status')) {
            $q->where('status', $request->status);
        }

        if ($request->filled('warehouse_id')) {
            $q->where('warehouse_id', $request->warehouse_id);
        }

        $jobs = $q->paginate(20)->withQueryString();
        $warehouses = Warehouse::orderBy('code')->get();

        // KPI counts — selalu dari seluruh data, tanpa filter
        $kpis = CuttingJob::query()
            ->selectRaw('status, count(*) as cnt')
            ->groupBy('status')
            ->pluck('cnt', 'status');

        return view('production.cutting_jobs.index', [
            'jobs'       => $jobs,
            'warehouses' => $warehouses,
            'filters'    => $request->only(['status', 'warehouse_id']),
            'kpis'       => $kpis,
        ]);
    }

    /**
     * Form Cutting Job - versi MEDIUM:
     * - User pilih item kain.
     * - Centang beberapa LOT (multi-LOT) dari gudang RM.
     * - Bundles punya lot_id masing-masing (dropdown hanya LOT yang dicentang).
     */
    public function create(Request $request)
    {
        // 1️⃣ Cari gudang RM (wajib ada, konfigurasi awal di warehouses)
        $rmWarehouseId = Warehouse::where('code', 'RM')->value('id');

        if (!$rmWarehouseId) {
            throw new \RuntimeException('Warehouse RM belum dikonfigurasi di tabel warehouses (code = RM).');
        }

        // 2️⃣ Ambil LOT di gudang RM yang masih ada saldo (> 0)
        //    LOT dengan saldo kurang dari kebutuhan BOM tetap bisa menyebabkan minus di RM
        $lotStocks = $this->inventory->getAvailableLots(
            warehouseId: $rmWarehouseId,
            itemId: null,          // filter per item kain dilakukan di front-end (JS)
            includeZeroBalance: false,
        );
        $lotStocks = $this->onlyMainRawMaterialLots($lotStocks);

        // 3️⃣ Data master item jadi (finished_good) untuk combobox di bundle
        $items = Item::query()
            ->select('id', 'code', 'item_category_id')
            ->where('type', 'finished_good')
            ->with(['category:id,code,name'])
            ->orderBy('code')
            ->get();

        // 4️⃣ Data master operator cutting
        $operators = Employee::query()
            ->select('id', 'code', 'name')
            ->where('role', 'cutting')
            ->orderBy('code')
            ->get();

        // 5️⃣ Warehouse untuk header cutting job
        $warehouses = Warehouse::orderBy('code')->get();

        // 6️⃣ BOM data — untuk estimasi pemakaian kain di frontend & kalkulasi backend
        //    Hanya bahan baku utama (usage_stage=main_material) yang dikirim ke frontend.
        //    Format: { finished_item_id => { fabric_item_id => { qty, scrap_pct } } }
        $bomLines = \App\Models\ItemBomLine::query()
            ->where('usage_stage', \App\Models\ItemBomLine::STAGE_MAIN_MATERIAL)
            ->whereHas('bom', fn($q) => $q->where('active', true))
            ->with('bom:id,item_id')
            ->get();

        $bomData = $bomLines
            ->groupBy(fn($line) => (int) $line->bom->item_id)
            ->map(fn($lines) => $lines->keyBy(fn($l) => (int) $l->material_item_id)
                ->map(fn($l) => ['qty' => (float) $l->qty, 'scrap_pct' => (float) $l->scrap_pct])
            );

        // URL edit BOM per finished item: { finishedItemId => editUrl }
        // URL quick-update line BOM: { finishedItemId => quickUrl }
        $bomEditUrls = $bomLines
            ->unique(fn($l) => (int) $l->bom->item_id)
            ->mapWithKeys(fn($l) => [
                (int) $l->bom->item_id => route('master.item_boms.edit', $l->bom->id),
            ]);

        $bomQuickUrls = $bomLines
            ->unique(fn($l) => (int) $l->bom->item_id)
            ->mapWithKeys(fn($l) => [
                (int) $l->bom->item_id => route('master.item_boms.quick_line', $l->bom->id),
            ]);

        return view('production.cutting_jobs.create', [
            'lotStocks'    => $lotStocks,
            'items'        => $items,
            'operators'    => $operators,
            'warehouses'   => $warehouses,
            'bomData'      => $bomData,
            'bomEditUrls'  => $bomEditUrls,
            'bomQuickUrls' => $bomQuickUrls,
        ]);
    }

    /**
     * Form Edit Cutting Job:
     * (sementara masih versi lama, tapi sudah kompatibel dengan bundles yang punya lot_id)
     */

    public function edit(CuttingJob $cuttingJob)
    {
        $cuttingJob->load([
            'warehouse',
            'bundles.finishedItem.category',
            'bundles.operator',
            'lots.lot.item', // CuttingJobLot -> lot -> item
        ]);

        // 1) Warehouse RM
        $rmWarehouseId = Warehouse::where('code', 'RM')->value('id');
        if (!$rmWarehouseId) {
            throw new \RuntimeException('Warehouse RM belum dikonfigurasi di tabel warehouses (code = RM).');
        }

        // 2) list LOT available (fallback legacy)
        $lotStocks = $this->inventory->getAvailableLots(
            warehouseId: $rmWarehouseId,
            itemId: null
        );
        $lotStocks = $this->onlyMainRawMaterialLots($lotStocks);

        // 3) items FG (buat suggest API, tapi di blade kita pakai item-suggest fetch)
        $items = Item::query()
            ->select('id', 'code', 'name', 'item_category_id')
            ->where('type', 'finished_good')
            ->with(['category:id,code,name'])
            ->orderBy('code')
            ->get();

        // 4) operators
        $operators = Employee::query()
            ->select('id', 'code', 'name')
            ->where('role', 'cutting')
            ->orderBy('code')
            ->get();

        // 5) selected lots: sumber utama CuttingJobLot
        $selectedLotsExisting = $cuttingJob->lots
            ->pluck('lot_id')
            ->filter()
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        // fallback job lama: derive dari bundles
        if (empty($selectedLotsExisting)) {
            $selectedLotsExisting = $cuttingJob->bundles
                ->pluck('lot_id')
                ->filter()
                ->map(fn($id) => (int) $id)
                ->unique()
                ->values()
                ->all();
        }

        // ringkasan LOT terkunci (kalau pivot ada)
        $selectedLotSummaries = $cuttingJob->lots
            ->filter(fn($cjLot) => !empty($cjLot->lot_id))
            ->map(function ($cjLot) {
                return [
                    'lot_id' => (int) $cjLot->lot_id,
                    'code' => $cjLot->lot?->code ?? ('LOT#' . $cjLot->lot_id),
                    'item_code' => $cjLot->lot?->item?->code ?? '-',
                    'item_name' => $cjLot->lot?->item?->name ?? '-',
                    'planned' => (float) $cjLot->planned_fabric_qty,
                    'used' => (float) $cjLot->effective_used_qty, // accessor kamu
                ];
            })
            ->values()
            ->all();

        // rows bundles (safe)
        $oldBundles = old('bundles');
        if ($oldBundles) {
            $rows = $oldBundles;
        } else {
            $rows = $cuttingJob->bundles->map(function ($b) {
                $fi = $b->finishedItem;
                return [
                    'id' => $b->id,
                    'bundle_no' => $b->bundle_no,
                    'lot_id' => $b->lot_id,
                    'finished_item_id' => $b->finished_item_id,
                    'finished_item_code' => $fi?->code,
                    'finished_item_name' => $fi?->name,
                    'item_category_id' => $fi?->item_category_id,
                    'qty_pcs' => (int) ($b->qty_pcs ?? 0),
                    'qty_used_fabric' => (float) ($b->qty_used_fabric ?? 0),
                    'notes' => $b->notes ?? '',
                ];
            })->values()->all();

            if (empty($rows)) {
                $rows[] = [
                    'id' => null,
                    'bundle_no' => 1,
                    'lot_id' => null,
                    'finished_item_id' => null,
                    'finished_item_code' => null,
                    'finished_item_name' => null,
                    'item_category_id' => null,
                    'qty_pcs' => null,
                    'qty_used_fabric' => 0,
                    'notes' => '',
                ];
            }
        }

        $lotBalance = (float) $cuttingJob->bundles->sum('qty_used_fabric');

        return view('production.cutting_jobs.edit', [
            'job' => $cuttingJob,
            'lotStocks' => $lotStocks,
            'items' => $items,
            'operators' => $operators,

            'rows' => $rows,
            'selectedLotsExisting' => $selectedLotsExisting,
            'selectedLotSummaries' => $selectedLotSummaries,

            'lotBalance' => $lotBalance,
        ]);
    }

    /**
     * Simpan Cutting Job + bundles (versi medium, multi-LOT).
     *
     * - LOT di level header (lot_id) dibuat optional (bisa diisi LOT pertama).
     * - LOT utama untuk stok ada di cutting_job_bundles.lot_id.
     */
    // public function store(Request $request)
    // {
    //     $validated = $request->validate([
    //         'date' => ['required', 'date'],
    //         'warehouse_id' => ['required', 'exists:warehouses,id'],
    //         'lot_id' => ['nullable', 'integer', 'exists:lots,id'], // header lot opsional
    //         'fabric_item_id' => ['required', 'integer', 'exists:items,id'],

    //         'operator_id' => ['required', 'exists:employees,id'],
    //         'notes' => ['nullable', 'string'],

    //         // LOT yang dipakai (sudah di-hidden dari _form multi-LOT)
    //         'selected_lots' => ['required', 'array', 'min:1'],
    //         'selected_lots.*' => ['integer', 'exists:lots,id'],

    //         // Bundles
    //         'bundles' => ['required', 'array', 'min:1'],
    //         'bundles.*.id' => ['nullable', 'integer'],
    //         'bundles.*.bundle_no' => ['nullable', 'integer'],
    //         'bundles.*.finished_item_id' => ['required', 'exists:items,id'],
    //         'bundles.*.item_category_id' => ['nullable', 'integer', 'exists:item_categories,id'],
    //         'bundles.*.qty_pcs' => ['required', 'numeric', 'min:0.01'],
    //         'bundles.*.qty_used_fabric' => ['nullable', 'numeric', 'min:0'],
    //         'bundles.*.notes' => ['nullable', 'string'],
    //     ], [
    //         'fabric_item_id.required' => 'Item kain wajib dipilih.',
    //         'operator_id.required' => 'Operator cutting wajib dipilih.',
    //         'selected_lots.required' => 'Minimal satu LOT harus dipilih.',
    //         'bundles.*.finished_item_id.required' => 'Item jadi pada setiap baris wajib diisi.',
    //         'bundles.*.qty_pcs.required' => 'Qty pcs pada setiap baris wajib diisi.',
    //     ]);

    //     // ================
    //     // 1) LOT TERPILIH
    //     // ================
    //     $selectedLotIds = collect($validated['selected_lots'] ?? [])
    //         ->map(fn($id) => (int) $id)
    //         ->values()
    //         ->all();

    //     if (empty($selectedLotIds)) {
    //         return back()
    //             ->withErrors(['selected_lots' => 'Minimal satu LOT harus dipilih.'])
    //             ->withInput();
    //     }

    //     $warehouseId = (int) $validated['warehouse_id'];
    //     $fabricItemId = (int) $validated['fabric_item_id'];

    //     // ==========================================
    //     // 2) HITUNG SALDO PER LOT + TOTAL SALDO
    //     // ==========================================
    //     $lotBalances = [];
    //     $totalLotBalance = 0.0;

    //     foreach ($selectedLotIds as $lotId) {
    //         // pakai InventoryService supaya ngikut semua mutasi (GRN, cutting, dsb)
    //         $saldo = (float) $this->inventory->getLotBalance(
    //             warehouseId: $warehouseId,
    //             itemId: $fabricItemId,
    //             lotId: $lotId,
    //         );

    //         // jaga-jaga kalau minus → anggap 0
    //         if ($saldo < 0) {
    //             $saldo = 0.0;
    //         }

    //         $lotBalances[$lotId] = $saldo;
    //         $totalLotBalance += $saldo;
    //     }

    //     if ($totalLotBalance <= 0.000001) {
    //         return back()
    //             ->withErrors(['selected_lots' => 'Saldo kain di LOT yang dipilih sudah habis / 0.'])
    //             ->withInput();
    //     }

    //     // LOT utama (untuk header) → pilih LOT pertama yang masih ada saldo, kalau nggak ada ya pakai index 0
    //     $primaryLotId = collect($selectedLotIds)
    //         ->first(fn($id) => ($lotBalances[$id] ?? 0) > 0) ?? $selectedLotIds[0];

    //     // =========================
    //     // 3) FILTER BUNDLE VALID
    //     // =========================
    //     $bundles = $validated['bundles'] ?? [];
    //     $validBundles = [];

    //     foreach ($bundles as $row) {
    //         $qty = (float) ($row['qty_pcs'] ?? 0);

    //         if (!empty($row['finished_item_id']) && $qty > 0) {
    //             // Versi "medium" dulu:
    //             // - semua bundle diarahkan ke LOT utama (supaya compatible dengan sistem sekarang)
    //             // - nanti kalau mau advanced multi-LOT per bundle, tinggal ganti di sini
    //             $row['lot_id'] = $primaryLotId;

    //             $validBundles[] = $row;
    //         }
    //     }

    //     if (count($validBundles) === 0) {
    //         return back()
    //             ->withErrors(['bundles' => 'Minimal 1 baris bundle harus diisi dengan item & qty pcs > 0.'])
    //             ->withInput();
    //     }

    //     // =========================
    //     // 4) HEADER lot_id & qty_used_fabric
    //     // =========================

    //     // Kalau header lot_id kosong → pakai LOT utama
    //     if (empty($validated['lot_id']) && $primaryLotId) {
    //         $validated['lot_id'] = $primaryLotId;
    //     }

    //     // Hitung qty_used_fabric per baris (estimasi, TOTAL kain dibagi jumlah bundle valid)
    //     $countValid = count($validBundles);
    //     $perRow = ($countValid > 0 && $totalLotBalance > 0)
    //     ? round($totalLotBalance / $countValid, 2)
    //     : 0.0;

    //     foreach ($validBundles as $i => $row) {
    //         $qty = (float) ($row['qty_pcs'] ?? 0);

    //         if (!empty($row['finished_item_id']) && $qty > 0 && $perRow > 0) {
    //             $validBundles[$i]['qty_used_fabric'] = $perRow;
    //         } else {
    //             $validBundles[$i]['qty_used_fabric'] = 0;
    //         }
    //     }

    //     $validated['bundles'] = $validBundles;

    //     // selected_lots tidak dipakai di CuttingService
    //     unset($validated['selected_lots']);

    //     // =========================
    //     // 5) CREATE JOB
    //     // =========================
    //     $job = $this->cutting->create($validated);

    //     // =========================
    //     // 6) SIMPAN PIVOT LOTS
    //     // =========================
    //     foreach ($selectedLotIds as $lotId) {
    //         $saldoLot = $lotBalances[$lotId] ?? 0.0;

    //         // kalau saldo 0, skip saja
    //         if ($saldoLot <= 0.000001) {
    //             continue;
    //         }

    //         CuttingJobLot::create([
    //             'cutting_job_id' => $job->id,
    //             'lot_id' => $lotId,
    //             // 🔥 sekarang planned_fabric_qty = SALDO REAL per LOT,
    //             // bukan rata-rata / dibagi sama rata.
    //             'planned_fabric_qty' => $saldoLot,
    //         ]);
    //     }

    //     return redirect()
    //         ->route('production.cutting_jobs.show', $job)
    //         ->with('success', 'Cutting job berhasil dibuat.');
    // }

    public function store(Request $request)
    {
        $this->resolveTypedFinishedItems($request);

        $validated = $request->validate([
            'date' => ['required', 'date'],
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'lot_id' => ['nullable', 'integer', 'exists:lots,id'], // header lot opsional
            'fabric_item_id' => ['required', 'integer', 'exists:items,id'],

            'operator_id' => ['required', 'exists:employees,id'],
            'notes' => ['nullable', 'string'],

            // LOT yang dipakai (hasil centang di kartu LOT)
            'selected_lots' => ['required', 'array', 'min:1'],
            'selected_lots.*' => ['integer', 'exists:lots,id'],

            // Bundles
            'bundles' => ['required', 'array', 'min:1'],
            'bundles.*.id' => ['nullable', 'integer'],
            'bundles.*.bundle_no' => ['nullable', 'integer'],
            // ⬇️ sekarang bundle WAJIB punya lot_id
            'bundles.*.lot_id' => ['required', 'integer', 'exists:lots,id'],
            'bundles.*.finished_item_id' => ['required', 'exists:items,id'],
            'bundles.*.item_category_id' => ['nullable', 'integer', 'exists:item_categories,id'],
            'bundles.*.qty_pcs' => ['required', 'numeric', 'min:0.01'],
            'bundles.*.qty_used_fabric' => ['nullable', 'numeric', 'min:0'],
            'bundles.*.notes' => ['nullable', 'string'],
        ], [
            'fabric_item_id.required' => 'Item kain wajib dipilih.',
            'operator_id.required' => 'Operator cutting wajib dipilih.',
            'selected_lots.required' => 'Minimal satu LOT harus dipilih.',
            'bundles.*.finished_item_id.required' => 'Item jadi pada setiap baris wajib diisi.',
            'bundles.*.qty_pcs.required' => 'Qty pcs pada setiap baris wajib diisi.',
            'bundles.*.lot_id.required' => 'LOT pada setiap baris bundle wajib dipilih.',
        ]);

        // ================
        // 1) LOT TERPILIH
        // ================
        $selectedLotIds = collect($validated['selected_lots'] ?? [])
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        if (empty($selectedLotIds)) {
            return back()
                ->withErrors(['selected_lots' => 'Minimal satu LOT harus dipilih.'])
                ->withInput();
        }

        $warehouseId = (int) $validated['warehouse_id'];
        $fabricItemId = (int) $validated['fabric_item_id'];

        // ====================================================
        // 1.a SAFETY: semua LOT harus item kain yang sama
        //      dan harus sama dengan fabric_item_id
        // ====================================================
        $lotItems = \App\Models\Lot::query()
            ->whereIn('id', $selectedLotIds)
            ->pluck('item_id', 'id'); // [lot_id => item_id]

        if ($lotItems->isEmpty()) {
            return back()
                ->withErrors(['selected_lots' => 'Data LOT tidak ditemukan.'])
                ->withInput();
        }

        $uniqueItemIds = $lotItems->unique()->values();

        if ($uniqueItemIds->count() !== 1 || (int) $uniqueItemIds->first() !== $fabricItemId) {
            return back()
                ->withErrors([
                    'selected_lots' => 'Semua LOT yang dipilih harus untuk item kain yang sama dengan Item Kain header.',
                ])
                ->withInput();
        }

        // ==========================================
        // 2) HITUNG SALDO PER LOT (info saja, tidak memblok jika 0)
        // ==========================================
        $lotBalances = [];
        $totalLotBalance = 0.0;

        foreach ($selectedLotIds as $lotId) {
            $saldo = (float) $this->inventory->getLotBalance(
                warehouseId: $warehouseId,
                itemId: $fabricItemId,
                lotId: $lotId,
            );
            // Simpan saldo asli (bisa 0 atau negatif — tidak diblok)
            $lotBalances[$lotId] = max($saldo, 0.0);
            $totalLotBalance += max($saldo, 0.0);
        }

        // LOT utama → preferensi yang punya saldo; fallback ke LOT pertama
        $primaryLotId = collect($selectedLotIds)
            ->first(fn($id) => ($lotBalances[$id] ?? 0) > 0) ?? $selectedLotIds[0];

        // =========================
        // 3) FILTER BUNDLE VALID
        // =========================
        $bundles = $validated['bundles'] ?? [];
        $validBundles = [];
        $bundlesIndexByLot = []; // [lot_id => [index, ...]]

        foreach ($bundles as $row) {
            $qty = (float) ($row['qty_pcs'] ?? 0);
            $lotId = !empty($row['lot_id']) ? (int) $row['lot_id'] : 0;

            if (empty($row['finished_item_id']) || $qty <= 0 || !$lotId) {
                continue;
            }

            if (!in_array($lotId, $selectedLotIds, true)) {
                return back()
                    ->withErrors(['bundles' => 'LOT pada baris bundle harus termasuk LOT yang dipilih di atas.'])
                    ->withInput();
            }

            $idx = count($validBundles);
            $validBundles[] = $row;
            $bundlesIndexByLot[$lotId] = $bundlesIndexByLot[$lotId] ?? [];
            $bundlesIndexByLot[$lotId][] = $idx;
        }

        if (count($validBundles) === 0) {
            return back()
                ->withErrors(['bundles' => 'Minimal 1 baris bundle harus diisi dengan item, LOT & qty pcs > 0.'])
                ->withInput();
        }

        // =========================
        // 4) HEADER lot_id
        // =========================
        if (empty($validated['lot_id']) && $primaryLotId) {
            $validated['lot_id'] = $primaryLotId;
        }

        // =======================================================
        // 5) HITUNG qty_used_fabric — PAKAI BOM jika tersedia,
        //    fallback ke distribusi saldo LOT jika tidak ada BOM.
        //    LOT dengan saldo 0 tetap diproses (boleh minus di RM).
        // =======================================================

        // Load active BOM lines untuk fabric item ini — hanya main_material, keyed by finished_item_id
        $bomLines = \App\Models\ItemBomLine::query()
            ->where('material_item_id', $fabricItemId)
            ->where('usage_stage', \App\Models\ItemBomLine::STAGE_MAIN_MATERIAL)
            ->whereHas('bom', fn($q) => $q->where('active', true))
            ->with('bom:id,item_id')
            ->get()
            ->keyBy(fn($line) => (int) $line->bom->item_id);

        foreach ($bundlesIndexByLot as $lotId => $indexes) {
            $saldoLot = $lotBalances[$lotId] ?? 0.0;
            $countInLot = count($indexes);
            if ($countInLot <= 0) {
                continue;
            }

            // Hitung qty_used_fabric per baris
            // Prioritas: (1) user submit manual → (2) hitung dari BOM → (3) fallback saldo LOT
            $anyBom = false;
            foreach ($indexes as $idx) {
                $finishedItemId = (int) ($validBundles[$idx]['finished_item_id'] ?? 0);
                $qtyPcs         = (float) ($validBundles[$idx]['qty_pcs'] ?? 0);

                // Prioritas 1: user sudah isi qty_used_fabric manual di form
                $userFabric = (float) ($validBundles[$idx]['qty_used_fabric'] ?? 0);
                if ($userFabric > 0) {
                    $anyBom = true; // ada nilai → skip fallback LOT
                    continue;       // nilai sudah ada di $validBundles[$idx], tidak perlu overwrite
                }

                // Prioritas 2: hitung dari BOM main_material
                $bomLine = $bomLines[$finishedItemId] ?? null;
                if ($bomLine && $qtyPcs > 0) {
                    $bomQty   = (float) $bomLine->qty;
                    $scrapPct = (float) $bomLine->scrap_pct;
                    $validBundles[$idx]['qty_used_fabric'] = round(
                        $qtyPcs * $bomQty * (1 + $scrapPct / 100),
                        4
                    );
                    $anyBom = true;
                }
            }

            if (!$anyBom) {
                // Fallback: distribusi saldo LOT secara merata
                // Jika saldo 0 → qty_used_fabric juga 0 (tidak ada deduction)
                $perRow = $saldoLot > 0 ? round($saldoLot / $countInLot, 2) : 0.0;
                $usedSoFar = 0.0;

                foreach ($indexes as $i => $idx) {
                    if ($i === $countInLot - 1) {
                        $validBundles[$idx]['qty_used_fabric'] = max($saldoLot - $usedSoFar, 0);
                    } else {
                        $validBundles[$idx]['qty_used_fabric'] = $perRow;
                        $usedSoFar += $perRow;
                    }
                }
            }
        }

        $validated['bundles'] = $validBundles;
        unset($validated['selected_lots']);

        // Hitung total planned fabric per LOT dari bundle yang sudah dihitung
        $fabricByLot = [];
        foreach ($validBundles as $b) {
            $lotId = (int) ($b['lot_id'] ?? 0);
            $fabricByLot[$lotId] = ($fabricByLot[$lotId] ?? 0.0) + (float) ($b['qty_used_fabric'] ?? 0);
        }

        $devRollback = $request->boolean('dev_rollback') && !app()->isProduction();
        if ($devRollback) {
            DB::beginTransaction();
        }

        // =========================
        // 6) CREATE JOB
        // =========================
        try {
            $job = $this->cutting->create($validated);

            // =========================
            // 7) SIMPAN PIVOT LOTS
            //    Semua LOT terpilih disimpan, termasuk yang saldo 0
            // =========================
            foreach ($selectedLotIds as $lotId) {
                \App\Models\CuttingJobLot::create([
                    'cutting_job_id'    => $job->id,
                    'lot_id'            => $lotId,
                    // planned_fabric_qty = total BOM/saldo yang direncanakan dari LOT ini
                    'planned_fabric_qty' => $fabricByLot[$lotId] ?? 0.0,
                ]);
            }
        } catch (\RuntimeException $e) {
            if ($devRollback && DB::transactionLevel() > 0) {
                DB::rollBack();
            }

            return back()
                ->withErrors(['selected_lots' => $this->humanizeStockError($e->getMessage())])
                ->withInput();
        } catch (\Throwable $e) {
            if ($devRollback && DB::transactionLevel() > 0) {
                DB::rollBack();
            }

            throw $e;
        }

        if ($devRollback) {
            $summary = [
                'code' => $job->code,
                'bundle_count' => $job->bundles()->count(),
                'qty_pcs' => (float) $job->bundles()->sum('qty_pcs'),
                'used_fabric' => (float) $job->bundles()->sum('qty_used_fabric'),
                'lot_count' => count($selectedLotIds),
            ];

            DB::rollBack();

            return back()
                ->withInput()
                ->with('dev_rollback_result', $summary)
                ->with('success', 'Mode Developer: simulasi cutting berhasil dan sudah di-rollback. Tidak ada data/stok yang berubah.');
        }

        try {
            $this->journal->postCuttingJob($job);
        } catch (\Throwable $e) {
            Log::warning('Gagal membuat jurnal cutting_job', [
                'cutting_job_id' => $job->id,
                'message' => $e->getMessage(),
            ]);
        }

        return redirect()
            ->route('production.cutting_jobs.show', $job)
            ->with('success', 'Cutting job berhasil dibuat.');
    }

    /**
     * Update Cutting Job + bundles (manual per LOT).
     */
    public function update(Request $request, CuttingJob $cuttingJob)
    {
        $this->resolveTypedFinishedItems($request);

        $validated = $request->validate([
            'date' => ['required', 'date'],
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'lot_id' => ['nullable', 'integer', 'exists:lots,id'],
            // ✅ jangan required di edit: akan kita set dari LOT
            'fabric_item_id' => ['nullable', 'integer'],

            'operator_id' => ['required', 'exists:employees,id'],
            'notes' => ['nullable', 'string'],

            // ✅ wajib ada selected_lots[] (di blade sudah kita hidden)
            'selected_lots' => ['required', 'array', 'min:1'],
            'selected_lots.*' => ['integer', 'exists:lots,id'],

            'bundles' => ['required', 'array', 'min:1'],
            'bundles.*.id' => ['nullable', 'integer'],
            'bundles.*.bundle_no' => ['nullable', 'integer'],
            'bundles.*.lot_id' => ['required', 'integer', 'exists:lots,id'],
            'bundles.*.finished_item_id' => ['required', 'exists:items,id'],
            'bundles.*.item_category_id' => ['nullable', 'integer', 'exists:item_categories,id'],
            'bundles.*.qty_pcs' => ['required', 'numeric', 'min:0.01'],
            // ✅ boleh nullable karena akan dihitung ulang planned/lot dibagi baris
            'bundles.*.qty_used_fabric' => ['nullable', 'numeric', 'min:0'],
            'bundles.*.notes' => ['nullable', 'string'],
        ]);

        $selectedLotIds = collect($validated['selected_lots'])
            ->map(fn($id) => (int) $id)->unique()->values()->all();

        if (empty($selectedLotIds)) {
            return back()->withErrors(['selected_lots' => 'Minimal satu LOT harus dipilih.'])->withInput();
        }

        // ✅ fabric_item_id ambil dari LOT terpilih (bukan dari dropdown RM)
        $lotItems = \App\Models\Lot::query()
            ->whereIn('id', $selectedLotIds)
            ->pluck('item_id', 'id'); // [lot_id => item_id]

        if ($lotItems->isEmpty()) {
            return back()->withErrors(['selected_lots' => 'Data LOT tidak ditemukan.'])->withInput();
        }

        $uniqueItemIds = $lotItems->unique()->values();
        if ($uniqueItemIds->count() !== 1) {
            return back()->withErrors(['selected_lots' => 'Semua LOT yang dipilih harus dari item kain yang sama.'])->withInput();
        }

        $fabricItemId = (int) $uniqueItemIds->first();
        $validated['fabric_item_id'] = $fabricItemId;

        // valid bundles + pastikan lot_id bundle termasuk selected
        $bundles = $validated['bundles'] ?? [];
        $validBundles = [];
        $bundlesIndexByLot = [];

        foreach ($bundles as $row) {
            $qty = (float) ($row['qty_pcs'] ?? 0);
            $lotId = (int) ($row['lot_id'] ?? 0);

            if (empty($row['finished_item_id']) || $qty <= 0 || !$lotId) {
                continue;
            }

            if (!in_array($lotId, $selectedLotIds, true)) {
                return back()->withErrors(['bundles' => 'LOT pada baris bundle harus termasuk LOT yang dipilih di atas.'])->withInput();
            }

            $idx = count($validBundles);
            $validBundles[] = $row;
            $bundlesIndexByLot[$lotId] = $bundlesIndexByLot[$lotId] ?? [];
            $bundlesIndexByLot[$lotId][] = $idx;
        }

        if (count($validBundles) === 0) {
            return back()->withErrors(['bundles' => 'Minimal 1 baris bundle harus diisi dengan item, LOT & qty pcs > 0.'])->withInput();
        }

        // isi header lot_id kalau kosong
        if (empty($validated['lot_id'])) {
            $validated['lot_id'] = (int) ($validBundles[0]['lot_id'] ?? $selectedLotIds[0]);
        }

        // ✅ sumber planned per LOT:
        // - kalau ada pivot CuttingJobLot: pakai planned_fabric_qty
        // - kalau tidak ada: fallback pakai balance RM (optional, tapi di edit biasanya pivot sudah ada)
        $cuttingJob->loadMissing(['lots']); // relation CuttingJobLot
        $plannedMap = [];

        if ($cuttingJob->lots && $cuttingJob->lots->count()) {
            foreach ($cuttingJob->lots as $cjLot) {
                $plannedMap[(int) $cjLot->lot_id] = (float) $cjLot->planned_fabric_qty;
            }
        } else {
            // fallback: pakai saldo sekarang (kalau kamu mau)
            $warehouseId = (int) $validated['warehouse_id'];
            foreach ($selectedLotIds as $lotId) {
                $plannedMap[$lotId] = (float) $this->inventory->getLotBalance(
                    warehouseId: $warehouseId,
                    itemId: $fabricItemId,
                    lotId: $lotId
                );
            }
        }

        // ✅ HITUNG qty_used_fabric = planned per LOT dibagi baris valid per LOT (last row remainder)
        foreach ($bundlesIndexByLot as $lotId => $indexes) {
            $planned = (float) ($plannedMap[$lotId] ?? 0);

            if ($planned <= 0.000001) {
                return back()->withErrors(['bundles' => "Planned kain untuk LOT {$lotId} = 0. Cek CuttingJobLot / planned_fabric_qty."])->withInput();
            }

            $countInLot = count($indexes);
            $perRow = round($planned / $countInLot, 2);
            $usedSoFar = 0.0;

            foreach ($indexes as $i => $idx) {
                if ($i === $countInLot - 1) {
                    $validBundles[$idx]['qty_used_fabric'] = max($planned - $usedSoFar, 0);
                } else {
                    $validBundles[$idx]['qty_used_fabric'] = $perRow;
                    $usedSoFar += $perRow;
                }
            }
        }

        $validated['bundles'] = $validBundles;

        // selected_lots tidak dipakai cutting service
        unset($validated['selected_lots']);

        $job = $this->cutting->update($validated, $cuttingJob);

        return redirect()
            ->route('production.cutting_jobs.show', $job)
            ->with('success', 'Cutting job berhasil diupdate.');
    }

    private function resolveTypedFinishedItems(Request $request): void
    {
        $bundles = $request->input('bundles', []);
        if (!is_array($bundles) || empty($bundles)) {
            return;
        }

        foreach ($bundles as $idx => $row) {
            if (!empty($row['finished_item_id'])) {
                continue;
            }

            $display = trim((string) ($row['finished_item_display'] ?? ''));
            if ($display === '') {
                continue;
            }

            $code = trim(str_replace('–', '—', $display));
            $code = trim(explode('—', $code)[0] ?? $code);
            $code = trim(preg_split('/\s+/', $code)[0] ?? $code);
            $code = strtoupper($code);

            if ($code === '') {
                continue;
            }

            $item = Item::query()
                ->where('type', 'finished_good')
                ->whereRaw('UPPER(code) = ?', [$code])
                ->first();

            if (!$item) {
                continue;
            }

            $bundles[$idx]['finished_item_id'] = $item->id;
            if (empty($bundles[$idx]['item_category_id'])) {
                $bundles[$idx]['item_category_id'] = $item->item_category_id;
            }
        }

        $request->merge(['bundles' => $bundles]);
    }

    private function onlyMainRawMaterialLots($lotStocks)
    {
        return $lotStocks
            ->filter(function ($row) {
                $item = $row->lot?->item;

                return $item && $item->role_code === ItemRole::RM;
            })
            ->values();
    }

    private function capLotBalancesToOnHand(array $lotBalances, float $onHandQty): array
    {
        $total = array_sum($lotBalances);
        if ($total <= 0 || $onHandQty <= 0 || $total <= $onHandQty) {
            return $lotBalances;
        }

        $capped = [];
        $used = 0.0;
        $keys = array_keys($lotBalances);
        $lastKey = end($keys);

        foreach ($lotBalances as $lotId => $balance) {
            if ($lotId === $lastKey) {
                $capped[$lotId] = max(round($onHandQty - $used, 4), 0);
                continue;
            }

            $qty = round(((float) $balance / $total) * $onHandQty, 4);
            $capped[$lotId] = max($qty, 0);
            $used += $qty;
        }

        return $capped;
    }

    private function humanizeStockError(string $message): string
    {
        if (preg_match('/Stok tidak mencukupi untuk item\s+(\d+)\s+di gudang\s+(\d+)\.\s*Stok:\s*([0-9\.,]+),\s*mau keluar:\s*([0-9\.,]+)/i', $message, $m)) {
            return "Stok kain tidak cukup. Stok tersedia {$m[3]}, tetapi sistem mencoba memakai {$m[4]}. Refresh halaman lalu pilih LOT ulang.";
        }

        return 'Stok kain tidak cukup: ' . $message;
    }

    /**
     * Detail satu Cutting Job.
     */
    public function show(CuttingJob $cuttingJob)
    {
        $cuttingJob->load([
            'warehouse',
            'lot.item',
            'lots.lot.item',
            'bundles.finishedItem',
            'bundles.operator',
            'bundles.qcResults' => function ($q) {
                $q->where('stage', QcResult::STAGE_CUTTING);
            },
        ]);

        $hasQcCutting = $cuttingJob->bundles()
            ->whereHas('qcResults', function ($q) {
                $q->where('stage', QcResult::STAGE_CUTTING);
            })
            ->exists();

        return view('production.cutting_jobs.show', [
            'job' => $cuttingJob,
            'hasQcCutting' => $hasQcCutting,
        ]);
    }

    /**
     * Void / rollback Cutting Job:
     * - Hanya owner
     * - Hanya status draft/cut (belum QC)
     * - Block jika ada sewing pickup atau WIP sudah diposting
     * - Reverse mutasi kain ke LOT semula
     * - Set status job & bundles → voided
     */
    public function void(Request $request, CuttingJob $cuttingJob)
    {
        // 1) Hanya owner
        if ((auth()->user()->role ?? null) !== 'owner') {
            return back()->with('error', 'Hanya Owner yang bisa melakukan void Cutting Job.');
        }

        // 2) Hanya boleh sebelum QC diinput (cut_sent_to_qc tetap ok selama belum ada QC result)
        $voidableStatuses = ['draft', 'cut', 'cut_sent_to_qc', 'sent_to_qc'];
        if (! in_array($cuttingJob->status, $voidableStatuses, true)) {
            return back()->with('error',
                'Cutting Job tidak bisa di-void. Status saat ini: ' . strtoupper($cuttingJob->status) . '. Void hanya bisa dilakukan sebelum QC diinput.'
            );
        }

        // 3) Block jika QC sudah diposting (wip_posted_at terisi)
        $cuttingJob->loadMissing('bundles');

        $hasWipPosted = $cuttingJob->bundles->contains(fn ($b) => ! empty($b->wip_posted_at));
        if ($hasWipPosted) {
            return back()->with('error',
                'Cutting Job tidak bisa di-void karena WIP sudah diposting ke gudang. Gunakan fitur Batalkan QC terlebih dahulu.'
            );
        }

        // 4) Block jika ada sewing pickup
        $hasSewingPickup = $cuttingJob->bundles->contains(fn ($b) => ((float) ($b->sewing_picked_qty ?? 0)) > 0);
        if ($hasSewingPickup) {
            return back()->with('error',
                'Cutting Job tidak bisa di-void karena sebagian bundle sudah diambil untuk jahit (Sewing Pickup).'
            );
        }

        DB::transaction(function () use ($cuttingJob) {
            // 5) Reverse mutasi kain — kembalikan saldo gudang (inventory_stocks)
            //    reverseBySource pakai affectLotCost=false, jadi lots.qty_onhand diurus terpisah di bawah.
            $outMutations = \App\Models\InventoryMutation::query()
                ->where('source_type', 'cutting_job')
                ->where('source_id', $cuttingJob->id)
                ->where('direction', 'out')
                ->whereNotNull('lot_id')
                ->get();

            $hasMutasi = $outMutations->isNotEmpty()
                || \App\Models\InventoryMutation::query()
                    ->where('source_type', 'cutting_job')
                    ->where('source_id', $cuttingJob->id)
                    ->exists();

            if ($hasMutasi) {
                $this->inventory->reverseBySource(
                    originalSourceTypes: ['cutting_job'],
                    originalSourceId:    $cuttingJob->id,
                    voidSourceType:      'cutting_job_void',
                    voidSourceId:        $cuttingJob->id,
                    notesPrefix:         "VOID {$cuttingJob->code}",
                    date:                now(),
                );
            }

            // 5b) Restore lots.qty_onhand per LOT yang terlibat
            //     (reverseBySource tidak update LOT karena affectLotCost=false)
            if ($outMutations->isNotEmpty()) {
                $lotRestore = [];
                foreach ($outMutations as $m) {
                    $lotId = (int) $m->lot_id;
                    $lotRestore[$lotId] = ($lotRestore[$lotId] ?? 0.0) + abs((float) $m->qty_change);
                }

                foreach ($lotRestore as $lotId => $restoreQty) {
                    $lot = \App\Models\Lot::lockForUpdate()->find($lotId);
                    if (! $lot || $restoreQty <= 0) {
                        continue;
                    }

                    $lot->qty_onhand = round((float) $lot->qty_onhand + $restoreQty, 4);
                    $lot->total_cost = round($lot->qty_onhand * (float) $lot->avg_cost, 4);

                    // Buka kembali LOT jika sempat tertutup
                    if ($lot->qty_onhand > 0 && $lot->status === 'closed') {
                        $lot->status = 'open';
                    }

                    $lot->save();
                }
            }

            // 6) Set status bundles → voided
            $cuttingJob->bundles()->update(['status' => 'voided']);

            // 7) Set status job → voided
            $cuttingJob->update(['status' => 'voided']);
        });

        try {
            $this->journal->voidBySource(JournalService::SRC_CUTTING_JOB, (int) $cuttingJob->id, "VOID Cutting Job {$cuttingJob->code}");
        } catch (\Throwable $e) {
            Log::warning('Gagal void jurnal cutting_job', [
                'cutting_job_id' => $cuttingJob->id,
                'message' => $e->getMessage(),
            ]);
        }

        return redirect()
            ->route('production.cutting_jobs.show', $cuttingJob)
            ->with('success', "Cutting Job {$cuttingJob->code} berhasil di-void. Stok kain sudah dikembalikan ke LOT.");
    }

    public function sendToQc(CuttingJob $cuttingJob)
    {
        $hasQcCutting = $cuttingJob->bundles()
            ->whereHas('qcResults', function ($q) {
                $q->where('stage', 'cutting');
            })
            ->exists();

        if (!$hasQcCutting) {
            $cuttingJob->update([
                'status' => 'sent_to_qc',
            ]);
        }

        return redirect()
            ->back()
            ->with('success', 'Cutting job dikirim ke QC Cutting.');
    }


    /**
     * Sinkron actual used fabric lot dari total bundle.
     *
     * Aturan:
     * cutting_job_lots.used_fabric_qty =
     * SUM(cutting_job_bundles.qty_used_fabric)
     * per cutting_job_id + lot_id.
     */
    private function syncCuttingJobLotUsedFabric($cuttingJob): void
    {
        $cuttingJobId = is_object($cuttingJob) ? (int) $cuttingJob->id : (int) $cuttingJob;

        if ($cuttingJobId <= 0) {
            return;
        }

        $rows = DB::table('cutting_job_lots as l')
            ->leftJoin('cutting_job_bundles as b', function ($join) {
                $join->on('b.cutting_job_id', '=', 'l.cutting_job_id')
                    ->on('b.lot_id', '=', 'l.lot_id');
            })
            ->where('l.cutting_job_id', $cuttingJobId)
            ->select(
                'l.id',
                DB::raw('COALESCE(SUM(COALESCE(b.qty_used_fabric,0)),0) as bundle_used_fabric')
            )
            ->groupBy('l.id')
            ->get();

        foreach ($rows as $row) {
            DB::table('cutting_job_lots')
                ->where('id', $row->id)
                ->update([
                    'used_fabric_qty' => (float) $row->bundle_used_fabric,
                    'updated_at' => now(),
                ]);
        }
    }

    /**
     * Catat sisa kain fisik setelah cutting dan kembalikan ke RM.
     * Tiap LOT bisa punya qty_sisa_fabric masing-masing.
     */
    public function recordSisaFabric(Request $request, CuttingJob $cuttingJob)
    {
        $validated = $request->validate([
            'lots'              => ['required', 'array', 'min:1'],
            'lots.*.lot_id'     => ['required', 'integer', 'exists:lots,id'],
            'lots.*.qty_sisa'   => ['required', 'numeric', 'min:0'],
        ]);

        $rmWarehouseId = Warehouse::where('code', 'RM')->value('id');
        if (!$rmWarehouseId) {
            throw ValidationException::withMessages(['lots' => 'Gudang RM belum dikonfigurasi.']);
        }

        $epsilon = 0.0001;
        $processed = 0;

        DB::transaction(function () use ($validated, $cuttingJob, $rmWarehouseId, $epsilon, &$processed) {

            foreach ($validated['lots'] as $row) {
                $lotId   = (int) $row['lot_id'];
                $qtySisa = (float) $row['qty_sisa'];

                if ($qtySisa <= $epsilon) {
                    continue;
                }

                // Cari pivot cutting_job_lots
                $cjLot = CuttingJobLot::where('cutting_job_id', $cuttingJob->id)
                    ->where('lot_id', $lotId)
                    ->lockForUpdate()
                    ->first();

                if (!$cjLot) {
                    throw ValidationException::withMessages([
                        'lots' => "LOT #{$lotId} tidak terdaftar di cutting job ini.",
                    ]);
                }

                if ($cjLot->sisa_recorded_at) {
                    throw ValidationException::withMessages([
                        'lots' => "Sisa bahan LOT #{$lotId} sudah pernah dicatat. Tidak bisa dicatat ulang.",
                    ]);
                }

                // Ambil avg_cost LOT untuk stockIn
                $lot     = Lot::lockForUpdate()->findOrFail($lotId);
                $avgCost = (float) ($lot->avg_cost ?? 0);

                // 1. StockIn ke RM
                $this->inventory->stockIn(
                    warehouseId: $rmWarehouseId,
                    itemId: (int) $cuttingJob->fabric_item_id,
                    qty: $qtySisa,
                    date: now(),
                    sourceType: 'cutting_job_sisa',
                    sourceId: (int) $cuttingJob->id,
                    notes: "Sisa kain cutting {$cuttingJob->code} - LOT {$lot->code}",
                    lotId: $lotId,
                    unitCost: $avgCost > 0 ? $avgCost : null,
                    affectLotCost: false,
                );

                // 2. Tambah kembali qty_onhand LOT
                $lot->qty_onhand  = (float) $lot->qty_onhand + $qtySisa;
                $lot->total_cost  = $lot->qty_onhand * $avgCost;
                if ($lot->status === 'closed' && $lot->qty_onhand > 0) {
                    $lot->status = 'active';
                }
                $lot->save();

                // 3. Catat di pivot
                $cjLot->qty_sisa_fabric   = $qtySisa;
                $cjLot->sisa_recorded_at  = now();
                $cjLot->sisa_recorded_by  = auth()->id();
                $cjLot->save();

                $processed++;
            }
        });

        if ($processed === 0) {
            return back()->with('warning', 'Tidak ada sisa yang perlu dicatat (qty = 0).');
        }

        return redirect()
            ->route('production.cutting_jobs.show', $cuttingJob)
            ->with('success', "Sisa kain berhasil dicatat dan dikembalikan ke RM ({$processed} LOT).");
    }

}
