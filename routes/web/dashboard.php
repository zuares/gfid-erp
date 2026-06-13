<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('storefront.home');
})->name('storefront.home');

Route::middleware(['auth'])->group(function () {

    Route::get('/dashboard', function () {
        $user = auth()->user();

        if (!$user) {
            return redirect()->route('login');
        }

        $landingRoute = $user->preferredLandingRouteName();
        if ($landingRoute && $landingRoute !== 'dashboard') {
            return redirect()->to(route($landingRoute, [], false));
        }

        abort_unless($user->canAccessModule('dashboard'), 403, 'Akses dashboard belum diizinkan.');

        return view('dashboard.index');
    })->name('dashboard');

    // ======================
    // ADMIN / OWNER ZONE
    // ======================
    Route::middleware(['role:admin,owner'])->group(function () {

        Route::get('/admin', function () {
            return view('welcome');
        })->name('admin.home');

        Route::redirect('/home', '/dashboard')->name('home');
    });
});
