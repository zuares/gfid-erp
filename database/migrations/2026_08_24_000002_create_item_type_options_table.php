<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('item_type_options', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name', 100);
            $table->string('base_type', 30)->index();
            $table->boolean('active')->default(true)->index();
            $table->boolean('is_system')->default(false)->index();
            $table->timestamps();
        });

        $now = now();
        DB::table('item_type_options')->insert([
            ['code' => 'material', 'name' => 'Material / Bahan', 'base_type' => 'material', 'active' => 1, 'is_system' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'wip', 'name' => 'Setengah Jadi (WIP)', 'base_type' => 'wip', 'active' => 1, 'is_system' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'finished_good', 'name' => 'Barang Jadi (FG)', 'base_type' => 'finished_good', 'active' => 1, 'is_system' => 1, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('item_type_options');
    }
};
