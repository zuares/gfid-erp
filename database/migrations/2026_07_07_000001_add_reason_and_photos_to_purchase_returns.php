<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Retur pembelian — dukungan lapangan:
 * - reason_code per baris (alasan retur terstruktur)
 * - tabel foto bukti per baris (banyak foto per baris)
 * Additive, idempoten.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('purchase_return_lines')
            && ! Schema::hasColumn('purchase_return_lines', 'reason_code')) {
            Schema::table('purchase_return_lines', function (Blueprint $table) {
                $table->string('reason_code', 30)->nullable()->after('notes');
            });
        }

        if (! Schema::hasTable('purchase_return_line_photos')) {
            Schema::create('purchase_return_line_photos', function (Blueprint $table) {
                $table->id();
                $table->foreignId('purchase_return_line_id')
                    ->constrained('purchase_return_lines')
                    ->cascadeOnDelete();
                $table->string('path');            // path relatif di disk "public"
                $table->string('original_name')->nullable();
                $table->timestamps();

                $table->index(['purchase_return_line_id']);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('purchase_return_line_photos')) {
            Schema::dropIfExists('purchase_return_line_photos');
        }

        if (Schema::hasTable('purchase_return_lines')
            && Schema::hasColumn('purchase_return_lines', 'reason_code')) {
            Schema::table('purchase_return_lines', function (Blueprint $table) {
                $table->dropColumn('reason_code');
            });
        }
    }
};
