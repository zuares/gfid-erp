<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Store;
use App\Models\Channel;
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
        $stores = Store::with('channel')->get();
        return view('marketplace.returns', compact('stores'));
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

                $formattedReturns = $returns->map(function ($o) {
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
                        'return_sn' => 'RTS-' . $o->external_order_id,
                        'order_sn' => $o->external_order_id,
                        'status' => 'FAILED_DELIVERY',
                        'reason' => 'Pengiriman Gagal (RTS) / Ditolak',
                        'reason_text_code' => 'RTS',
                        'return_solution' => 'RETURN_TO_SELLER',
                        'amount_before_discount' => $o->total_amount,
                        'needs_logistics' => false,
                        'tracking_number' => $o->shipping_awb_no,
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
            $driver = $this->manager->driver($store);
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
            $driver = $this->manager->driver($store);
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
            $driver = $this->manager->driver($store);
            if (!method_exists($driver, 'confirmReturn')) {
                return response()->json(['error' => 'Not supported on this channel'], 400);
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
