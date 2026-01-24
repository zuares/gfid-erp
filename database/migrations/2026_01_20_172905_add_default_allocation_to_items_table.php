<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('items', function (Blueprint $table) {
            // default allocation: hpp / expense
            $table->string('default_allocation', 16)
                ->default('hpp')
                ->after('affects_hpp');

            // akun biaya default (optional)
            $table->unsignedBigInteger('default_expense_account_id')
                ->nullable()
                ->after('default_allocation');

            // kalau kamu sudah punya table accounts & mau FK (optional, aman ditunda)
            // $table->foreign('default_expense_account_id')
            //     ->references('id')
            //     ->on('accounts')
            //     ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            // drop FK dulu kalau nanti dipakai
            // if (Schema::hasColumn('items', 'default_expense_account_id')) {
            //     $table->dropForeign(['default_expense_account_id']);
            // }

            $table->dropColumn([
                'default_expense_account_id',
                'default_allocation',
            ]);
        });
    }
};
