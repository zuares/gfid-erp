<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shipment_returns', function (Blueprint $table) {
            if (!Schema::hasColumn('shipment_returns', 'cancelled_at')) {
                $table->timestamp('cancelled_at')->nullable()->after('posted_by');
            }

            if (!Schema::hasColumn('shipment_returns', 'cancelled_by')) {
                $table->foreignId('cancelled_by')
                    ->nullable()
                    ->after('cancelled_at')
                    ->constrained('users')
                    ->nullOnDelete()
                    ->cascadeOnUpdate();
            }
        });
    }

    public function down(): void
    {
        Schema::table('shipment_returns', function (Blueprint $table) {
            if (Schema::hasColumn('shipment_returns', 'cancelled_by')) {
                $table->dropForeign(['cancelled_by']);
                $table->dropColumn('cancelled_by');
            }

            if (Schema::hasColumn('shipment_returns', 'cancelled_at')) {
                $table->dropColumn('cancelled_at');
            }
        });
    }
};
