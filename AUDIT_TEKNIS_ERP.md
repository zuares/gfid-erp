# LAPORAN AUDIT TEKNIS ERP — GFID Dev
**Tanggal audit:** 19 Juni 2026  
**Auditor:** Claude (Cowork session)  
**Status DB dev:** SQLite — purchasing data kosong, produksi aktif

---

## 1. RINGKASAN STATUS MODUL

| Modul | Status | Catatan |
|---|---|---|
| Inventory / Stok | ✅ Sudah ada | Audit trail lengkap via inventory_mutations |
| Item Master + BOM | ✅ Sudah ada | 131 items, 72 BOMs aktif, 360 BOM lines |
| Cutting Job | ✅ Sudah ada | Jurnal cutting_job + cutting_wip aktif |
| Sewing Pickup + Return | ✅ Sudah ada | Jurnal pickup + supply + return aktif |
| Finishing Job + BOM | ✅ Sudah ada | Jurnal finishing_job + finishing_bom aktif |
| Purchasing (PR/PO/GRN) | 🟡 Setengah ada | Kode lengkap, tapi dev DB kosong karena data dihapus |
| Inventory Adjustment | 🟡 Setengah ada | Kode ada + baru kita hook jurnal, tapi data lama belum di-backfill |
| Payroll Borongan | 🟡 Setengah ada | Rate + service ada, tapi belum pernah final di dev |
| HPP Aktual per SKU | 🟡 Setengah ada | Struktur ada (item_cost_snapshots), komponen upah = 0 |
| Stock Transfer Internal | 🟡 Setengah ada | stock_request ada (102 records, Rp 303jt) tapi tanpa jurnal |
| Material Shortage Report | ❌ Belum ada | Data BOM + stok ada, tapi belum ada halaman/logika shortage |
| Reservasi Material | ❌ Belum ada | Kolom reserved_qty tidak ada di schema |
| Auto-Generate PR dari Shortage | ❌ Belum ada | PR module ada, tapi belum ada trigger dari shortage |
| Closing Produksi Bulanan | ❌ Belum ada | ProductionCostPeriod ada (draft), HPP auto service ada, belum pernah dijalankan |
| Dashboard Kontrol Produksi | 🟡 Setengah ada | Dashboard bundel/sewing ada, belum ada indikator stok/jurnal/payroll |
| Jurnal Shipment Return | ❌ Belum ada | 3 mutasi tanpa jurnal |
| Jurnal Stock Transfer | ❌ Belum ada | 778 mutasi, Rp 303jt, tanpa jurnal |

**Coverage jurnal keseluruhan: 66.6%** (Rp 1.24 miliar terjurnal, Rp 620 juta belum)

---

## 2. TABEL AUDIT LENGKAP

### A. DATABASE SCHEMA

| Area | Tabel | Status | Temuan | Risiko | Rekomendasi |
|---|---|---|---|---|---|
| Item Master | `items` (131 rows) | ✅ Ada | Kolom: code, name, type, item_role, hpp, allow_negative, is_stocked, base_unit_cost | allow_negative=1 pada 25 items menyebabkan stok negatif disengaja | Buat laporan stok negatif dulu sebelum ubah flag |
| Kategori | `item_categories` (16 rows) | ✅ Ada | kind: produk/bahan/supply | — | — |
| BOM | `item_boms` (72 rows) + `item_bom_lines` (360 rows) | ✅ Ada | Kolom usage_stage, scrap_pct, is_optional — BOM sudah cukup kaya | 10 item FG tanpa BOM | Audit item FG tanpa BOM sebelum shortage report |
| Mutasi Stok | `inventory_mutations` (7.362 rows) | ✅ Ada | Source of truth. Kolom: source_type, source_id, unit_cost, total_cost | 86 mutasi tanpa cost (total_cost=0 atau NULL) | Cek satu per satu mutasi tanpa cost, buat report |
| Stok Running | `inventory_stocks` (267 rows) | ✅ Ada | Denormalized cache, qty selalu sama dengan SUM(mutations) | 11 items stok negatif (8 genuine) | Report + adjustment resmi |
| Gudang | `warehouses` (13 rows) | ✅ Ada | RM, WIP-CUT/SEW/FIN/PACK, FG, REJ-*, WH-RTS, WH-PRD | — | — |
| Lot Tracking | `lots` (138 rows) | ✅ Ada | Untuk tracking kain per batch beli | lots.qty_onhand harus selalu sync dengan mutations | Cek apakah lots.qty_onhand = mutations sum |
| Inventory Adjustment | `inventory_adjustments` (55 rows) | ✅ Ada | Status: approved. Jurnal hook baru dipasang Juni 2026 | 54 adjustment lama (Rp 295jt) belum punya jurnal | Backfill jurnal adjustment lama dengan artisan command |
| Stock Transfer | `stock_requests` (102 rows) | ✅ Ada | Dipakai untuk transfer RM → WH-RTS (replenishment) | 778 mutasi, Rp 303jt, tanpa jurnal sama sekali | Tambah jurnal hook di stock_request flow (Dr 1203/Cr 1201) |
| Cutting | `cutting_jobs` (66) + `cutting_job_lots` (98) + `cutting_job_bundles` (372) | ✅ Ada | Bundle tracking per lot — cukup detail | — | — |
| Sewing | `sewing_pickups` (238) + `sewing_pickup_lines` (379) + `sewing_returns` (229) | ✅ Ada | — | — | — |
| Finishing | `finishing_jobs` (93) + `finishing_job_lines` (249) | ✅ Ada | Kolom bom_applied_at ada — track kapan BOM dikonsumsi | — | — |
| Purchase Request | `purchase_requests` (0 rows) | 🟡 Ada (kosong) | Tabel + controller + flow convert-to-PO sudah ada | Belum pernah dipakai di production | — |
| Purchase Order | `purchase_orders` (0 rows) | 🟡 Ada (kosong di dev) | Tabel + controller + GRN flow lengkap | Data dev hilang (dihapus manual) | Test ulang dengan seeder |
| GRN | `purchase_receipts` (0 rows di dev) | 🟡 Ada (kosong di dev) | GoodsReceiptService dengan jurnal grn_inv sudah ada | — | — |
| Payroll | `piecework_payroll_periods` (1, draft) + `piecework_payroll_lines` (25, amount=0) | 🟡 Ada | PieceRate ada (18 rates). PostingService ada tapi belum pernah final | rate_per_pcs = 0 di semua lines — data belum diisi | Isi rate, finalize, posting → cek jurnal 2102 |
| Closing Produksi | `production_cost_periods` (1, draft) | 🟡 Ada | FgHppAutoService + HppService + ProductionCostService tersedia | Belum pernah dijalankan | — |
| HPP Snapshot | `item_cost_snapshots` (936, 105 aktif) | 🟡 Ada | rm_unit_cost terisi, cutting=0, sewing=0, finishing=0 | HPP yang dipakai untuk shipment_cogs tidak include upah | Jalankan closing setelah payroll final |
| Jurnal | `journals` (1.393) + `journal_lines` (3.191) | ✅ Ada | Double-entry, idempotent, source_type + source_id pattern | 33.4% mutasi belum terjurnal | Lihat bagian jurnal di bawah |
| COA | `accounts` (26) | ✅ Ada | 1101–6201, lengkap untuk operasi ini. 2102 Hutang Upah ada | — | — |
| Marketplace | `mp_shipments` (1.796) + `mp_incomes` (526) + `mp_reconciliations` (31) | ✅ Ada | Data MP masuk | — | — |
| Production Orders | `production_orders` (0 rows) | ❌ Tidak dipakai | Tabel ada tapi belum pernah dipakai | — | Pertimbangkan apakah perlu |
| BOM Issues | `bom_issues` + `bom_issue_lines` (0 rows) | ❌ Tidak dipakai | Tabel ada untuk material issue tapi belum pernah dipakai | — | Kandidat untuk shortage tracking |
| Reservasi | — | ❌ Tidak ada | Tidak ada kolom reserved_qty/allocated_qty di inventory_stocks atau items | Tidak bisa bedakan stok bebas vs stok yang sudah "dijanjikan" ke produksi | Lihat rekomendasi desain di bawah |

---

### B. CONTROLLER & SERVICE

| Modul | File | Status | Workflow Ada | Validasi Stok | Jurnal | Bug/Risiko |
|---|---|---|---|---|---|---|
| Inventory | `InventoryService.php` | ✅ | stockIn/stockOut/adjustByDifference, validasi allowNegative per item | ✅ Ada | Hook dipanggil dari controller | — |
| Cutting | `CuttingJobController.php` + `CuttingService.php` | ✅ | Buat job → ambil fabric (stockOut RM) → bundle per lot → post WIP → jurnal cutting_job + cutting_wip | ✅ | ✅ | Backfill cutting_qc_void (Rp 6.4jt) belum ada jurnal |
| Sewing Pickup | `SewingPickupController.php` | ✅ | Bundle WIP-CUT → WIP-SEW + supply kelengkapan → jurnal sewing_pickup + supply | ✅ (allowNegative=false) | ✅ | 22 mutasi sewing_pickup tanpa cost (Rp 0) |
| Sewing Return | `SewingReturnController.php` | ✅ | WIP-SEW → WIP-FIN (ok) / REJ-SEW (reject) → jurnal ok + reject | ✅ | ✅ | sewing_return_void_ok (Rp 3.8jt) belum ada jurnal |
| Finishing | `FinishingJobController.php` + `FinishingBomService.php` | ✅ | WIP-FIN → FG/REJ-FIN + konsumsi BOM supplies → jurnal finishing_job + finishing_bom | ✅ | ✅ | 16 mutasi finishing_bom tanpa cost |
| Purchasing (PR) | `PurchaseRequestController.php` | 🟡 | CRUD + approve + convert to PO ada | — | — | Belum pernah dipakai (0 rows) |
| Purchasing (PO) | `PurchaseOrderController.php` | 🟡 | CRUD + approve + close ada | — | — | Data dev kosong |
| GRN | `PurchaseReceiptController.php` + `GoodsReceiptService.php` | 🟡 | GRN create → post → stockIn RM + jurnal grn_inv (Dr 1201 / Cr 2101) | ✅ | ✅ | Data dev kosong. journal_id disimpan ke GRN ✅ |
| Inventory Adjustment | `InventoryAdjustmentController.php` | 🟡 | Approved → stockIn/Out → jurnal (hook baru Juni 2026) | ✅ | 🟡 Hook baru, data lama belum di-backfill | 54 adjustment lama tanpa jurnal |
| Stock Transfer | `RtsStockRequestController.php` | 🟡 | Transfer RM → WH-RTS/WH-PRD ada | ✅ | ❌ Belum ada | Rp 303jt tanpa jurnal — terbesar |
| Payroll | `PieceworkPayrollController.php` + `PieceworkPayrollPostingService.php` + `PieceRateService.php` + `SewingPayrollGenerator.php` | 🟡 | Rate per pcs per employee + category → generate lines → finalize → post jurnal 2102 (accrual) + bayar (Dr 2102 / Cr Kas) | — | 🟡 Service ada tapi belum pernah dijalankan | Payroll lines amount=0, data belum diisi |
| HPP / Costing | `HppService.php` + `FgHppAutoService.php` + `ProductionCostService.php` | 🟡 | Snapshot HPP per SKU per periode — cutting+sewing dari payroll period | — | — | cutting_unit_cost=0 semua — belum pernah dijalankan |
| Shipment/FG | `ShipmentController.php` | ✅ | FG keluar → mutasi shipment → jurnal shipment_cogs (Dr 5101 HPP / Cr 1203) | ✅ | ✅ | HPP yang dipakai = rm_cost saja, upah belum masuk |
| Inventory Intelligence | `InventoryIntelligenceService.php` | 🟡 | Cover analysis, demand series, trend — untuk FG | — | — | Tidak ada shortage check untuk RM |
| Production Dashboard | `ProductionDashboardController.php` | 🟡 | Bundle counts, sewing outstanding, siap jahit, reject | — | — | Tidak ada indikator: stok negatif, jurnal miss, payroll, shortage |

---

## 3. WORKFLOW PRODUKSI AKTUAL (dari kode)

```
[1] BELI BAHAN BAKU
    PurchaseRequest (draft → approved) → convert → PurchaseOrder (draft → approved)
    → GRN/PurchaseReceipt (draft → posted)
    → stockIn ke Gudang RM (source: purchase_receipt)
    → Jurnal: Dr 1201 Persediaan RM / Cr 2101 Hutang Dagang ✅
    → Payment: Dr 2101 / Cr Kas ✅

[2] CUTTING
    CuttingJob (create) → ambil fabric dari RM lot
    → stockOut RM (source: cutting_job) → Jurnal: Dr 1202 WIP / Cr 1201 RM ✅
    → QC bundle → status ok/reject
    → Post WIP ke WIP-CUT (source: cutting_wip) → Jurnal: Dr 1202 WIP / Cr 1202 WIP ✅
    → cutting_job_bundles: status = siap_jahit

[3] SEWING PICKUP
    SewingPickup (create → store → jurnal)
    → Bundle WIP-CUT → WIP-SEW (source: App\Models\SewingPickup)
    → Jurnal: Dr 1202 WIP / Cr 1202 WIP ✅
    → Supply/kelengkapan (karet, label, benang) → stockOut RM
    → Jurnal supply: Dr 6101 / Cr 1201 RM ✅

[4] SEWING RETURN
    SewingReturn (create → store)
    → WIP-SEW → WIP-FIN (qty_ok) → Jurnal sewing_return_ok: Dr 1202 WIP-FIN / Cr 1202 WIP-SEW ✅
    → WIP-SEW → REJ-SEW (qty_reject) → Jurnal sewing_return_reject: Dr 1204 / Cr 1202 ✅

[5] FINISHING
    FinishingJob (create → post)
    → WIP-FIN → FG (qty_ok) + REJ-FIN (qty_reject)
    → Jurnal finishing_job: Dr 1203 FG / Cr 1202 WIP ✅ | Dr 1204 Reject / Cr 1202 WIP ✅
    → BOM supplies dikonsumsi (finishing_bom)
    → Jurnal finishing_bom: Dr 6101 / Cr 1201 RM ✅

[6] STOCK TRANSFER (REPLENISHMENT ke Toko/RTS)
    StockRequest (rts_replenish) → WH-RTS/WH-PRD
    → stockOut FG / RM ke gudang lain (source: stock_request)
    → JURNAL: ❌ BELUM ADA (Rp 303jt tanpa jurnal)

[7] SHIPMENT / KELUAR BARANG
    Shipment (create → post) → stockOut FG (source: shipment)
    → Jurnal shipment_cogs: Dr 5101 HPP / Cr 1203 Barang Jadi ✅
    → CATATAN: HPP yang dipakai hanya rm_cost, upah cutting/sewing/finishing BELUM masuk

[8] PAYROLL BORONGAN
    SewingPayrollGenerator / CuttingPayrollGenerator → generate piecework_payroll_lines
    → rate_per_pcs dari piece_rates (per employee + kategori)
    → PieceworkPayrollController → finalize → PieceworkPayrollPostingService
    → Jurnal accrual: Dr 6101/WIP / Cr 2102 Hutang Upah ✅ (kode ada)
    → Bayar: Dr 2102 / Cr Kas ✅ (kode ada)
    → STATUS AKTUAL: ❌ BELUM PERNAH DIFINALIZE (data draft, amount=0)

[9] HPP CLOSING
    ProductionCostPeriod (draft) → FgHppAutoService / HppService
    → Hitung unit_cost per SKU (rm + cutting + sewing + finishing)
    → Update item_cost_snapshots
    → STATUS AKTUAL: ❌ BELUM PERNAH DIJALANKAN (cutting=0, sewing=0)
```

---

## 4. TEMUAN KRITIS

### D. Stok Negatif

| Cek | Status | Detail |
|---|---|---|
| Izinkan stok minus | Ya — by design | 25 items punya allow_negative=1 (fabric + supply) |
| Validasi sebelum mutasi keluar | ✅ Ada | InventoryService.stockOut() cek allow_negative per item |
| Stok negatif aktual | ⚠️ Ada 8 items | LBLSIZE -122, FLC280NVY -18.29, FLC280MST -6.2, RIB280NVY -3.54, dst. |
| Adjustment stok resmi | ✅ Ada | inventory_adjustments dengan status, audit trail, jurnal |
| GRN | ✅ Ada (kode) | Data dev kosong karena dihapus |
| Audit trail mutasi | ✅ Ada | inventory_mutations source_type + source_id per transaksi |
| Costing per mutasi | 🟡 Sebagian | 86 mutasi tanpa cost (22 sewing_pickup, 17 inv_adjustment, 16 finishing_bom) |

**Penyebab stok negatif:** Produksi berjalan tapi GRN/restock belum masuk. Karena allow_negative=1, sistem tidak memblok. Ini risiko laporan stok dan HPP tidak akurat.

---

### E. Material Shortage

| Cek | Status |
|---|---|
| BOM data tersedia | ✅ 72 BOMs aktif, 360 lines |
| Stok RM tersedia | ✅ inventory_stocks + inventory_mutations |
| Perbandingan BOM vs stok | ❌ Belum ada halaman/logic |
| Outstanding PR/PO | ❌ Tidak bisa cek (data kosong, belum ada query) |
| Shortage report | ❌ Belum ada |
| Generate PR dari shortage | ❌ Belum ada (PR module ada tapi manual) |

**Data yang sudah siap:** BOM, stok RM, supplier, item. Hanya perlu logic dan tampilan.

---

### F. Reservasi Material

**Kesimpulan: TIDAK ADA konsep reservasi** di sistem ini.

- `inventory_stocks` tidak punya `reserved_qty`, `allocated_qty`, `available_qty`
- `items` punya `default_allocation` tapi ini setting default ke warehouse, bukan reservasi
- `cutting_job_bundles` tidak track kebutuhan bahan per bundle
- Tidak ada tabel `material_reservations` atau sejenisnya

**Desain sederhana yang direkomendasikan (tanpa migrasi besar):**

```
Tambah 3 kolom ke inventory_stocks:
  reserved_qty   numeric default 0   -- dijanjikan ke produksi aktif
  available_qty  AS (qty - reserved_qty) VIRTUAL  -- stok bebas

Tambah tabel material_reservations:
  id, item_id, warehouse_id, source_type (cutting_job/sewing_pickup/etc),
  source_id, qty_reserved, reserved_at, released_at
```

Tapi **jangan implement dulu** — perlu diskusi apakah cutting job sudah "reserve" saat dibuat atau saat dipost.

---

### G. Payroll dan Accounting

| Cek | Status | Detail |
|---|---|---|
| Rate borongan per employee | ✅ Ada | piece_rates: 18 records, per module+employee+category |
| Generator payroll | ✅ Ada | SewingPayrollGenerator + CuttingPayrollGenerator |
| PostingService ke 2102 | ✅ Ada | PieceworkPayrollPostingService: accrual + payment journal |
| Payroll pernah final | ❌ Belum | 1 period status=draft, 25 lines amount=0 |
| Jurnal payroll terbentuk | ❌ Belum | accrual_journal_id = NULL |
| Upah masuk ke HPP | ❌ Belum | cutting/sewing unit_cost = 0 di semua snapshots |
| Risiko double cost | ⚠️ Perlu dicek | Jika payroll di-posting ke WIP dan HPP auto juga menghitung, bisa double |

**Risiko terbesar payroll:** Saat payroll akhirnya difinalize dan dipost ke 2102, tidak otomatis update HPP snapshot. Perlu dipastikan urutan: Payroll final → HPP closing → baru shipment pakai cost baru.

---

### H. HPP Aktual per SKU

| Komponen HPP | Status | Data Tersedia |
|---|---|---|
| Kain (fabric) | 🟡 Terisi | rm_unit_cost ada di 91/105 snapshots aktif |
| Rib / label / karet | 🟡 Partial | Masuk sebagai supply di finishing_bom (konsumsi, bukan reserved) |
| Upah cutting | ❌ Kosong | cutting_unit_cost = 0 di semua snapshots |
| Upah jahit | ❌ Kosong | sewing_unit_cost = 0 di semua snapshots |
| Packing/finishing | ❌ Kosong | finishing_unit_cost = 0 |
| Total HPP per pcs | ❌ Tidak akurat | HPP saat ini = rm_cost saja, upah tidak masuk |

**Data yang kurang untuk HPP lengkap:**
1. Payroll harus difinalize dulu (dapat total upah per periode)
2. Qty produksi per periode harus terhitung (cutting qty per SKU)
3. ProductionCostService.php sudah ada dan bisa mengallokasikan upah ke SKU — tinggal dijalankan

---

### I. Dashboard Kontrol

| Indikator | Status |
|---|---|
| Bundle siap jahit | ✅ Ada (ProductionDashboard) |
| Bundle sedang jahit | ✅ Ada |
| Reject count | ✅ Ada |
| Activity timeline | ✅ Ada |
| **Stok negatif** | ❌ Tidak ada |
| **Mutasi tanpa cost** | ❌ Tidak ada |
| **Jurnal belum terbentuk** | ❌ Tidak ada |
| **WIP terlalu lama** | ❌ Tidak ada |
| **Material shortage** | ❌ Tidak ada |
| **Payroll belum final** | ❌ Tidak ada |
| **Produksi selesai belum masuk FG** | ❌ Tidak ada |

---

## 5. JURNAL COVERAGE DETAIL

**Total cost movement: Rp 1.86 miliar**

| Source Type | Mutasi | Nilai | Jurnal | Status |
|---|---|---|---|---|
| shipment (COGS) | 3.131 | Rp 222jt | shipment_cogs (266) | ✅ |
| App\Models\SewingPickup | 780 | Rp 245jt | 238 journals | ✅ |
| sewing_return_ok | 788 | Rp 267jt | 227 journals | ✅ |
| cutting_wip | 387 | Rp 120jt | 66 journals | ✅ |
| App\Models\FinishingJob | 428 | Rp 118jt | 85 journals | ✅ |
| cutting_job | 98 | Rp 109jt | 66 journals | ✅ |
| purchase_receipt (GRN) | 138 | Rp 143jt | 43 journals | ✅ (orphaned di dev) |
| finishing_bom | 37 | Rp 1.7jt | 5 journals | ✅ |
| sewing_return_reject | 20 | Rp 548rb | 9 journals | ✅ |
| purchase_return | 7 | Rp 9.2jt | 1 journal | ✅ |
| **stock_request** | **778** | **Rp 303jt** | **0** | **❌ MISSING** |
| **App\Models\InventoryAdjustment** | **651** | **Rp 295jt** | **0** | **❌ Backfill needed** |
| **App\Models\WipFinAdjustment** | **15** | **Rp 3.6jt** | **0** | **❌ Backfill needed** |
| **cutting_qc_void** | **15** | **Rp 6.4jt** | **0** | **❌ MISSING** |
| **sewing_return_void_ok** | **10** | **Rp 3.8jt** | **0** | **❌ MISSING** |
| **auto_sr_ok / auto_sr_ok_rts** | **60** | **Rp 6.8jt** | **0** | **❌ MISSING** |
| **prd_dispatch_correction** | **2** | **Rp 420rb** | **0** | **❌ MISSING** |
| **cutting_reject / correction** | **8** | **Rp 261rb** | **0** | **❌ MISSING** |
| **shipment_return** | **3** | **Rp 66rb** | **0** | **❌ MISSING** |

---

## 6. URUTAN IMPLEMENTASI TERBAIK (DARI PALING AMAN)

### Fase 0 — Bersih-bersih data (tidak butuh kode baru)
1. **Backfill jurnal InventoryAdjustment** — jalankan artisan command, jurnal hook sudah ada
2. **Backfill jurnal WipFinAdjustment** — hook sudah ada, data lama belum terjurnal
3. **Laporan stok negatif** — read-only, tidak ubah data apapun
4. **Laporan mutasi tanpa cost** — read-only, audit data

### Fase 1 — Akuntansi lengkap (prioritas)
5. **Jurnal stock_request** — Rp 303jt terbesar yang missing (transfer Dr 1203 / Cr 1201)
6. **Jurnal cutting_qc_void** — void stok reject dari QC
7. **Jurnal sewing_return_void_ok** — void reversal
8. **Jurnal auto_sr_ok** — auto stock request

### Fase 2 — Payroll (prerequisite untuk HPP)
9. **Finalize payroll borongan** — isi rate, generate lines, finalize, cek jurnal 2102
10. **Verifikasi jurnal payroll** — pastikan tidak double posting

### Fase 3 — HPP Closing
11. **Running closing produksi bulanan** — ProductionCostService, update snapshots
12. **Verifikasi HPP per SKU** — cek cutting/sewing unit_cost terisi

### Fase 4 — Shortage Read-Only
13. **Halaman shortage checker** — BOM × qty produksi vs stok RM (read-only, belum generate PR)
14. **Laporan outstanding PR/PO** — beli apa yang sudah dipesan tapi belum datang

### Fase 5 — Auto features
15. **Generate PR dari shortage** — satu tombol buat PR draft
16. **Reservasi material** — tambah kolom reserved_qty ke inventory_stocks
17. **Dashboard kontrol lengkap** — indikator stok negatif, payroll, jurnal, shortage

---

## 7. REKOMENDASI TAHAP 1 (KECIL, AMAN, BISA LANGSUNG)

### Tahap 1A — Laporan Read-Only (tidak ubah kode logic, tidak ubah DB)
Buat halaman baru yang hanya membaca data:

**1. Stok Negatif Report** (`/inventory/negative-stock`)
- Tabel: item + warehouse + qty negatif + allow_negative flag
- Filter: warehouse, kategori
- Tidak ada tombol ubah — hanya info

**2. Mutasi Tanpa Cost Report** (`/inventory/mutations-no-cost`)
- Daftar inventory_mutations di mana total_cost = 0 atau NULL
- Kelompokkan per source_type
- Nilai total yang "hilang dari accounting"

**3. Jurnal Missing Report** (`/accounting/journal-gaps`)
- Bandingkan mutasi vs jurnal coverage per source_type
- Tampilkan total Rp yang belum terjurnal
- Read-only, tidak ada tombol posting

### Tahap 1B — Backfill Jurnal (tidak ubah UI, tidak ubah stok)
**4. Artisan Command: backfill InventoryAdjustment journals**
```bash
php artisan journal:backfill adjustments --dry-run  # preview dulu
php artisan journal:backfill adjustments             # eksekusi
```
- Idempotent — aman dijalankan berkali-kali
- Target: 54 adjustments, Rp 295jt

**5. Artisan Command: backfill WipFinAdjustment journals**
- Target: 12 adjustments, Rp 3.6jt

### Tahap 1C — Payroll (UI sudah ada, tinggal pakai)
**6. Finalize payroll borongan**
- Isi rate_per_pcs di piecework_payroll_lines (dari piece_rates yang sudah ada)
- Finalize period
- Verifikasi jurnal Dr 6101 / Cr 2102 terbuat
- Tidak perlu kode baru, pakai UI yang ada

### Apa yang JANGAN dilakukan di Tahap 1:
- ❌ Jangan buat reservasi material dulu (butuh migrasi + logic kompleks)
- ❌ Jangan auto-generate PR dari shortage dulu (risiko PR sampah)
- ❌ Jangan closing produksi dulu (payroll harus final dulu)
- ❌ Jangan ubah HPP dulu (clos produksi harus jalan dulu)
- ❌ Jangan tambah kolom database dulu

---

## 8. FILE-FILE PENTING YANG PERLU DIKETAHUI

```
ACCOUNTING
  app/Services/Accounting/JournalService.php          ← pusat semua posting jurnal
  app/Http/Controllers/Accounting/                    ← laporan: trial balance, buku besar, P&L

INVENTORY
  app/Services/Inventory/InventoryService.php         ← stockIn/stockOut/adjustByDifference
  app/Services/Inventory/InventoryIntelligenceService.php ← analisis FG demand

PRODUKSI
  app/Http/Controllers/Production/CuttingJobController.php
  app/Http/Controllers/Production/SewingPickupController.php
  app/Http/Controllers/Production/SewingReturnController.php
  app/Http/Controllers/Production/FinishingJobController.php
  app/Services/Production/FinishingBomService.php
  app/Http/Controllers/Production/ProductionDashboardController.php

PURCHASING
  app/Services/Purchasing/GoodsReceiptService.php
  app/Http/Controllers/Purchasing/PurchaseRequestController.php

PAYROLL
  app/Services/Payroll/PieceworkPayrollPostingService.php
  app/Services/Payroll/SewingPayrollGenerator.php
  app/Services/Payroll/CuttingPayrollGenerator.php

HPP/COSTING
  app/Services/Costing/HppService.php
  app/Services/Costing/FgHppAutoService.php
  app/Services/Costing/ProductionCostService.php

STOCK TRANSFER (BELUM ADA JURNAL)
  app/Http/Controllers/Inventory/RtsStockRequestController.php
```

---

*Laporan ini dibuat berdasarkan audit database SQLite dev + scan kode PHP. Tidak ada perubahan dilakukan.*
