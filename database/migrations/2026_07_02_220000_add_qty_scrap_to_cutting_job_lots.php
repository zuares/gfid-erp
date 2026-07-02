<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('cutting_job_lots', 'qty_scrap')) {
            return;
        }

        Schema::table('cutting_job_lots', function (Blueprint $table) {
            // Kain terbuang (perca/waste) saat pencatatan sisa — bagian dari
            // pemakaian, TIDAK dikembalikan ke stok. Dipakai untuk hitung
            // scrap% aktual sebagai umpan balik ke BOM.
            $table->decimal('qty_scrap', 12, 4)->nullable()->default(0)->after('qty_sisa_fabric');
        });
    }

    public function down(): void
    {
        if (!Schema::hasColumn('cutting_job_lots', 'qty_scrap')) {
            return;
        }

        Schema::table('cutting_job_lots', function (Blueprint $table) {
            $table->dropColumn('qty_scrap');
        });
    }
};
