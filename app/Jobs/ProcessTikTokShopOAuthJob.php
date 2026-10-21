<?php

namespace App\Jobs;

use App\Services\TikTokShop\TikTokShopOAuthService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessTikTokShopOAuthJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 120;

    public function __construct(
        public string $authCode,
        public ?int $targetStoreId = null,
        public bool $readOnlyIntegration = false,
    ) {}

    public function handle(TikTokShopOAuthService $service): void
    {
        $service->process($this->authCode, $this->targetStoreId, $this->readOnlyIntegration);
    }
}
