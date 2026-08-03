<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('marketplace_promotions', function (Blueprint $table) {
            $table->json('detail_cache_json')->nullable()->after('delete_response');
            $table->timestamp('detail_cached_at')->nullable()->after('detail_cache_json');
        });
    }

    public function down(): void
    {
        Schema::table('marketplace_promotions', function (Blueprint $table) {
            $table->dropColumn(['detail_cache_json', 'detail_cached_at']);
        });
    }
};
