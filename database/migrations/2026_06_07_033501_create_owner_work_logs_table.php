<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('owner_work_logs', function (Blueprint $table) {
            $table->id();

            $table->date('work_date')->nullable();
            $table->string('title');
            $table->string('category')->nullable();
            $table->string('status')->default('plan');
            $table->string('priority')->default('medium');
            $table->string('page_url')->nullable();

            $table->longText('description')->nullable();
            $table->longText('technical_notes')->nullable();
            $table->longText('testing_notes')->nullable();
            $table->longText('rollback_notes')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->index(['work_date', 'status']);
            $table->index(['category', 'priority']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('owner_work_logs');
    }
};
