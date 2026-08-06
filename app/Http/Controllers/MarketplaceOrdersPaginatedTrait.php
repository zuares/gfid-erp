<?php

namespace App\Http\Controllers;

use App\Models\MarketplaceOrder;
use App\Models\MarketplaceBooking;
use Illuminate\Http\JsonResponse;

trait MarketplaceOrdersPaginatedTrait
{
    public function localOrderCounts(): JsonResponse
    {
        // Simple counts for all relevant statuses without eager loading.
        $baseQuery = MarketplaceOrder::query();
        
        $dateFrom = request()->query('date_from');
        $dateTo   = request()->query('date_to');
        
        if ($dateFrom || $dateTo) {
            $baseQuery->where(function ($q) use ($dateFrom, $dateTo) {
                $q->whereNull('ordered_at');
                $q->orWhere(function ($range) use ($dateFrom, $dateTo) {
                    if ($dateFrom) $range->where('ordered_at', '>=', $dateFrom . ' 00:00:00');
                    if ($dateTo)   $range->where('ordered_at', '<=', $dateTo . ' 23:59:59');
                });
            });
        }
        
        $counts = $baseQuery->select('order_status', \DB::raw('count(*) as total'))
            ->groupBy('order_status')
            ->pluck('total', 'order_status')
            ->all();

        // Calculate issues count (simplified)
        $issuesCount = MarketplaceOrder::whereHas('items', function ($q) {
            $q->where('data_status', '!=', 'valid')->orWhereNull('data_status');
        })->count();

        return response()->json([
            'ready' => ($counts['READY_TO_SHIP'] ?? 0) + ($counts['MATCHED'] ?? 0),
            'processed' => $counts['PROCESSED'] ?? 0,
            'shipped' => ($counts['SHIPPED'] ?? 0) + ($counts['TO_CONFIRM_RECEIVE'] ?? 0),
            'completed' => $counts['COMPLETED'] ?? 0,
            'unpaid' => $counts['UNPAID'] ?? 0,
            'issues' => $issuesCount
        ]);
    }

    public function localOrdersPaginated(): JsonResponse
    {
        $limit = (int) request()->query('limit', 50);
        $tab = request()->query('tab', 'ready');
        $subTab = request()->query('sub_tab', 'all');
        $search = request()->query('search');
        $store = request()->query('store');
        $dateFrom = request()->query('date_from');
        $dateTo = request()->query('date_to');

        $hasScanLog = in_array('scan_log', \Illuminate\Support\Facades\Schema::getColumnListing('order_fulfillments'));
        $fulfillmentSelect = $hasScanLog ? 'id,marketplace_order_id,status,scan_log' : 'id,marketplace_order_id,status';

        $with = [
            'store.channel',
            'items',
            'items.internalItem' => fn ($q) => $q->select('id', 'code', 'item_category_id')->with('category:id,code,name'),
            'fulfillment:' . $fulfillmentSelect,
            'fulfillment.lines',
            'fulfillment.lines.item:id,code,name',
            'fulfillment.lines.splitChildren',
            'fulfillment.lines.splitChildren.item:id,code,name',
        ];

        $query = MarketplaceOrder::with($with);

        // Date Filter
        if ($dateFrom || $dateTo) {
            $query->where(function ($q) use ($dateFrom, $dateTo) {
                $q->whereNull('ordered_at');
                $q->orWhere(function ($range) use ($dateFrom, $dateTo) {
                    if ($dateFrom) $range->where('ordered_at', '>=', $dateFrom . ' 00:00:00');
                    if ($dateTo)   $range->where('ordered_at', '<=', $dateTo . ' 23:59:59');
                });
            });
        }

        // Store Filter
        if ($store) {
            $query->whereHas('store', function($q) use ($store) {
                $q->where('name', $store);
            });
        }

        // Search Filter
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('channel_order_id', 'like', "%{$search}%")
                  ->orWhere('external_order_id', 'like', "%{$search}%")
                  ->orWhere('shipping_awb_no', 'like', "%{$search}%")
                  ->orWhereHas('items', function($iq) use ($search) {
                      $iq->where('model_sku', 'like', "%{$search}%")
                         ->orWhere('item_sku', 'like', "%{$search}%")
                         ->orWhere('variant_name', 'like', "%{$search}%")
                         ->orWhereHas('internalItem', function($iiq) use ($search) {
                             $iiq->where('code', 'like', "%{$search}%");
                         });
                  });
            });
        }

        // Tab Filter
        if ($tab === 'issues') {
            $query->whereHas('items', function ($q) {
                $q->where('data_status', '!=', 'valid')->orWhereNull('data_status');
            });
        } elseif ($tab === 'ready') {
            if ($subTab === 'unpaid') {
                $query->where('order_status', 'UNPAID');
            } else {
                // Approximate logic for kilat/instant handling
                $query->whereIn('order_status', ['READY_TO_SHIP', 'MATCHED']);
                if ($subTab === 'kilat') {
                    $query->where(function($q) {
                        $q->whereNotNull('booking_sn')->where('shipping_carrier', 'not like', '%instant%')->where('shipping_carrier', 'not like', '%same day%');
                    });
                }
            }
        } elseif ($tab === 'processed') {
            $query->where('order_status', 'PROCESSED');
            if ($subTab === 'packing') {
                $query->whereNull('shipping_awb_no');
            } elseif ($subTab === 'ready') {
                $query->whereNotNull('shipping_awb_no');
            }
        } elseif ($tab === 'shipped') {
            $query->whereIn('order_status', ['SHIPPED', 'TO_CONFIRM_RECEIVE']);
        } elseif ($tab === 'completed') {
            $query->where('order_status', 'COMPLETED');
        }

        $paginator = $query->latest('ordered_at')->paginate($limit);

        // Map items exactly like in localOrders
        $paginator->getCollection()->transform(function ($o) use ($hasScanLog) {
            $arr = $o->toArray();
            
            $carrier   = strtolower((string) $o->shipping_carrier);
            $isInstant = str_contains($carrier, 'instant') || str_contains($carrier, 'same day') || str_contains($carrier, 'sameday');
            $arr['is_kilat']               = (!empty($o->booking_sn)) && ! $isInstant;
            $arr['api_order_status']       = null;
            $arr['api_logistics_status']   = null;
            $arr['api_platform_pending']   = null;
            $arr['status_source']          = 'database';
            $arr['fulfillment_id']         = $o->fulfillment?->id;
            $arr['fulfillment_status']     = $o->fulfillment?->status;
            $arr['print_count']            = $o->print_count ?? 0;
            $arr['printed_at']             = $o->printed_at;
            $arr['has_unresolved_lines']   = $o->fulfillment
                ? $o->fulfillment->lines->whereNull('item_id')->isNotEmpty()
                : false;
            $arr['needs_shipping_arrangement'] = $o->needs_shipping_arrangement;
            $arr['has_data_issues'] = $o->items->contains(
                fn ($item) => ($item->data_status ?? 'incomplete') !== 'valid'
            );
            $arr['logistics_status'] = $o->raw_json['package_list'][0]['logistics_status'] ?? null;
            $arr['fulfillment_scan_log'] = null;

            if ($o->fulfillment && $hasScanLog && $o->fulfillment->scan_log) {
                $decoded = json_decode($o->fulfillment->scan_log, true) ?? [];
                $arr['fulfillment_scan_log'] = array_values(
                    array_filter($decoded, fn ($s) => ! empty($s['code']) && ($s['qty'] ?? 0) > 0)
                );
            }

            return $arr;
        });

        return response()->json($paginator);
    }
}
