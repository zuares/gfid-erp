@extends('layouts.app')
@section('title', 'Marketplace • Profit Order')

@include('marketplace._shared')

@section('content')
<div class="page-wrap">
    
    <div class="ship-topbar">
        <div>
            <h1 class="title">Profit per Order</h1>
            <div class="sub">Harga jual dikurangi COGS, fee marketplace, voucher, dan promosi — profit bersih per order.</div>
            <div class="sub" id="lastSyncLabel" style="margin-top:4px; font-size:0.75rem; color:#64748b; font-weight: 500;">
                Terakhir Sync: <span id="lastSyncTime">—</span>
                <span id="financeSyncChip" style="display:none; margin-left:8px; padding:2px 10px; border-radius:999px; font-weight:700; font-size:.68rem; cursor:pointer;" title="Klik untuk lihat log" onclick="toggleFinanceLog()"></span>
                <a href="javascript:void(0)" id="financeLogToggle" onclick="toggleFinanceLog()" style="display:none; margin-left:6px; font-size:.68rem; font-weight:600;">lihat log</a>
            </div>
        </div>
        <div class="controls">
            <button class="btn btn-sm btn-dark btn-pill" onclick="openSyncModal()" style="border-radius:999px;font-weight:600"><i class="bi bi-arrow-repeat"></i> Sync Data</button>
            <button class="btn btn-sm btn-ship-outline btn-pill" onclick="exportCsv()"><i class="bi bi-download"></i> Export</button>
            <button class="btn btn-sm btn-ship-outline btn-pill" onclick="loadProfits()" title="Muat ulang data"><i class="bi bi-arrow-clockwise"></i> Refresh</button>
        </div>
    </div>

    <!-- ── Navigasi Profit — urut berdasarkan alur kerja user ── -->
    <div style="margin-bottom:1rem;">
        <div class="profit-tabs-modern">
            <button type="button" id="ptabSummary" class="profit-tab-m active" onclick="switchProfitTab('summary')"><i class="bi bi-bar-chart-line"></i> Ringkasan</button>
            <button type="button" id="ptabUnmapped" class="profit-tab-m" onclick="switchProfitTab('unmapped')"><i class="bi bi-exclamation-triangle"></i> Perlu Mapping</button>
            <button type="button" id="ptabUnsettled" class="profit-tab-m" onclick="switchProfitTab('unsettled')"><i class="bi bi-hourglass-split"></i> Belum Cair</button>
            <button type="button" id="ptabSync" class="profit-tab-m" onclick="switchProfitTab('sync')"><i class="bi bi-arrow-repeat"></i> Sinkronisasi <span id="syncTabDot" style="display:none; width:8px; height:8px; border-radius:50%; background:#3b82f6;"></span></button>
        </div>
    </div>

<div id="profitTabData">

    <div id="profitHppWarning" class="alert alert-warning d-none mb-3" style="border-radius:8px;font-size:.85rem">
        ⚠️ Beberapa order tidak memiliki mapping SKU → Item, sehingga COGS-nya <strong>0</strong>. Lengkapi <a href="{{ route('marketplace.sku-mapping') }}">SKU Mapping</a> dan pastikan sudah ada <em>COGS Snapshot</em> aktif.
    </div>

    <style>
        .profit-filters {
            display: flex;
            flex-wrap: wrap;
            gap: 0.65rem;
            align-items: center;
        }
        .profit-filters .filter-item {
            flex: 1 1 140px;
            position: relative;
            min-width: 140px;
        }
        .profit-filters .filter-item.search-item {
            flex: 2 1 210px;
        }
        /* Label kecil uppercase di atas tiap field — sama dengan .dash-filter Ads Dashboard */
        .profit-filters label {
            font-size: .68rem;
            font-weight: 600;
            color: #64748b;
            margin-bottom: .3rem;
            display: block;
            text-transform: uppercase;
            letter-spacing: .05em;
        }
        .profit-filters select.form-select,
        .profit-filters input.form-control {
            width: 100%;
            font-size: 0.82rem;
            height: 34px;
            padding: .35rem .65rem;
            font-weight: 500;
            border: 1px solid rgba(148, 163, 184, 0.3);
            border-radius: 8px;
            background: var(--bg, #f8fafc);
            box-shadow: none;
        }
        .profit-filters select.form-select:focus,
        .profit-filters input.form-control:focus {
            outline: none;
            border-color: var(--shp-primary, #2563eb);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }
        body[data-theme="dark"] .profit-filters select.form-select,
        body[data-theme="dark"] .profit-filters input.form-control {
            background: rgba(15, 23, 42, 0.6);
            border-color: rgba(255, 255, 255, .1);
            color: #e2e8f0;
        }
        /* Preset tanggal cepat */
        .date-presets { display: inline-flex; gap: .25rem; background: rgba(148,163,184,.1); padding: .25rem; border-radius: 9px; }
        .date-preset {
            border: none; background: transparent; padding: .32rem .7rem; border-radius: 7px;
            font-size: .72rem; font-weight: 600; color: #64748b; cursor: pointer; white-space: nowrap; transition: all .15s ease;
        }
        .date-preset:hover { color: var(--text, #0f172a); background: var(--card, #fff); box-shadow: 0 1px 2px rgba(0,0,0,.08); }
        .date-preset.active { color: var(--shp-primary, #2563eb); background: var(--card, #fff); box-shadow: 0 1px 2px rgba(0,0,0,.08); }
        /* KPI bergaya dpanel Ads Dashboard */
        .pkpi {
            flex: 1 1 145px; min-width: 145px; border: 1px solid rgba(148,163,184,.25);
            background: rgba(148,163,184,.05); border-radius: 12px; padding: .7rem .9rem; overflow: hidden;
        }
        .pkpi-label {
            font-size: .62rem; font-weight: 700; text-transform: uppercase; letter-spacing: .5px;
            color: #64748b; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        }
        .pkpi-value {
            font-size: 1.05rem; font-weight: 800; font-variant-numeric: tabular-nums;
            margin-top: 2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; color: var(--text, #0f172a);
        }
    </style>

    <div class="card-main mb-3" style="padding:1rem 1.25rem;">
        <div class="profit-filters" style="align-items:flex-end;">

            <div class="filter-item">
                <label>Toko</label>
                <select class="form-select filter-select w-100" style="cursor:pointer;" id="profitStoreId" onchange="loadProfits()">
                    <option value="">Semua</option>
                </select>
            </div>

            <div class="filter-item">
                <label>Status</label>
                <select class="form-select filter-select w-100" style="cursor:pointer;" id="filterStatus" onchange="loadProfits()">
                    <option value="">Semua</option>
                    <option value="COMPLETED">Selesai</option>
                    <option value="SHIPPED">Dikirim</option>
                    <option value="CANCELLED">Batal</option>
                </select>
            </div>

            <div class="filter-item">
                <label>Dana</label>
                <select class="form-select filter-select w-100" style="cursor:pointer;" id="filterSettlementStatus" onchange="loadProfits()">
                    <option value="">Semua</option>
                    <option value="cair">Cair</option>
                    <option value="belum_cair">Belum Cair</option>
                </select>
            </div>

            <div class="filter-item">
                <label>COGS</label>
                <select class="form-select filter-select w-100" style="cursor:pointer;" id="filterHppStatus" onchange="loadProfits()">
                    <option value="">Semua</option>
                    <option value="empty">Kosong</option>
                    <option value="mapped">Terisi</option>
                </select>
            </div>

            <div class="filter-item">
                <label>Urutkan</label>
                <select class="form-select filter-select w-100" style="cursor:pointer;" id="filterSort" onchange="loadProfits()">
                    <option value="">Waktu Cair</option>
                    <option value="margin_asc">Margin ↑</option>
                    <option value="margin_desc" selected>Margin ↓</option>
                    <option value="profit_asc">Profit ↑</option>
                    <option value="profit_desc">Profit ↓</option>
                </select>
            </div>

            <div class="filter-item">
                <label>Tgl Order</label>
                <input type="text" class="form-control filter-select w-100" style="cursor:pointer;" id="filterOrderDate" placeholder="Rentang tanggal">
            </div>

            <div class="filter-item">
                <label>Tgl Cair</label>
                <input type="text" class="form-control filter-select w-100" style="cursor:pointer;" id="filterSettlementDate" placeholder="Rentang tanggal">
            </div>

            <div class="filter-item search-item">
                <label>Cari</label>
                <div style="display:flex; gap:.5rem;">
                    <input type="text" class="form-control filter-select w-100" id="filterSearch" placeholder="Order SN / SKU / produk…" onkeyup="if(event.key==='Enter'){clearTimeout(searchDebounce);currentPage=1;loadProfits();}">
                    <button class="btn btn-light border" style="height:34px; padding:0 .7rem; font-size:.8rem; display:flex; align-items:center; color:var(--shp-muted); border-radius:8px; flex-shrink:0;" onclick="resetFilters()" title="Reset Filter">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </button>
                </div>
            </div>

        </div>

        <!-- Preset rentang tanggal order -->
        <div style="display:flex; align-items:center; justify-content:space-between; gap:.5rem; margin-top:.75rem; flex-wrap:wrap;">
            <div style="display:flex; align-items:center; gap:.5rem;">
                <span style="font-size:.68rem; font-weight:600; color:#94a3b8; text-transform:uppercase; letter-spacing:.05em;">Cepat</span>
                <div class="date-presets" id="datePresets">
                    <button type="button" class="date-preset" data-preset="today" onclick="setDatePreset('today')">Hari Ini</button>
                    <button type="button" class="date-preset" data-preset="7d" onclick="setDatePreset('7d')">7 Hari</button>
                    <button type="button" class="date-preset" data-preset="30d" onclick="setDatePreset('30d')">30 Hari</button>
                    <button type="button" class="date-preset" data-preset="month" onclick="setDatePreset('month')">Bulan Ini</button>
                    <button type="button" class="date-preset" data-preset="prev_month" onclick="setDatePreset('prev_month')">Bulan Lalu</button>
                </div>
            </div>
            <span id="resultCount" style="font-size:.72rem; font-weight:600; color:#64748b;"></span>
        </div>
    </div>
    
    <!-- KPI — urutan alur hitung: Harga Jual → Dana Cair − COGS − Iklan = Profit -->
    <div style="display:flex; flex-wrap: wrap; gap: .6rem; margin-bottom: 1rem;">
        <div class="pkpi" style="border-color:rgba(3,105,161,.2); background:rgba(3,105,161,.05);">
            <div class="pkpi-label" style="color:#0369a1;"><i class="bi bi-graph-up-arrow"></i> Harga Jual</div>
            <div class="pkpi-value" id="kpiOmzet" style="color:#0369a1;">—</div>
        </div>
        <div class="pkpi" style="border-color:rgba(3,105,161,.2); background:rgba(3,105,161,.05);">
            <div class="pkpi-label" style="color:#0369a1;"><i class="bi bi-wallet2"></i> Dana Cair</div>
            <div class="pkpi-value" id="kpiIncome" style="color:#0369a1;">—</div>
        </div>
        <div class="pkpi" style="border-color:rgba(100,116,139,.25); background:rgba(100,116,139,.05);">
            <div class="pkpi-label" style="color:#475569;"><i class="bi bi-box-seam"></i> &minus; COGS</div>
            <div class="pkpi-value" id="kpiHpp" style="color:#334155;">—</div>
        </div>
        <div class="pkpi" style="border-color:rgba(245,158,11,.25); background:rgba(245,158,11,.05);">
            <div class="pkpi-label" style="color:#b45309;"><i class="bi bi-badge-ad"></i> &minus; Iklan +PPN</div>
            <div class="pkpi-value" id="kpiAds" style="color:#92400e;">—</div>
        </div>
        <div class="pkpi" style="border-color:rgba(22,163,74,.3); background:rgba(22,163,74,.07);">
            <div class="pkpi-label" style="color:#15803d;"><i class="bi bi-cash-stack"></i> = Profit Bersih</div>
            <div class="pkpi-value" id="kpiProfit" style="color:#16a34a;">—</div>
        </div>
        <div class="pkpi" style="border-color:rgba(124,58,237,.2); background:rgba(124,58,237,.04);">
            <div class="pkpi-label" style="color:#7c3aed;"><i class="bi bi-percent"></i> Margin</div>
            <div class="pkpi-value" id="kpiMargin" style="color:#6d28d9;">—</div>
        </div>
        <div class="pkpi">
            <div class="pkpi-label"><i class="bi bi-receipt"></i> Avg Profit/Order</div>
            <div class="pkpi-value" id="kpiAvgProfit">—</div>
        </div>
    </div>

    <div class="card-main">
        <div id="profitBody">
            <div style="padding:2rem;text-align:center;color:var(--shp-muted);font-size:.85rem;">
                <span class="spinner-border spinner-border-sm"></span> Memuat data...
            </div>
        </div>
    </div>

</div><!-- /profitTabData -->

    <!-- ── Tab: Sync — struktur mengikuti tab Sync di Ads Dashboard ── -->
    <div id="profitTabSync" style="display:none;">

        <!-- Header + tombol aksi -->
        <div style="display:flex; align-items:center; justify-content:space-between; gap:.5rem; flex-wrap:wrap; margin-bottom:1rem;">
            <h5 style="margin:0; font-weight:700; font-size:1.02rem;"><i class="bi bi-arrow-repeat" style="color:var(--shp-primary,#2563eb);"></i> Sinkronisasi</h5>
            <div style="display:flex; gap:.4rem; flex-wrap:wrap;">
                <button type="button" class="btn fw-bold" onclick="syncFinance({days:7, mode:'missing'})" style="background:var(--shp-primary,#2563eb); color:#fff; border-radius:10px; font-size:.75rem; padding:.45rem .85rem;">
                    <i class="bi bi-lightning-charge"></i> Sync Cepat (7 Hari)
                </button>
                <button type="button" class="btn fw-bold" onclick="openSyncModal()" style="border:1px solid rgba(148,163,184,.35); color:var(--text,#0f172a); border-radius:10px; font-size:.75rem; padding:.45rem .85rem;">
                    <i class="bi bi-cloud-download"></i> Manual Sync&hellip;
                </button>
                <button type="button" class="btn fw-bold" onclick="refreshFinanceSyncStatus()" style="border:1px solid rgba(148,163,184,.35); color:#64748b; border-radius:10px; font-size:.75rem; padding:.45rem .85rem;">
                    <i class="bi bi-arrow-clockwise"></i> Refresh
                </button>
            </div>
        </div>

        <!-- Strip status -->
        <div class="card-main mb-3" style="padding:.85rem 1rem;">
            <div style="display:flex; align-items:center; gap:.6rem; flex-wrap:wrap; font-size:.72rem;">
                <span id="syncStateChip" style="display:inline-flex; align-items:center; gap:.4rem; padding:.35rem .7rem; border-radius:8px; border:1px solid rgba(22,163,74,.25); background:rgba(22,163,74,.05);">
                    <i class="bi bi-shield-check" style="color:#64748b;"></i>
                    <b id="syncStateValue" style="color:#15803d; font-weight:800;">Siap menerima sync</b>
                </span>
                <span style="display:inline-flex; align-items:center; gap:.4rem; padding:.35rem .7rem; border-radius:8px; border:1px solid rgba(148,163,184,.25);">
                    <i class="bi bi-check2-circle" style="color:#15803d;"></i>
                    Sukses terakhir: <b id="syncLastSuccess" style="color:var(--text,#0f172a);">—</b>
                </span>
                <span style="margin-left:auto; font-size:.64rem; color:#94a3b8;"><i class="bi bi-broadcast"></i> auto-refresh 5 dtk saat berjalan</span>
            </div>
        </div>

        <!-- Konsol proses berjalan (log live) -->
        <div class="card-main mb-3" style="padding:.85rem 1rem;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:.5rem;">
                <span style="font-size:.72rem; font-weight:700;"><i class="bi bi-activity"></i> Proses Berjalan <span id="financeLogMeta" style="font-weight:500; color:#64748b;"></span></span>
                <button type="button" onclick="document.getElementById('financeLogContent').textContent='> Memantau aktivitas sync…'" style="border:none; background:none; color:#94a3b8; font-size:.64rem; cursor:pointer;"><i class="bi bi-eraser"></i> bersihkan</button>
            </div>
            <pre id="financeLogContent" style="margin:0; background:rgba(2,6,23,.92); border:1px solid rgba(148,163,184,.25); border-radius:8px; padding:10px 12px; font-family:ui-monospace,SFMono-Regular,monospace; font-size:.72rem; color:#a1a1aa; height:170px; overflow-y:auto; line-height:1.55; white-space:pre-wrap;">&gt; Memantau aktivitas sync&hellip;</pre>
        </div>

        <!-- Riwayat -->
        <div class="card-main mb-3" style="padding:0; overflow:hidden;">
            <div style="display:flex; justify-content:space-between; align-items:center; padding:.6rem .9rem; border-bottom:1px solid rgba(148,163,184,.18);">
                <div style="font-weight:700; font-size:.85rem;"><i class="bi bi-clock-history" style="color:#64748b;"></i> Riwayat Sync</div>
                <span style="font-size:.68rem; color:#94a3b8;">15 proses terakhir</span>
            </div>
            <table class="table-list" style="width:100%; font-size:.78rem; border-collapse:collapse;">
                <thead>
                    <tr>
                        <th style="padding:.5rem .9rem;">#</th>
                        <th style="padding:.5rem .7rem;">Jenis</th>
                        <th style="padding:.5rem .7rem;">Status</th>
                        <th style="padding:.5rem .7rem;">Mulai</th>
                        <th style="padding:.5rem .7rem;">Selesai</th>
                        <th style="padding:.5rem .7rem; text-align:right;">Durasi</th>
                        <th style="padding:.5rem .9rem; text-align:center;">Log</th>
                    </tr>
                </thead>
                <tbody id="syncRunsBody">
                    <tr><td colspan="7" style="padding:1.5rem; text-align:center; color:var(--shp-muted);">Memuat riwayat…</td></tr>
                </tbody>
            </table>
        </div>

    </div>

    <!-- ── Modal Sync Data ── -->
    <div id="syncModal" style="display:none; position:fixed; inset:0; z-index:1050; background:rgba(15,23,42,.45); align-items:center; justify-content:center; padding:1rem;" onclick="if(event.target===this)closeSyncModal()">
        <div style="background:var(--card,#fff); border-radius:14px; width:100%; max-width:430px; padding:1.1rem 1.2rem; box-shadow:0 20px 60px rgba(0,0,0,.25);">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:.8rem;">
                <div style="font-weight:800; font-size:.95rem;">Sync Data Keuangan</div>
                <button type="button" onclick="closeSyncModal()" style="border:none; background:none; color:#64748b; cursor:pointer; font-size:1rem;">✕</button>
            </div>

            <div style="margin-bottom:.65rem;">
                <label style="display:block; font-size:.68rem; font-weight:600; color:#64748b; text-transform:uppercase; letter-spacing:.05em; margin-bottom:.3rem;">Rentang</label>
                <select id="syncRange" class="form-select" style="font-size:.82rem; height:36px; border-radius:8px; border:1px solid rgba(148,163,184,.3);">
                    <option value="d1">1 hari terakhir</option>
                    <option value="d3">3 hari terakhir</option>
                    <option value="d7" selected>7 hari terakhir</option>
                    <option value="d14">14 hari terakhir</option>
                    <option value="d30">30 hari terakhir</option>
                    <option value="m3">3 bulan terakhir</option>
                    <option value="m6">6 bulan terakhir</option>
                </select>
            </div>

            <label style="display:flex; align-items:flex-start; gap:.5rem; font-size:.78rem; cursor:pointer; margin-bottom:.75rem;">
                <input type="checkbox" id="syncMissingOnly" checked style="margin-top:2px;">
                <span><b>Cek data dulu — ambil yang belum ada saja</b><br>
                <small style="color:#64748b;">lebih cepat; matikan untuk tarik ulang semua (perbaiki data lama)</small></span>
            </label>

            <button type="button" class="btn btn-dark w-100" style="border-radius:9px; font-weight:700; font-size:.82rem; height:38px;" onclick="startFinanceSync()">
                <i class="bi bi-arrow-repeat"></i> Mulai Sync
            </button>

            <div style="border-top:1px solid rgba(148,163,184,.25); margin:.7rem 0;"></div>

            <button type="button" class="sync-opt" onclick="closeSyncModal(); syncHpp();">
                <span class="sync-opt-icon" style="background:rgba(22,163,74,.1); color:#16a34a;"><i class="bi bi-tags"></i></span>
                <span style="flex:1; text-align:left;">
                    <b>Sync COGS</b><br>
                    <small>tarik HPP terbaru dari master produk</small>
                </span>
            </button>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    /* Segmented tabs — mengikuti .dash-tabs-modern / .dash-tab-m di Ads Dashboard */
    .profit-tabs-modern{
        display:inline-flex; flex-wrap:wrap; background:rgba(148,163,184,.1); padding:.35rem; border-radius:12px; gap:.25rem;
    }
    body[data-theme="dark"] .profit-tabs-modern{ background:rgba(30,41,59,.5); }
    .profit-tab-m{
        border:none; background:transparent; padding:.6rem 1.25rem; border-radius:8px;
        font-weight:600; font-size:.85rem; color:#64748b; cursor:pointer;
        transition:all .2s ease; display:flex; align-items:center; gap:.5rem; white-space:nowrap;
    }
    .profit-tab-m:hover{ color:var(--text,#0f172a); }
    .profit-tab-m.active{
        background:var(--card,#fff); color:var(--text,#0f172a);
        box-shadow:0 1px 3px rgba(0,0,0,.05), 0 1px 2px rgba(0,0,0,.1);
    }
    body[data-theme="dark"] .profit-tab-m{ color:#94a3b8; }
    body[data-theme="dark"] .profit-tab-m.active{ background:var(--card,#1e293b); color:var(--text,#e2e8f0); }
    .sync-opt{
        display:flex; align-items:center; gap:.7rem; width:100%; margin-bottom:.45rem;
        padding:.6rem .7rem; border:1px solid rgba(148,163,184,.3); border-radius:10px;
        background:none; cursor:pointer; font-size:.8rem; color:inherit; transition:border-color .15s, background .15s;
    }
    .sync-opt:hover{ border-color:var(--shp-primary,#2563eb); background:rgba(37,99,235,.04); }
    .sync-opt small{ color:#64748b; }
    .sync-opt-icon{
        display:inline-flex; align-items:center; justify-content:center;
        width:34px; height:34px; border-radius:9px; font-size:1rem; flex-shrink:0;
    }
    .sync-pill{
        display:inline-flex; align-items:center; gap:.3rem;
        padding:2px 10px; border-radius:999px; font-size:.7rem; font-weight:700; white-space:nowrap;
    }
    body[data-theme="dark"] #syncRange{
        background: rgba(15,23,42,.6); color:#e2e8f0; border-color: rgba(255,255,255,.12);
    }
    /* Ikon berputar untuk status sync — dipakai chip & pill, sebelumnya tidak terdefinisi */
    .spin{ display:inline-block; animation: profit-spin 1s linear infinite; }
    @keyframes profit-spin{ from{ transform:rotate(0deg); } to{ transform:rotate(360deg); } }
    .table-list thead th{
        position: sticky;
        top: 0;
        z-index: 10;
        border-bottom-width:1px;
        font-size:.68rem;
        text-transform:none;
        color:#64748b;
        background: var(--card,#fff);
        padding:.45rem .62rem;
        box-shadow: 0 1px 0 rgba(148,163,184,.18);
    }
    body[data-theme="dark"] .table-list thead th{
        background: rgba(15, 23, 42, 0.98);
        color:#9ca3af;
        border-bottom-color: rgba(30, 64, 175, 0.6);
    }
    .table-list tbody td{
        vertical-align:top;
        border-top-color: rgba(148, 163, 184, 0.16);
        padding:.4rem .45rem;
    }
    body[data-theme="dark"] .table-list tbody td{ border-top-color: rgba(51, 65, 85, 0.85); }
    .code-link{ font-weight:700; text-decoration:none; color:inherit; }
</style>
@endpush

@push('scripts')
<script>
(function () {
    const { api, fmt, fmtDate, fmtRp, esc, channelPill, statusBadge } = window.mpHelpers;
    let rows = [], stores = [];
    let fpOrderDate = null, fpSettlementDate = null;
    let currentPage = 1;
    let paginationData = null;
    const $ = id => document.getElementById(id);

    // ── Init ──────────────────────────────────────────────────────────────────
    async function init() {
        // Status sync finance terakhir (chip di header); kalau masih berjalan,
        // langsung ikut memantau sampai selesai.
        refreshFinanceSyncStatus().then(latest => {
            if (latest && latest.status === 'processing') startFinancePolling();
        });

        stores = await api('/api/marketplace/stores').catch(() => []);
        const sel = $('profitStoreId');
        stores.forEach(s => {
            const opt = document.createElement('option');
            opt.value = s.id;
            opt.textContent = s.name + ' (' + (s.channel?.name || '?') + ')';
            sel.appendChild(opt);
        });

        // 1. Prepare default dates
        const today = new Date();
        const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
        const lastDay = new Date(today.getFullYear(), today.getMonth() + 1, 0);
        let defaultOrderDate = [firstDay, lastDay];
        let defaultSettlementDate = [firstDay, lastDay];

        // 2. Read saved state
        const savedRaw = sessionStorage.getItem('mpProfitFilters');
        let saved = null;
        if (savedRaw) {
            try { saved = JSON.parse(savedRaw); } catch(e){}
        }

        if (saved) {
            if (saved.filterOrderDate) defaultOrderDate = saved.filterOrderDate;
            if (saved.filterSettlementDate) defaultSettlementDate = saved.filterSettlementDate;
        }

        // 3. Init pickers — konfigurasi DISALIN APA ADANYA dari rangePicker
        //    Ads Dashboard (terbukti bisa blok rentang), TANPA helper GFID.
        function initRangePicker(sel, defaultDate) {
            const el = document.querySelector(sel);
            if (!el || typeof flatpickr === 'undefined') {
                console.error('[profit] flatpickr tidak tersedia untuk', sel);
                return null;
            }
            if (el._flatpickr) el._flatpickr.destroy();
            const applyDates = function () { markPreset(null); currentPage = 1; loadProfits(); };
            return flatpickr(el, {
                mode: 'range',
                locale: (flatpickr.l10ns && flatpickr.l10ns.id) ? 'id' : 'default',
                showMonths: 1,
                dateFormat: 'Y-m-d',
                altInput: true,
                altFormat: 'j M Y',
                disableMobile: true,
                defaultDate: defaultDate || null,
                onChange: function (selectedDates) {
                    // Tanggal kedua diklik → langsung apply (persis perilaku Ads)
                    if (selectedDates.length === 2) applyDates();
                },
                onClose: function (selectedDates, dateStr, instance) {
                    if (selectedDates.length === 1) {
                        // Satu tanggal lalu tutup = filter satu hari itu
                        instance.setDate([selectedDates[0], selectedDates[0]], false);
                        applyDates();
                    } else if (selectedDates.length === 0) {
                        applyDates(); // rentang dihapus
                    }
                }
            });
        }
        fpOrderDate = initRangePicker('#filterOrderDate', defaultOrderDate);
        fpSettlementDate = initRangePicker('#filterSettlementDate', defaultSettlementDate);
        
        // 4. Restore other filters or apply defaults
        if (saved) {
            ['profitStoreId', 'filterStatus', 'filterSettlementStatus', 'filterHppStatus', 'filterSort', 'filterSearch'].forEach(id => {
                if (saved[id] !== undefined && $(id)) $(id).value = saved[id];
            });
        } else {
            ['filterStatus', 'filterSettlementStatus', 'filterHppStatus', 'filterSearch'].forEach(id => {
                const el = document.getElementById(id);
                if (el) el.value = '';
            });
            $('filterSort').value = 'margin_desc';
        }

        // Hanya SELECT — pencarian sudah ditangani debounce + Enter,
        // ikut listener 'change' bikin reload dobel saat input kehilangan fokus.
        document.querySelectorAll('select.filter-select').forEach(el => {
            el.addEventListener('change', () => { currentPage = 1; loadProfits(); });
        });

        // Rentang awal = bulan berjalan → tandai preset "Bulan Ini"
        if (!saved || !saved.filterOrderDate) markPreset('month');

        loadProfits();
    }

    // ── Sync finance: tanpa popup — chip status inline + polling run-tracking ──
    let financePollTimer = null;

    function setFinanceChip(html, fg, bg) {
        const chip = $('financeSyncChip');
        if (!chip) return;
        if (!html) { chip.style.display = 'none'; return; }
        chip.style.display = 'inline-block';
        chip.style.color = fg;
        chip.style.background = bg;
        chip.innerHTML = html;
    }

    // ── Tab Profit / Riwayat Sync ─────────────────────────────────────────────
    let activeProfitTab = 'summary';

    // Navigasi mengikuti prioritas kerja: lihat hasil → perbaiki mapping →
    // cek dana tertahan → pantau sinkronisasi.
    window.switchProfitTab = function (tab) {
        if (tab === 'data') tab = 'summary'; // kompatibilitas pemanggil lama
        activeProfitTab = tab;
        const isSync = tab === 'sync';
        $('profitTabData').style.display = isSync ? 'none' : '';
        $('profitTabSync').style.display = isSync ? '' : 'none';
        $('ptabSummary').classList.toggle('active', tab === 'summary');
        $('ptabUnmapped').classList.toggle('active', tab === 'unmapped');
        $('ptabUnsettled').classList.toggle('active', tab === 'unsettled');
        $('ptabSync').classList.toggle('active', isSync);

        if (!isSync) {
            if (tab === 'unmapped') {
                $('filterHppStatus').value = 'empty';
                $('filterSettlementStatus').value = '';
            } else if (tab === 'unsettled') {
                $('filterHppStatus').value = '';
                $('filterSettlementStatus').value = 'belum_cair';
            } else {
                $('filterHppStatus').value = '';
                $('filterSettlementStatus').value = '';
            }
            currentPage = 1;
            loadProfits();
        } else {
            refreshFinanceSyncStatus();
        }
    };

    // ── Modal sync ────────────────────────────────────────────────────────────
    window.openSyncModal  = function () { $('syncModal').style.display = 'flex'; };
    window.closeSyncModal = function () { $('syncModal').style.display = 'none'; };
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && $('syncModal').style.display === 'flex') closeSyncModal();
    });

    // Pencarian otomatis 500 ms setelah berhenti mengetik (Enter tetap langsung)
    let searchDebounce = null;
    (function () {
        const si = $('filterSearch');
        if (!si) return;
        si.addEventListener('input', function () {
            clearTimeout(searchDebounce);
            searchDebounce = setTimeout(() => { currentPage = 1; loadProfits(); }, 500);
        });
    })();

    function statusBadgeSync(status) {
        if (status === 'processing') return '<span class="sync-pill" style="color:#1d4ed8; background:rgba(59,130,246,.12);"><i class="bi bi-arrow-repeat spin"></i> berjalan</span>';
        if (status === 'success')    return '<span class="sync-pill" style="color:#15803d; background:rgba(22,163,74,.12);">✓ selesai</span>';
        return '<span class="sync-pill" style="color:#b91c1c; background:rgba(220,38,38,.12);">✕ gagal</span>';
    }

    function triggerLabel(t) {
        if (!t) return '—';
        if (t === 'manual') return 'Manual · 1 bln';
        if (t.indexOf('manual ') === 0) return 'Manual · ' + t.slice(7);
        if (t.indexOf('backfill') === 0) return 'Backfill · ' + t.replace('backfill', '').trim();
        if (['schedule', 'scheduled', 'cron'].includes(t)) return 'Terjadwal';
        return t.charAt(0).toUpperCase() + t.slice(1);
    }

    let syncRunsCache = [];
    let openLogRunId = null;   // run yang sedang dibuka lognya di panel
    let autoOpenLog  = false;  // buka log otomatis saat sync baru dimulai
    let syncTabTimer = null;   // poll pelan selama ada run berjalan & tab sync terbuka

    function fillLogPanel(r) {
        const running = r.status === 'processing';
        const out = r.output || (running
            ? 'Berjalan sejak ' + (r.started_at || '—') + ' — menunggu output…\n(Kalau terus kosong: restart queue worker → php artisan queue:restart)'
            : 'Tidak ada output tercatat.');
        const pre = $('financeLogContent');
        const nearBottom = pre.scrollHeight - pre.scrollTop - pre.clientHeight < 60;
        pre.textContent = out + (r.error ? '\n\n[ERROR] ' + r.error : '');
        if (running && nearBottom) pre.scrollTop = pre.scrollHeight; // ikuti log terbaru
        $('financeLogMeta').textContent = '— run #' + r.id + ' (' + (r.trigger || '-') + ') '
            + (running ? '· berjalan…' : (r.finished_at || r.started_at || ''));
    }

    function renderSyncRuns(runs) {
        syncRunsCache = runs || [];

        // Sync baru dimulai → konsol langsung mengikuti run teratas
        if (autoOpenLog && syncRunsCache.length && syncRunsCache[0].status === 'processing') {
            autoOpenLog = false;
            openLogRunId = syncRunsCache[0].id;
        }

        // Segarkan isi konsol dengan run yang sedang diikuti
        if (openLogRunId != null) {
            const r = syncRunsCache.find(x => x.id === openLogRunId);
            if (r) fillLogPanel(r);
        }

        // Selama ada run berjalan & tab sync terlihat, poll pelan (tiap 5 dtk)
        clearTimeout(syncTabTimer);
        if (syncRunsCache.some(r => r.status === 'processing') && $('profitTabSync').style.display !== 'none') {
            syncTabTimer = setTimeout(refreshFinanceSyncStatus, 5000);
        }
        const body = $('syncRunsBody');
        if (!body) return;
        if (!syncRunsCache.length) {
            body.innerHTML = '<tr><td colspan="7" style="padding:1.5rem; text-align:center; color:var(--shp-muted);">Belum ada riwayat sync.</td></tr>';
            return;
        }
        body.innerHTML = syncRunsCache.map((r, i) => `
            <tr style="border-top:1px solid rgba(148,163,184,.16);">
                <td style="padding:.5rem .9rem; color:#94a3b8; font-variant-numeric:tabular-nums;">${r.id}</td>
                <td style="padding:.5rem .7rem; font-weight:600; white-space:nowrap;">${esc(triggerLabel(r.trigger))}</td>
                <td style="padding:.5rem .7rem;">${statusBadgeSync(r.status)}${r.error ? '<div style="font-size:.68rem; color:#b91c1c; margin-top:2px; max-width:260px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;" title="' + esc(r.error) + '">' + esc(r.error) + '</div>' : ''}</td>
                <td style="padding:.5rem .7rem; white-space:nowrap; color:#64748b;">${r.started_at || '—'}</td>
                <td style="padding:.5rem .7rem; white-space:nowrap; color:#64748b;">${r.finished_at || '—'}</td>
                <td style="padding:.5rem .7rem; text-align:right; font-variant-numeric:tabular-nums;">${r.duration != null ? r.duration + ' dtk' : '—'}</td>
                <td style="padding:.5rem .9rem; text-align:center;">${r.output ? '<button type="button" onclick="showRunLog(' + i + ')" style="border:none; background:none; color:var(--shp-primary,#2563eb); cursor:pointer; font-size:.72rem; font-weight:600;">lihat</button>' : '<span style="color:#cbd5e1;">—</span>'}</td>
            </tr>`).join('');
        // Dot indikator di tab saat ada run yang sedang berjalan
        $('syncTabDot').style.display = syncRunsCache.some(r => r.status === 'processing') ? 'inline-block' : 'none';
    }

    window.showRunLog = function (i) {
        const r = syncRunsCache[i];
        if (!r) return;
        openLogRunId = r.id;
        fillLogPanel(r);
        $('financeLogContent').scrollTop = 0; // baca log run lama dari atas
    };

    async function refreshFinanceSyncStatus() {
        try {
            const res = await fetch('/api/marketplace/sync-finance-status', { headers: { 'Accept': 'application/json' } });
            if (!res.ok) return null;
            const data = await res.json();
            renderSyncRuns(data.runs || []);
            const latest = (data.runs || [])[0] || null;
            if (!latest) return null;

            const logLink = $('financeLogToggle');
            if (logLink) logLink.style.display = 'inline';
            // Belum ada run yang dipilih → konsol mengikuti run terbaru
            if (openLogRunId == null) {
                openLogRunId = latest.id;
                fillLogPanel(latest);
            }

            // Strip status ala tab Sync Ads Dashboard
            const running = data.runs.some(r => r.status === 'processing');
            const chipEl = $('syncStateChip'), valEl = $('syncStateValue');
            if (chipEl && valEl) {
                if (running) {
                    valEl.innerHTML = '<i class="bi bi-arrow-repeat spin"></i> Sync sedang berjalan…';
                    valEl.style.color = '#1d4ed8';
                    chipEl.style.borderColor = 'rgba(59,130,246,.35)';
                    chipEl.style.background = 'rgba(59,130,246,.07)';
                } else {
                    valEl.textContent = 'Siap menerima sync';
                    valEl.style.color = '#15803d';
                    chipEl.style.borderColor = 'rgba(22,163,74,.25)';
                    chipEl.style.background = 'rgba(22,163,74,.05)';
                }
            }
            const lastOk = data.runs.find(r => r.status === 'success');
            if ($('syncLastSuccess')) $('syncLastSuccess').textContent = lastOk ? (lastOk.finished_at || '—') : '—';

            if (latest.status === 'processing') {
                setFinanceChip('<i class="bi bi-arrow-repeat spin"></i> Sync berjalan di background…', '#1d4ed8', 'rgba(59,130,246,.12)');
            } else if (latest.status === 'success') {
                setFinanceChip('✓ ' + triggerLabel(latest.trigger) + ' selesai ' + (latest.finished_at || '') + (latest.duration ? ' (' + latest.duration + ' dtk)' : ''), '#15803d', 'rgba(22,163,74,.12)');
            } else {
                setFinanceChip('✕ Sync gagal — ' + String(latest.error || 'lihat log').slice(0, 80), '#b91c1c', 'rgba(220,38,38,.12)');
            }
            return latest;
        } catch (e) { return null; }
    }

    function startFinancePolling() {
        if (financePollTimer) clearInterval(financePollTimer);
        let polls = 0;
        financePollTimer = setInterval(async () => {
            polls++;
            const latest = await refreshFinanceSyncStatus();
            if (!latest || polls > 180) { clearInterval(financePollTimer); financePollTimer = null; return; }
            if (latest.status !== 'processing') {
                clearInterval(financePollTimer);
                financePollTimer = null;
                if (latest.status === 'success') loadProfits(); // muat ulang data begitu selesai
            }
        }, 5000);
    }

    window.toggleFinanceLog = function () {
        switchProfitTab('sync'); // konsol log selalu tampil di tab Sync
    };

    // Baca pilihan modal → kirim ke backend
    window.startFinanceSync = function () {
        const v = $('syncRange').value;
        const payload = { mode: $('syncMissingOnly').checked ? 'missing' : 'full' };
        if (v.charAt(0) === 'd') payload.days = parseInt(v.slice(1), 10);
        else payload.months = parseInt(v.slice(1), 10);
        syncFinance(payload);
    };

    window.syncFinance = async function(payload) {
        payload = payload || { months: 1, mode: 'missing' };
        closeSyncModal();
        const label = payload.days ? payload.days + ' hari' : (payload.months || 1) + ' bulan';
        try {
            const res = await fetch('/api/marketplace/sync-finance-all', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify(payload)
            });
            const data = await res.json().catch(() => ({}));

            if (data.status === 'no_change') {
                // Data sudah lengkap → tidak ada job background yang dibuat
                setFinanceChip('✓ ' + (data.message || 'Data sudah lengkap — tidak perlu sync.'), '#15803d', 'rgba(22,163,74,.12)');
            } else if (res.status === 409) {
                switchProfitTab('sync');
                setFinanceChip('⏳ ' + (data.message || 'Sync masih berjalan — tunggu sampai selesai.'), '#b45309', 'rgba(245,158,11,.14)');
                startFinancePolling();
            } else if (!res.ok) {
                setFinanceChip('✕ Gagal memulai sync (' + res.status + ')', '#b91c1c', 'rgba(220,38,38,.12)');
            } else {
                switchProfitTab('sync'); // perlihatkan progres di tab Sync
                setFinanceChip('<i class="bi bi-arrow-repeat spin"></i> Sync ' + label + (payload.mode === 'missing' ? ' (hanya yang belum ada)' : '') + ' berjalan di background…', '#1d4ed8', 'rgba(59,130,246,.12)');
                autoOpenLog = true; // log detail langsung dibuka begitu run muncul
                startFinancePolling();
            }
            refreshFinanceSyncStatus();
        } catch (err) {
            setFinanceChip('✕ Gagal terhubung: ' + err.message, '#b91c1c', 'rgba(220,38,38,.12)');
        }
    }
    
    // Preset rentang tanggal order (Cepat) — gaya quick-range Ads Dashboard
    window.setDatePreset = function (preset) {
        const today = new Date();
        let from, to;
        if (preset === 'today') { from = to = today; }
        else if (preset === '7d')  { to = today; from = new Date(today); from.setDate(from.getDate() - 6); }
        else if (preset === '30d') { to = today; from = new Date(today); from.setDate(from.getDate() - 29); }
        else if (preset === 'month') { from = new Date(today.getFullYear(), today.getMonth(), 1); to = new Date(today.getFullYear(), today.getMonth() + 1, 0); }
        else if (preset === 'prev_month') { from = new Date(today.getFullYear(), today.getMonth() - 1, 1); to = new Date(today.getFullYear(), today.getMonth(), 0); }
        else return;
        markPreset(preset);
        if (fpOrderDate) fpOrderDate.setDate([from, to]);
        currentPage = 1;
        loadProfits();
    };

    function markPreset(preset) {
        document.querySelectorAll('#datePresets .date-preset').forEach(b =>
            b.classList.toggle('active', b.dataset.preset === preset));
    }

    window.resetFilters = function() {
        sessionStorage.removeItem('mpProfitFilters');
        $('profitStoreId').value = '';
        ['filterStatus', 'filterSettlementStatus', 'filterHppStatus', 'filterSearch'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.value = '';
        });
        $('filterSort').value = 'margin_desc';
        
        const today = new Date();
        const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
        const lastDay = new Date(today.getFullYear(), today.getMonth() + 1, 0);

        if (fpOrderDate) fpOrderDate.setDate([firstDay, lastDay]);
        if (fpSettlementDate) fpSettlementDate.setDate([firstDay, lastDay]);
        markPreset('month'); // default reset = bulan ini

        currentPage = 1;
        loadProfits();
    };
    
    window.goToPage = function(page) {
        currentPage = page;
        loadProfits();
    };

    window.syncHpp = async function() {
        if (!confirm('Tarik COGS terbaru dari master produk untuk semua order yang sudah ter-mapping?')) return;
        
        // Find the button and show loading state
        const btn = document.activeElement && document.activeElement.tagName === 'BUTTON' ? document.activeElement : null;
        let oldText = '';
        if (btn) {
            oldText = btn.innerHTML;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Syncing...';
            btn.disabled = true;
        }
        
        const storeId = $('profitStoreId').value;
        const payload = storeId ? { store_id: storeId } : {};
        
        try {
            const res = await api('/api/marketplace/sync-hpp', {
                method: 'POST',
                body: JSON.stringify(payload)
            });
            alert(res.message + ' (' + res.updated + ' diperbarui)');
            loadProfits();
        } catch (e) {
            alert('Gagal: ' + e.message);
        } finally {
            if (btn) {
                btn.innerHTML = oldText;
                btn.disabled = false;
            }
        }
    };

    // ── Load ──────────────────────────────────────────────────────────────────
    window.loadProfits = async function () {
        // Muat ulang lembut: tabel yang sudah tampil cukup diredupkan,
        // bukan diganti spinner — tidak ada kedipan tiap ganti filter.
        const pb = $('profitBody');
        if (pb.querySelector('table')) {
            pb.style.opacity = '.45';
            pb.style.pointerEvents = 'none';
        } else {
            pb.innerHTML = '<div style="padding:2rem;text-align:center;color:var(--shp-muted);font-size:.85rem;"><span class="spinner-border spinner-border-sm"></span> Memuat data...</div>';
        }
        
        const params = new URLSearchParams();
        if ($('profitStoreId').value) params.append('store_id', $('profitStoreId').value);
        if ($('filterStatus').value) params.append('status', $('filterStatus').value);
        if ($('filterSettlementStatus').value) params.append('settlement_status', $('filterSettlementStatus').value);
        if ($('filterHppStatus').value) params.append('hpp_status', $('filterHppStatus').value);
        if ($('filterSort').value) params.append('sort', $('filterSort').value);
        if ($('filterSearch').value) params.append('search', $('filterSearch').value);
        params.append('page', currentPage);
        params.append('per_page', 50);

        let savedOrderDate = null;
        if (fpOrderDate && fpOrderDate.selectedDates && fpOrderDate.selectedDates.length === 2) {
            savedOrderDate = fpOrderDate.selectedDates;
            params.append('order_date_from', fpOrderDate.formatDate(fpOrderDate.selectedDates[0], 'Y-m-d'));
            params.append('order_date_to', fpOrderDate.formatDate(fpOrderDate.selectedDates[1], 'Y-m-d'));
        }
        
        let savedSettlementDate = null;
        if (fpSettlementDate && fpSettlementDate.selectedDates && fpSettlementDate.selectedDates.length === 2) {
            savedSettlementDate = fpSettlementDate.selectedDates;
            params.append('settlement_date_from', fpSettlementDate.formatDate(fpSettlementDate.selectedDates[0], 'Y-m-d'));
            params.append('settlement_date_to', fpSettlementDate.formatDate(fpSettlementDate.selectedDates[1], 'Y-m-d'));
        }

        // Save state to sessionStorage
        try {
            sessionStorage.setItem('mpProfitFilters', JSON.stringify({
                profitStoreId: $('profitStoreId').value,
                filterStatus: $('filterStatus').value,
                filterSettlementStatus: $('filterSettlementStatus').value,
                filterHppStatus: $('filterHppStatus').value,
                filterSort: $('filterSort').value,
                filterSearch: $('filterSearch').value,
                filterOrderDate: savedOrderDate,
                filterSettlementDate: savedSettlementDate
            }));
        } catch(e) {}

        try {
            const res = await api('/api/marketplace/order-profits?' + params.toString());
            if (!res || !res.paginator) {
                throw new Error("Invalid response format from server.");
            }
            
            rows = res.paginator.data || [];
            paginationData = res.paginator;
            
            renderKpi(res.meta);
            renderTable();
        } catch (e) {
            $('profitBody').innerHTML = '<div style="padding:2rem;text-align:center;color:#b91c1c;">Gagal memuat data: ' + e.message + '</div>';
        } finally {
            pb.style.opacity = '';
            pb.style.pointerEvents = '';
        }
    };
    
    window.exportCsv = function() {
        const params = new URLSearchParams();
        if ($('profitStoreId').value) params.append('store_id', $('profitStoreId').value);
        if ($('filterStatus').value) params.append('status', $('filterStatus').value);
        if ($('filterSettlementStatus').value) params.append('settlement_status', $('filterSettlementStatus').value);
        if ($('filterHppStatus').value) params.append('hpp_status', $('filterHppStatus').value);
        if ($('filterSort').value) params.append('sort', $('filterSort').value);
        if ($('filterSearch').value) params.append('search', $('filterSearch').value);
        if (fpOrderDate && fpOrderDate.selectedDates && fpOrderDate.selectedDates.length === 2) {
            params.append('order_date_from', fpOrderDate.formatDate(fpOrderDate.selectedDates[0], 'Y-m-d'));
            params.append('order_date_to', fpOrderDate.formatDate(fpOrderDate.selectedDates[1], 'Y-m-d'));
        }
        if (fpSettlementDate && fpSettlementDate.selectedDates && fpSettlementDate.selectedDates.length === 2) {
            params.append('settlement_date_from', fpSettlementDate.formatDate(fpSettlementDate.selectedDates[0], 'Y-m-d'));
            params.append('settlement_date_to', fpSettlementDate.formatDate(fpSettlementDate.selectedDates[1], 'Y-m-d'));
        }
        params.append('export', 'csv');
        
        window.location.href = '/api/marketplace/order-profits?' + params.toString();
    };

    let isSyncing = false;
    let hasUnmapped = false;

    const fmtDateTime = (iso) => {
        if (!iso) return '';
        const d = new Date(iso);
        if (isNaN(d.getTime())) return iso;
        const pad = n => n.toString().padStart(2, '0');
        const day = pad(d.getDate());
        const month = d.toLocaleString('id-ID', { month: 'short' });
        const year = d.getFullYear();
        const hrs = pad(d.getHours());
        const min = pad(d.getMinutes());
        return `${day} ${month} ${year} ${hrs}:${min}`;
    };

    function renderKpi(meta) {
        if (!meta) return;
        hasUnmapped = rows.some(r => !r.hpp_mapped);

        // Profit & margin final = sudah dikurangi biaya iklan (+PPN) dari Ads Dashboard.
        // Fallback ke angka lama kalau backend belum mengirim field *_final.
        const profitFinal = meta.kpi_profit_final !== undefined ? meta.kpi_profit_final : meta.kpi_profit;
        const marginFinal = meta.kpi_margin_final !== undefined ? meta.kpi_margin_final : meta.kpi_margin;
        const avgFinal    = meta.avg_profit_final !== undefined ? meta.avg_profit_final : meta.avg_profit;

        $('kpiOmzet').textContent  = fmtRp(meta.kpi_omzet);
        $('kpiIncome').textContent = fmtRp(meta.kpi_net);
        $('kpiHpp').textContent    = fmtRp(meta.kpi_hpp);
        $('kpiAds').textContent    = meta.kpi_ads_total !== undefined
            ? (meta.kpi_ads_total > 0 ? '−' + fmtRp(meta.kpi_ads_total) : fmtRp(0))
            : '—';
        $('kpiProfit').textContent = fmtRp(profitFinal);
        $('kpiProfit').style.color = profitFinal >= 0 ? '#16a34a' : '#dc2626';
        $('kpiAvgProfit').textContent = fmtRp(avgFinal);
        $('kpiMargin').textContent = marginFinal !== null ? marginFinal + '%' : '—';
        $('kpiMargin').style.color = (marginFinal !== null && marginFinal < 0) ? '#dc2626' : '#6d28d9';

        if (meta.last_sync) {
            $('lastSyncTime').textContent = fmtDateTime(meta.last_sync);
        } else {
            $('lastSyncTime').textContent = '—';
        }

        const rc = $('resultCount');
        if (rc) rc.textContent = meta.kpi_count != null ? meta.kpi_count.toLocaleString('id-ID') + ' order cocok' : '';

        $('profitHppWarning').className = 'alert alert-warning mt-3' + (hasUnmapped ? '' : ' d-none');
    }

    function renderTable() {
        const body = $('profitBody');
        if (!rows || rows.length === 0) {
            body.innerHTML = '<div style="padding:40px; text-align:center; color:#94a3b8;">'
                + '<i class="bi bi-inbox fs-1 d-block mb-2"></i>Tidak ada data untuk filter ini.'
                + '<div style="margin-top:12px;"><button class="btn btn-sm btn-light border" style="border-radius:8px; font-size:.78rem;" onclick="resetFilters()"><i class="bi bi-arrow-counterclockwise"></i> Reset filter</button></div>'
                + '</div>';
            return;
        }

        let html = `
        <div class="table-responsive" style="margin:0; border:none; max-height:65vh; overflow-y:auto;">
        <table class="table table-list w-100" style="margin-bottom:0">
            <thead>
                <tr>
                    <th style="min-width:130px">Order & Toko</th>
                    <th style="min-width:100px">Waktu</th>
                    <th class="text-end" style="min-width:90px">Harga Jual</th>
                    <th class="text-end" style="min-width:110px">Promosi Seller</th>
                    <th class="text-end" style="min-width:100px">Dana Cair</th>
                    <th class="text-end" title="COGS dari snapshot aktif">COGS</th>
                    <th class="text-end" style="color:#16a34a;font-weight:900">Profit</th>
                    <th class="text-end">Margin</th>
                </tr>
            </thead>
            <tbody>
            ${rows.map((r, idx) => {
                let marginColor = '#b91c1c'; // Red
                if (r.margin_pct >= 15) marginColor = '#16a34a'; // Green
                else if (r.margin_pct >= 0) marginColor = '#d97706'; // Amber/Orange
                
                const profitColor = r.profit_net >= 0 ? '#16a34a' : '#b91c1c';
                const hppLabel = r.hpp_mapped
                    ? fmtRp(r.hpp_total)
                    : `<span style="color:#b91c1c" title="SKU belum ter-mapping">${fmtRp(r.hpp_total)} ⚠</span>`;
                
                let omzetGross = r.buyer_payment_amount;
                if (r.raw_json) {
                    const inc = r.raw_json.income_details || {};
                    omzetGross = inc.cost_of_goods_sold || inc.order_selling_price || r.raw_json.cost_of_goods_sold || r.raw_json.order_selling_price || r.buyer_payment_amount;
                }

                return `<tr>
                    <td>
                        <a href="/marketplace/orders/${r.order?.id || ''}" class="code-link">${esc(r.channel_order_id)}</a>
                        <div style="margin-top:3px">${r.order?.order_status ? `<span class="oc-badge oc-badge-muted" style="font-size:.65rem">${esc(r.order.order_status)}</span>` : ''}</div>
                        <div style="font-size:.75rem; font-weight:600; margin-top:4px; color:var(--shp-muted);">${esc(r.store?.name || '—')}</div>
                        ${r.items && r.items.length ? `
                        <div style="margin-top:6px; border-top:1px dashed var(--shp-border); padding-top:4px;">
                            ${r.items.map(i => `
                                <div style="display:flex; align-items:center; gap:4px; font-size:.68rem; margin-bottom:2px;" title="${esc(i.sku)} (Qty: ${i.qty})">
                                    ${i.mapped ? '<span style="color:#16a34a">✓</span>' : '<span style="color:#b91c1c;font-weight:bold" title="Belum di-mapping atau COGS kosong">!</span>'}
                                    <span style="color:var(--shp-text); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:110px; flex:1;">${esc(i.sku)}</span>
                                    <span style="color:#94a3b8">x${i.qty}</span>
                                </div>
                            `).join('')}
                        </div>
                        ` : ''}
                    </td>
                    
                    <td style="font-size:.78rem;">
                        <div style="color:var(--shp-text)">${r.order?.ordered_at ? fmtDateTime(r.order.ordered_at) : '<span style="color:#94a3b8">—</span>'}</div>
                        <div style="font-size:.72rem;color:var(--shp-muted);margin-top:2px;">
                            ${r.settlement_time ? fmtDateTime(r.settlement_time) : '<span class="oc-badge oc-badge-amber" style="font-size:.65rem;padding:.1rem .3rem">Belum Cair</span>'}
                        </div>
                    </td>
                    
                    <td class="text-end">
                        <div class="fw-bold" style="font-size:.85rem; color:var(--shp-text);">${fmtRp(omzetGross)}</div>
                    </td>

                    <td class="text-end">
                        <div style="font-size:.75rem; color:var(--shp-muted);">
                            Vcr: <span style="font-weight:600;color:${r.seller_voucher ? '#b91c1c' : '#94a3b8'}">${r.seller_voucher ? '−'+fmtRp(r.seller_voucher) : '0'}</span>
                        </div>
                        <div style="font-size:.75rem; color:var(--shp-muted); margin-top:2px;">
                            Koin: <span style="font-weight:600;color:${r.seller_coin_cash_back ? '#b91c1c' : '#94a3b8'}">${r.seller_coin_cash_back ? '−'+fmtRp(r.seller_coin_cash_back) : '0'}</span>
                        </div>
                        <div style="font-size:.75rem; color:var(--shp-muted); margin-top:2px;">
                            Pkt Diskon: <span style="font-weight:600;color:${r.seller_discount ? '#b91c1c' : '#94a3b8'}">${r.seller_discount ? '−'+fmtRp(r.seller_discount) : '0'}</span>
                        </div>
                    </td>

                    <td class="text-end">
                        <div class="fw-black" style="font-size:.9rem; color:#0369a1;">${fmtRp(r.final_income)}</div>
                        ${!r.settlement_time ? `<div style="font-size:.65rem; color:#d97706; margin-top:2px; font-weight:600;">(Estimasi)</div>` : ''}
                    </td>

                    <td class="text-end" style="font-size:.78rem;color:#b45309">${hppLabel}
                        ${r.hpp_total > 0 ? `<div style="font-size:.66rem;color:#94a3b8">COGS unit tersimpan</div>` : ''}
                    </td>

                    <td class="text-end fw-black" style="font-size:.88rem;color:${profitColor}">${fmtRp(r.profit_net)}</td>
                    
                    <td class="text-end" style="font-size:.82rem;font-weight:700;color:${marginColor}">
                        ${r.margin_pct !== null ? r.margin_pct + '%' : '—'}
                    </td>
                </tr>`;
            }).join('')}
            </tbody>
        </table>
        </div>`;

        // Pagination UI
        let linksHtml = '';
        if (paginationData && paginationData.last_page > 1) {
            linksHtml += '<div class="btn-group">';
            if (paginationData.current_page > 1) {
                linksHtml += `<button class="btn btn-sm btn-light border" onclick="goToPage(${paginationData.current_page - 1})">Prev</button>`;
            } else {
                linksHtml += `<button class="btn btn-sm btn-light border" disabled>Prev</button>`;
            }

            // Simple pagination (show +/- 2 pages)
            let start = Math.max(1, paginationData.current_page - 2);
            let end = Math.min(paginationData.last_page, paginationData.current_page + 2);
            
            for(let p = start; p <= end; p++) {
                if (p === paginationData.current_page) {
                    linksHtml += `<button class="btn btn-sm btn-primary active">${p}</button>`;
                } else {
                    linksHtml += `<button class="btn btn-sm btn-light border" onclick="goToPage(${p})">${p}</button>`;
                }
            }

            if (paginationData.current_page < paginationData.last_page) {
                linksHtml += `<button class="btn btn-sm btn-light border" onclick="goToPage(${paginationData.current_page + 1})">Next</button>`;
            } else {
                linksHtml += `<button class="btn btn-sm btn-light border" disabled>Next</button>`;
            }
            linksHtml += '</div>';
        }

        html += `
        <div style="padding:.5rem .75rem; border-top:1px solid var(--shp-border); display:flex; justify-content:space-between; align-items:center; font-size:.75rem; color:var(--shp-muted);">
            <div>Menampilkan baris ${paginationData.from || 0} - ${paginationData.to || 0} dari total ${paginationData.total || 0}</div>
            <div>${linksHtml}</div>
        </div>`;

        body.innerHTML = html;
    }

    // ── Edit Ad Cost (inline) ─────────────────────────────────────────────────
    window.editAdCost = function (idx) {
        const r   = rows[idx];
        if (!r.id) {
            alert('Order belum cair, belum ada catatan settlement untuk menyimpan biaya iklan.');
            return;
        }
        const cur = r.ad_cost || 0;
        const val = prompt(`Biaya iklan untuk order ${r.channel_order_id}:`, cur);
        if (val === null) return;
        const num = parseFloat(val);
        if (isNaN(num) || num < 0) { alert('Masukkan angka ≥ 0'); return; }

        api('/api/marketplace/settlements/' + r.id + '/ad-cost', {
            method: 'PATCH',
            body: JSON.stringify({ ad_cost: num }),
        }).then(res => {
            rows[idx].ad_cost    = res.ad_cost;
            rows[idx].profit_net = rows[idx].final_income - rows[idx].hpp_total - res.ad_cost;
            rows[idx].margin_pct = rows[idx].buyer_payment_amount > 0
                ? Math.round(rows[idx].profit_net / rows[idx].buyer_payment_amount * 1000) / 10
                : null;
            renderKpi();
            renderTable();
        }).catch(e => alert('Gagal simpan: ' + e.message));
    };

    init();
})();
</script>
@endpush
