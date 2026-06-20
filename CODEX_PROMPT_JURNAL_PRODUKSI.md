# CODEX PROMPT — Implementasi Jurnal Otomatis Semua Movement Produksi

## Konteks Proyek

Laravel ERP garmen. Stack: Laravel 11, Blade/Livewire, SQLite dev. Modul produksi: Cutting → Sewing Pickup → Sewing Return → Finishing → Packing.

Double-entry accounting sudah ada via `JournalService` (`app/Services/Accounting/JournalService.php`).

---

## Yang Sudah Selesai

1. **`finishing_job`** — jurnal WIP-FIN → Barang Jadi sudah dibuat di session sebelumnya:
   - Dr 1203 Barang Jadi / Cr 1202 WIP (qty_ok)
   - Dr 1204 Barang Cacat / Cr 1202 WIP (qty_reject)
   - Method: `JournalService::postFinishingJob()`
   - Dipanggil di `FinishingJobController::postCreatedJob()` **di luar** DB::transaction

2. **`opening_balance_batch`** — sudah ada.

---

## Yang Perlu Dikerjakan Sekarang

### Prioritas 1: finishing_bom
Source type `finishing_bom` = konsumsi bahan baku/supplies saat finishing job diposting.
- Di-generate oleh `FinishingBomService::applySupOnlyForPostedJob()`
- Source_id = `finishing_job_line_id` (integer), bukan finishing_job_id
- Mutasi dari warehouse `RM` (id=2, Bahan Baku), qty_change negatif
- Jurnal: **Dr 6101 Biaya Operasional Umum / Cr 1201 Persediaan Bahan Baku**
- Total nilai di DB: ~Rp 1,743,179 (37 mutasi)
- Dipanggil bersamaan dengan posting FinishingJob, jadi bisa dipost sekalian di `postCreatedJob()`

### Prioritas 2: purchase_receipt
Source type `purchase_receipt` = penerimaan barang dari supplier (GRN).
- Controller: `app/Http/Controllers/Purchasing/PurchaseReceiptController.php`
- Jurnal: **Dr 1201 Persediaan Bahan Baku / Cr 2101 Hutang Dagang**
- Total nilai: ~Rp 143 juta (138 mutasi)
- **PENTING**: Jangan hapus/ubah logic GRN yang sudah ada. Tambahkan jurnal saja.

### Prioritas 3: purchase_return
- Jurnal: **Dr 2101 Hutang Dagang / Cr 1201 Persediaan Bahan Baku**
- Total nilai: ~Rp 9 juta

### Prioritas 4: shipment (penjualan keluar)
- Jurnal: **Dr 5101 HPP / Cr 1203 Barang Jadi** (untuk COGS)
- Total nilai: ~Rp 222 juta (3131 mutasi)
- Controller: `app/Http/Controllers/Fulfillment/ShipmentController.php` (atau nama serupa)

### Prioritas 5: cutting_job & cutting_wip
- `cutting_job`: material masuk WIP-CUT dari RM → Dr 1202 WIP / Cr 1201 Bahan Baku
- `cutting_wip`: hasil cutting ke WIP-SEW atau sub-WIP
- Total nilai: ~Rp 109-120 juta

### Prioritas 6: SewingPickup & sewing_return_ok
- SewingPickup: RM → WIP-SEW → Dr 1202 WIP / Cr 1201 Bahan Baku
- sewing_return_ok: selesai jahit, WIP masuk ke WIP-FIN
- Total nilai: ~Rp 245-267 juta

---

## Chart of Accounts (accounts table)

```
1101  Kas Tunai
1111  Bank Jago (Bisnis)
1112  Bank BCA (Operasional)
1201  Persediaan Bahan Baku
1202  Persediaan WIP
1203  Persediaan Barang Jadi
1204  Persediaan Barang Cacat
1301  Piutang Dagang
1302  Piutang Marketplace
2101  Hutang Dagang
4101  Penjualan
5101  Harga Pokok Penjualan (HPP)
6101  Biaya Operasional Umum
6102  Biaya Transport / Ongkir
6110  Biaya Packing
```

---

## Warehouse IDs

```
id=2   RM         Bahan Baku
id=3   WIP-CUT    Sedang Cutting
id=4   WIP-SEW    Sedang Jahit
id=5   WIP-FIN    Sedang Finishing
id=6   WIP-PACK   Sedang Packing
id=7   FG         Finished Goods
id=10  REJ-CUT    Reject Cutting
id=11  REJ-SEW    Reject Sewing
id=12  REJ-FIN    Reject Finishing
```

---

## Arsitektur JournalService

File: `app/Services/Accounting/JournalService.php`

Key methods yang sudah ada:
```php
// Post jurnal (idempotent via source_type + source_id)
public function post(string $date, string $sourceType, int $sourceId, string $memo, array $lines): Journal

// Void jurnal berdasarkan source
public function voidBySource(string $sourceType, int $sourceId, string $reason): void

// Helper
private function accountIdByCode(string $code): int
private function dateOnly($value): string
```

Constants yang sudah ada:
```php
public const CODE_INV_WIP    = '1202';
public const CODE_INV_FG     = '1203';
public const CODE_INV_DEFECT = '1204';
public const CODE_EXP_OPEX   = '6101';
public const SRC_FINISHING_JOB        = 'finishing_job';
public const SRC_WIP_FIN_ADJUSTMENT   = 'wip_fin_adjustment';
```

Perlu tambah constants baru:
```php
public const CODE_INV_RM     = '1201';
public const CODE_PAYABLE    = '2101';
public const CODE_HPP        = '5101';
public const SRC_FINISHING_BOM    = 'finishing_bom';
public const SRC_PURCHASE_RECEIPT = 'purchase_receipt';
public const SRC_PURCHASE_RETURN  = 'purchase_return';
public const SRC_SHIPMENT         = 'shipment';
public const SRC_CUTTING_JOB      = 'cutting_job';
public const SRC_SEWING_PICKUP    = 'App\\Models\\SewingPickup';
```

---

## Cara Buat Method postFinishingBom()

finishing_bom source_id = finishing_job_line_id, bukan finishing_job_id.
Untuk post satu finishing job, kita perlu aggregate semua BOM mutations dari job tersebut.

```php
// Cara ambil finishing_job_id dari job
// finishing_bom mutations memiliki source_id = finishing_job_line_id
// finishing_job_lines.finishing_job_id = finishing job

// Query pattern:
DB::table('inventory_mutations as im')
    ->join('finishing_job_lines as fjl', 'fjl.id', '=', 'im.source_id')
    ->where('im.source_type', 'finishing_bom')
    ->where('fjl.finishing_job_id', $job->id)
    ->where('im.qty_change', '<', 0)
    ->selectRaw('im.item_id, SUM(ABS(im.total_cost)) as total_cost')
    ->groupBy('im.item_id')
    ->get();
```

Jurnal:
- Satu credit line per item: Cr 1201 sebesar total_cost
- Satu debit line aggregate: Dr 6101 sebesar total seluruh BOM cost

---

## Implementasi Existing postFinishingJob() untuk referensi

```php
public function postFinishingJob(\App\Models\FinishingJob $job): ?Journal
{
    // Idempotent guard
    $existing = Journal::query()
        ->where('source_type', self::SRC_FINISHING_JOB)
        ->where('source_id', $job->id)
        ->whereNull('voided_at')
        ->first();
    if ($existing) return $existing;

    $job->loadMissing(['lines']);
    if ($job->lines->isEmpty()) return null;

    $wipId    = $this->accountIdByCode(self::CODE_INV_WIP);
    $fgId     = $this->accountIdByCode(self::CODE_INV_FG);
    $defectId = $this->accountIdByCode(self::CODE_INV_DEFECT);
    $wipWarehouseId = (int) \App\Models\Warehouse::where('code', 'WIP-FIN')->value('id');

    // SUM cost dari OUT mutations WIP-FIN per item
    $mutCosts = DB::table('inventory_mutations')
        ->where('source_type', \App\Models\FinishingJob::class)
        ->where('source_id', $job->id)
        ->where('warehouse_id', $wipWarehouseId)
        ->where('qty_change', '<', 0)
        ->groupBy('item_id')
        ->selectRaw('item_id, SUM(ABS(total_cost)) as total_cost, SUM(ABS(qty_change)) as total_qty')
        ->get()
        ->keyBy('item_id');

    $journalLines = [];
    $totalWip = 0.0;

    foreach ($job->lines as $line) {
        // ... aggregate per item, split ok vs reject ...
    }

    $journalLines[] = ['account_id' => $wipId, 'debit' => 0, 'credit' => round($totalWip, 2)];

    return $this->post(
        $this->dateOnly($job->date),
        self::SRC_FINISHING_JOB,
        (int) $job->id,
        "Finishing {$job->code} — WIP → Barang Jadi",
        $journalLines
    );
}
```

---

## Aturan & Constraints (WAJIB DIIKUTI)

- **Jangan hapus modul GRN** (route, controller, model, view, migration GRN)
- **Jangan ubah logic GRN post/unpost** — hanya tambahkan jurnal
- **Jangan hapus migration** — migration hanya additive, jangan drop column
- **Jangan rename status lama**
- **Jangan hapus data**
- **Jangan ubah stok, journal existing, atau payment**
- **Jangan ubah Supplier Invoice**
- **Jangan refactor besar** — tambah/modifikasi minimal
- **Backup semua file yang diubah** sebelum modifikasi
- **Frozen page**: `/marketplace/picking` — jangan diubah
- **QC tidak wajib** — GRN tetap bisa jalan tanpa QC
- **Migration additive only**
- **Jangan jalankan migrate otomatis** kecuali diminta user

---

## Urutan Pengerjaan yang Disarankan

1. **finishing_bom** — tambah `postFinishingBom(FinishingJob $job)` di JournalService, panggil dari `FinishingJobController::postCreatedJob()` setelah `postFinishingJob()`
2. **purchase_receipt** — tambah `postPurchaseReceipt()` di JournalService, hook ke PurchaseReceiptController saat status posted
3. **purchase_return** — tambah `postPurchaseReturn()`, hook ke controller return
4. **shipment** — tambah `postShipment()` untuk COGS (HPP), hook ke ShipmentController
5. **cutting_job** — tambah `postCuttingJob()`, cutting material masuk WIP
6. **SewingPickup & sewing_return_ok** — tambah method untuk flow sewing

Setiap method harus:
- Idempotent (cek existing journal sebelum post)
- Pakai source_type + source_id yang konsisten
- Dipanggil SETELAH DB::transaction inventory selesai
- Wrapped dalam try/catch dengan Log::warning agar tidak crash flow utama

---

## File Utama yang Relevan

```
app/Services/Accounting/JournalService.php         ← UTAMA, tambah methods di sini
app/Http/Controllers/Production/FinishingJobController.php
app/Http/Controllers/Purchasing/PurchaseReceiptController.php
app/Services/Production/FinishingBomService.php    ← referensi source finishing_bom
app/Models/FinishingJob.php
app/Models/FinishingJobLine.php
app/Models/Journal.php
app/Models/JournalLine.php
app/Models/Warehouse.php
app/Models/Account.php (atau ChartOfAccount)
```

---

## Verifikasi setelah implementasi

Jalankan query SQLite ini untuk cek balance:

```sql
SELECT j.id, j.source_type, j.source_id, j.memo,
       SUM(jl.debit) as total_debit,
       SUM(jl.credit) as total_credit,
       SUM(jl.debit) - SUM(jl.credit) as selisih
FROM journals j
JOIN journal_lines jl ON jl.journal_id = j.id
WHERE j.voided_at IS NULL
GROUP BY j.id
HAVING ABS(selisih) > 0.01
ORDER BY j.id DESC;
-- Harus return 0 rows (semua balanced)
```
