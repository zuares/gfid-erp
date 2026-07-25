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

    /* ═══════════════════════════════
       PREVIEW MODAL (Custom)
    ═══════════════════════════════ */
    .ms-modal-backdrop {
        position: fixed; top: 0; left: 0; right: 0; bottom: 0;
        background: rgba(15,23,42,.6);
        backdrop-filter: blur(4px);
        z-index: 9990;
        display: none;
        align-items: center; justify-content: center;
        padding: 1rem;
    }
    .ms-modal-backdrop.show { display: flex; }
    
    .ms-modal-content {
        background: var(--card, #fff);
        border-radius: 24px;
        width: 100%; max-width: 500px;
        box-shadow: 0 20px 40px rgba(0,0,0,.2);
        overflow: hidden;
        display: flex; flex-direction: column;
        max-height: 90vh;
    }
    .ms-modal-header {
        padding: 1.25rem 1.5rem;
        border-bottom: 1px solid var(--shp-border, rgba(148,163,184,.15));
        display: flex; align-items: center; justify-content: space-between;
    }
    .ms-modal-header h3 {
        margin: 0; font-size: 1.1rem; font-weight: 800; color: var(--text, #0f172a);
    }
    .ms-modal-close {
        background: transparent; border: none; font-size: 1.5rem; color: var(--muted); cursor: pointer;
    }
    .ms-modal-body {
        padding: 1.5rem;
        overflow-y: auto;
        background: #e2e8f0;
        display: flex; justify-content: center;
    }
    body[data-theme="dark"] .ms-modal-body { background: #0f172a; }
    
    .ms-modal-footer {
        padding: 1.25rem 1.5rem;
        border-top: 1px solid var(--shp-border, rgba(148,163,184,.15));
        display: flex; justify-content: flex-end; gap: .75rem;
    }

    /* ═══════════════════════════════
       LABEL (100mm × 150mm)
    ═══════════════════════════════ */
    .label-wrap {
        width: 100mm;
        min-height: 150mm;
        background: #fff;
        color: #0f172a;
        font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
        position: relative;
        box-shadow: 0 8px 32px rgba(0,0,0,.15);
        overflow: hidden;
    }

    .label-header {
        background: #fff;
        color: #000;
        padding: 10px 14px;
        display: flex;
        align-items: center;
        gap: 10px;
        border-bottom: 2px solid #000;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
    .label-header .logo-svg { width: 36px; height: 36px; flex-shrink: 0; object-fit: contain; }
    .label-header .brand-text { font-size: 18px; font-weight: 900; letter-spacing: -.5px; color: #000; }
    .label-header .brand-sub { font-size: 8px; font-weight: 800; color: #000; letter-spacing: 1px; text-transform: uppercase; margin-top: 1px; }

    .label-section { padding: 8px 14px; border-bottom: 1.5px dashed #000; }
    .label-section:last-child { border-bottom: none; }
    .label-section-title { font-size: 7px; font-weight: 900; text-transform: uppercase; letter-spacing: 1.2px; color: #000; margin-bottom: 4px; }
    .label-name { font-size: 13px; font-weight: 900; line-height: 1.2; margin-bottom: 2px; text-transform: uppercase; color: #000; }
    .label-phone { font-size: 11px; font-weight: 700; color: #000; display: flex; align-items: center; gap: 4px; }
    .label-phone .phone-icon { font-size: 10px; }
    .label-address { font-size: 11px; font-weight: 500; line-height: 1.35; color: #000; margin-top: 4px; word-break: break-word; }
    .label-items { font-size: 9px; font-weight: 600; color: #000; margin-top: 6px; line-height: 1.4; }
    .label-item-row { display: flex; justify-content: space-between; border-bottom: 1px solid #ccc; padding: 2px 0; color: #000; }
    .label-item-row:last-child { border-bottom: none; }

    .label-divider { display: flex; align-items: center; gap: 8px; padding: 3px 14px; background: #fff; }
    .label-divider::before, .label-divider::after { content: ''; flex: 1; height: 1px; background: #000; }
    .label-divider .arrow-icon { font-size: 14px; color: #000; }

    .label-promo { background: #fff; padding: 10px 14px; text-align: center; border-bottom: 1.5px dashed #000; }
    .label-promo-title { font-size: 8px; font-weight: 800; text-transform: uppercase; letter-spacing: 1.5px; color: #000; margin-bottom: 6px; }
    .label-promo-content { display: flex; align-items: center; justify-content: center; gap: 12px; }
    .label-qr { flex-shrink: 0; }
    .label-qr canvas, .label-qr img { width: 60px !important; height: 60px !important; }
    .label-promo-info { text-align: left; }
    .label-promo-url { font-size: 12px; font-weight: 900; color: #000; margin-bottom: 4px; }
    .label-promo-socials { display: flex; flex-direction: column; gap: 3px; }
    .label-promo-social-item { display: flex; align-items: center; gap: 5px; font-size: 9px; font-weight: 600; color: #000; }
    .label-promo-social-item img { width: 13px; height: 13px; object-fit: contain; filter: grayscale(100%) contrast(200%); }

    .label-footer { 
        background: #fff; 
        color: #000; 
        text-align: center; 
        padding: 8px 14px; 
        font-size: 8px; 
        font-weight: 800; 
        letter-spacing: .3px; 
        border-top: 2px solid #000; 
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
    .label-footer strong { color: #000; font-weight: 900; }

    @media print {
        @page { size: 100mm 150mm; margin: 0; }
        body * { visibility: hidden; }
        #modalPreview, #modalPreview * { visibility: visible; }
        #modalPreview { position: absolute; left: 0; top: 0; width: 100mm; height: 150mm; margin: 0; padding: 0; display: block !important; background: none !important; }
        .ms-modal-content { box-shadow: none !important; border-radius: 0 !important; max-width: none !important; border: none !important; background: none !important; margin: 0 !important; padding: 0 !important; }
        .ms-modal-header, .ms-modal-footer, .no-print { display: none !important; }
        .ms-modal-body { padding: 0 !important; background: #fff !important; display: block !important; overflow: visible !important; }
        .label-wrap { width: 100mm !important; min-height: 150mm !important; box-shadow: none !important; margin: 0 !important; position: absolute; top: 0; left: 0; }
    }

    body[data-theme="dark"] .ms-modal-content { background: #1e293b; }
    body[data-theme="dark"] .ms-modal-header h3 { color: #f1f5f9; }
    body[data-theme="dark"] .ms-modal-header, body[data-theme="dark"] .ms-modal-footer { border-color: rgba(51,65,85,.6); }
</style>
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
@endpush

@section('content')
<div class="page-wrap">
    @if(isset($isDummy) && $isDummy)
    <div style="background-color: #fff3cd; color: #856404; padding: 10px 15px; border-radius: 6px; border: 1px solid #ffeeba; margin-bottom: 15px; display: flex; align-items: center; justify-content: space-between;">
        <div>
            <strong>🧪 MENGGUNAKAN DUMMY MODE</strong><br>
            <span style="font-size: 0.9em;">Halaman ini sedang berada dalam mode dummy pengujian UI.</span>
        </div>
        <a href="?" style="background: #856404; color: #fff; padding: 5px 10px; border-radius: 4px; text-decoration: none; font-size: 0.85em; font-weight: bold;">Keluar Dummy</a>
    </div>
    @endif
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
                                        @if($shipment->shipment_type === 'manual')
                                            @php 
                                                $recv = ['nama' => '-', 'phone' => '-', 'alamat' => '-'];
                                                if ($shipment->notes) {
                                                    $decoded = json_decode($shipment->notes, true);
                                                    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                                                        $recv = array_merge($recv, $decoded);
                                                    }
                                                }
                                                $linesJson = $shipment->lines->map(fn($l) => [
                                                    'code' => $l->item->code ?? '', 
                                                    'name' => $l->item->name ?? '', 
                                                    'qty' => $l->qty_scanned
                                                ])->toJson();
                                            @endphp
                                            <div class="d-flex justify-content-end gap-1">
                                                <button type="button" class="btn btn-sm btn-ship-outline btn-pill"
                                                    data-code="{{ $shipment->code }}"
                                                    data-date="{{ $fmtDate($shipment->date, 'd M Y') }}"
                                                    data-nama="{{ $recv['nama'] }}"
                                                    data-phone="{{ $recv['phone'] }}"
                                                    data-alamat="{{ $recv['alamat'] }}"
                                                    data-items="{{ $linesJson }}"
                                                    onclick="openPreview(this)" title="Preview Label">
                                                    <i class="bi bi-printer"></i>
                                                </button>
                                                
                                                @if($uiStatus === 'draft')
                                                    <form action="{{ route('sales.shipments.manual.post', $shipment) }}" method="POST" class="d-inline" onsubmit="return confirm('Kirim paket manual ini? Stok WH-RTS akan dipotong.');">
                                                        @csrf
                                                        <button type="submit" class="btn btn-sm btn-ship-primary btn-pill" title="Kirim/Post">
                                                            <i class="bi bi-send"></i>
                                                        </button>
                                                    </form>
                                                    
                                                    <form action="{{ route('sales.shipments.manual.destroy', $shipment) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus paket manual ini?');">
                                                        @csrf @method('DELETE')
                                                        <button type="submit" class="btn btn-sm btn-outline-danger btn-pill" style="border-color:#fecaca;" title="Hapus">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                    </form>
                                                @endif
                                            </div>
                                        @else
                                            <a href="{{ $actionRoute }}" class="btn btn-sm btn-ship-outline btn-pill w-100">
                                                {{ $actionLabel }}
                                            </a>
                                        @endif
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

<!-- MODAL PREVIEW LABEL (Manual Shipment) -->
<div class="ms-modal-backdrop" id="modalPreview">
    <div class="ms-modal-content">
        <div class="ms-modal-header no-print">
            <h3><i class="bi bi-zoom-in"></i> Preview Label Cetak</h3>
            <button class="ms-modal-close" onclick="closePreview()">&times;</button>
        </div>
        
        <div class="ms-modal-body" id="previewArea">
            <div class="label-wrap" id="labelWrap">
                <!-- Label Header -->
                <div class="label-header">
                    <img src="{{ asset('images/logo-mark.svg') }}" alt="GF Logo" class="logo-svg" style="filter: brightness(0); width: 36px; height: 36px;">
                    <div>
                        <div class="brand-text">GREATFIT.ID</div>
                        <div class="brand-sub">Manual Shipping Label</div>
                    </div>
                </div>

                <!-- Resi Placeholder -->
                <div class="label-section" style="padding: 12px 14px; text-align: center;">
                    <div style="border: 2px dashed #000; min-height: 90px; display: flex; align-items: center; justify-content: center; font-weight: 800; color: #64748b; letter-spacing: 1.5px; font-size: 11px; text-transform: uppercase;">
                        [ TEMPEL / TULIS NO RESI DI SINI ]
                    </div>
                </div>

                <!-- Pengirim -->
                <div class="label-section">
                    <div class="label-section-title">✉ Pengirim</div>
                    <div class="label-name" id="lblSenderName">GREATFIT.ID</div>
                    <div class="label-phone">
                        <span class="phone-icon">📞</span>
                        <span id="lblSenderPhone">081224889319</span>
                    </div>
                </div>

                <!-- Arrow Divider -->
                <div class="label-divider">
                    <span class="arrow-icon">▼</span>
                </div>

                <!-- Penerima -->
                <div class="label-section">
                    <div class="label-section-title">📍 Penerima</div>
                    <div class="label-name" id="lblRecvName">—</div>
                    <div class="label-phone">
                        <span class="phone-icon">📞</span>
                        <span id="lblRecvPhone">—</span>
                    </div>
                    <div class="label-address" id="lblRecvAddress">—</div>
                </div>
                
                <!-- Items Summary -->
                <div class="label-section">
                    <div class="label-section-title">📦 Daftar Item</div>
                    <div class="label-items" id="lblItemsList">
                        <!-- Items will be injected here -->
                    </div>
                </div>

                <!-- Promo Section -->
                <div class="label-promo">
                    <div class="label-promo-title">✨ Kunjungi Kami ✨</div>
                    <div class="label-promo-content">
                        <div class="label-qr" id="labelQrCode"></div>
                        <div class="label-promo-info">
                            <div class="label-promo-url">www.greatfit.id</div>
                            <div class="label-promo-socials">
                                <div class="label-promo-social-item">
                                    <img src="{{ asset('img/social/IG.png') }}" alt="IG"> @greatfit.id
                                </div>
                                <div class="label-promo-social-item">
                                    <img src="{{ asset('img/social/WA.png') }}" alt="WA"> 081224889319
                                </div>
                                <div class="label-promo-social-item">
                                    <img src="{{ asset('img/social/TT.png') }}" alt="TT"> @greatfit.id
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="label-footer" style="display:flex; justify-content:space-between; align-items:center; padding: 8px 14px;">
                    <span id="lblShipmentCode" style="font-weight: 900; letter-spacing: 1px;">MNL-XXX</span>
                    <span>Terima kasih sudah berbelanja di <strong>Greatfit</strong> 🩵</span>
                    <span id="lblShipmentDate">01 JAN 2026</span>
                </div>
            </div>
        </div>
        
        <div class="ms-modal-footer no-print">
            <button class="ms-btn ms-btn-outline" onclick="closePreview()" style="padding:.5rem 1rem; border-radius:10px; border:1px solid #cbd5e1; background:transparent; cursor:pointer;">Tutup</button>
            <button class="ms-btn ms-btn-primary" onclick="printLabel()" style="padding:.5rem 1rem; border-radius:10px; border:none; background:#3b82f6; color:#fff; cursor:pointer;"><i class="bi bi-printer"></i> Cetak Label</button>
        </div>
    </div>
</div>

<script>
let qrGenerated = false;

function openPreview(btn) {
    const code = btn.getAttribute('data-code');
    const date = btn.getAttribute('data-date');
    const nama = btn.getAttribute('data-nama');
    const phone = btn.getAttribute('data-phone');
    const alamat = btn.getAttribute('data-alamat');
    let items = [];
    try {
        items = JSON.parse(btn.getAttribute('data-items'));
    } catch(e) {}

    document.getElementById('lblShipmentCode').textContent = code;
    document.getElementById('lblShipmentDate').textContent = date;
    document.getElementById('lblRecvName').textContent = nama.toUpperCase();
    document.getElementById('lblRecvPhone').textContent = phone;
    document.getElementById('lblRecvAddress').textContent = alamat;
    
    // Render items
    const itemsContainer = document.getElementById('lblItemsList');
    itemsContainer.innerHTML = '';
    
    if(items && items.length > 0) {
        items.forEach((item, index) => {
            const row = document.createElement('div');
            row.className = 'label-item-row';
            row.style.alignItems = 'center';
            row.innerHTML = `
                <div style="flex:1; padding-right:10px; line-height:1.2;">
                    ${index + 1}. <strong style="font-size:10px;">${item.name || item.code}</strong><br>
                    <span style="color:#475569; font-size:8px; margin-left:12px;">${item.code}</span>
                </div>
                <strong style="font-size:11px;">x${item.qty}</strong>
            `;
            itemsContainer.appendChild(row);
        });
    } else {
        itemsContainer.innerHTML = '<i>Data item tidak tersedia</i>';
    }
    
    // Generate QR once
    if (!qrGenerated && typeof QRCode !== 'undefined') {
        const qrContainer = document.getElementById('labelQrCode');
        new QRCode(qrContainer, {
            text: 'https://www.greatfit.id',
            width: 60,
            height: 60,
            colorDark: '#000000',
            colorLight: '#ffffff',
            correctLevel: QRCode.CorrectLevel.M
        });
        qrGenerated = true;
    }
    
    document.getElementById('modalPreview').classList.add('show');
}

function closePreview() {
    document.getElementById('modalPreview').classList.remove('show');
}

function printLabel() {
    window.print();
}
</script>
@endsection
