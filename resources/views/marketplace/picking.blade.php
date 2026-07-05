@extends('layouts.app')
@section('title', 'Marketplace • Picking')

@include('marketplace._shared')

@push('head')
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
<style>
/* ── Layout ─────────────────────────────────────────────────────────── */
.pk-wrap    { max-width: 900px; margin: 0 auto; padding: .75rem 1rem 3rem; }
.pk-header  { display: flex; align-items: center; justify-content: space-between; margin-bottom: .85rem; flex-wrap: wrap; gap: .5rem; }
.pk-title   { font-size: 1.1rem; font-weight: 900; color: #0f172a; letter-spacing: -.02em; }
.pk-subtitle{ font-size: .75rem; color: #64748b; margin-top: .1rem; }

/* ── Workflow banner ────────────────────────────────────────────────── */
.pk-flow-banner {
    display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between;
    gap: .65rem; padding: .7rem 1rem; border-radius: 14px; margin-bottom: 1rem;
    background: linear-gradient(135deg,#0f172a 0%,#1e293b 100%); color: #fff;
}
.pk-flow-title { font-size: .78rem; font-weight: 900; letter-spacing: -.01em; }
.pk-flow-sub   { font-size: .68rem; color: #94a3b8; margin-top: .1rem; }
.pk-flow-steps { display: flex; flex-wrap: wrap; gap: .35rem; }
.pk-step {
    font-size: .68rem; font-weight: 800; padding: .22rem .7rem; border-radius: 999px;
    border: 1.5px solid rgba(255,255,255,.18); color: rgba(255,255,255,.55); background: transparent;
    transition: all .15s; cursor: pointer; white-space: nowrap;
}
.pk-step.active-step { background: #fff; color: #0f172a; border-color: #fff; }
.pk-step.done-step   { background: rgba(34,197,94,.25); color: #86efac; border-color: rgba(34,197,94,.4); }

/* ── KPI grid ───────────────────────────────────────────────────────── */
.pk-kpi-grid { display: grid; grid-template-columns: repeat(4, minmax(0,1fr)); gap: .55rem; margin-bottom: 1rem; }
@media(max-width:540px){ .pk-kpi-grid { grid-template-columns: repeat(2,1fr); } }
.pk-kpi {
    border: 1px solid rgba(15,23,42,.07); border-radius: 14px;
    padding: .7rem .85rem; background: #fff;
}
.pk-kpi-label { font-size: .62rem; font-weight: 950; text-transform: uppercase; letter-spacing: .05em; color: #94a3b8; margin-bottom: .12rem; }
.pk-kpi-value { font-size: 1.35rem; font-weight: 950; letter-spacing: -.03em; color: #0f172a; }
.pk-kpi-note  { font-size: .65rem; color: #94a3b8; margin-top: .1rem; }
.pk-kpi.locked  { border-left: 3px solid #22c55e; }
.pk-kpi.locked  .pk-kpi-value { color: #16a34a; }
.pk-kpi.hold    { border-left: 3px solid #f59e0b; }
.pk-kpi.hold    .pk-kpi-value { color: #b45309; }

/* ── Tabs ───────────────────────────────────────────────────────────── */
.pk-tabs { display: flex; gap: .3rem; flex-wrap: wrap; margin-bottom: 1rem; }
.pk-tab  {
    font-size: .75rem; font-weight: 700; padding: .3rem .85rem;
    border-radius: 999px; border: 1.5px solid #e2e8f0;
    background: #f8fafc; color: #475569; cursor: pointer;
    transition: all .12s; display: inline-flex; align-items: center; gap: .35rem;
}
.pk-tab.active, .pk-tab:hover { background: #0f172a; color: #fff; border-color: #0f172a; }
.pk-badge {
    font-size: .65rem; font-weight: 900; padding: .05rem .42rem;
    border-radius: 999px; background: rgba(255,255,255,.22);
}
.pk-tab:not(.active) .pk-badge { background: #e2e8f0; color: #64748b; }

/* ── Panels ─────────────────────────────────────────────────────────── */
.pk-panel { display: none; }
.pk-panel.active { display: block; }

/* ── Scan input row ─────────────────────────────────────────────────── */
.pk-scan-row {
    display: flex; gap: .5rem; margin-bottom: .85rem; align-items: center; flex-wrap: wrap;
}
.pk-scan-input {
    flex: 1; min-width: 180px; border: 2px solid #e2e8f0; border-radius: 10px;
    padding: .45rem .8rem; font-size: .85rem; transition: border-color .15s;
}
.pk-scan-input:focus { border-color: #0f172a; outline: none; }
.pk-scan-hint { font-size: .7rem; color: #94a3b8; margin-bottom: .75rem; }

/* ── Toolbar ────────────────────────────────────────────────────────── */
.pk-toolbar {
    display: flex; align-items: center; justify-content: space-between;
    background: #f8fafc; border: 1.5px solid #e2e8f0; border-radius: 12px;
    padding: .5rem .85rem; margin-bottom: .85rem; gap: .5rem; flex-wrap: wrap;
}
.pk-toolbar-left  { font-size: .75rem; color: #64748b; font-weight: 600; }
.pk-toolbar-left strong { color: #0f172a; }
.pk-toolbar-right { display: flex; gap: .4rem; flex-wrap: wrap; }

/* ── Buttons ────────────────────────────────────────────────────────── */
.pk-btn {
    font-size: .73rem; font-weight: 700; padding: .3rem .8rem;
    border-radius: 999px; border: 1.5px solid #e2e8f0;
    background: #fff; color: #0f172a; cursor: pointer; transition: all .15s; white-space: nowrap;
}
.pk-btn:hover   { border-color: #94a3b8; }
.pk-btn.primary { background: #0f172a; color: #fff; border-color: #0f172a; }
.pk-btn.primary:hover { background: #1e293b; }
.pk-btn.success { background: #16a34a; color: #fff; border-color: #16a34a; }
.pk-btn.success:hover { background: #15803d; }
.pk-btn.warning { background: #d97706; color: #fff; border-color: #d97706; }
.pk-btn.warning:hover { background: #b45309; }
.pk-btn.danger  { background: #dc2626; color: #fff; border-color: #dc2626; }
.pk-btn.danger:hover  { background: #b91c1c; }
.pk-btn:disabled { opacity: .45; cursor: not-allowed; }

/* ── Table ──────────────────────────────────────────────────────────── */
.pk-table { width: 100%; border-collapse: collapse; font-size: .8rem; }
.pk-table th {
    text-align: left; font-size: .65rem; font-weight: 800; text-transform: uppercase;
    letter-spacing: .05em; color: #94a3b8; padding: .4rem .7rem;
    border-bottom: 2px solid #e2e8f0; white-space: nowrap;
}
.pk-table td   { padding: .55rem .7rem; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
.pk-table tr:last-child td { border-bottom: none; }
.pk-table tr:hover td { background: #fafafa; }
.pk-cell-name  { font-weight: 700; color: #0f172a; }
.pk-cell-sku   { font-size: .67rem; color: #94a3b8; margin-top: .05rem; }
.pk-cell-qty   { font-size: .9rem; font-weight: 900; color: #0f172a; text-align: center; }

/* ── Status badges ──────────────────────────────────────────────────── */
.pk-status {
    display: inline-block; font-size: .65rem; font-weight: 800;
    padding: .15rem .55rem; border-radius: 999px; white-space: nowrap;
}
.pk-status-locked  { background: #f0fdf4; color: #16a34a; border: 1.5px solid #bbf7d0; }
.pk-status-hold    { background: #fffbeb; color: #b45309; border: 1.5px solid #fde68a; }
.pk-status-partial { background: #eff6ff; color: #1d4ed8; border: 1.5px solid #bfdbfe; }
.pk-status-missing { background: #fef2f2; color: #dc2626; border: 1.5px solid #fecaca; }
.pk-status-approved{ background: #f0fdf4; color: #16a34a; border: 1.5px solid #bbf7d0; }
.pk-status-pending { background: #f8fafc; color: #64748b; border: 1.5px solid #e2e8f0; }

/* ── Order chip in mapping ──────────────────────────────────────────── */
.pk-order-chip {
    font-size: .65rem; font-weight: 700; padding: .1rem .4rem;
    border-radius: 6px; background: #f1f5f9; color: #475569;
    border: 1px solid #e2e8f0; white-space: nowrap;
}

/* ── Empty state ────────────────────────────────────────────────────── */
.pk-empty { text-align: center; padding: 2.5rem 1rem; color: #94a3b8; }
.pk-empty-icon { font-size: 2.2rem; margin-bottom: .4rem; }
.pk-empty-text { font-size: .82rem; font-weight: 600; }
.pk-empty-sub  { font-size: .72rem; margin-top: .25rem; color: #cbd5e1; }

/* ── Ready to Ship card ─────────────────────────────────────────────── */
.pk-ship-card {
    background: #fff; border: 1.5px solid #e2e8f0; border-radius: 14px;
    margin-bottom: .65rem; overflow: hidden;
}
.pk-ship-head {
    display: flex; align-items: center; justify-content: space-between;
    padding: .6rem .9rem; background: #f8fafc; border-bottom: 1px solid #f1f5f9; flex-wrap: wrap; gap: .4rem;
}
.pk-ship-order { font-size: .85rem; font-weight: 900; color: #0f172a; }
.pk-ship-meta  { font-size: .7rem; color: #64748b; margin-top: .06rem; }
.pk-ship-body  { padding: .55rem .9rem; }
.pk-ship-footer{
    display: flex; gap: .4rem; padding: .5rem .9rem;
    border-top: 1px solid #f1f5f9; justify-content: flex-end;
}

/* ── Replacement card ───────────────────────────────────────────────── */
.pk-rep-card {
    background: #fff; border: 1.5px solid #fde68a; border-radius: 14px;
    margin-bottom: .65rem; overflow: hidden;
}
.pk-rep-head {
    display: flex; align-items: center; justify-content: space-between;
    padding: .6rem .9rem; background: #fffbeb; border-bottom: 1px solid #fef9c3; flex-wrap: wrap; gap: .4rem;
}
.pk-rep-approved { border-color: #bbf7d0; }
.pk-rep-approved .pk-rep-head { background: #f0fdf4; border-bottom-color: #dcfce7; }

/* ── Mobile ─────────────────────────────────────────────────────────── */
@media(max-width:540px){
    .pk-wrap  { padding: .5rem .65rem 4rem; }
    .pk-table { font-size: .74rem; }
    .pk-table th, .pk-table td { padding: .4rem .5rem; }
    .pk-flow-steps { gap: .25rem; }
    .pk-step  { font-size: .62rem; padding: .18rem .55rem; }
}
</style>
@endpush

@section('content')
<div class="pk-wrap">

    {{-- Header --}}
    <div class="pk-header">
        <div>
            <div class="pk-title">📦 Batch Picking</div>
            <div class="pk-subtitle">Scan item → scan pesanan → mapping → kirim</div>
        </div>
        <a href="/marketplace/orders" class="pk-btn">← Orders</a>
    </div>

    {{-- Workflow banner --}}
    <div class="pk-flow-banner">
        <div>
            <div class="pk-flow-title">Workflow: Batch Fulfillment</div>
            <div class="pk-flow-sub">Scan semua item dulu → scan pesanan → mapping otomatis → replacement approval → proses kirim</div>
        </div>
        <div class="pk-flow-steps">
            <span class="pk-step" data-flow="1" onclick="switchTab('itempool',document.querySelector('[data-tab=itempool]'))">1. Scan Item</span>
            <span class="pk-step" data-flow="2" onclick="switchTab('orderpool',document.querySelector('[data-tab=orderpool]'))">2. Scan Pesanan</span>
            <span class="pk-step" data-flow="3" onclick="switchTab('mapping',document.querySelector('[data-tab=mapping]'))">3. Mapping</span>
            <span class="pk-step" data-flow="4" onclick="switchTab('replacement',document.querySelector('[data-tab=replacement]'))">4. Replacement</span>
            <span class="pk-step" data-flow="5" onclick="switchTab('readytoship',document.querySelector('[data-tab=readytoship]'))">5. Kirim</span>
        </div>
    </div>

    {{-- KPIs --}}
    <div class="pk-kpi-grid" id="kpiGrid">
        <div class="pk-kpi"><div class="pk-kpi-label">Item di Pool</div><div class="pk-kpi-value" id="kpi-items">0</div><div class="pk-kpi-note">scan item</div></div>
        <div class="pk-kpi"><div class="pk-kpi-label">Order di Pool</div><div class="pk-kpi-value" id="kpi-orders">0</div><div class="pk-kpi-note">scan pesanan</div></div>
        <div class="pk-kpi locked"><div class="pk-kpi-label">Order Locked</div><div class="pk-kpi-value" id="kpi-locked">0</div><div class="pk-kpi-note">siap kirim</div></div>
        <div class="pk-kpi hold"><div class="pk-kpi-label">Order Hold</div><div class="pk-kpi-value" id="kpi-hold">0</div><div class="pk-kpi-note">perlu replacement</div></div>
    </div>

    {{-- Tabs --}}
    <div class="pk-tabs">
        <button class="pk-tab active" data-tab="itempool"    onclick="switchTab('itempool',this)">
            📦 Item Pool <span class="pk-badge" id="badge-items">0</span>
        </button>
        <button class="pk-tab" data-tab="orderpool"   onclick="switchTab('orderpool',this)">
            📋 Order Pool <span class="pk-badge" id="badge-orders">0</span>
        </button>
        <button class="pk-tab" data-tab="mapping"     onclick="switchTab('mapping',this)">
            🔗 Mapping <span class="pk-badge" id="badge-mapping">0</span>
        </button>
        <button class="pk-tab" data-tab="replacement" onclick="switchTab('replacement',this)">
            🔄 Replacement <span class="pk-badge" id="badge-replacement" style="background:rgba(245,158,11,.2);color:#b45309">0</span>
        </button>
        <button class="pk-tab" data-tab="readytoship" onclick="switchTab('readytoship',this)">
            🚀 Ready to Ship <span class="pk-badge" id="badge-ready" style="background:rgba(34,197,94,.2);color:#16a34a">0</span>
        </button>
    </div>

    {{-- ── Panel 1: Item Pool ───────────────────────────────────────────── --}}
    <div class="pk-panel active" id="panel-itempool">
        <div class="pk-scan-row">
            <input class="pk-scan-input" id="scanItemInput" type="text"
                placeholder="Scan barcode item atau ketik SKU…"
                onkeydown="if(event.key==='Enter') scanItemDummy(this.value)">
            <button class="pk-btn primary" onclick="scanItemDummy(document.getElementById('scanItemInput').value)">+ Tambah</button>
            <button class="pk-btn" onclick="loadDummyItemPool()">🗂 Load Dummy</button>
        </div>
        <div class="pk-scan-hint">Scan barcode → Enter, atau klik "Load Dummy" untuk isi data contoh.</div>

        <div class="pk-toolbar">
            <div class="pk-toolbar-left"><strong id="itemPoolCount">0</strong> item di pool · <strong id="itemPoolTotalQty">0</strong> total pcs</div>
            <div class="pk-toolbar-right">
                <button class="pk-btn danger" onclick="clearItemPool()">🗑 Kosongkan</button>
            </div>
        </div>

        <table class="pk-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>SKU</th>
                    <th>Nama Item</th>
                    <th style="text-align:center">Qty</th>
                    <th>Waktu Scan</th>
                    <th></th>
                </tr>
            </thead>
            <tbody id="itempool-body"></tbody>
        </table>
    </div>

    {{-- ── Panel 2: Order Pool ──────────────────────────────────────────── --}}
    <div class="pk-panel" id="panel-orderpool">
        <div class="pk-scan-row">
            <input class="pk-scan-input" id="scanOrderInput" type="text"
                placeholder="Scan nomor pesanan atau resi…"
                onkeydown="if(event.key==='Enter') scanOrderDummy(this.value)">
            <button class="pk-btn primary" onclick="scanOrderDummy(document.getElementById('scanOrderInput').value)">+ Tambah</button>
            <button class="pk-btn" onclick="loadDummyOrderPool()">🗂 Load Dummy</button>
        </div>
        <div class="pk-scan-hint">Scan nomor pesanan → Enter, atau klik "Load Dummy" untuk isi data contoh.</div>

        <div class="pk-toolbar">
            <div class="pk-toolbar-left"><strong id="orderPoolCount">0</strong> order di pool</div>
            <div class="pk-toolbar-right">
                <button class="pk-btn danger" onclick="clearOrderPool()">🗑 Kosongkan</button>
            </div>
        </div>

        <table class="pk-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>No Pesanan</th>
                    <th>Toko · Channel</th>
                    <th>Item</th>
                    <th>Waktu Scan</th>
                    <th></th>
                </tr>
            </thead>
            <tbody id="orderpool-body"></tbody>
        </table>
    </div>

    {{-- ── Panel 3: Mapping ─────────────────────────────────────────────── --}}
    <div class="pk-panel" id="panel-mapping">
        <div class="pk-toolbar">
            <div class="pk-toolbar-left">
                <strong id="mappingLocked">0</strong> locked &nbsp;·&nbsp;
                <strong id="mappingHold">0</strong> hold
            </div>
            <div class="pk-toolbar-right">
                <button class="pk-btn primary" onclick="runAutoMappingDummy()">⚡ Auto Mapping</button>
            </div>
        </div>

        <table class="pk-table">
            <thead>
                <tr>
                    <th>No Pesanan</th>
                    <th>Item yang Dibutuhkan</th>
                    <th style="text-align:center">Qty Butuh</th>
                    <th style="text-align:center">Qty Pool</th>
                    <th>Status</th>
                    <th>Catatan</th>
                </tr>
            </thead>
            <tbody id="mapping-body"></tbody>
        </table>

        <div id="mapping-empty" class="pk-empty" style="display:none">
            <div class="pk-empty-icon">🔗</div>
            <div class="pk-empty-text">Belum ada mapping</div>
            <div class="pk-empty-sub">Pastikan Item Pool dan Order Pool sudah terisi, lalu klik Auto Mapping.</div>
        </div>
    </div>

    {{-- ── Panel 4: Replacement ─────────────────────────────────────────── --}}
    <div class="pk-panel" id="panel-replacement">
        <div class="pk-toolbar">
            <div class="pk-toolbar-left">
                <strong id="repPendingCount">0</strong> menunggu approval &nbsp;·&nbsp;
                <strong id="repApprovedCount">0</strong> approved
            </div>
            <div class="pk-toolbar-right">
                <button class="pk-btn success" onclick="approveAllReplacements()">✓ Approve Semua</button>
            </div>
        </div>
        <div id="replacement-list"></div>
        <div id="replacement-empty" class="pk-empty" style="display:none">
            <div class="pk-empty-icon">✅</div>
            <div class="pk-empty-text">Tidak ada replacement yang perlu disetujui</div>
            <div class="pk-empty-sub">Semua order sudah exact match atau belum di-mapping.</div>
        </div>
    </div>

    {{-- ── Panel 5: Ready to Ship ───────────────────────────────────────── --}}
    <div class="pk-panel" id="panel-readytoship">
        <div class="pk-toolbar">
            <div class="pk-toolbar-left">
                <strong id="readyCount">0</strong> order siap dikirim
            </div>
            <div class="pk-toolbar-right">
                <button class="pk-btn" onclick="printPickingList()">
                    Cetak Picking List
                </button>
                <button class="pk-btn success" id="btnProcessShipping" onclick="processShippingDummy()">
                    🚀 Proses Kirim Semua
                </button>
            </div>
        </div>
        <div id="readytoship-list"></div>
        <div id="readytoship-empty" class="pk-empty" style="display:none">
            <div class="pk-empty-icon">📦</div>
            <div class="pk-empty-text">Belum ada order locked</div>
            <div class="pk-empty-sub">Jalankan Auto Mapping dan selesaikan Replacement terlebih dahulu.</div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
// ══════════════════════════════════════════════════════════════════════
// DUMMY DATA — semua data disimpan di JS state, tidak menyentuh database
// ══════════════════════════════════════════════════════════════════════

window.gfPickingDummy = {
    itemPool: [
        { id: 1, barcode: 'KPP-L-001',  sku: 'KPP-L',  name: 'Kaos Putih Polos L',     qty: 5, scannedAt: '08:30' },
        { id: 2, barcode: 'CJH-M-001',  sku: 'CJH-M',  name: 'Celana Jogger Hitam M',  qty: 3, scannedAt: '08:31' },
        { id: 3, barcode: 'PSN-M-001',  sku: 'PSN-M',  name: 'Polo Shirt Navy M',      qty: 1, scannedAt: '08:32' },
        { id: 4, barcode: 'KPP-XL-001', sku: 'KPP-XL', name: 'Kaos Putih Polos XL',   qty: 2, scannedAt: '08:33' },
    ],
    orderPool: [
        {
            id: 1, orderNo: '260610ABC001', store: 'Insight Corps', channel: 'Shopee', scannedAt: '08:35',
            items: [
                { sku: 'KPP-L',  name: 'Kaos Putih Polos L',    qty: 3 },
                { sku: 'CJH-M',  name: 'Celana Jogger Hitam M', qty: 1 },
            ]
        },
        {
            id: 2, orderNo: '260610ABC002', store: 'Insight Corps', channel: 'Tokopedia', scannedAt: '08:36',
            items: [
                { sku: 'KPP-L',  name: 'Kaos Putih Polos L',  qty: 2 },
                { sku: 'PSN-M',  name: 'Polo Shirt Navy M',   qty: 1 },
                { sku: 'KPP-XL', name: 'Kaos Putih Polos XL', qty: 2 },
            ]
        },
        {
            id: 3, orderNo: '260610ABC003', store: 'Insight Corps', channel: 'Shopee', scannedAt: '08:37',
            items: [
                { sku: 'CJH-M', name: 'Celana Jogger Hitam M', qty: 2 },
            ]
        },
        {
            id: 4, orderNo: '260610ABC004', store: 'Insight Corps', channel: 'TikTok', scannedAt: '08:38',
            items: [
                { sku: 'KHM-M', name: 'Kaos Hitam M', qty: 2 }, // item ini tidak ada di pool → hold
            ]
        },
    ],
    mappings: [],
    replacements: [
        {
            id: 1, orderId: 4, orderNo: '260610ABC004',
            requiredSku: 'KHM-M', requiredName: 'Kaos Hitam M', requiredQty: 2,
            availableSku: 'KPP-L', availableName: 'Kaos Putih Polos L', availableQty: 5,
            reason: 'Item KHM-M tidak tersedia di pool. Tersedia pengganti: KPP-L.',
            status: 'pending', // pending | approved | hold
        },
        {
            id: 2, orderId: 3, orderNo: '260610ABC003',
            requiredSku: 'CJH-M', requiredName: 'Celana Jogger Hitam M', requiredQty: 2,
            availableSku: 'CJH-M', availableName: 'Celana Jogger Hitam M', availableQty: 3,
            reason: 'Qty di pool (3) cukup namun ada order lain yang butuh item yang sama. Konfirmasi alokasi.',
            status: 'pending',
        },
    ],
    readyToShip: [
        { id: 1, orderNo: '260610ABC001', store: 'Insight Corps', channel: 'Shopee',    items: 2, totalQty: 4, status: 'locked' },
        { id: 2, orderNo: '260610ABC002', store: 'Insight Corps', channel: 'Tokopedia', items: 3, totalQty: 5, status: 'locked' },
        { id: 5, orderNo: '260610ABC005', store: 'Insight Corps', channel: 'Shopee',    items: 1, totalQty: 1, status: 'locked' },
    ],
    holdOrders: [
        { id: 4, orderNo: '260610ABC004', store: 'Insight Corps', channel: 'TikTok', reason: 'Item KHM-M tidak tersedia di pool' },
    ],
};

// ══════════════════════════════════════════════════════════════════════
// STATE (runtime copy — jangan ubah dummy langsung)
// ══════════════════════════════════════════════════════════════════════
let S = {
    itemPool:     [],
    orderPool:    [],
    mappings:     [],
    replacements: [],
    readyToShip:  [],
    holdOrders:   [],
    itemIdSeq:    100,
    orderIdSeq:   100,
};

let activeTab = 'itempool';

// ══════════════════════════════════════════════════════════════════════
// TABS & FLOW STEPS
// ══════════════════════════════════════════════════════════════════════
const TAB_FLOW = { itempool:1, orderpool:2, mapping:3, replacement:4, readytoship:5 };

function switchTab(tab, btn) {
    activeTab = tab;
    document.querySelectorAll('.pk-tab').forEach(b => b.classList.remove('active'));
    document.querySelectorAll('.pk-panel').forEach(p => p.classList.remove('active'));
    if (btn) btn.classList.add('active');
    document.getElementById('panel-' + tab).classList.add('active');
    // Highlight flow step
    const step = TAB_FLOW[tab] || 0;
    document.querySelectorAll('.pk-step').forEach((el, i) => {
        const n = i + 1;
        el.classList.remove('active-step', 'done-step');
        if (n === step)    el.classList.add('active-step');
        else if (n < step) el.classList.add('done-step');
    });
    renderAll();
}

// ══════════════════════════════════════════════════════════════════════
// RENDER ALL
// ══════════════════════════════════════════════════════════════════════
function renderAll() {
    renderKpis();
    renderItemPool();
    renderOrderPool();
    renderMappings();
    renderReplacements();
    renderReadyToShip();
    updateBadges();
}

// ══════════════════════════════════════════════════════════════════════
// 1. renderKpis()
// ══════════════════════════════════════════════════════════════════════
function renderKpis() {
    const totalItemQty = S.itemPool.reduce((s, i) => s + i.qty, 0);
    document.getElementById('kpi-items').textContent   = S.itemPool.length + ' SKU / ' + totalItemQty + ' pcs';
    document.getElementById('kpi-orders').textContent  = S.orderPool.length;
    document.getElementById('kpi-locked').textContent  = S.readyToShip.length;
    document.getElementById('kpi-hold').textContent    = S.holdOrders.length;
}

function updateBadges() {
    document.getElementById('badge-items').textContent      = S.itemPool.length;
    document.getElementById('badge-orders').textContent     = S.orderPool.length;
    document.getElementById('badge-mapping').textContent    = S.mappings.length;
    document.getElementById('badge-replacement').textContent= S.replacements.filter(r => r.status === 'pending').length;
    document.getElementById('badge-ready').textContent      = S.readyToShip.length;
}

// ══════════════════════════════════════════════════════════════════════
// 2. renderItemPool()
// ══════════════════════════════════════════════════════════════════════
function renderItemPool() {
    const body     = document.getElementById('itempool-body');
    const totalQty = S.itemPool.reduce((s, i) => s + i.qty, 0);
    document.getElementById('itemPoolCount').textContent    = S.itemPool.length;
    document.getElementById('itemPoolTotalQty').textContent = totalQty;

    if (!S.itemPool.length) {
        body.innerHTML = `<tr><td colspan="6"><div class="pk-empty">
            <div class="pk-empty-icon">📦</div>
            <div class="pk-empty-text">Item pool kosong</div>
            <div class="pk-empty-sub">Scan barcode atau klik "Load Dummy"</div>
        </div></td></tr>`;
        return;
    }
    body.innerHTML = S.itemPool.map((item, idx) => `
        <tr>
            <td style="color:#94a3b8;font-size:.7rem">${idx + 1}</td>
            <td><div class="pk-cell-name">${esc(item.sku)}</div></td>
            <td><div class="pk-cell-name">${esc(item.name)}</div></td>
            <td class="pk-cell-qty">${item.qty}</td>
            <td style="font-size:.7rem;color:#94a3b8">${esc(item.scannedAt)}</td>
            <td>
                <button class="pk-btn" style="font-size:.65rem;padding:.15rem .55rem"
                    onclick="removeItemFromPool(${item.id})">✕</button>
            </td>
        </tr>`).join('');
}

// ══════════════════════════════════════════════════════════════════════
// 3. renderOrderPool()
// ══════════════════════════════════════════════════════════════════════
function renderOrderPool() {
    const body = document.getElementById('orderpool-body');
    document.getElementById('orderPoolCount').textContent = S.orderPool.length;

    if (!S.orderPool.length) {
        body.innerHTML = `<tr><td colspan="6"><div class="pk-empty">
            <div class="pk-empty-icon">📋</div>
            <div class="pk-empty-text">Order pool kosong</div>
            <div class="pk-empty-sub">Scan nomor pesanan atau klik "Load Dummy"</div>
        </div></td></tr>`;
        return;
    }
    body.innerHTML = S.orderPool.map((order, idx) => {
        const itemList = order.items.map(i =>
            `<span class="pk-order-chip">${esc(i.sku)} ×${i.qty}</span>`
        ).join(' ');
        return `
        <tr>
            <td style="color:#94a3b8;font-size:.7rem">${idx + 1}</td>
            <td><div class="pk-cell-name">${esc(order.orderNo)}</div></td>
            <td>
                <div style="font-size:.78rem;font-weight:700">${esc(order.store)}</div>
                <div class="pk-cell-sku">${esc(order.channel)}</div>
            </td>
            <td><div style="display:flex;flex-wrap:wrap;gap:.2rem">${itemList}</div></td>
            <td style="font-size:.7rem;color:#94a3b8">${esc(order.scannedAt)}</td>
            <td>
                <button class="pk-btn" style="font-size:.65rem;padding:.15rem .55rem"
                    onclick="removeOrderFromPool(${order.id})">✕</button>
            </td>
        </tr>`;
    }).join('');
}

// ══════════════════════════════════════════════════════════════════════
// 4. renderMappings()
// ══════════════════════════════════════════════════════════════════════
function renderMappings() {
    const body    = document.getElementById('mapping-body');
    const empty   = document.getElementById('mapping-empty');
    const locked  = S.mappings.filter(m => m.status === 'locked').length;
    const hold    = S.mappings.filter(m => m.status !== 'locked').length;
    document.getElementById('mappingLocked').textContent = locked;
    document.getElementById('mappingHold').textContent   = hold;

    if (!S.mappings.length) {
        body.innerHTML = '';
        empty.style.display = 'block';
        return;
    }
    empty.style.display = 'none';

    body.innerHTML = S.mappings.map(m => {
        const statusClass = m.status === 'locked'  ? 'pk-status-locked'
                          : m.status === 'partial' ? 'pk-status-partial'
                          : m.status === 'missing' ? 'pk-status-missing'
                          :                          'pk-status-hold';
        const statusLabel = m.status === 'locked'  ? '✓ Locked'
                          : m.status === 'partial' ? '⚠ Partial'
                          : m.status === 'missing' ? '✕ Item Tidak Ada'
                          :                          '⏸ Hold';
        return `
        <tr>
            <td><span class="pk-order-chip">${esc(m.orderNo)}</span></td>
            <td>
                <div class="pk-cell-name">${esc(m.itemName)}</div>
                <div class="pk-cell-sku">${esc(m.sku)}</div>
            </td>
            <td class="pk-cell-qty">${m.qtyNeed}</td>
            <td class="pk-cell-qty" style="color:${m.qtyPool >= m.qtyNeed ? '#16a34a' : '#dc2626'}">${m.qtyPool}</td>
            <td><span class="pk-status ${statusClass}">${statusLabel}</span></td>
            <td style="font-size:.72rem;color:#64748b">${esc(m.note || '')}</td>
        </tr>`;
    }).join('');
}

// ══════════════════════════════════════════════════════════════════════
// 5. renderReplacements()
// ══════════════════════════════════════════════════════════════════════
function renderReplacements() {
    const list    = document.getElementById('replacement-list');
    const empty   = document.getElementById('replacement-empty');
    const pending  = S.replacements.filter(r => r.status === 'pending').length;
    const approved = S.replacements.filter(r => r.status === 'approved').length;
    document.getElementById('repPendingCount').textContent  = pending;
    document.getElementById('repApprovedCount').textContent = approved;

    if (!S.replacements.length) {
        list.innerHTML = '';
        empty.style.display = 'block';
        return;
    }
    empty.style.display = 'none';

    list.innerHTML = S.replacements.map(r => {
        const isApproved = r.status === 'approved';
        return `
        <div class="pk-rep-card ${isApproved ? 'pk-rep-approved' : ''}">
            <div class="pk-rep-head">
                <div>
                    <div style="font-size:.82rem;font-weight:900;color:#0f172a">${esc(r.orderNo)}</div>
                    <div style="font-size:.7rem;color:#92400e;margin-top:.05rem">${esc(r.reason)}</div>
                </div>
                <span class="pk-status ${isApproved ? 'pk-status-approved' : 'pk-status-pending'}">
                    ${isApproved ? '✓ Approved' : '⏳ Pending'}
                </span>
            </div>
            <div style="padding:.6rem .9rem">
                <div style="display:flex;gap:1.2rem;flex-wrap:wrap;margin-bottom:.5rem">
                    <div>
                        <div style="font-size:.65rem;font-weight:800;text-transform:uppercase;letter-spacing:.05em;color:#94a3b8;margin-bottom:.2rem">Item Diminta</div>
                        <div style="font-size:.8rem;font-weight:700">${esc(r.requiredName)}</div>
                        <div style="font-size:.68rem;color:#94a3b8">${esc(r.requiredSku)} · ${r.requiredQty} pcs</div>
                    </div>
                    <div style="display:flex;align-items:center;color:#94a3b8;font-size:1rem">→</div>
                    <div>
                        <div style="font-size:.65rem;font-weight:800;text-transform:uppercase;letter-spacing:.05em;color:#94a3b8;margin-bottom:.2rem">Item Pengganti</div>
                        <div style="font-size:.8rem;font-weight:700">${esc(r.availableName)}</div>
                        <div style="font-size:.68rem;color:#94a3b8">${esc(r.availableSku)} · tersedia ${r.availableQty} pcs</div>
                    </div>
                </div>
                ${!isApproved ? `
                <div style="display:flex;gap:.4rem;justify-content:flex-end">
                    <button class="pk-btn danger" onclick="holdReplacementDummy(${r.id})">✕ Hold</button>
                    <button class="pk-btn success" onclick="approveReplacementDummy(${r.id})">✓ Approve Replacement</button>
                </div>` : `
                <div style="text-align:right;font-size:.72rem;color:#16a34a;font-weight:700">✓ Replacement disetujui — order dipindah ke Ready to Ship</div>`}
            </div>
        </div>`;
    }).join('');
}

// ══════════════════════════════════════════════════════════════════════
// 6. renderReadyToShip()
// ══════════════════════════════════════════════════════════════════════
function renderReadyToShip() {
    const list  = document.getElementById('readytoship-list');
    const empty = document.getElementById('readytoship-empty');
    document.getElementById('readyCount').textContent = S.readyToShip.length;

    if (!S.readyToShip.length) {
        list.innerHTML = '';
        empty.style.display = 'block';
        document.getElementById('btnProcessShipping').disabled = true;
        return;
    }
    empty.style.display = 'none';
    document.getElementById('btnProcessShipping').disabled = false;

    list.innerHTML = S.readyToShip.map(o => {
        const lines      = o.lines || [];
        const pickedCnt  = lines.filter(l => l.picked).length;
        const hasProblem = lines.some(l => l.problem);
        const allPicked  = lines.length > 0 && pickedCnt === lines.length;

        const pickedBadge = lines.length
            ? `<span style="font-size:.72rem;font-weight:700;padding:.2rem .5rem;border-radius:999px;background:${allPicked?'#dcfce7':'#fef3c7'};color:${allPicked?'#15803d':'#92400e'}">${pickedCnt}/${lines.length} dipick</span>`
            : '';

        const linesHtml = lines.length ? `
            <div class="pk-line-checklist">
                ${lines.map((l, li) => `
                    <div class="pk-line-row${l.picked?' picked':''}${l.problem?' has-problem':''}">
                        <button class="pk-line-check-btn" onclick="toggleLinePick(${o.id},${li})" title="${l.picked?'Batalkan pick':'Tandai picked'}">
                            <span class="pk-checkbox">${l.picked ? '☑' : '☐'}</span>
                            <span class="pk-line-body">
                                <span class="pk-line-sku">${esc(l.sku)}</span>
                                <span class="pk-line-name">${esc(l.name)}</span>
                                <span class="pk-line-qty">×${l.qty}</span>
                            </span>
                        </button>
                        <div class="pk-line-actions">
                            ${l.problem
                                ? `<span class="pk-line-problem-tag" title="${esc(l.problem)}">⚠ ${esc(l.problem)}</span>
                                   <button class="pk-btn-icon" onclick="resolveLinePickProblem(${o.id},${li})" title="Clear problem">✓</button>`
                                : `<button class="pk-btn-icon" onclick="flagLinePickProblem(${o.id},${li})" title="Tandai masalah">🚩</button>`
                            }
                        </div>
                    </div>`).join('')}
            </div>` : '';

        return `
        <div class="pk-ship-card">
            <div class="pk-ship-head">
                <div>
                    <div class="pk-ship-order">${esc(o.orderNo)}</div>
                    <div class="pk-ship-meta">${esc(o.store)} · ${esc(o.channel)} · ${o.items} item · ${o.totalQty} pcs</div>
                </div>
                <div style="display:flex;align-items:center;gap:.4rem;flex-wrap:wrap;justify-content:flex-end">
                    ${pickedBadge}
                    <span class="pk-status pk-status-locked">✓ Locked</span>
                </div>
            </div>
            ${linesHtml}
            <div class="pk-ship-footer" style="display:flex;align-items:center;gap:.75rem;flex-wrap:wrap">
                <button class="pk-btn" onclick="printPickingList(${o.id})">
                    Cetak
                </button>
                <button class="pk-btn success" onclick="processShippingDummy(${o.id})"
                    ${hasProblem ? 'disabled title="Ada baris bermasalah — selesaikan dulu"' : ''}>
                    🚀 Proses Kirim
                </button>
                ${hasProblem ? '<span style="font-size:.72rem;color:#dc2626;font-weight:600">⚠ Ada baris bermasalah</span>' : ''}
            </div>
        </div>`;
    }).join('');
}

// ══════════════════════════════════════════════════════════════════════
// 7. scanItemDummy(barcode)
// ══════════════════════════════════════════════════════════════════════
window.scanItemDummy = async function(input) {
    input = (input || '').trim();
    if (!input) return;

    // Duplikat → +1 qty langsung, tanpa hit API
    const existing = S.itemPool.find(i => i.sku === input);
    if (existing) {
        existing.qty++;
        toast(`+1 qty untuk ${existing.name}`);
        document.getElementById('scanItemInput').value = '';
        renderAll();
        return;
    }

    toast('Mencari item…');
    try {
        const res  = await fetch(`/api/marketplace/items/search?q=${encodeURIComponent(input)}&limit=10`);
        const list = await res.json();

        // Exact-match pada code (case-insensitive)
        const found = list.find(i => i.code?.toLowerCase() === input.toLowerCase());
        if (!found) {
            toast(`SKU "${input}" tidak ditemukan di master item`, 'warn');
            return;
        }

        const now = new Date().toTimeString().slice(0, 5);
        S.itemPool.push({
            id:        ++S.itemIdSeq,
            itemId:    found.id,
            sku:       found.code,
            name:      found.name,
            qty:       1,
            scannedAt: now,
        });
        toast(`✓ ${found.code} — ${found.name} ditambahkan`);
        document.getElementById('scanItemInput').value = '';
        renderAll();
    } catch (e) {
        toast('Gagal menghubungi server', 'warn');
        console.error('[GF Picking] scanItem error', e);
    }
};

// ══════════════════════════════════════════════════════════════════════
// 8. scanOrderDummy(orderNo)
// ══════════════════════════════════════════════════════════════════════
window.scanOrderDummy = async function(orderNo) {
    orderNo = (orderNo || '').trim();
    if (!orderNo) return;

    const existing = S.orderPool.find(o => o.orderNo === orderNo);
    if (existing) { toast(`Order "${orderNo}" sudah ada di pool`, 'warn'); return; }

    toast('Mencari order…');
    try {
        const res = await fetch('/api/fulfillments/scan', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify({ order_no: orderNo }),
        });
        const data = await res.json();

        if (!res.ok) {
            toast(data.message || `Order "${orderNo}" tidak ditemukan`, 'warn');
            return;
        }
        if (data.already_confirmed) {
            toast(`Order "${orderNo}" sudah dikonfirmasi sebelumnya`, 'warn');
            return;
        }

        const f   = data.fulfillment;
        const now = new Date().toTimeString().slice(0, 5);
        S.orderPool.push({
            id:            ++S.orderIdSeq,
            fulfillmentId: f.id,
            orderNo:       orderNo,
            store:         f.order?.store?.name    || '—',
            channel:       f.order?.store?.channel || '—',
            scannedAt:     now,
            items:         (f.lines || []).map(l => ({
                sku:    l.marketplace_sku        || '?',
                name:   l.marketplace_item_name  || '(unknown)',
                qty:    l.qty_ordered            || 1,
                lineId: l.id,
            })),
        });
        toast(`Order "${orderNo}" ditambahkan (${f.lines_count} item)`);
        document.getElementById('scanOrderInput').value = '';
        renderAll();
    } catch (e) {
        toast('Gagal menghubungi server', 'warn');
        console.error('[GF Picking] scanOrder error', e);
    }
};

// ══════════════════════════════════════════════════════════════════════
// 9. runAutoMappingDummy()
// ══════════════════════════════════════════════════════════════════════
window.runAutoMappingDummy = function() {
    if (!S.itemPool.length || !S.orderPool.length) {
        toast('Isi Item Pool dan Order Pool terlebih dahulu', 'warn');
        return;
    }

    // Build item availability map { sku → totalQty }
    const poolMap = {};
    S.itemPool.forEach(i => { poolMap[i.sku] = (poolMap[i.sku] || 0) + i.qty; });

    S.mappings     = [];
    S.replacements = [];
    const newReady = [];
    const newHold  = [];

    S.orderPool.forEach(order => {
        let orderLocked = true;
        order.items.forEach(item => {
            const qtyPool = poolMap[item.sku] || 0;
            let status, note;
            if (qtyPool >= item.qty) {
                status = 'locked'; note = 'Stok cukup';
                poolMap[item.sku] -= item.qty; // alokasikan
            } else if (qtyPool > 0) {
                status = 'partial'; note = `Butuh ${item.qty}, pool hanya ${qtyPool}`;
                orderLocked = false;
            } else {
                status = 'missing'; note = 'Item tidak ada di pool';
                orderLocked = false;
                // Tambah ke replacements
                const altSku = Object.keys(poolMap).find(k => poolMap[k] > 0);
                S.replacements.push({
                    id:            S.replacements.length + 1,
                    orderId:       order.id,
                    orderNo:       order.orderNo,
                    requiredSku:   item.sku,
                    requiredName:  item.name,
                    requiredQty:   item.qty,
                    availableSku:  altSku || '—',
                    availableName: altSku ? (S.itemPool.find(i=>i.sku===altSku)?.name || altSku) : 'Tidak ada',
                    availableQty:  altSku ? (poolMap[altSku] || 0) : 0,
                    reason:        `Item ${item.sku} tidak tersedia di pool.`,
                    status:        'pending',
                });
            }
            S.mappings.push({
                orderNo:  order.orderNo,
                sku:      item.sku,
                itemName: item.name,
                qtyNeed:  item.qty,
                qtyPool:  poolMap[item.sku] !== undefined ? poolMap[item.sku] + (status==='locked'?item.qty:0) : (poolMap[item.sku+'-orig'] || qtyPool),
                status,
                note,
            });
        });

        if (orderLocked) {
            if (!newReady.find(r => r.orderNo === order.orderNo)) {
                newReady.push({
                    id:            order.id,
                    fulfillmentId: order.fulfillmentId || null,
                    orderNo:       order.orderNo,
                    store:         order.store,
                    channel:       order.channel,
                    items:         order.items.length,
                    totalQty:      order.items.reduce((s,i) => s+i.qty, 0),
                    status:        'locked',
                    lines:         order.items.map(i => ({
                        sku:     i.sku,
                        name:    i.name,
                        qty:     i.qty,
                        lineId:  i.lineId || null,
                        picked:  false,
                        problem: null,
                    })),
                });
            }
        } else {
            newHold.push({ id: order.id, orderNo: order.orderNo, store: order.store, channel: order.channel, reason: 'Ada item partial/missing' });
        }
    });

    S.readyToShip = newReady;
    S.holdOrders  = newHold;
    toast(`Mapping selesai: ${newReady.length} locked, ${newHold.length} hold`);

    // Panggil start-picking untuk order locked yang punya fulfillmentId real
    const idsToStart = newReady
        .map(o => o.fulfillmentId)
        .filter(Boolean);
    if (idsToStart.length) {
        fetch('/api/fulfillments/start-picking', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify({ ids: idsToStart }),
        })
        .then(r => r.json())
        .then(d => console.log('[GF Picking] start-picking:', d.message))
        .catch(e => console.warn('[GF Picking] start-picking error', e));
    }

    switchTab('mapping', document.querySelector('[data-tab=mapping]'));
};

// ══════════════════════════════════════════════════════════════════════
// 10. approveReplacementDummy(replacementId)
// ══════════════════════════════════════════════════════════════════════
window.approveReplacementDummy = function(replacementId) {
    const rep = S.replacements.find(r => r.id === replacementId);
    if (!rep) return;
    rep.status = 'approved';

    // Pindahkan order dari hold ke readyToShip jika semua replacements untuk order ini approved
    const orderReps = S.replacements.filter(r => r.orderId === rep.orderId);
    const allApproved = orderReps.every(r => r.status === 'approved');
    if (allApproved) {
        S.holdOrders = S.holdOrders.filter(o => o.id !== rep.orderId);
        if (!S.readyToShip.find(o => o.id === rep.orderId)) {
            const srcOrder = S.orderPool.find(o => o.id === rep.orderId);
            S.readyToShip.push({
                id:       rep.orderId,
                orderNo:  rep.orderNo,
                store:    srcOrder?.store   || 'Insight Corps',
                channel:  srcOrder?.channel || '—',
                items:    srcOrder?.items.length || 1,
                totalQty: srcOrder?.items.reduce((s,i)=>s+i.qty,0) || rep.requiredQty,
                status:   'locked',
            });
        }
        toast(`Order ${rep.orderNo} dipindah ke Ready to Ship`);
    } else {
        toast(`Replacement #${replacementId} disetujui`);
    }
    renderAll();
};

// ══════════════════════════════════════════════════════════════════════
// 11. holdReplacementDummy(replacementId)
// ══════════════════════════════════════════════════════════════════════
window.holdReplacementDummy = function(replacementId) {
    const rep = S.replacements.find(r => r.id === replacementId);
    if (!rep) return;
    rep.status = 'hold';
    toast(`Replacement #${replacementId} di-hold`, 'warn');
    renderAll();
};

function approveAllReplacements() {
    S.replacements.filter(r => r.status === 'pending').forEach(r => approveReplacementDummy(r.id));
}

// ══════════════════════════════════════════════════════════════════════
// 12. processShippingDummy(orderId?)
// ══════════════════════════════════════════════════════════════════════
window.processShippingDummy = async function(orderId) {
    let targets = orderId
        ? S.readyToShip.filter(o => o.id === orderId)
        : S.readyToShip;

    if (!targets.length) { toast('Tidak ada order locked', 'warn'); return; }

    const realTargets  = targets.filter(o => o.fulfillmentId);
    const dummyTargets = targets.filter(o => !o.fulfillmentId);

    // Kalau semua dummy — preview saja
    if (!realTargets.length) {
        console.log('[GF Picking — PREVIEW] Semua order adalah data dummy:');
        console.table(dummyTargets.map(o => ({ orderNo: o.orderNo, store: o.store, channel: o.channel })));
        alert(
            `[PREVIEW — Database belum diubah]\n\n` +
            `${dummyTargets.length} order (data dummy) — scan order nyata terlebih dahulu ` +
            `agar fulfillment ID tersedia.\n\n` +
            `Stok belum dipotong.`
        );
        return;
    }

    const label = realTargets.length === 1
        ? `order ${realTargets[0].orderNo}`
        : `${realTargets.length} order`;
    if (!confirm(`Konfirmasi ${label}?\n\nStok akan dipotong dan tidak bisa di-undo.`)) return;

    toast('Memproses konfirmasi…');
    const errors = [];
    for (const order of realTargets) {
        try {
            const res  = await fetch(`/api/fulfillments/${order.fulfillmentId}/confirm`, {
                method:  'POST',
                headers: { 'Content-Type': 'application/json' },
            });
            const data = await res.json();
            if (!res.ok) {
                errors.push(`${order.orderNo}: ${data.message}`);
            } else {
                S.readyToShip = S.readyToShip.filter(o => o.id !== order.id);
                toast(`✓ ${order.orderNo} dikonfirmasi — stok dipotong`);
            }
        } catch (e) {
            errors.push(`${order.orderNo}: network error`);
            console.error('[GF Picking] confirm error', e);
        }
    }

    if (dummyTargets.length) {
        toast(`${dummyTargets.length} order dummy dilewati (tidak ada fulfillment ID)`, 'warn');
    }
    if (errors.length) {
        alert('Gagal konfirmasi:\n' + errors.join('\n'));
    }
    renderAll();
};

// ══════════════════════════════════════════════════════════════════════
// PICKING CHECKLIST: toggle / flag / resolve per line
// ══════════════════════════════════════════════════════════════════════
window.toggleLinePick = async function(orderId, lineIdx) {
    const order = S.readyToShip.find(o => o.id === orderId);
    if (!order?.lines) return;
    const line = order.lines[lineIdx];
    if (!line) return;

    line.picked  = !line.picked;
    line.problem = line.picked ? null : line.problem; // clear problem saat un-pick

    renderAll();

    if (order.fulfillmentId && line.lineId) {
        try {
            await fetch(`/api/fulfillments/${order.fulfillmentId}/lines/${line.lineId}/toggle-picked`, {
                method: 'POST', headers: { 'Content-Type': 'application/json' },
            });
        } catch (e) { console.warn('[GF Picking] toggleLinePick error', e); }
    }
};

window.flagLinePickProblem = async function(orderId, lineIdx) {
    const order = S.readyToShip.find(o => o.id === orderId);
    if (!order?.lines) return;
    const line = order.lines[lineIdx];
    if (!line) return;

    const reason = prompt(`Masalah untuk "${line.name}":`);
    if (!reason?.trim()) return;

    line.picked  = false;
    line.problem = reason.trim();
    renderAll();

    if (order.fulfillmentId && line.lineId) {
        try {
            await fetch(`/api/fulfillments/${order.fulfillmentId}/lines/${line.lineId}/flag-problem`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body:   JSON.stringify({ reason: line.problem }),
            });
        } catch (e) { console.warn('[GF Picking] flagLinePickProblem error', e); }
    }
};

window.resolveLinePickProblem = async function(orderId, lineIdx) {
    const order = S.readyToShip.find(o => o.id === orderId);
    if (!order?.lines) return;
    const line = order.lines[lineIdx];
    if (!line) return;

    line.problem = null;
    line.picked  = false; // perlu di-pick ulang setelah resolve
    renderAll();
    toast(`Problem baris "${line.sku}" dihapus — pick ulang untuk lanjut`);

    if (order.fulfillmentId && line.lineId) {
        try {
            await fetch(`/api/fulfillments/${order.fulfillmentId}/lines/${line.lineId}/resolve-problem`, {
                method: 'POST', headers: { 'Content-Type': 'application/json' },
                body:   JSON.stringify({}),
            });
        } catch (e) { console.warn('[GF Picking] resolveLinePickProblem error', e); }
    }
};

// ══════════════════════════════════════════════════════════════════════
// LOAD FROM API (picking-queue)
// ══════════════════════════════════════════════════════════════════════
async function loadFromAPI() {
    try {
        const res = await fetch('/api/fulfillments/picking-queue');
        if (!res.ok) return;
        const data = await res.json();
        let added = 0;

        // picking status → pre-populate orderPool
        (data.picking || []).forEach(f => {
            const orderNo = f.order?.channel_order_id;
            if (!orderNo) return;
            if (S.orderPool.find(o => o.fulfillmentId === f.id)) return;
            S.orderPool.push({
                id:            ++S.orderIdSeq,
                fulfillmentId: f.id,
                orderNo,
                store:         f.order?.store?.name    || '—',
                channel:       f.order?.store?.channel || '—',
                scannedAt:     '(DB)',
                items:         (f.lines || []).map(l => ({
                    sku:    l.marketplace_sku       || '?',
                    name:   l.marketplace_item_name || '(unknown)',
                    qty:    l.qty_ordered           || 1,
                    lineId: l.id,
                })),
            });
            added++;
        });

        // packed status → pre-populate readyToShip
        (data.packed || []).forEach(f => {
            const orderNo = f.order?.channel_order_id;
            if (!orderNo) return;
            if (S.readyToShip.find(o => o.fulfillmentId === f.id)) return;
            S.readyToShip.push({
                id:            f.id,
                fulfillmentId: f.id,
                orderNo,
                store:         f.order?.store?.name    || '—',
                channel:       f.order?.store?.channel || '—',
                items:         f.lines_count,
                totalQty:      (f.lines || []).reduce((s, l) => s + (l.qty_fulfilled || 0), 0) || f.lines_count,
                status:        'locked',
                lines:         (f.lines || []).map(l => ({
                    sku:     l.marketplace_sku       || '?',
                    name:    l.marketplace_item_name || '(unknown)',
                    qty:     l.qty_ordered           || 1,
                    lineId:  l.id,
                    picked:  l.is_picked  || false,
                    problem: l.pick_problem || null,
                })),
            });
            added++;
        });

        if (added) {
            toast(`${added} fulfillment dimuat dari database`);
            renderAll();
        }
    } catch (e) {
        console.warn('[GF Picking] loadFromAPI error', e);
    }
}

// ══════════════════════════════════════════════════════════════════════
// LOAD DUMMY HELPERS
// ══════════════════════════════════════════════════════════════════════
function loadDummyItemPool() {
    S.itemPool = JSON.parse(JSON.stringify(window.gfPickingDummy.itemPool));
    toast('Item pool diisi data dummy');
    renderAll();
}

function loadDummyOrderPool() {
    S.orderPool = JSON.parse(JSON.stringify(window.gfPickingDummy.orderPool));
    toast('Order pool diisi data dummy');
    renderAll();
}

function clearItemPool() {
    if (!confirm('Kosongkan semua item dari pool?')) return;
    S.itemPool = [];
    renderAll();
}

function clearOrderPool() {
    if (!confirm('Kosongkan semua order dari pool?')) return;
    S.orderPool = [];
    renderAll();
}

function removeItemFromPool(id) {
    S.itemPool = S.itemPool.filter(i => i.id !== id);
    renderAll();
}

function removeOrderFromPool(id) {
    S.orderPool = S.orderPool.filter(o => o.id !== id);
    renderAll();
}

// ══════════════════════════════════════════════════════════════════════
// TOAST
// ══════════════════════════════════════════════════════════════════════
function toast(msg, type) {
    const el = document.createElement('div');
    const bg = type === 'warn' ? '#d97706' : '#0f172a';
    el.style.cssText = `position:fixed;top:1rem;left:50%;transform:translateX(-50%);z-index:9999;
        background:${bg};color:#fff;font-size:.82rem;font-weight:700;
        padding:.45rem 1.1rem;border-radius:10px;box-shadow:0 4px 16px rgba(0,0,0,.25);
        animation:fadeInDown .15s ease;pointer-events:none;white-space:nowrap`;
    el.textContent = msg;
    document.body.appendChild(el);
    setTimeout(() => el.remove(), 2500);
}

// ══════════════════════════════════════════════════════════════════════
// UTILS
// ══════════════════════════════════════════════════════════════════════
function esc(str) {
    if (str === null || str === undefined) return '';
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function printPickingList(orderId = null) {
    const targets = orderId
        ? S.readyToShip.filter(o => o.id === orderId)
        : S.readyToShip;

    if (!targets.length) {
        toast('Tidak ada picking list untuk dicetak', 'warn');
        return;
    }

    const printedAt = new Date().toLocaleString('id-ID', {
        day: '2-digit',
        month: '2-digit',
        year: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
    });

    const pages = targets.map((order) => {
        const lines = Array.isArray(order.lines) ? order.lines : [];
        const rows = lines.length
            ? lines.map((line, idx) => {
                const sku = line.sku || '-';
                const name = line.name || '';
                const isUnmapped = sku === '?' || sku === '-' || /belum|mapping|tidak/i.test(`${sku} ${name}`);

                return `
                <tr class="${isUnmapped ? 'is-unmapped' : ''}">
                    <td class="num">${idx + 1}</td>
                    <td>
                        <div class="sku">${esc(sku)}</div>
                        <div class="name">${esc(name)}</div>
                    </td>
                    <td class="qty">${Number(line.qty || 0)}</td>
                    <td class="check"></td>
                </tr>
            `;
            }).join('')
            : `<tr><td colspan="4" class="empty">Detail item belum tersedia.</td></tr>`;

        return `
            <section class="pick-page">
                <header class="pick-head">
                    <div>
                        <div class="doc-title">PICKING LIST</div>
                        <div class="doc-sub">${esc(printedAt)}</div>
                    </div>
                    <div class="doc-code">${esc(order.orderNo || '-')}</div>
                </header>

                <div class="meta-grid">
                    <div>
                        <div class="meta-label">Toko</div>
                        <div class="meta-value">${esc(order.store || '-')}</div>
                    </div>
                    <div>
                        <div class="meta-label">Channel</div>
                        <div class="meta-value">${esc(order.channel || '-')}</div>
                    </div>
                    <div>
                        <div class="meta-label">Item</div>
                        <div class="meta-value">${Number(order.items || lines.length || 0)}</div>
                    </div>
                    <div>
                        <div class="meta-label">Qty</div>
                        <div class="meta-value">${Number(order.totalQty || 0)}</div>
                    </div>
                </div>

                <table class="pick-table">
                    <thead>
                        <tr>
                            <th class="num">#</th>
                            <th>Barang</th>
                            <th class="qty">Qty</th>
                            <th class="check">OK</th>
                        </tr>
                    </thead>
                    <tbody>${rows}</tbody>
                </table>

                <footer class="pick-foot">
                    <div>
                        <div class="sign-line"></div>
                        <div>Picker</div>
                    </div>
                    <div>
                        <div class="sign-line"></div>
                        <div>Checker</div>
                    </div>
                </footer>
            </section>
        `;
    }).join('');

    const html = `<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Picking List</title>
<style>
@page { size: 100mm 150mm; margin: 0; }
*,
*::before,
*::after { box-sizing: border-box; color: #000 !important; background: #fff !important; box-shadow: none !important; text-shadow: none !important; border-color: #000 !important; filter: none !important; opacity: 1 !important; }
html, body { margin: 0; padding: 0; font-family: Arial, Helvetica, sans-serif; color: #000 !important; background: #fff !important; font-size: 10.5pt; }
body { -webkit-print-color-adjust: economy; print-color-adjust: economy; color-scheme: light only; }
.pick-page {
    width: 100mm;
    min-height: 150mm;
    padding: 4.5mm;
    page-break-after: always;
    background: #fff !important;
}
.pick-page:last-child { page-break-after: auto; }
.pick-head {
    display: flex;
    justify-content: space-between;
    gap: 4mm;
    align-items: flex-start;
    border-bottom: .45mm solid #000;
    padding-bottom: 2.2mm;
    margin-bottom: 2.7mm;
}
.doc-title { font-size: 17pt; font-weight: 900; letter-spacing: .12mm; color: #000 !important; }
.doc-sub { font-size: 10pt; margin-top: .6mm; font-weight: 900; color: #000 !important; }
.doc-code {
    max-width: 45mm;
    text-align: right;
    font-size: 18pt;
    font-weight: 900;
    word-break: break-word;
    line-height: 1.05;
    color: #fff !important;
    background: #000 !important;
    border: .45mm solid #000;
    padding: 1.8mm 2.2mm;
}
.meta-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1.8mm 3mm;
    border: .35mm solid #000;
    padding: 2mm;
    margin-bottom: 2.7mm;
}
.meta-label { font-size: 9.5pt; text-transform: uppercase; letter-spacing: .08mm; font-weight: 900; color: #000 !important; }
.meta-value { font-size: 12.5pt; font-weight: 900; margin-top: .4mm; word-break: break-word; color: #000 !important; }
.pick-table { width: 100%; border-collapse: collapse; table-layout: fixed; }
.pick-table th,
.pick-table td { border: .35mm solid #000; padding: 1.8mm 1.5mm; vertical-align: top; }
.pick-table th { font-size: 10pt; text-transform: uppercase; text-align: left; font-weight: 900; border: .35mm solid #000; padding: 1.6mm 1.5mm; color: #000 !important; }
.pick-table td { font-size: 11.5pt; font-weight: 900; color: #000 !important; }
.pick-table .num { width: 8mm; text-align: center; }
.pick-table .qty { width: 15mm; text-align: center; font-weight: 900; font-size: 16pt; color: #000 !important; }
.pick-table .check { width: 12mm; text-align: center; }
.sku { font-size: 16pt; font-weight: 900; line-height: 1.02; color: #000 !important; }
.name { font-size: 10.5pt; line-height: 1.12; margin-top: .7mm; font-weight: 900; color: #000 !important; }
.is-unmapped,
.is-unmapped *,
.unmapped,
.unmapped *,
.muted,
.text-muted {
    color: #000 !important;
    background: #fff !important;
    opacity: 1 !important;
    font-weight: 900 !important;
}
.empty { text-align: center; padding: 6mm 2mm !important; font-size: 11pt; font-weight: 900; }
.pick-foot {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10mm;
    margin-top: 7mm;
    font-size: 8.5pt;
    font-weight: 800;
    text-align: center;
}
.sign-line { border-top: .35mm solid #000; height: 9mm; }
@media screen {
    body { background: #fff !important; padding: 6mm; }
    .pick-page { margin: 0 auto 8mm; border: .35mm solid #000; }
}
@media print {
    *,
    *::before,
    *::after {
        color: #000 !important;
        background: #fff !important;
        border-color: #000 !important;
        box-shadow: none !important;
        text-shadow: none !important;
        filter: none !important;
        opacity: 1 !important;
    }
    html,
    body,
    .pick-page {
        width: 100mm;
        background: #fff !important;
    }
    .doc-code {
        color: #fff !important;
        background: #000 !important;
        border-color: #000 !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
}
</style>
</head>
<body>${pages}</body>
</html>`;

    const printWindow = window.open('', '_blank', 'width=420,height=640');
    if (!printWindow) {
        toast('Popup print diblokir browser', 'warn');
        return;
    }
    printWindow.document.open();
    printWindow.document.write(html);
    printWindow.document.close();
    printWindow.focus();
    setTimeout(() => printWindow.print(), 250);
}

// ══════════════════════════════════════════════════════════════════════
// INIT
// ══════════════════════════════════════════════════════════════════════
document.addEventListener('DOMContentLoaded', function() {
    // Aktifkan flow step 1
    const firstStep = document.querySelector('.pk-step[data-flow="1"]');
    if (firstStep) firstStep.classList.add('active-step');
    renderAll();
    // Muat data real dari API (picking + packed)
    loadFromAPI();
    // Focus scan input
    setTimeout(() => document.getElementById('scanItemInput')?.focus(), 300);
});
</script>
<style>
@keyframes fadeInUp {
    from { opacity:0; transform:translateY(8px); }
    to   { opacity:1; transform:translateY(0); }
}
@keyframes fadeInDown {
    from { opacity:0; transform:translateX(-50%) translateY(-8px); }
    to   { opacity:1; transform:translateX(-50%) translateY(0); }
}

/* ── Picking Checklist ── */
.pk-line-checklist {
    border-top: 1px solid #e2e8f0;
    padding: .5rem 0 .25rem;
    display: flex;
    flex-direction: column;
    gap: .15rem;
}
.pk-line-row {
    display: flex;
    align-items: flex-start;
    gap: .4rem;
    padding: .3rem .5rem;
    border-radius: 6px;
    transition: background .15s;
}
.pk-line-row.picked   { background: #f0fdf4; }
.pk-line-row.has-problem { background: #fef2f2; }
.pk-line-check-btn {
    flex: 1;
    display: flex;
    align-items: flex-start;
    gap: .45rem;
    background: none;
    border: none;
    cursor: pointer;
    text-align: left;
    padding: 0;
    font-size: .82rem;
    line-height: 1.4;
    color: #1e293b;
}
.pk-line-check-btn:hover { opacity: .75; }
.pk-checkbox  { font-size: 1.05rem; flex-shrink: 0; margin-top: .05rem; }
.pk-line-body { display: flex; flex-wrap: wrap; gap: .2rem .5rem; align-items: baseline; }
.pk-line-sku  { font-weight: 700; font-size: .75rem; color: #475569; }
.pk-line-name { color: #334155; }
.pk-line-qty  { font-weight: 700; color: #0f172a; }
.pk-line-actions { display: flex; align-items: center; gap: .25rem; flex-shrink: 0; }
.pk-btn-icon {
    background: none;
    border: none;
    cursor: pointer;
    font-size: 1rem;
    padding: .1rem .25rem;
    border-radius: 4px;
    line-height: 1;
    opacity: .6;
}
.pk-btn-icon:hover { opacity: 1; background: #f1f5f9; }
.pk-line-problem-tag {
    font-size: .7rem;
    font-weight: 600;
    color: #dc2626;
    background: #fee2e2;
    padding: .15rem .4rem;
    border-radius: 4px;
    max-width: 140px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
</style>
@endpush
