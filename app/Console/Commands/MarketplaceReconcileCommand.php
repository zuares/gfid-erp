<?php

namespace App\Console\Commands;

use App\Services\Marketplace\MarketplaceReconcileService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class MarketplaceReconcileCommand extends Command
{
    protected $signature = 'marketplace:reconcile
        {--date= : YYYY-MM-DD}
        {--channel= : shopee|tiktok}
        {--store_id= : store id}
        {--window=1 : window days (default 1)}
        {--threshold=80 : auto match threshold (0-100)}
        {--dry-run : do not write mp_reconciliations}';

    protected $description = 'Reconcile marketplace shipments to operational shipments by date/qty (and AWB if exists).';

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

        if ($window < 0) {
            $this->error("--window must be >= 0");
            return self::FAILURE;
        }

        if ($threshold < 0 || $threshold > 100) {
            $this->error("--threshold must be between 0 and 100");
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

        $stats = $res['stats'];

        // =====================
        // Output
        // =====================
        $this->info("Marketplace Reconcile");
        $this->line("Date       : {$stats['date']}");
        $this->line("Window     : {$stats['window']}");
        $this->line("Channel    : " . ($this->option('channel') ?: 'ALL'));
        $this->line("Store      : " . ($this->option('store_id') ?: 'ALL'));
        $this->line("AWB Match  : " . ($stats['awb_enabled'] ? 'ENABLED' : 'DISABLED'));
        $this->line(str_repeat('-', 40));
        $this->line("Scanned    : {$stats['scanned']}");
        $this->line("Matched    : {$stats['matched']}");
        $this->line("NeedReview : {$stats['needs_review']}");
        $this->line("Skipped    : {$stats['skipped']}");
        $this->line("Dry-run    : " . ($stats['dry_run'] ? 'YES' : 'NO'));

        return self::SUCCESS;
    }
}
