<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('items', function (Blueprint $table) {
            // default true: semua item dianggap masuk HPP kecuali kita set false via seeder
            $table->boolean('affects_hpp')->default(true)->after('category_id'); // sesuaikan after(...) kalau kolom beda
            $table->index('affects_hpp');
        });
    }

    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->dropIndex(['affects_hpp']);
            $table->dropColumn('affects_hpp');
        });
    }
};
