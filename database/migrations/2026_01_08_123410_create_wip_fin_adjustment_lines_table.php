<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wip_fin_adjustment_lines', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('wip_fin_adjustment_id');
            $table->unsignedBigInteger('bundle_id');
            $table->unsignedBigInteger('item_id');

            $table->unsignedInteger('qty'); // INTEGER pcs
            $table->string('line_notes', 255)->nullable();

            $table->timestamps();

            $table->foreign('wip_fin_adjustment_id')
                ->references('id')->on('wip_fin_adjustments')
                ->cascadeOnDelete();

            $table->foreign('bundle_id')
                ->references('id')->on('cutting_job_bundles');

            $table->foreign('item_id')
                ->references('id')->on('items');

            $table->index(['bundle_id', 'item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wip_fin_adjustment_lines');
    }
};
