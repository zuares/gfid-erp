<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_category_mappings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_category_id')->unique()->constrained('item_categories')->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained('suppliers')->cascadeOnDelete();
            $table->unsignedInteger('lead_time_days')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->index(['supplier_id', 'active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_category_mappings');
    }
};
