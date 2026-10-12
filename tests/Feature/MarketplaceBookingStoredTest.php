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

    private function activeStore(string $code = 'KILAT'): Store
    {
        $channel = Channel::firstOrCreate(['code' => 'shopee'], ['name' => 'Shopee']);

        return Store::create([
            'code' => $code, 'name' => $code, 'channel_id' => $channel->id,
            'status' => 'active', 'is_active' => true,
        ]);
    }

    private function stored(array $params = []): array
    {
        return app(MarketplaceBookingController::class)->stored(
            Request::create('/api/marketplace/bookings/stored', 'GET', $params)
        )->getData(true);
    }

    public function test_seller_bookings_without_dates_remain_reachable_beyond_the_old_thousand_row_limit(): void
    {
        $store = $this->activeStore();
        $rows = [];
        for ($i = 0; $i < 1005; $i++) {
            $rows[] = [
                'store_id' => $store->id, 'booking_sn' => "HISTORY-{$i}",
                'booking_status' => 'MATCHED', 'create_time' => 1791766800,
            ];
        }
        MarketplaceBooking::insert($rows);
        MarketplaceBooking::create([
            'store_id' => $store->id, 'booking_sn' => '261011AAR6JZMLGXZRI',
            'booking_status' => 'PROCESSED', 'tracking_number' => 'SPXID06902531824A',
        ]);
        MarketplaceBooking::create([
            'store_id' => $store->id, 'booking_sn' => 'UNDATED-READY', 'booking_status' => 'READY_TO_SHIP',
        ]);

        $seller = $this->stored(['tab' => 'ready']);
        $this->assertSame(1007, $seller['counts']['all']);
        $this->assertSame(2, $seller['counts']['ready']);
        $this->assertCount(2, $seller['data']);
        $this->assertEqualsCanonicalizing(['261011AAR6JZMLGXZRI', 'UNDATED-READY'], array_column($seller['data'], 'booking_sn'));
        $this->assertSame(['all' => 2, 'to_arrange' => 1, 'packing' => 1, 'ready_ship' => 0], $seller['ready_counts']);
        $found = $this->stored(['tab' => 'ready', 'sub' => 'packing', 'search' => 'SPXID06902531824A']);
        $this->assertSame('261011AAR6JZMLGXZRI', $found['data'][0]['booking_sn']);
        $this->assertSame(1, $found['pagination']['total']);

        // Equal timestamps and null dates must never cause rows to be skipped or duplicated.
        $seen = [];
        for ($page = 1; $page <= 11; $page++) {
            $result = $this->stored(['tab' => 'all', 'page' => $page, 'per_page' => 100]);
            $this->assertSame(1007, $result['counts']['all']);
            $seen = array_merge($seen, array_column($result['data'], 'booking_sn'));
        }
        $this->assertCount(1007, array_unique($seen));
        $this->assertCount(1007, $seen);
        $this->assertContains('261011AAR6JZMLGXZRI', $seen);
        $last = $this->stored(['page' => 999, 'per_page' => 5000]);
        $this->assertSame(11, $last['pagination']['current_page']);
        $this->assertSame(100, $last['pagination']['per_page']);
        $this->assertSame(1001, $last['pagination']['from']);
        $this->assertSame(1007, $last['pagination']['to']);
    }

    public function test_seller_subtabs_use_shipping_artifacts_and_fulfillment_not_visibility_flag(): void
    {
        $store = $this->activeStore();
        $cases = [
            ['ARRANGE', 'READY_TO_SHIP', null, 'to_arrange'],
            ['PROCESSED-NO-AWB', 'PROCESSED', null, 'to_arrange'],
            ['PACKING', 'PROCESSED', 'AWB-PACKING', 'packing'],
            ['READY-WITH-AWB', 'READY_TO_SHIP', 'AWB-READY', 'packing'],
            ['CONFIRMED', 'PROCESSED', 'AWB-CONFIRMED', 'ready_ship'],
            ['WAREHOUSE', 'SHIPPED', 'AWB-WAREHOUSE', 'ready_ship'],
            ['HANDOVER', 'READY_TO_HANDOVER', 'AWB-HANDOVER', 'ready_ship'],
        ];
        foreach ($cases as [$sn, $status, $awb]) {
            MarketplaceBooking::create([
                'store_id' => $store->id, 'booking_sn' => $sn,
                'booking_status' => $status, 'tracking_number' => $awb,
            ]);
        }
        $order = MarketplaceOrder::create([
            'store_id' => $store->id, 'external_order_id' => 'CONFIRMED-ORDER',
            'channel_order_id' => 'CONFIRMED-ORDER', 'booking_sn' => 'CONFIRMED', 'order_date' => now(),
        ]);
        $order->fulfillment()->create(['status' => 'confirmed']);
        foreach (['MATCHED', 'COMPLETED', 'CANCELLED', 'CANCELLED_BEFORE_SHIPPING', 'FAILED'] as $status) {
            MarketplaceBooking::create([
                'store_id' => $store->id, 'booking_sn' => $status,
                'booking_status' => $status, 'tracking_number' => "AWB-{$status}",
            ]);
        }

        $result = $this->stored(['tab' => 'ready', 'per_page' => 1]);
        $this->assertSame(7, $result['pagination']['total']);
        $this->assertSame(['all' => 7, 'to_arrange' => 2, 'packing' => 2, 'ready_ship' => 3], $result['ready_counts']);
        $this->assertSame(1, $result['counts']['warehouse']);
        $this->assertSame(2, $result['counts']['shipped']);
        $this->assertSame(3, $result['counts']['cancelled']);
        foreach (['to_arrange', 'packing', 'ready_ship'] as $sub) {
            $result = $this->stored(['tab' => 'ready', 'sub' => $sub]);
            $expected = array_column(array_filter($cases, fn ($row) => $row[3] === $sub), 0);
            $this->assertEqualsCanonicalizing($expected, array_column($result['data'], 'booking_sn'));
            foreach ($result['data'] as $row) {
                $this->assertTrue($row['seller_process_visible']);
                $this->assertSame($sub, $row['ready_sub']);
            }
        }
    }

    public function test_linked_awb_search_does_not_match_unrelated_bookings_or_other_stores(): void
    {
        $store = $this->activeStore();
        $other = $this->activeStore('OTHER');
        foreach ([$store, $other] as $s) {
            foreach (['LINKED', 'UNRELATED'] as $sn) {
                MarketplaceBooking::create([
                    'store_id' => $s->id, 'booking_sn' => $sn, 'booking_status' => 'PROCESSED',
                ]);
            }
        }
        MarketplaceOrder::create([
            'store_id' => $store->id, 'external_order_id' => 'ORDER-LINKED',
            'channel_order_id' => 'ORDER-LINKED', 'booking_sn' => 'LINKED',
            'order_date' => now(), 'shipping_awb_no' => 'SPXID06902531824A',
        ]);
        $result = $this->stored(['search' => 'SPXID06902531824A', 'tab' => 'ready', 'sub' => 'packing']);
        $this->assertCount(1, $result['data']);
        $this->assertSame('LINKED', $result['data'][0]['booking_sn']);
        $this->assertSame($store->id, $result['data'][0]['store_id']);
        $this->assertFalse($result['data'][0]['needs_shipping']);
        $this->assertSame(1, $result['counts']['all']);
        $this->assertSame(0, $this->stored(['search' => 'SPXID06902531824A', 'store_id' => $other->id])['pagination']['total']);
        $this->assertSame(2, $this->stored(['store_id' => $other->id])['counts']['ready']);
    }

    public function test_explicit_order_link_takes_precedence_over_booking_link(): void
    {
        $store = $this->activeStore();
        MarketplaceBooking::create([
            'store_id' => $store->id, 'booking_sn' => 'BOOKING',
            'order_sn' => 'EXPLICIT', 'booking_status' => 'PROCESSED',
        ]);
        foreach (['EXPLICIT', 'FALLBACK'] as $sn) {
            MarketplaceOrder::create([
                'store_id' => $store->id, 'external_order_id' => $sn, 'channel_order_id' => $sn,
                'booking_sn' => 'BOOKING', 'order_date' => now(), 'shipping_awb_no' => "AWB-{$sn}",
            ]);
        }
        $this->assertSame('AWB-EXPLICIT', $this->stored()['data'][0]['tracking_number']);
        $this->assertSame(0, $this->stored(['search' => 'AWB-FALLBACK'])['pagination']['total']);
    }

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

    public function test_stored_endpoint_searches_awb_from_linked_order(): void
    {
        $channel = Channel::create(['code' => 'shopee', 'name' => 'Shopee']);
        $store = Store::create([
            'code' => 'SHOPEE-AWB-SEARCH-TEST',
            'name' => 'Toko AWB Search Test',
            'channel_id' => $channel->id,
            'status' => 'active',
            'is_active' => true,
        ]);
        $bookingSn = 'BOOKING-AWB-SEARCH-TEST';

        MarketplaceBooking::create([
            'store_id' => $store->id,
            'booking_sn' => $bookingSn,
            'order_sn' => 'ORDER-AWB-SEARCH-TEST',
            'booking_status' => 'SHIPPED',
        ]);
        MarketplaceOrder::create([
            'store_id' => $store->id,
            'external_order_id' => 'ORDER-AWB-SEARCH-TEST',
            'channel_order_id' => 'ORDER-AWB-SEARCH-TEST',
            'booking_sn' => $bookingSn,
            'order_status' => 'SHIPPED',
            'order_date' => now(),
            'ordered_at' => now(),
            'shipping_awb_no' => 'SPXID06902531824A',
        ]);

        $response = app(MarketplaceBookingController::class)->stored(
            Request::create('/api/marketplace/bookings/stored', 'GET', [
                'search' => 'SPXID06902531824A',
            ])
        );
        $booking = collect($response->getData(true)['data'])
            ->firstWhere('booking_sn', $bookingSn);

        $this->assertNotNull($booking);
        $this->assertSame('SPXID06902531824A', $booking['tracking_number']);
    }

    public function test_ready_to_ship_and_processed_bookings_are_seller_process_visible(): void
    {
        $channel = Channel::create(['code' => 'shopee', 'name' => 'Shopee']);
        $store = Store::create([
            'code' => 'SHOPEE-READY-TEST',
            'name' => 'Toko Ready Test',
            'channel_id' => $channel->id,
            'status' => 'active',
            'is_active' => true,
        ]);

        foreach (['READY_TO_SHIP', 'PROCESSED'] as $index => $status) {
            MarketplaceBooking::create([
                'store_id' => $store->id,
                'booking_sn' => "BOOKING-{$status}-TEST",
                'booking_status' => $status,
            ]);
        }

        $response = app(MarketplaceBookingController::class)->stored(
            Request::create('/api/marketplace/bookings/stored', 'GET')
        );
        $rows = collect($response->getData(true)['data']);

        $this->assertTrue($rows->firstWhere('booking_sn', 'BOOKING-READY_TO_SHIP-TEST')['seller_process_visible']);
        $this->assertTrue($rows->firstWhere('booking_sn', 'BOOKING-PROCESSED-TEST')['seller_process_visible']);
    }
}
