<?php

namespace App\Console\Commands;

use App\Models\SystemSetting;
use Illuminate\Console\Command;

/**
 * Tampilkan semua system settings via terminal.
 *
 * Usage:
 *   php artisan settings:show
 *   php artisan settings:show --set-cutoff=2026-07-01
 *   php artisan settings:show --clear-cutoff
 */
class ShowSystemSettings extends Command
{
    protected $signature = 'settings:show
        {--set-cutoff= : Set cut-off date (format: YYYY-MM-DD)}
        {--clear-cutoff : Hapus cut-off date}';

    protected $description = 'Tampilkan / ubah system settings (cut-off date, dll)';

    public function handle(): int
    {
        // ── ACTION: set cut-off ─────────────────────────────
        if ($this->option('set-cutoff')) {
            $date = $this->option('set-cutoff');

            try {
                $parsed = \Carbon\Carbon::parse($date)->toDateString();
            } catch (\Throwable $e) {
                $this->error("Format tanggal tidak valid: {$date}. Gunakan YYYY-MM-DD.");
                return self::FAILURE;
            }

            SystemSetting::set(SystemSetting::KEY_CUTOFF_DATE, $parsed);
            $this->info("✅ Cut-off date berhasil di-set: {$parsed}");
            return self::SUCCESS;
        }

        // ── ACTION: clear cut-off ────────────────────────────
        if ($this->option('clear-cutoff')) {
            SystemSetting::remove(SystemSetting::KEY_CUTOFF_DATE);
            SystemSetting::remove(SystemSetting::KEY_CUTOFF_NOTES);
            $this->info('✅ Cut-off date berhasil dihapus.');
            return self::SUCCESS;
        }

        // ── DEFAULT: tampilkan semua settings ────────────────
        $this->line('');
        $this->line('  <fg=cyan;options=bold>SYSTEM SETTINGS</>');
        $this->line('  ' . str_repeat('─', 50));

        $cutoffDate  = SystemSetting::cutoffDateString();
        $cutoffNotes = SystemSetting::get(SystemSetting::KEY_CUTOFF_NOTES, '(tidak ada catatan)');

        if ($cutoffDate) {
            $this->line("  <fg=green>✅ Cut-off Date  :</> <options=bold>{$cutoffDate}</>");
            $this->line("  <fg=white>   Catatan       :</> {$cutoffNotes}");

            // Statistik singkat
            try {
                $stats = \Illuminate\Support\Facades\DB::table('inventory_mutations')
                    ->selectRaw("
                        COUNT(CASE WHEN date < ? THEN 1 END) as legacy_count,
                        COUNT(CASE WHEN date >= ? THEN 1 END) as new_count
                    ", [$cutoffDate, $cutoffDate])
                    ->first();

                $this->line('');
                $this->line("  <fg=yellow>  Mutasi sebelum cut-off (LEGACY) :</> {$stats->legacy_count}");
                $this->line("  <fg=green>  Mutasi setelah cut-off (BARU)   :</> {$stats->new_count}");
            } catch (\Throwable $e) {
                // skip jika tabel belum ada
            }
        } else {
            $this->line('  <fg=yellow>⚠️  Cut-off Date  :</> <fg=yellow>Belum di-set</>');
            $this->line('     Semua laporan menampilkan data historis.');
        }

        $this->line('');
        $this->line('  <fg=gray>Commands:</> ');
        $this->line('    php artisan settings:show --set-cutoff=YYYY-MM-DD   (set cut-off)');
        $this->line('    php artisan settings:show --clear-cutoff             (hapus cut-off)');
        $this->line('');

        return self::SUCCESS;
    }
}
