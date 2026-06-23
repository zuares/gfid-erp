============================================================
 GFID – ALUR KERJA DEVELOPMENT & OPERASIONAL
============================================================

MULAI DEVELOPMENT
------------------------------------------------------------
php artisan dev:start fix nama-pekerjaan
php artisan dev:start feat nama-fitur-baru

Otomatis: health check → buat branch git → (feat) copy DB.

Opsi tambahan:
  --copy-db        paksa copy DB meski mode fix
  --activate-db    langsung arahkan .env ke DB copy
  --no-branch      jangan buat branch baru
  --force          lanjut walau health check ada masalah

Contoh:
  php artisan dev:start fix filter-payments
  php artisan dev:start feat purchasing-dashboard --activate-db


SELESAI DEVELOPMENT / KEMBALI KE OPERASIONAL
------------------------------------------------------------
php artisan system:check

Alias: ops:stabil  |  app:check  |  app:stabil

Ini: backup DB → snapshot → cek kesehatan sistem.

Kalau mau cepat (skip backup):
  php artisan system:check --no-backup --no-snapshot

Yang dicek otomatis:
  - Stok minus
  - Lot minus
  - Jurnal tidak balance (debit ≠ kredit)
  - Dokumen draft > 7 hari
  - Bundle ambil jahit belum selesai
  - Bundle cutting masih WIP


SETELAH CODING (sebelum merge):
------------------------------------------------------------
php artisan migrate
php artisan optimize:clear
php artisan system:check --no-backup --no-snapshot


============================================================
 GFID – DATABASE BACKUP & RESTORE ARTISAN COMMANDS
============================================================

1) Membuat backup database SQLite terbaru
------------------------------------------------------------
php artisan db:backup


2) Melihat daftar semua file backup
------------------------------------------------------------
php artisan db:backup:list


3) Melihat daftar backup dengan batas jumlah tertentu
------------------------------------------------------------
php artisan db:backup:list --limit=5
php artisan db:backup:list --limit=10


4) Restore database dari file backup tertentu
------------------------------------------------------------
php artisan db:restore backup_20251120_163501.sqlite


5) Backup otomatis sebelum migrate:fresh
------------------------------------------------------------
php artisan migrate:fresh

(Perintah ini otomatis akan membuat backup sebelum database direset)


============================================================
 Lokasi File Backup:
 storage/backups/
============================================================


# 🧱 LARAVEL MIGRATION (RAPI & TERPISAH)

# 1. Buat tabel (CREATE TABLE)
php artisan make:migration create_products_table

# 2. Tambah kolom ke tabel (ADD COLUMN)
php artisan make:migration add_stock_to_products_table --table=products

# 3. Ubah kolom (CHANGE COLUMN)
php artisan make:migration change_price_type_in_products_table --table=products

# 4. Tambah foreign key (ADD FOREIGN KEY)
php artisan make:migration add_user_id_foreign_to_products_table --table=products

# 5. Hapus kolom (DROP COLUMN)
php artisan make:migration drop_status_from_products_table --table=products

# 6. Hapus tabel (DROP TABLE)
php artisan make:migration drop_products_table --table=products





===========================================
 GFID : CODE GENERATOR - PO / INV / LOT
===========================================

Nama      : CodeGenerator
File      : app/Helpers/CodeGenerator.php
Fungsi    : Generate kode aman (race-condition safe)
Format    : PREFIX-YYYYMMDD-###

Contoh:
  PO-20251121-001
  INV-20251121-002
  LOT-20251121-003
  TRF-20251121-004


===========================================
 1. PERSIAPAN TABEL RUNNING NUMBER
===========================================

Migration:
  database/migrations/xxxx_xx_xx_xxxxxx_create_running_numbers_table.php

Isi file (ringkas):

  Schema::create('running_numbers', function (Blueprint $table) {
      $table->id();
      $table->string('prefix', 20);
      $table->date('date');
      $table->unsignedInteger('last_number')->default(0);
      $table->timestamps();
      $table->unique(['prefix', 'date']);
  });

Perintah:

  php artisan migrate


===========================================
 2. FILE HELPER CODE GENERATOR
===========================================

Lokasi:

  app/Helpers/CodeGenerator.php

Isi utama:

  namespace App\Helpers;

  use Illuminate\Support\Facades\DB;

  class CodeGenerator
  {
      public static function generate(string $prefix = 'PO'): string
      {
          // transaction + lockForUpdate + retry
          // return: PREFIX-YYYYMMDD-###
      }
  }


===========================================
 3. DAFTARKAN HELPER DI COMPOSER
===========================================

File:

  composer.json

Tambahkan di bagian "autoload" → "files":

  "autoload": {
      "psr-4": {
          "App\\": "app/"
      },
      "files": [
          "app/Helpers/CodeGenerator.php"
      ]
  }

Perintah:

  composer dump-autoload


===========================================
 4. PENGGUNAAN DI CONTROLLER
===========================================

Import:

  use App\Helpers\CodeGenerator;

Contoh: Generate kode Purchase Order (PO):

  $code = CodeGenerator::generate('PO');

  // hasil contoh:
  // PO-20251121-001
  // PO-20251121-002
  // dst...


===========================================
 5. CONTOH IMPLEMENTASI STORE PURCHASE ORDER
===========================================

  use App\Helpers\CodeGenerator;
  use App\Models\PurchaseOrder;
  use Illuminate\Http\Request;

  public function store(Request $request)
  {
      $data = $request->validate([
          'supplier_id' => 'required|exists:suppliers,id',
          'date'        => 'required|date',
      ]);

      // generate kode aman
      $data['code']       = CodeGenerator::generate('PO');
      $data['status']     = 'draft';
      $data['created_by'] = $request->user()->id;

      $po = PurchaseOrder::create($data);

      return redirect()
          ->route('purchase_orders.show', $po)
          ->with('success', 'PO berhasil dibuat: ' . $po->code);
  }


===========================================
 6. PREFIX LAIN (TINGGAL GANTI)
===========================================

Invoice:

  $invCode = CodeGenerator::generate('INV');
  // INV-20251121-001

LOT Kain:

  $lotCode = CodeGenerator::generate('LOT');
  // LOT-20251121-001

Transfer Gudang:

  $trfCode = CodeGenerator::generate('TRF');
  // TRF-20251121-001

Production Batch:

  $pbCode = CodeGenerator::generate('PB');
  // PB-20251121-001


===========================================
 7. SYARAT DI TABEL TUJUAN
===========================================

Tabel yang pakai kode wajib punya kolom:

  code VARCHAR UNIQUE

Contoh di migration:

  $table->string('code')->unique();

Ini mencegah duplikasi kode jika ada bug di luar helper.


===========================================
 8. RINGKASAN
===========================================

- Buat tabel: running_numbers
- Buat helper: app/Helpers/CodeGenerator.php
- Daftarkan di composer.json → autoload.files
- Panggil di controller:
    CodeGenerator::generate('PO');
    CodeGenerator::generate('INV');
    CodeGenerator::generate('LOT');
- Pastikan kolom "code" di tabel tujuan bersifat UNIQUE

Selesai.


