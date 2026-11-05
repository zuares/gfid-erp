<?php

namespace Tests\Feature\Console;

use App\Models\Channel;
use App\Models\MarketplaceOrder;
use App\Models\Store;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AuditTikTokOrderOwnershipCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_reports_the_single_tiktok_store_confirmed_by_order_detail_api(): void
    {
        $channel = Channel::create(['code' => 'tiktok', 'name' => 'TikTok Shop']);
        $gfid = $this->store($channel, 7, 'GFID', 'shop-7-cipher');
        $greatfull = $this->store($channel, 8, 'Greatfull.id', 'shop-8-cipher');

        foreach ([$gfid, $greatfull] as $store) {
            MarketplaceOrder::create([
                'store_id' => $store->id,
                'channel_order_id' => 'ORDER-DUP-1',
                'external_order_id' => 'ORDER-DUP-1',
                'ordered_at' => now()->subDay(),
                'order_date' => now()->subDay(),
                'status' => 'completed',
                'order_status' => 'COMPLETED',
            ]);
        }

        Http::fake(function ($request) {
            if (str_contains($request->url(), 'shop_cipher=shop-7-cipher')) {
                return Http::response([
                    'code' => 0,
                    'data' => [
                        'orders' => [[
                            'id' => 'ORDER-DUP-1',
                            'status' => 'COMPLETED',
                        ]],
                    ],
                ]);
            }

            return Http::response([
                'code' => 0,
                'data' => ['orders' => []],
            ]);
        });

        $status = \Illuminate\Support\Facades\Artisan::call('marketplace:audit-tiktok-order-ownership', [
            '--stores' => "{$gfid->id},{$greatfull->id}",
            '--order' => 'ORDER-DUP-1',
            '--pause-ms' => 0,
            '--format' => 'json',
        ]);
        $this->assertSame(0, $status);
        $report = json_decode(\Illuminate\Support\Facades\Artisan::output(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('confirmed_single_store', $report['findings'][0]['classification']);
        $this->assertSame($gfid->id, $report['findings'][0]['recommended_store_id']);

        $this->assertDatabaseCount('marketplace_orders', 2);
    }

    public function test_it_reports_api_errors_without_recommending_a_store(): void
    {
        $channel = Channel::create(['code' => 'tiktok', 'name' => 'TikTok Shop']);
        $store = $this->store($channel, 7, 'GFID', 'shop-7-cipher');

        MarketplaceOrder::create([
            'store_id' => $store->id,
            'channel_order_id' => 'ORDER-ERROR-1',
            'external_order_id' => 'ORDER-ERROR-1',
            'ordered_at' => now()->subDay(),
            'order_date' => now()->subDay(),
        ]);

        Http::fake([
            'https://open-api.tiktokglobalshop.com/*' => Http::response([
                'code' => 105005,
                'message' => 'scope missing',
            ], 200),
        ]);

        $status = \Illuminate\Support\Facades\Artisan::call('marketplace:audit-tiktok-order-ownership', [
            '--stores' => (string) $store->id,
            '--order' => 'ORDER-ERROR-1',
            '--pause-ms' => 0,
            '--format' => 'json',
        ]);
        $this->assertSame(0, $status);
        $report = json_decode(\Illuminate\Support\Facades\Artisan::output(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('api_error', $report['findings'][0]['classification']);
        $this->assertNull($report['findings'][0]['recommended_store_id']);
    }

    private function store(Channel $channel, int $id, string $name, string $cipher): Store
    {
        return Store::create([
            'id' => $id,
            'channel_id' => $channel->id,
            'code' => 'tiktok_'.$cipher,
            'name' => $name,
            'external_shop_id' => 'shop-'.$id,
            'region' => 'ID',
            'status' => 'active',
            'is_active' => true,
            'credentials' => [
                'app_key' => 'test-app-key',
                'app_secret' => 'test-app-secret',
                'access_token' => 'test-access-token',
                'shop_id' => 'shop-'.$id,
                'shop_cipher' => $cipher,
            ],
        ]);
    }
}
