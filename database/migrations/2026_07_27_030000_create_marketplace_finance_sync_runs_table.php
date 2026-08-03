<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketplace_finance_sync_runs', function (Blueprint $table) {
            $table->id();
            $table->string('trigger', 30)->default('manual'); // manual | schedule
            $table->string('status', 30)->default('processing'); // processing | success | error
            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
            $table->index(['status', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketplace_finance_sync_runs');
    }
};
