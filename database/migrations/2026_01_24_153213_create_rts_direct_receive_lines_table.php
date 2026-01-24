<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rts_direct_receive_lines', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('rts_direct_receive_id');
            $table->unsignedInteger('line_no')->default(1);

            $table->unsignedBigInteger('item_id');
            $table->decimal('qty', 14, 2)->default(0);
            $table->string('notes', 255)->nullable();

            $table->timestamps();

            // ✅ anti duplicate item per dokumen
            $table->unique(['rts_direct_receive_id', 'item_id']);

            $table->foreign('rts_direct_receive_id')
                ->references('id')->on('rts_direct_receives')
                ->onDelete('cascade');

            $table->foreign('item_id')->references('id')->on('items');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rts_direct_receive_lines');
    }
};
