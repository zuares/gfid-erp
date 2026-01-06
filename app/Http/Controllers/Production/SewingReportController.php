<?php

namespace App\Http\Controllers\Production;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Item;
use App\Models\SewingPickup;
use App\Models\SewingPickupLine;
use App\Models\SewingReturnLine;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class SewingReportController extends Controller
{

    public function operatorSummary(Request $request): View
    {
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');
        $operatorId = $request->input('operator_id');

        $query = SewingPickup::query()
            ->join('sewing_pickup_lines', 'sewing_pickup_lines.sewing_pickup_id', '=', 'sewing_pickups.id')
            ->join('employees as operators', 'operators.id', '=', 'sewing_pickups.operator_id')
            ->selectRaw('
                sewing_pickups.operator_id,
                operators.code    as operator_code,
                operators.name    as operator_name,
                COUNT(DISTINCT sewing_pickups.id) as total_pickups,
                SUM(sewing_pickup_lines.qty_bundle) as total_picked,
                SUM(sewing_pickup_lines.qty_returned_ok) as total_returned_ok,
                SUM(sewing_pickup_lines.qty_returned_reject) as total_returned_reject,
                SUM(
                    sewing_pickup_lines.qty_bundle
                    - sewing_pickup_lines.qty_returned_ok
                    - sewing_pickup_lines.qty_returned_reject
                ) as total_outstanding
            ')
            ->when($dateFrom, function ($q) use ($dateFrom) {
                $q->whereDate('sewing_pickups.date', '>=', $dateFrom);
            })
            ->when($dateTo, function ($q) use ($dateTo) {
                $q->whereDate('sewing_pickups.date', '<=', $dateTo);
            })
            ->when($operatorId, function ($q) use ($operatorId) {
                $q->where('sewing_pickups.operator_id', $operatorId);
            })
            ->groupBy(
                'sewing_pickups.operator_id',
                'operators.code',
                'operators.name'
            )
            ->orderBy('operators.code');

        $rows = $query->get();

        $operators = Employee::where('role', 'sewing')
            ->orderBy('code')
            ->get();

        return view('production.reports.sewing.report_operators', [
            'rows' => $rows,
            'operators' => $operators,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'operatorId' => $operatorId,
        ]);
    }

    /**
     * Detailed report: bundles / lines with outstanding qty (not fully returned yet).
     */
    public function outstanding(Request $request): View
    {
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');
        $operatorId = $request->input('operator_id');

        $linesQuery = \App\Models\SewingPickupLine::query()
            ->with([
                'sewingPickup.operator',
                'finishedItem',
                'bundle.cuttingJob',
            ])
            ->whereHas('sewingPickup', function ($q) use ($dateFrom, $dateTo, $operatorId) {
                if ($dateFrom) {
                    $q->whereDate('date', '>=', $dateFrom);
                }
                if ($dateTo) {
                    $q->whereDate('date', '<=', $dateTo);
                }
                if ($operatorId) {
                    $q->where('operator_id', $operatorId);
                }
            });

        // Filter only lines that still have outstanding qty
        $lines = $linesQuery->get()->filter(function ($line) {
            $outstanding = max(
                ($line->qty_bundle ?? 0)
                 - ($line->qty_returned_ok ?? 0)
                 - ($line->qty_returned_reject ?? 0),
                0
            );

            return $outstanding > 0;
        });

        $operators = Employee::where('role', 'sewing')
            ->orderBy('code')
            ->get();

        return view('production.reports.sewing.report_outstanding', [
            'lines' => $lines,
            'operators' => $operators,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'operatorId' => $operatorId,
        ]);
    }

    /**
     * Aging report for WIP-SEW:
     * outstanding sewing lines grouped by aging bucket.
     */
    public function agingWipSew(Request $request): View
    {
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');
        $operatorId = $request->input('operator_id');

        $today = Carbon::today();

        $linesQuery = SewingPickupLine::query()
            ->with([
                'sewingPickup.operator', // ⬅️ relasi benar
                'finishedItem',
            ])
            ->whereHas('sewingPickup', function ($q) use ($dateFrom, $dateTo, $operatorId) {
                if ($dateFrom) {
                    $q->whereDate('date', '>=', $dateFrom);
                }
                if ($dateTo) {
                    $q->whereDate('date', '<=', $dateTo);
                }
                if ($operatorId) {
                    $q->where('operator_id', $operatorId);
                }
            });

        $today = Carbon::today();

        $lines = $linesQuery->get()->map(function ($line) use ($today) {
            $picked = (float) ($line->qty_bundle ?? 0);
            $returnedOk = (float) ($line->qty_returned_ok ?? 0);
            $returnedReject = (float) ($line->qty_returned_reject ?? 0);

            $outstanding = max($picked - $returnedOk - $returnedReject, 0);

            /** @var \App\Models\SewingPickup|null $pickup */
            $pickup = $line->sewingPickup ?? null; // ⬅️ pakai sewingPickup
            $pickupDate = $pickup && $pickup->date
            ? Carbon::parse($pickup->date)->startOfDay()
            : null;

            $agingDays = $pickupDate ? $pickupDate->diffInDays($today) : null;

            $line->picked = $picked;
            $line->returned_ok = $returnedOk;
            $line->returned_reject = $returnedReject;
            $line->outstanding = $outstanding;
            $line->aging_days = $agingDays;

            return $line;
        })->filter(fn($line) => $line->outstanding > 0);

        // Bucket summary
        $bucket0_3 = $lines->where('aging_days', '!==', null)
            ->where('aging_days', '<=', 3)->sum('outstanding');
        $bucket4_7 = $lines->where('aging_days', '>=', 4)
            ->where('aging_days', '<=', 7)->sum('outstanding');
        $bucket8_14 = $lines->where('aging_days', '>=', 8)
            ->where('aging_days', '<=', 14)->sum('outstanding');
        $bucket15p = $lines->where('aging_days', '>=', 15)->sum('outstanding');
        $unknownAging = $lines->where('aging_days', null)->sum('outstanding');
        $totalOutstanding = $lines->sum('outstanding');

        $operators = Employee::where('role', 'sewing')
            ->orderBy('code')
            ->get();

        return view('production.reports.sewing.aging_wip_sew', [
            'lines' => $lines,
            'operators' => $operators,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'operatorId' => $operatorId,
            'totalOutstanding' => $totalOutstanding,
            'bucket0_3' => $bucket0_3,
            'bucket4_7' => $bucket4_7,
            'bucket8_14' => $bucket8_14,
            'bucket15p' => $bucket15p,
            'unknownAging' => $unknownAging,
            'today' => $today,
        ]);
    }

    /**
     * Sewing productivity per operator (daily/weekly/monthly).
     */

    public function productivity(Request $request): View
    {
        $period = $request->input('period', 'daily');
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');
        $operatorId = $request->input('operator_id');

        $today = Carbon::today();

// kalau user tidak isi, pakai bulan ini
        if (!$dateFrom && !$dateTo) {
            $dateFrom = $today->copy()->startOfMonth()->toDateString();
            $dateTo = $today->toDateString();
        }

        $rawRows = SewingReturnLine::with(['sewingReturn.operator'])
            ->whereHas('sewingReturn', function ($q) use ($dateFrom, $dateTo, $operatorId) {

                if ($dateFrom && $dateTo) {
                    // pakai whereDate biar aman kalau kolomnya datetime
                    $q->whereDate('date', '>=', $dateFrom)
                        ->whereDate('date', '<=', $dateTo);
                }

                if ($operatorId) {
                    $q->where('operator_id', $operatorId);
                }
            })
            ->get();

        // Cast ke bentuk "row" yang konsisten
        $rows = $rawRows->map(function (SewingReturnLine $line) {
            $return = $line->sewingReturn;
            $op = $return?->operator;

            return (object) [
                'return_date' => Carbon::parse($return->date),
                'qty_ok' => (float) $line->qty_ok,
                'qty_reject' => (float) $line->qty_reject,
                'operator_id' => $op?->id,
                'operator_code' => $op?->code,
                'operator_name' => $op?->name,
            ];
        })->filter(fn($row) => $row->operator_id !== null);

        // Group per operator
        $operatorsSummary = $rows->groupBy('operator_id')->map(function ($items, $opId) use ($period) {
            $first = $items->first();
            $totalOk = $items->sum('qty_ok');
            $totalReject = $items->sum('qty_reject');
            $totalAll = $totalOk + $totalReject;

            $efficiency = $totalAll > 0 ? ($totalOk / $totalAll) * 100 : null;

            // Hitung banyaknya periode (hari/minggu/bulan) yang benar-benar ada data
            $periodKeys = $items->map(function ($row) use ($period) {
                /** @var \Carbon\Carbon $date */
                $date = $row->return_date;

                return match ($period) {
                    'weekly' => $date->copy()->startOfWeek()->format('Y-m-d'),
                    'monthly' => $date->format('Y-m'),
                    default => $date->format('Y-m-d'),
                };
            })->unique();

            $periodCount = max($periodKeys->count(), 1);
            $avgPerPeriod = $periodCount > 0 ? $totalOk / $periodCount : 0;

            return (object) [
                'operator_id' => $opId,
                'operator_code' => $first->operator_code,
                'operator_name' => $first->operator_name,
                'total_ok' => $totalOk,
                'total_reject' => $totalReject,
                'total_all' => $totalAll,
                'efficiency' => $efficiency,
                'period_count' => $periodCount,
                'avg_per_period' => $avgPerPeriod,
            ];
        })->sortByDesc('total_ok')->values();

        // grand total semua operator
        $grandTotalOk = $operatorsSummary->sum('total_ok');
        $grandTotalReject = $operatorsSummary->sum('total_reject');
        $grandTotalAll = $operatorsSummary->sum('total_all');

        $grandEfficiency = $grandTotalAll > 0
        ? ($grandTotalOk / $grandTotalAll) * 100
        : null;

        $operators = Employee::where('role', 'sewing')
            ->orderBy('code')
            ->get();

        return view('production.reports.sewing.productivity', [
            'period' => $period,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'operatorId' => $operatorId,
            'operators' => $operators,
            'rows' => $operatorsSummary,
            'grandTotalOk' => $grandTotalOk,
            'grandTotalReject' => $grandTotalReject,
            'grandTotalAll' => $grandTotalAll,
            'grandEfficiency' => $grandEfficiency,
        ]);
    }

    public function partialPickup(Request $request)
    {
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        $query = SewingPickupLine::query()
            ->with([
                'sewingPickup.operator',
                'bundle.finishedItem',
                'bundle.cuttingJob.lot',
            ])
            ->join('sewing_pickups', 'sewing_pickup_lines.sewing_pickup_id', '=', 'sewing_pickups.id')
            ->select('sewing_pickup_lines.*', 'sewing_pickups.date as pickup_date')
            ->orderBy('sewing_pickup_lines.id', 'desc');

        // Optional filter tanggal
        if ($dateFrom && $dateTo) {
            $query->whereBetween('sewing_pickups.date', [$dateFrom, $dateTo]);
        }

        $rows = $query->get()->filter(function ($line) {
            $returned = ($line->qty_returned_ok ?? 0) + ($line->qty_returned_reject ?? 0);
            return $line->qty_bundle > $returned; // hanya ambil yang masih outstanding
        });

        // Hitung outstanding & aging
        $rows->transform(function ($line) {
            $returned = ($line->qty_returned_ok ?? 0) + ($line->qty_returned_reject ?? 0);
            $line->outstanding = $line->qty_bundle - $returned;
            $line->days_aging = \Carbon\Carbon::parse($line->pickup_date)->diffInDays(now());
            return $line;
        });

        return view('production.reports.sewing.partial_pickup', [
            'rows' => $rows,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
        ]);
    }

    /**
     * Reject Sewing Analysis
     *
     * Fokus: ambil semua baris sewing_return_lines yang punya qty_reject > 0,
     * join dengan header, operator, dan item supaya gampang dibaca.
     */
    public function rejectAnalysis(Request $request): View
    {
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');
        $operatorId = $request->input('operator_id');
        $itemId = $request->input('item_id');

        // === Base query: join tabel penting ===
        $baseQuery = DB::table('sewing_return_lines')
            ->join('sewing_returns', 'sewing_return_lines.sewing_return_id', '=', 'sewing_returns.id')
            ->join('employees', 'sewing_returns.operator_id', '=', 'employees.id')
            ->join('sewing_pickup_lines', 'sewing_return_lines.sewing_pickup_line_id', '=', 'sewing_pickup_lines.id')
            ->join('items', 'sewing_pickup_lines.finished_item_id', '=', 'items.id')
            ->selectRaw('
            sewing_return_lines.id,
            sewing_returns.code       as return_code,
            sewing_returns.date       as return_date,
            employees.id              as operator_id,
            employees.code            as operator_code,
            employees.name            as operator_name,
            items.id                  as item_id,
            items.code                as item_code,
            items.name                as item_name,
            sewing_return_lines.qty_ok,
            sewing_return_lines.qty_reject,
            sewing_return_lines.notes
        ')
            ->where('sewing_return_lines.qty_reject', '>', 0);

        // === Filter tanggal (optional) ===
        if ($dateFrom) {
            $baseQuery->whereDate('sewing_returns.date', '>=', $dateFrom);
        }
        if ($dateTo) {
            $baseQuery->whereDate('sewing_returns.date', '<=', $dateTo);
        }

        // === Filter operator (optional) ===
        if ($operatorId) {
            $baseQuery->where('employees.id', $operatorId);
        }

        // === Filter item (optional) ===
        if ($itemId) {
            $baseQuery->where('items.id', $itemId);
        }

        // Detail baris untuk tabel
        $details = $baseQuery
            ->orderBy('sewing_returns.date')
            ->orderBy('employees.code')
            ->orderBy('items.code')
            ->get();

        // === Summary by operator ===
        $summaryByOperator = $details
            ->groupBy('operator_id')
            ->map(function ($rows) {
                return [
                    'operator_code' => $rows->first()->operator_code,
                    'operator_name' => $rows->first()->operator_name,
                    'total_reject' => $rows->sum('qty_reject'),
                    'total_ok' => $rows->sum('qty_ok'),
                ];
            })
            ->sortByDesc('total_reject');

        // === Summary by item ===
        $summaryByItem = $details
            ->groupBy('item_id')
            ->map(function ($rows) {
                return [
                    'item_code' => $rows->first()->item_code,
                    'item_name' => $rows->first()->item_name,
                    'total_reject' => $rows->sum('qty_reject'),
                    'total_ok' => $rows->sum('qty_ok'),
                ];
            })
            ->sortByDesc('total_reject');

        $totalReject = $details->sum('qty_reject');
        $totalOk = $details->sum('qty_ok');

        // Dropdown filter
        $operators = Employee::where('role', 'sewing')
            ->orderBy('code')
            ->get();

        $items = Item::where('type', 'finished_good')
            ->orderBy('code')
            ->get();

        return view('production.reports.sewing.reject_analysis', [
            'details' => $details,
            'summaryByOperator' => $summaryByOperator,
            'summaryByItem' => $summaryByItem,
            'totalReject' => $totalReject,
            'totalOk' => $totalOk,
            'operators' => $operators,
            'items' => $items,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'operatorId' => $operatorId,
            'itemId' => $itemId,
        ]);
    }

    public function dailyDashboard(Request $request): View
    {
        // ==========================
        // Filter input
        // ==========================
        $dateInput = $request->input('date');
        $operatorId = $request->input('operator_id');
        $itemId = $request->input('item_id');

        $selectedDate = $dateInput
        ? Carbon::parse($dateInput)->startOfDay()
        : Carbon::today();

        $dateString = $selectedDate->toDateString();

        // ==========================
        // Helper: exclude void (AUTO-DETECT, SQLite-safe)
        // ==========================
        $applyNotVoid = function ($q, string $table) {
            // boolean-style columns
            foreach (['is_void', 'is_voided', 'void', 'voided', 'is_canceled', 'is_cancelled'] as $col) {
                if (Schema::hasColumn($table, $col)) {
                    $q->where("$table.$col", 0);
                    return;
                }
            }

            // status-style columns
            foreach (['status', 'state'] as $col) {
                if (Schema::hasColumn($table, $col)) {
                    $q->whereNotIn("$table.$col", ['void', 'VOID', 'canceled', 'CANCELED', 'cancelled', 'CANCELLED']);
                    return;
                }
            }

            // timestamp-style columns
            foreach (['voided_at', 'canceled_at', 'cancelled_at'] as $col) {
                if (Schema::hasColumn($table, $col)) {
                    $q->whereNull("$table.$col");
                    return;
                }
            }

            // If nothing matches: do nothing (avoid SQL error)
        };

        // ==========================
        // Dropdown data
        // ==========================
        $operators = Employee::where('role', 'sewing')
            ->orderBy('code')
            ->get();

        $items = Item::where('type', 'finished_good')
            ->orderBy('code')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | 1) Total Pickup di tanggal terpilih (exclude void pickup)
        |--------------------------------------------------------------------------
         */
        $pickupQuery = SewingPickupLine::query()
            ->join('sewing_pickups', 'sewing_pickup_lines.sewing_pickup_id', '=', 'sewing_pickups.id')
            ->whereDate('sewing_pickups.date', $dateString);

        $applyNotVoid($pickupQuery, 'sewing_pickups');

        if ($operatorId) {
            $pickupQuery->where('sewing_pickups.operator_id', $operatorId);
        }
        if ($itemId) {
            $pickupQuery->where('sewing_pickup_lines.finished_item_id', $itemId);
        }

        $totalPickup = (float) $pickupQuery->sum('sewing_pickup_lines.qty_bundle');

        /*
        |--------------------------------------------------------------------------
        | 2) Total Return OK & Reject di tanggal terpilih (exclude void return)
        |--------------------------------------------------------------------------
         */
        $returnBase = SewingReturnLine::query()
            ->join('sewing_returns', 'sewing_return_lines.sewing_return_id', '=', 'sewing_returns.id')
            ->whereDate('sewing_returns.date', $dateString);

        $applyNotVoid($returnBase, 'sewing_returns');

        if ($operatorId) {
            $returnBase->where('sewing_returns.operator_id', $operatorId);
        }

        if ($itemId) {
            $returnBase->join('sewing_pickup_lines', 'sewing_return_lines.sewing_pickup_line_id', '=', 'sewing_pickup_lines.id')
                ->where('sewing_pickup_lines.finished_item_id', $itemId);
        }

        $totalReturnOk = (float) (clone $returnBase)->sum('sewing_return_lines.qty_ok');
        $totalReject = (float) (clone $returnBase)->sum('sewing_return_lines.qty_reject');

        /*
        |--------------------------------------------------------------------------
        | 2B) Masuk WIP-FIN (Setor) = Return OK (non-void) di tanggal terpilih
        |--------------------------------------------------------------------------
         */
        $wipFinInToday = (int) $totalReturnOk;

        /*
        |--------------------------------------------------------------------------
        | 3) Outstanding WIP per Operator + Item (Detail tabel) (exclude void pickup)
        |--------------------------------------------------------------------------
         */
        $outstandingExpr = '
    (
        SUM(sewing_pickup_lines.qty_bundle)
        - SUM(COALESCE(sewing_pickup_lines.qty_returned_ok,0))
        - SUM(COALESCE(sewing_pickup_lines.qty_returned_reject,0))
    )
    ';

        $outstandingDetailQuery = SewingPickupLine::query()
            ->join('sewing_pickups', 'sewing_pickup_lines.sewing_pickup_id', '=', 'sewing_pickups.id')
            ->join('employees', 'sewing_pickups.operator_id', '=', 'employees.id')
            ->join('items', 'sewing_pickup_lines.finished_item_id', '=', 'items.id')
            ->whereNotNull('sewing_pickups.operator_id')
            ->where('items.type', 'finished_good');

        $applyNotVoid($outstandingDetailQuery, 'sewing_pickups');

        if ($operatorId) {
            $outstandingDetailQuery->where('sewing_pickups.operator_id', $operatorId);
        }
        if ($itemId) {
            $outstandingDetailQuery->where('sewing_pickup_lines.finished_item_id', $itemId);
        }

        $outstandingDetail = $outstandingDetailQuery
            ->select(
                'employees.id as operator_id',
                'employees.code as operator_code',
                'employees.name as operator_name',
                'items.id as item_id',
                'items.code as item_code',
                'items.name as item_name',
                DB::raw('MIN(sewing_pickups.date) as tanggal_ambil'),
                DB::raw('SUM(sewing_pickup_lines.qty_bundle) as picked_total'),
                DB::raw("CASE WHEN {$outstandingExpr} > 0 THEN {$outstandingExpr} ELSE 0 END as outstanding")
            )
            ->groupBy(
                'employees.id', 'employees.code', 'employees.name',
                'items.id', 'items.code', 'items.name'
            )
            ->havingRaw("{$outstandingExpr} > 0")
            ->orderByDesc('outstanding')
            ->get();

        $totalOutstanding = (int) $outstandingDetail->sum('outstanding');

        /*
        |--------------------------------------------------------------------------
        | 4) Operator Terbaik (exclude void return)
        |--------------------------------------------------------------------------
         */
        $topOperatorQuery = SewingReturnLine::query()
            ->join('sewing_returns', 'sewing_return_lines.sewing_return_id', '=', 'sewing_returns.id')
            ->join('employees', 'sewing_returns.operator_id', '=', 'employees.id')
            ->whereDate('sewing_returns.date', $dateString);

        $applyNotVoid($topOperatorQuery, 'sewing_returns');

        if ($operatorId) {
            $topOperatorQuery->where('sewing_returns.operator_id', $operatorId);
        }
        if ($itemId) {
            $topOperatorQuery->join('sewing_pickup_lines', 'sewing_return_lines.sewing_pickup_line_id', '=', 'sewing_pickup_lines.id')
                ->where('sewing_pickup_lines.finished_item_id', $itemId);
        }

        $topOperator = $topOperatorQuery
            ->select(
                'employees.id',
                'employees.code',
                'employees.name',
                DB::raw('SUM(COALESCE(sewing_return_lines.qty_ok,0)) as total_ok')
            )
            ->groupBy('employees.id', 'employees.code', 'employees.name')
            ->orderByDesc('total_ok')
            ->first();

        /*
        |--------------------------------------------------------------------------
        | 5) WIP Terlama (exclude void pickup)
        |--------------------------------------------------------------------------
         */
        $agingBase = SewingPickupLine::query()
            ->join('sewing_pickups', 'sewing_pickup_lines.sewing_pickup_id', '=', 'sewing_pickups.id')
            ->join('employees', 'sewing_pickups.operator_id', '=', 'employees.id')
            ->whereNotNull('sewing_pickups.operator_id');

        $applyNotVoid($agingBase, 'sewing_pickups');

        if ($operatorId) {
            $agingBase->where('sewing_pickups.operator_id', $operatorId);
        }
        if ($itemId) {
            $agingBase->where('sewing_pickup_lines.finished_item_id', $itemId);
        }

        $agingRow = $agingBase
            ->select(
                'employees.id as operator_id',
                'employees.code as operator_code',
                'employees.name as operator_name',
                DB::raw('MIN(sewing_pickups.date) as oldest_pickup_date')
            )
            ->whereRaw('sewing_pickup_lines.qty_bundle > (COALESCE(sewing_pickup_lines.qty_returned_ok,0) + COALESCE(sewing_pickup_lines.qty_returned_reject,0))')
            ->groupBy('employees.id', 'employees.code', 'employees.name')
            ->orderBy('oldest_pickup_date', 'asc')
            ->first();

        $agingWip = null;
        if ($agingRow && $agingRow->oldest_pickup_date) {
            $pickupDate = Carbon::parse($agingRow->oldest_pickup_date)->startOfDay();
            $agingWip = [
                'operator' => (object) [
                    'id' => $agingRow->operator_id,
                    'code' => $agingRow->operator_code,
                    'name' => $agingRow->operator_name,
                ],
                'aging' => $pickupDate->diffInDays(Carbon::today()),
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | 6) Output OK per Jam (exclude void return)
        |--------------------------------------------------------------------------
         */
        $hourlyQuery = SewingReturnLine::query()
            ->join('sewing_returns', 'sewing_return_lines.sewing_return_id', '=', 'sewing_returns.id')
            ->whereDate('sewing_returns.date', $dateString);

        $applyNotVoid($hourlyQuery, 'sewing_returns');

        if ($operatorId) {
            $hourlyQuery->where('sewing_returns.operator_id', $operatorId);
        }
        if ($itemId) {
            $hourlyQuery->join('sewing_pickup_lines', 'sewing_return_lines.sewing_pickup_line_id', '=', 'sewing_pickup_lines.id')
                ->where('sewing_pickup_lines.finished_item_id', $itemId);
        }

        $hourlyRaw = $hourlyQuery
            ->select('sewing_return_lines.qty_ok', 'sewing_returns.created_at')
            ->get();

        $hours = collect(range(0, 23));
        $hourlyOutput = $hours->map(function ($h) use ($hourlyRaw) {
            $sum = $hourlyRaw
                ->filter(function ($row) use ($h) {
                    if (!$row->created_at) {
                        return false;
                    }

                    $created = Carbon::parse($row->created_at);
                    return (int) $created->format('H') === (int) $h;
                })
                ->sum('qty_ok');

            return [
                'hour' => $h,
                'label' => sprintf('%02d:00', $h),
                'qty_ok' => (int) $sum,
            ];
        });

        /*
        |--------------------------------------------------------------------------
        | 7) Breakdown per Item (OK + Reject) di tanggal terpilih (exclude void return)
        |--------------------------------------------------------------------------
         */
        $itemBreakdownQuery = SewingReturnLine::query()
            ->join('sewing_returns', 'sewing_return_lines.sewing_return_id', '=', 'sewing_returns.id')
            ->join('sewing_pickup_lines', 'sewing_return_lines.sewing_pickup_line_id', '=', 'sewing_pickup_lines.id')
            ->join('items', 'sewing_pickup_lines.finished_item_id', '=', 'items.id')
            ->whereDate('sewing_returns.date', $dateString)
            ->where('items.type', 'finished_good');

        $applyNotVoid($itemBreakdownQuery, 'sewing_returns');

        if ($operatorId) {
            $itemBreakdownQuery->where('sewing_returns.operator_id', $operatorId);
        }
        if ($itemId) {
            $itemBreakdownQuery->where('items.id', $itemId);
        }

        $itemBreakdown = $itemBreakdownQuery
            ->select(
                'items.id',
                'items.code',
                'items.name',
                DB::raw('SUM(COALESCE(sewing_return_lines.qty_ok,0)) as total_ok'),
                DB::raw('SUM(COALESCE(sewing_return_lines.qty_reject,0)) as total_reject')
            )
            ->groupBy('items.id', 'items.code', 'items.name')
            ->orderByDesc('total_ok')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | 7B) Breakdown Masuk WIP-FIN per Item (Setor OK) (exclude void return)
        |--------------------------------------------------------------------------
         */
        $wipFinInBreakdownQuery = SewingReturnLine::query()
            ->join('sewing_returns', 'sewing_return_lines.sewing_return_id', '=', 'sewing_returns.id')
            ->join('sewing_pickup_lines', 'sewing_return_lines.sewing_pickup_line_id', '=', 'sewing_pickup_lines.id')
            ->join('items', 'sewing_pickup_lines.finished_item_id', '=', 'items.id')
            ->whereDate('sewing_returns.date', $dateString)
            ->where('items.type', 'finished_good');

        $applyNotVoid($wipFinInBreakdownQuery, 'sewing_returns');

        if ($operatorId) {
            $wipFinInBreakdownQuery->where('sewing_returns.operator_id', $operatorId);
        }
        if ($itemId) {
            $wipFinInBreakdownQuery->where('items.id', $itemId);
        }

        $wipFinInBreakdown = $wipFinInBreakdownQuery
            ->select(
                'items.id',
                'items.code',
                'items.name',
                DB::raw('SUM(COALESCE(sewing_return_lines.qty_ok,0)) as qty_in')
            )
            ->groupBy('items.id', 'items.code', 'items.name')
            ->orderByDesc('qty_in')
            ->get();

        return view('production.reports.sewing.dashboard', [
            'selectedDate' => $selectedDate,

            'totalPickupToday' => $totalPickup,
            'totalReturnOkToday' => $totalReturnOk,
            'totalRejectToday' => $totalReject,

            'wipFinInToday' => $wipFinInToday,
            'wipFinInBreakdown' => $wipFinInBreakdown,

            'outstandingDetail' => $outstandingDetail,
            'totalOutstanding' => $totalOutstanding,

            'topOperator' => $topOperator,
            'agingWip' => $agingWip,

            'hourlyOutput' => $hourlyOutput,
            'itemBreakdown' => $itemBreakdown,

            'operators' => $operators,
            'items' => $items,
            'selectedOperatorId' => $operatorId,
            'selectedItemId' => $itemId,
        ]);
    }

    public function leadTime(Request $request): View
    {
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');
        $operatorId = $request->input('operator_id');

        // Query dasar: SewingReturnLine + join header & pickup & item & operator
        $rawRows = SewingReturnLine::query()
            ->join('sewing_returns', 'sewing_return_lines.sewing_return_id', '=', 'sewing_returns.id')
            ->join('sewing_pickup_lines', 'sewing_return_lines.sewing_pickup_line_id', '=', 'sewing_pickup_lines.id')
            ->join('sewing_pickups', 'sewing_pickup_lines.sewing_pickup_id', '=', 'sewing_pickups.id')
            ->join('employees', 'sewing_returns.operator_id', '=', 'employees.id')
            ->join('items', 'sewing_pickup_lines.finished_item_id', '=', 'items.id')
            ->selectRaw('
            sewing_return_lines.id  as line_id,
            sewing_return_lines.qty_ok,
            sewing_return_lines.qty_reject,

            sewing_returns.id       as return_id,
            sewing_returns.code     as return_code,
            sewing_returns.date     as return_date,
            sewing_returns.operator_id,

            sewing_pickups.id       as pickup_id,
            sewing_pickups.code     as pickup_code,
            sewing_pickups.date     as pickup_date,

            employees.code          as operator_code,
            employees.name          as operator_name,

            items.id                as item_id,
            items.code              as item_code,
            items.name              as item_name
        ')
            ->when($dateFrom, function ($q) use ($dateFrom) {
                $q->whereDate('sewing_pickups.date', '>=', $dateFrom);
            })
            ->when($dateTo, function ($q) use ($dateTo) {
                $q->whereDate('sewing_pickups.date', '<=', $dateTo);
            })
            ->when($operatorId, function ($q) use ($operatorId) {
                $q->where('sewing_returns.operator_id', $operatorId);
            })
            ->orderBy('sewing_pickups.date')
            ->orderBy('employees.code')
            ->orderBy('items.code')
            ->get();

        // Hitung lead time di PHP (aman untuk SQLite/MySQL)
        $rows = $rawRows->map(function ($row) {
            $pickupDate = $row->pickup_date ? Carbon::parse($row->pickup_date)->startOfDay() : null;
            $returnDate = $row->return_date ? Carbon::parse($row->return_date)->startOfDay() : null;

            $leadTimeDays = null;
            if ($pickupDate && $returnDate) {
                // 0 berarti same-day pickup & return
                $leadTimeDays = $pickupDate->diffInDays($returnDate);
            }

            $row->lead_time_days = $leadTimeDays;
            $row->total_qty = (float) ($row->qty_ok ?? 0) + (float) ($row->qty_reject ?? 0);

            return $row;
        });

        // Summary per operator
        $byOperator = $rows
            ->filter(fn($r) => $r->lead_time_days !== null)
            ->groupBy('operator_id')
            ->map(function ($group) {
                $first = $group->first();
                $countReturns = $group->count();
                $sumLead = $group->sum('lead_time_days');
                $avgLead = $countReturns > 0 ? $sumLead / $countReturns : null;

                return (object) [
                    'operator_id' => $first->operator_id,
                    'operator_code' => $first->operator_code,
                    'operator_name' => $first->operator_name,
                    'avg_lead_days' => $avgLead,
                    'min_lead_days' => $group->min('lead_time_days'),
                    'max_lead_days' => $group->max('lead_time_days'),
                    'count_returns' => $countReturns,
                    'total_qty_ok' => $group->sum('qty_ok'),
                    'total_qty' => $group->sum('total_qty'),
                ];
            })
            ->sortBy('avg_lead_days')
            ->values();

        $overallAvgLead = $byOperator->count() > 0
        ? $byOperator->avg('avg_lead_days')
        : null;

        $totalReturns = $rows->count();
        $totalQtyOk = $rows->sum('qty_ok');

        $operators = Employee::where('role', 'sewing')
            ->orderBy('code')
            ->get();

        $today = Carbon::today();

        return view('production.reports.sewing.lead_time', [ // ⬅️ pastikan path Blade sama
            'rows' => $rows,
            'byOperator' => $byOperator,
            'overallAvgLead' => $overallAvgLead,
            'totalReturns' => $totalReturns,
            'totalQtyOk' => $totalQtyOk,
            'operators' => $operators,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'operatorId' => $operatorId,
            'today' => $today,
        ]);
    }

    public function operatorBehavior(Request $request): View
    {
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');
        $operatorId = $request->input('operator_id');
        $days = (int) $request->input('days', 30); // default 30 hari terakhir

        $today = Carbon::today();

        if (!$dateFrom && !$dateTo) {
            $dateFrom = $today->copy()->subDays($days - 1)->toDateString();
            $dateTo = $today->toDateString();
        }

        // ==========================
        // Ambil data pickup lines + operator
        // ==========================
        $pickupLinesQuery = SewingPickupLine::query()
            ->join('sewing_pickups', 'sewing_pickup_lines.sewing_pickup_id', '=', 'sewing_pickups.id')
            ->join('employees', 'sewing_pickups.operator_id', '=', 'employees.id')
            ->select([
                'sewing_pickup_lines.*',
                'sewing_pickups.operator_id',
                'sewing_pickups.date as pickup_date',
                'employees.code as operator_code',
                'employees.name as operator_name',
            ]);

        if ($dateFrom) {
            $pickupLinesQuery->whereDate('sewing_pickups.date', '>=', $dateFrom);
        }
        if ($dateTo) {
            $pickupLinesQuery->whereDate('sewing_pickups.date', '<=', $dateTo);
        }
        if ($operatorId) {
            $pickupLinesQuery->where('sewing_pickups.operator_id', $operatorId);
        }

        $pickupLines = $pickupLinesQuery->get();

        if ($pickupLines->isEmpty()) {
            $operators = Employee::where('role', 'sewing')->orderBy('code')->get();

            return view('production.report.sewing.operator_behavior', [
                'operators' => $operators,
                'dateFrom' => $dateFrom,
                'dateTo' => $dateTo,
                'operatorId' => $operatorId,
                'days' => $days,
                'today' => $today,
                'summaries' => collect(),
                'bestOperator' => null,
                'worstOperator' => null,
                'avgScore' => null,
                'totalOperators' => 0,
            ]);
        }

        $pickupLineIds = $pickupLines->pluck('id')->all();

        // ==========================
        // Ambil aggregate RETURN untuk setiap pickup_line
        // ==========================
        $returnAgg = SewingReturnLine::query()
            ->join('sewing_returns', 'sewing_return_lines.sewing_return_id', '=', 'sewing_returns.id')
            ->whereIn('sewing_pickup_line_id', $pickupLineIds)
            ->selectRaw('
            sewing_pickup_line_id,
            MIN(sewing_returns.date) as first_return_date,
            MAX(sewing_returns.date) as last_return_date,
            SUM(sewing_return_lines.qty_ok) as total_ok,
            SUM(sewing_return_lines.qty_reject) as total_reject
        ')
            ->groupBy('sewing_pickup_line_id')
            ->get()
            ->keyBy('sewing_pickup_line_id');

        // ==========================
        // Hitung behavior per operator
        // ==========================
        $summaries = [];
        $dailyOutputs = []; // [operator_id][date] = total OK

        foreach ($pickupLines as $line) {
            $operatorIdLine = (int) $line->operator_id;
            $operatorCode = $line->operator_code;
            $operatorName = $line->operator_name;
            $pickupDate = $line->pickup_date ? Carbon::parse($line->pickup_date)->toDateString() : null;

            $agg = $returnAgg->get($line->id);

            $picked = (float) ($line->qty_bundle ?? 0);
            $returnedOk = (float) ($agg->total_ok ?? $line->qty_returned_ok ?? 0);
            $returnedReject = (float) ($agg->total_reject ?? $line->qty_returned_reject ?? 0);

            $outstanding = max($picked - $returnedOk - $returnedReject, 0);

            // lead time dari pickup → first_return
            $leadDays = null;
            if ($pickupDate && $agg && $agg->first_return_date) {
                $pickupCarbon = Carbon::parse($pickupDate)->startOfDay();
                $firstReturn = Carbon::parse($agg->first_return_date)->startOfDay();
                $leadDays = $pickupCarbon->diffInDays($firstReturn);
            }

            if (!isset($summaries[$operatorIdLine])) {
                $summaries[$operatorIdLine] = [
                    'operator_id' => $operatorIdLine,
                    'operator_code' => $operatorCode,
                    'operator_name' => $operatorName,
                    'total_pickup' => 0.0,
                    'total_ok' => 0.0,
                    'total_reject' => 0.0,
                    'total_outstanding' => 0.0,
                    'lead_times' => [],
                    'pickup_days' => [],
                    'behavior_score' => 0.0,
                    'grade' => null,
                ];
            }

            $summaries[$operatorIdLine]['total_pickup'] += $picked;
            $summaries[$operatorIdLine]['total_ok'] += $returnedOk;
            $summaries[$operatorIdLine]['total_reject'] += $returnedReject;
            $summaries[$operatorIdLine]['total_outstanding'] += $outstanding;

            if ($leadDays !== null) {
                $summaries[$operatorIdLine]['lead_times'][] = $leadDays;
            }

            if ($pickupDate) {
                $summaries[$operatorIdLine]['pickup_days'][] = $pickupDate;
            }

            // daily output chart: pakai tanggal RETURN terakhir (atau pickup kalau tidak ada)
            $outputDate = null;
            if ($agg && $agg->last_return_date) {
                $outputDate = Carbon::parse($agg->last_return_date)->toDateString();
            } elseif ($pickupDate) {
                $outputDate = $pickupDate;
            }

            if ($outputDate && $returnedOk > 0) {
                if (!isset($dailyOutputs[$operatorIdLine])) {
                    $dailyOutputs[$operatorIdLine] = [];
                }
                if (!isset($dailyOutputs[$operatorIdLine][$outputDate])) {
                    $dailyOutputs[$operatorIdLine][$outputDate] = 0;
                }
                $dailyOutputs[$operatorIdLine][$outputDate] += $returnedOk;
            }
        }

        // ==========================
        // Behavior scoring per operator
        // ==========================
        foreach ($summaries as &$s) {
            $pickup = $s['total_pickup'];
            $ok = $s['total_ok'];
            $reject = $s['total_reject'];
            $outstanding = $s['total_outstanding'];

            $okRate = $pickup > 0 ? $ok / $pickup : 0.0;
            $rejectRate = ($ok + $reject) > 0 ? $reject / ($ok + $reject) : 0.0;
            $outRatio = $pickup > 0 ? $outstanding / $pickup : 0.0;

            $avgLeadDays = !empty($s['lead_times'])
            ? array_sum($s['lead_times']) / max(count($s['lead_times']), 1)
            : null;

            // Score components
            // 1) Completion / accuracy (max 40)
            $scoreCompletion = min($okRate * 100, 100) * 0.40;

            // 2) Reject (max 25) - 0% reject = 25, 20% reject = 0
            $clampedReject = min($rejectRate, 0.20);
            $scoreReject = (1 - $clampedReject / 0.20) * 25;

            // 3) Outstanding ratio (max 20) - 0 outstanding = 20, 50% outstanding = 0
            $clampedOut = min($outRatio, 0.50);
            $scoreOutstanding = (1 - $clampedOut / 0.50) * 20;

            // 4) Lead time (max 15) - 0 hari = 15, >=7 hari = 0
            if ($avgLeadDays === null) {
                $scoreLead = 7.5; // netral
            } else {
                $clampedLead = min($avgLeadDays, 7);
                $scoreLead = (1 - $clampedLead / 7) * 15;
            }

            $totalScore = round($scoreCompletion + $scoreReject + $scoreOutstanding + $scoreLead, 1);
            $s['behavior_score'] = max(0, min(100, $totalScore));

            // Grade
            if ($s['behavior_score'] >= 85) {
                $s['grade'] = 'Excellent';
            } elseif ($s['behavior_score'] >= 70) {
                $s['grade'] = 'Good';
            } elseif ($s['behavior_score'] >= 50) {
                $s['grade'] = 'Needs Attention';
            } else {
                $s['grade'] = 'Risk';
            }
        }
        unset($s);

        // Konversi ke koleksi biar gampang sort
        $summariesCollection = collect($summaries)->sortByDesc('behavior_score')->values();

        $bestOperator = $summariesCollection->first();
        $worstOperator = $summariesCollection->sortBy('behavior_score')->first();
        $avgScore = $summariesCollection->avg('behavior_score');
        $totalOperators = $summariesCollection->count();

        // Siapkan 7 hari terakhir untuk mini-chart
        $chartDays = [];
        for ($i = 6; $i >= 0; $i--) {
            $chartDays[] = $today->copy()->subDays($i)->toDateString();
        }

        $operators = Employee::where('role', 'sewing')->orderBy('code')->get();

        return view('production.reports.sewing.operator_behavior', [
            'operators' => $operators,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'operatorId' => $operatorId,
            'days' => $days,
            'today' => $today,
            'summaries' => $summariesCollection,
            'dailyOutputs' => $dailyOutputs,
            'chartDays' => $chartDays,
            'bestOperator' => $bestOperator,
            'worstOperator' => $worstOperator,
            'avgScore' => $avgScore,
            'totalOperators' => $totalOperators,
        ]);
    }

}
