<?php

namespace App\Http\Controllers\Inventory;

use App\Helpers\CodeGenerator;
use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\ExternalTransfer;
use App\Models\ExternalTransferLine;
use App\Models\InventoryMutation;
use App\Models\Lot;
use App\Models\Warehouse;
use App\Services\Inventory\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ExternalTransferController extends Controller
{
    public function __construct(
        protected InventoryService $inventory,
    ) {}

    /**
     * INDEX — daftar pengiriman bahan ke vendor (external transfers).
     */
    public function index(Request $request)
    {
        $q = ExternalTransfer::with(['fromWarehouse', 'toWarehouse', 'creator'])
            ->orderByDesc('date')
            ->orderByDesc('id');

        if ($request->filled('process')) {
            $q->where('process', $request->process);
        }

        if ($request->filled('status')) {
            $q->where('status', $request->status);
        }

        if ($request->filled('from_warehouse_id')) {
            $q->where('from_warehouse_id', $request->from_warehouse_id);
        }

        if ($request->filled('to_warehouse_id')) {
            $q->where('to_warehouse_id', $request->to_warehouse_id);
        }

        if ($request->filled('operator_code')) {
            $q->where('operator_code', $request->operator_code);
        }

        if ($request->filled('from_date')) {
            $q->whereDate('date', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $q->whereDate('date', '<=', $request->to_date);
        }

        if ($search = $request->get('q')) {
            $q->where('code', 'like', "%{$search}%");
        }

        $transfers = $q->paginate(20)->withQueryString();
        $warehouses = Warehouse::orderBy('name')->get();
        $employees = Employee::orderBy('name')->get();

        return view('inventory.external_transfers.index', compact(
            'transfers',
            'warehouses',
            'employees'
        ));
    }

    /**
     * SHOW — detail external transfer + LOT yang dikirim.
     */
    public function show(ExternalTransfer $externalTransfer)
    {
        $externalTransfer->load([
            'fromWarehouse',
            'toWarehouse',
            'creator',
            'lines.lot.item',
        ]);

        return view('inventory.external_transfers.show', [
            'transfer' => $externalTransfer,
        ]);
    }

    /**
     * FORM CREATE — pilih gudang, pilih LOT, input qty.
     */
    public function create(Request $request)
    {
        $defaultProcess = $request->query('process', 'cutting');

        // 1. Tentukan gudang asal default
        $defaultFromWarehouseId = $request->query('from_warehouse_id');
        if (!$defaultFromWarehouseId) {
            $defaultFromWarehouseId =
            Warehouse::where('code', 'KONTRAKAN')->value('id') ?? Warehouse::orderBy('name')->value('id');
        }

        $warehouses = Warehouse::orderBy('name')->get();
        $employees = Employee::orderBy('name')->get();

        // 2. Ambil stok LOT per gudang dari inventory_mutations
        $stockRows = InventoryMutation::query()
            ->selectRaw('lot_id, warehouse_id, SUM(qty_change) as stock_remain')
            ->whereNotNull('lot_id')
            ->when($defaultFromWarehouseId, function ($q, $wid) {
                $q->where('warehouse_id', $wid);
            })
            ->groupBy('lot_id', 'warehouse_id')
            ->having('stock_remain', '>', 0)
            ->get();

        // 3. Ambil detail LOT + item untuk lot_id yang ketemu
        $lotIds = $stockRows->pluck('lot_id')->unique()->filter()->all();

        $lotModels = Lot::with('item')
            ->whereIn('id', $lotIds)
            ->get()
            ->keyBy('id');

        // 4. Bangun koleksi $lots yang siap dipakai Blade
        $lots = $stockRows->map(function ($row) use ($lotModels) {
            $lot = $lotModels->get($row->lot_id);
            if (!$lot) {
                return null;
            }

            if ($lot->status !== 'open') {
                return null;
            }

            $vm = clone $lot;

            $vm->lot_code = $lot->code;
            $vm->item_code = $lot->item->code ?? '';
            $vm->item_name = $lot->item->name ?? '';
            $vm->stock_remain = (float) $row->stock_remain;
            $vm->unit = $lot->unit ?? ($lot->item->unit ?? 'pcs');

            return $vm;
        })->filter()->values();

        $autoToWarehouseCode = null;

        return view('inventory.external_transfers.create', compact(
            'warehouses',
            'employees',
            'lots',
            'defaultProcess',
            'defaultFromWarehouseId',
            'autoToWarehouseCode',
        ));
    }

    /**
     * STORE — simpan dokumen + transfer stok antar gudang (per LOT).
     * Menggunakan InventoryService->transfer() supaya mutasi & moving average LOT jalan.
     */
    public function store(Request $request)
    {
        // 1. VALIDASI SEMUA INPUT
        $data = $request->validate([
            'date' => ['required', 'date'],
            'process' => ['required', 'string'],
            'operator_code' => ['required', 'string'],
            'from_warehouse_id' => ['required', 'exists:warehouses,id'],
            'to_warehouse_code' => ['required', 'string'], // kode: CUT-EXT-MRF, dll
            'notes' => ['nullable', 'string'],

            'lines' => ['required', 'array', 'min:1'],
            'lines.*.lot_id' => ['required', 'exists:lots,id'],
            'lines.*.item_id' => ['required', 'exists:items,id'],
            'lines.*.qty' => ['required', 'numeric', 'gt:0'],
            'lines.*.unit' => ['required', 'string'],
            'lines.*.notes' => ['nullable', 'string'],
        ]);

        // 2. CARI / BUAT GUDANG TUJUAN DARI KODE
        $toCode = trim($data['to_warehouse_code']);

        /** @var Warehouse $toWarehouse */
        $toWarehouse = Warehouse::firstOrCreate(
            ['code' => $toCode],
            [
                'name' => $toCode, // boleh kamu ganti "Vendor {$toCode}"
                'type' => 'external', // sesuaikan skema type
            ]
        );

        // Cek: gudang asal ≠ gudang tujuan
        if ((int) $data['from_warehouse_id'] === (int) $toWarehouse->id) {
            return back()
                ->withInput()
                ->withErrors(['to_warehouse_code' => 'Gudang tujuan tidak boleh sama dengan gudang asal.']);
        }

        // 3. NORMALISASI LINES (pakai helper num() untuk qty)
        $lines = [];
        foreach ($data['lines'] as $row) {
            $qty = $this->num($row['qty'] ?? 0);
            if ($qty <= 0) {
                continue;
            }

            $lot = Lot::with('item')->find($row['lot_id']);
            if (!$lot || !$lot->item) {
                continue;
            }

            $lines[] = [
                'lot_id' => $lot->id,
                'item_id' => $lot->item_id,
                'item_code' => $lot->item->code,
                'qty' => $qty,
                'unit' => $row['unit'] ?? ($lot->item->unit ?? 'pcs'),
                'notes' => $row['notes'] ?? null,
            ];
        }

        if (empty($lines)) {
            return back()
                ->withInput()
                ->withErrors(['lines' => 'Pilih minimal satu LOT dengan qty > 0.']);
        }

        // 4. SIMPAN HEADER + DETAIL + MUTASI STOK
        try {
            $transfer = DB::transaction(function () use ($data, $lines, $request, $toWarehouse) {

                $code = CodeGenerator::generate('EXT');

                /** @var ExternalTransfer $header */
                $header = ExternalTransfer::create([
                    'code' => $code,
                    'date' => $data['date'],
                    'process' => $data['process'],
                    'operator_code' => $data['operator_code'],
                    'from_warehouse_id' => $data['from_warehouse_id'],
                    'to_warehouse_id' => $toWarehouse->id, // ⭐ HANYA SIMPAN ID
                    'status' => 'SENT',
                    'notes' => $data['notes'] ?? null,
                    'created_by' => $request->user()->id ?? null,
                ]);

                foreach ($lines as $row) {
                    /** @var ExternalTransferLine $line */
                    $line = ExternalTransferLine::create([
                        'external_transfer_id' => $header->id,
                        'lot_id' => $row['lot_id'],
                        'item_id' => $row['item_id'],
                        'item_code' => $row['item_code'],
                        'qty' => $row['qty'],
                        'unit' => $row['unit'],
                        'notes' => $row['notes'],
                    ]);

                    // Transfer stok: OUT dari gudang asal, IN ke gudang tujuan
                    $this->inventory->transfer(
                        fromWarehouseId: $header->from_warehouse_id,
                        toWarehouseId: $header->to_warehouse_id,
                        itemId: $row['item_id'],
                        qty: $row['qty'],
                        date: $header->date,
                        sourceType: 'external_transfer',
                        sourceId: $header->id,
                        notes: "External Transfer {$header->code} line {$line->id}",
                        allowNegative: false,
                        lotId: $row['lot_id'],
                    );
                }

                return $header;
            });

            return redirect()
                ->route('inventory.external_transfers.show', $transfer->id)
                ->with('success', 'External transfer berhasil dibuat dan stok berpindah.');

        } catch (\Throwable $e) {
            return back()
                ->withInput()
                ->withErrors(['general' => $e->getMessage()]);
        }
    }

    // =====================================================================
    // VALIDASI & HELPER
    // =====================================================================

    protected function validateHeader(Request $request): array
    {
        return $request->validate([
            'date' => ['required', 'date'],
            'process' => ['required', 'string'],
            'operator_code' => ['required', 'string'],
            'from_warehouse_id' => ['required', 'exists:warehouses,id'],
            'to_warehouse_id' => ['required', 'exists:warehouses,id'],
            'notes' => ['nullable', 'string'],
        ]);
    }

    /**
     * Helper parsing angka gaya Indonesia, copy dari InventoryService (num).
     */
    protected function num(float | int | string | null $value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }

        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        $value = trim((string) $value);
        $value = str_replace(' ', '', $value);

        // Kalau ada koma → anggap format Indonesia (1.234,56)
        if (strpos($value, ',') !== false) {
            $value = str_replace('.', '', $value); // buang titik ribuan
            $value = str_replace(',', '.', $value); // koma jadi titik
            return (float) $value;
        }

        return (float) $value;
    }
}
