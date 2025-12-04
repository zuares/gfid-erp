<?php

// app/Http/Controllers/Sales/SalesInvoiceController.php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Item;
use App\Models\ItemCostSnapshot;
use App\Models\SalesInvoice;
use App\Models\SalesInvoiceLine;
use App\Models\Warehouse;
use App\Services\Inventory\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SalesInvoiceController extends Controller
{

    public function __construct(
        protected InventoryService $inventory,
    ) {}

    public function index(Request $request)
    {
        $query = SalesInvoice::query()
            ->with(['customer', 'warehouse'])
            ->orderByDesc('date')
            ->orderByDesc('id');

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($q = $request->input('q')) {
            $query->where(function ($sub) use ($q) {
                $sub->where('code', 'like', '%' . $q . '%')
                    ->orWhereHas('customer', function ($q2) use ($q) {
                        $q2->where('name', 'like', '%' . $q . '%');
                    });
            });
        }

        $invoices = $query->paginate(25)->withQueryString();

        return view('sales.invoices.index', compact('invoices'));
    }

    public function create()
    {
        $invoice = null;

        $warehouses = Warehouse::orderBy('code')->get();
        $customers = Customer::orderBy('name')->limit(50)->get(); // optional; quick pick

        return view('sales.invoices.create', compact('invoice', 'warehouses', 'customers'));
    }

    public function show(SalesInvoice $invoice)
    {
        $invoice->load(['customer', 'warehouse', 'lines.item']);

        return view('sales.invoices.show', compact('invoice'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'date' => ['required', 'date'],
            'warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],
            'customer_id' => ['nullable', 'integer', 'exists:customers,id'],
            'remarks' => ['nullable', 'string'],

            'subtotal' => ['nullable', 'numeric'],

            'lines' => ['required', 'array'],
            'lines.*.item_id' => ['nullable', 'integer', 'exists:items,id'],
            'lines.*.qty' => ['nullable', 'numeric', 'min:0'],
            'lines.*.unit_price' => ['nullable', 'numeric', 'min:0'],
            'lines.*.line_discount' => ['nullable', 'numeric', 'min:0'],
        ]);

        $rawLines = $data['lines'] ?? [];

        // 🔎 1) Filter baris kosong: tidak ada item_id atau qty <= 0
        $lines = [];
        foreach ($rawLines as $row) {
            $itemId = $row['item_id'] ?? null;
            $qty = (float) ($row['qty'] ?? 0);
            $price = (float) ($row['unit_price'] ?? 0);

            if (!$itemId || $qty <= 0 || $price < 0) {
                continue;
            }

            $lines[] = $row;
        }

        if (count($lines) === 0) {
            return back()
                ->withErrors(['lines' => 'Minimal satu item harus diisi dengan qty > 0.'])
                ->withInput();
        }

        $invoice = null;

        DB::transaction(function () use (&$invoice, $data, $lines) {
            $invoiceDate = Carbon::parse($data['date']);
            $warehouseId = (int) $data['warehouse_id'];
            $customerId = $data['customer_id'] ?? null;
            $remarks = $data['remarks'] ?? null;

            // 🔢 2) Generate kode invoice (SI-YYYYMM-###)
            $code = $this->generateCode($invoiceDate);

            // 🧾 3) Buat header (status langsung 'posted' untuk v1)
            $invoice = SalesInvoice::create([
                'code' => $code,
                'date' => $invoiceDate,
                'customer_id' => $customerId,
                'marketplace_order_id' => null, // nanti bisa diisi jika link ke order marketplace
                'warehouse_id' => $warehouseId,
                'status' => 'posted',

                'subtotal' => 0,
                'discount_total' => 0,
                'tax_percent' => 0,
                'tax_amount' => 0,
                'grand_total' => 0,
                'currency' => 'IDR',
                'remarks' => $remarks,
                'created_by' => Auth::id(),
            ]);

            $subtotal = 0;
            $lineNo = 1;

            // 🧮 4) Loop lines → simpan detail + hitung subtotal + lock HPP + stock out
            foreach ($lines as $row) {
                $itemId = (int) $row['item_id'];
                $qty = (float) $row['qty'];
                $price = (float) $row['unit_price'];
                $lineDiscount = (float) ($row['line_discount'] ?? 0);

                $gross = $qty * $price;
                $lineTotal = max(0, $gross - $lineDiscount);

                /** @var \App\Models\Item $item */
                $item = Item::findOrFail($itemId);

                // 🔐 4a) Cari HPP unit dari item_cost_snapshots (snapshot ≤ tanggal invoice)
                $hppUnit = $this->findHppUnitForItem($itemId, $warehouseId, $invoiceDate);

                $hppTotal = $hppUnit * $qty;

                // 4b) Simpan line
                $line = SalesInvoiceLine::create([
                    'sales_invoice_id' => $invoice->id,
                    'line_no' => $lineNo++,
                    'item_id' => $item->id,
                    'item_code_snapshot' => $item->code,
                    'item_name_snapshot' => $item->name,
                    'qty' => $qty,
                    'unit_price' => $price,
                    'line_discount' => $lineDiscount,
                    'line_total' => $lineTotal,
                    'hpp_unit_snapshot' => $hppUnit,
                    'hpp_total_snapshot' => $hppTotal,
                    'warehouse_id' => $warehouseId,
                    'lot_id' => null, // kalau nanti mau per LOT bisa diisi
                ]);

                // 4c) Tambah ke subtotal header
                $subtotal += $lineTotal;

                // 4d) Stock OUT dari inventory
                $this->inventory->stockOut(
                    date: $invoiceDate,
                    warehouseId: $warehouseId,
                    itemId: $item->id,
                    qty: $qty,
                    sourceType: 'sales_invoice',
                    sourceId: $invoice->id,
                    remarks: 'Sales Invoice ' . $invoice->code . ' line ' . $line->line_no,
                    lotId: null,
                    unitCostOverride: $hppUnit, // SESUAIKAN kalau method-mu beda
                );
            }

            // 🧾 5) Hitung total header (v1: tanpa PPN & header discount)
            $discountHeader = 0;
            $taxPercent = 0;
            $taxAmount = 0;
            $grandTotal = $subtotal - $discountHeader + $taxAmount;

            $invoice->update([
                'subtotal' => $subtotal,
                'discount_total' => $discountHeader,
                'tax_percent' => $taxPercent,
                'tax_amount' => $taxAmount,
                'grand_total' => $grandTotal,
            ]);
        });

        return redirect()
            ->route('sales.invoices.show', $invoice)
            ->with('success', 'Sales Invoice berhasil dibuat & stok sudah berkurang.');
    }

    /**
     * Generate kode invoice: SI-YYYYMM-###
     */
    protected function generateCode(Carbon $date): string
    {
        $prefix = 'SI-' . $date->format('Ym') . '-';

        $last = SalesInvoice::query()
            ->where('code', 'like', $prefix . '%')
            ->orderByDesc('code')
            ->first();

        $lastNumber = 0;

        if ($last) {
            $parts = explode('-', $last->code);
            $lastNumber = (int) ($parts[2] ?? 0);
        }

        $next = $lastNumber + 1;

        return $prefix . str_pad((string) $next, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Cari HPP per unit dari ItemCostSnapshot untuk item & warehouse tertentu,
     * dengan snapshot_date ≤ tanggal invoice, urut paling baru.
     *
     * Kalau tidak ketemu, return 0.
     */
    protected function findHppUnitForItem(int $itemId, int $warehouseId, Carbon $date): float
    {
        $snapshot = ItemCostSnapshot::query()
            ->where('item_id', $itemId)
        // kalau kamu simpan per warehouse:
            ->where(function ($q) use ($warehouseId) {
                $q->whereNull('warehouse_id')
                    ->orWhere('warehouse_id', $warehouseId);
            })
            ->where('snapshot_date', '<=', $date->toDateTimeString())
            ->orderByDesc('snapshot_date')
            ->orderByDesc('id')
            ->first();

        if (!$snapshot) {
            return 0.0;
        }

        // ⚠️ SESUAIKAN nama kolom HPP:
        // misalnya: $snapshot->hpp_unit atau $snapshot->unit_cost dll.
        $hppUnit = $snapshot->hpp_unit ?? $snapshot->unit_cost ?? 0;

        return (float) $hppUnit;
    }

    public function itemProfit(Request $request)
    {
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');
        $customerId = $request->input('customer_id');
        $warehouseId = $request->input('warehouse_id');
        $storeId = $request->input('store_id'); // channel marketplace

        // Default periode: bulan berjalan kalau kosong
        if (!$dateFrom && !$dateTo) {
            $dateFrom = now()->startOfMonth()->toDateString();
            $dateTo = now()->endOfMonth()->toDateString();
        }

        // Normalisasi ke Carbon
        $from = $dateFrom ? Carbon::parse($dateFrom)->startOfDay() : null;
        $to = $dateTo ? Carbon::parse($dateTo)->endOfDay() : null;

        $baseQuery = SalesInvoiceLine::query()
            ->select([
                'sales_invoice_lines.item_id',
                DB::raw('SUM(sales_invoice_lines.qty) as total_qty'),
                DB::raw('SUM(sales_invoice_lines.line_total) as total_revenue'),
                DB::raw('SUM(sales_invoice_lines.hpp_total_snapshot) as total_hpp'),
            ])
            ->join('sales_invoices as si', 'si.id', '=', 'sales_invoice_lines.sales_invoice_id')
            ->where('si.status', 'posted');

        // Filter periode
        if ($from) {
            $baseQuery->where('si.date', '>=', $from);
        }
        if ($to) {
            $baseQuery->where('si.date', '<=', $to);
        }

        // Filter customer (opsional)
        if ($customerId) {
            $baseQuery->where('si.customer_id', $customerId);
        }

        // Filter warehouse (asal stok)
        if ($warehouseId) {
            // bisa pakai warehouse di header atau di line, pilih salah satu
            $baseQuery->where('sales_invoice_lines.warehouse_id', $warehouseId);
            // atau:
            // $baseQuery->where('si.warehouse_id', $warehouseId);
        }

        // Filter channel marketplace (store)
        if ($storeId) {
            // join ke orders marketplace via marketplace_order_id
            $baseQuery
                ->join('marketplace_orders as mo', 'mo.id', '=', 'si.marketplace_order_id')
                ->where('mo.store_id', $storeId);
        }

        $baseQuery->groupBy('sales_invoice_lines.item_id');

        $rawRows = $baseQuery->get();

        $itemIds = $rawRows->pluck('item_id')->filter()->unique()->values();
        $items = Item::whereIn('id', $itemIds)->get()->keyBy('id');

        $rows = $rawRows->map(function ($row) use ($items) {
            $item = $items->get($row->item_id);

            $qty = (float) $row->total_qty;
            $revenue = (float) $row->total_revenue;
            $hpp = (float) $row->total_hpp;
            $margin = $revenue - $hpp;

            $avgPrice = $qty > 0 ? $revenue / $qty : 0;
            $avgHpp = $qty > 0 ? $hpp / $qty : 0;
            $marginPerUnit = $qty > 0 ? $margin / $qty : 0;
            $marginPct = $revenue > 0 ? ($margin / $revenue) * 100 : 0;

            return (object) [
                'item_id' => $row->item_id,
                'item' => $item,
                'total_qty' => $qty,
                'total_revenue' => $revenue,
                'total_hpp' => $hpp,
                'total_margin' => $margin,
                'avg_price' => $avgPrice,
                'avg_hpp' => $avgHpp,
                'margin_per_unit' => $marginPerUnit,
                'margin_pct' => $marginPct,
            ];
        })->sortBy('item.code ?? ""')->values();

        // Summary
        $summary = [
            'total_qty' => $rows->sum('total_qty'),
            'total_revenue' => $rows->sum('total_revenue'),
            'total_hpp' => $rows->sum('total_hpp'),
            'total_margin' => $rows->sum('total_margin'),
        ];

        $customers = Customer::orderBy('name')->get();
        $selectedCustomer = $customerId
        ? $customers->firstWhere('id', $customerId)
        : null;

        $warehouses = Warehouse::orderBy('code')->get();

        // master store marketplace (sesuaikan model & field)
        $stores = MarketplaceStore::orderBy('platform')
            ->orderBy('name')
            ->get();

        $selectedWarehouse = $warehouseId
        ? $warehouses->firstWhere('id', $warehouseId)
        : null;

        $selectedStore = $storeId
        ? $stores->firstWhere('id', $storeId)
        : null;

        return view('sales.reports.item_profit', [
            'rows' => $rows,
            'summary' => $summary,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'customers' => $customers,
            'selectedCustomer' => $selectedCustomer,
            'warehouses' => $warehouses,
            'stores' => $stores,
            'selectedWarehouse' => $selectedWarehouse,
            'selectedStore' => $selectedStore,
        ]);
    }

    public function channelProfit(Request $request)
    {
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');
        $warehouseId = $request->input('warehouse_id');
        $customerId = $request->input('customer_id');

        // Default periode: bulan berjalan
        if (!$dateFrom && !$dateTo) {
            $dateFrom = now()->startOfMonth()->toDateString();
            $dateTo = now()->endOfMonth()->toDateString();
        }

        $from = $dateFrom ? Carbon::parse($dateFrom)->startOfDay() : null;
        $to = $dateTo ? Carbon::parse($dateTo)->endOfDay() : null;

        $baseQuery = SalesInvoiceLine::query()
            ->select([
                'mo.store_id',
                DB::raw('COUNT(DISTINCT si.id) as invoice_count'),
                DB::raw('SUM(sales_invoice_lines.qty) as total_qty'),
                DB::raw('SUM(sales_invoice_lines.line_total) as total_revenue'),
                DB::raw('SUM(sales_invoice_lines.hpp_total_snapshot) as total_hpp'),
            ])
            ->join('sales_invoices as si', 'si.id', '=', 'sales_invoice_lines.sales_invoice_id')
            ->join('marketplace_orders as mo', 'mo.id', '=', 'si.marketplace_order_id')
            ->whereNotNull('si.marketplace_order_id') // hanya invoice yang linked ke marketplace
            ->where('si.status', 'posted');

        // Filter periode
        if ($from) {
            $baseQuery->where('si.date', '>=', $from);
        }
        if ($to) {
            $baseQuery->where('si.date', '<=', $to);
        }

        // Filter warehouse (opsional, stok keluar dari gudang tertentu)
        if ($warehouseId) {
            $baseQuery->where('sales_invoice_lines.warehouse_id', $warehouseId);
        }

        // Filter customer (opsional kalau mau lihat satu customer)
        if ($customerId) {
            $baseQuery->where('si.customer_id', $customerId);
        }

        $baseQuery->groupBy('mo.store_id');

        $rawRows = $baseQuery->get();

        $storeIds = $rawRows->pluck('store_id')->filter()->unique()->values();

        $stores = MarketplaceStore::whereIn('id', $storeIds)
            ->get()
            ->keyBy('id');

        $rows = $rawRows->map(function ($row) use ($stores) {
            $store = $stores->get($row->store_id);

            $revenue = (float) $row->total_revenue;
            $hpp = (float) $row->total_hpp;
            $margin = $revenue - $hpp;
            $marginPct = $revenue > 0 ? ($margin / $revenue) * 100 : 0;

            return (object) [
                'store_id' => $row->store_id,
                'store' => $store,
                'invoice_count' => (int) $row->invoice_count,
                'total_qty' => (float) $row->total_qty,
                'total_revenue' => $revenue,
                'total_hpp' => $hpp,
                'total_margin' => $margin,
                'margin_pct' => $marginPct,
            ];
        })->sortBy(function ($row) {
            $label = '';
            if ($row->store) {
                $label = ($row->store->platform ?? '') . ' ' . ($row->store->name ?? '');
            }
            return trim($label);
        })->values();

        // Summary global
        $summary = [
            'total_invoices' => $rows->sum('invoice_count'),
            'total_qty' => $rows->sum('total_qty'),
            'total_revenue' => $rows->sum('total_revenue'),
            'total_hpp' => $rows->sum('total_hpp'),
            'total_margin' => $rows->sum('total_margin'),
        ];

        $warehouses = Warehouse::orderBy('code')->get();
        $customers = Customer::orderBy('name')->get();

        $selectedWarehouse = $warehouseId
        ? $warehouses->firstWhere('id', $warehouseId)
        : null;

        $selectedCustomer = $customerId
        ? $customers->firstWhere('id', $customerId)
        : null;

        return view('sales.reports.channel_profit', [
            'rows' => $rows,
            'summary' => $summary,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'warehouses' => $warehouses,
            'customers' => $customers,
            'selectedWarehouse' => $selectedWarehouse,
            'selectedCustomer' => $selectedCustomer,
        ]);
    }

}
