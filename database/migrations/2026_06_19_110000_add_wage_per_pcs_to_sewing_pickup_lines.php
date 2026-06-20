<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sewing_pickup_lines', function (Blueprint $table) {
            if (! Schema::hasColumn('sewing_pickup_lines', 'wage_per_pcs')) {
                // Upah jahit per pcs — diisi otomatis dari PieceRateService saat Ambil Jahit.
                // Labor cost sudah masuk ke unit_cost WIP-SEW; Setor Jahit tidak perlu tambah lagi.
                $table->decimal('wage_per_pcs', 15, 4)->default(0)->after('unit_cost');
            }
        });
    }

    public function down(): void
    {
        Schema::table('sewing_pickup_lines', function (Blueprint $table) {
            if (Schema::hasColumn('sewing_pickup_lines', 'wage_per_pcs')) {
                $table->dropColumn('wage_per_pcs');
            }
        });
    }
};
