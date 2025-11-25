<?php

namespace App\Http\Controllers\Production;

use App\Http\Controllers\Controller;
use App\Models\CuttingJob;
use App\Models\Employee;
use App\Models\Item;
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
        // semua LOT dengan saldo > 0 (sudah include relasi lot.item & warehouse di service)
        $lotStocks = $this->inventory->getAvailableLots();

        // ambil lot_id dari query (ketika user klik "Input Outputs")
        $selectedLotId = $request->get('lot_id') ?? old('lot_id');
        $selectedLotRow = null;

        if ($selectedLotId) {
            $selectedLotRow = $lotStocks->firstWhere('lot_id', (int) $selectedLotId);
        }

        // data master item jadi & operator cutting (dipakai kalau LOT sudah dipilih)
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

        // kalau LOT belum dipilih → tidak perlu siapkan $lot, $warehouse, dst
        $lot = null;
        $warehouse = null;
        $lotBalance = 0.0;
        $rows = [];

        if ($selectedLotRow) {
            $lot = $selectedLotRow->lot;
            $warehouse = $selectedLotRow->warehouse;
            $lotBalance = (float) $selectedLotRow->qty_balance;

            // rows initial (kalau ada old input, pakai itu)
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

        return view('production.cutting_jobs.create', [
            'lotStocks' => $lotStocks,
            'selectedLotId' => $selectedLotId,
            'selectedLotRow' => $selectedLotRow,

            // untuk _form
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
            'fabric_item_id' => ['nullable', 'exists:items,id'],

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
    public function show(CuttingJob $cuttingJob)
    {
        $cuttingJob->load([
            'warehouse',
            'lot.item',
            'bundles.finishedItem',
            'bundles.operator',
        ]);

        return view('production.cutting_jobs.show', [
            'job' => $cuttingJob,
        ]);
    }
}
