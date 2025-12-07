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
     * Form Cutting Job - tahap awal:
     * 1) User pilih LOT dulu dari gudang RM (hanya LOT yang masih ada saldo).
     * 2) Setelah LOT dipilih (?lot_id=...), baru tampil form bundle di _form.
     */
    public function create(Request $request)
    {
        // 1️⃣ Cari gudang RM (wajib ada, jadi konfigurasi awal di tabel warehouses)
        $rmWarehouseId = Warehouse::where('code', 'RM')->value('id');

        if (!$rmWarehouseId) {
            // Bisa juga diganti redirect dengan pesan error / view khusus
            throw new \RuntimeException('Warehouse RM belum dikonfigurasi di tabel warehouses (code = RM).');
        }

        // 2️⃣ Ambil semua LOT yang masih punya saldo > 0 di gudang RM
        //    - getAvailableLots() sudah kita buat hanya mengembalikan LOT dengan qty_balance > 0
        //    - sudah include relasi lot.item dan warehouse
        $lotStocks = $this->inventory->getAvailableLots(
            warehouseId: $rmWarehouseId,
            itemId: null// kalau mau, nanti bisa ditambah filter per item kain tertentu
        );

        // 3️⃣ Tentukan LOT yang dipilih user:
        //    - dari query string ?lot_id=... (klik dari _pick_lot)
        //    - atau dari old('lot_id') setelah submit gagal validasi
        $selectedLotId = $request->query('lot_id') ?: old('lot_id');
        $selectedLotRow = null;

        if ($selectedLotId) {
            // Cari baris LOT yang sesuai dari Collection $lotStocks
            $selectedLotRow = $lotStocks->firstWhere('lot_id', (int) $selectedLotId);
        }

        // ❌ 4️⃣ Dulu di sini ada auto-pilih LOT pertama kalau belum ada lot_id
        //    → sekarang DIHAPUS supaya user wajib pilih LOT sendiri dari _pick_lot
        // if (!$selectedLotRow && $lotStocks->isNotEmpty()) {
        //     $selectedLotRow = $lotStocks->first();
        //     $selectedLotId = $selectedLotRow->lot_id;
        // }

        // 5️⃣ Siapkan variable untuk dikirim ke view
        //    Default-nya null/0, supaya kalau LOT belum dipilih, _form tidak ditampilkan.
        $lot = null;
        $warehouse = null;
        $lotBalance = 0.0;
        $rows = [];

        if ($selectedLotRow) {
            // Jika user sudah memilih LOT, isi detailnya untuk _form
            $lot = $selectedLotRow->lot; // relasi Lot model
            $warehouse = $selectedLotRow->warehouse; // relasi Warehouse model
            $lotBalance = (float) $selectedLotRow->qty_balance; // saldo kain yang masih tersedia

            // 6️⃣ Siapkan rows bundle awal:
            //     - kalau ada old('bundles') → pakai itu (habis validasi gagal)
            //     - kalau tidak → buat 1 baris kosong default
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

        // 7️⃣ Data master item jadi (finished_good) untuk combobox di form
        $items = Item::query()
            ->select('id', 'code', 'item_category_id')
            ->where('type', 'finished_good')
            ->with(['category:id,code,name'])
            ->orderBy('code')
            ->get();

        // 8️⃣ Data master operator cutting
        $operators = Employee::query()
            ->select('id', 'code', 'name')
            ->where('role', 'cutting')
            ->orderBy('code')
            ->get();

        // 9️⃣ Kirim ke view
        // Di Blade:
        // - jika $selectedLotRow == null → tampil _pick_lot (pilih LOT dulu)
        // - jika $selectedLotRow != null → tampil _form (input bundle)
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
     * Form Edit Cutting Job:
     * - Load gudang, LOT, bundles yang sudah ada.
     * - Isi rows untuk _form dari data bundle di DB (atau dari old() kalau validasi gagal).
     */
    public function edit(CuttingJob $cuttingJob)
    {
        // 1️⃣ Preload relasi yang dibutuhkan
        $cuttingJob->load([
            'warehouse',
            'lot.item',
            'bundles.finishedItem',
            'bundles.operator',
        ]);

        $lot = $cuttingJob->lot;
        $warehouse = $cuttingJob->warehouse;

        // 2️⃣ lotBalance di tampilan (sementara = total pemakaian kain)
        $lotBalance = $cuttingJob->bundles->sum('qty_used_fabric');

        // 3️⃣ Master data item jadi & operator cutting
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

        // 4️⃣ Siapkan rows bundle untuk form:
        //    - Kalau ada old('bundles') → pakai itu
        //    - Kalau tidak → generate dari bundles existing
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
                    'item_category' => $b->item_category ?? '', // kalau nanti kamu butuh
                    'notes' => $b->notes,
                ];
            }

            if (empty($rows)) {
                // minimal 1 baris supaya form tidak kosong
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
     * Simpan Cutting Job + bundles.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'date' => ['required', 'date'],
            'warehouse_id' => ['required', 'exists:warehouses,id'],

            // lot_id di header sekarang OPSIONAL (boleh kosong)
            'lot_id' => ['nullable', 'integer', 'exists:lots,id'],

            'fabric_item_id' => ['required', 'integer', 'exists:items,id'],
            'operator_id' => ['required', 'exists:employees,id'],
            'notes' => ['nullable', 'string'],

            'bundles' => ['required', 'array', 'min:1'],
            'bundles.*.id' => ['nullable', 'integer'],
            'bundles.*.bundle_no' => ['nullable', 'integer'],

            // 🔹 LOT per baris (akan diisi otomatis oleh JS, user nggak perlu klik)
            'bundles.*.lot_id' => ['required', 'integer', 'exists:lots,id'],

            'bundles.*.finished_item_id' => ['required', 'integer', 'exists:items,id'],
            'bundles.*.item_category_id' => ['nullable', 'integer', 'exists:item_categories,id'],

            'bundles.*.qty_pcs' => ['required', 'numeric', 'min:0.01'],

            // 🔹 tidak wajib diisi user, nanti kita isi otomatis
            'bundles.*.qty_used_fabric' => ['nullable', 'numeric', 'min:0'],

            'bundles.*.notes' => ['nullable', 'string'],
        ], [
            'fabric_item_id.required' => 'Item kain wajib dipilih.',
            'operator_id.required' => 'Operator cutting wajib dipilih.',
            'bundles.*.finished_item_id.required' => 'Item jadi pada setiap baris wajib diisi.',
            'bundles.*.qty_pcs.required' => 'Qty pcs pada setiap baris wajib diisi.',
            'bundles.*.lot_id.required' => 'LOT pada setiap baris wajib diisi (akan diisi otomatis).',
        ]);

        // 🔹 Pastikan header lot_id punya nilai (kalau kosong, pakai lot pertama dari bundles)
        if (empty($validated['lot_id']) && !empty($validated['bundles'])) {
            $firstLotId = collect($validated['bundles'])
                ->pluck('lot_id')
                ->filter()
                ->first();

            if ($firstLotId) {
                $validated['lot_id'] = $firstLotId;
            }
        }

        // 🔹 Hitung qty_used_fabric per baris di server (AUTO)
        //    lot_balance dikirim dari hidden input yang diisi JS:
        //    total saldo kain dari LOT yang dicentang.
        $lotBalance = (float) ($request->input('lot_balance') ?? 0);
        $bundles = $validated['bundles'];

        // cari baris valid (punya item jadi & qty pcs > 0)
        $validRows = [];
        foreach ($bundles as $row) {
            $qty = (float) ($row['qty_pcs'] ?? 0);
            if (!empty($row['finished_item_id']) && $qty > 0) {
                $validRows[] = $row;
            }
        }

        $countValid = count($validRows);

        // 🔹 Rumus paling sederhana: bagi rata kain ke semua baris valid
        $perRow = ($countValid > 0 && $lotBalance > 0)
        ? round($lotBalance / $countValid, 2)
        : 0.0;

        foreach ($validated['bundles'] as $i => $row) {
            $qty = (float) ($row['qty_pcs'] ?? 0);

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
