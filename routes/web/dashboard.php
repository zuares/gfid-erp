<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {

    Route::get('/dashboard', function () {
        $user = auth()->user();

        if (!$user) {
            return redirect()->route('login');
        }

        // production → Dashboard Sewing (yang barusan dibuat)
        if ($user->role === 'operating') {
            return redirect()->route('production.reports.dashboard');
        }

        // admin → langsung ke laporan shipment
        if ($user->role === 'admin') {
            return redirect()->route('sales.shipments.report');
        }

        // owner / role lain → dashboard biasa
        return view('dashboard.index');
    })->name('dashboard');

    // ======================
    // ADMIN / OWNER ZONE
    // ======================
    Route::middleware(['role:admin,owner'])->group(function () {

        Route::get('/admin', function () {
            return view('welcome');
        })->name('admin.home');

        Route::get('/', function () {
            return view('welcome');
        })->name('home');
    });
});
