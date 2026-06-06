@extends('layouts.app')

@section('title', 'Accounting • Detail Pengeluaran')

@php
    use Illuminate\Support\Facades\Storage;

    $fmt = fn($n) => number_format((float) $n, 0, ',', '.');
    $status = $cashExpense->status;
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
        .ce-detail-page { display: grid; gap: 1rem; }
        .ce-actions { display: flex; justify-content: flex-end; gap: .5rem; flex-wrap: wrap; }
        .ce-btn {
            display: inline-flex; align-items: center; justify-content: center; gap: .45rem;
            min-height: 40px; padding: .55rem .95rem; border-radius: 999px;
            border: 1px solid rgba(15, 23, 42, .10); background: #fff;
            color: #0f172a; text-decoration: none; font-size: .84rem; font-weight: 850;
        }
        .ce-btn:hover { color: #0f172a; background: #f8fafc; }
        .ce-btn-primary { color: #fff; background: #0f172a; border-color: #0f172a; }
        .ce-btn-primary:hover { color: #fff; background: #1e293b; }
        .ce-btn-danger { color: #b91c1c; border-color: #fecaca; background: #fff5f5; }
        .ce-btn-danger:hover { color: #991b1b; background: #fee2e2; }
        .ce-kpi-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: .75rem; }
        .ce-kpi {
            border: 1px solid rgba(15, 23, 42, .08); border-radius: 12px;
            background: #fff; padding: .85rem .95rem;
        }
        .ce-kpi-label {
            color: #64748b; font-size: .68rem; font-weight: 900;
            text-transform: uppercase; letter-spacing: .06em;
        }
        .ce-kpi-value {
            margin-top: .18rem; color: #0f172a; font-size: 1.14rem;
            font-weight: 950; line-height: 1.15; font-variant-numeric: tabular-nums;
        }
        .ce-meta { margin-top: .16rem; color: #64748b; font-size: .78rem; }
        .ce-status {
            display: inline-flex; align-items: center; gap: .35rem; border-radius: 999px;
            padding: .22rem .6rem; font-size: .74rem; font-weight: 850; border: 1px solid transparent;
        }
        .ce-status::before { content: ''; width: 7px; height: 7px; border-radius: 999px; background: currentColor; }
        .ce-status-draft { color: #b45309; background: #fef3c7; border-color: #fde68a; }
        .ce-status-posted { color: #166534; background: #dcfce7; border-color: #bbf7d0; }
        .ce-status-void { color: #b91c1c; background: #fee2e2; border-color: #fecaca; }
        .ce-info-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .75rem; }
        .ce-info {
            border: 1px solid #e2e8f0; border-radius: 12px; background: #fff;
            padding: .8rem .9rem;
        }
        .ce-info-label { color: #64748b; font-size: .72rem; font-weight: 900; text-transform: uppercase; letter-spacing: .05em; }
        .ce-info-value { margin-top: .2rem; color: #0f172a; font-weight: 900; }
        .ce-post-note {
            color: #1e3a8a; background: #eff6ff; border: 1px solid rgba(37, 99, 235, .14);
            border-radius: 12px; padding: .75rem .85rem; font-size: .84rem; font-weight: 720;
        }
        .ce-inline-form { display: inline-flex; gap: .5rem; align-items: center; flex-wrap: wrap; }
        .ce-reason {
            min-height: 40px; border-radius: 999px; border: 1px solid rgba(15, 23, 42, .12);
            padding: .45rem .75rem; min-width: 230px; box-shadow: none;
        }
        @media (max-width: 768px) {
            .gf-master-header { padding: 12px 14px; border-radius: 14px; }
            .gf-master-title { font-size: 18px; }
            .gf-master-desc { font-size: 11.5px; }
            .gf-master-actions { flex: 1 1 100%; }
            .ce-actions { justify-content: stretch; }
            .ce-actions .ce-btn, .ce-actions form { flex: 1 1 auto; }
            .ce-actions form .ce-btn { width: 100%; }
            .ce-kpi-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .55rem; }
            .ce-kpi { padding: .7rem .75rem; }
            .ce-kpi-value { font-size: 1.02rem; }
            .ce-info-grid { grid-template-columns: 1fr; }
            .ce-inline-form { display: flex; width: 100%; }
            .ce-reason { width: 100%; min-width: 0; }
        }
    </style>
@endpush

@section('content')
    <x-gf.page
        eyebrow="Accounting"
        title="Detail Pengeluaran"
        description="{{ $cashExpense->description ?: 'Pengeluaran kas harian' }}">
        <x-slot:actions>
            <div class="ce-actions">
                <a class="ce-btn" href="{{ route('accounting.cash-expenses.index') }}">Daftar Pengeluaran</a>
                @if ($cashExpense->status === 'draft')
                    <a class="ce-btn" href="{{ route('accounting.cash-expenses.edit', $cashExpense) }}">Edit</a>
                    <form method="POST"
                        action="{{ route('accounting.cash-expenses.destroy', $cashExpense) }}"
                        data-gf-confirm
                        data-gf-confirm-title="Hapus draft?"
                        data-gf-confirm-text="Draft pengeluaran ini akan dihapus."
                        data-gf-confirm-icon="warning"
                        data-gf-confirm-ok="Ya, hapus">
                        @csrf
                        @method('DELETE')
                        <button class="ce-btn ce-btn-danger" type="submit">Hapus</button>
                    </form>
                @endif
            </div>
        </x-slot:actions>

        <div class="ce-detail-page">
            @if (session('message'))
                <div class="alert alert-{{ session('status') === 'error' ? 'danger' : 'success' }} mb-0">
                    {{ session('message') }}
                </div>
            @endif

            <div class="ce-kpi-grid">
                <div class="ce-kpi">
                    <div class="ce-kpi-label">Status</div>
                    <div class="ce-kpi-value">
                        <span class="ce-status ce-status-{{ $status }}">{{ $statusLabel }}</span>
                    </div>
                    <div class="ce-meta">ID #{{ $cashExpense->id }}</div>
                </div>
                <div class="ce-kpi">
                    <div class="ce-kpi-label">Nominal</div>
                    <div class="ce-kpi-value">Rp {{ $fmt($cashExpense->amount) }}</div>
                    <div class="ce-meta">nilai transaksi</div>
                </div>
                <div class="ce-kpi">
                    <div class="ce-kpi-label">Tanggal</div>
                    <div class="ce-kpi-value">{{ \Illuminate\Support\Carbon::parse($cashExpense->date)->format('Y-m-d') }}</div>
                    <div class="ce-meta">{{ \Illuminate\Support\Carbon::parse($cashExpense->date)->translatedFormat('l') }}</div>
                </div>
                <div class="ce-kpi">
                    <div class="ce-kpi-label">Jurnal</div>
                    <div class="ce-kpi-value">{{ $cashExpense->journal ? '#' . $cashExpense->journal->id : '-' }}</div>
                    <div class="ce-meta">{{ $cashExpense->journal ? 'sudah ada bukti jurnal' : 'belum posting' }}</div>
                </div>
            </div>

            <x-gf.panel title="Rincian Transaksi" subtitle="Saat diposting, biaya akan didebit dan kas/bank akan dikredit.">
                <div class="ce-info-grid">
                    <div class="ce-info">
                        <div class="ce-info-label">Kategori Pengeluaran</div>
                        <div class="ce-info-value">{{ $cashExpense->expenseAccount?->name ?? '-' }}</div>
                        <div class="ce-meta">{{ $cashExpense->expenseAccount?->code ?? '' }}</div>
                    </div>
                    <div class="ce-info">
                        <div class="ce-info-label">Bayar Dari</div>
                        <div class="ce-info-value">{{ $cashExpense->cashAccount?->name ?? '-' }}</div>
                        <div class="ce-meta">{{ $cashExpense->cashAccount?->code ?? '' }}</div>
                    </div>
                    <div class="ce-info">
                        <div class="ce-info-label">No. Referensi</div>
                        <div class="ce-info-value">{{ $cashExpense->reference ?: '-' }}</div>
                    </div>
                    <div class="ce-info">
                        <div class="ce-info-label">Catatan</div>
                        <div class="ce-info-value">{{ $cashExpense->notes ?: '-' }}</div>
                    </div>
                    <div class="ce-info">
                        <div class="ce-info-label">Bukti Foto</div>
                        <div class="ce-info-value">
                            @if ($cashExpense->proof_photo_path)
                                <a href="{{ Storage::disk('public')->url($cashExpense->proof_photo_path) }}" target="_blank" rel="noopener">
                                    Lihat bukti foto
                                </a>
                            @else
                                -
                            @endif
                        </div>
                    </div>
                </div>
            </x-gf.panel>

            @if ($cashExpense->proof_photo_path)
                <x-gf.panel title="Preview Bukti Foto" subtitle="Foto struk atau bukti pembayaran.">
                    <img src="{{ Storage::disk('public')->url($cashExpense->proof_photo_path) }}"
                        alt="Bukti foto pengeluaran"
                        style="display:block;max-width:420px;width:100%;border-radius:12px;border:1px solid #e2e8f0">
                </x-gf.panel>
            @endif

            @if ($cashExpense->journal)
                <x-gf.panel title="Bukti Jurnal" subtitle="Jurnal dibuat otomatis saat transaksi diposting.">
                    <div class="d-flex justify-content-between align-items-center gap-2 flex-wrap">
                        <div>
                            <div class="fw-bold text-dark">Jurnal #{{ $cashExpense->journal->id }}</div>
                            <div class="ce-meta">{{ $cashExpense->journal->description }}</div>
                        </div>
                        <a class="ce-btn" href="{{ route('accounting.journals.show', $cashExpense->journal) }}">Lihat Jurnal</a>
                    </div>
                </x-gf.panel>
            @endif

            <x-gf.panel title="Aksi Berikutnya" subtitle="Draft bisa diposting. Transaksi tercatat bisa dibatalkan dengan void/reversal.">
                <div class="d-flex justify-content-between align-items-center gap-3 flex-wrap">
                    <div class="ce-post-note">
                        @if ($cashExpense->status === 'draft')
                            Posting akan mengunci transaksi dan membuat jurnal: debit biaya, kredit kas/bank.
                        @elseif ($cashExpense->status === 'posted')
                            Jika salah, gunakan Void. Sistem akan membuat jurnal pembalik agar saldo kembali netral.
                        @else
                            Transaksi ini sudah dibatalkan.
                        @endif
                    </div>

                    @if ($cashExpense->status === 'draft')
                        <form method="POST"
                            action="{{ route('accounting.cash-expenses.post', $cashExpense) }}"
                            data-gf-confirm
                            data-gf-confirm-title="Posting pengeluaran?"
                            data-gf-confirm-text="Setelah posting, transaksi akan terkunci dan jurnal dibuat."
                            data-gf-confirm-ok="Ya, posting">
                            @csrf
                            <button class="ce-btn ce-btn-primary" type="submit">Posting</button>
                        </form>
                    @endif

                    @if ($cashExpense->status === 'posted')
                        <form method="POST"
                            action="{{ route('accounting.cash-expenses.void', $cashExpense) }}"
                            class="ce-inline-form"
                            data-gf-confirm
                            data-gf-confirm-title="Void pengeluaran?"
                            data-gf-confirm-text="Sistem akan membuat jurnal pembalik untuk membatalkan transaksi ini."
                            data-gf-confirm-icon="warning"
                            data-gf-confirm-ok="Ya, void">
                            @csrf
                            <input class="ce-reason" type="text" name="reason" maxlength="255" placeholder="Alasan batal (opsional)">
                            <button class="ce-btn ce-btn-danger" type="submit">Void</button>
                        </form>
                    @endif
                </div>
            </x-gf.panel>
        </div>
    </x-gf.page>
@endsection
