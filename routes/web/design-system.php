<?php

use Illuminate\Support\Facades\Route;

// Internal UI inventory for the gradual Blade → React migration.
Route::middleware(['auth'])->group(function () {
    Route::view('/design-system', 'design-system.index')->name('design-system.index');
});
