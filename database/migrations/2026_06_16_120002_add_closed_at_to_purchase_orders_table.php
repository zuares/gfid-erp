<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            // Gunakan closed_at (additive) agar tidak mengubah status lama
            $table->timestamp('closed_at')->nullable()->after('cancelled_at');
            $table->unsignedBigInteger('closed_by')->nullable()->after('closed_at');

            $table->index('closed_at');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropIndex(['closed_at']);
            $table->dropColumn(['closed_at', 'closed_by']);
        });
    }
};
