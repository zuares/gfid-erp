<?php

namespace App\Support;

use Illuminate\Support\Collection;
use Carbon\Carbon;

final class DummyMarketplaceOrderProvider
{
    public function orders(): Collection
    {
        $today = Carbon::now();
        $yesterday = Carbon::now()->subDay();

        return collect([
            // 1. Toko A — Reguler — Belum dicetak
            $this->makeOrder([
                'id' => 9001,
                'store_id' => 101,
                'store_name' => 'Greatfit Official',
                'channel_name' => 'Shopee',
                'channel_order_id' => 'DUMMY-A-001',
                'shipping_carrier' => 'Reguler - J&T Express',
                'status' => 'READY_TO_SHIP',
                'printed_at' => null,
                'print_count' => 0,
                'ordered_at' => $yesterday->copy()->subHours(2),
                'dummy_print_result' => 'success',
            ]),
            // 2. Toko A — Reguler — Sudah dicetak
            $this->makeOrder([
                'id' => 9002,
                'store_id' => 101,
                'store_name' => 'Greatfit Official',
                'channel_name' => 'Shopee',
                'channel_order_id' => 'DUMMY-A-002',
                'shipping_carrier' => 'Reguler - JNE',
                'status' => 'READY_TO_SHIP',
                'printed_at' => $today->copy()->subMinutes(10),
                'print_count' => 1,
                'ordered_at' => $yesterday->copy()->subHours(3),
                'dummy_print_result' => 'success',
            ]),
            // 3. Toko A — Instan — Belum dicetak
            $this->makeOrder([
                'id' => 9003,
                'store_id' => 101,
                'store_name' => 'Greatfit Official',
                'channel_name' => 'Shopee',
                'channel_order_id' => 'DUMMY-A-003',
                'shipping_carrier' => 'Instant - GrabExpress',
                'status' => 'READY_TO_SHIP',
                'printed_at' => null,
                'print_count' => 0,
                'ordered_at' => $today->copy()->subMinutes(30),
                'dummy_print_result' => 'success',
            ]),
            // 4. Toko A — Instan — Dokumen belum tersedia
            $this->makeOrder([
                'id' => 9004,
                'store_id' => 101,
                'store_name' => 'Greatfit Official',
                'channel_name' => 'Shopee',
                'channel_order_id' => 'DUMMY-A-004',
                'shipping_carrier' => 'Instant - GoSend',
                'status' => 'READY_TO_SHIP',
                'printed_at' => null,
                'print_count' => 0,
                'ordered_at' => $today->copy()->subMinutes(15),
                'dummy_print_result' => 'document_not_ready',
            ]),
            // 5. Toko B — Reguler — Belum dicetak
            $this->makeOrder([
                'id' => 9005,
                'store_id' => 102,
                'store_name' => 'Greatfit Store B',
                'channel_name' => 'Shopee',
                'channel_order_id' => 'DUMMY-B-001',
                'shipping_carrier' => 'Reguler - SPX Standard',
                'status' => 'READY_TO_SHIP',
                'printed_at' => null,
                'print_count' => 0,
                'ordered_at' => $yesterday->copy()->subHours(1),
                'dummy_print_result' => 'success',
            ]),
            // 6. Toko B — Reguler — Sudah dicetak
            $this->makeOrder([
                'id' => 9006,
                'store_id' => 102,
                'store_name' => 'Greatfit Store B',
                'channel_name' => 'Shopee',
                'channel_order_id' => 'DUMMY-B-002',
                'shipping_carrier' => 'Reguler - Sicepat',
                'status' => 'READY_TO_SHIP',
                'printed_at' => $today->copy()->subHours(1),
                'print_count' => 2,
                'ordered_at' => $yesterday->copy()->subHours(5),
                'dummy_print_result' => 'success',
            ]),
            // 7. Toko B — Instan — Belum dicetak
            $this->makeOrder([
                'id' => 9007,
                'store_id' => 102,
                'store_name' => 'Greatfit Store B',
                'channel_name' => 'Shopee',
                'channel_order_id' => 'DUMMY-B-003',
                'shipping_carrier' => 'Same Day - GrabExpress',
                'status' => 'READY_TO_SHIP',
                'printed_at' => null,
                'print_count' => 0,
                'ordered_at' => $today->copy()->subMinutes(50),
                'dummy_print_result' => 'success',
            ]),
            // 8. Toko B — Instan — Token gagal
            $this->makeOrder([
                'id' => 9008,
                'store_id' => 102,
                'store_name' => 'Greatfit Store B',
                'channel_name' => 'Shopee',
                'channel_order_id' => 'DUMMY-B-004',
                'shipping_carrier' => 'Instant - GoSend',
                'status' => 'READY_TO_SHIP',
                'printed_at' => null,
                'print_count' => 0,
                'ordered_at' => $today->copy()->subMinutes(5),
                'dummy_print_result' => 'token_expired',
            ]),
            // 9. Toko C — Reguler — Belum dicetak
            $this->makeOrder([
                'id' => 9009,
                'store_id' => 103,
                'store_name' => 'Greatfit Official Store C',
                'channel_name' => 'Shopee',
                'channel_order_id' => 'DUMMY-C-001',
                'shipping_carrier' => 'Reguler - J&T Express',
                'status' => 'READY_TO_SHIP',
                'printed_at' => null,
                'print_count' => 0,
                'ordered_at' => $today->copy()->subHours(2),
                'dummy_print_result' => 'success',
            ]),
            // 10. Toko C — Reguler — PDF rusak
            $this->makeOrder([
                'id' => 9010,
                'store_id' => 103,
                'store_name' => 'Greatfit Official Store C',
                'channel_name' => 'Shopee',
                'channel_order_id' => 'DUMMY-C-002',
                'shipping_carrier' => 'Reguler - JNE',
                'status' => 'READY_TO_SHIP',
                'printed_at' => null,
                'print_count' => 0,
                'ordered_at' => $today->copy()->subHours(3),
                'dummy_print_result' => 'pdf_corrupt',
            ]),
        ]);
    }

    private function makeOrder(array $data): array
    {
        return [
            'id' => $data['id'],
            'marketplace_store_id' => $data['store_id'],
            'store_id' => $data['store_id'],
            'channel_order_id' => $data['channel_order_id'],
            'order_status' => $data['status'],
            'shipping_carrier' => $data['shipping_carrier'],
            'shipping_awb_no' => 'AWB-DUMMY-' . $data['id'],
            'logistics_status' => 'LOGISTICS_READY_TO_SHIP',
            'ordered_at' => $data['ordered_at']->toISOString(),
            'created_at' => clone $data['ordered_at'],
            'updated_at' => Carbon::now(),
            'printed_at' => $data['printed_at'] ? $data['printed_at']->toISOString() : null,
            'print_count' => $data['print_count'],
            'dummy_print_result' => $data['dummy_print_result'],
            'store' => [
                'id' => $data['store_id'],
                'name' => $data['store_name'],
                'channel' => [
                    'id' => 1,
                    'name' => $data['channel_name'],
                ],
            ],
            'items' => [
                [
                    'id' => $data['id'] * 10,
                    'marketplace_order_id' => $data['id'],
                    'item_name' => 'Dummy Item ' . $data['id'],
                    'variant_name' => 'L / Hitam',
                    'item_sku' => 'SKU-DUMMY',
                    'model_sku' => 'VAR-DUMMY',
                    'qty' => 1,
                    'internal_item' => [
                        'id' => 999,
                        'code' => 'INT-DUMMY',
                        'item_category_id' => 1,
                        'category' => ['id' => 1, 'code' => 'CAT', 'name' => 'Kategori Dummy']
                    ]
                ]
            ],
            'fulfillment' => [
                'id' => $data['id'] * 100,
                'marketplace_order_id' => $data['id'],
                'status' => 'pending_review',
                'scan_log' => [],
                'lines' => []
            ]
        ];
    }
}
