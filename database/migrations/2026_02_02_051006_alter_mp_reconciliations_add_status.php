
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mp_reconciliations', function (Blueprint $table) {
            $table->string('status', 20)->default('needs_review')->after('shipment_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::table('mp_reconciliations', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropColumn('status');
        });
    }
};
