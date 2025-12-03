<?php

// routes/api.php
use App\Http\Controllers\Api\ItemController;

Route::prefix('v1')->group(function () {
    Route::get('/items', [ItemController::class, 'index']);
    Route::get('/items/suggest', [ItemController::class, 'suggest']);
    Route::get('/items/{item}', [ItemController::class, 'show']);

});
