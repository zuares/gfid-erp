@extends('layouts.app')

@section('title', 'Accounting • Penerimaan Kas')

@php
    $fmt = fn($n) => number_format((float) $n, 0, ',', '.');
    $statusLabels = [
        'draft' => 'Draft',
        'posted' => 'Tercatat',
        'void' => 'Dibatalkan',
    ];
    $summary = $cashReceiptSummary ?? [
        'total_docs' => 0,
        'total_amount' => 0,
        'posted_amount' => 0,
        'draft_docs' => 0,
        'void_docs' => 0,
    ];
@endphp

@push('head')
    @include('production.dashboard.partials._gf-styles')
    <style>
        .cr-page { display: grid; gap: 1rem; }
        .cr-header-actions { display: flex; justify-content: flex-end; gap: .5rem; flex-wrap: wrap; }
        .cr-btn {
            display: inline-flex; align-items: center; justify-content: center; gap: .45rem;
            min-height: 40px; padding: .55rem .95rem; border-radius: 999px;
            border: 1px solid rgba(15, 23, 42, .10); background: #fff;
            color: #0f172a; text-decoration: none; font-size: .84rem; font-weight: 850;
        }
        .cr-btn:hover { color: #0f172a; background: #f8fafc; }
        .cr-btn-primary { background: #0f172a; border-color: #0f172a; color: #fff; }
        .cr-btn-primary:hover { background: #1e293b; color: #fff; }
        .cr-kpi-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: .75rem; }
        .cr-kpi {
            border: 1px solid rgba(15, 23, 42, .08); border-radius: 12px;
            background: #fff; padding: .85rem .95rem;
        }
        .cr-kpi-label {
            color: #64748b; font-size: .68rem; font-weight: 900;
            text-transform: uppercase; letter-spacing: .06em;
        }
        .cr-kpi-value { margin-top: .18rem; color: #0f172a; font-size: 1.25rem; font-weight: 950; line-height: 1.15; }
        .cr-kpi-note { margin-top: .2rem; color: #94a3b8; font-size: .74rem; }
        .cr-filter {
            display: grid;
            grid-template-columns: minmax(150px, .9fr) minmax(140px, .8fr) minmax(140px, .8fr) auto;
            gap: .55rem; align-items: end;
        }
        .cr-filter .form-control,
        .cr-filter .form-select {
            min-height: 40px; border-radius: 999px; border-color: rgba(15, 23, 42, .12);
            font-size: .84rem; font-weight: 700; box-shadow: none;
        }
        .cr-filter-actions { display: flex; gap: .45rem; }
        .cr-table-wrap { max-height: calc(100vh - 340px); overflow: auto; -webkit-overflow-scrolling: touch; }
        .cr-table th, .cr-table td { vertical-align: middle; }
        .cr-click-row { cursor: pointer; }
        .cr-click-row:hover td { background: #f8fafc; }
        .cr-date { display: flex; flex-direction: column; line-height: 1.15; }
        .cr-date-main { color: #0f172a; font-weight: 800; font-variant-numeric: tabular-nums; }
        .cr-date-sub { color: #94a3b8; font-size: .72rem; }
        .cr-desc { color: #0f172a; font-weight: 850; text-decoration: none; }
        .cr-meta { margin-top: .12rem; color: #64748b; font-size: .78rem; }
        .cr-account { color: #334155; font-weight: 760; }
        .cr-account-code { color: #94a3b8; font-size: .76rem; font-variant-numeric: tabular-nums; }
        .cr-num { text-align: right; font-variant-numeric: tabular-nums; font-weight: 900; color: #0f172a; }
        .cr-status {
            display: inline-flex; align-items: center; gap: .35rem; border-radius: 999px;
            padding: .22rem .6rem; font-size: .74rem; font-weight: 850;
            border: 1px solid transparent; white-space: nowrap;
        }
        .cr-status::before { content: ''; width: 7px; height: 7px; border-radius: 999px; background: currentColor; }
        .cr-status-draft { color: #b45309; background: #fef3c7; border-color: #fde68a; }
        .cr-status-posted { color: #166534; background: #dcfce7; border-color: #bbf7d0; }
        .cr-status-void { color: #b91c1c; background: #fee2e2; border-color: #fecaca; }
        .cr-empty { text-align: center; color: #64748b; padding: 2.4rem 1rem; }
        .cr-mobile-list { display: none; }
        .cr-form-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .8rem; }
        .cr-field { display: grid; gap: .32rem; margin: 0; }
        .cr-field-full { grid-column: 1 / -1; }
        .cr-field > span {
            color: #64748b; font-size: .75rem; font-weight: 900;
            text-transform: uppercase; letter-spacing: .04em;
        }
        .cr-field small { color: #94a3b8; font-weight: 800; text-transform: none; letter-spacing: 0; }
        .cr-form-control {
            min-height: 42px; border-radius: 12px; border-color: rgba(15, 23, 42, .12);
            box-shadow: none; font-size: .88rem;
        }
        .cr-form-error {
            padding: .75rem .85rem; border-radius: 12px;
            border: 1px solid rgba(239, 68, 68, .24);
            background: rgba(239, 68, 68, .08); color: #991b1b; font-size: .86rem;
        }
        .cr-form-error ul { margin: .35rem 0 0; padding-left: 1.1rem; }
        .cr-modal .modal-content { border: 0; border-radius: 16px; overflow: hidden; }
        .cr-modal .modal-header { border-bottom: 1px solid #eef2f7; }
        .cr-modal .modal-footer { border-top: 1px solid #eef2f7; }
        .cr-modal-title { font-weight: 900; color: #0f172a; }
        .cr-modal-sub { color: #64748b; font-size: .82rem; margin-top: .1rem; }
        @media (max-width: 768px) {
            .gf-master-header { padding: 12px 14px; border-radius: 14px; }
            .gf-master-title { font-size: 18px; }
            .gf-master-desc { font-size: 11.5px; }
            .gf-master-actions { flex: 1 1 100%; }
            .cr-header-actions { justify-content: stretch; }
            .cr-header-actions .cr-btn { flex: 1 1 auto; }
            .cr-kpi-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .55rem; }
            .cr-kpi { padding: .7rem .75rem; }
            .cr-kpi-value { font-size: 1.05rem; }
            .cr-filter { grid-template-columns: 1fr 1fr; }
            .cr-filter .form-select { grid-column: 1 / -1; }
            .cr-filter-actions { grid-column: 1 / -1; }
            .cr-filter-actions .cr-btn { flex: 1 1 0; }
            .cr-table-wrap { display: none; }
            .cr-mobile-list { display: grid; gap: .62rem; }
            .cr-mobile-card {
                display: grid; gap: .5rem; padding: .8rem;
                border: 1px solid rgba(15, 23, 42, .08); border-radius: 10px; background: #fff;
                box-shadow: 0 6px 18px rgba(15, 23, 42, .04); cursor: pointer;
            }
            .cr-mobile-top { display: flex; justify-content: space-between; align-items: flex-start; gap: .7rem; }
            .cr-mobile-title { color: #0f172a; font-weight: 900; text-decoration: none; line-height: 1.25; }
            .cr-mobile-amount { color: #0f172a; font-weight: 950; font-variant-numeric: tabular-nums; white-space: nowrap; }
            .cr-mobile-meta { display: grid; grid-template-columns: 1fr 1fr; gap: .45rem .65rem; color: #64748b; font-size: .76rem; }
            .cr-mobile-meta b { display: block; color: #334155; font-size: .8rem; }
            .cr-form-grid { grid-template-columns: 1fr; }
        }
    </style>
@endpush

@section('content')
    <x-gf.page
        eyebrow="Accounting"
        title="Penerimaan Kas"
        description="Catat uang masuk dengan alur draft, posting, dan void.">
        <x-slot:actions>
            <div class="cr-header-actions">
                <button class="cr-btn cr-btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#cashReceiptCreateModal">
                    + Tambah Penerimaan
                </button>
            </div>
        </x-slot:actions>

        <div class="cr-page">
            <div class="cr-kpi-grid">
                <div class="cr-kpi">
                    <div class="cr-kpi-label">Total Penerimaan</div>
                    <div class="cr-kpi-value">Rp {{ $fmt($summary['total_amount']) }}</div>
                    <div class="cr-kpi-note">{{ $fmt($summary['total_docs']) }} dokumen sesuai filter</div>
                </div>
                <div class="cr-kpi">
                    <div class="cr-kpi-label">Sudah Tercatat</div>
                    <div class="cr-kpi-value">Rp {{ $fmt($summary['posted_amount']) }}</div>
                    <div class="cr-kpi-note">masuk jurnal</div>
                </div>
                <div class="cr-kpi">
                    <div class="cr-kpi-label">Draft</div>
                    <div class="cr-kpi-value">{{ $fmt($summary['draft_docs']) }}</div>
                    <div class="cr-kpi-note">menunggu posting</div>
                </div>
                <div class="cr-kpi">
                    <div class="cr-kpi-label">Dibatalkan</div>
                    <div class="cr-kpi-value">{{ $fmt($summary['void_docs']) }}</div>
                    <div class="cr-kpi-note">void/reversal</div>
                </div>
            </div>

            <x-gf.panel title="Daftar Penerimaan" subtitle="Klik baris untuk buka detail dan posting jika masih draft.">
                <form class="cr-filter mb-3" method="GET" action="{{ route('accounting.cash-receipts.index') }}">
                    @php $st = request('status'); @endphp
                    <select class="form-select" name="status" aria-label="Status">
                        <option value="">Semua status</option>
                        <option value="draft" @selected($st === 'draft')>Draft</option>
                        <option value="posted" @selected($st === 'posted')>Tercatat</option>
                        <option value="void" @selected($st === 'void')>Dibatalkan</option>
                    </select>

                    <input class="form-control" type="text" name="from" value="{{ request('from') }}" data-gf-date aria-label="Dari tanggal" autocomplete="off" placeholder="Dari tanggal">
                    <input class="form-control" type="text" name="to" value="{{ request('to') }}" data-gf-date aria-label="Sampai tanggal" autocomplete="off" placeholder="Sampai tanggal">

                    <div class="cr-filter-actions">
                        <button class="cr-btn" type="submit">Filter</button>
                        <a class="cr-btn" href="{{ route('accounting.cash-receipts.index') }}">Reset</a>
                    </div>
                </form>

                @if (session('message'))
                    <div class="gf-mpl-insight">
                        <span class="gf-mpl-insight-ico">✓</span>
                        <b>{{ session('message') }}</b>
                    </div>
                @endif

                @if ($cashReceipts->isEmpty())
                    <div class="cr-empty">Belum ada data penerimaan.</div>
                @else
                    <div class="cr-table-wrap">
                        <table class="table table-hover align-middle mb-0 gf-clean-table gf-sticky-table cr-table">
                            <thead>
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Ringkasan</th>
                                    <th>Sumber</th>
                                    <th>Terima Ke</th>
                                    <th>Status</th>
                                    <th class="cr-num">Nominal</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($cashReceipts as $receipt)
                                    @php
                                        $status = $receipt->status;
                                        $label = $statusLabels[$status] ?? ucfirst((string) $status);
                                        $date = \Illuminate\Support\Carbon::parse($receipt->date);
                                    @endphp
                                    <tr class="cr-click-row" data-href="{{ route('accounting.cash-receipts.show', $receipt) }}" tabindex="0">
                                        <td>
                                            <div class="cr-date">
                                                <span class="cr-date-main">{{ $date->format('d/m/Y') }}</span>
                                                <span class="cr-date-sub">{{ $date->translatedFormat('D') }}</span>
                                            </div>
                                        </td>
                                        <td>
                                            <a class="cr-desc" href="{{ route('accounting.cash-receipts.show', $receipt) }}">
                                                {{ $receipt->description ?: 'Penerimaan' }}
                                            </a>
                                            <div class="cr-meta">
                                                {{ $receipt->reference ? 'Ref: ' . $receipt->reference . ' · ' : '' }}ID #{{ $receipt->id }}
                                            </div>
                                        </td>
                                        <td>
                                            <div class="cr-account">{{ $receipt->sourceAccount?->name ?? '-' }}</div>
                                            <div class="cr-account-code">{{ $receipt->sourceAccount?->code ?? '' }}</div>
                                        </td>
                                        <td>
                                            <div class="cr-account">{{ $receipt->cashAccount?->name ?? '-' }}</div>
                                            <div class="cr-account-code">{{ $receipt->cashAccount?->code ?? '' }}</div>
                                        </td>
                                        <td>
                                            <span class="cr-status cr-status-{{ $status }}">{{ $label }}</span>
                                        </td>
                                        <td class="cr-num">Rp {{ $fmt($receipt->amount) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="cr-mobile-list">
                        @foreach ($cashReceipts as $receipt)
                            @php
                                $status = $receipt->status;
                                $label = $statusLabels[$status] ?? ucfirst((string) $status);
                                $date = \Illuminate\Support\Carbon::parse($receipt->date);
                            @endphp
                            <div class="cr-mobile-card" data-href="{{ route('accounting.cash-receipts.show', $receipt) }}" tabindex="0" role="link">
                                <div class="cr-mobile-top">
                                    <div>
                                        <a class="cr-mobile-title" href="{{ route('accounting.cash-receipts.show', $receipt) }}">
                                            {{ $receipt->description ?: 'Penerimaan' }}
                                        </a>
                                        <div class="cr-meta">{{ $date->format('d/m/Y') }} · ID #{{ $receipt->id }}</div>
                                    </div>
                                    <div class="cr-mobile-amount">Rp {{ $fmt($receipt->amount) }}</div>
                                </div>
                                <div class="d-flex justify-content-between align-items-center gap-2">
                                    <span class="cr-status cr-status-{{ $status }}">{{ $label }}</span>
                                    @if ($receipt->reference)
                                        <span class="cr-meta">Ref: {{ $receipt->reference }}</span>
                                    @endif
                                </div>
                                <div class="cr-mobile-meta">
                                    <div><span>Sumber</span><b>{{ $receipt->sourceAccount?->name ?? '-' }}</b></div>
                                    <div><span>Terima ke</span><b>{{ $receipt->cashAccount?->name ?? '-' }}</b></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif

                <div class="mt-3">
                    {{ $cashReceipts->links() }}
                </div>
            </x-gf.panel>
        </div>
    </x-gf.page>

    <div class="modal fade cr-modal" id="cashReceiptCreateModal" tabindex="-1" aria-labelledby="cashReceiptCreateModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <form class="modal-content" method="POST" action="{{ route('accounting.cash-receipts.store') }}"
                data-gf-confirm
                data-gf-confirm-title="Simpan sebagai Draft?"
                data-gf-confirm-text="Penerimaan akan tersimpan sebagai draft dan bisa diposting setelah dicek."
                data-gf-confirm-ok="Ya, simpan">
                @csrf
                <div class="modal-header">
                    <div>
                        <h2 class="modal-title fs-5 cr-modal-title" id="cashReceiptCreateModalLabel">Tambah Penerimaan</h2>
                        <div class="cr-modal-sub">Simpan sebagai Draft, lalu posting setelah dicek.</div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    @include('accounting.cash_receipts._form')
                </div>
                <div class="modal-footer">
                    <button type="button" class="cr-btn" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="cr-btn cr-btn-primary">Simpan Draft</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('[data-href]').forEach((el) => {
                el.addEventListener('click', (event) => {
                    if (event.target.closest('a,button,input,select,textarea,form')) return;
                    window.location.href = el.dataset.href;
                });
                el.addEventListener('keydown', (event) => {
                    if (!['Enter', ' '].includes(event.key)) return;
                    event.preventDefault();
                    window.location.href = el.dataset.href;
                });
            });

            @if ($errors->any())
                const modalEl = document.getElementById('cashReceiptCreateModal');
                if (modalEl && window.bootstrap) {
                    window.bootstrap.Modal.getOrCreateInstance(modalEl).show();
                }
            @endif

            @if (session('message'))
                if (window.GFID && typeof window.GFID.toast === 'function') {
                    window.GFID.toast(@json(session('message')), {
                        icon: @json(session('status') === 'error' ? 'error' : 'success'),
                    });
                }
            @endif
        });
    </script>
@endpush

{{-- AUTO SWEETALERT CASH RECEIPT PAGE --}}
<style>
@media (max-width: 768px) {
    .cr-actions .cr-btn,
    .cr-actions form,
    .cr-actions form .cr-btn,
    .cr-btn {
        width: 100%;
    }

    .d-flex.justify-content-end.gap-2.flex-wrap.mt-3 {
        display: grid !important;
        width: 100%;
    }

    .d-flex.justify-content-end.gap-2.flex-wrap.mt-3 .cr-btn {
        width: 100%;
    }
}
</style>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    function ready(callback) {
        if (window.Swal) {
            callback();
            return;
        }

        const script = document.createElement('script');
        script.src = 'https://cdn.jsdelivr.net/npm/sweetalert2@11';
        script.onload = callback;
        document.head.appendChild(script);
    }

    ready(function () {
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

