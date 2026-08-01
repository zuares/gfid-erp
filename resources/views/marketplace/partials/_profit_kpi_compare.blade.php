@if(($cmp['value'] ?? null) !== null)
    <div class="profit-kpi-compare {{ ($cmp['good'] ?? false) ? 'is-good' : 'is-bad' }}" title="Perubahan vs {{ $label }}">
        <div class="profit-kpi-compare-change">
            <i class="bi bi-arrow-{{ ($cmp['value'] ?? 0) >= 0 ? 'up-right' : 'down-right' }}"></i>
            <span>{{ abs($cmp['value']) }}%</span>
        </div>
        <div class="profit-kpi-compare-prev">{{ $cmp['previous_display'] ?? '—' }}</div>
    </div>
@elseif(($cmp['is_new'] ?? false))
    <div class="profit-kpi-compare is-neutral" title="Belum ada data {{ $label }}">
        <div class="profit-kpi-compare-change">Baru</div>
        <div class="profit-kpi-compare-prev">{{ $cmp['previous_display'] ?? '—' }}</div>
    </div>
@else
    <div class="profit-kpi-compare is-neutral" title="Tidak ada perubahan vs {{ $label }}">
        <div class="profit-kpi-compare-change">—</div>
        <div class="profit-kpi-compare-prev">{{ $cmp['previous_display'] ?? '—' }}</div>
    </div>
@endif
