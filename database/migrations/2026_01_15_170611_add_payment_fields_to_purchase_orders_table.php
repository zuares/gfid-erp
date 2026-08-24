<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            // status pembayaran di luar status dokumen PO
            $table->string('payment_status', 20)->default('unpaid')->after('status'); // unpaid|partial|paid|overpaid
            $table->decimal('paid_amount', 18, 2)->default(0)->after('payment_status');

            // tempo
            $table->unsignedInteger('payment_terms_days')->nullable()->after('paid_amount');
            $table->date('due_date')->nullable()->after('payment_terms_days');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropColumn([
                'payment_status',
                'paid_amount',
                'payment_terms_days',
                'due_date',
            ]);
        });
    }
};
