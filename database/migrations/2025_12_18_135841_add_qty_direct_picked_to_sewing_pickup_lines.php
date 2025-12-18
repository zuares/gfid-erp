<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sewing_pickup_lines', function (Blueprint $table) {
            $table
                ->decimal('qty_direct_picked', 12, 2)
                ->default(0)
                ->after('qty_returned_reject')
                ->comment('Qty yang terpakai melalui Direct Pickup (WIP-SEW → RTS / WIP-FIN)');
        });
    }

    public function down(): void
    {
        Schema::table('sewing_pickup_lines', function (Blueprint $table) {
            $table->dropColumn('qty_direct_picked');
        });
    }
};
