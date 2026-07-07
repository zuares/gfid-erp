<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Penutupan administratif baris ambil-jahit yang menggantung (WIP Cleanup).
 *
 * qty_closed = qty yang ditutup lewat cleanup (write-off / batalkan), TERPISAH
 * dari qty_returned_ok / qty_returned_reject supaya tidak mengacaukan pelaporan
 * reject maupun alur terima RTS. "Disetor" tetap memakai qty_returned_ok
 * (karena barangnya memang lanjut ke WIP-FIN), jadi tidak butuh kolom ini.
 *
 * Semua nullable/berdefault → aditif, aman untuk data lama.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sewing_pickup_lines', function (Blueprint $table) {
            if (! Schema::hasColumn('sewing_pickup_lines', 'qty_closed')) {
                $table->decimal('qty_closed', 14, 3)->default(0)->after('qty_returned_reject');
            }
            if (! Schema::hasColumn('sewing_pickup_lines', 'close_action')) {
                $table->string('close_action')->nullable()->after('qty_closed'); // write_off | cancel
            }
            if (! Schema::hasColumn('sewing_pickup_lines', 'closed_at')) {
                $table->timestamp('closed_at')->nullable()->after('close_action');
            }
            if (! Schema::hasColumn('sewing_pickup_lines', 'closed_by')) {
                $table->foreignId('closed_by')->nullable()->after('closed_at')
                    ->constrained('users')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('sewing_pickup_lines', function (Blueprint $table) {
            if (Schema::hasColumn('sewing_pickup_lines', 'closed_by')) {
                $table->dropConstrainedForeignId('closed_by');
            }
            foreach (['closed_at', 'close_action', 'qty_closed'] as $col) {
                if (Schema::hasColumn('sewing_pickup_lines', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
