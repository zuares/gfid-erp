<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class OperationalStabilityCheck extends Command
{
    protected $signature = 'system:check
        {--no-backup : Lewati backup database}
        {--no-snapshot : Lewati snapshot checkpoint}
        {--days=7 : Batas umur dokumen draft/open yang perlu dipantau}
        {--limit=10 : Jumlah contoh baris yang ditampilkan per cek}';

    protected $aliases = [
        'ops:stabil',
        'app:check',
        'app:stabil',
    ];

    protected $description = 'Backup, snapshot, dan cek kesehatan operasional lokal dalam satu perintah.';

    public function handle(): int
    {
        $this->line('=== Mode Stabil Operasional Lokal ===');
        $this->line('Waktu: ' . now()->format('Y-m-d H:i:s'));
        $this->newLine();

        $hasIssue = false;

        if (! $this->option('no-backup')) {
            $hasIssue = $this->runChildCommand('db:backup') || $hasIssue;
        }

        if (! $this->option('no-snapshot')) {
            $hasIssue = $this->runChildCommand('db:snapshot') || $hasIssue;
        }

        $this->newLine();
        $this->info('Cek kesehatan operasional...');

        $checks = [
            $this->checkNegativeStock(),
            $this->checkNegativeLots(),
            $this->checkUnbalancedJournals(),
            $this->checkOldDraftDocuments(),
            $this->checkOpenSewingPickups(),
            $this->checkOpenCuttingBundles(),
        ];

        foreach ($checks as $check) {
            $hasIssue = $this->renderCheck($check) || $hasIssue;
        }

        $this->newLine();
        if ($hasIssue) {
            $this->warn('Selesai: ada temuan yang perlu dicek sebelum operasional lanjut terlalu jauh.');
            return self::FAILURE;
        }

        $this->info('Selesai: sistem lokal terlihat stabil untuk operasional.');
        return self::SUCCESS;
    }

    private function runChildCommand(string $command): bool
    {
        $this->info("Menjalankan {$command}...");

        $code = Artisan::call($command);
        $output = trim(Artisan::output());

        if ($output !== '') {
            $this->line($output);
        }

        if ($code !== self::SUCCESS) {
            $this->error("Command {$command} gagal.");
            return true;
        }

        $this->newLine();
        return false;
    }

    private function renderCheck(array $check): bool
    {
        $count = (int) ($check['count'] ?? 0);
        $level = $check['level'] ?? 'ok';
        $title = $check['title'] ?? 'Cek';
        $message = $check['message'] ?? null;

        $this->newLine();
        if ($count > 0) {
            $level === 'warn' ? $this->warn("[PERLU CEK] {$title}: {$count}") : $this->error("[MASALAH] {$title}: {$count}");
        } else {
            $this->info("[OK] {$title}");
        }

        if ($message && ($count > 0 || str_starts_with($message, 'Tabel '))) {
            $this->line($message);
        }

        $rows = $check['rows'] ?? collect();
        if ($rows instanceof Collection && $rows->isNotEmpty()) {
            $this->table($check['headers'] ?? [], $rows->map(fn ($row) => (array) $row)->all());
        }

        return $count > 0 && $level !== 'warn';
    }

    private function checkNegativeStock(): array
    {
        if (! Schema::hasTable('inventory_stocks')) {
            return $this->skipped('Stok minus', 'Tabel inventory_stocks belum ada.');
        }

        $query = DB::table('inventory_stocks as s')
            ->leftJoin('items as i', 'i.id', '=', 's.item_id')
            ->leftJoin('warehouses as w', 'w.id', '=', 's.warehouse_id')
            ->where('s.qty', '<', 0);

        return [
            'title' => 'Stok minus',
            'level' => 'error',
            'count' => (clone $query)->count(),
            'message' => 'Stok minus perlu ditelusuri dokumen penyebabnya.',
            'headers' => ['Gudang', 'Kode', 'Nama', 'Qty'],
            'rows' => (clone $query)
                ->orderBy('s.qty')
                ->limit($this->limit())
                ->get([
                    DB::raw("COALESCE(w.code, '-') as gudang"),
                    DB::raw("COALESCE(i.code, '-') as kode"),
                    DB::raw("COALESCE(i.name, '-') as nama"),
                    DB::raw('ROUND(s.qty, 4) as qty'),
                ]),
        ];
    }

    private function checkNegativeLots(): array
    {
        if (! Schema::hasTable('lots')) {
            return $this->skipped('Lot minus', 'Tabel lots belum ada.');
        }

        $query = DB::table('lots as l')
            ->leftJoin('items as i', 'i.id', '=', 'l.item_id')
            ->where('l.qty_onhand', '<', 0);

        return [
            'title' => 'Lot minus',
            'level' => 'error',
            'count' => (clone $query)->count(),
            'message' => 'Lot minus biasanya dari stok keluar melebihi lot tersedia.',
            'headers' => ['Lot', 'Kode', 'Qty Lot', 'Status'],
            'rows' => (clone $query)
                ->orderBy('l.qty_onhand')
                ->limit($this->limit())
                ->get([
                    DB::raw("COALESCE(l.code, '-') as lot"),
                    DB::raw("COALESCE(i.code, '-') as kode"),
                    DB::raw('ROUND(l.qty_onhand, 4) as qty_lot'),
                    DB::raw("COALESCE(l.status, '-') as status"),
                ]),
        ];
    }

    private function checkUnbalancedJournals(): array
    {
        if (! Schema::hasTable('journals') || ! Schema::hasTable('journal_lines')) {
            return $this->skipped('Jurnal tidak balance', 'Tabel jurnal belum lengkap.');
        }

        $query = DB::table('journals as j')
            ->join('journal_lines as l', 'l.journal_id', '=', 'j.id')
            ->whereNull('j.voided_at')
            ->groupBy('j.id', 'j.date', 'j.description')
            ->havingRaw('ABS(SUM(l.debit) - SUM(l.credit)) > 0.01')
            ->selectRaw('j.id, j.date, j.description, ROUND(SUM(l.debit), 2) as debit, ROUND(SUM(l.credit), 2) as credit, ROUND(SUM(l.debit) - SUM(l.credit), 2) as selisih');

        return [
            'title' => 'Jurnal tidak balance',
            'level' => 'error',
            'count' => DB::query()->fromSub(clone $query, 'x')->count(),
            'message' => 'Jurnal aktif harus selalu debit = kredit.',
            'headers' => ['ID', 'Tanggal', 'Deskripsi', 'Debit', 'Credit', 'Selisih'],
            'rows' => (clone $query)->orderByDesc('j.date')->limit($this->limit())->get(),
        ];
    }

    private function checkOldDraftDocuments(): array
    {
        $cutoff = Carbon::today()->subDays($this->days())->toDateString();
        $rows = collect();

        foreach ([
            ['purchase_requests', 'PR draft lama', 'code', 'date', fn ($q) => $q->where('status', 'draft')],
            ['purchase_orders', 'PO draft lama', 'code', 'date', fn ($q) => $q->where('status', 'draft')],
            ['cash_expenses', 'Kas keluar draft lama', 'description', 'date', fn ($q) => $q->where('status', 'draft')],
        ] as [$table, $label, $codeColumn, $dateColumn, $scope]) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'status') || ! Schema::hasColumn($table, $dateColumn)) {
                continue;
            }

            $query = DB::table($table)->whereDate($dateColumn, '<=', $cutoff);
            $scope($query);

            $query->limit($this->limit())->get(['id', $codeColumn . ' as kode', $dateColumn . ' as tanggal'])
                ->each(fn ($row) => $rows->push([
                    'dokumen' => $label,
                    'id' => $row->id,
                    'kode' => $row->kode,
                    'tanggal' => $row->tanggal,
                ]));
        }

        return [
            'title' => 'Dokumen draft lama',
            'level' => 'warn',
            'count' => $rows->count(),
            'message' => "Batas pantau: lebih dari {$this->days()} hari.",
            'headers' => ['Dokumen', 'ID', 'Kode', 'Tanggal'],
            'rows' => $rows->take($this->limit()),
        ];
    }

    private function checkOpenSewingPickups(): array
    {
        if (! Schema::hasTable('sewing_pickup_lines')) {
            return $this->skipped('Ambil jahit belum selesai', 'Tabel sewing_pickup_lines belum ada.');
        }

        $query = DB::table('sewing_pickup_lines as l')
            ->leftJoin('items as i', 'i.id', '=', 'l.finished_item_id')
            ->leftJoin('sewing_pickups as p', 'p.id', '=', 'l.sewing_pickup_id')
            ->whereNull('l.voided_at')
            ->whereRaw('(COALESCE(l.qty_bundle, 0) - COALESCE(l.qty_returned_ok, 0) - COALESCE(l.qty_returned_reject, 0)) > 0.0001')
            ->selectRaw("
                l.id,
                COALESCE(p.date, '-') as tanggal,
                COALESCE(i.code, '-') as kode,
                ROUND(COALESCE(l.qty_bundle, 0), 2) as ambil,
                ROUND(COALESCE(l.qty_returned_ok, 0) + COALESCE(l.qty_returned_reject, 0), 2) as setor,
                ROUND(COALESCE(l.qty_bundle, 0) - COALESCE(l.qty_returned_ok, 0) - COALESCE(l.qty_returned_reject, 0), 2) as sisa
            ");

        return [
            'title' => 'Ambil jahit belum selesai',
            'level' => 'warn',
            'count' => DB::query()->fromSub(clone $query, 'x')->count(),
            'message' => 'Ini boleh ada, tapi perlu dipantau agar bundle tidak hilang di lapangan.',
            'headers' => ['Line', 'Tanggal', 'Kode', 'Ambil', 'Setor', 'Sisa'],
            'rows' => (clone $query)->orderByDesc('sisa')->limit($this->limit())->get(),
        ];
    }

    private function checkOpenCuttingBundles(): array
    {
        if (! Schema::hasTable('cutting_job_bundles')) {
            return $this->skipped('Bundle cutting masih WIP', 'Tabel cutting_job_bundles belum ada.');
        }

        $query = DB::table('cutting_job_bundles as b')
            ->leftJoin('items as i', 'i.id', '=', 'b.finished_item_id')
            ->whereRaw('COALESCE(b.cut_wip_qty, b.wip_qty, 0) > 0.0001')
            ->selectRaw("
                b.id,
                COALESCE(b.bundle_code, '-') as bundle,
                COALESCE(i.code, '-') as kode,
                COALESCE(b.status, '-') as status,
                ROUND(COALESCE(b.cut_wip_qty, b.wip_qty, 0), 2) as qty_wip
            ");

        return [
            'title' => 'Bundle cutting masih WIP',
            'level' => 'warn',
            'count' => DB::query()->fromSub(clone $query, 'x')->count(),
            'message' => 'Ini normal kalau memang belum diambil jahit, tapi bagus dicek harian.',
            'headers' => ['ID', 'Bundle', 'Kode', 'Status', 'Qty WIP'],
            'rows' => (clone $query)->orderByDesc('qty_wip')->limit($this->limit())->get(),
        ];
    }

    private function skipped(string $title, string $message): array
    {
        return [
            'title' => $title,
            'level' => 'ok',
            'count' => 0,
            'message' => $message,
            'rows' => collect(),
        ];
    }

    private function days(): int
    {
        return max(1, (int) $this->option('days'));
    }

    private function limit(): int
    {
        return max(1, (int) $this->option('limit'));
    }
}
