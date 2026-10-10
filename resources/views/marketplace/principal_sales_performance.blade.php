@extends('layouts.app')

@section('title', 'Principal Sales Performance')

@php
    $summary = is_array($summary ?? null) ? $summary : [];
    $details = collect($details ?? []);
    $filters = $filters ?? [];
    $currency = $summary['currency'] ?? ($filters['currency'] ?? 'USD');
    $latestDate = now()->subDay()->toDateString();
    $granularityLabels = [
        'customize' => 'Custom / harian',
        'day' => 'Harian',
        'week' => 'Mingguan',
        'month' => 'Bulanan',
        'quarter' => 'Kuartalan',
        'year' => 'Tahunan',
    ];
    $money = function ($value, $moneyCurrency = null) {
        if ($value === null || $value === '') {
            return '-';
        }

        return ($moneyCurrency ? $moneyCurrency . ' ' : '') . number_format((float) $value, 2, ',', '.');
    };
    $integer = fn ($value) => $value === null || $value === '' ? '-' : number_format((int) $value, 0, ',', '.');
    $percent = fn ($value) => $value === null || $value === '' ? '-' : number_format((float) $value * 100, 2, ',', '.') . '%';
    $periodLabel = ($filters['start_date'] ?? '-') . ' – ' . ($filters['end_date'] ?? '-');
    $scopeLabel = filled($filters['regions'] ?? null) ? strtoupper($filters['regions']) : 'Semua wilayah';
    $kpis = [
        ['label' => 'Total sales', 'value' => $money($summary['sales'] ?? null, $currency), 'note' => 'Nilai order setelah rebate', 'icon' => 'bi-cash-stack', 'tone' => 'blue'],
        ['label' => 'Orders', 'value' => $integer($summary['orders'] ?? null), 'note' => 'Termasuk unpaid order', 'icon' => 'bi-bag-check', 'tone' => 'violet'],
        ['label' => 'Units sold', 'value' => $integer($summary['units_sold'] ?? null), 'note' => 'Unit dari order terbuat', 'icon' => 'bi-box-seam', 'tone' => 'amber'],
        ['label' => 'Product views', 'value' => $integer($summary['product_views'] ?? null), 'note' => 'Kunjungan halaman produk', 'icon' => 'bi-eye', 'tone' => 'cyan'],
        ['label' => 'Unique visitors', 'value' => $integer($summary['unique_visitors'] ?? null), 'note' => 'Visitor unik di periode', 'icon' => 'bi-people', 'tone' => 'emerald'],
        ['label' => 'Order conversion', 'value' => $percent($summary['order_conversion_rate'] ?? null), 'note' => 'Orders ÷ product clicks', 'icon' => 'bi-graph-up-arrow', 'tone' => 'rose'],
    ];
@endphp

@push('head')
<style>
    @media (min-width: 768px) { .app-main .page-wrap:has(.principal-dashboard) { max-width: none; } }
    .principal-dashboard { --pd-card: var(--card, #fff); --pd-soft: var(--card-soft, #f8fafc); --pd-line: var(--line, #d9deea); --pd-text: var(--text, #0f172a); --pd-muted: var(--muted, #64748b); --pd-accent: var(--accent, #2563eb); --pd-accent-soft: var(--accent-soft, #dbeafe); max-width: 1440px; margin-inline: auto; padding: .25rem .15rem 4rem; color: var(--pd-text); }
    .principal-dashboard .pd-card { border: 1px solid var(--pd-line); border-radius: 14px; background: var(--pd-card); box-shadow: 0 10px 30px rgba(15, 23, 42, .055); }
    .principal-dashboard .pd-hero { position: relative; overflow: hidden; display: flex; align-items: flex-end; justify-content: space-between; gap: 1.5rem; min-height: 176px; padding: 1.55rem 1.65rem; margin-bottom: 1rem; color: #fff; border-radius: 16px; background: linear-gradient(120deg, #172554 0%, #1d4ed8 62%, #0f766e 150%); box-shadow: 0 18px 44px rgba(30, 64, 175, .22); }
    .principal-dashboard .pd-hero::after { content: ''; position: absolute; width: 330px; height: 330px; right: -105px; top: -175px; border: 1px solid rgba(255,255,255,.16); border-radius: 50%; box-shadow: 0 0 0 28px rgba(255,255,255,.035), 0 0 0 58px rgba(255,255,255,.025); pointer-events: none; }
    .principal-dashboard .pd-kicker { position: relative; z-index: 1; font-size: .68rem; font-weight: 800; letter-spacing: .13em; text-transform: uppercase; opacity: .72; }
    .principal-dashboard .pd-title { position: relative; z-index: 1; margin: .45rem 0 .35rem; font-size: clamp(1.5rem, 2.8vw, 2.25rem); font-weight: 850; letter-spacing: -.045em; }
    .principal-dashboard .pd-subtitle { position: relative; z-index: 1; max-width: 650px; margin: 0; color: rgba(255,255,255,.74); font-size: .86rem; }
    .principal-dashboard .pd-hero-context { position: relative; z-index: 1; display: grid; grid-template-columns: repeat(2, minmax(120px, 1fr)); min-width: 285px; padding: .85rem 1rem; border: 1px solid rgba(255,255,255,.16); border-radius: 12px; background: rgba(15,23,42,.2); backdrop-filter: blur(8px); }
    .principal-dashboard .pd-context-item + .pd-context-item { padding-left: .9rem; border-left: 1px solid rgba(255,255,255,.16); }
    .principal-dashboard .pd-context-label { color: rgba(255,255,255,.58); font-size: .62rem; font-weight: 800; letter-spacing: .06em; text-transform: uppercase; }
    .principal-dashboard .pd-context-value { overflow: hidden; margin-top: .22rem; font-size: .82rem; font-weight: 750; text-overflow: ellipsis; white-space: nowrap; }
    .principal-dashboard .pd-filter { margin-bottom: 1.1rem; }
    .principal-dashboard .pd-filter-head { display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: .95rem 1.15rem; border-bottom: 1px solid var(--pd-line); }
    .principal-dashboard .pd-eyebrow { color: var(--pd-accent); font-size: .66rem; font-weight: 850; letter-spacing: .11em; text-transform: uppercase; }
    .principal-dashboard .pd-section-title { margin: .18rem 0 0; color: var(--pd-text); font-size: 1rem; font-weight: 800; letter-spacing: -.02em; }
    .principal-dashboard .pd-filter-body { padding: 1rem 1.15rem 1.1rem; }
    .principal-dashboard .pd-filter .form-label { margin-bottom: .35rem; color: var(--pd-muted); font-size: .7rem; font-weight: 800; }
    .principal-dashboard .pd-filter .form-control, .principal-dashboard .pd-filter .form-select { min-height: 38px; border-color: var(--pd-line); border-radius: 8px; background-color: var(--pd-card); color: var(--pd-text); font-size: .8rem; }
    .principal-dashboard .pd-filter .form-control::placeholder { color: #94a3b8; }
    .principal-dashboard .pd-filter .form-control:focus, .principal-dashboard .pd-filter .form-select:focus { border-color: var(--pd-accent); box-shadow: 0 0 0 .2rem color-mix(in srgb, var(--pd-accent) 16%, transparent); }
    .principal-dashboard .pd-filter-help { margin-top: .35rem; color: var(--pd-muted); font-size: .68rem; line-height: 1.35; }
    .principal-dashboard .pd-filter-foot { display: flex; align-items: center; justify-content: space-between; gap: 1rem; margin-top: .95rem; padding-top: .85rem; border-top: 1px solid color-mix(in srgb, var(--pd-line) 75%, transparent); }
    .principal-dashboard .pd-filter-note { display: flex; align-items: center; gap: .4rem; color: var(--pd-muted); font-size: .72rem; }
    .principal-dashboard .pd-filter-note i { color: var(--pd-accent); }
    .principal-dashboard .pd-actions { display: flex; align-items: center; gap: .45rem; }
    .principal-dashboard .pd-actions .btn { min-height: 36px; border-radius: 8px; font-size: .78rem; font-weight: 750; }
    .principal-dashboard .pd-actions .btn-primary { background: var(--pd-accent); border-color: var(--pd-accent); }
    .principal-dashboard .pd-reset { color: var(--pd-muted); text-decoration: none; font-size: .75rem; font-weight: 700; }
    .principal-dashboard .pd-reset:hover { color: var(--pd-accent); }
    .principal-dashboard .pd-alert { display: flex; align-items: flex-start; gap: .65rem; padding: .9rem 1rem; border: 1px solid; border-radius: 12px; font-size: .8rem; }
    .principal-dashboard .pd-alert i { margin-top: .08rem; font-size: 1rem; }
    .principal-dashboard .pd-alert-danger { color: #991b1b; border-color: #fecaca; background: #fff1f2; }
    .principal-dashboard .pd-alert-warning { color: #92400e; border-color: #fde68a; background: #fffbeb; }
    body[data-theme="dark"] .principal-dashboard .pd-alert-danger { color: #fecaca; border-color: rgba(248,113,113,.28); background: rgba(127,29,29,.2); }
    body[data-theme="dark"] .principal-dashboard .pd-alert-warning { color: #fde68a; border-color: rgba(251,191,36,.28); background: rgba(120,53,15,.2); }
    .principal-dashboard .pd-section-head { display: flex; align-items: flex-end; justify-content: space-between; gap: 1rem; margin: 1.4rem 0 .75rem; }
    .principal-dashboard .pd-section-subtitle { margin: .25rem 0 0; color: var(--pd-muted); font-size: .75rem; }
    .principal-dashboard .pd-result-meta { display: flex; align-items: center; gap: .4rem; color: var(--pd-muted); font-size: .72rem; white-space: nowrap; }
    .principal-dashboard .pd-result-meta .badge { color: var(--pd-accent); background: var(--pd-accent-soft); font-size: .68rem; font-weight: 800; }
    .principal-dashboard .pd-kpi { position: relative; min-height: 132px; overflow: hidden; }
    .principal-dashboard .pd-kpi::after { content: ''; position: absolute; right: -1.6rem; bottom: -2.2rem; width: 6rem; height: 6rem; border-radius: 50%; background: var(--pd-accent); opacity: .065; }
    .principal-dashboard .pd-kpi-body { position: relative; z-index: 1; padding: 1rem; }
    .principal-dashboard .pd-kpi-top { display: flex; align-items: flex-start; justify-content: space-between; gap: .6rem; }
    .principal-dashboard .pd-kpi-label { color: var(--pd-muted); font-size: .68rem; font-weight: 800; letter-spacing: .03em; text-transform: uppercase; }
    .principal-dashboard .pd-kpi-icon { display: inline-flex; align-items: center; justify-content: center; flex: 0 0 30px; width: 30px; height: 30px; border-radius: 8px; font-size: .9rem; }
    .principal-dashboard .pd-kpi-icon.blue { color: #2563eb; background: #dbeafe; } .principal-dashboard .pd-kpi-icon.violet { color: #7c3aed; background: #ede9fe; } .principal-dashboard .pd-kpi-icon.amber { color: #d97706; background: #fef3c7; } .principal-dashboard .pd-kpi-icon.cyan { color: #0891b2; background: #cffafe; } .principal-dashboard .pd-kpi-icon.emerald { color: #059669; background: #d1fae5; } .principal-dashboard .pd-kpi-icon.rose { color: #e11d48; background: #ffe4e6; }
    .principal-dashboard .pd-kpi-value { overflow: hidden; margin-top: .85rem; color: var(--pd-text); font-size: clamp(1.05rem, 1.7vw, 1.35rem); font-weight: 850; letter-spacing: -.035em; text-overflow: ellipsis; white-space: nowrap; }
    .principal-dashboard .pd-kpi-note { overflow: hidden; margin-top: .25rem; color: var(--pd-muted); font-size: .68rem; text-overflow: ellipsis; white-space: nowrap; }
    .principal-dashboard .pd-main-grid { display: grid; grid-template-columns: minmax(0, 1fr) 280px; gap: 1rem; align-items: start; margin-top: 1rem; }
    .principal-dashboard .pd-panel-head { display: flex; align-items: center; justify-content: space-between; gap: .75rem; padding: 1rem 1.1rem; border-bottom: 1px solid var(--pd-line); }
    .principal-dashboard .pd-panel-title { margin: 0; color: var(--pd-text); font-size: .95rem; font-weight: 800; }
    .principal-dashboard .pd-panel-caption { margin: .2rem 0 0; color: var(--pd-muted); font-size: .7rem; }
    .principal-dashboard .pd-count { color: var(--pd-muted); font-size: .72rem; font-weight: 700; white-space: nowrap; }
    .principal-dashboard .pd-table-wrap { overflow-x: auto; }
    .principal-dashboard .pd-table { --bs-table-bg: var(--pd-card); --bs-table-color: var(--pd-text); --bs-table-hover-bg: color-mix(in srgb, var(--pd-accent-soft) 38%, var(--pd-card)); min-width: 970px; margin: 0; font-size: .76rem; }
    .principal-dashboard .pd-table th { position: sticky; top: 0; z-index: 2; padding: .7rem .8rem; color: var(--pd-muted); background: var(--pd-soft); border-bottom: 1px solid var(--pd-line); font-size: .63rem; font-weight: 850; letter-spacing: .045em; text-transform: uppercase; white-space: nowrap; }
    .principal-dashboard .pd-table td { padding: .75rem .8rem; border-color: color-mix(in srgb, var(--pd-line) 70%, transparent); vertical-align: middle; white-space: nowrap; }
    .principal-dashboard .pd-table .num { text-align: right; font-variant-numeric: tabular-nums; }
    .principal-dashboard .pd-region { display: inline-flex; align-items: center; gap: .45rem; color: var(--pd-text); font-weight: 800; }
    .principal-dashboard .pd-region-code { display: inline-flex; align-items: center; justify-content: center; width: 29px; height: 24px; border: 1px solid color-mix(in srgb, var(--pd-accent) 20%, var(--pd-line)); border-radius: 6px; color: var(--pd-accent); background: var(--pd-accent-soft); font-size: .65rem; font-weight: 900; }
    .principal-dashboard .pd-empty { padding: 3rem 1rem; color: var(--pd-muted); text-align: center; }
    .principal-dashboard .pd-empty i { display: block; margin-bottom: .65rem; color: var(--pd-accent); font-size: 2rem; }
    .principal-dashboard .pd-side-card { padding: 1rem; }
    .principal-dashboard .pd-side-row { display: flex; justify-content: space-between; gap: .75rem; padding: .55rem 0; border-bottom: 1px solid color-mix(in srgb, var(--pd-line) 70%, transparent); font-size: .75rem; }
    .principal-dashboard .pd-side-row:last-child { border-bottom: 0; padding-bottom: 0; }
    .principal-dashboard .pd-side-label { color: var(--pd-muted); }
    .principal-dashboard .pd-side-value { color: var(--pd-text); font-weight: 800; text-align: right; }
    .principal-dashboard .pd-info { margin-top: 1rem; color: var(--pd-muted); font-size: .72rem; line-height: 1.55; }
    .principal-dashboard .pd-info strong { color: var(--pd-text); }
    body[data-theme="dark"] .principal-dashboard .pd-kpi-icon.blue { color: #93c5fd; background: rgba(30,64,175,.35); } body[data-theme="dark"] .principal-dashboard .pd-kpi-icon.violet { color: #c4b5fd; background: rgba(76,29,149,.35); } body[data-theme="dark"] .principal-dashboard .pd-kpi-icon.amber { color: #fcd34d; background: rgba(120,53,15,.35); } body[data-theme="dark"] .principal-dashboard .pd-kpi-icon.cyan { color: #67e8f9; background: rgba(22,78,99,.35); } body[data-theme="dark"] .principal-dashboard .pd-kpi-icon.emerald { color: #6ee7b7; background: rgba(6,78,59,.35); } body[data-theme="dark"] .principal-dashboard .pd-kpi-icon.rose { color: #fda4af; background: rgba(136,19,55,.35); }
    body[data-theme="dark"] .principal-dashboard .pd-table th { background: #132a45; }
    @media (max-width: 1100px) { .principal-dashboard .pd-main-grid { grid-template-columns: 1fr; } .principal-dashboard .pd-side-card { display: grid; grid-template-columns: repeat(3, 1fr); gap: .7rem; } .principal-dashboard .pd-side-row { display: block; padding: 0; border: 0; } .principal-dashboard .pd-side-value { margin-top: .18rem; text-align: left; } .principal-dashboard .pd-info { grid-column: 1 / -1; margin-top: .2rem; } }
    @media (max-width: 768px) { .principal-dashboard { padding-inline: 0; } .principal-dashboard .pd-hero { display: block; min-height: 0; padding: 1.2rem; } .principal-dashboard .pd-hero-context { min-width: 0; margin-top: 1.2rem; } .principal-dashboard .pd-filter-head, .principal-dashboard .pd-filter-body, .principal-dashboard .pd-panel-head, .principal-dashboard .pd-side-card { padding-inline: .85rem; } .principal-dashboard .pd-filter-foot { display: block; } .principal-dashboard .pd-actions { justify-content: space-between; margin-top: .75rem; } .principal-dashboard .pd-section-head { display: block; } .principal-dashboard .pd-result-meta { margin-top: .55rem; } }
    @media (max-width: 576px) { .principal-dashboard .pd-kpi-body { padding: .85rem; } .principal-dashboard .pd-kpi { min-height: 122px; } .principal-dashboard .pd-side-card { grid-template-columns: repeat(2, 1fr); } }
</style>
@endpush

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.querySelector('.principal-dashboard form');
        const button = form?.querySelector('button[type="submit"]');
        if (!form || !button) return;

        form.addEventListener('submit', function () {
            button.disabled = true;
            button.innerHTML = '<span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>Memuat data';
        });
    });
</script>
@endpush

<div class="principal-dashboard">
    <header class="pd-hero">
        <div>
            <div class="pd-kicker"><i class="bi bi-bar-chart-line me-1"></i> Shopee Brand Portal · Performance</div>
            <h1 class="pd-title">Principal Sales Performance</h1>
            <p class="pd-subtitle">Pantau performa penjualan lintas wilayah dalam satu ringkasan operasional yang terukur.</p>
        </div>
        <div class="pd-hero-context" aria-label="Report context">
            <div class="pd-context-item"><div class="pd-context-label">Scope</div><div class="pd-context-value">Principal / Region</div></div>
            <div class="pd-context-item"><div class="pd-context-label">Mode</div><div class="pd-context-value"><i class="bi bi-shield-check me-1"></i>Read-only</div></div>
        </div>
    </header>

    @if ($error)
        <div class="pd-alert pd-alert-danger mb-3" role="alert"><i class="bi bi-exclamation-octagon"></i><div><strong>Data belum dapat dimuat.</strong><br>{{ $error }}</div></div>
    @endif

    @if ($stores->isEmpty())
        <div class="pd-alert pd-alert-warning mb-3" role="alert"><i class="bi bi-link-45deg"></i><div><strong>Belum ada koneksi Shopee aktif.</strong><br>Hubungkan toko terlebih dahulu melalui menu Toko Online.</div></div>
    @endif

    <form method="GET" class="pd-card pd-filter">
        <input type="hidden" name="load" value="1">
        <div class="pd-filter-head">
            <div><div class="pd-eyebrow">Report controls</div><h2 class="pd-section-title">Konfigurasi laporan</h2></div>
            <div class="pd-filter-note"><i class="bi bi-info-circle"></i><span>Hanya data periode selesai yang tersedia</span></div>
        </div>
        <div class="pd-filter-body">
            <div class="row g-3">
                <div class="col-12 col-xl-3">
                    <label class="form-label" for="principal-store">Koneksi Shopee</label>
                    <select id="principal-store" class="form-select" name="store_id" required>
                        @foreach ($stores as $store)
                            <option value="{{ $store->id }}" @selected((string) $store->id === (string) ($filters['store_id'] ?? ''))>{{ $store->name }} · #{{ $store->external_shop_id ?? $store->id }}</option>
                        @endforeach
                    </select>
                    <div class="pd-filter-help">Token principal harus tersedia pada koneksi terpilih.</div>
                </div>
                <div class="col-12 col-xl-3">
                    <label class="form-label" for="principal-id">Principal ID</label>
                    <input id="principal-id" class="form-control" name="principal_id" value="{{ $filters['principal_id'] ?? '' }}" inputmode="numeric" placeholder="Contoh: 123456" required>
                    <div class="pd-filter-help">Opsional dari konfigurasi <code>SHOPEE_PRINCIPAL_ID</code>.</div>
                </div>
                <div class="col-6 col-xl-2"><label class="form-label" for="principal-start">Mulai</label><input id="principal-start" type="date" class="form-control" name="start_date" value="{{ $filters['start_date'] ?? '' }}" max="{{ $latestDate }}" required></div>
                <div class="col-6 col-xl-2"><label class="form-label" for="principal-end">Sampai</label><input id="principal-end" type="date" class="form-control" name="end_date" value="{{ $filters['end_date'] ?? '' }}" max="{{ $latestDate }}" required></div>
                <div class="col-6 col-xl-2">
                    <label class="form-label" for="principal-timezone">Timezone</label>
                    <select id="principal-timezone" class="form-select" name="timezone">@foreach (['GMT+7', 'GMT+8', 'GMT-3'] as $timezone)<option value="{{ $timezone }}" @selected(($filters['timezone'] ?? 'GMT+7') === $timezone)>{{ $timezone }}</option>@endforeach</select>
                </div>
                <div class="col-6 col-xl-2">
                    <label class="form-label" for="principal-granularity">Granularitas</label>
                    <select id="principal-granularity" class="form-select" name="granularity">@foreach ($granularityLabels as $value => $label)<option value="{{ $value }}" @selected(($filters['granularity'] ?? 'customize') === $value)>{{ $label }}</option>@endforeach</select>
                </div>
                <div class="col-6 col-xl-2">
                    <label class="form-label" for="principal-currency">Mata uang</label>
                    <select id="principal-currency" class="form-select" name="currency">@foreach (['USD', 'LOCAL'] as $currencyOption)<option value="{{ $currencyOption }}" @selected(($filters['currency'] ?? 'USD') === $currencyOption)>{{ $currencyOption }}</option>@endforeach</select>
                </div>
                <div class="col-12 col-xl-6"><label class="form-label" for="principal-regions">Wilayah <span class="fw-normal">(opsional)</span></label><input id="principal-regions" class="form-control" name="regions" value="{{ $filters['regions'] ?? '' }}" placeholder="Contoh: ID, MY, SG — kosongkan untuk semua wilayah"><div class="pd-filter-help">Gunakan kode negara yang dipisahkan koma atau spasi.</div></div>
            </div>
            <div class="pd-filter-foot">
                <div class="pd-filter-note"><i class="bi bi-lock"></i><span>Request bersifat read-only dan tidak mengubah data Shopee.</span></div>
                <div class="pd-actions"><a class="pd-reset" href="{{ route('marketplace.reports.principal-sales-performance') }}">Reset filter</a><button class="btn btn-primary px-3" type="submit"><i class="bi bi-arrow-clockwise me-1"></i>Ambil data</button></div>
            </div>
        </div>
    </form>

    @if ($loaded && !$error)
        <div class="pd-section-head"><div><div class="pd-eyebrow">Executive summary</div><h2 class="pd-section-title">Ringkasan performa penjualan</h2><p class="pd-section-subtitle">{{ $periodLabel }} · {{ $granularityLabels[$filters['granularity'] ?? 'customize'] ?? 'Custom' }} · {{ $scopeLabel }}</p></div><div class="pd-result-meta"><span class="badge rounded-pill">{{ $details->count() }} wilayah</span><span>{{ $currency }}</span></div></div>

        <div class="row g-3">
            @foreach ($kpis as $kpi)
                <div class="col-6 col-xl-2"><article class="pd-card pd-kpi h-100"><div class="pd-kpi-body"><div class="pd-kpi-top"><div class="pd-kpi-label">{{ $kpi['label'] }}</div><span class="pd-kpi-icon {{ $kpi['tone'] }}"><i class="bi {{ $kpi['icon'] }}"></i></span></div><div class="pd-kpi-value" title="{{ $kpi['value'] }}">{{ $kpi['value'] }}</div><div class="pd-kpi-note">{{ $kpi['note'] }}</div></div></article></div>
            @endforeach
        </div>

        <div class="pd-main-grid">
            <section class="pd-card" aria-labelledby="principal-region-breakdown">
                <div class="pd-panel-head"><div><h2 id="principal-region-breakdown" class="pd-panel-title">Breakdown per wilayah</h2><p class="pd-panel-caption">Performa region pada periode dan scope yang dipilih.</p></div><div class="pd-count"><i class="bi bi-table me-1"></i>{{ $details->count() }} record</div></div>
                <div class="pd-table-wrap"><table class="table table-hover pd-table"><thead><tr><th>Region</th><th class="text-end">Sales</th><th class="text-end">Orders</th><th class="text-end">Units</th><th class="text-end">Avg basket</th><th class="text-end">Avg price</th><th class="text-end">Views</th><th class="text-end">Visitors</th><th class="text-end">Item conv.</th><th class="text-end">Order conv.</th></tr></thead><tbody>
                    @forelse ($details as $detail)
                        <tr><td><span class="pd-region"><span class="pd-region-code">{{ $detail['region'] ?? '-' }}</span>{{ $detail['currency'] ?? $currency }}</span></td><td class="num fw-bold">{{ $money($detail['sales'] ?? null, $detail['currency'] ?? $currency) }}</td><td class="num">{{ $integer($detail['orders'] ?? null) }}</td><td class="num">{{ $integer($detail['units_sold'] ?? null) }}</td><td class="num">{{ $money($detail['average_basket_size'] ?? null) }}</td><td class="num">{{ $money($detail['average_selling_price'] ?? null) }}</td><td class="num">{{ $integer($detail['product_views'] ?? null) }}</td><td class="num">{{ $integer($detail['unique_visitors'] ?? null) }}</td><td class="num">{{ $percent($detail['item_conversion_rate'] ?? null) }}</td><td class="num">{{ $percent($detail['order_conversion_rate'] ?? null) }}</td></tr>
                    @empty
                        <tr><td colspan="10"><div class="pd-empty"><i class="bi bi-inbox"></i><strong>Belum ada data wilayah</strong><div>Periksa kembali periode atau filter region yang dipilih.</div></div></td></tr>
                    @endforelse
                </tbody></table></div>
            </section>

            <aside class="pd-card pd-side-card" aria-label="Report details"><div class="pd-eyebrow mb-2">Report context</div><div class="pd-side-row"><span class="pd-side-label">Period</span><span class="pd-side-value">{{ $periodLabel }}</span></div><div class="pd-side-row"><span class="pd-side-label">Granularity</span><span class="pd-side-value">{{ $granularityLabels[$filters['granularity'] ?? 'customize'] ?? 'Custom' }}</span></div><div class="pd-side-row"><span class="pd-side-label">Timezone</span><span class="pd-side-value">{{ $filters['timezone'] ?? 'GMT+7' }}</span></div><div class="pd-side-row"><span class="pd-side-label">Currency</span><span class="pd-side-value">{{ $currency }}</span></div><div class="pd-side-row"><span class="pd-side-label">Region scope</span><span class="pd-side-value">{{ $scopeLabel }}</span></div><div class="pd-info"><strong>Catatan data:</strong> sales dapat mencakup paid/unpaid order serta order cancelled dan return/refund sesuai definisi Shopee.</div></aside>
        </div>
    @elseif (!$loaded && !$stores->isEmpty())
        <div class="pd-card pd-empty"><i class="bi bi-sliders2"></i><strong>Pilih filter untuk mulai</strong><div class="mt-1">Atur principal, periode, dan wilayah untuk melihat ringkasan performa.</div></div>
    @endif
</div>
