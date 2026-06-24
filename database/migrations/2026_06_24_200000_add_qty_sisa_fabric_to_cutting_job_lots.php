<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cutting_job_lots', function (Blueprint $table) {
            $table->decimal('qty_sisa_fabric', 12, 4)->default(0)->after('used_fabric_qty')
                ->comment('Sisa kain fisik yang dikembalikan ke RM setelah cutting');
            $table->timestamp('sisa_recorded_at')->nullable()->after('qty_sisa_fabric');
            $table->foreignId('sisa_recorded_by')->nullable()->constrained('users')->nullOnDelete()
                ->after('sisa_recorded_at');
        });
    }

    public function down(): void
    {
        Schema::table('cutting_job_lots', function (Blueprint $table) {
            $table->dropColumn(['qty_sisa_fabric', 'sisa_recorded_at', 'sisa_recorded_by']);
        });
    }
};
