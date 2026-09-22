<?php

namespace Tests\Feature;

use App\Http\Controllers\MarketplaceBookingController;
use App\Models\Channel;
use App\Models\MarketplaceBooking;
use App\Models\Store;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class MarketplaceBookingStoredTest extends TestCase
{
    use RefreshDatabase;

    public function test_stored_endpoint_exposes_warehouse_stage_for_shipped_booking(): void
    {
        $channel = Channel::create(['code' => 'shopee', 'name' => 'Shopee']);
        $store = Store::create([
            'code' => 'SHOPEE-BOOKING-TEST',
            'name' => 'Toko Test',
            'channel_id' => $channel->id,
            'status' => 'active',
            'is_active' => true,
        ]);

        MarketplaceBooking::create([
            'store_id' => $store->id,
            'booking_sn' => '260915AASADZEU6ZP7Q',
            'order_sn' => '260915AASADZEU6ZP7Q',
            'booking_status' => 'SHIPPED',
            'tracking_number' => 'SPXID067342336109',
        ]);

        $response = app(MarketplaceBookingController::class)->stored(
            Request::create('/api/marketplace/bookings/stored', 'GET')
        );

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('warehouse', $response->getData(true)['data'][0]['fulfillment_stage']);
    }
}
