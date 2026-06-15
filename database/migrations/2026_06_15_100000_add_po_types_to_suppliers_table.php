<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            // null = bisa untuk semua jenis PO; otherwise JSON array: ["material"], ["finished_good"], ["material","finished_good"]
            $table->json('po_types')->nullable()->after('type');
        });
    }

    public function down(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropColumn('po_types');
        });
    }
};
