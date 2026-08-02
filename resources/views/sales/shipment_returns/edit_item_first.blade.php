@extends('layouts.app')

@section('title', 'Scan Item Retur ' . $shipmentReturn->code)

@push('head')
<style>
    .srif-page { max-width: 980px; margin: 0 auto; padding: 0 .75rem 6rem; color: #111827; background: #f8fafc; }
    .srif-topbar { display: flex; align-items: center; justify-content: space-between; gap: .75rem; padding: .75rem 0; }
    .srif-title { margin: 0; font-size: 1.1rem; font-weight: 750; letter-spacing: .03em; }
    .srif-sub { color: #64748b; font-size: .76rem; }
    .srif-card { margin-top: .6rem; border: 1px solid rgba(148,163,184,.18); border-radius: 10px; background: #fff; overflow: hidden; }
    .srif-body { padding: .85rem; }
    .srif-stepper { display: grid; grid-template-columns: repeat(3, 1fr); gap: .35rem; margin-top: .4rem; }
    .srif-step { display: flex; align-items: center; gap: .45rem; min-height: 42px; padding: .4rem .55rem; border: 1px solid rgba(148,163,184,.22); border-radius: 8px; color: #94a3b8; font-size: .72rem; font-weight: 650; }
    .srif-step-number { display: inline-flex; align-items: center; justify-content: center; width: 24px; height: 24px; border-radius: 50%; background: #f1f5f9; color: #64748b; }
    .srif-step.active { border-color: #334155; background: #334155; color: #fff; }
    .srif-step.active .srif-step-number { background: #fff; color: #334155; }
    .srif-step.done { border-color: rgba(51,65,85,.3); color: #334155; }
    .srif-step.done .srif-step-number { background: #e2e8f0; color: #334155; }
    .srif-meta { display: flex; flex-wrap: wrap; gap: .55rem 1.2rem; color: #64748b; font-size: .75rem; }
    .srif-meta strong { color: #111827; }
    .srif-scan-title { margin: 0; font-size: .9rem; font-weight: 750; }
    .srif-help { margin: .2rem 0 .65rem; color: #64748b; font-size: .75rem; }
    .srif-input { width: 100%; min-height: 62px; border: 1px solid rgba(148,163,184,.4) !important; border-radius: 9px !important; padding: .7rem .85rem; color: #111827; font-size: 1.2rem; font-weight: 750; text-transform: uppercase; box-shadow: none !important; }
    .srif-input::placeholder { color: #94a3b8; font-size: .9rem; font-weight: 500; text-transform: none; }
    .srif-actions { display: flex; justify-content: flex-end; gap: .45rem; flex-wrap: wrap; margin-top: .7rem; }
    .srif-btn { display: inline-flex; align-items: center; justify-content: center; min-height: 38px; padding: .4rem .85rem; border: 1px solid rgba(148,163,184,.45); border-radius: 8px; background: #fff; color: #475569; font-size: .72rem; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; text-decoration: none; cursor: pointer; }
    .srif-btn-primary { border-color: #334155; background: #334155; color: #fff; }
    .srif-btn:disabled, .srif-btn.is-disabled { opacity: .45; pointer-events: none; }
    .srif-summary { display: grid; grid-template-columns: repeat(3, 1fr); gap: .4rem; margin-top: .6rem; }
    .srif-stat { padding: .48rem .6rem; border: 1px solid rgba(148,163,184,.16); border-radius: 8px; background: #fff; }
    .srif-stat-label { color: #94a3b8; font-size: .62rem; text-transform: uppercase; }
    .srif-stat-value { margin-top: .15rem; font-size: 1rem; font-weight: 750; }
    .srif-tabs { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .35rem; margin-top: .6rem; padding: .25rem; border: 1px solid rgba(148,163,184,.18); border-radius: 9px; background: #fff; }
    .srif-tab { min-height: 38px; border: 0; border-radius: 7px; background: transparent; color: #64748b; font-size: .72rem; font-weight: 750; cursor: pointer; }
    .srif-tab.active { background: #334155; color: #fff; }
    .srif-tab-count { display: inline-flex; align-items: center; justify-content: center; min-width: 22px; margin-left: .25rem; padding: .1rem .3rem; border-radius: 99px; background: rgba(148,163,184,.18); font-size: .65rem; }
    .srif-tab.active .srif-tab-count { background: rgba(255,255,255,.2); }
    .srif-section-title { display: flex; align-items: center; justify-content: space-between; gap: .5rem; margin: 0 0 .6rem; font-size: .85rem; font-weight: 750; }
    .srif-list { display: grid; gap: .35rem; }
    .srif-row { display: grid; grid-template-columns: 1fr auto; align-items: center; gap: .75rem; padding: .55rem .65rem; border: 1px solid rgba(148,163,184,.14); border-radius: 7px; }
    .srif-code { font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; font-size: .82rem; font-weight: 750; }
    .srif-name, .srif-muted { color: #64748b; font-size: .72rem; }
    .srif-qty { min-width: 40px; padding: .18rem .45rem; border-radius: 6px; background: #334155; color: #fff; text-align: center; font-weight: 750; }
    .srif-order { display: flex; align-items: center; justify-content: space-between; gap: .5rem; padding: .52rem .65rem; border: 1px solid rgba(148,163,184,.14); border-radius: 7px; }
    .srif-empty { padding: .8rem; color: #94a3b8; font-size: .75rem; text-align: center; }
    .srif-confirm { border-color: rgba(51,65,85,.2); background: #f8fafc; }
    .srif-toast { position: fixed; right: 1rem; bottom: 1rem; z-index: 500; max-width: 320px; padding: .65rem .8rem; border-radius: 8px; background: #334155; color: #fff; font-size: .75rem; opacity: 0; transform: translateY(8px); pointer-events: none; transition: .15s ease; }
    .srif-toast.show { opacity: 1; transform: translateY(0); }
    .srif-toast.error { background: #991b1b; }
    @media (max-width: 640px) {
        .srif-stepper { grid-template-columns: 1fr; }
        .srif-summary { grid-template-columns: 1fr; }
        .srif-topbar { align-items: flex-start; flex-direction: column; }
    }
</style>
@endpush

@section('content')
@php
    $initialLines = $shipmentReturn->lines
        ->map(fn ($line) => [
            'id' => $line->id,
            'item_id' => $line->item_id,
            'code' => $line->item->code ?? '-',
            'name' => $line->item->name ?? '',
            'qty' => (int) $line->qty,
            'scanned_at' => optional($line->scanned_at)->toISOString(),
        ])
        ->values();
    $initialOrders = $shipmentReturn->orderScans
        ->map(fn ($scan) => [
            'code' => strtoupper(trim((string) $scan->order_number)),
            'label' => 'Pencatatan order',
            'scanned_at' => optional($scan->scanned_at)->toISOString(),
        ])
        ->filter(fn ($order) => $order['code'] !== '')
        ->values();
@endphp

<div class="srif-page">
    <div class="srif-topbar">
        <div>
            <h1 class="srif-title">Retur {{ $shipmentReturn->code }}</h1>
            <div class="srif-sub">Mode Scan Item Dulu · pencatatan item dan order tetap terpisah</div>
        </div>
        <a href="{{ route('sales.shipment_returns.index') }}" class="srif-btn">Kembali</a>
    </div>

    <div class="srif-card">
        <div class="srif-body">
            <div class="srif-meta">
                <span>Marketplace: <strong>{{ $shipmentReturn->store?->code ?? 'Belum dihubungkan' }}</strong></span>
                <span>Tanggal: <strong>{{ optional($shipmentReturn->date)->format('d M Y') }}</strong></span>
                <span>Status: <strong>{{ ucfirst($shipmentReturn->status) }}</strong></span>
            </div>
            <div class="srif-stepper" id="stepper" aria-label="Tahapan retur">
                <div class="srif-step active" data-step="item"><span class="srif-step-number">1</span>Scan Semua Item</div>
                <div class="srif-step" data-step="order"><span class="srif-step-number">2</span>Scan Semua Order</div>
                <div class="srif-step" data-step="confirm"><span class="srif-step-number">3</span>Konfirmasi</div>
            </div>
        </div>
    </div>

    @if ($shipmentReturn->status === 'draft')
        <div class="srif-card" id="scanCard">
            <div class="srif-body">
                <h2 class="srif-scan-title" id="scanTitle">Scan semua item retur</h2>
                <p class="srif-help" id="scanHelp">Scan barcode atau kode item satu per satu. Item belum ditautkan ke order.</p>
                <input type="text" id="scanInput" class="form-control srif-input" placeholder="Scan barcode / kode item" inputmode="text" autocomplete="off" autofocus>
                <div class="srif-actions">
                    <button type="button" class="srif-btn srif-btn-primary" id="nextBtn">Next: Scan Order</button>
                </div>
            </div>
        </div>
    @endif

    <div class="srif-summary">
        <div class="srif-stat"><div class="srif-stat-label">Jenis Item</div><div class="srif-stat-value" id="sumItems">0</div></div>
        <div class="srif-stat"><div class="srif-stat-label">Total Qty</div><div class="srif-stat-value" id="sumQty">0</div></div>
        <div class="srif-stat"><div class="srif-stat-label">Total Order</div><div class="srif-stat-value" id="sumOrders">0</div></div>
    </div>

    <div class="srif-tabs" id="contentTabs" role="tablist" aria-label="Ringkasan retur" hidden>
        <button type="button" class="srif-tab active" data-content-tab="order" role="tab" aria-selected="true">Order <span class="srif-tab-count" id="tabOrdersCount">0</span></button>
        <button type="button" class="srif-tab" data-content-tab="item" role="tab" aria-selected="false">Item <span class="srif-tab-count" id="tabItemsCount">0</span></button>
    </div>

    <div class="srif-card" id="itemsCard">
        <div class="srif-body">
            <div class="srif-section-title"><span>Item yang sudah discan</span><span class="srif-muted">Tidak terhubung ke order</span></div>
            <div class="srif-list" id="itemsWrap"></div>
        </div>
    </div>

    <div class="srif-card" id="ordersCard" hidden>
        <div class="srif-body">
            <div class="srif-section-title"><span>Order yang sudah discan</span><span class="srif-muted">Disimpan sebagai pencatatan</span></div>
            <div class="srif-list" id="ordersWrap"></div>
        </div>
    </div>

    <div class="srif-card srif-confirm" id="confirmCard" hidden>
        <div class="srif-body">
            <h2 class="srif-scan-title">Konfirmasi retur</h2>
            <p class="srif-help">Periksa jumlah item dan order. Setelah ini Anda dapat melanjutkan ke detail retur dan proses WH-RTS.</p>
            <div class="srif-actions">
                <button type="button" class="srif-btn" id="backBtn">Kembali Scan Order</button>
                <a href="{{ route('sales.shipment_returns.show', $shipmentReturn) }}" class="srif-btn srif-btn-primary is-disabled" id="confirmBtn" aria-disabled="true">Konfirmasi Retur</a>
            </div>
        </div>
    </div>

    @if ($shipmentReturn->status !== 'draft')
        <div class="srif-actions">
            <a href="{{ route('sales.shipment_returns.show', $shipmentReturn) }}" class="srif-btn srif-btn-primary">Buka Detail Retur</a>
        </div>
    @endif
</div>

<div class="srif-toast" id="toast"></div>
@endsection

@push('scripts')
<script>
(function () {
    const isDraft = @json($shipmentReturn->status === 'draft');
    const scanItemUrl = @json(route('sales.shipment_returns.scan_item', $shipmentReturn));
    const scanOrderUrl = @json(route('sales.shipment_returns.scan_order', $shipmentReturn));
    const csrf = @json(csrf_token());
    const initialLines = @json($initialLines);
    const initialOrders = @json($initialOrders);
    const workflowStorageKey = @json('shipment_return_item_first_' . $shipmentReturn->id);

    function loadWorkflowStage() {
        let saved = null;
        try {
            saved = JSON.parse(localStorage.getItem(workflowStorageKey) || 'null');
        } catch (error) {}
        const stage = saved?.stage;
        if (['item', 'order', 'confirm'].includes(stage)) {
            if (stage === 'item' || initialLines.length === 0) return 'item';
            if (stage === 'order' && initialOrders.length > 0) return 'order';
            if (stage === 'confirm' && initialOrders.length > 0) return 'confirm';
        }
        return initialOrders.length > 0 ? 'order' : 'item';
    }

    function loadWorkflowTab(stage) {
        try {
            const saved = JSON.parse(localStorage.getItem(workflowStorageKey) || 'null');
            if (saved?.contentTab === 'item' || saved?.contentTab === 'order') return saved.contentTab;
        } catch (error) {}
        return stage === 'item' ? 'item' : 'order';
    }

    const initialStage = loadWorkflowStage();
    const state = {
        stage: initialStage,
        contentTab: loadWorkflowTab(initialStage),
        lines: initialLines.map(line => ({ ...line })),
        orders: initialOrders.map(order => ({ ...order })),
        busy: false,
    };
    const $ = id => document.getElementById(id);
    const esc = value => String(value ?? '').replace(/[&<>"']/g, char => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[char]));
    const normalize = value => String(value ?? '').trim().toUpperCase();
    const formatScanTime = value => {
        if (!value) return 'Belum ada timestamp';
        const date = new Date(value);
        return Number.isNaN(date.getTime()) ? String(value) : date.toLocaleString('id-ID', { dateStyle: 'short', timeStyle: 'medium' });
    };
    let fallbackAudioContext = null;

    function saveWorkflowState() {
        if (!isDraft) return;
        try {
            localStorage.setItem(workflowStorageKey, JSON.stringify({ stage: state.stage, contentTab: state.contentTab }));
        } catch (error) {}
    }

    function unlockScanAudio() {
        try {
            const Ctx = window.AudioContext || window.webkitAudioContext;
            if (!Ctx) return;
            if (window.GFID && !window.GFID.scanAudioContext) window.GFID.scanAudioContext = new Ctx();
            const contexts = [fallbackAudioContext, window.GFID?.scanAudioContext].filter(Boolean);
            contexts.forEach(context => {
                if (context.state === 'suspended') context.resume().catch(() => {});
            });
        } catch (error) {}
    }

    function fallbackTone(frequency, duration = .1, volume = .18, delay = 0) {
        try {
            const Ctx = window.AudioContext || window.webkitAudioContext;
            if (!Ctx) return;
            fallbackAudioContext = fallbackAudioContext || new Ctx();
            const context = fallbackAudioContext;
            const play = () => {
                const start = context.currentTime + delay;
                const oscillator = context.createOscillator();
                const gain = context.createGain();
                oscillator.type = 'sine';
                oscillator.frequency.setValueAtTime(frequency, start);
                oscillator.connect(gain);
                gain.connect(context.destination);
                gain.gain.setValueAtTime(volume, start);
                gain.gain.exponentialRampToValueAtTime(.001, start + duration);
                oscillator.start(start);
                oscillator.stop(start + duration + .02);
            };
            if (context.state === 'suspended') context.resume().then(play).catch(() => {});
            else play();
        } catch (error) {}
    }

    function playScanSound(kind) {
        const eventMap = { item: 'item_success', order: 'order_success', duplicate: 'order_duplicate', next: 'navigation', error: 'error_general' };
        const fallback = () => {
            const tones = {
                item: [[1046, .06, 0], [1318, .08, .07]],
                order: [[660, .07, 0], [880, .1, .08]],
                duplicate: [[784, .08, 0], [784, .08, .1]],
                next: [[740, .06, 0], [932, .06, .07], [1175, .1, .14]],
                error: [[220, .16, 0]],
            };
            (tones[kind] || tones.error).forEach(note => fallbackTone(note[0], note[1], .16, note[2]));
        };
        if (window.GFID && typeof window.GFID.playScanSound === 'function') {
            window.GFID.playScanSound(eventMap[kind] || 'error_general', fallback);
            return;
        }
        fallback();
    }

    function focusScanInput() {
        if (!isDraft || state.stage === 'confirm' || state.busy) return;
        const input = $('scanInput');
        if (!input || input.disabled) return;
        setTimeout(() => input.focus({ preventScroll: true }), 20);
    }

    function toast(message, error = false) {
        const node = $('toast');
        if (!node) return;
        node.textContent = message;
        node.classList.toggle('error', error);
        node.classList.add('show');
        clearTimeout(window.__srifToast);
        window.__srifToast = setTimeout(() => node.classList.remove('show'), 2400);
    }

    function render() {
        const qty = state.lines.reduce((sum, line) => sum + Number(line.qty || 0), 0);
        $('sumItems').textContent = new Set(state.lines.map(line => line.item_id)).size.toLocaleString('id-ID');
        $('sumQty').textContent = qty.toLocaleString('id-ID');
        $('sumOrders').textContent = state.orders.length.toLocaleString('id-ID');
        $('tabItemsCount').textContent = state.lines.length.toLocaleString('id-ID');
        $('tabOrdersCount').textContent = state.orders.length.toLocaleString('id-ID');
        $('itemsWrap').innerHTML = state.lines.length
            ? state.lines.map(line => '<div class="srif-row"><div><div class="srif-code">' + esc(line.code) + '</div><div class="srif-name">' + esc(line.name) + '</div><div class="srif-name">Scan: ' + esc(formatScanTime(line.scanned_at)) + '</div></div><div class="srif-qty">' + Number(line.qty || 0).toLocaleString('id-ID') + '</div></div>').join('')
            : '<div class="srif-empty">Belum ada item. Silakan scan item retur.</div>';
        $('ordersWrap').innerHTML = state.orders.length
            ? state.orders.map((order, index) => '<div class="srif-order"><span><span class="srif-code">' + esc(order.code) + '</span><br><span class="srif-name">' + esc(order.label || 'Pencatatan order') + '</span><br><span class="srif-name">Scan: ' + esc(formatScanTime(order.scanned_at)) + '</span></span><span class="srif-muted">#' + (index + 1) + '</span></div>').join('')
            : '<div class="srif-empty">Belum ada order. Scan nomor order/resi satu per satu.</div>';

        if (isDraft) $('scanCard').hidden = state.stage === 'confirm';
        const showTabs = state.stage !== 'item';
        $('contentTabs').hidden = !showTabs;
        document.querySelectorAll('[data-content-tab]').forEach(tab => {
            const active = showTabs && tab.dataset.contentTab === state.contentTab;
            tab.classList.toggle('active', active);
            tab.setAttribute('aria-selected', active ? 'true' : 'false');
        });
        $('itemsCard').hidden = showTabs ? state.contentTab !== 'item' : false;
        $('ordersCard').hidden = !showTabs || state.contentTab !== 'order';
        $('confirmCard').hidden = state.stage !== 'confirm';
        document.querySelectorAll('[data-step]').forEach(step => {
            const name = step.dataset.step;
            step.classList.toggle('active', name === state.stage);
            step.classList.toggle('done', (state.stage === 'order' && name === 'item') || (state.stage === 'confirm' && ['item', 'order'].includes(name)));
        });
        if (!isDraft) return;

        const input = $('scanInput');
        const next = $('nextBtn');
        if (state.stage === 'item') {
            $('scanTitle').textContent = 'Scan semua item retur';
            $('scanHelp').textContent = 'Scan barcode atau kode item satu per satu. Item belum ditautkan ke order.';
            input.placeholder = 'Scan barcode / kode item';
            next.textContent = 'Next: Scan Order';
            next.disabled = qty === 0;
        } else if (state.stage === 'order') {
            $('scanTitle').textContent = 'Scan semua order / resi';
            $('scanHelp').textContent = 'Scan seluruh nomor order yang berkaitan. Order hanya dicatat dan tidak mengubah item yang sudah discan.';
            input.placeholder = 'Scan nomor order / resi';
            next.textContent = 'Next: Konfirmasi';
            next.disabled = state.orders.length === 0;
        }
        input.disabled = state.stage === 'confirm' || state.busy;
        next.hidden = state.stage === 'confirm';
        $('backBtn').hidden = state.stage !== 'confirm';
        const confirmEnabled = qty > 0 && state.orders.length > 0;
        $('confirmBtn').classList.toggle('is-disabled', !confirmEnabled);
        $('confirmBtn').setAttribute('aria-disabled', confirmEnabled ? 'false' : 'true');
        $('confirmBtn').tabIndex = confirmEnabled ? 0 : -1;
        focusScanInput();
    }

    function setStage(stage) {
        if (stage === 'order' && state.lines.length === 0) return toast('Scan minimal satu item terlebih dahulu.', true);
        if (stage === 'confirm' && state.orders.length === 0) return toast('Scan minimal satu order terlebih dahulu.', true);
        state.stage = stage;
        state.contentTab = stage === 'item' ? 'item' : 'order';
        saveWorkflowState();
        playScanSound('next');
        render();
    }

    async function post(url, body) {
        const response = await fetch(url, {
            method: 'POST',
            headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': csrf },
            body: JSON.stringify(body),
        });
        const payload = await response.json().catch(() => ({}));
        if (!response.ok) throw new Error(payload.message || 'Pencatatan gagal.');
        return payload;
    }

    async function scanItem(code) {
        if (!code || state.busy) return;
        state.busy = true;
        render();
        try {
            const payload = await post(scanItemUrl, { scan_code: code, qty: 1, order_number: '' });
            const line = payload.line;
            const existing = state.lines.find(item => String(item.id) === String(line.id));
            if (existing) Object.assign(existing, { qty: line.qty, code: line.item_code, name: line.item_name, item_id: line.item_id, scanned_at: line.scanned_at });
            else state.lines.push({ id: line.id, item_id: line.item_id, code: line.item_code, name: line.item_name, qty: line.qty, scanned_at: line.scanned_at });
            playScanSound('item');
            toast(payload.message || 'Item berhasil dicatat.');
        } catch (error) {
            playScanSound('error');
            toast(error.message, true);
        } finally {
            state.busy = false;
            render();
        }
    }

    async function scanOrder(code) {
        if (!code || state.busy) return;
        state.busy = true;
        render();
        try {
            const payload = await post(scanOrderUrl, { order_number: code });
            const order = payload.order || { code: code, label: 'Pencatatan order', scanned_at: null };
            const orderCode = normalize(order.code);
            const existingOrder = state.orders.find(item => normalize(item.code) === orderCode);
            if (!existingOrder) {
                state.orders.push({ code: orderCode, label: order.label || 'Pencatatan order', scanned_at: order.scanned_at });
                playScanSound('order');
            } else {
                existingOrder.scanned_at = order.scanned_at;
                playScanSound('duplicate');
            }
            toast(payload.message || ('Order ' + orderCode + ' dicatat.'));
        } catch (error) {
            toast(error.message, true);
        } finally {
            state.busy = false;
            render();
        }
    }

    $('nextBtn')?.addEventListener('click', () => setStage(state.stage === 'item' ? 'order' : 'confirm'));
    $('backBtn')?.addEventListener('click', () => setStage('order'));
    document.querySelectorAll('[data-content-tab]').forEach(tab => {
        tab.addEventListener('click', () => {
            state.contentTab = tab.dataset.contentTab === 'item' ? 'item' : 'order';
            saveWorkflowState();
            render();
        });
    });
    $('scanInput')?.addEventListener('input', event => { event.target.value = event.target.value.toUpperCase(); });
    $('scanInput')?.addEventListener('keydown', event => {
        if (event.key !== 'Enter') return;
        event.preventDefault();
        const code = normalize(event.target.value);
        event.target.value = '';
        if (state.stage === 'item') scanItem(code);
        if (state.stage === 'order') scanOrder(code);
    });
    $('confirmBtn')?.addEventListener('click', event => {
        if ($('confirmBtn').getAttribute('aria-disabled') === 'true') event.preventDefault();
    });
    ['pointerdown', 'keydown', 'touchstart'].forEach(eventName => {
        document.addEventListener(eventName, unlockScanAudio, { once: true, passive: true });
    });
    window.addEventListener('focus', focusScanInput);
    document.addEventListener('visibilitychange', focusScanInput);
    document.addEventListener('click', event => {
        if (!event.target.closest('#confirmCard')) focusScanInput();
    });
    render();
})();
</script>
@endpush
