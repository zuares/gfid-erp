<?php

namespace App\Http\Controllers\Production;

use App\Http\Controllers\Controller;
use App\Models\CuttingJob;
use App\Models\Employee;
use App\Models\Item;
use App\Models\Lot;
use App\Models\QcResult;
use App\Models\Warehouse;
use App\Services\Inventory\InventoryService;
use App\Services\Production\CuttingService;
use Illuminate\Http\Request;

class CuttingJobController extends Controller
{
    public function __construct(
        protected InventoryService $inventory,
        protected CuttingService $cutting,
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
                'operator', // ⬅️ tambahkan ini (sesuaikan nama relasinya)
            ])
            ->withCount('bundles')
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

        return view('production.cutting_jobs.index', [
            'jobs' => $jobs,
            'warehouses' => $warehouses,
            'filters' => $request->only(['status', 'warehouse_id']),
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

        // 2️⃣ Ambil semua LOT yang masih punya saldo > 0 di gudang RM
        $lotStocks = $this->inventory->getAvailableLots(
            warehouseId: $rmWarehouseId,
            itemId: null// filter per item kain akan dilakukan di front-end (JS)
        );

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

        return view('production.cutting_jobs.create', [
            'lotStocks' => $lotStocks,
            'items' => $items,
            'operators' => $operators,
            'warehouses' => $warehouses,
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
            'lot.item',
            'bundles.finishedItem',
            'bundles.operator',
        ]);

        $lot = $cuttingJob->lot;
        $warehouse = $cuttingJob->warehouse;

        $lotBalance = $cuttingJob->bundles->sum('qty_used_fabric');

        $items = Item::query()
            ->select('id', 'code', 'name')
            ->where('type', 'finished_good')
            ->orderBy('code')
            ->get();

        $operators = Employee::query()
            ->select('id', 'code', 'name')
            ->where('role', 'cutting')
            ->orderBy('code')
            ->get();

        $oldBundles = old('bundles');
        $rows = [];

        if ($oldBundles) {
            $rows = $oldBundles;
        } else {
            foreach ($cuttingJob->bundles as $b) {
                $rows[] = [
                    'id' => $b->id,
                    'bundle_no' => $b->bundle_no,
                    'finished_item_id' => $b->finished_item_id,
                    'qty_pcs' => $b->qty_pcs,
                    'qty_used_fabric' => $b->qty_used_fabric,
                    'item_category' => $b->item_category ?? '',
                    'notes' => $b->notes,
                    // kalau mau edit lot per bundle, nanti bisa tambahkan lot_id di sini
                ];
            }

            if (empty($rows)) {
                $rows[] = [
                    'bundle_no' => 1,
                    'finished_item_id' => null,
                    'qty_pcs' => null,
                    'qty_used_fabric' => 0,
                    'item_category' => '',
                    'notes' => '',
                ];
            }
        }

        return view('production.cutting_jobs.edit', [
            'job' => $cuttingJob,
            'lot' => $lot,
            'warehouse' => $warehouse,
            'lotBalance' => $lotBalance,
            'items' => $items,
            'operators' => $operators,
            'rows' => $rows,
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
        // 2) HITUNG SALDO PER LOT + TOTAL SALDO
        // ==========================================
        $lotBalances = [];
        $totalLotBalance = 0.0;

        foreach ($selectedLotIds as $lotId) {
            // pakai InventoryService supaya ngikut semua mutasi
            $saldo = (float) $this->inventory->getLotBalance(
                warehouseId: $warehouseId,
                itemId: $fabricItemId,
                lotId: $lotId,
            );

            if ($saldo < 0) {
                $saldo = 0.0;
            }

            $lotBalances[$lotId] = $saldo;
            $totalLotBalance += $saldo;
        }

        if ($totalLotBalance <= 0.000001) {
            return back()
                ->withErrors(['selected_lots' => 'Saldo kain di LOT yang dipilih sudah habis / 0.'])
                ->withInput();
        }

        // LOT utama (untuk header) → ambil LOT pertama yang punya saldo > 0
        $primaryLotId = collect($selectedLotIds)
            ->first(fn($id) => ($lotBalances[$id] ?? 0) > 0) ?? $selectedLotIds[0];

        // =========================
        // 3) FILTER BUNDLE VALID
        // =========================
        $bundles = $validated['bundles'] ?? [];
        $validBundles = [];
        $bundlesIndexByLot = []; // [lot_id => [index2, index7, ...]]

        foreach ($bundles as $row) {
            $qty = (float) ($row['qty_pcs'] ?? 0);
            $lotId = !empty($row['lot_id']) ? (int) $row['lot_id'] : 0;

            if (empty($row['finished_item_id']) || $qty <= 0 || !$lotId) {
                continue;
            }

            // pastikan lot_id bundle termasuk LOT yang dipilih di kartu atas
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
        // 5) HITUNG qty_used_fabric PER LOT (BUKAN GLOBAL)
        //    + Rounding halus: baris terakhir ambil sisa
        // =======================================================
        foreach ($bundlesIndexByLot as $lotId => $indexes) {
            $saldoLot = $lotBalances[$lotId] ?? 0.0;

            if ($saldoLot <= 0.000001) {
                return back()
                    ->withErrors(['bundles' => "Saldo kain untuk LOT {$lotId} sudah habis / 0."])
                    ->withInput();
            }

            $countInLot = count($indexes);
            if ($countInLot <= 0) {
                continue;
            }

            // bagi rata per baris (dibulatkan), tapi jaga total supaya = saldoLot
            $perRow = round($saldoLot / $countInLot, 2);
            $usedSoFar = 0.0;

            foreach ($indexes as $i => $idx) {
                if ($i === $countInLot - 1) {
                    // baris terakhir: ambil sisa supaya total pas = saldoLot
                    $remaining = $saldoLot - $usedSoFar;
                    $validBundles[$idx]['qty_used_fabric'] = max($remaining, 0);
                } else {
                    $validBundles[$idx]['qty_used_fabric'] = $perRow;
                    $usedSoFar += $perRow;
                }
            }
        }

        $validated['bundles'] = $validBundles;

        // selected_lots tidak dipakai di CuttingService
        unset($validated['selected_lots']);

        // =========================
        // 6) CREATE JOB
        // =========================
        $job = $this->cutting->create($validated);

        // =========================
        // 7) SIMPAN PIVOT LOTS
        // =========================
        foreach ($selectedLotIds as $lotId) {
            $saldoLot = $lotBalances[$lotId] ?? 0.0;

            if ($saldoLot <= 0.000001) {
                continue;
            }

            \App\Models\CuttingJobLot::create([
                'cutting_job_id' => $job->id,
                'lot_id' => $lotId,
                // planned_fabric_qty = saldo real per LOT
                'planned_fabric_qty' => $saldoLot,
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
        $validated = $request->validate([
            'date' => ['required', 'date'],
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'lot_id' => ['nullable', 'integer', 'exists:lots,id'],
            'fabric_item_id' => ['nullable', 'exists:items,id'],

            'operator_id' => ['required', 'exists:employees,id'],
            'notes' => ['nullable', 'string'],

            'bundles' => ['required', 'array', 'min:1'],
            'bundles.*.id' => ['nullable', 'integer'],
            'bundles.*.bundle_no' => ['nullable', 'integer'],
            'bundles.*.lot_id' => ['required', 'integer', 'exists:lots,id'],
            'bundles.*.finished_item_id' => ['required', 'exists:items,id'],
            'bundles.*.qty_pcs' => ['required', 'numeric', 'min:0.01'],
            'bundles.*.qty_used_fabric' => ['required', 'numeric', 'min:0'],
            'bundles.*.item_category' => ['nullable', 'string'],
            'bundles.*.notes' => ['nullable', 'string'],
        ]);

        $bundles = $validated['bundles'] ?? [];
        $validBundles = [];
        foreach ($bundles as $row) {
            $qty = (float) ($row['qty_pcs'] ?? 0);
            if (!empty($row['finished_item_id']) && $qty > 0) {
                $validBundles[] = $row;
            }
        }

        if (count($validBundles) === 0) {
            return back()
                ->withErrors(['bundles' => 'Minimal 1 baris bundle harus diisi dengan item & qty pcs > 0.'])
                ->withInput();
        }

        $validated['bundles'] = $validBundles;

        // Isi header lot_id dengan LOT pertama kalau kosong
        if (empty($validated['lot_id']) && count($validBundles) > 0 && !empty($validBundles[0]['lot_id'])) {
            $validated['lot_id'] = $validBundles[0]['lot_id'];
        }

        $job = $this->cutting->update($validated, $cuttingJob);

        return redirect()
            ->route('production.cutting_jobs.show', $job)
            ->with('success', 'Cutting job berhasil diupdate.');
    }

    /**
     * Detail satu Cutting Job.
     */
    public function show(CuttingJob $cuttingJob)
    {
        $cuttingJob->load([
            'warehouse',
            'lot.item',
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
}
