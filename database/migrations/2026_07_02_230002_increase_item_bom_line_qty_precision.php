<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('item_bom_lines', function (Blueprint $table) {
            $table->decimal('qty', 14, 8)->change();
        });
    }

    public function down(): void
    {
        Schema::table('item_bom_lines', function (Blueprint $table) {
            $table->decimal('qty', 12, 4)->change();
        });
    }
};
