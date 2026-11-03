<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('sewing_pickup_lines')) {
            Schema::table('sewing_pickup_lines', function (Blueprint $table): void {
                if (! Schema::hasColumn('sewing_pickup_lines', 'qty_rejected')) {
                    $table->decimal('qty_rejected', 10, 2)->default(0);
                }

                if (! Schema::hasColumn('sewing_pickup_lines', 'reject_unit_cost')) {
                    $table->decimal('reject_unit_cost', 18, 6)->default(0);
                }

                if (! Schema::hasColumn('sewing_pickup_lines', 'reject_reason')) {
                    $table->string('reject_reason', 255)->nullable();
                }
            });
        }

        $now = now();

        foreach ([
            [
                'source_type' => 'sewing_pickup_reject',
                'legacy_account_code' => '1204',
                'line_side' => 'debit',
                'standalone_account_code' => '1-153',
                'stage' => 'sewing',
                'purpose' => 'Reject susulan saat ambil jahit masuk persediaan reject',
            ],
            [
                'source_type' => 'sewing_pickup_reject',
                'legacy_account_code' => '1202',
                'line_side' => 'credit',
                'standalone_account_code' => '1-151-01',
                'stage' => 'sewing',
                'purpose' => 'Reject susulan keluar dari WIP Cutting',
            ],
        ] as $mapping) {
            DB::table('standalone_production_account_mappings')->updateOrInsert(
                [
                    'source_type' => $mapping['source_type'],
                    'legacy_account_code' => $mapping['legacy_account_code'],
                    'line_side' => $mapping['line_side'],
                ],
                [
                    'standalone_account_code' => $mapping['standalone_account_code'],
                    'stage' => $mapping['stage'],
                    'purpose' => $mapping['purpose'],
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            );
        }
    }

    public function down(): void
    {
        DB::table('standalone_production_account_mappings')
            ->where('source_type', 'sewing_pickup_reject')
            ->delete();

        if (Schema::hasTable('sewing_pickup_lines')) {
            Schema::table('sewing_pickup_lines', function (Blueprint $table): void {
                foreach (['reject_reason', 'reject_unit_cost', 'qty_rejected'] as $column) {
                    if (Schema::hasColumn('sewing_pickup_lines', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
