<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('storefront_visitors', function (Blueprint $table) {
            // Flag for internal/office traffic (e.g. staff using office WiFi)
            $table->boolean('is_internal')->default(false)->after('last_seen_at');
            // Optional note for why it's marked internal
            $table->string('internal_reason', 100)->nullable()->after('is_internal');
        });
    }

    public function down(): void
    {
        Schema::table('storefront_visitors', function (Blueprint $table) {
            $table->dropColumn(['is_internal', 'internal_reason']);
        });
    }
};
