<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sewing_pickup_lines', function (Blueprint $table) {
            if (! Schema::hasColumn('sewing_pickup_lines', 'qty_progress_adjusted')) {
                $table->decimal('qty_progress_adjusted', 12, 4)->default(0)->after('qty_direct_picked');
            }
        });
    }

    public function down(): void
    {
        // additive — tidak drop
    }
};
