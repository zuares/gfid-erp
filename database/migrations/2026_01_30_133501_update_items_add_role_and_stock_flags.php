<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('items', function (Blueprint $table) {

            // Peran bisnis item (logic utama)
            $table->string('item_role', 32)
                ->default('raw_material')
                ->after('type')
                ->comment('raw_material | production_supply | shipping_supply | finished_good');

            // Apakah item ini dicatat di inventory
            $table->boolean('is_stocked')
                ->default(true)
                ->after('item_role');

            // Perilaku HPP
            $table->string('hpp_behavior', 16)
                ->default('hpp')
                ->after('affects_hpp')
                ->comment('hpp | non_hpp');
        });
    }

    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->dropColumn([
                'item_role',
                'is_stocked',
                'hpp_behavior',
            ]);
        });
    }
};
