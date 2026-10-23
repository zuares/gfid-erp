@extends('layouts.app')

@section('title', 'Marketplace • Preview Posting Settlement')

@php
    $fmt = fn ($n) => number_format((float) $n, 0, ',', '.');
    $statement = $preview['statement'];
    $summary = $statement['summary'];
    $quality = $statement['quality'] ?? [];
    $filters = $preview['filters'];
    $includeAds = (bool) ($preview['included_in_gl']['include_ads'] ?? true);
    $adsSource = $preview['included_in_gl']['source'] ?? 'none';
    $adsAmount = (float) ($preview['included_in_gl']['amount'] ?? 0);
    $adsFallback = $includeAds && $adsAmount > 0 && $adsSource !== 'wallet_actual';
    $balanceDifference = abs((float) $preview['total_debit'] - (float) $preview['total_credit']);
    $qualityIssues = (int) ($quality['incomplete'] ?? 0) + (int) ($quality['unknown'] ?? 0);
    $readyForPosting = $qualityIssues === 0
        && (int) ($summary['order_count'] ?? 0) > 0
        && $balanceDifference <= 0.01;
    $store = $filters['store_id']
        ? $stores->firstWhere('id', (int) $filters['store_id'])
        : null;
    $storeLabel = $store?->name ?? 'Semua toko';
    $scopeLabel = ($filters['report_scope'] ?? 'final') === 'final' ? 'Final' : 'Termasuk dikirim';
    $basisLabel = ($filters['date_basis'] ?? 'ordered_at') === 'settlement_time' ? 'Waktu settlement' : 'Tanggal order';
@endphp

@push('head')
<style>
    .mp-posting-page {
        --mp-ink: var(--gf-color-content, #101828); --mp-muted: var(--gf-color-muted, #667085); --mp-subtle: #98a2b3;
        --mp-border: var(--gf-color-line, #e4e7ec); --mp-soft: var(--gf-color-surface-muted, #f2f4f7);
        --mp-brand: var(--gf-color-brand, #5b5ce2); --mp-brand-dark: var(--gf-color-brand-strong, #4142bb);
        --mp-success: #16704c; --mp-warning: #986000; --mp-radius: var(--gf-radius-card, 16px);
        --mp-control-radius: var(--gf-radius-control, 10px); --mp-shadow: var(--gf-shadow-sm, 0 1px 2px rgba(16,24,40,.04), 0 6px 18px rgba(16,24,40,.04));
        max-width: var(--gf-content-max, 1280px); margin: 0 auto; padding: 1rem var(--gf-content-gutter, 1rem) 4rem; color: var(--mp-ink);
    }
    .mp-posting-page *, .mp-posting-page *::before, .mp-posting-page *::after { box-sizing: border-box; }
    .mp-breadcrumb { display: flex; align-items: center; gap: .45rem; margin-bottom: .75rem; color: var(--mp-subtle); font-size: .68rem; font-weight: 700; }
    .mp-breadcrumb a { color: var(--mp-muted); text-decoration: none; } .mp-breadcrumb a:hover { color: var(--mp-brand); }
    .mp-header { display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem; margin-bottom: .8rem; padding: 1.2rem 1.35rem; border: 1px solid rgba(91,92,226,.18); border-radius: 22px; background: linear-gradient(135deg, #f7f7ff 0%, #fff 58%, #effcfd 100%); box-shadow: var(--mp-shadow); }
    .mp-header-copy { min-width: 0; } .mp-eyebrow { margin-bottom: .3rem; color: var(--mp-brand); font-size: .67rem; font-weight: 850; letter-spacing: .1em; text-transform: uppercase; }
    .mp-title { margin: 0; color: var(--mp-ink); font-size: clamp(1.25rem, 2vw, 1.65rem); font-weight: 780; letter-spacing: -.03em; }
    .mp-description { margin: .35rem 0 0; color: var(--mp-muted); font-size: .78rem; line-height: 1.5; }
    .mp-header-meta { display: flex; flex-wrap: wrap; gap: .4rem; margin-top: .7rem; }
    .mp-chip { display: inline-flex; align-items: center; gap: .3rem; padding: .28rem .5rem; border: 1px solid var(--mp-border); border-radius: 999px; color: var(--mp-muted); background: rgba(255,255,255,.8); font-size: .65rem; font-weight: 750; }
    .mp-btn { border-radius: var(--mp-control-radius); padding: .48rem .7rem; font-size: .73rem; font-weight: 750; box-shadow: none !important; }
    .mp-btn:focus-visible, .mp-post-button:focus-visible { outline: 3px solid rgba(91,92,226,.28); outline-offset: 2px; }
    .mp-btn-primary, .mp-post-button { color: #fff !important; background: var(--mp-brand) !important; border-color: var(--mp-brand) !important; }
    .mp-btn-primary:hover, .mp-post-button:hover { background: var(--mp-brand-dark) !important; border-color: var(--mp-brand-dark) !important; }
    .mp-card { border: 1px solid var(--mp-border); border-radius: var(--mp-radius); background: #fff; box-shadow: var(--mp-shadow); }
    .mp-workflow { display: grid; grid-template-columns: auto minmax(0, 1fr); gap: .8rem 1rem; align-items: center; margin-bottom: .8rem; padding: .8rem 1rem; border: 1px solid #dbe4ff; border-radius: var(--mp-radius); background: linear-gradient(90deg, #f4f6ff, #f7ffff); }
    .mp-workflow-label { color: var(--mp-brand-dark); font-size: .68rem; font-weight: 850; letter-spacing: .04em; text-transform: uppercase; }
    .mp-workflow-steps { display: flex; flex-wrap: wrap; align-items: center; gap: .4rem; } .mp-workflow-step { display: inline-flex; align-items: center; gap: .3rem; color: var(--mp-ink); font-size: .68rem; font-weight: 750; }
    .mp-workflow-step b { display: grid; width: 21px; height: 21px; place-items: center; border-radius: 50%; color: #fff; background: var(--mp-brand); font-size: .6rem; } .mp-workflow-arrow { color: var(--mp-subtle); font-size: .68rem; }
    .mp-readiness { display: flex; align-items: center; justify-content: space-between; gap: 1rem; margin-bottom: .8rem; padding: .75rem .9rem; border: 1px solid; border-radius: var(--mp-radius); }
    .mp-readiness-good { color: var(--mp-success); background: #f0fdf7; border-color: #b7ead4; } .mp-readiness-warning { color: var(--mp-warning); background: #fffbeb; border-color: #f5d98a; }
    .mp-readiness-main { display: flex; align-items: flex-start; gap: .6rem; min-width: 0; } .mp-readiness-icon { display: grid; width: 28px; height: 28px; flex: 0 0 28px; place-items: center; border-radius: 8px; background: rgba(255,255,255,.75); font-size: .9rem; }
    .mp-readiness-title { margin: 0 0 .1rem; font-size: .77rem; font-weight: 850; } .mp-readiness-copy { margin: 0; color: inherit; opacity: .82; font-size: .69rem; line-height: 1.4; }
    .mp-status-pill { display: inline-flex; align-items: center; gap: .3rem; flex: 0 0 auto; padding: .28rem .5rem; border-radius: 999px; color: var(--mp-success); background: #dcfaeb; font-size: .65rem; font-weight: 800; white-space: nowrap; }
    .mp-status-pill::before { width: 5px; height: 5px; border-radius: 50%; background: currentColor; content: ''; } .mp-readiness-warning .mp-status-pill { color: var(--mp-warning); background: #fff0c2; }
    .mp-kpis { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: .65rem; margin-bottom: .8rem; } .mp-kpi { min-width: 0; min-height: 96px; padding: .8rem .85rem; }
    .mp-kpi-label { display: flex; align-items: center; gap: .35rem; color: var(--mp-muted); font-size: .68rem; font-weight: 750; } .mp-kpi-icon { display: grid; width: 22px; height: 22px; place-items: center; border-radius: 6px; color: var(--mp-brand); background: #eef4ff; font-size: .7rem; }
    .mp-kpi-value { display: block; margin-top: .5rem; overflow: hidden; color: var(--mp-ink); font-size: 1.22rem; font-weight: 800; letter-spacing: -.035em; line-height: 1.05; text-overflow: ellipsis; white-space: nowrap; } .mp-kpi-note { display: block; margin-top: .32rem; overflow: hidden; color: var(--mp-subtle); font-size: .63rem; text-overflow: ellipsis; white-space: nowrap; }
    .mp-kpi-success .mp-kpi-icon { color: var(--mp-success); background: #ecfdf3; } .mp-kpi-warning .mp-kpi-icon { color: var(--mp-warning); background: #fff4d6; }
    .mp-alert { display: flex; align-items: flex-start; gap: .55rem; margin-bottom: .8rem; padding: .65rem .8rem; border: 1px solid #f5d98a; border-radius: var(--mp-radius); color: #7a3e00; background: #fffbeb; font-size: .7rem; line-height: 1.45; } .mp-alert i { margin-top: .08rem; }
    .mp-section-head { display: flex; align-items: center; justify-content: space-between; gap: .75rem; padding: .78rem .9rem; border-bottom: 1px solid var(--mp-border); } .mp-section-title { margin: 0; font-size: .8rem; font-weight: 800; } .mp-section-meta { color: var(--mp-muted); font-size: .67rem; }
    .mp-journal-card { margin-bottom: .8rem; overflow: hidden; } .mp-table-wrap { overflow-x: auto; } .mp-table { width: 100%; min-width: 620px; margin: 0; table-layout: fixed; font-size: .73rem; font-variant-numeric: tabular-nums; }
    .mp-table th { padding: .58rem .75rem; border-bottom: 1px solid var(--mp-border); color: var(--mp-muted); background: #fbfcfe; font-size: .63rem; font-weight: 850; letter-spacing: .03em; text-transform: uppercase; } .mp-table td { padding: .62rem .75rem; border-top: 1px solid #f0f2f5; vertical-align: middle; }
    .mp-table th:first-child, .mp-table td:first-child { width: 18%; } .mp-table th:nth-child(2), .mp-table td:nth-child(2) { width: 48%; } .mp-table th:nth-child(3), .mp-table td:nth-child(3), .mp-table th:nth-child(4), .mp-table td:nth-child(4) { width: 17%; }
    .mp-table tbody tr:hover { background: #fbfdff; } .mp-table code { color: var(--mp-brand-dark); font-size: .68rem; font-weight: 750; } .mp-number { color: var(--mp-ink); font-weight: 700; white-space: nowrap; } .mp-total td { color: var(--mp-ink); background: var(--mp-soft); font-weight: 850; }
    .mp-account-card { margin-bottom: .8rem; overflow: hidden; } .mp-account-toggle { display: flex; align-items: center; justify-content: space-between; gap: .8rem; width: 100%; padding: .78rem .9rem; border: 0; color: var(--mp-ink); background: #fff; font: inherit; text-align: left; cursor: pointer; } .mp-account-toggle:hover { background: #fbfcfe; }
    .mp-account-toggle:focus-visible { outline: 3px solid rgba(91,92,226,.22); outline-offset: -3px; } .mp-account-toggle span { display: flex; align-items: center; gap: .5rem; font-size: .8rem; font-weight: 800; } .mp-account-toggle small { color: var(--mp-muted); font-size: .67rem; font-weight: 650; }
    .mp-account-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: .5rem; padding: .8rem .9rem .9rem; border-top: 1px solid var(--mp-border); background: #fbfcfe; } .mp-account-item { min-width: 0; padding: .55rem .6rem; border: 1px solid var(--mp-border); border-radius: 9px; background: #fff; }
    .mp-account-label { display: block; overflow: hidden; color: var(--mp-muted); font-size: .62rem; font-weight: 700; text-overflow: ellipsis; white-space: nowrap; } .mp-account-item code { display: inline-block; margin-top: .22rem; color: var(--mp-brand-dark); font-size: .65rem; } .mp-account-name { display: block; margin-top: .12rem; overflow: hidden; color: var(--mp-ink); font-size: .68rem; font-weight: 700; text-overflow: ellipsis; white-space: nowrap; }
    .mp-account-note { margin: .7rem .9rem 0; padding-bottom: .8rem; color: var(--mp-muted); font-size: .65rem; line-height: 1.45; } .mp-actions { display: flex; align-items: center; justify-content: space-between; gap: .9rem; padding: .75rem .9rem; border: 1px solid var(--mp-border); border-radius: var(--mp-radius); background: #fff; box-shadow: var(--mp-shadow); }
    .mp-actions-copy { display: flex; align-items: center; gap: .55rem; min-width: 0; } .mp-actions-icon { display: grid; width: 28px; height: 28px; flex: 0 0 28px; place-items: center; border-radius: 8px; color: var(--mp-success); background: #ecfdf3; }
    .mp-actions-title { display: block; color: var(--mp-ink); font-size: .72rem; font-weight: 800; } .mp-actions-note { display: block; margin-top: .12rem; color: var(--mp-muted); font-size: .65rem; }
    .mp-actions-buttons { display: flex; align-items: center; gap: .45rem; } .mp-post-button { display: inline-flex; align-items: center; gap: .35rem; border-radius: var(--mp-control-radius); padding: .52rem .75rem; font-size: .73rem; font-weight: 800; }
    @media (max-width: 900px) { .mp-kpis { grid-template-columns: repeat(2, minmax(0, 1fr)); } .mp-account-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
    @media (max-width: 640px) {
        .mp-posting-page { padding: .75rem .75rem 3rem; }
        .mp-header { flex-direction: column; padding: 1rem; }
        .mp-header .mp-btn { width: 100%; }
        .mp-workflow { grid-template-columns: 1fr; gap: .55rem; }
        .mp-readiness { align-items: flex-start; flex-direction: column; gap: .55rem; }
        .mp-status-pill { align-self: flex-start; }
        .mp-kpis { gap: .5rem; }
        .mp-kpi { min-height: 84px; padding: .7rem; }
        .mp-kpi-value { font-size: 1.05rem; }
        .mp-section-head { align-items: flex-start; flex-direction: column; gap: .2rem; }
        .mp-table-wrap { overflow: visible; }
        .mp-table { display: block; min-width: 0; }
        .mp-table thead { display: none; }
        .mp-table tbody, .mp-table tr { display: block; }
        .mp-table tr:not(.mp-total) { padding: .58rem .75rem; border-top: 1px solid #f0f2f5; }
        .mp-table tr:not(.mp-total) td { display: flex; align-items: baseline; justify-content: space-between; gap: 1rem; width: auto !important; padding: .18rem 0; border: 0; text-align: right !important; }
        .mp-table tr:not(.mp-total) td::before { color: var(--mp-muted); content: attr(data-label); font-size: .62rem; font-weight: 750; text-align: left; }
        .mp-table .mp-total { display: grid; grid-template-columns: 1fr 1fr; gap: .25rem .75rem; padding: .7rem .75rem; }
        .mp-table .mp-total td { display: block; width: auto !important; padding: 0; border: 0; text-align: right !important; }
        .mp-table .mp-total td:first-child { grid-column: 1 / -1; text-align: left !important; }
        .mp-table .mp-total td::before { display: none; }
        .mp-account-grid { grid-template-columns: 1fr; }
        .mp-actions { align-items: stretch; flex-direction: column; }
        .mp-actions-copy { align-items: flex-start; }
        .mp-actions-buttons { width: 100%; }
        .mp-actions-buttons .mp-btn, .mp-post-button { flex: 1; justify-content: center; }
    }
</style>
@endpush

@section('content')
<main class="mp-posting-page">
    <nav class="mp-breadcrumb" aria-label="Breadcrumb">
        <a href="{{ route('marketplace.reports.financial-statement', $filters) }}">Financial statement</a>
        <i class="bi bi-chevron-right" aria-hidden="true"></i>
        <span>Posting preview</span>
    </nav>

    <header class="mp-header">
        <div class="mp-header-copy">
            <div class="mp-eyebrow">Marketplace · Owner finance</div>
            <h1 class="mp-title">Posting settlement ke jurnal</h1>
            <p class="mp-description">Review draft jurnal untuk periode yang dipilih sebelum membuat posting akuntansi.</p>
            <div class="mp-header-meta">
                <span class="mp-chip"><i class="bi bi-shop" aria-hidden="true"></i>{{ $storeLabel }}</span>
                <span class="mp-chip"><i class="bi bi-calendar3" aria-hidden="true"></i>{{ $filters['date_from'] }} – {{ $filters['date_to'] }}</span>
                <span class="mp-chip"><i class="bi bi-funnel" aria-hidden="true"></i>{{ $scopeLabel }} · {{ $basisLabel }}</span>
            </div>
        </div>
        <a class="btn btn-outline-secondary mp-btn" href="{{ route('marketplace.reports.financial-statement', $filters) }}"><i class="bi bi-arrow-left me-1" aria-hidden="true"></i>Kembali</a>
    </header>

    <section class="mp-workflow" aria-labelledby="posting-workflow-title">
        <div id="posting-workflow-title" class="mp-workflow-label">Yang perlu dilakukan</div>
        <div class="mp-workflow-steps" aria-label="Alur posting">
            <span class="mp-workflow-step"><b>1</b>Audit data</span><span class="mp-workflow-arrow" aria-hidden="true">→</span>
            <span class="mp-workflow-step"><b>2</b>Rekonsiliasi</span><span class="mp-workflow-arrow" aria-hidden="true">→</span>
            <span class="mp-workflow-step"><b>3</b>Review jurnal</span><span class="mp-workflow-arrow" aria-hidden="true">→</span>
            <span class="mp-workflow-step"><b>4</b>Posting</span>
        </div>
    </section>

    <section class="mp-readiness {{ $readyForPosting ? 'mp-readiness-good' : 'mp-readiness-warning' }}" aria-live="polite">
        <div class="mp-readiness-main">
            <div class="mp-readiness-icon" aria-hidden="true"><i class="bi {{ $readyForPosting ? 'bi-check2-circle' : 'bi-exclamation-triangle' }}"></i></div>
            <div>
                <p class="mp-readiness-title">{{ $readyForPosting ? 'Siap diposting' : 'Perlu perhatian sebelum posting' }}</p>
                <p class="mp-readiness-copy">{{ $readyForPosting ? 'Preview tidak mengubah data. Posting akan membuat satu batch jurnal dan audit trail.' : 'Selesaikan validasi data atau mapping sebelum menyimpan jurnal.' }}</p>
            </div>
        </div>
        <span class="mp-status-pill">{{ $readyForPosting ? 'Dr = Cr' : 'Review diperlukan' }}</span>
    </section>

    @if ($adsFallback)
        <div class="mp-alert" role="status"><i class="bi bi-arrow-repeat" aria-hidden="true"></i><span>Biaya iklan menggunakan <strong>Ads Daily fallback</strong> (Rp {{ $fmt($adsAmount) }}). Sync wallet disarankan sebelum posting final.</span></div>
    @endif

    <section class="mp-kpis" aria-label="Ringkasan posting">
        <article class="mp-card mp-kpi"><div class="mp-kpi-label"><span class="mp-kpi-icon"><i class="bi bi-calendar3" aria-hidden="true"></i></span>Periode</div><strong class="mp-kpi-value">{{ $filters['date_from'] }}</strong><span class="mp-kpi-note">s/d {{ $filters['date_to'] }}</span></article>
        <article class="mp-card mp-kpi"><div class="mp-kpi-label"><span class="mp-kpi-icon"><i class="bi bi-receipt" aria-hidden="true"></i></span>Order siap</div><strong class="mp-kpi-value">{{ $fmt($summary['order_count']) }}</strong><span class="mp-kpi-note">Data audit lengkap</span></article>
        <article class="mp-card mp-kpi"><div class="mp-kpi-label"><span class="mp-kpi-icon"><i class="bi bi-wallet2" aria-hidden="true"></i></span>Payout</div><strong class="mp-kpi-value">Rp {{ $fmt($summary['payout']) }}</strong><span class="mp-kpi-note">Payout aktual marketplace</span></article>
        <article class="mp-card mp-kpi {{ $balanceDifference <= .01 ? 'mp-kpi-success' : 'mp-kpi-warning' }}"><div class="mp-kpi-label"><span class="mp-kpi-icon"><i class="bi bi-check2-circle" aria-hidden="true"></i></span>Balance check</div><strong class="mp-kpi-value">{{ $balanceDifference <= .01 ? 'Dr = Cr Rp ' . $fmt($preview['total_debit']) : 'Tidak balance' }}</strong><span class="mp-kpi-note">Selisih Rp {{ $fmt($balanceDifference) }}</span></article>
    </section>

    <section class="mp-card mp-journal-card" aria-labelledby="journal-title">
        <div class="mp-section-head"><h2 id="journal-title" class="mp-section-title">Draft jurnal</h2><span class="mp-section-meta">{{ $preview['accounting_scope'] }}</span></div>
        <div class="mp-table-wrap"><table class="mp-table align-middle"><thead><tr><th>Akun</th><th>Keterangan</th><th class="text-end">Debit</th><th class="text-end">Credit</th></tr></thead><tbody>
            @foreach ($preview['lines'] as $line)
                <tr><td data-label="Akun"><code>{{ $line['account_code'] }}</code></td><td data-label="Keterangan">{{ $line['account_name'] }}</td><td data-label="Debit" class="text-end mp-number">{{ $line['debit'] > 0 ? 'Rp ' . $fmt($line['debit']) : '-' }}</td><td data-label="Credit" class="text-end mp-number">{{ $line['credit'] > 0 ? 'Rp ' . $fmt($line['credit']) : '-' }}</td></tr>
            @endforeach
            <tr class="mp-total"><td colspan="2">Total</td><td class="text-end">Rp {{ $fmt($preview['total_debit']) }}</td><td class="text-end">Rp {{ $fmt($preview['total_credit']) }}</td></tr>
        </tbody></table></div>
    </section>

    <section class="mp-card mp-account-card">
        <button class="mp-account-toggle" type="button" data-bs-toggle="collapse" data-bs-target="#posting-account-mapping" aria-expanded="false" aria-controls="posting-account-mapping"><span><i class="bi bi-diagram-3" aria-hidden="true"></i>Mapping COA</span><small>{{ count($accountCodes) }} akun · klik untuk lihat</small></button>
        <div id="posting-account-mapping" class="collapse"><div class="mp-account-grid">
            @foreach ($accountCodes as $key => $code)
                @php($account = $accountMappings->get($code))
                <div class="mp-account-item"><span class="mp-account-label">{{ str_replace('_', ' ', ucfirst($key)) }}</span><code>{{ $code }}</code><span class="mp-account-name">{{ $account?->name ?? 'Belum tersedia' }}</span></div>
            @endforeach
        </div><p class="mp-account-note">Top up saldo iklan: Rp {{ $fmt($preview['included_in_gl']['wallet_ad_topup'] ?? 0) }}. HPP tidak ikut batch ini karena mengikuti posting COGS shipment. Penarikan payout tetap diproses melalui akun marketplace clearing.</p></div>
    </section>

    <div class="mp-actions">
        <div class="mp-actions-copy">
            <span class="mp-actions-icon" aria-hidden="true"><i class="bi bi-shield-check"></i></span>
            <div><span class="mp-actions-title">Preview siap diposting</span><span class="mp-actions-note">Read-only · jurnal dan audit trail dibuat setelah konfirmasi.</span></div>
        </div>
        <div class="mp-actions-buttons">
        <form method="POST" action="{{ route('marketplace.reports.financial-statement.post') }}" onsubmit="return confirm('Posting batch ini ke jurnal umum? Pastikan periode dan draft jurnal sudah benar.');">
            @csrf
            @foreach ($filters as $key => $value)<input type="hidden" name="{{ $key }}" value="{{ $value }}" data-gf-date="off">@endforeach
            <button class="mp-post-button" type="submit"><i class="bi bi-journal-plus" aria-hidden="true"></i>Posting ke jurnal</button>
        </form>
        </div>
    </div>
</main>
@endsection
