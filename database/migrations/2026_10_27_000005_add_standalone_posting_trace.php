<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('standalone_accounting_journals', function (Blueprint $table): void {
            $table->string('source_type', 80)->nullable()->after('reference');
            $table->unsignedBigInteger('source_id')->nullable()->after('source_type');
            $table->index(['source_type', 'source_id']);
            $table->unique(['source_type', 'source_id'], 'standalone_journals_source_unique');
        });

        Schema::table('purchase_payments', function (Blueprint $table): void {
            $table->foreignId('standalone_journal_id')
                ->nullable()
                ->after('journal_id')
                ->constrained('standalone_accounting_journals')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('purchase_payments', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('standalone_journal_id');
        });

        Schema::table('standalone_accounting_journals', function (Blueprint $table): void {
            $table->dropUnique('standalone_journals_source_unique');
            $table->dropIndex('standalone_accounting_journals_source_type_source_id_index');
            $table->dropColumn(['source_type', 'source_id']);
        });
    }
};
