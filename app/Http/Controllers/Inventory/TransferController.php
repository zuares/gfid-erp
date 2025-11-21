<?php

namespace App\Http\Controllers\Inventory;

use App\Helpers\CodeGenerator;
use App\Http\Controllers\Controller;
use App\Models\InventoryTransfer;
use App\Models\InventoryTransferLine;
use App\Models\Item;
use App\Models\Warehouse;
use App\Services\Inventory\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TransferController extends Controller
{
    public function __construct(
        protected InventoryService $inventory,
    ) {
    }

    /**
     * List transfer stok.
     */
    public function index(Request $request)
    {
        $q = InventoryTransfer::with(['fromWarehouse', 'toWarehouse', 'lines.item'])
            ->orderByDesc('date')
            ->orderByDesc('id');

        if ($request->filled('from_warehouse_id')) {
            $q->where('from_warehouse_id', $request->from_warehouse_id);
        }

        if ($request->filled('to_warehouse_id')) {
            $q->where('to_warehouse_id', $request->to_warehouse_id);
        }

        if ($request->filled('item_id')) {
            // filter berdasarkan item di detail
            $q->whereHas('lines', function ($sub) use ($request) {
                $sub->where('item_id', $request->item_id);
            });
        }

        if ($request->filled('from_date')) {
            $q->whereDate('date', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $q->whereDate('date', '<=', $request->to_date);
        }

        $transfers = $q->paginate(15)->withQueryString();
        $warehouses = Warehouse::orderBy('name')->get();
        $items = Item::where('active', 1)->orderBy('name')->get();

        return view('inventory.transfers.index', compact(
            'transfers',
            'warehouses',
            'items'
        ));
    }

    /**
     * Form create transfer.
     */
    public function create()
    {
        $warehouses = Warehouse::orderBy('name')->get();
        $items = Item::where('active', 1)->orderBy('name')->get();

        return view('inventory.transfers.create', compact('warehouses', 'items'));
    }

    /**
     * Simpan transfer + mutasi stok (pakai InventoryService).
     */
// ...

    public function store(Request $request)
    {
        $headerData = $this->validateData($request);

        if ($headerData['from_warehouse_id'] == $headerData['to_warehouse_id']) {
            return back()
                ->withInput()
                ->withErrors(['to_warehouse_id' => 'Gudang tujuan tidak boleh sama dengan gudang asal.']);
        }

        // mapping line dari input
        $itemIds = $request->input('item_id', []);
        $qtys = $request->input('qty', []);
        $lineNotes = $request->input('line_notes', []);

        $lines = [];
        foreach ($itemIds as $i => $itemId) {
            $qty = $qtys[$i] ?? null;

            if (!$itemId) {
                continue;
            }

            $qtyFloat = $this->inventory->num($qty ?? 0); // kalau num protected, kita salin helpernya ke controller; kalau tidak, pakai (float)$qty

            if ($qtyFloat <= 0) {
                continue;
            }

            $lines[] = [
                'item_id' => $itemId,
                'qty' => $qtyFloat,
                'notes' => $lineNotes[$i] ?? null,
            ];
        }

        if (empty($lines)) {
            return back()
                ->withInput()
                ->withErrors(['item_id' => 'Minimal 1 baris item dengan qty > 0.']);
        }

        try {
            $transfer = DB::transaction(function () use ($headerData, $lines, $request) {
                $code = CodeGenerator::generate('TRF');

                /** @var InventoryTransfer $transfer */
                $transfer = InventoryTransfer::create([
                    'code' => $code,
                    'date' => $headerData['date'],
                    'from_warehouse_id' => $headerData['from_warehouse_id'],
                    'to_warehouse_id' => $headerData['to_warehouse_id'],
                    'notes' => $headerData['notes'] ?? null,
                    'created_by' => $request->user()->id,
                ]);

                foreach ($lines as $lineData) {
                    // simpan detail
                    $line = InventoryTransferLine::create([
                        'inventory_transfer_id' => $transfer->id,
                        'item_id' => $lineData['item_id'],
                        'qty' => $lineData['qty'],
                        'notes' => $lineData['notes'],
                    ]);

                    // jalankan transfer stok per item
                    $this->inventory->transfer(
                        fromWarehouseId: $transfer->from_warehouse_id,
                        toWarehouseId: $transfer->to_warehouse_id,
                        itemId: $line->item_id,
                        qty: $line->qty,
                        date: $transfer->date,
                        sourceType: 'inventory_transfer',
                        sourceId: $transfer->id,
                        notes: 'Transfer ' . $transfer->code . ' line ' . $line->id,
                        allowNegative: false,
                    );
                }

                return $transfer;
            });

            return redirect()
                ->route('inventory.transfers.show', $transfer->id)
                ->with('success', 'Transfer stok multi-item berhasil disimpan.');
        } catch (\Throwable $e) {
            return back()
                ->withInput()
                ->withErrors(['general' => $e->getMessage()]);
        }
    }

    /**
     * Detail transfer.
     */
    public function show(InventoryTransfer $transfer)
    {
        $transfer->load(['fromWarehouse', 'toWarehouse', 'lines.item', 'creator']);

        return view('inventory.transfers.show', compact('transfer'));
    }

    protected function validateData(Request $request): array
    {
        return $request->validate([
            'date' => ['required', 'date'],
            'from_warehouse_id' => ['required', 'exists:warehouses,id'],
            'to_warehouse_id' => ['required', 'exists:warehouses,id'],
            'item_id' => ['required', 'exists:items,id'],
            'qty' => ['required', 'numeric', 'gt:0'],
            'notes' => ['nullable', 'string'],
        ]);
    }
}
