# Audit Faktual — Modul Purchasing & Accounting

> Audit kondisi **aktual** berdasarkan kode yang ada. Dokumen ini murni deskriptif — tidak berisi rekomendasi implementasi. Semua klaim dirujuk ke file & baris kode.

Tanggal audit: 13 Juli 2026
Basis: Laravel 12, SQLite (`database_dev.sqlite`)

---

## 0. Ringkasan Temuan Kunci

1. **PO tidak menyentuh akuntansi sama sekali.** `PurchaseOrderService::approve()` **tidak** memposting jurnal apa pun — hanya mengubah `status`. Komentar di kode menyebut posting expense, tetapi jalurnya sudah dinonaktifkan (konstanta `SRC_PO_EXPENSE_APPROVE` di-*comment out*, `JournalService.php:64`).
2. **Titik akuntansi & stok yang sesungguhnya adalah GRN Post.** Stok masuk, jurnal (2101 AP + 1201/1202/1203/1205 inventory), dan basis hutang semuanya terbentuk saat `GoodsReceiptService::post()`.
3. **Supplier Invoice = dokumen bayangan.** `SupplierInvoiceController::post()` **tidak** membuat jurnal apa pun. AP untuk pembayaran dihitung dari GRN posted (`netDebtByGrn`), bukan dari invoice.
4. **Basis hutang (AP) = GRN posted − Return posted**, dihitung ulang (`netDebtByGrn`), bukan dibaca dari saldo buku besar akun 2101.
5. **Guard "GRN dari Draft PO" ada, tetapi hanya di level Controller & hanya untuk header `purchase_order_id`.** Service (`create`/`post`) tidak mengecek status PO. Level baris (`purchase_order_line_id`) juga tidak divalidasi terhadap status PO.

---

## 1. Purchase Order

| Aspek | Nilai aktual |
|---|---|
| Model | `App\Models\PurchaseOrder`, `App\Models\PurchaseOrderLine` |
| Controller | `App\Http\Controllers\Purchasing\PurchaseOrderController` |
| Service | `App\Services\Purchasing\PurchaseOrderService` |

**Status workflow.** Kolom `status`: `draft`, `approved`, `cancelled` (`PurchaseOrder.php:107-120`). Status turunan tambahan:
- `closed` — ditandai kolom `closed_at` (bukan kolom `status`), helper `isClosed()` (`PurchaseOrder.php:123-126`).
- `received_status` — `not_received` / `partial` / `fully_received`, di-*maintain* oleh GRN (`GoodsReceiptService::syncReceivedStatus`).
- `payment_status` — `unpaid` / `partial` / `paid`, di-*maintain* oleh Payment.

Data aktual DB: `status` → `approved`(6), `draft`(1); `received_status` → `fully_received`(6), `not_received`(1).

**Trigger posting / unposting.**
- `create()` → `status = draft` (`PurchaseOrderService.php:48`).
- `approve()` → `status = approved`, set `approved_by/approved_at`. Validasi: setiap baris `allocation=expense` **wajib** punya `expense_account_id`, kalau tidak → `ValidationException` (`PurchaseOrderService.php:160-172`). **Tidak ada jurnal diposting di sini.**
- `unapprove()` → kembali ke `draft`, reset approver.
- `cancel()` → `status = cancelled`, **diblokir bila sudah ada `purchaseReceipts`** (`PurchaseOrderService.php:204-206`).
- Edit/Update/Destroy hanya untuk `draft` (`PurchaseOrderController.php:441, 513, 545`).

**Dampak stok:** Tidak ada. PO tidak membuat mutasi stok.
**Dampak hutang (AP):** Tidak ada. PO tidak menyentuh AP.
**Dampak jurnal:** Tidak ada. `approve()` tidak memanggil `JournalService`.
**Efek samping lain:** `touchLastPrices()` meng-update `items.last_purchase_price` dan `SupplierPrice.last_price` (`PurchaseOrderService.php:393-409`).

> Catatan integritas: `syncLines()` melakukan `lines()->delete()` lalu **membuat ulang** semua baris (`PurchaseOrderService.php:254`). Artinya setiap edit PO menghasilkan `purchase_order_line_id` **baru**. Ini relevan untuk pertanyaan integritas GRN di bagian 9.

---

## 2. Goods Receipt Note (GRN)

| Aspek | Nilai aktual |
|---|---|
| Model | `App\Models\PurchaseReceipt`, `PurchaseReceiptLine`, `PurchaseReceiptQc` |
| Controller | `App\Http\Controllers\Purchasing\PurchaseReceiptController` |
| Service | `App\Services\Purchasing\GoodsReceiptService` |

**Status workflow.** Kolom `status`: `draft` → `posted`; `unpost` mengembalikan ke `draft` (`GoodsReceiptService.php:60, 226, 545`). Index menampilkan filter `closed` tetapi tidak ada kode yang menyetel `closed` pada GRN. Draft GRN *replacement* bisa dihapus (`destroy`, hanya draft + `is_replacement`). Data DB: `posted`(6).

**Trigger posting.** `post()` (`GoodsReceiptService.php:123`). Prasyarat yang benar-benar dicek:
1. `status === 'draft'`,
2. `warehouse_id` terisi,
3. jumlah baris > 0,
4. `grand_total > 0` **dan** `>= 100` (soft-guard harga PO belum diisi) (`GoodsReceiptService.php:145-165`).

> **Tidak ada pengecekan status PO di dalam service.** `create()` maupun `post()` tidak memvalidasi status `purchase_order`. Satu-satunya penjaga status PO ada di controller (lihat bagian 9).

**Trigger unposting.** `unpost()` (`GoodsReceiptService.php:497`). Prasyarat: `status === 'posted'`, dan **diblokir bila ada payment aktif** pada PO terkait (`hasActivePaymentsForOrder`, `GoodsReceiptService.php:504-509`).

**Dampak stok.**
- Post: `stockIn` per baris **HPP saja** (baris `expense` di-skip), membuat `Lot` bila perlu, mencatat `inventory_mutations` `source_type='purchase_receipt'`, dan meng-update moving-average `items.hpp` (`GoodsReceiptService.php:173-221, 659-695`).
- Unpost: `reverseBySource(['purchase_receipt'] → 'purchase_receipt_void')` + `recomputeHppFromHistory` untuk menghapus kontribusi GRN ini (`GoodsReceiptService.php:520-574`).

**Dampak hutang (AP).**
- Post mengkredit **AP 2101** sebesar `grand_total` (dipecah antara jurnal inventory & expense). Bila `is_replacement`, kredit lari ke **1305 (Piutang/Klaim Supplier)**, bukan 2101 (`GoodsReceiptService.php:240-241`).
- `syncReceivedStatus()` meng-update `received_status` PO (`GoodsReceiptService.php:1028`).
- Basis hutang untuk pembayaran = total `grand_total` GRN posted (via `netDebtByGrn`).

**Dampak jurnal.** Dua jurnal terpisah (`GoodsReceiptService.php:334-481`):
- `grn_inv`: **Dr Persediaan** (per `item_role` → 1201/1202/1203/1205) / **Cr AP 2101**.
- `grn_exp`: **Dr Expense** (akun expense per baris / fallback 6110) + **Dr PPN Masukan 1401** (opsional) + **Dr Ongkir 6102** / **Cr AP 2101** (sisa).
- Diskon di-*prorate* antara HPP dan expense.
- Unpost: `voidBySource('grn_inv')` + `voidBySource('grn_exp')`.

---

## 3. Purchase Invoice (Supplier Invoice)

| Aspek | Nilai aktual |
|---|---|
| Model | `App\Models\SupplierInvoice` |
| Controller | `App\Http\Controllers\Purchasing\SupplierInvoiceController` |
| Service | — (tidak ada service khusus; logika langsung di controller) |

**Status workflow.** `draft` → `posted` → `partial_paid` → `paid`; serta `void` (`SupplierInvoice.php:76-94`, `SupplierInvoiceController.php:34`). Data DB: kosong (belum ada invoice).

**Trigger posting / unposting.**
- `store()` → `draft`.
- `post()` → `posted`, hanya mengecek `total_amount > 0` (`SupplierInvoiceController.php:217-236`).
- `void()` → `void` (diblokir bila sudah `paid`).
- `setDeduction()` → update `return_deduction_amount`, `recalcTotal()`, `syncPaymentStatus()`.
- `paid_amount`/status paid disinkronkan **dari sisi Payment** (`PurchasePaymentController::syncInvoicePaymentStatus`), bukan dari invoice sendiri.

**Dampak stok:** Tidak ada.
**Dampak hutang (AP):** **Tidak ada dampak ke buku besar.** Invoice tidak membuat jurnal AP. Nilai AP yang dibayar dihitung dari GRN (`netDebtByGrn`), bukan dari `total_amount` invoice.
**Dampak jurnal:** **Tidak ada.** `post()` tidak memanggil `JournalService`. Ini murni dokumen administratif/penagihan.

---

## 4. Supplier Payment (Purchase Payment)

| Aspek | Nilai aktual |
|---|---|
| Model | `App\Models\PurchasePayment` |
| Controller | `App\Http\Controllers\Purchasing\PurchasePaymentController` |
| Service | `App\Services\Accounting\JournalService::postPurchasePayment` |

**Status workflow.** Tidak ada kolom `status`. State = **aktif** vs **voided** (`voided_at`). Kolom `type`: `dp`, `payment`, `dp_apply` (`PurchasePayment.php:10-23`).

**Trigger posting / unposting.**
- `store()` → buat payment + `postPurchasePayment()`. **Prasyarat: harus ada GRN posted** (`grnPostedTotal > 0`, `PurchasePaymentController.php:112-116`). PO `cancelled` ditolak. Metode `credit/tempo` hanya boleh untuk `dp`, tidak untuk pelunasan.
- `applyDp()` → buat `type=dp_apply` (offset DP ke AP).
- `void()` → set `voided_at` + void jurnal (`voidById` atau `voidBySource('purchase_payment')`).

**Dampak stok:** Tidak ada.
**Dampak hutang (AP).**
- `type=dp`: **tidak** mengurangi AP (masuk uang muka 1151).
- `type=payment`: mengurangi AP 2101.
- `type=dp_apply`: mengurangi AP 2101 (offset dari 1151).
- `recalcPaymentStatus()` meng-update `paid_amount` & `payment_status` PO berbasis `netDebtByGrn` (`PurchasePaymentController.php:393-449`).

**Dampak jurnal** (`JournalService::postPurchasePayment`, `JournalService.php:291`):
- `dp`: **Dr 1151 / Cr Kas/Bank**.
- `payment`: **Dr 2101 / Cr Kas/Bank**.
- `dp_apply`: **Dr 2101 / Cr 1151** (tanpa kas/bank).
- Idempoten via `(source_type=purchase_payment, source_id=payment_id)` + `journal_id`.

---

## 5. Purchase Return

| Aspek | Nilai aktual |
|---|---|
| Model | `App\Models\PurchaseReturn`, `PurchaseReturnLine`, `PurchaseReturnLinePhoto` |
| Controller | `App\Http\Controllers\Purchasing\PurchaseReturnController` |
| Service | `InventoryService`, `JournalService` (dipanggil dari controller) |

**Status workflow.** `draft` → `submitted` → `posted`; `draft/submitted` → `cancelled`; `posted` → `void` (`voided_at`). Tambahan `replacement_status`: `pending` / `partial` / `received` (`PurchaseReturnController.php:468, 493, 509`; `GoodsReceiptService::syncReplacementProgress`). Data DB: `posted`(1).

**Trigger posting / unposting.**
- Dibuat dari **GRN posted** (`createFromGrn`).
- Simpan draft → mengalokasikan (*reserve*) stok pada baris HPP.
- `submit()` → `submitted`.
- `post()` → `posted`. Prasyarat: GRN `posted`, qty ≤ remaining per baris, stok & saldo lot mencukupi (`PurchaseReturnController.php:503-606`).
- `cancel()` → `cancelled` + `releaseStock` (lepas alokasi).
- `void()` → `stockIn` balik + void kedua jurnal (`PurchaseReturnController.php:850-913`).

**Dampak stok.**
- Post: `consumeAllocationAndStockOut` (stok **keluar**) untuk baris HPP; `source_type='purchase_return'`.
- Void: `stockIn` (`source_type='purchase_return_void'`).

**Dampak hutang (AP).**
- Post mengurangi AP 2101 hingga `apOutstanding`; sisanya ke **Klaim 1305** (`PurchaseReturnController.php:716-734`).
- Bila `resolution_type = replacement`: AP **tidak** dipotong, 100% ke 1305 (`PurchaseReturnController.php:643-645`).
- Mengurangi `netDebtByGrn` (komponen `totalPostedReturns`).

**Dampak jurnal.**
- `purchase_return_inv`: **Cr Persediaan** (1201/1205 dst) / **Dr AP 2101 + Dr Klaim 1305**.
- `purchase_return_exp`: **Cr Expense** / **Dr AP/Klaim**.
- Void: `voidBySource('purchase_return_inv')` + `voidBySource('purchase_return_exp')`.

---

## 6. Journal Entry

| Aspek | Nilai aktual |
|---|---|
| Model | `App\Models\Journal`, `App\Models\JournalLine` |
| Controller | `App\Http\Controllers\Accounting\JournalController` (tampilan/manual) |
| Service | `App\Services\Accounting\JournalService` |

**Status workflow.** **Tidak ada state `draft`.** Jurnal dibuat langsung dengan `posted_at` terisi (`JournalService.php:148`). State efektif = **aktif** vs **voided** (`voided_at`) (`Journal.php:73-84`).

**Trigger posting / unposting.**
- `post()` (`JournalService.php:76`): validasi ≥ 2 baris, tiap baris **debit XOR credit**, total balance, dan **idempoten** via `(source_type, source_id)` bila `source_id` tidak null (`JournalService.php:88-141`).
- `void()` (`JournalService.php:182`): *soft-void* (`voided_at`) pada jurnal asli **plus** membuat jurnal *reversal* (debit/kredit dibalik) yang juga langsung ber-`voided_at` sebagai jejak audit. Semua laporan mengabaikan jurnal `voided`.
- Helper: `voidBySource()`, `voidById()`.

**Dampak stok:** Tidak langsung (jurnal adalah *engine* pencatatan; stok ditangani `InventoryService`).
**Dampak hutang (AP):** Jurnal adalah tempat AP (2101), 1151, 1305 dicatat — tetapi selalu **atas perintah modul lain** (GRN, Payment, Return).
**Dampak jurnal:** Ini modul jurnal itu sendiri. Konstanta akun & source dipusatkan di sini (`JournalService.php:18-62`).

---

## 7. Inventory Mutation

| Aspek | Nilai aktual |
|---|---|
| Model | `App\Models\InventoryMutation` |
| Controller | — (tidak ada; ledger internal) |
| Service | `App\Services\Inventory\InventoryService` |

**Status workflow.** Tidak ada status; **buku besar append-only**. Kolom kunci: `direction` (`in`/`out`), `qty_change`, `unit_cost`, `total_cost`, `lot_id`, `source_type`, `source_id` (`InventoryMutation.php:10-24`).

**Trigger.**
- `stockIn()` (+): tambah `InventoryStock.qty`, catat mutasi `in`, update Lot cost (`InventoryService.php:23`).
- `stockOut()` (−): kurangi stok, catat mutasi `out` (`InventoryService.php:100`).
- `consumeAllocationAndStockOut()`: pakai alokasi + stok keluar (dipakai Return).
- `reverseBySource()`: membuat mutasi penyeimbang dengan `source_type` void; idempoten (hanya membalik mutasi yang lebih baru dari reversal terakhir) (`InventoryService.php:922-989`).

**Dampak stok:** Ini penggerak stok tunggal (`InventoryStock` + `Lot`).
**Dampak hutang (AP):** Tidak langsung. Namun **basis AP purchasing bergantung pada keberadaan mutasi** (`unpost` GRN mengecek `inventory_mutations` sebelum reverse — `GoodsReceiptService.php:520-534`).
**Dampak jurnal:** Tidak membuat jurnal sendiri; beberapa poster jurnal (mis. `postShipmentCogsFromMutations`) **membaca** mutasi sebagai sumber nilai.

---

## 8. Diagram State Machine (aktual)

### 8.1 Purchase Order
```
        create()                approve()                 (GRN post)
 ( · ) ─────────▶ [draft] ───────────────▶ [approved] ─────────────────▶ received_status:
                    │  ▲                       │                            not_received
                    │  └──── unapprove() ──────┘                            → partial
                    │                          │                            → fully_received
        cancel() /  │              cancel()    │
        destroy()   ▼             (blocked bila ada GRN)
                [cancelled] ◀──────────────────┘
                                    │
                     closed_at diisi (aditif, lintas status) ─▶ [closed]
```
Guard: `edit/update/destroy/approve` hanya untuk `draft`; `cancel` hanya `draft|approved` dan gagal bila `purchaseReceipts()->exists()`.

### 8.2 GRN (Purchase Receipt)
```
   create()          post()                     unpost()
( · ) ─────▶ [draft] ───────▶ [posted] ───────────────────▶ [draft]
              │  (stockIn + 2 jurnal + AP)  (reverse stok + void jurnal)
              │
   destroy() (hanya draft & is_replacement)
              ▼
          [deleted]
```
Guard post: `draft` + warehouse + lines + `grand_total ≥ 100`. Guard unpost: `posted` + tidak ada payment aktif di PO.

### 8.3 Supplier Invoice
```
 store()        post()             (payment sync)        (payment sync)
( · )──▶[draft]──────▶[posted]──────────▶[partial_paid]──────────▶[paid]
             │            │                    │
             └── void() ──┴────────────────────┘   (tidak boleh void bila paid)
                          ▼
                        [void]
```
Tidak ada jurnal pada seluruh transisi ini.

### 8.4 Purchase Payment
```
 store()/applyDp()                      void()
( · ) ───────────────▶ [active] ─────────────────▶ [voided]
      (+ jurnal DP/Payment/Apply)   (+ void jurnal)
```

### 8.5 Purchase Return
```
 createFromGrn()     submit()          post()
( · ) ─────▶ [draft] ────────▶ [submitted] ────────▶ [posted]
              │  (alokasi stok)     │      (stockOut + 2 jurnal + kurangi AP)
              │                     │                    │
     cancel() │           cancel() │           void()   ▼
              ▼(release)           ▼                 [void]
          [cancelled]          [cancelled]     (stockIn balik + void jurnal)
```

### 8.6 Journal
```
 post()                         void()
( · ) ──────▶ [active] ───────────────────────▶ [voided] + [reversal(voided)]
        (posted_at diisi)   (soft-void + jurnal balik utk audit)
```

---

## 9. Matriks Event → Stock / AP / Journal / Mutation

| Event | Stock | AP (2101) | Journal | Inventory Mutation |
|---|---|---|---|---|
| PO Create (draft) | — | — | — | — |
| PO Approve | — | — | **— (tidak ada jurnal)** | — |
| PO Cancel/Unapprove | — | — | — | — |
| GRN Create (draft) | — | — | — | — |
| **GRN Post** | **+** (stockIn HPP, update HPP moving-avg) | **+ kredit** = `grand_total` (replacement → 1305) | **`grn_inv` + `grn_exp`** (Dr 1201/1202/1203/1205 & expense/PPN/ongkir, Cr 2101) | **+ IN** `source_type=purchase_receipt` |
| **GRN Unpost** | **−** (reverse) | **− (void)** | void `grn_inv`+`grn_exp` | **reverse** `purchase_receipt_void` |
| Invoice Create/Post | — | **— (tidak ke buku besar)** | **—** | — |
| Invoice Void/SetDeduction | — | — | — | — |
| Payment `dp` | — | — (masuk 1151) | Dr 1151 / Cr Kas-Bank | — |
| Payment `payment` | — | **− (Dr 2101)** | Dr 2101 / Cr Kas-Bank | — |
| Payment `dp_apply` | — | **− (Dr 2101)** | Dr 2101 / Cr 1151 | — |
| Payment Void | — | **+ (balik)** | void `purchase_payment` | — |
| Return Draft (save) | **reserve/alokasi** (bukan keluar) | — | — | — |
| **Return Post** | **−** (stockOut HPP) | **− (Dr 2101 s/d outstanding, sisa 1305)** | `purchase_return_inv` + `purchase_return_exp` | **− OUT** `source_type=purchase_return` |
| **Return Void** | **+** (stockIn balik) | **+ (void)** | void kedua jurnal return | **+ IN** `purchase_return_void` |
| Journal Post (manual) | — | tergantung baris | jurnal aktif | — |
| Journal Void | — | balik saldo | soft-void + reversal | — |

Catatan: "AP" di sini mengacu ke akun 2101 **dan** ke basis hutang purchasing `netDebtByGrn = Σ GRN posted − Σ Return posted`. Keduanya digerakkan oleh GRN/Return, bukan oleh Invoice.

---

## 10. Audit Khusus: "Apakah GRN dapat dibuat dari PO berstatus Draft tanpa merusak integritas data?"

### Jawaban singkat
**Melalui alur aplikasi normal (UI + validasi Controller): TIDAK BISA — sudah diblokir.** Namun blokade itu **hanya di lapisan Controller dan hanya pada header `purchase_order_id`**; lapisan Service dan level baris (`purchase_order_line_id`) **tidak** memeriksa status PO. Jadi secara arsitektur, penjagaannya tidak berlapis (*defense-in-depth* tidak lengkap).

### Alasan teknis mengapa alur normal memblokir (bukti kode)

1. **Form create hanya menampilkan PO `approved`.** `PurchaseReceiptController::create()` memuat baris PO dengan `whereHas('purchaseOrder', fn($q) => $q->where('status','approved'))` (`PurchaseReceiptController.php:121-123`). Baris PO `draft` tidak pernah muncul di form.

2. **`createFromOrder()` menolak non-approved.** Bila `status !== 'approved'` → redirect + error *"GRN hanya bisa dibuat dari PO yang sudah di-approve."* (`PurchaseReceiptController.php:175-179`).

3. **`store()` memvalidasi ulang status PO header.** `validateOptionalPurchaseOrderRelation()`: jika `purchase_order_id` diisi dan status PO **bukan** `approved`/`closed` → `ValidationException` *"GRN hanya boleh mengacu ke PO yang statusnya approved/closed."* (`PurchaseReceiptController.php:605-609`). Supplier GRN juga wajib sama dengan supplier PO.

### Celah / batasan penjagaan yang ditemukan (kondisi aktual, bukan rekomendasi)

- **Guard hanya pada header, `purchase_order_id` bersifat `nullable`.** GRN sah dibuat dengan `purchase_order_id = null` (`PurchaseReceiptController.php:411`), sehingga ada jalur GRN "tanpa PO" yang tidak melewati pengecekan status PO sama sekali (tetapi ini juga bukan "GRN dari PO Draft").
- **Level baris tidak divalidasi terhadap status PO.** `buildLinesFromRequest()` hanya memuat `qty` & `unit_price` dari `purchase_order_line_id` (`PurchaseReceiptController.php:469-472`), tanpa memeriksa status PO pemilik baris. Jadi request yang disusun manual (header PO approved/null, tetapi `po_line_id` menunjuk baris milik PO Draft) tidak tertangkap.
- **Service tidak punya guard.** `GoodsReceiptService::create()` dan `post()` tidak pernah membaca status PO. Pemanggilan service langsung (job, tinker, seeder, endpoint lain) bisa memposting GRN atas PO Draft.

### Jika GRN *dipaksa* terbentuk dari PO Draft — perubahan perilaku yang akan terjadi

- **Stok:** `stockIn` tetap berjalan saat GRN post; integritas stok GRN bersifat mandiri (tidak bergantung status PO). Stok & moving-average HPP akan berubah normal.
- **Jurnal:** `grn_inv` + `grn_exp` tetap terposting, AP 2101 terkredit. **Risiko:** validasi *"baris expense wajib punya `expense_account_id`"* hanya berjalan di `PurchaseOrderService::approve()` (`:160-172`). PO Draft belum melewati gerbang itu, sehingga baris expense ber-`expense_account_id` NULL akan jatuh ke fallback 6110 atau melempar error *"Expense line ada tapi tidak ada akun biaya"* (`GoodsReceiptService.php:437-444`).
- **AP / hutang:** `netDebtByGrn` menghitung semua GRN posted **tanpa melihat status PO**, jadi hutang tetap terbentuk dan **Payment bisa diproses** (Payment hanya butuh GRN posted, bukan PO approved — `PurchasePaymentController.php:112-116`). Rekonsiliasi AP internal purchasing tetap "seimbang", namun terlepas dari lifecycle PO.
- **Reporting:** `syncReceivedStatus()` akan menandai PO Draft sebagai `partial`/`fully_received` — muncul **anomali**: PO `status=draft` tetapi `received_status=fully_received`, dengan `approved_by/approved_at = NULL`. Laporan AP & penerimaan akan mencantumkan PO yang belum pernah di-approve.
- **Workflow — kerusakan integritas paling konkret:** PO Draft **masih bisa di-edit dan di-hapus** (`PurchaseOrderController.php:441,513,545` mengizinkan draft), dan `PurchaseOrderService::syncLines()` melakukan `lines()->delete()` lalu membuat baris baru (`:254`). Jika GRN sudah menunjuk `purchase_order_line_id` lama:
  - edit PO → baris lama terhapus, `purchase_order_line_id` GRN menjadi **yatim (orphan FK)**;
  - hapus PO → GRN menunjuk PO yang tidak ada;
  - akibatnya `backfillPricesFromPoLines()`, pemetaan `allocation`/`eligibility` HPP-vs-expense, dan `syncReceivedStatus()` semuanya membaca referensi yang sudah hilang.
  Inilah alasan teknis utama mengapa GRN dibatasi ke PO `approved`/`closed`: PO pada status tersebut tidak lagi bisa diubah/dihapus lewat controller, sehingga `purchase_order_line_id` yang dirujuk GRN stabil.

### Kesimpulan bagian 10
Pada **alur normal**, GRN dari PO Draft **tidak dapat** dibuat dan integritas terjaga — tiga lapis validasi controller (form filter, `createFromOrder`, `validateOptionalPurchaseOrderRelation`) menutupnya. **Namun** penjagaan tidak berlapis sampai ke Service/level-baris, dan konsekuensi bila blokade ditembus (mis. via pemanggilan service langsung atau request manual dengan `po_line_id` PO Draft) adalah **kerusakan integritas referensial** karena PO Draft masih mutable/deletable — bukan pada mekanika stok/jurnal GRN itu sendiri, melainkan pada tautan `purchase_order_line_id` dan konsistensi lifecycle/reporting PO.

---

*Selesai — audit deskriptif. Tidak ada usulan perubahan kode dalam dokumen ini, sesuai instruksi.*
