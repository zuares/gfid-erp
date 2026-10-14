@extends('layouts.app')
@section('title', 'Marketplace • Produk')

@push('head')
<style>
    :root{
        --prd-accent:#334155;
        --prd-accent-2:#1f2937;
        --prd-border:rgba(148,163,184,.18);
        --prd-muted:#64748b;
        --prd-surface:#ffffff;
        --prd-shadow:0 10px 30px rgba(15,23,42,.06);
        --prd-radius:12px;
        --prd-focus:rgba(37,99,235,.22);
    }
    .page-wrap{ max-width:1440px; margin-inline:auto; padding:.85rem 1rem 4rem; background:transparent!important; }

    /* ── Topbar (selaras shipment) ── */
    .prd-topbar{
        position:sticky; top:0; z-index:300;
        display:flex; justify-content:space-between; align-items:center; gap:.6rem; flex-wrap:wrap;
        padding:.7rem .85rem; margin-inline:-1rem; margin-bottom:.85rem;
        background:var(--card,#fff); background:color-mix(in srgb, var(--card,#fff) 94%, transparent); border-bottom:1px solid var(--prd-border);
        box-shadow:0 4px 14px rgba(15,23,42,.04); backdrop-filter:blur(12px);
    }
    body[data-theme="dark"] .prd-topbar{ background:var(--card,#0f172a); }
    body[data-theme="dark"] .store-tabs-shell, body[data-theme="dark"] .prd-change-log-row{ --prd-surface:#0f172a; background:var(--card,#0f172a); }
    .title{ font-weight:800; font-size:1.08rem; margin:0; letter-spacing:-.01em; }
    .sub{ color:var(--prd-muted); font-size:.78rem; }
    body[data-theme="dark"] .sub{ color:#9ca3af; }

    .kpis{ display:flex; flex-wrap:wrap; gap:.32rem; margin-top:.35rem; }
    .kpi{
        display:inline-flex; align-items:baseline; gap:.45rem; cursor:pointer;
        border-radius:9px; padding:.28rem .55rem; border:1px solid rgba(148,163,184,.28);
        background:transparent; font-size:.72rem; user-select:none;
        transition:background .15s,border-color .15s,transform .15s;
    }
    .kpi:hover{ background:rgba(148,163,184,.08); border-color:rgba(51,65,85,.38); transform:translateY(-1px); }
    .kpi.active{ border-color:var(--prd-accent); background:rgba(51,65,85,.06); }
    body[data-theme="dark"] .kpi{ background:rgba(15,23,42,.96); border-color:rgba(51,65,85,.85); }
    .kpi .lbl{ font-size:.66rem; color:#94a3b8; }
    .kpi .val{ font-weight:650; color:var(--prd-accent); }
    body[data-theme="dark"] .kpi .val{ color:#cbd5e1; }

    .controls{ display:flex; gap:.5rem; align-items:center; flex-wrap:wrap; }
    .btn-pill{ border-radius:9px; padding:.48rem .82rem; min-height:36px; box-shadow:none!important; font-weight:650; font-size:.8rem; }
    .btn-prd-primary{ background:var(--prd-accent)!important; border-color:var(--prd-accent)!important; color:#fff!important; }
    .btn-prd-primary:hover{ background:var(--prd-accent-2)!important; border-color:var(--prd-accent-2)!important; }
    .btn-prd-outline{ color:#475569!important; background:transparent!important; border:1px solid rgba(148,163,184,.35)!important; }
    .btn-prd-outline:hover{ background:rgba(148,163,184,.08)!important; }
    .btn:focus-visible, .form-control:focus, .form-select:focus, .store-tab:focus-visible, .pt-tab:focus-visible{ box-shadow:0 0 0 3px var(--prd-focus)!important; outline:0; }

    .workspace-intro{ display:flex; align-items:center; gap:.45rem; margin-bottom:.55rem; color:var(--prd-muted); font-size:.75rem; }
    .workspace-intro .dot{ width:7px; height:7px; border-radius:999px; background:#22c55e; box-shadow:0 0 0 3px rgba(34,197,94,.12); flex:none; }
    .store-tabs-shell{ display:flex; align-items:center; gap:.45rem; overflow-x:auto; border:1px solid var(--prd-border); border-radius:var(--prd-radius) var(--prd-radius) 0 0; background:var(--prd-surface); padding:.15rem .45rem 0; scrollbar-width:none; }
    .store-tabs-shell::-webkit-scrollbar{ display:none; }
    .filter-panel{ background:rgba(148,163,184,.035); padding:.7rem .8rem .55rem; border-radius:0 0 var(--prd-radius) var(--prd-radius); border:1px solid var(--prd-border); border-top:0; margin-bottom:.8rem; }
    .filter-panel-heading{ display:flex; align-items:center; gap:.45rem; margin-bottom:.35rem; color:var(--prd-muted); font-size:.7rem; font-weight:750; }
    .filter-panel-heading .filter-count{ margin-left:auto; }
    .filter-bar{ margin-bottom:0; }

    /* ── Filter bar ── */
    .filter-bar{
        display:flex; gap:.5rem; align-items:center; flex-wrap:wrap; margin-bottom:.65rem;
    }
    .filter-select{ border-radius:7px; padding-left:.6rem; padding-right:1.8rem; font-size:.8rem; max-width:170px; }
    .filter-search{ border-radius:7px; font-size:.8rem; max-width:250px; }
    .filter-count{ font-size:.72rem; color:var(--prd-muted); margin-left:auto; }
    .filter-reset{ font-size:.7rem; color:#b91c1c; cursor:pointer; text-decoration:underline; display:none; }

    /* ── Card + table (selaras shipment) ── */
    .card-main{
        background:var(--card,#fff); border-radius:var(--prd-radius); border:1px solid var(--prd-border); overflow:hidden; box-shadow:var(--prd-shadow);
    }
    body[data-theme="dark"] .card-main{ border-color:rgba(51,65,85,.85); }
    .table-list{ width:100%; margin-bottom:0; border-collapse:collapse; }
    .table-list thead th{
        border-bottom:1px solid var(--prd-border); font-size:.68rem; color:#64748b;
        background:var(--card,#fff); padding:.52rem .62rem; white-space:nowrap; text-align:left;
        position: sticky; top: 0; z-index: 10;
        box-shadow: 0 1px 2px rgba(0,0,0,0.04);
    }
    body[data-theme="dark"] .table-list thead th{ background:rgba(15,23,42,.98); color:#9ca3af; }
    .table-list tbody td{
        vertical-align:middle; border-top:1px solid rgba(148,163,184,.16);
        padding:.52rem .62rem; font-size:.78rem;
    }
    body[data-theme="dark"] .table-list tbody td{ border-top-color:rgba(51,65,85,.85); }
    .table-list tbody tr[data-pid]:hover td{ background:rgba(59,130,246,.035); }
    .table-list tbody tr[data-pid]{ transition:background .15s; }
    .table-list th:last-child, .table-list td:last-child{ min-width:180px; }

    .prd-img{ width:46px; height:46px; border-radius:9px; object-fit:cover; background:#f1f5f9; border:1px solid rgba(148,163,184,.2); }
    .prd-name{ 
        font-weight:700; color:inherit; max-width:280px; line-height:1.3;
        display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden; text-overflow:ellipsis;
    }
    .prd-sku{ font-size:.67rem; color:#94a3b8; }
    .muted{ font-size:.74rem; color:#6b7280; }
    .prd-money{ font-weight:700; white-space:nowrap; color:#334155; }
    .prd-money.promo{ color:#b45309; }
    .prd-money.buyer{ color:#0f766e; }
    .prd-no{ color:#64748b; font-size:.7rem!important; font-weight:800; text-align:center; }
    .prd-product-link{ display:block; color:inherit; text-decoration:none; cursor:pointer; }
    .prd-product-link:hover .prd-name{ color:#2563eb; text-decoration:underline; text-decoration-thickness:1px; text-underline-offset:2px; }

    /* Badge status ala shipment (dengan titik) */
    .badge-status{
        border-radius:7px; padding:.16rem .48rem; font-size:.68rem;
        border:1px solid transparent; display:inline-flex; align-items:center; gap:.35rem; white-space:nowrap;
    }
    .badge-status::before{ content:''; width:7px; height:7px; border-radius:999px; display:inline-block; }
    .st-normal{ background:rgba(34,197,94,.10); color:#166534; border-color:rgba(34,197,94,.30); }
    .st-normal::before{ background:rgba(34,197,94,.95); }
    .st-unlist{ background:rgba(148,163,184,.10); color:#475569; border-color:rgba(148,163,184,.30); }
    .st-unlist::before{ background:rgba(100,116,139,.95); }
    .st-banned{ background:rgba(239,68,68,.10); color:#991b1b; border-color:rgba(239,68,68,.30); }
    .st-banned::before{ background:rgba(239,68,68,.95); }
    .st-map-ok{ background:rgba(34,197,94,.10); color:#166534; border-color:rgba(34,197,94,.30); }
    .st-map-ok::before{ background:rgba(34,197,94,.95); }
    .st-map-no{ background:rgba(239,68,68,.10); color:#991b1b; border-color:rgba(239,68,68,.30); }
    .st-map-no::before{ background:rgba(239,68,68,.95); }
    .st-map-warn{ background:rgba(245,158,11,.10); color:#92400e; border-color:rgba(245,158,11,.30); }
    .st-map-warn::before{ background:rgba(245,158,11,.95); }

    .stock-low{ color:#b91c1c; font-weight:800; }
    .stock-zero{ color:#b91c1c; font-weight:800; background:rgba(239,68,68,.08); border-radius:6px; padding:1px 7px; }

    .model-row td{ background:rgba(148,163,184,.04); font-size:.73rem; padding: .35rem .62rem; border-top: 1px solid transparent; }
    body[data-theme="dark"] .model-row td{ background:rgba(30,41,59,.4); }
    .inp-mini{ width:88px; font-size:.72rem; padding:2px 6px; border:1px solid rgba(148,163,184,.35); border-radius:6px; background:transparent; color:inherit; }
    .btn-mini{ font-size:.65rem; padding:2px 8px; border-radius:6px; }
    .prd-caret{ cursor:pointer; user-select:none; color:#64748b; font-size:.8rem; }
    .empty{ padding:2.2rem 1.25rem; text-align:center; color:#64748b; }
    .empty-state{ max-width:380px; margin:0 auto; padding:1.25rem .5rem; }
    .empty-state-icon{ display:grid; place-items:center; width:42px; height:42px; margin:0 auto .6rem; border-radius:12px; background:rgba(59,130,246,.1); color:#2563eb; font-size:1.1rem; }
    .empty-state-title{ color:inherit; font-weight:750; font-size:.85rem; margin-bottom:.25rem; }
    .empty-state-copy{ font-size:.74rem; line-height:1.5; }
    .prd-loading-row{ height:74px; }
    .prd-skeleton{ display:inline-block; width:100%; height:10px; border-radius:999px; background:linear-gradient(90deg,rgba(148,163,184,.12),rgba(148,163,184,.3),rgba(148,163,184,.12)); background-size:200% 100%; animation:prdshine 1.2s infinite; }
    @keyframes prdshine{ to{ background-position:-200% 0; } }
    .prd-edit-gallery{ display:grid; grid-template-columns:repeat(auto-fill,minmax(130px,1fr)); gap:.65rem; }
    .prd-edit-image{ position:relative; aspect-ratio:1/1; overflow:hidden; border:1px solid var(--prd-border); border-radius:10px; background:#f8fafc; cursor:grab; }
    .prd-edit-image:active{ cursor:grabbing; }
    .prd-edit-image img{ width:100%; height:100%; object-fit:cover; }
    .prd-edit-image .cover{ position:absolute; left:.35rem; top:.35rem; border-radius:999px; padding:.16rem .4rem; background:#334155; color:#fff; font-size:.62rem; font-weight:700; }
    .prd-edit-image .image-order{ position:absolute; right:.35rem; bottom:.35rem; border-radius:999px; padding:.12rem .38rem; background:rgba(15,23,42,.75); color:#fff; font-size:.62rem; }
    .prd-edit-section{ border:1px solid var(--prd-border); border-radius:12px; padding:.8rem; margin-bottom:.75rem; background:rgba(148,163,184,.035); }
    .prd-edit-section-title{ font-size:.78rem; font-weight:800; margin-bottom:.6rem; color:var(--prd-accent); }
    .prd-edit-meta{ font-size:.7rem; color:var(--prd-muted); }
    .prd-promo-context{ display:flex; align-items:center; gap:.75rem; padding:.65rem .75rem; border:1px solid rgba(37,99,235,.2); border-radius:11px; background:rgba(37,99,235,.06); color:#1e3a8a; }
    .prd-promo-modal .modal-content{ border:0; box-shadow:0 18px 60px rgba(15,23,42,.2); overflow:hidden; }
    .prd-promo-modal .modal-header{ padding:1.1rem 1.25rem .85rem; background:var(--card,#fff); }
    .prd-promo-modal .modal-body{ padding:.75rem 1.25rem 1.1rem; background:rgba(248,250,252,.62); }
    .prd-promo-modal .modal-footer{ padding:.8rem 1.25rem; border-top:1px solid var(--prd-border)!important; background:var(--card,#fff); }
    .prd-promo-context{ display:flex; align-items:center; gap:.8rem; padding:.8rem .9rem; border:1px solid rgba(37,99,235,.2); border-radius:13px; background:linear-gradient(135deg,rgba(37,99,235,.09),rgba(59,130,246,.025)); color:#1e3a8a; }
    .prd-promo-context-image{ width:52px; height:52px; flex:none; display:grid; place-items:center; border-radius:11px; overflow:hidden; background:rgba(37,99,235,.1); color:#60a5fa; font-size:1.1rem; }
    .prd-promo-context-image img{ width:100%; height:100%; object-fit:cover; }
    .prd-promo-context .eyebrow,.prd-promo-campaign-box .eyebrow{ font-size:.61rem; font-weight:800; letter-spacing:.08em; text-transform:uppercase; color:#64748b; }
    .prd-promo-context .title{ margin-top:.12rem; font-size:.84rem; font-weight:800; color:#1e3a8a; }
    .prd-promo-context .meta{ font-size:.68rem; color:#64748b; margin-top:.14rem; }
    .prd-promo-context-status{ margin-left:auto; display:inline-flex; align-items:center; gap:.3rem; padding:.27rem .5rem; border-radius:999px; background:rgba(22,163,74,.1); color:#15803d; font-size:.65rem; font-weight:800; white-space:nowrap; }
    .prd-promo-campaign-box{ margin: .8rem 0; padding:.75rem .85rem; border:1px solid var(--prd-border); border-radius:13px; background:var(--card,#fff); }
    .prd-promo-campaign-head{ display:flex; align-items:center; gap:.5rem; margin-bottom:.5rem; }
    .prd-promo-campaign-label{ margin-top:.1rem; font-size:.78rem; font-weight:800; color:inherit; }
    .prd-promo-campaign-count{ margin-left:auto; color:#64748b; font-size:.67rem; font-weight:700; }
    .prd-promo-campaign-select{ min-height:38px; border-radius:9px; font-size:.76rem; font-weight:650; }
    .prd-promo-campaign-help{ display:flex; align-items:center; gap:.35rem; margin-top:.45rem; color:#64748b; font-size:.67rem; }
    .prd-promo-detail-head{ display:flex; align-items:flex-start; justify-content:space-between; gap:.75rem; margin:.95rem 0 .55rem; }
    .prd-promo-detail-title{ font-size:.8rem; font-weight:800; }
    .prd-promo-detail-copy{ margin-top:.15rem; color:#64748b; font-size:.68rem; }
    .prd-promo-detail-meta{ display:flex; align-items:center; gap:.35rem; flex-wrap:wrap; justify-content:flex-end; }
    .prd-promo-status,.prd-promo-variant-count{ display:inline-flex; align-items:center; min-height:25px; padding:.2rem .5rem; border-radius:999px; font-size:.64rem; font-weight:800; }
    .prd-promo-status{ background:rgba(22,163,74,.1); color:#15803d; }
    .prd-promo-variant-count{ background:rgba(100,116,139,.11); color:#64748b; }
    .prd-promo-period{ display:flex; align-items:center; gap:.35rem; margin-bottom:.55rem; color:#64748b; font-size:.67rem; }
    .prd-promo-table-wrap{ border:1px solid var(--prd-border); border-radius:12px; background:var(--card,#fff); overflow:hidden; }
    .prd-promo-table{ width:100%; font-size:.74rem; }
    .prd-promo-table th{ color:#64748b; background:rgba(148,163,184,.07); font-size:.62rem; text-transform:uppercase; letter-spacing:.06em; white-space:nowrap; }
    .prd-promo-table td,.prd-promo-table th{ padding:.65rem .7rem; vertical-align:middle; border-bottom:1px solid var(--prd-border); }
    .prd-promo-table tbody tr:hover{ background:rgba(37,99,235,.025); }
    .prd-promo-table tr:last-child td{ border-bottom:0; }
    .prd-promo-variant-name{ font-size:.75rem; font-weight:800; }
    .prd-promo-variant-meta{ display:flex; gap:.25rem; flex-wrap:wrap; margin-top:.18rem; color:#94a3b8; font-size:.65rem; }
    .prd-promo-normal-price{ color:#475569; font-size:.76rem; font-weight:750; white-space:nowrap; }
    .prd-promo-input-wrap{ display:flex; align-items:center; width:150px; max-width:100%; border:1px solid var(--prd-border); border-radius:8px; background:var(--card,#fff); overflow:hidden; }
    .prd-promo-input-wrap:focus-within{ border-color:#16a34a; box-shadow:0 0 0 3px rgba(22,163,74,.1); }
    .prd-promo-input-prefix{ padding-left:.55rem; color:#94a3b8; font-size:.68rem; font-weight:750; }
    .prd-promo-input{ width:100%; border:0!important; box-shadow:none!important; text-align:right; background:transparent!important; }
    .prd-promo-discount{ display:inline-flex; align-items:center; justify-content:center; min-width:47px; padding:.28rem .45rem; border-radius:7px; background:rgba(22,163,74,.1); color:#15803d; font-size:.7rem; font-weight:800; }
    .prd-promo-discount.empty{ background:rgba(148,163,184,.11); color:#94a3b8; font-weight:650; }
    .prd-promo-empty{ padding:1rem .75rem; border:1px dashed var(--prd-border); border-radius:11px; color:var(--prd-muted); font-size:.75rem; }
    body[data-theme="dark"] .prd-promo-modal .modal-body{ background:rgba(15,23,42,.42); }
    body[data-theme="dark"] .prd-promo-context{ background:rgba(37,99,235,.12); color:#bfdbfe; border-color:rgba(96,165,250,.25); }
    body[data-theme="dark"] .prd-promo-context .title{ color:#bfdbfe; }
    body[data-theme="dark"] .prd-promo-table th{ background:rgba(30,41,59,.65); }
    body[data-theme="dark"] .prd-promo-normal-price{ color:#cbd5e1; }
    body[data-theme="dark"] .prd-promo-input-wrap{ background:#0f172a; }
    @media (max-width:768px){
        .prd-promo-modal .modal-body,.prd-promo-modal .modal-header,.prd-promo-modal .modal-footer{ padding-inline:.75rem; }
        .prd-promo-context-status{ display:none; }
        .prd-promo-detail-head{ display:block; }
        .prd-promo-detail-meta{ justify-content:flex-start; margin-top:.5rem; }
        .prd-promo-table td,.prd-promo-table th{ padding:.55rem .45rem; }
        .prd-promo-input-wrap{ width:126px; }
    }
    body[data-theme="dark"] .prd-promo-context .meta,.prd-promo-table th{ color:#94a3b8; }
    .prd-edit-readonly{ background:var(--card,#fff)!important; }
    .prd-change-log{ display:flex; flex-direction:column; gap:.45rem; }
    .prd-change-log-row{ display:flex; align-items:flex-start; gap:.55rem; padding:.5rem .6rem; border:1px solid var(--prd-border); border-radius:9px; background:var(--card,#fff); }
    .prd-change-log-dot{ width:7px; height:7px; border-radius:999px; margin-top:.35rem; flex:none; background:#94a3b8; }
    .prd-change-log-dot.ok{ background:#22c55e; }
    .prd-change-log-dot.fail{ background:#ef4444; }
    .prd-change-log-action{ font-size:.72rem; font-weight:750; }
    .prd-change-log-meta{ margin-top:.1rem; font-size:.66rem; color:var(--prd-muted); }

    /* Autosuggest item untuk SKU varian di tab Bermasalah */
    .variant-sku-suggest{ position:relative; width:180px; }
    .variant-sku-suggest .input-group{ width:100%; }
    .variant-sku-results{
        display:none; position:absolute; left:0; right:0; top:calc(100% + 3px); z-index:10050;
        max-height:210px; overflow-y:auto; background:var(--card,#fff);
        border:1px solid rgba(148,163,184,.35); border-radius:7px;
        box-shadow:0 8px 18px rgba(15,23,42,.16);
    }
    body[data-theme="dark"] .variant-sku-results{ background:#1e293b; border-color:rgba(51,65,85,.9); }
    .variant-sku-option{
        display:block; width:100%; padding:.42rem .55rem; border:0; border-bottom:1px solid rgba(148,163,184,.14);
        background:transparent; color:inherit; text-align:left; cursor:pointer; font-size:.7rem;
    }
    .variant-sku-option:last-child{ border-bottom:0; }
    .variant-sku-option:hover{ background:rgba(59,130,246,.12); }
    .variant-sku-option-code{ display:block; font-weight:750; }
    .variant-sku-option-name{ display:block; color:#64748b; margin-top:1px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    body[data-theme="dark"] .variant-sku-option-name{ color:#cbd5e1; }

    /* ── Tabs (Produk / Boost) ── */
    .store-tab { 
        background: transparent; border: none; padding: .4rem .8rem; font-size: .8rem; font-weight: 600; 
        color: var(--prd-muted); cursor: pointer; border-bottom: 2px solid transparent; margin-bottom: -1px; white-space: nowrap; transition: color 0.15s;
    }
    .store-tab:hover { color: var(--prd-accent); }
    .store-tab.active { color: var(--prd-accent); border-bottom-color: var(--prd-accent); }
    body[data-theme="dark"] .store-tab:hover, body[data-theme="dark"] .store-tab.active { color: #fff; border-bottom-color: #fff; }

    .pt-tabs{ display:flex; gap:.35rem; margin-bottom:.85rem; }
    .pt-tab{
        font-size:.82rem; font-weight:650; padding:.4rem .9rem; border-radius:8px;
        border:1px solid var(--prd-border); background:transparent; color:var(--prd-muted); cursor:pointer;
    }
    .pt-tab:hover{ background:rgba(148,163,184,.08); }
    .pt-tab.active{ background:var(--prd-accent); border-color:var(--prd-accent); color:#fff; }
    .pt-pane{ display:none; }
    .pt-pane.active{ display:block; animation:ptfade .2s ease; }
    @keyframes ptfade{ from{ opacity:0; transform:translateY(3px);} to{ opacity:1; transform:none;} }

    /* ── Stok tab ── */
    .st-dirty-row td{ background:rgba(37,99,235,.07)!important; }
    body[data-theme="dark"] .st-dirty-row td{ background:rgba(37,99,235,.16)!important; }
    .st-stock-input{ text-align:right; }
    .st-cat-head{ cursor:pointer; }
    .st-cat-head td{ background:rgba(51,65,85,.07); border-top:2px solid rgba(148,163,184,.3); padding:.5rem .62rem; }
    body[data-theme="dark"] .st-cat-head td{ background:rgba(51,65,85,.4); border-top-color:rgba(51,65,85,.9); }
    .st-cat-head:hover td{ background:rgba(51,65,85,.12); }
    .st-cat-inner{ display:flex; align-items:center; gap:.55rem; }
    .st-caret{ color:#64748b; width:.9rem; font-size:.72rem; }
    .st-cat-name{ font-weight:800; font-size:.82rem; }
    .st-cat-meta{ font-size:.67rem; color:#94a3b8; }
    .st-cat-sum{ margin-left:auto; font-size:.72rem; color:#475569; display:inline-flex; align-items:center; gap:.5rem; white-space:nowrap; }
    body[data-theme="dark"] .st-cat-sum{ color:#cbd5e1; }
    .st-badge-empty{ background:rgba(239,68,68,.12); color:#b91c1c; border-radius:6px; padding:1px 7px; font-weight:700; font-size:.65rem; }
    .st-item-head td{ background:rgba(148,163,184,.05); padding:.32rem .62rem .32rem 1rem; }
    body[data-theme="dark"] .st-item-head td{ background:rgba(30,41,59,.55); }
    .st-item-name{ font-weight:700; font-size:.76rem; }
    .st-grp-row td:nth-child(2){ padding-left:1.15rem; }
    .st-stok-cell{ display:flex; align-items:center; gap:.4rem; justify-content:flex-end; }
    .st-stok-cell .st-stock-input{ width:76px; text-align:right; font-weight:700; font-size:.8rem; padding:2px 7px; border-radius:7px; }
    .st-gd-cell{ text-align:right; font-weight:700; font-size:.8rem; white-space:nowrap; }
    .st-inp-zero{ border-color:rgba(239,68,68,.55)!important; color:#b91c1c!important; background:rgba(239,68,68,.06)!important; }
    .st-inp-low{ border-color:rgba(245,158,11,.6)!important; color:#b45309!important; background:rgba(245,158,11,.07)!important; }
    .st-save-btn{ opacity:.4; transition:opacity .15s; }
    tr:hover .st-save-btn, .st-dirty-row .st-save-btn{ opacity:1; }

    /* ── Boost pane (minimalis) ── */
    .bo-bar{ display:flex; align-items:center; gap:.5rem; margin-bottom:1rem; }
    .bo-slotcount{ font-size:.78rem; font-weight:700; color:#ea580c; }
    .bo-sec{ border:1px solid var(--prd-border); border-radius:10px; padding:.85rem 1rem; margin-bottom:.9rem; background:var(--card,#fff); }
    body[data-theme="dark"] .bo-sec{ background:rgba(15,23,42,.5); }
    .bo-sec-head{ font-size:.85rem; font-weight:750; margin-bottom:.6rem; }
    .bo-hint{ font-weight:500; font-size:.7rem; color:#94a3b8; margin-left:.3rem; }
    .bo-product-choice{ flex:1 1 280px; min-width:0; }
    .bo-select-product-btn{ display:flex; align-items:center; gap:.6rem; width:100%; min-height:58px; padding:.45rem .6rem; border:1px solid var(--prd-border); border-radius:11px; background:var(--card,#fff); color:inherit; text-align:left; cursor:pointer; transition:border-color .15s, background .15s, box-shadow .15s; }
    .bo-select-product-btn:hover{ border-color:#ea580c; background:rgba(234,88,12,.04); box-shadow:0 2px 8px rgba(15,23,42,.06); }
    body[data-theme="dark"] .bo-select-product-btn{ background:rgba(30,41,59,.72); }
    .bo-choice-thumb, .bo-product-thumb{ width:42px; height:42px; border-radius:9px; object-fit:cover; flex:none; background:#e2e8f0; display:flex; align-items:center; justify-content:center; color:#94a3b8; font-size:1rem; }
    .bo-choice-copy{ min-width:0; flex:1; }
    .bo-choice-copy strong{ display:block; font-size:.76rem; font-weight:750; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
    .bo-choice-copy small{ display:block; margin-top:.12rem; color:#94a3b8; font-size:.66rem; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
    .bo-choice-chevron{ color:#94a3b8; font-size:.9rem; flex:none; }
    .bo-modal-search-wrap{ position:relative; margin-bottom:.75rem; }
    .bo-modal-search-wrap i{ position:absolute; left:.75rem; top:50%; transform:translateY(-50%); color:#94a3b8; }
    .bo-modal-search{ width:100%; border:1px solid var(--prd-border); border-radius:10px; background:var(--card,#fff); color:inherit; font-size:.8rem; padding:.55rem .7rem .55rem 2.15rem; outline:0; }
    .bo-modal-search:focus{ border-color:#ea580c; box-shadow:0 0 0 3px rgba(234,88,12,.1); }
    body[data-theme="dark"] .bo-modal-search{ background:#0f172a; }
    .bo-modal-count{ color:#94a3b8; font-size:.7rem; margin-bottom:.45rem; }
    .bo-modal-product-grid{ display:grid; grid-template-columns:repeat(auto-fill,minmax(230px,1fr)); gap:.55rem; max-height:48vh; overflow-y:auto; padding:.15rem; }
    .bo-product-card{ display:flex; align-items:center; gap:.55rem; width:100%; min-width:0; padding:.55rem; border:1px solid var(--prd-border); border-radius:11px; background:var(--card,#fff); color:inherit; text-align:left; cursor:pointer; transition:border-color .15s, background .15s, box-shadow .15s; }
    .bo-product-card:hover{ border-color:rgba(234,88,12,.55); background:rgba(234,88,12,.04); }
    .bo-product-card.active{ border-color:#ea580c; background:rgba(234,88,12,.09); box-shadow:0 0 0 2px rgba(234,88,12,.1); }
    body[data-theme="dark"] .bo-product-card{ background:rgba(30,41,59,.72); }
    .bo-product-card img, .bo-product-card .bo-product-thumb{ width:52px; height:52px; }
    .bo-product-card .copy{ min-width:0; flex:1; }
    .bo-product-card .name{ display:block; font-size:.76rem; font-weight:750; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
    .bo-product-card .meta{ display:block; margin-top:.18rem; font-size:.66rem; color:#94a3b8; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
    .bo-product-card .check{ color:#ea580c; font-size:.9rem; opacity:0; flex:none; }
    .bo-product-card.active .check{ opacity:1; }
    .bo-picker-empty{ color:#94a3b8; font-size:.75rem; padding:1rem .25rem; grid-column:1/-1; text-align:center; }
    .bo-slotbar{ display:flex; gap:.4rem; margin-bottom:.6rem; }
    .bo-slot{ width:40px; height:40px; border-radius:10px; border:2px dashed rgba(148,163,184,.4); display:flex; align-items:center; justify-content:center; font-size:1rem; color:#cbd5e1; }
    .bo-slot.filled{ border-style:solid; border-color:#ea580c; background:rgba(234,88,12,.08); }
    .bo-chips{ display:flex; gap:.5rem; flex-wrap:wrap; }
    .bo-chip{ display:flex; align-items:center; gap:.5rem; border:1px solid var(--prd-border); border-radius:10px; padding:.35rem .55rem; background:rgba(248,250,252,.6); max-width:260px; }
    body[data-theme="dark"] .bo-chip{ background:rgba(30,41,59,.6); }
    .bo-chip img{ width:32px; height:32px; border-radius:7px; object-fit:cover; background:#e2e8f0; }
    .bo-chip .nm{ font-size:.74rem; font-weight:650; max-width:150px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
    .bo-chip .rm{ font-size:.66rem; color:#ea580c; font-weight:700; }
    .bo-list{ display:flex; flex-direction:column; }
    .bo-row{ display:flex; align-items:center; gap:.55rem; padding:.45rem 0; border-top:1px solid rgba(148,163,184,.14); font-size:.78rem; }
    .bo-row:first-child{ border-top:0; }
    .bo-row img{ width:32px; height:32px; border-radius:7px; object-fit:cover; background:#e2e8f0; flex:none; }
    .bo-row .grow{ flex:1; min-width:0; }
    .bo-row .nm{ font-weight:650; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
    .bo-row .meta{ font-size:.68rem; color:#94a3b8; }
    .bo-empty{ font-size:.76rem; color:#94a3b8; padding:.5rem 0; }
    .bo-timepill{ display:inline-flex; align-items:center; gap:.3rem; font-size:.7rem; font-weight:700; padding:.14rem .5rem; border-radius:999px; background:rgba(37,99,235,.1); color:#1d4ed8; margin:0 .25rem .25rem 0; }
    .bo-timepill.off{ background:rgba(148,163,184,.18); color:#64748b; }
    .bo-timepill a{ text-decoration:none; }
    .bo-add{ display:flex; gap:.4rem; flex-wrap:wrap; align-items:center; margin-top:.7rem; padding-top:.7rem; border-top:1px dashed rgba(148,163,184,.3); }
    .bo-add input[type="hidden"]{ display:none; }
    .bo-times{ display:flex; gap:.3rem; flex-wrap:wrap; }
    .bo-time-picker{ flex:1 1 100%; min-width:0; padding:.65rem .7rem; border:1px solid var(--prd-border); border-radius:12px; background:rgba(248,250,252,.55); }
    body[data-theme="dark"] .bo-time-picker{ background:rgba(15,23,42,.48); }
    .bo-time-head{ display:flex; align-items:center; gap:.5rem; margin-bottom:.55rem; }
    .bo-time-label{ font-size:.72rem; font-weight:800; color:#475569; text-transform:uppercase; letter-spacing:.04em; }
    body[data-theme="dark"] .bo-time-label{ color:#cbd5e1; }
    .bo-time-count{ margin-left:auto; color:#ea580c; font-size:.68rem; font-weight:700; }
    .bo-time-presets{ display:flex; gap:.35rem; flex-wrap:wrap; margin-bottom:.55rem; }
    .bo-time-preset{ border:1px solid var(--prd-border); border-radius:999px; padding:.25rem .55rem; background:var(--card,#fff); color:inherit; font-size:.69rem; font-weight:700; cursor:pointer; }
    .bo-time-preset:hover, .bo-time-preset.active{ border-color:#ea580c; color:#c2410c; background:rgba(234,88,12,.09); }
    body[data-theme="dark"] .bo-time-preset{ background:#0f172a; }
    .bo-time-fields{ display:flex; gap:.45rem; flex-wrap:wrap; align-items:center; }
    .bo-time-field{ display:flex; align-items:center; gap:.25rem; padding:.2rem .25rem .2rem .5rem; border:1px solid var(--prd-border); border-radius:9px; background:var(--card,#fff); }
    body[data-theme="dark"] .bo-time-field{ background:#0f172a; }
    .bo-t{ min-width:92px; border:0; outline:0; font-size:.76rem; font-weight:700; padding:.18rem .2rem; background:transparent; color:inherit; }
    .bo-time-remove{ width:22px; height:22px; border:0; border-radius:6px; background:transparent; color:#94a3b8; cursor:pointer; line-height:1; }
    .bo-time-remove:hover:not(:disabled){ background:rgba(239,68,68,.1); color:#dc2626; }
    .bo-time-remove:disabled{ opacity:.28; cursor:not-allowed; }
    .bo-time-add{ margin-top:.55rem; border:1px dashed rgba(234,88,12,.5); border-radius:8px; padding:.3rem .55rem; background:transparent; color:#c2410c; font-size:.69rem; font-weight:750; cursor:pointer; }
    .bo-time-add:hover{ background:rgba(234,88,12,.07); }
    .bo-tag{ font-size:.62rem; font-weight:800; padding:.1rem .4rem; border-radius:5px; text-transform:uppercase; }
    .tag-schedule{ background:rgba(37,99,235,.12); color:#1d4ed8; }
    .tag-pool{ background:rgba(22,163,74,.12); color:#15803d; }
    .tag-manual{ background:rgba(100,116,139,.14); color:#475569; }
    .bo-off{ opacity:.5; }

    @media (max-width:768px){
        .page-wrap{ padding:.5rem .5rem 4rem; }
        .prd-topbar{ margin-inline:-.5rem; padding:.6rem .65rem; }
        .sub{ display:none; }
        .controls{ width:100%; }
        .controls .btn-pill{ flex:1 1 30%; padding-inline:.45rem; }
        .filter-select, .filter-search{ max-width:none; flex:1 1 46%; }
        .table-wrap{ overflow-x:auto; }
        .prd-name{ max-width:200px; }
        .workspace-intro{ align-items:flex-start; }
        .filter-panel-heading{ display:none; }
    }

    /* ── Minimal modern layer ── */
    .prd-topbar{
        top:.65rem;
        margin-inline:0;
        padding:1rem 1.1rem;
        border:1px solid var(--prd-border);
        border-radius:18px;
        box-shadow:0 14px 34px rgba(15,23,42,.07);
    }
    .prd-eyebrow{ display:flex; align-items:center; gap:.38rem; margin-bottom:.18rem; color:#94a3b8; font-size:.62rem; font-weight:800; letter-spacing:.11em; text-transform:uppercase; }
    .prd-eyebrow i{ color:#2563eb; font-size:.72rem; }
    .title{ font-size:1.2rem; letter-spacing:-.025em; }
    .sub{ margin-top:.18rem; max-width:680px; line-height:1.45; }
    .controls{ gap:.4rem; }
    .controls .btn-pill{ min-height:34px; border-radius:10px; }
    .kpis{ gap:.4rem; margin-top:.65rem; }
    .kpi{ background:rgba(248,250,252,.8); border-color:rgba(148,163,184,.2); }
    body[data-theme="dark"] .kpi{ background:rgba(15,23,42,.75); }
    .pt-tabs{ gap:.2rem; padding:.25rem; border:1px solid var(--prd-border); border-radius:13px; background:rgba(148,163,184,.05); width:max-content; }
    .pt-tab{ border-color:transparent; border-radius:9px; padding:.48rem .85rem; }
    .pt-tab.active{ box-shadow:0 4px 12px rgba(15,23,42,.12); }
    .store-tabs-shell{ border-radius:15px 15px 0 0; padding:.3rem .55rem 0; }
    .store-tab{ border-radius:8px 8px 0 0; padding:.55rem .8rem; }
    .filter-panel{ border-radius:0 0 15px 15px; padding:.75rem .85rem .65rem; }
    .filter-bar{ gap:.6rem; }
    .filter-search,.filter-select{ border-radius:9px; min-height:34px; }
    .card-main{ border-radius:16px; box-shadow:0 14px 34px rgba(15,23,42,.06); }
    .table-list thead th{ padding:.7rem .65rem; font-size:.65rem; letter-spacing:.02em; text-transform:uppercase; }
    .table-list tbody td{ padding:.72rem .65rem; }
    .table-list tbody tr[data-pid]:hover td{ background:rgba(37,99,235,.055); }
    .prd-img{ width:50px; height:50px; border-radius:11px; }
    .prd-product-cell{ display:flex; align-items:center; gap:.55rem; min-width:240px; }
    .prd-product-copy{ min-width:0; }
    .prd-product-copy .prd-sku{ white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:260px; }
    .prd-name{ font-size:.8rem; }
    .badge-status{ border-radius:999px; padding:.22rem .55rem; }
    .prd-demo-badge{ display:none; align-items:center; gap:.3rem; margin-left:.4rem; padding:.18rem .5rem; border:1px solid rgba(245,158,11,.3); border-radius:999px; background:rgba(245,158,11,.1); color:#a16207; font-size:.65rem; font-weight:750; vertical-align:middle; }
    .prd-demo-badge.show{ display:inline-flex; }
    body[data-theme="dark"] .prd-demo-badge{ color:#fbbf24; background:rgba(245,158,11,.12); }
    .btn-mini{ border-radius:8px; }
    .prd-edit-section{ border-radius:14px; padding:.95rem; background:rgba(148,163,184,.025); }
    .modal-content{ border:1px solid var(--prd-border)!important; box-shadow:0 24px 70px rgba(15,23,42,.18); }
    body[data-theme="dark"] .modal-content{ box-shadow:0 24px 70px rgba(0,0,0,.35); }
    @media (max-width:768px){
        .prd-topbar{ margin-inline:0; top:.35rem; padding:.85rem; }
        .pt-tabs{ width:100%; overflow-x:auto; }
        .pt-tab{ flex:1 0 auto; }
    }
</style>
@endpush

@section('content')
<div class="page-wrap">

    {{-- Topbar --}}
    <div class="prd-topbar">
        <div>
            <div class="prd-eyebrow"><i class="bi bi-shop"></i> Marketplace / Shopee</div>
            <h1 class="title"><i class="bi bi-box-seam me-1 text-primary"></i>Produk Marketplace</h1>
            <div class="sub">Kelola listing, harga normal, stok, mapping SKU, dan gambar dalam satu workspace <span id="rtLabel" class="muted"></span><span id="prdDemoBadge" class="prd-demo-badge"><i class="bi bi-flask"></i> Data demo lokal</span></div>
            <div class="kpis" id="kpiRow"></div>
        </div>
        <div class="controls">
            <button class="btn btn-pill btn-prd-outline" id="btnAutoMap" onclick="autoMap()" title="Cocokkan SKU marketplace dengan item internal"><i class="bi bi-link-45deg me-1"></i>Auto-map</button>
            <button class="btn btn-pill btn-prd-primary" onclick="openProductCreate()" title="Buat listing produk baru"><i class="bi bi-plus-lg me-1"></i>Tambah Produk</button>
            <button class="btn btn-pill btn-prd-outline" id="btnSync" onclick="syncProducts()" title="Ambil data terbaru dari Shopee"><i class="bi bi-arrow-repeat me-1"></i>Sync Shopee</button>
        </div>
    </div>

    {{-- Tabs --}}
    <div class="pt-tabs" role="tablist" aria-label="Area produk marketplace">
        <button class="pt-tab active" data-tab="produk" role="tab" aria-selected="true" onclick="switchTab('produk')"><i class="bi bi-grid me-1"></i>Daftar Produk</button>
        <button class="pt-tab" data-tab="stok" role="tab" aria-selected="false" onclick="switchTab('stok')"><i class="bi bi-boxes me-1"></i>Stok</button>
        <button class="pt-tab" data-tab="boost" role="tab" aria-selected="false" onclick="switchTab('boost')"><i class="bi bi-rocket me-1"></i>Naikkan Produk</button>
    </div>

    {{-- ══ TAB: Daftar Produk ══ --}}
    <div id="tabProduk" class="pt-pane active">
    <div class="workspace-intro"><span class="dot"></span><span>Pilih toko atau gunakan filter untuk menemukan produk yang ingin dikelola. Harga promo diatur dari modul Promosi.</span></div>
    <div id="storeTabs" class="store-tabs-shell" role="tablist" aria-label="Filter toko"></div>
    <div class="filter-panel">
        <div class="filter-panel-heading"><i class="bi bi-funnel"></i><span>Filter & urutan</span><span class="filter-count" id="prdCountLabel"></span></div>
        <div class="filter-bar" style="flex-wrap: nowrap; overflow-x: auto; padding-bottom: 4px; margin-bottom: 0;">
            <div style="position:relative; flex-shrink:0;">
                <input type="text" class="form-control form-control-sm filter-search w-100" style="padding-left:26px;" placeholder="Cari nama / SKU / item id…" id="fSearch">
                <i class="bi bi-search" style="position:absolute;left:8px;top:50%;transform:translateY(-50%);color:#94a3b8;font-size:.75rem"></i>
            </div>
            <input type="hidden" id="fStore" value="">
            <div style="position:relative; flex-shrink:0; min-width:125px;">
                <select class="form-select form-select-sm filter-select w-100" style="padding-left:26px; cursor:pointer;" id="fStatus">
                    <option value="">Semua Status</option>
                    <option value="NORMAL" selected>● Tampil</option>
                    <option value="UNLIST">● Arsip</option>
                    <option value="BANNED">● Banned</option>
                </select>
                <i class="bi bi-circle-half" style="position:absolute;left:8px;top:50%;transform:translateY(-50%);color:#94a3b8;font-size:.75rem"></i>
            </div>
            <div style="position:relative; flex-shrink:0; min-width:145px;">
                <select class="form-select form-select-sm filter-select w-100" style="padding-left:26px; cursor:pointer;" id="fMapping">
                    <option value="">Semua Mapping</option>
                    <option value="unmapped">❌ Belum di-mapping</option>
                    <option value="mapped">✓ Sudah di-mapping</option>
                    <option value="nosku">⚠ SKU kosong</option>
                </select>
                <i class="bi bi-link-45deg" style="position:absolute;left:8px;top:50%;transform:translateY(-50%);color:#94a3b8;font-size:.85rem"></i>
            </div>
            <div style="position:relative; flex-shrink:0; min-width:125px;">
                <select class="form-select form-select-sm filter-select w-100" style="padding-left:26px; cursor:pointer;" id="fSort">
                    <option value="sales" selected>Terlaris (Total)</option>
                    <option value="synced">Terbaru sync</option>
                    <option value="stock_asc">Stok terendah</option>
                    <option value="stock_desc">Stok tertinggi</option>
                    <option value="price_asc">Harga terendah</option>
                    <option value="price_desc">Harga tertinggi</option>
                    <option value="name">Nama A–Z</option>
                </select>
                <i class="bi bi-sort-down" style="position:absolute;left:8px;top:50%;transform:translateY(-50%);color:#94a3b8;font-size:.8rem"></i>
            </div>
            <span class="filter-reset ms-2" id="fReset" onclick="resetFilters()" style="flex-shrink:0;">✕ Reset filter</span>
            <span class="filter-count ms-auto" id="prdCount" style="flex-shrink:0; white-space:nowrap;"></span>
        </div>
    </div>

    {{-- Tabel --}}
    <div class="card-main table-wrap" style="max-height: calc(100vh - 210px); overflow-y: auto;">
        <table class="table-list table-hover">
            <thead><tr>
                <th style="width:38px;text-align:center">#</th><th>Produk</th><th>Status</th><th title="Harga normal produk">Harga</th><th>Stok</th><th>Mapping</th><th style="width:230px">Aksi</th>
            </tr></thead>
            <tbody id="prdBody"><tr><td colspan="7" class="empty"><span class="prd-skeleton" style="max-width:260px"></span></td></tr></tbody>
        </table>
    </div>
    </div>{{-- /tabProduk --}}

    {{-- ══ TAB: Stok ══ --}}
    <div id="tabStok" class="pt-pane">
        <div id="stStoreTabs" class="d-flex gap-2 mb-2" style="overflow-x:auto; border-bottom:1px solid var(--prd-border); padding-bottom:0px; scrollbar-width:none;"></div>
        <div class="kpis" id="stKpiRow" style="margin-bottom:.65rem;"></div>
        <div style="background:rgba(148,163,184,.03); padding:.55rem .75rem; border-radius:8px; border:1px solid var(--prd-border); margin-bottom:.65rem;">
            <div class="filter-bar" style="flex-wrap: nowrap; overflow-x: auto; padding-bottom: 4px; margin-bottom: 0;">
                <div style="position:relative; flex-shrink:0;">
                    <input type="text" class="form-control form-control-sm filter-search w-100" style="padding-left:26px;" placeholder="Cari nama / SKU / varian…" id="stSearch">
                    <i class="bi bi-search" style="position:absolute;left:8px;top:50%;transform:translateY(-50%);color:#94a3b8;font-size:.75rem"></i>
                </div>
                <div style="position:relative; flex-shrink:0; min-width:135px;">
                    <select class="form-select form-select-sm filter-select w-100" style="padding-left:26px; cursor:pointer;" id="stStock">
                        <option value="">Semua Stok</option>
                        <option value="empty">● Habis (0)</option>
                        <option value="low">● Menipis (≤5)</option>
                        <option value="in">● Tersedia</option>
                    </select>
                    <i class="bi bi-box-seam" style="position:absolute;left:8px;top:50%;transform:translateY(-50%);color:#94a3b8;font-size:.75rem"></i>
                </div>
                <div style="position:relative; flex-shrink:0; min-width:125px;">
                    <select class="form-select form-select-sm filter-select w-100" style="padding-left:26px; cursor:pointer;" id="stSort">
                        <option value="stock_asc" selected>Stok terendah</option>
                        <option value="stock_desc">Stok tertinggi</option>
                        <option value="sales">Terlaris</option>
                        <option value="name">Nama A–Z</option>
                    </select>
                    <i class="bi bi-sort-down" style="position:absolute;left:8px;top:50%;transform:translateY(-50%);color:#94a3b8;font-size:.8rem"></i>
                </div>
                <label class="ms-auto" style="display:flex;align-items:center;gap:.35rem;font-size:.72rem;flex-shrink:0;white-space:nowrap;cursor:pointer;color:var(--prd-muted)" title="Kelompokkan per kategori & produk hasil mapping">
                    <input type="checkbox" id="stGroup" checked style="cursor:pointer"> 📂 Grup kategori
                </label>
                <button class="btn btn-sm btn-prd-primary" id="stSaveAll" onclick="stSaveAll()" style="flex-shrink:0; white-space:nowrap;" disabled>💾 Simpan Semua (<span id="stDirtyCount">0</span>)</button>
            </div>
        </div>

        {{-- Tabel stok --}}
        <div class="card-main table-wrap" style="max-height: calc(100vh - 240px); overflow-y: auto;">
            <table class="table-list table-hover">
                <thead><tr>
                    <th style="width:46px"></th>
                    <th>Produk</th>
                    <th style="width:78px; text-align:right;" title="Stok gudang internal (tersedia = fisik − dialokasi)">Gudang</th>
                    <th style="width:150px; text-align:right;">Marketplace</th>
                </tr></thead>
                <tbody id="stBody"><tr><td colspan="4" class="empty">Memuat…</td></tr></tbody>
            </table>
        </div>
    </div>{{-- /tabStok --}}

    {{-- ══ TAB: Naikkan Produk (Boost) ══ --}}
    <div id="tabBoost" class="pt-pane">
        <div class="bo-bar">
            <select class="form-select form-select-sm filter-select" id="boStore"></select>
            <span class="bo-slotcount" id="boSlots">—</span>
            <button class="btn btn-sm btn-prd-outline ms-auto" onclick="boReload()">⟳ Refresh</button>
        </div>

        {{-- Sedang di-boost --}}
        <div class="bo-sec">
            <div class="bo-sec-head">Sedang di-boost <span class="bo-hint">maks 5 · tiap boost 4 jam</span></div>
            <div class="bo-slotbar" id="boSlotBar"></div>
            <div class="bo-chips" id="boBoosted"></div>
        </div>

        {{-- Jadwal jam-tetap --}}
        <div class="bo-sec">
            <div class="bo-sec-head">⏰ Jadwal jam-tetap <span class="bo-hint">naik otomatis tiap hari di jam ini</span></div>
            <div id="boSchedList" class="bo-list"></div>
            <div class="bo-add">
                <div class="bo-product-choice" id="boSchedPicker">
                    <button type="button" class="bo-select-product-btn" onclick="boOpenProductPicker('sched')">
                        <span class="bo-choice-thumb" id="boSchedSelectedThumb">▧</span>
                        <span class="bo-choice-copy"><strong id="boSchedSelectedLabel">Pilih produk</strong><small id="boSchedSelectedMeta">Buka daftar produk aktif</small></span>
                        <span class="bo-choice-chevron">⌄</span>
                    </button>
                </div>
                <input type="hidden" id="boSchedPick">
                <div class="bo-time-picker">
                    <div class="bo-time-head"><span class="bo-time-label">Jam naik otomatis</span><span class="bo-time-count" id="boTimeCount">1 jam dipilih</span></div>
                    <div class="bo-time-presets" id="boTimePresets">
                        <button type="button" class="bo-time-preset" data-time="08:00" onclick="boAddPresetTime('08:00')">08:00</button>
                        <button type="button" class="bo-time-preset" data-time="12:00" onclick="boAddPresetTime('12:00')">12:00</button>
                        <button type="button" class="bo-time-preset" data-time="16:00" onclick="boAddPresetTime('16:00')">16:00</button>
                        <button type="button" class="bo-time-preset" data-time="20:00" onclick="boAddPresetTime('20:00')">20:00</button>
                    </div>
                    <div class="bo-time-fields" id="boSchedTimes"></div>
                    <button type="button" class="bo-time-add" onclick="boAddTime()">＋ Tambah jam lain</button>
                </div>
                <button class="btn btn-sm btn-prd-primary" onclick="boSaveSched()">Simpan</button>
            </div>
        </div>

        {{-- Rotasi otomatis --}}
        <div class="bo-sec">
            <div class="bo-sec-head">🔁 Rotasi otomatis <span class="bo-hint">isi slot kosong bergiliran tiap 4 jam</span></div>
            <div id="boPoolList" class="bo-list"></div>
            <div class="bo-add">
                <div class="bo-product-choice" id="boPoolPicker">
                    <button type="button" class="bo-select-product-btn" onclick="boOpenProductPicker('pool')">
                        <span class="bo-choice-thumb" id="boPoolSelectedThumb">▧</span>
                        <span class="bo-choice-copy"><strong id="boPoolSelectedLabel">Pilih produk</strong><small id="boPoolSelectedMeta">Buka daftar produk aktif</small></span>
                        <span class="bo-choice-chevron">⌄</span>
                    </button>
                </div>
                <input type="hidden" id="boPoolPick">
                <button class="btn btn-sm btn-prd-primary" onclick="boAddPool()">+ Tambah ke antrian</button>
            </div>
        </div>

        {{-- Riwayat --}}
        <div class="bo-sec">
            <div class="bo-sec-head">🧾 Riwayat <span class="bo-hint">50 boost terakhir</span></div>
            <div id="boLogs" class="bo-list bo-logs"></div>
        </div>
    </div>{{-- /tabBoost --}}
</div>

<div class="modal fade" id="modalMapping" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:20px">
            <div class="modal-header border-0">
                <h5 class="modal-title fw-black">Hubungkan SKU ke Item Internal</h5>
                <button class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="mapSelectedInternalId">

                <div class="mb-3">
                    <label class="form-label fw-bold" style="font-size:.75rem;color:#64748b">MARKETPLACE SKU</label>
                    <input type="text" class="form-control" id="mapSkuInput"
                        placeholder="Marketplace SKU" style="border-radius:12px" autocomplete="off" readonly>
                </div>

                <div id="mapRecommendations" style="display:none;margin-bottom:1rem">
                    <div class="fw-bold mb-1"
                        style="font-size:.72rem;color:#64748b;text-transform:uppercase;letter-spacing:.04em">
                        REKOMENDASI ITEM INTERNAL
                    </div>
                    <div id="mapRecoList" class="d-flex flex-wrap gap-2"></div>
                </div>

                <div class="mb-3 position-relative">
                    <label class="form-label fw-bold" style="font-size:.75rem;color:#64748b">CARI ITEM INTERNAL</label>
                    <input type="text" class="form-control mb-1" id="mapItemSearch"
                        placeholder="Ketik kode atau nama item…" style="border-radius:12px" autocomplete="off">
                    <div id="mapItemResults" class="border shadow-sm"
                        style="border-radius:12px;overflow:hidden;display:none;max-height:200px;overflow-y:auto;position:absolute;z-index:99;background:#fff;width:100%"></div>
                    <div id="mapItemSelected" class="mt-2 fw-bold" style="font-size:.85rem;color:#166534"></div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold" style="font-size:.75rem;color:#64748b">CATATAN (Opsional)</label>
                    <input type="text" class="form-control" id="mapNotes" style="border-radius:12px">
                </div>

                <div class="d-flex justify-content-end gap-2 mt-4">
                    <button class="btn btn-light border" style="border-radius:999px" data-bs-dismiss="modal">Batal</button>
                    <button class="btn btn-dark fw-bold" style="border-radius:999px" id="mapSaveBtn"
                        onclick="submitMapping()">Simpan Mapping</button>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalProductCreate" tabindex="-1" aria-labelledby="modalProductCreateLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content" style="border-radius:16px">
            <div class="modal-header border-0 pb-2">
                <div>
                    <h5 class="modal-title fw-bold" id="modalProductCreateLabel">Tambah Produk Baru</h5>
                    <div class="prd-edit-meta">Wizard terpisah · produk dibuat sebagai UNLIST sampai diverifikasi.</div>
                </div>
                <button class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body pt-1" id="productCreateBody">
                <div class="text-center text-muted py-5">Memuat wizard…</div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <span class="prd-edit-meta me-auto" id="productCreateStatus">Langkah 1 dari 4</span>
                <button class="btn btn-light border" style="border-radius:999px" id="productCreatePrev" onclick="previousProductCreateStep()" disabled>Kembali</button>
                <button class="btn btn-primary fw-bold" style="border-radius:999px" id="productCreateNext" onclick="nextProductCreateStep()">Lanjut</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalProductEditor" tabindex="-1" aria-labelledby="modalProductEditorLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content" style="border-radius:16px">
            <div class="modal-header border-0 pb-2">
                <div>
                    <h5 class="modal-title fw-bold" id="modalProductEditorLabel">Detail Produk</h5>
                    <div class="prd-edit-meta" id="productEditorSubtitle">Mengambil data terbaru dari Shopee…</div>
                </div>
                <button class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body pt-1" id="productEditorBody">
                <div class="text-center text-muted py-5">Memuat detail produk…</div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <span class="prd-edit-meta me-auto" id="productEditorSaveStatus">Perubahan belum disimpan.</span>
                <button class="btn btn-primary fw-bold" style="border-radius:999px" id="productEditorSaveBtn" onclick="saveProductEditor()" disabled>Simpan Perubahan</button>
                <button class="btn btn-light border" style="border-radius:999px" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalProductPromotion" tabindex="-1" aria-labelledby="modalProductPromotionLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable prd-promo-modal">
        <div class="modal-content">
            <div class="modal-header border-0 pb-2">
                <div>
                    <div class="prd-edit-meta mb-1"><i class="bi bi-tag me-1"></i>PRICING WORKSPACE</div>
                    <h5 class="modal-title fw-bold" id="modalProductPromotionLabel">Atur Harga Promo</h5>
                    <div class="prd-edit-meta" id="productPromotionSubtitle">Memuat campaign terkait…</div>
                </div>
                <button class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body pt-1" id="productPromotionBody">
                <div class="text-center text-muted py-5">Memuat campaign terkait…</div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <span class="prd-edit-meta me-auto" id="productPromotionStatus">Harga promo tersimpan sebagai bagian dari campaign.</span>
                <button class="btn btn-light border" style="border-radius:999px" data-bs-dismiss="modal">Batal</button>
                <button class="btn btn-success fw-bold" style="border-radius:999px" id="productPromotionSaveBtn" onclick="saveProductPromotion()" disabled>Simpan Harga Promo</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalBoostProductPicker" tabindex="-1" aria-labelledby="modalBoostProductPickerLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content" style="border-radius:16px">
            <div class="modal-header border-0 pb-2">
                <div>
                    <h5 class="modal-title fw-bold" id="modalBoostProductPickerLabel">Pilih Produk</h5>
                    <div class="prd-edit-meta">Pilih produk aktif untuk dijadwalkan atau dimasukkan ke rotasi.</div>
                </div>
                <button class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body pt-1">
                <div class="bo-modal-search-wrap"><i class="bi bi-search"></i><input type="search" class="bo-modal-search" id="boModalProductSearch" placeholder="Cari nama produk, SKU, atau ID…" autocomplete="off"></div>
                <div class="bo-modal-count" id="boModalProductCount"></div>
                <div class="bo-modal-product-grid" id="boModalProductGrid"></div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
(function () {
    const API = '/api/marketplace/products';
    let products = [];   // semua data dari server
    let kpiFilter = '';  // filter cepat via klik KPI
    const $ = id => document.getElementById(id);
    const esc = s => (s ?? '').toString().replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    const rp = n => n == null ? '—' : 'Rp' + Number(n).toLocaleString('id-ID');
    const orderDate = value => {
        if (!value) return '—';
        const date = new Date(String(value).replace(' ', 'T'));
        return Number.isNaN(date.getTime()) ? esc(value) : date.toLocaleString('id-ID', { dateStyle: 'medium', timeStyle: 'short' });
    };

    async function api(url, opts = {}) {
        const headers = { 'Accept': 'application/json', ...(opts.headers || {}) };
        if (!(opts.body instanceof FormData)) headers['Content-Type'] = 'application/json';
        const res = await fetch(url, { ...opts, headers });
        if (!res.ok) throw new Error((await res.json().catch(() => ({})))?.message || ('HTTP ' + res.status));
        return res.json();
    }

    let productEditorPid = null;
    let productEditorData = null;
    let productEditorImages = [];
    let productEditorImagesDirty = false;
    let productCreate = null;
    let productPromotionState = {
        product: null,
        modelId: null,
        campaigns: [],
        detail: null,
        selectedDiscountId: null,
        loading: false,
    };

    function emptyProductCreate() {
        return {
            step: 1,
            stores: [],
            storeId: '',
            categories: [],
            categoryId: '',
            attributes: [],
            attributeList: [],
            logistics: [],
            selectedLogistics: [],
            logisticsLoaded: false,
            files: [],
            images: [],
            itemName: '', itemSku: '', description: '',
            price: '', stock: '', weight: '',
            length: '', width: '', height: '',
            status: 'UNLIST',
            publishing: false,
        };
    }

    function flattenCreateCategories(nodes, level = 0, rows = []) {
        (nodes || []).forEach(node => {
            rows.push({ id: node.category_id ?? node.id, name: `${'— '.repeat(level)}${node.category_name ?? node.name ?? 'Kategori'}` });
            flattenCreateCategories(node.children || node.sub_category_list || node.category_list || [], level + 1, rows);
        });
        return rows;
    }

    function createStepStatus() {
        const labels = ['Toko & kategori', 'Atribut', 'Konten', 'Gambar & logistik'];
        return `${labels[productCreate.step - 1] || 'Preview'} · Langkah ${productCreate.step} dari 4`;
    }

    function createAttributeValue(attr) {
        const values = attr.attribute_value_list || attr.value_list || [];
        if (values.length) {
            return `<select class="form-select form-select-sm" data-create-attr="${esc(attr.attribute_id)}">
                <option value="">Pilih nilai…</option>
                ${values.map(value => `<option value="${esc(value.value_id ?? 0)}" data-original="${esc(value.original_value_name ?? value.value_name ?? '')}">${esc(value.original_value_name ?? value.value_name ?? 'Nilai')}</option>`).join('')}
            </select>`;
        }
        return `<input class="form-control form-control-sm" data-create-attr="${esc(attr.attribute_id)}" placeholder="Isi ${esc(attr.attribute_name || 'nilai atribut')}">`;
    }

    function renderProductCreate() {
        if (!productCreate) return;
        const body = $('productCreateBody');
        let html = '';

        if (productCreate.step === 1) {
            html = `<div class="prd-edit-section">
                <div class="prd-edit-section-title">1. Pilih toko dan kategori</div>
                <label class="form-label small fw-bold">Toko Shopee</label>
                <select class="form-select mb-3" id="createStoreSelect" onchange="onProductCreateStoreChange(this.value)">
                    <option value="">Pilih toko…</option>
                    ${productCreate.stores.map(store => `<option value="${store.id}" ${String(store.id) === String(productCreate.storeId) ? 'selected' : ''}>${esc(store.name)} (${esc(store.code)})</option>`).join('')}
                </select>
                <label class="form-label small fw-bold">Kategori Shopee</label>
                <select class="form-select" id="createCategorySelect" onchange="productCreate.categoryId=this.value" ${productCreate.categories.length ? '' : 'disabled'}>
                    <option value="">${productCreate.storeId ? (productCreate.categories.length ? 'Pilih kategori…' : 'Memuat kategori…') : 'Pilih toko terlebih dahulu'}</option>
                    ${productCreate.categories.map(category => `<option value="${esc(category.id)}" ${String(category.id) === String(productCreate.categoryId) ? 'selected' : ''}>${esc(category.name)}</option>`).join('')}
                </select>
            </div>`;
        } else if (productCreate.step === 2) {
            html = `<div class="prd-edit-section">
                <div class="prd-edit-section-title">2. Atribut wajib kategori</div>
                <div class="prd-edit-meta mb-3">Isi atribut yang ditandai wajib. Atribut lanjutan/varian khusus akan dilanjutkan pada tahap berikutnya.</div>
                ${productCreate.attributes.length ? productCreate.attributes.map(attr => `<div class="mb-3">
                    <label class="form-label small fw-bold">${esc(attr.attribute_name || 'Atribut')} ${attr.is_mandatory ? '<span class="text-danger">*</span>' : ''}</label>
                    ${createAttributeValue(attr)}
                </div>`).join('') : '<div class="text-muted small">Tidak ada atribut wajib yang dikembalikan untuk kategori ini.</div>'}
            </div>`;
        } else if (productCreate.step === 3) {
            if (!productCreate.logisticsLoaded && !productCreate.loadingLogistics && productCreate.storeId) {
                productCreate.loadingLogistics = true;
                api(`${API}/create/logistics`, { method: 'POST', body: JSON.stringify({ store_id: productCreate.storeId }) })
                    .then(result => {
                        productCreate.logistics = result.response?.logistics_channel_list || result.logistics_channel_list || [];
                        const enabled = productCreate.logistics.filter(item => item.enabled).map(item => String(item.logistics_channel_id ?? item.logistic_id));
                        productCreate.selectedLogistics = enabled.length ? enabled : (productCreate.logistics[0] ? [String(productCreate.logistics[0].logistics_channel_id ?? productCreate.logistics[0].logistic_id)] : []);
                        productCreate.logisticsLoaded = true;
                        renderProductCreate();
                    })
                    .catch(error => { productCreate.loadingLogistics = false; alert(error.message); });
            }
            html = `<div class="prd-edit-section">
                <div class="prd-edit-section-title">3. Konten, harga, stok, dan paket</div>
                <div class="row g-2">
                    <div class="col-md-8"><label class="form-label small fw-bold">Nama produk</label><input class="form-control form-control-sm" id="createItemName" value="${esc(productCreate.itemName)}" maxlength="255"></div>
                    <div class="col-md-4"><label class="form-label small fw-bold">SKU</label><input class="form-control form-control-sm" id="createItemSku" value="${esc(productCreate.itemSku)}" maxlength="100"></div>
                    <div class="col-12"><label class="form-label small fw-bold">Deskripsi</label><textarea class="form-control form-control-sm" id="createDescription" rows="4" maxlength="3000">${esc(productCreate.description)}</textarea></div>
                    <div class="col-md-3"><label class="form-label small fw-bold">Harga</label><input type="number" min="100" class="form-control form-control-sm" id="createPrice" value="${esc(productCreate.price)}"></div>
                    <div class="col-md-3"><label class="form-label small fw-bold">Stok</label><input type="number" min="0" class="form-control form-control-sm" id="createStock" value="${esc(productCreate.stock)}"></div>
                    <div class="col-md-3"><label class="form-label small fw-bold">Berat (kg)</label><input type="number" step="0.001" min="0.001" class="form-control form-control-sm" id="createWeight" value="${esc(productCreate.weight)}"></div>
                    <div class="col-md-3"><label class="form-label small fw-bold">Status awal</label><select class="form-select form-select-sm" id="createStatus"><option value="UNLIST" ${productCreate.status === 'UNLIST' ? 'selected' : ''}>UNLIST (aman)</option><option value="NORMAL" ${productCreate.status === 'NORMAL' ? 'selected' : ''}>NORMAL</option></select></div>
                    <div class="col-md-4"><label class="form-label small fw-bold">Panjang (cm)</label><input type="number" min="1" class="form-control form-control-sm" id="createLength" value="${esc(productCreate.length)}"></div>
                    <div class="col-md-4"><label class="form-label small fw-bold">Lebar (cm)</label><input type="number" min="1" class="form-control form-control-sm" id="createWidth" value="${esc(productCreate.width)}"></div>
                    <div class="col-md-4"><label class="form-label small fw-bold">Tinggi (cm)</label><input type="number" min="1" class="form-control form-control-sm" id="createHeight" value="${esc(productCreate.height)}"></div>
                </div>
            </div>
            <div class="prd-edit-section">
                <div class="prd-edit-section-title">4. Gambar dan logistik</div>
                <input type="file" class="form-control form-control-sm mb-2" id="createImages" accept="image/jpeg,image/png" multiple onchange="productCreate.files=Array.from(this.files || [])">
                <div class="prd-edit-meta mb-2">Maksimal 9 gambar, JPG/PNG, 5 MB per file.</div>
                <div class="row g-2">${productCreate.logistics.length ? productCreate.logistics.map(item => {
                    const id = String(item.logistics_channel_id ?? item.logistic_id ?? '');
                    const name = item.logistics_channel_name ?? item.logistic_name ?? `Logistik ${id}`;
                    return `<div class="col-md-6"><label class="border rounded p-2 d-flex gap-2 align-items-center small"><input type="checkbox" data-create-logistic="${esc(id)}" ${productCreate.selectedLogistics.includes(id) ? 'checked' : ''}> <span>${esc(name)} <span class="text-muted">(${esc(id)})</span></span></label></div>`;
                }).join('') : `<div class="col-12 text-muted small">${productCreate.loadingLogistics ? 'Memuat channel logistik…' : 'Channel logistik belum tersedia.'}</div>`}</div>
            </div>`;
        } else {
            const selectedLogistics = productCreate.selectedLogistics.length ? productCreate.selectedLogistics.join(', ') : 'Belum dipilih';
            html = `<div class="prd-edit-section">
                <div class="prd-edit-section-title">Preview sebelum publish</div>
                <dl class="row small mb-0"><dt class="col-sm-3">Toko</dt><dd class="col-sm-9">${esc(productCreate.stores.find(s => String(s.id) === String(productCreate.storeId))?.name || '—')}</dd><dt class="col-sm-3">Kategori</dt><dd class="col-sm-9">${esc(productCreate.categories.find(c => String(c.id) === String(productCreate.categoryId))?.name || productCreate.categoryId)}</dd><dt class="col-sm-3">Nama / SKU</dt><dd class="col-sm-9">${esc(productCreate.itemName)} / ${esc(productCreate.itemSku || '—')}</dd><dt class="col-sm-3">Harga / stok</dt><dd class="col-sm-9">${rp(productCreate.price)} / ${esc(productCreate.stock)}</dd><dt class="col-sm-3">Gambar</dt><dd class="col-sm-9">${productCreate.images.length} gambar</dd><dt class="col-sm-3">Logistik</dt><dd class="col-sm-9">${esc(selectedLogistics)}</dd></dl>
                <div class="alert alert-warning small mt-3 mb-0">Produk akan dibuat sebagai <strong>${esc(productCreate.status)}</strong>. Publish tidak mengubah campaign atau eksperimen iklan.</div>
            </div>`;
        }

        body.innerHTML = html;
        $('productCreateStatus').textContent = createStepStatus();
        $('productCreatePrev').disabled = productCreate.step <= 1 || productCreate.publishing;
        $('productCreateNext').disabled = productCreate.publishing;
        $('productCreateNext').textContent = productCreate.step === 4 ? 'Publish ke Shopee' : 'Lanjut';
    }

    window.openProductCreate = async function () {
        productCreate = emptyProductCreate();
        $('productCreateBody').innerHTML = '<div class="text-center text-muted py-5">Memuat toko Shopee…</div>';
        bootstrap.Modal.getOrCreateInstance($('modalProductCreate')).show();
        try {
            const result = await api(`${API}/create/options`);
            productCreate.stores = result.stores || [];
            renderProductCreate();
        } catch (error) {
            $('productCreateBody').innerHTML = `<div class="alert alert-danger">${esc(error.message)}</div>`;
        }
    };

    window.onProductCreateStoreChange = async function (storeId) {
        productCreate.storeId = storeId;
        productCreate.categories = [];
        productCreate.categoryId = '';
        productCreate.logistics = [];
        productCreate.logisticsLoaded = false;
        renderProductCreate();
        if (!storeId) return;
        try {
            const result = await api(`${API}/create/categories`, { method: 'POST', body: JSON.stringify({ store_id: storeId }) });
            productCreate.categories = flattenCreateCategories(result.response?.category_list || result.category_list || []);
            renderProductCreate();
        } catch (error) { alert(error.message); }
    };

    function collectProductCreateAttributes() {
        const list = [];
        document.querySelectorAll('[data-create-attr]').forEach(input => {
            const attr = productCreate.attributes.find(item => String(item.attribute_id) === String(input.dataset.createAttr));
            if (!attr || !input.value) return;
            const option = input.selectedOptions?.[0];
            list.push({
                attribute_id: Number(attr.attribute_id),
                attribute_value_list: [{
                    value_id: Number(input.value) || 0,
                    original_value_name: option?.dataset.original || input.value,
                }],
            });
        });
        return list;
    }

    function collectProductCreateContent() {
        productCreate.itemName = $('createItemName')?.value.trim() || productCreate.itemName;
        productCreate.itemSku = $('createItemSku')?.value.trim() || '';
        productCreate.description = $('createDescription')?.value || productCreate.description;
        productCreate.price = $('createPrice')?.value || productCreate.price;
        productCreate.stock = $('createStock')?.value || productCreate.stock;
        productCreate.weight = $('createWeight')?.value || productCreate.weight;
        productCreate.length = $('createLength')?.value || productCreate.length;
        productCreate.width = $('createWidth')?.value || productCreate.width;
        productCreate.height = $('createHeight')?.value || productCreate.height;
        productCreate.status = $('createStatus')?.value || productCreate.status;
        productCreate.selectedLogistics = Array.from(document.querySelectorAll('[data-create-logistic]:checked')).map(input => input.dataset.createLogistic);
    }

    async function loadProductCreateAttributes() {
        const result = await api(`${API}/create/attributes`, { method: 'POST', body: JSON.stringify({ store_id: productCreate.storeId, category_id: productCreate.categoryId }) });
        productCreate.attributes = result.response?.attribute_list || result.attribute_list || [];
    }

    async function uploadProductCreateImages() {
        if (productCreate.images.length) return;
        if (!productCreate.files.length) throw new Error('Pilih minimal satu gambar produk.');
        const form = new FormData();
        form.append('store_id', productCreate.storeId);
        productCreate.files.forEach(file => form.append('images[]', file, file.name));
        const result = await api(`${API}/create/images/upload`, { method: 'POST', body: form });
        productCreate.images = result.images || [];
        productCreate.files = [];
    }

    window.nextProductCreateStep = async function () {
        if (!productCreate || productCreate.publishing) return;
        try {
            if (productCreate.step === 1) {
                if (!productCreate.storeId || !productCreate.categoryId) throw new Error('Pilih toko dan kategori terlebih dahulu.');
                await loadProductCreateAttributes();
                productCreate.step = 2;
            } else if (productCreate.step === 2) {
                productCreate.attributeList = collectProductCreateAttributes();
                productCreate.step = 3;
            } else if (productCreate.step === 3) {
                collectProductCreateContent();
                if (!productCreate.itemName || !productCreate.description || Number(productCreate.price) < 100 || Number(productCreate.weight) <= 0) throw new Error('Lengkapi nama, deskripsi, harga, dan berat.');
                if (!productCreate.length || !productCreate.width || !productCreate.height) throw new Error('Lengkapi dimensi paket.');
                if (!productCreate.selectedLogistics.length) throw new Error('Pilih minimal satu channel logistik.');
                await uploadProductCreateImages();
                productCreate.step = 4;
            } else {
                productCreate.publishing = true;
                renderProductCreate();
                const result = await api(`${API}/create/publish`, {
                    method: 'POST',
                    body: JSON.stringify({
                        store_id: productCreate.storeId,
                        item_name: productCreate.itemName,
                        item_sku: productCreate.itemSku || null,
                        description: productCreate.description,
                        category_id: productCreate.categoryId,
                        image_ids: productCreate.images.map(image => image.image_id),
                        original_price: Number(productCreate.price),
                        stock: Number(productCreate.stock),
                        weight: Number(productCreate.weight),
                        dimension: { package_length: Number(productCreate.length), package_width: Number(productCreate.width), package_height: Number(productCreate.height) },
                        logistic_info: productCreate.selectedLogistics.map(id => ({ logistic_id: Number(id), enabled: true, is_free: false })),
                        attribute_list: productCreate.attributeList,
                        item_status: productCreate.status,
                    }),
                });
                productCreate.publishing = false;
                toast(result.warning || `Produk berhasil dibuat. Item ID ${result.item_id || '—'}`, result.warning ? 'warning' : 'success');
                bootstrap.Modal.getOrCreateInstance($('modalProductCreate')).hide();
                if (typeof loadProducts === 'function') loadProducts();
                return;
            }
            renderProductCreate();
        } catch (error) {
            productCreate.publishing = false;
            renderProductCreate();
            alert(error.message || 'Wizard produk gagal dilanjutkan.');
        }
    };

    window.previousProductCreateStep = function () {
        if (!productCreate || productCreate.step <= 1 || productCreate.publishing) return;
        if (productCreate.step === 3) collectProductCreateContent();
        productCreate.step -= 1;
        renderProductCreate();
    };

    function productEditorImageHtml(image, index) {
        const url = image?.file ? URL.createObjectURL(image.file) : (image?.image_url || '');
        const label = image?.file ? image.file.name : (image?.image_id || 'Shopee');
        return `<div class="prd-edit-image" draggable="true" ondragstart="dragProductEditorImage(event, ${index})" ondragover="event.preventDefault()" ondrop="dropProductEditorImage(event, ${index})">
            ${url ? `<img src="${esc(url)}" loading="lazy" alt="Gambar ${index + 1}">` : '<div class="d-flex h-100 align-items-center justify-content-center text-muted" style="font-size:.7rem">Preview tidak tersedia</div>'}
            ${index === 0 ? '<span class="cover">★ Cover</span>' : ''}
            <span class="image-order">#${index + 1}</span>
            <div class="position-absolute bottom-0 start-0 end-0 d-flex gap-1 p-1" style="background:linear-gradient(transparent,rgba(15,23,42,.86))">
                <button type="button" class="btn btn-light btn-sm py-0 px-1" title="Naik" onclick="moveProductEditorImage(${index}, -1)" ${index === 0 ? 'disabled' : ''}>↑</button>
                <button type="button" class="btn btn-light btn-sm py-0 px-1" title="Turun" onclick="moveProductEditorImage(${index}, 1)" ${index === productEditorImages.length - 1 ? 'disabled' : ''}>↓</button>
                ${index !== 0 ? `<button type="button" class="btn btn-warning btn-sm py-0 px-1" title="Jadikan gambar utama" onclick="makeProductEditorCover(${index})">★</button>` : ''}
                <button type="button" class="btn btn-danger btn-sm py-0 px-1 ms-auto" title="Hapus" onclick="removeProductEditorImage(${index})">×</button>
            </div>
            <div class="position-absolute top-0 start-0 end-0 p-1 text-truncate" style="font-size:.58rem;color:#fff;background:rgba(15,23,42,.65)" title="${esc(label)}">${esc(label)}</div>
        </div>`;
    }

    function renderProductEditorGallery() {
        const gallery = $('productEditorGallery');
        if (!gallery) return;
        gallery.innerHTML = productEditorImages.length
            ? productEditorImages.map(productEditorImageHtml).join('')
            : '<div class="text-muted" style="font-size:.75rem">Belum ada gambar. Pilih minimal satu gambar sebelum menyimpan.</div>';
        const count = $('productEditorImageCount');
        if (count) count.textContent = `(${productEditorImages.length}/9 gambar)`;
    }

    function changeLogActionLabel(action) {
        return {
            base_info: 'Info produk diperbarui',
            sku: 'SKU induk diperbarui',
            model_sku: 'SKU varian diperbarui',
            price: 'Harga diperbarui',
            stock: 'Stok diperbarui',
            images: 'Urutan gambar diperbarui',
            image_upload: 'Gambar baru di-upload',
            unlist: 'Status listing diperbarui',
            create: 'Produk dibuat',
        }[action] || action || 'Perubahan produk';
    }

    function renderProductEditorChangeLogs(logs) {
        const target = $('productEditorChangeLogs');
        if (!target) return;
        if (!logs.length) {
            target.innerHTML = '<div class="prd-edit-meta">Belum ada riwayat perubahan untuk produk ini.</div>';
            return;
        }
        target.innerHTML = `<div class="prd-change-log">${logs.slice(0, 8).map(log => {
            const failed = ['failed', 'error'].includes(String(log.status || '').toLowerCase());
            const status = failed ? 'Gagal' : (log.status || 'Selesai');
            return `<div class="prd-change-log-row">
                <span class="prd-change-log-dot ${failed ? 'fail' : 'ok'}"></span>
                <div class="flex-grow-1 min-w-0"><div class="prd-change-log-action">${esc(changeLogActionLabel(log.action))}</div>
                    <div class="prd-change-log-meta">${esc(status)} · ${esc(log.created_by || 'Sistem')} · ${orderDate(log.created_at)}</div>
                    ${failed && log.error_message ? `<div class="text-danger mt-1" style="font-size:.66rem">${esc(log.error_message)}</div>` : ''}
                </div>
            </div>`;
        }).join('')}</div>`;
    }

    window.loadProductEditorLogs = async function () {
        const target = $('productEditorChangeLogs');
        if (!target || !productEditorPid) return;
        target.innerHTML = '<div class="prd-edit-meta">Memuat riwayat…</div>';
        try {
            const result = await api(`${API}/${productEditorPid}/change-logs`);
            renderProductEditorChangeLogs(result.logs || []);
        } catch (error) {
            target.innerHTML = `<div class="prd-edit-meta text-danger">Riwayat belum dapat dimuat: ${esc(error.message || 'Error')}</div>`;
        }
    }

    function renderProductEditor(data) {
        const product = data.product || {};
        const models = data.models || [];
        productEditorData = data;
        productEditorPid = product.local_id;
        productEditorImagesDirty = false;
        productEditorImages = (data.images || []).map(image => ({ ...image, file: null }));
        $('modalProductEditorLabel').textContent = product.item_name || 'Edit Produk';
        $('productEditorSubtitle').textContent = `${product.store_name || 'Toko'} · Item ID ${product.item_id || '—'} · Fresh ${orderDate(data.source?.fetched_at)}`;

        const modelRows = models.length
            ? models.map(model => {
                const price = model.original_price ?? model.current_price ?? '';
                return `<tr data-model-id="${esc(model.model_id || '0')}" data-original-price="${esc(price)}" data-original-stock="${esc(model.stock ?? 0)}" data-original-sku="${esc(model.model_sku || '')}">
                    <td>${esc(model.model_name || 'Produk')}</td>
                    <td><input class="form-control form-control-sm editor-model-sku" value="${esc(model.model_sku || '')}" maxlength="100" placeholder="SKU"></td>
                    <td class="text-muted">${esc(model.model_id || '0')}</td>
                    <td><input type="number" min="100" step="1" class="form-control form-control-sm text-end editor-model-price" value="${esc(price)}"></td>
                    <td><input type="number" min="0" step="1" class="form-control form-control-sm text-end editor-model-stock" value="${esc(model.stock ?? 0)}"></td>
                </tr>`;
            }).join('')
            : '<tr><td colspan="5" class="text-center text-muted">Data varian tidak tersedia</td></tr>';

        $('productEditorBody').innerHTML = `
            <div class="prd-edit-section">
                <div class="prd-edit-section-title">Informasi Produk</div>
                <div class="row">
                    <div class="col-md-8 mb-2"><label class="form-label mb-1" style="font-size:.7rem;font-weight:700;color:#64748b">NAMA PRODUK</label><input class="form-control form-control-sm" id="editorItemName" maxlength="255" value="${esc(product.item_name || '')}"></div>
                    <div class="col-md-4 mb-2"><label class="form-label mb-1" style="font-size:.7rem;font-weight:700;color:#64748b">SKU INDUK</label><input class="form-control form-control-sm" id="editorItemSku" maxlength="100" value="${esc(product.item_sku || '')}"></div>
                    <div class="col-md-6 mb-2"><label class="form-label mb-1" style="font-size:.7rem;font-weight:700;color:#64748b">STATUS</label><input class="form-control form-control-sm prd-edit-readonly" value="${esc(product.item_status || '—')}" readonly></div>
                    <div class="col-md-6 mb-2"><label class="form-label mb-1" style="font-size:.7rem;font-weight:700;color:#64748b">KATEGORI ID</label><input class="form-control form-control-sm prd-edit-readonly" value="${esc(product.category_id || '—')}" readonly></div>
                </div>
                <label class="form-label mb-1" style="font-size:.7rem;font-weight:700;color:#64748b">DESKRIPSI</label>
                <textarea class="form-control form-control-sm" id="editorDescription" rows="5" maxlength="3000">${esc(product.description || '')}</textarea>
            </div>

            <div class="prd-edit-section">
                <div class="d-flex align-items-center justify-content-between gap-2 mb-2">
                    <div class="prd-edit-section-title mb-0">Gallery Produk <span class="prd-edit-meta" id="productEditorImageCount">(0/9 gambar)</span></div>
                    <label class="btn btn-sm btn-outline-primary mb-0" style="border-radius:999px;cursor:pointer">+ Tambah gambar<input type="file" id="productEditorImageInput" accept="image/jpeg,image/png" multiple hidden onchange="addProductEditorImages(event)"></label>
                </div>
                <div class="prd-edit-meta mb-2">Drag-and-drop atau gunakan ↑/↓ untuk mengatur urutan. Gambar pertama menjadi cover; tombol × menghapus dari listing saat disimpan.</div>
                <div class="prd-edit-gallery" id="productEditorGallery"></div>
            </div>

            <div class="prd-edit-section">
                <div class="d-flex align-items-center justify-content-between gap-2 mb-2">
                    <div class="prd-edit-section-title mb-0">Varian, Harga, Stok, dan SKU</div>
                    <span class="prd-edit-meta">Harga minimum Rp100</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0" style="font-size:.72rem">
                        <thead><tr><th>Varian</th><th>SKU</th><th>Model ID</th><th>Harga</th><th>Stok</th></tr></thead>
                        <tbody id="productEditorModels">${modelRows}</tbody>
                    </table>
                </div>
            </div>

            <div class="prd-edit-section mb-0">
                <div class="prd-edit-section-title">Catatan</div>
                <div class="prd-edit-meta">Simpan akan mengirim hanya perubahan yang terdeteksi. Perubahan gallery meng-upload file baru ke Shopee lalu menyimpan daftar image ID final.</div>
            </div>

            <div class="prd-edit-section mb-0">
                <div class="d-flex align-items-center justify-content-between gap-2 mb-2">
                    <div class="prd-edit-section-title mb-0">Riwayat perubahan</div>
                    <button type="button" class="btn btn-sm btn-outline-secondary" style="border-radius:999px" onclick="loadProductEditorLogs()">↻ Muat ulang</button>
                </div>
                <div id="productEditorChangeLogs"></div>
            </div>`;

        renderProductEditorGallery();
        loadProductEditorLogs();
        $('productEditorSaveBtn').disabled = false;
        $('productEditorSaveStatus').textContent = 'Perubahan belum disimpan.';
    }

    window.addProductEditorImages = function (event) {
        const selected = Array.from(event.target.files || []);
        if (productEditorImages.length + selected.length > 9) {
            alert('Maksimal 9 gambar produk.');
            event.target.value = '';
            return;
        }
        productEditorImages.push(...selected.map(file => ({ image_id: null, image_url: null, file })));
        productEditorImagesDirty = true;
        event.target.value = '';
        renderProductEditorGallery();
    };

    window.removeProductEditorImage = function (index) {
        productEditorImages.splice(index, 1);
        productEditorImagesDirty = true;
        renderProductEditorGallery();
    };

    window.moveProductEditorImage = function (index, direction) {
        const target = index + direction;
        if (target < 0 || target >= productEditorImages.length) return;
        [productEditorImages[index], productEditorImages[target]] = [productEditorImages[target], productEditorImages[index]];
        productEditorImagesDirty = true;
        renderProductEditorGallery();
    };

    let productEditorDragIndex = null;
    window.dragProductEditorImage = function (event, index) {
        productEditorDragIndex = index;
        event.dataTransfer.effectAllowed = 'move';
        event.dataTransfer.setData('text/plain', String(index));
    };

    window.dropProductEditorImage = function (event, targetIndex) {
        event.preventDefault();
        const sourceIndex = productEditorDragIndex ?? Number(event.dataTransfer.getData('text/plain'));
        productEditorDragIndex = null;
        if (!Number.isInteger(sourceIndex) || sourceIndex === targetIndex || sourceIndex < 0 || targetIndex < 0) return;
        const [moved] = productEditorImages.splice(sourceIndex, 1);
        productEditorImages.splice(targetIndex, 0, moved);
        productEditorImagesDirty = true;
        renderProductEditorGallery();
    };

    window.makeProductEditorCover = function (index) {
        if (index <= 0 || index >= productEditorImages.length) return;
        const [cover] = productEditorImages.splice(index, 1);
        productEditorImages.unshift(cover);
        productEditorImagesDirty = true;
        renderProductEditorGallery();
    };

    async function saveProductEditorImages() {
        if (!productEditorImagesDirty) return null;
        if (!productEditorImages.length) throw new Error('Gallery harus memiliki minimal satu gambar.');

        const pending = productEditorImages.filter(image => image.file);
        if (pending.length) {
            const uploadBody = new FormData();
            pending.forEach(image => uploadBody.append('images[]', image.file, image.file.name));
            const uploadResult = await api(`${API}/${productEditorPid}/images/upload`, { method: 'POST', body: uploadBody });
            const uploaded = uploadResult.images || [];
            if (uploaded.length !== pending.length) throw new Error('Sebagian file tidak mendapatkan image ID dari Shopee.');

            let uploadedIndex = 0;
            productEditorImages = productEditorImages.map(image => {
                if (!image.file) return image;
                const remote = uploaded[uploadedIndex++];
                return { ...image, ...remote, file: null };
            });
            renderProductEditorGallery();
        }

        const imageOrder = productEditorImages.map(image => image.image_id ? `existing:${image.image_id}` : null).filter(Boolean);
        if (imageOrder.length !== productEditorImages.length) throw new Error('Ada gambar tanpa image ID yang belum selesai di-upload.');
        const result = await api(`${API}/${productEditorPid}/images`, {
            method: 'POST',
            body: JSON.stringify({ image_order: imageOrder }),
        });
        productEditorImagesDirty = false;
        return result;
    }

    window.saveProductEditor = async function () {
        if (!productEditorPid || !productEditorData) return;
        const button = $('productEditorSaveBtn');
        const status = $('productEditorSaveStatus');
        button.disabled = true;
        status.textContent = 'Menyimpan perubahan ke Shopee…';

        try {
            const original = productEditorData.product || {};
            const itemName = $('editorItemName')?.value.trim() || '';
            const description = $('editorDescription')?.value || '';
            const itemSku = $('editorItemSku')?.value.trim() || '';
            const results = [];

            if (itemName !== (original.item_name || '') || description !== (original.description || '')) {
                results.push(await api(`${API}/${productEditorPid}/base-info`, {
                    method: 'POST', body: JSON.stringify({ item_name: itemName, description })
                }));
            }
            if (itemSku !== (original.item_sku || '')) {
                results.push(await api(`${API}/${productEditorPid}/sku`, {
                    method: 'POST', body: JSON.stringify({ new_sku: itemSku })
                }));
            }

            const rows = Array.from(document.querySelectorAll('#productEditorModels tr[data-model-id]'));
            const priceList = [];
            const stockList = [];
            const modelSkuUpdates = [];
            rows.forEach(row => {
                const modelId = row.dataset.modelId || '0';
                const price = Number(row.querySelector('.editor-model-price')?.value);
                const stock = Number(row.querySelector('.editor-model-stock')?.value);
                const sku = row.querySelector('.editor-model-sku')?.value.trim() || '';
                if (Number.isFinite(price) && price !== Number(row.dataset.originalPrice)) priceList.push({ model_id: modelId, original_price: price });
                if (Number.isInteger(stock) && stock >= 0 && stock !== Number(row.dataset.originalStock)) stockList.push({ model_id: modelId, stock });
                if (modelId !== '0' && sku !== (row.dataset.originalSku || '')) modelSkuUpdates.push({ model_id: modelId, new_sku: sku });
            });

            if (priceList.length) results.push(await api(`${API}/${productEditorPid}/price`, { method: 'POST', body: JSON.stringify({ price_list: priceList }) }));
            if (stockList.length) results.push(await api(`${API}/${productEditorPid}/stock`, { method: 'POST', body: JSON.stringify({ stock_list: stockList }) }));
            for (const update of modelSkuUpdates) {
                results.push(await api(`${API}/${productEditorPid}/model-sku`, { method: 'POST', body: JSON.stringify(update) }));
            }
            if (productEditorImagesDirty) results.push(await saveProductEditorImages());

            status.textContent = results.length ? 'Perubahan berhasil disimpan. Memuat ulang data terbaru…' : 'Tidak ada perubahan yang terdeteksi.';
            if (results.length) {
                const fresh = await api(`${API}/${productEditorPid}/edit`);
                renderProductEditor(fresh);
                if (typeof loadProducts === 'function') loadProducts();
                const warning = results.find(result => result && result.warning)?.warning;
                toast(warning || 'Perubahan produk berhasil disimpan.', warning ? 'warning' : 'success');
            }
        } catch (error) {
            status.textContent = error.message || 'Gagal menyimpan perubahan.';
            alert('Gagal menyimpan produk: ' + (error.message || 'Unknown error'));
        } finally {
            button.disabled = false;
        }
    };

    window.openProductEditor = async function (pid) {
        const modal = bootstrap.Modal.getOrCreateInstance($('modalProductEditor'));
        $('modalProductEditorLabel').textContent = 'Detail Produk';
        $('productEditorSubtitle').textContent = 'Mengambil data terbaru dari Shopee…';
        $('productEditorBody').innerHTML = '<div class="text-center text-muted py-5">⏳ Memuat detail produk…</div>';
        $('productEditorSaveBtn').disabled = true;
        $('productEditorSaveStatus').textContent = 'Memuat data terbaru…';
        modal.show();

        try {
            const data = await api(`${API}/${pid}/edit`);
            renderProductEditor(data);
        } catch (e) {
            $('productEditorBody').innerHTML = `<div class="alert alert-danger mb-0" style="font-size:.8rem">${esc(e.message || 'Gagal mengambil detail produk.')}</div>`;
            $('productEditorSubtitle').textContent = 'Gagal mengambil data terbaru';
        }
    };

    // ── Data ────────────────────────────────────────────────────────────────
    window.loadProducts = async function () {
        $('prdBody').innerHTML = `<tr><td colspan="7" class="empty"><div class="empty-state"><div class="empty-state-icon"><i class="bi bi-arrow-repeat"></i></div><div class="empty-state-title">Mengambil produk terbaru…</div><div class="empty-state-copy">Data listing sedang disiapkan dari penyimpanan lokal.</div></div></td></tr>`;
        try {
            products = await api(API);
            const demoBadge = $('prdDemoBadge');
            if (demoBadge) demoBadge.classList.toggle('show', products.some(product => product.raw_json?.dummy_source === 'marketplace_products_ui_v1'));
            buildStoreOptions();
            render();
            if ($('tabStok') && $('tabStok').classList.contains('active')) stRender();
        } catch (e) {
            $('prdBody').innerHTML = `<tr><td colspan="7" class="empty text-danger">${esc(e.message)}</td></tr>`;
        }
    };

    window.syncProducts = async function () {
        $('btnSync').disabled = true; $('btnSync').textContent = '⏳ Memulai…';
        try {
            const res = await api(`${API}/sync`, { method: 'POST', body: '{}' });
            toast(res.message, 'success');
            // Do not call loadProducts immediately since it's backgrounded. 
            // Just let user know it's running.
        } catch (e) { alert('Sync gagal: ' + e.message); }
        finally { $('btnSync').disabled = false; $('btnSync').textContent = '⟳ Sync Shopee'; }
    };

    function buildStoreOptions() {
        const sel = $('fStore'), cur = sel.value;
        const stores = [...new Map(products.filter(p => p.store).map(p => [p.store.id, p.store.name])).entries()];
        
        let html = `<button type="button" class="store-tab ${cur === '' ? 'active' : ''}" role="tab" aria-selected="${cur === ''}" onclick="setStoreTab('')">Semua Produk</button>`;
        html += `<button type="button" class="store-tab ${cur === 'bermasalah' ? 'active' : ''}" role="tab" aria-selected="${cur === 'bermasalah'}" style="color:var(--bs-danger);" onclick="setStoreTab('bermasalah')">⚠ Bermasalah</button>`;
        stores.forEach(([id, name]) => {
            html += `<button type="button" class="store-tab ${cur === String(id) ? 'active' : ''}" role="tab" aria-selected="${cur === String(id)}" onclick="setStoreTab('${id}')">${esc(name)}</button>`;
        });
        if ($('storeTabs')) $('storeTabs').innerHTML = html;
    }
    
    window.setStoreTab = function(id) {
        $('fStore').value = id;
        buildStoreOptions();
        render();
    };

    // ── Filter fungsional (instan, client-side) ─────────────────────────────
    function modelMapState(m) {
        if (!m.model_sku) return 'nosku';
        return m.mapping ? 'mapped' : 'unmapped';
    }
    function productMapState(p) {
        const models = p.models || [];
        if (!models.length) {
            return p.item_sku ? (p.mapping ? 'mapped' : 'unmapped') : 'nosku';
        }
        const states = models.map(modelMapState);
        if (states.every(s => s === 'nosku')) return 'nosku';
        return states.some(s => s === 'unmapped') ? 'unmapped' : 'mapped';
    }

    function filtered() {
        const q = $('fSearch').value.trim().toLowerCase();
        const store = $('fStore').value;
        const status = $('fStatus').value;
        const mapping = $('fMapping')?.value || (kpiFilter === 'unmapped' ? 'unmapped' : '');
        const stock = kpiFilter === 'zero' ? 'zero' : '';
        const statusKpi = ['NORMAL','UNLIST'].includes(kpiFilter) ? kpiFilter : '';

        let rows = products.filter(p => {
            if (store === 'bermasalah') {
                const isBermasalah = p.item_status === 'BANNED' || (p.stock_total ?? 0) === 0 || productMapState(p) === 'unmapped' || productMapState(p) === 'nosku';
                if (!isBermasalah) return false;
            } else if (store && String(p.store?.id) !== store) {
                return false;
            }
            if ((status || statusKpi) && p.item_status !== (status || statusKpi)) return false;
            if (mapping && productMapState(p) !== mapping) return false;
            if (stock === 'zero' && (p.stock_total ?? 0) !== 0) return false;
            if (q) {
                const hay = [p.item_name, p.item_sku, p.item_id, ...(p.models || []).map(m => m.model_sku), ...(p.models || []).map(m => m.model_name)]
                    .filter(Boolean).join(' ').toLowerCase();
                if (!hay.includes(q)) return false;
            }
            return true;
        });

        const sort = $('fSort').value;
        const num = v => v == null ? -1 : Number(v);
        rows.sort((a, b) => {
            switch (sort) {
                case 'sales':      return num(b.sales) - num(a.sales);
                case 'stock_asc':  return num(a.stock_total) - num(b.stock_total);
                case 'stock_desc': return num(b.stock_total) - num(a.stock_total);
                case 'price_asc':  return num(a.price_min) - num(b.price_min);
                case 'price_desc': return num(b.price_min) - num(a.price_min);
                case 'name':       return (a.item_name || '').localeCompare(b.item_name || '');
                default:           return new Date(b.synced_at || 0) - new Date(a.synced_at || 0);
            }
        });
        return rows;
    }

    window.resetFilters = function () {
        ['fSearch','fStore','fMapping'].forEach(id => {
            if ($(id)) $(id).value = '';
        });
        if ($('fStatus')) $('fStatus').value = 'NORMAL';
        if ($('fSort')) $('fSort').value = 'sales';
        kpiFilter = '';
        buildStoreOptions();
        render();
    };

    function anyFilterActive() {
        return kpiFilter || $('fSearch').value || $('fStore').value || ($('fStatus') && $('fStatus').value !== 'NORMAL') || $('fMapping').value;
    }

    // ── KPI (klik untuk quick-filter) ───────────────────────────────────────
    function renderKpis() {
        const total    = products.length;
        const normal   = products.filter(p => p.item_status === 'NORMAL').length;
        const unlist   = products.filter(p => p.item_status === 'UNLIST').length;
        const habis    = products.filter(p => (p.stock_total ?? 0) === 0 && p.item_status === 'NORMAL').length;
        const unmapped = products.filter(p => productMapState(p) === 'unmapped').length;
        const totalPromotion = products.reduce((sum, p) => sum + Number(p.total_promotion || 0), 0);
        const buyerPayment = products.reduce((sum, p) => sum + Number(p.buyer_payment || 0), 0);

        const kpi = (key, lbl, val) =>
            `<span class="kpi ${kpiFilter === key ? 'active' : ''}" onclick="kpiClick('${key}')"><span class="lbl">${lbl}</span><span class="val">${val}</span></span>`;
        const metricKpi = (lbl, val, cls) =>
            `<span class="kpi ${cls || ''}" title="Akumulasi dari transaksi marketplace"><span class="lbl">${lbl}</span><span class="val">${rp(val)}</span></span>`;

        $('kpiRow').innerHTML =
            kpi('', 'Total', total) +
            kpi('NORMAL', 'Tampil', normal) +
            kpi('UNLIST', 'Sembunyi', unlist) +
            kpi('zero', 'Stok Habis', habis) +
            kpi('unmapped', 'Belum Map', unmapped) +
            metricKpi('Total Promosi', totalPromotion, 'kpi-finance-promo') +
            metricKpi('Bayar Pembeli', buyerPayment, 'kpi-finance-buyer');
    }

    window.kpiClick = function (key) {
        kpiFilter = (kpiFilter === key) ? '' : key;
        // KPI mengambil alih dropdown terkait supaya tidak dobel
        if (['NORMAL','UNLIST'].includes(key) && $('fStatus')) $('fStatus').value = '';
        if (key === 'unmapped' && $('fMapping')) $('fMapping').value = '';
        render();
    };

    // ── Render tabel ────────────────────────────────────────────────────────
    function statusBadge(st) {
        const cls = { NORMAL: 'st-normal', UNLIST: 'st-unlist', BANNED: 'st-banned' }[st] || 'st-unlist';
        const lbl = { NORMAL: 'Tampil', UNLIST: 'Sembunyi', BANNED: 'Banned' }[st] || (st || '—');
        return `<span class="badge-status ${cls}">${lbl}</span>`;
    }

    function stockCell(n) {
        if ((n ?? 0) === 0) return '<span class="stock-zero">0</span>';
        if (n <= 5) return `<span class="stock-low">${n}</span>`;
        return `<b>${n}</b>`;
    }

    function mappingBadge(m, skuOverride = null, mappingOverride = undefined) {
        const sku = skuOverride ?? m.model_sku ?? '';
        const mapping = mappingOverride === undefined ? m.mapping : mappingOverride;
        if (!sku) return '<span class="badge-status st-map-warn">SKU kosong</span>';
        if (mapping) {
            return `<span class="badge-status st-map-ok" title="${esc(mapping.item_name || '')}">${esc(mapping.item_code || mapping.item_id)}</span>
                <button class="btn btn-prd-outline btn-mini" onclick="openMapModal('${esc(sku)}')" title="Ganti mapping">✎</button>`;
        }
        return `<button class="btn btn-outline-danger btn-mini" onclick="openMapModal('${esc(sku)}')">❌ Map</button>`;
    }

    function mappingSummary(models) {
        const withSku = models.filter(m => m.model_sku);
        if (!withSku.length) return '<span class="badge-status st-map-warn">SKU kosong</span>';
        const mapped = withSku.filter(m => m.mapping).length;
        const cls = mapped === withSku.length ? 'st-map-ok' : 'st-map-no';
        return `<span class="badge-status ${cls}">${mapped}/${withSku.length} map</span>`;
    }

    function inlineEditors(pid, m) {
        let origPrc = m.price ?? '';
        
        try {
            const raw = typeof m.raw_json === 'string' ? JSON.parse(m.raw_json) : (m.raw_json || {});
            const pInfo = raw.price_info?.[0];
            if (pInfo) {
                origPrc = pInfo.original_price ?? origPrc;
            }
        } catch(e) {}

        return `<div class="d-flex gap-1 flex-nowrap align-items-center">
            <div class="input-group input-group-sm" style="width:132px;" title="Harga normal produk">
                <span class="input-group-text" style="padding:0 6px; font-size:.7rem">Rp</span>
                <input class="form-control px-2" style="font-size:.75rem; padding: 2px 6px;" type="number" min="100" value="${origPrc}" id="base-${pid}-${m.model_id}" aria-label="Harga normal">
            </div>
            <button class="btn btn-outline-primary btn-mini" onclick="savePrice(${pid}, '${m.model_id}')" style="padding:1px 6px;" title="Simpan harga normal">💾</button>
            <button class="btn btn-outline-success btn-mini" onclick="openPromotionsForProduct(${pid}, '${m.model_id}')" style="padding:1px 6px;" title="Atur harga promo">🏷</button>
        </div>`;
    }

    function render() {
        renderKpis();
        const rows = filtered();
        $('prdCount').textContent = `${rows.length} dari ${products.length} produk`;
        if ($('prdCountLabel')) $('prdCountLabel').textContent = `${rows.length} produk terlihat`;
        $('fReset').style.display = anyFilterActive() ? '' : 'none';

        if (!rows.length) {
            const hasProducts = products.length > 0;
            $('prdBody').innerHTML = `<tr><td colspan="7" class="empty"><div class="empty-state">
                <div class="empty-state-icon"><i class="bi bi-${hasProducts ? 'search' : 'box-seam'}"></i></div>
                <div class="empty-state-title">${hasProducts ? 'Produk tidak ditemukan' : 'Belum ada produk'}</div>
                <div class="empty-state-copy">${hasProducts ? 'Coba ubah kata kunci atau filter yang dipilih.' : 'Klik Sync Shopee untuk mengambil listing, atau tambah produk baru untuk mulai menjual.'}</div>
                ${hasProducts ? '<button type="button" class="btn btn-sm btn-prd-outline mt-2" onclick="resetFilters()">Reset filter</button>' : '<button type="button" class="btn btn-sm btn-prd-primary mt-2" onclick="syncProducts()">⟳ Sync Shopee</button>'}
            </div></td></tr>`;
            return;
        }

        $('prdBody').innerHTML = rows.map((p, rowIndex) => {
            const st = p.item_status || '—';
            const models = p.models || [];
            const multiModel = p.has_model && models.length > 0;
            const price = p.price_min != null
                ? (p.price_min === p.price_max ? rp(p.price_min) : `${rp(p.price_min)} – ${rp(p.price_max)}`)
                : '—';
            const isBermasalahTab = $('fStore').value === 'bermasalah';

            let aksiContent = '';
            if (isBermasalahTab) {
                let issues = [];
                issues.push(`<button class="btn btn-sm btn-outline-primary w-100 mb-1 py-1" onclick="openProductEditor(${p.id})" style="font-size:.7rem">✎ Detail Produk</button>`);
                issues.push(`<button class="btn btn-sm btn-outline-success w-100 mb-1 py-1" onclick="openPromotionsForProduct(${p.id})" style="font-size:.7rem">🏷 Atur Harga Promo</button>`);
                if (p.item_status === 'BANNED') {
                    issues.push(`<a class="btn btn-sm btn-outline-danger w-100 mb-1 py-1" href="https://seller.shopee.co.id/portal/product/${p.item_id}" target="_blank" style="font-size:.7rem">Cek Pelanggaran di Shopee ↗</a>`);
                }
                if ((p.stock_total ?? 0) === 0) {
                    if (multiModel) {
                        issues.push(`<button class="btn btn-sm btn-outline-warning w-100 mb-1 py-1" onclick="toggleModels(${p.id}, document.querySelector('tr[data-pid=\\'${p.id}\\'] .prd-caret'))" style="font-size:.7rem">Buka Varian & Isi Stok</button>`);
                    } else {
                        issues.push(`<div class="input-group input-group-sm mb-1"><input type="number" class="form-control px-2" placeholder="Stok Baru" id="stk-${p.id}-0" style="font-size:.75rem"><button class="btn btn-warning" onclick="saveStock(${p.id}, '0')" style="font-size:.7rem; padding: 2px 6px;">Simpan</button></div>`);
                    }
                }
                const mapState = productMapState(p);
                if (mapState === 'nosku') {
                    if (multiModel) {
                        issues.push(`<button class="btn btn-sm btn-outline-danger w-100 py-1 mb-1" onclick="toggleModels(${p.id}, document.querySelector('tr[data-pid=\\'${p.id}\\'] .prd-caret'))" style="font-size:.7rem">⚠️ SKU Varian Kosong (Buka)</button>`);
                    } else {
                        issues.push(`<div class="mb-1 text-danger fw-bold" style="font-size:.7rem">⚠️ SKU Kosong (Isi di sebelah kiri)</div>`);
                    }
                } else if (mapState === 'unmapped') {
                    if (multiModel) {
                        issues.push(`<button class="btn btn-sm btn-outline-primary w-100 py-1" onclick="toggleModels(${p.id}, document.querySelector('tr[data-pid=\\'${p.id}\\'] .prd-caret'))" style="font-size:.7rem">Perbaiki Mapping Varian</button>`);
                    } else {
                        const sku = models.length ? models[0].model_sku : p.item_sku;
                        issues.push(`<button class="btn btn-sm btn-outline-primary w-100 py-1" onclick="openMapModal('${esc(sku || '')}')" style="font-size:.7rem">Mapping ke GFID</button>`);
                    }
                }
                
                aksiContent = issues.join('');
                if (!aksiContent) aksiContent = '<span class="text-muted" style="font-size:.75rem">Tidak ada masalah khusus</span>';
            } else {
                aksiContent = `
                    <div class="dropdown mb-1">
                        <button class="btn btn-prd-outline btn-mini dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" style="padding: 2px 8px; font-weight: 700; border-radius: 6px;">
                            ⋮ Aksi
                        </button>
                        <ul class="dropdown-menu shadow-sm" style="font-size: .78rem; min-width: 140px; padding: .3rem 0; border-radius: 8px;">
                            <li><a class="dropdown-item py-2" href="javascript:void(0)" onclick="openProductEditor(${p.id})">✎ Detail Produk</a></li>
                            <li><a class="dropdown-item py-2 text-success" href="javascript:void(0)" onclick="openPromotionsForProduct(${p.id})">🏷 Atur Harga Promo</a></li>
                            <li><a class="dropdown-item py-2" href="javascript:void(0)" onclick="showHistory(${p.id})">📈 Riwayat</a></li>
                            ${st === 'NORMAL' ? `<li><a class="dropdown-item py-2" href="javascript:void(0)" onclick="boostNow(${p.id}, this)">🚀 Naikkan (Boost)</a></li>` : ''}
                            ${st === 'NORMAL' ? `<li><hr class="dropdown-divider" style="margin: .2rem 0"></li><li><a class="dropdown-item py-2 text-danger" href="javascript:void(0)" onclick="setUnlist(${p.id}, true)">🙈 Arsipkan Produk</a></li>` : (st === 'UNLIST' ? `<li><hr class="dropdown-divider" style="margin: .2rem 0"></li><li><a class="dropdown-item py-2 text-success" href="javascript:void(0)" onclick="setUnlist(${p.id}, false)">👁 Aktifkan Produk</a></li>` : '')}
                        </ul>
                    </div>
                    ${!multiModel && models.length ? `<div class="mt-1">${inlineEditors(p.id, models[0])}</div>` : ''}
                `;
            }

            const isModelOpened = openedModels.has(p.id);
            const mainRow = `<tr data-pid="${p.id}">
                <td class="prd-no">${rowIndex + 1}</td>
                <td><div class="prd-product-cell">
                    ${multiModel ? `<span class="prd-caret" onclick="toggleModels(${p.id}, this)" title="Tampilkan varian"><i class="bi bi-chevron-${isModelOpened ? 'down' : 'right'}"></i></span>` : '<span style="width:.8rem"></span>'}
                    ${p.image_url ? `<img class="prd-img" src="${esc(p.image_url)}" loading="lazy" alt="">` : '<div class="prd-img"></div>'}
                    <div class="prd-product-copy"><a class="prd-product-link" href="javascript:void(0)" onclick="showProductOrders(${p.id}); return false;" title="Lihat pesanan produk"><div class="prd-name" style="display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden" title="${esc(p.item_name || '')}">${esc(p.item_name || '—')}</div></a>
                    <div class="prd-sku mt-1" title="${esc(p.item_sku || p.item_id || '')}">
                        <span class="text-muted">SKU:</span>
                        ${esc(p.item_sku || 'SKU kosong')} <span class="text-muted">· ${esc(p.item_id)}${multiModel ? ` · ${models.length} varian` : ''}</span>
                    </div></div>
                </div>
                </td>
                <td>${statusBadge(st)}</td>
                <td>${price}</td>
                <td>${stockCell(p.stock_total)}</td>
                <td data-product-mapping="${p.id}">${multiModel ? mappingSummary(models) : (models.length ? mappingBadge(models[0]) : mappingBadge(p, p.item_sku, p.mapping))}</td>
                <td>
                    ${aksiContent}
                </td>
            </tr>`;

            const modelRows = multiModel ? models.map(m => `
                <tr class="model-row mr-${p.id}" data-model-id="${esc(m.model_id)}" style="${isModelOpened ? '' : 'display:none'}">
                    <td></td>
                    <td style="padding-left:22px">↳ ${esc(m.model_name || 'Varian')} 
                        <div class="variant-sku-suggest mt-1">
                            <div class="input-group input-group-sm">
                                <input type="text" class="form-control px-2 js-variant-sku-input" style="font-size:.72rem" value="${esc(m.model_sku || '')}" id="vsku-${p.id}-${m.model_id}" data-product-id="${p.id}" data-model-id="${esc(m.model_id)}" placeholder="Kode Variasi" autocomplete="off">
                                <button class="btn btn-outline-secondary btn-mini fw-bold" onclick="saveSkuInline(${p.id}, '${m.model_id}')" style="padding:1px 8px;" title="Simpan SKU">💾</button>
                            </div>
                            <div class="variant-sku-results" role="listbox"></div>
                        </div>
                    </td>
                    <td></td>
                    <td>${rp(m.price)}</td>
                    <td>${stockCell(m.stock)}</td>
                    <td class="variant-mapping-cell">${mappingBadge(m)}</td>
                    <td><div class="d-flex align-items-center gap-1">${inlineEditors(p.id, m)}<button class="btn btn-outline-secondary btn-mini" onclick="showHistory(${p.id})" style="padding:1px 5px" title="Riwayat">📈</button></div></td>
                </tr>`).join('') : '';

            return mainRow + modelRows;
        }).join('');
    }

    let openedModels = new Set();
    window.toggleModels = function (pid, caret) {
        const rows = document.querySelectorAll('.mr-' + pid);
        const show = rows.length && rows[0].style.display === 'none';
        rows.forEach(r => r.style.display = show ? '' : 'none');
        if (caret) caret.innerHTML = show ? '<i class="bi bi-chevron-down"></i>' : '<i class="bi bi-chevron-right"></i>';
        if (show) openedModels.add(pid);
        else openedModels.delete(pid);
    };

    // ── Autosuggest item untuk input SKU varian ─────────────────────────────
    // Input dirender ulang setiap kali tabel di-render, jadi gunakan event delegation.
    function closeVariantSuggestions(except = null) {
        document.querySelectorAll('.variant-sku-results').forEach(box => {
            if (!except || box !== except) box.style.display = 'none';
        });
    }

    function renderVariantSuggestions(input, items) {
        const box = input.closest('.variant-sku-suggest')?.querySelector('.variant-sku-results');
        if (!box) return;

        box.innerHTML = '';
        if (!items.length) {
            box.innerHTML = '<div class="p-2 text-muted" style="font-size:.7rem">Item tidak ditemukan</div>';
            box.style.display = 'block';
            return;
        }

        items.slice(0, 8).forEach(item => {
            const option = document.createElement('button');
            option.type = 'button';
            option.className = 'variant-sku-option';
            option.dataset.code = item.code || '';
            option.dataset.itemId = item.id || '';
            option.dataset.itemName = item.name || '';
            option.innerHTML = `<span class="variant-sku-option-code"></span><span class="variant-sku-option-name"></span>`;
            option.querySelector('.variant-sku-option-code').textContent = (item.code || '').toUpperCase();
            option.querySelector('.variant-sku-option-name').textContent = item.name || '';
            box.appendChild(option);
        });

        closeVariantSuggestions(box);
        box.style.display = 'block';
    }

    async function loadVariantSuggestions(input) {
        const box = input.closest('.variant-sku-suggest')?.querySelector('.variant-sku-results');
        if (!box) return;

        const q = input.value.trim();
        if (q.length < 2) {
            box.style.display = 'none';
            return;
        }

        const requestId = String(Number(input.dataset.suggestRequest || 0) + 1);
        input.dataset.suggestRequest = requestId;
        box.innerHTML = '<div class="p-2 text-muted" style="font-size:.7rem">Mencari item…</div>';
        closeVariantSuggestions(box);
        box.style.display = 'block';

        try {
            const result = await api('/api/v1/items/suggest?q=' + encodeURIComponent(q) + '&limit=8');
            if (input.dataset.suggestRequest !== requestId) return;
            renderVariantSuggestions(input, Array.isArray(result?.data) ? result.data : []);
        } catch (e) {
            if (input.dataset.suggestRequest !== requestId) return;
            box.innerHTML = '<div class="p-2 text-danger" style="font-size:.7rem">Autosuggest gagal dimuat</div>';
            box.style.display = 'block';
        }
    }

    let variantSuggestTimer = null;
    document.addEventListener('focusin', e => {
        const input = e.target.closest('.js-variant-sku-input');
        if (!input) return;

        // Memudahkan mengganti SKU varian yang sudah ada.
        input.select();
        clearTimeout(variantSuggestTimer);
        variantSuggestTimer = setTimeout(() => loadVariantSuggestions(input), 80);
    });

    document.addEventListener('input', e => {
        const input = e.target.closest('.js-variant-sku-input');
        if (!input) return;

        clearTimeout(variantSuggestTimer);
        variantSuggestTimer = setTimeout(() => loadVariantSuggestions(input), 220);
    });

    document.addEventListener('mousedown', e => {
        const option = e.target.closest('.variant-sku-option');
        if (option) {
            e.preventDefault();
            const input = option.closest('.variant-sku-suggest')?.querySelector('.js-variant-sku-input');
            if (!input) return;
            clearTimeout(variantSuggestTimer);
            input.value = option.dataset.code || '';
            input.focus();
            input.select();
            clearTimeout(variantSuggestTimer);
            closeVariantSuggestions();
            if (option.dataset.itemId) {
                autoMapVariantSelection(input, {
                    id: Number(option.dataset.itemId),
                    code: option.dataset.code || '',
                    name: option.dataset.itemName || '',
                });
            }
            return;
        }

        if (!e.target.closest('.variant-sku-suggest')) closeVariantSuggestions();
    });

    document.addEventListener('keydown', e => {
        const input = e.target.closest('.js-variant-sku-input');
        if (input && e.key === 'Escape') closeVariantSuggestions();
    });

    // ── Aksi stok / harga / unlist ──────────────────────────────────────────
    window.saveStock = async function (pid, modelId) {
        const val = parseInt($(`stk-${pid}-${modelId}`).value);
        if (isNaN(val) || val < 0) return alert('Stok tidak valid');
        try {
            await api(`${API}/${pid}/stock`, { method: 'POST', body: JSON.stringify({ stock_list: [{ model_id: modelId, stock: val }] }) });
            toast('Stok tersimpan ke Shopee ✔');
            
            // Update local state instead of reloading table
            const p = products.find(x => x.id === pid);
            if (p) {
                const m = p.models.find(x => String(x.model_id) === String(modelId));
                if (m) {
                    const diff = val - (m.stock || 0);
                    m.stock = val;
                    p.stock_total = (p.stock_total || 0) + diff;
                }
            }
        } catch (e) { alert('Gagal: ' + e.message); }
    };

    window.savePrice = async function (pid, modelId) {
        const origPrice = parseFloat($(`base-${pid}-${modelId}`).value);
        
        if (isNaN(origPrice) || origPrice < 100) return alert('Harga asli tidak valid (min 100)');

        try {
            await api(`${API}/${pid}/price`, { 
                method: 'POST', 
                body: JSON.stringify({ 
                    price_list: [{ model_id: modelId, original_price: origPrice }]
                }) 
            });
            toast('Harga normal berhasil diupdate ke Shopee ✔');
            
            // Update local state instead of reloading table
            const p = products.find(x => x.id === pid);
            if (p) {
                const m = p.models.find(x => String(x.model_id) === String(modelId));
                if (m) {
                    m.price = origPrice;
                }
            }
            render();
        } catch (e) { alert('Gagal: ' + e.message); }
    };

    function productPromotionVariants(product, promoItem = null) {
        const models = Array.isArray(product?.models) && product.models.length
            ? product.models
            : [{ model_id: '0', model_name: 'Default', model_sku: product?.item_sku || '', price: product?.price_min ?? 0, stock: product?.stock_total ?? 0 }];
        const promoModels = new Map((promoItem?.model_list || []).map(model => [String(model.model_id ?? '0'), model]));
        const itemOriginalPrice = Number(promoItem?.item_original_price ?? 0);
        return models.map(model => {
            const modelId = String(model.model_id ?? '0');
            const promoModel = promoModels.get(modelId);
            const campaignOriginalPrice = Number(promoModel?.model_original_price ?? 0);
            const localOriginalPrice = Number(model.price ?? model.original_price ?? model.current_price ?? product?.price_min ?? 0);
            const originalPrice = campaignOriginalPrice > 0
                ? campaignOriginalPrice
                : (modelId === '0' && itemOriginalPrice > 0 ? itemOriginalPrice : localOriginalPrice);

            return {
                model_id: modelId,
                model_name: model.model_name || promoModel?.model_name || 'Default',
                model_sku: model.model_sku || '',
                original_price: originalPrice,
                stock: Number(model.stock ?? 0),
            };
        });
    }

    function productPromotionDateLabel(value) {
        if (!value) return '—';
        const date = new Date(Number(value) * 1000);
        return Number.isNaN(date.getTime()) ? '—' : date.toLocaleString('id-ID', { dateStyle: 'medium', timeStyle: 'short' });
    }

    function productPromotionCampaignHasProduct(campaign, product) {
        return (campaign?.items_preview || []).some(item => String(item.item_id) === String(product?.item_id));
    }

    function promotionItemPayload(item) {
        return {
            item_id: Number(item.item_id),
            model_list: (item.model_list || []).map(model => ({
                model_id: Number(model.model_id || 0),
                model_promotion_price: model.model_promotion_price === '' || model.model_promotion_price == null ? null : Number(model.model_promotion_price),
                model_promotion_percentage: model.model_promotion_percentage === '' || model.model_promotion_percentage == null ? null : Number(model.model_promotion_percentage),
            })).filter(model => model.model_promotion_price !== null || model.model_promotion_percentage !== null),
        };
    }

    function renderProductPromotionCampaignPicker() {
        const product = productPromotionState.product;
        const body = $('productPromotionBody');
        if (!product || !body) return;

        const campaigns = productPromotionState.campaigns || [];
        const withPreview = campaigns.filter(campaign => Array.isArray(campaign.items_preview) && campaign.items_preview.length);
        const related = withPreview.filter(campaign => productPromotionCampaignHasProduct(campaign, product));
        const candidates = related.length ? related : campaigns;
        productPromotionState.candidates = candidates;

        $('productPromotionSubtitle').textContent = `${product.item_name || 'Produk'} · Item ID ${product.item_id} · Harga promo diatur per campaign`;

        if (!candidates.length) {
            body.innerHTML = `<div class="prd-promo-context mb-3"><i class="bi bi-tag fs-5"></i><div><div class="title">Belum ada campaign aktif</div><div class="meta">Tidak ada promo ongoing atau upcoming yang bisa diedit untuk toko ini.</div></div></div>
                <div class="prd-promo-empty">Buat campaign baru dari modul Promosi terlebih dahulu, lalu harga promo variant dapat diedit langsung dari halaman produk.</div>`;
            $('productPromotionSaveBtn').disabled = true;
            return;
        }

        body.innerHTML = `
            <div class="prd-promo-context">
                <div class="prd-promo-context-image">${product.image_url ? `<img src="${esc(product.image_url)}" loading="lazy" alt="">` : '<i class="bi bi-image"></i>'}</div>
                <div class="min-w-0"><div class="eyebrow">Produk terkait promo</div><div class="title text-truncate">${esc(product.item_name || 'Produk')}</div><div class="meta">${esc(product.item_sku || 'SKU kosong')} · Item ID ${esc(product.item_id)} · ${productPromotionVariants(product).length} variant</div></div>
                <span class="prd-promo-context-status"><i class="bi bi-check-circle-fill"></i> Siap diedit</span>
            </div>
            <div class="prd-promo-campaign-box">
                <div class="prd-promo-campaign-head"><div><div class="eyebrow">Campaign promo</div><div class="prd-promo-campaign-label">Pilih campaign yang ingin diperbarui</div></div><span class="prd-promo-campaign-count">${candidates.length} campaign</span></div>
                <select class="form-select prd-promo-campaign-select" id="productPromotionCampaign" onchange="selectProductPromotion(this.value)">
                    ${candidates.map(campaign => `<option value="${esc(campaign.discount_id)}">${esc(campaign.discount_name || `Promo #${campaign.discount_id}`)} · ${esc(campaign.status_label || campaign.discount_status || '')}</option>`).join('')}
                </select>
                <div class="prd-promo-campaign-help"><i class="bi bi-info-circle"></i>${related.length ? 'Campaign yang memuat produk ini ditampilkan lebih dulu.' : 'Campaign toko ditemukan. Pilih campaign untuk mengecek variant produk ini.'}</div>
            </div>
            <div id="productPromotionDetail"><div class="text-center text-muted py-4">Memuat variant promo…</div></div>`;

        selectProductPromotion(candidates[0].discount_id);
    }

    function renderProductPromotionDetail() {
        const detail = productPromotionState.detail;
        const product = productPromotionState.product;
        const target = $('productPromotionDetail');
        const saveButton = $('productPromotionSaveBtn');
        if (!target || !product || !detail) return;

        const promoItem = (detail.items || []).find(item => String(item.item_id) === String(product.item_id));
        if (!promoItem) {
            target.innerHTML = `<div class="prd-promo-empty"><i class="bi bi-info-circle me-1"></i> Produk ini belum masuk ke campaign <b>${esc(detail.discount_name || `#${detail.discount_id}`)}</b>.</div>`;
            saveButton.disabled = true;
            return;
        }

        const promoModels = promoItem.model_list || [];
        const variants = productPromotionVariants(product, promoItem);
        const rows = variants.map(model => {
            const current = promoModels.find(item => String(item.model_id ?? 0) === model.model_id);
            const promoPrice = current?.model_promotion_price ?? '';
            const percentage = promoPrice && model.original_price > 0
                ? Math.max(0, Math.round(((model.original_price - Number(promoPrice)) / model.original_price) * 100))
                : '';
            return `<tr>
                <td><div class="prd-promo-variant-name">${esc(model.model_name)}</div><div class="prd-promo-variant-meta"><span>${esc(model.model_sku || 'SKU kosong')}</span><span>·</span><span>Stok ${model.stock.toLocaleString('id-ID')}</span></div></td>
                <td class="text-end"><span class="prd-promo-normal-price">${rp(model.original_price)}</span></td>
                <td><div class="prd-promo-input-wrap"><span class="prd-promo-input-prefix">Rp</span><input type="number" min="100" step="1" inputmode="numeric" class="form-control form-control-sm prd-promo-input" data-promo-model-id="${esc(model.model_id)}" value="${esc(promoPrice)}" placeholder="Nonaktif" aria-label="Harga promo ${esc(model.model_name)}"></div></td>
                <td class="text-end"><span class="prd-promo-discount ${percentage ? '' : 'empty'}" id="prdPromoPct-${esc(model.model_id)}">${percentage ? `${percentage}%` : '—'}</span></td>
            </tr>`;
        }).join('');

        target.innerHTML = `<div class="prd-promo-detail-head"><div><div class="prd-promo-detail-title">Harga promo per variant</div><div class="prd-promo-detail-copy">Kosongkan harga jika variant tidak ingin diikutkan dalam campaign.</div></div><div class="prd-promo-detail-meta"><span class="prd-promo-status"><i class="bi bi-broadcast-pin me-1"></i>${esc(detail.status_label || detail.discount_status || 'Aktif')}</span><span class="prd-promo-variant-count">${variants.length} variant</span></div></div>
            <div class="prd-promo-period"><i class="bi bi-calendar3"></i><span>Periode ${esc(productPromotionDateLabel(detail.start_time))} — ${esc(productPromotionDateLabel(detail.end_time))}</span></div>
            <div class="prd-promo-table-wrap"><div class="table-responsive"><table class="prd-promo-table"><thead><tr><th>Variant</th><th class="text-end">Harga normal</th><th>Harga promo</th><th class="text-end">Diskon</th></tr></thead><tbody>${rows}</tbody></table></div></div>`;
        saveButton.disabled = false;

        target.querySelectorAll('[data-promo-model-id]').forEach(input => {
            input.addEventListener('input', () => {
                const model = variants.find(item => item.model_id === String(input.dataset.promoModelId));
                const percentageTarget = $(`prdPromoPct-${input.dataset.promoModelId}`);
                const value = Number(input.value);
                const percentage = model && value > 0 && model.original_price > 0
                    ? Math.max(0, Math.round(((model.original_price - value) / model.original_price) * 100))
                    : '';
                if (percentageTarget) {
                    percentageTarget.textContent = percentage ? `${percentage}%` : '—';
                    percentageTarget.classList.toggle('empty', !percentage);
                }
            });
        });

        if (productPromotionState.modelId !== null && productPromotionState.modelId !== undefined) {
            const focusInput = target.querySelector(`[data-promo-model-id="${String(productPromotionState.modelId)}"]`);
            if (focusInput) {
                focusInput.focus();
                focusInput.scrollIntoView({ block: 'center' });
            }
        }
    }

    window.selectProductPromotion = async function (discountId) {
        const product = productPromotionState.product;
        const storeId = product?.store?.id;
        if (!product || !storeId || !discountId) return;

        productPromotionState.selectedDiscountId = String(discountId);
        const target = $('productPromotionDetail');
        const saveButton = $('productPromotionSaveBtn');
        if (target) target.innerHTML = '<div class="text-center text-muted py-4">Memuat variant promo…</div>';
        if (saveButton) saveButton.disabled = true;

        try {
            const result = await api(`/api/marketplace/promotions/${storeId}/${discountId}?refresh=1`);
            productPromotionState.detail = result.promotion || null;
            if (!productPromotionState.detail) throw new Error('Detail campaign tidak ditemukan.');
            renderProductPromotionDetail();
        } catch (error) {
            if (target) target.innerHTML = `<div class="prd-promo-empty text-danger">${esc(error.message || 'Detail campaign gagal dimuat.')}</div>`;
        }
    };

    window.saveProductPromotion = async function () {
        const product = productPromotionState.product;
        const detail = productPromotionState.detail;
        const button = $('productPromotionSaveBtn');
        if (!product || !detail || !button) return;

        const targetItemId = String(product.item_id);
        const promoItem = (detail.items || []).find(item => String(item.item_id) === targetItemId);
        const targetModels = productPromotionVariants(product, promoItem).map(model => {
            const input = document.querySelector(`[data-promo-model-id="${model.model_id}"]`);
            const rawPrice = input?.value?.trim() || '';
            if (!rawPrice) return null;
            const promoPrice = Number(rawPrice);
            if (!Number.isFinite(promoPrice) || promoPrice < 100) throw new Error(`${model.model_name}: harga promo minimal Rp100.`);
            if (model.original_price > 0 && promoPrice >= model.original_price) throw new Error(`${model.model_name}: harga promo harus lebih rendah dari harga normal.`);
            return {
                model_id: Number(model.model_id),
                model_promotion_price: promoPrice,
                model_promotion_percentage: model.original_price > 0 ? Math.round(((model.original_price - promoPrice) / model.original_price) * 100) : null,
            };
        }).filter(Boolean);

        if (!targetModels.length) return alert('Isi minimal satu harga promo variant.');

        const itemList = (detail.items || [])
            .filter(item => String(item.item_id) !== targetItemId)
            .map(promotionItemPayload)
            .filter(item => item.model_list.length);
        itemList.push({ item_id: Number(product.item_id), model_list: targetModels });

        button.disabled = true;
        button.textContent = 'Menyimpan…';
        try {
            const result = await api(`/api/marketplace/promotions/${product.store.id}/${detail.discount_id}/update`, {
                method: 'POST',
                body: JSON.stringify({ item_list: itemList }),
            });
            toast(result.message || 'Harga promo berhasil diperbarui.');
            $('productPromotionStatus').textContent = 'Harga promo berhasil disimpan ke campaign.';
            await window.selectProductPromotion(detail.discount_id);
        } catch (error) {
            toast(error.message || 'Harga promo gagal disimpan.', 'error');
        } finally {
            button.disabled = false;
            button.textContent = 'Simpan Harga Promo';
        }
    };

    window.openPromotionsForProduct = async function (pid, modelId = null) {
        const product = products.find(item => String(item.id) === String(pid));
        if (!product?.store?.id || !product.item_id) {
            return alert('Data toko atau item produk belum tersedia. Silakan sync produk terlebih dahulu.');
        }

        productPromotionState = { product, modelId: modelId === null || modelId === undefined ? null : String(modelId), campaigns: [], detail: null, selectedDiscountId: null, loading: true };
        const modal = bootstrap.Modal.getOrCreateInstance($('modalProductPromotion'));
        $('modalProductPromotionLabel').textContent = `Atur Harga Promo · ${product.item_name || 'Produk'}`;
        $('productPromotionSubtitle').textContent = 'Mencari campaign terkait…';
        $('productPromotionBody').innerHTML = '<div class="text-center text-muted py-5">Memuat campaign ongoing dan upcoming…</div>';
        $('productPromotionSaveBtn').disabled = true;
        modal.show();

        try {
            const [ongoing, upcoming] = await Promise.all([
                api(`/api/marketplace/promotions?store_id=${product.store.id}&status=ongoing`),
                api(`/api/marketplace/promotions?store_id=${product.store.id}&status=upcoming`),
            ]);
            const unique = new Map();
            [...(ongoing.promotions || []), ...(upcoming.promotions || [])].forEach(campaign => {
                if (campaign?.discount_id) unique.set(String(campaign.discount_id), campaign);
            });
            productPromotionState.campaigns = Array.from(unique.values());
            productPromotionState.loading = false;
            renderProductPromotionCampaignPicker();
        } catch (error) {
            productPromotionState.loading = false;
            $('productPromotionBody').innerHTML = `<div class="prd-promo-empty text-danger">${esc(error.message || 'Campaign promo gagal dimuat.')}</div>`;
            $('productPromotionSubtitle').textContent = 'Gagal memuat campaign';
        }
    };

    window.setUnlist = async function (pid, unlist) {
        try {
            const res = await api(`${API}/${pid}/unlist`, { method: 'POST', body: JSON.stringify({ unlist }) });
            const p = products.find(x => x.id === pid);
            if (p) p.item_status = res.item_status || (unlist ? 'UNLIST' : 'NORMAL');
            toast(unlist ? 'Produk disembunyikan' : 'Produk ditampilkan');
            render();
        } catch (e) { alert('Gagal: ' + e.message); }
    };

    // Naikkan (boost) satu produk sekarang — maks 5 aktif / 4 jam (dibatasi Shopee)
    window.boostNow = async function (pid, btn) {
        const p = products.find(x => x.id === pid);
        if (!p) return;
        const orig = btn ? btn.innerHTML : '';
        if (btn) { btn.disabled = true; btn.innerHTML = '⏳'; }
        try {
            const res = await api('/api/marketplace/boost/now', {
                method: 'POST',
                body: JSON.stringify({ store_id: p.store_id, product_ids: [pid] }),
            });
            toast(res.message || 'Produk dinaikkan', res.success ? 'success' : 'warning');
            if (boLoaded && $('tabBoost').classList.contains('active')) boReload();
        } catch (e) {
            toast('Gagal boost: ' + e.message, 'error');
        } finally {
            if (btn) { btn.disabled = false; btn.innerHTML = orig; }
        }
    };

    function toast(title, icon = 'success') {
        if (window.Swal) Swal.fire({ toast:true, position:'top-end', icon, title, showConfirmButton:false, timer:2600 });
    }

    window.saveSkuInline = async function (pid, modelId) {
        const inp = document.getElementById(`vsku-${pid}-${modelId}`);
        const newSku = inp.value;
        const prod = products.find(p => p.id === pid);
        if (!prod) return;
        const m = prod.models.find(x => String(x.model_id) === String(modelId));
        if (!m || m.model_sku === newSku) return;

        inp.disabled = true;
        try {
            const res = await fetch(`/api/marketplace/products/${pid}/model-sku`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify({ model_id: modelId, new_sku: newSku })
            });
            const dat = await res.json();
            if (!res.ok) throw new Error(dat.message || 'Gagal update SKU');
            
            m.model_sku = newSku;
            m.mapping = null; // SKU berubah → mapping lama tak berlaku lagi
            toast('SKU varian berhasil disimpan ✔');
            render(); // tampilkan tombol Map langsung tanpa reload/pindah tab
        } catch (e) {
            alert('Gagal simpan SKU: ' + e.message);
            inp.value = m.model_sku || '';
            inp.disabled = false;
        }
    };

    function updateVariantMappingUi(pid, modelId, model) {
        const row = [...document.querySelectorAll(`tr.mr-${pid}`)]
            .find(el => String(el.dataset.modelId) === String(modelId));
        const mappingCell = row?.querySelector('.variant-mapping-cell');
        if (mappingCell) mappingCell.innerHTML = mappingBadge(model);

        const product = products.find(p => String(p.id) === String(pid));
        const productCell = document.querySelector(`tr[data-pid="${pid}"] [data-product-mapping="${pid}"]`);
        if (product && productCell) {
            productCell.innerHTML = product.has_model
                ? mappingSummary(product.models || [])
                : (product.models?.length ? mappingBadge(product.models[0]) : '—');
        }
    }

    async function autoMapVariantSelection(input, item) {
        const pid = input.dataset.productId;
        const modelId = input.dataset.modelId;
        const product = products.find(p => String(p.id) === String(pid));
        const model = product?.models?.find(m => String(m.model_id) === String(modelId));
        const sku = (item.code || '').trim();
        const saveButton = input.closest('.variant-sku-suggest')?.querySelector('button[title="Simpan SKU"]');

        if (!product || !model || !sku || !item.id) return;

        const previousSku = model.model_sku || '';
        const previousMapping = model.mapping || null;
        const originalButtonText = saveButton?.innerHTML || '💾';
        let skuSaved = false;

        input.disabled = true;
        if (saveButton) {
            saveButton.disabled = true;
            saveButton.innerHTML = '⏳';
        }

        try {
            // Pilihan autosuggest juga langsung menyimpan SKU varian ke marketplace.
            if (previousSku !== sku) {
                const skuResponse = await fetch(`/api/marketplace/products/${pid}/model-sku`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify({ model_id: modelId, new_sku: sku })
                });
                const skuData = await skuResponse.json().catch(() => ({}));
                if (!skuResponse.ok) throw new Error(skuData.message || 'Gagal update SKU');

                model.model_sku = sku;
                model.mapping = null;
                skuSaved = true;
            }

            // Buat/update mapping tanpa memuat ulang seluruh tabel.
            const mapping = await api('/api/sku-mappings', {
                method: 'POST',
                body: JSON.stringify({
                    marketplace_sku: sku,
                    channel_code: null,
                    item_id: Number(item.id),
                    notes: 'auto-map dari autosuggest SKU varian'
                })
            });

            model.mapping = {
                id: mapping.id,
                item_id: mapping.item_id ?? mapping.item?.id ?? Number(item.id),
                item_code: mapping.item?.code ?? item.code,
                item_name: mapping.item?.name ?? item.name,
            };
            updateVariantMappingUi(pid, modelId, model);
            input.value = sku;
            toast(`Varian ${sku} tersimpan & auto-map ✔`);
        } catch (e) {
            if (!skuSaved) {
                input.value = previousSku;
                model.mapping = previousMapping;
            }
            updateVariantMappingUi(pid, modelId, model);
            toast(skuSaved ? `SKU tersimpan, mapping gagal: ${e.message}` : `Gagal auto-map: ${e.message}`, 'error');
        } finally {
            input.disabled = false;
            if (saveButton) {
                saveButton.disabled = false;
                saveButton.innerHTML = originalButtonText;
            }
        }
    }

    window.saveIndukSkuInline = async function (pid) {
        const inp = document.getElementById(`isku-${pid}`);
        const newSku = inp.value;
        const prod = products.find(p => p.id === pid);
        if (!prod || prod.item_sku === newSku) return;

        inp.disabled = true;
        try {
            const res = await fetch(`/api/marketplace/products/${pid}/sku`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify({ new_sku: newSku })
            });
            const dat = await res.json();
            if (!res.ok) throw new Error(dat.message || 'Gagal update SKU');
            
            prod.item_sku = newSku;
            prod.mapping = null;
            toast('Induk SKU berhasil disimpan ✔');
            
            if (!prod.has_model) {
                render();
            }
        } catch (e) {
            alert('Gagal simpan SKU: ' + e.message);
            inp.value = prod.item_sku || '';
        } finally {
            inp.disabled = false;
        }
    };

    // ── Mapping SKU → item internal ─────────────────────────────────────────
    window.autoMap = async function () {
        $('btnAutoMap').disabled = true;
        try {
            const res = await api(`${API}/auto-map`, { method: 'POST', body: '{}' });
            toast(res.message, res.created > 0 ? 'success' : 'info');
            loadProducts();
        } catch (e) { alert('Gagal: ' + e.message); }
        finally { $('btnAutoMap').disabled = false; }
    };

    let mapSearchTimer = null;
    let mapSelectedItem = null; // { id, code, name } item internal yang dipilih

    window.selectMapItem = function (id, code, name) {
        mapSelectedItem = { id, code: code || '', name: name || '' };
        $('mapSelectedInternalId').value = id;
        $('mapItemSearch').value = code || name;
        $('mapItemSelected').textContent = '✓ Terpilih: ' + (code ? code + ' — ' : '') + name;
        $('mapItemResults').style.display = 'none';
        
        document.querySelectorAll('#mapRecoList .btn-outline-primary').forEach(btn => {
            btn.classList.remove('active');
            if (btn.dataset.id == id) btn.classList.add('active');
        });
    };

    window.submitMapping = async function () {
        const sku = $('mapSkuInput').value;
        const itemId = $('mapSelectedInternalId').value;
        const notes = $('mapNotes').value;

        if (!itemId) return alert('Silakan cari dan pilih item internal terlebih dahulu.');

        const btn = $('mapSaveBtn');
        btn.disabled = true;
        btn.textContent = 'Menyimpan...';

        try {
            const dat = await api('/api/sku-mappings', {
                method: 'POST',
                body: JSON.stringify({
                    marketplace_sku: sku,
                    channel_code: null,
                    item_id: parseInt(itemId),
                    notes: notes || 'mapping dari tab Produk'
                })
            });

            // Update state lokal → tabel langsung ter-update tanpa reload
            const map = {
                id:        dat.id,
                item_id:   dat.item_id ?? (dat.item && dat.item.id) ?? parseInt(itemId),
                item_code: (dat.item && dat.item.code) ?? mapSelectedItem?.code ?? '',
                item_name: (dat.item && dat.item.name) ?? mapSelectedItem?.name ?? '',
            };
            products.forEach(p => (p.models || []).forEach(m => {
                if (m.model_sku === sku) m.mapping = map;
            }));
            products.forEach(p => {
                if (p.item_sku === sku) p.mapping = map;
            });

            toast(`SKU ${sku} berhasil di-mapping ✔`);
            render();
            bootstrap.Modal.getInstance($('modalMapping')).hide();
        } catch (e) {
            alert('Gagal simpan mapping: ' + e.message); 
        } finally {
            btn.disabled = false;
            btn.textContent = 'Simpan Mapping';
        }
    };

    window.openMapModal = async function (sku) {
        $('mapSelectedInternalId').value = '';
        $('mapSkuInput').value = sku || '';
        $('mapItemSearch').value = '';
        $('mapItemSelected').textContent = '';
        $('mapItemResults').style.display = 'none';
        $('mapRecommendations').style.display = 'none';
        $('mapRecoList').innerHTML = '';
        $('mapNotes').value = '';

        new bootstrap.Modal($('modalMapping')).show();

        if (!$('mapItemSearch').dataset.inited) {
            $('mapItemSearch').dataset.inited = '1';
            $('mapItemSearch').addEventListener('input', function () {
                clearTimeout(mapSearchTimer);
                const q = this.value.trim();
                if (q.length < 2) { $('mapItemResults').style.display = 'none'; return; }
                mapSearchTimer = setTimeout(async () => {
                    const items = await api('/api/sku-mappings/search-items?q=' + encodeURIComponent(q)).catch(() => []);
                    const box = $('mapItemResults');
                    if (!items.length) { box.style.display = 'none'; return; }
                    box.style.display = 'block';
                    box.innerHTML = items.map(i =>
                        `<div class="p-2 border-bottom text-dark" style="cursor:pointer;font-size:.82rem;background:#fff"
                            onmousedown="selectMapItem(${i.id},'${esc(i.code||'')}','${esc(i.name)}')" onmouseover="this.style.background='#f1f5f9'" onmouseout="this.style.background='#fff'">
                            <strong>${esc(i.code||'')}</strong>${i.code ? ' — ' : ''}${esc(i.name)}
                        </div>`
                    ).join('');
                }, 250);
            });
        }

        if (sku && sku.length >= 2) {
            const queries = new Set([sku, sku.split('-')[0], sku.replace(/[-_]\d+$/, '')]);
            let results = [];
            for (const q of queries) {
                if (!q || q.length < 2) continue;
                const items = await api('/api/sku-mappings/search-items?q=' + encodeURIComponent(q)).catch(() => []);
                items.forEach(i => { if (!results.find(r => r.id === i.id)) results.push(i); });
                if (results.length >= 5) break;
            }
            if (results.length) {
                $('mapRecommendations').style.display = 'block';
                $('mapRecoList').innerHTML = results.slice(0, 5).map(i =>
                    `<button type="button" class="btn btn-sm btn-outline-primary text-start" data-id="${i.id}" style="font-size:.7rem;border-radius:10px"
                        onclick="selectMapItem(${i.id},'${esc(i.code||'')}','${esc(i.name)}')">
                        <b>${esc(i.code||i.name)}</b><br><span style="font-size:.65rem">${esc(i.name)}</span>
                    </button>`
                ).join('');
            }
        }
    };

    // ── Mesin waktu: riwayat harian produk ──────────────────────────────────
    window.showHistory = async function (pid) {
        const p = products.find(x => x.id === pid);
        try {
            const d = await api(`${API}/${pid}/history?days=90`);
            const days = (d.days || []).slice().reverse(); // terbaru dulu
            const body = days.length ? `
                <div style="max-height:320px;overflow-y:auto">
                <table style="width:100%;font-size:.75rem;text-align:right;border-collapse:collapse">
                    <thead><tr style="color:#64748b;font-size:.65rem;text-transform:uppercase;position:sticky;top:0;background:#fff">
                        <th style="text-align:left;padding:4px">Tanggal</th><th>Stok</th><th>Harga</th><th>Terjual/hari</th><th>Total Jual</th><th>Status</th>
                    </tr></thead>
                    <tbody>${days.map(r => `<tr style="border-top:1px solid #f1f5f9">
                        <td style="text-align:left;padding:4px">${(r.date || '').substring(0, 10)}</td>
                        <td style="${r.stock_total === 0 ? 'color:#dc2626;font-weight:700' : ''}">${r.stock_total}</td>
                        <td>${r.price_min != null ? 'Rp' + Number(r.price_min).toLocaleString('id-ID') : '—'}</td>
                        <td>${r.sales_delta ?? '—'}</td>
                        <td>${r.sales ?? '—'}</td>
                        <td style="font-size:.65rem">${r.item_status || ''}</td>
                    </tr>`).join('')}</tbody>
                </table></div>`
                : '<div class="text-muted p-3">Belum ada snapshot. Data terkumpul otomatis tiap malam (23:45), atau jalankan <code>php artisan marketplace:snapshot-products</code>.</div>';

            Swal.fire({
                title: `📈 ${p?.item_name || 'Produk'}`,
                html: body,
                width: 620,
                showConfirmButton: false,
                showCloseButton: true,
            });
        } catch (e) { alert('Gagal muat riwayat: ' + e.message); }
    };

    // ── Pesanan berdasarkan produk ─────────────────────────────────────────
    window.showProductOrders = async function (pid) {
        const p = products.find(x => x.id === pid);
        Swal.fire({
            title: `Pesanan · ${p?.item_name || 'Produk'}`,
            html: '<div class="text-muted py-4">Memuat pesanan…</div>',
            width: 1040,
            showConfirmButton: false,
            showCloseButton: true,
            allowOutsideClick: false,
        });

        try {
            const data = await api(`${API}/${pid}/orders?limit=200`);
            const orders = data.orders || [];
            const totalQty = orders.reduce((sum, row) => sum + Number(row.qty || 0), 0);
            const totalBuyerPayment = orders.reduce((sum, row) => sum + Number(row.buyer_payment || 0), 0);
            const statusLabel = value => String(value || '—').replaceAll('_', ' ');
            const statusBadge = value => `<span class="badge-status ${['PAID','COMPLETED','SELESAI','LUNAS','SHIPPED','DELIVERED'].includes(String(value).toUpperCase()) ? 'st-normal' : 'st-unlist'}">${esc(statusLabel(value))}</span>`;
            const body = `
                <div style="display:flex;gap:.5rem;flex-wrap:wrap;margin-bottom:.8rem;text-align:left">
                    <span class="kpi"><span class="lbl">Total Pesanan</span><span class="val">${orders.length.toLocaleString('id-ID')}</span></span>
                    <span class="kpi"><span class="lbl">Total Qty</span><span class="val">${totalQty.toLocaleString('id-ID')}</span></span>
                    <span class="kpi"><span class="lbl">Pembayaran Pembeli</span><span class="val">${rp(totalBuyerPayment)}</span></span>
                </div>
                <div style="max-height:480px;overflow:auto;border:1px solid #e2e8f0;border-radius:8px">
                    <table style="width:100%;min-width:820px;font-size:.75rem;text-align:right;border-collapse:collapse">
                        <thead><tr style="color:#64748b;font-size:.65rem;text-transform:uppercase;position:sticky;top:0;background:#fff;z-index:1">
                            <th style="text-align:center;padding:8px">#</th><th style="text-align:left;padding:8px">Pesanan</th><th style="text-align:left;padding:8px">Tanggal</th><th style="text-align:left;padding:8px">Pembeli</th><th style="padding:8px">Qty</th><th style="padding:8px">Nilai Produk</th><th style="padding:8px">Pembayaran Pembeli</th><th style="padding:8px">Pembayaran</th><th style="padding:8px">Status</th>
                        </tr></thead>
                        <tbody>${orders.length ? orders.map((row, index) => `<tr style="border-top:1px solid #f1f5f9">
                            <td style="padding:8px;text-align:center;color:#64748b;font-weight:700">${index + 1}</td>
                            <td style="padding:8px;text-align:left;font-weight:700">${esc(row.order_number)}</td>
                            <td style="padding:8px;text-align:left;white-space:nowrap">${orderDate(row.ordered_at)}</td>
                            <td style="padding:8px;text-align:left;max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="${esc(row.buyer)}">${esc(row.buyer)}</td>
                            <td style="padding:8px">${Number(row.qty || 0).toLocaleString('id-ID')}</td>
                            <td style="padding:8px">${rp(row.line_total)}</td>
                            <td style="padding:8px;color:#0f766e;font-weight:700">${rp(row.buyer_payment)}</td>
                            <td style="padding:8px">${statusBadge(row.payment_status)}</td>
                            <td style="padding:8px">${statusBadge(row.order_status)}</td>
                        </tr>`).join('') : '<tr><td colspan="9" style="padding:2rem;text-align:center;color:#64748b">Belum ada pesanan untuk produk ini.</td></tr>'}</tbody>
                    </table>
                </div>`;

            Swal.fire({
                title: `Pesanan · ${p?.item_name || 'Produk'}`,
                html: body,
                width: 1040,
                showConfirmButton: false,
                showCloseButton: true,
            });
        } catch (e) {
            Swal.fire({ title: 'Gagal memuat pesanan', text: e.message, icon: 'error', showCloseButton: true });
        }
    };

    // ── Event listeners filter (instan) ─────────────────────────────────────
    $('fSearch').addEventListener('input', render);
    ['fStatus','fMapping','fSort'].forEach(id => {
        if ($(id)) $(id).addEventListener('change', render);
    });

    // ══════════════════════════════════════════════════════════════════════
    // TAB: Stok — kelola & ubah stok cepat (reuse `products`, `api`, `esc`)
    // ══════════════════════════════════════════════════════════════════════
    let stStore = '';               // filter toko aktif di tab Stok
    const stDirty = new Map();      // key `${pid}:${modelId}` -> nilai stok baru

    // Ratakan produk menjadi baris per varian (atau satu baris jika tanpa varian).
    function stFlatRows() {
        const rows = [];
        // Hanya produk aktif (tampil) — bukan yang diarsipkan/banned di marketplace.
        products.filter(p => p.item_status === 'NORMAL').forEach(p => {
            const models = p.models || [];
            if (models.length) {
                models.forEach(m => rows.push({
                    pid: p.id, product: p, modelId: String(m.model_id),
                    name: p.item_name, variant: m.model_name, sku: m.model_sku || p.item_sku,
                    stock: m.stock ?? 0, image: p.image_url, store: p.store,
                    sales: p.sales, status: p.item_status, mapping: m.mapping || null,
                }));
            } else {
                rows.push({
                    pid: p.id, product: p, modelId: '0',
                    name: p.item_name, variant: null, sku: p.item_sku,
                    stock: p.stock_total ?? 0, image: p.image_url, store: p.store,
                    sales: p.sales, status: p.item_status, mapping: p.mapping || null,
                });
            }
        });
        return rows;
    }

    function stBuildStores() {
        const stores = [...new Map(products.filter(p => p.store).map(p => [p.store.id, p.store.name])).entries()];
        let html = `<button class="store-tab ${stStore === '' ? 'active' : ''}" onclick="stSetStore('')">Semua Toko</button>`;
        stores.forEach(([id, name]) => {
            html += `<button class="store-tab ${stStore === String(id) ? 'active' : ''}" onclick="stSetStore('${id}')">${esc(name)}</button>`;
        });
        if ($('stStoreTabs')) $('stStoreTabs').innerHTML = html;
    }

    window.stSetStore = function (id) { stStore = String(id); stRender(); };

    function stFilteredRows() {
        const q = $('stSearch').value.trim().toLowerCase();
        const band = $('stStock').value;
        return stFlatRows().filter(r => {
            if (stStore && String(r.store?.id) !== stStore) return false;
            const s = r.stock ?? 0;
            if (band === 'empty' && s !== 0) return false;
            if (band === 'low'   && !(s > 0 && s <= 5)) return false;
            if (band === 'in'    && s <= 0) return false;
            if (q) {
                const hay = [r.name, r.sku, r.variant, r.product.item_id,
                    r.mapping?.item_name, r.mapping?.item_code, r.mapping?.category_name]
                    .filter(Boolean).join(' ').toLowerCase();
                if (!hay.includes(q)) return false;
            }
            return true;
        });
    }

    function stSortRows(rows) {
        const sort = $('stSort').value;
        const num = v => v == null ? -1 : Number(v);
        rows.sort((a, b) => {
            switch (sort) {
                case 'stock_desc': return num(b.stock) - num(a.stock);
                case 'sales':      return num(b.sales) - num(a.sales);
                case 'name':       return (a.name || '').localeCompare(b.name || '');
                default:           return num(a.stock) - num(b.stock); // stock_asc
            }
        });
        return rows;
    }

    function stStockClass(n) {
        if ((n ?? 0) === 0) return 'stock-zero';
        if (n <= 5) return 'stock-low';
        return '';
    }

    function stRenderKpis() {
        const rows = stFlatRows().filter(r => !stStore || String(r.store?.id) === stStore);
        const totalSku = rows.length;
        const empty = rows.filter(r => (r.stock ?? 0) === 0).length;
        const low   = rows.filter(r => (r.stock ?? 0) > 0 && (r.stock ?? 0) <= 5).length;
        const units = rows.reduce((s, r) => s + (Number(r.stock) || 0), 0);
        const kpi = (lbl, val) => `<span class="kpi" style="cursor:default"><span class="lbl">${lbl}</span><span class="val">${val}</span></span>`;
        if ($('stKpiRow')) $('stKpiRow').innerHTML =
            kpi('Total SKU', totalSku) +
            kpi('Stok Habis', empty) +
            kpi('Menipis', low) +
            kpi('Total Unit', units.toLocaleString('id-ID'));
    }

    function stUpdateDirtyCount() {
        if ($('stDirtyCount')) $('stDirtyCount').textContent = stDirty.size;
        if ($('stSaveAll')) $('stSaveAll').disabled = stDirty.size === 0;
    }

    // ── Grouping helpers (kategori & nama produk hasil mapping) ──────────
    const stExpanded = new Set(); // kategori yang sedang dibuka (default: semua tertutup)

    function stCatKey(r) {
        if (!r.mapping) return '❓ Belum di-mapping';
        return r.mapping.category_name || '📦 Tanpa Kategori';
    }
    function stItemKey(r) {
        if (!r.mapping) return r.name || '—';
        return (r.mapping.item_code ? r.mapping.item_code + ' · ' : '') + (r.mapping.item_name || ('Item #' + r.mapping.item_id));
    }

    function stInputClass(n) {
        if ((n ?? 0) === 0) return 'st-inp-zero';
        if (n <= 5) return 'st-inp-low';
        return '';
    }

    function stRowHtml(r, compact) {
        const key = `${r.pid}:${r.modelId}`;
        const dirty = stDirty.has(key);
        const inputVal = dirty ? stDirty.get(key) : (r.stock ?? 0);
        const line1 = compact ? (r.variant ? '↳ ' + esc(r.variant) : esc(r.name || '—')) : esc(r.name || '—');
        const meta = [];
        if (!compact && r.variant) meta.push('↳ ' + esc(r.variant));
        meta.push('SKU: ' + esc(r.sku || '—'));
        if (r.store?.name) meta.push(esc(r.store.name));
        const gd = r.mapping;
        const gdAvail = (gd && gd.internal_available != null) ? gd.internal_available : null;
        const gdCls = gdAvail == null ? 'muted' : (gdAvail <= 0 ? 'stock-zero' : (gdAvail <= 5 ? 'stock-low' : ''));
        const gdTip = gdAvail == null ? 'Belum di-mapping ke item internal' : `Fisik: ${(gd.internal_physical ?? 0).toLocaleString('id-ID')} · Dialokasi: ${(gd.internal_allocated ?? 0).toLocaleString('id-ID')}`;
        const gdTxt = gdAvail == null ? '—' : gdAvail.toLocaleString('id-ID');
        return `<tr data-st-key="${key}" class="${dirty ? 'st-dirty-row' : ''} ${compact ? 'st-grp-row' : ''}">
            <td>${r.image ? `<img class="prd-img" src="${esc(r.image)}" loading="lazy">` : '<div class="prd-img"></div>'}</td>
            <td>
                <div class="prd-name" style="-webkit-line-clamp:1" title="${esc(r.name || '')}">${line1}</div>
                <div class="prd-sku">${meta.join(' · ')}</div>
            </td>
            <td class="st-gd-cell"><span class="${gdCls}" title="${gdTip}">${gdTxt}</span></td>
            <td>
                <div class="st-stok-cell">
                    <input type="number" min="0" class="form-control form-control-sm st-stock-input ${stInputClass(r.stock)}" value="${inputVal}" data-pid="${r.pid}" data-model-id="${r.modelId}" data-orig="${r.stock ?? 0}" title="Stok sekarang: ${r.stock ?? 0}">
                    <button class="btn btn-outline-primary btn-mini st-save-btn" onclick="stSaveOne('${key}', this)" title="Simpan ke Shopee">💾</button>
                </div>
            </td>
        </tr>`;
    }

    window.stRender = function () {
        stBuildStores();
        stRenderKpis();
        const grouped = $('stGroup') ? $('stGroup').checked : true;
        const rows = stFilteredRows();
        if (!rows.length) {
            $('stBody').innerHTML = `<tr><td colspan="4" class="empty">${products.length ? 'Tidak ada produk yang cocok dengan filter.' : 'Belum ada produk. Klik "⟳ Sync Shopee".'}</td></tr>`;
            stUpdateDirtyCount();
            return;
        }

        if (!grouped) {
            $('stBody').innerHTML = stSortRows(rows).map(r => stRowHtml(r, false)).join('');
            stUpdateDirtyCount();
            return;
        }

        // Grouping 2 tingkat: Kategori (mapping) → Nama produk (mapping) → varian
        const cats = new Map(); // catName -> Map(itemKey -> rows[])
        rows.forEach(r => {
            const ck = stCatKey(r), ik = stItemKey(r);
            if (!cats.has(ck)) cats.set(ck, new Map());
            const items = cats.get(ck);
            if (!items.has(ik)) items.set(ik, []);
            items.get(ik).push(r);
        });

        const catNames = [...cats.keys()].sort((a, b) => {
            const au = a.startsWith('❓'), bu = b.startsWith('❓'); // bucket belum-mapping paling bawah
            if (au !== bu) return au ? 1 : -1;
            return a.localeCompare(b);
        });

        let html = '';
        catNames.forEach(ck => {
            const items = cats.get(ck);
            let catUnits = 0, catEmpty = 0, catSku = 0;
            items.forEach(rs => rs.forEach(r => { catUnits += Number(r.stock) || 0; if ((r.stock ?? 0) === 0) catEmpty++; catSku++; }));
            const expanded = stExpanded.has(ck);
            html += `<tr class="st-cat-head" data-cat="${esc(ck)}">
                <td colspan="4"><div class="st-cat-inner">
                    <span class="st-caret">${expanded ? '▾' : '▸'}</span>
                    <span class="st-cat-name">${esc(ck)}</span>
                    <span class="st-cat-meta">${items.size} produk · ${catSku} SKU</span>
                    <span class="st-cat-sum">${catEmpty ? `<span class="st-badge-empty">${catEmpty} habis</span>` : ''}<b>${catUnits.toLocaleString('id-ID')}</b> unit</span>
                </div></td></tr>`;
            if (!expanded) return;

            [...items.keys()].sort((a, b) => a.localeCompare(b)).forEach(ik => {
                const rs = stSortRows(items.get(ik));
                const itUnits = rs.reduce((s, r) => s + (Number(r.stock) || 0), 0);
                const itEmpty = rs.filter(r => (r.stock ?? 0) === 0).length;
                const itInternal = rs[0]?.mapping?.internal_available;
                const itIntTxt = (itInternal == null) ? '—' : itInternal.toLocaleString('id-ID');
                const itIntCls = (itInternal == null) ? 'muted' : (itInternal <= 0 ? 'stock-zero' : (itInternal <= 5 ? 'stock-low' : ''));
                html += `<tr class="st-item-head">
                    <td></td>
                    <td><span class="st-item-name">${esc(ik)}</span> <span class="muted">· ${rs.length} varian</span></td>
                    <td class="st-gd-cell"><span class="${itIntCls}" title="Stok gudang internal">${itIntTxt}</span></td>
                    <td style="text-align:right"><span class="${itEmpty ? 'stock-low' : 'muted'}" title="${itEmpty} varian habis">${itUnits.toLocaleString('id-ID')} unit</span></td>
                </tr>`;
                html += rs.map(r => stRowHtml(r, true)).join('');
            });
        });
        $('stBody').innerHTML = html;
        stUpdateDirtyCount();
    };

    // Lacak perubahan input stok (event delegation — baris dirender ulang tiap saat).
    document.addEventListener('input', e => {
        const inp = e.target.closest('.st-stock-input');
        if (!inp) return;
        const key = `${inp.dataset.pid}:${inp.dataset.modelId}`;
        const orig = Number(inp.dataset.orig);
        const val = inp.value === '' ? NaN : parseInt(inp.value);
        const row = inp.closest('tr');
        if (isNaN(val) || val === orig) {
            stDirty.delete(key);
            row && row.classList.remove('st-dirty-row');
        } else {
            stDirty.set(key, val);
            row && row.classList.add('st-dirty-row');
        }
        stUpdateDirtyCount();
    });

    // Simpan stok ke Shopee + sinkron state lokal supaya tab lain ikut ter-update.
    async function stPersist(pid, stockList) {
        await api(`${API}/${pid}/stock`, { method: 'POST', body: JSON.stringify({ stock_list: stockList }) });
        const p = products.find(x => x.id === pid);
        if (!p) return;
        if (p.models && p.models.length) {
            stockList.forEach(({ model_id, stock }) => {
                const m = p.models.find(x => String(x.model_id) === String(model_id));
                if (m) m.stock = stock;
            });
            p.stock_total = p.models.reduce((s, m) => s + (Number(m.stock) || 0), 0);
        } else if (stockList.length) {
            p.stock_total = stockList[0].stock;
        }
    }

    window.stSaveOne = async function (key, btn) {
        const [pid, modelId] = key.split(':');
        const inp = document.querySelector(`.st-stock-input[data-pid="${pid}"][data-model-id="${modelId}"]`);
        if (!inp) return;
        const val = parseInt(inp.value);
        if (isNaN(val) || val < 0) return alert('Stok tidak valid');
        const orig = btn ? btn.innerHTML : '';
        if (btn) { btn.disabled = true; btn.innerHTML = '⏳'; }
        try {
            await stPersist(Number(pid), [{ model_id: modelId, stock: val }]);
            stDirty.delete(key);
            toast('Stok tersimpan ke Shopee ✔');
            stRender();
        } catch (e) {
            alert('Gagal simpan stok: ' + e.message);
            if (btn) { btn.disabled = false; btn.innerHTML = orig; }
        }
    };

    window.stSaveAll = async function () {
        if (!stDirty.size) return;
        const btn = $('stSaveAll');
        const groups = {};
        stDirty.forEach((stock, key) => {
            const [pid, modelId] = key.split(':');
            (groups[pid] ||= []).push({ model_id: modelId, stock });
        });
        const origHtml = btn ? btn.innerHTML : '';
        if (btn) { btn.disabled = true; btn.innerHTML = '⏳ Menyimpan…'; }
        let ok = 0, fail = 0;
        for (const [pid, list] of Object.entries(groups)) {
            try {
                await stPersist(Number(pid), list);
                list.forEach(l => stDirty.delete(`${pid}:${l.model_id}`));
                ok += list.length;
            } catch (e) { fail += list.length; }
        }
        if (btn) btn.innerHTML = origHtml;
        toast(fail ? `${ok} stok tersimpan, ${fail} gagal` : `${ok} stok tersimpan ke Shopee ✔`, fail ? 'warning' : 'success');
        stRender();
    };

    // Event listener filter tab Stok
    ['stSearch','stStock','stSort'].forEach(id => {
        if ($(id)) $(id).addEventListener(id === 'stSearch' ? 'input' : 'change', stRender);
    });
    if ($('stGroup')) $('stGroup').addEventListener('change', stRender);
    // Lipat/buka kategori (event delegation; tbody dirender ulang tiap saat)
    if ($('stBody')) $('stBody').addEventListener('click', e => {
        const head = e.target.closest('.st-cat-head');
        if (!head) return;
        const ck = head.dataset.cat;
        if (stExpanded.has(ck)) stExpanded.delete(ck); else stExpanded.add(ck);
        stRender();
    });

    // ══════════════════════════════════════════════════════════════════════
    // TAB: Naikkan Produk (Boost) — memakai ulang `products`, `api`, `esc`
    // ══════════════════════════════════════════════════════════════════════
    const BAPI = '/api/marketplace/boost';
    let boLoaded = false;

    window.switchTab = function (tab) {
        document.querySelectorAll('.pt-tab').forEach(t => {
            const active = t.dataset.tab === tab;
            t.classList.toggle('active', active);
            t.setAttribute('aria-selected', active ? 'true' : 'false');
        });
        $('tabProduk').classList.toggle('active', tab === 'produk');
        $('tabStok').classList.toggle('active', tab === 'stok');
        $('tabBoost').classList.toggle('active', tab === 'boost');
        if (tab === 'stok') stRender();
        if (tab === 'boost') {
            boBuildStores();
            boLoaded = true;
            boReload();
        }
    };

    const boStoreId = () => $('boStore').value;
    const fmtT = d => d ? new Date(d).toLocaleString('id-ID', { day:'2-digit', month:'short', hour:'2-digit', minute:'2-digit' }) : '—';
    function remLabel(mins) {
        if (mins == null || mins <= 0) return 'aktif';
        const h = Math.floor(mins / 60), m = mins % 60;
        return (h ? h + 'j ' : '') + m + 'm lagi';
    }

    function boBuildStores() {
        const sel = $('boStore'), cur = sel.value;
        const stores = [...new Map(products.filter(p => p.store).map(p => [p.store.id, p.store.name])).entries()];
        sel.innerHTML = stores.map(([id, name]) => `<option value="${id}">${esc(name)}</option>`).join('') || '<option value="">— tidak ada toko —</option>';
        if (cur) sel.value = cur;
        if (!sel.dataset.bound) { sel.addEventListener('change', boReload); sel.dataset.bound = '1'; }
    }

    function boCandidateProducts() {
        const sid = String(boStoreId());
        return products
            .filter(p => String(p.store?.id) === sid && p.item_status === 'NORMAL')
            .sort((a, b) => String(a.item_name || '').localeCompare(String(b.item_name || ''), 'id'));
    }

    function boRenderPicker(type) {
        const isSched = type === 'sched';
        const hidden = $(isSched ? 'boSchedPick' : 'boPoolPick');
        const label = $(isSched ? 'boSchedSelectedLabel' : 'boPoolSelectedLabel');
        const meta = $(isSched ? 'boSchedSelectedMeta' : 'boPoolSelectedMeta');
        const thumb = $(isSched ? 'boSchedSelectedThumb' : 'boPoolSelectedThumb');
        if (!hidden || !label || !meta || !thumb) return;

        const list = boCandidateProducts();
        const selected = String(hidden.value || '');
        if (selected && !list.some(p => String(p.id) === selected)) hidden.value = '';
        const current = String(hidden.value || '');
        const currentProduct = list.find(p => String(p.id) === current);
        if (!currentProduct) {
            label.textContent = 'Pilih produk';
            meta.textContent = list.length ? 'Buka daftar produk aktif' : 'Belum ada produk aktif di toko ini';
            thumb.textContent = '▧';
            thumb.innerHTML = '▧';
            return;
        }
        label.textContent = currentProduct.item_name || 'Produk dipilih';
        meta.textContent = [currentProduct.item_sku || ('ID ' + (currentProduct.item_id || currentProduct.id)), currentProduct.stock_total != null ? 'Stok ' + currentProduct.stock_total : ''].filter(Boolean).join(' · ');
        thumb.innerHTML = currentProduct.image_url
            ? `<img class="bo-choice-thumb" src="${esc(currentProduct.image_url)}" alt="" loading="lazy">`
            : '▧';
    }

    let boPickerType = 'sched';

    function boRenderModalProducts() {
        const grid = $('boModalProductGrid');
        const count = $('boModalProductCount');
        const search = $('boModalProductSearch');
        const hidden = $(boPickerType === 'sched' ? 'boSchedPick' : 'boPoolPick');
        if (!grid || !hidden) return;
        const list = boCandidateProducts();
        const query = String(search?.value || '').trim().toLowerCase();
        const filtered = list.filter(p => !query || [p.item_name, p.item_sku, p.item_id].some(v => String(v || '').toLowerCase().includes(query)));
        count.textContent = `${filtered.length} dari ${list.length} produk aktif`;
        if (!filtered.length) {
            grid.innerHTML = `<div class="bo-picker-empty">${list.length ? 'Produk tidak ditemukan.' : 'Belum ada produk aktif di toko ini.'}</div>`;
            return;
        }
        const current = String(hidden.value || '');
        grid.innerHTML = filtered.map(p => {
            const id = String(p.id), active = id === current;
            const image = p.image_url
                ? `<img src="${esc(p.image_url)}" alt="" loading="lazy">`
                : '<span class="bo-product-thumb">▧</span>';
            const productMeta = [p.item_sku || ('ID ' + (p.item_id || p.id)), p.stock_total != null ? 'Stok ' + p.stock_total : ''].filter(Boolean).join(' · ');
            return `<button type="button" class="bo-product-card ${active ? 'active' : ''}" onclick="boPickProduct('${boPickerType}', ${p.id})" title="${esc(p.item_name || 'Produk')}">
                ${image}<span class="copy"><span class="name">${esc(p.item_name || 'Produk tanpa nama')}</span><span class="meta">${esc(productMeta)}</span></span><span class="check">✓</span>
            </button>`;
        }).join('');
    }

    window.boOpenProductPicker = function (type) {
        boPickerType = type === 'pool' ? 'pool' : 'sched';
        const search = $('boModalProductSearch');
        if (search) search.value = '';
        boRenderModalProducts();
        bootstrap.Modal.getOrCreateInstance($('modalBoostProductPicker')).show();
        setTimeout(() => search?.focus(), 150);
    };

    window.boPickProduct = function (type, id) {
        const hidden = $(type === 'sched' ? 'boSchedPick' : 'boPoolPick');
        if (!hidden) return;
        hidden.value = String(id);
        boRenderPicker(type);
        const modal = $('modalBoostProductPicker');
        if (modal) bootstrap.Modal.getOrCreateInstance(modal).hide();
    };

    function boFillPickers() {
        boRenderPicker('sched');
        boRenderPicker('pool');
    }

    const boModalSearch = $('boModalProductSearch');
    if (boModalSearch) boModalSearch.addEventListener('input', boRenderModalProducts);

    window.boReload = function () {
        boFillPickers();
        boStatus(); boScheds(); boPool(); boLogs();
    };

    async function boStatus() {
        $('boSlots').textContent = '…';
        $('boSlotBar').innerHTML = ''; $('boBoosted').innerHTML = '';
        let d;
        try { d = await api(`${BAPI}/status?store_id=${boStoreId()}`); }
        catch (e) { $('boSlots').textContent = ''; $('boBoosted').innerHTML = `<span class="bo-empty text-danger">${esc(e.message)}</span>`; return; }
        if (d.error) { $('boSlots').textContent = ''; $('boBoosted').innerHTML = `<span class="bo-empty text-danger">${esc(d.error)}</span>`; return; }

        const used = d.used || (d.items || []).length, max = d.max || 5;
        $('boSlots').textContent = `${used}/${max} slot terpakai`;
        let bar = '';
        for (let i = 0; i < max; i++) bar += `<div class="bo-slot ${i < used ? 'filled' : ''}">${i < used ? '🚀' : ''}</div>`;
        $('boSlotBar').innerHTML = bar;
        $('boBoosted').innerHTML = (d.items || []).length
            ? d.items.map(it => `<div class="bo-chip">${it.image_url ? `<img src="${esc(it.image_url)}">` : ''}<div><div class="nm" title="${esc(it.name)}">${esc(it.name)}</div><div class="rm">${remLabel(it.remaining_minutes)}</div></div></div>`).join('')
            : '<span class="bo-empty">Belum ada produk yang di-boost.</span>';
    }

    async function boScheds() {
        const c = $('boSchedList'); c.innerHTML = '<div class="bo-empty">Memuat…</div>';
        let d;
        try { d = await api(`${BAPI}/schedules?store_id=${boStoreId()}`); }
        catch (e) { c.innerHTML = `<div class="bo-empty text-danger">${esc(e.message)}</div>`; return; }
        const byProd = {};
        (d.schedules || []).forEach(s => { (byProd[s.product_id] ||= { name:s.product, sku:s.sku, img:s.image_url, rows:[] }).rows.push(s); });
        const keys = Object.keys(byProd);
        c.innerHTML = keys.length ? keys.map(k => {
            const g = byProd[k];
            const pills = g.rows.sort((a,b)=>a.time.localeCompare(b.time)).map(r =>
                `<span class="bo-timepill ${r.is_active ? '' : 'off'}">${r.time}
                    <a href="javascript:boToggleSched(${r.id})" title="aktif/nonaktif">${r.is_active ? '⏸' : '▶'}</a>
                    <a href="javascript:boDelSched(${r.id})" title="hapus" style="color:#dc2626">✕</a></span>`).join('');
            return `<div class="bo-row">${g.img ? `<img src="${esc(g.img)}">` : ''}<div class="grow"><div class="nm">${esc(g.name || '—')}</div><div class="meta">${esc(g.sku || '')}</div></div><div style="text-align:right">${pills}</div></div>`;
        }).join('') : '<div class="bo-empty">Belum ada jadwal.</div>';
    }

    function boTimeFieldHtml(value) {
        return `<label class="bo-time-field"><input type="time" class="bo-t" value="${esc(value || '')}" aria-label="Jam naik"><button type="button" class="bo-time-remove" onclick="boRemoveTime(this)" aria-label="Hapus jam">×</button></label>`;
    }

    function boUpdateTimeSummary() {
        const fields = [...document.querySelectorAll('#boSchedTimes .bo-t')];
        const values = fields.map(input => input.value).filter(Boolean);
        const count = $('boTimeCount');
        if (count) count.textContent = `${values.length} jam dipilih`;
        document.querySelectorAll('#boTimePresets .bo-time-preset').forEach(button => button.classList.toggle('active', values.includes(button.dataset.time)));
        document.querySelectorAll('#boSchedTimes .bo-time-remove').forEach(button => { button.disabled = fields.length <= 1; });
    }

    function boResetTimes(value = '08:00') {
        const target = $('boSchedTimes');
        if (!target) return;
        target.innerHTML = boTimeFieldHtml(value);
        boUpdateTimeSummary();
    }

    window.boAddTime = function (value = '20:00') {
        const target = $('boSchedTimes');
        if (!target) return;
        const current = [...target.querySelectorAll('.bo-t')].map(input => input.value).filter(Boolean);
        const next = value && !current.includes(value) ? value : ['08:00', '12:00', '16:00', '20:00'].find(time => !current.includes(time)) || '';
        target.insertAdjacentHTML('beforeend', boTimeFieldHtml(next));
        boUpdateTimeSummary();
    };

    window.boAddPresetTime = function (value) { boAddTime(value); };
    window.boRemoveTime = function (button) {
        const fields = document.querySelectorAll('#boSchedTimes .bo-time-field');
        if (fields.length <= 1) return;
        button.closest('.bo-time-field')?.remove();
        boUpdateTimeSummary();
    };

    $('boSchedTimes')?.addEventListener('input', boUpdateTimeSummary);
    boResetTimes();

    window.boSaveSched = async function () {
        const pid = parseInt($('boSchedPick').value);
        if (!pid) return alert('Pilih produk dulu.');
        const times = [...new Set([...document.querySelectorAll('#boSchedTimes .bo-t')].map(i => i.value).filter(Boolean))].sort();
        if (!times.length) return alert('Isi minimal satu jam.');
        try {
            const res = await api(`${BAPI}/schedules`, { method:'POST', body: JSON.stringify({ store_id: boStoreId(), marketplace_product_id: pid, times }) });
            toast(res.message);
            $('boSchedPick').value = '';
            boRenderPicker('sched');
            boResetTimes();
            boScheds();
        } catch (e) { toast('Gagal: ' + e.message, 'error'); }
    };

    window.boToggleSched = async function (id) { await api(`${BAPI}/schedules/${id}/toggle`, { method:'POST' }); boScheds(); };
    window.boDelSched = async function (id) { if (!confirm('Hapus slot jadwal ini?')) return; await api(`${BAPI}/schedules/${id}`, { method:'DELETE' }); boScheds(); };

    async function boPool() {
        const c = $('boPoolList'); c.innerHTML = '<div class="bo-empty">Memuat…</div>';
        let d;
        try { d = await api(`${BAPI}/pool?store_id=${boStoreId()}`); }
        catch (e) { c.innerHTML = `<div class="bo-empty text-danger">${esc(e.message)}</div>`; return; }
        c.innerHTML = (d.pool || []).length ? d.pool.map(p => `
            <div class="bo-row ${p.is_active ? '' : 'bo-off'}">${p.image_url ? `<img src="${esc(p.image_url)}">` : ''}
                <div class="grow"><div class="nm">${esc(p.product || '—')}</div>
                    <div class="meta">terakhir naik: ${p.last_boosted_at ? fmtT(p.last_boosted_at) : 'belum pernah'}</div></div>
                <button class="btn btn-prd-outline btn-mini" onclick="boTogglePool(${p.id})">${p.is_active ? '⏸' : '▶'}</button>
                <button class="btn btn-outline-danger btn-mini" onclick="boDelPool(${p.id})">✕</button></div>`).join('')
            : '<div class="bo-empty">Antrian kosong.</div>';
    }

    window.boAddPool = async function () {
        const pid = parseInt($('boPoolPick').value);
        if (!pid) return alert('Pilih produk dulu.');
        try {
            const res = await api(`${BAPI}/pool`, { method:'POST', body: JSON.stringify({ store_id: boStoreId(), product_ids: [pid] }) });
            toast(res.message); $('boPoolPick').value = ''; boRenderPicker('pool'); boPool();
        } catch (e) { toast('Gagal: ' + e.message, 'error'); }
    };

    window.boTogglePool = async function (id) { await api(`${BAPI}/pool/${id}/toggle`, { method:'POST' }); boPool(); };
    window.boDelPool = async function (id) { if (!confirm('Keluarkan dari antrian?')) return; await api(`${BAPI}/pool/${id}`, { method:'DELETE' }); boPool(); };

    async function boLogs() {
        const c = $('boLogs'); c.innerHTML = '<div class="bo-empty">Memuat…</div>';
        let d;
        try { d = await api(`${BAPI}/logs?store_id=${boStoreId()}`); }
        catch (e) { c.innerHTML = `<div class="bo-empty text-danger">${esc(e.message)}</div>`; return; }
        const rows = (d.logs || []).slice(0, 50);
        c.innerHTML = rows.length ? rows.map(l => `
            <div class="bo-row"><span class="bo-tag tag-${l.source}">${l.source}</span>
                <div class="grow"><div class="nm">${esc(l.product)}</div>
                    <div class="meta">${l.success ? '' : '⚠ ' + esc(l.message || 'gagal') + ' · '}${fmtT(l.boosted_at)}</div></div>
                <span>${l.success ? '✅' : '❌'}</span></div>`).join('')
            : '<div class="bo-empty">Belum ada riwayat.</div>';
    }

    // ── Realtime ────────────────────────────────────────────────────────────
    if (window.Echo) {
        try {
            window.Echo.channel('marketplace').listen('ProductUpdated', () => loadProducts());
            const conn = window.Echo.connector?.pusher?.connection;
            if (conn) {
                const upd = () => { $('rtLabel').textContent = conn.state === 'connected' ? '· ⚡ realtime' : ''; };
                conn.bind('connected', upd); conn.bind('disconnected', upd);
                upd();
            }
        } catch (e) {}
    }

    loadProducts();
})();
</script>
@endpush
