<?php

// database/migrations/2025_12_04_100000_create_marketplace_channels_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketplace_channels', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique(); // shopee, tokopedia, tiktok
            $table->string('name', 100);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketplace_channels');
    }
};
