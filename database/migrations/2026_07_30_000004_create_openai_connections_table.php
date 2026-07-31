<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('openai_connections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('label', 120)->nullable();
            $table->text('api_key');
            $table->string('organization_id', 120)->nullable();
            $table->string('project_id', 120)->nullable();
            $table->string('model', 120)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_verified_at')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();

            $table->unique('user_id', 'openai_connections_user_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('openai_connections');
    }
};
