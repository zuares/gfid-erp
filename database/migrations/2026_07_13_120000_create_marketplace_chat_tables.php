<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketplace_conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->string('conversation_id');            // ID percakapan dari Shopee (int64 → string)
            $table->string('buyer_user_id')->nullable();  // to_id / buyer user id di Shopee
            $table->string('buyer_username')->nullable();
            $table->string('buyer_avatar')->nullable();
            $table->string('last_message_type')->nullable();
            $table->text('last_message_text')->nullable();
            $table->timestamp('last_message_at')->nullable();
            $table->unsignedInteger('unread_count')->default(0);
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(['store_id', 'conversation_id']);
            $table->index(['store_id', 'buyer_user_id']);
            $table->index('last_message_at');
        });

        Schema::create('marketplace_chat_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('marketplace_conversation_id')->constrained('marketplace_conversations')->cascadeOnDelete();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->string('external_message_id');        // message_id dari Shopee
            $table->string('from_role', 10)->default('buyer'); // buyer | seller
            $table->string('from_id')->nullable();
            $table->string('message_type')->default('text');   // text | image | sticker | item | order | ...
            $table->text('text')->nullable();
            $table->json('content')->nullable();          // payload mentah content
            $table->timestamp('sent_at')->nullable();
            $table->boolean('is_read')->default(false);
            $table->timestamps();

            $table->unique(['store_id', 'external_message_id']);
            $table->index(['marketplace_conversation_id', 'sent_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketplace_chat_messages');
        Schema::dropIfExists('marketplace_conversations');
    }
};
