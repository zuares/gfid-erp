@php
    /**
     * Kartu KPI. Var yang diterima:
     *  label, value, sub (opsional), color (green|blue|amber|red|violet),
     *  icon (bootstrap icon class), url (opsional → jadi link), cta (opsional),
     *  small (bool → value lebih kecil)
     */
    $tag = !empty($url) ? 'a' : 'div';
    $color = $color ?? '';
    $small = $small ?? false;
@endphp
<{{ $tag }} class="kpi {{ $color }}" @if(!empty($url)) href="{{ $url }}" @endif>
    <span class="kpi-label"><span class="ico"><i class="bi {{ $icon ?? 'bi-dot' }}"></i></span> {{ $label }}</span>
    <div class="kpi-value {{ $small ? 'sm' : '' }}">{{ $value }}</div>
    @if(!empty($sub))
        <div class="kpi-sub">{{ $sub }}</div>
    @endif
    @if(!empty($cta) && !empty($url))
        <span class="kpi-cta">{{ $cta }} <i class="bi bi-arrow-right-short"></i></span>
    @endif
</{{ $tag }}>
