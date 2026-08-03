<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketplace_financial_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->string('action', 30); // posted | voided | closed | reopened
            $table->string('scope_key', 180)->index();
            $table->foreignId('store_id')->nullable()->constrained('stores')->nullOnDelete();
            $table->string('date_basis', 30);
            $table->date('date_from');
            $table->date('date_to');
            $table->foreignId('posting_id')->nullable()->constrained('marketplace_accounting_postings')->nullOnDelete();
            $table->foreignId('closing_id')->nullable()->constrained('marketplace_financial_closings')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->json('before_snapshot')->nullable();
            $table->json('after_snapshot')->nullable();
            $table->text('reason')->nullable();
            $table->timestamps();

            $table->index(['action', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketplace_financial_audit_logs');
    }
};
