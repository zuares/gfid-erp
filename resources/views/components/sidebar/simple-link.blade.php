{{-- resources/views/components/sidebar/simple-link.blade.php --}}
@props([
    'href',
    'icon' => '',
    'active' => false,

    // ✅ dot-only badge
    'dotOnly' => false,
    'badgeTone' => 'warn', // warn|ok|danger|info|muted
    'badgeTitle' => null, // tooltip
])

<a href="{{ $href }}"
    {{ $attributes->merge([
        'class' => 'sidebar-link ' . ($active ? 'active' : ''),
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
