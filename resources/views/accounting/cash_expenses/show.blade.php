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
        .ce-detail-shell { width: 100%; max-width: 1180px; margin-inline: auto; }
        .ce-detail-page { display: grid; gap: .75rem; }
        .ce-actions { display: flex; justify-content: flex-end; gap: .35rem; flex-wrap: wrap; }
        .ce-btn {
            display: inline-flex; align-items: center; justify-content: center; gap: .45rem;
            min-height: 40px; padding: .55rem .85rem; border-radius: 999px;
            border: 1px solid rgba(15, 23, 42, .10); background: #fff;
            color: #0f172a; text-decoration: none; font-size: .84rem; font-weight: 850;
        }
        .ce-btn:hover { color: #0f172a; background: #f8fafc; }
        .ce-btn-primary { color: #fff; background: #0f172a; border-color: #0f172a; }
        .ce-btn-primary:hover { color: #fff; background: #1e293b; }
        .ce-btn-danger { color: #b91c1c; border-color: #fecaca; background: #fff5f5; }
        .ce-btn-danger:hover { color: #991b1b; background: #fee2e2; }
        .ce-kpi-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: .75rem; }
        .ce-kpi-grid {
            position: sticky;
            top: calc(var(--app-navbar-offset, 58px) + 68px);
            align-self: start;
            z-index: 20;
            padding: .35rem 0;
            background: color-mix(in srgb, var(--bg, #f8fafc) 94%, transparent);
            -webkit-backdrop-filter: blur(10px);
            backdrop-filter: blur(10px);
        }
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
        .ce-reclass-form { display: grid; gap: .85rem; }
        .ce-reclass-help { color: #64748b; font-size: .82rem; line-height: 1.45; }
        .ce-history-row {
            display: flex; justify-content: space-between; gap: 1rem; align-items: flex-start;
            padding: .75rem 0; border-bottom: 1px dashed #e2e8f0;
        }
        .ce-history-row:last-child { border-bottom: 0; padding-bottom: 0; }
        .ce-history-meta { color: #64748b; font-size: .76rem; margin-top: .18rem; }

        .gf-master-header {
            position: sticky;
            top: var(--app-navbar-offset, 58px);
            z-index: 30;
            padding: .7rem .85rem;
            border-color: rgba(148, 163, 184, .22);
            background: color-mix(in srgb, var(--bg) 92%, transparent);
            -webkit-backdrop-filter: blur(12px);
            backdrop-filter: blur(12px);
        }
        .gf-master-section { gap: .75rem; }
        .gf-master-title { font-size: clamp(1.08rem, 2vw, 1.24rem); font-weight: 900; }
        .gf-master-desc { display: none; }
        .ce-detail-page > .ce-kpi-grid { order: 1; }
        .ce-detail-page > .ce-owner-layout { order: 2; }
        .ce-detail-page > .ce-post-panel { order: 3; }
        .ce-detail-page > .ce-journal-panel { order: 4; }
        .ce-detail-page > .ce-history-panel { order: 5; }
        .ce-actions .ce-btn {
            min-height: 36px;
            padding: .36rem .6rem;
            border-radius: 10px;
            font-size: .78rem;
        }
        .ce-action-icon { font-size: .9rem; }
        .ce-post-panel .gf-panel-header { padding-bottom: .65rem; }
        .ce-post-panel .gf-panel-title { font-size: .94rem; }
        .ce-post-note { flex: 1 1 260px; }
        .ce-owner-row span:last-child { overflow-wrap: anywhere; }
        @media (max-width: 768px) {
            .gf-master-header { padding: 12px 14px; border-radius: 14px; }
            .gf-master-title { font-size: 18px; }
            .gf-master-desc { font-size: 11.5px; }
            .gf-master-actions { flex: 1 1 100%; }
            .gf-master-header-layout { align-items: stretch; }
            .gf-master-header-copy, .gf-master-actions { min-width: 0; width: 100%; }
            .ce-actions { justify-content: stretch; }
            .ce-actions .ce-btn, .ce-actions form { flex: 1 1 0; }
            .ce-actions form .ce-btn { width: 100%; }
            .ce-kpi-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .55rem; }
            .ce-kpi-grid {
                position: static;
                padding: 0;
                background: transparent;
                -webkit-backdrop-filter: none;
                backdrop-filter: none;
            }
            .ce-kpi { padding: .7rem .75rem; }
            .ce-kpi-value { font-size: 1.02rem; }
            .ce-info-grid { grid-template-columns: 1fr; }
            .ce-inline-form { display: flex; width: 100%; }
            .ce-reason { width: 100%; min-width: 0; }
        }
    
        /* AUTO OWNER MINIMAL CASH EXPENSE SHOW */
        .ce-owner-layout {
            display: grid;
            grid-template-columns: minmax(0, 1.15fr) minmax(280px, .85fr);
            gap: 1rem;
            align-items: start;
        }
        .ce-proof-focus {
            border: 1px solid rgba(15, 23, 42, .1);
            border-radius: 18px;
            background: #fff;
            overflow: hidden;
            box-shadow: 0 14px 34px rgba(15, 23, 42, .10);
            order: 2;
        }
        .ce-proof-focus-head {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: .75rem;
            padding: .9rem 1rem;
            border-bottom: 1px solid #eef2f7;
        }
        .ce-proof-focus-title {
            margin: 0;
            font-size: 1rem;
            font-weight: 950;
            color: #0f172a;
        }
        .ce-proof-focus-sub {
            margin-top: .15rem;
            font-size: .78rem;
            color: #64748b;
            font-weight: 700;
        }
        .ce-proof-focus-badge {
            border-radius: 999px;
            padding: .35rem .65rem;
            background: #ecfdf5;
            color: #047857;
            font-size: .72rem;
            font-weight: 900;
            white-space: nowrap;
        }
        .ce-proof-focus-img {
            display: block;
            width: 100%;
            max-height: 68vh;
            object-fit: contain;
            background: #f8fafc;
        }
        .ce-proof-focus-empty {
            padding: 2rem 1rem;
            text-align: center;
            color: #92400e;
            background: #fffbeb;
            font-weight: 850;
        }
        .ce-proof-focus-actions {
            display: flex;
            gap: .5rem;
            flex-wrap: wrap;
            padding: .85rem 1rem 1rem;
        }
        .ce-owner-mini-card {
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            background: #fff;
            padding: 1rem;
            order: 1;
        }
        .ce-owner-mini-title {
            font-size: .82rem;
            color: #64748b;
            font-weight: 950;
            text-transform: uppercase;
            letter-spacing: .05em;
            margin-bottom: .65rem;
        }
        .ce-owner-row {
            display: flex;
            justify-content: space-between;
            gap: .75rem;
            padding: .65rem 0;
            border-bottom: 1px dashed #e2e8f0;
        }
        .ce-owner-row:last-child {
            border-bottom: 0;
            padding-bottom: 0;
        }
        .ce-owner-row span:first-child {
            color: #64748b;
            font-weight: 800;
        }
        .ce-owner-row span:last-child {
            color: #0f172a;
            font-weight: 950;
            text-align: right;
        }
        @media (max-width: 768px) {
            .ce-owner-layout {
                grid-template-columns: 1fr;
            }
            .ce-proof-focus-img {
                max-height: 62vh;
            }
        }

    </style>
@endpush

@section('content')
    <x-gf.page class="ce-detail-shell"
        eyebrow="Accounting"
        title="Pengeluaran #{{ $cashExpense->id }}"
        description="Detail transaksi pengeluaran kas">
        <x-slot:actions>
            <div class="ce-actions">
                <a class="ce-btn" href="{{ route('accounting.accounts.index') }}">
                    <i class="bi bi-wallet2 ce-action-icon" aria-hidden="true"></i><span>Akun</span>
                </a>
                <a class="ce-btn" href="{{ route('accounting.cash-expenses.index') }}">
                    <i class="bi bi-list-ul ce-action-icon" aria-hidden="true"></i><span>Daftar</span>
                </a>
                <a class="ce-btn ce-btn-primary"
                    href="{{ route('accounting.cash-expenses.index', ['open_modal' => 1]) }}">
                    <i class="bi bi-plus-lg ce-action-icon" aria-hidden="true"></i><span>Tambah</span>
                </a>
                @if ($cashExpense->status === 'draft')
                    <a class="ce-btn" href="{{ route('accounting.cash-expenses.edit', $cashExpense) }}">
                        <i class="bi bi-pencil ce-action-icon" aria-hidden="true"></i><span>Edit</span>
                    </a>
                    <form method="POST"
                        action="{{ route('accounting.cash-expenses.destroy', $cashExpense) }}"
                        data-gf-confirm
                        data-gf-confirm-title="Hapus draft?"
                        data-gf-confirm-text="Draft pengeluaran ini akan dihapus."
                        data-gf-confirm-icon="warning"
                        data-gf-confirm-ok="Ya, hapus">
                        @csrf
                        @method('DELETE')
                        <button class="ce-btn ce-btn-danger" type="submit">
                            <i class="bi bi-trash3 ce-action-icon" aria-hidden="true"></i><span>Hapus</span>
                        </button>
                    </form>
                @endif
            </div>
        </x-slot:actions>

        <div class="ce-detail-page">
            <x-gf.panel class="ce-post-panel" title="Tindakan" subtitle="Kelola status transaksi.">
                <div class="d-flex justify-content-between align-items-center gap-3 flex-wrap">
                    <div class="ce-post-note">
                        @if ($cashExpense->status === 'draft')
                            Posting membuat jurnal dan mengunci transaksi.
                        @elseif ($cashExpense->status === 'posted')
                            Void membalik transaksi.
                        @else
                            Transaksi sudah dibatalkan.
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
                            <button class="ce-btn ce-btn-primary" type="submit">
                                <i class="bi bi-check2-circle ce-action-icon" aria-hidden="true"></i> Posting
                            </button>
                        </form>
                    @endif

                    @if ($cashExpense->status === 'posted')
                        <button class="ce-btn" type="button" data-bs-toggle="modal" data-bs-target="#cashExpenseReclassifyModal">
                            <i class="bi bi-arrow-repeat ce-action-icon" aria-hidden="true"></i> Kategori
                        </button>
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
                            <button class="ce-btn ce-btn-danger" type="submit">
                                <i class="bi bi-x-circle ce-action-icon" aria-hidden="true"></i> Void
                            </button>
                        </form>
                    @endif
                </div>
            </x-gf.panel>

            @if ($cashExpense->status === 'posted')
                <div class="modal fade" id="cashExpenseReclassifyModal" tabindex="-1"
                    aria-labelledby="cashExpenseReclassifyModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content border-0 shadow-lg">
                            <div class="modal-header border-0 pb-0">
                                <div>
                                    <div class="ce-modal-sub">Reklasifikasi</div>
                                    <h5 class="modal-title fw-black" id="cashExpenseReclassifyModalLabel">Ubah Kategori Pengeluaran</h5>
                                </div>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                            </div>
                            <form method="POST" action="{{ route('accounting.cash-expenses.reclassify', $cashExpense) }}"
                                class="ce-reclass-form" data-gf-confirm
                                data-gf-confirm-title="Reklasifikasi kategori?"
                                data-gf-confirm-text="Sistem akan membuat jurnal koreksi tanpa mengubah saldo kas."
                                data-gf-confirm-ok="Ya, reklasifikasi">
                                @csrf
                                <div class="modal-body">
                                    <div class="ce-reclass-help mb-3">
                                        Kategori saat ini: <strong>{{ $cashExpense->expenseAccount?->name ?? '-' }}</strong>.
                                        Jurnal awal tidak diubah; sistem akan membuat jurnal koreksi dan menyimpan riwayatnya.
                                    </div>
                                    <label class="form-label small fw-semibold mb-1" for="reclassify-category">Kategori baru</label>
                                    <select class="form-select form-control-lg ce-form-control" id="reclassify-category"
                                        name="to_expense_account_id" required>
                                        <option value="">Pilih kategori baru</option>
                                        @foreach ($expenseAccounts as $account)
                                            @if ((int) $account->id !== (int) $cashExpense->expense_account_id)
                                                <option value="{{ $account->id }}">
                                                    {{ $account->name }}{{ $account->code ? " · {$account->code}" : '' }}
                                                </option>
                                            @endif
                                        @endforeach
                                    </select>
                                    <label class="form-label small fw-semibold mb-1 mt-3" for="reclassify-reason">Alasan</label>
                                    <textarea class="form-control ce-form-control" id="reclassify-reason" name="reason"
                                        rows="3" maxlength="255" required
                                        placeholder="Contoh: Seharusnya masuk kategori transportasi"></textarea>
                                </div>
                                <div class="modal-footer border-0 pt-0">
                                    <button type="button" class="ce-btn" data-bs-dismiss="modal">Batal</button>
                                    <button type="submit" class="ce-btn ce-btn-primary">Simpan Perubahan</button>
                                </div>
                            </form>
                        </div>
                    </div>
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

            
            @php
                $proofUrl = $cashExpense->proof_photo_path
                    ? route('accounting.cash-expenses.proof', $cashExpense)
                    : null;
            @endphp

            <div class="ce-owner-layout">
                <div class="ce-proof-focus">
                    <div class="ce-proof-focus-head">
                        <div>
                            <p class="ce-proof-focus-title">Bukti Transaksi</p>
                            <div class="ce-proof-focus-sub">Struk atau bukti pembayaran.</div>
                        </div>

                        @if ($proofUrl)
                            <div class="ce-proof-focus-badge">Tersedia</div>
                        @else
                            <div class="ce-proof-focus-badge" style="background:#fffbeb;color:#b45309;">Tidak ada</div>
                        @endif
                    </div>

                    @if ($proofUrl)
                        <a href="{{ $proofUrl }}" target="_blank" rel="noopener">
                            <img class="ce-proof-focus-img"
                                src="{{ $proofUrl }}"
                                alt="Bukti transaksi pengeluaran">
                        </a>

                        <div class="ce-proof-focus-actions">
                            <a class="ce-btn ce-btn-primary" href="{{ $proofUrl }}" target="_blank" rel="noopener">
                                <i class="bi bi-box-arrow-up-right" aria-hidden="true"></i> Buka Bukti
                            </a>
                        </div>
                    @else
                        <div class="ce-proof-focus-empty">
                            Belum ada bukti foto untuk transaksi ini.
                        </div>
                    @endif
                </div>

                <div class="ce-owner-mini-card">
                    <div class="ce-owner-mini-title">Rincian Transaksi</div>

                    <div class="ce-owner-row">
                        <span>Nominal</span>
                        <span>Rp {{ $fmt($cashExpense->amount) }}</span>
                    </div>

                    <div class="ce-owner-row">
                        <span>Tanggal</span>
                        <span>{{ $cashExpense->date?->format('d/m/Y') ?? '-' }}</span>
                    </div>

                    <div class="ce-owner-row">
                        <span>Kategori</span>
                        <span>{{ $cashExpense->expenseAccount?->name ?? '-' }}</span>
                    </div>

                    <div class="ce-owner-row">
                        <span>Bayar dari</span>
                        <span>{{ $cashExpense->cashAccount?->name ?? '-' }}</span>
                    </div>

                    <div class="ce-owner-row">
                        <span>Keterangan</span>
                        <span>{{ $cashExpense->description ?: '-' }}</span>
                    </div>

                    <div class="ce-owner-row">
                        <span>No. Referensi</span>
                        <span>{{ $cashExpense->reference ?: '-' }}</span>
                    </div>

                    <div class="ce-owner-row">
                        <span>Status</span>
                        <span>{{ $statusLabel }}</span>
                    </div>
                </div>
            </div>

            @if ($cashExpense->journal)
                <x-gf.panel class="ce-journal-panel" title="Bukti Jurnal" subtitle="Jurnal otomatis saat posting.">
                    <div class="d-flex justify-content-between align-items-center gap-2 flex-wrap">
                        <div>
                            <div class="fw-bold text-dark">Jurnal #{{ $cashExpense->journal->id }}</div>
                            <div class="ce-meta">{{ $cashExpense->journal->description }}</div>
                        </div>
                        <a class="ce-btn" href="{{ route('accounting.journals.show', $cashExpense->journal) }}">Lihat Jurnal</a>
                    </div>
                </x-gf.panel>
            @endif

            @if ($cashExpense->reclassifications->isNotEmpty())
                <x-gf.panel class="ce-history-panel" title="Riwayat Reklasifikasi" subtitle="Perubahan kategori dan jurnal koreksi.">
                    @foreach ($cashExpense->reclassifications as $reclassification)
                        <div class="ce-history-row">
                            <div>
                                <div class="fw-bold text-dark">
                                    {{ $reclassification->fromExpenseAccount?->name ?? '-' }}
                                    → {{ $reclassification->toExpenseAccount?->name ?? '-' }}
                                </div>
                                <div class="ce-history-meta">
                                    {{ $reclassification->reason }}
                                    · {{ $reclassification->creator?->name ?? 'Sistem' }}
                                    · {{ $reclassification->created_at?->format('d/m/Y H:i') }}
                                </div>
                            </div>
                            <a class="ce-btn" href="{{ route('accounting.journals.show', $reclassification->journal) }}">Jurnal Koreksi</a>
                        </div>
                    @endforeach
                </x-gf.panel>
            @endif

        </div>
    </x-gf.page>
@endsection

{{-- AUTO SWEETALERT CASH EXPENSE SHOW --}}
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    function runSwal(callback) {
        if (window.Swal) {
            callback();
            return;
        }

        const script = document.createElement('script');
        script.src = 'https://cdn.jsdelivr.net/npm/sweetalert2@11';
        script.onload = callback;
        document.head.appendChild(script);
    }

    runSwal(function () {
        @if (session('message'))
            Swal.fire({
                icon: @json(session('status') === 'error' ? 'error' : 'success'),
                title: @json(session('status') === 'error' ? 'Gagal' : 'Berhasil'),
                text: @json(session('message')),
                confirmButtonText: 'OK'
            });
        @endif

        document.querySelectorAll('form[data-gf-confirm]').forEach(function (form) {
            form.addEventListener('submit', function (event) {
                event.preventDefault();

                const title = form.getAttribute('data-gf-confirm-title') || 'Lanjutkan?';
                const text = form.getAttribute('data-gf-confirm-text') || 'Pastikan data sudah benar.';
                const icon = form.getAttribute('data-gf-confirm-icon') || 'question';
                const ok = form.getAttribute('data-gf-confirm-ok') || 'Ya, lanjutkan';

                Swal.fire({
                    title: title,
                    text: text,
                    icon: icon,
                    showCancelButton: true,
                    confirmButtonText: ok,
                    cancelButtonText: 'Batal',
                    reverseButtons: true
                }).then(function (result) {
                    if (result.isConfirmed) {
                        form.removeAttribute('data-gf-confirm');
                        form.submit();
                    }
                });
            });
        });
    });
});
</script>
