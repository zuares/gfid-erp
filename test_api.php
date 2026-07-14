<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$store = App\Models\Store::whereHas('channel', fn($q) => $q->whereIn('code', ['SHOPEE', 'SHP', 'shopee']))->where('is_active', true)->first();
if (!$store) die("No store\n");

$res = app(App\Services\Channels\ChannelManager::class)->driver($store)->getBookingDetail($store, '260713AASAJFBWCWQTU');
print_r($res);
