# Audit Integrasi API Keuangan Shopee — gfid-dev

Tanggal audit: 22 Juli 2026
Status: **AUDIT SAJA — belum ada kode/migration/database yang diubah.**

> ⚠️ **Catatan blocker Tahap 3**: `open.shopee.com` (dokumentasi resmi Shopee Open Platform) **tidak bisa diakses** — baik lewat `web_fetch` (HTTP 403, "URL is on blocklist") maupun lewat Chrome browser tool ("Permission denied"). Pencarian alternatif (GitHub resmi Shopee, Postman resmi Shopee) juga tidak ditemukan — yang ada hanya wrapper/SDK pihak ketiga (tidak resmi), yang sengaja **tidak dipakai sebagai sumber** sesuai instruksi Anda. Sebagai gantinya, Bagian B di bawah menandai jelas mana yang **terverifikasi dari kode project (endpoint yang sudah live di production)** vs mana yang **belum bisa diverifikasi sama sekali** dan wajib dicek manual oleh Anda di browser sebelum diimplementasikan. Tidak ada nama endpoint yang saya karang.

---

## A. Ringkasan Project Saat Ini

Project **sudah punya integrasi Shopee yang berjalan di production**, bukan project kosong. Pattern yang dipakai:

- **Channel/Driver pattern**: `MarketplaceChannel` (interface) → `ShopeeChannel` (implementasi) → di-resolve lewat `ChannelManager::driver($store)`. Channel lain (TikTok Shop) pakai kontrak yang sama.
- **Service layer**: `MarketplaceSyncService` (sync order, settlement, ads, produk), `App\Services\Accounting\JournalService` (jurnal umum), `App\Services\Accounting\MarketplacePayoutService` (jurnal payout marketplace).
- **Command + Scheduler**: sync dijalankan lewat Artisan command (`marketplace:sync-orders`, dst) yang dijadwalkan di `app/Console/Kernel.php` dan **juga** di `routes/console.php` (lihat bug #1 di Gap Analysis).
- **Webhook**: `WebhookController` → `ProcessShopeeWebhookJob` menangani push Shopee (order status, tracking, item update, dll) dengan dedup key di cache (60 detik) — **tidak ada handler untuk push finansial** (income release / payout).
- **Autentikasi**: token disimpan di `stores.credentials` (kolom JSON terenkripsi Laravel, `encrypted:array`), field `token_expires_at` terpisah. Refresh token pakai distributed lock (`Cache::lock`) karena refresh_token Shopee sekali-pakai dan berotasi — ini sudah ditangani dengan baik.
- **Data keuangan sudah punya jalur API resmi**: `MarketplaceOrderSettlement` (tabel `marketplace_order_settlements`) diisi dari `getEscrowDetail()` (endpoint `/api/v2/payment/get_escrow_detail`) via `MarketplaceSyncService::syncSettlements()`. **Tapi ini HANYA bisa dipicu manual** lewat controller (`MarketplaceController::syncSettlements`) — tidak ada scheduler otomatis untuk settlement, beda dengan order yang sudah terjadwal per jam/5 menit.
- **Ada TIGA jalur data finansial paralel yang tidak saling terhubung**:
  1. `marketplace_order_settlements` — dari API escrow (paling lengkap kolom fee-nya, tapi sync manual).
  2. `mp_incomes` — dari import file XLSX income Shopee (`ShopeeIncomeAdapter`).
  3. `sales_invoices` (+ kolom `marketplace_*` yang ditambahkan lewat beberapa migration terpisah) — jalur legacy dari `ImportShopeeIncomeService`/`ImportShopeeOrdersService`.
  
  Tidak ada foreign key silang antar ketiganya, dan tidak ada mekanisme deteksi selisih nilai otomatis.
- **Accounting**: `JournalService.php` (1908 baris, modul jurnal umum) **tidak punya satu pun referensi ke Shopee/marketplace**. Jurnal marketplace dibuat lewat `MarketplacePayoutService` yang **terpisah**, dan hanya mencatat **satu angka lump-sum** (Dr Bank / Cr 1302 Piutang Marketplace) — bukan breakdown per fee (komisi, voucher, ongkir, dll). Chart of accounts (`accounts` table, seed di `AccountSeeder.php`) hanya punya akun generik **"Biaya Marketplace"** — belum ada akun terpisah untuk komisi, service fee, voucher, subsidi ongkir, refund, atau adjustment.

---

## B. API Shopee yang Ditemukan

### B.1 — Endpoint yang SUDAH dipakai project (terverifikasi dari kode, live di production)

Sumber verifikasi: `app/Services/Channels/Shopee/ShopeeChannel.php` (bukan dokumentasi resmi — akses ke `open.shopee.com` diblokir untuk sesi ini).

| Nama fungsi (di kode) | Method | Endpoint path | File\:baris | Fungsi |
|---|---|---|---|---|
| `getEscrowDetail()` | GET | `/api/v2/payment/get_escrow_detail` | `ShopeeChannel.php:218` | **Satu-satunya endpoint finansial yang sudah diintegrasikan.** Dipanggil per `order_sn`, hasilnya di-map ke `marketplace_order_settlements` (commission_fee, service_fee, seller_voucher, shipping_fee_subsidy, escrow_tax, final_income, dll — lihat `MarketplaceSyncService.php:273-305`) |
| `getOrders()` / `get_order_list` | GET | `/api/v2/order/get_order_list` | `ShopeeChannel.php:150` | List order per status, dengan pagination cursor |
| `getOrderDetail()` | GET | `/api/v2/order/get_order_detail` | `ShopeeChannel.php:155` | Detail order (batch by order_sn) |
| `get_package_detail` | GET | `/api/v2/order/get_package_detail` | `ShopeeChannel.php:175` | Detail paket pengiriman |
| `getReturnList()` | GET | `/api/v2/returns/get_return_list` | `ShopeeChannel.php:192` | List retur/refund |
| `getReturnDetail()` | GET | `/api/v2/returns/get_return_detail` | `ShopeeChannel.php:197` | Detail retur (termasuk `return_solution`: 0=Retur&Refund, 1=Refund saja) |
| `getReverseTrackingInfo()` | GET | `/api/v2/returns/get_reverse_tracking_info` | `ShopeeChannel.php:204` | Tracking barang retur |
| `confirmReturn()` | POST | `/api/v2/returns/confirm` | `ShopeeChannel.php:211` | Konfirmasi retur |
| refresh token | POST | `/api/v2/auth/access_token/get` | `ShopeeChannel.php:353` | Tukar refresh_token → access_token baru |
| `get_shop_info` | GET | `/api/v2/shop/get_shop_info` | `ShopeeChannel.php:132` | Info toko |

Field response `get_escrow_detail` yang **sudah dipetakan** di `MarketplaceSyncService.php:273-305` (artinya field ini terverifikasi ada di response nyata, karena dipakai di production): `buyer_payment_amount`/`buyer_paid_amount`, `commission_fee`, `service_fee`/`credit_card_promotion`, `transaction_fee`, `seller_voucher_rebate`/`seller_voucher`, `seller_absorbed_coin_discount`/`seller_coin_cash_back`, `actual_shipping_fee`/`estimated_shipping_fee`, `shopee_shipping_rebate`/`shipping_fee_rebate`, `reverse_shipping_fee`, `activity_fee`/`ams_commission_fee`, `drc_adjustable_refund`/`seller_return_refund_amount`, `escrow_tax`, `final_income`/`escrow_amount`, `escrow_release_time`, `settlement_time`.

### B.2 — Kebutuhan yang BELUM ada di kode dan BELUM bisa diverifikasi ke dokumentasi resmi

Ini murni daftar **kebutuhan fungsional**, bukan nama endpoint — saya sengaja tidak menebak nama/path karena tidak bisa diverifikasi ke `open.shopee.com` pada sesi ini:

1. Daftar/riwayat pendapatan order dalam rentang tanggal (income list, bukan per-order satu-satu) — untuk sinkronisasi massal, bukan loop per order seperti sekarang.
2. Status pelepasan dana / daftar settlement periode tertentu yang **tidak terikat ke satu order** (payout batch, adjustment non-order).
3. Detail refund dan dampaknya ke pendapatan seller setelah settlement (yang ada baru `returns/get_return_*`, belum tentu mencakup dampak finansial pasca-settlement).
4. Wallet/saldo seller & riwayat transaksi wallet.
5. Push/webhook code khusus untuk event finansial (income released, payout) — push code yang sudah ditangani di `WebhookController.php` (0,1,3,4,5,10,12,15,23,24,25,29,30,37,47) **tidak termasuk** kode untuk event finansial.

**Wajib**: sebelum implementasi apa pun untuk poin 1-5, Anda perlu membuka `open.shopee.com` sendiri (atau memberi saya akses lain) untuk memverifikasi nama endpoint, versi, parameter, dan response field-nya. Saya tidak akan menuliskan nama endpoint tebakan di rencana implementasi manapun.

---

## C. API yang Direkomendasikan

| Kebutuhan | Status API | Waktu Dipanggil | Data yang Disimpan | Prioritas |
|---|---|---|---|---|
| Detail fee & net income per order | ✅ Sudah ada (`get_escrow_detail`) — **tinggal dijadwalkan otomatis**, belum perlu API baru | Setelah order `COMPLETED`/`SHIPPED`/`TO_CONFIRM_RECEIVE` (kondisi sudah ada di `syncSettlements()`) | Semua kolom `marketplace_order_settlements` | **Wajib — tapi ini gap operasional (scheduler), bukan gap API** |
| Income/settlement list periode (bukan per-order) | ❌ Belum ada di kode, belum diverifikasi | Sinkronisasi harian (batch) | Adjustment non-order, ringkasan payout | **Diperlukan untuk rekonsiliasi** — verifikasi dulu ke docs resmi |
| Refund pasca-settlement | Sebagian ada (`get_return_*`), dampak finansial pasca-settlement belum diverifikasi | Setelah `return_updates_push` webhook | Refund amount, jurnal pembalik | **Wajib** |
| Wallet/saldo & riwayat transaksi | ❌ Belum ada, belum diverifikasi | Sinkronisasi berkala (harian) | Saldo tertahan vs cair | **Opsional untuk laporan lanjutan** — hanya jika `marketplace_order_settlements` + income list belum cukup untuk rekonsiliasi kas |
| Push event finansial (income released) | ❌ Belum ada handler | Real-time via webhook | Trigger re-sync settlement satu order | **Diperlukan untuk rekonsiliasi** (mengurangi ketergantungan pada polling) |
| Detail order/produk/harga | ✅ Sudah ada, lengkap | Sudah terjadwal | — | **Tidak diperlukan tambahan** — data sudah tersedia dari `get_order_detail` |

Klasifikasi:
- **Wajib tahap pertama**: jadwalkan `syncSettlements()` yang sudah ada (bukan API baru); tambahkan webhook/command untuk refund pasca-settlement.
- **Diperlukan untuk rekonsiliasi**: income/settlement list periode + push event finansial — endpoint harus diverifikasi ke docs resmi dulu.
- **Opsional lanjutan**: wallet/saldo.
- **Tidak diperlukan**: endpoint order/produk — sudah tercakup.

---

## D. Gap Analysis

| Area | Gap yang ditemukan |
|---|---|
| **Database** | Tiga sumber finansial paralel (`marketplace_order_settlements`, `mp_incomes`, `sales_invoices`) tanpa FK silang. `marketplace_order_settlements.channel_order_id` unique **global**, bukan per store/channel. Tidak ada tabel untuk adjustment/settlement non-order. Tidak ada histori perubahan nilai (raw_json disimpan tapi tanpa versioning). |
| **Model** | `marketplace_orders.store_id` FK ke `marketplace_stores` (skema lama), sementara `marketplace_order_settlements.store_id` dan `marketplace_returns.store_id` FK ke `stores` (skema baru) — dua skema toko berjalan paralel, berisiko salah join. |
| **Service** | `syncSettlements()` sudah ada tapi tidak dipanggil dari mana pun secara otomatis (hanya endpoint controller manual). Tidak ada service untuk income-list/adjustment batch (karena API-nya sendiri belum diverifikasi). |
| **Job/Scheduler** | `marketplace:sync-orders` terdaftar **dua kali** dengan jadwal berbeda (`Kernel.php` hourly + `routes/console.php` setiap 5 menit) — lihat bug #1. Tidak ada scheduler untuk settlement/escrow sama sekali. |
| **Accounting** | `JournalService.php` tidak menyentuh Shopee sama sekali. `MarketplacePayoutService` hanya mencatat 1 baris lump-sum (Dr Bank/Cr Piutang Marketplace), tidak breakdown fee. Chart of accounts belum punya akun terpisah untuk komisi/service fee/voucher/subsidi ongkir/refund/adjustment — hanya ada akun generik "Biaya Marketplace". |
| **Reporting** | Tidak ditemukan laporan laba-rugi per marketplace atau laporan pendapatan bersih per order yang menarik dari `marketplace_order_settlements` — perlu dikonfirmasi apakah ada di modul reporting lain yang belum tercakup di audit ini. |
| **Error handling** | `syncSettlements()` sudah punya try/catch per order + log, tapi error tidak dikategorikan (mis. token expired vs order belum eligible vs API down) — semua masuk hitungan `errors` generik. |
| **Monitoring** | Ada `marketplace_sync_logs` (tabel generik untuk semua log sync), tapi tidak ada alert/threshold untuk fee yang tiba-tiba berubah drastis atau selisih order vs settlement. |

### Bug/risiko kritis yang ditemukan (dengan lokasi persis)

1. **Scheduler duplikat untuk `marketplace:sync-orders`**: terdaftar di `app/Console/Kernel.php` (`->hourly()`) **dan** `routes/console.php` (`->everyFiveMinutes()`). Keduanya pakai `withoutOverlapping()` sehingga tidak akan jalan bersamaan satu sama lain dalam satu proses command yang sama, tapi ini dua titik pendaftaran terpisah yang membingungkan untuk maintenance dan berisiko drift kalau salah satu diubah tanpa mengubah yang lain.
2. **Split-brain FK toko**: `database/migrations/2025_12_04_102000_create_marketplace_orders_table.php` → `store_id` constrained ke `marketplace_stores`. Tapi `database/migrations/2026_06_09_013006_create_marketplace_order_settlements_table.php` dan `2026_07_12_030215_create_marketplace_returns_table.php` → `store_id` constrained ke `stores`. Ini dua sistem toko berbeda yang hidup berdampingan.
3. **Unique constraint settlement bersifat global**: `2026_06_09_013006_create_marketplace_order_settlements_table.php` → `$table->unique('channel_order_id')` — tidak di-scope per `store_id`/channel, berisiko konflik kalau `order_sn` dari channel/toko berbeda pernah bertabrakan.
4. **Journal tidak punya unique constraint sumber**: `2026_01_14_123236_create_journals_table.php` hanya `index(['source_type','source_id'])`, bukan `unique`. Idempotency jurnal payout saat ini murni mengandalkan cek status di level aplikasi (`MarketplacePayoutService::post()` — cek `status === 'posted'` sebelum posting ulang), bukan constraint DB. Race condition tetap mungkin kalau dua proses membuat dua baris `MarketplacePayout` berbeda untuk transaksi yang sama.
5. **Settlement sync tidak terjadwal**: tidak ditemukan `schedule->command(...)` maupun `schedule->call(...)` yang memanggil `syncSettlements()` — satu-satunya pemicu adalah `MarketplaceController::syncSettlements()` (manual, via HTTP request).
6. **Tidak ada webhook untuk event finansial**: daftar push code yang ditangani (`WebhookController.php`, baris 62-77) hanya mencakup order/tracking/item/booking/return/shipping — tidak ada income release/payout.
7. **`.env.example` tidak punya key Shopee** (`SHOPEE_PARTNER_ID`, dst tidak ditemukan) meski `config/shopee.php` membacanya — bukan bug fungsional (karena token asli disimpan di kolom `stores.credentials`, bukan `.env`), tapi menyulitkan onboarding developer baru yang perlu tahu variabel apa saja yang relevan.

---

## E. Rencana Implementasi (usulan urutan, menunggu persetujuan)

1. **Verifikasi endpoint yang masih kosong** (Bagian B.2) ke `open.shopee.com` — blocker harus selesai duluan sebelum desain final Tahap C bisa dikunci.
2. **Perbaiki gap operasional dulu (tanpa API baru)**: jadwalkan `syncSettlements()` yang sudah ada, supaya `marketplace_order_settlements` terisi otomatis — ini value langsung tanpa risiko baru.
3. **Desain & migrasi database tambahan** (Bagian G) — setelah disetujui, baru dibuat migration-nya (belum sekarang).
4. **Bangun service jembatan ke accounting** (baru): dari `marketplace_order_settlements` → breakdown jurnal per akun (bukan lump-sum seperti `MarketplacePayoutService` sekarang).
5. **Tambahkan idempotency di level DB** (unique constraint per store pada settlement, reference unik pada journal) sebelum volume data bertambah.
6. **Refund/adjustment sync** setelah endpoint terverifikasi.
7. **Reporting**: laporan laba-rugi marketplace & pendapatan bersih per order, dibangun di atas data yang sudah rekonsil.
8. **Konsolidasi tiga jalur data finansial** (`marketplace_order_settlements` vs `mp_incomes` vs `sales_invoices`) — perlu keputusan bisnis, lihat Bagian J.

---

## F. Daftar File yang Akan Diubah (usulan, belum dieksekusi)

| File | Alasan perubahan | Dampak ke modul lain |
|---|---|---|
| `app/Console/Kernel.php` | Tambah/samakan jadwal `syncSettlements()`; audit ulang duplikasi `marketplace:sync-orders` | Perlu koordinasi dengan `routes/console.php` agar tidak duplikat |
| `routes/console.php` | Sama seperti di atas — pertimbangkan satu sumber jadwal saja | Perubahan jadwal order sync bisa pengaruhi command lain yang `withoutOverlapping` |
| `app/Services/MarketplaceSyncService.php` | Kemungkinan tambah method untuk income-list/adjustment batch (setelah endpoint terverifikasi) | Dipakai oleh `MarketplaceController` dan command baru |
| `app/Services/Accounting/` (service baru, nama TBD) | Jembatan `marketplace_order_settlements` → jurnal breakdown per akun | Perlu akun COA baru (lihat Bagian J) — tidak menyentuh `JournalService.php` inti |
| `database/seeders/AccountSeeder.php` | Tambah akun COA breakdown fee (jika disetujui) | Tidak retroaktif ke data lama tanpa migration data terpisah |
| `app/Http/Controllers/WebhookController.php` + `app/Jobs/ProcessShopeeWebhookJob.php` | Tambah handler push code finansial (setelah kode push diverifikasi ke docs resmi) | Menambah cabang baru di job yang sudah ada dedup key — perlu pastikan tidak pecah dedup logic |
| `.env.example` | Tambah dokumentasi key `SHOPEE_PARTNER_ID`, dst | Non-fungsional, hanya dokumentasi |

Migration baru (lihat Bagian G) akan didaftarkan terpisah setelah disetujui — **tidak dibuat pada tahap ini**.

---

## G. Rancangan Database (usulan — belum ada migration dibuat)

Mengikuti konvensi project yang sudah ada (decimal `15,2` untuk uang, `raw_json` untuk simpan response mentah, `synced_at` untuk jejak sinkronisasi, FK ke `stores` bukan `marketplace_stores` — mengikuti skema yang lebih baru).

### G.1 Perbaikan pada tabel existing (bukan tabel baru)
- `marketplace_order_settlements`: ubah `unique('channel_order_id')` → `unique(['store_id','channel_order_id'])` agar tidak konflik lintas toko/channel. **Perlu keputusan**: apakah aman diubah mengingat sudah ada data live? (lihat Bagian J)
- `journals`: pertimbangkan `unique(['source_type','source_id'])` alih-alih index biasa, KHUSUS untuk source_type yang memang 1:1 (mis. `marketplace_payout`) — tidak bisa dipaksakan ke semua source_type tanpa cek data existing dulu.

### G.2 Tabel baru yang dipertimbangkan (nama tentatif, menunggu persetujuan)

**`marketplace_settlement_batches`** — untuk item B.2 (settlement/adjustment yang tidak terikat satu order), hanya dibuat setelah endpoint terverifikasi:
- `store_id` (FK `stores`), `channel`, `batch_reference` (unik per store+channel), `period_start`, `period_end`, `total_amount`, `status`, `raw_response` (json), `synced_at`, timestamps.

**`marketplace_finance_sync_logs`** — opsional, atau cukup perluas `marketplace_sync_logs` yang sudah ada (kolom `action`, `status`, `payload` sudah generik dan bisa dipakai untuk `sync_settlements`, seperti sudah dipakai sekarang di `MarketplaceSyncService::log()`).

### G.3 Klasifikasi kolom (prinsip yang harus dipegang di semua tabel finansial)
- **Langsung dari API** (jangan dihitung manual): `commission_fee`, `service_fee`, `seller_voucher`, `shipping_fee_subsidy`, `escrow_tax`, `final_income`, `escrow_amount`/`buyer_payment_amount`, `settlement_time`/`escrow_release_time`.
- **Hasil kalkulasi lokal** (boleh dihitung, tapi harus ditandai jelas beda kolom): margin, net income setelah HPP, dsb — **tidak boleh menimpa kolom yang berasal dari API**.
- **Tidak boleh dihitung sendiri**: nilai final_income/escrow_amount — ini harus selalu dari API, karena formula Shopee bisa berubah tanpa pemberitahuan.
- Estimasi (nilai order sebelum settlement, mis. `marketplace_orders.net_payout_estimated` yang sudah ada) **tidak boleh dicampur** dengan nilai final dari `marketplace_order_settlements` tanpa penanda status (`is_estimate` vs `is_final`) — project sudah punya pemisahan ini secara implisit lewat 2 tabel berbeda, harus dipertahankan, jangan digabung jadi satu kolom.

### G.4 Strategi histori jika nilai Shopee berubah (mis. setelah refund pasca-settlement)
Saat ini `updateOrCreate` di `syncSettlements()` (baris 273) **menimpa** baris lama tanpa jejak nilai sebelumnya (`raw_json` juga ikut tertimpa). Rekomendasi: sebelum overwrite, simpan snapshot lama ke tabel histori (`marketplace_order_settlement_histories` atau sejenis) — ini keputusan yang perlu persetujuan karena menambah beban tulis di setiap sync.

---

## H. Rancangan Sinkronisasi

- **Initial backfill**: `syncSettlements()` sudah punya `limit(200)` per pemanggilan dan filter `whereDoesntHave('settlement')` — cocok untuk backfill bertahap, tapi tanpa command khusus perlu dipanggil manual berkali-kali. Rekomendasi: bungkus jadi command dengan opsi `--all` yang looping sampai habis.
- **Incremental sync**: jadwalkan `syncSettlements()` mengikuti pola `marketplace:sync-orders` yang sudah ada (`withoutOverlapping()`), idealnya dipicu **setelah** `sync-orders` selesai per store (order harus `COMPLETED` dulu, sesuai kondisi yang sudah ada di kode).
- **Re-sync**: karena `updateOrCreate` berbasis `channel_order_id`, re-sync otomatis idempotent di level DB row — tapi lihat G.4 soal histori nilai yang hilang.
- **Refund sync**: baru bisa didesain detail setelah endpoint refund pasca-settlement diverifikasi (Bagian B.2).
- **Settlement non-order**: sama, menunggu verifikasi endpoint.
- **Retry & backoff**: `syncSettlements()` saat ini **tidak ada retry** — error langsung di-log dan lanjut ke order berikutnya (`errors++`, `continue`). Untuk API rate-limit, ini berisiko order tersebut baru ke-sync di run berikutnya (1 jam kemudian jika ikut jadwal order), bisa cukup, tapi perlu exponential backoff untuk error 5xx/timeout eksplisit.
- **Idempotency & unique key**: sudah dipegang dengan baik oleh `channel_order_id` (perlu diperbaiki jadi per-store, lihat G.1), tapi journal-side belum punya constraint DB (lihat D poin 4).
- **Token expired**: sudah ditangani baik (`ensureFreshToken()` + lock). Perlu dipastikan `syncSettlements()` juga memanggil jalur yang sama (perlu dicek langsung apakah `driver->getEscrowDetail()` melewati `ensureFreshToken()` — dari kode `get()`/`post()` helper di `ShopeeChannel.php` kemungkinan besar ya, karena dipakai semua endpoint, tapi perlu konfirmasi eksplisit sebelum implementasi finance baru).
- **Rate limit**: tidak ditemukan penanganan rate-limit eksplisit (mis. baca header `X-Rate-Limit` atau backoff berbasis response Shopee) di `ShopeeChannel.php` — perlu ditambahkan terutama untuk endpoint income-list yang mungkin dipanggil lebih sering.
- **Response parsial**: `syncSettlements()` sudah menangani per-order try/catch, jadi kegagalan satu order tidak menghentikan batch — pola ini baik dan harus dipertahankan untuk endpoint finance baru.
- **Order belum eligible**: sudah difilter di query (`whereIn('order_status', ['COMPLETED','SHIPPED','TO_CONFIRM_RECEIVE'])`) — perlu dikonfirmasi ke docs resmi apakah `get_escrow_detail` memang hanya valid untuk status ini atau ada status lain yang lebih tepat.
- **Refund setelah settlement**: belum ada mekanisme — ini salah satu keputusan besar yang perlu didesain di Bagian J.

---

## I. Test Plan (usulan)

- **Unit test**: parsing response `get_escrow_detail` → mapping ke kolom `marketplace_order_settlements` (termasuk semua fallback field seperti `service_fee ?? credit_card_promotion`); kalkulasi kolom turunan (jika ada) tidak menimpa kolom sumber API.
- **Feature test**: `MarketplaceSyncService::syncSettlements()` — order eligible ter-sync, order belum eligible dilewati, order yang sudah punya settlement tidak diproses ulang (`whereDoesntHave('settlement')`).
- **Integration test**: end-to-end dari job/command terjadwal → API (mock) → DB → (setelah ada) jurnal.
- **Idempotency test**: panggil `syncSettlements()` dua kali berturut-turut dengan response API sama → pastikan tidak ada baris dobel, dan (setelah jurnal breakdown dibuat) tidak ada jurnal dobel.
- **Duplicate job test**: jalankan job/command sync bersamaan (simulasi overlap `Kernel.php` vs `routes/console.php`) → pastikan `withoutOverlapping()` benar-benar mencegah race, atau data tetap konsisten kalau race terjadi.
- **Refund test**: setelah endpoint refund pasca-settlement diverifikasi — pastikan menghasilkan jurnal pembalik yang tertelusur, bukan overwrite diam-diam.
- **Fee berubah test**: simulasikan `get_escrow_detail` mengembalikan nilai berbeda untuk order yang sudah pernah di-sync → pastikan histori (jika diimplementasikan) tercatat, bukan hilang.
- **Token expired test**: mock token kedaluwarsa saat `syncSettlements()` berjalan → pastikan `ensureFreshToken()`/lock bekerja, tidak menghasilkan error auth yang membanjiri log.
- **Pagination test**: khusus endpoint income-list baru (setelah diverifikasi) — pastikan semua halaman tertarik, tidak ada yang terlewat saat `more`/`next_cursor` salah dibaca (pola ini sudah ada bug-nya sekali di `syncOrders()` — perlu direplikasi dengan hati-hati).
- **Settlement tanpa order test**: untuk `marketplace_settlement_batches` (jika dibuat) — pastikan tidak dipaksa link ke `marketplace_orders` kalau memang tidak ada order terkait.
- **Order belum eligible test**: pastikan `syncSettlements()` tidak memanggil API untuk order yang belum `COMPLETED`/`SHIPPED`/`TO_CONFIRM_RECEIVE`.
- **UAT**: bandingkan total `final_income` dari `marketplace_order_settlements` dengan mutasi bank riil (dari `MarketplacePayoutService`) untuk 1 periode penuh, sebelum dianggap siap produksi untuk jurnal otomatis.

---

## J. Pertanyaan / Keputusan yang Dibutuhkan dari Anda

1. **Akses dokumentasi Shopee**: karena `open.shopee.com` diblokir dari sisi saya, endpoint di Bagian B.2 tidak bisa saya verifikasi. Apakah Anda bisa membuka sendiri dan mengirim isi halaman API Payment/Finance yang relevan, atau ada cara lain (mis. akun developer Shopee Anda punya export dokumentasi/Swagger internal)?
2. **Migrasi unique constraint `marketplace_order_settlements.channel_order_id`**: mengubah dari global unique → per-store unique berpotensi bentrok kalau ternyata SUDAH ada data lama yang (secara tidak sengaja) punya `channel_order_id` sama di lebih dari satu `store_id`. Perlu saya cek datanya dulu, atau Anda sudah yakin ini aman?
3. **Konsolidasi tiga jalur data finansial** (`marketplace_order_settlements` vs `mp_incomes` vs `sales_invoices`): apakah `mp_incomes` dan jalur XLSX `sales_invoices` akan **dipensiunkan** setelah sync API otomatis berjalan, atau tetap dipakai sebagai jalur cadangan/silang-cek manual? Ini keputusan bisnis, bukan teknis.
4. **Breakdown jurnal per fee**: saat ini `MarketplacePayoutService` mencatat 1 baris lump-sum. Apakah Anda mau breakdown penuh per akun (komisi, service fee, voucher, ongkir, refund, adjustment terpisah di jurnal) seperti prinsip Tahap 7, atau cukup breakdown minimal (gross sales, total fee, net) untuk tahap pertama?
5. **Refund pasca-settlement**: kebijakan akuntansi — apakah refund setelah dana sudah cair dicatat sebagai jurnal pembalik penuh, atau sebagai piutang baru ke seller (potong pembayaran berikutnya)? Ini menentukan desain tabel adjustment.
6. **Histori perubahan nilai settlement** (G.4): apakah perlu tabel histori terpisah (menambah beban tulis tiap sync), atau cukup mengandalkan `raw_json` + `updated_at` saat ini (nilai lama akan hilang saat re-sync)?
7. **Skema toko ganda** (`marketplace_stores` vs `stores`): apakah project memang sedang dalam migrasi bertahap dari skema lama ke baru, dan tabel finansial baru harus ikut skema `stores` (baru) — mohon konfirmasi supaya tidak menambah FK ke skema yang akan dipensiunkan.
8. **Prioritas fase pertama**: apakah cukup menjadwalkan `syncSettlements()` yang sudah ada dulu (quick win, tanpa API baru) sebagai rilis pertama, sebelum lanjut ke income-list/refund yang perlu verifikasi docs?

---

**Status: menunggu persetujuan Anda sebelum implementasi apa pun.** Tidak ada kode, migration, atau `.env` yang diubah selama audit ini.
