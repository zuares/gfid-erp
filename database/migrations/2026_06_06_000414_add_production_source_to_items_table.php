<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('items', function (Blueprint $table) {
            if (!Schema::hasColumn('items', 'production_source')) {
                $table->string('production_source', 24)
                    ->nullable()
                    ->after('item_role_id')
                    ->comment('in_house | outsource | buy');
                $table->index(['production_source']);
            }
        });

        DB::table('items')
            ->where('type', 'finished_good')
            ->whereNull('production_source')
            ->update([
                'production_source' => 'buy',
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            if (Schema::hasColumn('items', 'production_source')) {
                $table->dropIndex(['production_source']);
                $table->dropColumn('production_source');
            }
        });
    }
};
