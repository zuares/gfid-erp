<?php

namespace App\Console\Commands;

use App\Models\Journal;
use App\Models\PieceworkPayrollPeriod;
use App\Services\Accounting\StandalonePayrollPostingService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class BackfillStandalonePayroll extends Command
{
    protected $signature = 'accounting:backfill-payroll
        {--period=* : Batasi ke ID periode tertentu}
        {--module= : cutting, sewing, atau daily}
        {--dry-run : Pratinjau saja (default)}
        {--post : Posting seluruh kandidat secara atomik setelah validasi}
        {--json : Keluarkan laporan JSON}';

    protected $description = 'Pratinjau/migrasi jurnal payroll dan sumber upah produksi ke Akuntansi Baru; tidak membuat saldo awal.';

    public function handle(StandalonePayrollPostingService $service): int
    {
        $validator = Validator::make(['periods' => $this->option('period'), 'module' => $this->option('module')], [
            'periods' => ['array'], 'periods.*' => ['integer', 'min:1', 'exists:piecework_payroll_periods,id'],
            'module' => ['nullable', 'in:cutting,sewing,daily'],
        ]);
        if ($validator->fails() || ($this->option('post') && $this->option('dry-run'))) {
            $this->error($validator->fails() ? $validator->errors()->first() : 'Pilih --post atau --dry-run.');

            return self::FAILURE;
        }
        if ($this->option('post') && ! config('accounting.standalone_dual_posting_payroll_enabled', true)) {
            $this->error('Posting payroll Akuntansi Baru dinonaktifkan.');

            return self::FAILURE;
        }
        $report = ['mode' => $this->option('post') ? 'post' : 'preview', 'periods' => [], 'errors' => [], 'reconciliation_warnings' => [],
            'unlinked_correction_ids' => Journal::where('source_type', 'manual_payroll_correction')->whereNull('voided_at')->pluck('id')->all()];
        try {
            DB::transaction(function () use ($service, &$report) {
                $periods = PieceworkPayrollPeriod::whereIn('status', ['final', 'posted'])
                    ->when($this->option('period'), fn ($q, $ids) => $q->whereIn('id', $ids))
                    ->when($this->option('module'), fn ($q, $module) => $q->where('module', $module))
                    ->orderBy('id')->when($this->option('post'), fn ($q) => $q->lockForUpdate())->get();
                $unique = collect();
                foreach ($periods as $period) {
                    try {
                        $plan = $service->plan($period, (bool) $this->option('post'));
                        foreach ($plan as $entry) {
                            $unique->put($entry['legacy']->id, $entry);
                        }
                        $reconciliation = $service->reconciliation($period, $plan);
                        if ($reconciliation['difference'] !== 0.0 || $reconciliation['payment_difference'] !== 0.0) {
                            $report['reconciliation_warnings'][] = ['period_id' => $period->id] + $reconciliation;
                        }
                        $report['periods'][] = [
                            'id' => $period->id, 'module' => $period->module,
                            'total' => (float) $period->total_amount,
                            'payroll_journals' => $plan->where('production', false)->count(),
                            'production_journals' => $plan->where('production', true)->count(),
                            'missing' => $plan->whereNull('existing')->count(),
                        ];
                    } catch (ValidationException $e) {
                        $report['errors'][] = "Periode #{$period->id}: ".$e->getMessage();
                    }
                }
                $report['unique_journals'] = $unique->count();
                $report['new_journals'] = $unique->whereNull('existing')->count();
                $report['already_posted'] = $unique->whereNotNull('existing')->count();
                $report['debit'] = round($unique->sum(fn ($e) => collect($e['lines'])->sum('debit')), 2);
                $report['credit'] = round($unique->sum(fn ($e) => collect($e['lines'])->sum('credit')), 2);
                if ($report['errors'] || ! $this->option('post')) {
                    return;
                }
                foreach ($periods as $period) {
                    $service->sync($period);
                }
                $report['posted'] = true;
            });
        } catch (\Throwable $e) {
            $report['errors'][] = $e->getMessage();
            $report['posted'] = false;
        }
        if ($this->option('json')) {
            $this->line(json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        } else {
            $this->info($report['mode'] === 'post' ? 'Migrasi payroll' : 'Pratinjau payroll — tanpa perubahan data');
            $this->table(['Periode', 'Modul', 'Bruto', 'Jurnal payroll', 'Sumber produksi', 'Belum diposting'], $report['periods']);
            $this->line('Jurnal unik: '.($report['unique_journals'] ?? 0).' | Baru: '.($report['new_journals'] ?? 0));
            foreach ($report['errors'] as $error) {
                $this->error($error);
            }
            foreach ($report['reconciliation_warnings'] as $warning) {
                $this->warn('Periode #'.$warning['period_id'].' selisih akrual: '.$warning['difference'].'; selisih pembayaran: '.$warning['payment_difference'].'. Histori dipertahankan, perlu rekonsiliasi.');
            }
            if ($report['unlinked_correction_ids']) {
                $this->warn('Koreksi manual tidak dimigrasikan karena belum terhubung ke periode: #'.implode(', #', $report['unlinked_correction_ids']));
            }
            if ($report['posted'] ?? false) {
                $this->info('Posting selesai. Tidak ada saldo awal yang dibuat.');
            }
        }

        return $report['errors'] ? self::FAILURE : self::SUCCESS;
    }
}
