<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Item;
use App\Models\SalesInvoice;
use App\Models\SalesInvoiceLine;
use App\Models\Shipment;
use App\Models\Store;
use App\Models\Warehouse;
use App\Services\Inventory\InventoryService;
use App\Services\Accounting\SalesInvoiceAccountingService;
use App\Services\Sales\SalesInvoiceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SalesInvoiceController extends Controller
{
    public function __construct(
        protected InventoryService $inventory, // siap kalau integrasi stok/shipment
        protected SalesInvoiceService $salesInvoices,
    ) {}

    public function index()
    {
        $invoices = SalesInvoice::with(['customer', 'warehouse', 'store'])
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->paginate(25);

        return view('sales.invoices.index', compact('invoices'));
    }

    public function create()
    {
        $customers = Customer::orderBy('name')->get();
        $warehouses = Warehouse::orderBy('code')->get();
        $stores = Store::orderBy('code')->get();

        // ✅ untuk dropdown item + tampilkan HPP master
        $items = Item::query()
            ->where('type', 'finished_good')
            ->orderBy('code')
            ->get()
            ->map(function ($item) {
                $item->hpp_unit = (float) ($item->hpp ?? 0);
                return $item;
            });

        return view('sales.invoices.create', [
            'customers' => $customers,
            'warehouses' => $warehouses,
            'stores' => $stores,
            'items' => $items,

            'sourceShipment' => null,
            'defaultDate' => now()->toDateString(),
            'defaultWarehouseId' => null,
            'defaultCustomerId' => null,
            'defaultStoreId' => null,
            'prefilledLines' => [],
        ]);
    }

    public function createFromShipment(Shipment $shipment)
    {
        $shipment->loadMissing(['lines.item', 'store', 'warehouse']);

        $warehouses = Warehouse::orderBy('code')->get();
        $whRts = $warehouses->firstWhere('code', 'WH-RTS');

        $stores = Store::orderBy('code')->get();

        $items = Item::query()
            ->where('type', 'finished_good')
            ->orderBy('code')
            ->get()
            ->map(function ($item) {
                $item->hpp_unit = (float) ($item->hpp ?? 0);
                return $item;
            });

        $prefilledLines = $shipment->lines
            ->filter(fn($line) => $line->item_id && ($line->qty_scanned ?? $line->qty ?? 0) > 0)
            ->values()
            ->map(function ($line) {
                $qty = $line->qty_scanned ?? $line->qty ?? 0;

                return [
                    'item_id' => $line->item_id,
                    'qty' => $qty,
                    'unit_price' => null,
                    'line_discount' => 0,
                ];
            })
            ->all();

        return view('sales.invoices.create', [
            'warehouses' => $warehouses,
            'stores' => $stores,
            'items' => $items,

            'sourceShipment' => $shipment,
            'defaultDate' => optional($shipment->date)->toDateString() ?? now()->toDateString(),
            'defaultWarehouseId' => $whRts?->id ?? ($shipment->warehouse_id ?? null),
            'defaultStoreId' => $shipment->store_id ?? null,
            'prefilledLines' => $prefilledLines,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'date' => ['required', 'date'],
            'customer_id' => ['nullable', 'exists:customers,id'],
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'remarks' => ['nullable', 'string'],
            'tax_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'header_discount' => ['nullable', 'numeric', 'min:0'],
            'store_id' => ['nullable', 'exists:stores,id'],
            'source_shipment_id' => ['nullable', 'exists:shipments,id'],

            'items' => ['required', 'array', 'min:1'],
            'items.*.item_id' => ['required', 'exists:items,id'],
            'items.*.qty' => ['required', 'numeric', 'min:0.01'],
            'items.*.unit_price' => ['nullable', 'numeric', 'min:0'],
            'items.*.line_discount' => ['nullable', 'numeric', 'min:0'],
        ]);

        $taxPercent = (float) ($data['tax_percent'] ?? 0);
        $headerDiscount = (float) ($data['header_discount'] ?? 0);
        $invoiceDate = $data['date'];
        $warehouseId = (int) $data['warehouse_id'];
        $sourceShipmentId = $data['source_shipment_id'] ?? null;

        $initialStatus = $this->salesInvoices->statusFromPricing($data['items']);

        $code = 'INV-' . now()->format('Ymd') . '-' . str_pad(
            (string) (SalesInvoice::count() + 1),
            3,
            '0',
            STR_PAD_LEFT
        );

        // ✅ preload HPP master (items.hpp) biar 1 query saja
        $hppMap = $this->salesInvoices->hppMap($data['items']);

        /** @var SalesInvoice $invoice */
        $invoice = DB::transaction(function () use (
            $data,
            $taxPercent,
            $headerDiscount,
            $invoiceDate,
            $warehouseId,
            $code,
            $initialStatus,
            $sourceShipmentId,
            $hppMap
        ) {
            $invoice = SalesInvoice::create([
                'code' => $code,
                'date' => $invoiceDate,
                'customer_id' => $data['customer_id'] ?? null,
                'store_id' => $data['store_id'] ?? null,
                'warehouse_id' => $warehouseId,
                'status' => $initialStatus,
                'remarks' => $data['remarks'] ?? null,
                'created_by' => auth()->id(),
                'tax_percent' => $taxPercent,
            ]);

            $built = $this->salesInvoices->buildLines($data['items'], $hppMap);

            foreach ($built['lines'] as $lineAttr) {
                $invoice->lines()->create($lineAttr);
            }

            $invoice->update(
                $this->salesInvoices->computeTotals($built['subtotal'], $headerDiscount, $taxPercent)
            );

            if ($sourceShipmentId) {
                $shipment = Shipment::find($sourceShipmentId);
                if ($shipment && !$shipment->sales_invoice_id) {
                    $shipment->sales_invoice_id = $invoice->id;
                    $shipment->save();
                }
            }

            return $invoice;
        });

        return redirect()
            ->route('sales.invoices.show', $invoice)
            ->with('success', "Invoice {$invoice->code} berhasil dibuat.");
    }

    public function show(SalesInvoice $invoice)
    {
        $invoice->load([
            'customer:id,name',
            'warehouse:id,code,name',
            'store:id,code,name',
            'lines.item:id,code,name',
            'shipments:id,sales_invoice_id,code,shipment_no,date,status,shipping_method,tracking_no',
        ]);

        return view('sales.invoices.show', compact('invoice'));
    }

    public function edit(SalesInvoice $invoice)
    {
        $invoice->load(['lines.item', 'customer', 'store', 'warehouse']);

        $customers = Customer::orderBy('name')->get();
        $warehouses = Warehouse::orderBy('code')->get();
        $stores = Store::orderBy('code')->get();

        $items = Item::query()
            ->where('type', 'finished_good')
            ->orderBy('code')
            ->get()
            ->map(function ($item) {
                $item->hpp_unit = (float) ($item->hpp ?? 0);
                return $item;
            });

        $prefilledLines = $invoice->lines->map(function (SalesInvoiceLine $line) {
            return [
                'item_id' => $line->item_id,
                'qty' => (float) $line->qty,
                'unit_price' => (float) $line->unit_price,
                'line_discount' => (float) $line->line_discount,
            ];
        })->all();

        return view('sales.invoices.edit', [
            'invoice' => $invoice,
            'customers' => $customers,
            'warehouses' => $warehouses,
            'stores' => $stores,
            'items' => $items,

            'sourceShipment' => null,
            'defaultDate' => optional($invoice->date)->toDateString() ?? now()->toDateString(),
            'defaultWarehouseId' => $invoice->warehouse_id,
            'defaultCustomerId' => $invoice->customer_id,
            'defaultStoreId' => $invoice->store_id,
            'prefilledLines' => $prefilledLines,
        ]);
    }

    public function update(Request $request, SalesInvoice $invoice)
    {
        if ($invoice->status === 'posted') {
            return back()->with('error', 'Invoice sudah posted, tidak bisa diedit.');
        }

        $data = $request->validate([
            'date' => ['required', 'date'],
            'customer_id' => ['nullable', 'exists:customers,id'],
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'remarks' => ['nullable', 'string'],
            'tax_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'header_discount' => ['nullable', 'numeric', 'min:0'],
            'store_id' => ['nullable', 'exists:stores,id'],

            'items' => ['required', 'array', 'min:1'],
            'items.*.item_id' => ['required', 'exists:items,id'],
            'items.*.qty' => ['required', 'numeric', 'min:0.01'],
            'items.*.unit_price' => ['nullable', 'numeric', 'min:0'],
            'items.*.line_discount' => ['nullable', 'numeric', 'min:0'],
        ]);

        $taxPercent = (float) ($data['tax_percent'] ?? 0);
        $headerDiscount = (float) ($data['header_discount'] ?? 0);
        $invoiceDate = $data['date'];
        $warehouseId = (int) $data['warehouse_id'];

        $newStatus = $this->salesInvoices->statusFromPricing($data['items']);

        // ✅ preload HPP master lagi
        $hppMap = $this->salesInvoices->hppMap($data['items']);

        DB::transaction(function () use (
            $invoice,
            $data,
            $taxPercent,
            $headerDiscount,
            $invoiceDate,
            $warehouseId,
            $newStatus,
            $hppMap
        ) {
            $invoice->update([
                'date' => $invoiceDate,
                'customer_id' => $data['customer_id'] ?? null,
                'store_id' => $data['store_id'] ?? null,
                'warehouse_id' => $warehouseId,
                'status' => $newStatus,
                'remarks' => $data['remarks'] ?? null,
                'tax_percent' => $taxPercent,
            ]);

            $invoice->lines()->delete();

            $built = $this->salesInvoices->buildLines($data['items'], $hppMap);

            foreach ($built['lines'] as $lineAttr) {
                $invoice->lines()->create($lineAttr);
            }

            $invoice->update(
                $this->salesInvoices->computeTotals($built['subtotal'], $headerDiscount, $taxPercent)
            );
        });

        return redirect()
            ->route('sales.invoices.show', $invoice)
            ->with('success', "Invoice {$invoice->code} berhasil diperbarui.");
    }

    public function post(SalesInvoice $invoice, SalesInvoiceAccountingService $accounting)
    {
        try {
            $accounting->post($invoice, auth()->id());
        } catch (ValidationException $e) {
            return back()
                ->withErrors($e->errors())
                ->with('error', collect($e->errors())->flatten()->first());
        } catch (\Throwable $e) {
            report($e);
            return back()->with('error', 'Gagal memposting invoice: ' . $e->getMessage());
        }

        return back()->with('success', "Invoice {$invoice->code} berhasil diposting dan jurnal penjualan sudah dibuat.");
    }
}
