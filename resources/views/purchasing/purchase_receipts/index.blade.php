{{-- resources/views/purchasing/purchase_receipts/index.blade.php --}}
@extends('layouts.app')

@section('title', 'Goods Receipts')

@push('head')
<style>
    .page-wrap {
        max-width: 1080px;
        margin-inline: auto;
        padding-bottom: 3rem;
    }

    .card-filter {
        background: var(--card);
        border-radius: 14px;
        border: 1px solid var(--line);
        padding: .85rem .95rem;
        margin-bottom: .85rem;
    }

    .card-table {
        background: var(--card);
        border-radius: 14px;
        border: 1px solid var(--line);
        overflow: hidden;
    }

    .mono {
        font-variant-numeric: tabular-nums;
        font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, "Liberation Mono";
    }

    .table thead th {
        border-bottom-width: 1px;
        font-size: .72rem;
        text-transform: uppercase;
        letter-spacing: .07em;
        color: var(--muted);
        white-space: nowrap;
        padding: .5rem .75rem;
    }

    .table tbody td {
        vertical-align: middle;
        font-size: .83rem;
        padding: .45rem .75rem;
    }

    .table tbody tr:last-child td { border-bottom: none; }

    .grn-row { cursor: pointer; }
    .grn-row:hover { background: rgba(59,130,246,.04); }

    /* Status badges */
    .badge-grn {
        border-radius: 999px;
        font-size: .7rem;
        padding: .1rem .6rem;
        border: 1px solid transparent;
        white-space: nowrap;
    }
    .badge-grn-draft {
        background: rgba(148,163,184,.12);
        color: #64748b;
        border-color: rgba(148,163,184,.5);
    }
    .badge-grn-posted {
        background: rgba(22,163,74,.12);
        color: #15803d;
        border-color: rgba(22,163,74,.6);
    }
    .badge-grn-closed {
        background: rgba(15,23,42,.10);
        color: #334155;
        border-color: rgba(15,23,42,.25);
    }

    @media (prefers-color-scheme: dark) {
        .badge-grn-closed { color: #cbd5e1; border-color: rgba(203,213,225,.3); }
    }
    [data-theme="dark"] .badge-grn-closed { color: #cbd5e1; border-color: rgba(203,213,225,.3); }

    /* Mobile card */
    @media (max-width: 767.98px) {
        .page-wrap { padding-inline: .75rem; }
        .card-filter { padding: .75rem .8rem; }

        .card-grn-mobile {
            background: var(--card);
            border-radius: 12px;
            border: 1px solid var(--line);
            padding: .75rem .85rem;
            margin-bottom: .6rem;
        }
        .card-grn-mobile .meta {
            font-size: .75rem;
            color: var(--muted);
        }
        .card-grn-mobile .meta span+span::before {
            content: "•";
            margin-inline: .35rem;
            opacity: .65;
        }
    }
</style>
@endpush

@section('content')
@php
    $user = auth()->user();
    $startIndex = method_exists($receipts, 'firstItem') ? $receipts->firstItem() : 1;
@endphp

<div class="page-wrap py-3">

    {{-- HEADER --}}
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h2 class="mb-0">Goods Receipts</h2>
            <div class="text-muted small">Penerimaan barang dari supplier ke gudang.</div>
        </div>
        <a href="{{ route('purchasing.purchase_receipts.create') }}" class="btn btn-primary btn-sm">
            + GRN Baru
        </a>
    </div>

    {{-- FLASH --}}
    @if (session('success'))
        <div class="alert alert-success py-2 small">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger py-2 small">{{ session('error') }}</div>
    @endif

    {{-- FILTER --}}
    <div class="card-filter">
        <form id="grn-filter-form" method="GET" action="{{ route('purchasing.purchase_receipts.index') }}">
            <input type="hidden" name="from_date" id="grn-from-date" value="{{ request('from_date') }}">
            <input type="hidden" name="to_date"   id="grn-to-date"   value="{{ request('to_date') }}">

            <div class="d-flex flex-wrap gap-2 align-items-center">
                <input type="text" name="supplier_search"
                    id="grn-supplier-search"
                    value="{{ request('supplier_search') }}"
                    placeholder="Cari supplier…"
                    class="form-control form-control-sm"
                    style="max-width:180px;" autocomplete="off" />

                <select name="warehouse_id" class="form-select form-select-sm grn-filter-auto" style="max-width:150px;">
                    <option value="">Semua Gudang</option>
                    @foreach ($warehouses as $wh)
                        <option value="{{ $wh->id }}" @selected(request('warehouse_id') == $wh->id)>
                            {{ $wh->name }}
                        </option>
                    @endforeach
                </select>

                <select name="status" class="form-select form-select-sm grn-filter-auto" style="max-width:120px;">
                    <option value="">Semua Status</option>
                    <option value="draft"  @selected(request('status') === 'draft')>Draft</option>
                    <option value="posted" @selected(request('status') === 'posted')>Posted</option>
                    <option value="closed" @selected(request('status') === 'closed')>Closed</option>
                </select>

                @php
                    $idMonthsGrn = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
                    $grnRangeDisplay = '';
                    if (request('from_date') && request('to_date')) {
                        try {
                            $f = \Carbon\Carbon::parse(request('from_date'));
                            $t = \Carbon\Carbon::parse(request('to_date'));
                            $grnRangeDisplay = $f->day . ' ' . $idMonthsGrn[$f->month-1]
                                . ' – ' . $t->day . ' ' . $idMonthsGrn[$t->month-1] . ' ' . $t->year;
                        } catch (\Exception $e) {
                            $grnRangeDisplay = request('from_date') . ' – ' . request('to_date');
                        }
                    } elseif (request('from_date')) {
                        try {
                            $f = \Carbon\Carbon::parse(request('from_date'));
                            $grnRangeDisplay = $f->day . ' ' . $idMonthsGrn[$f->month-1] . ' ' . $f->year;
                        } catch (\Exception $e) {
                            $grnRangeDisplay = request('from_date');
                        }
                    }
                @endphp
                <input type="text" id="grn-date-range" value="{{ $grnRangeDisplay }}"
                    placeholder="Pilih tanggal…" autocomplete="off" data-gf-date="off"
                    class="form-control form-control-sm" style="max-width:190px;cursor:pointer;" readonly />

                @if (request()->filled('supplier_search') || request()->filled('warehouse_id') || request()->filled('status') || request()->filled('from_date') || request()->filled('to_date'))
                    <a href="{{ route('purchasing.purchase_receipts.index') }}"
                       class="btn btn-sm btn-outline-secondary" style="font-size:.78rem;padding:.25rem .65rem;">
                        <i class="bi bi-x me-1"></i>Reset
                    </a>
                @endif
            </div>
        </form>

        {{-- SUMMARY --}}
        @if (isset($summary))
            <div class="d-flex flex-wrap gap-1 mt-2" style="font-size:.78rem;color:var(--muted);">
                <span><strong class="text-body mono">{{ $summary->total_receipts ?? 0 }}</strong> GRN</span>
                <span>·</span>
                <span>Draft <strong class="text-body mono">{{ $summary->draft_count ?? 0 }}</strong></span>
                <span>·</span>
                <span>Posted <strong class="text-body mono">{{ $summary->posted_count ?? 0 }}</strong></span>
                @if (($summary->closed_count ?? 0) > 0)
                    <span>·</span>
                    <span>Closed <strong class="text-body mono">{{ $summary->closed_count }}</strong></span>
                @endif
                @if (!empty($summary->last_date))
                    <span>·</span>
                    <span>Terakhir <strong class="text-body mono">{{ id_date($summary->last_date) }}</strong></span>
                @endif
            </div>
        @endif
    </div>

    {{-- DESKTOP TABLE --}}
    <div class="card-table d-none d-md-block">
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead>
                    <tr>
                        <th style="width:22%;">GRN</th>
                        <th>Supplier</th>
                        <th style="width:16%;">Gudang</th>
                        <th style="width:14%;">Status</th>
                        <th style="width:4%;"></th>
                    </tr>
                </thead>
                <tbody id="grn-table-body">
                    @forelse ($receipts as $receipt)
                        @php
                            $hasReturn   = ($receipt->return_count ?? 0) > 0;
                            $statusClass = match ((string) $receipt->status) {
                                'posted' => 'badge-grn badge-grn-posted',
                                'closed' => 'badge-grn badge-grn-closed',
                                default  => 'badge-grn badge-grn-draft',
                            };
                        @endphp
                        <tr class="grn-row" data-href="{{ route('purchasing.purchase_receipts.show', $receipt->id) }}">
                            <td>
                                <span class="fw-semibold mono" style="font-size:.82rem;white-space:nowrap;">
                                    {{ $receipt->code ?? ('GRN#' . $receipt->id) }}
                                </span>
                                <div class="text-muted mono" style="font-size:.72rem;white-space:nowrap;">
                                    {{ $receipt->date ? id_date($receipt->date) : '—' }}
                                </div>
                            </td>
                            <td style="max-width:220px;">
                                <div style="font-size:.83rem;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                                    {{ optional($receipt->supplier)->name ?? '—' }}
                                </div>
                                <div class="text-muted mono" style="font-size:.71rem;">
                                    {{ optional($receipt->supplier)->code ?? '' }}
                                </div>
                            </td>
                            <td>
                                <div style="font-size:.83rem;">{{ optional($receipt->warehouse)->name ?? '—' }}</div>
                                <div class="text-muted mono" style="font-size:.71rem;">{{ optional($receipt->warehouse)->code ?? '' }}</div>
                            </td>
                            <td>
                                <span class="{{ $statusClass }}">{{ ucfirst($receipt->status) }}</span>
                                @if ($hasReturn)
                                    <div class="text-muted" style="font-size:.72rem;margin-top:.15rem;">Retur {{ $receipt->return_count }}x</div>
                                @endif
                            </td>
                            <td class="text-end">
                                <i class="bi bi-chevron-right text-muted" style="font-size:.8rem;opacity:.4;"></i>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">Belum ada Goods Receipt.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div id="grn-loading" class="text-center py-2 text-muted d-none" style="font-size:.8rem;">
            Memuat data…
        </div>
        <div id="grn-load-more-trigger"></div>

        @if (method_exists($receipts, 'links'))
            <div class="px-3 py-2">{{ $receipts->links() }}</div>
        @endif
    </div>

    {{-- MOBILE LIST --}}
    <div class="d-md-none">
        @forelse ($receipts as $receipt)
            @php
                $hasReturn   = ($receipt->return_count ?? 0) > 0;
                $statusClass = match ((string) $receipt->status) {
                    'posted' => 'badge-grn badge-grn-posted',
                    'closed' => 'badge-grn badge-grn-closed',
                    default  => 'badge-grn badge-grn-draft',
                };
            @endphp
            <div class="card-grn-mobile" onclick="window.location='{{ route('purchasing.purchase_receipts.show', $receipt->id) }}'">
                <div class="d-flex justify-content-between align-items-start mb-1">
                    <div>
                        <div class="fw-semibold mono" style="font-size:.88rem;">
                            {{ $receipt->code ?? ('GRN#' . $receipt->id) }}
                        </div>
                        <div class="meta mt-1">
                            <span class="mono">{{ $receipt->date ? id_date($receipt->date) : '—' }}</span>
                            @if (optional($receipt->supplier)->name)
                                <span>{{ $receipt->supplier->name }}</span>
                            @endif
                            @if (optional($receipt->warehouse)->name)
                                <span>{{ $receipt->warehouse->name }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="text-end">
                        <span class="{{ $statusClass }}">{{ ucfirst($receipt->status) }}</span>
                        @if ($hasReturn)
                            <div class="text-muted mt-1" style="font-size:.72rem;">Retur {{ $receipt->return_count }}x</div>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="text-center text-muted py-3 small">Belum ada Goods Receipt.</div>
        @endforelse

        <div class="mt-2">
            @if (method_exists($receipts, 'links'))
                {{ $receipts->links() }}
            @endif
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('grn-filter-form');
    if (!form) return;

    // Selects: auto-submit on change
    form.querySelectorAll('select.grn-filter-auto').forEach(function (el) {
        el.addEventListener('change', function () { form.submit(); });
    });

    // Supplier text: debounce 500ms + auto-focus
    const supplierInput = document.getElementById('grn-supplier-search');
    if (supplierInput) {
        let timer;
        supplierInput.addEventListener('input', function () {
            clearTimeout(timer);
            timer = setTimeout(function () { form.submit(); }, 500);
        });
        setTimeout(function () {
            supplierInput.focus();
            const len = supplierInput.value.length;
            supplierInput.setSelectionRange(len, len);
        }, 100);
    }

    // Flatpickr date range
    const rangeInput = document.getElementById('grn-date-range');
    const fromHidden = document.getElementById('grn-from-date');
    const toHidden   = document.getElementById('grn-to-date');
    if (rangeInput && window.flatpickr) {
        const ID_MONTHS = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
        function fmtDate(d, withYear) {
            return d.getDate() + ' ' + ID_MONTHS[d.getMonth()] + (withYear ? ' ' + d.getFullYear() : '');
        }
        function fmtRange(dates) {
            if (dates.length === 2) {
                const sameYear = dates[0].getFullYear() === dates[1].getFullYear();
                return fmtDate(dates[0], !sameYear) + ' – ' + fmtDate(dates[1], true);
            }
            if (dates.length === 1) return fmtDate(dates[0], true) + ' …';
            return '';
        }

        flatpickr(rangeInput, {
            mode: 'range',
            dateFormat: 'Y-m-d',
            locale: { firstDayOfWeek: 1 },
            allowInput: false,
            defaultDate: [fromHidden.value, toHidden.value].filter(Boolean),
            onChange: function (selectedDates, dateStr, fp) {
                fp.input.value = fmtRange(selectedDates);
                if (selectedDates.length === 1) {
                    fromHidden.value = flatpickr.formatDate(selectedDates[0], 'Y-m-d');
                    toHidden.value   = '';
                } else if (selectedDates.length === 2) {
                    fromHidden.value = flatpickr.formatDate(selectedDates[0], 'Y-m-d');
                    toHidden.value   = flatpickr.formatDate(selectedDates[1], 'Y-m-d');
                    form.submit();
                }
            },
            onReady: function (selectedDates, dateStr, fp) {
                fp.input.classList.add('gf-date-input');
                if (selectedDates.length) fp.input.value = fmtRange(selectedDates);
            },
        });
    }

    // Row click via data-href
    document.querySelectorAll('tr.grn-row').forEach(function (row) {
        row.addEventListener('click', function (e) {
            if (e.target.closest('a, button, form')) return;
            const href = row.dataset.href;
            if (href) window.location = href;
        });
    });

    // Infinite scroll
    let nextPageUrl = @json($receipts->nextPageUrl());
    const tableBody = document.getElementById('grn-table-body');
    const loadingEl = document.getElementById('grn-loading');
    const triggerEl = document.getElementById('grn-load-more-trigger');
    let isLoading = false;

    async function loadMore() {
        if (!nextPageUrl || isLoading) return;
        isLoading = true;
        if (loadingEl) loadingEl.classList.remove('d-none');
        try {
            const res = await fetch(nextPageUrl, {
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
            });
            const data = await res.json();
            if (data.html && tableBody) tableBody.insertAdjacentHTML('beforeend', data.html);
            nextPageUrl = data.next_page_url;
            if (!nextPageUrl && observer) observer.unobserve(triggerEl);
        } catch (e) {
            console.error(e);
        } finally {
            if (loadingEl) loadingEl.classList.add('d-none');
            isLoading = false;
        }
    }

    const observer = new IntersectionObserver(entries => {
        entries.forEach(e => { if (e.isIntersecting && nextPageUrl) loadMore(); });
    }, { rootMargin: '200px', threshold: 0.1 });

    if (nextPageUrl && triggerEl) observer.observe(triggerEl);
});
</script>
@endpush
