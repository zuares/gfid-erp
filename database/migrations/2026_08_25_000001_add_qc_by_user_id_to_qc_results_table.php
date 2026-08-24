<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('qc_results', function (Blueprint $table) {
            $table->foreignId('qc_by_user_id')
                ->nullable()
                ->after('operator_id')
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('qc_results', function (Blueprint $table) {
            $table->dropConstrainedForeignId('qc_by_user_id');
        });
    }
};
