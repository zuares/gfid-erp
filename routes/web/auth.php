<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Storefront\OAuthLoginController as StorefrontOAuthLoginController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    // Admin login — URL berbeda dari /login storefront agar tidak konflik
    Route::get('/admin/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/admin/login', [LoginController::class, 'login'])
        ->middleware('throttle:5,1')
        ->name('login.post');
});

Route::post('/admin/logout', [LoginController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

Route::middleware('guest')->group(function () {
    $oauthProviders = array_keys((array) config('services.oauth.providers', []));

    Route::get('/auth/{provider}', [StorefrontOAuthLoginController::class, 'redirect'])
        ->whereIn('provider', $oauthProviders)
        ->name('auth.oauth.redirect');

    Route::get('/auth/{provider}/callback', [StorefrontOAuthLoginController::class, 'callback'])
        ->whereIn('provider', $oauthProviders)
        ->name('auth.oauth.callback');
});
