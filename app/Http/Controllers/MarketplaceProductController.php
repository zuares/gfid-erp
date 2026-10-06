<?php

namespace App\Http\Controllers;

use App\Models\MarketplaceProduct;
use App\Models\Store;
use App\Services\MarketplaceProductService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class MarketplaceProductController extends Controller
{
    public function __construct(protected MarketplaceProductService $products) {}

    public function page()
    {
        return view('marketplace.products');
    }

    /**
     * Daftar produk lokal + filter.
     */
    public function index(Request $request)
    {
        $q = MarketplaceProduct::with(['store:id,name', 'models'])
            ->when($request->filled('store_id'), fn ($qq) => $qq->where('store_id', $request->integer('store_id')))
            ->when($request->filled('status'), fn ($qq) => $qq->where('item_status', $request->string('status')))
            ->when($request->filled('search'), function ($qq) use ($request) {
                $s = '%' . $request->string('search') . '%';
                $qq->where(fn ($w) => $w->where('item_name', 'like', $s)
                    ->orWhere('item_sku', 'like', $s)
                    ->orWhere('item_id', 'like', $s)
                    ->orWhereHas('models', fn ($m) => $m->where('model_sku', 'like', $s)));
            })
            ->orderByDesc('synced_at');

        $products = $q->limit(500)->get();

        // ── Status mapping per model (dipakai juga oleh sync order) ─────────
        $skus = $products->flatMap(fn ($p) => $p->models->pluck('model_sku')->push($p->item_sku))
            ->filter()->map(fn ($sku) => trim((string) $sku))->unique()->values();

        $mappings = \App\Models\SkuMapping::whereIn('marketplace_sku', $skus)
            ->where(fn ($w) => $w->whereNull('channel_code')->orWhereRaw('LOWER(channel_code) = ?', ['shopee']))
            ->with(['item:id,code,name,item_category_id', 'item.category:id,code,name'])
            ->get()
            // channel-spesifik menang atas global
            ->sortBy(fn ($m) => $m->channel_code === null ? 1 : 0)
            ->keyBy(fn ($mapping) => mb_strtolower(trim((string) $mapping->marketplace_sku)));

        // Stok gudang internal per item (fisik = SUM qty, tersedia = fisik - dialokasi).
        $mappedItemIds = $mappings->pluck('item_id')->filter()->unique()->values();
        $internalStocks = \DB::table('inventory_stocks')
            ->whereIn('item_id', $mappedItemIds)
            ->selectRaw('item_id, COALESCE(SUM(qty),0) as physical, COALESCE(SUM(allocated_qty),0) as allocated')
            ->groupBy('item_id')
            ->get()
            ->keyBy('item_id');

        $internalFor = function ($mapping) use ($internalStocks): array {
            $s = $mapping ? $internalStocks->get($mapping->item_id) : null;
            $physical  = (float) ($s->physical ?? 0);
            $allocated = (float) ($s->allocated ?? 0);
            return [
                'internal_physical'  => $physical,
                'internal_allocated' => $allocated,
                'internal_available' => $physical - $allocated,
            ];
        };

        foreach ($products as $p) {
            $parentMapping = $p->item_sku
                ? ($mappings[mb_strtolower(trim((string) $p->item_sku))] ?? null)
                : null;
            $p->setAttribute('mapping', $parentMapping ? array_merge([
                'id'            => $parentMapping->id,
                'item_id'       => $parentMapping->item_id,
                'item_code'     => $parentMapping->item?->code,
                'item_name'     => $parentMapping->item?->name,
                'category_id'   => $parentMapping->item?->item_category_id,
                'category_name' => $parentMapping->item?->category?->name,
            ], $internalFor($parentMapping)) : null);
            foreach ($p->models as $m) {
                $map = $m->model_sku
                    ? ($mappings[mb_strtolower(trim((string) $m->model_sku))] ?? null)
                    : null;
                $m->setAttribute('mapping', $map ? array_merge([
                    'id'            => $map->id,
                    'item_id'       => $map->item_id,
                    'item_code'     => $map->item?->code,
                    'item_name'     => $map->item?->name,
                    'category_id'   => $map->item?->item_category_id,
                    'category_name' => $map->item?->category?->name,
                ], $internalFor($map)) : null);
            }
        }

        $this->attachFinancialMetrics($products);

        return response()->json($products);
    }

    /**
     * Tambahkan metrik transaksi ke katalog produk.
     *
     * Pembayaran pembeli adalah nilai order-level, sehingga dialokasikan ke
     * baris produk berdasarkan nilai line item. Sisa promosi order-level
     * (voucher) memakai pembagian yang sama, sementara diskon produk tetap
     * dicatat langsung di line item agar tidak hilang atau terhitung dua kali.
     */
    private function attachFinancialMetrics(Collection $products): void
    {
        if ($products->isEmpty()) {
            return;
        }

        $itemIds = $products->pluck('item_id')
            ->filter(fn ($value) => trim((string) $value) !== '')
            ->map(fn ($value) => trim((string) $value))
            ->unique()
            ->values()
            ->all();
        $skuValues = $products
            ->flatMap(fn ($product) => $product->models
                ->pluck('model_sku')
                ->push($product->item_sku))
            ->filter(fn ($value) => trim((string) $value) !== '')
            ->map(fn ($value) => trim((string) $value))
            ->unique()
            ->values()
            ->all();

        $productsByItem = [];
        $productsBySku = [];
        foreach ($products as $product) {
            $storeKey = (string) $product->store_id;
            $productsByItem[$storeKey . '|' . trim((string) $product->item_id)] = $product->id;

            $skus = $product->models->pluck('model_sku')->push($product->item_sku);
            foreach ($skus as $sku) {
                $sku = mb_strtolower(trim((string) $sku));
                if ($sku !== '') {
                    $productsBySku[$storeKey . '|' . $sku] = $product->id;
                }
            }
        }

        $metrics = $products->mapWithKeys(fn ($product) => [$product->id => [
            'total_promotion' => 0.0,
            'buyer_payment' => 0.0,
        ]])->all();

        $itemsQuery = DB::table('marketplace_order_items as oi')
            ->join('marketplace_orders as o', function ($join) {
                $join->on(DB::raw('COALESCE(oi.marketplace_order_id, oi.order_id)'), '=', 'o.id');
            })
            ->where(function ($query) use ($itemIds, $skuValues) {
                if ($itemIds !== []) {
                    $query->whereIn('oi.external_item_id', $itemIds);
                }
                if ($skuValues !== []) {
                    $method = $itemIds !== [] ? 'orWhereIn' : 'whereIn';
                    $query->{$method}('oi.item_sku', $skuValues)
                        ->orWhereIn('oi.model_sku', $skuValues)
                        ->orWhereIn('oi.marketplace_sku', $skuValues)
                        ->orWhereIn('oi.external_sku', $skuValues);
                }
            });

        if ($itemIds === [] && $skuValues === []) {
            return;
        }

        $matchedItems = $itemsQuery
            ->whereRaw("UPPER(COALESCE(NULLIF(o.order_status, ''), NULLIF(o.status, ''), '')) NOT IN (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)", [
                'UNPAID', 'INVOICE_PENDING', 'CANCELLED', 'CANCELED',
                'CANCELLED_BEFORE_SHIPPING', 'BATAL', 'IN_CANCEL', 'TO_RETURN',
                'RETURNING', 'RETURNED', 'REFUND', 'REFUNDED',
            ])
            ->select([
                'oi.id',
                'oi.marketplace_order_id',
                'oi.order_id',
                'oi.external_item_id',
                'oi.item_sku',
                'oi.model_sku',
                'oi.marketplace_sku',
                'oi.external_sku',
                'oi.qty',
                'oi.price',
                'oi.price_original',
                'oi.price_after_discount',
                'oi.line_discount',
                'oi.line_gross_amount',
                'oi.line_net_amount',
                'o.store_id',
            ])
            ->get();

        if ($matchedItems->isEmpty()) {
            foreach ($products as $product) {
                $product->setAttribute('total_promotion', 0.0);
                $product->setAttribute('buyer_payment', 0.0);
            }
            return;
        }

        $orderIds = $matchedItems
            ->map(fn ($item) => (int) ($item->marketplace_order_id ?: $item->order_id))
            ->filter()
            ->unique()
            ->values()
            ->all();

        $allItems = DB::table('marketplace_order_items')
            ->whereIn(DB::raw('COALESCE(marketplace_order_id, order_id)'), $orderIds)
            ->get([
                'id', 'marketplace_order_id', 'order_id', 'qty', 'price',
                'price_original', 'price_after_discount', 'line_discount',
                'line_gross_amount', 'line_net_amount',
            ])
            ->groupBy(fn ($item) => (int) ($item->marketplace_order_id ?: $item->order_id));

        $orders = DB::table('marketplace_orders as o')
            ->leftJoin('marketplace_order_settlements as s', 's.order_id', '=', 'o.id')
            ->whereIn('o.id', $orderIds)
            ->get([
                'o.id', 'o.voucher_discount', 'o.other_discount',
                'o.total_paid_customer', 'o.total_amount', 'o.subtotal_items',
                'o.raw_json as order_raw_json', 's.id as settlement_id',
                's.buyer_payment_amount', 's.seller_voucher', 's.raw_json as settlement_raw_json',
            ])
            ->keyBy('id');

        $matchedItems->groupBy(fn ($item) => (int) ($item->marketplace_order_id ?: $item->order_id))
            ->each(function (Collection $orderItems, $orderId) use (&$metrics, $allItems, $orders, $productsByItem, $productsBySku) {
                $order = $orders->get($orderId);
                if (!$order) {
                    return;
                }

                $sourceItems = $allItems->get($orderId, collect());
                $lineTotals = $sourceItems->mapWithKeys(fn ($item) => [
                    $item->id => $this->productLineValue($item),
                ]);
                $orderLineValue = (float) $lineTotals->sum();
                $orderQty = max(0, (float) $sourceItems->sum(fn ($item) => max(0, (float) ($item->qty ?? 0))));
                $linePromotionTotal = (float) $sourceItems->sum(fn ($item) => $this->productLinePromotion($item));
                $promotionTotal = $this->orderPromotionTotal($order, $linePromotionTotal);
                $promotionRemainder = max(0, $promotionTotal - $linePromotionTotal);
                $buyerPayment = $this->buyerPaymentTotal($order);

                $orderItems->each(function ($item) use (&$metrics, $orderLineValue, $orderQty, $promotionRemainder, $buyerPayment, $productsByItem, $productsBySku) {
                    $storeKey = (string) $item->store_id;
                    $productId = $productsByItem[$storeKey . '|' . trim((string) $item->external_item_id)] ?? null;
                    if (!$productId) {
                        foreach ([$item->model_sku, $item->item_sku, $item->marketplace_sku, $item->external_sku] as $sku) {
                            $productId = $productsBySku[$storeKey . '|' . mb_strtolower(trim((string) $sku))] ?? null;
                            if ($productId) {
                                break;
                            }
                        }
                    }
                    if (!$productId || !isset($metrics[$productId])) {
                        return;
                    }

                    $lineValue = $this->productLineValue($item);
                    $share = $orderLineValue > 0
                        ? $lineValue / $orderLineValue
                        : ($orderQty > 0 ? max(0, (float) ($item->qty ?? 0)) / $orderQty : 0);
                    $metrics[$productId]['total_promotion'] += $this->productLinePromotion($item) + ($promotionRemainder * $share);
                    $metrics[$productId]['buyer_payment'] += $buyerPayment * $share;
                });
            });

        foreach ($products as $product) {
            $metric = $metrics[$product->id] ?? ['total_promotion' => 0, 'buyer_payment' => 0];
            $product->setAttribute('total_promotion', round((float) $metric['total_promotion'], 2));
            $product->setAttribute('buyer_payment', round((float) $metric['buyer_payment'], 2));
        }
    }

    private function productLineValue(object $item): float
    {
        $qty = max(0, (float) ($item->qty ?? 0));
        foreach ([
            (float) ($item->line_net_amount ?? 0),
            (float) ($item->price ?? 0) * $qty,
            (float) ($item->line_gross_amount ?? 0),
            (float) ($item->price_after_discount ?? 0) * $qty,
        ] as $value) {
            if ($value > 0) {
                return $value;
            }
        }

        return 0.0;
    }

    private function productLinePromotion(object $item): float
    {
        $lineDiscount = max(0, (float) ($item->line_discount ?? 0));
        if ($lineDiscount > 0) {
            return $lineDiscount;
        }

        $priceGap = max(0, (float) ($item->price_original ?? 0) - (float) ($item->price_after_discount ?? 0));
        return $priceGap * max(0, (float) ($item->qty ?? 0));
    }

    private function buyerPaymentTotal(object $order): float
    {
        foreach ([
            (float) ($order->buyer_payment_amount ?? 0),
            (float) ($order->total_paid_customer ?? 0),
            (float) ($order->total_amount ?? 0),
            (float) ($order->subtotal_items ?? 0),
        ] as $value) {
            if ($value > 0) {
                return $value;
            }
        }

        return 0.0;
    }

    private function orderPromotionTotal(object $order, float $linePromotionTotal): float
    {
        $settlementRaw = $this->decodePayload($order->settlement_raw_json ?? null);
        $orderRaw = $this->decodePayload($order->order_raw_json ?? null);

        if ($order->settlement_id !== null) {
            $voucherStore = array_key_exists('voucher_from_seller', $settlementRaw)
                ? (float) $settlementRaw['voucher_from_seller']
                : (float) ($order->seller_voucher ?? 0);
            $voucherPlatform = array_key_exists('voucher_from_shopee', $settlementRaw)
                ? (float) $settlementRaw['voucher_from_shopee']
                : 0.0;
            $itemPromotion = 0.0;
            foreach ((array) ($settlementRaw['items'] ?? ($orderRaw['item_list'] ?? [])) as $item) {
                if (!is_array($item)) {
                    continue;
                }
                $reported = max(0, (float) ($item['seller_discount'] ?? 0));
                if ($reported <= 0) {
                    $reported = max(0, (float) ($item['original_price'] ?? $item['model_original_price'] ?? 0)
                        - (float) ($item['discounted_price'] ?? $item['model_discounted_price'] ?? 0));
                    $reported *= max(1, (int) ($item['quantity_purchased'] ?? $item['model_quantity_purchased'] ?? 1));
                }
                $itemPromotion += $reported;
            }
            if ($itemPromotion <= 0) {
                $itemPromotion = max(0, (float) ($settlementRaw['seller_discount'] ?? 0));
            }

            return max($linePromotionTotal, $itemPromotion) + $voucherStore + $voucherPlatform;
        }

        return $linePromotionTotal
            + max(0, (float) ($order->voucher_discount ?? 0))
            + max(0, (float) ($order->other_discount ?? 0));
    }

    private function decodePayload(mixed $payload): array
    {
        if (is_array($payload)) {
            return $payload;
        }
        if (is_string($payload)) {
            $decoded = json_decode($payload, true);
            return is_array($decoded) ? $decoded : [];
        }
        return [];
    }

    /**
     * Auto-map: model_sku yang persis sama dengan kode item internal
     * dibuatkan mapping global otomatis.
     */
    public function autoMap()
    {
        $unmappedSkus = \App\Models\MarketplaceProductModel::whereNotNull('model_sku')
            ->where('model_sku', '!=', '')
            ->pluck('model_sku')
            ->merge(\App\Models\MarketplaceProduct::whereNotNull('item_sku')->pluck('item_sku'))
            ->filter()
            ->map(fn ($sku) => trim((string) $sku))
            ->unique()
            ->reject(fn ($sku) => \App\Models\SkuMapping::where('marketplace_sku', $sku)->exists())
            ->values();

        $created = 0;
        foreach ($unmappedSkus as $sku) {
            $item = \App\Models\Item::where('code', $sku)->where('active', 1)->first();
            if ($item) {
                \App\Models\SkuMapping::create([
                    'marketplace_sku' => $sku,
                    'channel_code'    => null, // global — berlaku semua channel
                    'item_id'         => $item->id,
                    'notes'           => 'auto-map dari tab Produk (kode sama persis)',
                ]);
                $created++;
            }
        }

        return response()->json([
            'created' => $created,
            'message' => $created > 0
                ? "Berhasil auto-map {$created} SKU (kode persis sama)."
                : 'Tidak ada SKU yang cocok persis dengan kode item internal.',
        ]);
    }

    /**
     * Sync produk dari Shopee (semua toko aktif atau satu toko).
     */
    public function sync(Request $request)
    {
        $stores = Store::whereHas('channel', fn ($q) => $q->whereIn('code', ['SHOPEE', 'SHP', 'shopee']))
            ->where('status', 'active')
            ->when($request->filled('store_id'), fn ($q) => $q->where('id', $request->integer('store_id')))
            ->get();

        $total = 0;
        foreach ($stores as $store) {
            \App\Jobs\Marketplace\SyncMarketplaceProductsJob::dispatch($store);
            $total++;
        }

        return response()->json([
            'message' => "Proses sinkronisasi sedang berjalan di latar belakang (background) untuk {$total} toko. Silakan refresh halaman dalam beberapa menit.",
        ]);
    }

    /**
     * Update stok. Body: stock_list: [{model_id, stock}]
     */
    public function updateStock(MarketplaceProduct $product, Request $request)
    {
        $data = $request->validate([
            'stock_list'            => 'required|array|min:1',
            'stock_list.*.model_id' => 'required',
            'stock_list.*.stock'    => 'required|integer|min:0',
        ]);

        $res = $this->products->updateStock($product, $data['stock_list']);

        if (! empty($res['error'])) {
            return response()->json(['message' => $res['message'] ?? 'Gagal update stok di Shopee.'], 422);
        }

        return response()->json(['success' => true]);
    }

    /**
     * Update harga. Body: price_list: [{model_id, original_price}]
     */
    public function updatePrice(MarketplaceProduct $product, Request $request)
    {
        $data = $request->validate([
            'price_list'                  => 'required|array|min:1',
            'price_list.*.model_id'       => 'required',
            'price_list.*.original_price' => 'required|numeric|min:100',
        ]);

        $res = $this->products->updatePrice($product, $data['price_list']);

        if (! empty($res['error'])) {
            return response()->json(['message' => $res['message'] ?? 'Gagal update harga di Shopee.'], 422);
        }

        return response()->json(['success' => true]);
    }

    public function updateSku(MarketplaceProduct $product, Request $request)
    {
        $data = $request->validate([
            'new_sku' => 'nullable|string|max:100',
        ]);
        try {
            $this->products->updateSku($product, $data['new_sku'] ?? '');
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function updateModelSku(MarketplaceProduct $product, Request $request)
    {
        $data = $request->validate([
            'model_id' => 'required|numeric',
            'new_sku'  => 'nullable|string|max:100',
        ]);
        try {
            $model = $product->models()->where('model_id', $data['model_id'])->firstOrFail();
            $this->products->updateModelSku($model, $data['new_sku'] ?? '');
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /**
     * Riwayat harian sebuah produk (mesin waktu: stok, harga, terjual).
     */
    public function history(MarketplaceProduct $product, Request $request)
    {
        $days = min(365, max(7, (int) $request->input('days', 90)));

        $rows = \App\Models\MarketplaceProductDaily::where('marketplace_product_id', $product->id)
            ->where('date', '>=', now()->subDays($days)->toDateString())
            ->orderBy('date')
            ->get(['date', 'item_status', 'price_min', 'price_max', 'stock_total', 'sales', 'sales_delta', 'views', 'rating_star']);

        return response()->json([
            'product' => ['id' => $product->id, 'name' => $product->item_name, 'sku' => $product->item_sku],
            'days'    => $rows,
        ]);
    }

    /**
     * Pesanan yang memiliki item dari produk marketplace tertentu.
     * Satu pesanan dikembalikan satu kali walaupun membeli beberapa varian.
     */
    public function orders(MarketplaceProduct $product, Request $request)
    {
        $skuValues = $product->models()
            ->pluck('model_sku')
            ->push($product->item_sku)
            ->filter(fn ($value) => trim((string) $value) !== '')
            ->map(fn ($value) => trim((string) $value))
            ->unique()
            ->values()
            ->all();

        $items = DB::table('marketplace_order_items as oi')
            ->join('marketplace_orders as o', function ($join) {
                $join->on(DB::raw('COALESCE(oi.marketplace_order_id, oi.order_id)'), '=', 'o.id');
            })
            ->leftJoin('marketplace_order_settlements as s', 's.order_id', '=', 'o.id')
            ->where('o.store_id', $product->store_id)
            ->where(function ($query) use ($product, $skuValues) {
                $query->where('oi.external_item_id', (string) $product->item_id);
                if ($skuValues !== []) {
                    $query->orWhereIn('oi.item_sku', $skuValues)
                        ->orWhereIn('oi.model_sku', $skuValues)
                        ->orWhereIn('oi.marketplace_sku', $skuValues)
                        ->orWhereIn('oi.external_sku', $skuValues);
                }
            })
            ->whereRaw("UPPER(COALESCE(NULLIF(o.order_status, ''), NULLIF(o.status, ''), '')) NOT IN (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)", [
                'UNPAID', 'INVOICE_PENDING', 'CANCELLED', 'CANCELED',
                'CANCELLED_BEFORE_SHIPPING', 'BATAL', 'IN_CANCEL', 'TO_RETURN',
                'RETURNING', 'RETURNED', 'REFUND', 'REFUNDED',
            ])
            ->select([
                'o.id',
                'o.channel_order_id',
                'o.external_order_id',
                'o.buyer_username',
                'o.buyer_name',
                'o.payment_status',
                'o.order_status',
                'o.status',
                'o.ordered_at',
                'o.order_date',
                'o.total_paid_customer',
                'o.total_amount',
                'o.subtotal_items',
                's.buyer_payment_amount',
                'oi.qty',
                'oi.price',
                'oi.price_after_discount',
                'oi.line_net_amount',
                'oi.line_gross_amount',
            ])
            ->orderByDesc(DB::raw('COALESCE(o.ordered_at, o.order_date)'))
            ->orderByDesc('o.id')
            ->limit(min(200, max(1, (int) $request->input('limit', 100))))
            ->get();

        $orders = $items
            ->groupBy('id')
            ->map(function (Collection $rows) {
                $first = $rows->first();
                $buyerPayment = $this->buyerPaymentTotal($first);
                $lineTotal = (float) $rows->sum(fn ($row) => $this->productLineValue($row));

                return [
                    'id' => (int) $first->id,
                    'order_number' => $first->channel_order_id ?: ($first->external_order_id ?: ('#' . $first->id)),
                    'ordered_at' => $first->ordered_at ?: $first->order_date,
                    'buyer' => $first->buyer_username ?: ($first->buyer_name ?: 'Pelanggan marketplace'),
                    'qty' => (int) $rows->sum(fn ($row) => max(0, (int) ($row->qty ?? 0))),
                    'line_total' => round($lineTotal, 2),
                    'buyer_payment' => round($buyerPayment, 2),
                    'payment_status' => strtoupper((string) ($first->payment_status ?: 'BELUM DITENTUKAN')),
                    'order_status' => strtoupper((string) ($first->order_status ?: $first->status ?: 'BELUM DITENTUKAN')),
                ];
            })
            ->sortByDesc(fn ($row) => (string) ($row['ordered_at'] ?? ''))
            ->values();

        return response()->json([
            'product' => [
                'id' => $product->id,
                'name' => $product->item_name,
                'sku' => $product->item_sku,
            ],
            'total' => $orders->count(),
            'orders' => $orders,
        ]);
    }

    /**
     * Toggle tampil/sembunyi (unlist).
     */
    public function toggleUnlist(MarketplaceProduct $product, Request $request)
    {
        $unlist = $request->boolean('unlist');
        $res = $this->products->setUnlist($product, $unlist);

        if (! empty($res['error'])) {
            return response()->json(['message' => $res['message'] ?? 'Gagal ubah status tampil.'], 422);
        }

        return response()->json(['success' => true, 'item_status' => $product->fresh()->item_status]);
    }
}
