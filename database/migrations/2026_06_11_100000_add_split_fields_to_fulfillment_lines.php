<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_fulfillment_lines', function (Blueprint $table) {
            // Parent yang sudah di-split: disembunyikan dari UI tapi tetap ada untuk audit
            $table->boolean('is_split_parent')->default(false)->after('substituted');
            // Untuk split children: menunjuk ke parent line asli
            $table->foreignId('split_parent_id')->nullable()->after('is_split_parent')
                ->constrained('order_fulfillment_lines')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('order_fulfillment_lines', function (Blueprint $table) {
            $table->dropForeign(['split_parent_id']);
            $table->dropColumn(['is_split_parent', 'split_parent_id']);
        });
    }
};
