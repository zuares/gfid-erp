<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('marketplace_tiktok_financial_transactions', function (Blueprint $table): void {
            $table->index(
                ['store_id', 'source', 'transaction_created_at'],
                'tiktok_financial_store_source_created_index'
            );
        });
    }

    public function down(): void
    {
        Schema::table('marketplace_tiktok_financial_transactions', function (Blueprint $table): void {
            $table->dropIndex('tiktok_financial_store_source_created_index');
        });
    }
};
