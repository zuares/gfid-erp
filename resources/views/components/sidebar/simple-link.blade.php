@props(['href', 'icon' => '', 'active' => false])

<a href="{{ $href }}"
    {{ $attributes->merge([
        'class' => 'sidebar-link ' . ($active ? 'active' : ''),
    ]) }}>
    @if ($icon)
        <span class="icon">{{ $icon }}</span>
    @endif
    <span>{{ $slot }}</span>
</a>
