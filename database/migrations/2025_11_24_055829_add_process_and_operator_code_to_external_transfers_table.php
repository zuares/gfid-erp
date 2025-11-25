<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('external_transfers', function (Blueprint $table) {
            // setelah kolom date
            $table->string('process', 50)
                ->nullable()
                ->after('date');

            // kode operator / vendor (misal MRF, ANDI, dll)
            $table->string('operator_code', 50)
                ->nullable()
                ->after('process');
        });
    }

    public function down(): void
    {
        Schema::table('external_transfers', function (Blueprint $table) {
            $table->dropColumn('process');
            $table->dropColumn('operator_code');
        });
    }
};
