<?php

namespace App\Services\Production;

use App\Helpers\CodeGenerator;
use App\Models\CuttingJob;
use App\Models\CuttingJobBundle;
use App\Models\Item;
use App\Models\Warehouse;
use App\Services\Inventory\InventoryService;
use Illuminate\Support\Facades\DB;

class CuttingService
{
    public function __construct(
        protected InventoryService $inventory,
    ) {}

    /**
     * Buat Cutting Job baru.
     */
    public function create(array $payload): CuttingJob
    {
        return DB::transaction(function () use ($payload) {
            $bundlesData = $payload['bundles'] ?? [];
            unset($payload['bundles']);

            // generate kode job kalau belum diisi
            if (empty($payload['code'] ?? null)) {
                $payload['code'] = CodeGenerator::generate('CUT');
            }

            /** @var CuttingJob $job */
            $job = CuttingJob::create([
                'code' => $payload['code'],
                'date' => $payload['date'],
                'warehouse_id' => $payload['warehouse_id'], // gudang RM / LOT
                'lot_id' => $payload['lot_id'],
                'fabric_item_id' => $payload['fabric_item_id'] ?? null,
                'notes' => $payload['notes'] ?? null,
                'status' => 'cut',
                'total_bundles' => 0,
                'total_qty_pcs' => 0,
                'operator_id' => $payload['operator_id'],
            ]);

            $operatorId = $payload['operator_id'] ?? null;

            // Ambil gudang WIP-CUT untuk di-injek ke bundle sebagai posisi WIP
            $wipCutWarehouseId = Warehouse::where('code', 'WIP-CUT')->value('id');

            // 🔹 PREFETCH: mapping item_id => item_category_id
            $itemCategoryMap = Item::whereIn(
                'id',
                collect($bundlesData)
                    ->pluck('finished_item_id')
                    ->filter()
                    ->unique()
            )
                ->pluck('item_category_id', 'id'); // [item_id => item_category_id]

            $running = 1;
            $totalBundles = 0;
            $totalQtyPcs = 0.0;

            foreach ($bundlesData as $row) {
                if (empty($row['finished_item_id'])) {
                    continue;
                }

                $qtyPcs = $this->num($row['qty_pcs'] ?? 0);
                if ($qtyPcs <= 0) {
                    continue;
                }

                $bundleNo = $row['bundle_no'] ?? $running;
                $qtyUsedFabric = $this->num($row['qty_used_fabric'] ?? 0);

                $finishedItemId = (int) $row['finished_item_id'];
                $itemCategoryId = $itemCategoryMap[$finishedItemId] ?? null;

                $cuttingJobBunlde = CuttingJobBundle::create([
                    'cutting_job_id' => $job->id,
                    'bundle_code' => $this->generateBundleCode($job, $bundleNo),
                    'bundle_no' => $bundleNo,
                    'lot_id' => $job->lot_id,
                    'finished_item_id' => $finishedItemId,
                    'item_category_id' => $itemCategoryId, // ⬅️ INI YANG PENTING
                    'qty_pcs' => $qtyPcs,
                    'qty_used_fabric' => $qtyUsedFabric,
                    'operator_id' => $operatorId,
                    'status' => 'cut',
                    'notes' => $row['notes'] ?? null,
                    'wip_warehouse_id' => null,
                    'wip_qty' => 0,
                ]);
                $running++;
                $totalBundles++;
                $totalQtyPcs += $qtyPcs;
            }

            // update summary di header
            $job->update([
                'total_bundles' => $totalBundles,
                'total_qty_pcs' => $totalQtyPcs,
            ]);

            // … (lanjutan mutasi stok tetap seperti punyamu)
            // jangan lupa hapus `dd($job);` di atas ya 😉

            return $job;
        });
    }

    /**
     * Update Cutting Job + bundles.
     */
    public function update(array $payload, CuttingJob $job): CuttingJob
    {
        return DB::transaction(function () use ($payload, $job) {
            $bundlesData = $payload['bundles'] ?? [];
            unset($payload['bundles']);

            // update header (tanggal, notes saja; lot & warehouse fix)
            $job->update([
                'date' => $payload['date'],
                'notes' => $payload['notes'] ?? null,
                'fabric_item_id' => $payload['fabric_item_id'] ?? null,
            ]);

            $operatorId = $payload['operator_id'] ?? null;
            $existingIds = $job->bundles()->pluck('id')->all();
            $keepIds = [];

            $running = 1;
            $totalBundles = 0;
            $totalQtyPcs = 0.0;

            foreach ($bundlesData as $row) {
                if (empty($row['finished_item_id'])) {
                    continue;
                }

                $qtyPcs = $this->num($row['qty_pcs'] ?? 0);
                if ($qtyPcs <= 0) {
                    continue;
                }

                $bundleNo = $row['bundle_no'] ?? $running;
                $qtyUsedFabric = $this->num($row['qty_used_fabric'] ?? 0);

                if (!empty($row['id']) && in_array($row['id'], $existingIds)) {
                    // UPDATE
                    $bundle = CuttingJobBundle::where('cutting_job_id', $job->id)
                        ->where('id', $row['id'])
                        ->first();

                    if ($bundle) {
                        $bundle->update([
                            'bundle_no' => $bundleNo,
                            'finished_item_id' => $row['finished_item_id'],
                            'qty_pcs' => $qtyPcs,
                            'qty_used_fabric' => $qtyUsedFabric,
                            'operator_id' => $operatorId,
                            'notes' => $row['notes'] ?? null,
                        ]);
                        $keepIds[] = $bundle->id;
                    }
                } else {
                    // INSERT baru
                    $bundle = CuttingJobBundle::create([
                        'cutting_job_id' => $job->id,
                        'bundle_code' => $this->generateBundleCode($job, $bundleNo),
                        'bundle_no' => $bundleNo,
                        'lot_id' => $job->lot_id,
                        'finished_item_id' => $row['finished_item_id'],
                        'qty_pcs' => $qtyPcs,
                        'qty_used_fabric' => $qtyUsedFabric,
                        'operator_id' => $operatorId,
                        'status' => 'cut',
                        'notes' => $row['notes'] ?? null,
                    ]);
                    $keepIds[] = $bundle->id;
                }

                $running++;
                $totalBundles++;
                $totalQtyPcs += $qtyPcs;
            }

            // hapus bundle yang tidak dikirim lagi
            if (!empty($existingIds)) {
                $deleteIds = array_diff($existingIds, $keepIds);
                if (!empty($deleteIds)) {
                    CuttingJobBundle::where('cutting_job_id', $job->id)
                        ->whereIn('id', $deleteIds)
                        ->delete();
                }
            }

            // update summary
            $job->update([
                'total_bundles' => $totalBundles,
                'total_qty_pcs' => $totalQtyPcs,
            ]);

            return $job->fresh(['bundles']);
        });
    }

    /**
     * Generate kode bundle.
     * Contoh: BND-20251125-001-001 (Tgl-JobId-BundleNo)
     */
    protected function generateBundleCode(CuttingJob $job, int $bundleNo): string
    {
        $datePart = $job->date?->format('Ymd') ?? now()->format('Ymd');
        $jobSeq = str_pad((string) $job->id, 3, '0', STR_PAD_LEFT);
        $bundleSeq = str_pad((string) $bundleNo, 3, '0', STR_PAD_LEFT);

        return "BND-{$datePart}-{$jobSeq}-{$bundleSeq}";
    }

    /**
     * Normalisasi angka.
     */
    protected function num(float | int | string | null $value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }

        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        $value = trim((string) $value);
        $value = str_replace(' ', '', $value);

        // format Indonesia 1.234,56
        if (strpos($value, ',') !== false) {
            $value = str_replace('.', '', $value);
            $value = str_replace(',', '.', $value);
        }

        return (float) $value;
    }
}
