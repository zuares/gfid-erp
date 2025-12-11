<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // 👇 Tambahkan baris ini
        Paginator::useBootstrapFive();

        // Kalau mau pakai Bootstrap 4:
        // Paginator::useBootstrapFour();
    }
}
