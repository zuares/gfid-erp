@extends('layouts.app')
@section('title', 'Marketplace • Toko & Channel')

@include('marketplace._shared')

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
        background: var(--card, #fff);
        border-radius: 8px;
        border: 1px solid var(--shp-border);
        box-shadow: none;
        margin-bottom: 1.5rem;
    }
    body[data-theme="dark"] .card-main{
        background: var(--card, #0f172a);
        border-color: rgba(51,65,85,.85);
        box-shadow: none;
    }

    @media (min-width: 768px) {
        .table-responsive {
            overflow: visible !important;
        }
    }
    
    .table-responsive .dropdown-menu {
        z-index: 1055 !important;
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
    .sub{ color:var(--shp-muted); font-size:.78rem; display:none; }
    
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
    .btn-pill{ border-radius:7px; padding-inline:.78rem; box-shadow:none!important; font-weight:600; font-size:.78rem; }
    .btn-ship-primary{ background:var(--shp-accent)!important; border-color:var(--shp-accent)!important; color:#fff!important; }
    .btn-ship-primary:hover{ background:var(--shp-accent-2)!important; border-color:var(--shp-accent-2)!important; color:#fff!important; }
    .btn-ship-outline{ color:#475569!important; background:transparent!important; border:1px solid rgba(148,163,184,.35)!important; }
    .btn-ship-outline:hover{ background:rgba(148,163,184,.08)!important; color:#111827!important; }

    .grid-stores {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(310px, 1fr));
        gap: 1.25rem;
    }
    .store-card {
        background: var(--card, #ffffff);
        border: 1px solid var(--shp-border);
        border-radius: 12px;
        padding: 1.25rem;
        display: flex;
        flex-direction: column;
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
        overflow: hidden;
    }
    .store-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.01);
        border-color: rgba(148,163,184,.4);
    }
    body[data-theme="dark"] .store-card {
        background: var(--card, #0f172a);
        border-color: rgba(51,65,85,.85);
    }
    body[data-theme="dark"] .store-card:hover {
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.3);
        border-color: rgba(148,163,184,.3);
    }

    .store-header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        margin-bottom: 1rem;
        padding-bottom: 1rem;
        border-bottom: 1px dashed var(--shp-border-strong);
    }
    .store-brand {
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }
    .store-brand-icon {
        width: 38px;
        height: 38px;
        border-radius: 8px;
        object-fit: contain;
        background: #f8fafc;
        padding: 4px;
        border: 1px solid rgba(148,163,184,.15);
    }
    body[data-theme="dark"] .store-brand-icon { background: rgba(255,255,255,0.05); }
    .store-title-wrap {
        display: flex;
        flex-direction: column;
        justify-content: center;
    }
    .store-name { font-weight: 800; font-size: 1.05rem; color: #0f172a; margin: 0; line-height: 1.2; display: flex; align-items: center; gap: .3rem;}
    body[data-theme="dark"] .store-name { color: #f1f5f9; }
    .store-id { font-size: 0.7rem; color: #64748b; font-family: monospace; letter-spacing: 0.5px; margin-top: 2px;}

    .rename-btn { background:none;border:none;padding:0 .2rem;cursor:pointer;color:#94a3b8;font-size:.85rem;line-height:1; }
    .rename-btn:hover { color:#0f172a; }
    body[data-theme="dark"] .rename-btn:hover { color:#f1f5f9; }
    .rename-inline { display:none;align-items:center;gap:.4rem; margin-top:.35rem; }
    .rename-inline input { font-size:.8rem;font-weight:600;border:1px solid var(--shp-border-strong);border-radius:6px;padding:.2rem .4rem;width:100%; background:transparent; color:inherit; }
    .rename-inline .btn-save-name { font-size:.7rem;font-weight:700;border-radius:6px;padding:.2rem .4rem; }

    .badge-status {
        border-radius: 6px; padding: .25rem .6rem;
        font-size: .7rem; letter-spacing: 0; text-transform: none;
        border: 1px solid transparent;
        display: inline-flex; align-items: center; gap: .35rem;
        white-space: nowrap; font-weight: 700;
    }
    .badge-status::before { content: ''; width: 6px; height: 6px; border-radius: 999px; display: inline-block; }

    .st-connected{ background: rgba(34, 197, 94, 0.12); color:#166534; border-color: rgba(34, 197, 94, 0.30); }
    .st-connected::before{ background: rgba(34, 197, 94, 0.95); box-shadow: 0 0 6px rgba(34,197,94,0.6);}
    body[data-theme="dark"] .st-connected { color: #4ade80; }
    
    .st-not-connected{ background: rgba(148, 163, 184, 0.12); color:#475569; border-color: rgba(148, 163, 184, 0.30); }
    .st-inactive{ background: rgba(100, 116, 139, 0.10); color:#64748b; border-color: rgba(100, 116, 139, 0.25); }
    .st-inactive::before{ background: rgba(100, 116, 139, 0.6); }
    .store-card.is-inactive{ opacity:.72; }
    .st-not-connected::before{ background: rgba(100, 116, 139, 0.95); }
    body[data-theme="dark"] .st-not-connected { color: #94a3b8; }
    
    .st-auth-required{ background: rgba(239, 68, 68, 0.12); color:#991b1b; border-color: rgba(239, 68, 68, 0.30); }
    .st-auth-required::before{ background: rgba(239, 68, 68, 0.95); box-shadow: 0 0 6px rgba(239,68,68,0.6);}
    body[data-theme="dark"] .st-auth-required { color: #f87171; }
    
    .st-warning{ background: rgba(245, 158, 11, 0.12); color:#b45309; border-color: rgba(245, 158, 11, 0.30); }
    .st-warning::before{ background: rgba(245, 158, 11, 0.95); box-shadow: 0 0 6px rgba(245,158,11,0.6);}
    body[data-theme="dark"] .st-warning { color: #fbbf24; }

    .store-stats {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 0.5rem;
        margin-bottom: 1.25rem;
    }
    .stat-box {
        background: rgba(241, 245, 249, 0.5);
        border-radius: 8px;
        padding: 0.75rem 0.5rem;
        text-align: center;
        border: 1px solid transparent;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
    }
    body[data-theme="dark"] .stat-box { background: rgba(30, 41, 59, 0.5); }
    
    .stat-box.warn { background: rgba(254, 243, 199, 0.4); border-color: rgba(253, 230, 138, 0.5); }
    body[data-theme="dark"] .stat-box.warn { background: rgba(120, 53, 15, 0.3); border-color: rgba(146, 64, 14, 0.4); }
    
    .stat-box.err { background: rgba(254, 226, 226, 0.4); border-color: rgba(254, 202, 202, 0.5); }
    body[data-theme="dark"] .stat-box.err { background: rgba(127, 29, 29, 0.3); border-color: rgba(153, 27, 27, 0.4); }

    .stat-val { font-size: 1.2rem; font-weight: 800; color: #0f172a; line-height: 1; margin-bottom: 0.2rem; }
    body[data-theme="dark"] .stat-val { color: #f8fafc; }
    .stat-lbl { font-size: 0.65rem; color: #64748b; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; }
    body[data-theme="dark"] .stat-lbl { color: #94a3b8; }
    
    .stat-box.warn .stat-val { color: #b45309; }
    body[data-theme="dark"] .stat-box.warn .stat-val { color: #fbbf24; }
    .stat-box.err .stat-val { color: #b91c1c; }
    body[data-theme="dark"] .stat-box.err .stat-val { color: #f87171; }

    .store-actions {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
        margin-top: auto;
    }
    .action-row {
        display: flex;
        gap: 0.5rem;
    }
    .btn-action-primary {
        flex: 1;
        background: var(--shp-accent);
        color: #fff;
        border: none;
        border-radius: 8px;
        padding: 0.5rem;
        font-weight: 600;
        font-size: 0.8rem;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.4rem;
        transition: all 0.2s;
    }
    .btn-action-primary:hover { background: var(--shp-accent-2); color: #fff; transform: translateY(-1px); }
    
    .btn-action-secondary {
        flex: 1;
        background: transparent;
        color: #475569;
        border: 1px solid var(--shp-border-strong);
        border-radius: 8px;
        padding: 0.5rem;
        font-weight: 600;
        font-size: 0.8rem;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.4rem;
        transition: all 0.2s;
    }
    body[data-theme="dark"] .btn-action-secondary { color: #cbd5e1; }
    .btn-action-secondary:hover { background: rgba(148,163,184,.08); color: #0f172a; border-color: #94a3b8; }
    body[data-theme="dark"] .btn-action-secondary:hover { color: #fff; border-color: #cbd5e1; }
    
    .btn-action-danger {
        flex: 1;
        background: rgba(239, 68, 68, 0.1);
        color: #dc2626;
        border: 1px solid rgba(239, 68, 68, 0.3);
        border-radius: 8px;
        padding: 0.5rem;
        font-weight: 600;
        font-size: 0.8rem;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.4rem;
        transition: all 0.2s;
        text-decoration: none;
    }
    body[data-theme="dark"] .btn-action-danger { color: #f87171; }
    .btn-action-danger:hover { background: rgba(239, 68, 68, 0.2); color: #b91c1c; }

    .btn-more {
        width: 36px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: transparent;
        border: 1px solid var(--shp-border-strong);
        border-radius: 8px;
        color: #64748b;
        transition: all 0.2s;
    }
    .btn-more:hover { background: rgba(148,163,184,.1); color: #0f172a; }
    body[data-theme="dark"] .btn-more:hover { color: #f8fafc; }

    .store-footer {
        margin-top: 1rem;
        padding-top: 0.75rem;
        border-top: 1px solid var(--shp-border);
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-size: 0.7rem;
        color: #94a3b8;
    }
    .sync-time { display: flex; align-items: center; gap: 0.3rem; }

    .empty-state {
        grid-column: 1 / -1;
        background: var(--card, #fff);
        border: 1px dashed var(--shp-border-strong);
        border-radius: 12px;
        padding: 4rem 2rem;
        text-align: center;
        color: #64748b;
    }
    body[data-theme="dark"] .empty-state { background: rgba(15, 23, 42, 0.5); color: #9ca3af; }
    .empty-state i { font-size: 3rem; color: #cbd5e1; margin-bottom: 1rem; display: block; }
    body[data-theme="dark"] .empty-state i { color: #475569; }

    /* Summary Bar */
    .summary-bar {
        background: var(--card, #fff);
        border: 1px solid var(--shp-border);
        border-radius: 12px;
        padding: 1rem 1.25rem;
        margin-top: 1.5rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 1rem;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.02);
    }
    body[data-theme="dark"] .summary-bar { background: var(--card, #0f172a); }
    .summary-label { font-weight: 700; color: #475569; font-size: 0.9rem; }
    body[data-theme="dark"] .summary-label { color: #cbd5e1; }
    .summary-stats { display: flex; gap: 1.5rem; }
    .summary-stat-item { display: flex; align-items: center; gap: 0.5rem; font-size: 0.85rem; font-weight: 600; }
</style>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
@endpush

@section('content')
<div class="page-wrap">

    <div class="ship-topbar">
        <div>
            <div class="title">Toko & Channel</div>
            <div class="kpis">
                <span class="kpi" title="Channel aktif"><span class="lbl">Channel</span><span class="val" id="kpiChannels">—</span></span>
                <span class="kpi" title="Toko terhubung"><span class="lbl">Toko</span><span class="val" id="kpiStores">—</span></span>
                <span class="kpi" title="Toko dengan token valid"><span class="lbl">Token Valid</span><span class="val" id="kpiTokenExp">—</span></span>
            </div>
        </div>

        <div class="controls">
            <button type="button" class="btn btn-sm btn-ship-outline btn-pill" onclick="openWebhookLogs()" style="border-color:#e2e8f0;">
                <i class="bi bi-file-earmark-code"></i> Log Webhook
            </button>
            <a href="{{ route('marketplace.shopee.connect') }}" class="btn btn-sm btn-ship-outline btn-pill">
                <img src="https://logodownload.org/wp-content/uploads/2021/03/shopee-logo-0.png" style="height:14px; margin-right:4px; vertical-align:middle;"> + Shopee
            </a>
            <a href="{{ route('marketplace.tiktok.connect') }}" class="btn btn-sm btn-ship-primary btn-pill" style="background:#000!important; border-color:#000!important; color:#fff!important;">
                <img src="https://cdn4.iconfinder.com/data/icons/social-media-flat-7/64/Social-media_Tiktok-512.png" style="height:14px; margin-right:4px; vertical-align:middle; filter:brightness(0) invert(1);"> + TikTok
            </a>
        </div>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="fw-bold m-0" style="color:var(--shp-accent)"><i class="bi bi-shop me-2" style="color:#3b82f6;"></i> Daftar Toko Anda</h5>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-ship-outline btn-pill btn-sm shadow-sm" onclick="bootstrap_()"><i class="bi bi-magic me-1"></i> Setup Default</button>
            <button type="button" class="btn btn-ship-primary btn-pill btn-sm shadow-sm" onclick="loadAll()"><i class="bi bi-arrow-clockwise me-1"></i> Segarkan</button>
        </div>
    </div>

    <div id="storeBody" class="grid-stores">
        <div class="empty-state"><div class="spinner-border text-primary"></div><div class="mt-3">Memuat data toko...</div></div>
    </div>

    <div id="storeSummary" class="summary-bar" style="display:none;">
        <div class="summary-label"><i class="bi bi-pie-chart-fill me-2 text-primary"></i> Ringkasan Total</div>
        <div class="summary-stats" id="summaryStats"></div>
    </div>

</div>

{{-- Modal & Skrip pendukung lainnya disembunyikan untuk kerapihan, tapi tetap berjalan --}}
<div class="modal fade" id="syncModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered" style="max-width:350px">
        <div class="modal-content" style="border-radius:12px">
            <div class="modal-header border-0 pb-0">
                <div>
                    <h6 class="modal-title fw-black" id="syncModalTitle">Sync Order</h6>
                </div>
                <button class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="syncAlert" class="alert d-none mb-3" style="border-radius:8px;font-size:.8rem;padding:.5rem"></div>
                <div class="d-flex gap-2 mb-3">
                    <div class="flex-fill">
                        <label class="form-label fw-bold" style="font-size:.7rem;color:#64748b;margin-bottom:.2rem">DARI TANGGAL</label>
                        <input type="date" class="form-control form-control-sm" id="syncFrom" style="border-radius:6px">
                    </div>
                    <div class="flex-fill">
                        <label class="form-label fw-bold" style="font-size:.7rem;color:#64748b;margin-bottom:.2rem">SAMPAI TANGGAL</label>
                        <input type="date" class="form-control form-control-sm" id="syncTo" style="border-radius:6px">
                    </div>
                </div>
                <div class="mb-3">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" role="switch" id="tokoSyncDryRun" style="cursor:pointer">
                        <label class="form-check-label fw-bold" for="tokoSyncDryRun" style="font-size:.75rem;color:#64748b;cursor:pointer">Mode Uji Coba (Tanpa Simpan)</label>
                    </div>
                </div>
                <div class="d-flex justify-content-end gap-2">
                    <button class="btn btn-ship-outline btn-pill btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button class="btn btn-ship-primary btn-pill btn-sm" id="syncBtn" onclick="doSync()">Sync Sekarang</button>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="syncResultModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered" style="max-width:400px">
        <div class="modal-content" style="border-radius:12px">
            <div class="modal-header border-0 pb-0">
                <div>
                    <h6 class="modal-title fw-black">✅ Hasil Sinkronisasi</h6>
                    <div class="text-muted" style="font-size:.75rem" id="syncResultSub">—</div>
                </div>
                <button class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="syncResultBody"></div>
            <div class="modal-footer border-0 pt-0" id="syncResultFooter"></div>
        </div>
    </div>
</div>

<div class="modal fade" id="infoModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content" style="border-radius:12px">
            <div class="modal-header border-0 pb-2">
                <h6 class="modal-title fw-black" id="infoModalTitle">Detail Teknis Toko</h6>
                <button class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body pt-0">
                <div class="input-group input-group-sm mb-3" id="orderStatusGroup" style="display:none;">
                    <span class="input-group-text bg-light text-muted fw-bold">Status Pesanan:</span>
                    <select class="form-select" id="orderStatusSelect">
                        <option value="">Semua Status</option>
                        <option value="UNPAID">UNPAID (Belum Dibayar)</option>
                        <option value="READY_TO_SHIP">READY_TO_SHIP (Perlu Dikirim)</option>
                        <option value="PROCESSED">PROCESSED (Sudah Diproses)</option>
                        <option value="SHIPPED">SHIPPED (Dikirim)</option>
                        <option value="COMPLETED">COMPLETED (Selesai)</option>
                        <option value="CANCELLED">CANCELLED (Dibatalkan)</option>
                    </select>
                    <button class="btn btn-ship-primary px-3" type="button" id="btnFetchOrderList">Tarik Data</button>
                </div>
                <pre class="bg-light rounded p-3 small mb-0" id="infoOutput" style="max-height:65vh;overflow:auto"></pre>
            </div>
        </div>
    </div>
</div>

<!-- Webhook Logs Modal -->
<div class="modal fade" id="webhookModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Live Webhook Logs (Shopee)</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <div class="list-group list-group-flush" id="webhookLogList">
                    <div class="p-4 text-center text-muted">Memuat logs...</div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" onclick="openWebhookLogs()">Refresh</button>
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<!-- API Test Modal -->
<div class="modal fade" id="apiTestModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="apiTestModalTitle">Tes API Shopee</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body bg-light">
                <div class="input-group mb-3">
                    <span class="input-group-text bg-white" id="apiTestInputLabel">Order SN</span>
                    <input type="text" id="apiTestInput" class="form-control form-control-sm" placeholder="Contoh: 2404098R48U37H">
                    <button class="btn btn-primary btn-sm px-3" id="btnTestApi" style="font-weight:600">Fetch JSON</button>
                </div>
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white py-2 d-flex justify-content-between align-items-center">
                        <span class="fw-bold" style="font-size:0.8rem; color:#475569">Response:</span>
                        <button class="btn btn-sm text-muted py-0" onclick="navigator.clipboard.writeText(document.getElementById('apiTestOutput').textContent); alert('Tersalin!')" title="Copy JSON"><i class="bi bi-clipboard"></i></button>
                    </div>
                    <div class="card-body p-0">
                        <pre id="apiTestOutput" class="m-0 p-3" style="max-height: 50vh; font-size: 0.75rem; color:#0f172a; background:#f8fafc; border-bottom-left-radius:8px; border-bottom-right-radius:8px; overflow-y:auto;"></pre>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
(function () {
    const { api, fmt, fmtDate, esc, channelPill, statusBadge } = window.mpHelpers;
    let channels = [], stores = [], warehouses = [], storeStats = {};
    let syncStoreId = null, syncStoreName = '';
    const $ = id => document.getElementById(id);

    // ── Icons for Platform ────────────────────────────────────────────────
    const platformIcon = (channelCode) => {
        if (!channelCode) return '';
        const c = channelCode.toUpperCase();
        if (c === 'SHP' || c === 'SHOPEE') return `<img src="https://logodownload.org/wp-content/uploads/2021/03/shopee-logo-0.png" class="platform-icon" title="Shopee">`;
        if (c === 'TKT' || c === 'TIKTOK') return `<img src="https://cdn4.iconfinder.com/data/icons/social-media-flat-7/64/Social-media_Tiktok-512.png" class="platform-icon" title="TikTok Shop">`;
        return `<span class="badge bg-secondary" style="font-size:.65rem">${esc(c)}</span>`;
    };

    const fmtShortDate = dStr => {
        if (!dStr) return '—';
        const d = new Date(dStr);
        if (isNaN(d)) return '—';
        const t = d.toTimeString().substring(0,5);
        return `${d.getDate()} ${d.toLocaleString('id',{month:'short'})} ${t}`;
    };

    async function loadAll() {
        $('storeBody').innerHTML = `<tr><td colspan="8" class="empty"><div class="spinner-border spinner-border-sm text-secondary me-2"></div> Memuat toko...</td></tr>`;
        const [cRes, sRes, wRes, statRes] = await Promise.allSettled([
            api('/api/marketplace/channels'),
            api('/api/marketplace/stores'),
            api('/api/marketplace/warehouses'),
            api('/api/marketplace/stores-summary'),
        ]);
        channels   = cRes.value   || [];
        stores     = sRes.value   || [];
        warehouses = wRes.value   || [];
        storeStats = statRes.value || {};
        renderKpi();
        renderStoreCards();
    }

    function renderKpi() {
        $('kpiChannels').textContent = channels.length;
        $('kpiStores').textContent   = stores.length;
        const valid = stores.filter(s => s.token_expires_at && new Date(s.token_expires_at) > new Date()).length;
        $('kpiTokenExp').textContent = valid;
    }

    function renderStoreCards() {
        const body = $('storeBody');
        const summary = $('storeSummary');
        if (!stores.length) {
            body.innerHTML = `<div class="empty-state"><i class="bi bi-inboxes"></i><div>Belum ada toko yang terhubung.</div></div>`;
            summary.style.display = 'none';
            return;
        }

        let totalOrders = 0;
        let totalUnfulfil = 0;
        let totalIssues = 0;

        body.innerHTML = stores.map((s, idx) => {
            const stats = storeStats[String(s.id)] || {};
            const issues    = stats.issues    || 0;
            const orders    = stats.orders_today || 0;
            const unfulfil  = stats.unfulfilled  || 0;
            
            totalOrders += orders;
            totalUnfulfil += unfulfil;
            totalIssues += issues;
            
            let statusClass = 'st-not-connected';
            let statusLabel = 'Terputus';
            if (s.connection_status === 'CONNECTED') { statusClass = 'st-connected'; statusLabel = 'Tersambung'; }
            else if (s.connection_status === 'AUTH_REQUIRED') { statusClass = 'st-auth-required'; statusLabel = 'Akses Ditolak'; }
            else if (s.connection_status !== 'NOT_CONNECTED') { statusClass = 'st-warning'; statusLabel = 'Perlu Login'; }

            // Toko nonaktif (sengaja tidak dipakai) → tidak diberi peringatan koneksi.
            const inactive = s.is_active === false;
            if (inactive) { statusClass = 'st-inactive'; statusLabel = 'Nonaktif'; }

            const channelCode = s.channel ? (s.channel.code || '').toUpperCase() : '';
            const connectUrl = `/marketplace/${channelCode === 'TKT' || channelCode === 'TIKTOK' ? 'tiktok' : 'shopee'}/connect?store_id=${s.id}`;
            const isConn = s.connection_status === 'CONNECTED';

            return `
            <div class="store-card ${inactive ? 'is-inactive' : ''}">
                <div class="store-header">
                    <div class="store-brand">
                        <div class="store-brand-icon">${platformIcon(channelCode)}</div>
                        <div class="store-title-wrap">
                            <h3 class="store-name" id="store-name-${s.id}">
                                ${esc(s.name || 'Toko Tanpa Nama')}
                                <button class="rename-btn" title="Ubah Nama" onclick="startRename(${s.id})"><i class="bi bi-pencil-square"></i></button>
                            </h3>
                            <div class="rename-inline" id="rename-inline-${s.id}">
                                <input type="text" id="rename-input-${s.id}" value="${esc(s.name || '')}">
                                <button class="btn btn-ship-primary btn-save-name" onclick="saveRename(${s.id})">Simpan</button>
                                <button class="btn btn-ship-outline btn-save-name" onclick="cancelRename(${s.id})">Batal</button>
                            </div>
                            <div class="store-id">ID: ${esc(s.external_shop_id || '—')}</div>
                            ${s.token_expires_at ? `<div style="font-size: 0.65rem; color: #64748b; margin-top: 4px;"><i class="bi bi-key text-warning"></i> Exp: ${new Date(s.token_expires_at).toLocaleString('id-ID', {day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit'})}</div>` : ''}
                        </div>
                    </div>
                    <div class="dropdown">
                        <button class="btn-more px-2 py-1" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Menu Lanjutan">
                            <i class="bi bi-three-dots-vertical"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow-sm" style="font-size:.8rem; border-radius:8px">
                            <li><button class="dropdown-item py-2 fw-semibold text-primary" onclick="forceSyncStore(${s.id}, '${esc(s.name)}')"><i class="bi bi-arrow-repeat me-2"></i>Tarik Data (Semua & Sekarang)</button></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item py-2 fw-semibold" href="/marketplace/orders?store_id=${s.id}"><i class="bi bi-card-list me-2"></i>Semua Pesanan</a></li>
                            <li><a class="dropdown-item py-2 fw-semibold" href="/marketplace/fulfillment"><i class="bi bi-box-seam me-2"></i>Menu Packing</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><button class="dropdown-item py-2 fw-semibold" onclick="checkStore(${s.id}, '${esc(s.name)}')"><i class="bi bi-info-square me-2"></i>Shop Info API</button></li>
                            ${(channelCode === 'SHP' || channelCode === 'SHOPEE') ? `
                            <li><button class="dropdown-item py-2 fw-semibold" onclick="simulateWebhook(${s.id}, '${esc(s.name)}', 'shopee', '${s.external_shop_id}', 'order_status_update')"><i class="bi bi-broadcast text-primary me-2"></i>Simulasi: Tes Order Baru</button></li>
                            <li><button class="dropdown-item py-2 fw-semibold" onclick="simulateWebhook(${s.id}, '${esc(s.name)}', 'shopee', '${s.external_shop_id}', 'auth_expiry_push')"><i class="bi bi-broadcast text-warning me-2"></i>Simulasi: Token Expired (Push 12)</button></li>
                            <li><button class="dropdown-item py-2 fw-semibold" onclick="testOrderDetail(${s.id}, '${esc(s.name)}')"><i class="bi bi-bug text-info me-2"></i>Tes API: get_order_detail</button></li>
                            <li><button class="dropdown-item py-2 fw-semibold" onclick="testPackageDetail(${s.id}, '${esc(s.name)}')"><i class="bi bi-box text-success me-2"></i>Tes API: get_package_detail</button></li>
                            <li><button class="dropdown-item py-2 fw-semibold" onclick="testReturnList(${s.id}, '${esc(s.name)}')"><i class="bi bi-arrow-return-left text-danger me-2"></i>Tes API: get_return_list</button></li>
                            <li><button class="dropdown-item py-2 fw-semibold" onclick="triggerHistoricalBackfill(${s.id}, '${esc(s.name)}')"><i class="bi bi-clock-history text-primary me-2"></i>Tarik Histori (Mesin Waktu)</button></li>
                            ` : ''}
                            <li><a class="dropdown-item py-2 fw-semibold text-warning" href="${connectUrl}"><i class="bi bi-key me-2"></i>Otorisasi Ulang (Re-Auth)</a></li>
                            <li><button class="dropdown-item py-2 fw-semibold" onclick="disconnectStore(${s.id}, '${esc(s.name)}')"><i class="bi bi-plug text-secondary me-2"></i>Putuskan Koneksi</button></li>
                            <li><button class="dropdown-item py-2 fw-semibold" onclick="toggleActive(${s.id})"><i class="bi bi-power ${inactive ? 'text-success' : 'text-secondary'} me-2"></i>${inactive ? 'Aktifkan Toko' : 'Nonaktifkan Toko (sembunyikan peringatan)'}</button></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><button class="dropdown-item py-2 text-danger fw-bold" onclick="deleteStore(${s.id}, '${esc(s.name)}')"><i class="bi bi-trash3-fill me-2"></i>Hapus Toko</button></li>
                        </ul>
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="badge-status ${statusClass}">${statusLabel}</span>
                    ${inactive
                        ? `<button class="btn btn-sm btn-outline-success" style="font-size:.7rem; padding:.15rem .5rem;" onclick="toggleActive(${s.id})"><i class="bi bi-power"></i> Aktifkan Toko</button>`
                        : `<span style="display:flex; gap:.35rem; align-items:center;">
                             <a href="${connectUrl}" class="btn btn-sm btn-outline-primary" style="font-size:.7rem; padding:.15rem .5rem;"><i class="bi bi-plug"></i> Hubungkan Ulang</a>
                             <button class="btn btn-sm btn-outline-secondary" style="font-size:.7rem; padding:.15rem .5rem;" onclick="toggleActive(${s.id})" title="Sembunyikan dari peringatan koneksi"><i class="bi bi-power"></i> Nonaktifkan</button>
                           </span>`}
                </div>

                <div class="store-stats">
                    <a href="/marketplace/orders?store_id=${s.id}&tab=unpaid" class="stat-box" style="text-decoration:none; color:inherit; cursor:pointer;">
                        <div class="stat-val">${orders}</div>
                        <div class="stat-lbl">Hari Ini</div>
                    </a>
                    <a href="/marketplace/orders?store_id=${s.id}&tab=ready_to_ship" class="stat-box ${unfulfil > 0 ? 'warn' : ''}" style="text-decoration:none; color:inherit; cursor:pointer;">
                        <div class="stat-val">${unfulfil}</div>
                        <div class="stat-lbl">Perlu Kirim</div>
                    </a>
                    <a href="/marketplace/issues?store_id=${s.id}" class="stat-box ${issues > 0 ? 'err' : ''}" style="text-decoration:none; color:inherit; cursor:pointer;">
                        <div class="stat-val">${issues}</div>
                        <div class="stat-lbl">Isu Data</div>
                    </a>
                </div>

                <div class="store-actions">
                    ${issues > 0 ? `
                    <div class="action-row">
                        <a href="/marketplace/issues?store_id=${s.id}" class="btn-action-danger"><i class="bi bi-exclamation-triangle-fill"></i> Perbaiki Isu Data</a>
                    </div>
                    ` : ''}
                    <div class="action-row">
                        ${isConn 
                            ? `<button class="btn-action-primary" onclick="openSync(${s.id},'${esc(s.name)}')"><i class="bi bi-calendar-range"></i> Pilih Tanggal Sync</button>`
                            : `<button class="btn-action-secondary" disabled><i class="bi bi-cloud-download"></i> Terputus</button>`
                        }
                        <a href="/marketplace/orders?store_id=${s.id}" class="btn-action-secondary"><i class="bi bi-receipt"></i> Kelola</a>
                    </div>
                </div>

                <div class="store-footer">
                    <div class="sync-time" style="margin-bottom:2px;">
                        <i class="bi bi-arrow-repeat"></i>
                        <span>Sync API: ${s.last_synced_at ? fmtShortDate(s.last_synced_at) : 'Belum Pernah'}</span>
                    </div>
                    <div class="sync-time">
                        <i class="bi bi-lightning-charge"></i>
                        <span>Webhook: ${s.meta?.last_webhook_at ? fmtShortDate(s.meta.last_webhook_at) : 'Belum Pernah'}</span>
                    </div>
                </div>
            </div>`;
        }).join('');

        // Render Summary
        $('summaryStats').innerHTML = `
            <div class="summary-stat-item" title="Total Pesanan Masuk"><i class="bi bi-bag-check" style="font-size:1rem; color:#3b82f6"></i> <span>${totalOrders} Pesanan Hari Ini</span></div>
            ${totalUnfulfil > 0 ? `<div class="summary-stat-item text-warning" title="Total Belum Dikemas"><i class="bi bi-box-seam"></i> <span>${totalUnfulfil} Perlu Dikirim</span></div>` : ''}
            ${totalIssues > 0 ? `<div class="summary-stat-item text-danger" title="Total Masalah Data"><i class="bi bi-exclamation-circle-fill"></i> <span>${totalIssues} Isu Data</span></div>` : ''}
        `;
        summary.style.display = 'flex';
    }

    window.openSync = function (id, name) {
        syncStoreId = id;
        syncStoreName = name;
        $('syncModalTitle').textContent = 'Tarik Pesanan: ' + name;
        $('syncAlert').className = 'alert d-none';
        const today = new Date().toISOString().slice(0,10);
        const week  = new Date(Date.now() - 6*864e5).toISOString().slice(0,10);
        $('syncFrom').value = week; $('syncTo').value = today;
        $('syncBtn').disabled = false; $('syncBtn').textContent = 'Mulai Proses';
        new bootstrap.Modal($('syncModal')).show();
    };

    window.doSync = async function () {
        if (!syncStoreId) return;
        const from = new Date($('syncFrom').value + 'T00:00:00');
        const to   = new Date($('syncTo').value   + 'T23:59:59');
        const btn  = $('syncBtn'), alertEl = $('syncAlert');
        btn.disabled = true; btn.textContent = 'Memproses...';
        alertEl.className = 'alert alert-warning'; alertEl.textContent = 'Menghubungi Marketplace...';

        try {
            const d = await api('/api/marketplace/stores/' + syncStoreId + '/sync-orders', {
                method: 'POST',
                body: JSON.stringify({
                    time_from: Math.floor(from.getTime()/1000),
                    time_to:   Math.floor(to.getTime()/1000),
                    page_size: 50,
                    dry_run: $('tokoSyncDryRun').checked ? 1 : 0
                })
            });

            bootstrap.Modal.getInstance($('syncModal')).hide();
            showSyncResult(d, syncStoreName);
            loadAll();
        } catch (e) {
            alertEl.className = 'alert alert-danger'; alertEl.textContent = 'Error: ' + e.message;
            btn.disabled = false; btn.textContent = 'Coba Lagi';
        }
    };

    function showSyncResult(d, storeName) {
        $('syncResultSub').textContent = storeName + ' — ' + (d.message || 'Selesai');
        const rows = [
            { label: 'Pesanan Ditemukan',       val: d.found            ?? 0,  style: '' },
            { label: 'Pesanan Baru Disimpan',   val: d.new              ?? 0,  style: 'ok' },
            { label: 'Pesanan Diperbarui',      val: d.updated          ?? 0,  style: '' },
            { label: 'Isu: SKU Belum Terpetakan',val: d.mapping_not_found ?? 0, style: (d.mapping_not_found||0)>0 ? 'warn' : '' },
            { label: 'Isu: Kekurangan Data',    val: (d.sku_empty||0)+(d.missing_hpp||0)+(d.incomplete||0),  style: ((d.sku_empty||0)+(d.missing_hpp||0)+(d.incomplete||0))>0 ? 'warn' : '' },
        ];
        $('syncResultBody').innerHTML = rows.map(r =>
            `<div style="display:flex; justify-content:space-between; padding:.5rem 0; border-bottom:1px solid #e2e8f0; font-size:.85rem">
                <span style="color:#64748b">${r.label}</span>
                <span class="${r.style === 'ok' ? 'text-success fw-bold' : (r.style === 'warn' ? 'text-warning fw-bold' : 'fw-bold')}">${r.val}</span>
            </div>`
        ).join('');
        
        const hasIssues = (d.sku_empty||0) + (d.mapping_not_found||0) + (d.missing_hpp||0) > 0;
        const storeQs = syncStoreId ? `?store_id=${syncStoreId}` : '';

        $('syncResultFooter').innerHTML = `
            <div class="d-flex flex-wrap gap-2 w-100 mt-2">
                ${hasIssues ? `<a href="/marketplace/issues${storeQs}" class="btn btn-ship-primary btn-pill btn-sm" style="background:#f59e0b!important;border-color:#f59e0b!important;color:#fff!important" data-bs-dismiss="modal">Cek Masalah Data</a>` : ''}
                <button class="btn btn-ship-outline btn-pill btn-sm ms-auto" data-bs-dismiss="modal">Tutup</button>
            </div>`;
        new bootstrap.Modal($('syncResultModal')).show();
    }

    window.bootstrap_ = async function () {
        await api('/api/marketplace/bootstrap', { method: 'POST' }).catch(e => alert(e.message));
        loadAll();
    };



    window.checkStore = async function (id, name) {
        $('orderStatusGroup').style.display = 'none';
        $('infoModalTitle').textContent = 'Info API — ' + name;
        $('infoOutput').textContent = 'Memuat…';
        new bootstrap.Modal($('infoModal')).show();
        try {
            const d = await api('/api/marketplace/stores/' + id + '/shop-info');
            $('infoOutput').textContent = JSON.stringify(d, null, 2);
        } catch (e) { $('infoOutput').textContent = 'Error: ' + e.message; }
    };

    window.testApiType = null;
    window.testApiStoreId = null;

    // Webhook log function
    window.openWebhookLogs = async function() {
        new bootstrap.Modal($('webhookModal')).show();
        $('webhookLogList').innerHTML = '<div class="p-4 text-center text-muted">Memuat logs...</div>';
        try {
            const logs = await api('/api/webhooks/logs?provider=shopee');
            if (logs.length === 0) {
                $('webhookLogList').innerHTML = '<div class="p-4 text-center text-muted">Belum ada webhook yang masuk.</div>';
                return;
            }
            
            $('webhookLogList').innerHTML = logs.map(log => `
                <div class="list-group-item">
                    <div class="d-flex w-100 justify-content-between mb-1">
                        <strong class="mb-1">${log.event_type}</strong>
                        <small class="text-muted">${new Date(log.created_at).toLocaleString('id-ID')}</small>
                    </div>
                    <div class="mb-2">
                        <span class="badge ${log.signature_verified ? 'bg-success' : 'bg-warning text-dark'}">
                            ${log.signature_verified ? 'Signature Valid' : 'Signature Unverified / Invalid'}
                        </span>
                    </div>
                    <pre style="background:#f8fafc; padding:10px; border-radius:6px; font-size:11px; max-height:200px; overflow:auto;">${JSON.stringify(log.payload, null, 2)}</pre>
                </div>
            `).join('');
        } catch (e) {
            $('webhookLogList').innerHTML = '<div class="p-4 text-center text-danger">Error: ' + e.message + '</div>';
        }
    };

    window.simulateWebhook = async function(id, name, driver, platformId, eventType = 'order_status_update') {
        if (driver !== 'shopee') {
            alert('Saat ini simulasi webhook baru mendukung Shopee.');
            return;
        }
        
        let confirmMsg = 'Kirim simulasi Webhook "Order Status Update (READY_TO_SHIP)" ke toko ' + name + '?';
        if (eventType === 'auth_expiry_push') {
            confirmMsg = 'Kirim simulasi Webhook "Open API Authorization Expiry Push" (Code 12) ke toko ' + name + '?\n\nIni akan merubah status token toko menjadi kedaluwarsa.';
        }

        if (!confirm(confirmMsg)) return;
        
        try {
            await api('/api/webhooks/simulate', {
                method: 'POST',
                body: JSON.stringify({
                    provider: driver,
                    platform_id: platformId,
                    event_type: eventType
                })
            });
            alert('Simulasi berhasil dikirim! Silakan klik tombol "Log Webhook" untuk melihat hasilnya.');
            // Reload the stores after a slight delay to allow the background job to finish
            setTimeout(() => loadStores(), 1500);
            // window.openWebhookLogs();
        } catch (e) {
            alert('Error: ' + e.message);
        }
    };

    window.checkBookingList = function (id, name) {
        window.testApiStoreId = id;
        window.testApiType = 'booking';
        $('infoModalTitle').textContent = 'API Get Booking List — ' + name;
        $('orderStatusGroup').querySelector('.input-group-text').textContent = 'Status Booking:';
        $('orderStatusGroup').style.display = 'flex';
        $('orderStatusSelect').value = ''; // default semua
        new bootstrap.Modal($('infoModal')).show();
        
        $('btnFetchOrderList').click();
    };

    window.checkOrderList = function (id, name) {
        window.testApiStoreId = id;
        window.testApiType = 'order';
        $('infoModalTitle').textContent = 'API Get Order List — ' + name;
        $('orderStatusGroup').querySelector('.input-group-text').textContent = 'Status Pesanan:';
        $('orderStatusGroup').style.display = 'flex';
        $('orderStatusSelect').value = ''; // default semua
        new bootstrap.Modal($('infoModal')).show();
        
        // Auto-fetch default "Semua Status"
        $('btnFetchOrderList').click();
    };

    $('btnFetchOrderList').onclick = async function() {
        const id = window.testApiStoreId;
        const type = window.testApiType;
        const status = $('orderStatusSelect').value;
        
        let url = '';
        if (type === 'order') {
            url = '/api/marketplace/stores/' + id + '/order-list' + (status ? '?order_status=' + status : '');
            $('infoOutput').textContent = 'Memuat Order List (' + (status || 'Semua Status') + ')...';
        } else {
            url = '/api/marketplace/stores/' + id + '/booking-list' + (status ? '?booking_status=' + status : '');
            $('infoOutput').textContent = 'Memuat Booking List (' + (status || 'Semua Status') + ')...';
        }
        
        $('btnFetchOrderList').disabled = true;
        
        try {
            const d = await api(url);
            $('infoOutput').textContent = JSON.stringify(d, null, 2);
        } catch (e) { 
            $('infoOutput').textContent = 'Error: ' + e.message; 
        } finally {
            $('btnFetchOrderList').disabled = false;
        }
    };

    window.testOrderDetail = function(storeId, storeName) {
        window.testApiStoreId = storeId;
        window.testApiEndpointType = 'order';
        $('apiTestModalTitle').textContent = 'Tes API: get_order_detail — ' + storeName;
        $('apiTestInputLabel').textContent = 'Order SN';
        $('apiTestInput').value = '';
        $('apiTestOutput').textContent = 'Masukkan Order SN lalu klik Fetch JSON.';
        
        new bootstrap.Modal($('apiTestModal')).show();
    };

    window.testPackageDetail = function(storeId, storeName) {
        window.testApiStoreId = storeId;
        window.testApiEndpointType = 'package';
        $('apiTestModalTitle').textContent = 'Tes API: get_package_detail — ' + storeName;
        $('apiTestInputLabel').textContent = 'Package Number';
        $('apiTestInput').value = '';
        $('apiTestOutput').textContent = 'Masukkan Package Number lalu klik Fetch JSON.';
        
        new bootstrap.Modal($('apiTestModal')).show();
    };

    window.testReturnList = function(storeId, storeName) {
        window.testApiStoreId = storeId;
        window.testApiEndpointType = 'return_list';
        $('apiTestModalTitle').textContent = 'Tes API: get_return_list — ' + storeName;
        $('apiTestInputLabel').textContent = 'Page No';
        $('apiTestInput').value = '0';
        $('apiTestOutput').textContent = 'Masukkan Page No (misal 0) lalu klik Fetch JSON.';
        
        new bootstrap.Modal($('apiTestModal')).show();
    };

    $('btnTestApi').onclick = async function() {
        const storeId = window.testApiStoreId;
        const inputVal = $('apiTestInput').value.trim();
        const type = window.testApiEndpointType;
        
        if (!inputVal && type !== 'return_list') return alert('Silakan masukkan parameter.');
        if (type === 'return_list' && inputVal === '') $('apiTestInput').value = '0';
        
        let url = '';
        if (type === 'order') {
            url = '/api/marketplace/stores/' + storeId + '/orders/' + inputVal + '/raw-detail';
        } else if (type === 'package') {
            url = '/api/marketplace/stores/' + storeId + '/packages/' + inputVal + '/raw-detail';
        } else if (type === 'return_list') {
            url = '/api/marketplace/stores/' + storeId + '/return-list/raw-detail?page_no=' + (inputVal || 0);
        }

        $('apiTestOutput').textContent = 'Memanggil ' + url + ' ...\\nTunggu sebentar...';
        $('btnTestApi').disabled = true;
        
        try {
            const res = await api(url);
            $('apiTestOutput').textContent = JSON.stringify(res, null, 2);
        } catch (e) {
            $('apiTestOutput').textContent = 'Error: ' + e.message;
        } finally {
            $('btnTestApi').disabled = false;
        }
    };

    window.startRename = function (storeId) {
        document.getElementById('store-name-' + storeId).style.display = 'none';
        const row = document.getElementById('rename-inline-' + storeId);
        row.style.display = 'flex';
        document.getElementById('rename-input-' + storeId).focus();
    };

    window.cancelRename = function (storeId) {
        document.getElementById('rename-inline-' + storeId).style.display = 'none';
        document.getElementById('store-name-' + storeId).style.display = '';
    };

    window.saveRename = async function (storeId) {
        const input = document.getElementById('rename-input-' + storeId);
        const name = input.value.trim();
        if (!name) return;
        try {
            await api('/api/marketplace/stores/' + storeId, {
                method: 'PATCH',
                body: JSON.stringify({ name }),
            });
            document.getElementById('store-name-' + storeId).textContent = name;
            cancelRename(storeId);
        } catch (e) {
            alert('Gagal menyimpan nama: ' + e.message);
        }
    };

    window.disconnectStore = async function (storeId, name) {
        if (!confirm('Apakah Anda yakin ingin memutuskan koneksi API untuk toko "' + name + '"?')) return;
        try {
            await api('/stores/' + storeId + '/disconnect', {
                method: 'POST',
            });
            loadAll();
        } catch (e) {
            alert(e.message || 'Gagal memutuskan koneksi toko');
        }
    };

    window.toggleActive = async function (storeId) {
        try {
            const res = await api('/api/marketplace/stores/' + storeId + '/toggle-active', { method: 'POST' });
            loadAll();
        } catch (e) {
            alert(e.message || 'Gagal mengubah status aktif toko');
        }
    };

    window.deleteStore = async function (storeId, name) {
        if (!confirm('Apakah Anda yakin ingin menghapus toko "' + name + '"?')) return;
        try {
            await api('/api/marketplace/stores/' + storeId, {
                method: 'DELETE',
            });
            loadAll();
        } catch (e) {
            alert(e.message || 'Gagal menghapus toko');
        }
    };

    window.forceSyncStore = async function (storeId, storeName) {
        if (!confirm(`Tarik seluruh pesanan (15 hari) dan retur untuk toko ${storeName} sekarang? Proses akan berjalan di latar belakang.`)) return;

        try {
            const res = await api(`/stores/${storeId}/force-sync-background`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({})
            });
            alert(res.message || 'Sinkronisasi berhasil dijadwalkan.');
        } catch (e) {
            alert('Gagal menjadwalkan sinkronisasi: ' + e.message);
        }
    };

    window.triggerHistoricalBackfill = async function (storeId, storeName) {
        const year = prompt(`Tarik Histori (Mesin Waktu) untuk toko ${storeName}.\nMasukkan tahun target mundur (contoh: 2022):`, "2022");
        if (!year) return;
        
        if (!confirm(`Peringatan: Menarik seluruh histori order dan retur dari tahun ${year} akan berjalan lama di latar belakang.\nAnda tetap bisa menutup halaman ini. Lanjutkan?`)) return;

        try {
            const res = await fetch(`/api/marketplace/stores/${storeId}/sync-historical`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ year: year })
            });
            const data = await res.json();
            if (data.status === 'success') {
                alert("Berhasil! Proses tarik histori sedang berjalan di latar belakang server.");
            } else {
                alert("Gagal: " + (data.message || 'Unknown error'));
            }
        } catch (e) {
            alert("Gagal memanggil API: " + e.message);
        }
    };

    window.loadAll = loadAll;
    loadAll();
})();
</script>
@endpush
