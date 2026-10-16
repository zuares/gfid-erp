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

    /* Enterprise finance treatment: stronger hierarchy, restrained color,
       and dense audit-friendly surfaces while preserving the existing data. */
    .mp-finance-page { --mp-ink:#12233d; --mp-muted:#64748b; --mp-line:#e2e8f0; max-width:1440px; padding:.9rem 1.15rem 4rem; color:#1e293b; }
    .mp-finance-page .card { border-radius:14px; border-color:var(--mp-line); box-shadow:0 5px 18px rgba(15,23,42,.035)!important; }
    .mp-finance-page .mp-finance-hero { position:relative; overflow:hidden; background:var(--mp-ink); border-color:#203555; padding:1.35rem 1.5rem 1.15rem; border-radius:18px; color:#fff; box-shadow:0 14px 34px rgba(15,35,61,.16); }
    .mp-finance-page .mp-finance-hero::after { content:''; position:absolute; width:360px; height:360px; right:-150px; top:-190px; border:1px solid rgba(147,197,253,.15); border-radius:50%; box-shadow:0 0 0 28px rgba(147,197,253,.035), 0 0 0 56px rgba(147,197,253,.025); pointer-events:none; }
    .mp-finance-page .mp-eyebrow { display:flex; align-items:center; gap:.55rem; text-transform:uppercase; letter-spacing:.12em; font-size:.63rem; font-weight:800; color:#a9b7ca; }
    .mp-finance-page .mp-eyebrow-badge { display:inline-flex; align-items:center; gap:.35rem; padding:.24rem .5rem; border:1px solid rgba(191,219,254,.2); border-radius:999px; color:#dbeafe; background:rgba(59,130,246,.13); letter-spacing:.08em; }
    .mp-finance-page .mp-eyebrow-badge::before { content:''; width:6px; height:6px; border-radius:50%; background:#60a5fa; box-shadow:0 0 0 3px rgba(96,165,250,.16); }
    .mp-finance-page .mp-finance-hero h4 { position:relative; z-index:1; margin:.6rem 0 .35rem!important; font-weight:800; letter-spacing:-.03em; font-size:clamp(1.45rem,2.5vw,2rem); }
    .mp-finance-page .mp-finance-hero p { position:relative; z-index:1; color:#c7d2e2!important; font-size:.8rem; line-height:1.6; }
    .mp-finance-page .mp-hero-status { position:relative; z-index:1; min-width:176px; margin-top:.2rem; padding:.7rem .8rem; border:1px solid rgba(191,219,254,.16); border-radius:11px; background:rgba(15,23,42,.26); }
    .mp-finance-page .mp-hero-status strong { display:block; font-size:.76rem; color:#f8fafc; }
    .mp-finance-page .mp-hero-status small { display:block; margin-top:.22rem; color:#a9b7ca; font-size:.66rem; }
    .mp-finance-page .page-actions { position:relative; z-index:2; margin-top:1rem; padding-top:.9rem; border-top:1px solid rgba(191,219,254,.14); }
    .mp-finance-page .mp-finance-hero .page-actions { flex:1 0 100%; }
    .mp-finance-page .page-actions .btn { border-radius:8px; font-weight:700; font-size:.72rem; padding:.43rem .7rem; }
    .mp-finance-page .page-actions .btn-primary { background:#3b82f6; border-color:#3b82f6; }
    .mp-finance-page .page-actions .btn-outline-secondary { color:#dbeafe; border-color:rgba(191,219,254,.35); }
    .mp-finance-page .page-actions .btn-outline-warning { color:#fde68a; border-color:rgba(253,230,138,.4); }
    .mp-finance-page .page-actions .btn-outline-primary { color:#bfdbfe; border-color:rgba(147,197,253,.4); }
    .mp-finance-page .mp-finance-hero + .mp-filter-card { margin-top:1rem; }
    .mp-finance-page .mp-filter-card .card-body { padding:1rem 1.1rem 1.05rem; }
    .mp-finance-page .mp-panel-heading { display:flex; align-items:flex-start; justify-content:space-between; gap:1rem; margin-bottom:1rem; }
    .mp-finance-page .mp-panel-heading p { margin:.2rem 0 0; color:#64748b; font-size:.7rem; }
    .mp-finance-page .mp-filter-card .mp-section-title { font-size:.88rem; color:var(--mp-ink); }
    .mp-finance-page .mp-filter-card .form-label { margin-bottom:.35rem; color:#475569; font-size:.68rem; font-weight:800!important; letter-spacing:.02em; }
    .mp-finance-page .form-select, .mp-finance-page .form-control { min-height:37px; border-color:#d7e0eb; border-radius:8px; font-size:.77rem; box-shadow:none; }
    .mp-finance-page .form-select:focus, .mp-finance-page .form-control:focus { border-color:#60a5fa; box-shadow:0 0 0 3px rgba(59,130,246,.12); }
    .mp-finance-page .mp-filter-card .btn { min-height:37px; border-radius:8px; font-size:.73rem; font-weight:750; }
    .mp-finance-page .mp-mode-switch { display:grid; grid-template-columns:1fr 1.2fr; gap:.25rem; padding:.22rem; min-height:37px; border:1px solid #d7e0eb; border-radius:8px; background:#f8fafc; }
    .mp-finance-page .mp-mode-switch .btn { border:0; border-radius:6px!important; color:#64748b; font-size:.69rem; font-weight:750; }
    .mp-finance-page .mp-mode-switch .btn-check:checked + .btn { background:#fff; color:#1d4ed8; box-shadow:0 1px 4px rgba(15,23,42,.11); }
    .mp-finance-page .summary-card { border-width:0 0 0 4px!important; overflow:hidden; }
    .mp-finance-page .summary-card .card-body { min-height:102px; padding:.9rem 1rem; }
    .mp-finance-page .summary-card .text-muted { color:#64748b!important; font-weight:700; }
    .mp-finance-page .summary-card .fs-5 { font-size:1.18rem!important; font-weight:800!important; letter-spacing:-.02em; }
    .mp-finance-page .mp-status-strip { background:#fff; border:1px solid var(--mp-line); border-radius:11px; padding:.65rem .8rem; box-shadow:0 3px 12px rgba(15,23,42,.025); }
    .mp-finance-page .mp-status-strip .badge { padding:.36rem .55rem; font-weight:750; }
    .mp-finance-page .mp-tab-nav { position:sticky; top:.5rem; z-index:10; background:rgba(255,255,255,.92); border:1px solid var(--mp-line); border-radius:11px; padding:.28rem; gap:.25rem; box-shadow:0 5px 18px rgba(15,23,42,.05); backdrop-filter:blur(10px); }
    .mp-finance-page .mp-tab-nav .nav-link { border-radius:7px; color:#64748b; font-weight:750; font-size:.72rem; padding:.45rem .8rem; }
    .mp-finance-page .mp-tab-nav .nav-link.active { background:var(--mp-ink); color:#fff; box-shadow:0 2px 5px rgba(15,35,61,.16); }
    .mp-finance-page .card-header { padding:.8rem 1rem; border-bottom-color:#eef2f7; background:#fff!important; }
    .mp-finance-page .table-responsive { max-height:60vh; overflow:auto; }
    .mp-finance-page .table thead th { background:#f8fafc; border-bottom:1px solid #e2e8f0; font-size:.62rem; text-transform:uppercase; letter-spacing:.06em; color:#64748b; }
    .mp-finance-page .table td { border-color:#eef2f7; font-size:.75rem; }
    .mp-finance-page .table tbody tr:hover { background:#f8fbff; }
    .mp-finance-page .mp-status-strip a { color:#2563eb; font-weight:700; text-decoration:none; }
    .mp-finance-page .mp-status-strip a:hover { text-decoration:underline; }

    /* Shared control language: every action uses the same height, rhythm,
       icon alignment, focus ring, and semantic color treatment. */
    .mp-finance-page .mp-btn { display:inline-flex; align-items:center; justify-content:center; gap:.42rem; min-height:37px; padding:.45rem .78rem; border-radius:8px!important; font-size:.73rem; font-weight:750; line-height:1.1; white-space:nowrap; transition:background-color .15s ease, border-color .15s ease, color .15s ease, box-shadow .15s ease, transform .15s ease; }
    .mp-finance-page .mp-btn i { margin:0!important; font-size:.9rem; line-height:1; }
    .mp-finance-page .mp-btn:hover { transform:translateY(-1px); }
    .mp-finance-page .mp-btn:focus-visible, .mp-finance-page .mp-mode-switch .btn:focus-visible, .mp-finance-page .mp-tab-nav .nav-link:focus-visible { outline:0; box-shadow:0 0 0 3px rgba(59,130,246,.2)!important; }
    .mp-finance-page .mp-btn-primary { color:#fff; background:#2563eb; border-color:#2563eb; box-shadow:0 2px 5px rgba(37,99,235,.18); }
    .mp-finance-page .mp-btn-primary:hover { color:#fff; background:#1d4ed8; border-color:#1d4ed8; }
    .mp-finance-page .mp-btn-secondary { color:#334155; background:#fff; border-color:#cbd5e1; }
    .mp-finance-page .mp-btn-secondary:hover { color:#1e293b; background:#f8fafc; border-color:#94a3b8; }
    .mp-finance-page .mp-btn-info { color:#075985; background:#eff6ff; border-color:#bfdbfe; }
    .mp-finance-page .mp-btn-info:hover { color:#0c4a6e; background:#dbeafe; border-color:#93c5fd; }
    .mp-finance-page .mp-btn-warning { color:#92400e; background:#fffbeb; border-color:#fcd34d; }
    .mp-finance-page .mp-btn-warning:hover { color:#78350f; background:#fef3c7; border-color:#f59e0b; }
    .mp-finance-page .mp-btn-danger { color:#b91c1c; background:#fff; border-color:#fca5a5; }
    .mp-finance-page .mp-btn-danger:hover { color:#991b1b; background:#fef2f2; border-color:#f87171; }
    .mp-finance-page .page-actions .mp-btn { min-height:35px; }
    .mp-finance-page .mp-filter-actions { align-items:stretch; }
    .mp-finance-page .mp-filter-actions .mp-btn-apply { min-width:128px; }
    .mp-finance-page .mp-filter-actions .mp-btn-reset { min-width:78px; }
    .mp-finance-page .mp-mode-switch .btn { min-height:31px; }
    .mp-finance-page .mp-mode-switch .btn-check:checked + .btn { color:#1d4ed8; }
    .mp-finance-page .mp-mode-switch .btn-check:checked + .btn[data-scope="provisional"] { color:#075985; background:#eff6ff; }
    .mp-finance-page .mp-tab-nav .nav-link { display:inline-flex; align-items:center; gap:.38rem; }
    .mp-finance-page .mp-tab-nav .nav-link i { font-size:.82rem; }
    @media (max-width:767.98px) {
        .mp-finance-page { padding-left:.65rem; padding-right:.65rem; }
        .mp-finance-page .mp-finance-hero { padding:1.1rem; border-radius:14px; }
        .mp-finance-page .mp-hero-status { width:100%; min-width:0; margin-top:.85rem; }
        .mp-finance-page .page-actions { width:100%; }
        .mp-finance-page .page-actions .btn, .mp-finance-page .page-actions form { flex:1 1 auto; }
        .mp-finance-page .page-actions form .btn { width:100%; }
        .mp-finance-page .mp-filter-actions { width:100%; }
        .mp-finance-page .mp-filter-actions .mp-btn { flex:1 1 0; }
        .mp-finance-page .summary-card .card-body { min-height:88px; padding:.7rem; }
        .mp-finance-page .summary-card .fs-5 { font-size:1rem!important; }
        .mp-finance-page .mp-tab-nav { overflow-x:auto; flex-wrap:nowrap; }
        .mp-finance-page .mp-tab-nav .nav-item { flex:0 0 auto; }
    }
</style>
@endpush

@section('content')
<div class="container-fluid py-3 mp-finance-page">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3 mp-finance-hero">
        <div>
            <div class="mp-eyebrow"><span class="mp-eyebrow-badge">Marketplace finance</span><span>Owner control center</span></div>
            <h4 class="mb-1">Laporan Keuangan Marketplace</h4>
            <p class="text-muted mb-0" style="max-width: 820px">
                Laporan subledger marketplace berdasarkan settlement terverifikasi. Mode berjalan dapat menampilkan order shipped/to_confirm_receive
                sebagai piutang provisional; hanya mode final yang dapat diposting ke jurnal umum.
            </p>
        </div>
        <div class="mp-hero-status">
            <strong><i class="bi bi-shield-check me-1 text-info"></i>{{ $includeShipped ? 'Provisional monitoring' : 'Final ledger view' }}</strong>
            <small>{{ $filters['date_from'] }} — {{ $filters['date_to'] }}</small>
        </div>
        <div class="d-flex flex-wrap gap-2 page-actions">
            <a class="btn mp-btn mp-btn-secondary" href="{{ route('marketplace.reports.profit', $filters) }}"><i class="bi bi-graph-up-arrow"></i> Lihat profit</a>
            <a class="btn mp-btn mp-btn-warning" href="{{ route('marketplace.reports.financial-closing', $filters) }}"><i class="bi bi-lock-fill"></i> Closing & audit</a>
            <form method="POST" action="{{ route('marketplace.reports.financial-statement.sync-ad-wallet') }}">
                @csrf
                @foreach ($filters as $key => $value)
                    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                @endforeach
                <button class="btn mp-btn mp-btn-info" type="submit" title="Tarik biaya iklan wallet Shopee untuk periode ini"><i class="bi bi-arrow-repeat"></i> Sinkronkan iklan</button>
            </form>
            <a class="btn mp-btn mp-btn-primary" href="{{ route('marketplace.reports.financial-statement.export', $filters) }}"><i class="bi bi-download"></i> Export laporan</a>
        </div>
    </div>

    <div class="card shadow-sm mb-3 mp-filter-card">
        <div class="card-body">
            <div class="mp-panel-heading">
                <div>
                    <div class="mp-section-title"><i class="bi bi-sliders2"></i> Parameter laporan</div>
                    <p>Atur scope pengakuan dan basis tanggal sebelum melakukan review.</p>
                </div>
                <span class="badge rounded-pill bg-light text-secondary border">{{ $filters['date_basis'] === 'settlement_time' ? 'Cash / settlement basis' : 'Order basis' }}</span>
            </div>
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
                    <div class="btn-group w-100 mp-mode-switch" role="group" aria-label="Mode laporan">
                        <input type="radio" class="btn-check" name="report_scope" id="scope-final" value="final" @checked(!$includeShipped)>
                        <label class="btn btn-outline-secondary" for="scope-final"><i class="bi bi-check2-circle"></i> Final ledger</label>
                        <input type="radio" class="btn-check" name="report_scope" id="scope-shipped" value="include_shipped" @checked($includeShipped)>
                        <label class="btn btn-outline-info" data-scope="provisional" for="scope-shipped"><i class="bi bi-hourglass-split"></i> Provisional</label>
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
                <div class="col-md-auto d-flex gap-2 mp-filter-actions">
                    <button class="btn mp-btn mp-btn-primary mp-btn-apply" type="submit"><i class="bi bi-funnel"></i> Terapkan filter</button>
                    <a class="btn mp-btn mp-btn-secondary mp-btn-reset" href="{{ route('marketplace.reports.financial-statement') }}" title="Kembalikan filter default"><i class="bi bi-arrow-counterclockwise"></i><span>Reset</span></a>
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
                <div class="fw-semibold">Mode berjalan: shipped dan to_confirm_receive ikut dihitung sebagai provisional.</div>
                <div class="small">{{ $fmt($summary['provisional_order_count'] ?? 0) }} order shipped/to_confirm_receive dengan settlement lengkap ditampilkan sebagai piutang marketplace. Angka ini belum boleh diposting ke pendapatan final sampai order menjadi COMPLETED.</div>
            </div>
        </div>
    @endif

    <div class="mp-status-strip d-flex flex-wrap align-items-center gap-2 mb-3">
        <span class="small fw-semibold text-dark"><i class="bi bi-database-check me-1 text-success"></i>Data terakhir: {{ $dataLastDate }}</span>
        <span class="badge rounded-pill bg-success-subtle text-success-emphasis">{{ $fmt($summary['final_order_count'] ?? $summary['order_count']) }} final</span>
        @if ($includeShipped)
            <span class="badge rounded-pill bg-info-subtle text-info-emphasis">{{ $fmt($summary['provisional_order_count'] ?? 0) }} provisional</span>
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
                    <a class="btn mp-btn mp-btn-primary" href="{{ route('marketplace.reports.financial-statement.posting-preview', $filters) }}"><i class="bi bi-journal-check"></i> Review jurnal</a>
                @elseif ($posting?->status === 'posted')
                    <form method="POST" action="{{ route('marketplace.reports.financial-statement.postings.void', $posting) }}" onsubmit="return confirm('Void posting ini? Jurnal asal akan di-void dan reversal audit dibuat.');">
                        @csrf
                        <input type="hidden" name="reason" value="Koreksi posting marketplace oleh owner">
                        <button class="btn mp-btn mp-btn-danger" type="submit"><i class="bi bi-arrow-counterclockwise"></i> Void & buat reversal</button>
                    </form>
                @endif
            </div>
        </div>
    </div>

    <ul class="nav nav-pills mp-tab-nav mb-3" id="financialStatementTabs" role="tablist">
        <li class="nav-item" role="presentation"><button class="nav-link active" data-bs-toggle="pill" data-bs-target="#tab-summary" type="button" role="tab" aria-controls="tab-summary" aria-selected="true"><i class="bi bi-grid-1x2"></i> Ringkasan</button></li>
        <li class="nav-item" role="presentation"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#tab-stores" type="button" role="tab" aria-controls="tab-stores" aria-selected="false"><i class="bi bi-shop"></i> Per toko</button></li>
        <li class="nav-item" role="presentation"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#tab-products" type="button" role="tab" aria-controls="tab-products" aria-selected="false"><i class="bi bi-box-seam"></i> Per produk</button></li>
        <li class="nav-item" role="presentation"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#tab-orders" type="button" role="tab" aria-controls="tab-orders" aria-selected="false"><i class="bi bi-receipt"></i> Detail order</button></li>
        <li class="nav-item" role="presentation"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#tab-trend" type="button" role="tab" aria-controls="tab-trend" aria-selected="false"><i class="bi bi-graph-up"></i> Trend</button></li>
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
                    <div class="col-6 col-md-3"><div class="text-muted small">Order provisional</div><div class="fs-5 fw-bold text-info">{{ $fmt($summary['provisional_order_count'] ?? 0) }}</div></div>
                    <div class="col-6 col-md-3"><div class="text-muted small">Payout final</div><div class="fs-5 fw-bold">Rp {{ $fmt($summary['final']['payout'] ?? 0) }}</div></div>
                    <div class="col-6 col-md-3"><div class="text-muted small">Piutang provisional</div><div class="fs-5 fw-bold text-info">Rp {{ $fmt($summary['provisional_receivable'] ?? 0) }}</div></div>
                </div>
                <div class="small text-muted mt-3">Piutang provisional memakai order SHIPPED dan TO_CONFIRM_RECEIVE dengan settlement lengkap. Reklasifikasi ke penjualan final dilakukan setelah status COMPLETED.</div>
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
                <div class="col-6 col-md-3"><div class="text-muted small">Top up saldo iklan</div><div class="fw-bold">Rp {{ $fmt($summary['wallet_ad_topup'] ?? 0) }}</div></div>
                <div class="col-6 col-md-3"><div class="text-muted small">Charge wallet</div><div class="fw-bold">Rp {{ $fmt($summary['wallet_ad_charge'] ?? 0) }}</div></div>
                <div class="col-6 col-md-3"><div class="text-muted small">Refund wallet</div><div class="fw-bold">Rp {{ $fmt($summary['wallet_ad_refund'] ?? 0) }}</div></div>
                <div class="col-6 col-md-3"><div class="text-muted small">Ads Daily spend</div><div class="fw-bold">Rp {{ $fmt($summary['ads_daily_spend'] ?? 0) }}</div></div>
                <div class="col-6 col-md-3"><div class="text-muted small">Selisih wallet vs Ads Daily</div><div class="fw-bold {{ abs((float) ($summary['ad_cost_variance'] ?? 0)) > 0.01 ? 'text-warning-emphasis' : 'text-success' }}">Rp {{ $fmt($summary['ad_cost_variance'] ?? 0) }}</div></div>
            </div>
            <div class="small text-muted mt-3">SPM top up dipindahkan dari 1302 ke 1304 sebagai saldo iklan prepaid. Ads Daily/wallet actual mengakui pemakaian ke akun 6206 dengan lawan 1304, sedangkan penarikan bank tetap menggunakan 1302. Angka ini bersifat period-level dan tidak dialokasikan ulang ke laba per order.</div>
            @if (!($summary['wallet_actual_available'] ?? false))
                <div class="alert alert-warning small mt-3 mb-0"><i class="bi bi-info-circle me-1"></i> Wallet aktual belum memiliki transaksi charge Paid Ads. Biaya iklan yang masuk GL tetap Rp0; Ads Daily hanya digunakan sebagai pembanding.</div>
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
                <tr><td class="fw-semibold">{{ $row['channel_order_id'] }}</td><td>{{ $row['ordered_at'] ?: '-' }}</td><td><span class="badge rounded-pill {{ ($row['recognition_status'] ?? 'final') === 'provisional' ? 'bg-info-subtle text-info-emphasis' : 'bg-success-subtle text-success-emphasis' }}">{{ ($row['recognition_status'] ?? 'final') === 'provisional' ? str_replace('_', ' ', strtoupper($row['order_status'] ?? 'provisional')) . ' · provisional' : 'Final' }}</span></td><td>{{ $row['store_name'] }}</td><td class="text-end">Rp {{ $fmt($row['gross_sales']) }}</td><td class="text-end">Rp {{ $fmt($row['payout']) }}</td><td class="text-end">Rp {{ $fmt($row['hpp']) }}</td><td class="text-end fw-semibold">Rp {{ $fmt($row['operating_profit']) }}</td></tr>
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
