<?php

namespace App\Http\Controllers\Purchasing;

use App\Helpers\CodeGenerator;
use App\Http\Controllers\Controller;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestLine;
use App\Models\Supplier;
use App\Services\Purchasing\MaterialShortageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class MaterialShortageController extends Controller
{
    public function __construct(
        protected MaterialShortageService $shortages,
    ) {}

    public function index(Request $request): View
    {
        $rows = $this->shortages->rows();
        $q = trim((string) $request->input('q', ''));
        $status = (string) $request->input('status', 'shortage');
        $status = in_array($status, ['shortage', 'safe', 'all'], true) ? $status : 'shortage';

        $allRows = $this->shortages->rows();
        $summary = [
            'materials' => $allRows->count(),
            'shortage_count' => $allRows->where('has_shortage', true)->count(),
            'covered_count' => $allRows->where('has_shortage', false)->count(),
            'open_pr_count' => PurchaseRequest::whereIn('status', ['draft', 'approved'])->count(),
        ];

        $suppliers = Supplier::query()->where('active', true)->orderBy('name')->get(['id', 'code', 'name']);

        return view('purchasing.material_shortages.index', compact('rows', 'summary', 'suppliers', 'status', 'q'));
    }

    public function createPurchaseRequest(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'item_ids' => ['required', 'array', 'min:1'],
            'item_ids.*' => ['integer', 'distinct', 'exists:items,id'],
            'supplier_id' => ['nullable', 'integer', 'exists:suppliers,id'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $selected = collect($validated['item_ids'])->map(fn($id) => (int) $id)->unique();
        $rows = $this->shortages->rows()
            ->whereIn('item_id', $selected)
            ->filter(fn($row) => $row->shortage_qty > 0.000001)
            ->values();

        if ($rows->isEmpty()) {
            throw ValidationException::withMessages([
                'item_ids' => 'Item yang dipilih sudah tidak kekurangan setelah stok, PR, dan PO dihitung ulang.',
            ]);
        }

        $pr = DB::transaction(function () use ($request, $validated, $rows) {
            $pr = PurchaseRequest::create([
                'code' => CodeGenerator::make('PR'),
                'date' => now()->toDateString(),
                'supplier_id' => $validated['supplier_id'] ?? null,
                'requested_by' => (int) $request->user()->id,
                'status' => 'draft',
                'notes' => trim("Dibuat otomatis dari Material Shortage.\n" . ($validated['notes'] ?? '')),
            ]);

            foreach ($rows as $row) {
                $qty = ceil((float) $row->shortage_qty * 100) / 100;
                PurchaseRequestLine::create([
                    'purchase_request_id' => $pr->id,
                    'item_id' => $row->item_id,
                    'qty' => $qty,
                    'unit_price' => null,
                    'notes' => sprintf(
                        'Shortage: butuh %.2f, stok RM %.2f, PR %.2f, PO %.2f',
                        $row->required_qty,
                        $row->stock_qty,
                        $row->open_pr_qty,
                        $row->open_po_qty,
                    ),
                ]);
            }

            return $pr;
        });

        return redirect()
            ->route('purchasing.purchase_requests.show', $pr)
            ->with('success', "PR {$pr->code} dibuat dari Material Shortage dan siap direview.");
    }
}
