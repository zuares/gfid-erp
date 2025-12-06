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
use App\Services\Costing\HppService;
use App\Services\Inventory\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SalesInvoiceController extends Controller
{
    public function __construct(
        protected HppService $hpp,
        protected InventoryService $inventory, // disiapkan untuk integrasi stok/shipment
    ) {}

    /**
     * List invoice.
     */
    public function index()
    {
        $invoices = SalesInvoice::with(['customer', 'warehouse', 'store'])
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->paginate(25);

        return view('sales.invoices.index', compact('invoices'));
    }

    /**
     * Form create invoice biasa.
     */
    public function create()
    {
        $customers = Customer::orderBy('name')->get();
        $warehouses = Warehouse::orderBy('code')->get();
        $stores = Store::orderBy('code')->get();

        // Item FG + preload snapshot HPP aktif (final, dari ProductionCostPeriod aktif)
        $items = Item::query()
            ->where('type', 'finished_good')
            ->with('activeCostSnapshot') // relasi di model Item
            ->orderBy('code')
            ->get()
            ->map(function ($item) {
                $item->hpp_unit = $item->activeCostSnapshot?->unit_cost ?? 0.0;
                return $item;
            });

        // create biasa → tidak ada sourceShipment/prefill
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

    /**
     * Form create invoice dari Shipment (alur: Shipment → Invoice).
     */
    public function createFromShipment(Shipment $shipment)
    {
        // Load relasi yang dipakai di view (judul + info)
        $shipment->loadMissing([
            'lines.item',
            'store',
            'warehouse',
            // kalau nanti sudah ada relasi customer() di Shipment, bisa tambahkan 'customer'
        ]);

        // Ambil semua gudang (dipakai di view)
        $warehouses = Warehouse::orderBy('code')->get();

        // Cari WH-RTS untuk dijadikan gudang invoice
        $whRts = $warehouses instanceof \Illuminate\Support\Collection
        ? $warehouses->firstWhere('code', 'WH-RTS')
        : null;

        // Store / Channel list
        $stores = Store::orderBy('code')->get();

        // Item FG (tetap dikirim supaya bisa tambah item manual)
        $items = Item::query()
            ->where('type', 'finished_good')
            ->with('activeCostSnapshot')
            ->orderBy('code')
            ->get()
            ->map(function ($item) {
                $item->hpp_unit = $item->activeCostSnapshot?->unit_cost ?? 0.0;
                return $item;
            });

        // Prefill lines dari shipment: pakai qty_scanned kalau ada; fallback ke qty
        $prefilledLines = $shipment->lines
            ->filter(fn($line) => $line->item_id && ($line->qty_scanned ?? $line->qty ?? 0) > 0)
            ->values()
            ->map(function ($line) {
                $qty = $line->qty_scanned ?? $line->qty ?? 0;

                return [
                    'item_id' => $line->item_id,
                    'qty' => $qty,
                    'unit_price' => null, // piutang dagang → harga bisa diisi nanti
                    'line_discount' => 0,
                ];
            })
            ->all();

        return view('sales.invoices.create', [
            // dropdowns
            'warehouses' => $warehouses,
            'stores' => $stores,
            'items' => $items,

            // context dari Shipment
            'sourceShipment' => $shipment,
            'defaultDate' => optional($shipment->date)->toDateString() ?? now()->toDateString(),

            // 🔥 Gudang invoice = WH-RTS (kalau ada), fallback ke warehouse shipment
            'defaultWarehouseId' => $whRts?->id ?? ($shipment->warehouse_id ?? null),

            // Store / channel ikut shipment → nanti di blade di-lock (read-only)
            'defaultStoreId' => $shipment->store_id ?? null,

            // Lines prefilled
            'prefilledLines' => $prefilledLines,
        ]);
    }

    /**
     * Simpan invoice + line + hitung HPP & margin.
     * Sekarang support UNPRICED (unit_price boleh kosong).
     */
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

            // 🔥 kalau dari Shipment, kirim hidden source_shipment_id
            'source_shipment_id' => ['nullable', 'exists:shipments,id'],

            'items' => ['required', 'array', 'min:1'],
            'items.*.item_id' => ['required', 'exists:items,id'],
            // qty boleh desimal (di view tadi pakai step="0.01")
            'items.*.qty' => ['required', 'numeric', 'min:0.01'],
            // unit_price boleh kosong → invoice UNPRICED
            'items.*.unit_price' => ['nullable', 'numeric', 'min:0'],
            'items.*.line_discount' => ['nullable', 'numeric', 'min:0'],
        ]);

        $taxPercent = (float) ($data['tax_percent'] ?? 0);
        $headerDiscount = (float) ($data['header_discount'] ?? 0);
        $invoiceDate = $data['date'];
        $warehouseId = (int) $data['warehouse_id']; // biasanya WH-RTS
        $sourceShipmentId = $data['source_shipment_id'] ?? null;

        // Cek apakah ada minimal satu line yang punya harga > 0
        $hasAnyPrice = collect($data['items'])->contains(function ($row) {
            if (!isset($row['unit_price']) || $row['unit_price'] === '' || $row['unit_price'] === null) {
                return false;
            }
            return (float) $row['unit_price'] > 0;
        });

        // Kalau tidak ada harga sama sekali → status UNPRICED, selain itu DRAFT
        $initialStatus = $hasAnyPrice ? 'draft' : 'unpriced';

        // Sederhana dulu, nanti bisa pakai generator terpisah
        $code = 'INV-' . now()->format('Ymd') . '-' . str_pad(
            SalesInvoice::count() + 1,
            3,
            '0',
            STR_PAD_LEFT
        );

        /** @var \App\Models\SalesInvoice $invoice */
        $invoice = DB::transaction(function () use (
            $data,
            $taxPercent,
            $headerDiscount,
            $invoiceDate,
            $warehouseId,
            $code,
            $initialStatus,
            $sourceShipmentId,
        ) {
            // 1️⃣ Buat header invoice
            $invoice = SalesInvoice::create([
                'code' => $code,
                'date' => $invoiceDate,
                'customer_id' => $data['customer_id'] ?? null,
                'store_id' => $data['store_id'] ?? null,
                'warehouse_id' => $warehouseId,
                'status' => $initialStatus, // draft / unpriced
                'remarks' => $data['remarks'] ?? null,
                'created_by' => auth()->id(),
                'tax_percent' => $taxPercent,
            ]);

            // 2️⃣ Detail line + HPP + margin
            $subtotal = 0.0;

            foreach ($data['items'] as $row) {
                $itemId = (int) $row['item_id'];
                $qty = (float) $row['qty']; // boleh desimal

                // kalau kosong/null → anggap 0
                $unitPriceRaw = $row['unit_price'] ?? null;
                $unitPrice = $unitPriceRaw !== null && $unitPriceRaw !== '' ? (float) $unitPriceRaw : 0.0;

                $lineDiscount = (float) ($row['line_discount'] ?? 0.0);

                $lineTotal = max(0, ($qty * $unitPrice) - $lineDiscount);
                $subtotal += $lineTotal;

                // 🔥 Ambil HPP FINAL aktif (ProductionCostPeriod aktif) via HppService
                $hppSnapshot = $this->hpp->getActiveFinalHppForItem($itemId, $warehouseId);
                $hppUnit = $hppSnapshot?->unit_cost ?? 0.0;

                // Hitung margin berbasis HPP final
                $costTotal = $hppUnit * $qty;
                $marginTotal = $lineTotal - $costTotal;
                $marginUnit = $qty > 0 ? $marginTotal / $qty : 0.0;

                SalesInvoiceLine::create([
                    'sales_invoice_id' => $invoice->id,
                    'item_id' => $itemId,
                    'qty' => $qty,
                    'unit_price' => $unitPrice,
                    'line_discount' => $lineDiscount,
                    'line_total' => $lineTotal,
                    'hpp_unit_snapshot' => $hppUnit,
                    'margin_unit' => $marginUnit,
                    'margin_total' => $marginTotal,
                ]);
            }

            // 3️⃣ Hitung ringkasan header (subtotal, diskon, pajak, grand total)
            $discountTotal = min($headerDiscount, $subtotal);
            $dpp = $subtotal - $discountTotal;

            $taxAmount = $taxPercent > 0 ? round($dpp * $taxPercent / 100, 2) : 0.0;
            $grandTotal = $dpp + $taxAmount;

            $invoice->update([
                'subtotal' => $subtotal,
                'discount_total' => $discountTotal,
                'tax_amount' => $taxAmount,
                'grand_total' => $grandTotal,
            ]);

            // 4️⃣ Kalau invoice ini dibuat dari Shipment → update shipments.sales_invoice_id
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

    /**
     * Detail invoice (+ relasi shipment).
     */
    public function show(SalesInvoice $invoice)
    {
        $invoice->load([
            'customer',
            'warehouse',
            'store',
            'lines.item',
            'shipments', // relasi ke Shipment (kalau sudah dibuat: hasMany)
        ]);

        return view('sales.invoices.show', compact('invoice'));
    }

    /**
     * Form EDIT: untuk melengkapi harga invoice UNPRICED / revisi harga.
     */
    public function edit(SalesInvoice $invoice)
    {
        $invoice->load(['lines.item', 'customer', 'store', 'warehouse']);

        $customers = Customer::orderBy('name')->get();
        $warehouses = Warehouse::orderBy('code')->get();
        $stores = Store::orderBy('code')->get();

        $items = Item::query()
            ->where('type', 'finished_good')
            ->with('activeCostSnapshot')
            ->orderBy('code')
            ->get()
            ->map(function ($item) {
                $item->hpp_unit = $item->activeCostSnapshot?->unit_cost ?? 0.0;
                return $item;
            });

        // siapkan initialLines dari invoice lines
        $prefilledLines = $invoice->lines->map(function (SalesInvoiceLine $line) {
            return [
                'item_id' => $line->item_id,
                'qty' => $line->qty,
                'unit_price' => $line->unit_price,
                'line_discount' => $line->line_discount,
            ];
        })->all();

        return view('sales.invoices.edit', [
            'invoice' => $invoice,
            'customers' => $customers,
            'warehouses' => $warehouses,
            'stores' => $stores,
            'items' => $items,

            'sourceShipment' => null, // edit invoice biasa
            'defaultDate' => optional($invoice->date)->toDateString() ?? now()->toDateString(),
            'defaultWarehouseId' => $invoice->warehouse_id,
            'defaultCustomerId' => $invoice->customer_id,
            'defaultStoreId' => $invoice->store_id,
            'prefilledLines' => $prefilledLines,
        ]);
    }

    /**
     * Update invoice (isi/ubah harga, qty, dsb).
     * Logic hampir sama dengan store(), tapi mengupdate + hapus ulang lines.
     */
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
            'items.*.qty' => ['required', 'integer', 'min:1'],
            'items.*.unit_price' => ['nullable', 'numeric', 'min:0'],
            'items.*.line_discount' => ['nullable', 'numeric', 'min:0'],
        ]);

        $taxPercent = (float) ($data['tax_percent'] ?? 0);
        $headerDiscount = (float) ($data['header_discount'] ?? 0);
        $invoiceDate = $data['date'];
        $warehouseId = (int) $data['warehouse_id'];

        // cek apakah ada harga jual
        $hasAnyPrice = collect($data['items'])->contains(function ($row) {
            if (!isset($row['unit_price']) || $row['unit_price'] === '' || $row['unit_price'] === null) {
                return false;
            }
            return (float) $row['unit_price'] > 0;
        });

        $newStatus = $hasAnyPrice ? 'draft' : 'unpriced';

        DB::transaction(function () use (
            $invoice,
            $data,
            $taxPercent,
            $headerDiscount,
            $invoiceDate,
            $warehouseId,
            $newStatus
        ) {
            // update header basic
            $invoice->update([
                'date' => $invoiceDate,
                'customer_id' => $data['customer_id'] ?? null,
                'store_id' => $data['store_id'] ?? null,
                'warehouse_id' => $warehouseId,
                'status' => $newStatus,
                'remarks' => $data['remarks'] ?? null,
                'tax_percent' => $taxPercent,
            ]);

            // hapus semua lines lama
            $invoice->lines()->delete();

            // rebuild lines
            $subtotal = 0.0;

            foreach ($data['items'] as $row) {
                $itemId = (int) $row['item_id'];
                $qty = (int) $row['qty'];
                $unitPriceRaw = $row['unit_price'] ?? null;
                $unitPrice = $unitPriceRaw !== null && $unitPriceRaw !== '' ? (float) $unitPriceRaw : 0.0;
                $lineDiscount = (float) ($row['line_discount'] ?? 0.0);

                $lineTotal = max(0, ($qty * $unitPrice) - $lineDiscount);
                $subtotal += $lineTotal;

                $hppSnapshot = $this->hpp->getActiveFinalHppForItem($itemId, $warehouseId);
                $hppUnit = $hppSnapshot?->unit_cost ?? 0.0;

                $costTotal = $hppUnit * $qty;
                $marginTotal = $lineTotal - $costTotal;
                $marginUnit = $qty > 0 ? $marginTotal / $qty : 0.0;

                SalesInvoiceLine::create([
                    'sales_invoice_id' => $invoice->id,
                    'item_id' => $itemId,
                    'qty' => $qty,
                    'unit_price' => $unitPrice,
                    'line_discount' => $lineDiscount,
                    'line_total' => $lineTotal,
                    'hpp_unit_snapshot' => $hppUnit,
                    'margin_unit' => $marginUnit,
                    'margin_total' => $marginTotal,
                ]);
            }

            $discountTotal = min($headerDiscount, $subtotal);
            $dpp = $subtotal - $discountTotal;

            $taxAmount = $taxPercent > 0 ? round($dpp * $taxPercent / 100, 2) : 0.0;
            $grandTotal = $dpp + $taxAmount;

            $invoice->update([
                'subtotal' => $subtotal,
                'discount_total' => $discountTotal,
                'tax_amount' => $taxAmount,
                'grand_total' => $grandTotal,
            ]);
        });

        return redirect()
            ->route('sales.invoices.show', $invoice)
            ->with('success', "Invoice {$invoice->code} berhasil diperbarui.");
    }

    /**
     * Posting invoice → hanya lock status.
     * Stok akan berkurang saat Shipment di-post.
     */
    public function post(SalesInvoice $invoice)
    {
        if ($invoice->status === 'posted') {
            return back()->with('info', "Invoice {$invoice->code} sudah berstatus posted.");
        }

        $invoice->load('lines');

        if ($invoice->lines->isEmpty()) {
            return back()->with('error', 'Invoice tidak memiliki item, tidak bisa diposting.');
        }

        // 🔥 cegah posting kalau masih UNPRICED atau grand_total 0
        if ($invoice->status === 'unpriced') {
            return back()->with('error', 'Invoice masih UNPRICED (harga belum diisi). Lengkapi harga sebelum posting.');
        }

        if (($invoice->grand_total ?? 0) <= 0) {
            return back()->with('error', 'Grand total invoice 0. Lengkapi harga sebelum posting.');
        }

        try {
            DB::transaction(function () use ($invoice) {
                $invoice->update([
                    'status' => 'posted',
                ]);
            });
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal memposting invoice: ' . $e->getMessage());
        }

        return back()->with(
            'success',
            "Invoice {$invoice->code} berhasil diposting. Stok akan berkurang saat Shipment diposting dari WH-RTS."
        );
    }
}
