@php
    /**
     * Kartu KPI. Var yang diterima:
     *  label, value, sub (opsional), color (green|blue|amber|red|violet),
     *  icon (bootstrap icon class), url (opsional → jadi link), cta (opsional),
     *  small (bool → value lebih kecil), comparisons (array opsional)
     */
    $tag = !empty($url) ? 'a' : 'div';
    $color = $color ?? '';
    $small = $small ?? false;
    $comparisons = $comparisons ?? [];
    $variant = $variant ?? '';
    $featured = $featured ?? false;
@endphp
<{{ $tag }} class="kpi {{ $color }} {{ $variant }} {{ $featured ? 'kpi-featured' : '' }}" @if(!empty($url)) href="{{ $url }}" @endif>
    <span class="kpi-label"><span class="ico"><i class="bi {{ $icon ?? 'bi-dot' }}"></i></span> {{ $label }}</span>
    <div class="kpi-value {{ $small ? 'sm' : '' }}">{{ $value }}</div>
    @if(!empty($sub))
        <div class="kpi-sub">{{ $sub }}</div>
    @endif
    @if(!empty($comparisons))
        <div class="kpi-comparisons">
            @foreach($comparisons as $comparison)
                <span class="kpi-compare {{ $comparison['tone'] ?? 'muted' }}">
                    <span>{{ $comparison['label'] }}</span>
                    <strong>
                        {{ $comparison['value'] }}
                        @if(!empty($comparison['delta']))
                            <small class="kpi-compare-delta {{ $comparison['delta_tone'] ?? 'muted' }}">{{ $comparison['delta'] }}</small>
                        @endif
                    </strong>
                </span>
            @endforeach
        </div>
    @endif
    @if(!empty($cta) && !empty($url))
        <span class="kpi-cta">{{ $cta }} <i class="bi bi-arrow-right-short"></i></span>
    @endif
</{{ $tag }}>
