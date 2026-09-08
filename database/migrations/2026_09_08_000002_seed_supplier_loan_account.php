<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        DB::table('accounts')->updateOrInsert(
            ['code' => '1306'],
            [
                'name' => 'Piutang Pinjaman Supplier',
                'type' => 'asset',
                'is_cash' => false,
                'is_active' => true,
                'updated_at' => $now,
                'created_at' => $now,
            ]
        );
    }

    public function down(): void
    {
        DB::table('accounts')->where('code', '1306')->delete();
    }
};
