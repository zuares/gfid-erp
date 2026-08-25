@extends('layouts.app')

@section('title', 'Marketplace • Financial Data Quality')

@php
    $fmt = fn ($n) => number_format((int) $n, 0, ',', '.');
    $statusLabels = [
        'ready' => 'Ready',
        'incomplete' => 'Incomplete',
        'not_applicable' => 'Tidak berlaku',
        'unknown' => 'Unknown',
    ];
    $statusClasses = [
        'ready' => 'fq-status-ready',
        'incomplete' => 'fq-status-incomplete',
        'not_applicable' => 'fq-status-neutral',
        'unknown' => 'fq-status-unknown',
    ];
    $result = session('quality_result');
    $qualityIssues = (int) ($orderCounts['incomplete'] ?? 0) + (int) ($orderCounts['unknown'] ?? 0);
    $settlementIssues = (int) ($settlementCounts['incomplete'] ?? 0) + (int) ($settlementCounts['unknown'] ?? 0);
    $hasIssues = $qualityIssues > 0 || $settlementIssues > 0;
    $auditedOrders = collect($orderCounts)->sum(fn ($value) => (int) $value);
    $activeFilterCount = collect([
        filled($search),
        filled($storeId),
        filled($orderStatus),
        filled($settlementStatus),
        filled($dateFrom),
        filled($dateTo),
        $status !== '' && ! $defaultIssueQueue,
    ])->filter()->count();
    $hasFilters = $activeFilterCount > 0;
    $dateRangeValue = '';
    if ($dateFrom && $dateTo) {
        $dateRangeValue = date('d M Y', strtotime($dateFrom)) . ' – ' . date('d M Y', strtotime($dateTo));
    } elseif ($dateFrom) {
        $dateRangeValue = date('d M Y', strtotime($dateFrom)) . ' – …';
    } elseif ($dateTo) {
        $dateRangeValue = '… – ' . date('d M Y', strtotime($dateTo));
    }
@endphp

@push('head')
<style>
    :root {
        --fq-ink: #172033;
        --fq-muted: #667085;
        --fq-subtle: #98a2b3;
        --fq-border: #e6eaf0;
        --fq-surface: #ffffff;
        --fq-surface-soft: #f8fafc;
        --fq-brand: #2457a6;
        --fq-brand-dark: #183d78;
        --fq-shadow: 0 1px 2px rgba(16, 24, 40, .04), 0 6px 18px rgba(16, 24, 40, .04);
    }
    .fq-page { max-width: 1440px; margin: 0 auto; padding: 1.15rem 1.15rem 4rem; color: var(--fq-ink); }
    .fq-page *, .fq-page *::before, .fq-page *::after { box-sizing: border-box; }
    .fq-breadcrumb { display: flex; align-items: center; gap: .45rem; margin-bottom: .8rem; color: var(--fq-subtle); font-size: .7rem; font-weight: 650; letter-spacing: .01em; }
    .fq-breadcrumb a { color: var(--fq-muted); text-decoration: none; }
    .fq-breadcrumb a:hover { color: var(--fq-brand); }
    .fq-breadcrumb i { font-size: .63rem; }
    .fq-header { display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem; margin-bottom: 1rem; }
    .fq-eyebrow { margin-bottom: .3rem; color: var(--fq-brand); font-size: .68rem; font-weight: 800; letter-spacing: .1em; text-transform: uppercase; }
    .fq-title { margin: 0; color: var(--fq-ink); font-size: clamp(1.25rem, 2vw, 1.65rem); font-weight: 760; letter-spacing: -.025em; }
    .fq-description { max-width: 680px; margin: .35rem 0 0; color: var(--fq-muted); font-size: .82rem; line-height: 1.55; }
    .fq-actions { display: flex; flex-wrap: wrap; justify-content: flex-end; gap: .45rem; }
    .fq-btn { border-radius: 7px; padding: .48rem .7rem; font-size: .75rem; font-weight: 700; box-shadow: none !important; }
    .fq-btn-primary { color: #fff !important; background: var(--fq-brand) !important; border-color: var(--fq-brand) !important; }
    .fq-btn-primary:hover { background: var(--fq-brand-dark) !important; border-color: var(--fq-brand-dark) !important; }
    .fq-card { background: var(--fq-surface); border: 1px solid var(--fq-border); border-radius: 10px; box-shadow: var(--fq-shadow); }
    .fq-health { display: flex; align-items: center; justify-content: space-between; gap: 1rem; margin-bottom: .85rem; padding: .85rem .95rem; border: 1px solid; border-radius: 10px; }
    .fq-health-attention { color: #7a3e00; background: #fffbeb; border-color: #f5d98a; }
    .fq-health-good { color: #176044; background: #f0fdf7; border-color: #b7ead4; }
    .fq-health-main { display: flex; align-items: flex-start; gap: .65rem; }
    .fq-health-icon { display: grid; width: 28px; height: 28px; flex: 0 0 28px; place-items: center; border-radius: 8px; background: rgba(255,255,255,.72); font-size: .9rem; }
    .fq-health-title { margin: 0 0 .12rem; font-size: .78rem; font-weight: 800; }
    .fq-health-copy { margin: 0; color: inherit; opacity: .84; font-size: .72rem; line-height: 1.45; }
    .fq-health-copy strong { font-weight: 800; }
    .fq-health-time { color: inherit; opacity: .75; font-size: .68rem; white-space: nowrap; }
    .fq-kpis { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: .65rem; margin-bottom: .85rem; }
    .fq-kpi { position: relative; min-height: 98px; padding: .82rem .9rem; overflow: hidden; }
    .fq-kpi::after { position: absolute; right: -16px; bottom: -26px; width: 72px; height: 72px; border: 10px solid rgba(36,87,166,.06); border-radius: 50%; content: ''; }
    .fq-kpi-label { display: flex; align-items: center; gap: .35rem; color: var(--fq-muted); font-size: .7rem; font-weight: 700; }
    .fq-kpi-icon { display: grid; width: 22px; height: 22px; place-items: center; border-radius: 6px; color: var(--fq-brand); background: #eef4ff; font-size: .72rem; }
    .fq-kpi-value { display: block; margin-top: .5rem; color: var(--fq-ink); font-size: 1.35rem; font-weight: 780; letter-spacing: -.035em; line-height: 1; }
    .fq-kpi-note { display: block; margin-top: .35rem; color: var(--fq-subtle); font-size: .65rem; }
    .fq-kpi-warning .fq-kpi-icon { color: #a15c00; background: #fff4d6; }
    .fq-kpi-danger .fq-kpi-icon { color: #b42318; background: #feeceb; }
    .fq-filter-card { margin-bottom: .85rem; }
    .fq-filter-head { display: flex; align-items: center; justify-content: space-between; gap: .75rem; padding: .78rem .9rem; border-bottom: 1px solid var(--fq-border); }
    .fq-filter-title-wrap { display: flex; align-items: center; gap: .55rem; }
    .fq-filter-icon { display: grid; width: 26px; height: 26px; place-items: center; border-radius: 7px; color: var(--fq-brand); background: #eef4ff; font-size: .78rem; }
    .fq-filter-title { margin: 0; font-size: .8rem; font-weight: 760; }
    .fq-filter-helper { margin: .14rem 0 0; color: var(--fq-muted); font-size: .68rem; }
    .fq-filter-count { padding: .24rem .45rem; border: 1px solid #d8e4fa; border-radius: 999px; color: var(--fq-brand); background: #f5f8ff; font-size: .66rem; font-weight: 750; white-space: nowrap; }
    .fq-filter-body { padding: .85rem .9rem .9rem; }
    .fq-filter-form { display: grid; grid-template-columns: minmax(220px, 1.5fr) repeat(4, minmax(130px, 1fr)) minmax(135px, .8fr) minmax(135px, .8fr) auto; gap: .65rem; align-items: end; }
    .fq-field { min-width: 0; }
    .fq-field-label { display: block; margin-bottom: .3rem; color: var(--fq-muted); font-size: .66rem; font-weight: 750; }
    .fq-field .form-control, .fq-field .form-select { height: 35px; min-width: 0; border-color: #d9dee8; border-radius: 7px; color: var(--fq-ink); font-size: .73rem; box-shadow: none; }
    .fq-field .form-control:focus, .fq-field .form-select:focus { border-color: #91afe0; box-shadow: 0 0 0 3px rgba(36,87,166,.1); }
    .fq-field-range { grid-column: span 2; }
    .fq-range-control { position: relative; }
    .fq-range-control .fq-range-icon { position: absolute; top: 50%; right: .7rem; z-index: 1; color: var(--fq-brand); pointer-events: none; transform: translateY(-50%); }
    .fq-range-control .form-control { padding-right: 2.15rem; cursor: pointer; }
    .fq-filter-actions { display: flex; align-items: center; gap: .4rem; height: 35px; }
    .fq-refresh { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: .8rem; padding: .7rem .9rem; border-top: 1px solid var(--fq-border); background: var(--fq-surface-soft); }
    .fq-refresh-copy { display: flex; align-items: flex-start; gap: .55rem; }
    .fq-refresh-copy i { margin-top: .1rem; color: var(--fq-brand); }
    .fq-refresh-title { margin: 0 0 .1rem; color: var(--fq-ink); font-size: .73rem; font-weight: 760; }
    .fq-refresh-note { margin: 0; color: var(--fq-muted); font-size: .67rem; }
    .fq-refresh-actions { display: flex; gap: .4rem; }
    .fq-main-grid { display: grid; grid-template-columns: minmax(0, 1fr) 300px; gap: .85rem; align-items: start; }
    .fq-queue-card { min-width: 0; }
    .fq-queue-head { display: flex; align-items: center; justify-content: space-between; gap: .7rem; padding: .8rem .9rem; border-bottom: 1px solid var(--fq-border); }
    .fq-queue-heading { display: flex; align-items: center; gap: .55rem; }
    .fq-queue-title { margin: 0; font-size: .84rem; font-weight: 780; }
    .fq-queue-subtitle { margin: .15rem 0 0; color: var(--fq-muted); font-size: .68rem; }
    .fq-queue-total { color: var(--fq-muted); font-size: .7rem; white-space: nowrap; }
    .fq-table-wrap { max-height: 660px; overflow: auto; }
    .fq-table { min-width: 910px; margin: 0; font-size: .73rem; }
    .fq-table thead th { position: sticky; top: 0; z-index: 2; padding: .58rem .65rem; border-bottom: 1px solid var(--fq-border); color: var(--fq-muted); background: #fbfcfe; font-size: .64rem; font-weight: 800; letter-spacing: .025em; text-transform: uppercase; white-space: nowrap; }
    .fq-table tbody td { padding: .68rem .65rem; border-top: 1px solid #f0f2f5; vertical-align: middle; }
    .fq-table tbody tr:hover { background: #fbfdff; }
    .fq-order { color: var(--fq-ink); font-weight: 780; }
    .fq-store { color: var(--fq-ink); font-weight: 650; }
    .fq-muted { color: var(--fq-muted); font-size: .66rem; }
    .fq-status { display: inline-flex; align-items: center; gap: .3rem; padding: .22rem .42rem; border: 1px solid transparent; border-radius: 999px; font-size: .64rem; font-weight: 760; line-height: 1; white-space: nowrap; }
    .fq-status::before { width: 5px; height: 5px; border-radius: 50%; background: currentColor; content: ''; }
    .fq-status-ready { color: #16704c; background: #ecfdf3; border-color: #b9e8cf; }
    .fq-status-incomplete { color: #986000; background: #fff8e5; border-color: #f4d890; }
    .fq-status-neutral { color: #667085; background: #f5f6f8; border-color: #e1e5eb; }
    .fq-status-unknown { color: #475467; background: #f2f4f7; border-color: #d0d5dd; }
    .fq-status-missing { color: #b42318; background: #fef3f2; border-color: #f5c4c0; }
    .fq-valid { color: #16704c; font-weight: 750; }
    .fq-invalid { color: #a15c00; font-weight: 750; }
    .fq-action { color: var(--fq-brand); font-weight: 750; text-decoration: none; white-space: nowrap; }
    .fq-action:hover { color: var(--fq-brand-dark); }
    .fq-empty { padding: 3rem 1rem !important; text-align: center; }
    .fq-empty-icon { display: grid; width: 40px; height: 40px; margin: 0 auto .7rem; place-items: center; border-radius: 12px; color: #16704c; background: #ecfdf3; font-size: 1rem; }
    .fq-empty-title { margin: 0 0 .25rem; color: var(--fq-ink); font-size: .8rem; font-weight: 780; }
    .fq-empty-copy { max-width: 420px; margin: 0 auto; color: var(--fq-muted); font-size: .7rem; line-height: 1.5; }
    .fq-pagination { padding: .7rem .85rem; border-top: 1px solid var(--fq-border); }
    .fq-sidebar { display: grid; gap: .85rem; }
    .fq-card-head { display: flex; align-items: center; justify-content: space-between; gap: .75rem; padding: .78rem .9rem; border-bottom: 1px solid var(--fq-border); }
    .fq-card-title { margin: 0; color: var(--fq-ink); font-size: .8rem; font-weight: 760; }
    .fq-card-meta { color: var(--fq-muted); font-size: .7rem; }
    .fq-summary-list { display: grid; gap: .65rem; padding: .85rem .9rem; }
    .fq-summary-row { display: flex; align-items: center; justify-content: space-between; gap: .65rem; }
    .fq-summary-label { display: flex; align-items: center; gap: .4rem; color: var(--fq-muted); font-size: .7rem; }
    .fq-summary-dot { width: 7px; height: 7px; border-radius: 50%; background: #98a2b3; }
    .fq-summary-dot.ready { background: #2eaf78; }
    .fq-summary-dot.warning { background: #d98b00; }
    .fq-summary-dot.danger { background: #d92d20; }
    .fq-summary-value { color: var(--fq-ink); font-size: .76rem; font-weight: 780; }
    .fq-progress { height: 6px; margin: .1rem .9rem .85rem; overflow: hidden; border-radius: 99px; background: #edf0f4; }
    .fq-progress-bar { height: 100%; border-radius: inherit; background: #2eaf78; }
    .fq-progress-note { padding: 0 .9rem .85rem; color: var(--fq-muted); font-size: .65rem; }
    .fq-issues { padding: .65rem .9rem .85rem; }
    .fq-issue { display: flex; align-items: center; justify-content: space-between; gap: .65rem; padding: .55rem 0; border-bottom: 1px solid #f0f2f5; }
    .fq-issue:last-child { border-bottom: 0; }
    .fq-issue-name { color: var(--fq-muted); font-size: .69rem; }
    .fq-issue-count { min-width: 25px; padding: .2rem .35rem; border-radius: 999px; color: #986000; background: #fff8e5; font-size: .64rem; font-weight: 780; text-align: center; }
    .fq-side-note { padding: .8rem .9rem; color: var(--fq-muted); background: var(--fq-surface-soft); font-size: .68rem; line-height: 1.55; }
    .fq-side-note strong { color: var(--fq-ink); }
    @media (max-width: 1180px) {
        .fq-filter-form { grid-template-columns: repeat(4, minmax(0, 1fr)); }
        .fq-field-search { grid-column: span 2; }
        .fq-field-range { grid-column: span 2; }
        .fq-filter-actions { grid-column: span 2; }
    }
    @media (max-width: 900px) {
        .fq-header { display: block; }
        .fq-actions { justify-content: flex-start; margin-top: .8rem; }
        .fq-kpis { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .fq-main-grid { grid-template-columns: 1fr; }
        .fq-sidebar { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .fq-sidebar .fq-side-note { grid-column: 1 / -1; }
    }
    @media (max-width: 640px) {
        .fq-page { padding: .8rem .55rem 3rem; }
        .fq-health { align-items: flex-start; flex-direction: column; }
        .fq-health-time { margin-left: 2.35rem; }
        .fq-kpis { gap: .45rem; }
        .fq-kpi { min-height: 88px; padding: .68rem; }
        .fq-kpi-value { font-size: 1.15rem; }
        .fq-filter-form { grid-template-columns: 1fr 1fr; gap: .55rem; }
        .fq-field-search, .fq-field-range, .fq-field-wide, .fq-filter-actions { grid-column: 1 / -1; }
        .fq-refresh { align-items: flex-start; flex-direction: column; }
        .fq-sidebar { grid-template-columns: 1fr; }
        .fq-sidebar .fq-side-note { grid-column: auto; }
    }
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const rangeInput = document.getElementById('fq-date-range');
    const fromInput = document.getElementById('fq-date-from');
    const toInput = document.getElementById('fq-date-to');

    if (!rangeInput || !fromInput || !toInput || typeof window.flatpickr !== 'function') return;

    const formatRange = function (dates) {
        if (dates.length === 2) {
            return window.flatpickr.formatDate(dates[0], 'd M Y') + ' – ' + window.flatpickr.formatDate(dates[1], 'd M Y');
        }
        return dates.length === 1 ? window.flatpickr.formatDate(dates[0], 'd M Y') + ' – …' : '';
    };

    window.flatpickr(rangeInput, {
        mode: 'range',
        dateFormat: 'Y-m-d',
        defaultDate: [fromInput.value, toInput.value].filter(Boolean),
        allowInput: false,
        disableMobile: true,
        locale: window.flatpickr.l10ns && window.flatpickr.l10ns.id ? window.flatpickr.l10ns.id : 'default',
        onReady: function (selectedDates, _, instance) {
            if (selectedDates.length) instance.input.value = formatRange(selectedDates);
        },
        onChange: function (selectedDates, _, instance) {
            if (!selectedDates.length) {
                fromInput.value = '';
                toInput.value = '';
                instance.input.value = '';
                return;
            }

            fromInput.value = window.flatpickr.formatDate(selectedDates[0], 'Y-m-d');
            toInput.value = selectedDates.length === 2
                ? window.flatpickr.formatDate(selectedDates[1], 'Y-m-d')
                : '';
            instance.input.value = formatRange(selectedDates);
        },
    });
});
</script>
@endpush

@section('content')
<div class="fq-page">
    <nav class="fq-breadcrumb" aria-label="Breadcrumb">
        <a href="{{ route('marketplace.orders') }}">Marketplace</a>
        <i class="bi bi-chevron-right"></i>
        <span>Financial data quality</span>
    </nav>

    <header class="fq-header">
        <div>
            <div class="fq-eyebrow">Financial controls</div>
            <h1 class="fq-title">Audit Kualitas Data Keuangan</h1>
            <p class="fq-description">Pantau kelengkapan settlement, HPP, dan data order marketplace sebelum masuk laporan keuangan atau diposting ke GL.</p>
        </div>
        <div class="fq-actions">
            <a href="{{ route('marketplace.reports.financial-statement') }}" class="btn btn-sm btn-outline-secondary fq-btn"><i class="bi bi-file-earmark-spreadsheet me-1"></i>Financial statement</a>
            <a href="{{ route('marketplace.reports.financial-closing') }}" class="btn btn-sm btn-outline-warning fq-btn"><i class="bi bi-lock-fill me-1"></i>Closing</a>
        </div>
    </header>

    @if ($result)
        <div class="fq-health {{ $result['dry_run'] ? 'fq-health-attention' : 'fq-health-good' }}" role="status">
            <div class="fq-health-main">
                <div class="fq-health-icon"><i class="bi {{ $result['dry_run'] ? 'bi-search' : 'bi-check-lg' }}"></i></div>
                <div>
                    <p class="fq-health-title">{{ $result['dry_run'] ? 'Audit dry-run selesai' : 'Audit status berhasil disimpan' }}</p>
                    <p class="fq-health-copy">{{ $result['message'] }}</p>
                </div>
            </div>
            <span class="fq-health-time">{{ now()->format('d M Y H:i') }}</span>
        </div>
    @elseif ($hasIssues)
        <div class="fq-health fq-health-attention" role="status">
            <div class="fq-health-main">
                <div class="fq-health-icon"><i class="bi bi-exclamation-triangle"></i></div>
                <div>
                    <p class="fq-health-title">Perlu ditinjau sebelum posting</p>
                    <p class="fq-health-copy">Ditemukan <strong>{{ $fmt($qualityIssues) }} quality issue</strong> dan <strong>{{ $fmt($settlementIssues) }} settlement issue</strong>. Gunakan queue di bawah untuk menelusuri detailnya.</p>
                </div>
            </div>
            @if ($lastCheckedAt)<span class="fq-health-time">Audit {{ date('d M Y H:i', strtotime($lastCheckedAt)) }}</span>@endif
        </div>
    @else
        <div class="fq-health fq-health-good" role="status">
            <div class="fq-health-main">
                <div class="fq-health-icon"><i class="bi bi-check2-circle"></i></div>
                <div>
                    <p class="fq-health-title">Tidak ada issue yang terdeteksi</p>
                    <p class="fq-health-copy">Data siap digunakan untuk proses laporan dan review accounting berikutnya.</p>
                </div>
            </div>
            @if ($lastCheckedAt)<span class="fq-health-time">Audit {{ date('d M Y H:i', strtotime($lastCheckedAt)) }}</span>@endif
        </div>
    @endif

    @if ($errors->has('store_id'))
        <div class="alert alert-danger py-2 small">{{ $errors->first('store_id') }}</div>
    @endif

    <section class="fq-kpis" aria-label="Quality overview">
        <div class="fq-card fq-kpi">
            <span class="fq-kpi-label"><span class="fq-kpi-icon"><i class="bi bi-inbox"></i></span>Queue perbaikan</span>
            <strong class="fq-kpi-value">{{ $fmt($orders->total()) }}</strong>
            <span class="fq-kpi-note">{{ $orders->total() ? 'Item yang perlu ditinjau' : 'Tidak ada queue aktif' }}</span>
        </div>
        <div class="fq-card fq-kpi">
            <span class="fq-kpi-label"><span class="fq-kpi-icon"><i class="bi bi-check2-circle"></i></span>Order ready</span>
            <strong class="fq-kpi-value">{{ $fmt($orderCounts['ready'] ?? 0) }}</strong>
            <span class="fq-kpi-note">Dari {{ $fmt($auditedOrders) }} order diaudit</span>
        </div>
        <div class="fq-card fq-kpi fq-kpi-warning">
            <span class="fq-kpi-label"><span class="fq-kpi-icon"><i class="bi bi-file-earmark-excel"></i></span>Quality incomplete</span>
            <strong class="fq-kpi-value">{{ $fmt($qualityIssues) }}</strong>
            <span class="fq-kpi-note">Order belum siap untuk laporan</span>
        </div>
        <div class="fq-card fq-kpi fq-kpi-danger">
            <span class="fq-kpi-label"><span class="fq-kpi-icon"><i class="bi bi-wallet2"></i></span>Settlement issue</span>
            <strong class="fq-kpi-value">{{ $fmt($settlementIssues) }}</strong>
            <span class="fq-kpi-note">Incomplete atau belum dikenali</span>
        </div>
    </section>

    <section class="fq-card fq-filter-card" aria-labelledby="filter-title">
        <div class="fq-filter-head">
            <div class="fq-filter-title-wrap">
                <span class="fq-filter-icon"><i class="bi bi-sliders2"></i></span>
                <div>
                    <h2 id="filter-title" class="fq-filter-title">Filter operasional</h2>
                    <p class="fq-filter-helper">Persempit queue berdasarkan toko, status order, kualitas, atau periode.</p>
                </div>
            </div>
            @if ($activeFilterCount)<span class="fq-filter-count">{{ $activeFilterCount }} filter aktif</span>@endif
        </div>
        <div class="fq-filter-body">
            <form method="GET" class="fq-filter-form">
                <div class="fq-field fq-field-search">
                    <label class="fq-field-label" for="fq-search">Cari order</label>
                    <input id="fq-search" class="form-control" type="search" name="q" value="{{ $search }}" placeholder="Order, booking, atau buyer" autocomplete="off">
                </div>
                <div class="fq-field">
                    <label class="fq-field-label" for="fq-store">Toko</label>
                    <select id="fq-store" name="store_id" class="form-select"><option value="">Semua toko</option>@foreach ($stores as $store)<option value="{{ $store->id }}" @selected((int) $storeId === (int) $store->id)>{{ $store->name }}</option>@endforeach</select>
                </div>
                <div class="fq-field">
                    <label class="fq-field-label" for="fq-order-status">Status order</label>
                    <select id="fq-order-status" name="order_status" class="form-select"><option value="">Semua status</option>@foreach ($orderStatuses as $value)<option value="{{ $value }}" @selected($orderStatus === $value)>{{ $value }}</option>@endforeach</select>
                </div>
                <div class="fq-field">
                    <label class="fq-field-label" for="fq-quality">Quality</label>
                    <select id="fq-quality" name="status" class="form-select"><option value="">Semua kualitas</option>@foreach ($statusLabels as $value => $label)<option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>@endforeach</select>
                </div>
                <div class="fq-field">
                    <label class="fq-field-label" for="fq-settlement">Settlement</label>
                    <select id="fq-settlement" name="settlement_status" class="form-select"><option value="">Semua settlement</option><option value="complete" @selected($settlementStatus === 'complete')>Complete</option><option value="incomplete" @selected($settlementStatus === 'incomplete')>Incomplete</option><option value="missing" @selected($settlementStatus === 'missing')>Missing</option><option value="unknown" @selected($settlementStatus === 'unknown')>Unknown</option></select>
                </div>
                <div class="fq-field fq-field-range">
                    <label class="fq-field-label" for="fq-date-range">Periode order</label>
                    <div class="fq-range-control">
                        <input id="fq-date-range" class="form-control" type="text" value="{{ $dateRangeValue }}" placeholder="Pilih tanggal mulai – selesai" data-gf-date="off" autocomplete="off" aria-label="Pilih periode order">
                        <i class="bi bi-calendar3 fq-range-icon"></i>
                    </div>
                    <input id="fq-date-from" type="hidden" name="date_from" value="{{ $dateFrom }}" data-gf-date="off">
                    <input id="fq-date-to" type="hidden" name="date_to" value="{{ $dateTo }}" data-gf-date="off">
                </div>
                <div class="fq-filter-actions">
                    <button class="btn btn-sm fq-btn fq-btn-primary" type="submit"><i class="bi bi-search me-1"></i>Terapkan</button>
                    @if ($hasFilters)<a class="btn btn-sm btn-outline-secondary fq-btn" href="{{ route('marketplace.reports.financial-quality') }}">Reset</a>@endif
                </div>
            </form>
        </div>
        <div class="fq-refresh">
            <div class="fq-refresh-copy">
                <i class="bi bi-arrow-clockwise"></i>
                <div>
                    <p class="fq-refresh-title">Perbarui quality status</p>
                    <p class="fq-refresh-note">{{ $storeId ? 'Toko terpilih' : 'Semua toko' }} · Order non-COMPLETED masuk kategori Tidak berlaku.</p>
                </div>
            </div>
            <div class="fq-refresh-actions">
                <form method="POST" action="{{ route('marketplace.reports.financial-quality.refresh') }}" class="d-flex gap-2">
                    @csrf
                    <input type="hidden" name="store_id" value="{{ $storeId ?: 'all' }}">
                    <button name="dry_run" value="1" class="btn btn-sm btn-outline-secondary fq-btn"><i class="bi bi-search me-1"></i>Audit dry-run</button>
                    <button name="dry_run" value="0" class="btn btn-sm fq-btn fq-btn-primary"><i class="bi bi-check2 me-1"></i>Audit & simpan status</button>
                </form>
            </div>
        </div>
    </section>

    <section class="fq-main-grid">
        <div class="fq-card fq-queue-card">
            <div class="fq-queue-head">
                <div>
                    <div class="fq-queue-heading"><i class="bi bi-list-check text-primary"></i><h2 class="fq-queue-title">Queue perbaikan</h2></div>
                    <p class="fq-queue-subtitle">Order dengan quality atau settlement yang perlu ditinjau.</p>
                </div>
                <span class="fq-queue-total">{{ $fmt($orders->total()) }} hasil · halaman {{ $orders->currentPage() }}</span>
            </div>
            <div class="fq-table-wrap">
                <table class="table fq-table">
                    <thead><tr><th>Order</th><th>Toko</th><th>Order status</th><th>Quality</th><th>Settlement</th><th>Item / HPP</th><th>Issue</th><th></th></tr></thead>
                    <tbody>
                    @forelse ($orders as $order)
                        @php
                            $qualityStatus = $order->financial_data_status ?: 'unknown';
                            $flags = $order->settlement?->data_quality_flags ?? [];
                            $missing = $flags['blocking_missing_fields'] ?? $flags['missing_financial_fields'] ?? [];
                            $validItems = $order->items->filter(fn ($item) => ($item->data_status ?? null) === 'valid' && (float) ($item->hpp_snapshot ?? 0) > 0)->count();
                            $settlementStatusRow = $order->settlement?->data_status ?: ($order->settlement ? 'unknown' : 'missing');
                        @endphp
                        <tr>
                            <td><div class="fq-order">{{ $order->channel_order_id ?: $order->external_order_id }}</div><div class="fq-muted">{{ optional($order->ordered_at)->format('d M Y H:i') ?: '-' }}</div></td>
                            <td><div class="fq-store">{{ $order->store?->name ?: '-' }}</div><div class="fq-muted">{{ strtoupper($order->store?->channel?->code ?? '-') }}</div></td>
                            <td><span class="fq-status {{ in_array(strtoupper((string) $order->order_status), ['COMPLETED'], true) ? 'fq-status-ready' : 'fq-status-neutral' }}">{{ $order->order_status ?: '-' }}</span></td>
                            <td><span class="fq-status {{ $statusClasses[$qualityStatus] ?? 'fq-status-unknown' }}">{{ $statusLabels[$qualityStatus] ?? 'Unknown' }}</span></td>
                            <td><span class="fq-status {{ $settlementStatusRow === 'complete' ? 'fq-status-ready' : ($settlementStatusRow === 'missing' ? 'fq-status-missing' : 'fq-status-incomplete') }}">{{ $settlementStatusRow }}</span></td>
                            <td><span class="{{ $validItems === $order->items->count() && $order->items->count() > 0 ? 'fq-valid' : 'fq-invalid' }}">{{ $validItems }}/{{ $order->items->count() }} valid</span></td>
                            <td class="fq-muted">@if ($order->financial_issue_reason)<span class="d-block">{{ str_replace('_', ' ', $order->financial_issue_reason) }}</span>@endif @if (count($missing))<span>Missing: {{ implode(', ', array_slice($missing, 0, 2)) }}{{ count($missing) > 2 ? '…' : '' }}</span>@elseif (!$order->financial_issue_reason)<span>-</span>@endif</td>
                            <td class="text-end"><a href="{{ route('marketplace.orders.show', $order) }}" class="fq-action">Detail <i class="bi bi-arrow-up-right ms-1"></i></a></td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="fq-empty"><div class="fq-empty-icon"><i class="bi bi-check2"></i></div><p class="fq-empty-title">Queue sudah bersih</p><p class="fq-empty-copy">Tidak ada order yang sesuai dengan filter saat ini. Jika Anda mengharapkan data settlement, jalankan audit status atau ubah filter settlement.</p></td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            @if ($orders->hasPages())<div class="fq-pagination">{{ $orders->links() }}</div>@endif
        </div>

        <aside class="fq-sidebar">
            <div class="fq-card">
                <div class="fq-card-head"><h2 class="fq-card-title">Ringkasan quality</h2><span class="fq-card-meta">{{ $fmt($auditedOrders) }} order</span></div>
                <div class="fq-summary-list">
                    <div class="fq-summary-row"><span class="fq-summary-label"><i class="fq-summary-dot ready"></i>Ready</span><strong class="fq-summary-value">{{ $fmt($orderCounts['ready'] ?? 0) }}</strong></div>
                    <div class="fq-summary-row"><span class="fq-summary-label"><i class="fq-summary-dot warning"></i>Incomplete</span><strong class="fq-summary-value">{{ $fmt($orderCounts['incomplete'] ?? 0) }}</strong></div>
                    <div class="fq-summary-row"><span class="fq-summary-label"><i class="fq-summary-dot"></i>Tidak berlaku</span><strong class="fq-summary-value">{{ $fmt($orderCounts['not_applicable'] ?? 0) }}</strong></div>
                    <div class="fq-summary-row"><span class="fq-summary-label"><i class="fq-summary-dot"></i>Unknown</span><strong class="fq-summary-value">{{ $fmt($orderCounts['unknown'] ?? 0) }}</strong></div>
                </div>
                <div class="fq-progress"><div class="fq-progress-bar" style="width: {{ $auditedOrders > 0 ? min(100, (($orderCounts['ready'] ?? 0) / $auditedOrders) * 100) : 0 }}%"></div></div>
                <div class="fq-progress-note">{{ $auditedOrders > 0 ? $fmt(round((($orderCounts['ready'] ?? 0) / $auditedOrders) * 100)) . '% order siap' : 'Belum ada order diaudit' }}</div>
            </div>

            <div class="fq-card">
                <div class="fq-card-head"><h2 class="fq-card-title">Ringkasan settlement</h2><span class="fq-card-meta">Data payout</span></div>
                <div class="fq-summary-list">
                    <div class="fq-summary-row"><span class="fq-summary-label"><i class="fq-summary-dot ready"></i>Complete</span><strong class="fq-summary-value">{{ $fmt($settlementCounts['complete'] ?? 0) }}</strong></div>
                    <div class="fq-summary-row"><span class="fq-summary-label"><i class="fq-summary-dot danger"></i>Incomplete</span><strong class="fq-summary-value">{{ $fmt($settlementCounts['incomplete'] ?? 0) }}</strong></div>
                    <div class="fq-summary-row"><span class="fq-summary-label"><i class="fq-summary-dot"></i>Unknown</span><strong class="fq-summary-value">{{ $fmt($settlementCounts['unknown'] ?? 0) }}</strong></div>
                </div>
                <div class="fq-issues">
                    @forelse ($issueBreakdown->take(4) as $issue)
                        <div class="fq-issue"><span class="fq-issue-name">{{ str_replace('_', ' ', $issue->reason) }}</span><span class="fq-issue-count">{{ $fmt($issue->total) }}</span></div>
                    @empty
                        <div class="fq-muted">Tidak ada quality issue.</div>
                    @endforelse
                </div>
            </div>

            <div class="fq-card fq-side-note">
                <strong>Quality gate</strong><br>
                Hanya order berstatus <strong>Ready</strong> yang dapat dipakai dalam laporan profit dan posting accounting. Settlement harus memiliki field finansial lengkap sebelum dianggap sebagai fakta payout.
            </div>
        </aside>
    </section>
</div>
@endsection
