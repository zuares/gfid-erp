<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('items', 'design_code')) {
            Schema::table('items', function (Blueprint $table) {
                $table->dropColumn('design_code');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('items', 'design_code')) {
            Schema::table('items', function (Blueprint $table) {
                $table->string('design_code')->nullable();
            });
        }
    }
};
