<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $exists = DB::table('accounts')->where('code', '2102')->exists();
        if (!$exists) {
            DB::table('accounts')->insert([
                'code'       => '2102',
                'name'       => 'Hutang Upah Borongan',
                'type'       => 'liability',
                'is_active'  => 1,
                'is_cash'    => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('accounts')->where('code', '2102')->delete();
    }
};
