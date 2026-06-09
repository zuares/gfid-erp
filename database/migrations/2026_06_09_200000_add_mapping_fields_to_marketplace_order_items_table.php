<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('marketplace_order_items', function (Blueprint $table) {
            // Hanya tambah kolom yang belum ada
            if (! Schema::hasColumn('marketplace_order_items', 'mapping_status')) {
                $table->string('mapping_status', 30)->nullable()
                    ->comment('marketplace_sku_empty | mapping_not_found | mapped')
                    ->after('model_sku');
            }
            if (! Schema::hasColumn('marketplace_order_items', 'cost_status')) {
                $table->string('cost_status', 30)->nullable()
                    ->comment('missing_hpp | complete')
                    ->after('mapping_status');
            }
            if (! Schema::hasColumn('marketplace_order_items', 'profit_status')) {
                $table->string('profit_status', 30)->nullable()
                    ->comment('incomplete | complete')
                    ->after('cost_status');
            }
            if (! Schema::hasColumn('marketplace_order_items', 'issue_reason')) {
                $table->string('issue_reason', 50)->nullable()
                    ->comment('marketplace_sku_empty | mapping_not_found | missing_hpp')
                    ->after('profit_status');
            }
            if (! Schema::hasColumn('marketplace_order_items', 'internal_item_id')) {
                $table->unsignedBigInteger('internal_item_id')->nullable()
                    ->after('issue_reason');
                $table->foreign('internal_item_id')
                    ->references('id')->on('items')
                    ->nullOnDelete();
            }
            if (! Schema::hasColumn('marketplace_order_items', 'hpp_snapshot')) {
                $table->decimal('hpp_snapshot', 15, 4)->nullable()
                    ->comment('HPP per unit saat order disync — NULL jika belum diisi')
                    ->after('internal_item_id');
            }
        });

        // Index untuk query issue center
        Schema::table('marketplace_order_items', function (Blueprint $table) {
            try { $table->index('mapping_status', 'idx_moi_mapping_status'); } catch (\Throwable) {}
            try { $table->index('cost_status',    'idx_moi_cost_status');    } catch (\Throwable) {}
            try { $table->index('profit_status',  'idx_moi_profit_status');  } catch (\Throwable) {}
        });
    }

    public function down(): void
    {
        Schema::table('marketplace_order_items', function (Blueprint $table) {
            // Drop foreign key first (SQLite tidak support drop FK, tapi tetap kita tulis untuk DB lain)
            try { $table->dropForeign(['internal_item_id']); } catch (\Throwable) {}
            try { $table->dropIndex('idx_moi_mapping_status'); } catch (\Throwable) {}
            try { $table->dropIndex('idx_moi_cost_status');    } catch (\Throwable) {}
            try { $table->dropIndex('idx_moi_profit_status');  } catch (\Throwable) {}

            $cols = ['mapping_status','cost_status','profit_status','issue_reason','internal_item_id','hpp_snapshot'];
            foreach ($cols as $col) {
                if (Schema::hasColumn('marketplace_order_items', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
