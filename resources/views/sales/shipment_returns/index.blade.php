{{-- resources/views/sales/shipment_returns/index.blade.php --}}
@extends('layouts.app')

@section('title', 'Sales • Retur Shipment')

@push('head')
<style>
    :root{
        --shp-accent:#334155;
        --shp-accent-2:#1f2937;
        --shp-border:rgba(148,163,184,.18);
        --shp-border-strong:rgba(148,163,184,.30);
        --shp-muted:#64748b;
    }

    .page-wrap{
        max-width:1040px;
        margin-inline:auto;
        padding:.75rem .75rem 4rem;
        background:transparent!important;
    }

    .card-main{
        background:var(--card);
        border-radius:8px;
        border:1px solid var(--shp-border);
        box-shadow:none;
        overflow:hidden;
    }

    body[data-theme="dark"] .card-main{
        border-color:rgba(51,65,85,.85);
        box-shadow:none;
    }

    .ship-topbar{
        position:sticky;
        top:0;
        z-index:300;
        display:flex;
        justify-content:space-between;
        align-items:center;
        gap:.6rem;
        flex-wrap:wrap;
        padding:.45rem .75rem;
        margin-inline:-.75rem;
        margin-bottom:.65rem;
        background:var(--card,#fff);
        border-bottom:1px solid var(--shp-border);
    }

    body[data-theme="dark"] .ship-topbar{
        background:var(--card,#0f172a);
    }

    .title{
        font-weight:750;
        font-size:1rem;
        letter-spacing:0;
        margin:0;
    }

    .sub{
        color:var(--shp-muted);
        font-size:.78rem;
    }

    body[data-theme="dark"] .sub{
        color:#9ca3af;
    }

    .kpis{
        display:flex;
        flex-wrap:wrap;
        gap:.32rem;
        margin-top:.35rem;
    }

    .kpi{
        display:inline-flex;
        align-items:baseline;
        gap:.45rem;
        border-radius:7px;
        padding:.2rem .48rem;
        border:1px solid rgba(148,163,184,.28);
        background:transparent;
        font-size:.72rem;
    }

    body[data-theme="dark"] .kpi{
        background:rgba(15,23,42,.96);
        border-color:rgba(51,65,85,.85);
    }

    .kpi .lbl{
        text-transform:none;
        letter-spacing:0;
        font-size:.66rem;
        color:#94a3b8;
    }

    body[data-theme="dark"] .kpi .lbl{
        color:#6b7280;
    }

    .kpi .val{
        font-weight:650;
        color:var(--shp-accent);
    }

    body[data-theme="dark"] .kpi .val{
        color:#e5e7eb;
    }

    .controls{
        display:flex;
        gap:.5rem;
        align-items:center;
        flex-wrap:wrap;
    }

    .btn-pill{
        border-radius:7px;
        padding-inline:.78rem;
        box-shadow:none!important;
        font-weight:600;
    }

    .btn-ship-primary{
        background:var(--shp-accent)!important;
        border-color:var(--shp-accent)!important;
        color:#fff!important;
    }

    .btn-ship-primary:hover{
        background:var(--shp-accent-2)!important;
        border-color:var(--shp-accent-2)!important;
        color:#fff!important;
    }

    .btn-ship-outline{
        color:#475569!important;
        background:transparent!important;
        border:1px solid rgba(148,163,184,.35)!important;
    }

    .btn-ship-outline:hover{
        background:rgba(148,163,184,.08)!important;
        color:#111827!important;
    }

    .table-list{
        margin-bottom:0;
    }

    .table-list thead th{
        border-bottom-width:1px;
        font-size:.68rem;
        text-transform:none;
        letter-spacing:0;
        color:#64748b;
        background:var(--card,#fff);
        padding:.52rem .62rem;
        white-space:nowrap;
    }

    body[data-theme="dark"] .table-list thead th{
        background:rgba(15,23,42,.98);
        color:#9ca3af;
        border-bottom-color:rgba(30,64,175,.6);
    }

    .table-list tbody td{
        vertical-align:middle;
        border-top-color:rgba(148,163,184,.16);
        padding:.52rem .62rem;
    }

    body[data-theme="dark"] .table-list tbody td{
        border-top-color:rgba(51,65,85,.85);
    }

    .code-link{
        font-weight:700;
        text-decoration:none;
        color:inherit;
    }

    .code-link:hover{
        text-decoration:underline;
    }

    .muted{
        font-size:.82rem;
        color:#6b7280;
    }

    body[data-theme="dark"] .muted{
        color:#9ca3af;
    }

    .store-name{
        font-weight:600;
    }

    .mono{
        font-variant-numeric:tabular-nums;
        font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,"Liberation Mono",monospace;
    }

    .badge-status{
        border-radius:7px;
        padding:.16rem .48rem;
        font-size:.68rem;
        letter-spacing:0;
        text-transform:none;
        border:1px solid transparent;
        display:inline-flex;
        align-items:center;
        gap:.35rem;
        white-space:nowrap;
    }

    .badge-status::before{
        content:'';
        width:7px;
        height:7px;
        border-radius:999px;
        display:inline-block;
    }

    .st-draft{
        background:rgba(148,163,184,.10);
        color:#475569;
        border-color:rgba(148,163,184,.30);
    }

    .st-draft::before{
        background:rgba(100,116,139,.95);
    }

    .st-submitted{
        background:rgba(59,130,246,.10);
        color:#1d4ed8;
        border-color:rgba(59,130,246,.30);
    }

    .st-submitted::before{
        background:rgba(59,130,246,.95);
    }

    .st-cancelled{
        background:rgba(239,68,68,.10);
        color:#991b1b;
        border-color:rgba(239,68,68,.30);
    }

    .st-cancelled::before{
        background:rgba(239,68,68,.95);
    }

    .st-posted{
        background:rgba(34,197,94,.10);
        color:#166534;
        border-color:rgba(34,197,94,.30);
    }

    .st-posted::before{
        background:rgba(34,197,94,.95);
    }

    .st-linked{
        background:rgba(234,179,8,.10);
        color:#92400e;
        border-color:rgba(234,179,8,.30);
    }

    .st-linked::before{
        background:rgba(234,179,8,.95);
    }

    body[data-theme="dark"] .st-submitted{
        background:rgba(59,130,246,.20);
        color:#dbeafe;
        border-color:rgba(59,130,246,.55);
    }

    body[data-theme="dark"] .st-posted{
        background:rgba(34,197,94,.20);
        color:#dcfce7;
        border-color:rgba(34,197,94,.55);
    }

    body[data-theme="dark"] .st-cancelled{
        background:rgba(239,68,68,.20);
        color:#fecaca;
        border-color:rgba(239,68,68,.55);
    }

    .empty{
        padding:2.2rem 1.25rem;
        text-align:center;
        color:#64748b;
    }

    body[data-theme="dark"] .empty{
        color:#9ca3af;
    }

    .flash-clean{
        border-radius:8px;
        padding:.62rem .75rem;
        font-size:.84rem;
        border:1px solid rgba(148,163,184,.25);
    }

    @media (max-width:768px){
        .page-wrap{
            padding:.5rem .5rem 4rem;
        }

        .ship-topbar{
            margin-inline:-.5rem;
            padding:.5rem .65rem;
        }

        .title{
            font-size:1.05rem;
        }

        .sub{
            display:none;
        }

        .controls{
            width:100%;
            align-items:stretch;
        }

        .controls .btn{
            width:100%;
            min-height:40px;
        }

        .kpis{
            display:none;
        }

        .table-responsive{
            overflow:visible;
        }

        .table-list thead{
            display:none;
        }

        .table-list,
        .table-list tbody,
        .table-list tr,
        .table-list td{
            display:block;
            width:100%;
        }

        .table-list tbody tr{
            padding:.66rem;
            border-top:1px solid rgba(148,163,184,.16);
        }

        .table-list tbody td{
            border:0;
            padding:0;
        }

        .table-list tbody td.mobile-hide{
            display:none;
        }

        .ret-row-main{
            display:flex;
            align-items:flex-start;
            justify-content:space-between;
            gap:.75rem;
        }

        .ret-row-meta{
            display:flex;
            align-items:center;
            gap:.45rem;
            flex-wrap:wrap;
            margin-top:.35rem;
            color:#64748b;
            font-size:.78rem;
        }

        .ret-row-action{
            margin-top:.55rem;
        }

        .ret-row-action .btn{
            width:100%;
            min-height:38px;
        }
    }
</style>
@endpush

@section('content')
<div class="page-wrap">
    @php
        $pageTotalQty = (int) $returns->getCollection()->sum(fn ($row) => (int) ($row->total_qty ?? 0));
        $pageDraft = (int) $returns->getCollection()->where('status', 'draft')->count();
        $pagePosted = (int) $returns->getCollection()->where('status', 'posted')->count();

        $statusClass = function ($status) {
            return match ($status) {
                'draft' => 'st-draft',
                'submitted' => 'st-submitted',
                'cancelled' => 'st-cancelled',
                'posted' => 'st-posted',
                default => 'st-draft',
            };
        };

        $statusLabel = function ($status) {
            return match ($status) {
                'draft', 'submitted' => 'Draft',
                'cancelled' => 'Dibatalkan',
                'posted' => 'Diterima WH-RTS',
                default => ucfirst((string) $status),
            };
        };
    @endphp

    @if(session('message'))
        <div class="flash-clean alert alert-{{ session('status') === 'success' ? 'success' : 'danger' }} mb-2">
            {{ session('message') }}
        </div>
    @endif

    <div class="ship-topbar">
        <div>
            <div class="title">Retur Shipment</div>
            <div class="sub">Pencatatan retur dan penerimaan barang ke WH-RTS.</div>

            <div class="kpis">
                <span class="kpi"><span class="lbl">Total</span><span class="val">{{ number_format($returns->total(), 0, ',', '.') }}</span></span>
                <span class="kpi"><span class="lbl">Halaman</span><span class="val">{{ number_format($returns->count(), 0, ',', '.') }}</span></span>
                <span class="kpi"><span class="lbl">Qty</span><span class="val">{{ number_format($pageTotalQty, 0, ',', '.') }}</span></span>
                <span class="kpi"><span class="lbl">Draft</span><span class="val">{{ number_format($pageDraft, 0, ',', '.') }}</span></span>
                <span class="kpi"><span class="lbl">Posted</span><span class="val">{{ number_format($pagePosted, 0, ',', '.') }}</span></span>
            </div>
        </div>

        <div class="controls">
            <a href="{{ route('sales.shipment_returns.create') }}" class="btn btn-sm btn-ship-primary btn-pill">
                Retur Baru
            </a>
        </div>
    </div>

    <div class="card card-main">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-list align-middle">
                    <thead>
                        <tr>
                            <th>Kode</th>
                            <th>Tanggal</th>
                            <th>Store</th>
                            <th>Shipment Asal</th>
                            <th>Status</th>
                            <th class="text-end">Jumlah Order</th>
                            <th class="text-end">Jumlah Item</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($returns as $ret)
                            @php
                                $status = $ret->status;
                                $storeLabel = $ret->store
                                    ? trim(($ret->store->code ?? '-') . ' · ' . ($ret->store->name ?? '-'))
                                    : '-';
                            @endphp

                            <tr>
                                <td>
                                    <div class="ret-row-main">
                                        <div>
                                            <a href="{{ route('sales.shipment_returns.show', $ret) }}" class="code-link mono">
                                                {{ $ret->code }}
                                            </a>

                                            <div class="ret-row-meta d-md-none">
                                                <span>{{ optional($ret->date)->format('d M Y') ?: '-' }}</span>
                                                <span>·</span>
                                                <span>{{ $storeLabel }}</span>
                                            </div>
                                        </div>

                                        <span class="badge-status {{ $statusClass($status) }} d-md-none">
                                            {{ $statusLabel($status) }}
                                        </span>
                                    </div>
                                </td>

                                <td class="mono mobile-hide">
                                    {{ optional($ret->date)->format('d M Y') ?: '-' }}
                                </td>

                                <td class="mobile-hide">
                                    @if ($ret->store)
                                        <div class="store-name">{{ $ret->store->name ?? '-' }}</div>
                                        <div class="muted mono">{{ $ret->store->code ?? '-' }}</div>
                                    @else
                                        <span class="muted">-</span>
                                    @endif
                                </td>

                                <td class="mobile-hide">
                                    @if ($ret->shipment)
                                        <span class="badge-status st-linked mono">
                                            {{ $ret->shipment->code }}
                                        </span>
                                    @else
                                        <span class="muted">Manual</span>
                                    @endif
                                </td>

                                <td class="mobile-hide">
                                    <span class="badge-status {{ $statusClass($status) }}">
                                        {{ $statusLabel($status) }}
                                    </span>
                                </td>

                                <td class="text-end mono mobile-hide">
                                    {{ number_format((int) ($ret->order_scans_count ?? 0), 0, ',', '.') }}
                                </td>

                                <td class="text-end mono mobile-hide">
                                    {{ number_format((int) ($ret->lines_qty_sum ?? $ret->total_qty), 0, ',', '.') }}
                                </td>

                                <td>
                                    <div class="ret-row-action text-end">
                                        <a href="{{ route('sales.shipment_returns.show', $ret) }}" class="btn btn-sm btn-ship-outline btn-pill">
                                            Detail
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8">
                                    <div class="empty">
                                        Belum ada retur shipment.
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @if(method_exists($returns, 'links'))
        <div class="mt-3">
            {{ $returns->links() }}
        </div>
    @endif
</div>
@endsection
