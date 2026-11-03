<?php

namespace App\Console\Commands;

use App\Http\Controllers\Production\FinishingJobController;
use App\Models\CuttingJobBundle;
use App\Models\Employee;
use App\Models\FinishingJob;
use App\Models\FinishingJobLine;
use App\Models\InventoryMutation;
use App\Models\Item;
use App\Models\ItemBom;
use App\Models\ItemBomLine;
use App\Models\Journal;
use App\Models\StandaloneAccountingJournal;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\Accounting\StandaloneProductionPostingService;
use App\Services\Inventory\InventoryService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SeedFinishingPackingPilotDummy extends Command
{
    private const CODE = 'FIN-PACK-PILOT-20261031';

    private const PACKING_ITEM_CODE = 'PACK-PILOT-20261031';

    protected $signature = 'production:seed-finishing-packing-dummy
        {--json : Cetak hasil dalam JSON}
        {--missing-cost : Hanya siapkan fixture cost kosong untuk menguji blocking posting}
        {--with-reservation : Buat reservation Sewing Return sementara untuk uji VOID}';

    protected $description = 'Buat dan posting fixture Finishing dengan Auto BOM Packing untuk audit lokal.';

    public function handle(
        InventoryService $inventory,
        StandaloneProductionPostingService $standalone,
    ): int {
        if (app()->environment('production')) {
            return $this->report([
                'status' => 'BLOCKED',
                'reason' => 'Command dummy hanya boleh dijalankan di local/testing.',
            ], self::FAILURE);
        }

        $existing = FinishingJob::query()->where('code', self::CODE)->first();
        if ($existing) {
            return $this->report([
                'status' => 'EXISTS',
                'job' => [
                    'id' => (int) $existing->id,
                    'code' => $existing->code,
                    'status' => $existing->status,
                ],
                'message' => 'Dummy Auto BOM Packing sudah ada; tidak ada data baru dibuat.',
            ]);
        }

        $bundle = CuttingJobBundle::query()
            ->whereNotNull('finished_item_id')
            ->with('finishedItem')
            ->orderByDesc('wip_qty')
            ->orderBy('id')
            ->first();

        $withReservation = (bool) $this->option('with-reservation');
        $warehouseCodes = ['RM', 'WIP-FIN', 'WH-PRD', 'REJ-FIN', 'REJ-SEW'];
        if ($withReservation) {
            $warehouseCodes[] = 'WIP-SEW';
        }
        $warehouses = Warehouse::query()
            ->whereIn('code', $warehouseCodes)
            ->get()
            ->keyBy('code');

        $operatorId = Employee::query()
            ->whereIn('role', ['sewing', 'operating'])
            ->orderBy('id')
            ->value('id');
        $user = User::query()->orderBy('id')->first();

        if (! $bundle || ! $user || ! $operatorId || $warehouses->count() !== count($warehouseCodes)) {
            return $this->report([
                'status' => 'BLOCKED',
                'reason' => 'Butuh bundle finished item, user, operator, dan gudang '.implode('/', $warehouseCodes).'.',
            ], self::FAILURE);
        }

        if (config('accounting.standalone_dual_posting_production_finishing_enabled', false)) {
            $standalone->assertFinishingMappings();
        }

        $missingCost = (bool) $this->option('missing-cost');
        $date = now()->toDateString();
        $packingQtyPerPcs = 1.0;
        $packingUnitCost = $missingCost ? 0.0 : 2500.0;

        $result = DB::transaction(function () use (
            $bundle,
            $warehouses,
            $operatorId,
            $user,
            $date,
            $packingQtyPerPcs,
            $packingUnitCost,
            $missingCost,
            $withReservation,
            $inventory,
        ): array {
            $packingItem = Item::query()->firstOrCreate(
                ['code' => self::PACKING_ITEM_CODE],
                [
                    'name' => 'Packing supply pilot (dummy lokal)',
                    'unit' => 'pcs',
                    'type' => 'material',
                    'item_role' => 'production_supply',
                    'is_stocked' => true,
                    'active' => true,
                    'last_purchase_price' => $packingUnitCost,
                    'hpp' => $packingUnitCost,
                    'base_unit_cost' => $packingUnitCost,
                ],
            );

            $bom = ItemBom::query()->firstOrCreate(
                ['item_id' => $bundle->finished_item_id],
                [
                    'name' => 'BOM '.$bundle->finishedItem?->code.' (existing + pilot packing)',
                    'active' => true,
                ],
            );
            $bom->update(['active' => true]);

            $bomLine = ItemBomLine::query()->firstOrCreate(
                [
                    'item_bom_id' => $bom->id,
                    'material_item_id' => $packingItem->id,
                    'usage_stage' => ItemBomLine::STAGE_PACKING_SUPPLY,
                ],
                [
                    'qty' => $packingQtyPerPcs,
                    'uom' => 'pcs',
                    'scrap_pct' => 0,
                    'is_optional' => false,
                    'sort_order' => 90,
                ],
            );

            $job = FinishingJob::create([
                'code' => self::CODE,
                'date' => $date,
                'status' => 'draft',
                'notes' => 'Dummy Auto BOM Packing; lokal saja, jangan dipakai untuk transaksi riil.',
                'destination_warehouse_id' => $warehouses['WH-PRD']->id,
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ]);

            $line = FinishingJobLine::create([
                'finishing_job_id' => $job->id,
                'bundle_id' => $bundle->id,
                'operator_id' => $operatorId,
                'item_id' => $bundle->finished_item_id,
                'qty_in' => 1,
                'qty_ok' => 1,
                'qty_reject' => 0,
                'reject_cause' => 'finishing',
                'processed_at' => $date,
            ]);

            if ($withReservation) {
                // Reservation sewing minimal agar VOID dapat menguji release
                // kapasitas seperti transaksi UI nyata.
                $pickupId = DB::table('sewing_pickups')->insertGetId([
                    'code' => 'SWP-PACK-PILOT-20261031',
                    'date' => $date,
                    'warehouse_id' => $warehouses['WIP-SEW']->id,
                    'operator_id' => $operatorId,
                    'status' => 'posted',
                    'notes' => 'Reservation dummy untuk pilot Auto BOM Packing.',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $pickupLineId = DB::table('sewing_pickup_lines')->insertGetId([
                    'sewing_pickup_id' => $pickupId,
                    'cutting_job_bundle_id' => $bundle->id,
                    'finished_item_id' => $bundle->finished_item_id,
                    'qty_bundle' => 1,
                    'qty_returned_ok' => 1,
                    'qty_returned_reject' => 0,
                    'status' => 'done',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $returnId = DB::table('sewing_returns')->insertGetId([
                    'code' => 'SRT-PACK-PILOT-20261031',
                    'date' => $date,
                    'warehouse_id' => $warehouses['WIP-SEW']->id,
                    'operator_id' => $operatorId,
                    'status' => 'posted',
                    'notes' => 'Reservation dummy untuk pilot Auto BOM Packing.',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                DB::table('sewing_return_lines')->insert([
                    'sewing_return_id' => $returnId,
                    'sewing_pickup_line_id' => $pickupLineId,
                    'qty_ok' => 1,
                    'qty_reject' => 0,
                    'finished_qty' => 1,
                    'notes' => 'Dipakai oleh dummy Finishing Packing pilot.',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // Seed cost hanya untuk fixture normal. Mode --missing-cost sengaja
            // tidak membuat mutasi RM agar blocking HPP dapat diuji.
            if (! $missingCost) {
                $inventory->stockIn(
                    warehouseId: (int) $warehouses['RM']->id,
                    itemId: (int) $packingItem->id,
                    qty: 100,
                    date: $date,
                    sourceType: 'finishing_packing_pilot_seed',
                    sourceId: (int) $job->id,
                    notes: 'Seed RM untuk Auto BOM Packing pilot lokal',
                    lotId: null,
                    unitCost: $packingUnitCost,
                    affectLotCost: false,
                );
            }

            // Seed WIP-FIN agar posting job memakai jalur controller yang sama
            // dengan UI, bukan membuat jurnal/mutasi manual.
            $inventory->stockIn(
                warehouseId: (int) $warehouses['WIP-FIN']->id,
                itemId: (int) $bundle->finished_item_id,
                qty: 1,
                date: $date,
                sourceType: 'finishing_packing_pilot_wip_seed',
                sourceId: (int) $job->id,
                notes: 'Seed WIP-FIN untuk Auto BOM Packing pilot lokal',
                lotId: null,
                unitCost: 100000,
                affectLotCost: false,
                cuttingJobBundleId: (int) $bundle->id,
            );

            return [
                'job' => $job->fresh(['lines']),
                'line' => $line->fresh(),
                'bom' => $bom->fresh(['lines.material']),
                'packing_item' => $packingItem->fresh(),
                'packing_line' => $bomLine->fresh(),
                'missing_cost' => $missingCost,
            ];
        });

        Auth::loginUsingId((int) $user->id);

        try {
            // Public controller method dipakai agar command menguji workflow UI
            // lengkap: BOM, inventory, legacy journal, dan mirror baru.
            app(FinishingJobController::class)->post($result['job']->fresh());
        } catch (\Throwable $e) {
            $result['post_error'] = $e->getMessage();
        }

        $job = FinishingJob::query()->with('lines')->findOrFail($result['job']->id);
        $lineId = (int) $result['line']->id;
        $result['job'] = [
            'id' => (int) $job->id,
            'code' => $job->code,
            'status' => $job->status,
        ];
        $result['line'] = [
            'id' => $lineId,
            'qty_ok' => (float) $job->lines->firstWhere('id', $lineId)?->qty_ok,
            'bom_applied_at' => (string) ($job->lines->firstWhere('id', $lineId)?->bom_applied_at ?? ''),
            'bom_has_gaps' => (bool) ($job->lines->firstWhere('id', $lineId)?->bom_has_gaps ?? false),
        ];
        $result['packing_mutations'] = InventoryMutation::query()
            ->where('source_type', 'finishing_bom')
            ->where('source_id', $lineId)
            ->get(['id', 'warehouse_id', 'item_id', 'qty_change', 'direction', 'unit_cost', 'total_cost'])
            ->toArray();
        $result['legacy_journals'] = Journal::query()
            ->where('source_id', $job->id)
            ->whereIn('source_type', ['finishing_job', 'finishing_bom'])
            ->whereNull('voided_at')
            ->count();
        $result['standalone_journals'] = StandaloneAccountingJournal::query()
            ->where('source_id', $job->id)
            ->whereIn('source_type', ['finishing_job', 'finishing_bom'])
            ->whereNull('voided_at')
            ->count();
        $result['feature_flag'] = (bool) config('accounting.standalone_dual_posting_production_finishing_enabled', false);
        $result['message'] = $missingCost
            ? 'Fixture cost kosong dibuat untuk menguji blocking HPP; job seharusnya tetap DRAFT dan tidak membuat mutasi finishing_bom.'
            : 'Fixture Auto BOM Packing dibuat dan diposting melalui controller Finishing.';

        return $this->report($result, isset($result['post_error']) && $job->status !== 'posted' ? self::FAILURE : self::SUCCESS);
    }

    private function report(array $payload, int $exitCode = self::SUCCESS): int
    {
        if ($this->option('json')) {
            $this->line(json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        } else {
            $this->table(['Key', 'Value'], collect($payload)->map(fn ($value, $key) => [$key, is_scalar($value) || $value === null ? (string) $value : json_encode($value, JSON_UNESCAPED_UNICODE)])->all());
        }

        return $exitCode;
    }
}
