# API Integration Audit

Audit statik project Laravel ini pada folder `app/`, `routes/`, `config/`, `resources/js/`, `resources/views/`, `database/`, `bootstrap/`, dan `tests/`.

Catatan:
- Angka ringkasan di bawah menghitung call site unik yang memicu request keluar, bukan jumlah request runtime.
- Saya tidak menjalankan migration, seeder, test yang mengubah database, `composer update`, `npm update`, atau command destruktif.
- `fetch`/`axios`/CDN yang hanya mengarah ke route internal aplikasi saya pisahkan dari komunikasi keluar yang benar-benar eksternal.

## A. Ringkasan

| Metrik | Hasil |
|---|---:|
| Total pemanggilan API eksternal teridentifikasi | 84 call site |
| Total provider / family API | 10 |
| Total controller terkait | 18 |
| Total service / client terkait | 12 |
| Total job / command terkait | 8 |
| Total pemanggilan langsung dari controller | 9 |
| Total pemanggilan tanpa proteksi rate-limit memadai | 11 |

Provider yang paling dominan:
- Shopee
- TikTok Shop
- OpenAI
- Google OAuth
- GitHub OAuth
- RajaOngkir
- Fonnte
- ip-api
- ipify
- QRServer

## B. Daftar Controller

| Controller | Method | Route | Service yang Dipanggil | Provider API | Status |
|---|---|---|---|---|---|
| `app/Http/Controllers/ShopeeStoreAuthController.php` | `callback` | `GET /marketplace/shopee/callback` | `ShopeeChannel::refreshToken()`, `ShopeeChannel::getShopInfo()` | Shopee | Direct auth exchange, simpan token ke DB |
| `app/Http/Controllers/TikTokShopAuthController.php` | `callback` | `GET /marketplace/tiktok/callback` | `TikTokShopChannel::refreshToken()`, `TikTokShopChannel::getAuthorizedShops()` | TikTok Shop | Direct auth exchange, simpan token ke DB |
| `app/Http/Controllers/Storefront/OAuthLoginController.php` | `redirect`, `callback`, `fetchProfile`, `resolveGithubEmail` | `GET /auth/{provider}`, `GET /auth/{provider}/callback` | OAuth provider endpoints via `Http` | Google OAuth, GitHub OAuth | Direct OAuth flow |
| `app/Http/Controllers/Auth/OAuthLoginController.php` | `callback`, `fetchProfile`, `resolveGithubEmail` | Route aktif tidak ditemukan di route files yang diaudit | OAuth provider endpoints via `Http` | Google OAuth, GitHub OAuth | Ada pemanggilan API, tetapi route aktif tidak ditemukan |
| `app/Http/Controllers/Ai/OpenAiConnectionController.php` | `store`, `persist` | `POST /ai/openai` | `OpenAiService::probeApiKey()` | OpenAI | Indirect, validasi koneksi API key |
| `app/Http/Controllers/AiAgentController.php` | `chat`, `task` | `POST /ai/agent/chat`, `POST /ai/agent/task` | `OpenAiService::generateAgentResponse()` | OpenAI | Indirect, server-side only |
| `app/Http/Controllers/Inventory/WarehouseIntelligenceController.php` | `index`, `insights`, `generateAiInsights` | `GET /inventory/warehouse-intelligence`, `GET /inventory/warehouse-intelligence/insights` | Direct `Http::post()` ke OpenAI | OpenAI | Direct HTTP dari controller, perlu difaktorkan |
| `app/Http/Controllers/Storefront/CartController.php` | `rajaongkirRequest`, `rajaongkirCostsByAvailableCourier` | `GET /checkout/ongkir` | cURL langsung | RajaOngkir | Direct HTTP via cURL |
| `app/Http/Controllers/MarketplaceController.php` | `shopInfo`, `promotions*`, `syncAdsDaily`, `syncOrders`, `syncSettlements`, `debugAdApi`, `adsBalanceAll` | `GET /marketplace/*`, `POST /api/marketplace/*` | `MarketplaceSyncService`, `ShopeeChannel`, `ShopeeAdsApiService`, `ShopeeAdsSyncService` | Shopee | Indirect orchestration, banyak jalur API |
| `app/Http/Controllers/MarketplaceBookingController.php` | `detail`, `tracking`, `ship`, `printDocument`, `createBulkPrintJob` | `GET /marketplace/kilat`, `POST /api/marketplace/*` | `MarketplaceSyncService`, `MarketplaceChannel`, queue jobs | Shopee | Indirect, queue-heavy |
| `app/Http/Controllers/MarketplaceLogisticsController.php` | `syncAwb`, `getShippingParameter`, `arrangeShipment`, `printDocument`, `createBulkPrintJob` | `GET /api/marketplace/stores/{store}/orders/{orderSn}/sync-awb`, `POST /api/marketplace/stores/{store}/sync-bookings`, `POST /api/marketplace/documents/bulk-print` | `MarketplaceChannel`, `MarketplaceSyncService`, queue jobs | Shopee | Indirect, banyak fallback dan polling |
| `app/Http/Controllers/MarketplaceProductController.php` | `sync`, `updateStock`, `updatePrice`, `updateSku`, `updateModelSku`, `toggleUnlist` | `POST /api/marketplace/products/*` | `MarketplaceProductService` | Shopee | Indirect via service |
| `app/Http/Controllers/MarketplaceBoostController.php` | `status`, `boostNow` | `GET /api/marketplace/boost/status`, `POST /api/marketplace/boost/now` | `MarketplaceBoostService` | Shopee | Indirect via service |
| `app/Http/Controllers/MarketplaceChatController.php` | `conversations`, `messages`, `send`, `markRead`, `diagnoseChat` | `GET /api/marketplace/chat/*`, `POST /api/marketplace/chat/*` | `MarketplaceChatService`, `ShopeeChannel` | Shopee | Indirect via service, ada jalur diagnostik langsung |
| `app/Http/Controllers/MarketplaceReturnController.php` | `live`, `syncAllReturns`, `syncReturns` | `GET /marketplace/returns`, `POST /api/marketplace/returns/*` | `MarketplaceSyncService`, queue job | Shopee | Indirect via service/job |
| `app/Http/Controllers/Marketplace/MarketplaceOrderController.php` | `show` | `GET /marketplace/orders/{order}` | `ShopeeChannel::getOrderDetail()` | Shopee | Direct refresh detail order |
| `app/Http/Controllers/OmnichannelController.php` | `shopInfo`, `syncOrders` | Route internal omnichannel | `OmnichannelSyncService` | Shopee / marketplace driver | Indirect |
| `app/Http/Controllers/WebhookController.php` | `shopee`, `simulate` | `POST /webhooks/shopee`, `POST /webhooks/simulate` | `ProcessShopeeWebhookJob` | Shopee webhook ingestion | Inbound webhook, bukan outbound API |

## C. Daftar Service atau API Client

| Service | Method | Provider | Endpoint | Dipanggil Oleh | Queue/Direct |
|---|---|---|---|---|---|
| `app/Services/Channels/Shopee/ShopeeChannel.php` | `get*`, `post*`, `refreshToken`, `getShopInfo`, `getOrders`, `getOrderDetail`, `getShipping*`, `getAds*`, `getConversation*`, `getItem*`, `getDiscount*`, `boostItems`, `unlistItems` | Shopee | `https://partner.shopeemobile.com` atau `base_url` dari config/store credential; ratusan call ke `v2/*` | Banyak controller, service, job, command | Direct HTTP client |
| `app/Services/Channels/TikTokShop/TikTokShopChannel.php` | `getShopInfo`, `getOrders`, `getOrderDetail`, `refreshToken` | TikTok Shop | `https://open-api.tiktok-shops.com` dan `https://auth.tiktok-shops.com` | `TikTokShopAuthController`, `MarketplaceController`, `MarketplaceSyncService` | Direct HTTP client |
| `app/Services/OpenAI/OpenAiService.php` | `generateAgentResponse`, `probeApiKey` | OpenAI | `https://api.openai.com/v1/responses` | `AiAgentController`, `Ai/OpenAiConnectionController` | Direct HTTP client |
| `app/Http/Controllers/Inventory/WarehouseIntelligenceController.php` | `generateAiInsights` | OpenAI | `https://api.openai.com/v1/responses` | `insights`, `index` | Direct HTTP client dari controller |
| `app/Services/WaNotificationService.php` | `sendOtp`, `sendMessage`, `sendFonnte` | Fonnte | `https://api.fonnte.com/send` | Flow notifikasi WA internal | Direct cURL |
| `app/Http/Controllers/Storefront/CartController.php` | `rajaongkirRequest`, `rajaongkirCostsByAvailableCourier` | RajaOngkir | `RAJAONGKIR_BASE_URL` default `https://rajaongkir.komerce.id/api/v1` | Checkout ongkir | Direct cURL |
| `app/Http/Middleware/TrackStorefrontVisitor.php` | `geolocateIp` | ip-api | `http://ip-api.com/json/{ip}` | Middleware visitor tracking | Direct cURL |
| `resources/views/admin/crm/visitors.blade.php` | `fetch('https://api.ipify.org?format=json')` | ipify | `https://api.ipify.org?format=json` | Frontend visitor UI | Browser fetch |
| `resources/views/storefront/checkout.blade.php` | QR code render | QRServer | `https://api.qrserver.com/v1/create-qr-code/...` | Checkout UI | Browser fetch / image URL |
| `app/Services/MarketplaceSyncService.php` | `syncOrders`, `syncSettlements`, `syncAdCampaigns`, `promoteBookingToOrder`, `getEscrowDetailWithRetry` | Shopee | turun ke `MarketplaceChannel`/`ShopeeChannel` | Banyak controller, command, job | Service orchestration |
| `app/Services/MarketplaceChatService.php` | `syncConversations`, `syncMessages`, `sendText`, `markRead` | Shopee sellerchat | `sellerchat/*` | `MarketplaceChatController`, webhook jobs | Service orchestration |
| `app/Services/MarketplaceProductService.php` | `syncProducts`, `updateStock`, `updatePrice`, `addDiscount`, `updateDiscount`, `deleteDiscountItem`, `setUnlist` | Shopee product/discount | `product/*`, `discount/*` | `MarketplaceProductController`, jobs, commands | Service orchestration |
| `app/Services/MarketplaceBoostService.php` | `boostNow`, `currentlyBoosted`, `run`, `runForStore` | Shopee boost | `product/boost_item`, `product/get_boosted_list` | `MarketplaceBoostController`, `RunBoostsCommand` | Service orchestration |
| `app/Services/Marketplace/Ads/ShopeeAdsApiService.php` | `getAdsTotalBalance`, `getAdsShopToggleInfo`, `getCampaignIdList`, `getCampaignSettingInfo`, `getAdsShopDailyPerformance`, `getCampaignDailyPerformance`, `getCampaignHourlyPerformance`, `getGmsCampaignPerformance`, `getGmsItemPerformance` | Shopee Ads | turun ke `ShopeeChannel` ads endpoints | `AdsActionService`, `MarketplaceSyncService`, `SyncAdsDailyCommand` | Wrapper + throttle |
| `app/Services/Marketplace/Ads/ShopeeAdsSyncService.php` | `syncBalance`, `syncCampaignsAndSettings`, `syncShopDailyPerformance`, `syncCampaignDailyPerformance`, `syncGmsDailyPerformance` | Shopee Ads | turun ke `ShopeeAdsApiService` | `MarketplaceController`, `SyncAdsCommand`, `ShopeeAdsSyncJob` | Queue-friendly orchestration |
| `app/Services/Marketplace/Ads/AdsActionService.php` | `realtimeStatus`, `actionGmsItem`, `actionCpcCampaign`, `actionGmsCampaign`, `campaignHourly` | Shopee Ads / Shopee product | `ShopeeAdsApiService`, `ShopeeChannel` | `AdsDashboardController`, `MarketplaceController` | Direct via service |
| `app/Services/OmnichannelSyncService.php` | `syncOrders`, `syncBookings`, `syncReturns`, `syncChats` | Shopee / marketplace driver | berbagai endpoint marketplace | `OmnichannelController`, commands | Service orchestration |

### Bukti singkat

```php
Http::withToken($apiKey)->acceptJson()->timeout(35)->post('https://api.openai.com/v1/responses', [...]);
```

```php
$ch = curl_init("http://ip-api.com/json/{$ip}?fields=status,city,regionName");
```

```php
$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_HTTPHEADER => ['Accept: application/json', 'key: ' . $apiKey],
]);
```

## D. Daftar Job dan Scheduler

| Job/Command | Provider | Trigger | Retry | Backoff | Rate Limit |
|---|---|---|---|---|---|
| `app/Jobs/ProcessShopeeWebhookJob.php` | Shopee | `POST /webhooks/shopee` | Tidak eksplisit | Tidak eksplisit | Dedupe cache 60 detik per payload |
| `app/Jobs/PromoteBookingToOrderJob.php` | Shopee | dipicu dari booking/ship flow | `$tries = 8` | delay bertahap 15/30/60/120/180/300/300 | Tidak ada limiter formal, tapi ada retry terjadwal |
| `app/Jobs/DownloadMarketplaceShippingDocumentJob.php` | Shopee | queue label download resi | `$tries = 6` | `[60, 180, 300]` | `ShouldBeUnique`, cache file, polling result |
| `app/Jobs/SyncMarketplaceBookings.php` | Shopee | `marketplace:sync-bookings` dan beberapa controller | Tidak eksplisit | Tidak eksplisit | Jendela 15 hari untuk mengurangi beban |
| `app/Jobs/SyncMarketplaceReturns.php` | Shopee | `marketplace:sync-returns` | Tidak eksplisit | Tidak eksplisit | Jendela 15 hari |
| `app/Jobs/SyncAdCampaignsJob.php` | Shopee Ads | `marketplace:sync-ads` / controller sync | `$tries = 1` | Tidak ada | Mengandalkan service throttle |
| `app/Jobs/Marketplace/SyncMarketplaceProductsJob.php` | Shopee | `marketplace:snapshot-products --sync` / sync manual | Tidak eksplisit | Tidak ada | Tidak ada |
| `app/Jobs/ShopeeAdsSyncJob.php` | Shopee Ads | `marketplace:sync-ads` / queue backfill | `$tries = 10` | `[30,60,120,300,600]` | `WithoutOverlapping`, cooldown cache, redis throttle |
| `app/Console/Commands/Marketplace/SyncOrdersCommand.php` | Shopee | `routes/console.php` schedule `everyFiveMinutes()` | mengandalkan service | mengandalkan service | lock per store, windowed sync |
| `app/Console/Commands/Marketplace/SyncSettlementsCommand.php` | Shopee | `routes/console.php` schedule `cron('7 */4 * * *')` | mengandalkan service | mengandalkan service | lock per store, retry aware |
| `app/Console/Commands/Marketplace/SyncAdsCommand.php` | Shopee Ads | schedule `marketplace:sync-ads` | service/job specific | service/job specific | cache dedupe untuk backfill, queue chain |
| `app/Console/Commands/Marketplace/SyncAdsDailyCommand.php` | Shopee Ads | schedule / manual | tidak eksplisit | tidak eksplisit | throttled via `ShopeeAdsApiService` |
| `app/Console/Commands/Marketplace/SyncChatsCommand.php` | Shopee | schedule `everyMinute()` | tidak eksplisit | tidak eksplisit | mengandalkan service |
| `app/Console/Commands/Marketplace/SnapshotProductsCommand.php` | Shopee | schedule `dailyAt('23:45')` | tidak eksplisit | tidak eksplisit | tidak ada limiter formal |
| `app/Console/Commands/Marketplace/RunBoostsCommand.php` | Shopee | schedule `everyFiveMinutes()` | tidak eksplisit | tidak eksplisit | pool schedule internal |

### Scheduler utama

`routes/console.php`
- `marketplace:sync-orders` setiap 5 menit
- `marketplace:sync-bookings` setiap jam di menit 17
- `marketplace:sync-returns` setiap jam di menit 37
- `marketplace:sync-settlements` tiap 4 jam
- `marketplace:sync-finance` tiap 4 jam
- `marketplace:sync-chats` tiap menit
- `marketplace:snapshot-products --sync true` tiap hari 23:45
- `marketplace:run-boosts` tiap 5 menit
- worker queue default/labels tiap menit
- worker queue heavy tiap 5 menit
- `marketplace:cleanup-labels` tiap hari 01:00

### Bukti singkat

```php
Schedule::call(fn () => Artisan::call('marketplace:sync-orders'))->everyFiveMinutes();
Schedule::call(fn () => Artisan::call('queue:work', ['--queue' => 'default,labels', '--stop-when-empty' => true]));
```

## E. Temuan Rate Limit

### Sudah aman

- `app/Services/Channels/Shopee/ShopeeChannel.php` sudah memakai `connectTimeout`, `timeout`, retry transien, pacing lock, cooldown cache, dan pembacaan `Retry-After`.
- `app/Services/Marketplace/Ads/ShopeeAdsApiService.php` sudah memakai Redis throttle `100 request / 60 detik per toko` dan cooldown cache `shopee-ads-cooldown:*`.
- `app/Services/MarketplaceSyncService.php` pada settlement sync sudah menunggu `retry_after` dan membatasi retry saat rate limited.
- `app/Jobs/ShopeeAdsSyncJob.php` sudah pakai `WithoutOverlapping`, `tries`, `backoff`, dan cooldown cache.
- `app/Jobs/DownloadMarketplaceShippingDocumentJob.php` sudah pakai `ShouldBeUnique` dan backoff.
- `ProcessShopeeWebhookJob` punya dedupe cache untuk payload webhook yang sama.

### Perlu diperbaiki

- `app/Services/OpenAI/OpenAiService.php` hanya memakai `retry(2, 250)` generik. Belum ada perlakuan khusus untuk `429` dan `Retry-After`.
- `app/Services/Channels/TikTokShop/TikTokShopChannel.php` tidak punya retry/backoff/rate limit umum, hanya auto-refresh token sekali saat access token invalid.
- `app/Http/Controllers/Inventory/WarehouseIntelligenceController.php` memanggil OpenAI langsung dari controller tanpa wrapper bersama atau rate-limit guard.
- `app/Http/Controllers/Storefront/OAuthLoginController.php` dan `app/Http/Controllers/Auth/OAuthLoginController.php` tidak menunjukkan throttle khusus untuk token/profile lookup.
- `app/Http/Controllers/Storefront/CartController.php` memakai cURL ke RajaOngkir tanpa retry/backoff.

### Risiko tinggi

- `app/Http/Middleware/TrackStorefrontVisitor.php` memanggil `http://ip-api.com/...` tanpa TLS.
- `app/Services/WaNotificationService.php` tidak punya retry/backoff/rate limit; fallback dev log bisa menulis OTP mentah.
- `app/Http/Controllers/MarketplaceBookingController.php` dan `app/Http/Controllers/MarketplaceLogisticsController.php` punya beberapa fallback/polling yang berpotensi memicu request ganda.
- `app/Http/Controllers/MarketplaceController.php` pada flow ads dan sync besar bisa memicu batch request besar jika lock/cooldown tidak konsisten.

### Bukti singkat

```php
if (isset($response['_meta']['http_status']) && $response['_meta']['http_status'] == 429) {
    Cache::put('shopee-ads-cooldown:' . $store->id, now()->addSeconds($retryAfter)->timestamp, $retryAfter);
}
```

```php
$response = Http::withToken($apiKey)->acceptJson()->timeout(35)->post('https://api.openai.com/v1/responses', [...]);
```

```php
$ch = curl_init("http://ip-api.com/json/{$ip}?fields=status,city,regionName");
```

## F. Temuan Keamanan

### 1) Secret tidak hardcoded, tetapi credential disimpan dan dipindahkan

- Saya tidak menemukan API key, token, atau client secret yang ditulis mentah di source code.
- Credential sensitif diambil dari `config()` / `.env` atau disimpan terenkripsi di DB.
- `ShopeeStoreAuthController` menulis `partner_key`, `access_token`, `refresh_token` ke credential store.
- `TikTokShopAuthController` menulis `app_secret`, `access_token`, `refresh_token` dan raw token response ke metadata store.
- `OpenAiConnectionController` menyimpan API key user dalam model `OpenAiConnection` yang terenkripsi.

### 2) OTP berpotensi bocor ke log

- `app/Services/WaNotificationService.php:106` menulis OTP ke log saat `FONNTE_TOKEN` kosong.

```php
Log::info("[OTP] Ke +{$phone}: {$otp}");
```

Risiko:
- OTP bisa masuk ke file log, aggregator, atau dashboard observability.
- Ini temuan keamanan paling sensitif di audit ini.

### 3) Cleartext HTTP ke pihak ketiga

- `app/Http/Middleware/TrackStorefrontVisitor.php:29` memakai `http://ip-api.com/...` bukan HTTPS.

```php
$ch = curl_init("http://ip-api.com/json/{$ip}?fields=status,city,regionName");
```

Risiko:
- IP visitor, kota, dan region dikirim tanpa TLS.
- Request dapat dimodifikasi atau diintip di jaringan yang tidak tepercaya.

### 4) External API exposure dari frontend

- `resources/views/admin/crm/visitors.blade.php` memanggil `https://api.ipify.org?format=json`.
- `resources/views/storefront/checkout.blade.php` memanggil `https://api.qrserver.com/v1/create-qr-code/...`.
- Ini bukan credential leak, tetapi tetap komunikasi keluar dari browser yang perlu disadari.

### 5) Tidak ditemukan

- Credential hardcoded di JavaScript.
- Credential hardcoded di Blade.
- `.env` yang ikut ter-commit pada folder yang diaudit.
- Token produksi yang tertulis mentah di source.

## G. Dependency Map

```text
marketplace.shopee.connect / marketplace.shopee.callback
└── ShopeeStoreAuthController@redirect / @callback
└── ShopeeChannel::refreshToken(), ShopeeChannel::getShopInfo()
└── Shopee auth + shop API

marketplace.tiktok.connect / marketplace.tiktok.callback
└── TikTokShopAuthController@redirect / @callback
└── TikTokShopChannel::refreshToken(), getAuthorizedShops()
└── TikTok Shop auth + shop API

auth.oauth.redirect / auth.oauth.callback
└── Storefront\OAuthLoginController@redirect / @callback
└── OAuth token/profile/email lookup
└── Google OAuth / GitHub OAuth

ai.openai.store
└── Ai/OpenAiConnectionController@store
└── OpenAiService::probeApiKey()
└── OpenAI /v1/responses

ai.agent.chat / ai.agent.task
└── AiAgentController@chat / @task
└── OpenAiService::generateAgentResponse()
└── OpenAI /v1/responses

inventory.warehouse-intelligence / inventory.warehouse-intelligence.insights
└── WarehouseIntelligenceController@index / insights
└── generateAiInsights() direct Http::post()
└── OpenAI /v1/responses

marketplace.sync
└── MarketplaceController@sync
└── MarketplaceSyncService::syncOrders(), syncSettlements(), syncAdCampaigns()
└── Shopee order / settlement / ads APIs

api/marketplace/products/sync
└── MarketplaceProductController@sync
└── SyncMarketplaceProductsJob -> MarketplaceProductService::syncProducts()
└── Shopee product / discount APIs

api/marketplace/boost/now
└── MarketplaceBoostController@boostNow
└── MarketplaceBoostService::boostNow()
└── Shopee product boost API

api/marketplace/chat/send
└── MarketplaceChatController@send
└── MarketplaceChatService::sendText()
└── Shopee sellerchat send_message

marketplace.logistics.ship
└── MarketplaceLogisticsController@arrangeShipment
└── MarketplaceChannel::shipOrder()/shipBooking()/getTrackingNumber()
└── Shopee logistics APIs

marketplace.kilat print document flow
└── MarketplaceBookingController@printDocument / createBulkPrintJob
└── DownloadMarketplaceShippingDocumentJob / PromoteBookingToOrderJob
└── Shopee logistics shipping document APIs

marketplace:sync-orders
└── SyncOrdersCommand
└── MarketplaceSyncService::syncOrdersWindowed()/syncOrders()
└── Shopee order APIs

marketplace:sync-ads
└── SyncAdsCommand
└── ShopeeAdsSyncJob / ShopeeAdsSyncService
└── Shopee Ads APIs

webhooks/shopee
└── WebhookController@shopee
└── ProcessShopeeWebhookJob
└── Downstream sync/service calls
```

## H. Rekomendasi Refactor

- Pindahkan semua request OpenAI dari controller ke service tunggal yang dipakai bersama.
- Tambahkan `RateLimiter` atau throttle per store untuk semua flow yang masih langsung memanggil API dari controller.
- Gunakan retry dengan exponential backoff untuk OpenAI, RajaOngkir, dan TikTok Shop.
- Tambahkan pembacaan `Retry-After` untuk provider yang mengirim 429.
- Pindahkan flow berat dan berulang ke queue:
  - bulk shipping document
  - sync ads
  - sync orders / returns / settlements skala besar
  - re-sync chat histories
- Tambahkan idempotency key atau dedupe cache untuk action yang bisa dipicu ulang:
  - webhook Shopee
  - print label bulk
  - promote booking to order
  - sync ads daily / backfill
- Pertimbangkan `Saloon` atau client wrapper khusus untuk:
  - Shopee
  - TikTok Shop
  - OpenAI
  - RajaOngkir
- Tambahkan circuit breaker untuk provider yang sering menolak:
  - Shopee Ads
  - OpenAI
  - TikTok Shop
- Hapus fallback log OTP mentah; kalau gagal kirim, log hanya masked phone dan event id.
- Ganti `http://ip-api.com` ke HTTPS atau proxy server-side.
- Hindari `Http::retry()` generik tanpa treatment 429 khusus pada OpenAI.

## I. Daftar File Prioritas

Urutan dari risiko tertinggi ke terendah:

1. `app/Services/WaNotificationService.php`
2. `app/Http/Middleware/TrackStorefrontVisitor.php`
3. `app/Http/Controllers/Inventory/WarehouseIntelligenceController.php`
4. `app/Services/OpenAI/OpenAiService.php`
5. `app/Http/Controllers/Storefront/CartController.php`
6. `app/Services/Channels/Shopee/ShopeeChannel.php`
7. `app/Services/MarketplaceSyncService.php`
8. `app/Services/Marketplace/Ads/ShopeeAdsApiService.php`
9. `app/Services/Marketplace/Ads/ShopeeAdsSyncService.php`
10. `app/Http/Controllers/MarketplaceController.php`
11. `app/Http/Controllers/MarketplaceLogisticsController.php`
12. `app/Http/Controllers/MarketplaceBookingController.php`
13. `app/Http/Controllers/TikTokShopAuthController.php`
14. `app/Http/Controllers/ShopeeStoreAuthController.php`
15. `app/Http/Controllers/Ai/OpenAiConnectionController.php`
16. `app/Http/Controllers/AiAgentController.php`
17. `app/Http/Controllers/MarketplaceChatController.php`
18. `app/Http/Controllers/MarketplaceProductController.php`
19. `app/Services/MarketplaceChatService.php`
20. `app/Services/MarketplaceProductService.php`

## Daftar File yang Diperiksa

### Routes

- `routes/web.php`
- `routes/api.php`
- `routes/console.php`
- `routes/web/auth.php`
- `routes/web/dashboard.php`
- `routes/web/inventory.php`

### Config

- `config/app.php`
- `config/broadcasting.php`
- `config/dompdf.php`
- `config/mail.php`
- `config/queue.php`
- `config/sanctum.php`
- `config/services.php`
- `config/shopee.php`
- `config/tiktok_shop.php`

### Controllers

- `app/Http/Controllers/ShopeeStoreAuthController.php`
- `app/Http/Controllers/TikTokShopAuthController.php`
- `app/Http/Controllers/Auth/OAuthLoginController.php`
- `app/Http/Controllers/Storefront/OAuthLoginController.php`
- `app/Http/Controllers/Ai/OpenAiConnectionController.php`
- `app/Http/Controllers/AiAgentController.php`
- `app/Http/Controllers/Inventory/WarehouseIntelligenceController.php`
- `app/Http/Controllers/Storefront/CartController.php`
- `app/Http/Controllers/MarketplaceController.php`
- `app/Http/Controllers/MarketplaceBookingController.php`
- `app/Http/Controllers/MarketplaceLogisticsController.php`
- `app/Http/Controllers/MarketplaceProductController.php`
- `app/Http/Controllers/MarketplaceBoostController.php`
- `app/Http/Controllers/MarketplaceChatController.php`
- `app/Http/Controllers/MarketplaceReturnController.php`
- `app/Http/Controllers/Marketplace/MarketplaceOrderController.php`
- `app/Http/Controllers/OmnichannelController.php`
- `app/Http/Controllers/WebhookController.php`

### Services

- `app/Services/Channels/Shopee/ShopeeChannel.php`
- `app/Services/Channels/TikTokShop/TikTokShopChannel.php`
- `app/Services/OpenAI/OpenAiService.php`
- `app/Services/WaNotificationService.php`
- `app/Services/MarketplaceSyncService.php`
- `app/Services/MarketplaceChatService.php`
- `app/Services/MarketplaceProductService.php`
- `app/Services/MarketplaceBoostService.php`
- `app/Services/Marketplace/Ads/ShopeeAdsApiService.php`
- `app/Services/Marketplace/Ads/ShopeeAdsSyncService.php`
- `app/Services/Marketplace/Ads/AdsActionService.php`
- `app/Services/OmnichannelSyncService.php`

### Jobs / Commands

- `app/Jobs/ProcessShopeeWebhookJob.php`
- `app/Jobs/PromoteBookingToOrderJob.php`
- `app/Jobs/DownloadMarketplaceShippingDocumentJob.php`
- `app/Jobs/SyncMarketplaceBookings.php`
- `app/Jobs/SyncMarketplaceReturns.php`
- `app/Jobs/SyncAdCampaignsJob.php`
- `app/Jobs/Marketplace/SyncMarketplaceProductsJob.php`
- `app/Jobs/ShopeeAdsSyncJob.php`
- `app/Console/Commands/Marketplace/SyncOrdersCommand.php`
- `app/Console/Commands/Marketplace/SyncSettlementsCommand.php`
- `app/Console/Commands/Marketplace/SyncAdsCommand.php`
- `app/Console/Commands/Marketplace/SyncAdsDailyCommand.php`
- `app/Console/Commands/Marketplace/SyncChatsCommand.php`
- `app/Console/Commands/Marketplace/SnapshotProductsCommand.php`
- `app/Console/Commands/Marketplace/RunBoostsCommand.php`
- `app/Console/Commands/Marketplace/AdsAuditGapsCommand.php`
- `app/Console/Commands/MarketplaceSyncFinanceCommand.php`
- `app/Console/Commands/SyncHistoricalOrders.php`
- `app/Console/Commands/SyncHistoricalReturns.php`
- `app/Console/Commands/SyncMarketplaceBookingsCommand.php`
- `app/Console/Commands/SyncMarketplaceReturnsCommand.php`
- `app/Console/Commands/Marketplace/FixShopeeAwbCommand.php`

### Views / JS

- `resources/js/app.js`
- `resources/js/bootstrap.js`
- `resources/js/echo.js`
- `resources/views/layouts/app.blade.php`
- `resources/views/layouts/app.bladv2.php`
- `resources/views/layouts/print.blade.php`
- `resources/views/storefront/layouts/app.blade.php`
- `resources/views/storefront/layouts/checkout.blade.php`
- `resources/views/storefront/layouts/auth.blade.php`
- `resources/views/storefront/checkout.blade.php`
- `resources/views/storefront/masuk.blade.php`
- `resources/views/auth/login.blade.php`
- `resources/views/admin/crm/visitors.blade.php`
- `resources/views/admin/crm/segments/show.blade.php`
- `resources/views/admin/crm/prospects.blade.php`
- `resources/views/admin/crm/customers/show.blade.php`
- `resources/views/admin/website/settings.blade.php`
- `resources/views/marketplace/ads_dashboard.blade.php`
- `resources/views/marketplace/chat.blade.php`
- `resources/views/marketplace/products.blade.php`
- `resources/views/marketplace/shopee_api_logs.blade.php`
- `resources/views/marketplace/settings.blade.php`
- `resources/views/marketplace/orders.blade.php`
- `resources/views/marketplace/cache-monitor.blade.php`
- `resources/views/marketplace/documents/fallback_awb.blade.php`
- `resources/views/inventory/intelligence/index.blade.php`
- `resources/views/inventory/intelligence/slip.blade.php`
- `resources/views/inventory/warehouse_intelligence/index.blade.php`
- `resources/views/inventory/warehouse_intelligence/partials/_insights.blade.php`
- `resources/views/inventory/warehouse_intelligence/partials/_prd.blade.php`
- `resources/views/inventory/warehouse_intelligence/partials/_rts.blade.php`
- `resources/views/inventory/barcodes/print.blade.php`
- `resources/views/production/qc/cutting_edit.blade.php`
- `resources/views/production/dashboard/slip.blade.php`
- `resources/views/purchasing/purchase_orders/print_dot_matrix.blade.php`
- `resources/views/components/item-suggest.blade.php`
- `resources/views/components/account-suggest.blade.php`
- `resources/views/components/mobile-bottom-nav.blade.php`
- `resources/views/dashboard/index.blade.php`
- `resources/views/dashboard/partials/owner.blade.php`
- `resources/views/imports/marketplace/index.blade.php`
- `resources/views/sales/shipment_returns/edit.blade.php`
- `resources/views/sales/shipments/index.blade.php`

### Database / Bootstrap / Tests

- `database/seeders/StorefrontProductionProductSeeder.php`
- `database/seeders/StorefrontSettingSeeder.php`
- `database/seeders/StorefrontProductCatalogSeeder.php`
- `bootstrap/app.php`
- `tests/Feature/AdsModuleTest.php`
- `tests/Feature/MarketplacePromotionsTest.php`
- `tests/Feature/MarketplacePromotionsSummaryTest.php`
- `tests/Feature/ShopeeStoreAuthControllerTest.php`
- `tests/Feature/MarketplaceSettlementSyncControllerTest.php`
- `tests/Feature/Services/MarketplaceSyncServiceSettlementTest.php`
- `tests/Feature/Console/MarketplaceSyncOrdersCommandTest.php`
- `tests/Feature/Console/MarketplaceSyncSettlementsCommandTest.php`
- `tests/Feature/MarketplaceChatAuditTest.php`
- `tests/Unit/Services/Channels/Shopee/ShopeeChannelMetaTest.php`
- `tests/Unit/Services/Channels/Shopee/ShopeeDiscountApiTest.php`

### File lain yang ikut terbaca saat audit

- `app/helpers.php`
- `app/Http/Controllers/Marketplace/AdsDashboardController.php`
- `app/Http/Controllers/Inventory/InventoryIntelligenceController.php`
- `app/Http/Controllers/AiHubController.php`
- `app/Http/Controllers/WebhookController.php`
- `app/Http/Controllers/MarketplaceController.php`
- `app/Services/Marketplace/Ads/AdsAnalyticsService.php`
- `app/Services/Marketplace/Ads/ShopeeProductAdsSearchTermImporter.php`
- `app/Services/Channels/ChannelManager.php`
- `app/Models/OpenAiConnection.php`
- `app/Models/Store.php`

## Catatan Akhir

- Mayoritas traffic eksternal proyek ini terkonsentrasi di ekosistem Shopee.
- Jalur paling riskan adalah: OpenAI langsung dari controller, cURL cleartext ke ip-api, dan fallback log OTP di Fonnte service.
- Jalur paling matang dari sisi ketahanan adalah: `ShopeeChannel`, `ShopeeAdsApiService`, dan `MarketplaceSyncService` untuk settlement.
