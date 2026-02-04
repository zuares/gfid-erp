<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('production_issues', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->date('date');
            $table->foreignId('production_order_id')->constrained('production_orders')->cascadeOnDelete();

            $table->foreignId('from_warehouse_id')->constrained('warehouses');
            $table->foreignId('to_warehouse_id')->constrained('warehouses');

            $table->string('status')->default('draft'); // draft|posted|void
            $table->dateTime('posted_at')->nullable();
            $table->unsignedBigInteger('posted_by')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['production_order_id', 'date']);
            $table->index(['status']);
        });

        Schema::create('production_issue_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_issue_id')->constrained('production_issues')->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('items'); // RM item
            $table->decimal('qty', 14, 2);

            $table->unsignedBigInteger('lot_id')->nullable();
            $table->decimal('unit_cost', 14, 4)->nullable(); // snapshot optional

            $table->timestamps();

            $table->index(['production_issue_id', 'item_id']);
            $table->index(['lot_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_issue_lines');
        Schema::dropIfExists('production_issues');
    }
};
