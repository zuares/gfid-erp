<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('shipments', 'scan_mode')) {
            Schema::table('shipments', function (Blueprint $table) {
                $table->string('scan_mode', 20)
                    ->default('item_first')
                    ->after('shipment_type')
                    ->index();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('shipments', 'scan_mode')) {
            Schema::table('shipments', function (Blueprint $table) {
                $table->dropIndex(['scan_mode']);
                $table->dropColumn('scan_mode');
            });
        }
    }
};
