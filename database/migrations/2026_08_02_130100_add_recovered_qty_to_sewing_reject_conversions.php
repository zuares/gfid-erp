<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sewing_reject_conversions', function (Blueprint $table) {
            if (!Schema::hasColumn('sewing_reject_conversions', 'recovered_qty')) {
                $table->decimal('recovered_qty', 12, 3)
                    ->default(0)
                    ->after('qty');
            }
        });
    }

    public function down(): void
    {
        Schema::table('sewing_reject_conversions', function (Blueprint $table) {
            if (Schema::hasColumn('sewing_reject_conversions', 'recovered_qty')) {
                $table->dropColumn('recovered_qty');
            }
        });
    }
};
