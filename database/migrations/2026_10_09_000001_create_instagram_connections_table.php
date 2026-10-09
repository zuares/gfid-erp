<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('instagram_connections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('instagram_user_id');
            $table->string('username')->nullable();
            $table->text('access_token');
            $table->timestamp('token_expires_at')->nullable();
            $table->json('scopes')->nullable();
            $table->string('status')->default('active');
            $table->timestamp('last_connected_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'instagram_user_id']);
            $table->index(['user_id', 'status']);
            $table->index('token_expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('instagram_connections');
    }
};
