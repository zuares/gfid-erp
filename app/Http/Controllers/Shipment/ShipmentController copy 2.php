<?php

namespace App\Http\Controllers\Shipment;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\MarketplaceOrder;
use App\Models\SalesInvoice;
use App\Models\Shipment;
use App\Models\ShipmentLine;
use App\Models\Store;
use App\Models\Warehouse;
use App\Services\Inventory\InventoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ShipmentController extends Controller
{
    public function __construct(
        protected InventoryService $inventory,
    ) {}

    /**
     * List shipment (dengan filter sederhana).
     */
    public function index(): View
    {
        // Normalisasi filters biar selalu punya key
        $filters = [
            'warehouse_id' => $request->input('warehouse_id'),
            'customer_id' => $request->input('customer_id'),
            'store_id' => $request->input('store_id'),
            'date_from' => $request->input('date_from'),
            'date_to' => $request->input('date_to'),
            'q' => $request->input('q'),
        ];

        $shipments = Shipment::with(['warehouse', 'customer', 'store', 'creator'])
            ->filter($filters)
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        // KHUSUS: warehouse diisi hanya WH-RTS
        $warehouses = Warehouse::where('code', 'WH-RTS')
            ->orderBy('code')
            ->get();

        $customers = Customer::orderBy('name')->get();
        $stores = Store::orderBy('name')->get();

        return view('sales.shipments.index', [
            'shipments' => $shipments,
            'filters' => $filters,
            'warehouses' => $warehouses,
            'customers' => $customers,
            'stores' => $stores,
        ]);
    }

    /**
     * Form create shipment (scan keluar barang).
     */
    public function create(Request $request): View
    {
        $warehouseId = $request->input('warehouse_id');

        $warehouses = Warehouse::orderBy('code')->get();
        $customers = Customer::orderBy('name')->get();
        $stores = Store::orderBy('name')->get();

        return view('shipments.create', [
            'warehouses' => $warehouses,
            'customers' => $customers,
            'stores' => $stores,
            'selectedWarehouseId' => $warehouseId,
            'defaultDate' => now()->toDateString(),
            'defaultCustomerId' => null,
            'defaultStoreId' => null,
            'prefilledLines' => [], // tidak ada prefill
        ]);
    }

    public function createFromInvoice(SalesInvoice $invoice): View
    {
        $invoice->loadMissing(['lines.item', 'marketplaceOrder']);

        $warehouses = Warehouse::orderBy('code')->get();
        $customers = Customer::orderBy('name')->get();
        $stores = Store::orderBy('name')->get();

        // Ambil line yang punya item_id & qty > 0
        $prefilledLines = $invoice->lines
            ->filter(fn($line) => $line->item_id && $line->qty > 0)
            ->values()
            ->map(function ($line) {
                return [
                    'item_id' => $line->item_id,
                    'item_code' => $line->item_code_snapshot ?: ($line->item?->code ?? ''),
                    'item_name' => $line->item_name_snapshot ?: ($line->item?->name ?? ''),
                    'qty' => $line->qty,
                    'remarks' => null,
                ];
            })
            ->all();

        return view('shipments.create', [
            'warehouses' => $warehouses,
            'customers' => $customers,
            'stores' => $stores,
            'selectedWarehouseId' => $invoice->warehouse_id,
            'defaultDate' => optional($invoice->date)->toDateString() ?? now()->toDateString(),
            'defaultCustomerId' => $invoice->customer_id,
            'defaultStoreId' => optional($invoice->marketplaceOrder)->store_id,
            'prefilledLines' => $prefilledLines,
        ]);
    }

    public function createFromMarketplaceOrder(MarketplaceOrder $order): View
    {
        $order->loadMissing(['items.item', 'store', 'customer']);

        $warehouses = Warehouse::orderBy('code')->get();
        $customers = Customer::orderBy('name')->get();
        $stores = Store::orderBy('name')->get();

        $prefilledLines = $order->items
            ->filter(fn($line) => $line->item_id && $line->qty > 0)
            ->values()
            ->map(function ($line) {
                return [
                    'item_id' => $line->item_id,
                    'item_code' => $line->item_code_snapshot ?: ($line->item?->code ?? ''),
                    'item_name' => $line->item_name_snapshot ?: ($line->item?->name ?? ''),
                    'qty' => $line->qty,
                    'remarks' => null,
                ];
            })
            ->all();

        return view('shipments.create', [
            'warehouses' => $warehouses,
            'customers' => $customers,
            'stores' => $stores,
            'selectedWarehouseId' => null, // gudang dipilih manual
            'defaultDate' => optional($order->order_date)->toDateString() ?? now()->toDateString(),
            'defaultCustomerId' => $order->customer_id,
            'defaultStoreId' => $order->store_id,
            'prefilledLines' => $prefilledLines,
        ]);
    }

    /**
     * Simpan shipment + mutasi stok keluar.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'date' => ['required', 'date'],
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'customer_id' => ['nullable', 'exists:customers,id'],
            'store_id' => ['nullable', 'exists:stores,id'],
            'notes' => ['nullable', 'string'],

            'lines' => ['required', 'array', 'min:1'],
            'lines.*.item_id' => ['required', 'exists:items,id'],
            'lines.*.qty' => ['required', 'integer', 'min:1'],
            'lines.*.remarks' => ['nullable', 'string'],
        ]);

        $userId = Auth::id();

        try {
            $shipment = DB::transaction(function () use ($validated, $userId) {

                $totalItems = collect($validated['lines'])->sum('qty');

                /** @var \App\Models\Shipment $shipment */
                $shipment = Shipment::create([
                    'shipment_no' => $this->generateShipmentNo(),
                    'date' => $validated['date'],
                    'warehouse_id' => $validated['warehouse_id'],
                    'customer_id' => $validated['customer_id'] ?? null,
                    'store_id' => $validated['store_id'] ?? null,
                    'status' => 'submitted', // versi ini: langsung dianggap submitted
                    'total_items' => $totalItems,
                    'notes' => $validated['notes'] ?? null,
                    'created_by' => $userId,
                ]);

                foreach ($validated['lines'] as $lineData) {
                    $line = new ShipmentLine([
                        'item_id' => $lineData['item_id'],
                        'qty' => $lineData['qty'],
                        'remarks' => $lineData['remarks'] ?? null,
                    ]);

                    $shipment->lines()->save($line);

                    // 🔥 Mutasi stok keluar dari gudang pakai signature InventoryService yang sekarang
                    $this->inventory->stockOut(
                        warehouseId: $shipment->warehouse_id,
                        itemId: $line->item_id,
                        qty: $line->qty,
                        date: $shipment->date,
                        sourceType: 'shipment',
                        sourceId: $shipment->id,
                        notes: $this->buildShipmentNotes($shipment),
                        allowNegative: false,
                        lotId: null,
                        unitCostOverride: null,
                        affectLotCost: false, // shipment FG → tidak mengubah LotCost kain
                    );
                }

                return $shipment;
            });
        } catch (\Throwable $e) {
            report($e);

            return redirect()
                ->back()
                ->withInput()
                ->with('status', 'error')
                ->with('message', 'Gagal menyimpan shipment: ' . $e->getMessage());
        }

        return redirect()
            ->route('shipments.show', $shipment)
            ->with('status', 'success')
            ->with('message', 'Shipment ' . $shipment->shipment_no . ' berhasil dibuat.');
    }

    /**
     * Detail shipment.
     */
    public function show(Shipment $shipment): View
    {
        $shipment->load([
            'lines.item',
            'warehouse',
            'customer',
            'store',
            'creator',
        ]);

        return view('shipments.show', compact('shipment'));
    }

    /**
     * Generate nomor auto: SHP-YYYYMMDD-###
     */
    protected function generateShipmentNo(): string
    {
        $today = now()->format('Ymd');

        $last = Shipment::whereDate('date', now()->toDateString())
            ->orderByDesc('id')
            ->first();

        $nextNumber = 1;

        if ($last) {
            // Asumsi format: SHP-YYYYMMDD-###
            $parts = explode('-', $last->shipment_no);
            $lastSeq = (int) end($parts);
            $nextNumber = $lastSeq + 1;
        }

        return sprintf('SHP-%s-%03d', $today, $nextNumber);
    }

    /**
     * Helper buat notes mutasi shipment.
     */
    protected function buildShipmentNotes(Shipment $shipment): string
    {
        $parts = ['Shipment ' . $shipment->shipment_no];

        if ($shipment->store?->code) {
            $parts[] = 'store ' . $shipment->store->code;
        }

        if ($shipment->customer?->name) {
            $parts[] = 'customer ' . $shipment->customer->name;
        }

        return implode(' - ', $parts);
    }
}
