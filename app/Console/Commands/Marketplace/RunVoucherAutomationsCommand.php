<?php

namespace App\Console\Commands\Marketplace;

use App\Services\Marketplace\MarketplaceVoucherAutomationService;
use Illuminate\Console\Command;

class RunVoucherAutomationsCommand extends Command
{
    protected $signature = 'marketplace:run-voucher-automations';

    protected $description = 'Buat voucher Shopee otomatis dari automation yang sudah jatuh tempo';

    public function handle(MarketplaceVoucherAutomationService $service): int
    {
        $results = $service->runDue();
        $created = collect($results)->where('status', 'created')->count();
        $failed = collect($results)->where('status', 'failed')->count();

        if ($created || $failed) {
            $this->info("Voucher dibuat: {$created}; gagal: {$failed}.");
        }

        return self::SUCCESS;
    }
}
