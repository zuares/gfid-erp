@extends('layouts.app')

@section('title', 'Accounting • Penerimaan Marketplace')

@php
    $fmt = fn($n) => number_format((float) $n, 0, ',', '.');
    $statusLabels = ['draft' => 'Draft', 'posted' => 'Tercatat', 'void' => 'Dibatalkan'];
    $dayLabels = ['Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa', 'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu'];
@endphp

@push('head')
    @include('production.dashboard.partials._gf-styles')
    <style>
        .mp-page { display: grid; gap: 1rem; max-width: 1480px; }
        .mp-hero {
            display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem;
            padding: 1.25rem 1.35rem; border: 1px solid #dbe4ee; border-radius: 16px;
            background: linear-gradient(135deg, #ffffff 0%, #f8fbff 100%);
            box-shadow: 0 8px 24px rgba(15,23,42,.04);
        }
        .mp-eyebrow { color: #2563eb; font-size: .66rem; font-weight: 950; letter-spacing: .12em; text-transform: uppercase; }
        .mp-hero-title { margin: .22rem 0 .25rem; color: #0f172a; font-size: 1.35rem; font-weight: 950; letter-spacing: -.025em; }
        .mp-hero-subtitle { color: #64748b; font-size: .82rem; }
        .mp-hero-meta { display: inline-flex; align-items: center; gap: .4rem; margin-top: .7rem; color: #475569; font-size: .72rem; font-weight: 800; }
        .mp-hero-meta i { color: #16a34a; font-size: .55rem; }
        .mp-actions { display: flex; align-items: center; justify-content: flex-end; gap: .5rem; flex-wrap: wrap; }
        .mp-btn {
            display: inline-flex; align-items: center; justify-content: center; gap: .45rem;
            min-height: 40px; padding: .58rem .9rem; border-radius: 10px;
            border: 1px solid #dbe4ee; background: #fff; color: #0f172a;
            text-decoration: none; font-size: .78rem; font-weight: 850; transition: .15s ease;
        }
        .mp-btn:hover { border-color: #b8c7d9; background: #f8fafc; color: #0f172a; transform: translateY(-1px); }
        .mp-btn-primary { background: #1d4ed8; border-color: #1d4ed8; color: #fff; box-shadow: 0 5px 12px rgba(29,78,216,.16); }
        .mp-btn-primary:hover { background: #1e40af; border-color: #1e40af; color: #fff; }
        .mp-btn-icon { width: 16px; height: 16px; }
        .mp-alert { display: flex; align-items: flex-start; gap: .65rem; border: 1px solid; border-radius: 12px; padding: .75rem .9rem; font-size: .78rem; }
        .mp-alert-success { color: #166534; background: #f0fdf4; border-color: #bbf7d0; }
        .mp-alert-danger { color: #991b1b; background: #fef2f2; border-color: #fecaca; }
        .mp-alert i { margin-top: .1rem; font-size: 1rem; }
        .mp-kpi-grid { display: grid; grid-template-columns: repeat(4, minmax(0,1fr)); gap: .75rem; }
        .mp-kpi { position: relative; overflow: hidden; border: 1px solid #dbe4ee; border-radius: 14px; background: #fff; padding: 1rem; box-shadow: 0 5px 16px rgba(15,23,42,.035); }
        .mp-kpi::after { position: absolute; right: -24px; bottom: -34px; width: 90px; height: 90px; border-radius: 50%; background: var(--mp-kpi-tint); content: ''; }
        .mp-kpi-head { position: relative; z-index: 1; display: flex; align-items: center; justify-content: space-between; gap: .5rem; }
        .mp-kpi-label { color: #64748b; font-size: .66rem; font-weight: 950; letter-spacing: .08em; text-transform: uppercase; }
        .mp-kpi-icon { display: inline-flex; align-items: center; justify-content: center; width: 29px; height: 29px; border-radius: 9px; background: var(--mp-kpi-bg); color: var(--mp-kpi-color); font-size: .9rem; }
        .mp-kpi-value { position: relative; z-index: 1; margin-top: .5rem; color: #0f172a; font-size: 1.35rem; font-weight: 950; line-height: 1.1; letter-spacing: -.02em; }
        .mp-kpi-note { position: relative; z-index: 1; margin-top: .35rem; color: #94a3b8; font-size: .72rem; }
        .mp-kpi-blue { --mp-kpi-bg:#dbeafe; --mp-kpi-color:#1d4ed8; --mp-kpi-tint:#eff6ff; }
        .mp-kpi-green { --mp-kpi-bg:#dcfce7; --mp-kpi-color:#15803d; --mp-kpi-tint:#f0fdf4; }
        .mp-kpi-amber { --mp-kpi-bg:#fef3c7; --mp-kpi-color:#b45309; --mp-kpi-tint:#fffbeb; }
        .mp-kpi-slate { --mp-kpi-bg:#e2e8f0; --mp-kpi-color:#475569; --mp-kpi-tint:#f8fafc; }
        .mp-panel { border: 1px solid #dbe4ee; border-radius: 14px; background: #fff; box-shadow: 0 5px 16px rgba(15,23,42,.035); }
        .mp-panel-head { display: flex; align-items: center; justify-content: space-between; gap: .75rem; padding: .9rem 1rem; border-bottom: 1px solid #edf2f7; }
        .mp-panel-title { color: #0f172a; font-size: .84rem; font-weight: 900; }
        .mp-panel-subtitle { color: #94a3b8; font-size: .72rem; }
        .mp-filter { display: grid; grid-template-columns: repeat(4, minmax(0,1fr)) auto; gap: .75rem; align-items: end; padding: .9rem 1rem 1rem; }
        .mp-filter label, .mp-modal-label { display: block; margin-bottom: .35rem; color: #475569; font-size: .69rem; font-weight: 900; letter-spacing: .03em; }
        .mp-filter .form-control, .mp-filter .form-select, .mp-modal .form-control, .mp-modal .form-select { min-height: 40px; border-radius: 9px; border-color: #dbe4ee; color: #0f172a; font-size: .78rem; font-weight: 700; box-shadow: none; }
        .mp-filter .form-control:focus, .mp-filter .form-select:focus, .mp-modal .form-control:focus, .mp-modal .form-select:focus { border-color: #60a5fa; box-shadow: 0 0 0 3px rgba(59,130,246,.12); }
        .mp-filter-actions { display: flex; gap: .45rem; align-items: center; }
        .mp-filter-actions .mp-btn { white-space: nowrap; }
        .mp-table-panel { overflow: hidden; }
        .mp-table-toolbar { display: flex; align-items: center; justify-content: space-between; gap: .75rem; padding: .9rem 1rem; border-bottom: 1px solid #edf2f7; }
        .mp-table-title { display: flex; align-items: center; gap: .55rem; color: #0f172a; font-size: .86rem; font-weight: 900; }
        .mp-count { display: inline-flex; align-items: center; min-height: 22px; padding: .1rem .45rem; border-radius: 6px; background: #eff6ff; color: #1d4ed8; font-size: .68rem; font-weight: 900; }
        .mp-sort-note { color: #94a3b8; font-size: .7rem; }
        .mp-table-wrap { max-height: calc(100vh - 425px); min-height: 220px; overflow: auto; }
        .mp-table { min-width: 940px; margin: 0; }
        .mp-table th { position: sticky; top: 0; z-index: 2; padding: .7rem 1rem; border-bottom: 1px solid #dbe4ee; background: #f8fafc; color: #64748b; font-size: .65rem; font-weight: 950; letter-spacing: .07em; text-transform: uppercase; white-space: nowrap; }
        .mp-table td { padding: .8rem 1rem; border-color: #edf2f7; vertical-align: middle; }
        .mp-click-row { cursor: pointer; transition: background .15s ease; }
        .mp-click-row:hover td { background: #f8fbff; }
        .mp-date-main { color: #0f172a; font-size: .78rem; font-weight: 900; white-space: nowrap; }
        .mp-date-sub { color: #94a3b8; font-size: .68rem; }
        .mp-marketplace { display: inline-flex; align-items: center; gap: .4rem; color: #334155; font-size: .76rem; font-weight: 850; }
        .mp-marketplace-dot { width: 7px; height: 7px; border-radius: 50%; background: #f97316; box-shadow: 0 0 0 3px #ffedd5; }
        .mp-store-name { color: #0f172a; font-size: .78rem; font-weight: 900; }
        .mp-muted { color: #94a3b8; font-size: .69rem; }
        .mp-reference { display: inline-block; max-width: 205px; overflow: hidden; color: #475569; font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: .7rem; text-overflow: ellipsis; white-space: nowrap; }
        .mp-num { color: #0f172a; font-size: .8rem; font-weight: 950; text-align: right; font-variant-numeric: tabular-nums; white-space: nowrap; }
        .mp-status { display: inline-flex; align-items: center; gap: .35rem; border-radius: 7px; padding: .28rem .55rem; font-size: .68rem; font-weight: 900; border: 1px solid transparent; white-space: nowrap; }
        .mp-status::before { content: ''; width: 6px; height: 6px; border-radius: 50%; background: currentColor; }
        .mp-status-draft { color: #a16207; background: #fefce8; border-color: #fde68a; }
        .mp-status-posted { color: #166534; background: #f0fdf4; border-color: #bbf7d0; }
        .mp-status-void { color: #b91c1c; background: #fef2f2; border-color: #fecaca; }
        .mp-row-action { display: inline-flex; align-items: center; justify-content: center; width: 28px; height: 28px; border: 1px solid #dbe4ee; border-radius: 8px; color: #64748b; text-decoration: none; }
        .mp-row-action:hover { border-color: #93c5fd; background: #eff6ff; color: #1d4ed8; }
        .mp-empty { padding: 3.5rem 1rem !important; color: #64748b; text-align: center; }
        .mp-empty i { display: block; margin-bottom: .5rem; color: #cbd5e1; font-size: 2rem; }
        .mp-pagination { padding: .8rem 1rem; border-top: 1px solid #edf2f7; }
        .mp-modal .modal-content { overflow: hidden; border: 1px solid #dbe4ee; border-radius: 16px; box-shadow: 0 24px 70px rgba(15,23,42,.18); }
        .mp-modal .modal-header { padding: 1rem 1.15rem; border-bottom-color: #edf2f7; }
        .mp-modal .modal-body { padding: 1.15rem; }
        .mp-modal .modal-footer { padding: .85rem 1.15rem; border-top-color: #edf2f7; }
        .mp-import-info { display: flex; gap: .65rem; padding: .75rem .8rem; border: 1px solid #bfdbfe; border-radius: 10px; background: #eff6ff; color: #1e40af; font-size: .75rem; line-height: 1.45; }
        .mp-import-info i { margin-top: .1rem; font-size: 1rem; }
        .mp-store-toolbar { display: flex; align-items: center; justify-content: space-between; gap: .5rem; margin-bottom: .5rem; }
        .mp-store-selection { color: #64748b; font-size: .72rem; font-weight: 700; }
        .mp-store-selection strong { color: #1d4ed8; }
        .mp-store-table { border: 1px solid #dbe4ee; border-radius: 10px; overflow: hidden; }
        .mp-store-table th { padding: .55rem .7rem; background: #f8fafc; color: #64748b; font-size: .64rem; font-weight: 950; letter-spacing: .06em; text-transform: uppercase; }
        .mp-store-table td { padding: .65rem .7rem; border-color: #edf2f7; vertical-align: middle; }
        .mp-store-table .form-select { min-height: 36px; }
        .mp-store-code { margin-top: .12rem; color: #94a3b8; font-size: .68rem; }
        .mp-date-grid { display: grid; grid-template-columns: 1fr 1fr; gap: .7rem; margin-top: 1rem; }
        @media (max-width: 960px) {
            .mp-filter { grid-template-columns: repeat(2, minmax(0,1fr)); }
            .mp-filter-actions { grid-column: 1 / -1; }
        }
        @media (max-width: 768px) {
            .mp-hero { flex-direction: column; padding: 1rem; }
            .mp-actions { justify-content: flex-start; width: 100%; }
            .mp-actions .mp-btn { flex: 1; }
            .mp-kpi-grid { grid-template-columns: repeat(2, minmax(0,1fr)); gap: .55rem; }
            .mp-kpi { padding: .8rem; }
            .mp-kpi-value { font-size: 1.08rem; }
            .mp-filter { grid-template-columns: 1fr; padding: .8rem; }
            .mp-filter-actions { grid-column: auto; }
            .mp-filter-actions .mp-btn { flex: 1; }
            .mp-table-toolbar { align-items: flex-start; flex-direction: column; }
            .mp-sort-note { display: none; }
        }
    </style>
@endpush

@section('content')
<div class="mp-page">

    {{-- Header --}}
    <div class="mp-hero">
        <div>
            <div class="mp-eyebrow">Finance operations / cash settlement</div>
            <h1 class="mp-hero-title">Penerimaan Marketplace</h1>
            <div class="mp-hero-subtitle">Kelola pencairan marketplace, akun penerima, dan status posting jurnal dalam satu workspace.</div>
            <div class="mp-hero-meta"><i class="bi bi-circle-fill"></i> Ledger aktif · {{ now()->format('d M Y, H:i') }}</div>
        </div>
        <div class="mp-actions">
            @if($shopeeStores->isNotEmpty())
                <button type="button" class="mp-btn" data-bs-toggle="modal" data-bs-target="#importShopeeModal">
                    <i class="bi bi-cloud-arrow-down"></i>
                    Import Shopee
                </button>
            @endif
            <a href="{{ route('accounting.marketplace-payouts.create') }}" class="mp-btn mp-btn-primary">
                <i class="bi bi-plus-lg"></i>
                Tambah transaksi
            </a>
        </div>
    </div>

    {{-- Flash --}}
    @if(session('message'))
        <div class="mp-alert mp-alert-{{ session('status') === 'ok' ? 'success' : 'danger' }}" role="alert">
            <i class="bi bi-{{ session('status') === 'ok' ? 'check-circle-fill' : 'exclamation-triangle-fill' }}"></i>
            <div>{{ session('message') }}</div>
        </div>
    @endif

    {{-- KPI --}}
    <div class="mp-kpi-grid">
        <div class="mp-kpi mp-kpi-blue">
            <div class="mp-kpi-head"><div class="mp-kpi-label">Total dokumen</div><span class="mp-kpi-icon"><i class="bi bi-files"></i></span></div>
            <div class="mp-kpi-value">{{ $summary['total_docs'] }}</div>
            <div class="mp-kpi-note">Seluruh penerimaan tercatat</div>
        </div>
        <div class="mp-kpi mp-kpi-green">
            <div class="mp-kpi-head"><div class="mp-kpi-label">Total nilai</div><span class="mp-kpi-icon"><i class="bi bi-cash-stack"></i></span></div>
            <div class="mp-kpi-value">Rp {{ $fmt($summary['total_amount']) }}</div>
            <div class="mp-kpi-note">Nilai seluruh marketplace</div>
        </div>
        <div class="mp-kpi mp-kpi-green">
            <div class="mp-kpi-head"><div class="mp-kpi-label">Sudah diposting</div><span class="mp-kpi-icon"><i class="bi bi-journal-check"></i></span></div>
            <div class="mp-kpi-value">Rp {{ $fmt($summary['posted_amount']) }}</div>
            <div class="mp-kpi-note">Sudah masuk ke jurnal</div>
        </div>
        <div class="mp-kpi mp-kpi-amber">
            <div class="mp-kpi-head"><div class="mp-kpi-label">Draft / batal</div><span class="mp-kpi-icon"><i class="bi bi-hourglass-split"></i></span></div>
            <div class="mp-kpi-value">{{ $summary['draft_docs'] }} / {{ $summary['void_docs'] }}</div>
            <div class="mp-kpi-note">Perlu review / dibatalkan</div>
        </div>
    </div>

    {{-- Filter --}}
    <div class="mp-panel">
        <div class="mp-panel-head">
            <div>
                <div class="mp-panel-title"><i class="bi bi-sliders2 me-1"></i> Filter data</div>
                <div class="mp-panel-subtitle">Persempit daftar berdasarkan status, channel, atau periode pencairan.</div>
            </div>
            @if(request()->hasAny(['status','marketplace','from','to']))
                <a href="{{ route('accounting.marketplace-payouts.index') }}" class="mp-btn"><i class="bi bi-arrow-counterclockwise"></i> Reset filter</a>
            @endif
        </div>
        <form method="GET" class="mp-filter">
            <div>
                <label for="mp-status">Status</label>
                <select id="mp-status" name="status" class="form-select">
                    <option value="">Semua status</option>
                    @foreach(['draft'=>'Draft','posted'=>'Tercatat','void'=>'Dibatalkan'] as $val => $label)
                        <option value="{{ $val }}" @selected(request('status') === $val)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="mp-marketplace">Marketplace</label>
                <select id="mp-marketplace" name="marketplace" class="form-select">
                    <option value="">Semua marketplace</option>
                    @foreach($marketplaceNames as $name)
                        <option value="{{ $name }}" @selected(request('marketplace') === $name)>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="mp-from">Dari tanggal</label>
                <input id="mp-from" type="date" name="from" class="form-control" value="{{ request('from') }}">
            </div>
            <div>
                <label for="mp-to">Sampai tanggal</label>
                <input id="mp-to" type="date" name="to" class="form-control" value="{{ request('to') }}">
            </div>
            <div class="mp-filter-actions">
                <button type="submit" class="mp-btn mp-btn-primary"><i class="bi bi-funnel"></i> Terapkan</button>
            </div>
        </form>
    </div>

    {{-- Table --}}
    <div class="mp-panel mp-table-panel">
        <div class="mp-table-toolbar">
            <div class="mp-table-title"><i class="bi bi-list-check"></i> Daftar penerimaan <span class="mp-count">{{ $payouts->total() }}</span></div>
            <div class="mp-sort-note"><i class="bi bi-sort-down me-1"></i> Terbaru ditampilkan lebih dulu</div>
        </div>
        <div class="mp-table-wrap">
            <table class="table mp-table mb-0">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Channel / toko</th>
                        <th>Referensi</th>
                        <th>Akun Bank</th>
                        <th class="text-end">Jumlah</th>
                        <th>Status</th>
                        <th class="text-end">Detail</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($payouts as $p)
                        <tr class="mp-click-row" onclick="location.href='{{ route('accounting.marketplace-payouts.show', $p) }}'">
                            <td>
                                <div class="mp-date-main">{{ $p->date->format('d M Y') }}</div>
                                <div class="mp-date-sub">{{ $dayLabels[$p->date->format('l')] ?? $p->date->format('l') }}</div>
                            </td>
                            <td>
                                <div class="mp-marketplace"><span class="mp-marketplace-dot"></span>{{ $p->marketplace_name }}</div>
                                <div class="mp-store-name mt-1">{{ $p->store?->name ?? 'Tanpa toko' }}</div>
                                @if($p->store?->code)
                                    <div class="mp-muted">{{ $p->store->code }}</div>
                                @endif
                            </td>
                            <td><span class="mp-reference" title="{{ $p->reference ?: '-' }}">{{ $p->reference ?: '-' }}</span></td>
                            <td>
                                <div class="mp-store-name">{{ $p->bankAccount?->name ?? '-' }}</div>
                                <div class="mp-muted">{{ $p->bankAccount?->code ?: 'Akun tidak tersedia' }}</div>
                            </td>
                            <td class="mp-num">Rp {{ $fmt($p->amount) }}</td>
                            <td>
                                <span class="mp-status mp-status-{{ $p->status }}">
                                    {{ $statusLabels[$p->status] ?? $p->status }}
                                </span>
                            </td>
                            <td class="text-end"><a href="{{ route('accounting.marketplace-payouts.show', $p) }}" class="mp-row-action" aria-label="Buka detail"><i class="bi bi-arrow-up-right"></i></a></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="mp-empty"><i class="bi bi-inbox"></i><div>Belum ada penerimaan marketplace.</div><small>Gunakan filter atau import payout untuk mulai mencatat.</small></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($payouts->hasPages())
            <div class="mp-pagination">{{ $payouts->withQueryString()->links() }}</div>
        @endif
    </div>

</div>

@if($shopeeStores->isNotEmpty())
<div class="modal fade mp-modal" id="importShopeeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <div class="mp-eyebrow">Shopee wallet</div>
                    <h6 class="modal-title fw-bold mt-1">Import pencairan</h6>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('accounting.marketplace-payouts.import-shopee') }}">
                @csrf
                <div class="modal-body">
                    <div class="mp-import-info mb-3">
                        <i class="bi bi-info-circle-fill"></i>
                        <div><strong>Import aman untuk rekonsiliasi.</strong><br>Pilih toko dan akun bank penerima. Hanya pencairan wallet yang masuk sebagai Draft; periode panjang otomatis dipecah menjadi request maksimal 15 hari ke Shopee.</div>
                    </div>

                    <div>
                        <div class="mp-store-toolbar">
                            <div class="mp-modal-label mb-0">Toko yang diimport <span class="mp-store-selection">(<strong id="selectedShopeeStoreCount">0</strong> dipilih)</span></div>
                            <button type="button" class="btn btn-link btn-sm p-0" id="selectAllShopeeStores">Pilih semua toko</button>
                        </div>
                        <div class="mp-store-table table-responsive">
                            <table class="table table-sm align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th style="width:42px"></th>
                                        <th>Toko</th>
                                        <th style="min-width:220px">Akun Bank Tujuan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($shopeeStores as $store)
                                        <tr>
                                            <td>
                                                <input
                                                    class="form-check-input shopee-store-toggle"
                                                    type="checkbox"
                                                    name="stores[{{ $store->id }}][enabled]"
                                                    value="1"
                                                    @checked($loop->first)
                                                >
                                            </td>
                                            <td>
                                                <div class="mp-store-name">{{ $store->name }}</div>
                                                @if($store->code)
                                                    <div class="mp-store-code">{{ $store->code }}</div>
                                                @endif
                                            </td>
                                            <td>
                                                <select
                                                    name="stores[{{ $store->id }}][bank_account_id]"
                                                    class="form-select form-select-sm shopee-store-bank"
                                                >
                                                    <option value="">-- Pilih Akun --</option>
                                                    @foreach($bankAccounts as $acc)
                                                        <option value="{{ $acc->id }}">{{ $acc->code }} – {{ $acc->name }}</option>
                                                    @endforeach
                                                </select>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="mp-muted mt-2">
                            Transaksi yang sama pada toko yang sama tetap tidak dibuat dua kali. Jika akun bank berbeda,
                            transaksi akan dilewati dan dilaporkan sebagai konflik akun bank.
                        </div>
                    </div>

                    <div class="mp-date-grid">
                        <div>
                            <label class="mp-modal-label" for="mp-import-from">Periode mulai</label>
                            <input id="mp-import-from" type="date" name="from" class="form-control" value="{{ now()->subDays(30)->format('Y-m-d') }}" required>
                        </div>
                        <div>
                            <label class="mp-modal-label" for="mp-import-to">Periode sampai</label>
                            <input id="mp-import-to" type="date" name="to" class="form-control" value="{{ now()->format('Y-m-d') }}" required>
                        </div>
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="mp-btn" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="mp-btn mp-btn-primary"><i class="bi bi-play-circle"></i> Jalankan import</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const modal = document.getElementById('importShopeeModal');
            if (!modal) return;

            const syncStoreRow = function (checkbox) {
                const bankSelect = checkbox.closest('tr')?.querySelector('.shopee-store-bank');
                if (bankSelect) bankSelect.disabled = !checkbox.checked;
            };

            const toggles = Array.from(modal.querySelectorAll('.shopee-store-toggle'));
            const selectedCount = document.getElementById('selectedShopeeStoreCount');
            const selectAllButton = document.getElementById('selectAllShopeeStores');
            const syncSelection = function () {
                const checked = toggles.filter(function (checkbox) { return checkbox.checked; }).length;
                if (selectedCount) selectedCount.textContent = checked;
                if (selectAllButton) selectAllButton.textContent = checked === toggles.length ? 'Kosongkan semua' : 'Pilih semua toko';
            };

            toggles.forEach(function (checkbox) {
                syncStoreRow(checkbox);
                checkbox.addEventListener('change', function () {
                    syncStoreRow(checkbox);
                    syncSelection();
                });
            });

            selectAllButton?.addEventListener('click', function () {
                const shouldSelectAll = toggles.some(function (checkbox) {
                    return !checkbox.checked;
                });

                toggles.forEach(function (checkbox) {
                    checkbox.checked = shouldSelectAll;
                    syncStoreRow(checkbox);
                });
                syncSelection();
            });

            syncSelection();
        });
    </script>
@endpush
@endsection
