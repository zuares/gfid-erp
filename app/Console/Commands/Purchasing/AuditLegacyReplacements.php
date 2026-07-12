<?php

namespace App\Console\Commands\Purchasing;

use App\Models\PurchaseReturn;
use Illuminate\Console\Command;

class AuditLegacyReplacements extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'purchasing:audit-legacy-replacements';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Mendaftar dokumen Purchase Return legacy yang status replacement-nya sudah received/partial tapi tidak memiliki dokumen Replacement Receipt yang valid.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Memulai audit legacy replacements...');

        $legacyReturns = PurchaseReturn::query()
            ->where('resolution_type', 'replacement')
            ->whereIn('replacement_status', ['received', 'partial'])
            ->where('status', 'posted')
            ->whereNull('voided_at')
            ->doesntHave('replacementReceipts')
            ->get();

        if ($legacyReturns->isEmpty()) {
            $this->info('Tidak ada legacy replacement yang ditemukan. Sistem bersih.');
            return Command::SUCCESS;
        }

        $this->warn('Ditemukan ' . $legacyReturns->count() . ' dokumen Return dengan legacy replacement (tanpa GRN pengganti):');

        $headers = ['ID', 'Kode', 'Tanggal', 'Supplier', 'Status Rep'];
        $rows = $legacyReturns->map(function ($ret) {
            return [
                $ret->id,
                $ret->code,
                $ret->date ? $ret->date->format('Y-m-d') : '-',
                $ret->supplier?->name ?? '-',
                strtoupper($ret->replacement_status),
            ];
        })->toArray();

        $this->table($headers, $rows);

        $this->info('Tindakan: Jika item dari retur legacy di atas perlu diretur kembali, mohon informasikan tim IT untuk membuat dokumen dummy GRN atau penyesuaian manual.');

        return Command::SUCCESS;
    }
}
