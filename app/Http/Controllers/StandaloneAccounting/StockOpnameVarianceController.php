<?php

namespace App\Http\Controllers\StandaloneAccounting;

use App\Http\Controllers\Controller;
use App\Models\StandaloneAccountingAccountMapping;
use App\Models\StandaloneAccountingJournal;
use App\Models\StockOpname;
use App\Models\Warehouse;
use Illuminate\Http\Request;

class StockOpnameVarianceController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'warehouse_id' => ['nullable', 'integer', 'exists:warehouses,id'],
            'status' => ['nullable', 'in:all,draft,counting,reviewed,finalized,cancelled'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
            'q' => ['nullable', 'string', 'max:100'],
        ]);

        $query = StockOpname::query()
            ->with(['warehouse', 'adjustment'])
            ->withCount('lines')
            ->orderByDesc('date')
            ->orderByDesc('id');

        if (! empty($filters['warehouse_id'])) {
            $query->where('warehouse_id', (int) $filters['warehouse_id']);
        }

        if (! empty($filters['status']) && $filters['status'] !== 'all') {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['date_from'])) {
            $query->whereDate('date', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('date', '<=', $filters['date_to']);
        }

        if (! empty($filters['q'])) {
            $search = trim($filters['q']);
            $query->where(function ($where) use ($search): void {
                $where
                    ->where('code', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%")
                    ->orWhereHas('warehouse', function ($warehouse) use ($search): void {
                        $warehouse
                            ->where('code', 'like', "%{$search}%")
                            ->orWhere('name', 'like', "%{$search}%");
                    });
            });
        }

        $opnames = $query->paginate(25)->appends($request->query());

        $adjustmentIds = $opnames->getCollection()
            ->pluck('adjustment.id')
            ->filter()
            ->map(fn ($id): int => (int) $id)
            ->values();

        $journals = StandaloneAccountingJournal::query()
            ->with('lines.account')
            ->where('source_type', 'inventory_adjustment')
            ->whereIn('source_id', $adjustmentIds)
            ->whereNull('voided_at')
            ->get()
            ->keyBy('source_id');

        $varianceAccountCode = StandaloneAccountingAccountMapping::query()
            ->where('legacy_account_code', '6115')
            ->where('is_active', true)
            ->value('standalone_account_code') ?? '6-210';

        $rows = $opnames->getCollection()->map(function (StockOpname $opname) use ($journals, $varianceAccountCode): object {
            $adjustment = $opname->adjustment;
            $journal = $adjustment ? $journals->get($adjustment->id) : null;
            $varianceLines = $journal?->lines
                ->filter(fn ($line): bool => $line->account?->code === $varianceAccountCode)
                ?? collect();
            $varianceDebit = round((float) $varianceLines->sum('debit'), 2);
            $varianceCredit = round((float) $varianceLines->sum('credit'), 2);

            return (object) [
                'opname' => $opname,
                'adjustment' => $adjustment,
                'journal' => $journal,
                'variance_account_code' => $varianceAccountCode,
                'variance_amount' => max($varianceDebit, $varianceCredit),
                'variance_side' => $varianceDebit > 0 ? 'Debit' : ($varianceCredit > 0 ? 'Kredit' : null),
                'approval_status' => $adjustment?->status ?: $opname->status,
            ];
        });

        return view('standalone_accounting.stock_opname_variance.index', [
            'rows' => $rows,
            'opnames' => $opnames,
            'warehouses' => Warehouse::query()->orderBy('code')->get(),
            'filters' => [
                'warehouse_id' => $filters['warehouse_id'] ?? '',
                'status' => $filters['status'] ?? 'all',
                'date_from' => $filters['date_from'] ?? '',
                'date_to' => $filters['date_to'] ?? '',
                'q' => $filters['q'] ?? '',
            ],
            'statusOptions' => [
                'all' => 'Semua status',
                StockOpname::STATUS_DRAFT => 'Draft',
                StockOpname::STATUS_COUNTING => 'Counting',
                StockOpname::STATUS_REVIEWED => 'Reviewed',
                StockOpname::STATUS_FINALIZED => 'Finalized',
                StockOpname::STATUS_CANCELLED => 'Cancelled',
            ],
        ]);
    }
}
