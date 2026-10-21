<?php

namespace Tests\Feature;

use App\Models\Channel;
use App\Models\Item;
use App\Models\MarketplaceImportBatch;
use App\Models\MarketplaceOrder;
use App\Models\MarketplaceOrderItem;
use App\Models\MarketplaceOrderSettlement;
use App\Models\MarketplaceImportedAdSpend;
use App\Models\MpShipment;
use App\Models\MpIncome;
use App\Models\SkuMapping;
use App\Models\Store;
use App\Models\User;
use App\Services\Marketplace\Import\MpImportService;
use App\Services\Marketplace\Income\ImportedMarketplaceIncomeSyncService;
use App\Services\Marketplace\Income\MpIncomeImportService;
use App\Services\Marketplace\Ads\AdsDashboardService;
use App\Services\Marketplace\MarketplaceAnalyticsSummaryService;
use App\Services\Marketplace\Source\TiktokApiSourceAdapter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class MarketplaceTikTokImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_tiktok_api_adapter_uses_the_same_normalized_shape(): void
    {
        $normalized = app(TiktokApiSourceAdapter::class)->normalize([
            'data' => [
                'orders' => [[
                    'order_id' => 'API-ORDER-001',
                    'status' => 'SHIPPED',
                    'purchase_channel' => 'Tokopedia',
                    'create_time' => 1785750000,
                    'packages' => [[
                        'package_id' => 'API-PACKAGE-001',
                        'tracking_number' => 'API-TRACK-001',
                        'items' => [[
                            'seller_sku' => 'TTB-BLK-M',
                            'quantity' => 2,
                            'unit_price' => 100000,
                        ]],
                    ]],
                ]],
            ],
        ], 7);

        $this->assertCount(1, $normalized);
        $this->assertSame('API-ORDER-001', $normalized[0]['platform_order_id']);
        $this->assertSame('API-PACKAGE-001', $normalized[0]['platform_shipment_id']);
        $this->assertSame('API-TRACK-001', $normalized[0]['tracking_no']);
        $this->assertSame('tokopedia', $normalized[0]['purchase_channel']);
        $this->assertSame('TTB-BLK-M', $normalized[0]['items'][0]['sku_code']);
        $this->assertSame(2, $normalized[0]['items'][0]['qty']);
        $this->assertSame(0.0, $normalized[0]['promotion_breakdown']['voucher_store']);
        $this->assertSame(0.0, $normalized[0]['promotion_breakdown']['bundle_deal_discount']);
    }

    public function test_tiktok_promotion_columns_are_normalized_without_mixing_vouchers_and_shipping(): void
    {
        $normalized = app(TiktokApiSourceAdapter::class)->normalize([
            'data' => [
                'orders' => [[
                    'order_id' => 'API-PROMO-001',
                    'platform_discount' => 5000,
                    'seller_discount' => 7000,
                    'voucher_from_seller' => 2000,
                    'voucher_from_platform' => 1500,
                    'shipping_fee_platform_discount' => 22000,
                    'packages' => [[
                        'items' => [[
                            'seller_sku' => 'TTB-PROMO-M',
                            'quantity' => 1,
                            'original_price' => 100000,
                            'discounted_price' => 86000,
                            'platform_discount' => 5000,
                            'seller_discount' => 7000,
                        ]],
                    ]],
                ]],
            ],
        ], 7);

        $promotion = $normalized[0]['promotion_breakdown'];
        $this->assertSame(12000.0, $promotion['product_discount']);
        $this->assertSame(5000.0, $promotion['platform_discount']);
        $this->assertSame(7000.0, $promotion['seller_discount']);
        $this->assertSame(2000.0, $promotion['voucher_store']);
        $this->assertSame(1500.0, $promotion['voucher_platform']);
        $this->assertSame(22000.0, $promotion['shipping_discount_platform']);
        $this->assertSame(0.0, $promotion['bundle_deal_discount']);
    }

    public function test_owner_can_open_import_page_and_see_sidebar_link(): void
    {
        $owner = User::factory()->create([
            'role' => 'owner',
            'employee_code' => 'TTK-SIDEBAR-'.uniqid(),
        ]);

        $this->actingAs($owner)
            ->get(route('imports.marketplace.index'))
            ->assertOk()
            ->assertSee('Impor Pengiriman')
            ->assertSee('Data Pengiriman')
            ->assertSee('Performa')
            ->assertSee('Riwayat Import')
            ->assertSee(route('imports.marketplace.index'), false);
    }

    public function test_admin_can_open_toko_and_import_from_marketplace_flow(): void
    {
        $channel = Channel::updateOrCreate(
            ['code' => 'TTK'],
            [
                'name' => 'Tiktok',
                'status' => 'active',
                'is_active' => true,
            ]
        );

        $store = Store::create([
            'code' => 'TTK-ADMIN-FLOW',
            'name' => 'TikTok Admin Flow',
            'channel_id' => $channel->id,
            'status' => 'active',
            'is_active' => true,
        ]);
        $offlineChannel = Channel::updateOrCreate(
            ['code' => 'OFFL'],
            [
                'name' => 'Offline',
                'status' => 'active',
                'is_active' => true,
            ]
        );

        $admin = User::factory()->create([
            'role' => 'admin',
            'employee_code' => 'ADMIN-IMPORT-'.uniqid(),
        ]);

        $this->actingAs($admin)
            ->get(route('marketplace.toko'))
            ->assertOk()
            ->assertSee('Import Order');

        $importPage = $this->actingAs($admin)
            ->get(route('imports.marketplace.create', ['store_id' => $store->id]));

        $importPage
            ->assertOk()
            ->assertSee('Upload File')
            ->assertSee('Import Pesanan Marketplace')
            ->assertSee('Tiga langkah singkat')
            ->assertSee('id="marketplaceImportForm"', false)
            ->assertSee('id="importDropzone"', false)
            ->assertSee('id="previewImportButton"', false)
            ->assertSee('data-selected-store-id="'.$store->id.'"', false)
            ->assertSee('value="'.$channel->id.'" selected', false);
        $this->assertFalse($importPage->viewData('channels')->contains('id', $offlineChannel->id));
    }

    public function test_completed_import_batch_can_be_deleted_from_ui(): void
    {
        $channel = Channel::updateOrCreate(
            ['code' => 'TTK'],
            [
                'name' => 'Tiktok',
                'status' => 'active',
                'is_active' => true,
            ]
        );

        $store = Store::create([
            'code' => 'TTK-DELETE-TEST',
            'name' => 'TikTok Delete Test',
            'channel_id' => $channel->id,
            'status' => 'active',
            'is_active' => true,
        ]);

        $owner = User::factory()->create([
            'role' => 'owner',
            'employee_code' => 'TTK-DELETE-'.uniqid(),
        ]);

        $csv = implode("\n", [
            'Order ID,Order Status,SKU ID,Seller SKU,Product Name,Quantity,Order Amount,Created Time,Tracking ID,Package ID',
            'ORDER-DELETE-001,Dikirim,SKU-ID-1,TTB-BLK-M,Jacket Tracktop,1,81406,03/08/2026 11:27:18,JY-DELETE-001,PKG-DELETE-001',
        ]);

        $this->actingAs($owner)
            ->post(route('imports.marketplace.preview'), [
                'channel_id' => $channel->id,
                'store_id' => $store->id,
                'file' => UploadedFile::fake()->createWithContent('delete-test.csv', $csv),
            ])
            ->assertOk();

        $batchId = session('mp_import_preview.import_batch_id');

        $this->actingAs($owner)
            ->post(route('imports.marketplace.commit'))
            ->assertRedirect(route('imports.marketplace.index'));

        $this->assertDatabaseHas('marketplace_import_batches', [
            'id' => $batchId,
            'status' => 'completed',
            'updated_shipments' => 0,
        ]);

        $this->actingAs($owner)
            ->get(route('imports.marketplace.index'))
            ->assertOk()
            ->assertSee('Hapus import')
            ->assertSee(route('imports.marketplace.batches.destroy', $batchId), false);

        $this->actingAs($owner)
            ->get(route('imports.marketplace.create', ['store_id' => $store->id]))
            ->assertOk()
            ->assertSee('Batch import terbaru')
            ->assertSee('Hapus batch import')
            ->assertSee(route('imports.marketplace.batches.destroy', $batchId), false);

        $shipment = MpShipment::query()
            ->where('platform_order_id', 'ORDER-DELETE-001')
            ->firstOrFail();
        $order = MarketplaceOrder::query()
            ->where('channel_order_id', 'ORDER-DELETE-001')
            ->where('store_id', $store->id)
            ->firstOrFail();

        $this->assertDatabaseHas('mp_shipment_items', ['mp_shipment_id' => $shipment->id]);
        $this->assertDatabaseHas('mp_packet_items', ['mp_shipment_id' => (string) $shipment->id]);
        $this->assertDatabaseHas('marketplace_order_items', ['order_id' => $order->id]);

        $this->actingAs($owner)
            ->delete(route('imports.marketplace.batches.destroy', $batchId))
            ->assertRedirect(route('imports.marketplace.index'))
            ->assertSessionHas('success', 'Import dihapus: 1 shipment dan 1 order dashboard.');

        $this->assertDatabaseMissing('marketplace_import_batches', ['id' => $batchId]);
        $this->assertDatabaseMissing('mp_shipments', [
            'platform_order_id' => 'ORDER-DELETE-001',
            'platform_shipment_id' => 'PKG-DELETE-001',
        ]);
        $this->assertDatabaseMissing('marketplace_orders', [
            'channel_order_id' => 'ORDER-DELETE-001',
            'store_id' => $store->id,
        ]);
        $this->assertDatabaseMissing('mp_shipment_items', ['mp_shipment_id' => $shipment->id]);
        $this->assertDatabaseMissing('mp_packet_items', ['mp_shipment_id' => (string) $shipment->id]);
        $this->assertDatabaseMissing('marketplace_order_items', ['order_id' => $order->id]);
    }

    public function test_admin_can_add_edit_and_delete_empty_store(): void
    {
        $channel = Channel::updateOrCreate(
            ['code' => 'TTK'],
            [
                'name' => 'Tiktok',
                'status' => 'active',
                'is_active' => true,
            ]
        );

        $admin = User::factory()->create([
            'role' => 'admin',
            'employee_code' => 'ADMIN-CRUD-'.uniqid(),
        ]);

        $createResponse = $this->actingAs($admin)->postJson('/api/marketplace/stores', [
            'channel_id' => $channel->id,
            'name' => 'Toko Baru Test',
            'code' => 'TTK-CRUD-TEST',
            'region' => 'ID',
        ]);

        $createResponse->assertCreated()->assertJsonPath('store.name', 'Toko Baru Test');
        $storeId = $createResponse->json('store.id');

        $this->actingAs($admin)
            ->patchJson('/api/marketplace/stores/'.$storeId, [
                'name' => 'Toko Edit Test',
                'region' => 'ID-JK',
            ])
            ->assertOk();

        $this->assertDatabaseHas('stores', [
            'id' => $storeId,
            'name' => 'Toko Edit Test',
            'region' => 'ID-JK',
        ]);

        $this->actingAs($admin)
            ->deleteJson('/api/marketplace/stores/'.$storeId)
            ->assertOk()
            ->assertJsonPath('message', 'Toko berhasil dihapus karena belum memiliki riwayat pesanan.');

        $this->assertDatabaseMissing('stores', ['id' => $storeId]);
    }

    public function test_tiktok_csv_preview_and_commit_are_idempotent(): void
    {
        $channel = Channel::updateOrCreate(
            ['code' => 'TTK'],
            [
                'name' => 'Tiktok',
                'status' => 'active',
                'is_active' => true,
            ]
        );

        $store = Store::create([
            'code' => 'TTK-TEST',
            'name' => 'TikTok Test',
            'channel_id' => $channel->id,
            'status' => 'active',
            'is_active' => true,
        ]);

        $user = User::factory()->create([
            'role' => 'owner',
            'employee_code' => 'TTK-TEST-001',
        ]);

        $csv = implode("\n", [
            'Order ID,Order Status,Order Substatus,SKU ID,Seller SKU,Product Name,Variation,Quantity,SKU Unit Original Price,SKU Subtotal Before Discount,SKU Platform Discount,SKU Seller Discount,SKU Subtotal After Discount,Shipping Fee After Discount,Order Amount,Created Time,Shipped Time,Delivered Time,Tracking ID,Package ID,Shipping Provider Name,Payment Method',
            'ORDER-TEST-001,Dikirim,Sedang transit,SKU-ID-1,TTB-BLK-M,Jacket Tracktop,"Hitam, M",1,189950,189950,9403,104472,76075,2000,81406,03/08/2026 11:27:18,03/08/2026 13:57:37,,JY-TEST-001,PKG-TEST-001,J&T Express,Bayar di tempat',
        ]);

        $this->actingAs($user)
            ->post(route('imports.marketplace.preview'), [
                'channel_id' => $channel->id,
                'store_id' => $store->id,
                'file' => UploadedFile::fake()->createWithContent('tiktok-test.csv', $csv),
            ])
            ->assertOk()
            ->assertViewIs('imports.marketplace.preview');

        $batchId = session('mp_import_preview.import_batch_id');
        $this->assertNotEmpty($batchId);
        $this->assertDatabaseHas('marketplace_import_batches', [
            'id' => $batchId,
            'status' => 'preview',
            'channel' => 'tiktok',
        ]);

        $this->actingAs($user)
            ->post(route('imports.marketplace.commit'))
            ->assertRedirect(route('imports.marketplace.index'));

        $this->assertDatabaseHas('mp_shipments', [
            'store_id' => $store->id,
            'channel' => 'tiktok',
            'platform_order_id' => 'ORDER-TEST-001',
            'platform_shipment_id' => 'PKG-TEST-001',
            'tracking_no' => 'JY-TEST-001',
            'grand_total' => 81406,
            'status_norm' => 'in_transit',
        ]);

        $shipment = MpShipment::where('platform_order_id', 'ORDER-TEST-001')->firstOrFail();
        $this->assertSame(9403.0, (float) data_get($shipment->raw_payload, 'promotion_breakdown.platform_discount'));
        $this->assertSame(104472.0, (float) data_get($shipment->raw_payload, 'promotion_breakdown.seller_discount'));
        $this->assertSame(0.0, (float) data_get($shipment->raw_payload, 'promotion_breakdown.voucher_store'));
        $this->assertSame(0.0, (float) data_get($shipment->raw_payload, 'promotion_breakdown.bundle_deal_discount'));

        $this->assertSame(1, MpShipment::where('platform_order_id', 'ORDER-TEST-001')->count());
        $this->assertSame('completed', MarketplaceImportBatch::findOrFail($batchId)->status);

        // Import ulang file yang sama tidak membuat shipment kedua.
        $this->actingAs($user)
            ->post(route('imports.marketplace.preview'), [
                'channel_id' => $channel->id,
                'store_id' => $store->id,
                'file' => UploadedFile::fake()->createWithContent('tiktok-test.csv', $csv),
            ])
            ->assertOk();

        $secondBatchId = session('mp_import_preview.import_batch_id');
        $this->actingAs($user)
            ->post(route('imports.marketplace.commit'))
            ->assertRedirect(route('imports.marketplace.index'));

        $this->assertSame(1, MpShipment::where('platform_order_id', 'ORDER-TEST-001')->count());
        $this->assertSame(1, MarketplaceImportBatch::findOrFail($secondBatchId)->updated_shipments);
    }

    public function test_tiktok_full_export_skips_description_row(): void
    {
        $channel = Channel::updateOrCreate(
            ['code' => 'TTK'],
            [
                'name' => 'Tiktok',
                'status' => 'active',
                'is_active' => true,
            ]
        );

        $store = Store::create([
            'code' => 'TTK-DESCRIPTION-ROW',
            'name' => 'TikTok Description Row Test',
            'channel_id' => $channel->id,
            'status' => 'active',
            'is_active' => true,
        ]);

        $user = User::factory()->create([
            'role' => 'owner',
            'employee_code' => 'TTK-DESCRIPTION-'.uniqid(),
        ]);

        $csv = implode("\n", [
            'Order ID,Order Status,Seller SKU,Product Name,Quantity,SKU Unit Original Price,SKU Subtotal After Discount,Order Amount,Created Time,Shipped Time,Tracking ID,Package ID',
            'Platform unique order ID.,Current order status.,Seller sku input by the seller in the product system.,Platform product name.,SKU sold quantity in the order.,1 SKU original price.,It equals SKU Subtotal Before Discount - SKU Platform Discount - SKU Seller Discount.,Order total amount paid by the buyer.,Order created time.,The time when the order status changes to In Transit.,The order tracking number.,The order package ID',
            '586443354048595556,Dikirim,TTC-BLK-XL,Tracktop Jacket,1,189950,94367,95311,06/10/2026 18:55:47,07/10/2026 12:43:06,JY1996684340,1213665526886401636',
        ]);

        $this->actingAs($user)
            ->post(route('imports.marketplace.preview'), [
                'channel_id' => $channel->id,
                'store_id' => $store->id,
                'file' => UploadedFile::fake()->createWithContent('tiktok-description-row.csv', $csv),
            ])
            ->assertOk()
            ->assertViewIs('imports.marketplace.preview');

        $this->assertSame(1, session('mp_import_preview.stats.shipments_parsed'));
        $this->assertSame(1, session('mp_import_preview.stats.items_parsed'));

        $this->actingAs($user)
            ->post(route('imports.marketplace.commit'))
            ->assertRedirect(route('imports.marketplace.index'));

        $this->assertSame(1, MpShipment::where('store_id', $store->id)->count());
        $this->assertDatabaseHas('mp_shipments', [
            'store_id' => $store->id,
            'platform_order_id' => '586443354048595556',
            'platform_shipment_id' => '1213665526886401636',
            'tracking_no' => 'JY1996684340',
            'grand_total' => 95311,
            'status_norm' => 'in_transit',
        ]);
        $this->assertDatabaseMissing('mp_shipments', [
            'store_id' => $store->id,
            'platform_order_id' => 'Platform unique order ID.',
        ]);

        $this->actingAs($user)
            ->get(route('marketplace.dashboard.sales', [
                'store_id' => $store->id,
                'date_from' => '2026-10-06',
                'date_to' => '2026-10-07',
            ]))
            ->assertOk()
            ->assertSee('586443354048595556')
            ->assertSee('Tracktop Jacket');
    }

    public function test_tiktok_xlsx_export_can_be_previewed_committed_and_shown_on_sales_dashboard(): void
    {
        $channel = Channel::updateOrCreate(
            ['code' => 'TTK'],
            ['name' => 'Tiktok', 'status' => 'active', 'is_active' => true]
        );
        $store = Store::create([
            'code' => 'TTK-XLSX-TEST',
            'name' => 'TikTok XLSX Test',
            'channel_id' => $channel->id,
            'status' => 'active',
            'is_active' => true,
        ]);
        $owner = User::factory()->create([
            'role' => 'owner',
            'employee_code' => 'TTK-XLSX-'.uniqid(),
        ]);

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('OrderSKUList');
        $sheet->fromArray([
            ['Order ID', 'Order Status', 'Seller SKU', 'Product Name', 'Quantity', 'SKU Subtotal After Discount', 'Order Amount', 'Created Time', 'Tracking ID', 'Package ID', 'Purchase Channel'],
            ['Platform unique order ID.', 'Current order status.', 'Seller sku input by the seller in the product system.', 'Platform product name.', 'SKU sold quantity in the order.', 'Subtotal after discount.', 'Order total amount paid by the buyer.', 'Order created time.', 'The order tracking number.', 'The order package ID.', ''],
            ['', 'Dikirim', 'TTC-BLK-XL', 'Tracktop Jacket', 1, 50000, 85000, '12/10/2026 10:30:00', 'JY-XLSX-001', '', 'TikTok'],
            ['', 'Dikirim', 'K5BLK', 'Jogger Hitam', 1, 35000, 85000, '12/10/2026 10:30:00', 'JY-XLSX-001', '', 'TikTok'],
            ['', 'Dikirim', 'S3RDM', 'Boxer Random', 1, 12000, 85000, '12/10/2026 10:30:00', 'JY-XLSX-001', '', 'TikTok'],
            ['586455555555555555', 'Dikirim', 'K7BLK', 'Jogger Tokopedia', 2, 33000, 66000, '12/10/2026 10:40:00', 'JY-XLSX-002', 'PKG-TOKO-001', 'Tokopedia'],
        ]);
        $orderId = '586444444444444444';
        $packageId = '1213665526886401636';
        foreach ([3, 4, 5] as $row) {
            $sheet->setCellValueExplicit("A{$row}", $orderId."\t", DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("J{$row}", ($row === 5 ? '1213665526886401637' : $packageId)."\t", DataType::TYPE_STRING);
        }
        $sheet->setCellValue('I5', 'JY-XLSX-001B');

        $path = tempnam(sys_get_temp_dir(), 'tiktok-xlsx-');
        (new Xlsx($spreadsheet))->save($path);
        $spreadsheet->disconnectWorksheets();

        try {
            $this->actingAs($owner)
                ->post(route('imports.marketplace.preview'), [
                    'channel_id' => $channel->id,
                    'store_id' => $store->id,
                    'file' => new UploadedFile($path, 'tiktok-full-export.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true),
                ])
                ->assertOk()
                ->assertViewIs('imports.marketplace.preview')
                ->assertSee('Asal pesanan')
                ->assertSee('Tokopedia')
                ->assertSee('<div class="shipment-kpi-label">Paket</div>', false)
                ->assertSee('Paket pengiriman terdeteksi')
                ->assertDontSee('Baris terbaca dari file')
                ->assertSee('Ch: <span class="mono">tiktok</span>', false)
                ->assertSee('Ch: <span class="mono">tokopedia</span>', false);
        } finally {
            unlink($path);
        }

        $this->assertSame(3, session('mp_import_preview.stats.shipments_parsed'));
        $this->assertSame(4, session('mp_import_preview.stats.items_parsed'));
        $this->assertSame(1, session('mp_import_preview.stats.purchase_channels.tiktok.orders'));
        $this->assertSame(1, session('mp_import_preview.stats.purchase_channels.tokopedia.orders'));

        $this->actingAs($owner)
            ->post(route('imports.marketplace.commit'))
            ->assertRedirect(route('imports.marketplace.index'));

        $shipment = MpShipment::query()
            ->where('store_id', $store->id)
            ->where('platform_order_id', $orderId)
            ->firstOrFail();
        $this->assertSame($packageId, $shipment->platform_shipment_id);
        $this->assertSame('JY-XLSX-001', $shipment->tracking_no);
        $this->assertSame(2, $shipment->items()->count());
        $this->assertSame(2, MpShipment::query()
            ->where('store_id', $store->id)
            ->where('platform_order_id', $orderId)
            ->count());
        $this->assertSame('tiktok', $shipment->purchase_channel);
        $this->assertDatabaseHas('mp_shipments', [
            'store_id' => $store->id,
            'platform_order_id' => '586455555555555555',
            'purchase_channel' => 'tokopedia',
        ]);
        $this->assertDatabaseHas('marketplace_orders', [
            'store_id' => $store->id,
            'channel_order_id' => $orderId,
            'purchase_channel' => 'tiktok',
        ]);
        $this->assertDatabaseHas('marketplace_orders', [
            'store_id' => $store->id,
            'channel_order_id' => '586455555555555555',
            'purchase_channel' => 'tokopedia',
        ]);
        $marketplaceOrder = MarketplaceOrder::query()
            ->where('store_id', $store->id)
            ->where('channel_order_id', $orderId)
            ->firstOrFail();
        $this->assertSame(3, $marketplaceOrder->items()->count());
        $this->assertSame(3, (int) $marketplaceOrder->items()->sum('qty'));

        $combinedDashboard = $this->actingAs($owner)
            ->get(route('marketplace.dashboard.sales', [
                'store_id' => $store->id,
                'date_from' => '2026-10-12',
                'date_to' => '2026-10-12',
            ]))
            ->assertOk()
            ->assertSee($orderId)
            ->assertSee('Tracktop Jacket')
            ->assertSee('name="purchase_channel"', false)
            ->assertSee("document.querySelector('#sales-platform-filter')", false)
            ->assertSee('TikTok Shop')
            ->assertSee('Tokopedia')
            ->assertDontSee('platform:OFFL', false)
            ->assertDontSee('Kanal pesanan');
        $this->assertSame(2, $combinedDashboard->viewData('summary')['orders']);
        $this->assertSame(5, $combinedDashboard->viewData('summary')['qty']);

        $tiktokDashboard = $this->get(route('marketplace.dashboard.sales', [
            'store_id' => $store->id,
            'purchase_channel' => 'tiktok',
            'date_from' => '2026-10-12',
            'date_to' => '2026-10-12',
        ]))
            ->assertOk()
            ->assertSee('value="purchase:tiktok" selected', false);
        $this->assertSame(1, $tiktokDashboard->viewData('summary')['orders']);
        $this->assertSame(3, $tiktokDashboard->viewData('summary')['qty']);
        $this->assertTrue($tiktokDashboard->viewData('products')->contains('marketplace_name', 'Tracktop Jacket'));
        $this->assertFalse($tiktokDashboard->viewData('products')->contains('marketplace_name', 'Jogger Tokopedia'));

        $tokopediaDashboard = $this->get(route('marketplace.dashboard.sales', [
            'store_id' => $store->id,
            'purchase_channel' => 'tokopedia',
            'date_from' => '2026-10-12',
            'date_to' => '2026-10-12',
        ]))
            ->assertOk()
            ->assertSee('value="purchase:tokopedia" selected', false);
        $this->assertSame(1, $tokopediaDashboard->viewData('summary')['orders']);
        $this->assertSame(2, $tokopediaDashboard->viewData('summary')['qty']);
        $this->assertTrue($tokopediaDashboard->viewData('products')->contains('marketplace_name', 'Jogger Tokopedia'));
        $this->assertFalse($tokopediaDashboard->viewData('products')->contains('marketplace_name', 'Tracktop Jacket'));
    }

    public function test_unknown_purchase_channel_blocks_preview_commit(): void
    {
        $store = $this->makeTiktokStore('TTK-UNKNOWN');
        $path = $this->makeTiktokCsv('UNKNOWN-001', 'PKG-UNKNOWN', 'JY-UNKNOWN', 'Lazada');

        try {
            $service = app(MpImportService::class);
            $preview = $service->import('tiktok', $path, $store->id, 'unknown.csv', true);
            $this->assertCount(1, $preview['import_errors']);
            $this->assertSame(0, $preview['stats']['purchase_channels']['tiktok']['orders'] ?? 0);

            $this->expectException(\InvalidArgumentException::class);
            $service->import('tiktok', $path, $store->id, 'unknown.csv');
        } finally {
            unlink($path);
            $this->assertDatabaseCount('mp_shipments', 0);
        }
    }

    public function test_import_only_fills_gaps_in_api_owned_shipment_and_order(): void
    {
        $store = $this->makeTiktokStore('TTK-API-SAFE');
        $path = $this->makeTiktokCsv('API-SAFE-001', 'PKG-API-SAFE', 'JY-API-SAFE', 'TikTok');
        $service = app(MpImportService::class);

        try {
            $service->import('tiktok', $path, $store->id, 'initial.csv');
            $shipment = MpShipment::where('platform_order_id', 'API-SAFE-001')->firstOrFail();
            $order = MarketplaceOrder::where('channel_order_id', 'API-SAFE-001')->firstOrFail();
            $shipment->forceFill([
                'source_type' => 'api', 'purchase_channel' => null,
                'marketplace_status' => 'COMPLETED', 'grand_total' => 999000,
                'source_updated_at' => '2026-10-13 12:00:00',
            ])->save();
            $order->forceFill([
                'purchase_channel' => null, 'order_status' => 'COMPLETED',
                'raw_json' => ['source' => 'api'],
            ])->save();
            $shipment->items()->firstOrFail()->forceFill(['qty' => 9])->save();
            $order->items()->firstOrFail()->forceFill(['qty' => 9])->save();

            $service->import('tiktok', $path, $store->id, 'older.csv');
            $shipment->refresh();
            $order->refresh();
            $this->assertSame('COMPLETED', $shipment->marketplace_status);
            $this->assertSame(999000.0, (float) $shipment->grand_total);
            $this->assertSame(9, $shipment->items()->firstOrFail()->qty);
            $this->assertSame('tiktok', $shipment->purchase_channel);
            $this->assertSame('COMPLETED', $order->order_status);
            $this->assertSame(9, $order->items()->firstOrFail()->qty);
            $this->assertSame('tiktok', $order->purchase_channel);
            $this->assertSame(1, MpShipment::where('platform_order_id', 'API-SAFE-001')->count());
        } finally {
            unlink($path);
        }
    }

    public function test_existing_order_cannot_change_purchase_channel_by_reimport(): void
    {
        $store = $this->makeTiktokStore('TTK-CHANNEL-SAFE');
        $original = $this->makeTiktokCsv('CHANNEL-001', 'PKG-CHANNEL', 'JY-CHANNEL', 'Tokopedia');
        $conflict = $this->makeTiktokCsv('CHANNEL-001', 'PKG-CHANNEL', 'JY-CHANNEL', 'TikTok');
        $service = app(MpImportService::class);

        try {
            $service->import('tiktok', $original, $store->id, 'original.csv');
            $preview = $service->import('tiktok', $conflict, $store->id, 'conflict.csv', true);
            $this->assertNotEmpty($preview['import_errors']);
            $this->expectException(\InvalidArgumentException::class);
            $service->import('tiktok', $conflict, $store->id, 'conflict.csv');
        } finally {
            unlink($original);
            unlink($conflict);
            $this->assertDatabaseHas('mp_shipments', [
                'platform_order_id' => 'CHANNEL-001', 'purchase_channel' => 'tokopedia',
            ]);
            $this->assertDatabaseHas('marketplace_orders', [
                'channel_order_id' => 'CHANNEL-001', 'purchase_channel' => 'tokopedia',
            ]);
        }
    }

    public function test_older_import_cannot_rollback_newer_import_and_tracking_matches_missing_package(): void
    {
        $store = $this->makeTiktokStore('TTK-OLD-SAFE');
        $path = $this->makeTiktokCsv('OLD-SAFE-001', 'PKG-OLD-SAFE', 'JY-OLD-SAFE', 'Tokopedia');
        $service = app(MpImportService::class);

        try {
            $service->import('tiktok', $path, $store->id, 'initial.csv');
            $shipment = MpShipment::where('platform_order_id', 'OLD-SAFE-001')->firstOrFail();
            $shipment->forceFill([
                'platform_shipment_id' => null,
                'source_updated_at' => '2026-10-13 12:00:00',
                'marketplace_status' => 'COMPLETED',
                'grand_total' => 999000,
            ])->save();
            $shipment->items()->firstOrFail()->forceFill(['qty' => 9])->save();

            $preview = $service->import('tiktok', $path, $store->id, 'older.csv', true);
            $this->assertSame(1, $preview['stats']['existing_shipments']);
            $this->assertSame([], $preview['import_errors']);
            $service->import('tiktok', $path, $store->id, 'older.csv');

            $shipment->refresh();
            $this->assertSame('PKG-OLD-SAFE', $shipment->platform_shipment_id);
            $this->assertSame('COMPLETED', $shipment->marketplace_status);
            $this->assertSame(999000.0, (float) $shipment->grand_total);
            $this->assertSame(9, $shipment->items()->firstOrFail()->qty);
            $this->assertSame(1, MpShipment::where('platform_order_id', 'OLD-SAFE-001')->count());
        } finally {
            unlink($path);
        }
    }

    public function test_newer_file_refreshes_import_owned_snapshot(): void
    {
        $store = $this->makeTiktokStore('TTK-NEWER');
        $older = $this->makeTiktokCsv('NEWER-001', 'PKG-NEWER', 'JY-NEWER', 'TikTok');
        $newer = $this->makeTiktokCsv('NEWER-001', 'PKG-NEWER', 'JY-NEWER', 'TikTok', 2, '13/10/2026 11:00:00');
        $service = app(MpImportService::class);

        try {
            $service->import('tiktok', $older, $store->id, 'older.csv');
            $service->import('tiktok', $newer, $store->id, 'newer.csv');
            $shipment = MpShipment::where('platform_order_id', 'NEWER-001')->firstOrFail();
            $order = MarketplaceOrder::where('channel_order_id', 'NEWER-001')->firstOrFail();
            $this->assertSame(2, $shipment->items()->firstOrFail()->qty);
            $this->assertSame(2, $order->items()->firstOrFail()->qty);
            $this->assertSame(66000.0, (float) $shipment->grand_total);
            $this->assertSame(1, MpShipment::where('platform_order_id', 'NEWER-001')->count());
        } finally {
            unlink($older);
            unlink($newer);
        }
    }

    private function makeTiktokStore(string $code): Store
    {
        $channel = Channel::updateOrCreate(['code' => 'TTK'], [
            'name' => 'Tiktok', 'status' => 'active', 'is_active' => true,
        ]);

        return Store::create([
            'code' => $code, 'name' => $code, 'channel_id' => $channel->id,
            'status' => 'active', 'is_active' => true,
        ]);
    }

    private function makeTiktokCsv(
        string $orderId,
        string $packageId,
        string $tracking,
        string $purchaseChannel,
        int $qty = 1,
        string $shippedAt = '12/10/2026 11:00:00',
    ): string {
        $path = tempnam(sys_get_temp_dir(), 'ttk-stage-one-');
        $rows = [
            ['Order ID', 'Order Status', 'Seller SKU', 'Product Name', 'Quantity', 'SKU Unit Original Price', 'SKU Subtotal After Discount', 'Order Amount', 'Created Time', 'Shipped Time', 'Tracking ID', 'Package ID', 'Purchase Channel'],
            [$orderId, 'Dikirim', 'K5BLK', 'Jogger Hitam', $qty, 33000, 33000 * $qty, 33000 * $qty, '12/10/2026 10:30:00', $shippedAt, $tracking, $packageId, $purchaseChannel],
        ];
        $file = fopen($path, 'w');
        foreach ($rows as $row) {
            fputcsv($file, $row);
        }
        fclose($file);

        return $path;
    }

    public function test_empty_or_wrong_template_cannot_be_committed(): void
    {
        $channel = Channel::updateOrCreate(
            ['code' => 'TTK'],
            [
                'name' => 'Tiktok',
                'status' => 'active',
                'is_active' => true,
            ]
        );

        $store = Store::create([
            'code' => 'TTK-EMPTY-TEST',
            'name' => 'TikTok Empty Test',
            'channel_id' => $channel->id,
            'status' => 'active',
            'is_active' => true,
        ]);

        $owner = User::factory()->create([
            'role' => 'owner',
            'employee_code' => 'TTK-EMPTY-'.uniqid(),
        ]);

        $this->actingAs($owner)
            ->post(route('imports.marketplace.preview'), [
                'channel_id' => $channel->id,
                'store_id' => $store->id,
                'file' => UploadedFile::fake()->createWithContent(
                    'wrong-template.csv',
                    "Wrong Header\nThis is not a marketplace export\n"
                ),
            ])
            ->assertOk()
            ->assertViewIs('imports.marketplace.preview')
            ->assertSee('Ada error validasi');

        $batchId = session('mp_import_preview.import_batch_id');

        $this->actingAs($owner)
            ->post(route('imports.marketplace.commit'))
            ->assertRedirect(route('imports.marketplace.create'))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('marketplace_import_batches', [
            'id' => $batchId,
            'status' => 'failed',
            'error_count' => 1,
        ]);
        $this->assertDatabaseCount('mp_shipments', 0);
    }

    public function test_income_import_uses_store_channel_and_rejects_mismatch(): void
    {
        $shopee = Channel::updateOrCreate(
            ['code' => 'SHP'],
            ['name' => 'Shopee', 'status' => 'active', 'is_active' => true]
        );
        $tiktok = Channel::updateOrCreate(
            ['code' => 'TTK'],
            ['name' => 'Tiktok', 'status' => 'active', 'is_active' => true]
        );

        $store = Store::create([
            'code' => 'SHP-INCOME-TEST',
            'name' => 'Shopee Income Test',
            'channel_id' => $shopee->id,
            'status' => 'active',
            'is_active' => true,
        ]);

        $user = User::factory()->create([
            'role' => 'owner',
            'employee_code' => 'INCOME-TEST-'.uniqid(),
        ]);

        $this->actingAs($user)
            ->get(route('imports.marketplace_income.create'))
            ->assertOk()
            ->assertSee('Import Order')
            ->assertSee('Import Income')
            ->assertSee('data-income-channel="shopee"', false)
            ->assertSee('Channel income mengikuti channel toko secara otomatis.');

        // Toko Shopee tidak boleh diproses sebagai TikTok meski payload diubah manual.
        $this->actingAs($user)
            ->from(route('imports.marketplace_income.create'))
            ->post(route('imports.marketplace_income.preview'), [
                'channel' => 'tiktok',
                'store_id' => $store->id,
                'file' => UploadedFile::fake()->createWithContent('income.csv', "Order ID,Amount\nINCOME-001,1000\n"),
            ])
            ->assertRedirect(route('imports.marketplace_income.create'))
            ->assertSessionHas('error', 'Channel tidak sesuai dengan toko yang dipilih. Channel sudah ditentukan otomatis dari toko.');

        $this->assertDatabaseMissing('mp_incomes', ['store_id' => $store->id]);
        $this->assertDatabaseHas('channels', ['id' => $tiktok->id, 'code' => 'TTK']);
    }

    public function test_imported_order_maps_tiktok_sku_to_hpp_snapshot(): void
    {
        $store = $this->makeTiktokStore('TTK-HPP-SNAPSHOT');
        $item = Item::create([
            'code' => 'K5BLK',
            'name' => 'Jogger Hitam',
            'type' => 'finished',
            'hpp' => 33000,
            'active' => true,
        ]);
        SkuMapping::create([
            'marketplace_sku' => 'K5BLK',
            'channel_code' => 'tiktok',
            'item_id' => $item->id,
        ]);
        $path = $this->makeTiktokCsv('HPP-IMPORT-001', 'PKG-HPP-001', 'JY-HPP-001', 'TikTok');

        try {
            app(MpImportService::class)->import('tiktok', $path, $store->id, 'hpp-import.csv');
        } finally {
            unlink($path);
        }

        $order = MarketplaceOrder::where('channel_order_id', 'HPP-IMPORT-001')->firstOrFail();
        $orderItem = $order->items()->firstOrFail();

        $this->assertSame($item->id, $orderItem->internal_item_id);
        $this->assertSame('mapped', $orderItem->mapping_status);
        $this->assertSame('complete', $orderItem->cost_status);
        $this->assertSame('complete', $orderItem->profit_status);
        $this->assertSame('valid', $orderItem->data_status);
        $this->assertSame(33000.0, (float) $orderItem->hpp_snapshot);
        $this->assertSame(33000.0, (float) $orderItem->hpp_unit_snapshot);
        $this->assertSame(33000.0, (float) $orderItem->hpp_total_snapshot);
    }

    public function test_income_reconciliation_marks_aggregate_profit_incomplete_and_shows_badge(): void
    {
        $store = $this->makeTiktokStore('TTK-INCOME-QUALITY');
        $owner = User::factory()->create([
            'role' => 'owner',
            'employee_code' => 'INCOME-QUALITY-'.uniqid(),
        ]);
        $item = Item::create([
            'code' => 'K5BLK-INCOME',
            'name' => 'Jogger Hitam Income',
            'type' => 'finished',
            'hpp' => 33000,
            'active' => true,
        ]);
        SkuMapping::create([
            'marketplace_sku' => 'K5BLK',
            'channel_code' => 'TTK',
            'item_id' => $item->id,
        ]);
        $path = $this->makeTiktokCsv('INCOME-ORDER-001', 'PKG-INCOME-001', 'JY-INCOME-001', 'TikTok');

        try {
            app(MpImportService::class)->import('tiktok', $path, $store->id, 'income-order.csv');
        } finally {
            unlink($path);
        }

        $result = app(ImportedMarketplaceIncomeSyncService::class)->syncFromParsedRows(
            $store->id,
            ['INCOME-ORDER-001'],
            ['INCOME-ORDER-001' => [
                'platform_order_id' => 'INCOME-ORDER-001',
                'platform_fee_total' => 5000,
                'refund_total' => 0,
                'net_payout_actual' => 28000,
                'released_at' => '2026-10-13 09:00:00',
            ]],
            'income-batch-001',
            'income-tiktok.xlsx',
        );

        $order = MarketplaceOrder::where('channel_order_id', 'INCOME-ORDER-001')->firstOrFail();
        $settlement = $order->settlement()->firstOrFail();
        $this->assertSame(1, $result['settlements_created']);
        $this->assertSame(1, $result['profit_incomplete']);
        $this->assertSame('COMPLETED', $order->order_status);
        $this->assertSame('incomplete', $order->financial_data_status);
        $this->assertSame('settlement_data_incomplete', $order->financial_issue_reason);
        $this->assertSame(28000.0, (float) $settlement->final_income);
        $this->assertSame('incomplete', $settlement->data_status);
        $this->assertSame('marketplace_income_import', data_get($settlement->data_quality_flags, 'source'));
        $this->assertSame('income_breakdown_incomplete', data_get($settlement->data_quality_flags, 'reason'));

        $this->actingAs($owner)
            ->get(route('marketplace.dashboard.sales', [
                'store_id' => $store->id,
                'date_from' => '2026-10-12',
                'date_to' => '2026-10-12',
            ]))
            ->assertOk()
            ->assertSee('Profit belum lengkap');
    }

    public function test_income_reconciliation_preserves_authoritative_api_settlement(): void
    {
        $store = $this->makeTiktokStore('TTK-INCOME-API-SAFE');
        $order = MarketplaceOrder::create([
            'store_id' => $store->id,
            'channel_order_id' => 'API-INCOME-001',
            'external_order_id' => 'API-INCOME-001',
            'order_status' => 'COMPLETED',
            'status' => 'completed',
            'ordered_at' => '2026-10-12 10:00:00',
            'order_date' => '2026-10-12 10:00:00',
            'total_amount' => 100000,
            'subtotal_items' => 100000,
            'total_paid_customer' => 100000,
            'raw_json' => ['source' => 'api'],
        ]);
        MarketplaceOrderItem::create([
            'order_id' => $order->id,
            'marketplace_order_id' => $order->id,
            'item_name' => 'API Item',
            'item_sku' => 'API-SKU-001',
            'qty' => 1,
            'data_status' => 'valid',
            'mapping_status' => 'mapped',
            'cost_status' => 'complete',
            'profit_status' => 'complete',
            'hpp_snapshot' => 30000,
        ]);
        $settlement = MarketplaceOrderSettlement::create([
            'store_id' => $store->id,
            'order_id' => $order->id,
            'channel_order_id' => 'API-INCOME-001',
            'buyer_payment_amount' => 100000,
            'final_income' => 80000,
            'data_status' => 'complete',
            'data_quality_flags' => ['source' => 'escrow_detail'],
            'raw_json' => [
                'buyer_total_amount' => 100000,
                'commission_fee' => 5000,
                'service_fee' => 5000,
                'transaction_fee' => 1000,
                'affiliate_fee' => 0,
                'voucher_from_seller' => 0,
                'seller_coin_cash_back' => 0,
                'actual_shipping_fee' => 0,
                'shopee_shipping_rebate' => 0,
                'reverse_shipping_fee' => 0,
                'shipping_insurance_fee' => 0,
                'activity_fee' => 0,
                'drc_adjustable_refund' => 0,
                'escrow_tax' => 0,
                'final_income' => 80000,
            ],
        ]);

        $result = app(ImportedMarketplaceIncomeSyncService::class)->syncFromParsedRows(
            $store->id,
            ['API-INCOME-001'],
            ['API-INCOME-001' => [
                'net_payout_actual' => 79000,
                'platform_fee_total' => 21000,
                'refund_total' => 0,
                'released_at' => '2026-10-13 09:00:00',
            ]],
            'income-batch-api',
            'income-api-check.xlsx',
        );

        $settlement->refresh();
        $order->refresh();
        $this->assertSame(1, $result['api_settlements_preserved']);
        $this->assertSame(1, $result['income_mismatches']);
        $this->assertSame(80000.0, (float) $settlement->final_income);
        $this->assertSame('mismatch', data_get($settlement->data_quality_flags, 'income_import_reconciliation.status'));
        $this->assertSame(-1000.0, (float) data_get($settlement->data_quality_flags, 'income_import_reconciliation.difference'));
        $this->assertSame('ready', $order->financial_data_status);
    }

    public function test_tiktok_income_file_reconciles_into_order_settlement(): void
    {
        $store = $this->makeTiktokStore('TTK-INCOME-END-TO-END');
        $item = Item::create([
            'code' => 'K5BLK-E2E',
            'name' => 'Jogger Income E2E',
            'type' => 'finished',
            'hpp' => 33000,
            'active' => true,
        ]);
        SkuMapping::create([
            'marketplace_sku' => 'K5BLK',
            'channel_code' => 'tiktok',
            'item_id' => $item->id,
        ]);
        $orderPath = $this->makeTiktokCsv('INCOME-E2E-001', 'PKG-E2E-001', 'JY-E2E-001', 'TikTok');
        try {
            app(MpImportService::class)->import('tiktok', $orderPath, $store->id, 'order-e2e.csv');
        } finally {
            unlink($orderPath);
        }

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Detail Pesanan');
        $sheet->fromArray([
            ['Order/Adjustment ID', 'Type', 'Order Settled Time', 'Total Settlement Amount', 'Total Fees'],
            ['INCOME-E2E-001', 'Order', '13/10/2026 09:00:00', 28000, -5000],
        ]);
        $incomePath = tempnam(sys_get_temp_dir(), 'tiktok-income-e2e-');
        (new Xlsx($spreadsheet))->save($incomePath);
        $spreadsheet->disconnectWorksheets();

        try {
            $result = app(MpIncomeImportService::class)->import(
                'tiktok',
                $incomePath,
                'income-e2e.xlsx',
                $store->id,
            );
        } finally {
            unlink($incomePath);
        }

        $order = MarketplaceOrder::where('channel_order_id', 'INCOME-E2E-001')->firstOrFail();
        $this->assertSame(1, $result['stats']['orders_matched_marketplace']);
        $this->assertSame(1, $result['stats']['settlements_created']);
        $this->assertSame(1, $result['stats']['profit_incomplete']);
        $this->assertSame(28000.0, (float) $order->settlement()->value('final_income'));
        $this->assertSame('settlement_data_incomplete', $order->fresh()->financial_issue_reason);
    }

    public function test_income_saved_before_tiktok_order_is_reconciled_when_order_is_imported(): void
    {
        $store = $this->makeTiktokStore('TTK-INCOME-FIRST');

        MpIncome::create([
            'store_id' => $store->id,
            'channel' => 'tiktok',
            'platform_order_id' => 'INCOME-FIRST-001',
            'released_at' => '2026-10-13 09:00:00',
            'released_date' => '2026-10-13',
            'platform_fee_total' => 5000,
            'refund_total' => 0,
            'net_payout_actual' => 28000,
            'currency' => 'IDR',
            'source_file' => 'income-before-order.xlsx',
            'import_batch_id' => 'income-before-order-batch',
            'raw_payload' => ['income' => ['raw' => ['source' => 'test']]],
        ]);

        $orderPath = $this->makeTiktokCsv('INCOME-FIRST-001', 'PKG-INCOME-FIRST-001', 'JY-INCOME-FIRST-001', 'TikTok');
        try {
            app(MpImportService::class)->import('tiktok', $orderPath, $store->id, 'order-after-income.csv');
        } finally {
            unlink($orderPath);
        }

        $order = MarketplaceOrder::where('channel_order_id', 'INCOME-FIRST-001')->firstOrFail();
        $settlement = $order->settlement()->firstOrFail();
        $shipment = MpShipment::where('platform_order_id', 'INCOME-FIRST-001')->firstOrFail();

        $this->assertSame(28000.0, (float) $settlement->final_income);
        $this->assertSame('2026-10-13 09:00:00', $settlement->settlement_time?->format('Y-m-d H:i:s'));
        $this->assertSame(28000.0, (float) $shipment->net_payout_actual);
    }

    public function test_income_import_saves_advertising_spend_for_sales_dashboard(): void
    {
        $store = $this->makeTiktokStore('TTK-IMPORT-ADS');
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Detail Pesanan');
        $sheet->fromArray([
            ['Order/Adjustment ID', 'Type', 'Order Settled Time', 'Total Settlement Amount', 'Total Fees', 'Advertising Fee', 'Jumlah penyesuaian'],
            ['AD-ORDER-001', 'Order', '13/10/2026 09:00:00', 28000, -5000, -3000],
            ['ADJUSTMENT-ADS-001', 'Advertising', '13/10/2026 10:00:00', -4500, -4500, null, null],
            ['GMV-ADS-001', 'Pembayaran GMV untuk Iklan TikTok', '13/10/2026 11:00:00', null, null, null, -12500],
        ]);
        $path = tempnam(sys_get_temp_dir(), 'tiktok-income-ads-');
        (new Xlsx($spreadsheet))->save($path);
        $spreadsheet->disconnectWorksheets();

        try {
            $result = app(MpIncomeImportService::class)->import(
                'tiktok',
                $path,
                'income-with-ads.xlsx',
                $store->id,
            );
        } finally {
            unlink($path);
        }

        $this->assertSame(17000.0, (float) $result['stats']['ad_spend_total']);
        $this->assertDatabaseHas('marketplace_imported_ad_spends', [
            'store_id' => $store->id,
            'channel' => 'tiktok',
            'date' => '2026-10-13',
            'spend' => 17000,
        ]);

        $daily = app(AdsDashboardService::class)->getDailyMetrics(
            [$store->id],
            $store->id,
            '2026-10-13',
            '2026-10-13',
        );

        $this->assertSame(17000.0, (float) data_get($daily->get('2026-10-13'), 'spend'));

        $summary = app(MarketplaceAnalyticsSummaryService::class)->summary([
            'store_id' => $store->id,
            'date_from' => '2026-10-13',
            'date_to' => '2026-10-13',
            'compare_mode' => 'prev_period',
        ]);

        $this->assertSame(18870.0, (float) $summary['current']['ad_cost']);
        $this->assertSame(18870.0, (float) data_get($summary, 'daily.0.ad_cost'));
    }
}
