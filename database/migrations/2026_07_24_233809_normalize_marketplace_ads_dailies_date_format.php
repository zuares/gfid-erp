<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Check for duplicates logically
        $duplicates = DB::table('marketplace_ads_dailies')
            ->select('store_id', DB::raw('DATE(date) as normalized_date'))
            ->groupBy('store_id', DB::raw('DATE(date)'))
            ->havingRaw('COUNT(*) > 1')
            ->get();

        if ($duplicates->isNotEmpty()) {
            throw new \Exception("Logical duplicates found, cannot normalize safely. Count: " . $duplicates->count());
        }

        // 2. Normalize all date columns
        // This query works on both MySQL and SQLite
        DB::statement("UPDATE marketplace_ads_dailies SET date = DATE(date) WHERE date LIKE '%00:00:00%' OR length(date) > 10");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // One-way migration
    }
};
