<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $timestamp = now();

        $upsertCategory = static function (array $category) use ($timestamp): void {
            $query = DB::table('item_categories')->where('code', $category['code']);

            if ($query->exists()) {
                $query->update([
                    'name' => $category['name'],
                    'active' => $category['active'],
                    'kind' => $category['kind'],
                    'updated_at' => $timestamp,
                ]);

                return;
            }

            DB::table('item_categories')->insert($category + [
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ]);
        };

        $upsertCategory([
            'code' => 'MNT',
            'name' => 'Maintenance Mesin',
            'active' => 1,
            'kind' => 'operational',
        ]);

        $account = DB::table('accounts')->where('code', '6105');
        $accountData = [
            'name' => 'Biaya Pemeliharaan Mesin',
            'type' => 'expense',
            'is_cash' => 0,
            'is_active' => 1,
        ];

        if ($account->exists()) {
            $account->update($accountData + ['updated_at' => $timestamp]);
        } else {
            DB::table('accounts')->insert($accountData + [
                'code' => '6105',
                'updated_at' => $timestamp,
                'created_at' => $timestamp,
            ]);
        }
    }

    public function down(): void
    {
        DB::table('item_categories')->where('code', 'MNT')->delete();
        DB::table('accounts')->where('code', '6105')->delete();
    }
};
