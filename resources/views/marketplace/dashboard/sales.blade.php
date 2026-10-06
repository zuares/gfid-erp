@extends('layouts.app')

@section('title', 'Dashboard Penjualan • Marketplace')

@include('marketplace._shared')

@push('head')
<style>
    .sales-dashboard {
        --sales-line: var(--line, #d4d7e3);
        --sales-muted: var(--muted, #6b7280);
        --sales-card: var(--card, #fff);
        --sales-soft: var(--card-soft, #f9fafb);
        --sales-ink: var(--text, #111827);
        max-width: 1280px;
        margin-inline: auto;
        color: var(--sales-ink);
    }

    .sales-dashboard .sales-card {
        border: 1px solid var(--sales-line);
        border-radius: .75rem;
        background: var(--sales-card);
        box-shadow: 0 .125rem .25rem rgba(15, 23, 42, .035);
    }

    .sales-dashboard .sales-kicker {
        color: var(--accent, #2563eb);
        font-size: .68rem;
        font-weight: 800;
        letter-spacing: .12em;
        text-transform: uppercase;
    }

    .sales-dashboard .sales-title { color: var(--sales-ink); letter-spacing: -.035em; }
    .sales-dashboard .sales-subtitle,
    .sales-dashboard .sales-section-subtitle { color: var(--sales-muted); }

    .sales-dashboard .sales-filter-card { background: var(--sales-soft); }
    .sales-dashboard .sales-filter-card .form-label {
        color: var(--sales-muted);
        font-size: .74rem;
        font-weight: 700;
        margin-bottom: .3rem;
    }
    .sales-dashboard .sales-filter-card .form-control,
    .sales-dashboard .sales-filter-card .form-select {
        border-color: var(--sales-line);
        background-color: var(--sales-card);
        color: var(--sales-ink);
        font-size: .8rem;
    }
    .sales-dashboard .sales-filter-card .form-control:focus,
    .sales-dashboard .sales-filter-card .form-select:focus {
        border-color: var(--accent, #2563eb);
        box-shadow: 0 0 0 .2rem color-mix(in srgb, var(--accent, #2563eb) 18%, transparent);
    }

    .sales-dashboard .sales-nav { overflow-x: auto; scrollbar-width: none; }
    .sales-dashboard .sales-nav::-webkit-scrollbar { display: none; }
    .sales-dashboard .sales-nav .nav-link {
        border: 0;
        color: var(--sales-muted);
        background: transparent;
        border-radius: .55rem;
        font-size: .8rem;
        font-weight: 650;
        white-space: nowrap;
    }
    .sales-dashboard .sales-nav .nav-link:hover { color: var(--accent, #2563eb); background: var(--accent-soft, #dbeafe); }
    .sales-dashboard .sales-nav .nav-link.active { color: var(--accent, #2563eb); background: var(--accent-soft, #dbeafe); }
    .sales-dashboard .sales-tab-pane.is-hidden { display: none; }

    .sales-dashboard .sales-kpi { min-height: 132px; position: relative; overflow: hidden; }
    .sales-dashboard .sales-kpi::after {
        content: '';
        position: absolute;
        right: -1.8rem;
        bottom: -2.3rem;
        width: 6rem;
        height: 6rem;
        border-radius: 50%;
        background: var(--accent, #2563eb);
        opacity: .06;
    }
    .sales-dashboard .sales-kpi-label { color: var(--sales-muted); font-size: .7rem; font-weight: 750; }
    .sales-dashboard .sales-kpi-icon {
        width: 2rem;
        height: 2rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: .55rem;
        background: var(--accent-soft, #dbeafe);
        color: var(--accent, #2563eb);
    }
    .sales-dashboard .sales-kpi-value { color: var(--sales-ink); font-size: 1.4rem; font-weight: 800; letter-spacing: -.03em; }
    .sales-dashboard .sales-kpi-note { color: var(--sales-muted); font-size: .72rem; }
    .sales-dashboard .sales-kpi--success .sales-kpi-icon { background: var(--success-soft, #dcfce7); color: var(--success, #16a34a); }
    .sales-dashboard .sales-kpi--success::after { background: var(--success, #16a34a); }
    .sales-dashboard .sales-kpi--warning .sales-kpi-icon { background: #fff7ed; color: #c2410c; }
    .sales-dashboard .sales-kpi--warning::after { background: #ea580c; }

    .sales-dashboard .sales-section-header { padding: 1rem 1.15rem .85rem; }
    .sales-dashboard .sales-section-title { color: var(--sales-ink); font-size: 1rem; font-weight: 750; }
    .sales-dashboard .sales-section-subtitle { font-size: .75rem; }
    .sales-dashboard .sales-detail-link { color: var(--accent, #2563eb); font-size: .75rem; font-weight: 700; text-decoration: none; }
    .sales-dashboard .sales-detail-link:hover { text-decoration: underline; }
    .sales-dashboard .sales-table { --bs-table-bg: var(--sales-card); --bs-table-color: var(--sales-ink); --bs-table-hover-bg: color-mix(in srgb, var(--accent-soft, #dbeafe) 35%, var(--sales-card)); margin-bottom: 0; }
    .sales-dashboard .sales-table th {
        background: var(--sales-soft);
        border-bottom-color: var(--sales-line);
        color: var(--sales-muted);
        font-size: .68rem;
        font-weight: 750;
        letter-spacing: .04em;
        text-transform: uppercase;
        white-space: nowrap;
    }
    .sales-dashboard .sales-table td { border-color: color-mix(in srgb, var(--sales-line) 65%, transparent); font-size: .78rem; }
    .sales-dashboard .sales-date-link,
    .sales-dashboard .sales-action-link { color: var(--accent, #2563eb); font-weight: 700; text-decoration: none; }
    .sales-dashboard .sales-date-link:hover,
    .sales-dashboard .sales-action-link:hover { text-decoration: underline; }
    .sales-dashboard .sales-empty { color: var(--sales-muted); padding: 2.5rem 1rem; }
    .sales-dashboard .sales-badge { background: var(--sales-soft); border: 1px solid var(--sales-line); color: var(--sales-muted); font-size: .7rem; font-weight: 650; }

    body[data-theme="dark"] .sales-dashboard .sales-filter-card,
    body[data-theme="dark"] .sales-dashboard .sales-table th { background: var(--card-soft, #132a45); }
    body[data-theme="dark"] .sales-dashboard .sales-filter-card .form-control,
    body[data-theme="dark"] .sales-dashboard .sales-filter-card .form-select { color: var(--sales-ink); }

    @media (max-width: 767.98px) {
        .sales-dashboard { padding-inline: .75rem !important; }
        .sales-dashboard .sales-table { min-width: 720px; }
        .sales-dashboard .sales-kpi { min-height: 118px; }
        .sales-dashboard .sales-kpi-value { font-size: 1.2rem; }
    }
</style>
@endpush

@section('content')
@php
    $fmt = fn ($value) => 'Rp '.number_format((float) $value, 0, ',', '.');
    $dateLabel = fn ($date) => \Carbon\Carbon::parse($date)->format('d M Y');
    $detailQuery = ['date_from' => $filters['date_from'], 'date_to' => $filters['date_to']];
    if ($filters['store_id']) $detailQuery['store_id'] = $filters['store_id'];
@endphp

<div class="container-fluid py-4 sales-dashboard">
    <div class="d-flex flex-wrap align-items-start justify-content-between gap-3 mb-4">
        <div>
            <div class="text-muted small mb-1">Toko Online / Dashboard Operasional</div>
            <h1 class="h3 sales-title mb-1">Dashboard Penjualan</h1>
            <p class="sales-subtitle mb-0">Ringkasan pesanan dan penjualan marketplace pada periode yang dipilih.</p>
        </div>
        <span class="badge sales-badge rounded-pill px-3 py-2"><i class="bi bi-database-check me-1"></i>Data marketplace</span>
    </div>

    <form class="card sales-card sales-filter-card shadow-sm mb-4" method="GET" action="{{ route('marketplace.dashboard.sales') }}">
        <div class="card-body p-3">
            <div class="row g-2 align-items-end">
                <div class="col-12 col-md-auto me-md-2">
                    <div class="sales-kicker mb-1">Periode data</div>
                    <div class="small text-muted">Filter angka dashboard</div>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label" for="sales-date-from">Tanggal mulai</label>
                    <input id="sales-date-from" class="form-control form-control-sm" type="date" name="date_from" value="{{ $filters['date_from'] }}">
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label" for="sales-date-to">Tanggal akhir</label>
                    <input id="sales-date-to" class="form-control form-control-sm" type="date" name="date_to" value="{{ $filters['date_to'] }}">
                </div>
                @if ($stores->isNotEmpty())
                    <div class="col-12 col-md-3">
                        <label class="form-label" for="sales-store">Toko</label>
                        <select id="sales-store" class="form-select form-select-sm" name="store_id">
                            <option value="">Semua toko</option>
                            @foreach ($stores as $store)
                                <option value="{{ $store->id }}" @selected((string) $filters['store_id'] === (string) $store->id)>
                                    {{ $store->name }}{{ $store->channel?->code ? ' · '.ucfirst($store->channel->code) : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <div class="col-12 col-md-auto">
                    <button class="btn btn-primary btn-sm w-100" type="submit"><i class="bi bi-funnel me-1"></i>Terapkan</button>
                </div>
            </div>
        </div>
    </form>

    <nav class="sales-nav nav nav-pills gap-2 mb-4" aria-label="Dashboard operasional" role="tablist">
        <button class="nav-link active" type="button" role="tab" aria-selected="true" data-sales-tab="sales"><i class="bi bi-graph-up-arrow me-1"></i>Penjualan</button>
        <button class="nav-link" type="button" role="tab" aria-selected="false" data-sales-tab="products"><i class="bi bi-box-seam me-1"></i>Produk</button>
        <button class="nav-link" type="button" role="tab" aria-selected="false" data-sales-tab="payments"><i class="bi bi-wallet2 me-1"></i>Pembayaran</button>
        <button class="nav-link" type="button" role="tab" aria-selected="false" data-sales-tab="promotions"><i class="bi bi-percent me-1"></i>Promosi</button>
        <button class="nav-link" type="button" role="tab" aria-selected="false" data-sales-tab="shipping"><i class="bi bi-truck me-1"></i>Pengiriman</button>
    </nav>

    <section class="row g-3 mb-4" aria-label="Ringkasan penjualan">
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card sales-card sales-kpi h-100 shadow-sm">
                <div class="card-body p-3">
                    <div class="d-flex align-items-start justify-content-between mb-3"><span class="sales-kpi-label">Pesanan</span><span class="sales-kpi-icon"><i class="bi bi-receipt"></i></span></div>
                    <div class="sales-kpi-value">{{ number_format($summary['orders']) }}</div>
                    <div class="sales-kpi-note mt-1">pesanan tidak dibatalkan</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card sales-card sales-kpi h-100 shadow-sm">
                <div class="card-body p-3">
                    <div class="d-flex align-items-start justify-content-between mb-3"><span class="sales-kpi-label">Jumlah barang</span><span class="sales-kpi-icon"><i class="bi bi-boxes"></i></span></div>
                    <div class="sales-kpi-value">{{ number_format($summary['qty']) }}</div>
                    <div class="sales-kpi-note mt-1">unit terjual</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card sales-card sales-kpi sales-kpi--success h-100 shadow-sm">
                <div class="card-body p-3">
                    <div class="d-flex align-items-start justify-content-between mb-3"><span class="sales-kpi-label">Subtotal barang</span><span class="sales-kpi-icon"><i class="bi bi-cash-stack"></i></span></div>
                    <div class="sales-kpi-value">{{ $fmt($summary['subtotal']) }}</div>
                    <div class="sales-kpi-note mt-1">sebelum biaya pengiriman</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card sales-card sales-kpi sales-kpi--warning h-100 shadow-sm">
                <div class="card-body p-3">
                    <div class="d-flex align-items-start justify-content-between mb-3"><span class="sales-kpi-label">AOV</span><span class="sales-kpi-icon"><i class="bi bi-bar-chart-line"></i></span></div>
                    <div class="sales-kpi-value">{{ $fmt($summary['aov']) }}</div>
                    <div class="sales-kpi-note mt-1">rata-rata nilai pesanan</div>
                </div>
            </div>
        </div>
    </section>

    <div class="sales-tab-pane" data-sales-pane="sales" role="tabpanel">
    <section class="card sales-card shadow-sm" aria-labelledby="daily-sales-title">
        <div class="sales-section-header d-flex flex-wrap align-items-start justify-content-between gap-3">
            <div>
                <div class="sales-kicker mb-1">Ringkasan waktu</div>
                <h2 id="daily-sales-title" class="sales-section-title mb-1">Penjualan per tanggal</h2>
                <div class="sales-section-subtitle">Klik tanggal untuk memfilter dashboard ke tanggal tersebut.</div>
            </div>
            <span class="badge sales-badge rounded-pill px-3 py-2">{{ $daily->count() }} hari aktif</span>
        </div>

        @if ($daily->isEmpty())
            <div class="sales-empty text-center"><i class="bi bi-bar-chart-line d-block fs-3 mb-2"></i>Belum ada penjualan pada periode yang dipilih.</div>
        @else
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle sales-table">
                    <thead>
                        <tr>
                            <th class="ps-3">Tanggal</th>
                            <th class="text-end">Pesanan</th>
                            <th class="text-end">Unit terjual</th>
                            <th class="text-end">Subtotal barang</th>
                            <th class="text-end">AOV</th>
                            <th class="text-end pe-3">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($daily as $row)
                            @php $rowQuery = ['date_from' => $row->day, 'date_to' => $row->day]; if ($filters['store_id']) $rowQuery['store_id'] = $filters['store_id']; @endphp
                            <tr>
                                <td class="ps-3"><a class="sales-date-link" href="{{ route('marketplace.dashboard.sales', $rowQuery) }}">{{ $dateLabel($row->day) }}</a></td>
                                <td class="text-end">{{ number_format($row->orders) }}</td>
                                <td class="text-end">{{ number_format($row->qty) }}</td>
                                <td class="text-end">{{ $fmt($row->subtotal) }}</td>
                                <td class="text-end">{{ $fmt($row->aov) }}</td>
                                <td class="text-end pe-3"><a class="sales-action-link" href="{{ route('marketplace.orders', $rowQuery) }}">Detail pesanan <i class="bi bi-arrow-right"></i></a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
    </div>

    <div class="sales-tab-pane is-hidden" data-sales-pane="products" role="tabpanel" aria-hidden="true">
        <section class="card sales-card shadow-sm">
            <div class="sales-section-header d-flex flex-wrap align-items-start justify-content-between gap-3">
                <div>
                    <div class="sales-kicker mb-1">Kinerja produk</div>
                    <h2 class="sales-section-title mb-1">Produk terlaris</h2>
                    <div class="sales-section-subtitle">Produk diurutkan berdasarkan nilai penjualan pada periode aktif.</div>
                </div>
                <span class="badge sales-badge rounded-pill px-3 py-2">{{ $products->count() }} produk</span>
            </div>
            @if ($products->isEmpty())
                <div class="sales-empty text-center"><i class="bi bi-box-seam d-block fs-3 mb-2"></i>Belum ada detail produk pada periode ini.</div>
            @else
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle sales-table">
                        <thead><tr><th class="ps-3">Produk</th><th>SKU</th><th class="text-end">Qty</th><th class="text-end pe-3">Penjualan</th></tr></thead>
                        <tbody>
                            @foreach ($products as $product)
                                <tr>
                                    <td class="ps-3 fw-semibold">{{ $product->name }}</td>
                                    <td class="text-muted small">{{ $product->sku }}</td>
                                    <td class="text-end">{{ number_format((int) $product->qty) }}</td>
                                    <td class="text-end pe-3 fw-semibold">{{ $fmt($product->sales) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    </div>

    <div class="sales-tab-pane is-hidden" data-sales-pane="payments" role="tabpanel" aria-hidden="true">
        <section class="card sales-card shadow-sm">
            <div class="sales-section-header">
                <div class="sales-kicker mb-1">Metode pembayaran</div>
                <h2 class="sales-section-title mb-1">Ringkasan pembayaran</h2>
                <div class="sales-section-subtitle">Distribusi pesanan berdasarkan metode atau status pembayaran yang tersimpan.</div>
            </div>
            @if ($payments->isEmpty())
                <div class="sales-empty text-center"><i class="bi bi-wallet2 d-block fs-3 mb-2"></i>Belum ada data pembayaran pada periode ini.</div>
            @else
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle sales-table">
                        <thead><tr><th class="ps-3">Metode / status</th><th class="text-end">Pesanan</th><th class="text-end pe-3">Subtotal</th></tr></thead>
                        <tbody>
                            @foreach ($payments as $payment)
                                <tr><td class="ps-3 fw-semibold">{{ ucwords(str_replace('_', ' ', strtolower($payment->method))) }}</td><td class="text-end">{{ number_format((int) $payment->orders) }}</td><td class="text-end pe-3 fw-semibold">{{ $fmt($payment->subtotal) }}</td></tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    </div>

    <div class="sales-tab-pane is-hidden" data-sales-pane="promotions" role="tabpanel" aria-hidden="true">
        <section class="card sales-card shadow-sm">
            <div class="sales-section-header d-flex flex-wrap align-items-start justify-content-between gap-3">
                <div>
                    <div class="sales-kicker mb-1">Dampak promosi</div>
                    <h2 class="sales-section-title mb-1">Diskon dan subsidi</h2>
                    <div class="sales-section-subtitle">Nilai promosi yang tercatat pada order dalam periode aktif.</div>
                </div>
                <span class="badge sales-badge rounded-pill px-3 py-2">{{ number_format($promotionOrders) }} order memakai promo</span>
            </div>
            @if ($promotions->isEmpty())
                <div class="sales-empty text-center"><i class="bi bi-percent d-block fs-3 mb-2"></i>Belum ada diskon atau subsidi pada periode ini.</div>
            @else
                <div class="row g-3 p-3">
                    @foreach ($promotions as $promotion)
                        <div class="col-12 col-md-4">
                            <div class="border rounded p-3 h-100">
                                <div class="text-muted small">{{ $promotion['label'] }}</div>
                                <div class="h5 mb-0 mt-2">{{ $fmt($promotion['amount']) }}</div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </section>
    </div>

    <div class="sales-tab-pane is-hidden" data-sales-pane="shipping" role="tabpanel" aria-hidden="true">
        <section class="card sales-card shadow-sm">
            <div class="sales-section-header">
                <div class="sales-kicker mb-1">Fulfillment marketplace</div>
                <h2 class="sales-section-title mb-1">Status pengiriman</h2>
                <div class="sales-section-subtitle">Jumlah order berdasarkan status yang tersimpan di marketplace.</div>
            </div>
            @if ($shipping->isEmpty())
                <div class="sales-empty text-center"><i class="bi bi-truck d-block fs-3 mb-2"></i>Belum ada data pengiriman pada periode ini.</div>
            @else
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle sales-table">
                        <thead><tr><th class="ps-3">Status</th><th class="text-end pe-3">Pesanan</th></tr></thead>
                        <tbody>
                            @foreach ($shipping as $status)
                                <tr><td class="ps-3 fw-semibold">{{ $status->label }}</td><td class="text-end pe-3">{{ number_format((int) $status->orders) }}</td></tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const tabs = document.querySelectorAll('[data-sales-tab]');
        const panes = document.querySelectorAll('[data-sales-pane]');

        tabs.forEach(function (tab) {
            tab.addEventListener('click', function () {
                const target = tab.dataset.salesTab;

                tabs.forEach(function (item) {
                    const active = item === tab;
                    item.classList.toggle('active', active);
                    item.setAttribute('aria-selected', active ? 'true' : 'false');
                });

                panes.forEach(function (pane) {
                    const active = pane.dataset.salesPane === target;
                    pane.classList.toggle('is-hidden', !active);
                    pane.setAttribute('aria-hidden', active ? 'false' : 'true');
                });
            });
        });
    });
</script>
@endpush
