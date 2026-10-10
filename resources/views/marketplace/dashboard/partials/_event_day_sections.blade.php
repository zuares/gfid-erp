@if ($activeComparison)
    @include('marketplace.dashboard.partials._event_comparison_table', [
        'eventId' => 'sales-twin-comparison',
        'eventPeriods' => $salesTwinComparisonPeriods,
        'eventRows' => $salesTwinComparisonRows,
        'eventKicker' => 'Tanggal kembar',
        'eventTitle' => 'Perbandingan '.collect($salesTwinComparisonPeriods)->pluck('label')->implode(', '),
        'eventSubtitle' => 'Baseline adalah tanggal kembar pada bulan aktif, dibandingkan dengan tiga bulan sebelumnya.',
        'eventIcon' => 'bi-calendar2-event',
        'eventBadge' => 'Tanggal kembar',
    ])
    @if ($comparisonMode === 'month')
        @include('marketplace.dashboard.partials._event_comparison_table', [
            'eventId' => 'sales-twin-pre-peak',
            'eventPeriods' => $salesTwinPrePeakPeriods,
            'eventRows' => $salesTwinComparisonRows,
            'eventKicker' => 'Persiapan peak day',
            'eventTitle' => '7 hari sebelum '.collect($salesTwinPrePeakPeriods)->pluck('label')->implode(', '),
            'eventSubtitle' => 'Membandingkan D-7 sampai D-1 sebelum setiap tanggal kembar. Peak day tidak termasuk.',
            'eventIcon' => 'bi-calendar-week',
            'eventBadge' => 'Pre-peak',
        ])
        @include('marketplace.dashboard.partials._event_comparison_table', [
            'eventId' => 'sales-payday-comparison',
            'eventPeriods' => $salesPaydayComparisonPeriods,
            'eventRows' => $salesTwinComparisonRows,
            'eventKicker' => 'Tanggal gajian',
            'eventTitle' => 'Perbandingan payday tanggal '.$paydayDay,
            'eventSubtitle' => 'Baseline payday pada bulan aktif dibandingkan dengan tanggal gajian pada tiga bulan sebelumnya.',
            'eventIcon' => 'bi-wallet2',
            'eventBadge' => 'Payday',
        ])
        @include('marketplace.dashboard.partials._event_comparison_table', [
            'eventId' => 'sales-payday-pre-peak',
            'eventPeriods' => $salesPaydayPrePeakPeriods,
            'eventRows' => $salesTwinComparisonRows,
            'eventKicker' => 'Persiapan payday',
            'eventTitle' => '7 hari sebelum payday '.$paydayDay,
            'eventSubtitle' => 'Membandingkan D-7 sampai D-1 sebelum tanggal gajian. Payday tidak termasuk.',
            'eventIcon' => 'bi-calendar-week',
            'eventBadge' => 'Pre-payday',
        ])
    @endif
@endif
