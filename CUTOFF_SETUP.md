# Cut-off Date & Opening Balance — Setup Guide

## Apa yang diimplementasi

| Komponen | File | Keterangan |
|---|---|---|
| Migration | `database/migrations/2026_06_19_000001_create_system_settings_table.php` | Tabel `system_settings` (key-value) |
| Model | `app/Models/SystemSetting.php` | Static helpers: `get()`, `set()`, `cutoffDate()`, `isLegacy()` |
| Controller | `app/Http/Controllers/Settings/SystemSettingController.php` | UI set/hapus cut-off |
| Routes | `routes/web/settings.php` | `/settings/system` (owner only) |
| View | `resources/views/settings/system/index.blade.php` | Halaman pengaturan + panduan |
| Artisan | `app/Console/Commands/ShowSystemSettings.php` | `php artisan settings:show` |
| Sidebar | `resources/views/layouts/partials/sidebar.blade.php` | Link "Pengaturan Sistem" (owner) |

**File yang diubah (ada .bak backup):**
- `routes/web.php` → tambah `require settings.php`
- `app/Http/Controllers/Inventory/StockCardController.php` → default from_date = cutoff
- `app/Http/Controllers/Accounting/JournalController.php` → default from = cutoff
- `resources/views/layouts/partials/sidebar.blade.php` → tambah link menu

---

## LANGKAH 1: Jalankan Migration

```bash
# Di terminal project (bukan sandbox)
cd /path/to/gfid-dev

php artisan migrate --path=database/migrations/2026_06_19_000001_create_system_settings_table.php

# Verifikasi tabel terbuat
php artisan tinker --execute="echo DB::table('system_settings')->count();"
```

---

## LANGKAH 2: Test Artisan Command

```bash
# Lihat status settings
php artisan settings:show

# Set cut-off date via terminal
php artisan settings:show --set-cutoff=2026-07-01

# Cek kembali
php artisan settings:show

# Hapus cut-off (undo)
php artisan settings:show --clear-cutoff
```

---

## LANGKAH 3: Test UI

1. Login sebagai owner
2. Buka sidebar → klik **"⚙️ Pengaturan Sistem"** → URL: `/settings/system`
3. Pilih tanggal cut-off → klik **Simpan**
4. Lihat statistik mutasi sebelum/sesudah cut-off
5. Buka Buku Besar (`/accounting/journals`) → harusnya default dari tanggal cut-off
6. Buka Kartu Stok (`/inventory/stock-card`) → harusnya default dari tanggal cut-off
7. Test **show_legacy**: tambahkan `?show_legacy=1` di URL → data lama muncul kembali

---

## LANGKAH 4: Input Opening Balance (Alur Lengkap)

Setelah cut-off di-set, ikuti urutan ini:

### A. Opening Stock Bahan Baku (Gudang RM)
```
Inventory → Stock Opname → Buat Baru → Tipe: Opening → Gudang: RM
→ Isi physical_qty dan unit_cost per item
→ Review → Finalize
→ Hasil: inventory_mutation source_type = stock_opname_adjustment
```

### B. Opening Stock Barang Jadi (Gudang FG/WH-RTS)
```
Sama seperti A, tapi pilih gudang FG atau WH-RTS
```

### C. Opening Balance Accounting
```
Accounting → Opening Balance Batch → Buat Baru
→ Isi semua akun dengan saldo awal
→ Simpan → source_type = opening_balance_batch
```

### D. Verifikasi
```
Accounting → Trial Balance → set as_of = cut-off date
→ Pastikan total debit = total kredit
```

---

## ROLLBACK (jika ada masalah)

### Rollback migration saja (paling aman):
```bash
php artisan migrate:rollback --step=1
# Ini hanya hapus tabel system_settings, tidak ada data lain yang berubah
```

### Kembalikan file yang diubah:
```bash
# StockCardController
cp app/Http/Controllers/Inventory/StockCardController.php.bak_cutoff \
   app/Http/Controllers/Inventory/StockCardController.php

# JournalController
cp app/Http/Controllers/Accounting/JournalController.php.bak_cutoff \
   app/Http/Controllers/Accounting/JournalController.php

# Sidebar
cp resources/views/layouts/partials/sidebar.blade.php.bak_cutoff \
   resources/views/layouts/partials/sidebar.blade.php

# web.php
cp routes/web.php.bak_cutoff routes/web.php
```

---

## Yang TIDAK berubah

- ✅ Semua data lama di database — tidak ada yang dihapus
- ✅ Flow produksi (cutting, sewing, finishing)
- ✅ InventoryService (stockIn/stockOut)
- ✅ JournalService
- ✅ StockOpname + StockOpnameService
- ✅ OpeningBalanceController
- ✅ OpeningBalanceBatchController
- ✅ Semua migration lama

---

## Cara lihat data lama di laporan (setelah cut-off di-set)

| Laporan | URL untuk lihat data lama |
|---|---|
| Kartu Stok | `/inventory/stock-card?show_legacy=1` |
| Buku Besar | `/accounting/journals?show_legacy=1` |
| Kartu Stok item tertentu | `/inventory/stock-card?item_id=X&from_date=2020-01-01` |
| Semua jurnal | `/accounting/journals?from=2020-01-01` |

---

## Cara pakai `SystemSetting` di kode lain

```php
use App\Models\SystemSetting;

// Baca cut-off date
$cutoff = SystemSetting::cutoffDateString();  // '2026-07-01' atau null

// Cek apakah sebuah tanggal adalah legacy
if (SystemSetting::isLegacy($someDate)) {
    // data ini sebelum cut-off
}

// Default from-date untuk query filter
$from = $request->input('from', SystemSetting::defaultFromDate());

// Cek apakah cut-off sudah di-set
if (SystemSetting::hasCutoff()) {
    // tampilkan badge "Data sejak {cutoff}"
}
```
