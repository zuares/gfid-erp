<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_receipts', function (Blueprint $table) {
            // Nomor surat jalan dari supplier (opsional)
            $table->string('surat_jalan_no', 100)
                ->nullable()
                ->after('notes')
                ->comment('Nomor surat jalan dari supplier');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_receipts', function (Blueprint $table) {
            $table->dropColumn('surat_jalan_no');
        });
    }
};
