<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_payments', function (Blueprint $table) {
            if (!Schema::hasColumn('purchase_payments', 'journal_id')) {
                $table->foreignId('journal_id')
                    ->nullable()
                    ->constrained('journals')
                    ->nullOnDelete()
                    ->after('id'); // kalau kolom kamu beda, bisa diganti after('...') atau hapus after()
            }
        });
    }

    public function down(): void
    {
        Schema::table('purchase_payments', function (Blueprint $table) {
            if (Schema::hasColumn('purchase_payments', 'journal_id')) {
                $table->dropConstrainedForeignId('journal_id');
            }
        });
    }
};
