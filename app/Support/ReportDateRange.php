<?php

namespace App\Support;

use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * Resolver rentang tanggal untuk laporan Sales.
 * Satu tempat untuk parsing filter tanggal supaya tidak ditulis ulang di tiap controller.
 */
final class ReportDateRange
{
    private function __construct(
        public readonly ?string $from,        // 'Y-m-d' atau null
        public readonly ?string $to,          // 'Y-m-d' atau null
        public readonly ?Carbon $fromCarbon = null,
        public readonly ?Carbon $toCarbon = null,
    ) {}

    /**
     * Passthrough mentah: baca key dari request apa adanya (nullable),
     * tanpa default & tanpa normalisasi. String kosong dianggap null.
     */
    public static function fromRequest(Request $request, string $fromKey = 'date_from', string $toKey = 'date_to'): self
    {
        $from = $request->input($fromKey);
        $to = $request->input($toKey);

        return new self(
            $from === null || $from === '' ? null : $from,
            $to === null || $to === '' ? null : $to,
        );
    }

    /**
     * Window default N hari terakhir, dengan swap guard + start/end of day.
     * Mereplikasi perilaku lama SalesReportController (default 30 hari).
     */
    public static function lastDays(Request $request, int $days = 30, string $fromKey = 'from', string $toKey = 'to', ?string $tz = null): self
    {
        $tz = $tz ?: config('app.timezone', 'Asia/Jakarta');

        $from = $request->query($fromKey)
            ? Carbon::parse($request->query($fromKey), $tz)->startOfDay()
            : now($tz)->subDays($days - 1)->startOfDay();

        $to = $request->query($toKey)
            ? Carbon::parse($request->query($toKey), $tz)->endOfDay()
            : now($tz)->endOfDay();

        // Guard: kalau kebalik, swap
        if ($from->gt($to)) {
            [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
        }

        return new self($from->toDateString(), $to->toDateString(), $from, $to);
    }

    /**
     * Jumlah hari inklusif dalam range (minimal 1). Hanya valid untuk range
     * yang dibuat via lastDays(); passthrough mengembalikan 1.
     */
    public function days(): int
    {
        if (!$this->fromCarbon || !$this->toCarbon) {
            return 1;
        }

        return max(1, (int) $this->fromCarbon->diffInDays($this->toCarbon) + 1);
    }
}
