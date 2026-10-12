<?php

namespace Tests\Feature;

use App\Http\Controllers\MarketplaceBookingController;
use App\Models\Channel;
use App\Models\MarketplaceBooking;
use App\Models\MarketplaceOrder;
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
        $this->assertTrue($response->getData(true)['data'][0]['seller_process_visible']);
    }

    public function test_stored_endpoint_exposes_financial_data_from_linked_order(): void
    {
        $channel = Channel::create(['code' => 'SHOPEE', 'name' => 'Shopee']);
        $store = Store::create([
            'code' => 'SHOPEE-FINANCIAL-TEST',
            'name' => 'Toko Financial Test',
            'channel_id' => $channel->id,
            'status' => 'active',
            'is_active' => true,
        ]);
        $bookingSn = 'BOOKING-FINANCIAL-TEST';

        MarketplaceBooking::create([
            'store_id' => $store->id,
            'booking_sn' => $bookingSn,
            'order_sn' => 'ORDER-FINANCIAL-TEST',
            'booking_status' => 'MATCHED',
        ]);
        MarketplaceOrder::create([
            'store_id' => $store->id,
            'external_order_id' => 'ORDER-FINANCIAL-TEST',
            'channel_order_id' => 'ORDER-FINANCIAL-TEST',
            'booking_sn' => $bookingSn,
            'order_status' => 'MATCHED',
            'order_date' => now(),
            'ordered_at' => now(),
            'payment_method' => 'QRIS',
            'payment_status' => 'paid',
            'subtotal_items' => 50000,
            'shipping_fee_customer' => 1000,
            'voucher_discount' => 2500,
            'total_paid_customer' => 48500,
            'net_payout_estimated' => 43000,
        ]);

        $response = app(MarketplaceBookingController::class)->stored(
            Request::create('/api/marketplace/bookings/stored', 'GET')
        );
        $booking = collect($response->getData(true)['data'])
            ->firstWhere('booking_sn', $bookingSn);

        $this->assertSame('QRIS', $booking['payment_method']);
        $this->assertSame(48500.0, (float) $booking['payment_breakdown']['buyer_paid']);
        $this->assertSame(2500.0, (float) $booking['payment_breakdown']['voucher_store']);
        $this->assertSame(43000.0, (float) $booking['income']['estimated']);
    }
}
