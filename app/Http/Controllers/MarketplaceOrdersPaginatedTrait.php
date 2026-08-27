<?php

namespace App\Http\Controllers;

use App\Models\MarketplaceOrder;
use App\Models\MarketplaceBooking;
use App\Models\Item;
use App\Models\SkuMapping;
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

        // Calculate processed_instant count
        $processedInstantCount = MarketplaceOrder::whereIn('order_status', ['PROCESSED', 'READY_TO_HANDOVER'])
            ->where(function($q) {
                $q->where('shipping_carrier', 'like', '%instant%')
                  ->orWhere('shipping_carrier', 'like', '%same day%')
                  ->orWhere('shipping_carrier', 'like', '%sameday%');
            })->count();

        return response()->json([
            'ready' => ($counts['READY_TO_SHIP'] ?? 0) + ($counts['MATCHED'] ?? 0),
            'processed' => ($counts['PROCESSED'] ?? 0) + ($counts['READY_TO_HANDOVER'] ?? 0),
            'processed_instant' => $processedInstantCount,
            'shipped' => ($counts['SHIPPED'] ?? 0) + ($counts['TO_CONFIRM_RECEIVE'] ?? 0),
            'completed' => $counts['COMPLETED'] ?? 0,
            'unpaid' => $counts['UNPAID'] ?? 0,
            'rrc' => ($counts['CANCELLED'] ?? 0) + ($counts['IN_CANCEL'] ?? 0) + ($counts['CANCELLED_BEFORE_SHIPPING'] ?? 0) + ($counts['TO_RETURN'] ?? 0) + ($counts['RETURNED'] ?? 0),
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
            $query->whereIn('order_status', ['PROCESSED', 'READY_TO_HANDOVER']);
            if ($subTab === 'packing') {
                // Belum Packing = sudah diproses di marketplace, tetapi belum
                // pernah discan sebagai order di modul Shipments.
                $query->where('order_status', 'PROCESSED')
                    ->whereDoesntHave('fulfillment.shipmentScans');
            } elseif ($subTab === 'ready') {
                $query->whereNotNull('shipping_awb_no');
            }
        } elseif ($tab === 'processed_instant') {
            $query->whereIn('order_status', ['PROCESSED', 'READY_TO_HANDOVER'])
                  ->where(function($q) {
                      $q->where('shipping_carrier', 'like', '%instant%')
                        ->orWhere('shipping_carrier', 'like', '%same day%')
                        ->orWhere('shipping_carrier', 'like', '%sameday%');
                  });
        } elseif ($tab === 'shipped') {
            $query->whereIn('order_status', ['SHIPPED', 'TO_CONFIRM_RECEIVE']);
        } elseif ($tab === 'completed') {
            $query->where('order_status', 'COMPLETED');
        } elseif ($tab === 'rrc') {
            $query->whereIn('order_status', ['CANCELLED', 'IN_CANCEL', 'CANCELLED_BEFORE_SHIPPING', 'TO_RETURN', 'RETURNED']);
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

        // Pesanan Kilat baru sering masih tersimpan sebagai booking tanpa
        // marketplace_order (order_sn belum diberikan Shopee). Endpoint lama
        // menyertakan pseudo-order ini, jadi endpoint paginated juga harus
        // mengembalikannya saat sub-tab Kilat dibuka.
        if ($tab === 'ready' && $subTab === 'kilat' && (int) request()->query('page', 1) === 1) {
            $pureBookingRows = $this->pureKilatBookingRows($store, $search);

            if ($pureBookingRows->isNotEmpty()) {
                $payload = $paginator->toArray();
                $merged = $paginator->getCollection()
                    ->concat($pureBookingRows)
                    ->sortByDesc('ordered_at')
                    ->values();

                $payload['data'] = $merged->all();
                $payload['total'] = $paginator->total() + $pureBookingRows->count();
                $payload['last_page'] = max(1, (int) ceil($payload['total'] / max(1, $limit)));

                return response()->json($payload);
            }
        }

        return response()->json($paginator);
    }

    private function pureKilatBookingRows(?string $store, ?string $search): \Illuminate\Support\Collection
    {
        if (! \Illuminate\Support\Facades\Schema::hasTable('marketplace_bookings')) {
            return collect();
        }

        $bookings = MarketplaceBooking::with('store.channel')
            ->whereIn('booking_status', ['PENDING', 'READY_TO_SHIP', 'PROCESSED'])
            ->get();

        // Booking aktif adalah antrean operasional, bukan histori penjualan.
        // Tetap tampilkan meskipun created_at lebih lama dari rentang tanggal
        // halaman Orders, agar jumlahnya konsisten dengan /marketplace/kilat.
        $bookings = $bookings->filter(function ($booking) use ($store, $search) {
            if ($store && $booking->store?->name !== $store) {
                return false;
            }

            if ($search) {
                $parts = [$booking->booking_sn, $booking->order_sn, $booking->shipping_carrier];
                foreach ((array) $booking->items as $item) {
                    if (is_array($item)) {
                        $parts[] = $item['model_sku'] ?? null;
                        $parts[] = $item['item_sku'] ?? null;
                        $parts[] = $item['item_name'] ?? null;
                    }
                }

                if (mb_stripos(implode(' ', array_filter($parts)), $search) === false) {
                    return false;
                }
            }

            return true;
        });

        $allSkus = $bookings->flatMap(function ($booking) {
            return collect(is_array($booking->items) ? $booking->items : [])
                ->map(fn ($item) => $item['model_sku'] ?? $item['item_sku'] ?? null);
        })->filter()->unique()->values()->all();

        $mappedItems = Item::whereIn('code', $allSkus)
            ->select('id', 'code', 'item_category_id', 'name')
            ->with('category:id,code,name')
            ->get()
            ->keyBy('code');

        $skuMapped = SkuMapping::with(['item' => fn ($query) => $query
            ->select('id', 'code', 'item_category_id', 'name')
            ->with('category:id,code,name')])
            ->whereIn('marketplace_sku', $allSkus)
            ->get()
            ->sortBy(fn ($mapping) => $mapping->channel_code === null ? 1 : 0)
            ->unique('marketplace_sku')
            ->keyBy('marketplace_sku');

        // Jangan tampilkan pseudo-order bila booking ternyata sudah terhubung
        // ke order lokal melalui salah satu nomor identitasnya.
        $sns = $bookings->flatMap(fn ($booking) => [
            $booking->booking_sn,
            $booking->order_sn,
        ])->filter()->unique()->values();

        $knownSns = collect();
        if ($sns->isNotEmpty()) {
            $knownSns = MarketplaceOrder::query()
                ->where(function ($query) use ($sns) {
                    $query->whereIn('channel_order_id', $sns->all())
                        ->orWhereIn('external_order_id', $sns->all())
                        ->orWhereIn('booking_sn', $sns->all());
                })
                ->get(['channel_order_id', 'external_order_id', 'booking_sn'])
                ->flatMap(fn ($order) => [
                    $order->channel_order_id,
                    $order->external_order_id,
                    $order->booking_sn,
                ])->filter()->flip();
        }

        return $bookings
            ->reject(fn ($booking) => $knownSns->has($booking->booking_sn)
                || ($booking->order_sn && $knownSns->has($booking->order_sn)))
            ->map(function ($booking) use ($mappedItems, $skuMapped) {
                $items = collect(is_array($booking->items) ? $booking->items : [])
                    ->map(function ($item) use ($mappedItems, $skuMapped) {
                        $sku = $item['model_sku'] ?? $item['item_sku'] ?? null;
                        $title = trim(($item['item_name'] ?? '')
                            . (! empty($item['model_name']) ? ' - ' . $item['model_name'] : '')) ?: null;
                        $mapped = $sku
                            ? ($mappedItems->get($sku) ?? $skuMapped->get($sku)?->item)
                            : null;

                        return [
                            'qty' => $item['quantity'] ?? $item['model_quantity_purchased'] ?? 1,
                            'variant_name' => $sku ?: $title,
                            'item_name' => $item['item_name'] ?? null,
                            'model_sku' => $sku,
                            'item_sku' => $item['item_sku'] ?? null,
                            'internal_item' => $mapped ? [
                                'id' => $mapped->id,
                                'code' => $mapped->code,
                                'name' => $mapped->name,
                                'category' => $mapped->category ? [
                                    'id' => $mapped->category->id,
                                    'code' => $mapped->category->code,
                                    'name' => $mapped->category->name,
                                ] : null,
                            ] : null,
                        ];
                    })->values()->all();

                $bookingStatus = (string) $booking->booking_status;
                $needsShipping = $booking->needsShipping();
                $orderedAt = $booking->create_time
                    ? \Carbon\Carbon::createFromTimestamp((int) $booking->create_time)->toIso8601String()
                    : optional($booking->created_at)->toIso8601String();

                return [
                    'id' => -$booking->id,
                    'store_id' => $booking->store_id,
                    'store' => $booking->store ? [
                        'id' => $booking->store->id,
                        'name' => $booking->store->name,
                        'channel' => $booking->store->channel ? [
                            'code' => strtolower((string) $booking->store->channel->code),
                            'name' => $booking->store->channel->name,
                        ] : null,
                    ] : null,
                    'channel_order_id' => $booking->order_sn ?: $booking->booking_sn,
                    'external_order_id' => $booking->order_sn,
                    'booking_sn' => $booking->booking_sn,
                    'order_status' => $needsShipping ? 'READY_TO_SHIP' : $bookingStatus,
                    'platform_status' => $bookingStatus,
                    'status_source' => 'database',
                    'ordered_at' => $orderedAt,
                    'items' => $items,
                    'shipping_carrier' => $booking->shipping_carrier,
                    'shipping_awb_no' => $booking->tracking_number,
                    'raw_json' => $booking->raw_json,
                    'is_kilat' => true,
                    'is_booking' => true,
                    'needs_shipping_arrangement' => $needsShipping,
                    'fulfillment_id' => null,
                    'fulfillment_status' => null,
                    'print_count' => $booking->print_count ?? 0,
                    'printed_at' => $booking->printed_at
                        ? \Carbon\Carbon::parse($booking->printed_at)->toIso8601String()
                        : null,
                    'has_unresolved_lines' => false,
                    'has_data_issues' => false,
                    'logistics_status' => null,
                    'fulfillment_scan_log' => null,
                    'fulfillment_resolve_lines' => [],
                    'fulfillment_packing_summary' => null,
                    'fulfillment_lines' => [],
                ];
            })->values();
    }
}
