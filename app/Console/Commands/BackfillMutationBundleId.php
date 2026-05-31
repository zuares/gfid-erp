<?php

namespace App\Console\Commands;

use App\Models\CuttingJobBundle;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * FASE 0 — Backfill inventory_mutations.cutting_job_bundle_id dari data lama.
 *
 * Hanya men-tag source_type yang DIPOSTING PER BUNDLE dan menyebut bundle_code
 * di kolom notes, jadi pemetaannya pasti (tidak menebak):
 *   - cutting_wip
 *   - cutting_reject
 *   - cutting_reject_correction
 *   - App\Models\SewingPickup
 *
 * source_type yang diposting AGREGAT per item (sewing_return_ok, FinishingJob,
 * sewing_qc_*) SENGAJA dilewati — itu butuh perubahan cara posting (split per
 * bundle) di Fase 1, baru bisa di-tag akurat. Di sini dibiarkan NULL.
 *
 * Ledger TIDAK diubah nilainya — hanya mengisi kolom tag. Nol dampak costing.
 *
 * DEFAULT = DRY-RUN. Tambahkan --apply untuk benar-benar menulis.
 */
class BackfillMutationBundleId extends Command
{
    protected $signature = 'inventory:backfill-mutation-bundle {--apply : Benar-benar tulis tag (default: dry-run)}';

    protected $description = 'Isi inventory_mutations.cutting_job_bundle_id dari notes (cutting_* & SewingPickup). Default dry-run.';

    /** source_type yang aman di-backfill (per-bundle, ada bundle_code di notes). */
    private array $derivableTypes = [
        'cutting_wip',
        'cutting_reject',
        'cutting_reject_correction',
        'cutting_qc_adjust_in',
        'cutting_qc_adjust_out',
        'App\\Models\\SewingPickup',
    ];

    /**
     * source_type yang notes-nya TIDAK menyebut bundle_code, tapi menyebut
     * "reverse mut#<id>" → tag-nya diwarisi dari mutasi asal yang di-reverse.
     */
    private array $reverseTypes = [
        'cutting_qc_void',
    ];

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');

        $this->info($apply ? '>>> MODE APPLY (akan menulis tag)' : '>>> MODE DRY-RUN (preview saja)');
        $this->line('Ledger tidak diubah nilainya — hanya mengisi cutting_job_bundle_id.');
        $this->newLine();

        // Peta bundle_code → id (sekali ambil, hemat query).
        $codeToId = CuttingJobBundle::query()
            ->whereNotNull('bundle_code')
            ->pluck('id', 'bundle_code'); // [bundle_code => id]

        $this->line('Total bundle dengan kode: ' . $codeToId->count());
        $this->newLine();

        $summary = []; // per type: total, already, matched, unmatched_nocode, unmatched_notfound
        $applyMap = []; // [mutation_id => bundle_id]

        foreach ($this->derivableTypes as $type) {
            $rows = DB::table('inventory_mutations')
                ->where('source_type', $type)
                ->select('id', 'notes', 'cutting_job_bundle_id')
                ->get();

            $s = ['total' => $rows->count(), 'already' => 0, 'matched' => 0, 'nocode' => 0, 'notfound' => 0];

            foreach ($rows as $r) {
                if ($r->cutting_job_bundle_id !== null) {
                    $s['already']++;
                    continue;
                }

                $code = $this->extractBundleCode($r->notes);
                if ($code === null) {
                    $s['nocode']++;
                    continue;
                }

                $bundleId = $codeToId[$code] ?? null;
                if ($bundleId === null) {
                    $s['notfound']++;
                    continue;
                }

                $s['matched']++;
                $applyMap[$r->id] = $bundleId;
            }

            $summary[$type] = $s;
        }

        // ===== PASS 2: inherit dari mutasi asal via "reverse mut#<id>" =====
        foreach ($this->reverseTypes as $type) {
            $rows = DB::table('inventory_mutations')
                ->where('source_type', $type)
                ->select('id', 'notes', 'cutting_job_bundle_id')
                ->get();

            $s = ['total' => $rows->count(), 'already' => 0, 'matched' => 0, 'nocode' => 0, 'notfound' => 0];

            foreach ($rows as $r) {
                if ($r->cutting_job_bundle_id !== null) {
                    $s['already']++;
                    continue;
                }

                $origId = $this->extractReverseId($r->notes);
                if ($origId === null) {
                    $s['nocode']++; // tidak ada "reverse mut#<id>"
                    continue;
                }

                // Tag asal: dari hasil pass-1 di run ini, atau nilai DB saat ini.
                $bundleId = $applyMap[$origId]
                    ?? DB::table('inventory_mutations')->where('id', $origId)->value('cutting_job_bundle_id');

                if ($bundleId === null) {
                    $s['notfound']++; // mutasi asal pun belum bertag
                    continue;
                }

                $s['matched']++;
                $applyMap[$r->id] = (int) $bundleId;
            }

            $summary[$type] = $s;
        }

        // Tabel ringkasan.
        $rows = [];
        $totalMatched = 0;
        foreach ($summary as $type => $s) {
            $totalMatched += $s['matched'];
            $rows[] = [
                $type,
                $s['total'],
                $s['already'],
                $s['matched'],
                $s['nocode'],
                $s['notfound'],
            ];
        }
        $this->table(
            ['source_type', 'Total', 'Sudah ada', 'Akan di-tag', 'Notes tanpa kode', 'Kode tak ketemu'],
            $rows
        );

        $this->newLine();
        $this->line('Total mutasi yang akan di-tag: <fg=yellow>' . $totalMatched . '</>');
        $this->newLine();

        if (!$apply) {
            $this->warn('Ini hanya PREVIEW. Untuk eksekusi:');
            $this->line('   php artisan inventory:backfill-mutation-bundle --apply');
            return self::SUCCESS;
        }

        // ===== APPLY (chunked update) =====
        $written = 0;
        foreach (array_chunk($applyMap, 500, true) as $chunk) {
            DB::transaction(function () use ($chunk, &$written) {
                foreach ($chunk as $mutId => $bundleId) {
                    DB::table('inventory_mutations')
                        ->where('id', $mutId)
                        ->update(['cutting_job_bundle_id' => $bundleId]);
                    $written++;
                }
            });
        }

        $this->info("✅ Selesai. {$written} mutasi di-tag dengan cutting_job_bundle_id.");
        return self::SUCCESS;
    }

    /**
     * Ambil bundle_code (mis. BND-20260105-005-001) dari notes.
     */
    private function extractBundleCode(?string $notes): ?string
    {
        if (!$notes) {
            return null;
        }
        // Format: BND-<tanggal>-<job>-<urut>, contoh BND-20260105-005-001
        if (preg_match('/BND-[0-9]{6,8}-[0-9]+-[0-9]+/', $notes, $m)) {
            return $m[0];
        }
        return null;
    }

    /**
     * Ambil id mutasi asal dari notes "reverse mut#<id>".
     */
    private function extractReverseId(?string $notes): ?int
    {
        if (!$notes) {
            return null;
        }
        if (preg_match('/reverse\s+mut#(\d+)/i', $notes, $m)) {
            return (int) $m[1];
        }
        return null;
    }
}
