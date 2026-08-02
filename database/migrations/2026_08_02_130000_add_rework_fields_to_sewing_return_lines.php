<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sewing_return_lines', function (Blueprint $table) {
            if (!Schema::hasColumn('sewing_return_lines', 'reject_bahan_action')) {
                $table->string('reject_bahan_action', 30)
                    ->nullable()
                    ->after('qty_reject')
                    ->index();
            }

            if (!Schema::hasColumn('sewing_return_lines', 'result_item_id')) {
                $table->foreignId('result_item_id')
                    ->nullable()
                    ->after('reject_bahan_action')
                    ->constrained('items')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('sewing_return_lines', function (Blueprint $table) {
            if (Schema::hasColumn('sewing_return_lines', 'result_item_id')) {
                $table->dropConstrainedForeignId('result_item_id');
            }

            if (Schema::hasColumn('sewing_return_lines', 'reject_bahan_action')) {
                $table->dropColumn('reject_bahan_action');
            }
        });
    }
};
