<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$b = \App\Models\MarketplaceBooking::where('booking_sn', '260717AASCMHOZXR5GM')->first();
if ($b) {
    $store = $b->store;
    $driver = app('marketplace')->driver($store->channel->code);
    try {
        $res = $driver->getBookingDetail($store, '260717AASCMHOZXR5GM');
        $list = $res['response']['booking_list'] ?? $res['response']['order_list'] ?? [];
        if (!empty($list)) {
            $d = $list[0];
            echo "Got detail!\n";
            $b->items = $d['item_list'] ?? [];
            if (!empty($d['order_sn'])) $b->order_sn = $d['order_sn'];
            $b->save();
            echo "Saved! Items count: " . count($d['item_list'] ?? []) . "\n";
        } else {
            echo "Empty list from getBookingDetail. Raw response: " . json_encode($res) . "\n";
        }
    } catch (\Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }
}
