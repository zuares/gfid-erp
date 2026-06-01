<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Index performa untuk Dashboard Produksi.
 *
 * Builder dashboard memfilter berat dengan whereBetween('date') + operator_id
 * pada sewing_pickups & sewing_returns, lalu join sewing_return_lines lewat
 * sewing_return_id & sewing_pickup_line_id. sewing_return_lines sebelumnya
 * TIDAK punya index sama sekali padahal jadi sumber join terbanyak.
 *
 * Murni read-side / performa — tidak mengubah data atau perilaku tulis.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sewing_pickups', function (Blueprint $table) {
            $table->index('date', 'idx_sp_date');
            $table->index('operator_id', 'idx_sp_operator');
        });

        Schema::table('sewing_returns', function (Blueprint $table) {
            $table->index('date', 'idx_sr_date');
            $table->index('operator_id', 'idx_sr_operator');
        });

        Schema::table('sewing_return_lines', function (Blueprint $table) {
            $table->index('sewing_return_id', 'idx_srl_return');
            $table->index('sewing_pickup_line_id', 'idx_srl_pickup_line');
        });
    }

    public function down(): void
    {
        Schema::table('sewing_pickups', function (Blueprint $table) {
            $table->dropIndex('idx_sp_date');
            $table->dropIndex('idx_sp_operator');
        });

        Schema::table('sewing_returns', function (Blueprint $table) {
            $table->dropIndex('idx_sr_date');
            $table->dropIndex('idx_sr_operator');
        });

        Schema::table('sewing_return_lines', function (Blueprint $table) {
            $table->dropIndex('idx_srl_return');
            $table->dropIndex('idx_srl_pickup_line');
        });
    }
};
