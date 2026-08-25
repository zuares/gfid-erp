@extends('layouts.app')

@section('title', 'Daftar Purchase Order')

@php
    $user = auth()->user();
    $canSeeMoney = $user?->canSeePurchasePrices() ?? false;
    $sortCol ??= 'date';
    $sortDir ??= 'desc';
    $sortUrl = fn(string $col) => request()->fullUrlWithQuery([
        'sort' => $col,
        'dir'  => ($sortCol === $col && $sortDir === 'asc') ? 'desc' : 'asc',
        'page' => 1,
    ]);
    $sortIcon = fn(string $col) => $sortCol === $col
        ? ($sortDir === 'asc' ? '↑' : '↓')
        : '↕';

    $statusOptions = [
        '' => 'Semua Status',
        'draft' => 'Draft',
        'approved' => 'Posted',
        'cancelled' => 'Cancelled',
    ];

    $payStatusOptions = [
        '' => 'Semua Bayar',
        'unpaid' => 'Unpaid',
        'partial' => 'Partial',
        'paid' => 'Paid',
        'overpaid' => 'Piutang',
    ];

    $hasPoFilters = request()->filled('q')
        || request()->filled('supplier_id')
        || request()->filled('status')
        || request()->filled('pay_status')
        || request()->filled('from_date')
        || request()->filled('to_date')
        || (request()->filled('period') && request('period') !== 'all');

    $payBadge = function ($s) {
        return match ((string) $s) {
            'paid' => 'badge-pay badge-pay-paid',
            'partial' => 'badge-pay badge-pay-partial',
            'overpaid' => 'badge-pay badge-pay-overpaid',
            default => 'badge-pay badge-pay-unpaid',
        };
    };
@endphp

@push('head')
<style>
    .po-date-row {
        display: flex;
        align-items: baseline;
        flex-wrap: wrap;
        gap: .45rem;
        color: var(--shp-text, #334155);
        font-weight: 650;
        line-height: 1.35;
    }

    .po-date-row .po-date-time {
        color: #94a3b8;
        font-size: .68rem;
        font-weight: 500;
    }

    .po-mobile-statuses {
        display: flex;
        flex-wrap: wrap;
        gap: .35rem;
        margin-top: .5rem;
    }

    .po-mobile-status {
        display: inline-flex;
        align-items: center;
        min-height: 24px;
        padding: .18rem .5rem;
        border: 1px solid rgba(148, 163, 184, .28);
        border-radius: 999px;
        color: #64748b;
        background: rgba(148, 163, 184, .08);
        font-size: .68rem;
        font-weight: 700;
        line-height: 1;
        white-space: nowrap;
    }

    .po-item-cell {
        min-width: 190px;
    }

    .po-unit-cell {
        min-width: 100px;
        vertical-align: top;
    }

    .po-item-line {
        display: flex;
        align-items: baseline;
        gap: .35rem;
        min-width: 0;
        line-height: 1.35;
    }

    .po-item-line + .po-item-line {
        margin-top: .2rem;
    }

    .po-item-code {
        flex: 0 1 auto;
        min-width: 0;
        overflow: hidden;
        color: #334155;
        font-family: ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;
        font-size: .76rem;
        font-weight: 800;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .po-item-qty {
        flex: 0 0 auto;
        color: #64748b;
        font-size: .72rem;
        font-variant-numeric: tabular-nums;
        white-space: nowrap;
    }

    .po-item-more {
        color: #94a3b8;
        font-size: .7rem;
        font-weight: 700;
    }

    .po-mobile-items {
        display: none;
        margin-top: .45rem;
        padding-top: .4rem;
        border-top: 1px dashed rgba(148,163,184,.25);
    }

    .po-mobile-items-label {
        display: block;
        margin-bottom: .2rem;
        color: #94a3b8;
        font-size: .63rem;
        font-weight: 800;
        letter-spacing: .06em;
        text-transform: uppercase;
    }

    body[data-theme="dark"] .po-item-code,
    body[data-theme="dark"] .po-unit-value {
        color: #e2e8f0;
    }

    body[data-theme="dark"] .po-item-qty,
    body[data-theme="dark"] .po-name {
        color: #94a3b8;
    }

    .po-mobile-status.is-approved,
    .po-mobile-status.is-received {
        color: #15803d;
        background: rgba(22, 163, 74, .08);
        border-color: rgba(22, 163, 74, .2);
    }

    .po-mobile-status.is-cancelled {
        color: #b91c1c;
        background: rgba(239, 68, 68, .08);
        border-color: rgba(239, 68, 68, .2);
    }

    .po-mobile-status.is-partial {
        color: #a16207;
        background: rgba(234, 179, 8, .1);
        border-color: rgba(234, 179, 8, .25);
    }

    .po-mobile-status.is-overpaid {
        color: #6d28d9;
        background: rgba(124, 58, 237, .1);
        border-color: rgba(124, 58, 237, .25);
    }

    .po-filter-main {
        gap: .45rem !important;
    }

    .po-date-filter {
        display: flex;
        align-items: center;
        gap: .7rem;
        margin-bottom: 1rem;
        padding: .6rem .75rem;
        background: var(--card, #fff);
        border: 1px solid rgba(148, 163, 184, .18);
        border-radius: 10px;
        box-shadow: 0 2px 8px rgba(15, 23, 42, .025);
    }

    body[data-theme="dark"] .po-date-filter {
        background: rgba(15, 23, 42, .98);
        border-color: rgba(51, 65, 85, .6);
    }

    .po-date-filter-label {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        flex: 0 0 auto;
        color: #64748b;
        font-size: .7rem;
        font-weight: 800;
        letter-spacing: .06em;
        text-transform: uppercase;
        white-space: nowrap;
    }

    .po-date-filter-label i {
        color: #94a3b8;
        font-size: .85rem;
    }

    .po-date-filter-controls {
        display: flex;
        align-items: center;
        gap: .55rem;
        flex: 1 1 auto;
        min-width: 0;
    }

    .po-date-filter-controls .date-section {
        flex: 1 1 auto;
        min-width: 0;
    }

    .po-reset-all-filters {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 32px;
        margin-left: auto;
        padding-inline: .7rem;
        border-radius: 8px !important;
        background: transparent !important;
        white-space: nowrap;
    }

    @media (max-width: 768px) {
        .po-filter-main {
            display: grid !important;
            grid-template-columns: 1fr;
            gap: .45rem !important;
        }

        .po-filter-main > input,
        .po-filter-main > select {
            width: 100% !important;
            max-width: none !important;
            min-height: 40px;
        }

        .po-date-filter {
            align-items: stretch;
            flex-direction: column;
            gap: .4rem;
            padding: .6rem;
        }

        .po-date-filter-label {
            min-height: 22px;
            font-size: .68rem;
            letter-spacing: .04em;
        }

        .po-date-filter-controls {
            flex-direction: column;
            align-items: stretch;
            gap: .45rem;
        }

        .po-date-filter-controls .date-section {
            width: 100% !important;
            border-radius: 9px;
        }

        .po-date-filter-controls .ds-presets {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }

        .po-date-filter-controls .ds-preset-btn {
            min-height: 38px;
            height: 38px !important;
            padding-inline: .2rem;
            font-size: .68rem !important;
        }

        .po-date-filter-controls .rts-date-picker.flatpickr-input {
            min-height: 40px;
            height: 40px !important;
            font-size: .8rem !important;
        }

        .po-date-filter-controls .ds-clear {
            min-height: 36px;
            height: 36px;
        }

        .po-reset-all-filters {
            width: 100%;
            margin-left: 0;
            min-height: 40px;
        }

        .po-mobile-items {
            display: block;
        }

        .po-mobile-items .po-item-line {
            max-width: 100%;
        }
    }
</style>
@endpush

@section('content')
<x-index-layout title="Purchase Orders" subtitle="Daftar pemesanan barang.">
    @if (isset($summary))
        <x-slot name="kpis">
            <span class="kpi"><span class="lbl">Total PO</span><span class="val mono">{{ $summary->total_orders ?? 0 }}</span></span>
            <span class="kpi"><span class="lbl">Draft</span><span class="val mono">{{ $summary->draft_count ?? 0 }}</span></span>
            <span class="kpi"><span class="lbl">Posted</span><span class="val mono">{{ $summary->approved_count ?? 0 }}</span></span>
            @if ($canSeeMoney)
                <span class="kpi" style="background: rgba(22, 163, 74, 0.05); border-color: rgba(22, 163, 74, 0.2);"><span class="lbl" style="color:#15803d;">Total Nilai</span><span class="val mono" style="color:#16a34a;">Rp {{ number_format($summary->total_grand_total ?? 0, 0, ',', '.') }}</span></span>
            @endif
        </x-slot>
    @endif

    @if ($user && in_array($user->role, ['owner', 'admin']))
        <x-slot name="actions">
            <a href="{{ route('purchasing.purchase_orders.create') }}" class="btn btn-sm btn-ship-primary btn-pill">
                <i class="bi bi-plus-lg me-1"></i> PO Baru
            </a>
        </x-slot>
    @endif

    <x-slot name="filters">
        <form id="po-filter-form" method="GET" action="{{ route('purchasing.purchase_orders.index') }}">
            <div class="filter-bar mb-3">
                <div class="po-filter-main d-flex flex-wrap align-items-center">
                    <input type="text" name="q" value="{{ request('q') }}" class="form-control form-control-sm search-input" style="max-width:200px;" placeholder="Cari PO..." autocomplete="off">

                    <select name="supplier_id" class="form-select form-select-sm po-filter-auto" style="max-width:160px;">
                        <option value="">Semua Supplier</option>
                        @foreach ($suppliers as $sup)
                            <option value="{{ $sup->id }}" @selected(request('supplier_id') == $sup->id)>{{ $sup->name }}</option>
                        @endforeach
                    </select>

                    <select name="status" class="form-select form-select-sm po-filter-auto" style="max-width:130px;">
                        @foreach ($statusOptions as $value => $label)
                            <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>

                    @if ($canSeeMoney)
                        <select name="pay_status" class="form-select form-select-sm po-filter-auto" style="max-width:130px;">
                            @foreach ($payStatusOptions as $value => $label)
                                <option value="{{ $value }}" @selected(request('pay_status') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    @endif
                </div>
            </div>

            <div class="po-date-filter">
                <div class="po-date-filter-label">
                    <i class="bi bi-calendar3"></i>
                    <span>Tanggal PO</span>
                </div>

                <div class="po-date-filter-controls">
                    <x-date-range-picker
                        :date-from="request('from_date')"
                        :date-to="request('to_date')"
                        :period="request('period', 'all')"
                        form-id="po-filter-form"
                        name-from="from_date"
                        name-to="to_date"
                    />

                    @if ($hasPoFilters)
                        <a href="{{ route('purchasing.purchase_orders.index') }}" class="btn btn-sm btn-ship-outline po-reset-all-filters">
                            <i class="bi bi-x-lg me-1"></i>Reset Filter
                        </a>
                    @endif
                </div>
            </div>
        </form>
    </x-slot>

    @if (isset($summary) && !empty($summary->last_date))
        <x-slot name="summary">
            PO terakhir dibuat: <strong>{{ id_day($summary->last_date) }}</strong>
        </x-slot>
    @endif

    @if ($orders->count() === 0)
        <x-slot name="emptyState">
            <div class="empty">Belum ada Purchase Order.</div>
        </x-slot>
    @endif

    <x-slot name="thead">
        <tr>
            <th style="width: 46px; position: sticky; top: 0; z-index: 10; background: var(--card, #fff);" class="mobile-hide">#</th>
            <th style="width: 230px; position: sticky; top: 0; z-index: 10; background: var(--card, #fff);">
                <a href="{{ $sortUrl('date') }}" class="th-sort {{ $sortCol === 'date' ? 'active' : '' }}">
                    Dokumen & Hari/Tanggal {{ $sortIcon('date') }}
                </a>
            </th>
            <th style="position: sticky; top: 0; z-index: 10; background: var(--card, #fff);">
                <a href="{{ $sortUrl('supplier_id') }}" class="th-sort {{ $sortCol === 'supplier_id' ? 'active' : '' }}">
                    Supplier {{ $sortIcon('supplier_id') }}
                </a>
            </th>
            <th class="mobile-hide" style="width: 210px; position: sticky; top: 0; z-index: 10; background: var(--card, #fff);">
                Item
            </th>
            <th class="mobile-hide" style="width: 110px; position: sticky; top: 0; z-index: 10; background: var(--card, #fff);">
                Satuan Beli
            </th>
            @if ($canSeeMoney)
                <th class="text-end" style="width: 130px; position: sticky; top: 0; z-index: 10; background: var(--card, #fff);">
                    <a href="{{ $sortUrl('grand_total') }}" class="th-sort {{ $sortCol === 'grand_total' ? 'active' : '' }}">
                        Total Rp {{ $sortIcon('grand_total') }}
                    </a>
                </th>
            @endif
            <th style="width: 120px; position: sticky; top: 0; z-index: 10; background: var(--card, #fff);" class="mobile-hide">Status PO</th>
            <th style="width: 150px; position: sticky; top: 0; z-index: 10; background: var(--card, #fff);">Status Pembayaran</th>
            <th style="width: 90px; position: sticky; top: 0; z-index: 10; background: var(--card, #fff);" class="mobile-hide"></th>
        </tr>
    </x-slot>

    @foreach ($orders as $order)
        @php
            $grnCount = $order->purchaseReceipts?->count() ?? 0;
            $ps = (string) ($order->payment_status ?? 'unpaid');
            $payBadgeClass = $payBadge($ps);
            $rcv = $order->received_status ?? 'not_received';
            $rcvClass = match($rcv) {
                'fully_received' => 'badge-rcv badge-rcv-full',
                'partial'        => 'badge-rcv badge-rcv-partial',
                default          => 'badge-rcv badge-rcv-none',
            };
            
            $uiStatus = $order->status;
            $statusClass = match ($uiStatus) {
                'approved' => 'st-approved',
                'cancelled' => 'st-cancelled',
                default => 'st-draft',
            };
            $statusLabel = match ((string) $uiStatus) {
                'approved' => 'Posted',
                'cancelled' => 'Cancelled',
                default => 'Draft',
            };
            $rcvLabel = match($rcv) {
                'fully_received' => 'Masuk Gudang',
                'partial' => 'Masuk Sebagian',
                default => 'Belum Masuk',
            };
            $payLabel = match($ps) {
                'paid' => 'Lunas',
                'partial' => 'Bayar sebagian',
                'overpaid' => 'Piutang supplier',
                default => 'Belum bayar',
            };

            $orderLines = $order->lines ?? collect();
            $visibleOrderLines = $orderLines->take(2);
            $remainingOrderLines = max(0, $orderLines->count() - $visibleOrderLines->count());

            // Klik baris selalu membuka detail; edit hanya lewat tombol pensil.
            $actionRoute = route('purchasing.purchase_orders.show', $order->id);
        @endphp

        <tr class="po-row" data-href="{{ $actionRoute }}" style="cursor: pointer;">
            <td class="text-muted small mobile-hide mono">
                {{ ($orders->currentPage() - 1) * $orders->perPage() + $loop->iteration }}
            </td>

            <td>
                <div class="ship-row-main">
                    <div style="min-width:0;">
                        <a class="code-link mono text-muted d-block" style="font-size: 0.72rem; text-decoration: none;" href="{{ $actionRoute }}">
                            {{ $order->code }}
                        </a>
                        
                        <div class="small mono mt-1 po-date-row">
                            <span class="po-date">{{ id_day($order->date) }}</span>
                            @if($order->updated_at)
                                <span class="po-date-time">· diperbarui {{ id_time($order->updated_at) }}</span>
                            @endif
                        </div>

                        <div class="muted mt-1" style="font-size: .74rem;">
                            @php
                                $subParts = [];
                                if ($canSeeMoney) $subParts[] = $payLabel;
                                if ($grnCount > 0) $subParts[] = 'GRN ' . $grnCount;
                                if (!empty($order->purchase_request_id)) $subParts[] = 'PR';
                                if (!empty($order->due_date) && $canSeeMoney) $subParts[] = 'JT: ' . id_date($order->due_date);
                            @endphp
                            {{ implode(' · ', $subParts) }}
                        </div>

                        <div class="po-mobile-items">
                            <span class="po-mobile-items-label">Item &amp; satuan</span>
                            @forelse ($visibleOrderLines as $line)
                                <div class="po-item-line">
                                    <span class="po-item-code">{{ $line->item?->code ?? 'Item #' . ($line->item_id ?? '-') }}</span>
                                    <span class="po-item-qty">{{ decimal_id($line->qty, 2) }} {{ $line->effectivePurchaseUnit() }}</span>
                                </div>
                            @empty
                                <span class="text-muted" style="font-size:.72rem;">Belum ada item</span>
                            @endforelse
                            @if ($remainingOrderLines > 0)
                                <span class="po-item-more">+{{ $remainingOrderLines }} item lainnya</span>
                            @endif
                        </div>
                    </div>
                    <div class="po-mobile-statuses d-md-none" aria-label="Ringkasan status PO">
                        <span class="po-mobile-status {{ $uiStatus === 'approved' ? 'is-approved' : ($uiStatus === 'cancelled' ? 'is-cancelled' : '') }}">
                            {{ $statusLabel }}
                        </span>
                        <span class="po-mobile-status {{ $rcv === 'fully_received' ? 'is-received' : ($rcv === 'partial' ? 'is-partial' : '') }}">
                            {{ $rcvLabel }}
                        </span>
                        @if ($canSeeMoney)
                            <span class="po-mobile-status {{ $ps === 'paid' ? 'is-approved' : ($ps === 'partial' ? 'is-partial' : ($ps === 'overpaid' ? 'is-overpaid' : '')) }}">
                                {{ $payLabel }}
                            </span>
                        @endif
                    </div>
                </div>
            </td>

            <td>
                <div class="supplier-name">{{ optional($order->supplier)->name ?? '—' }}</div>
            </td>

            <td class="po-item-cell mobile-hide">
                @forelse ($visibleOrderLines as $line)
                    <div class="po-item-line" title="{{ $line->item?->name ?? '' }}">
                        <span class="po-item-code">{{ $line->item?->code ?? 'Item #' . ($line->item_id ?? '-') }}</span>
                        <span class="po-name">{{ $line->item?->name ?? '-' }}</span>
                    </div>
                @empty
                    <span class="text-muted" style="font-size:.74rem;">Belum ada item</span>
                @endforelse
                @if ($remainingOrderLines > 0)
                    <div class="po-item-more">+{{ $remainingOrderLines }} item lainnya</div>
                @endif
            </td>

            <td class="po-unit-cell mobile-hide">
                @forelse ($visibleOrderLines as $line)
                    <div class="po-item-line">
                        <span class="po-item-qty">{{ decimal_id($line->qty, 2) }} {{ $line->effectivePurchaseUnit() }}</span>
                    </div>
                @empty
                    <span class="text-muted" style="font-size:.74rem;">—</span>
                @endforelse
                @if ($remainingOrderLines > 0)
                    <div class="po-item-more">+{{ $remainingOrderLines }} lainnya</div>
                @endif
            </td>

            @if($canSeeMoney)
                <td class="text-end mobile-hide" style="white-space: nowrap;">
                    <span class="fw-semibold mono">Rp {{ number_format($order->grand_total, 0, ',', '.') }}</span>
                </td>
            @endif

            <td class="mobile-hide">
                @php
                    $poBadgeStyle = match ($uiStatus) {
                        'approved' => 'background: rgba(14, 165, 233, 0.1); color: #0ea5e9; border: 1px solid rgba(14, 165, 233, 0.2);',
                        'cancelled' => 'background: rgba(239, 68, 68, 0.1); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.2);',
                        default => 'background: rgba(100, 116, 139, 0.1); color: #64748b; border: 1px solid rgba(100, 116, 139, 0.2);',
                    };
                @endphp
                <span class="badge py-1 px-2" style="font-weight: 600; font-size: .75rem; border-radius: 6px; {{ $poBadgeStyle }}">{{ $statusLabel }}</span>
            </td>

            <td class="mobile-hide">
                @if ($canSeeMoney)
                    <span class="badge {{ $payBadge($ps) }} py-1 px-2" style="font-weight: 600; font-size: .75rem; border-radius: 6px;">{{ $payLabel }}</span>
                @else
                    <span class="text-muted" style="font-size: .8rem;">-</span>
                @endif
            </td>

            <td class="text-end ship-row-action mobile-hide">
                <div class="d-inline-flex gap-1 justify-content-end">
                    @if ($uiStatus === 'draft')
                        <a href="{{ route('purchasing.purchase_orders.edit', $order->id) }}" class="btn btn-sm btn-ship-outline btn-pill px-2" title="Edit">
                            <i class="bi bi-pencil"></i>
                        </a>
                        @if(!$order->isLocked())
                            <form action="{{ route('purchasing.purchase_orders.destroy', $order->id) }}" method="POST" onsubmit="return confirm('Hapus PO ini?');" class="d-inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger btn-pill px-2" title="Hapus">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        @endif
                    @endif
                </div>
            </td>
        </tr>
    @endforeach

    <x-slot name="pagination">
        @if (method_exists($orders, 'links'))
            {{ $orders->links() }}
        @endif
    </x-slot>

</x-index-layout>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('po-filter-form');
    if (!form) return;

    form.querySelectorAll('select.po-filter-auto').forEach(function (el) {
        el.addEventListener('change', function () { form.submit(); });
    });
});
</script>
@endpush
@endsection
