{{-- resources/views/sales/shipments/edit_order_first.blade.php --}}
@extends('layouts.app')

@section('title', 'Scan Order • ' . $shipment->code)

@push('head')
<style>
    :root {
        --sr-accent: #334155;
        --sr-accent-2: #1f2937;
        --sr-accent-bg: rgba(148, 163, 184, .08);
        --sr-text: #111827;
        --sr-muted: #64748b;
        --sr-mobile-nav-offset: calc(78px + env(safe-area-inset-bottom, 0px));
    }

    .sr-scan-page {
        max-width: 1100px;
        margin: 0 auto;
        padding: 0 .75rem 6rem;
        color: var(--sr-text);
        background: #f8fafc;
    }

    .sr-topbar {
        position: sticky;
        top: 0;
        z-index: 300;
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto;
        align-items: center;
        gap: .6rem;
        margin: 0 -.75rem;
        padding: .5rem .85rem;
        border-bottom: 1px solid rgba(148, 163, 184, .16);
        background: rgba(248, 250, 252, .92);
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
    }

    .sr-top-main { min-width: 0; }

    .sr-top-actions {
        display: inline-flex;
        align-items: center;
        gap: .4rem;
        justify-content: flex-end;
    }

    .sr-title {
        margin: 0;
        font-size: 1.05rem;
        font-weight: 750;
        letter-spacing: .04em;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .sr-sub {
        color: var(--sr-muted);
        font-size: .77rem;
        font-weight: 500;
    }

    .sr-shell {
        display: grid;
        gap: .5rem;
        margin-top: .5rem;
    }

    .sr-workflow-stepper {
        display: flex;
        align-items: center;
        gap: .35rem;
        flex-wrap: wrap;
        padding: .3rem 0;
        border: 0;
        border-radius: 0;
        background: transparent;
        box-shadow: none;
    }

    .sr-flow-step {
        display: inline-flex;
        align-items: center;
        min-height: 28px;
        padding: .18rem .5rem;
        border-radius: 5px;
        border: 1px solid rgba(148, 163, 184, .2);
        color: #64748b;
        font-size: .72rem;
        font-weight: 500;
        white-space: nowrap;
    }

    .sr-flow-step.active {
        color: #fff;
        background: var(--sr-accent);
        border-color: var(--sr-accent);
    }

    .sr-flow-step.done {
        color: #334155;
        background: transparent;
    }

    .sr-flow-sep {
        color: #cbd5e1;
        font-size: .72rem;
    }

    .sr-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 38px;
        border-radius: 8px;
        padding: .38rem 1rem;
        border: 1px solid rgba(148, 163, 184, .5);
        background: transparent;
        color: #6b7280;
        font-size: .77rem;
        font-weight: 500;
        letter-spacing: .05em;
        text-transform: uppercase;
        text-decoration: none;
        cursor: pointer;
        transition: background .12s, color .12s, border-color .12s;
    }

    .sr-btn:hover {
        background: rgba(226, 232, 240, .7);
        color: #374151;
    }

    .sr-btn-primary {
        border-color: var(--sr-accent);
        background: var(--sr-accent);
        color: #fff;
        font-weight: 650;
    }

    .sr-btn-primary:hover {
        border-color: var(--sr-accent-2);
        background: var(--sr-accent-2);
        color: #fff;
    }

    .sr-btn-danger {
        border-color: rgba(185, 28, 28, .28);
        color: #991b1b;
        background: transparent;
    }

    .sr-btn-danger:hover {
        border-color: rgba(185, 28, 28, .4);
        color: #7f1d1d;
        background: rgba(254, 242, 242, .75);
    }

    .sr-btn:disabled,
    .sr-btn.is-disabled,
    .sr-btn[aria-disabled="true"] {
        opacity: .45;
        cursor: not-allowed;
        pointer-events: none;
    }

    .sr-btn:focus-visible,
    .sr-mini-btn:focus-visible,
    .sr-qty-input:focus-visible,
    .sr-scan-input:focus-visible {
        outline: 0;
        box-shadow: 0 0 0 .18rem var(--sr-accent-bg) !important;
    }

    .sr-panel {
        border: 1px solid rgba(148, 163, 184, .12);
        border-radius: 8px;
        background: #fff;
        overflow: hidden;
        box-shadow: none;
    }

    .sr-panel-body { padding: .72rem .85rem; }

    .sr-meta-panel {
        background: transparent;
        border-color: transparent;
    }

    .sr-meta-panel .sr-panel-body { padding: .12rem .15rem; }

    .sr-meta {
        display: flex;
        align-items: center;
        gap: .45rem;
        flex-wrap: wrap;
    }

    .sr-meta-item {
        border: 0;
        border-radius: 0;
        padding: 0;
        background: transparent;
        min-width: 0;
        box-shadow: none;
    }

    .sr-meta-label {
        color: #9ca3af;
        font-size: .65rem;
        text-transform: uppercase;
        letter-spacing: .08em;
        font-weight: 650;
    }

    .sr-meta-value {
        color: #334155;
        font-size: .82rem;
        font-weight: 650;
    }

    .sr-summary {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: .45rem;
    }

    .sr-stat {
        border: 1px solid rgba(148, 163, 184, .12);
        border-radius: 8px;
        padding: .55rem .7rem;
        background: #fff;
    }

    .sr-stat-label {
        color: #94a3b8;
        font-size: .62rem;
        text-transform: uppercase;
        letter-spacing: .08em;
        font-weight: 650;
    }

    .sr-stat-value {
        margin-top: .1rem;
        color: #0f172a;
        font-size: 1.05rem;
        font-weight: 800;
    }

    .sr-scan-card {
        display: grid;
        gap: .65rem;
    }

    .sr-mode-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: .5rem;
    }

    .sr-mode {
        display: inline-flex;
        align-items: center;
        min-height: 26px;
        border-radius: 999px;
        padding: .18rem .68rem;
        background: var(--sr-accent);
        color: #fff;
        font-size: .7rem;
        font-weight: 800;
        letter-spacing: .06em;
    }

    .sr-current {
        color: #64748b;
        font-size: .8rem;
        font-weight: 650;
        text-align: right;
    }

    .sr-scan-input {
        min-height: 72px;
        border-radius: 10px !important;
        border: 2px solid rgba(37, 99, 235, .18) !important;
        font-size: 1.35rem;
        font-weight: 800;
        letter-spacing: .03em;
        padding: .8rem 1rem;
        text-transform: uppercase;
    }

    .sr-scan-input:focus {
        border-color: var(--sr-accent) !important;
        box-shadow: 0 0 0 .18rem var(--sr-accent-bg) !important;
    }

    .sr-order-section { min-height: 180px; }

    .sr-order-tools {
        padding: .55rem .7rem;
        border-bottom: 1px solid rgba(148, 163, 184, .1);
        background: #f8fafc;
    }

    .sr-order-search {
        min-height: 42px;
        border-radius: 8px !important;
        font-size: .9rem;
    }

    .sr-order-panel-body {
        max-height: 52vh;
        overflow: auto;
    }

    .sr-orders {
        display: grid;
        gap: .5rem;
    }

    .sr-empty {
        padding: 1rem;
        border-radius: 8px;
        background: #f8fafc;
        color: #94a3b8;
        font-size: .86rem;
        text-align: center;
    }

    .sr-order {
        border: 1px solid rgba(148, 163, 184, .15);
        border-radius: 8px;
        background: #fff;
        overflow: hidden;
    }

    .sr-order.active {
        border-color: rgba(37, 99, 235, .35);
        box-shadow: 0 0 0 2px rgba(37, 99, 235, .06);
    }

    .sr-order-head {
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto auto;
        align-items: center;
        gap: .5rem;
        padding: .55rem .7rem;
        cursor: pointer;
    }

    .sr-order-code {
        color: #0f172a;
        font-size: .95rem;
        font-weight: 850;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .sr-order-info {
        color: #94a3b8;
        font-size: .72rem;
        margin-top: .1rem;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .sr-order-qty {
        min-width: 38px;
        border-radius: 999px;
        padding: .14rem .45rem;
        background: #e5e7eb;
        color: #374151;
        text-align: center;
        font-size: .78rem;
        font-weight: 800;
    }

    .sr-order-chevron {
        color: #94a3b8;
        font-size: .78rem;
        font-weight: 800;
    }

    .sr-order-items {
        border-top: 1px solid rgba(148, 163, 184, .1);
        padding: .4rem .55rem .55rem;
        display: grid;
        gap: .35rem;
    }

    .sr-item-row {
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto;
        align-items: center;
        gap: .5rem;
        padding: .45rem .55rem;
        border-radius: 7px;
        background: #f8fafc;
    }

    .sr-item-code {
        font-weight: 800;
        color: #0f172a;
        font-size: .85rem;
    }

    .sr-item-name {
        color: #94a3b8;
        font-size: .7rem;
        line-height: 1.2;
        margin-top: .08rem;
    }

    .sr-item-qty {
        font-weight: 850;
        color: #334155;
        font-size: .85rem;
    }

    .sr-actions {
        display: flex;
        align-items: center;
        gap: .5rem;
        justify-content: flex-end;
        padding: .65rem 0 0;
    }

    .sr-toast {
        position: fixed;
        left: 50%;
        bottom: 1.25rem;
        z-index: 10000;
        transform: translateX(-50%);
        display: none;
        max-width: min(92vw, 520px);
        border-radius: 999px;
        padding: .72rem 1rem;
        background: #0f172a;
        color: #fff;
        font-size: .86rem;
        font-weight: 700;
        box-shadow: 0 16px 40px rgba(15, 23, 42, .2);
    }

    .sr-toast.show { display: block; }
    .sr-toast.ok { background: #0f172a; }
    .sr-toast.error { background: #991b1b; }

    @media (max-width: 640px) {
        .sr-scan-page {
            padding: 0 .45rem 6rem;
        }
        .sr-topbar {
            margin: 0 -.45rem;
            padding: .46rem .55rem;
            grid-template-columns: minmax(0, 1fr);
        }
        .sr-title {
            max-width: calc(100vw - 1.1rem);
            font-size: .92rem;
            letter-spacing: .02em;
        }
        .sr-sub,
        .sr-top-actions .sr-btn[href] {
            display: none;
        }
        .sr-top-actions {
            grid-column: 1 / -1;
            justify-content: stretch;
        }
        .sr-top-actions .sr-btn {
            width: 100%;
            min-height: 34px;
            font-size: .64rem;
        }
        .sr-shell {
            gap: .45rem;
            margin-top: .45rem;
        }
        .sr-workflow-stepper {
            gap: .24rem;
            padding: .38rem .45rem;
            overflow-x: auto;
            flex-wrap: nowrap;
            border-radius: 6px;
            scrollbar-width: none;
        }
        .sr-workflow-stepper::-webkit-scrollbar { display: none; }
        .sr-flow-step {
            min-height: 26px;
            padding: .15rem .42rem;
            font-size: .64rem;
            font-weight: 550;
        }
        .sr-flow-sep { font-size: .62rem; }
        .sr-meta-panel {
            background: transparent;
            border: 0;
            box-shadow: none;
        }
        .sr-meta-panel .sr-panel-body { padding: 0; }
        .sr-meta { gap: .4rem; }
        .sr-meta-item {
            display: none;
            border-radius: 6px;
            padding: .5rem .65rem;
            box-shadow: none;
        }
        .sr-meta-store { display: block; }
        .sr-meta-label { display: none; }
        .sr-meta-value {
            margin-top: 0;
            font-size: .8rem;
            line-height: 1.15;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .sr-panel { border-radius: 6px; }
        .sr-panel-body { padding: .5rem; }
        .sr-scan-card { gap: .48rem; }
        .sr-mode-row {
            align-items: center;
            flex-wrap: nowrap;
        }
        .sr-mode {
            padding: .16rem .56rem;
            font-size: .68rem;
            letter-spacing: .03em;
            white-space: nowrap;
        }
        .sr-current {
            min-width: 0;
            overflow: hidden;
            text-align: right;
            text-overflow: ellipsis;
            white-space: nowrap;
            font-size: .7rem;
        }
        .sr-scan-input {
            min-height: 62px;
            border-radius: 8px !important;
            font-size: 1.08rem;
            padding: .7rem .75rem;
        }
        .sr-scan-input::placeholder { font-size: .84rem; }
        .sr-summary { gap: .3rem; }
        .sr-stat {
            padding: .28rem .38rem;
            border-radius: 6px;
            box-shadow: none;
        }
        .sr-stat-label {
            font-size: .54rem;
            letter-spacing: 0;
        }
        .sr-stat-value {
            margin-top: 0;
            font-size: .76rem;
            font-weight: 600;
        }
        .sr-orders { gap: .42rem; }
        .sr-order-panel-body {
            max-height: none;
            overflow: visible;
        }
        .sr-order-tools { padding: .42rem .5rem; }
        .sr-order-search {
            min-height: 40px;
            border-radius: 8px !important;
            font-size: .86rem;
            padding: .48rem .65rem;
        }
        .sr-order { border-radius: 6px; }
        .sr-order-head {
            padding: .46rem .58rem;
            align-items: center;
        }
        .sr-order-info { display: none; }
        .sr-order-code {
            font-size: .9rem;
            max-width: calc(100vw - 6rem);
        }
        .sr-order-qty {
            min-width: 34px;
            padding: .12rem .42rem;
            font-size: .74rem;
        }
        .sr-actions {
            position: fixed;
            left: 0;
            right: 0;
            bottom: var(--sr-mobile-nav-offset);
            z-index: 9998;
            padding: .55rem;
            border: 0;
            border-radius: 0;
            background: transparent;
            box-shadow: none;
            backdrop-filter: none;
            -webkit-backdrop-filter: none;
        }
        .sr-actions .sr-btn {
            flex: 1;
            min-height: 48px;
            padding: .45rem .65rem;
            font-size: .72rem;
        }
        .sr-toast {
            bottom: calc(var(--sr-mobile-nav-offset) + 4.25rem);
        }
    }
</style>
@endpush

@section('content')
@php
    $initialOrders = collect($savedOrderScans ?? [])
        ->map(function ($scan) {
            return [
                'code' => strtoupper((string) ($scan['code'] ?? $scan['order_no'] ?? '')),
                'label' => $scan['label'] ?? 'Pencatatan order',
                'items' => $scan['items'] ?? [],
            ];
        })
        ->filter(fn ($scan) => $scan['code'] !== '')
        ->values();

    $initialLines = $shipment->lines
        ->map(fn ($line) => [
            'id' => $line->id,
            'order_scan_id' => $line->shipment_order_scan_id,
            'item_id' => $line->item_id,
            'code' => $line->item->code ?? '-',
            'name' => $line->item->name ?? '',
            'qty' => (int) $line->qty_scanned,
        ])
        ->values();
@endphp

<div class="sr-scan-page">
    <div class="sr-topbar">
        <div class="sr-top-main">
            <h1 class="sr-title">{{ $shipment->code }}</h1>
            <div class="sr-sub">Pencatatan shipment mandiri · scan order untuk mulai.</div>
        </div>
        <div class="sr-top-actions">
            <button type="button" id="gfidScanSoundToggle" class="gf-scan-sound-toggle" aria-pressed="true">🔊 Suara ON</button>
            @if ($shipment->status === 'draft')
                <a href="{{ route('sales.shipments.cancel_form', $shipment) }}" class="sr-btn sr-btn-danger">Batalkan</a>
            @endif
            <a href="{{ route('sales.shipments.edit', $shipment) }}" class="sr-btn">Scan Item</a>
            <a href="{{ route('sales.shipments.show', $shipment) }}" class="sr-btn">Detail</a>
        </div>
    </div>

    <div class="sr-shell">
        <div class="sr-workflow-stepper" id="shipmentWorkflowStepper" aria-label="Workflow Shipment">
            <span class="sr-flow-step active" data-flow-step="order">Scan Order</span>
            <span class="sr-flow-sep">-&gt;</span>
            <span class="sr-flow-step" data-flow-step="item">Scan Item</span>
            <span class="sr-flow-sep">-&gt;</span>
            <span class="sr-flow-step" data-flow-step="review">Cek Shipment</span>
            <span class="sr-flow-sep">-&gt;</span>
            <span class="sr-flow-step" data-flow-step="confirm">Submit</span>
        </div>

        <div class="sr-panel sr-meta-panel">
            <div class="sr-panel-body">
                <div class="sr-meta">
                    <div class="sr-meta-item sr-meta-store">
                        <div class="sr-meta-label">Tipe</div>
                        <div class="sr-meta-value">{{ ucfirst($shipment->shipment_type ?? 'manual') }}</div>
                    </div>
                    <div class="sr-meta-item sr-meta-store">
                        <div class="sr-meta-label">Channel</div>
                        <div class="sr-meta-value">{{ $shipment->store ? (($shipment->store->code ?? '-') . ' - ' . ($shipment->store->name ?? '-')) : 'Belum dihubungkan' }}</div>
                    </div>
                    <div class="sr-meta-item">
                        <div class="sr-meta-label">Tanggal</div>
                        <div class="sr-meta-value">{{ optional($shipment->date)->format('d M Y') }}</div>
                    </div>
                    <div class="sr-meta-item">
                        <div class="sr-meta-label">Status</div>
                        <div class="sr-meta-value">{{ ucfirst($shipment->status) }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="sr-summary">
            <div class="sr-stat">
                <div class="sr-stat-label">Pesanan</div>
                <div class="sr-stat-value" id="sumOrders">0</div>
            </div>
            <div class="sr-stat">
                <div class="sr-stat-label">Item</div>
                <div class="sr-stat-value" id="sumItems">0</div>
            </div>
            <div class="sr-stat">
                <div class="sr-stat-label">Qty</div>
                <div class="sr-stat-value" id="sumQty">0</div>
            </div>
        </div>

        @if ($shipment->status === 'draft')
            <div class="sr-panel">
                <div class="sr-panel-body">
                    <div class="sr-scan-card">
                        <div class="sr-mode-row">
                            <span class="sr-mode" id="modeBadge">SCAN ORDER</span>
                            <span class="sr-current" id="currentLabel">Belum ada pesanan aktif</span>
                        </div>
                        <input type="text"
                               id="scanInput"
                               class="form-control sr-scan-input"
                               placeholder="Scan nomor pesanan"
                               inputmode="text"
                               autocomplete="off"
                               autofocus>
                    </div>
                </div>
            </div>
        @endif

        <div class="sr-panel sr-order-section">
            <div class="sr-order-tools" id="orderTools">
                <input type="search" id="orderSearch" class="form-control sr-order-search" placeholder="Cari order" autocomplete="off">
            </div>
            <div class="sr-panel-body sr-order-panel-body">
                <div class="sr-orders" id="ordersWrap">
                    <div class="sr-empty">Belum ada item shipment.</div>
                </div>
            </div>
        </div>

        <div class="sr-actions">
            @if ($shipment->status === 'draft')
                <button type="button" class="sr-btn" id="nextOrderBtn">Order Baru</button>
                <a href="{{ route('sales.shipments.confirm_orders', $shipment) }}"
                   class="sr-btn sr-btn-primary"
                   id="submitBtn"
                   aria-disabled="true">Cek Shipment</a>
            @else
                <a href="{{ route('sales.shipments.show', $shipment) }}" class="sr-btn">Detail</a>
                <a href="{{ route('sales.shipments.index') }}" class="sr-btn sr-btn-primary">Daftar Shipment</a>
            @endif
        </div>
    </div>
</div>

<div id="toast" class="sr-toast"></div>
@endsection

@push('scripts')
<script>
(function () {
    const scanUrl = @json(route('sales.shipments.scan_item', $shipment));
    const recordOrderUrl = @json(route('sales.shipments.scan_order_store', $shipment));
    const deleteOrderUrl = @json(route('sales.shipments.delete_order_scan', $shipment));
    const updateQtyUrlTemplate = @json(route('sales.shipments.update_line_qty', '__LINE_ID__'));
    const deleteLineUrlTemplate = @json(route('sales.shipments.destroy_line', '__LINE_ID__'));
    const csrf = @json(csrf_token());
    const isDraft = @json($shipment->status === 'draft');
    const initialOrders = @json($initialOrders);
    const initialLines = @json($initialLines);

    const state = { mode: 'order', current: null, expanded: null, search: '', orders: [] };
    const lastScanStack = [];

    const scanInput = document.getElementById('scanInput');
    const modeBadge = document.getElementById('modeBadge');
    const currentLabel = document.getElementById('currentLabel');
    const workflowStepper = document.getElementById('shipmentWorkflowStepper');
    const nextOrderBtn = document.getElementById('nextOrderBtn');
    const orderSearch = document.getElementById('orderSearch');
    const ordersWrap = document.getElementById('ordersWrap');
    const sumOrders = document.getElementById('sumOrders');
    const sumItems = document.getElementById('sumItems');
    const sumQty = document.getElementById('sumQty');
    const submitBtn = document.getElementById('submitBtn');
    const toastEl = document.getElementById('toast');

    window.GFID?.bindScanSoundToggle(document.getElementById('gfidScanSoundToggle'));

    let toastTimer = null;
    let audioCtx = null;

    function normalize(value) {
        const normalized = String(value ?? '').trim();
        if (!normalized || ['undefined', 'null', 'nan'].includes(normalized.toLowerCase())) return '';
        return normalized.toUpperCase();
    }

    function isNextOrderCommand(code) {
        return ['ORDER BARU', 'BARU', 'NEXT', 'NEXT ORDER', 'ORDER NEXT'].includes(normalize(code));
    }

    function isResetOrderCommand(code) {
        return ['RESET', 'RESET ORDER'].includes(normalize(code));
    }

    function isUndoCommand(code) {
        return ['UNDO', 'BATAL'].includes(normalize(code));
    }

    function esc(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function audioContext() {
        try {
            const Ctx = window.AudioContext || window.webkitAudioContext;
            if (!Ctx) return null;
            audioCtx = audioCtx || new Ctx();
            return audioCtx;
        } catch (_) {
            return null;
        }
    }


    function playTone(type = 'ok', fromConfig = false) {
        const eventMap = {
            ok: 'item_success',
            item: 'item_success',
            order: 'order_success',
            orderRepeat: 'order_duplicate',
            next: 'navigation',
            undo: 'undo',
            reset: 'reset',
            errorGuard: 'item_duplicate',
            errorNoOrder: 'order_not_found',
            errorNetwork: 'error_network',
            errorItem: 'error_general',
            error: 'error_general',
        };
        if (!fromConfig && window.GFID && typeof window.GFID.playScanSound === 'function') {
            return window.GFID.playScanSound(eventMap[type] || type, () => playTone(type, true));
        }
        if (window.GFID && typeof window.GFID.isScanSoundEnabled === 'function' && !window.GFID.isScanSoundEnabled()) return;
        if (!window.AudioContext && !window.webkitAudioContext) return;

        const AudioCtx = window.AudioContext || window.webkitAudioContext;
        const ctx = new AudioCtx();

        const presets = {
            // proses normal
            ok: [
                { freq: 880, duration: 0.055, gap: 0 },
                { freq: 1175, duration: 0.065, gap: 0.045 },
            ],
            item: [
                { freq: 932, duration: 0.045, gap: 0 },
                { freq: 1244, duration: 0.05, gap: 0.035 },
            ],
            order: [
                { freq: 587, duration: 0.07, gap: 0 },
                { freq: 784, duration: 0.08, gap: 0.045 },
            ],
            orderRepeat: [
                { freq: 659, duration: 0.055, gap: 0 },
                { freq: 659, duration: 0.055, gap: 0.04 },
            ],
            next: [
                { freq: 523, duration: 0.06, gap: 0 },
                { freq: 698, duration: 0.06, gap: 0.035 },
                { freq: 880, duration: 0.075, gap: 0.035 },
            ],

            // aksi koreksi
            undo: [
                { freq: 784, duration: 0.055, gap: 0 },
                { freq: 523, duration: 0.075, gap: 0.04 },
            ],
            reset: [
                { freq: 440, duration: 0.07, gap: 0 },
                { freq: 330, duration: 0.07, gap: 0.05 },
                { freq: 220, duration: 0.09, gap: 0.05 },
            ],

            // guard / error
            errorGuard: [
                { freq: 196, duration: 0.09, gap: 0 },
                { freq: 196, duration: 0.09, gap: 0.055 },
            ],
            errorNoOrder: [
                { freq: 220, duration: 0.08, gap: 0 },
                { freq: 165, duration: 0.105, gap: 0.055 },
            ],
            errorItem: [
                { freq: 180, duration: 0.09, gap: 0 },
                { freq: 140, duration: 0.12, gap: 0.055 },
            ],
            errorNetwork: [
                { freq: 155, duration: 0.08, gap: 0 },
                { freq: 120, duration: 0.08, gap: 0.05 },
                { freq: 100, duration: 0.11, gap: 0.05 },
            ],
            error: [
                { freq: 180, duration: 0.09, gap: 0 },
                { freq: 140, duration: 0.12, gap: 0.055 },
            ],
        };

        const notes = presets[type] || presets.ok;

        let cursor = ctx.currentTime;

        notes.forEach(note => {
            cursor += Number(note.gap || 0);

            const oscillator = ctx.createOscillator();
            const gain = ctx.createGain();

            oscillator.type = 'sine';
            oscillator.frequency.setValueAtTime(note.freq, cursor);

            gain.gain.setValueAtTime(0.0001, cursor);
            gain.gain.exponentialRampToValueAtTime(0.08, cursor + 0.01);
            gain.gain.exponentialRampToValueAtTime(0.0001, cursor + Number(note.duration || 0.06));

            oscillator.connect(gain);
            gain.connect(ctx.destination);

            oscillator.start(cursor);
            oscillator.stop(cursor + Number(note.duration || 0.06) + 0.025);

            cursor += Number(note.duration || 0.06);
        });

        window.setTimeout(() => {
            try { ctx.close(); } catch (e) {}
        }, Math.ceil((cursor - ctx.currentTime) * 1000) + 180);
    }

    function toast(type, message) {
        if (!toastEl) return;
        clearTimeout(toastTimer);
        toastEl.className = 'sr-toast show ' + (type || 'ok');
        toastEl.textContent = message;
        toastTimer = setTimeout(() => {
            toastEl.className = 'sr-toast';
        }, 1700);
    }

    function alertError(message, toneType = 'error') {
        const errorMessage = message || 'Terjadi kesalahan.';
        playTone(toneType || 'error');

        if (window.GFID && typeof window.GFID.errorAlert === 'function') {
            const alertPromise = window.GFID.errorAlert(errorMessage);
            if (alertPromise && typeof alertPromise.then === 'function') {
                alertPromise.then(() => {
                    focusScan({ preventScroll: true });
                });
                return;
            }
        }

        toast('error', errorMessage);
        focusScan({ preventScroll: true });
    }

    function focusScan(options = {}) {
        if (!scanInput || !isDraft) return;
        setTimeout(() => {
            try {
                scanInput.focus(options);
                scanInput.select();
            } catch (_) {
                scanInput.focus();
            }
        }, 40);
    }

    function findOrder(code) {
        code = normalize(code);
        return state.orders.find(order => order.code === code);
    }

    function ensureOrder(code, meta = {}) {
        code = normalize(code);
        if (!code) return null;

        let order = findOrder(code);
        if (!order) {
            order = {
                code,
                label: meta.label || 'Manual',
                items: Array.isArray(meta.items) ? meta.items : []
            };
            state.orders.push(order);
        }

        state.current = order.code;
        state.expanded = order.code;
        render();
        return order;
    }

    function activeOrder() {
        return state.current ? findOrder(state.current) : null;
    }


    function latestOrder() {
        if (state.current) return activeOrder();
        return state.orders.length ? state.orders[state.orders.length - 1] : null;
    }

    function setMode(mode) {
        state.mode = mode === 'item' ? 'item' : 'order';

        if (modeBadge) {
            modeBadge.textContent = state.mode === 'order' ? 'SCAN ORDER' : 'SCAN ITEM';
        }

        if (currentLabel) {
            const order = activeOrder();
            currentLabel.textContent = order
                ? `Order aktif: ${order.code}`
                : 'Belum ada pesanan aktif';
        }

        if (scanInput) {
            scanInput.placeholder = state.mode === 'order'
                ? 'Scan nomor pesanan'
                : 'Scan item / barcode barang';
        }

        workflowStepper?.querySelectorAll('[data-flow-step]').forEach(step => {
            const key = step.dataset.flowStep;
            step.classList.remove('active', 'done');

            if (state.mode === 'order') {
                if (key === 'order') step.classList.add('active');
            } else {
                if (key === 'order') step.classList.add('done');
                if (key === 'item') step.classList.add('active');
            }
        });

        render();
        focusScan({ preventScroll: true });
    }

    function upsertItem(orderCode, item) {
        const order = ensureOrder(orderCode || 'MANUAL');
        if (!order) return;

        const key = String(item.line_id || item.id || item.code || '').toUpperCase();
        let row = order.items.find(existing => {
            const existingKey = String(existing.line_id || existing.id || existing.code || '').toUpperCase();
            return existingKey === key;
        });

        if (row) {
            row.qty = Number(item.qty || row.qty || 0);
            row.name = item.name || row.name;
            row.code = item.code || row.code;
            row.line_id = item.line_id || row.line_id;
        } else {
            row = {
                line_id: item.line_id || item.id || null,
                item_id: item.item_id || null,
                code: item.code || item.item_code || key,
                name: item.name || item.item_name || '',
                qty: Number(item.qty || item.qty_scanned || 1),
            };
            order.items.push(row);
        }

        state.expanded = order.code;
        render();
    }

    function filteredOrders() {
        const query = normalize(state.search);
        if (!query) return state.orders;
        return state.orders.filter(order => {
            if (order.code.includes(query)) return true;
            return Object.values(order.items || {}).some(item => {
                return normalize(item.code).includes(query) || normalize(item.name).includes(query);
            });
        });
    }

    function render() {
        const orders = filteredOrders();
        let totalItems = 0;
        let totalQty = 0;

        state.orders.forEach(order => {
            (order.items || []).forEach(item => {
                totalItems += 1;
                totalQty += Number(item.qty || 0);
            });
        });

        if (sumOrders) {
            sumOrders.textContent = state.orders.filter(order => order.code !== 'BELUM DIKELOMPOKKAN').length;
        }
        if (sumItems) sumItems.textContent = totalItems || initialLines.length || 0;
        if (sumQty) {
            const initialQty = (initialLines || []).reduce((total, line) => total + Number(line.qty || 0), 0);
            sumQty.textContent = totalQty || initialQty || 0;
        }

        if (submitBtn) {
            const hasItems = totalQty > 0 || (initialLines || []).some(line => Number(line.qty || 0) > 0);
            submitBtn.setAttribute('aria-disabled', hasItems ? 'false' : 'true');
            submitBtn.title = hasItems ? 'Cek shipment sebelum submit' : 'Scan item terlebih dahulu';
        }

        if (!ordersWrap) return;

        if (!orders.length) {
            ordersWrap.innerHTML = '<div class="sr-empty">Belum ada item shipment.</div>';
            return;
        }

        ordersWrap.innerHTML = orders.slice().reverse().map(order => {
            const items = order.items || [];
            const qty = items.reduce((total, item) => total + Number(item.qty || 0), 0);
            const isActive = state.current === order.code;
            const expanded = state.expanded === order.code || isActive;

            const rows = items.length
                ? items.map(item => `
                    <div class="sr-item-row">
                        <div>
                            <div class="sr-item-code">${esc(item.code || '-')}</div>
                            <div class="sr-item-name">${esc(item.name || '')}</div>
                        </div>
                        <div class="sr-item-qty">x${Number(item.qty || 0)}</div>
                    </div>
                `).join('')
                : '<div class="sr-empty">Belum ada item untuk order ini.</div>';

            return `
                <div class="sr-order ${isActive ? 'active' : ''}" data-order-code="${esc(order.code)}">
                    <div class="sr-order-head" data-toggle-order="${esc(order.code)}">
                        <div>
                            <div class="sr-order-code">${esc(order.code)}</div>
                            <div class="sr-order-info">${esc(order.label || ('{{ $shipment->shipment_type ?? 'manual' }}' === 'marketplace' ? 'Marketplace' : 'Manual'))}</div>
                        </div>
                        <div class="sr-order-qty">${qty}</div>
                        <div class="sr-order-chevron">${expanded ? '▲' : '▼'}</div>
                    </div>
                    ${expanded ? `<div class="sr-order-items">${rows}</div>` : ''}
                </div>
            `;
        }).join('');

        ordersWrap.querySelectorAll('[data-toggle-order]').forEach(el => {
            el.addEventListener('click', function () {
                const code = this.dataset.toggleOrder;
                state.expanded = state.expanded === code ? null : code;
                render();
            });
        });
    }



    function decrementItemLocal(orderCode, lineId, nextQty) {
        const order = findOrder(orderCode);
        if (!order || !Array.isArray(order.items)) return;

        const targetId = String(lineId || '');
        const item = order.items.find(row => String(row.line_id || row.id || '') === targetId);

        if (!item) return;

        item.qty = Math.max(0, Number(nextQty || 0));

        if (item.qty <= 0) {
            order.items = order.items.filter(row => String(row.line_id || row.id || '') !== targetId);
        }

        if (!order.items.length) {
            state.expanded = order.code;
        }

        render();
    }



    function findLocalOrderItem(orderCode, lineId, itemCode = null) {
        const order = findOrder(orderCode);

        if (!order || !Array.isArray(order.items)) {
            return { order: order || null, item: null };
        }

        const targetId = String(lineId || '');
        const targetCode = String(itemCode || '').toUpperCase();

        let item = null;

        if (targetId) {
            item = order.items.find(row => String(row.line_id || row.id || '') === targetId) || null;
        }

        if (!item && targetCode) {
            item = order.items.find(row => String(row.code || row.item_code || '').toUpperCase() === targetCode) || null;
        }

        return { order, item };
    }

    function readItemQty(item) {
        if (!item) return 0;

        const rawQty = item.qty ?? item.qty_scanned ?? item.quantity ?? 0;
        return Math.max(0, Number(rawQty || 0));
    }

    function writeItemQty(item, qty) {
        if (!item) return;

        const safeQty = Math.max(0, Number(qty || 0));

        item.qty = safeQty;
        item.qty_scanned = safeQty;
        item.quantity = safeQty;
    }

    function setItemLocalQty(orderCode, lineId, nextQty, itemCode = null) {
        const found = findLocalOrderItem(orderCode, lineId, itemCode);
        const order = found.order;
        const item = found.item;

        if (!order || !Array.isArray(order.items)) return;

        const safeQty = Math.max(0, Number(nextQty || 0));

        if (item && safeQty > 0) {
            writeItemQty(item, safeQty);
        }

        if (safeQty <= 0) {
            const targetId = String(lineId || '');
            const targetCode = String(itemCode || '').toUpperCase();

            order.items = order.items.filter(row => {
                const sameId = targetId && String(row.line_id || row.id || '') === targetId;
                const sameCode = targetCode && String(row.code || row.item_code || '').toUpperCase() === targetCode;

                return !(sameId || sameCode);
            });
        }

        // Order tetap aktif walaupun item kosong.
        state.current = order.code;
        state.expanded = order.code;

        render();
    }

    function updateItemLocalQty(orderCode, lineId, nextQty, itemCode = null) {
        setItemLocalQty(orderCode, lineId, nextQty, itemCode);
    }

    function removeItemLocal(orderCode, lineId) {
        const order = findOrder(orderCode);
        if (!order || !Array.isArray(order.items)) return;

        const targetId = String(lineId || '');

        order.items = order.items.filter(item => String(item.line_id || item.id || '') !== targetId);

        // Penting:
        // UNDO hanya menghapus item terakhir, bukan menghapus pesanan/order.
        // Jadi walaupun items kosong, order tetap dipertahankan agar operator bisa scan item lagi.
        state.current = order.code;
        state.expanded = order.code;

        render();
    }


    function updateLineQty(lineId, qty) {
        if (!lineId) return Promise.resolve(false);

        const url = updateQtyUrlTemplate.replace('__LINE_ID__', lineId);

        return fetch(url, {
            method: 'PATCH',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrf,
            },
            body: JSON.stringify({
                qty: Math.max(0, Number(qty || 0))
            })
        }).then(async response => {
            const payload = await response.json().catch(() => ({}));
            if (!response.ok) throw new Error(payload.message || 'Gagal update qty');
            return payload;
        });
    }

    function deleteLine(lineId) {
        if (!lineId) return Promise.resolve(false);

        const url = deleteLineUrlTemplate.replace('__LINE_ID__', lineId);

        return fetch(url, {
            method: 'DELETE',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrf,
            },
            body: JSON.stringify({})
        }).then(async response => {
            const payload = await response.json().catch(() => ({}));
            if (!response.ok) throw new Error(payload.message || 'Gagal hapus item');
            return payload;
        });
    }

    function deleteOrderScan(orderNo) {
        return fetch(deleteOrderUrl, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrf,
            },
            body: JSON.stringify({ order_no: orderNo })
        }).then(async response => {
            const payload = await response.json().catch(() => ({}));
            if (!response.ok) throw new Error(payload.message || 'Gagal hapus pencatatan order');
            return payload;
        });
    }




    function isConfirmModalOpen() {
        return !!document.querySelector('.swal2-container.swal2-shown, .swal2-container');
    }

    function handleConfirmModalScanCommand(code) {
        if (!isConfirmModalOpen()) return false;

        if (isNextOrderCommand(code)) {
            const confirmButton = document.querySelector('.swal2-confirm');

            if (confirmButton) {
                playTone('next');
                confirmButton.click();
                return true;
            }
        }

        if (isResetOrderCommand(code)) {
            const cancelButton = document.querySelector('.swal2-cancel, .swal2-close, .swal2-deny');

            if (cancelButton) {
                playTone('undo');
                cancelButton.click();
                return true;
            }

            if (window.Swal && typeof Swal.close === 'function') {
                playTone('undo');
                Swal.close();
                return true;
            }
        }

        return false;
    }

    function nextOrderCommand() {
        // NEXT = selesai order aktif, lanjut scan nomor order berikutnya.
        state.current = null;
        state.expanded = null;
        state.search = '';

        if (orderSearch) {
            orderSearch.value = '';
        }

        playTone('next');
        toast('ok', 'Scan nomor order berikutnya');
        setMode('order');
        focusScan();
    }

    function startOrder(code) {
        code = normalize(code);
        if (!code) return;

        const existingOrder = findOrder(code);
        if (existingOrder) {
            state.current = existingOrder.code;
            state.expanded = existingOrder.code;
            playTone('orderRepeat');
            toast('ok', `Kembali ke order ${existingOrder.code}`);
            setMode('item');
            return;
        }

        fetch(recordOrderUrl, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrf,
            },
            body: JSON.stringify({ order_no: code })
        }).then(async response => {
            const payload = await response.json().catch(() => ({}));
            if (!response.ok) throw new Error(payload.message || 'Gagal mencatat nomor order');
            return payload;
        }).then(payload => {
            const orderCode = normalize(payload.order?.code || payload.order?.order_no || code);
            if (!orderCode) throw new Error('Nomor order tidak tersedia. Scan ulang nomor order tersebut.');
            ensureOrder(orderCode, { label: payload.order?.label || 'Pencatatan order' });
            playTone(payload.created ? 'order' : 'orderRepeat');
            toast('ok', payload.message || `Order ${orderCode} tercatat`);
            setMode('item');
        }).catch(error => {
            playTone('errorNetwork');
            alertError(error.message || 'Gagal mencatat nomor order.', 'errorNetwork');
        });
    }

    function scanItem(code) {
        code = normalize(code);
        if (!code) return;

        if (isNextOrderCommand(code)) {
            nextOrderCommand();
            return;
        }

        if (isResetOrderCommand(code)) {
            resetActiveOrderCommand();
            return;
        }

        if (isUndoCommand(code)) {
            undoLastItemCommand();
            return;
        }

        const order = activeOrder();

        if (!order) {
            playTone('errorNoOrder');
            alertError('Scan nomor order dulu sebelum scan item.', 'errorNoOrder');
            setMode('order');
            return;
        }

        state.expanded = order.code;
        state.search = '';
        if (orderSearch) orderSearch.value = '';

        fetch(scanUrl, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrf,
            },
            body: JSON.stringify({
                scan_code: code,
                qty: 1,
                order_no: order.code === 'MANUAL' ? '' : order.code
            })
        })
            .then(async response => {
                const payload = await response.json().catch(() => ({}));
                if (!response.ok) throw new Error(payload.message || 'Gagal scan item');
                return payload;
            })
            .then(payload => {
                const line = payload.line || payload.data?.line || null;

                if (line) {
                    const beforeScanFound = findLocalOrderItem(order.code, line.id, line.item_code || line.code || code);
                    const beforeScanQty = readItemQty(beforeScanFound.item);

                    upsertItem(order.code, {
                        line_id: line.id,
                        item_id: line.item_id,
                        code: line.item_code || line.code || code,
                        name: line.item_name || line.name || '',
                        qty: beforeScanQty + 1,
                    });

                    const afterScanItem = findLocalOrderItem(order.code, line.id, line.item_code || line.code || code).item;
                    lastScanStack.push({
                        orderCode: order.code,
                        lineId: line.id,
                        code: line.item_code || line.code || code,
                        qtyAfter: readItemQty(afterScanItem),
                        backendQtyAfter: Number(line.qty_scanned || line.qty || 1),
                    });

                    playTone('item');
                    toast('ok', payload.message || `+1 ${line.item_code || line.code || code}`);
                } else {
                    upsertItem(order.code, {
                        code,
                        name: '',
                        qty: 1,
                    });

                    playTone('item');
                    toast('ok', payload.message || `Berhasil scan ${code}`);
                }

                setMode('item');
            })
            .catch(error => {
                const toneType = (error.message || '').includes('tidak ditemukan') ? 'errorItem' : 'errorNetwork';
                playTone(toneType);
                alertError(error.message || 'Gagal scan item.', toneType);
            });
    }

    function resetActiveOrderCommand() {
        const order = activeOrder() || latestOrder();

        if (!order) {
            alertError('Belum ada order aktif untuk di-reset.');
            setMode('order');
            return;
        }

        const runReset = () => {
            const lineIds = (order.items || [])
                .map(item => item.line_id || item.id)
                .filter(Boolean);

            if (!lineIds.length) {
                deleteOrderScan(order.code)
                    .then(() => {
                        state.orders = state.orders.filter(row => row.code !== order.code);
                        state.current = null;
                        state.expanded = null;
                        render();
                        playTone('reset');
                        toast('ok', `Order ${order.code} di-reset`);
                        setMode('order');
                    })
                    .catch(error => alertError(error.message || 'Gagal reset order.', 'errorNetwork'));
                return;
            }

            Promise.all(lineIds.map(lineId => deleteLine(lineId).catch(error => ({ error }))))
                .then(results => {
                    const failed = results.filter(row => row && row.error);
                    if (failed.length) {
                        playTone('errorNetwork');
                        alertError('Sebagian item gagal di-reset. Cek ulang daftar item.', 'errorNetwork');
                        return;
                    }

                    deleteOrderScan(order.code)
                        .then(() => {
                            state.orders = state.orders.filter(row => row.code !== order.code);
                            state.current = null;
                            state.expanded = null;

                            for (let i = lastScanStack.length - 1; i >= 0; i--) {
                                if (lastScanStack[i].orderCode === order.code) {
                                    lastScanStack.splice(i, 1);
                                }
                            }

                            render();
                            playTone('reset');
                            toast('ok', `Order ${order.code} di-reset`);
                            setMode('order');
                        })
                        .catch(error => alertError(error.message || 'Gagal reset order.', 'errorNetwork'));
                });
        };

        if (window.Swal) {
            Swal.fire({
                icon: 'warning',
                title: 'Reset order?',
                text: `Item pada order ${order.code} akan dihapus dari shipment ini.`,
                showCancelButton: true,
                confirmButtonText: 'NEXT / Lanjut',
                cancelButtonText: 'RESET / Batal',
                confirmButtonColor: '#991b1b',
                cancelButtonColor: '#64748b',
            }).then(result => {
                if (result.isConfirmed) runReset();
            });
            return;
        }

        if (confirm(`Reset order ${order.code}?`)) runReset();
    }

    function undoLastItemCommand() {
        const last = lastScanStack.pop();

        if (!last || !last.lineId) {
            playTone('errorGuard');
            alertError('Belum ada item terakhir untuk di-undo.', 'errorGuard');
            return;
        }

        const found = findLocalOrderItem(last.orderCode, last.lineId, last.code);
        const order = found.order || findOrder(last.orderCode);
        const item = found.item;

        const currentUiQty = readItemQty(item) || Math.max(1, Number(last.qtyAfter || 1));
        const nextUiQty = Math.max(0, currentUiQty - 1);

        const backendQtyAfter = Math.max(1, Number(last.backendQtyAfter || last.qtyAfter || currentUiQty || 1));
        const nextBackendQty = Math.max(0, backendQtyAfter - 1);

        const request = nextBackendQty > 0
            ? updateLineQty(last.lineId, nextBackendQty)
            : deleteLine(last.lineId);

        request
            .then(() => {
                setItemLocalQty(last.orderCode, last.lineId, nextUiQty, last.code);

                playTone('undo');

                if (nextUiQty > 0) {
                    toast('ok', `Scan terakhir dibatalkan. Qty sekarang ${nextUiQty}`);
                } else {
                    toast('ok', 'Scan terakhir dibatalkan');
                }

                if (order) {
                    state.current = order.code;
                    state.expanded = order.code;
                    setMode('item');
                } else {
                    ensureOrder(last.orderCode, { label: 'Manual' });
                    state.current = last.orderCode;
                    state.expanded = last.orderCode;
                    setMode('item');
                }

                render();
            })
            .catch(error => {
                lastScanStack.push(last);
                playTone('errorNetwork');
                alertError(error.message || 'Gagal undo item terakhir.', 'errorNetwork');
            });
    }

    initialOrders.forEach(order => {
        ensureOrder(order.code, {
            label: order.label || 'Pencatatan order',
            items: order.items || [],
        });
    });

    const ungroupedLines = initialLines.filter(line => !line.order_scan_id);

    if (ungroupedLines.length) {
        ensureOrder('BELUM DIKELOMPOKKAN', { label: 'Item existing' });
    }

    ungroupedLines.forEach(line => {
        upsertItem('BELUM DIKELOMPOKKAN', {
            line_id: line.id,
            item_id: line.item_id,
            code: line.code,
            name: line.name,
            qty: line.qty,
        });
    });

    state.current = null;
    state.expanded = state.orders[0]?.code || null;
    state.search = '';
    render();

    orderSearch?.addEventListener('input', function () {
        state.search = this.value;
        render();
    });

    if (isDraft) {
        setMode('order');

        scanInput?.addEventListener('input', function () {
            this.value = this.value.toUpperCase();
        });

        scanInput?.addEventListener('keydown', function (event) {
            if (event.key !== 'Enter') return;

            event.preventDefault();

            const code = normalize(this.value);
            this.value = '';

            if (!code) return;

            if (handleConfirmModalScanCommand(code)) {
                return;
            }

            if (isNextOrderCommand(code)) {
                nextOrderCommand();
                return;
            }

            if (isResetOrderCommand(code)) {
                resetActiveOrderCommand();
                return;
            }

            if (isUndoCommand(code)) {
                undoLastItemCommand();
                return;
            }

            state.mode === 'order' ? startOrder(code) : scanItem(code);
        });


        document.addEventListener('keydown', function (event) {
            if (event.key !== 'Enter') return;
            if (!isConfirmModalOpen()) return;

            const target = event.target;
            const rawValue = target && 'value' in target ? target.value : '';
            const code = normalize(rawValue);

            if (!code) return;

            if (handleConfirmModalScanCommand(code)) {
                event.preventDefault();

                if (target && 'value' in target) {
                    target.value = '';
                }
            }
        }, true);

        nextOrderBtn?.addEventListener('click', function () {
            nextOrderCommand();
        });

        submitBtn?.addEventListener('click', function (event) {
            if (this.getAttribute('aria-disabled') === 'true') {
                event.preventDefault();
                alertError('Scan minimal satu item sebelum cek shipment.', 'errorGuard');
                return;
            }

            playTone('next');
        });

        window.addEventListener('load', function () {
            focusScan();
        });

        document.addEventListener('click', function (event) {
            if (event.target.closest('a,button,input,textarea,select')) return;
            focusScan({ preventScroll: true });
        });
    }
})();
</script>
@endpush
