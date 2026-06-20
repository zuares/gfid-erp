@extends('layouts.app')
@section('title', 'Buku Besar')

@php
    $fmt = fn($n) => $n > 0 ? number_format((float)$n, 0, ',', '.') : '-';
    $typeLabel = ['asset'=>'Aset','liability'=>'Liabilitas','equity'=>'Ekuitas','revenue'=>'Pendapatan','expense'=>'Beban'];
    $typeColor = ['asset'=>'#2563eb','liability'=>'#dc2626','equity'=>'#7e22ce','revenue'=>'#16a34a','expense'=>'#ea580c'];
@endphp

@push('head')
    @include('production.dashboard.partials._gf-styles')
    <style>
        .bb-page { display:grid; gap:1rem; }
        .bb-btn { display:inline-flex; align-items:center; gap:.4rem; min-height:38px; padding:.45rem .9rem; border-radius:999px; border:1px solid rgba(15,23,42,.10); background:#fff; color:#0f172a; font-size:.83rem; font-weight:850; cursor:pointer; text-decoration:none; }
        .bb-btn:hover { background:#f8fafc; color:#0f172a; }
        .bb-type-head { background:#f1f5f9; font-size:.74rem; font-weight:900; text-transform:uppercase; letter-spacing:.05em; color:#475569; }
        .bb-num { text-align:right; font-variant-numeric:tabular-nums; font-weight:800; }
        .bb-row:hover td { background:#f8fafc; }
        .bb-row td { vertical-align:middle; }
    </style>
@endphp

@section('content')
<div class="bb-page">

    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div>
            <h5 class="mb-0 fw-black">Buku Besar</h5>
            <div class="text-muted" style="font-size:.8rem">Mutasi per akun · klik kode untuk detail transaksi</div>
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
        <button type="submit" class="bb-btn" style="background:#0f172a;color:#fff;border-color:#0f172a">Tampilkan</button>
        @php
            $shortcuts = [
                'Bulan Ini'  => ['from'=>now()->startOfMonth()->format('Y-m-d'),'to'=>now()->format('Y-m-d')],
                'Bulan Lalu' => ['from'=>now()->subMonth()->startOfMonth()->format('Y-m-d'),'to'=>now()->subMonth()->endOfMonth()->format('Y-m-d')],
                'Tahun Ini'  => ['from'=>now()->startOfYear()->format('Y-m-d'),'to'=>now()->format('Y-m-d')],
            ];
        @endphp
        @foreach($shortcuts as $label => $q)
            <a href="{{ route('accounting.buku-besar.index', $q) }}" class="bb-btn" style="font-size:.76rem">{{ $label }}</a>
        @endforeach
    </form>

    <div class="card border-0 shadow-sm p-0">
        <div style="overflow-x:auto">
            <table class="table table-sm mb-0" style="font-size:.83rem">
                <thead class="table-light">
                    <tr>
                        <th style="width:90px">Kode</th>
                        <th>Nama Akun</th>
                        <th class="text-center" style="width:80px">Transaksi</th>
                        <th class="bb-num">Total Debit</th>
                        <th class="bb-num">Total Kredit</th>
                        <th class="bb-num">Mutasi Bersih</th>
                        <th style="width:80px"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows->groupBy('type') as $type => $typeRows)
                        <tr class="bb-type-head">
                            <td colspan="7" style="padding:.5rem .75rem">
                                <span style="color:{{ $typeColor[$type] ?? '#475569' }}">{{ $typeLabel[$type] ?? $type }}</span>
                            </td>
                        </tr>
                        @foreach($typeRows as $r)
                            @php $net = round($r->period_debit - $r->period_credit, 2); @endphp
                            <tr class="bb-row">
                                <td>
                                    <a href="{{ route('accounting.accounts.ledger', $r->id) }}?from={{ $from }}&to={{ $to }}"
                                       style="color:#2563eb; text-decoration:none; font-weight:800">{{ $r->code }}</a>
                                </td>
                                <td style="font-weight:700">{{ $r->name }}</td>
                                <td class="text-center">
                                    <span class="badge bg-light text-dark" style="font-weight:800">{{ $r->tx_count }}</span>
                                </td>
                                <td class="bb-num">{{ $fmt($r->period_debit) }}</td>
                                <td class="bb-num">{{ $fmt($r->period_credit) }}</td>
                                <td class="bb-num" style="color:{{ $net > 0 ? '#0f172a' : ($net < 0 ? '#dc2626' : '#94a3b8') }}">
                                    @if($net != 0)
                                        {{ $net < 0 ? '(' : '' }}Rp {{ number_format(abs($net), 0, ',', '.') }}{{ $net < 0 ? ')' : '' }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('accounting.accounts.ledger', $r->id) }}?from={{ $from }}&to={{ $to }}"
                                       class="bb-btn" style="min-height:28px;padding:.15rem .6rem;font-size:.74rem">
                                        Detail →
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    @empty
                        <tr><td colspan="7" style="text-align:center;color:#64748b;padding:3rem">Belum ada transaksi di periode ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
