<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Queue\Middleware\WithoutOverlapping;

class SyncFinanceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 1800; // 30 menit
    public $tries = 3;
    public $backoff = [60, 300];

    /**
     * Anti-overlap: klik dobel / bentrok dengan jadwal 4-jam-an tidak boleh
     * menjalankan dua sync finance bersamaan (rawan "database is locked" di
     * SQLite dan data ganda). Job kedua di-release 120 dtk, dicoba lagi
     * setelah yang pertama selesai.
     */
    public function middleware(): array
    {
        return [
            (new WithoutOverlapping('sync-finance-all'))
                ->expireAfter(1900)
                ->releaseAfter(120),
        ];
    }

    public function __construct(
        public string $trigger = 'manual',
        public int $months = 1,
        public ?int $days = null,       // 1-183 — mengalahkan months jika diisi
        public string $mode = 'full'    // 'full' | 'missing' (cek DB, ambil yang belum ada saja)
    ) {
    }

    public function handle()
    {
        // Run-tracking (pola sama dengan sync ads): status selalu difinalisasi,
        // tidak ada run yang tertinggal 'processing' selamanya.
        $run = \App\Models\MarketplaceFinanceSyncRun::create([
            'trigger'    => $this->trigger,
            'status'     => 'processing',
            'started_at' => now(),
        ]);

        /*
        | Output command di-stream LANGSUNG ke file per-run
        | (storage/logs/sync-finance-run-{id}.log) lewat StreamOutput —
        | endpoint status membaca file ini selagi run masih 'processing',
        | jadi log detail tampil live di UI, bukan hanya setelah selesai.
        */
        $livePath = storage_path("logs/sync-finance-run-{$run->id}.log");

        try {
            $params = ['--months' => $this->months, '--mode' => $this->mode];
            if ($this->days !== null) $params['--days'] = $this->days;

            // Baris pembuka langsung ditulis supaya konsol UI tidak kosong
            @file_put_contents($livePath, '[run #' . $run->id . '] ' . $this->trigger . ' — dimulai ' . now()->format('d/m H:i:s') . "\n", FILE_APPEND);

            $stream = fopen($livePath, 'a');
            $liveOutput = new \Symfony\Component\Console\Output\StreamOutput($stream);
            Artisan::call('marketplace:sync-finance', $params, $liveOutput);
            fclose($stream);

            $output = trim((string) @file_get_contents($livePath));

            // Log lengkap ke file gabungan (tail-able), potongan akhir ke run row.
            $this->appendLogFile($run->id, $output);

            $run->update([
                'status'      => 'success',
                'output'      => mb_substr($output, -8000),
                'finished_at' => now(),
            ]);
        } catch (\Throwable $e) {
            @file_put_contents($livePath, "\n[ERROR] " . $e->getMessage() . "\n", FILE_APPEND);
            $output = trim((string) @file_get_contents($livePath));
            $this->appendLogFile($run->id, $output);

            $run->update([
                'status'        => 'error',
                'error_message' => substr($e->getMessage(), 0, 1000),
                'output'        => mb_substr($output, -8000),
                'finished_at'   => now(),
            ]);
            throw $e;
        }
    }

    protected function appendLogFile(int $runId, string $output): void
    {
        try {
            file_put_contents(
                storage_path('logs/sync-finance.log'),
                '===== run #' . $runId . ' (' . $this->trigger . ') @ ' . now()->toDateTimeString() . " =====\n" . $output . "\n\n",
                FILE_APPEND
            );
        } catch (\Throwable) {
            // logging best-effort — jangan gagalkan sync karena file log
        }
    }

    public function failed(\Throwable $exception)
    {
        \App\Models\MarketplaceFinanceSyncRun::where('status', 'processing')
            ->where('started_at', '<', now()->subMinutes(1))
            ->update([
                'status'        => 'error',
                'error_message' => substr($exception->getMessage(), 0, 1000),
                'finished_at'   => now(),
            ]);
    }
}
