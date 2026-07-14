# Audit & Perbaikan Modul Marketplace Returns

Tanggal: 2026-07-14
Ruang lingkup: `marketplace/returns` (Shopee) — controller, job, model, migration, view, scheduler.
Prinsip: **data retur tetap disimpan di database** (bukan live-only), anti-duplikat di penyimpanan maupun tampilan.

> Koreksi audit sebelumnya: DB yang dipakai aplikasi adalah `database/database.sqlite`
> (bukan `database_dev.sqlite`). Di DB yang benar, tabel `marketplace_returns` &
> `marketplace_return_items` SUDAH ADA (53 retur, `return_solution` 0=Retur ×40, 1=Refund ×13),
> `return_sn` unik, tanpa duplikat. Jadi temuan "tabel hilang" TIDAK berlaku.

---

## ✅ Bug yang sudah diperbaiki

### 1. Fatal error di `storedRrc()` — model tanpa import  `[controller]`
`MarketplaceReturn::` dipanggil tanpa `use`, ter-resolve ke `App\Http\Controllers\MarketplaceReturn` → Class not found; `/returns/stored` selalu 500.
**Fix:** tambah `use App\Models\MarketplaceReturn;` (+ `MarketplaceOrder`).

### 2. `getReturnList()` tidak membedakan Retur vs Refund  `[controller]`
Endpoint utama UI (`/returns/list`) menjalankan query identik untuk `type=return` & `type=refund` → tab Retur dan Refund menampilkan data sama.
**Fix:** tambah filter `return_solution` sesuai `type` (selaras dengan `storedRrc`).

### 3. Sync manual tidak memecah rentang >15 hari  `[job]`
`SyncMarketplaceReturns` hanya memecah 15-harian saat `fullSync`. Rentang lebar dari tombol Refresh ditolak Shopee → tidak tersimpan.
**Fix:** job SELALU memecah rentang ke jendela ≤15 hari (fullSync maupun manual).

### 4. Tidak ada jaminan anti-duplikat item di DB  `[migration + job]`
`marketplace_return_items` tidak punya unique index; cron per-jam + Refresh manual bersamaan bisa membuat item dobel.
**Fix:** migration baru `2026_07_14_000001_add_unique_index_to_marketplace_return_items`
(unique `marketplace_return_id, item_sku, variation_sku`, dengan dedup defensif dulu),
dan kunci `updateOrCreate` di job diselaraskan (item_name dipindah ke nilai, bukan kunci).

### 5. `live()` salah memperlakukan `return_solution`  `[controller]`
Diperlakukan sebagai string (`str_contains 'REFUND'`), padahal Shopee kirim integer (0/1) → tab Refund live selalu kosong.
**Fix:** deteksi numerik (1 = refund), tetap toleran bila endpoint lawas kirim string.

### 6. Halaman returns menyertakan toko non-Shopee/nonaktif + pagination lintas-toko salah  `[controller + view]`
`index()` memuat `Store::all()`; "Muat Lebih Banyak" memakai offset yang sama ke tiap toko lalu digabung → baris bisa lompat/hilang.
**Fix:** `index()` hanya toko Shopee aktif ber-token; view memuat semua baris sekaligus (`page_size=1000`, dari DB) lalu digabung & diurutkan di klien.

### 7. `confirmAndRestock` tidak idempoten  `[controller]`
Konfirmasi dua kali bisa membuat draf ShipmentReturn ganda.
**Fix:** cek draf existing per `return_sn`; kalau ada, langsung arahkan ke draf itu tanpa konfirmasi ulang.

### 8. Refresh retur lama memberi payload detail ke `processReturn`  `[job]`
Bentuk field item pada `get_return_detail` beda dari list (`model_sku/model_name/amount`) → item bisa tak termap / duplikat SKU-null.
**Fix:** `processReturn($data, $syncItems=false)` untuk jalur refresh — hanya perbarui header, item tidak di-resync (item sudah dari sync list).

### 9. Komentar enum `return_solution` menyesatkan  `[migration]`
Tertulis "1: Refund, 2: Retur & Refund".
**Fix:** komentar diperbaiki → `0 = RETURN_REFUND (Retur & Refund), 1 = REFUND (Refund saja)`.

---

## ⚠️ Perlu langkah manual (tidak bisa dijalankan dari sini)
1. **Jalankan migration** untuk memasang unique index:
   ```
   php artisan migrate
   ```
2. **Lint** (sandbox ini tanpa PHP; brace/paren sudah dicek seimbang):
   ```
   php -l app/Http/Controllers/MarketplaceReturnController.php
   php -l app/Jobs/SyncMarketplaceReturns.php
   ```

## 📝 Catatan (belum diubah — keputusan bisnis, bukan bug)
- KPI "Nilai" memakai `amount_before_discount` (sebelum diskon) sehingga nilai refund bisa overstated. Untuk memakai nilai bersih perlu menyimpan `refund_amount` sebagai kolom baru — dibiarkan agar tidak mengubah model data tanpa konfirmasi.
- File scratch/test di root repo (`scratch_*.php`, `test_*`, `grep_audit_*.txt`) mengotori project; sebaiknya dipindah/`.gitignore` (di luar modul returns).

## File yang diubah
- `app/Http/Controllers/MarketplaceReturnController.php` (import, index filter, getReturnList filter, live() solution, confirmAndRestock idempoten)
- `app/Jobs/SyncMarketplaceReturns.php` (pecah rentang, align upsert item, guard resync item)
- `database/migrations/2026_07_14_000001_add_unique_index_to_marketplace_return_items.php` (baru)
- `database/migrations/2026_07_12_030215_create_marketplace_returns_table.php` (komentar)
- `resources/views/marketplace/returns.blade.php` (muat penuh dari DB)
