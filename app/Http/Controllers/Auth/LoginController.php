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
            'employee_code' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        // Login pakai employee_code + password
        if (Auth::attempt([
            'employee_code' => $credentials['employee_code'],
            'password' => $credentials['password'],
        ], $request->boolean('remember'))) {

            $request->session()->regenerate();

            return redirect()->intended(route('dashboard'));

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

        return redirect()->route('login');
    }
}
