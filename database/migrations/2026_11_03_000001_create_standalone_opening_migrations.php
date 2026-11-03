<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('standalone_opening_migrations', function (Blueprint $table): void {
            $table->unsignedTinyInteger('id')->primary();
            $table->date('as_of')->nullable();
            $table->date('source_from')->nullable();
            $table->uuid('request_token')->nullable()->unique();
            $table->json('snapshot')->nullable();
            $table->foreignId('journal_id')->nullable()->constrained('standalone_accounting_journals')->restrictOnDelete();
            $table->foreignId('posted_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('posted_at')->nullable();
            $table->timestamps();
        });

        // One ledger, one opening migration. The row also serializes posting/retries.
        DB::table('standalone_opening_migrations')->insert(['id' => 1, 'created_at' => now(), 'updated_at' => now()]);

        Schema::create('standalone_opening_migration_corrections', function (Blueprint $table): void {
            $table->id();
            $table->unsignedTinyInteger('opening_migration_id');
            $table->foreign('opening_migration_id', 'opening_correction_migration_fk')->references('id')->on('standalone_opening_migrations')->restrictOnDelete();
            $table->uuid('request_token')->unique();
            $table->foreignId('journal_id')->constrained('standalone_accounting_journals')->restrictOnDelete();
            $table->foreignId('posted_by')->constrained('users')->restrictOnDelete();
            $table->text('reason');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        if (DB::table('standalone_opening_migrations')->whereNotNull('posted_at')->exists()) {
            throw new RuntimeException('Opening Migrasi sudah diposting. Riwayat migrasi tidak boleh dihapus melalui rollback.');
        }

        Schema::dropIfExists('standalone_opening_migration_corrections');
        Schema::dropIfExists('standalone_opening_migrations');
    }
};
