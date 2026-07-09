<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.login'); // bikin blade sendiri
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'employee_code' => ['required', 'string', 'max:50'],
            'password' => ['required', 'string'],
        ]);

        $employeeCode = strtoupper(trim((string) $credentials['employee_code']));

        // Login pakai employee_code + password
        if (Auth::attempt([
            'employee_code' => $employeeCode,
            'password' => $credentials['password'],
        ], $request->boolean('remember'))) {

            $request->session()->regenerate();

            // Dashboard jadi halaman awal. Kalau role tidak boleh membuka
            // dashboard, pakai halaman modul terdekat sebagai fallback.
            $user = $request->user();
            $landingRoute = ($user && $user->canAccessModule('dashboard'))
                ? 'dashboard'
                : ($user?->preferredLandingRouteName() ?? 'dashboard');

            return redirect()->intended(route($landingRoute, [], false));

        }

        return back()->withErrors([
            'employee_code' => 'Employee code atau password salah.',
        ])->onlyInput('employee_code');
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->to(route('login', [], false));
    }
}
