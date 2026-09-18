<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Item;
use App\Models\MarketplaceImportBatch;
use App\Models\MarketplaceOrder;
use App\Models\MarketplaceOrderItem;
use App\Models\MarketplaceProduct;
use App\Models\Store;
use App\Models\SkuMapping;
use App\Models\WhatsAppTemplate;
use App\Services\Marketplace\Crm\MarketplaceCrmImportService;
use App\Services\MarketplaceIssueService;
use App\Services\WhatsApp\WhatsAppMessageService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class MarketplaceCrmController extends Controller
{
    public function dashboard(Request $request)
    {
        $daysInput = strtolower(trim((string) $request->query('days', '30')));
        $days = match ($daysInput) {
            '7', '7h' => 7,
            '30', '30h' => 30,
            '90', '90h' => 90,
            '365', '1t', '1tahun', '1 year', 'year' => 365,
            default => 30,
        };
        $storeId = $request->integer('store_id') ?: null;
        [$productSort, $productDirection] = $this->sortParameters($request, ['name', 'qty', 'revenue'], 'qty');
        $since = $days === 365
            ? now()->startOfMonth()->subMonths(11)->startOfDay()
            : now()->subDays($days - 1)->startOfDay();

        $orders = $this->ordersQuery($storeId)->where(function ($q) use ($since) {
            $q->where('ordered_at', '>=', $since)
                ->orWhere(function ($q) use ($since) {
                    $q->whereNull('ordered_at')->where('order_date', '>=', $since);
                });
        });

        $totalOrders = (clone $orders)->count();
        $totalRevenue = (clone $orders)->where('status', '!=', 'cancelled')->sum('total_amount');
        $averageOrder = $totalOrders > 0 ? $totalRevenue / max(1, (clone $orders)->where('status', '!=', 'cancelled')->count()) : 0;
        $customerCount = (clone $orders)->whereNotNull('customer_id')->distinct()->count('customer_id');
        $pendingOrders = (clone $orders)->whereIn('status', ['new', 'packed', 'shipped'])->count();

        $repeatCustomers = DB::table('marketplace_orders')
            ->whereNotNull('channel_order_id')
            ->whereNotNull('customer_id')
            ->where(function ($q) use ($since) {
                $q->where('ordered_at', '>=', $since)
                    ->orWhere(function ($q) use ($since) {
                        $q->whereNull('ordered_at')->where('order_date', '>=', $since);
                    });
            })
            ->when($storeId, fn ($q) => $q->where('store_id', $storeId))
            ->select('customer_id')
            ->groupBy('customer_id')
            ->havingRaw('COUNT(*) > 1')
            ->count();

        $statusCounts = (clone $orders)
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $topProducts = MarketplaceOrderItem::query()
            ->join('marketplace_orders', 'marketplace_orders.id', '=', 'marketplace_order_items.order_id')
            ->whereNotNull('marketplace_orders.channel_order_id')
            ->where(function ($q) use ($since) {
                $q->where('marketplace_orders.ordered_at', '>=', $since)
                    ->orWhere(function ($q) use ($since) {
                        $q->whereNull('marketplace_orders.ordered_at')->where('marketplace_orders.order_date', '>=', $since);
                    });
            })
            ->when($storeId, fn ($q) => $q->where('marketplace_orders.store_id', $storeId))
            ->selectRaw("COALESCE(marketplace_order_items.item_name_snapshot, marketplace_order_items.item_name, marketplace_order_items.external_sku, 'Tanpa nama') as name")
            ->selectRaw('SUM(marketplace_order_items.qty) as qty')
            ->selectRaw('SUM(marketplace_order_items.line_net_amount) as revenue')
            ->groupBy('name')
            ->orderBy($productSort, $productDirection)
            ->paginate(8)
            ->withPath($request->url())
            ->appends($request->query());

        $dailyOrders = (clone $orders)
            ->selectRaw("DATE(COALESCE(ordered_at, order_date)) as date")
            ->selectRaw('COUNT(*) as orders')
            ->selectRaw('SUM(CASE WHEN status != ? THEN total_amount ELSE 0 END) as revenue', ['cancelled'])
            ->groupBy('date')
            ->orderBy('date')
            ->get();
        $dailyOrders = $this->aggregateDashboardChart($dailyOrders, $days);

        return view('admin.crm.marketplace.dashboard', [
            'days' => $days,
            'storeId' => $storeId,
            'stores' => $this->stores(),
            'totalOrders' => $totalOrders,
            'totalRevenue' => $totalRevenue,
            'averageOrder' => $averageOrder,
            'customerCount' => $customerCount,
            'pendingOrders' => $pendingOrders,
            'repeatCustomers' => $repeatCustomers,
            'statusCounts' => $statusCounts,
            'topProducts' => $topProducts,
            'dailyOrders' => $dailyOrders,
            'recentImports' => MarketplaceImportBatch::query()
                ->where('source_type', 'marketplace_crm')
                ->with('store:id,name')
                ->latest()
                ->limit(8)
                ->get(),
            'productSort' => $productSort,
            'productDirection' => $productDirection,
        ]);
    }

    public function orders(Request $request)
    {
        $storeId = $request->integer('store_id') ?: null;
        $status = trim((string) $request->query('status', ''));
        $search = trim((string) $request->query('q', ''));
        $dateFrom = $this->parseOrderDate($request->query('date_from'));
        $dateTo = $this->parseOrderDate($request->query('date_to'))?->endOfDay();
        [$sort, $direction] = $this->sortParameters($request, ['order', 'customer', 'status', 'total', 'date'], 'date');
        $sortColumns = [
            'order' => 'channel_order_id',
            'customer' => 'buyer_name',
            'status' => 'status',
            'total' => 'total_amount',
        ];

        $baseQuery = $this->ordersQuery($storeId);
        $this->applyOrderDateRange($baseQuery, $dateFrom, $dateTo);

        $filteredQuery = (clone $baseQuery)
            ->when($status, fn ($q) => $q->where('status', $status))
            ->when($search, function ($q) use ($search) {
                $q->where(function ($query) use ($search) {
                    $query->where('channel_order_id', 'like', "%{$search}%")
                        ->orWhere('external_order_id', 'like', "%{$search}%")
                        ->orWhere('buyer_name', 'like', "%{$search}%")
                        ->orWhere('buyer_username', 'like', "%{$search}%")
                        ->orWhere('buyer_phone', 'like', "%{$search}%");
                });
            });

        $orders = (clone $filteredQuery)
            ->when($sort === 'date', fn ($q) => $q->orderByRaw("COALESCE(ordered_at, order_date) {$direction}"))
            ->when($sort !== 'date', fn ($q) => $q->orderBy($sortColumns[$sort], $direction))
            ->orderByDesc('id')
            ->paginate(25)
            ->withPath($request->url())
            ->appends($request->query());

        $totalOrders = (clone $filteredQuery)->count();
        $cancelledOrders = (clone $filteredQuery)->where('status', 'cancelled')->count();
        $completedQuery = (clone $filteredQuery)->where('status', '!=', 'cancelled');
        $revenue = (clone $completedQuery)->sum('total_amount');
        $customerCount = (clone $filteredQuery)->whereNotNull('customer_id')->distinct()->count('customer_id');
        $averageOrder = $completedQuery->count() > 0 ? $revenue / $completedQuery->count() : 0;

        $chartRows = (clone $filteredQuery)
            ->selectRaw("DATE(COALESCE(ordered_at, order_date)) as date")
            ->selectRaw('COUNT(*) as orders')
            ->selectRaw('SUM(CASE WHEN status != ? THEN total_amount ELSE 0 END) as revenue', ['cancelled'])
            ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as cancelled', ['cancelled'])
            ->whereRaw('COALESCE(ordered_at, order_date) IS NOT NULL')
            ->groupBy('date')
            ->orderBy('date')
            ->get();
        $rangeStart = $dateFrom ?: ($chartRows->first()?->date ? Carbon::parse($chartRows->first()->date) : null);
        $rangeEnd = $dateTo ?: ($chartRows->last()?->date ? Carbon::parse($chartRows->last()->date) : null);
        $chartDays = $rangeStart && $rangeEnd ? max(1, $rangeStart->diffInDays($rangeEnd) + 1) : 30;
        $dailyOrders = $this->aggregateDashboardChart($chartRows, $chartDays);

        return view('admin.crm.marketplace.orders', [
            'orders' => $orders,
            'stores' => $this->stores(),
            'storeId' => $storeId,
            'status' => $status,
            'search' => $search,
            'dateFrom' => $dateFrom?->toDateString(),
            'dateTo' => $dateTo?->toDateString(),
            'sort' => $sort,
            'direction' => $direction,
            'statusCounts' => (clone $baseQuery)->select('status', DB::raw('COUNT(*) as total'))->groupBy('status')->pluck('total', 'status'),
            'analytics' => [
                'totalOrders' => $totalOrders,
                'revenue' => $revenue,
                'averageOrder' => $averageOrder,
                'customerCount' => $customerCount,
                'cancelledOrders' => $cancelledOrders,
                'cancelRate' => $totalOrders > 0 ? ($cancelledOrders / $totalOrders) * 100 : 0,
            ],
            'dailyOrders' => $dailyOrders,
        ]);
    }

    public function showOrder(Request $request, MarketplaceOrder $order)
    {
        abort_if(! $order->channel_order_id, 404);

        [$itemSort, $itemDirection] = $this->sortParameters($request, ['product', 'variant', 'qty', 'price', 'subtotal'], 'product');
        $itemColumns = [
            'product' => 'item_name_snapshot',
            'variant' => 'variant_snapshot',
            'qty' => 'qty',
            'price' => 'price_after_discount',
            'subtotal' => 'line_net_amount',
        ];

        $order->load(['store.channel', 'customer']);
        $items = $order->items()
            ->orderBy($itemColumns[$itemSort], $itemDirection)
            ->paginate(25, ['*'], 'items_page')
            ->withPath($request->url())
            ->appends($request->query());

        return view('admin.crm.marketplace.order-show', compact('order', 'items', 'itemSort', 'itemDirection'));
    }

    public function customers(Request $request)
    {
        $storeId = $request->integer('store_id') ?: null;
        $search = trim((string) $request->query('q', ''));
        [$sort, $direction] = $this->sortParameters($request, ['customer', 'location', 'orders', 'total', 'last_order'], 'total');
        $sortColumns = [
            'customer' => 'customers.name',
            'location' => 'customers.city',
            'orders' => 'marketplace_order_count',
            'total' => 'marketplace_total_spent',
            'last_order' => 'last_marketplace_order_at',
        ];

        $customers = Customer::query()
            ->join('marketplace_orders', 'marketplace_orders.customer_id', '=', 'customers.id')
            ->whereNotNull('marketplace_orders.channel_order_id')
            ->when($storeId, fn ($q) => $q->where('marketplace_orders.store_id', $storeId))
            ->when($search, function ($q) use ($search) {
                $q->where(function ($query) use ($search) {
                    $query->where('customers.name', 'like', "%{$search}%")
                        ->orWhere('customers.phone', 'like', "%{$search}%");
                });
            })
            ->select('customers.*')
            ->selectRaw('COUNT(DISTINCT marketplace_orders.id) as marketplace_order_count')
            ->selectRaw('SUM(CASE WHEN marketplace_orders.status != ? THEN marketplace_orders.total_amount ELSE 0 END) as marketplace_total_spent', ['cancelled'])
            ->selectRaw('MAX(COALESCE(marketplace_orders.ordered_at, marketplace_orders.order_date)) as last_marketplace_order_at')
            ->groupBy('customers.id')
            ->orderBy($sortColumns[$sort], $direction)
            ->orderBy('customers.id')
            ->paginate(25)
            ->withPath($request->url())
            ->appends($request->query());

        return view('admin.crm.marketplace.customers', [
            'customers' => $customers,
            'stores' => $this->stores(),
            'storeId' => $storeId,
            'search' => $search,
            'sort' => $sort,
            'direction' => $direction,
        ]);
    }

    public function prospects(Request $request)
    {
        $storeId = $request->integer('store_id') ?: null;
        $search = trim((string) $request->query('q', ''));
        $productTitle = trim((string) $request->query('product_title', ''));
        $paymentMethod = trim((string) $request->query('payment_method', ''));
        $city = trim((string) $request->query('city', ''));
        $province = trim((string) $request->query('province', ''));
        $segment = trim((string) $request->query('segment', ''));
        $orderAge = trim((string) $request->query('order_age', ''));
        $paidRange = trim((string) $request->query('paid_range', ''));
        $dateFrom = $this->parseOrderDate($request->query('date_from'));
        $dateTo = $this->parseOrderDate($request->query('date_to'))?->endOfDay();
        $waStatus = trim((string) $request->query('wa_status', ''));
        $perPageOptions = [25, 50, 100];
        $perPage = (int) $request->query('per_page', 25);
        if (!in_array($perPage, $perPageOptions, true)) {
            $perPage = 25;
        }
        [$sort, $direction] = $this->sortParameters($request, ['customer', 'first_order', 'total', 'paid', 'segment'], 'first_order');
        $profiles = $this->customerProfiles($storeId);

        // Marketplace tidak memiliki event abandoned-cart seperti storefront.
        // Prospect berarti buyer yang baru satu kali order; recency tetap
        // ditampilkan agar buyer lama bisa dipisahkan saat follow-up.
        $prospects = $profiles
            ->filter(fn ($customer) => (int) $customer->marketplace_order_count === 1)
            ->values();
        if ($dateFrom || $dateTo) {
            $prospects = $prospects->filter(function ($customer) use ($dateFrom, $dateTo) {
                $orderDate = $customer->first_marketplace_order_at
                    ? Carbon::parse($customer->first_marketplace_order_at)
                    : null;

                return $orderDate
                    && (! $dateFrom || $orderDate->greaterThanOrEqualTo($dateFrom))
                    && (! $dateTo || $orderDate->lessThanOrEqualTo($dateTo));
            })->values();
        }
        $cities = $prospects
            ->map(fn ($customer) => trim((string) $customer->city))
            ->filter()
            ->unique()
            ->sortBy(fn ($value) => mb_strtolower($value))
            ->values();
        $provinces = $prospects
            ->map(fn ($customer) => trim((string) $customer->province))
            ->filter()
            ->unique()
            ->sortBy(fn ($value) => mb_strtolower($value))
            ->values();
        $paymentMethods = $prospects
            ->map(fn ($customer) => trim((string) ($customer->marketplace_payment_method ?: '')))
            ->filter()
            ->unique()
            ->sortBy(fn ($value) => mb_strtolower($value))
            ->values();
        $productTitles = MarketplaceOrderItem::query()
            ->whereNotNull('item_name_snapshot')
            ->where('item_name_snapshot', '!=', '')
            ->distinct()
            ->orderBy('item_name_snapshot')
            ->pluck('item_name_snapshot');
        if ($segment !== '') {
            $prospects = $prospects->filter(fn ($customer) => (string) $customer->segment === $segment)->values();
        }
        if ($orderAge !== '') {
            $prospects = $prospects->filter(function ($customer) use ($orderAge) {
                $days = (int) $customer->days_since_last_order;
                return match ($orderAge) {
                    '0_30' => $days <= 30,
                    '31_90' => $days >= 31 && $days <= 90,
                    '91_365' => $days >= 91 && $days <= 365,
                    '366_plus' => $days >= 366,
                    default => true,
                };
            })->values();
        }
        if ($paidRange !== '') {
            $prospects = $prospects->filter(function ($customer) use ($paidRange) {
                $paid = (float) $customer->marketplace_total_paid;
                return match ($paidRange) {
                    'under_100k' => $paid < 100_000,
                    '100k_300k' => $paid >= 100_000 && $paid < 300_000,
                    '300k_plus' => $paid >= 300_000,
                    default => true,
                };
            })->values();
        }
        if ($city !== '') {
            $prospects = $prospects->filter(fn ($customer) => mb_strtolower(trim((string) $customer->city)) === mb_strtolower($city))->values();
        }
        if ($province !== '') {
            $prospects = $prospects->filter(fn ($customer) => mb_strtolower(trim((string) $customer->province)) === mb_strtolower($province))->values();
        }
        if ($paymentMethod !== '') {
            $prospects = $prospects->filter(fn ($customer) => mb_strtolower(trim((string) ($customer->marketplace_payment_method ?: ''))) === mb_strtolower($paymentMethod))->values();
        }
        if ($waStatus !== '') {
            $prospects = $prospects->filter(function ($customer) use ($waStatus) {
                $hasWhatsApp = trim((string) $customer->wa_phone) !== '';
                return $waStatus === 'ready' ? $hasWhatsApp : ($waStatus === 'missing' ? ! $hasWhatsApp : true);
            })->values();
        }
        if ($productTitle !== '') {
            $matchingCustomerIds = $this->prospectItemCustomerIds($storeId, $productTitle);
            $prospects = $prospects->filter(fn ($customer) => $matchingCustomerIds->contains((int) $customer->id))->values();
        }
        if ($search !== '') {
            $needle = mb_strtolower($search);
            $matchingCustomerIds = $this->prospectItemCustomerIds($storeId, $search);
            $prospects = $prospects->filter(fn ($customer) => $matchingCustomerIds->contains((int) $customer->id)
                || str_contains(mb_strtolower((string) $customer->name), $needle)
                || str_contains(mb_strtolower((string) $customer->phone), $needle))->values();
        }
        $totalProspects = $prospects->count();
        $totalPaid = (float) $prospects->sum(fn ($customer) => (float) $customer->marketplace_total_paid);
        $waReady = $prospects->filter(fn ($customer) => trim((string) $customer->wa_phone) !== '')->count();
        $prospectMetrics = [
            'total' => $totalProspects,
            'total_paid' => $totalPaid,
            'average_paid' => $totalProspects > 0 ? $totalPaid / $totalProspects : 0,
            'wa_ready' => $waReady,
            'wa_sent' => $prospects->filter(fn ($customer) => (int) ($customer->whatsapp_sent_count ?? 0) > 0)->count(),
            'wa_total_attempts' => (int) $prospects->sum(fn ($customer) => (int) ($customer->whatsapp_message_count ?? 0)),
            'wa_coverage' => $totalProspects > 0 ? ($waReady / $totalProspects) * 100 : 0,
        ];
        $prospectAnalytics = [
            'order_age' => collect([
                ['label' => '0–30 hari', 'count' => $prospects->filter(fn ($customer) => (int) $customer->days_since_last_order <= 30)->count()],
                ['label' => '31–90 hari', 'count' => $prospects->filter(fn ($customer) => (int) $customer->days_since_last_order >= 31 && (int) $customer->days_since_last_order <= 90)->count()],
                ['label' => '91 hari–1 tahun', 'count' => $prospects->filter(fn ($customer) => (int) $customer->days_since_last_order >= 91 && (int) $customer->days_since_last_order <= 365)->count()],
                ['label' => '> 1 tahun', 'count' => $prospects->filter(fn ($customer) => (int) $customer->days_since_last_order >= 366)->count()],
            ])->values()->all(),
            'follow_up' => collect([
                ['label' => 'Belum dikirim', 'count' => $prospects->filter(fn ($customer) => (int) ($customer->whatsapp_message_count ?? 0) === 0)->count()],
                ['label' => '1 kali', 'count' => $prospects->filter(fn ($customer) => (int) ($customer->whatsapp_message_count ?? 0) === 1)->count()],
                ['label' => '2–3 kali', 'count' => $prospects->filter(fn ($customer) => (int) ($customer->whatsapp_message_count ?? 0) >= 2 && (int) ($customer->whatsapp_message_count ?? 0) <= 3)->count()],
                ['label' => '>3 kali', 'count' => $prospects->filter(fn ($customer) => (int) ($customer->whatsapp_message_count ?? 0) >= 4)->count()],
            ])->values()->all(),
            'top_provinces' => $prospects
                ->map(fn ($customer) => trim((string) $customer->province))
                ->filter()
                ->countBy()
                ->sortDesc()
                ->take(6)
                ->map(fn ($count, $label) => ['label' => $label, 'count' => $count])
                ->values()
                ->all(),
            'payment_methods' => $prospects
                ->map(fn ($customer) => trim((string) ($customer->marketplace_payment_method ?: 'Tidak diketahui')))
                ->map(fn ($method) => $method !== '' ? $method : 'Tidak diketahui')
                ->countBy()
                ->sortDesc()
                ->take(6)
                ->map(fn ($count, $label) => ['label' => $label, 'count' => $count])
                ->values()
                ->all(),
        ];
        $prospects = $this->sortCollection($prospects, [
            'customer' => fn ($customer) => mb_strtolower((string) $customer->name),
            'first_order' => fn ($customer) => (string) $customer->first_marketplace_order_at,
            'total' => fn ($customer) => (float) $customer->marketplace_total_spent,
            'paid' => fn ($customer) => (float) $customer->marketplace_total_paid,
            'segment' => fn ($customer) => mb_strtolower((string) $customer->segment),
        ], $sort, $direction);
        $prospects = $this->paginateCollection($prospects, $request, $perPage);
        $prospects->setCollection($this->attachProspectItems($prospects->getCollection(), $storeId, true));

        return view('admin.crm.marketplace.prospects', [
            'prospects' => $prospects,
            'stores' => $this->stores(),
            'storeId' => $storeId,
            'search' => $search,
            'productTitle' => $productTitle,
            'productTitles' => $productTitles,
            'paymentMethod' => $paymentMethod,
            'paymentMethods' => $paymentMethods,
            'city' => $city,
            'cities' => $cities,
            'province' => $province,
            'provinces' => $provinces,
            'segment' => $segment,
            'orderAge' => $orderAge,
            'paidRange' => $paidRange,
            'dateFrom' => $dateFrom?->toDateString(),
            'dateTo' => $dateTo?->toDateString(),
            'waStatus' => $waStatus,
            'perPage' => $perPage,
            'perPageOptions' => $perPageOptions,
            'segmentDefinitions' => self::segmentDefinitions(),
            'prospectMetrics' => $prospectMetrics,
            'prospectAnalytics' => $prospectAnalytics,
            'sort' => $sort,
            'direction' => $direction,
        ]);
    }

    public function searchProspectItems(Request $request)
    {
        $search = trim((string) $request->query('q', ''));
        $limit = min(max($request->integer('limit', 20), 1), 50);

        $items = Item::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('code', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%");
                });
            })
            ->orderBy('code')
            ->limit($limit)
            ->get(['id', 'code', 'sku', 'name']);

        return response()->json($items);
    }

    public function mapProspectItem(
        Request $request,
        MarketplaceOrderItem $item,
        MarketplaceIssueService $issueService,
    ) {
        $data = $request->validate([
            'internal_item_id' => ['required', 'integer', 'exists:items,id'],
            'apply_to_all' => ['nullable', 'boolean'],
        ]);

        $orderId = $item->marketplace_order_id ?: $item->order_id;
        $order = MarketplaceOrder::query()->find($orderId);
        abort_unless($order?->channel_order_id, 404);
        $item->setRelation('order', $order);

        try {
            $result = $issueService->mapSku(
                $item,
                (int) $data['internal_item_id'],
                (bool) ($data['apply_to_all'] ?? false),
            );
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => "Item berhasil dihubungkan ke master. {$result['affected']} item diperbarui.",
            'affected' => $result['affected'],
        ]);
    }

    public function composeProspectFollowUp(Customer $customer)
    {
        $marketplaceOrderCount = MarketplaceOrder::query()
            ->where('customer_id', $customer->id)
            ->whereNotNull('channel_order_id')
            ->where('status', '!=', 'cancelled')
            ->count();

        abort_unless($marketplaceOrderCount === 1, 404);

        $phone = $this->normalizePhone($customer->phone);
        if ($phone === '') {
            return redirect()
                ->route('admin.crm.marketplace.prospects')
                ->with('error', 'Nomor WhatsApp customer belum diisi.');
        }

        $templates = WhatsAppTemplate::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $variables = [
            'customer_name' => $customer->name ?: 'Customer',
        ];
        $message = 'Halo '.$variables['customer_name'].",\n\n"
            . "Terima kasih sudah order di Great Fit Indonesia. Ada koleksi baru yang mungkin cocok untuk kamu.\n\n"
            . 'Silakan balas pesan ini jika ingin kami bantu.';

        return view('whatsapp.compose-prospect', [
            'customer' => $customer,
            'draft' => [
                'phone' => $phone,
                'recipient_name' => $customer->name,
                'message' => $message,
                'template_key' => null,
                'variables' => $variables,
                'module' => 'marketplace_crm',
                'reference_type' => Customer::class,
                'reference_id' => $customer->id,
                'reference_label' => 'Follow up prospect marketplace',
            ],
            'templates' => $templates,
            'contextTitle' => 'Follow up '.$customer->name,
            'contextUrl' => route('admin.crm.marketplace.prospects'),
            'isConfigured' => filled(config('services.fonnte.token')),
        ]);
    }

    public function composeBulkProspectFollowUp(Request $request)
    {
        $customers = $this->selectedBulkProspects($request);
        if ($customers->isEmpty()) {
            return redirect()
                ->route('admin.crm.marketplace.prospects')
                ->with('error', 'Pilih minimal satu prospect yang memiliki nomor WhatsApp.');
        }

        $templates = WhatsAppTemplate::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('whatsapp.compose-prospect-bulk', [
            'customers' => $customers,
            'templates' => $templates,
            'draftMessage' => "Halo {customer_name},\n\n"
                . "Terima kasih sudah order di Great Fit Indonesia. Ada koleksi baru yang mungkin cocok untuk kamu.\n\n"
                . 'Silakan balas pesan ini jika ingin kami bantu.',
            'contextTitle' => 'Bulk follow up prospect marketplace',
            'contextUrl' => route('admin.crm.marketplace.prospects'),
            'isConfigured' => filled(config('services.fonnte.token')),
        ]);
    }

    public function sendBulkProspectFollowUp(Request $request, WhatsAppMessageService $whatsapp)
    {
        $data = $request->validate([
            'customer_ids' => ['required', 'array', 'min:1', 'max:100'],
            'customer_ids.*' => ['integer'],
            'message' => ['required', 'string', 'max:4000'],
            'template_key' => ['nullable', 'string', 'exists:whatsapp_templates,key'],
        ]);

        if (! filled(config('services.fonnte.token'))) {
            return back()->withInput()->with('error', 'Token Fonnte belum dikonfigurasi.');
        }

        $template = ! empty($data['template_key'])
            ? WhatsAppTemplate::query()
                ->where('key', $data['template_key'])
                ->where('is_active', true)
                ->first()
            : null;
        if ($data['template_key'] && ! $template) {
            return back()->withInput()->with('error', 'Template WhatsApp tidak aktif atau tidak ditemukan.');
        }

        $customers = $this->selectedBulkProspects($request);
        if ($customers->isEmpty()) {
            return redirect()
                ->route('admin.crm.marketplace.prospects')
                ->with('error', 'Tidak ada prospect valid untuk dikirim.');
        }

        $sent = 0;
        $failed = 0;
        foreach ($customers as $customer) {
            $message = str_replace(
                ['{customer_name}'],
                [$customer->name ?: 'Customer'],
                $data['message'],
            );
            $message = mb_substr($message, 0, 4000);

            $log = $whatsapp->sendText(
                $customer->wa_phone,
                $message,
                [
                    'module' => 'marketplace_crm',
                    'reference_type' => Customer::class,
                    'reference_id' => $customer->id,
                    'reference_label' => 'Bulk follow up prospect marketplace',
                ],
                $customer->name,
                $template?->key,
            );

            $log->isSent() ? $sent++ : $failed++;
        }

        $message = "Bulk follow-up selesai: {$sent} terkirim";
        if ($failed > 0) {
            $message .= ", {$failed} gagal.";
        } else {
            $message .= '.';
        }

        return redirect()
            ->route('admin.crm.marketplace.prospects')
            ->with($failed > 0 ? 'error' : 'success', $message);
    }

    private function selectedBulkProspects(Request $request): Collection
    {
        $ids = collect($request->input('customer_ids', []))
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->take(100)
            ->values();

        if ($ids->isEmpty()) {
            return collect();
        }

        return Customer::query()
            ->whereIn('id', $ids)
            ->withCount(['marketplaceOrders as active_marketplace_order_count' => function ($query) {
                $query->whereNotNull('channel_order_id')->where('status', '!=', 'cancelled');
            }])
            ->get(['id', 'name', 'phone'])
            ->filter(function ($customer) {
                $customer->wa_phone = $this->normalizePhone($customer->phone);
                return (int) $customer->active_marketplace_order_count === 1 && $customer->wa_phone !== '';
            })
            ->values();
    }

    public function segments(Request $request)
    {
        $storeId = $request->integer('store_id') ?: null;
        $profiles = $this->customerProfiles($storeId);
        $definitions = self::segmentDefinitions();
        $overview = collect($definitions)->map(function ($definition, $key) use ($profiles) {
            $group = $profiles->where('segment', $key);
            return $definition + [
                'key' => $key,
                'count' => $group->count(),
                'revenue' => $group->sum('marketplace_total_spent'),
                'avg_spent' => $group->count() ? $group->avg('marketplace_total_spent') : 0,
            ];
        });

        $totalCustomers = $profiles->count();
        $totalRevenue = (float) $profiles->sum('marketplace_total_spent');
        $totalOrders = (int) $profiles->sum('marketplace_order_count');
        $repeatCustomers = $profiles->where('marketplace_order_count', '>=', 2)->count();
        $revenueAtRisk = (float) $profiles
            ->whereIn('segment', ['at_risk', 'lost'])
            ->sum('marketplace_total_spent');
        $engagedCustomers = $profiles->where('days_since_last_order', '<=', 90)->count();

        $segmentMetrics = $overview->map(function (array $segment) use ($totalCustomers, $totalRevenue) {
            $segment['share'] = $totalCustomers > 0 ? ($segment['count'] / $totalCustomers) * 100 : 0;
            $segment['revenue_share'] = $totalRevenue > 0 ? ($segment['revenue'] / $totalRevenue) * 100 : 0;
            return $segment;
        });

        $recencyBuckets = collect([
            ['key' => '0_30', 'label' => '0–30 hari', 'color' => '#2563eb', 'min' => 0, 'max' => 30],
            ['key' => '31_90', 'label' => '31–90 hari', 'color' => '#10b981', 'min' => 31, 'max' => 90],
            ['key' => '91_180', 'label' => '91–180 hari', 'color' => '#f97316', 'min' => 91, 'max' => 180],
            ['key' => '181_plus', 'label' => '181+ hari', 'color' => '#94a3b8', 'min' => 181, 'max' => PHP_INT_MAX],
        ])->map(function (array $bucket) use ($profiles) {
            $group = $profiles->filter(fn ($profile) =>
                (int) $profile->days_since_last_order >= $bucket['min']
                && (int) $profile->days_since_last_order <= $bucket['max']
            );
            $bucket['count'] = $group->count();
            $bucket['revenue'] = (float) $group->sum('marketplace_total_spent');
            return $bucket;
        });

        $valueTiers = collect([
            ['label' => 'Premium', 'range' => '≥ Rp1 juta', 'min' => 1_000_000, 'max' => PHP_INT_MAX, 'color' => '#7c3aed'],
            ['label' => 'High value', 'range' => 'Rp250 ribu–999 ribu', 'min' => 250_000, 'max' => 999_999, 'color' => '#2563eb'],
            ['label' => 'Core', 'range' => 'Rp100 ribu–249 ribu', 'min' => 100_000, 'max' => 249_999, 'color' => '#10b981'],
            ['label' => 'Entry', 'range' => '< Rp100 ribu', 'min' => 0, 'max' => 99_999, 'color' => '#94a3b8'],
        ])->map(function (array $tier) use ($profiles) {
            $group = $profiles->filter(fn ($profile) =>
                (float) $profile->marketplace_total_spent >= $tier['min']
                && (float) $profile->marketplace_total_spent <= $tier['max']
            );
            $tier['count'] = $group->count();
            $tier['revenue'] = (float) $group->sum('marketplace_total_spent');
            return $tier;
        });

        $topRevenueSegment = $segmentMetrics->sortByDesc('revenue')->first();
        $riskShare = $totalCustomers > 0 ? (($profiles->whereIn('segment', ['at_risk', 'lost'])->count() / $totalCustomers) * 100) : 0;
        $insights = collect([
            [
                'icon' => 'bi-shield-exclamation',
                'tone' => 'orange',
                'label' => 'Revenue perlu diamankan',
                'value' => $revenueAtRisk,
                'format' => 'currency',
                'body' => number_format($riskShare, 1, ',', '.').'% customer berada di At Risk atau Lost.',
                'href' => route('admin.crm.marketplace.segments.show', array_merge(['segment' => 'at_risk'], $storeId ? ['store_id' => $storeId] : [])),
                'cta' => 'Buka action queue',
            ],
            [
                'icon' => 'bi-graph-up-arrow',
                'tone' => 'green',
                'label' => 'Mesin pertumbuhan',
                'value' => $repeatCustomers,
                'format' => 'number',
                'body' => number_format($repeatCustomers).' customer sudah melakukan pembelian berulang.',
                'href' => route('admin.crm.marketplace.segments.show', array_merge(['segment' => 'loyal'], $storeId ? ['store_id' => $storeId] : [])),
                'cta' => 'Lihat customer loyal',
            ],
            [
                'icon' => 'bi-stars',
                'tone' => 'blue',
                'label' => 'Peluang konversi kedua',
                'value' => $segmentMetrics->firstWhere('key', 'promising')['count'] ?? 0,
                'format' => 'number',
                'body' => 'Customer baru dengan peluang paling jelas untuk didorong ke order kedua.',
                'href' => route('admin.crm.marketplace.segments.show', array_merge(['segment' => 'promising'], $storeId ? ['store_id' => $storeId] : [])),
                'cta' => 'Buka promising',
            ],
        ]);

        return view('admin.crm.marketplace.segments', [
            'overview' => $segmentMetrics,
            'totalCustomers' => $totalCustomers,
            'totalRevenue' => $totalRevenue,
            'totalOrders' => $totalOrders,
            'repeatCustomers' => $repeatCustomers,
            'repeatRate' => $totalCustomers > 0 ? ($repeatCustomers / $totalCustomers) * 100 : 0,
            'averageCustomerValue' => $totalCustomers > 0 ? $totalRevenue / $totalCustomers : 0,
            'averageOrderValue' => $totalOrders > 0 ? $totalRevenue / $totalOrders : 0,
            'revenueAtRisk' => $revenueAtRisk,
            'revenueAtRiskShare' => $totalRevenue > 0 ? ($revenueAtRisk / $totalRevenue) * 100 : 0,
            'engagedCustomers' => $engagedCustomers,
            'recencyBuckets' => $recencyBuckets,
            'valueTiers' => $valueTiers,
            'insights' => $insights,
            'topRevenueSegment' => $topRevenueSegment,
            'analysisDate' => now()->startOfDay(),
            'storeId' => $storeId,
            'stores' => $this->stores(),
        ]);
    }

    public function segmentShow(Request $request, string $segment)
    {
        $definitions = self::segmentDefinitions();
        abort_if(! isset($definitions[$segment]), 404);

        $storeId = $request->integer('store_id') ?: null;
        [$sort, $direction] = $this->sortParameters($request, ['customer', 'orders', 'total', 'last_order'], 'total');
        $customers = $this->customerProfiles($storeId)
            ->where('segment', $segment)
            ->values();
        $analysisDate = now()->startOfDay();
        $customers = $this->sortCollection($customers, [
            'customer' => fn ($customer) => mb_strtolower((string) $customer->name),
            'orders' => fn ($customer) => (int) $customer->marketplace_order_count,
            'total' => fn ($customer) => (float) $customer->marketplace_total_spent,
            'last_order' => fn ($customer) => (string) $customer->last_marketplace_order_at,
        ], $sort, $direction);
        $customers = $this->paginateCollection($customers, $request);

        return view('admin.crm.marketplace.segment-show', [
            'definition' => $definitions[$segment],
            'segment' => $segment,
            'customers' => $customers,
            'analysisDate' => $analysisDate,
            'storeId' => $storeId,
            'sort' => $sort,
            'direction' => $direction,
        ]);
    }

    public static function segmentDefinitions(): array
    {
        return [
            'champions' => ['label' => 'Champions', 'icon' => 'bi-trophy', 'color' => '#f59e0b', 'bg' => '#fffbeb', 'desc' => 'Order 3× atau lebih dan aktif dalam 60 hari.', 'action' => 'Tawarkan produk premium atau program referral.'],
            'loyal' => ['label' => 'Loyal', 'icon' => 'bi-heart', 'color' => '#ef4444', 'bg' => '#fef2f2', 'desc' => 'Order minimal 2× dan aktif dalam 90 hari.', 'action' => 'Tawarkan produk baru atau bundle.'],
            'new' => ['label' => 'New Customer', 'icon' => 'bi-stars', 'color' => '#6366f1', 'bg' => '#eef2ff', 'desc' => 'Baru pertama order dalam 30 hari terakhir.', 'action' => 'Follow up kepuasan dan minta review.'],
            'promising' => ['label' => 'Promising', 'icon' => 'bi-graph-up-arrow', 'color' => '#10b981', 'bg' => '#f0fdf4', 'desc' => 'Baru 1× order, antara 31–90 hari lalu.', 'action' => 'Dorong pembelian kedua dengan penawaran.'],
            'at_risk' => ['label' => 'At Risk', 'icon' => 'bi-exclamation-triangle', 'color' => '#f97316', 'bg' => '#fff7ed', 'desc' => 'Pernah order minimal 2×, terakhir lebih dari 90 hari.', 'action' => 'Lakukan re-engagement dengan promo.'],
            'lost' => ['label' => 'Lost', 'icon' => 'bi-person-dash', 'color' => '#94a3b8', 'bg' => '#f8fafc', 'desc' => 'Tidak ada order lebih dari 180 hari.', 'action' => 'Win-back campaign dengan penawaran khusus.'],
            'big_spender' => ['label' => 'Big Spender', 'icon' => 'bi-gem', 'color' => '#8b5cf6', 'bg' => '#faf5ff', 'desc' => 'Total belanja minimal Rp1 juta.', 'action' => 'Berikan layanan prioritas dan penawaran premium.'],
        ];
    }

    public static function classify(int $orderCount, int $daysSinceLast, float $totalSpent): string
    {
        if ($orderCount >= 3 && $daysSinceLast <= 60) return 'champions';
        if ($orderCount >= 2 && $daysSinceLast <= 90) return 'loyal';
        if ($orderCount === 1 && $daysSinceLast <= 30) return 'new';
        if ($daysSinceLast > 180) return 'lost';
        if ($orderCount >= 2 && $daysSinceLast > 90) return 'at_risk';
        if ($totalSpent >= 1_000_000) return 'big_spender';
        return 'promising';
    }

    public static function formatElapsedDays(int $days): string
    {
        $days = max(0, $days);

        if ($days < 7) {
            return $days.' hari lalu';
        }

        if ($days < 30) {
            return intdiv($days, 7).' minggu lalu';
        }

        if ($days < 365) {
            return intdiv($days, 30).' bulan lalu';
        }

        $years = intdiv($days, 365);
        $months = intdiv($days % 365, 30);

        return $months > 0
            ? $years.' tahun '.$months.' bulan lalu'
            : $years.' tahun lalu';
    }

    public function importPage()
    {
        return view('admin.crm.marketplace.import', [
            'stores' => $this->stores(),
            'recentImports' => MarketplaceImportBatch::query()
                ->where('source_type', 'marketplace_crm')
                ->with('store:id,name')
                ->latest()
                ->limit(10)
                ->get(),
        ]);
    }

    public function import(Request $request, MarketplaceCrmImportService $service)
    {
        // File besar membutuhkan waktu lebih dari default PHP 30 detik.
        // Service tetap menyimpan data secara bulk/chunk agar tidak menjalankan
        // ribuan query satu per satu.
        set_time_limit(300);

        $data = $request->validate([
            'store_id' => ['required', 'integer', 'exists:stores,id'],
            'files' => ['required', 'array', 'min:1', 'max:20'],
            'files.*' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:20480'],
        ]);

        $stats = [
            'files' => 0,
            'rows' => 0,
            'orders' => 0,
            'items' => 0,
            'inserted_orders' => 0,
            'updated_orders' => 0,
            'inserted_customers' => 0,
            'updated_customers' => 0,
            'collapsed_item_rows' => 0,
        ];
        $failedFiles = [];

        foreach ($data['files'] as $file) {
            $storedPath = null;

            try {
                $storedPath = $file->store('imports/marketplace-crm');
                $fileStats = $service->import(
                    Storage::disk('local')->path($storedPath),
                    (int) $data['store_id'],
                    $file->getClientOriginalName(),
                    $request->user()?->id
                );

                foreach (array_keys($stats) as $key) {
                    if ($key === 'files') {
                        continue;
                    }

                    $stats[$key] += (int) ($fileStats[$key] ?? 0);
                }
                $stats['files']++;
            } catch (\Throwable $e) {
                report($e);
                $failedFiles[] = $file->getClientOriginalName().' ('.$this->importFailureReason($e).')';
            } finally {
                if ($storedPath) {
                    Storage::disk('local')->delete($storedPath);
                }
            }
        }

        if ($stats['files'] === 0) {
            return back()
                ->withInput()
                ->withErrors(['import' => 'Tidak ada file yang berhasil di-import. Gagal: '.implode(', ', $failedFiles).'.']);
        }

        $message = sprintf(
            'Import %d file selesai: %d order baru, %d order di-update, %d customer baru.',
            $stats['files'],
            $stats['inserted_orders'],
            $stats['updated_orders'],
            $stats['inserted_customers']
        );

        if ($failedFiles) {
            $message .= ' Gagal: '.implode(', ', $failedFiles).'.';
        }

        return redirect()
            ->route('admin.crm.marketplace.dashboard')
            ->with('success', $message.' File yang sama aman di-import ulang.');
    }

    private function importFailureReason(\Throwable $e): string
    {
        return str_contains(strtolower($e->getMessage()), 'database is locked')
            ? 'database sedang sibuk, coba lagi'
            : 'terjadi kesalahan';
    }

    private function ordersQuery(?int $storeId = null)
    {
        return MarketplaceOrder::query()
            ->whereNotNull('channel_order_id')
            ->when($storeId, fn ($q) => $q->where('store_id', $storeId));
    }

    private function parseOrderDate(mixed $value): ?Carbon
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            return Carbon::createFromFormat('Y-m-d', trim($value))->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }

    private function applyOrderDateRange($query, ?Carbon $dateFrom, ?Carbon $dateTo): void
    {
        if (! $dateFrom && ! $dateTo) {
            return;
        }

        $applyBounds = static function ($query) use ($dateFrom, $dateTo): void {
            if ($dateFrom) {
                $query->where('ordered_at', '>=', $dateFrom);
            }
            if ($dateTo) {
                $query->where('ordered_at', '<=', $dateTo);
            }
        };
        $applyLegacyBounds = static function ($query) use ($dateFrom, $dateTo): void {
            if ($dateFrom) {
                $query->where('order_date', '>=', $dateFrom);
            }
            if ($dateTo) {
                $query->where('order_date', '<=', $dateTo);
            }
        };

        $query->where(function ($query) use ($applyBounds, $applyLegacyBounds): void {
            $query->where(function ($query) use ($applyBounds): void {
                $applyBounds($query);
            })->orWhere(function ($query) use ($applyLegacyBounds): void {
                $query->whereNull('ordered_at');
                $applyLegacyBounds($query);
            });
        });
    }

    private function stores()
    {
        return Store::with('channel')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    private function customerProfiles(?int $storeId = null)
    {
        $baseQuery = DB::table('customers')
            ->join('marketplace_orders', 'marketplace_orders.customer_id', '=', 'customers.id')
            ->whereNotNull('marketplace_orders.channel_order_id')
            ->where('marketplace_orders.status', '!=', 'cancelled')
            ->when($storeId, fn ($q) => $q->where('marketplace_orders.store_id', $storeId));

        // Recency harus dibandingkan dengan hari ini. Menggunakan tanggal order
        // terakhir di dataset membuat data historis terlihat lebih baru dari
        // kondisi sebenarnya dan mengacaukan segment Lost/At Risk.
        $analysisDate = now()->startOfDay();
        $whatsappMessages = DB::table('whatsapp_messages as wm')
            ->where('wm.module', 'marketplace_crm')
            ->where('wm.reference_type', Customer::class)
            ->whereColumn('wm.reference_id', 'customers.id')
            ->where('wm.direction', 'outbound');

        return $baseQuery
            ->select('customers.id', 'customers.name', 'customers.phone', 'customers.city', 'customers.province')
            ->selectRaw('COUNT(DISTINCT marketplace_orders.id) as marketplace_order_count')
            ->selectRaw('SUM(marketplace_orders.total_amount) as marketplace_total_spent')
            ->selectRaw('SUM(COALESCE(NULLIF(marketplace_orders.total_paid_customer, 0), marketplace_orders.total_amount)) as marketplace_total_paid')
            ->selectRaw('MAX(marketplace_orders.payment_method) as marketplace_payment_method')
            ->selectRaw('MAX(COALESCE(marketplace_orders.ordered_at, marketplace_orders.order_date)) as last_marketplace_order_at')
            ->selectRaw('MIN(COALESCE(marketplace_orders.ordered_at, marketplace_orders.order_date)) as first_marketplace_order_at')
            ->selectSub((clone $whatsappMessages)->selectRaw('COUNT(*)'), 'whatsapp_message_count')
            ->selectSub((clone $whatsappMessages)->where('wm.status', 'sent')->selectRaw('COUNT(*)'), 'whatsapp_sent_count')
            ->selectSub((clone $whatsappMessages)->select('wm.status')->latest('wm.id')->limit(1), 'whatsapp_last_status')
            ->groupBy('customers.id', 'customers.name', 'customers.phone', 'customers.city', 'customers.province')
            ->get()
            ->map(function ($customer) use ($analysisDate) {
                $days = $customer->last_marketplace_order_at
                    ? Carbon::parse($customer->last_marketplace_order_at)->startOfDay()->diffInDays($analysisDate->copy()->startOfDay())
                    : 99999;
                $days = (int) $days;
                $customer->days_since_last_order = $days;
                $customer->segment = self::classify(
                    (int) $customer->marketplace_order_count,
                    (int) $days,
                    (float) $customer->marketplace_total_spent
                );
                $customer->wa_phone = $this->normalizePhone($customer->phone);
                return $customer;
            });
    }

    private function normalizePhone(?string $phone): string
    {
        $phone = preg_replace('/\D+/', '', (string) $phone);
        if ($phone === '') return '';
        if (str_starts_with($phone, '0')) return '62' . substr($phone, 1);
        if (str_starts_with($phone, '8')) return '62' . $phone;
        return $phone;
    }

    private function prospectItemCustomerIds(?int $storeId, string $needle): Collection
    {
        $needle = trim($needle);
        if ($needle === '') {
            return collect();
        }

        $like = '%'.$needle.'%';

        return MarketplaceOrderItem::query()
            ->join('marketplace_orders as matched_orders', function ($join): void {
                $join->on('matched_orders.id', '=', 'marketplace_order_items.marketplace_order_id')
                    ->orOn('matched_orders.id', '=', 'marketplace_order_items.order_id');
            })
            ->whereNotNull('matched_orders.channel_order_id')
            ->where('matched_orders.status', '!=', 'cancelled')
            ->when($storeId, fn ($query) => $query->where('matched_orders.store_id', $storeId))
            ->where(function ($query) use ($like): void {
                $query->where('marketplace_order_items.item_name', 'like', $like)
                    ->orWhere('marketplace_order_items.item_name_snapshot', 'like', $like)
                    ->orWhere('marketplace_order_items.variant_name', 'like', $like)
                    ->orWhere('marketplace_order_items.variant_snapshot', 'like', $like)
                    ->orWhere('marketplace_order_items.model_sku', 'like', $like)
                    ->orWhere('marketplace_order_items.item_sku', 'like', $like)
                    ->orWhere('marketplace_order_items.external_sku', 'like', $like)
                    ->orWhere('marketplace_order_items.item_code_snapshot', 'like', $like)
                    ->orWhere('marketplace_order_items.marketplace_sku', 'like', $like);
            })
            ->distinct()
            ->pluck('matched_orders.customer_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->values();
    }

    private function attachProspectItems(Collection $prospects, ?int $storeId, bool $withItems = true): Collection
    {
        $customerIds = $prospects->pluck('id')->filter()->map(fn ($id) => (int) $id)->values();
        if ($customerIds->isEmpty()) {
            return $prospects;
        }

        $orders = MarketplaceOrder::query()
            ->whereIn('customer_id', $customerIds)
            ->whereNotNull('channel_order_id')
            ->where('status', '!=', 'cancelled')
            ->when($storeId, fn ($query) => $query->where('store_id', $storeId))
            ->orderByRaw('COALESCE(ordered_at, order_date) asc')
            ->orderBy('id')
            ->with('store:id,name')
            ->get([
                'id', 'customer_id', 'store_id', 'channel_order_id', 'external_order_id',
                'order_status', 'status', 'ordered_at', 'order_date', 'payment_method',
                'total_amount', 'total_paid_customer', 'subtotal_items', 'shipping_fee_customer',
                'voucher_discount', 'other_discount', 'buyer_name', 'buyer_phone',
                'buyer_username', 'shipping_address', 'shipping_city', 'shipping_province',
                'shipping_postal_code', 'shipping_awb_no',
            ]);

        $firstOrdersByCustomer = $orders->groupBy('customer_id')->map->first();
        $orderIds = $firstOrdersByCustomer->pluck('id')->map(fn ($id) => (int) $id)->values();
        if ($orderIds->isEmpty()) {
            return $prospects;
        }

        if (! $withItems) {
            return $prospects->map(function ($prospect) use ($firstOrdersByCustomer) {
                $order = $firstOrdersByCustomer->get((int) $prospect->id);
                $prospect->prospect_order = $order;
                $prospect->prospect_items = collect();

                return $prospect;
            });
        }

        $items = MarketplaceOrderItem::query()
            ->where(function ($query) use ($orderIds) {
                $query->whereIn('marketplace_order_id', $orderIds)
                    ->orWhereIn('order_id', $orderIds);
            })
            ->orderBy('line_no')
            ->orderBy('id')
            ->get([
                'id', 'order_id', 'marketplace_order_id', 'item_name', 'item_name_snapshot',
                'variant_name', 'variant_snapshot', 'model_sku', 'item_sku',
                'external_sku', 'external_item_id', 'external_model_id', 'item_code_snapshot', 'qty', 'internal_item_id',
                'mapping_status', 'marketplace_sku', 'price', 'price_after_discount', 'line_net_amount',
            ]);

        $itemsByOrder = $items->groupBy(function ($item) {
            return (int) ($item->marketplace_order_id ?: $item->order_id);
        });

        return $prospects->map(function ($prospect) use ($firstOrdersByCustomer, $itemsByOrder) {
            $order = $firstOrdersByCustomer->get((int) $prospect->id);
            $prospect->prospect_order = $order;
            $prospect->prospect_items = $order
                ? $itemsByOrder->get((int) $order->id, collect())
                : collect();

            return $prospect;
        });
    }

    /**
     * Pakai mapping variant dari halaman Produk Marketplace saat item order
     * belum memiliki internal_item_id sendiri.
     */
    private function attachProductPageMappings(
        Collection $items,
        Collection $orders,
    ): Collection {
        $orderById = $orders->keyBy(fn ($order) => (int) $order->id);
        $storeIds = $orders->pluck('store_id')->filter()->unique()->values();
        if ($items->isEmpty() || $storeIds->isEmpty()) {
            return $items;
        }

        $normalize = static fn ($value): string => preg_replace('/\s+/u', ' ', mb_strtolower(trim((string) $value))) ?? '';
        $products = MarketplaceProduct::query()
            ->whereIn('store_id', $storeIds)
            ->with('models:id,marketplace_product_id,model_name,model_sku')
            ->get(['id', 'store_id', 'item_id', 'item_name', 'item_sku']);
        if ($products->isEmpty()) {
            return $items;
        }

        $productsByTitle = $products
            ->filter(fn ($product) => $normalize($product->item_name) !== '')
            ->groupBy(fn ($product) => (int) $product->store_id.'|'.$normalize($product->item_name));
        $productsByItemId = $products
            ->filter(fn ($product) => trim((string) $product->item_id) !== '')
            ->keyBy(fn ($product) => (int) $product->store_id.'|'.$normalize($product->item_id));
        $productsByModelSku = $products
            ->flatMap(function ($product) use ($normalize) {
                return $product->models
                    ->filter(fn ($model) => trim((string) $model->model_sku) !== '')
                    ->mapWithKeys(fn ($model) => [
                        (int) $product->store_id.'|'.$normalize($model->model_sku) => $product,
                    ]);
            });
        $modelSkus = $products->flatMap(fn ($product) => $product->models->pluck('model_sku'))
            ->filter(fn ($sku) => trim((string) $sku) !== '')
            ->map(fn ($sku) => trim((string) $sku))
            ->unique()
            ->values();
        if ($modelSkus->isEmpty()) {
            return $items;
        }

        $mappingIndex = [];
        SkuMapping::query()
            ->whereIn('marketplace_sku', $modelSkus)
            ->with(['item:id,code,name,item_category_id', 'item.category:id,code,name'])
            ->get()
            ->each(function ($mapping) use (&$mappingIndex, $normalize): void {
                $sku = $normalize($mapping->marketplace_sku);
                if ($sku === '' || ! $mapping->item) {
                    return;
                }

                $channel = strtolower(trim((string) ($mapping->channel_code ?? '')));
                $key = $channel !== '' ? $channel.'|'.$sku : 'global|'.$sku;
                $mappingIndex[$key] ??= $mapping;
            });

        $channelByStore = Store::query()
            ->with('channel:id,code')
            ->whereIn('id', $storeIds)
            ->get(['id', 'channel_id'])
            ->mapWithKeys(fn ($store) => [(int) $store->id => strtolower(trim((string) ($store->channel?->code ?? ''))) ]);

        $mappedItems = $items->map(function ($item) use ($orderById, $productsByTitle, $productsByItemId, $productsByModelSku, $channelByStore, $mappingIndex, $normalize): mixed {
            if ($item->internalItem) {
                return $item;
            }

            $orderId = (int) ($item->marketplace_order_id ?: $item->order_id);
            $order = $orderById->get($orderId);
            $storeKey = (int) ($order?->store_id ?? 0).'|';
            $itemIdCandidates = collect([$item->external_item_id])
                ->map($normalize)
                ->filter()
                ->unique();
            $modelSkuCandidates = collect([
                $item->external_model_id,
                $item->model_sku,
                $item->item_sku,
                $item->external_sku,
                $item->marketplace_sku,
            ])
                ->map($normalize)
                ->filter()
                ->unique();
            $titleCandidates = collect([$item->item_name_snapshot, $item->item_name])
                ->map($normalize)
                ->filter()
                ->unique();
            $product = $itemIdCandidates
                ->map(fn ($externalItemId) => $productsByItemId->get($storeKey.$externalItemId))
                ->filter()
                ->first();
            $product ??= $modelSkuCandidates
                ->map(fn ($sku) => $productsByModelSku->get($storeKey.$sku))
                ->filter()
                ->first();
            $product ??= $titleCandidates
                ->map(fn ($title) => $productsByTitle->get($storeKey.$title)?->first())
                ->filter()
                ->first();
            if (! $product) {
                return $item;
            }

            $variantCandidates = collect([$item->variant_name, $item->variant_snapshot])
                ->map($normalize)
                ->filter()
                ->unique();
            $models = $product->models->filter(fn ($model) => trim((string) $model->model_sku) !== '');
            $model = $variantCandidates
                ->map(fn ($variant) => $models->first(fn ($candidate) => $normalize($candidate->model_name) === $variant))
                ->filter()
                ->first();
            if (! $model && $models->count() === 1) {
                $model = $models->first();
            }
            if (! $model) {
                return $item;
            }

            $sku = $normalize($model->model_sku);
            $channel = $channelByStore->get((int) ($order?->store_id ?? 0), '');
            $mapping = $mappingIndex[$channel.'|'.$sku] ?? $mappingIndex['global|'.$sku] ?? null;
            if (! $mapping?->item) {
                return $item;
            }

            $item->setRelation('internalItem', $mapping->item);
            $item->prospect_mapping_source = 'marketplace_product';

            return $item;
        });

        return $mappedItems;
    }

    private function aggregateDashboardChart(Collection $orders, int $days): Collection
    {
        if ($days < 90) {
            return $orders->map(function ($row) {
                $row->label = Carbon::parse($row->date)->format('d/m');
                return $row;
            });
        }

        $groupByMonth = $days >= 365;
        $grouped = $orders->groupBy(function ($row) use ($groupByMonth) {
            $date = Carbon::parse($row->date);
            return ($groupByMonth ? $date->startOfMonth() : $date->startOfWeek())->toDateString();
        });

        return $grouped->map(function (Collection $group, string $period) use ($groupByMonth) {
            $start = Carbon::parse($period);
            $label = $groupByMonth
                ? $start->format('M Y')
                : $start->format('d/m');
            $tooltip = $groupByMonth
                ? $start->format('M Y')
                : $start->format('d M').'–'.$start->copy()->addDays(6)->format('d M');

            return (object) [
                'date' => $period,
                'label' => $label,
                'tooltip' => $tooltip,
                'orders' => $group->sum('orders'),
                'revenue' => $group->sum('revenue'),
                'cancelled' => $group->sum('cancelled'),
            ];
        })->values();
    }

    private function sortParameters(Request $request, array $allowed, string $default): array
    {
        $sort = (string) $request->query('sort', $default);
        $sort = in_array($sort, $allowed, true) ? $sort : $default;
        $direction = strtolower((string) $request->query('direction', 'desc')) === 'asc' ? 'asc' : 'desc';

        return [$sort, $direction];
    }

    private function sortCollection(Collection $items, array $callbacks, string $sort, string $direction): Collection
    {
        $callback = $callbacks[$sort] ?? reset($callbacks);

        return $direction === 'asc'
            ? $items->sortBy($callback)->values()
            : $items->sortByDesc($callback)->values();
    }

    private function paginateCollection(Collection $items, Request $request, int $perPage = 25): LengthAwarePaginator
    {
        $page = LengthAwarePaginator::resolveCurrentPage();
        $slice = $items->slice(($page - 1) * $perPage, $perPage)->values();

        return new LengthAwarePaginator(
            $slice,
            $items->count(),
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );
    }
}
