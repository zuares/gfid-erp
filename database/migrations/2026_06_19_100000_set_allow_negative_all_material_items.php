<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Set allow_negative = true untuk SEMUA item bertipe 'material'.
 *
 * Latar belakang:
 * - Di lapangan, urutan input admin (GRN, BOM) sering tertinggal dari
 *   aktivitas produksi operator.
 * - Stok negatif bukan error — ini sinyal bahwa material perlu dibeli.
 * - Shortage page (MaterialShortageService) sudah menampilkan item
 *   dengan stok negatif sebagai prioritas pembelian.
 *
 * ROLLBACK: kembalikan hanya 7 item yang sebelumnya false.
 * Item yang sebelumnya sudah true tidak terpengaruh rollback.
 */
return new class extends Migration
{
    // Item yang sebelumnya allow_negative = FALSE — untuk rollback presisi
    private const PREVIOUSLY_FALSE = [
        'PLY20X30', 'PLY25X35', 'PLY30X40',
        'THR100X150', 'THR57X30', 'THR57X40', 'THR80X50',
    ];

    public function up(): void
    {
        DB::table('items')
            ->where('type', 'material')
            ->update(['allow_negative' => true]);
    }

    public function down(): void
    {
        // Kembalikan hanya yang sebelumnya false
        DB::table('items')
            ->whereIn('code', self::PREVIOUSLY_FALSE)
            ->update(['allow_negative' => false]);
    }
};
