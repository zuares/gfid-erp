<?php

namespace Tests\Feature\Console;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarketplaceSyncFailedDeliveryTrackingCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_backfill_option_is_available_and_handles_empty_batch(): void
    {
        $this->artisan('marketplace:sync-failed-deliveries', [
            '--backfill' => true,
            '--from' => '2026-08-01',
            '--to' => '2026-08-28',
            '--apply' => true,
        ])
            ->expectsOutput('Backfill selesai; tidak ada batch berikutnya.')
            ->assertExitCode(0);
    }
}
