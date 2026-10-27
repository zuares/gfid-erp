<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('standalone_accounting_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();
            $table->string('name', 150);
            $table->string('type', 20)->index();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('standalone_accounting_journals', function (Blueprint $table) {
            $table->id();
            $table->date('date')->index();
            $table->string('reference', 80)->nullable()->unique();
            $table->string('description', 255)->nullable();
            $table->timestamp('posted_at')->nullable()->index();
            $table->timestamp('voided_at')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('standalone_accounting_journal_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('journal_id')
                ->constrained('standalone_accounting_journals')
                ->cascadeOnDelete();
            $table->foreignId('account_id')
                ->constrained('standalone_accounting_accounts')
                ->restrictOnDelete();
            $table->decimal('debit', 20, 2)->default(0);
            $table->decimal('credit', 20, 2)->default(0);
            $table->timestamps();

            $table->index(['account_id', 'journal_id']);
        });

        DB::table('standalone_accounting_accounts')->insert([
            ['code' => '1000', 'name' => 'Kas', 'type' => 'asset', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['code' => '1100', 'name' => 'Bank', 'type' => 'asset', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['code' => '1200', 'name' => 'Piutang Usaha', 'type' => 'asset', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['code' => '1300', 'name' => 'Persediaan', 'type' => 'asset', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['code' => '2000', 'name' => 'Utang Usaha', 'type' => 'liability', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['code' => '3000', 'name' => 'Modal', 'type' => 'equity', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['code' => '4000', 'name' => 'Pendapatan', 'type' => 'revenue', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['code' => '5000', 'name' => 'Beban', 'type' => 'expense', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('standalone_accounting_journal_lines');
        Schema::dropIfExists('standalone_accounting_journals');
        Schema::dropIfExists('standalone_accounting_accounts');
    }
};
