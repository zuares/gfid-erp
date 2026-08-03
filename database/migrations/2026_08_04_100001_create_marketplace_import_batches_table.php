<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketplace_import_batches', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('channel', 32)->index();
            $table->foreignId('store_id')->nullable()->constrained('stores')->nullOnDelete();
            $table->string('source_type', 20)->default('import')->index();
            $table->string('source_file')->nullable();
            $table->string('file_hash', 64)->nullable()->index();
            $table->string('status', 20)->default('preview')->index();
            $table->unsignedInteger('total_rows')->default(0);
            $table->unsignedInteger('shipments_parsed')->default(0);
            $table->unsignedInteger('items_parsed')->default(0);
            $table->unsignedInteger('inserted_shipments')->default(0);
            $table->unsignedInteger('updated_shipments')->default(0);
            $table->unsignedInteger('inserted_items')->default(0);
            $table->unsignedInteger('error_count')->default(0);
            $table->json('warnings')->nullable();
            $table->json('errors')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['store_id', 'channel', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketplace_import_batches');
    }
};
