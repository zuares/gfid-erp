{{-- resources/views/purchasing/purchase_receipts/index.blade.php --}}
@extends('layouts.app')

@section('title', 'Goods Receipts')

@push('head')
    {{-- ========== PAGE WRAP + GLOBAL ========== --}}
    <style>
        .index-page {
            min-height: 100vh;
        }

        .index-page .page-wrap {
            max-width: 1150px;
            margin-inline: auto;
            padding: .75rem .75rem 4rem;
        }

        body[data-theme="light"] .index-page .page-wrap {
            background: radial-gradient(circle at top left,
                    rgba(59, 130, 246, 0.12) 0,
                    rgba(45, 212, 191, 0.10) 26%,
                    #f9fafb 60%);
        }

        .index-page .card-main {
            background: var(--card);
            border-radius: 16px;
            border: 1px solid rgba(148, 163, 184, 0.35);
            box-shadow:
                0 10px 30px rgba(15, 23, 42, 0.10),
                0 0 0 1px rgba(148, 163, 184, 0.08);
        }

        .mono {
            font-variant-numeric: tabular-nums;
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, "Liberation Mono";
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
        }
    </style>

    {{-- ========== HEADER STYLES ========== --}}
    <style>
        .index-page .index-header-title {
            font-size: 1.35rem;
            font-weight: 600;
        }

        .index-page .index-header-subtitle {
            font-size: .8rem;
            color: var(--muted);
        }

        .index-page .index-header-actions .btn-primary {
            border-radius: 999px;
            padding-inline: 1rem;
            box-shadow:
                0 8px 18px rgba(59, 130, 246, .30);
        }

        .index-page .index-header-pill {
            font-size: .75rem;
            border-radius: 999px;
            padding: .16rem .7rem;
            border: 1px solid rgba(148, 163, 184, .55);
            background: color-mix(in srgb, var(--card) 80%, var(--bg) 20%);
        }

        @media (max-width: 767.98px) {
            .index-page .index-header {
                flex-direction: column;
                align-items: flex-start;
                gap: .35rem;
            }

            .index-page .index-header-title {
                font-size: 1.15rem;
            }

            .index-page .index-header-actions {
                width: 100%;
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

        .index-page .status-badge-posted {
            background: rgba(34, 197, 94, .12);
            color: #16a34a;
            border: 1px solid rgba(34, 197, 94, .32);
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

        @media (max-width: 767.98px) {
            .index-page .metric-card .metric-value-main {
                font-size: 1.35rem;
            }
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
            font-size: .75rem;
            font-weight: 600;
            letter-spacing: .06em;
            text-transform: uppercase;
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

        .index-page .filter-card .badge {
            border-radius: 999px;
        }

        @media (max-width: 767.98px) {
            .index-page .filter-card form .btn {
                width: 100%;
            }

            .index-page .filter-card form .btn+.btn {
                margin-top: .25rem;
            }

            .index-page .filter-card .card-header {
                padding-inline: .9rem;
            }
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

        @media (max-width: 991.98px) {
            .index-table-wrapper {
                max-height: none;
            }
        }

        .index-page .table thead th {
            background: color-mix(in srgb, var(--card) 90%, var(--bg) 10%);
            border-bottom-color: var(--line);
            font-size: .78rem;
            text-transform: uppercase;
            letter-spacing: .04em;
        }

        .index-page .index-table-row {
            cursor: pointer;
            transition: background .16s ease, transform .08s ease, box-shadow .12s ease;
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
            font-size: .8rem;
        }

        @media (max-width: 767.98px) {
            .index-page .table thead {
                font-size: .7rem;
            }

            .index-page .table td {
                padding-top: .45rem;
                padding-bottom: .45rem;
                font-size: .8rem;
            }
        }
    </style>
@endpush

@section('content')
    @php
        /** @var \Illuminate\Contracts\Pagination\LengthAwarePaginator $receipts */
        $startIndex = method_exists($receipts, 'firstItem') ? $receipts->firstItem() : 1;

        $total = max($summary->total_receipts ?? 0, 1);
        $draftPct = (($summary->draft_count ?? 0) / $total) * 100;
        $postedPct = (($summary->posted_count ?? 0) / $total) * 100;
        $closedPct = (($summary->closed_count ?? 0) / $total) * 100;
    @endphp

    <div class="index-page">
        <div class="page-wrap">

            {{-- HEADER --}}
            <div class="index-header d-flex justify-content-between align-items-center mb-3 gap-2">
                <div class="d-flex flex-column gap-1">
                    <div class="d-flex flex-wrap align-items-center gap-2">
                        <h1 class="mb-0 index-header-title">Goods Receipts</h1>
                        <span class="index-header-pill d-none d-sm-inline">
                            {{ $summary->total_receipts ?? 0 }} GRN tersimpan
                        </span>
                    </div>
                    <div class="index-header-subtitle">
                        Penerimaan barang dari supplier ke gudang produksi / RTS.
                    </div>
                </div>

                <div class="index-header-actions d-flex align-items-center gap-2">
                    <a href="{{ route('purchasing.purchase_receipts.create') }}" class="btn btn-primary btn-sm">
                        + GRN Baru
                    </a>
                </div>
            </div>

            {{-- MINI DASHBOARD --}}
            <div class="row g-3 mb-3">
                {{-- Total GRN --}}
                <div class="col-12 col-md-6">
                    <div class="card metric-card h-100">
                        <div class="card-body py-2 px-3">
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
                        <div class="card-body py-2 px-3">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="metric-label">Status GRN</span>
                                <span class="metric-icon">📊</span>
                            </div>

                            <div class="d-flex flex-wrap gap-2 mono mb-2">
                                <span class="status-badge status-badge-draft">
                                    Draft: {{ angka($summary->draft_count ?? 0) }}
                                </span>
                                <span class="status-badge status-badge-posted">
                                    Posted: {{ angka($summary->posted_count ?? 0) }}
                                </span>
                                <span class="status-badge status-badge-closed">
                                    Closed: {{ angka($summary->closed_count ?? 0) }}
                                </span>
                            </div>

                            <div class="index-progress progress">
                                <div class="progress-bar bg-secondary" style="width: {{ $draftPct }}%"></div>
                                <div class="progress-bar bg-success" style="width: {{ $postedPct }}%"></div>
                                <div class="progress-bar bg-dark" style="width: {{ $closedPct }}%"></div>
                            </div>

                            <div class="metric-value-sub mt-1">
                                Komposisi: Draft / Posted / Closed
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Filter Aktif ringkas --}}
                <div class="col-12">
                    <div class="card metric-card h-100">
                        <div class="card-body py-2 px-3">
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
            <div class="card filter-card mb-3">
                <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2 py-2 px-3">
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
                    <div class="card-body pt-2 pb-3 px-3">
                        {{-- AUTO FILTER: form akan submit otomatis saat nilai berubah --}}
                        <form id="grnFilterForm" method="GET" class="row g-3 align-items-end">
                            {{-- BARIS 1: Supplier + Gudang + Status --}}
                            <div class="col-12 col-md-4">
                                <label class="form-label mb-1">Supplier</label>
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
                                <label class="form-label mb-1">Gudang</label>
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
                                <label class="form-label mb-1">Status</label>
                                <select name="status" class="form-select form-select-sm">
                                    <option value="">Semua</option>
                                    <option value="draft" @selected(request('status') == 'draft')>Draft</option>
                                    <option value="posted" @selected(request('status') == 'posted')>Posted</option>
                                    <option value="closed" @selected(request('status') == 'closed')>Closed</option>
                                </select>
                            </div>

                            {{-- BARIS 2: Periode + Tombol --}}
                            <div class="col-12 col-md-4">
                                <label class="form-label mb-1">Periode Tanggal</label>
                                <div class="input-group input-group-sm">
                                    <input type="date" name="from_date" class="form-control"
                                        value="{{ request('from_date') }}" placeholder="Dari">
                                    <span class="input-group-text">s.d.</span>
                                    <input type="date" name="to_date" class="form-control"
                                        value="{{ request('to_date') }}" placeholder="Sampai">
                                </div>
                                <div class="form-text">
                                    Filter otomatis diterapkan saat Anda mengubah nilai.
                                </div>
                            </div>

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
            <div class="card card-main p-2">
                <div class="table-responsive index-table-wrapper">
                    <table class="table table-sm mb-0 align-middle index-table">
                        <thead>
                            <tr>
                                <th style="width: 1%" class="text-center">No</th>
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
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // ============================
            // INFINITE SCROLL
            // ============================
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
                rootMargin: '200px',
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

            // ============================
            // AUTO FILTER: submit on change
            // ============================
            const filterForm = document.getElementById('grnFilterForm');
            if (filterForm) {
                const autoSubmit = () => {
                    filterForm.submit();
                };

                const selects = filterForm.querySelectorAll('select');
                selects.forEach(function(select) {
                    select.addEventListener('change', autoSubmit);
                });

                const dateInputs = filterForm.querySelectorAll('input[type="date"]');
                dateInputs.forEach(function(input) {
                    input.addEventListener('change', autoSubmit);
                });
            }
        });
    </script>
@endpush
