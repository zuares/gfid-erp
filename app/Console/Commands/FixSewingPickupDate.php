<?php

namespace App\Console\Commands;

use App\Models\SewingPickup;
use App\Services\Accounting\JournalService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class FixSewingPickupDate extends Command
{
    protected $signature = 'production:fix-sewing-pickup-date
        {pickup : ID atau kode Ambil Jahit, contoh: 1 atau SWP-20260703-001}
        {date : Tanggal benar format YYYY-MM-DD}
        {--apply : Terapkan perubahan. Default hanya preview}
        {--force : Lewati konfirmasi saat apply}';

    protected $description = 'Koreksi tanggal Ambil Jahit beserta inventory_mutations dan journals terkait.';

    public function handle(): int
    {
        $pickupArg = trim((string) $this->argument('pickup'));
        $newDate = Carbon::parse((string) $this->argument('date'))->toDateString();
        $apply = (bool) $this->option('apply');

        $pickup = SewingPickup::query()
            ->with(['operator:id,code,name', 'lines:id,sewing_pickup_id'])
            ->when(
                ctype_digit($pickupArg),
                fn($q) => $q->whereKey((int) $pickupArg),
                fn($q) => $q->where('code', $pickupArg)
            )
            ->first();

        if (!$pickup) {
            $this->error("Ambil Jahit {$pickupArg} tidak ditemukan.");
            return self::FAILURE;
        }

        $oldDate = $pickup->date ? Carbon::parse($pickup->date)->toDateString() : null;
        $lineIds = $pickup->lines->pluck('id')->map(fn($id) => (int) $id)->values();

        $impact = [
            'sewing_pickups' => 1,
            'inventory_pickup' => $this->countInventoryMutations(SewingPickup::class, (int) $pickup->id),
            'inventory_supply' => $this->countInventoryMutations(JournalService::SRC_SEWING_PICKUP_SUPPLY, (int) $pickup->id),
            'journal_pickup' => $this->countJournals(JournalService::SRC_SEWING_PICKUP, (int) $pickup->id),
            'journal_supply' => $this->countJournals(JournalService::SRC_SEWING_PICKUP_SUPPLY, (int) $pickup->id),
            'journal_wage' => $lineIds->isEmpty()
                ? 0
                : DB::table('journals')
                    ->where('source_type', JournalService::SRC_SEWING_PICKUP_WAGE)
                    ->whereIn('source_id', $lineIds->all())
                    ->count(),
        ];

        $this->line($apply ? 'MODE APPLY' : 'MODE DRY-RUN');
        $this->newLine();
        $this->table(
            ['Field', 'Value'],
            [
                ['Pickup', "{$pickup->code} (#{$pickup->id})"],
                ['Operator', trim(($pickup->operator?->code ?? '-') . ' ' . ($pickup->operator?->name ?? ''))],
                ['Tanggal lama', $oldDate ?: '-'],
                ['Tanggal baru', $newDate],
                ['Line pickup', (string) $lineIds->count()],
            ]
        );

        $this->table(
            ['Target', 'Rows'],
            collect($impact)->map(fn($count, $label) => [$label, number_format((int) $count, 0, ',', '.')])->values()->all()
        );

        if ($oldDate === $newDate) {
            $this->info('Tanggal sudah sama. Tidak ada yang perlu diubah.');
            return self::SUCCESS;
        }

        if (!$apply) {
            $this->warn('Preview saja. Jalankan dengan --apply untuk menerapkan.');
            $this->line("php artisan production:fix-sewing-pickup-date {$pickup->code} {$newDate} --apply");
            return self::SUCCESS;
        }

        if (!$this->option('force') && !$this->confirm('Lanjut koreksi tanggal dokumen, mutasi, dan jurnal terkait?', false)) {
            $this->warn('Dibatalkan. Tidak ada data yang diubah.');
            return self::SUCCESS;
        }

        DB::transaction(function () use ($pickup, $newDate, $lineIds) {
            $timestamp = now();

            $pickup->forceFill([
                'date' => $newDate,
                'updated_at' => $timestamp,
            ])->save();

            $this->updateInventoryMutations(SewingPickup::class, (int) $pickup->id, $newDate, $timestamp);
            $this->updateInventoryMutations(JournalService::SRC_SEWING_PICKUP_SUPPLY, (int) $pickup->id, $newDate, $timestamp);

            $this->updateJournals(JournalService::SRC_SEWING_PICKUP, (int) $pickup->id, $newDate, $timestamp);
            $this->updateJournals(JournalService::SRC_SEWING_PICKUP_SUPPLY, (int) $pickup->id, $newDate, $timestamp);

            if ($lineIds->isNotEmpty()) {
                $query = DB::table('journals')
                    ->where('source_type', JournalService::SRC_SEWING_PICKUP_WAGE)
                    ->whereIn('source_id', $lineIds->all());

                $payload = ['date' => $newDate];
                if (Schema::hasColumn('journals', 'updated_at')) {
                    $payload['updated_at'] = $timestamp;
                }

                $query->update($payload);
            }
        });

        $this->info("Tanggal {$pickup->code} berhasil dikoreksi ke {$newDate}.");
        return self::SUCCESS;
    }

    private function countInventoryMutations(string $sourceType, int $sourceId): int
    {
        return DB::table('inventory_mutations')
            ->where('source_type', $sourceType)
            ->where('source_id', $sourceId)
            ->count();
    }

    private function countJournals(string $sourceType, int $sourceId): int
    {
        return DB::table('journals')
            ->where('source_type', $sourceType)
            ->where('source_id', $sourceId)
            ->count();
    }

    private function updateInventoryMutations(string $sourceType, int $sourceId, string $date, mixed $timestamp): void
    {
        $payload = ['date' => $date];
        if (Schema::hasColumn('inventory_mutations', 'updated_at')) {
            $payload['updated_at'] = $timestamp;
        }

        DB::table('inventory_mutations')
            ->where('source_type', $sourceType)
            ->where('source_id', $sourceId)
            ->update($payload);
    }

    private function updateJournals(string $sourceType, int $sourceId, string $date, mixed $timestamp): void
    {
        $payload = ['date' => $date];
        if (Schema::hasColumn('journals', 'updated_at')) {
            $payload['updated_at'] = $timestamp;
        }

        DB::table('journals')
            ->where('source_type', $sourceType)
            ->where('source_id', $sourceId)
            ->update($payload);
    }
}
