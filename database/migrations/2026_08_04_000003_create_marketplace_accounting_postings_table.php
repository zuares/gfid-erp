<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketplace_accounting_postings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->nullable()->constrained('stores')->nullOnDelete();
            $table->string('date_basis', 30);
            $table->date('date_from');
            $table->date('date_to');
            $table->string('scope_key', 180)->unique();
            $table->string('status', 20)->default('draft'); // draft | posted | void
            $table->foreignId('journal_id')->nullable()->constrained('journals')->nullOnDelete();
            $table->unsignedInteger('order_count')->default(0);
            $table->decimal('gross_sales', 18, 2)->default(0);
            $table->decimal('payout', 18, 2)->default(0);
            $table->decimal('posted_amount', 18, 2)->default(0);
            $table->json('snapshot');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('posted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('posted_at')->nullable();
            $table->timestamp('voided_at')->nullable();
            $table->text('void_reason')->nullable();
            $table->timestamps();

            $table->index(['date_from', 'date_to']);
            $table->index(['status', 'store_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketplace_accounting_postings');
    }
};
