<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Store;
use App\Models\Channel;
use App\Models\MarketplaceReturn;
use App\Models\MarketplaceOrder;
use App\Services\Marketplace\MarketplaceApiGateway;
use App\Services\Channels\ChannelManager;

class MarketplaceReturnController extends Controller
{
    protected ChannelManager $manager;

    public function __construct(ChannelManager $manager)
    {
        $this->manager = $manager;
    }

    public function index(Request $request)
    {
        // Hanya toko Shopee aktif yang sudah terkoneksi (punya access_token).
        // Menghindari loop sync/list ke toko non-Shopee / nonaktif / tanpa token.
        $stores = Store::with('channel')
            ->where('is_active', true)
            ->whereHas('channel', fn ($q) => $q->whereIn('code', ['SHOPEE', 'SHP', 'shopee']))
            ->get()
            ->filter(fn ($s) => ! blank($s->credential('access_token')))
            ->values();

        return view('marketplace.returns', compact('stores'));
    }

    /**
     * Data LIVE Retur / Refund / Batal langsung dari API Shopee.
     * Dipakai oleh tab "Retur/Refund/Batal" di halaman Order Lokal.
     *
     * Query:
     *   type      = return | refund | cancel   (default: return)
     *   date_from = Y-m-d
     *   date_to   = Y-m-d
     *   search    = filter opsional (return_sn / order_sn)
     *
     * Loop semua toko Shopee aktif & terkoneksi, agregasi hasilnya.
     */
    public function live(Request $request)
    {
        $type     = $request->query('type', 'return'); // return | refund | cancel
        $dateFrom = $request->query('date_from');
        $dateTo   = $request->query('date_to');
        $search   = trim((string) $request->query('search', ''));

        // Rentang waktu → unix timestamp
        $from = $dateFrom ? strtotime($dateFrom . ' 00:00:00') : strtotime('-7 days');
        $to   = $dateTo   ? strtotime($dateTo . ' 23:59:59')   : time();

        // Toko Shopee aktif & sudah terkoneksi (punya access_token)
        $stores = Store::with('channel')
            ->where('is_active', true)
            ->whereHas('channel', fn ($q) => $q->whereIn('code', ['SHOPEE', 'SHP', 'shopee']))
            ->get()
            ->filter(fn ($s) => ! blank($s->credential('access_token')))
            ->values();

        // Shopee membatasi rentang create_time maksimal 15 hari per panggilan,
        // jadi rentang lebar dipecah menjadi beberapa jendela.
        $windows = $this->timeWindows($from, $to, 15 * 86400);

        $data   = [];
        $errors = [];

        foreach ($stores as $store) {
            try {
                
                if ($type === 'cancel') {
                    // Order dibatalkan → get_order_list status CANCELLED lalu ambil detail item
                    if (! method_exists($driver, 'getOrderList')) {
                        continue;
                    }
                    $orderSns = [];
                    foreach ($windows as [$wFrom, $wTo]) {
                        $cursor = '';
                        $guard  = 0;
                        do {
                            $listRes = $driver->getOrderList($store, $wFrom, $wTo, 50, $cursor, 'CANCELLED');
                            if (! empty($listRes['error'])) {
                                $errors[] = "[{$store->name}] " . ($listRes['message'] ?? $listRes['error']);
                                break;
                            }
                            foreach (($listRes['response']['order_list'] ?? []) as $o) {
                                if (! empty($o['order_sn'])) {
                                    $orderSns[] = $o['order_sn'];
                                }
                            }
                            $cursor = (string) ($listRes['response']['next_cursor'] ?? '');
                            $more   = ! empty($listRes['response']['more']);
                        } while ($more && $cursor !== '' && ++$guard < 20);
                    }
                    $orderSns = array_values(array_unique($orderSns));
                    if (empty($orderSns)) {
                        continue;
                    }

                    foreach (array_chunk($orderSns, 50) as $chunk) {
                        $detRes = $driver->getOrderDetail($store, $chunk);
                        foreach (($detRes['response']['order_list'] ?? []) as $d) {
                            $sn = $d['order_sn'] ?? '';
                            if ($search !== '' && stripos($sn, $search) === false) {
                                continue;
                            }
                            $items = array_map(function ($it) {
                                return [
                                    'item_sku'       => $it['item_sku'] ?? null,
                                    'variation_sku'  => $it['model_sku'] ?? null,
                                    'item_name'      => $it['item_name'] ?? null,
                                    'variation_name' => $it['model_name'] ?? null,
                                    'quantity'       => $it['model_quantity_purchased'] ?? ($it['active_qty'] ?? 0),
                                    'images'         => ! empty($it['image_info']['image_url']) ? [$it['image_info']['image_url']] : [],
                                ];
                            }, $d['item_list'] ?? []);

                            $data[] = [
                                'store_id'        => $store->id,
                                'store_name'      => $store->name,
                                'kind'            => 'cancel',
                                'order_sn'        => $sn,
                                'return_sn'       => null,
                                'status'          => $d['order_status'] ?? 'CANCELLED',
                                'reason'          => $d['cancel_reason'] ?? (! empty($d['cancel_by']) ? ('Dibatalkan oleh ' . $d['cancel_by']) : 'Dibatalkan'),
                                'amount'          => (float) ($d['total_amount'] ?? 0),
                                'tracking_number' => $d['package_list'][0]['tracking_number'] ?? null,
                                'create_time'     => $d['create_time'] ?? null,
                                'update_time'     => $d['update_time'] ?? null,
                                'items'           => $items,
                            ];
                        }
                    }
                } else {
                    // return | refund → get_return_list (satu API, dipisah via return_solution)
                    if (! method_exists($driver, 'getReturnList')) {
                        continue;
                    }
                    $seen = [];
                    foreach ($windows as [$wFrom, $wTo]) {
                        $pageNo = 0;
                        $guard  = 0;
                        do {
                            $res = $driver->getReturnList($store, $pageNo, 50, $wFrom, $wTo);
                            if (! empty($res['error'])) {
                                $errors[] = "[{$store->name}] " . ($res['message'] ?? $res['error']);
                                break;
                            }
                            $returns = $res['response']['return'] ?? [];

                            foreach ($returns as $r) {
                                $sn  = $r['return_sn'] ?? '';
                                if ($sn !== '' && isset($seen[$sn])) {
                                    continue; // hindari duplikat antar-jendela/halaman
                                }
                                $seen[$sn] = true;

                                // Shopee get_return_list mengirim return_solution sebagai INTEGER
                                // (0 = RETURN_REFUND / barang dikembalikan, 1 = REFUND / refund saja).
                                // Sebagian endpoint lawas bisa mengirim string, jadi tangani keduanya.
                                $rawSolution = $r['return_solution'] ?? null;
                                if (is_numeric($rawSolution)) {
                                    $isRefundOnly = ((int) $rawSolution) === 1;
                                } else {
                                    $sol          = strtoupper((string) $rawSolution);
                                    $isRefundOnly = str_contains($sol, 'REFUND') && ! str_contains($sol, 'RETURN');
                                }

                                if ($type === 'refund' && ! $isRefundOnly) {
                                    continue;
                                }
                                if ($type === 'return' && $isRefundOnly) {
                                    continue;
                                }

                                $osn = $r['order_sn'] ?? '';
                                if ($search !== '' && stripos($sn, $search) === false && stripos($osn, $search) === false) {
                                    continue;
                                }

                                $items = array_map(function ($it) {
                                    return [
                                        'item_sku'       => $it['item_sku'] ?? null,
                                        'variation_sku'  => $it['variation_sku'] ?? ($it['model_sku'] ?? null),
                                        'item_name'      => $it['name'] ?? ($it['item_name'] ?? null),
                                        'variation_name' => $it['variation_name'] ?? null,
                                        'quantity'       => $it['amount'] ?? ($it['return_item_quantity'] ?? ($it['quantity'] ?? 0)),
                                        'images'         => ! empty($it['images']) ? (array) $it['images'] : [],
                                    ];
                                }, $r['item'] ?? []);

                                $data[] = [
                                    'store_id'        => $store->id,
                                    'store_name'      => $store->name,
                                    'kind'            => $isRefundOnly ? 'refund' : 'return',
                                    'order_sn'        => $osn,
                                    'return_sn'       => $sn,
                                    'status'          => $r['status'] ?? null,
                                    'reason'          => $r['reason'] ?? null,
                                    'return_solution' => $r['return_solution'] ?? null,
                                    'amount'          => (float) ($r['refund_amount'] ?? ($r['amount_before_discount'] ?? 0)),
                                    'tracking_number' => $r['tracking_number'] ?? null,
                                    'needs_logistics' => $r['needs_logistics'] ?? false,
                                    'create_time'     => $r['create_time'] ?? null,
                                    'update_time'     => $r['update_time'] ?? null,
                                    'items'           => $items,
                                ];
                            }

                            $more = ! empty($res['response']['more']);
                            $pageNo++;
                        } while ($more && ++$guard < 20);
                    }
                }
            } catch (\Throwable $e) {
                $errors[] = "[{$store->name}] " . $e->getMessage();
            }
        }

        // Terbaru dulu
        usort($data, fn ($a, $b) => ($b['create_time'] ?? 0) <=> ($a['create_time'] ?? 0));

        return response()->json([
            'type'           => $type,
            'data'           => $data,
            'count'          => count($data),
            'errors'         => $errors,
            'stores_queried' => $stores->count(),
        ]);
    }

    /** Pecah rentang [from, to] menjadi jendela berukuran maksimal $maxSpan detik. */
    private function timeWindows(int $from, int $to, int $maxSpan): array
    {
        if ($to <= $from) {
            return [[$from, max($from, $to)]];
        }
        $windows = [];
        $start = $from;
        while ($start < $to) {
            $end = min($start + $maxSpan - 1, $to);
            $windows[] = [$start, $end];
            $start = $end + 1;
        }
        return $windows;
    }

    /**
     * Baca data Retur / Refund / Batal dari DATABASE (agregat lintas toko).
     * Tanpa batas rentang tanggal (kalau tidak diberi date_from/date_to → semua riwayat).
     * Sumbernya tersimpan & anti-duplikat: retur/refund via unique return_sn,
     * batal via order yang sudah tersimpan (unik per toko).
     */
    public function storedRrc(Request $request)
    {
        $type     = $request->query('type', 'return'); // return | refund | cancel
        $dateFrom = $request->query('date_from');
        $dateTo   = $request->query('date_to');
        $search   = trim((string) $request->query('search', ''));

        $from = $dateFrom ? strtotime($dateFrom . ' 00:00:00') : null;
        $to   = $dateTo   ? strtotime($dateTo . ' 23:59:59')   : null;

        $storeIds = Store::whereHas('channel', fn ($q) => $q->whereIn('code', ['SHOPEE', 'SHP', 'shopee']))
            ->pluck('id');

        $data = [];

        if ($type === 'cancel') {
            $q = \App\Models\MarketplaceOrder::with(['items', 'store'])
                ->whereIn('store_id', $storeIds)
                ->where('status', 'cancelled');

            if ($from && $to) {
                $q->where(function ($w) use ($from, $to) {
                    $w->whereBetween('cancelled_at', [date('Y-m-d H:i:s', $from), date('Y-m-d H:i:s', $to)])
                      ->orWhereBetween('order_date', [date('Y-m-d H:i:s', $from), date('Y-m-d H:i:s', $to)]);
                });
            }
            if ($search !== '') {
                $q->where(fn ($w) => $w->where('external_order_id', 'like', "%{$search}%")->orWhere('shipping_awb_no', 'like', "%{$search}%"));
            }

            $orders = $q->orderByRaw('COALESCE(cancelled_at, order_date) DESC')->limit(1000)->get();
            foreach ($orders as $o) {
                $items = $o->items->map(fn ($it) => [
                    'item_sku'       => $it->item_sku,
                    'variation_sku'  => $it->model_sku,
                    'item_name'      => $it->item_name,
                    'variation_name' => $it->variant_name,
                    'quantity'       => $it->qty,
                    'images'         => $it->image_url ? [$it->image_url] : [],
                ])->toArray();

                $data[] = [
                    'store_id'        => $o->store_id,
                    'store_name'      => optional($o->store)->name,
                    'kind'            => 'cancel',
                    'order_sn'        => $o->external_order_id,
                    'return_sn'       => null,
                    'status'          => $o->order_status ?? 'CANCELLED',
                    'reason'          => 'Dibatalkan',
                    'amount'          => (float) $o->total_amount,
                    'tracking_number' => $o->shipping_awb_no,
                    'create_time'     => $o->cancelled_at ? $o->cancelled_at->timestamp : ($o->order_date ? strtotime($o->order_date) : null),
                    'items'           => $items,
                ];
            }
        } else {
            $q = MarketplaceReturn::with(['items', 'store'])->whereIn('store_id', $storeIds);

            // Shopee: return_solution 1 = REFUND (refund saja), 0/2 = RETURN_REFUND (retur).
            if ($type === 'refund') {
                $q->where('return_solution', 1);
            } else {
                $q->where(function ($w) {
                    $w->where('return_solution', '!=', 1)->orWhereNull('return_solution');
                });
            }
            if ($from && $to) {
                $q->whereBetween('create_time', [$from, $to]);
            }
            if ($search !== '') {
                $q->where(fn ($w) => $w->where('return_sn', 'like', "%{$search}%")
                    ->orWhere('order_sn', 'like', "%{$search}%")
                    ->orWhere('tracking_number', 'like', "%{$search}%"));
            }

            $returns = $q->orderBy('create_time', 'desc')->limit(1000)->get();
            foreach ($returns as $r) {
                $items = $r->items->map(fn ($it) => [
                    'item_sku'       => $it->item_sku,
                    'variation_sku'  => $it->variation_sku,
                    'item_name'      => $it->item_name,
                    'variation_name' => $it->variation_name,
                    'quantity'       => $it->return_item_quantity,
                    'images'         => is_array($it->images) ? $it->images : [],
                ])->toArray();

                $data[] = [
                    'store_id'        => $r->store_id,
                    'store_name'      => optional($r->store)->name,
                    'kind'            => ((int) $r->return_solution === 1) ? 'refund' : 'return',
                    'order_sn'        => $r->order_sn,
                    'return_sn'       => $r->return_sn,
                    'status'          => $r->status,
                    'reason'          => $r->reason,
                    'return_solution' => $r->return_solution,
                    'amount'          => (float) $r->amount_before_discount,
                    'tracking_number' => $r->tracking_number,
                    'needs_logistics' => $r->needs_logistics,
                    'create_time'     => $r->create_time,
                    'update_time'     => $r->update_time,
                    'items'           => $items,
                ];
            }
        }

        return response()->json([
            'type'           => $type,
            'data'           => $data,
            'count'          => count($data),
            'source'         => 'db',
            'stores_queried' => $storeIds->count(),
        ]);
    }

    /**
     * Tarik & simpan retur/refund dari Shopee untuk SEMUA toko aktif (anti-duplikat via upsert).
     * full=true → riwayat penuh (dipecah 15 harian di dalam job).
     */
    public function syncAllReturns(Request $request)
    {
        @set_time_limit(300);

        $full = $request->boolean('full', true);
        $from = $request->query('create_time_from');
        $to   = $request->query('create_time_to');

        $stores = Store::whereHas('channel', fn ($q) => $q->whereIn('code', ['SHOPEE', 'SHP', 'shopee']))
            ->where('is_active', true)
            ->get()
            ->filter(fn ($s) => ! blank($s->credential('access_token')));

        $synced = 0;
        $errors = [];
        foreach ($stores as $store) {
            try {
                dispatch_sync(new \App\Jobs\SyncMarketplaceReturns(
                    $store,
                    $from ? (int) $from : null,
                    $to ? (int) $to : null,
                    $full
                ));
                $synced++;
            } catch (\Throwable $e) {
                $errors[] = "[{$store->name}] " . $e->getMessage();
            }
        }

        return response()->json(['success' => true, 'stores_synced' => $synced, 'errors' => $errors]);
    }

    public function getReturnList(Store $store, Request $request)
    {
        try {
            $pageNo = $request->query('page_no', 0);
            $pageSize = $request->query('page_size', 40);
            $createTimeFrom = $request->query('create_time_from');
            $createTimeTo = $request->query('create_time_to');
            $type = $request->query('type', 'return');
            $search = $request->query('search', '');

            if ($type === 'rts') {
                $query = \App\Models\MarketplaceOrder::with(['items'])
                    ->where('store_id', $store->id)
                    ->where('status', 'cancelled')
                    ->whereNotNull('shipping_awb_no');

                if (!empty($search)) {
                    $query->where(function($q) use ($search) {
                        $q->where('external_order_id', 'like', "%{$search}%")
                          ->orWhere('shipping_awb_no', 'like', "%{$search}%");
                    });
                }

                if ($createTimeFrom && $createTimeTo) {
                    $query->whereBetween('cancelled_at', [
                        date('Y-m-d H:i:s', (int)$createTimeFrom),
                        date('Y-m-d H:i:s', (int)$createTimeTo)
                    ]);
                }

                // Fallback ke order_date jika cancelled_at null
                $returns = $query->orderByRaw('COALESCE(cancelled_at, order_date) DESC')
                    ->skip((int)$pageNo)
                    ->take((int)$pageSize)
                    ->get();
                $total = $query->count();

                // Resi pada marketplace_orders adalah resi pengiriman awal.
                // Untuk RTS, tampilkan hanya resi pengembalian dari record retur
                // Shopee yang punya Return SN terkait — jangan sampai operator
                // mengira resi awal sebagai resi barang kembali.
                $orderSns = $returns->map(fn ($order) => $order->channel_order_id ?: $order->external_order_id)
                    ->filter()
                    ->unique()
                    ->values();
                $returnShipments = MarketplaceReturn::query()
                    ->where('store_id', $store->id)
                    ->whereIn('order_sn', $orderSns)
                    ->orderByDesc('update_time')
                    ->orderByDesc('id')
                    ->get()
                    ->unique('order_sn')
                    ->keyBy('order_sn');

                $formattedReturns = $returns->map(function ($o) use ($returnShipments) {
                    $orderSn = $o->channel_order_id ?: $o->external_order_id;
                    $returnShipment = $returnShipments->get($orderSn);
                    $items = $o->items->map(function ($itm) {
                        return [
                            'item_sku' => $itm->item_sku,
                            'variation_sku' => $itm->model_sku,
                            'item_name' => $itm->item_name,
                            'variation_name' => $itm->variant_name,
                            'internal_name' => null, 
                            'return_item_quantity' => $itm->qty,
                            'images' => $itm->image_url ? [$itm->image_url] : [],
                        ];
                    });

                    return [
                        'store_id' => $o->store_id,
                        'return_sn' => $returnShipment?->return_sn ?: 'RTS-' . $orderSn,
                        'order_sn' => $orderSn,
                        'status' => 'FAILED_DELIVERY',
                        'reason' => 'Pengiriman Gagal (RTS) / Ditolak',
                        'reason_text_code' => 'RTS',
                        'return_solution' => 'RETURN_TO_SELLER',
                        'amount_before_discount' => $o->total_amount,
                        'needs_logistics' => (bool) $returnShipment?->needs_logistics,
                        'tracking_number' => $returnShipment?->tracking_number,
                        'original_tracking_number' => $o->shipping_awb_no,
                        'is_return_shipment' => (bool) $returnShipment,
                        'create_time' => $o->cancelled_at ? $o->cancelled_at->timestamp : strtotime($o->order_date),
                        'update_time' => $o->updated_at->timestamp,
                        'item' => $items->toArray(),
                    ];
                });

                return response()->json([
                    'return' => $formattedReturns,
                    'more' => ($pageNo + $pageSize) < $total
                ]);
            }

            $query = \App\Models\MarketplaceReturn::with(['items' => function($q) {
                $q->with('item:id,code,name');
            }])->where('store_id', $store->id);

            // Pisahkan Retur vs Refund (konsisten dengan storedRrc()).
            // Shopee: return_solution 1 = REFUND (refund saja), 0/2 = RETURN_REFUND (retur).
            if ($type === 'refund') {
                $query->where('return_solution', 1);
            } elseif ($type === 'return') {
                $query->where(function ($w) {
                    $w->where('return_solution', '!=', 1)->orWhereNull('return_solution');
                });
            }

            if (!empty($search)) {
                $query->where(function($q) use ($search) {
                    $q->where('return_sn', 'like', "%{$search}%")
                      ->orWhere('order_sn', 'like', "%{$search}%")
                      ->orWhere('tracking_number', 'like', "%{$search}%");
                });
            }

            if ($createTimeFrom && $createTimeTo) {
                $query->whereBetween('create_time', [(int)$createTimeFrom, (int)$createTimeTo]);
            }

            $returns = $query->orderBy('create_time', 'desc')
                ->skip((int)$pageNo)
                ->take((int)$pageSize)
                ->get();

            $total = $query->count();

            // Format ke bentuk response API yang diharapkan UI
            $formattedReturns = $returns->map(function ($r) {
                $items = $r->items->map(function ($itm) {
                    return [
                        'item_sku' => $itm->item_sku,
                        'variation_sku' => $itm->variation_sku,
                        'item_name' => $itm->item_name,
                        'variation_name' => $itm->variation_name,
                        'internal_name' => $itm->item ? $itm->item->name : null,
                        'return_item_quantity' => $itm->return_item_quantity,
                        'images' => $itm->images,
                    ];
                });

                return [
                    'store_id' => $r->store_id,
                    'return_sn' => $r->return_sn,
                    'order_sn' => $r->order_sn,
                    'status' => $r->status,
                    'reason' => $r->reason,
                    'reason_text_code' => $r->reason_text_code,
                    'return_solution' => $r->return_solution,
                    'amount_before_discount' => $r->amount_before_discount,
                    'needs_logistics' => $r->needs_logistics,
                    'tracking_number' => $r->tracking_number,
                    'create_time' => $r->create_time,
                    'update_time' => $r->update_time,
                    'item' => $items->toArray(),
                ];
            });

            return response()->json([
                'return' => $formattedReturns,
                'more' => ($pageNo + $pageSize) < $total,
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function syncReturns(Store $store, Request $request)
    {
        try {
            $createTimeFrom = $request->query('create_time_from');
            $createTimeTo = $request->query('create_time_to');
            
            // Sync secara synchronous agar UI mendapat data terbaru setelah refresh
            dispatch_sync(new \App\Jobs\SyncMarketplaceReturns($store, $createTimeFrom ? (int)$createTimeFrom : null, $createTimeTo ? (int)$createTimeTo : null));
            
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function getReturnDetail(Store $store, string $returnSn)
    {
        try {
                        if (!method_exists($driver, 'getReturnDetail')) {
                return response()->json(['error' => 'Not supported'], 400);
            }

            $result = $driver->getReturnDetail($store, $returnSn);
            if (isset($result['error']) && !empty($result['error'])) {
                return response()->json(['error' => $result['message'] ?? $result['error']], 400);
            }

            return response()->json($result['response'] ?? $result);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function getTracking(Store $store, $returnSn, Request $request)
    {
        try {
                        if (!method_exists($driver, 'getReverseTrackingInfo')) {
                return response()->json(['error' => 'Not supported'], 400);
            }

            $result = $driver->getReverseTrackingInfo($store, $returnSn);
            
            // Handle error when tracking info is not yet available but it's not a systemic failure
            if (isset($result['error']) && !empty($result['error'])) {
                if ($result['error'] === 'returns.error_reverse_logistics' || $result['error'] === 'returns.error_reverse_logistics_tracking_info') {
                    return response()->json([
                        'tracking_info' => [],
                        'message' => 'Belum ada data pelacakan retur atau retur tidak menggunakan logistik yang didukung.'
                    ]);
                }
                return response()->json(['error' => $result['message'] ?? $result['error']], 400);
            }

            return response()->json($result['response'] ?? $result);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function confirmAndRestock(Store $store, string $returnSn)
    {
        try {
                        if (!method_exists($driver, 'confirmReturn')) {
                return response()->json(['error' => 'Not supported on this channel'], 400);
            }

            // 0. Idempoten: kalau draf retur untuk return_sn ini sudah pernah dibuat,
            //    jangan konfirmasi ulang ke Shopee & jangan bikin draf ganda —
            //    langsung arahkan ke draf yang sudah ada.
            $existing = \App\Models\ShipmentReturn::where('store_id', $store->id)
                ->where('notes', 'like', '%Return SN: ' . $returnSn . '%')
                ->first();
            if ($existing) {
                return response()->json([
                    'success'      => true,
                    'message'      => 'Retur ini sudah pernah dikonfirmasi. Membuka draf retur yang sudah ada.',
                    'redirect_url' => route('shipment_returns.edit', $existing->id),
                ]);
            }

            // 1. Confirm directly to marketplace
            $res = $driver->confirmReturn($store, $returnSn);
            if (isset($res['error']) && !empty($res['error'])) {
                throw new \Exception($res['message'] ?? $res['error']);
            }

            // 2. Fetch Return Detail to know which items to restock
            $detailRes = $driver->getReturnDetail($store, $returnSn);
            $items = [];
            $orderSn = '';
            if (isset($detailRes['response'])) {
                $resp = $detailRes['response'];
                $orderSn = $resp['order_sn'] ?? '';
                if (isset($resp['item'])) {
                    foreach ($resp['item'] as $it) {
                        $items[] = [
                            'item_id' => $it['item_id'],
                            'model_id' => $it['model_id'],
                            'item_name' => $it['item_name'],
                            'model_name' => $it['model_name'],
                            'amount' => $it['amount'] ?? 1,
                        ];
                    }
                }
            }

            // 3. Buat Dokumen Retur Pengiriman (ShipmentReturn) di ERP
            \Illuminate\Support\Facades\DB::beginTransaction();
            try {
                // Buat draf retur (status DRAFT agar gudang bisa scan & review)
                $shipmentReturn = \App\Models\ShipmentReturn::create([
                    'store_id' => $store->id,
                    'date' => now()->toDateString(),
                    'status' => 'draft',
                    'reason' => 'Retur Marketplace',
                    'notes' => 'Auto-generated from Shopee Returns. Order SN: ' . $orderSn . ' | Return SN: ' . $returnSn,
                    'created_by' => auth()->id() ?? 1,
                ]);

                // Buat data order scan (sebagai referensi pesanan apa yang diretur)
                if ($orderSn) {
                    \App\Models\ShipmentReturnOrderScan::create([
                        'shipment_return_id' => $shipmentReturn->id,
                        'order_no' => $orderSn,
                        'order_number' => $orderSn,
                        'status' => 'pending',
                        'match_status' => 'pending',
                        'source' => 'marketplace',
                        'source_type' => 'auto',
                        'raw_payload' => $items,
                    ]);
                }

                \Illuminate\Support\Facades\DB::commit();

                return response()->json([
                    'success' => true, 
                    'message' => 'Retur berhasil dikonfirmasi ke Shopee dan Draf Retur Gudang berhasil dibuat.',
                    'order_sn' => $orderSn,
                    'redirect_url' => route('shipment_returns.edit', $shipmentReturn->id)
                ]);

            } catch (\Exception $e) {
                \Illuminate\Support\Facades\DB::rollBack();
                throw $e;
            }

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
