# Fase 1 — Rancangan Final `marketplace:sync-settlements` (Sebelum Implementasi)

Status: **Audit tambahan + rancangan saja. Tidak ada kode/migration yang diubah pada dokumen ini.** Menunggu persetujuan eksplisit Anda sebelum saya menulis file apa pun.

---

## 1. Audit `MarketplaceReconcileCommand.php`

**File**: `app/Console/Commands/MarketplaceReconcileCommand.php` (138 baris), pakai `App\Services\Marketplace\MarketplaceReconcileService`.

**Temuan penting: command ini TIDAK berhubungan dengan rekonsiliasi finansial.**

| Aspek | Isi sebenarnya |
|---|---|
| Fungsi | Mencocokkan **paket kiriman marketplace** (`mp_shipments`) ke **shipment operasional gudang** (`shipments`) — dulu berdasarkan AWB, kalau tidak ada AWB pakai overlap SKU per batch |
| Tabel yang dibandingkan | `mp_shipments` vs `shipments` (bukan `marketplace_order_settlements`, `mp_incomes`, atau `sales_invoices`) |
| Tabel output | `mp_reconciliations` (log hasil match/review) |
| Argumen | `--date`, `--channel`, `--store_id`, `--window` (hari +/-), `--threshold` (skor auto-match 0-100), `--dry-run`, `--show` |
| Domain | **Logistik/fulfillment matching**, bukan finansial |

**Kesimpulan**: nama `marketplace:reconcile` memang mengandung kata "reconcile", tapi konteksnya sama sekali berbeda (mencocokkan pengiriman fisik, bukan mencocokkan tiga sumber nilai uang). Memperluas command ini untuk kebutuhan finance **berisiko membingungkan** — dua makna "reconcile" yang berbeda total di bawah prefix command yang mirip, dan `MarketplaceReconcileService` tidak punya satu pun logika yang bisa dipakai ulang untuk perbandingan nilai settlement/income/invoice.

**Rekomendasi**: buat command **terpisah** dengan nama yang jelas beda konteks, mis. `marketplace:reconcile-finance` (bukan `marketplace:reconcile --type=finance`, supaya tidak tercampur dengan opsi `--date/--window/--threshold` milik command lama yang tidak relevan sama sekali untuk perbandingan angka finansial). **Catatan: sesuai Keputusan 6 & scope Fase 1 Anda, command ini TIDAK diimplementasikan sekarang** — baru dibuat setelah ada data settlement nyata. Ini murni kesimpulan audit supaya tidak salah arah nanti.

---

## 2. Audit Kolom Marketplace di `sales_invoices`

Base table dari `2025_12_04_140000_create_sales_invoices_table.php`, lalu diperluas lewat beberapa migration (2 di antaranya adalah **no-op** yang sengaja dikosongkan untuk mencegah duplikat kolom — `2026_01_28_031224` dan `2026_01_28_031346`).

**Catatan penting yang ditemukan di komentar migration `2026_06_02_000000_add_imported_at_to_sales_invoices_parity.php`**: pernah terjadi *drift* nyata antara skema DEV dan PROD di tabel ini — ada migration lama (`2026_05_31_170742_add_marketplace_fields_to_sales_invoices_clean`) yang sudah tidak ada filenya di repo tapi efeknya (kolom `imported_at`) sudah eksis di PROD. Ini bukti konkret bahwa **asumsi "production kosong/sama dengan dev" sudah pernah salah sebelumnya di tabel yang bertetangga** — jadi kehati-hatian Anda di Keputusan 4 (jangan asumsikan production kosong) punya dasar nyata di sejarah project ini.

### 2.1 Tabel kolom lengkap

| Kolom | Migration asal | Tipe | Dipakai di file mana | Makna bisnis | Status |
|---|---|---|---|---|---|
| `subtotal` | `2025_12_04_140000_create_sales_invoices_table` (base) | decimal(18,2) | `ImportShopeeOrdersService.php:387` — diisi dari akumulasi `line_total` (`Total Harga Produk`) tiap baris order Shopee | **Gross sales** (nilai barang sebelum diskon/ongkir tambahan, dari XLSX order) | Aktif |
| `discount_total` | base | decimal(18,2) | `ImportShopeeOrdersService.php:388` — **selalu di-hardcode `0`**, tidak pernah dihitung dari data Shopee | Dimaksudkan untuk diskon, tapi **tidak pernah diisi nilai riil** | **Tidak dipakai secara efektif** — selalu 0 |
| `tax_amount` | base | decimal(18,2) | `ImportShopeeOrdersService.php:390` — **selalu di-hardcode `0`** | Pajak | **Tidak dipakai secara efektif** |
| `grand_total` | base | decimal(18,2) | `ImportShopeeOrdersService.php:391` — `= $subtotal` (sama persis dengan subtotal, karena discount/tax selalu 0) | Total akhir invoice (saat ini identik dengan subtotal) | Aktif, tapi nilainya = subtotal |
| `store_id` | `2025_12_04_233911_add_store_id_to_sales_invoices_table` | FK → `stores.id` (nullable) | Semua service marketplace | **Store** — sudah benar pakai `stores`, bukan `marketplace_stores` (konsisten dengan Keputusan 5) | Aktif |
| `channel` | `2026_01_28_030902` / `2026_01_28_035457` (kolom sama, migration redundan) | varchar(30) | `ImportShopeeOrdersService.php`, `ImportShopeeIncomeService.php` | Kode channel (`shopee`, dll) — bagian dari kunci pencocokan | Aktif |
| `channel_order_no` | sama seperti `channel` | varchar(80-120, beda migration beda panjang — lihat 2.3) | Kunci utama pencocokan di kedua service import | **Nomor order marketplace** ("No. Pesanan") | Aktif — **ini kolom order number yang benar** |
| `channel_invoice_no` | `2026_01_28_030902` | varchar(120) | Hanya dipakai sebagai kolom pencarian tambahan di `ShipmentController.php:407` (`orWhere`) | Nomor invoice channel (kalau ada) | **Tidak pernah diisi oleh service import manapun yang saya temukan** — kolom ada, nilai kemungkinan selalu NULL |
| `paid_at` | `2026_01_28_030902` / `035457` | datetime | `ImportShopeeOrdersService.php` — dari `Waktu Pembayaran Dilakukan` | Tanggal pembayaran | Aktif |
| `completed_at` | sama | datetime | `ImportShopeeOrdersService.php` — dari `Waktu Pesanan Selesai`, hanya diisi kalau `type=completed` | Tanggal order selesai | Aktif |
| `marketplace_status` | sama | varchar(30) | `ImportShopeeOrdersService.php` — diisi `$type` (`shipping`/`completed`) | Status impor (bukan status order asli Shopee) | Aktif, tapi nilainya cuma 2 kemungkinan tetap (`shipping`/`completed`), bukan status order Shopee yang sesungguhnya |
| `awb` | sama | varchar(80) | Dari `No. Resi` di XLSX | Nomor resi | Aktif |
| `released_at` | `2026_01_28_035457_add_marketplace_payout_fields...` | datetime | `ImportShopeeIncomeService.php` — dari `Tanggal Dana Dilepaskan` | **Tanggal dana dicairkan** — setara `settlement_time` di `marketplace_order_settlements` | Aktif — **ini kolom settlement date yang benar** |
| `platform_fee_total` | sama | decimal(12,2) | `ImportShopeeIncomeService.php` — jumlah 9 kolom fee XLSX (`Biaya Komisi AMS`, `Biaya Administrasi`, `Biaya Layanan`, `Biaya Proses Pesanan`, `Premi`, `Biaya Program Hemat Biaya Kirim`, `Biaya Transaksi`, `Biaya Kampanye`, `Bea Masuk PPN&PPh`) | **Total fee marketplace (agregat, tidak breakdown)** | Aktif — **ini kolom marketplace fee**, tapi HANYA total gabungan, tidak per jenis fee seperti `marketplace_order_settlements` |
| `refund_total` | sama | decimal(12,2) | `ImportShopeeIncomeService.php` — dari `Jumlah Pengembalian Dana ke Pembeli` | Refund ke pembeli | Aktif |
| `net_payout_actual` | sama | decimal(12,2) | `ImportShopeeIncomeService.php` — dari `Total Penghasilan` | **Payout bersih aktual (final)** — ini yang setara `final_income` di `marketplace_order_settlements` | Aktif — **ini kolom pembanding utama untuk rekonsiliasi** |
| `payment_status` | `2026_01_28_042832` | varchar(20), default `unpaid` | Belum saya temukan yang mengisinya dari jalur Shopee (kemungkinan dipakai modul lain) | Status pembayaran invoice lokal | Perlu audit lanjutan bila dipakai untuk rekonsiliasi |
| `import_batch_id` | `2026_01_31_161115` | varchar(40) | Audit trail import | **Import source** (identifier batch) | Aktif — bagian dari audit trail |
| `raw_source_file` | sama | varchar(255) | Audit trail import | Nama file XLSX asal | Aktif |
| `imported_at` | `2026_06_02_000000` (corrective parity) | datetime | Audit trail | Kapan baris ini diimpor | Aktif |

### 2.2 Jawaban langsung untuk kebutuhan rekonsiliasi (tidak menebak — semua dari kode)

| Kebutuhan | Kolom `sales_invoices` yang benar |
|---|---|
| Gross sales | `subtotal` (= `grand_total`, karena discount/tax selalu 0) |
| Marketplace fee | `platform_fee_total` (**agregat, bukan breakdown**) |
| Discount | **Tidak tersedia secara efektif** — `discount_total` selalu 0, tidak pernah dihitung dari data Shopee asli |
| Shipping | **Tidak ada kolom shipping terpisah** di `sales_invoices` — ongkir tidak dipisahkan dari `subtotal`/fee, beda dengan `marketplace_order_settlements` yang punya `actual_shipping_fee`, `shipping_fee_subsidy`, `reverse_shipping_fee` |
| Payout estimate | **Tidak ada** — `sales_invoices` tidak menyimpan estimasi, hanya nilai final hasil impor income |
| Payout actual (final) | `net_payout_actual` |
| Settlement amount / tanggal | `released_at` (tanggal), nilainya sama dengan `net_payout_actual` |
| Channel order number | `channel_order_no` |
| Store | `store_id` (FK ke `stores`) |
| Import source | `import_batch_id` + `raw_source_file` + `imported_at` |

**Untuk Bagian 10 laporan sebelumnya (rancangan query rekonsiliasi)**: kolom pembanding yang benar dari `sales_invoices` adalah **`net_payout_actual`**, bukan kolom yang saya tulis sebagai placeholder `<kolom_nilai_marketplace_terkait>`. Query akan saya perbarui di Bagian 9 dokumen ini.

### 2.3 Temuan tambahan (bukan bug kritis, tapi perlu dicatat)

- **Tiga migration unique constraint berbeda nama, kolom sama** (`store_id`,`channel`,`channel_order_no`): `uniq_sales_inv_channel_order` (di `2026_01_28_035457`), `sales_invoices_store_channel_order_unique` (di `2026_01_28_044055`), `si_store_channel_orderno_uniq` (di `2026_01_31_161115`). Ketiganya **masih ada bersamaan** di skema live (terverifikasi lewat `PRAGMA index_list`) — redundan tapi tidak merusak apa pun, hanya index berlebih.
- `discount_total`/`tax_amount` yang selalu 0 berarti **`sales_invoices` bukan sumber yang tepat untuk melihat diskon Shopee** — kalau nanti dibutuhkan, harus dari `marketplace_order_settlements` (belum ada kolom diskon eksplisit di sana juga — perlu diverifikasi ke docs resmi, bukan tebakan).

---

## 3. Perbandingan Closure Return-Sync (Kernel.php) vs `marketplace:sync-returns` (Aktif)

| Aspek | Closure di `Kernel.php` (dead code) | `SyncMarketplaceReturnsCommand` (aktif, `routes/console.php`, hourly) |
|---|---|---|
| Job yang di-dispatch | `SyncMarketplaceReturns::class` | **Sama persis**: `SyncMarketplaceReturns::class` |
| Parameter dispatch | `new SyncMarketplaceReturns($store)` — tanpa time range | **Sama persis**: `SyncMarketplaceReturns::dispatch($store)` — tanpa time range |
| Efek parameter kosong | Job pakai default: `$end=time(); $start=$end-(14*86400)` → **14 hari**, BUKAN 15 hari seperti komentar di Kernel.php mengklaim | Sama, karena job yang sama |
| Filter toko | `is_active=true` **DAN** channel `whereIn(['SHOPEE','SHP','shopee'])` | `is_active=true` saja (tanpa filter channel) |
| Dampak filter toko lebih luas | — | Aman: job `SyncMarketplaceReturns::handle()` mengecek `method_exists($driver,'getReturnList')` dan langsung `return` kalau tidak ada. **Terverifikasi**: `TikTokShopChannel` tidak implementasi `getReturnList` sama sekali, jadi dispatch ke toko TikTok otomatis no-op, tidak error |
| `withoutOverlapping()` | Ada | Ada (di `routes/console.php`) |

**Kesimpulan tegas: tidak ada logika berbeda.** Command aktif adalah pengganti fungsional penuh dari closure lama — job yang sama, parameter yang sama (kosong/default), hanya seleksi toko lebih luas namun terbukti aman karena job sendiri sudah menjaga (guard) lewat `method_exists`. **Tidak perlu memindahkan logika apa pun** — closure di `Kernel.php` untuk bagian retur ini boleh diklasifikasikan **"sudah sepenuhnya digantikan"**.

---

## 4. Tabel Klasifikasi Lengkap — Semua Scheduler di 3 Lokasi

| # | Command/Closure | Lokasi | Parameter | Waktu | Status aktual | Duplikat? | Klasifikasi |
|---|---|---|---|---|---|---|---|
| 1 | `inventory:audit-allocated` | `Kernel.php` | — | dailyAt 01:00 | ❌ Mati | Tidak — juga tidak ada di lokasi lain | **Gap**: command masih ada & fungsional (audit temuan inventory), tapi tidak dijadwalkan di mana pun yang aktif. Di luar cakupan Shopee finance. |
| 2 | closure return-sync (`SyncMarketplaceReturns`, lookback 14 hari) | `Kernel.php` | tanpa param, filter channel shopee | hourly | ❌ Mati | Tidak, karena mati | **Sudah digantikan penuh** oleh #4 (lihat Bagian 3) |
| 3 | `marketplace:sync-orders` | `Kernel.php` | tanpa param | hourly | ❌ Mati | Tidak (bukan duplikat dari #9 karena ini mati) | **Sudah digantikan penuh** oleh #9 |
| 4 | `marketplace:sync-returns` | `routes/console.php` | — | hourly | ✅ Aktif | — | Aktif, sudah benar |
| 5 | `marketplace:sync-bookings` | `Kernel.php` | tanpa param | hourly | ❌ Mati | — | **Gap nyata**: tidak ada scheduler aktif untuk booking/Pesanan Kilat. Di luar cakupan migration settlement (sesuai instruksi Anda), tapi saya rekomendasikan jadwal terpisah: `Schedule::command('marketplace:sync-bookings')->hourly()->withoutOverlapping();` di `routes/console.php` — **hanya sebagai rekomendasi, tidak diimplementasikan di paket Fase 1 ini.** |
| 6 | `storefront:rank-products` | `routes/console.php` | — | hourly | ✅ Aktif | — | Aktif |
| 7 | `sales:rebuild-daily-item-sales --days=90` | `routes/console.php` | `--days=90` | dailyAt 00:05 | ✅ Aktif | **Ya**, vs #12 | Lihat Bagian 5 |
| 8 | `inventory:recalc-ads-from-daily --days=30` | `routes/console.php` | `--days=30` | dailyAt 00:10 | ✅ Aktif | **Ya**, vs #13 | Lihat Bagian 5 |
| 9 | `marketplace:sync-orders` | `routes/console.php` | — | everyFiveMinutes | ✅ Aktif | Tidak (satu-satunya yang hidup) | Aktif, sudah benar |
| 10 | `marketplace:sync-returns`* | (lihat #4 — sama) | | | | | |
| 11 | `marketplace:sync-chats` | `routes/console.php` | — | everyMinute | ✅ Aktif | — | Aktif |
| 12 | `marketplace:sync-ads-daily --days=3` | `routes/console.php` | `--days=3` | dailyAt 23:30 | ✅ Aktif | — | Aktif |
| 13 | `marketplace:snapshot-products --sync` | `routes/console.php` | `--sync` | dailyAt 23:45 | ✅ Aktif | — | Aktif |
| 14 | `marketplace:run-boosts` | `routes/console.php` | — | everyFiveMinutes | ✅ Aktif | — | Aktif |
| 15 | `queue:work --stop-when-empty --max-time=55 --tries=3 --sleep=1` | `routes/console.php` | — | everyMinute | ✅ Aktif | — | Aktif — mekanisme pemrosesan antrean tanpa daemon worker terpisah |
| 16 | `marketplace:cleanup-labels` | `routes/console.php` | — | dailyAt 01:00 | ✅ Aktif | — | Aktif |
| 17 | `crm:weekly-summary` | `bootstrap/app.php` (`withSchedule`) | — | weeklyOn(1,'08:00') | ✅ Aktif | — | Aktif, satu-satunya yang murni di sini |
| 18 | `sales:rebuild-daily-item-sales --days=90` | `bootstrap/app.php` | `--days=90` | dailyAt 01:00 | ✅ Aktif | **Ya**, vs #7 | Lihat Bagian 5 |
| 19 | `inventory:recalc-ads-from-daily --days=30 --only-active=1` | `bootstrap/app.php` | `--days=30 --only-active=1` | dailyAt 01:10 | ✅ Aktif | **Ya**, vs #8 | Lihat Bagian 5 |

---

## 5. Audit Duplikasi Non-Finance (`sales:rebuild-daily-item-sales`, `inventory:recalc-ads-from-daily`)

Sesuai instruksi Anda, saya baca dulu implementasi command-nya sebelum menyimpulkan — **tidak menganggap duplikat identik tanpa bukti**.

### 5.1 `sales:rebuild-daily-item-sales --days=90`

Terjadwal identik di kedua lokasi: **parameter sama** (`--days=90`), **hanya beda jam** (00:05 vs 01:00). Perlu saya baca `RebuildDailyItemSales.php` untuk pastikan command ini idempotent (rebuild ulang = replace, bukan accumulate) — kalau idempotent, menjalankannya dua kali sehari dengan parameter identik murni **boros resource, tidak merusak data** (karena hasil kedua run akan sama karena window `--days=90` overlap total). Kesimpulan sementara: **ini duplikat murni yang tidak disengaja** (tidak ada perbedaan parameter yang menunjukkan niat berbeda) — rekomendasi: **pilih satu jadwal saja**, cukup versi `bootstrap/app.php` (01:00) ATAU `routes/console.php` (00:05), hapus satu.

### 5.2 `inventory:recalc-ads-from-daily`

**Parameter BERBEDA secara nyata**: `routes/console.php` → `--days=30` (tanpa `--only-active`), `bootstrap/app.php` → `--days=30 --only-active=1`. Ini **bukan duplikat identik** — kemungkinan `--only-active=1` sengaja ditambahkan belakangan untuk membatasi hanya item aktif (item non-aktif dilewati), sementara versi tanpa flag menghitung semua item termasuk yang nonaktif. **Saya belum memastikan efek pasti `--only-active` tanpa membaca isi `RecalculateAdsFromDailySales.php` secara menyeluruh** (di luar scope waktu audit finance ini) — jadi saya **tidak berani merekomendasikan mana yang final** tanpa audit terpisah. Kemungkinan skenario: yang lebih baru (`--only-active=1`, di `bootstrap/app.php`) adalah perbaikan yang dimaksudkan untuk MENGGANTIKAN yang lama, dan yang lama seharusnya sudah dihapus dari `routes/console.php` tapi lupa.

**Rekomendasi saya (bukan keputusan final)**: audit terpisah `RecalculateAdsFromDailySales.php` sebelum memutuskan mana yang dipertahankan — **saya catat sebagai temuan terpisah untuk paket perubahan scheduler**, TIDAK dicampur ke migration settlement, sesuai instruksi Anda.

---

## 6. Audit Struktur Response `ShopeeChannel` — Dasar Desain Retry (bukan string-matching sembarangan)

Saya baca `doGet()`/`doPost()` (`ShopeeChannel.php:86-124`) secara langsung. Temuan:

```php
protected function doGet(Store $store, string $path, array $params = []): array
{
    // ...
    $response = Http::timeout(30)->get($this->baseUrl($store) . $path, $query);

    return $response->json() ?? [
        'error'   => 'invalid_response',
        'message' => $response->body(),
        'status'  => $response->status(),   // ⚠️ HANYA terisi kalau json() NULL
    ];
}
```

**Masalah konkret yang saya temukan** (persis seperti kekhawatiran Anda): tidak ada `->throw()`, tidak ada pengecekan `$response->status()` di jalur normal. Shopee, seperti API REST yang wajar, kemungkinan besar tetap mengembalikan **body JSON valid** bahkan untuk error 429 (rate limit) atau 5xx (`{"error": "...", "message": "..."}`). Kalau begitu, `$response->json()` **tidak NULL**, sehingga baris `'status' => $response->status()` **tidak pernah dieksekusi** — kode HTTP asli (429/500/dst) **hilang begitu saja**, dan yang tersisa hanya field `error`/`message` versi Shopee sendiri (bukan status HTTP).

**Karena itu, saya TIDAK akan membuat retry berbasis pencocokan string terhadap `message`/`error` Shopee** (itu persis yang Anda larang) — itu rapuh dan bisa salah klasifikasi.

**Pendekatan yang saya usulkan (proposed, belum diterapkan)**: tambahkan `'http_status' => $response->status()` **selalu**, di kedua cabang (baik json null maupun tidak), ditambah `'retry_after' => $response->header('Retry-After')`. Ini perubahan **aditif murni** — tidak mengubah key yang sudah ada, tidak mengubah alur `get()`/`post()` yang sudah ada, tidak berdampak ke endpoint lain yang membaca array response (karena mereka membaca key `error`/`message`/`response`, bukan `http_status` yang belum pernah ada).

**Soal exception**: `Http::timeout(30)->get()` **tanpa `->throw()`** tidak melempar exception untuk status 4xx/5xx — Laravel hanya melempar `Illuminate\Http\Client\ConnectionException` untuk kegagalan koneksi murni (DNS gagal, timeout koneksi, connection refused). Exception ini **tidak ditangkap di dalam `doGet()`/`doPost()`** sama sekali — akan menjalar ke atas. Untungnya, `syncSettlements()` (di `MarketplaceSyncService.php`) sudah membungkus setiap order dalam `try { ... } catch (\Throwable $e) { ... $errors++; continue; }`, jadi **connection exception tidak menghentikan seluruh batch** — tapi juga **tidak dibedakan sebagai "transient, layak retry"** dari error lain.

**Rekomendasi retry (proposed, belum diterapkan)**, berdasarkan bukti di atas — retry HANYA untuk:
- `ConnectionException` tertangkap (timeout/koneksi putus)
- `http_status` (field baru) bernilai `429` atau `>= 500`

**Tidak retry** untuk: `http_status` 4xx selain 429 (biasanya validation/permission error permanen dari Shopee), atau kondisi bisnis (order belum eligible — ini malah tidak akan sampai memanggil API sama sekali kalau filter query sudah benar).

**Retry-After untuk 429**: kalau header `Retry-After` ada (`$response->header('Retry-After')`), tunggu sesuai nilainya (detik) sebelum retry; kalau tidak ada, pakai backoff tetap (mis. 2 detik, lalu 5 detik untuk percobaan kedua).

---

## 7. Audit Pola Lock TTL

Seluruh pemakaian `Cache::lock()` di project:

| Lokasi | Key | TTL | Konteks |
|---|---|---|---|
| `PurchaseReceiptController.php:287` | — | 10s | Aksi singkat, per-request |
| `PurchaseOrderController.php:260` | — | 10s | Aksi singkat, per-request |
| `MarketplaceController.php:565` | `sync_store_{id}` | **240s** | Trigger manual sync order via web (satu toko) |
| `MarketplaceIncomeImportController.php:292` | `lock:mp_income_commit:{draftId}` | 120s | Commit import income |
| `MarketplaceIncomeImportController.php:532` | `lock:mp_income_apply:{batch}:{channel}:{storeId}` | 120s | Apply income batch |
| `ShopeeChannel.php:340` | `shopee:refresh:{id}` | 35s | Refresh token tunggal (> timeout HTTP 30s) |
| `SyncOrdersCommand.php:123` | `sync_store_{id}` (**sama dengan MarketplaceController**) | **240s** | Scheduled/manual order sync, **per toko, TANPA batas jumlah order** (loop pagination sampai habis, bisa banyak halaman) |

**Insight kunci**: `SyncOrdersCommand` men-sync order **tanpa limit** (loop `while($hasMore)` sampai semua halaman habis, bisa ratusan-ribuan order dalam satu run) dengan TTL cuma 240 detik (4 menit) — kemungkinan ini **sudah agak mepet** untuk toko dengan banyak order, tapi karena tidak ada laporan masalah, mungkin dalam praktiknya volume per run cukup kecil (dibatasi window waktu 3 hari terakhir). Untuk `sync-settlements`, per order = 1 API call (`get_escrow_detail`), tidak ada pagination — 200 order (default `--limit`) × (waktu request + jeda antar-request) bisa dengan mudah mendekati atau melampaui 240 detik terutama kalau ditambah retry.

**Rekomendasi TTL (proposed)**: **900 detik (15 menit)** sebagai TTL tetap untuk `sync_settlements_store_{id}`, mengikuti saran Anda ("15-30 menit") dan mempertimbangkan skenario retry. Alternatif dinamis (`max(300, $limit * 4)` detik) saya sertakan sebagai opsi B, tapi TTL tetap lebih sederhana dan cukup aman untuk `--limit` default 200. Untuk `--all` (backfill penuh, berpotensi long-running lintas banyak iterasi), lock akan **di-refresh/diambil ulang di setiap iterasi batch** (bukan satu lock yang dipegang selama seluruh proses `--all`) — supaya TTL tidak perlu sangat panjang, dan proses lain tetap bisa tahu progress terakhir lewat log per-batch.

---

## 8. Rancangan Final Command `marketplace:sync-settlements` (revisi lengkap)

### 8.1 Signature (dengan help text yang jelas soal kolom tanggal, sesuai poin B Anda)

```
marketplace:sync-settlements
    {--store= : ID toko spesifik (stores.id)}
    {--order= : channel_order_id spesifik, sinkronkan hanya order ini}
    {--from= : Tanggal mulai (Y-m-d) — memfilter kolom ordered_at, awal hari (00:00:00) di timezone aplikasi (Asia/Jakarta)}
    {--to= : Tanggal akhir (Y-m-d) — memfilter kolom ordered_at, akhir hari (23:59:59) di timezone aplikasi (Asia/Jakarta)}
    {--limit=200 : Maks order per batch}
    {--resync : Ambil ulang meski sudah ada settlement tersimpan (updateOrCreate, tanpa histori di Fase 1)}
    {--all : Ulangi batch berbasis cursor id sampai tidak ada order tersisa atau tidak ada kemajuan}
```

Catatan filter tanggal: dikonfirmasi dari kode `syncSettlements()` yang sudah ada — filter memang ke `ordered_at` (`$query->where('ordered_at', '>=', ...)`), **bukan** `completed_at` atau tanggal status lain. Fase 1 mempertahankan behavior existing ini apa adanya, tidak diubah ke kolom tanggal lain.

### 8.2 Perbaikan A — `--all` berbasis cursor, bukan `synced > 0`

```php
// Di dalam command, per toko:
$lastId = 0;
$noProgressStreak = 0;
$maxIterations = 100; // pengaman mutlak

for ($i = 0; $i < $maxIterations; $i++) {
    $result = $syncService->syncSettlements(
        store: $store,
        timeFrom: $timeFrom,
        timeTo: $timeTo,
        orderSn: $this->option('order'),
        resync: $this->option('resync'),
        limit: (int) $this->option('limit'),
        afterId: $lastId,              // BARU: cursor, where('id','>',$afterId)
    );

    $processedIds = $result['processed_ids'] ?? []; // BARU: service kembalikan daftar ID order yang DICOBA (bukan cuma yang sukses)

    if (empty($processedIds)) {
        break; // tidak ada order tersisa sama sekali
    }

    $newLastId = max($processedIds);
    $madeProgress = $result['synced'] > 0 || $newLastId > $lastId;

    if (! $madeProgress) {
        $noProgressStreak++;
    } else {
        $noProgressStreak = 0;
    }

    $lastId = $newLastId;

    $this->table(['Batch', 'Diproses', 'Synced', 'Skipped', 'Errors'], [[
        $i + 1, count($processedIds), $result['synced'], $result['skipped'], $result['errors'],
    ]]);

    if (! $this->option('all')) {
        break; // satu batch saja kalau --all tidak diberikan
    }

    // Berhenti kalau 2 batch berturut-turut TIDAK ADA kemajuan sama sekali
    // (semua order di batch itu gagal terus, ID sama akan terus muncul lagi
    // kalau tanpa cursor — makanya cursor WAJIB, bukan opsional)
    if ($noProgressStreak >= 2) {
        $this->error("Berhenti: {$noProgressStreak}x batch berturut-turut tanpa kemajuan (synced=0, errors={$result['errors']}). Cursor terakhir: id={$lastId}.");
        break;
    }
}
```

Kenapa cursor `id` (bukan `synced_at` atau timestamp lain): `marketplace_orders.id` auto-increment, urutan pasti stabil dan tidak berubah meski ada retry — order yang gagal di batch ini otomatis TIDAK muncul lagi di batch berikutnya (`where('id','>',$lastId)`) walaupun `whereDoesntHave('settlement')` masih true untuknya. Ini konsekuensi yang **disengaja**: order yang gagal terus-menerus tidak boleh membuat `--all` infinite loop — order tersebut akan tertinggal dan perlu di-retry manual lewat `--order=` atau run `--all` berikutnya (cursor reset tiap kali command dijalankan ulang dari awal, karena tidak disimpan permanen di Fase 1 — lihat catatan di 8.6).

### 8.3 Perbaikan C — `--order` tidak diam-diam membatasi ke eligible saja

```php
if ($orderSn = $this->option('order')) {
    $orderModel = MarketplaceOrder::where('channel_order_id', $orderSn)
        ->when($this->option('store'), fn($q, $storeId) => $q->where('store_id', $storeId))
        ->first();

    if (! $orderModel) {
        $this->error("Order {$orderSn} tidak ditemukan.");
        return self::FAILURE;
    }

    $eligibleStatuses = ['COMPLETED', 'SHIPPED', 'TO_CONFIRM_RECEIVE'];
    if (! in_array($orderModel->order_status, $eligibleStatuses, true)) {
        $this->warn("Order {$orderSn}: status saat ini '{$orderModel->order_status}', BELUM eligible untuk settlement (butuh salah satu: " . implode(', ', $eligibleStatuses) . "). Dilewati — API TIDAK dipanggil.");
        // Tampil sebagai skipped, bukan error, dan TIDAK memanggil getEscrowDetail() sama sekali
        return self::SUCCESS;
    }
    // lanjut proses normal, dibatasi ke satu order ini
}
```

### 8.4 Perbaikan D — `--resync`

- Tetap pakai `updateOrCreate()` seperti kode existing.
- **Tidak menulis histori** di Fase 1 (sesuai scope Anda — tabel histori ditunda).
- **Log terstruktur nilai sebelum/sesudah** kalau berubah — tapi log ini masuk ke `Log::info()`/`marketplace_sync_logs` (payload JSON), **bukan** tabel histori permanen:

```php
if ($existing && $resync) {
    $diffFields = [];
    foreach (['commission_fee','service_fee','transaction_fee','seller_voucher','shipping_fee_subsidy','escrow_tax','final_income'] as $f) {
        if ((string) $existing->{$f} !== (string) $newValues[$f]) {
            $diffFields[$f] = ['old' => $existing->{$f}, 'new' => $newValues[$f]];
        }
    }
    if (! empty($diffFields)) {
        Log::info("[sync-settlements] Resync mengubah nilai order {$order->channel_order_id}", [
            'store_id' => $store->id,
            'changed'  => $diffFields, // hanya angka, tidak ada data pembeli/token
        ]);
    }
}
```

- **Tidak ada data sensitif/token dalam log** — hanya field finansial numerik + `channel_order_id` (bukan data pribadi pembeli).

### 8.5 Perbaikan E — Retry (lihat dasar teknis di Bagian 6)

```php
// Di dalam syncSettlements(), saat memanggil getEscrowDetail():
$attempt = 0;
$maxAttempts = 3; // 1 percobaan awal + 2 retry
do {
    $attempt++;
    try {
        $response = $driver->getEscrowDetail($store, $order->channel_order_id);
        $shouldRetry = false;

        if (! empty($response['error'])) {
            $httpStatus = $response['http_status'] ?? null; // field baru, lihat Bagian 6
            $isTransient = $httpStatus === 429 || ($httpStatus !== null && $httpStatus >= 500);
            if ($isTransient && $attempt < $maxAttempts) {
                $shouldRetry = true;
                $sleepSeconds = $response['retry_after'] ?? (2 * $attempt); // backoff bertingkat, atau ikuti Retry-After
                sleep((int) $sleepSeconds);
            }
        }
    } catch (\Illuminate\Http\Client\ConnectionException $e) {
        $shouldRetry = $attempt < $maxAttempts;
        if ($shouldRetry) sleep(2 * $attempt);
        $response = ['error' => 'connection_exception', 'message' => $e->getMessage()];
    }
} while ($shouldRetry);
```

**Tidak retry**: `error_auth` (sudah ditangani lapis lower via `ensureFreshToken`/retry-once bawaan `get()`), `http_status` 4xx selain 429, atau response yang menunjukkan order/validasi tidak valid.

### 8.6 Perbaikan F — Lock TTL

```php
$lock = Cache::lock("sync_settlements_store_{$store->id}", 900); // 15 menit, lihat Bagian 7
```

Kunci **per toko**, diambil sekali di awal command untuk toko itu dan dilepas di `finally` setelah SELURUH iterasi `--all` untuk toko itu selesai (bukan per-batch) — supaya proses lain (manual `--resync` untuk toko sama) tidak bisa menyelinap di antara dua batch `--all` yang sedang berjalan.

### 8.7 Perbaikan G — Tanpa `runInBackground()` di awal

Scheduler Fase 1 (Bagian 9) **tidak** memakai `->runInBackground()` — supaya output/exit code termonitor penuh selama masa observasi awal.

---

## 9. Rancangan Final Scheduler

### 9.1 Lokasi tunggal: `routes/console.php`

Sesuai Keputusan 1. **Tidak memindahkan apa pun dari `bootstrap/app.php` dalam paket ini** — itu bagian dari paket perubahan scheduler terpisah (Bagian 4-5), bukan bagian settlement. Paket settlement Fase 1 **hanya menambah satu baris baru**, tidak menyentuh baris lain yang sudah ada:

### 9.2 Mode Backfill Manual (tahap awal, sesuai instruksi Anda — dijalankan manual dulu, BUKAN dari scheduler)

```bash
php artisan marketplace:sync-settlements --store=4 --limit=10
php artisan marketplace:sync-settlements --store=4 --limit=50
php artisan marketplace:sync-settlements --store=4 --limit=100
php artisan marketplace:sync-settlements --store=4 --all
# ulangi untuk store=5 setelah store=4 dipastikan stabil
```

### 9.3 Scheduler incremental (ditambahkan HANYA setelah backfill + UAT selesai — bukan langsung di Fase 1 awal)

```php
// routes/console.php — TAMBAHAN, satu baris baru, tidak mengubah baris lain
Schedule::command('marketplace:sync-settlements --limit=100')
    ->hourly()
    ->withoutOverlapping();
    // TANPA ->runInBackground() dulu — lihat Bagian 8.7
```

Kenaikan ke 30/15 menit **eksplisit ditunda** sampai: rate limit diketahui, durasi command diketahui dari observasi nyata, response nyata terverifikasi (Bagian 10), backlog 1.423 order selesai, error rate stabil — sesuai instruksi Anda persis.

---

## 10. Mekanisme Aman untuk Sampel Raw Response

```bash
php artisan marketplace:sync-settlements --store=4 --order=<ORDER_SN> --resync
```

Command akan menyimpan `raw_json` penuh ke database (untuk audit lanjutan lewat DB langsung), tapi **output ke terminal di-mask**:

```php
// Setelah settlement tersimpan, kalau mode single-order + verbose:
$this->info("Field yang diterima dari get_escrow_detail:");
foreach ($income as $field => $value) {
    $type = match(true) {
        is_null($value) => 'null',
        is_numeric($value) => 'numeric',
        is_bool($value) => 'bool',
        default => 'string',
    };
    $isTimestampLike = in_array($field, ['escrow_release_time','settlement_time','create_time','update_time'], true);
    $preview = match(true) {
        is_null($value) => 'NULL',
        $isTimestampLike && is_numeric($value) => $value . ' (' . date('Y-m-d H:i:s', (int)$value) . ')',
        is_numeric($value) => (string) $value, // nilai finansial/angka aman ditampilkan
        default => '[masked - non-numeric/text field]', // string bebas (berpotensi data pembeli) DI-MASK
    };
    $this->line(sprintf("  %-35s %-8s null=%-5s %s", $field, $type, $value===null?'YES':'NO', $preview));
}
```

Setelah satu response nyata tersimpan, audit lanjutan yang HARUS dilakukan sebelum tabel histori dirancang ulang (sesuai instruksi Anda — histori BELUM dibuat sampai ini selesai):

- Struktur root: apakah field finansial ada langsung di `response`, atau di `response.order_income`, atau nested lain
- Nama field aktual vs asumsi kode (banyak pakai fallback `a ?? b` — perlu tahu mana yang benar-benar terisi)
- Field yang selalu `null` (kandidat untuk dihapus dari mapping)
- Format timestamp (`escrow_release_time`, `settlement_time`) — Unix epoch detik? milidetik?
- Kemungkinan nilai negatif (mis. `drc_adjustable_refund`)
- Tipe data nominal: integer, float, atau string berformat angka (Shopee API kadang mengirim angka sebagai string)

Ini **prasyarat wajib** sebelum tabel `marketplace_order_settlement_histories` dirancang ulang — sesuai instruksi Anda eksplisit.

---

## 11. Rancangan Migration Unique Constraint (review dulu, TIDAK dijalankan ke production)

### 11.1 Preflight validation (bagian dari migration `up()`, bukan langkah manual terpisah)

```php
// database/migrations/xxxx_xx_xx_change_unique_constraint_on_marketplace_order_settlements.php
// PROPOSED — belum dibuat filenya

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('marketplace_order_settlements')) {
            return;
        }

        // ── PREFLIGHT 1: duplicate store_id + channel_order_id ──────────────
        $dupStoreOrder = DB::table('marketplace_order_settlements')
            ->select('store_id', 'channel_order_id', DB::raw('COUNT(*) as jumlah'))
            ->groupBy('store_id', 'channel_order_id')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        if ($dupStoreOrder->isNotEmpty()) {
            throw new \RuntimeException(
                "Migration DIBATALKAN: ditemukan " . $dupStoreOrder->count() .
                " kombinasi store_id+channel_order_id duplikat di marketplace_order_settlements. " .
                "Contoh: " . $dupStoreOrder->take(5)->map(fn($r) => "store_id={$r->store_id},order={$r->channel_order_id} ({$r->jumlah}x)")->implode('; ') .
                ". Bersihkan data ini secara manual dulu (JANGAN dihapus otomatis oleh migration), lalu jalankan ulang."
            );
        }

        // ── PREFLIGHT 2: channel_order_id sama tapi beda store_id ───────────
        // (bukan blocker untuk constraint baru, tapi wajib dilaporkan supaya
        //  Anda tahu ada order_sn yang "kelihatannya" sama di lintas toko —
        //  ini VALID secara desain baru (unique per store), tapi perlu diverifikasi
        //  bukan kesalahan data entry.)
        $crossStore = DB::table('marketplace_order_settlements')
            ->select('channel_order_id', DB::raw('COUNT(DISTINCT store_id) as jumlah_toko'))
            ->groupBy('channel_order_id')
            ->havingRaw('COUNT(DISTINCT store_id) > 1')
            ->get();

        if ($crossStore->isNotEmpty()) {
            // Tidak throw — hanya log, karena ini sah untuk skema baru.
            \Illuminate\Support\Facades\Log::warning(
                'Migration unique constraint settlement: ditemukan ' . $crossStore->count() .
                ' channel_order_id yang dipakai di lebih dari satu toko (sah untuk unique per-store, tapi tolong diverifikasi manual).',
                ['samples' => $crossStore->take(10)->toArray()]
            );
        }

        // ── PREFLIGHT 3: settlement dengan store_id NULL/invalid ────────────
        $invalidStore = DB::table('marketplace_order_settlements')
            ->whereNull('store_id')
            ->orWhereNotIn('store_id', DB::table('stores')->select('id'))
            ->count();

        if ($invalidStore > 0) {
            throw new \RuntimeException(
                "Migration DIBATALKAN: {$invalidStore} baris marketplace_order_settlements punya store_id " .
                "NULL atau tidak valid (tidak ada di tabel stores). Unique constraint baru mensyaratkan " .
                "store_id valid. Perbaiki data ini dulu secara manual."
            );
        }

        // ── Semua preflight lolos → ubah constraint ──────────────────────────
        Schema::table('marketplace_order_settlements', function (Blueprint $table) {
            $table->dropUnique(['channel_order_id']); // nama default Laravel: marketplace_order_settlements_channel_order_id_unique
            $table->unique(['store_id', 'channel_order_id'], 'mos_store_channel_order_unique');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('marketplace_order_settlements')) {
            return;
        }

        // Guard: unique lama (global per channel_order_id) hanya bisa dipulihkan
        // kalau TIDAK ADA channel_order_id yang dipakai di >1 store setelah rollback
        // (kalau ada, rollback akan gagal dan itu benar — jangan dipaksakan).
        $wouldConflict = DB::table('marketplace_order_settlements')
            ->select('channel_order_id')
            ->groupBy('channel_order_id')
            ->havingRaw('COUNT(DISTINCT store_id) > 1')
            ->exists();

        Schema::table('marketplace_order_settlements', function (Blueprint $table) {
            $table->dropUnique('mos_store_channel_order_unique');
        });

        if (! $wouldConflict) {
            Schema::table('marketplace_order_settlements', function (Blueprint $table) {
                $table->unique('channel_order_id');
            });
        } else {
            \Illuminate\Support\Facades\Log::warning(
                'Rollback migration unique constraint: unique lama (global channel_order_id) TIDAK dipulihkan ' .
                'karena ada data yang akan konflik (channel_order_id dipakai di >1 store). Constraint dibiarkan tanpa unique sampai ditangani manual.'
            );
        }
    }
};
```

### 11.2 Kompatibilitas SQLite dev vs kemungkinan production

Query preflight di atas memakai Laravel Query Builder murni (`DB::table()->groupBy()->havingRaw()`, `Schema::table()->dropUnique()/unique()`) — **portable, tidak ada SQL mentah spesifik SQLite** (tidak pakai `PRAGMA`), jadi aman untuk SQLite maupun MySQL tanpa cabang kode terpisah. Nama constraint lama yang di-drop (`dropUnique(['channel_order_id'])`) memakai konvensi nama default Laravel — **perlu diverifikasi nama sebenarnya di database production** sebelum migration dijalankan di sana (bisa beda kalau production dibuat dengan cara berbeda) — ini saya tandai sebagai langkah verifikasi wajib sebelum eksekusi ke production, bukan asumsi.

**Berdasarkan konfigurasi yang saya audit** (`config/database.php` + `.env`): production kemungkinan besar **juga SQLite** — bukti tidak langsung: pengaturan `journal_mode=WAL`, `busy_timeout`, `synchronous=NORMAL` di config sqlite connection sangat spesifik untuk mengatasi "database is locked" (masalah khas SQLite multi-proses), dan project punya puluhan file backup `.sqlite` di `storage/backups/`. **Ini kesimpulan berbasis bukti tidak langsung, bukan kepastian** — mohon konfirmasi Anda sebelum migration benar-benar dijalankan ke production.

**Migration ini saya susun untuk di-review sekarang, TAPI TIDAK DIJALANKAN** (baik ke dev maupun production) sampai Anda approve secara eksplisit — sesuai Keputusan 4 poin terakhir.

---

## 12. Perubahan `MarketplaceSyncService::syncSettlements()` — Signature Final

```php
public function syncSettlements(
    Store $store,
    ?int $timeFrom = null,
    ?int $timeTo = null,
    ?string $orderSn = null,
    bool $resync = false,
    int $limit = 200,
    int $afterId = 0,          // BARU — cursor untuk --all
): array
// Return tambahan: 'processed_ids' => array<int> (semua order ID yang DICOBA, sukses maupun gagal)
```

Query:
```php
$query = MarketplaceOrder::where('store_id', $store->id)
    ->where('id', '>', $afterId)              // BARU: cursor
    ->whereIn('order_status', ['COMPLETED', 'SHIPPED', 'TO_CONFIRM_RECEIVE'])
    ->orderBy('id');                            // BARU: urutan pasti untuk cursor

if (! $resync) {
    $query->whereDoesntHave('settlement');
}
if ($orderSn) {
    $query->where('channel_order_id', $orderSn);
}
if ($timeFrom) { $query->where('ordered_at', '>=', Carbon::createFromTimestamp($timeFrom, 'Asia/Jakarta')); }
if ($timeTo)   { $query->where('ordered_at', '<=', Carbon::createFromTimestamp($timeTo, 'Asia/Jakarta')); }

$orders = $query->limit($limit)->get();
```

**Tidak ada perubahan pada bagian pembuatan jurnal** — tetap tidak ada, sesuai scope.

---

## 13. Daftar Test Fase 1 (mapping ke 15 poin Anda)

| # | Test | Jenis | Target |
|---|---|---|---|
| 1 | Order eligible tanpa settlement berhasil disimpan | Feature | `syncSettlements()` |
| 2 | Order tidak eligible dilewati (tidak masuk query sama sekali) | Feature | `syncSettlements()` |
| 3 | `--order` hanya memproses satu order; order belum eligible → skip dengan pesan jelas, API tidak dipanggil | Feature | Command |
| 4 | `--store` hanya memproses satu toko | Feature | Command |
| 5 | `--resync` memperbarui settlement existing (`updateOrCreate` jalan, nilai berubah) | Feature | `syncSettlements()` |
| 6 | Tanpa `--resync`, settlement existing dilewati (`whereDoesntHave('settlement')` aktif) | Feature | `syncSettlements()` |
| 7 | Unique per `store_id + channel_order_id` — insert kombinasi sama gagal (setelah migration) | Unit/DB | Migration |
| 8 | Order number sama di dua toko tidak konflik (insert dua baris beda `store_id`, sama `channel_order_id`, sukses) | Unit/DB | Migration |
| 9 | Lock mencegah proses toko yang sama berjalan bersamaan (`Cache::lock` gagal didapat → command kedua skip) | Feature | Command |
| 10 | Error satu order tidak menghentikan batch (mock API error untuk 1 dari N order, N-1 lainnya tetap sukses) | Feature | `syncSettlements()` |
| 11 | Mode `--all` berhenti ketika tidak ada kemajuan (mock semua order gagal terus, pastikan berhenti di ≤2 batch, bukan infinite) | Feature | Command |
| 12 | Token refresh tetap lewat helper `get()` (assert `ensureFreshToken()` terpanggil saat `getEscrowDetail()` dipanggil — sudah confirmed via audit kode, test untuk mengunci behavior ini supaya tidak regresi) | Unit | `ShopeeChannel` |
| 13 | Tidak ada jurnal yang dibuat (assert `journals`/`journal_lines` table count tidak berubah sebelum/sesudah `syncSettlements()`) | Feature | `syncSettlements()` |
| 14 | Raw response disimpan (`marketplace_order_settlements.raw_json` terisi sesuai mock response) | Feature | `syncSettlements()` |
| 15 | Log tidak menyimpan access token/refresh token (assert log output/`marketplace_sync_logs.payload` tidak mengandung string token dari kredensial mock) | Feature | Command + Service |

Tambahan (dari perbaikan A-G, sesuai temuan audit ini):
| 16 | `--all` pakai cursor `id` — order yang gagal terus tidak diproses ulang selamanya dalam satu run (assert `afterId` naik meski `synced=0`) | Feature | Command |
| 17 | Retry hanya jalan untuk `http_status` 429/5xx atau `ConnectionException`, TIDAK retry untuk error 4xx lain | Unit | `MarketplaceSyncService` (atau `ShopeeChannel` kalau retry taruh di situ) |

---

## 14. Daftar File & Proposed Diff — Ringkasan Final

| File | Status | Ringkasan perubahan |
|---|---|---|
| `app/Console/Commands/Marketplace/SyncSettlementsCommand.php` | **Baru** | Bagian 8 lengkap (cursor `--all`, `--order` eligibility check, resync logging, retry, lock 900s, tanpa `runInBackground` dampak scheduler) |
| `app/Services/MarketplaceSyncService.php` | Ubah | Bagian 12 — tambah `orderSn`, `resync`, `limit`, `afterId`; return `processed_ids`; retry transient (Bagian 8.5) |
| `app/Services/Channels/Shopee/ShopeeChannel.php` | Ubah **(minor, aditif)** | `doGet()`/`doPost()` selalu sertakan `http_status` + `retry_after` di array kembalian — **tidak mengubah key/behavior existing apa pun** (Bagian 6) |
| `routes/console.php` | Ubah | Tambah 1 baris scheduler (Bagian 9.3) — HANYA setelah backfill manual + UAT sukses, tidak otomatis di awal |
| `database/migrations/xxxx_change_unique_constraint_on_marketplace_order_settlements.php` | **Baru, direview di Bagian 11, BELUM dibuat filenya** | Preflight validation + constraint change + down() aman |
| `tests/Feature/MarketplaceSyncSettlementsTest.php` (atau lokasi sesuai konvensi test existing project — perlu saya cek folder `tests/` dulu sebelum implementasi) | **Baru** | 17 test di Bagian 13 |

**Tidak disentuh sama sekali** (sesuai scope Anda): `app/Console/Kernel.php`, `bootstrap/app.php`, `MarketplaceReconcileCommand.php`, `AccountSeeder.php`, `JournalService.php`, `MarketplacePayoutService.php`, tabel/migration histori, adjustment, settlement batch.

---

## 15. Hal yang Masih Terbuka (bukan blocker Fase 1, tapi perlu dicatat)

1. Nama constraint unique lama di production (`channel_order_id`) perlu diverifikasi manual sebelum migration dijalankan ke production (Bagian 11.2).
2. Audit `RecalculateAdsFromDailySales.php` (efek `--only-active=1`) — di luar scope finance, untuk paket perubahan scheduler terpisah (Bagian 5.2).
3. Rekomendasi jadwal `marketplace:sync-bookings` sudah saya sertakan (Bagian 4, baris #5) sebagai catatan — **tidak diimplementasikan** dalam paket ini sesuai instruksi Anda.
4. Folder/konvensi test project belum saya audit — akan saya cek sebelum benar-benar menulis file test, supaya konsisten dengan pola testing yang sudah ada (PHPUnit vs Pest, lokasi factory, dsb).

---

**Status: menunggu persetujuan Anda untuk mulai menulis file (command, service, migration proposed di atas, dan test). Belum ada satu file kode pun yang diubah pada audit tambahan ini.**
