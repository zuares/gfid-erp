<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_opnames', function (Blueprint $table) {
            $table->timestamp('cancelled_at')->nullable()->after('finalized_at');
            $table->unsignedBigInteger('cancelled_by')->nullable()->after('cancelled_at');
            $table->string('cancel_reason', 255)->nullable()->after('cancelled_by');

            $table->index(['cancelled_at']);
        });
    }

    public function down(): void
    {
        Schema::table('stock_opnames', function (Blueprint $table) {
            $table->dropIndex(['cancelled_at']);
            $table->dropColumn(['cancelled_at', 'cancelled_by', 'cancel_reason']);
        });
    }
};
