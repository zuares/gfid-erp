<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Stock: produk + variant ────────────────────────────────────────────
        Schema::table('storefront_products', function (Blueprint $table) {
            // Fallback stock — dipakai jika produk tidak punya active variants
            $table->unsignedInteger('stock')->default(0)->after('sort_order');
        });

        Schema::table('storefront_product_variants', function (Blueprint $table) {
            // Stok per variant; ProductRankingService akan sum ini jika ada variant aktif
            $table->unsignedInteger('stock')->default(0)->after('is_active');
        });

        // ── Manual override (owner) ───────────────────────────────────────────
        Schema::table('storefront_products', function (Blueprint $table) {
            $table->boolean('is_pinned')->default(false)->after('stock');
            $table->unsignedSmallInteger('pin_position')->nullable()->after('is_pinned');
            // Additive boost yang dijumlahkan ke final_score (tidak ternormalisasi)
            // Nilai wajar: 0.0–1.0. Bisa lebih besar untuk paksa ke atas
            $table->float('manual_boost', 5, 3)->default(0)->after('pin_position');
            // Jika masih dalam rentang ini → inject +0.5 ke manual_boost secara otomatis
            $table->timestamp('featured_until')->nullable()->after('manual_boost');
        });

        // ── Rank hasil komputasi ──────────────────────────────────────────────
        Schema::table('storefront_products', function (Blueprint $table) {
            $table->float('rank_score', 8, 5)->nullable()->after('featured_until');
            $table->unsignedInteger('rank_position')->nullable()->after('rank_score');
            $table->timestamp('rank_updated_at')->nullable()->after('rank_position');
            // Breakdown tiap komponen untuk debugging
            $table->json('rank_debug')->nullable()->after('rank_updated_at');

            $table->index('rank_position');
        });
    }

    public function down(): void
    {
        Schema::table('storefront_products', function (Blueprint $table) {
            $table->dropIndex(['rank_position']);
            $table->dropColumn([
                'stock', 'is_pinned', 'pin_position', 'manual_boost', 'featured_until',
                'rank_score', 'rank_position', 'rank_updated_at', 'rank_debug',
            ]);
        });

        Schema::table('storefront_product_variants', function (Blueprint $table) {
            $table->dropColumn('stock');
        });
    }
};
