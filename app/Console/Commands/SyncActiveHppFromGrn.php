<?php

namespace App\Console\Commands;

use App\Services\Costing\ActiveHppService;
use Illuminate\Console\Command;

class SyncActiveHppFromGrn extends Command
{
    protected $signature = 'hpp:sync-active-from-grn
                            {--item= : Batasi ke ID item tertentu}
                            {--dry-run : Hanya tampilkan ringkasan tanpa mengubah data}';

    protected $description = 'Sinkronkan HPP aktif: GRN posted terakhir, atau HPP awal master jika belum ada GRN';

    public function handle(ActiveHppService $service): int
    {
        $itemId = $this->option('item') ? (int) $this->option('item') : null;
        $summary = $service->syncAll($itemId, (bool) $this->option('dry-run'));

        $this->table(
            ['Diperiksa', 'Berubah', 'Dari GRN', 'Dari Master', 'Tanpa HPP'],
            [[
                $summary['checked'],
                $summary['changed'],
                $summary['from_grn'],
                $summary['from_master'],
                $summary['no_cost'],
            ]]
        );

        return self::SUCCESS;
    }
}
