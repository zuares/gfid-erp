<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('sewing_pickups') || ! Schema::hasColumn('sewing_pickups', 'operator_id')) {
            return;
        }

        Schema::table('sewing_pickups', function (Blueprint $table): void {
            // Reject-only bukan pekerjaan operator jahit. Operator tetap wajib
            // untuk qty baik, tetapi boleh NULL untuk dokumen reject susulan.
            $table->foreignId('operator_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('sewing_pickups') || ! Schema::hasColumn('sewing_pickups', 'operator_id')) {
            return;
        }

        Schema::table('sewing_pickups', function (Blueprint $table): void {
            $table->foreignId('operator_id')->nullable(false)->change();
        });
    }
};
