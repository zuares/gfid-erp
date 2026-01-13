<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Update harian setelah ganti hari
Schedule::command('sales:rebuild-daily-item-sales --days=90')
    ->dailyAt('00:05');

Schedule::command('inventory:recalc-ads-from-daily --days=30')
    ->dailyAt('00:10');
