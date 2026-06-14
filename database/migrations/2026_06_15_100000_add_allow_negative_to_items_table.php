<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->boolean('allow_negative')
                ->default(false)
                ->after('is_stocked')
                ->comment('Jika true, stockOut boleh membuat qty negatif (untuk bahan baku/pendukung produksi)');
        });
    }

    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->dropColumn('allow_negative');
        });
    }
};
