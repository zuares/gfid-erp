@extends('layouts.app')
@section('title', 'Laporan Laba Rugi')

@php $fmt = fn($n) => number_format((float)$n, 0, ',', '.'); @endphp

@push('head')
    @include('production.dashboard.partials._gf-styles')
    <style>
        .pl-page { display:grid; gap:1rem; }
        .pl-btn { display:inline-flex; align-items:center; gap:.4rem; min-height:38px; padding:.45rem .9rem; border-radius:999px; border:1px solid rgba(15,23,42,.10); background:#fff; color:#0f172a; font-size:.83rem; font-weight:850; cursor:pointer; text-decoration:none; }
        .pl-btn:hover { background:#f8fafc; color:#0f172a; }
        .pl-card { border:1px solid rgba(15,23,42,.08); border-radius:12px; background:#fff; overflow:hidden; }
        .pl-section-head { background:#f8fafc; padding:.6rem 1rem; font-size:.74rem; font-weight:900; text-transform:uppercase; letter-spacing:.06em; color:#475569; border-bottom:1px solid rgba(15,23,42,.06); }
        .pl-row { display:flex; justify-content:space-between; align-items:center; padding:.5rem 1rem; border-bottom:1px solid rgba(15,23,42,.04); font-size:.84rem; }
        .pl-row:last-child { border-bottom:none; }
        .pl-row-code { color:#94a3b8; font-size:.75rem; margin-right:.5rem; }
        .pl-row-total { display:flex; justify-content:space-between; padding:.65rem 1rem; font-weight:950; font-size:.88rem; border-top:2px solid rgba(15,23,42,.12); }
        .pl-summary-card { border-radius:12px; padding:1rem 1.2rem; }
        .pl-num { font-variant-numeric:tabular-nums; font-weight:900; }
        .pl-positive { color:#16a34a; }
        .pl-negative { color:#dc2626; }
        .pl-neutral  { color:#0f172a; }
        .pl-grid3 { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:.75rem; }
        @media(max-width:768px){ .pl-grid3{grid-template-columns:1fr;} }
    </style>
@endphp

@section('content')
<div class="pl-page">

    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div>
            <h5 class="mb-0 fw-black">Laporan Laba Rugi</h5>
            <div class="text-muted" style="font-size:.8rem">Berbasis jurnal yang sudah di-post</div>
        </div>
    </div>

    {{-- Filter --}}
    <form method="GET" class="d-flex gap-2 align-items-end flex-wrap">
        <div>
            <label class="form-label fw-bold" style="font-size:.74rem">Dari</label>
            <input type="date" name="from" class="form-control"
                   style="min-height:38px;border-radius:999px;border-color:rgba(15,23,42,.12);font-size:.83rem;font-weight:700;box-shadow:none;width:150px"
                   value="{{ $from }}">
        </div>
        <div>
            <label class="form-label fw-bold" style="font-size:.74rem">Sampai</label>
            <input type="date" name="to" class="form-control"
                   style="min-height:38px;border-radius:999px;border-color:rgba(15,23,42,.12);font-size:.83rem;font-weight:700;box-shadow:none;width:150px"
                   value="{{ $to }}">
        </div>
        <button type="submit" class="pl-btn" style="background:#0f172a;color:#fff;border-color:#0f172a">Tampilkan</button>
        {{-- Shortcuts --}}
        @php
            $shortcuts = [
                'Bulan Ini'  => ['from'=>now()->startOfMonth()->format('Y-m-d'),'to'=>now()->format('Y-m-d')],
                'Bulan Lalu' => ['from'=>now()->subMonth()->startOfMonth()->format('Y-m-d'),'to'=>now()->subMonth()->endOfMonth()->format('Y-m-d')],
                'Tahun Ini'  => ['from'=>now()->startOfYear()->format('Y-m-d'),'to'=>now()->format('Y-m-d')],
            ];
        @endphp
        @foreach($shortcuts as $label => $q)
            <a href="{{ route('accounting.profit-loss.index', $q) }}" class="pl-btn" style="font-size:.76rem">{{ $label }}</a>
        @endforeach
    </form>

    {{-- Summary cards --}}
    <div class="pl-grid3">
        <div class="pl-summary-card" style="background:#dcfce7; border:1px solid #bbf7d0">
            <div style="font-size:.7rem; font-weight:900; text-transform:uppercase; color:#166534; letter-spacing:.05em">Pendapatan</div>
            <div style="font-size:1.4rem; font-weight:950; color:#15803d" class="pl-num">Rp {{ $fmt($totalRevenue) }}</div>
        </div>
        <div class="pl-summary-card" style="background:#fee2e2; border:1px solid #fecaca">
            <div style="font-size:.7rem; font-weight:900; text-transform:uppercase; color:#991b1b; letter-spacing:.05em">Total Beban</div>
            <div style="font-size:1.4rem; font-weight:950; color:#dc2626" class="pl-num">Rp {{ $fmt($totalCogs + $totalExpenses) }}</div>
        </div>
        <div class="pl-summary-card {{ $netProfit >= 0 ? 'bg-dark text-white' : '' }}"
             style="{{ $netProfit < 0 ? 'background:#fff7ed;border:1px solid #fed7aa' : 'border:1px solid #0f172a' }}">
            <div style="font-size:.7rem; font-weight:900; text-transform:uppercase; letter-spacing:.05em; color:{{ $netProfit >= 0 ? '#94a3b8' : '#c2410c' }}">
                Laba / Rugi Bersih
            </div>
            <div style="font-size:1.4rem; font-weight:950; color:{{ $netProfit >= 0 ? '#fff' : '#ea580c' }}" class="pl-num">
                {{ $netProfit < 0 ? '(' : '' }}Rp {{ $fmt(abs($netProfit)) }}{{ $netProfit < 0 ? ')' : '' }}
            </div>
        </div>
    </div>

    <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem">
        {{-- Pendapatan --}}
        <div>
            <div class="pl-card">
                <div class="pl-section-head">Pendapatan</div>
                @forelse($revenueRows as $r)
                    <div class="pl-row">
                        <span><span class="pl-row-code">{{ $r->code }}</span>{{ $r->name }}</span>
                        <span class="pl-num {{ $r->amount >= 0 ? 'pl-positive' : 'pl-negative' }}">
                            {{ $r->amount < 0 ? '(' : '' }}Rp {{ $fmt(abs($r->amount)) }}{{ $r->amount < 0 ? ')' : '' }}
                        </span>
                    </div>
                @empty
                    <div class="pl-row" style="color:#94a3b8">Belum ada data</div>
                @endforelse
                <div class="pl-row-total">
                    <span>Total Pendapatan</span>
                    <span class="pl-num pl-positive">Rp {{ $fmt($totalRevenue) }}</span>
                </div>
            </div>

            {{-- HPP --}}
            <div class="pl-card mt-3">
                <div class="pl-section-head">Harga Pokok Penjualan</div>
                @forelse($cogsRows as $r)
                    <div class="pl-row">
                        <span><span class="pl-row-code">{{ $r->code }}</span>{{ $r->name }}</span>
                        <span class="pl-num pl-negative">Rp {{ $fmt($r->amount) }}</span>
                    </div>
                @empty
                    <div class="pl-row" style="color:#94a3b8">Belum ada data</div>
                @endforelse
                <div class="pl-row-total">
                    <span>Total HPP</span>
                    <span class="pl-num pl-negative">Rp {{ $fmt($totalCogs) }}</span>
                </div>
            </div>

            {{-- Gross Profit --}}
            <div class="pl-card mt-3" style="background:#f0fdf4; border-color:#bbf7d0">
                <div class="pl-row-total" style="border-top:none; padding:.85rem 1.2rem">
                    <span style="color:#166534">Laba Kotor</span>
                    <span class="pl-num {{ $grossProfit >= 0 ? 'pl-positive' : 'pl-negative' }}" style="font-size:1rem">
                        {{ $grossProfit < 0 ? '(' : '' }}Rp {{ $fmt(abs($grossProfit)) }}{{ $grossProfit < 0 ? ')' : '' }}
                    </span>
                </div>
            </div>
        </div>

        {{-- Beban Operasional --}}
        <div>
            <div class="pl-card">
                <div class="pl-section-head">Beban Operasional</div>
                @forelse($expenseRows as $r)
                    <div class="pl-row">
                        <span><span class="pl-row-code">{{ $r->code }}</span>{{ $r->name }}</span>
                        <span class="pl-num pl-negative">Rp {{ $fmt($r->amount) }}</span>
                    </div>
                @empty
                    <div class="pl-row" style="color:#94a3b8">Belum ada data</div>
                @endforelse
                <div class="pl-row-total">
                    <span>Total Beban</span>
                    <span class="pl-num pl-negative">Rp {{ $fmt($totalExpenses) }}</span>
                </div>
            </div>

            {{-- Net Profit --}}
            <div class="pl-card mt-3" style="{{ $netProfit >= 0 ? 'background:#0f172a' : 'background:#fff7ed;border-color:#fed7aa' }}">
                <div class="pl-row-total" style="border-top:none; padding:.85rem 1.2rem">
                    <span style="color:{{ $netProfit >= 0 ? '#94a3b8' : '#c2410c' }}; font-size:1rem">
                        {{ $netProfit >= 0 ? 'Laba Bersih' : 'Rugi Bersih' }}
                    </span>
                    <span class="pl-num" style="font-size:1.15rem; color:{{ $netProfit >= 0 ? '#fff' : '#ea580c' }}">
                        {{ $netProfit < 0 ? '(' : '' }}Rp {{ $fmt(abs($netProfit)) }}{{ $netProfit < 0 ? ')' : '' }}
                    </span>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection
