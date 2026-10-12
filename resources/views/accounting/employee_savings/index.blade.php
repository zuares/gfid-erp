@extends('layouts.app')

@section('title', 'Accounting • Tabungan Karyawan')

@push('head')
    @include('production.dashboard.partials._gf-styles')
    <style>
        .es-wrap { display: grid; gap: 1rem; }
        .es-actions { display: flex; gap: .55rem; flex-wrap: wrap; justify-content: flex-end; }
        .es-btn { display: inline-flex; align-items: center; gap: .4rem; min-height: 40px; padding: .55rem .9rem; border: 1px solid #dbe3ee; border-radius: 999px; background: #fff; color: #0f172a; text-decoration: none; font-size: .82rem; font-weight: 800; }
        .es-btn-primary { color: #fff; background: #0f172a; border-color: #0f172a; }
        .es-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: .75rem; }
        .es-card { padding: 1rem; border: 1px solid #e5eaf1; border-radius: 14px; background: #fff; }
        .es-label { color: #64748b; font-size: .72rem; font-weight: 800; text-transform: uppercase; letter-spacing: .04em; }
        .es-value { margin-top: .25rem; color: #0f172a; font-size: 1.15rem; font-weight: 900; }
        .es-filter { display: flex; gap: .65rem; align-items: end; flex-wrap: wrap; }
        .es-filter label { display: grid; gap: .3rem; min-width: 190px; color: #475569; font-size: .75rem; font-weight: 800; }
        .es-control { min-height: 40px; border-radius: 10px; border-color: #dbe3ee; }
        .es-table { width: 100%; }
        .es-table th { color: #64748b; font-size: .7rem; text-transform: uppercase; letter-spacing: .04em; }
        .es-table td { vertical-align: middle; }
        .es-status { display: inline-flex; padding: .25rem .55rem; border-radius: 999px; font-size: .7rem; font-weight: 800; }
        .es-status-posted { color: #166534; background: #dcfce7; }
        .es-status-draft { color: #92400e; background: #fef3c7; }
        .es-status-void { color: #991b1b; background: #fee2e2; }
        .es-type-payment { color: #991b1b; }
        .es-type-deposit { color: #166534; }
        @media (max-width: 900px) { .es-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
        @media (max-width: 600px) { .es-grid { grid-template-columns: 1fr 1fr; } .es-table-wrap { overflow-x: auto; } .es-table { min-width: 760px; } }
    </style>
@endpush

@section('content')
    @php
        $fmt = fn ($value) => number_format((float) $value, 0, ',', '.');
        $labels = ['posted' => 'POSTED', 'draft' => 'DRAFT', 'void' => 'VOID'];
    @endphp

    <x-gf.page eyebrow="Accounting" title="Tabungan Karyawan">
        <x-slot:actions>
            <div class="es-actions">
                <a class="es-btn es-btn-primary" href="{{ route('accounting.employee-savings.create') }}"><i class="bi bi-wallet2"></i> Bayar Tabungan</a>
            </div>
        </x-slot:actions>

        <div class="es-wrap">
            <div class="es-grid">
                <div class="es-card"><div class="es-label">Total disisihkan</div><div class="es-value">Rp {{ $fmt($summary['deposited']) }}</div></div>
                <div class="es-card"><div class="es-label">Total dibayar</div><div class="es-value">Rp {{ $fmt($summary['paid']) }}</div></div>
                <div class="es-card"><div class="es-label">Kewajiban tersisa</div><div class="es-value">Rp {{ $fmt($summary['outstanding']) }}</div></div>
                <div class="es-card"><div class="es-label">Draft pembayaran</div><div class="es-value">{{ $summary['draft'] }}</div></div>
            </div>

            <x-gf.panel title="Filter transaksi">
                <form class="es-filter" method="GET" action="{{ route('accounting.employee-savings.index') }}">
                    <label>Karyawan
                        <select class="form-select es-control" name="employee_id">
                            <option value="">Semua karyawan</option>
                            @foreach ($employees as $employee)
                                <option value="{{ $employee->id }}" @selected((int) request('employee_id') === $employee->id)>{{ $employee->name }} · {{ $employee->code }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label>Status
                        <select class="form-select es-control" name="status">
                            <option value="">Semua status</option>
                            @foreach ($labels as $key => $label)
                                <option value="{{ $key }}" @selected(request('status') === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </label>
                    <button class="es-btn es-btn-primary" type="submit">Terapkan</button>
                    <a class="es-btn" href="{{ route('accounting.employee-savings.index') }}">Reset</a>
                </form>
            </x-gf.panel>

            <x-gf.panel title="Riwayat tabungan" subtitle="Pembayaran diposting sebagai Debit akun 2104 · Tabungan Karyawan dan Kredit Kas/Bank.">
                <div class="es-table-wrap">
                    <table class="table es-table align-middle mb-0">
                        <thead><tr><th>Tanggal</th><th>Karyawan</th><th>Jenis</th><th>Nominal</th><th>Kas/Bank</th><th>Status</th><th>Aksi</th></tr></thead>
                        <tbody>
                            @forelse ($transactions as $transaction)
                                <tr>
                                    <td>{{ optional($transaction->date)->format('d/m/Y') }}</td>
                                    <td><strong>{{ $transaction->employee?->name ?? '-' }}</strong><div class="small text-muted">{{ $transaction->employee?->code }}</div></td>
                                    <td class="{{ $transaction->type === 'payment' ? 'es-type-payment' : 'es-type-deposit' }}">{{ $transaction->type === 'payment' ? 'Pembayaran' : 'Setoran payroll' }}</td>
                                    <td class="fw-bold">Rp {{ $fmt($transaction->amount) }}</td>
                                    <td>{{ $transaction->cashAccount?->name ?? '—' }}</td>
                                    <td><span class="es-status es-status-{{ $transaction->status }}">{{ $labels[$transaction->status] ?? strtoupper($transaction->status) }}</span></td>
                                    <td><a class="es-btn" href="{{ route('accounting.employee-savings.show', $transaction) }}">Detail</a></td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="text-center text-muted py-5">Belum ada transaksi tabungan.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($transactions->hasPages())<div class="mt-3">{{ $transactions->links() }}</div>@endif
            </x-gf.panel>
        </div>
    </x-gf.page>
@endsection
