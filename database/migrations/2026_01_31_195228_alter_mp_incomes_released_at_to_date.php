<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('mp_incomes', function (Blueprint $table) {
            $table->date('released_date')->nullable()->after('released_at');
        });

        // optional: backfill from released_at
        DB::statement("UPDATE mp_incomes SET released_date = DATE(released_at) WHERE released_at IS NOT NULL");
    }

    public function down(): void
    {
        Schema::table('mp_incomes', function (Blueprint $table) {
            $table->dropColumn('released_date');
        });
    }

};
