<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Storefront\CartController;

Route::get('/', function () {
    $products = storefrontProducts();
    $channels = storefrontChannels();
    return view('storefront.home', compact('products', 'channels'));
})->name('storefront.home');

Route::get('/products', function () {
    $products = storefrontProducts();
    $channels = storefrontChannels();
    return view('storefront.products', compact('products', 'channels'));
})->name('storefront.products');

Route::get('/products/{slug}', function ($slug) {
    $products = storefrontProducts();
    $channels = storefrontChannels();
    $product  = collect($products)->firstWhere('slug', $slug);
    abort_if(!$product, 404);
    return view('storefront.product_detail', compact('slug', 'product', 'products', 'channels'));
})->name('storefront.product_detail');

Route::get('/cart',           [CartController::class, 'index'])->name('storefront.cart');
Route::get('/checkout',       [CartController::class, 'checkout'])->name('storefront.checkout');
Route::get('/checkout/address', [CartController::class, 'address'])->name('storefront.checkout.address');
Route::post('/checkout/address', [CartController::class, 'saveAddress'])->name('storefront.checkout.address.save');
Route::post('/cart/add',      [CartController::class, 'add'])->name('storefront.cart.add');
Route::post('/cart/update',   [CartController::class, 'update'])->name('storefront.cart.update');
Route::post('/cart/remove',   [CartController::class, 'remove'])->name('storefront.cart.remove');
Route::get('/checkout/ongkir', [CartController::class, 'ongkir'])->name('storefront.checkout.ongkir');
Route::post('/checkout/upload-bukti', [CartController::class, 'uploadBukti'])->name('storefront.checkout.upload_bukti');

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
