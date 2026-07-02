<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\StorefrontProduct;
use App\Models\StorefrontEvent;
use App\Models\StorefrontOrder;
use App\Models\StorefrontVisitor;
use App\Models\Warehouse;
use App\Services\Inventory\InventoryService;
use App\Services\Storefront\StockResolver;
use App\Services\WaNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CartController extends Controller
{
    public function index()
    {
        $cart     = session('cart', []);
        $channels = storefrontChannels();
        $products = storefrontProducts();
        $cartStock = $this->cartStockSnapshot($cart);
        $total    = array_sum(array_map(fn($i) => $i['price'] * $i['qty'], $cart));

        return view('storefront.cart', compact('cart', 'channels', 'products', 'total', 'cartStock'));
    }

    public function checkout(Request $request)
    {
        $cart     = $request->boolean('buy_now')
            ? session('checkout_buy_now', [])
            : session('cart', []);

        $this->logEvent($request, 'checkout_start', ['items_count' => count($cart)]);
        $selected = array_values(array_filter((array) $request->query('items', []), 'is_string'));

        if (!$request->boolean('buy_now') && !empty($selected)) {
            $cart = array_intersect_key($cart, array_flip($selected));
        }

        session(['checkout_active_cart' => $cart]);

        $channels = storefrontChannels();
        $products = storefrontProducts();
        $address  = session('checkout_address', []);
        $cartStock = $this->cartStockSnapshot($cart);
        $total    = array_sum(array_map(fn($i) => $i['price'] * $i['qty'], $cart));
        $uniqueCode = $this->checkoutUniqueCode($cart);
        $totalWeightKg = $this->cartTotalWeightKg($cart);

        return view('storefront.checkout', compact('cart', 'channels', 'products', 'address', 'total', 'cartStock', 'uniqueCode', 'totalWeightKg'));
    }

    public function address(Request $request)
    {
        $address = session('checkout_address', []);
        $returnTo = $this->checkoutReturnUrl($request->query('return_to'));

        return view('storefront.address', compact('address', 'returnTo'));
    }

    public function saveAddress(Request $request)
    {
        $data = $request->validate([
            'recipient_name' => ['required', 'string', 'max:80'],
            'phone' => ['required', 'string', 'max:24'],
            'province_id' => ['required', 'string', 'max:12'],
            'province_name' => ['required', 'string', 'max:80'],
            'city_id' => ['required', 'string', 'max:12'],
            'city_name' => ['required', 'string', 'max:100'],
            'district_id' => ['required', 'string', 'max:12'],
            'district_name' => ['required', 'string', 'max:100'],
            'village_id' => ['required', 'string', 'max:16'],
            'village_name' => ['required', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:10'],
            'detail' => ['required', 'string', 'max:300'],
            'note' => ['nullable', 'string', 'max:160'],
        ]);

        session(['checkout_address' => $data]);

        // Log event + update visitor dengan identitas customer
        $this->logEvent($request, 'address_fill', [
            'name'     => $data['recipient_name'],
            'province' => $data['province_name'],
            'city'     => $data['city_name'],
        ]);
        $this->updateVisitorIdentity($request, $data['recipient_name'], $data['phone'], $data['province_name'], $data['city_name']);

        return redirect()->to($this->checkoutReturnUrl($request->input('return_to')));
    }

    public function uploadBukti(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'image', 'max:10240'],
        ]);

        $file     = $request->file('file');
        $mime     = $file->getMimeType();
        $tmpPath  = $file->getRealPath();

        // Buat image resource dari file asli
        $src = match (true) {
            str_contains($mime, 'jpeg') => imagecreatefromjpeg($tmpPath),
            str_contains($mime, 'png')  => imagecreatefrompng($tmpPath),
            str_contains($mime, 'webp') => imagecreatefromwebp($tmpPath),
            str_contains($mime, 'gif')  => imagecreatefromgif($tmpPath),
            default                     => null,
        };

        if (!$src) {
            // Fallback: simpan as-is kalau format tidak dikenali GD
            $path = $file->store('bukti-bayar', 'public');
            return response()->json(['url' => asset('storage/' . $path)]);
        }

        // Resize kalau lebar > 1200px (jaga aspek rasio)
        $origW = imagesx($src);
        $origH = imagesy($src);
        $maxW  = 1200;

        if ($origW > $maxW) {
            $newW  = $maxW;
            $newH  = (int) round($origH * $maxW / $origW);
            $dst   = imagecreatetruecolor($newW, $newH);

            // Preserve transparency untuk PNG
            if (str_contains($mime, 'png')) {
                imagealphablending($dst, false);
                imagesavealpha($dst, true);
            }

            imagecopyresampled($dst, $src, 0, 0, 0, 0, $newW, $newH, $origW, $origH);
            imagedestroy($src);
            $src = $dst;
        }

        // Simpan sebagai JPEG quality 78 ke temp file
        $tmpOut  = tempnam(sys_get_temp_dir(), 'bukti_') . '.jpg';
        imagejpeg($src, $tmpOut, 78);
        imagedestroy($src);

        // Pindahkan ke storage
        $filename = 'bukti-bayar/' . uniqid('bukti_', true) . '.jpg';
        \Storage::disk('public')->put($filename, file_get_contents($tmpOut));
        @unlink($tmpOut);

        return response()->json(['url' => asset('storage/' . $filename)]);
    }

    public function ongkir(Request $request)
    {
        $destination = trim($request->query('destination', ''));
        $destinationId = trim($request->query('destination_id', ''));
        $weightInput = max(0.5, (float) $request->query('weight', 0.5));
        $weight = $weightInput <= 50 ? (int) ceil($weightInput * 1000) : (int) ceil($weightInput);

        if (!$destination && !$destinationId) {
            return response()->json(['error' => 'Destination required'], 422);
        }

        $apiKey = (string) env('RAJAONGKIR_API_KEY', '');
        $originId = (string) env('RAJAONGKIR_ORIGIN_ID', '');
        $couriers = (string) env('RAJAONGKIR_COURIERS', 'jne:sicepat:anteraja:pos');

        if (!$apiKey || !$originId) {
            return $this->shippingFallbackResponse('RajaOngkir belum dikonfigurasi.');
        }

        try {
            if (!$destinationId) {
                $destinations = $this->rajaongkirRequest('GET', '/destination/domestic-destination', [
                    'search' => $destination,
                    'limit' => 1,
                    'offset' => 0,
                ], $apiKey);

                $destinationId = (string) data_get($destinations, 'data.0.id', '');
            }

            if (!$destinationId) {
                return $this->shippingFallbackResponse('Area ongkir tidak ditemukan di RajaOngkir.');
            }

            $cost = $this->rajaongkirCostsByAvailableCourier($originId, $destinationId, $weight, $couriers, $apiKey);

            return response()->json($this->normalizeRajaongkirCost($cost));
        } catch (\Throwable $e) {
            return $this->shippingFallbackResponse($e->getMessage() ?: 'Terjadi kesalahan saat memuat ongkir RajaOngkir.');
        }
    }

    public function add(Request $request)
    {
        $slug  = $request->input('slug');
        $color = trim($request->input('color', ''));
        $size  = trim($request->input('size', ''));
        $qty   = max(1, (int) $request->input('qty', 1));
        $mode  = $request->input('mode', 'cart');

        $products = storefrontProducts();
        $product  = collect($products)->firstWhere('slug', $slug);
        $catalogProduct = StorefrontProduct::where('slug', $slug)
            ->where('is_published', true)
            ->with(['variants.itemMappings.item.inventoryStocks.warehouse', 'sizes', 'variantItemMappings.item.inventoryStocks.warehouse'])
            ->first();

        if (!$product || !$catalogProduct || !$color || !$size) {
            return back()->with('cart_error', 'Pilih warna dan ukuran terlebih dahulu.');
        }

        $stockResolver = app(StockResolver::class);
        $selectedMapping = $stockResolver->mappingForSelection($catalogProduct, $color, $size);
        $selectedVariant = $stockResolver->variantForSelection($catalogProduct, $color, $size);
        $availableStock = $selectedMapping
            ? $stockResolver->forMapping($selectedMapping)
            : ($selectedVariant ? $stockResolver->forVariant($selectedVariant) : 0);

        $allowNegativeStock = (bool) ($selectedMapping?->item_id ?: $selectedVariant?->item_id);

        if (! $allowNegativeStock && $availableStock < $qty) {
            return back()->with('cart_error', $availableStock > 0
                ? "Stok {$color} ukuran {$size} tersisa {$availableStock} pcs."
                : "Stok {$color} ukuran {$size} sedang kosong.");
        }

        // ── Resolve harga: size_override > color_override > base_price ──────────
        $basePrice = $product['_base_price'] ?? $product['price'];
        $variant   = collect($product['_variants'] ?? [])->first(function ($row) use ($color, $size) {
            return strcasecmp((string) ($row['name'] ?? ''), $color) === 0
                && strcasecmp((string) ($row['size_label'] ?? ''), $size) === 0;
        }) ?: collect($product['_variants'] ?? [])->firstWhere('name', $color);
        $price     = $variant['price_override'] ?? $basePrice;

        // Cek size price_override (opsional, jarang dipakai tapi support)
        $sizeData = collect($product['_sizes'] ?? [])->firstWhere('label', $size);
        if (!empty($sizeData['price_override'])) {
            $price = $sizeData['price_override'];
        }
        if (!empty($selectedMapping?->price_override)) {
            $price = $selectedMapping->price_override;
        }

        // ── Resolve gambar dari variant warna yang dipilih ───────────────────────
        $img = ($variant && !empty($variant['img'])) ? $variant['img'] : $product['img'];

        $key  = $slug . '-' . strtolower($color) . '-' . $size;
        $line = [
            'slug'  => $slug,
            'name'  => $product['name'],
            'color' => $color,
            'size'  => $size,
            'price' => $price,
            'img'   => $img,
            'qty'   => $qty,
            'variant_id' => $selectedVariant?->id,
            'item_id' => $selectedMapping?->item_id ?? $selectedVariant?->item_id,
            'item_code' => $selectedMapping?->item?->code ?? $selectedVariant?->item?->code,
        ];

        $this->logEvent($request, 'add_to_cart', [
            'slug'  => $slug,
            'name'  => $product['name'],
            'color' => $color,
            'size'  => $size,
            'qty'   => $qty,
            'price' => $price,
        ]);

        if ($mode === 'now') {
            session(['checkout_buy_now' => [$key => $line]]);

            return redirect()->route('storefront.checkout', ['buy_now' => 1]);
        }

        $cart = session('cart', []);

        if (isset($cart[$key])) {
            $cart[$key]['qty'] += $qty;
        } else {
            $cart[$key] = $line;
        }

        session(['cart' => $cart]);

        return back()->with([
            'cart_added' => true,
            'cart_added_name' => $product['name'],
        ]);
    }

    public function update(Request $request)
    {
        $key  = $request->input('key');
        $qty  = max(0, (int) $request->input('qty', 1));
        $cart = session('cart', []);

        if (isset($cart[$key])) {
            if ($qty <= 0) {
                unset($cart[$key]);
            } else {
                $availableStock = $this->stockForCartLine($cart[$key]);
                if ($availableStock !== null && $qty > $availableStock) {
                    return back()->with('cart_error', $availableStock > 0
                        ? "Stok {$cart[$key]['color']} ukuran {$cart[$key]['size']} tersisa {$availableStock} pcs."
                        : "Stok {$cart[$key]['color']} ukuran {$cart[$key]['size']} sedang kosong.");
                }
                $cart[$key]['qty'] = $qty;
            }
        }

        session(['cart' => $cart]);

        return back();
    }

    public function remove(Request $request)
    {
        $key  = $request->input('key');
        $cart = session('cart', []);

        if (isset($cart[$key])) {
            $this->logEvent($request, 'remove_from_cart', ['key' => $key, 'name' => $cart[$key]['name'] ?? '']);
            unset($cart[$key]);
        }

        session(['cart' => $cart]);

        return back();
    }

    public function placeOrder(Request $request)
    {
        $cart    = session('checkout_active_cart', session('cart', []));
        $address = session('checkout_address', []);

        if (empty($cart) || empty($address)) {
            return redirect()->route('storefront.checkout')->with('order_error', 'Keranjang atau alamat kosong.');
        }

        $stockErrors = $this->validateCartStock($cart);
        if (!empty($stockErrors)) {
            return redirect()->route('storefront.checkout')->with('order_error', implode(' ', $stockErrors));
        }

        try {
            $order = DB::transaction(function () use ($request, $cart, $address) {
                $subtotal     = (int) $request->input('subtotal', array_sum(array_map(fn($i) => $i['price'] * $i['qty'], $cart)));
                $shippingCost = (int) $request->input('shipping_cost', 0);
                $uniqueCode   = $this->checkoutUniqueCode($cart);
                $total        = $subtotal + $shippingCost + $uniqueCode;

                // Generate order number: GF-YYYYMMDD-XXXX
                $dateStr     = now()->format('Ymd');
                $todayCount  = StorefrontOrder::whereDate('created_at', today())->lockForUpdate()->count() + 1;
                $orderNumber = 'GF-' . $dateStr . '-' . str_pad($todayCount, 4, '0', STR_PAD_LEFT);

                $order = StorefrontOrder::create([
                    'order_number'      => $orderNumber,
                    'visitor_token'     => $request->attributes->get('visitor_token'),
                    'customer_name'     => $address['recipient_name'] ?? '',
                    'customer_phone'    => $address['phone'] ?? '',
                    'province'          => $address['province_name'] ?? '',
                    'city'              => $address['city_name'] ?? '',
                    'district'          => $address['district_name'] ?? '',
                    'village'           => $address['village_name'] ?? '',
                    'address_detail'    => $address['detail'] ?? '',
                    'postal_code'       => $address['postal_code'] ?? null,
                    'address_note'      => $address['note'] ?? null,
                    'items'             => array_values($cart),
                    'subtotal'          => $subtotal,
                    'shipping_cost'     => $shippingCost,
                    'unique_code'       => $uniqueCode,
                    'total_amount'      => $total,
                    'shipping_courier'  => $request->input('shipping_courier'),
                    'shipping_service'  => $request->input('shipping_service'),
                    'payment_method'    => $request->input('payment_method'),
                    'payment_proof_url' => $request->input('payment_proof_url'),
                    'status'            => 'pending',
                ]);

                $this->deductOrderStock($cart, $order);

                return $order;
            });
        } catch (\Throwable $e) {
            return redirect()->route('storefront.checkout')->with('order_error', $e->getMessage() ?: 'Stok gagal diperbarui. Silakan coba lagi.');
        }

        $this->logEvent($request, 'order_complete', [
            'order_number' => $order->order_number,
            'total'        => $order->total_amount,
            'items_count'  => count($cart),
        ]);

        // Notif WA ke admin (fire-and-forget, error tidak mengganggu user)
        app(WaNotificationService::class)->sendOrderNotification($order);

        // Simpan pesan WA di session agar success page bisa tampilkan tombol langsung
        session(['last_wa_message' => $request->input('wa_message', '')]);

        // Bersihkan cart
        $this->forgetOrderedCartLines($cart);
        session()->forget(['checkout_active_cart', 'checkout_buy_now', 'checkout_unique_code']);

        return redirect()->route('storefront.order.success', $order->order_number);
    }

    public function orderSuccess(Request $request, string $orderNumber)
    {
        $order = StorefrontOrder::where('order_number', $orderNumber)->firstOrFail();
        $waMessage = session()->pull('last_wa_message', '');

        return view('storefront.order_success', compact('order', 'waMessage'));
    }

    public function markWaClick(Request $request, string $orderNumber)
    {
        $order = StorefrontOrder::where('order_number', $orderNumber)->first();
        if ($order && ! $order->wa_sent_at) {
            $order->update(['wa_sent_at' => now()]);
            $this->logEvent($request, 'wa_click', ['order_number' => $orderNumber]);
        }
        return response()->json(['ok' => true]);
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    private function logEvent(Request $request, string $eventType, array $payload = []): void
    {
        // 4-level fallback: request attribute → cookie → session → hidden form field
        $token = $request->attributes->get('visitor_token')
            ?? $request->cookie('gf_vid')
            ?? $request->session()->get('_gf_vid')
            ?? $request->input('_sf_token');
        if (! $token) return;

        try {
            StorefrontEvent::create([
                'visitor_token' => $token,
                'event_type'    => $eventType,
                'payload'       => $payload,
                'created_at'    => now(),
            ]);
        } catch (\Throwable) {
            // Jangan pernah error tracking mengganggu user flow
        }
    }

    private function rajaongkirRequest(string $method, string $path, array $params, string $apiKey): array
    {
        $baseUrl = rtrim((string) env('RAJAONGKIR_BASE_URL', 'https://rajaongkir.komerce.id/api/v1'), '/');
        $method = strtoupper($method);
        $url = $baseUrl . '/' . ltrim($path, '/');

        if ($method === 'GET' && !empty($params)) {
            $url .= '?' . http_build_query($params);
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 12,
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'key: ' . $apiKey,
            ],
        ]);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        }

        $body = curl_exec($ch);
        $err = curl_error($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($err) {
            throw new \RuntimeException('Gagal menghubungi RajaOngkir.');
        }

        $data = json_decode((string) $body, true);
        if (!is_array($data)) {
            throw new \RuntimeException('Response RajaOngkir tidak valid.');
        }

        if ($status >= 400) {
            $message = data_get($data, 'meta.message')
                ?? data_get($data, 'message')
                ?? data_get($data, 'error')
                ?? 'RajaOngkir menolak request.';
            throw new \RuntimeException((string) $message);
        }

        return $data;
    }

    private function rajaongkirCostsByAvailableCourier(string $originId, string $destinationId, int $weight, string $couriers, string $apiKey): array
    {
        $courierCodes = array_values(array_filter(array_map(
            fn ($code) => trim((string) $code),
            preg_split('/[:,]/', $couriers) ?: []
        )));

        if (empty($courierCodes)) {
            $courierCodes = ['jnt'];
        }

        $combinedRows = [];
        $lastError = null;

        foreach ($courierCodes as $courier) {
            try {
                $response = $this->rajaongkirRequest('POST', '/calculate/domestic-cost', [
                    'origin' => $originId,
                    'destination' => $destinationId,
                    'weight' => $weight,
                    'courier' => $courier,
                    'price' => 'lowest',
                ], $apiKey);

                $rows = data_get($response, 'data', []);
                if (is_array($rows) && !empty($rows)) {
                    $combinedRows = array_merge($combinedRows, $rows);
                }
            } catch (\Throwable $e) {
                $lastError = $e;
                continue;
            }
        }

        if (empty($combinedRows)) {
            throw new \RuntimeException($lastError?->getMessage() ?: 'Tidak ada jasa kirim yang tersedia untuk alamat penerima.');
        }

        return [
            'meta' => [
                'message' => 'OK',
            ],
            'data' => $combinedRows,
        ];
    }

    private function normalizeRajaongkirCost(array $payload): array
    {
        $rows = data_get($payload, 'data', []);
        if (!is_array($rows)) {
            $rows = [];
        }

        $grouped = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $code = strtolower((string) ($row['code'] ?? $row['courier_code'] ?? $row['name'] ?? 'kurir'));
            $name = (string) ($row['name'] ?? $row['courier_name'] ?? strtoupper($code));
            $service = (string) ($row['service'] ?? '');
            $description = (string) ($row['description'] ?? $service);
            $cost = (int) ($row['cost'] ?? $row['value'] ?? 0);
            $etd = (string) ($row['etd'] ?? '');
            if ($cost < 0) {
                continue;
            }

            if (!isset($grouped[$code])) {
                $grouped[$code] = [
                    'name' => $name,
                    'code' => $code,
                    'costs' => [],
                ];
            }

            $grouped[$code]['costs'][] = [
                'service' => $service,
                'description' => $description,
                'cost' => [[
                    'value' => $cost,
                    'etd' => $etd,
                    'note' => '',
                ]],
            ];
        }

        return [
            'code' => '200',
            'message' => data_get($payload, 'meta.message', 'OK'),
            'data' => [
                'results' => array_values($grouped),
            ],
        ];
    }

    private function shippingFallbackResponse(string $message)
    {
        $rawCost = storefront_setting('shipping.fallback_cost', null);
        if ($rawCost === null || $rawCost === '') {
            $rawCost = env('STOREFRONT_FALLBACK_SHIPPING_COST', 15000);
        }
        if ($rawCost === null || $rawCost === '') {
            $rawCost = 15000;
        }
        $cost = is_numeric($rawCost) ? max(0, (int) $rawCost) : null;

        if ($cost === null) {
            return response()->json([
                'error' => $message,
                'hint' => 'Set STOREFRONT_FALLBACK_SHIPPING_COST untuk memakai ongkir sementara.',
            ], 503);
        }

        $service = storefront_setting('shipping.fallback_service', env('STOREFRONT_FALLBACK_SHIPPING_SERVICE', 'REG'));
        $courier = storefront_setting('shipping.fallback_courier', env('STOREFRONT_FALLBACK_SHIPPING_COURIER', 'Greatfit'));
        $etd = storefront_setting('shipping.fallback_etd', env('STOREFRONT_FALLBACK_SHIPPING_ETD', '2-5'));

        return response()->json([
            'code' => '200',
            'message' => $message,
            'data' => [
                'results' => [[
                    'name' => $courier,
                    'code' => strtolower((string) $courier),
                    'costs' => [[
                        'service' => $service,
                        'description' => 'Ongkir sementara',
                        'cost' => [[
                            'value' => $cost,
                            'etd' => $etd,
                            'note' => 'fallback',
                        ]],
                    ]],
                ]],
            ],
        ]);
    }

    private function updateVisitorIdentity(Request $request, string $name, string $phone, string $province, string $city): void
    {
        $token = $request->attributes->get('visitor_token')
            ?? $request->cookie('gf_vid')
            ?? $request->session()->get('_gf_vid')
            ?? $request->input('_sf_token');
        if (! $token) return;

        try {
            StorefrontVisitor::where('visitor_token', $token)->update([
                'customer_name'  => $name,
                'customer_phone' => $phone,
                'province'       => $province,
                'city'           => $city,
            ]);
        } catch (\Throwable) {}
    }

    private function checkoutReturnUrl(?string $returnTo): string
    {
        $checkoutUrl = route('storefront.checkout');

        if (!$returnTo) {
            return $checkoutUrl;
        }

        $base = url('/');

        // Allow checkout URLs
        if (str_starts_with($returnTo, $base . '/checkout')) {
            return $returnTo;
        }
        if (str_starts_with($returnTo, '/checkout')) {
            return url($returnTo);
        }

        // Allow /user (setelah registrasi baru isi alamat)
        if ($returnTo === '/user' || $returnTo === $base . '/user') {
            return url('/user');
        }

        return $checkoutUrl;
    }

    private function validateCartStock(array $cart): array
    {
        $stockResolver = app(StockResolver::class);
        $errors = [];

        foreach ($cart as $line) {
            $product = StorefrontProduct::where('slug', $line['slug'] ?? '')
                ->with(['variants.itemMappings.item.inventoryStocks.warehouse', 'sizes', 'variantItemMappings.item.inventoryStocks.warehouse'])
                ->first();

            if (! $product) {
                $errors[] = 'Produk ' . ($line['name'] ?? '') . ' tidak ditemukan.';
                continue;
            }

            $mapping = $stockResolver->mappingForSelection($product, (string) ($line['color'] ?? ''), (string) ($line['size'] ?? ''));
            $variant = $stockResolver->variantForSelection($product, (string) ($line['color'] ?? ''), (string) ($line['size'] ?? ''));
            if ($mapping?->item_id || $variant?->item_id || !empty($line['item_id'])) {
                continue;
            }

            $available = $stockResolver->forSelection($product, (string) ($line['color'] ?? ''), (string) ($line['size'] ?? ''));
            $qty = max(1, (int) ($line['qty'] ?? 1));

            if ($available < $qty) {
                $label = trim(($line['name'] ?? 'Produk') . ' ' . ($line['color'] ?? '') . ' ' . ($line['size'] ?? ''));
                $errors[] = $available > 0
                    ? "{$label} stok tersisa {$available} pcs."
                    : "{$label} sedang kosong.";
            }
        }

        return $errors;
    }

    /**
     * Total berat cart (kg) untuk estimasi ongkir.
     * Per baris: weight_kg produk katalog; kalau kosong/0 pakai fallback
     * setting checkout.weight_per_item (default 0.5 kg).
     */
    private function cartTotalWeightKg(array $cart): float
    {
        $fallback = (float) (storefront_setting('checkout.weight_per_item') ?: 0.5);
        if ($fallback <= 0) {
            $fallback = 0.5;
        }

        $slugs = array_values(array_unique(array_filter(
            array_map(fn($line) => $line['slug'] ?? null, $cart)
        )));

        $weights = empty($slugs)
            ? collect()
            : StorefrontProduct::whereIn('slug', $slugs)->pluck('weight_kg', 'slug');

        $totalKg = 0.0;
        foreach ($cart as $line) {
            $qty = max(1, (int) ($line['qty'] ?? 1));
            $weight = (float) ($weights[$line['slug'] ?? ''] ?? 0);
            if ($weight <= 0) {
                $weight = $fallback;
            }
            $totalKg += $qty * $weight;
        }

        return round($totalKg, 3);
    }

    private function checkoutUniqueCode(array $cart): int
    {
        if (empty($cart)) {
            session()->forget('checkout_unique_code');
            return 0;
        }

        $signature = md5(json_encode(array_values($cart)));
        $current = session('checkout_unique_code');

        if (is_array($current) && ($current['signature'] ?? null) === $signature) {
            return (int) ($current['code'] ?? 0);
        }

        $code = random_int(101, 999);
        session(['checkout_unique_code' => [
            'signature' => $signature,
            'code' => $code,
        ]]);

        return $code;
    }

    private function cartStockSnapshot(array $cart): array
    {
        $snapshot = [];

        foreach ($cart as $key => $line) {
            $allowNegativeStock = $this->cartLineAllowsNegativeStock($line);
            $available = $allowNegativeStock ? null : $this->stockForCartLine($line);
            $qty = max(1, (int) ($line['qty'] ?? 1));

            $snapshot[$key] = [
                'available' => $available,
                'qty' => $qty,
                'ok' => $available === null || $available >= $qty,
                'low' => $available !== null && $available > 0 && $available <= 4,
            ];
        }

        return $snapshot;
    }

    private function stockForCartLine(array $line): ?int
    {
        $product = StorefrontProduct::where('slug', $line['slug'] ?? '')
            ->with(['variants.itemMappings.item.inventoryStocks.warehouse', 'sizes', 'variantItemMappings.item.inventoryStocks.warehouse'])
            ->first();

        if (! $product) {
            return null;
        }

        return app(StockResolver::class)->forSelection($product, (string) ($line['color'] ?? ''), (string) ($line['size'] ?? ''));
    }

    private function deductOrderStock(array $cart, StorefrontOrder $order): void
    {
        $stockResolver = app(StockResolver::class);

        foreach ($cart as $line) {
            $qty = max(1, (int) ($line['qty'] ?? 1));
            $product = StorefrontProduct::where('slug', $line['slug'] ?? '')
                ->with(['variants.itemMappings.item.inventoryStocks.warehouse', 'sizes', 'variantItemMappings.item.inventoryStocks.warehouse'])
                ->first();

            if (! $product) {
                throw new \RuntimeException('Produk ' . ($line['name'] ?? '') . ' tidak ditemukan saat update stok.');
            }

            $mapping = $stockResolver->mappingForSelection($product, (string) ($line['color'] ?? ''), (string) ($line['size'] ?? ''));
            $variant = $stockResolver->variantForSelection($product, (string) ($line['color'] ?? ''), (string) ($line['size'] ?? ''));
            $itemId = (int) ($mapping?->item_id ?: $variant?->item_id ?: ($line['item_id'] ?? 0));

            if ($itemId > 0) {
                $this->deductItemStockFromRtsWarehouse($itemId, $qty, $order, $line);
                continue;
            }

            if ($mapping && $mapping->stock_override !== null) {
                if ((int) $mapping->stock_override < $qty) {
                    throw new \RuntimeException($this->stockErrorLabel($line) . ' stok tersisa ' . (int) $mapping->stock_override . ' pcs.');
                }
                $mapping->decrement('stock_override', $qty);
                continue;
            }

            if ($variant && $variant->stock_override !== null) {
                if ((int) $variant->stock_override < $qty) {
                    throw new \RuntimeException($this->stockErrorLabel($line) . ' stok tersisa ' . (int) $variant->stock_override . ' pcs.');
                }
                $variant->decrement('stock_override', $qty);
            }
        }
    }

    private function deductItemStockFromRtsWarehouse(int $itemId, int $qty, StorefrontOrder $order, array $line): void
    {
        $warehouse = Warehouse::query()
            ->where('code', 'WH-RTS')
            ->where('active', true)
            ->first();

        if (! $warehouse) {
            throw new \RuntimeException('Gudang WH-RTS belum tersedia.');
        }

        app(InventoryService::class)->stockOut(
            warehouseId: (int) $warehouse->id,
            itemId: $itemId,
            qty: $qty,
            date: now(),
            sourceType: 'storefront_order',
            sourceId: $order->id,
            notes: 'Checkout storefront ' . $order->order_number . ' - ' . $this->stockErrorLabel($line),
            allowNegative: true,
            affectLotCost: false,
            strictNonNegative: false,
        );
    }

    private function stockErrorLabel(array $line): string
    {
        return trim(($line['name'] ?? 'Produk') . ' ' . ($line['color'] ?? '') . ' ' . ($line['size'] ?? ''));
    }

    private function cartLineAllowsNegativeStock(array $line): bool
    {
        if (!empty($line['item_id'])) {
            return true;
        }

        $product = StorefrontProduct::where('slug', $line['slug'] ?? '')
            ->with(['variants.itemMappings.item.inventoryStocks.warehouse', 'sizes', 'variantItemMappings.item.inventoryStocks.warehouse'])
            ->first();

        if (! $product) {
            return false;
        }

        $stockResolver = app(StockResolver::class);
        $mapping = $stockResolver->mappingForSelection($product, (string) ($line['color'] ?? ''), (string) ($line['size'] ?? ''));
        $variant = $stockResolver->variantForSelection($product, (string) ($line['color'] ?? ''), (string) ($line['size'] ?? ''));

        return (bool) ($mapping?->item_id ?: $variant?->item_id);
    }

    private function forgetOrderedCartLines(array $orderedCart): void
    {
        $cart = session('cart', []);

        foreach (array_keys($orderedCart) as $key) {
            unset($cart[$key]);
        }

        session(['cart' => $cart]);
    }
}
