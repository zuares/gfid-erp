<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rts_direct_receives', function (Blueprint $table) {
            $table->id();

            $table->date('date');
            $table->string('code', 32)->unique();

            $table->unsignedBigInteger('from_warehouse_id');
            $table->unsignedBigInteger('to_warehouse_id');

            $table->unsignedBigInteger('operator_id')->nullable(); // penjahit / finisher (opsional)
            $table->text('notes')->nullable();

            $table->unsignedBigInteger('created_by_user_id')->nullable();

            $table->timestamps();

            $table->index(['date']);
            $table->index(['from_warehouse_id']);
            $table->index(['to_warehouse_id']);
            $table->index(['operator_id']);

            $table->foreign('from_warehouse_id')->references('id')->on('warehouses');
            $table->foreign('to_warehouse_id')->references('id')->on('warehouses');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rts_direct_receives');
    }
};
