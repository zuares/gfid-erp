@extends('layouts.app')
@section('title', 'Marketplace • Promosi')

@include('marketplace._shared')

@push('head')
<style>
    .promo-wrap{ max-width:1240px; margin-inline:auto; padding:.75rem .75rem 4rem; background:transparent!important; }
    .promo-topbar{
        position:sticky; top:0; z-index:300;
        display:flex; justify-content:space-between; align-items:center; gap:.6rem; flex-wrap:wrap;
        padding:.85rem .95rem; margin-inline:-.75rem; margin-bottom:.75rem;
        background:var(--card,#fff); border:1px solid var(--shp-border);
        border-radius:16px;
        box-shadow:0 10px 24px rgba(15,23,42,.04);
    }
    body[data-theme="dark"] .promo-topbar{ background:var(--card,#0f172a); box-shadow:none; border-color:rgba(51,65,85,.88); }
    .promo-title{ font-weight:800; font-size:1.08rem; letter-spacing:-.02em; margin:0; }
    .promo-sub{ color:var(--shp-muted); font-size:.79rem; margin-top:.1rem; }
    body[data-theme="dark"] .promo-sub{ color:#9ca3af; }
    .promo-controls{ display:flex; gap:.5rem; align-items:center; flex-wrap:wrap; }
    .promo-chipbar{ display:flex; flex-wrap:wrap; gap:.35rem; margin-top:.5rem; }
    .promo-chip{
        display:inline-flex; align-items:baseline; gap:.45rem; border-radius:7px; padding:.2rem .48rem;
        border:1px solid rgba(148,163,184,.22); background:rgba(148,163,184,.04); font-size:.72rem;
    }
    body[data-theme="dark"] .promo-chip{ background:rgba(15,23,42,.96); border-color:rgba(51,65,85,.85); }
    .promo-chip .lbl{ font-size:.66rem; color:#94a3b8; }
    .promo-chip .val{ font-weight:650; color:var(--shp-accent); }
    body[data-theme="dark"] .promo-chip .val{ color:#e2e8f0; }

    .promo-card{
        background:var(--card,#fff);
        border-radius:14px;
        border:1px solid var(--shp-border);
        overflow:hidden;
        box-shadow:none;
    }
    body[data-theme="dark"] .promo-card{ border-color:rgba(51,65,85,.85); }

    .promo-dashboard{
        display:grid;
        gap:.75rem;
    }
    .promo-dashboard-grid{
        display:grid;
        grid-template-columns:repeat(3, minmax(0, 1fr));
        gap:.75rem;
    }
    .promo-stat-card{
        border:1px solid rgba(148,163,184,.18);
        border-radius:14px;
        background:var(--card,#fff);
        padding:.85rem .95rem;
        box-shadow:none;
        min-height:100px;
        display:flex;
        flex-direction:column;
        justify-content:space-between;
    }
    body[data-theme="dark"] .promo-stat-card{
        background:rgba(15,23,42,.98);
        border-color:rgba(51,65,85,.85);
    }
    .promo-stat-card.is-accent{
        background:linear-gradient(180deg, rgba(248,250,252,.92), rgba(241,245,249,.88));
    }
    body[data-theme="dark"] .promo-stat-card.is-accent{
        background:linear-gradient(180deg, rgba(15,23,42,.98), rgba(2,6,23,.98));
    }
    .promo-stat-label{
        color:#64748b;
        font-size:.68rem;
        font-weight:800;
        letter-spacing:.06em;
        text-transform:uppercase;
    }
    body[data-theme="dark"] .promo-stat-label{ color:#9ca3af; }
    .promo-stat-value{
        font-size:1.2rem;
        font-weight:900;
        letter-spacing:-.03em;
        margin-top:.12rem;
        color:#0f172a;
    }
    body[data-theme="dark"] .promo-stat-value{ color:#f8fafc; }
    .promo-stat-note{
        font-size:.72rem;
        color:#64748b;
        margin-top:.25rem;
        line-height:1.35;
    }
    body[data-theme="dark"] .promo-stat-note{ color:#9ca3af; }
    .promo-stat-inline{
        display:flex;
        align-items:center;
        justify-content:space-between;
        gap:.6rem;
        margin-top:.2rem;
    }
    .promo-stat-inline .pill{
        display:inline-flex;
        align-items:center;
        gap:.25rem;
        padding:.16rem .42rem;
        border-radius:999px;
        border:1px solid rgba(148,163,184,.18);
        background:rgba(148,163,184,.06);
        font-size:.65rem;
        font-weight:800;
        color:#475569;
    }
    body[data-theme="dark"] .promo-stat-inline .pill{
        background:rgba(15,23,42,.8);
        border-color:rgba(51,65,85,.85);
        color:#cbd5e1;
    }

    .promo-filter{
        border-radius:7px; font-size:.82rem; border:1px solid var(--shp-border);
        padding:.35rem .6rem; background:var(--card,#fff); color:inherit; outline:none;
    }
    body[data-theme="dark"] .promo-filter{ background:rgba(15,23,42,.98); }
    .promo-filter:focus{ border-color:var(--shp-accent); }

    .promo-table{ width:100%; margin-bottom:0; border-collapse:collapse; }
    .promo-table thead th{
        position: sticky;
        top: 0;
        z-index: 10;
        border-bottom:1px solid var(--shp-border); font-size:.68rem; color:#64748b;
        background:var(--card,#fff); padding:.58rem .65rem; white-space:nowrap; text-align:left;
        box-shadow: 0 1px 0 rgba(148,163,184,.18);
    }
    body[data-theme="dark"] .promo-table thead th{ background:rgba(15,23,42,.98); color:#9ca3af; box-shadow:0 1px 0 rgba(51,65,85,.85); }
    .promo-table tbody td{
        vertical-align:middle; border-top:1px solid rgba(148,163,184,.16); padding:.52rem .62rem; font-size:.78rem;
    }
    body[data-theme="dark"] .promo-table tbody td{ border-top-color:rgba(51,65,85,.85); }
    .promo-name{ font-weight:700; }
    .promo-muted{ color:#64748b; font-size:.72rem; }
    body[data-theme="dark"] .promo-muted{ color:#9ca3af; }
    .promo-detail-meta{
        display:flex;
        align-items:center;
        gap:.45rem;
        flex-wrap:wrap;
        margin-top:.1rem;
    }
    .promo-cache-badge{
        display:inline-flex;
        align-items:center;
        border-radius:999px;
        padding:.1rem .45rem;
        font-size:.62rem;
        font-weight:800;
        letter-spacing:.03em;
        text-transform:uppercase;
        border:1px solid transparent;
    }
    .promo-cache-live{ background:rgba(22,163,74,.10); color:#15803d; border-color:rgba(22,163,74,.22); }
    .promo-cache-cached{ background:rgba(2,132,199,.10); color:#0369a1; border-color:rgba(2,132,199,.22); }
    .promo-cache-time{ font-size:.68rem; color:#64748b; }
    body[data-theme="dark"] .promo-cache-time{ color:#9ca3af; }
    .promo-product-title{
        display:block;
        max-width:320px;
        overflow:hidden;
        text-overflow:ellipsis;
        white-space:nowrap;
        font-weight:700;
    }

    .promo-badge{
        display:inline-flex; align-items:center; gap:.3rem; border-radius:999px; padding:.15rem .55rem;
        font-size:.68rem; font-weight:800; white-space:nowrap;
    }
    .promo-ongoing{ background:rgba(22,163,74,.12); color:#15803d; }
    .promo-upcoming{ background:rgba(37,99,235,.10); color:#1d4ed8; }
    .promo-ended{ background:rgba(100,116,139,.12); color:#475569; }
    .promo-suspended{ background:rgba(217,119,6,.12); color:#b45309; }

    .promo-json{
        background:#0f172a; color:#e2e8f0; border-radius:8px; padding:.75rem; font-size:.74rem;
        max-height:380px; overflow:auto; white-space:pre-wrap; word-break:break-word;
    }
    body[data-theme="dark"] .promo-json{ background:#020617; }
    .promo-detail-grid{
        display:grid; grid-template-columns:repeat(4, minmax(0,1fr)); gap:.5rem; margin-bottom:.75rem;
    }
    .promo-detail-box{
        border:1px solid var(--shp-border);
        border-radius:12px;
        padding:.45rem .55rem;
        background:rgba(148,163,184,.02);
    }
    .promo-detail-box .lbl{ font-size:.63rem; text-transform:uppercase; letter-spacing:.04em; color:#94a3b8; }
    .promo-detail-box .val{ font-size:.78rem; font-weight:700; margin-top:.08rem; }
    .promo-form-help{ font-size:.72rem; color:#64748b; line-height:1.45; }
    body[data-theme="dark"] .promo-form-help{ color:#9ca3af; }
    .promo-builder{
        border:1px solid rgba(148,163,184,.18);
        border-radius:14px;
        padding:1rem;
        background:rgba(248,250,252,.76);
    }
    body[data-theme="dark"] .promo-builder{
        background:rgba(15,23,42,.98);
        border-color:rgba(51,65,85,.92);
    }
    .promo-builder-head{
        display:flex; justify-content:space-between; align-items:flex-start; gap:.75rem; flex-wrap:wrap;
    }
    .promo-builder-stats{ display:flex; flex-wrap:wrap; gap:.35rem; }
    .promo-builder-stat{
        display:inline-flex; align-items:baseline; gap:.35rem; padding:.18rem .5rem; border-radius:999px;
        border:1px solid rgba(148,163,184,.24); background:rgba(148,163,184,.06); font-size:.7rem; color:#475569;
    }
    body[data-theme="dark"] .promo-builder-stat{
        border-color:rgba(71,85,105,.75); background:rgba(15,23,42,.9); color:#cbd5e1;
    }
    .promo-builder-stat b{ font-size:.78rem; color:var(--shp-accent); }
    body[data-theme="dark"] .promo-builder-stat b{ color:#e2e8f0; }
    .promo-builder-list{ display:grid; gap:.85rem; }
    .promo-builder-item{
        border:1px solid rgba(148,163,184,.18);
        border-radius:12px;
        padding:.8rem;
        background:rgba(255,255,255,.92);
    }
    .promo-builder-item > summary{
        list-style:none;
        cursor:pointer;
    }
    .promo-builder-item > summary::-webkit-details-marker{
        display:none;
    }
    body[data-theme="dark"] .promo-builder-item{
        background:rgba(15,23,42,.88);
        border-color:rgba(51,65,85,.88);
    }
    .promo-builder-item + .promo-builder-item{ margin-top:.15rem; }
    .promo-builder-item-head{
        display:flex; justify-content:space-between; align-items:flex-start; gap:.75rem; flex-wrap:wrap;
        margin-bottom:.75rem;
    }
    .promo-builder-model{
        border:1px solid rgba(148,163,184,.12);
        border-radius:10px;
        padding:.7rem;
        background:rgba(148,163,184,.02);
    }
    .promo-builder-identity{
        display:grid;
        gap:.25rem;
    }
    .promo-builder-identity-lines{
        display:grid;
        gap:.12rem;
        padding:.2rem 0 .05rem;
    }
    .promo-builder-identity-lines .promo-muted{
        line-height:1.35;
    }
    .promo-builder-kode{
        display:grid;
        gap:.1rem;
    }
    .promo-builder-price-grid{
        display:grid;
        grid-template-columns:repeat(3, minmax(0, 1fr));
        gap:.5rem;
    }
    .promo-builder-price-box{
        border:1px solid rgba(148,163,184,.16);
        border-radius:10px;
        padding:.6rem .65rem;
        background:rgba(148,163,184,.03);
    }
    .promo-builder-price-box .lbl{
        font-size:.62rem;
        text-transform:uppercase;
        letter-spacing:.04em;
        color:#94a3b8;
        font-weight:800;
    }
    .promo-builder-price-box .val{
        margin-top:.18rem;
        font-size:.9rem;
        font-weight:800;
        color:#0f172a;
    }
    body[data-theme="dark"] .promo-builder-price-box .val{ color:#f8fafc; }
    .promo-builder-price-box input{
        margin-top:.12rem;
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", monospace;
        font-weight:800;
    }
    .promo-builder-price-box.is-primary{
        background:rgba(59,130,246,.04);
        border-color:rgba(59,130,246,.16);
    }
    .promo-builder-price-box.is-primary .lbl{ color:#2563eb; }
    body[data-theme="dark"] .promo-builder-price-box.is-primary{
        background:rgba(30,64,175,.12);
        border-color:rgba(59,130,246,.25);
    }
    .promo-builder-price-box.is-secondary{
        background:rgba(148,163,184,.04);
    }
    .promo-builder-price-box.is-secondary .lbl{ color:#64748b; }
    .promo-builder-price-box.is-muted{
        background:rgba(148,163,184,.03);
    }
    .promo-builder-price-box.is-muted .val{ color:#64748b; }
    .promo-action-icon{
        width:2rem;
        height:2rem;
        padding:0;
        display:inline-flex;
        align-items:center;
        justify-content:center;
        border-radius:8px;
    }
    .promo-items-preview{
        display:grid;
        gap:.35rem;
    }
    .promo-items-preview .item{
        display:grid;
        gap:.22rem;
        padding:.45rem .55rem;
        border:1px solid rgba(148,163,184,.16);
        border-radius:10px;
        background:rgba(148,163,184,.03);
    }
    .promo-items-preview .item-head{
        display:flex;
        align-items:center;
        gap:.35rem;
        flex-wrap:wrap;
        font-size:.72rem;
        font-weight:700;
        color:#0f172a;
    }
    body[data-theme="dark"] .promo-items-preview .item-head{ color:#e2e8f0; }
    .promo-items-preview .item-head .id{
        color:#0369a1;
    }
    body[data-theme="dark"] .promo-items-preview .item-head .id{ color:#7dd3fc; }
    .promo-items-preview .variants{
        display:flex;
        flex-wrap:wrap;
        gap:.25rem;
    }
    .promo-items-preview .variant-chip{
        display:inline-flex;
        align-items:center;
        gap:.25rem;
        border-radius:999px;
        padding:.1rem .4rem;
        background:rgba(37,99,235,.08);
        color:#1d4ed8;
        font-size:.66rem;
        font-weight:700;
    }
    body[data-theme="dark"] .promo-items-preview .variant-chip{
        background:rgba(37,99,235,.18);
        color:#dbeafe;
    }
    .promo-items-preview .more{
        font-size:.66rem;
        color:#64748b;
        align-self:center;
    }
    body[data-theme="dark"] .promo-items-preview .more{ color:#9ca3af; }
    #promoFormModal .modal-content,
    #promoDetailModal .modal-content{
        border:1px solid var(--shp-border);
        border-radius:14px;
        overflow:hidden;
        box-shadow:0 18px 50px rgba(15,23,42,.12);
    }
    #promoFormModal .modal-header,
    #promoDetailModal .modal-header{
        padding:.85rem .95rem .7rem;
    }
    #promoFormModal .modal-body,
    #promoDetailModal .modal-body{
        padding:.95rem;
    }
    #promoFormModal .modal-footer{
        padding:.75rem .95rem .95rem;
    }
    #promoFormModal .modal-body .form-label,
    #promoDetailModal .modal-body .lbl{
        margin-bottom:.25rem;
    }
    #promoFormModal .modal-body .row.g-3{
        --bs-gutter-y: .75rem;
    }
    #promoFormModal .modal-body .mt-3.border.rounded-3.p-3{
        margin-top:.75rem!important;
        padding:.75rem!important;
    }
    #promoFormModal .modal-body .d-grid.gap-3.mt-3{
        gap:.75rem!important;
        margin-top:.75rem!important;
    }
    #promoDetailModal .modal-body .promo-detail-grid{
        margin-bottom:.65rem;
    }
    #promoDetailModal .modal-body .d-flex.gap-2.flex-wrap.mb-3{
        margin-bottom:.75rem!important;
    }
    body[data-theme="dark"] .promo-builder-model{
        border-color:rgba(51,65,85,.72);
        background:rgba(2,6,23,.35);
    }
    .promo-builder-model.is-inactive{
        opacity:.62;
    }
    .promo-builder-model + .promo-builder-model{ margin-top:.6rem; }
    .promo-builder-identity{
        display:flex;
        flex-direction:column;
        gap:.3rem;
    }
    .promo-builder-identity .form-control{
        min-height:38px;
    }
    .promo-builder-identity .meta{
        font-size:.68rem;
        color:#64748b;
    }
    body[data-theme="dark"] .promo-builder-identity .meta{ color:#9ca3af; }

    .promo-modal-shell{
        display:grid;
        gap:.9rem;
    }
    .promo-modal-hero{
        display:flex;
        justify-content:space-between;
        align-items:flex-start;
        gap:.75rem;
        flex-wrap:wrap;
        padding:.85rem .95rem;
        border:1px solid var(--shp-border);
        border-radius:12px;
        background:rgba(248,250,252,.7);
    }
    body[data-theme="dark"] .promo-modal-hero{
        background:rgba(15,23,42,.72);
    }
    .promo-modal-title{
        margin:0;
        font-size:1rem;
        font-weight:800;
        letter-spacing:-.02em;
    }
    .promo-modal-sub{
        color:#64748b;
        font-size:.75rem;
        margin-top:.15rem;
    }
    body[data-theme="dark"] .promo-modal-sub{ color:#9ca3af; }
    .promo-modal-badges{
        display:flex;
        flex-wrap:wrap;
        gap:.35rem;
        margin-top:.4rem;
    }
    .promo-modal-badge{
        display:inline-flex;
        align-items:center;
        gap:.3rem;
        padding:.18rem .45rem;
        border-radius:999px;
        background:rgba(148,163,184,.08);
        border:1px solid rgba(148,163,184,.18);
        font-size:.68rem;
        font-weight:700;
        color:#475569;
    }
    body[data-theme="dark"] .promo-modal-badge{
        background:rgba(15,23,42,.8);
        border-color:rgba(51,65,85,.85);
        color:#cbd5e1;
    }

    @media (max-width: 768px){
        .promo-wrap{ padding:.5rem .5rem 4rem; }
        .promo-topbar{ margin-inline:-.5rem; }
        .promo-detail-grid{ grid-template-columns:repeat(2, minmax(0,1fr)); }
        .promo-dashboard-grid{ grid-template-columns:repeat(2, minmax(0,1fr)); }
    }
    @media (max-width: 576px){
        .promo-dashboard-grid{ grid-template-columns:1fr; }
    }
</style>
@endpush

@section('content')
<div class="promo-wrap">
    <div class="promo-topbar">
        <div>
            <h1 class="promo-title">Promosi Diskon</h1>
            <div class="promo-sub">Kelola campaign diskon per toko, mulai dari list, detail, sampai tutup promo.</div>
            <div class="promo-chipbar">
                <span class="promo-chip"><span class="lbl">Total</span><span class="val" id="kpiTotal">—</span></span>
                <span class="promo-chip"><span class="lbl">Ongoing</span><span class="val" id="kpiOngoing">—</span></span>
                <span class="promo-chip"><span class="lbl">Upcoming</span><span class="val" id="kpiUpcoming">—</span></span>
                <span class="promo-chip"><span class="lbl">Ended</span><span class="val" id="kpiEnded">—</span></span>
                <span class="promo-chip"><span class="lbl">Items</span><span class="val" id="kpiItems">—</span></span>
            </div>
        </div>
        <div class="promo-controls">
            <a href="{{ route('marketplace.promotions.summary') }}" class="btn btn-pill btn-ship-outline">
                <i class="bi bi-grid-3x3-gap me-1"></i>Summary
            </a>
            <button class="btn btn-pill btn-ship-outline" type="button" onclick="loadPromotions()">
                <i class="bi bi-arrow-repeat me-1"></i>Refresh
            </button>
            <a href="{{ route('marketplace.promotions.create') }}" class="btn btn-pill btn-ship-primary">
                <i class="bi bi-plus-lg me-1"></i>Buat Promosi
            </a>
        </div>
    </div>

    <div class="promo-dashboard mb-3">
        <div class="promo-dashboard-grid">
            <div class="promo-stat-card is-accent">
                <div class="promo-stat-label">Store aktif</div>
                <div class="promo-stat-value" id="summaryStoreName">—</div>
                <div class="promo-stat-note" id="summaryStoreMeta">Pilih toko untuk memuat kampanye Shopee.</div>
            </div>
            <div class="promo-stat-card">
                <div class="promo-stat-label">Total campaign</div>
                <div class="promo-stat-value" id="summaryTotal">—</div>
                <div class="promo-stat-note" id="summaryTotalMeta">Semua campaign untuk filter aktif.</div>
            </div>
            <div class="promo-stat-card">
                <div class="promo-stat-label">Campaign berjalan</div>
                <div class="promo-stat-value" id="summaryOngoing">—</div>
                <div class="promo-stat-note">Campaign yang sedang aktif sekarang.</div>
            </div>
            <div class="promo-stat-card">
                <div class="promo-stat-label">Campaign upcoming</div>
                <div class="promo-stat-value" id="summaryUpcoming">—</div>
                <div class="promo-stat-note">Promo yang sudah dijadwalkan.</div>
            </div>
            <div class="promo-stat-card">
                <div class="promo-stat-label">Campaign ended</div>
                <div class="promo-stat-value" id="summaryEnded">—</div>
                <div class="promo-stat-note">Campaign yang sudah selesai.</div>
            </div>
            <div class="promo-stat-card">
                <div class="promo-stat-label">Total items</div>
                <div class="promo-stat-value" id="summaryItems">—</div>
                <div class="promo-stat-note" id="summaryFilterMeta">Ringkasan item dari promo yang tampil.</div>
            </div>
        </div>
    </div>

    <div class="card promo-card mb-3">
        <div class="card-body p-2">
            <div class="d-flex gap-2 flex-wrap align-items-center">
                <select id="storeSelect" class="form-select form-select-sm promo-filter" style="min-width:240px"></select>
                <select id="statusSelect" class="form-select form-select-sm promo-filter" style="min-width:160px">
                    <option value="ongoing">Ongoing</option>
                    <option value="upcoming">Upcoming</option>
                    <option value="ended">Ended</option>
                    <option value="all">Semua</option>
                </select>
                <input id="searchInput" type="search" class="form-control form-control-sm promo-filter" placeholder="Cari promo / ID / status" style="min-width:240px; flex:1">
                <button class="btn btn-sm btn-ship-outline" type="button" onclick="loadPromotions()">Muat</button>
            </div>
        </div>
    </div>

    <div id="promoAlert" class="alert d-none mb-3" style="border-radius:12px;font-size:.85rem"></div>

    <div class="card promo-card">
        <div class="table-responsive">
            <table class="promo-table">
                <thead>
                    <tr>
                        <th>Promo</th>
                        <th>Status</th>
                        <th>Periode</th>
                        <th>Items</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody id="promoBody">
                    <tr><td colspan="5" class="text-center text-muted py-4">Belum dimuat.</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="promoFormModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title mb-0" id="promoFormTitle">Buat Promosi</h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="promo-modal-shell">
                    <div class="promo-modal-hero">
                        <div>
                            <h6 class="promo-modal-title mb-0">Builder Promosi</h6>
                        </div>
                        <div class="promo-modal-badges">
                            <span class="promo-modal-badge"><i class="bi bi-lightning-charge"></i> Compact</span>
                            <span class="promo-modal-badge"><i class="bi bi-grid-3x3-gap"></i> Shipmints aligned</span>
                        </div>
                    </div>
                    <input type="hidden" id="promoDiscountId">
                    <input type="hidden" id="promoStoreId">
                    <input type="hidden" id="promoDuplicateFromDiscountId">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Nama Diskon</label>
                            <input type="text" id="promoName" class="form-control" placeholder="GFID Flash Sale Januari">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Mulai</label>
                            <input type="text" id="promoStart" class="form-control" data-gf-date="1" autocomplete="off" placeholder="Pilih tanggal & jam">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Selesai</label>
                            <input type="text" id="promoEnd" class="form-control" data-gf-date="1" autocomplete="off" placeholder="Pilih tanggal & jam">
                        </div>
                    <div class="col-12">
                        <div class="d-flex justify-content-end gap-2 flex-wrap">
                            <button type="button" class="btn btn-sm btn-ship-outline" onclick="fillSamplePayload()">Isi Contoh</button>
                            <button type="button" class="btn btn-sm btn-ship-outline" onclick="clearPayload()">Kosongkan</button>
                        </div>
                    </div>
                </div>

                <div class="mt-3 border rounded-3 p-3" style="background:rgba(148,163,184,.04);">
                    <div class="d-flex justify-content-between align-items-start gap-2 flex-wrap">
                        <div class="d-flex gap-2 flex-wrap">
                            <button type="button" class="btn btn-sm btn-ship-outline" onclick="addBuilderItem()">
                                <i class="bi bi-plus-lg me-1"></i>Tambah Item
                            </button>
                            <button type="button" class="btn btn-sm btn-ship-outline" onclick="importPromoJson()">
                                <i class="bi bi-arrow-down-up me-1"></i>Import JSON
                            </button>
                            <button type="button" class="btn btn-sm btn-ship-outline" onclick="clearBuilder()">
                                <i class="bi bi-trash3 me-1"></i>Bersihkan
                            </button>
                        </div>
                    </div>

                    <div id="promoBuilderStats" class="d-flex gap-2 flex-wrap mt-2"></div>
                    <div id="promoBuilderList" class="d-grid gap-3 mt-3"></div>
                    <textarea id="promoItems" class="form-control font-monospace d-none" rows="10" aria-hidden="true"></textarea>
                </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary" id="promoSaveBtn" onclick="savePromotion()">Simpan</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="promoDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title mb-0" id="promoDetailTitle">Detail Promosi</h5>
                    <div class="promo-detail-meta">
                        <div class="promo-muted" id="promoDetailSub"></div>
                        <span id="detailCacheBadge" class="promo-cache-badge promo-cache-live">Live</span>
                        <span id="detailCacheTime" class="promo-cache-time d-none"></span>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="promo-modal-shell">
                    <div class="promo-modal-hero">
                        <div>
                            <h6 class="promo-modal-title mb-0">Detail Campaign</h6>
                        </div>
                        <div class="promo-modal-badges">
                            <span id="detailCacheBadge" class="promo-modal-badge promo-cache-live">Live</span>
                            <span id="detailCacheTime" class="promo-modal-badge d-none"></span>
                        </div>
                    </div>

                    <div class="promo-detail-grid">
                        <div class="promo-detail-box">
                            <div class="lbl">Store</div>
                            <div class="val" id="detailStore">—</div>
                        </div>
                        <div class="promo-detail-box">
                            <div class="lbl">Campaign ID</div>
                            <div class="val" id="detailId">—</div>
                        </div>
                        <div class="promo-detail-box">
                            <div class="lbl">Status</div>
                            <div class="val" id="detailStatus">—</div>
                        </div>
                        <div class="promo-detail-box">
                            <div class="lbl">Items</div>
                            <div class="val" id="detailItems">—</div>
                        </div>
                    </div>

                    <div class="d-flex gap-2 flex-wrap mb-3">
                        <button type="button" class="btn btn-sm btn-outline-success" onclick="duplicateCurrentPromotion()"><i class="bi bi-files me-1"></i>Duplikat</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="refreshCurrentPromotionDetail()"><i class="bi bi-arrow-repeat me-1"></i>Refresh cache</button>
                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="editCurrentPromotion()"><i class="bi bi-pencil me-1"></i>Edit</button>
                        <button type="button" class="btn btn-sm btn-outline-warning" onclick="endCurrentPromotion()"><i class="bi bi-stop-circle me-1"></i>Tutup</button>
                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteCurrentPromotion()"><i class="bi bi-trash me-1"></i>Hapus</button>
                    </div>

                    <div class="table-responsive mb-3">
                        <table class="promo-table">
                            <thead>
                                <tr>
                                    <th>Item ID</th>
                                    <th>Produk</th>
                                    <th class="text-end">Harga Awal</th>
                                    <th class="text-end">Promo %</th>
                                    <th class="text-end">Harga Promo</th>
                                    <th class="text-end">Stok Promo</th>
                                    <th class="text-end">Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="detailItemBody">
                                <tr><td colspan="7" class="text-center text-muted py-4">Belum ada data.</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <pre class="promo-json mt-2" id="detailRaw">{}</pre>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(() => {
    const initialStoreId = @json($selectedStoreId);
    const initialStatus = @json((string) $selectedStatus);
    const { api, esc } = window.mpHelpers;

    const state = {
        stores: [],
        promotions: [],
        detail: null,
        editing: false,
        formDetail: null,
        formMode: 'create',
        builderItems: [],
        pendingFormConfig: null,
    };

    const refs = {
        storeSelect: document.getElementById('storeSelect'),
        statusSelect: document.getElementById('statusSelect'),
        searchInput: document.getElementById('searchInput'),
        promoBody: document.getElementById('promoBody'),
        promoAlert: document.getElementById('promoAlert'),
        kpiTotal: document.getElementById('kpiTotal'),
        kpiOngoing: document.getElementById('kpiOngoing'),
        kpiUpcoming: document.getElementById('kpiUpcoming'),
        kpiEnded: document.getElementById('kpiEnded'),
        kpiItems: document.getElementById('kpiItems'),
        summaryStoreName: document.getElementById('summaryStoreName'),
        summaryStoreMeta: document.getElementById('summaryStoreMeta'),
        summaryTotal: document.getElementById('summaryTotal'),
        summaryTotalMeta: document.getElementById('summaryTotalMeta'),
        summaryOngoing: document.getElementById('summaryOngoing'),
        summaryUpcoming: document.getElementById('summaryUpcoming'),
        summaryEnded: document.getElementById('summaryEnded'),
        summaryItems: document.getElementById('summaryItems'),
        summaryFilterMeta: document.getElementById('summaryFilterMeta'),
        promoFormModal: document.getElementById('promoFormModal'),
        promoDetailModal: document.getElementById('promoDetailModal'),
        promoFormTitle: document.getElementById('promoFormTitle'),
        promoDiscountId: document.getElementById('promoDiscountId'),
        promoName: document.getElementById('promoName'),
        promoStart: document.getElementById('promoStart'),
        promoEnd: document.getElementById('promoEnd'),
        promoItems: document.getElementById('promoItems'),
        promoDuplicateFromDiscountId: document.getElementById('promoDuplicateFromDiscountId'),
        promoBuilderStats: document.getElementById('promoBuilderStats'),
        promoBuilderList: document.getElementById('promoBuilderList'),
        promoSaveBtn: document.getElementById('promoSaveBtn'),
        detailStore: document.getElementById('detailStore'),
        detailId: document.getElementById('detailId'),
        detailStatus: document.getElementById('detailStatus'),
        detailItems: document.getElementById('detailItems'),
        detailItemBody: document.getElementById('detailItemBody'),
        detailRaw: document.getElementById('detailRaw'),
        detailTitle: document.getElementById('promoDetailTitle'),
        detailSub: document.getElementById('promoDetailSub'),
        detailCacheBadge: document.getElementById('detailCacheBadge'),
        detailCacheTime: document.getElementById('detailCacheTime'),
        promoStoreId: document.getElementById('promoStoreId'),
    };

    const formModal = new bootstrap.Modal(refs.promoFormModal);
    const detailModal = new bootstrap.Modal(refs.promoDetailModal);
    let promoStartPicker = null;
    let promoEndPicker = null;

    function initPromoPickers() {
        if (window.GFID?.initDate) {
            promoStartPicker = window.GFID.initDate(refs.promoStart, {
                enableTime: true,
                time_24hr: true,
                dateFormat: 'Y-m-d H:i',
                altFormat: 'j M Y, H:i',
            });
            promoEndPicker = window.GFID.initDate(refs.promoEnd, {
                enableTime: true,
                time_24hr: true,
                dateFormat: 'Y-m-d H:i',
                altFormat: 'j M Y, H:i',
            });
        }
    }

    function setPickerValue(picker, value) {
        if (!picker) return;
        if (!value) {
            picker.clear();
            return;
        }

        const date = value instanceof Date ? value : new Date(String(value).replace(' ', 'T'));
        if (Number.isNaN(date.getTime())) {
            picker.clear();
            return;
        }

        picker.setDate(date, false);
    }

    function setStartTimeLocked(locked) {
        if (!refs.promoStart) return;

        refs.promoStart.disabled = Boolean(locked);
        refs.promoStart.readOnly = Boolean(locked);
        refs.promoStart.setAttribute('aria-disabled', locked ? 'true' : 'false');
    }

    function combineIdentity(id, name) {
        const cleanId = textValue(id).trim();
        const cleanName = textValue(name).trim();

        if (!cleanId && !cleanName) {
            return '';
        }

        return cleanName ? `${cleanId} | ${cleanName}` : cleanId;
    }

    function splitIdentity(value) {
        const raw = textValue(value).trim();
        if (!raw) {
            return { id: '', name: '' };
        }

        const match = raw.match(/^\s*(\d+)\s*(?:[|•\-]\s*(.*))?$/);
        if (match) {
            return {
                id: match[1] || '',
                name: textValue(match[2]).trim(),
            };
        }

        return {
            id: raw,
            name: '',
        };
    }

    function selectAllInput(input) {
        if (!input) return;
        window.requestAnimationFrame(() => {
            if (typeof input.select === 'function') {
                input.select();
            }
        });
    }

    function setBuilderItemIdentity(index, value) {
        if (!state.builderItems[index]) {
            return;
        }

        const identity = splitIdentity(value);
        state.builderItems[index].item_id = identity.id;
        state.builderItems[index].item_name = identity.name;
        syncBuilderPreview();
    }

    function setBuilderModelIdentity(itemIndex, modelIndex, value) {
        const item = state.builderItems[itemIndex];
        if (!item || !item.model_list || !item.model_list[modelIndex]) {
            return;
        }

        const identity = splitIdentity(value);
        item.model_list[modelIndex].model_id = identity.id;
        item.model_list[modelIndex].model_name = identity.name;
        syncBuilderPreview();
    }

    function toast(message, type = 'success') {
        const el = document.createElement('div');
        el.className = `alert alert-${type === 'error' ? 'danger' : type} shadow`;
        el.style.position = 'fixed';
        el.style.right = '16px';
        el.style.bottom = '16px';
        el.style.zIndex = '9999';
        el.style.maxWidth = '420px';
        el.style.margin = '0';
        el.textContent = message;
        document.body.appendChild(el);
        setTimeout(() => {
            el.style.opacity = '0';
            el.style.transition = 'opacity .25s ease';
            setTimeout(() => el.remove(), 250);
        }, 2600);
    }

    function setAlert(message, type = 'info') {
        refs.promoAlert.className = `alert alert-${type === 'error' ? 'danger' : type} mb-3`;
        refs.promoAlert.textContent = message;
        refs.promoAlert.classList.remove('d-none');
    }

    function clearAlert() {
        refs.promoAlert.classList.add('d-none');
        refs.promoAlert.textContent = '';
    }

    function fmtTs(ts) {
        if (!ts) return '—';
        const d = new Date(Number(ts) * 1000);
        return d.toLocaleString('id-ID', {
            day: '2-digit',
            month: 'short',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        });
    }

    function fmtMoney(value) {
        if (value === null || typeof value === 'undefined' || value === '') {
            return '—';
        }

        const num = Number(value);
        if (Number.isNaN(num)) {
            return '—';
        }

        return num.toLocaleString('id-ID');
    }

    function toLocalInput(ts) {
        if (!ts) return '';
        const d = new Date(Number(ts) * 1000 - (new Date().getTimezoneOffset() * 60000));
        return d.toISOString().slice(0, 16);
    }

    function toLocalInputFromDate(date) {
        const d = date instanceof Date ? new Date(date.getTime()) : new Date(date);
        if (Number.isNaN(d.getTime())) return '';
        return new Date(d.getTime() - (d.getTimezoneOffset() * 60000)).toISOString().slice(0, 16);
    }

    function formatCacheTime(value) {
        if (!value) return '';
        const d = new Date(value);
        if (Number.isNaN(d.getTime())) return '';

        return d.toLocaleString('id-ID', {
            day: '2-digit',
            month: 'short',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        });
    }

    function setDetailCacheMeta(isCached, cachedAt = null) {
        if (!refs.detailCacheBadge || !refs.detailCacheTime) {
            return;
        }

        refs.detailCacheBadge.classList.remove('promo-cache-live', 'promo-cache-cached');
        refs.detailCacheBadge.classList.add(isCached ? 'promo-cache-cached' : 'promo-cache-live');
        refs.detailCacheBadge.textContent = isCached ? 'Cached' : 'Live';

        const timeText = formatCacheTime(cachedAt);
        if (timeText) {
            refs.detailCacheTime.textContent = timeText;
            refs.detailCacheTime.classList.remove('d-none');
        } else {
            refs.detailCacheTime.textContent = '';
            refs.detailCacheTime.classList.add('d-none');
        }
    }

    function fromLocalInput(value) {
        if (!value) return null;
        const parsed = new Date(String(value).replace(' ', 'T'));
        if (Number.isNaN(parsed.getTime())) return null;
        return Math.floor(parsed.getTime() / 1000);
    }

    function statusClass(status) {
        const key = String(status || '').toLowerCase();
        if (key === 'ongoing') return 'promo-ongoing';
        if (key === 'upcoming') return 'promo-upcoming';
        if (key === 'ended') return 'promo-ended';
        if (key === 'suspended') return 'promo-suspended';
        return 'promo-ended';
    }

    function normalizeForPayload(items) {
        return (items || []).map((item) => ({
            item_id: Number(item.item_id || 0),
            model_list: (item.model_list || []).map((model) => ({
                model_id: Number(model.model_id || 0),
                model_promotion_price: model.model_promotion_price === '' || model.model_promotion_price === null || typeof model.model_promotion_price === 'undefined'
                    ? null
                    : Number(model.model_promotion_price),
                model_promotion_percentage: model.model_promotion_percentage === '' || model.model_promotion_percentage === null || typeof model.model_promotion_percentage === 'undefined'
                    ? null
                    : Number(model.model_promotion_percentage),
            })),
        })).filter((item) => item.item_id > 0);
    }

    function parseItemList(value) {
        if (!value || !value.trim()) return [];
        const parsed = JSON.parse(value);
        if (!Array.isArray(parsed)) throw new Error('Item JSON harus berupa array.');
        return normalizeForPayload(parsed);
    }

    function textValue(value) {
        return value === null || typeof value === 'undefined' ? '' : String(value);
    }

    function parseBoolean(value, defaultValue = true) {
        if (value === null || typeof value === 'undefined' || value === '') {
            return defaultValue;
        }

        if (typeof value === 'boolean') {
            return value;
        }

        if (typeof value === 'number') {
            return value !== 0;
        }

        const normalized = String(value).trim().toLowerCase();
        if (['0', 'false', 'off', 'no', 'nonaktif', 'inactive'].includes(normalized)) {
            return false;
        }

        if (['1', 'true', 'on', 'yes', 'aktif', 'active'].includes(normalized)) {
            return true;
        }

        return defaultValue;
    }

    function isBuilderModelActive(model) {
        return parseBoolean(model?.active, true);
    }

    function toNumberOrNull(value) {
        if (value === null || typeof value === 'undefined' || value === '') {
            return null;
        }

        const normalized = String(value).replace(/,/g, '').trim();
        if (!normalized) {
            return null;
        }

        const num = Number(normalized);
        return Number.isFinite(num) ? num : null;
    }

    function calculatePromoPercentage(originalPrice, promoPrice) {
        const original = toNumberOrNull(originalPrice);
        const promo = toNumberOrNull(promoPrice);

        if (!original || !promo || original <= 0 || promo <= 0 || promo >= original) {
            return '';
        }

        return String(Math.max(0, Math.round(((original - promo) / original) * 100)));
    }

    function formatPriceLabel(value) {
        const num = toNumberOrNull(value);
        return num === null ? '—' : num.toLocaleString('id-ID');
    }

    function syncBuilderModelPercentage(item, model) {
        const percentage = calculatePromoPercentage(
            model?.model_original_price ?? item?.item_original_price ?? null,
            model?.model_promotion_price ?? null
        );

        if (percentage) {
            model.model_promotion_percentage = percentage;
            return;
        }

        if (!textValue(model?.model_promotion_price).trim()) {
            model.model_promotion_percentage = '';
        }
    }

    function newBuilderModel(model = {}) {
        const originalPrice = model.model_original_price ?? null;
        const promoPrice = textValue(model.model_promotion_price);
        const existingPercentage = textValue(model.model_promotion_percentage);

        return {
            model_id: textValue(model.model_id),
            model_name: textValue(model.model_name),
            model_original_price: textValue(originalPrice),
            model_promotion_price: promoPrice,
            model_promotion_percentage: existingPercentage || calculatePromoPercentage(originalPrice, promoPrice),
            active: isBuilderModelActive(model),
        };
    }

    function newBuilderItem(item = {}) {
        const models = Array.isArray(item.model_list) && item.model_list.length ? item.model_list : [{}];
        const originalPrice = item.item_original_price ?? null;

        return {
            item_id: textValue(item.item_id),
            item_name: textValue(item.item_name),
            item_original_price: textValue(originalPrice),
            model_list: models.map((model) => newBuilderModel({
                ...model,
                model_original_price: model.model_original_price ?? originalPrice,
            })),
        };
    }

    function normalizeBuilderItems(items) {
        const list = Array.isArray(items) ? items : [];
        return list.length ? list.map((item) => newBuilderItem(item)) : [newBuilderItem()];
    }

    function builderToPayload() {
        return (state.builderItems || [])
            .map((item) => {
                const itemId = textValue(item.item_id).trim();
                const models = (item.model_list || [])
                    .filter((model) => isBuilderModelActive(model))
                    .map((model) => {
                        const modelId = textValue(model.model_id).trim();
                        const price = textValue(model.model_promotion_price).trim();
                        const percentage = textValue(model.model_promotion_percentage).trim();

                        if (!price && !percentage) {
                            return null;
                        }

                        return {
                            model_id: modelId,
                            model_promotion_price: price || null,
                            model_promotion_percentage: percentage || null,
                        };
                    })
                    .filter(Boolean);

                if (!itemId || !models.length) {
                    return null;
                }

                return {
                    item_id: itemId,
                    model_list: models,
                };
            })
            .filter(Boolean);
    }

    function renderBuilderStats() {
        if (!refs.promoBuilderStats) {
            return;
        }

        const draftItems = (state.builderItems || []).length;
        const draftModels = (state.builderItems || []).reduce((sum, item) => sum + ((item.model_list || []).length || 0), 0);
        const activeModels = (state.builderItems || []).reduce((sum, item) => sum + ((item.model_list || []).filter((model) => isBuilderModelActive(model)).length || 0), 0);
        const payloadItems = builderToPayload();
        const payloadModels = payloadItems.reduce((sum, item) => sum + (item.model_list || []).length, 0);

        refs.promoBuilderStats.innerHTML = `
            <span class="promo-builder-stat">Draft item <b>${draftItems.toLocaleString('id-ID')}</b></span>
            <span class="promo-builder-stat">Model draft <b>${draftModels.toLocaleString('id-ID')}</b></span>
            <span class="promo-builder-stat">Model aktif <b>${activeModels.toLocaleString('id-ID')}</b></span>
            <span class="promo-builder-stat">Siap kirim <b>${payloadItems.length.toLocaleString('id-ID')}</b></span>
            <span class="promo-builder-stat">Model valid <b>${payloadModels.toLocaleString('id-ID')}</b></span>
        `;
    }

    function syncBuilderPreview() {
        if (!refs.promoItems) {
            return;
        }

        refs.promoItems.value = JSON.stringify(builderToPayload(), null, 2);
        renderBuilderStats();
    }

    function renderBuilder() {
        if (!refs.promoBuilderList) {
            return;
        }

        if (!Array.isArray(state.builderItems) || state.builderItems.length === 0) {
            state.builderItems = [newBuilderItem()];
        }

        refs.promoBuilderList.innerHTML = state.builderItems.map((item, itemIndex) => {
            const models = Array.isArray(item.model_list) && item.model_list.length ? item.model_list : [newBuilderModel()];
            const itemOriginalPrice = textValue(item.item_original_price);

            return `
                <details class="promo-builder-item">
                    <summary class="promo-builder-item-head">
                        <div>
                            <div class="fw-semibold text-dark">${esc(textValue(item.item_name) || 'Item belum diisi')}</div>
                            <div class="promo-muted">${esc(textValue(item.item_id) || '—')}</div>
                        </div>
                        <div class="d-flex gap-2 flex-wrap align-items-center">
                            <i class="bi bi-chevron-down"></i>
                        </div>
                    </summary>

                    <div class="pt-2">
                        <div class="d-flex gap-2 flex-wrap mb-2">
                            <button type="button" class="btn btn-sm btn-ship-outline" onclick="window.__promoApp.addBuilderModel(${itemIndex})">
                                <i class="bi bi-plus-lg me-1"></i>Tambah Model
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="window.__promoApp.removeBuilderItem(${itemIndex})">
                                <i class="bi bi-trash3 me-1"></i>Hapus Item
                            </button>
                        </div>

                        <div class="mt-2">
                        ${models.map((model, modelIndex) => `
                            <div class="promo-builder-model ${isBuilderModelActive(model) ? '' : 'is-inactive'}">
                                <div class="d-flex justify-content-between align-items-center gap-2 flex-wrap mb-2">
                                    <div class="d-flex gap-2 flex-wrap align-items-center">
                                        <span class="badge rounded-pill ${isBuilderModelActive(model) ? 'text-bg-success' : 'text-bg-secondary'}">${isBuilderModelActive(model) ? 'Aktif' : 'Nonaktif'}</span>
                                        <button type="button" class="btn btn-sm ${isBuilderModelActive(model) ? 'btn-outline-warning' : 'btn-outline-success'}" aria-pressed="${isBuilderModelActive(model) ? 'true' : 'false'}" onclick="window.__promoApp.toggleBuilderModelActive(${itemIndex}, ${modelIndex})">
                                            ${isBuilderModelActive(model) ? 'Nonaktifkan' : 'Aktifkan'}
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="window.__promoApp.removeBuilderModel(${itemIndex}, ${modelIndex})">
                                            <i class="bi bi-dash-lg me-1"></i>Hapus Model
                                        </button>
                                    </div>
                                </div>
                                <div class="row g-2 ${isBuilderModelActive(model) ? '' : 'opacity-50'}">
                                    <div class="col-12 col-lg-5">
                                        <div class="promo-builder-identity">
                                            <div class="promo-builder-identity-lines">
                                                <div class="fw-semibold text-dark">${esc(textValue(model.model_name) || '—')}</div>
                                                <div class="promo-muted">${esc(textValue(model.variant_sku_label || model.model_sku || model.model_id) || '—')}</div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-6 col-lg-2">
                                        <div class="promo-builder-price-box is-muted">
                                            <div class="lbl">Harga asli</div>
                                            <div class="val">${formatPriceLabel(model.model_original_price ?? itemOriginalPrice)}</div>
                                        </div>
                                    </div>
                                    <div class="col-6 col-lg-3">
                                        <div class="promo-builder-price-box is-primary">
                                            <div class="lbl">Harga promo</div>
                                            <input type="text" inputmode="decimal" class="form-control form-control-sm" value="${esc(model.model_promotion_price || '')}" placeholder="99000" onfocus="window.__promoApp.selectAll(this)" oninput="window.__promoApp.setBuilderModelField(${itemIndex}, ${modelIndex}, 'model_promotion_price', this.value)" ${isBuilderModelActive(model) ? '' : 'disabled'}>
                                        </div>
                                    </div>
                                    <div class="col-12 col-lg-2">
                                        <div class="promo-builder-price-box is-secondary">
                                            <div class="lbl">Promo %</div>
                                            <input id="builderPercentage-${itemIndex}-${modelIndex}" type="text" inputmode="decimal" class="form-control form-control-sm" value="${esc(model.model_promotion_percentage || '')}" readonly tabindex="-1" ${isBuilderModelActive(model) ? '' : 'disabled'}>
                                        </div>
                                    </div>
                                    <div class="col-12 col-lg-2">
                                        <div class="promo-builder-price-box is-muted">
                                            <div class="lbl">Status</div>
                                            <div class="val">${isBuilderModelActive(model) ? 'Siap promo' : 'Nonaktif'}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        `).join('')}
                    </div>
                    </div>
                </details>
            `;
        }).join('');

        syncBuilderPreview();
    }

    function setBuilderItemField(index, field, value) {
        if (!state.builderItems[index]) {
            return;
        }

        state.builderItems[index][field] = value;
        syncBuilderPreview();
    }

    function setBuilderModelField(itemIndex, modelIndex, field, value) {
        const item = state.builderItems[itemIndex];
        if (!item || !item.model_list || !item.model_list[modelIndex]) {
            return;
        }

        item.model_list[modelIndex][field] = value;
        if (field === 'model_promotion_price') {
            syncBuilderModelPercentage(item, item.model_list[modelIndex]);
            const percentageInput = document.getElementById(`builderPercentage-${itemIndex}-${modelIndex}`);
            if (percentageInput) {
                percentageInput.value = item.model_list[modelIndex].model_promotion_percentage || '';
            }
        }
        syncBuilderPreview();
    }

    function renderItemsPreview(promo) {
        const items = Array.isArray(promo.items_preview) ? promo.items_preview : [];
        const count = Number(promo.item_count || items.length || 0);

        if (!items.length) {
            return `
                <div class="promo-items-preview">
                    <div class="item">
                        <div class="item-head">
                            <span class="id">${count.toLocaleString('id-ID')} item</span>
                            <span>${esc(promo.item_preview_summary || 'Belum ada preview item')}</span>
                        </div>
                    </div>
                </div>
            `;
        }

        const rendered = items.slice(0, 2).map((item) => {
            const variants = Array.isArray(item.model_list) ? item.model_list : [];
            const variantHtml = variants.slice(0, 3).map((model) => {
                const skuCode = model.sku_mapping_code ? ` • ${esc(model.sku_mapping_code)}` : '';
                const variantLabel = [model.model_id, model.model_name].filter(Boolean).join(' • ') || 'variant';
                return `<span class="variant-chip">${esc(variantLabel)}${skuCode}</span>`;
            }).join('');
            const extra = variants.length > 3 ? `<span class="more">+${variants.length - 3}</span>` : '';
            const skuLine = item.sku_mapping_code ? `<span class="promo-muted">SKU: ${esc(item.sku_mapping_code)}</span>` : '';

            return `
                <div class="item">
                    <div class="item-head">
                        <span class="id">${esc(String(item.item_id || '-'))}</span>
                        <span>${esc(item.item_name || item.product_title_label || '—')}</span>
                    </div>
                    ${skuLine}
                    <div class="variants">
                        ${variantHtml || '<span class="promo-muted">—</span>'}
                        ${extra}
                    </div>
                </div>
            `;
        }).join('');

        const more = items.length > 2 ? `<div class="promo-muted">+${items.length - 2} item lain</div>` : '';

        return `<div class="promo-items-preview">${rendered}${more}</div>`;
    }

    function toggleBuilderModelActive(itemIndex, modelIndex) {
        const item = state.builderItems[itemIndex];
        if (!item || !item.model_list || !item.model_list[modelIndex]) {
            return;
        }

        item.model_list[modelIndex].active = !isBuilderModelActive(item.model_list[modelIndex]);
        renderBuilder();
    }

    function addBuilderItem() {
        state.builderItems.push(newBuilderItem());
        renderBuilder();
    }

    function removeBuilderItem(index) {
        if (state.builderItems.length <= 1) {
            state.builderItems = [newBuilderItem()];
        } else {
            state.builderItems.splice(index, 1);
        }

        renderBuilder();
    }

    function addBuilderModel(itemIndex) {
        const item = state.builderItems[itemIndex];
        if (!item) {
            return;
        }

        item.model_list = Array.isArray(item.model_list) ? item.model_list : [];
        item.model_list.push(newBuilderModel());
        renderBuilder();
    }

    function removeBuilderModel(itemIndex, modelIndex) {
        const item = state.builderItems[itemIndex];
        if (!item || !Array.isArray(item.model_list)) {
            return;
        }

        if (item.model_list.length <= 1) {
            item.model_list = [newBuilderModel()];
        } else {
            item.model_list.splice(modelIndex, 1);
        }

        renderBuilder();
    }

    function importPromoJson() {
        try {
            const parsed = parseItemList(refs.promoItems.value);
            state.builderItems = normalizeBuilderItems(parsed);
            renderBuilder();
            toast('Payload JSON berhasil diimpor.');
        } catch (err) {
            toast(err.message || 'JSON promo tidak valid.', 'danger');
        }
    }

    function clearBuilder() {
        state.builderItems = [newBuilderItem()];
        renderBuilder();
    }

    function formatStoreLabel(store) {
        if (!store) return '—';
        return store.channel ? `${store.name} • ${store.channel.name}` : store.name;
    }

    function renderStoreOptions() {
        const selected = initialStoreId ? String(initialStoreId) : '';
        refs.storeSelect.innerHTML = state.stores.map((store) => {
            const isSelected = selected && String(store.id) === selected;
            return `<option value="${store.id}" ${isSelected ? 'selected' : ''}>${esc(formatStoreLabel(store))}</option>`;
        }).join('');

        if (!refs.storeSelect.value && state.stores.length > 0) {
            refs.storeSelect.value = String(state.stores[0].id);
        }
    }

    function renderDashboardSummary(items) {
        const list = Array.isArray(items) ? items : [];
        const total = list.length;
        const ongoing = list.filter((i) => String(i.discount_status).toLowerCase() === 'ongoing').length;
        const upcoming = list.filter((i) => String(i.discount_status).toLowerCase() === 'upcoming').length;
        const ended = list.filter((i) => String(i.discount_status).toLowerCase() === 'ended').length;
        const totalItems = list.reduce((sum, item) => sum + Number(item.item_count || 0), 0);
        const store = state.current;
        const storeName = store?.name || state.stores.find((s) => String(s.id) === String(refs.storeSelect.value))?.name || '—';
        const channelName = store?.channel?.name || state.stores.find((s) => String(s.id) === String(refs.storeSelect.value))?.channel?.name || '';
        const filterLabel = refs.statusSelect?.value || 'ongoing';
        const filterText = filterLabel === 'all'
            ? 'Menampilkan semua status'
            : `Filter status: ${filterLabel}`;

        if (refs.summaryStoreName) refs.summaryStoreName.textContent = storeName;
        if (refs.summaryStoreMeta) refs.summaryStoreMeta.textContent = channelName ? `Channel ${channelName}` : 'Pilih toko untuk memuat kampanye';
        if (refs.summaryTotal) refs.summaryTotal.textContent = total.toLocaleString('id-ID');
        if (refs.summaryTotalMeta) refs.summaryTotalMeta.textContent = `${filterText} · ${totalItems.toLocaleString('id-ID')} item`;
        if (refs.summaryOngoing) refs.summaryOngoing.textContent = ongoing.toLocaleString('id-ID');
        if (refs.summaryUpcoming) refs.summaryUpcoming.textContent = upcoming.toLocaleString('id-ID');
        if (refs.summaryEnded) refs.summaryEnded.textContent = ended.toLocaleString('id-ID');
        if (refs.summaryItems) refs.summaryItems.textContent = totalItems.toLocaleString('id-ID');
        if (refs.summaryFilterMeta) refs.summaryFilterMeta.textContent = filterLabel === 'all'
            ? 'Semua promo sedang ditampilkan di tabel.'
            : `Tabel sedang difilter ke status ${filterLabel}.`;
    }

    function renderKpis(items) {
        const total = items.length;
        const ongoing = items.filter((i) => String(i.discount_status).toLowerCase() === 'ongoing').length;
        const upcoming = items.filter((i) => String(i.discount_status).toLowerCase() === 'upcoming').length;
        const ended = items.filter((i) => String(i.discount_status).toLowerCase() === 'ended').length;
        const promoItems = items.reduce((sum, item) => sum + Number(item.item_count || 0), 0);

        refs.kpiTotal.textContent = total.toLocaleString('id-ID');
        refs.kpiOngoing.textContent = ongoing.toLocaleString('id-ID');
        refs.kpiUpcoming.textContent = upcoming.toLocaleString('id-ID');
        refs.kpiEnded.textContent = ended.toLocaleString('id-ID');
        refs.kpiItems.textContent = promoItems.toLocaleString('id-ID');
        renderDashboardSummary(items);
    }

    function rowActions(promo) {
        return `
            <div class="d-flex gap-1 justify-content-end flex-wrap">
                <button class="btn btn-sm btn-outline-primary promo-action-icon" type="button" title="Detail" aria-label="Detail" onclick="window.__promoApp.openDetail(${promo.discount_id})"><i class="bi bi-eye"></i></button>
                <button class="btn btn-sm btn-outline-success promo-action-icon" type="button" title="Duplikat" aria-label="Duplikat" onclick="window.__promoApp.duplicateFromList(${promo.discount_id})"><i class="bi bi-files"></i></button>
                <button class="btn btn-sm btn-outline-secondary promo-action-icon" type="button" title="Edit" aria-label="Edit" onclick="window.__promoApp.openEdit(${promo.discount_id})"><i class="bi bi-pencil"></i></button>
                <button class="btn btn-sm btn-outline-warning promo-action-icon" type="button" title="Tutup" aria-label="Tutup" onclick="window.__promoApp.endPromotion(${promo.discount_id})"><i class="bi bi-stop-circle"></i></button>
                <button class="btn btn-sm btn-outline-danger promo-action-icon" type="button" title="Hapus" aria-label="Hapus" onclick="window.__promoApp.deletePromotion(${promo.discount_id})"><i class="bi bi-trash"></i></button>
            </div>
        `;
    }

    function renderPromotions() {
        const term = refs.searchInput.value.trim().toLowerCase();
        const rows = state.promotions.filter((promo) => {
            if (!term) return true;
            return [
                promo.discount_name,
                promo.discount_status,
                promo.discount_id,
                promo.start_label,
                promo.end_label,
            ].some((value) => String(value || '').toLowerCase().includes(term));
        });

        if (!rows.length) {
            refs.promoBody.innerHTML = `<tr><td colspan="5" class="text-center text-muted py-4">Tidak ada data promo untuk filter ini.</td></tr>`;
            renderKpis(state.promotions);
            return;
        }

        refs.promoBody.innerHTML = rows.map((promo) => `
            <tr>
                <td>
                    <div class="promo-name">${esc(promo.discount_name || '-')}</div>
                    <div class="promo-muted">ID: ${esc(String(promo.discount_id || '-'))}${state.current?.channel ? ` • ${esc(state.current.channel.name)}` : ''}</div>
                </td>
                <td><span class="promo-badge ${statusClass(promo.discount_status)}">${esc(promo.status_label || promo.discount_status || '-')}</span></td>
                <td>
                    <div>${esc(promo.start_label || fmtTs(promo.start_time))}</div>
                    <div class="promo-muted">${esc(promo.end_label || fmtTs(promo.end_time))}</div>
                </td>
                <td>
                    <div class="fw-bold">${Number(promo.item_count || 0).toLocaleString('id-ID')}</div>
                    <div class="promo-muted">item</div>
                </td>
                <td class="text-end">${rowActions(promo)}</td>
            </tr>
        `).join('');

        renderKpis(state.promotions);
    }

    async function loadStores() {
        const data = await api('/api/marketplace/stores');
        state.stores = Array.isArray(data)
            ? data.filter((store) => {
                const channelCode = String(store?.channel?.code || '').toLowerCase();
                return store?.is_active !== false && (channelCode === 'shopee' || channelCode === 'shp');
            })
            : [];
        renderStoreOptions();
    }

    async function loadPromotions() {
        clearAlert();
        const storeId = refs.storeSelect.value;
        const status = refs.statusSelect.value;

        if (!storeId) {
            refs.promoBody.innerHTML = `<tr><td colspan="5" class="text-center text-muted py-4">Belum ada toko marketplace.</td></tr>`;
            renderKpis([]);
            return;
        }

        refs.promoBody.innerHTML = `<tr><td colspan="5" class="text-center text-muted py-4">Memuat promosi...</td></tr>`;

        try {
            const params = new URLSearchParams({ store_id: storeId, status });
            const res = await api(`/api/marketplace/promotions?${params.toString()}`);
            state.promotions = Array.isArray(res.promotions) ? res.promotions : [];
            state.current = res.store || null;

            if (state.current?.channel?.code && state.current.channel.code !== 'shopee' && state.current.channel.code !== 'shp') {
                setAlert(`Toko ${state.current.name} terhubung ke ${state.current.channel.name}. Endpoint diskon ini masih paling optimal untuk Shopee.`, 'warning');
            }

            renderPromotions();
        } catch (err) {
            state.promotions = [];
            refs.promoBody.innerHTML = `<tr><td colspan="5" class="text-center text-danger py-4">${esc(err.message || 'Gagal memuat promosi')}</td></tr>`;
            renderKpis([]);
            setAlert(err.message || 'Gagal memuat promosi', 'danger');
        }
    }

    function fillSamplePayload() {
        state.builderItems = normalizeBuilderItems([
            {
                item_id: '123456789',
                item_name: 'Sample Item',
                model_list: [
                    {
                        model_id: '0',
                        model_name: 'Default',
                        model_promotion_price: '99000',
                    },
                ],
            },
        ]);

        renderBuilder();
    }

    function clearPayload() {
        clearBuilder();
    }

    function computeDuplicateSchedule(endTime) {
        const sourceEnd = endTime ? new Date(Number(endTime) * 1000) : new Date();
        const base = Number.isNaN(sourceEnd.getTime()) ? new Date() : sourceEnd;
        const start = new Date(base);
        start.setDate(start.getDate() + 1);
        start.setHours(0, 0, 0, 0);

        const end = new Date(start.getFullYear(), start.getMonth() + 1, 0, 23, 59, 0, 0);

        return {
            startValue: toLocalInputFromDate(start),
            endValue: toLocalInputFromDate(end),
        };
    }

    function buildDuplicateConfig(detail) {
        const schedule = computeDuplicateSchedule(detail?.end_time);

        return {
            mode: 'duplicate',
            discountName: detail?.discount_name ? `${detail.discount_name} (Copy)` : 'Promo Copy',
            startValue: schedule.startValue,
            endValue: schedule.endValue,
            duplicateFromDiscountId: detail?.discount_id || '',
            items: detail?.items || [],
        };
    }

    function openFormModal(detail = null, options = {}) {
        const mode = options.mode || (detail ? 'edit' : 'create');
        const activeStoreId = detail?.store?.id || refs.storeSelect.value;
        const discountName = Object.prototype.hasOwnProperty.call(options, 'discountName')
            ? options.discountName
            : detail?.discount_name || '';
        const startValue = Object.prototype.hasOwnProperty.call(options, 'startValue')
            ? options.startValue
            : toLocalInput(detail?.start_time);
        const endValue = Object.prototype.hasOwnProperty.call(options, 'endValue')
            ? options.endValue
            : toLocalInput(detail?.end_time);
        const duplicateFromDiscountId = Object.prototype.hasOwnProperty.call(options, 'duplicateFromDiscountId')
            ? options.duplicateFromDiscountId
            : '';
        const builderSource = Object.prototype.hasOwnProperty.call(options, 'items')
            ? options.items
            : (detail?.items || []);

        if (!activeStoreId) {
            toast('Pilih toko terlebih dulu.', 'warning');
            return;
        }

        state.editing = mode === 'edit';
        state.formDetail = detail || null;
        state.formMode = mode;
        refs.promoFormTitle.textContent = mode === 'edit'
            ? 'Edit Promosi'
            : (mode === 'duplicate' ? 'Duplikat Promosi' : 'Buat Promosi');
        refs.promoDiscountId.value = mode === 'edit' ? (detail?.discount_id || '') : '';
        refs.promoStoreId.value = activeStoreId;
        refs.promoDuplicateFromDiscountId.value = duplicateFromDiscountId || '';
        refs.promoName.value = discountName || '';
        setPickerValue(promoStartPicker, startValue || '');
        setPickerValue(promoEndPicker, endValue || '');
        setStartTimeLocked(mode === 'edit' && String(detail?.discount_status || '').toLowerCase() === 'ongoing');
        state.builderItems = normalizeBuilderItems(builderSource || []);
        renderBuilder();
        formModal.show();
    }

    function openPendingFormModal() {
        if (!state.pendingFormConfig) {
            return;
        }

        const config = state.pendingFormConfig;
        state.pendingFormConfig = null;
        openFormModal(config.detail || state.detail, config);
    }

    async function fetchPromotionDetail(storeId, discountId, forceRefresh = false) {
        const params = forceRefresh ? '?refresh=1' : '';
        const res = await api(`/api/marketplace/promotions/${storeId}/${discountId}${params}`);
        return res;
    }

    async function openDetail(discountId, forceRefresh = false) {
        const storeId = refs.storeSelect.value;
        if (!storeId) {
            toast('Pilih toko terlebih dahulu.', 'warning');
            return;
        }

            refs.detailItemBody.innerHTML = `<tr><td colspan="7" class="text-center text-muted py-4">Memuat detail...</td></tr>`;
        refs.detailRaw.textContent = '{}';
        setDetailCacheMeta(false);

        try {
            const res = await fetchPromotionDetail(storeId, discountId, forceRefresh);
            state.detail = res.promotion || null;
            state.detailCached = Boolean(res.cached);
            state.detailCachedAt = res.cached_at || null;

            const detail = state.detail;
            refs.promoStoreId.value = detail?.store?.id || storeId;
            refs.detailTitle.textContent = detail?.discount_name || 'Detail Promosi';
            refs.detailSub.textContent = `Campaign #${detail?.discount_id || discountId}`;
            setDetailCacheMeta(Boolean(res.cached), res.cached_at || null);
            refs.detailStore.textContent = detail?.store ? formatStoreLabel(detail.store) : '—';
            refs.detailId.textContent = detail?.discount_id || discountId;
            refs.detailStatus.innerHTML = `<span class="promo-badge ${statusClass(detail?.discount_status)}">${esc(detail?.status_label || detail?.discount_status || '-')}</span>`;
            refs.detailItems.textContent = Number(detail?.item_count || 0).toLocaleString('id-ID');
            refs.detailRaw.textContent = JSON.stringify(detail?.raw || res.raw || {}, null, 2);

            const itemRows = [];
            (detail?.items || []).forEach((item) => {
                const models = Array.isArray(item.model_list) && item.model_list.length ? item.model_list : [{}];
                models.forEach((model) => {
                    const promoPercentage = model.model_promotion_percentage !== null && typeof model.model_promotion_percentage !== 'undefined' && model.model_promotion_percentage !== ''
                        ? Number(model.model_promotion_percentage)
                        : (() => {
                            const original = Number(model.model_original_price ?? item.item_original_price ?? 0);
                            const promoPrice = Number(model.model_promotion_price ?? 0);
                            if (!original || !promoPrice || promoPrice >= original) {
                                return null;
                            }
                            return Math.max(0, Math.round(((original - promoPrice) / original) * 100));
                        })();
                    const promoStock = Number(model.promo_stock ?? item.promo_stock ?? 0);
                    const variantLabel = model.model_name || model.variant_sku_label || model.model_sku || model.model_id || '—';
                    const variantCode = model.sku_mapping_code || item.sku_mapping_code || '';
                    const variantLine = variantCode ? `${variantLabel} • ${variantCode}` : variantLabel;
                    itemRows.push(`
                        <tr>
                            <td>${esc(String(item.item_id || '-'))}</td>
                            <td>
                                <div class="promo-product-title" title="${esc(item.product_title_label || item.item_name || '—')}">${esc(item.product_title_label || item.item_name || '—')}</div>
                                <div class="promo-muted">${esc(variantLine)}</div>
                                ${variantCode ? `<div class="promo-muted">SKU mapping: ${esc(variantCode)}</div>` : ''}
                            </td>
                            <td class="text-end">${fmtMoney(model.model_original_price ?? item.item_original_price)}</td>
                            <td class="text-end">${promoPercentage !== null ? promoPercentage.toLocaleString('id-ID') + '%' : '—'}</td>
                            <td class="text-end">${fmtMoney(model.model_promotion_price)}</td>
                            <td class="text-end">${promoStock.toLocaleString('id-ID')}</td>
                            <td class="text-end">
                                <button class="btn btn-sm btn-outline-warning" type="button" onclick="window.__promoApp.deletePromoItem(${detail.discount_id}, ${item.item_id}, ${Number(model.model_id || 0)})">Nonaktifkan</button>
                            </td>
                        </tr>
                    `);
                });
            });

            refs.detailItemBody.innerHTML = itemRows.length
                ? itemRows.join('')
                : `<tr><td colspan="7" class="text-center text-muted py-4">Belum ada item di promo ini.</td></tr>`;

            detailModal.show();
        } catch (err) {
            refs.detailItemBody.innerHTML = `<tr><td colspan="7" class="text-center text-danger py-4">${esc(err.message || 'Gagal memuat detail')}</td></tr>`;
            toast(err.message || 'Gagal memuat detail', 'danger');
        }
    }

    function refreshCurrentPromotionDetail() {
        if (!state.detail?.discount_id) {
            toast('Detail promo belum dimuat.', 'warning');
            return;
        }

        openDetail(state.detail.discount_id, true);
    }

    function queueDuplicatePromotion(detail = null) {
        const targetDetail = detail || state.detail;
        if (!targetDetail) {
            toast('Detail promo belum dimuat.', 'warning');
            return;
        }

        state.pendingFormConfig = {
            ...buildDuplicateConfig(targetDetail),
            detail: targetDetail,
        };

        if (refs.promoDetailModal.classList.contains('show')) {
            detailModal.hide();
            window.setTimeout(() => {
                openPendingFormModal();
            }, 120);
            return;
        }

        openPendingFormModal();
    }

    function queueEditPromotion(detail = null) {
        const targetDetail = detail || state.detail;
        if (!targetDetail) {
            toast('Detail promo belum dimuat.', 'warning');
            return;
        }

        state.pendingFormConfig = {
            mode: 'edit',
            detail: targetDetail,
        };

        if (refs.promoDetailModal.classList.contains('show')) {
            detailModal.hide();
            window.setTimeout(() => {
                openPendingFormModal();
            }, 120);
            return;
        }

        openPendingFormModal();
    }

    async function openEditPromotion(discountId) {
        const storeId = refs.storeSelect.value;
        if (!storeId) {
            toast('Pilih toko terlebih dahulu.', 'warning');
            return;
        }

        refs.promoSaveBtn.disabled = true;
        refs.promoSaveBtn.textContent = 'Memuat...';

        try {
            const res = await fetchPromotionDetail(storeId, discountId, false);
            const detail = res.promotion || null;

            if (!detail) {
                throw new Error('Detail promo tidak ditemukan.');
            }

            state.detail = detail;
            state.detailCached = Boolean(res.cached);
            state.detailCachedAt = res.cached_at || null;

            openFormModal(detail, {
                mode: 'edit',
                detail,
                discountName: detail.discount_name || '',
                startValue: toLocalInput(detail.start_time),
                endValue: toLocalInput(detail.end_time),
                items: detail.items || [],
            });
        } catch (err) {
            toast(err.message || 'Gagal membuka editor promo.', 'danger');
        } finally {
            refs.promoSaveBtn.disabled = false;
            refs.promoSaveBtn.textContent = 'Simpan';
        }
    }

    async function savePromotion() {
        const discountId = refs.promoDiscountId.value.trim();
        const storeId = refs.promoStoreId.value || refs.storeSelect.value;
        const itemList = builderToPayload();
        const payload = {
            discount_name: refs.promoName.value.trim(),
            start_time: fromLocalInput(refs.promoStart.value),
            end_time: fromLocalInput(refs.promoEnd.value),
            item_list: itemList,
            duplicate_from_discount_id: refs.promoDuplicateFromDiscountId.value.trim() || null,
        };

        if (!payload.item_list.length) {
            toast('Tambahkan minimal satu item promo yang lengkap.', 'warning');
            return;
        }

        if (!payload.discount_name) {
            toast('Nama promo wajib diisi.', 'warning');
            return;
        }

        if (!payload.start_time || !payload.end_time) {
            toast('Waktu mulai dan selesai wajib diisi.', 'warning');
            return;
        }

        if (payload.end_time <= payload.start_time) {
            toast('Waktu selesai harus lebih besar dari waktu mulai.', 'warning');
            return;
        }

        refs.promoSaveBtn.disabled = true;
        refs.promoSaveBtn.textContent = 'Menyimpan...';

        try {
            const url = discountId
                ? `/api/marketplace/promotions/${storeId}/${discountId}/update`
                : '/api/marketplace/promotions';
            const res = await api(url, {
                method: 'POST',
                body: JSON.stringify({ ...payload, store_id: storeId }),
            });

            toast(res.message || 'Promo tersimpan.');
            formModal.hide();
            await loadPromotions();
            if (res.discount_id) {
                await openDetail(res.discount_id);
            }
        } catch (err) {
            toast(err.message || 'Gagal menyimpan promo.', 'danger');
        } finally {
            refs.promoSaveBtn.disabled = false;
            refs.promoSaveBtn.textContent = 'Simpan';
        }
    }

    async function endPromotion(discountId) {
        const storeId = refs.storeSelect.value;
        if (!confirm('Tutup campaign promosi ini?')) return;
        try {
            const res = await api(`/api/marketplace/promotions/${storeId}/${discountId}/end`, { method: 'POST', body: JSON.stringify({}) });
            toast(res.message || 'Promo ditutup.');
            await loadPromotions();
        } catch (err) {
            toast(err.message || 'Gagal menutup promo.', 'danger');
        }
    }

    async function deletePromotion(discountId) {
        const storeId = refs.storeSelect.value;
        if (!confirm('Hapus campaign promosi ini? Tindakan ini tidak bisa dibatalkan.')) return;
        try {
            const res = await api(`/api/marketplace/promotions/${storeId}/${discountId}/delete`, { method: 'POST', body: JSON.stringify({}) });
            toast(res.message || 'Promo dihapus.');
            state.detail = null;
            detailModal.hide();
            await loadPromotions();
        } catch (err) {
            toast(err.message || 'Gagal menghapus promo.', 'danger');
        }
    }

    async function deletePromoItem(discountId, itemId, modelId = 0) {
        const storeId = refs.storeSelect.value;
        if (!confirm(`Nonaktifkan variant ${itemId} dari promo ini?`)) return;
        try {
            const res = await api(`/api/marketplace/promotions/${storeId}/${discountId}/delete-item`, {
                method: 'POST',
                body: JSON.stringify({ item_id: itemId, model_id: modelId }),
            });
            toast(res.message || 'Variant dinonaktifkan.');
            await openDetail(discountId);
            await loadPromotions();
        } catch (err) {
            toast(err.message || 'Gagal menghapus item.', 'danger');
        }
    }

    function editCurrentPromotion() {
        queueEditPromotion(state.detail);
    }

    function duplicateCurrentPromotion() {
        queueDuplicatePromotion(state.detail);
    }

    async function duplicateFromList(discountId) {
        const storeId = refs.storeSelect.value;
        if (!storeId) {
            toast('Pilih toko terlebih dahulu.', 'warning');
            return;
        }

        refs.promoSaveBtn.disabled = true;
        refs.promoSaveBtn.textContent = 'Memuat...';

        try {
            const res = await fetchPromotionDetail(storeId, discountId, false);
            const detail = res.promotion || null;

            if (!detail) {
                throw new Error('Detail promo tidak ditemukan.');
            }

            state.detail = detail;
            state.detailCached = Boolean(res.cached);
            state.detailCachedAt = res.cached_at || null;
            queueDuplicatePromotion(detail);
        } catch (err) {
            toast(err.message || 'Gagal membuka duplikat promo.', 'danger');
        } finally {
            refs.promoSaveBtn.disabled = false;
            refs.promoSaveBtn.textContent = 'Simpan';
        }
    }

    function endCurrentPromotion() {
        if (!state.detail) return;
        endPromotion(state.detail.discount_id);
    }

    function deleteCurrentPromotion() {
        if (!state.detail) return;
        deletePromotion(state.detail.discount_id);
    }

    refs.storeSelect.addEventListener('change', loadPromotions);
    refs.statusSelect.value = initialStatus || 'ongoing';
    refs.statusSelect.addEventListener('change', loadPromotions);
    refs.searchInput.addEventListener('input', renderPromotions);
    refs.promoDetailModal.addEventListener('hidden.bs.modal', () => {
        openPendingFormModal();
    });
    window.__promoApp = {
        openDetail,
        openEdit: openEditPromotion,
        endPromotion,
        deletePromotion,
        deletePromoItem,
        addBuilderItem,
        removeBuilderItem,
        addBuilderModel,
        removeBuilderModel,
        setBuilderItemField,
        setBuilderItemIdentity,
        setBuilderModelField,
        setBuilderModelIdentity,
        toggleBuilderModelActive,
        importPromoJson,
        clearBuilder,
        duplicateCurrentPromotion,
        duplicateFromList,
        refreshCurrentPromotionDetail,
        selectAll: selectAllInput,
    };

    document.addEventListener('DOMContentLoaded', async () => {
        try {
            initPromoPickers();
            await loadStores();
            if (initialStatus) refs.statusSelect.value = initialStatus;
            await loadPromotions();
        } catch (err) {
            setAlert(err.message || 'Gagal inisialisasi halaman promosi.', 'danger');
        }
    });

    window.loadPromotions = loadPromotions;
    window.openFormModal = () => openFormModal();
    window.savePromotion = savePromotion;
    window.fillSamplePayload = fillSamplePayload;
    window.clearPayload = clearPayload;
    window.addBuilderItem = addBuilderItem;
    window.removeBuilderItem = removeBuilderItem;
    window.addBuilderModel = addBuilderModel;
    window.removeBuilderModel = removeBuilderModel;
    window.setBuilderItemField = setBuilderItemField;
    window.setBuilderModelField = setBuilderModelField;
    window.toggleBuilderModelActive = toggleBuilderModelActive;
    window.importPromoJson = importPromoJson;
    window.clearBuilder = clearBuilder;
    window.editCurrentPromotion = editCurrentPromotion;
    window.duplicateCurrentPromotion = duplicateCurrentPromotion;
    window.refreshCurrentPromotionDetail = refreshCurrentPromotionDetail;
    window.endCurrentPromotion = endCurrentPromotion;
    window.deleteCurrentPromotion = deleteCurrentPromotion;
})();
</script>
@endpush
