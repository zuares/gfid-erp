<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\InventoryStock;
use App\Models\Item;
use App\Models\MpReconciliation;
use App\Models\SalesInvoice;
use App\Models\Shipment;
use App\Models\ShipmentLine;
use App\Models\ShipmentOrderScan;
use App\Models\Store;
use App\Models\Warehouse;
use App\Models\Account;
use App\Services\Accounting\JournalService;
use App\Services\Inventory\InventoryService;
use App\Services\Sales\DailySalesRealtimeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ShipmentController extends Controller
{
    private function isShipmentNextCommand(?string $code): bool
    {
        $code = mb_strtoupper(trim((string) $code));

        return in_array($code, [
            'ORDER BARU',
            'BARU',
            'NEXT',
            'NEXT ORDER',
            'ORDER NEXT',
        ], true);
    }


    protected ?Warehouse $whRtsCached = null;

    public function __construct(
        protected InventoryService $inventory,
        protected DailySalesRealtimeService $dailySales,
        protected JournalService $journalService
    ) {}

    /**
     * Halaman Kirim Paket Manual — form + preview label 100×150mm.
     */
    public function manualShipment()
    {
        $shipments = Shipment::with(['creator'])
            ->where('shipment_type', 'manual')
            ->orderBy('id', 'desc')
            ->paginate(15);

        return view('sales.manual-shipment', compact('shipments'));
    }

    public function manualShipmentStore(Request $request)
    {
        $data = $request->validate([
            'receiverName'    => ['required', 'string'],
            'receiverPhone'   => ['required', 'string'],
            'receiverAddress' => ['required', 'string'],
            'items'           => ['required', 'array', 'min:1'],
            'items.*.id'      => ['required', 'exists:items,id'],
            'items.*.qty'     => ['required', 'integer', 'min:1'],
        ]);

        try {
            DB::transaction(function () use ($data) {
                $code = Shipment::generateCode('MNL');
                
                $receiverData = [
                    'nama' => $data['receiverName'],
                    'phone' => $data['receiverPhone'],
                    'alamat' => $data['receiverAddress'],
                ];

                $shipment = Shipment::create([
                    'code'          => $code,
                    'shipment_type' => 'manual',
                    'date'          => now()->toDateString(),
                    'status'        => 'draft', // sedang dipacking
                    'notes'         => json_encode($receiverData),
                    'created_by'    => Auth::id(),
                ]);

                // Create lines and reserve stock if warehouse available
                $warehouse = $this->whRts();
                
                foreach ($data['items'] as $itemData) {
                    $item = Item::find($itemData['id']);
                    
                    if ($warehouse) {
                        app(\App\Services\Inventory\InventoryService::class)->reserveStock(
                            warehouseId: $warehouse->id,
                            itemId: $item->id,
                            qty: $itemData['qty'],
                            allowNegative: true 
                        );
                    }

                    ShipmentLine::create([
                        'shipment_id'   => $shipment->id,
                        'item_id'       => $item->id,
                        'qty_expected'  => 0,
                        'qty_scanned'   => $itemData['qty'],
                        'allocated_qty' => $itemData['qty'],
                        'uom'           => $item->uom ?? 'pcs',
                    ]);
                }
            });

            return redirect()->route('sales.shipments.index')
                ->with('status', 'success')
                ->with('message', 'Paket manual berhasil dibuat (Draft).');
        } catch (\Throwable $e) {
            return redirect()->route('sales.shipments.manual')
                ->with('status', 'error')
                ->with('message', 'Gagal membuat paket: ' . $e->getMessage());
        }
    }

    public function manualShipmentPost(Shipment $shipment)
    {
        if ($shipment->shipment_type !== 'manual') {
            return redirect()->route('sales.shipments.manual')
                ->with('status', 'error')->with('message', 'Bukan shipment manual.');
        }

        if (!empty($shipment->posted_at) || $shipment->status !== 'draft') {
            return redirect()->route('sales.shipments.manual')
                ->with('status', 'error')->with('message', 'Hanya shipment draft yang bisa diposting.');
        }

        $warehouse = $this->whRts();
        if (!$warehouse) {
            return redirect()->route('sales.shipments.manual')
                ->with('status', 'error')->with('message', 'Warehouse WH-RTS belum dikonfigurasi.');
        }

        $shipment->load('lines.item');
        $stockErrors = $this->checkStockSufficiency($shipment, $warehouse);
        if (!empty($stockErrors)) {
            return redirect()->route('sales.shipments.manual')
                ->with('status', 'error')
                ->with('message', 'Stok WH-RTS tidak cukup.')
                ->with('stock_insufficient', $stockErrors);
        }

        try {
            DB::transaction(function () use ($shipment, $warehouse) {
                // Update status to submitted so doPostShipment will run
                $shipment->status = 'submitted';
                $shipment->save();
                
                $this->doPostShipment($shipment, $warehouse);
            });
        } catch (\Throwable $e) {
            return redirect()->route('sales.shipments.manual')
                ->with('status', 'error')->with('message', 'Gagal posting: ' . $e->getMessage());
        }

        return redirect()->route('sales.shipments.index')
            ->with('status', 'success')->with('message', 'Paket berhasil dikirim & stok WH-RTS berkurang.');
    }

    public function manualShipmentDestroy(Shipment $shipment)
    {
        if ($shipment->shipment_type !== 'manual') {
            return redirect()->route('sales.shipments.manual')
                ->with('status', 'error')->with('message', 'Bukan shipment manual.');
        }

        if ($shipment->status !== 'draft') {
            return redirect()->route('sales.shipments.manual')
                ->with('status', 'error')->with('message', 'Hanya shipment draft yang bisa dihapus.');
        }

        try {
            DB::transaction(function () use ($shipment) {
                $warehouse = $this->whRts();
                $lines = ShipmentLine::where('shipment_id', $shipment->id)->get();
                foreach ($lines as $line) {
                    if ($warehouse && $line->allocated_qty > 0) {
                        app(\App\Services\Inventory\InventoryService::class)->releaseStock(
                            $warehouse->id,
                            $line->item_id,
                            $line->allocated_qty
                        );
                    }
                    $line->delete();
                }
                $shipment->delete();
            });

            return redirect()->route('sales.shipments.index')
                ->with('status', 'success')->with('message', 'Paket manual berhasil dihapus.');
        } catch (\Throwable $e) {
            return redirect()->route('sales.shipments.manual')
                ->with('status', 'error')->with('message', 'Gagal menghapus: ' . $e->getMessage());
        }
    }

    protected function whRts(): ?Warehouse
    {
        if ($this->whRtsCached) {
            return $this->whRtsCached;
        }

        return $this->whRtsCached = Warehouse::where('code', 'WH-RTS')->first();
    }

    /**
     * ADMIN: tidak boleh lihat nominal sama sekali.
     * (Kalau mau hanya owner yang boleh lihat nominal, bilang ya nanti aku ubah ke whitelist.)
     */
    protected function canSeeNominal(): bool
    {
        return (auth()->user()->role ?? null) !== 'admin';
    }

    public function index(Request $request)
    {
        $statusFilter = $request->get('status', 'all');
        $canSeeNominal = $this->canSeeNominal();

        $query = Shipment::query()
            ->with(['store', 'lines', 'lines.item.category'])
            ->orderByDesc('date')
            ->orderByDesc('id');

        if ($statusFilter === 'cancelled') {
            $query->whereNotNull('cancelled_at');
        } elseif (in_array($statusFilter, ['draft', 'submitted', 'posted'], true)) {
            $query->whereNull('cancelled_at')->where('status', $statusFilter);
        }

        $shipments = $query->paginate(20)->withQueryString();

        // ✅ Transform ringkas + admin tidak menghitung nominal
        $shipments->getCollection()->transform(function (Shipment $shipment) use ($canSeeNominal) {
            $totalQty = 0;
            $totalRp = 0.0;
            $cats = [];

            foreach ($shipment->lines as $line) {
                $qty = (int) ($line->qty_scanned ?? 0);
                if ($qty <= 0) {
                    continue;
                }

                $totalQty += $qty;

                if ($canSeeNominal) {
                    $unitHpp = 0;
                    if ($line->item) {
                        $unitHpp = $line->item->latest_hpp ?? $line->item->hpp ?? $line->item->last_purchase_price ?? 0;
                    }
                    $totalRp += ((float) $unitHpp) * $qty;
                }

                $catName = optional(optional($line->item)->category)->name ?: 'Tanpa Kategori';
                $cats[$catName] = true;
            }

            $names = array_keys($cats);
            sort($names);

            $shipment->total_qty_calc = $totalQty;
            $shipment->total_rp_calc = $canSeeNominal ? $totalRp : null; // admin: null
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

        $canFreshShipments = env('APP_DB_MODE') === 'dev'
            && (auth()->user()?->role === 'owner');

        $staleDrafts = \App\Models\Shipment::whereIn('status', ['draft', 'submitted'])
            ->where('created_at', '<', now()->subHours(24))
            ->whereHas('lines', function($q) {
                $q->where('allocated_qty', '>', 0);
            })
            ->withSum('lines as total_allocated', 'allocated_qty')
            ->get();

        $isDummy = request()->boolean('dummy') && app()->environment(['local', 'testing']);

        return view('sales.shipments.index', compact(
            'shipments',
            'statusFilter',
            'canSeeNominal',
            'canFreshShipments',
            'staleDrafts',
            'isDummy'
        ));
    }

    public function devFreshShipments(Request $request): RedirectResponse
    {
        if (env('APP_DB_MODE') !== 'dev') {
            abort(403, 'Fresh data shipment hanya tersedia di mode dev.');
        }

        if (!$request->user() || $request->user()->role !== 'owner') {
            abort(403, 'Hanya owner yang bisa fresh data shipment.');
        }

        $deleted = [];

        DB::transaction(function () use (&$deleted) {
            $shipmentIds = DB::table('shipments')->pluck('id');

            if ($shipmentIds->isEmpty()) {
                $deleted['shipments'] = 0;
                return;
            }

            if (Schema::hasTable('mp_reconciliations') && Schema::hasColumn('mp_reconciliations', 'shipment_id')) {
                $deleted['link rekonsiliasi'] = DB::table('mp_reconciliations')
                    ->whereIn('shipment_id', $shipmentIds)
                    ->update(['shipment_id' => null]);
            }

            if (Schema::hasTable('shipment_returns') && Schema::hasColumn('shipment_returns', 'shipment_id')) {
                $deleted['link return'] = DB::table('shipment_returns')
                    ->whereIn('shipment_id', $shipmentIds)
                    ->update(['shipment_id' => null]);
            }

            if (Schema::hasTable('shipment_return_lines') && Schema::hasColumn('shipment_return_lines', 'shipment_line_id')) {
                $lineIds = DB::table('shipment_lines')
                    ->whereIn('shipment_id', $shipmentIds)
                    ->pluck('id');

                if ($lineIds->isNotEmpty()) {
                    $deleted['link line return'] = DB::table('shipment_return_lines')
                        ->whereIn('shipment_line_id', $lineIds)
                        ->update(['shipment_line_id' => null]);
                }
            }

            if (Schema::hasTable('journals')) {
                $journalIds = DB::table('journals')
                    ->where('source_type', 'shipment_cogs')
                    ->whereIn('source_id', $shipmentIds)
                    ->pluck('id');

                if ($journalIds->isNotEmpty()) {
                    if (Schema::hasTable('journal_lines')) {
                        $deleted['baris jurnal'] = DB::table('journal_lines')
                            ->whereIn('journal_id', $journalIds)
                            ->delete();
                    }

                    $deleted['jurnal shipment'] = DB::table('journals')
                        ->whereIn('id', $journalIds)
                        ->delete();
                }
            }

            if (Schema::hasTable('inventory_mutations')) {
                $deleted['mutasi inventory'] = DB::table('inventory_mutations')
                    ->whereIn('source_type', ['shipment', 'shipment_cancel'])
                    ->whereIn('source_id', $shipmentIds)
                    ->delete();
            }

            if (Schema::hasTable('daily_item_sales')) {
                $deleted['daily item sales'] = DB::table('daily_item_sales')->delete();
            }

            if (Schema::hasTable('shipment_lines')) {
                $deleted['shipment lines'] = DB::table('shipment_lines')
                    ->whereIn('shipment_id', $shipmentIds)
                    ->delete();
            }

            $deleted['shipments'] = DB::table('shipments')->whereIn('id', $shipmentIds)->delete();

            if (Schema::hasTable('inventory_stocks') && Schema::hasTable('inventory_mutations')) {
                DB::statement("
                    UPDATE inventory_stocks SET qty = COALESCE(
                        (SELECT SUM(m.qty_change) FROM inventory_mutations m
                         WHERE m.warehouse_id = inventory_stocks.warehouse_id
                           AND m.item_id = inventory_stocks.item_id), 0)
                ");
            }
        });

        $summary = collect($deleted)
            ->filter(fn ($count) => (int) $count > 0)
            ->map(fn ($count, $label) => "{$label}: {$count}")
            ->implode(', ');

        return redirect()
            ->route('sales.shipments.index')
            ->with('status', 'success')
            ->with('message', $summary
                ? 'Data shipment berhasil dibersihkan. ' . $summary
                : 'Tidak ada data shipment yang perlu dibersihkan.');
    }

    public function show(Shipment $shipment)
    {
        $canSeeNominal = $this->canSeeNominal();

        $shipment->load([
            'store',
            'lines.item.category',
            'orderScans.confirmer',
            'orderScans.fulfillment.marketplaceOrder.store',
            'creator',
            'submitter',
            'invoice',
        ]);

        $isDummy = request()->boolean('dummy') && app()->environment(['local', 'testing']);
        if ($isDummy) {
            $dummyProvider = app(\App\Support\DummyMarketplaceOrderProvider::class);
            $dummyOrders = $dummyProvider->orders();
            $dummyScans = $dummyOrders->map(function ($order, $index) use ($shipment) {
                $scan = new \App\Models\ShipmentOrderScan();
                $scan->id = 99000 + $index;
                $scan->order_no = $order['channel_order_id'];
                $scan->status = 'ok';
                $scan->source = 'shopee';
                $scan->confirmed_at = now();
                
                $fulfillment = new \App\Models\OrderFulfillment();
                $fulfillment->id = $order['fulfillment']['id'];
                
                $mpOrder = new \App\Models\MarketplaceOrder();
                $mpOrder->id = $order['id'];
                $mpOrder->store_id = $order['store_id'];
                $mpOrder->channel_order_id = $order['channel_order_id'];
                $mpOrder->order_status = $order['order_status'];
                $mpOrder->shipping_carrier = $order['shipping_carrier'];
                $mpOrder->print_count = $order['print_count'];
                $mpOrder->printed_at = $order['printed_at'] ? \Carbon\Carbon::parse($order['printed_at']) : null;
                
                $store = new \App\Models\MarketplaceStore();
                $store->id = $order['store']['id'];
                $store->name = $order['store']['name'];
                
                $mpOrder->setRelation('store', $store);
                $fulfillment->setRelation('marketplaceOrder', $mpOrder);
                $scan->setRelation('fulfillment', $fulfillment);
                
                return $scan;
            });
            $shipment->setRelation('orderScans', $dummyScans);
        }

        // admin: jangan set unit_hpp / total_hpp sama sekali
        $hppCache = [];

        $shipment->lines->each(function ($line) use (&$hppCache, $canSeeNominal) {
            $qty = (int) ($line->qty_scanned ?? 0);

            if (!$canSeeNominal) {
                $line->unit_hpp = null;
                $line->total_hpp = null;
                return;
            }

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
        $totalHpp = $canSeeNominal
        ? (float) $shipment->lines->sum(fn($l) => (float) ($l->total_hpp ?? 0))
        : 0.0;

        $summaryPerCategory = $shipment->lines
            ->groupBy(fn($line) => $line->item?->category?->name ?: 'Tanpa Kategori')
            ->map(function ($group, $categoryName) use ($canSeeNominal) {
                return [
                    'category_name' => $categoryName,
                    'total_lines' => (int) $group->count(),
                    'total_qty' => (int) $group->sum(fn($l) => (int) ($l->qty_scanned ?? 0)),
                    'total_hpp' => $canSeeNominal
                    ? (float) $group->sum(fn($l) => (float) ($l->total_hpp ?? 0))
                    : 0.0,
                ];
            })
            ->values()
            ->sortBy('category_name')
            ->values();

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
            'deltaQty',
            'canSeeNominal',
            'isDummy'
        ));
    }/**
     * ADMIN: tidak boleh lihat nominal sama sekali.
     * (Kalau mau hanya owner yang boleh lihat nominal, bilang ya nanti aku ubah ke whitelist.)
     */

    /**
     * AJAX: cari SalesInvoice berdasarkan no pesanan, kode invoice, atau channel_invoice_no.
     * Dipanggil dari create.blade.php saat user mengetik no pesanan.
     */
    public function invoiceLookup(Request $request): \Illuminate\Http\JsonResponse
    {
        $q = trim($request->input('q', ''));

        if (strlen($q) < 3) {
            return response()->json(['found' => false]);
        }

        $invoice = SalesInvoice::with('store')
            ->where(function ($query) use ($q) {
                $query->where('code', $q)
                    ->orWhere('channel_order_no', $q)
                    ->orWhere('channel_invoice_no', $q)
                    ->orWhere('code', 'like', '%' . $q . '%')
                    ->orWhere('channel_order_no', 'like', '%' . $q . '%');
            })
            ->orderByRaw("CASE WHEN code = ? OR channel_order_no = ? THEN 0 ELSE 1 END", [$q, $q])
            ->first();

        if (! $invoice) {
            return response()->json(['found' => false]);
        }

        return response()->json([
            'found'            => true,
            'id'               => $invoice->id,
            'code'             => $invoice->code,
            'channel_order_no' => $invoice->channel_order_no,
            'date'             => optional($invoice->date)->format('Y-m-d'),
            'store_id'         => $invoice->store_id,
            'store_code'       => $invoice->store->code ?? null,
            'store_name'       => $invoice->store->name ?? null,
        ]);
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

        $today = now()->toDateString();
        $kpi = [
            'created' => Shipment::whereDate('created_at', $today)->count(),
            'qty'     => (int) ShipmentLine::whereHas('shipment', fn ($q) => $q->whereDate('created_at', $today))->sum('qty_scanned'),
            'draft'   => Shipment::whereDate('created_at', $today)->where('status', 'draft')->count(),
            'posted'  => Shipment::whereDate('created_at', $today)->where('status', 'posted')->count(),
        ];

        $latestDraft = Auth::id()
            ? Shipment::query()
                ->with('store')
                ->withCount('lines')
                ->withSum('lines as total_qty_scanned', 'qty_scanned')
                ->where('status', 'draft')
                ->where('created_by', Auth::id())
                ->latest('updated_at')
                ->first()
            : null;

        return view('sales.shipments.create', compact('stores', 'whRts', 'invoice', 'kpi', 'latestDraft'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'shipment_type'    => ['required', 'in:marketplace,manual'],
            'store_id'         => ['nullable', 'exists:stores,id'],
            'date'             => ['required', 'date'],
            'notes'            => ['nullable', 'string'],
            'sales_invoice_id' => ['nullable', 'exists:sales_invoices,id'],
            'scan_mode'        => ['nullable', 'in:item_first,order_first'],
        ]);

        // Store boleh null — diisi nanti saat rekonsiliasi dengan no pesanan
        $store = $data['store_id'] ? Store::find($data['store_id']) : null;

        $prefix = 'SHP';
        if ($store) {
            $storeName = strtoupper(trim($store->name ?? ''));
            $storeCode = strtoupper(trim($store->code ?? ''));
            $storeKey  = $storeCode . ' ' . $storeName;

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
        }

        $code = Shipment::generateCode($prefix);

        $shipment = Shipment::create([
            'code'             => $code,
            'shipment_type'    => $data['shipment_type'],
            'store_id'         => $data['store_id'] ?? null,
            'sales_invoice_id' => $data['sales_invoice_id'] ?? null,
            'date'             => $data['date'],
            'status'           => 'draft',
            'notes'            => $data['notes'] ?? null,
            'created_by'       => Auth::id(),
        ]);

        if ($request->expectsJson() || $request->ajax()) {
            // Gunakan path-only (strip host) agar AJAX selalu ke host yang sama,
            // tidak peduli APP_URL (bisa berbeda saat pakai ngrok/expose).
            $path = fn ($name, $params = []) => parse_url(route($name, $params), PHP_URL_PATH);

            return response()->json([
                'status'   => 'ok',
                'shipment' => [
                    'id'         => $shipment->id,
                    'code'       => $shipment->code,
                    'date'       => $shipment->date->format('Y-m-d'),
                    'store_id'   => $shipment->store_id,
                    'store_code' => $store?->code,
                    'store_name' => $store?->name,
                    'notes'      => $shipment->notes,
                    'scan_url'         => $path('sales.shipments.scan_item', $shipment),
                    'submit_url'       => $path('sales.shipments.submit', $shipment),
                    'clear_url'        => $path('sales.shipments.clear_lines', $shipment),
                    'show_url'         => $path('sales.shipments.show', $shipment),
                    'scan_order_url'   => $path('sales.shipments.scan_order', $shipment),
                    // edit_url tetap scanner item-first lama.
                    'edit_url'         => $path('sales.shipments.edit', $shipment),
                    'legacy_edit_url'  => $path('sales.shipments.edit', $shipment),
                    'rekon_url'        => $path('sales.shipments.rekon', $shipment),
                    'rekon_apply_url'  => $path('sales.shipments.rekon_apply', $shipment),
                    'export_url'       => $path('sales.shipments.export_lines', $shipment),
                    'destroy_line_url' => $path('sales.shipments.destroy_line', ['line' => '__LINE_ID__']),
                    'update_qty_url'   => $path('sales.shipments.update_line_qty', ['line' => '__LINE_ID__']),
                ],
            ]);
        }

        $redirectRoute = ($data['scan_mode'] ?? 'item_first') === 'order_first'
            ? 'sales.shipments.scan_order'
            : 'sales.shipments.edit';

        $message = ($data['scan_mode'] ?? 'item_first') === 'order_first'
            ? 'Shipment dibuat. Scan nomor order terlebih dahulu.'
            : 'Shipment dibuat. Silakan scan barang.';

        return redirect()
            ->route($redirectRoute, $shipment)
            ->with('status', 'success')
            ->with('message', $message);
    }

    public function edit(Shipment $shipment)
    {
        if ($shipment->status !== 'draft') {
            return redirect()
                ->route('sales.shipments.show', $shipment)
                ->with('status', 'error')
                ->with('message', 'Shipment bukan draft, tidak bisa di-edit / discan lagi.');
        }

        $shipment->load(['store', 'lines.item.category', 'creator', 'invoice']);

        // Hitung kekurangan stok secara live agar panel tetap muncul saat reload
        // dan otomatis hilang begitu stok/qty sudah beres.
        $warehouse = $this->whRts();
        $stockInsufficient = $warehouse
            ? $this->checkStockSufficiency($shipment, $warehouse)
            : [];

        $importPreview = session('shipment_import_preview.' . $shipment->id . '.rows') ?? null;
        $importPreviewSummary = session('shipment_import_preview.' . $shipment->id . '.summary') ?? null;

        $today = now()->toDateString();
        $kpi = [
            'created' => Shipment::whereDate('created_at', $today)->count(),
            'qty'     => (int) ShipmentLine::whereHas('shipment', fn ($q) => $q->whereDate('created_at', $today))->sum('qty_scanned'),
            'draft'   => Shipment::whereDate('created_at', $today)->where('status', 'draft')->count(),
            'posted'  => Shipment::whereDate('created_at', $today)->where('status', 'posted')->count(),
        ];

        return view('sales.shipments.edit', compact('shipment', 'importPreview', 'importPreviewSummary', 'kpi', 'stockInsufficient'));
    }


    public function editOrderFirst(Shipment $shipment)
    {
        $shipment->load([
            'store',
            'lines.item',
            'orderScans',
        ]);

        $savedOrderScans = $shipment->orderScans
            ->sortBy('id')
            ->map(function ($scan) {
                return [
                    'id' => $scan->id,
                    'code' => $scan->order_number ?: $scan->order_no,
                    'order_number' => $scan->order_number ?: $scan->order_no,
                    'order_no' => $scan->order_no ?: $scan->order_number,
                    'status' => $scan->status,
                    'match_status' => $scan->match_status,
                    'items' => [],
                ];
            })
            ->values();

        return view('sales.shipments.edit_order_first', compact('shipment', 'savedOrderScans'));
    }


    public function scanLookup(Request $request, Shipment $shipment)
    {
        $code = mb_strtoupper(trim((string) $request->query('code', '')));

        if ($code === '') {
            return response()->json([
                'type' => 'empty',
            ]);
        }

        $item = Item::query()
            ->where('type', 'finished_good')
            ->where(function ($query) use ($code) {
                $query->where('barcode', $code)
                    ->orWhere('code', $code)
                    ->orWhereHas('barcodes', function ($barcodeQuery) use ($code) {
                        $barcodeQuery->where('barcode', $code)
                            ->where('is_active', true);
                    });
            })
            ->first(['id', 'code', 'name']);

        if ($item) {
            return response()->json([
                'type' => 'item',
                'item' => [
                    'id' => $item->id,
                    'code' => $item->code,
                    'name' => $item->name,
                ],
            ]);
        }

        $order = Shipment::with('store:id,code,name')
            ->where('code', $code)
            ->first(['id', 'code', 'store_id', 'date']);

        if ($order) {
            return response()->json([
                'type' => 'order',
                'order' => [
                    'id' => $order->id,
                    'code' => $order->code,
                    'store_code' => $order->store?->code,
                    'store_name' => $order->store?->name,
                ],
            ]);
        }

        return response()->json([
            'type' => 'unknown',
        ]);
    }

    public function scanItem(Request $request, Shipment $shipment)
    {

        $code = mb_strtoupper(trim((string) ($request->input('code') ?? $request->input('barcode') ?? $request->input('item_code') ?? $request->input('scan') ?? '')));

        if ($this->isShipmentNextCommand($code)) {
            return response()->json([
                'status' => 'ok',
                'type' => 'next_order',
                'message' => 'Order baru. Scan nomor order berikutnya.',
            ]);
        }


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
            'order_no' => ['nullable', 'string', 'max:200'],
        ]);

        $scanCode = mb_strtoupper(trim($data['scan_code']));
        $qty = max(1, (int) ($data['qty'] ?? 1));
        $orderNo = mb_strtoupper(trim((string) ($data['order_no'] ?? '')));

        $item = Item::query()
            ->with('category:id,name')
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

        $fulfillmentId = null;
        $scanInfo = null;

        if ($orderNo !== '') {
            if ($shipment->shipment_type === 'marketplace') {
                $marketplaceOrder = \App\Models\MarketplaceOrder::where('channel_order_id', $orderNo)
                    ->orWhere('shipping_awb_no', $orderNo)
                    ->first(['id']);
                
                if (!$marketplaceOrder) {
                    $msg = "Order/resi tidak ditemukan di data marketplace.";
                    if ($request->expectsJson() || $request->ajax()) return response()->json(['status' => 'error', 'message' => $msg], 422);
                    return redirect()->back()->with('status', 'error')->with('message', $msg);
                }

                $fulfillment = \App\Models\OrderFulfillment::where('marketplace_order_id', $marketplaceOrder->id)->first(['id', 'status']);
                if (!$fulfillment) {
                    $msg = "Order ditemukan tapi tidak ada di data Fulfillment.";
                    if ($request->expectsJson() || $request->ajax()) return response()->json(['status' => 'error', 'message' => $msg], 422);
                    return redirect()->back()->with('status', 'error')->with('message', $msg);
                }

                if ($fulfillment->status === \App\Models\OrderFulfillment::STATUS_CANCELLED) {
                    $msg = "Order/Resi {$orderNo} berstatus Cancelled. Jangan dikirim!";
                    if ($request->expectsJson() || $request->ajax()) return response()->json(['status' => 'error', 'message' => $msg], 422);
                    return redirect()->back()->with('status', 'error')->with('message', $msg);
                }

                if ($fulfillment->status === 'confirmed') {
                    $msg = "Order/Resi {$orderNo} sudah dipacking (berada di tab Siap Kirim).";
                    if ($request->expectsJson() || $request->ajax()) return response()->json(['status' => 'error', 'message' => $msg], 422);
                    return redirect()->back()->with('status', 'error')->with('message', $msg);
                }

                $fulfillmentId = $fulfillment->id;
                
                // Cek di shipment ini
                $existsInCurrent = \App\Models\ShipmentOrderScan::where('fulfillment_id', $fulfillmentId)
                    ->where('shipment_id', $shipment->id)
                    ->exists();
                if ($existsInCurrent) {
                    $scanInfo = "Pesanan sudah discan di shipment ini.";
                }

                // Cek di shipment lain yang aktif
                $existingScan = \App\Models\ShipmentOrderScan::where('fulfillment_id', $fulfillmentId)
                    ->where('shipment_id', '!=', $shipment->id)
                    ->whereHas('shipment', function ($q) {
                        $q->whereIn('status', ['draft', 'submitted', 'posted']);
                    })->first();
                    
                if ($existingScan) {
                    $code = $existingScan->shipment->code ?? '-';
                    $msg = "Pesanan sedang diproses di shipment lain: {$code}.";
                    if ($request->expectsJson() || $request->ajax()) return response()->json(['status' => 'error', 'message' => $msg], 422);
                    return redirect()->back()->with('status', 'error')->with('message', $msg);
                }
            } else {
                $orderNo = ''; 
            }
        }

        $result = DB::transaction(function () use ($shipment, $item, $qty, $orderNo, $fulfillmentId) {
            $warehouse = $this->whRts();
            if ($warehouse) {
                app(\App\Services\Inventory\InventoryService::class)->reserveStock(
                    warehouseId: $warehouse->id,
                    itemId: $item->id,
                    qty: $qty,
                    allowNegative: true // izinkan over-reserve saat edit; blokir di submit/post
                );
            }

            /** @var \App\Models\ShipmentLine|null $line */
            $line = \App\Models\ShipmentLine::query()
                ->where('shipment_id', $shipment->id)
                ->where('item_id', $item->id)
                ->lockForUpdate()
                ->first();

            if ($line) {
                $line->qty_scanned = (int) $line->qty_scanned + $qty;
                $line->allocated_qty = (int) $line->allocated_qty + $qty;
                $line->save();
            } else {
                $line = \App\Models\ShipmentLine::create([
                    'shipment_id' => $shipment->id,
                    'item_id' => $item->id,
                    'qty_expected' => 0, 
                    'qty_scanned' => $qty,
                    'allocated_qty' => $qty,
                    'uom' => $item->uom ?? 'pcs',
                ]);
            }

            $totals = \App\Models\ShipmentLine::query()
                ->where('shipment_id', $shipment->id)
                ->selectRaw('COALESCE(SUM(qty_scanned),0) as total_qty, COUNT(*) as total_lines')
                ->first();

            if ($orderNo !== '') {
                ShipmentOrderScan::updateOrCreate(
                    [
                        'shipment_id' => $shipment->id,
                        'order_no' => $orderNo,
                    ],
                    [
                        'fulfillment_id' => $fulfillmentId,
                        'status' => 'pending', 
                        'source' => 'scan',
                    ]
                );
            }

            return [
                'line' => $line,
                'total_qty' => (int) ($totals->total_qty ?? 0),
                'total_lines' => (int) ($totals->total_lines ?? 0),
            ];
        });

        $line = $result['line'];

        $warehouseId = $shipment->warehouse_id;
        $stockPhysical = 0;
        $stockPacking = 0;
        $stockAvailable = 0;
        
        if ($item) {
            if ($warehouseId) {
                $invStock = \App\Models\InventoryStock::where('warehouse_id', $warehouseId)
                    ->where('item_id', $item->id)
                    ->first();
                if ($invStock) {
                    $stockPhysical = (float) $invStock->qty;
                    $stockPacking = (float) $invStock->allocated_qty;
                    $stockAvailable = (float) $invStock->available_qty;
                }
            } else {
                $invStock = \App\Models\InventoryStock::where('item_id', $item->id)
                    ->selectRaw('SUM(qty) as qty, SUM(allocated_qty) as allocated_qty')
                    ->first();
                if ($invStock) {
                    $stockPhysical = (float) $invStock->qty;
                    $stockPacking = (float) $invStock->allocated_qty;
                    $stockAvailable = $stockPhysical - $stockPacking;
                }
            }
        }

        if ($request->expectsJson() || $request->ajax()) {
            $msg = 'Berhasil scan ' . $item->code . ' (+' . $qty . ')';
            if ($scanInfo) $msg .= ' ' . $scanInfo;
            
            return response()->json([
                'status' => 'ok',
                'message' => $msg,
                'last_scanned_line_id' => $line->id,
                'order_no' => $orderNo !== '' ? $orderNo : null,
                'line' => [
                    'id' => $line->id,
                    'item_code' => $item->code,
                    'item_name' => $item->name,
                    'category_name' => $item->category?->name ?: 'Tanpa Kategori',
                    'remarks' => $line->remarks ?? null,
                    'qty_scanned' => (int) $line->qty_scanned,
                    'update_qty_url' => route('sales.shipments.update_line_qty', $line),
                    'stock_physical' => $stockPhysical,
                    'stock_packing' => $stockPacking,
                    'stock_available' => $stockAvailable,
                ],
                'totals' => [
                    'total_qty' => $result['total_qty'],
                    'total_lines' => $result['total_lines'],
                ],
                'stock_insufficient' => $this->stockInsufficientPayload($shipment),
            ]);
        }

        $redirectMsg = 'Berhasil scan ' . $item->code . ' (+' . $qty . ')';
        if ($scanInfo) $redirectMsg .= ' ' . $scanInfo;

        return redirect()
            ->route('sales.shipments.edit', $shipment)
            ->with('status', 'success')
            ->with('message', $redirectMsg)
            ->with('last_scanned_line_id', $line->id);
    }

    /**
     * Cek apakah semua lines shipment cukup stok di WH-RTS.
     * Return array error per item jika ada yang kurang, array kosong jika aman.
     */
    protected function checkStockSufficiency(Shipment $shipment, Warehouse $warehouse): array
    {
        $lines = $shipment->lines()->with('item:id,code,name')->get();
        if ($lines->isEmpty()) {
            return [];
        }

        $itemIds = $lines->pluck('item_id')->unique()->toArray();
        $stocks  = InventoryStock::where('warehouse_id', $warehouse->id)
            ->whereIn('item_id', $itemIds)
            ->get(['item_id', 'qty', 'allocated_qty'])
            ->keyBy('item_id');

        $errors = [];
        foreach ($lines as $line) {
            $qty    = (float) ($line->qty_scanned ?? 0);
            if ($qty <= 0) continue;

            $itemId  = (int) $line->item_id;
            $stock   = $stocks->get($itemId);
            
            $physQty    = (float) ($stock->qty ?? 0);
            $totalAlloc = (float) ($stock->allocated_qty ?? 0);
            $myAlloc    = (float) ($line->allocated_qty ?? 0);

            // True available = Stok fisik - Total alokasi semua draft + Alokasi milik shipment ini sendiri
            $trueAvailable = $physQty - $totalAlloc + $myAlloc;

            if (($trueAvailable + 0.0000001) < $qty) {
                $errors[] = [
                    'code'    => $line->item->code ?? "item#{$itemId}",
                    'name'    => $line->item->name ?? '',
                    'stock'   => (int) $trueAvailable,
                    'needed'  => (int) $qty,
                    'short'   => (int) ($qty - $trueAvailable),
                ];
            }
        }

        return $errors;
    }

    /**
     * Payload kekurangan stok WH-RTS untuk response AJAX (dipakai scan/ubah/hapus baris),
     * supaya panel "Stok WH-RTS tidak mencukupi" bisa diupdate live tanpa reload.
     */
    protected function stockInsufficientPayload(Shipment $shipment): array
    {
        $warehouse = $this->whRts();
        if (!$warehouse) {
            return [];
        }

        $shipment->load('lines.item');
        return $this->checkStockSufficiency($shipment, $warehouse);
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

        $locked->load(['lines.item', 'store']);

        // ===================================================
        // VALIDASI MISMATCH QTY FULFILLMENT VS SHIPMENT
        // ===================================================
        if ($locked->shipment_type === \App\Models\Shipment::TYPE_MARKETPLACE) {
            $scans = \App\Models\ShipmentOrderScan::where('shipment_id', $locked->id)
                ->whereNotIn('status', ['skip'])
                ->get();
            
            $fulfillmentIds = [];
            foreach ($scans as $scan) {
                if (empty($scan->fulfillment_id)) {
                    abort(422, "Shipment Marketplace tidak boleh memiliki order scan tanpa fulfillment_id.");
                }
                $fulfillmentIds[] = $scan->fulfillment_id;
            }
            $fulfillmentIds = array_unique($fulfillmentIds);

            if (empty($fulfillmentIds)) {
                abort(422, "Shipment Marketplace wajib memiliki minimal 1 order scan valid.");
            }

            $expectedItems = \App\Models\OrderFulfillmentLine::whereIn('fulfillment_id', $fulfillmentIds)
                ->whereNotNull('item_id')
                ->where('qty_fulfilled', '>', 0)
                ->selectRaw('item_id, SUM(qty_fulfilled) as total_expected')
                ->groupBy('item_id')
                ->pluck('total_expected', 'item_id')->toArray();
            
            $scannedItems = [];
            foreach ($locked->lines as $line) {
                $itemId = (int) $line->item_id;
                $qty = (int) ($line->qty_scanned ?? 0);
                if ($qty > 0) {
                    $scannedItems[$itemId] = ($scannedItems[$itemId] ?? 0) + $qty;
                }
            }

            foreach ($expectedItems as $itemId => $expectedQty) {
                $scannedQty = $scannedItems[$itemId] ?? 0;
                if ($scannedQty !== (int) $expectedQty) {
                    $itemCode = \App\Models\Item::find($itemId)->code ?? $itemId;
                    if ($scannedQty === 0) {
                        abort(422, "SKU {$itemCode} belum lengkap discan.");
                    }
                    abort(422, "Qty barang tidak sesuai untuk SKU {$itemCode}. Order: {$expectedQty}, Scan: {$scannedQty}.");
                }
            }

            foreach ($scannedItems as $itemId => $scannedQty) {
                if (!isset($expectedItems[$itemId])) {
                    $itemCode = \App\Models\Item::find($itemId)->code ?? $itemId;
                    abort(422, "SKU {$itemCode} tidak ada di order.");
                }
            }
        }

        $totalQty = 0;

        foreach ($locked->lines as $line) {
            $qty = (int) ($line->qty_scanned ?? 0);
            if ($qty <= 0) {
                continue;
            }

            $totalQty += $qty;

            // Release alokasi stok sebelum dipotong fisiknya
            if ($line->allocated_qty > 0) {
                $this->inventory->releaseStock(
                    warehouseId: $warehouse->id,
                    itemId: (int) $line->item_id,
                    qty: (int) $line->allocated_qty
                );
                
                $line->allocated_qty = 0;
                $line->save();
            }

            $this->inventory->stockOut(
                warehouseId: $warehouse->id,
                itemId: (int) $line->item_id,
                qty: $qty,
                date: $locked->date,
                sourceType: 'shipment',
                sourceId: (int) $locked->id,
                notes: 'Shipment ' . $locked->code . ' ke store ' . ($locked->store->code ?? '-'),
                allowNegative: false,
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

        // ===================================================
        // JURNAL COGS: Dr 5101 HPP / Cr Persediaan (per item_role)
        // ===================================================
        $this->postShipmentCogs($locked);

        // realtime daily sales
        $this->dailySales->applyShipmentPosted($locked, adsDays: 30, onlyActive: true);

        // ===================================================
        // UPDATE ORDER FULFILLMENT STATUS
        // ===================================================
        if ($locked->shipment_type === \App\Models\Shipment::TYPE_MARKETPLACE) {
            $scans = \App\Models\ShipmentOrderScan::where('shipment_id', $locked->id)
                ->whereNotNull('fulfillment_id')
                ->whereNotIn('status', ['skip'])
                ->get();

            $fulfillmentIds = $scans->pluck('fulfillment_id')->unique()->toArray();

            if (!empty($fulfillmentIds)) {
                \App\Models\OrderFulfillment::whereIn('id', $fulfillmentIds)
                    ->whereNotIn('status', [\App\Models\OrderFulfillment::STATUS_CONFIRMED, \App\Models\OrderFulfillment::STATUS_CANCELLED])
                    ->update([
                        'status' => \App\Models\OrderFulfillment::STATUS_CONFIRMED,
                        'confirmed_at' => now(),
                        'confirmed_by' => auth()->id(),
                    ]);

                // Update order marketplace menjadi SHIPPED
                $marketplaceOrderIds = \App\Models\OrderFulfillment::whereIn('id', $fulfillmentIds)
                    ->pluck('marketplace_order_id')->unique()->toArray();
                
                if (!empty($marketplaceOrderIds)) {
                    \App\Models\MarketplaceOrder::whereIn('id', $marketplaceOrderIds)
                        ->update([
                            'order_status' => 'READY_TO_HANDOVER',
                            'updated_at' => now(),
                        ]);
                }
            }
        }
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

        // ✅ Tolak jika ada item yang stok WH-RTS tidak cukup
        $shipment->load('lines.item');
        $stockErrors = $this->checkStockSufficiency($shipment, $warehouse);
        if (!empty($stockErrors)) {
            return redirect()->route('sales.shipments.edit', $shipment)
                ->with('status', 'error')
                ->with('message', 'Stok WH-RTS belum cukup, jadi barang belum bisa dikirim.')
                ->with('stock_insufficient', $stockErrors);
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

        // ✅ Tolak jika ada item yang stok WH-RTS tidak cukup
        $shipment->load('lines.item');
        $stockErrors = $this->checkStockSufficiency($shipment, $warehouse);
        if (!empty($stockErrors)) {
            return redirect()->route('sales.shipments.show', $shipment)
                ->with('stock_insufficient', $stockErrors);
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
            $warehouse = $this->whRts();
            $lines = ShipmentLine::where('shipment_id', $shipment->id)->get();
            foreach ($lines as $line) {
                if ($warehouse && $line->allocated_qty > 0) {
                    app(\App\Services\Inventory\InventoryService::class)->releaseStock(
                        $warehouse->id,
                        $line->item_id,
                        $line->allocated_qty
                    );
                }
                $line->delete();
            }
        });

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'status' => 'ok',
                'message' => 'Semua baris berhasil dibersihkan.',
                'totals' => ['total_qty' => 0, 'total_lines' => 0],
                'stock_insufficient' => [],
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

        DB::transaction(function () use ($line) {
            $warehouse = $this->whRts();
            if ($warehouse && $line->allocated_qty > 0) {
                app(\App\Services\Inventory\InventoryService::class)->releaseStock(
                    $warehouse->id,
                    $line->item_id,
                    $line->allocated_qty
                );
            }
            $line->delete();
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
                'message' => 'Baris berhasil dihapus.',
                'totals' => ['total_qty' => $totalQty, 'total_lines' => $totalLines],
                'stock_insufficient' => $this->stockInsufficientPayload($shipment),
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
            $warehouse = $this->whRts();
            $diff = $qty - $line->qty_scanned;
            
            if ($diff > 0 && $warehouse) {
                app(\App\Services\Inventory\InventoryService::class)->reserveStock(
                    warehouseId: $warehouse->id,
                    itemId: $line->item_id,
                    qty: $diff,
                    allowNegative: true // izinkan over-reserve saat edit; blokir di submit/post
                );
            } elseif ($diff < 0 && $warehouse) {
                app(\App\Services\Inventory\InventoryService::class)->releaseStock(
                    $warehouse->id,
                    $line->item_id,
                    abs($diff)
                );
            }

            if ($qty === 0) {
                $line->delete();
            } else {
                $line->qty_scanned = $qty;
                $line->allocated_qty = $qty;
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
                'stock_insufficient' => $this->stockInsufficientPayload($shipment),
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

                // Void jurnal COGS jika ada
                $this->journalService->voidBySource('shipment_cogs', (int) $locked->id);

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

    protected function postShipmentCogs(Shipment $shipment): void
    {
        $this->journalService->postShipmentCogsFromMutations($shipment);
    }

    /* ═══════════════════════════════════════════════════════════════════
     |  OPSI C — REKONSILIASI PESANAN
     |  Alur: scan batch bebas → input no pesanan → auto-match → confirm
     ═══════════════════════════════════════════════════════════════════ */

    /** Halaman rekonsiliasi pesanan untuk shipment draft */
    public function rekon(Shipment $shipment)
    {

        if ($shipment->status !== 'draft') {
            return redirect()->route('sales.shipments.show', $shipment)
                ->with('status', 'error')
                ->with('message', 'Hanya shipment draft yang bisa direkonsiliasi.');
        }

        $shipment->load(['store', 'lines.item', 'orderScans']);

        // Ringkasan batch: {item_id => {code, name, qty, qty_remaining}}
        $batchPool = $shipment->lines->mapWithKeys(fn ($l) => [
            $l->item_id => [
                'item_id'       => $l->item_id,
                'item_code'     => $l->item?->code ?? '-',
                'item_name'     => $l->item?->name ?? '-',
                'category_name' => $l->item?->category?->name ?? 'Tanpa Kategori',
                'qty'           => (int) $l->qty_scanned,
            ],
        ]);

        $savedOrderScans = $shipment->orderScans
            ->sortBy('id')
            ->map(function ($scan) {
                if (!empty($scan->raw_payload)) {
                    $payload = $scan->raw_payload;
                    $payload['decision'] = $scan->status ?: 'pending';
                    $payload['scanned_at'] = $scan->created_at?->format('d/m/Y H:i:s');
                    return $payload;
                }
                return [
                    'no' => $scan->order_no,
                    'found' => true,
                    'order' => [
                        'order_no' => $scan->order_no,
                        'source' => $scan->source ?: 'manual_scan',
                        'status' => $scan->status ?: 'pending',
                        'lines' => [],
                        'allocated' => [],
                    ],
                    'pool_full' => [],
                    'decision' => $scan->status ?: 'pending',
                    'subs' => [],
                    'scanned_at' => $scan->created_at?->format('d/m/Y H:i:s'),
                ];
            })
            ->values();

        return view('sales.shipments.rekon', compact('shipment', 'batchPool', 'savedOrderScans'));
    }

    public function confirmOrders(Shipment $shipment)
    {
        if ($shipment->status !== 'draft') {
            return redirect()->route('sales.shipments.show', $shipment)
                ->with('status', 'error')
                ->with('message', 'Hanya shipment draft yang bisa dikonfirmasi.');
        }

        $shipment->load(['store', 'lines.item', 'orderScans']);

        $batchPool = $shipment->lines->mapWithKeys(fn ($l) => [
            $l->item_id => [
                'item_id'   => $l->item_id,
                'item_code' => $l->item?->code ?? '-',
                'item_name' => $l->item?->name ?? '-',
                'qty'       => (int) $l->qty_scanned,
            ],
        ]);

        $savedOrderScans = $shipment->orderScans
            ->sortBy('id')
            ->map(fn ($scan) => [
                'no' => $scan->order_no,
                'decision' => $scan->status ?: 'pending',
            ])
            ->values();

        return view('sales.shipments.confirm_orders', compact('shipment', 'batchPool', 'savedOrderScans'));
    }

    /**
     * AJAX: analisis SATU no pesanan terhadap sisa pool batch.
     *
     * Input  (form):  order_no  — satu channel_order_no / invoice code
     *                 pool_used — JSON {item_id: qty_already_allocated} dari sisi JS
     *                             agar server bisa hitung sisa pool dengan benar
     * Output (JSON):  hasil match satu pesanan + seluruh pool yang tersisa (untuk picker substitusi)
     */
    public function rekonMatch(Request $request, Shipment $shipment): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'order_no'  => ['required', 'string', 'max:200'],
            'pool_used' => ['nullable', 'string'], // JSON: {item_id: qty_used}
        ]);

        $shipment->load('lines.item');

        // ── Build base pool dari shipment lines ──────────────────────────
        $basePool = $shipment->lines->mapWithKeys(fn ($l) => [
            $l->item_id => (int) $l->qty_scanned,
        ])->toArray();

        // ── Kurangi dengan apa yang sudah dialokasikan oleh pesanan sebelumnya ──
        $poolUsed = [];
        if ($request->filled('pool_used')) {
            try { $poolUsed = json_decode($request->pool_used, true) ?: []; } catch (\Throwable) {}
        }
        $pool = $basePool;
        foreach ($poolUsed as $itemId => $usedQty) {
            if (isset($pool[$itemId])) {
                $pool[$itemId] = max(0, $pool[$itemId] - (int) $usedQty);
            }
        }

        $no  = trim($request->order_no);

        $marketplaceOrder = \App\Models\MarketplaceOrder::with(['items.internalItem', 'store'])
            ->where('channel_order_id', $no)
            ->orWhere('shipping_awb_no', $no)
            ->first();

        if (!$marketplaceOrder) {

            $rawPayload = [
                'no' => $no,
                'found' => false,
                'order' => [
                    'order_no' => $no,
                    'source' => 'manual_scan',
                    'status' => 'pending',
                ],
                'decision' => 'pending',
            ];

            $scanModel = \App\Models\ShipmentOrderScan::updateOrCreate(
                [
                    'shipment_id' => $shipment->id,
                    'order_no' => $no,
                ],
                [
                    'fulfillment_id' => null,
                    'status' => 'pending',
                    'source' => 'manual_scan',
                    'raw_payload' => $rawPayload,
                ]
            );

            $rawPayload['scanned_at'] = $scanModel->created_at?->format('d/m/Y H:i:s');
            return response()->json(array_merge(['status' => 'ok'], $rawPayload));
        }

        // Validasi Status: Order baru bisa discan jika sedang diproses / packing (memiliki AWB)
        // Kita juga izinkan jika sudah READY_TO_HANDOVER (agar jika ter-scan dua kali tidak error melainkan bisa diproses ulang)
        if ($shipment->shipment_type !== 'manual' && !in_array($marketplaceOrder->order_status, ['PROCESSED', 'READY_TO_HANDOVER'])) {
            return response()->json([
                'status'  => 'error',
                'message' => "Order {$no} berstatus {$marketplaceOrder->order_status}. Hanya pesanan di tahap 'Sedang Dikemas' (PROCESSED) yang bisa di-scan.",
            ], 422);
        }

        $fulfillment = \App\Models\OrderFulfillment::where('marketplace_order_id', $marketplaceOrder->id)->first();
        if (!$fulfillment) {
            $service = app(\App\Services\OrderFulfillmentService::class);
            $fulfillment = $service->createDraft($marketplaceOrder);
        }

        $lines = [];
        $scanLog = [];
        foreach ($marketplaceOrder->items as $item) {
            $internalItem = $item->internalItem;
            $itemId = $internalItem ? $internalItem->id : null;
            
            $qtyNeed = (int) $item->qty;
            $qtyAlloc = 0;
            
            if ($itemId && isset($pool[$itemId]) && $pool[$itemId] > 0) {
                $alloc = min($qtyNeed, $pool[$itemId]);
                $qtyAlloc = $alloc;
                $pool[$itemId] -= $alloc;
            }
            
            $qtyShort = $qtyNeed - $qtyAlloc;
            
            $lines[] = [
                'item_id'   => $itemId,
                'item_code' => $internalItem ? $internalItem->code : ($item->marketplace_sku ?? '-'),
                'item_name' => $internalItem ? $internalItem->name : $item->item_name,
                'qty_need'  => $qtyNeed,
                'qty_alloc' => $qtyAlloc,
                'qty_short' => $qtyShort,
                'status'    => $qtyShort > 0 ? 'short' : 'ok',
            ];

            if ($qtyAlloc > 0) {
                $scanLog[] = [
                    'code' => $internalItem ? $internalItem->code : ($item->marketplace_sku ?? '-'),
                    'name' => $internalItem ? $internalItem->name : $item->item_name,
                    'qty'  => $qtyAlloc,
                ];
            }
        }

        // SIMPAN scan_log ke draft fulfillment agar langsung muncul 'Tersedia' di UI Marketplace Orders
        if ($fulfillment) {
            $fulfillment->update(['scan_log' => json_encode($scanLog)]);
        }

        $poolFull = $this->buildPoolSnapshot($shipment, $pool);

        $hasShort = false;
        foreach ($lines as $line) {
            if ($line['qty_short'] > 0) {
                $hasShort = true;
                break;
            }
        }
        $orderStatus = $hasShort ? 'partial' : 'ready';
        $decision = $hasShort ? 'pending' : 'fulfill';

        // OTOMATIS: ubah status ke Siap Kirim (READY_TO_HANDOVER) HANYA jika status OK (stok mencukupi)
        if (!$hasShort && $marketplaceOrder->order_status !== 'READY_TO_HANDOVER') {
            $marketplaceOrder->update([
                'order_status' => 'READY_TO_HANDOVER',
                'updated_at' => now(),
            ]);
        }

        $rawPayload = [
            'no' => $no,
            'found' => true,
            'order' => [
                'invoice_id'   => $marketplaceOrder->id,
                'invoice_code' => $marketplaceOrder->order_sn,
                'order_no'     => $marketplaceOrder->channel_order_id ?? $no,
                'shipping_awb_no' => $marketplaceOrder->shipping_awb_no,
                'store_id'     => $marketplaceOrder->store_id,
                'store_name'   => $marketplaceOrder->store?->name ?? '-',
                'store_code'   => $marketplaceOrder->store?->code ?? '-',
                'date'         => $marketplaceOrder->ordered_at?->toIso8601String(),
                'source'       => 'marketplace',
                'status'       => $orderStatus,
                'lines'        => $lines,
                'allocated'    => [],
            ],
            'pool_full' => $poolFull,
            'decision' => $decision,
            'subs' => [],
        ];

        \App\Models\ShipmentOrderScan::updateOrCreate(
            [
                'shipment_id' => $shipment->id,
                'order_no' => $no,
            ],
            [
                'fulfillment_id' => $fulfillment->id,
                'status' => $decision,
                'source' => 'marketplace',
                'raw_payload' => $rawPayload,
            ]
        );
        
        $rawPayload['scanned_at'] = now()->format('d/m/Y H:i:s');

        return response()->json(array_merge(['status' => 'ok'], $rawPayload));
    }

    /** Helper: build array snapshot pool saat ini untuk dikirim ke JS */
    private function buildPoolSnapshot(Shipment $shipment, array $pool): array
    {
        $snap = [];
        foreach ($pool as $itemId => $qty) {
            $line = $shipment->lines->firstWhere('item_id', $itemId);
            if ($line) {
                $snap[] = [
                    'item_id'   => $itemId,
                    'item_code' => $line->item?->code ?? '-',
                    'item_name' => $line->item?->name ?? '-',
                    'qty'       => max(0, $qty),
                ];
            }
        }
        return $snap;
    }

    /**
     * AJAX: terapkan keputusan rekonsiliasi.
     *
     * Menerima keputusan per-pesanan. Untuk sementara nomor pesanan hanya
     * dicatat sebagai pending/skip tanpa menautkan shipment ke invoice/order.
     */
    public function rekonApply(Request $request, Shipment $shipment): \Illuminate\Http\JsonResponse
    {
        if ($shipment->status !== 'draft') {
            return response()->json(['status' => 'error', 'message' => 'Shipment bukan draft.'], 409);
        }

        $request->validate([
            'decisions'             => ['required', 'array'],
            'decisions.*.order_no'  => ['required', 'string'],
            'decisions.*.action'    => ['required', 'in:fulfill,pending,skip'],
            'decisions.*.invoice_id'=> ['nullable', 'integer'],
            'decisions.*.subs'      => ['nullable', 'array'],
            // subs: [{item_id, sub_item_id, qty}]
            'submit_after'          => ['boolean'],
        ]);

        $decisions = collect($request->decisions)
            ->map(function ($row) {
                $row['order_no'] = strtoupper(trim((string) ($row['order_no'] ?? '')));
                $row['action'] = $row['action'] ?? 'pending';
                return $row;
            })
            ->filter(fn ($row) => $row['order_no'] !== '')
            ->unique('order_no')
            ->values();

        DB::transaction(function () use ($shipment, $decisions) {
            foreach ($decisions as $row) {
                if ($row['action'] === 'skip') {
                    continue; // Skip validasi ketat jika memang tidak diproses
                }

                $orderNo = $row['order_no'];
                $fulfillmentId = null;

                $scan = \App\Models\ShipmentOrderScan::where('shipment_id', $shipment->id)
                    ->where('order_no', $orderNo)
                    ->first();

                $isManual = ($shipment->shipment_type === 'manual') || ($scan && $scan->source === 'manual_scan');

                if ($isManual) {
                    // Manual order (unlinked)
                    $marketplaceOrder = null;
                    $fulfillment = null;
                } else {
                    $marketplaceOrder = \App\Models\MarketplaceOrder::where('channel_order_id', $orderNo)
                        ->orWhere('shipping_awb_no', $orderNo)
                        ->first();
                    
                    if (!$marketplaceOrder) {
                        abort(422, "Order/resi {$orderNo} tidak ditemukan di data marketplace.");
                    }

                    $fulfillment = \App\Models\OrderFulfillment::where('marketplace_order_id', $marketplaceOrder->id)->first(['id', 'status']);
                    if (!$fulfillment) {
                        $service = app(\App\Services\OrderFulfillmentService::class);
                        $fulfillment = $service->createDraft($marketplaceOrder);
                    }

                    if ($fulfillment->status === \App\Models\OrderFulfillment::STATUS_CANCELLED) {
                        abort(422, "Order/Resi {$orderNo} berstatus Cancelled. Jangan dikirim!");
                    }
                    
                    $fulfillmentId = $fulfillment->id;
                }

                $existingScan = \App\Models\ShipmentOrderScan::where('fulfillment_id', $fulfillmentId)
                    ->whereNotNull('fulfillment_id')
                    ->where('shipment_id', '!=', $shipment->id)
                    ->whereHas('shipment', function ($q) {
                        $q->whereIn('status', ['draft', 'submitted', 'posted']);
                    })->first();
                    
                if ($existingScan) {
                    $code = $existingScan->shipment->code ?? '-';
                    abort(422, "Pesanan sedang diproses di shipment lain: {$code}.");
                }

                if ($scan) {
                    $scan->update([
                        'fulfillment_id' => $fulfillmentId,
                        'status'       => $row['action'],
                        'confirmed_at' => now(),
                        'confirmed_by' => auth()->id(),
                    ]);
                } else {
                    \App\Models\ShipmentOrderScan::create([
                        'shipment_id'  => $shipment->id,
                        'order_no'     => $orderNo,
                        'fulfillment_id' => $fulfillmentId,
                        'status'       => $row['action'],
                        'source'       => $isManual ? 'manual_scan' : 'marketplace',
                        'raw_payload'  => $row,
                        'confirmed_at' => now(),
                        'confirmed_by' => auth()->id(),
                    ]);
                }

                // Hanya pindahkan ke Siap Kirim jika keputusannya fulfill (Siap Kirim)
                if ($marketplaceOrder && $row['action'] === 'fulfill' && $marketplaceOrder->order_status !== 'READY_TO_HANDOVER') {
                    $marketplaceOrder->update([
                        'order_status' => 'READY_TO_HANDOVER',
                        'updated_at' => now(),
                    ]);
                }
            }
        });

        $path = fn ($name, $params = []) => parse_url(route($name, $params), PHP_URL_PATH);
        $pendingNos = $decisions->where('action', 'pending')->pluck('order_no')->values();

        return response()->json([
            'status'       => 'ok',
            'message'      => 'Pesanan dikonfirmasi.' . ($pendingNos->isNotEmpty() ? ' ' . $pendingNos->count() . ' pesanan pending.' : ''),
            'pending_nos'  => $pendingNos,
            'submit_url'   => $path('sales.shipments.submit', $shipment),
            'show_url'     => $path('sales.shipments.show', $shipment),
            'edit_url'     => $path('sales.shipments.edit', $shipment),
        ]);
    }

    public function updateRekonScan(Request $request, Shipment $shipment, $orderNo)
    {
        if ($shipment->status !== 'draft') abort(403);
        
        $scan = \App\Models\ShipmentOrderScan::where('shipment_id', $shipment->id)
            ->where('order_no', $orderNo)
            ->firstOrFail();

        $payload = $scan->raw_payload;
        if ($request->has('new_order_no')) {
            $newNo = trim($request->input('new_order_no'));
            $scan->order_no = $newNo;
            $payload['no'] = $newNo;
            if (isset($payload['order'])) {
                $payload['order']['order_no'] = $newNo;
            }
        }
        
        if ($request->has('decision')) {
            $payload['decision'] = $request->input('decision');
            $scan->status = $request->input('decision') ?: 'pending';
            
            if ($scan->status === 'fulfill') {
                $fulfillment = \App\Models\OrderFulfillment::find($scan->fulfillment_id);
                if ($fulfillment) {
                    $mpOrder = \App\Models\MarketplaceOrder::find($fulfillment->marketplace_order_id);
                    if ($mpOrder && $mpOrder->order_status !== 'READY_TO_HANDOVER') {
                        $mpOrder->update(['order_status' => 'READY_TO_HANDOVER', 'updated_at' => now()]);
                    }
                }
            }
        }
        if ($request->has('subs')) {
            $payload['subs'] = $request->input('subs');
        }

        $scan->raw_payload = $payload;
        $scan->save();

        return response()->json(['status' => 'ok']);
    }

    public function deleteRekonScan(Request $request, Shipment $shipment, $orderNo)
    {
        if ($shipment->status !== 'draft') abort(403);
        
        $scan = \App\Models\ShipmentOrderScan::where('shipment_id', $shipment->id)
            ->where('order_no', $orderNo)
            ->firstOrFail();
            
        $scan->delete();
        
        return response()->json(['status' => 'ok']);
    }

    public function linkRekonScan(Request $request, Shipment $shipment, $orderNo)
    {
        if ($shipment->status !== 'draft') abort(403);
        
        $request->validate(['target_order_no' => 'required|string']);
        $targetNo = trim($request->input('target_order_no'));

        $scan = \App\Models\ShipmentOrderScan::where('shipment_id', $shipment->id)
            ->where('order_no', $orderNo)
            ->firstOrFail();

        $marketplaceOrder = \App\Models\MarketplaceOrder::with(['items.internalItem'])
            ->where('channel_order_id', $targetNo)
            ->orWhere('shipping_awb_no', $targetNo)
            ->first();

        if (!$marketplaceOrder) {
            return response()->json(['status' => 'error', 'message' => "Order/Resi {$targetNo} tidak ditemukan."], 422);
        }

        $pool = $this->buildBatchPool($shipment);

        $fulfillment = \App\Models\OrderFulfillment::where('marketplace_order_id', $marketplaceOrder->id)->first();
        if (!$fulfillment) {
            $service = app(\App\Services\OrderFulfillmentService::class);
            $fulfillment = $service->createDraft($marketplaceOrder);
        }

        $lines = [];
        foreach ($marketplaceOrder->items as $item) {
            $internalItem = $item->internalItem;
            $itemId = $internalItem ? $internalItem->id : null;
            $qtyNeed = (int) $item->qty;
            $qtyAlloc = 0;
            if ($itemId && isset($pool[$itemId]) && $pool[$itemId] > 0) {
                $alloc = min($qtyNeed, $pool[$itemId]);
                $qtyAlloc = $alloc;
                $pool[$itemId] -= $alloc;
            }
            $qtyShort = $qtyNeed - $qtyAlloc;
            $lines[] = [
                'item_id'   => $itemId,
                'item_code' => $internalItem ? $internalItem->code : ($item->marketplace_sku ?? '-'),
                'item_name' => $internalItem ? $internalItem->name : $item->item_name,
                'qty_need'  => $qtyNeed,
                'qty_alloc' => $qtyAlloc,
                'qty_short' => $qtyShort,
                'status'    => $qtyShort > 0 ? 'short' : 'ok',
            ];
        }

        $hasShort = false;
        foreach ($lines as $line) {
            if ($line['qty_short'] > 0) {
                $hasShort = true;
                break;
            }
        }
        $orderStatus = $hasShort ? 'partial' : 'ready';
        $decision = $hasShort ? 'pending' : 'fulfill';

        if (!$hasShort && $marketplaceOrder->order_status !== 'READY_TO_HANDOVER') {
            $marketplaceOrder->update(['order_status' => 'READY_TO_HANDOVER', 'updated_at' => now()]);
        }

        $poolFull = $this->buildPoolSnapshot($shipment, $pool);

        $rawPayload = [
            'no' => $targetNo,
            'found' => true,
            'order' => [
                'invoice_id'   => $marketplaceOrder->id,
                'invoice_code' => $marketplaceOrder->order_sn,
                'order_no'     => $marketplaceOrder->channel_order_id ?? $targetNo,
                'shipping_awb_no' => $marketplaceOrder->shipping_awb_no,
                'store_id'     => $marketplaceOrder->store_id,
                'store_name'   => $marketplaceOrder->store?->name ?? '-',
                'store_code'   => $marketplaceOrder->store?->code ?? '-',
                'date'         => $marketplaceOrder->ordered_at?->toIso8601String(),
                'source'       => 'marketplace',
                'status'       => $orderStatus,
                'lines'        => $lines,
                'allocated'    => [],
            ],
            'pool_full' => $poolFull,
            'decision' => $decision,
            'subs' => [],
        ];

        $scan->update([
            'order_no' => $targetNo,
            'fulfillment_id' => $fulfillment->id,
            'status' => $decision,
            'source' => 'marketplace',
            'raw_payload' => $rawPayload,
        ]);

        return response()->json(array_merge(['status' => 'ok'], $rawPayload));
    }

    public function resetRekonScans(Shipment $shipment)
    {
        if ($shipment->status !== 'draft') abort(403);
        
        \App\Models\ShipmentOrderScan::where('shipment_id', $shipment->id)
            ->where('status', 'pending')
            ->delete();

        return response()->json(['status' => 'ok']);
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

    public function report(Request $request)
    {
        // TODO: sesuaikan view yang kamu punya
        // return view('sales.shipments.report');

        // sementara biar route tidak 500:
        return redirect()->route('sales.shipments.index')
            ->with('info', 'Report belum diimplementasikan.');
    }

}
