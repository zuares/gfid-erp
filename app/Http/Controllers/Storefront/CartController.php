<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\StorefrontEvent;
use App\Models\StorefrontOrder;
use App\Models\StorefrontVisitor;
use App\Services\WaNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CartController extends Controller
{
    public function index()
    {
        $cart     = session('cart', []);
        $channels = storefrontChannels();
        $products = storefrontProducts();
        $total    = array_sum(array_map(fn($i) => $i['price'] * $i['qty'], $cart));

        return view('storefront.cart', compact('cart', 'channels', 'products', 'total'));
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

        $channels = storefrontChannels();
        $products = storefrontProducts();
        $address  = session('checkout_address', []);
        $total    = array_sum(array_map(fn($i) => $i['price'] * $i['qty'], $cart));

        return view('storefront.checkout', compact('cart', 'channels', 'products', 'address', 'total'));
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
        $weight      = max(0.5, (float) $request->query('weight', 0.5));

        if (!$destination) {
            return response()->json(['error' => 'Destination required'], 422);
        }

        $apiKey  = env('BINDERBYTE_API_KEY', '');
        $origin  = env('BINDERBYTE_ORIGIN', 'kab bandung');

        if (!$apiKey) {
            return response()->json(['error' => 'API key belum dikonfigurasi'], 503);
        }

        try {
            $ch = curl_init('https://api.binderbyte.com/v1/cost');
            curl_setopt_array($ch, [
                CURLOPT_POST            => true,
                CURLOPT_RETURNTRANSFER  => true,
                CURLOPT_CONNECTTIMEOUT  => 5,
                CURLOPT_TIMEOUT         => 10,
                CURLOPT_POSTFIELDS      => http_build_query([
                    'api_key'     => $apiKey,
                    'origin'      => $origin,
                    'destination' => $destination,
                    'courier'     => 'jne,sicepat,anteraja,ide,pos',
                    'weight'      => $weight,
                ]),
            ]);
            $body = curl_exec($ch);
            $err  = curl_error($ch);
            curl_close($ch);

            if ($err) {
                return response()->json(['error' => 'Gagal menghubungi API ongkir'], 502);
            }

            $data = json_decode($body, true);
            return response()->json($data);
        } catch (\Throwable $e) {
            return response()->json(['error' => 'Terjadi kesalahan'], 500);
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

        if (!$product || !$color || !$size) {
            return back()->with('cart_error', 'Pilih warna dan ukuran terlebih dahulu.');
        }

        // ── Resolve harga: size_override > color_override > base_price ──────────
        $basePrice = $product['_base_price'] ?? $product['price'];
        $variant   = collect($product['_variants'] ?? [])->firstWhere('name', $color);
        $price     = $variant['price_override'] ?? $basePrice;

        // Cek size price_override (opsional, jarang dipakai tapi support)
        $sizeData = collect($product['_sizes'] ?? [])->firstWhere('label', $size);
        if (!empty($sizeData['price_override'])) {
            $price = $sizeData['price_override'];
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
        $cart    = session('cart', []);
        $address = session('checkout_address', []);

        if (empty($cart) || empty($address)) {
            return redirect()->route('storefront.checkout')->with('order_error', 'Keranjang atau alamat kosong.');
        }

        $subtotal     = (int) $request->input('subtotal', array_sum(array_map(fn($i) => $i['price'] * $i['qty'], $cart)));
        $shippingCost = (int) $request->input('shipping_cost', 0);
        $total        = $subtotal + $shippingCost;

        // Generate order number: GF-YYYYMMDD-XXXX
        $dateStr     = now()->format('Ymd');
        $todayCount  = StorefrontOrder::whereDate('created_at', today())->count() + 1;
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
            'total_amount'      => $total,
            'shipping_courier'  => $request->input('shipping_courier'),
            'shipping_service'  => $request->input('shipping_service'),
            'payment_method'    => $request->input('payment_method'),
            'payment_proof_url' => $request->input('payment_proof_url'),
            'status'            => 'pending',
        ]);

        $this->logEvent($request, 'order_complete', [
            'order_number' => $orderNumber,
            'total'        => $total,
            'items_count'  => count($cart),
        ]);

        // Notif WA ke admin (fire-and-forget, error tidak mengganggu user)
        app(WaNotificationService::class)->sendOrderNotification($order);

        // Simpan pesan WA di session agar success page bisa tampilkan tombol langsung
        session(['last_wa_message' => $request->input('wa_message', '')]);

        // Bersihkan cart
        session()->forget(['cart', 'checkout_buy_now']);

        return redirect()->route('storefront.order.success', $orderNumber);
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
        if (str_starts_with($returnTo, $base . '/checkout')) {
            return $returnTo;
        }

        if (str_starts_with($returnTo, '/checkout')) {
            return url($returnTo);
        }

        return $checkoutUrl;
    }
}
