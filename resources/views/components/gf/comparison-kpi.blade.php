@props([
    'label',
    'valueId',
    'noteId',
    'icon' => 'bi-graph-up-arrow',
    'infoId' => null,
    'infoLabel' => null,
    'tooltip' => null,
])

<article {{ $attributes->merge(['class' => 'ds-comparison-kpi an-kpi']) }} aria-label="{{ $label }}">
    <div class="ds-comparison-kpi-main">
        <div>
            <span class="ds-comparison-kpi-label">
                {{ $label }}
                @if($infoId)
                    <i id="{{ $infoId }}" class="bi bi-info-circle an-kpi-info" tabindex="0" role="img" aria-label="{{ $infoLabel ?: 'Penjelasan '.$label }}" data-tooltip="{{ $tooltip }}"></i>
                @endif
            </span>
            <strong class="an-kpi-value" id="{{ $valueId }}">—</strong>
            <span class="ds-comparison-kpi-description an-kpi-note" id="{{ $noteId }}">{{ $slot }}</span>
        </div>
        <span class="ds-comparison-kpi-icon" aria-hidden="true"><i class="bi {{ $icon }}"></i></span>
    </div>
    <div class="ds-comparison-kpi-comparisons" aria-label="Perbandingan periode">
        <div class="ds-comparison-kpi-row an-kpi-comparison" data-kpi-comparison="{{ $valueId }}">
            <div>
                <span>Periode pembanding</span>
                <small data-kpi-compare-period>Periode sebelumnya</small>
            </div>
            <div class="ds-comparison-kpi-result">
                <strong data-kpi-compare-value>—</strong>
                <span class="ds-comparison-kpi-change is-neutral" data-kpi-compare-change><i class="bi bi-dash" aria-hidden="true"></i> —</span>
            </div>
        </div>
    </div>
</article>
