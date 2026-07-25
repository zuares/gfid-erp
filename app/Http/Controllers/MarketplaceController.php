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
use App\Models\MarketplaceOrderItem;
use App\Models\MarketplaceOrderSettlement;
use App\Models\MarketplaceSyncLog;
use App\Models\OrderFulfillment;
use App\Models\SkuMapping;
use App\Models\Store;
use App\Models\Warehouse;
use App\Services\Channels\ChannelManager;
use App\Support\GmvMaxAnalytics;
use App\Services\MarketplaceIssueService;
use App\Services\MarketplaceSyncService;
use App\Services\OrderFulfillmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Artisan;
use Carbon\Carbon;

class MarketplaceController extends Controller
{
    public function __construct(
        protected MarketplaceSyncService $sync,
        protected ChannelManager $manager,
        protected OrderFulfillmentService $fulfillment,
        protected MarketplaceIssueService $issueService = new MarketplaceIssueService(),
    ) {}

    // ─── Pages ────────────────────────────────────────────────────────────────

    public function toko(): \Illuminate\View\View
    {
        return view('marketplace.toko');
    }

    public function orders(Request $request): \Illuminate\View\View
    {
        $today   = now()->toDateString();
        $weekAgo = now()->subDays(6)->toDateString();
        $filters = [
            'date_from' => $request->query('date_from', $weekAgo),
            'date_to'   => $request->query('date_to', $today),
        ];
        $isDummy = $request->boolean('dummy') && app()->environment(['local', 'testing']);

        return view('marketplace.orders', compact('filters', 'isDummy'));
    }

    public function webhookTests(Request $request): \Illuminate\View\View
    {
        $today   = now()->toDateString();
        $weekAgo = now()->subDays(6)->toDateString();
        $filters = [
            'date_from' => $request->query('date_from', $weekAgo),
            'date_to'   => $request->query('date_to', $today),
        ];
        $isDummy = $request->boolean('dummy') && app()->environment(['local', 'testing']);

        return view('marketplace.webhook-tests', compact('filters', 'isDummy'));
    }

    public function fulfillment(): \Illuminate\View\View
    {
        return view('marketplace.fulfillment');
    }

    public function fulfillmentProcess(int $id): \Illuminate\View\View
    {
        return view('marketplace.fulfillment-process', compact('id'));
    }

    public function picking(): \Illuminate\View\View
    {
        return view('marketplace.picking');
    }

    public function skuMapping(): \Illuminate\View\View
    {
        return view('marketplace.sku-mapping');
    }

    public function sync(): \Illuminate\View\View
    {
        return view('marketplace.sync');
    }

    public function settlement(): \Illuminate\View\View
    {
        return view('marketplace.settlement');
    }

    public function profit(): \Illuminate\View\View
    {
        return view('marketplace.profit');
    }

    public function analytics(Request $request): \Illuminate\View\View
    {
        $today   = now()->toDateString();
        $weekAgo = now()->subDays(29)->toDateString();
        $filters = [
            'date_from' => $request->query('date_from', $weekAgo),
            'date_to'   => $request->query('date_to', $today),
        ];
        return view('marketplace.analytics', compact('filters'));
    }

    public function ads(): \Illuminate\View\View
    {
        return view('marketplace.ads');
    }

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

    public function settings(): \Illuminate\View\View
    {
        $settings = [
            'marketplace_print_default_format' => \App\Models\SystemSetting::get('marketplace_print_default_format', 'THERMAL_AIR_WAYBILL'),
            'marketplace_print_branding'       => \App\Models\SystemSetting::get('marketplace_print_branding', '1'),
            'marketplace_footer_image'         => \App\Models\SystemSetting::get('marketplace_footer_image', ''),
            'marketplace_footer_greeting'      => \App\Models\SystemSetting::get('marketplace_footer_greeting', 'Terima kasih telah berbelanja!'),
            'marketplace_footer_alignment'     => \App\Models\SystemSetting::get('marketplace_footer_alignment', 'C'),
            'marketplace_footer_divider'       => \App\Models\SystemSetting::get('marketplace_footer_divider', '0'),
            'marketplace_sender_name'          => \App\Models\SystemSetting::get('marketplace_sender_name', ''),
            'marketplace_sender_phone'         => \App\Models\SystemSetting::get('marketplace_sender_phone', ''),
            'marketplace_social_accounts'      => \App\Models\SystemSetting::get('marketplace_social_accounts', '[]'),
            'marketplace_auto_sync'            => \App\Models\SystemSetting::get('marketplace_auto_sync', '0'),
            'marketplace_auto_push_stock'      => \App\Models\SystemSetting::get('marketplace_auto_push_stock', '0'),
            'marketplace_auto_process_orders'  => \App\Models\SystemSetting::get('marketplace_auto_process_orders', '0'),
            'marketplace_default_warehouse'    => \App\Models\SystemSetting::get('marketplace_default_warehouse', ''),
        ];
        
        $warehouses = \App\Models\Warehouse::orderBy('name')->get();

        // Ambil daftar file template greeting
        $greetingTemplates = [];
        if (\Illuminate\Support\Facades\Storage::disk('public')->exists('templates/greetings')) {
            $files = \Illuminate\Support\Facades\Storage::disk('public')->files('templates/greetings');
            foreach ($files as $file) {
                // Return just the basename, e.g., 'template_1.png'
                $greetingTemplates[] = pathinfo($file, PATHINFO_BASENAME);
            }
        }
        sort($greetingTemplates);

        // Ambil daftar file template footer
        $footerTemplates = [];
        if (\Illuminate\Support\Facades\Storage::disk('public')->exists('templates/footers')) {
            $files = \Illuminate\Support\Facades\Storage::disk('public')->files('templates/footers');
            foreach ($files as $file) {
                $footerTemplates[] = pathinfo($file, PATHINFO_BASENAME);
            }
        }
        sort($footerTemplates);

        return view('marketplace.settings', compact('settings', 'warehouses', 'greetingTemplates', 'footerTemplates'));
    }

    public function updateSettings(\Illuminate\Http\Request $request)
    {
        $data = $request->validate([
            'marketplace_print_default_format' => ['required', 'string'],
            'marketplace_print_branding'       => ['required', 'in:0,1'],
            'marketplace_footer_image'         => ['nullable', 'file', 'mimes:jpeg,png,jpg,pdf', 'max:2048'],
            'remove_footer_image'              => ['nullable', 'in:1'],
            'marketplace_footer_greeting'      => ['nullable', 'string', 'max:500'],
            'marketplace_footer_alignment'     => ['required', 'in:L,C,R'],
            'marketplace_footer_template'      => ['nullable', 'string', 'max:50'],
            'marketplace_footer_divider'       => ['nullable', 'in:0,1'],
            'marketplace_sender_name'          => ['nullable', 'string', 'max:255'],
            'marketplace_sender_phone'         => ['nullable', 'string', 'max:50'],
            'marketplace_auto_sync'            => ['required', 'in:0,1'],
            'marketplace_auto_push_stock'      => ['required', 'in:0,1'],
            'marketplace_print_greeting_card'  => ['nullable', 'in:0,1'],
            'marketplace_greeting_card_image'  => ['nullable', 'file', 'mimes:jpeg,png,jpg,pdf', 'max:2048'],
            'remove_greeting_card_image'       => ['nullable', 'in:1'],
            'marketplace_greeting_card_template' => ['nullable', 'string', 'max:50'],
            'marketplace_auto_process_orders'  => ['required', 'in:0,1'],
            'marketplace_default_warehouse'    => ['nullable', 'exists:warehouses,id'],
            'upload_template_1'                => ['nullable', 'file', 'mimes:jpeg,png,jpg,pdf', 'max:2048'],
            'upload_template_2'                => ['nullable', 'file', 'mimes:jpeg,png,jpg,pdf', 'max:2048'],
            'upload_template_3'                => ['nullable', 'file', 'mimes:jpeg,png,jpg,pdf', 'max:2048'],
            'add_greeting_template'            => ['nullable', 'file', 'mimes:jpeg,png,jpg,pdf', 'max:2048'],
            'add_footer_template'              => ['nullable', 'file', 'mimes:jpeg,png,jpg,pdf', 'max:2048'],
        ]);

        if ($request->has('remove_footer_image') && $request->input('remove_footer_image') == '1') {
            $oldPath = \App\Models\SystemSetting::get('marketplace_footer_image', '');
            if ($oldPath && \Illuminate\Support\Facades\Storage::disk('public')->exists($oldPath)) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($oldPath);
            }
            \App\Models\SystemSetting::set('marketplace_footer_image', '');
            unset($data['marketplace_footer_image']);
        } elseif ($request->hasFile('marketplace_footer_image')) {
            $path = $request->file('marketplace_footer_image')->store('marketplace/footer', 'public');
            \App\Models\SystemSetting::set('marketplace_footer_image', $path);
            unset($data['marketplace_footer_image']);
        }
        unset($data['remove_footer_image']);

        if ($request->has('remove_greeting_card_image') && $request->input('remove_greeting_card_image') == '1') {
            $oldPath = \App\Models\SystemSetting::get('marketplace_greeting_card_image', '');
            if ($oldPath && \Illuminate\Support\Facades\Storage::disk('public')->exists($oldPath)) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($oldPath);
            }
            \App\Models\SystemSetting::set('marketplace_greeting_card_image', '');
            unset($data['marketplace_greeting_card_image']);
        } elseif ($request->hasFile('marketplace_greeting_card_image')) {
            $path = $request->file('marketplace_greeting_card_image')->store('marketplace/greetings', 'public');
            \App\Models\SystemSetting::set('marketplace_greeting_card_image', $path);
            unset($data['marketplace_greeting_card_image']);
        }
        unset($data['remove_greeting_card_image']);

        for ($i = 1; $i <= 3; $i++) {
            $field = 'upload_template_' . $i;
            if ($request->hasFile($field)) {
                $file = $request->file($field);
                $ext = $file->getClientOriginalExtension() ?: 'png';
                $filename = 'template_' . $i . '.' . $ext;
                // Delete existing ones with different extensions just in case
                foreach (['png', 'jpg', 'jpeg', 'pdf'] as $e) {
                    $old = 'templates/greetings/template_' . $i . '.' . $e;
                    if (\Illuminate\Support\Facades\Storage::disk('public')->exists($old)) {
                        \Illuminate\Support\Facades\Storage::disk('public')->delete($old);
                    }
                }
                $file->storeAs('templates/greetings', $filename, 'public');
            }
            unset($data[$field]);
        }
        
        if ($request->hasFile('add_greeting_template')) {
            $file = $request->file('add_greeting_template');
            $ext = $file->getClientOriginalExtension() ?: 'png';
            $baseName = 'custom_' . time();
            $filename = $baseName . '.' . $ext;
            $file->storeAs('templates/greetings', $filename, 'public');
            unset($data['add_greeting_template']);
        }
        
        if ($request->hasFile('add_footer_template')) {
            $file = $request->file('add_footer_template');
            $ext = $file->getClientOriginalExtension() ?: 'png';
            $baseName = 'footer_' . time();
            $filename = $baseName . '.' . $ext;
            $file->storeAs('templates/footers', $filename, 'public');
            unset($data['add_footer_template']);
        }

        foreach ($data as $key => $value) {
            \App\Models\SystemSetting::set($key, $value);
        }
        
        $platforms = $request->input('social_platforms', []);
        $usernames = $request->input('social_usernames', []);
        
        $accounts = [];
        for ($i = 0; $i < count($platforms); $i++) {
            if (!empty(trim($usernames[$i] ?? ''))) {
                $accounts[] = [
                    'platform' => $platforms[$i],
                    'username' => trim($usernames[$i])
                ];
            }
        }
        
        \App\Models\SystemSetting::set('marketplace_social_accounts', json_encode($accounts));

        return redirect()->route('marketplace.settings')->with('success', 'Pengaturan berhasil disimpan');
    }
    
    public function deleteTemplate(\Illuminate\Http\Request $request)
    {
        $request->validate([
            'type' => 'required|in:greeting,footer',
            'filename' => 'required|string',
        ]);
        
        $type = $request->input('type');
        $filename = $request->input('filename');
        $folder = $type === 'greeting' ? 'templates/greetings' : 'templates/footers';
        $path = $folder . '/' . $filename;
        
        if (\Illuminate\Support\Facades\Storage::disk('public')->exists($path)) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($path);
        }
        
        return redirect()->route('marketplace.settings')->with('success', 'Template berhasil dihapus!');
    }

    public function previewSettingsPdf(\Illuminate\Http\Request $request)
    {
        $config = $request->except(['marketplace_footer_image', 'social_platforms', 'social_usernames']);
        
        $platforms = $request->input('social_platforms', []);
        $usernames = $request->input('social_usernames', []);
        
        $accounts = [];
        for ($i = 0; $i < count($platforms); $i++) {
            if (!empty(trim($usernames[$i] ?? ''))) {
                $accounts[] = [
                    'platform' => $platforms[$i],
                    'username' => trim($usernames[$i])
                ];
            }
        }
        $config['marketplace_social_accounts'] = json_encode($accounts);
        
        $tmpImgPath = null;
        if ($request->hasFile('marketplace_footer_image')) {
            $path = $request->file('marketplace_footer_image')->store('marketplace/footer/tmp', 'public');
            $config['marketplace_footer_image'] = $path;
            $tmpImgPath = storage_path('app/public/' . $path);
        } else {
            // Keep existing if any
            if ($request->input('remove_footer_image') == '1') {
                $config['marketplace_footer_image'] = '';
            } else {
                $config['marketplace_footer_image'] = \App\Models\SystemSetting::get('marketplace_footer_image', '');
            }
        }

        // Generate a fake Shopee AWB using FPDF
        $pdf = new \setasign\Fpdi\Fpdi();
        $pdf->AddPage('P', [100, 150]);
        $pdf->SetFont('Arial', 'B', 14);
        $pdf->Cell(0, 10, 'MARKETPLACE AWB MOCKUP', 0, 1, 'C');
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell(0, 5, 'No. Pesanan: 260709908SV5CN', 0, 1, 'C');
        
        $pdf->SetFillColor(0, 0, 0);
        for($i = 0; $i < 60; $i++) {
            $w = rand(1, 3);
            $pdf->Rect(15 + ($i * 1.2), 30, $w * 0.5, 15, 'F');
        }
        
        $pdf->SetY(50);
        $pdf->Cell(0, 10, '==================================================', 0, 1, 'C');
        $rawPdf = $pdf->Output('S');
        
        $overlayService = new \App\Services\ShippingLabelOverlayService();
        $finalPdf = $overlayService->overlayPdfContent($rawPdf, $config);
        
        if ($tmpImgPath && file_exists($tmpImgPath)) {
            @unlink($tmpImgPath);
        }
        
        return response($finalPdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="preview.pdf"'
        ]);
    }

    public function printSampleGreetingCard(\Illuminate\Http\Request $request)
    {
        $pdf = new \setasign\Fpdi\Fpdi();
        $pdf->AddPage('P', [100, 150]);
        
        $settings = \App\Models\SystemSetting::all()->pluck('setting_value', 'setting_key')->toArray();
        
        $greetingImageFull = null;
        $customGreetingImg = $settings['marketplace_greeting_card_image'] ?? '';
        
        if (!empty($customGreetingImg) && file_exists(storage_path('app/public/' . $customGreetingImg))) {
            $greetingImageFull = storage_path('app/public/' . $customGreetingImg);
        } else {
            $gTpl = $request->query('template', $settings['marketplace_greeting_card_template'] ?? 'template_1.png');
            if ($gTpl !== 'none') {
                if (in_array($gTpl, ['1', '2', '3'])) {
                    $gTpl = 'template_' . $gTpl . '.png';
                }
                $tplPath = storage_path('app/public/templates/greetings/' . $gTpl);
                if (file_exists($tplPath)) {
                    $greetingImageFull = $tplPath;
                }
            }
        }
        
        if ($greetingImageFull) {
            $ext = strtolower(pathinfo($greetingImageFull, PATHINFO_EXTENSION));
            if ($ext === 'pdf') {
                try {
                    $pdf->setSourceFile($greetingImageFull);
                    $gTplId = $pdf->importPage(1);
                    $pdf->useTemplate($gTplId, 0, 0, 100, 150);
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error("Failed to load PDF greeting card sample: " . $e->getMessage());
                }
            } else {
                $m = 4;
                $pdf->Image($greetingImageFull, $m, $m, 100 - ($m * 2), 150 - ($m * 2));
            }
        } else {
            $pdf->SetFont('Helvetica', 'B', 14);
            $pdf->SetXY(0, 70);
            $pdf->Cell(100, 10, 'Thank you for your order!', 0, 1, 'C');
        }
        
        return response($pdf->Output('S'), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="Sample_Greeting_Card.pdf"'
        ]);
    }

    // ─── API ──────────────────────────────────────────────────────────────────

    public function bootstrap(): JsonResponse
    {
        $channels = $this->sync->bootstrapChannels();

        return response()->json([
            'message'  => 'Channel default berhasil dibuat.',
            'channels' => $channels,
        ]);
    }

    public function channels(): JsonResponse
    {
        return response()->json(
            Channel::withCount('stores')->orderBy('name')->get()
        );
    }

    public function stores(): JsonResponse
    {
        $stores = Store::with(['channel', 'defaultWarehouse'])->latest()->get()->map(fn (Store $store) => [
            'id'                   => $store->id,
            'channel_id'           => $store->channel_id,
            'name'                 => $store->name,
            'external_shop_id'     => $store->external_shop_id,
            'region'               => $store->region,
            'status'               => $store->status,
            'is_active'            => (bool) $store->is_active,
            'connection_status'    => $store->connection_status,
            'token_expires_at'     => $store->token_expires_at?->toISOString(),
            'last_synced_at'       => $store->last_synced_at?->toISOString(),
            'meta'                 => $store->meta,
            'default_warehouse_id' => $store->default_warehouse_id,
            'default_warehouse'    => $store->defaultWarehouse ? [
                'id'   => $store->defaultWarehouse->id,
                'code' => $store->defaultWarehouse->code,
                'name' => $store->defaultWarehouse->name,
            ] : null,
            'channel' => $store->channel ? [
                'id'     => $store->channel->id,
                'code'   => $store->channel->code,
                'name'   => $store->channel->name,
                'status' => $store->channel->status,
            ] : null,
        ]);

        return response()->json($stores);
    }

    public function shopInfo(Store $store): JsonResponse
    {
        return response()->json(
            $this->manager->driver($store)->getShopInfo($store)
        );
    }

    /**
     * Cek connection_status toko SEBELUM meng-queue job latar belakang (force-sync-background,
     * sync-orders-background, sync-historical) — supaya tidak diam-diam gagal di worker tanpa
     * pernah memberi tahu user (job latar belakang tidak punya jalur feedback real-time ke UI).
     * Pola & pesan disalin persis dari pre-check yang sudah dipakai & terverifikasi di
     * syncOrders() (baris ~594-649) dan syncSettlementsBackground(). Return null kalau toko
     * siap disync, atau JsonResponse redirect (422/401) kalau tidak.
     */
    private function ensureStoreReadyForBackgroundSync(Store $store): ?JsonResponse
    {
        $status = $store->connection_status;

        if ($status === 'TOKEN_EXPIRED') {
            try {
                if ($store->channel->code === 'shopee') {
                    /** @var \App\Services\Channels\Shopee\ShopeeChannel $shopee */
                    $shopee = app(\App\Services\Channels\Shopee\ShopeeChannel::class);
                    $shopee->refreshToken($store);
                    $store->refresh();
                    $status = $store->connection_status;
                } elseif ($store->channel->code === 'tiktok') {
                    /** @var \App\Services\Channels\TikTokShop\TikTokShopChannel $tiktok */
                    $tiktok = app(\App\Services\Channels\TikTokShop\TikTokShopChannel::class);
                    $tiktok->refreshToken($store);
                    $store->refresh();
                    $status = $store->connection_status;
                }
            } catch (\Throwable $e) {
                $status = 'AUTH_REQUIRED';
                \Illuminate\Support\Facades\Log::warning('Token refresh failed before queueing background sync', [
                    'store_id'   => $store->id,
                    'store_name' => $store->name,
                    'error'      => $e->getMessage(),
                ]);
            }
        }

        if ($status === 'NOT_CONNECTED') {
            $urlSegment = $store->channel->code === 'TKT' ? 'tiktok' : 'shopee';
            return response()->json([
                'success' => false,
                'code'    => 'STORE_NOT_CONNECTED',
                'message' => "Toko {$store->name} belum terhubung ke {$store->channel->name}.",
                'action'  => [
                    'type'  => 'redirect',
                    'label' => 'Hubungkan ' . $store->channel->name,
                    'url'   => url('/marketplace/' . $urlSegment . '/connect'),
                ],
            ], 422);
        }

        if ($status !== 'CONNECTED') {
            $urlSegment = $store->channel->code === 'TKT' ? 'tiktok' : 'shopee';
            return response()->json([
                'success' => false,
                'code'    => 'SHOPEE_AUTH_REQUIRED',
                'message' => "Koneksi {$store->channel->name} untuk toko {$store->name} sudah tidak aktif. Login ulang diperlukan sebelum sinkronisasi bisa berjalan.",
                'action'  => [
                    'type'  => 'redirect',
                    'label' => 'Login Ulang ' . $store->channel->name,
                    'url'   => url('/marketplace/' . $urlSegment . '/connect'),
                ],
            ], 401);
        }

        return null;
    }

    public function syncHistorical(Request $request, Store $store): JsonResponse
    {
        if ($resp = $this->ensureStoreReadyForBackgroundSync($store)) {
            return $resp;
        }

        $year = $request->input('year', 2022);

        // Lempar pekerjaan berat ini ke antrean (Queue) di latar belakang
        \Illuminate\Support\Facades\Artisan::queue('shopee:sync-historical-orders', [
            'year' => $year,
            '--store' => $store->id
        ]);

        \Illuminate\Support\Facades\Artisan::queue('shopee:sync-historical-returns', [
            'year' => $year,
            '--store' => $store->id
        ]);

        return response()->json([
            'status' => 'success',
            'message' => "Proses 'Mesin Waktu' menuju tahun {$year} untuk toko {$store->name} sedang berjalan di latar belakang!"
        ]);
    }

    public function forceSyncBackground(Request $request, Store $store): JsonResponse
    {
        if ($resp = $this->ensureStoreReadyForBackgroundSync($store)) {
            return $resp;
        }

        // Jalankan sinkronisasi secara asinkron di queue
        \Illuminate\Support\Facades\Artisan::queue('marketplace:sync-orders', ['--store' => $store->id]);
        \App\Jobs\SyncMarketplaceReturns::dispatch($store, null, null, true);

        $store->update(['last_synced_at' => now()]);

        return response()->json([
            'message' => 'Perintah tarik data pesanan (3 hari terakhir, default command) dan retur terbaru telah dikirim ke latar belakang.',
            'status' => 'queued'
        ]);
    }

    /**
     * Sync pesanan rentang panjang (mis. 30/60 hari) di latar belakang via queue.
     * Menghindari timeout browser untuk penarikan data berat.
     */
    public function syncOrdersBackground(Request $request, Store $store): JsonResponse
    {
        if ($resp = $this->ensureStoreReadyForBackgroundSync($store)) {
            return $resp;
        }

        $days = max(1, min(60, (int) $request->input('days', 30)));

        // Set state awal supaya UI langsung menampilkan progres "antre" sebelum worker jalan.
        \Illuminate\Support\Facades\Cache::put("marketplace:sync_progress:{$store->id}", [
            'percent' => 0,
            'label'   => 'Menunggu antrean worker…',
            'status'  => 'queued',
            'store'   => $store->name,
            'ts'      => now()->timestamp,
        ], 1800);

        \Illuminate\Support\Facades\Artisan::queue('marketplace:sync-orders', [
            '--store' => $store->id,
            '--days'  => $days,
        ]);

        $store->update(['last_synced_at' => now()]);

        return response()->json([
            'message' => "Sync pesanan {$days} hari untuk toko ini dikirim ke latar belakang. Data akan masuk bertahap.",
            'status'  => 'queued',
            'days'    => $days,
        ]);
    }

    /**
     * Progres sinkronisasi latar belakang untuk satu toko (dibaca dari Cache).
     * Dipakai UI untuk menampilkan persentase pada dropdown "Latar Belakang".
     */
    public function syncOrdersProgress(Store $store): JsonResponse
    {
        $progress = \Illuminate\Support\Facades\Cache::get("marketplace:sync_progress:{$store->id}");

        if (! $progress) {
            return response()->json(['status' => 'idle', 'percent' => null]);
        }

        return response()->json($progress);
    }

    public function syncOrders(SyncOrdersRequest $request, Store $store): JsonResponse
    {
        // Beri waktu lebih untuk penarikan data berat (opsi 30/60 hari dipecah
        // menjadi beberapa jendela 14 hari, jadi butuh waktu lebih panjang).
        set_time_limit(300);

        $lock = \Illuminate\Support\Facades\Cache::lock("sync_store_{$store->id}", 240);

        if (!$lock->get()) {
            return response()->json(['message' => 'Sync sedang berjalan untuk toko ini. Mohon tunggu.'], 429);
        }

        $status = $store->connection_status;

        if ($status === 'TOKEN_EXPIRED') {
            try {
                if ($store->channel->code === 'shopee') {
                    /** @var \App\Services\Channels\Shopee\ShopeeChannel $shopee */
                    $shopee = app(\App\Services\Channels\Shopee\ShopeeChannel::class);
                    $shopee->refreshToken($store);
                    $store->refresh();
                    $status = $store->connection_status;
                } else if ($store->channel->code === 'tiktok') {
                    /** @var \App\Services\Channels\TikTokShop\TikTokShopChannel $tiktok */
                    $tiktok = app(\App\Services\Channels\TikTokShop\TikTokShopChannel::class);
                    $tiktok->refreshToken($store);
                    $store->refresh();
                    $status = $store->connection_status;
                }
            } catch (\Throwable $e) {
                $status = 'AUTH_REQUIRED';
                \Illuminate\Support\Facades\Log::warning('Token refresh failed during sync', [
                    'store_id' => $store->id,
                    'store_name' => $store->name,
                    'error' => $e->getMessage()
                ]);
            }
        }

        if ($status === 'NOT_CONNECTED') {
            $lock->release();
            $urlSegment = $store->channel->code === 'TKT' ? 'tiktok' : 'shopee';
            return response()->json([
                'success' => false,
                'code'    => 'STORE_NOT_CONNECTED',
                'message' => "Toko {$store->name} belum terhubung ke {$store->channel->name}.",
                'action'  => [
                    'type'  => 'redirect',
                    'label' => 'Hubungkan ' . $store->channel->name,
                    'url'   => url('/marketplace/' . $urlSegment . '/connect')
                ]
            ], 422);
        }

        if ($status !== 'CONNECTED') {
            $lock->release();
            $urlSegment = $store->channel->code === 'TKT' ? 'tiktok' : 'shopee';
            return response()->json([
                'success' => false,
                'code'    => 'SHOPEE_AUTH_REQUIRED',
                'message' => "Koneksi {$store->channel->name} untuk toko {$store->name} sudah tidak aktif.",
                'action'  => [
                    'type'  => 'redirect',
                    'label' => 'Login Ulang ' . $store->channel->name,
                    'url'   => url('/marketplace/' . $urlSegment . '/connect')
                ]
            ], 401);
        }

        try {
            // Sinkronisasi order reguler
            $result = $this->sync->syncOrders(
                $store,
                (int) $request->time_from,
                (int) $request->time_to,
                (int) ($request->page_size ?? 50),
                (bool) $request->dry_run
            );
            
            // Sinkronisasi pesanan kilat (bookings) agar statusnya ter-update di UI
            if (class_exists(\App\Jobs\SyncMarketplaceBookings::class)) {
                dispatch_sync(new \App\Jobs\SyncMarketplaceBookings($store, null, null, false));
            }
            
        } catch (\RuntimeException $e) {
            $lock->release();
            
            $msg = $e->getMessage();
            if (str_contains(strtolower($msg), 'access_token') || str_contains(strtolower($msg), 'auth')) {
                return response()->json([
                    'success' => false,
                    'code'    => 'SHOPEE_AUTH_REQUIRED',
                    'message' => "Koneksi {$store->channel->name} untuk toko {$store->name} sudah tidak aktif.",
                    'action'  => [
                        'type'  => 'redirect',
                        'label' => 'Login Ulang ' . $store->channel->name,
                        'url'   => url('/marketplace/' . $store->channel->code . '/connect')
                    ]
                ], 401);
            }
            
            return response()->json([
                'success' => false,
                'code'    => 'VALIDATION_ERROR',
                'message' => $msg
            ], 422);
        } catch (\Throwable $e) {
            $lock->release();
            
            \Illuminate\Support\Facades\Log::error('Marketplace sync internal error', [
                'store_id' => $store->id,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'code'    => 'CONNECTION_ERROR',
                'message' => 'Sinkronisasi pesanan belum berhasil. Koneksi ke ' . $store->channel->name . ' sedang bermasalah.'
            ], 502);
        }

        $lock->release();
        return response()->json($result);
    }

    public function localOrders(): JsonResponse
    {
        if (request()->boolean('dummy') && app()->environment(['local', 'testing'])) {
            return response()->json(app(\App\Support\DummyMarketplaceOrderProvider::class)->orders());
        }

        // Sertakan scan_log jika kolom sudah ada (setelah migration)
        $hasScanLog = in_array(
            'scan_log',
            \Illuminate\Support\Facades\Schema::getColumnListing('order_fulfillments')
        );
        $fulfillmentSelect = $hasScanLog
            ? 'id,marketplace_order_id,status,scan_log'
            : 'id,marketplace_order_id,status';

        $with = [
            'store.channel',
            'items',
            'items.internalItem' => fn ($q) => $q->select('id', 'code', 'item_category_id')->with('category:id,code,name'),
            'fulfillment:' . $fulfillmentSelect,
            'fulfillment.lines',
            'fulfillment.lines.item:id,code,name',
            'fulfillment.lines.splitChildren',
            'fulfillment.lines.splitChildren.item:id,code,name',
        ];

        // order_sn milik Pesanan Kilat (punya booking) — untuk penanda is_kilat, sekaligus
        // supaya order kilat tetap ikut ditarik walau lebih lama dari 200 order terbaru.
        // Guard hasTable agar Orders tidak error bila migration booking belum jalan di server.
        // Cocokkan hanya via order_sn (unik global) — JANGAN pakai store_id, karena record
        // booking & order bisa tersimpan di store_id lokal berbeda sehingga match gagal.
        $kilatMap = [];      // key: order_sn => booking_sn
        $kilatOrderSns = [];
        if (\Illuminate\Support\Facades\Schema::hasTable('marketplace_bookings')) {
            $kilatMap = \App\Models\MarketplaceBooking::whereNotNull('order_sn')->where('order_sn', '!=', '')
                ->pluck('booking_sn', 'order_sn')->all();
            $kilatOrderSns = array_keys($kilatMap);
        }

        $orders = MarketplaceOrder::with($with)->latest('ordered_at')->limit(200)->get();

        // Sertakan order kilat yang TIDAK masuk 200 terbaru (mis. booking MATCHED yang lama).
        // Cocokkan channel_order_id ATAU external_order_id (sebagian toko simpan order_sn di sana).
        if (! empty($kilatOrderSns)) {
            $extra = MarketplaceOrder::with($with)
                ->where(function ($q) use ($kilatOrderSns) {
                    $q->whereIn('channel_order_id', $kilatOrderSns)
                      ->orWhereIn('external_order_id', $kilatOrderSns);
                })
                ->whereNotIn('id', $orders->pluck('id')->all())
                ->latest('ordered_at')
                ->limit(300)
                ->get();
            if ($extra->isNotEmpty()) {
                $orders = $orders->concat($extra)->sortByDesc('ordered_at')->values();
            }
        }

        $mapped = $orders->map(function ($o) use ($hasScanLog, $kilatMap) {
            $arr = $o->toArray();
            // Kilat = punya booking DAN BUKAN Instan (same-day). Keduanya berbeda:
            // Instan dideteksi dari nama kurir, ditangani di tab "Instan" tersendiri.
            $carrier   = strtolower((string) $o->shipping_carrier);
            $isInstant = str_contains($carrier, 'instant') || str_contains($carrier, 'same day') || str_contains($carrier, 'sameday');
            $bookingSn = $o->booking_sn ?? ($kilatMap[$o->channel_order_id] ?? ($kilatMap[$o->external_order_id] ?? null));
            $arr['is_kilat']               = (!empty($bookingSn)) && ! $isInstant;
            $arr['booking_sn']             = $bookingSn ?: null;
            $arr['fulfillment_id']         = $o->fulfillment?->id;
            $arr['fulfillment_status']     = $o->fulfillment?->status; // null|draft|pending_review|confirmed|cancelled
            $arr['print_count']            = $o->print_count ?? 0;
            $arr['printed_at']             = $o->printed_at;
            $arr['has_unresolved_lines']   = $o->fulfillment
                ? $o->fulfillment->lines->whereNull('item_id')->isNotEmpty()
                : false;
            $arr['needs_shipping_arrangement'] = $o->needs_shipping_arrangement;
            // Issues: ada item dengan data_status != 'valid' (sama seperti halaman /marketplace/issues)
            $arr['has_data_issues'] = $o->items->contains(
                fn ($item) => ($item->data_status ?? 'incomplete') !== 'valid'
            );

            // Logistics status dari raw_json (untuk order Shopee)
            $arr['logistics_status'] = $o->raw_json['package_list'][0]['logistics_status'] ?? null;

            // Item terscan untuk tab Sedang Packing & Sudah Proses
            // Priority: scan_log (raw scan baru) → picked_at lines (lama/manual) → null
            $arr['fulfillment_scan_log'] = null;
            if ($o->fulfillment) {
                $raw = $hasScanLog ? ($o->fulfillment->scan_log ?? null) : null;

                if ($raw) {
                    // scan_log tersedia (hasil packOrder() baru) — filter item tanpa code
                    $decoded = json_decode($raw, true) ?? [];
                    $arr['fulfillment_scan_log'] = array_values(
                        array_filter($decoded, fn ($s) => ! empty($s['code']) && ($s['qty'] ?? 0) > 0)
                    );
                } else {
                    // Fallback: lines yang sudah di-scan (picked_at not null, qty_fulfilled > 0)
                    // Untuk status packed, juga ambil semua lines dengan qty_fulfilled > 0 (bisa 0 jika tidak dipacking)
                    $status = $o->fulfillment->status;
                    $usePickedAt = in_array($status, ['picking', 'pending_review']);
                    $usePacked   = in_array($status, ['packed', 'confirmed']);

                    $scannedLines = $o->fulfillment->lines
                        ->where('is_split_parent', false)
                        ->filter(function ($l) use ($usePickedAt, $usePacked) {
                            if (! $l->item_id) return false;
                            if ($usePacked)   return ($l->qty_fulfilled ?? 0) > 0;
                            if ($usePickedAt) return $l->picked_at !== null && ($l->qty_fulfilled ?? 0) > 0;
                            return false;
                        });

                    if ($scannedLines->isNotEmpty()) {
                        // Group by item_id, sum qty
                        $grouped = $scannedLines->groupBy('item_id')->map(fn ($g, $itemId) => [
                            'item_id' => (int) $itemId,
                            'qty'     => $g->sum('qty_fulfilled'),
                            'code'    => $g->first()->item?->code ?? null,
                            'name'    => $g->first()->item?->name ?? null,
                        ]);
                        $arr['fulfillment_scan_log'] = $grouped->values()->all();
                    }
                }
            }

            // Item Resolve: fulfillment lines untuk packing orders (picking/packed/pending_review)
            $packingStatuses = ['picking', 'packed', 'pending_review', 'draft'];
            if ($o->fulfillment && in_array($o->fulfillment->status, $packingStatuses)) {
                $lines = $o->fulfillment->lines->where('is_split_parent', false);
                $arr['fulfillment_resolve_lines'] = $lines
                    ->filter(fn ($l) => $l->item_id !== null)
                    ->map(fn ($l) => [
                        'qty_ordered'   => $l->qty_ordered   ?? 1,
                        'qty_fulfilled' => $l->qty_fulfilled ?? 0,
                        'code'          => $l->item?->code ?? null,
                        'name'          => $l->item?->name ?? null,
                        'marketplace_sku' => $l->marketplace_sku ?? null,
                        'substituted'   => (bool) $l->substituted,
                        'split_parent_id' => $l->split_parent_id,
                    ])
                    ->values()->all();
                // Summary info untuk card display
                $totalOrdered   = $lines->sum('qty_ordered');
                $totalFulfilled = $lines->sum('qty_fulfilled');
                $arr['fulfillment_packing_summary'] = [
                    'total_ordered'   => $totalOrdered,
                    'total_fulfilled' => $totalFulfilled,
                    'has_shortage'    => $lines->filter(fn ($l) => $l->item_id)
                                               ->some(fn ($l) => ($l->qty_fulfilled ?? 0) < ($l->qty_ordered ?? 1)),
                ];
            } else {
                $arr['fulfillment_resolve_lines']    = [];
                $arr['fulfillment_packing_summary']  = null;
            }

            // Untuk tab Sudah Proses: include fulfilled lines dengan info lengkap
            if ($o->fulfillment?->status === 'confirmed') {
                $arr['fulfillment_lines'] = $o->fulfillment->lines->map(function ($l) {
                    return [
                        'id'                    => $l->id,
                        'marketplace_sku'       => $l->marketplace_sku,
                        'marketplace_item_name' => $l->marketplace_item_name,
                        'qty_ordered'           => $l->qty_ordered,
                        'qty_fulfilled'         => $l->qty_fulfilled,
                        'substituted'           => (bool) $l->substituted,
                        'is_split_parent'       => (bool) $l->is_split_parent,
                        'split_parent_id'       => $l->split_parent_id,
                        'notes'                 => $l->notes,
                        'item' => $l->item ? [
                            'id'   => $l->item->id,
                            'code' => $l->item->code,
                            'name' => $l->item->name,
                        ] : null,
                        'split_children' => $l->is_split_parent
                            ? $l->splitChildren->map(fn ($c) => [
                                'id'          => $c->id,
                                'qty_fulfilled'=> $c->qty_fulfilled,
                                'substituted' => (bool) $c->substituted,
                                'item'        => $c->item ? [
                                    'id'   => $c->item->id,
                                    'code' => $c->item->code,
                                    'name' => $c->item->name,
                                ] : null,
                            ])->values()->all()
                            : [],
                    ];
                })->values()->all();
            } else {
                $arr['fulfillment_lines'] = [];
            }

            return $arr;
        });

        // ── Pesanan Kilat murni (booking READY_TO_SHIP tanpa order lokal) ──────
        // Booking baru berstatus READY_TO_SHIP sering belum MATCHED ke order
        // (order_sn baru diberikan Shopee setelah MATCHED). Supaya tetap tampil
        // di halaman Orders (sub-tab ⚡ Pengiriman Kilat), sertakan sebagai baris
        // pseudo-order dengan flag is_booking = true.
        if (\Illuminate\Support\Facades\Schema::hasTable('marketplace_bookings')) {
            $knownSns = $orders->pluck('channel_order_id')
                ->merge($orders->pluck('external_order_id'))
                ->filter()->flip();

            $pureBookings = \App\Models\MarketplaceBooking::with('store.channel')
                ->whereIn('booking_status', ['PENDING', 'READY_TO_SHIP', 'PROCESSED'])
                ->get()
                ->reject(fn ($b) => $b->order_sn && $knownSns->has($b->order_sn));

            $allSkus = [];
            foreach ($pureBookings as $b) {
                if (is_array($b->items)) {
                    foreach ($b->items as $i) {
                        if ($sku = ($i['model_sku'] ?? $i['item_sku'] ?? null)) {
                            $allSkus[] = $sku;
                        }
                    }
                }
            }
            $mappedItems = \App\Models\Item::whereIn('code', array_unique($allSkus))
                ->select('id', 'code', 'item_category_id', 'name')
                ->with('category:id,code,name')
                ->get()
                ->keyBy('code');

            $bookingRows = $pureBookings->map(function ($b) use ($mappedItems) {
                $items = collect(is_array($b->items) ? $b->items : [])->map(function ($i) use ($mappedItems) {
                    // Tampilkan SKU marketplace (bukan judul produk); judul hanya fallback.
                    $sku = $i['model_sku'] ?? $i['item_sku'] ?? null;
                    $title = trim(($i['item_name'] ?? '') . (! empty($i['model_name']) ? ' - ' . $i['model_name'] : '')) ?: null;
                    $mapped = $sku ? $mappedItems->get($sku) : null;
                    return [
                        'qty'           => $i['quantity'] ?? $i['model_quantity_purchased'] ?? 1,
                        'variant_name'  => $sku ?: $title,
                        'model_sku'     => $sku,
                        'item_sku'      => $i['item_sku'] ?? null,
                        'internal_item' => $mapped ? [
                            'id'   => $mapped->id,
                            'code' => $mapped->code,
                            'name' => $mapped->name,
                            'category' => $mapped->category ? [
                                'id'   => $mapped->category->id,
                                'code' => $mapped->category->code,
                                'name' => $mapped->category->name,
                            ] : null,
                        ] : null,
                    ];
                })->values()->all();

                return [
                    'id'                          => -$b->id, // negatif = baris booking (bukan order)
                    'store_id'                    => $b->store_id,
                    'store'                       => $b->store ? [
                        'id'      => $b->store->id,
                        'name'    => $b->store->name,
                        'channel' => $b->store->channel ? [
                            'code' => strtolower((string) $b->store->channel->code),
                            'name' => $b->store->channel->name,
                        ] : null,
                    ] : null,
                    'channel_order_id'            => $b->order_sn ?: $b->booking_sn,
                    'external_order_id'           => $b->order_sn,
                    'booking_sn'                  => $b->booking_sn,
                    // PROCESSED → tab "Sedang Dikemas"; PENDING/READY_TO_SHIP → "Perlu Dikirim" (sub-tab ⚡).
                    'order_status'                => $b->booking_status === 'PROCESSED' ? 'PROCESSED' : 'READY_TO_SHIP',
                    'ordered_at'                  => $b->create_time
                        ? \Carbon\Carbon::createFromTimestamp($b->create_time)->toIso8601String()
                        : optional($b->created_at)->toIso8601String(),
                    'items'                       => $items,
                    'shipping_carrier'            => $b->shipping_carrier,
                    'shipping_awb_no'             => $b->tracking_number,
                    'is_kilat'                    => true,
                    'is_booking'                  => true,
                    'needs_shipping_arrangement'  => $b->needsShipping(),
                    'fulfillment_id'              => null,
                    'fulfillment_status'          => null,
                    'print_count'                 => $b->print_count ?? 0,
                    'printed_at'                  => $b->printed_at ? \Carbon\Carbon::parse($b->printed_at)->toIso8601String() : null,
                    'has_unresolved_lines'        => false,
                    'has_data_issues'             => false,
                    'logistics_status'            => null,
                    'fulfillment_scan_log'        => null,
                    'fulfillment_resolve_lines'   => [],
                    'fulfillment_packing_summary' => null,
                    'fulfillment_lines'           => [],
                ];
            });

            $mapped = $mapped->concat($bookingRows);
        }

        return response()->json($mapped->values());
    }

    public function syncSettlements(Request $request, Store $store): JsonResponse
    {
        // Beri waktu lebih untuk batch settlement (retry + panggilan per-order ke Shopee).
        set_time_limit(180);

        // Kunci yang SAMA dengan yang dipakai `marketplace:sync-settlements` (lihat
        // SyncSettlementsCommand::LOCK_TTL_SECONDS) supaya sync manual dari tombol UI dan
        // sync dari scheduler/CLI untuk toko yang sama tidak bisa tumpang tindih.
        $lock = \Illuminate\Support\Facades\Cache::lock("sync_settlements_store_{$store->id}", 900);

        if (! $lock->get()) {
            return response()->json([
                'message' => "Sync settlement sedang berjalan untuk toko {$store->name} (dari proses lain atau scheduler). Mohon tunggu beberapa saat lalu coba lagi.",
            ], 429);
        }

        $status = $store->connection_status;

        if ($status === 'TOKEN_EXPIRED') {
            try {
                if ($store->channel->code === 'shopee') {
                    /** @var \App\Services\Channels\Shopee\ShopeeChannel $shopee */
                    $shopee = app(\App\Services\Channels\Shopee\ShopeeChannel::class);
                    $shopee->refreshToken($store);
                    $store->refresh();
                    $status = $store->connection_status;
                }
            } catch (\Throwable $e) {
                $status = 'AUTH_REQUIRED';
                \Illuminate\Support\Facades\Log::warning('Token refresh failed during settlement sync', [
                    'store_id' => $store->id,
                    'store_name' => $store->name,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if ($status === 'NOT_CONNECTED') {
            $lock->release();
            return response()->json([
                'success' => false,
                'code'    => 'STORE_NOT_CONNECTED',
                'message' => "Toko {$store->name} belum terhubung ke {$store->channel->name}.",
                'action'  => [
                    'type'  => 'redirect',
                    'label' => 'Hubungkan ' . $store->channel->name,
                    'url'   => url('/marketplace/shopee/connect'),
                ],
            ], 422);
        }

        if ($status !== 'CONNECTED') {
            $lock->release();
            return response()->json([
                'success' => false,
                'code'    => 'SHOPEE_AUTH_REQUIRED',
                'message' => "Koneksi {$store->channel->name} untuk toko {$store->name} sudah tidak aktif. Login ulang diperlukan sebelum settlement bisa ditarik.",
                'action'  => [
                    'type'  => 'redirect',
                    'label' => 'Login Ulang ' . $store->channel->name,
                    'url'   => url('/marketplace/shopee/connect'),
                ],
            ], 401);
        }

        try {
            $result = $this->sync->syncSettlements(
                $store,
                $request->input('time_from') ? (int) $request->input('time_from') : null,
                $request->input('time_to')   ? (int) $request->input('time_to')   : null,
            );
        } catch (\Throwable $e) {
            // Detail exception (bisa memuat pesan driver HTTP, query, dsb) HANYA masuk log —
            // TIDAK dikirim ke client. Response ke UI selalu pesan generik yang ramah,
            // konsisten dengan pola catch(\Throwable) di syncOrders() (baris ~665-677).
            \Illuminate\Support\Facades\Log::error('Settlement sync gagal total', [
                'store_id'   => $store->id,
                'store_name' => $store->name,
                'error'      => $e->getMessage(),
                'exception'  => get_class($e),
            ]);

            return response()->json([
                'success' => false,
                'code'    => 'SETTLEMENT_SYNC_ERROR',
                'message' => "Sinkronisasi settlement untuk toko {$store->name} belum berhasil. Detail teknis sudah dicatat di log server — hubungi admin bila berulang.",
            ], 502);
        } finally {
            $lock->release();
        }

        return response()->json($result);
    }

    /**
     * Versi latar belakang dari syncSettlements() — untuk backfill jumlah besar (ratusan/
     * ribuan order) yang TIDAK mungkin selesai dalam satu siklus request HTTP tanpa
     * timeout. Mengikuti pola persis forceSyncBackground() (baris 546) yang sudah dipakai
     * untuk orders: dispatch command lewat Artisan::queue(), TIDAK dieksekusi langsung di
     * request ini. Butuh queue worker aktif di server (lihat catatan di response).
     *
     * Beda dengan syncSettlements() (tombol "Tarik Settlement Baru", satu batch @200 order,
     * sinkron/blocking): endpoint ini memicu `marketplace:sync-settlements --store=X --all`
     * yang mengulang batch sampai habis atau kena batas pengaman command (20 batch/12 menit
     * per eksekusi, lihat SyncSettlementsCommand::ALL_MAX_BATCHES/ALL_MAX_RUNTIME_SECONDS) —
     * cukup untuk backlog saat ini, dan aman diulang (idempotent, lock per toko) kalau
     * belum habis dalam satu run.
     */
    public function syncSettlementsBackground(Request $request, Store $store): JsonResponse
    {
        // Cek connection_status DULU (sama seperti syncSettlements()) — supaya tidak
        // meng-queue job yang sudah pasti gagal tanpa memberi tahu user sama sekali
        // (job latar belakang tidak punya jalur feedback real-time ke UI).
        $status = $store->connection_status;

        if ($status === 'TOKEN_EXPIRED') {
            try {
                if ($store->channel->code === 'shopee') {
                    /** @var \App\Services\Channels\Shopee\ShopeeChannel $shopee */
                    $shopee = app(\App\Services\Channels\Shopee\ShopeeChannel::class);
                    $shopee->refreshToken($store);
                    $store->refresh();
                    $status = $store->connection_status;
                }
            } catch (\Throwable $e) {
                $status = 'AUTH_REQUIRED';
                \Illuminate\Support\Facades\Log::warning('Token refresh failed before queueing background settlement sync', [
                    'store_id' => $store->id,
                    'store_name' => $store->name,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if ($status === 'NOT_CONNECTED') {
            return response()->json([
                'success' => false,
                'code'    => 'STORE_NOT_CONNECTED',
                'message' => "Toko {$store->name} belum terhubung ke {$store->channel->name}.",
                'action'  => [
                    'type'  => 'redirect',
                    'label' => 'Hubungkan ' . $store->channel->name,
                    'url'   => url('/marketplace/shopee/connect'),
                ],
            ], 422);
        }

        if ($status !== 'CONNECTED') {
            return response()->json([
                'success' => false,
                'code'    => 'SHOPEE_AUTH_REQUIRED',
                'message' => "Koneksi {$store->channel->name} untuk toko {$store->name} sudah tidak aktif. Login ulang diperlukan sebelum settlement bisa ditarik.",
                'action'  => [
                    'type'  => 'redirect',
                    'label' => 'Login Ulang ' . $store->channel->name,
                    'url'   => url('/marketplace/shopee/connect'),
                ],
            ], 401);
        }

        // Dispatch ke queue — TIDAK dieksekusi sekarang. Lock per toko
        // (sync_settlements_store_{id}) tetap ditegakkan di DALAM command saat job ini
        // benar-benar dijalankan worker, jadi aman kalau di-klik berkali-kali atau
        // bertabrakan dengan scheduler/CLI manual — yang belakangan cuma akan dilewati
        // (bukan dobel proses), bukan gagal.
        \Illuminate\Support\Facades\Artisan::queue('marketplace:sync-settlements', [
            '--store' => $store->id,
            '--all'   => true,
        ]);

        return response()->json([
            'success' => true,
            'status'  => 'queued',
            'message' => "Sinkronisasi settlement toko {$store->name} telah dikirim ke latar belakang. Proses berjalan bertahap (per batch 200 order) — refresh halaman beberapa saat lagi untuk lihat progresnya. PENTING: proses ini butuh queue worker aktif di server (php artisan queue:work) — kalau tidak ada worker yang berjalan, job akan menunggu di antrian tanpa pernah dieksekusi.",
        ]);
    }

    public function settlements(Request $request): JsonResponse
    {
        $query = MarketplaceOrderSettlement::with(['store:id,name', 'order:id,channel_order_id,order_status,ordered_at'])
            ->latest('settlement_time');

        if ($request->filled('store_id')) {
            $query->where('store_id', $request->store_id);
        }

        if ($request->filled('order_date_from')) {
            $query->whereHas('order', function ($q) use ($request) {
                $q->whereDate('ordered_at', '>=', $request->order_date_from);
            });
        }

        if ($request->filled('order_date_to')) {
            $query->whereHas('order', function ($q) use ($request) {
                $q->whereDate('ordered_at', '<=', $request->order_date_to);
            });
        }

        if ($request->filled('settlement_date_from')) {
            $query->whereDate('settlement_time', '>=', $request->settlement_date_from);
        }

        if ($request->filled('settlement_date_to')) {
            $query->whereDate('settlement_time', '<=', $request->settlement_date_to);
        }

        if ($request->filled('status')) {
            $query->whereHas('order', function ($q) use ($request) {
                $q->where('order_status', $request->status);
            });
        }

        if ($request->filled('settlement_status')) {
            if ($request->settlement_status === 'cair') {
                $query->whereNotNull('settlement_time');
            } elseif ($request->settlement_status === 'belum_cair') {
                $query->whereNull('settlement_time');
            }
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('channel_order_id', 'like', "%{$search}%");
        }

        $paginator = $query->paginate($request->input('per_page', 50))->through(fn ($s) => [
            'id'                    => $s->id,
            'store'                 => $s->store,
            'order'                 => $s->order,
            'channel_order_id'      => $s->channel_order_id,
            'buyer_payment_amount'  => (float) $s->buyer_payment_amount,
            'commission_fee'        => (float) $s->commission_fee,
            'service_fee'           => (float) $s->service_fee,
            'transaction_fee'       => (float) $s->transaction_fee,
            'seller_voucher'        => (float) $s->seller_voucher,
            'seller_coin_cash_back' => (float) $s->seller_coin_cash_back,
            'actual_shipping_fee'   => (float) $s->actual_shipping_fee,
            'shipping_fee_subsidy'  => (float) $s->shipping_fee_subsidy,
            'reverse_shipping_fee'  => (float) $s->reverse_shipping_fee,
            'activity_fee'          => (float) $s->activity_fee,
            'drc_adjustable_refund' => (float) $s->drc_adjustable_refund,
            'escrow_tax'            => (float) $s->escrow_tax,
            'ad_cost'               => (float) $s->ad_cost,
            'final_income'          => (float) $s->final_income,
            'settlement_time'       => $s->settlement_time?->toISOString(),
            'synced_at'             => $s->synced_at?->toISOString(),
            'raw_json'              => is_string($s->raw_json) ? json_decode($s->raw_json, true) : $s->raw_json,
        ]);

        if ($request->input('page', 1) == 1) {
            $aggr = (clone $query)->reorder()->selectRaw('
                COUNT(*) as count,
                SUM(buyer_payment_amount) as gross,
                SUM(final_income) as net,
                SUM(commission_fee + service_fee + transaction_fee + activity_fee + escrow_tax + ad_cost) as fees
            ')->first();
            $meta = [
                'kpi_count' => (int) $aggr->count,
                'kpi_gross' => (float) $aggr->gross,
                'kpi_net'   => (float) $aggr->net,
                'kpi_fees'  => (float) $aggr->fees,
            ];
        } else {
            $meta = null; // Frontend can preserve previous KPI state on page change
        }

        return response()->json([
            'paginator' => $paginator,
            'meta'      => $meta
        ]);
    }

    public function orderProfits(Request $request): JsonResponse
    {
        $query = MarketplaceOrder::with([
            'store:id,name,channel_id',
            'store.channel:id,code,name',
            'settlement',
            'items:id,marketplace_order_id,model_sku,item_sku,qty,price,mapping_status,internal_item_id,hpp_snapshot',
        ])
        ->where(function($q) {
            $q->whereNotIn('order_status', ['UNPAID', 'CANCELLED'])
              ->orWhereHas('settlement');
        });

        if ($request->filled('store_id')) {
            $query->where('store_id', $request->store_id);
        }

        if ($request->filled('order_date_from')) {
            $query->whereDate('ordered_at', '>=', $request->order_date_from);
        }

        if ($request->filled('order_date_to')) {
            $query->whereDate('ordered_at', '<=', $request->order_date_to);
        }

        if ($request->filled('settlement_date_from')) {
            $query->whereHas('settlement', function ($q) use ($request) {
                $q->whereDate('settlement_time', '>=', $request->settlement_date_from);
            });
        }

        if ($request->filled('settlement_date_to')) {
            $query->whereHas('settlement', function ($q) use ($request) {
                $q->whereDate('settlement_time', '<=', $request->settlement_date_to);
            });
        }

        if ($request->filled('status')) {
            $query->where('order_status', $request->status);
        }

        if ($request->filled('settlement_status')) {
            if ($request->settlement_status === 'cair') {
                $query->whereHas('settlement', function($q) {
                    $q->whereNotNull('settlement_time');
                });
            } elseif ($request->settlement_status === 'belum_cair') {
                $query->where(function($q) {
                    $q->doesntHave('settlement')
                      ->orWhereHas('settlement', function($q2) {
                          $q2->whereNull('settlement_time');
                      });
                });
            }
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('channel_order_id', 'like', "%{$search}%")
                  ->orWhereHas('items', function ($qi) use ($search) {
                      $qi->where('item_name', 'like', "%{$search}%")
                         ->orWhere('model_sku', 'like', "%{$search}%")
                         ->orWhere('item_sku', 'like', "%{$search}%");
                  });
            });
        }

        $orders = $query->get();

        // Pre-load HPP: build map sku → hpp_unit
        // Collect all unique SKUs from all items
        $allSkus = $orders->flatMap(fn ($o) => $o->items ?? collect())
            ->map(fn ($item) => $item->model_sku ?: $item->item_sku)
            ->filter()
            ->unique()
            ->values();

        $channelCode = $orders->first()?->store?->channel?->code;

        // Build sku → item_id map via SkuMapping
        $skuMappings = SkuMapping::whereIn('marketplace_sku', $allSkus)
            ->when($channelCode, fn ($q) => $q->where(fn ($q2) =>
                $q2->where('channel_code', $channelCode)->orWhereNull('channel_code')
            ))
            ->get()
            ->groupBy('marketplace_sku');

        // Get item IDs and load active HPP snapshots
        $itemIds = $skuMappings->map(fn ($group) => $group->sortByDesc('channel_code')->first()->item_id)->unique();

        $costSnapshots = ItemCostSnapshot::whereIn('item_id', $itemIds)
            ->active()
            ->orderByDesc('snapshot_date')
            ->orderByDesc('id')
            ->get()
            ->groupBy('item_id')
            ->map(fn ($snaps) => (float) $snaps->first()->unit_cost);

        // Build final sku → hpp_unit map
        $skuToHpp = [];
        foreach ($skuMappings as $sku => $group) {
            $itemId = $group->sortByDesc('channel_code')->first()->item_id;
            $skuToHpp[$sku] = $costSnapshots[$itemId] ?? null;
        }

        $rows = $orders->map(function ($order) use ($skuToHpp) {
            $s = $order->settlement;
            $items    = $order->items ?? collect();
            $hppTotal = 0.0;
            $hppMapped = true;

            $itemDetails = [];
            foreach ($items as $item) {
                $sku = $item->model_sku ?: $item->item_sku;
                $isMapped = $item->mapping_status === \App\Services\MarketplaceIssueService::MAPPING_MAPPED || !empty($item->internal_item_id);
                
                $hpp = (float) $item->hpp_snapshot;
                // Fallback to active snapshot if hpp_snapshot is 0
                if ($hpp <= 0 && $sku && isset($skuToHpp[$sku])) {
                    $hpp = (float) $skuToHpp[$sku];
                }

                if ($isMapped && $hpp > 0) {
                    $hppTotal += $hpp * (int) $item->qty;
                } else {
                    $hppMapped = false;
                }
                
                $itemDetails[] = [
                    'sku' => $sku ?: 'No SKU',
                    'qty' => (int) $item->qty,
                    'mapped' => $isMapped && $hpp > 0
                ];
            }

            $rawJson = $s && $s->raw_json ? $s->raw_json : $order->raw_json;
            if (is_string($rawJson)) {
                $rawJson = json_decode($rawJson, true);
            }

            $baseAmount = (float) ($order->total_paid_customer > 0 ? $order->total_paid_customer : $order->total_amount);
            $inc = $rawJson['income_details'] ?? [];
            $escrowAmount = (float)($inc['escrow_amount'] ?? $rawJson['payment_info']['net_revenue'] ?? 0);
            $isCompleted = in_array(strtoupper($order->order_status ?: $order->status), ['COMPLETED', 'SELESAI']);

            if ($s && $s->final_income !== null && (float)$s->final_income > 0) {
                $finalIncome = (float) $s->final_income;
            } else if ($isCompleted && $escrowAmount > 0) {
                $finalIncome = $escrowAmount;
            } else if ($isCompleted && $order->net_payout_estimated > 0) {
                $finalIncome = (float) $order->net_payout_estimated;
            } else {
                // Estimasi potong admin fee 24% (Hanya untuk belum selesai / belum ada data)
                $finalIncome = $baseAmount * 0.76;
            }

            $adCost      = $s ? (float) $s->ad_cost : 0.0;
            $profitNet   = $finalIncome - $hppTotal - $adCost;
            $buyerPayment = $s ? (float) $s->buyer_payment_amount : ($isCompleted && !empty($inc['buyer_total_amount']) ? (float)$inc['buyer_total_amount'] : $baseAmount);
            
            $omzetGross = (float) ($inc['cost_of_goods_sold'] ?? $inc['order_selling_price'] ?? $rawJson['cost_of_goods_sold'] ?? $rawJson['order_selling_price'] ?? $buyerPayment);

            $data = [
                'id'                    => $s ? $s->id : null,
                'channel_order_id'      => $order->channel_order_id,
                'store'                 => $order->store ? ['id' => $order->store->id, 'name' => $order->store->name] : null,
                'order'                 => [
                    'id'           => $order->id,
                    'order_status' => $order->order_status,
                    'ordered_at'   => $order->ordered_at?->toISOString(),
                ],
                'items'                 => $itemDetails,
                'buyer_payment_amount'  => $buyerPayment,
                'final_income'          => $finalIncome,
                'hpp_total'             => $hppTotal,
                'hpp_mapped'            => $hppMapped,
                'ad_cost'               => $adCost,
                'profit_gross'          => $finalIncome - $hppTotal,  // before ad cost
                'profit_net'            => $profitNet,
                'margin_pct'            => $omzetGross > 0
                    ? round($profitNet / $omzetGross * 100, 1)
                    : null,
                'settlement_time'       => $s ? $s->settlement_time?->toISOString() : null,
                'raw_json'              => $rawJson,
                // Detail potongan (untuk tooltip)
                'commission_fee'        => $s ? (float) $s->commission_fee : 0.0,
                'service_fee'           => $s ? (float) $s->service_fee : 0.0,
                'transaction_fee'       => $s ? (float) $s->transaction_fee : 0.0,
                'activity_fee'          => $s ? (float) $s->activity_fee : 0.0,
                'seller_voucher'        => $s ? (float) $s->seller_voucher : 0.0,
                'seller_coin_cash_back' => $s ? (float) $s->seller_coin_cash_back : 0.0,
                'shipping_fee_subsidy'  => $s ? (float) $s->shipping_fee_subsidy : 0.0,
            ];
            
            $itemsDiscount = 0;
            if (isset($rawJson['items']) && is_array($rawJson['items'])) {
                foreach ($rawJson['items'] as $it) {
                    $sellPrice = (float)($it['selling_price'] ?? 0);
                    $discPrice = (float)($it['discounted_price'] ?? 0);
                    if ($sellPrice > $discPrice) {
                        $itemsDiscount += ($sellPrice - $discPrice);
                    }
                }
            }
            
            $data['raw_json'] = $rawJson;
            $data['seller_discount'] = $itemsDiscount;
            
            return $data;
        });

        if ($request->filled('hpp_status')) {
            if ($request->hpp_status === 'empty') {
                $rows = $rows->where('hpp_mapped', false)->values();
            } elseif ($request->hpp_status === 'mapped') {
                $rows = $rows->where('hpp_mapped', true)->values();
            }
        }

        // 1. Calculate Global KPIs
        $kpiOmzet = 0;
        $kpiHpp = 0;
        $kpiNet = 0;
        $kpiProfit = 0;
        $kpiCount = $rows->count();
        
        foreach ($rows as $row) {
            $inc = $row['raw_json']['income_details'] ?? [];
            $omzetGross = (float)($inc['cost_of_goods_sold'] ?? $inc['order_selling_price'] ?? $row['raw_json']['cost_of_goods_sold'] ?? $row['raw_json']['order_selling_price'] ?? $row['buyer_payment_amount']);
            
            $kpiOmzet += $omzetGross;
            $kpiHpp += (float) $row['hpp_total'];
            $kpiNet += (float) $row['final_income'];
            $kpiProfit += (float) $row['profit_net'];
        }
        $kpiMargin = $kpiOmzet > 0 ? round(($kpiProfit / $kpiOmzet) * 100, 1) : null;
        $avgProfit = $kpiCount > 0 ? round($kpiProfit / $kpiCount) : 0;
        
        // 2. Sort the Collection
        if ($request->filled('sort')) {
            $sort = $request->sort;
            if ($sort === 'margin_asc') $rows = $rows->sortBy('margin_pct')->values();
            elseif ($sort === 'margin_desc') $rows = $rows->sortByDesc('margin_pct')->values();
            elseif ($sort === 'profit_asc') $rows = $rows->sortBy('profit_net')->values();
            elseif ($sort === 'profit_desc') $rows = $rows->sortByDesc('profit_net')->values();
            elseif ($sort === 'date_asc') $rows = $rows->sortBy(function ($r) { return $r['settlement_time'] ?? $r['order']['ordered_at'] ?? ''; })->values();
            elseif ($sort === 'date_desc') $rows = $rows->sortByDesc(function ($r) { return $r['settlement_time'] ?? $r['order']['ordered_at'] ?? ''; })->values();
        } else {
            // Default sort: latest settlement time or ordered at
            $rows = $rows->sortByDesc(function ($r) { return $r['settlement_time'] ?? $r['order']['ordered_at'] ?? ''; })->values();
        }
        
        // 3. Export to CSV if requested
        if ($request->export === 'csv') {
            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="profit_export.csv"',
            ];
            
            $callback = function() use ($rows) {
                $file = fopen('php://output', 'w');
                // CSV Header
                fputcsv($file, ['Order SN', 'Toko', 'Status', 'Tgl Order', 'Tgl Cair', 'Harga Jual', 'Promosi Seller (Voucher)', 'Promosi Seller (Koin)', 'Dana Cair', 'HPP', 'Profit', 'Margin %']);
                
                foreach ($rows as $row) {
                    $inc = $row['raw_json']['income_details'] ?? [];
                    $omzetGross = (float)($inc['cost_of_goods_sold'] ?? $inc['order_selling_price'] ?? $row['raw_json']['cost_of_goods_sold'] ?? $row['raw_json']['order_selling_price'] ?? $row['buyer_payment_amount']);
                    
                    fputcsv($file, [
                        $row['channel_order_id'],
                        $row['store']['name'] ?? '',
                        $row['order']['order_status'] ?? '',
                        $row['order']['ordered_at'] ?? '',
                        $row['settlement_time'] ?? 'Belum Cair',
                        $omzetGross,
                        $row['seller_voucher'],
                        $row['seller_coin_cash_back'],
                        $row['final_income'],
                        $row['hpp_total'],
                        $row['profit_net'],
                        $row['margin_pct']
                    ]);
                }
                fclose($file);
            };
            
            return response()->stream($callback, 200, $headers);
        }
        
        // 4. Manual Pagination
        $perPage = (int) $request->input('per_page', 50);
        $page = (int) $request->input('page', 1);
        $pagedData = $rows->slice(($page - 1) * $perPage, $perPage)->values();
        $paginator = new \Illuminate\Pagination\LengthAwarePaginator($pagedData, $kpiCount, $perPage, $page);

        $lastSync = \App\Models\MarketplaceSyncLog::whereIn('action', ['sync_finance', 'sync_settlements'])
            ->latest()
            ->value('created_at');

        return response()->json([
            'paginator' => $paginator,
            'meta'      => [
                'kpi_omzet'   => $kpiOmzet,
                'kpi_hpp'     => $kpiHpp,
                'kpi_net'     => $kpiNet,
                'kpi_profit'  => $kpiProfit,
                'kpi_margin'  => $kpiMargin,
                'avg_profit'  => $avgProfit,
                'kpi_count'   => $kpiCount,
                'last_sync'   => $lastSync ? $lastSync->toISOString() : null
            ]
        ]);
    }

    public function updateSettlementAdCost(Request $request, MarketplaceOrderSettlement $settlement): JsonResponse
    {
        $data = $request->validate([
            'ad_cost' => ['required', 'numeric', 'min:0'],
        ]);

        $settlement->update(['ad_cost' => $data['ad_cost']]);

        return response()->json(['message' => 'Ad cost diperbarui.', 'ad_cost' => (float) $settlement->ad_cost]);
    }

    public function syncAdCampaigns(Request $request, Store $store): JsonResponse
    {
        set_time_limit(180); // 3 menit — banyak campaign

        $dateFrom = $request->input('date_from', now()->subDays(29)->toDateString());
        $dateTo   = $request->input('date_to',   now()->toDateString());

        if (app()->environment('local') && function_exists('exec') && false === stripos(ini_get('disable_functions'), 'exec')) {
            try {
                $result = $this->sync->syncAdCampaigns($store, $dateFrom, $dateTo);
                return response()->json($result);
            } catch (\Throwable $e) {
                return response()->json(['message' => $e->getMessage()], 422);
            }
        }

        // Production: dispatch ke background menggunakan Job khusus (dengan timeout besar)
        \App\Jobs\SyncAdCampaignsJob::dispatch($store, $dateFrom, $dateTo);

        return response()->json([
            'status' => 'success', 
            'message' => 'Proses sinkronisasi campaign berjalan di background.'
        ]);
    }

    /**
     * Saldo iklan (ads credit) sebuah toko — v2.ads.get_total_balance
     */
    public function adsBalance(Store $store): JsonResponse
    {
        $res = $this->manager->driver($store)->getAdsTotalBalance($store);

        if (! empty($res['error'])) {
            return response()->json(['message' => $res['message'] ?? $res['error']], 422);
        }

        $data = $res['response'] ?? [];

        return response()->json([
            'balance'  => $data['total_balance'] ?? $data['balance'] ?? null,
            'currency' => $data['currency'] ?? 'IDR',
            'raw'      => $data,
        ]);
    }

    /**
     * Performa iklan harian level toko — v2.ads.get_all_cpc_ads_daily_performance
     */
    public function adsShopPerformance(Request $request, Store $store): JsonResponse
    {
        $dateFrom = $request->input('date_from', now()->subDays(29)->toDateString());
        $dateTo   = $request->input('date_to',   now()->toDateString());

        $res = $this->manager->driver($store)->getAdsShopDailyPerformance(
            $store,
            \Carbon\Carbon::parse($dateFrom)->format('d-m-Y'),
            \Carbon\Carbon::parse($dateTo)->format('d-m-Y'),
        );

        if (! empty($res['error'])) {
            return response()->json(['message' => $res['message'] ?? $res['error']], 422);
        }

        // Bentuk respons bervariasi antar region: response.day_list / array langsung
        $days = data_get($res, 'response.day_list')
            ?? data_get($res, 'response.daily_performance')
            ?? (is_array($res['response'] ?? null) && array_is_list($res['response']) ? $res['response'] : []);

        $rows = collect($days)->map(fn ($d) => [
            'date'        => $d['date'] ?? null,
            'impressions' => $d['impression'] ?? $d['impressions'] ?? 0,
            'clicks'      => $d['clicks'] ?? $d['click'] ?? 0,
            'ctr'         => $d['ctr'] ?? null,
            'spend'       => $d['expense'] ?? $d['spend'] ?? 0,
            'orders'      => $d['broad_order'] ?? $d['orders'] ?? 0,
            'gmv'         => $d['broad_gmv'] ?? $d['broad_order_amount'] ?? $d['gmv'] ?? 0,
            'roas'        => $d['broad_roi'] ?? $d['roas'] ?? null,
        ])->values();

        return response()->json(['days' => $rows]);
    }

    /**
     * Saldo iklan SEMUA toko Shopee aktif (untuk filter "semua toko").
     */
    public function adsBalanceAll(): JsonResponse
    {
        $stores = Store::whereHas('channel', fn ($q) => $q->whereIn('code', ['SHOPEE', 'SHP', 'shopee']))
            ->where('status', 'active')->get();

        $rows = [];
        $total = 0;
        foreach ($stores as $store) {
            try {
                $res = $this->manager->driver($store)->getAdsTotalBalance($store);
                $bal = data_get($res, 'response.total_balance') ?? data_get($res, 'response.balance');
                if ($bal !== null) {
                    $total += (float) $bal;
                    \App\Models\MarketplaceAdsBalanceLog::create(['store_id' => $store->id, 'balance' => $bal]);
                }
                $rows[] = ['store_id' => $store->id, 'store' => $store->name, 'balance' => $bal, 'error' => $res['message'] ?? $res['error'] ?? null];
            } catch (\Throwable $e) {
                $rows[] = ['store_id' => $store->id, 'store' => $store->name, 'balance' => null, 'error' => $e->getMessage()];
            }
        }

        return response()->json(['total' => $total, 'stores' => $rows]);
    }

    /**
     * Sync performa iklan harian ke DB (semua toko Shopee atau satu toko).
     * Simpan snapshot saldo sekalian.
     */
    public function syncAdsDaily(Request $request): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        set_time_limit(300);
        \Log::info('SYNC_ADS_DAILY CALLED', $request->all());

        $syncType = $request->input('sync_type', '1_week');
        $dateTo   = now()->toDateString();
        
        if ($syncType === 'today') {
            $dateFrom = now()->toDateString();
            $dateTo   = now()->toDateString();
        } elseif ($syncType === 'yesterday') {
            $dateFrom = now()->subDay()->toDateString();
            $dateTo   = now()->subDay()->toDateString();
        } elseif ($syncType === '1_week') {
            $dateFrom = now()->subDays(7)->toDateString();
        } elseif ($syncType === '1_month') {
            $dateFrom = now()->subDays(30)->toDateString();
        } elseif ($syncType === '3_months') {
            $dateFrom = now()->subDays(90)->toDateString();
        } else {
            // custom
            $dateFrom = $request->input('date_from', now()->subDays(7)->toDateString());
            $dateTo   = $request->input('date_to', now()->toDateString());
        }

        $stores = Store::whereHas('channel', fn ($q) => $q->whereIn('code', ['SHOPEE', 'SHP', 'shopee']))
            ->where('status', 'active')
            ->where('is_active', true)
            ->when($request->filled('store_id'), fn ($q) => $q->where('id', $request->integer('store_id')))
            ->get();

        return response()->stream(function () use ($stores, $dateFrom, $dateTo) {
            $sendEvent = function ($type, $message, $progress = null, $extra = []) {
                echo json_encode(array_merge(['type' => $type, 'message' => $message, 'progress' => $progress], $extra)) . "\n";
                if (ob_get_level() > 0) ob_flush();
                flush();
            };

            $sendEvent('log', 'Memulai sinkronisasi Iklan...', 5);
            $saved = 0;
            $errors = [];
            $totalStores = $stores->count();
            
            if ($totalStores === 0) {
                $sendEvent('done', 'Tidak ada toko aktif untuk disinkronisasi.', 100, ['saved' => 0, 'errors' => []]);
                return;
            }

            foreach ($stores as $index => $store) {
                $baseProgress = 5 + (($index / $totalStores) * 90);
                $sendEvent('log', "Mempersiapkan koneksi toko {$store->name}...", $baseProgress + 2);

                try {
                    $driver = $this->manager->driver($store);
                } catch (\Throwable $e) {
                    $errors[] = "[{$store->name}] " . $e->getMessage();
                    $sendEvent('log', "Gagal menghubungi {$store->name}: " . $e->getMessage(), $baseProgress + 5);
                    continue;
                }

                // 1. Snapshot saldo
                $sendEvent('log', "[{$store->name}] Sinkronisasi saldo iklan...", $baseProgress + 5);
                try {
                    $bal = data_get($driver->getAdsTotalBalance($store), 'response.total_balance');
                    if ($bal !== null) {
                        \App\Models\MarketplaceAdsBalanceLog::create(['store_id' => $store->id, 'balance' => $bal]);
                        $sendEvent('log', "[{$store->name}] Saldo berhasil disimpan.", $baseProgress + 10);
                    }
                } catch (\Throwable $e) { 
                    $sendEvent('log', "[{$store->name}] Gagal menarik saldo: " . $e->getMessage(), $baseProgress + 10);
                }

                $syncService = app(\App\Services\Marketplace\Ads\ShopeeAdsSyncService::class);
                $run = \App\Models\MarketplaceAdsSyncRun::create([
                    'store_id' => $store->id,
                    'sync_type' => 'manual_dashboard',
                    'date_from' => $dateFrom,
                    'date_to' => $dateTo,
                    'status' => 'processing',
                    'started_at' => now(),
                ]);

                $sendEvent('log', "[{$store->name}] Sinkronisasi Daftar Kampanye...", $baseProgress + 12);
                try {
                    $syncService->syncCampaignsAndSettings($store, $run);
                } catch (\Throwable $e) {
                    $sendEvent('log', "[{$store->name}] Gagal menarik daftar kampanye: " . $e->getMessage(), $baseProgress + 15);
                }

                // 2. Performa harian, kampanye, dan produk
                $sendEvent('log', "[{$store->name}] Memulai sinkronisasi performa...", $baseProgress + 15);
                
                $currentStart = \Carbon\Carbon::parse($dateFrom);
                $finalEnd     = \Carbon\Carbon::parse($dateTo);
                
                while ($currentStart->lessThanOrEqualTo($finalEnd)) {
                    $currentEnd = clone $currentStart;
                    $currentEnd->addDays(29);
                    if ($currentEnd->greaterThan($finalEnd)) {
                        $currentEnd = clone $finalEnd;
                    }
                    
                    $sendEvent('log', "[{$store->name}] Menarik periode " . $currentStart->format('d-m-Y') . " s/d " . $currentEnd->format('d-m-Y'), $baseProgress + 20);
                    
                    try {
                        $syncService->syncShopDailyPerformance($store, $currentStart->format('Y-m-d'), $currentEnd->format('Y-m-d'), $run);
                        $sendEvent('log', "[{$store->name}] Performa harian toko tersimpan.", $baseProgress + 22);

                        $syncService->syncCampaignDailyPerformance($store, $currentStart->format('Y-m-d'), $currentEnd->format('Y-m-d'), $run);
                        $sendEvent('log', "[{$store->name}] Performa kampanye tersimpan.", $baseProgress + 25);

                        $syncService->syncGmsDailyPerformance($store, $currentStart->format('Y-m-d'), $currentEnd->format('Y-m-d'), $run);
                        $sendEvent('log', "[{$store->name}] Performa produk tersimpan.", $baseProgress + 27);

                        $sendEvent('log', "[{$store->name}] Mengambil data performa per-jam (heatmap)...", $baseProgress + 28);
                        $hStart = clone $currentStart;
                        while ($hStart->lessThanOrEqualTo($currentEnd)) {
                            $syncService->syncShopHourlyPerformance($store, $hStart->format('Y-m-d'), $run);
                            $hStart->addDay();
                            usleep(100000); // 0.1s rate limit
                        }
                        $sendEvent('log', "[{$store->name}] Performa per-jam tersimpan.", $baseProgress + 30);
                    } catch (\Throwable $e) {
                        $errors[] = "[{$store->name}] " . $e->getMessage();
                        $sendEvent('log', "[{$store->name}] Kesalahan pada periode ini: " . $e->getMessage());
                    }
                    
                    $currentStart = $currentEnd->addDay();
                    usleep(500000); // 0.5 sec delay between chunks
                }
                $run->update(['status' => 'success', 'finished_at' => now()]);
                $saved += $run->total_updated;
                $sendEvent('log', "[{$store->name}] Berhasil memperbarui {$run->total_updated} baris data.", $baseProgress + (90 / $totalStores));
            }

            $sendEvent('done', 'Sinkronisasi selesai!', 100, [
                'saved' => $saved,
                'stores' => $totalStores,
                'errors' => $errors,
                'status' => 'success'
            ]);
        }, 200, [
            'Content-Type' => 'application/x-ndjson',
            'X-Accel-Buffering' => 'no',
            'Cache-Control' => 'no-cache',
            'Connection' => 'keep-alive',
        ]);
    }

    /**
     * Riwayat saldo iklan (dari snapshot log) — saldo terakhir per hari.
     */
    public function adsBalanceHistory(Request $request): JsonResponse
    {
        $days = min(365, max(7, (int) $request->input('days', 60)));

        $logs = \App\Models\MarketplaceAdsBalanceLog::where('created_at', '>=', now()->subDays($days))
            ->when($request->filled('store_id'), fn ($q) => $q->where('store_id', $request->integer('store_id')))
            ->with('store:id,name')
            ->orderBy('created_at')
            ->get();

        // Saldo TERAKHIR per (tanggal, toko) → lalu total per tanggal
        $byDayStore = [];
        foreach ($logs as $log) {
            $byDayStore[$log->created_at->toDateString()][$log->store_id] = (float) $log->balance;
        }

        $rows = collect($byDayStore)->map(fn ($stores, $date) => [
            'date'    => $date,
            'balance' => array_sum($stores),
            'stores'  => count($stores),
        ])->values();

        return response()->json(['days' => $rows]);
    }

    /**
     * Baca performa harian dari DB — mendukung "semua toko" (agregat) & per toko.
     */
    public function adsDaily(Request $request): JsonResponse
    {
        $dateFrom = $request->input('date_from', now()->subDays(29)->toDateString());
        $dateTo   = $request->input('date_to',   now()->toDateString());

        $q = \App\Models\MarketplaceAdsDaily::query()
            ->whereBetween('date', [$dateFrom, $dateTo])
            ->when($request->filled('store_id'), fn ($qq) => $qq->where('store_id', $request->integer('store_id')));

        // Agregat per tanggal (kalau semua toko → dijumlahkan lintas toko)
        $days = (clone $q)
            ->selectRaw('date,
                SUM(impressions) as impressions, SUM(clicks) as clicks,
                SUM(spend) as spend, SUM(orders) as orders, SUM(gmv) as gmv')
            ->groupBy('date')->orderBy('date')
            ->get()
            ->map(function ($r) {
                $r->ctr  = $r->impressions > 0 ? round($r->clicks / $r->impressions * 100, 2) : null;
                $r->roas = $r->spend > 0 ? round($r->gmv / $r->spend, 2) : null;
                return $r;
            });

        // Ringkasan per toko (untuk perbandingan antar toko)
        $perStore = (clone $q)
            ->selectRaw('store_id, SUM(spend) as spend, SUM(orders) as orders, SUM(gmv) as gmv')
            ->groupBy('store_id')
            ->with('store:id,name')
            ->get()
            ->map(fn ($r) => [
                'store' => $r->store?->name,
                'spend' => (float) $r->spend,
                'orders'=> (int) $r->orders,
                'gmv'   => (float) $r->gmv,
                'roas'  => $r->spend > 0 ? round($r->gmv / $r->spend, 2) : null,
            ]);

        return response()->json(['days' => $days, 'per_store' => $perStore]);
    }

    /** Debug: lihat raw response Shopee Ads API (hapus setelah selesai debug) */
    public function debugAdApi(Request $request, Store $store): JsonResponse
    {
        $driver     = $this->manager->driver($store);
        $sampleIds  = [34741562, 34741571, 65538832]; // 3 campaign pertama
        $today      = now()->format('d-m-Y');
        $monthAgo   = now()->subDays(29)->format('d-m-Y');

        return response()->json([
            'toggle_info'    => $driver->getShopToggleInfo($store),
            'campaign_ids'   => $driver->getCampaignIdList($store, 1, 5),
            'setting_info'   => $driver->getCampaignSettingInfo($store, $sampleIds),
            'daily_perf'     => $driver->getCampaignDailyPerformance($store, $sampleIds, $monthAgo, $today),
        ]);
    }

    public function adsAnalytics(Request $request): JsonResponse
    {
        $dateFrom = $request->input('date_from', now()->subDays(29)->toDateString());
        $dateTo   = $request->input('date_to',   now()->toDateString());
        $storeId  = $request->input('store_id');
        $groupBy  = $request->input('group_by', 'campaign'); // campaign | item | group

        // ── 1. Agregasi metrik harian per campaign dalam rentang tanggal ──────
        //    Grain data ada di marketplace_ad_campaign_dailies → dijumlahkan
        //    supaya rentang tanggal apapun akurat (bukan bergantung range sync).
        $agg = \DB::table('marketplace_ad_campaign_dailies')
            ->select(
                'store_id',
                'channel_campaign_id',
                \DB::raw('SUM(expense) as spend'),
                \DB::raw('SUM(broad_gmv) as gmv'),
                \DB::raw('SUM(direct_gmv) as direct_gmv'),
                \DB::raw('SUM(impressions) as impressions'),
                \DB::raw('SUM(clicks) as clicks'),
                \DB::raw('SUM(broad_order) as orders'),
                \DB::raw('SUM(direct_order) as items_sold'),
            )
            ->whereBetween('date', [$dateFrom, $dateTo])
            ->when($storeId, fn ($q) => $q->where('store_id', $storeId))
            ->groupBy('store_id', 'channel_campaign_id')
            ->get()
            ->keyBy(fn ($r) => $r->store_id . '|' . $r->channel_campaign_id);

        if ($agg->isEmpty()) {
            return response()->json([
                'rows'   => [],
                'groups' => $this->adGroupsPayload(),
                'view'   => $groupBy,
                'kpi'    => ['spend' => 0, 'gmv' => 0, 'roas' => null, 'acos' => null,
                             'orders' => 0, 'clicks' => 0, 'profit_after_ads' => null],
            ]);
        }

        // ── 2. Master campaign (identitas + mapping + grup + break-even) ──────
        $masters = MarketplaceAdCampaign::with(['store:id,name', 'internalItem:id,code,name', 'group:id,name,color'])
            ->when($storeId, fn ($q) => $q->where('store_id', $storeId))
            ->whereIn('channel_campaign_id', $agg->pluck('channel_campaign_id')->unique()->all())
            ->get()
            ->keyBy(fn ($c) => $c->store_id . '|' . $c->channel_campaign_id);

        // ── 3. Build baris campaign-level ─────────────────────────────────────
        $campaignRows = collect();
        foreach ($agg as $key => $a) {
            $c = $masters->get($key);
            if (! $c) continue;

            $spend  = (float) $a->spend;
            $gmv    = (float) $a->gmv;
            $orders = (int) $a->orders;
            $clicks = (int) $a->clicks;
            $units  = (int) $a->items_sold;

            $acos = $gmv > 0 && $spend > 0 ? round($spend / $gmv, 4) : null;
            $roas = GmvMaxAnalytics::roas($gmv, $spend);            // broad actual ROAS
            $directRoas = GmvMaxAnalytics::roas((float) $a->direct_gmv, $spend);
            $cpc  = $clicks > 0 ? round($spend / $clicks, 2) : null;

            // ── Setting GMV Max (Fase 2 kolom; sudah ter-load di $c, tanpa N+1) ──
            $targetRoas   = $c->target_roas !== null ? (float) $c->target_roas : null;
            $targetStatus = GmvMaxAnalytics::targetStatus($targetRoas, $roas, $c->bidding_method);

            // Break-even ACOS efektif (override manual atau derivasi HPP item)
            $beAcos = $c->break_even_acos !== null
                ? (float) $c->break_even_acos
                : $this->deriveBreakEvenAcos($c->internalItem, $gmv, $units);
            $bePct  = $beAcos !== null ? round($beAcos * 100, 1) : null;

            $profitAfterAds = ($beAcos !== null) ? round($gmv * $beAcos - $spend, 2) : null;
            $reco = $this->adsRecommendation($spend, $acos, $beAcos, $orders);

            $campaignRows->push([
                'id'              => $c->id,
                'store_id'        => $c->store_id,
                'store_name'      => $c->store?->name,
                'campaign_id'     => $c->channel_campaign_id,
                'campaign_name'   => $c->campaign_name,
                'campaign_type'   => $c->campaign_type,
                'status'          => $c->status,
                'channel_item_id' => $c->channel_item_id,
                'internal_item_id'=> $c->internal_item_id,
                'item_code'       => $c->internalItem?->code,
                'item_name'       => $c->internalItem?->name,
                'mapping_status'  => $c->mapping_status,
                'ad_group_id'     => $c->ad_group_id,
                'group_name'      => $c->group?->name,
                'group_color'     => $c->group?->color,
                // ── Setting GMV Max ──
                'ad_type'            => $c->ad_type,
                'bidding_method'     => $c->bidding_method,
                'target_roas'        => $targetRoas,
                'target_status'      => $targetStatus,
                'campaign_budget'    => $c->campaign_budget !== null ? (float) $c->campaign_budget : null,
                'campaign_status'    => $c->campaign_status,
                'campaign_placement' => $c->campaign_placement,
                'started_at'         => optional($c->started_at)->toDateTimeString(),
                'ended_at'           => optional($c->ended_at)->toDateTimeString(),
                'setting_synced_at'  => optional($c->setting_synced_at)->toDateTimeString(),

                'spend'           => $spend,
                'gmv'             => $gmv,
                'direct_gmv'      => (float) $a->direct_gmv,
                'direct_roas'     => $directRoas,
                'impressions'     => (int) $a->impressions,
                'clicks'          => $clicks,
                'orders'          => $orders,
                'items_sold'      => $units,
                'roas'            => $roas,
                'cpc'             => $cpc,
                'acos'            => $acos,
                'acos_pct'        => $acos !== null ? round($acos * 100, 1) : null,
                'break_even_acos'     => $beAcos,
                'break_even_acos_pct' => $bePct,
                'profit_after_ads'    => $profitAfterAds,
                'reco'            => $reco,
            ]);
        }

        // ── 3b. Filter server-side khusus GMV Max (opsional) ──────────────────
        $fBidding      = $request->input('bidding_method');
        $fCampStatus   = $request->input('campaign_status');
        $fTargetStatus = $request->input('target_status');
        if ($fBidding || $fCampStatus || $fTargetStatus) {
            $campaignRows = $campaignRows->filter(function ($r) use ($fBidding, $fCampStatus, $fTargetStatus) {
                if ($fBidding && ($r['bidding_method'] ?? null) !== $fBidding) return false;
                if ($fCampStatus && ($r['campaign_status'] ?? null) !== $fCampStatus) return false;
                if ($fTargetStatus && ($r['target_status'] ?? null) !== $fTargetStatus) return false;
                return true;
            })->values();
        }

        // ── 4. Regroup jika perlu (per item internal / per grup) ──────────────
        $rows = match ($groupBy) {
            'item'  => $this->aggregateAdRows($campaignRows, 'internal_item_id',
                fn ($r) => $r['item_name'] ?? ($r['internal_item_id'] ? 'Item #' . $r['internal_item_id'] : '⚠ Belum di-mapping'),
                fn ($r) => ['item_code' => $r['item_code'], 'internal_item_id' => $r['internal_item_id']]),
            'group' => $this->aggregateAdRows($campaignRows, 'ad_group_id',
                fn ($r) => $r['group_name'] ?? '— Tanpa Grup —',
                fn ($r) => ['ad_group_id' => $r['ad_group_id'], 'group_color' => $r['group_color']]),
            default => $campaignRows->sortByDesc('spend')->values(),
        };

        // ── 5. Overall KPI (selalu level campaign; broad = attributed utama) ──
        $totSpend    = $campaignRows->sum('spend');
        $totGmv      = $campaignRows->sum('gmv');       // broad — JANGAN jumlahkan dgn direct
        $totDirect   = $campaignRows->sum('direct_gmv');
        // Last sync = setting/perf tersinkron terbaru dari campaign yang tampil
        $lastSync = $masters->flatMap(fn ($c) => [$c->setting_synced_at, $c->synced_at])
            ->filter()->max();
        $kpi = [
            'spend'            => $totSpend,
            'gmv'              => $totGmv,
            'direct_gmv'       => $totDirect,
            'roas'             => GmvMaxAnalytics::roas($totGmv, $totSpend),
            'direct_roas'      => GmvMaxAnalytics::roas($totDirect, $totSpend),
            'weighted_target_roas' => GmvMaxAnalytics::weightedTargetRoas($campaignRows),
            'acos'             => $totGmv  > 0 ? round($totSpend / $totGmv * 100, 1) : null,
            'orders'           => $campaignRows->sum('orders'),
            'clicks'           => $campaignRows->sum('clicks'),
            'active_campaigns' => $campaignRows->where('campaign_status', 'ongoing')->count(),
            'below_target'     => $campaignRows->where('target_status', 'below')->count(),
            'profit_after_ads' => $campaignRows->filter(fn ($r) => $r['profit_after_ads'] !== null)->sum('profit_after_ads') ?: null,
            'unmapped'         => $campaignRows->where('internal_item_id', null)->count(),
            'last_sync'        => $lastSync ? $lastSync->toDateTimeString() : null,
        ];

        return response()->json([
            'rows'   => $rows->values(),
            'groups' => $this->adGroupsPayload(),
            'view'   => $groupBy,
            'kpi'    => $kpi,
        ]);
    }

    /**
     * Break-even ACOS (0..1) diturunkan dari harga jual teramati (gmv/unit)
     * vs HPP item internal. Null jika data tak cukup.
     */
    private function deriveBreakEvenAcos(?Item $item, float $gmv, int $units): ?float
    {
        if (! $item || $units <= 0 || $gmv <= 0) return null;
        $hpp = (float) ($item->hpp ?? $item->base_unit_cost ?? 0);
        if ($hpp <= 0) return null;
        $avgPrice = $gmv / $units;
        if ($hpp >= $avgPrice) return null;
        return round(($avgPrice - $hpp) / $avgPrice, 6);
    }

    /**
     * Agregasi baris campaign menjadi baris grup (per item / per grup).
     * Rasio dihitung ulang; profit dijumlahkan; break-even = rata-rata tertimbang gmv.
     */
    private function aggregateAdRows($campaignRows, string $keyField, callable $labelFn, callable $metaFn)
    {
        return $campaignRows
            ->groupBy(fn ($r) => $r[$keyField] ?? '∅')
            ->map(function ($group) use ($keyField, $labelFn, $metaFn) {
                $first  = $group->first();
                $spend  = $group->sum('spend');
                $gmv    = $group->sum('gmv');
                $orders = $group->sum('orders');
                $clicks = $group->sum('clicks');

                $acos = $gmv > 0 && $spend > 0 ? round($spend / $gmv, 4) : null;
                $roas = $spend > 0 ? round($gmv / $spend, 2) : null;

                $beWeighted = $group->filter(fn ($r) => $r['break_even_acos'] !== null && $r['gmv'] > 0);
                $beAcos = $beWeighted->isNotEmpty() && $beWeighted->sum('gmv') > 0
                    ? round($beWeighted->sum(fn ($r) => $r['break_even_acos'] * $r['gmv']) / $beWeighted->sum('gmv'), 4)
                    : null;
                $bePct  = $beAcos !== null ? round($beAcos * 100, 1) : null;

                $profit = $group->filter(fn ($r) => $r['profit_after_ads'] !== null)->sum('profit_after_ads') ?: null;
                $reco   = $this->adsRecommendation($spend, $acos, $beAcos, $orders);

                return array_merge([
                    'id'              => $keyField . ':' . ($first[$keyField] ?? 'none'),
                    'is_group'        => true,
                    'campaign_id'     => null,
                    'campaign_name'   => $labelFn($first),
                    'campaign_type'   => $group->count() . ' campaign',
                    'status'          => null,
                    'members'         => $group->count(),
                    'spend'           => $spend,
                    'gmv'             => $gmv,
                    'direct_gmv'      => $group->sum('direct_gmv'),
                    'impressions'     => $group->sum('impressions'),
                    'clicks'          => $clicks,
                    'orders'          => $orders,
                    'items_sold'      => $group->sum('items_sold'),
                    'roas'            => $roas,
                    'cpc'             => $clicks > 0 ? round($spend / $clicks, 2) : null,
                    'acos'            => $acos,
                    'acos_pct'        => $acos !== null ? round($acos * 100, 1) : null,
                    'break_even_acos'     => $beAcos,
                    'break_even_acos_pct' => $bePct,
                    'profit_after_ads'    => $profit,
                    'reco'            => $reco,
                ], $metaFn($first));
            })
            ->sortByDesc('spend')
            ->values();
    }

    /** Daftar semua grup iklan untuk UI (dengan jumlah campaign). */
    private function adGroupsPayload()
    {
        return MarketplaceAdGroup::withCount('campaigns')
            ->orderBy('name')
            ->get(['id', 'name', 'color', 'notes'])
            ->map(fn ($g) => [
                'id' => $g->id, 'name' => $g->name, 'color' => $g->color,
                'notes' => $g->notes, 'campaigns_count' => $g->campaigns_count,
            ]);
    }

    public function updateCampaignBreakEven(Request $request, MarketplaceAdCampaign $campaign): JsonResponse
    {
        $data = $request->validate([
            'break_even_acos' => ['required', 'numeric', 'min:0', 'max:1'],
        ]);

        $campaign->update(['break_even_acos' => $data['break_even_acos']]);

        return response()->json([
            'message'         => 'Break-even ACOS diperbarui.',
            'break_even_acos' => (float) $campaign->break_even_acos,
        ]);
    }

    /**
     * Mapping manual campaign iklan → item internal.
     * Menyimpan override di marketplace_ad_item_maps (agar bertahan saat re-sync)
     * dan langsung meng-update campaign.
     */
    public function mapCampaignItem(Request $request, MarketplaceAdCampaign $campaign): JsonResponse
    {
        $data = $request->validate([
            'internal_item_id' => ['nullable', 'integer', 'exists:items,id'],
        ]);

        if (empty($data['internal_item_id'])) {
            // Batalkan override → kembalikan ke resolusi otomatis
            MarketplaceAdItemMap::query()
                ->where('channel_code', 'shopee')
                ->where(function ($q) use ($campaign) {
                    if ($campaign->channel_item_id) $q->orWhere('channel_item_id', $campaign->channel_item_id);
                    $q->orWhere('channel_campaign_id', $campaign->channel_campaign_id);
                })->delete();

            app(\App\Services\AdItemMapper::class)->applyTo($campaign);
            $campaign->save();

            return response()->json([
                'message'          => 'Override mapping dihapus, kembali ke otomatis.',
                'internal_item_id' => $campaign->internal_item_id,
                'mapping_status'   => $campaign->mapping_status,
            ]);
        }

        MarketplaceAdItemMap::updateOrCreate(
            $campaign->channel_item_id
                ? ['channel_code' => 'shopee', 'channel_item_id' => $campaign->channel_item_id]
                : ['channel_code' => 'shopee', 'channel_campaign_id' => $campaign->channel_campaign_id],
            [
                'store_id'            => $campaign->store_id,
                'channel_campaign_id' => $campaign->channel_campaign_id,
                'internal_item_id'    => $data['internal_item_id'],
                'created_by'          => $request->user()?->id,
            ]
        );

        $campaign->update([
            'internal_item_id' => $data['internal_item_id'],
            'mapping_status'   => 'manual',
            'mapping_source'   => 'manual',
        ]);

        return response()->json([
            'message'          => 'Item internal berhasil di-mapping.',
            'internal_item_id' => $campaign->internal_item_id,
            'item'             => optional(Item::find($data['internal_item_id']))->only(['id', 'code', 'name']),
            'mapping_status'   => 'manual',
        ]);
    }

    /** Buat grup iklan. */
    public function storeAdGroup(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'  => ['required', 'string', 'max:120'],
            'color' => ['nullable', 'string', 'max:20'],
            'notes' => ['nullable', 'string'],
        ]);
        $data['created_by'] = $request->user()?->id;

        $group = MarketplaceAdGroup::create($data);

        return response()->json(['message' => 'Grup dibuat.', 'group' => $group], 201);
    }

    /** Update grup iklan. */
    public function updateAdGroup(Request $request, MarketplaceAdGroup $group): JsonResponse
    {
        $data = $request->validate([
            'name'  => ['sometimes', 'string', 'max:120'],
            'color' => ['nullable', 'string', 'max:20'],
            'notes' => ['nullable', 'string'],
        ]);

        $group->update($data);

        return response()->json(['message' => 'Grup diperbarui.', 'group' => $group]);
    }

    /** Assign / lepas campaign dari grup. */
    public function assignCampaignGroup(Request $request, MarketplaceAdCampaign $campaign): JsonResponse
    {
        $data = $request->validate([
            'ad_group_id' => ['nullable', 'integer', 'exists:marketplace_ad_groups,id'],
        ]);

        $campaign->update(['ad_group_id' => $data['ad_group_id'] ?? null]);

        return response()->json([
            'message'     => $data['ad_group_id'] ? 'Campaign dimasukkan ke grup.' : 'Campaign dikeluarkan dari grup.',
            'ad_group_id' => $campaign->ad_group_id,
        ]);
    }

    /**
     * Detail campaign GMV Max (READ-ONLY): setting + performa agregat periode +
     * daily trend. Raw setting payload hanya untuk owner (sudah tersanitasi).
     * Broad = attributed utama; direct = pembanding (tidak dijumlahkan).
     */
    public function campaignDetail(Request $request, MarketplaceAdCampaign $campaign): JsonResponse
    {
        $dateFrom = $request->input('date_from', now()->subDays(29)->toDateString());
        $dateTo   = $request->input('date_to',   now()->toDateString());

        $campaign->loadMissing('internalItem:id,code,name', 'store:id,name');

        $daily = \DB::table('marketplace_ad_campaign_dailies')
            ->where('store_id', $campaign->store_id)
            ->where('channel_campaign_id', $campaign->channel_campaign_id)
            ->whereBetween('date', [$dateFrom, $dateTo])
            ->orderBy('date')
            ->get(['date', 'expense', 'impressions', 'clicks', 'broad_order', 'direct_order', 'broad_gmv', 'direct_gmv']);

        $spend  = (float) $daily->sum('expense');
        $gmv    = (float) $daily->sum('broad_gmv');
        $dGmv   = (float) $daily->sum('direct_gmv');
        $impr   = (int) $daily->sum('impressions');
        $clicks = (int) $daily->sum('clicks');
        $bOrd   = (int) $daily->sum('broad_order');
        $dOrd   = (int) $daily->sum('direct_order');

        $performance = [
            'spend'       => $spend,
            'broad_gmv'   => $gmv,
            'direct_gmv'  => $dGmv,
            'impressions' => $impr,
            'clicks'      => $clicks,
            'broad_order' => $bOrd,
            'direct_order'=> $dOrd,
            'ctr'         => GmvMaxAnalytics::safeDiv($clicks * 100, $impr, 2),
            'cpc'         => GmvMaxAnalytics::safeDiv($spend, $clicks, 2),
            'broad_cvr'   => GmvMaxAnalytics::safeDiv($bOrd * 100, $clicks, 2),
            'direct_cvr'  => GmvMaxAnalytics::safeDiv($dOrd * 100, $clicks, 2),
            'broad_roas'  => GmvMaxAnalytics::roas($gmv, $spend),
            'direct_roas' => GmvMaxAnalytics::roas($dGmv, $spend),
            'broad_cpa'   => GmvMaxAnalytics::safeDiv($spend, $bOrd, 2),
            'direct_cpa'  => GmvMaxAnalytics::safeDiv($spend, $dOrd, 2),
        ];

        $setting = [
            'campaign_id'        => $campaign->channel_campaign_id,
            'item_id'            => $campaign->channel_item_id,
            'item_code'          => $campaign->internalItem?->code,
            'item_name'          => $campaign->internalItem?->name,
            'ad_type'            => $campaign->ad_type,
            'bidding_method'     => $campaign->bidding_method,
            'target_roas'        => $campaign->target_roas !== null ? (float) $campaign->target_roas : null,
            'campaign_budget'    => $campaign->campaign_budget !== null ? (float) $campaign->campaign_budget : null,
            'campaign_placement' => $campaign->campaign_placement,
            'campaign_status'    => $campaign->campaign_status,
            'started_at'         => optional($campaign->started_at)->toDateTimeString(),
            'ended_at'           => optional($campaign->ended_at)->toDateTimeString(),
            'setting_synced_at'  => optional($campaign->setting_synced_at)->toDateTimeString(),
        ];

        // Raw payload: owner-only, sanitasi ganda (defensif) walau sudah bersih saat simpan.
        $raw = null;
        if ($request->user()?->isOwner()) {
            $raw = \App\Services\MarketplaceSyncService::stripSensitive($campaign->raw_setting_payload ?? []);
        }

        return response()->json([
            'setting'             => $setting,
            'performance'         => $performance,
            'daily'               => $daily,
            'raw_setting_payload' => $raw,
        ]);
    }

    private function adsRecommendation(float $spend, ?float $acos, ?float $breakEvenAcos, int $orders): array
    {
        if ($spend === 0.0) {
            return ['label' => 'Tidak Aktif', 'color' => '#94a3b8', 'icon' => '⚪'];
        }
        if ($orders === 0) {
            return ['label' => 'Stop — 0 Konversi', 'color' => '#b91c1c', 'icon' => '🔴'];
        }
        if ($breakEvenAcos === null) {
            return ['label' => 'Set Break-Even', 'color' => '#b45309', 'icon' => '⚠️'];
        }
        if ($acos === null) {
            return ['label' => 'Data Tidak Lengkap', 'color' => '#94a3b8', 'icon' => '⚪'];
        }

        $ratio = $acos / $breakEvenAcos;

        if ($ratio <= 0.60) {
            return ['label' => 'Scale — Naikkan Budget', 'color' => '#16a34a', 'icon' => '🚀'];
        }
        if ($ratio <= 0.85) {
            return ['label' => 'Pertahankan', 'color' => '#2563eb', 'icon' => '✅'];
        }
        if ($ratio <= 1.00) {
            return ['label' => 'Perhatikan — Margin Tipis', 'color' => '#d97706', 'icon' => '⚡'];
        }

        return ['label' => 'Stop / Kurangi Bid', 'color' => '#b91c1c', 'icon' => '🔴'];
    }

    public function syncLogs(): JsonResponse
    {
        $logs = MarketplaceSyncLog::with('store:id,name')
            ->latest()
            ->limit(100)
            ->get()
            ->map(fn ($log) => [
                'id'         => $log->id,
                'store_name' => $log->store?->name,
                'action'     => $log->action,
                'status'     => $log->status,
                'message'    => $log->message,
                'payload'    => $log->payload ?? [],
                'created_at' => $log->created_at?->toISOString(),
            ]);

        return response()->json($logs);
    }

    public function warehouses(): JsonResponse
    {
        return response()->json(
            Warehouse::orderBy('name')->get(['id', 'code', 'name'])
        );
    }

    public function updateStore(Request $request, Store $store): JsonResponse
    {
        $data = $request->validate([
            'name'                        => ['sometimes', 'string', 'max:120'],
            'default_warehouse_id'        => ['nullable', 'integer', 'exists:warehouses,id'],
            'meta_shipping_document_type' => ['sometimes', 'string', 'in:THERMAL_AIR_WAYBILL,NORMAL_AIR_WAYBILL'],
        ]);

        $updateData = [];
        if (array_key_exists('name', $data)) {
            $updateData['name'] = $data['name'];
        }
        if (array_key_exists('default_warehouse_id', $data)) {
            $updateData['default_warehouse_id'] = $data['default_warehouse_id'];
        }

        if (array_key_exists('meta_shipping_document_type', $data)) {
            $meta = $store->meta ?? [];
            $meta['shipping_document_type'] = $data['meta_shipping_document_type'];
            $updateData['meta'] = $meta;
        }

        if (!empty($updateData)) {
            $store->update($updateData);
        }

        if (!empty($data['default_warehouse_id'])) {
            $warehouseId  = $data['default_warehouse_id'];
            $fulfillments = OrderFulfillment::whereHas('order', fn ($q) => $q->where('store_id', $store->id))
                ->whereIn('status', [OrderFulfillment::STATUS_DRAFT, OrderFulfillment::STATUS_PENDING_REVIEW])
                ->whereNull('warehouse_id')
                ->get();

            foreach ($fulfillments as $f) {
                $f->update(['warehouse_id' => $warehouseId]);
                $this->fulfillment->refreshStock($f->load('lines'));
            }
        }

        $store->load('channel');
        return response()->json([
            'message'              => 'Toko diperbarui.',
            'default_warehouse_id' => $store->default_warehouse_id,
            'fulfillments_updated' => isset($fulfillments) ? $fulfillments->count() : 0,
        ]);
    }

    public function disconnectStore(Store $store): JsonResponse
    {
        try {
            $store->update([
                'credentials' => null,
                'token_expires_at' => null,
            ]);
            
            return response()->json([
                'message' => 'Toko berhasil diputuskan koneksinya.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Terjadi kesalahan saat memutuskan koneksi: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Aktif/Nonaktifkan toko. Toko nonaktif tidak dimunculkan di peringatan
     * koneksi (dianggap sengaja tidak dipakai) dan dilewati sync chat.
     */
    public function toggleActive(Store $store): JsonResponse
    {
        $store->update(['is_active' => ! $store->is_active]);

        return response()->json([
            'success'   => true,
            'is_active' => (bool) $store->is_active,
            'message'   => $store->is_active ? 'Toko diaktifkan.' : 'Toko dinonaktifkan (disembunyikan dari peringatan).',
        ]);
    }

    public function deleteStore(Store $store): JsonResponse
    {
        // Cek apakah ada data krusial yang sudah masuk
        $hasOrders = \Illuminate\Support\Facades\DB::table('marketplace_orders')->where('store_id', $store->id)->exists();
        $hasShipments = \Illuminate\Support\Facades\DB::table('shipments')->where('store_id', $store->id)->exists();

        if ($hasOrders || $hasShipments) {
            return response()->json([
                'message' => 'Toko ini tidak dapat dihapus karena sudah memiliki riwayat pesanan (orders) atau pengiriman (shipments). Untuk menjaga integritas data akuntansi dan riwayat, sistem menolak penghapusan ini.'
            ], 422);
        }

        try {
            \Illuminate\Support\Facades\DB::transaction(function () use ($store) {
                // Hanya membersihkan data log yang tidak berdampak pada transaksi utama
                \Illuminate\Support\Facades\DB::table('marketplace_sync_logs')->where('store_id', $store->id)->delete();
                $store->delete();
            });
            
            return response()->json([
                'message' => 'Toko berhasil dihapus karena belum memiliki riwayat pesanan.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Gagal menghapus toko: ' . $e->getMessage()
            ], 500);
        }
    }

    // ─── Issue Center API ─────────────────────────────────────────────────────

    // ─────────────────────────────────────────────────────────────────────────
    // Store stats summary — untuk toko page cards
    // ─────────────────────────────────────────────────────────────────────────

    public function storesSummary(): JsonResponse
    {
        $today = now()->startOfDay();

        $storeIds = \App\Models\Store::pluck('id')->toArray();

        // Orders hari ini per toko
        $ordersToday = MarketplaceOrder::where('ordered_at', '>=', $today)
            ->whereIn('store_id', $storeIds)
            ->selectRaw('store_id, count(*) as cnt')
            ->groupBy('store_id')
            ->pluck('cnt', 'store_id');

        // Order READY_TO_SHIP belum punya fulfillment draft
        $unfulfilled = MarketplaceOrder::whereIn('order_status', ['READY_TO_SHIP', 'PROCESSED'])
            ->whereIn('store_id', $storeIds)
            ->whereDoesntHave('fulfillment')
            ->selectRaw('store_id, count(*) as cnt')
            ->groupBy('store_id')
            ->pluck('cnt', 'store_id');

        // Item bermasalah (data_status = incomplete) per toko
        $issueItems = MarketplaceOrderItem::whereHas(
                'order', fn ($q) => $q->whereIn('store_id', $storeIds)
            )
            ->where('data_status', 'incomplete')
            ->join('marketplace_orders', 'marketplace_order_items.marketplace_order_id', '=', 'marketplace_orders.id')
            ->selectRaw('marketplace_orders.store_id, count(marketplace_order_items.id) as cnt')
            ->groupBy('marketplace_orders.store_id')
            ->pluck('cnt', 'marketplace_orders.store_id');

        $result = [];
        foreach ($storeIds as $id) {
            $result[(string)$id] = [
                'orders_today' => (int) ($ordersToday[$id] ?? 0),
                'unfulfilled'  => (int) ($unfulfilled[$id] ?? 0),
                'issues'       => (int) ($issueItems[$id] ?? 0),
            ];
        }

        return response()->json($result);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Data Perlu Diperbaiki — list items dengan search + filter + tabs
    // ─────────────────────────────────────────────────────────────────────────

    public function issueItems(Request $request): JsonResponse
    {
        $tab      = $request->input('tab', 'all');   // all|sku_empty|mapping_not_found|missing_hpp|profit_incomplete|selesai
        $storeId  = $request->input('store_id');
        $q        = $request->input('q');
        $dateFrom = $request->input('date_from');
        $dateTo   = $request->input('date_to');
        $page     = max(1, (int) $request->input('page', 1));
        $perPage  = 20;

        $query = MarketplaceOrderItem::with([
            'order:id,store_id,channel_order_id,external_order_id,ordered_at,order_date',
            'order.store:id,name',
            'order.store.channel:id,code,name',
            'internalItem:id,name,code,base_unit_cost,hpp',
        ]);

        // Filter toko & tanggal via whereHas
        if ($storeId || $dateFrom || $dateTo) {
            $query->whereHas('order', function ($oq) use ($storeId, $dateFrom, $dateTo) {
                if ($storeId)  $oq->where('store_id', $storeId);
                if ($dateFrom) $oq->where('ordered_at', '>=', $dateFrom . ' 00:00:00');
                if ($dateTo)   $oq->where('ordered_at', '<=', $dateTo   . ' 23:59:59');
            });
        }

        // Search
        if ($q) {
            $query->where(function ($qr) use ($q) {
                $qr->where('item_name',      'like', "%{$q}%")
                   ->orWhere('variant_name',  'like', "%{$q}%")
                   ->orWhere('model_sku',     'like', "%{$q}%")
                   ->orWhere('item_sku',      'like', "%{$q}%")
                   ->orWhere('marketplace_sku','like', "%{$q}%")
                   ->orWhereHas('order', fn ($oq) => $oq
                       ->where('channel_order_id', 'like', "%{$q}%")
                       ->orWhere('external_order_id', 'like', "%{$q}%"));
            });
        }

        // Tab filter
        match ($tab) {
            'sku_empty'         => $query->skuEmpty(),
            'mapping_not_found' => $query->mappingNotFound(),
            'missing_hpp'       => $query->missingHpp(),
            'profit_incomplete' => $query->profitIncomplete(),
            'selesai'           => $query->where('data_status', 'valid'),
            default             => $query->hasIssues(),
        };

        $paginator = $query->orderByDesc('id')->paginate($perPage, ['*'], 'page', $page);

        $items = $paginator->through(function ($i) {
            // Recommendation: fuzzy-match by item_name for mapping_not_found items
            $rec = null;
            if ($i->issue_reason === 'mapping_not_found' && $i->item_name) {
                $found = Item::where('name', 'like', '%' . addcslashes($i->item_name, '%_\\') . '%')
                             ->select('id', 'name', 'code', 'base_unit_cost', 'hpp')
                             ->first();
                if ($found) {
                    $rec = [
                        'id'   => $found->id,
                        'name' => $found->name,
                        'code' => $found->code ?? '',
                        'hpp'  => (float) ($found->base_unit_cost ?: $found->hpp ?: 0),
                    ];
                }
            }

            $sku = $i->marketplace_sku ?? $i->model_sku ?? $i->item_sku ?? $i->external_sku;
            $mappingStatus = $i->mapping_status;
            $issueReason = $i->issue_reason;

            if ($mappingStatus === 'marketplace_sku_empty' && !empty($sku)) {
                $mappingStatus = 'mapping_not_found';
                $issueReason = 'mapping_not_found';
            }

            return [
                'id'                  => $i->id,
                'order_id'            => $i->order?->id,
                'order_number'        => $i->order?->channel_order_id ?? $i->order?->external_order_id,
                'ordered_at'          => $i->order?->ordered_at?->toISOString() ?? $i->order?->order_date,
                'store_name'          => $i->order?->store?->name,
                'channel_code'        => $i->order?->store?->channel?->code,
                'item_name'           => $i->item_name ?? $i->item_name_snapshot,
                'variant_name'        => $i->variant_name ?? $i->variant_snapshot,
                'marketplace_sku'     => $sku,
                'qty'                 => $i->qty,
                'mapping_status'      => $mappingStatus,
                'cost_status'         => $i->cost_status,
                'profit_status'       => $i->profit_status,
                'data_status'         => $i->data_status,
                'issue_reason'        => $issueReason,
                'internal_item_id'    => $i->internal_item_id,
                'internal_item_name'  => $i->internalItem?->name,
                'internal_item_code'  => $i->internalItem?->code,
                'hpp_current'         => $i->internalItem ? (float) ($i->internalItem->base_unit_cost ?: $i->internalItem->hpp ?: 0) : 0,
                'hpp_snapshot'        => $i->hpp_snapshot,
                'recommended_item'    => $rec,
            ];
        });

        // ── Pesanan Kilat (booking tanpa order lokal) — sisipkan di halaman 1 ──
        $bookingRows = [];
        if ($page === 1 && in_array($tab, ['all', 'sku_empty', 'mapping_not_found', 'missing_hpp'], true)) {
            $bookingRows = $this->issueService->bookingIssueRows(
                $storeId ? (int) $storeId : null, $q ?: null, $tab
            );

            // Filter tanggal (booking pakai create_time → ordered_at ISO string)
            if ($dateFrom || $dateTo) {
                $bookingRows = array_values(array_filter($bookingRows, function ($r) use ($dateFrom, $dateTo) {
                    if (! $r['ordered_at']) return false;
                    $t = \Carbon\Carbon::parse($r['ordered_at']);
                    if ($dateFrom && $t->lt(\Carbon\Carbon::parse($dateFrom)->startOfDay())) return false;
                    if ($dateTo   && $t->gt(\Carbon\Carbon::parse($dateTo)->endOfDay()))   return false;
                    return true;
                }));
            }
        }

        return response()->json([
            'data'         => array_merge($bookingRows, $items->items()),
            'current_page' => $paginator->currentPage(),
            'last_page'    => $paginator->lastPage(),
            'total'        => $paginator->total() + count($bookingRows),
            'per_page'     => $perPage,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Quick-action endpoints
    // ─────────────────────────────────────────────────────────────────────────

    public function fillItemSku(Request $request, MarketplaceOrderItem $item): JsonResponse
    {
        $data = $request->validate([
            'sku'              => 'required|string|max:100',
            'apply_to_similar' => 'boolean',
        ]);

        try {
            $result = $this->issueService->fillSku(
                $item, $data['sku'], (bool) ($data['apply_to_similar'] ?? false)
            );
            return response()->json([
                'message'  => "SKU berhasil diisi. {$result['affected']} item diperbarui.",
                'affected' => $result['affected'],
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function mapItemSku(Request $request, MarketplaceOrderItem $item): JsonResponse
    {
        $data = $request->validate([
            'internal_item_id' => 'required|integer|exists:items,id',
            'apply_to_all'     => 'boolean',
        ]);

        try {
            $result = $this->issueService->mapSku(
                $item, (int) $data['internal_item_id'], (bool) ($data['apply_to_all'] ?? false)
            );
            return response()->json([
                'message'  => "SKU berhasil dihubungkan. {$result['affected']} item diperbarui.",
                'affected' => $result['affected'],
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function fillItemHpp(Request $request, MarketplaceOrderItem $item): JsonResponse
    {
        $data = $request->validate([
            'hpp'             => 'required|numeric|min:1',
            'update_affected' => 'boolean',
        ]);

        try {
            $result = $this->issueService->fillHpp(
                $item, (float) $data['hpp'], (bool) ($data['update_affected'] ?? true)
            );
            return response()->json([
                'message'  => "HPP berhasil disimpan. {$result['affected']} item diperbarui.",
                'affected' => $result['affected'],
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function recalcItemProfit(Request $request, MarketplaceOrderItem $item): JsonResponse
    {
        try {
            $result = $this->issueService->recalcProfit($item);
            return response()->json([
                'message'  => "Profit berhasil dihitung ulang.",
                'affected' => $result['affected'],
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function bulkMapSku(Request $request): JsonResponse
    {
        $request->validate([
            'item_ids'         => 'required|array|min:1',
            'item_ids.*'       => 'integer',
            'internal_item_id' => 'required|integer|exists:items,id',
            'apply_to_all'     => 'boolean',
        ]);

        $items    = MarketplaceOrderItem::whereIn('id', $request->item_ids)->get();
        $updated  = 0;
        $errors   = [];

        foreach ($items as $item) {
            try {
                $this->issueService->mapSku($item, (int) $request->internal_item_id, $request->boolean('apply_to_all', false));
                $updated++;
            } catch (\Throwable $e) {
                $errors[] = 'Item #' . $item->id . ': ' . $e->getMessage();
            }
        }

        return response()->json([
            'message' => "{$updated} item berhasil di-mapping." . (count($errors) ? ' ' . count($errors) . ' gagal.' : ''),
            'updated' => $updated,
            'errors'  => $errors,
        ]);
    }

        public function searchInternalItems(Request $request): JsonResponse
    {
        $q     = trim($request->input('q', ''));
        $limit = min(20, (int) $request->input('limit', 15));

        $items = Item::query()
            ->when($q, fn ($query) => $query->where(function ($qr) use ($q) {
                $qr->where('name', 'like', "%{$q}%")
                   ->orWhere('code', 'like', "%{$q}%");
            }))
            ->select('id', 'name', 'code', 'base_unit_cost', 'hpp')
            ->limit($limit)
            ->get()
            ->map(fn ($i) => [
                'id'   => $i->id,
                'name' => $i->name,
                'code' => $i->code,
                'hpp'  => (float) ($i->base_unit_cost ?: $i->hpp ?: 0),
            ]);

        return response()->json($items);
    }

    /** Lookup item by exact code (untuk batch scan item di fulfillment). */
    public function itemByCode(Request $request): JsonResponse
    {
        $code = trim($request->input('code', ''));
        if (! $code) {
            return response()->json(['message' => 'Parameter code diperlukan.'], 422);
        }

        $item = Item::where('code', $code)->select('id', 'name', 'code', 'base_unit_cost', 'hpp')->first();
        if (! $item) {
            return response()->json(['message' => "Item dengan kode \"{$code}\" tidak ditemukan."], 404);
        }

        return response()->json([
            'id'   => $item->id,
            'name' => $item->name,
            'code' => $item->code,
            'hpp'  => (float) ($item->base_unit_cost ?: $item->hpp ?: 0),
        ]);
    }

        public function issueSummary(Request $request): JsonResponse
    {
        $storeId = $request->input('store_id');
        $summary = $this->issueService->summary($storeId ? (int) $storeId : null);

        // Gabungkan issue dari Pesanan Kilat (booking tanpa order lokal)
        $booking = $this->issueService->bookingIssueSummary($storeId ? (int) $storeId : null);
        $summary['sku_empty']         += $booking['sku_empty'];
        $summary['mapping_not_found'] += $booking['mapping_not_found'];
        $summary['missing_hpp']       += $booking['missing_hpp'];
        $summary['total_issues']      += $booking['total_issues'];
        $summary['data_incomplete']   += $booking['total_issues'];
        $summary['booking_issues']     = $booking['total_issues'];

        return response()->json($summary);
    }

    // ── Quick actions untuk item Pesanan Kilat (booking) ─────────────────────

    public function fillBookingItemSku(Request $request, \App\Models\MarketplaceBooking $booking): JsonResponse
    {
        $data = $request->validate([
            'index' => 'required|integer|min:0',
            'sku'   => 'required|string|max:100',
        ]);

        try {
            $result = $this->issueService->fillBookingItemSku($booking, (int) $data['index'], $data['sku']);
            return response()->json([
                'message'  => 'SKU berhasil diisi. Mapping nama produk tersimpan — order berikutnya otomatis terisi.',
                'affected' => $result['affected'],
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function mapBookingItemSku(Request $request, \App\Models\MarketplaceBooking $booking): JsonResponse
    {
        $data = $request->validate([
            'index'            => 'required|integer|min:0',
            'internal_item_id' => 'required|integer|exists:items,id',
        ]);

        try {
            $result = $this->issueService->mapBookingItemSku($booking, (int) $data['index'], (int) $data['internal_item_id']);
            return response()->json([
                'message'  => 'SKU berhasil dihubungkan ke item internal.',
                'affected' => $result['affected'],
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function remapOrderItems(Request $request): JsonResponse
    {
        set_time_limit(300);
        $storeId = $request->input('store_id');
        $result  = $this->issueService->remapItems($storeId ? (int) $storeId : null);
        return response()->json([
            'message' => 'Remap selesai.',
            'updated' => $result['updated'],
            'errors'  => $result['errors'],
        ]);
    }

    public function syncHpp(Request $request): JsonResponse
    {
        set_time_limit(300);
        $storeId = $request->input('store_id');
        $result  = $this->issueService->syncHpp($storeId ? (int) $storeId : null);
        return response()->json([
            'message' => 'HPP berhasil disinkronisasi.',
            'updated' => $result['updated'],
            'errors'  => $result['errors'],
        ]);
    }

    public function autoMapByCode(Request $request): JsonResponse
    {
        set_time_limit(300);
        $storeId = $request->input('store_id');
        $result  = $this->issueService->autoMapByCode($storeId ? (int) $storeId : null);
        return response()->json([
            'message' => 'Auto-map selesai.',
            'mapped'  => $result['mapped'],
            'skipped' => $result['skipped'],
            'errors'  => $result['errors'],
        ]);
    }

    /** [DEV ONLY] Hapus semua marketplace orders + fulfillments + mutations untuk reset testing. */
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

    /** [DEV ONLY] Hapus fulfillments saja, orders tetap (reset ke state "Perlu Proses"). */
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

    /** [DEV ONLY] Return order READY_TO_SHIP berikutnya yang belum punya fulfillment. */
    public function devNextOrder(): JsonResponse
    {
        if (app()->isProduction()) {
            return response()->json(['message' => 'Tidak diizinkan di production.'], 403);
        }

        $order = MarketplaceOrder::whereIn('order_status', ['READY_TO_SHIP', 'PROCESSED'])
            ->whereDoesntHave('fulfillment')
            ->orderByDesc('ordered_at')
            ->first(['id', 'channel_order_id', 'buyer_username', 'order_status']);

        if (! $order) {
            return response()->json(['message' => 'Tidak ada order yang perlu diproses.', 'order' => null]);
        }

        return response()->json(['order' => [
            'id'               => $order->id,
            'channel_order_id' => $order->channel_order_id,
            'buyer_username'   => $order->buyer_username,
            'order_status'     => $order->order_status,
        ]]);
    }

    /** [DEV ONLY] Stats ringkas untuk dev panel. */
    public function devStats(): JsonResponse
    {
        if (app()->isProduction()) {
            return response()->json(['message' => 'Tidak diizinkan di production.'], 403);
        }

        $orders       = \DB::table('marketplace_orders')->count();
        $perluProses  = \DB::table('marketplace_orders')
            ->whereIn('order_status', ['READY_TO_SHIP', 'PROCESSED'])
            ->whereNotExists(fn($q) => $q->from('order_fulfillments')->whereColumn('marketplace_order_id','marketplace_orders.id'))
            ->count();
        $sedangPacking = \DB::table('order_fulfillments')
            ->whereNotIn('status', ['confirmed', 'cancelled'])
            ->count();
        $fulfilled    = \DB::table('order_fulfillments')->where('status', 'confirmed')->count();

        return response()->json(compact('orders', 'perluProses', 'sedangPacking', 'fulfilled'));
    }

    /** [DEV ONLY] Buat dummy marketplace orders untuk testing. */
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

}
