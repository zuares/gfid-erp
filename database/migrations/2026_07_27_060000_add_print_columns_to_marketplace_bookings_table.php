<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cetak resi booking (Pesanan Kilat) menambah hitungan cetak & waktu cetak
 * pertama pada marketplace_bookings — kolomnya belum pernah dibuat sehingga
 * cetak resi gagal: "no such column: print_count".
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('marketplace_bookings', function (Blueprint $table) {
            if (! Schema::hasColumn('marketplace_bookings', 'print_count')) {
                $table->unsignedInteger('print_count')->default(0)->after('booking_status');
            }
            if (! Schema::hasColumn('marketplace_bookings', 'printed_at')) {
                $table->timestamp('printed_at')->nullable()->after('print_count');
            }
        });
    }

    public function down(): void
    {
        Schema::table('marketplace_bookings', function (Blueprint $table) {
            if (Schema::hasColumn('marketplace_bookings', 'print_count')) {
                $table->dropColumn('print_count');
            }
            if (Schema::hasColumn('marketplace_bookings', 'printed_at')) {
                $table->dropColumn('printed_at');
            }
        });
    }
};
