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

    public function createOptions()
    {
        $stores = Store::with('channel:id,code,name')
            ->whereHas('channel', fn ($q) => $q->whereIn('code', ['SHOPEE', 'SHP', 'shopee', 'shp', 'TIKTOK', 'TTK', 'TT', 'tiktok', 'ttk', 'tt']))
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'channel_id'])
            ->map(function (Store $store): array {
                $channel = strtolower((string) $store->channel?->code);
                $channel = in_array($channel, ['tt', 'ttk'], true) ? 'tiktok' : 'shopee';

                return [
                    'id' => $store->id,
                    'name' => $store->name,
                    'code' => $store->code,
                    'channel' => $channel,
                    'channel_name' => $channel === 'tiktok' ? 'TikTok Shop' : 'Shopee',
                ];
            })->values();

        return response()->json([
            'stores' => $stores,
            'steps' => ['store_category', 'attributes', 'content', 'images_logistics', 'preview'],
        ]);
    }

    public function createCategories(Request $request)
    {
        $store = $this->createStore($request);
        $result = $this->products->getCreateCategories($store);

        return $this->marketplaceResult($result, 'Kategori marketplace gagal diambil.');
    }

    public function createAttributes(Request $request)
    {
        $data = $request->validate(['category_id' => ['required', 'regex:/^[0-9]{1,30}$/']]);
        $store = $this->createStore($request);
        $result = $this->products->getCreateAttributes($store, (string) $data['category_id']);

        return $this->marketplaceResult($result, 'Atribut kategori marketplace gagal diambil.');
    }

    public function createLogistics(Request $request)
    {
        $store = $this->createStore($request);
        $result = $this->products->getCreateLogistics($store);

        return $this->marketplaceResult($result, 'Data logistik marketplace gagal diambil.');
    }

    public function uploadCreateImages(Request $request)
    {
        $data = $request->validate([
            'store_id' => 'required|integer|exists:stores,id',
            'images' => 'required|array|min:1|max:9',
            'images.*' => 'required|file|image|mimes:jpg,jpeg,png|max:5120',
        ]);
        $store = $this->createStore($request);
        $result = $this->products->uploadImagesForStore($store, array_values(array_filter($data['images'] ?? [])));

        return $this->marketplaceResult($result, 'Gambar produk baru gagal di-upload.');
    }

    public function publishCreate(Request $request)
    {
        $store = $this->createStore($request);
        if ($this->isTikTokStore($store)) {
            return $this->publishTikTokCreate($request, $store);
        }

        $data = $request->validate([
            'store_id' => 'required|integer|exists:stores,id',
            'item_name' => 'required|string|max:255',
            'item_sku' => 'nullable|string|max:100',
            'description' => 'required|string|max:3000',
            'category_id' => 'required|integer|min:1',
            'image_ids' => 'required|array|min:1|max:9',
            'image_ids.*' => 'required|string|max:160',
            'original_price' => 'required|numeric|min:100',
            'stock' => 'required|integer|min:0',
            'weight' => 'required|numeric|gt:0',
            'dimension' => 'required|array',
            'dimension.package_length' => 'required|integer|min:1',
            'dimension.package_width' => 'required|integer|min:1',
            'dimension.package_height' => 'required|integer|min:1',
            'logistic_info' => 'required|array|min:1',
            'logistic_info.*.logistic_id' => 'required|integer|min:1',
            'logistic_info.*.enabled' => 'required|boolean',
            'logistic_info.*.is_free' => 'nullable|boolean',
            'logistic_info.*.shipping_fee' => 'nullable|numeric|min:0',
            'attribute_list' => 'nullable|array',
            'pre_order' => 'nullable|array',
            'pre_order.is_pre_order' => 'nullable|boolean',
            'pre_order.days_to_ship' => 'nullable|integer|min:1',
            'item_status' => 'nullable|in:NORMAL,UNLIST',
        ]);

        $payload = [
            'item_name' => $data['item_name'],
            'description' => $data['description'],
            'category_id' => (int) $data['category_id'],
            'original_price' => (float) $data['original_price'],
            'weight' => (float) $data['weight'],
            'image' => ['image_id_list' => array_values($data['image_ids'])],
            'seller_stock' => [['stock' => (int) $data['stock']]],
            'logistic_info' => array_values($data['logistic_info']),
            'item_status' => $data['item_status'] ?? 'UNLIST',
        ];

        foreach (['item_sku', 'dimension', 'attribute_list', 'pre_order'] as $key) {
            if (array_key_exists($key, $data) && $data[$key] !== null && $data[$key] !== '') {
                $payload[$key] = $data[$key];
            }
        }

        $result = $this->products->createProduct($store, $payload);

        return $this->marketplaceResult($result, 'Produk baru gagal dipublish ke Shopee.');
    }

    private function publishTikTokCreate(Request $request, Store $store)
    {
        $data = $request->validate([
            'item_name' => 'required|string|max:255',
            'item_sku' => ['required', 'string', 'max:100', 'regex:/^[A-Za-z0-9._-]+$/'],
            'description' => 'required|string|max:10000',
            'category_id' => ['required', 'regex:/^[0-9]{1,30}$/'],
            'image_ids' => 'required|array|min:1|max:9',
            'image_ids.*' => 'required|string|max:500',
            'original_price' => 'required|numeric|min:100',
            'stock' => 'required|integer|min:0',
            'weight' => 'required|numeric|gt:0',
            'dimension' => 'required|array',
            'dimension.package_length' => 'required|numeric|gt:0',
            'dimension.package_width' => 'required|numeric|gt:0',
            'dimension.package_height' => 'required|numeric|gt:0',
            'warehouse_id' => 'required|string|max:80',
            'attribute_list' => 'nullable|array',
            'attribute_list.*.attribute_id' => 'required|string|max:30',
            'attribute_list.*.attribute_value_list' => 'required|array|min:1',
            'attribute_list.*.attribute_value_list.*.value_id' => 'nullable|string|max:30',
            'attribute_list.*.attribute_value_list.*.original_value_name' => 'required|string|max:500',
            'save_mode' => 'nullable|in:AS_DRAFT,LISTING',
            'idempotency_key' => 'required|uuid',
        ]);

        $attributes = collect($data['attribute_list'] ?? [])->map(function (array $attribute): array {
            return [
                'id' => (string) $attribute['attribute_id'],
                'values' => collect($attribute['attribute_value_list'])->map(function (array $value): array {
                    return array_filter([
                        'id' => filled($value['value_id'] ?? null) && (string) $value['value_id'] !== '0'
                            ? (string) $value['value_id']
                            : null,
                        'name' => (string) $value['original_value_name'],
                    ], static fn ($item) => $item !== null);
                })->values()->all(),
            ];
        })->values()->all();

        $payload = [
            'save_mode' => $data['save_mode'] ?? 'AS_DRAFT',
            'idempotency_key' => $data['idempotency_key'],
            'title' => $data['item_name'],
            'description' => '<p>'.nl2br(e($data['description']), false).'</p>',
            'category_id' => (string) $data['category_id'],
            'category_version' => 'v2',
            'listing_platforms' => ['TIKTOK_SHOP'],
            'main_images' => collect($data['image_ids'])->map(fn ($uri): array => ['uri' => (string) $uri])->values()->all(),
            'package_weight' => [
                'value' => (string) $data['weight'],
                'unit' => 'KILOGRAM',
            ],
            'package_dimensions' => [
                'length' => (string) $data['dimension']['package_length'],
                'width' => (string) $data['dimension']['package_width'],
                'height' => (string) $data['dimension']['package_height'],
                'unit' => 'CENTIMETER',
            ],
            'skus' => [[
                'seller_sku' => $data['item_sku'],
                'price' => [
                    'amount' => (string) ((int) round((float) $data['original_price'])),
                    'currency' => 'IDR',
                ],
                'inventory' => [[
                    'warehouse_id' => $data['warehouse_id'],
                    'quantity' => (int) $data['stock'],
                ]],
            ]],
        ];
        if ($attributes !== []) {
            $payload['product_attributes'] = $attributes;
        }

        $result = $this->products->createProduct($store, $payload);

        return $this->marketplaceResult($result, 'Produk baru gagal dibuat di TikTok Shop.');
    }

    public function page()
    {
        return view('marketplace.products');
    }

    private function createStore(Request $request): Store
    {
        $storeId = $request->validate(['store_id' => 'required|integer|exists:stores,id'])['store_id'];
        $store = Store::whereKey($storeId)
            ->where('status', 'active')
            ->whereHas('channel', fn ($q) => $q->whereIn('code', ['SHOPEE', 'SHP', 'shopee', 'shp', 'TIKTOK', 'TTK', 'TT', 'tiktok', 'ttk', 'tt']))
            ->first();

        abort_unless($store, 422, 'Toko aktif Shopee/TikTok tidak ditemukan.');

        return $store;
    }

    private function isTikTokStore(Store $store): bool
    {
        return in_array(strtolower((string) $store->channel?->code), ['tiktok', 'ttk', 'tt'], true);
    }

    private function marketplaceResult(array $result, string $fallbackMessage)
    {
        if (! empty($result['error'])) {
            return response()->json([
                'message' => $result['message'] ?? $fallbackMessage,
                'error' => $result['error'],
            ], 422);
        }

        return response()->json($result);
    }

    /**
     * Detail edit produk yang diambil ulang dari Shopee.
     * Tahap ini read-only: belum mengirim perubahan ke marketplace.
     */
    public function edit(MarketplaceProduct $product)
    {
        try {
            $data = $this->products->getEditData($product);

            if (! empty($data['error'])) {
                return response()->json([
                    'message' => $data['message'] ?? 'Detail produk tidak dapat diambil dari marketplace.',
                ], 422);
            }

            return response()->json($data);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'message' => $e->getMessage() ?: 'Gagal mengambil detail produk dari marketplace.',
            ], 422);
        }
    }

    public function categoryInfo(Request $request, MarketplaceProduct $product)
    {
        try {
            $categoryId = $request->integer('category_id') ?: null;
            $data = $this->products->getCategoryInfo($product, $categoryId);

            if (! empty($data['error'])) {
                return response()->json([
                    'message' => $data['message'] ?? 'Metadata kategori tidak dapat diambil dari Shopee.',
                ], 422);
            }

            return response()->json($data);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'message' => $e->getMessage() ?: 'Gagal mengambil metadata kategori.',
            ], 422);
        }
    }

    /**
     * Daftar produk lokal + filter.
     */
    public function index(Request $request)
    {
        $q = MarketplaceProduct::with(['store:id,name,channel_id', 'store.channel:id,code', 'models'])
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
            ->where(fn ($w) => $w->whereNull('channel_code')->orWhereRaw('LOWER(channel_code) IN (?, ?)', ['shopee', 'tiktok']))
            ->with(['item:id,code,name,item_category_id', 'item.category:id,code,name'])
            ->get()
            // channel-spesifik menang atas global; key menyertakan channel
            // supaya SKU yang sama dapat dipetakan berbeda di tiap marketplace.
            ->sortBy(fn ($m) => $m->channel_code === null ? 1 : 0)
            ->mapWithKeys(function ($mapping): array {
                $channel = $mapping->channel_code === null
                    ? '*'
                    : strtolower(trim((string) $mapping->channel_code));
                $sku = mb_strtolower(trim((string) $mapping->marketplace_sku));

                return [$channel . '|' . $sku => $mapping];
            });

        $mappingFor = static function (MarketplaceProduct $product, ?string $sku) use ($mappings) {
            $sku = mb_strtolower(trim((string) $sku));
            if ($sku === '') {
                return null;
            }

            $channel = strtolower((string) $product->store?->channel?->code);
            $channel = in_array($channel, ['tt', 'ttk'], true) ? 'tiktok' : $channel;

            return $mappings->get($channel . '|' . $sku) ?? $mappings->get('*|' . $sku);
        };

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
            $parentMapping = $mappingFor($p, $p->item_sku);
            $p->setAttribute('mapping', $parentMapping ? array_merge([
                'id'            => $parentMapping->id,
                'item_id'       => $parentMapping->item_id,
                'item_code'     => $parentMapping->item?->code,
                'item_name'     => $parentMapping->item?->name,
                'category_id'   => $parentMapping->item?->item_category_id,
                'category_name' => $parentMapping->item?->category?->name,
            ], $internalFor($parentMapping)) : null);
            foreach ($p->models as $m) {
                $map = $mappingFor($p, $m->model_sku);
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

        $matchedQuery = $itemsQuery
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
            ]);

        // Jangan mengambil seluruh transaksi ke memory sekaligus. Toko yang
        // sudah lama berjalan bisa memiliki puluhan ribu line item yang
        // cocok dengan SKU produk di katalog. Chunk berdasarkan order, bukan
        // line item, supaya voucher/order payment tidak dihitung dua kali
        // ketika satu order punya beberapa item yang cocok.
        $matchedOrderQuery = (clone $matchedQuery)
            ->selectRaw('COALESCE(oi.marketplace_order_id, oi.order_id) as matched_order_id')
            ->distinct()
            ->orderBy('matched_order_id');

        $matchedOrderQuery->chunk(500, function (Collection $orderRows) use ($matchedQuery, &$metrics, $productsByItem, $productsBySku): void {
            $orderIds = $orderRows
                ->pluck('matched_order_id')
                ->filter()
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values()
                ->all();

            if ($orderIds === []) {
                return;
            }

            $matchedItems = (clone $matchedQuery)
                ->whereIn(DB::raw('COALESCE(oi.marketplace_order_id, oi.order_id)'), $orderIds)
                ->get();

            $this->attachFinancialMetricChunk($matchedItems, $metrics, $productsByItem, $productsBySku);
        });

        foreach ($products as $product) {
            $metric = $metrics[$product->id] ?? ['total_promotion' => 0, 'buyer_payment' => 0];
            $product->setAttribute('total_promotion', round((float) $metric['total_promotion'], 2));
            $product->setAttribute('buyer_payment', round((float) $metric['buyer_payment'], 2));
        }
    }

    /**
     * Proses sebagian line item supaya endpoint katalog tidak kehabisan memory.
     */
    private function attachFinancialMetricChunk(Collection $matchedItems, array &$metrics, array $productsByItem, array $productsBySku): void
    {
        $orderIds = $matchedItems
            ->map(fn ($item) => (int) ($item->marketplace_order_id ?: $item->order_id))
            ->filter()
            ->unique()
            ->values()
            ->all();

        if ($orderIds === []) {
            return;
        }

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
            ->each(function (Collection $orderItems, $orderId) use (&$metrics, $allItems, $orders, $productsByItem, $productsBySku): void {
                $order = $orders->get($orderId);
                if (! $order) {
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

                $orderItems->each(function ($item) use (&$metrics, $orderLineValue, $orderQty, $promotionRemainder, $buyerPayment, $productsByItem, $productsBySku): void {
                    $storeKey = (string) $item->store_id;
                    $productId = $productsByItem[$storeKey . '|' . trim((string) $item->external_item_id)] ?? null;
                    if (! $productId) {
                        foreach ([$item->model_sku, $item->item_sku, $item->marketplace_sku, $item->external_sku] as $sku) {
                            $productId = $productsBySku[$storeKey . '|' . mb_strtolower(trim((string) $sku))] ?? null;
                            if ($productId) {
                                break;
                            }
                        }
                    }
                    if (! $productId || ! isset($metrics[$productId])) {
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
        $stores = Store::whereHas('channel', fn ($q) => $q->whereIn('code', ['SHOPEE', 'SHP', 'shopee', 'TIKTOK', 'TTK', 'tiktok', 'tt']))
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
            'price_list.*.discount_price' => 'prohibited',
        ]);

        $res = $this->products->updatePrice($product, $data['price_list']);

        if (! empty($res['error'])) {
            return response()->json(['message' => $res['message'] ?? 'Gagal update harga di Shopee.'], 422);
        }

        return response()->json(['success' => true]);
    }

    public function updateBaseInfo(MarketplaceProduct $product, Request $request)
    {
        $data = $request->validate([
            'item_name' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:3000',
        ]);

        if (! array_filter($data, static fn ($value) => filled($value))) {
            return response()->json(['message' => 'Isi judul atau deskripsi yang ingin diubah.'], 422);
        }

        $res = $this->products->updateBaseInfo($product, $data);
        if (! empty($res['error'])) {
            return response()->json(['message' => $res['message'] ?? 'Gagal update informasi produk di Shopee.'], 422);
        }

        return response()->json(['success' => true]);
    }

    /**
     * Upload file gambar ke media space Shopee tanpa mengubah listing.
     */
    public function uploadImages(MarketplaceProduct $product, Request $request)
    {
        $data = $request->validate([
            'images'   => 'required|array|min:1|max:9',
            'images.*' => 'required|file|image|mimes:jpg,jpeg,png|max:5120',
        ]);

        $res = $this->products->uploadImages($product, array_values(array_filter($data['images'] ?? [])));
        if (! empty($res['error'])) {
            return response()->json(['message' => $res['message'] ?? 'Gagal upload gambar ke Shopee.'], 422);
        }

        return response()->json($res);
    }

    /**
     * Update gallery produk. image_order berisi existing:{id} atau new:{index}.
     */
    public function updateImages(MarketplaceProduct $product, Request $request)
    {
        $data = $request->validate([
            'image_order'   => 'nullable|array|max:9',
            'image_order.*' => 'string|max:120',
            'images'        => 'nullable|array|max:9',
            'images.*'      => 'file|image|mimes:jpg,jpeg,png|max:5120',
        ]);

        $files = array_values(array_filter($request->file('images', [])));
        if (empty($data['image_order']) && empty($files)) {
            return response()->json(['message' => 'Pilih minimal satu gambar untuk disimpan.'], 422);
        }

        $res = $this->products->updateImages($product, $data['image_order'] ?? [], $files);
        if (! empty($res['error'])) {
            return response()->json(['message' => $res['message'] ?? 'Gagal update gambar produk di Shopee.'], 422);
        }

        return response()->json($res);
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
     * Audit perubahan listing produk, termasuk hasil verifikasi gambar Shopee.
     */
    public function changeLogs(MarketplaceProduct $product)
    {
        $logs = $product->changeLogs()
            ->with('creator:id,name')
            ->latest()
            ->limit(100)
            ->get()
            ->map(static fn ($log) => [
                'id' => $log->id,
                'action' => $log->action,
                'status' => $log->status,
                'before' => $log->before_json,
                'after' => $log->after_json,
                'response' => $log->response_json,
                'error_message' => $log->error_message,
                'created_by' => $log->creator?->name,
                'created_at' => $log->created_at?->toIso8601String(),
            ]);

        return response()->json(['product_id' => $product->id, 'logs' => $logs]);
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
            'summary' => [
                'orders' => $orders->count(),
                'gmv' => round((float) $orders->sum('line_total'), 2),
                'buyer_payment' => round((float) $orders->sum('buyer_payment'), 2),
                'aov' => $orders->count() > 0
                    ? round((float) $orders->sum('buyer_payment') / $orders->count(), 2)
                    : 0,
            ],
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
