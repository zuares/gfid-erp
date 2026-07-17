<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$bookings = \App\Models\MarketplaceBooking::whereIn('booking_status', ['PENDING', 'READY_TO_SHIP', 'PROCESSED'])
    ->get();

$manager = app(\App\Services\Channels\ChannelManager::class);

$count = 0;
foreach ($bookings as $b) {
    if (empty($b->items)) {
        try {
            $driver = $manager->driver($b->store);
            if (method_exists($driver, 'getBookingDetail')) {
                $res = $driver->getBookingDetail($b->store, $b->booking_sn);
                $list = $res['response']['booking_list'] ?? $res['response']['order_list'] ?? [];
                if (!empty($list)) {
                    $d = $list[0];
                    $b->items = $d['item_list'] ?? [];
                    if (!empty($d['order_sn'])) $b->order_sn = $d['order_sn'];
                    $b->save();
                    $count++;
                    echo "Fixed booking: " . $b->booking_sn . " with " . count($d['item_list'] ?? []) . " items\n";
                }
            }
        } catch (\Exception $e) {
            echo "Error for " . $b->booking_sn . ": " . $e->getMessage() . "\n";
        }
    }
}
echo "Total fixed: $count\n";
