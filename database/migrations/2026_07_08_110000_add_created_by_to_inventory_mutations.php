<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * created_by pada inventory_mutations — pelaku setiap pergerakan stok,
 * supaya kolom "Oleh" di Log Produksi terisi di level event stok
 * (bukan hanya di journals/movements). Aditif & nullable.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_mutations', function (Blueprint $table) {
            if (! Schema::hasColumn('inventory_mutations', 'created_by')) {
                $table->foreignId('created_by')->nullable()->after('notes')
                    ->constrained('users')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('inventory_mutations', function (Blueprint $table) {
            if (Schema::hasColumn('inventory_mutations', 'created_by')) {
                $table->dropConstrainedForeignId('created_by');
            }
        });
    }
};
