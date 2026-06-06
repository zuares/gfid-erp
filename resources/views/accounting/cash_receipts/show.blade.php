@extends('layouts.app')

@section('title', 'Accounting • Detail Penerimaan')

@php
    $fmt = fn($n) => number_format((float) $n, 0, ',', '.');
    $status = $cashReceipt->status;
    $statusLabels = [
        'draft' => 'Draft',
        'posted' => 'Tercatat',
        'void' => 'Dibatalkan',
    ];
    $statusLabel = $statusLabels[$status] ?? ucfirst((string) $status);
@endphp

@push('head')
    @include('production.dashboard.partials._gf-styles')
    <style>
        .cr-detail-page { display: grid; gap: 1rem; }
        .cr-actions { display: flex; justify-content: flex-end; gap: .5rem; flex-wrap: wrap; }
        .cr-btn {
            display: inline-flex; align-items: center; justify-content: center; gap: .45rem;
            min-height: 40px; padding: .55rem .95rem; border-radius: 999px;
            border: 1px solid rgba(15, 23, 42, .10); background: #fff;
            color: #0f172a; text-decoration: none; font-size: .84rem; font-weight: 850;
        }
        .cr-btn:hover { color: #0f172a; background: #f8fafc; }
        .cr-btn-primary { color: #fff; background: #0f172a; border-color: #0f172a; }
        .cr-btn-primary:hover { color: #fff; background: #1e293b; }
        .cr-btn-danger { color: #b91c1c; border-color: #fecaca; background: #fff5f5; }
        .cr-btn-danger:hover { color: #991b1b; background: #fee2e2; }
        .cr-kpi-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: .75rem; }
        .cr-kpi { border: 1px solid rgba(15, 23, 42, .08); border-radius: 12px; background: #fff; padding: .85rem .95rem; }
        .cr-kpi-label { color: #64748b; font-size: .68rem; font-weight: 900; text-transform: uppercase; letter-spacing: .06em; }
        .cr-kpi-value { margin-top: .18rem; color: #0f172a; font-size: 1.14rem; font-weight: 950; line-height: 1.15; font-variant-numeric: tabular-nums; }
        .cr-meta { margin-top: .16rem; color: #64748b; font-size: .78rem; }
        .cr-status {
            display: inline-flex; align-items: center; gap: .35rem; border-radius: 999px;
            padding: .22rem .6rem; font-size: .74rem; font-weight: 850; border: 1px solid transparent;
        }
        .cr-status::before { content: ''; width: 7px; height: 7px; border-radius: 999px; background: currentColor; }
        .cr-status-draft { color: #b45309; background: #fef3c7; border-color: #fde68a; }
        .cr-status-posted { color: #166534; background: #dcfce7; border-color: #bbf7d0; }
        .cr-status-void { color: #b91c1c; background: #fee2e2; border-color: #fecaca; }
        .cr-info-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .75rem; }
        .cr-info { border: 1px solid #e2e8f0; border-radius: 12px; background: #fff; padding: .8rem .9rem; }
        .cr-info-label { color: #64748b; font-size: .72rem; font-weight: 900; text-transform: uppercase; letter-spacing: .05em; }
        .cr-info-value { margin-top: .2rem; color: #0f172a; font-weight: 900; }
        .cr-post-note {
            color: #1e3a8a; background: #eff6ff; border: 1px solid rgba(37, 99, 235, .14);
            border-radius: 12px; padding: .75rem .85rem; font-size: .84rem; font-weight: 720;
        }
        .cr-inline-form { display: inline-flex; gap: .5rem; align-items: center; flex-wrap: wrap; }
        .cr-reason { min-height: 40px; border-radius: 999px; border: 1px solid rgba(15, 23, 42, .12); padding: .45rem .75rem; min-width: 230px; box-shadow: none; }
        @media (max-width: 768px) {
            .gf-master-header { padding: 12px 14px; border-radius: 14px; }
            .gf-master-title { font-size: 18px; }
            .gf-master-desc { font-size: 11.5px; }
            .gf-master-actions { flex: 1 1 100%; }
            .cr-actions { justify-content: stretch; }
            .cr-actions .cr-btn, .cr-actions form { flex: 1 1 auto; }
            .cr-actions form .cr-btn { width: 100%; }
            .cr-kpi-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .55rem; }
            .cr-kpi { padding: .7rem .75rem; }
            .cr-kpi-value { font-size: 1.02rem; }
            .cr-info-grid { grid-template-columns: 1fr; }
            .cr-inline-form { display: flex; width: 100%; }
            .cr-reason { width: 100%; min-width: 0; }
        }
    </style>
@endpush

@section('content')
    <x-gf.page
        eyebrow="Accounting"
        title="Detail Penerimaan"
        description="{{ $cashReceipt->description ?: 'Penerimaan kas harian' }}">
        <x-slot:actions>
            <div class="cr-actions">
                <a class="cr-btn" href="{{ route('accounting.cash-receipts.index') }}">Daftar Penerimaan</a>
                @if ($cashReceipt->status === 'draft')
                    <a class="cr-btn" href="{{ route('accounting.cash-receipts.edit', $cashReceipt) }}">Edit</a>
                    <form method="POST"
                        action="{{ route('accounting.cash-receipts.destroy', $cashReceipt) }}"
                        data-gf-confirm
                        data-gf-confirm-title="Hapus draft?"
                        data-gf-confirm-text="Draft penerimaan ini akan dihapus."
                        data-gf-confirm-icon="warning"
                        data-gf-confirm-ok="Ya, hapus">
                        @csrf
                        @method('DELETE')
                        <button class="cr-btn cr-btn-danger" type="submit">Hapus</button>
                    </form>
                @endif
            </div>
        </x-slot:actions>

        <div class="cr-detail-page">
            @if (session('message'))
                <div class="alert alert-{{ session('status') === 'error' ? 'danger' : 'success' }} mb-0">
                    {{ session('message') }}
                </div>
            @endif

            <div class="cr-kpi-grid">
                <div class="cr-kpi">
                    <div class="cr-kpi-label">Status</div>
                    <div class="cr-kpi-value">
                        <span class="cr-status cr-status-{{ $status }}">{{ $statusLabel }}</span>
                    </div>
                    <div class="cr-meta">ID #{{ $cashReceipt->id }}</div>
                </div>
                <div class="cr-kpi">
                    <div class="cr-kpi-label">Nominal</div>
                    <div class="cr-kpi-value">Rp {{ $fmt($cashReceipt->amount) }}</div>
                    <div class="cr-meta">nilai transaksi</div>
                </div>
                <div class="cr-kpi">
                    <div class="cr-kpi-label">Tanggal</div>
                    <div class="cr-kpi-value">{{ \Illuminate\Support\Carbon::parse($cashReceipt->date)->format('Y-m-d') }}</div>
                    <div class="cr-meta">{{ \Illuminate\Support\Carbon::parse($cashReceipt->date)->translatedFormat('l') }}</div>
                </div>
                <div class="cr-kpi">
                    <div class="cr-kpi-label">Jurnal</div>
                    <div class="cr-kpi-value">{{ $cashReceipt->journal ? '#' . $cashReceipt->journal->id : '-' }}</div>
                    <div class="cr-meta">{{ $cashReceipt->journal ? 'sudah ada bukti jurnal' : 'belum posting' }}</div>
                </div>
            </div>

            <x-gf.panel title="Rincian Transaksi" subtitle="Saat diposting, kas/bank akan didebit dan sumber penerimaan akan dikredit.">
                <div class="cr-info-grid">
                    <div class="cr-info">
                        <div class="cr-info-label">Terima Ke</div>
                        <div class="cr-info-value">{{ $cashReceipt->cashAccount?->name ?? '-' }}</div>
                        <div class="cr-meta">{{ $cashReceipt->cashAccount?->code ?? '' }}</div>
                    </div>
                    <div class="cr-info">
                        <div class="cr-info-label">Sumber Penerimaan</div>
                        <div class="cr-info-value">{{ $cashReceipt->sourceAccount?->name ?? '-' }}</div>
                        <div class="cr-meta">{{ $cashReceipt->sourceAccount?->code ?? '' }}</div>
                    </div>
                    <div class="cr-info">
                        <div class="cr-info-label">No. Referensi</div>
                        <div class="cr-info-value">{{ $cashReceipt->reference ?: '-' }}</div>
                    </div>
                    <div class="cr-info">
                        <div class="cr-info-label">Catatan</div>
                        <div class="cr-info-value">{{ $cashReceipt->notes ?: '-' }}</div>
                    </div>
                </div>
            </x-gf.panel>

            @if ($cashReceipt->journal)
                <x-gf.panel title="Bukti Jurnal" subtitle="Jurnal dibuat otomatis saat transaksi diposting.">
                    <div class="d-flex justify-content-between align-items-center gap-2 flex-wrap">
                        <div>
                            <div class="fw-bold text-dark">Jurnal #{{ $cashReceipt->journal->id }}</div>
                            <div class="cr-meta">{{ $cashReceipt->journal->description }}</div>
                        </div>
                        <a class="cr-btn" href="{{ route('accounting.journals.show', $cashReceipt->journal) }}">Lihat Jurnal</a>
                    </div>
                </x-gf.panel>
            @endif

            <x-gf.panel title="Aksi Berikutnya" subtitle="Draft bisa diposting. Transaksi tercatat bisa dibatalkan dengan void/reversal.">
                <div class="d-flex justify-content-between align-items-center gap-3 flex-wrap">
                    <div class="cr-post-note">
                        @if ($cashReceipt->status === 'draft')
                            Posting akan mengunci transaksi dan membuat jurnal: debit kas/bank, kredit sumber penerimaan.
                        @elseif ($cashReceipt->status === 'posted')
                            Jika salah, gunakan Void. Sistem akan membuat jurnal pembalik agar saldo kembali netral.
                        @else
                            Transaksi ini sudah dibatalkan.
                        @endif
                    </div>

                    @if ($cashReceipt->status === 'draft')
                        <form method="POST"
                            action="{{ route('accounting.cash-receipts.post', $cashReceipt) }}"
                            data-gf-confirm
                            data-gf-confirm-title="Posting penerimaan?"
                            data-gf-confirm-text="Setelah posting, transaksi akan terkunci dan jurnal dibuat."
                            data-gf-confirm-ok="Ya, posting">
                            @csrf
                            <button class="cr-btn cr-btn-primary" type="submit">Posting</button>
                        </form>
                    @endif

                    @if ($cashReceipt->status === 'posted')
                        <form method="POST"
                            action="{{ route('accounting.cash-receipts.void', $cashReceipt) }}"
                            class="cr-inline-form"
                            data-gf-confirm
                            data-gf-confirm-title="Void penerimaan?"
                            data-gf-confirm-text="Sistem akan membuat jurnal pembalik untuk membatalkan transaksi ini."
                            data-gf-confirm-icon="warning"
                            data-gf-confirm-ok="Ya, void">
                            @csrf
                            <input class="cr-reason" type="text" name="reason" maxlength="255" placeholder="Alasan batal (opsional)">
                            <button class="cr-btn cr-btn-danger" type="submit">Void</button>
                        </form>
                    @endif
                </div>
            </x-gf.panel>
        </div>
    </x-gf.page>
@endsection
