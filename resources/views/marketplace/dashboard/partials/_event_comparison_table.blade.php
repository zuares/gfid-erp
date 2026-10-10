@if (collect($eventPeriods)->contains(fn ($period) => $period['has_event']))
    <section class="card sales-card sales-comparison-section shadow-sm mb-3" aria-labelledby="{{ $eventId }}-title">
        <div class="sales-section-header d-flex flex-wrap align-items-start justify-content-between gap-2">
            <div>
                <div class="sales-kicker mb-1">{{ $eventKicker }}</div>
                <h2 id="{{ $eventId }}-title" class="sales-section-title mb-1">{{ $eventTitle }}</h2>
                <div class="sales-section-subtitle d-block">{{ $eventSubtitle }}</div>
            </div>
            <span class="badge sales-badge rounded-pill px-3 py-2"><i class="bi {{ $eventIcon }} me-1" aria-hidden="true"></i>{{ $eventBadge }}</span>
        </div>
        <div class="table-responsive">
            <table class="table table-sm table-hover align-middle sales-table sales-sales-comparison-table mb-0">
                <thead>
                    <tr>
                        <th class="ps-3" scope="col">Metrik</th>
                        @foreach ($eventPeriods as $periodIndex => $period)
                            <th class="text-end {{ $periodIndex === 0 ? 'sales-period-current' : '' }}" scope="col">
                                <span class="sales-period-label">{{ $period['label'] }}</span>
                                <span class="sales-period-range sales-twin-period-range">{{ $period['event_date'] }}</span>
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach ($eventRows as $eventRow)
                        @php $eventCurrentValue = $eventPeriods[0]['metrics'][$eventRow['key']] ?? null; @endphp
                        <tr>
                            <td class="ps-3 fw-semibold">{{ $eventRow['label'] }}</td>
                            @foreach ($eventPeriods as $periodIndex => $period)
                                @php
                                    $eventValue = $period['metrics'][$eventRow['key']] ?? null;
                                    $eventDelta = null;
                                    if ($periodIndex > 0 && is_numeric($eventCurrentValue) && is_numeric($eventValue)) {
                                        $eventDifference = (float) $eventCurrentValue - (float) $eventValue;
                                        $eventBase = abs((float) $eventValue);
                                        $eventDelta = ($eventRow['is_percent'] ?? false)
                                            ? $eventDifference
                                            : ($eventBase > 0 ? ($eventDifference / $eventBase) * 100 : ($eventDifference === 0.0 ? 0 : null));
                                    }
                                    $eventDeltaTone = $eventDelta === null || $eventDelta === 0.0 ? 'is-neutral' : ($eventDelta > 0 ? 'is-up' : 'is-down');
                                @endphp
                                <td class="text-end {{ $periodIndex === 0 ? 'sales-period-current' : '' }}">
                                    <span class="sales-comparison-cell">
                                        <span class="sales-comparison-value-row">
                                            {{ $eventValue === null ? '—' : $eventRow['format']($eventValue) }}
                                            @if ($eventDelta !== null)
                                                <span class="sales-comparison-delta {{ $eventDeltaTone }}" title="Perubahan dibanding {{ $period['label'] }}">
                                                    <i class="bi {{ $eventDelta > 0 ? 'bi-arrow-up-right' : ($eventDelta < 0 ? 'bi-arrow-down-right' : 'bi-arrow-left-right') }}" aria-hidden="true"></i>{{ ($eventDelta > 0 ? '+' : ($eventDelta < 0 ? '−' : '±')).number_format(abs($eventDelta), 1, ',', '.') }}{{ ($eventRow['is_percent'] ?? false) ? ' pt' : '%' }}
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
