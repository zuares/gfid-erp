<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('item_categories')->updateOrInsert(
            ['code' => 'ATK'],
            [
                'code' => 'ATK',
                'name' => 'ATK & Operasional',
                'kind' => 'operational',
                'active' => 1,
                'updated_at' => now(),
            ],
        );
    }

    public function down(): void
    {
        DB::table('item_categories')
            ->where('code', 'ATK')
            ->where('kind', 'operational')
            ->delete();
    }
};
