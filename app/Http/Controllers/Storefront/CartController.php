<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

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

        $key  = $slug . '-' . strtolower($color) . '-' . $size;
        $line = [
            'slug'  => $slug,
            'name'  => $product['name'],
            'color' => $color,
            'size'  => $size,
            'price' => $product['price'],
            'img'   => $product['img'],
            'qty'   => $qty,
        ];

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
        unset($cart[$key]);
        session(['cart' => $cart]);

        return back();
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
