<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sewing_pickup_lines', function (Blueprint $table) {
            $table->decimal('qty_progress_adjusted', 14, 4)
                ->default(0)
                ->after('qty_direct_picked');
        });
    }

    public function down(): void
    {
        Schema::table('sewing_pickup_lines', function (Blueprint $table) {
            $table->dropColumn('qty_progress_adjusted');
        });
    }
};
