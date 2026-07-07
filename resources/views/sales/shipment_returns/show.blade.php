{{-- resources/views/sales/shipment_returns/show.blade.php --}}
@extends('layouts.app')

@section('title', 'Retur ' . $shipmentReturn->code)

@push('head')
<style>
    .sr-show-page {
        max-width: 980px;
        margin: 0 auto;
        padding: .75rem .75rem 5rem;
        color: #111827;
    }

    .sr-topbar {
        position: sticky;
        top: 0;
        z-index: 20;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: .75rem;
        margin: 0 -.75rem;
        padding: .55rem .75rem;
        border-bottom: 1px solid rgba(148, 163, 184, .24);
        background: rgba(248, 250, 252, .98);
        backdrop-filter: blur(14px);
    }

    .sr-title {
        margin: 0;
        color: #111827;
        font-size: 1.05rem;
        font-weight: 900;
        font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
    }

    .sr-sub {
        color: #64748b;
        font-size: .78rem;
        font-weight: 650;
    }

    .sr-shell {
        display: grid;
        gap: .65rem;
        margin-top: .65rem;
    }

    .sr-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 38px;
        border-radius: 8px;
        padding: .45rem .85rem;
        border: 1px solid rgba(148, 163, 184, .35);
        background: #fff;
        color: #334155;
        font-size: .82rem;
        font-weight: 850;
        text-decoration: none;
        cursor: pointer;
        box-shadow: none;
    }

    .sr-btn-primary {
        border-color: #111827;
        background: #111827;
        color: #fff;
    }

    .sr-btn:disabled {
        opacity: .45;
        cursor: not-allowed;
    }

    .sr-panel {
        border: 1px solid rgba(148, 163, 184, .22);
        border-radius: 8px;
        background: #fff;
        overflow: hidden;
    }

    .sr-panel-body {
        padding: .75rem;
    }

    .sr-meta {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: .5rem;
    }

    .sr-meta-item,
    .sr-stat {
        border: 1px solid rgba(148, 163, 184, .22);
        border-radius: 8px;
        padding: .55rem .65rem;
        background: #fff;
        min-width: 0;
    }

    .sr-meta-item {
        background: #f8fafc;
    }

    .sr-meta-label,
    .sr-stat-label {
        color: #64748b;
        font-size: .68rem;
        font-weight: 850;
        text-transform: uppercase;
    }

    .sr-meta-value,
    .sr-stat-value {
        margin-top: .12rem;
        color: #111827;
        font-size: .86rem;
        font-weight: 900;
        overflow-wrap: anywhere;
    }

    .sr-stat-value {
        font-size: 1.12rem;
        font-variant-numeric: tabular-nums;
    }

    .sr-status {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        border-radius: 999px;
        padding: .2rem .58rem;
        border: 1px solid rgba(148, 163, 184, .28);
        color: #475569;
        background: rgba(148, 163, 184, .10);
        font-size: .76rem;
        font-weight: 900;
    }

    .sr-status::before {
        content: "";
        width: 7px;
        height: 7px;
        border-radius: 999px;
        background: #64748b;
    }

    .sr-status-submitted {
        color: #1d4ed8;
        background: rgba(59, 130, 246, .10);
        border-color: rgba(59, 130, 246, .28);
    }

    .sr-status-submitted::before { background: #3b82f6; }

    .sr-status-posted {
        color: #166534;
        background: rgba(34, 197, 94, .10);
        border-color: rgba(34, 197, 94, .28);
    }

    .sr-status-posted::before { background: #22c55e; }

    .sr-summary {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: .5rem;
    }

    .sr-note {
        color: #475569;
        font-size: .84rem;
        font-weight: 650;
        white-space: pre-line;
    }

    .sr-orders {
        display: grid;
        gap: .5rem;
    }

    .sr-order {
        border: 1px solid rgba(148, 163, 184, .22);
        border-radius: 8px;
        background: #fff;
        overflow: hidden;
    }

    .sr-order-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: .5rem;
        padding: .62rem .7rem;
        border-bottom: 1px solid rgba(148, 163, 184, .16);
        background: #f8fafc;
    }

    .sr-order-no {
        color: #94a3b8;
        font-size: .72rem;
        font-weight: 900;
        text-transform: uppercase;
    }

    .sr-order-code,
    .sr-item-code,
    .sr-mono {
        font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
    }

    .sr-order-code,
    .sr-item-code {
        color: #111827;
        font-weight: 950;
    }

    .sr-order-info,
    .sr-item-name {
        color: #64748b;
        font-size: .76rem;
        font-weight: 650;
    }

    .sr-order-qty,
    .sr-item-qty {
        min-width: 42px;
        text-align: center;
        border-radius: 999px;
        padding: .16rem .5rem;
        font-weight: 950;
        font-variant-numeric: tabular-nums;
    }

    .sr-order-qty {
        background: #111827;
        color: #fff;
    }

    .sr-item-list {
        display: grid;
        padding: .2rem 0;
    }

    .sr-item-row {
        display: grid;
        grid-template-columns: 28px 1fr auto;
        align-items: center;
        gap: .55rem;
        padding: .48rem .7rem;
    }

    .sr-item-num {
        color: #64748b;
        font-weight: 900;
        text-align: right;
        font-variant-numeric: tabular-nums;
    }

    .sr-item-qty {
        border: 1px solid rgba(148, 163, 184, .28);
    }

    .sr-empty {
        padding: 1.4rem .9rem;
        text-align: center;
        color: #94a3b8;
        font-size: .85rem;
        font-weight: 750;
    }

    .sr-actions {
        display: flex;
        justify-content: space-between;
        gap: .5rem;
        flex-wrap: wrap;
    }

    .sr-actions-group {
        display: flex;
        gap: .5rem;
        flex-wrap: wrap;
    }

    .sr-inline-form {
        margin: 0;
    }

    @media (max-width: 720px) {
        .sr-show-page {
            padding: .5rem .5rem 5.5rem;
        }

        .sr-topbar {
            margin: 0 -.5rem;
            padding: .5rem;
        }

        .sr-topbar > .sr-btn,
        .sr-sub {
            display: none;
        }

        .sr-meta {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .sr-summary {
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: .35rem;
        }

        .sr-panel-body {
            padding: .6rem;
        }

        .sr-meta-value {
            font-size: .8rem;
        }

        .sr-stat {
            padding: .45rem .5rem;
        }

        .sr-stat-value {
            font-size: 1rem;
        }

        .sr-item-row {
            grid-template-columns: 24px 1fr auto;
            padding: .55rem .6rem;
        }

        .sr-actions {
            position: fixed;
            left: 0;
            right: 0;
            bottom: 0;
            z-index: 30;
            display: grid;
            grid-template-columns: 1fr 1fr;
            padding: .55rem;
            border-top: 1px solid rgba(148, 163, 184, .24);
            background: rgba(248, 250, 252, .98);
            backdrop-filter: blur(14px);
        }

        .sr-actions-group,
        .sr-inline-form,
        .sr-actions .sr-btn {
            width: 100%;
        }

        .sr-actions .sr-btn {
            min-height: 46px;
        }
    }
</style>
@endpush

@section('content')
@php
    $status = $shipmentReturn->status ?? 'draft';
    $statusClass = match ($status) {
        'submitted' => 'sr-status-submitted',
        'posted' => 'sr-status-posted',
        default => '',
    };

    $lines = $shipmentReturn->lines ?? collect();
    $totalQty = (int) $lines->sum('qty');
    $groupedOrders = $shipmentReturn->orderScans->isNotEmpty()
        ? $shipmentReturn->orderScans->map(fn ($scan) => [
            'code' => ($scan->order_number ?: $scan->order_no) ?: 'MANUAL',
            'qty' => (int) $scan->items->sum(fn ($scanItem) => (int) ($scanItem->qty_scanned ?: $scanItem->qty)),
            'items' => $scan->items->map(fn ($scanItem) => [
                'code' => $scanItem->item->code ?? '-',
                'name' => $scanItem->item->name ?? '',
                'qty' => (int) ($scanItem->qty_scanned ?: $scanItem->qty),
            ])->values(),
        ])->values()
        : $lines
            ->groupBy(fn ($line) => trim((string) ($line->remarks ?: 'MANUAL')) ?: 'MANUAL')
            ->map(function ($group, $orderCode) {
                return [
                    'code' => $orderCode,
                    'qty' => (int) $group->sum('qty'),
                    'items' => $group->map(fn ($line) => [
                        'code' => $line->item->code ?? '-',
                        'name' => $line->item->name ?? '',
                        'qty' => (int) $line->qty,
                    ])->values(),
                ];
            })
            ->values();
@endphp

<div class="sr-show-page">
    <div class="sr-topbar">
        <div>
            <h1 class="sr-title">{{ $shipmentReturn->code }}</h1>
            <div class="sr-sub">Detail retur shipment</div>
        </div>
        <div style="display:flex;gap:.5rem;align-items:center;flex-wrap:wrap">
            <a href="{{ route('sales.shipment_returns.barcode', $shipmentReturn->id) }}" target="_blank" class="sr-btn">
                <i class="bi bi-upc-scan"></i> Cetak Barcode
            </a>
            <a href="{{ route('sales.shipment_returns.index') }}" class="sr-btn">Daftar</a>
        </div>
    </div>

    <div class="sr-shell">
        @if (session('status') && session('message'))
            <div class="alert alert-{{ session('status') === 'error' ? 'danger' : 'success' }} mb-0" style="border-radius:8px;font-size:.84rem;">
                {{ session('message') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger mb-0" style="border-radius:8px;font-size:.84rem;">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <div class="sr-panel">
            <div class="sr-panel-body">
                <div class="sr-meta">
                    <div class="sr-meta-item">
                        <div class="sr-meta-label">Status</div>
                        <div class="sr-meta-value">
                            <span class="sr-status {{ $statusClass }}">{{ ucfirst($status) }}</span>
                        </div>
                    </div>
                    <div class="sr-meta-item">
                        <div class="sr-meta-label">Marketplace</div>
                        <div class="sr-meta-value">{{ $shipmentReturn->store->code ?? '-' }} - {{ $shipmentReturn->store->name ?? '-' }}</div>
                    </div>
                    <div class="sr-meta-item">
                        <div class="sr-meta-label">Tanggal</div>
                        <div class="sr-meta-value">{{ optional($shipmentReturn->date)->format('d M Y') ?: '-' }}</div>
                    </div>
                    <div class="sr-meta-item">
                        <div class="sr-meta-label">Shipment Asal</div>
                        <div class="sr-meta-value">{{ $shipmentReturn->shipment->code ?? 'Manual' }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="sr-summary">
            <div class="sr-stat">
                <div class="sr-stat-label">Pesanan</div>
                <div class="sr-stat-value">{{ number_format($groupedOrders->count(), 0, ',', '.') }}</div>
            </div>
            <div class="sr-stat">
                <div class="sr-stat-label">Item</div>
                <div class="sr-stat-value">{{ number_format($lines->count(), 0, ',', '.') }}</div>
            </div>
            <div class="sr-stat">
                <div class="sr-stat-label">Qty</div>
                <div class="sr-stat-value">{{ number_format($totalQty, 0, ',', '.') }}</div>
            </div>
        </div>

        @if ($shipmentReturn->reason || $shipmentReturn->notes || $shipmentReturn->submitted_at || $shipmentReturn->posted_at)
            <div class="sr-panel">
                <div class="sr-panel-body">
                    <div class="sr-meta">
                        @if ($shipmentReturn->reason)
                            <div class="sr-meta-item">
                                <div class="sr-meta-label">Alasan</div>
                                <div class="sr-meta-value">{{ $shipmentReturn->reason }}</div>
                            </div>
                        @endif
                        @if ($shipmentReturn->submitted_at)
                            <div class="sr-meta-item">
                                <div class="sr-meta-label">Submitted</div>
                                <div class="sr-meta-value">
                                    {{ optional($shipmentReturn->submitted_at)->format('d M Y H:i') }}
                                    @if ($shipmentReturn->submittedBy)
                                        <div class="sr-order-info">{{ $shipmentReturn->submittedBy->name }}</div>
                                    @endif
                                </div>
                            </div>
                        @endif
                        @if ($shipmentReturn->posted_at)
                            <div class="sr-meta-item">
                                <div class="sr-meta-label">Posted</div>
                                <div class="sr-meta-value">
                                    {{ optional($shipmentReturn->posted_at)->format('d M Y H:i') }}
                                    @if ($shipmentReturn->postedBy)
                                        <div class="sr-order-info">{{ $shipmentReturn->postedBy->name }}</div>
                                    @endif
                                </div>
                            </div>
                        @endif
                        @if ($shipmentReturn->notes)
                            <div class="sr-meta-item" style="grid-column:1 / -1;">
                                <div class="sr-meta-label">Catatan</div>
                                <div class="sr-note">{{ $shipmentReturn->notes }}</div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @endif

        <div class="sr-panel">
            <div class="sr-panel-body">
                <div class="sr-orders">
                    @forelse ($groupedOrders as $order)
                        <div class="sr-order">
                            <div class="sr-order-head">
                                <div>
                                    <div class="sr-order-no">No Order</div>
                                    <div class="sr-order-code">{{ $order['code'] }}</div>
                                    <div class="sr-order-info">{{ $order['code'] === 'MANUAL' ? 'Tanpa order' : 'Retur pesanan' }}</div>
                                </div>
                                <div class="sr-order-qty">{{ number_format($order['qty'], 0, ',', '.') }}</div>
                            </div>
                            <div class="sr-item-list">
                                @foreach ($order['items'] as $line)
                                    <div class="sr-item-row">
                                        <div class="sr-item-num">{{ $loop->iteration }}.</div>
                                        <div>
                                            <div class="sr-item-code">{{ $line['code'] ?? '-' }}</div>
                                            <div class="sr-item-name">{{ $line['name'] ?? '' }}</div>
                                        </div>
                                        <div class="sr-item-qty">{{ number_format((int) ($line['qty'] ?? 0), 0, ',', '.') }}</div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @empty
                        <div class="sr-empty">Belum ada item retur.</div>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="sr-actions">
            <div class="sr-actions-group">
                <a href="{{ route('sales.shipment_returns.index') }}" class="sr-btn">Kembali</a>
                @if ($shipmentReturn->shipment)
                    <a href="{{ route('sales.shipments.show', $shipmentReturn->shipment) }}" class="sr-btn">Shipment Asal</a>
                @endif
            </div>

            <div class="sr-actions-group">
                @if ($status === 'draft')
                    <a href="{{ route('sales.shipment_returns.edit', $shipmentReturn) }}" class="sr-btn">Scan Retur</a>
                    <form action="{{ route('sales.shipment_returns.submit', $shipmentReturn) }}" method="POST" class="sr-inline-form" onsubmit="return confirm('Submit retur ini?');">
                        @csrf
                        <button type="submit" class="sr-btn sr-btn-primary" @disabled($lines->count() === 0)>Submit</button>
                    </form>
                @elseif ($status === 'submitted')
                    <form action="{{ route('sales.shipment_returns.post', $shipmentReturn) }}" method="POST" class="sr-inline-form" onsubmit="return confirm('Posting retur ini dan tambah stok ke WH-RTS?');">
                        @csrf
                        <button type="submit" class="sr-btn sr-btn-primary" @disabled($lines->count() === 0)>Posting WH-RTS</button>
                    </form>
                @else
                    <a href="{{ route('sales.shipment_returns.index') }}" class="sr-btn sr-btn-primary">Selesai</a>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
