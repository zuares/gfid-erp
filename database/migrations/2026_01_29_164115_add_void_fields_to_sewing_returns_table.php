<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sewing_returns', function (Blueprint $table) {

            // waktu di-void
            if (!Schema::hasColumn('sewing_returns', 'voided_at')) {
                $table->timestamp('voided_at')
                    ->nullable()
                    ->after('status');
            }

            // siapa yang void
            if (!Schema::hasColumn('sewing_returns', 'voided_by_user_id')) {
                $table->unsignedBigInteger('voided_by_user_id')
                    ->nullable()
                    ->after('voided_at');
            }

            // alasan void
            if (!Schema::hasColumn('sewing_returns', 'void_reason')) {
                $table->string('void_reason', 255)
                    ->nullable()
                    ->after('voided_by_user_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('sewing_returns', function (Blueprint $table) {

            if (Schema::hasColumn('sewing_returns', 'void_reason')) {
                $table->dropColumn('void_reason');
            }

            if (Schema::hasColumn('sewing_returns', 'voided_by_user_id')) {
                $table->dropColumn('voided_by_user_id');
            }

            if (Schema::hasColumn('sewing_returns', 'voided_at')) {
                $table->dropColumn('voided_at');
            }
        });
    }
};
