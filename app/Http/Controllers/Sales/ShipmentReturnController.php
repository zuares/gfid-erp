<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\Shipment;
use App\Models\ShipmentLine;
use App\Models\ShipmentReturn;
use App\Models\ShipmentReturnLine;
use App\Models\Store;
use App\Models\Warehouse;
use App\Services\Inventory\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ShipmentReturnController extends Controller
{
    public function __construct(
        protected InventoryService $inventory
    ) {}

    /**
     * List retur pengiriman.
     */
    public function index(Request $request)
    {
        $returns = ShipmentReturn::with(['store', 'shipment'])
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->paginate(20);

        return view('sales.shipment_returns.index', compact('returns'));
    }

    /**
     * Form buat retur pengiriman.
     * Bisa optional dari shipment asal (?shipment_id=...).
     */
    public function create(Request $request)
    {
        $stores = Store::orderBy('code')->get();

        $shipment = null;
        if ($request->filled('shipment_id')) {
            $shipment = Shipment::with(['store', 'lines.item'])
                ->find($request->shipment_id);
        }

        return view('sales.shipment_returns.create', [
            'stores' => $stores,
            'shipment' => $shipment,
        ]);
    }

    /**
     * Simpan header retur pengiriman.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'store_id' => ['required', 'exists:stores,id'],
            'shipment_id' => ['nullable', 'exists:shipments,id'],
            'date' => ['required', 'date'],
            'reason' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
        ]);

        $store = Store::findOrFail($data['store_id']);

        $storeName = strtoupper(trim($store->name ?? ''));
        $storeCode = strtoupper(trim($store->code ?? ''));
        $storeKey = $storeCode . ' ' . $storeName;

        // Default prefix
        $prefix = 'RTP';

        // Kalau store punya code, bisa dipakai sebagai base prefix
        $cleanCode = preg_replace('/[^A-Z0-9]/', '', $storeCode);
        if ($cleanCode !== '') {
            // Mis: SHP → SHPR (retur)
            $prefix = substr($cleanCode, 0, 3) . 'R';
        }

        // Override khusus marketplace
        if (str_contains($storeKey, 'SHP') || str_contains($storeKey, 'SHOPEE')) {
            $prefix = 'SHR'; // Shopee Return
        } elseif (str_contains($storeKey, 'TTK') || str_contains($storeKey, 'TIKTOK')) {
            $prefix = 'TTR'; // TikTok Return
        }

        $code = ShipmentReturn::generateCode($prefix);

        $ret = ShipmentReturn::create([
            'code' => $code,
            'store_id' => $data['store_id'],
            'shipment_id' => $data['shipment_id'] ?? null,
            'date' => $data['date'],
            'status' => 'draft',
            'reason' => $data['reason'] ?? null,
            'notes' => $data['notes'] ?? null,
            'created_by' => Auth::id(),
        ]);

        return redirect()
            ->route('sales.shipment_returns.show', $ret)
            ->with('status', 'success')
            ->with('message', 'Retur pengiriman dibuat. Silakan scan barang yang kembali.');
    }

    /**
     * Detail retur pengiriman.
     */
    public function show(ShipmentReturn $shipmentReturn)
    {
        $shipmentReturn->load([
            'store',
            'shipment',
            'lines.item',
            'creator',
            'submittedBy',
            'postedBy',
        ]);

        return view('sales.shipment_returns.show', compact('shipmentReturn'));
    }

    /**
     * Scan item yang dikembalikan → tambah / update line retur.
     * Support AJAX.
     */
    public function scanItem(Request $request, ShipmentReturn $shipmentReturn)
    {
        if ($shipmentReturn->status !== 'draft') {
            $message = 'Retur sudah tidak bisa discan (bukan draft).';

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

        $result = DB::transaction(function () use ($shipmentReturn, $item, $qty) {
            /** @var \App\Models\ShipmentReturnLine|null $line */
            $line = ShipmentReturnLine::query()
                ->where('shipment_return_id', $shipmentReturn->id)
                ->where('item_id', $item->id)
                ->lockForUpdate()
                ->first();

            // Opsional: link ke shipment_line asal kalau ada shipment_id
            $shipmentLineId = null;
            if ($shipmentReturn->shipment_id) {
                $shipmentLine = ShipmentLine::query()
                    ->where('shipment_id', $shipmentReturn->shipment_id)
                    ->where('item_id', $item->id)
                    ->first();

                if ($shipmentLine) {
                    $shipmentLineId = $shipmentLine->id;
                }
            }

            if ($line) {
                $line->qty = (int) $line->qty + $qty;
                if ($shipmentLineId && !$line->shipment_line_id) {
                    $line->shipment_line_id = $shipmentLineId;
                }
                $line->save();
            } else {
                $line = ShipmentReturnLine::create([
                    'shipment_return_id' => $shipmentReturn->id,
                    'item_id' => $item->id,
                    'shipment_line_id' => $shipmentLineId,
                    'qty' => $qty,
                ]);
            }

            $totalQty = (int) ShipmentReturnLine::where('shipment_return_id', $shipmentReturn->id)->sum('qty');
            $totalLines = (int) ShipmentReturnLine::where('shipment_return_id', $shipmentReturn->id)->count();

            session()->put('last_scanned_return_line_id', $line->id);

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
                'message' => 'Berhasil scan retur ' . $item->code . ' (+' . $qty . ')',
                'last_scanned_line_id' => $line->id,
                'line' => [
                    'id' => $line->id,
                    'item_code' => $item->code,
                    'item_name' => $item->name,
                    'remarks' => $line->remarks ?? null,
                    'qty' => (int) $line->qty,
                    'update_qty_url' => route('sales.shipment_returns.update_line_qty', $line),
                ],
                'totals' => [
                    'total_qty' => $totalQty,
                    'total_lines' => $totalLines,
                ],
            ]);
        }

        return redirect()
            ->route('sales.shipment_returns.show', $shipmentReturn)
            ->with('last_scanned_return_line_id', $line->id);
    }

    /**
     * Submit retur (lock scan, belum stock in).
     */
    public function submit(Request $request, ShipmentReturn $shipmentReturn)
    {
        if ($shipmentReturn->status !== 'draft') {
            return back()
                ->with('status', 'error')
                ->with('message', 'Hanya retur draft yang bisa di-submit.');
        }

        if ($shipmentReturn->lines()->count() === 0) {
            return back()
                ->with('status', 'error')
                ->with('message', 'Tidak ada item di retur ini.');
        }

        $shipmentReturn->status = 'submitted';
        $shipmentReturn->submitted_at = now();
        $shipmentReturn->submitted_by = auth()->id();
        $shipmentReturn->save();

        return redirect()
            ->route('sales.shipment_returns.show', $shipmentReturn)
            ->with('status', 'success')
            ->with('message', 'Retur disubmit. Siap diposting ke WH-RTS.');
    }

    /**
     * Posting retur → stock in ke WH-RTS.
     */
    public function post(Request $request, ShipmentReturn $shipmentReturn)
    {
        if ($shipmentReturn->status === 'posted') {
            return back()
                ->with('status', 'error')
                ->with('message', 'Shipment retur sudah diposting sebelumnya.');
        }

        if ($shipmentReturn->status !== 'submitted') {
            return back()
                ->with('status', 'error')
                ->with('message', 'Shipment retur harus berstatus submitted sebelum diposting.');
        }

        $shipmentReturn->load(['lines.item', 'store']);

        if ($shipmentReturn->lines->isEmpty()) {
            return back()
                ->with('status', 'error')
                ->with('message', 'Tidak ada item di shipment retur ini.');
        }

        // ✅ Ambil WH-RTS (gudang FG untuk channel)
        $warehouse = Warehouse::where('code', 'WH-RTS')->first();

        if (!$warehouse) {
            return back()
                ->with('status', 'error')
                ->with('message', 'Warehouse WH-RTS belum dikonfigurasi.');
        }

        DB::transaction(function () use ($shipmentReturn, $warehouse) {
            $totalQty = 0;

            foreach ($shipmentReturn->lines as $line) {
                $qty = (int) $line->qty;

                if ($qty <= 0) {
                    continue;
                }

                $totalQty += $qty;

                // ✅ Ambil HPP dari master item
                $unitCost = optional($line->item)->hpp;

                // Opsional: kalau HPP null atau <= 0, bisa fallback ke null
                if ($unitCost !== null) {
                    $unitCost = (float) $unitCost;
                    if ($unitCost <= 0) {
                        $unitCost = null;
                    }
                }

                // ✅ Nambah stok FG ke WH-RTS dengan HPP dari item
                $this->inventory->stockIn(
                    warehouseId: $warehouse->id,
                    itemId: $line->item_id,
                    qty: $qty,
                    date: $shipmentReturn->date,
                    sourceType: 'shipment_return',
                    sourceId: $shipmentReturn->id,
                    notes: 'Retur shipment ' . ($shipmentReturn->code ?? $shipmentReturn->id) .
                    ' dari store ' . ($shipmentReturn->store->code ?? '-'),
                    lotId: null, // FG tidak pakai LOT
                    unitCost: $unitCost, // ⬅️ sekarang pakai kolom items.hpp
                    affectLotCost: false, // tetap jangan sentuh LotCost kain
                );
            }

            $shipmentReturn->status = 'posted';
            $shipmentReturn->total_qty = $totalQty;

            // Kalau nanti ada kolom posted_at / posted_by:
            // $shipmentReturn->posted_at = now();
            // $shipmentReturn->posted_by = auth()->id();

            $shipmentReturn->save();
        });

        return redirect()
            ->route('sales.shipment_returns.show', $shipmentReturn)
            ->with('status', 'success')
            ->with('message', 'Shipment retur berhasil diposting & stok bertambah di WH-RTS.');
    }

    /**
     * Inline update qty line retur (support AJAX).
     */
    public function updateLineQty(Request $request, ShipmentReturnLine $line)
    {
        $header = $line->header;

        if (!$header || $header->status !== 'draft') {
            $message = 'Retur sudah tidak draft, qty tidak bisa diubah.';

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
                $line->qty = $qty;
                $line->save();
            }
        });

        $totalQty = (int) ShipmentReturnLine::where('shipment_return_id', $header->id)->sum('qty');
        $totalLines = (int) ShipmentReturnLine::where('shipment_return_id', $header->id)->count();

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'status' => 'ok',
                'message' => 'Qty retur berhasil diperbarui.',
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
            ->with('message', 'Qty retur berhasil diperbarui.');
    }

    /**
     * Placeholder kalau nanti mau sync dari perangkat lain.
     */
    public function syncScans(Request $request, ShipmentReturn $shipmentReturn)
    {
        return back()
            ->with('status', 'error')
            ->with('message', 'Fitur sync scans retur belum diimplementasi.');
    }
}
