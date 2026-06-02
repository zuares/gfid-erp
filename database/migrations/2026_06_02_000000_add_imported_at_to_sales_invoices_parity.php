<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * CORRECTIVE PARITY MIGRATION.
 *
 * Tujuan: menyamakan struktur DEV dengan PROD untuk kolom
 * `sales_invoices.imported_at`.
 *
 * Latar belakang:
 * - Di PROD kolom `imported_at` SUDAH ADA (ditambahkan oleh migrasi lama
 *   "2026_05_31_170742_add_marketplace_fields_to_sales_invoices_clean" yang
 *   filenya sudah tidak ada di repo / dikonsolidasi).
 * - Di DEV kolom tersebut TIDAK ADA, sehingga terjadi drift 1 kolom.
 *
 * Migrasi ini bersifat IDEMPOTENT & ADDITIVE:
 * - up()   : hanya menambahkan kolom bila tabel ada DAN kolom belum ada.
 *            Jadi aman dijalankan di DEV (akan menambah) maupun di PROD
 *            (akan no-op karena kolom sudah ada).
 * - down() : SENGAJA no-op. TIDAK boleh drop `imported_at`, karena di PROD
 *            kolom itu sudah eksis sebelum migrasi ini dibuat; men-drop-nya
 *            saat rollback akan menghancurkan struktur PROD yang sah.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('sales_invoices')) {
            return;
        }

        if (Schema::hasColumn('sales_invoices', 'imported_at')) {
            // Kolom sudah ada (kasus PROD) -> tidak melakukan apa-apa.
            return;
        }

        Schema::table('sales_invoices', function (Blueprint $table) {
            // Tipe datetime nullable, ditempatkan setelah raw_source_file
            // (mengikuti urutan kolom PROD). after() diabaikan oleh SQLite,
            // tapi tetap aman dan tidak menimbulkan error.
            $table->dateTime('imported_at')->nullable()->after('raw_source_file');
        });
    }

    public function down(): void
    {
        // NO-OP yang disengaja.
        //
        // Ini adalah corrective parity migration. Kolom `imported_at` sudah
        // ada di PROD sebelum migrasi ini dibuat, sehingga rollback TIDAK boleh
        // menghapusnya (akan merusak struktur PROD yang valid). Dibiarkan kosong
        // dengan sengaja agar `migrate:rollback` tidak menyentuh kolom existing.
    }
};
