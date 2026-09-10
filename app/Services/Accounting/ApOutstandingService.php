<?php

namespace App\Services\Accounting;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ApOutstandingService
{
    public function rowsAsOf(string $asOf, ?int $supplierId = null): Collection
    {
        $openings = DB::table('supplier_ap_opening_balances as ob')
            ->join('suppliers as s', 's.id', '=', 'ob.supplier_id')
            ->where('ob.status', 'posted')
            ->whereNull('ob.voided_at')
            ->whereDate('ob.date', '<=', $asOf)
            ->when($supplierId, fn ($q) => $q->where('ob.supplier_id', $supplierId))
            ->select('ob.*', 's.name as supplier_name', 's.code as supplier_code')
            ->orderBy('ob.date')->orderBy('ob.invoice_date')->orderBy('ob.id')
            ->get()->groupBy('supplier_id');

        $orders = DB::table('purchase_orders as po')
            ->join('suppliers as s', 's.id', '=', 'po.supplier_id')
            ->when($supplierId, fn ($q) => $q->where('po.supplier_id', $supplierId))
            ->select('po.*', 's.name as supplier_name', 's.code as supplier_code')->get();
        $receipts = DB::table('purchase_receipts')
            ->where('status', 'posted')
            ->where(fn ($q) => $q->whereNull('is_replacement')->orWhere('is_replacement', false))
            ->whereDate('date', '<=', $asOf)
            ->whereIn('purchase_order_id', $orders->pluck('id'))
            ->orderBy('date')->orderBy('id')->get()->groupBy('purchase_order_id');
        $payments = DB::table('purchase_payments')
            ->whereNull('voided_at')->whereIn('type', ['payment', 'dp_apply'])
            ->whereDate('date', '<=', $asOf)
            ->whereIn('purchase_order_id', $orders->pluck('id'))
            ->orderBy('date')->orderBy('id')->get()->groupBy('purchase_order_id');
        $returnRows = DB::table('purchase_returns')
            ->where('status', 'posted')->whereNull('voided_at')
            ->where(function ($q) {
                $q->whereNull('resolution_type')->orWhere('resolution_type', '!=', 'replacement');
            })
            ->whereDate('date', '<=', $asOf)
            ->where(function ($q) use ($orders, $receipts) {
                $q->whereIn('purchase_order_id', $orders->pluck('id'))
                    ->orWhereIn('purchase_receipt_id', $receipts->flatten(1)->pluck('id'));
            })
            ->orderBy('date')->orderBy('id')->get();
        $receiptOrderMap = $receipts->flatten(1)->keyBy('id');
        $returns = $returnRows->groupBy(function ($return) use ($receiptOrderMap) {
            return $return->purchase_order_id
                ?: $receiptOrderMap->get($return->purchase_receipt_id)?->purchase_order_id;
        });

        $rows = collect();
        $openingSettlements = collect();
        foreach ($orders as $order) {
            $grns = $receipts->get($order->id, collect());
            $paid = $payments->get($order->id, collect());
            $orderReturns = $returns->get($order->id, collect());
            $supplierOpenings = $openings->get($order->supplier_id, collect());
            $cutoff = $supplierOpenings->isEmpty() ? null : $this->date($supplierOpenings->first()->date);
            $currentGrns = $cutoff
                ? $grns->filter(fn ($grn) => $this->date($grn->date) > $cutoff)
                : $grns;
            $currentPaid = 0.0;
            $receiptReturnTotal = fn ($receiptId, $until = null) => (float) $orderReturns
                ->filter(fn ($return) => (int) ($return->purchase_receipt_id ?? 0) === (int) $receiptId)
                ->when($until, fn ($items) => $items->filter(fn ($return) => $this->date($return->date) <= $until))
                ->sum('total');
            $receiptNet = fn ($receipt, $until = null) => max(0, round(
                (float) $receipt->grand_total - $receiptReturnTotal($receipt->id, $until), 2
            ));

            if ($cutoff) {
                // Opening replaces the old net debt, including settlements on
                // the opening date. Legacy PO payments settle old debt first;
                // an explicit receipt link takes precedence for mixed POs.
                $oldGrns = $grns->filter(fn ($grn) => $this->date($grn->date) <= $cutoff);
                $oldRemaining = max(0, round($oldGrns->sum(fn ($grn) => $receiptNet($grn, $cutoff)) - $paid
                    ->filter(fn ($payment) => $this->date($payment->date) <= $cutoff)->sum('amount'), 2));
                foreach ($orderReturns as $return) {
                    if ($this->date($return->date) <= $cutoff) {
                        continue;
                    }
                    $isOldReceipt = $return->purchase_receipt_id
                        && $oldGrns->contains('id', $return->purchase_receipt_id);
                    $isCurrentReceipt = $return->purchase_receipt_id
                        && $currentGrns->contains('id', $return->purchase_receipt_id);
                    if ($isCurrentReceipt) {
                        continue;
                    }
                    $openingReturn = min($oldRemaining, (float) $return->total);
                    $oldRemaining = round($oldRemaining - $openingReturn, 2);
                    if ($openingReturn > 0) {
                        $openingSettlements->push((object) [
                            'supplier_id' => $order->supplier_id,
                            'date' => $this->date($return->date),
                            'amount' => $openingReturn,
                            'id' => $return->id,
                        ]);
                    }
                }
                foreach ($paid as $payment) {
                    if ($this->date($payment->date) <= $cutoff) {
                        continue;
                    }
                    $receiptId = $payment->purchase_receipt_id ?? null;
                    $isOldReceipt = $receiptId && $oldGrns->contains('id', $receiptId);
                    $isCurrentReceipt = $receiptId && $currentGrns->contains('id', $receiptId);
                    $openingPaid = $isCurrentReceipt ? 0.0 : min($oldRemaining, (float) $payment->amount);
                    $oldRemaining = round($oldRemaining - $openingPaid, 2);
                    if ($openingPaid > 0) {
                        $openingSettlements->push((object) [
                            'supplier_id' => $order->supplier_id,
                            'date' => $this->date($payment->date),
                            'amount' => $openingPaid,
                            'id' => $payment->id,
                        ]);
                    }
                    // An old receipt's payment must not also pay a new GRN.
                    if (! $isOldReceipt) {
                        $currentPaid += (float) $payment->amount - $openingPaid;
                    }
                }
            } else {
                $currentPaid = (float) $paid->sum('amount');
            }

            if ($currentGrns->isNotEmpty()) {
                $currentGross = (float) $currentGrns->sum(fn ($grn) => $receiptNet($grn));
                $rows->push((object) [
                    'supplier_id' => $order->supplier_id,
                    'supplier_name' => $order->supplier_name,
                    'supplier_code' => $order->supplier_code,
                    'po_id' => $order->id,
                    'po_code' => $order->code,
                    'po_date' => $order->date,
                    'oldest_grn_date' => $this->date($currentGrns->first()->date),
                    'grn_total' => $currentGross,
                    'paid_total' => round($currentPaid, 2),
                    'outstanding' => round($currentGross - $currentPaid, 2),
                    'is_opening' => false,
                    'reference_no' => null,
                ]);
            }
        }

        foreach ($openings as $supplierOpenings) {
            $openingRows = $supplierOpenings->map(fn ($opening) => (object) [
                'supplier_id' => $opening->supplier_id,
                'supplier_name' => $opening->supplier_name,
                'supplier_code' => $opening->supplier_code,
                'po_id' => null,
                'po_code' => null,
                'po_date' => $this->date($opening->date),
                'oldest_grn_date' => $this->date($opening->invoice_date ?? $opening->date),
                'grn_total' => (float) $opening->amount,
                'paid_total' => 0.0,
                'outstanding' => (float) $opening->amount,
                'is_opening' => true,
                'reference_no' => $opening->reference_no,
            ]);
            foreach ($openingSettlements->where('supplier_id', $supplierOpenings->first()->supplier_id)
                ->sortBy(fn ($payment) => $payment->date.'|'.str_pad($payment->id, 20, '0', STR_PAD_LEFT)) as $payment) {
                $remaining = $payment->amount;
                foreach ($openingRows as $row) {
                    if ($row->po_date >= $payment->date || $remaining <= 0) {
                        continue;
                    }
                    $applied = min($row->outstanding, $remaining);
                    $row->paid_total = round($row->paid_total + $applied, 2);
                    $row->outstanding = round($row->outstanding - $applied, 2);
                    $remaining = round($remaining - $applied, 2);
                }
            }
            $rows = $rows->concat($openingRows);
        }

        return $rows->filter(fn ($row) => $row->outstanding > 0.01)
            ->each(function ($row) use ($asOf) {
                $row->days_outstanding = (int) Carbon::parse($row->oldest_grn_date)->diffInDays(Carbon::parse($asOf));
            })
            ->sortBy(fn ($row) => strtolower($row->supplier_name).'|'.$row->oldest_grn_date.'|'.$row->is_opening)
            ->values();
    }

    private function date(string $value): string
    {
        return Carbon::parse($value)->toDateString();
    }
}
