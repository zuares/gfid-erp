@props([
    'eyebrow' => null,
    'title' => null,
    'description' => null,
])

<div {{ $attributes->merge(['class' => 'gf-master-section']) }}>
    <div class="gf-master-header">
        <div class="gf-master-header-layout">
            <div class="gf-master-header-copy">
                @if($eyebrow)
                    <div class="gf-master-eyebrow">{{ $eyebrow }}</div>
                @endif

                @if($title)
                    <h1 class="gf-master-title">{{ $title }}</h1>
                @endif

                @if($description)
                    <p class="gf-master-desc">{{ $description }}</p>
                @endif

                @isset($meta)
                    <div class="gf-master-meta">
                        {{ $meta }}
                    </div>
                @endisset
            </div>

            @isset($actions)
                <div class="gf-master-actions">
                    {{ $actions }}
                </div>
            @endisset
        </div>

        @isset($header)
            <div class="gf-master-header-extra">
                {{ $header }}
            </div>
        @endisset
    </div>

    {{ $slot }}
</div>
