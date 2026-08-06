<?php

namespace App\Http\Controllers;

use App\Http\Requests\SyncOrdersRequest;
use App\Models\Channel;
use App\Models\ItemCostSnapshot;
use App\Models\MarketplaceAdCampaign;
use App\Models\MarketplaceAdGroup;
use App\Models\MarketplaceAdItemMap;
use App\Models\MarketplaceOrder;
use App\Models\Item;
use App\Models\MarketplaceProduct;
use App\Models\MarketplacePromotion;
use App\Models\MarketplaceOrderItem;
use App\Models\MarketplaceOrderSettlement;
use App\Models\MarketplaceSyncLog;
use App\Models\OrderFulfillment;
use App\Models\SkuMapping;
use App\Models\Store;
use App\Models\Warehouse;
use App\Services\Marketplace\MarketplaceApiGateway;
use App\Services\Marketplace\Ads\ItemHppResolver;
use App\Services\Channels\ChannelManager;
use App\Support\GmvMaxAnalytics;
use App\Services\MarketplaceIssueService;
use App\Services\MarketplaceSyncService;
use App\Services\OrderFulfillmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use Carbon\Carbon;

class MarketplaceSystemController extends Controller
{
    public function issueCenter(): \Illuminate\View\View
    {
        return view('marketplace.issues');
    }

    public function cacheMonitor(): \Illuminate\View\View
    {
        $disk = Storage::disk('local');
        $directory = 'shipping_labels';

        $totalFiles = 0;
        $totalSizeBytes = 0;
        $expiredFiles = 0;

        if ($disk->exists($directory)) {
            $files = $disk->allFiles($directory);
            $totalFiles = count($files);

            $fourDaysAgo = Carbon::now()->subDays(4);

            foreach ($files as $file) {
                if (!str_ends_with($file, '.pdf.gz')) continue;

                $totalSizeBytes += $disk->size($file);

                $filename = basename($file);
                $filenameWithoutExt = str_replace('.pdf.gz', '', $filename);
                $parts = explode('_', $filenameWithoutExt, 2);

                if (count($parts) === 2) {
                    $storeId = $parts[0];
                    $orderSn = $parts[1];

                    // Estimasi expired
                    $order = MarketplaceOrder::where('store_id', $storeId)
                        ->where('channel_order_id', $orderSn)
                        ->first();

                    if (!$order || ($order->order_status === 'COMPLETED' && $order->updated_at < $fourDaysAgo)) {
                        $expiredFiles++;
                    }
                }
            }
        }

        return view('marketplace.cache-monitor', compact('totalFiles', 'totalSizeBytes', 'expiredFiles'));
    }

    public function runCacheCleanup()
    {
        Artisan::call('marketplace:cleanup-labels');
        $output = Artisan::output();

        return back()->with('success', nl2br(e($output)));
    }

    public function devFreshOrders(): JsonResponse
    {
        if (app()->isProduction()) {
            return response()->json(['message' => 'Tidak diizinkan di production.'], 403);
        }

        \DB::transaction(function () {
            \DB::table('inventory_mutations')
                ->whereIn('source_type', ['order_fulfillment', 'order_fulfillment_substitution'])
                ->delete();
            \DB::table('order_fulfillment_lines')->delete();
            \DB::table('order_fulfillments')->delete();
            \DB::table('marketplace_order_items')->delete();
            \DB::table('marketplace_orders')->delete();
        });

        return response()->json(['message' => '✅ Semua marketplace orders berhasil dihapus. Siap sync ulang.']);
    }

    public function devSeedOrders(Request $request): JsonResponse
    {
        if (app()->isProduction()) {
            return response()->json(['message' => 'Tidak diizinkan di production.'], 403);
        }

        $count  = max(1, min(50, (int) ($request->input('count', 5))));
        $status = strtoupper($request->input('status', 'READY_TO_SHIP'));

        if (! in_array($status, ['READY_TO_SHIP', 'PROCESSED'])) {
            return response()->json(['message' => 'Status tidak valid.'], 422);
        }

        // Auto-create dummy channel + store jika belum ada (dev convenience)
        $channel = \DB::table('marketplace_channels')->first();
        if (! $channel) {
            $channelId = \DB::table('marketplace_channels')->insertGetId([
                'code'       => 'shopee',
                'name'       => 'Shopee',
                'is_active'  => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            $channelId = $channel->id;
        }

        $store = \App\Models\MarketplaceStore::first();
        if (! $store) {
            $store = \App\Models\MarketplaceStore::create([
                'channel_id'  => $channelId,
                'name'        => 'Toko Dev (Dummy)',
                'short_code'  => 'DEV',
                'is_active'   => true,
            ]);
        }

        // Ambil SKU mappings yang ada sebagai referensi item dummy
        $mappings = \App\Models\SkuMapping::with('item')->whereNotNull('item_id')->get();

        $buyers   = ['budi_santoso88','siti_rahayu22','agus_wijaya99','dewi_lestari7','rizky_pratama',
                     'nurul_hidayah','arif_firmansyah','maya_kusuma','fandi_ahmadi','linda_permata'];
        $carriers = ['JNE','J&T Express','SiCepat','AnterAja','SPX Express'];

        $created = 0;
        try {
        \DB::transaction(function () use ($count, $status, $store, $mappings, $buyers, $carriers, &$created) {
            for ($i = 0; $i < $count; $i++) {
                $channelId = now()->format('ymd') . strtoupper(\Str::random(8));

                $order = MarketplaceOrder::create([
                    'store_id'           => $store->id,
                    'channel_order_id'   => $channelId,
                    'external_order_id'  => $channelId,
                    'booking_sn'         => $channelId,
                    'order_date'         => now()->subMinutes(rand(5, 1440)),
                    'order_status'       => $status,
                    'status'             => $status,
                    'buyer_username'   => $buyers[array_rand($buyers)],
                    'payment_method'   => rand(0, 1) ? 'ShopeePay' : 'COD',
                    'shipping_carrier' => $carriers[array_rand($carriers)],
                    'currency'         => 'IDR',
                    'ordered_at'       => now()->subMinutes(rand(5, 1440)),
                    'synced_at'        => now(),
                    'total_amount'     => 0,
                ]);

                $itemCount   = rand(1, 3);
                $totalAmount = 0;

                // Pilih mapping acak (tanpa duplikat dalam 1 order)
                $pool = $mappings->isNotEmpty()
                    ? $mappings->shuffle()->take($itemCount)
                    : collect();

                // Isi sisa dengan item tanpa mapping jika kurang
                $slots = $pool->map(fn($m) => ['mapped' => true, 'mapping' => $m])
                    ->concat(collect(range(1, max(0, $itemCount - $pool->count())))
                        ->map(fn() => ['mapped' => false, 'mapping' => null]));

                foreach ($slots as $slot) {
                    $qty   = rand(1, 3);
                    $price = rand(15, 200) * 1000;

                    if ($slot['mapped'] && $slot['mapping']) {
                        // Item punya mapping → resolusi status
                        $m              = $slot['mapping'];
                        $itemName       = $m->item?->name ?? 'Produk ' . $m->marketplace_sku;
                        $sku            = $m->marketplace_sku;
                        $internalItem   = $m->item;
                        $hasHpp         = $internalItem && $internalItem->hpp > 0;
                        $mappingStatus  = 'mapped';
                        $costStatus     = $hasHpp ? 'complete'     : 'missing_hpp';
                        $profitStatus   = $hasHpp ? 'complete'     : 'incomplete';
                        $dataStatus     = $hasHpp ? 'valid'        : 'incomplete';
                        $issueReason    = $hasHpp ? null           : 'missing_hpp';
                        $internalItemId = $internalItem?->id;
                        $hppSnapshot    = $hasHpp ? $internalItem->hpp : null;
                    } else {
                        // Item tanpa mapping → muncul di /issues
                        $itemName       = 'Sample ' . strtoupper(\Str::random(5));
                        $sku            = 'SKU-' . strtoupper(\Str::random(6));
                        $mappingStatus  = 'mapping_not_found';
                        $costStatus     = 'missing_hpp';
                        $profitStatus   = 'incomplete';
                        $dataStatus     = 'incomplete';
                        $issueReason    = 'mapping_not_found';
                        $internalItemId = null;
                        $hppSnapshot    = null;
                    }

                    MarketplaceOrderItem::create([
                        'marketplace_order_id' => $order->id,
                        'order_id'             => $order->id,
                        'external_item_id'     => rand(100000, 999999),
                        'external_model_id'    => rand(1000000, 9999999),
                        'item_name'            => $itemName,
                        'item_sku'             => $sku,
                        'model_sku'            => $sku,
                        'qty'                  => $qty,
                        'price'                => $price,
                        'mapping_status'       => $mappingStatus,
                        'cost_status'          => $costStatus,
                        'profit_status'        => $profitStatus,
                        'data_status'          => $dataStatus,
                        'issue_reason'         => $issueReason,
                        'internal_item_id'     => $internalItemId,
                        'hpp_snapshot'         => $hppSnapshot,
                    ]);

                    $totalAmount += $qty * $price;
                }

                $order->update(['total_amount' => $totalAmount]);
                $created++;
            }
        });

        } catch (\Throwable $e) {
            return response()->json([
                'message' => '❌ Error: ' . $e->getMessage(),
                'file'    => $e->getFile() . ':' . $e->getLine(),
            ], 500);
        }

        return response()->json([
            'message' => "✅ {$created} dummy order ({$status}) berhasil dibuat.",
            'count'   => $created,
            'status'  => $status,
        ]);
    }

    public function devResetFulfillments(): JsonResponse
    {
        if (app()->isProduction()) {
            return response()->json(['message' => 'Tidak diizinkan di production.'], 403);
        }

        \DB::transaction(function () {
            $mutations = \DB::table('inventory_mutations')
                ->whereIn('source_type', ['order_fulfillment', 'order_fulfillment_substitution'])
                ->delete();
            \DB::table('order_fulfillment_lines')->delete();
            $fulfillments = \DB::table('order_fulfillments')->delete();
        });

        $orders = \DB::table('marketplace_orders')->count();

        return response()->json([
            'message' => "✅ Semua fulfillments dihapus. {$orders} order kembali ke tab Perlu Proses.",
            'orders'  => $orders,
        ]);
    }
}
