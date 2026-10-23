@extends('layouts.app')
@section('title', 'Marketplace • Log Sync & Queue')

@include('marketplace._shared')

@push('head')
<style>
    .log-tabs {
        display: inline-flex;
        gap: .3rem;
        padding: .35rem;
        border-radius: 14px;
        background: rgba(148, 163, 184, .08);
        margin-bottom: 1rem;
        overflow-x: auto;
        max-width: 100%;
        scrollbar-width: none;
    }
    .log-tabs::-webkit-scrollbar { display: none; }
    body[data-theme="dark"] .log-tabs { background: rgba(30, 41, 59, .5); }
    .log-tab-btn {
        border: none;
        background: transparent;
        color: #64748b;
        border-radius: 10px;
        padding: .5rem .95rem;
        font-size: .82rem;
        font-weight: 800;
        cursor: pointer;
        white-space: nowrap;
        transition: all .15s ease;
        display: inline-flex;
        align-items: center;
        gap: .4rem;
    }
    .log-tab-btn:hover { color: var(--shp-text); }
    .log-tab-btn.active {
        background: var(--card, #fff);
        color: var(--shp-text);
        box-shadow: 0 1px 3px rgba(0,0,0,.05), 0 1px 2px rgba(0,0,0,.1);
    }
    body[data-theme="dark"] .log-tab-btn { color: #94a3b8; }
    body[data-theme="dark"] .log-tab-btn.active {
        background: var(--card, #1e293b);
        color: var(--text, #e2e8f0);
    }
    .pill-badge {
        display: inline-flex;
        align-items: center;
        gap: .3rem;
        padding: .2rem .55rem;
        border-radius: 999px;
        font-size: .7rem;
        font-weight: 750;
    }
    .pill-badge.success { background: rgba(34, 197, 94, .14); color: #166534; }
    .pill-badge.warn { background: rgba(245, 158, 11, .16); color: #b45309; }
    .pill-badge.error { background: rgba(239, 68, 68, .14); color: #b91c1c; }
    .pill-badge.info { background: rgba(59, 130, 246, .14); color: #1d4ed8; }
    .pill-badge.neutral { background: rgba(148, 163, 184, .14); color: #475569; }
    body[data-theme="dark"] .pill-badge.success { background: rgba(34, 197, 94, .25); color: #4ade80; }
    body[data-theme="dark"] .pill-badge.warn { background: rgba(245, 158, 11, .25); color: #fbbf24; }
    body[data-theme="dark"] .pill-badge.error { background: rgba(239, 68, 68, .25); color: #f87171; }
    body[data-theme="dark"] .pill-badge.info { background: rgba(59, 130, 246, .25); color: #60a5fa; }
    body[data-theme="dark"] .pill-badge.neutral { background: rgba(148, 163, 184, .25); color: #cbd5e1; }
    .console-terminal {
        background: #020617;
        color: #e2e8f0;
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        font-size: .75rem;
        line-height: 1.5;
        padding: 1rem;
        border-radius: 12px;
        height: 520px;
        overflow-y: auto;
        white-space: pre-wrap;
        word-break: break-all;
    }
    .console-line { padding: 1px 0; border-bottom: 1px solid rgba(255,255,255,.03); }
    .console-line.error { color: #f87171; font-weight: 700; }
    .console-line.warn { color: #fbbf24; }
    .console-line.info { color: #93c5fd; }
    .json-pre-box {
        background: #0f172a;
        color: #f1f5f9;
        font-family: ui-monospace, monospace;
        font-size: .75rem;
        padding: 1rem;
        border-radius: 10px;
        max-height: 480px;
        overflow: auto;
    }
</style>
@endpush

@section('content')
<div class="page-wrap">
    {{-- Header --}}
    <div class="ship-topbar">
        <div>
            <h1 class="title"><i class="bi bi-journal-text me-2" style="color:var(--shp-accent)"></i>Log Sync & Queue Marketplace</h1>
            <div class="sub">Monitoring riwayat sinkronisasi order/settlement, antrean background jobs, dan live scheduler stream.</div>
        </div>
        <div style="display:flex;gap:.5rem;align-items:center;flex-wrap:wrap">
            <div class="d-flex align-items-center gap-2">
                <span style="font-size:.72rem;color:var(--shp-muted);font-weight:700">Auto Refresh:</span>
                <select class="form-select form-select-sm" id="autoRefreshInterval" style="width:auto;font-size:.75rem;padding:.2rem 1.8rem .2rem .5rem;border-radius:8px" onchange="setupAutoRefresh()">
                    <option value="0">Mati</option>
                    <option value="5000">5 detik</option>
                    <option value="10000" selected>10 detik</option>
                    <option value="30000">30 detik</option>
                </select>
            </div>
            <button class="btn btn-sm btn-dark btn-pill" onclick="refreshCurrentTab()">
                <i class="bi bi-arrow-clockwise"></i> Refresh
            </button>
        </div>
    </div>

    {{-- 4 Summary Cards --}}
    <div class="row g-2 mb-3">
        <div class="col-6 col-md-3" style="cursor:pointer" onclick="switchTab('sync')" title="Klik untuk lihat riwayat sync">
            <div class="card-main p-3">
                <div class="d-flex justify-content-between align-items-center">
                    <span style="font-size:.7rem;font-weight:800;color:var(--shp-muted);text-transform:uppercase">Sync 24 Jam</span>
                    <i class="bi bi-arrow-repeat text-primary" style="font-size:1.1rem"></i>
                </div>
                <div class="fs-4 fw-bold mt-1" id="kpiTotalSync">—</div>
                <div class="text-muted small" style="font-size:.68rem">Semua modul marketplace</div>
            </div>
        </div>
        <div class="col-6 col-md-3" style="cursor:pointer" onclick="switchTab('sync')" title="Klik untuk lihat riwayat sync">
            <div class="card-main p-3">
                <div class="d-flex justify-content-between align-items-center">
                    <span style="font-size:.7rem;font-weight:800;color:var(--shp-muted);text-transform:uppercase">Sync Sukses</span>
                    <i class="bi bi-check-circle text-success" style="font-size:1.1rem"></i>
                </div>
                <div class="fs-4 fw-bold mt-1 text-success" id="kpiSuccessSync">—</div>
                <div class="text-muted small" style="font-size:.68rem" id="kpiSuccessNote"><span id="kpiFailedSync">0</span> sync log error</div>
            </div>
        </div>
        <div class="col-6 col-md-3" style="cursor:pointer" onclick="switchTab('queue')" title="Klik untuk lihat antrean aktif">
            <div class="card-main p-3">
                <div class="d-flex justify-content-between align-items-center">
                    <span style="font-size:.7rem;font-weight:800;color:var(--shp-muted);text-transform:uppercase">Antrean Queue</span>
                    <i class="bi bi-stack text-info" style="font-size:1.1rem"></i>
                </div>
                <div class="fs-4 fw-bold mt-1 text-info" id="kpiPendingJobs">—</div>
                <div class="text-muted small" style="font-size:.68rem" id="kpiPendingJobsNote">Jobs menunggu dieksekusi</div>
            </div>
        </div>
        <div class="col-6 col-md-3" style="cursor:pointer" onclick="switchTab('failed')" title="Klik untuk lihat pekerjaan gagal">
            <div class="card-main p-3">
                <div class="d-flex justify-content-between align-items-center">
                    <span style="font-size:.7rem;font-weight:800;color:var(--shp-muted);text-transform:uppercase">Pekerjaan Gagal</span>
                    <i class="bi bi-exclamation-octagon-fill text-danger" style="font-size:1.1rem"></i>
                </div>
                <div class="fs-4 fw-bold mt-1 text-danger" id="kpiFailedJobs">—</div>
                <div class="text-muted small" style="font-size:.68rem" id="kpiFailedJobsNote">0 jobs gagal</div>
            </div>
        </div>
    </div>

    {{-- Navigation Tabs --}}
    <div class="log-tabs" role="tablist">
        <button type="button" class="log-tab-btn active" id="tabBtnSync" onclick="switchTab('sync')">
            <i class="bi bi-receipt-cutoff"></i> Riwayat Sync (<span id="tabCountSync">0</span>)
        </button>
        <button type="button" class="log-tab-btn" id="tabBtnQueue" onclick="switchTab('queue')">
            <i class="bi bi-hourglass-split"></i> Antrean Queue (<span id="tabCountQueue">0</span>)
        </button>
        <button type="button" class="log-tab-btn" id="tabBtnFailed" onclick="switchTab('failed')">
            <i class="bi bi-x-octagon"></i> Pekerjaan Gagal (<span id="tabCountFailed">0</span>)
        </button>
        <button type="button" class="log-tab-btn" id="tabBtnStream" onclick="switchTab('stream')">
            <i class="bi bi-terminal"></i> Live Log Stream
        </button>
    </div>

    {{-- TAB 1: SYNC LOGS --}}
    <div id="panelSync">
        <div class="card-main mb-3 p-3">
            <div class="row g-2 align-items-end">
                <div class="col-12 col-sm-6 col-md-3">
                    <label class="form-label mb-1" style="font-size:.68rem;font-weight:800;color:var(--shp-muted);text-transform:uppercase">Toko</label>
                    <select class="form-select form-select-sm" id="filterStore" onchange="loadSyncLogs(1)">
                        <option value="">Semua Toko</option>
                        @foreach($stores as $st)
                            <option value="{{ $st->id }}">{{ $st->name }} ({{ $st->channel?->name ?? 'MP' }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-sm-6 col-md-2">
                    <label class="form-label mb-1" style="font-size:.68rem;font-weight:800;color:var(--shp-muted);text-transform:uppercase">Modul / Aksi</label>
                    <select class="form-select form-select-sm" id="filterAction" onchange="loadSyncLogs(1)">
                        <option value="">Semua Modul</option>
                        @foreach($modules as $k => $v)
                            <option value="{{ $k }}">{{ $v }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-sm-6 col-md-2">
                    <label class="form-label mb-1" style="font-size:.68rem;font-weight:800;color:var(--shp-muted);text-transform:uppercase">Status</label>
                    <select class="form-select form-select-sm" id="filterStatus" onchange="loadSyncLogs(1)">
                        <option value="">Semua Status</option>
                        <option value="success">Sukses (success)</option>
                        <option value="partial_success">Parsial (partial_success)</option>
                        <option value="failed">Gagal (failed)</option>
                    </select>
                </div>
                <div class="col-12 col-sm-6 col-md-3">
                    <label class="form-label mb-1" style="font-size:.68rem;font-weight:800;color:var(--shp-muted);text-transform:uppercase">Cari Pesan / Order</label>
                    <input type="text" class="form-control form-control-sm" id="filterSearch" placeholder="Cari keyword..." onkeyup="if(event.key==='Enter')loadSyncLogs(1)">
                </div>
                <div class="col-12 col-md-2 d-flex gap-1">
                    <button class="btn btn-sm btn-dark w-100 btn-pill" onclick="loadSyncLogs(1)">Filter</button>
                    <button class="btn btn-sm btn-outline-secondary btn-pill" onclick="resetSyncFilter()">Reset</button>
                </div>
            </div>
        </div>

        <div class="card-main">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" style="font-size:.8rem">
                    <thead style="background:rgba(148,163,184,.06);font-size:.72rem;text-transform:uppercase;color:var(--shp-muted)">
                        <tr>
                            <th width="14%">Waktu</th>
                            <th width="16%">Toko & Channel</th>
                            <th width="15%">Aksi / Modul</th>
                            <th width="10%">Status</th>
                            <th width="35%">Pesan & Ringkasan</th>
                            <th width="10%" class="text-end">Payload</th>
                        </tr>
                    </thead>
                    <tbody id="syncLogsTableBody">
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">
                                <span class="spinner-border spinner-border-sm me-2"></span> Memuat data riwayat sync…
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="p-3 border-top d-flex justify-content-between align-items-center flex-wrap gap-2" id="syncPaginationWrap" style="font-size:.78rem">
                <div id="syncPaginationInfo" class="text-muted">Menampilkan 0 data</div>
                <div id="syncPaginationBtns" class="btn-group btn-group-sm"></div>
            </div>
        </div>
    </div>

    {{-- TAB 2: QUEUE JOBS --}}
    <div id="panelQueue" style="display:none">
        <div class="row g-2 mb-3" id="queueBreakdownRow"></div>
        <div class="card-main">
            <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
                <div class="fw-bold" style="font-size:.85rem"><i class="bi bi-stack text-primary me-2"></i>Daftar Antrean Menunggu (`jobs`)</div>
                <span class="badge bg-secondary" id="activeJobsCountBadge">0 job</span>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" style="font-size:.8rem">
                    <thead style="background:rgba(148,163,184,.06);font-size:.72rem;text-transform:uppercase;color:var(--shp-muted)">
                        <tr>
                            <th width="16%">Waktu Masuk</th>
                            <th width="8%">ID</th>
                            <th width="12%">Queue</th>
                            <th width="32%">Job / Command Name</th>
                            <th width="12%">Percobaan</th>
                            <th width="20%">Status Worker</th>
                        </tr>
                    </thead>
                    <tbody id="queueJobsTableBody">
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">Tidak ada antrean pending. Antrean kosong!</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- TAB 3: FAILED JOBS --}}
    <div id="panelFailed" style="display:none">
        <div class="card-main">
            <div class="p-3 border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <div class="fw-bold" style="font-size:.85rem"><i class="bi bi-exclamation-octagon text-danger me-2"></i>Daftar Pekerjaan Gagal (`failed_jobs`)</div>
                    <div class="text-muted small" style="font-size:.7rem">Job yang mengalami unhandled exception atau melebihi batas percobaan.</div>
                </div>
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <span class="badge bg-danger rounded-pill px-2.5 py-1" id="failedJobsCountBadge">0 gagal</span>
                    @if(auth()->user()?->isOwner() || auth()->user()?->isDeveloper())
                        <button type="button" class="btn btn-xs btn-outline-primary btn-pill" onclick="retryAllFailedJobs()" title="Coba ulang semua job gagal">
                            <i class="bi bi-arrow-repeat me-1"></i> Coba Ulang Semua
                        </button>
                        <button type="button" class="btn btn-xs btn-outline-danger btn-pill" onclick="flushFailedJobs()" title="Hapus semua failed jobs">
                            <i class="bi bi-trash me-1"></i> Hapus Semua
                        </button>
                    @endif
                    <button type="button" class="btn btn-xs btn-dark btn-pill" onclick="loadQueueStatus()" title="Muat ulang antrean">
                        <i class="bi bi-arrow-clockwise"></i>
                    </button>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" style="font-size:.8rem">
                    <thead style="background:rgba(148,163,184,.06);font-size:.72rem;text-transform:uppercase;color:var(--shp-muted)">
                        <tr>
                            <th width="16%">Waktu Gagal</th>
                            <th width="12%">Queue</th>
                            <th width="26%">Job Name</th>
                            <th width="36%">Penyebab Error</th>
                            <th width="10%" class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="failedJobsTableBody">
                        <tr>
                            <td colspan="5" class="text-center py-4 text-muted">Bagus! Tidak ada antrean gagal.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- TAB 4: LIVE STREAM --}}
    <div id="panelStream" style="display:none">
        <div class="card-main p-3 mb-2">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div class="d-flex align-items-center gap-2 flex-grow-1" style="max-width:400px">
                    <i class="bi bi-search text-muted"></i>
                    <input type="text" class="form-control form-control-sm" id="streamFilterInput" placeholder="Filter log keyword (mis. sync, shopee, error)..." onkeyup="renderStreamLines()">
                </div>
                <div class="d-flex align-items-center gap-3">
                    <div class="form-check form-switch mb-0">
                        <input class="form-check-input" type="checkbox" id="streamAutoscroll" checked>
                        <label class="form-check-label small" for="streamAutoscroll" style="font-size:.75rem">Auto Scroll</label>
                    </div>
                    <button class="btn btn-sm btn-outline-secondary btn-pill" onclick="loadStreamLogs()">
                        <i class="bi bi-arrow-repeat"></i> Muat Ulang Log
                    </button>
                </div>
            </div>
        </div>
        <div class="console-terminal" id="consoleTerminalWrap">
            <div class="text-muted">Memuat baris log sistem…</div>
        </div>
    </div>
</div>

{{-- MODAL: JSON PAYLOAD DETAIL --}}
<div class="modal fade" id="payloadDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content" style="border-radius:16px;border:none">
            <div class="modal-header border-bottom py-2 px-3">
                <h6 class="modal-title fw-bold" id="payloadModalTitle"><i class="bi bi-code-slash me-2 text-primary"></i>Detail Payload Sync</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-muted small" id="payloadModalMeta">Payload data mentah:</span>
                    <button class="btn btn-xs btn-outline-secondary btn-pill" onclick="copyModalJson()">
                        <i class="bi bi-clipboard"></i> Salin JSON
                    </button>
                </div>
                <pre class="json-pre-box mb-0" id="payloadModalJson"></pre>
            </div>
            <div class="modal-footer border-top py-2 px-3">
                <button type="button" class="btn btn-sm btn-secondary btn-pill" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

{{-- MODAL: EXCEPTION DETAIL --}}
<div class="modal fade" id="exceptionDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content" style="border-radius:16px;border:none">
            <div class="modal-header border-bottom py-2 px-3 bg-danger text-white">
                <h6 class="modal-title fw-bold" id="exceptionModalTitle"><i class="bi bi-bug me-2"></i>Exception Stack Trace</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3">
                <pre class="json-pre-box text-danger-emphasis mb-0" style="background:#090d16;color:#fca5a5" id="exceptionModalBody"></pre>
            </div>
            <div class="modal-footer border-top py-2 px-3">
                <button type="button" class="btn btn-sm btn-secondary btn-pill" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
(function () {
    let currentTab = 'sync';
    let autoRefreshTimer = null;
    let rawStreamLines = [];
    let currentPayloadCache = null;
    let failedJobsCache = {};
    let syncLogsCache = {};
    const isOwnerOrDev = {{ auth()->user()?->isOwner() || auth()->user()?->isDeveloper() ? 'true' : 'false' }};

    function esc(s) {
        if (!s) return '';
        return String(s).replace(/[&<>"']/g, m => ({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' }[m]));
    }

    window.switchTab = function (tab) {
        currentTab = tab;
        ['sync', 'queue', 'failed', 'stream'].forEach(t => {
            const btn = document.getElementById('tabBtn' + t.charAt(0).toUpperCase() + t.slice(1));
            const panel = document.getElementById('panel' + t.charAt(0).toUpperCase() + t.slice(1));
            if (btn) btn.classList.toggle('active', t === tab);
            if (panel) panel.style.display = (t === tab ? 'block' : 'none');
        });

        if (tab === 'sync') loadSyncLogs(1);
        if (tab === 'queue' || tab === 'failed') loadQueueStatus();
        if (tab === 'stream') loadStreamLogs();
    };

    window.refreshCurrentTab = function () {
        loadSyncLogs(1);
        loadQueueStatus();
        if (currentTab === 'stream') loadStreamLogs();
    };

    window.setupAutoRefresh = function () {
        if (autoRefreshTimer) clearInterval(autoRefreshTimer);
        const ms = parseInt(document.getElementById('autoRefreshInterval').value, 10);
        if (ms > 0) {
            autoRefreshTimer = setInterval(() => {
                if (currentTab === 'sync') loadSyncLogs(undefined, true);
                if (currentTab === 'queue' || currentTab === 'failed') loadQueueStatus(true);
                if (currentTab === 'stream') loadStreamLogs(true);
            }, ms);
        }
    };

    // ── SYNC LOGS ────────────────────────────────────────────────────────────
    window.loadSyncLogs = async function (page = 1, isSilent = false) {
        const storeId = document.getElementById('filterStore').value;
        const action = document.getElementById('filterAction').value;
        const status = document.getElementById('filterStatus').value;
        const search = document.getElementById('filterSearch').value;

        const params = new URLSearchParams({ page });
        if (storeId) params.append('store_id', storeId);
        if (action) params.append('action', action);
        if (status) params.append('status', status);
        if (search) params.append('search', search);

        if (!isSilent) {
            document.getElementById('syncLogsTableBody').innerHTML = `
                <tr><td colspan="6" class="text-center py-4 text-muted"><span class="spinner-border spinner-border-sm me-2"></span>Memuat...</td></tr>
            `;
        }

        try {
            const res = await fetch('/api/marketplace/sync-queue-logs/syncs?' + params.toString());
            const json = await res.json();
            if (!json.success) return;

            // Update KPIs
            document.getElementById('kpiTotalSync').innerText = json.stats_24h.total.toLocaleString();
            document.getElementById('kpiSuccessSync').innerText = json.stats_24h.success.toLocaleString();
            const failedSyncEl = document.getElementById('kpiFailedSync');
            if (failedSyncEl) failedSyncEl.innerText = json.stats_24h.failed.toLocaleString();
            document.getElementById('tabCountSync').innerText = json.pagination.total.toLocaleString();

            renderSyncTable(json.data);
            renderSyncPagination(json.pagination);
        } catch (e) {
            document.getElementById('syncLogsTableBody').innerHTML = `
                <tr><td colspan="6" class="text-center py-4 text-danger">Gagal memuat log sync: ${esc(e.message)}</td></tr>
            `;
        }
    };

    function renderSyncTable(items) {
        const tbody = document.getElementById('syncLogsTableBody');
        if (!items || items.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" class="text-center py-4 text-muted">Tidak ada riwayat sync ditemukan.</td></tr>';
            return;
        }

        syncLogsCache = {};
        items.forEach(item => {
            syncLogsCache[item.id] = item;
        });

        tbody.innerHTML = items.map(item => {
            let badgeClass = 'neutral';
            if (item.status === 'success') badgeClass = 'success';
            else if (item.status === 'partial_success') badgeClass = 'warn';
            else if (item.status === 'failed') badgeClass = 'error';

            return `
                <tr>
                    <td>
                        <div class="fw-bold" style="font-size:.78rem">${esc(item.created_at)}</div>
                        <div class="text-muted" style="font-size:.68rem">${esc(item.time_ago)}</div>
                    </td>
                    <td>
                        <div class="fw-bold">${esc(item.store_name)}</div>
                        <span class="badge bg-light text-dark border" style="font-size:.65rem">${esc(item.channel)}</span>
                    </td>
                    <td>
                        <code style="font-size:.72rem">${esc(item.action)}</code>
                    </td>
                    <td>
                        <span class="pill-badge ${badgeClass}">${esc(item.status)}</span>
                    </td>
                    <td>
                        <div style="max-width:450px;white-space:normal;word-break:break-word">${esc(item.message)}</div>
                    </td>
                    <td class="text-end">
                        <button type="button" class="btn btn-xs btn-outline-primary btn-pill" onclick="viewPayloadModal(${item.id})">
                            <i class="bi bi-eye"></i> JSON
                        </button>
                    </td>
                </tr>
            `;
        }).join('');
    }

    function renderSyncPagination(p) {
        document.getElementById('syncPaginationInfo').innerText = `Menampilkan halaman ${p.current_page} dari ${p.last_page} (${p.total} total riwayat)`;
        const btns = document.getElementById('syncPaginationBtns');
        btns.innerHTML = '';

        if (p.last_page <= 1) return;

        if (p.current_page > 1) {
            btns.innerHTML += `<button class="btn btn-outline-secondary" onclick="loadSyncLogs(${p.current_page - 1})">Prev</button>`;
        }
        btns.innerHTML += `<button class="btn btn-dark active">${p.current_page}</button>`;
        if (p.current_page < p.last_page) {
            btns.innerHTML += `<button class="btn btn-outline-secondary" onclick="loadSyncLogs(${p.current_page + 1})">Next</button>`;
        }
    }

    window.resetSyncFilter = function () {
        document.getElementById('filterStore').value = '';
        document.getElementById('filterAction').value = '';
        document.getElementById('filterStatus').value = '';
        document.getElementById('filterSearch').value = '';
        loadSyncLogs(1);
    };

    // ── QUEUE & FAILED JOBS ──────────────────────────────────────────────────
    window.loadQueueStatus = async function (isSilent = false) {
        try {
            const res = await fetch('/api/marketplace/sync-queue-logs/queues');
            const json = await res.json();
            if (!json.success) return;

            document.getElementById('kpiPendingJobs').innerText = json.pending_count.toLocaleString();
            const pendingNoteEl = document.getElementById('kpiPendingJobsNote');
            if (pendingNoteEl) pendingNoteEl.innerText = `${json.pending_count} jobs di antrean`;

            const failedKpiEl = document.getElementById('kpiFailedJobs');
            if (failedKpiEl) failedKpiEl.innerText = json.failed_count.toLocaleString();

            const failedNoteEl = document.getElementById('kpiFailedJobsNote');
            if (failedNoteEl) {
                failedNoteEl.innerText = json.failed_count > 0 
                    ? `${json.failed_count} jobs perlu ditangani` 
                    : 'Semua antrean lancar';
            }

            document.getElementById('tabCountQueue').innerText = json.pending_count;
            document.getElementById('tabCountFailed').innerText = json.failed_count;
            document.getElementById('activeJobsCountBadge').innerText = `${json.pending_count} pending`;
            document.getElementById('failedJobsCountBadge').innerText = `${json.failed_count} gagal`;

            // Render Queue Breakdown Cards
            const bkRow = document.getElementById('queueBreakdownRow');
            bkRow.innerHTML = Object.entries(json.queue_breakdown || {}).map(([q, count]) => `
                <div class="col-6 col-md-3">
                    <div class="card-main p-2 px-3 d-flex justify-content-between align-items-center">
                        <span class="badge bg-light text-dark border">queue: ${esc(q)}</span>
                        <strong class="fs-5">${count}</strong>
                    </div>
                </div>
            `).join('');

            // Render Active Jobs Table
            const qTbody = document.getElementById('queueJobsTableBody');
            if (!json.active_jobs || json.active_jobs.length === 0) {
                qTbody.innerHTML = '<tr><td colspan="6" class="text-center py-4 text-muted"><i class="bi bi-check2-circle text-success me-1"></i> Antrean kosong. Semua pekerjaan selesai diproses worker!</td></tr>';
            } else {
                qTbody.innerHTML = json.active_jobs.map(j => `
                    <tr>
                        <td>
                            <div class="fw-bold" style="font-size:.78rem">${esc(j.created_at)}</div>
                            <div class="text-muted" style="font-size:.68rem">${esc(j.age)}</div>
                        </td>
                        <td><code>#${j.id}</code></td>
                        <td><span class="badge bg-light text-dark border">${esc(j.queue)}</span></td>
                        <td>
                            <strong style="font-size:.82rem">${esc(j.job_name)}</strong>
                            <div class="text-muted small" style="font-size:.68rem">${esc(j.full_name)}</div>
                        </td>
                        <td><span class="pill-badge neutral">${j.attempts}x coba</span></td>
                        <td>
                            ${j.is_reserved 
                                ? `<span class="pill-badge warn"><span class="spinner-border spinner-border-sm me-1" style="width:.65rem;height:.65rem"></span> Sedang diproses (${j.reserved_at})</span>` 
                                : '<span class="pill-badge info">Menunggu antrean</span>'}
                        </td>
                    </tr>
                `).join('');
            }

            // Cache & Render Failed Jobs Table
            failedJobsCache = {};
            (json.failed_jobs || []).forEach(f => {
                failedJobsCache[f.uuid] = f;
            });

            const fTbody = document.getElementById('failedJobsTableBody');
            if (!json.failed_jobs || json.failed_jobs.length === 0) {
                fTbody.innerHTML = '<tr><td colspan="5" class="text-center py-4 text-muted"><i class="bi bi-shield-check text-success me-1"></i> Tidak ada antrean gagal. Semuanya berjalan mulus!</td></tr>';
            } else {
                fTbody.innerHTML = json.failed_jobs.map(f => {
                    return `
                        <tr>
                            <td>
                                <div class="fw-bold" style="font-size:.78rem">${esc(f.failed_at)}</div>
                                <div class="text-muted" style="font-size:.68rem">${esc(f.time_ago)}</div>
                            </td>
                            <td><span class="badge bg-danger-subtle text-danger border border-danger-subtle">${esc(f.queue)}</span></td>
                            <td>
                                <strong>${esc(f.job_name)}</strong>
                                <div class="text-muted" style="font-size:.65rem;font-family:monospace">${esc(f.uuid)}</div>
                            </td>
                            <td>
                                <div class="text-danger fw-bold" style="font-size:.75rem">${esc(f.error_msg)}</div>
                            </td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <button type="button" class="btn btn-outline-secondary btn-xs" onclick="viewExceptionModal('${esc(f.uuid)}')" title="Detail Exception">
                                        <i class="bi bi-file-earmark-text"></i>
                                    </button>
                                    ${isOwnerOrDev ? `
                                        <button type="button" class="btn btn-outline-primary btn-xs" onclick="retryFailedJob('${esc(f.uuid)}')" title="Coba Ulang Job Ini">
                                            <i class="bi bi-arrow-repeat"></i>
                                        </button>
                                        <button type="button" class="btn btn-outline-danger btn-xs" onclick="deleteFailedJob('${esc(f.uuid)}')" title="Hapus Dari Failed">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    ` : ''}
                                </div>
                            </td>
                        </tr>
                    `;
                }).join('');
            }
        } catch (e) {
            console.error('loadQueueStatus error:', e);
        }
    };

    window.retryFailedJob = async function (uuid) {
        if (!confirm('Kirim ulang job ini ke antrean?')) return;
        try {
            const res = await fetch(`/api/marketplace/sync-queue-logs/queues/${uuid}/retry`, { method: 'POST' });
            const json = await res.json();
            alert(json.message);
            loadQueueStatus();
        } catch (e) {
            alert('Gagal: ' + e.message);
        }
    };

    window.deleteFailedJob = async function (uuid) {
        if (!confirm('Hapus job ini dari daftar failed jobs?')) return;
        try {
            const res = await fetch(`/api/marketplace/sync-queue-logs/queues/${uuid}`, { method: 'DELETE' });
            const json = await res.json();
            alert(json.message);
            loadQueueStatus();
        } catch (e) {
            alert('Gagal: ' + e.message);
        }
    };

    window.retryAllFailedJobs = async function () {
        if (!confirm('Apakah Anda yakin ingin mencoba ulang (retry) SEMUA pekerjaan yang gagal?')) return;
        try {
            const res = await fetch('/api/marketplace/sync-queue-logs/queues/retry-all', { method: 'POST' });
            const json = await res.json();
            alert(json.message);
            loadQueueStatus();
        } catch (e) {
            alert('Gagal: ' + e.message);
        }
    };

    window.flushFailedJobs = async function () {
        if (!confirm('PERINGATAN: Apakah Anda yakin ingin MENGHAPUS SEMUA riwayat pekerjaan gagal? Tindakan ini tidak dapat dibatalkan.')) return;
        try {
            const res = await fetch('/api/marketplace/sync-queue-logs/queues/flush-all', { method: 'DELETE' });
            const json = await res.json();
            alert(json.message);
            loadQueueStatus();
        } catch (e) {
            alert('Gagal: ' + e.message);
        }
    };

    // ── LIVE STREAM LOGS ─────────────────────────────────────────────────────
    window.loadStreamLogs = async function (isSilent = false) {
        try {
            const res = await fetch('/api/marketplace/sync-queue-logs/streams');
            const json = await res.json();
            if (!json.success) return;
            rawStreamLines = json.lines || [];
            renderStreamLines();
        } catch (e) {
            console.error(e);
        }
    };

    window.renderStreamLines = function () {
        const wrap = document.getElementById('consoleTerminalWrap');
        const filter = document.getElementById('streamFilterInput').value.toLowerCase().trim();

        const filtered = filter ? rawStreamLines.filter(l => l.toLowerCase().includes(filter)) : rawStreamLines;

        if (filtered.length === 0) {
            wrap.innerHTML = '<div class="text-muted">Tidak ada log yang cocok dengan filter.</div>';
            return;
        }

        wrap.innerHTML = filtered.map(line => {
            let cls = '';
            const lower = line.toLowerCase();
            if (lower.includes('.error') || lower.includes('failed') || lower.includes('exception')) cls = 'error';
            else if (lower.includes('.warning') || lower.includes('warn') || lower.includes('rate limit')) cls = 'warn';
            else if (lower.includes('.info') || lower.includes('success')) cls = 'info';

            return `<div class="console-line ${cls}">${esc(line)}</div>`;
        }).join('');

        if (document.getElementById('streamAutoscroll').checked) {
            wrap.scrollTop = wrap.scrollHeight;
        }
    };

    // ── MODALS ───────────────────────────────────────────────────────────────
    window.viewPayloadModal = function (id) {
        const item = syncLogsCache[id];
        if (!item) return;
        currentPayloadCache = item.payload;
        document.getElementById('payloadModalTitle').innerHTML = `<i class="bi bi-code-slash me-2 text-primary"></i>Payload: ${esc(item.action)}`;
        document.getElementById('payloadModalMeta').innerText = `Dicatat pada: ${esc(item.created_at)} (${esc(item.store_name)})`;
        document.getElementById('payloadModalJson').textContent = JSON.stringify(item.payload || {}, null, 2);

        new bootstrap.Modal(document.getElementById('payloadDetailModal')).show();
    };

    window.copyModalJson = function () {
        if (!currentPayloadCache) return;
        navigator.clipboard.writeText(JSON.stringify(currentPayloadCache, null, 2)).then(() => {
            alert('JSON berhasil disalin ke clipboard!');
        });
    };

    window.viewExceptionModal = function (uuid) {
        const f = failedJobsCache[uuid];
        if (!f) return;
        document.getElementById('exceptionModalTitle').innerHTML = `<i class="bi bi-bug me-2"></i>Exception: ${esc(f.job_name)}`;
        document.getElementById('exceptionModalBody').textContent = f.exception;
        new bootstrap.Modal(document.getElementById('exceptionDetailModal')).show();
    };

    // ── INITIALIZE ───────────────────────────────────────────────────────────
    loadSyncLogs(1);
    loadQueueStatus();
    setupAutoRefresh();
})();
</script>
@endpush
