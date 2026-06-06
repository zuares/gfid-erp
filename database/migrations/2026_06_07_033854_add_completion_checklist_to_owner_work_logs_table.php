<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('owner_work_logs', function (Blueprint $table) {
            $table->boolean('check_route')->default(false)->after('rollback_notes');
            $table->boolean('check_view')->default(false)->after('check_route');
            $table->boolean('check_form')->default(false)->after('check_view');
            $table->boolean('check_mobile')->default(false)->after('check_form');
            $table->boolean('check_no_bak')->default(false)->after('check_mobile');
            $table->boolean('check_optimize_clear')->default(false)->after('check_no_bak');
            $table->boolean('check_git_status')->default(false)->after('check_optimize_clear');
            $table->timestamp('completed_at')->nullable()->after('check_git_status');
        });
    }

    public function down(): void
    {
        Schema::table('owner_work_logs', function (Blueprint $table) {
            $table->dropColumn([
                'check_route',
                'check_view',
                'check_form',
                'check_mobile',
                'check_no_bak',
                'check_optimize_clear',
                'check_git_status',
                'completed_at',
            ]);
        });
    }
};
