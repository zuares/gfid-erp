use App\Models\CuttingJob;
use App\Models\InventoryMutation;
use App\Services\Production\CuttingService;

$jobs = CuttingJob::all();
$cuttingService = app(CuttingService::class);
$fixed = 0;

foreach ($jobs as $job) {
    $hasMutations = InventoryMutation::where('source_type', 'cutting_job')
        ->where('source_id', $job->id)
        ->where('direction', 'out')
        ->exists();

    if (!$hasMutations) {
        $totalUsed = (float) $job->bundles()->sum('qty_used_fabric');
        if ($totalUsed > 0) {
            echo "Fixing Cutting Job: {$job->code} (Missing mutations)\n";
            $cuttingService->reconsumeFabricFromLots($job);
            $fixed++;
        }
    }
}

echo "Total fixed: $fixed\n";
