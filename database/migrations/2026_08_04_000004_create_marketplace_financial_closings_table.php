<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketplace_financial_closings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->nullable()->constrained('stores')->nullOnDelete();
            $table->string('date_basis', 30);
            $table->date('date_from');
            $table->date('date_to');
            $table->string('scope_key', 180)->unique();
            $table->string('status', 20)->default('open'); // open | closed
            $table->json('snapshot');
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('closed_at')->nullable();
            $table->foreignId('reopened_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reopened_at')->nullable();
            $table->text('reopen_reason')->nullable();
            $table->timestamps();

            $table->index(['date_from', 'date_to']);
            $table->index(['status', 'store_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketplace_financial_closings');
    }
};
