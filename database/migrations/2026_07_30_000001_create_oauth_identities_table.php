<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('oauth_identities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('provider', 50);
            $table->string('provider_user_id', 191);
            $table->string('email')->nullable()->index();
            $table->string('name')->nullable();
            $table->string('avatar_url', 2048)->nullable();
            $table->json('profile_json')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->timestamps();

            $table->unique(['provider', 'provider_user_id'], 'oauth_identities_provider_user_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('oauth_identities');
    }
};
