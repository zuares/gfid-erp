<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('packing_jobs', function (Blueprint $table) {
            $table->id();

            $table->string('code')->unique(); // PCK-YYYYMMDD-###

            $table->date('date');

            $table->string('status')->default('draft'); // draft / posted
            $table->timestamp('posted_at')->nullable();
            $table->timestamp('unposted_at')->nullable();

            // Optional info: channel penjualan & referensi
            $table->string('channel')->nullable(); // SHOPEE / TOKO / WEBSITE, dll
            $table->string('reference')->nullable(); // SO-xxx / DO-xxx / dsb

            $table->text('notes')->nullable();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('updated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('packing_jobs');
    }
};
