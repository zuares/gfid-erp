@props([
    'date' => null,
    'time' => null,
])
@php
    $d = $date ? \Carbon\Carbon::parse($date)->locale('id') : null;
    $t = $time ? \Carbon\Carbon::parse($time) : null;
@endphp
@if ($d)
    <div class="gf-datecell">
        <span class="gf-datecell-d">{{ $d->translatedFormat('d M') }}</span>
        <span class="gf-datecell-sub">{{ $d->translatedFormat('l') }}@if ($t) · {{ $t->format('H:i') }}@endif</span>
    </div>
@else
    <span class="text-muted">-</span>
@endif
