<?php

// routes/api.php
use App\Http\Controllers\Api\AccountSuggestController;
use App\Http\Controllers\Api\CustomerController as ApiCustomerController;
use App\Http\Controllers\Api\ItemController;
use App\Http\Controllers\Api\StockApiController;
use App\Http\Controllers\Api\InternalInventoryController;

Route::middleware('auth:sanctum')->prefix('v1')->group(function () {
    Route::get('/items', [ItemController::class, 'index']);
    Route::get('/items/suggest', [ItemController::class, 'suggest']);
    Route::get('/items/{item}', [ItemController::class, 'show']);
    Route::get('/items/by-barcode', [ItemController::class, 'findByBarcode'])
        ->name('items.by_barcode');
    Route::get('/accounts/suggest', AccountSuggestController::class);

});

Route::prefix('v1')
    ->middleware(['auth:sanctum', 'throttle:stock-read'])
    ->group(function () {
        Route::post('/stocks/ready', [StockApiController::class, 'readyBatch'])
            ->name('stocks.ready');
    });

Route::prefix('api')
    ->name('api.')
    ->middleware(['auth:sanctum'])
    ->group(function () {
        Route::get('/customers/suggest', [ApiCustomerController::class, 'suggest'])
            ->name('customers.suggest');

        // ... route api lain (stock, items, dll)
    });

// The official Shopee callback remains public. Viewer/debug routes require the
// same web session and marketplace access as the marketplace UI.
Route::prefix('webhooks')->group(function () {
    Route::post('/shopee', [\App\Http\Controllers\WebhookController::class, 'shopee']);

    Route::middleware(['web', 'auth', 'access:marketplace'])->group(function () {
        Route::post('/simulate', [\App\Http\Controllers\WebhookController::class, 'simulate'])
            ->middleware('role:owner');
        Route::get('/logs', [\App\Http\Controllers\WebhookController::class, 'logs']);
        Route::get('/logs/{log}', [\App\Http\Controllers\WebhookController::class, 'show']);
    });
});

Route::get('/internal/inventory/stock', [InternalInventoryController::class, 'stock'])
    ->middleware([
        'machine.audit',
        'machine.ip-limit',
        'auth:sanctum',
        'machine.throttle',
        'service.ability:mcp:inventory:read',
    ])
    ->name('api.internal.inventory.stock');

Route::middleware('auth:sanctum')->group(function () {
    Route::get('customers', [ApiCustomerController::class, 'index'])
        ->name('api.customers.index');

});

// Mock Shopee API endpoints
Route::post('/v2/logistics/get_shipping_document_data_info', function (\Illuminate\Http\Request $request) {
    $filePath = storage_path('app/mock/shopee_shipping_document.json');
    if (!file_exists($filePath)) {
        return response()->json(['error' => 'mock_file_not_found', 'message' => 'Mock file not found'], 404);
    }
    
    $jsonContent = file_get_contents($filePath);
    return response($jsonContent, 200)->header('Content-Type', 'application/json');
});
