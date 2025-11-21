@extends('layouts.app')

@section('title', 'Goods Receipts')

@push('head')
    {{-- ========== HEADER STYLES (copy dari PO, ganti sedikit teks) ========== --}}
    <style>
        .index-page .index-header-title {
            font-size: 1.3rem;
            font-weight: 600;
        }

        .index-page .index-header-actions .btn-primary {
            border-radius: 999px;
            padding-inline: 1rem;
            box-shadow:
                0 8px 18px rgba(59, 130, 246, .30);
        }

        @media (max-width: 767.98px) {
            .index-page .index-header {
                flex-direction: column;
                align-items: center;
                text-align: center;
            }

            .index-page .index-header-actions {
                width: 100%;
                display: flex;
                justify-content: center;
            }

            .index-page .index-header-title {
                font-size: 1.1rem;
            }

            .index-page .index-header-actions .btn-primary {
                width: 100%;
                justify-content: center;
            }
        }
    </style>

    {{-- ========== MINI DASHBOARD STYLES ========== --}}
    <style>
        .index-page .metric-card {
            border-radius: 14px;
            border: 1px solid var(--line);
            background: color-mix(in srgb, var(--card) 92%, var(--bg) 8%);
            box-shadow:
                0 10px 24px rgba(15, 23, 42, .06),
                0 0 0 1px rgba(148, 163, 184, .10);
        }

        .index-page .metric-label {
            font-size: .78rem;
            text-transform: uppercase;
            letter-spacing: .06em;
            color: var(--muted);
        }

        .index-page .metric-icon {
            font-size: .9rem;
            color: var(--muted);
        }

        .index-page .metric-value-main {
            font-size: 1.6rem;
            font-weight: 600;
        }

        .index-page .metric-value-sub {
            font-size: .8rem;
            color: var(--muted);
        }

        .index-page .status-badge {
            font-size: .75rem;
            border-radius: 999px;
            padding-inline: .55rem;
            padding-block: .15rem;
        }

        .index-page .status-badge-draft {
            background: color-mix(in srgb, var(--muted) 18%, transparent);
            color: var(--muted);
            border: 1px solid color-mix(in srgb, var(--muted) 35%, transparent);
        }

        .index-page .status-badge-approved {
            background: rgba(34, 197, 94, .10);
            color: #16a34a;
            border: 1px solid rgba(34, 197, 94, .26);
        }

        .index-page .status-badge-closed {
            background: rgba(15, 23, 42, .14);
            color: #0f172a;
            border: 1px solid rgba(15, 23, 42, .3);
        }

        :root[data-theme="dark"] .index-page .status-badge-closed {
            background: rgba(15, 23, 42, .75);
            color: #e5e7eb;
            border-color: rgba(15, 23, 42, .9);
        }

        .index-page .index-progress {
            background: color-mix(in srgb, var(--card) 60%, var(--bg) 40%);
            border-radius: 999px;
            overflow: hidden;
            height: .4rem;
        }

        .index-page .index-progress .bg-secondary {
            background: color-mix(in srgb, var(--muted) 75%, transparent) !important;
        }

        .index-page .index-progress .bg-success {
            background: #22c55e !important;
        }

        .index-page .index-progress .bg-dark {
            background: #020617 !important;
        }

        :root[data-theme="dark"] .index-page .index-progress .bg-dark {
            background: #0f172a !important;
        }
    </style>

    {{-- ========== FILTER CARD STYLES ========== --}}
    <style>
        .index-page .filter-card {
            border-radius: 16px;
            background: color-mix(in srgb, var(--card) 94%, var(--bg) 6%);
            border: 1px solid var(--line);
        }

        .index-page .filter-card .card-header {
            background: transparent;
            border-bottom-color: var(--line);
        }

        .index-page .filter-card .form-label {
            font-size: .78rem;
            text-transform: uppercase;
            letter-spacing: .05em;
            color: var(--muted);
        }

        .index-page .filter-card .badge {
            border-radius: 999px;
        }
    </style>

    {{-- ========== TABLE + ROW STYLES ========== --}}
    <style>
        @media (min-width: 992px) {
            .index-table-wrapper {
                max-height: 60vh;
                overflow-y: auto;
                overflow-x: hidden;
            }

            .index-table-wrapper::-webkit-scrollbar {
                width: 6px;
            }

            .index-table-wrapper::-webkit-scrollbar-thumb {
                background: color-mix(in srgb, var(--muted) 60%, transparent);
                border-radius: 999px;
            }

            .index-table-wrapper::-webkit-scrollbar-track {
                background: transparent;
            }
        }

        .index-page .table thead th {
            background: color-mix(in srgb, var(--card) 90%, var(--bg) 10%);
        }

        .index-page .index-table-row {
            cursor: pointer;
            transition: background .16s ease, transform .08s ease;
        }

        .index-page .index-table-row:hover {
            background: color-mix(in srgb, var(--accent-soft) 60%, var(--card) 40%);
            transform: translateY(-1px);
        }

        .index-page .index-table-row td {
            border-bottom-color: var(--line);
            vertical-align: middle;
            padding-top: .55rem;
            padding-bottom: .55rem;
        }

        .index-page .index-row-subtext {
            font-size: .78rem;
            color: color-mix(in srgb, var(--muted) 85%, var(--text) 15%);
        }

        :root[data-theme="dark"] .index-page .index-row-subtext {
            color: color-mix(in srgb, var(--muted) 40%, var(--text) 60%);
        }

        .index-page .index-code-badge {
            display: inline-flex;
            align-items: center;
            border-radius: 999px;
            border: 1px solid var(--line);
            padding: .14rem .6rem;
            font-size: .72rem;
            background: color-mix(in srgb, var(--card) 88%, var(--bg) 12%);
            color: var(--text);
        }

        :root[data-theme="dark"] .index-page .index-code-badge {
            background: color-mix(in srgb, var(--card) 80%, #020617 20%);
            border-color: var(--line);
            color: var(--text);
        }

        .index-page .index-loading {
            font-size: .8rem;
            color: var(--muted);
        }

        .index-page .col-number {
            color: var(--muted);
        }

        @media (max-width: 767.98px) {

            html,
            body {
                max-width: 100%;
                overflow-x: hidden;
            }

            .index-page {
                overflow-x: hidden;
            }

            .index-page .table thead {
                font-size: .75rem;
            }

            .index-page .table td {
                padding-top: .4rem;
                padding-bottom: .4rem;
            }
        }
    </style>

    <style>
        .index-page .filter-card .form-label {
            font-size: .75rem;
            font-weight: 600;
            letter-spacing: .06em;
            color: var(--muted);
        }

        .index-page .filter-card .form-text {
            font-size: .7rem;
            color: var(--muted);
        }

        .index-page .filter-card .input-group-text {
            background: color-mix(in srgb, var(--card) 90%, var(--bg) 10%);
            border-color: var(--line);
            font-size: .7rem;
        }

        @media (max-width: 767.98px) {
            .index-page .filter-card form .btn {
                width: 100%;
            }

            .index-page .filter-card form .btn+.btn {
                margin-top: .25rem;
            }
        }
    </style>
@endpush

@section('content')
    @php
        use Illuminate\Support\Carbon;
        $startIndex = method_exists($receipts, 'firstItem') ? $receipts->firstItem() : 1;

        $total = max($summary->total_receipts ?? 0, 1);
        $draftPct = (($summary->draft_count ?? 0) / $total) * 100;
        $appPct = (($summary->approved_count ?? 0) / $total) * 100;
        $closedPct = (($summary->closed_count ?? 0) / $total) * 100;
    @endphp

    <div class="container py-3 index-page">

        {{-- HEADER + BUTTON --}}
        <div class="index-header d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
            <div>
                <h1 class="mb-0 index-header-title">Goods Receipts</h1>
            </div>
            <div class="index-header-actions">
                <a href="{{ route('purchasing.purchase_receipts.create') }}" class="btn btn-primary">
                    + GRN Baru
                </a>
            </div>
        </div>

        {{-- MINI DASHBOARD --}}
        <div class="row g-3 mb-3">
            {{-- Total GRN --}}
            <div class="col-12 col-md-6">
                <div class="card metric-card h-100">
                    <div class="card-body py-2">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="metric-label">Total GRN (hasil filter)</span>
                            <span class="metric-icon">📥</span>
                        </div>
                        <div class="metric-value-main mono">
                            {{ angka($summary->total_receipts ?? 0) }}
                        </div>
                        <div class="metric-value-sub">
                            {{ request('supplier_id') ||
                            request('warehouse_id') ||
                            request('status') ||
                            request('from_date') ||
                            request('to_date')
                                ? 'Data setelah filter diterapkan'
                                : 'Semua Goods Receipt yang tersimpan' }}
                        </div>
                    </div>
                </div>
            </div>

            {{-- Status GRN --}}
            <div class="col-12 col-md-6">
                <div class="card metric-card h-100">
                    <div class="card-body py-2">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="metric-label">Status GRN</span>
                            <span class="metric-icon">📊</span>
                        </div>

                        <div class="d-flex flex-wrap gap-2 mono mb-2">
                            <span class="status-badge status-badge-draft">
                                Draft: {{ angka($summary->draft_count ?? 0) }}
                            </span>
                            <span class="status-badge status-badge-approved">
                                Approved: {{ angka($summary->approved_count ?? 0) }}
                            </span>
                            <span class="status-badge status-badge-closed">
                                Closed: {{ angka($summary->closed_count ?? 0) }}
                            </span>
                        </div>

                        <div class="index-progress progress">
                            <div class="progress-bar bg-secondary" style="width: {{ $draftPct }}%"></div>
                            <div class="progress-bar bg-success" style="width: {{ $appPct }}%"></div>
                            <div class="progress-bar bg-dark" style="width: {{ $closedPct }}%"></div>
                        </div>

                        <div class="metric-value-sub mt-1">
                            Komposisi: Draft / Approved / Closed
                        </div>
                    </div>
                </div>
            </div>

            {{-- Filter Aktif ringkas --}}
            <div class="col-12">
                <div class="card metric-card h-100">
                    <div class="card-body py-2">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="metric-label">Filter Aktif</span>
                            <span class="metric-icon">🔍</span>
                        </div>

                        @php
                            $supId = request('supplier_id');
                            $sup = $supId ? $suppliers->firstWhere('id', $supId) : null;

                            $whId = request('warehouse_id');
                            $wh = $whId ? $warehouses->firstWhere('id', $whId) : null;
                        @endphp

                        <div class="d-flex flex-wrap gap-2 mb-1">
                            <span class="badge bg-light text-dark border small">
                                Supplier:
                                <strong>{{ $sup?->name ?? 'Semua' }}</strong>
                            </span>

                            <span class="badge bg-light text-dark border small">
                                Gudang:
                                <strong>{{ $wh?->name ?? 'Semua' }}</strong>
                            </span>

                            <span class="badge bg-light text-dark border small">
                                Status:
                                <strong>{{ request('status') ? ucfirst(request('status')) : 'Semua' }}</strong>
                            </span>

                            <span class="badge bg-light text-dark border small">
                                Periode:
                                <strong>
                                    {{ request('from_date') ?: 'Awal' }}
                                    &ndash;
                                    {{ request('to_date') ?: 'Akhir' }}
                                </strong>
                            </span>
                        </div>

                        @if (!request('from_date') && !request('to_date') && $summary?->last_date)
                            <div class="metric-value-sub">
                                Last GRN: <span class="mono">{{ $summary->last_date }}</span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        {{-- END MINI DASHBOARD --}}

        {{-- FILTER FORM --}}
        {{-- =======================================
     SECTION: FILTER FORM (VERSI RAPI)
======================================= --}}
        <div class="card filter-card mb-3">
            <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2 py-2">
                <div class="d-flex align-items-center gap-2">
                    <span class="fw-semibold small text-uppercase">Filter Pencarian</span>
                    <span class="badge bg-light text-dark border-0 small d-none d-md-inline">
                        {{ $summary->total_receipts ?? 0 }} GRN ditemukan
                    </span>
                </div>

                <button class="btn btn-sm btn-outline-secondary d-md-none" type="button" data-bs-toggle="collapse"
                    data-bs-target="#grn-filter-collapse" aria-expanded="false" aria-controls="grn-filter-collapse">
                    Tampilkan / Sembunyikan
                </button>
            </div>

            <div id="grn-filter-collapse" class="collapse show">
                <div class="card-body">
                    <form method="GET" class="row g-3 align-items-end">

                        {{-- BARIS 1: Supplier + Gudang --}}
                        <div class="col-12 col-md-4">
                            <label class="form-label small mb-1 text-uppercase">Supplier</label>
                            <select name="supplier_id" class="form-select form-select-sm">
                                <option value="">Semua Supplier</option>
                                @foreach ($suppliers as $sup)
                                    <option value="{{ $sup->id }}" @selected(request('supplier_id') == $sup->id)>
                                        {{ $sup->code }} — {{ $sup->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-12 col-md-4">
                            <label class="form-label small mb-1 text-uppercase">Gudang</label>
                            <select name="warehouse_id" class="form-select form-select-sm">
                                <option value="">Semua Gudang</option>
                                @foreach ($warehouses as $wh)
                                    <option value="{{ $wh->id }}" @selected(request('warehouse_id') == $wh->id)>
                                        {{ $wh->code }} — {{ $wh->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-6 col-md-2">
                            <label class="form-label small mb-1 text-uppercase">Status</label>
                            <select name="status" class="form-select form-select-sm">
                                <option value="">Semua</option>
                                <option value="draft" @selected(request('status') == 'draft')>Draft</option>
                                <option value="approved" @selected(request('status') == 'approved')>Approved</option>
                                <option value="closed" @selected(request('status') == 'closed')>Closed</option>
                            </select>
                        </div>

                        {{-- BARIS 2: Periode --}}
                        <div class="col-12 col-md-4">
                            <label class="form-label small mb-1 text-uppercase">Periode Tanggal</label>
                            <div class="input-group input-group-sm">
                                <input type="date" name="from_date" class="form-control"
                                    value="{{ request('from_date') }}" placeholder="Dari">
                                <span class="input-group-text">s.d.</span>
                                <input type="date" name="to_date" class="form-control" value="{{ request('to_date') }}"
                                    placeholder="Sampai">
                            </div>
                            <div class="form-text small">
                                Kosongkan untuk menampilkan semua periode
                            </div>
                        </div>

                        {{-- BARIS 2: Tombol --}}
                        <div class="col-12 col-md-4 ms-md-auto">
                            <div class="d-flex flex-wrap justify-content-md-end gap-2">
                                <button class="btn btn-sm btn-primary px-3" type="submit">
                                    Terapkan Filter
                                </button>
                                <a href="{{ route('purchasing.purchase_receipts.index') }}"
                                    class="btn btn-sm btn-outline-secondary px-3">
                                    Reset
                                </a>
                            </div>
                        </div>

                    </form>
                </div>
            </div>
        </div>


        {{-- TABLE + INFINITE SCROLL --}}
        <div class="card p-2">
            <div class="table-responsive index-table-wrapper">
                <table class="table table-sm mb-0 align-middle index-table">
                    <thead>
                        <tr>
                            <th style="width: 1%">No</th>
                            <th>Tanggal</th>
                            <th>Kode</th>
                            <th>Supplier</th>
                            <th>Gudang</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody id="grn-table-body">
                        @include('purchasing.purchase_receipts._rows', [
                            'receipts' => $receipts,
                            'startIndex' => $startIndex,
                        ])
                    </tbody>
                </table>
            </div>

            <div id="grn-loading" class="text-center py-2 index-loading d-none">
                Memuat data berikutnya...
            </div>
            <div id="grn-load-more-trigger"></div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            let nextPageUrl = @json($receipts->nextPageUrl());
            const tableBody = document.getElementById('grn-table-body');
            const loadingEl = document.getElementById('grn-loading');
            const triggerEl = document.getElementById('grn-load-more-trigger');
            let isLoading = false;

            function showLoading() {
                if (loadingEl) loadingEl.classList.remove('d-none');
            }

            function hideLoading() {
                if (loadingEl) loadingEl.classList.add('d-none');
            }

            async function loadMore() {
                if (!nextPageUrl || isLoading) return;
                isLoading = true;
                showLoading();

                try {
                    const response = await fetch(nextPageUrl, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        }
                    });

                    if (!response.ok) {
                        throw new Error('Network error');
                    }

                    const data = await response.json();

                    if (data.html) {
                        tableBody.insertAdjacentHTML('beforeend', data.html);
                    }

                    nextPageUrl = data.next_page_url;

                    if (!nextPageUrl && observer) {
                        observer.unobserve(triggerEl);
                    }
                } catch (e) {
                    console.error(e);
                } finally {
                    hideLoading();
                    isLoading = false;
                }
            }

            const observer = new IntersectionObserver(entries => {
                entries.forEach(entry => {
                    if (entry.isIntersecting && nextPageUrl) {
                        loadMore();
                    }
                });
            }, {
                root: null,
                rootMargin: '0px',
                threshold: 0.1
            });

            if (nextPageUrl && triggerEl) {
                observer.observe(triggerEl);
            }

            // Klik baris -> ke halaman show (kecuali klik <a>/<button>)
            if (tableBody) {
                tableBody.addEventListener('click', function(e) {
                    const row = e.target.closest('.index-table-row');
                    if (!row) return;

                    if (e.target.closest('a, button')) return;

                    const href = row.dataset.href;
                    if (href) {
                        window.location = href;
                    }
                });
            }
        });
    </script>
@endpush
