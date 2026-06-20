@php
    $visibleLines = $pr->lines->take(2);
    $hiddenLines = $pr->lines->skip(2);
    $itemsTarget = 'pr-items-' . $surface . '-' . $pr->id;
    $estTotal = $pr->lines->sum(fn ($line) => ($line->qty ?? 0) * ($line->unit_price ?? 0));
    $hasEstimate = $pr->lines->whereNotNull('unit_price')->isNotEmpty();
@endphp

<div class="pr-items-preview">
    @foreach ($visibleLines as $line)
        <div class="pr-item-line">
            <div class="pr-item-main">
                <span class="pr-item-name">{{ $line->item?->name ?? 'Barang tidak ditemukan' }}</span>
                <span class="pr-item-code">{{ $line->item?->code }}</span>
            </div>
            <span class="pr-item-qty">{{ number_format($line->qty, 2, ',', '.') }} {{ $line->item?->unit }}</span>
        </div>
    @endforeach

    @if ($hiddenLines->isNotEmpty())
        <div class="d-none" id="{{ $itemsTarget }}">
            @foreach ($hiddenLines as $line)
                <div class="pr-item-line">
                    <div class="pr-item-main">
                        <span class="pr-item-name">{{ $line->item?->name ?? 'Barang tidak ditemukan' }}</span>
                        <span class="pr-item-code">{{ $line->item?->code }}</span>
                    </div>
                    <span class="pr-item-qty">{{ number_format($line->qty, 2, ',', '.') }} {{ $line->item?->unit }}</span>
                </div>
            @endforeach
        </div>
        <button type="button" class="btn btn-link btn-sm p-0 mt-1 pr-items-toggle" data-target="{{ $itemsTarget }}" data-count="{{ $hiddenLines->count() }}">
            +{{ $hiddenLines->count() }} barang
        </button>
    @endif

    @if ($canSeeMoney && $hasEstimate)
        <div class="pr-item-estimate">Estimasi Rp {{ number_format($estTotal, 0, ',', '.') }}</div>
    @endif
</div>
