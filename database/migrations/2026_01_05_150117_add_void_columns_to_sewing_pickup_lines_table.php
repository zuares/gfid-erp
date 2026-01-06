<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sewing_pickup_lines', function (Blueprint $table) {
            $table->string('void_reason', 150)->nullable()->after('status');
            $table->timestamp('voided_at')->nullable()->after('void_reason');
            $table->unsignedBigInteger('voided_by')->nullable()->after('voided_at');

            $table->index(['sewing_pickup_id', 'status']);
            $table->index(['voided_by']);
        });
    }

    public function down(): void
    {
        Schema::table('sewing_pickup_lines', function (Blueprint $table) {
            $table->dropIndex(['sewing_pickup_id', 'status']);
            $table->dropIndex(['voided_by']);

            $table->dropColumn(['void_reason', 'voided_at', 'voided_by']);
        });
    }
};
