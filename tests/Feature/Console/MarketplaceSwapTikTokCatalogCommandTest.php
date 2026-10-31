<?php

namespace Tests\Feature\Console;

use App\Jobs\Marketplace\SyncMarketplaceProductsJob;
use App\Models\Channel;
use App\Models\MarketplaceProduct;
use App\Models\MarketplaceProductDaily;
use App\Models\MarketplaceProductModel;
use App\Models\Store;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MarketplaceSwapTikTokCatalogCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_dry_run_does_not_change_catalog_ownership(): void
    {
        [$gfid, $greatfull] = $this->createTikTokStores();
        $gfidProduct = $this->createProduct($gfid, 'GFID-PRODUCT');
        $greatfullProduct = $this->createProduct($greatfull, 'GREATFULL-PRODUCT');

        $this->artisan('marketplace:swap-tiktok-catalog', [
            'first_store_id' => $gfid->id,
            'second_store_id' => $greatfull->id,
        ])->expectsOutputToContain('DRY-RUN')
            ->assertExitCode(0);

        $this->assertSame($gfid->id, $gfidProduct->fresh()->store_id);
        $this->assertSame($greatfull->id, $greatfullProduct->fresh()->store_id);
    }

    public function test_confirmed_swap_moves_catalog_and_dependent_store_ids_then_queues_sync(): void
    {
        Storage::fake('local');
        Queue::fake();
        [$gfid, $greatfull] = $this->createTikTokStores();
        $gfidProduct = $this->createProduct($gfid, 'GFID-PRODUCT');
        $greatfullProduct = $this->createProduct($greatfull, 'GREATFULL-PRODUCT');
        $gfidModel = MarketplaceProductModel::create([
            'marketplace_product_id' => $gfidProduct->id,
            'model_id' => 'GFID-MODEL',
            'model_sku' => 'GFID-SKU',
            'stock' => 4,
        ]);
        $greatfullModel = MarketplaceProductModel::create([
            'marketplace_product_id' => $greatfullProduct->id,
            'model_id' => 'GREATFULL-MODEL',
            'model_sku' => 'GREATFULL-SKU',
            'stock' => 6,
        ]);
        $gfidDaily = MarketplaceProductDaily::create([
            'store_id' => $gfid->id,
            'marketplace_product_id' => $gfidProduct->id,
            'date' => today(),
            'stock_total' => 4,
        ]);
        $greatfullDaily = MarketplaceProductDaily::create([
            'store_id' => $greatfull->id,
            'marketplace_product_id' => $greatfullProduct->id,
            'date' => today(),
            'stock_total' => 6,
        ]);

        $this->artisan('marketplace:swap-tiktok-catalog', [
            'first_store_id' => $gfid->id,
            'second_store_id' => $greatfull->id,
            '--confirm' => true,
            '--queue-sync' => true,
        ])->assertExitCode(0);

        $this->assertSame($greatfull->id, $gfidProduct->fresh()->store_id);
        $this->assertSame($gfid->id, $greatfullProduct->fresh()->store_id);
        $this->assertSame($gfidProduct->id, $gfidModel->fresh()->marketplace_product_id);
        $this->assertSame($greatfullProduct->id, $greatfullModel->fresh()->marketplace_product_id);
        $this->assertSame($greatfull->id, $gfidDaily->fresh()->store_id);
        $this->assertSame($gfid->id, $greatfullDaily->fresh()->store_id);
        $backupFiles = Storage::disk('local')->allFiles('marketplace-catalog-repairs');
        $this->assertCount(1, $backupFiles);
        $this->assertStringStartsWith('marketplace-catalog-repairs/tiktok-catalog-swap-', $backupFiles[0]);

        Queue::assertPushed(SyncMarketplaceProductsJob::class, function (SyncMarketplaceProductsJob $job) use ($gfid): bool {
            return $job->store->is($gfid);
        });
        Queue::assertPushed(SyncMarketplaceProductsJob::class, function (SyncMarketplaceProductsJob $job) use ($greatfull): bool {
            return $job->store->is($greatfull);
        });
        Queue::assertPushed(SyncMarketplaceProductsJob::class, 2);
    }

    public function test_confirmed_swap_aborts_when_both_catalogs_contain_same_item_id(): void
    {
        [$gfid, $greatfull] = $this->createTikTokStores();
        $gfidProduct = $this->createProduct($gfid, 'SHARED-PRODUCT');
        $greatfullProduct = $this->createProduct($greatfull, 'SHARED-PRODUCT');

        $this->artisan('marketplace:swap-tiktok-catalog', [
            'first_store_id' => $gfid->id,
            'second_store_id' => $greatfull->id,
            '--confirm' => true,
        ])->expectsOutputToContain('item_id yang ada di kedua toko')
            ->assertExitCode(1);

        $this->assertSame($gfid->id, $gfidProduct->fresh()->store_id);
        $this->assertSame($greatfull->id, $greatfullProduct->fresh()->store_id);
    }

    /** @return array{Store, Store} */
    private function createTikTokStores(): array
    {
        $channel = Channel::create([
            'code' => 'tiktok',
            'name' => 'TikTok Shop',
            'status' => 'active',
        ]);

        $gfid = Store::create([
            'channel_id' => $channel->id,
            'code' => 'tiktok_gfid',
            'name' => 'GFID',
            'external_shop_id' => '7494806895821097374',
            'status' => 'active',
            'is_active' => true,
            'credentials' => ['access_token' => 'gfid-token'],
        ]);
        $greatfull = Store::create([
            'channel_id' => $channel->id,
            'code' => 'tiktok_greatfull',
            'name' => 'Greatfull.id',
            'external_shop_id' => '7494905651529353596',
            'status' => 'active',
            'is_active' => true,
            'credentials' => ['access_token' => 'greatfull-token'],
        ]);

        return [$gfid, $greatfull];
    }

    private function createProduct(Store $store, string $itemId): MarketplaceProduct
    {
        return MarketplaceProduct::create([
            'store_id' => $store->id,
            'item_id' => $itemId,
            'item_name' => "Produk {$itemId}",
            'item_status' => 'NORMAL',
            'has_model' => true,
        ]);
    }
}
