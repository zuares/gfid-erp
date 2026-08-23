<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->foreignId('item_type_option_id')->nullable()->after('type')->constrained('item_type_options')->nullOnDelete();
            $table->foreignId('purchase_treatment_id')->nullable()->after('default_allocation')->constrained('item_purchase_treatments')->nullOnDelete();
        });

        foreach (DB::table('item_type_options')->pluck('id', 'base_type') as $baseType => $id) {
            DB::table('items')->where('type', $baseType)->update(['item_type_option_id' => $id]);
        }

        foreach (DB::table('item_purchase_treatments')->pluck('id', 'allocation') as $allocation => $id) {
            DB::table('items')->where('default_allocation', $allocation)->update(['purchase_treatment_id' => $id]);
        }
    }

    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->dropForeign(['item_type_option_id']);
            $table->dropForeign(['purchase_treatment_id']);
            $table->dropColumn(['item_type_option_id', 'purchase_treatment_id']);
        });
    }
};
