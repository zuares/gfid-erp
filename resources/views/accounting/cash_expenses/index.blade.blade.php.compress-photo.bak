@extends('layouts.app')

@section('title', 'Accounting • Pengeluaran')

@php
    $fmt = fn($n) => number_format((float) $n, 0, ',', '.');
    $statusLabels = [
        'draft' => 'Draft',
        'posted' => 'Tercatat',
        'void' => 'Dibatalkan',
    ];
    $summary = $cashExpenseSummary ?? [
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
        .ce-page { display: grid; gap: 1rem; }
        .ce-header-actions { display: flex; justify-content: flex-end; gap: .5rem; flex-wrap: wrap; }
        .ce-btn {
            display: inline-flex; align-items: center; justify-content: center; gap: .45rem;
            min-height: 40px; padding: .55rem .95rem; border-radius: 999px;
            border: 1px solid rgba(15, 23, 42, .10); background: #fff;
            color: #0f172a; text-decoration: none; font-size: .84rem; font-weight: 800;
        }
        .ce-btn:hover { color: #0f172a; background: #f8fafc; }
        .ce-btn-primary { background: #0f172a; border-color: #0f172a; color: #fff; }
        .ce-btn-primary:hover { background: #1e293b; color: #fff; }
        .ce-kpi-grid {
            display: grid; grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: .75rem;
        }
        .ce-kpi {
            border: 1px solid rgba(15, 23, 42, .08); border-radius: 12px;
            background: #fff; padding: .85rem .95rem;
        }
        .ce-kpi-label {
            color: #64748b; font-size: .68rem; font-weight: 900;
            text-transform: uppercase; letter-spacing: .06em;
        }
        .ce-kpi-value { margin-top: .18rem; color: #0f172a; font-size: 1.25rem; font-weight: 950; line-height: 1.15; }
        .ce-kpi-note { margin-top: .2rem; color: #94a3b8; font-size: .74rem; }
        .ce-filter {
            display: grid;
            grid-template-columns: minmax(150px, .9fr) minmax(140px, .8fr) minmax(140px, .8fr) auto auto;
            gap: .55rem; align-items: end;
        }
        .ce-filter .form-control,
        .ce-filter .form-select {
            min-height: 40px; border-radius: 999px; border-color: rgba(15, 23, 42, .12);
            font-size: .84rem; font-weight: 700; box-shadow: none;
        }
        .ce-filter-actions { display: flex; gap: .45rem; }
        .ce-table-wrap { max-height: calc(100vh - 340px); overflow: auto; -webkit-overflow-scrolling: touch; }
        .ce-table th, .ce-table td { vertical-align: middle; }
        .ce-table thead th { white-space: nowrap; }
        .ce-click-row { cursor: pointer; }
        .ce-click-row:hover td { background: #f8fafc; }
        .ce-date { display: flex; flex-direction: column; line-height: 1.15; }
        .ce-date-main { color: #0f172a; font-weight: 800; font-variant-numeric: tabular-nums; }
        .ce-date-sub { color: #94a3b8; font-size: .72rem; }
        .ce-main { min-width: 220px; }
        .ce-desc { color: #0f172a; font-weight: 850; text-decoration: none; }
        .ce-desc:hover { color: #1d4ed8; }
        .ce-meta { margin-top: .12rem; color: #64748b; font-size: .78rem; }
        .ce-account { color: #334155; font-weight: 760; }
        .ce-account-code { color: #94a3b8; font-size: .76rem; font-variant-numeric: tabular-nums; }
        .ce-num { text-align: right; font-variant-numeric: tabular-nums; font-weight: 900; color: #0f172a; }
        .ce-status {
            display: inline-flex; align-items: center; gap: .35rem; border-radius: 999px;
            padding: .22rem .6rem; font-size: .74rem; font-weight: 850;
            border: 1px solid transparent; white-space: nowrap;
        }
        .ce-status::before { content: ''; width: 7px; height: 7px; border-radius: 999px; background: currentColor; }
        .ce-status-draft { color: #b45309; background: #fef3c7; border-color: #fde68a; }
        .ce-status-posted { color: #166534; background: #dcfce7; border-color: #bbf7d0; }
        .ce-status-void { color: #b91c1c; background: #fee2e2; border-color: #fecaca; }
        .ce-empty { text-align: center; color: #64748b; padding: 2.4rem 1rem; }
        .ce-mobile-list { display: none; }
        .ce-form-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .8rem; }
        .ce-field { display: grid; gap: .32rem; margin: 0; }
        .ce-field-full { grid-column: 1 / -1; }
        .ce-field > span {
            color: #64748b; font-size: .75rem; font-weight: 900;
            text-transform: uppercase; letter-spacing: .04em;
        }
        .ce-field small { color: #94a3b8; font-weight: 800; text-transform: none; letter-spacing: 0; }
        .ce-form-control {
            min-height: 42px; border-radius: 12px; border-color: rgba(15, 23, 42, .12);
            box-shadow: none; font-size: .88rem;
        }
        .ce-form-error {
            padding: .75rem .85rem; border-radius: 12px;
            border: 1px solid rgba(239, 68, 68, .24);
            background: rgba(239, 68, 68, .08); color: #991b1b; font-size: .86rem;
        }
        .ce-form-error ul { margin: .35rem 0 0; padding-left: 1.1rem; }
        .ce-modal .modal-content { border: 0; border-radius: 16px; overflow: hidden; }
        .ce-modal .modal-header { border-bottom: 1px solid #eef2f7; }
        .ce-modal .modal-footer { border-top: 1px solid #eef2f7; }
        .ce-modal-title { font-weight: 900; color: #0f172a; }
        .ce-modal-sub { color: #64748b; font-size: .82rem; margin-top: .1rem; }
        @media (max-width: 768px) {
            .gf-master-header { padding: 12px 14px; border-radius: 14px; }
            .gf-master-title { font-size: 18px; }
            .gf-master-desc { font-size: 11.5px; }
            .gf-master-actions { flex: 1 1 100%; }
            .ce-header-actions { justify-content: stretch; }
            .ce-header-actions .ce-btn { flex: 1 1 auto; }
            .ce-kpi-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .55rem; }
            .ce-kpi { padding: .7rem .75rem; }
            .ce-kpi-value { font-size: 1.05rem; }
            .ce-filter { grid-template-columns: 1fr 1fr; }
            .ce-filter .form-select { grid-column: 1 / -1; }
            .ce-filter-actions { grid-column: 1 / -1; }
            .ce-filter-actions .ce-btn { flex: 1 1 0; }
            .ce-table-wrap { display: none; }
            .ce-mobile-list { display: grid; gap: .62rem; }
            .ce-mobile-card {
                display: grid; gap: .5rem; padding: .8rem;
                border: 1px solid rgba(15, 23, 42, .08); border-radius: 10px; background: #fff;
                box-shadow: 0 6px 18px rgba(15, 23, 42, .04);
                cursor: pointer;
            }
            .ce-mobile-card:active { background: #f8fafc; }
            .ce-mobile-top { display: flex; justify-content: space-between; align-items: flex-start; gap: .7rem; }
            .ce-mobile-title { color: #0f172a; font-weight: 900; text-decoration: none; line-height: 1.25; }
            .ce-mobile-amount { color: #0f172a; font-weight: 950; font-variant-numeric: tabular-nums; white-space: nowrap; }
            .ce-mobile-meta {
                display: grid; grid-template-columns: 1fr 1fr; gap: .45rem .65rem;
                color: #64748b; font-size: .76rem;
            }
            .ce-mobile-meta b { display: block; color: #334155; font-size: .8rem; }
            .ce-form-grid { grid-template-columns: 1fr; }
            body.modal-open .mobile-bottom-nav { display: none; }
            .ce-modal .modal-dialog,
            .ce-category-modal .modal-dialog {
                width: calc(100% - 1.5rem);
                max-width: 480px;
                margin: .75rem auto calc(.75rem + env(safe-area-inset-bottom));
            }
            .ce-modal .modal-content,
            .ce-category-modal .modal-content {
                max-height: calc(var(--app-vh, 100vh) - 1.5rem - env(safe-area-inset-bottom));
                border-radius: 16px;
            }
            .ce-modal .modal-body,
            .ce-category-modal .modal-body {
                overflow-y: auto;
                -webkit-overflow-scrolling: touch;
                padding: .9rem;
            }
            .ce-modal .modal-header,
            .ce-modal .modal-footer,
            .ce-category-modal .modal-header,
            .ce-category-modal .modal-footer {
                padding: .8rem .9rem;
            }
            .ce-modal .modal-footer,
            .ce-category-modal .modal-footer {
                position: sticky;
                bottom: 0;
                background: #fff;
                z-index: 2;
                gap: .5rem;
            }
            .ce-modal .modal-footer .ce-btn,
            .ce-category-modal .modal-footer .ce-btn {
                flex: 1 1 0;
            }
        }
    </style>
@endpush

@section('content')
    <x-gf.page
        eyebrow="Accounting"
        title="Pengeluaran Kas"
        description="Catat dan pantau pengeluaran harian dengan alur draft, posting, dan void.">
        <x-slot:actions>
            <div class="ce-header-actions">
                <button class="ce-btn ce-btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#cashExpenseCreateModal">
                    + Tambah Pengeluaran
                </button>
            </div>
        </x-slot:actions>

        <div class="ce-page">
            <div class="ce-kpi-grid">
                <div class="ce-kpi">
                    <div class="ce-kpi-label">Total Pengeluaran</div>
                    <div class="ce-kpi-value">Rp {{ $fmt($summary['total_amount']) }}</div>
                    <div class="ce-kpi-note">{{ $fmt($summary['total_docs']) }} dokumen sesuai filter</div>
                </div>
                <div class="ce-kpi">
                    <div class="ce-kpi-label">Sudah Tercatat</div>
                    <div class="ce-kpi-value">Rp {{ $fmt($summary['posted_amount']) }}</div>
                    <div class="ce-kpi-note">masuk jurnal</div>
                </div>
                <div class="ce-kpi">
                    <div class="ce-kpi-label">Draft</div>
                    <div class="ce-kpi-value">{{ $fmt($summary['draft_docs']) }}</div>
                    <div class="ce-kpi-note">menunggu posting</div>
                </div>
                <div class="ce-kpi">
                    <div class="ce-kpi-label">Dibatalkan</div>
                    <div class="ce-kpi-value">{{ $fmt($summary['void_docs']) }}</div>
                    <div class="ce-kpi-note">void/reversal</div>
                </div>
            </div>

            <x-gf.panel title="Daftar Pengeluaran" subtitle="Gunakan filter untuk melihat status dan periode tertentu.">
                <form class="ce-filter mb-3" method="GET" action="{{ route('accounting.cash-expenses.index') }}">
                    @php $st = request('status'); @endphp
                    <select class="form-select" name="status" aria-label="Status">
                        <option value="">Semua status</option>
                        <option value="draft" @selected($st === 'draft')>Draft</option>
                        <option value="posted" @selected($st === 'posted')>Tercatat</option>
                        <option value="void" @selected($st === 'void')>Dibatalkan</option>
                    </select>

                    <input class="form-control" type="text" name="from" value="{{ request('from') }}" data-ce-date aria-label="Dari tanggal" autocomplete="off" placeholder="Dari tanggal">
                    <input class="form-control" type="text" name="to" value="{{ request('to') }}" data-ce-date aria-label="Sampai tanggal" autocomplete="off" placeholder="Sampai tanggal">

                    <div class="ce-filter-actions">
                        <button class="ce-btn" type="submit">Filter</button>
                        <a class="ce-btn" href="{{ route('accounting.cash-expenses.index') }}">Reset</a>
                    </div>
                </form>

                @if (session('message'))
                    <div class="gf-mpl-insight">
                        <span class="gf-mpl-insight-ico">✓</span>
                        <b>{{ session('message') }}</b>
                    </div>
                @endif

                @if ($cashExpenses->isEmpty())
                    <div class="ce-empty">Belum ada data pengeluaran.</div>
                @else
                    <div class="ce-table-wrap">
                        <table class="table table-hover align-middle mb-0 gf-clean-table gf-sticky-table ce-table">
                            <thead>
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Ringkasan</th>
                                    <th>Kategori</th>
                                    <th>Bayar Dari</th>
                                    <th>Status</th>
                                    <th class="ce-num">Nominal</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($cashExpenses as $x)
                                    @php
                                        $status = $x->status;
                                        $label = $statusLabels[$status] ?? ucfirst((string) $status);
                                        $date = \Illuminate\Support\Carbon::parse($x->date);
                                    @endphp
                                    <tr class="ce-click-row" data-href="{{ route('accounting.cash-expenses.show', $x) }}" tabindex="0" aria-label="Buka detail pengeluaran {{ $x->id }}">
                                        <td>
                                            <div class="ce-date">
                                                <span class="ce-date-main">{{ $date->format('d/m/Y') }}</span>
                                                <span class="ce-date-sub">{{ $date->translatedFormat('D') }}</span>
                                            </div>
                                        </td>
                                        <td class="ce-main">
                                            <a class="ce-desc" href="{{ route('accounting.cash-expenses.show', $x) }}">
                                                {{ $x->description ?: 'Pengeluaran' }}
                                            </a>
                                            <div class="ce-meta">
                                                {{ $x->reference ? 'Ref: ' . $x->reference . ' · ' : '' }}ID #{{ $x->id }}
                                            </div>
                                        </td>
                                        <td>
                                            <div class="ce-account">{{ $x->expenseAccount?->name ?? '-' }}</div>
                                            <div class="ce-account-code">{{ $x->expenseAccount?->code ?? '' }}</div>
                                        </td>
                                        <td>
                                            <div class="ce-account">{{ $x->cashAccount?->name ?? '-' }}</div>
                                            <div class="ce-account-code">{{ $x->cashAccount?->code ?? '' }}</div>
                                        </td>
                                        <td>
                                            <span class="ce-status ce-status-{{ $status }}">{{ $label }}</span>
                                        </td>
                                        <td class="ce-num">Rp {{ $fmt($x->amount) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="ce-mobile-list">
                        @foreach ($cashExpenses as $x)
                            @php
                                $status = $x->status;
                                $label = $statusLabels[$status] ?? ucfirst((string) $status);
                                $date = \Illuminate\Support\Carbon::parse($x->date);
                            @endphp
                            <div class="ce-mobile-card" data-href="{{ route('accounting.cash-expenses.show', $x) }}" tabindex="0" role="link" aria-label="Buka detail pengeluaran {{ $x->id }}">
                                <div class="ce-mobile-top">
                                    <div>
                                        <a class="ce-mobile-title" href="{{ route('accounting.cash-expenses.show', $x) }}">
                                            {{ $x->description ?: 'Pengeluaran' }}
                                        </a>
                                        <div class="ce-meta">{{ $date->format('d/m/Y') }} · ID #{{ $x->id }}</div>
                                    </div>
                                    <div class="ce-mobile-amount">Rp {{ $fmt($x->amount) }}</div>
                                </div>
                                <div class="d-flex justify-content-between align-items-center gap-2">
                                    <span class="ce-status ce-status-{{ $status }}">{{ $label }}</span>
                                    @if ($x->reference)
                                        <span class="ce-meta">Ref: {{ $x->reference }}</span>
                                    @endif
                                </div>
                                <div class="ce-mobile-meta">
                                    <div><span>Kategori</span><b>{{ $x->expenseAccount?->name ?? '-' }}</b></div>
                                    <div><span>Bayar dari</span><b>{{ $x->cashAccount?->name ?? '-' }}</b></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif

                <div class="mt-3">
                    {{ $cashExpenses->links() }}
                </div>
            </x-gf.panel>
        </div>
    </x-gf.page>

    <div class="modal fade ce-modal" id="cashExpenseCreateModal" tabindex="-1" aria-labelledby="cashExpenseCreateModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <form class="modal-content" method="POST" action="{{ route('accounting.cash-expenses.store') }}" enctype="multipart/form-data"
                data-gf-confirm
                data-gf-confirm-title="Simpan sebagai Draft?"
                data-gf-confirm-text="Pengeluaran akan tersimpan sebagai draft dan bisa diposting setelah dicek."
                data-gf-confirm-ok="Ya, simpan">
                @csrf
                <div class="modal-header">
                    <div>
                        <h2 class="modal-title fs-5 ce-modal-title" id="cashExpenseCreateModalLabel">Tambah Pengeluaran</h2>
                        <div class="ce-modal-sub">Simpan sebagai Draft, lalu posting setelah dicek.</div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    @include('accounting.cash_expenses._form')
                </div>
                <div class="modal-footer">
                    <button type="button" class="ce-btn" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="ce-btn ce-btn-primary">Simpan Draft</button>
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
                const modalEl = document.getElementById('cashExpenseCreateModal');
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
