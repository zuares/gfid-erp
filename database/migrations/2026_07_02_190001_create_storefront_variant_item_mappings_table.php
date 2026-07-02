<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('storefront_variant_item_mappings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('storefront_products')->cascadeOnDelete();
            $table->foreignId('variant_id')->constrained('storefront_product_variants')->cascadeOnDelete();
            $table->foreignId('size_id')->constrained('storefront_product_sizes')->cascadeOnDelete();
            $table->foreignId('item_id')->nullable()->constrained('items')->nullOnDelete();
            $table->unsignedBigInteger('price_override')->nullable();
            $table->unsignedInteger('stock_override')->nullable();
            $table->timestamps();

            $table->unique(['variant_id', 'size_id'], 'sf_variant_item_mapping_unique');
            $table->index(['product_id', 'item_id'], 'sf_variant_item_mapping_product_item_idx');
        });

        if (
            Schema::hasColumn('storefront_product_variants', 'size_label')
            && Schema::hasColumn('storefront_product_variants', 'item_id')
        ) {
            $legacyVariants = DB::table('storefront_product_variants')
                ->whereNotNull('size_label')
                ->where(function ($query) {
                    $query->whereNotNull('item_id')
                        ->orWhereNotNull('stock_override')
                        ->orWhereNotNull('price_override');
                })
                ->get();

            foreach ($legacyVariants as $legacy) {
                $sizeId = DB::table('storefront_product_sizes')
                    ->where('product_id', $legacy->product_id)
                    ->whereRaw('LOWER(size_label) = ?', [strtolower((string) $legacy->size_label)])
                    ->value('id');

                if (! $sizeId) {
                    continue;
                }

                $colorVariantId = DB::table('storefront_product_variants')
                    ->where('product_id', $legacy->product_id)
                    ->whereRaw('LOWER(color_name) = ?', [strtolower((string) $legacy->color_name)])
                    ->whereNull('size_label')
                    ->orderBy('sort_order')
                    ->orderBy('id')
                    ->value('id') ?: $legacy->id;

                DB::table('storefront_variant_item_mappings')->updateOrInsert(
                    ['variant_id' => $colorVariantId, 'size_id' => $sizeId],
                    [
                        'product_id'      => $legacy->product_id,
                        'item_id'         => $legacy->item_id,
                        'price_override'  => $legacy->price_override,
                        'stock_override'  => $legacy->stock_override,
                        'updated_at'      => now(),
                        'created_at'      => now(),
                    ]
                );
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('storefront_variant_item_mappings');
    }
};
