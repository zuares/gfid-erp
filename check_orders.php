$orders = App\Models\MarketplaceOrder::where('order_status', 'READY_TO_SHIP')->get();
$count = 0;
foreach($orders as $o) {
    if ($o->needs_shipping_arrangement) {
        $status = $o->raw_json['package_list'][0]['logistics_status'] ?? 'N/A';
        echo $o->channel_order_id . ' | LOGISTICS: ' . $status . " | AWB: " . $o->shipping_awb_no . "\n";
        $count++;
    }
}
echo "Total stuck orders: $count\n";
