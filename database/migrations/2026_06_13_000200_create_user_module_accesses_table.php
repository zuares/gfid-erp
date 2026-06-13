<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('user_module_accesses')) {
            return;
        }

        Schema::create('user_module_accesses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('module', 40);
            $table->boolean('can_access')->default(false);
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'module']);
            $table->index(['module', 'can_access']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_module_accesses');
    }
};
