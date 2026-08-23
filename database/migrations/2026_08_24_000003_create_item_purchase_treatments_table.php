<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('item_purchase_treatments', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name', 120);
            $table->string('allocation', 20)->index();
            $table->foreignId('default_expense_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->boolean('active')->default(true)->index();
            $table->boolean('is_system')->default(false)->index();
            $table->timestamps();
        });

        $now = now();
        DB::table('item_purchase_treatments')->insert([
            ['code' => 'hpp', 'name' => 'Persediaan / HPP', 'allocation' => 'hpp', 'active' => 1, 'is_system' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'expense', 'name' => 'Biaya langsung', 'allocation' => 'expense', 'active' => 1, 'is_system' => 1, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('item_purchase_treatments');
    }
};
