<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\SewingReturn;
use App\Models\InventoryStock;
use App\Models\Journal;
use Illuminate\Support\Facades\DB;

class RevertPostedSewingReturnsToPendingQc extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'production:revert-reject-returns {id?} {--all}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Revert posted Sewing Returns (reject rework) to pending_qc and rollback their premature mutations.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $id = $this->argument('id');
        $all = $this->option('all');

        if (!$id && !$all) {
            $this->error('Please provide an ID or use --all to process all relevant records.');
            return;
        }

        $query = SewingReturn::query()
            ->where('status', 'posted')
            ->whereHas('lines', function($q) {
                $q->whereNotNull('source_reject_return_line_id');
            });

        if ($id) {
            $query->where('id', $id);
        }

        $returns = $query->get();

        if ($returns->isEmpty()) {
            $this->info('No matching posted Sewing Returns found.');
            return;
        }

        $this->info("Found {$returns->count()} returns to process.");

        foreach ($returns as $return) {
            DB::transaction(function () use ($return) {
                // 1. Rollback inventory_mutations
                $mutations = DB::table('inventory_mutations')
                    ->where('source_id', $return->id)
                    ->whereIn('source_type', [
                        'sewing_reject_rework_ok',
                        'sewing_qc_out',
                        'sewing_qc_in',
                        'sewing_qc_reject',
                    ])
                    ->get();

                foreach ($mutations as $m) {
                    $stock = InventoryStock::firstOrCreate([
                        'warehouse_id' => $m->warehouse_id,
                        'item_id' => $m->item_id,
                    ]);
                    
                    // qty_change can be positive (in) or negative (out).
                    // Subtraction naturally reverses it.
                    $stock->qty -= $m->qty_change;
                    $stock->save();
                    
                    DB::table('inventory_mutations')->where('id', $m->id)->delete();
                }

                $this->info("Reverted {$mutations->count()} mutations for Return #{$return->id}.");

                // 2. Rollback QC Results
                $qcDeleted = DB::table('qc_results')
                    ->where('stage', 'sewing')
                    ->where('sewing_job_id', $return->id)
                    ->delete();
                    
                if ($qcDeleted > 0) {
                    $this->info("Deleted {$qcDeleted} QC results for Return #{$return->id}.");
                }

                // 2. Delete Journals
                $journals = Journal::where('source_id', $return->id)
                    ->whereIn('source_type', [
                        'sewing_reject_rework_ok',
                        'sewing_return_ok',
                        'sewing_return_reject'
                    ])
                    ->get();

                foreach ($journals as $journal) {
                    $journal->delete();
                }

                $this->info("Deleted {$journals->count()} journals for Return #{$return->id}.");

                // 3. Reset Status
                $return->status = 'pending_qc';
                $return->save();

                $this->info("Return #{$return->id} reset to pending_qc.\n");
            });
        }

        $this->info('All done!');
    }
}
