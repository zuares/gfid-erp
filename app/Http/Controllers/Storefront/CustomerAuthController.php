<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\StorefrontCustomer;
use App\Services\WaNotificationService;
use Illuminate\Http\Request;

class CustomerAuthController extends Controller
{
    // ── GET /login ────────────────────────────────────────────────────────────
    public function loginPage(Request $request)
    {
        if (session('storefront_customer_id')) {
            return redirect()->route('storefront.user');
        }
        return view('storefront.login', [
            'error' => session()->pull('login_error'),
            'tab'   => $request->query('tab', 'customer'), // 'customer' | 'admin'
        ]);
    }

    // ── POST /login ───────────────────────────────────────────────────────────
    public function loginSubmit(Request $request)
    {
        $raw   = $request->input('phone', '');
        $phone = StorefrontCustomer::normalizePhone($raw);

        if (strlen($phone) < 10) {
            return back()->with('login_error', 'Nomor HP tidak valid.');
        }

        $customer = StorefrontCustomer::where('phone', $phone)->first();

        if (!$customer) {
            // Belum punya akun → arahkan ke register dengan phone pre-filled
            return redirect()->route('storefront.register', ['phone' => $raw])
                ->with('register_info', 'Nomor ini belum terdaftar. Daftar dulu yuk!');
        }

        // Kirim OTP
        $otp = $customer->generateOtp();
        $sent = app(WaNotificationService::class)->sendOtp($phone, $otp, $customer->name);

        if (! $sent) {
            return back()->with('login_error', 'OTP belum bisa dikirim. Coba lagi sebentar atau hubungi admin Greatfit.');
        }

        session(['otp_phone' => $phone]);

        return redirect()->route('storefront.login.verify')
            ->with('otp_sent', true);
    }

    // ── GET /login/verify ─────────────────────────────────────────────────────
    public function verifyPage(Request $request)
    {
        if (!session('otp_phone')) {
            return redirect()->route('storefront.login');
        }
        return view('storefront.login-verify', [
            'phone'    => session('otp_phone'),
            'otpSent'  => session()->pull('otp_sent', false),
            'error'    => session()->pull('verify_error'),
            'resent'   => session()->pull('otp_resent', false),
        ]);
    }

    // ── POST /login/verify ────────────────────────────────────────────────────
    public function verifySubmit(Request $request)
    {
        $phone = session('otp_phone');
        if (!$phone) {
            return redirect()->route('storefront.login');
        }

        $code     = preg_replace('/\D/', '', $request->input('otp', ''));
        $customer = StorefrontCustomer::where('phone', $phone)->first();

        if (!$customer || !$customer->verifyOtp($code)) {
            return back()->with('verify_error', 'Kode OTP salah atau sudah kadaluarsa.');
        }

        $isNewReg = session()->pull('otp_is_new_registration', false);

        session()->forget('otp_phone');
        session(['storefront_customer_id' => $customer->id]);

        if ($isNewReg) {
            // Pre-fill alamat dengan nama & HP customer, lalu arahkan ke form alamat
            $phoneDisplay = str_starts_with($phone, '62') ? '0' . substr($phone, 2) : $phone;
            session(['checkout_address' => array_merge(
                session('checkout_address', []),
                [
                    'recipient_name' => $customer->name,
                    'phone'          => $phoneDisplay,
                ]
            )]);

            return redirect()->route('storefront.checkout.address', [
                'return_to' => route('storefront.user', [], false),
            ]);
        }

        return redirect()->route('storefront.user');
    }

    // ── POST /login/resend ────────────────────────────────────────────────────
    public function resendOtp(Request $request)
    {
        $phone    = session('otp_phone');
        $customer = $phone ? StorefrontCustomer::where('phone', $phone)->first() : null;

        if ($customer) {
            $otp = $customer->generateOtp();
            $sent = app(WaNotificationService::class)->sendOtp($phone, $otp, $customer->name);
            if (! $sent) {
                return back()->with('verify_error', 'OTP belum bisa dikirim ulang. Coba lagi sebentar.');
            }
        }

        return back()->with('otp_resent', true);
    }

    // ── GET /register ─────────────────────────────────────────────────────────
    public function registerPage(Request $request)
    {
        if (session('storefront_customer_id')) {
            return redirect()->route('storefront.user');
        }
        return view('storefront.register', [
            'prefillPhone' => $request->query('phone', ''),
            'info'         => session()->pull('register_info'),
            'error'        => session()->pull('register_error'),
        ]);
    }

    // ── POST /register ────────────────────────────────────────────────────────
    public function registerSubmit(Request $request)
    {
        $name  = trim($request->input('name', ''));
        $raw   = $request->input('phone', '');
        $email = trim($request->input('email', '')) ?: null;
        $phone = StorefrontCustomer::normalizePhone($raw);

        if (strlen($name) < 2) {
            return back()->withInput()->with('register_error', 'Nama minimal 2 karakter.');
        }
        if (strlen($phone) < 10) {
            return back()->withInput()->with('register_error', 'Nomor HP tidak valid.');
        }

        // Cek duplikat
        if (StorefrontCustomer::where('phone', $phone)->exists()) {
            return redirect()->route('storefront.login', ['phone' => $raw])
                ->with('login_error', 'Nomor ini sudah terdaftar. Silakan masuk.');
        }
        if ($email && StorefrontCustomer::where('email', $email)->exists()) {
            return back()->withInput()->with('register_error', 'Email sudah digunakan akun lain.');
        }

        // Buat akun
        $customer = StorefrontCustomer::create([
            'name'  => $name,
            'phone' => $phone,
            'email' => $email,
        ]);

        // Kirim OTP
        $otp = $customer->generateOtp();
        $sent = app(WaNotificationService::class)->sendOtp($phone, $otp, $name);

        if (! $sent) {
            $customer->delete();
            return back()->withInput()->with('register_error', 'OTP belum bisa dikirim. Coba lagi sebentar atau hubungi admin Greatfit.');
        }

        // Tandai sebagai registrasi baru → setelah verify, arahkan ke form alamat
        session([
            'otp_phone'               => $phone,
            'otp_is_new_registration' => true,
        ]);

        return redirect()->route('storefront.login.verify')
            ->with('otp_sent', true);
    }

    // ── POST /logout ──────────────────────────────────────────────────────────
    public function logout(Request $request)
    {
        session()->forget(['storefront_customer_id', 'customer_phone', 'otp_phone']);
        return redirect()->route('storefront.home');
    }

    // ── GET /user ─────────────────────────────────────────────────────────────
    public function userPage(Request $request)
    {
        $customerId = session('storefront_customer_id');
        if (!$customerId) {
            return redirect()->route('storefront.login');
        }

        $customer = StorefrontCustomer::find($customerId);
        if (!$customer) {
            session()->forget('storefront_customer_id');
            return redirect()->route('storefront.login');
        }

        $phone    = $customer->phone;
        $phoneAlt = str_starts_with($phone, '62') ? '0' . substr($phone, 2) : $phone;

        $orders = \App\Models\StorefrontOrder::where('customer_phone', $phone)
            ->orWhere('customer_phone', $phoneAlt)
            ->orderByDesc('created_at')
            ->limit(20)
            ->get()
            ->toArray();

        $address = session('checkout_address', []);
        $role    = $customer->customer_role; // 'customer' | 'prospect'

        return view('storefront.user', compact('customer', 'orders', 'address', 'role'));
    }

    // ── GET /user/orders ─────────────────────────────────────────────────────
    public function ordersPage(Request $request)
    {
        $customerId = session('storefront_customer_id');
        if (!$customerId) {
            return redirect()->route('storefront.login');
        }

        $customer = StorefrontCustomer::find($customerId);
        if (!$customer) {
            session()->forget('storefront_customer_id');
            return redirect()->route('storefront.login');
        }

        $phone = $customer->phone;
        $phoneAlt = str_starts_with($phone, '62') ? '0' . substr($phone, 2) : $phone;
        $activeStatus = $request->query('status', '');

        $query = \App\Models\StorefrontOrder::where(function ($q) use ($phone, $phoneAlt) {
            $q->where('customer_phone', $phone)->orWhere('customer_phone', $phoneAlt);
        });

        if ($activeStatus) {
            $query->where('status', $activeStatus);
        }

        $orders = $query->orderByDesc('created_at')->limit(50)->get()->toArray();

        return view('storefront.orders', compact('customer', 'orders', 'activeStatus'));
    }
}
