<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ketertelusuran jurnal ke sumbernya.
 *
 * journals sebelumnya hanya punya source_type + source_id + description.
 * Kolom berikut ditambahkan agar tiap jurnal (termasuk WIP Normalization nanti)
 * bisa ditelusuri balik: nomor dokumen sumber, catatan, dan siapa yang membuat /
 * menyetujui. Semua nullable → aditif, tidak mengganggu data lama.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('journals', function (Blueprint $table) {
            if (! Schema::hasColumn('journals', 'reference_no')) {
                $table->string('reference_no')->nullable()->after('source_id');
            }
            if (! Schema::hasColumn('journals', 'notes')) {
                $table->text('notes')->nullable()->after('reference_no');
            }
            if (! Schema::hasColumn('journals', 'created_by')) {
                $table->foreignId('created_by')->nullable()->after('notes')
                    ->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('journals', 'approved_by')) {
                $table->foreignId('approved_by')->nullable()->after('created_by')
                    ->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('journals', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('approved_by');
            }
        });
    }

    public function down(): void
    {
        Schema::table('journals', function (Blueprint $table) {
            foreach (['approved_at', 'approved_by', 'created_by', 'notes', 'reference_no'] as $col) {
                if (Schema::hasColumn('journals', $col)) {
                    // dropConstrainedForeignId aman untuk kolom FK; string/text pakai dropColumn
                    if (in_array($col, ['created_by', 'approved_by'], true)) {
                        $table->dropConstrainedForeignId($col);
                    } else {
                        $table->dropColumn($col);
                    }
                }
            }
        });
    }
};
