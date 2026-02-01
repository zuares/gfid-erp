<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            if (!Schema::hasColumn('shipments', 'awb')) {
                $table->string('awb', 80)->nullable()->after('notes');
                $table->index('awb', 'shipments_awb_idx');
            }
        });
    }

    public function down(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            if (Schema::hasColumn('shipments', 'awb')) {
                $table->dropIndex('shipments_awb_idx');
                $table->dropColumn('awb');
            }
        });
    }
};
