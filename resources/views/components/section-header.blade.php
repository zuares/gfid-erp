@props([
    'title',
    'description' => null,
    'count' => null,
    'eyebrow' => null,
    'icon' => null,
    'id' => null,
])

@php
    $headingId = $id ? $id . '-title' : 'ui-section-header-' . uniqid();
    $hasActions = isset($actions) && $actions->isNotEmpty();
@endphp

<header {{ $attributes->merge(['class' => 'ui-section-header']) }} @if($id) id="{{ $id }}" @endif aria-labelledby="{{ $headingId }}">
    <div class="ui-section-header-main">
        @if($eyebrow || $icon)
            <div class="ui-section-header-eyebrow">
                @if($icon)
                    <i class="bi {{ $icon }}" aria-hidden="true"></i>
                @endif
                @if($eyebrow)
                    <span>{{ $eyebrow }}</span>
                @endif
            </div>
        @endif

        <h2 id="{{ $headingId }}">{{ $title }}</h2>

        @if($description)
            <p>{{ $description }}</p>
        @endif
    </div>

    @if($count || $hasActions)
        <div class="ui-section-header-aside">
            @if($count)
                <span class="ui-section-header-count">{{ $count }}</span>
            @endif
            @if($hasActions)
                <div class="ui-section-header-actions">{{ $actions }}</div>
            @endif
        </div>
    @endif
</header>

@once
    @push('head')
        <style>
            .ui-section-header { display: flex; align-items: flex-end; justify-content: space-between; gap: 1rem; margin: 1.45rem .2rem .72rem; color: var(--text, #0f172a); }
            .ui-section-header-main { min-width: 0; }
            .ui-section-header-eyebrow { display: inline-flex; align-items: center; gap: .35rem; margin-bottom: .28rem; color: var(--accent, #2563eb); font-size: .62rem; font-weight: 850; letter-spacing: .08em; line-height: 1; text-transform: uppercase; }
            .ui-section-header-eyebrow i { font-size: .72rem; }
            .ui-section-header h2 { margin: 0; color: var(--text, #0f172a); font-size: 1.05rem; font-weight: 850; letter-spacing: -.035em; line-height: 1.2; }
            .ui-section-header p { max-width: 700px; margin: .25rem 0 0; color: var(--muted, #64748b); font-size: .75rem; line-height: 1.45; }
            .ui-section-header-aside { display: flex; align-items: center; justify-content: flex-end; gap: .65rem; flex-wrap: wrap; }
            .ui-section-header-count { color: var(--muted, #64748b); font-size: .67rem; font-weight: 800; white-space: nowrap; }
            .ui-section-header-actions { display: inline-flex; align-items: center; gap: .42rem; flex-wrap: wrap; }
            @media (max-width: 700px) {
                .ui-section-header { align-items: flex-start; flex-direction: column; gap: .55rem; }
                .ui-section-header-aside { justify-content: flex-start; }
            }
            @media (prefers-reduced-motion: reduce) {
                .ui-section-header * { scroll-behavior: auto; }
            }
        </style>
    @endpush
@endonce
