<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();

            // kode akun (misal: 1001, 5001)
            $table->string('code', 20)->unique();

            // nama akun (Kas, Bank BCA, Biaya Listrik, dll)
            $table->string('name');

            // asset | liability | equity | revenue | expense
            $table->string('type', 20)->index();

            // khusus kas / bank
            $table->boolean('is_cash')->default(false)->index();

            // aktif / nonaktif
            $table->boolean('is_active')->default(true)->index();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};
