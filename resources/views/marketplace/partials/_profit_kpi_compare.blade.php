@if(($cmp['value'] ?? null) !== null)
    <div class="profit-kpi-compare {{ ($cmp['good'] ?? false) ? 'is-good' : 'is-bad' }}" title="Perubahan vs {{ $label }}">
        <i class="bi bi-arrow-{{ ($cmp['value'] ?? 0) >= 0 ? 'up-right' : 'down-right' }}"></i>
        <span>{{ abs($cmp['value']) }}%</span>
    </div>
@else
    <div class="profit-kpi-compare is-neutral" title="Belum ada data {{ $label }}">—</div>
@endif
