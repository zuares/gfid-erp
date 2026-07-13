<?php

namespace App\Console\Commands\Marketplace;

use Illuminate\Console\Command;
use App\Models\MarketplaceOrder;

class FixShopeeAwbCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'marketplace:fix-shopee-awb';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix missing shipping AWB for Shopee orders that use package_number in raw_json';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Finding Shopee orders with missing AWB but has package_number in raw_json...');

        $orders = MarketplaceOrder::whereNull('shipping_awb_no')
            ->orWhere('shipping_awb_no', '')
            ->get();

        $fixedCount = 0;

        foreach ($orders as $order) {
            $raw = $order->raw_json;
            
            // Skip if no raw_json or no package_list
            if (!$raw || !isset($raw['package_list']) || !is_array($raw['package_list']) || empty($raw['package_list'])) {
                continue;
            }

            $firstPkg = $raw['package_list'][0];
            $trackingNo = $firstPkg['tracking_no'] 
                          ?? $firstPkg['tracking_number'] 
                          ?? $firstPkg['package_number'] 
                          ?? null;

            if ($trackingNo) {
                $order->update(['shipping_awb_no' => $trackingNo]);
                $fixedCount++;
                $this->line("Fixed order {$order->channel_order_id} -> AWB: {$trackingNo}");
            }
        }

        $this->info("Done! Fixed AWB for {$fixedCount} orders.");
    }
}
