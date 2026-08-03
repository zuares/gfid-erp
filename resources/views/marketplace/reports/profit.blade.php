@extends('layouts.app')

@section('title', 'Marketplace • Laporan Keuntungan')

@php
    $fmt = fn ($n) => number_format((float) $n, 0, ',', '.');
    $pct = fn ($n) => number_format((float) $n, 2, ',', '.') . '%';
    $summary = $report['summary'];
    $quality = $report['quality'];
    $filters = $report['filters'];
@endphp

@section('content')
<div class="container-fluid py-3">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
        <div>
            <div class="text-uppercase text-muted small fw-semibold">Marketplace · Owner report</div>
            <h4 class="mb-1">Laporan Keuntungan Marketplace</h4>
            <p class="text-muted mb-0" style="max-width: 820px">
                Profit hanya menghitung order yang sudah lolos quality gate. Payout aktual dikurangi HPP dan biaya iklan;
                fee marketplace ditampilkan sebagai rekonsiliasi, bukan dikurangi dua kali dari payout.
            </p>
        </div>
        <div class="d-flex gap-2">
            <a class="btn btn-outline-secondary" href="{{ route('marketplace.reports.financial-quality') }}">
                <i class="bi bi-clipboard2-data me-1"></i> Audit Data
            </a>
            <a class="btn btn-primary" href="{{ route('marketplace.reports.profit.export', $filters) }}">
                <i class="bi bi-download me-1"></i> Export CSV
            </a>
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
                            <option value="{{ $store->id }}" @selected((int) ($filters['store_id'] ?? 0) === (int) $store->id)>
                                {{ $store->name }} · {{ strtoupper($store->channel?->code ?? '-') }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Basis tanggal</label>
                    <select name="date_basis" class="form-select">
                        <option value="ordered_at" @selected($filters['date_basis'] === 'ordered_at')>Tanggal order</option>
                        <option value="settlement_time" @selected($filters['date_basis'] === 'settlement_time')>Tanggal cair</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Dari</label>
                    <input type="date" name="date_from" class="form-control" value="{{ $filters['date_from'] }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Sampai</label>
                    <input type="date" name="date_to" class="form-control" value="{{ $filters['date_to'] }}">
                </div>
                <div class="col-md-auto">
                    <button class="btn btn-outline-primary"><i class="bi bi-funnel me-1"></i> Terapkan</button>
                </div>
            </form>
        </div>
    </div>

    @if ($quality['incomplete'] > 0 || $quality['unknown'] > 0)
        <div class="alert alert-warning d-flex align-items-start gap-2 mb-3">
            <i class="bi bi-exclamation-triangle fs-5"></i>
            <div>
                <div class="fw-semibold">Sebagian data belum masuk perhitungan profit.</div>
                <div class="small">
                    {{ $fmt($quality['incomplete'] + $quality['unknown']) }} order incomplete/unknown dari {{ $fmt($quality['total']) }} order pada filter ini.
                    <a href="{{ route('marketplace.reports.financial-quality', ['store_id' => $filters['store_id']]) }}" class="alert-link">Buka audit kualitas data</a> untuk memperbaikinya.
                </div>
            </div>
        </div>
    @endif

    <div class="row g-3 mb-3">
        @foreach ([
            ['label' => 'Omzet customer', 'value' => $summary['gross_sales'], 'class' => 'primary', 'icon' => 'graph-up-arrow'],
            ['label' => 'Payout aktual', 'value' => $summary['payout'], 'class' => 'info', 'icon' => 'wallet2'],
            ['label' => 'HPP', 'value' => $summary['hpp'], 'class' => 'secondary', 'icon' => 'box-seam'],
            ['label' => 'Fee marketplace', 'value' => $summary['marketplace_fees'], 'class' => 'warning', 'icon' => 'receipt'],
            ['label' => 'Biaya iklan', 'value' => $summary['ad_cost'], 'class' => 'danger', 'icon' => 'badge-ad'],
            ['label' => 'Laba operasional', 'value' => $summary['operating_profit'], 'class' => 'success', 'icon' => 'cash-stack'],
        ] as $card)
            <div class="col-6 col-xl-2">
                <div class="card shadow-sm h-100 border-{{ $card['class'] }}">
                    <div class="card-body">
                        <div class="text-muted small"><i class="bi bi-{{ $card['icon'] }} me-1"></i>{{ $card['label'] }}</div>
                        <div class="fs-5 fw-bold text-{{ $card['class'] }} mt-1">Rp {{ $fmt($card['value']) }}</div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-3"><div class="card shadow-sm"><div class="card-body"><div class="text-muted small">Margin operasional</div><div class="fs-4 fw-bold">{{ $pct($summary['margin_pct']) }}</div></div></div></div>
        <div class="col-md-3"><div class="card shadow-sm"><div class="card-body"><div class="text-muted small">Order masuk profit</div><div class="fs-4 fw-bold text-success">{{ $fmt($quality['ready']) }}</div></div></div></div>
        <div class="col-md-3"><div class="card shadow-sm"><div class="card-body"><div class="text-muted small">Order dikecualikan</div><div class="fs-4 fw-bold text-warning">{{ $fmt($quality['incomplete'] + $quality['unknown'] + $quality['not_applicable']) }}</div></div></div></div>
        <div class="col-md-3"><div class="card shadow-sm"><div class="card-body"><div class="text-muted small">Refund/adjustment</div><div class="fs-4 fw-bold">Rp {{ $fmt($summary['refund']) }}</div></div></div></div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-lg-7">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white fw-semibold">Trend harian</div>
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle mb-0">
                        <thead class="table-light"><tr><th>Tanggal</th><th class="text-end">Order</th><th class="text-end">Payout</th><th class="text-end">HPP</th><th class="text-end">Laba Operasional</th><th class="text-end">Margin</th></tr></thead>
                        <tbody>
                        @forelse ($report['daily'] as $row)
                            <tr><td>{{ $row['date'] ?: 'Tanpa tanggal' }}</td><td class="text-end">{{ $fmt($row['order_count']) }}</td><td class="text-end">Rp {{ $fmt($row['payout']) }}</td><td class="text-end">Rp {{ $fmt($row['hpp']) }}</td><td class="text-end fw-semibold">Rp {{ $fmt($row['operating_profit']) }}</td><td class="text-end">{{ $pct($row['margin_pct']) }}</td></tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-4">Belum ada order ready pada periode ini.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white fw-semibold">Profit per toko</div>
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle mb-0">
                        <thead class="table-light"><tr><th>Toko</th><th class="text-end">Order</th><th class="text-end">Payout</th><th class="text-end">Laba</th></tr></thead>
                        <tbody>
                        @forelse ($report['stores'] as $row)
                            <tr><td><div class="fw-semibold">{{ $row['store_name'] }}</div><div class="text-muted small">{{ strtoupper($row['channel']) }}</div></td><td class="text-end">{{ $fmt($row['order_count']) }}</td><td class="text-end">Rp {{ $fmt($row['payout']) }}</td><td class="text-end fw-semibold">Rp {{ $fmt($row['operating_profit']) }}</td></tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted py-4">Belum ada data.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-header bg-white fw-semibold">Profit per produk</div>
        <div class="table-responsive">
            <table class="table table-sm table-hover align-middle mb-0">
                <thead class="table-light"><tr><th>SKU</th><th>Produk</th><th class="text-end">Qty</th><th class="text-end">Payout</th><th class="text-end">HPP</th><th class="text-end">Laba Operasional</th><th class="text-end">Margin</th></tr></thead>
                <tbody>
                @forelse (collect($report['items'])->sortByDesc('operating_profit')->take(50) as $row)
                    <tr><td class="font-monospace">{{ $row['sku'] }}</td><td>{{ $row['item_name'] }}</td><td class="text-end">{{ $fmt($row['qty']) }}</td><td class="text-end">Rp {{ $fmt($row['payout']) }}</td><td class="text-end">Rp {{ $fmt($row['hpp']) }}</td><td class="text-end fw-semibold">Rp {{ $fmt($row['operating_profit']) }}</td><td class="text-end">{{ $pct($row['margin_pct']) }}</td></tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">Belum ada produk ready pada periode ini.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-white fw-semibold">Detail order yang masuk perhitungan</div>
        <div class="table-responsive">
            <table class="table table-sm table-hover align-middle mb-0">
                <thead class="table-light"><tr><th>Order</th><th>Toko</th><th>Tanggal</th><th class="text-end">Omzet</th><th class="text-end">Payout</th><th class="text-end">HPP</th><th class="text-end">Iklan</th><th class="text-end">Laba Operasional</th><th class="text-end">Margin</th></tr></thead>
                <tbody>
                @forelse (collect($report['orders'])->take(100) as $row)
                    <tr><td class="fw-semibold">{{ $row['channel_order_id'] }}</td><td>{{ $row['store_name'] }}</td><td>{{ $row['ordered_at'] ?: '-' }}</td><td class="text-end">Rp {{ $fmt($row['gross_sales']) }}</td><td class="text-end">Rp {{ $fmt($row['payout']) }}</td><td class="text-end">Rp {{ $fmt($row['hpp']) }}</td><td class="text-end">Rp {{ $fmt($row['ad_cost']) }}</td><td class="text-end fw-semibold">Rp {{ $fmt($row['operating_profit']) }}</td><td class="text-end">{{ $pct($row['margin_pct']) }}</td></tr>
                @empty
                    <tr><td colspan="9" class="text-center text-muted py-5">Belum ada order yang lolos quality gate pada periode ini.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
