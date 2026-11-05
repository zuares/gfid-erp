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
            <div class="summary-card-head"><div class="summary-label">Stok awal</div><span class="summary-icon"><i class="bi bi-boxes"></i></span></div>
            <div class="summary-value mono {{ abs((float) $openingQty) < 0.000001 ? 'summary-value--zero' : '' }}">{{ $fmtBalance($openingQty) }}</div>
            <div class="summary-sub">Sebelum periode</div>
        </div>

        <div class="summary-card summary-card--in">
            <div class="summary-card-head"><div class="summary-label">Masuk</div><span class="summary-icon"><i class="bi bi-arrow-down-left"></i></span></div>
            <div class="summary-value mono">{{ $fmtMove($sumIn) }}</div>
            <div class="summary-sub">Total IN</div>
        </div>

        <div class="summary-card summary-card--out">
            <div class="summary-card-head"><div class="summary-label">Keluar</div><span class="summary-icon"><i class="bi bi-arrow-up-right"></i></span></div>
            <div class="summary-value mono">{{ $fmtMove($sumOut) }}</div>
            <div class="summary-sub">Total OUT</div>
        </div>

        <div class="summary-card summary-card--final">
            <div class="summary-card-head"><div class="summary-label">Stok akhir</div><span class="summary-icon"><i class="bi bi-check2-circle"></i></span></div>
            <div class="summary-value mono {{ abs((float) $closingQty) < 0.000001 ? 'summary-value--zero' : '' }}">{{ $fmtBalance($closingQty) }}</div>
            <div class="summary-sub">Saldo berjalan</div>
        </div>

    @else
        <div class="summary-card summary-card--soft">
            <div class="summary-card-head"><div class="summary-label">Transaksi</div><span class="summary-icon"><i class="bi bi-list-check"></i></span></div>
            <div class="summary-value mono">{{ $rows->count() }}</div>
            <div class="summary-sub">Pada halaman ini</div>
        </div>

        <div class="summary-card summary-card--in">
            <div class="summary-card-head"><div class="summary-label">Masuk</div><span class="summary-icon"><i class="bi bi-arrow-down-left"></i></span></div>
            <div class="summary-value mono">{{ $fmtMove($sumIn) }}</div>
            <div class="summary-sub">Total IN</div>
        </div>

        <div class="summary-card summary-card--out">
            <div class="summary-card-head"><div class="summary-label">Keluar</div><span class="summary-icon"><i class="bi bi-arrow-up-right"></i></span></div>
            <div class="summary-value mono">{{ $fmtMove($sumOut) }}</div>
            <div class="summary-sub">Total OUT</div>
        </div>

        <div class="summary-card summary-card--final">
            <div class="summary-card-head"><div class="summary-label">Net</div><span class="summary-icon"><i class="bi bi-activity"></i></span></div>
            <div class="summary-value mono {{ abs($netQty) < 0.000001 ? 'summary-value--zero' : '' }}">{{ abs($netQty) < 0.000001 ? '0' : ($netQty >= 0 ? '+' : '') . number_format($netQty, 2, ',', '.') }}</div>
            <div class="summary-sub">Masuk dikurangi keluar</div>
        </div>
    @endif
</div>

<div class="summary-inline-note mt-2">
    Tanda <span class="mono">-</span> dipakai untuk mutasi yang kosong. Nilai saldo tetap ditampilkan sebagai <span class="mono">0</span> bila memang habis.
</div>
