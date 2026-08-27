<?php

namespace App\Services\Payroll;

use App\Models\Employee;
use App\Models\PieceRate;
use App\Models\PieceworkPayrollLine;
use App\Models\PieceworkPayrollPeriod;
use App\Models\SewingPickupLine;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SewingPayrollGenerator
{
    public static function generate(
        $periodStart,
        $periodEnd,
        ?int $createdByUserId = null,
        ?PieceworkPayrollPeriod $existingPeriod = null,
    ): PieceworkPayrollPeriod {
        // Normalisasi tanggal ke Carbon
        $start = Carbon::parse($periodStart)->startOfDay();
        $end = Carbon::parse($periodEnd)->endOfDay();

        return DB::transaction(function () use ($start, $end, $createdByUserId, $existingPeriod) {
            // ==========================
            // 1. Siapkan / pakai period
            // ==========================
            if ($existingPeriod) {
                // MODE REGENERATE IN-PLACE
                /** @var PieceworkPayrollPeriod $period */
                $period = $existingPeriod->fresh();

                // Bersihkan semua line lama
                $period->lines()->delete();

                // Reset total
                $period->total_amount = 0;
                $period->save();
            } else {
                // MODE GENERATE BARU
                $period = PieceworkPayrollPeriod::create([
                    'module' => 'sewing',
                    'period_start' => $start->toDateString(),
                    'period_end' => $end->toDateString(),
                    'status' => 'draft',
                    'created_by' => $createdByUserId,
                    'total_amount' => 0,
                ]);
            }

            // ==========================
            // 2. AGREGASI DARI AMBIL JAHIT
            // ==========================
            // Basis pembayaran adalah qty yang benar-benar diambil operator,
            // bukan qty setor atau qty lolos QC. wage_per_pcs pada pickup line
            // menjadi snapshot tarif agar perubahan master tarif tidak mengubah
            // histori pekerjaan yang sudah diambil.
            $rows = SewingPickupLine::query()
                ->join('sewing_pickups', 'sewing_pickup_lines.sewing_pickup_id', '=', 'sewing_pickups.id')
                ->join('items', 'sewing_pickup_lines.finished_item_id', '=', 'items.id')
                ->whereBetween('sewing_pickups.date', [$start->toDateString(), $end->toDateString()])
                ->whereNull('sewing_pickups.voided_at')
                ->where('sewing_pickups.status', '!=', 'void')
                ->whereNull('sewing_pickup_lines.voided_at')
                ->where('sewing_pickup_lines.status', '!=', 'void')
                ->where('sewing_pickup_lines.qty_bundle', '>', 0)
                ->select([
                    'sewing_pickup_lines.id as pickup_line_id',
                    'sewing_pickup_lines.finished_item_id as item_id',
                    'sewing_pickups.operator_id as pickup_employee_id',
                    'sewing_pickups.date as pickup_date',
                    'sewing_pickup_lines.qty_bundle',
                    'sewing_pickup_lines.wage_per_pcs',
                    'items.item_category_id as item_category_from_item',
                ])
                ->orderBy('sewing_pickup_lines.id')
                ->get();

            $grouped = [];
            foreach ($rows as $row) {
                $employeeId = (int) $row->pickup_employee_id;
                $itemId = (int) $row->item_id;
                $categoryId = (int) ($row->item_category_from_item ?: 0);
                $qty = (float) $row->qty_bundle;

                if ($employeeId <= 0 || $itemId <= 0 || $qty <= 0) {
                    continue;
                }

                $rateValue = (float) ($row->wage_per_pcs ?? 0);
                if ($rateValue <= 0) {
                    // Fallback hanya untuk data lama sebelum snapshot tarif
                    // disimpan di pickup line.
                    $rateValue = self::resolveRate(
                        employeeId: $employeeId,
                        itemCategoryId: $categoryId ?: null,
                        itemId: $itemId,
                        atDate: Carbon::parse($row->pickup_date)->toDateString(),
                    );
                }

                if ($rateValue <= 0) {
                    throw new \RuntimeException(
                        "Tarif upah jahit belum tersedia untuk operator #{$employeeId}, item #{$itemId}, "
                        .'tanggal '.Carbon::parse($row->pickup_date)->format('d/m/Y').'.'
                    );
                }

                // Pisahkan line jika tarif snapshot berbeda dalam satu periode.
                $key = implode('|', [
                    $employeeId,
                    $categoryId,
                    $itemId,
                    number_format($rateValue, 4, '.', ''),
                ]);

                if (! isset($grouped[$key])) {
                    $grouped[$key] = [
                        'employee_id' => $employeeId,
                        'item_category_id' => $categoryId ?: null,
                        'item_id' => $itemId,
                        'total_qty_ok' => 0.0,
                        'rate_per_pcs' => $rateValue,
                    ];
                }

                $grouped[$key]['total_qty_ok'] += $qty;
            }

            $totalAmount = 0.0;
            foreach ($grouped as $row) {
                $qty = round((float) $row['total_qty_ok'], 2);
                $rateValue = round((float) $row['rate_per_pcs'], 2);
                $amount = round($rateValue * $qty, 2);
                $totalAmount += $amount;

                PieceworkPayrollLine::create([
                    'payroll_period_id' => $period->id,
                    'employee_id' => $row['employee_id'],
                    'item_category_id' => $row['item_category_id'],
                    'item_id' => $row['item_id'],
                    'total_qty_ok' => $qty,
                    'rate_per_pcs' => $rateValue,
                    'amount' => $amount,
                ]);
            }

            // ==========================
            // 3. Update total & return
            // ==========================
            $period->update([
                'total_amount' => $totalAmount,
            ]);

            return $period->fresh(['lines']);
        });
    }

    /**
     * Ambil tarif borongan SEWING
     * - module = 'sewing'
     * - prioritas: item_id > item_category_id > default_piece_rate karyawan
     */
    public static function resolveRate(
        int $employeeId,
        ?int $itemCategoryId,
        ?int $itemId,
        string $atDate
    ): float {
        $query = PieceRate::query()
            ->where('module', 'sewing')
            ->where('employee_id', $employeeId)
            ->where('effective_from', '<=', $atDate)
            ->where(function ($q) use ($atDate) {
                $q->whereNull('effective_to')
                    ->orWhere('effective_to', '>=', $atDate);
            });

        $query->where(function ($q) use ($itemId, $itemCategoryId) {
            if ($itemId) {
                $q->where('item_id', $itemId);
            }

            if ($itemCategoryId) {
                $q->orWhere(function ($q2) use ($itemCategoryId) {
                    $q2->whereNull('item_id')
                        ->where('item_category_id', $itemCategoryId);
                });
            }
        });

        $pieceRate = $query
            ->orderByDesc('item_id')
            ->orderByDesc('item_category_id')
            ->first();

        if ($pieceRate) {
            return (float) $pieceRate->rate_per_pcs;
        }

        $employee = Employee::find($employeeId);
        if ($employee && $employee->default_piece_rate) {
            return (float) $employee->default_piece_rate;
        }

        return 0.0;
    }
}
