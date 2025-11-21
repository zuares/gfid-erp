<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warehouses', function (Blueprint $table) {
            $table->id();

            // Kode gudang unik, misal: RM, WIP-CUT, FG, KONTRAKAN
            $table->string('code')->unique();

            // Nama lengkap gudang
            $table->string('name');

            // Jenis gudang: internal / external / wip / fg / transit, dll
            $table->string('type')->default('internal');

            // Status aktif / non-aktif
            $table->boolean('active')->default(true);

            // Opsional: alamat dan catatan
            $table->string('address')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warehouses');
    }
};
