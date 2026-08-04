<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_messages', function (Blueprint $table) {
            $table->id();
            $table->string('direction', 20)->default('outbound');
            $table->string('provider', 40)->default('fonnte');
            $table->string('recipient_phone', 30);
            $table->string('recipient_name')->nullable();
            $table->text('message');
            $table->string('module', 60)->nullable();
            $table->string('reference_type', 160)->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->string('reference_label', 120)->nullable();
            $table->string('template_key', 120)->nullable();
            $table->string('status', 20)->default('pending');
            $table->string('provider_message_id', 160)->nullable();
            $table->text('error_message')->nullable();
            $table->foreignId('sent_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index(['module', 'reference_id']);
            $table->index('recipient_phone');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_messages');
    }
};
