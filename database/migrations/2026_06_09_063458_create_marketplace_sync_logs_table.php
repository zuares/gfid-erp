<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('marketplace_sync_logs')) {
            Schema::create('marketplace_sync_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('store_id')->nullable()->constrained()->nullOnDelete();
                $table->string('action');
                $table->string('status')->default('success');
                $table->text('message')->nullable();
                $table->json('payload')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('marketplace_sync_logs');
    }
};
