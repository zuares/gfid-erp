<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('owner_work_logs', function (Blueprint $table) {
            if (! Schema::hasColumn('owner_work_logs', 'done_at')) {
                $table->timestamp('done_at')->nullable()->after('status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('owner_work_logs', function (Blueprint $table) {
            if (Schema::hasColumn('owner_work_logs', 'done_at')) {
                $table->dropColumn('done_at');
            }
        });
    }
};
