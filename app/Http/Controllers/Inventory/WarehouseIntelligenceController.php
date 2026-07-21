<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Services\Inventory\InventoryIntelligenceService;
use App\Models\Item;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use App\Models\Warehouse;
use App\Models\StockRequest;
use App\Models\StockRequestLine;

class WarehouseIntelligenceController extends Controller
{
    const TABS = ['rts', 'prd'];

    public function __construct(
        private readonly InventoryIntelligenceService $intelligenceService
    ) {}

    public function index(Request $request)
    {
        $tab = $request->query('tab', 'rts');
        if (!in_array($tab, self::TABS)) {
            $tab = 'rts';
        }

        // We need all items for the basic filter placeholders
        $items = \App\Models\Item::with('category:id,name')->select('id', 'code', 'name', 'item_category_id')->get();
        $categories = $items->pluck('category')->filter()->unique('id')->sortBy('name')->values();

        return view('inventory.warehouse_intelligence.index', compact('tab', 'categories', 'items'));
    }

    public function tabData(Request $request)
    {
        $tab = $request->input('tab', 'rts');
        $filters = $request->only(['item_id', 'category_id']);

        // Base rows from Intelligence Service (No controller-level cache to ensure limits update instantly)
        $rows = $this->intelligenceService->rows($filters);

        // Cari item yang sudah ada draft request-nya
        $draftItems = [];
        $prDraftItems = [];
        if ($tab === 'rts') {
            $draftItems = StockRequestLine::whereHas('request', function ($q) {
                $q->where('purpose', 'rts_replenish')->where('status', 'draft');
            })->pluck('stock_request_id', 'item_id')->toArray();
            
            $prDraftItems = \App\Models\PurchaseRequestLine::whereHas('purchaseRequest', function ($q) {
                $q->where('status', 'draft');
            })->pluck('purchase_request_id', 'item_id')->toArray();
        }

        // Enrich with RTS specific logic
        $rows = $rows->map(function ($r) use ($draftItems, $prDraftItems) {
            $rtsCover = $r->ads > 0 ? $r->ready / $r->ads : 9999;
            $r->rts_cover = round($rtsCover, 1);
            
            // Defisit calculation: Target is custom max display or 14 days cover
            $targetRtsQty = $r->rts_max_display ?? ceil($r->ads * 14);
            $r->rts_deficit = max(0, $targetRtsQty - $r->ready);
            
            // Threshold logic: Min display or 7 days cover (min 5)
            $minThreshold = $r->rts_min_display ?? max(5, ceil($r->ads * 7));
            $r->is_rts_critical = $r->ready <= $minThreshold;
            
            // Amount that can actually be requested from PRD
            $r->minta_prd = min($r->rts_deficit, $r->wh_prd);
            
            $r->draft_id = $draftItems[$r->item_id] ?? null;
            $r->pr_draft_id = $prDraftItems[$r->item_id] ?? null;

            return $r;
        });

        if ($tab === 'rts') {
            // WH-RTS Needs: Tampilkan SEMUA item aktif agar user bisa mengatur batas Min/Max-nya.
            $rtsNeeds = $rows->filter(function ($r) {
                return $r->ads > 0;
            })->sortByDesc('wh_prd')->values();
            
            return view('inventory.warehouse_intelligence.partials._rts', [
                'rows' => $rtsNeeds,
                'fmt' => fn($n, $d = 0) => number_format((float) $n, $d, ',', '.')
            ])->render();
            
        } elseif ($tab === 'prd') {
            // WH-PRD Priorities: 
            // 1. Packing: RTS needs it, and PRD has stock
            $packingPriority = $rows->filter(function ($r) {
                return $r->ads > 0 && $r->is_rts_critical && $r->wh_prd > 0;
            })->sortByDesc('ads')->values();

            // 2. Sewing: RTS needs it, PRD is empty, but WIP exists
            $sewingPriority = $rows->filter(function ($r) {
                return $r->ads > 0 && $r->is_rts_critical && $r->wh_prd <= 0 && $r->wip > 0;
            })->sortByDesc('ads')->values();

            // 3. Cutting: Total stock (RTS + PRD + WIP) is less than 60 days of sales, and it is produced internally
            $cuttingPriority = $rows->filter(function ($r) {
                $totalStock = $r->ready + $r->wh_prd + $r->wip;
                $target60d = $r->ads * 60;
                return $r->ads > 0 && $totalStock < $target60d && $r->production_source !== 'buy';
            })->map(function ($r) {
                $r->target_60d = $r->ads * 60;
                $totalStock = $r->ready + $r->wh_prd + $r->wip;
                $r->saran_potong = max(0, $r->target_60d - $totalStock);
                
                $name = $r->product;
                $color = 'Lainnya';
                $colors = ['Hitam', 'Navy', 'Misty', 'Abu', 'Putih', 'Maroon', 'Hijau', 'Biru', 'Coklat', 'Cream', 'Khaki', 'Army', 'Mustard', 'Olive', 'Sage', 'Taro', 'Lilac', 'Pink', 'Merah', 'Kuning', 'Orange', 'Blue', 'Black', 'White', 'Grey'];
                foreach ($colors as $c) {
                    if (stripos($name, $c) !== false) {
                        $color = $c;
                        break;
                    }
                }
                $r->color_group = $color;
                
                return $r;
            })->sortByDesc('saran_potong')->values();

            $cuttingItemIds = $cuttingPriority->pluck('item_id')->unique()->toArray();
            $boms = \App\Models\ItemBom::whereIn('item_id', $cuttingItemIds)
                ->where('active', true)
                ->with(['lines.material'])
                ->get()
                ->keyBy('item_id');

            $cuttingPriorityGrouped = $cuttingPriority->groupBy('color_group')->map(function($items) use ($boms) {
                $groupMaterials = collect();
                foreach ($items as $r) {
                    $itemMaterials = [];
                    $bom = $boms->get($r->item_id);
                    if ($bom) {
                        foreach ($bom->lines as $line) {
                            if ($line->material && in_array($line->usage_stage, ['main_material', 'sewing_supply'])) {
                                $matId = $line->material->id;
                                $reqQty = $r->saran_potong * $line->qty;
                                
                                // For item row display
                                $itemMaterials[] = [
                                    'name' => $line->material->name,
                                    'req_qty' => $reqQty,
                                    'uom' => $line->uom,
                                ];

                                // For group summary
                                if (!isset($groupMaterials[$matId])) {
                                    $groupMaterials->put($matId, [
                                        'name' => $line->material->name,
                                        'req_qty' => 0,
                                        'uom' => $line->uom,
                                    ]);
                                }
                                $matData = $groupMaterials->get($matId);
                                $matData['req_qty'] += $reqQty;
                                $groupMaterials->put($matId, $matData);
                            }
                        }
                    }
                    $r->bom_materials = $itemMaterials;
                }
                return [
                    'items' => $items,
                    'materials' => $groupMaterials->values()
                ];
            });

            return view('inventory.warehouse_intelligence.partials._prd', [
                'packingPriority' => $packingPriority,
                'sewingPriority' => $sewingPriority,
                'cuttingPriority' => $cuttingPriority,
                'cuttingPriorityGrouped' => $cuttingPriorityGrouped,
                'fmt' => fn($n, $d = 0) => number_format((float) $n, $d, ',', '.')
            ])->render();
        }

        return '';
    }

    public function updateLimits(Request $request)
    {
        $request->validate([
            'item_id' => 'required|exists:items,id',
            'rts_min_display' => 'nullable|integer|min:0',
            'rts_max_display' => 'nullable|integer|min:0',
        ]);

        $item = Item::findOrFail($request->item_id);
        $item->update([
            'rts_min_display' => $request->rts_min_display,
            'rts_max_display' => $request->rts_max_display,
        ]);

        return response()->json(['success' => true, 'message' => 'Batas display berhasil diperbarui']);
    }

    public function requestDraft(Request $request)
    {
        $request->validate([
            'lines' => 'required|array|min:1',
            'lines.*.item_id' => 'required|exists:items,id',
            'lines.*.qty' => 'required|numeric|gt:0'
        ]);

        $prdWarehouse = Warehouse::where('code', 'WH-PRD')->first();
        $rtsWarehouse = Warehouse::where('code', 'WH-RTS')->first();

        if (!$prdWarehouse || !$rtsWarehouse) {
            return response()->json(['success' => false, 'message' => 'Gudang PRD/RTS tidak ditemukan.']);
        }

        $draft = StockRequest::where('purpose', 'rts_replenish')
            ->where('status', 'draft')
            ->latest('id')
            ->first();

        DB::transaction(function () use (&$draft, $request, $prdWarehouse, $rtsWarehouse) {
            if (!$draft) {
                $date = now()->toDateString();
                $prefix = 'RTS-' . now()->format('Ymd') . '-';
                
                $lastCode = DB::table('stock_requests')
                    ->where('purpose', 'rts_replenish')
                    ->where('code', 'like', $prefix . '%')
                    ->lockForUpdate()
                    ->orderByDesc('code')
                    ->value('code');
                    
                $next = 1;
                if ($lastCode) {
                    $tail = substr((string) $lastCode, strlen($prefix));
                    $next = max((int) ltrim($tail, '0') + 1, 1);
                }
                
                $code = $prefix . str_pad((string) $next, 4, '0', STR_PAD_LEFT);

                $draft = new StockRequest();
                $draft->code = $code;
                $draft->date = $date;
                $draft->purpose = 'rts_replenish';
                $draft->status = 'draft';
                $draft->source_warehouse_id = $prdWarehouse->id;
                $draft->destination_warehouse_id = $rtsWarehouse->id;
                
                if (property_exists($draft, 'created_by')) {
                    $draft->created_by = auth()->id();
                } elseif (property_exists($draft, 'created_by_id')) {
                    $draft->created_by_id = auth()->id();
                }
                
                if (property_exists($draft, 'requested_by_user_id')) {
                    $draft->requested_by_user_id = auth()->id();
                }
                
                $draft->save();
            }

            $lineNo = $draft->lines()->max('line_no') + 1;
            
            foreach ($request->lines as $lineData) {
                $existingLine = $draft->lines()->where('item_id', $lineData['item_id'])->first();
                if ($existingLine) {
                    $existingLine->qty_request += $lineData['qty'];
                    $existingLine->save();
                } else {
                    StockRequestLine::create([
                        'stock_request_id' => $draft->id,
                        'line_no' => $lineNo++,
                        'item_id' => $lineData['item_id'],
                        'qty_request' => $lineData['qty'],
                        'qty_dispatched' => 0,
                        'qty_received' => 0,
                    ]);
                }
            }
        });

        return response()->json(['success' => true, 'message' => 'Draft permintaan RTS berhasil dibuat.', 'draft_id' => $draft->id]);
    }

    public function requestPrDraft(Request $request)
    {
        $request->validate([
            'lines' => 'required|array|min:1',
            'lines.*.item_id' => 'required|exists:items,id',
            'lines.*.qty' => 'required|numeric|gt:0'
        ]);

        $draft = \App\Models\PurchaseRequest::where('status', 'draft')
            ->where('requested_by', auth()->id())
            ->latest('id')
            ->first();

        DB::transaction(function () use (&$draft, $request) {
            if (!$draft) {
                $code = class_exists(\App\Helpers\CodeGenerator::class) 
                    ? \App\Helpers\CodeGenerator::make('PR')
                    : 'PR-' . now()->format('Ymd') . '-' . rand(100, 999);
                    
                $draft = \App\Models\PurchaseRequest::create([
                    'code'         => $code,
                    'date'         => now()->toDateString(),
                    'requested_by' => auth()->id(),
                    'status'       => 'draft',
                ]);
            }

            foreach ($request->lines as $lineData) {
                $existingLine = $draft->lines()->where('item_id', $lineData['item_id'])->first();
                if ($existingLine) {
                    $existingLine->qty += $lineData['qty'];
                    $existingLine->save();
                } else {
                    \App\Models\PurchaseRequestLine::create([
                        'purchase_request_id' => $draft->id,
                        'item_id' => $lineData['item_id'],
                        'qty' => $lineData['qty'],
                    ]);
                }
            }
        });

        return response()->json([
            'success' => true, 
            'message' => 'Draft PR berhasil diperbarui!',
            'pr_draft_id' => $draft->id
        ]);
    }
}
