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
    .ams-page { max-width: 1540px; margin: 0 auto; padding: 1.25rem; }
    .ams-hero { color: #fff; border-radius: 20px; padding: 1.5rem; background: linear-gradient(135deg, #312e81, #7c3aed 55%, #c026d3); box-shadow: 0 18px 40px rgba(109, 40, 217, .2); }
    .ams-card { border: 1px solid #e5e7eb; border-radius: 16px; background: var(--bs-body-bg, #fff); box-shadow: 0 8px 24px rgba(15, 23, 42, .05); }
    .ams-card .card-body { padding: 1.15rem; }
    .ams-label { color: #64748b; font-size: .7rem; font-weight: 800; letter-spacing: .045em; text-transform: uppercase; }
    .ams-muted { color: #64748b; font-size: .82rem; }
    .ams-kpi { font-size: 1.35rem; font-weight: 800; color: #111827; }
    .ams-table-wrap { overflow-x: auto; }
    .ams-table { min-width: 900px; }
    .ams-table th { color: #64748b; font-size: .68rem; letter-spacing: .04em; text-transform: uppercase; white-space: nowrap; }
    .ams-table td { vertical-align: middle; white-space: nowrap; }
    .ams-code { color: #6d28d9; font-size: .76rem; }
    .ams-filter-section { border-top: 1px solid #eef2f7; margin-top: 1rem; padding-top: 1rem; }
    @media (max-width: 768px) { .ams-page { padding: .75rem; } .ams-hero { padding: 1.1rem; } }
</style>

<div class="ams-page">
    <div class="ams-hero mb-4">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
            <div>
                <div class="small opacity-75 mb-1"><i class="bi bi-person-hearts me-1"></i> Shopee Affiliate Marketing Solutions</div>
                <h1 class="h3 fw-bold mb-2">Performance &amp; Report</h1>
                <p class="mb-0 opacity-75">Performa toko, produk, affiliate, campaign, konversi, dan validasi komisi AMS.</p>
            </div>
            <span class="badge rounded-pill text-bg-light text-primary px-3 py-2">Read-only</span>
        </div>
    </div>

    @if ($error)
        <div class="alert alert-danger ams-card border-0 mb-4"><i class="bi bi-exclamation-triangle me-2"></i>{{ $error }}</div>
    @endif

    @if ($stores->isEmpty())
        <div class="alert alert-warning ams-card border-0">Belum ada koneksi toko Shopee aktif. Hubungkan toko terlebih dahulu melalui menu Toko Online.</div>
    @else
        <form method="GET" class="ams-card mb-4">
            <div class="card-body">
                <input type="hidden" name="load" value="1">
                <div class="row g-3">
                    <div class="col-12 col-lg-3">
                        <label class="form-label ams-label">Koneksi Shopee</label>
                        <select class="form-select" name="store_id" required>
                            @foreach ($stores as $store)
                                <option value="{{ $store->id }}" @selected((string) $store->id === (string) ($filters['store_id'] ?? ''))>
                                    {{ $store->name }} (#{{ $store->external_shop_id ?: $store->id }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 col-lg-3">
                        <label class="form-label ams-label">Laporan</label>
                        <select class="form-select" name="report" id="amsReport" required>
                            @foreach ($reportLabels as $report => $label)
                                <option value="{{ $report }}" @selected($selectedReport === $report)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6 col-lg-2 performance-filter">
                        <label class="form-label ams-label">Periode</label>
                        <select class="form-select" name="period_type">
                            @foreach (['Last30d' => '30 hari terakhir', 'Last7d' => '7 hari terakhir', 'Day' => 'Harian', 'Week' => 'Mingguan', 'Month' => 'Bulanan'] as $period => $label)
                                <option value="{{ $period }}" @selected(($filters['period_type'] ?? 'Last30d') === $period)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6 col-lg-2">
                        <label class="form-label ams-label">Mulai</label>
                        <input type="date" class="form-control" name="date_from" value="{{ $filters['date_from'] ?? '' }}" required>
                    </div>
                    <div class="col-6 col-lg-2">
                        <label class="form-label ams-label">Sampai</label>
                        <input type="date" class="form-control" name="date_to" value="{{ $filters['date_to'] ?? '' }}" required>
                    </div>
                    <div class="col-6 col-lg-2 performance-filter">
                        <label class="form-label ams-label">Tipe order</label>
                        <select class="form-select" name="order_type">
                            <option value="ConfirmedOrder" @selected(($filters['order_type'] ?? '') === 'ConfirmedOrder')>Confirmed order</option>
                            <option value="PlacedOrder" @selected(($filters['order_type'] ?? '') === 'PlacedOrder')>Placed order</option>
                        </select>
                    </div>
                    <div class="col-6 col-lg-2 performance-filter">
                        <label class="form-label ams-label">Channel</label>
                        <select class="form-select" name="channel" id="amsChannel">
                            @foreach (['AllChannel' => 'Semua channel', 'SocialMedia' => 'Social media', 'ShopeeVideo' => 'Shopee Video', 'LiveStreaming' => 'Live streaming'] as $channel => $label)
                                <option value="{{ $channel }}" @selected(($filters['channel'] ?? 'AllChannel') === $channel)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6 col-lg-2">
                        <label class="form-label ams-label">Halaman</label>
                        <input type="number" class="form-control" name="page_no" min="1" max="500" value="{{ $filters['page_no'] ?? 1 }}">
                    </div>
                    <div class="col-6 col-lg-2">
                        <label class="form-label ams-label">Baris per halaman</label>
                        <select class="form-select" name="page_size">
                            @foreach ([20, 50, 100, 500] as $pageSize)
                                <option value="{{ $pageSize }}" @selected((int) ($filters['page_size'] ?? 20) === $pageSize)>{{ $pageSize }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="ams-filter-section">
                    <div class="ams-label mb-2">Filter tambahan</div>
                    <div class="row g-3">
                        <div class="col-6 col-lg-2 conversion-filter"><input class="form-control" name="order_sn" value="{{ $filters['order_sn'] ?? '' }}" placeholder="Order SN"></div>
                        <div class="col-6 col-lg-2 conversion-filter"><input class="form-control" name="item_name" value="{{ $filters['item_name'] ?? '' }}" placeholder="Nama item"></div>
                        <div class="col-6 col-lg-2"><input class="form-control" name="item_id" value="{{ $filters['item_id'] ?? '' }}" placeholder="Item ID"></div>
                        <div class="col-6 col-lg-2"><input class="form-control" name="affiliate_id" value="{{ $filters['affiliate_id'] ?? '' }}" placeholder="Affiliate ID"></div>
                        <div class="col-6 col-lg-2"><input class="form-control" name="campaign_id" value="{{ $filters['campaign_id'] ?? '' }}" placeholder="Campaign ID"></div>
                        <div class="col-6 col-lg-2 conversion-filter"><input class="form-control" name="l1_category_id" value="{{ $filters['l1_category_id'] ?? '' }}" placeholder="L1 category ID"></div>
                        <div class="col-6 col-lg-2 conversion-filter"><input class="form-control" name="l2_category_id" value="{{ $filters['l2_category_id'] ?? '' }}" placeholder="L2 category ID"></div>
                        <div class="col-6 col-lg-2 conversion-filter"><input class="form-control" name="l3_category_id" value="{{ $filters['l3_category_id'] ?? '' }}" placeholder="L3 category ID"></div>
                        <div class="col-6 col-lg-2 conversion-filter"><input class="form-control" name="order_status" value="{{ $filters['order_status'] ?? '' }}" placeholder="Order status"></div>
                        <div class="col-6 col-lg-2 conversion-filter"><input class="form-control" name="verified_status" value="{{ $filters['verified_status'] ?? '' }}" placeholder="Verified status"></div>
                        <div class="col-6 col-lg-2 conversion-filter"><input class="form-control" name="seller_campaign_type" value="{{ $filters['seller_campaign_type'] ?? '' }}" placeholder="Campaign type"></div>
                        <div class="col-6 col-lg-2 conversion-filter"><input class="form-control" name="deduction_status" value="{{ $filters['deduction_status'] ?? '' }}" placeholder="Deduction status"></div>
                        <div class="col-6 col-lg-2 conversion-filter"><input class="form-control" name="deduction_method" value="{{ $filters['deduction_method'] ?? '' }}" placeholder="Deduction method"></div>
                    </div>
                </div>

                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mt-3">
                    <div class="ams-muted">Tanggal AMS memakai format kalender Shopee; data terbaru bisa tertinggal dari hari ini.</div>
                    <button class="btn btn-primary px-4" type="submit"><i class="bi bi-cloud-download me-1"></i>Ambil Data</button>
                </div>
            </div>
        </form>
    @endif

    @if ($loaded && !$error)
        <div class="d-flex flex-wrap justify-content-between align-items-end gap-2 mb-3">
            <div>
                <h2 class="h5 fw-bold mb-1">{{ $reportLabels[$selectedReport] ?? 'AMS Report' }}</h2>
                <div class="ams-muted"><code class="ams-code">/api/v2/ams/{{ $endpointPaths[$selectedReport] ?? 'report' }}</code></div>
            </div>
            @if ($latestReportDate)<div class="ams-muted">Data AMS terakhir: <strong>{{ $latestReportDate }}</strong></div>@endif
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
                    <div class="col-6 col-xl-2"><div class="ams-card h-100"><div class="card-body"><div class="ams-label mb-2"><i class="bi {{ $icon }} me-1"></i>{{ $label }}</div><div class="ams-kpi">{{ $number }}</div></div></div></div>
                @endforeach
            </div>
            <div class="row g-3">
                @foreach ([['Open Campaign', $openMetrics], ['Targeted Campaign', $targetedMetrics]] as [$label, $metric])
                    <div class="col-12 col-lg-6"><div class="ams-card"><div class="card-body"><h3 class="h6 fw-bold mb-3">{{ $label }}</h3><div class="row g-3"><div class="col-4"><div class="ams-label">Affiliate</div><div class="ams-kpi">{{ $value($metric['affiliates'] ?? null) }}</div></div><div class="col-4"><div class="ams-label">Item sold</div><div class="ams-kpi">{{ $value($metric['items_sold'] ?? null) }}</div></div><div class="col-4"><div class="ams-label">Sales</div><div class="ams-kpi">{{ $money($metric['sales'] ?? null) }}</div></div></div></div></div></div>
                @endforeach
            </div>
        @elseif ($selectedReport === 'validation')
            <div class="row g-3">
                <div class="col-12 col-xl-5"><div class="ams-card"><div class="card-body"><h3 class="h6 fw-bold mb-3">Daftar validation bill</h3><div class="ams-table-wrap"><table class="table table-hover ams-table mb-0"><thead><tr><th>ID</th><th>Bulan</th><th>Sumber</th><th>Total</th></tr></thead><tbody>
                    @forelse ($validationList as $bill)
                        <tr><td><a href="{{ request()->fullUrlWithQuery(['validation_id' => $bill['validation_id'] ?? '', 'validation_month' => $bill['validation_month'] ?? '', 'campaign_source' => $bill['campaign_source'] ?? 'ShopeeManaged']) }}">{{ $bill['validation_id'] ?? '—' }}</a></td><td>{{ $bill['validation_month'] ?? '—' }}</td><td>{{ $bill['campaign_source'] ?? '—' }}</td><td>{{ $money(data_get($bill, 'online_bill.total_amount', data_get($bill, 'total_amount'))) }}</td></tr>
                    @empty <tr><td colspan="4" class="text-center text-secondary py-4">Belum ada validation bill.</td></tr>@endforelse
                </tbody></table></div></div></div></div>
                <div class="col-12 col-xl-7"><div class="ams-card"><div class="card-body"><h3 class="h6 fw-bold mb-3">Detail validation report</h3>@if ($filters['validation_id'] === '')<div class="ams-muted">Klik validation ID di sebelah kiri untuk mengambil detail.</div>@else<div class="ams-table-wrap"><table class="table table-hover ams-table mb-0"><thead><tr><th>Order</th><th>Status</th><th>Affiliate</th><th>Item</th><th>Commission</th><th>Service fee</th></tr></thead><tbody>
                    @forelse ($validationReport as $row)
                        @foreach ((array) ($row['items'] ?? [[]]) as $item)<tr><td>{{ $row['order_sn'] ?? '—' }}</td><td>{{ $row['verified_status'] ?? ($row['order_status'] ?? '—') }}</td><td>{{ $row['affiliate_name'] ?? '—' }}</td><td>{{ $item['item_name'] ?? '—' }}</td><td>{{ $money($item['item_brand_commission'] ?? null) }}</td><td>{{ $money($item['seller_service_fee'] ?? null) }}</td></tr>@endforeach
                    @empty <tr><td colspan="6" class="text-center text-secondary py-4">Tidak ada detail untuk filter ini.</td></tr>@endforelse
                </tbody></table></div>@endif</div></div></div>
            </div>
        @else
            <div class="ams-card"><div class="card-body"><div class="d-flex justify-content-between align-items-center mb-3"><div class="ams-muted">{{ $rows->count() }} baris ditampilkan · Total: {{ $value($response['total_count'] ?? null) }}</div><span class="badge text-bg-light">{{ !empty($response['has_more']) ? 'Masih ada halaman berikutnya' : 'Halaman terakhir' }}</span></div><div class="ams-table-wrap"><table class="table table-hover ams-table mb-0"><thead><tr>
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
                @empty <tr><td colspan="10" class="text-center text-secondary py-5">Shopee tidak mengembalikan data untuk filter ini.</td></tr>@endforelse
            </tbody></table></div></div></div>
        @endif
    @elseif (!$loaded && !$stores->isEmpty())
        <div class="ams-card"><div class="card-body text-center py-5"><i class="bi bi-bar-chart-line fs-1 text-primary"></i><h2 class="h5 fw-bold mt-3">Pilih filter untuk mulai</h2><p class="ams-muted mb-0">Modul ini hanya membaca data Performance &amp; Report AMS dari Shopee.</p></div></div>
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
