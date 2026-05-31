<?php

namespace App\Console\Commands;

use App\Models\CuttingJobBundle;
use App\Models\Warehouse;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * FASE 2 — SHADOW COMPARE (read-only, NOL mutasi).
 *
 * Membandingkan cache per-bundle (kolom di cutting_job_bundles) terhadap
 * saldo LEDGER per-bundle yang dihitung dari inventory_mutations yang sudah
 * ditandai cutting_job_bundle_id (hasil Fase 1 dual-write + Fase 0 backfill).
 *
 *   WIP-CUT : cache cut_wip_qty   vs  Σ qty_change ledger (tag bundle, di WIP-CUT)
 *   WIP-FIN : cache wip_qty       vs  Σ qty_change ledger (tag bundle, di WIP-FIN)
 *
 * Karena backfill historis tidak 100% (sebagian mutasi lama tak punya kode
 * bundle di notes), command ini juga melaporkan berapa banyak aktivitas ledger
 * di gudang ybs yang MASIH UNTAGGED (cutting_job_bundle_id NULL). Itu memisahkan
 * "selisih historis (untagged)" dari "selisih data baru (bug dual-write)".
 *
 * Target Fase 2: untuk aktivitas SETELAH cutover dual-write, selisih = 0.
 * Pakai --since=YYYY-MM-DD untuk membatasi perbandingan ke periode pasca-cutover.
 *
 * READ-ONLY. Tidak pernah menulis apa pun.
 */
class ShadowCompareBundleLedger extends Command
{
    protected $signature = 'inventory:shadow-compare
        {--since= : Hanya hitung mutasi pada/sesudah tanggal ini (YYYY-MM-DD) untuk fokus pasca-cutover}
        {--limit=40 : Maksimum baris detail per stage}';

    protected $description = 'FASE 2: bandingkan cache bundle vs saldo ledger per-bundle (read-only).';

    public function handle(): int
    {
        $eps = 0.0001;
        $since = $this->option('since');
        $limit = (int) $this->option('limit');

        $stages = [
            ['code' => 'WIP-CUT', 'cacheCol' => 'cut_wip_qty', 'whCol' => 'cut_wip_warehouse_id'],
            ['code' => 'WIP-FIN', 'cacheCol' => 'wip_qty',     'whCol' => 'wip_warehouse_id'],
        ];

        $this->info('>>> FASE 2 SHADOW COMPARE (read-only)');
        if ($since) {
            $this->line("Periode: mutasi pada/sesudah {$since}");
        } else {
            $this->line('Periode: seluruh ledger (termasuk historis untagged)');
        }
        $this->newLine();

        foreach ($stages as $stage) {
            $wh = Warehouse::where('code', $stage['code'])->first();
            if (!$wh) {
                $this->warn("Gudang {$stage['code']} tidak ditemukan, lewati.");
                continue;
            }

            // Saldo ledger per-bundle (hanya yang sudah ditandai).
            $ledgerQ = DB::table('inventory_mutations')
                ->where('warehouse_id', $wh->id)
                ->whereNotNull('cutting_job_bundle_id');
            if ($since) {
                $ledgerQ->whereDate('date', '>=', $since);
            }
            $ledgerByBundle = $ledgerQ
                ->selectRaw('cutting_job_bundle_id, SUM(qty_change) s')
                ->groupBy('cutting_job_bundle_id')
                ->pluck('s', 'cutting_job_bundle_id');

            // Aktivitas ledger yang masih untagged (untuk konteks selisih historis).
            $untaggedQ = DB::table('inventory_mutations')
                ->where('warehouse_id', $wh->id)
                ->whereNull('cutting_job_bundle_id');
            if ($since) {
                $untaggedQ->whereDate('date', '>=', $since);
            }
            $untaggedNet = (float) $untaggedQ->sum('qty_change');
            $untaggedRows = (int) (clone $untaggedQ)->count();

            // Cache per bundle di stage ini.
            $bundles = CuttingJobBundle::where($stage['whCol'], $wh->id)->get();

            $diffRows = [];
            $cacheSum = 0.0;
            $ledgerSum = 0.0;
            $nDiff = 0;
            $absDiff = 0.0;

            // Gabungkan: semua bundle yang punya cache di stage ATAU punya ledger tag.
            $allBundleIds = collect($bundles->pluck('id'))
                ->merge($ledgerByBundle->keys())
                ->unique();

            $cacheById = $bundles->keyBy('id');

            foreach ($allBundleIds as $bid) {
                $cache = (float) (optional($cacheById->get($bid))->{$stage['cacheCol']} ?? 0);
                $ledger = (float) ($ledgerByBundle[$bid] ?? 0);
                $cacheSum += $cache;
                $ledgerSum += $ledger;
                $d = round($cache - $ledger, 4);
                if (abs($d) > $eps) {
                    $nDiff++;
                    $absDiff += abs($d);
                    $b = $cacheById->get($bid);
                    $diffRows[] = [
                        $bid,
                        $b?->bundle_code ?? ('#' . $bid),
                        rtrim(rtrim(number_format($cache, 3), '0'), '.'),
                        rtrim(rtrim(number_format($ledger, 3), '0'), '.'),
                        ($d > 0 ? '+' : '') . rtrim(rtrim(number_format($d, 3), '0'), '.'),
                    ];
                }
            }

            $this->line("<fg=cyan>=== {$stage['code']} (cache {$stage['cacheCol']} vs ledger tag) ===</>");
            $this->line('  Σ cache  = ' . rtrim(rtrim(number_format($cacheSum, 3), '0'), '.')
                . '   Σ ledger(tagged) = ' . rtrim(rtrim(number_format($ledgerSum, 3), '0'), '.')
                . '   net = ' . rtrim(rtrim(number_format($cacheSum - $ledgerSum, 3), '0'), '.'));
            $this->line('  Bundle beda = ' . $nDiff . '   total |selisih| = '
                . rtrim(rtrim(number_format($absDiff, 3), '0'), '.'));
            $this->line('  Ledger UNTAGGED di gudang ini: ' . $untaggedRows . ' baris, net '
                . rtrim(rtrim(number_format($untaggedNet, 3), '0'), '.')
                . '  <fg=yellow>(selisih historis berasal dari sini, bukan bug dual-write)</>');

            if ($nDiff > 0) {
                $show = array_slice($diffRows, 0, $limit);
                $this->table(['Bundle', 'Kode', 'Cache', 'Ledger(tag)', 'Selisih'], $show);
                if ($nDiff > $limit) {
                    $this->line('  … ' . ($nDiff - $limit) . ' baris lagi (naikkan --limit untuk lihat semua).');
                }
            } else {
                $this->info('  ✅ Tidak ada selisih cache vs ledger-tagged di stage ini.');
            }
            $this->newLine();
        }

        $this->line('<fg=gray>Catatan: command ini READ-ONLY. Untuk fokus verifikasi dual-write,'
            . ' jalankan dengan --since=<tanggal cutover>.</>');

        return self::SUCCESS;
    }
}
