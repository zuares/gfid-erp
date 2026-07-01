<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('storefront_events', function (Blueprint $table) {
            $table->id();
            $table->string('visitor_token', 64)->index();
            // page_view | product_view | add_to_cart | remove_from_cart
            // checkout_start | address_fill | order_complete | wa_click
            $table->string('event_type', 50);
            $table->json('payload')->nullable(); // product_id, url, qty, dll
            $table->timestamp('created_at')->useCurrent();

            $table->index(['visitor_token', 'event_type']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('storefront_events');
    }
};
