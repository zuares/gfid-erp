<?php

namespace Tests\Feature;

use App\Models\Channel;
use App\Models\MarketplaceVoucher;
use App\Models\Store;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MarketplaceVouchersTest extends TestCase
{
    use RefreshDatabase;

    public function test_voucher_module_is_present_on_promotions_page(): void
    {
        $this->withoutMiddleware();

        $this->get(route('marketplace.promotions'))
            ->assertOk()
            ->assertSee('Voucher Toko Shopee')
            ->assertSee('Buat Voucher');
    }

    private function store(): Store
    {
        $channel = Channel::firstOrCreate(['code' => 'shopee'], ['name' => 'Shopee']);
        return Store::create([
            'channel_id' => $channel->id, 'code' => 'VF' . random_int(1000, 9999), 'name' => 'Toko Voucher',
            'status' => 'active', 'is_active' => true, 'external_shop_id' => '12345',
            'credentials' => ['partner_id' => '2000000', 'partner_key' => 'dummy_key', 'shop_id' => '12345', 'access_token' => 'dummy_access_token', 'refresh_token' => 'dummy_refresh_token'],
            'token_expires_at' => now()->addHour(),
        ]);
    }

    public function test_voucher_list_normalizes_and_persists_response(): void
    {
        $this->withoutMiddleware();
        $store = $this->store();
        Http::fake(['*/api/v2/voucher/get_voucher_list*' => Http::response(['response' => ['more' => false, 'voucher_list' => [[
            'voucher_id' => 456, 'voucher_code' => 'HEMAT10', 'voucher_name' => 'Hemat 10%', 'voucher_type' => 1,
            'reward_type' => 2, 'usage_quantity' => 100, 'current_usage' => 4, 'start_time' => now()->subHour()->timestamp,
            'end_time' => now()->addDay()->timestamp, 'min_basket_price' => 100000, 'percentage' => 10,
        ]]]])]);

        $response = $this->getJson('/api/marketplace/vouchers?store_id=' . $store->id . '&status=ongoing');

        $response->assertOk()->assertJsonPath('success', true)->assertJsonPath('vouchers.0.voucher_id', 456)->assertJsonPath('vouchers.0.voucher_status', 'ongoing');
        $this->assertDatabaseHas('marketplace_vouchers', ['store_id' => $store->id, 'voucher_id' => 456, 'voucher_code' => 'HEMAT10']);
    }

    public function test_voucher_create_forwards_and_returns_id(): void
    {
        $this->withoutMiddleware();
        $store = $this->store();
        Http::fake(['*/api/v2/voucher/add_voucher*' => Http::response(['response' => ['voucher_id' => 789]])]);

        $response = $this->postJson('/api/marketplace/vouchers', [
            'store_id' => $store->id, 'voucher_name' => 'Hemat', 'voucher_code' => 'HEMAT10',
            'start_time' => now()->addHour()->timestamp, 'end_time' => now()->addDays(7)->timestamp,
            'voucher_type' => 1, 'reward_type' => 1, 'usage_quantity' => 100,
            'min_basket_price' => 100000, 'discount_amount' => 10000, 'display_channel_list' => [1],
        ]);

        $response->assertOk()->assertJsonPath('success', true)->assertJsonPath('voucher_id', 789);
        $this->assertDatabaseHas('marketplace_vouchers', ['store_id' => $store->id, 'voucher_id' => 789, 'voucher_name' => 'Hemat']);
    }
}
