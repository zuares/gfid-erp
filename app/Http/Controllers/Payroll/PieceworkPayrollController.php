<?php

namespace App\Http\Controllers\Payroll;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\PieceworkPayrollLine;
use App\Models\PieceworkPayrollPeriod;
use App\Services\Payroll\PieceworkPayrollPostingService;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PieceworkPayrollController extends Controller
{
    /**
     * Mapping module -> generator + view folder + extra features
     */
    private function moduleConfig(string $module): array
    {
        $module = strtolower($module);

        $map = [
            'cutting' => [
                'label' => 'Cutting',
                'generator' => \App\Services\Payroll\CuttingPayrollGenerator::class,
                'views' => 'payroll.piecework', // ✅ PENTING
                'allow_slip_all' => false,
            ],
            'sewing' => [
                'label' => 'Sewing',
                'generator' => \App\Services\Payroll\SewingPayrollGenerator::class,
                'views' => 'payroll.piecework', // ✅ PENTING
                'allow_slip_all' => true,
            ],
        ];

        abort_unless(isset($map[$module]), 404);

        return $map[$module] + ['module' => $module];
    }

    /**
     * INDEX: daftar periode
     */
    public function index(Request $request, string $module): View
    {
        $cfg = $this->moduleConfig($module);

        $query = PieceworkPayrollPeriod::query()
            ->where('module', $cfg['module'])
            ->orderByDesc('period_start');

        if ($request->filled('from')) {
            $query->whereDate('period_start', '>=', $request->input('from'));
        }
        if ($request->filled('to')) {
            $query->whereDate('period_end', '<=', $request->input('to'));
        }

        $periods = $query->paginate(15)->withQueryString();

        return view("{$cfg['views']}.index", [
            'module' => $cfg['module'],
            'moduleLabel' => $cfg['label'],
            'periods' => $periods,
        ]);
    }

    /**
     * CREATE: form pilih periode
     */
    public function create(Request $request, string $module): View
    {
        $cfg = $this->moduleConfig($module);

        $defaultEnd = Carbon::today();
        $defaultStart = (clone $defaultEnd)->subDays(6);

        return view("{$cfg['views']}.create", [
            'module' => $cfg['module'],
            'moduleLabel' => $cfg['label'],
            'defaultStart' => $defaultStart->toDateString(),
            'defaultEnd' => $defaultEnd->toDateString(),
        ]);
    }

    /**
     * STORE: generate (draft) untuk periode start/end
     * - kalau existing draft: regenerate in-place
     * - kalau existing final: blok
     */
    public function store(Request $request, string $module): RedirectResponse
    {
        $cfg = $this->moduleConfig($module);

        $data = $request->validate([
            'period_start' => ['required', 'date'],
            'period_end' => ['required', 'date', 'after_or_equal:period_start'],
        ], [
            'period_start.required' => 'Tanggal awal periode wajib diisi.',
            'period_end.required' => 'Tanggal akhir periode wajib diisi.',
            'period_end.after_or_equal' => 'Tanggal akhir tidak boleh lebih awal dari tanggal awal.',
        ]);

        $existing = PieceworkPayrollPeriod::query()
            ->where('module', $cfg['module'])
            ->whereDate('period_start', $data['period_start'])
            ->whereDate('period_end', $data['period_end'])
            ->first();

        if ($existing && $existing->status === 'final') {
            return redirect()
                ->route('payroll.piecework.show', [$cfg['module'], $existing])
                ->with('error', "Periode payroll {$cfg['label']} ini sudah FINAL, tidak bisa digenerate ulang.");
        }

        $generatorClass = $cfg['generator'];

        /** @var \App\Models\PieceworkPayrollPeriod $period */
        $period = $generatorClass::generate(
            periodStart: $data['period_start'],
            periodEnd: $data['period_end'],
            createdByUserId: Auth::id(),
            existingPeriod: $existing // boleh null, generator kamu sudah support ini di cutting & sewing (pastikan sewing juga)
        );

        $message = $existing
        ? "Payroll {$cfg['label']} periode " . id_date($period->period_start) . " s/d " . id_date($period->period_end) . " berhasil di-UPDATE (regenerate)."
        : "Payroll {$cfg['label']} berhasil digenerate untuk periode " . id_date($period->period_start) . " s/d " . id_date($period->period_end) . ".";

        return redirect()
            ->route('payroll.piecework.show', [$cfg['module'], $period])
            ->with('status', $message);
    }

    /**
     * SHOW: detail periode + summary
     */
    public function show(string $module, PieceworkPayrollPeriod $period): View
    {
        $cfg = $this->moduleConfig($module);
        abort_unless($period->module === $cfg['module'], 404);

        $lines = PieceworkPayrollLine::query()
            ->with(['employee', 'category', 'item'])
            ->where('payroll_period_id', $period->id)
            ->orderBy('employee_id')
            ->orderBy('item_category_id')
            ->orderBy('item_id')
            ->get();

        $summaryByEmployee = $lines
            ->groupBy('employee_id')
            ->map(function ($group) {
                $employee = $group->first()->employee;

                return [
                    'employee_id' => $employee?->id,
                    'employee_name' => $employee?->name ?? '-',
                    'total_qty' => (float) $group->sum('total_qty_ok'),
                    'total_amount' => (float) $group->sum('amount'),
                ];
            })
            ->values();

        $grandTotalQty = (float) $lines->sum('total_qty_ok');
        $grandTotalAmount = (float) $lines->sum('amount');

        $cashAccounts = Account::query()
            ->where('is_cash', true)
            ->where('is_active', true)
            ->orderBy('code')
            ->get();

        return view("{$cfg['views']}.show", [
            'module' => $cfg['module'],
            'moduleLabel' => $cfg['label'],
            'period' => $period,
            'lines' => $lines,
            'summaryByEmployee' => $summaryByEmployee,
            'grandTotalQty' => $grandTotalQty,
            'grandTotalAmount' => $grandTotalAmount,
            'cashAccounts' => $cashAccounts,
            'allowSlipAll' => (bool) $cfg['allow_slip_all'],
        ]);
    }

    /**
     * SLIP per employee
     */
    public function slip(string $module, PieceworkPayrollPeriod $period, int $employeeId): View
    {
        $cfg = $this->moduleConfig($module);
        abort_unless($period->module === $cfg['module'], 404);

        $lines = PieceworkPayrollLine::query()
            ->with(['employee', 'category', 'item'])
            ->where('payroll_period_id', $period->id)
            ->where('employee_id', $employeeId)
            ->orderBy('item_category_id')
            ->orderBy('item_id')
            ->get();

        abort_if($lines->isEmpty(), 404, 'Operator tidak memiliki data pada periode ini.');

        $employee = $lines->first()->employee;

        $totalQty = (float) $lines->sum('total_qty_ok');
        $totalAmount = (float) $lines->sum('amount');

        return view("{$cfg['views']}.slip", [
            'module' => $cfg['module'],
            'moduleLabel' => $cfg['label'],
            'period' => $period,
            'employee' => $employee,
            'lines' => $lines,
            'totalQty' => $totalQty,
            'totalAmount' => $totalAmount,
        ]);
    }

    /**
     * SLIP ALL (khusus module yang allow)
     */
    public function slipAll(string $module, PieceworkPayrollPeriod $period): View
    {
        $cfg = $this->moduleConfig($module);
        abort_unless($cfg['allow_slip_all'], 404);
        abort_unless($period->module === $cfg['module'], 404);

        $lines = PieceworkPayrollLine::query()
            ->with(['employee', 'category', 'item'])
            ->where('payroll_period_id', $period->id)
            ->orderBy('employee_id')
            ->orderBy('item_category_id')
            ->orderBy('item_id')
            ->get();

        abort_if($lines->isEmpty(), 404, 'Tidak ada data payroll untuk periode ini.');

        $byEmployee = $lines
            ->groupBy('employee_id')
            ->map(function ($group) {
                $employee = $group->first()->employee;

                return [
                    'employee' => $employee,
                    'lines' => $group,
                    'total_qty' => (float) $group->sum('total_qty_ok'),
                    'total_amount' => (float) $group->sum('amount'),
                ];
            });

        $grandTotalQty = (float) $lines->sum('total_qty_ok');
        $grandTotalAmount = (float) $lines->sum('amount');

        return view("{$cfg['views']}.slip_all", [
            'module' => $cfg['module'],
            'moduleLabel' => $cfg['label'],
            'period' => $period,
            'byEmployee' => $byEmployee,
            'grandTotalQty' => $grandTotalQty,
            'grandTotalAmount' => $grandTotalAmount,
        ]);
    }

    /**
     * FINALIZE: Dr HPP, Cr Hutang Upah Borongan
     */
    public function finalize(string $module, PieceworkPayrollPeriod $period, PieceworkPayrollPostingService $svc): RedirectResponse
    {
        $cfg = $this->moduleConfig($module);
        abort_unless($period->module === $cfg['module'], 404);

        try {
            $svc->finalize($period);

            return redirect()
                ->route('payroll.piecework.show', [$cfg['module'], $period])
                ->with('status', "Periode payroll {$cfg['label']} berhasil difinalkan (HPP + Hutang dicatat).");
        } catch (\Throwable $e) {
            return redirect()
                ->route('payroll.piecework.show', [$cfg['module'], $period])
                ->with('error', $e->getMessage());
        }
    }

    /**
     * PAY: Dr Hutang Upah Borongan, Cr Kas/Bank
     */
    public function pay(Request $request, string $module, PieceworkPayrollPeriod $period, PieceworkPayrollPostingService $svc): RedirectResponse
    {
        $cfg = $this->moduleConfig($module);
        abort_unless($period->module === $cfg['module'], 404);

        $data = $request->validate([
            'paid_from_account_id' => ['required', 'integer', 'exists:accounts,id'],
        ]);

        try {
            $svc->pay($period, (int) $data['paid_from_account_id']);

            return redirect()
                ->route('payroll.piecework.show', [$cfg['module'], $period])
                ->with('status', "Pembayaran payroll {$cfg['label']} berhasil dicatat (Hutang dilunasi).");
        } catch (\Throwable $e) {
            return redirect()
                ->route('payroll.piecework.show', [$cfg['module'], $period])
                ->with('error', $e->getMessage());
        }
    }

    /**
     * REGENERATE: hitung ulang in-place (hanya jika belum final)
     */
    public function regenerate(string $module, PieceworkPayrollPeriod $period): RedirectResponse
    {
        $cfg = $this->moduleConfig($module);
        abort_unless($period->module === $cfg['module'], 404);

        if ($period->status === 'final') {
            return redirect()
                ->route('payroll.piecework.show', [$cfg['module'], $period])
                ->with('error', 'Periode yang sudah FINAL tidak boleh digenerate ulang.');
        }

        $generatorClass = $cfg['generator'];

        $period = $generatorClass::generate(
            periodStart: $period->period_start,
            periodEnd: $period->period_end,
            createdByUserId: Auth::id(),
            existingPeriod: $period, // in-place
        );

        return redirect()
            ->route('payroll.piecework.show', [$cfg['module'], $period])
            ->with('status', "Payroll {$cfg['label']} berhasil digenerate ulang.");
    }
}
