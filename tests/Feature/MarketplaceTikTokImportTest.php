<?php

namespace Tests\Feature;

use App\Models\Channel;
use App\Models\MarketplaceImportBatch;
use App\Models\MpShipment;
use App\Models\Store;
use App\Models\User;
use App\Services\Marketplace\Source\TiktokApiSourceAdapter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
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
            'employee_code' => 'TTK-SIDEBAR-' . uniqid(),
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

        $admin = User::factory()->create([
            'role' => 'admin',
            'employee_code' => 'ADMIN-IMPORT-' . uniqid(),
        ]);

        $this->actingAs($admin)
            ->get(route('marketplace.toko'))
            ->assertOk()
            ->assertSee('Import Order');

        $this->actingAs($admin)
            ->get(route('imports.marketplace.create', ['store_id' => $store->id]))
            ->assertOk()
            ->assertSee('Upload File')
            ->assertSee('data-selected-store-id="' . $store->id . '"', false)
            ->assertSee('value="' . $channel->id . '" selected', false);
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
            'employee_code' => 'TTK-DELETE-' . uniqid(),
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
            'employee_code' => 'ADMIN-CRUD-' . uniqid(),
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
            ->patchJson('/api/marketplace/stores/' . $storeId, [
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
            ->deleteJson('/api/marketplace/stores/' . $storeId)
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
            'employee_code' => 'TTK-DESCRIPTION-' . uniqid(),
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
            'employee_code' => 'TTK-EMPTY-' . uniqid(),
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
            'employee_code' => 'INCOME-TEST-' . uniqid(),
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
}
