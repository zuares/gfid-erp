<?php

namespace App\Http\Controllers\Production;

use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\ItemCategory;
use App\Services\Production\ProductionPriorityService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductionPriorityController extends Controller
{
    public function __construct(private ProductionPriorityService $priority)
    {
    }

    /** Daftar prioritas produksi (skor 0–100 per SKU). */
    public function index(Request $request): View
    {
        $filters = [
            'item_id' => $request->input('item_id') ?: null,
            'category_id' => $request->input('category_id') ?: null,
            'grade' => $request->input('grade') ?: null,
        ];

        $rows = $this->priority->priorityList($filters, 150);

        if (!empty($filters['grade'])) {
            $rows = $rows->where('grade', $filters['grade'])->values();
        }

        return view('production.priority.index', [
            'filters' => $filters,
            'rows' => $rows,
            'itemOptions' => Item::where('type', 'finished_good')->orderBy('code')->get(),
            'categoryOptions' => ItemCategory::where('active', 1)->orderBy('name')->get(),
        ]);
    }
}
