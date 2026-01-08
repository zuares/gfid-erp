<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wip_fin_adjustments', function (Blueprint $table) {
            $table->id();

            $table->string('code')->unique();
            $table->date('date');

            $table->enum('type', ['in', 'out']); // in= tambah WIP-FIN, out= kurangi WIP-FIN
            $table->string('reason', 100)->nullable();
            $table->text('notes')->nullable();

            $table->enum('status', ['draft', 'posted', 'void'])->default('draft');

            $table->timestamp('posted_at')->nullable();
            $table->unsignedBigInteger('posted_by')->nullable();

            $table->timestamp('voided_at')->nullable();
            $table->unsignedBigInteger('voided_by')->nullable();
            $table->string('void_reason', 255)->nullable();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->timestamps();

            $table->index(['date', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wip_fin_adjustments');
    }
};
