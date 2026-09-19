@extends('layouts.app')
@section('title', 'Customer Intelligence | Marketplace')
@push('head') @include('admin.crm.marketplace._styles') @endpush

@section('content')
<div class="container-fluid py-3 mpcrm-segments-page">
    @include('admin.crm.marketplace._nav')

    <div class="mpcrm-page-header mpcrm-segments-header mb-4">
        <div>
            <div class="mpcrm-eyebrow"><span class="mpcrm-eyebrow-mark"></span>Customer intelligence <span>·</span> Marketplace</div>
            <h1 class="mpcrm-page-title">Segment <span>command center</span></h1>
            <p class="mpcrm-page-description">Analisa customer unik berdasarkan username marketplace untuk membaca kualitas revenue, risiko churn, dan peluang campaign berbasis recency, frequency, dan monetary value.</p>
            @if($analysisDate)
                <div class="mpcrm-context-note mt-2"><i class="bi bi-calendar-check me-1"></i>Basis recency {{ \Carbon\Carbon::parse($analysisDate)->format('d M Y') }} · {{ number_format($totalCustomers) }} username customer terpetakan</div>
            @endif
        </div>
        <div class="mpcrm-page-context">
            <form method="GET" class="mpcrm-scope-form">
                <label for="segmentStore" class="mpcrm-field-label mb-1">Analytical scope</label>
                <select id="segmentStore" name="store_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">Semua toko</option>
                    @foreach($stores as $store)
                        <option value="{{ $store->id }}" @selected($storeId === $store->id)>{{ $store->name }}</option>
                    @endforeach
                </select>
            </form>
            <span class="mpcrm-context-badge"><i class="bi bi-shield-check"></i>Decision-ready view</span>
        </div>
    </div>

    <section class="mpcrm-executive-grid mpcrm-segment-kpis mb-4" aria-label="Executive metrics">
        <div class="mpcrm-kpi-card mpcrm-kpi-primary"><div class="mpcrm-kpi-icon"><i class="bi bi-people"></i></div><div><div class="mpcrm-kpi-label">Customer base</div><div class="mpcrm-kpi-value">{{ number_format($totalCustomers) }}</div><div class="mpcrm-kpi-note">username marketplace unik</div></div></div>
        <div class="mpcrm-kpi-card mpcrm-kpi-success"><div class="mpcrm-kpi-icon"><i class="bi bi-wallet2"></i></div><div><div class="mpcrm-kpi-label">Lifetime revenue</div><div class="mpcrm-kpi-value">Rp{{ number_format($totalRevenue,0,',','.') }}</div><div class="mpcrm-kpi-note">total belanja tercatat</div></div></div>
        <div class="mpcrm-kpi-card mpcrm-kpi-purple"><div class="mpcrm-kpi-icon"><i class="bi bi-arrow-repeat"></i></div><div><div class="mpcrm-kpi-label">Repeat rate</div><div class="mpcrm-kpi-value">{{ number_format($repeatRate,1,',','.') }}%</div><div class="mpcrm-kpi-note">{{ number_format($repeatCustomers) }} repeat customer</div></div></div>
        <div class="mpcrm-kpi-card mpcrm-kpi-danger"><div class="mpcrm-kpi-icon"><i class="bi bi-fire"></i></div><div><div class="mpcrm-kpi-label">Revenue at risk</div><div class="mpcrm-kpi-value">Rp{{ number_format($revenueAtRisk,0,',','.') }}</div><div class="mpcrm-kpi-note">{{ number_format($revenueAtRiskShare,1,',','.') }}% dari total revenue</div></div></div>
        <div class="mpcrm-kpi-card mpcrm-kpi-coverage"><div class="mpcrm-kpi-icon"><i class="bi bi-bullseye"></i></div><div><div class="mpcrm-kpi-label">Engaged ≤90 hari</div><div class="mpcrm-kpi-value">{{ number_format($engagedCustomers) }}</div><div class="mpcrm-kpi-note">{{ $totalCustomers ? number_format(($engagedCustomers / $totalCustomers) * 100,1,',','.') : '0,0' }}% dari base</div></div></div>
    </section>

    <section class="mpcrm-executive-callout mb-4">
        <div class="mpcrm-callout-icon"><i class="bi bi-stars"></i></div>
        <div class="flex-grow-1"><div class="mpcrm-callout-kicker">Executive readout</div><div class="mpcrm-callout-title">{{ $topRevenueSegment['label'] ?? 'Customer portfolio' }} menjadi kontributor revenue terbesar.</div><div class="mpcrm-callout-copy">Prioritaskan budget campaign pada customer yang masih engaged, sambil menjalankan win-back terukur untuk mengamankan revenue berisiko.</div></div>
        <div class="mpcrm-callout-metric"><span>Avg customer value</span><strong>Rp{{ number_format($averageCustomerValue,0,',','.') }}</strong><small>Avg order Rp{{ number_format($averageOrderValue,0,',','.') }}</small></div>
    </section>

    <div class="row g-3 mb-4">
        <div class="col-12 col-xl-8">
            <section class="mpcrm-enterprise-card h-100">
                <div class="mpcrm-enterprise-head"><div><div class="mpcrm-section-kicker"><i class="bi bi-grid-3x3-gap"></i> Portfolio composition</div><h2 class="mpcrm-enterprise-title">Segment performance matrix</h2><p class="mpcrm-enterprise-subtitle">Ukuran bubble menunjukkan customer base; bar menunjukkan kontribusi revenue.</p></div><span class="mpcrm-card-badge">{{ $overview->where('count','>',0)->count() }} active segments</span></div>
                <div class="mpcrm-segment-matrix">
                    @foreach($overview->sortByDesc('revenue') as $segment)
                        <a href="{{ route('admin.crm.marketplace.segments.show', array_merge(['segment'=>$segment['key']], $storeId ? ['store_id'=>$storeId] : [])) }}" class="mpcrm-segment-row" style="--segment-color:{{ $segment['color'] }};--segment-bg:{{ $segment['bg'] }};">
                            <div class="mpcrm-segment-icon"><i class="bi {{ $segment['icon'] }}"></i></div>
                            <div class="mpcrm-segment-main"><div class="mpcrm-segment-row-head"><strong>{{ $segment['label'] }}</strong><span>{{ number_format($segment['share'],1,',','.') }}% base</span></div><div class="mpcrm-segment-track"><span style="width:{{ min(100, max(2, $segment['revenue_share'])) }}%;"></span></div><div class="mpcrm-segment-row-foot"><span>{{ $segment['desc'] }}</span><b>{{ number_format($segment['revenue_share'],1,',','.') }}% revenue</b></div></div>
                            <div class="mpcrm-segment-total"><strong>{{ number_format($segment['count']) }}</strong><span>customer</span><small>Rp{{ number_format($segment['revenue'],0,',','.') }}</small></div><i class="bi bi-chevron-right mpcrm-segment-arrow"></i>
                        </a>
                    @endforeach
                </div>
            </section>
        </div>
        <div class="col-12 col-xl-4">
            <section class="mpcrm-enterprise-card h-100">
                <div class="mpcrm-enterprise-head"><div><div class="mpcrm-section-kicker"><i class="bi bi-clock-history"></i> Recency health</div><h2 class="mpcrm-enterprise-title">Engagement decay</h2></div><span class="mpcrm-card-badge">{{ number_format($engagedCustomers) }} engaged</span></div>
                <div class="mpcrm-recency-list">
                    @foreach($recencyBuckets as $bucket)
                        <div class="mpcrm-recency-item"><div class="mpcrm-recency-head"><span><i style="background:{{ $bucket['color'] }}"></i>{{ $bucket['label'] }}</span><b>{{ number_format($bucket['count']) }}</b></div><div class="mpcrm-recency-track"><span style="width:{{ $totalCustomers ? ($bucket['count'] / $totalCustomers) * 100 : 0 }}%;background:{{ $bucket['color'] }}"></span></div><div class="mpcrm-recency-meta"><span>{{ $totalCustomers ? number_format(($bucket['count'] / $totalCustomers) * 100,1,',','.') : '0,0' }}% customer</span><span>Rp{{ number_format($bucket['revenue'],0,',','.') }}</span></div></div>
                    @endforeach
                </div>
                <div class="mpcrm-panel-note"><i class="bi bi-info-circle me-1"></i>Gunakan bucket 91+ hari sebagai trigger campaign reactivation.</div>
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
                <div class="mpcrm-enterprise-head"><div><div class="mpcrm-section-kicker"><i class="bi bi-lightning-charge"></i> Decision support</div><h2 class="mpcrm-enterprise-title">Recommended actions</h2></div><span class="mpcrm-card-badge">Prioritized by impact</span></div>
                <div class="mpcrm-insight-grid">
                    @foreach($insights as $insight)
                        <a href="{{ $insight['href'] }}" class="mpcrm-insight-card mpcrm-insight-{{ $insight['tone'] }}"><div class="mpcrm-insight-icon"><i class="bi {{ $insight['icon'] }}"></i></div><div class="mpcrm-insight-body"><div class="mpcrm-insight-label">{{ $insight['label'] }}</div><strong>{{ $insight['format'] === 'currency' ? 'Rp'.number_format($insight['value'],0,',','.') : number_format($insight['value']) }}</strong><p>{{ $insight['body'] }}</p><span>{{ $insight['cta'] }} <i class="bi bi-arrow-up-right"></i></span></div></a>
                    @endforeach
                </div>
            </section>
        </div>
    </div>

    <section class="mpcrm-enterprise-card mpcrm-action-queue">
        <div class="mpcrm-enterprise-head"><div><div class="mpcrm-section-kicker"><i class="bi bi-kanban"></i> Campaign workbench</div><h2 class="mpcrm-enterprise-title">Action queue by segment</h2><p class="mpcrm-enterprise-subtitle">Klik untuk membuka daftar customer yang siap ditindaklanjuti.</p></div><a class="mpcrm-outline-action" href="{{ route('admin.crm.marketplace.prospects', $storeId ? ['store_id'=>$storeId] : []) }}"><i class="bi bi-whatsapp me-1"></i>Open prospect workspace</a></div>
        <div class="mpcrm-action-grid">
            @foreach(['at_risk','lost','promising'] as $priorityKey)
                @php($priority = $overview->firstWhere('key', $priorityKey))
                @if($priority)
                    <a href="{{ route('admin.crm.marketplace.segments.show', array_merge(['segment'=>$priorityKey], $storeId ? ['store_id'=>$storeId] : [])) }}" class="mpcrm-action-card" style="--segment-color:{{ $priority['color'] }};--segment-bg:{{ $priority['bg'] }};"><div class="mpcrm-action-card-top"><span class="mpcrm-priority-dot"></span><span>{{ $priority['label'] }}</span><i class="bi bi-arrow-up-right"></i></div><strong>{{ number_format($priority['count']) }} customer</strong><div class="mpcrm-action-revenue">Revenue base Rp{{ number_format($priority['revenue'],0,',','.') }}</div><div class="mpcrm-action-copy">{{ $priority['action'] }}</div></a>
                @endif
            @endforeach
        </div>
    </section>
</div>
@endsection
