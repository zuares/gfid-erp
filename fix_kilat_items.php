<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$orders = \App\Models\MarketplaceOrder::whereDoesntHave('items')
    ->whereNotNull('booking_sn')
    ->get();

$count = 0;
foreach ($orders as $order) {
    $booking = \App\Models\MarketplaceBooking::where('booking_sn', $order->booking_sn)->first();
    if ($booking && !empty($booking->items) && is_array($booking->items)) {
        foreach ($booking->items as $idx => $item) {
            \App\Models\MarketplaceOrderItem::updateOrCreate([
                'marketplace_order_id' => $order->id,
                'external_item_id'     => $item['item_id'] ?? null,
                'external_model_id'    => $item['model_id'] ?? null,
            ], [
                'order_id'             => $order->id,
                'line_no'              => $idx + 1,
                'item_name'            => $item['item_name'] ?? '-',
                'item_sku'             => $item['item_sku']  ?? null,
                'model_sku'            => $item['model_sku'] ?? null,
                'variant_name'         => $item['model_name'] ?? null,
                'qty'                  => (int) ($item['model_quantity_purchased'] ?? $item['active_qty'] ?? 0),
                'price'                => $item['model_original_price'] ?? $item['model_discounted_price'] ?? 0,
                'image_url'            => data_get($item, 'image_info.image_url'),
                'raw_json'             => $item,
            ]);
        }
        $count++;
    }
}
echo "Fixed $count orders.\n";
