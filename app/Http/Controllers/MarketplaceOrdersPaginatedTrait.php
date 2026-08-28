<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\MarketplaceOrder;
use Illuminate\Http\JsonResponse;

trait MarketplaceOrdersPaginatedTrait
{
    public function localOrderCounts(): JsonResponse
    {
        // Simple counts for all relevant statuses without eager loading.
        $dateFrom = request()->query('date_from');
        $dateTo   = request()->query('date_to');
        $store = request()->query('store');
        $search = trim((string) request()->query('search', ''));

        $applyScope = function ($query) use ($dateFrom, $dateTo, $store, $search) {
            $this->excludeKilatOrders($query);

            if ($dateFrom || $dateTo) {
                $query->where(function ($q) use ($dateFrom, $dateTo) {
                    $q->whereNull('ordered_at');
                    $q->orWhere(function ($range) use ($dateFrom, $dateTo) {
                        if ($dateFrom) $range->where('ordered_at', '>=', $dateFrom . ' 00:00:00');
                        if ($dateTo)   $range->where('ordered_at', '<=', $dateTo . ' 23:59:59');
                    });
                });
            }

            if ($store) {
                $query->whereHas('store', fn ($q) => $q->where('name', $store));
            }

            if ($search !== '') {
                $query->where(function ($q) use ($search) {
                    $q->where('channel_order_id', 'like', "%{$search}%")
                        ->orWhere('external_order_id', 'like', "%{$search}%")
                        ->orWhere('shipping_awb_no', 'like', "%{$search}%")
                        ->orWhereHas('items', function ($itemQuery) use ($search) {
                            $itemQuery->where('model_sku', 'like', "%{$search}%")
                                ->orWhere('item_sku', 'like', "%{$search}%")
                                ->orWhere('variant_name', 'like', "%{$search}%")
                                ->orWhereHas('internalItem', fn ($internalQuery) =>
                                    $internalQuery->where('code', 'like', "%{$search}%"));
                        });
                });
            }
        };

        $baseQuery = MarketplaceOrder::query();
        $applyScope($baseQuery);

        $counts = $baseQuery->select('order_status', \DB::raw('count(*) as total'))
            ->groupBy('order_status')
            ->pluck('total', 'order_status')
            ->all();

        // Calculate issues count (simplified)
        $issuesQuery = MarketplaceOrder::whereHas('items', function ($q) {
            $q->where('data_status', '!=', 'valid')->orWhereNull('data_status');
        });
        $applyScope($issuesQuery);
        $issuesCount = $issuesQuery->count();

        return response()->json([
            'ready' => ($counts['READY_TO_SHIP'] ?? 0) + ($counts['MATCHED'] ?? 0),
            'processed' => ($counts['PROCESSED'] ?? 0) + ($counts['READY_TO_HANDOVER'] ?? 0),
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
            'settlement:id,order_id,buyer_payment_amount,seller_voucher,raw_json,final_income,settlement_time,data_status',
            'incomeEstimate:id,marketplace_order_id,estimated_escrow_amount,estimated_payout_at,income_status,payment_method,synced_at',
            'items.internalItem' => fn ($q) => $q
                ->select('id', 'code', 'name', 'unit', 'stock_unit', 'item_category_id')
                ->with('category:id,code,name')
                ->withSum('inventoryStocks as stock_on_hand', 'qty')
                ->withSum('inventoryStocks as stock_allocated', 'allocated_qty'),
            'fulfillment:' . $fulfillmentSelect,
            'fulfillment.lines',
            'fulfillment.lines.item:id,code,name',
            'fulfillment.lines.splitChildren',
            'fulfillment.lines.splitChildren.item:id,code,name',
        ];

        $query = MarketplaceOrder::with($with);
        $this->excludeKilatOrders($query);

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
        } elseif ($tab === 'shipped') {
            $query->whereIn('order_status', ['SHIPPED', 'TO_CONFIRM_RECEIVE']);
        } elseif ($tab === 'completed') {
            $query->where('order_status', 'COMPLETED');
        } elseif ($tab === 'rrc') {
            $query->whereIn('order_status', ['CANCELLED', 'IN_CANCEL', 'CANCELLED_BEFORE_SHIPPING', 'TO_RETURN', 'RETURNED']);
        }

        $paginator = $query->latest('ordered_at')->paginate($limit);
        $buyerPurchaseCounts = $this->buyerPurchaseCounts($paginator->getCollection());
        $baseStockReferences = $this->baseStockReferences($paginator->getCollection());

        // Map items exactly like in localOrders
        $paginator->getCollection()->transform(function ($o) use ($hasScanLog, $buyerPurchaseCounts, $baseStockReferences) {
            $arr = $o->toArray();

            $buyerKey = $this->buyerIdentityKey($o);
            $purchaseCount = $buyerKey ? (int) ($buyerPurchaseCounts[$buyerKey] ?? 0) : 0;
            $currentOrderCounts = $this->isCountableBuyerOrder($o) ? 1 : 0;
            $previousPurchaseCount = max(0, $purchaseCount - $currentOrderCounts);
            $arr['buyer_previous_order_count'] = $previousPurchaseCount;
            $arr['is_repeat_buyer'] = $previousPurchaseCount > 0;

            if (isset($arr['items']) && is_array($arr['items'])) {
                foreach ($arr['items'] as &$orderItem) {
                    if (! empty($orderItem['internal_item'])) {
                        $onHand = (float) ($orderItem['internal_item']['stock_on_hand'] ?? 0);
                        $allocated = (float) ($orderItem['internal_item']['stock_allocated'] ?? 0);
                        $orderItem['internal_item']['stock_available'] = $onHand - $allocated;
                        $orderItem['internal_item']['stock_unit'] = $orderItem['internal_item']['stock_unit']
                            ?: ($orderItem['internal_item']['unit'] ?? 'pcs');

                        $stockReferenceCode = $this->baseStockReferenceCode($orderItem['internal_item']['code'] ?? null);
                        $stockReference = $stockReferenceCode
                            ? ($baseStockReferences[mb_strtoupper($stockReferenceCode)] ?? null)
                            : null;
                        if ($stockReference) {
                            $orderItem['internal_item']['stock_available'] = $stockReference['stock_available'];
                            $orderItem['internal_item']['stock_unit'] = $stockReference['stock_unit'];
                            $orderItem['internal_item']['stock_reference_code'] = $stockReference['code'];
                        }

                        unset($orderItem['internal_item']['stock_on_hand'], $orderItem['internal_item']['stock_allocated']);
                    }
                }
                unset($orderItem);
            }

            // Gunakan angka voucher dari settlement hasil Escrow Detail Shopee.
            // Field ini menormalisasi sumber voucher seller/platform, termasuk
            // response escrow yang hanya menyimpan nilainya di raw_json.
            if ($o->settlement) {
                $arr['settlement']['voucher_toko_total'] = $this->settlementVoucherTokoAmount($o->settlement);
                $arr['settlement']['voucher_platform_total'] = $this->settlementVoucherPlatformAmount($o->settlement);
                $arr['settlement']['voucher_external_total'] = $this->settlementVoucherExternalAmount($o->settlement);
                $arr['settlement']['bundle_discount_total'] = $this->settlementBundleDiscountAmount($o->settlement);
                $arr['settlement']['escrow_amount'] = $this->settlementEscrowAmount($o->settlement);
                $arr['settlement']['order_selling_price'] = $this->settlementOrderSellingPrice($o->settlement);
                $arr['settlement']['estimated_escrow_amount'] = $o->incomeEstimate?->estimated_escrow_amount !== null
                    ? (float) $o->incomeEstimate->estimated_escrow_amount
                    : null;
                $arr['settlement']['ams_total'] = $this->settlementAmsAmount($o->settlement);
            }

            // Bedakan "voucher nol" (data valid dari Shopee) dengan "escrow
            // belum masuk" agar operator tidak menganggap data yang masih
            // antre sebagai nominal nol. Alert muncul setelah 30 menit.
            $eligibleForEscrow = in_array($o->order_status, [
                'READY_TO_SHIP', 'MATCHED', 'SHIPPED', 'TO_CONFIRM_RECEIVE',
                'TO_RETURN', 'RETURNING', 'COMPLETED',
            ], true);
            $ageMinutes = $o->ordered_at ? max(0, (int) $o->ordered_at->diffInMinutes(now())) : null;
            $errorCode = $o->settlement_sync_error_code;
            $retryableErrors = [
                'connection_exception', 'rate_limit', 'rate_limited',
                'rate_limit_cooldown', 'server_error', 'service_unavailable', 'timeout',
            ];
            $escrowState = ! $eligibleForEscrow
                ? 'not_required'
                : ($o->settlement
                    ? 'synced'
                    : ($errorCode && ! in_array($errorCode, $retryableErrors, true)
                        ? 'failed'
                        : ($ageMinutes !== null && $ageMinutes >= 30
                            ? 'overdue'
                            : ($o->settlement_sync_last_attempt_at ? 'retrying' : 'queued'))));
            $arr['escrow_sync'] = [
                'state' => $escrowState,
                'age_minutes' => $ageMinutes,
                'last_attempt_at' => $o->settlement_sync_last_attempt_at?->toISOString(),
                'error_code' => $errorCode,
            ];
            
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

    /**
     * Riwayat order selesai dari pembeli yang sama di toko yang sama.
     * Identitas selalu diambil dari order saat ini agar tidak bisa diminta
     * berdasarkan username pembeli secara bebas dari browser.
     */
    public function buyerCompletedOrders(MarketplaceOrder $order): JsonResponse
    {
        $username = trim((string) ($order->buyer_username ?? ''));
        $name = trim((string) ($order->buyer_name ?? ''));

        if ($username === '' && $name === '') {
            return response()->json([
                'data' => [],
                'buyer_label' => null,
            ]);
        }

        $identityColumn = $username !== '' ? 'buyer_username' : 'buyer_name';
        $identityValue = $username !== '' ? $username : $name;
        $orders = MarketplaceOrder::query()
            ->where('store_id', $order->store_id)
            ->where('order_status', 'COMPLETED')
            ->whereKeyNot($order->getKey())
            ->where($identityColumn, $identityValue)
            ->with([
                'items.internalItem:id,code,name',
            ])
            ->latest('ordered_at')
            ->limit(20)
            ->get()
            ->map(function (MarketplaceOrder $completedOrder): array {
                return [
                    'id' => $completedOrder->id,
                    'order_sn' => $completedOrder->channel_order_id ?: $completedOrder->external_order_id,
                    'ordered_at' => $completedOrder->ordered_at?->toIso8601String(),
                    'status' => $completedOrder->order_status,
                    'items' => $completedOrder->items->map(fn ($item): array => [
                        'qty' => (int) ($item->qty ?: 1),
                        'code' => $item->internalItem?->code ?: ($item->model_sku ?: $item->item_sku),
                        'name' => $item->internalItem?->name ?: ($item->variant_name ?: $item->item_name),
                    ])->all(),
                ];
            })
            ->all();

        return response()->json([
            'data' => $orders,
            'buyer_label' => $identityValue,
        ]);
    }

    /**
     * Hitung order selesai per pembeli di toko yang sama. Query dilakukan
     * per halaman secara agregat agar UI bisa menandai repeat order tanpa N+1.
     *
     * @param \Illuminate\Support\Collection<int,MarketplaceOrder> $orders
     * @return array<string,int>
     */
    private function buyerPurchaseCounts($orders): array
    {
        $identities = $orders
            ->map(function (MarketplaceOrder $order): ?array {
                $username = trim((string) ($order->buyer_username ?? ''));
                if ($username !== '') {
                    return [
                        'type' => 'username',
                        'store_id' => (int) $order->store_id,
                        'value' => $username,
                    ];
                }

                $name = trim((string) ($order->buyer_name ?? ''));
                if ($name === '') {
                    return null;
                }

                return [
                    'type' => 'name',
                    'store_id' => (int) $order->store_id,
                    'value' => $name,
                ];
            })
            ->filter()
            ->values();

        $counts = [];

        foreach (['username', 'name'] as $type) {
            $groups = $identities
                ->where('type', $type)
                ->groupBy('store_id');

            foreach ($groups as $storeId => $rows) {
                $values = $rows->pluck('value')->unique()->values()->all();
                if ($values === []) {
                    continue;
                }

                $column = $type === 'username' ? 'buyer_username' : 'buyer_name';
                $result = MarketplaceOrder::query()
                    ->where('store_id', (int) $storeId)
                    ->where('order_status', 'COMPLETED')
                    ->whereNotNull($column)
                    ->where($column, '!=', '')
                    ->whereIn($column, $values)
                    ->select($column)
                    ->selectRaw('COUNT(*) AS purchase_count')
                    ->groupBy($column)
                    ->get();

                foreach ($result as $row) {
                    $normalized = mb_strtolower(trim((string) $row->{$column}));
                    $key = ((int) $storeId).'|'.$type.'|'.$normalized;
                    $counts[$key] = (int) $row->purchase_count;
                }
            }
        }

        return $counts;
    }

    private function buyerIdentityKey(MarketplaceOrder $order): ?string
    {
        $username = trim((string) ($order->buyer_username ?? ''));
        if ($username !== '') {
            return ((int) $order->store_id).'|username|'.mb_strtolower($username);
        }

        $name = trim((string) ($order->buyer_name ?? ''));
        if ($name !== '') {
            return ((int) $order->store_id).'|name|'.mb_strtolower($name);
        }

        return null;
    }

    private function isCountableBuyerOrder(MarketplaceOrder $order): bool
    {
        return strtoupper((string) $order->order_status) === 'COMPLETED';
    }

    /**
     * Varian internal dengan akhiran angka (mis. S2RDM-2) memakai stok item
     * induknya (S2RDM), bila item induk tersebut tersedia.
     *
     * @param \Illuminate\Support\Collection<int,MarketplaceOrder> $orders
     * @return array<string,array{code:string,stock_available:float,stock_unit:string}>
     */
    private function baseStockReferences($orders): array
    {
        $codes = $orders
            ->flatMap(fn (MarketplaceOrder $order) => $order->items
                ->map(fn ($item) => $this->baseStockReferenceCode($item->internalItem?->code)))
            ->filter()
            ->unique()
            ->values();

        if ($codes->isEmpty()) {
            return [];
        }

        return Item::query()
            ->whereIn('code', $codes->all())
            ->select('id', 'code', 'unit', 'stock_unit')
            ->withSum('inventoryStocks as stock_on_hand', 'qty')
            ->withSum('inventoryStocks as stock_allocated', 'allocated_qty')
            ->get()
            ->mapWithKeys(function (Item $item): array {
                $code = trim((string) $item->code);

                return [mb_strtoupper($code) => [
                    'code' => $code,
                    'stock_available' => (float) ($item->stock_on_hand ?? 0) - (float) ($item->stock_allocated ?? 0),
                    'stock_unit' => trim((string) ($item->stock_unit ?: $item->unit ?: 'pcs')),
                ]];
            })
            ->all();
    }

    private function baseStockReferenceCode(?string $code): ?string
    {
        $code = trim((string) $code);

        return preg_match('/^(.+)-\d+$/', $code, $matches) && trim($matches[1]) !== ''
            ? trim($matches[1])
            : null;
    }

    private function excludeKilatOrders($query): void
    {
        $query->where(function ($q) {
            $q->whereNull('booking_sn')
                ->orWhere('booking_sn', '')
                ->orWhere('shipping_carrier', 'like', '%instant%')
                ->orWhere('shipping_carrier', 'like', '%same day%')
                ->orWhere('shipping_carrier', 'like', '%sameday%');
        });
    }
}
