<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('item_categories', function (Blueprint $table) {
            if (!Schema::hasColumn('item_categories', 'kind')) {
                $table->string('kind', 24)
                    ->default('product')
                    ->after('name')
                    ->comment('product | material | support | accessory | packaging | other');
                $table->index(['kind', 'active']);
            }
        });

        $groups = [
            'material' => ['MAT'],
            'support' => ['BPU'],
            'accessory' => ['ACC'],
            'packaging' => ['PACK'],
            'product' => ['FG', 'TJR', 'LBP', 'HDY', 'LJR', 'LCG', 'SJR', 'CRG', 'SHT', 'TSH'],
        ];

        foreach ($groups as $kind => $codes) {
            DB::table('item_categories')
                ->whereIn('code', $codes)
                ->update([
                    'kind' => $kind,
                    'updated_at' => now(),
                ]);
        }
    }

    public function down(): void
    {
        Schema::table('item_categories', function (Blueprint $table) {
            if (Schema::hasColumn('item_categories', 'kind')) {
                $table->dropIndex(['kind', 'active']);
                $table->dropColumn('kind');
            }
        });
    }
};
