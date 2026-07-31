<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('marketplace_chat_messages', function (Blueprint $table) {
            $table->string('source', 32)->default('sync_api')->after('store_id');
            $table->string('external_conversation_id')->nullable()->after('source');
            $table->json('raw_payload')->nullable()->after('content');
            $table->json('raw_context')->nullable()->after('raw_payload');
            $table->foreignId('webhook_log_id')
                ->nullable()
                ->after('raw_context')
                ->constrained('webhook_logs')
                ->nullOnDelete();

            $table->index(['store_id', 'source']);
            $table->index(['store_id', 'external_conversation_id']);
            $table->index('webhook_log_id');
        });
    }

    public function down(): void
    {
        Schema::table('marketplace_chat_messages', function (Blueprint $table) {
            $table->dropForeign(['webhook_log_id']);
            $table->dropIndex(['store_id', 'source']);
            $table->dropIndex(['store_id', 'external_conversation_id']);
            $table->dropIndex(['webhook_log_id']);
            $table->dropColumn([
                'source',
                'external_conversation_id',
                'raw_payload',
                'raw_context',
                'webhook_log_id',
            ]);
        });
    }
};
