<?php

namespace App\Http\Controllers\Marketplace;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Item;
use App\Models\MarketplaceOrder;
use App\Models\MarketplaceOrderItem;
use App\Models\MarketplaceOrderSettlement;
use App\Models\Store;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

// ✅ WAJIB ADA INI

class MarketplaceOrderController extends Controller
{
    public function index(Request $request)
    {
        $stores = Store::with('channel')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $query = MarketplaceOrder::query()
            ->with([
                'store.channel',
                'customer',
            ])
            ->withCount('items');

        if ($storeId = $request->input('store_id')) {
            $query->where('store_id', (int) $storeId);
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        // Filter by CUSTOMER
        if ($customerId = $request->input('customer_id')) {
            $query->where('customer_id', (int) $customerId);
        }

        if ($q = trim((string) $request->input('q'))) {
            $query->where(function ($sub) use ($q) {
                $sub->where('external_order_id', 'like', '%' . $q . '%')
                    ->orWhere('buyer_name', 'like', '%' . $q . '%');
            });
        }

        /**
         * Filter tanggal:
         * - Basis utama: shipped_at
         * - Fallback: kalau shipped_at NULL, pakai order_date
         * (jadi order yang belum shipped tetap bisa ikut range)
         */
        if ($dateFrom = $request->input('date_from')) {
            $query->where(function ($w) use ($dateFrom) {
                $w->whereDate('shipped_at', '>=', $dateFrom)
                    ->orWhere(function ($x) use ($dateFrom) {
                        $x->whereNull('shipped_at')
                            ->whereDate('order_date', '>=', $dateFrom);
                    });
            });
        }

        if ($dateTo = $request->input('date_to')) {
            $query->where(function ($w) use ($dateTo) {
                $w->whereDate('shipped_at', '<=', $dateTo)
                    ->orWhere(function ($x) use ($dateTo) {
                        $x->whereNull('shipped_at')
                            ->whereDate('order_date', '<=', $dateTo);
                    });
            });
        }

        // ✅ Order utama berdasarkan shipped_at, fallback order_date kalau null
        $orders = $query
            ->orderByRaw('COALESCE(shipped_at, order_date) DESC')
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        $statuses = [
            'new' => 'New',
            'packed' => 'Packed',
            'shipped' => 'Shipped',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
        ];

        return view('marketplace.orders.index', compact(
            'orders',
            'stores',
            'statuses'
        ));
    }

    public function show(MarketplaceOrder $order)
    {
        $order->load(['store.channel', 'items.internalItem']);

        // Coba tarik data terbaru dari API agar halaman terupdate otomatis
        if ($order->store && $order->store->channel?->code === 'shopee') {
            try {
                $shopee = app(\App\Services\Marketplace\MarketplaceApiGateway::class);
                
                // 1. Tarik Order Detail
                $resDetail = $shopee->getOrderDetail($order->store, [$order->channel_order_id]);
                $liveData = $resDetail['response']['order_list'][0] ?? null;

                // 2. Tarik Escrow Detail (Income)
                // Shopee kadang mengembalikan escrow_detail meski status masih READY_TO_SHIP (estimasi)
                $resEscrow = [];
                try {
                    $resEscrow = $shopee->getEscrowDetail($order->store, $order->channel_order_id);
                } catch (\Exception $e) {}

                if ($liveData) {
                    if (!empty($resEscrow['response']['order_income'])) {
                        $liveData['income_details'] = $resEscrow['response']['order_income'];
                    }
                    $order->raw_json = $liveData;
                    $order->save();
                }
            } catch (\Exception $e) {
                // Abaikan error koneksi/token agar halaman tetap terbuka
            }
        }

        [$resolvedAwb, $awbSource, $internalPackageNo] = $this->resolveTrackingNumberForDetail($order, $liveData ?? []);
        if (!empty($resolvedAwb) && $order->shipping_awb_no !== $resolvedAwb) {
            try {
                $order->forceFill(['shipping_awb_no' => $resolvedAwb])->save();
            } catch (\Throwable $e) {
                // Jangan ganggu rendering halaman jika save gagal
            }
        }

        // Hitung estimasi rata-rata persentase potongan admin dari settlement historis toko ini
        $estimatedFeeRatio = 0.15; // default 15%
        if ($order->store_id) {
            try {
                $avgFees = \Illuminate\Support\Facades\DB::table('marketplace_order_settlements')
                    ->join('marketplace_orders', 'marketplace_order_settlements.order_id', '=', 'marketplace_orders.id')
                    ->where('marketplace_order_settlements.store_id', $order->store_id)
                    ->where('marketplace_orders.subtotal_items', '>', 0)
                    ->selectRaw('SUM(COALESCE(commission_fee, 0) + COALESCE(service_fee, 0) + COALESCE(transaction_fee, 0) + COALESCE(affiliate_fee, 0) + COALESCE(activity_fee, 0) + COALESCE(shipping_insurance_fee, 0)) as total_fees, SUM(marketplace_orders.subtotal_items) as total_subtotal')
                    ->first();
                    
                if ($avgFees && $avgFees->total_subtotal > 0) {
                    $ratio = $avgFees->total_fees / $avgFees->total_subtotal;
                    // Batasi rasio wajar agar tidak anomali (antara 1% s/d 25%)
                    if ($ratio > 0.01 && $ratio < 0.25) {
                        $estimatedFeeRatio = $ratio;
                    }
                }
            } catch (\Exception $e) {
                // Abaikan error (misal karena kolom DB di prod berbeda) dan gunakan fallback 15%
                \Illuminate\Support\Facades\Log::warning('Gagal mengambil rata-rata fee: ' . $e->getMessage());
            }
        }

        $estimatedFeePct = round($estimatedFeeRatio * 100, 1);

        return view('marketplace.orders.show', compact(
            'order',
            'estimatedFeeRatio',
            'estimatedFeePct',
            'awbSource',
            'internalPackageNo'
        ));
    }

    public function updateSettlementTestFields(Request $request, MarketplaceOrder $order)
    {
        abort_unless(app()->environment(['local', 'testing']) || $request->user()?->role === 'owner', 403, 'Aksi ini hanya untuk owner/dev testing.');

        $data = $request->validate([
            'order_ams_commission_fee' => ['nullable', 'numeric', 'min:0'],
            'voucher_from_shopee'      => ['nullable', 'numeric', 'min:0'],
            'voucher_from_seller'      => ['nullable', 'numeric', 'min:0'],
        ]);

        $settlement = MarketplaceOrderSettlement::query()
            ->where('order_id', $order->id)
            ->first();

        abort_unless($settlement instanceof MarketplaceOrderSettlement, 404, 'Settlement untuk order ini belum tersedia.');

        $raw = is_array($settlement->raw_json) ? $settlement->raw_json : [];
        $changes = [];

        if (array_key_exists('order_ams_commission_fee', $data) && $data['order_ams_commission_fee'] !== null) {
            $value = (float) $data['order_ams_commission_fee'];
            $settlement->activity_fee = $value;
            $raw['order_ams_commission_fee'] = $value;
            $raw['ams_commission_fee'] = $value;
            $changes['order_ams_commission_fee'] = $value;
        }

        if (array_key_exists('voucher_from_shopee', $data) && $data['voucher_from_shopee'] !== null) {
            $value = (float) $data['voucher_from_shopee'];
            $raw['voucher_from_shopee'] = $value;
            $raw['voucher_from_platform'] = $value;
            $raw['platform_voucher'] = $value;
            $changes['voucher_from_shopee'] = $value;
        }

        if (array_key_exists('voucher_from_seller', $data) && $data['voucher_from_seller'] !== null) {
            $value = (float) $data['voucher_from_seller'];
            $settlement->seller_voucher = $value;
            $raw['voucher_from_seller'] = $value;
            $raw['seller_voucher_rebate'] = $value;
            $raw['seller_voucher'] = $value;
            $changes['voucher_from_seller'] = $value;
        }

        $settlement->raw_json = $raw;
        $settlement->save();

        return back()->with('success', 'Data testing settlement berhasil diperbarui: ' . implode(', ', array_map(
            fn ($key, $value) => $key . '=' . number_format((float) $value, 0, ',', '.'),
            array_keys($changes),
            array_values($changes)
        )));
    }

    /**
     * Resolve tracking number for detail page.
     *
     * Priority:
     * 1) valid local/live AWB
     * 2) Shopee tracking API by booking/order sn
     * 3) last resort: order detail package tracking number
     */
    private function resolveTrackingNumberForDetail(MarketplaceOrder $order, array $liveData = []): array
    {
        $statusText = strtolower((string) ($order->order_status ?: $order->status));
        $trackableStatuses = ['ready_to_ship', 'processed', 'shipped', 'completed', 'to_confirm_receive', 'ready_to_handover'];
        $canResolveFromApi = in_array($statusText, $trackableStatuses, true);

        $cleanAwb = static function ($value) {
            $awb = trim((string) $value);
            if ($awb === '') {
                return null;
            }

            if (preg_match('/^OFG/i', $awb)) {
                return null;
            }

            return $awb;
        };

        $awbCandidates = [
            data_get($liveData, 'tracking_no'),
            data_get($liveData, 'shipping_document_info.tracking_number'),
            $order->shipping_awb_no,
            data_get($liveData, 'package_list.0.tracking_number'),
            data_get($liveData, 'package_list.0.package_number'),
        ];

        $awb = null;
        $awbSource = null;
        foreach ($awbCandidates as $candidate) {
            $candidate = $cleanAwb($candidate);
            if (!empty($candidate)) {
                $awb = $candidate;
                $awbSource = 'local';
                break;
            }
        }

        $packageNoCandidate = data_get($liveData, 'package_list.0.package_number');
        $internalPackageNo = !empty($packageNoCandidate) && preg_match('/^OFG/i', (string) $packageNoCandidate)
            ? $packageNoCandidate
            : null;

        if ($awb || ! $canResolveFromApi || !$order->store || strtolower((string) ($order->store->channel?->code ?? '')) !== 'shopee') {
            return [$awb, $awbSource, $internalPackageNo];
        }

        try {
            $shopee = app(\App\Services\Marketplace\MarketplaceApiGateway::class);

            if (!empty($order->booking_sn) && method_exists($shopee, 'getBookingTrackingNumber')) {
                $resp = $shopee->getBookingTrackingNumber($order->store, $order->booking_sn);
                $awb = $cleanAwb($resp['response']['tracking_number'] ?? null);
                if ($awb) {
                    $awbSource = 'booking_tracking_number';
                }
            }

            if (!$awb && method_exists($shopee, 'getTrackingNumber')) {
                $resp = $shopee->getTrackingNumber($order->store, $order->channel_order_id);
                $awb = $cleanAwb($resp['response']['tracking_number'] ?? null);
                if ($awb) {
                    $awbSource = 'tracking_number';
                }
            }

            if (!$awb && method_exists($shopee, 'getOrderDetail')) {
                $resp = $shopee->getOrderDetail($order->store, [$order->channel_order_id]);
                $detail = $resp['response']['order_list'][0] ?? null;
                $awb = $cleanAwb(data_get($detail, 'package_list.0.tracking_number'));
                if ($awb) {
                    $awbSource = 'order_detail';
                }
            }
        } catch (\Throwable $e) {
            // Tetap render halaman meski tracking API gagal
        }

        return [$awb, $awbSource, $internalPackageNo];
    }

    /**
     * Stage 1: form input manual (simple).
     * Nanti bisa diganti/import dari CSV/API.
     */

    public function create()
    {
        $stores = Store::with('channel')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $order = null;

        // Kalau kamu mau dropdown item internal, bisa kirim juga:
        // $items = Item::orderBy('code')->get();

        return view('marketplace.orders.create', compact('stores', 'order'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'store_id' => ['required', 'exists:stores,id'],
            'external_order_id' => ['required', 'string', 'max:100'],
            'external_invoice_no' => ['nullable', 'string', 'max:100'],
            'order_date' => ['required', 'date'],
            'buyer_name' => ['nullable', 'string', 'max:150'],
            'buyer_phone' => ['nullable', 'string', 'max:50'],
            'shipping_address' => ['nullable', 'string'],
            'remarks' => ['nullable', 'string'],

            // 🔹 dari quick search customer (opsional)
            'customer_id' => ['nullable', 'integer', 'exists:customers,id'],

            'lines' => ['array'],
            'lines.*.external_sku' => ['nullable', 'string', 'max:100'],
            'lines.*.item_id' => ['nullable', 'integer', 'exists:items,id'],
            'lines.*.item_name' => ['nullable', 'string', 'max:190'],
            'lines.*.qty' => ['nullable', 'numeric', 'min:0'],
            'lines.*.price' => ['nullable', 'numeric', 'min:0'],
        ]);

        $rawLines = $data['lines'] ?? [];

        // Filter baris kosong / qty 0
        $lines = [];
        foreach ($rawLines as $row) {
            $qty = (float) ($row['qty'] ?? 0);
            $price = (float) ($row['price'] ?? 0);
            $hasAnyId = !empty($row['external_sku']) || !empty($row['item_id']) || !empty($row['item_name']);

            if ($qty <= 0 || !$hasAnyId) {
                continue;
            }

            $lines[] = $row;
        }

        if (count($lines) === 0) {
            return back()
                ->withErrors(['lines' => 'Minimal satu item harus diisi dengan qty > 0.'])
                ->withInput();
        }

        $order = null;

        DB::transaction(function () use (&$order, $data, $lines) {

            // 🧩 1) Cari / buat Customer dulu
            // kalau ada customer_id dari quick search → pakai itu
            if (!empty($data['customer_id'])) {
                $customer = Customer::find($data['customer_id']);
            } else {
                // kalau tidak ada → auto-create/update dari buyer_name + buyer_phone
                $customer = $this->findOrCreateCustomerForOrder($data);
            }

            $subtotalItems = 0;

            // 🧩 2) Buat header order
            $order = MarketplaceOrder::create([
                'store_id' => $data['store_id'],
                'external_order_id' => $data['external_order_id'],
                'external_invoice_no' => $data['external_invoice_no'] ?? null,
                'order_date' => $data['order_date'],
                'status' => 'new',

                'customer_id' => $customer?->id,

                'buyer_name' => $data['buyer_name'] ?? null,
                'buyer_phone' => $data['buyer_phone'] ?? null,
                'shipping_address' => $data['shipping_address'] ?? null,
                'remarks' => $data['remarks'] ?? null,

                'subtotal_items' => 0,
                'shipping_fee_customer' => 0,
                'shipping_discount_platform' => 0,
                'voucher_discount' => 0,
                'other_discount' => 0,
                'total_paid_customer' => 0,
                'platform_fee_total' => 0,
                'net_payout_estimated' => 0,
                'payment_status' => 'unpaid',
            ]);

            // 🧩 3) Detail items
            $lineNo = 1;

            foreach ($lines as $row) {
                $qty = (float) $row['qty'];
                $price = (float) $row['price'];
                $gross = $qty * $price;

                $item = null;
                if (!empty($row['item_id'])) {
                    $item = Item::find($row['item_id']);
                }

                $itemCodeSnapshot = $item?->code ?: null;
                $itemNameSnapshot = $row['item_name'] ?? $item?->name;

                MarketplaceOrderItem::create([
                    'order_id' => $order->id,
                    'line_no' => $lineNo++,
                    'external_item_id' => null,
                    'external_sku' => $row['external_sku'] ?? null,
                    'item_id' => $item?->id,
                    'item_code_snapshot' => $itemCodeSnapshot,
                    'item_name_snapshot' => $itemNameSnapshot,
                    'variant_snapshot' => null,
                    'qty' => $qty,
                    'price_original' => $price,
                    'price_after_discount' => $price,
                    'line_discount' => 0,
                    'line_gross_amount' => $gross,
                    'line_net_amount' => $gross,
                    'hpp_unit_snapshot' => 0,
                    'hpp_total_snapshot' => 0,
                ]);

                $subtotalItems += $gross;
            }

            // 🧩 4) Update total di header
            $order->update([
                'subtotal_items' => $subtotalItems,
                'total_paid_customer' => $subtotalItems, // sementara
                'net_payout_estimated' => $subtotalItems,
            ]);
        });

        return redirect()
            ->route('marketplace.orders.show', $order)
            ->with('success', 'Marketplace order + customer berhasil dibuat.');
    }

    /**
     * Cari / buat Customer berdasarkan data buyer di order marketplace.
     *
     * Rule simpel:
     * - kalau ada phone → cari by phone
     * - kalau tidak ada, tapi ada email → cari by email
     * - kalau cuma nama → cek nama persis pertama kali
     * - kalau tidak ada apa2 → return null (tidak buat customer)
     */
    protected function findOrCreateCustomerForOrder(array $data): ?Customer
    {
        $name = trim($data['buyer_name'] ?? '');
        $phone = $this->normalizePhone($data['buyer_phone'] ?? '');
        $email = trim($data['buyer_phone'] ?? ''); // kalau nanti kamu mapping email sendiri, ganti ke buyer_email
        $address = $data['shipping_address'] ?? null;

        // kalau benar2 gak ada identitas, skip
        if ($name === '' && $phone === '' && $email === '') {
            return null;
        }

        $query = Customer::query();

        // Prioritas cari by phone kalau ada
        if ($phone !== '') {
            $existing = $query->where('phone', $phone)->first();
            if ($existing) {
                // update data dasar kalau kosong
                $this->updateCustomerIfNeeded($existing, $name, $phone, $email, $address);
                return $existing;
            }
        }

        // Kalau ada email, coba cari by email
        if ($email !== '') {
            $existing = Customer::where('email', $email)->first();
            if ($existing) {
                $this->updateCustomerIfNeeded($existing, $name, $phone, $email, $address);
                return $existing;
            }
        }

        // kalau cuma nama, optional: bisa cari by nama persis
        if ($name !== '') {
            $existing = Customer::where('name', $name)->first();
            if ($existing) {
                $this->updateCustomerIfNeeded($existing, $name, $phone, $email, $address);
                return $existing;
            }
        }

        // Tidak ada yang ketemu → buat baru
        return Customer::create([
            'name' => $name !== '' ? $name : ($phone ?: 'Buyer Marketplace'),
            'phone' => $phone ?: null,
            'email' => $email ?: null,
            'address' => $address,
            'active' => true,
        ]);
    }

/**
 * Update data customer kalau masih kosong.
 */
    protected function updateCustomerIfNeeded(Customer $customer, ?string $name, ?string $phone, ?string $email, ?string $address): void
    {
        $dirty = false;

        if ($name && !$customer->name) {
            $customer->name = $name;
            $dirty = true;
        }

        if ($phone && !$customer->phone) {
            $customer->phone = $phone;
            $dirty = true;
        }

        if ($email && !$customer->email) {
            $customer->email = $email;
            $dirty = true;
        }

        if ($address && !$customer->address) {
            $customer->address = $address;
            $dirty = true;
        }

        if ($dirty) {
            $customer->save();
        }
    }

/**
 * Normalisasi no HP sederhana.
 * Contoh: buang spasi, ganti leading 0 → +62 kalau kamu mau.
 */
    protected function normalizePhone(?string $phone): string
    {
        if (!$phone) {
            return '';
        }

        $clean = preg_replace('/\s+/', '', $phone); // buang spasi

        // kamu bisa tambahkan rule lain kalau mau (+62 vs 0, dll)
        return $clean;
    }

    public function salesSummary(Request $request)
    {
        $month = (string) $request->get('month', now()->format('Y-m'));
        $channel = (string) $request->get('channel', 'shopee');
        $dateField = (string) $request->get('date_field', 'ordered_at');
        $storeId = (int) $request->get('store_id', 0);
        $qItem = trim((string) $request->get('q_item', ''));

        $start = \Carbon\Carbon::createFromFormat('Y-m', $month)->startOfMonth()->startOfDay();
        $end = (clone $start)->addMonth(); // [start, end)

        $allowed = ['ordered_at', 'paid_at', 'shipped_at', 'delivered_at', 'completed_at', 'settlement_time'];
        if (!in_array($dateField, $allowed, true)) {
            $dateField = 'ordered_at';
        }

        $dateExpression = $dateField === 'settlement_time'
            ? 's.settlement_time'
            : 'o.' . $dateField;

        $channelId = DB::table('channels')->where('code', $channel)->value('id');

        $itemTotals = DB::table('marketplace_order_items as oi')
            ->selectRaw('oi.marketplace_order_id as order_id, COALESCE(SUM(oi.qty), 0) as total_qty')
            ->groupBy('oi.marketplace_order_id');

        $base = DB::table('marketplace_orders as o')
            ->leftJoin('stores as st', 'st.id', '=', 'o.store_id')
            ->leftJoin('marketplace_order_settlements as s', 's.order_id', '=', 'o.id')
            ->leftJoinSub($itemTotals, 'itot', 'itot.order_id', '=', 'o.id')
            ->whereNotNull($dateExpression)
            ->where($dateExpression, '>=', $start->toDateTimeString())
            ->where($dateExpression, '<', $end->toDateTimeString())
            ->when($channelId, fn ($q) => $q->where('st.channel_id', $channelId))
            ->when($storeId > 0, fn ($q) => $q->where('o.store_id', $storeId));

        $productSalesExpr = 'CASE WHEN COALESCE(o.subtotal_items, 0) > 0 THEN o.subtotal_items ELSE COALESCE(o.total_amount, 0) END';
        $shippingExpr = 'COALESCE(o.shipping_fee_customer, 0)';
        $platformFeeExpr = 'COALESCE(s.commission_fee, 0) + COALESCE(s.service_fee, 0) + COALESCE(s.transaction_fee, 0) + COALESCE(s.affiliate_fee, 0) + COALESCE(s.activity_fee, 0) + COALESCE(s.shipping_insurance_fee, 0) + COALESCE(s.escrow_tax, 0)';
        $refundExpr = 'COALESCE(s.drc_adjustable_refund, 0)';

        $summary = (clone $base)
            ->selectRaw('COUNT(DISTINCT o.id) as total_orders')
            ->selectRaw('COALESCE(SUM(COALESCE(itot.total_qty, 0)), 0) as total_qty')
            ->selectRaw("COALESCE(SUM({$productSalesExpr}), 0) as subtotal")
            ->selectRaw("COALESCE(SUM({$productSalesExpr} + {$shippingExpr}), 0) as gross_sales")
            ->selectRaw('COALESCE(SUM(COALESCE(o.voucher_discount, 0) + COALESCE(o.other_discount, 0)), 0) as discount_total')
            ->selectRaw("COALESCE(SUM({$shippingExpr}), 0) as shipping_fee")
            ->selectRaw("COALESCE(SUM({$platformFeeExpr}), 0) as platform_fee")
            ->selectRaw("COALESCE(SUM({$refundExpr}), 0) as refund_total")
            ->selectRaw('COALESCE(SUM(COALESCE(s.final_income, 0)), 0) as net_payout')
            ->first();

        $summary->aov = ((int) $summary->total_orders) > 0
        ? ((float) $summary->gross_sales / (int) $summary->total_orders)
        : 0;

        $itemDateExpression = $dateField === 'settlement_time' ? 's.settlement_time' : 'o.' . $dateField;
        $skuExpression = "COALESCE(NULLIF(oi.marketplace_sku, ''), NULLIF(oi.model_sku, ''), NULLIF(oi.item_sku, ''), NULLIF(oi.external_sku, ''))";
        $lineSalesExpression = 'CASE WHEN COALESCE(oi.line_net_amount, 0) > 0 THEN oi.line_net_amount WHEN COALESCE(oi.price, 0) > 0 THEN oi.price * COALESCE(oi.qty, 0) ELSE 0 END';

        $itemBase = DB::table('marketplace_order_items as oi')
            ->join('marketplace_orders as o', 'o.id', '=', 'oi.marketplace_order_id')
            ->leftJoin('marketplace_order_settlements as s', 's.order_id', '=', 'o.id')
            ->leftJoin('stores as st', 'st.id', '=', 'o.store_id')
            ->leftJoin('items as it', 'it.id', '=', 'oi.internal_item_id')
            ->leftJoin('item_categories as ic', 'ic.id', '=', 'it.item_category_id')
            ->whereNotNull($itemDateExpression)
            ->where($itemDateExpression, '>=', $start->toDateTimeString())
            ->where($itemDateExpression, '<', $end->toDateTimeString())
            ->when($channelId, fn ($q) => $q->where('st.channel_id', $channelId))
            ->when($storeId > 0, fn ($q) => $q->where('o.store_id', $storeId))
            ->when($qItem !== '', function ($q) use ($qItem, $skuExpression) {
                $q->where(function ($w) use ($qItem, $skuExpression) {
                    $w->whereRaw("{$skuExpression} LIKE ?", ["%{$qItem}%"])
                        ->orWhere('oi.item_name', 'like', "%{$qItem}%")
                        ->orWhere('oi.variant_name', 'like', "%{$qItem}%")
                        ->orWhere('it.name', 'like', "%{$qItem}%")
                        ->orWhere('ic.name', 'like', "%{$qItem}%");
                });
            });

        $itemRows = (clone $itemBase)
            ->groupBy(DB::raw($skuExpression), 'it.id', 'it.code', 'it.name', 'ic.code', 'ic.name')
            ->selectRaw("{$skuExpression} as sku")
            ->selectRaw("COALESCE(MAX(oi.item_name), {$skuExpression}) as name")
            ->selectRaw('MAX(it.id) as item_id, MAX(it.code) as item_code, MAX(it.name) as item_name')
            ->selectRaw("MAX(ic.code) as category_code, MAX(ic.name) as category_name")
            ->selectRaw('COALESCE(SUM(oi.qty), 0) as qty')
            ->selectRaw("COALESCE(SUM({$lineSalesExpression}), 0) as sales")
            ->selectRaw("ROUND(1.0 * COALESCE(SUM({$lineSalesExpression}), 0) / NULLIF(SUM(oi.qty), 0), 2) as avg_price");

        $items = (clone $itemRows)
            ->orderByDesc('sales')
            ->paginate(50)
            ->withQueryString();

        $categories = DB::query()
            ->fromSub($itemRows, 'q')
            ->selectRaw("COALESCE(q.category_code, 'UNMAPPED') as category_code")
            ->selectRaw("COALESCE(q.category_name, 'Unmapped') as category_name")
            ->selectRaw('SUM(q.qty) as total_qty, COALESCE(SUM(q.sales), 0) as total_sales')
            ->selectRaw('ROUND(1.0 * COALESCE(SUM(q.sales), 0) / NULLIF(SUM(q.qty), 0), 2) as avg_unit_price')
            ->groupBy('category_code', 'category_name')
            ->orderByDesc('total_sales')
            ->get();

        $stores = Store::with('channel')->where('is_active', true)->orderBy('name')->get();

        return view('marketplace.reports.sales_summary', compact(
            'month', 'channel', 'dateField', 'storeId',
            'start', 'end',
            'summary', 'stores',
            'items', 'categories', 'qItem'
        ));
    }

    public function salesSummaryCsv(Request $request): StreamedResponse
    {
        $month = (string) $request->get('month', now()->format('Y-m'));
        $channel = (string) $request->get('channel', 'shopee');
        $dateField = (string) $request->get('date_field', 'ordered_at');
        $storeId = (int) $request->get('store_id', 0);

        $start = Carbon::createFromFormat('Y-m', $month)->startOfMonth()->startOfDay();
        $end = (clone $start)->addMonth();

        $allowed = ['ordered_at', 'paid_at', 'shipped_at', 'delivered_at', 'completed_at', 'settlement_time'];
        if (!in_array($dateField, $allowed, true)) {
            $dateField = 'ordered_at';
        }

        $dateExpression = $dateField === 'settlement_time' ? 's.settlement_time' : 'o.' . $dateField;
        $channelId = DB::table('channels')->where('code', $channel)->value('id');
        $itemTotals = DB::table('marketplace_order_items as oi')
            ->selectRaw('oi.marketplace_order_id as order_id, COALESCE(SUM(oi.qty), 0) as total_qty')
            ->groupBy('oi.marketplace_order_id');

        $productSalesExpr = 'CASE WHEN COALESCE(o.subtotal_items, 0) > 0 THEN o.subtotal_items ELSE COALESCE(o.total_amount, 0) END';
        $platformFeeExpr = 'COALESCE(s.commission_fee, 0) + COALESCE(s.service_fee, 0) + COALESCE(s.transaction_fee, 0) + COALESCE(s.affiliate_fee, 0) + COALESCE(s.activity_fee, 0) + COALESCE(s.shipping_insurance_fee, 0) + COALESCE(s.escrow_tax, 0)';

        $q = DB::table('marketplace_orders as o')
            ->leftJoin('stores as st', 'st.id', '=', 'o.store_id')
            ->leftJoin('marketplace_order_settlements as s', 's.order_id', '=', 'o.id')
            ->leftJoinSub($itemTotals, 'itot', 'itot.order_id', '=', 'o.id')
            ->whereNotNull($dateExpression)
            ->where($dateExpression, '>=', $start->toDateTimeString())
            ->where($dateExpression, '<', $end->toDateTimeString())
            ->when($channelId, fn ($query) => $query->where('st.channel_id', $channelId))
            ->when($storeId > 0, fn ($query) => $query->where('o.store_id', $storeId));

        // export harian (biar CSV berguna)
        $rows = $q->selectRaw("DATE({$dateExpression}) as day")
            ->selectRaw('COUNT(DISTINCT o.id) as orders')
            ->selectRaw('COALESCE(SUM(COALESCE(itot.total_qty, 0)), 0) as qty')
            ->selectRaw("COALESCE(SUM({$productSalesExpr} + COALESCE(o.shipping_fee_customer, 0)), 0) as gross_sales")
            ->selectRaw("COALESCE(SUM({$platformFeeExpr}), 0) as platform_fee")
            ->selectRaw('COALESCE(SUM(COALESCE(s.drc_adjustable_refund, 0)), 0) as refund_total')
            ->selectRaw('COALESCE(SUM(COALESCE(s.final_income, 0)), 0) as net_payout')
            ->groupBy(DB::raw("DATE({$dateExpression})"))
            ->orderBy('day')
            ->cursor();

        $filename = "sales_summary_{$channel}_{$month}_{$dateField}.csv";

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['day', 'orders', 'qty', 'gross_sales', 'platform_fee', 'refund_total', 'net_payout']);

            foreach ($rows as $r) {
                fputcsv($out, [
                    $r->day,
                    (int) $r->orders,
                    (int) $r->qty,
                    (float) $r->gross_sales,
                    (float) $r->platform_fee,
                    (float) $r->refund_total,
                    (float) $r->net_payout,
                ]);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

}
