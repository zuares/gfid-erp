<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('finishing_repair_lines', function (Blueprint $table) {
            if (!Schema::hasColumn('finishing_repair_lines', 'reject_item_id')) {
                $table->foreignId('reject_item_id')
                    ->nullable()
                    ->after('item_id')
                    ->constrained('items')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('finishing_repair_lines', 'qty_reject')) {
                $table->decimal('qty_reject', 12, 3)
                    ->default(0)
                    ->after('qty_ok');
            }
        });
    }

    public function down(): void
    {
        Schema::table('finishing_repair_lines', function (Blueprint $table) {
            if (Schema::hasColumn('finishing_repair_lines', 'reject_item_id')) {
                $table->dropConstrainedForeignId('reject_item_id');
            }

            if (Schema::hasColumn('finishing_repair_lines', 'qty_reject')) {
                $table->dropColumn('qty_reject');
            }
        });
    }
};
