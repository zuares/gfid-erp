<?php

namespace App\Services\Production;

use App\Models\BundleAssembly;
use App\Models\Item;
use App\Services\Inventory\InventoryService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class BundleAssemblyService
{
    public function __construct(
        private ItemBomCostService $bomCost,
        private InventoryService $inventory,
    ) {}

    /**
     * Create an immutable BOM snapshot in draft status.
     * No inventory mutation is created until post() is called.
     */
    public function createDraft(
        Item $item,
        int $warehouseId,
        float|int|string $qty,
        string|\DateTimeInterface|null $date = null,
        ?string $notes = null,
        ?int $createdBy = null,
    ): BundleAssembly {
        $preview = $this->preview($item, $qty);
        $assemblyDate = $date ? Carbon::parse($date)->toDateString() : now()->toDateString();

        return DB::transaction(function () use ($preview, $warehouseId, $assemblyDate, $notes, $createdBy): BundleAssembly {
            $assembly = BundleAssembly::create([
                'code' => $this->nextCode($assemblyDate),
                'date' => $assemblyDate,
                'item_id' => $preview['item_id'],
                'warehouse_id' => $warehouseId,
                'qty' => $preview['assembly_qty'],
                'unit_cost' => $preview['unit_cost'],
                'total_cost' => $preview['total_cost'],
                'status' => BundleAssembly::STATUS_DRAFT,
                'created_by' => $createdBy ?? auth()->id(),
                'notes' => $notes,
            ]);

            foreach ($preview['components'] as $sortOrder => $component) {
                $assembly->lines()->create([
                    'material_item_id' => $component['material_item_id'],
                    'qty_per_unit' => $component['qty_per_unit'],
                    'scrap_pct' => $component['scrap_pct'],
                    'qty_required' => $component['qty_required'],
                    'uom' => $component['uom'],
                    'unit_cost' => $component['unit_cost'],
                    'total_cost' => $component['total_cost'],
                    'sort_order' => $sortOrder,
                ]);
            }

            return $assembly->load(['item', 'warehouse', 'lines.material']);
        });
    }

    /**
     * Post the snapshot: consume required components, then receive the bundle.
     * Every ledger write points back to the assembly and its line.
     */
    public function post(BundleAssembly $assembly): BundleAssembly
    {
        return DB::transaction(function () use ($assembly): BundleAssembly {
            $assembly = BundleAssembly::query()
                ->with(['lines.material'])
                ->lockForUpdate()
                ->findOrFail($assembly->id);

            if (! $assembly->isDraft()) {
                throw ValidationException::withMessages([
                    'assembly' => 'Assembly hanya dapat diposting dari status draft.',
                ]);
            }

            if ($assembly->lines->isEmpty()) {
                throw ValidationException::withMessages([
                    'assembly' => 'Assembly belum memiliki komponen material.',
                ]);
            }

            $totalCost = 0.0;

            foreach ($assembly->lines as $line) {
                if ((float) $line->qty_required <= 0 || ! $line->material) {
                    throw ValidationException::withMessages([
                        'assembly' => 'Snapshot komponen assembly tidak valid.',
                    ]);
                }

                $mutation = $this->inventory->stockOut(
                    warehouseId: (int) $assembly->warehouse_id,
                    itemId: (int) $line->material_item_id,
                    qty: (float) $line->qty_required,
                    date: $assembly->date,
                    sourceType: 'bundle_assembly',
                    sourceId: (int) $assembly->id,
                    notes: 'Konsumsi assembly '.$assembly->code,
                    allowNegative: false,
                    lotId: null,
                    unitCostOverride: null,
                    affectLotCost: false,
                    cuttingJobBundleId: null,
                    strictNonNegative: true,
                    sourceLineId: (int) $line->id,
                );

                $lineCost = $mutation?->total_cost !== null
                    ? abs((float) $mutation->total_cost)
                    : 0.0;
                $totalCost += $lineCost;

                $line->update([
                    'qty_consumed' => $line->qty_required,
                    'unit_cost' => $mutation?->unit_cost,
                    'total_cost' => $lineCost > 0 ? $lineCost : null,
                ]);
            }

            $assemblyUnitCost = (float) $assembly->qty > 0
                ? round($totalCost / (float) $assembly->qty, 6)
                : null;

            $receipt = $this->inventory->stockIn(
                warehouseId: (int) $assembly->warehouse_id,
                itemId: (int) $assembly->item_id,
                qty: (float) $assembly->qty,
                date: $assembly->date,
                sourceType: 'bundle_assembly',
                sourceId: (int) $assembly->id,
                notes: 'Hasil assembly '.$assembly->code,
                lotId: null,
                unitCost: $assemblyUnitCost,
                affectLotCost: false,
                cuttingJobBundleId: null,
                sourceLineId: null,
            );

            if (! $receipt) {
                throw new \RuntimeException('Stok hasil assembly gagal dicatat.');
            }

            $assembly->update([
                'unit_cost' => $assemblyUnitCost,
                'total_cost' => $totalCost > 0 ? round($totalCost, 2) : null,
                'status' => BundleAssembly::STATUS_POSTED,
                'posted_at' => now(),
                'posted_by' => auth()->id(),
            ]);

            return $assembly->fresh(['item', 'warehouse', 'lines.material']);
        });
    }

    /**
     * Reverse a posted assembly by undoing the output first, then restoring
     * the component quantities using their posted cost snapshots.
     */
    public function void(BundleAssembly $assembly): BundleAssembly
    {
        return DB::transaction(function () use ($assembly): BundleAssembly {
            $assembly = BundleAssembly::query()
                ->with(['lines.material'])
                ->lockForUpdate()
                ->findOrFail($assembly->id);

            if (! $assembly->isPosted()) {
                throw ValidationException::withMessages([
                    'assembly' => 'Hanya assembly yang sudah diposting yang dapat dibatalkan.',
                ]);
            }

            $this->inventory->stockOut(
                warehouseId: (int) $assembly->warehouse_id,
                itemId: (int) $assembly->item_id,
                qty: (float) $assembly->qty,
                date: $assembly->date,
                sourceType: 'bundle_assembly_void',
                sourceId: (int) $assembly->id,
                notes: 'Batal hasil assembly '.$assembly->code,
                allowNegative: false,
                lotId: null,
                unitCostOverride: $assembly->unit_cost,
                affectLotCost: false,
                cuttingJobBundleId: null,
                strictNonNegative: true,
                sourceLineId: null,
            );

            foreach ($assembly->lines as $line) {
                $this->inventory->stockIn(
                    warehouseId: (int) $assembly->warehouse_id,
                    itemId: (int) $line->material_item_id,
                    qty: (float) $line->qty_consumed,
                    date: $assembly->date,
                    sourceType: 'bundle_assembly_void',
                    sourceId: (int) $assembly->id,
                    notes: 'Batal konsumsi assembly '.$assembly->code,
                    lotId: null,
                    unitCost: $line->unit_cost,
                    affectLotCost: false,
                    cuttingJobBundleId: null,
                    sourceLineId: (int) $line->id,
                );
            }

            $assembly->update([
                'status' => BundleAssembly::STATUS_VOID,
                'voided_at' => now(),
                'voided_by' => auth()->id(),
            ]);

            return $assembly->fresh(['item', 'warehouse', 'lines.material']);
        });
    }

    /**
     * Build an assembly plan from the active BOM without writing inventory.
     * The returned component quantities are in each component's stock UOM.
     */
    public function preview(Item $item, float|int|string $qty): array
    {
        $assemblyQty = $this->normalizeQty($qty);

        if ($assemblyQty <= 0) {
            throw ValidationException::withMessages([
                'qty' => 'Qty assembly harus lebih dari 0.',
            ]);
        }

        if (! in_array($item->type, ['finished_good', 'wip'], true) || ! $item->canMake()) {
            throw ValidationException::withMessages([
                'item_id' => 'Item assembly harus berupa FG/WIP yang bisa diproduksi sendiri.',
            ]);
        }

        $bom = $item->activeBom()
            ->with(['item', 'lines.material'])
            ->first();

        if (! $bom) {
            throw ValidationException::withMessages([
                'item_id' => 'Item belum memiliki BOM aktif.',
            ]);
        }

        $this->validateComponents($bom->lines, (int) $item->id);
        $estimate = $this->bomCost->estimate($bom);
        $components = collect($estimate['components'])
            ->filter(fn (array $component): bool => ! $component['is_optional'])
            ->map(function (array $component) use ($assemblyQty): array {
                $qtyRequired = round($component['qty_with_scrap'] * $assemblyQty, 8);
                $totalCost = round($qtyRequired * $component['unit_cost'], 2);

                return [
                    'material_item_id' => $component['material_item_id'],
                    'code' => $component['code'],
                    'name' => $component['name'],
                    'qty_per_unit' => $component['qty'],
                    'scrap_pct' => $component['scrap_pct'],
                    'qty_required' => $qtyRequired,
                    'uom' => $component['uom'],
                    'unit_cost' => $component['unit_cost'],
                    'total_cost' => $totalCost,
                ];
            })->values();

        if ($components->isEmpty()) {
            throw ValidationException::withMessages([
                'item_id' => 'BOM belum memiliki komponen wajib untuk assembly.',
            ]);
        }

        $totalCost = round((float) $components->sum('total_cost'), 2);

        return [
            'item_id' => (int) $item->id,
            'item_code' => (string) $item->code,
            'item_name' => (string) $item->name,
            'stock_unit' => $item->stockUnit(),
            'bom_id' => (int) $bom->id,
            'bom_name' => (string) ($bom->name ?: 'BOM '.$item->code),
            'assembly_qty' => $assemblyQty,
            'unit_cost' => $assemblyQty > 0 ? round($totalCost / $assemblyQty, 6) : 0.0,
            'total_cost' => $totalCost,
            'components' => $components->all(),
        ];
    }

    private function validateComponents(Collection $lines, int $parentItemId): void
    {
        $invalid = $lines->first(function ($line) use ($parentItemId): bool {
            $material = $line->material;

            return (int) $line->material_item_id === $parentItemId
                || ! $material
                || $material->type !== 'material'
                || $material->usesExpenseAllocation()
                || $material->category?->kind === 'operational';
        });

        if ($invalid) {
            throw ValidationException::withMessages([
                'item_id' => 'BOM memiliki komponen yang tidak valid untuk assembly persediaan/HPP.',
            ]);
        }
    }

    private function normalizeQty(float|int|string $qty): float
    {
        if (is_string($qty)) {
            $qty = str_replace(',', '.', trim($qty));
        }

        return is_numeric($qty) ? (float) $qty : 0.0;
    }

    private function nextCode(string $date): string
    {
        do {
            $code = 'ASM-'.Carbon::parse($date)->format('Ymd').'-'.strtoupper(Str::random(6));
        } while (BundleAssembly::query()->where('code', $code)->exists());

        return $code;
    }
}
