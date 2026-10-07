@php($kpiColumnClass = $kpiColumnClass ?? (count($kpis) >= 5 ? 'col-xl' : 'col-xl-3'))
@php($lastKpiGroup = null)
<section class="row g-3 mb-4" aria-label="KPI {{ $kpiTitle }}">
    @foreach ($kpis as $kpi)
        @if (!empty($kpi['group']) && $kpi['group'] !== $lastKpiGroup)
            <div class="col-12 sales-kpi-group-divider" role="presentation">
                <span class="sales-kpi-group-label">{{ $kpi['group'] }}</span>
            </div>
            @php($lastKpiGroup = $kpi['group'])
        @endif
        <div class="col-12 col-sm-6 {{ $kpiColumnClass }}">
            <div class="card sales-card sales-kpi {{ $kpi['variant'] ?? '' }} h-100 shadow-sm">
                <div class="card-body p-3">
                    <div class="d-flex align-items-start justify-content-between mb-3">
                        <span class="sales-kpi-label">{{ $kpi['label'] }}</span>
                        <span class="sales-kpi-icon"><i class="bi {{ $kpi['icon'] }}"></i></span>
                    </div>
                    <div class="sales-kpi-primary">
                        <div class="sales-kpi-value">{{ is_numeric($kpi['value']) ? number_format($kpi['value']) : $kpi['value'] }}</div>
                    </div>
                    @if (!empty($kpi['comparisons']))
                        <div class="sales-kpi-comparisons mt-2">
                            @foreach ($kpi['comparisons'] as $comparisonItem)
                                @include('marketplace.dashboard.partials._comparison', ['comparison' => $comparisonItem['value'], 'class' => 'sales-kpi-comparison', 'context_label' => $comparisonItem['label'], 'period_label' => str_contains(strtolower($comparisonItem['label']), 'bln') ? ($comparisonMonthLabel ?? 'bulan lalu') : ($comparisonPeriodLabel ?? 'periode lalu')])
                            @endforeach
                        </div>
                    @endif
                    @if (filled($kpi['note'] ?? null))
                        <div class="sales-kpi-note mt-1">{{ $kpi['note'] }}</div>
                    @endif
                </div>
            </div>
        </div>
    @endforeach
</section>
