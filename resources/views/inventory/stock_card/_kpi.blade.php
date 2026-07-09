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
    $sumValue = (float) ($totals['sumValue'] ?? 0);
@endphp

<div class="kpis mb-3" id="sc_kpi_strip">
    @if ($itemId)
        <span class="kpi"><span class="lbl">Open</span> <span class="val mono">{{ number_format($openingQty, 2, ',', '.') }}</span></span>
        <span class="kpi"><span class="lbl">Close</span> <span class="val mono">{{ number_format($closingQty, 2, ',', '.') }}</span></span>
    @else
        <span class="kpi"><span class="lbl">Rows</span> <span class="val mono">{{ $rows->count() }}</span></span>
        @if ($canViewCost ?? false)
            <span class="kpi"><span class="lbl">Value</span> <span class="val mono">{{ number_format($sumValue, 0, ',', '.') }}</span></span>
        @endif
    @endif

    <span class="kpi"><span class="lbl" style="color:var(--in, var(--bs-teal))">IN</span> <span class="val mono">{{ number_format($sumIn, 2, ',', '.') }}</span></span>
    <span class="kpi"><span class="lbl" style="color:var(--out, var(--bs-orange))">OUT</span> <span class="val mono">{{ number_format($sumOut, 2, ',', '.') }}</span></span>
    <span class="kpi"><span class="lbl">Net</span> <span class="val mono">{{ $netQty >= 0 ? '+' : '' }}{{ number_format($netQty, 2, ',', '.') }}</span></span>

    @if ($itemId && ($canViewCost ?? false))
        <span class="kpi"><span class="lbl">Val</span> <span class="val mono">{{ number_format($openingValue, 0, ',', '.') }} &rarr; {{ number_format($closingValue, 0, ',', '.') }}</span></span>
    @endif
</div>
