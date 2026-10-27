<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        foreach ([
            [
                'code' => '3-110',
                'name' => 'Stock Capital / Modal Disetor',
                'description' => 'Modal yang disetor pemilik atau investor ke perusahaan; saldo normalnya kredit.',
            ],
            [
                'code' => '3-130',
                'name' => 'Retained Earnings / Laba Ditahan',
                'description' => 'Akumulasi laba atau rugi periode sebelumnya yang belum dibagikan kepada pemilik.',
            ],
            [
                'code' => '3-150',
                'name' => 'Income Summary / Ikhtisar Laba Rugi',
                'description' => 'Akun sementara untuk menutup pendapatan dan beban pada akhir periode; harus kembali nol setelah closing.',
            ],
        ] as $account) {
            DB::table('standalone_accounting_accounts')->updateOrInsert(
                ['code' => $account['code']],
                [
                    'name' => $account['name'],
                    'description' => $account['description'],
                    'type' => 'equity',
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            );
        }
    }

    public function down(): void
    {
        DB::table('standalone_accounting_accounts')
            ->whereIn('code', ['3-110', '3-130', '3-150'])
            ->delete();
    }
};
