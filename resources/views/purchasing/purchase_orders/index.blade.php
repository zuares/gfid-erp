@extends('layouts.app')

@section('title', 'Daftar Purchase Order')

@php
    $user = auth()->user();
    $canSeeMoney = $user?->isOwner() ?? false;
@endphp

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
            padding: .8rem .9rem;
            margin-bottom: .85rem;
        }

        .card-table {
            background: var(--card);
            border-radius: 14px;
            border: 1px solid var(--line);
            overflow: hidden;
        }

        .table thead th {
            background: color-mix(in srgb, var(--card) 90%, var(--bg) 10%);
            border-bottom-color: var(--line);
            font-size: .68rem;
            text-transform: uppercase;
            letter-spacing: .06em;
            color: var(--muted);
            white-space: nowrap;
            padding: .52rem .65rem;
        }

        .table tbody td {
            vertical-align: middle;
            font-size: .83rem;
            padding: .58rem .65rem;
            border-bottom-color: var(--line);
        }

        .table tbody tr:last-child td { border-bottom: none; }

        .po-row:hover { background: rgba(59,130,246,.04); }

        .mono {
            font-variant-numeric: tabular-nums;
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, "Liberation Mono";
        }

        /* PO Status badge */
        .badge-status {
            border-radius: 999px;
            font-size: .7rem;
            padding: .1rem .6rem;
            border: 1px solid transparent;
            white-space: nowrap;
        }

        .badge-draft {
            background: rgba(148, 163, 184, .12);
            color: #64748b;
            border-color: rgba(148, 163, 184, .5);
        }

        .badge-approved {
            background: rgba(22, 163, 74, .12);
            color: #15803d;
            border-color: rgba(22, 163, 74, .6);
        }

        .badge-cancelled {
            background: rgba(220, 38, 38, .08);
            color: #b91c1c;
            border-color: rgba(220, 38, 38, .6);
        }

        /* Payment status badge */
        .badge-pay {
            border-radius: 999px;
            font-size: .7rem;
            padding: .1rem .55rem;
            border: 1px solid rgba(148, 163, 184, .45);
            background: rgba(148, 163, 184, .10);
            color: #64748b;
            white-space: nowrap;
        }

        .badge-pay-paid {
            border-color: rgba(22, 163, 74, .55);
            background: rgba(22, 163, 74, .12);
            color: #15803d;
        }

        .badge-pay-partial {
            border-color: rgba(234, 179, 8, .55);
            background: rgba(234, 179, 8, .12);
            color: #a16207;
        }

        .badge-pay-unpaid {
            border-color: rgba(148, 163, 184, .45);
            background: rgba(148, 163, 184, .10);
            color: #64748b;
        }

        .badge-grn {
            border-radius: 999px;
            font-size: .65rem;
            padding: .05rem .45rem;
            margin-left: .25rem;
            background: rgba(59, 130, 246, .08);
            color: #1d4ed8;
            border: 1px solid rgba(59, 130, 246, .5);
            white-space: nowrap;
        }

        /* PR-E: badge "Dari PR" di PO index */
        .badge-pr-ref {
            border-radius: 999px;
            font-size: .62rem;
            padding: .04rem .42rem;
            margin-left: .2rem;
            background: rgba(99,102,241,.08);
            color: #4f46e5;
            border: 1px solid rgba(99,102,241,.4);
            white-space: nowrap;
            text-decoration: none;
            display: inline-block;
        }
        .badge-pr-ref:hover { background: rgba(99,102,241,.16); color: #4f46e5; }

        /* received_status badge */
        .badge-rcv {
            border-radius: 999px;
            font-size: .65rem;
            padding: .05rem .45rem;
            border: 1px solid transparent;
            white-space: nowrap;
            display: inline-block;
        }
        .badge-rcv-none {
            background: rgba(148,163,184,.08);
            color: #94a3b8;
            border-color: rgba(148,163,184,.4);
        }
        .badge-rcv-partial {
            background: rgba(234,179,8,.10);
            color: #a16207;
            border-color: rgba(234,179,8,.5);
        }
        .badge-rcv-full {
            background: rgba(22,163,74,.10);
            color: #15803d;
            border-color: rgba(22,163,74,.5);
        }

        .row-draft {
            background: rgba(248, 250, 252, 0.9);
        }

        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: .6rem;
            margin-bottom: .85rem;
        }

        .kpi-cell {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 12px;
            padding: .72rem .82rem;
            min-width: 0;
        }

        .kpi-label {
            font-size: .68rem;
            text-transform: uppercase;
            letter-spacing: .06em;
            color: var(--muted);
            font-weight: 800;
            margin-bottom: .18rem;
        }

        .kpi-value {
            font-size: .95rem;
            font-weight: 850;
            line-height: 1.2;
        }

        .kpi-sub {
            font-size: .72rem;
            color: var(--muted);
            margin-top: .08rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .filter-row {
            display: grid;
            grid-template-columns: minmax(170px, 1.2fr) 140px 140px 180px auto;
            gap: .5rem;
            align-items: center;
        }

        .po-code {
            font-weight: 850;
            font-size: .85rem;
            white-space: nowrap;
        }

        .po-sub {
            font-size: .72rem;
            color: var(--muted);
            white-space: nowrap;
        }

        .supplier-code {
            font-weight: 850;
            font-size: .86rem;
            white-space: nowrap;
        }

        .table-total {
            font-size: .86rem;
            font-weight: 850;
            white-space: nowrap;
        }

        .status-stack {
            display: flex;
            gap: .3rem;
            flex-wrap: wrap;
            align-items: center;
        }

        .status-main {
            font-weight: 850;
            font-size: .78rem;
            line-height: 1.15;
        }

        .status-main.is-approved {
            color: #15803d;
        }

        .status-main.is-draft {
            color: #64748b;
        }

        .status-main.is-cancelled {
            color: #b91c1c;
        }

        .status-meta {
            margin-top: .16rem;
            color: var(--muted);
            font-size: .72rem;
            line-height: 1.2;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* ===== MOBILE ===== */
        @media (max-width: 767.98px) {
            .page-wrap {
                padding-inline: .75rem;
            }

            .card-filter {
                padding: .75rem .8rem;
            }

            .kpi-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: .5rem;
            }

            .filter-row {
                grid-template-columns: 1fr 1fr;
            }

            .filter-row .filter-search,
            .filter-row .filter-date,
            .filter-row .filter-reset {
                grid-column: 1 / -1;
            }

            .card-po-mobile {
                background: var(--card);
                border-radius: 12px;
                border: 1px solid var(--line);
                padding: .75rem .85rem;
                margin-bottom: .6rem;
            }

            .card-po-mobile h6 {
                font-size: .92rem;
                margin-bottom: .25rem;
            }

            .card-po-mobile .meta {
                font-size: .75rem;
                color: var(--muted);
            }

            .card-po-mobile .meta span+span::before {
                content: "•";
                margin-inline: .35rem;
                opacity: .65;
            }

            .card-po-mobile .amount {
                font-size: .95rem;
            }

            .card-po-mobile .actions a {
                font-size: .75rem;
                padding-inline: .45rem;
                padding-block: .2rem;
            }

            .kpi-value {
                font-size: .88rem;
            }
        }
    </style>
@endpush

@section('content')
    @php
        $user = auth()->user();

        $statusOptions = [
            '' => 'Semua Status',
            'draft' => 'Draft',
            'approved' => 'Approved',
            'cancelled' => 'Cancelled',
        ];

        $payStatusOptions = [
            '' => 'Semua Bayar',
            'unpaid' => 'Unpaid',
            'partial' => 'Partial',
            'paid' => 'Paid',
        ];

        $payBadge = function ($s) {
            return match ((string) $s) {
                'paid' => 'badge-pay badge-pay-paid',
                'partial' => 'badge-pay badge-pay-partial',
                default => 'badge-pay badge-pay-unpaid',
            };
        };
    @endphp

    <div class="page-wrap py-3">

        {{-- HEADER --}}
        <div class="d-flex justify-content-between align-items-center gap-3 mb-3 flex-wrap">
            <div>
                <h2 class="mb-0 lh-1" style="font-size:1.35rem;">Purchase Orders</h2>
                <div class="text-muted small mt-1">Daftar PO</div>
            </div>
            <div>
                @if ($user && in_array($user->role, ['owner', 'admin']))
                    <a href="{{ route('purchasing.purchase_orders.create') }}" class="btn btn-primary btn-sm">
                        + PO
                    </a>
                @endif
            </div>
        </div>

        {{-- FLASH --}}
        @if (session('success'))
            <div class="alert alert-success py-2 small">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger py-2 small">{{ session('error') }}</div>
        @endif

        {{-- KPI --}}
        @if (isset($summary))
            <div class="kpi-grid">
                <div class="kpi-cell">
                    <div class="kpi-label">Total PO</div>
                    <div class="kpi-value mono">{{ $summary->total_orders }}</div>
                    @if ($summary->last_date)
                        <div class="kpi-sub">Terakhir {{ id_date($summary->last_date) }}</div>
                    @endif
                </div>
                <div class="kpi-cell">
                    <div class="kpi-label">Draft</div>
                    <div class="kpi-value mono">{{ $summary->draft_count }}</div>
                    <div class="kpi-sub">Belum approve</div>
                </div>
                <div class="kpi-cell">
                    <div class="kpi-label">Approved</div>
                    <div class="kpi-value mono">{{ $summary->approved_count }}</div>
                    <div class="kpi-sub">Siap operasional</div>
                </div>
                @if ($canSeeMoney)
                    <div class="kpi-cell">
                        <div class="kpi-label">Nilai PO</div>
                        <div class="kpi-value mono" style="font-size:.86rem;">{{ rupiah($summary->total_grand_total) }}</div>
                        @if (($summary->cancelled_count ?? 0) > 0)
                            <div class="kpi-sub">Cancel {{ $summary->cancelled_count }}</div>
                        @endif
                    </div>
                @else
                    <div class="kpi-cell">
                        <div class="kpi-label">Cancelled</div>
                        <div class="kpi-value mono">{{ $summary->cancelled_count ?? 0 }}</div>
                    </div>
                @endif
            </div>
        @endif

        {{-- FILTER --}}
        <div class="card-filter mb-3">
            <form id="po-filter-form" method="GET" action="{{ route('purchasing.purchase_orders.index') }}">
                <input type="hidden" name="from_date" id="po-from-date" value="{{ request('from_date') }}">
                <input type="hidden" name="to_date"   id="po-to-date"   value="{{ request('to_date') }}">

                <div class="filter-row">
                    <input type="text" name="supplier_search"
                        value="{{ request('supplier_search') }}"
                        placeholder="Cari supplier"
                        class="form-control form-control-sm filter-search"
                        autocomplete="off" />

                    <select name="status" class="form-select form-select-sm po-filter-auto">
                        @foreach ($statusOptions as $value => $label)
                            <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>

                    @if ($canSeeMoney)
                        <select name="pay_status" class="form-select form-select-sm po-filter-auto">
                            @foreach ($payStatusOptions as $value => $label)
                                <option value="{{ $value }}" @selected(request('pay_status') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    @endif

                    {{-- Single flatpickr range input --}}
                    @php
                        $idMonths = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
                        $rangeDisplay = '';
                        if (request('from_date') && request('to_date')) {
                            try {
                                $f = \Carbon\Carbon::parse(request('from_date'));
                                $t = \Carbon\Carbon::parse(request('to_date'));
                                $rangeDisplay = $f->day . ' ' . $idMonths[$f->month-1]
                                    . ' – ' . $t->day . ' ' . $idMonths[$t->month-1] . ' ' . $t->year;
                            } catch (\Exception $e) {
                                $rangeDisplay = request('from_date') . ' – ' . request('to_date');
                            }
                        } elseif (request('from_date')) {
                            try {
                                $f = \Carbon\Carbon::parse(request('from_date'));
                                $rangeDisplay = $f->day . ' ' . $idMonths[$f->month-1] . ' ' . $f->year;
                            } catch (\Exception $e) {
                                $rangeDisplay = request('from_date');
                            }
                        }
                    @endphp
                    <input type="text" id="po-date-range" value="{{ $rangeDisplay }}"
                        placeholder="Tanggal" autocomplete="off"
                        class="form-control form-control-sm filter-date" style="cursor:pointer;"
                        data-gf-date="off" readonly />

                    @if (request()->filled('supplier_search') || request()->filled('supplier_id') || request()->filled('status') || request()->filled('pay_status') || request()->filled('from_date') || request()->filled('to_date'))
                        <a href="{{ route('purchasing.purchase_orders.index') }}"
                           class="btn btn-sm btn-outline-secondary filter-reset" style="font-size:.78rem;padding:.25rem .65rem;">
                            Reset
                        </a>
                    @endif
                </div>
            </form>
        </div>

        {{-- DESKTOP TABLE --}}
        <div class="card-table d-none d-md-block">
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead>
                        <tr>
                            <th style="width:18%;">PO</th>
                            <th>Supplier</th>
                            @if ($canSeeMoney)
                                <th style="width:14%;" class="text-end">Total</th>
                            @endif
                            <th style="width:25%;">Status</th>
                            <th style="width:4%;"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($orders as $order)
                            @php
                                $poBadgeClass = match ($order->status) {
                                    'approved' => 'badge-status badge-approved',
                                    'cancelled' => 'badge-status badge-cancelled',
                                    default => 'badge-status badge-draft',
                                };
                                $rowClass = $order->status === 'draft' ? 'row-draft' : '';
                                $grnCount = $order->purchaseReceipts?->count() ?? 0;
                                $ps = (string) ($order->payment_status ?? 'unpaid');
                                $payBadgeClass = $payBadge($ps);
                                $rcv = $order->received_status ?? 'not_received';
                                $rcvClass = match($rcv) {
                                    'fully_received' => 'badge-rcv badge-rcv-full',
                                    'partial'        => 'badge-rcv badge-rcv-partial',
                                    default          => 'badge-rcv badge-rcv-none',
                                };
                                $statusMainClass = match ($order->status) {
                                    'approved' => 'status-main is-approved',
                                    'cancelled' => 'status-main is-cancelled',
                                    default => 'status-main is-draft',
                                };
                                $statusLabel = match ((string) $order->status) {
                                    'approved' => 'Approved',
                                    'cancelled' => 'Cancelled',
                                    'closed' => 'Closed',
                                    default => 'Draft',
                                };
                                $rcvLabel = match($rcv) {
                                    'fully_received' => 'Terima lengkap',
                                    'partial' => 'Terima sebagian',
                                    default => 'Belum terima',
                                };
                                $payLabel = match($ps) {
                                    'paid' => 'Lunas',
                                    'partial' => 'Bayar sebagian',
                                    default => 'Belum bayar',
                                };
                            @endphp

                            <tr class="{{ $rowClass }} po-row" style="cursor:pointer;"
                                data-href="{{ route('purchasing.purchase_orders.show', $order->id) }}">
                                {{-- PO: kode + tanggal --}}
                                <td>
                                    <span class="fw-semibold mono" style="font-size:.82rem;white-space:nowrap;">
                                        {{ $order->code }}
                                    </span>
                                    <div class="text-muted mono" style="font-size:.72rem;white-space:nowrap;">{{ id_date($order->date) }}</div>
                                </td>

                                {{-- Supplier + Jenis --}}
                                <td style="max-width:220px;">
                                    <div class="supplier-code mono" title="{{ optional($order->supplier)->name ?? '' }}">
                                        {{ optional($order->supplier)->code ?? '—' }}
                                    </div>
                                    <div class="po-sub">{{ po_order_type_label($order->order_type) }}</div>
                                </td>

                                @if ($canSeeMoney)
                                    <td class="text-end mono table-total">{{ rupiah($order->grand_total) }}</td>
                                @endif

                                {{-- Status: main badge + info baris kedua --}}
                                <td>
                                    <div class="{{ $statusMainClass }}">{{ $statusLabel }}</div>
                                    @php
                                        $subParts = [];
                                        $subParts[] = $rcvLabel;
                                        if ($canSeeMoney) $subParts[] = $payLabel;
                                        if ($grnCount > 0) $subParts[] = 'GRN ' . $grnCount;
                                        if (!empty($order->purchase_request_id)) $subParts[] = 'PR';
                                        if (!empty($order->due_date) && $canSeeMoney) $subParts[] = 'JT ' . id_date($order->due_date);
                                    @endphp
                                    <div class="status-meta">{{ implode(' · ', $subParts) }}</div>
                                </td>

                                <td class="text-end">
                                    @if ($order->status === 'draft')
                                        <a href="{{ route('purchasing.purchase_orders.edit', $order->id) }}"
                                            class="text-muted" title="Edit"
                                            style="font-size:.85rem;line-height:1;">
                                            <i class="bi bi-pencil-square"></i>
                                        </a>
                                    @else
                                        <i class="bi bi-chevron-right text-muted" style="font-size:.8rem;opacity:.4;"></i>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $canSeeMoney ? 6 : 4 }}" class="text-center text-muted py-4">Belum ada Purchase Order.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="px-3 py-2">
                {{ $orders->links() }}
            </div>
        </div>

        {{-- MOBILE LIST --}}
        <div class="d-md-none">
            @forelse ($orders as $order)
                @php
                    $poBadgeClass = match ($order->status) {
                        'approved' => 'badge-status badge-approved',
                        'cancelled' => 'badge-status badge-cancelled',
                        default => 'badge-status badge-draft',
                    };

                    $grnCount = $order->purchaseReceipts?->count() ?? 0;

                    $ps = (string) ($order->payment_status ?? 'unpaid');
                    $payBadgeClass = $payBadge($ps);

                    $paid = (float) ($order->paid_amount ?? 0);
                    $grand = (float) ($order->grand_total ?? 0);
                    $bal = max(0, $grand - $paid);
                    $rcv = $order->received_status ?? 'not_received';
                    $rcvClass = match($rcv) {
                        'fully_received' => 'badge-rcv badge-rcv-full',
                        'partial'        => 'badge-rcv badge-rcv-partial',
                        default          => 'badge-rcv badge-rcv-none',
                    };
                    $statusMainClass = match ($order->status) {
                        'approved' => 'status-main is-approved',
                        'cancelled' => 'status-main is-cancelled',
                        default => 'status-main is-draft',
                    };
                    $statusLabel = match ((string) $order->status) {
                        'approved' => 'Approved',
                        'cancelled' => 'Cancelled',
                        'closed' => 'Closed',
                        default => 'Draft',
                    };
                    $rcvLabel = match($rcv) {
                        'fully_received' => 'Terima lengkap',
                        'partial' => 'Terima sebagian',
                        default => 'Belum terima',
                    };
                    $payLabel = match($ps) {
                        'paid' => 'Lunas',
                        'partial' => 'Bayar sebagian',
                        default => 'Belum bayar',
                    };
                @endphp

                <div class="card-po-mobile">
                    <div class="d-flex justify-content-between align-items-start mb-1">
                        <div>
                            <h6 class="mb-0 mono">
                                <a href="{{ route('purchasing.purchase_orders.show', $order->id) }}"
                                    class="text-decoration-none">
                                    {{ $order->code }}
                                </a>
                            </h6>
                            <div class="meta mt-1">
                                <span class="mono">{{ id_date($order->date) }}</span>
                                @if ($order->supplier)
                                    <span class="mono">{{ $order->supplier->code }}</span>
                                @endif
                                <span>{{ po_order_type_label($order->order_type) }}</span>
                            </div>
                        </div>

                        <div class="text-end">
                            <div class="{{ $statusMainClass }}">{{ $statusLabel }}</div>
                        </div>
                    </div>

                    @php
                        $mobileStatusParts = [$rcvLabel];
                        if ($grnCount > 0) $mobileStatusParts[] = 'GRN ' . $grnCount;
                        if (!empty($order->purchase_request_id)) $mobileStatusParts[] = 'PR';
                    @endphp
                    <div class="status-meta mt-2">{{ implode(' · ', $mobileStatusParts) }}</div>

                    @if ($canSeeMoney)
                        <div class="d-flex justify-content-between align-items-center mt-2">
                            <div class="text-muted meta">Bayar</div>
                            <div class="text-end">
                                <span class="{{ $payBadgeClass }}">{{ match($ps) { 'paid' => 'Lunas', 'partial' => 'Sebagian', default => 'Belum' } }}</span>
                                @if (!empty($order->due_date))
                                    <div class="text-muted meta mono mt-1">JT: {{ id_date($order->due_date) }}</div>
                                @endif
                            </div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mt-2">
                            <div>
                                <div class="text-muted meta mb-1">Total</div>
                                <div class="amount mono">{{ rupiah($order->grand_total) }}</div>
                            </div>
                            <div class="text-end">
                                <div class="text-muted meta mb-1">Sisa</div>
                                <div class="amount mono fw-semibold">{{ rupiah($bal) }}</div>
                            </div>
                        </div>
                    @endif

                    <div class="d-flex justify-content-end gap-1 mt-2 actions">
                        <a href="{{ route('purchasing.purchase_orders.show', $order->id) }}"
                            class="btn btn-outline-secondary btn-sm px-2" title="Detail">
                            <i class="bi bi-eye"></i>
                        </a>
                        @if ($order->status === 'draft')
                            <a href="{{ route('purchasing.purchase_orders.edit', $order->id) }}"
                                class="btn btn-outline-primary btn-sm px-2" title="Edit">
                                <i class="bi bi-pencil"></i>
                            </a>
                        @endif
                    </div>
                </div>
            @empty
                <div class="text-center text-muted py-3 small">Belum ada Purchase Order.</div>
            @endforelse

            <div class="mt-2">
                {{ $orders->links() }}
            </div>
        </div>
    </div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Row click via data-href (safer than inline onclick)
    document.querySelectorAll('tr.po-row').forEach(function (row) {
        row.addEventListener('click', function (e) {
            if (e.target.closest('a, button, form')) return;
            const href = row.dataset.href;
            if (href) window.location = href;
        });
    });

    const form = document.getElementById('po-filter-form');
    if (!form) return;

    // Realtime: selects auto-submit
    form.querySelectorAll('select.po-filter-auto').forEach(function (el) {
        el.addEventListener('change', function () { form.submit(); });
    });

    // Supplier text input: debounce 500ms + auto-focus
    const supplierInput = form.querySelector('input[name="supplier_search"]');
    if (supplierInput) {
        let debounceTimer;
        supplierInput.addEventListener('input', function () {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(function () { form.submit(); }, 500);
        });
        // Auto-focus dengan delay agar tidak konflik dengan event lain
        setTimeout(function () {
            supplierInput.focus();
            const len = supplierInput.value.length;
            supplierInput.setSelectionRange(len, len);
        }, 100);
    }

    // Single flatpickr range input
    const rangeInput = document.getElementById('po-date-range');
    const fromHidden = document.getElementById('po-from-date');
    const toHidden   = document.getElementById('po-to-date');

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
});
</script>
@endpush
@endsection
