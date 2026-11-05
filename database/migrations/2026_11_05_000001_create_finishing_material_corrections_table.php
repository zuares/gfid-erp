<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finishing_material_corrections', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('finishing_item_id')->constrained('items')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('material_item_id')->constrained('items')->cascadeOnUpdate()->restrictOnDelete();
            $table->string('action', 20);
            $table->decimal('qty', 15, 3)->nullable();
            $table->string('unit', 40)->nullable();
            $table->text('reason');
            $table->foreignId('created_by')->constrained('users')->cascadeOnUpdate()->restrictOnDelete();
            $table->string('status', 20)->default('open');
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->timestamps();

            $table->index(['finishing_item_id', 'material_item_id']);
            $table->index(['action', 'status']);
            $table->index(['reference_type', 'reference_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finishing_material_corrections');
    }
};
