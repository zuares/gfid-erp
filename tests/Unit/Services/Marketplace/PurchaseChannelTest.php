<?php

namespace Tests\Unit\Services\Marketplace;

use App\Services\Marketplace\PurchaseChannel;
use PHPUnit\Framework\TestCase;

class PurchaseChannelTest extends TestCase
{
    public function test_tiktok_app_label_is_normalized_to_tiktok(): void
    {
        $this->assertSame('tiktok', PurchaseChannel::normalize('Aplikasi TikTok'));
        $this->assertSame('tiktok', PurchaseChannel::normalize('Aplikasi TikTok Shop'));
    }
}
