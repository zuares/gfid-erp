<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sewing_pickups', function (Blueprint $table) {
            $table->string('void_reason', 150)->nullable()->after('status');
            $table->dateTime('voided_at')->nullable()->after('void_reason');
            $table->unsignedBigInteger('voided_by')->nullable()->after('voided_at');

            // optional: kalau ada tabel users dan mau FK
            // $table->foreign('voided_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('sewing_pickups', function (Blueprint $table) {
            // optional: kalau pakai FK, drop dulu FK-nya
            // $table->dropForeign(['voided_by']);

            $table->dropColumn(['void_reason', 'voided_at', 'voided_by']);
        });
    }
};
