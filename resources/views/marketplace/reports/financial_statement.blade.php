@extends('layouts.app')

@section('title', 'Marketplace • Laporan Keuangan')

@php
    $fmt = fn ($n) => number_format((float) $n, 0, ',', '.');
    $pct = fn ($n) => number_format((float) $n, 2, ',', '.') . '%';
    $summary = $statement['summary'];
    $quality = $statement['quality'];
    $filters = $statement['filters'];
    $includeShipped = ($filters['report_scope'] ?? 'final') === 'include_shipped';
    $dataLastDate = !empty($summary['data_last_order_at'])
        ? \Carbon\Carbon::parse($summary['data_last_order_at'])->translatedFormat('d M Y')
        : 'Belum ada';
    $operatingProfitAfterWalletAds = (float) ($summary['operating_profit_after_wallet_ads'] ?? $summary['operating_profit'] ?? 0);
    $adCostSource = str_replace('_', ' ', $summary['ad_cost_for_gl_source'] ?? 'none');
    $visibleItems = array_slice($statement['items'], 0, 100);
    $visibleOrders = array_slice($statement['orders'], 0, 100);
@endphp

@push('head')
<style>
    .mp-finance-page { max-width:1040px; margin-inline:auto; padding:.75rem .75rem 4rem; }
    .mp-finance-page .card { border-radius:8px; border-color:rgba(148,163,184,.18); box-shadow:none!important; }
    .mp-finance-page .mp-finance-hero { background:var(--card,#fff); border:1px solid rgba(148,163,184,.18); padding:.45rem .75rem; border-radius:8px; }
    .mp-finance-page .mp-eyebrow { text-transform:none; letter-spacing:0; font-size:.72rem; font-weight:650; color:#64748b; }
    .mp-finance-page .mp-finance-hero h4 { font-weight:750; letter-spacing:0; font-size:1rem; }
    .mp-finance-page .mp-finance-hero p { font-size:.78rem; }
    .mp-finance-page .page-actions .btn { border-radius:7px; font-weight:600; font-size:.73rem; padding:.3rem .65rem; }
    .mp-finance-page .page-actions .btn-primary { background:#334155; border-color:#334155; color:#fff; }
    .mp-finance-page .mp-filter-card .card-body { padding:.75rem .85rem; }
    .mp-finance-page .mp-section-title { display:flex; align-items:center; gap:.45rem; text-transform:none; letter-spacing:0; font-size:.78rem; font-weight:750; color:#334155; }
    .mp-finance-page .mp-section-title i { color:#64748b; }
    .mp-finance-page .summary-card { border-width: 0 0 0 4px !important; }
    .mp-finance-page .summary-card .card-body { min-height:0; padding:.7rem .85rem; display:flex; flex-direction:column; justify-content:center; }
    .mp-finance-page .summary-card .text-muted { font-size:.66rem!important; color:#94a3b8!important; }
    .mp-finance-page .summary-card .fs-5 { font-size:1.2rem!important; font-weight:650!important; }
    .mp-finance-page .form-select, .mp-finance-page .form-control { min-height:34px; border-radius:8px; font-size:.8rem; }
    .mp-finance-page .table th { white-space: nowrap; }
    .mp-finance-page .mp-status-strip { background:#f8fafc; border:1px solid rgba(148,163,184,.2); border-radius:8px; padding:.5rem .7rem; }
    .mp-finance-page .mp-status-strip .badge { font-weight:700; letter-spacing:.01em; }
    .mp-finance-page .mp-tab-nav { position:sticky; top:.5rem; z-index:10; background:var(--card,#fff); border:1px solid rgba(148,163,184,.18); border-radius:8px; padding:.25rem; gap:.25rem; }
    .mp-finance-page .mp-tab-nav .nav-link { border-radius:999px; color:#475569; font-weight:700; font-size:.75rem; padding:.3rem .75rem; }
    .mp-finance-page .mp-tab-nav .nav-link.active { background:#334155; color:#fff; }
    .mp-finance-page .mp-table-caption { color:#64748b; font-size:.78rem; }
    .mp-finance-page .table-responsive { max-height:60vh; overflow:auto; }
    .mp-finance-page .table thead th { position:sticky; top:0; z-index:2; background:var(--card,#fff); font-size:.64rem; text-transform:uppercase; letter-spacing:.04em; color:#64748b; }
    .mp-finance-page .table td { font-size:.78rem; }
    @media (max-width: 767.98px) {
        .mp-finance-page { padding-left:.65rem; padding-right:.65rem; }
        .mp-finance-page .page-actions { width:100%; }
        .mp-finance-page .page-actions .btn { flex:1 1 auto; }
        .mp-finance-page .summary-card .card-body { padding:.65rem; }
        .mp-finance-page .summary-card .fs-5 { font-size:1rem !important; }
    }
</style>
@endpush

@section('content')
<div class="container-fluid py-3 mp-finance-page">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3 mp-finance-hero">
        <div>
            <div class="mp-eyebrow">Marketplace · Owner finance</div>
            <h4 class="mb-1">Laporan Keuangan Marketplace</h4>
            <p class="text-muted mb-0" style="max-width: 820px">
                Laporan subledger marketplace berdasarkan settlement terverifikasi. Mode berjalan dapat menampilkan order shipped
                sebagai piutang provisional; hanya mode final yang dapat diposting ke jurnal umum.
            </p>
        </div>
        <div class="d-flex flex-wrap gap-2 page-actions">
            <a class="btn btn-outline-secondary" href="{{ route('marketplace.reports.profit', $filters) }}"><i class="bi bi-graph-up-arrow me-1"></i> Detail Profit</a>
            <a class="btn btn-outline-warning" href="{{ route('marketplace.reports.financial-closing', $filters) }}"><i class="bi bi-lock-fill me-1"></i> Closing & Audit</a>
            <form method="POST" action="{{ route('marketplace.reports.financial-statement.sync-ad-wallet') }}">
                @csrf
                @foreach ($filters as $key => $value)
                    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                @endforeach
                <button class="btn btn-outline-primary" type="submit" title="Tarik biaya iklan wallet Shopee untuk periode ini"><i class="bi bi-arrow-repeat me-1"></i> Sync biaya iklan</button>
            </form>
            <a class="btn btn-primary" href="{{ route('marketplace.reports.financial-statement.export', $filters) }}"><i class="bi bi-download me-1"></i> Export Statement</a>
        </div>
    </div>

    <div class="card shadow-sm mb-3 mp-filter-card">
        <div class="card-body">
            <div class="mp-section-title mb-3"><i class="bi bi-funnel"></i> Filter laporan</div>
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Toko</label>
                    <select name="store_id" class="form-select">
                        <option value="">Semua toko</option>
                        @foreach ($stores as $store)
                            <option value="{{ $store->id }}" @selected((int) ($filters['store_id'] ?? 0) === (int) $store->id)>{{ $store->name }} · {{ strtoupper($store->channel?->code ?? '-') }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Mode laporan</label>
                    <div class="btn-group w-100" role="group" aria-label="Mode laporan">
                        <input type="radio" class="btn-check" name="report_scope" id="scope-final" value="final" @checked(!$includeShipped)>
                        <label class="btn btn-outline-secondary" for="scope-final">Final</label>
                        <input type="radio" class="btn-check" name="report_scope" id="scope-shipped" value="include_shipped" @checked($includeShipped)>
                        <label class="btn btn-outline-info" for="scope-shipped">+ Shipped</label>
                    </div>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Basis tanggal</label>
                    <select name="date_basis" class="form-select">
                        <option value="ordered_at" @selected($filters['date_basis'] === 'ordered_at')>Tanggal order</option>
                        <option value="settlement_time" @selected($filters['date_basis'] === 'settlement_time')>Tanggal cair</option>
                    </select>
                </div>
                <div class="col-md-2"><label class="form-label small fw-semibold">Dari</label><input type="date" name="date_from" class="form-control" value="{{ $filters['date_from'] }}"></div>
                <div class="col-md-2"><label class="form-label small fw-semibold">Sampai</label><input type="date" name="date_to" class="form-control" value="{{ $filters['date_to'] }}"></div>
                <div class="col-md-auto d-flex gap-2">
                    <button class="btn btn-outline-primary"><i class="bi bi-funnel me-1"></i> Terapkan</button>
                    <a class="btn btn-outline-secondary" href="{{ route('marketplace.reports.financial-statement') }}" title="Kembalikan filter default"><i class="bi bi-arrow-counterclockwise"></i></a>
                </div>
            </form>
        </div>
    </div>

    @if ($quality['incomplete'] > 0 || $quality['unknown'] > 0)
        <div class="alert alert-warning d-flex gap-2 align-items-start">
            <i class="bi bi-shield-exclamation fs-5"></i>
            <div><div class="fw-semibold">Laporan ini memakai quality gate.</div><div class="small">{{ $fmt($quality['incomplete'] + $quality['unknown']) }} order belum lengkap dan tidak masuk nilai laporan. <a class="alert-link" href="{{ route('marketplace.reports.financial-quality', ['store_id' => $filters['store_id']]) }}">Audit data sekarang</a>.</div></div>
        </div>
    @endif

    @if ($includeShipped)
        <div class="alert alert-info d-flex gap-2 align-items-start">
            <i class="bi bi-info-circle fs-5"></i>
            <div>
                <div class="fw-semibold">Mode berjalan: shipped ikut dihitung sebagai provisional.</div>
                <div class="small">{{ $fmt($summary['provisional_order_count'] ?? 0) }} order shipped dengan settlement lengkap ditampilkan sebagai piutang marketplace. Angka ini belum boleh diposting ke pendapatan final sampai order menjadi COMPLETED.</div>
            </div>
        </div>
    @endif

    <div class="mp-status-strip d-flex flex-wrap align-items-center gap-2 mb-3">
        <span class="small fw-semibold text-dark"><i class="bi bi-database-check me-1 text-success"></i>Data terakhir: {{ $dataLastDate }}</span>
        <span class="badge rounded-pill bg-success-subtle text-success-emphasis">{{ $fmt($summary['final_order_count'] ?? $summary['order_count']) }} final</span>
        @if ($includeShipped)
            <span class="badge rounded-pill bg-info-subtle text-info-emphasis">{{ $fmt($summary['provisional_order_count'] ?? 0) }} shipped provisional</span>
        @endif
        <a class="small ms-auto" href="{{ route('marketplace.reports.financial-quality', ['store_id' => $filters['store_id'], 'date_from' => $filters['date_from'], 'date_to' => $filters['date_to']]) }}">Buka audit kualitas <i class="bi bi-arrow-up-right"></i></a>
    </div>

    @if (session('status'))
        <div class="alert alert-success"><i class="bi bi-check-circle me-1"></i>{{ session('status') }}</div>
    @endif

    <div class="card shadow-sm border-primary mb-4">
        <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
            <div class="text-uppercase text-primary small fw-semibold">Kontrol posting accounting</div>
                @if ($posting?->status === 'posted')
                    <div class="fw-semibold">Periode ini sudah posted ke jurnal umum.</div>
                    <div class="small text-muted">Journal #{{ $posting->journal_id }} · Posted {{ optional($posting->posted_at)->format('d M Y H:i') }} · Scope tersimpan sebagai snapshot audit.</div>
                @elseif ($posting?->status === 'void')
                    <div class="fw-semibold text-danger">Posting periode ini sudah di-void.</div>
                    <div class="small text-muted">Journal asal #{{ $posting->journal_id }} · {{ $posting->void_reason }}</div>
                @elseif (!$includeShipped && $quality['incomplete'] === 0 && $quality['unknown'] === 0 && $summary['order_count'] > 0)
                    <div class="fw-semibold">Settlement siap direview sebelum masuk jurnal.</div>
                    <div class="small text-muted">Posting dibuat satu batch per toko/periode dan aman diulang karena idempotent.</div>
                @elseif ($includeShipped)
                    <div class="fw-semibold text-info">Mode provisional — belum dapat diposting ke jurnal final.</div>
                    <div class="small text-muted">Kembalikan Mode laporan ke Final setelah order shipped berubah menjadi COMPLETED.</div>
                @else
                    <div class="fw-semibold text-muted">Posting belum tersedia.</div>
                    <div class="small text-muted">Selesaikan quality gate dan pastikan periode memiliki order ready.</div>
                @endif
            </div>
            <div class="d-flex flex-wrap gap-2">
                @if (!$includeShipped && !$posting && $quality['incomplete'] === 0 && $quality['unknown'] === 0 && $summary['order_count'] > 0)
                    <a class="btn btn-primary" href="{{ route('marketplace.reports.financial-statement.posting-preview', $filters) }}"><i class="bi bi-journal-check me-1"></i> Review posting</a>
                @elseif ($posting?->status === 'posted')
                    <form method="POST" action="{{ route('marketplace.reports.financial-statement.postings.void', $posting) }}" onsubmit="return confirm('Void posting ini? Jurnal asal akan di-void dan reversal audit dibuat.');">
                        @csrf
                        <input type="hidden" name="reason" value="Koreksi posting marketplace oleh owner">
                        <button class="btn btn-outline-danger"><i class="bi bi-arrow-counterclockwise me-1"></i> Void & reversal</button>
                    </form>
                @endif
            </div>
        </div>
    </div>

    <ul class="nav nav-pills mp-tab-nav mb-3" id="financialStatementTabs" role="tablist">
        <li class="nav-item" role="presentation"><button class="nav-link active" data-bs-toggle="pill" data-bs-target="#tab-summary" type="button" role="tab">Ringkasan</button></li>
        <li class="nav-item" role="presentation"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#tab-stores" type="button" role="tab">Per toko</button></li>
        <li class="nav-item" role="presentation"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#tab-products" type="button" role="tab">Per produk</button></li>
        <li class="nav-item" role="presentation"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#tab-orders" type="button" role="tab">Detail order</button></li>
        <li class="nav-item" role="presentation"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#tab-trend" type="button" role="tab">Trend</button></li>
    </ul>

    <div class="tab-content" id="financialStatementTabContent">
    <div class="tab-pane fade show active" id="tab-summary" role="tabpanel">
    <div class="row g-3 mb-4">
        @foreach ([
            ['label' => $includeShipped ? 'Penjualan bersih berjalan' : 'Penjualan bersih', 'value' => $summary['net_sales_before_settlement'], 'class' => 'primary'],
            ['label' => $includeShipped ? 'Payout berjalan' : 'Payout aktual', 'value' => $summary['payout'], 'class' => 'info'],
            ['label' => $includeShipped ? 'Laba kotor berjalan' : 'Laba kotor', 'value' => $summary['gross_profit'], 'class' => 'success'],
            ['label' => $includeShipped ? 'Laba operasional berjalan' : 'Laba operasional setelah iklan', 'value' => $operatingProfitAfterWalletAds, 'class' => 'success'],
            ['label' => 'Margin operasional', 'value' => $pct($summary['margin_pct']), 'class' => 'dark', 'text' => true],
        ] as $card)
            <div class="col-6 col-xl-{{ $loop->last ? '2' : '2' }}"><div class="card summary-card shadow-sm h-100 border-{{ $card['class'] }}"><div class="card-body"><div class="text-muted small">{{ $card['label'] }}</div><div class="fs-5 fw-bold text-{{ $card['class'] }} mt-1">{!! !empty($card['text']) ? $card['value'] : 'Rp ' . $fmt($card['value']) !!}</div></div></div></div>
        @endforeach
    </div>

    @if ($includeShipped)
        <div class="card shadow-sm border-info mb-4">
            <div class="card-body">
                <div class="mp-section-title mb-3"><i class="bi bi-wallet2"></i> Piutang marketplace provisional</div>
                <div class="row g-3">
                    <div class="col-6 col-md-3"><div class="text-muted small">Order final</div><div class="fs-5 fw-bold">{{ $fmt($summary['final_order_count'] ?? 0) }}</div></div>
                    <div class="col-6 col-md-3"><div class="text-muted small">Order shipped</div><div class="fs-5 fw-bold text-info">{{ $fmt($summary['provisional_order_count'] ?? 0) }}</div></div>
                    <div class="col-6 col-md-3"><div class="text-muted small">Payout final</div><div class="fs-5 fw-bold">Rp {{ $fmt($summary['final']['payout'] ?? 0) }}</div></div>
                    <div class="col-6 col-md-3"><div class="text-muted small">Piutang provisional</div><div class="fs-5 fw-bold text-info">Rp {{ $fmt($summary['provisional_receivable'] ?? 0) }}</div></div>
                </div>
                <div class="small text-muted mt-3">Piutang provisional hanya memakai order SHIPPED dengan settlement lengkap. Reklasifikasi ke penjualan final dilakukan setelah status COMPLETED.</div>
            </div>
        </div>
    @endif

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <div class="mp-section-title mb-3"><i class="bi bi-speedometer2"></i> KPI audit marketplace</div>
            <div class="row g-3">
                @foreach ([
                    ['label' => 'Payout rate', 'value' => $pct($summary['payout_rate_pct'])],
                    ['label' => 'Fee rate', 'value' => $pct($summary['fee_rate_pct'])],
                    ['label' => 'HPP rate', 'value' => $pct($summary['hpp_rate_pct'])],
                    ['label' => 'Nilai order rata-rata', 'value' => 'Rp ' . $fmt($summary['average_order_value'])],
                    ['label' => 'Refund rate', 'value' => $pct($summary['refund_rate_pct'])],
                    ['label' => 'Iklan wallet aktual', 'value' => 'Rp ' . $fmt($summary['wallet_ad_cost'] ?? 0)],
                    ['label' => 'Selisih iklan', 'value' => 'Rp ' . $fmt($summary['ad_cost_variance'] ?? 0)],
                    ['label' => 'Data terakhir', 'value' => $dataLastDate],
                    ['label' => 'Anomali payout', 'value' => $fmt($summary['payout_anomaly_count'] ?? 0) . ' order'],
                ] as $kpi)
                    <div class="col-6 col-md-3 col-xl-{{ $loop->index < 4 ? '2' : '2' }}">
                        <div class="border rounded-3 h-100 p-3">
                            <div class="text-muted small">{{ $kpi['label'] }}</div>
                            <div class="fw-bold mt-1">{{ $kpi['value'] }}</div>
                        </div>
                    </div>
                @endforeach
            </div>
            @if (($summary['payout_anomaly_count'] ?? 0) > 0)
                <div class="alert alert-warning small mt-3 mb-0"><i class="bi bi-exclamation-triangle me-1"></i> Ada order dengan payout jauh lebih besar dari omzet. Audit raw settlement sebelum posting jurnal.</div>
            @endif
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-lg-7">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white"><div class="mp-section-title"><i class="bi bi-bar-chart-line"></i> Laporan Laba Rugi Subledger</div></div>
                <div class="table-responsive"><table class="table table-sm align-middle mb-0">
                    <tbody>
                        <tr><td>Omzet customer</td><td class="text-end">Rp {{ $fmt($summary['gross_sales']) }}</td></tr>
                        <tr><td>Diskon seller</td><td class="text-end text-danger">(Rp {{ $fmt($summary['seller_discount']) }})</td></tr>
                        <tr class="table-light fw-semibold"><td>Penjualan bersih sebelum settlement</td><td class="text-end">Rp {{ $fmt($summary['net_sales_before_settlement']) }}</td></tr>
                        <tr><td>Fee marketplace</td><td class="text-end text-danger">(Rp {{ $fmt($summary['marketplace_fees']) }})</td></tr>
                        <tr><td>Refund/adjustment</td><td class="text-end text-danger">(Rp {{ $fmt($summary['refund']) }})</td></tr>
                        <tr><td>Penyesuaian settlement lainnya</td><td class="text-end">Rp {{ $fmt($summary['other_settlement_adjustment']) }}</td></tr>
                        <tr class="table-info fw-semibold"><td>Payout aktual</td><td class="text-end">Rp {{ $fmt($summary['payout']) }}</td></tr>
                        <tr><td>HPP</td><td class="text-end text-danger">(Rp {{ $fmt($summary['hpp']) }})</td></tr>
                        <tr class="table-light fw-semibold"><td>Laba kotor</td><td class="text-end">Rp {{ $fmt($summary['gross_profit']) }}</td></tr>
                        <tr><td>Biaya iklan wallet aktual</td><td class="text-end text-danger">(Rp {{ $fmt($summary['wallet_ad_cost'] ?? 0) }})</td></tr>
                        <tr><td>Biaya iklan masuk subledger <span class="text-muted small">({{ $adCostSource }})</span></td><td class="text-end text-danger">(Rp {{ $fmt($summary['ad_cost_for_gl'] ?? 0) }})</td></tr>
                        <tr class="table-success fw-bold"><td>Laba operasional setelah iklan</td><td class="text-end">Rp {{ $fmt($operatingProfitAfterWalletAds) }}</td></tr>
                    </tbody>
                </table></div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white"><div class="mp-section-title"><i class="bi bi-arrow-left-right"></i> Rekonsiliasi payout</div></div>
                <div class="card-body">
                    @foreach ([
                        ['label' => 'Payout dasar sebelum penyesuaian', 'value' => $statement['reconciliation']['expected_payout_before_other_adjustments']],
                        ['label' => 'Penyesuaian settlement lain', 'value' => $statement['reconciliation']['other_settlement_adjustment']],
                        ['label' => 'Payout aktual', 'value' => $statement['reconciliation']['actual_payout']],
                        ['label' => 'Selisih rekonsiliasi', 'value' => $statement['reconciliation']['difference']],
                    ] as $line)
                        <div class="d-flex justify-content-between border-bottom py-2"><span class="small">{{ $line['label'] }}</span><strong>Rp {{ $fmt($line['value']) }}</strong></div>
                    @endforeach
                    @if (abs((float) $statement['reconciliation']['difference']) > 0.01)
                        <div class="alert alert-warning small mt-3 mb-0"><i class="bi bi-exclamation-triangle me-1"></i> Selisih payout perlu diaudit pada voucher, ongkir, pajak, refund, atau adjustment settlement.</div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-warning mb-4">
        <div class="card-body">
            <div class="mp-section-title mb-3"><i class="bi bi-megaphone"></i> Rekonsiliasi biaya iklan</div>
            <div class="row g-3">
                <div class="col-6 col-md-3"><div class="text-muted small">Charge wallet</div><div class="fw-bold">Rp {{ $fmt($summary['wallet_ad_charge'] ?? 0) }}</div></div>
                <div class="col-6 col-md-3"><div class="text-muted small">Refund wallet</div><div class="fw-bold">Rp {{ $fmt($summary['wallet_ad_refund'] ?? 0) }}</div></div>
                <div class="col-6 col-md-3"><div class="text-muted small">Ads Daily spend</div><div class="fw-bold">Rp {{ $fmt($summary['ads_daily_spend'] ?? 0) }}</div></div>
                <div class="col-6 col-md-3"><div class="text-muted small">Selisih</div><div class="fw-bold {{ abs((float) ($summary['ad_cost_variance'] ?? 0)) > 0.01 ? 'text-warning-emphasis' : 'text-success' }}">Rp {{ $fmt($summary['ad_cost_variance'] ?? 0) }}</div></div>
            </div>
            <div class="small text-muted mt-3">Biaya wallet memakai tanggal transaksi Shopee. Jika wallet belum tersinkron untuk toko tertentu, Ads Daily dipakai sebagai fallback agar biaya tetap masuk subledger. Saat posting, nilainya masuk ke akun 6206 dan mengurangi saldo marketplace 1302; angka ini bersifat period-level dan tidak dialokasikan ulang ke laba per order.</div>
            @if (($summary['ad_cost_for_gl_source'] ?? 'none') !== 'wallet_actual' && ($summary['ad_cost_for_gl'] ?? 0) > 0)
                <div class="alert alert-warning small mt-3 mb-0"><i class="bi bi-info-circle me-1"></i> Sumber posting saat ini: <strong>{{ $adCostSource }}</strong>. Klik <strong>Sync biaya iklan</strong> sebelum posting final agar angka wallet Shopee menjadi sumber utama bila tersedia.</div>
            @endif
        </div>
    </div>
    </div>

    <div class="tab-pane fade" id="tab-stores" role="tabpanel">
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-white"><div class="mp-section-title"><i class="bi bi-shop"></i> Performa per toko</div></div>
        <div class="table-responsive"><table class="table table-sm table-hover align-middle mb-0"><thead class="table-light"><tr><th>Toko</th><th class="text-end">Order</th><th class="text-end">Penjualan bersih</th><th class="text-end">Payout</th><th class="text-end">HPP</th><th class="text-end">Laba operasional</th><th class="text-end">Margin</th></tr></thead><tbody>
            @forelse ($statement['stores'] as $row)
                <tr><td><div class="fw-semibold">{{ $row['store_name'] }}</div><div class="text-muted small">{{ strtoupper($row['channel']) }}</div></td><td class="text-end">{{ $fmt($row['order_count']) }}</td><td class="text-end">Rp {{ $fmt($row['net_sales_before_settlement']) }}</td><td class="text-end">Rp {{ $fmt($row['payout']) }}</td><td class="text-end">Rp {{ $fmt($row['hpp']) }}</td><td class="text-end fw-semibold">Rp {{ $fmt($row['operating_profit']) }}</td><td class="text-end">{{ $pct($row['margin_pct']) }}</td></tr>
            @empty
                <tr><td colspan="7" class="text-center text-muted py-4">Belum ada data.</td></tr>
            @endforelse
        </tbody></table></div>
    </div>
    </div>

    <div class="tab-pane fade" id="tab-products" role="tabpanel">
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-white d-flex justify-content-between align-items-center"><div class="mp-section-title"><i class="bi bi-box-seam"></i> Performa per produk</div><span class="mp-table-caption">{{ $fmt(count($visibleItems)) }} dari {{ $fmt(count($statement['items'])) }} SKU</span></div>
        <div class="table-responsive"><table class="table table-sm table-hover align-middle mb-0"><thead class="table-light"><tr><th>SKU</th><th>Produk</th><th class="text-end">Qty</th><th class="text-end">Payout</th><th class="text-end">HPP</th><th class="text-end">Laba</th><th class="text-end">Margin</th></tr></thead><tbody>
            @forelse ($visibleItems as $row)
                <tr><td class="fw-semibold">{{ $row['sku'] }}</td><td style="min-width:280px">{{ $row['item_name'] }}</td><td class="text-end">{{ $fmt($row['qty']) }}</td><td class="text-end">Rp {{ $fmt($row['payout']) }}</td><td class="text-end">Rp {{ $fmt($row['hpp']) }}</td><td class="text-end fw-semibold">Rp {{ $fmt($row['operating_profit']) }}</td><td class="text-end">{{ $pct($row['margin_pct']) }}</td></tr>
            @empty
                <tr><td colspan="7" class="text-center text-muted py-4">Belum ada data produk.</td></tr>
            @endforelse
        </tbody></table></div>
    </div>
    </div>

    <div class="tab-pane fade" id="tab-orders" role="tabpanel">
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-white d-flex justify-content-between align-items-center"><div class="mp-section-title"><i class="bi bi-receipt"></i> Detail order</div><span class="mp-table-caption">{{ $fmt(count($visibleOrders)) }} dari {{ $fmt(count($statement['orders'])) }} order</span></div>
        <div class="table-responsive"><table class="table table-sm table-hover align-middle mb-0"><thead class="table-light"><tr><th>Order</th><th>Tanggal</th><th>Status</th><th>Toko</th><th class="text-end">Omzet</th><th class="text-end">Payout</th><th class="text-end">HPP</th><th class="text-end">Laba</th></tr></thead><tbody>
            @forelse ($visibleOrders as $row)
                <tr><td class="fw-semibold">{{ $row['channel_order_id'] }}</td><td>{{ $row['ordered_at'] ?: '-' }}</td><td><span class="badge rounded-pill {{ ($row['recognition_status'] ?? 'final') === 'provisional' ? 'bg-info-subtle text-info-emphasis' : 'bg-success-subtle text-success-emphasis' }}">{{ ($row['recognition_status'] ?? 'final') === 'provisional' ? 'Shipped · provisional' : 'Final' }}</span></td><td>{{ $row['store_name'] }}</td><td class="text-end">Rp {{ $fmt($row['gross_sales']) }}</td><td class="text-end">Rp {{ $fmt($row['payout']) }}</td><td class="text-end">Rp {{ $fmt($row['hpp']) }}</td><td class="text-end fw-semibold">Rp {{ $fmt($row['operating_profit']) }}</td></tr>
            @empty
                <tr><td colspan="8" class="text-center text-muted py-4">Belum ada order pada periode ini.</td></tr>
            @endforelse
        </tbody></table></div>
    </div>
    </div>

    <div class="tab-pane fade" id="tab-trend" role="tabpanel">
    <div class="card shadow-sm">
        <div class="card-header bg-white"><div class="mp-section-title"><i class="bi bi-graph-up"></i> Trend laba operasional</div></div>
        <div class="table-responsive"><table class="table table-sm table-hover align-middle mb-0"><thead class="table-light"><tr><th>Tanggal</th><th class="text-end">Order</th><th class="text-end">Penjualan bersih</th><th class="text-end">Payout</th><th class="text-end">HPP</th><th class="text-end">Biaya iklan</th><th class="text-end">Laba operasional</th></tr></thead><tbody>
            @forelse ($statement['daily'] as $row)
                <tr><td>{{ $row['date'] ?: 'Tanpa tanggal' }}</td><td class="text-end">{{ $fmt($row['order_count']) }}</td><td class="text-end">Rp {{ $fmt($row['net_sales_before_settlement']) }}</td><td class="text-end">Rp {{ $fmt($row['payout']) }}</td><td class="text-end">Rp {{ $fmt($row['hpp']) }}</td><td class="text-end">Rp {{ $fmt($row['ad_cost']) }}</td><td class="text-end fw-semibold">Rp {{ $fmt($row['operating_profit']) }}</td></tr>
            @empty
                <tr><td colspan="7" class="text-center text-muted py-4">Belum ada data.</td></tr>
            @endforelse
        </tbody></table></div>
    </div>
</div>
</div>
</div>
@endsection
