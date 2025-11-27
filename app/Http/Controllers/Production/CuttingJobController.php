<?php

namespace App\Http\Controllers\Production;

use App\Http\Controllers\Controller;
use App\Models\CuttingJob;
use App\Models\Employee;
use App\Models\Item;
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
     * Form create Cutting Job (2 tahap: pilih LOT → isi output).
     */
    public function create(Request $request)
    {
        // 1️⃣ Cari gudang RM (wajib ada)
        $rmWarehouseId = Warehouse::where('code', 'RM')->value('id');

        if (!$rmWarehouseId) {
            // optional: bisa abort(500) atau redirect dengan flash message
            throw new \RuntimeException('Warehouse RM belum dikonfigurasi di tabel warehouses (code = RM).');
        }

        // 2️⃣ Ambil semua LOT dengan saldo > 0 di gudang RM
        //    (asumsi getAvailableLots() sudah include relasi lot & warehouse)
        $lotStocks = $this->inventory->getAvailableLots(
            warehouseId: $rmWarehouseId,
            itemId: null, // bisa diisi kalau mau filter per item
        );

        // 3️⃣ Tentukan LOT yang dipilih:
        //    - dari query ?lot_id=...
        //    - atau dari old('lot_id') setelah validation error
        $selectedLotId = $request->query('lot_id') ?: old('lot_id');
        $selectedLotRow = null;

        if ($selectedLotId) {
            $selectedLotRow = $lotStocks->firstWhere('lot_id', (int) $selectedLotId);
        }

        // 4️⃣ Kalau belum ada LOT terpilih atau lot_id tidak valid,
        //    tapi stok LOT ada → auto-pilih LOT pertama supaya form tidak "kosong".
        if (!$selectedLotRow && $lotStocks->isNotEmpty()) {
            $selectedLotRow = $lotStocks->first();
            $selectedLotId = $selectedLotRow->lot_id;
        }

        // 5️⃣ Siapkan variable untuk view
        $lot = null;
        $warehouse = null;
        $lotBalance = 0.0;
        $rows = [];

        if ($selectedLotRow) {
            $lot = $selectedLotRow->lot; // relasi Lot model
            $warehouse = $selectedLotRow->warehouse; // relasi Warehouse model
            $lotBalance = (float) $selectedLotRow->qty_balance;

            // Rows initial (kalau ada old input, pakai itu; kalau tidak, buat 1 baris kosong)
            $oldBundles = old('bundles');
            if ($oldBundles) {
                $rows = $oldBundles;
            } else {
                $rows = [
                    [
                        'bundle_no' => 1,
                        'finished_item_id' => '',
                        'qty_pcs' => '',
                        'qty_used_fabric' => 0,
                        'item_category' => '',
                        'notes' => '',
                    ],
                ];
            }
        }

        // 6️⃣ Data master item jadi & operator cutting
        $items = Item::query()
            ->select('id', 'code', 'item_category_id')
            ->where('type', 'finished_good')
            ->with(['category:id,code,name'])
            ->orderBy('code')
            ->get();

        $operators = Employee::query()
            ->select('id', 'code', 'name')
            ->where('role', 'cutting')
            ->orderBy('code')
            ->get();

        return view('production.cutting_jobs.create', [
            'lotStocks' => $lotStocks,
            'selectedLotId' => $selectedLotId,
            'selectedLotRow' => $selectedLotRow,

            // untuk _form.blade.php
            'mode' => 'create',
            'job' => null, // hanya dipakai di edit
            'lot' => $lot,
            'warehouse' => $warehouse,
            'lotBalance' => $lotBalance,
            'items' => $items,
            'operators' => $operators,
            'rows' => $rows,
        ]);
    }

    /**
     * Simpan Cutting Job + bundles.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'date' => ['required', 'date'],
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'lot_id' => ['required', 'exists:lots,id'],
            'fabric_item_id' => ['required', 'integer', 'exists:items,id'],
            'lot_id' => ['required', 'integer', 'exists:lots,id'],

            // Wajib pilih operator
            'operator_id' => ['required', 'exists:employees,id'],

            'notes' => ['nullable', 'string'],

            'bundles' => ['required', 'array', 'min:1'],
            'bundles.*.id' => ['nullable', 'integer'], // di create selalu null
            'bundles.*.bundle_no' => ['nullable', 'integer'],
            'bundles.*.finished_item_id' => ['required', 'exists:items,id'],
            'bundles.*.qty_pcs' => ['required', 'numeric', 'min:0.01'],
            'bundles.*.qty_used_fabric' => ['nullable', 'numeric', 'min:0'],
            'bundles.*.item_category' => ['nullable', 'string'],
            'bundles.*.notes' => ['nullable', 'string'],
        ], [
            'lot_id.required' => 'Silakan pilih LOT kain dulu.',
            'operator_id.required' => 'Silakan pilih operator cutting.',
            'bundles.required' => 'Minimal 1 baris output harus diisi.',
            'bundles.*.finished_item_id.required' => 'Item jadi pada setiap baris wajib diisi.',
            'bundles.*.qty_pcs.required' => 'Qty pcs pada setiap baris wajib diisi.',
        ]);

        // Hitung qty_used_fabric per baris di server
        $lotBalance = (float) ($request->input('lot_balance') ?? 0);
        $bundles = $validated['bundles'];

        // hitung jumlah baris valid
        $validRows = [];
        foreach ($bundles as $row) {
            $qty = (float) $row['qty_pcs'];
            if (!empty($row['finished_item_id']) && $qty > 0) {
                $validRows[] = $row;
            }
        }

        $countValid = count($validRows);
        $perRow = ($countValid > 0 && $lotBalance > 0)
        ? round($lotBalance / $countValid, 2)
        : 0.0;

        // set ulang qty_used_fabric per baris
        foreach ($validated['bundles'] as $i => $row) {
            $qty = (float) $row['qty_pcs'];
            if (!empty($row['finished_item_id']) && $qty > 0 && $perRow > 0) {
                $validated['bundles'][$i]['qty_used_fabric'] = $perRow;
            } else {
                $validated['bundles'][$i]['qty_used_fabric'] = 0;
            }
        }

        $job = $this->cutting->create($validated);

        return redirect()
            ->route('production.cutting_jobs.show', $job)
            ->with('success', 'Cutting job berhasil dibuat.');
    }

    /**
     * Form edit Cutting Job.
     */
    public function edit(CuttingJob $cuttingJob)
    {
        $cuttingJob->load([
            'warehouse',
            'lot.item',
            'bundles.finishedItem',
        ]);

        $lot = $cuttingJob->lot;
        $warehouse = $cuttingJob->warehouse;

        // untuk sekarang, jadikan total pemakaian kain sebagai "lotBalance" tampilan
        $lotBalance = $cuttingJob->bundles->sum('qty_used_fabric');

        $items = Item::query()
            ->select('id', 'code', 'item_category_id')
            ->where('type', 'finished_good')
            ->with(['category:id,code,name'])
            ->orderBy('code')
            ->get();

        $operators = Employee::query()
            ->select('id', 'code', 'name')
            ->where('role', 'cutting')
            ->orderBy('code')
            ->get();

        // siapkan rows untuk _form: dari old() atau dari DB
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
                    'item_category' => '', // kalau suatu saat ada kolom / relasi kategori di bundle, bisa diisi
                    'notes' => $b->notes,
                ];
            }

            if (empty($rows)) {
                $rows[] = [
                    'bundle_no' => 1,
                    'finished_item_id' => '',
                    'qty_pcs' => '',
                    'qty_used_fabric' => 0,
                    'item_category' => '',
                    'notes' => '',
                ];
            }
        }

        return view('production.cutting_jobs.edit', [
            'mode' => 'edit',
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
     * Update Cutting Job + bundles.
     */
    public function update(Request $request, CuttingJob $cuttingJob)
    {
        $validated = $request->validate([
            'date' => ['required', 'date'],
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'lot_id' => ['required', 'exists:lots,id'],
            'fabric_item_id' => ['nullable', 'exists:items,id'],

            'operator_id' => ['required', 'exists:employees,id'],
            'notes' => ['nullable', 'string'],

            'bundles' => ['required', 'array', 'min:1'],
            'bundles.*.id' => ['nullable', 'integer'],
            'bundles.*.bundle_no' => ['nullable', 'integer'],
            'bundles.*.finished_item_id' => ['required', 'exists:items,id'],
            'bundles.*.qty_pcs' => ['required', 'numeric', 'min:0.01'],
            'bundles.*.qty_used_fabric' => ['nullable', 'numeric', 'min:0'],
            'bundles.*.item_category' => ['nullable', 'string'],
            'bundles.*.notes' => ['nullable', 'string'],
        ]);

        // pakai lot_balance dari input (kalau ada), kalau tidak → total used lama
        $lotBalance = (float) ($request->input('lot_balance') ?? $cuttingJob->bundles->sum('qty_used_fabric'));
        $bundles = $validated['bundles'];

        $validRows = [];
        foreach ($bundles as $row) {
            $qty = (float) $row['qty_pcs'];
            if (!empty($row['finished_item_id']) && $qty > 0) {
                $validRows[] = $row;
            }
        }

        $countValid = count($validRows);
        $perRow = ($countValid > 0 && $lotBalance > 0)
        ? round($lotBalance / $countValid, 2)
        : 0.0;

        foreach ($validated['bundles'] as $i => $row) {
            $qty = (float) $row['qty_pcs'];
            if (!empty($row['finished_item_id']) && $qty > 0 && $perRow > 0) {
                $validated['bundles'][$i]['qty_used_fabric'] = $perRow;
            } else {
                $validated['bundles'][$i]['qty_used_fabric'] = 0;
            }
        }

        $job = $this->cutting->update($validated, $cuttingJob);

        return redirect()
            ->route('production.cutting_jobs.show', $job)
            ->with('success', 'Cutting job berhasil diupdate.');
    }

    /**
     * Detail satu Cutting Job.
     */
    // app/Http/Controllers/Production/CuttingJobController.php

    public function show(CuttingJob $cuttingJob)
    {
        // Load relasi yang dibutuhkan
        $cuttingJob->load([
            'warehouse',
            'lot.item',
            'bundles.finishedItem',
            'bundles.operator',
            'bundles.qcResults' => function ($q) {
                $q->where('stage', QcResult::STAGE_CUTTING); // atau 'cutting' kalau belum pakai constant
            },
        ]);
        // Cek apakah sudah pernah di-QC Cutting (langsung via query)
        $hasQcCutting = $cuttingJob->bundles()
            ->whereHas('qcResults', function ($q) {
                $q->where('stage', QcResult::STAGE_CUTTING); // atau 'cutting'
            })
            ->exists();

        return view('production.cutting_jobs.show', [
            'job' => $cuttingJob,
            'hasQcCutting' => $hasQcCutting,
        ]);
    }

    public function sendToQc(CuttingJob $cuttingJob)
    {
        // Cek apakah sudah pernah QC Cutting
        $hasQcCutting = $cuttingJob->bundles()
            ->whereHas('qcResults', function ($q) {
                $q->where('stage', 'cutting');
            })
            ->exists();

        // Hanya update status kalau belum pernah QC
        if (!$hasQcCutting) {
            // Sesuaikan status yang kamu mau, mis: 'cut_sent_to_qc'
            $cuttingJob->update([
                'status' => 'sent_to_qc',
            ]);
        }

        return redirect()
            ->back()
            ->with('success', 'Cutting job dikirim ke QC Cutting.');
    }

}
