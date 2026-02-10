<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\MpReconciliation;
use App\Models\SalesInvoice;
use App\Models\Shipment;
use App\Models\ShipmentLine;
use App\Models\Store;
use App\Models\Warehouse;
use App\Services\Inventory\InventoryService;
use App\Services\Sales\DailySalesRealtimeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ShipmentController extends Controller
{
    protected ?Warehouse $whRtsCached = null;

    public function __construct(
        protected InventoryService $inventory,
        protected DailySalesRealtimeService $dailySales
    ) {}

    protected function whRts(): ?Warehouse
    {
        if ($this->whRtsCached) {
            return $this->whRtsCached;
        }

        return $this->whRtsCached = Warehouse::where('code', 'WH-RTS')->first();
    }

    public function index(Request $request)
    {
        $statusFilter = $request->get('status', 'all');

        // ✅ FIX N+1: eager load lines juga (bukan hanya lines.item.category)
        $query = Shipment::query()
            ->with(['store', 'lines', 'lines.item.category'])
            ->orderByDesc('date')
            ->orderByDesc('id');

        if ($statusFilter === 'cancelled') {
            $query->whereNotNull('cancelled_at');
        } elseif (in_array($statusFilter, ['submitted', 'posted'], true)) {
            $query->whereNull('cancelled_at')->where('status', $statusFilter);
        }

        $shipments = $query->paginate(20)->withQueryString();

        // ✅ keep transform (ringkas), tapi sekarang tidak N+1
        $shipments->getCollection()->transform(function (Shipment $shipment) {
            $totalQty = 0;
            $totalRp = 0.0;
            $cats = [];

            foreach ($shipment->lines as $line) {
                $qty = (int) ($line->qty_scanned ?? 0);
                if ($qty <= 0) {
                    continue;
                }

                $totalQty += $qty;

                $unitHpp = 0;
                if ($line->item) {
                    $unitHpp = $line->item->latest_hpp ?? $line->item->hpp ?? $line->item->last_purchase_price ?? 0;
                }
                $totalRp += ((float) $unitHpp) * $qty;

                $catName = optional(optional($line->item)->category)->name ?: 'Tanpa Kategori';
                $cats[$catName] = true;
            }

            $names = array_keys($cats);
            sort($names);

            $shipment->total_qty_calc = $totalQty;
            $shipment->total_rp_calc = $totalRp;
            $shipment->category_count_calc = count($names);

            if (count($names) === 0) {
                $shipment->categories_calc = '-';
            } elseif (count($names) <= 2) {
                $shipment->categories_calc = implode(', ', $names);
            } else {
                $shipment->categories_calc = $names[0] . ', ' . $names[1] . ' +' . (count($names) - 2);
            }

            return $shipment;
        });

        return view('sales.shipments.index', compact('shipments', 'statusFilter'));
    }

    public function create(Request $request)
    {
        $stores = Store::orderBy('code')->get();
        $whRts = $this->whRts();

        $invoice = null;
        if ($request->filled('sales_invoice_id')) {
            $invoice = SalesInvoice::with('store')->find($request->sales_invoice_id);
        } elseif ($request->filled('invoice_id')) {
            $invoice = SalesInvoice::with('store')->find($request->invoice_id);
        }

        return view('sales.shipments.create', compact('stores', 'whRts', 'invoice'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'store_id' => ['required', 'exists:stores,id'],
            'date' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
            'sales_invoice_id' => ['nullable', 'exists:sales_invoices,id'],
        ]);

        $store = Store::findOrFail($data['store_id']);

        $storeName = strtoupper(trim($store->name ?? ''));
        $storeCode = strtoupper(trim($store->code ?? ''));
        $storeKey = $storeCode . ' ' . $storeName;

        $prefix = 'SHP';
        if ($storeCode !== '') {
            $cleanCode = preg_replace('/[^A-Z0-9]/', '', $storeCode);
            if ($cleanCode !== '') {
                $prefix = substr($cleanCode, 0, 3);
            }

        }

        if (str_contains($storeKey, 'SHP') || str_contains($storeKey, 'SHOPEE')) {
            $prefix = 'SHP';
        } elseif (str_contains($storeKey, 'TTK') || str_contains($storeKey, 'TIKTOK')) {
            $prefix = 'TTK';
        }

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
            ->route('sales.shipments.edit', $shipment)
            ->with('status', 'success')
            ->with('message', 'Shipment dibuat. Silakan scan barang.');
    }

    public function show(Shipment $shipment)
    {
        $shipment->load([
            'store',
            'lines.item.category',
            'creator',
            'invoice',
        ]);

        // Cache HPP per item_id untuk hemat proses (dan aman kalau accessor)
        $hppCache = [];

        $shipment->lines->each(function ($line) use (&$hppCache) {
            $qty = (int) ($line->qty_scanned ?? 0);

            $itemId = (int) ($line->item_id ?? 0);
            if (!array_key_exists($itemId, $hppCache)) {
                $unitHpp = 0;
                if ($line->item) {
                    $unitHpp = (float) (
                        $line->item->latest_hpp ?? $line->item->hpp ?? $line->item->last_purchase_price ?? 0
                    );
                }
                $hppCache[$itemId] = $unitHpp;
            }

            $line->unit_hpp = (float) $hppCache[$itemId];
            $line->total_hpp = (float) ($line->unit_hpp * $qty);
        });

        $totalQty = (int) $shipment->lines->sum(fn($l) => (int) ($l->qty_scanned ?? 0));
        $totalLines = (int) $shipment->lines->count();
        $totalHpp = (float) $shipment->lines->sum(fn($l) => (float) ($l->total_hpp ?? 0));

        // Summary per category (basis qty_scanned + total_hpp)
        $summaryPerCategory = $shipment->lines
            ->groupBy(fn($line) => $line->item?->category?->name ?: 'Tanpa Kategori')
            ->map(function ($group, $categoryName) {
                return [
                    'category_name' => $categoryName,
                    'total_lines' => (int) $group->count(),
                    'total_qty' => (int) $group->sum(fn($l) => (int) ($l->qty_scanned ?? 0)),
                    'total_hpp' => (float) $group->sum(fn($l) => (float) ($l->total_hpp ?? 0)),
                ];
            })
            ->values()
            ->sortBy('category_name')
            ->values();

        // Marketplace packets (biarkan seperti kamu punya)
        $mpPackets = MpReconciliation::query()
            ->where('shipment_id', $shipment->id)
            ->whereIn('status', ['needs_review', 'auto_matched', 'resolved'])
            ->with(['mpShipment' => fn($q) => $q->with('items')])
            ->orderByDesc('match_confidence')
            ->get();

        $mpTotalQty = (int) $mpPackets->sum(fn($rec) => (int) ($rec->mpShipment?->total_qty ?? 0));

        $batchQty = (int) ($shipment->total_qty ?? 0);
        if ($batchQty <= 0) {
            $batchQty = $totalQty;
        }

        $deltaQty = $batchQty - $mpTotalQty;

        return view('sales.shipments.show', compact(
            'shipment',
            'totalQty',
            'totalLines',
            'totalHpp',
            'summaryPerCategory',
            'mpPackets',
            'mpTotalQty',
            'batchQty',
            'deltaQty'
        ));
    }

    public function edit(Shipment $shipment)
    {
        if ($shipment->status !== 'draft') {
            return redirect()
                ->route('sales.shipments.show', $shipment)
                ->with('status', 'error')
                ->with('message', 'Shipment bukan draft, tidak bisa di-edit / discan lagi.');
        }

        $shipment->load(['store', 'lines.item', 'creator', 'invoice']);

        $importPreview = session('shipment_import_preview.' . $shipment->id . '.rows') ?? null;
        $importPreviewSummary = session('shipment_import_preview.' . $shipment->id . '.summary') ?? null;

        return view('sales.shipments.edit', compact('shipment', 'importPreview', 'importPreviewSummary'));
    }

    public function scanItem(Request $request, Shipment $shipment)
    {
        if ($shipment->status !== 'draft') {
            $message = 'Shipment sudah tidak bisa discan (bukan draft).';

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['status' => 'error', 'message' => $message], 409);
            }

            return redirect()->route('sales.shipments.show', $shipment)
                ->with('status', 'error')->with('message', $message);
        }

        $data = $request->validate([
            'scan_code' => ['required', 'string', 'max:255'],
            'qty' => ['nullable', 'integer', 'min:1'],
        ]);

        $scanCode = mb_strtoupper(trim($data['scan_code']));
        $qty = max(1, (int) ($data['qty'] ?? 1));

        $item = Item::query()
            ->where('type', 'finished_good')
            ->where(fn($q) => $q->where('barcode', $scanCode)->orWhere('code', $scanCode))
            ->first();

        if (!$item) {
            $message = "Item dengan kode/barcode {$scanCode} tidak ditemukan atau bukan finished_good.";

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['status' => 'error', 'message' => $message], 422);
            }

            return redirect()->route('sales.shipments.edit', $shipment)
                ->with('status', 'error')->with('message', $message)->withInput();
        }

        $result = DB::transaction(function () use ($shipment, $item, $qty) {
            /** @var ShipmentLine|null $line */
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

            $totals = ShipmentLine::query()
                ->where('shipment_id', $shipment->id)
                ->selectRaw('COALESCE(SUM(qty_scanned),0) as total_qty, COUNT(*) as total_lines')
                ->first();

            session()->put('last_scanned_line_id', $line->id);

            return [
                'line' => $line,
                'total_qty' => (int) ($totals->total_qty ?? 0),
                'total_lines' => (int) ($totals->total_lines ?? 0),
            ];
        });

        $line = $result['line'];

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
                    'total_qty' => $result['total_qty'],
                    'total_lines' => $result['total_lines'],
                ],
            ]);
        }

        return redirect()
            ->route('sales.shipments.edit', $shipment)
            ->with('last_scanned_line_id', $line->id);
    }

    /**
     * ✅ Single source of truth untuk melakukan stockOut + set posted + realtime daily sales.
     * dipakai oleh submit() dan post()
     */
    protected function doPostShipment(Shipment $shipment, Warehouse $warehouse): void
    {
        // lock shipment
        $locked = Shipment::whereKey($shipment->id)->lockForUpdate()->firstOrFail();

        if (!empty($locked->posted_at)) {
            return;
        }

        $locked->load(['lines', 'store']);

        $totalQty = 0;

        foreach ($locked->lines as $line) {
            $qty = (int) ($line->qty_scanned ?? 0);
            if ($qty <= 0) {
                continue;
            }

            $totalQty += $qty;

            $this->inventory->stockOut(
                warehouseId: $warehouse->id,
                itemId: (int) $line->item_id,
                qty: $qty,
                date: $locked->date,
                sourceType: 'shipment',
                sourceId: (int) $locked->id,
                notes: 'Shipment ' . $locked->code . ' ke store ' . ($locked->store->code ?? '-'),
                allowNegative: true,
                lotId: null,
                unitCostOverride: null,
                affectLotCost: false,
            );
        }

        // posted flags
        $locked->posted_at = now();
        $locked->posted_by = auth()->id();
        $locked->status = 'posted';
        $locked->total_qty = $totalQty;

        // keep submitted meta if you want
        if (empty($locked->submitted_at)) {
            $locked->submitted_at = now();
            $locked->submitted_by = auth()->id();
        }

        $locked->save();

        // realtime daily sales
        $this->dailySales->applyShipmentPosted($locked, adsDays: 30, onlyActive: true);
    }

    public function submit(Request $request, Shipment $shipment)
    {
        if ($shipment->status !== 'draft') {
            return redirect()->route('sales.shipments.show', $shipment)
                ->with('status', 'error')->with('message', 'Hanya shipment draft yang bisa di-submit.');
        }

        // ✅ lebih ringan dari count()
        if (!$shipment->lines()->exists()) {
            return redirect()->route('sales.shipments.edit', $shipment)
                ->with('status', 'error')->with('message', 'Tidak ada item di shipment ini.');
        }

        $warehouse = $this->whRts();
        if (!$warehouse) {
            return redirect()->route('sales.shipments.show', $shipment)
                ->with('status', 'error')->with('message', 'Warehouse WH-RTS belum dikonfigurasi.');
        }

        try {
            DB::transaction(function () use ($shipment, $warehouse) {
                $this->doPostShipment($shipment, $warehouse);
            });
        } catch (\Throwable $e) {
            return redirect()->route('sales.shipments.edit', $shipment)
                ->with('status', 'error')->with('message', 'Gagal submit & kurangi stok: ' . $e->getMessage());
        }

        return redirect()->route('sales.shipments.show', $shipment)
            ->with('status', 'success')
            ->with('message', 'Shipment berhasil disubmit & stok WH-RTS langsung berkurang.');
    }

    public function post(Request $request, Shipment $shipment)
    {
        if (!empty($shipment->posted_at)) {
            return redirect()->route('sales.shipments.show', $shipment)
                ->with('status', 'error')->with('message', 'Shipment sudah diposting sebelumnya.');
        }

        // kalau kamu masih pakai flow submitted->post, biarkan guard ini
        if ($shipment->status !== 'submitted') {
            return redirect()->route('sales.shipments.show', $shipment)
                ->with('status', 'error')->with('message', 'Shipment harus berstatus submitted.');
        }

        $warehouse = $this->whRts();
        if (!$warehouse) {
            return redirect()->route('sales.shipments.show', $shipment)
                ->with('status', 'error')->with('message', 'Warehouse WH-RTS belum dikonfigurasi.');
        }

        try {
            DB::transaction(function () use ($shipment, $warehouse) {
                $this->doPostShipment($shipment, $warehouse);
            });
        } catch (\Throwable $e) {
            return redirect()->route('sales.shipments.show', $shipment)
                ->with('status', 'error')->with('message', 'Gagal posting: ' . $e->getMessage());
        }

        return redirect()->route('sales.shipments.show', $shipment)
            ->with('status', 'success')->with('message', 'Shipment berhasil diposting & stok berkurang.');
    }

    public function exportLines(Request $request, Shipment $shipment)
    {
        $shipment->load(['lines.item']);

        if ($shipment->lines->isEmpty()) {
            return redirect()->route('sales.shipments.show', $shipment)
                ->with('status', 'error')->with('message', 'Tidak ada item di shipment ini untuk diekspor.');
        }

        $format = (string) $request->get('format', 'default'); // default|mp|comma

        $delimiter = ';';
        if ($format === 'comma') {
            $delimiter = ',';
        }

        $fileName = 'shipment_' . $shipment->code . '_export_' . now()->format('Ymd_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ];

        $callback = function () use ($shipment, $delimiter, $format) {
            $handle = fopen('php://output', 'w');

            // UTF-8 BOM for Excel
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            // Header
            fputcsv($handle, ['Product', 'Quantity'], $delimiter);

            foreach ($shipment->lines as $line) {
                $product = $line->item?->code ?? '';
                $qtyInt = (int) ($line->qty_scanned ?? 0);

                if ($product === '' || $qtyInt <= 0) {
                    continue;
                }

                if ($format === 'default') {
                    // versi kamu: 2 desimal ala excel indonesia
                    $qtyFormatted = number_format($qtyInt, 2, ',', '.');
                } else {
                    // mp / comma: lebih aman integer
                    $qtyFormatted = (string) $qtyInt;
                }

                fputcsv($handle, [$product, $qtyFormatted], $delimiter);
            }

            fclose($handle);
        };

        return response()->streamDownload($callback, $fileName, $headers);
    }

    public function clearLines(Request $request, Shipment $shipment)
    {
        if ($shipment->status !== 'draft') {
            $message = 'Shipment sudah tidak draft, baris tidak bisa dibersihkan.';

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['status' => 'error', 'message' => $message], 409);
            }

            return redirect()->route('sales.shipments.show', $shipment)
                ->with('status', 'error')->with('message', $message);
        }

        DB::transaction(function () use ($shipment) {
            ShipmentLine::where('shipment_id', $shipment->id)->delete();
        });

        session()->forget('last_scanned_line_id');
        session()->forget('shipment_import_preview.' . $shipment->id);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'status' => 'ok',
                'message' => 'Semua baris berhasil dibersihkan.',
                'totals' => ['total_qty' => 0, 'total_lines' => 0],
            ]);
        }

        return redirect()->route('sales.shipments.edit', $shipment)
            ->with('status', 'success')->with('message', 'Semua baris shipment berhasil dibersihkan.');
    }

    public function destroyLine(Request $request, ShipmentLine $line)
    {
        $shipment = $line->shipment;

        if (!$shipment || $shipment->status !== 'draft') {
            $message = 'Shipment sudah tidak draft, baris tidak bisa dihapus.';

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['status' => 'error', 'message' => $message], 409);
            }

            return redirect()->route('sales.shipments.show', $shipment?->id ?? null)
                ->with('status', 'error')->with('message', $message);
        }

        DB::transaction(fn() => $line->delete());

        $totals = ShipmentLine::query()
            ->where('shipment_id', $shipment->id)
            ->selectRaw('COALESCE(SUM(qty_scanned),0) as total_qty, COUNT(*) as total_lines')
            ->first();

        $totalQty = (int) ($totals->total_qty ?? 0);
        $totalLines = (int) ($totals->total_lines ?? 0);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'status' => 'ok',
                'message' => 'Baris berhasil dihapus.',
                'totals' => ['total_qty' => $totalQty, 'total_lines' => $totalLines],
            ]);
        }

        return redirect()->route('sales.shipments.edit', $shipment)
            ->with('status', 'success')->with('message', 'Baris berhasil dihapus.');
    }

    public function updateLineQty(Request $request, ShipmentLine $line)
    {
        $shipment = $line->shipment;

        if (!$shipment || $shipment->status !== 'draft') {
            $message = 'Shipment sudah tidak draft, qty tidak bisa diubah.';

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['status' => 'error', 'message' => $message], 409);
            }

            return redirect()->route('sales.shipments.show', $shipment?->id ?? null)
                ->with('status', 'error')->with('message', $message);
        }

        $data = $request->validate([
            'qty' => ['required', 'integer', 'min:0'],
        ]);

        $qty = (int) $data['qty'];

        DB::transaction(function () use ($line, $qty) {
            if ($qty === 0) {
                $line->delete();
            } else {
                $line->qty_scanned = $qty;
                $line->save();
            }
        });

        $totals = ShipmentLine::query()
            ->where('shipment_id', $shipment->id)
            ->selectRaw('COALESCE(SUM(qty_scanned),0) as total_qty, COUNT(*) as total_lines')
            ->first();

        $totalQty = (int) ($totals->total_qty ?? 0);
        $totalLines = (int) ($totals->total_lines ?? 0);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'status' => 'ok',
                'message' => 'Qty berhasil diperbarui.',
                'deleted' => $qty === 0,
                'qty' => $qty,
                'totals' => ['total_qty' => $totalQty, 'total_lines' => $totalLines],
            ]);
        }

        return redirect()->route('sales.shipments.edit', $shipment)
            ->with('status', 'success')->with('message', 'Qty berhasil diperbarui.');
    }

    public function syncScans(Request $request, Shipment $shipment)
    {
        return back()->with('status', 'error')->with('message', 'Fitur sync scans belum diimplementasi.');
    }

    protected function parseImportedQty(?string $raw): int
    {
        if ($raw === null) {
            return 0;
        }

        $value = trim(str_replace("\xc2\xa0", ' ', $raw));
        if ($value === '') {
            return 0;
        }

        $value = str_replace(' ', '', $value);
        $value = str_replace('.', '', $value);
        $value = str_replace(',', '.', $value);

        if (!is_numeric($value)) {
            return 0;
        }

        return max(0, (int) round((float) $value));
    }

    // importLines & importPreview: kamu bisa keep seperti versi kamu (panjang),
    // karena optimasi besar sudah di N+1 index + unify post/submit.
    // Kalau kamu mau, aku bisa rapihin bagian importPreview supaya lebih singkat dan lebih cepat juga.

    public function cancelPosted(Request $request, Shipment $shipment): RedirectResponse
    {
        if ((auth()->user()->role ?? null) !== 'owner') {
            abort(403);
        }

        $validated = $request->validate([
            'cancel_reason' => ['required', 'string', 'max:255'],
        ]);

        $warehouse = $this->whRts();
        if (!$warehouse) {
            return redirect()->route('sales.shipments.show', $shipment)
                ->with('status', 'error')->with('message', 'Warehouse WH-RTS belum dikonfigurasi.');
        }

        try {
            DB::transaction(function () use ($shipment, $warehouse, $validated) {
                $locked = Shipment::whereKey($shipment->id)->lockForUpdate()->firstOrFail();

                if (!empty($locked->cancelled_at)) {
                    return;
                }

                if ($locked->status !== 'posted' || empty($locked->posted_at)) {
                    throw new \RuntimeException('Hanya shipment status posted yang bisa dibatalkan.');
                }
                if (!empty($locked->sales_invoice_id)) {
                    throw new \RuntimeException('Tidak bisa dibatalkan karena sudah dibuat invoice.');
                }

                $locked->load(['lines', 'store']);

                foreach ($locked->lines as $line) {
                    $qty = (int) ($line->qty_scanned ?? 0);
                    if ($qty <= 0) {
                        continue;
                    }

                    $unitCost = $line->unit_hpp ?? null;

                    $this->inventory->stockIn(
                        warehouseId: $warehouse->id,
                        itemId: (int) $line->item_id,
                        qty: $qty,
                        date: $locked->date,
                        sourceType: 'shipment_cancel',
                        sourceId: (int) $locked->id,
                        notes: 'Cancel Shipment ' . $locked->code . ' (balik dari store ' . ($locked->store->code ?? '-') . ')'
                        . ($validated['cancel_reason'] ? ' • ' . $validated['cancel_reason'] : ''),
                        lotId: null,
                        unitCost: $unitCost,
                        affectLotCost: false,
                    );
                }

                $locked->cancelled_at = now();
                $locked->cancelled_by = auth()->id();
                $locked->cancel_reason = $validated['cancel_reason'];
                $locked->save();

                $this->dailySales->reverseShipmentCancelled($locked, adsDays: 30, onlyActive: true);
            });
        } catch (\Throwable $e) {
            return redirect()->route('sales.shipments.show', $shipment)
                ->with('status', 'error')->with('message', 'Gagal membatalkan shipment: ' . $e->getMessage());
        }

        return redirect()->route('sales.shipments.show', $shipment)
            ->with('status', 'success')
            ->with('message', 'Shipment posted berhasil dibatalkan & stok sudah dikembalikan ke WH-RTS.');
    }

    public function reconcile(Shipment $shipment)
    {
        $shipment->load(['store', 'creator']);

        // Filter status untuk UI: all|needs_review|resolved|skipped|auto_matched
        $status = request('status', 'needs_review'); // default langsung ke review biar enak

        $base = \App\Models\MpReconciliation::query()
            ->where('shipment_id', $shipment->id);

        $mpCounts = (clone $base)
            ->selectRaw("status, COUNT(*) as c")
            ->groupBy('status')
            ->pluck('c', 'status');

        $mpStats = (clone $base)
            ->selectRaw("
            SUM(status = 'resolved')     as resolved,
            SUM(status = 'needs_review') as needs_review,
            SUM(status = 'skipped')      as skipped,
            SUM(status = 'auto_matched') as auto_matched,
            COUNT(*)                     as total
        ")
            ->first();

        $mpPacketsQ = (clone $base)
            ->with(['mpShipment']) // penting untuk platform_order_id
            ->orderByDesc('id');

        if ($status !== 'all') {
            $mpPacketsQ->where('status', $status);
        }

        $mpPackets = $mpPacketsQ->paginate(50)->withQueryString();

        return view('sales.shipments.reconcile', compact(
            'shipment',
            'status',
            'mpPackets',
            'mpCounts',
            'mpStats'
        ));
    }

}
