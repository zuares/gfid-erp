<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('shipment_returns', function (Blueprint $table) {
            $table->id();

            $table->string('code')->unique();

            $table->foreignId('store_id')
                ->constrained()
                ->cascadeOnUpdate();

            // Shipment asal (opsional)
            $table->foreignId('shipment_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete()
                ->cascadeOnUpdate();

            $table->date('date');

            // draft | submitted | posted
            $table->string('status')->default('draft');

            $table->text('reason')->nullable();
            $table->text('notes')->nullable();

            $table->integer('total_qty')->default(0);

            // Audit submit/post
            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('submitted_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            $table->timestamp('posted_at')->nullable();
            $table->foreignId('posted_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            // Created by
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            $table->timestamps();

            // Index tambahan kalau perlu filter cepat
            $table->index(['date', 'status']);
            $table->index('store_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipment_returns');
    }
};
