<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('items', 'sku')) {
            return;
        }

        Schema::table('items', function (Blueprint $table) {
            $table->string('sku', 100)->nullable()->after('code');
            $table->index('sku');
        });
    }

    public function down(): void
    {
        if (!Schema::hasColumn('items', 'sku')) {
            return;
        }

        Schema::table('items', function (Blueprint $table) {
            $table->dropIndex(['sku']);
            $table->dropColumn('sku');
        });
    }
};
