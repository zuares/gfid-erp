<?php

namespace App\Http\Controllers\Production;

use App\Helpers\CodeGenerator;
use App\Http\Controllers\Controller;
use App\Models\FinishingMaterialCorrection;
use App\Models\InventoryAdjustment;
use App\Models\Item;
use App\Models\ItemBom;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestLine;
use App\Models\Warehouse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class FinishingMaterialCorrectionController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'finishing_item_id' => ['required', 'integer', 'exists:items,id'],
            'material_item_id' => ['required', 'integer', 'exists:items,id'],
            'action' => ['required', Rule::in(['stock', 'bom', 'purchase'])],
            'qty' => ['nullable', 'numeric', 'min:0.001'],
            'reason' => ['required', 'string', 'min:3', 'max:2000'],
        ], [
            'reason.required' => 'Alasan koreksi wajib diisi.',
            'reason.min' => 'Alasan koreksi minimal 3 karakter.',
        ]);

        $finishingItem = Item::query()->findOrFail((int) $data['finishing_item_id']);
        $materialItem = Item::query()->findOrFail((int) $data['material_item_id']);
        $bom = ItemBom::query()
            ->where('item_id', $finishingItem->id)
            ->where('active', true)
            ->with(['lines' => fn ($query) => $query
                ->where('material_item_id', $materialItem->id)
                ->where('usage_stage', 'packing_supply')])
            ->first();

        abort_unless($bom && $bom->lines->isNotEmpty(), 422, 'Material tidak terkait dengan BOM packing item ini.');

        $qty = ($data['qty'] ?? null) !== null ? (float) $data['qty'] : null;
        if (in_array($data['action'], ['stock', 'purchase'], true) && ($qty === null || $qty <= 0)) {
            return back()->withErrors(['qty' => 'Qty koreksi wajib diisi untuk stok atau pembelian.'])->withInput();
        }

        $correction = DB::transaction(function () use ($request, $data, $finishingItem, $materialItem, $bom, $qty) {
            $correction = FinishingMaterialCorrection::create([
                'finishing_item_id' => $finishingItem->id,
                'material_item_id' => $materialItem->id,
                'action' => $data['action'],
                'qty' => $qty,
                'unit' => $materialItem->stockUnit(),
                'reason' => trim($data['reason']),
                'created_by' => (int) $request->user()->id,
                'status' => 'open',
            ]);

            if ($data['action'] === 'stock') {
                $warehouse = Warehouse::query()->where('code', 'RM')->firstOrFail();
                $adjustment = InventoryAdjustment::create([
                    'code' => CodeGenerator::make('ADJ', now()->toDateString()),
                    'date' => now()->toDateString(),
                    'warehouse_id' => $warehouse->id,
                    'reason' => 'Koreksi Material Finishing - Stok',
                    'notes' => $this->contextNote($finishingItem, $materialItem, $data['reason']),
                    'status' => InventoryAdjustment::STATUS_DRAFT,
                    'created_by' => (int) $request->user()->id,
                    'reference_type' => FinishingMaterialCorrection::class,
                    'reference_id' => $correction->id,
                ]);

                $adjustment->lines()->create([
                    'item_id' => $materialItem->id,
                    'qty_change' => $qty,
                    'direction' => 'in',
                    'notes' => trim($data['reason']),
                ]);

                $correction->update([
                    'reference_type' => InventoryAdjustment::class,
                    'reference_id' => $adjustment->id,
                ]);

                return [$correction, route('inventory.adjustments.show', $adjustment)];
            }

            if ($data['action'] === 'purchase') {
                $purchaseRequest = PurchaseRequest::create([
                    'code' => CodeGenerator::make('PR', now()->toDateString()),
                    'date' => now()->toDateString(),
                    'requested_by' => (int) $request->user()->id,
                    'status' => 'draft',
                    'notes' => $this->contextNote($finishingItem, $materialItem, $data['reason']),
                ]);

                $purchaseRequest->lines()->create([
                    'item_id' => $materialItem->id,
                    'qty' => $qty,
                    'purchase_unit' => $materialItem->purchaseUnit(),
                    'stock_unit' => $materialItem->stockUnit(),
                    'conversion_factor' => $materialItem->purchaseConversionFactor(),
                    'unit_price' => null,
                    'notes' => trim($data['reason']),
                ]);

                $correction->update([
                    'reference_type' => PurchaseRequest::class,
                    'reference_id' => $purchaseRequest->id,
                ]);

                return [$correction, route('purchasing.purchase_requests.show', $purchaseRequest)];
            }

            $correction->update([
                'reference_type' => ItemBom::class,
                'reference_id' => $bom->id,
            ]);

            return [$correction, route('master.item_boms.edit', ['bom' => $bom, 'correction_id' => $correction->id])];
        });

        return redirect($correction[1])
            ->with('success', 'Koreksi material dicatat. Alasan dan pembuat sudah tersimpan di audit trail.');
    }

    private function contextNote(Item $finishingItem, Item $materialItem, string $reason): string
    {
        return implode("\n", [
            'Sumber: Koreksi Material dari Finishing',
            'Item finishing: '.$finishingItem->code.' — '.$finishingItem->name,
            'Material: '.$materialItem->code.' — '.$materialItem->name,
            'Alasan: '.trim($reason),
        ]);
    }
}
