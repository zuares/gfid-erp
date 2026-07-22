# Audit Lanjutan & Rancangan — `marketplace:sync-settlements` (Fase 1)

Tanggal: 22 Juli 2026
Status: **Mode audit & perencanaan saja.** Tidak ada kode, migration, database, scheduler, `.env`, atau jurnal yang diubah. Semua query di bawah bersifat **read-only** (`SELECT` via Python `sqlite3`, tidak ada `UPDATE`/`INSERT`/`DELETE`).

Database yang diaudit: `database/database.sqlite` (sesuai `DB_DATABASE` di `.env`, driver `sqlite`, Laravel `12.39.0`).

---

## 1. Audit Data Settlement — Duplikat & Konsistensi `store_id`

**Hasil paling penting: `marketplace_order_settlements` saat ini punya 0 baris.** Tidak pernah ada satu pun sinkronisasi settlement yang berhasil tersimpan.

Bukti pendukung — tabel `marketplace_sync_logs` (total 8.855 baris log) **tidak punya satu pun entri** dengan `action` mengandung kata "settlement":

```
sync_orders           5275
sync_specific_order   3574
sync_ad_campaigns        3
sync_orders_by_sn        2
sync_orders_detail       1
sync_settlements         0   ← tidak pernah tercatat
```

Konsekuensi untuk 3 query audit yang Anda minta:

| Query audit | Hasil |
|---|---|
| Duplicate `channel_order_id` di `marketplace_order_settlements` | **0** (tabel kosong) |
| Order yang settlement-nya salah `store_id` dibanding order aslinya | **0** (tabel kosong) — tidak ada data untuk dibandingkan |
| Order berada di lebih dari satu `store_id` (di `marketplace_orders`) | **0** — dicek langsung: `GROUP BY channel_order_id HAVING COUNT(*)>1` di `marketplace_orders` (1.526 baris) menghasilkan 0 grup duplikat, dan tidak ada `channel_order_id` NULL/kosong |

**Implikasi bagus untuk keputusan Anda di poin 3 (unique constraint)**: karena tabel `marketplace_order_settlements` masih benar-benar kosong, mengubah `unique('channel_order_id')` → `unique(['store_id','channel_order_id'])` **tidak berisiko konflik data lama sama sekali** — belum ada baris yang bisa bentrok. Ini justru waktu paling aman untuk migration tersebut, sebelum backfill 1.423 order (lihat bagian 6) mengisi tabel. Saya tetap **belum menjalankan migration apa pun** — menunggu persetujuan eksplisit Anda sesuai instruksi.

---

## 2. Audit Konsistensi `store_id` — Skema Toko Ganda (Bagian 8 keputusan Anda)

Query terhadap `sqlite_master` (definisi FK asli) + data nyata:

### 2.1 Tabel mana memakai `marketplace_stores` vs `stores`

| Skema | Tabel yang memakainya (FK) |
|---|---|
| **`marketplace_stores`** (lama) | `marketplace_stores` sendiri, **`marketplace_orders`** — **hanya ini** |
| **`stores`** (baru) | `sales_invoices`, `shipment_returns`, `mp_incomes`, **`marketplace_order_settlements`**, `marketplace_sync_logs`, `marketplace_ad_campaigns`, `marketplace_returns`, `marketplace_conversations`, `marketplace_chat_messages`, `marketplace_products`, `marketplace_ads_dailies`, `marketplace_ads_balance_logs`, `marketplace_product_dailies`, `marketplace_boost_schedules`, `marketplace_boost_pool`, `marketplace_boost_logs`, `marketplace_bookings` — **17 tabel** |

`marketplace_orders` adalah **satu-satunya** tabel yang masih terikat FK ke skema lama. Semua tabel finansial/operasional yang dibuat belakangan (termasuk `marketplace_order_settlements` yang jadi sumber utama kita) sudah 100% memakai `stores`.

### 2.2 Data nyata — mana yang aktif dipakai

```
marketplace_stores : 0 baris   ← kosong total
marketplace_channels: 0 baris  ← kosong total
stores              : 5 baris  ← terisi, ini yang aktif
channels            : 4 baris  ← terisi, ini yang aktif
```

`marketplace_orders.store_id` yang benar-benar ada di data (1.526 baris) berisi nilai `4` dan `5` — yang **cocok dengan `stores.id`**, bukan `marketplace_stores.id` (karena `marketplace_stores` kosong, ID 4/5 di sana tidak mungkin ada). Artinya: FK constraint di migration `marketplace_orders.store_id → marketplace_stores` **secara faktual salah/tidak terpakai** — data sebenarnya selalu merujuk ke `stores`. Ini konsisten dengan temuan audit sebelumnya bahwa kode sync harus mematikan `PRAGMA foreign_keys=OFF` sebagai workaround (SQLite tidak menegakkan FK secara default kecuali PRAGMA diaktifkan, jadi inkonsistensi ini "lolos" tanpa error selama ini).

Detail 2 toko Shopee yang aktif (`is_active=1`, `status=active`, channel `shopee`):

| `stores.id` | `code` | `name` | `external_shop_id` | `channel_id` |
|---|---|---|---|---|
| 4 | `shopee_476637262` | Insight Corps | 476637262 | 4 (`channels.code='shopee'`) |
| 5 | `shopee_1076816997` | Greatfit.id | 1076816997 | 4 |

Ada juga `stores.id=1` (`SHP-INSIGHT`) berstatus `is_active=0` — kemungkinan toko lama/nonaktif dari skema sebelumnya, bukan dipakai untuk sync aktif saat ini.

### 2.3 Dampak jika tabel finansial baru memakai skema yang salah

- Kalau tabel histori/settlement baru (Bagian 5 & 9) di-FK ke `marketplace_stores`: **akan langsung tidak konsisten dengan data nyata**, karena `marketplace_stores` kosong — sama persis dengan bug yang sudah ada di `marketplace_orders` sekarang, hanya diperparah (menambah tabel keempat/dst yang cacat).
- Kalau tetap di-FK ke `stores` (mengikuti 17 tabel lain yang sudah benar): **konsisten dengan seluruh sistem yang sudah berjalan**, termasuk `marketplace_order_settlements` yang sudah jadi sumber utama kita.

**Saya belum melakukan migrasi FK apa pun.** Tapi berdasarkan bukti di atas, rekomendasi murni-audit saya: tabel baru (histori settlement, dll) sebaiknya ikut pola `stores` seperti 17 tabel lain — bukan karena saya menganggap keputusan sudah final, tapi karena itu satu-satunya skema yang benar-benar berisi data. Keputusan final tetap di tangan Anda.

---

## 3. Audit Scheduler yang Benar-Benar Aktif — Laravel 12

Ini temuan paling signifikan dari audit lanjutan ini, dan sudah saya verifikasi langsung ke source code framework yang ter-install (`vendor/laravel/framework` v12.39.0), bukan asumsi.

### 3.1 Fakta yang terverifikasi

Project ini pakai struktur bootstrap baru Laravel 11+ (`bootstrap/app.php` dengan `Application::configure()`), **bukan** struktur lama yang mengandalkan `App\Console\Kernel`. Buktinya:

1. `vendor/laravel/framework/.../ApplicationBuilder.php` baris 58-71 (`withKernels()`) **selalu** mem-bind `Illuminate\Contracts\Console\Kernel::class` → `Illuminate\Foundation\Console\Kernel::class` (kelas bawaan framework), **tanpa syarat**, setiap kali aplikasi boot.
2. Untuk memakai `App\Console\Kernel` custom, project harus me-rebind ini secara eksplisit (biasanya di provider, lewat `$this->app->singleton(\Illuminate\Contracts\Console\Kernel::class, \App\Console\Kernel::class)`). Saya grep seluruh `app/Providers`, `bootstrap/`, `config/` — **tidak ada satu pun binding seperti itu**.
3. Kelas dasar framework (`Illuminate\Foundation\Console\Kernel`) punya method `schedule()` bawaan yang **kosong** (baris 276-279: `protected function schedule(Schedule $schedule) { // }`).

**Kesimpulan: `app/Console/Kernel.php` di project ini adalah file yang ADA di disk tapi TIDAK PERNAH DIPANGGIL oleh Laravel.** Semua isi method `schedule()`-nya adalah dead code:

```php
// app/Console/Kernel.php — TIDAK PERNAH DIEKSEKUSI
$schedule->command('inventory:audit-allocated')->dailyAt('01:00');                 // ❌ mati
$schedule->call(function () { ... dispatch(SyncMarketplaceReturns) ... })->hourly(); // ❌ mati
$schedule->command('marketplace:sync-orders')->hourly();                            // ❌ mati
$schedule->command('marketplace:sync-bookings')->hourly();                          // ❌ mati
```

### 3.2 Yang benar-benar aktif

Dua sumber yang *sungguh* jalan (dan keduanya bergabung ke satu `Schedule` instance yang sama saat runtime):

**A. `routes/console.php`** — file ini didaftarkan lewat `withRouting(commands: __DIR__.'/../routes/console.php', ...)` di `bootstrap/app.php`, yang menjadikannya `commandRoutePath`. Terverifikasi di `Kernel.php` framework baris 500-504: setiap kali Artisan boot, file ini di-`require` langsung — termasuk saat `schedule:run` dipanggil cron setiap menit. Jadi semua `Schedule::command(...)` di file ini **genuinely aktif**:

```
marketplace:sync-orders              → everyFiveMinutes()   ✅ aktif (satu-satunya sumber order sync)
marketplace:sync-returns             → hourly()              ✅ aktif
marketplace:sync-chats               → everyMinute()         ✅ aktif
marketplace:sync-ads-daily --days=3  → dailyAt('23:30')      ✅ aktif
marketplace:snapshot-products --sync → dailyAt('23:45')      ✅ aktif
marketplace:run-boosts               → everyFiveMinutes()    ✅ aktif
marketplace:cleanup-labels           → dailyAt('01:00')      ✅ aktif
sales:rebuild-daily-item-sales --days=90     → dailyAt('00:05')  ✅ aktif
inventory:recalc-ads-from-daily --days=30    → dailyAt('00:10')  ✅ aktif
storefront:rank-products             → hourly()              ✅ aktif
queue:work --stop-when-empty ...     → everyMinute()         ✅ aktif
```

**B. `bootstrap/app.php` → `withSchedule(function (Schedule $schedule) {...})`** — didaftarkan lewat `Artisan::starting()`, juga genuinely aktif:

```
crm:weekly-summary                              → weeklyOn(1,'08:00')  ✅ aktif
sales:rebuild-daily-item-sales --days=90        → dailyAt('01:00')     ✅ aktif  ⚠️ LIHAT 3.3
inventory:recalc-ads-from-daily --days=30 --only-active=1 → dailyAt('01:10') ✅ aktif  ⚠️ LIHAT 3.3
```

### 3.3 Bug tambahan yang baru ketahuan dari audit ini: duplikasi BUKAN di tempat yang saya duga sebelumnya

Di laporan audit pertama saya sempat menandai `marketplace:sync-orders` di `Kernel.php` (hourly) vs `routes/console.php` (5 menit) sebagai "duplikasi scheduler". **Koreksi: itu bukan duplikasi nyata** — karena `Kernel.php` mati total, `marketplace:sync-orders` sebenarnya **hanya** dijadwalkan sekali (lewat `routes/console.php`, 5 menit). Mohon maaf atas ketidakakuratan itu di laporan sebelumnya — audit lanjutan dengan pembacaan source code framework ini mengoreksinya.

**Duplikasi yang benar-benar nyata** (dua-duanya sama-sama aktif, terverifikasi bergabung di runtime yang sama):

| Command | Di `routes/console.php` | Di `bootstrap/app.php` |
|---|---|---|
| `sales:rebuild-daily-item-sales --days=90` | `dailyAt('00:05')` | `dailyAt('01:00')` |
| `inventory:recalc-ads-from-daily` | `--days=30`, `dailyAt('00:10')` | `--days=30 --only-active=1`, `dailyAt('01:10')` |

Command yang sama jalan **dua kali sehari** dengan parameter berbeda (yang kedua pakai `--only-active=1`, yang pertama tidak). Ini di luar cakupan Shopee finance, tapi karena Anda minta "audit dan hilangkan duplikasi penjadwalan antara `Kernel.php` dan `routes/console.php`" — duplikasi sesungguhnya ada di **`routes/console.php` vs `bootstrap/app.php`**, bukan `Kernel.php` (yang memang harus diaudit lebih dulu statusnya sebelum bisa dibilang "duplikat", dan hasilnya dead code, bukan duplikat).

### 3.4 Dampak nyata: `marketplace:sync-bookings` tidak punya scheduler aktif sama sekali

Karena hanya terdaftar di `Kernel.php` (mati) dan tidak ada di `routes/console.php` maupun `bootstrap/app.php`, **"Pesanan Kilat" (booking) tidak pernah disinkronkan otomatis** — ini gap fungsional nyata, bukan cuma soal duplikasi. Di luar cakupan finance juga, tapi penting untuk Anda tahu karena berpotensi berdampak ke data pesanan yang hilang dari sync otomatis.

### 3.5 Rekomendasi lokasi scheduler tunggal (menunggu persetujuan — belum dieksekusi)

Karena `app/Console/Kernel.php` terbukti **sudah tidak berjalan sejak project migrasi ke Laravel 11/12 bootstrap style**, dan Laravel 12 secara resmi tidak lagi memakai `App\Console\Kernel` untuk scheduling, saya rekomendasikan:

- **Jadikan `routes/console.php` sebagai satu-satunya lokasi scheduler** (ini juga pola resmi Laravel 11+ untuk aplikasi yang tidak butuh banyak abstraksi kernel), **atau**
- Pindahkan semuanya ke closure `withSchedule()` di `bootstrap/app.php`.

Yang penting **hanya satu tempat**, bukan tersebar di tiga file. `app/Console/Kernel.php` sendiri **tidak saya sarankan dihapus sembarangan** (sesuai instruksi Anda) — cukup ditandai jelas dengan komentar bahwa file ini tidak aktif, atau isinya dipindahkan ke lokasi yang aktif kalau memang closure `dispatch(SyncMarketplaceReturns)` di dalamnya masih dibutuhkan (perlu Anda konfirmasi apakah command `marketplace:sync-returns` yang sudah aktif di `routes/console.php` sudah menggantikan fungsi closure itu sepenuhnya, atau ada logika berbeda — closure lama pakai lookback 15 hari, saya belum bandingkan detail dengan `SyncMarketplaceReturnsCommand`).

---

## 4. Audit `ensureFreshToken()` dari `getEscrowDetail()`

**Terkonfirmasi: sudah benar, tidak ada gap.**

`getEscrowDetail()` (`ShopeeChannel.php:216-224`) memanggil `$this->get($store, '/api/v2/payment/get_escrow_detail', [...])`. Helper `get()` (baris 49-66) **sama untuk semua endpoint GET** di channel ini:

```php
protected function get(Store $store, string $path, array $params = []): array
{
    $this->ensureFreshToken($store); // proaktif: refresh sebelum kedaluwarsa
    $result = $this->doGet($store, $path, $params);
    // ... kalau response mengandung error_auth / expired / access_token →
    // refreshToken() sekali, lalu retry doGet() sekali lagi
    return $result;
}
```

Jadi `getEscrowDetail()` otomatis dapat dua lapis perlindungan token: **proaktif** (`ensureFreshToken()` sebelum request, refresh kalau sisa hidup token ≤ 2 menit) dan **reaktif** (retry sekali kalau API tetap menjawab error auth). Tidak perlu perubahan apa pun di sini untuk `sync-settlements` — cukup pakai `getEscrowDetail()` yang sudah ada, tidak perlu mem-bypass helper `get()`.

---

## 5. Audit Struktur `raw_json` Nyata dari Settlement Existing

**Tidak bisa diaudit — datanya tidak ada.** Karena `marketplace_order_settlements` masih 0 baris (lihat Bagian 1) dan tidak pernah ada log `sync_settlements` (Bagian 1), tidak ada satu pun contoh `raw_json` nyata yang tersimpan di database ini untuk saya periksa strukturnya.

Sumber terbaik yang tersedia untuk desain field saat ini hanyalah **mapping yang sudah ditulis di kode** (`MarketplaceSyncService.php:273-305`), yang mengasumsikan (dengan fallback `??`) field-field berikut mungkin ada di response `get_escrow_detail`: `buyer_payment_amount`/`buyer_paid_amount`, `commission_fee`, `service_fee`/`credit_card_promotion`, `transaction_fee`, `seller_voucher_rebate`/`seller_voucher`, `seller_absorbed_coin_discount`/`seller_coin_cash_back`, `actual_shipping_fee`/`estimated_shipping_fee`, `shopee_shipping_rebate`/`shipping_fee_rebate`, `reverse_shipping_fee`, `activity_fee`/`ams_commission_fee`, `drc_adjustable_refund`/`seller_return_refund_amount`, `escrow_tax`, `final_income`/`escrow_amount`, `escrow_release_time`, `settlement_time`.

Field ganda dengan fallback (`a ?? b`) menunjukkan penulis kode dulu **tidak yakin 100%** nama field yang benar dan menjaga-jaga dua kemungkinan — ini sinyal bahwa struktur asli belum sepenuhnya diverifikasi bahkan oleh implementasi awal.

**Rekomendasi**: sebelum finalisasi tabel histori (Bagian 9), jalankan **satu kali** panggilan manual `syncSettlements()` untuk satu store dengan `--limit=1` (lewat command baru di Bagian 7, begitu disetujui) khusus untuk menangkap satu `raw_json` asli — lalu kita periksa field mana yang benar-benar terisi vs `null`/tidak ada, sebelum mengunci desain kolom histori.

---

## 6. Audit Jumlah Order Eligible Tanpa Settlement

Kondisi eligible mengikuti filter yang sudah ada di kode (`MarketplaceSyncService::syncSettlements()`): `order_status IN ('COMPLETED','SHIPPED','TO_CONFIRM_RECEIVE')`.

```
Distribusi order_status (marketplace_orders, 1.526 baris total):
  COMPLETED            1259
  SHIPPED               125
  CANCELLED              98
  TO_CONFIRM_RECEIVE     39
  UNPAID                  2
  READY_TO_HANDOVER       2
  TO_RETURN               1

Eligible (COMPLETED+SHIPPED+TO_CONFIRM_RECEIVE): 1.423 order
Sudah punya settlement: 0
→ Backlog awal yang perlu di-backfill: 1.423 order
```

Distribusi per toko: `store_id=4` (Insight Corps) 1.345 order, `store_id=5` (Greatfit.id) 181 order — total 1.526, sejalan dengan hanya 2 toko Shopee yang aktif di sistem.

Dengan `limit(200)` per pemanggilan yang sudah ada di kode, backfill 1.423 order butuh **± 8 kali pemanggilan batch** (atau otomatis lewat opsi `--all`, lihat Bagian 7) — di luar rate limit API yang perlu diperhitungkan (lihat Bagian 7.3).

---

## 7. Rancangan Command `marketplace:sync-settlements`

Mengikuti pola persis `SyncOrdersCommand` yang sudah ada (`app/Console/Commands/Marketplace/SyncOrdersCommand.php`) — lock per toko, cek `connection_status`, log terstruktur, ringkasan di akhir.

### 7.1 Signature

```
marketplace:sync-settlements
    {--store= : ID toko spesifik (stores.id)}
    {--order= : channel_order_id spesifik, sinkronkan hanya order ini}
    {--from= : Tanggal mulai (Y-m-d), difilter ke ordered_at}
    {--to= : Tanggal akhir (Y-m-d)}
    {--limit=200 : Maks order per batch}
    {--resync : Ambil ulang meski sudah ada settlement tersimpan}
    {--all : Ulangi batch sampai tidak ada order tersisa}
```

### 7.2 Perilaku (proposed diff — belum dibuat filenya)

```php
// app/Console/Commands/Marketplace/SyncSettlementsCommand.php  (BARU — belum dibuat)
namespace App\Console\Commands\Marketplace;

class SyncSettlementsCommand extends Command
{
    protected $signature = 'marketplace:sync-settlements
        {--store=} {--order=} {--from=} {--to=} {--limit=200} {--resync} {--all}';

    protected $description = 'Sinkronisasi settlement/escrow Shopee (tanpa membuat jurnal)';

    public function handle(MarketplaceSyncService $syncService): int
    {
        // 1. Resolve daftar toko (sama seperti SyncOrdersCommand: status=active, is_active=true, channel=shopee)
        //    kalau --store diisi, filter ke satu toko itu.

        foreach ($stores as $store) {
            // 2. Lock per toko — pola sama seperti SyncOrdersCommand:
            $lock = Cache::lock("sync_settlements_store_{$store->id}", 240);
            if (! $lock->get()) {
                $this->warn("Dilewati: toko sedang disinkronkan proses lain");
                continue;
            }

            try {
                do {
                    $result = $syncService->syncSettlements(
                        store: $store,
                        timeFrom: $from ? Carbon::parse($from)->timestamp : null,
                        timeTo:   $to   ? Carbon::parse($to)->timestamp   : null,
                        orderSn:  $this->option('order'),      // BARU: filter satu order
                        resync:   $this->option('resync'),     // BARU: bypass whereDoesntHave('settlement')
                        limit:    (int) $this->option('limit'),
                    );
                    $totalSynced += $result['synced'];
                    // 3. --all: ulangi selama masih ada order eligible tersisa (result['synced'] + result['skipped'] > 0
                    //    DAN belum mencapai iterasi maksimum pengaman, mis. 50x, supaya tidak infinite loop kalau API error terus)
                } while ($this->option('all') && $result['synced'] > 0 && $iterations++ < 50);

                $this->table(['Toko','Ditemukan','Synced','Skipped','Errors'], [[
                    $store->name, $result['found'] ?? '-', $result['synced'], $result['skipped'], $result['errors'],
                ]]);
            } finally {
                $lock->release();
            }
        }
        // 4. Ringkasan total di akhir (semua toko) — format sama seperti SyncOrdersCommand.
    }
}
```

### 7.3 Perubahan yang dibutuhkan di `MarketplaceSyncService::syncSettlements()` (proposed — belum diterapkan)

Signature saat ini: `syncSettlements(Store $store, ?int $timeFrom = null, ?int $timeTo = null): array`

Perlu ditambah 3 parameter baru (backward-compatible, semua opsional dengan default):

```php
public function syncSettlements(
    Store $store,
    ?int $timeFrom = null,
    ?int $timeTo = null,
    ?string $orderSn = null,   // BARU — kalau diisi, query diarahkan ke satu order (whereIn atau where channel_order_id=)
    bool $resync = false,      // BARU — kalau true, HAPUS klausa whereDoesntHave('settlement')
    int $limit = 200,          // BARU — ganti limit(200) hardcoded jadi parameter
): array
```

Perubahan pada query di dalamnya:
```php
$query = MarketplaceOrder::where('store_id', $store->id)
    ->whereIn('order_status', ['COMPLETED', 'SHIPPED', 'TO_CONFIRM_RECEIVE']);

if (! $resync) {
    $query->whereDoesntHave('settlement');
}
if ($orderSn) {
    $query->where('channel_order_id', $orderSn);
}
// ...filter timeFrom/timeTo seperti sekarang...
$orders = $query->limit($limit)->get();
```

**Tidak ada perubahan pada bagian yang membuat jurnal** — karena memang belum ada bagian itu di service ini (sesuai keputusan Anda, fase ini tidak membuat jurnal sama sekali).

### 7.4 Retry/backoff & rate limit (belum ada di kode sekarang — direkomendasikan ditambahkan)

`syncSettlements()` saat ini tidak retry sama sekali untuk error transient (timeout, 5xx). Untuk backfill 1.423 order, ini berisiko: kalau API Shopee rate-limit di tengah batch, semua order sisanya di batch itu langsung masuk hitungan `errors` tanpa dicoba ulang. Rekomendasi (proposed, bukan sudah diimplementasikan): tambah retry 2x dengan backoff singkat (mis. 1 detik, 3 detik) khusus untuk error yang bukan `error_auth` (karena auth sudah ditangani lapis lain) dan bukan error bisnis (order belum eligible) — murni untuk timeout/5xx.

---

## 8. Rancangan Scheduler + Locking untuk Settlement

Mengikuti keputusan Bagian 3 (satu lokasi scheduler). Contoh kalau `routes/console.php` dipilih sebagai lokasi tunggal (proposed, belum ditambahkan):

```php
// routes/console.php — TAMBAHAN (belum diterapkan)

// Settlement: jalan setelah sync-orders (order harus COMPLETED/SHIPPED dulu).
// Jeda 10 menit dari sync-orders (5 menit) supaya order yang baru saja
// berubah status sempat tersimpan dulu sebelum dicari settlement-nya.
Schedule::command('marketplace:sync-settlements')
    ->everyFifteenMinutes()
    ->withoutOverlapping()
    ->runInBackground();
```

**Locking**: dua lapis, konsisten dengan pola project —
1. **Level command** (di dalam handle, per toko): `Cache::lock("sync_settlements_store_{$store->id}", 240)` — sama seperti `SyncOrdersCommand`, mencegah dua proses sync settlement toko yang sama tumpang tindih (baik dari scheduler maupun manual `--resync` yang dijalankan bersamaan).
2. **Level scheduler** (`withoutOverlapping()`): mencegah command yang sama dijalankan dua kali kalau eksekusi sebelumnya belum selesai (mis. backfill besar yang lama).

Tidak menimpa lock yang sudah dipakai `syncOrders()` (`sync_store_{id}`) — pakai key terpisah `sync_settlements_store_{id}` supaya sync order dan sync settlement untuk toko yang sama **bisa berjalan paralel** tanpa saling memblokir (keduanya menyentuh tabel berbeda: `marketplace_orders` vs `marketplace_order_settlements`), tapi tidak saling tumpang tindih dengan dirinya sendiri.

---

## 9. Rancangan Tabel Histori Settlement

Menyetujui pendekatan "snapshot hanya kalau ada perubahan material" — desain final:

```
marketplace_order_settlement_histories   (BARU — belum ada migration)

  id                              bigint, PK
  marketplace_order_settlement_id bigint, FK → marketplace_order_settlements.id, cascadeOnDelete
  store_id                        bigint, FK → stores.id  (mengikuti pola 17 tabel lain, LIHAT Bagian 2 — menunggu persetujuan Anda)
  channel_order_id                varchar(100), index (denormalisasi, untuk query cepat tanpa join)
  version                         unsignedInteger, default 1   -- increment per settlement_id
  changed_fields                  json        -- array nama kolom yang berubah, mis. ["commission_fee","final_income"]
  previous_values                 json        -- {"commission_fee": 1000, "final_income": 45000}
  new_values                      json        -- {"commission_fee": 1200, "final_income": 44800}
  raw_response                    json        -- snapshot penuh raw_json dari sync yang memicu perubahan ini
  api_updated_at                  timestamp nullable  -- dari escrow_release_time/settlement_time versi baru
  synced_at                       timestamp   -- kapan sync ini dijalankan
  created_at                      timestamp

  unique(['marketplace_order_settlement_id', 'version'])
  index(['channel_order_id'])
  index(['store_id', 'created_at'])
```

### 9.1 Logika penulisan (di dalam `syncSettlements()`, sebelum `updateOrCreate`)

```php
$existing = MarketplaceOrderSettlement::where('channel_order_id', $order->channel_order_id)->first();

if ($existing) {
    $materialFields = [
        'commission_fee', 'service_fee', 'transaction_fee', 'seller_voucher',
        'shipping_fee_subsidy', /* + actual_shipping_fee, reverse_shipping_fee jika dianggap material */
        'escrow_tax', 'final_income', 'settlement_time',
        // + kolom status settlement kalau nanti ditambahkan
    ];

    $changed = [];
    foreach ($materialFields as $field) {
        if ((string) $existing->{$field} !== (string) $newValues[$field]) {
            $changed[$field] = ['old' => $existing->{$field}, 'new' => $newValues[$field]];
        }
    }

    if (! empty($changed)) {
        MarketplaceOrderSettlementHistory::create([
            'marketplace_order_settlement_id' => $existing->id,
            'store_id'          => $store->id,
            'channel_order_id'  => $order->channel_order_id,
            'version'           => $existing->histories()->max('version') + 1 ?? 1,
            'changed_fields'    => array_keys($changed),
            'previous_values'   => array_map(fn($c) => $c['old'], $changed),
            'new_values'        => array_map(fn($c) => $c['new'], $changed),
            'raw_response'      => $income, // raw_json baru
            'api_updated_at'    => $settlementTime,
            'synced_at'         => now(),
        ]);
    }
    // kalau $changed kosong → TIDAK menulis histori sama sekali (sesuai keputusan Anda)
}
```

### 9.2 Estimasi dampak

- **Storage**: karena hanya ditulis saat ada perubahan material (bukan setiap sync), dan settlement pada dasarnya final setelah dana cair (jarang berubah kecuali ada refund pasca-settlement), volume histori diperkirakan **jauh lebih kecil** dari volume settlement itu sendiri — mayoritas order kemungkinan tidak akan pernah punya baris histori sama sekali.
- **Beban tulis per sync**: tambahan 1 query `SELECT` (ambil existing) + kadang 1 `INSERT` histori per order yang nilainya berubah — dampak performa minor untuk volume ~1.400 order.
- **Ketergantungan**: butuh relasi baru `MarketplaceOrderSettlement::histories()` (hasMany) — perubahan model, bukan migration data.
- **Risiko**: definisi "material" saat ini manual (daftar field di atas) — kalau Shopee menambah field baru yang penting tapi lupa dimasukkan ke `$materialFields`, perubahan pada field itu tidak akan tercatat di histori (tapi tetap ter-update di baris utama). Perlu direview ulang begitu field raw_json asli sudah diverifikasi (Bagian 5).

**Belum ada migration yang dibuat untuk tabel ini** — menunggu persetujuan Anda, termasuk keputusan Bagian 2 soal `stores` vs `marketplace_stores`.

---

## 10. Rancangan Laporan Rekonsiliasi 3 Sumber Finansial

### 10.1 Kondisi data saat ini

Ketiga sumber **sama-sama kosong** di database ini saat ini:

```
marketplace_order_settlements : 0 baris
mp_incomes                    : 0 baris
sales_invoices                : 0 baris
```

Jadi laporan di bawah ini adalah **rancangan query + format**, bukan hasil rekonsiliasi nyata — belum ada data untuk direkonsiliasi. Baru bisa dijalankan dan menghasilkan angka setelah: (a) backfill settlement (Bagian 6-7) berjalan, dan (b) minimal satu siklus import `mp_incomes`/`sales_invoices` juga tersedia untuk periode yang sama.

### 10.2 Rancangan query (read-only, akan jadi Artisan command atau halaman report — belum diimplementasikan)

```sql
-- Kunci penyatuan: store_id + channel_order_id (Shopee order_sn)
-- mp_incomes pakai kolom platform_order_id, sales_invoices pakai channel_order_no
SELECT
    s.name                              AS store,
    mos.channel_order_id                AS order_number,
    mos.final_income                    AS settlement_final_income,
    mi.net_payout_actual                AS income_net_income,
    si.<kolom_nilai_marketplace_terkait> AS sales_invoice_value,  -- perlu konfirmasi nama kolom pastinya di sales_invoices
    (mos.final_income - mi.net_payout_actual)              AS selisih_settlement_vs_income,
    (mos.final_income - si.<kolom>)                         AS selisih_settlement_vs_invoice,
    CASE
        WHEN mos.final_income IS NULL OR mi.net_payout_actual IS NULL THEN 'DATA TIDAK LENGKAP'
        WHEN ABS(mos.final_income - mi.net_payout_actual) < 1 THEN 'COCOK'
        ELSE 'BERBEDA'
    END                                  AS status_settlement_vs_income,
    mos.synced_at                       AS settlement_last_synced,
    mi.updated_at                       AS income_last_imported,
    si.updated_at                       AS invoice_last_imported
FROM marketplace_order_settlements mos
JOIN stores s ON s.id = mos.store_id
LEFT JOIN mp_incomes mi
    ON mi.store_id = mos.store_id AND mi.platform_order_id = mos.channel_order_id AND mi.channel = 'shopee'
LEFT JOIN sales_invoices si
    ON si.store_id = mos.store_id AND si.channel_order_no = mos.channel_order_id  -- perlu verifikasi nama kolom
ORDER BY status_settlement_vs_income DESC, s.name, mos.channel_order_id;
```

Kolom output sesuai yang Anda minta: store, order number, nilai masing-masing sumber, selisih nominal, status cocok/berbeda, tanggal sinkronisasi/import terakhir masing-masing sumber.

**Catatan verifikasi yang masih perlu**: saya belum mengonfirmasi nama kolom pasti di `sales_invoices` yang merepresentasikan "nilai marketplace terkait" — tabel ini punya banyak migration tambahan (`add_marketplace_fields_to_sales_invoices_table` beberapa kali) yang perlu saya baca detail kolomnya sebelum query final dibuat. Ini bisa saya lanjutkan sebagai audit tambahan kalau diperlukan sebelum command report dibuat.

### 10.3 Bentuk penyajian

Karena Anda tidak menyebut format (halaman web/command/export), saya usulkan opsi: **Artisan command** (`marketplace:reconcile-finance --store= --from= --to= --format=table|csv`) — konsisten dengan pola command-based tooling yang sudah dipakai di project (banyak command reconcile/audit lain sudah ada, mis. `MarketplaceReconcileCommand.php` yang **sudah ada di project** — perlu saya baca dulu isinya, kemungkinan bisa diperluas alih-alih membuat command baru dari nol). Ini saya tandai sebagai pertanyaan terbuka di Bagian 13.

---

## 11. Rancangan Mapping Akun Accounting (mapping saja — tidak mengubah `AccountSeeder`)

Akun yang **sudah ada** di `AccountSeeder.php` yang relevan: `Piutang Marketplace` (kode terlihat `1302`, dipakai `MarketplacePayoutService`), `Biaya Marketplace` (generik), `Biaya Transport/Ongkir`.

Mapping usulan (kode akun baru bersifat contoh, perlu disesuaikan penomoran asli project — TIDAK ditambahkan ke seeder pada tahap ini):

| Kebutuhan (dari daftar Anda) | Akun existing yang bisa dipakai sementara | Akun baru yang diusulkan (kalau breakdown penuh disetujui nanti) |
|---|---|---|
| Penjualan Marketplace | — (belum ada akun revenue marketplace spesifik, perlu dicek ulang) | `4xxx Penjualan Marketplace - Shopee` |
| Diskon Ditanggung Seller | `Biaya Marketplace` (generik) | `5xxx Diskon Ditanggung Seller` |
| Voucher Ditanggung Seller | `Biaya Marketplace` (generik) | `5xxx Voucher Ditanggung Seller` |
| Komisi Marketplace | `Biaya Marketplace` (generik) | `5xxx Komisi Marketplace` |
| Biaya Layanan Marketplace | `Biaya Marketplace` (generik) | `5xxx Biaya Layanan Marketplace` |
| Biaya Transaksi Marketplace | `Biaya Marketplace` (generik) | `5xxx Biaya Transaksi Marketplace` |
| Komisi Affiliate | — belum ada | `5xxx Komisi Affiliate` |
| Beban Ongkir Seller | `Biaya Transport/Ongkir` (mungkin generik lintas channel, perlu cek scope-nya) | `5xxx Beban Ongkir Seller - Marketplace` |
| Subsidi Ongkir Marketplace | — belum ada (ini kontra-akun/pengurang beban, bukan beban) | `5xxx (contra) Subsidi Ongkir Marketplace` |
| Retur dan Refund Penjualan | — perlu dicek apakah ada akun retur penjualan umum yang bisa dipakai | `4xxx (contra) Retur & Refund Penjualan` |
| Adjustment Marketplace | — belum ada | `5xxx/4xxx Adjustment Marketplace` (bisa debit/kredit tergantung arah) |
| Piutang Marketplace / Escrow | `1302 Piutang Marketplace` — **sudah ada, dipakai `MarketplacePayoutService`** | (pakai yang existing) |
| Bank Penerimaan Marketplace | Akun bank yang sudah ada per rekening (`bank_account_id` di `marketplace_payouts`) | (pakai yang existing) |

**Tidak ada perubahan ke `AccountSeeder.php`.** Ini murni tabel pemetaan untuk didiskusikan — kode akun `5xxx`/`4xxx` di atas hanya notasi placeholder, bukan nomor final.

---

## 12. Rancangan Adjustment Refund Pasca-Settlement (pendekatan saja)

Sesuai prinsip Anda (histori tetap, adjustment terpisah, tidak overwrite):

- Settlement awal di `marketplace_order_settlements` **tidak pernah diubah nilainya untuk refund** — perubahan akan lewat `marketplace_order_settlement_histories` (Bagian 9) HANYA jika Shopee sendiri mengembalikan nilai `final_income` yang berbeda saat di-re-sync (jarang, biasanya settlement final tidak berubah).
- Refund yang terjadi **setelah** settlement (kasus lebih umum) akan dicatat di **tabel adjustment terpisah** (nama tentatif `marketplace_finance_adjustments`, belum dirancang detail kolomnya) — mereferensikan `marketplace_order_settlement_id` asal, dengan `type='refund_post_settlement'`, nominal, dan status (`pending_deduction` / `deducted_from_next_payout` / `written_off`).
- **Implementasi ditunda total** sampai endpoint/field refund pasca-settlement terverifikasi resmi (sesuai Bagian 13) — sesuai instruksi Anda eksplisit di poin 6.5.

Ini baru kerangka pendekatan, bukan rancangan tabel final — akan didetailkan setelah endpoint terverifikasi supaya kolom-kolomnya sesuai field yang benar-benar dikembalikan API, bukan tebakan.

---

## 13. Daftar File & Method yang Akan Diubah (Fase 1 — settlement sync saja, tanpa jurnal)

| File | Status | Perubahan |
|---|---|---|
| `app/Console/Commands/Marketplace/SyncSettlementsCommand.php` | **Baru** | Command lengkap sesuai Bagian 7 |
| `app/Services/MarketplaceSyncService.php` | Ubah | Tambah parameter `orderSn`, `resync`, `limit` ke `syncSettlements()` (Bagian 7.3); opsional tambah retry/backoff (7.4) |
| `routes/console.php` **atau** `bootstrap/app.php` | Ubah | Tambah 1 baris scheduler baru (Bagian 8) — lokasi final menunggu keputusan Bagian 3.5 |
| `app/Console/Kernel.php` | Ubah (anotasi saja) | Tandai non-aktif / audit isi closure `SyncMarketplaceReturns` apakah masih relevan (Bagian 3.5) — **tidak dihapus** |
| `app/Models/MarketplaceOrderSettlement.php` | Ubah | Tambah relasi `histories()` (hasMany) — setelah tabel histori disetujui |
| `app/Models/MarketplaceOrderSettlementHistory.php` | **Baru** | Model untuk tabel histori — setelah migration disetujui |
| `database/migrations/..._create_marketplace_order_settlement_histories_table.php` | **Baru, BELUM DIBUAT** | Sesuai Bagian 9 — menunggu persetujuan skema `stores` vs `marketplace_stores` |
| `database/migrations/..._change_unique_on_marketplace_order_settlements.php` | **Baru, BELUM DIBUAT** | `unique('channel_order_id')` → `unique(['store_id','channel_order_id'])` — aman dieksekusi kapan pun karena tabel masih kosong (Bagian 1), tapi tetap menunggu approval eksplisit Anda |
| `app/Console/Commands/MarketplaceReconcileCommand.php` (existing — perlu dibaca lebih dulu) | Ubah/perluas (opsional) | Kemungkinan bisa diperluas untuk laporan rekonsiliasi 3 sumber (Bagian 10) alih-alih command baru — perlu konfirmasi setelah saya baca isinya |
| `app/Http/Controllers/MarketplaceController.php` | Tidak diubah | `syncSettlements()` manual tetap ada sebagai jalur alternatif; tidak konflik dengan command baru |

Tidak ada file accounting (`JournalService.php`, `MarketplacePayoutService.php`, `AccountSeeder.php`) yang diubah pada fase ini, sesuai keputusan Anda.

---

## 14. Daftar Kebutuhan Verifikasi Manual ke `open.shopee.com`

Format sesuai permintaan Anda — silakan isi setelah membuka dokumentasi resmi:

```
1) Nama menu dokumentasi:
   Nama API: get_escrow_detail (KONFIRMASI ULANG — kita sudah pakai ini di produksi, tapi field response di bawah belum pernah diverifikasi ke docs resmi, hanya dari asumsi kode)
   Endpoint: /api/v2/payment/get_escrow_detail
   Method: GET
   Parameter:
   Response field penting: (tolong screenshot/copy daftar field lengkap dari section "order_income" di response)
   Pagination:
   Batas rentang tanggal:
   Status order yang didukung: (kita asumsikan COMPLETED/SHIPPED/TO_CONFIRM_RECEIVE — tolong konfirmasi apa benar hanya 3 status ini yang valid)
   Webhook/push code: (apakah ada push code khusus untuk escrow/income release?)

2) Nama menu dokumentasi:
   Nama API: (income/settlement list per periode — untuk sinkronisasi massal, bukan per-order)
   Endpoint:
   Method:
   Parameter:
   Response field penting:
   Pagination:
   Batas rentang tanggal:
   Status order yang didukung:
   Webhook/push code:

3) Nama menu dokumentasi:
   Nama API: (refund/adjustment pasca-settlement)
   Endpoint:
   Method:
   Parameter:
   Response field penting:
   Pagination:
   Batas rentang tanggal:
   Status order yang didukung:
   Webhook/push code:

4) Nama menu dokumentasi:
   Nama API: (wallet/saldo seller, kalau relevan untuk rekonsiliasi kas)
   Endpoint:
   Method:
   Parameter:
   Response field penting:
   Pagination:
   Batas rentang tanggal:
   Status order yang didukung:
   Webhook/push code:
```

Saya tidak akan menambahkan API baru apa pun ke rencana implementasi sampai template ini terisi dari sumber resmi.

---

## 15. Ringkasan Keputusan yang Perlu Anda Konfirmasi Sebelum Implementasi

1. **Lokasi scheduler tunggal**: `routes/console.php` atau `bootstrap/app.php` withSchedule? (Bagian 3.5)
2. **`app/Console/Kernel.php`**: cukup ditandai non-aktif, atau isinya (closure return sync 15 hari) perlu dipindah karena masih ada logika yang belum tentu digantikan `marketplace:sync-returns`?
3. **Duplikasi nyata** `sales:rebuild-daily-item-sales` & `inventory:recalc-ads-from-daily` (Bagian 3.3) — di luar cakupan Shopee finance, tapi perlu tahu apakah boleh saya sertakan sebagai temuan terpisah untuk ditindaklanjuti nanti, atau abaikan dulu.
4. **Migration unique constraint settlement** — boleh dieksekusi sekarang (aman, tabel kosong) atau tetap ditahan sampai seluruh paket migration (termasuk tabel histori) siap sekaligus?
5. **Skema `stores` vs `marketplace_stores`** untuk tabel baru — approve memakai `stores` (Bagian 2.3)?
6. **`MarketplaceReconcileCommand.php` yang sudah ada** — boleh saya baca & kemungkinan perluas untuk laporan rekonsiliasi 3-sumber, atau Anda mau command terpisah?
7. **Kolom nilai marketplace di `sales_invoices`** untuk query rekonsiliasi (Bagian 10.2) — perlu saya audit detail dulu migration-migration `add_marketplace_fields_to_sales_invoices_table` (ada 3-4 versi) untuk pastikan kolom yang benar dipakai?

---

**Status: menunggu persetujuan Anda. Belum ada migration/command/scheduler yang dibuat atau diubah.**
