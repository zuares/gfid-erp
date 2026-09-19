@extends('layouts.app')
@section('title', 'Prospects Marketplace')
@push('head') @include('admin.crm.marketplace._styles') @endpush

@section('content')
<div class="container-fluid py-3">
    @include('admin.crm.marketplace._nav')

    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
        <div>
            <h5 class="fw-bold mb-1">Prospects Marketplace</h5>
            <div class="text-secondary" style="font-size:.78rem;">Username marketplace dengan satu order aktif; lokasi, pembayaran, dan aktivitas komunikasi membantu menentukan prioritas follow-up.</div>
        </div>
        <span class="mpcrm-pill" style="background:#f0fdf4;color:#15803d;">{{ $prospects->total() }} prospects</span>
    </div>

    @if(session('success'))<div class="alert alert-success py-2" style="font-size:.8rem;">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="alert alert-danger py-2" style="font-size:.8rem;">{{ session('error') }}</div>@endif

    @php
        $formatMetricCurrency = fn ($amount) => 'Rp' . number_format((float) $amount, 0, ',', '.');
        $prospectTotalForAnalytics = max(1, (int) $prospectMetrics['total']);
        $formatMetricPercentage = fn ($count) => number_format(((int) $count / $prospectTotalForAnalytics) * 100, 1, ',', '.');
        $hasAdvancedFilters = (bool) ($storeId || $segment !== '' || $paidRange !== '');
    @endphp

    <section class="mpcrm-executive-grid mb-3" aria-label="Ringkasan performa prospect">
        <div class="mpcrm-kpi-card mpcrm-kpi-primary">
            <div class="mpcrm-kpi-icon"><i class="bi bi-people"></i></div>
            <div><div class="mpcrm-kpi-label">Prospect aktif</div><div class="mpcrm-kpi-value">{{ number_format($prospectMetrics['total'], 0, ',', '.') }}</div><div class="mpcrm-kpi-note">Buyer satu kali order</div></div>
        </div>
        <div class="mpcrm-kpi-card mpcrm-kpi-success">
            <div class="mpcrm-kpi-icon"><i class="bi bi-wallet2"></i></div>
            <div><div class="mpcrm-kpi-label">Potensi pembayaran</div><div class="mpcrm-kpi-value">{{ $formatMetricCurrency($prospectMetrics['total_paid']) }}</div><div class="mpcrm-kpi-note">Akumulasi buyer aktif</div></div>
        </div>
        <div class="mpcrm-kpi-card mpcrm-kpi-purple">
            <div class="mpcrm-kpi-icon"><i class="bi bi-receipt"></i></div>
            <div><div class="mpcrm-kpi-label">Rata-rata pembayaran</div><div class="mpcrm-kpi-value">{{ $formatMetricCurrency($prospectMetrics['average_paid']) }}</div><div class="mpcrm-kpi-note">Nilai per prospect</div></div>
        </div>
        <div class="mpcrm-kpi-card mpcrm-kpi-whatsapp">
            <div class="mpcrm-kpi-icon"><i class="bi bi-whatsapp"></i></div>
            <div><div class="mpcrm-kpi-label">Siap follow-up</div><div class="mpcrm-kpi-value">{{ number_format($prospectMetrics['wa_ready'], 0, ',', '.') }}</div><div class="mpcrm-kpi-note">{{ number_format($prospectMetrics['wa_sent'], 0, ',', '.') }} prospect · {{ number_format($prospectMetrics['wa_total_attempts'], 0, ',', '.') }} total kirim</div></div>
        </div>
        <div class="mpcrm-kpi-card mpcrm-kpi-coverage">
            <div class="mpcrm-kpi-icon"><i class="bi bi-broadcast-pin"></i></div>
            <div><div class="mpcrm-kpi-label">Coverage</div><div class="mpcrm-kpi-value">{{ number_format($prospectMetrics['wa_coverage'], 1, ',', '.') }}%</div><div class="mpcrm-kpi-note">Prospect dengan nomor valid</div></div>
        </div>
    </section>

    <section class="mpcrm-analytics-grid mb-3" aria-label="Analitik prospect marketplace">
        <div class="mpcrm-analysis-card">
            <div class="mpcrm-analysis-head"><div><div class="mpcrm-label">Distribusi lokasi</div><h6 class="mpcrm-analysis-title">Kota terbanyak</h6></div><i class="bi bi-buildings"></i></div>
            <div class="mpcrm-analysis-list">
                @forelse($prospectAnalytics['top_cities'] as $metric)
                    <div class="mpcrm-analysis-row {{ $metric['expandable'] ? 'mpcrm-analysis-row-expandable' : '' }}" @if($metric['expandable']) data-city-other-toggle role="button" tabindex="0" aria-expanded="false" @endif>
                        <div class="mpcrm-analysis-row-head"><span title="{{ $metric['label'] }}">{{ mb_strimwidth($metric['label'], 0, 24, '…') }}</span><span class="d-inline-flex align-items-center gap-1"><b>{{ $formatMetricPercentage($metric['count']) }}%</b>@if($metric['expandable'])<i class="bi bi-chevron-down mpcrm-city-toggle-icon"></i>@endif</span></div>
                        <div class="mpcrm-analysis-track"><span style="width:{{ round(($metric['count'] / $prospectTotalForAnalytics) * 100, 2) }}%"></span></div>
                        <div class="mpcrm-analysis-row-meta">{{ number_format($metric['count'], 0, ',', '.') }} prospect{{ $metric['expandable'] ? ' · klik untuk lihat rincian' : '' }}</div>
                    </div>
                    @if($metric['expandable'])
                        <div class="mpcrm-city-breakdown" data-city-other-list hidden>
                            @foreach($prospectAnalytics['other_cities'] as $otherCity)
                                <div class="mpcrm-analysis-row mpcrm-analysis-row-sub">
                                    <div class="mpcrm-analysis-row-head"><span title="{{ $otherCity['label'] }}">{{ mb_strimwidth($otherCity['label'], 0, 24, '…') }}</span><b>{{ $formatMetricPercentage($otherCity['count']) }}%</b></div>
                                    <div class="mpcrm-analysis-track"><span style="width:{{ round(($otherCity['count'] / $prospectTotalForAnalytics) * 100, 2) }}%"></span></div>
                                    <div class="mpcrm-analysis-row-meta">{{ number_format($otherCity['count'], 0, ',', '.') }} prospect</div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                @empty
                    <div class="mpcrm-empty-analytics">Belum ada data kota.</div>
                @endforelse
            </div>
        </div>
        <div class="mpcrm-analysis-card">
            <div class="mpcrm-analysis-head"><div><div class="mpcrm-label">Distribusi lokasi</div><h6 class="mpcrm-analysis-title">Provinsi terbanyak</h6></div><i class="bi bi-geo-alt"></i></div>
            <div class="mpcrm-analysis-list">
                @forelse($prospectAnalytics['top_provinces'] as $metric)
                    <div class="mpcrm-analysis-row"><div class="mpcrm-analysis-row-head"><span title="{{ $metric['label'] }}">{{ mb_strimwidth($metric['label'], 0, 24, '…') }}</span><b>{{ $formatMetricPercentage($metric['count']) }}%</b></div><div class="mpcrm-analysis-track"><span class="mpcrm-analysis-track-purple" style="width:{{ round(($metric['count'] / $prospectTotalForAnalytics) * 100, 2) }}%"></span></div><div class="mpcrm-analysis-row-meta">{{ number_format($metric['count'], 0, ',', '.') }} prospect</div></div>
                @empty
                    <div class="mpcrm-empty-analytics">Belum ada data provinsi.</div>
                @endforelse
            </div>
        </div>
        <div class="mpcrm-analysis-card">
            <div class="mpcrm-analysis-head"><div><div class="mpcrm-label">Preferensi transaksi</div><h6 class="mpcrm-analysis-title">Metode pembayaran</h6></div><i class="bi bi-credit-card-2-front"></i></div>
            <div class="mpcrm-analysis-list">
                @forelse($prospectAnalytics['payment_methods'] as $metric)
                    <div class="mpcrm-analysis-row"><div class="mpcrm-analysis-row-head"><span title="{{ $metric['label'] }}">{{ mb_strimwidth($metric['label'], 0, 24, '…') }}</span><b>{{ $formatMetricPercentage($metric['count']) }}%</b></div><div class="mpcrm-analysis-track"><span class="mpcrm-analysis-track-orange" style="width:{{ round(($metric['count'] / $prospectTotalForAnalytics) * 100, 2) }}%"></span></div><div class="mpcrm-analysis-row-meta">{{ number_format($metric['count'], 0, ',', '.') }} prospect</div></div>
                @empty
                    <div class="mpcrm-empty-analytics">Belum ada data metode pembayaran.</div>
                @endforelse
            </div>
        </div>
        <div class="mpcrm-analysis-card">
            <div class="mpcrm-analysis-head"><div><div class="mpcrm-label">Aktivitas komunikasi</div><h6 class="mpcrm-analysis-title">Frekuensi pengiriman</h6></div><i class="bi bi-send-check"></i></div>
            <div class="mpcrm-analysis-list">
                @foreach($prospectAnalytics['follow_up'] as $metric)
                    <div class="mpcrm-analysis-row"><div class="mpcrm-analysis-row-head"><span>{{ $metric['label'] }}</span><b>{{ $formatMetricPercentage($metric['count']) }}%</b></div><div class="mpcrm-analysis-track"><span class="mpcrm-analysis-track-green" style="width:{{ round(($metric['count'] / $prospectTotalForAnalytics) * 100, 2) }}%"></span></div><div class="mpcrm-analysis-row-meta">{{ number_format($metric['count'], 0, ',', '.') }} prospect</div></div>
                @endforeach
            </div>
        </div>
    </section>

    <div class="mpcrm-toolbar mb-3">
        <form method="GET" action="{{ route('admin.crm.marketplace.prospects') }}" class="row g-2 align-items-end" id="mpcrmFilterForm">
            <div class="col-md-4">
                <label class="form-label mb-1" style="font-size:.7rem;font-weight:800;">Cari prospect atau item order</label>
                <input name="q" value="{{ $search }}" class="form-control form-control-sm" placeholder="Nama, telepon, variant, SKU, atau item master…" autocomplete="off">
            </div>
            <div class="col-md-4">
                <label class="form-label mb-1" style="font-size:.7rem;font-weight:800;">Judul produk</label>
                <select name="product_title" class="form-select form-select-sm">
                    <option value="">Semua judul produk</option>
                    @foreach($productTitles as $title)
                        <option value="{{ $title }}" @selected($productTitle === $title)>{{ $title }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label mb-1" style="font-size:.7rem;font-weight:800;">Metode pembayaran</label>
                <select name="payment_method" class="form-select form-select-sm">
                    <option value="">Semua metode</option>
                    @foreach($paymentMethods as $option)
                        <option value="{{ $option }}" @selected($paymentMethod === $option)>{{ $option }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label mb-1" style="font-size:.7rem;font-weight:800;">Kota</label>
                <select name="city" class="form-select form-select-sm">
                    <option value="">Semua kota</option>
                    @foreach($cities as $option)
                        <option value="{{ $option }}" @selected($city === $option)>{{ $option }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label mb-1" style="font-size:.7rem;font-weight:800;">Provinsi</label>
                <select name="province" class="form-select form-select-sm">
                    <option value="">Semua provinsi</option>
                    @foreach($provinces as $option)
                        <option value="{{ $option }}" @selected($province === $option)>{{ $option }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label mb-1" style="font-size:.7rem;font-weight:800;">Usia order</label>
                <select name="order_age" class="form-select form-select-sm">
                    <option value="">Semua usia</option>
                    <option value="0_30" @selected($orderAge === '0_30')>0–30 hari</option>
                    <option value="31_90" @selected($orderAge === '31_90')>31–90 hari</option>
                    <option value="91_365" @selected($orderAge === '91_365')>91 hari–1 tahun</option>
                    <option value="366_plus" @selected($orderAge === '366_plus')>Lebih dari 1 tahun</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label mb-1" style="font-size:.7rem;font-weight:800;">Kesiapan kontak</label>
                <select name="wa_status" class="form-select form-select-sm">
                    <option value="">Semua kondisi</option>
                    <option value="ready" @selected($waStatus === 'ready')>Siap follow-up</option>
                    <option value="missing" @selected($waStatus === 'missing')>Nomor belum ada</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label mb-1" style="font-size:.7rem;font-weight:800;">Data per halaman</label>
                <select name="per_page" class="form-select form-select-sm">
                    @foreach($perPageOptions as $option)
                        <option value="{{ $option }}" @selected($perPage === $option)>{{ $option }} data</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label mb-1" style="font-size:.7rem;font-weight:800;">Periode order</label>
                <div class="position-relative">
                    <i class="bi bi-calendar3 mpcrm-date-icon"></i>
                    <input id="mpProspectDateRange" class="form-control form-control-sm mpcrm-date-range" placeholder="Semua waktu" autocomplete="off" aria-label="Pilih periode order">
                    <input type="hidden" data-gf-date="off" name="date_from" id="mpProspectDateFrom" value="{{ $dateFrom }}">
                    <input type="hidden" data-gf-date="off" name="date_to" id="mpProspectDateTo" value="{{ $dateTo }}">
                </div>
            </div>
            <div class="col-12">
                <button type="button" class="mpcrm-filter-toggle {{ $hasAdvancedFilters ? 'is-open' : '' }}" data-filter-advanced-toggle aria-expanded="{{ $hasAdvancedFilters ? 'true' : 'false' }}"><i class="bi bi-sliders2 me-1"></i>Filter lanjutan <span class="mpcrm-filter-toggle-note">Toko, segment, dan nominal pembayaran</span><i class="bi bi-chevron-down ms-auto"></i></button>
            </div>
            <div class="col-12 mpcrm-advanced-filters" data-filter-advanced @if(!$hasAdvancedFilters) hidden @endif>
                <div class="row g-2">
                    <div class="col-md-2">
                        <label class="form-label mb-1" style="font-size:.7rem;font-weight:800;">Toko</label>
                        <select name="store_id" class="form-select form-select-sm">
                            <option value="">Semua toko</option>
                            @foreach($stores as $store)
                                <option value="{{ $store->id }}" @selected($storeId === $store->id)>{{ $store->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label mb-1" style="font-size:.7rem;font-weight:800;">Segment</label>
                        <select name="segment" class="form-select form-select-sm">
                            <option value="">Semua segment</option>
                            @foreach($segmentDefinitions as $key => $definition)
                                <option value="{{ $key }}" @selected($segment === $key)>{{ $definition['label'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label mb-1" style="font-size:.7rem;font-weight:800;">Pembayaran buyer</label>
                        <select name="paid_range" class="form-select form-select-sm">
                            <option value="">Semua nominal</option>
                            <option value="under_100k" @selected($paidRange === 'under_100k')>Di bawah Rp100 ribu</option>
                            <option value="100k_300k" @selected($paidRange === '100k_300k')>Rp100–300 ribu</option>
                            <option value="300k_plus" @selected($paidRange === '300k_plus')>Di atas Rp300 ribu</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="col-md-2 d-flex justify-content-end">
                @if($search !== '' || $productTitle !== '' || $paymentMethod !== '' || $city !== '' || $province !== '' || $storeId || $segment !== '' || $orderAge !== '' || $paidRange !== '' || $dateFrom || $dateTo || $waStatus !== '')
                    <a href="{{ route('admin.crm.marketplace.prospects') }}" class="btn btn-sm btn-outline-secondary" title="Reset filter">Reset</a>
                @endif
            </div>
        </form>
    </div>

    @php
        $sortUrl = fn ($column) => request()->fullUrlWithQuery(['sort' => $column, 'direction' => $sort === $column && $direction === 'asc' ? 'desc' : 'asc', 'page' => 1]);
        $sortIcon = fn ($column) => $sort === $column ? ($direction === 'asc' ? 'bi-sort-up' : 'bi-sort-down') : 'bi-arrow-down-up';
    @endphp

    <div class="mpcrm-card p-0 overflow-hidden">
        <form method="POST" action="{{ route('admin.crm.marketplace.prospects.bulk_follow_up') }}" id="mpcrmBulkFollowUpForm">
            @csrf
            <div class="d-flex align-items-center justify-content-between gap-2 flex-wrap p-3 border-bottom">
                <div><span class="fw-bold" style="font-size:.82rem;">Bulk follow-up</span><span id="mpcrmBulkCount" class="mpcrm-sub ms-2">0 dipilih</span></div>
                <button type="submit" id="mpcrmBulkButton" class="btn btn-sm" style="background:#25d366;color:#fff;border-radius:8px;font-size:.72rem;font-weight:800;" disabled><i class="bi bi-whatsapp me-1"></i>Follow up terpilih</button>
            </div>
            <div class="table-responsive">
                <table class="table mb-0 mpcrm-table">
                <thead><tr>
                    <th style="width:42px;text-align:center;"><input type="checkbox" id="mpcrmSelectAll" title="Pilih semua yang memiliki nomor"></th>
                    <th><a class="mpcrm-sort {{ $sort === 'customer' ? 'active' : '' }}" href="{{ $sortUrl('customer') }}">Customer <i class="bi {{ $sortIcon('customer') }}"></i></a></th>
                    <th>Kota</th>
                    <th>Provinsi</th>
                    <th><a class="mpcrm-sort {{ $sort === 'first_order' ? 'active' : '' }}" href="{{ $sortUrl('first_order') }}">Order pertama <i class="bi {{ $sortIcon('first_order') }}"></i></a></th>
                    <th>Metode pembayaran</th>
                    <th><a class="mpcrm-sort {{ $sort === 'paid' ? 'active' : '' }}" href="{{ $sortUrl('paid') }}">Pembayaran pembeli <i class="bi {{ $sortIcon('paid') }}"></i></a></th>
                    <th class="mpcrm-wa-cell">Status pengiriman</th>
                    <th>Aksi</th>
                </tr></thead>
                <tbody>
                @forelse($prospects as $prospect)
                    @php
                        $order = $prospect->prospect_order ?? null;
                        $waAttempts = (int) ($prospect->whatsapp_message_count ?? 0);
                        $waSent = (int) ($prospect->whatsapp_sent_count ?? 0);
                        $prospectRowId = 'mpcrm-prospect-'.$loop->index;
                    @endphp
                    <tr class="mpcrm-prospect-row" data-prospect-toggle data-detail-id="mpcrm-order-detail-{{ $prospectRowId }}" tabindex="0" role="button" aria-expanded="false">
                        <td class="text-center"><input type="checkbox" name="customer_ids[]" value="{{ $prospect->id }}" class="mpcrm-prospect-check" @disabled(!$prospect->id || !$prospect->wa_phone) title="{{ $prospect->wa_phone && $prospect->id ? 'Pilih customer ini' : 'Customer atau nomor WhatsApp tidak tersedia' }}"></td>
                        <td><div class="fw-semibold">{{ $prospect->name ?: 'Buyer Marketplace' }}</div><div class="mpcrm-sub">{{ $prospect->buyer_username ? '@'.$prospect->buyer_username.' · ' : '' }}{{ $prospect->phone ?: 'Telepon tidak ada' }}</div></td>
                        <td>{{ $prospect->city ?: '-' }}</td>
                        <td>{{ $prospect->province ?: '-' }}</td>
                        <td>{{ $prospect->first_marketplace_order_at ? \Carbon\Carbon::parse($prospect->first_marketplace_order_at)->format('d M Y') : '-' }}<div class="mpcrm-sub">{{ \App\Http\Controllers\Admin\MarketplaceCrmController::formatElapsedDays((int) $prospect->days_since_last_order) }}</div></td>
                        <td>{{ $order?->payment_method ?: 'Tidak tercatat di file order' }}</td>
                        <td class="fw-bold">Rp{{ number_format($prospect->marketplace_total_paid,0,',','.') }}</td>
                        <td class="mpcrm-wa-cell">
                            @php
                                $waLastStatus = (string) ($prospect->whatsapp_last_status ?? '');
                                $waStatusLabel = match ($waLastStatus) {
                                    'sent' => 'Terkirim',
                                    'failed' => 'Gagal',
                                    'pending' => 'Pending',
                                    default => 'Belum ada status',
                                };
                                $waStatusClass = match ($waLastStatus) {
                                    'sent' => 'mpcrm-wa-status-ok',
                                    'failed' => 'mpcrm-wa-status-failed',
                                    'pending' => 'mpcrm-wa-status-pending',
                                    default => 'mpcrm-wa-status-none',
                                };
                            @endphp
                            @if($waAttempts > 0)
                                <span class="mpcrm-wa-status {{ $waSent > 0 ? 'mpcrm-wa-status-ok' : 'mpcrm-wa-status-failed' }}">{{ $waSent > 0 ? 'Pernah terkirim' : 'Belum berhasil' }}</span>
                                <div class="mpcrm-sub">{{ $waAttempts }}x dikirim · {{ $waSent }}x berhasil</div>
                                <div class="mpcrm-sub">Terakhir: <span class="mpcrm-wa-status {{ $waStatusClass }}">{{ $waStatusLabel }}</span></div>
                            @else
                                <span class="mpcrm-wa-status mpcrm-wa-status-none">Belum pernah dikirim</span>
                            @endif
                        </td>
                        <td>@if($prospect->wa_phone && $prospect->id)<a class="btn btn-sm" href="{{ route('admin.crm.marketplace.prospects.follow_up', $prospect->id) }}" title="Tinjau lalu kirim lewat Fonnte" style="background:#25d366;color:#fff;border-radius:8px;font-size:.68rem;font-weight:700;"><i class="bi bi-whatsapp me-1"></i>Follow up</a>@else<span class="text-secondary" style="font-size:.72rem;">{{ $prospect->wa_phone ? 'Profil belum terhubung' : 'Nomor tidak ada' }}</span>@endif</td>
                    </tr>
                    <tr id="mpcrm-order-detail-{{ $prospectRowId }}" class="mpcrm-accordion-detail" hidden>
                        <td colspan="9">
                            <div class="mpcrm-order-detail">
                                <div class="mpcrm-order-detail-head">
                                    <div>
                                        <div class="mpcrm-label">Detail order</div>
                                        <div class="fw-bold">{{ $order?->channel_order_id ?: $order?->external_order_id ?: 'Order marketplace' }}</div>
                                        <div class="mpcrm-sub">{{ $order?->store?->name ?: 'Marketplace' }} · {{ optional($order?->ordered_at ?: $order?->order_date)->format('d M Y H:i') ?: '-' }}</div>
                                    </div>
                                    <span class="mpcrm-pill" style="background:#eff6ff;color:#1d4ed8;">{{ ucfirst(str_replace('_', ' ', $order?->order_status ?: $order?->status ?: 'Tidak diketahui')) }}</span>
                                </div>
                                <div class="mpcrm-order-detail-summary">
                                    <div><span>Pembayaran pembeli</span><b>Rp{{ number_format((float) ($order?->total_paid_customer ?: $order?->total_amount ?: 0), 0, ',', '.') }}</b></div>
                                    <div><span>Metode pembayaran</span><b>{{ $order?->payment_method ?: 'Tidak tercatat di file order' }}</b></div>
                                    <div><span>Resi</span><b>{{ $order?->shipping_awb_no ?: '-' }}</b></div>
                                </div>
                                <div class="mpcrm-order-detail-grid">
                                    <div>
                                        <div class="mpcrm-label mb-1">Item dipesan</div>
                                        @forelse(($prospect->prospect_items ?? collect()) as $detailItem)
                                            @php
                                                $detailTitle = trim((string) ($detailItem->item_name_snapshot ?: ($detailItem->item_name ?: '')));
                                                $detailTitle = $detailTitle ?: 'Produk tidak tersedia';
                                                $detailVariant = trim((string) ($detailItem->variant_name ?: ($detailItem->variant_snapshot ?: '')));
                                                $detailVariant = $detailVariant ?: 'Variant tidak tersedia';
                                            @endphp
                                            <div class="mpcrm-order-detail-item">
                                                <div class="fw-semibold">{{ $detailTitle }}</div>
                                                <div class="mpcrm-sub">{{ $detailVariant }} · ×{{ max(1, (int) ($detailItem->qty ?? 0)) }} · Rp{{ number_format((float) ($detailItem->line_net_amount ?: $detailItem->price_after_discount ?: $detailItem->price ?: 0), 0, ',', '.') }}</div>
                                            </div>
                                        @empty
                                            <div class="text-secondary" style="font-size:.75rem;">Item tidak tersedia.</div>
                                        @endforelse
                                    </div>
                                    <div>
                                        <div class="mpcrm-label mb-1">Pengiriman</div>
                                        <div style="font-size:.78rem;">{{ $order?->buyer_name ?: $prospect->name ?: 'Buyer Marketplace' }}</div>
                                        <div class="mpcrm-sub" style="white-space:pre-line;">{{ $order?->shipping_address ?: 'Alamat tidak tersedia' }}</div>
                                        <div class="mpcrm-sub">{{ $order?->shipping_city ?: '-' }}, {{ $order?->shipping_province ?: '-' }} {{ $order?->shipping_postal_code ?: '' }}</div>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="text-center text-secondary py-5">Belum ada prospect marketplace.</td></tr>
                @endforelse
                </tbody>
            </table>
            </div>
        </form>
        <div class="p-3">{{ $prospects->links() }}</div>
    </div>
</div>

<div id="mpcrmMapModal" class="mpcrm-map-modal" aria-hidden="true">
    <div class="mpcrm-map-dialog" role="dialog" aria-modal="true" aria-labelledby="mpcrmMapTitle">
        <div class="mpcrm-map-head"><div><div id="mpcrmMapTitle" class="mpcrm-map-title">Hubungkan ke item master</div><div id="mpcrmMapSub" class="mpcrm-map-sub"></div></div><button type="button" class="mpcrm-map-close" data-map-close aria-label="Tutup">&times;</button></div>
        <label class="form-label mb-1" style="font-size:.72rem;font-weight:800;" for="mpcrmMapSearch">Cari item master</label>
        <input id="mpcrmMapSearch" class="form-control form-control-sm" placeholder="Cari kode, SKU, atau nama item…" autocomplete="off">
        <div id="mpcrmMapStatus" class="mpcrm-map-status">Ketik minimal 2 karakter.</div>
        <div id="mpcrmMapResults" class="mpcrm-map-results"></div>
        <div class="mpcrm-map-actions"><label class="form-check-label" style="font-size:.72rem;color:#475569;"><input id="mpcrmMapApplyAll" class="form-check-input me-1" type="checkbox"> Terapkan ke SKU marketplace yang sama</label><button type="button" id="mpcrmMapSave" class="btn btn-sm btn-primary" disabled>Simpan mapping</button></div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const bulkForm = document.getElementById('mpcrmBulkFollowUpForm');
    const selectAll = document.getElementById('mpcrmSelectAll');
    const bulkButton = document.getElementById('mpcrmBulkButton');
    const bulkCount = document.getElementById('mpcrmBulkCount');
    const checks = Array.from(document.querySelectorAll('.mpcrm-prospect-check'));
    function updateBulkState() {
        const selected = checks.filter(check => check.checked);
        if (bulkCount) bulkCount.textContent = selected.length + ' dipilih';
        if (bulkButton) bulkButton.disabled = selected.length === 0;
        if (selectAll) {
            const enabled = checks.filter(check => !check.disabled);
            selectAll.checked = enabled.length > 0 && enabled.every(check => check.checked);
            selectAll.indeterminate = selected.length > 0 && !selectAll.checked;
        }
    }
    selectAll?.addEventListener('change', function () {
        checks.filter(check => !check.disabled).forEach(check => { check.checked = selectAll.checked; });
        updateBulkState();
    });
    checks.forEach(check => check.addEventListener('change', updateBulkState));
    bulkForm?.addEventListener('submit', function (event) {
        if (!checks.some(check => check.checked)) {
            event.preventDefault();
            return;
        }
        if (checks.filter(check => check.checked).length > 100) {
            event.preventDefault();
            alert('Maksimal 100 customer per bulk follow-up.');
        }
    });
    updateBulkState();

    const filterForm = document.getElementById('mpcrmFilterForm');
    const filterSearch = filterForm?.querySelector('input[name="q"]');
    const advancedFilterToggle = filterForm?.querySelector('[data-filter-advanced-toggle]');
    const advancedFilters = filterForm?.querySelector('[data-filter-advanced]');
    let filterTimer = null;
    advancedFilterToggle?.addEventListener('click', function () {
        if (!advancedFilters) return;
        const willOpen = advancedFilters.hidden;
        advancedFilters.hidden = !willOpen;
        advancedFilterToggle.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
        advancedFilterToggle.classList.toggle('is-open', willOpen);
    });
    function submitProspectFilters() {
        if (!filterForm) return;
        filterForm.submit();
    }
    filterForm?.querySelectorAll('select').forEach(select => {
        select.addEventListener('change', submitProspectFilters);
    });
    filterSearch?.addEventListener('input', function () {
        clearTimeout(filterTimer);
        filterTimer = setTimeout(submitProspectFilters, 450);
    });
    filterSearch?.addEventListener('keydown', function (event) {
        if (event.key === 'Enter') {
            event.preventDefault();
            clearTimeout(filterTimer);
            submitProspectFilters();
        }
    });

    const prospectDateRange = document.getElementById('mpProspectDateRange');
    const prospectDateFrom = document.getElementById('mpProspectDateFrom');
    const prospectDateTo = document.getElementById('mpProspectDateTo');
    if (prospectDateRange && prospectDateFrom && prospectDateTo && typeof window.flatpickr === 'function') {
        const initialDates = [prospectDateFrom.value, prospectDateTo.value].filter(Boolean);
        const isSingleDayRange = initialDates.length === 2 && initialDates[0] === initialDates[1];
        window.flatpickr(prospectDateRange, {
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
                prospectDateFrom.value = selectedDates[0] ? window.flatpickr.formatDate(selectedDates[0], 'Y-m-d') : '';
                prospectDateTo.value = selectedDates[1] ? window.flatpickr.formatDate(selectedDates[1], 'Y-m-d') : '';
                if (selectedDates.length === 2) {
                    instance.input.value = window.flatpickr.formatDate(selectedDates[0], 'd/m/Y') + ' – ' + window.flatpickr.formatDate(selectedDates[1], 'd/m/Y');
                    submitProspectFilters();
                } else if (selectedDates.length === 0) {
                    submitProspectFilters();
                }
            },
        });
    }

    function toggleOrderDetail(trigger) {
        const detail = document.getElementById(trigger.dataset.detailId);
        if (!detail) return;
        const willOpen = detail.hidden;
        document.querySelectorAll('.mpcrm-accordion-detail:not([hidden])').forEach(openDetail => {
            openDetail.hidden = true;
            const openTrigger = document.querySelector(`[data-detail-id="${openDetail.id}"]`);
            openTrigger?.classList.remove('is-expanded');
            openTrigger?.setAttribute('aria-expanded', 'false');
        });
        detail.hidden = !willOpen;
        trigger.classList.toggle('is-expanded', willOpen);
        trigger.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
    }
    document.querySelectorAll('[data-prospect-toggle]').forEach(trigger => {
        trigger.addEventListener('click', function (event) {
            if (event.target.closest('input, a, button, select, label')) return;
            toggleOrderDetail(this);
        });
        trigger.addEventListener('keydown', function (event) {
            if (event.key !== 'Enter' && event.key !== ' ') return;
            event.preventDefault();
            toggleOrderDetail(this);
        });
    });

    document.querySelectorAll('[data-city-other-toggle]').forEach(trigger => {
        const breakdown = trigger.parentElement?.querySelector('[data-city-other-list]');
        if (!breakdown) return;
        const toggleCityBreakdown = function () {
            const willOpen = breakdown.hidden;
            breakdown.hidden = !willOpen;
            trigger.classList.toggle('is-expanded', willOpen);
            trigger.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
        };
        trigger.addEventListener('click', toggleCityBreakdown);
        trigger.addEventListener('keydown', function (event) {
            if (event.key !== 'Enter' && event.key !== ' ') return;
            event.preventDefault();
            toggleCityBreakdown();
        });
    });

    const modal = document.getElementById('mpcrmMapModal');
    const search = document.getElementById('mpcrmMapSearch');
    const results = document.getElementById('mpcrmMapResults');
    const status = document.getElementById('mpcrmMapStatus');
    const save = document.getElementById('mpcrmMapSave');
    const sub = document.getElementById('mpcrmMapSub');
    const searchUrl = @json(route('admin.crm.marketplace.prospects.items.search'));
    const mapUrl = @json(route('admin.crm.marketplace.prospects.items.map', ['item' => '__ITEM__']));
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    let selectedItemId = null, orderItemId = null, searchTimer = null;

    function escapeHtml(value) { return String(value ?? '').replace(/[&<>'"]/g, char => ({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#039;','"':'&quot;'}[char])); }
    function closeModal() { modal.classList.remove('is-open'); modal.setAttribute('aria-hidden', 'true'); selectedItemId = null; orderItemId = null; save.disabled = true; results.innerHTML = ''; search.value = ''; }
    async function searchMasterItems() {
        const query = search.value.trim();
        if (query.length < 2) { results.innerHTML = ''; status.textContent = 'Ketik minimal 2 karakter.'; return; }
        status.textContent = 'Mencari item master…';
        try {
            const response = await fetch(searchUrl + '?q=' + encodeURIComponent(query), {headers: {'Accept': 'application/json'}});
            if (!response.ok) throw new Error('Gagal mencari item master.');
            const items = await response.json();
            results.innerHTML = items.length ? items.map(item => `<button type="button" class="mpcrm-map-result" data-master-id="${item.id}" data-master-label="${escapeHtml(item.code || item.sku || '')} · ${escapeHtml(item.name || '')}"><span class="mpcrm-map-result-code">${escapeHtml(item.code || item.sku || '-')}</span><span class="mpcrm-map-result-name">${escapeHtml(item.name || '')}</span></button>`).join('') : '';
            status.textContent = items.length ? 'Pilih item yang sesuai.' : 'Item master tidak ditemukan.';
        } catch (error) { results.innerHTML = ''; status.textContent = error.message; }
    }
    document.querySelectorAll('[data-map-item]').forEach(button => button.addEventListener('click', function () { orderItemId = this.dataset.mapItem; sub.textContent = 'Order item: ' + (this.dataset.mapLabel || '-'); modal.classList.add('is-open'); modal.setAttribute('aria-hidden', 'false'); search.focus(); }));
    document.querySelectorAll('[data-map-close]').forEach(button => button.addEventListener('click', closeModal));
    modal.addEventListener('click', event => { if (event.target === modal) closeModal(); });
    search.addEventListener('input', function () { clearTimeout(searchTimer); searchTimer = setTimeout(searchMasterItems, 250); });
    results.addEventListener('click', function (event) { const button = event.target.closest('[data-master-id]'); if (!button) return; selectedItemId = button.dataset.masterId; status.textContent = 'Terpilih: ' + button.dataset.masterLabel; save.disabled = false; results.querySelectorAll('[data-master-id]').forEach(item => item.style.borderColor = ''); button.style.borderColor = '#2563eb'; });
    save.addEventListener('click', async function () {
        if (!orderItemId || !selectedItemId) return;
        save.disabled = true; status.textContent = 'Menyimpan mapping…';
        try {
            const response = await fetch(mapUrl.replace('__ITEM__', orderItemId), {method: 'PATCH', headers: {'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf}, body: JSON.stringify({internal_item_id: Number(selectedItemId), apply_to_all: document.getElementById('mpcrmMapApplyAll').checked})});
            const payload = await response.json();
            if (!response.ok) throw new Error(payload.message || 'Mapping gagal disimpan.');
            closeModal(); window.location.reload();
        } catch (error) { status.textContent = error.message; save.disabled = false; }
    });
});
</script>
@endsection
