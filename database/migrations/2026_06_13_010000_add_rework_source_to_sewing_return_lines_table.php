<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sewing_return_lines', function (Blueprint $table) {
            $table->string('source_type')->nullable()->after('notes')->index();
            $table->foreignId('source_reject_return_line_id')
                ->nullable()
                ->after('source_type')
                ->constrained('sewing_return_lines')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('sewing_return_lines', function (Blueprint $table) {
            $table->dropConstrainedForeignId('source_reject_return_line_id');
            $table->dropColumn('source_type');
        });
    }
};
