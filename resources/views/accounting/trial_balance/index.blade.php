@extends('layouts.app')
@section('title', 'Neraca Saldo')

@php
    $fmt = fn($n) => $n > 0 ? number_format((float)$n, 0, ',', '.') : '-';
    $typeLabel = ['asset'=>'Aset','liability'=>'Liabilitas','equity'=>'Ekuitas','revenue'=>'Pendapatan','expense'=>'Beban'];
@endphp

@push('head')
    @include('production.dashboard.partials._gf-styles')
    <style>
        .tb-page { display:grid; gap:1rem; }
        .tb-btn { display:inline-flex; align-items:center; gap:.4rem; min-height:38px; padding:.45rem .9rem; border-radius:999px; border:1px solid rgba(15,23,42,.10); background:#fff; color:#0f172a; font-size:.83rem; font-weight:850; cursor:pointer; text-decoration:none; }
        .tb-btn:hover { background:#f8fafc; color:#0f172a; }
        .tb-type-header { background:#f1f5f9; font-weight:900; font-size:.78rem; text-transform:uppercase; letter-spacing:.05em; color:#475569; }
        .tb-total-row td { font-weight:950; border-top:2px solid #0f172a !important; font-size:.92rem; }
        .tb-num { text-align:right; font-variant-numeric:tabular-nums; font-weight:800; }
        .tb-balanced { color:#16a34a; font-size:.8rem; font-weight:900; }
        .tb-unbalanced { color:#dc2626; font-size:.8rem; font-weight:900; }
        .tb-zero { color:#94a3b8; }
    </style>
@endphp

@section('content')
<div class="tb-page">

    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div>
            <h5 class="mb-0 fw-black">Neraca Saldo</h5>
            <div class="text-muted" style="font-size:.8rem">
                @if($cutoffDate && !$showLegacy)
                    Jurnal sistem baru mulai {{ \Illuminate\Support\Carbon::parse($cutoffDate)->format('d-m-Y') }} s.d. tanggal terpilih
                @else
                    Akumulasi semua jurnal s.d. tanggal terpilih
                @endif
            </div>
        </div>
        @php $balanced = abs($totalBalanceDebit - $totalBalanceCredit) < 0.02; @endphp
        @if($rows->count() > 0)
            <span class="{{ $balanced ? 'tb-balanced' : 'tb-unbalanced' }}">
                {{ $balanced ? '✓ Balanced' : '✗ Tidak Balanced' }}
            </span>
        @endif
    </div>

    {{-- Filter --}}
    <form method="GET" class="d-flex gap-2 align-items-end flex-wrap">
        <div>
            <label class="form-label fw-bold" style="font-size:.74rem">Per Tanggal</label>
            <input type="date" name="as_of" class="form-control"
                   style="min-height:38px; border-radius:999px; border-color:rgba(15,23,42,.12); font-size:.83rem; font-weight:700; box-shadow:none; width:160px"
                   value="{{ $asOf }}">
        </div>
        <button type="submit" class="tb-btn" style="background:#0f172a;color:#fff;border-color:#0f172a">Tampilkan</button>
        @if($cutoffDate)
            <label class="d-flex align-items-center gap-1 mb-2" style="font-size:.76rem;font-weight:700;color:#475569;">
                <input type="checkbox" name="show_legacy" value="1" @checked($showLegacy)>
                Tampilkan legacy
            </label>
        @endif
    </form>

    {{-- Table --}}
    <div class="card border-0 shadow-sm p-0">
        <div style="overflow-x:auto">
            <table class="table table-sm mb-0" style="font-size:.83rem">
                <thead class="table-light">
                    <tr>
                        <th style="width:90px">Kode</th>
                        <th>Nama Akun</th>
                        <th class="tb-num">Total Debit</th>
                        <th class="tb-num">Total Kredit</th>
                        <th class="tb-num">Saldo Debit</th>
                        <th class="tb-num">Saldo Kredit</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows->groupBy('type') as $type => $typeRows)
                        <tr class="tb-type-header">
                            <td colspan="6">{{ $typeLabel[$type] ?? $type }}</td>
                        </tr>
                        @foreach($typeRows as $r)
                            <tr>
                                <td>
                                    <a href="{{ route('accounting.accounts.ledger', $r->id) }}?as_of={{ $asOf }}"
                                       style="color:#2563eb; text-decoration:none; font-weight:700">{{ $r->code }}</a>
                                </td>
                                <td>{{ $r->name }}</td>
                                <td class="tb-num">{{ $fmt($r->debit) }}</td>
                                <td class="tb-num">{{ $fmt($r->credit) }}</td>
                                <td class="tb-num {{ $r->balance_debit > 0 ? 'text-dark' : 'tb-zero' }}">
                                    {{ $fmt($r->balance_debit) }}
                                </td>
                                <td class="tb-num {{ $r->balance_credit > 0 ? 'text-dark' : 'tb-zero' }}">
                                    {{ $fmt($r->balance_credit) }}
                                </td>
                            </tr>
                        @endforeach
                    @empty
                        <tr><td colspan="6" style="text-align:center;color:#64748b;padding:3rem">Belum ada data jurnal.</td></tr>
                    @endforelse

                    @if($rows->count() > 0)
                        <tr class="tb-total-row">
                            <td colspan="2">TOTAL</td>
                            <td class="tb-num">Rp {{ number_format($totalDebit, 0, ',', '.') }}</td>
                            <td class="tb-num">Rp {{ number_format($totalCredit, 0, ',', '.') }}</td>
                            <td class="tb-num">Rp {{ number_format($totalBalanceDebit, 0, ',', '.') }}</td>
                            <td class="tb-num">Rp {{ number_format($totalBalanceCredit, 0, ',', '.') }}</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
