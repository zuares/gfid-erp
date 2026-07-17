<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\CuttingJob;
use App\Models\InventoryMutation;
use App\Services\Production\CuttingService;
use Illuminate\Support\Facades\DB;

class SyncMissingCuttingStock extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cutting:sync-missing-stock';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Mendeteksi dan memperbaiki Cutting Job yang belum memiliki mutasi pemotongan stok (RM OUT).';

    /**
     * Execute the console command.
     */
    public function handle(CuttingService $cuttingService)
    {
        $this->info('Mulai mengecek seluruh data Cutting Job...');

        $jobs = CuttingJob::with(['bundles', 'lots'])->get();
        $fixedCount = 0;
        $skippedCount = 0;

        $this->output->progressStart($jobs->count());

        foreach ($jobs as $job) {
            // Cek apakah ada pemakaian kain di bundle
            $totalUsed = (float) $job->bundles->sum('qty_used_fabric');
            if ($totalUsed <= 0.0001) {
                // Tidak ada kain yang dipakai, aman dilewati
                $this->output->progressAdvance();
                continue;
            }

            // Cek apakah mutasi keluarnya sudah ada
            $hasMutations = InventoryMutation::where('source_type', 'cutting_job')
                ->where('source_id', $job->id)
                ->where('direction', 'out')
                ->exists();

            if (!$hasMutations) {
                // Mutasi hilang, lakukan pemotongan ulang!
                try {
                    DB::transaction(function () use ($cuttingService, $job) {
                        $cuttingService->reconsumeFabricFromLots($job);
                    });
                    $fixedCount++;
                } catch (\Exception $e) {
                    $this->error("\nGagal sinkronisasi Job {$job->code}: " . $e->getMessage());
                }
            } else {
                $skippedCount++;
            }

            $this->output->progressAdvance();
        }

        $this->output->progressFinish();

        $this->info("Pengecekan selesai!");
        $this->line("- Total job dicek: {$jobs->count()}");
        $this->line("- Job sudah aman (dilewati): {$skippedCount}");
        $this->line("- Job berhasil diperbaiki (sinkronisasi stok): <info>{$fixedCount}</info>");

        return Command::SUCCESS;
    }
}
