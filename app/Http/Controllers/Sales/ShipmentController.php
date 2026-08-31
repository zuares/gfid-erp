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
use App\Models\ShipmentWave;
use App\Models\Store;
use App\Models\SystemSetting;
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
    /**
     * Marketplace statuses that are eligible for shipment reconciliation.
     * Keep cancelled, waiting, completed, and other terminal states out of
     * the reconciliation picker.
     */
    private const RECONCILIATION_MARKETPLACE_STATUSES = [
        'READY_TO_SHIP',
        'PROCESSED',
        'SHIPPED',
    ];

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
                    'dispatch_mode' => Shipment::DISPATCH_SINGLE,
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

    protected function usesDailyWaves(Shipment $shipment): bool
    {
        return ($shipment->dispatch_mode ?? Shipment::DISPATCH_SINGLE) === Shipment::DISPATCH_DAILY;
    }

    protected function normalizeOrderNumber(?string $value): string
    {
        $normalized = mb_strtoupper(trim((string) $value));

        return in_array(mb_strtolower($normalized), ['undefined', 'null', 'nan'], true)
            ? ''
            : $normalized;
    }

    /**
     * Ambil gelombang aktif untuk satu shipment harian. Shipment lama yang
     * belum punya wave diadopsi ke gelombang pertama agar tetap kompatibel.
     */
    protected function ensureOpenShipmentWave(Shipment $shipment, ?string $label = null): ShipmentWave
    {
        $wave = ShipmentWave::query()
            ->where('shipment_id', $shipment->id)
            ->where('status', ShipmentWave::STATUS_OPEN)
            ->latest('sequence')
            ->first();

        if ($wave) {
            return $wave;
        }

        $nextSequence = ((int) ShipmentWave::where('shipment_id', $shipment->id)->max('sequence')) + 1;
        $wave = ShipmentWave::create([
            'shipment_id' => $shipment->id,
            'sequence' => $nextSequence,
            'code' => $shipment->code . '-W' . str_pad((string) $nextSequence, 2, '0', STR_PAD_LEFT),
            'label' => $label ?: match ($nextSequence) {
                1 => 'Siang',
                2 => 'Sore',
                default => 'Gelombang ' . $nextSequence,
            },
            'status' => ShipmentWave::STATUS_OPEN,
            'opened_at' => now(),
        ]);

        // Data shipment lama belum memiliki wave_id. Hanya adopsi saat wave
        // pertama dibuat; wave berikutnya tidak boleh mengambil baris lama.
        if ($nextSequence === 1) {
            ShipmentLine::where('shipment_id', $shipment->id)
                ->whereNull('shipment_wave_id')
                ->update(['shipment_wave_id' => $wave->id]);
            ShipmentOrderScan::where('shipment_id', $shipment->id)
                ->whereNull('shipment_wave_id')
                ->update(['shipment_wave_id' => $wave->id]);
        }

        return $wave;
    }

    protected function currentShipmentWave(Shipment $shipment): ?ShipmentWave
    {
        if (!$this->usesDailyWaves($shipment)) {
            return null;
        }

        return $this->ensureOpenShipmentWave($shipment);
    }

    protected function waveStockErrors(ShipmentWave $wave, Warehouse $warehouse): array
    {
        $lines = $wave->lines()->with('item:id,code,name')->get();
        if ($lines->isEmpty()) {
            return [];
        }

        $stocks = InventoryStock::where('warehouse_id', $warehouse->id)
            ->whereIn('item_id', $lines->pluck('item_id')->unique())
            ->get(['item_id', 'qty', 'allocated_qty'])
            ->keyBy('item_id');

        $requirements = $lines
            ->groupBy('item_id')
            ->map(fn ($itemLines) => [
                'item_id' => (int) $itemLines->first()->item_id,
                'needed' => (float) $itemLines->sum(fn ($line) => (float) ($line->qty_scanned ?? 0)),
                'own_allocation' => (float) $itemLines->sum(fn ($line) => (float) ($line->allocated_qty ?? 0)),
                'item' => $itemLines->first()->item,
            ]);

        $errors = [];
        foreach ($requirements as $requirement) {
            $needed = $requirement['needed'];
            if ($needed <= 0) {
                continue;
            }

            $itemId = $requirement['item_id'];
            $stock = $stocks->get($itemId);
            $physical = (float) ($stock->qty ?? 0);
            $allocated = (float) ($stock->allocated_qty ?? 0);
            $ownAllocation = $requirement['own_allocation'];
            $available = $physical - $allocated + $ownAllocation;

            if (($available + 0.0000001) < $needed) {
                $errors[] = [
                    'code' => $requirement['item']?->code ?? 'ITEM-' . $itemId,
                    'name' => $requirement['item']?->name ?? '',
                    'stock' => (int) $available,
                    'needed' => (int) $needed,
                    'short' => (int) ($needed - $available),
                ];
            }
        }

        return $errors;
    }

    protected function salesOperationalSetting(string $key, mixed $default = null): mixed
    {
        $keys = [
            'lookup_mode' => SystemSetting::KEY_SALES_LOOKUP_MODE,
            'lookup_sources' => SystemSetting::KEY_SALES_LOOKUP_SOURCES,
            'lookup_identifiers' => SystemSetting::KEY_SALES_LOOKUP_IDENTIFIERS,
            'same_store' => SystemSetting::KEY_SALES_LOOKUP_SAME_STORE,
            'block_duplicate' => SystemSetting::KEY_SALES_LOOKUP_BLOCK_DUPLICATE,
            'allow_unlinked_submit' => SystemSetting::KEY_SALES_ALLOW_UNLINKED_SUBMIT,
            'allow_mixed_linkage' => SystemSetting::KEY_SALES_ALLOW_MIXED_LINKAGE,
            'status_timing' => SystemSetting::KEY_SALES_STATUS_TIMING,
            'record_only_daily_sales' => SystemSetting::KEY_SALES_RECORD_ONLY_DAILY_SALES,
        ];

        $value = SystemSetting::get($keys[$key] ?? $key, $default);

        if (in_array($key, ['lookup_sources', 'lookup_identifiers'], true)) {
            if (is_array($value)) {
                return $value;
            }

            $decoded = json_decode((string) $value, true);

            return is_array($decoded) ? $decoded : (is_array($default) ? $default : []);
        }

        if (in_array($key, ['same_store', 'block_duplicate', 'allow_unlinked_submit', 'allow_mixed_linkage', 'record_only_daily_sales'], true)) {
            return (string) $value === '1';
        }

        return $value;
    }

    protected function salesLookupSources(): array
    {
        return array_values(array_intersect(
            ['marketplace_order', 'sales_invoice'],
            $this->salesOperationalSetting('lookup_sources', ['marketplace_order', 'sales_invoice'])
        ));
    }

    protected function salesLookupIdentifiers(): array
    {
        return array_values(array_intersect(
            ['shipping_awb_no', 'channel_order_id', 'booking_sn', 'external_order_id', 'invoice_code', 'channel_invoice_no'],
            $this->salesOperationalSetting('lookup_identifiers', [
                'shipping_awb_no', 'channel_order_id', 'booking_sn', 'external_order_id', 'invoice_code', 'channel_invoice_no',
            ])
        ));
    }

    protected function shouldUpdateMarketplaceStatus(string $event): bool
    {
        $timing = (string) $this->salesOperationalSetting('status_timing', 'on_post');

        return $timing === 'on_' . $event;
    }

    protected function updateMarketplaceStatusIfAllowed(?\App\Models\MarketplaceOrder $order, string $event): void
    {
        if (!$order || !$this->shouldUpdateMarketplaceStatus($event)
            || in_array($order->order_status, ['READY_TO_HANDOVER', 'SHIPPED', 'DELIVERED', 'COMPLETED', 'CANCELLED'], true)) {
            return;
        }

        $order->update([
            'order_status' => 'READY_TO_HANDOVER',
            'updated_at' => now(),
        ]);
    }

    protected function promoteMarketplaceOrderAfterScan(?\App\Models\MarketplaceOrder $order): void
    {
        if (!$order || !in_array($order->order_status, ['READY_TO_SHIP', 'PROCESSED'], true)) {
            return;
        }

        $order->update([
            'order_status' => 'READY_TO_HANDOVER',
            'updated_at' => now(),
        ]);
    }

    protected function activeDuplicateScan(?int $fulfillmentId, Shipment $shipment): ?ShipmentOrderScan
    {
        if (!$fulfillmentId || !$this->salesOperationalSetting('block_duplicate', true)) {
            return null;
        }

        return ShipmentOrderScan::query()
            ->where('fulfillment_id', $fulfillmentId)
            ->where('shipment_id', '!=', $shipment->id)
            ->whereHas('shipment', fn ($query) => $query
                ->whereIn('status', ['draft', 'submitted', 'posted'])
                ->whereNull('cancelled_at'))
            ->with('shipment:id,code')
            ->first();
    }

    protected function restoreMarketplaceOrdersAfterCancellation(Shipment $shipment): int
    {
        $fulfillmentIds = ShipmentOrderScan::query()
            ->where('shipment_id', $shipment->id)
            ->whereNotNull('fulfillment_id')
            ->whereNotIn('status', ['skip'])
            ->pluck('fulfillment_id')
            ->filter()
            ->unique()
            ->values();

        if ($fulfillmentIds->isEmpty()) {
            return 0;
        }

        $marketplaceOrderIds = \App\Models\OrderFulfillment::query()
            ->whereIn('id', $fulfillmentIds)
            ->pluck('marketplace_order_id')
            ->filter()
            ->unique()
            ->values();

        if ($marketplaceOrderIds->isEmpty()) {
            return 0;
        }

        // Order yang sudah masuk Siap Kirim dikembalikan ke Dikemas → Belum Packing.
        $restorableOrderIds = \App\Models\MarketplaceOrder::query()
            ->whereIn('id', $marketplaceOrderIds)
            ->whereIn('order_status', ['READY_TO_HANDOVER', 'READY_TO_SHIP', 'PROCESSED', 'MATCHED'])
            ->pluck('id');

        if ($restorableOrderIds->isEmpty()) {
            return 0;
        }

        $restored = \App\Models\MarketplaceOrder::query()
            ->whereIn('id', $restorableOrderIds)
            ->update([
                'order_status' => 'PROCESSED',
                'updated_at' => now(),
            ]);

        // Fulfillment confirmed berasal dari shipment yang dibatalkan; buka kembali
        // ke tahap packed agar dapat diproses/konfirmasi ulang.
        \App\Models\OrderFulfillment::query()
            ->whereIn('id', $fulfillmentIds)
            ->whereIn('marketplace_order_id', $restorableOrderIds)
            ->where('status', \App\Models\OrderFulfillment::STATUS_CONFIRMED)
            ->update([
                'status' => \App\Models\OrderFulfillment::STATUS_PACKED,
                'confirmed_at' => null,
                'confirmed_by' => null,
                'updated_at' => now(),
            ]);

        return (int) $restored;
    }

    protected function recordUnlinkedOrderScan(Shipment $shipment, string $orderNo, array $pool, ?string $lookupStatus = null): \Illuminate\Http\JsonResponse
    {
        $orderNo = $this->normalizeOrderNumber($orderNo);
        if ($orderNo === '') {
            return response()->json([
                'status' => 'error',
                'message' => 'Nomor order tidak valid atau belum terbaca.',
            ], 422);
        }

        $currentWave = $this->currentShipmentWave($shipment);
        $rawPayload = [
            'no' => $orderNo,
            'found' => false,
            'mode' => 'record_only',
            'lookup_status' => $lookupStatus,
            'order' => [
                'order_no' => $orderNo,
                'source' => 'manual_scan',
                'status' => 'pending',
            ],
            'decision' => 'pending',
            'pool_full' => $this->buildPoolSnapshot($shipment, $pool),
            'subs' => [],
        ];

        $scan = ShipmentOrderScan::updateOrCreate(
            [
                'shipment_id' => $shipment->id,
                'order_no' => $orderNo,
            ],
            [
                'shipment_wave_id' => $currentWave?->id,
                'fulfillment_id' => null,
                'status' => 'pending',
                'source' => 'manual_scan',
                'raw_payload' => $rawPayload,
            ]
        );

        $rawPayload['scanned_at'] = $scan->created_at?->format('d/m/Y H:i:s');

        return response()->json(array_merge(['status' => 'ok'], $rawPayload));
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
        $search = trim((string) $request->get('q', ''));
        $scanMode = $request->get('scan_mode', 'all');
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');
        $period = $request->get('period', 'all');
        if (!$dateFrom && !$dateTo && in_array($period, ['today', 'week', 'month'], true)) {
            $periodStart = match ($period) {
                'today' => now()->startOfDay(),
                'week' => now()->startOfWeek(),
                'month' => now()->startOfMonth(),
            };
            $dateFrom = $periodStart->toDateString();
            $dateTo = now()->toDateString();
        }
        $sort = $request->get('sort', 'date');
        $direction = strtolower((string) $request->get('direction', 'desc')) === 'asc' ? 'asc' : 'desc';
        $allowedSorts = ['date', 'code', 'order_scans_count', 'lines_count', 'status'];
        if (!in_array($sort, $allowedSorts, true)) {
            $sort = 'date';
        }
        $canSeeNominal = $this->canSeeNominal();

        $query = Shipment::query()
            ->with(['store', 'lines', 'lines.item.category'])
            ->withCount(['orderScans', 'lines'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('code', 'like', '%' . $search . '%')
                        ->orWhereHas('orderScans', function ($orders) use ($search) {
                            $orders->where('order_no', 'like', '%' . $search . '%');
                        });
                });
            })
            ->when(in_array($scanMode, ['item_first', 'order_first'], true), fn ($query) => $query->where('scan_mode', $scanMode))
            ->when($dateFrom, fn ($query) => $query->whereDate('date', '>=', $dateFrom))
            ->when($dateTo, fn ($query) => $query->whereDate('date', '<=', $dateTo));

        if ($statusFilter === 'cancelled') {
            $query->whereNotNull('cancelled_at');
        } elseif (in_array($statusFilter, ['draft', 'submitted', 'posted'], true)) {
            $query->whereNull('cancelled_at')->where('status', $statusFilter);
        }

        $kpiQuery = clone $query;
        $kpi = [
            'total' => (clone $kpiQuery)->reorder()->count(),
            'draft' => (clone $kpiQuery)->reorder()->whereNull('cancelled_at')->where('status', 'draft')->count(),
            'submitted' => (clone $kpiQuery)->reorder()->whereNull('cancelled_at')->where('status', 'submitted')->count(),
            'posted' => (clone $kpiQuery)->reorder()->whereNull('cancelled_at')->where('status', 'posted')->count(),
        ];

        if ($sort === 'order_scans_count' || $sort === 'lines_count') {
            $query->orderBy($sort, $direction)->orderByDesc('id');
        } else {
            $query->orderBy($sort, $direction)->orderByDesc('id');
        }

        $shipments = $query->paginate(20)->withQueryString();

        $warehouse = $this->whRts();

        // ✅ Transform ringkas + admin tidak menghitung nominal
        $shipments->getCollection()->transform(function (Shipment $shipment) use ($canSeeNominal, $warehouse) {
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
            $shipment->stock_insufficient_calc = ($warehouse && $shipment->status === 'draft')
                ? $this->checkStockSufficiency($shipment, $warehouse)
                : [];

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
            'search',
            'scanMode',
            'dateFrom',
            'dateTo',
            'period',
            'sort',
            'direction',
            'kpi',
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
            'orderScans.lines.item',
            'orderScans.fulfillment.marketplaceOrder.store',
            'waves.lines.item',
            'waves.orderScans',
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
        $isDaily = $this->usesDailyWaves($shipment);
        $waves = $shipment->waves ?? collect();

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
            'isDaily',
            'waves',
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
            'scan_mode'        => $data['scan_mode'] ?? 'item_first',
            'dispatch_mode'    => $data['dispatch_mode'] ?? ($data['shipment_type'] === Shipment::TYPE_MARKETPLACE ? Shipment::DISPATCH_DAILY : Shipment::DISPATCH_SINGLE),
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
                    'scan_mode'  => $shipment->scan_mode,
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

        $currentWave = $this->currentShipmentWave($shipment);
        $shipment->load(['store', 'lines.item.category', 'waves']);
        if ($currentWave) {
            $shipment->setRelation(
                'lines',
                $shipment->lines->where('shipment_wave_id', $currentWave->id)->values()
            );
        }

        // Hitung kekurangan stok secara live agar panel tetap muncul saat reload
        // dan otomatis hilang begitu stok/qty sudah beres.
        $warehouse = $this->whRts();
        $stockInsufficient = $warehouse
            ? $this->checkStockSufficiency($shipment, $warehouse)
            : [];

        $importPreview = session('shipment_import_preview.' . $shipment->id . '.rows') ?? null;
        $importPreviewSummary = session('shipment_import_preview.' . $shipment->id . '.summary') ?? null;

        $postedWaveCount = $shipment->waves->where('status', ShipmentWave::STATUS_POSTED)->count();

        return view('sales.shipments.edit', compact(
            'shipment',
            'currentWave',
            'postedWaveCount',
            'importPreview',
            'importPreviewSummary',
            'stockInsufficient'
        ));
    }


    public function editOrderFirst(Shipment $shipment)
    {
        if ($shipment->status !== 'draft') {
            return redirect()
                ->route('sales.shipments.show', $shipment)
                ->with('status', 'error')
                ->with('message', 'Shipment bukan draft, tidak bisa scan nomor order lagi.');
        }

        $currentWave = $this->currentShipmentWave($shipment);

        if (($shipment->scan_mode ?? 'item_first') === 'item_first') {
            $shipment->load(['lines.item', 'orderScans.lines.item']);
            if ($currentWave) {
                $shipment->setRelation(
                    'lines',
                    $shipment->lines->where('shipment_wave_id', $currentWave->id)->values()
                );
                $shipment->setRelation(
                    'orderScans',
                    $shipment->orderScans->where('shipment_wave_id', $currentWave->id)->values()
                );
            }

            if ($shipment->lines->isEmpty()) {
                return redirect()
                    ->route('sales.shipments.edit', $shipment)
                    ->with('status', 'error')
                    ->with('message', 'Scan item terlebih dahulu sebelum scan nomor order.');
            }

            return view('sales.shipments.scan_order_item_first', compact('shipment'));
        }

        $shipment->load([
            'store',
            'lines.item',
            'orderScans.lines.item',
        ]);

        $savedOrderScans = $shipment->orderScans
            ->sortBy('id')
            ->map(function ($scan) use ($shipment) {
                $payload = is_array($scan->raw_payload) ? $scan->raw_payload : [];
                $scanLines = $scan->lines;

                // Backward-compatible fallback: shipment lama yang hanya
                // punya satu order masih bisa ditampilkan grouped by order.
                if ($shipment->orderScans->count() === 1) {
                    $scanLines = $scanLines
                        ->merge($shipment->lines->filter(fn ($line) => !$line->shipment_order_scan_id))
                        ->values();
                }

                return [
                    'id' => $scan->id,
                    'code' => $scan->order_no,
                    'order_no' => $scan->order_no,
                    'status' => $scan->status,
                    'label' => $payload['label'] ?? 'Pencatatan order',
                    'items' => $scanLines
                        ->map(fn ($line) => [
                            'line_id' => $line->id,
                            'item_id' => $line->item_id,
                            'code' => $line->item?->code ?? '-',
                            'name' => $line->item?->name ?? '',
                            'qty' => (int) $line->qty_scanned,
                        ])
                        ->values(),
                ];
            })
            ->values();

        return view('sales.shipments.edit_order_first', compact('shipment', 'savedOrderScans'));
    }

    /**
     * Catat nomor order hasil scan.
     *
     * Jika lookup aktif dan order berada di tahap yang bisa diproses, order
     * ditautkan otomatis. Jika tidak ditemukan atau berada di tahap lain,
     * tetap dicatat sebagai manual/belum tertaut untuk direkonsiliasi nanti.
     */
    public function scanOrder(Request $request, Shipment $shipment)
    {
        if ($shipment->status !== 'draft') {
            return response()->json([
                'status' => 'error',
                'message' => 'Shipment sudah tidak berstatus draft.',
            ], 409);
        }

        if (($shipment->scan_mode ?? 'item_first') === 'item_first'
            && !$shipment->lines()->exists()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Scan item terlebih dahulu sebelum scan nomor order.',
            ], 422);
        }

        $currentWave = $this->currentShipmentWave($shipment);

        $data = $request->validate([
            'order_no' => ['required', 'string', 'max:200'],
        ]);

        $orderNo = $this->normalizeOrderNumber($data['order_no']);
        if ($orderNo === '') {
            return response()->json([
                'status' => 'error',
                'message' => 'Nomor order belum diisi.',
            ], 422);
        }

        $lookupMode = (string) $this->salesOperationalSetting('lookup_mode', 'record_only');
        $marketplaceOrder = null;
        $salesInvoice = null;
        $fulfillment = null;
        $lookupFallbackStatus = null;

        if ($lookupMode === 'auto_link_on_scan') {
            $marketplaceOrder = $this->marketplaceOrderQuery($orderNo, $shipment)->first();
            if ($marketplaceOrder) {
                if ($shipment->shipment_type !== 'manual' && !in_array($marketplaceOrder->order_status, ['READY_TO_SHIP', 'PROCESSED', 'READY_TO_HANDOVER', 'SHIPPED', 'DELIVERED', 'COMPLETED'], true)) {
                    $existingFulfillment = \App\Models\OrderFulfillment::query()
                        ->where('marketplace_order_id', $marketplaceOrder->id)
                        ->first();
                    if ($existingFulfillment?->status === \App\Models\OrderFulfillment::STATUS_CANCELLED) {
                        return response()->json(['status' => 'error', 'message' => "Order {$orderNo} berstatus Cancelled. Jangan dikirim!"], 422);
                    }

                    // Di luar tahap Dikemas tetap boleh dicatat sebagai manual/
                    // belum tertaut. Jangan paksa link ke marketplace.
                    $lookupFallbackStatus = $marketplaceOrder->order_status;
                    $marketplaceOrder = null;
                } else {
                    $fulfillment = \App\Models\OrderFulfillment::query()
                        ->where('marketplace_order_id', $marketplaceOrder->id)
                        ->first();
                    if (!$fulfillment) {
                        $fulfillment = app(\App\Services\OrderFulfillmentService::class)->createDraft($marketplaceOrder);
                    }
                    if ($fulfillment->status === \App\Models\OrderFulfillment::STATUS_CANCELLED) {
                        return response()->json(['status' => 'error', 'message' => "Order {$orderNo} berstatus Cancelled. Jangan dikirim!"], 422);
                    }
                }
            } else {
                $salesInvoice = $this->salesInvoiceQuery($orderNo, $shipment)->first();
                if (!$salesInvoice) {
                    $lookupFallbackStatus = 'not_found';
                }
            }

            $duplicate = $this->activeDuplicateScan($fulfillment?->id, $shipment);
            if ($duplicate) {
                return response()->json([
                    'status' => 'error',
                    'message' => "Pesanan sedang diproses di shipment lain: {$duplicate->shipment?->code}.",
                ], 422);
            }
        }

        $scan = DB::transaction(function () use ($shipment, $currentWave, $orderNo, $lookupMode, $lookupFallbackStatus, $marketplaceOrder, $salesInvoice, $fulfillment) {
            Shipment::query()
                ->lockForUpdate()
                ->findOrFail($shipment->id);

            $scan = ShipmentOrderScan::query()
                ->where('shipment_id', $shipment->id)
                ->where('order_no', $orderNo)
                ->lockForUpdate()
                ->first();

            if (!$scan) {
                $scan = ShipmentOrderScan::create([
                    'shipment_id' => $shipment->id,
                    'shipment_wave_id' => $currentWave?->id,
                    'order_no' => $orderNo,
                    'fulfillment_id' => $fulfillment?->id,
                    'status' => 'pending',
                    'source' => 'scanner',
                    'raw_payload' => [
                        'mode' => $lookupMode === 'auto_link_on_scan' && $fulfillment ? 'auto_link' : 'record_only',
                        'no' => $orderNo,
                        'label' => $fulfillment
                            ? 'Tertaut otomatis'
                            : ($lookupFallbackStatus !== null ? 'Belum tertaut' : 'Pencatatan order'),
                        'order' => [
                            'order_no' => $orderNo,
                            'source' => $fulfillment ? 'marketplace' : 'record_only',
                            'status' => 'pending',
                        ],
                        'linked_source' => $fulfillment ? 'marketplace_order' : ($salesInvoice ? 'sales_invoice' : null),
                        'lookup_status' => $lookupFallbackStatus,
                        'sales_invoice_id' => $salesInvoice?->id,
                    ],
                ]);
            } else {
                $payload = is_array($scan->raw_payload) ? $scan->raw_payload : [];
                $scan->update([
                    'shipment_wave_id' => $currentWave?->id ?: $scan->shipment_wave_id,
                    'fulfillment_id' => $fulfillment?->id,
                    'raw_payload' => array_merge($payload, [
                        'no' => $orderNo,
                        'mode' => $lookupMode === 'auto_link_on_scan' && $fulfillment ? 'auto_link' : ($payload['mode'] ?? 'record_only'),
                        'label' => $fulfillment
                            ? 'Tertaut otomatis'
                            : ($lookupFallbackStatus !== null ? 'Belum tertaut' : ($payload['label'] ?? 'Pencatatan order')),
                        'linked_source' => $fulfillment ? 'marketplace_order' : ($salesInvoice ? 'sales_invoice' : ($payload['linked_source'] ?? null)),
                        'lookup_status' => $lookupFallbackStatus ?? ($payload['lookup_status'] ?? null),
                        'sales_invoice_id' => $salesInvoice?->id ?? ($payload['sales_invoice_id'] ?? null),
                        'order' => array_merge(
                            is_array($payload['order'] ?? null) ? $payload['order'] : [],
                            ['order_no' => $orderNo]
                        ),
                    ]),
                ]);
            }

            if ($salesInvoice && !$shipment->sales_invoice_id) {
                $shipment->update(['sales_invoice_id' => $salesInvoice->id]);
            }

            return $scan;
        });

        if ($marketplaceOrder && $fulfillment) {
            $this->promoteMarketplaceOrderAfterScan($marketplaceOrder);
            $this->updateMarketplaceStatusIfAllowed($marketplaceOrder, 'link');
        }

        return response()->json([
            'status' => 'ok',
            'created' => $scan->wasRecentlyCreated,
            'duplicate' => !$scan->wasRecentlyCreated,
            'message' => $lookupFallbackStatus !== null
                ? "Order {$orderNo} dicatat sebagai manual/belum tertaut."
                : ($scan->wasRecentlyCreated
                    ? "Order {$orderNo} dicatat."
                    : "Order {$orderNo} sudah tercatat."),
            'order' => [
                'code' => $orderNo,
                'order_no' => $orderNo,
                'label' => $fulfillment
                    ? 'Tertaut otomatis'
                    : ($lookupFallbackStatus !== null ? 'Belum tertaut' : 'Pencatatan order'),
                'linked_source' => $fulfillment ? 'marketplace_order' : ($salesInvoice ? 'sales_invoice' : null),
            ],
        ]);
    }

    public function deleteOrderScan(Request $request, Shipment $shipment)
    {
        if ($shipment->status !== 'draft') {
            return response()->json([
                'status' => 'error',
                'message' => 'Shipment sudah tidak berstatus draft.',
            ], 409);
        }

        $data = $request->validate([
            'order_no' => ['required', 'string', 'max:200'],
        ]);

        $orderNo = mb_strtoupper(trim($data['order_no']));

        DB::transaction(function () use ($shipment, $orderNo) {
            Shipment::query()
                ->lockForUpdate()
                ->findOrFail($shipment->id);

            ShipmentOrderScan::query()
                ->where('shipment_id', $shipment->id)
                ->where('order_no', $orderNo)
                ->delete();
        });

        return response()->json([
            'status' => 'ok',
            'message' => "Order {$orderNo} dihapus dari pencatatan shipment.",
        ]);
    }


    public function scanLookup(Request $request, Shipment $shipment)
    {
        $code = mb_strtoupper(trim((string) $request->query('code', '')));

        if ($code === '') {
            return response()->json([
                'type' => 'empty',
            ]);
        }

        $item = $this->finishedGoodItemQuery($code)
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

        $lookupMode = (string) $this->salesOperationalSetting('lookup_mode', 'record_only');
        $marketplaceOrder = $lookupMode !== 'record_only'
            ? $this->marketplaceOrderQuery($code, $shipment)
                ->with('store:id,code,name')
                ->first(['id', 'store_id', 'channel_order_id', 'booking_sn', 'external_order_id', 'shipping_awb_no'])
            : null;

        if ($marketplaceOrder) {
            $orderCode = $marketplaceOrder->channel_order_id
                ?: $marketplaceOrder->shipping_awb_no
                ?: $marketplaceOrder->external_order_id
                ?: $marketplaceOrder->booking_sn
                ?: $code;

            return response()->json([
                'type' => 'order',
                'order' => [
                    'id' => $marketplaceOrder->id,
                    'code' => $orderCode,
                    'channel_order_id' => $marketplaceOrder->channel_order_id,
                    'shipping_awb_no' => $marketplaceOrder->shipping_awb_no,
                    'store_code' => $marketplaceOrder->store?->code,
                    'store_name' => $marketplaceOrder->store?->name,
                ],
            ]);
        }

        if ($lookupMode !== 'record_only') {
            $salesInvoice = $this->salesInvoiceQuery($code, $shipment)->with('store:id,code,name')->first();
            if ($salesInvoice) {
                return response()->json([
                    'type' => 'order',
                    'order' => [
                        'id' => $salesInvoice->id,
                        'code' => $salesInvoice->code ?: ($salesInvoice->channel_order_no ?: $code),
                        'channel_order_id' => $salesInvoice->channel_order_no,
                        'store_code' => $salesInvoice->store?->code,
                        'store_name' => $salesInvoice->store?->name,
                        'source' => 'sales_invoice',
                    ],
                ]);
            }
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

        $currentWave = $this->currentShipmentWave($shipment);

        $data = $request->validate([
            'scan_code' => ['required', 'string', 'max:255'],
            'qty' => ['nullable', 'integer', 'min:1'],
            'order_no' => ['nullable', 'string', 'max:200'],
        ]);

        $scanCode = mb_strtoupper(trim($data['scan_code']));
        $qty = max(1, (int) ($data['qty'] ?? 1));
        $orderNo = mb_strtoupper(trim((string) ($data['order_no'] ?? '')));

        $item = $this->finishedGoodItemQuery($scanCode)
            ->with('category:id,name')
            ->first();

        if (!$item) {
            $marketplaceOrder = $this->marketplaceOrderQuery($scanCode, $shipment)
                ->first(['id', 'channel_order_id', 'shipping_awb_no', 'booking_sn', 'external_order_id']);

            if ($marketplaceOrder) {
                $message = "Kode {$scanCode} terdeteksi sebagai nomor pesanan/resi, bukan item. Gunakan Scan Pesanan.";

                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json(['status' => 'error', 'message' => $message], 422);
                }

                return redirect()->route('sales.shipments.edit', $shipment)
                    ->with('status', 'error')->with('message', $message)->withInput();
            }

            $message = "Item dengan kode/barcode {$scanCode} tidak ditemukan atau bukan finished_good.";

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['status' => 'error', 'message' => $message], 422);
            }

            return redirect()->route('sales.shipments.edit', $shipment)
                ->with('status', 'error')->with('message', $message)->withInput();
        }

        $autoOrderScan = $this->autoMapItemFirstLines($shipment);
        $autoOrderScanId = $autoOrderScan?->id;
        $fulfillmentId = $autoOrderScan?->fulfillment_id;
        $scanInfo = null;
        $recordOnlyOrder = false;

        if ($orderNo !== '') {
            $savedOrderScan = ShipmentOrderScan::query()
                ->where('shipment_id', $shipment->id)
                ->where('order_no', $orderNo)
                ->first(['id', 'raw_payload']);

            $savedPayload = is_array($savedOrderScan?->raw_payload) ? $savedOrderScan->raw_payload : [];
            $recordOnlyOrder = (($savedPayload['mode'] ?? null) === 'record_only');

            if ($shipment->shipment_type === 'marketplace' && !$recordOnlyOrder) {
                $marketplaceOrder = $this->marketplaceOrderQuery($orderNo, $shipment)
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

                // Cek di shipment lain yang aktif jika kebijakan duplikat aktif.
                $existingScan = $this->activeDuplicateScan($fulfillmentId, $shipment);
                if ($existingScan) {
                    $code = $existingScan->shipment?->code ?? '-';
                    $msg = "Pesanan sedang diproses di shipment lain: {$code}.";
                    if ($request->expectsJson() || $request->ajax()) return response()->json(['status' => 'error', 'message' => $msg], 422);
                    return redirect()->back()->with('status', 'error')->with('message', $msg);
                }
            } elseif ($shipment->shipment_type !== 'marketplace') {
                $orderNo = ''; 
            }
        }

        $result = DB::transaction(function () use ($shipment, $currentWave, $item, $qty, $orderNo, $fulfillmentId, $autoOrderScanId) {
            $warehouse = $this->whRts();
            if ($warehouse) {
                app(\App\Services\Inventory\InventoryService::class)->reserveStock(
                    warehouseId: $warehouse->id,
                    itemId: $item->id,
                    qty: $qty,
                    allowNegative: true // izinkan over-reserve saat edit; blokir di submit/post
                );
            }

            $orderScanId = $autoOrderScanId;
            if (!$orderScanId && $orderNo !== '') {
                $orderScan = ShipmentOrderScan::query()
                    ->where('shipment_id', $shipment->id)
                    ->where('order_no', $orderNo)
                    ->lockForUpdate()
                    ->first();

                if (!$orderScan) {
                    $orderScan = ShipmentOrderScan::create([
                        'shipment_id' => $shipment->id,
                        'shipment_wave_id' => $currentWave?->id,
                        'order_no' => $orderNo,
                        'fulfillment_id' => $fulfillmentId,
                        'status' => 'pending',
                        'source' => 'scan',
                    ]);
                } else {
                    $orderScan->update([
                        'fulfillment_id' => $fulfillmentId,
                        'status' => 'pending',
                    ]);
                }

                $orderScanId = $orderScan->id;
            }

            /** @var \App\Models\ShipmentLine|null $line */
            $line = \App\Models\ShipmentLine::query()
                ->where('shipment_id', $shipment->id)
                ->where('item_id', $item->id)
                ->when(
                    $orderScanId,
                    fn ($query) => $query->where('shipment_order_scan_id', $orderScanId),
                    fn ($query) => $query
                        ->whereNull('shipment_order_scan_id')
                        ->when($currentWave, fn ($waveQuery) => $waveQuery->where('shipment_wave_id', $currentWave->id))
                )
                ->lockForUpdate()
                ->first();

            if ($line) {
                $line->qty_scanned = (int) $line->qty_scanned + $qty;
                $line->allocated_qty = (int) $line->allocated_qty + $qty;
                $line->save();
            } else {
                $line = \App\Models\ShipmentLine::create([
                    'shipment_id' => $shipment->id,
                    'shipment_wave_id' => $currentWave?->id,
                    'shipment_order_scan_id' => $orderScanId,
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
        $linesQuery = $shipment->lines()->with('item:id,code,name');
        if ($this->usesDailyWaves($shipment)) {
            // Baris yang sudah diposting pada gelombang sebelumnya tidak lagi
            // menjadi kebutuhan/reservasi gelombang aktif.
            $linesQuery->where(function ($query) {
                $query->whereNull('shipment_wave_id')
                    ->orWhereHas('wave', fn ($waveQuery) => $waveQuery->where('status', ShipmentWave::STATUS_OPEN));
            });
        }
        $lines = $linesQuery->get();
        if ($lines->isEmpty()) {
            return [];
        }

        $itemIds = $lines->pluck('item_id')->unique()->toArray();
        $stocks  = InventoryStock::where('warehouse_id', $warehouse->id)
            ->whereIn('item_id', $itemIds)
            ->get(['item_id', 'qty', 'allocated_qty'])
            ->keyBy('item_id');

        $requirements = $lines
            ->groupBy('item_id')
            ->map(fn ($itemLines) => [
                'item_id' => (int) $itemLines->first()->item_id,
                'needed' => (float) $itemLines->sum(fn ($line) => (float) ($line->qty_scanned ?? 0)),
                'own_allocation' => (float) $itemLines->sum(fn ($line) => (float) ($line->allocated_qty ?? 0)),
                'item' => $itemLines->first()->item,
            ]);

        $errors = [];
        foreach ($requirements as $requirement) {
            $qty = $requirement['needed'];
            if ($qty <= 0) continue;

            $itemId = $requirement['item_id'];
            $stock = $stocks->get($itemId);
            
            $physQty    = (float) ($stock->qty ?? 0);
            $totalAlloc = (float) ($stock->allocated_qty ?? 0);
            $myAlloc    = $requirement['own_allocation'];

            // True available = Stok fisik - Total alokasi semua draft + Alokasi milik shipment ini sendiri
            $trueAvailable = $physQty - $totalAlloc + $myAlloc;

            if (($trueAvailable + 0.0000001) < $qty) {
                $errors[] = [
                    'code'    => $requirement['item']->code ?? "item#{$itemId}",
                    'name'    => $requirement['item']->name ?? '',
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

    protected function finishedGoodItemQuery(string $scanCode)
    {
        $scanCode = mb_strtoupper(trim($scanCode));

        return Item::query()
            ->where('type', 'finished_good')
            ->where(function ($query) use ($scanCode) {
                $query->where('barcode', $scanCode)
                    ->orWhere('code', $scanCode)
                    ->orWhereHas('barcodes', function ($barcodeQuery) use ($scanCode) {
                        $barcodeQuery->where('barcode', $scanCode)
                            ->where('is_active', true);
                    });
            });
    }

    protected function marketplaceOrderQuery(string $scanCode, ?Shipment $shipment = null)
    {
        $scanCode = mb_strtoupper(trim($scanCode));
        $lookupIdentifiers = $this->salesLookupIdentifiers();

        $query = \App\Models\MarketplaceOrder::query();

        if (!in_array('marketplace_order', $this->salesLookupSources(), true)) {
            return $query->whereRaw('1 = 0');
        }

        if ($shipment && $this->salesOperationalSetting('same_store', true) && !empty($shipment->store_id)) {
            $query->where('store_id', $shipment->store_id);
        }

        return $query->where(function ($query) use ($scanCode, $lookupIdentifiers) {
            $first = true;
            foreach ($lookupIdentifiers as $identifier) {
                $column = match ($identifier) {
                    'shipping_awb_no' => 'shipping_awb_no',
                    'channel_order_id' => 'channel_order_id',
                    'booking_sn' => 'booking_sn',
                    'external_order_id' => 'external_order_id',
                    default => null,
                };

                if (!$column) {
                    continue;
                }

                $first ? $query->where($column, $scanCode) : $query->orWhere($column, $scanCode);
                $first = false;
            }

            // Shopee menyimpan booking dan nomor resi operasional di tabel
            // marketplace_bookings. Jangan hanya mengandalkan kolom snapshot
            // di marketplace_orders karena data order lama bisa belum terisi.
            if (in_array('booking_sn', $lookupIdentifiers, true)) {
                $query->orWhereExists(function ($bookingQuery) use ($scanCode) {
                    $bookingQuery->selectRaw('1')
                        ->from('marketplace_bookings')
                        ->whereColumn('marketplace_bookings.store_id', 'marketplace_orders.store_id')
                        ->whereRaw('UPPER(TRIM(marketplace_bookings.booking_sn)) = ?', [$scanCode])
                        ->where(function ($orderLinkQuery) {
                            $orderLinkQuery
                                ->whereColumn('marketplace_bookings.order_sn', 'marketplace_orders.channel_order_id')
                                ->orWhereColumn('marketplace_bookings.order_sn', 'marketplace_orders.external_order_id')
                                ->orWhereColumn('marketplace_bookings.booking_sn', 'marketplace_orders.booking_sn');
                        });
                });
                $first = false;
            }

            if (in_array('shipping_awb_no', $lookupIdentifiers, true)) {
                $query->orWhereExists(function ($bookingQuery) use ($scanCode) {
                    $bookingQuery->selectRaw('1')
                        ->from('marketplace_bookings')
                        ->whereColumn('marketplace_bookings.store_id', 'marketplace_orders.store_id')
                        ->whereRaw('UPPER(TRIM(marketplace_bookings.tracking_number)) = ?', [$scanCode])
                        ->where(function ($orderLinkQuery) {
                            $orderLinkQuery
                                ->whereColumn('marketplace_bookings.order_sn', 'marketplace_orders.channel_order_id')
                                ->orWhereColumn('marketplace_bookings.order_sn', 'marketplace_orders.external_order_id')
                                ->orWhereColumn('marketplace_bookings.booking_sn', 'marketplace_orders.booking_sn');
                        });
                });
                $first = false;
            }

            if ($first) {
                $query->whereRaw('1 = 0');
            }
        });
    }

    /**
     * Cari order aktif dari nomor order, booking SN, atau AWB.
     *
     * Booking Shopee bisa sudah memiliki AWB tetapi belum memiliki order_sn
     * lokal. Dalam kondisi itu, ambil detail booking untuk melengkapi order
     * marketplace sebelum proses rekonsiliasi dilanjutkan.
     */
    protected function findReconciliationMarketplaceOrder(
        string $scanCode,
        ?Shipment $shipment = null,
        bool $promoteBooking = false
    ): ?\App\Models\MarketplaceOrder {
        $findOrder = fn () => $this->marketplaceOrderQuery($scanCode, $shipment)
            ->whereIn('order_status', self::RECONCILIATION_MARKETPLACE_STATUSES)
            ->with(['items.internalItem', 'store'])
            ->first();

        $marketplaceOrder = $findOrder();
        if ($marketplaceOrder || !$promoteBooking) {
            return $marketplaceOrder;
        }

        $booking = \App\Models\MarketplaceBooking::query()
            ->where(function ($query) use ($scanCode) {
                $query
                    ->whereRaw('UPPER(TRIM(booking_sn)) = ?', [$scanCode])
                    ->orWhereRaw('UPPER(TRIM(order_sn)) = ?', [$scanCode])
                    ->orWhereRaw('UPPER(TRIM(tracking_number)) = ?', [$scanCode]);
            })
            ->when(
                $shipment && $this->salesOperationalSetting('same_store', true) && !empty($shipment->store_id),
                fn ($query) => $query->where('store_id', $shipment->store_id)
            )
            ->with('store')
            ->first();

        if (!$booking || !$booking->store) {
            return null;
        }

        try {
            app(\App\Services\MarketplaceSyncService::class)
                ->promoteBookingToOrder($booking->store, $booking->booking_sn);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning(
                "Shipment reconciliation booking lookup failed [{$booking->booking_sn}]: " . $e->getMessage()
            );
        }

        return $findOrder();
    }

    protected function salesInvoiceQuery(string $scanCode, ?Shipment $shipment = null)
    {
        $scanCode = mb_strtoupper(trim($scanCode));
        $query = SalesInvoice::query();

        if (!in_array('sales_invoice', $this->salesLookupSources(), true)) {
            return $query->whereRaw('1 = 0');
        }

        if ($shipment && $this->salesOperationalSetting('same_store', true) && !empty($shipment->store_id)) {
            $query->where('store_id', $shipment->store_id);
        }

        return $query->where(function ($query) use ($scanCode) {
            $first = true;
            foreach ($this->salesLookupIdentifiers() as $identifier) {
                $column = match ($identifier) {
                    'invoice_code' => 'code',
                    'channel_order_id' => 'channel_order_no',
                    'channel_invoice_no' => 'channel_invoice_no',
                    default => null,
                };

                if (!$column) {
                    continue;
                }

                $first ? $query->where($column, $scanCode) : $query->orWhere($column, $scanCode);
                $first = false;
            }

            if ($first) {
                $query->whereRaw('1 = 0');
            }
        });
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

        if (($locked->scan_mode ?? 'item_first') === 'item_first') {
            // Satu order masih bisa dimapping otomatis untuk kompatibilitas
            // flow lama. Jika lebih dari satu order, mapping harus berasal
            // dari rekonsiliasi yang tersimpan di server.
            $this->autoMapItemFirstLines($locked);
            $this->syncItemFirstLineAllocations($locked->fresh());
            $locked->refresh()->load(['lines.item', 'store']);
        }

        // ===================================================
        // VALIDASI MISMATCH QTY FULFILLMENT VS SHIPMENT
        // ===================================================
        if ($locked->shipment_type === \App\Models\Shipment::TYPE_MARKETPLACE) {
            $mappingErrors = $this->shipmentMappingErrors($locked);
            if (!empty($mappingErrors)) {
                abort(422, implode(' ', $mappingErrors));
            }

            $scans = \App\Models\ShipmentOrderScan::where('shipment_id', $locked->id)
                ->whereNotIn('status', ['skip'])
                ->get();

            $hasRecordOnlyScan = $scans->contains(function ($scan) {
                $payload = is_array($scan->raw_payload) ? $scan->raw_payload : [];

                return ($payload['mode'] ?? null) === 'record_only';
            });
            $hasLinkedScan = $scans->contains(fn ($scan) => !empty($scan->fulfillment_id));
            $recordOnlyBatch = $hasRecordOnlyScan && !$hasLinkedScan;

            if ($recordOnlyBatch && !$this->salesOperationalSetting('allow_unlinked_submit', true)) {
                abort(422, 'Shipment record-only belum diizinkan untuk disubmit. Aktifkan Boleh submit record-only di Pengaturan Operasional Penjualan.');
            }

            if ($hasRecordOnlyScan && $hasLinkedScan && !$this->salesOperationalSetting('allow_mixed_linkage', false)) {
                abort(422, 'Shipment tidak boleh mencampur order tertaut dan record-only. Pisahkan menjadi shipment terpisah.');
            }

            // Order record-only boleh diproses sebagai shipment batch sementara.
            // Jika ada order yang sudah tertaut, validasi hanya order tertautnya.
            if ($hasLinkedScan) {
                $scans = $scans->filter(function ($scan) {
                    $payload = is_array($scan->raw_payload) ? $scan->raw_payload : [];

                    return ($payload['mode'] ?? null) !== 'record_only';
                })->values();
            } elseif ($recordOnlyBatch) {
                $scans = collect();
            }
            
            $fulfillmentIds = [];
            foreach ($scans as $scan) {
                if (empty($scan->fulfillment_id)) {
                    abort(422, "Shipment Marketplace tidak boleh memiliki order scan tanpa fulfillment_id.");
                }
                $fulfillmentIds[] = $scan->fulfillment_id;
            }
            $fulfillmentIds = array_unique($fulfillmentIds);

            if (empty($fulfillmentIds) && !$recordOnlyBatch) {
                abort(422, "Shipment Marketplace wajib memiliki minimal 1 order scan valid.");
            }

            if (!$recordOnlyBatch) {
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

        // Record-only adalah pencatatan operasional; jangan masukkan ke Daily Sales
        // kecuali owner memang mengaktifkan kebijakan tersebut.
        $isRecordOnlyShipment = $locked->shipment_type === \App\Models\Shipment::TYPE_MARKETPLACE
            && !\App\Models\ShipmentOrderScan::where('shipment_id', $locked->id)
                ->whereNotNull('fulfillment_id')
                ->whereNotIn('status', ['skip'])
                ->exists();
        if (!$isRecordOnlyShipment || $this->salesOperationalSetting('record_only_daily_sales', false)) {
            $this->dailySales->applyShipmentPosted($locked, adsDays: 30, onlyActive: true);
        }

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
                
                if ($this->shouldUpdateMarketplaceStatus('post') && !empty($marketplaceOrderIds)) {
                    \App\Models\MarketplaceOrder::whereIn('id', $marketplaceOrderIds)
                        ->get()
                        ->each(fn ($order) => $this->updateMarketplaceStatusIfAllowed($order, 'post'));
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

        if ($this->usesDailyWaves($shipment)) {
            return redirect()->route('sales.shipments.confirm_orders', $shipment)
                ->with('status', 'error')
                ->with('message', 'Shipment harian harus diselesaikan per gelombang. Gunakan tombol Selesaikan Gelombang.');
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

        return redirect()->route('sales.shipments.index')
            ->with('status', 'success')
            ->with('message', 'Shipment berhasil disubmit & stok WH-RTS langsung berkurang.');
    }

    /**
     * Post satu gelombang pada shipment harian. Shipment induk tetap draft
     * sehingga operator masih bisa menambahkan gelombang berikutnya.
     */
    public function postWave(Request $request, Shipment $shipment)
    {
        if (!$this->usesDailyWaves($shipment)) {
            return redirect()->route('sales.shipments.confirm_orders', $shipment)
                ->with('status', 'error')
                ->with('message', 'Shipment ini memakai mode sekali posting.');
        }

        if ($shipment->status !== 'draft' || $shipment->cancelled_at) {
            return redirect()->route('sales.shipments.show', $shipment)
                ->with('status', 'error')
                ->with('message', 'Shipment harian sudah tidak terbuka.');
        }

        $warehouse = $this->whRts();
        if (!$warehouse) {
            return redirect()->route('sales.shipments.edit', $shipment)
                ->with('status', 'error')
                ->with('message', 'Warehouse WH-RTS belum dikonfigurasi.');
        }

        try {
            $wave = DB::transaction(function () use ($shipment, $warehouse) {
                $locked = Shipment::query()->lockForUpdate()->findOrFail($shipment->id);
                $wave = $this->ensureOpenShipmentWave($locked);

                if (!$wave->isOpen()) {
                    throw new \RuntimeException('Tidak ada gelombang yang masih terbuka.');
                }

                if (($locked->scan_mode ?? 'item_first') === 'item_first') {
                    $this->syncItemFirstLineAllocations($locked, $wave);
                }

                $wave->refresh()->load(['lines.item', 'orderScans']);
                $mappingErrors = $this->shipmentMappingErrors($locked, $wave);
                if (!empty($mappingErrors)) {
                    throw new \RuntimeException(implode(' ', $mappingErrors));
                }

                if ($wave->lines->isEmpty()) {
                    throw new \RuntimeException('Gelombang ini belum memiliki item.');
                }

                $stockErrors = $this->waveStockErrors($wave, $warehouse);
                if (!empty($stockErrors)) {
                    $summary = collect($stockErrors)
                        ->map(fn ($row) => ($row['code'] ?? '-') . ' kurang ' . ($row['short'] ?? 0))
                        ->implode(', ');
                    throw new \RuntimeException('Stok WH-RTS belum cukup: ' . $summary . '.');
                }

                $totalQty = 0;
                foreach ($wave->lines as $line) {
                    $qty = (int) ($line->qty_scanned ?? 0);
                    if ($qty <= 0) {
                        continue;
                    }

                    $totalQty += $qty;

                    if ((int) ($line->allocated_qty ?? 0) > 0) {
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
                        notes: 'Gelombang ' . $wave->code . ' — Shipment ' . $locked->code,
                        allowNegative: false,
                        lotId: null,
                        unitCostOverride: null,
                        affectLotCost: false,
                        sourceLineId: (int) $line->id,
                    );
                }

                $wave->update([
                    'status' => ShipmentWave::STATUS_POSTED,
                    'total_qty' => $totalQty,
                    'posted_at' => now(),
                    'posted_by' => auth()->id(),
                ]);

                $this->journalService->postShipmentWaveCogsFromMutations($wave->fresh('shipment'));

                $scans = $wave->orderScans
                    ->whereNotIn('status', ['skip'])
                    ->whereNotNull('fulfillment_id');
                $fulfillmentIds = $scans->pluck('fulfillment_id')->unique()->values()->all();

                if (!empty($fulfillmentIds)) {
                    \App\Models\OrderFulfillment::whereIn('id', $fulfillmentIds)
                        ->whereNotIn('status', [
                            \App\Models\OrderFulfillment::STATUS_CONFIRMED,
                            \App\Models\OrderFulfillment::STATUS_CANCELLED,
                        ])
                        ->update([
                            'status' => \App\Models\OrderFulfillment::STATUS_CONFIRMED,
                            'confirmed_at' => now(),
                            'confirmed_by' => auth()->id(),
                        ]);

                    if ($this->shouldUpdateMarketplaceStatus('post')) {
                        \App\Models\OrderFulfillment::whereIn('id', $fulfillmentIds)
                            ->with('marketplaceOrder')
                            ->get()
                            ->each(fn ($fulfillment) => $this->updateMarketplaceStatusIfAllowed($fulfillment->marketplaceOrder, 'post'));
                    }
                }

                return $wave->fresh();
            });
        } catch (\Throwable $e) {
            return redirect()->route('sales.shipments.confirm_orders', $shipment)
                ->with('status', 'error')
                ->with('message', 'Gelombang belum bisa diposting: ' . $e->getMessage());
        }

        return redirect()->route('sales.shipments.edit', $shipment)
            ->with('status', 'success')
            ->with('message', 'Gelombang ' . $wave->code . ' berhasil diposting. Shipment harian masih terbuka untuk gelombang berikutnya.');
    }

    /** Tutup shipment induk setelah seluruh gelombang selesai diposting. */
    public function closeDailyShipment(Request $request, Shipment $shipment)
    {
        if (!$this->usesDailyWaves($shipment)) {
            return redirect()->route('sales.shipments.show', $shipment)
                ->with('status', 'error')->with('message', 'Shipment ini bukan shipment harian.');
        }

        try {
            DB::transaction(function () use ($shipment) {
                $locked = Shipment::query()->lockForUpdate()->findOrFail($shipment->id);
                if ($locked->status !== 'draft' || $locked->cancelled_at) {
                    throw new \RuntimeException('Shipment sudah tidak terbuka.');
                }

                $locked->load(['waves.lines', 'waves.orderScans']);
                $waves = $locked->waves->where('status', '!=', ShipmentWave::STATUS_CANCELLED);
                if ($waves->where('status', ShipmentWave::STATUS_POSTED)->isEmpty()) {
                    throw new \RuntimeException('Belum ada gelombang yang diposting.');
                }

                foreach ($waves->where('status', ShipmentWave::STATUS_OPEN) as $wave) {
                    if ($wave->lines->isNotEmpty()) {
                        $errors = $this->shipmentMappingErrors($locked, $wave);
                        throw new \RuntimeException(!empty($errors)
                            ? implode(' ', $errors)
                            : 'Masih ada item pada gelombang yang belum diposting.');
                    }

                    $wave->update([
                        'status' => ShipmentWave::STATUS_POSTED,
                        'posted_at' => now(),
                        'posted_by' => auth()->id(),
                    ]);
                }

                $locked->update([
                    'status' => 'posted',
                    'posted_at' => now(),
                    'posted_by' => auth()->id(),
                    'submitted_at' => $locked->submitted_at ?: now(),
                    'submitted_by' => $locked->submitted_by ?: auth()->id(),
                    'total_qty' => (int) $locked->lines()->sum('qty_scanned'),
                ]);

                $this->dailySales->applyShipmentPosted($locked, adsDays: 30, onlyActive: true);
            });
        } catch (\Throwable $e) {
            return redirect()->route('sales.shipments.edit', $shipment)
                ->with('status', 'error')->with('message', 'Shipment belum bisa ditutup: ' . $e->getMessage());
        }

        return redirect()->route('sales.shipments.show', $shipment)
            ->with('status', 'success')->with('message', 'Shipment harian berhasil ditutup. Semua gelombang sudah selesai.');
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

        return redirect()->route('sales.shipments.index')
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

        $isDaily = $this->usesDailyWaves($shipment);

        DB::transaction(function () use ($shipment, $isDaily) {
            $warehouse = $this->whRts();
            $wave = $isDaily ? $this->ensureOpenShipmentWave($shipment) : null;
            $linesQuery = ShipmentLine::where('shipment_id', $shipment->id);
            if ($wave) {
                $linesQuery->where('shipment_wave_id', $wave->id);
            }
            $lines = $linesQuery->get();
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
                'message' => $isDaily
                    ? 'Baris pada gelombang aktif berhasil dibersihkan.'
                    : 'Semua baris berhasil dibersihkan.',
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

        if ($line->wave?->isPosted()) {
            $message = 'Baris pada gelombang yang sudah diposting tidak bisa diubah.';

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['status' => 'error', 'message' => $message], 409);
            }

            return redirect()->route('sales.shipments.edit', $shipment)
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

        if ($line->wave?->isPosted()) {
            $message = 'Baris pada gelombang yang sudah diposting tidak bisa diubah.';

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['status' => 'error', 'message' => $message], 409);
            }

            return redirect()->route('sales.shipments.edit', $shipment)
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
            $restoredOrderCount = DB::transaction(function () use ($shipment, $warehouse, $validated) {
                $locked = Shipment::whereKey($shipment->id)->lockForUpdate()->firstOrFail();

                if (!empty($locked->cancelled_at)) {
                    return 0;
                }

                if (!empty($locked->sales_invoice_id)) {
                    throw new \RuntimeException('Tidak bisa dibatalkan karena sudah dibuat invoice.');
                }

                $locked->load(['lines.wave', 'store', 'waves']);
                $parentWasPosted = $locked->status === 'posted' && !empty($locked->posted_at);
                $postedWaveIds = $locked->waves
                    ->where('status', ShipmentWave::STATUS_POSTED)
                    ->pluck('id')
                    ->map(fn ($id) => (int) $id)
                    ->all();
                $hasPostedWaves = !empty($postedWaveIds);

                foreach ($locked->lines as $line) {
                    $qty = (int) ($line->qty_scanned ?? 0);
                    if ($qty <= 0) {
                        continue;
                    }

                    $lineWasPosted = $parentWasPosted
                        || in_array((int) ($line->shipment_wave_id ?? 0), $postedWaveIds, true)
                        || $line->wave?->isPosted();

                    if ($lineWasPosted) {
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
                    } elseif ($line->allocated_qty > 0) {
                        $this->inventory->releaseStock(
                            $warehouse->id,
                            $line->item_id,
                            $line->allocated_qty
                        );

                        $line->allocated_qty = 0;
                        $line->save();
                    }
                }

                $locked->cancelled_at = now();
                $locked->cancelled_by = auth()->id();
                $locked->cancel_reason = $validated['cancel_reason'];
                $locked->save();

                $restoredOrderCount = $this->restoreMarketplaceOrdersAfterCancellation($locked);

                if ($parentWasPosted) {
                    // Void jurnal COGS jika ada
                    $this->journalService->voidBySource('shipment_cogs', (int) $locked->id);

                    $this->dailySales->reverseShipmentCancelled($locked, adsDays: 30, onlyActive: true);
                }

                if ($hasPostedWaves) {
                    foreach ($postedWaveIds as $waveId) {
                        $this->journalService->voidBySource(JournalService::SRC_SHIPMENT_WAVE_COGS, $waveId);
                    }
                }

                return $restoredOrderCount;
            });
        } catch (\Throwable $e) {
            return redirect()->route('sales.shipments.show', $shipment)
                ->with('status', 'error')->with('message', 'Gagal membatalkan shipment: ' . $e->getMessage());
        }

        return redirect()->route('sales.shipments.show', $shipment)
            ->with('status', 'success')
            ->with('message', 'Shipment berhasil dibatalkan dan stok sudah disesuaikan.'
                . ($restoredOrderCount > 0
                    ? " {$restoredOrderCount} order dikembalikan ke status PROCESSED (Belum Packing)."
                    : ''));
    }

    public function cancelForm(Shipment $shipment)
    {
        if (!empty($shipment->cancelled_at)) {
            return redirect()->route('sales.shipments.show', $shipment);
        }

        $shipment->load(['store', 'warehouse', 'lines.item', 'orderScans']);

        return view('sales.shipments.cancel', compact('shipment'));
    }

    protected function postShipmentCogs(Shipment $shipment): void
    {
        $this->journalService->postShipmentCogsFromMutations($shipment);
    }

    /**
     * Simpan hasil alokasi item-first ke shipment_lines.
     *
     * Browser hanya dipakai untuk membantu menghitung alokasi. Server tetap
     * menjadi sumber kebenaran dengan membangun ulang line mapped dan line
     * yang masih tersisa di batch. Total qty per item tidak boleh berubah.
     */
    protected function syncItemFirstLineAllocations(Shipment $shipment, ?ShipmentWave $wave = null): void
    {
        if (($shipment->scan_mode ?? 'item_first') !== 'item_first') {
            return;
        }

        $wave ??= $this->currentShipmentWave($shipment);
        $waveId = $wave?->id;
        $shipment->load(['lines', 'orderScans']);

        $lines = $waveId
            ? $shipment->lines->where('shipment_wave_id', $waveId)->values()
            : $shipment->lines;
        $orderScans = $waveId
            ? $shipment->orderScans->where('shipment_wave_id', $waveId)->values()
            : $shipment->orderScans;

        $baseQty = $lines
            ->groupBy('item_id')
            ->map(fn ($lines) => (int) $lines->sum('qty_scanned'))
            ->all();

        if (empty($baseQty)) {
            return;
        }

        $existingByScan = $lines
            ->filter(fn ($line) => !empty($line->shipment_order_scan_id))
            ->groupBy('shipment_order_scan_id')
            ->map(fn ($lines) => $lines
                ->groupBy('item_id')
                ->map(fn ($itemLines) => (int) $itemLines->sum('qty_scanned'))
                ->all())
            ->all();

        $allocationsByScan = [];
        $allocatedTotals = [];

        foreach ($orderScans->where('status', '!=', 'skip') as $scan) {
            $payload = is_array($scan->raw_payload) ? $scan->raw_payload : [];
            $hasPayloadAllocation = array_key_exists('allocations', $payload);
            $allocations = [];

            if ($hasPayloadAllocation && is_array($payload['allocations'])) {
                foreach ($payload['allocations'] as $allocation) {
                    $itemId = (int) data_get($allocation, 'item_id');
                    $qty = (int) data_get($allocation, 'qty');

                    if ($itemId <= 0 || $qty <= 0) {
                        continue;
                    }

                    $allocations[$itemId] = ($allocations[$itemId] ?? 0) + $qty;
                }
            } elseif (isset($existingByScan[$scan->id])) {
                // Backward-compatible untuk mapping lama yang belum menyimpan
                // payload allocations.
                $allocations = $existingByScan[$scan->id];
            }

            foreach ($allocations as $itemId => $qty) {
                if (!array_key_exists($itemId, $baseQty)) {
                    abort(422, "Item {$itemId} tidak ada di batch shipment.");
                }

                $allocatedTotals[$itemId] = ($allocatedTotals[$itemId] ?? 0) + $qty;
                if ($allocatedTotals[$itemId] > $baseQty[$itemId]) {
                    $itemCode = Item::find($itemId)?->code ?? $itemId;
                    abort(422, "Alokasi SKU {$itemCode} melebihi qty item di batch.");
                }
            }

            $allocationsByScan[$scan->id] = $allocations;
        }

        // Semua line draft dibangun ulang tanpa mengubah total reservasi.
        // allocated_qty selalu sama dengan qty_scanned agar reserve stok tetap
        // konsisten walaupun line berpindah dari ungrouped ke order tertentu.
        ShipmentLine::where('shipment_id', $shipment->id)
            ->when($waveId, fn ($query) => $query->where('shipment_wave_id', $waveId))
            ->when(!$waveId, fn ($query) => $query->whereNull('shipment_wave_id'))
            ->delete();

        foreach ($allocationsByScan as $scanId => $allocations) {
            foreach ($allocations as $itemId => $qty) {
                ShipmentLine::create([
                    'shipment_id' => $shipment->id,
                    'shipment_wave_id' => $waveId,
                    'shipment_order_scan_id' => $scanId,
                    'item_id' => $itemId,
                    'qty_expected' => 0,
                    'qty_scanned' => $qty,
                    'allocated_qty' => $qty,
                    'uom' => Item::find($itemId)?->uom ?? 'pcs',
                ]);
            }
        }

        foreach ($baseQty as $itemId => $qty) {
            $remaining = $qty - (int) ($allocatedTotals[$itemId] ?? 0);
            if ($remaining <= 0) {
                continue;
            }

            ShipmentLine::create([
                'shipment_id' => $shipment->id,
                'shipment_wave_id' => $waveId,
                'shipment_order_scan_id' => null,
                'item_id' => $itemId,
                'qty_expected' => 0,
                'qty_scanned' => $remaining,
                'allocated_qty' => $remaining,
                'uom' => Item::find($itemId)?->uom ?? 'pcs',
            ]);
        }
    }

    /**
     * Validasi server sebelum stock-out. Item-first tidak boleh lolos hanya
     * karena UI/browser menganggap mapping sudah selesai.
     */
    protected function shipmentMappingErrors(Shipment $shipment, ?ShipmentWave $wave = null): array
    {
        if ($shipment->shipment_type !== Shipment::TYPE_MARKETPLACE) {
            return [];
        }

        if ($wave) {
            $wave->load(['lines.item', 'orderScans']);
            $lines = $wave->lines;
            $activeScans = $wave->orderScans->where('status', '!=', 'skip')->values();
        } else {
            $shipment->load(['lines.item', 'orderScans']);
            $lines = $shipment->lines;
            $activeScans = $shipment->orderScans->where('status', '!=', 'skip')->values();
        }
        $errors = [];

        if ($activeScans->isEmpty()) {
            $errors[] = 'Shipment marketplace wajib memiliki minimal satu nomor order.';
            return $errors;
        }

        $unmapped = $lines->filter(fn ($line) => empty($line->shipment_order_scan_id));
        if ($unmapped->isNotEmpty()) {
            $summary = $unmapped
                ->groupBy('item_id')
                ->map(function ($lines) {
                    $line = $lines->first();
                    return ($line->item?->code ?? 'ITEM-' . $line->item_id)
                        . ' x' . (int) $lines->sum('qty_scanned');
                })
                ->values()
                ->implode(', ');
            $errors[] = 'Masih ada item yang belum terhubung ke order: ' . $summary . '.';
        }

        $linesByScan = $lines
            ->filter(fn ($line) => !empty($line->shipment_order_scan_id))
            ->groupBy('shipment_order_scan_id');

        foreach ($activeScans as $scan) {
            $scanLines = $linesByScan->get($scan->id, collect());
            if ($scanLines->isEmpty()) {
                $errors[] = "Order {$scan->order_no} belum memiliki item yang dialokasikan.";
                continue;
            }

            if (!$scan->fulfillment_id) {
                // Order external/manual boleh dikirim saat marketplace belum
                // tersinkron, tetapi tetap wajib punya order number + mapping.
                continue;
            }

            $expected = \App\Models\OrderFulfillmentLine::query()
                ->where('fulfillment_id', $scan->fulfillment_id)
                ->whereNotNull('item_id')
                ->where('qty_fulfilled', '>', 0)
                ->selectRaw('item_id, SUM(qty_fulfilled) as total_expected')
                ->groupBy('item_id')
                ->pluck('total_expected', 'item_id')
                ->map(fn ($qty) => (int) $qty)
                ->all();

            $actual = $scanLines
                ->groupBy('item_id')
                ->map(fn ($lines) => (int) $lines->sum('qty_scanned'))
                ->all();

            foreach (array_unique(array_merge(array_keys($expected), array_keys($actual))) as $itemId) {
                $expectedQty = (int) ($expected[$itemId] ?? 0);
                $actualQty = (int) ($actual[$itemId] ?? 0);
                if ($expectedQty === $actualQty) {
                    continue;
                }

                $itemCode = Item::find($itemId)?->code ?? $itemId;
                $errors[] = "Order {$scan->order_no}: SKU {$itemCode} expected {$expectedQty}, scan {$actualQty}.";
            }
        }

        return array_values(array_unique($errors));
    }

    /**
     * Untuk mode scan barang dulu, tautkan item ungrouped hanya ketika
     * target order-nya memang tidak ambigu: invoice shipment atau satu
     * order scan yang sudah tersimpan.
     */
    protected function autoMapItemFirstLines(Shipment $shipment): ?ShipmentOrderScan
    {
        if (($shipment->scan_mode ?? 'item_first') !== 'item_first') {
            return null;
        }

        $shipment->loadMissing(['orderScans', 'invoice']);
        $orderScan = null;

        if ($shipment->sales_invoice_id && $shipment->invoice) {
            $invoice = $shipment->invoice;
            $orderNo = trim((string) ($invoice->channel_order_no ?: $invoice->code ?: ('INV-' . $invoice->id)));

            if ($orderNo !== '') {
                $payload = [
                    'mode' => 'record_only',
                    'label' => 'Tertaut otomatis dari invoice',
                    'linked_source' => 'sales_invoice',
                    'sales_invoice_id' => $invoice->id,
                    'order' => [
                        'order_no' => $orderNo,
                        'invoice_id' => $invoice->id,
                        'invoice_code' => $invoice->code,
                        'source' => 'sales_invoice',
                        'status' => 'pending',
                    ],
                ];

                $orderScan = ShipmentOrderScan::firstOrNew([
                    'shipment_id' => $shipment->id,
                    'order_no' => $orderNo,
                ]);
                $orderScan->fill([
                    'fulfillment_id' => $orderScan->fulfillment_id,
                    'status' => $orderScan->status ?: 'pending',
                    'source' => 'sales_invoice',
                    'raw_payload' => array_merge((array) $orderScan->raw_payload, $payload),
                ]);
                $orderScan->save();
            }
        } elseif ($shipment->orderScans->count() === 1) {
            $orderScan = $shipment->orderScans->first();
        }

        if (!$orderScan) {
            return null;
        }

        ShipmentLine::query()
            ->where('shipment_id', $shipment->id)
            ->whereNull('shipment_order_scan_id')
            ->update(['shipment_order_scan_id' => $orderScan->id]);

        return $orderScan;
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

        $currentWave = $this->currentShipmentWave($shipment);
        $this->autoMapItemFirstLines($shipment);
        $shipment->load(['store', 'warehouse', 'lines.item', 'orderScans.lines.item', 'waves']);
        if ($currentWave) {
            $shipment->setRelation(
                'lines',
                $shipment->lines->where('shipment_wave_id', $currentWave->id)->values()
            );
            $shipment->setRelation(
                'orderScans',
                $shipment->orderScans->where('shipment_wave_id', $currentWave->id)->values()
            );
        }

        $warehouse = $this->whRts();
        $stockInsufficient = $warehouse
            ? $this->checkStockSufficiency($shipment, $warehouse)
            : [];

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
                    $payload = is_array($scan->raw_payload) ? $scan->raw_payload : [];
                    $canonicalNo = $this->normalizeOrderNumber(
                        $scan->order_no
                            ?: data_get($payload, 'no')
                            ?: data_get($payload, 'order.order_no')
                            ?: data_get($payload, 'order.code')
                    ) ?: ('SCAN-' . $scan->id);
                    $payload['no'] = $canonicalNo;
                    $payload['order'] = array_merge(
                        is_array($payload['order'] ?? null) ? $payload['order'] : [],
                        ['order_no' => $canonicalNo]
                    );
                    $payload['decision'] = $scan->status ?: 'pending';
                    $payload['scanned_at'] = $scan->created_at?->format('d/m/Y H:i:s');
                    return $payload;
                }
                $canonicalNo = $this->normalizeOrderNumber($scan->order_no) ?: ('SCAN-' . $scan->id);
                return [
                    'no' => $canonicalNo,
                    'found' => true,
                    'order' => [
                        'order_no' => $canonicalNo,
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

        $isDaily = $this->usesDailyWaves($shipment);

        return view('sales.shipments.rekon', compact(
            'shipment',
            'currentWave',
            'isDaily',
            'batchPool',
            'savedOrderScans'
        ));
    }

    public function confirmOrders(Shipment $shipment)
    {
        if ($shipment->status !== 'draft') {
            return redirect()->route('sales.shipments.show', $shipment)
                ->with('status', 'error')
                ->with('message', 'Hanya shipment draft yang bisa dikonfirmasi.');
        }

        $currentWave = $this->currentShipmentWave($shipment);
        $this->autoMapItemFirstLines($shipment);
        $shipment->load([
            'lines.item',
            'orderScans.lines.item',
            'orderScans.fulfillment.marketplaceOrder.items.internalItem',
            'orderScans.fulfillment.marketplaceOrder.store',
            'waves',
        ]);

        // Shipment lama yang dibuat dalam mode record-only dapat menyimpan
        // nomor order tanpa fulfillment_id. Resolve ulang di halaman confirm
        // agar detail item marketplace tetap terbaca, tanpa mengubah shipment
        // atau mengunci kemampuan edit operator.
        foreach ($shipment->orderScans as $scan) {
            if ($scan->status === 'skip' || $scan->source === 'sales_invoice') {
                continue;
            }

            if ($scan->fulfillment?->marketplaceOrder) {
                continue;
            }

            $orderNo = $this->normalizeOrderNumber($scan->order_no);
            if ($orderNo === '') {
                continue;
            }

            $marketplaceOrder = $this->findReconciliationMarketplaceOrder($orderNo, $shipment, true);

            if (!$marketplaceOrder) {
                continue;
            }

            $fulfillment = $scan->fulfillment;
            if (!$fulfillment) {
                $fulfillment = new \App\Models\OrderFulfillment([
                    'marketplace_order_id' => $marketplaceOrder->id,
                ]);
            }

            $fulfillment->setRelation('marketplaceOrder', $marketplaceOrder);
            $scan->setRelation('fulfillment', $fulfillment);
        }

        $warehouse = $this->whRts();
        $stockInsufficient = $warehouse
            ? $this->checkStockSufficiency($shipment, $warehouse)
            : [];
        $mappingErrors = $this->shipmentMappingErrors($shipment, $currentWave);
        $isDaily = $this->usesDailyWaves($shipment);

        return view('sales.shipments.confirm_orders', compact(
            'shipment',
            'currentWave',
            'isDaily',
            'stockInsufficient',
            'mappingErrors'
        ));
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

        $currentWave = $this->currentShipmentWave($shipment);
        $shipment->load('lines.item');
        if ($currentWave) {
            $shipment->setRelation(
                'lines',
                $shipment->lines->where('shipment_wave_id', $currentWave->id)->values()
            );
        }

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

        $no  = $this->normalizeOrderNumber($request->order_no);
        if ($no === '') {
            return response()->json([
                'status' => 'error',
                'message' => 'Nomor order tidak valid atau belum terbaca.',
            ], 422);
        }

        // Endpoint ini hanya dipanggil dari halaman rekonsiliasi, sehingga
        // lookup marketplace tetap dijalankan walaupun scan operasional utama
        // dikonfigurasi sebagai record-only.
        $marketplaceOrder = $this->findReconciliationMarketplaceOrder($no, $shipment, true);

        if (!$marketplaceOrder) {
            $salesInvoice = $this->salesInvoiceQuery($no, $shipment)
                ->with(['lines.item', 'store'])
                ->first();

            if ($salesInvoice) {
                $lines = [];
                foreach ($salesInvoice->lines as $invoiceLine) {
                    $item = $invoiceLine->item;
                    $itemId = $item?->id;
                    $qtyNeed = (int) $invoiceLine->qty;
                    $qtyAlloc = $itemId && isset($pool[$itemId]) ? min($qtyNeed, max(0, (int) $pool[$itemId])) : 0;
                    if ($itemId) {
                        $pool[$itemId] = max(0, (int) ($pool[$itemId] ?? 0) - $qtyAlloc);
                    }

                    $lines[] = [
                        'item_id' => $itemId,
                        'item_code' => $item?->code ?? '-',
                        'item_name' => $item?->name ?? '-',
                        'qty_need' => $qtyNeed,
                        'qty_alloc' => $qtyAlloc,
                        'qty_short' => max(0, $qtyNeed - $qtyAlloc),
                        'status' => $qtyAlloc >= $qtyNeed ? 'ok' : 'short',
                    ];
                }

                $hasShort = collect($lines)->contains(fn ($line) => $line['qty_short'] > 0);
                $rawPayload = [
                    'no' => $no,
                    'found' => true,
                    'order' => [
                        'invoice_id' => $salesInvoice->id,
                        'invoice_code' => $salesInvoice->code,
                        'order_no' => $salesInvoice->channel_order_no ?: $no,
                        'store_id' => $salesInvoice->store_id,
                        'store_name' => $salesInvoice->store?->name ?? '-',
                        'store_code' => $salesInvoice->store?->code ?? '-',
                        'source' => 'sales_invoice',
                        'status' => $hasShort ? 'partial' : 'ready',
                        'lines' => $lines,
                        'allocated' => [],
                    ],
                    'mode' => 'record_only',
                    'linked_source' => 'sales_invoice',
                    'decision' => $hasShort ? 'pending' : 'fulfill',
                    'pool_full' => $this->buildPoolSnapshot($shipment, $pool),
                    'subs' => [],
                ];

                ShipmentOrderScan::updateOrCreate(
                    ['shipment_id' => $shipment->id, 'order_no' => $no],
                    [
                        'shipment_wave_id' => $currentWave?->id,
                        'fulfillment_id' => null,
                        'status' => $rawPayload['decision'],
                        'source' => 'sales_invoice',
                        'raw_payload' => $rawPayload,
                    ]
                );

                $rawPayload['scanned_at'] = now()->format('d/m/Y H:i:s');

                return response()->json(array_merge(['status' => 'ok'], $rawPayload));
            }

            return $this->recordUnlinkedOrderScan($shipment, $no, $pool);
        }

        // Order dari tab Belum Packing (READY_TO_SHIP) maupun Sedang Dikemas
        // (PROCESSED) boleh masuk workflow scan dan dipromosikan setelah match.
        if ($shipment->shipment_type !== 'manual' && !in_array($marketplaceOrder->order_status, self::RECONCILIATION_MARKETPLACE_STATUSES, true)) {
            $fulfillment = \App\Models\OrderFulfillment::query()
                ->where('marketplace_order_id', $marketplaceOrder->id)
                ->first();
            if ($fulfillment?->status === \App\Models\OrderFulfillment::STATUS_CANCELLED) {
                return response()->json([
                    'status' => 'error',
                    'message' => "Order {$no} berstatus Cancelled. Jangan dikirim!",
                ], 422);
            }

            return $this->recordUnlinkedOrderScan($shipment, $no, $pool, $marketplaceOrder->order_status);
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
        if (!$hasShort) {
            $this->promoteMarketplaceOrderAfterScan($marketplaceOrder);
            $this->updateMarketplaceStatusIfAllowed($marketplaceOrder, 'link');
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
                'shipment_wave_id' => $currentWave?->id,
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

                $scanPayload = is_array($scan?->raw_payload) ? $scan->raw_payload : [];
                $isRecordOnly = (($scanPayload['mode'] ?? null) === 'record_only');
                $isInvoiceLinked = ($scan && $scan->source === 'sales_invoice')
                    || (($scanPayload['linked_source'] ?? null) === 'sales_invoice');
                $isManual = ($shipment->shipment_type === 'manual')
                    || ($scan && $scan->source === 'manual_scan')
                    || (($scan && $scan->source === 'scanner') && (string) $this->salesOperationalSetting('lookup_mode', 'record_only') === 'record_only')
                    || ($isRecordOnly && (string) $this->salesOperationalSetting('lookup_mode', 'record_only') === 'record_only');

                if ($isManual || $isInvoiceLinked) {
                    // Manual order (unlinked)
                    $marketplaceOrder = null;
                    $fulfillment = null;

                    if ($isInvoiceLinked && $row['action'] === 'fulfill') {
                        $invoiceId = data_get($scanPayload, 'order.invoice_id')
                            ?: data_get($scanPayload, 'sales_invoice_id');
                        if ($invoiceId && SalesInvoice::whereKey($invoiceId)->exists()) {
                            $shipment->update(['sales_invoice_id' => $invoiceId]);
                        }
                    }
                } else {
                    $marketplaceOrder = $this->marketplaceOrderQuery($orderNo, $shipment)->first();
                    
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

                $existingScan = $this->activeDuplicateScan($fulfillmentId, $shipment);
                    
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
                if ($marketplaceOrder && $row['action'] === 'fulfill') {
                    $this->promoteMarketplaceOrderAfterScan($marketplaceOrder);
                    $this->updateMarketplaceStatusIfAllowed($marketplaceOrder, 'link');
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

        $request->validate([
            'new_order_no' => ['nullable', 'string', 'max:200'],
            'decision' => ['nullable', 'in:fulfill,pending,skip'],
            'subs' => ['nullable', 'array'],
            'allocations' => ['nullable', 'array'],
            'allocations.*.item_id' => ['required', 'integer', 'exists:items,id'],
            'allocations.*.qty' => ['required', 'integer', 'min:1'],
        ]);
        
        $scan = \App\Models\ShipmentOrderScan::where('shipment_id', $shipment->id)
            ->where('order_no', $orderNo)
            ->firstOrFail();

        $payload = is_array($scan->raw_payload) ? $scan->raw_payload : [];
        if ($request->has('new_order_no')) {
            $newNo = $this->normalizeOrderNumber($request->input('new_order_no'));
            if ($newNo === '') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Nomor order baru tidak boleh kosong.',
                ], 422);
            }
            $scan->order_no = $newNo;
            $payload['no'] = $newNo;
            $payload['order'] = array_merge(
                is_array($payload['order'] ?? null) ? $payload['order'] : [],
                ['order_no' => $newNo]
            );
        }
        
        if ($request->has('decision')) {
            $payload['decision'] = $request->input('decision');
            $scan->status = $request->input('decision') ?: 'pending';
            
            if ($scan->status === 'fulfill') {
                $fulfillment = \App\Models\OrderFulfillment::find($scan->fulfillment_id);
                if ($fulfillment) {
                    $mpOrder = \App\Models\MarketplaceOrder::find($fulfillment->marketplace_order_id);
                    $this->promoteMarketplaceOrderAfterScan($mpOrder);
                    $this->updateMarketplaceStatusIfAllowed($mpOrder, 'link');
                }
            }
        }
        if ($request->has('subs')) {
            $payload['subs'] = $request->input('subs');
        }

        if ($request->has('allocations')) {
            $payload['allocations'] = collect($request->input('allocations', []))
                ->map(fn ($allocation) => [
                    'item_id' => (int) ($allocation['item_id'] ?? 0),
                    'qty' => (int) ($allocation['qty'] ?? 0),
                ])
                ->filter(fn ($allocation) => $allocation['item_id'] > 0 && $allocation['qty'] > 0)
                ->groupBy('item_id')
                ->map(fn ($rows, $itemId) => [
                    'item_id' => (int) $itemId,
                    'qty' => (int) $rows->sum('qty'),
                ])
                ->values()
                ->all();
        }

        DB::transaction(function () use ($shipment, $scan, $payload) {
            Shipment::query()->lockForUpdate()->findOrFail($shipment->id);
            $scan->raw_payload = $payload;
            $scan->save();

            $this->syncItemFirstLineAllocations($shipment->fresh());
        });

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

        $marketplaceOrder = $this->marketplaceOrderQuery($targetNo, $shipment)
            ->with(['items.internalItem', 'store'])
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

        if (!$hasShort) {
            $this->promoteMarketplaceOrderAfterScan($marketplaceOrder);
            $this->updateMarketplaceStatusIfAllowed($marketplaceOrder, 'link');
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
        // Laporan Pengiriman kini berada di grup Sales Reports
        // (route: sales.reports.shipment). Redirect agar URL lama tetap valid.
        return redirect()->route('sales.reports.shipment', $request->query());
    }

}
