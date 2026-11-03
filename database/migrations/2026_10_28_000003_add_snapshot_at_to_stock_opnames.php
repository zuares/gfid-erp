<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('stock_opnames', 'snapshot_at')) {
            Schema::table('stock_opnames', function (Blueprint $table): void {
                $table->timestamp('snapshot_at')->nullable()->after('date');
                $table->index(['warehouse_id', 'snapshot_at'], 'stock_opnames_wh_snapshot_idx');
            });
        }

        // Dokumen lama dipertahankan dengan cutoff deterministik.
        DB::table('stock_opnames')
            ->whereNull('snapshot_at')
            ->update(['snapshot_at' => DB::raw('created_at')]);
    }

    public function down(): void
    {
        if (Schema::hasColumn('stock_opnames', 'snapshot_at')) {
            Schema::table('stock_opnames', function (Blueprint $table): void {
                $table->dropIndex('stock_opnames_wh_snapshot_idx');
                $table->dropColumn('snapshot_at');
            });
        }
    }
};
