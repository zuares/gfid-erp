{{-- resources/views/sales/shipments/index.blade.php --}}
@extends('layouts.app')

@section('title', 'Shipments • Keluar Barang')

@push('head')
<style>
    :root{
        --shp-accent:#334155;
        --shp-accent-2:#1f2937;
        --shp-border:rgba(148,163,184,.18);
        --shp-border-strong:rgba(148,163,184,.30);
        --shp-muted:#64748b;
    }
    .page-wrap{ max-width:1040px; margin-inline:auto; padding:.75rem .75rem 4rem; background:transparent!important; }

    .card-main{
        background: var(--card);
        border-radius: 8px;
        border: 1px solid var(--shp-border);
        box-shadow: none;
        overflow:hidden;
    }
    body[data-theme="dark"] .card-main{
        border-color: rgba(51,65,85,.85);
        box-shadow: none;
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
    body[data-theme="dark"] .ship-topbar{ background:var(--card,#0f172a); }
    .title{ font-weight: 750; font-size:1rem; letter-spacing: 0; margin:0; }
    .sub{ color:var(--shp-muted); font-size:.78rem; }
    body[data-theme="dark"] .sub{ color:#9ca3af; }

    .kpis{ display:flex; flex-wrap:wrap; gap:.32rem; margin-top:.35rem; }
    .kpi{
        display:inline-flex; align-items:baseline; gap:.45rem;
        border-radius:7px; padding:.2rem .48rem;
        border:1px solid rgba(148,163,184,.28);
        background: transparent;
        font-size:.72rem;
    }
    body[data-theme="dark"] .kpi{
        background: rgba(15, 23, 42, 0.96);
        border-color: rgba(51, 65, 85, 0.85);
    }
    .kpi .lbl{ text-transform:none; letter-spacing:0; font-size:.66rem; color:#94a3b8; }
    body[data-theme="dark"] .kpi .lbl{ color:#6b7280; }
    .kpi .val{ font-weight:650; color:var(--shp-accent); }

    .controls{ display:flex; gap:.5rem; align-items:center; flex-wrap:wrap; }
    .filter-label{ font-size:.8rem; color:#6b7280; }
    body[data-theme="dark"] .filter-label{ color:#9ca3af; }
    .filter-select{ border-radius:7px; padding-left:.75rem; padding-right:2rem; font-size:.82rem; }
    .btn-pill{ border-radius:7px; padding-inline:.78rem; box-shadow:none!important; font-weight:600; }
    .btn-ship-primary{ background:var(--shp-accent)!important; border-color:var(--shp-accent)!important; color:#fff!important; }
    .btn-ship-primary:hover{ background:var(--shp-accent-2)!important; border-color:var(--shp-accent-2)!important; color:#fff!important; }
    .btn-ship-outline{ color:#475569!important; background:transparent!important; border:1px solid rgba(148,163,184,.35)!important; }
    .btn-ship-outline:hover{ background:rgba(148,163,184,.08)!important; color:#111827!important; }
    .btn-fresh{ border-color:#fecaca; color:#b91c1c; background:transparent; }
    .btn-fresh:hover{ background:#fef2f2; color:#991b1b; border-color:#fca5a5; }

    .table-list{ margin-bottom:0; }
    .table-list thead th{
        border-bottom-width:1px;
        font-size:.68rem;
        text-transform:none;
        letter-spacing:0;
        color:#64748b;
        background: var(--card,#fff);
        padding:.52rem .62rem;
        white-space:nowrap;
    }
    body[data-theme="dark"] .table-list thead th{
        background: rgba(15, 23, 42, 0.98);
        color:#9ca3af;
        border-bottom-color: rgba(30, 64, 175, 0.6);
    }
    .table-list tbody td{
        vertical-align:middle;
        border-top-color: rgba(148, 163, 184, 0.16);
        padding:.52rem .62rem;
    }
    body[data-theme="dark"] .table-list tbody td{ border-top-color: rgba(51, 65, 85, 0.85); }

    .code-link{ font-weight:700; text-decoration:none; color:inherit; }
    .code-link:hover{ text-decoration:underline; }
    .muted{ font-size:.82rem; color:#6b7280; }
    body[data-theme="dark"] .muted{ color:#9ca3af; }
    .store-name{ font-weight:600; }

    .badge-status{
        border-radius:7px; padding:.16rem .48rem;
        font-size:.68rem; letter-spacing:0; text-transform:none;
        border:1px solid transparent;
        display:inline-flex; align-items:center; gap:.35rem;
        white-space:nowrap;
    }
    .badge-status::before{ content:''; width:7px; height:7px; border-radius:999px; display:inline-block; }

    .st-draft{ background: rgba(148, 163, 184, 0.10); color:#475569; border-color: rgba(148, 163, 184, 0.30); }
    .st-draft::before{ background: rgba(100, 116, 139, 0.95); }
    .st-submitted{ background: rgba(59, 130, 246, 0.10); color:#1d4ed8; border-color: rgba(59, 130, 246, 0.30); }
    .st-submitted::before{ background: rgba(59, 130, 246, 0.95); }
    .st-posted{ background: rgba(34, 197, 94, 0.10); color:#166534; border-color: rgba(34, 197, 94, 0.30); }
    .st-posted::before{ background: rgba(34, 197, 94, 0.95); }
    .st-cancelled{ background: rgba(239, 68, 68, 0.10); color:#991b1b; border-color: rgba(239, 68, 68, 0.30); }
    .st-cancelled::before{ background: rgba(239, 68, 68, 0.95); }

    body[data-theme="dark"] .st-submitted{ background: rgba(59, 130, 246, 0.20); color:#dbeafe; border-color: rgba(59, 130, 246, 0.55); }
    body[data-theme="dark"] .st-posted{ background: rgba(34, 197, 94, 0.20); color:#dcfce7; border-color: rgba(34, 197, 94, 0.55); }
    body[data-theme="dark"] .st-cancelled{ background: rgba(239, 68, 68, 0.18); color:#fecaca; border-color: rgba(239, 68, 68, 0.55); }

    .empty{ padding:2.2rem 1.25rem; text-align:center; color:#64748b; }
    body[data-theme="dark"] .empty{ color:#9ca3af; }
    .divider{ height:1px; background: rgba(148, 163, 184, 0.20); }
    body[data-theme="dark"] .divider{ background: rgba(51, 65, 85, 0.85); }
    .flash-clean{ border-radius:8px; padding:.62rem .75rem; font-size:.84rem; border:1px solid rgba(148,163,184,.25); }

    @media (max-width: 768px) {
        .page-wrap{ padding:.5rem .5rem 4rem; }
        .ship-topbar{ margin-inline:-.5rem; padding:.5rem .65rem; }
        .title{ font-size:1.05rem; }
        .sub{ display:none; }
        .controls{ width:100%; align-items:stretch; }
        .controls form{ flex:1 1 100%; }
        .filter-select{ width:100%; min-height:40px; }
        .controls .btn,
        .controls form button{ min-height:40px; }
        .kpis{ display:none; }
        .table-responsive{ overflow:visible; }
        .table-list thead{ display:none; }
        .table-list,
        .table-list tbody,
        .table-list tr,
        .table-list td{ display:block; width:100%; }
        .table-list tbody tr{
            padding:.66rem;
            border-top:1px solid rgba(148,163,184,.16);
        }
        .table-list tbody td{
            border:0;
            padding:0;
        }
        .table-list tbody td.mobile-hide{ display:none; }
        .ship-row-main{
            display:flex;
            align-items:flex-start;
            justify-content:space-between;
            gap:.75rem;
        }
        .ship-row-meta{
            display:flex;
            align-items:center;
            gap:.45rem;
            flex-wrap:wrap;
            margin-top:.35rem;
            color:#64748b;
            font-size:.78rem;
        }
        .ship-row-action{
            margin-top:.55rem;
        }
        .ship-row-action .btn{
            width:100%;
            min-height:38px;
        }
    }
</style>
@endpush

@section('content')
<div class="page-wrap">
    @php
        use Illuminate\Support\Carbon;

        $fmtDate = function ($value, string $format = 'd M Y', string $fallback = '-') {
            if (empty($value)) return $fallback;
            try {
                if ($value instanceof \DateTimeInterface) return $value->format($format);
                return Carbon::parse($value)->format($format);
            } catch (\Throwable $e) {
                return $fallback;
            }
        };

        $statusFilter = $statusFilter ?? request('status', 'all');
        $canSeeNominal = $canSeeNominal ?? ((auth()->user()->role ?? null) !== 'admin');
        $canFreshShipments = $canFreshShipments ?? false;

        $pageTotalQty = (int) $shipments->getCollection()->sum('total_qty_calc');
        $pageTotalRp  = $canSeeNominal ? (float) $shipments->getCollection()->sum('total_rp_calc') : 0;
    @endphp

    @if(session('message'))
        <div class="flash-clean alert alert-{{ session('status') === 'success' ? 'success' : 'danger' }} mb-2">
            {{ session('message') }}
        </div>
    @endif

    @if(isset($staleDrafts) && $staleDrafts->count() > 0)
        <div class="alert alert-warning mb-2" style="border-radius: 8px; font-size: 0.88rem;">
            <i class="bi bi-clock-history"></i> 
            <strong>Peringatan Stale Draft:</strong> 
            Terdapat <b>{{ $staleDrafts->count() }} Shipment</b> (Draft/Submitted) berumur lebih dari 24 jam yang menahan total 
            <b>{{ number_format($staleDrafts->sum('total_allocated'), 0, ',', '.') }} unit stok</b>. 
            Mohon segera selesaikan atau hapus draf berikut: 
            @foreach($staleDrafts->take(5) as $sd)
                <a href="{{ route('sales.shipments.edit', $sd) }}" class="fw-bold text-dark text-decoration-underline">{{ $sd->code }}</a>{{ !$loop->last ? ',' : '' }}
            @endforeach
            @if($staleDrafts->count() > 5) ... @endif
        </div>
    @endif

    <div class="ship-topbar">
        <div>
            <div class="title">Shipment</div>
            <div class="sub">Dokumen barang keluar WH-RTS.</div>

            <div class="kpis">
                <span class="kpi"><span class="lbl">Total</span><span class="val">{{ number_format($shipments->total(), 0, ',', '.') }}</span></span>
                <span class="kpi"><span class="lbl">Halaman</span><span class="val">{{ number_format($shipments->count(), 0, ',', '.') }}</span></span>
                <span class="kpi"><span class="lbl">Qty</span><span class="val">{{ number_format($pageTotalQty, 0, ',', '.') }}</span></span>

                @if($canSeeNominal)
                    <span class="kpi"><span class="lbl">Rp</span><span class="val">Rp {{ number_format($pageTotalRp, 0, ',', '.') }}</span></span>
                @endif
            </div>
        </div>

        <div class="controls">
            <form method="GET" class="d-flex gap-2 align-items-center">
                <span class="filter-label">Status</span>
                <select name="status" class="form-select form-select-sm filter-select" onchange="this.form.submit()">
                    <option value="all" {{ $statusFilter === 'all' ? 'selected' : '' }}>All</option>
                    <option value="draft" {{ $statusFilter === 'draft' ? 'selected' : '' }}>Draft</option>
                    <option value="submitted" {{ $statusFilter === 'submitted' ? 'selected' : '' }}>Submitted</option>
                    <option value="posted" {{ $statusFilter === 'posted' ? 'selected' : '' }}>Posted</option>
                    <option value="cancelled" {{ $statusFilter === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                </select>
            </form>

            @if($canFreshShipments)
                <form method="POST" action="{{ route('sales.shipments.dev_fresh') }}"
                      onsubmit="return confirm('Fresh semua data shipment? Aksi ini hanya untuk database dev.');">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-outline-danger btn-pill btn-fresh">
                        Fresh Data
                    </button>
                </form>
            @endif

            <a href="{{ route('sales.shipments.create') }}" class="btn btn-sm btn-ship-primary btn-pill">
                Shipment Baru
            </a>
        </div>
    </div>

    <div class="card card-main">
        <div class="card-body p-0">
            @if ($shipments->count() === 0)
                <div class="empty">
                    Belum ada shipment.
                    <div class="mt-1">Klik <b>Shipment Baru</b> untuk mulai scan.</div>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle table-list">
                        <thead>
                            <tr>
                                <th style="width: 46px;">#</th>
                                <th style="width: 120px;">Tanggal</th>
                                <th style="width: 210px;">Shipment</th>
                                <th>Store / Channel</th>
                                <th class="text-end" style="width: 110px;">Qty</th>

                                @if($canSeeNominal)
                                    <th class="text-end" style="width: 170px;">Total Rp</th>
                                @endif

                                <th class="text-end" style="width: 110px;">Kategori</th>
                                <th style="width: 130px;">Status</th>
                                <th style="width: 90px;"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($shipments as $shipment)
                                @php
                                    $storeName = $shipment->store->name ?? '-';
                                    $storeCode = $shipment->store->code ?? '';
                                    $channelLabel = $storeCode ? strtoupper($storeCode) : null;

                                    $qty = (int) ($shipment->total_qty_calc ?? 0);
                                    $totalRp = (float) ($shipment->total_rp_calc ?? 0);
                                    $catCount = (int) ($shipment->category_count_calc ?? 0);

                                    $isCancelled = !empty($shipment->cancelled_at);
                                    $uiStatus = $isCancelled ? 'cancelled' : ($shipment->status ?? 'submitted');

                                    $statusClass = match ($uiStatus) {
                                        'draft' => 'st-draft',
                                        'submitted' => 'st-submitted',
                                        'posted' => 'st-posted',
                                        'cancelled' => 'st-cancelled',
                                        default => 'st-submitted',
                                    };

                                    $statusLabel = ucfirst($uiStatus);
                                    $actionRoute = $uiStatus === 'draft'
                                        ? route('sales.shipments.edit', $shipment)
                                        : route('sales.shipments.show', $shipment);
                                    $actionLabel = $uiStatus === 'draft' ? 'Lanjutkan' : 'Detail';
                                @endphp

                                <tr>
                                    <td class="text-muted small mobile-hide">
                                        {{ ($shipments->currentPage() - 1) * $shipments->perPage() + $loop->iteration }}
                                    </td>

                                    <td class="small mobile-hide">{{ $fmtDate($shipment->date, 'd M Y') }}</td>

                                    <td>
                                        <div class="ship-row-main">
                                            <div>
                                                <a class="code-link" href="{{ $actionRoute }}">
                                                    {{ $shipment->code }}
                                                </a>

                                                <div class="muted mt-1">
                                                    @if ($isCancelled)
                                                        Cancelled {{ $fmtDate($shipment->cancelled_at, 'd M Y H:i') }}
                                                    @elseif (!empty($shipment->posted_at))
                                                        Posted {{ $fmtDate($shipment->posted_at, 'd M Y H:i') }}
                                                    @elseif (!empty($shipment->submitted_at))
                                                        Submitted {{ $fmtDate($shipment->submitted_at, 'd M Y H:i') }}
                                                    @else
                                                        Draft
                                                    @endif
                                                </div>
                                            </div>
                                            <span class="badge-status {{ $statusClass }} d-md-none">{{ $statusLabel }}</span>
                                        </div>
                                    </td>

                                    <td>
                                        <div class="store-name">{{ $storeName }}</div>
                                        @if ($channelLabel)
                                            <div class="muted">{{ $channelLabel }}</div>
                                        @endif
                                        <div class="ship-row-meta d-md-none">
                                            <span>{{ $fmtDate($shipment->date, 'd M Y') }}</span>
                                            <span>Qty {{ number_format($qty, 0, ',', '.') }}</span>
                                            <span>{{ number_format($catCount, 0, ',', '.') }} kategori</span>
                                        </div>
                                    </td>

                                    <td class="text-end mobile-hide">
                                        <span class="fw-semibold">{{ number_format($qty, 0, ',', '.') }}</span>
                                    </td>

                                    @if($canSeeNominal)
                                        <td class="text-end mobile-hide">
                                            <span class="fw-semibold">Rp {{ number_format($totalRp, 0, ',', '.') }}</span>
                                        </td>
                                    @endif

                                    <td class="text-end mobile-hide">
                                        <span class="fw-semibold">{{ number_format($catCount, 0, ',', '.') }}</span>
                                    </td>

                                    <td class="mobile-hide">
                                        <span class="badge-status {{ $statusClass }}">{{ $statusLabel }}</span>
                                    </td>

                                    <td class="text-end ship-row-action">
                                        <a href="{{ $actionRoute }}" class="btn btn-sm btn-ship-outline btn-pill">
                                            {{ $actionLabel }}
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="divider"></div>

                <div class="p-3">
                    {{ $shipments->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
