<?php

namespace App\Console\Commands;

use App\Models\MarketplaceOrder;
use App\Models\MarketplaceOrderItem;
use App\Models\MarketplaceOrderSettlement;
use App\Models\Store;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SeedPromotionDashboardDummy extends Command
{
    private const SOURCE = 'promotion_dashboard_v1';

    protected $signature = 'marketplace:seed-promotion-dashboard-dummy
                            {--store= : ID toko yang dipakai untuk order dummy}
                            {--reset : Hapus dummy promosi sebelumnya sebelum membuat ulang}';

    protected $description = '[DEV ONLY] Buat dummy promosi harian untuk dashboard penjualan.';

    public function handle(): int
    {
        if (app()->isProduction()) {
            $this->error('Command ini hanya boleh dijalankan di lokal/testing.');

            return self::FAILURE;
        }

        $store = $this->option('store')
            ? Store::find($this->option('store'))
            : Store::query()->first();

        if (! $store) {
            $this->error('Tidak ada toko lokal. Buat toko terlebih dahulu.');

            return self::FAILURE;
        }

        $existingIds = MarketplaceOrder::query()
            ->whereJsonContains('meta->dummy_source', self::SOURCE)
            ->pluck('id');

        if ($existingIds->isNotEmpty() && ! $this->option('reset')) {
            $this->error('Dummy promosi sudah ada. Jalankan ulang dengan --reset jika ingin menggantinya.');

            return self::FAILURE;
        }

        $rows = $this->promotionRows();

        DB::transaction(function () use ($existingIds, $rows, $store): void {
            if ($existingIds->isNotEmpty()) {
                MarketplaceOrderSettlement::whereIn('order_id', $existingIds)->delete();
                MarketplaceOrderItem::whereIn('marketplace_order_id', $existingIds)->delete();
                MarketplaceOrder::whereKey($existingIds)->delete();
            }

            foreach ($rows as $row) {
                $this->createPromotionOrder($store, $row);
            }
        });

        $this->info(sprintf(
            '✅ %d dummy order promosi dibuat untuk toko "%s".',
            count($rows),
            $store->name,
        ));
        $this->line('Buka: /marketplace/dashboard/sales?dummy=1&date_from=2026-09-06&date_to=2026-10-06');

        return self::SUCCESS;
    }

    private function createPromotionOrder(Store $store, array $row): void
    {
        $date = Carbon::createFromFormat('Y-m-d H:i:s', $row['date'].' 12:00:00');
        $orderSn = 'DUMMY-PROMO-'.str_replace('-', '', $row['date']);
        $subtotal = 11_000_000;
        $hiddenDiscount = $row['total'] - $row['seller_voucher'] - $row['platform_voucher'] - $row['bundle_discount'];
        $buyerPaid = $subtotal - $row['total'];

        $items = [
            [
                'activity_id' => 'dummy-bundle-'.$row['date'],
                'activity_type' => 'bundle_deal',
                'promotion_list' => [['promotion_type' => 'bundle_deal']],
                'original_price' => 1_000_000,
                'selling_price' => 1_000_000,
                'discounted_price' => 1_000_000 - $row['bundle_discount'],
                'seller_discount' => $row['bundle_discount'],
                'quantity_purchased' => 1,
                'item_id' => 'DUMMY-BUNDLE',
                'item_name' => 'Dummy Paket Diskon',
                'item_sku' => 'DUMMY-BUNDLE',
            ],
            [
                'activity_id' => 'dummy-product-'.$row['date'],
                'activity_type' => 'seller_discount',
                'promotion_list' => [['promotion_type' => 'seller_discount']],
                'original_price' => 10_000_000,
                'selling_price' => 10_000_000,
                'discounted_price' => 10_000_000 - $hiddenDiscount,
                'seller_discount' => $hiddenDiscount,
                'quantity_purchased' => 1,
                'item_id' => 'DUMMY-PRODUCT',
                'item_name' => 'Dummy Produk Promosi',
                'item_sku' => 'DUMMY-PRODUCT',
            ],
        ];

        $raw = [
            'order_status' => 'COMPLETED',
            'voucher_from_seller' => $row['seller_voucher'],
            'voucher_from_shopee' => $row['platform_voucher'],
            'voucher_from_external_party' => 0,
            'seller_discount' => $hiddenDiscount + $row['bundle_discount'],
            'items' => $items,
            'item_list' => $items,
        ];

        $order = MarketplaceOrder::create([
            'store_id' => $store->id,
            'channel_order_id' => $orderSn,
            'external_order_id' => $orderSn,
            'order_date' => $date,
            'ordered_at' => $date,
            'status' => 'completed',
            'order_status' => 'COMPLETED',
            'buyer_name' => 'Dummy Promosi',
            'buyer_username' => 'dummy_promotion_dashboard',
            'payment_method' => 'ShopeePay',
            'payment_status' => 'paid',
            'shipping_carrier' => 'Reguler Dummy',
            'currency' => 'IDR',
            'subtotal_items' => $subtotal,
            'total_amount' => $subtotal,
            'total_paid_customer' => $buyerPaid,
            'raw_json' => $raw,
            'raw_payload_json' => json_encode(['item_list' => $items]),
            'meta' => [
                'is_dummy' => true,
                'dummy_source' => self::SOURCE,
                'dummy_label' => 'Dashboard promosi harian',
            ],
            'synced_at' => now(),
            'completed_at' => $date->copy()->addHour(),
        ]);

        foreach ($items as $index => $item) {
            MarketplaceOrderItem::create([
                'marketplace_order_id' => $order->id,
                'order_id' => $order->id,
                'line_no' => $index + 1,
                'external_item_id' => $item['item_id'],
                'external_model_id' => $item['item_id'].'-MODEL',
                'item_name' => $item['item_name'],
                'item_sku' => $item['item_sku'],
                'model_sku' => $item['item_sku'],
                'marketplace_sku' => $item['item_sku'],
                'qty' => 1,
                'price' => $item['selling_price'],
                'price_original' => $item['original_price'],
                'price_after_discount' => $item['discounted_price'],
                'line_discount' => $item['seller_discount'],
                'line_gross_amount' => $item['selling_price'],
                'line_net_amount' => $item['discounted_price'],
                'mapping_status' => 'mapped',
                'cost_status' => 'complete',
                'profit_status' => 'complete',
                'data_status' => 'valid',
                'issue_reason' => null,
            ]);
        }

        MarketplaceOrderSettlement::create([
            'store_id' => $store->id,
            'order_id' => $order->id,
            'channel_order_id' => $orderSn,
            'buyer_payment_amount' => $buyerPaid,
            'seller_voucher' => $row['seller_voucher'],
            'shipping_fee_subsidy' => 0,
            'final_income' => $buyerPaid,
            'settlement_time' => $date->copy()->addHour(),
            'synced_at' => now(),
            'data_status' => 'valid',
            'raw_json' => $raw,
        ]);
    }

    private function promotionRows(): array
    {
        return [
            ['date' => '2026-10-06', 'seller_voucher' => 73400, 'platform_voucher' => 421159, 'bundle_discount' => 61694, 'total' => 1977540],
            ['date' => '2026-10-05', 'seller_voucher' => 42400, 'platform_voucher' => 224842, 'bundle_discount' => 0, 'total' => 993290],
            ['date' => '2026-10-04', 'seller_voucher' => 42000, 'platform_voucher' => 289674, 'bundle_discount' => 5000, 'total' => 953475],
            ['date' => '2026-10-03', 'seller_voucher' => 43000, 'platform_voucher' => 296651, 'bundle_discount' => 0, 'total' => 1007539],
            ['date' => '2026-10-02', 'seller_voucher' => 53600, 'platform_voucher' => 156449, 'bundle_discount' => 0, 'total' => 1217207],
            ['date' => '2026-10-01', 'seller_voucher' => 13600, 'platform_voucher' => 251969, 'bundle_discount' => 0, 'total' => 1747234],
            ['date' => '2026-09-30', 'seller_voucher' => 3400, 'platform_voucher' => 236921, 'bundle_discount' => 0, 'total' => 771522],
            ['date' => '2026-09-29', 'seller_voucher' => 73600, 'platform_voucher' => 400243, 'bundle_discount' => 7247, 'total' => 1293332],
            ['date' => '2026-09-28', 'seller_voucher' => 74000, 'platform_voucher' => 236396, 'bundle_discount' => 5000, 'total' => 910445],
            ['date' => '2026-09-27', 'seller_voucher' => 37000, 'platform_voucher' => 186984, 'bundle_discount' => 0, 'total' => 835836],
            ['date' => '2026-09-26', 'seller_voucher' => 124400, 'platform_voucher' => 365738, 'bundle_discount' => 62200, 'total' => 1668278],
            ['date' => '2026-09-25', 'seller_voucher' => 83800, 'platform_voucher' => 318407, 'bundle_discount' => 0, 'total' => 1080705],
            ['date' => '2026-09-24', 'seller_voucher' => 72200, 'platform_voucher' => 197886, 'bundle_discount' => 47200, 'total' => 923811],
            ['date' => '2026-09-23', 'seller_voucher' => 91400, 'platform_voucher' => 324343, 'bundle_discount' => 160900, 'total' => 1124103],
            ['date' => '2026-09-22', 'seller_voucher' => 40000, 'platform_voucher' => 399342, 'bundle_discount' => 79630, 'total' => 1526217],
            ['date' => '2026-09-21', 'seller_voucher' => 39200, 'platform_voucher' => 253601, 'bundle_discount' => 5749, 'total' => 1228741],
            ['date' => '2026-09-20', 'seller_voucher' => 24200, 'platform_voucher' => 177970, 'bundle_discount' => 48850, 'total' => 551631],
            ['date' => '2026-09-19', 'seller_voucher' => 28400, 'platform_voucher' => 169485, 'bundle_discount' => 7498, 'total' => 790394],
            ['date' => '2026-09-18', 'seller_voucher' => 38000, 'platform_voucher' => 106907, 'bundle_discount' => 50161, 'total' => 893092],
            ['date' => '2026-09-17', 'seller_voucher' => 44000, 'platform_voucher' => 392318, 'bundle_discount' => 26996, 'total' => 1543982],
            ['date' => '2026-09-16', 'seller_voucher' => 60000, 'platform_voucher' => 468420, 'bundle_discount' => 21497, 'total' => 1517402],
            ['date' => '2026-09-15', 'seller_voucher' => 124400, 'platform_voucher' => 365045, 'bundle_discount' => 6000, 'total' => 1318966],
            ['date' => '2026-09-14', 'seller_voucher' => 93000, 'platform_voucher' => 307266, 'bundle_discount' => 13247, 'total' => 847024],
            ['date' => '2026-09-13', 'seller_voucher' => 23800, 'platform_voucher' => 334445, 'bundle_discount' => 7000, 'total' => 709114],
            ['date' => '2026-09-12', 'seller_voucher' => 52400, 'platform_voucher' => 381244, 'bundle_discount' => 158147, 'total' => 1118188],
            ['date' => '2026-09-11', 'seller_voucher' => 42400, 'platform_voucher' => 244671, 'bundle_discount' => 16745, 'total' => 845664],
            ['date' => '2026-09-10', 'seller_voucher' => 58400, 'platform_voucher' => 519716, 'bundle_discount' => 163942, 'total' => 1840220],
            ['date' => '2026-09-09', 'seller_voucher' => 123800, 'platform_voucher' => 1998148, 'bundle_discount' => 203764, 'total' => 5330363],
            ['date' => '2026-09-08', 'seller_voucher' => 14600, 'platform_voucher' => 345658, 'bundle_discount' => 160247, 'total' => 857735],
            ['date' => '2026-09-07', 'seller_voucher' => 38800, 'platform_voucher' => 290447, 'bundle_discount' => 10498, 'total' => 979754],
            ['date' => '2026-09-06', 'seller_voucher' => 36400, 'platform_voucher' => 367316, 'bundle_discount' => 162391, 'total' => 1406568],
        ];
    }
}
