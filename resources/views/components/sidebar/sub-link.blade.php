{{-- resources/views/components/sidebar/sub-link.blade.php --}}
@props([
    'href',
    'icon' => '',
    'active' => false,

    // ✅ dot-only badge
    'dotOnly' => false,
    'badgeTone' => 'warn',
    'badgeTitle' => null,
])

<a href="{{ $href }}"
    {{ $attributes->merge([
        'class' => 'sidebar-link-sub ' . ($active ? 'active' : ''),
    ]) }}>
    @if ($icon)
        <span class="icon">
            @if (str_starts_with($icon, 'bi '))
                <i class="{{ $icon }}"></i>
            @else
                {{ $icon }}
            @endif
        </span>
    @endif

    <span>{{ $slot }}</span>

    @if ($dotOnly)
        <span class="nav-dot {{ $badgeTone }}" title="{{ $badgeTitle ?? '' }}"
            aria-label="{{ $badgeTitle ?? 'Notifikasi' }}"></span>
    @endif
</a>
