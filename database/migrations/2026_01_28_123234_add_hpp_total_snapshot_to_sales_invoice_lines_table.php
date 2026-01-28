<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_invoice_lines', function (Blueprint $table) {
            // simpan total HPP snapshot (unit * qty)
            $table->decimal('hpp_total_snapshot', 18, 2)->default(0)->after('hpp_unit_snapshot');
        });
    }

    public function down(): void
    {
        Schema::table('sales_invoice_lines', function (Blueprint $table) {
            $table->dropColumn('hpp_total_snapshot');
        });
    }
};
