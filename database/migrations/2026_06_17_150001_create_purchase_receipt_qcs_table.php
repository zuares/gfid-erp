<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Idempotent: skip jika tabel sudah ada
        if (Schema::hasTable('purchase_receipt_qcs')) {
            return;
        }

        Schema::create('purchase_receipt_qcs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('purchase_receipt_id')
                ->constrained('purchase_receipts')
                ->cascadeOnDelete();

            // Siapa yang melakukan pemeriksaan QC
            $table->foreignId('checked_by')
                ->nullable()
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->timestamp('checked_at')->nullable();

            // Status QC: draft | passed | issue | rejected | cancelled
            $table->string('status', 20)->default('draft');

            // Qty
            $table->decimal('qty_checked', 18, 2)->default(0);
            $table->decimal('qty_ok',      18, 2)->default(0);
            $table->decimal('qty_issue',   18, 2)->default(0);

            // Jenis masalah (nullable — diisi jika issue/rejected)
            // Values: rusak | salah_item | salah_warna | kurang_qty | lebih_qty | kualitas_tidak_sesuai | lainnya
            $table->string('issue_type', 50)->nullable();

            $table->text('notes')->nullable();
            $table->string('photo_path', 500)->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_receipt_qcs');
    }
};
