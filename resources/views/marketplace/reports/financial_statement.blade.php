@extends('layouts.app')

@section('title', 'Marketplace • Laporan Keuangan')

@php
    $fmt = fn ($n) => number_format((float) $n, 0, ',', '.');
    $pct = fn ($n) => number_format((float) $n, 2, ',', '.') . '%';
    $summary = $statement['summary'];
    $quality = $statement['quality'];
    $filters = $statement['filters'];
@endphp

@push('head')
<style>
    .mp-finance-page .card { border-radius: 14px; }
    .mp-finance-page .summary-card { border-width: 0 0 0 4px !important; }
    .mp-finance-page .summary-card .card-body { min-height: 92px; display:flex; flex-direction:column; justify-content:center; }
    .mp-finance-page .table th { white-space: nowrap; }
    .mp-finance-page .filter-help { font-size:.76rem; color:#64748b; }
    @media (max-width: 767.98px) {
        .mp-finance-page { padding-left:.65rem; padding-right:.65rem; }
        .mp-finance-page .page-actions { width:100%; }
        .mp-finance-page .page-actions .btn { flex:1 1 auto; }
        .mp-finance-page .summary-card .card-body { min-height:78px; padding:.8rem; }
        .mp-finance-page .summary-card .fs-5 { font-size:1rem !important; }
    }
</style>
@endpush

@section('content')
<div class="container-fluid py-3 mp-finance-page">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
        <div>
            <div class="text-uppercase text-muted small fw-semibold">Marketplace · Owner finance</div>
            <h4 class="mb-1">Laporan Keuangan Marketplace</h4>
            <p class="text-muted mb-0" style="max-width: 820px">
                Laporan subledger marketplace berbasis order yang sudah terverifikasi. Owner dapat meninjau dan mem-posting settlement
                ke jurnal umum berdasarkan filter periode yang sedang aktif.
            </p>
        </div>
        <div class="d-flex flex-wrap gap-2 page-actions">
            <a class="btn btn-outline-secondary" href="{{ route('marketplace.reports.profit', $filters) }}"><i class="bi bi-graph-up-arrow me-1"></i> Detail Profit</a>
            <a class="btn btn-outline-warning" href="{{ route('marketplace.reports.financial-closing', $filters) }}"><i class="bi bi-lock-fill me-1"></i> Closing & Audit</a>
            <a class="btn btn-primary" href="{{ route('marketplace.reports.financial-statement.export', $filters) }}"><i class="bi bi-download me-1"></i> Export Statement</a>
        </div>
    </div>

    <div class="card shadow-sm mb-3">
        <div class="card-body">
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
                    <label class="form-label small fw-semibold">Basis tanggal</label>
                    <select name="date_basis" class="form-select">
                        <option value="ordered_at" @selected($filters['date_basis'] === 'ordered_at')>Tanggal order</option>
                        <option value="settlement_time" @selected($filters['date_basis'] === 'settlement_time')>Tanggal cair</option>
                    </select>
                    <div class="filter-help mt-1">Menentukan transaksi yang masuk periode laporan.</div>
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
                @elseif ($quality['incomplete'] === 0 && $quality['unknown'] === 0 && $summary['order_count'] > 0)
                    <div class="fw-semibold">Settlement siap direview sebelum masuk jurnal.</div>
                    <div class="small text-muted">Posting dibuat satu batch per toko/periode dan aman diulang karena idempotent.</div>
                @else
                    <div class="fw-semibold text-muted">Posting belum tersedia.</div>
                    <div class="small text-muted">Selesaikan quality gate dan pastikan periode memiliki order ready.</div>
                @endif
            </div>
            <div class="d-flex flex-wrap gap-2">
                @if (!$posting && $quality['incomplete'] === 0 && $quality['unknown'] === 0 && $summary['order_count'] > 0)
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

    <div class="row g-3 mb-4">
        @foreach ([
            ['label' => 'Penjualan bersih', 'value' => $summary['net_sales_before_settlement'], 'class' => 'primary'],
            ['label' => 'Payout aktual', 'value' => $summary['payout'], 'class' => 'info'],
            ['label' => 'Laba kotor', 'value' => $summary['gross_profit'], 'class' => 'success'],
            ['label' => 'Laba operasional', 'value' => $summary['operating_profit'], 'class' => 'success'],
            ['label' => 'Margin operasional', 'value' => $pct($summary['margin_pct']), 'class' => 'dark', 'text' => true],
        ] as $card)
            <div class="col-6 col-xl-{{ $loop->last ? '2' : '2' }}"><div class="card summary-card shadow-sm h-100 border-{{ $card['class'] }}"><div class="card-body"><div class="text-muted small">{{ $card['label'] }}</div><div class="fs-5 fw-bold text-{{ $card['class'] }} mt-1">{!! !empty($card['text']) ? $card['value'] : 'Rp ' . $fmt($card['value']) !!}</div></div></div></div>
        @endforeach
    </div>

    <div class="row g-3 mb-4">
        <div class="col-lg-7">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white fw-semibold">Laporan Laba Rugi Subledger</div>
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
                        <tr><td>Biaya iklan</td><td class="text-end text-danger">(Rp {{ $fmt($summary['ad_cost']) }})</td></tr>
                        <tr class="table-success fw-bold"><td>Laba operasional</td><td class="text-end">Rp {{ $fmt($summary['operating_profit']) }}</td></tr>
                    </tbody>
                </table></div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white fw-semibold">Rekonsiliasi payout</div>
                <div class="card-body">
                    <div class="small text-muted mb-3">Selisih payout aktual dengan perhitungan dasar ditampilkan sebagai penyesuaian lain agar tidak dipaksakan hilang.</div>
                    @foreach ([
                        ['label' => 'Payout dasar sebelum penyesuaian', 'value' => $statement['reconciliation']['expected_payout_before_other_adjustments']],
                        ['label' => 'Penyesuaian settlement lain', 'value' => $statement['reconciliation']['other_settlement_adjustment']],
                        ['label' => 'Payout aktual', 'value' => $statement['reconciliation']['actual_payout']],
                        ['label' => 'Selisih rekonsiliasi', 'value' => $statement['reconciliation']['difference']],
                    ] as $line)
                        <div class="d-flex justify-content-between border-bottom py-2"><span class="small">{{ $line['label'] }}</span><strong>Rp {{ $fmt($line['value']) }}</strong></div>
                    @endforeach
                    <div class="alert alert-info small mt-3 mb-0">Selisih idealnya Rp0. Jika besar, cek voucher, ongkir, pajak, refund, atau adjustment di settlement raw.</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-header bg-white fw-semibold">Performa per toko</div>
        <div class="table-responsive"><table class="table table-sm table-hover align-middle mb-0"><thead class="table-light"><tr><th>Toko</th><th class="text-end">Order</th><th class="text-end">Penjualan bersih</th><th class="text-end">Payout</th><th class="text-end">HPP</th><th class="text-end">Laba operasional</th><th class="text-end">Margin</th></tr></thead><tbody>
            @forelse ($statement['stores'] as $row)
                <tr><td><div class="fw-semibold">{{ $row['store_name'] }}</div><div class="text-muted small">{{ strtoupper($row['channel']) }}</div></td><td class="text-end">{{ $fmt($row['order_count']) }}</td><td class="text-end">Rp {{ $fmt($row['net_sales_before_settlement']) }}</td><td class="text-end">Rp {{ $fmt($row['payout']) }}</td><td class="text-end">Rp {{ $fmt($row['hpp']) }}</td><td class="text-end fw-semibold">Rp {{ $fmt($row['operating_profit']) }}</td><td class="text-end">{{ $pct($row['margin_pct']) }}</td></tr>
            @empty
                <tr><td colspan="7" class="text-center text-muted py-4">Belum ada data.</td></tr>
            @endforelse
        </tbody></table></div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-white fw-semibold">Trend laba operasional</div>
        <div class="table-responsive"><table class="table table-sm table-hover align-middle mb-0"><thead class="table-light"><tr><th>Tanggal</th><th class="text-end">Order</th><th class="text-end">Penjualan bersih</th><th class="text-end">Payout</th><th class="text-end">HPP</th><th class="text-end">Biaya iklan</th><th class="text-end">Laba operasional</th></tr></thead><tbody>
            @forelse ($statement['daily'] as $row)
                <tr><td>{{ $row['date'] ?: 'Tanpa tanggal' }}</td><td class="text-end">{{ $fmt($row['order_count']) }}</td><td class="text-end">Rp {{ $fmt($row['net_sales_before_settlement']) }}</td><td class="text-end">Rp {{ $fmt($row['payout']) }}</td><td class="text-end">Rp {{ $fmt($row['hpp']) }}</td><td class="text-end">Rp {{ $fmt($row['ad_cost']) }}</td><td class="text-end fw-semibold">Rp {{ $fmt($row['operating_profit']) }}</td></tr>
            @empty
                <tr><td colspan="7" class="text-center text-muted py-4">Belum ada data.</td></tr>
            @endforelse
        </tbody></table></div>
    </div>
</div>
@endsection
