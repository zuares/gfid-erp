@extends('layouts.app')

@section('title', 'Accounting • Detail Transfer Kas/Bank')

@php
    $fmt = fn ($n) => number_format((float) $n, 0, ',', '.');
    $status = $cashTransfer->status;
    $statusLabels = ['draft' => 'Draft', 'posted' => 'Tercatat', 'void' => 'Dibatalkan'];
@endphp

@push('head')
    @include('production.dashboard.partials._gf-styles')
    <style>
        .ct-detail-page { display: grid; gap: 1rem; }
        .ct-actions { display: flex; justify-content: flex-end; gap: .5rem; flex-wrap: wrap; }
        .ct-btn { display: inline-flex; align-items: center; justify-content: center; min-height: 40px; padding: .55rem .95rem; border-radius: 999px; border: 1px solid rgba(15,23,42,.1); background: #fff; color: #0f172a; text-decoration: none; font-size: .84rem; font-weight: 850; }
        .ct-btn-primary { color: #fff; background: #0f172a; border-color: #0f172a; }
        .ct-btn-danger { color: #b91c1c; background: #fff5f5; border-color: #fecaca; }
        .ct-kpi-grid { display: grid; grid-template-columns: repeat(4, minmax(0,1fr)); gap: .75rem; }
        .ct-kpi, .ct-info { border: 1px solid rgba(15,23,42,.08); border-radius: 12px; background: #fff; padding: .85rem .95rem; }
        .ct-kpi-label, .ct-info-label { color: #64748b; font-size: .68rem; font-weight: 900; text-transform: uppercase; letter-spacing: .06em; }
        .ct-kpi-value { margin-top: .2rem; color: #0f172a; font-size: 1.1rem; font-weight: 950; font-variant-numeric: tabular-nums; }
        .ct-meta { margin-top: .18rem; color: #94a3b8; font-size: .74rem; }
        .ct-info-grid { display: grid; grid-template-columns: repeat(2, minmax(0,1fr)); gap: .75rem; }
        .ct-info-value { margin-top: .2rem; color: #0f172a; font-weight: 900; }
        .ct-status { display: inline-flex; align-items: center; gap: .35rem; border-radius: 999px; padding: .22rem .6rem; font-size: .74rem; font-weight: 850; }
        .ct-status::before { content: ''; width: 7px; height: 7px; border-radius: 999px; background: currentColor; }
        .ct-status-draft { color: #b45309; background: #fef3c7; }
        .ct-status-posted { color: #166534; background: #dcfce7; }
        .ct-status-void { color: #b91c1c; background: #fee2e2; }
        .ct-note { color: #1e3a8a; background: #eff6ff; border: 1px solid rgba(37,99,235,.14); border-radius: 12px; padding: .75rem .85rem; font-size: .84rem; }
        .ct-inline-form { display: inline-flex; gap: .5rem; align-items: center; flex-wrap: wrap; }
        .ct-reason { min-height: 40px; min-width: 230px; border-radius: 999px; border: 1px solid rgba(15,23,42,.12); padding: .45rem .75rem; }
        @media (max-width: 768px) { .ct-kpi-grid { grid-template-columns: repeat(2, minmax(0,1fr)); gap: .55rem; } .ct-kpi { padding: .7rem .75rem; } .ct-kpi-value { font-size: 1rem; } .ct-info-grid { grid-template-columns: 1fr; } .ct-actions { justify-content: stretch; } .ct-actions .ct-btn, .ct-actions form { flex: 1; } .ct-actions form .ct-btn { width: 100%; } .ct-inline-form { width: 100%; } .ct-reason { width: 100%; min-width: 0; } }
    </style>
@endpush

@section('content')
    <x-gf.page eyebrow="Accounting" title="Detail Transfer Kas/Bank" description="{{ $cashTransfer->description ?: 'Transfer antar kas/bank' }}">
        <x-slot:actions>
            <div class="ct-actions">
                <a class="ct-btn" href="{{ route('accounting.cash-transfers.index') }}">Daftar Transfer</a>
                @if ($status === 'draft')
                    <a class="ct-btn" href="{{ route('accounting.cash-transfers.edit', $cashTransfer) }}">Edit</a>
                    <form method="POST" action="{{ route('accounting.cash-transfers.destroy', $cashTransfer) }}" data-gf-confirm data-gf-confirm-title="Hapus draft?" data-gf-confirm-text="Draft transfer ini akan dihapus." data-gf-confirm-icon="warning" data-gf-confirm-ok="Ya, hapus">
                        @csrf @method('DELETE')
                        <button class="ct-btn ct-btn-danger" type="submit">Hapus</button>
                    </form>
                @endif
            </div>
        </x-slot:actions>

        <div class="ct-detail-page">
            <div class="ct-kpi-grid">
                <div class="ct-kpi"><div class="ct-kpi-label">Status</div><div class="ct-kpi-value"><span class="ct-status ct-status-{{ $status }}">{{ $statusLabels[$status] ?? ucfirst($status) }}</span></div><div class="ct-meta">ID #{{ $cashTransfer->id }}</div></div>
                <div class="ct-kpi"><div class="ct-kpi-label">Nominal</div><div class="ct-kpi-value">Rp {{ $fmt($cashTransfer->amount) }}</div><div class="ct-meta">nilai transfer</div></div>
                <div class="ct-kpi"><div class="ct-kpi-label">Tanggal</div><div class="ct-kpi-value">{{ optional($cashTransfer->date)->format('Y-m-d') }}</div><div class="ct-meta">{{ optional($cashTransfer->date)->translatedFormat('l') }}</div></div>
                <div class="ct-kpi"><div class="ct-kpi-label">Jurnal</div><div class="ct-kpi-value">{{ $cashTransfer->journal ? '#'.$cashTransfer->journal->id : '-' }}</div><div class="ct-meta">{{ $cashTransfer->journal ? 'sudah dibuat' : 'belum posting' }}</div></div>
            </div>

            <div class="ct-info-grid">
                <div class="ct-info"><div class="ct-info-label">Dari Kas/Bank</div><div class="ct-info-value">{{ $cashTransfer->fromCashAccount?->name ?? '-' }}{{ $cashTransfer->fromCashAccount?->code ? ' · '.$cashTransfer->fromCashAccount->code : '' }}</div></div>
                <div class="ct-info"><div class="ct-info-label">Ke Kas/Bank</div><div class="ct-info-value">{{ $cashTransfer->toCashAccount?->name ?? '-' }}{{ $cashTransfer->toCashAccount?->code ? ' · '.$cashTransfer->toCashAccount->code : '' }}</div></div>
                <div class="ct-info"><div class="ct-info-label">Keterangan</div><div class="ct-info-value">{{ $cashTransfer->description ?: '-' }}</div></div>
                <div class="ct-info"><div class="ct-info-label">No. Referensi</div><div class="ct-info-value">{{ $cashTransfer->reference ?: '-' }}</div></div>
            </div>

            @if ($cashTransfer->journal)
                <x-gf.panel title="Bukti Jurnal" subtitle="Jurnal dibuat otomatis saat transfer diposting.">
                    <div class="d-flex justify-content-between align-items-center gap-2 flex-wrap"><div><div class="fw-bold text-dark">Jurnal #{{ $cashTransfer->journal->id }}</div><div class="ct-meta">{{ $cashTransfer->journal->description }}</div></div><a class="ct-btn" href="{{ route('accounting.journals.show', $cashTransfer->journal) }}">Lihat Jurnal</a></div>
                </x-gf.panel>
            @endif

            <x-gf.panel title="Aksi Berikutnya" subtitle="Draft bisa diposting. Transfer tercatat bisa dibatalkan dengan void/reversal.">
                <div class="d-flex justify-content-between align-items-center gap-3 flex-wrap">
                    <div class="ct-note">
                        @if ($status === 'draft')
                            Posting akan membuat jurnal: debit {{ $cashTransfer->toCashAccount?->name ?? 'akun tujuan' }}, kredit {{ $cashTransfer->fromCashAccount?->name ?? 'akun asal' }}.
                        @elseif ($status === 'posted')
                            Jika salah, gunakan Void. Sistem akan membuat jurnal pembalik.
                        @else
                            Transfer ini sudah dibatalkan.
                        @endif
                    </div>
                    @if ($status === 'draft')
                        <form method="POST" action="{{ route('accounting.cash-transfers.post', $cashTransfer) }}" data-gf-confirm data-gf-confirm-title="Posting transfer?" data-gf-confirm-text="Setelah posting, transaksi akan terkunci dan jurnal dibuat." data-gf-confirm-ok="Ya, posting">
                            @csrf <button class="ct-btn ct-btn-primary" type="submit">Posting</button>
                        </form>
                    @elseif ($status === 'posted')
                        <form method="POST" action="{{ route('accounting.cash-transfers.void', $cashTransfer) }}" class="ct-inline-form" data-gf-confirm data-gf-confirm-title="Void transfer?" data-gf-confirm-text="Sistem akan membuat jurnal pembalik untuk membatalkan transfer ini." data-gf-confirm-icon="warning" data-gf-confirm-ok="Ya, void">
                            @csrf <input class="ct-reason" type="text" name="reason" maxlength="255" placeholder="Alasan batal (opsional)"><button class="ct-btn ct-btn-danger" type="submit">Void</button>
                        </form>
                    @endif
                </div>
            </x-gf.panel>
        </div>
    </x-gf.page>
@endsection
