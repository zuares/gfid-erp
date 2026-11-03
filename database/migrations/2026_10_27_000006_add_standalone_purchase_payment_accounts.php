<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        DB::table('standalone_accounting_accounts')->updateOrInsert(
            ['code' => '1-180'],
            [
                'name' => 'Prepaid Purchases / Uang Muka Pembelian',
                'description' => 'Uang yang sudah dibayarkan kepada supplier sebelum barang diterima atau sebelum hutang supplier terbentuk; saldo normalnya debit.',
                'type' => 'asset',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        );

        Schema::create('standalone_accounting_account_mappings', function (Blueprint $table): void {
            $table->id();
            $table->string('legacy_account_code', 30)->unique();
            $table->string('standalone_account_code', 30)->index();
            $table->string('purpose', 100);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        foreach ([
            ['legacy_account_code' => '1101', 'standalone_account_code' => '1-120', 'purpose' => 'Kas tunai'],
            ['legacy_account_code' => '1111', 'standalone_account_code' => '1-110', 'purpose' => 'Bank Jago'],
            ['legacy_account_code' => '1112', 'standalone_account_code' => '1-110', 'purpose' => 'Bank BCA'],
            ['legacy_account_code' => '1113', 'standalone_account_code' => '1-110', 'purpose' => 'Seabank'],
            ['legacy_account_code' => '1114', 'standalone_account_code' => '1-110', 'purpose' => 'Aladin Bank'],
            ['legacy_account_code' => '1151', 'standalone_account_code' => '1-180', 'purpose' => 'Uang muka pembelian'],
            ['legacy_account_code' => '2101', 'standalone_account_code' => '2-110', 'purpose' => 'Hutang supplier'],
        ] as $mapping) {
            DB::table('standalone_accounting_account_mappings')->insert([
                ...$mapping,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        $accountId = DB::table('standalone_accounting_accounts')
            ->where('code', '1-180')
            ->value('id');

        if ($accountId && DB::table('standalone_accounting_journal_lines')->where('account_id', $accountId)->exists()) {
            throw new RuntimeException('Akun 1-180 sudah dipakai jurnal dan tidak boleh dihapus saat rollback.');
        }

        Schema::dropIfExists('standalone_accounting_account_mappings');
        DB::table('standalone_accounting_accounts')->where('code', '1-180')->delete();
    }
};
