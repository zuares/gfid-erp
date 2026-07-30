@php
    $itemId = $filters['item_id'] ?? null;

    $rows = $mutations instanceof \Illuminate\Pagination\AbstractPaginator ? $mutations->getCollection() : $mutations;

    $sumIn =
        (float) ($totals['sumIn'] ??
            $rows->sum(fn($m) => ($m->direction ?? null) === 'in' ? abs((float) $m->qty_change) : 0));
    $sumOut =
        (float) ($totals['sumOut'] ??
            $rows->sum(fn($m) => ($m->direction ?? null) === 'out' ? abs((float) $m->qty_change) : 0));
    $netQty = $sumIn - $sumOut;
    $fmtMove = fn ($value) => abs((float) $value) < 0.000001 ? '-' : number_format((float) $value, 2, ',', '.');
    $fmtBalance = fn ($value) => abs((float) $value) < 0.000001 ? '0' : number_format((float) $value, 2, ',', '.');
@endphp

<div class="summary-grid mb-3" id="sc_kpi_strip">
    @if ($itemId)
        <div class="summary-card summary-card--soft">
            <div class="summary-label">Stok Awal</div>
            <div class="summary-value mono {{ abs((float) $openingQty) < 0.000001 ? 'summary-value--zero' : '' }}">{{ $fmtBalance($openingQty) }}</div>
        </div>

        <div class="summary-card summary-card--in">
            <div class="summary-label">Masuk</div>
            <div class="summary-value mono">{{ $fmtMove($sumIn) }}</div>
        </div>

        <div class="summary-card summary-card--out">
            <div class="summary-label">Keluar</div>
            <div class="summary-value mono">{{ $fmtMove($sumOut) }}</div>
        </div>

        <div class="summary-card summary-card--final">
            <div class="summary-label">Stok Akhir</div>
            <div class="summary-value mono {{ abs((float) $closingQty) < 0.000001 ? 'summary-value--zero' : '' }}">{{ $fmtBalance($closingQty) }}</div>
        </div>

    @else
        <div class="summary-card summary-card--soft">
            <div class="summary-label">Transaksi</div>
            <div class="summary-value mono">{{ $rows->count() }}</div>
        </div>

        <div class="summary-card summary-card--in">
            <div class="summary-label">Masuk</div>
            <div class="summary-value mono">{{ $fmtMove($sumIn) }}</div>
        </div>

        <div class="summary-card summary-card--out">
            <div class="summary-label">Keluar</div>
            <div class="summary-value mono">{{ $fmtMove($sumOut) }}</div>
        </div>

        <div class="summary-card summary-card--final">
            <div class="summary-label">Net</div>
            <div class="summary-value mono {{ abs($netQty) < 0.000001 ? 'summary-value--zero' : '' }}">{{ abs($netQty) < 0.000001 ? '0' : ($netQty >= 0 ? '+' : '') . number_format($netQty, 2, ',', '.') }}</div>
        </div>
    @endif
</div>

<div class="summary-inline-note mt-2">
    Tanda <span class="mono">-</span> dipakai untuk mutasi yang kosong. Nilai saldo tetap ditampilkan sebagai <span class="mono">0</span> bila memang habis.
</div>
