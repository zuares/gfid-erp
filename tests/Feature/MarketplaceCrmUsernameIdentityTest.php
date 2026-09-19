<?php

namespace Tests\Feature;

use App\Models\Channel;
use App\Models\Customer;
use App\Models\MarketplaceOrder;
use App\Models\Store;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MarketplaceCrmUsernameIdentityTest extends TestCase
{
    use RefreshDatabase;

    private Store $store;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware();

        $channel = Channel::create(['code' => 'shopee', 'name' => 'Shopee']);
        $this->store = Store::create([
            'channel_id' => $channel->id,
            'code' => 'CRM-USERNAME-STORE',
            'name' => 'CRM Username Store',
            'status' => 'active',
            'is_active' => true,
            'credentials' => ['access_token' => 'test-token'],
        ]);

        $legacyChannelId = DB::table('marketplace_channels')->insertGetId([
            'code' => 'shopee',
            'name' => 'Shopee',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('marketplace_stores')->insert([
            'id' => $this->store->id,
            'channel_id' => $legacyChannelId,
            'name' => $this->store->name,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_crm_counts_and_groups_buyers_by_normalized_username_on_each_customer_tab(): void
    {
        $firstAccount = Customer::create(['name' => 'Buyer First', 'phone' => '081234567890']);
        $secondAccount = Customer::create(['name' => 'Buyer Second', 'phone' => '081234567891']);
        $oneTimeAccount = Customer::create(['name' => 'Buyer One Time', 'phone' => '081234567892']);

        $this->createOrder('CRM-USERNAME-1', 'SameBuyer', $firstAccount);
        $this->createOrder('CRM-USERNAME-2', ' samebuyer ', $secondAccount);
        $this->createOrder('CRM-LEGACY-1', '', $secondAccount);
        $this->createOrder('CRM-ONE-TIME', 'one_time_buyer', $oneTimeAccount);

        $this->get('/admin/crm/marketplace')
            ->assertOk()
            ->assertSee('username unik pada order periode ini')
            ->assertSee('username dengan lebih dari satu order');

        $this->get('/admin/crm/marketplace/orders')
            ->assertOk()
            ->assertSeeInOrder(['Customer unik', '3', 'berdasarkan username marketplace']);

        $this->get('/admin/crm/marketplace/customers')
            ->assertOk()
            ->assertSee('3 username customer dalam scope')
            ->assertSee('@one_time_buyer');

        $this->get('/admin/crm/marketplace/prospects')
            ->assertOk()
            ->assertSee('2 prospects')
            ->assertSee('@one_time_buyer');

        $this->get('/admin/crm/marketplace/segments')
            ->assertOk();

        $this->get('/admin/crm/marketplace/segments/loyal')
            ->assertOk();
    }

    private function createOrder(string $orderId, string $username, Customer $customer): void
    {
        MarketplaceOrder::create([
            'store_id' => $this->store->id,
            'external_order_id' => $orderId,
            'channel_order_id' => $orderId,
            'order_date' => now(),
            'ordered_at' => now(),
            'status' => 'completed',
            'buyer_username' => $username,
            'customer_id' => $customer->id,
            'total_amount' => 100_000,
            'total_paid_customer' => 100_000,
        ]);
    }
}
