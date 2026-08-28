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
use Illuminate\Support\Facades\DB;

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
            'daily' => [
                'label' => 'Harian',
                'generator' => \App\Services\Payroll\DailyPayrollGenerator::class,
                'views' => 'payroll.piecework',
                'allow_slip_all' => false,
            ],
        ];

        abort_unless(isset($map[$module]), 404);

        return $map[$module] + ['module' => $module];
    }

    /**
     * OVERVIEW: daftar periode cutting + sewing dalam satu halaman.
     */
    public function overview(Request $request): View
    {
        $module = strtolower((string) $request->input('module', 'all'));
        $allowedModules = ['all', 'cutting', 'sewing', 'daily'];

        if (!in_array($module, $allowedModules, true)) {
            $module = 'all';
        }

        $query = PieceworkPayrollPeriod::query()
            ->withSum('lines as lines_total_qty', 'total_qty_ok')
            ->withSum('lines as lines_total_amount', 'amount')
            ->selectSub(
                PieceworkPayrollLine::query()
                    ->selectRaw('COUNT(DISTINCT employee_id)')
                    ->whereColumn('payroll_period_id', 'piecework_payroll_periods.id'),
                'operator_count'
            )
            ->orderByDesc('period_start')
            ->orderByDesc('id');

        if ($module !== 'all') {
            $query->where('module', $module);
        }

        if ($request->filled('from')) {
            $query->whereDate('period_start', '>=', $request->input('from'));
        }

        if ($request->filled('to')) {
            $query->whereDate('period_end', '<=', $request->input('to'));
        }

        $periods = $query->paginate(15)->withQueryString();

        return view('payroll.piecework.overview', [
            'module' => $module,
            'periods' => $periods,
        ]);
    }

    /**
     * STORE OVERVIEW: generate periode dari form gabungan.
     */
    public function storeOverview(Request $request): RedirectResponse
    {
        $request->validate([
            'module' => ['required', 'in:cutting,sewing,daily'],
        ], [
            'module.required' => 'Modul payroll wajib dipilih.',
            'module.in' => 'Modul payroll tidak valid.',
        ]);

        return $this->store($request, (string) $request->input('module'), true);
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
    public function store(Request $request, string $module, bool $returnToOverview = false): RedirectResponse
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
            $redirect = $returnToOverview
                ? redirect()->route('payroll.piecework.overview', ['module' => $cfg['module']])
                : redirect()->route('payroll.piecework.show', [$cfg['module'], $existing]);

            return $redirect
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

        if ($returnToOverview) {
            return redirect()
                ->route('payroll.piecework.overview', ['module' => $cfg['module']])
                ->with('status', $message);
        }

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
            ->when($cfg['module'] === 'daily', function ($query) {
                return $query->orderBy('work_date')->orderBy('employee_id');
            }, function ($query) {
                return $query->orderBy('employee_id')
                    ->orderBy('item_category_id')
                    ->orderBy('item_id');
            })
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

        if ($cfg['module'] === 'daily') {
            $summaryByEmployee = $lines
                ->groupBy('employee_id')
                ->map(function ($group) {
                    $employee = $group->first()->employee;

                    return [
                        'employee_id' => $employee?->id,
                        'employee_name' => $employee?->name ?? '-',
                        'total_qty' => (float) $group->sum('attendance_factor'),
                        'total_amount' => (float) $group->sum('amount'),
                    ];
                })
                ->values();

            $grandTotalQty = (float) $lines->sum('attendance_factor');
        }

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
     * Update kehadiran satu baris payroll harian.
     */
    public function updateDailyLine(Request $request, string $module, PieceworkPayrollPeriod $period, PieceworkPayrollLine $line): RedirectResponse
    {
        $cfg = $this->moduleConfig($module);
        abort_unless($cfg['module'] === 'daily', 404);
        abort_unless((int) $line->payroll_period_id === (int) $period->id, 404);

        if ($period->status === 'final' || $period->paid_at) {
            return back()->with('error', 'Payroll harian yang sudah FINAL atau dibayar tidak bisa diubah.');
        }

        $data = $request->validate([
            'attendance_status' => ['required', 'in:pending,hadir,setengah_hari,izin,sakit,libur'],
        ]);

        $factor = match ($data['attendance_status']) {
            'hadir' => 1.0,
            'setengah_hari' => 0.5,
            default => 0.0,
        };
        $rate = round((float) ($line->rate_per_day ?: $line->rate_per_pcs), 2);
        $amount = round($factor * $rate, 2);

        DB::transaction(function () use ($period, $line, $data, $factor, $rate, $amount): void {
            $lockedPeriod = PieceworkPayrollPeriod::query()
                ->lockForUpdate()
                ->findOrFail($period->id);
            $lockedLine = PieceworkPayrollLine::query()
                ->where('payroll_period_id', $lockedPeriod->id)
                ->lockForUpdate()
                ->findOrFail($line->id);

            $lockedLine->forceFill([
                'attendance_status' => $data['attendance_status'],
                'attendance_factor' => $factor,
                'rate_per_day' => $rate,
                'rate_per_pcs' => $rate,
                'total_qty_ok' => $factor,
                'amount' => $amount,
            ])->save();

            $lockedPeriod->forceFill([
                'total_amount' => $lockedPeriod->lines()->sum('amount'),
            ])->save();
        });

        return back()->with('status', 'Kehadiran payroll harian berhasil disimpan.');
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

        if ($cfg['module'] === 'daily' && $period->lines()->where('attendance_status', 'pending')->exists()) {
            return redirect()
                ->route('payroll.piecework.show', [$cfg['module'], $period])
                ->with('error', 'Lengkapi status kehadiran payroll harian sebelum difinalkan.');
        }

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

    /**
     * DESTROY: hapus periode payroll yang masih draft beserta detailnya.
     */
    public function destroy(string $module, PieceworkPayrollPeriod $period): RedirectResponse
    {
        $cfg = $this->moduleConfig($module);
        abort_unless($period->module === $cfg['module'], 404);

        if (
            $period->status !== 'draft'
            || $period->paid_at
            || $period->finalized_at
            || $period->accrual_journal_id
            || $period->payment_journal_id
        ) {
            return redirect()
                ->route('payroll.piecework.overview', ['module' => $cfg['module']])
                ->with('error', 'Payroll yang sudah FINAL atau memiliki pencatatan akuntansi tidak bisa dihapus.');
        }

        DB::transaction(function () use ($period): void {
            $period->lines()->delete();
            $period->delete();
        });

        return redirect()
            ->route('payroll.piecework.overview', ['module' => $cfg['module']])
            ->with('status', "Payroll {$cfg['label']} periode " . id_date($period->period_start) . ' s/d ' . id_date($period->period_end) . ' berhasil dihapus.');
    }
}
