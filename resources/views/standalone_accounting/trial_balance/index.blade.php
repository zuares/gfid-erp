@extends('layouts.app')

@section('title', 'Akuntansi Baru • Neraca Saldo')

@php
    $typeLabel = [
        'asset' => 'Aset',
        'liability' => 'Liabilitas',
        'equity' => 'Ekuitas',
        'revenue' => 'Pendapatan',
        'expense' => 'Beban',
    ];
    $money = fn ($value) => $value > 0 ? 'Rp ' . number_format((float) $value, 0, ',', '.') : '-';
    $balanced = abs($totalBalanceDebit - $totalBalanceCredit) < 0.01;
@endphp

@push('head')
    <style>
        .new-accounting-page { display: grid; gap: 1rem; }
        .new-accounting-actions { display:flex; gap:.5rem; flex-wrap:wrap; }
        .new-accounting-btn { display:inline-flex; align-items:center; min-height:38px; padding:.45rem .85rem; border:1px solid #dbe3ee; border-radius:10px; background:#fff; color:#0f172a; text-decoration:none; font-size:.84rem; font-weight:700; }
        .new-accounting-btn:hover { background:#f8fafc; color:#0f172a; }
        .new-accounting-btn.primary { background:#0f172a; border-color:#0f172a; color:#fff; }
        .new-accounting-btn.primary:hover { background:#1e293b; color:#fff; }
        .new-accounting-num { text-align:right; white-space:nowrap; font-variant-numeric:tabular-nums; }
        .new-accounting-total td { border-top:2px solid #0f172a; font-weight:800; }
        .new-accounting-type td { background:#f1f5f9; color:#475569; font-size:.76rem; font-weight:800; text-transform:uppercase; letter-spacing:.04em; }
        .new-accounting-muted { color:#64748b; font-size:.82rem; }
    </style>
@endpush

@section('content')
    <div class="new-accounting-page">
        <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div>
                <h4 class="mb-1">Akuntansi Baru — Neraca Saldo</h4>
                <div class="new-accounting-muted">Modul terpisah dari akuntansi lama. Data di bawah hanya berasal dari tabel akuntansi baru.</div>
            </div>
            <div class="new-accounting-actions">
                <a class="new-accounting-btn" href="{{ route('standalone-accounting.accounts.index') }}">Kelola Akun</a>
                <a class="new-accounting-btn" href="{{ route('standalone-accounting.journals.index') }}">Daftar Jurnal</a>
                <a class="new-accounting-btn primary" href="{{ route('standalone-accounting.journals.create') }}">+ Buat Jurnal</a>
            </div>
        </div>

        @if (session('message'))
            <div class="alert alert-success mb-0">{{ session('message') }}</div>
        @endif

        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <form method="GET" class="d-flex align-items-end gap-2 flex-wrap">
                    <div>
                        <label for="as_of" class="form-label mb-1">Per tanggal</label>
                        <input id="as_of" name="as_of" type="date" value="{{ $asOf }}" class="form-control" required>
                    </div>
                    <button class="new-accounting-btn primary" type="submit">Tampilkan</button>
                </form>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Kode</th>
                            <th>Nama Akun</th>
                            <th class="new-accounting-num">Total Debit</th>
                            <th class="new-accounting-num">Total Kredit</th>
                            <th class="new-accounting-num">Saldo Debit</th>
                            <th class="new-accounting-num">Saldo Kredit</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rows->groupBy('type') as $type => $typeRows)
                            <tr class="new-accounting-type"><td colspan="6">{{ $typeLabel[$type] ?? $type }}</td></tr>
                            @foreach ($typeRows as $row)
                                <tr>
                                    <td class="fw-semibold">{{ $row->code }}</td>
                                    <td>{{ $row->name }}<div class="new-accounting-muted">{{ $row->description }}</div></td>
                                    <td class="new-accounting-num">{{ $money($row->total_debit) }}</td>
                                    <td class="new-accounting-num">{{ $money($row->total_credit) }}</td>
                                    <td class="new-accounting-num">{{ $money($row->balance_debit) }}</td>
                                    <td class="new-accounting-num">{{ $money($row->balance_credit) }}</td>
                                </tr>
                            @endforeach
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-5">Belum ada akun.</td></tr>
                        @endforelse
                        @if ($rows->isNotEmpty())
                            <tr class="new-accounting-total">
                                <td colspan="2">TOTAL</td>
                                <td class="new-accounting-num">{{ $money($totalDebit) }}</td>
                                <td class="new-accounting-num">{{ $money($totalCredit) }}</td>
                                <td class="new-accounting-num">{{ $money($totalBalanceDebit) }}</td>
                                <td class="new-accounting-num">{{ $money($totalBalanceCredit) }}</td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
            <div class="card-footer bg-white border-0 text-end">
                <span class="{{ $balanced ? 'text-success' : 'text-danger' }} fw-semibold">
                    {{ $balanced ? '✓ Neraca saldo seimbang' : '✗ Neraca saldo belum seimbang' }}
                </span>
            </div>
        </div>
    </div>
@endsection
