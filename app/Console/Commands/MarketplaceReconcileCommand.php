<?php

namespace App\Console\Commands;

use App\Services\Marketplace\MarketplaceReconcileService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class MarketplaceReconcileCommand extends Command
{
    protected $signature = 'marketplace:reconcile
        {--date= : YYYY-MM-DD (default today)}
        {--channel= : shopee|tiktok (default ALL)}
        {--store_id= : store id (default ALL)}
        {--window=1 : window days +/- (default 1)}
        {--threshold=80 : auto match threshold (0-100)}
        {--dry-run : do not write mp_reconciliations}
        {--show=10 : show top N samples for matches & reviews (default 10)}';

    protected $description = 'Reconcile marketplace shipped packets (mp_shipments) to operational batch shipments by AWB (if exists) then SKU overlap allocation (batch-aware).';

    public function handle(MarketplaceReconcileService $svc): int
    {
        // =====================
        // Validate date
        // =====================
        $date = $this->option('date') ?: now()->toDateString();

        try {
            Carbon::createFromFormat('Y-m-d', $date);
        } catch (\Exception $e) {
            $this->error("Invalid --date format. Use YYYY-MM-DD.");
            return self::FAILURE;
        }

        // =====================
        // Validate numbers
        // =====================
        $window = (int) $this->option('window');
        $threshold = (int) $this->option('threshold');
        $show = (int) $this->option('show');

        if ($window < 0) {
            $this->error("--window must be >= 0");
            return self::FAILURE;
        }

        if ($threshold < 0 || $threshold > 100) {
            $this->error("--threshold must be between 0 and 100");
            return self::FAILURE;
        }

        if ($show < 0) {
            $this->error("--show must be >= 0");
            return self::FAILURE;
        }

        // =====================
        // Execute reconcile
        // =====================
        $res = $svc->reconcileByDate(
            dateYmd: $date,
            channel: $this->option('channel'),
            storeId: $this->option('store_id') ? (int) $this->option('store_id') : null,
            windowDays: $window,
            threshold: $threshold,
            dryRun: (bool) $this->option('dry-run'),
        );

        $stats = $res['stats'] ?? [];
        $matches = $res['matches'] ?? [];
        $reviews = $res['reviews'] ?? [];

        // =====================
        // Output summary
        // =====================
        $this->info("Marketplace Reconcile (Batch)");
        $this->line("Date       : " . ($stats['date'] ?? $date));
        $this->line("Window     : " . ($stats['window'] ?? '-'));
        $this->line("Channel    : " . ($this->option('channel') ?: 'ALL'));
        $this->line("Store      : " . ($this->option('store_id') ?: 'ALL'));
        $this->line("AWB Match  : " . (!empty($stats['awb_enabled']) ? 'ENABLED' : 'DISABLED'));
        $this->line("Threshold  : {$threshold}");
        $this->line(str_repeat('-', 56));
        $this->line("Scanned       : " . ($stats['scanned'] ?? 0));
        $this->line("Auto Matched  : " . ($stats['matched'] ?? count($matches)));
        $this->line("Need Review   : " . ($stats['needs_review'] ?? count($reviews)));
        $this->line("Skipped       : " . ($stats['skipped'] ?? 0));
        $this->line("Dry-run       : " . (!empty($stats['dry_run']) ? 'YES' : 'NO'));

        // =====================
        // Show samples
        // =====================
        if ($show > 0) {
            $this->line(str_repeat('-', 56));

            $this->info("Samples: Auto Matches (top {$show})");
            if (empty($matches)) {
                $this->line("- none");
            } else {
                foreach (array_slice($matches, 0, $show) as $m) {
                    $this->line(sprintf(
                        "- mp_shipment_id=%s -> shipment_id=%s | conf=%s | key=%s",
                        $m['mp_shipment_id'] ?? '-',
                        $m['shipment_id'] ?? '-',
                        $m['confidence'] ?? '-',
                        $m['match_key'] ?? '-',
                    ));
                }
            }

            $this->line('');

            $this->info("Samples: Needs Review (top {$show})");
            if (empty($reviews)) {
                $this->line("- none");
            } else {
                foreach (array_slice($reviews, 0, $show) as $r) {
                    $this->line(sprintf(
                        "- mp_shipment_id=%s -> suggested_shipment_id=%s | conf=%s | key=%s",
                        $r['mp_shipment_id'] ?? '-',
                        $r['shipment_id'] ?? '-',
                        $r['confidence'] ?? '-',
                        $r['match_key'] ?? '-',
                    ));
                }
            }
        }

        // Friendly hint
        if (!empty($stats['dry_run'])) {
            $this->line('');
            $this->comment("Tip: jalankan tanpa --dry-run untuk menyimpan ke mp_reconciliations.");
        }

        return self::SUCCESS;
    }
}
