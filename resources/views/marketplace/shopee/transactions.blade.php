@extends('layouts.app')
@section('title', 'Marketplace • Transaksi Shopee')

@include('marketplace._shared')

@push('head')
<style>
    .shopee-tx-hero {
        display: flex;
        justify-content: space-between;
        gap: 1rem;
        align-items: flex-start;
        flex-wrap: wrap;
        margin-bottom: .85rem;
        padding: 1.1rem 1.25rem;
        border-radius: 18px;
        background: linear-gradient(135deg, #ea580c, #c2410c 50%, #9a3412);
        color: #fff;
        box-shadow: 0 14px 34px rgba(234, 88, 12, .2);
    }
    .shopee-tx-hero h1 {
        font-size: 1.25rem;
        letter-spacing: -.03em;
        margin: 0 0 .3rem;
        font-weight: 850;
    }
    .shopee-tx-hero p {
        color: #ffedd5;
        margin: 0;
        font-size: .8rem;
        max-width: 48rem;
        line-height: 1.45;
    }
    .shopee-tx-live-badge {
        display: inline-flex;
        align-items: center;
        gap: .4rem;
        border: 1px solid rgba(255, 255, 255, .3);
        border-radius: 999px;
        padding: .35rem .75rem;
        font-size: .72rem;
        font-weight: 800;
        color: #fef08a;
        background: rgba(0, 0, 0, .2);
        white-space: nowrap;
    }
    .shopee-tx-live-badge i {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: #4ade80;
        box-shadow: 0 0 0 4px rgba(74, 222, 128, .2);
    }
    .shopee-tx-panel, .shopee-tx-kpi, .shopee-tx-table-wrap {
        border: 1px solid var(--shp-border);
        border-radius: 14px;
        background: var(--card);
    }
    .shopee-tx-panel {
        padding: 1rem;
        margin-bottom: .85rem;
    }
    .shopee-tx-filters-grid {
        display: grid;
        grid-template-columns: minmax(180px, 1.2fr) minmax(135px, 0.9fr) minmax(135px, 0.9fr) minmax(140px, 1fr) minmax(160px, 1.2fr);
        gap: .75rem;
        align-items: end;
    }
    .shopee-tx-field label {
        display: block;
        font-size: .68rem;
        font-weight: 850;
        text-transform: uppercase;
        letter-spacing: .04em;
        color: var(--shp-muted);
        margin-bottom: .28rem;
    }
    .shopee-tx-field .form-control, .shopee-tx-field .form-select {
        font-size: .82rem;
        border-radius: 8px;
    }

    /* Quick Date Presets Row */
    .shopee-tx-preset-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: .65rem;
        flex-wrap: wrap;
        margin-top: .75rem;
        padding-top: .75rem;
        border-top: 1px dashed var(--shp-border);
    }
    .shopee-tx-quick-presets {
        display: flex;
        align-items: center;
        gap: .3rem;
        flex-wrap: wrap;
    }
    .shopee-tx-quick-label {
        font-size: .67rem;
        font-weight: 850;
        text-transform: uppercase;
        color: var(--shp-muted);
        letter-spacing: .04em;
        margin-right: .2rem;
    }
    .shopee-tx-quick-btn {
        padding: .22rem .55rem;
        font-size: .7rem;
        border-radius: 999px;
        border: 1px solid var(--shp-border);
        background: transparent;
        color: var(--shp-muted);
        cursor: pointer;
        transition: all .15s ease-in-out;
        font-weight: 650;
        line-height: 1.2;
    }
    .shopee-tx-quick-btn:hover {
        background: rgba(234, 88, 12, .08);
        color: #ea580c;
        border-color: #fdba74;
    }
    .shopee-tx-quick-btn.active {
        background: #ea580c;
        color: #fff;
        border-color: #ea580c;
        box-shadow: 0 2px 6px rgba(234, 88, 12, .25);
    }

    /* Search Bar & Action Buttons */
    .shopee-tx-search-bar {
        display: flex;
        align-items: center;
        gap: .65rem;
        flex-wrap: wrap;
        margin-top: .75rem;
    }
    .shopee-tx-search-wrap {
        position: relative;
        flex: 1 1 280px;
    }
    .shopee-tx-search-wrap .search-icon {
        position: absolute;
        left: .75rem;
        top: 50%;
        transform: translateY(-50%);
        color: var(--shp-muted);
        font-size: .85rem;
        pointer-events: none;
    }
    .shopee-tx-search-wrap input {
        padding-left: 2.15rem;
        padding-right: 2.15rem;
        font-size: .82rem;
        border-radius: 8px;
    }
    .shopee-tx-search-clear {
        position: absolute;
        right: .55rem;
        top: 50%;
        transform: translateY(-50%);
        border: 0;
        background: transparent;
        color: var(--shp-muted);
        font-size: 1.1rem;
        line-height: 1;
        cursor: pointer;
        padding: 0 .25rem;
        display: none;
    }
    .shopee-tx-search-clear.show {
        display: block;
    }
    .shopee-tx-actions-group {
        display: flex;
        gap: .45rem;
        align-items: center;
        flex-wrap: wrap;
    }

    .shopee-tx-help {
        display: flex;
        align-items: center;
        gap: .4rem;
        color: var(--shp-muted);
        font-size: .73rem;
        margin-top: .65rem;
    }

    /* Type Breakdown Pills */
    .shopee-tx-breakdown {
        display: flex;
        align-items: center;
        gap: .5rem;
        flex-wrap: wrap;
        margin-bottom: .85rem;
        padding: .5rem .75rem;
        background: var(--card);
        border: 1px solid var(--shp-border);
        border-radius: 10px;
    }
    .shopee-tx-breakdown-label {
        font-size: .68rem;
        font-weight: 800;
        text-transform: uppercase;
        color: var(--shp-muted);
        letter-spacing: .04em;
    }
    .shopee-tx-breakdown-pills {
        display: flex;
        align-items: center;
        gap: .35rem;
        flex-wrap: wrap;
    }
    .shopee-tx-pill {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        padding: .2rem .55rem;
        border-radius: 999px;
        font-size: .7rem;
        font-weight: 700;
        cursor: pointer;
        border: 1px solid transparent;
        background: transparent;
        transition: all .15s;
    }
    .shopee-tx-pill:hover {
        transform: translateY(-1px);
        box-shadow: 0 2px 5px rgba(0,0,0,.08);
    }
    .shopee-tx-pill.active {
        box-shadow: 0 0 0 2px #ea580c;
    }
    .shopee-tx-kpis {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: .75rem;
        margin-bottom: .85rem;
    }
    .shopee-tx-kpi {
        padding: .85rem 1rem;
        position: relative;
        overflow: hidden;
    }
    .shopee-tx-kpi-label {
        color: var(--shp-muted);
        font-size: .67rem;
        text-transform: uppercase;
        letter-spacing: .05em;
        font-weight: 850;
    }
    .shopee-tx-kpi-value {
        font-size: 1.15rem;
        line-height: 1.25;
        font-weight: 900;
        color: var(--shp-text);
        margin-top: .25rem;
    }
    .shopee-tx-kpi-note {
        font-size: .7rem;
        color: var(--shp-muted);
        margin-top: .2rem;
    }
    .shopee-tx-kpi.in .shopee-tx-kpi-value { color: #16a34a; }
    .shopee-tx-kpi.out .shopee-tx-kpi-value { color: #dc2626; }
    .shopee-tx-kpi.net .shopee-tx-kpi-value { color: #2563eb; }

    .shopee-tx-table-wrap {
        overflow: auto;
        border-bottom-left-radius: 0;
        border-bottom-right-radius: 0;
    }
    .shopee-tx-table {
        margin: 0;
        width: 100%;
        min-width: 980px;
    }
    .shopee-tx-table th {
        font-size: .68rem;
        text-transform: uppercase;
        letter-spacing: .04em;
        color: var(--shp-muted);
        white-space: nowrap;
        background: rgba(148, 163, 184, .06);
        padding: .75rem .85rem;
        border-bottom: 1px solid var(--shp-border);
    }
    .shopee-tx-table td {
        font-size: .78rem;
        vertical-align: middle;
        padding: .65rem .85rem;
        border-top: 1px solid var(--shp-border);
        white-space: nowrap;
    }
    .tx-code {
        font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
        font-size: .74rem;
        font-weight: 750;
        color: var(--shp-text);
    }
    .tx-money-in {
        font-weight: 850;
        color: #16a34a;
        white-space: nowrap;
    }
    .tx-money-out {
        font-weight: 850;
        color: #dc2626;
        white-space: nowrap;
    }
    .tx-badge {
        display: inline-block;
        padding: .2rem .5rem;
        font-size: .67rem;
        font-weight: 750;
        border-radius: 6px;
        white-space: nowrap;
    }
    .tx-badge.success { background: #dcfce7; color: #166534; }
    .tx-badge.info { background: #e0f2fe; color: #075985; }
    .tx-badge.warning { background: #fef3c7; color: #92400e; }
    .tx-badge.danger { background: #fee2e2; color: #991b1b; }
    .tx-badge.secondary { background: #f1f5f9; color: #475569; }
    .tx-badge.primary { background: #ede9fe; color: #5b21b6; }

    .shopee-tx-empty {
        text-align: center;
        padding: 3rem 1rem;
        color: var(--shp-muted);
        font-size: .84rem;
    }
    .shopee-tx-alert {
        display: none;
        border-radius: 10px;
        padding: .75rem 1rem;
        font-size: .8rem;
        margin-bottom: .85rem;
    }
    .shopee-tx-alert.show { display: block; }
    .shopee-tx-alert.error {
        background: #fef2f2;
        border: 1px solid #fecaca;
        color: #991b1b;
    }
    .shopee-tx-alert.info {
        background: #eff6ff;
        border: 1px solid #bfdbfe;
        color: #1e40af;
    }
    .shopee-tx-spinner {
        display: inline-block;
        width: 14px;
        height: 14px;
        border: 2px solid currentColor;
        border-right-color: transparent;
        border-radius: 50%;
        animation: shopee-tx-spin .65s linear infinite;
        vertical-align: -2px;
    }
    @keyframes shopee-tx-spin { to { transform: rotate(360deg); } }

    .shopee-tx-actions {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: .65rem;
        padding: .75rem 1rem;
        border: 1px solid var(--shp-border);
        border-top: 0;
        border-radius: 0 0 14px 14px;
        background: var(--card);
    }
    .shopee-tx-page-info {
        font-size: .74rem;
        color: var(--shp-muted);
    }

    /* Modal Backdrop & Body */
    .shopee-tx-modal-backdrop {
        display: none;
        position: fixed;
        inset: 0;
        z-index: 1050;
        background: rgba(15, 23, 42, .65);
        padding: 1.2rem;
        overflow: auto;
    }
    .shopee-tx-modal-backdrop.show {
        display: flex;
        align-items: flex-start;
        justify-content: center;
    }
    .shopee-tx-modal {
        width: min(920px, 100%);
        margin: 3vh auto;
        background: var(--card, #fff);
        border-radius: 18px;
        box-shadow: 0 25px 80px rgba(15, 23, 42, .35);
        overflow: hidden;
        border: 1px solid var(--shp-border);
    }
    .shopee-tx-modal-head {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 1rem;
        padding: 1rem 1.25rem;
        border-bottom: 1px solid var(--shp-border);
        background: rgba(234, 88, 12, .04);
    }
    .shopee-tx-modal-title {
        font-weight: 900;
        color: var(--shp-text);
        font-size: 1rem;
    }
    .shopee-tx-modal-sub {
        font-size: .72rem;
        color: var(--shp-muted);
        margin-top: .2rem;
    }
    .shopee-tx-close {
        border: 0;
        background: transparent;
        color: var(--shp-muted);
        font-size: 1.35rem;
        cursor: pointer;
        line-height: 1;
    }
    .shopee-tx-detail-body {
        padding: 1.15rem;
    }
    .shopee-tx-detail-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: .65rem;
        margin-bottom: 1.1rem;
    }
    .shopee-tx-detail-card {
        border: 1px solid var(--shp-border);
        border-radius: 10px;
        padding: .65rem .85rem;
        background: rgba(148, 163, 184, .03);
    }
    .shopee-tx-detail-label {
        font-size: .64rem;
        text-transform: uppercase;
        letter-spacing: .04em;
        color: var(--shp-muted);
        font-weight: 850;
    }
    .shopee-tx-detail-value {
        font-size: .82rem;
        font-weight: 800;
        color: var(--shp-text);
        margin-top: .25rem;
        word-break: break-word;
    }
    .shopee-tx-raw {
        max-height: 320px;
        overflow: auto;
        background: #0f172a;
        color: #cbd5e1;
        padding: .85rem;
        border-radius: 10px;
        font-size: .7rem;
        line-height: 1.45;
        font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
        margin: 0;
    }

    @media (max-width: 992px) {
        .shopee-tx-filters-grid {
            grid-template-columns: 1fr 1fr;
        }
        .shopee-tx-kpis {
            grid-template-columns: 1fr 1fr;
        }
        .shopee-tx-detail-grid {
            grid-template-columns: 1fr;
        }
    }
</style>
@endpush

@section('content')
<div class="page-wrap">
    {{-- Header Banner --}}
    <div class="shopee-tx-hero">
        <div>
            <h1>Transaksi Dompet Shopee (Mutasi Saldo)</h1>
            <p>
                Penarikan riwayat mutasi saldo penjual langsung secara live dari Shopee Open Platform v2
                (<code>v2.payment.get_wallet_transaction_list</code>) tanpa menyentuh atau menulis ke database.
            </p>
        </div>
        <div class="shopee-tx-live-badge">
            <i></i> Live Shopee API · Tanpa Database
        </div>
    </div>

    {{-- Error / Info Alert --}}
    <div id="txAlert" class="shopee-tx-alert" role="alert"></div>

    {{-- Filter Panel --}}
    <div class="shopee-tx-panel">
        {{-- Row 1: Primary Dropdown & Date Filters --}}
        <div class="shopee-tx-filters-grid">
            <div class="shopee-tx-field">
                <label for="txStore">Toko Shopee</label>
                <select id="txStore" class="form-select">
                    @if($stores->isEmpty())
                        <option value="">Tidak ada toko Shopee terhubung</option>
                    @else
                        @foreach($stores as $s)
                            <option value="{{ $s->id }}">{{ $s->name }} · Shopee</option>
                        @endforeach
                    @endif
                </select>
            </div>
            <div class="shopee-tx-field">
                <label for="txFrom">Tanggal dari</label>
                <input id="txFrom" type="date" class="form-control">
            </div>
            <div class="shopee-tx-field">
                <label for="txTo">Tanggal sampai</label>
                <input id="txTo" type="date" class="form-control">
            </div>
            <div class="shopee-tx-field">
                <label for="txFlow">Arah Dana</label>
                <select id="txFlow" class="form-select">
                    <option value="ALL">Semua Arah Dana</option>
                    <option value="MONEY_IN">Dana Masuk (+)</option>
                    <option value="MONEY_OUT">Dana Keluar (-)</option>
                </select>
            </div>
            <div class="shopee-tx-field">
                <label for="txType">Tipe Transaksi</label>
                <select id="txType" class="form-select">
                    <option value="">Semua Tipe Transaksi</option>
                    @foreach($transactionTypes as $t)
                        <option value="{{ $t['code'] }}">{{ $t['label'] }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        {{-- Row 2: Quick Date Presets --}}
        <div class="shopee-tx-preset-row">
            <div class="shopee-tx-quick-presets">
                <span class="shopee-tx-quick-label">Rentang Cepat:</span>
                <button type="button" class="shopee-tx-quick-btn" data-preset="today">Hari ini</button>
                <button type="button" class="shopee-tx-quick-btn" data-preset="yesterday">Kemarin</button>
                <button type="button" class="shopee-tx-quick-btn" data-preset="7days">7 Hari</button>
                <button type="button" class="shopee-tx-quick-btn active" data-preset="15days">15 Hari</button>
                <button type="button" class="shopee-tx-quick-btn" data-preset="30days">30 Hari</button>
                <button type="button" class="shopee-tx-quick-btn" data-preset="thisMonth">Bulan Ini</button>
                <button type="button" class="shopee-tx-quick-btn" data-preset="lastMonth">Bulan Lalu</button>
            </div>
        </div>

        {{-- Row 3: Search Bar & Actions --}}
        <div class="shopee-tx-search-bar">
            <div class="shopee-tx-search-wrap">
                <i class="bi bi-search search-icon"></i>
                <input id="txSearch" type="search" class="form-control" placeholder="Cari No. Pesanan / ID Transaksi / Keterangan / Pembeli..." autocomplete="off">
                <button id="txSearchClear" type="button" class="shopee-tx-search-clear" title="Hapus pencarian">×</button>
            </div>
            <div class="shopee-tx-actions-group">
                <button id="txResetBtn" type="button" class="btn btn-outline-secondary btn-pill" title="Reset filter ke kondisi awal">
                    <i class="bi bi-arrow-counterclockwise me-1"></i> Reset
                </button>
                <button id="txLoadBtn" type="button" class="btn btn-warning text-white fw-bold btn-pill" style="background:#ea580c; border-color:#ea580c;">
                    <span id="txLoadLabel"><i class="bi bi-cloud-arrow-down me-1"></i> Tarik Data</span>
                </button>
                <button id="txExportBtn" type="button" class="btn btn-outline-secondary btn-pill" title="Unduh CSV">
                    <i class="bi bi-download me-1"></i> CSV
                </button>
            </div>
        </div>

        <div class="shopee-tx-help">
            <i class="bi bi-info-circle-fill text-warning"></i>
            <span>Rentang waktu maksimal 60 hari. Shopee membatasi 15 hari per pemanggilan, sehingga rentang di atas 15 hari otomatis dipecah secara bertahap oleh sistem.</span>
        </div>
    </div>

    {{-- KPI Cards --}}
    <div class="shopee-tx-kpis">
        <div class="shopee-tx-kpi">
            <div class="shopee-tx-kpi-label">Total Transaksi</div>
            <div id="kpiCount" class="shopee-tx-kpi-value">—</div>
            <div class="shopee-tx-kpi-note">jumlah baris pada halaman</div>
        </div>
        <div class="shopee-tx-kpi in">
            <div class="shopee-tx-kpi-label">Total Dana Masuk (+)</div>
            <div id="kpiIn" class="shopee-tx-kpi-value">—</div>
            <div class="shopee-tx-kpi-note">pelepasan escrow & refund</div>
        </div>
        <div class="shopee-tx-kpi out">
            <div class="shopee-tx-kpi-label">Total Dana Keluar (-)</div>
            <div id="kpiOut" class="shopee-tx-kpi-value">—</div>
            <div class="shopee-tx-kpi-note">penarikan bank & biaya iklan</div>
        </div>
        <div class="shopee-tx-kpi net">
            <div class="shopee-tx-kpi-label">Perubahan Bersih</div>
            <div id="kpiNet" class="shopee-tx-kpi-value">—</div>
            <div class="shopee-tx-kpi-note">selisih masuk dikurangi keluar</div>
        </div>
    </div>

    {{-- Quick Type Breakdown Pills --}}
    <div id="txBreakdownWrap" class="shopee-tx-breakdown" style="display:none;">
        <span class="shopee-tx-breakdown-label">Filter Tipe:</span>
        <div id="txBreakdownPills" class="shopee-tx-breakdown-pills"></div>
    </div>

    {{-- Transactions Table --}}
    <div id="txTableWrap" class="shopee-tx-table-wrap">
        <div class="shopee-tx-empty">
            <i class="bi bi-wallet2 fs-2 d-block mb-2 text-muted"></i>
            Pilih toko Shopee dan rentang tanggal, lalu klik <strong>Tarik Data</strong> untuk mengambil mutasi dompet secara live.
        </div>
    </div>

    {{-- Pagination Bar --}}
    <div id="txPagination" class="shopee-tx-actions" style="display:none">
        <button id="txPrev" type="button" class="btn btn-sm btn-outline-secondary btn-pill">
            ← Sebelumnya
        </button>
        <span id="txPageInfo" class="shopee-tx-page-info"></span>
        <button id="txNext" type="button" class="btn btn-sm btn-outline-secondary btn-pill">
            Berikutnya →
        </button>
    </div>
</div>

{{-- Detail Modal --}}
<div id="txModalBackdrop" class="shopee-tx-modal-backdrop" role="dialog" aria-modal="true" aria-labelledby="txModalTitle">
    <div class="shopee-tx-modal">
        <div class="shopee-tx-modal-head">
            <div>
                <div id="txModalTitle" class="shopee-tx-modal-title">Rincian Transaksi Wallet</div>
                <div id="txModalSub" class="shopee-tx-modal-sub">Informasi detail dan raw response dari Shopee</div>
            </div>
            <button id="txModalClose" type="button" class="shopee-tx-close" aria-label="Tutup">×</button>
        </div>
        <div id="txModalBody" class="shopee-tx-detail-body">
            {{-- Injected dynamically --}}
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(() => {
    const $ = (id) => document.getElementById(id);
    const PAGE_SIZE = 100;

    const state = {
        pageNo: 0,
        pageSize: PAGE_SIZE,
        more: false,
        nextOffset: 0,
        items: [],
        loading: false,
    };

    // Safe local date formatting (Avoids UTC shift issues in GMT+7 WIB)
    const pad = (n) => String(n).padStart(2, '0');
    const formatDate = (d) => `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;

    // Apply quick date presets
    const setDateRange = (from, to, presetName = null) => {
        $('txFrom').value = formatDate(from);
        $('txTo').value = formatDate(to);

        document.querySelectorAll('.shopee-tx-quick-btn').forEach(btn => {
            if (presetName && btn.dataset.preset === presetName) {
                btn.classList.add('active');
            } else {
                btn.classList.remove('active');
            }
        });
    };

    const applyPreset = (presetName) => {
        const now = new Date();
        const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());

        if (presetName === 'today') {
            setDateRange(today, today, 'today');
        } else if (presetName === 'yesterday') {
            const yest = new Date(today);
            yest.setDate(yest.getDate() - 1);
            setDateRange(yest, yest, 'yesterday');
        } else if (presetName === '7days') {
            const from = new Date(today);
            from.setDate(from.getDate() - 6);
            setDateRange(from, today, '7days');
        } else if (presetName === '15days') {
            const from = new Date(today);
            from.setDate(from.getDate() - 14);
            setDateRange(from, today, '15days');
        } else if (presetName === '30days') {
            const from = new Date(today);
            from.setDate(from.getDate() - 29);
            setDateRange(from, today, '30days');
        } else if (presetName === 'thisMonth') {
            const from = new Date(today.getFullYear(), today.getMonth(), 1);
            setDateRange(from, today, 'thisMonth');
        } else if (presetName === 'lastMonth') {
            const from = new Date(today.getFullYear(), today.getMonth() - 1, 1);
            const to = new Date(today.getFullYear(), today.getMonth(), 0);
            setDateRange(from, to, 'lastMonth');
        }
    };

    // Initialize default date range: 15 days
    applyPreset('15days');

    // Formatters
    const moneyFormat = (val) => {
        if (val === null || val === undefined || isNaN(val)) return '—';
        return 'Rp ' + Math.round(Number(val)).toLocaleString('id-ID');
    };

    const escHtml = (str) => {
        return String(str ?? '').replace(/[&<>'"]/g, (c) => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#039;', '"': '&quot;'
        }[c]));
    };

    const showAlert = (msg, type = 'error') => {
        $('txAlert').textContent = msg;
        $('txAlert').className = `shopee-tx-alert show ${type}`;
    };

    const hideAlert = () => {
        $('txAlert').className = 'shopee-tx-alert';
    };

    const buildUrl = (exportMode = false) => {
        const storeId = $('txStore').value;
        if (!storeId) return null;

        const path = exportMode
            ? `/api/marketplace/stores/${encodeURIComponent(storeId)}/shopee-transactions/export`
            : `/api/marketplace/stores/${encodeURIComponent(storeId)}/shopee-transactions`;

        const params = new URLSearchParams({
            date_from: $('txFrom').value,
            date_to: $('txTo').value,
            page_no: state.pageNo,
            page_size: state.pageSize,
        });

        const flow = $('txFlow').value;
        if (flow && flow !== 'ALL') params.set('money_flow', flow);

        const type = $('txType').value;
        if (type) params.set('transaction_type', type);

        const search = $('txSearch')?.value?.trim();
        if (search) params.set('search', search);

        return `${path}?${params.toString()}`;
    };

    const validateInputs = () => {
        if (!$('txStore').value) {
            showAlert('Pilih toko Shopee terlebih dahulu.');
            return false;
        }
        if (!$('txFrom').value || !$('txTo').value) {
            showAlert('Tanggal mulai dan selesai wajib diisi.');
            return false;
        }
        if ($('txFrom').value > $('txTo').value) {
            showAlert('Tanggal mulai tidak boleh melewati tanggal akhir.');
            return false;
        }

        const dFrom = new Date(`${$('txFrom').value}T00:00:00`);
        const dTo = new Date(`${$('txTo').value}T00:00:00`);
        const diffDays = Math.round((dTo - dFrom) / 86400000) + 1;

        if (diffDays > 60) {
            showAlert(`Rentang tanggal yang dipilih ${diffDays} hari. Batas maksimal penarikan live Shopee adalah 60 hari.`);
            return false;
        }

        return true;
    };

    const renderBreakdownPills = (breakdowns) => {
        const wrap = $('txBreakdownWrap');
        const container = $('txBreakdownPills');
        if (!breakdowns || !breakdowns.length) {
            wrap.style.display = 'none';
            container.innerHTML = '';
            return;
        }

        wrap.style.display = 'flex';
        const currentType = $('txType').value;

        const pillsHtml = breakdowns.map(b => {
            const isActive = currentType && (currentType.toLowerCase() === b.type.toLowerCase() || currentType === b.type);
            return `
                <button type="button" class="shopee-tx-pill tx-badge ${b.badge || 'secondary'} ${isActive ? 'active' : ''}" data-type="${escHtml(b.type)}">
                    <span>${escHtml(b.label)}</span>
                    <span class="badge bg-dark-subtle text-dark ms-1">${b.count}</span>
                </button>
            `;
        }).join('');

        container.innerHTML = pillsHtml;

        container.querySelectorAll('.shopee-tx-pill').forEach(pill => {
            pill.addEventListener('click', () => {
                const type = pill.dataset.type;
                if ($('txType').value === type) {
                    $('txType').value = '';
                } else {
                    $('txType').value = type;
                }
                state.pageNo = 0;
                loadData();
            });
        });
    };

    const renderTable = (items) => {
        if (!items.length) {
            const keyword = $('txSearch').value.trim();
            $('txTableWrap').innerHTML = `
                <div class="shopee-tx-empty">
                    <i class="bi bi-${keyword ? 'search' : 'inbox'} fs-2 d-block mb-2 text-muted"></i>
                    ${keyword
                        ? `Tidak ada transaksi yang cocok dengan kata kunci "<strong>${escHtml(keyword)}</strong>".`
                        : 'Tidak ada transaksi wallet pada rentang tanggal dan filter yang dipilih.'}
                    <div class="mt-2">
                        <button type="button" class="btn btn-sm btn-outline-secondary btn-pill" id="emptyResetBtn">
                            <i class="bi bi-arrow-counterclockwise me-1"></i> Reset Filter
                        </button>
                    </div>
                </div>
            `;
            const emptyReset = $('emptyResetBtn');
            if (emptyReset) {
                emptyReset.addEventListener('click', resetFilters);
            }
            return;
        }

        const rowsHtml = items.map((item, idx) => {
            const isMoneyIn = item.money_flow === 'MONEY_IN';
            const moneyClass = isMoneyIn ? 'tx-money-in' : 'tx-money-out';
            const signPrefix = isMoneyIn ? '+ ' : '- ';
            const badgeClass = item.badge || (isMoneyIn ? 'success' : 'secondary');

            return `
                <tr>
                    <td class="text-muted">${escHtml(item.date_formatted)}</td>
                    <td>
                        <span class="tx-code">${escHtml(item.transaction_id || '—')}</span>
                        <button type="button" class="btn btn-link btn-sm p-0 text-muted ms-1 copy-btn" data-text="${escHtml(item.transaction_id || '')}" title="Salin ID Transaksi">
                            <i class="bi bi-clipboard"></i>
                        </button>
                    </td>
                    <td>
                        <span class="tx-badge ${badgeClass}">
                            ${escHtml(item.transaction_type_label || item.transaction_type)}
                        </span>
                    </td>
                    <td>
                        <span class="badge ${isMoneyIn ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger'} fw-bold">
                            ${isMoneyIn ? '<i class="bi bi-arrow-down-left"></i> Masuk' : '<i class="bi bi-arrow-up-right"></i> Keluar'}
                        </span>
                    </td>
                    <td class="${moneyClass}">
                        ${signPrefix}${escHtml(item.formatted_amount)}
                    </td>
                    <td class="fw-semibold text-secondary">
                        ${escHtml(item.formatted_balance || '—')}
                    </td>
                    <td>
                        ${item.order_sn ? `
                            <div class="d-flex align-items-center gap-1">
                                <span class="tx-code text-primary">${escHtml(item.order_sn)}</span>
                                <button type="button" class="btn btn-link btn-sm p-0 text-muted copy-btn" data-text="${escHtml(item.order_sn)}" title="Salin Order SN">
                                    <i class="bi bi-clipboard"></i>
                                </button>
                            </div>
                        ` : '<span class="text-muted">—</span>'}
                        ${item.buyer_name ? `
                            <div class="small text-muted mt-1"><i class="bi bi-person me-1"></i>${escHtml(item.buyer_name)}</div>
                        ` : ''}
                    </td>
                    <td class="text-muted" style="max-width:280px; overflow:hidden; text-overflow:ellipsis;" title="${escHtml(item.reason || '')}">
                        ${escHtml(item.reason || '—')}
                    </td>
                    <td class="text-end">
                        <button type="button" class="btn btn-sm btn-outline-secondary btn-pill view-detail-btn" data-index="${idx}">
                            <i class="bi bi-code-slash me-1"></i> Detail
                        </button>
                    </td>
                </tr>
            `;
        }).join('');

        $('txTableWrap').innerHTML = `
            <table class="table shopee-tx-table">
                <thead>
                    <tr>
                        <th>Waktu</th>
                        <th>ID Transaksi</th>
                        <th>Tipe Transaksi</th>
                        <th>Arah Dana</th>
                        <th>Nominal</th>
                        <th>Saldo Berjalan</th>
                        <th>No. Pesanan / Pembeli</th>
                        <th>Keterangan / Deskripsi</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    ${rowsHtml}
                </tbody>
            </table>
        `;

        // Event listener for detail modal buttons
        document.querySelectorAll('.view-detail-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const item = state.items[parseInt(btn.dataset.index, 10)];
                openDetailModal(item);
            });
        });

        // Event listener for copy buttons
        document.querySelectorAll('.copy-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                navigator.clipboard.writeText(btn.dataset.text);
                btn.innerHTML = '<i class="bi bi-check text-success"></i>';
                setTimeout(() => { btn.innerHTML = '<i class="bi bi-clipboard"></i>'; }, 1500);
            });
        });
    };

    const renderPagination = () => {
        $('txPagination').style.display = 'flex';
        $('txPrev').disabled = state.pageNo <= 0;
        $('txNext').disabled = !state.more;

        const currentOffset = state.pageNo;
        const count = state.items.length;
        $('txPageInfo').textContent = `Offset data: ${currentOffset} · Menampilkan ${count} item${state.more ? ' · Ada data berikutnya' : ' · Selesai'}`;
    };

    const loadData = async () => {
        hideAlert();
        if (!validateInputs()) return;

        const url = buildUrl();
        if (!url) return;

        state.loading = true;
        $('txLoadBtn').disabled = true;
        $('txLoadLabel').innerHTML = '<span class="shopee-tx-spinner me-1"></span> Mengambil…';
        $('txTableWrap').innerHTML = `
            <div class="shopee-tx-empty">
                <span class="shopee-tx-spinner me-2"></span> Mengambil mutasi wallet live dari Shopee API…
            </div>
        `;

        try {
            const res = await fetch(url, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                }
            });

            const json = await res.json().catch(() => ({}));

            if (!res.ok || json.success === false) {
                throw new Error(json.message || `Gagal mengambil data (${res.status})`);
            }

            const data = json.data || {};
            state.items = data.items || [];
            state.more = Boolean(data.pagination?.more);
            state.nextOffset = data.pagination?.next_offset || 0;

            // Render KPIs
            const summary = data.summary || {};
            $('kpiCount').textContent = (summary.total_count || 0).toLocaleString('id-ID');
            $('kpiIn').textContent = moneyFormat(summary.total_money_in);
            $('kpiOut').textContent = moneyFormat(summary.total_money_out);
            $('kpiNet').textContent = (summary.net_amount < 0 ? '- ' : '+ ') + moneyFormat(Math.abs(summary.net_amount || 0));

            // Render breakdown pills
            renderBreakdownPills(summary.type_breakdown || []);

            renderTable(state.items);
            renderPagination();
        } catch (err) {
            showAlert(err.message);
            $('txTableWrap').innerHTML = `
                <div class="shopee-tx-empty text-danger">
                    <i class="bi bi-exclamation-triangle fs-2 d-block mb-2"></i>
                    ${escHtml(err.message)}
                </div>
            `;
            $('txPagination').style.display = 'none';
        } finally {
            state.loading = false;
            $('txLoadBtn').disabled = false;
            $('txLoadLabel').innerHTML = '<i class="bi bi-cloud-arrow-down me-1"></i> Tarik Data';
        }
    };

    const openDetailModal = (item) => {
        if (!item) return;

        const fields = [
            ['ID Transaksi', item.transaction_id || '—'],
            ['Toko', item.store_name || '—'],
            ['Waktu Transaksi', item.date_formatted || '—'],
            ['Tipe Transaksi', `${item.transaction_type_label} (${item.transaction_type})`],
            ['Arah Dana', item.money_flow === 'MONEY_IN' ? 'Dana Masuk (+)' : 'Dana Keluar (-)'],
            ['Nominal', item.signed_formatted_amount || item.formatted_amount],
            ['Saldo Akhir Shopee', item.formatted_balance || '—'],
            ['No. Pesanan (Order SN)', item.order_sn || '—'],
            ['Nama Pembeli', item.buyer_name || '—'],
            ['Status Transaksi', item.status || '—'],
            ['Tipe Wallet', item.wallet_type || '—'],
            ['Alasan / Deskripsi', item.reason || '—'],
        ];

        const cardsHtml = fields.map(([label, val]) => `
            <div class="shopee-tx-detail-card">
                <div class="shopee-tx-detail-label">${escHtml(label)}</div>
                <div class="shopee-tx-detail-value">${escHtml(val)}</div>
            </div>
        `).join('');

        $('txModalTitle').textContent = `Transaksi #${item.transaction_id || '—'}`;
        $('txModalSub').textContent = `${item.date_formatted} · ${item.transaction_type_label}`;
        $('txModalBody').innerHTML = `
            <div class="shopee-tx-detail-grid">
                ${cardsHtml}
            </div>
            <div class="mt-3">
                <h6 class="fw-bold mb-2 text-secondary" style="font-size:.78rem;">
                    <i class="bi bi-filetype-json me-1"></i> Raw Response Shopee API (JSON)
                </h6>
                <pre class="shopee-tx-raw">${escHtml(JSON.stringify(item.raw_payload || item, null, 2))}</pre>
            </div>
        `;

        $('txModalBackdrop').classList.add('show');
    };

    // Close modal handlers
    $('txModalClose').addEventListener('click', () => $('txModalBackdrop').classList.remove('show'));
    $('txModalBackdrop').addEventListener('click', (e) => {
        if (e.target === $('txModalBackdrop')) $('txModalBackdrop').classList.remove('show');
    });
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') $('txModalBackdrop').classList.remove('show');
    });

    // Reset all filters
    const resetFilters = () => {
        $('txFlow').value = 'ALL';
        $('txType').value = '';
        $('txSearch').value = '';
        $('txSearchClear').classList.remove('show');
        applyPreset('15days');
        state.pageNo = 0;
        loadData();
    };

    $('txResetBtn').addEventListener('click', resetFilters);

    // Search input handler with debounce
    let searchDebounce = null;
    $('txSearch').addEventListener('input', (e) => {
        const val = e.target.value.trim();
        if (val.length > 0) {
            $('txSearchClear').classList.add('show');
        } else {
            $('txSearchClear').classList.remove('show');
        }

        clearTimeout(searchDebounce);
        searchDebounce = setTimeout(() => {
            state.pageNo = 0;
            loadData();
        }, 350);
    });

    $('txSearchClear').addEventListener('click', () => {
        $('txSearch').value = '';
        $('txSearchClear').classList.remove('show');
        $('txSearch').focus();
        state.pageNo = 0;
        loadData();
    });

    // Quick date preset button clicks
    document.querySelectorAll('.shopee-tx-quick-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            applyPreset(btn.dataset.preset);
            state.pageNo = 0;
            loadData();
        });
    });

    // Manual date changes
    const onManualDateChange = () => {
        document.querySelectorAll('.shopee-tx-quick-btn').forEach(b => b.classList.remove('active'));
        state.pageNo = 0;
        loadData();
    };
    $('txFrom').addEventListener('change', onManualDateChange);
    $('txTo').addEventListener('change', onManualDateChange);

    // Reactive filter dropdowns
    $('txStore').addEventListener('change', () => {
        state.pageNo = 0;
        if ($('txStore').value) loadData();
    });
    $('txFlow').addEventListener('change', () => {
        state.pageNo = 0;
        loadData();
    });
    $('txType').addEventListener('change', () => {
        state.pageNo = 0;
        loadData();
    });

    // Manual Load Button
    $('txLoadBtn').addEventListener('click', () => {
        state.pageNo = 0;
        loadData();
    });

    // Pagination buttons
    $('txPrev').addEventListener('click', () => {
        if (state.pageNo > 0) {
            state.pageNo = Math.max(0, state.pageNo - PAGE_SIZE);
            loadData();
        }
    });

    $('txNext').addEventListener('click', () => {
        if (state.more) {
            state.pageNo = state.nextOffset;
            loadData();
        }
    });

    // Export CSV
    $('txExportBtn').addEventListener('click', () => {
        if (!validateInputs()) return;
        const url = buildUrl(true);
        if (url) {
            window.location.href = url;
        }
    });

    // Auto-load on initial page render if store is selected
    if ($('txStore').value) {
        loadData();
    }
})();
</script>
@endpush
