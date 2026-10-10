@if ($activeComparison)
    @if (collect($salesTwinComparisonPeriods)->contains(fn ($period) => $period['has_event']))
        <section class="card sales-card sales-comparison-section shadow-sm mb-3" aria-labelledby="sales-twin-comparison-title">
            <div class="sales-section-header d-flex flex-wrap align-items-start justify-content-between gap-2">
                <div>
                    <div class="sales-kicker mb-1">Perbandingan tanggal kembar</div>
                    <h2 id="sales-twin-comparison-title" class="sales-section-title mb-1">Perbandingan {{ collect($salesTwinComparisonPeriods)->pluck('label')->implode(', ') }}</h2>
                    <div class="sales-section-subtitle d-block">Baseline adalah tanggal kembar pada bulan aktif, dibandingkan dengan tiga tanggal kembar sebelumnya.</div>
                </div>
                <span class="badge sales-badge rounded-pill px-3 py-2"><i class="bi bi-calendar2-event me-1" aria-hidden="true"></i>Bulanan</span>
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle sales-table sales-sales-comparison-table mb-0">
                    <thead>
                        <tr>
                            <th class="ps-3" scope="col">Metrik</th>
                            @foreach ($salesTwinComparisonPeriods as $periodIndex => $period)
                                <th class="text-end {{ $periodIndex === 0 ? 'sales-period-current' : '' }}" scope="col">
                                    <span class="sales-period-label">{{ $period['label'] }}</span>
                                    <span class="sales-period-range sales-twin-period-range">{{ $period['event_date'] }}</span>
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($salesTwinComparisonRows as $twinRow)
                            @php $twinCurrentValue = $salesTwinComparisonPeriods[0]['metrics'][$twinRow['key']] ?? null; @endphp
                            <tr>
                                <td class="ps-3 fw-semibold">{{ $twinRow['label'] }}</td>
                                @foreach ($salesTwinComparisonPeriods as $periodIndex => $period)
                                    @php
                                        $twinValue = $period['metrics'][$twinRow['key']] ?? null;
                                        $twinDelta = null;
                                        if ($periodIndex > 0 && is_numeric($twinCurrentValue) && is_numeric($twinValue)) {
                                            $twinDifference = (float) $twinCurrentValue - (float) $twinValue;
                                            $twinBase = abs((float) $twinValue);
                                            $twinDelta = ($twinRow['is_percent'] ?? false)
                                                ? $twinDifference
                                                : ($twinBase > 0 ? ($twinDifference / $twinBase) * 100 : ($twinDifference === 0.0 ? 0 : null));
                                        }
                                        $twinDeltaTone = $twinDelta === null || $twinDelta === 0.0 ? 'is-neutral' : ($twinDelta > 0 ? 'is-up' : 'is-down');
                                    @endphp
                                    <td class="text-end {{ $periodIndex === 0 ? 'sales-period-current' : '' }}">
                                        <span class="sales-comparison-cell">
                                            <span class="sales-comparison-value-row">
                                                {{ $twinValue === null ? '—' : $twinRow['format']($twinValue) }}
                                                @if ($twinDelta !== null)
                                                    <span class="sales-comparison-delta {{ $twinDeltaTone }}" title="Perubahan dibanding {{ $period['label'] }}">
                                                        <i class="bi {{ $twinDelta > 0 ? 'bi-arrow-up-right' : ($twinDelta < 0 ? 'bi-arrow-down-right' : 'bi-arrow-left-right') }}" aria-hidden="true"></i>{{ ($twinDelta > 0 ? '+' : ($twinDelta < 0 ? '−' : '±')).number_format(abs($twinDelta), 1, ',', '.') }}{{ ($twinRow['is_percent'] ?? false) ? ' pt' : '%' }}
                                                    </span>
                                                @endif
                                            </span>
                                        </span>
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @endif
    @if ($comparisonMode === 'month' && collect($salesTwinPrePeakPeriods)->contains(fn ($period) => $period['has_event']))
        <section class="card sales-card sales-comparison-section shadow-sm mb-3" aria-labelledby="sales-twin-pre-peak-title">
            <div class="sales-section-header d-flex flex-wrap align-items-start justify-content-between gap-2">
                <div>
                    <div class="sales-kicker mb-1">Persiapan peak day</div>
                    <h2 id="sales-twin-pre-peak-title" class="sales-section-title mb-1">Performa 7 hari sebelum {{ collect($salesTwinPrePeakPeriods)->pluck('label')->implode(', ') }}</h2>
                    <div class="sales-section-subtitle d-block">Membandingkan periode D-7 sampai D-1 sebelum setiap tanggal kembar. Peak day tidak termasuk.</div>
                </div>
                <span class="badge sales-badge rounded-pill px-3 py-2"><i class="bi bi-calendar-week me-1" aria-hidden="true"></i>Pre-peak</span>
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle sales-table sales-sales-comparison-table mb-0">
                    <thead>
                        <tr>
                            <th class="ps-3" scope="col">Metrik</th>
                            @foreach ($salesTwinPrePeakPeriods as $periodIndex => $period)
                                <th class="text-end {{ $periodIndex === 0 ? 'sales-period-current' : '' }}" scope="col">
                                    <span class="sales-period-label">{{ $period['label'] }}</span>
                                    <span class="sales-period-range sales-twin-period-range">{{ $period['event_date'] }}</span>
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($salesTwinComparisonRows as $twinRow)
                            @php $prePeakCurrentValue = $salesTwinPrePeakPeriods[0]['metrics'][$twinRow['key']] ?? null; @endphp
                            <tr>
                                <td class="ps-3 fw-semibold">{{ $twinRow['label'] }}</td>
                                @foreach ($salesTwinPrePeakPeriods as $periodIndex => $period)
                                    @php
                                        $prePeakValue = $period['metrics'][$twinRow['key']] ?? null;
                                        $prePeakDelta = null;
                                        if ($periodIndex > 0 && is_numeric($prePeakCurrentValue) && is_numeric($prePeakValue)) {
                                            $prePeakDifference = (float) $prePeakCurrentValue - (float) $prePeakValue;
                                            $prePeakBase = abs((float) $prePeakValue);
                                            $prePeakDelta = ($twinRow['is_percent'] ?? false)
                                                ? $prePeakDifference
                                                : ($prePeakBase > 0 ? ($prePeakDifference / $prePeakBase) * 100 : ($prePeakDifference === 0.0 ? 0 : null));
                                        }
                                        $prePeakDeltaTone = $prePeakDelta === null || $prePeakDelta === 0.0 ? 'is-neutral' : ($prePeakDelta > 0 ? 'is-up' : 'is-down');
                                    @endphp
                                    <td class="text-end {{ $periodIndex === 0 ? 'sales-period-current' : '' }}">
                                        <span class="sales-comparison-cell">
                                            <span class="sales-comparison-value-row">
                                                {{ $prePeakValue === null ? '—' : $twinRow['format']($prePeakValue) }}
                                                @if ($prePeakDelta !== null)
                                                    <span class="sales-comparison-delta {{ $prePeakDeltaTone }}" title="Perubahan dibanding {{ $period['label'] }}">
                                                        <i class="bi {{ $prePeakDelta > 0 ? 'bi-arrow-up-right' : ($prePeakDelta < 0 ? 'bi-arrow-down-right' : 'bi-arrow-left-right') }}" aria-hidden="true"></i>{{ ($prePeakDelta > 0 ? '+' : ($prePeakDelta < 0 ? '−' : '±')).number_format(abs($prePeakDelta), 1, ',', '.') }}{{ ($twinRow['is_percent'] ?? false) ? ' pt' : '%' }}
                                                    </span>
                                                @endif
                                            </span>
                                        </span>
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @endif
    @if ($comparisonMode === 'month')
        @include('marketplace.dashboard.partials._event_comparison_table', [
            'eventId' => 'sales-payday-comparison',
            'eventPeriods' => $salesPaydayComparisonPeriods,
            'eventRows' => $salesTwinComparisonRows,
            'eventKicker' => 'Perbandingan payday',
            'eventTitle' => 'Perbandingan tanggal gajian '.$paydayDay,
            'eventSubtitle' => 'Baseline payday pada bulan aktif dibandingkan dengan tanggal gajian pada tiga bulan sebelumnya.',
            'eventIcon' => 'bi-wallet2',
            'eventBadge' => 'Bulanan',
        ])
        @include('marketplace.dashboard.partials._event_comparison_table', [
            'eventId' => 'sales-payday-pre-peak',
            'eventPeriods' => $salesPaydayPrePeakPeriods,
            'eventRows' => $salesTwinComparisonRows,
            'eventKicker' => 'Persiapan payday',
            'eventTitle' => 'Performa 7 hari sebelum payday '.$paydayDay,
            'eventSubtitle' => 'Membandingkan periode D-7 sampai D-1 sebelum tanggal gajian. Payday tidak termasuk.',
            'eventIcon' => 'bi-calendar-week',
            'eventBadge' => 'Pre-payday',
        ])
    @endif
@endif
