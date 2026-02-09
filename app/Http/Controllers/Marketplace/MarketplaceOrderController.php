<?php

namespace App\Http\Controllers\Marketplace;

use App\Http\Controllers\Controller;
use App\Models\MarketplaceOrder;
use App\Models\MarketplaceOrderItem;
use App\Models\MarketplaceStore;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

// ✅ WAJIB ADA INI

class MarketplaceOrderController extends Controller
{
    public function index(Request $request)
    {
        $stores = MarketplaceStore::with('channel')
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
        $order->load(['store.channel', 'items.item']);

        return view('marketplace.orders.show', compact('order'));
    }

    /**
     * Stage 1: form input manual (simple).
     * Nanti bisa diganti/import dari CSV/API.
     */

    public function create()
    {
        $stores = MarketplaceStore::with('channel')
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
            'store_id' => ['required', 'exists:marketplace_stores,id'],
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

}
