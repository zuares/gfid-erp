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
@endphp

<div class="summary-grid mb-3" id="sc_kpi_strip">
    @if ($itemId)
        <div class="summary-card summary-card--soft">
            <div class="summary-label">Stok Awal</div>
            <div class="summary-value mono">{{ number_format($openingQty, 2, ',', '.') }}</div>
        </div>

        <div class="summary-card summary-card--in">
            <div class="summary-label">Masuk</div>
            <div class="summary-value mono">{{ number_format($sumIn, 2, ',', '.') }}</div>
        </div>

        <div class="summary-card summary-card--out">
            <div class="summary-label">Keluar</div>
            <div class="summary-value mono">{{ number_format($sumOut, 2, ',', '.') }}</div>
        </div>

        <div class="summary-card summary-card--final">
            <div class="summary-label">Stok Akhir</div>
            <div class="summary-value mono">{{ number_format($closingQty, 2, ',', '.') }}</div>
        </div>

    @else
        <div class="summary-card summary-card--soft">
            <div class="summary-label">Transaksi</div>
            <div class="summary-value mono">{{ $rows->count() }}</div>
        </div>

        <div class="summary-card summary-card--in">
            <div class="summary-label">Masuk</div>
            <div class="summary-value mono">{{ number_format($sumIn, 2, ',', '.') }}</div>
        </div>

        <div class="summary-card summary-card--out">
            <div class="summary-label">Keluar</div>
            <div class="summary-value mono">{{ number_format($sumOut, 2, ',', '.') }}</div>
        </div>

        <div class="summary-card summary-card--final">
            <div class="summary-label">Net</div>
            <div class="summary-value mono">{{ $netQty >= 0 ? '+' : '' }}{{ number_format($netQty, 2, ',', '.') }}</div>
        </div>
    @endif
</div>
