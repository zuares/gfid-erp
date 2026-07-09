<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Admin\StorefrontCrmController;
use App\Http\Controllers\Admin\StorefrontCustomerController;
use App\Http\Controllers\Admin\StorefrontProductCatalogController;
use App\Http\Controllers\Admin\StorefrontProductCategoryController;
use App\Http\Controllers\Admin\StorefrontSegmentController;
use App\Http\Controllers\Admin\StorefrontVisitorController;
use App\Http\Controllers\Admin\StorefrontWebsiteSettingsController;
use App\Http\Controllers\Storefront\CartController;
use App\Http\Controllers\Storefront\CustomerAuthController;
use App\Http\Controllers\Storefront\EventTrackController;

// ─── STOREFRONT (public, dengan visitor tracking) ───────────────────────────
Route::middleware(['track.storefront'])->group(function () {

    Route::get('/', function () {
        $products   = storefrontProducts();
        $channels   = storefrontChannels();
        $categories = \App\Models\StorefrontProductCategory::where('is_active', true)
            ->orderBy('sort_order')->orderBy('name')->get();
        return view('storefront.home', compact('products', 'channels', 'categories'));
    })->name('storefront.home');

    Route::get('/products', function (\Illuminate\Http\Request $request) {
        $allProducts    = storefrontProducts();
        $activeCategory = $request->query('kategori', '');
        $activeAudience = $request->query('audience', '');
        $activeType     = $request->query('type', '');   // '' | 'regular' | 'jumbo'

        $products = $allProducts;
        if ($activeCategory) {
            $products = array_values(array_filter($products, fn($p) => ($p['category_slug'] ?? '') === $activeCategory));
        }
        if ($activeAudience) {
            $products = array_values(array_filter($products, fn($p) => ($p['audience'] ?? '') === $activeAudience));
        }
        if ($activeType) {
            $products = array_values(array_filter($products, fn($p) => ($p['product_type'] ?? '') === $activeType));
        }

        $channels   = storefrontChannels();
        $categories = \App\Models\StorefrontProductCategory::where('is_active', true)
            ->orderBy('sort_order')->orderBy('name')->get();

        $audienceOptions = [
            'pria'     => 'Pria',
            'wanita'   => 'Wanita',
            'anak'     => 'Anak',
            'olahraga' => 'Olahraga',
            'unisex'   => 'Unisex',
        ];

        return view('storefront.products', compact(
            'products', 'channels', 'categories',
            'activeCategory', 'activeAudience', 'activeType', 'audienceOptions'
        ));
    })->name('storefront.products');

    // ── Customer Auth ─────────────────────────────────────────────────────────
    Route::get('/login',         [CustomerAuthController::class, 'loginPage'])->name('storefront.login');
    Route::post('/login',        [CustomerAuthController::class, 'loginSubmit'])->name('storefront.login.post');
    Route::get('/login/verify',  [CustomerAuthController::class, 'verifyPage'])->name('storefront.login.verify');
    Route::post('/login/verify', [CustomerAuthController::class, 'verifySubmit'])->name('storefront.login.verify.post');
    Route::post('/login/resend', [CustomerAuthController::class, 'resendOtp'])->name('storefront.login.resend');
    Route::get('/register',      [CustomerAuthController::class, 'registerPage'])->name('storefront.register');
    Route::post('/register',     [CustomerAuthController::class, 'registerSubmit'])->name('storefront.register.post');
    Route::post('/logout',       [CustomerAuthController::class, 'logout'])->name('storefront.logout');

    // ── Customer Dashboard ───────────────────────────────────────────────────
    Route::get('/user', [CustomerAuthController::class, 'userPage'])->name('storefront.user');
    Route::get('/user/orders', [CustomerAuthController::class, 'ordersPage'])->name('storefront.user.orders');

    // Legacy redirects
    Route::get('/masuk', fn() => redirect()->route('storefront.login', [], 301))->name('storefront.masuk');

    Route::get('/products/{slug}', function ($slug) {
        $products = storefrontProducts();
        $channels = storefrontChannels();
        $product  = collect($products)->firstWhere('slug', $slug);
        abort_if(!$product, 404);
        return view('storefront.product_detail', compact('slug', 'product', 'products', 'channels'));
    })->name('storefront.product_detail');

    Route::get('/cart',             [CartController::class, 'index'])->name('storefront.cart');
    Route::get('/checkout',         [CartController::class, 'checkout'])->name('storefront.checkout');
    Route::get('/checkout/address', [CartController::class, 'address'])->name('storefront.checkout.address');
    Route::post('/checkout/address', [CartController::class, 'saveAddress'])->name('storefront.checkout.address.save');
    Route::post('/cart/add',        [CartController::class, 'add'])->name('storefront.cart.add');
    Route::post('/cart/update',     [CartController::class, 'update'])->name('storefront.cart.update');
    Route::post('/cart/remove',     [CartController::class, 'remove'])->name('storefront.cart.remove');
    Route::get('/checkout/ongkir',  [CartController::class, 'ongkir'])->name('storefront.checkout.ongkir');
    Route::post('/checkout/upload-bukti', [CartController::class, 'uploadBukti'])->name('storefront.checkout.upload_bukti');

    // Order placement & success
    Route::post('/checkout/place-order', [CartController::class, 'placeOrder'])->name('storefront.checkout.place_order');
    Route::get('/order/{orderNumber}',   [CartController::class, 'orderSuccess'])->name('storefront.order.success');
    Route::post('/order/{orderNumber}/wa-click', [CartController::class, 'markWaClick'])->name('storefront.order.wa_click');

    // Client-side behaviour tracking (beacon dari JS tracker)
    Route::post('/storefront/track', [EventTrackController::class, 'store'])->name('storefront.track');
});

Route::middleware(['auth'])->group(function () {

    Route::get('/dashboard', function (\Illuminate\Http\Request $request) {
        $user = auth()->user();

        if (!$user) {
            return redirect()->route('login');
        }

        // Dashboard = halaman awal tiap role. Kalau user tidak diizinkan membuka
        // modul dashboard, arahkan ke halaman modul terdekat sebagai fallback.
        if (!$user->canAccessModule('dashboard')) {
            $landingRoute = $user->preferredLandingRouteName();
            if ($landingRoute && $landingRoute !== 'dashboard') {
                return redirect()->to(route($landingRoute, [], false));
            }
            abort(403, 'Akses dashboard belum diizinkan.');
        }

        return app(DashboardController::class)->index($request);
    })->name('dashboard');

    // ======================
    // ADMIN / OWNER ZONE
    // ======================
    Route::middleware(['role:admin,owner'])->group(function () {

        Route::get('/admin', function () {
            return view('welcome');
        })->name('admin.home');

        Route::redirect('/home', '/dashboard')->name('home');

        // ── Catalog Kategori ──────────────────────────────────────────────────
        Route::prefix('admin/catalog/categories')->name('admin.catalog.categories.')->group(function () {
            Route::get('/',                    [StorefrontProductCategoryController::class, 'index'])->name('index');
            Route::get('/create',              [StorefrontProductCategoryController::class, 'create'])->name('create');
            Route::post('/',                   [StorefrontProductCategoryController::class, 'store'])->name('store');
            Route::get('/{category}/edit',     [StorefrontProductCategoryController::class, 'edit'])->name('edit');
            Route::put('/{category}',          [StorefrontProductCategoryController::class, 'update'])->name('update');
            Route::delete('/{category}',       [StorefrontProductCategoryController::class, 'destroy'])->name('destroy');
        });

        // ── Catalog Produk Website ────────────────────────────────────────────
        Route::prefix('admin/catalog/products')->name('admin.catalog.products.')->group(function () {
            Route::get('/',                          [StorefrontProductCatalogController::class, 'index'])->name('index');
            Route::get('/items/suggest',             [StorefrontProductCatalogController::class, 'suggestItems'])->name('items.suggest');
            Route::get('/create',                    [StorefrontProductCatalogController::class, 'create'])->name('create');
            Route::post('/',                         [StorefrontProductCatalogController::class, 'store'])->name('store');
            Route::get('/ranking',                   [StorefrontProductCatalogController::class, 'rankingOverview'])->name('ranking');
            Route::get('/{product}/edit',            [StorefrontProductCatalogController::class, 'edit'])->name('edit');
            Route::put('/{product}',                 [StorefrontProductCatalogController::class, 'update'])->name('update');
            Route::delete('/{product}',              [StorefrontProductCatalogController::class, 'destroy'])->name('destroy');
            Route::post('/{product}/toggle-publish', [StorefrontProductCatalogController::class, 'togglePublish'])->name('toggle-publish');
            // Variants
            Route::post('/{product}/variants',                   [StorefrontProductCatalogController::class, 'storeVariant'])->name('variants.store');
            Route::patch('/{product}/variants/{variant}',        [StorefrontProductCatalogController::class, 'updateVariant'])->name('variants.update');
            Route::delete('/{product}/variants/{variant}',       [StorefrontProductCatalogController::class, 'destroyVariant'])->name('variants.destroy');
            // Sizes
            Route::post('/{product}/sizes',                      [StorefrontProductCatalogController::class, 'storeSize'])->name('sizes.store');
            Route::patch('/{product}/sizes/{size}',              [StorefrontProductCatalogController::class, 'updateSize'])->name('sizes.update');
            Route::delete('/{product}/sizes/{size}',             [StorefrontProductCatalogController::class, 'destroySize'])->name('sizes.destroy');
            // Item mapping
            Route::patch('/{product}/variant-items',              [StorefrontProductCatalogController::class, 'updateVariantItems'])->name('variant-items.update');
            // Ranking
            Route::patch('/{product}/ranking',                   [StorefrontProductCatalogController::class, 'updateRanking'])->name('ranking.update');
            Route::post('/rank-now',                             [StorefrontProductCatalogController::class, 'rankNow'])->name('rank-now');
        });

        // ── Website Settings ──────────────────────────────────────────────────
        Route::prefix('admin/website')->name('admin.website.')->group(function () {
            Route::get('/settings',          [StorefrontWebsiteSettingsController::class, 'index'])->name('settings');
            Route::post('/settings',         [StorefrontWebsiteSettingsController::class, 'update'])->name('settings.update');
            Route::get('/settings/sections/{section}', [StorefrontWebsiteSettingsController::class, 'editSection'])->name('settings.sections.edit');
            Route::post('/settings/sections/{section}', [StorefrontWebsiteSettingsController::class, 'updateSection'])->name('settings.sections.update');
            Route::post('/settings/upload',  [StorefrontWebsiteSettingsController::class, 'uploadImage'])->name('settings.upload');
        });

        // ── CRM Storefront ────────────────────────────────────────────────────
        Route::prefix('admin/crm')->name('admin.crm.')->group(function () {
            Route::get('/',                          [StorefrontCrmController::class, 'dashboard'])->name('dashboard');
            Route::get('/orders',                    [StorefrontCrmController::class, 'orders'])->name('orders');
            Route::get('/orders/live',               [StorefrontCrmController::class, 'ordersLive'])->name('orders.live');
            Route::patch('/orders/{order}/status',   [StorefrontCrmController::class, 'updateStatus'])->name('orders.status');
            Route::get('/prospects',                 [StorefrontCrmController::class, 'prospects'])->name('prospects');
            Route::get('/prospects/live',            [StorefrontCrmController::class, 'prospectsLive'])->name('prospects.live');
            Route::get('/prospects/export',          [StorefrontCrmController::class, 'exportProspects'])->name('prospects.export');
            Route::get('/visitors',                  [StorefrontVisitorController::class, 'index'])->name('visitors');
            Route::get('/visitors/live',             [StorefrontVisitorController::class, 'live'])->name('visitors.live');
            Route::post('/visitors/{visitor}/toggle-internal', [StorefrontVisitorController::class, 'toggleInternal'])->name('visitors.toggle_internal');
            Route::post('/visitors/rescan-internal', [StorefrontVisitorController::class, 'rescanInternal'])->name('visitors.rescan_internal');
            Route::post('/visitors/save-ip-settings', [StorefrontVisitorController::class, 'saveIpSettings'])->name('visitors.save_ip_settings');
            Route::get('/segments',                  [StorefrontSegmentController::class, 'index'])->name('segments');
            Route::get('/segments/{segment}',        [StorefrontSegmentController::class, 'show'])->name('segments.show');
            Route::get('/customers',                 [StorefrontCustomerController::class, 'index'])->name('customers');
            Route::get('/customers/{phone}',         [StorefrontCustomerController::class, 'show'])->name('customers.show');
            // DEV ONLY — seed & reset data storefront (owner + APP_DB_MODE=dev)
            Route::post('/dev-seed',                 [StorefrontCrmController::class, 'devSeed'])->name('dev_seed');
            Route::post('/dev-seed-abandoned',       [StorefrontCrmController::class, 'devSeedAbandoned'])->name('dev_seed_abandoned');
            Route::post('/dev-reset',                [StorefrontCrmController::class, 'devReset'])->name('dev_reset');
            Route::post('/dev-backfill-city',        [StorefrontCrmController::class, 'devBackfillCity'])->name('dev_backfill_city');
        });
    });
});
