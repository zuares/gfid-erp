# Implementasi: PO Draft → GRN → PO Locked

Status: **kode selesai; migration & test suite BELUM dijalankan di environment ini**
(lingkungan kerja tidak memiliki runtime PHP; jalankan perintah di bagian 8 pada mesin Anda).

---

## 1. Ringkasan kondisi sebelum perubahan

- GRN hanya bisa dibuat dari PO `approved` (filter form, `createFromOrder`, dan `validateOptionalPurchaseOrderRelation`).
- PO tidak membuat jurnal; stok/AP/jurnal terbentuk saat **GRN post**.
- PO draft masih bebas **diedit & dihapus**; `PurchaseOrderService::syncLines()` melakukan `lines()->delete()` lalu membuat ulang line → `purchase_order_line_id` berubah setiap edit.
- **Kebocoran harga**: blade PO (`index/show/_form/_table_rows`) dan `PurchaseOrderController::canSeeMoney()` memakai `hasRole(['owner','admin'])` → **admin gudang melihat harga**. Endpoint `getSupplierLastPrice` & print PO juga terbuka untuk admin.
- Tidak ada kolom/penanda lock pada `purchase_orders`.

## 2. Daftar file yang diubah/ditambah

Baru:
- `database/migrations/2026_07_14_090000_add_receiving_lock_to_purchase_orders.php`
- `tests/Feature/GrnFromDraftPoTest.php`

Diubah:
- `app/Models/PurchaseOrder.php` — kolom lock, cast, `isLocked()`, `isReceivableForGrn()`, `receivingStageLabel()`, relasi `lockedBy/firstGrn`.
- `app/Models/User.php` — `canSeePurchasePrices()` (sumber kebenaran tunggal hak harga).
- `app/Services/Purchasing/PurchaseOrderService.php` — `lockForReceiving()`, `maybeUnlock()`, `receivedQtyByLineId()`, `syncLinesLocked()`, guard `update()` (lockForUpdate + supplier terkunci).
- `app/Services/Purchasing/GoodsReceiptService.php` — inject `PurchaseOrderService`; `assertPoReceivable()`, `validateAndEnrichLinesFromPo()` (harga server-side + validasi relasi/over-receipt); lock PO di `create()` & fallback di `post()`; `backfillPricesFromPoLines()` selalu ambil harga PO.
- `app/Http/Controllers/Purchasing/PurchaseReceiptController.php` — izinkan PO draft/approved/closed; tolak cancelled; harga baris selalu server-side; `canSeeMoney` → `canSeePurchasePrices`.
- `app/Http/Controllers/Purchasing/PurchaseOrderController.php` — guard lock pada `edit/update/destroy`; `canSeeMoney` → `canSeePurchasePrices`; gate print PO (`printRaw/printDotMatrix`).
- Blade: `purchase_orders/index,show,_form,_table_rows` dan `purchase_receipts/create` — gerbang harga tunggal + badge `Receiving/Locked` + tombol Terima dari PO draft.

## 3. Desain lock yang dipilih

- **Kolom** `locked_at` (flag otoritatif), `locked_by`, `lock_reason`, `first_grn_id`, `receiving_started_at`. Semua nullable & aditif.
- **Kapan dikunci**: saat **GRN pertama berhasil DISIMPAN (create)**, bukan saat post.
  Alasan: referensi `purchase_order_line_id` sudah lahir begitu GRN draft tersimpan; jika baru dikunci saat post, PO draft masih bisa diedit (`syncLines` delete+recreate) sehingga referensi GRN menjadi orphan. Ada juga **fallback lock saat post** untuk GRN yang mungkin dibuat sebelum fitur ini.
- **UI**: label turunan `receivingStageLabel()` (Draft/Receiving/Partially/Fully Received) + badge `Locked` — bukan status kolom baru.
- **Unlock (konservatif)**: `maybeUnlock()` hanya melepas bila TIDAK ada GRN tersisa, TIDAK ada payment, dan TIDAK ada return. Bila pernah ada jejak (stok/jurnal/payment/return) → tetap terkunci. Default flow tetap terkunci karena GRN normal tidak dapat dihapus.

## 4. Keamanan harga

- Satu gerbang: `User::canSeePurchasePrices()` = `isOwner() || role==='accounting'`. Admin & operating = **false**.
- Diterapkan di: blade PO & GRN, `PurchaseOrderController::canSeeMoney`, `PurchaseReceiptController::canSeeMoney`, endpoint `getSupplierLastPrice`, dan print PO (`abort_unless`).
- **Harga GRN dipaksa server-side dari PO line** di tiga lapis: `buildLinesFromRequest` (controller), `validateAndEnrichLinesFromPo` (service.create), `backfillPricesFromPoLines` (service.post). `unit_price` dari request diabaikan bila baris punya `purchase_order_line_id`.
- Field harga (`unit_price/subtotal/discount/tax/grand_total/last_purchase_price/expense_account_id`) tidak dirender/dikirim untuk role tanpa hak (hidden input hanya muncul saat `canSeeMoney`).

## 5. Perubahan validasi Controller & Service (defense-in-depth)

Controller (`PurchaseReceiptController`):
- `create()`/`createFromOrder()`/`validateOptionalPurchaseOrderRelation()` → PO `draft|approved|closed`, tolak `cancelled`; supplier harus sama; harga baris selalu dari PO.

Service (`GoodsReceiptService::create`, di dalam transaksi):
- `lockForUpdate()` pada PO & PO line.
- `assertPoReceivable()`: PO ada, bukan cancelled, `isReceivableForGrn()`, supplier cocok.
- `validateAndEnrichLinesFromPo()`: PO line milik header PO (bukan PO lain/terhapus), item cocok, `qty_received+qty_reject ≤ outstanding`, `unit_price` diset dari PO.

Service (`PurchaseOrderService::update`):
- `lockForUpdate()`; bila PO `isLocked()` → supplier tidak boleh berubah; line diproses via `syncLinesLocked()` (tanpa `delete()` buta): line ber-referensi GRN tidak boleh hilang, item tidak boleh ganti, qty tidak boleh < received; harga boleh diperbarui.
- Controller `edit/update/destroy` memblokir PO terkunci untuk role tanpa hak harga; `destroy` menolak PO terkunci untuk semua role.

## 6. Dampak stok, jurnal, AP, return, payment (tidak berubah perilakunya)

- GRN draft: belum menambah stok. GRN post: stok + moving-average HPP + `inventory_mutations` (`purchase_receipt`) + jurnal `grn_inv`/`grn_exp` + AP 2101. Unpost: reverse stok (`purchase_receipt_void`) + void jurnal.
- Basis AP (`netDebtByGrn = Σ GRN posted − Σ Return posted`) tidak berubah. Payment tetap butuh GRN posted. Purchase Return & Supplier Invoice tidak disentuh logikanya.
- Karena GRN post tidak pernah bergantung pada status PO, posting dari PO draft menghasilkan stok/jurnal/AP yang sama seperti dari PO approved. `received_status` PO tetap tersinkron (termasuk PO draft).

## 7. Hasil migration

- **Migration BERHASIL dijalankan** (run pertama Anda): `2026_07_14_090000_add_receiving_lock_to_purchase_orders ... 13.31ms DONE`.
- Pemeriksaan schema pada salinan `database_dev.sqlite` sebelumnya juga OK (5 kolom aditif, data lama utuh, rollback bersih).

## 8. Hasil test/UAT (run pertama + perbaikan)

Run pertama Anda:
- ✅ Migration jalan.
- ❌ 16 test `GrnFromDraftPoTest` gagal — **BUKAN karena logika fitur**, tetapi bug di `setUp()` test: COA (mis. 1205) sudah ada dari migration, sehingga `Account::create()` melanggar UNIQUE `accounts.code` sebelum satu assertion pun berjalan (semua error identik di baris 65).
  - **Diperbaiki**: `Account::create` → `Account::firstOrCreate` (juga warehouse/supplier/item pakai `firstOrCreate` + kode unik `GRN*`). Silakan re-run.
- ⚠️ 2 test `PurchaseReturnReplacementTest` gagal (`replacement_status` pending vs partial/received; void guard). **Pre-existing**, bukan dari perubahan ini:
  - working tree Anda sudah "dirty" sebelum pekerjaan ini (banyak file tak saya sentuh ikut `M`: `layouts/*`, `marketplace/*`, `routes/web/purchasing.php`, `PurchaseReturnController.php` +30 baris `searchGrnForReturn`, `purchase_returns/index.blade`).
  - alur replacement dibuat oleh `ReplacementReceiptService` (bukan `GoodsReceiptService::create` saya). Perubahan saya di `post()` hanya penulisan kolom lock non-throwing yang di-gate `purchase_order_id`, dan TIDAK menyentuh `syncReplacementProgress` (penentu `replacement_status`).
- ✅ Test lain lulus: `PurchaseReturnAllocationTest` (8/8), `MarketplaceSyncOrdersCommandTest`, `DummyBulkPrintTest`.

Run kedua (setelah perbaikan setUp): **15 passed, 1 failed**.
- Satu-satunya sisa gagal = `price visibility helper`: `CHECK constraint failed: role` saat insert user `role='accounting'`.
- **Temuan penting**: enum `users.role` = `['sewing','cutting','operating','admin','owner','other']` — **tidak ada `'accounting'`**. Jadi di sistem ini praktis hanya **OWNER (+developer)** yang melihat harga; referensi role `accounting` di `SupplierInvoiceController`/blade adalah branch tak-aktif. `canSeePurchasePrices()` tetap menyertakan `accounting` sebagai future-proof (harmless).
- **Diperbaiki**: assertion `accounting` diuji in-memory (tanpa insert DB). 15 test fungsional inti sudah HIJAU; re-run untuk konfirmasi 16/16.

Perintah re-run (setelah kedua perbaikan):
```bash
php artisan test tests/Feature/GrnFromDraftPoTest.php
```

Keputusan yang perlu Anda ambil (opsional): apakah ingin menambahkan role `accounting` ke enum `users.role`
(agar staf finance non-owner bisa melihat harga), atau membiarkan hanya OWNER yang berhak harga.
Untuk membuktikan 2 kegagalan replacement itu pre-existing (baseline commit):
```bash
git stash push --include-untracked && php artisan test tests/Feature/PurchaseReturnReplacementTest.php ; git stash pop
```
(jika tetap gagal pada HEAD bersih → memang sudah gagal sebelum perubahan ini.)

## 9. Risiko yang masih tersisa

- **Belum dieksekusi**: migration & Pest suite harus dijalankan di mesin Anda; angka UAT belum ada.
- **Edit PO terkunci via UI**: form PO tidak mengirim id line, jadi proteksi granular mengandalkan pencocokan per `item_id`. UI edit penuh untuk PO terkunci sengaja diblokir untuk role tanpa hak harga; owner mengedit melalui path terproteksi. UI "partial edit" khusus belum dibuat.
- **Baris GRN ad-hoc tanpa `purchase_order_line_id`** pada GRN ber-PO masih diizinkan (tanpa enrichment PO) — konsisten dgn perilaku lama; pertimbangkan pembatasan bila tidak diinginkan.
- **Perubahan perilaku admin**: role `admin` kini tidak lagi melihat harga PO (sebelumnya bisa). Ini sesuai permintaan, namun mengubah pengalaman admin lama.
- **FK database**: `first_grn_id`/`locked_by` ditamb以 tanpa constraint FK keras (SQLite dev) — integritas dijaga di level aplikasi.

## 10. Contoh flow sebelum & sesudah

Sebelum:
```
PO Draft → (wajib) Approve → GRN → stok/jurnal/AP.  PO draft bebas edit/hapus.
```
Sesudah (dua jalur didukung):
```
PO Draft → Admin buat GRN → PO otomatis Locked → GRN draft/posted → received_status
PO Draft → Approve → GRN            (jalur lama tetap berjalan)
```
