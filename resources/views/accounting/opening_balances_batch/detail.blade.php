@extends('layouts.app')

@section('title', 'Accounting • Detail Saldo Awal')

@php
    $fmt = fn($n) => number_format((float) $n, 0, ',', '.');
    $rows = collect($detail['rows'] ?? []);
    $plainGuides = [
        '1202' => [
            'title' => 'WIP berjalan',
            'body' => 'Barang yang masih proses',
            'item' => 'Item / posisi',
            'info' => 'Qty dan nilai',
        ],
        '1203' => [
            'title' => 'Stok barang jadi',
            'body' => 'Barang siap jual di gudang',
            'item' => 'Item / gudang',
            'info' => 'Qty dan HPP',
        ],
        '1301' => [
            'title' => 'Piutang customer',
            'body' => 'Invoice non-marketplace belum lunas',
            'item' => 'Invoice / customer',
            'info' => 'Tanggal / status',
        ],
        '1302' => [
            'title' => 'Piutang marketplace',
            'body' => 'Dana marketplace belum cair',
            'item' => 'Pembeli / order',
            'info' => 'Marketplace / status',
        ],
        '2102' => [
            'title' => 'Upah belum dibayar',
            'body' => 'Cutting dan jahit yang masih jadi hutang',
            'item' => 'Operator',
            'info' => 'Proses / item / qty',
        ],
    ];
    $guide = $plainGuides[$account->code] ?? [
        'title' => 'Rincian saldo awal',
        'body' => 'Rincian angka saldo awal',
        'item' => 'Nama / dokumen',
        'info' => 'Keterangan',
    ];
    $diff = round((float) $prefillValue - (float) $total, 0);
@endphp

@push('head')
@include('production.dashboard.partials._gf-styles')
<style>
    .obd-page { max-width: 980px; margin: 0 auto; display: grid; gap: 1rem; }
    .obd-actions { display: flex; gap: .5rem; flex-wrap: wrap; }
    .obd-btn {
        display: inline-flex; align-items: center; justify-content: center; gap: .45rem;
        min-height: 38px; padding: .5rem .9rem; border-radius: 999px;
        border: 1px solid rgba(15,23,42,.12); background: #fff;
        color: #0f172a; text-decoration: none; font-size: .82rem; font-weight: 900;
    }
    .obd-btn:hover { color:#0f172a; background:#f8fafc; }
    .obd-headline {
        border:1px solid rgba(15,23,42,.08); border-radius:14px;
        background:#fff; padding:1rem; display:flex; justify-content:space-between;
        align-items:flex-end; gap:1rem;
    }
    .obd-head-title { color:#0f172a; font-size:1.05rem; font-weight:950; line-height:1.2; }
    .obd-head-sub { margin-top:.2rem; color:#64748b; font-size:.82rem; font-weight:760; }
    .obd-head-amount { color:#0f172a; font-size:1.28rem; font-weight:950; font-variant-numeric:tabular-nums; white-space:nowrap; }
    .obd-summary { display: grid; grid-template-columns: repeat(2, minmax(0,1fr)); gap: .75rem; }
    .obd-kpi {
        border: 1px solid rgba(15,23,42,.08); border-radius: 12px; background: #fff;
        padding: .9rem 1rem;
    }
    .obd-kpi-label {
        color:#64748b; font-size:.68rem; font-weight:900; text-transform:uppercase; letter-spacing:.06em;
    }
    .obd-kpi-value {
        margin-top:.18rem; color:#0f172a; font-size:1.2rem; font-weight:950; font-variant-numeric:tabular-nums;
    }
    .obd-panel {
        border: 1px solid rgba(15,23,42,.08); border-radius: 14px; background:#fff;
        overflow:hidden;
    }
    .obd-panel-h {
        padding:.85rem 1rem; border-bottom:1px solid rgba(15,23,42,.08);
        display:flex; justify-content:space-between; align-items:center; gap:.75rem;
    }
    .obd-panel-title { font-weight:950; color:#0f172a; }
    .obd-panel-note { color:#64748b; font-size:.78rem; font-weight:760; }
    .obd-list { display:grid; gap:.55rem; padding:.75rem; }
    .obd-row {
        display:grid; grid-template-columns:minmax(0,1.1fr) minmax(0,1.35fr) auto;
        gap:.85rem; align-items:center; padding:.78rem .85rem;
        border:1px solid rgba(15,23,42,.07); border-radius:10px; background:#fff;
    }
    .obd-main { color:#0f172a; font-size:.9rem; font-weight:950; line-height:1.3; }
    .obd-sub { color:#64748b; font-size:.8rem; font-weight:760; line-height:1.45; }
    .obd-num { text-align:right; color:#0f172a; font-weight:950; font-variant-numeric:tabular-nums; white-space:nowrap; }
    .obd-label { display:none; }
    .obd-empty { padding:2.4rem 1rem; text-align:center; color:#64748b; font-weight:780; }
    .obd-note {
        border:1px solid #fde68a; background:#fef3c7; color:#92400e;
        border-radius:12px; padding:.75rem .9rem; font-size:.82rem; font-weight:780;
    }
    .obd-soft-note {
        border:1px solid rgba(37,99,235,.12); background:#eff6ff; color:#1e3a8a;
        border-radius:12px; padding:.75rem .9rem; font-size:.82rem; font-weight:780;
    }
    @media(max-width:768px) {
        .obd-page { padding:0 .1rem; }
        .obd-headline { align-items:flex-start; flex-direction:column; }
        .obd-summary { grid-template-columns:1fr; gap:.55rem; }
        .obd-panel-h { align-items:flex-start; flex-direction:column; }
        .obd-row { grid-template-columns:1fr; gap:.5rem; }
        .obd-num { text-align:left; font-size:1rem; }
    }
</style>
@endpush

@section('content')
<x-gf.page
    eyebrow="Saldo Awal"
    title="{{ $account->code }} · {{ $account->name }}"
    description="{{ $guide['body'] }}">
    <x-slot:actions>
        <div class="obd-actions">
            <a href="{{ route('accounting.opening-balances-batch.create') }}" class="obd-btn">Kembali ke Input</a>
            <a href="{{ route('accounting.opening-balances-batch.index') }}" class="obd-btn">Riwayat Batch</a>
        </div>
    </x-slot:actions>

    <div class="obd-page">
        <div class="obd-headline">
            <div>
                <div class="obd-head-title">{{ $guide['title'] }}</div>
                <div class="obd-head-sub">{{ $guide['body'] }}</div>
            </div>
            <div class="obd-head-amount">Rp {{ $fmt($prefillValue) }}</div>
        </div>

        <div class="obd-summary">
            <div class="obd-kpi">
                <div class="obd-kpi-label">Rincian Tampil</div>
                <div class="obd-kpi-value">Rp {{ $fmt($total) }}</div>
            </div>
            <div class="obd-kpi">
                <div class="obd-kpi-label">Baris</div>
                <div class="obd-kpi-value">{{ $fmt($rows->count()) }}</div>
            </div>
        </div>

        @if($diff !== 0)
            <div class="obd-soft-note">
                Selisih Rp {{ $fmt(abs($diff)) }} karena rincian ditampilkan terbatas.
            </div>
        @endif

        @if(!empty($detail['note']))
            <div class="obd-note">{{ $detail['note'] }}</div>
        @endif

        <div class="obd-panel">
            <div class="obd-panel-h">
                <div>
                    <div class="obd-panel-title">{{ $detail['title'] ?? 'Rincian' }}</div>
                </div>
                <div class="obd-panel-note">Rp {{ $fmt($total) }}</div>
            </div>

            @if($rows->isEmpty())
                <div class="obd-empty">Belum ada rincian untuk akun ini.</div>
            @else
                <div class="obd-list">
                    @foreach($rows as $row)
                        <div class="obd-row">
                            <div>
                                <div class="obd-main">{{ $row['label'] ?? '-' }}</div>
                            </div>
                            <div>
                                <div class="obd-sub">{{ $row['sub'] ?? '-' }}</div>
                            </div>
                            <div>
                                <div class="obd-num">Rp {{ $fmt($row['value'] ?? 0) }}</div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</x-gf.page>
@endsection
