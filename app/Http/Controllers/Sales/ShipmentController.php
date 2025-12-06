<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\SalesInvoice;
use App\Models\Shipment;
use App\Models\ShipmentLine;
use App\Models\Store;
use App\Models\Warehouse;
use App\Services\Inventory\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ShipmentController extends Controller
{
    public function __construct(
        protected InventoryService $inventory
    ) {}

    public function index(Request $request)
    {
        $shipments = Shipment::with('store')
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->paginate(20);

        return view('sales.shipments.index', compact('shipments'));
    }

    public function create(Request $request)
    {
        $stores = Store::orderBy('code')->get();
        $whRts = Warehouse::where('code', 'WH-RTS')->first();

        $invoice = null;

        // Terima dari query ?sales_invoice_id=... (dari "create invoice from shipment")
        if ($request->filled('sales_invoice_id')) {
            $invoice = SalesInvoice::with('store')
                ->find($request->sales_invoice_id);
        }
        // Backward compatibility: masih bisa pakai ?invoice_id=...
        elseif ($request->filled('invoice_id')) {
            $invoice = SalesInvoice::with('store')
                ->find($request->invoice_id);
        }

        return view('sales.shipments.create', [
            'stores' => $stores,
            'whRts' => $whRts,
            'invoice' => $invoice,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'store_id' => ['required', 'exists:stores,id'],
            'date' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
            'sales_invoice_id' => ['nullable', 'exists:sales_invoices,id'],
        ]);

        // 🔥 Ambil store untuk tentukan prefix kode
        $store = Store::findOrFail($data['store_id']);

        $storeName = strtoupper(trim($store->name ?? ''));
        $storeCode = strtoupper(trim($store->code ?? ''));
        $storeKey = $storeCode . ' ' . $storeName;

        // Default prefix dari kode store (3 huruf pertama)
        $prefix = 'SHP';

        // Kalau kode store ada, pakai 3 huruf pertama sebagai default
        if ($storeCode !== '') {
            // Ambil hanya huruf/angka, lalu potong 3 karakter
            $cleanCode = preg_replace('/[^A-Z0-9]/', '', $storeCode);
            if ($cleanCode !== '') {
                $prefix = substr($cleanCode, 0, 3);
            }
        }

        // Override khusus Shopee / TikTok
        if (str_contains($storeKey, 'SHP') || str_contains($storeKey, 'SHOPEE')) {
            $prefix = 'SHP';
        } elseif (str_contains($storeKey, 'TTK') || str_contains($storeKey, 'TIKTOK')) {
            $prefix = 'TTK';
        }

        // Generate kode dengan prefix sesuai channel
        $code = Shipment::generateCode($prefix);

        $shipment = Shipment::create([
            'code' => $code,
            'store_id' => $data['store_id'],
            'sales_invoice_id' => $data['sales_invoice_id'] ?? null,
            'date' => $data['date'],
            'status' => 'draft',
            'notes' => $data['notes'] ?? null,
            'created_by' => Auth::id(),
        ]);

        return redirect()
            ->route('sales.shipments.show', $shipment)
            ->with('status', 'success')
            ->with('message', 'Shipment dibuat. Silakan scan barang.');
    }

    public function show(Shipment $shipment)
    {
        $shipment->load(['store', 'lines.item', 'creator', 'invoice']);

        return view('sales.shipments.show', compact('shipment'));
    }

    /**
     * Scan item → tambah / update line.
     */
    public function scanItem(Request $request, Shipment $shipment)
    {
        if ($shipment->status !== 'draft') {
            $message = 'Shipment sudah tidak bisa discan (bukan draft).';

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $message,
                ], 409);
            }

            return back()
                ->with('status', 'error')
                ->with('message', $message);
        }

        $data = $request->validate([
            'scan_code' => ['required', 'string', 'max:255'],
            'qty' => ['nullable', 'integer', 'min:1'],
        ]);

        // Paksa uppercase
        $scanCode = mb_strtoupper(trim($data['scan_code']));
        $qty = (int) ($data['qty'] ?? 1);
        if ($qty <= 0) {
            $qty = 1;
        }

        $item = Item::query()
            ->where('type', 'finished_good')
            ->where(function ($q) use ($scanCode) {
                $q->where('barcode', $scanCode)
                    ->orWhere('code', $scanCode);
            })
            ->first();

        if (!$item) {
            $message = "Item dengan kode/barcode {$scanCode} tidak ditemukan atau bukan finished_good.";

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $message,
                ], 422);
            }

            return back()
                ->with('status', 'error')
                ->with('message', $message)
                ->withInput();
        }

        $result = DB::transaction(function () use ($shipment, $item, $qty) {
            /** @var \App\Models\ShipmentLine|null $line */
            $line = ShipmentLine::query()
                ->where('shipment_id', $shipment->id)
                ->where('item_id', $item->id)
                ->lockForUpdate()
                ->first();

            if ($line) {
                $line->qty_scanned = (int) $line->qty_scanned + $qty;
                $line->save();
            } else {
                $line = ShipmentLine::create([
                    'shipment_id' => $shipment->id,
                    'item_id' => $item->id,
                    'qty_scanned' => $qty,
                ]);
            }

            $totalQty = (int) ShipmentLine::where('shipment_id', $shipment->id)->sum('qty_scanned');
            $totalLines = (int) ShipmentLine::where('shipment_id', $shipment->id)->count();

            session()->put('last_scanned_line_id', $line->id);

            return [
                'line' => $line,
                'total_qty' => $totalQty,
                'total_lines' => $totalLines,
            ];
        });

        $line = $result['line'];
        $totalQty = $result['total_qty'];
        $totalLines = $result['total_lines'];

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'status' => 'ok',
                'message' => 'Berhasil scan ' . $item->code . ' (+' . $qty . ')',
                'last_scanned_line_id' => $line->id,
                'line' => [
                    'id' => $line->id,
                    'item_code' => $item->code,
                    'item_name' => $item->name,
                    'remarks' => $line->remarks ?? null,
                    'qty_scanned' => (int) $line->qty_scanned,
                    'update_qty_url' => route('sales.shipments.update_line_qty', $line),
                ],
                'totals' => [
                    'total_qty' => $totalQty,
                    'total_lines' => $totalLines,
                ],
            ]);
        }

        return redirect()
            ->route('sales.shipments.show', $shipment)
            ->with('last_scanned_line_id', $line->id);
    }

    /**
     * Submit shipment (lock scan, belum stock out).
     */
    public function submit(Request $request, Shipment $shipment)
    {
        if ($shipment->status !== 'draft') {
            return back()
                ->with('status', 'error')
                ->with('message', 'Hanya shipment draft yang bisa di-submit.');
        }

        if ($shipment->lines()->count() === 0) {
            return back()
                ->with('status', 'error')
                ->with('message', 'Tidak ada item di shipment ini.');
        }

        $shipment->status = 'submitted';

        // Kalau nanti sudah ada kolom submitted_at / submitted_by di DB + fillable,
        // bisa dihidupkan lagi ini:
        $shipment->submitted_at = now();
        $shipment->submitted_by = auth()->id();

        $shipment->save();

        return redirect()
            ->route('sales.shipments.show', $shipment)
            ->with('status', 'success')
            ->with('message', 'Shipment disubmit. Tidak bisa discan lagi, siap untuk posting stok.');
    }

    /**
     * Posting shipment → stock out dari WH-RTS.
     */
    public function post(Request $request, Shipment $shipment)
    {
        if ($shipment->status === 'posted') {
            return back()
                ->with('status', 'error')
                ->with('message', 'Shipment sudah diposting sebelumnya.');
        }

        if ($shipment->status !== 'submitted') {
            return back()
                ->with('status', 'error')
                ->with('message', 'Shipment harus berstatus submitted sebelum diposting.');
        }

        $shipment->load(['lines.item', 'store']);

        if ($shipment->lines->isEmpty()) {
            return back()
                ->with('status', 'error')
                ->with('message', 'Tidak ada item di shipment ini.');
        }

        $warehouse = Warehouse::where('code', 'WH-RTS')->first();

        if (!$warehouse) {
            return back()
                ->with('status', 'error')
                ->with('message', 'Warehouse WH-RTS belum dikonfigurasi.');
        }

        DB::transaction(function () use ($shipment, $warehouse) {
            $totalQty = 0;

            foreach ($shipment->lines as $line) {
                $qty = (int) $line->qty_scanned;
                if ($qty <= 0) {
                    continue;
                }

                $totalQty += $qty;

                // Kurangi stok FG dari WH-RTS → SELARAS dengan InventoryService & laporan lain
                $this->inventory->stockOut(
                    warehouseId: $warehouse->id,
                    itemId: $line->item_id,
                    qty: $qty,
                    date: $shipment->date,
                    sourceType: 'shipment',
                    sourceId: $shipment->id,
                    notes: 'Shipment ' . $shipment->code . ' ke store ' . ($shipment->store->code ?? '-'),
                    allowNegative: false, // stok FG tidak boleh minus
                    lotId: null, // FG tidak pakai LOT
                    unitCostOverride: null, // biarkan pakai avg cost FG di WH-RTS
                    affectLotCost: false, // jangan sentuh LotCost (bukan kain mentah)
                );
            }

            $shipment->status = 'posted';
            $shipment->total_qty = $totalQty;

            // Kalau nanti ada kolom posted_at / posted_by di DB:
            // $shipment->posted_at = now();
            // $shipment->posted_by = auth()->id();

            $shipment->save();
        });

        return redirect()
            ->route('sales.shipments.show', $shipment)
            ->with('status', 'success')
            ->with('message', 'Shipment berhasil diposting & stok berkurang dari WH-RTS.');
    }

    /**
     * Inline update qty (support AJAX).
     */
    public function updateLineQty(Request $request, ShipmentLine $line)
    {
        $shipment = $line->shipment;

        if (!$shipment || $shipment->status !== 'draft') {
            $message = 'Shipment sudah tidak draft, qty tidak bisa diubah.';

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $message,
                ], 409);
            }

            return back()
                ->with('status', 'error')
                ->with('message', $message);
        }

        $data = $request->validate([
            'qty' => ['required', 'integer', 'min:0'],
        ]);

        $qty = (int) $data['qty'];

        DB::transaction(function () use (&$line, $qty) {
            if ($qty === 0) {
                $line->delete();
            } else {
                $line->qty_scanned = $qty;
                $line->save();
            }
        });

        $totalQty = (int) ShipmentLine::where('shipment_id', $shipment->id)->sum('qty_scanned');
        $totalLines = (int) ShipmentLine::where('shipment_id', $shipment->id)->count();

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'status' => 'ok',
                'message' => 'Qty berhasil diperbarui.',
                'deleted' => $qty === 0,
                'qty' => $qty,
                'totals' => [
                    'total_qty' => $totalQty,
                    'total_lines' => $totalLines,
                ],
            ]);
        }

        return back()
            ->with('status', 'success')
            ->with('message', 'Qty berhasil diperbarui.');
    }

    /**
     * Opsional: placeholder syncScans, kalau masih belum dipakai.
     */
    public function syncScans(Request $request, Shipment $shipment)
    {
        return back()
            ->with('status', 'error')
            ->with('message', 'Fitur sync scans belum diimplementasi.');
    }
}
