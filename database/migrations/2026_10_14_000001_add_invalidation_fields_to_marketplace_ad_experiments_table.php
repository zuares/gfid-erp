<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('marketplace_ad_experiments', function (Blueprint $table) {
            $table->timestamp('invalidated_at')->nullable()->after('completed_at');
            $table->unsignedBigInteger('invalidated_by')->nullable()->after('invalidated_at')->index();
            $table->string('invalidation_reason', 500)->nullable()->after('invalidated_by');
        });
    }

    public function down(): void
    {
        Schema::table('marketplace_ad_experiments', function (Blueprint $table) {
            $table->dropIndex(['invalidated_by']);
            $table->dropColumn(['invalidated_at', 'invalidated_by', 'invalidation_reason']);
        });
    }
};
