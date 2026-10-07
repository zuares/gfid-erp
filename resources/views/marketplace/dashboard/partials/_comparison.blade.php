@php
    $comparison = $comparison ?? null;
    $class = $class ?? '';
    $periodLabel = $period_label ?? 'periode sebelumnya';
    $contextLabel = $context_label ?? 'vs periode';
    $icon = ($comparison['tone'] ?? 'is-neutral') === 'is-good'
        ? 'bi-arrow-up-right'
        : (($comparison['tone'] ?? 'is-neutral') === 'is-bad' ? 'bi-arrow-down-right' : 'bi-dash');
@endphp
@if ($comparison)
    <div class="sales-compare-line {{ $class }} {{ $comparison['tone'] ?? 'is-neutral' }}"
         title="Periode pembanding {{ $periodLabel }}: {{ $comparison['previous_label'] ?? '—' }} · Perubahan: {{ $comparison['delta_label'] ?? '—' }}"
         aria-label="Periode pembanding {{ $periodLabel }}, nilai {{ $comparison['previous_label'] ?? '—' }}, perubahan {{ $comparison['delta_label'] ?? '—' }}">
        <span class="sales-compare-context">{{ $contextLabel }}</span>
        <i class="bi {{ $icon }}" aria-hidden="true"></i>
        <strong>{{ $comparison['delta_label'] ?? '—' }}</strong>
    </div>
@endif
