<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        DB::table('accounts')->updateOrInsert(
            ['code' => '2103'],
            ['name' => 'Utang Pinjaman', 'type' => 'liability', 'is_cash' => false, 'is_active' => true, 'updated_at' => $now, 'created_at' => $now]
        );
        DB::table('accounts')->updateOrInsert(
            ['code' => '6208'],
            ['name' => 'Beban Bunga Pinjaman', 'type' => 'expense', 'is_cash' => false, 'is_active' => true, 'updated_at' => $now, 'created_at' => $now]
        );
    }

    public function down(): void
    {
        DB::table('accounts')->whereIn('code', ['2103', '6208'])->delete();
    }
};
