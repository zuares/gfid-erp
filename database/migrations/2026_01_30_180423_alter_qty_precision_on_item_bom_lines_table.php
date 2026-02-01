<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('item_bom_lines', function (Blueprint $table) {
            $table->decimal('qty', 12, 4)->change(); // ✅ simpan sampai 0.009
            $table->decimal('scrap_pct', 6, 3)->default(0)->change(); // optional
        });
    }

    public function down(): void
    {
        Schema::table('item_bom_lines', function (Blueprint $table) {
            $table->decimal('qty', 12, 2)->change();
            $table->decimal('scrap_pct', 6, 2)->default(0)->change();
        });
    }
};
