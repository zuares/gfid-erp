<?php
// database/migrations/2026_01_29_000001_create_purchase_returns_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_returns', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->date('date');

            $table->foreignId('purchase_receipt_id')->constrained('purchase_receipts');
            $table->foreignId('purchase_order_id')->nullable()->constrained('purchase_orders');
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers');

            $table->string('status')->default('draft'); // draft|posted
            $table->decimal('total', 18, 2)->default(0);
            $table->text('notes')->nullable();

            $table->unsignedBigInteger('journal_id')->nullable(); // optional
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('posted_by')->nullable();
            $table->timestamp('posted_at')->nullable();

            $table->unsignedBigInteger('voided_by')->nullable();
            $table->timestamp('voided_at')->nullable();

            $table->timestamps();

            $table->index(['purchase_receipt_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_returns');
    }
};
