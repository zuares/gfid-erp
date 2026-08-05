<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private string $table = 'marketplace_bookings';
    private string $newIndex = 'marketplace_bookings_store_booking_sn_unique';

    public function up(): void
    {
        if (! Schema::hasTable($this->table)) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();
        $oldIndex = $this->findUniqueIndexName($driver, $this->table, ['booking_sn']);

        if ($oldIndex === null) {
            throw new \RuntimeException(
                "Migration dibatalkan: unique index global booking_sn tidak ditemukan pada {$this->table}."
            );
        }

        Schema::table($this->table, function (Blueprint $table) use ($oldIndex): void {
            $table->dropUnique($oldIndex);
            $table->unique(['store_id', 'booking_sn'], $this->newIndex);
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable($this->table)) {
            return;
        }

        $conflicts = DB::table($this->table)
            ->select('booking_sn')
            ->groupBy('booking_sn')
            ->havingRaw('COUNT(DISTINCT store_id) > 1')
            ->count();

        if ($conflicts > 0) {
            throw new \RuntimeException(
                "Rollback dibatalkan: {$conflicts} booking_sn dipakai oleh lebih dari satu toko."
            );
        }

        Schema::table($this->table, function (Blueprint $table): void {
            $table->dropUnique($this->newIndex);
            $table->unique('booking_sn');
        });
    }

    private function findUniqueIndexName(string $driver, string $table, array $columns): ?string
    {
        if ($driver === 'sqlite') {
            foreach (DB::select("PRAGMA index_list('{$table}')") as $index) {
                if ((int) ($index->unique ?? 0) !== 1) {
                    continue;
                }

                $indexColumns = array_map(
                    static fn ($column): string => (string) $column->name,
                    DB::select("PRAGMA index_info('{$index->name}')")
                );

                if ($indexColumns === $columns) {
                    return (string) $index->name;
                }
            }

            return null;
        }

        if ($driver === 'mysql') {
            $rows = DB::select(
                'SELECT index_name AS index_name,
                        GROUP_CONCAT(column_name ORDER BY seq_in_index) AS cols,
                        MAX(non_unique) AS non_unique
                 FROM information_schema.statistics
                 WHERE table_schema = DATABASE() AND table_name = ?
                 GROUP BY index_name',
                [$table]
            );
            $wanted = implode(',', $columns);

            foreach ($rows as $row) {
                if ((int) $row->non_unique === 0 && $row->cols === $wanted) {
                    return (string) $row->index_name;
                }
            }
        }

        return null;
    }
};
