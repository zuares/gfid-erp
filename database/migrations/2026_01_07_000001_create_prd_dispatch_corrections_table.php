<?php

// database/migrations/2026_01_07_000001_create_prd_dispatch_corrections_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prd_dispatch_corrections', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('stock_request_id');
            $table->date('date');
            $table->text('notes')->nullable();

            $table->unsignedBigInteger('created_by_user_id')->nullable();
            $table->timestamps();

            $table->foreign('stock_request_id')->references('id')->on('stock_requests');
            $table->foreign('created_by_user_id')->references('id')->on('users');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prd_dispatch_corrections');
    }
};
