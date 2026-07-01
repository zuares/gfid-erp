<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('storefront_products', function (Blueprint $table) {
            $table->string('audience', 20)->nullable()->after('category_id');
            // Values: pria | wanita | anak | olahraga | unisex
        });
    }

    public function down(): void
    {
        Schema::table('storefront_products', function (Blueprint $table) {
            $table->dropColumn('audience');
        });
    }
};
