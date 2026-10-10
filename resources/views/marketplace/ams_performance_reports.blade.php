@extends('layouts.app')

@php
    $filters = $filters ?? [];
    $response = is_array($response ?? null) ? $response : [];
    $overview = is_array($overview ?? null) ? $overview : [];
    $rows = collect($rows ?? []);
    $validationList = collect($validationList ?? []);
    $validationReport = collect($validationReport ?? []);

    $reportLabels = [
        'overview' => 'Overview toko',
        'product' => 'Performa produk',
        'affiliate' => 'Performa affiliate',
        'content' => 'Performa content',
        'open_campaign' => 'Open Campaign',
        'targeted_campaign' => 'Targeted Campaign',
        'conversion' => 'Conversion Report',
        'validation' => 'Validation Report',
    ];
    $selectedReport = $filters['report'] ?? 'overview';
    $hasAdvancedFilters = collect([
        'item_id', 'affiliate_id', 'campaign_id', 'order_sn', 'item_name',
        'l1_category_id', 'l2_category_id', 'l3_category_id', 'order_status',
        'verified_status', 'seller_campaign_type', 'deduction_status', 'deduction_method',
    ])->contains(fn ($field) => filled($filters[$field] ?? null));
    $endpointPaths = [
        'overview' => 'get_shop_performance + get_campaign_key_metrics_performance',
        'product' => 'get_product_performance',
        'affiliate' => 'get_affiliate_performance',
        'content' => 'get_content_performance',
        'open_campaign' => 'get_open_campaign_performance',
        'targeted_campaign' => 'get_targeted_campaign_performance',
        'conversion' => 'get_conversion_report',
        'validation' => 'get_validation_list + get_validation_report',
    ];
    $shop = (array) ($overview['shop'] ?? []);
    $campaignMetrics = (array) ($overview['campaign_metrics'] ?? []);
    $openMetrics = (array) ($campaignMetrics['open_campaign_key_metircs'] ?? $campaignMetrics['open_campaign_key_metrics'] ?? []);
    $targetedMetrics = (array) ($campaignMetrics['targeted_campaign_key_metircs'] ?? $campaignMetrics['targeted_campaign_key_metrics'] ?? []);

    $value = function ($value, $decimals = 0) {
        if ($value === null || $value === '') return '—';
        if (! is_numeric($value)) return (string) $value;
        return number_format((float) $value, $decimals, ',', '.');
    };
    $money = fn ($value) => $value === null || $value === '' ? '—' : (is_numeric($value) ? number_format((float) $value, 2, ',', '.') : $value);
    $dateTime = function ($value) {
        if ($value === null || $value === '') return '—';
        if (is_numeric($value)) return date('d M Y H:i', (int) $value);
        return (string) $value;
    };
@endphp

@section('title', 'AMS Performance & Report')

<style>
    .ams-page {
        --ams-ink: var(--text, #172033);
        --ams-muted: var(--muted, #64748b);
        --ams-line: color-mix(in srgb, var(--ams-ink) 12%, transparent);
        --ams-soft: color-mix(in srgb, var(--primary-soft, #eff6ff) 58%, var(--card, #fff) 42%);
        --ams-card: var(--card, #fff);
        max-width: 1760px;
        margin: 0 auto;
        padding: 1.75rem 1.25rem 2.75rem;
        color: var(--ams-ink);
    }
    .ams-page .text-muted,
    .ams-muted { color: var(--ams-muted) !important; font-size: .82rem; }
    .ams-hero {
        position: relative;
        overflow: hidden;
        border: 1px solid var(--ams-line);
        border-radius: 14px;
        padding: 1.25rem 1.35rem;
        background: linear-gradient(135deg, color-mix(in srgb, var(--primary-soft, #eff6ff) 62%, var(--ams-card) 38%), var(--ams-card) 72%);
        box-shadow: 0 1px 2px rgba(15, 23, 42, .035);
    }
    .ams-hero::after {
        content: "";
        position: absolute;
        width: 210px;
        height: 210px;
        top: -135px;
        right: 8%;
        border-radius: 50%;
        background: color-mix(in srgb, var(--primary) 18%, transparent);
        pointer-events: none;
    }
    .ams-hero > * { position: relative; z-index: 1; }
    .ams-eyebrow,
    .ams-label {
        color: var(--primary, #2563eb);
        font-size: .7rem;
        font-weight: 800;
        letter-spacing: .055em;
        text-transform: uppercase;
    }
    .ams-hero h1 { color: var(--ams-ink); letter-spacing: -.025em; }
    .ams-hero .ams-muted { max-width: 720px; }
    .ams-breadcrumb { color: var(--ams-muted); font-size: .75rem; font-weight: 650; }
    .ams-breadcrumb i { color: var(--primary, #2563eb); }
    .ams-status { display: inline-flex; align-items: center; gap: .4rem; border: 1px solid color-mix(in srgb, var(--primary) 24%, transparent); border-radius: 999px; padding: .38rem .68rem; background: color-mix(in srgb, var(--primary-soft, #eff6ff) 72%, var(--ams-card) 28%); color: var(--primary, #2563eb); font-size: .72rem; font-weight: 750; white-space: nowrap; }
    .ams-card {
        border: 1px solid var(--ams-line);
        border-radius: 14px;
        background: var(--ams-card);
        box-shadow: 0 1px 2px rgba(15, 23, 42, .035);
    }
    .ams-card .card-body { padding: 1.1rem 1.2rem; }
    .ams-filter-card { background: color-mix(in srgb, var(--ams-soft) 72%, var(--ams-card) 28%); }
    .ams-filter-card .card-body { padding: 1rem 1.15rem; }
    .ams-section-title { color: var(--ams-ink); font-size: .92rem; font-weight: 750; }
    .ams-section-meta { color: var(--ams-muted); font-size: .78rem; }
    .ams-filter-card .form-label { margin-bottom: .35rem; color: var(--ams-muted); font-size: .75rem; font-weight: 700; }
    .ams-filter-card .form-control,
    .ams-filter-card .form-select { border-color: var(--ams-line); background: var(--ams-card); color: var(--ams-ink); border-radius: .6rem; font-size: .84rem; }
    .ams-filter-card .form-control:focus,
    .ams-filter-card .form-select:focus { border-color: color-mix(in srgb, var(--primary) 58%, transparent); box-shadow: 0 0 0 .2rem color-mix(in srgb, var(--primary) 16%, transparent); }
    .ams-filter-section { border-top: 1px solid var(--ams-line); margin-top: 1rem; padding-top: 1rem; }
    .ams-advanced { border-top: 1px solid var(--ams-line); margin-top: 1rem; padding-top: .85rem; }
    .ams-advanced summary { cursor: pointer; color: var(--ams-ink); font-size: .78rem; font-weight: 750; list-style: none; }
    .ams-advanced summary::-webkit-details-marker { display: none; }
    .ams-advanced summary::before { content: "▸"; display: inline-block; width: 1rem; color: var(--primary, #2563eb); transition: transform .15s ease; }
    .ams-advanced[open] summary::before { transform: rotate(90deg); }
    .ams-advanced[open] .ams-advanced-body { padding-top: .85rem; }
    .ams-filter-note { display: inline-flex; align-items: flex-start; gap: .4rem; color: var(--ams-muted); font-size: .76rem; line-height: 1.45; }
    .ams-filter-note i { color: var(--primary, #2563eb); margin-top: .1rem; }
    .ams-kpi-card { position: relative; overflow: hidden; min-height: 112px; }
    .ams-kpi-card::after { content: ""; position: absolute; width: 76px; height: 76px; right: -22px; bottom: -30px; border-radius: 50%; background: var(--primary, #2563eb); opacity: .065; }
    .ams-kpi-icon { display: inline-flex; align-items: center; justify-content: center; width: 34px; height: 34px; border-radius: 9px; background: var(--primary-soft, #eff6ff); color: var(--primary, #2563eb); }
    .ams-kpi { color: var(--ams-ink); font-size: 1.3rem; font-weight: 800; letter-spacing: -.02em; }
    .ams-table-wrap { overflow-x: auto; }
    .ams-table { min-width: 900px; margin-bottom: 0; --bs-table-bg: var(--ams-card); --bs-table-color: var(--ams-ink); --bs-table-hover-bg: color-mix(in srgb, var(--primary-soft, #eff6ff) 35%, var(--ams-card)); }
    .ams-table th { color: var(--ams-muted); background: color-mix(in srgb, var(--ams-card) 88%, var(--bg, #f8fafc) 12%); border-bottom-color: var(--ams-line); font-size: .68rem; letter-spacing: .045em; text-transform: uppercase; white-space: nowrap; }
    .ams-table td { vertical-align: middle; border-color: color-mix(in srgb, var(--ams-line) 75%, transparent); white-space: nowrap; }
    .ams-table th:first-child,
    .ams-table td:first-child { padding-left: 1.2rem; }
    .ams-table th:last-child,
    .ams-table td:last-child { padding-right: 1.2rem; }
    .ams-code { color: var(--primary, #2563eb); font-size: .76rem; }
    .ams-table .ams-primary-cell { color: var(--ams-ink); font-weight: 700; }
    .ams-table .ams-id { color: var(--ams-muted); font-size: .74rem; }
    .ams-report-meta { display: flex; align-items: center; gap: .65rem; color: var(--ams-muted); font-size: .78rem; }
    .ams-report-meta .badge { border: 1px solid var(--ams-line); background: var(--ams-soft); color: var(--ams-muted); font-weight: 650; }
    .ams-empty { padding: 2.6rem 1rem; text-align: center; color: var(--ams-muted); }
    .ams-empty i { display: block; margin-bottom: .65rem; color: color-mix(in srgb, var(--primary) 58%, var(--ams-muted) 42%); font-size: 1.8rem; }
    .ams-empty strong { display: block; color: var(--ams-ink); font-size: .9rem; }
    .ams-empty span { display: block; margin-top: .25rem; font-size: .78rem; }
    .ams-alert { border: 1px solid color-mix(in srgb, #dc2626 22%, transparent); background: color-mix(in srgb, #fee2e2 58%, var(--ams-card) 42%); color: var(--ams-ink); }
    .ams-alert-warning { border-color: color-mix(in srgb, #d97706 24%, transparent); background: color-mix(in srgb, #fef3c7 58%, var(--ams-card) 42%); }
    @media (max-width: 767.98px) {
        .ams-page { padding: 1rem .75rem 2.5rem; }
        .ams-hero { padding: 1rem; }
        .ams-card .card-body,
        .ams-filter-card .card-body { padding: .95rem; }
        .ams-table { min-width: 900px; }
        .ams-report-meta { align-items: flex-start; flex-direction: column; gap: .3rem; }
    }
</style>

<div class="ams-page">
    <div class="ams-hero mb-4">
        <div class="ams-breadcrumb mb-2"><i class="bi bi-grid-1x2-fill me-1"></i> Marketplace <span class="mx-1">/</span> Reports <span class="mx-1">/</span> AMS</div>
        <div class="d-flex flex-wrap justify-content-between align-items-end gap-3">
            <div>
                <div class="ams-eyebrow mb-2"><i class="bi bi-person-hearts me-1"></i> Shopee Affiliate Marketing Solutions</div>
                <h1 class="h3 fw-bold mb-2">Performance &amp; Report</h1>
                <p class="ams-muted mb-0">Pantau kontribusi affiliate, campaign, konten, konversi, dan validasi komisi dari satu workspace.</p>
            </div>
            <div class="d-flex flex-wrap align-items-center gap-2">
                <span class="ams-status"><i class="bi bi-shield-check"></i> Read-only data</span>
                @if ($latestReportDate)
                    <span class="ams-muted"><i class="bi bi-clock me-1"></i>Update {{ $latestReportDate }}</span>
                @endif
            </div>
        </div>
    </div>

    @if ($error)
        <div class="alert ams-alert mb-4" role="alert"><i class="bi bi-exclamation-triangle me-2"></i>{{ $error }}</div>
    @endif

    @if ($stores->isEmpty())
        <div class="alert ams-alert ams-alert-warning mb-0" role="alert"><i class="bi bi-shop me-2"></i>Belum ada koneksi toko Shopee aktif. Hubungkan toko terlebih dahulu melalui menu Toko Online.</div>
    @else
        <form method="GET" class="ams-card ams-filter-card mb-4" id="amsFilters">
            <div class="card-body">
                <input type="hidden" name="load" value="1">
                <div class="d-flex flex-wrap align-items-start justify-content-between gap-2 mb-3">
                    <div>
                        <div class="ams-section-title"><i class="bi bi-sliders2 me-2 text-primary"></i>Report workspace</div>
                        <div class="ams-section-meta">Pilih ruang lingkup data lalu jalankan report sesuai kebutuhan.</div>
                    </div>
                    <span class="ams-section-meta"><i class="bi bi-info-circle me-1"></i>Semua data bersumber dari Shopee AMS</span>
                </div>

                <div class="row g-3">
                    <div class="col-12 col-xl-4">
                        <label class="form-label" for="amsStore">Koneksi Shopee</label>
                        <select class="form-select" name="store_id" id="amsStore" required>
                            @foreach ($stores as $store)
                                <option value="{{ $store->id }}" @selected((string) $store->id === (string) ($filters['store_id'] ?? ''))>
                                    {{ $store->name }} · #{{ $store->external_shop_id ?: $store->id }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 col-xl-4">
                        <label class="form-label" for="amsReport">Jenis report</label>
                        <select class="form-select" name="report" id="amsReport" required>
                            @foreach ($reportLabels as $report => $label)
                                <option value="{{ $report }}" @selected($selectedReport === $report)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6 col-xl-2 performance-filter">
                        <label class="form-label" for="amsPeriod">Periode</label>
                        <select class="form-select" name="period_type" id="amsPeriod">
                            @foreach (['Last30d' => '30 hari terakhir', 'Last7d' => '7 hari terakhir', 'Day' => 'Harian', 'Week' => 'Mingguan', 'Month' => 'Bulanan'] as $period => $label)
                                <option value="{{ $period }}" @selected(($filters['period_type'] ?? 'Last30d') === $period)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6 col-xl-2">
                        <label class="form-label" for="amsPageSize">Baris / halaman</label>
                        <select class="form-select" name="page_size" id="amsPageSize">
                            @foreach ([20, 50, 100, 500] as $pageSize)
                                <option value="{{ $pageSize }}" @selected((int) ($filters['page_size'] ?? 20) === $pageSize)>{{ $pageSize }} baris</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6 col-xl-3">
                        <label class="form-label" for="amsDateFrom">Mulai tanggal</label>
                        <input type="date" class="form-control" name="date_from" id="amsDateFrom" value="{{ $filters['date_from'] ?? '' }}" required>
                    </div>
                    <div class="col-6 col-xl-3">
                        <label class="form-label" for="amsDateTo">Sampai tanggal</label>
                        <input type="date" class="form-control" name="date_to" id="amsDateTo" value="{{ $filters['date_to'] ?? '' }}" required>
                    </div>
                    <div class="col-6 col-xl-2 performance-filter">
                        <label class="form-label" for="amsOrderType">Tipe order</label>
                        <select class="form-select" name="order_type" id="amsOrderType">
                            <option value="ConfirmedOrder" @selected(($filters['order_type'] ?? '') === 'ConfirmedOrder')>Confirmed order</option>
                            <option value="PlacedOrder" @selected(($filters['order_type'] ?? '') === 'PlacedOrder')>Placed order</option>
                        </select>
                    </div>
                    <div class="col-6 col-xl-2 performance-filter">
                        <label class="form-label" for="amsChannel">Channel</label>
                        <select class="form-select" name="channel" id="amsChannel">
                            @foreach (['AllChannel' => 'Semua channel', 'SocialMedia' => 'Social media', 'ShopeeVideo' => 'Shopee Video', 'LiveStreaming' => 'Live streaming'] as $channel => $label)
                                <option value="{{ $channel }}" @selected(($filters['channel'] ?? 'AllChannel') === $channel)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6 col-xl-2">
                        <label class="form-label" for="amsPageNo">Halaman</label>
                        <input type="number" class="form-control" name="page_no" id="amsPageNo" min="1" max="500" value="{{ $filters['page_no'] ?? 1 }}">
                    </div>
                    <div class="col-12 col-xl-2 d-flex align-items-end">
                        <button class="btn btn-primary w-100" type="submit"><i class="bi bi-arrow-repeat me-1"></i>Jalankan report</button>
                    </div>
                </div>

                <details class="ams-advanced" @if ($hasAdvancedFilters) open @endif>
                    <summary>Filter lanjutan <span class="ams-muted ms-1">(opsional)</span></summary>
                    <div class="ams-advanced-body">
                        <div class="row g-3">
                            <div class="col-6 col-md-4 col-xl-2 conversion-filter"><label class="form-label" for="amsOrderSn">Order SN</label><input id="amsOrderSn" class="form-control" name="order_sn" value="{{ $filters['order_sn'] ?? '' }}" placeholder="Cari order"></div>
                            <div class="col-6 col-md-4 col-xl-2 conversion-filter"><label class="form-label" for="amsItemName">Nama item</label><input id="amsItemName" class="form-control" name="item_name" value="{{ $filters['item_name'] ?? '' }}" placeholder="Cari item"></div>
                            <div class="col-6 col-md-4 col-xl-2"><label class="form-label" for="amsItemId">Item ID</label><input id="amsItemId" class="form-control" name="item_id" value="{{ $filters['item_id'] ?? '' }}" placeholder="ID item"></div>
                            <div class="col-6 col-md-4 col-xl-2"><label class="form-label" for="amsAffiliateId">Affiliate ID</label><input id="amsAffiliateId" class="form-control" name="affiliate_id" value="{{ $filters['affiliate_id'] ?? '' }}" placeholder="ID affiliate"></div>
                            <div class="col-6 col-md-4 col-xl-2"><label class="form-label" for="amsCampaignId">Campaign ID</label><input id="amsCampaignId" class="form-control" name="campaign_id" value="{{ $filters['campaign_id'] ?? '' }}" placeholder="ID campaign"></div>
                            <div class="col-6 col-md-4 col-xl-2 conversion-filter"><label class="form-label" for="amsL1">L1 category ID</label><input id="amsL1" class="form-control" name="l1_category_id" value="{{ $filters['l1_category_id'] ?? '' }}" placeholder="ID kategori"></div>
                            <div class="col-6 col-md-4 col-xl-2 conversion-filter"><label class="form-label" for="amsL2">L2 category ID</label><input id="amsL2" class="form-control" name="l2_category_id" value="{{ $filters['l2_category_id'] ?? '' }}" placeholder="ID kategori"></div>
                            <div class="col-6 col-md-4 col-xl-2 conversion-filter"><label class="form-label" for="amsL3">L3 category ID</label><input id="amsL3" class="form-control" name="l3_category_id" value="{{ $filters['l3_category_id'] ?? '' }}" placeholder="ID kategori"></div>
                            <div class="col-6 col-md-4 col-xl-2 conversion-filter"><label class="form-label" for="amsOrderStatus">Order status</label><select id="amsOrderStatus" class="form-select" name="order_status"><option value="">Semua status</option>@foreach (['Unpaid', 'Pending', 'Completed', 'Cancelled'] as $status)<option value="{{ $status }}" @selected(($filters['order_status'] ?? '') === $status)>{{ $status }}</option>@endforeach</select></div>
                            <div class="col-6 col-md-4 col-xl-2 conversion-filter"><label class="form-label" for="amsVerifiedStatus">Verified status</label><select id="amsVerifiedStatus" class="form-select" name="verified_status"><option value="">Semua status</option>@foreach (['Unverified', 'Valid', 'Invalid'] as $status)<option value="{{ $status }}" @selected(($filters['verified_status'] ?? '') === $status)>{{ $status }}</option>@endforeach</select></div>
                            <div class="col-6 col-md-4 col-xl-2 conversion-filter"><label class="form-label" for="amsCampaignType">Campaign type</label><select id="amsCampaignType" class="form-select" name="seller_campaign_type"><option value="">Semua tipe</option>@foreach (['TargetCampaign', 'OpenCampaign', 'MCNCampaign'] as $type)<option value="{{ $type }}" @selected(($filters['seller_campaign_type'] ?? '') === $type)>{{ $type }}</option>@endforeach</select></div>
                            <div class="col-6 col-md-4 col-xl-2 conversion-filter"><label class="form-label" for="amsDeductionStatus">Deduction status</label><select id="amsDeductionStatus" class="form-select" name="deduction_status"><option value="">Semua status</option>@foreach (['PendingDeduction', 'Deducted'] as $status)<option value="{{ $status }}" @selected(($filters['deduction_status'] ?? '') === $status)>{{ $status }}</option>@endforeach</select></div>
                            <div class="col-6 col-md-4 col-xl-2 conversion-filter"><label class="form-label" for="amsDeductionMethod">Deduction method</label><select id="amsDeductionMethod" class="form-select" name="deduction_method"><option value="">Semua metode</option>@foreach (['OrderEscrow', 'SellerWallet', 'AutoAdjustment', 'SVSPaymentLink', 'OfflineSettlement', 'AMSCredit'] as $method)<option value="{{ $method }}" @selected(($filters['deduction_method'] ?? '') === $method)>{{ $method }}</option>@endforeach</select></div>
                        </div>
                    </div>
                </details>

                <div class="d-flex flex-wrap align-items-start justify-content-between gap-2 mt-3 pt-3 border-top">
                    <div class="ams-filter-note"><i class="bi bi-info-circle"></i><span>Tanggal AMS mengikuti kalender Shopee. Data performa dapat tertinggal dari hari berjalan.</span></div>
                    <span class="ams-section-meta"><i class="bi bi-cloud-check me-1"></i>Mode sinkronisasi manual</span>
                </div>
            </div>
        </form>
    @endif

    @if ($loaded && !$error)
        <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-3">
            <div>
                <div class="ams-eyebrow mb-1">Active report</div>
                <h2 class="h5 fw-bold mb-1">{{ $reportLabels[$selectedReport] ?? 'AMS Report' }}</h2>
                <div class="ams-muted"><code class="ams-code">/api/v2/ams/{{ $endpointPaths[$selectedReport] ?? 'report' }}</code></div>
            </div>
            <div class="ams-report-meta">
                @if ($selectedReport === 'overview')
                    <span class="badge rounded-pill"><i class="bi bi-grid-1x2 me-1"></i>Ringkasan eksekutif</span>
                @elseif ($selectedReport === 'validation')
                    <span class="badge rounded-pill"><i class="bi bi-patch-check me-1"></i>Kontrol komisi</span>
                @else
                    <span class="badge rounded-pill"><i class="bi bi-table me-1"></i>Data terperinci</span>
                @endif
                @if ($latestReportDate)<span><i class="bi bi-clock me-1"></i>Data terakhir {{ $latestReportDate }}</span>@endif
            </div>
        </div>

        @if ($selectedReport === 'overview')
            <div class="row g-3 mb-4">
                @foreach ([
                    ['Sales', $money($shop['sales'] ?? null), 'bi-cash-stack'],
                    ['Orders', $value($shop['orders'] ?? null), 'bi-bag-check'],
                    ['Item terjual', $value($shop['gross_item_sold'] ?? null), 'bi-box-seam'],
                    ['Clicks', $value($shop['clicks'] ?? null), 'bi-cursor'],
                    ['Est. commission', $money($shop['est_commission'] ?? null), 'bi-wallet2'],
                    ['ROI', $value($shop['roi'] ?? null, 2), 'bi-graph-up-arrow'],
                ] as [$label, $number, $icon])
                    <div class="col-6 col-xl-2">
                        <div class="ams-card ams-kpi-card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-start justify-content-between mb-3">
                                    <span class="ams-label">{{ $label }}</span>
                                    <span class="ams-kpi-icon"><i class="bi {{ $icon }}"></i></span>
                                </div>
                                <div class="ams-kpi">{{ $number }}</div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="row g-3">
                @foreach ([['Open Campaign', $openMetrics], ['Targeted Campaign', $targetedMetrics]] as [$label, $metric])
                    <div class="col-12 col-lg-6">
                        <div class="ams-card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-center justify-content-between mb-3">
                                    <div>
                                        <div class="ams-eyebrow mb-1">Campaign channel</div>
                                        <h3 class="h6 fw-bold mb-0">{{ $label }}</h3>
                                    </div>
                                    <span class="ams-kpi-icon"><i class="bi bi-megaphone"></i></span>
                                </div>
                                <div class="row g-3">
                                    <div class="col-4"><div class="ams-label mb-1">Affiliate</div><div class="ams-kpi">{{ $value($metric['affiliates'] ?? null) }}</div></div>
                                    <div class="col-4"><div class="ams-label mb-1">Item sold</div><div class="ams-kpi">{{ $value($metric['items_sold'] ?? null) }}</div></div>
                                    <div class="col-4"><div class="ams-label mb-1">Sales</div><div class="ams-kpi">{{ $money($metric['sales'] ?? null) }}</div></div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @elseif ($selectedReport === 'validation')
            <div class="row g-3">
                <div class="col-12 col-xl-5">
                    <div class="ams-card h-100">
                        <div class="card-body border-bottom">
                            <div class="ams-eyebrow mb-1">Validation queue</div>
                            <h3 class="h6 fw-bold mb-0">Daftar validation bill</h3>
                        </div>
                        <div class="ams-table-wrap"><table class="table table-hover ams-table mb-0"><thead><tr><th>ID</th><th>Bulan</th><th>Sumber</th><th>Total</th></tr></thead><tbody>
                    @forelse ($validationList as $bill)
                        <tr><td><a class="ams-primary-cell" href="{{ request()->fullUrlWithQuery(['validation_id' => $bill['validation_id'] ?? '', 'validation_month' => $bill['validation_month'] ?? '', 'campaign_source' => $bill['campaign_source'] ?? 'ShopeeManaged']) }}">{{ $bill['validation_id'] ?? '—' }}</a></td><td>{{ $bill['validation_month'] ?? '—' }}</td><td>{{ $bill['campaign_source'] ?? '—' }}</td><td>{{ $money(data_get($bill, 'online_bill.total_amount', data_get($bill, 'total_amount'))) }}</td></tr>
                    @empty <tr><td colspan="4"><div class="ams-empty"><i class="bi bi-inbox"></i><strong>Belum ada validation bill</strong><span>Shopee belum mengembalikan validation bill untuk filter ini.</span></div></td></tr>@endforelse
                </tbody></table></div>
                    </div>
                </div>
                <div class="col-12 col-xl-7">
                    <div class="ams-card h-100">
                        <div class="card-body border-bottom">
                            <div class="ams-eyebrow mb-1">Validation detail</div>
                            <h3 class="h6 fw-bold mb-0">Detail validation report</h3>
                        </div>
                        @if ($filters['validation_id'] === '')
                            <div class="ams-empty"><i class="bi bi-arrow-left-circle"></i><strong>Pilih validation bill</strong><span>Gunakan ID pada tabel di sebelah kiri untuk memuat detail komisi.</span></div>
                        @else
                            <div class="ams-table-wrap"><table class="table table-hover ams-table mb-0"><thead><tr><th>Order</th><th>Status</th><th>Affiliate</th><th>Item</th><th>Commission</th><th>Service fee</th></tr></thead><tbody>
                    @forelse ($validationReport as $row)
                        @foreach ((array) ($row['items'] ?? [[]]) as $item)<tr><td>{{ $row['order_sn'] ?? '—' }}</td><td>{{ $row['verified_status'] ?? ($row['order_status'] ?? '—') }}</td><td>{{ $row['affiliate_name'] ?? '—' }}</td><td>{{ $item['item_name'] ?? '—' }}</td><td>{{ $money($item['item_brand_commission'] ?? null) }}</td><td>{{ $money($item['seller_service_fee'] ?? null) }}</td></tr>@endforeach
                    @empty <tr><td colspan="6"><div class="ams-empty"><i class="bi bi-search"></i><strong>Tidak ada detail ditemukan</strong><span>Periksa kembali validation bill atau rentang tanggal yang dipilih.</span></div></td></tr>@endforelse
                </tbody></table></div>
                        @endif
                    </div>
                </div>
            </div>
        @else
            <div class="ams-card">
                <div class="card-body border-bottom">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                        <div>
                            <div class="ams-eyebrow mb-1">Report data</div>
                            <div class="ams-section-title">Rincian {{ strtolower($reportLabels[$selectedReport] ?? 'AMS report') }}</div>
                        </div>
                        <div class="ams-report-meta">
                            <span>{{ $rows->count() }} baris ditampilkan</span>
                            <span class="badge rounded-pill">Total {{ $value($response['total_count'] ?? null) }}</span>
                            <span class="badge rounded-pill">{{ !empty($response['has_more']) ? 'Ada halaman berikutnya' : 'Halaman terakhir' }}</span>
                        </div>
                    </div>
                </div>
                <div class="ams-table-wrap"><table class="table table-hover ams-table mb-0"><thead><tr>
                @if ($selectedReport === 'product') <th>Item</th><th>Sales</th><th>Item sold</th><th>Orders</th><th>Clicks</th><th>Commission</th><th>ROI</th>
                @elseif ($selectedReport === 'affiliate') <th>Affiliate</th><th>Username</th><th>Sales</th><th>Item sold</th><th>Orders</th><th>Clicks</th><th>Commission</th><th>ROI</th>
                @elseif ($selectedReport === 'content') <th>Content</th><th>Affiliate</th><th>Channel</th><th>Views</th><th>Likes</th><th>Comments</th><th>Sales</th><th>Orders</th>
                @elseif ($selectedReport === 'open_campaign') <th>Item</th><th>Affiliate</th><th>Sales</th><th>Item sold</th><th>Commission</th>
                @elseif ($selectedReport === 'targeted_campaign') <th>Campaign</th><th>Affiliate</th><th>Sales</th><th>Item sold</th><th>Commission</th>
                @elseif ($selectedReport === 'conversion') <th>Order</th><th>Status</th><th>Affiliate</th><th>Channel</th><th>Items</th><th>Purchase value</th><th>Commission</th><th>Service fee</th>
                @endif
            </tr></thead><tbody>
                @forelse ($rows as $row)
                    <tr>
                    @if ($selectedReport === 'product') <td><strong>{{ $row['item_name'] ?? '—' }}</strong><br><span class="ams-muted">#{{ $row['item_id'] ?? '—' }}</span></td><td>{{ $money($row['sales'] ?? null) }}</td><td>{{ $value($row['items_sold'] ?? null) }}</td><td>{{ $value($row['orders'] ?? null) }}</td><td>{{ $value($row['clicks'] ?? null) }}</td><td>{{ $money($row['est_commission'] ?? null) }}</td><td>{{ $value($row['roi'] ?? null, 2) }}</td>
                    @elseif ($selectedReport === 'affiliate') <td>{{ $row['affiliate_name'] ?? '—' }}</td><td>{{ $row['affiliate_username'] ?? '—' }}</td><td>{{ $money($row['sales'] ?? null) }}</td><td>{{ $value($row['items_sold'] ?? null) }}</td><td>{{ $value($row['orders'] ?? null) }}</td><td>{{ $value($row['clicks'] ?? null) }}</td><td>{{ $money($row['est_commission'] ?? null) }}</td><td>{{ $value($row['roi'] ?? null, 2) }}</td>
                    @elseif ($selectedReport === 'content') <td>{{ $row['content_title'] ?? '—' }}</td><td>{{ $row['affiliate_name'] ?? '—' }}</td><td>{{ $row['channel'] ?? '—' }}</td><td>{{ $value($row['views'] ?? null) }}</td><td>{{ $value($row['likes'] ?? null) }}</td><td>{{ $value($row['comments'] ?? null) }}</td><td>{{ $money($row['sales'] ?? null) }}</td><td>{{ $value($row['orders'] ?? null) }}</td>
                    @elseif ($selectedReport === 'open_campaign') <td>{{ $row['item_name'] ?? '—' }}<br><span class="ams-muted">#{{ $row['item_id'] ?? '—' }}</span></td><td>{{ $value($row['affiliates'] ?? null) }}</td><td>{{ $money($row['sales'] ?? null) }}</td><td>{{ $value($row['item_sold'] ?? null) }}</td><td>{{ $money($row['est_commission'] ?? null) }}</td>
                    @elseif ($selectedReport === 'targeted_campaign') <td>{{ $row['campaign_name'] ?? '—' }}<br><span class="ams-muted">#{{ $row['campaign_id'] ?? '—' }}</span></td><td>{{ $value($row['affiliates'] ?? null) }}</td><td>{{ $money($row['sales'] ?? null) }}</td><td>{{ $value($row['item_sold'] ?? null) }}</td><td>{{ $money($row['est_commission'] ?? null) }}</td>
                    @elseif ($selectedReport === 'conversion') <td>{{ $row['order_sn'] ?? '—' }}</td><td>{{ $row['order_status'] ?? '—' }}<br><span class="ams-muted">{{ $row['verified_status'] ?? '' }}</span></td><td>{{ $row['affiliate_name'] ?? '—' }}</td><td>{{ $row['channel'] ?? '—' }}</td><td>{{ count((array) ($row['items'] ?? [])) }}</td><td>{{ $money($row['purchase_value'] ?? null) }}</td><td>{{ $money($row['order_brand_commission'] ?? null) }}</td><td>{{ $money(data_get($row, 'items.0.seller_service_fee')) }}</td>
                    @endif
                    </tr>
                @empty <tr><td colspan="10"><div class="ams-empty"><i class="bi bi-bar-chart"></i><strong>Belum ada data untuk filter ini</strong><span>Shopee tidak mengembalikan data. Coba ubah report, periode, atau filter lanjutan.</span></div></td></tr>@endforelse
            </tbody></table></div></div>
        @endif
    @elseif (!$loaded && !$stores->isEmpty())
        <div class="ams-card">
            <div class="card-body ams-empty">
                <i class="bi bi-bar-chart-line"></i>
                <strong>Siap membaca data AMS</strong>
                <span>Pilih koneksi toko, jenis report, dan periode lalu klik <em>Jalankan report</em>.</span>
            </div>
        </div>
    @endif
</div>

<script>
(() => {
    const report = document.getElementById('amsReport');
    const channel = document.getElementById('amsChannel');
    const performance = document.querySelectorAll('.performance-filter');
    const conversion = document.querySelectorAll('.conversion-filter');
    const sync = () => {
        const value = report?.value || 'overview';
        const isPerformance = !['conversion', 'validation'].includes(value);
        performance.forEach(el => el.style.display = isPerformance ? '' : 'none');
        conversion.forEach(el => el.style.display = value === 'conversion' ? '' : 'none');
        if (channel) {
            const isContent = value === 'content';
            [...channel.options].forEach(option => {
                option.hidden = isContent && !['ShopeeVideo', 'LiveStreaming'].includes(option.value);
            });
            if (isContent && !['ShopeeVideo', 'LiveStreaming'].includes(channel.value)) channel.value = 'ShopeeVideo';
        }
    };
    report?.addEventListener('change', sync);
    sync();
})();
</script>
