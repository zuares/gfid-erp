<?php

namespace App\Jobs;

use App\Services\Shopee\ShopeeOAuthService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessShopeeOAuthJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 120;

    public function __construct(
        public string $code,
        public int $shopId,
        public ?int $targetStoreId = null,
        public bool $readOnlyIntegration = false,
    ) {}

    public function handle(ShopeeOAuthService $service): void
    {
        $service->process(
            $this->code,
            $this->shopId,
            $this->targetStoreId,
            $this->readOnlyIntegration,
        );
    }
}
