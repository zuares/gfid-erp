<?php

namespace App\Http\Controllers\Costing;

use App\Http\Controllers\Controller;
use App\Models\ItemCostSnapshot;
use App\Models\PieceworkPayrollPeriod;
use App\Models\ProductionCostPeriod;
use App\Services\Costing\HppService;
use App\Services\Costing\ProductionCostService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductionCostPeriodController extends Controller
{
    public function __construct(
        protected HppService $hpp,
        protected ProductionCostService $productionCost,
    ) {}

    /**
     * List periode costing produksi.
     */
    public function index(Request $request): View
    {
        $periods = ProductionCostPeriod::query()
            ->with([
                'cuttingPayrollPeriod',
                'sewingPayrollPeriod',
                'finishingPayrollPeriod',
            ])
            ->orderByDesc('snapshot_date')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('costing.production_cost_periods.index', compact('periods'));
    }

    /**
     * Detail satu periode + hasil HPP per item (dibaca dari item_cost_snapshots).
     */
    public function show(ProductionCostPeriod $period): View
    {
        $period->load([
            'cuttingPayrollPeriod',
            'sewingPayrollPeriod',
            'finishingPayrollPeriod',
        ]);

        $snapshots = ItemCostSnapshot::query()
            ->with('item')
            ->where('reference_type', 'production_cost_period')
            ->where('reference_id', $period->id)
            ->orderBy('item_id')
            ->get();

        return view('costing.production_cost_periods.show', [
            'period' => $period,
            'snapshots' => $snapshots,
        ]);
    }

    /**
     * Jalankan generate HPP FINAL dari payroll + RM-only untuk 1 periode.
     * Delegasi penuh ke ProductionCostService.
     */
    public function generate(ProductionCostPeriod $period): RedirectResponse
    {
        // Kalau mau dilarang di-re-generate setelah posted, bisa aktifkan block ini:
        // if ($period->status === 'posted') {
        //     return back()->with('status', 'Periode sudah posted, tidak bisa generate ulang.');
        // }

        $results = $this->productionCost->generateFromPayroll($period);

        return redirect()
            ->route('costing.production_cost_periods.show', $period)
            ->with('status', "HPP periode {$period->code} berhasil digenerate untuk " . count($results) . " item.")
            ->with('generate_results', $results);
    }

    /**
     * Form edit periode HPP:
     * - nama periode
     * - range tanggal
     * - link ke payroll period (cutting / sewing / finishing)
     */
    public function edit(ProductionCostPeriod $period): View
    {
        $cuttingPeriods = PieceworkPayrollPeriod::where('module', 'cutting')
            ->where('status', 'final') // atau 'posted' sesuai skema kamu
            ->orderByDesc('period_start')
            ->get();

        $sewingPeriods = PieceworkPayrollPeriod::where('module', 'sewing')
            ->where('status', 'final')
            ->orderByDesc('period_start')
            ->get();

        $finishingPeriods = PieceworkPayrollPeriod::where('module', 'finishing')
            ->where('status', 'final')
            ->orderByDesc('period_start')
            ->get();

        return view('costing.production_cost_periods.edit', [
            'period' => $period,
            'cuttingPeriods' => $cuttingPeriods,
            'sewingPeriods' => $sewingPeriods,
            'finishingPeriods' => $finishingPeriods,
        ]);
    }

    /**
     * Simpan perubahan periode HPP + linkage ke payroll.
     */
    public function update(Request $request, ProductionCostPeriod $period): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string'],
            'date_from' => ['required', 'date'],
            'date_to' => ['required', 'date'],
            'snapshot_date' => ['required', 'date'],
            'cutting_payroll_period_id' => ['nullable', 'exists:piecework_payroll_periods,id'],
            'sewing_payroll_period_id' => ['nullable', 'exists:piecework_payroll_periods,id'],
            'finishing_payroll_period_id' => ['nullable', 'exists:piecework_payroll_periods,id'],
            'notes' => ['nullable', 'string'],
        ]);

        $period->update([
            'name' => $data['name'],
            'date_from' => $data['date_from'],
            'date_to' => $data['date_to'],
            'snapshot_date' => $data['snapshot_date'],
            'cutting_payroll_period_id' => $data['cutting_payroll_period_id'] ?? null,
            'sewing_payroll_period_id' => $data['sewing_payroll_period_id'] ?? null,
            'finishing_payroll_period_id' => $data['finishing_payroll_period_id'] ?? null,
            'notes' => $data['notes'] ?? null,
            'updated_by' => auth()->id(),
        ]);

        return redirect()
            ->route('costing.production_cost_periods.show', $period)
            ->with('status', 'Periode HPP berhasil diupdate dan link ke payroll disimpan.');
    }
}
