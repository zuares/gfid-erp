<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipment_scan_ringtones', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('original_name')->nullable();
            $table->string('path');
            $table->string('mime_type', 100)->nullable();
            $table->string('extension', 12)->default('mp3');
            $table->unsignedBigInteger('size_bytes')->default(0);
            $table->unsignedBigInteger('compressed_size_bytes')->default(0);
            $table->unsignedInteger('duration_ms')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipment_scan_ringtones');
    }
};
