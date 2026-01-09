<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->timestamp('cancelled_at')->nullable()->after('posted_at');
            $table->unsignedBigInteger('cancelled_by')->nullable()->after('cancelled_at');
            $table->string('cancel_reason', 255)->nullable()->after('cancelled_by');

            $table->index(['cancelled_at']);
            $table->index(['cancelled_by']);
        });
    }

    public function down(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->dropIndex(['cancelled_at']);
            $table->dropIndex(['cancelled_by']);

            $table->dropColumn(['cancelled_at', 'cancelled_by', 'cancel_reason']);
        });
    }
};
