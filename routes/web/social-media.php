<?php

use App\Http\Controllers\Instagram\InstagramOAuthController;

// Social Media — koneksi akun dan OAuth
Route::middleware(['web', 'auth', 'role:admin'])->group(function () {
    Route::redirect('/social-media', '/social-media/instagram')
        ->name('social-media.index');
    Route::get('/social-media/instagram', [InstagramOAuthController::class, 'index'])
        ->name('social-media.instagram');
    Route::get('/social-media/instagram/connect', [InstagramOAuthController::class, 'redirect'])
        ->name('social-media.instagram.connect');
    Route::get('/social-media/instagram/callback', [InstagramOAuthController::class, 'callback'])
        ->name('social-media.instagram.callback');
});
