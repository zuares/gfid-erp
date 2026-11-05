@props([
    'type' => 'empty',
    'title' => null,
    'message' => null,
    'icon' => null,
])

@php
    $stateType = in_array($type, ['empty', 'loading', 'error'], true) ? $type : 'empty';
    $stateTitle = $title ?: match ($stateType) {
        'loading' => 'Memuat data',
        'error' => 'Data belum dapat dimuat',
        default => 'Belum ada data',
    };
    $stateMessage = $message ?: match ($stateType) {
        'loading' => 'Menyiapkan data terbaru…',
        'error' => 'Coba muat ulang halaman atau ubah filter.',
        default => 'Belum ada data untuk ditampilkan pada filter ini.',
    };
    $stateIcon = $icon ?: ($stateType === 'error' ? 'bi-exclamation-triangle' : 'bi-inbox');
@endphp

<div class="ds-empty gf-analytics-state gf-analytics-state-{{ $stateType }}" role="status" aria-live="polite">
    <div>
        @if ($stateType === 'loading')
            <div class="ds-skeleton" aria-hidden="true">
                <span></span>
                <span></span>
                <span></span>
            </div>
            <p class="ds-empty-loading-label">{{ $stateTitle }}</p>
        @else
            <div class="ds-empty-icon" aria-hidden="true"><i class="bi {{ $stateIcon }}"></i></div>
            <strong>{{ $stateTitle }}</strong>
            <p>{{ $stateMessage }}</p>
        @endif
    </div>
</div>
