@props([
    'title' => null,
    'subtitle' => null,
    'flush' => false,
])

<div {{ $attributes->merge(['class' => 'gf-panel']) }}>
    @if($title || $subtitle || isset($actions))
        <div class="gf-panel-header">
            <div>
                @if($title)
                    <div class="gf-panel-title">{{ $title }}</div>
                @endif

                @if($subtitle)
                    <div class="gf-subtext">{{ $subtitle }}</div>
                @endif
            </div>

            @isset($actions)
                <div class="gf-panel-actions">
                    {{ $actions }}
                </div>
            @endisset
        </div>
    @endif

    <div class="{{ $flush ? '' : 'gf-panel-body' }}">
        {{ $slot }}
    </div>
</div>
