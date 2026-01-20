<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_receipts', function (Blueprint $table) {
            if (!Schema::hasColumn('purchase_receipts', 'posted_at')) {
                $table->dateTime('posted_at')->nullable()->after('status');
            }
            if (!Schema::hasColumn('purchase_receipts', 'journal_id')) {
                $table->foreignId('journal_id')->nullable()->constrained('journals')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('purchase_receipts', function (Blueprint $table) {
            if (Schema::hasColumn('purchase_receipts', 'journal_id')) {
                $table->dropConstrainedForeignId('journal_id');
            }
            if (Schema::hasColumn('purchase_receipts', 'posted_at')) {
                $table->dropColumn('posted_at');
            }
        });
    }
};
