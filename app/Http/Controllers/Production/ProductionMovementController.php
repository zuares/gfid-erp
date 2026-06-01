<?php

namespace App\Http\Controllers\Production;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Item;
use App\Models\ItemCategory;
use App\Services\Production\ProductionFlowService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductionMovementController extends Controller
{
    public function __construct(private ProductionFlowService $flow)
    {
    }

    /** Daftar mutasi produksi + form tombol aksi. */
    public function index(Request $request): View
    {
        $today = Carbon::today();
        $filters = [
            'date_from' => $request->input('date_from') ?: $today->copy()->subDays(29)->toDateString(),
            'date_to' => $request->input('date_to') ?: $today->toDateString(),
            'item_id' => $request->input('item_id') ?: null,
            'category_id' => $request->input('category_id') ?: null,
            'operator_id' => $request->input('operator_id') ?: null,
            'to_status' => $request->input('to_status') ?: null,
        ];

        $movements = $this->flow->movementsQuery($filters)->paginate(30)->withQueryString();

        return view('production.movements.index', [
            'filters' => $filters,
            'movements' => $movements,
            'statuses' => $this->flow->statuses(),
            'itemOptions' => Item::where('type', 'finished_good')->orderBy('code')->get(),
            'categoryOptions' => ItemCategory::where('active', 1)->orderBy('name')->get(),
            'operatorOptions' => Employee::where('role', 'sewing')->orderBy('code')->get(),
        ]);
    }

    /** Eksekusi tombol aksi: pindahkan stok antar status. */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'from_status' => 'required|string',
            'to_status' => 'required|string|different:from_status',
            'item_id' => 'required|integer|exists:items,id',
            'qty' => 'required|numeric|min:0.001',
            'cutting_job_bundle_id' => 'nullable|integer|exists:cutting_job_bundles,id',
            'operator_id' => 'nullable|integer|exists:employees,id',
            'deadline' => 'nullable|date',
            'date' => 'nullable|date',
            'notes' => 'nullable|string|max:1000',
        ]);

        // ProductionFlowService::move() melempar ValidationException bila stok kurang dsb.
        $movement = $this->flow->move($data, $request->user()?->id);

        return redirect()
            ->route('production.movements.index')
            ->with('success', "Mutasi {$movement->code} berhasil: {$movement->qty} pcs {$movement->from_status} → {$movement->to_status}.");
    }
}
