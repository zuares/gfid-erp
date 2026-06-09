<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // channels — tambah status & meta
        Schema::table('channels', function (Blueprint $table) {
            if (! Schema::hasColumn('channels', 'status')) {
                $table->string('status')->default('active')->after('name');
            }
            if (! Schema::hasColumn('channels', 'meta')) {
                $table->json('meta')->nullable()->after('status');
            }
        });

        // stores — tambah kolom omnichannel
        Schema::table('stores', function (Blueprint $table) {
            if (! Schema::hasColumn('stores', 'external_shop_id')) {
                $table->string('external_shop_id')->nullable()->after('name');
            }
            if (! Schema::hasColumn('stores', 'region')) {
                $table->string('region')->nullable()->after('external_shop_id');
            }
            if (! Schema::hasColumn('stores', 'status')) {
                $table->string('status')->default('active')->after('region');
            }
            if (! Schema::hasColumn('stores', 'credentials')) {
                $table->text('credentials')->nullable()->after('status');
            }
            if (! Schema::hasColumn('stores', 'token_expires_at')) {
                $table->timestamp('token_expires_at')->nullable()->after('credentials');
            }
            if (! Schema::hasColumn('stores', 'last_synced_at')) {
                $table->timestamp('last_synced_at')->nullable()->after('token_expires_at');
            }
            if (! Schema::hasColumn('stores', 'meta')) {
                $table->json('meta')->nullable()->after('last_synced_at');
            }
        });

        // marketplace_orders — tambah kolom omnichannel baru
        Schema::table('marketplace_orders', function (Blueprint $table) {
            if (! Schema::hasColumn('marketplace_orders', 'channel_order_id')) {
                $table->string('channel_order_id')->nullable()->index()->after('store_id');
            }
            if (! Schema::hasColumn('marketplace_orders', 'booking_sn')) {
                $table->string('booking_sn')->nullable()->after('channel_order_id');
            }
            if (! Schema::hasColumn('marketplace_orders', 'order_status')) {
                $table->string('order_status')->nullable()->after('booking_sn');
            }
            if (! Schema::hasColumn('marketplace_orders', 'buyer_username')) {
                $table->string('buyer_username')->nullable()->after('order_status');
            }
            if (! Schema::hasColumn('marketplace_orders', 'payment_method')) {
                $table->string('payment_method')->nullable()->after('buyer_username');
            }
            if (! Schema::hasColumn('marketplace_orders', 'shipping_carrier')) {
                $table->string('shipping_carrier')->nullable()->after('payment_method');
            }
            if (! Schema::hasColumn('marketplace_orders', 'total_amount')) {
                $table->decimal('total_amount', 15, 2)->default(0)->after('shipping_carrier');
            }
            if (! Schema::hasColumn('marketplace_orders', 'currency')) {
                $table->string('currency', 10)->default('IDR')->after('total_amount');
            }
            if (! Schema::hasColumn('marketplace_orders', 'ordered_at')) {
                $table->timestamp('ordered_at')->nullable()->after('currency');
            }
            if (! Schema::hasColumn('marketplace_orders', 'synced_at')) {
                $table->timestamp('synced_at')->nullable()->after('ordered_at');
            }
            if (! Schema::hasColumn('marketplace_orders', 'raw_json')) {
                $table->json('raw_json')->nullable()->after('synced_at');
            }
        });

        // marketplace_order_items — tambah kolom omnichannel baru
        Schema::table('marketplace_order_items', function (Blueprint $table) {
            if (! Schema::hasColumn('marketplace_order_items', 'marketplace_order_id')) {
                $table->unsignedBigInteger('marketplace_order_id')->nullable()->after('order_id');
                $table->index('marketplace_order_id');
            }
            if (! Schema::hasColumn('marketplace_order_items', 'external_model_id')) {
                $table->string('external_model_id')->nullable()->after('external_item_id');
            }
            if (! Schema::hasColumn('marketplace_order_items', 'item_name')) {
                $table->string('item_name')->nullable()->after('external_model_id');
            }
            if (! Schema::hasColumn('marketplace_order_items', 'item_sku')) {
                $table->string('item_sku')->nullable()->after('item_name');
            }
            if (! Schema::hasColumn('marketplace_order_items', 'model_sku')) {
                $table->string('model_sku')->nullable()->after('item_sku');
            }
            if (! Schema::hasColumn('marketplace_order_items', 'variant_name')) {
                $table->string('variant_name')->nullable()->after('model_sku');
            }
            if (! Schema::hasColumn('marketplace_order_items', 'price')) {
                $table->decimal('price', 15, 2)->default(0)->after('variant_name');
            }
            if (! Schema::hasColumn('marketplace_order_items', 'image_url')) {
                $table->string('image_url')->nullable()->after('price');
            }
            if (! Schema::hasColumn('marketplace_order_items', 'raw_json')) {
                $table->json('raw_json')->nullable()->after('image_url');
            }
        });
    }

    public function down(): void
    {
        Schema::table('channels', function (Blueprint $table) {
            $table->dropColumn(['status', 'meta']);
        });

        Schema::table('stores', function (Blueprint $table) {
            $table->dropColumn(['external_shop_id', 'region', 'status', 'credentials', 'token_expires_at', 'last_synced_at', 'meta']);
        });

        Schema::table('marketplace_orders', function (Blueprint $table) {
            $table->dropColumn(['channel_order_id', 'booking_sn', 'order_status', 'buyer_username', 'payment_method', 'shipping_carrier', 'total_amount', 'currency', 'ordered_at', 'synced_at', 'raw_json']);
        });

        Schema::table('marketplace_order_items', function (Blueprint $table) {
            $table->dropColumn(['marketplace_order_id', 'external_model_id', 'item_name', 'item_sku', 'model_sku', 'variant_name', 'price', 'image_url', 'raw_json']);
        });
    }
};
