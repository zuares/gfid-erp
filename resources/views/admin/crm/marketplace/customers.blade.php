@extends('layouts.app')
@section('title', 'Customer Intelligence | Marketplace')
@push('head') @include('admin.crm.marketplace._styles') @endpush

@section('content')
<div class="container-fluid py-3 mpcrm-segments-page">
    @include('admin.crm.marketplace._nav')

    <div class="mpcrm-page-header mb-4">
        <div>
            <div class="mpcrm-eyebrow"><span class="mpcrm-eyebrow-mark"></span>Customer intelligence <span>·</span> Marketplace</div>
            <h1 class="mpcrm-page-title">Customer <span>command center</span></h1>
            <p class="mpcrm-page-description">Pusat analisa customer unik berdasarkan username marketplace untuk membaca nilai pelanggan, repeat purchase, kesehatan recency, dan prioritas campaign.</p>
            <div class="mpcrm-context-note mt-2"><i class="bi bi-calendar-check me-1"></i>Basis recency {{ \Carbon\Carbon::parse($analysisDate)->format('d M Y') }} · {{ number_format($totalCustomers) }} username customer dalam scope</div>
        </div>
        <div class="mpcrm-page-context">
            <form method="GET" class="mpcrm-scope-form">
                <label for="customerStore" class="mpcrm-field-label mb-1">Analytical scope</label>
                <select id="customerStore" name="store_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">Semua toko</option>
                    @foreach($stores as $store)
                        <option value="{{ $store->id }}" @selected($storeId === $store->id)>{{ $store->name }}</option>
                    @endforeach
                </select>
            </form>
            <span class="mpcrm-context-badge"><i class="bi bi-shield-check"></i>Decision-ready view</span>
        </div>
    </div>

    <section class="mpcrm-executive-grid mpcrm-segment-kpis mb-4" aria-label="Executive metrics customer">
        <div class="mpcrm-kpi-card mpcrm-kpi-primary"><div class="mpcrm-kpi-icon"><i class="bi bi-people"></i></div><div><div class="mpcrm-kpi-label">Customer base</div><div class="mpcrm-kpi-value">{{ number_format($totalCustomers) }}</div><div class="mpcrm-kpi-note">username marketplace unik</div></div></div>
        <div class="mpcrm-kpi-card mpcrm-kpi-success"><div class="mpcrm-kpi-icon"><i class="bi bi-wallet2"></i></div><div><div class="mpcrm-kpi-label">Lifetime revenue</div><div class="mpcrm-kpi-value">Rp{{ number_format($totalRevenue,0,',','.') }}</div><div class="mpcrm-kpi-note">total belanja tercatat</div></div></div>
        <div class="mpcrm-kpi-card mpcrm-kpi-purple"><div class="mpcrm-kpi-icon"><i class="bi bi-arrow-repeat"></i></div><div><div class="mpcrm-kpi-label">Repeat rate</div><div class="mpcrm-kpi-value">{{ number_format($repeatRate,1,',','.') }}%</div><div class="mpcrm-kpi-note">{{ number_format($repeatCustomers) }} repeat customer</div></div></div>
        <div class="mpcrm-kpi-card mpcrm-kpi-danger"><div class="mpcrm-kpi-icon"><i class="bi bi-fire"></i></div><div><div class="mpcrm-kpi-label">Revenue at risk</div><div class="mpcrm-kpi-value">Rp{{ number_format($revenueAtRisk,0,',','.') }}</div><div class="mpcrm-kpi-note">{{ number_format($revenueAtRiskShare,1,',','.') }}% dari total revenue</div></div></div>
        <div class="mpcrm-kpi-card mpcrm-kpi-coverage"><div class="mpcrm-kpi-icon"><i class="bi bi-bullseye"></i></div><div><div class="mpcrm-kpi-label">Customer terpetakan</div><div class="mpcrm-kpi-value">{{ number_format($totalCustomers) }}</div><div class="mpcrm-kpi-note">Seluruh histori customer dalam scope</div></div></div>
    </section>

    <section class="mpcrm-executive-callout mb-4">
        <div class="mpcrm-callout-icon"><i class="bi bi-stars"></i></div>
        <div class="flex-grow-1"><div class="mpcrm-callout-kicker">Executive readout</div><div class="mpcrm-callout-title">{{ $topRevenueSegment['label'] ?? 'Customer portfolio' }} menjadi kontributor revenue terbesar.</div><div class="mpcrm-callout-copy">Gunakan customer yang masih engaged untuk repeat purchase, sambil menjalankan win-back terukur untuk revenue yang mulai berisiko.</div></div>
        <div class="mpcrm-callout-metric"><span>Avg customer value</span><strong>Rp{{ number_format($averageCustomerValue,0,',','.') }}</strong><small>Avg order Rp{{ number_format($averageOrderValue,0,',','.') }}</small></div>
    </section>

    <div class="row g-3 mb-4">
        <div class="col-12 col-xl-8">
            <section class="mpcrm-enterprise-card h-100">
                <div class="mpcrm-enterprise-head"><div><div class="mpcrm-section-kicker"><i class="bi bi-grid-3x3-gap"></i> Portfolio composition</div><h2 class="mpcrm-enterprise-title">Segment performance matrix</h2><p class="mpcrm-enterprise-subtitle">Berdasarkan seluruh histori order dan total belanja, tanpa batas recency.</p></div><span class="mpcrm-card-badge">{{ $matrixSegmentMetrics->where('count','>',0)->count() }} active segments</span></div>
                <div class="mpcrm-segment-matrix">
                    @foreach($matrixSegmentMetrics->sortByDesc('revenue') as $segmentItem)
                        <div class="mpcrm-segment-row" style="--segment-color:{{ $segmentItem['color'] }};--segment-bg:{{ $segmentItem['bg'] }};">
                            <div class="mpcrm-segment-icon"><i class="bi {{ $segmentItem['icon'] }}"></i></div>
                            <div class="mpcrm-segment-main"><div class="mpcrm-segment-row-head"><strong>{{ $segmentItem['label'] }}</strong><span>{{ number_format($segmentItem['share'],1,',','.') }}% base</span></div><div class="mpcrm-segment-track"><span style="width:{{ min(100, max(2, $segmentItem['revenue_share'])) }}%;"></span></div><div class="mpcrm-segment-row-foot"><span>{{ $segmentItem['desc'] }}</span><b>{{ number_format($segmentItem['revenue_share'],1,',','.') }}% revenue</b></div></div>
                            <div class="mpcrm-segment-total"><strong>{{ number_format($segmentItem['count']) }}</strong><span>customer</span><small>Rp{{ number_format($segmentItem['revenue'],0,',','.') }}</small></div>
                        </div>
                    @endforeach
                </div>
            </section>
        </div>
        <div class="col-12 col-xl-4">
            <section class="mpcrm-enterprise-card h-100">
                <div class="mpcrm-enterprise-head"><div><div class="mpcrm-section-kicker"><i class="bi bi-clock-history"></i> Recency health</div><h2 class="mpcrm-enterprise-title">Engagement decay</h2><p class="mpcrm-enterprise-subtitle">Seluruh customer tetap dihitung; usia order hanya menjadi indikator distribusi.</p></div><span class="mpcrm-card-badge">{{ number_format($totalCustomers) }} terpetakan</span></div>
                <div class="mpcrm-recency-list">
                    @foreach($recencyBuckets as $bucket)
                        <div class="mpcrm-recency-item"><div class="mpcrm-recency-head"><span><i style="background:{{ $bucket['color'] }}"></i>{{ $bucket['label'] }}</span><b>{{ number_format($bucket['count']) }}</b></div><div class="mpcrm-recency-track"><span style="width:{{ $totalCustomers ? ($bucket['count'] / $totalCustomers) * 100 : 0 }}%;background:{{ $bucket['color'] }}"></span></div><div class="mpcrm-recency-meta"><span>{{ $totalCustomers ? number_format(($bucket['count'] / $totalCustomers) * 100,1,',','.') : '0,0' }}% customer</span><span>Rp{{ number_format($bucket['revenue'],0,',','.') }}</span></div></div>
                    @endforeach
                </div>
                <div class="mpcrm-panel-note"><i class="bi bi-info-circle me-1"></i>Tidak ada customer yang dikeluarkan dari analitik. Bucket 91+ hari dapat dipakai sebagai trigger campaign reactivation.</div>
            </section>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-12 col-lg-5">
            <section class="mpcrm-enterprise-card h-100">
                <div class="mpcrm-enterprise-head"><div><div class="mpcrm-section-kicker"><i class="bi bi-cash-stack"></i> Monetary analysis</div><h2 class="mpcrm-enterprise-title">Value tier distribution</h2></div><span class="mpcrm-card-badge">RFM monetary</span></div>
                <div class="mpcrm-value-tier-list">
                    @foreach($valueTiers as $tier)
                        <div class="mpcrm-tier-row"><div class="mpcrm-tier-dot" style="background:{{ $tier['color'] }}"></div><div class="flex-grow-1"><div class="mpcrm-tier-head"><strong>{{ $tier['label'] }}</strong><span>{{ number_format($tier['count']) }}</span></div><div class="mpcrm-tier-range">{{ $tier['range'] }}</div></div><b>Rp{{ number_format($tier['revenue'],0,',','.') }}</b></div>
                    @endforeach
                </div>
                <div class="mpcrm-panel-note"><i class="bi bi-lightbulb me-1"></i>Tier Premium cocok untuk VIP service, bundle premium, dan referral program.</div>
            </section>
        </div>
        <div class="col-12 col-lg-7">
            <section class="mpcrm-enterprise-card h-100">
                <div class="mpcrm-enterprise-head"><div><div class="mpcrm-section-kicker"><i class="bi bi-geo-alt"></i> Geographic distribution</div><h2 class="mpcrm-enterprise-title">Top province</h2><p class="mpcrm-enterprise-subtitle">Wilayah dengan customer terbanyak pada scope aktif.</p></div><span class="mpcrm-card-badge">{{ $topProvinces->count() }} wilayah</span></div>
                <div class="mpcrm-recency-list">
                    @forelse($topProvinces as $provinceItem)
                        <div class="mpcrm-recency-item"><div class="mpcrm-recency-head"><span><i style="background:#7c3aed"></i>{{ $provinceItem['label'] }}</span><b>{{ number_format($provinceItem['count']) }}</b></div><div class="mpcrm-recency-track"><span style="width:{{ $totalCustomers ? ($provinceItem['count'] / $totalCustomers) * 100 : 0 }}%;background:#7c3aed"></span></div><div class="mpcrm-recency-meta"><span>{{ $totalCustomers ? number_format(($provinceItem['count'] / $totalCustomers) * 100,1,',','.') : '0,0' }}% dari base</span><span>Prioritas regional</span></div></div>
                    @empty
                        <div class="mpcrm-empty-analytics">Belum ada data provinsi.</div>
                    @endforelse
                </div>
            </section>
        </div>
    </div>

    @php
        $hasAdvancedFilters = $segment !== '' || $orderAge !== '' || $paidRange !== '' || $city !== '' || $province !== '';
    @endphp
    <section class="mpcrm-filter-card mpcrm-card mb-4">
        <div class="mpcrm-filter-head"><div><div class="mpcrm-section-kicker"><i class="bi bi-funnel"></i> Customer explorer</div><h2 class="mpcrm-filter-title">Cari dan filter customer</h2></div><div class="mpcrm-filter-meta"><span class="mpcrm-filter-count"><i class="bi bi-people"></i>{{ number_format($totalCustomers) }} hasil</span><span class="mpcrm-filter-hint">Filter otomatis saat pilihan berubah</span></div></div>
        <form method="GET" action="{{ route('admin.crm.marketplace.customers') }}" id="mpcrmCustomerFilterForm" class="row g-2 align-items-end">
            <div class="col-12 col-lg-5"><label class="mpcrm-field-label">Cari customer</label><div class="mpcrm-input-wrap"><i class="bi bi-search"></i><input name="q" value="{{ $search }}" class="form-control" placeholder="Username, nama, telepon, kota, atau provinsi…" autocomplete="off"></div></div>
            <div class="col-6 col-lg-2"><label class="mpcrm-field-label">Data per halaman</label><select name="per_page" class="form-select"><option value="25" @selected($perPage === 25)>25 data</option><option value="50" @selected($perPage === 50)>50 data</option><option value="100" @selected($perPage === 100)>100 data</option></select></div>
            <div class="col-6 col-lg-2"><button class="mpcrm-apply-btn w-100"><i class="bi bi-search me-1"></i>Terapkan</button></div>
            <div class="col-6 col-lg-2"><a href="{{ route('admin.crm.marketplace.customers', $storeId ? ['store_id'=>$storeId] : []) }}" class="mpcrm-reset-btn w-100 text-decoration-none"><i class="bi bi-arrow-counterclockwise me-1"></i>Reset</a></div>
            <div class="col-12"><button type="button" class="mpcrm-filter-toggle {{ $hasAdvancedFilters ? 'is-open' : '' }}" data-filter-advanced-toggle aria-expanded="{{ $hasAdvancedFilters ? 'true' : 'false' }}"><i class="bi bi-sliders2 me-1"></i>Filter lanjutan <span class="mpcrm-filter-toggle-note">Segment, recency, value, dan lokasi</span><i class="bi bi-chevron-down ms-auto"></i></button></div>
            <div class="col-12 mpcrm-advanced-filters" data-filter-advanced @if(!$hasAdvancedFilters) hidden @endif><div class="row g-2">
                <div class="col-6 col-md-3"><label class="mpcrm-field-label">Segment</label><select name="segment" class="form-select"><option value="">Semua segment</option>@foreach($segmentDefinitions as $key => $definition)<option value="{{ $key }}" @selected($segment === $key)>{{ $definition['label'] }}</option>@endforeach</select></div>
                <div class="col-6 col-md-3"><label class="mpcrm-field-label">Usia order terakhir</label><select name="order_age" class="form-select"><option value="">Semua usia</option><option value="0_30" @selected($orderAge === '0_30')>0–30 hari</option><option value="31_90" @selected($orderAge === '31_90')>31–90 hari</option><option value="91_180" @selected($orderAge === '91_180')>91–180 hari</option><option value="181_plus" @selected($orderAge === '181_plus')>181+ hari</option></select></div>
                <div class="col-6 col-md-2"><label class="mpcrm-field-label">Total belanja</label><select name="paid_range" class="form-select"><option value="">Semua nominal</option><option value="under_100k" @selected($paidRange === 'under_100k')>Di bawah Rp100 ribu</option><option value="100k_300k" @selected($paidRange === '100k_300k')>Rp100–300 ribu</option><option value="300k_plus" @selected($paidRange === '300k_plus')>Di atas Rp300 ribu</option></select></div>
                <div class="col-6 col-md-2"><label class="mpcrm-field-label">Kota</label><select name="city" class="form-select"><option value="">Semua kota</option>@foreach($cities as $option)<option value="{{ $option }}" @selected($city === $option)>{{ $option }}</option>@endforeach</select></div>
                <div class="col-6 col-md-2"><label class="mpcrm-field-label">Provinsi</label><select name="province" class="form-select"><option value="">Semua provinsi</option>@foreach($provinces as $option)<option value="{{ $option }}" @selected($province === $option)>{{ $option }}</option>@endforeach</select></div>
            </div></div>
            @if($storeId)<input type="hidden" name="store_id" value="{{ $storeId }}">@endif
        </form>
    </section>

    @php
        $sortUrl = fn ($column) => request()->fullUrlWithQuery(['sort' => $column, 'direction' => $sort === $column && $direction === 'asc' ? 'desc' : 'asc', 'page' => 1]);
        $sortIcon = fn ($column) => $sort === $column ? ($direction === 'asc' ? 'bi-sort-up' : 'bi-sort-down') : 'bi-arrow-down-up';
    @endphp
    <section class="mpcrm-data-card">
        <div class="mpcrm-data-card-head"><div><div class="mpcrm-section-kicker"><i class="bi bi-table"></i> Customer directory</div><h2 class="mpcrm-panel-title">Daftar customer marketplace</h2></div><div class="mpcrm-data-card-meta"><strong>{{ number_format($customers->total()) }} customer</strong><span class="mpcrm-data-divider"></span><span>Urut berdasarkan {{ $sort === 'total' ? 'total belanja' : str_replace('_', ' ', $sort) }}</span></div></div>
        <div class="table-responsive"><table class="table mb-0 mpcrm-table"><thead><tr>
            <th><a class="mpcrm-sort {{ $sort === 'customer' ? 'active' : '' }}" href="{{ $sortUrl('customer') }}">Customer <i class="bi {{ $sortIcon('customer') }}"></i></a></th>
            <th><a class="mpcrm-sort {{ $sort === 'location' ? 'active' : '' }}" href="{{ $sortUrl('location') }}">Lokasi <i class="bi {{ $sortIcon('location') }}"></i></a></th>
            <th><a class="mpcrm-sort {{ $sort === 'segment' ? 'active' : '' }}" href="{{ $sortUrl('segment') }}">Segment <i class="bi {{ $sortIcon('segment') }}"></i></a></th>
            <th><a class="mpcrm-sort {{ $sort === 'orders' ? 'active' : '' }}" href="{{ $sortUrl('orders') }}">Order <i class="bi {{ $sortIcon('orders') }}"></i></a></th>
            <th><a class="mpcrm-sort {{ $sort === 'total' ? 'active' : '' }}" href="{{ $sortUrl('total') }}">Total belanja <i class="bi {{ $sortIcon('total') }}"></i></a></th>
            <th><a class="mpcrm-sort {{ $sort === 'last_order' ? 'active' : '' }}" href="{{ $sortUrl('last_order') }}">Order terakhir <i class="bi {{ $sortIcon('last_order') }}"></i></a></th>
            <th>Aksi</th>
        </tr></thead><tbody>
        @forelse($customers as $customer)
            @php
                $customerSegment = $segmentDefinitions[$customer->segment] ?? null;
            @endphp
            <tr>
                <td><div class="fw-semibold">{{ $customer->name ?: 'Buyer Marketplace' }}</div><div class="mpcrm-sub">{{ $customer->buyer_username ? '@'.$customer->buyer_username : 'Username tidak tersedia' }} · {{ $customer->phone ?: 'Telepon tidak ada' }}</div></td>
                <td>{{ $customer->city ?: '-' }}<div class="mpcrm-sub">{{ $customer->province ?: '-' }}</div></td>
                <td>@if($customerSegment)<span class="mpcrm-pill" style="background:{{ $customerSegment['bg'] }};color:{{ $customerSegment['color'] }};"><i class="bi {{ $customerSegment['icon'] }} me-1"></i>{{ $customerSegment['label'] }}</span>@else<span class="mpcrm-pill" style="background:#f1f5f9;color:#64748b;">Tidak terklasifikasi</span>@endif</td>
                <td><span class="mpcrm-pill" style="background:#eff6ff;color:#1d4ed8;">{{ number_format($customer->marketplace_order_count) }} order</span></td>
                <td class="fw-bold">Rp{{ number_format($customer->marketplace_total_spent,0,',','.') }}</td>
                <td>{{ $customer->last_marketplace_order_at ? \Carbon\Carbon::parse($customer->last_marketplace_order_at)->format('d M Y') : '-' }}<div class="mpcrm-sub">{{ \App\Http\Controllers\Admin\MarketplaceCrmController::formatElapsedDays((int) $customer->days_since_last_order) }}</div></td>
                <td><a class="mpcrm-row-action" title="Buka segment customer" href="{{ route('admin.crm.marketplace.segments.show', array_merge(['segment'=>$customer->segment], $storeId ? ['store_id'=>$storeId] : [])) }}"><i class="bi bi-arrow-up-right"></i></a></td>
            </tr>
        @empty
            <tr><td colspan="7" class="text-center text-secondary py-5"><i class="bi bi-search d-block fs-4 mb-2"></i>Tidak ada customer yang cocok dengan filter.</td></tr>
        @endforelse
        </tbody></table></div>
        <div class="mpcrm-pagination p-3">{{ $customers->links() }}</div>
    </section>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('mpcrmCustomerFilterForm');
    const search = form?.querySelector('input[name="q"]');
    const advancedToggle = form?.querySelector('[data-filter-advanced-toggle]');
    const advanced = form?.querySelector('[data-filter-advanced]');
    let timer = null;
    advancedToggle?.addEventListener('click', function () {
        if (!advanced) return;
        const open = advanced.hidden;
        advanced.hidden = !open;
        advancedToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        advancedToggle.classList.toggle('is-open', open);
    });
    form?.querySelectorAll('select').forEach(select => select.addEventListener('change', () => form.submit()));
    search?.addEventListener('input', function () {
        clearTimeout(timer);
        timer = setTimeout(() => form.submit(), 450);
    });
    search?.addEventListener('keydown', function (event) {
        if (event.key === 'Enter') { event.preventDefault(); clearTimeout(timer); form.submit(); }
    });
});
</script>
@endsection
