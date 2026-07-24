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

@section('content')
<x-index-layout title="Purchase Orders" subtitle="Daftar pemesanan barang.">
    @if (isset($summary))
        <x-slot name="kpis">
            <span class="kpi"><span class="lbl">Total PO</span><span class="val mono">{{ $summary->total_orders ?? 0 }}</span></span>
            <span class="kpi"><span class="lbl">Draft</span><span class="val mono">{{ $summary->draft_count ?? 0 }}</span></span>
            <span class="kpi"><span class="lbl">Approved</span><span class="val mono">{{ $summary->approved_count ?? 0 }}</span></span>
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
                <div class="d-flex flex-wrap gap-2 align-items-center">
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

            <div class="d-flex flex-wrap gap-2 align-items-center mb-3" style="background: rgba(148,163,184,.08); border: 1px dashed rgba(148,163,184,.35); padding: .75rem .85rem; border-radius: 10px;">
                <div style="font-size: .8rem; font-weight: 600; color: #64748b; margin-right: .5rem;">Filter Tanggal:</div>
                <x-date-range-picker 
                    :date-from="request('from_date')" 
                    :date-to="request('to_date')" 
                    :period="request('period', 'all')" 
                    form-id="po-filter-form"
                    name-from="from_date"
                    name-to="to_date"
                />

                @if (request()->filled('q') || request()->filled('supplier_id') || request()->filled('status') || request()->filled('pay_status') || request()->filled('from_date') || request()->filled('to_date'))
                    <a href="{{ route('purchasing.purchase_orders.index') }}" class="btn btn-sm btn-ship-outline btn-pill ms-auto" style="height: 32px; display: flex; align-items: center; background: #fff;">
                        <i class="bi bi-x me-1"></i>Reset Semua Filter
                    </a>
                @endif
            </div>
        </form>
    </x-slot>

    @if (isset($summary) && !empty($summary->last_date))
        <x-slot name="summary">
            PO terakhir dibuat: <strong class="mono">{{ id_date($summary->last_date) }}</strong>
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
                    Dokumen & Tanggal {{ $sortIcon('date') }}
                </a>
            </th>
            <th style="position: sticky; top: 0; z-index: 10; background: var(--card, #fff);">
                <a href="{{ $sortUrl('supplier_id') }}" class="th-sort {{ $sortCol === 'supplier_id' ? 'active' : '' }}">
                    Supplier {{ $sortIcon('supplier_id') }}
                </a>
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
                'approved' => 'Approved',
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
                default => 'Belum bayar',
            };

            $actionRoute = $uiStatus === 'draft'
                ? route('purchasing.purchase_orders.edit', $order->id)
                : route('purchasing.purchase_orders.show', $order->id);
            $actionLabel = $uiStatus === 'draft' ? 'Lanjutkan' : 'Detail';
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
                        
                        <div class="small mono mt-1 d-flex align-items-center gap-2" style="color:var(--shp-text); font-weight: 500; line-height: 1.3;">
                            <span>{{ id_date($order->date) }}</span>
                            @if($order->updated_at)
                                <span style="font-size: .65rem; font-weight: 400; opacity: .65;">{{ $order->updated_at->format('H:i') }}</span>
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
                    </div>
                    <div class="d-flex align-items-center justify-content-end d-md-none gap-2" style="font-size: 1.05rem;">
                        <!-- PO Status -->
                        <i class="bi {{ $uiStatus === 'approved' ? 'bi-check-square-fill text-primary' : ($uiStatus === 'cancelled' ? 'bi-x-square-fill text-danger' : 'bi-file-earmark-text-fill text-muted') }}" title="{{ $statusLabel }}"></i>
                        
                        <!-- RCV Status -->
                        @if ($rcv === 'fully_received')
                            <i class="bi bi-box-seam-fill text-success" title="Masuk Gudang"></i>
                        @elseif ($rcv === 'partial')
                            <i class="bi bi-box-seam" style="color: #eab308;" title="Masuk Sebagian"></i>
                        @else
                            <i class="bi bi-box" style="color: #cbd5e1;" title="Belum Masuk"></i>
                        @endif

                        <!-- Payment Status -->
                        @if ($canSeeMoney)
                            <i class="bi {{ $ps === 'paid' ? 'bi-check-circle-fill pay-icon paid' : ($ps === 'partial' ? 'bi-pie-chart-fill pay-icon partial' : 'bi-circle pay-icon unpaid') }}" title="{{ $payLabel }}"></i>
                        @endif
                    </div>
                </div>
            </td>

            <td>
                <div class="supplier-name">{{ optional($order->supplier)->name ?? '—' }}</div>
                <div class="ship-row-meta d-md-none">
                    <span class="mono">{{ id_date($order->date) }}</span>
                </div>
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
                    <a href="{{ route('purchasing.purchase_orders.show', $order->id) }}" class="btn btn-sm btn-ship-outline btn-pill">
                        Detail
                    </a>
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
