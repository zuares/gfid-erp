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

<div class="kpi-strip mb-2" id="sc_kpi_strip">
    @if ($itemId)
        <span class="kpi-pill">Open <strong class="mono">{{ number_format($openingQty, 2, ',', '.') }}</strong></span>
        <span class="kpi-pill">Close <strong class="mono">{{ number_format($closingQty, 2, ',', '.') }}</strong></span>
    @else
        <span class="kpi-pill">Rows <strong class="mono">{{ $rows->count() }}</strong></span>
        <span class="kpi-pill">Value <strong class="mono">{{ number_format($sumValue, 0, ',', '.') }}</strong></span>
    @endif

    <span class="kpi-pill kpi-in">IN <strong class="mono">{{ number_format($sumIn, 2, ',', '.') }}</strong></span>
    <span class="kpi-pill kpi-out">OUT <strong class="mono">{{ number_format($sumOut, 2, ',', '.') }}</strong></span>
    <span class="kpi-pill">Net <strong
            class="mono">{{ $netQty >= 0 ? '+' : '' }}{{ number_format($netQty, 2, ',', '.') }}</strong></span>

    @if ($itemId)
        <span class="kpi-pill">Val <strong class="mono">{{ number_format($openingValue, 0, ',', '.') }}</strong> →
            <strong class="mono">{{ number_format($closingValue, 0, ',', '.') }}</strong></span>
    @endif
</div>
