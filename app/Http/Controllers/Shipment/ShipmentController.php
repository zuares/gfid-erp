<?php

namespace App\Http\Controllers\Shipment;

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
    public function index(Request $request): View
    {
        $filters = $request->only([
            'warehouse_id',
            'customer_id',
            'store_id',
            'date_from',
            'date_to',
            'q',
        ]);

        $shipments = Shipment::with(['warehouse', 'customer', 'store', 'creator'])
            ->filter($filters)
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        $warehouses = Warehouse::orderBy('code')->get();
        $customers = Customer::orderBy('name')->get();
        $stores = Store::orderBy('name')->get();

        return view('shipments.index', compact(
            'shipments',
            'filters',
            'warehouses',
            'customers',
            'stores',
        ));
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
            ->filter(function ($line) {
                return $line->item_id && $line->qty > 0;
            })
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

            // default header dari invoice
            'selectedWarehouseId' => $invoice->warehouse_id,
            'defaultDate' => optional($invoice->date)->toDateString() ?? now()->toDateString(),
            'defaultCustomerId' => $invoice->customer_id,
            'defaultStoreId' => optional($invoice->marketplaceOrder)->store_id,

            // prefill lines
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
            ->filter(function ($line) {
                return $line->item_id && $line->qty > 0;
            })
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

            // default header dari marketplace order
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
            'store_id' => ['nullable,', 'exists:stores,id'],
            'notes' => ['nullable', 'string'],

            // Lines dari form (array)
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.item_id' => ['required', 'exists:items,id'],
            'lines.*.qty' => ['required', 'integer', 'min:1'],
            'lines.*.remarks' => ['nullable', 'string'],
        ]);

        $userId = Auth::id();

        try {
            $shipment = DB::transaction(function () use ($validated, $userId) {
                $totalItems = collect($validated['lines'])->sum('qty');

                $shipment = Shipment::create([
                    'shipment_no' => $this->generateShipmentNo(),
                    'date' => $validated['date'],
                    'warehouse_id' => $validated['warehouse_id'],
                    'customer_id' => $validated['customer_id'] ?? null,
                    'store_id' => $validated['store_id'] ?? null,
                    'status' => 'submitted', // scan out biasanya langsung submitted
                    'total_items' => $totalItems,
                    'notes' => $validated['notes'] ?? null,
                    'created_by' => $userId,
                ]);

                foreach ($validated['lines'] as $lineData) {
                    /** @var \App\Models\ShipmentLine $line */
                    $line = new ShipmentLine([
                        'item_id' => $lineData['item_id'],
                        'qty' => $lineData['qty'],
                        // 'hpp_unit_snapshot' => 0, // nanti bisa diisi dari ItemCostSnapshot kalau perlu
                        'remarks' => $lineData['remarks'] ?? null,
                    ]);

                    $shipment->lines()->save($line);

                    // Mutasi stok keluar dari gudang
                    $this->inventory->stockOut(
                        warehouseId: $shipment->warehouse_id,
                        itemId: $line->item_id,
                        qty: $line->qty,
                        meta: [
                            'reference' => $shipment->shipment_no,
                            'reference_type' => 'shipment',
                            'reference_id' => $shipment->id,
                            'description' => 'Shipment ' . $shipment->shipment_no,
                        ]
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
}
