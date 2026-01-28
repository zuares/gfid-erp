<?php
// database/migrations/2026_01_29_000002_create_purchase_return_lines_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_return_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_return_id')->constrained('purchase_returns')->cascadeOnDelete();

            $table->foreignId('purchase_receipt_line_id')->constrained('purchase_receipt_lines');
            $table->foreignId('item_id')->constrained('items');
            $table->foreignId('lot_id')->nullable()->constrained('lots');

            $table->decimal('qty', 18, 4)->default(0);
            $table->decimal('unit_price', 18, 4)->default(0); // ambil dari GRN line
            $table->decimal('line_total', 18, 2)->default(0);

            $table->string('notes')->nullable();
            $table->timestamps();

            $table->index(['purchase_receipt_line_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_return_lines');
    }
};
