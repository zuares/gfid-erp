<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Services\Inventory\InventoryIntelligenceService;
use App\Models\Item;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
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
        $role = strtolower((string)(auth()->user()?->role ?? 'owner'));
        
        // Define allowed tabs per role
        $allowedTabs = self::TABS;
        if ($role === 'admin') {
            $allowedTabs = ['rts'];
        } elseif ($role === 'operating') {
            $allowedTabs = ['prd'];
        }

        $tab = $request->query('tab');
        if (!$tab || !in_array($tab, $allowedTabs)) {
            $tab = $allowedTabs[0] ?? 'rts';
        }

        // We need all items for the basic filter placeholders
        $items = \App\Models\Item::with('category:id,name')->select('id', 'code', 'name', 'item_category_id')->get();
        $categories = $items->pluck('category')->filter()->unique('id')->sortBy('name')->values();
        $filters = $request->only(['item_id', 'category_id', 'operational_filter', 'draft_filter']);
        $aiInsights = $this->buildInsights($tab, $filters);

        return view('inventory.warehouse_intelligence.index', compact('tab', 'categories', 'items', 'filters', 'aiInsights'));
    }

    public function tabData(Request $request)
    {
        $role = strtolower((string)(auth()->user()?->role ?? 'owner'));
        
        $allowedTabs = self::TABS;
        if ($role === 'admin') {
            $allowedTabs = ['rts'];
        } elseif ($role === 'operating') {
            $allowedTabs = ['prd'];
        }

        $tab = $request->input('tab');
        if (!$tab || !in_array($tab, $allowedTabs)) {
            $tab = $allowedTabs[0] ?? 'rts';
        }
        $filters = $request->only(['item_id', 'category_id', 'operational_filter', 'draft_filter']);

        // Base rows from Intelligence Service (No controller-level cache to ensure limits update instantly)
        $rows = $this->intelligenceService->rows($filters);
        $rows = $this->applyPrdInHouseOnly($rows, $tab);

        // Cari item yang sudah ada draft request-nya
        $draftItems = [];
        $prDraftItems = [];
        $poDraftItems = [];
        if ($tab === 'rts') {
            $draftItems = StockRequestLine::whereHas('request', function ($q) {
                $q->where('purpose', 'rts_replenish')->where('status', 'draft');
            })->pluck('stock_request_id', 'item_id')->toArray();
            
            $prDraftItems = \App\Models\PurchaseRequestLine::whereHas('purchaseRequest', function ($q) {
                $q->where('status', 'draft');
            })->pluck('purchase_request_id', 'item_id')->toArray();

            $poDraftItems = DB::table('purchase_order_lines')
                ->join('purchase_orders', 'purchase_orders.id', '=', 'purchase_order_lines.purchase_order_id')
                ->where('purchase_orders.status', 'draft')
                ->whereNotNull('purchase_orders.purchase_request_id')
                ->orderByDesc('purchase_orders.id')
                ->select(
                    'purchase_order_lines.item_id',
                    'purchase_orders.id as purchase_order_id',
                    'purchase_orders.code as purchase_order_code'
                )
                ->get()
                ->unique('item_id')
                ->keyBy('item_id')
                ->all();
        }

        // Enrich with RTS specific logic
        $rows = $rows->map(function ($r) use ($draftItems, $prDraftItems, $poDraftItems) {
            $effectiveMin = $this->effectiveRtsMinDisplay($r);
            $effectiveMax = $this->effectiveRtsMaxDisplay($r);
            $productionGroup = $this->normalizeProductionSourceGroup($r);

            $rtsCover = $r->ads > 0 ? $r->ready / $r->ads : 9999;
            $r->rts_cover = round($rtsCover, 1);
            
            // Defisit calculation: Target is custom max display or 14 days cover
            $targetRtsQty = $effectiveMax;
            $r->rts_deficit = max(0, $targetRtsQty - $r->ready);
            
            // Threshold logic: Min display or 7 days cover (min 5)
            $minThreshold = $effectiveMin;
            $r->is_rts_critical = $r->ready <= $minThreshold;
            
            // Amount that can actually be requested from PRD
            $r->minta_prd = min($r->rts_deficit, $r->wh_prd);
            $r->buy_pr_qty = $this->purchaseRequestQtyForOneMonth($r);
            $r->buy_pr_qty_label = $this->formatSmartQty($r->buy_pr_qty);
            $r->rts_min_effective = $effectiveMin;
            $r->rts_max_effective = $effectiveMax;
            $r->production_group = $productionGroup;
            $r->production_group_label = $this->productionSourceGroupLabel($productionGroup);
            
            $r->draft_id = $draftItems[$r->item_id] ?? null;
            $r->pr_draft_id = $prDraftItems[$r->item_id] ?? null;
            $r->po_draft_id = data_get($poDraftItems, $r->item_id . '.purchase_order_id');
            $r->po_draft_code = data_get($poDraftItems, $r->item_id . '.purchase_order_code');

            return $r;
        });

        $rows = $this->applyOperationalFilter(
            $rows,
            $tab,
            (string) ($filters['operational_filter'] ?? 'all'),
            (string) ($filters['draft_filter'] ?? 'all')
        );

        if ($tab === 'rts') {
            // WH-RTS Needs: Tampilkan SEMUA item aktif agar user bisa mengatur batas Min/Max-nya.
            $rtsNeeds = $rows->filter(function ($r) {
                return $r->ads > 0;
            })->sortBy(fn ($r) => (float) ($r->rts_cover ?? 0))->values();
            
            return view('inventory.warehouse_intelligence.partials._rts', [
                'rows' => $rtsNeeds,
                'fmt' => fn($n, $d = 0) => number_format((float) $n, $d, ',', '.')
            ])->render();
            
        } elseif ($tab === 'prd') {
            $sewingRecommendations = $this->buildSewingRecommendations($rows);

            // WH-PRD Priorities: 
            // 1. Packing: RTS needs it, and PRD has stock
            $packingPriority = $rows->filter(function ($r) {
                return $r->ads > 0 && $r->is_rts_critical && $r->wh_prd > 0;
            })->sortByDesc('ads')->values();

            // 2. Sewing: RTS needs it, PRD is empty, but WIP exists
            $sewingPriority = $rows->filter(function ($r) {
                return $r->ads > 0 && $r->is_rts_critical && $r->wh_prd <= 0 && $r->wip > 0;
            })->sortByDesc('ads')->values();

            // 3. Cutting: Total stock (RTS + PRD + WIP) is less than 30 days of sales, and it is produced internally
            $cuttingPriority = $rows->filter(function ($r) {
                $totalStock = $r->ready + $r->wh_prd + $r->wip;
                $target30d = $r->ads * 30;
                return $r->ads > 0 && $totalStock < $target30d && $this->normalizeProductionSourceGroup($r) === 'in_house';
            })->map(function ($r) {
                $r->target_30d = $r->ads * 30;
                $totalStock = $r->ready + $r->wh_prd + $r->wip;
                $r->saran_potong = max(0, $r->target_30d - $totalStock);
                
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
                'sewingRecommendations' => $sewingRecommendations,
                'fmt' => fn($n, $d = 0) => number_format((float) $n, $d, ',', '.')
            ])->render();
        }

        return '';
    }

    public function insights(Request $request)
    {
        $role = strtolower((string)(auth()->user()?->role ?? 'owner'));

        $allowedTabs = self::TABS;
        if ($role === 'admin') {
            $allowedTabs = ['rts'];
        } elseif ($role === 'operating') {
            $allowedTabs = ['prd'];
        }

        $tab = $request->input('tab');
        if (!$tab || !in_array($tab, $allowedTabs, true)) {
            $tab = $allowedTabs[0] ?? 'rts';
        }

        $filters = $request->only(['item_id', 'category_id', 'operational_filter', 'draft_filter']);

        return response()->json([
            'html' => view('inventory.warehouse_intelligence.partials._insights', [
                'insights' => $this->buildInsights($tab, $filters),
            ])->render(),
        ]);
    }

    private function buildInsights(string $tab, array $filters): array
    {
        $rows = $this->intelligenceService->rows($filters);
        $rows = $this->applyPrdInHouseOnly($rows, $tab);
        $rows = $this->applyOperationalFilter(
            $rows,
            $tab,
            (string) ($filters['operational_filter'] ?? 'all'),
            (string) ($filters['draft_filter'] ?? 'all')
        );

        $summary = $this->intelligenceService->summary($rows);
        $sourceSummary = $this->productionSourceSummary($rows);
        $itemLookup = $rows->keyBy('sku')->map(fn ($r) => [
            'sku' => $r->sku,
            'product' => $r->product,
        ]);

        $critical = $rows->filter(fn ($r) => in_array(($r->status ?? ''), ['stockout', 'kritis'], true));
        $topRestock = $rows
            ->filter(fn ($r) => ($r->suggested_qty ?? 0) > 0)
            ->sortByDesc('suggested_qty')
            ->take(5)
            ->map(function ($r) {
                $readyQty = max(0, (float) (($r->ready ?? 0) - ($r->ready_allocated ?? 0)));
                $coverDays = data_get($r, 'cover_days');

                return [
                    'sku' => $r->sku,
                    'product' => $r->product,
                    'ready_qty' => round($readyQty, 0),
                    'ready_qty_label' => $this->formatSmartQty($readyQty),
                    'suggested_qty' => round((float) ($r->suggested_qty ?? 0), 0),
                    'suggested_qty_label' => $this->formatSmartQty((float) ($r->suggested_qty ?? 0)),
                    'cover_days' => $coverDays,
                    'cover_days_label' => $coverDays !== null
                        ? 'Stok aman bertahan ' . number_format((float) $coverDays, 1, ',', '.') . ' hari'
                        : 'Stok aman belum kebaca',
                    'status' => (string) data_get($r, 'status', '-'),
                ];
            })
            ->values();

        $watchlistDefaults = $this->buildWatchlist($rows, $tab);
        $priorityDefaults = $this->buildPriorityActions($tab, $rows);
        $heuristicActions = $this->buildHeuristicActions($tab, $rows);
        $heuristicActionLookup = $heuristicActions->keyBy('sku');
        $priorityLookup = $priorityDefaults->keyBy('sku');
        $actions = $heuristicActions;
        $priorities = $priorityDefaults;
        $watchlist = $watchlistDefaults;
        $watchlistLookup = $watchlistDefaults->keyBy('sku');
        $signals = $this->buildSignals($rows, $summary, $tab);
        $sewingRecommendations = $tab === 'prd' ? $this->buildSewingRecommendations($rows) : [];
        $overview = $this->buildOverview($tab, $summary, $signals, $watchlist, $sewingRecommendations);
        $confidence = 'medium';

        $ai = $this->generateAiInsights($tab, $filters, $summary, $rows, $signals, $actions, $watchlist);
        if (! empty($ai)) {
            $overview = $this->humanizeRecommendationText((string) ($ai['overview'] ?? $overview));
            $actions = collect($ai['actions'] ?? [])->filter()->values();
            if ($actions->isEmpty()) {
                $actions = $heuristicActions;
            } else {
                $actions = $actions->map(function ($action) use ($heuristicActionLookup, $tab) {
                    $sku = (string) ($action['sku'] ?? '');
                    $base = $heuristicActionLookup->get($sku, []);
                    $mapped = [
                        'title' => $this->friendlyActionTitle((string) ($action['title'] ?? ''), $tab),
                        'sku' => $sku,
                        'product' => $base['product'] ?? ($action['product'] ?? $sku),
                        'label' => $this->friendlyActionLabel((string) ($action['label'] ?? ''), $tab),
                        'reason' => $this->humanizeRecommendationText((string) ($action['reason'] ?? '')),
                        'tone' => in_array(($action['tone'] ?? 'info'), ['success', 'warning', 'danger', 'info'], true) ? $action['tone'] : 'info',
                    ];

                    return array_merge($base, $mapped);
                });
            }
            $watchlist = collect($ai['watchlist'] ?? [])->filter()->values();
            if ($watchlist->isEmpty()) {
                $watchlist = $watchlistDefaults;
            } else {
                $watchlist = $watchlist->map(function ($item) use ($watchlistLookup) {
                    $sku = (string) ($item['sku'] ?? '');
                    $base = $watchlistLookup->get($sku, []);
                    return array_merge($base, $item);
                })->values();
            }
            $priorities = collect($ai['priorities'] ?? [])->filter()->values();
            if ($priorities->isEmpty()) {
                $priorities = $priorityDefaults;
            } else {
                $priorities = $priorities->map(function ($item) use ($priorityLookup) {
                    $sku = (string) ($item['sku'] ?? '');
                    $base = $priorityLookup->get($sku, []);
                    return array_merge($base, $item);
                })->values();
            }
            $signals = collect($ai['signals'] ?? [])->filter()->values();
            if ($signals->isEmpty()) {
                $signals = $this->buildSignals($rows, $summary, $tab);
            }
            $confidence = $ai['confidence'] ?? $confidence;
        }

        if ($tab === 'prd' && ! empty(data_get($sewingRecommendations, 'best.name'))) {
            $best = data_get($sewingRecommendations, 'best.name');
            $overview = trim($overview . ' Pickup jahit paling cocok sekarang ada di ' . $best . '.');
        }

        $topPriorityProduct = data_get($priorities->first(), 'product');
        if ($topPriorityProduct && ! Str::contains($overview, (string) $topPriorityProduct)) {
            $overview = trim($overview . ' Prioritas utama ada di ' . $topPriorityProduct . '.');
        }

        $actions = $actions->map(function ($action) use ($itemLookup, $tab) {
            $sku = (string) ($action['sku'] ?? '');
            $product = data_get($itemLookup->get($sku), 'product') ?: $sku;

            return [
                'title' => $this->friendlyActionTitle((string) ($action['title'] ?? ''), $tab),
                'sku' => $sku,
                'product' => $product,
                'label' => $this->friendlyActionLabel((string) ($action['label'] ?? ''), $tab),
                'qty_label' => (string) ($action['qty_label'] ?? ''),
                'days_label' => (string) ($action['days_label'] ?? ''),
                'qty_value' => $action['qty_value'] ?? null,
                'days_value' => $action['days_value'] ?? null,
                'reason' => $this->humanizeRecommendationText((string) ($action['reason'] ?? '')),
                'tone' => in_array(($action['tone'] ?? 'info'), ['success', 'warning', 'danger', 'info'], true) ? $action['tone'] : 'info',
            ];
        })->values();

        $watchlist = $watchlist->map(function ($item) use ($itemLookup) {
            $sku = (string) ($item['sku'] ?? '');
            $product = data_get($itemLookup->get($sku), 'product') ?: $sku;
            $qtyValue = (float) ($item['qty_value'] ?? data_get($item, 'suggested_qty', 0));
            $daysValue = $item['days_value'] ?? data_get($item, 'cover_days');

            return [
                'sku' => $sku,
                'product' => $product,
                'reason' => $this->humanizeRecommendationText((string) ($item['reason'] ?? '')),
                'qty_value' => $qtyValue > 0 ? $qtyValue : null,
                'qty_label' => trim((string) ($item['qty_label'] ?? '')) !== ''
                    ? (string) $item['qty_label']
                    : ($qtyValue > 0 ? $this->formatSmartQty($qtyValue) : ''),
                'days_value' => $daysValue !== null ? (float) $daysValue : null,
                'days_label' => trim((string) ($item['days_label'] ?? '')) !== ''
                    ? (string) $item['days_label']
                    : ($daysValue !== null ? $this->formatSmartDays((float) $daysValue) : ''),
            ];
        })->values();

        $priorities = $priorities->map(function ($item) use ($itemLookup) {
            $sku = (string) ($item['sku'] ?? '');
            $product = data_get($itemLookup->get($sku), 'product') ?: ($item['product'] ?? $sku);

            return [
                'rank' => (string) ($item['rank'] ?? ''),
                'title' => (string) ($item['title'] ?? ''),
                'sku' => $sku,
                'product' => $product,
                'label' => (string) ($item['label'] ?? ''),
                'qty_label' => (string) ($item['qty_label'] ?? ''),
                'days_label' => (string) ($item['days_label'] ?? ''),
                'reason' => $this->humanizeRecommendationText((string) ($item['reason'] ?? '')),
                'tone' => in_array(($item['tone'] ?? 'info'), ['success', 'warning', 'danger', 'info'], true) ? $item['tone'] : 'info',
            ];
        })->values();

        $signals = $signals->map(function ($signal) {
            return [
                'label' => $this->friendlySignalLabel((string) ($signal['label'] ?? '')),
                'value' => (string) ($signal['value'] ?? '-'),
                'evidence' => $this->humanizeRecommendationText((string) ($signal['evidence'] ?? '')),
            ];
        })->values();

        return [
            'criticalCount' => $critical->count(),
            'topRestockCount' => $topRestock->count(),
            'topRestock' => $topRestock,
            'actions' => $actions,
            'priorities' => $priorities,
            'watchlist' => $watchlist,
            'signals' => $signals,
            'overview' => $overview,
            'confidence' => $confidence,
            'summary' => $summary,
            'sourceSummary' => $sourceSummary,
            'dataBasis' => $this->dataBasisLabels($tab),
            'itemLookup' => $itemLookup,
            'sewingRecommendations' => $sewingRecommendations,
            'generatedAt' => now(),
            'tab' => $tab,
        ];
    }

    private function buildHeuristicActions(string $tab, Collection $rows): Collection
    {
        $actions = collect();

        if ($tab === 'rts') {
            $actions = $rows
                ->filter(fn ($r) => ($r->ads ?? 0) > 0)
                ->map(function ($r) {
                    $available = max(0, (float) (($r->ready ?? 0) - ($r->ready_allocated ?? 0)));
                    $minThreshold = $this->effectiveRtsMinDisplay($r);
                    $pullQty = (float) min($r->minta_prd ?? 0, $r->wh_prd ?? 0);
                    $rtsCover = ($r->ads ?? 0) > 0 ? round(($r->ready ?? 0) / max((float) $r->ads, 0.0001), 1) : null;
                    $productionGroup = $this->normalizeProductionSourceGroup($r);

                    if ($available <= $minThreshold && ($r->wh_prd ?? 0) > 0) {
                        $qty = max(1, $pullQty);
                        $days = ($r->ads ?? 0) > 0 ? round($qty / max((float) $r->ads, 0.0001), 1) : null;

                        return [
                            'title' => 'Ambil stok dari produksi',
                            'sku' => $r->sku,
                            'product' => $r->product,
                            'label' => 'Siap dipindah',
                            'qty_value' => $qty,
                            'qty_label' => $this->formatSmartQty($qty),
                            'days_value' => $days,
                            'days_label' => $days !== null ? $this->formatSmartDays($days) : 'Belum kebaca',
                            'reason' => 'Stok depan sudah menipis, sementara stok produksi masih ada dan siap dipindahkan. Stok aman bertahan ' . ($rtsCover !== null ? number_format((float) $rtsCover, 1, ',', '.') . ' hari.' : 'belum terukur.'),
                            'tone' => 'success',
                        ];
                    }

                    if ($available <= $minThreshold && ($r->wh_prd ?? 0) <= 0 && $productionGroup === 'buy') {
                        $qty = $this->purchaseRequestQtyForOneMonth($r);
                        $days = 30;

                        return [
                            'title' => 'Bikin pembelian',
                            'sku' => $r->sku,
                            'product' => $r->product,
                            'label' => 'Untuk 1 bulan',
                            'qty_value' => $qty,
                            'qty_label' => $this->formatSmartQty($qty),
                            'days_value' => $days,
                            'days_label' => $this->formatSmartDays($days),
                            'reason' => 'Stok depan hampir habis, stok produksi kosong, dan qty ini disiapkan untuk kebutuhan 1 bulan.',
                            'tone' => 'warning',
                        ];
                    }

                    if ($available <= $minThreshold && ($r->wh_prd ?? 0) <= 0) {
                        $qty = max(1, (int) ceil(max(0, ($minThreshold - $available)) * max((float) ($r->ads ?? 0), 1)));
                        $days = max(1, (float) round($minThreshold, 1));

                        return [
                            'title' => 'Kejar produksi',
                            'sku' => $r->sku,
                            'product' => $r->product,
                            'label' => 'Perlu dikejar',
                            'qty_value' => $qty,
                            'qty_label' => $this->formatSmartQty($qty),
                            'days_value' => $days,
                            'days_label' => $this->formatSmartDays($days),
                            'reason' => 'Stok depan mendekati habis dan belum ada cadangan dari produksi. Item ini perlu didahulukan di proses produksi.',
                            'tone' => 'danger',
                        ];
                    }

                    return null;
                })
                ->filter()
                ->take(4)
                ->values();
        } else {
            $actions = $rows
                ->filter(fn ($r) => ($r->ads ?? 0) > 0)
                ->map(function ($r) {
                    $total = (float) (($r->ready ?? 0) + ($r->wh_prd ?? 0) + ($r->wip ?? 0));
                $target30d = (float) (($r->ads ?? 0) * 30);
                $minThreshold = $this->effectiveRtsMinDisplay($r);
                $pullQty = (float) min($r->minta_prd ?? 0, $r->wh_prd ?? 0);

                    if (($r->wh_prd ?? 0) > 0 && (($r->ready ?? 0) <= $minThreshold)) {
                        $qty = max(1, $pullQty);
                        $days = ($r->ads ?? 0) > 0 ? round($qty / max((float) $r->ads, 0.0001), 1) : null;

                        return [
                            'title' => 'Pindahkan ke stok depan',
                            'sku' => $r->sku,
                            'product' => $r->product,
                            'label' => 'Siap dipindah',
                            'qty_value' => $qty,
                            'qty_label' => $this->formatSmartQty($qty),
                            'days_value' => $days,
                            'days_label' => $days !== null ? $this->formatSmartDays($days) : 'Belum kebaca',
                            'reason' => 'Barang masih ada di stok produksi dan stok depan sudah tipis.',
                            'tone' => 'success',
                        ];
                    }

                    if (($r->wh_prd ?? 0) <= 0 && ($r->wip ?? 0) > 0 && (($r->ready ?? 0) + ($r->wh_prd ?? 0)) <= $minThreshold) {
                        $qty = max(1, (int) round((float) ($r->wip ?? 0)));
                        $days = ($r->ads ?? 0) > 0 ? round($qty / max((float) $r->ads, 0.0001), 1) : null;

                        return [
                            'title' => 'Ambil jahit',
                            'sku' => $r->sku,
                            'product' => $r->product,
                            'label' => 'Perlu jahit',
                            'qty_value' => $qty,
                            'qty_label' => $this->formatSmartQty($qty),
                            'days_value' => $days,
                            'days_label' => $days !== null ? $this->formatSmartDays($days) : 'Belum kebaca',
                            'reason' => 'Stok depan kosong, stok produksi belum ada, tapi masih ada stok proses yang bisa diselesaikan lebih dulu.',
                            'tone' => 'warning',
                        ];
                    }

                    if ($total < $target30d && $productionGroup !== 'buy') {
                        $qty = max(1, (int) ceil(max(0, $target30d - $total)));
                        $days = 30;

                        return [
                            'title' => 'Jadwalkan potong',
                            'sku' => $r->sku,
                            'product' => $r->product,
                            'label' => 'Kejar 30 hari',
                            'qty_value' => $qty,
                            'qty_label' => $this->formatSmartQty($qty),
                            'days_value' => $days,
                            'days_label' => $this->formatSmartDays($days),
                            'reason' => 'Total stok masih belum cukup untuk menutup penjualan 1 bulan ke depan.',
                            'tone' => 'danger',
                        ];
                    }

                    return null;
                })
                ->filter()
                ->take(4)
                ->values();
        }

        return $actions;
    }

    private function buildPriorityActions(string $tab, Collection $rows): Collection
    {
        $priorities = $rows
            ->filter(fn ($r) => ($r->ads ?? 0) > 0)
            ->map(function ($r) use ($tab) {
                $available = max(0, (float) (($r->ready ?? 0) - ($r->ready_allocated ?? 0)));
                $minThreshold = $this->effectiveRtsMinDisplay($r);
                $totalStock = (float) (($r->ready ?? 0) + ($r->wh_prd ?? 0) + ($r->wip ?? 0));
                $target30d = (float) (($r->ads ?? 0) * 30);
                $productionGroup = $this->normalizeProductionSourceGroup($r);
                $ads = max(0.0001, (float) ($r->ads ?? 0));
                $coverDays = round($available / $ads, 1);
                $shortage30d = max(0, $target30d - $totalStock);
                $liftBonus = ((float) ($r->ads_lift ?? 0) > 0) ? 3 : 0;
                $criticalBonus = $available <= 0 ? 5 : ($available <= $minThreshold ? 2 : 0);

                if ($tab === 'rts') {
                    if ($available <= 0 && ($r->wh_prd ?? 0) > 0) {
                        return [
                            'priority_score' => 100 + $liftBonus + $criticalBonus,
                            'title' => 'Ambil stok dari produksi',
                            'sku' => $r->sku,
                            'product' => $r->product,
                            'label' => 'P1 · siap dipindah',
                            'qty_value' => max(1, (float) min($r->minta_prd ?? 0, $r->wh_prd ?? 0)),
                            'qty_label' => $this->formatSmartQty(max(1, (float) min($r->minta_prd ?? 0, $r->wh_prd ?? 0))),
                            'days_value' => $coverDays,
                            'days_label' => $this->formatSmartDays($coverDays),
                            'reason' => 'Stok depan kosong, stok produksi masih ada, dan item ini harus diutamakan supaya penjualan tidak putus.',
                            'tone' => 'success',
                        ];
                    }

                    if ($available <= $minThreshold && ($r->wh_prd ?? 0) > 0) {
                        $qty = max(1, (float) min($r->minta_prd ?? 0, $r->wh_prd ?? 0));

                        return [
                            'priority_score' => 95 + $liftBonus + $criticalBonus,
                            'title' => 'Ambil stok dari produksi',
                            'sku' => $r->sku,
                            'product' => $r->product,
                            'label' => 'P1 · stok tipis',
                            'qty_value' => $qty,
                            'qty_label' => $this->formatSmartQty($qty),
                            'days_value' => $coverDays,
                            'days_label' => $this->formatSmartDays($coverDays),
                            'reason' => 'Stok depan sudah tipis dan stok produksi masih tersedia. Item ini paling aman ditarik lebih dulu.',
                            'tone' => 'success',
                        ];
                    }

                    if ($available <= $minThreshold && ($r->wh_prd ?? 0) <= 0 && $productionGroup === 'buy') {
                        $qty = max(1, $this->purchaseRequestQtyForOneMonth($r));

                        return [
                            'priority_score' => 92 + $liftBonus + $criticalBonus,
                            'title' => 'Bikin pembelian',
                            'sku' => $r->sku,
                            'product' => $r->product,
                            'label' => 'P2 · perlu beli',
                            'qty_value' => $qty,
                            'qty_label' => $this->formatSmartQty($qty),
                            'days_value' => 30,
                            'days_label' => $this->formatSmartDays(30),
                            'reason' => 'Stok depan menipis, stok produksi kosong, dan item ini memang masuk grup beli jadi.',
                            'tone' => 'warning',
                        ];
                    }

                    if ($available <= $minThreshold && ($r->wh_prd ?? 0) <= 0 && ($r->wip ?? 0) > 0) {
                        $qty = max(1, (int) round((float) ($r->wip ?? 0)));

                        return [
                            'priority_score' => 90 + $liftBonus + $criticalBonus,
                            'title' => 'Ambil jahit',
                            'sku' => $r->sku,
                            'product' => $r->product,
                            'label' => 'P2 · stok proses',
                            'qty_value' => $qty,
                            'qty_label' => $this->formatSmartQty($qty),
                            'days_value' => $coverDays,
                            'days_label' => $this->formatSmartDays($coverDays),
                            'reason' => 'Stok depan tipis, stok produksi kosong, tapi masih ada stok proses yang bisa segera dikejar.',
                            'tone' => 'warning',
                        ];
                    }

                    if ($available <= $minThreshold && ($r->wh_prd ?? 0) <= 0) {
                        $qty = max(1, (int) ceil(max(0, $minThreshold - $available) * max((float) ($r->ads ?? 0), 1)));

                        return [
                            'priority_score' => 88 + $liftBonus + $criticalBonus,
                            'title' => $productionGroup === 'outsource' ? 'Follow up makloon' : 'Kejar produksi',
                            'sku' => $r->sku,
                            'product' => $r->product,
                            'label' => 'P3 · perlu dikejar',
                            'qty_value' => $qty,
                            'qty_label' => $this->formatSmartQty($qty),
                            'days_value' => $coverDays,
                            'days_label' => $this->formatSmartDays($coverDays),
                            'reason' => $productionGroup === 'outsource'
                                ? 'Stok depan menipis dan item ini bergantung ke proses makloon. Perlu difollow up lebih dulu.'
                                : 'Stok depan menipis dan belum ada cadangan yang siap. Item ini perlu didahulukan di produksi.',
                            'tone' => 'danger',
                        ];
                    }
                }

                if ($tab === 'prd') {
                    if (($r->wh_prd ?? 0) > 0 && $available <= $minThreshold) {
                        $qty = max(1, (float) min($r->minta_prd ?? 0, $r->wh_prd ?? 0));

                        return [
                            'priority_score' => 96 + $liftBonus + $criticalBonus,
                            'title' => 'Pindahkan ke stok depan',
                            'sku' => $r->sku,
                            'product' => $r->product,
                            'label' => 'P1 · transfer',
                            'qty_value' => $qty,
                            'qty_label' => $this->formatSmartQty($qty),
                            'days_value' => $coverDays,
                            'days_label' => $this->formatSmartDays($coverDays),
                            'reason' => 'Stok produksi masih ada dan stok depan sudah menipis. Ini kandidat transfer paling cepat.',
                            'tone' => 'success',
                        ];
                    }

                    if (($r->wh_prd ?? 0) <= 0 && ($r->wip ?? 0) > 0 && $available <= $minThreshold) {
                        $qty = max(1, (int) round((float) ($r->wip ?? 0)));

                        return [
                            'priority_score' => 92 + $liftBonus + $criticalBonus,
                            'title' => 'Ambil jahit',
                            'sku' => $r->sku,
                            'product' => $r->product,
                            'label' => 'P2 · jahit',
                            'qty_value' => $qty,
                            'qty_label' => $this->formatSmartQty($qty),
                            'days_value' => $coverDays,
                            'days_label' => $this->formatSmartDays($coverDays),
                            'reason' => 'Stok depan kosong dan masih ada stok proses yang bisa dipercepat dulu.',
                            'tone' => 'warning',
                        ];
                    }

                    if ($totalStock < $target30d && $productionGroup === 'in_house') {
                        $qty = max(1, (int) ceil($shortage30d));

                        return [
                            'priority_score' => 86 + $liftBonus,
                            'title' => 'Jadwalkan potong',
                            'sku' => $r->sku,
                            'product' => $r->product,
                            'label' => 'P3 · cutting',
                            'qty_value' => $qty,
                            'qty_label' => $this->formatSmartQty($qty),
                            'days_value' => 30,
                            'days_label' => $this->formatSmartDays(30),
                            'reason' => 'Total stok belum cukup untuk menutup 30 hari ke depan, jadi cutting perlu dijadwalkan.',
                            'tone' => 'danger',
                        ];
                    }
                }

                return null;
            })
            ->filter()
            ->sortByDesc('priority_score')
            ->values()
            ->take(3)
            ->map(function ($item, $index) {
                $item['rank'] = 'P' . ($index + 1);
                unset($item['priority_score']);

                return $item;
            });

        return $priorities;
    }

    private function buildWatchlist(Collection $rows, string $tab): Collection
    {
        $minify = function ($r) use ($tab) {
            $productionGroup = $this->normalizeProductionSourceGroup($r);
            $productionLabel = $this->productionSourceGroupLabel($productionGroup);

            return [
                'sku' => $r->sku,
                'product' => $r->product,
                'qty_value' => max(0, (float) ($r->suggested_qty ?? 0)),
                'qty_label' => $this->formatSmartQty((float) ($r->suggested_qty ?? 0)),
                'days_value' => isset($r->cover_days) ? (float) $r->cover_days : null,
                'days_label' => isset($r->cover_days)
                    ? $this->formatSmartDays((float) $r->cover_days)
                    : '',
                'ready_qty' => max(0, (float) (($r->ready ?? 0) - ($r->ready_allocated ?? 0))),
                'reason' => $tab === 'rts'
                    ? (
                        (($r->wh_prd ?? 0) > 0)
                            ? 'Masih ada stok produksi yang bisa dipindahkan.'
                            : ($productionGroup === 'buy'
                                ? 'Masuk grup ' . $productionLabel . '. Perlu dibelikan lagi.'
                                : 'Masuk grup ' . $productionLabel . '. Perlu didahulukan di produksi.')
                    )
                    : (
                        (($r->wh_prd ?? 0) > 0)
                            ? 'Perlu dipindah ke stok depan.'
                            : (($r->wip ?? 0) > 0 ? 'Masih ada stok proses yang bisa dikejar.' : 'Perlu dijadwalkan potong.')
                    ),
            ];
        };

        return $rows
            ->filter(fn ($r) => ($r->ads ?? 0) > 0)
            ->sortByDesc(fn ($r) => (float) ($r->eval_score ?? 0))
            ->take(3)
            ->map($minify)
            ->values();
    }

    private function buildSignals(Collection $rows, array $summary, string $tab): Collection
    {
        $signals = collect([
            [
                'label' => 'Perlu perhatian',
                'value' => (int) ($summary['below_target'] ?? 0),
                'evidence' => 'Item yang stok depannya sudah tipis atau habis.',
            ],
            [
                'label' => 'Isi ulang',
                'value' => (int) round((float) ($summary['total_suggested'] ?? 0)),
                'evidence' => 'Kebutuhan barang tambahan dari data yang masuk sekarang.',
            ],
            [
                'label' => 'Lagi naik',
                'value' => (int) $rows->filter(fn ($r) => (float) ($r->ads_lift ?? 0) > 0)->count(),
                'evidence' => 'Barang yang penjualannya mulai menguat dibanding rata-ratanya.',
            ],
        ]);

        if ($tab === 'rts') {
            $signals->prepend([
                'label' => 'Siap dipindah',
                'value' => (int) $rows->filter(fn ($r) => ($r->wh_prd ?? 0) > 0 && (($r->ready ?? 0) <= $this->effectiveRtsMinDisplay($r)))->count(),
                'evidence' => 'Barang yang masih ada di stok produksi dan sudah tipis di stok depan.',
            ]);
        } else {
            $signals->prepend([
                'label' => 'Siap dipindah',
                'value' => (int) $rows->filter(fn ($r) => ($r->wh_prd ?? 0) > 0 && (($r->ready ?? 0) <= $this->effectiveRtsMinDisplay($r)))->count(),
                'evidence' => 'Barang yang sudah siap bergerak ke stok depan.',
            ]);
            $signals->push([
                'label' => 'Stok proses',
                'value' => (int) $rows->filter(fn ($r) => ($r->wip ?? 0) > 0)->count(),
                'evidence' => 'Barang yang masih ada di tahap proses dan bisa dikejar lebih dulu.',
            ]);
        }

        return $signals->values();
    }

    private function buildOverview(string $tab, array $summary, Collection $signals, Collection $watchlist, array $sewingRecommendations = []): string
    {
        $demandSku = (int) ($summary['sku_demand'] ?? 0);
        $criticalSku = (int) ($summary['below_target'] ?? 0);
        $restock = (int) round((float) ($summary['total_suggested'] ?? 0));
        $topWatch = data_get($watchlist->first(), 'product') ?: data_get($watchlist->first(), 'sku');

        if ($tab === 'rts') {
            $text = "Saya membaca {$demandSku} SKU aktif. {$criticalSku} SKU sudah di bawah target stok aman dan total saran pengisian stok mencapai " . $this->formatSmartQty($restock) . '.';
            if ($topWatch) {
                $text .= " Fokus terdekat ada di {$topWatch}.";
            }
            return $text;
        }

        $transferReady = (int) (data_get($signals->firstWhere('label', 'Siap dipindah'), 'value') ?? 0);
        $wipActive = (int) (data_get($signals->firstWhere('label', 'Stok proses'), 'value') ?? 0);
        $text = "Saya membaca {$demandSku} SKU aktif. {$transferReady} SKU siap ditarik ke RTS dan {$wipActive} SKU masih punya WIP yang bisa dikejar.";
        if ($topWatch) {
            $text .= " Fokus terdekat ada di {$topWatch}.";
        }

        $bestOperator = data_get($sewingRecommendations, 'best.name');
        if ($bestOperator) {
            $text .= " Untuk pickup jahit, yang paling siap saat ini {$bestOperator}.";
        }

        return $text;
    }

    private function dataBasisLabels(string $tab): array
    {
        $common = [
            'stok depan',
            'stok produksi',
            'stok proses',
            'rata-rata jual 7/14/30 hari',
            'tren jual',
            'stok aman bertahan',
        ];

        return $tab === 'rts'
            ? array_merge($common, ['batas display', 'draft permintaan', 'group produksi in-house vs beli'])
            : array_merge($common, ['produksi sendiri', 'prioritas pindah', 'prioritas jahit', 'prioritas potong', 'operator jahit']);
    }

    private function normalizeDisplayLimit(mixed $value): ?int
    {
        if (! is_numeric($value)) {
            return null;
        }

        $normalized = (int) $value;

        return $normalized > 0 ? $normalized : null;
    }

    private function effectiveRtsMinDisplay(object $row): float
    {
        $explicit = $this->normalizeDisplayLimit($row->rts_min_display ?? null);

        return (float) ($explicit ?? max(5, ceil(((float) ($row->ads ?? 0)) * 7)));
    }

    private function effectiveRtsMaxDisplay(object $row): float
    {
        $explicit = $this->normalizeDisplayLimit($row->rts_max_display ?? null);

        return (float) ($explicit ?? ceil(((float) ($row->ads ?? 0)) * 14));
    }

    private function applyOperationalFilter(Collection $rows, string $tab, string $operationalFilter, string $draftFilter): Collection
    {
        $operationalFilter = $operationalFilter ?: 'all';
        $draftFilter = $draftFilter ?: 'all';

        return $rows
            ->filter(function ($r) use ($tab, $operationalFilter, $draftFilter) {
                $ready = max(0, (float) (($r->ready ?? 0) - ($r->ready_allocated ?? 0)));
                $minThreshold = $this->effectiveRtsMinDisplay($r);
                $whPrd = (float) ($r->wh_prd ?? 0);
                $wip = (float) ($r->wip ?? 0);
                $productionGroup = $this->normalizeProductionSourceGroup($r);
                $hasDraft = !empty($r->draft_id) || !empty($r->pr_draft_id);
                $totalStock = (float) (($r->ready ?? 0) + $whPrd + $wip);
                $target30d = (float) (($r->ads ?? 0) * 30);

                $operationalPass = match ($operationalFilter) {
                    'critical' => $ready <= $minThreshold,
                    'transfer_ready' => $whPrd > 0 && $ready <= $minThreshold,
                    'need_buy' => $ready <= $minThreshold && $whPrd <= 0 && $productionGroup === 'buy',
                    'need_production' => $ready <= $minThreshold && $whPrd <= 0 && $productionGroup !== 'buy',
                    'with_wip' => $wip > 0,
                    'sewing' => $wip > 0 && $whPrd <= 0 && $ready <= $minThreshold,
                    'cutting' => $tab === 'prd' ? ($totalStock < $target30d && $productionGroup !== 'buy') : $totalStock < $target30d,
                    'ready_to_move' => $whPrd > 0 && $ready <= $minThreshold,
                    default => true,
                };

                $draftPass = match ($draftFilter) {
                    'has_draft' => $hasDraft,
                    'no_draft' => ! $hasDraft,
                    default => true,
                };

                return $operationalPass && $draftPass;
            })
            ->values();
    }

    private function applyPrdInHouseOnly(Collection $rows, string $tab): Collection
    {
        if ($tab !== 'prd') {
            return $rows;
        }

        return $rows
            ->filter(function ($r) {
                return $this->normalizeProductionSourceGroup($r) === 'in_house';
            })
            ->values();
    }

    private function buildSewingRecommendations(Collection $rows): array
    {
        $windowDays = 14;
        $startDate = now()->subDays($windowDays - 1)->toDateString();

        $performanceRows = DB::table('sewing_returns as r')
            ->join('sewing_return_lines as rl', 'rl.sewing_return_id', '=', 'r.id')
            ->join('sewing_pickup_lines as pl', 'pl.id', '=', 'rl.sewing_pickup_line_id')
            ->leftJoin('employees as e', 'e.id', '=', 'r.operator_id')
            ->whereNull('r.voided_at')
            ->where('r.status', 'posted')
            ->whereDate('r.date', '>=', $startDate)
            ->groupBy('r.operator_id', 'e.code', 'e.name')
            ->selectRaw('r.operator_id, e.code as operator_code, e.name as operator_name, SUM(rl.qty_ok) as total_ok, SUM(rl.qty_reject) as total_reject, COUNT(DISTINCT r.date) as active_days, COUNT(DISTINCT pl.finished_item_id) as item_variety')
            ->get()
            ->keyBy('operator_id');

        $activeRows = DB::table('sewing_pickup_lines as pl')
            ->join('sewing_pickups as p', 'p.id', '=', 'pl.sewing_pickup_id')
            ->leftJoin('employees as e', 'e.id', '=', 'p.operator_id')
            ->leftJoin('items as i', 'i.id', '=', 'pl.finished_item_id')
            ->whereNull('p.voided_at')
            ->where('pl.status', 'in_progress')
            ->whereRaw('(COALESCE(pl.qty_bundle,0) - COALESCE(pl.qty_returned_ok,0) - COALESCE(pl.qty_returned_reject,0) - COALESCE(pl.qty_direct_picked,0) - COALESCE(pl.qty_progress_adjusted,0) - COALESCE(pl.qty_closed,0)) > 0.0001')
            ->groupBy('p.operator_id', 'e.code', 'e.name', 'pl.finished_item_id', 'i.code', 'i.name')
            ->orderByDesc(DB::raw('SUM(COALESCE(pl.qty_bundle,0) - COALESCE(pl.qty_returned_ok,0) - COALESCE(pl.qty_returned_reject,0) - COALESCE(pl.qty_direct_picked,0) - COALESCE(pl.qty_progress_adjusted,0) - COALESCE(pl.qty_closed,0))'))
            ->selectRaw('p.operator_id, e.code as operator_code, e.name as operator_name, pl.finished_item_id, i.code as item_code, i.name as item_name, SUM(COALESCE(pl.qty_bundle,0) - COALESCE(pl.qty_returned_ok,0) - COALESCE(pl.qty_returned_reject,0) - COALESCE(pl.qty_direct_picked,0) - COALESCE(pl.qty_progress_adjusted,0) - COALESCE(pl.qty_closed,0)) as outstanding_qty, COUNT(*) as line_count, MIN(p.date) as oldest_date')
            ->get();

        $activeByOperator = $activeRows->groupBy('operator_id')->map(function (Collection $items) {
            $displayItems = $items
                ->sortByDesc('outstanding_qty')
                ->take(2)
                ->values()
                ->map(function ($row) {
                    return [
                        'item_code' => (string) ($row->item_code ?? ''),
                        'item_name' => (string) ($row->item_name ?? ''),
                        'outstanding_qty' => round((float) ($row->outstanding_qty ?? 0), 0),
                    ];
                })
                ->all();

            return [
                'active_load' => round((float) $items->sum('outstanding_qty'), 0),
                'item_count' => (int) $items->count(),
                'items' => $displayItems,
                'oldest_date' => $items->min('oldest_date'),
            ];
        });

        $operators = Employee::query()
            ->whereIn('role', ['sewing', 'operating'])
            ->where('active', true)
            ->orderBy('code')
            ->get()
            ->map(function (Employee $employee) use ($performanceRows, $activeByOperator) {
                $perf = $performanceRows->get($employee->id);
                $active = $activeByOperator->get($employee->id, [
                    'active_load' => 0,
                    'item_count' => 0,
                    'items' => [],
                    'oldest_date' => null,
                ]);

                $totalOk = (float) ($perf->total_ok ?? 0);
                $totalReject = (float) ($perf->total_reject ?? 0);
                $activeDays = max(1, (int) ($perf->active_days ?? 0));
                $throughput = $totalOk / $activeDays;
                $qualityRate = ($totalOk + $totalReject) > 0 ? $totalOk / max($totalOk + $totalReject, 0.0001) : 0;
                $activeLoad = (float) ($active['active_load'] ?? 0);
                $score = round(($throughput * 5) + ($qualityRate * 35) - ($activeLoad * 0.25), 1);

                $note = 'Stabil';
                if ($qualityRate < 0.9) {
                    $note = 'Perlu dicek hasilnya';
                } elseif ($activeLoad < 20 && $throughput >= 25) {
                    $note = 'Cocok dikasih pickup baru';
                } elseif ($activeLoad > 60) {
                    $note = 'Lagi penuh';
                }

                return [
                    'id' => $employee->id,
                    'name' => $employee->name,
                    'code' => $employee->code,
                    'score' => $score,
                    'throughput' => round($throughput, 1),
                    'quality_rate' => round($qualityRate * 100, 1),
                    'recent_ok' => round($totalOk, 0),
                    'recent_reject' => round($totalReject, 0),
                    'active_load' => round($activeLoad, 0),
                    'active_item_count' => (int) ($active['item_count'] ?? 0),
                    'active_items' => $active['items'] ?? [],
                    'oldest_date' => $active['oldest_date'] ?? null,
                    'note' => $note,
                ];
            })
            ->sortByDesc('score')
            ->values();

        $best = $operators->first();

        return [
            'window_days' => $windowDays,
            'summary' => [
                'operator_count' => $operators->count(),
                'active_operator_count' => $activeRows->pluck('operator_id')->filter()->unique()->count(),
                'active_item_count' => $activeRows->count(),
                'active_load_total' => round((float) $activeRows->sum('outstanding_qty'), 0),
            ],
            'best' => $best,
            'operators' => $operators->take(3)->values(),
        ];
    }

    private function friendlyActionTitle(string $title, string $tab): string
    {
        $map = [
            'Tarik dari PRD' => 'Ambil stok dari produksi',
            'Buat Purchase Request' => 'Bikin pembelian',
            'Prioritaskan Produksi' => 'Kejar produksi',
            'Siapkan Transfer ke RTS' => 'Pindahkan ke stok depan',
            'Prioritaskan Jahit' => 'Ambil jahit',
            'Jadwalkan Cutting' => 'Jadwalkan potong',
            'Ambil stok dari produksi' => 'Ambil stok dari produksi',
            'Bikin pembelian' => 'Bikin pembelian',
            'Kejar produksi' => 'Kejar produksi',
            'Pindahkan ke stok depan' => 'Pindahkan ke stok depan',
            'Ambil jahit' => 'Ambil jahit',
            'Jadwalkan potong' => 'Jadwalkan potong',
        ];

        return $map[$title] ?? $title;
    }

    private function friendlyActionLabel(string $label, string $tab): string
    {
        $label = trim($label);
        if ($label === '') {
            return 'Cek segera';
        }

        $replacements = [
            'Kirim' => 'Siapkan',
            'Perlu beli' => 'Perlu dibeli',
            'Cek cutting / jahit' => 'Perlu dikerjakan',
            'Ambil WIP' => 'Ambil stok proses',
                'Kejar 30 hari cover' => 'Kejar 30 hari',
            ];

        return strtr($label, $replacements);
    }

    private function friendlySignalLabel(string $label): string
    {
        return match ($label) {
            'SKU kritis' => 'Perlu perhatian',
            'Total saran restock' => 'Isi ulang',
            'Momentum naik' => 'Lagi naik',
            'Siap tarik PRD', 'Siap transfer' => 'Siap dipindah',
            'WIP aktif' => 'Stok proses',
            default => $label,
        };
    }

    private function humanizeRecommendationText(string $text): string
    {
        if ($text === '') {
            return $text;
        }

        $text = strtr($text, [
            'target cover' => 'target stok aman',
            'Cover saat ini' => 'Stok aman saat ini',
            'cover saat ini' => 'stok aman saat ini',
            'cover hari' => 'stok aman bertahan hari',
            'hari cover' => 'stok aman bertahan hari',
            'cover' => 'stok aman bertahan',
            'WH-RTS' => 'stok depan',
            'WH-PRD' => 'stok produksi',
            'RTS' => 'stok depan',
            'PRD' => 'stok produksi',
            'WIP' => 'stok proses',
            'cutting' => 'potong',
            'replenishment display' => 'isi ulang display',
        ]);

        return preg_replace_callback('/\b(\d+)\s*pcs\b/i', function (array $matches): string {
            return $this->formatSmartQty((int) $matches[1]);
        }, $text) ?? $text;
    }

    private function formatSmartQty(float|int $qty): string
    {
        $normalized = max(0, (int) round($qty));

        if ($normalized <= 12) {
            return number_format($normalized, 0, ',', '.') . ' pcs';
        }

        $lusin = intdiv($normalized, 12);
        $sisa = $normalized % 12;

        if ($sisa === 0) {
            return $lusin . ' lusin';
        }

        return $lusin . ' lusin ' . $sisa . ' pcs';
    }

    private function formatSmartDays(float|int $days): string
    {
        $normalized = max(0, round((float) $days, 1));
        if (abs($normalized - round($normalized)) < 0.05) {
            return number_format((int) round($normalized), 0, ',', '.') . ' hari';
        }

        return number_format($normalized, 1, ',', '.') . ' hari';
    }

    private function purchaseRequestQtyForOneMonth(object $row): int
    {
        $ads = max(0, (float) ($row->ads ?? 0));
        if ($ads <= 0) {
            return 1;
        }

        $target30d = (int) ceil($ads * 30);
        $available = max(0, (float) (($row->ready ?? 0) - ($row->ready_allocated ?? 0)));

        return max(1, $target30d - (int) round($available));
    }

    private function normalizeProductionSourceGroup(object $row): string
    {
        $source = strtolower(trim((string) ($row->production_source ?? '')));
        $sourceKey = strtolower(trim((string) ($row->production_source_key ?? '')));

        if ($source === Item::PRODUCTION_BUY) {
            return 'buy';
        }

        if ($source === Item::PRODUCTION_OUTSOURCE) {
            return 'outsource';
        }

        if ($source === Item::PRODUCTION_IN_HOUSE || $sourceKey === 'own') {
            return 'in_house';
        }

        if ($sourceKey === 'external') {
            return 'outsource';
        }

        return 'unknown';
    }

    private function productionSourceGroupLabel(string $group): string
    {
        return match ($group) {
            'in_house' => 'Produksi sendiri',
            'buy' => 'Perlu beli',
            'outsource' => 'Makloon / outsource',
            default => 'Belum jelas',
        };
    }

    private function productionSourceSummary(Collection $rows): array
    {
        $counts = $rows->countBy(fn ($row) => $this->normalizeProductionSourceGroup($row));

        return [
            'in_house' => (int) ($counts->get('in_house') ?? 0),
            'buy' => (int) ($counts->get('buy') ?? 0),
            'outsource' => (int) ($counts->get('outsource') ?? 0),
            'unknown' => (int) ($counts->get('unknown') ?? 0),
        ];
    }

    private function generateAiInsights(string $tab, array $filters, array $summary, Collection $rows, Collection $signals, Collection $actions, Collection $watchlist): ?array
    {
        $apiKey = config('services.openai.key');
        if (! $apiKey) {
            return null;
        }

        $priorityCandidates = $this->buildPriorityActions($tab, $rows);

        $payloadRows = $rows
            ->sortByDesc(fn ($r) => (float) (($r->eval_score ?? 0) * 1000 + ($r->suggested_qty ?? 0)))
            ->take(10)
            ->map(function ($r) use ($tab) {
                $available = max(0, (float) (($r->ready ?? 0) - ($r->ready_allocated ?? 0)));
                $minThreshold = $this->effectiveRtsMinDisplay($r);
                $target30d = (float) (($r->ads ?? 0) * 30);
                $productionGroup = $this->normalizeProductionSourceGroup($r);

                return [
                    'sku' => $r->sku,
                    'product' => $r->product,
                    'category' => $r->category,
                    'status' => $r->status,
                    'production_source' => $r->production_source_label ?? $r->production_source ?? '-',
                    'production_group' => $productionGroup,
                    'ready' => round((float) ($r->ready ?? 0), 2),
                    'ready_allocated' => round((float) ($r->ready_allocated ?? 0), 2),
                    'available_ready' => round($available, 2),
                    'wh_prd' => round((float) ($r->wh_prd ?? 0), 2),
                    'wip' => round((float) ($r->wip ?? 0), 2),
                    'ads' => round((float) ($r->ads ?? 0), 2),
                    'wads' => round((float) ($r->wads ?? 0), 2),
                    'ads_lift' => round((float) ($r->ads_lift ?? 0), 2),
                    'cover_days' => $r->cover_days,
                    'pipe_cover_days' => $r->pipe_cover_days,
                    'forecast_30' => round((float) ($r->forecast_30 ?? 0), 2),
                    'suggested_qty' => round((float) ($r->suggested_qty ?? 0), 2),
                    'eval_score' => round((float) ($r->eval_score ?? 0), 2),
                    'rts_min_display' => $r->rts_min_display,
                    'rts_max_display' => $r->rts_max_display,
                    'rts_min_effective' => $this->effectiveRtsMinDisplay($r),
                    'rts_max_effective' => $this->effectiveRtsMaxDisplay($r),
                    'min_threshold' => $minThreshold,
                    'target_30d' => $target30d,
                    'focus_hint' => $tab === 'rts'
                        ? ($r->wh_prd > 0 ? 'transfer' : ($productionGroup === 'buy' ? 'purchase_request' : 'production'))
                        : (($r->wh_prd > 0 && $available <= $minThreshold) ? 'transfer' : (($r->wip > 0) ? 'sewing' : 'cutting')),
                ];
            })
            ->values()
            ->all();

        $cacheKey = 'warehouse-intel:ai:' . md5(json_encode([
            'tab' => $tab,
            'filters' => $filters,
            'summary' => $summary,
            'rows' => $payloadRows,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        return Cache::remember($cacheKey, now()->addMinutes(10), function () use ($apiKey, $tab, $summary, $payloadRows, $signals, $actions, $watchlist) {
            $system = <<<'TXT'
Kamu adalah analis inventory untuk GreatFit.
Tugasmu membaca data gudang yang diberikan dan menulis rekomendasi yang benar-benar berbasis angka.
        Aturan:
        - Hanya gunakan data yang diberikan.
        - Jangan mengarang SKU, jumlah, atau status yang tidak ada.
        - Prioritaskan aksi yang paling berdampak dan paling mendesak.
        - Jika tab = rts, fokus pada transfer PRD, purchase request, dan replenishment display.
        - Jika tab = prd, fokus pada packing/transfer, sewing, dan cutting.
        - Gunakan bahasa Indonesia yang singkat, jelas, dan awam dipahami.
        - Jangan pakai istilah kaku atau kode gudang di kalimat utama.
        - Kode barang cukup diisi di field sku; jangan ditulis di overview, reason, atau evidence kecuali benar-benar perlu.
        - Jangan pakai kata "cover"; ganti dengan kalimat seperti "stok aman bertahan" atau "cukup untuk".
        - Kalau menyebut jumlah di atas 12, tulis dalam format lusin dan pcs.
        - Beri alasan singkat yang menyebut angka/data yang dipakai.
        Output harus JSON sesuai schema.
TXT;

            $payload = [
                'tab' => $tab,
                'summary' => $summary,
                'signals' => $signals->values()->all(),
                'heuristic_actions' => $actions->values()->all(),
                'watchlist' => $watchlist->values()->all(),
                'priorities' => $priorityCandidates->values()->all(),
                'rows' => $payloadRows,
                'response_format' => [
                    'overview' => 'string',
                    'confidence' => 'low|medium|high',
                    'signals' => [
                        [
                            'label' => 'string',
                            'value' => 'string',
                            'evidence' => 'string',
                        ],
                    ],
                    'actions' => [
                        [
                            'title' => 'string',
                            'sku' => 'string',
                            'label' => 'string',
                            'reason' => 'string',
                            'tone' => 'success|warning|danger|info',
                        ],
                    ],
                    'watchlist' => [
                        [
                            'sku' => 'string',
                            'reason' => 'string',
                        ],
                    ],
                ],
            ];

            $response = Http::withToken($apiKey)
                ->acceptJson()
                ->timeout(35)
                ->post('https://api.openai.com/v1/responses', [
                    'model' => config('services.openai.model', 'gpt-5.6-terra'),
                    'input' => [
                        [
                            'role' => 'system',
                            'content' => [
                                ['type' => 'input_text', 'text' => $system],
                            ],
                        ],
                        [
                            'role' => 'user',
                            'content' => [
                                ['type' => 'input_text', 'text' => json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)],
                            ],
                        ],
                    ],
                    'text' => [
                        'format' => [
                            'type' => 'json_schema',
                            'name' => 'warehouse_intelligence_response',
                            'schema' => [
                                'type' => 'object',
                                'additionalProperties' => false,
                                'properties' => [
                                    'overview' => ['type' => 'string'],
                                    'confidence' => [
                                        'type' => 'string',
                                        'enum' => ['low', 'medium', 'high'],
                                    ],
                                    'signals' => [
                                        'type' => 'array',
                                        'items' => [
                                            'type' => 'object',
                                            'additionalProperties' => false,
                                            'properties' => [
                                                'label' => ['type' => 'string'],
                                                'value' => ['type' => 'string'],
                                                'evidence' => ['type' => 'string'],
                                            ],
                                            'required' => ['label', 'value', 'evidence'],
                                        ],
                                    ],
                                    'actions' => [
                                        'type' => 'array',
                                        'items' => [
                                            'type' => 'object',
                                            'additionalProperties' => false,
                                            'properties' => [
                                                'title' => ['type' => 'string'],
                                                'sku' => ['type' => 'string'],
                                                'label' => ['type' => 'string'],
                                                'reason' => ['type' => 'string'],
                                                'tone' => [
                                                    'type' => 'string',
                                                    'enum' => ['success', 'warning', 'danger', 'info'],
                                                ],
                                            ],
                                            'required' => ['title', 'sku', 'label', 'reason', 'tone'],
                                        ],
                                    ],
                                    'watchlist' => [
                                        'type' => 'array',
                                        'items' => [
                                            'type' => 'object',
                                            'additionalProperties' => false,
                                            'properties' => [
                                                'sku' => ['type' => 'string'],
                                                'reason' => ['type' => 'string'],
                                            ],
                                            'required' => ['sku', 'reason'],
                                        ],
                                    ],
                                    'priorities' => [
                                        'type' => 'array',
                                        'items' => [
                                            'type' => 'object',
                                            'additionalProperties' => false,
                                            'properties' => [
                                                'title' => ['type' => 'string'],
                                                'sku' => ['type' => 'string'],
                                                'label' => ['type' => 'string'],
                                                'reason' => ['type' => 'string'],
                                                'tone' => [
                                                    'type' => 'string',
                                                    'enum' => ['success', 'warning', 'danger', 'info'],
                                                ],
                                                'qty_label' => ['type' => 'string'],
                                                'days_label' => ['type' => 'string'],
                                            ],
                                            'required' => ['title', 'sku', 'label', 'reason', 'tone', 'qty_label', 'days_label'],
                                        ],
                                    ],
                                ],
                                'required' => ['overview', 'confidence', 'signals', 'actions', 'watchlist', 'priorities'],
                            ],
                            'strict' => true,
                        ],
                    ],
                ]);

            if (! $response->successful()) {
                return null;
            }

            $json = $response->json();
            $text = data_get($json, 'output_text');

            if (! $text) {
                $text = collect(data_get($json, 'output', []))
                    ->flatMap(fn ($item) => data_get($item, 'content', []))
                    ->pluck('text')
                    ->filter()
                    ->implode('');
            }

            $parsed = json_decode((string) $text, true);
            if (! is_array($parsed)) {
                return null;
            }

            return [
                'overview' => Str::limit(trim((string) ($parsed['overview'] ?? '')), 260, ''),
                'confidence' => in_array(($parsed['confidence'] ?? ''), ['low', 'medium', 'high'], true) ? $parsed['confidence'] : 'medium',
                'signals' => collect($parsed['signals'] ?? [])->filter()->values()->all(),
                'actions' => collect($parsed['actions'] ?? [])->filter()->values()->all(),
                'watchlist' => collect($parsed['watchlist'] ?? [])->filter()->values()->all(),
                'priorities' => collect($parsed['priorities'] ?? [])->filter()->values()->all(),
            ];
        });
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
            'rts_min_display' => $this->normalizeDisplayLimit($request->rts_min_display),
            'rts_max_display' => $this->normalizeDisplayLimit($request->rts_max_display),
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
