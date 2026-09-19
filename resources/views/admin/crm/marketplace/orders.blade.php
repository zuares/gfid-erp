@extends('layouts.app')
@section('title', 'Order Marketplace')
@push('head') @include('admin.crm.marketplace._styles') @endpush

@section('content')
<div class="container-fluid py-3 mpcrm-orders-page">
    @include('admin.crm.marketplace._nav')
    <div class="mpcrm-page-header mb-4"><div><div class="mpcrm-eyebrow"><span class="mpcrm-eyebrow-mark"></span>Marketplace CRM <span>/</span> Order operations</div><h1 class="mpcrm-page-title">Order Marketplace<span>.</span></h1><p class="mpcrm-page-description">Satu ruang kendali untuk memantau volume order, revenue, dan fulfillment marketplace.</p></div><div class="mpcrm-page-context"><span class="mpcrm-context-badge"><i class="bi bi-bar-chart-line"></i> Analytics view</span><span class="mpcrm-context-note">{{ number_format($analytics['totalOrders']) }} order terfilter</span></div></div>

    @php
        $activeFilterCount = collect([$search, $storeId, $status, $dateFrom || $dateTo])->filter(fn ($value) => $value !== null && $value !== '' && $value !== false)->count();
    @endphp
    <section class="mpcrm-toolbar mpcrm-filter-card mb-4" aria-label="Filter order marketplace"><div class="mpcrm-filter-head"><div><div class="mpcrm-section-kicker">Filter & segmentasi</div><h2 class="mpcrm-filter-title">Persempit data operasional</h2></div><div class="mpcrm-filter-meta"><span class="mpcrm-filter-count"><i class="bi bi-sliders2"></i>{{ $activeFilterCount }} aktif</span><span class="mpcrm-filter-hint">Semua filter memengaruhi KPI, chart, dan tabel</span></div></div><form method="GET" class="row g-3 align-items-end">
        <div class="col-xl-3 col-lg-6"><label class="mpcrm-field-label" for="mpSearch">Cari order atau customer</label><div class="mpcrm-input-wrap"><i class="bi bi-search"></i><input id="mpSearch" name="q" value="{{ $search }}" class="form-control form-control-sm" placeholder="Order ID, username, nama, telepon…"></div></div>
        <div class="col-xl-2 col-lg-6"><label class="mpcrm-field-label" for="mpStore">Toko</label><select id="mpStore" name="store_id" class="form-select form-select-sm"><option value="">Semua toko</option>@foreach($stores as $store)<option value="{{ $store->id }}" @selected($storeId === $store->id)>{{ $store->name }}</option>@endforeach</select></div>
        <div class="col-xl-2 col-lg-6"><label class="mpcrm-field-label" for="mpStatus">Status order</label><select id="mpStatus" name="status" class="form-select form-select-sm"><option value="">Semua status</option>@foreach(['new'=>'New','packed'=>'Packed','shipped'=>'Shipped','completed'=>'Completed','cancelled'=>'Cancelled'] as $key=>$label)<option value="{{ $key }}" @selected($status === $key)>{{ $label }} ({{ $statusCounts[$key] ?? 0 }})</option>@endforeach</select></div>
        <div class="col-xl-3 col-lg-6"><label class="mpcrm-field-label" for="mpOrderDateRange">Periode order</label><div class="position-relative"><i class="bi bi-calendar3 mpcrm-date-icon"></i><input id="mpOrderDateRange" class="form-control form-control-sm mpcrm-date-range" placeholder="Semua waktu" autocomplete="off"><input type="hidden" data-gf-date="off" name="date_from" id="mpOrderDateFrom" value="{{ $dateFrom }}"><input type="hidden" data-gf-date="off" name="date_to" id="mpOrderDateTo" value="{{ $dateTo }}"></div></div>
        <div class="col-xl-2 col-lg-12 d-flex gap-2"><button class="btn btn-sm mpcrm-apply-btn flex-fill"><i class="bi bi-funnel me-1"></i>Terapkan filter</button><a href="{{ route('admin.crm.marketplace.orders') }}" class="btn btn-sm mpcrm-reset-btn" title="Reset filter"><i class="bi bi-arrow-counterclockwise"></i><span class="visually-hidden">Reset</span></a></div>
    </form></section>

    <div class="mpcrm-executive-grid mb-4">
        @foreach([
            ['Order', number_format($analytics['totalOrders']), 'sesuai filter aktif', 'bi-bag-check', 'mpcrm-kpi-primary'],
            ['Revenue', 'Rp'.number_format($analytics['revenue'], 0, ',', '.'), 'tidak termasuk cancel', 'bi-cash-stack', 'mpcrm-kpi-success'],
            ['Average order', 'Rp'.number_format($analytics['averageOrder'], 0, ',', '.'), 'nilai rata-rata non-cancel', 'bi-graph-up-arrow', 'mpcrm-kpi-purple'],
            ['Customer unik', number_format($analytics['customerCount']), 'berdasarkan username marketplace', 'bi-people', 'mpcrm-kpi-whatsapp'],
            ['Cancel rate', number_format($analytics['cancelRate'], 1, ',', '.').'%', $analytics['cancelledOrders'].' order dibatalkan', 'bi-x-circle', 'mpcrm-kpi-danger'],
        ] as [$label,$value,$sub,$icon,$tone])
            <article class="mpcrm-kpi-card {{ $tone }}"><div class="mpcrm-kpi-icon"><i class="bi {{ $icon }}"></i></div><div class="min-w-0"><div class="mpcrm-kpi-label">{{ $label }}</div><div class="mpcrm-kpi-value">{{ $value }}</div><div class="mpcrm-kpi-note">{{ $sub }}</div></div></article>
        @endforeach
    </div>

    <section class="mpcrm-card mpcrm-chart-card mb-4"><div class="mpcrm-panel-head"><div><div class="mpcrm-section-kicker">Performance trend</div><h2 class="mpcrm-panel-title">Order & revenue trend</h2><div class="mpcrm-sub">Grafik mengikuti pencarian, toko, status, dan periode yang aktif.</div></div><div class="mpcrm-chart-context"><span class="mpcrm-pill mpcrm-period-pill"><i class="bi bi-calendar3 me-1"></i>{{ $dateFrom && $dateTo ? \Carbon\Carbon::parse($dateFrom)->format('d M Y').' – '.\Carbon\Carbon::parse($dateTo)->format('d M Y') : 'Semua periode' }}</span><span class="mpcrm-chart-scope">{{ $dailyOrders->count() }} periode</span></div></div>
        @php
            $maxChartRevenue = max(1, (float) $dailyOrders->max('revenue'));
            $maxChartOrders = max(1, (int) $dailyOrders->max('orders'));
        @endphp
        <div class="mpcrm-chart-container" aria-label="Grafik tren order dan revenue">
            @if($dailyOrders->isEmpty())
                <div class="text-secondary text-center py-5" style="font-size:.8rem;">Belum ada data untuk filter yang dipilih.</div>
            @else
                <div class="mpcrm-chart-legend"><span><i class="mpcrm-legend-dot mpcrm-legend-revenue"></i>Revenue</span><span><i class="mpcrm-legend-dot mpcrm-legend-orders"></i>Order</span><span class="mpcrm-sub ms-auto"><i class="bi bi-info-circle me-1"></i>Arahkan kursor ke batang untuk detail</span></div>
                <div class="mpcrm-chart-grid" style="--chart-columns:{{ max(1, $dailyOrders->count()) }};">
                    @foreach($dailyOrders as $day)
                        @php $revenue = (float) ($day->revenue ?? 0); $ordersCount = (int) ($day->orders ?? 0); @endphp
                        <div class="mpcrm-chart-column" title="{{ $day->tooltip ?? $day->label ?? $day->date }} · {{ number_format($ordersCount) }} order · Rp{{ number_format($revenue, 0, ',', '.') }} · {{ number_format((int) ($day->cancelled ?? 0)) }} cancel">
                            <div class="mpcrm-chart-bars"><span class="mpcrm-chart-bar mpcrm-chart-bar-revenue" style="height:{{ max(4, ($revenue / $maxChartRevenue) * 100) }}%;"></span><span class="mpcrm-chart-bar mpcrm-chart-bar-orders" style="height:{{ max(4, ($ordersCount / $maxChartOrders) * 100) }}%;"></span></div>
                            <span class="mpcrm-chart-label">{{ $day->label ?? \Carbon\Carbon::parse($day->date)->format('d/m') }}</span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
    @php
        $sortUrl = fn ($column) => request()->fullUrlWithQuery(['sort' => $column, 'direction' => $sort === $column && $direction === 'asc' ? 'desc' : 'asc', 'page' => 1]);
        $sortIcon = fn ($column) => $sort === $column ? ($direction === 'asc' ? 'bi-sort-up' : 'bi-sort-down') : 'bi-arrow-down-up';
    @endphp
    <section class="mpcrm-card mpcrm-data-card p-0 overflow-hidden"><div class="mpcrm-data-card-head"><div><div class="mpcrm-section-kicker">Order register</div><h2 class="mpcrm-panel-title">Daftar order marketplace</h2></div><div class="mpcrm-data-card-meta"><span><strong>{{ number_format($orders->total()) }}</strong> records</span><span class="mpcrm-data-divider"></span><span>25 / halaman</span></div></div><div class="table-responsive"><table class="table mb-0 mpcrm-table"><thead><tr>
        <th><a class="mpcrm-sort {{ $sort === 'order' ? 'active' : '' }}" href="{{ $sortUrl('order') }}">Order <i class="bi {{ $sortIcon('order') }}"></i></a></th>
        <th><a class="mpcrm-sort {{ $sort === 'customer' ? 'active' : '' }}" href="{{ $sortUrl('customer') }}">Customer <i class="bi {{ $sortIcon('customer') }}"></i></a></th>
        <th>Toko / Channel</th>
        <th><a class="mpcrm-sort {{ $sort === 'status' ? 'active' : '' }}" href="{{ $sortUrl('status') }}">Status <i class="bi {{ $sortIcon('status') }}"></i></a></th>
        <th><a class="mpcrm-sort {{ $sort === 'total' ? 'active' : '' }}" href="{{ $sortUrl('total') }}">Total <i class="bi {{ $sortIcon('total') }}"></i></a></th>
        <th><a class="mpcrm-sort {{ $sort === 'date' ? 'active' : '' }}" href="{{ $sortUrl('date') }}">Waktu <i class="bi {{ $sortIcon('date') }}"></i></a></th>
        <th></th>
    </tr></thead><tbody>
        @forelse($orders as $order)
            @php $statusColors=['completed'=>['#dcfce7','#166534'],'cancelled'=>['#fee2e2','#991b1b'],'shipped'=>['#dbeafe','#1d4ed8'],'packed'=>['#fef3c7','#92400e'],'new'=>['#f1f5f9','#475569']]; $sc=$statusColors[$order->status]??$statusColors['new']; @endphp
            <tr><td><div class="mpcrm-order-cell"><span class="mpcrm-order-marker"></span><div><a class="mpcrm-link" href="{{ route('admin.crm.marketplace.orders.show', $order) }}">{{ $order->channel_order_id ?: $order->external_order_id }}</a><div class="mpcrm-sub">{{ $order->buyer_username ?: 'username tidak ada' }}</div></div></div></td><td><div class="fw-semibold text-slate-strong">{{ $order->buyer_name ?: $order->customer?->name ?: 'Buyer Marketplace' }}</div><div class="mpcrm-sub">{{ $order->buyer_phone ?: $order->customer?->phone ?: '-' }}</div></td><td><div class="mpcrm-store-name">{{ $order->store?->name ?: '-' }}</div><span class="mpcrm-channel-tag"><i class="bi bi-shop"></i>{{ $order->store?->channel?->name ?: '-' }}</span></td><td><span class="mpcrm-pill mpcrm-status-pill mpcrm-status-{{ $order->status }}" style="background:{{ $sc[0] }};color:{{ $sc[1] }};">{{ ucfirst($order->status) }}</span></td><td class="fw-bold mpcrm-total-cell">Rp{{ number_format($order->total_amount ?? $order->total_paid_customer ?? 0,0,',','.') }}</td><td><div class="mpcrm-date-cell">{{ optional($order->ordered_at ?: $order->order_date)->format('d M Y') }}</div><div class="mpcrm-sub">{{ optional($order->ordered_at ?: $order->order_date)->format('H:i') }}</div></td><td><a class="mpcrm-row-action" href="{{ route('admin.crm.marketplace.orders.show', $order) }}" aria-label="Buka detail order"><i class="bi bi-arrow-up-right"></i></a></td></tr>
        @empty <tr><td colspan="7" class="text-center text-secondary py-5">Belum ada order marketplace.</td></tr>@endforelse
    </tbody></table></div><div class="mpcrm-pagination p-3">{{ $orders->links() }}</div></section>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const rangeInput = document.getElementById('mpOrderDateRange');
    const fromInput = document.getElementById('mpOrderDateFrom');
    const toInput = document.getElementById('mpOrderDateTo');
    if (!rangeInput || !fromInput || !toInput || typeof window.flatpickr !== 'function') return;

    const initialDates = [fromInput.value, toInput.value].filter(Boolean);
    const isSingleDayRange = initialDates.length === 2 && initialDates[0] === initialDates[1];
    window.flatpickr(rangeInput, {
        mode: 'range',
        dateFormat: 'd/m/Y',
        allowInput: false,
        defaultDate: initialDates.map(value => window.flatpickr.parseDate(value, 'Y-m-d')),
        locale: window.flatpickr.l10ns?.id || { firstDayOfWeek: 1 },
        onReady: function (selectedDates, dateStr, instance) {
            if (isSingleDayRange && selectedDates[0]) {
                instance.input.value = window.flatpickr.formatDate(selectedDates[0], 'd/m/Y');
            } else if (selectedDates.length === 2) {
                instance.input.value = window.flatpickr.formatDate(selectedDates[0], 'd/m/Y') + ' – ' + window.flatpickr.formatDate(selectedDates[1], 'd/m/Y');
            }
        },
        onChange: function (selectedDates, dateStr, instance) {
            fromInput.value = selectedDates[0] ? window.flatpickr.formatDate(selectedDates[0], 'Y-m-d') : '';
            toInput.value = selectedDates[1] ? window.flatpickr.formatDate(selectedDates[1], 'Y-m-d') : '';
            if (selectedDates.length === 2) {
                instance.input.value = window.flatpickr.formatDate(selectedDates[0], 'd/m/Y') + ' – ' + window.flatpickr.formatDate(selectedDates[1], 'd/m/Y');
            }
        },
    });
});
</script>
@endpush
