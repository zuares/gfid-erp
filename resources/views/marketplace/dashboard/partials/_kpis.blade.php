@php($kpiColumnClass = count($kpis) >= 5 ? 'col-xl' : 'col-xl-3')
<section class="row g-3 mb-4" aria-label="KPI {{ $kpiTitle }}">
    @foreach ($kpis as $kpi)
        <div class="col-12 col-sm-6 {{ $kpiColumnClass }}">
            <div class="card sales-card sales-kpi {{ $kpi['variant'] ?? '' }} h-100 shadow-sm">
                <div class="card-body p-3">
                    <div class="d-flex align-items-start justify-content-between mb-3">
                        <span class="sales-kpi-label">{{ $kpi['label'] }}</span>
                        <span class="sales-kpi-icon"><i class="bi {{ $kpi['icon'] }}"></i></span>
                    </div>
                    <div class="sales-kpi-value">{{ is_numeric($kpi['value']) ? number_format($kpi['value']) : $kpi['value'] }}</div>
                    @if (filled($kpi['note'] ?? null))
                        <div class="sales-kpi-note mt-1">{{ $kpi['note'] }}</div>
                    @endif
                </div>
            </div>
        </div>
    @endforeach
</section>
