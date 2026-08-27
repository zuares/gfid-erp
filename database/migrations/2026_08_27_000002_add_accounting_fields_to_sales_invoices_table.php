<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_invoices', function (Blueprint $table) {
            if (! Schema::hasColumn('sales_invoices', 'posted_at')) {
                $table->timestamp('posted_at')->nullable()->index();
            }

            if (! Schema::hasColumn('sales_invoices', 'journal_id')) {
                $table->foreignId('journal_id')
                    ->nullable()
                    ->constrained('journals')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('sales_invoices', function (Blueprint $table) {
            if (Schema::hasColumn('sales_invoices', 'journal_id')) {
                $table->dropConstrainedForeignId('journal_id');
            }

            if (Schema::hasColumn('sales_invoices', 'posted_at')) {
                $table->dropColumn('posted_at');
            }
        });
    }
};
