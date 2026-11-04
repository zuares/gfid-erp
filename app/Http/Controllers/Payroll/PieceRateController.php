<?php

namespace App\Http\Controllers\Payroll;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Item;
use App\Models\ItemCategory;
use App\Models\PieceRate;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PieceRateController extends Controller
{
    /**
     * List Piece Rate dengan filter sederhana
     */
    public function index(Request $request): View
    {
        $query = PieceRate::query()
            ->with(['employee', 'item', 'category'])
            ->orderBy('module')
            ->orderBy('employee_id');

        if ($request->filled('module')) {
            $query->where('module', $request->input('module'));
        }

        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->input('employee_id'));
        }

        if ($request->filled('item_id')) {
            $query->where('item_id', $request->input('item_id'));
        }

        if ($request->filled('item_category_id')) {
            $query->where('item_category_id', $request->input('item_category_id'));
        }

        $rates = $query->paginate(20)->withQueryString();

        $employees = Employee::orderBy('name')->get();
        $items = Item::orderBy('code')->get();
        $categories = ItemCategory::orderBy('name')->get();

        // untuk pilihan modul, simple hardcoded dulu
        $modules = [
            'cutting' => 'Cutting',
            'sewing' => 'Sewing',
        ];

        return view('payroll.piece_rates.index', compact(
            'rates',
            'employees',
            'items',
            'categories',
            'modules'
        ));
    }

    /**
     * Form create Piece Rate
     */
    public function create(): View
    {
        $employees = Employee::orderBy('name')->get();
        $items = Item::orderBy('code')->get();
        $categories = ItemCategory::orderBy('name')->get();

        $modules = [
            'cutting' => 'Cutting',
            'sewing' => 'Sewing',
        ];

        $rate = new PieceRate([
            'module' => 'cutting',
            'rate_per_pcs' => 0,
            'effective_from' => now()->toDateString(),
        ]);

        return view('payroll.piece_rates.create', compact(
            'rate',
            'employees',
            'items',
            'categories',
            'modules'
        ));
    }

    /**
     * Store Piece Rate baru
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateData($request);

        PieceRate::create($data);

        return redirect()
            ->route('payroll.piece_rates.index')
            ->with('status', 'Tarif borongan berhasil ditambahkan.');
    }

    /**
     * Form edit Piece Rate
     */
    public function edit(PieceRate $pieceRate): View
    {
        $employees = Employee::orderBy('name')->get();
        $items = Item::orderBy('code')->get();
        $categories = ItemCategory::orderBy('name')->get();

        $modules = [
            'cutting' => 'Cutting',
            'sewing' => 'Sewing',
        ];

        $rate = $pieceRate;

        return view('payroll.piece_rates.edit', compact(
            'rate',
            'employees',
            'items',
            'categories',
            'modules'
        ));
    }

    /**
     * Update Piece Rate
     */
    public function update(Request $request, PieceRate $pieceRate): RedirectResponse
    {
        $data = $this->validateData($request);

        $pieceRate->update($data);

        return redirect()
            ->route('payroll.piece_rates.index')
            ->with('status', 'Tarif borongan berhasil diperbarui.');
    }

    /**
     * Hapus Piece Rate
     */
    public function destroy(PieceRate $pieceRate): RedirectResponse
    {
        $pieceRate->delete();

        return redirect()
            ->route('payroll.piece_rates.index')
            ->with('status', 'Tarif borongan berhasil dihapus.');
    }

    /**
     * Validasi input
     */
    protected function validateData(Request $request): array
    {
        $data = $request->validate([
            'module' => ['required', 'string', 'in:cutting,sewing'],
            'employee_id' => ['required', 'exists:employees,id'],
            'item_id' => ['nullable', 'exists:items,id'],
            'item_category_id' => ['required', 'exists:item_categories,id'],
            'rate_per_pcs' => ['required', 'numeric', 'min:0'],
            'effective_from' => ['required', 'date'],
            'effective_to' => ['nullable', 'date', 'after_or_equal:effective_from'],
        ], [
            'module.required' => 'Module wajib diisi.',
            'employee_id.required' => 'Operator wajib dipilih.',
            'rate_per_pcs.required' => 'Tarif per pcs wajib diisi.',
        ]);

        $itemId = (int) ($data['item_id'] ?? 0);
        $categoryId = (int) ($data['item_category_id'] ?? 0);

        if ($itemId > 0) {
            $item = Item::query()->find($itemId);

            if (! $item || (int) $item->item_category_id !== $categoryId) {
                throw ValidationException::withMessages([
                    'item_id' => 'Item harus masuk ke kategori yang dipilih pada tarif piece rate.',
                ]);
            }
        }

        return $data;
    }
}
