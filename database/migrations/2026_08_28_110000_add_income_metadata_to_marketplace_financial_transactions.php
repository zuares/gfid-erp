<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('marketplace_financial_transactions', function (Blueprint $table) {
            $table->string('income_source_hash', 64)->nullable()->after('income_status');
            $table->json('income_raw_payload')->nullable()->after('income_source_hash');
            $table->timestamp('income_synced_at')->nullable()->after('income_raw_payload');
            $table->index(['income_status', 'income_synced_at'], 'mp_financial_transactions_income_sync_idx');
        });
    }

    public function down(): void
    {
        Schema::table('marketplace_financial_transactions', function (Blueprint $table) {
            $table->dropIndex('mp_financial_transactions_income_sync_idx');
            $table->dropColumn(['income_source_hash', 'income_raw_payload', 'income_synced_at']);
        });
    }
};
