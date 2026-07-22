# Laporan Implementasi Fase 1 — `marketplace:sync-settlements`

Status kode: **ditulis, direview manual secara ketat, TAPI BELUM DIJALANKAN.** Migration belum dieksekusi (`php artisan migrate` tidak saya jalankan). Tidak ada panggilan ke API Shopee nyata. Tidak ada perubahan ke database development. Tidak ada scheduler ditambahkan.

---

## ⚠️ Keterbatasan penting yang harus Anda tahu dulu

**Saya tidak bisa menjalankan `php artisan test` di sesi ini** — sandbox kerja saya tidak punya runtime PHP terpasang, dan saya tidak punya akses `root`/`sudo` untuk menginstalnya (`apt-get install php-cli` ditolak: `Permission denied` pada dpkg lock). Ini murni keterbatasan lingkungan kerja saya, bukan pilihan untuk melewati langkah yang Anda minta.

Sebagai gantinya saya melakukan **review manual baris-demi-baris** terhadap seluruh kode yang ditulis: menelusuri setiap alur logika terhadap skema database nyata (termasuk query `PRAGMA table_info` ke database dev untuk memastikan nullability kolom), membaca source code Laravel 12 yang ter-install (`vendor/laravel/framework`) untuk memverifikasi perilaku `dropUnique()`, `setAttribute()` untuk cast `decimal`, dan `Http` client, serta menjejaki setiap skenario test secara manual terhadap kode yang ditulis.

**Review manual ini menemukan satu bug nyata sebelum sempat jadi masalah produksi**: 13 kolom fee di `marketplace_order_settlements` (`commission_fee`, `final_income`, dll) ternyata **NOT NULL** di skema (terverifikasi lewat `PRAGMA table_info`, bukan asumsi) — kalau `decimalValue()` mengembalikan `null` murni (sesuai Koreksi 6) langsung disimpan ke kolom ini, insert akan **gagal dengan integrity error**. Saya perbaiki dengan menambah guard `nn()` yang secara eksplisit dan terdokumentasi mengubah `null → '0.00'` HANYA di titik penyimpanan untuk 13 kolom NOT NULL ini — `decimalValue()` sendiri tetap murni (null tetap null, teruji terpisah lewat Reflection). Detail lengkap di Bagian D (Risiko).

**Rekomendasi saya**: jalankan `composer test` (atau perintah spesifik di Bagian C) di komputer Anda sendiri sebelum lanjut ke migration/UAT API nyata. Kalau ada test yang gagal, kirim outputnya ke saya dan saya perbaiki.

---

## A. File yang Berubah

### Diubah (existing)

| File | Alasan |
|---|---|
| `app/Services/Channels/Shopee/ShopeeChannel.php` | Tambah `_meta` (http_status, retry_after) di `doGet()`/`doPost()` lewat method baru `withHttpMeta()` — aditif murni, sudah diaudit tidak ada caller lain yang iterasi root response atau simpan root response mentah (Koreksi 3). Field `error`/`message`/`response`/`status` (lama) tidak diubah. |
| `app/Services/MarketplaceSyncService.php` | Perluasan `syncSettlements()` (signature baru: `orderSn`, `resync`, `limit`, `afterId`) + 6 method privat baru: `getEscrowDetailWithRetry()`, `validateEscrowIncome()`, `mapEscrowSettlement()`, `decimalValue()`, `normalizeShopeeTimestamp()`, `logMaterialChanges()`, `nn()`. Transaction sekarang per-order (bukan implisit per-batch seperti sebelumnya karena tidak ada transaction sama sekali di kode lama). Tidak menyentuh method sync lain (`syncOrders()`, ads, dll). |

### Baru (belum pernah ada)

| File | Baris | Isi |
|---|---|---|
| `app/Console/Commands/Marketplace/SyncSettlementsCommand.php` | 418 | Command `marketplace:sync-settlements` lengkap: validasi opsi, resolusi `--order` (termasuk deteksi ambigu lintas toko), lock per toko (900 detik), loop `--all` berbasis cursor dengan pengaman 20 batch/12 menit, `--inspect` masked, exit code sesuai hasil. |
| `database/migrations/2026_07_23_000001_change_unique_constraint_on_marketplace_order_settlements.php` | 182 | Migration `unique(channel_order_id)` → `unique(store_id, channel_order_id)`, dengan preflight validation di `up()` (3 pengecekan) dan `down()` (1 pengecekan, atomic), pencarian nama index lama driver-aware (SQLite `PRAGMA`, MySQL `information_schema`). **BELUM DIJALANKAN.** |
| `tests/Feature/Console/MarketplaceSyncSettlementsCommandTest.php` | 275 | 16 test skenario command (validasi opsi, `--store`, `--order` ambigu/eligible, lock, `--all` no-progress, exit code). |
| `tests/Feature/Services/MarketplaceSyncServiceSettlementTest.php` | 530 | 22 test skenario service (sync sukses, resync, error handling, transaction per-order, validasi response, normalisasi nominal & timestamp, retry, tanpa jurnal, raw response, `_meta` terpisah dari `raw_json`). |
| `tests/Feature/Database/MarketplaceOrderSettlementsUniqueConstraintMigrationTest.php` | 175 | 5 test migration (preflight `up()` menolak duplikat/store invalid, `up()` berhasil untuk data bersih, `down()` menolak kalau ada konflik + atomic, `down()` berhasil kalau aman). |
| `tests/Unit/Services/Channels/Shopee/ShopeeChannelMetaTest.php` | 141 | 5 test `_meta` (status 200/429/500/non-JSON tetap tercatat, field Shopee asli tidak berubah) + 1 test regresi `ensureFreshToken()` masih terpanggil. |

**Total: 49 test skenario ditulis** (melebihi 30 yang diminta — beberapa poin di daftar Anda saya pecah jadi beberapa test untuk kejelasan, mis. duplikat vs store invalid di migration).

### Tidak disentuh sama sekali (sesuai instruksi)

`app/Console/Kernel.php`, `bootstrap/app.php`, `routes/console.php`, `AccountSeeder.php`, `JournalService.php`, `MarketplacePayoutService.php`, `MarketplaceReconcileCommand.php`, jalur XLSX (`ImportShopeeIncomeService.php`, `ImportShopeeOrdersService.php`, `mp_incomes`/`sales_invoices`), FK `marketplace_orders`.

### Catatan di luar sesi ini

`git status` menunjukkan `app/Http/Controllers/Production/QcController.php` dan `resources/views/production/qc/sewing_edit.blade.php` berstatus "modified" — **ini BUKAN dari saya**, sudah ada sebelum sesi ini dimulai (di luar cakupan Shopee finance sama sekali). Juga ada beberapa file `database/.fuse_hidden*` — artefak sementara dari proses baca read-only saya ke `database.sqlite` lewat filesystem mount, aman diabaikan/dihapus.

---

## B. Hasil Test

**Tidak dieksekusi** (lihat keterangan di atas). Yang saya lakukan sebagai gantinya:

1. Tracing manual tiap skenario test terhadap kode yang ditulis, baris demi baris.
2. Verifikasi terhadap skema database NYATA (`PRAGMA table_info` ke `database.sqlite`) untuk `marketplace_orders` dan `marketplace_order_settlements` — bukan asumsi dari migration file saja (mengingat sudah ada preseden *drift* dev/prod di `sales_invoices`, lihat audit sebelumnya).
3. Verifikasi perilaku Laravel 12 langsung dari `vendor/laravel/framework` source untuk: `Blueprint::dropUnique()` (string vs array), `HasAttributes::setAttribute()` untuk cast `decimal` (konfirmasi: setter TIDAK memformat ulang nilai, jadi string dari `decimalValue()` tersimpan apa adanya), `Http\Client\ConnectionException` (constructor standar).
4. Menemukan & memperbaiki 1 bug nyata (kolom NOT NULL, lihat atas) SEBELUM dilaporkan sebagai selesai.

**Test yang gagal**: tidak dapat diketahui tanpa eksekusi nyata. Saya TIDAK mengklaim "semua lulus" — status sebenarnya adalah **"ditulis & direview manual, status eksekusi belum terverifikasi."**

---

## C. Command UAT Manual yang Disarankan

Jalankan berurutan, di komputer Anda (bukan di sandbox saya):

```bash
# 1. Jalankan test dulu — WAJIB sebelum lanjut ke bawah
composer test
# atau lebih spesifik/cepat untuk file baru saja:
php artisan test --filter=SyncSettlements
php artisan test tests/Unit/Services/Channels/Shopee/ShopeeChannelMetaTest.php
php artisan test tests/Feature/Database/MarketplaceOrderSettlementsUniqueConstraintMigrationTest.php

# 2. Review migration (belum dijalankan) — baca dulu isinya, lalu:
php artisan migrate --pretend --path=database/migrations/2026_07_23_000001_change_unique_constraint_on_marketplace_order_settlements.php
# (--pretend menampilkan SQL yang AKAN dijalankan tanpa benar-benar mengeksekusi)

# 3. Setelah test lulus DAN Anda approve migration, baru jalankan migration sungguhan:
php artisan migrate --path=database/migrations/2026_07_23_000001_change_unique_constraint_on_marketplace_order_settlements.php
# Migration akan BERHENTI dengan pesan jelas kalau ada data tidak aman — baca pesannya kalau gagal.

# 4. UAT manual command — SATU order dulu, sambil verifikasi raw response:
php artisan marketplace:sync-settlements --store=4 --order=<channel_order_id asli> --inspect

# 5. Kalau langkah 4 OK, backfill bertahap (sesuai instruksi Anda sebelumnya):
php artisan marketplace:sync-settlements --store=4 --limit=10
php artisan marketplace:sync-settlements --store=4 --limit=50
php artisan marketplace:sync-settlements --store=4 --all
# lalu ulangi untuk store=5 setelah store=4 stabil
```

Saya **tidak menjalankan** langkah 2 dst di atas — semuanya menunggu Anda.

---

## D. Risiko Tersisa

1. **Test belum tereksekusi** (Bagian B) — risiko utama sesi ini. Saya percaya diri pada logikanya berdasarkan review manual, tapi hanya eksekusi nyata yang bisa memastikan tidak ada typo/kesalahan sintaks yang lolos dari mata.
2. **Guard `nn()` (null→0.00 untuk kolom NOT NULL)** — ini technical debt yang didokumentasikan jelas di kode (lihat komentar method `nn()`), tapi secara bisnis berarti: kalau Shopee API benar-benar tidak mengirim suatu field fee, sistem TIDAK BISA membedakan itu dari "fee-nya memang 0" di kolom database saat ini. Kalau distingsi ini penting untuk rekonsiliasi nanti, perlu migration terpisah (di luar approved scope Fase 1) untuk membuat kolom-kolom itu nullable.
3. **Fallback field ganda di `mapEscrowSettlement()`** (mis. `service_fee ?? credit_card_promotion`) masih sama seperti kode lama — BELUM diverifikasi ke response asli Shopee. Prioritas berikutnya: jalankan UAT langkah 4 di atas dengan `--inspect` untuk menangkap satu response nyata, lalu revisi mapping ini.
4. **Nama unique index lama di production** tidak saya asumsikan — migration akan mencarinya sendiri dan berhenti dengan pesan jelas kalau tidak ketemu (Koreksi 9), tapi ini berarti migration BISA gagal di percobaan pertama di production kalau strukturnya beda dari dugaan. Disarankan jalankan `--pretend` dulu (Bagian C langkah 2).
5. **Retry `sleep()` memakai PHP native `sleep()`** (blocking) — untuk command yang dijalankan manual ini wajar, tapi kalau nanti dijadwalkan (scheduler, di luar Fase 1 ini), backoff blocking di dalam satu proses queue/cron perlu dipertimbangkan ulang dampaknya ke worker lain.
6. **Command `marketplace:reconcile-finance`** yang direncanakan untuk laporan rekonsiliasi 3-sumber (dari audit sebelumnya) sengaja belum dibuat — menunggu data settlement nyata dulu, sesuai instruksi Anda.

---

## E. Ringkasan Keputusan Desain Kecil yang Saya Ambil Sendiri (tolong dikonfirmasi)

- `getEscrowDetailWithRetry()` dan seluruh helper baru saya taruh sebagai method **privat di `MarketplaceSyncService`** (bukan class/trait terpisah) — mengikuti pola "abstraction paling sesuai" yang Anda izinkan, karena `$driver` sudah di-resolve di service ini dan tidak ada pola helper class terpisah lain di project untuk hal serupa.
- Command baru **tidak menimpa lock key `sync_store_{id}`** milik `SyncOrdersCommand` — pakai key baru `sync_settlements_store_{id}` supaya sync order & sync settlement toko yang sama bisa jalan paralel tanpa saling blokir.
- `nn()` guard (Bagian D poin 2) adalah keputusan teknis yang saya ambil untuk mencegah bug nyata — bukan permintaan eksplisit Anda, jadi mohon direview apakah pendekatan ini bisa diterima atau Anda mau pendekatan lain (mis. migration nullable terpisah).

---

**Berhenti di sini — menunggu hasil `composer test` dari Anda sebelum lanjut ke migration atau UAT API nyata.**
