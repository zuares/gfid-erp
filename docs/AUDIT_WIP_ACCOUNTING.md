# Audit Jurnal Accounting untuk WIP Normalization & WIP Cleanup

Tanggal audit: 7 Juli 2026
Scope: keselarasan movement WIP (Normalization/Cleanup) dengan modul accounting existing.
Status: **audit only — belum ada perubahan kode.**

Referensi utama yang dibaca:
`app/Services/Accounting/JournalService.php`, `database/seeders/AccountSeeder.php`,
`app/Http/Controllers/Inventory/WipAdjustmentController.php`,
`app/Http/Controllers/Production/WipOpnameController.php`,
`app/Services/Purchasing/GoodsReceiptService.php`,
`app/Http/Controllers/Production/SewingReturnController.php`,
`database/migrations/2026_06_01_000200_create_production_movements_table.php`,
serta data aktual di `database_dev.sqlite`.

---

## Ringkasan eksekutif

Project **sudah punya fondasi accounting yang kuat**: satu `JournalService`
terpusat, idempotent per `(source_type, source_id)`, dengan void + reversal
berjejak, dan **nilai selalu diambil dari `inventory_mutations.total_cost`**
(bukan hardcode). Mayoritas alur produksi inti (cutting, sewing, finishing,
COGS shipment, GRN, opname reguler) **sudah menghasilkan jurnal otomatis**.

Namun ada **tiga celah yang tepat berada di jalur WIP Normalization/Cleanup**:

1. **WIP Adjustment (`WipAdjustmentController`) tidak membuat jurnal sama sekali.**
   Stok WIP dikoreksi, `inventory_mutations` ditulis (untuk WIP-* global), tetapi
   `JournalService` tidak pernah dipanggil → **stok berubah, neraca WIP tidak.**
2. **WIP Opname (`WipOpnameController@approve`) hanya mengoreksi `cut_wip_qty`
   bundle.** Tidak menulis mutasi nilai, tidak ada jurnal → selisih opname WIP
   tidak pernah masuk akuntansi.
3. **Akun `1204` (Barang Cacat) dan `1205` (Packaging) direferensikan di kode
   tetapi tidak ada di chart of accounts.** Semua jurnal reject/defect memanggil
   `accountIdByCode('1204')` yang akan **melempar exception** — dan di
   `SewingReturnController` exception itu **ditelan `try/catch`**, sehingga stok
   pindah tanpa jurnal.

Kesimpulan: WIP Normalization/Cleanup **belum aman** untuk diselaraskan sebelum
ketiga celah ini ditutup dan sebelum akun minimal ditambahkan. Rekomendasi flow
teraman ada di bagian F–J.

---

## A. Apakah project sudah punya jurnal otomatis untuk inventory/produksi?

**Ya.** Terpusat di `App\Services\Accounting\JournalService` dengan sifat penting:

- `post(date, sourceType, sourceId, desc, lines)` — memvalidasi balance
  (Σdebit = Σkredit), minimal 2 baris, dan **idempotent**: jika sudah ada jurnal
  aktif dengan `(source_type, source_id)` yang sama, mengembalikan yang lama.
- `void()` / `voidBySource()` — soft void + membuat baris **reversal** yang juga
  ditandai void, sehingga tidak terhitung dua kali di laporan.
- Nilai (amount) hampir selalu dihitung dari
  `SUM(ABS(inventory_mutations.total_cost))` per `source_type/source_id` —
  artinya **stok adalah sumber kebenaran nilai**, jurnal mengikuti.

Chart of accounts aktif yang relevan (dari DB):

| Kode | Nama | Peran |
|------|------|-------|
| 1201 | Persediaan Bahan Baku | RM |
| 1202 | Persediaan WIP | WIP (semua tahap jadi satu akun) |
| 1203 | Persediaan Barang Jadi | FG |
| 5101 | Harga Pokok Penjualan | HPP |
| 6101 | Biaya Operasional Umum | dipakai sebagai penampung selisih adjustment/opname |
| 2101 / 2102 | Hutang Dagang / Hutang Upah Borongan | AP / upah borongan |

> ⚠️ `1204 Persediaan Barang Cacat` dan `1205 Persediaan Packaging` **ada di
> konstanta `JournalService` (`CODE_INV_DEFECT`, `CODE_INV_PACKAGING`) tetapi
> tidak ada di `accounts`**. Belum ada seeder/migration yang membuatnya.

---

## B. Movement produksi yang SEKARANG sudah membuat jurnal

Terverifikasi dari pemanggil `JournalService` + data jurnal aktif di DB:

| Movement | Pemicu (caller) | Pola jurnal |
|---|---|---|
| GRN inventory | `GoodsReceiptService::post` (`grn_inv`) | Dr 1201 / Cr 2101 |
| Pembayaran pembelian / DP | `PurchasePaymentController` → `postPurchasePayment` | Dr 2101/1151 / Cr Kas-Bank |
| Cutting: RM → WIP | `CuttingJobController` → `postCuttingJob` | Dr 1202 / Cr 1201 |
| Cutting: upah borongan | `CuttingJobController` → `postCuttingJobWage` | Dr 1202 / Cr 2102 |
| Cutting: hasil QC (+upah) | `QcController` → `postCuttingWip` | Dr 1202/1204 / Cr 1202 (+2102) |
| Ambil jahit: WIP-CUT → WIP-SEW | `SewingPickupController` → `postSewingPickup` | Dr 1202 / Cr 1202 (material only) |
| Ambil jahit: upah + kelengkapan | `postSewingPickupLineWage`, `postSewingPickupSupply` | Dr 1202 / Cr 2102 & Cr 1201 |
| Setor jahit OK / Reject / Rework | `SewingReturnController` (dynamic call) → `postSewingReturnOk/Reject/ReworkOk` | Dr 1202/1204 / Cr 1202/1204 (+2102) |
| Finishing: WIP → FG / Cacat | `FinishingJobController` → `postFinishingJob` | Dr 1203/1204 / Cr 1202 |
| Finishing: konsumsi BOM | `FinishingJobController` → `postFinishingBom` | Dr 1202 / Cr 1201 |
| COGS shipment | `ShipmentController` → `postShipmentCogsFromMutations` | Dr 5101 / Cr 1203/1202/1201 (per item_role) |
| Inventory adjustment (umum) | `InventoryAdjustmentController` → `postInventoryAdjustment` | Dr/Cr akun persediaan per role vs 6101 |
| Stock opname (reguler) | `StockOpnameService` → `postInventoryAdjustment` | idem, selisih ke 6101 |

Poin penting: **jalur produksi utama sudah lengkap** dan konsisten memakai
`total_cost` sebagai nilai.

---

## C. Movement produksi yang BELUM membuat jurnal (tapi seharusnya)

| Movement | Kondisi saat ini | Dampak |
|---|---|---|
| **WIP Adjustment** (`WipAdjustmentController@approve`, `purpose='wip'`) | Menyesuaikan stok WIP (InventoryService untuk WIP-* global; manipulasi `InventoryStock` langsung untuk WIP-CUT) **tanpa memanggil `JournalService`** | Stok WIP berubah, **1202 di neraca tidak** → nilai neraca salah |
| **WIP-CUT adjustment (bundle-aware)** | Menulis `InventoryStock` langsung, **tidak lewat InventoryService** | Kemungkinan **tidak menulis `inventory_mutations`** → tidak ada jejak nilai sama sekali |
| **WIP Opname** (`WipOpnameController@approve`) | Hanya update `cut_wip_qty` bundle | Selisih fisik WIP tidak pernah jadi mutasi/jurnal → WIP "hilang" dari produksi tapi tetap di neraca |
| **WipFinAdjustment** | Model + `postWipFinAdjustment()` ada, **tidak ada pembuat record & tidak ada pemanggil** (0 baris di DB) | Fitur setengah jadi; kalau dipakai tanpa wiring, tidak berjurnal |
| **Reject/Defect apa pun** | Memakai `1204` yang tidak ada di COA → `accountIdByCode` melempar; di sewing exception **ditelan try/catch** | Reject memindah stok tapi **tidak membuang nilai WIP** (jurnal gagal diam-diam) |
| **Finishing Repair** (`FinishingRepairController`) | Tidak ada pemanggilan `JournalService` terdeteksi | Pergerakan repair berpotensi tanpa jurnal — perlu ditegaskan |
| **`production_movements`** | Tabel anotasi movement sudah dibuat, **0 baris, belum di-wire** | Jejak konteks produksi (dari/ke status, operator, bundle) belum dimanfaatkan |

Ketujuh baris di atas adalah **tepat area yang mau kamu rapikan lewat WIP
Normalization/Cleanup.**

---

## D. WIP Normalization — kapan perlu jurnal, kapan tidak

Prinsip: **jurnal dibuat hanya kalau nilai berpindah keluar/masuk bucket
persediaan.** Selama nilai tetap di dalam WIP (1202), tidak perlu jurnal
finansial — cukup **movement record** untuk jejak.

| # | Disposition | Perlu jurnal? | Alasan |
|---|---|---|---|
| 1 | **Pindah lokasi WIP → WIP lain** | **Tidak** (selama WIP = satu akun 1202) | Nilai tidak keluar dari 1202. Cukup movement record. *Jika* nanti tiap tahap WIP dipisah jadi akun GL sendiri, baru Dr WIP-tujuan / Cr WIP-asal. |
| 2 | **WIP → Barang Jadi / WH-PRD** | **Ya** | Nilai pindah bucket: Dr 1203 / Cr 1202. Ini pola `postFinishingJob`. |
| 3 | **WIP masuk repair** | **Tergantung** | Jika barang masih berstatus WIP (repair internal) → tidak (value-neutral). Jika berasal dari Barang Cacat lalu dikembalikan ke WIP → Ya: Dr 1202 / Cr 1204 (pola `postSewingReworkOk`). |
| 4 | **WIP → reject final** | **Ya** | Nilai harus keluar dari WIP. Jika reject punya nilai jual/salvage → Dr 1204 (aset). Jika scrap tanpa nilai → Dr **Kerugian Produksi/Reject** (P&L) / Cr 1202. |
| 5 | **WIP write-off (fisik tak ditemukan)** | **Ya** | Dr **Selisih Stock Opname / Kerugian Produksi** / Cr 1202. Membuang nilai hantu dari neraca. |
| 6 | **WIP adjustment tambah (fisik ditemukan)** | **Ya** | Dr 1202 / Cr **Selisih Stock Opname** (gain). |
| 7 | **Close as legacy** | **Ya** | Jangan hapus diam-diam. Dr **Koreksi Persediaan Legacy** (P&L/other) / Cr 1202, dengan referensi & catatan. |

Aturan turunan: movement #1 dan #3-internal cukup **movement-only**; #2, #4, #5,
#6, #7 **wajib jurnal**.

---

## E. Rekomendasi mapping jurnal (debit/kredit + sumber nilai)

Mengikuti konvensi yang sudah dipakai `JournalService` (nilai dari `total_cost`):

| Disposition | Debit | Kredit | Sumber nilai |
|---|---|---|---|
| 2. WIP → FG/WH-PRD | 1203 Barang Jadi | 1202 WIP | **Biaya aktual** dari `inventory_mutations` WIP yang keluar |
| 3. Defect → WIP (rework) | 1202 WIP | 1204 Barang Cacat | Biaya aktual mutasi keluar defect (+ upah rework ke 2102 bila ada) |
| 4. WIP → reject (salvage) | 1204 Barang Cacat | 1202 WIP | Biaya aktual WIP keluar |
| 4. WIP → reject (scrap) | **Kerugian Produksi/Reject** (P&L) | 1202 WIP | Biaya aktual; jika tak ada → HPP/standar |
| 5. Write-off hilang | **Selisih Stock Opname** / Kerugian Produksi | 1202 WIP | Biaya aktual; jika tak ada → standar |
| 6. Adjustment tambah | 1202 WIP | **Selisih Stock Opname** (gain) | Biaya standar/HPP item (barang "ditemukan" jarang punya mutasi asal) |
| 7. Close as legacy | **Koreksi Persediaan Legacy** | 1202 WIP | Nilai WIP tercatat; jika 0/tak diketahui → standar, jangan 0 |

**Kapan pakai biaya aktual vs estimasi:**

- **Biaya produksi aktual** (default): saat WIP berasal dari movement ter-track
  (cutting/sewing/finishing) — nilainya sudah ada di `inventory_mutations.total_cost`.
  Semua service existing sudah begini.
- **Estimasi/HPP standar** (`ItemCostSnapshot` / `HppService` / `ProductionCostService`):
  saat WIP tidak punya mutasi asal (bundle legacy, entri manual, barang
  "ditemukan" di opname).
- **Kalau nilai WIP tidak diketahui sama sekali:** ambil cost snapshot terakhir
  item; jika tetap 0, **jangan post jurnal ber-nilai 0 yang memindah qty tanpa
  nilai** (itu justru bikin FG masuk WH-PRD tanpa nilai / HPP kacau). Sebaiknya
  **blokir + tandai untuk review owner**, atau pakai standar cost sebagai
  fallback yang eksplisit dan berjejak.

---

## F. Arsitektur service (jurnal jangan dari controller)

Project **sudah menerapkan pola ini sebagian**: `JournalService` adalah satu-satunya
penulis jurnal, dan controller memanggilnya. Rekomendasi untuk WIP Normalization:

- **`AccountingJournalService`** → **sudah ada** = `JournalService`. Pertahankan
  sebagai satu-satunya penulis `journals`. Tambahkan hanya method pola baru
  (mis. `postWipNormalization(...)`) yang tetap idempotent per `(source_type, source_id)`.
- **`InventoryMovementService`** → **sudah ada** = `Inventory\InventoryService`
  (`adjustByDifference`, dsb.). Semua perubahan stok WIP **harus** lewat sini agar
  `inventory_mutations` + `total_cost` konsisten. (Perbaiki jalur WIP-CUT yang
  saat ini menyentuh `InventoryStock` langsung.)
- **`ProductionMovementService`** → **baru, tipis**. Mengisi `production_movements`
  (kode, batch, from/to status, operator, `created_by`, link `inventory_mutation_id`)
  saat movement produksi/WIP terjadi. Tabelnya sudah ada, tinggal di-wire.
- **`WipNormalizationService`** → **baru, orkestrator**. Untuk tiap disposition
  (D1–D7): panggil `InventoryService` untuk stok, `ProductionMovementService`
  untuk jejak, lalu `JournalService` untuk jurnal — **tidak menulis jurnal sendiri.**

Controller hanya memvalidasi request + memanggil `WipNormalizationService`.

---

## G. Ketertelusuran jurnal ke sumber

Kondisi sekarang di tabel `journals`: ada `source_type`, `source_id`,
`description`, `posted_at`, `voided_at`. **Belum ada** `reference_no`, `notes`,
`created_by`, `approved_by`, `approved_at`.

Rekomendasi (migration aditif, tanpa ubah data lama):

- Tambah kolom `journals`: `reference_no` (nomor dokumen sumber, mis. `WOP-CUT-...`),
  `notes`, `created_by`, `approved_by`, `approved_at`.
- Untuk jejak movement: manfaatkan `production_movements` yang **sudah** punya
  `created_by` dan `inventory_mutation_id` (link ke baris mutasi).
- Konvensi `source_type`/`source_id` yang stabil per disposition (lihat H)
  membuat jurnal bisa ditelusuri balik ke record WIP Normalization.

---

## H. Anti jurnal dobel

`JournalService::post` **sudah idempotent** pada `(source_type, source_id)`
selama `voided_at` null. Untuk menjaga tidak dobel di WIP Normalization:

- Setiap disposition punya **`source_type` unik + `source_id` = id record
  normalization** (mis. `wip_normalization` + id, atau reuse konstanta yang ada).
- **Jika hasil normalization sebenarnya adalah movement yang sudah berjurnal**
  (contoh: WIP → FG identik dengan penyelesaian finishing), **panggil method
  existing** (`postFinishingJob` / movement service yang sama), **jangan** bikin
  jurnal WIP paralel. Nilai diambil dari mutasi yang sama → tidak ada double.
- Reversal/void selalu lewat `voidBySource(source_type, source_id)` supaya jurnal
  asli + reversal sama-sama ter-void.

---

## I. Rekomendasi akun minimal

Yang **wajib ditambahkan** (beberapa sudah dipakai kode tapi belum ada di COA):

| Kode (usulan) | Nama | Tipe | Catatan |
|---|---|---|---|
| **1204** | Persediaan Barang Cacat | asset | **Sudah dipakai kode, tapi belum ada di DB — prioritas 1** |
| **1205** | Persediaan Packaging | asset | Sudah direferensikan (`CODE_INV_PACKAGING`) |
| **1202** | Persediaan WIP / Barang Dalam Proses | asset | Sudah ada |
| **1203** | Persediaan Barang Jadi | asset | Sudah ada |
| **6115** (usulan) | Selisih Stock Opname / Persediaan | expense | Ganti pemakaian 6101 sbg penampung selisih |
| **6120** (usulan) | Kerugian Produksi / Reject | expense | Untuk reject scrap (D4) & write-off (D5) |
| **6116** (usulan) | Koreksi Persediaan Legacy | expense | Khusus close-as-legacy (D7), agar terpisah & auditable |

Catatan: kode 611x/612x hanya usulan agar konsisten dengan blok biaya
operasional existing (6101–6201); silakan sesuaikan penomoran.

---

## J. Risiko yang harus dicegah (dipetakan ke temuan)

| Risiko | Temuan terkait | Mitigasi |
|---|---|---|
| **Stok benar, nilai accounting salah** | WIP Adjustment & WIP Opname ubah stok tanpa jurnal (C) | Semua disposition D2,D4–D7 wajib lewat `WipNormalizationService` → `JournalService` |
| **Jurnal dobel** | — | Idempotensi `(source_type, source_id)` + reuse method existing (H) |
| **WIP hilang dari produksi tapi masih di neraca** | WIP Opname hanya koreksi `cut_wip_qty` (C) | Write-off (D5) buat jurnal Dr selisih/kerugian / Cr 1202 |
| **Barang jadi masuk WH-PRD tanpa nilai** | Fallback nilai 0 (E) | Jangan post qty tanpa nilai; pakai standar cost / blokir & review |
| **Reject tidak membuang nilai WIP** | 1204 hilang → jurnal reject gagal & ditelan try/catch (C) | Tambah 1204/akun kerugian, hilangkan swallow diam-diam, D4 wajib jurnal |
| **Close legacy menghapus nilai tanpa jejak** | Belum ada flow legacy (C) | D7: jurnal ke Koreksi Persediaan Legacy + `reference_no`/`notes`/`created_by` (G) |
| **HPP kacau karena WIP tak punya cost** | Nilai dari `total_cost`; WIP-CUT adj tak lewat InventoryService (C) | Paksa semua stok WIP lewat InventoryService; fallback cost eksplisit (E) |

---

## Rekomendasi urutan implementasi (aman, bertahap)

1. **Tambah akun** 1204, 1205, dan akun selisih/kerugian/legacy (I). Tanpa ini,
   jurnal reject sudah rusak diam-diam.
2. **Tutup swallow error** di `SewingReturnController` (dan sejenis) agar
   kegagalan jurnal tidak tersembunyi.
3. **Wire WIP Adjustment & WIP Opname ke `JournalService`** (via service baru),
   mulai dari yang paling berdampak nilai (write-off, reject, WIP→FG).
4. **Buat `WipNormalizationService` + `ProductionMovementService`** sebagai
   orkestrator 7 disposition, memakai `InventoryService` + `JournalService`.
5. **Tambah kolom traceability** di `journals` (G).
6. Terakhir, samakan WIP Cleanup agar **memanggil service yang sama** (H).

> Belum ada kode yang diubah dalam audit ini. Silakan konfirmasi penomoran akun
> dan prioritas, lalu implementasi bisa dijalankan bertahap sesuai urutan di atas.
