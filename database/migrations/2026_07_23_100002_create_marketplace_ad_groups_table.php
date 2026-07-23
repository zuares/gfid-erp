<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Grup iklan manual — untuk mengelompokkan campaign lintas toko/campaign
 * (mis. "Gamis Lebaran 2026"). Relasi 1 campaign : 1 grup via
 * marketplace_ad_campaigns.ad_group_id.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketplace_ad_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('slug', 120)->nullable()->unique();
            $table->string('color', 20)->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->index('name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketplace_ad_groups');
    }
};
