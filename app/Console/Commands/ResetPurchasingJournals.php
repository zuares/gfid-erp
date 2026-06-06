<?php

namespace App\Console\Commands;

use App\Models\Journal;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ResetPurchasingJournals extends Command
{
    protected $signature = 'accounting:reset-purchasing-journals
        {--apply : Benar-benar soft-void jurnal (default: dry-run)}
        {--force : Lewati konfirmasi saat apply}
        {--source=* : Batasi source_type tertentu, boleh lebih dari satu}
        {--after= : Batasi jurnal dari tanggal ini, format YYYY-MM-DD}
        {--before= : Batasi jurnal sampai tanggal ini, format YYYY-MM-DD}';

    protected $description = 'Preview/soft-void jurnal accounting dari purchasing agar COA bisa dibersihkan. Default dry-run.';

    private const DEFAULT_SOURCES = [
        'grn',
        'grn_inv',
        'grn_exp',
        'purchase_payment',
        'purchase_return_post',
        'purchase_return_inv',
        'purchase_return_exp',
    ];

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');
        $sources = $this->option('source') ?: self::DEFAULT_SOURCES;
        $after = $this->option('after');
        $before = $this->option('before');

        if (!$this->validDateOption($after, 'after') || !$this->validDateOption($before, 'before')) {
            return self::FAILURE;
        }

        $base = Journal::query()
            ->whereNull('voided_at')
            ->whereIn('source_type', $sources)
            ->when($after, fn($q) => $q->whereDate('date', '>=', $after))
            ->when($before, fn($q) => $q->whereDate('date', '<=', $before));

        $journalIds = (clone $base)->orderBy('id')->pluck('id');

        $this->info($apply ? '>>> MODE APPLY (akan soft-void jurnal)' : '>>> MODE DRY-RUN (preview saja)');
        $this->line('Source: ' . implode(', ', $sources));
        if ($after || $before) {
            $this->line('Tanggal: ' . ($after ?: 'awal') . ' s/d ' . ($before ?: 'akhir'));
        }
        $this->newLine();

        if ($journalIds->isEmpty()) {
            $this->info('Tidak ada jurnal purchasing aktif yang cocok. Accounting sudah bersih untuk filter ini.');
            return self::SUCCESS;
        }

        $summaryRows = (clone $base)
            ->selectRaw('source_type, COUNT(*) as journal_count, MIN(date) as first_date, MAX(date) as last_date')
            ->groupBy('source_type')
            ->orderBy('source_type')
            ->get()
            ->map(fn($row) => [
                $row->source_type,
                number_format((int) $row->journal_count, 0, ',', '.'),
                $row->first_date,
                $row->last_date,
            ])
            ->all();

        $impactRows = DB::table('journal_lines as jl')
            ->join('accounts as a', 'a.id', '=', 'jl.account_id')
            ->whereIn('jl.journal_id', $journalIds)
            ->groupBy('a.code', 'a.name')
            ->orderBy('a.code')
            ->selectRaw('a.code, a.name, COALESCE(SUM(jl.debit - jl.credit), 0) as balance')
            ->get()
            ->map(fn($row) => [
                $row->code,
                $row->name,
                $this->money((float) $row->balance),
                $this->money((float) $row->balance * -1),
            ])
            ->all();

        $this->table(['Source', 'Jurnal', 'Dari', 'Sampai'], $summaryRows);
        $this->newLine();
        $this->table(['Kode', 'Akun', 'Saldo Sekarang Dari Source', 'Efek Setelah Dibersihkan'], $impactRows);

        $this->newLine();
        $this->line('Total jurnal yang kena: <fg=yellow>' . number_format($journalIds->count(), 0, ',', '.') . '</>');
        $this->line('Yang disentuh hanya <fg=yellow>journals.voided_at</>. Journal lines, PO, GRN, pembayaran, retur, dan stok tidak dihapus.');

        if (!$apply) {
            $this->newLine();
            $this->warn('Ini hanya PREVIEW. Untuk eksekusi:');
            $this->line('   php artisan accounting:reset-purchasing-journals --apply');
            return self::SUCCESS;
        }

        if (!$this->option('force') && !$this->confirm('Lanjut soft-void jurnal purchasing di atas?', false)) {
            $this->warn('Dibatalkan. Tidak ada data yang diubah.');
            return self::SUCCESS;
        }

        DB::transaction(function () use ($journalIds) {
            Journal::query()
                ->whereIn('id', $journalIds)
                ->whereNull('voided_at')
                ->lockForUpdate()
                ->update(['voided_at' => now()]);
        });

        $this->info('Selesai. ' . number_format($journalIds->count(), 0, ',', '.') . ' jurnal purchasing sudah di-soft-void.');
        $this->line('Langkah berikutnya: input saldo awal accounting yang benar jika saldo akun perlu dibuka lagi.');

        return self::SUCCESS;
    }

    private function validDateOption(?string $value, string $option): bool
    {
        if (!$value) {
            return true;
        }

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            $this->error("Option --{$option} harus format YYYY-MM-DD.");
            return false;
        }

        return true;
    }

    private function money(float $value): string
    {
        return 'Rp ' . number_format($value, 0, ',', '.');
    }
}
