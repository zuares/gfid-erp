@extends('layouts.app')

@section('title', 'Accounting • Detail Pembayaran Tabungan')

@push('head')
    @include('production.dashboard.partials._gf-styles')
    <style>
        .es-wrap { display: grid; gap: 1rem; max-width: 900px; }
        .es-actions { display: flex; gap: .55rem; justify-content: flex-end; flex-wrap: wrap; }
        .es-btn { display: inline-flex; align-items: center; gap: .4rem; min-height: 40px; padding: .55rem .9rem; border: 1px solid #dbe3ee; border-radius: 999px; background: #fff; color: #0f172a; text-decoration: none; font-size: .82rem; font-weight: 800; }
        .es-btn-primary { color: #fff; background: #0f172a; border-color: #0f172a; }
        .es-btn-danger { color: #991b1b; border-color: #fecaca; background: #fff; }
        .es-detail { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 1rem; }
        .es-item { padding: .8rem 0; border-bottom: 1px solid #eef2f7; }
        .es-label { color: #64748b; font-size: .72rem; font-weight: 800; text-transform: uppercase; letter-spacing: .04em; }
        .es-value { margin-top: .2rem; color: #0f172a; font-weight: 800; }
        .es-status { display: inline-flex; padding: .25rem .55rem; border-radius: 999px; font-size: .7rem; font-weight: 800; }
        .es-status-posted { color: #166534; background: #dcfce7; }
        .es-status-draft { color: #92400e; background: #fef3c7; }
        .es-status-void { color: #991b1b; background: #fee2e2; }
        .es-help { padding: .85rem 1rem; border-radius: 12px; color: #334155; background: #f8fafc; font-size: .82rem; }
        @media (max-width: 650px) { .es-detail { grid-template-columns: 1fr; } }
    </style>
@endpush

@section('content')
    @php $fmt = fn ($value) => number_format((float) $value, 0, ',', '.'); @endphp
    <x-gf.page eyebrow="Accounting" title="Detail Pembayaran Tabungan">
        <x-slot:actions>
            <div class="es-actions">
                <a class="es-btn" href="{{ route('accounting.employee-savings.index') }}"><i class="bi bi-arrow-left"></i> Daftar</a>
                @if ($employeeSavings->status === 'draft')
                    <form method="POST" action="{{ route('accounting.employee-savings.post', $employeeSavings) }}">@csrf<button class="es-btn es-btn-primary" type="submit">Post Pembayaran</button></form>
                @elseif ($employeeSavings->status === 'posted')
                    <form method="POST" action="{{ route('accounting.employee-savings.void', $employeeSavings) }}" onsubmit="return confirm('Void pembayaran ini? Jurnal reversal akan dibuat.')">@csrf<button class="es-btn es-btn-danger" type="submit">Void</button></form>
                @endif
            </div>
        </x-slot:actions>

        <div class="es-wrap">
            <x-gf.panel title="Pembayaran tabungan">
                <div class="es-help mb-3">Saat di-POST: <strong>Debit 2104 · Tabungan Karyawan</strong> dan <strong>Kredit Kas/Bank</strong>.</div>
                <div class="es-detail">
                    <div class="es-item"><div class="es-label">Karyawan</div><div class="es-value">{{ $employeeSavings->employee?->name ?? '-' }} · {{ $employeeSavings->employee?->code }}</div></div>
                    <div class="es-item"><div class="es-label">Status</div><div class="es-value"><span class="es-status es-status-{{ $employeeSavings->status }}">{{ strtoupper($employeeSavings->status) }}</span></div></div>
                    <div class="es-item"><div class="es-label">Tanggal</div><div class="es-value">{{ optional($employeeSavings->date)->format('d/m/Y') }}</div></div>
                    <div class="es-item"><div class="es-label">Nominal</div><div class="es-value">Rp {{ $fmt($employeeSavings->amount) }}</div></div>
                    <div class="es-item"><div class="es-label">Bayar dari</div><div class="es-value">{{ $employeeSavings->cashAccount?->name ?? '-' }}</div></div>
                    <div class="es-item"><div class="es-label">Sisa tabungan sebelum pembayaran</div><div class="es-value">Rp {{ $fmt($outstanding) }}</div></div>
                    <div class="es-item"><div class="es-label">Referensi</div><div class="es-value">{{ $employeeSavings->reference ?: '—' }}</div></div>
                    <div class="es-item"><div class="es-label">Jurnal</div><div class="es-value">{{ $employeeSavings->journal_id ? '#'.$employeeSavings->journal_id : 'Belum diposting' }}</div></div>
                </div>
                @if ($employeeSavings->notes)<div class="mt-3"><div class="es-label">Catatan</div><div class="mt-1">{!! nl2br(e($employeeSavings->notes)) !!}</div></div>@endif
            </x-gf.panel>
        </div>
    </x-gf.page>
@endsection
