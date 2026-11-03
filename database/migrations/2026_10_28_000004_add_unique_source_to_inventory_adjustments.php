<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_adjustments', function (Blueprint $table): void {
            $table->unique(
                ['source_type', 'source_id'],
                'inventory_adjustments_source_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('inventory_adjustments', function (Blueprint $table): void {
            $table->dropUnique('inventory_adjustments_source_unique');
        });
    }
};
