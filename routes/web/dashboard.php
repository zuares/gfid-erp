<?php
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {

    Route::get('/dashboard', function () {
        $user = auth()->user();

        // kalau operating → lempar ke Flow Dashboard
        if ($user && $user->role === 'operating') {
            return redirect()
                ->route('production.reports.production_flow_dashboard');
        }

        // owner / admin → tetap dashboard biasa
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
