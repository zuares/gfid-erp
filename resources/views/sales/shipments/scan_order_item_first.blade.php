{{-- Halaman khusus: Scan Item Dulu -> Scan No Order --}}
@extends('layouts.app')

@section('title', 'Scan No Order · ' . $shipment->code)

@push('head')
<style>
    .sif-page { max-width: 1100px; margin: 0 auto; padding: 0 .75rem 4rem; color: #0f172a; }
    .sif-topbar {
        position: sticky; top: 0; z-index: 300;
        display: flex; align-items: center; gap: .45rem; flex-wrap: wrap;
        margin: 0 -.75rem; padding: .45rem .75rem;
        background: var(--card, #fff); border-bottom: 1px solid rgba(148,163,184,.18);
    }
    .sif-code { font-size: .95rem; font-weight: 900; letter-spacing: .03em; }
    .sif-spacer { flex: 1; min-width: .35rem; }
    .sif-btn {
        display: inline-flex; align-items: center; justify-content: center; gap: .3rem;
        min-height: 36px; padding: .34rem .72rem; border: 1px solid rgba(148,163,184,.35);
        border-radius: 8px; background: #fff; color: #475569; font-size: .74rem;
        font-weight: 800; text-decoration: none; cursor: pointer;
    }
    .sif-btn:hover { color: #1e293b; background: #f8fafc; }
    .sif-btn-primary { color: #fff !important; background: #2563eb; border-color: #2563eb; box-shadow: 0 4px 12px rgba(37,99,235,.2); }
    .sif-btn-primary:hover { color: #fff !important; background: #1d4ed8; border-color: #1d4ed8; }
    .sif-btn-primary[aria-disabled="true"] { color: #fff !important; background: #64748b; border-color: #64748b; opacity: .7; pointer-events: none; }
    .sif-pill { display: inline-flex; align-items: center; gap: .25rem; min-height: 30px; padding: .2rem .55rem; border: 1px solid rgba(37,99,235,.18); border-radius: 999px; background: #eff6ff; color: #1d4ed8; font-size: .7rem; font-weight: 800; }
    .sif-shell { display: grid; gap: .55rem; margin-top: .55rem; }
    .sif-card { overflow: hidden; border: 1px solid rgba(148,163,184,.18); border-radius: 8px; background: var(--card, #fff); box-shadow: none; }
    .sif-card-head { display: flex; align-items: flex-start; justify-content: space-between; gap: .6rem; padding: .65rem .75rem; border-bottom: 1px solid rgba(148,163,184,.12); }
    .sif-title { font-size: .9rem; font-weight: 900; }
    .sif-sub { margin-top: .2rem; color: #64748b; font-size: .76rem; line-height: 1.4; }
    .sif-body { padding: .75rem; }
    .sif-scan-box { padding: .75rem; border: 1px solid rgba(37,99,235,.18); border-radius: 8px; background: rgba(37,99,235,.06); }
    .sif-label { display: block; margin-bottom: .35rem; color: #1e40af; font-size: .7rem; font-weight: 900; letter-spacing: .05em; text-transform: uppercase; }
    .sif-input { width: 100%; min-height: 54px; border: 1.5px solid rgba(148,163,184,.35); border-radius: 8px; padding: .5rem .7rem; color: #0f172a; background: #fff; font-size: 1.25rem; font-weight: 850; letter-spacing: .03em; text-transform: uppercase; }
    .sif-input:focus { outline: 0; border-color: #2563eb; box-shadow: 0 0 0 .2rem rgba(37,99,235,.14); }
    .sif-hint { margin-top: .35rem; color: #64748b; font-size: .72rem; }
    .sif-list { display: grid; gap: .4rem; }
    .sif-order { display: flex; align-items: center; gap: .55rem; padding: .6rem .65rem; border: 1px solid rgba(148,163,184,.17); border-radius: 8px; background: #fff; }
    .sif-order-no { flex: 1; min-width: 0; overflow: hidden; color: #0f172a; font-family: ui-monospace,SFMono-Regular,Menlo,monospace; font-size: .86rem; font-weight: 900; text-overflow: ellipsis; white-space: nowrap; }
    .sif-order-status { color: #166534; font-size: .7rem; font-weight: 800; }
    .sif-order-status-unlinked { color: #b45309; }
    .sif-empty { padding: 1rem; color: #64748b; text-align: center; font-size: .78rem; }
    .sif-order-group-section { display: grid; gap: .35rem; }
    .sif-order-group-heading { display: flex; align-items: center; gap: .4rem; padding: .15rem .1rem; color: #475569; font-size: .68rem; font-weight: 850; letter-spacing: .04em; text-transform: uppercase; }
    .sif-order-group-heading b { min-width: 1.25rem; padding: .1rem .3rem; border-radius: 999px; background: #e2e8f0; color: #334155; text-align: center; font-size: .68rem; }
    .sif-order-filters { display: flex; flex-wrap: wrap; gap: .35rem; margin-bottom: .65rem; }
    .sif-order-filter { display: inline-flex; align-items: center; gap: .4rem; min-height: 30px; padding: .22rem .55rem; border: 1px solid rgba(148,163,184,.28); border-radius: 7px; background: #fff; color: #64748b; font-size: .72rem; font-weight: 850; cursor: pointer; }
    .sif-order-filter b { min-width: 1.2rem; padding: .08rem .28rem; border-radius: 999px; background: #e2e8f0; color: #334155; text-align: center; font-size: .66rem; }
    .sif-order-filter:hover { border-color: rgba(37,99,235,.35); color: #1d4ed8; }
    .sif-order-filter.is-active { border-color: #2563eb; background: #2563eb; color: #fff; }
    .sif-order-filter.is-active b { background: rgba(255,255,255,.2); color: #fff; }
    .sif-order-filter:disabled { cursor: not-allowed; opacity: .45; }
    .sif-order-index { display: inline-flex; align-items: center; justify-content: center; flex: 0 0 1.45rem; width: 1.45rem; height: 1.45rem; border-radius: 6px; background: #f1f5f9; color: #64748b; font-size: .7rem; font-weight: 900; }
    .sif-pill-kpi { border-color: rgba(37,99,235,.28); background: #eff6ff; color: #1d4ed8; }
    .sif-input-row { display: flex; align-items: stretch; gap: .45rem; }
    .sif-input-row .sif-input { min-width: 0; flex: 1; }
    .sif-camera-btn { flex: 0 0 auto; min-width: 46px; min-height: 54px; border: 1px solid #2563eb; border-radius: 8px; padding: .35rem .7rem; color: #fff; background: #2563eb; font-size: .76rem; font-weight: 900; cursor: pointer; }
    .sif-camera-btn:hover { background: #1d4ed8; border-color: #1d4ed8; }
    .sif-camera-btn:disabled { opacity: .65; cursor: wait; }
    .sif-camera-panel { margin-top: .6rem; overflow: hidden; border: 1px solid rgba(148,163,184,.24); border-radius: 9px; background: #fff; }
    .sif-camera-panel[hidden] { display: none !important; }
    .sif-camera-head { display: flex; align-items: center; justify-content: space-between; gap: .5rem; padding: .5rem .65rem; color: #334155; background: #f8fafc; border-bottom: 1px solid rgba(148,163,184,.16); }
    .sif-camera-title { font-size: .74rem; font-weight: 900; }
    .sif-camera-close { border: 0; border-radius: 6px; padding: .22rem .45rem; color: #64748b; background: transparent; font-size: .7rem; font-weight: 800; cursor: pointer; }
    .sif-camera-close:hover { color: #0f172a; background: #e2e8f0; }
    .sif-camera-reader { min-height: 200px; background: #0f172a; }
    .sif-camera-reader video { display: block; width: 100% !important; max-height: 360px; object-fit: cover; }
    .sif-camera-reader img { max-width: 100%; }
    .sif-camera-status { padding: .45rem .65rem; color: #64748b; background: #fff; font-size: .68rem; line-height: 1.4; }
    .sif-camera-status.error { color: #b91c1c; background: #fef2f2; }
    .sif-topbar { --sif-topbar-height: 3.7rem; position: sticky; top: 0; z-index: 300; }
    .app-main .page-wrap:has(.sif-page) {
        /* Keep the scan section tied to the page scroll, not a clipped wrapper. */
        overflow-x: clip !important;
    }
    .sif-shell > .sif-card:first-child {
        position: sticky !important;
        top: var(--sif-topbar-height) !important;
        z-index: 250;
        align-self: flex-start;
        box-shadow: 0 8px 18px rgba(15,23,42,.08);
    }
    .sif-toast { position: fixed; left: 50%; bottom: 1.2rem; z-index: 9999; display: none; transform: translateX(-50%); max-width: min(92vw, 520px); padding: .65rem .9rem; border-radius: 999px; color: #fff; background: #0f172a; font-size: .8rem; font-weight: 800; }
    .sif-toast.show { display: block; }
    .sif-toast.error { background: #991b1b; }
    @media (max-width: 640px) {
        .sif-page { padding: 0 .5rem 5rem; }
        .sif-topbar { margin: 0 -.5rem; padding: .5rem; }
        .sif-topbar { --sif-topbar-height: 6.6rem; }
        .sif-topbar .sif-btn-primary { width: 100%; order: 5; }
        .sif-card-head, .sif-body { padding: .7rem; }
        .sif-input { min-height: 54px; font-size: 1.25rem; }
        .sif-camera-btn { width: 46px; min-width: 46px; min-height: 54px; padding-inline: .35rem; }
    }
</style>
@endpush

@section('content')
@php
  $lines = $shipment->lines ?? collect();
    $orders = ($shipment->orderScans ?? collect())->sortBy('id')->values();
    $totalQty = (int) $lines->sum('qty_scanned');
    $totalLines = (int) $lines->count();
    $orderGroup = static function ($orderNo): string {
        $value = strtoupper(trim((string) $orderNo));
        if (str_starts_with($value, 'SPX')) return 'SPX';
        if (str_starts_with($value, 'JY')) return 'JY';
        return 'Lainnya';
    };
    $orderGroups = collect(['SPX', 'JY', 'Lainnya'])
        ->mapWithKeys(fn ($group) => [$group => $orders->filter(fn ($order) => $orderGroup($order->order_no) === $group)->count()]);
@endphp

<div class="sif-page">
    <div class="sif-topbar">
        <a href="{{ route('sales.shipments.edit', $shipment) }}" class="sif-btn">← Scan Item</a>
        <a href="{{ route('sales.shipments.index') }}" class="sif-btn">Daftar Shipment</a>
        <span class="sif-code">{{ $shipment->code }}</span>
        <span class="sif-spacer"></span>
        <span class="sif-pill">{{ $totalLines }} SKU</span>
        <span class="sif-pill">{{ number_format($totalQty, 0, ',', '.') }} Qty</span>
        <span class="sif-pill sif-pill-kpi">Order <b id="sifOrderTotal">{{ $orders->count() }}</b></span>
        <a href="{{ route('sales.shipments.confirm_orders', $shipment) }}"
           id="sifConfirmBtn"
           class="sif-btn sif-btn-primary"
           aria-disabled="{{ $orders->isNotEmpty() ? 'false' : 'true' }}">Cek Shipment</a>
    </div>

    <div class="sif-shell">
        <div class="sif-card">
            <div class="sif-card-head">
                <div>
                    <div class="sif-title">Scan No Order</div>
                    <div class="sif-sub">Item sudah discan. Scan nomor order sekarang untuk menghubungkan order dengan item shipment.</div>
                </div>
            </div>
            <div class="sif-body">
                <div class="sif-scan-box">
                    <label class="sif-label" for="sifScanInput">Nomor Order</label>
                    <div class="sif-input-row">
                        <input type="text" id="sifScanInput" class="sif-input" placeholder="Scan / ketik nomor order lalu Enter" autocomplete="off" autofocus>
                        <button type="button" id="sifCameraBtn" class="sif-camera-btn" aria-label="Buka kamera untuk scan barcode" title="Scan dengan kamera">
                            <i class="bi bi-camera-video" aria-hidden="true"></i>
                        </button>
                    </div>
                    <div id="sifCameraPanel" class="sif-camera-panel" hidden>
                        <div class="sif-camera-head">
                            <span class="sif-camera-title"><i class="bi bi-upc-scan" aria-hidden="true"></i> Arahkan barcode ke kamera</span>
                            <button type="button" id="sifCameraClose" class="sif-camera-close">Tutup</button>
                        </div>
                        <div id="sifCameraReader" class="sif-camera-reader"></div>
                        <div id="sifCameraStatus" class="sif-camera-status">Meminta izin kamera...</div>
                    </div>
                    <div class="sif-hint">Setelah order dicatat, pilih Cek Shipment untuk meninjau item dan melanjutkan post.</div>
                </div>
            </div>
        </div>

        <div class="sif-card">
            <div class="sif-card-head">
                <div>
                    <div class="sif-title">Order Tercatat</div>
                    <div class="sif-sub">Order yang sudah masuk akan digunakan saat Cek Shipment.</div>
                </div>
            </div>
            <div class="sif-body">
                <div id="sifOrderFilters" class="sif-order-filters" role="group" aria-label="Filter grup order">
                    <button type="button" class="sif-order-filter is-active" data-group="all" aria-pressed="true">
                        Semua <b data-filter-count="all">{{ $orders->count() }}</b>
                    </button>
                    @foreach ($orderGroups as $group => $groupTotal)
                        <button type="button" class="sif-order-filter" data-group="{{ $group }}" aria-pressed="false" @disabled($groupTotal === 0)>
                            {{ $group }} <b data-filter-count="{{ $group }}">{{ $groupTotal }}</b>
                        </button>
                    @endforeach
                </div>
                <div id="sifOrderList" class="sif-list">
                    @php $orderNumber = 0; @endphp
                    @forelse ($orderGroups->filter(fn ($total) => $total > 0) as $group => $groupTotal)
                        <div class="sif-order-group-section">
                            <div class="sif-order-group-heading"><span>{{ $group }}</span><b>{{ $groupTotal }}</b></div>
                            @foreach ($orders->filter(fn ($order) => $orderGroup($order->order_no) === $group) as $order)
                                @php $orderNumber++; @endphp
                                @php
                                    $orderPayload = is_array($order->raw_payload) ? $order->raw_payload : [];
                                    $isUnlinked = empty($order->fulfillment_id)
                                        || ($orderPayload['mode'] ?? null) === 'record_only'
                                        || ($orderPayload['lookup_status'] ?? null) !== null;
                                @endphp
                                <div class="sif-order">
                                    <span class="sif-order-index">{{ $orderNumber }}</span>
                                    <span class="sif-order-no">{{ $order->order_no }}</span>
                                    <span class="sif-order-status {{ $isUnlinked ? 'sif-order-status-unlinked' : '' }}">
                                        {{ $isUnlinked ? 'Belum Tertaut' : 'Tertaut' }}
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    @empty
                        <div class="sif-empty">Belum ada nomor order yang discan.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>

<div id="sifToast" class="sif-toast"></div>

@push('scripts')
<script>
(function () {
    const input = document.getElementById('sifScanInput');
    const list = document.getElementById('sifOrderList');
    const count = document.getElementById('sifOrderTotal');
    const filterButtons = Array.from(document.querySelectorAll('#sifOrderFilters [data-group]'));
    const confirmBtn = document.getElementById('sifConfirmBtn');
    const toast = document.getElementById('sifToast');
    const cameraBtn = document.getElementById('sifCameraBtn');
    const cameraPanel = document.getElementById('sifCameraPanel');
    const cameraClose = document.getElementById('sifCameraClose');
    const cameraReader = document.getElementById('sifCameraReader');
    const cameraStatus = document.getElementById('sifCameraStatus');
    const recordUrl = @json(route('sales.shipments.scan_order_store', $shipment));
    const csrf = @json(csrf_token());
    let orders = @json($orders->map(fn ($order) => $order->order_no)->values());
    let camera = null;
    let cameraLoading = false;
    let submitting = false;
    let cameraLibraryPromise = null;
    let orderAudioContext = null;
    let activeGroup = 'all';
    let nativeCameraStream = null;
    let nativeCameraFrame = null;
    let nativeCameraDetecting = false;

    function showToast(message, error = false) {
        if (!toast) return;
        toast.textContent = message;
        toast.className = 'sif-toast show' + (error ? ' error' : '');
        clearTimeout(showToast.timer);
        showToast.timer = setTimeout(() => toast.classList.remove('show'), 2200);
    }

    function getOrderAudioContext() {
        try {
            const Ctx = window.AudioContext || window.webkitAudioContext;
            if (!Ctx) return null;
            orderAudioContext = orderAudioContext || new Ctx();
            return orderAudioContext;
        } catch (error) {
            return null;
        }
    }

    function unlockOrderAudio() {
        const context = getOrderAudioContext();
        if (context?.state === 'suspended') context.resume().catch(() => {});
    }

    function orderBeep(freq, duration = .1, volume = .3, delay = 0, type = 'sine') {
        try {
            const context = getOrderAudioContext();
            if (!context) return;
            unlockOrderAudio();
            const start = context.currentTime + delay;
            const oscillator = context.createOscillator();
            const gain = context.createGain();
            oscillator.type = type;
            oscillator.frequency.value = freq;
            oscillator.connect(gain);
            gain.connect(context.destination);
            gain.gain.setValueAtTime(volume, start);
            gain.gain.exponentialRampToValueAtTime(.001, start + duration);
            oscillator.start(start);
            oscillator.stop(start + duration);
        } catch (error) {}
    }

    function playOrderSound(eventKey, fallback) {
        if (window.GFID && typeof window.GFID.playScanSound === 'function') {
            window.GFID.playScanSound(eventKey, fallback);
            return;
        }
        fallback();
    }

    function orderSuccessSound() {
        playOrderSound('order_success', () => {
            orderBeep(660, .07, .36, 0, 'sine');
            orderBeep(880, .1, .36, .08, 'sine');
        });
    }

    function orderDuplicateSound() {
        playOrderSound('order_duplicate', () => {
            orderBeep(784, .08, .38, 0, 'square');
            orderBeep(784, .08, .38, .1, 'square');
        });
    }

    function orderErrorSound() {
        playOrderSound('error_general', () => {
            orderBeep(220, .13, .42, 0, 'sawtooth');
            orderBeep(150, .16, .42, .14, 'sawtooth');
        });
    }

    function getOrderGroup(orderNo) {
        const value = String(orderNo ?? '').trim().toUpperCase();
        if (value.startsWith('SPX')) return 'SPX';
        if (value.startsWith('JY')) return 'JY';
        return 'Lainnya';
    }

    async function enableOrderAutoFocus(video = cameraReader?.querySelector('video')) {
        const track = video?.srcObject?.getVideoTracks?.()[0];
        const capabilities = track?.getCapabilities?.();
        if (!track || !Array.isArray(capabilities?.focusMode) || !capabilities.focusMode.includes('continuous')) return;
        try { await track.applyConstraints({ advanced: [{ focusMode: 'continuous' }] }); } catch (error) {}
    }

    function renderOrderFilters() {
        const groups = { SPX: 0, JY: 0, Lainnya: 0 };
        orders.forEach(order => { groups[getOrderGroup(order)] += 1; });
        filterButtons.forEach(button => {
            const group = button.dataset.group;
            const total = group === 'all' ? orders.length : groups[group] || 0;
            const badge = button.querySelector('[data-filter-count]');
            if (badge) badge.textContent = total;
            button.disabled = group !== 'all' && total === 0;
            button.classList.toggle('is-active', activeGroup === group);
            button.setAttribute('aria-pressed', activeGroup === group ? 'true' : 'false');
        });
    }

    function renderOrders() {
        if (!list) return;
        const visibleOrders = activeGroup === 'all'
            ? orders
            : orders.filter(order => getOrderGroup(order) === activeGroup);

        if (!visibleOrders.length) {
            list.innerHTML = activeGroup === 'all'
                ? '<div class="sif-empty">Belum ada nomor order yang discan.</div>'
                : `<div class="sif-empty">Belum ada order grup ${activeGroup}.</div>`;
        } else {
            const groups = { SPX: [], JY: [], Lainnya: [] };
            visibleOrders.forEach(order => { groups[getOrderGroup(order)].push(order); });
            let orderNumber = 0;
            list.innerHTML = Object.entries(groups)
                .filter(([, groupOrders]) => groupOrders.length > 0)
                .map(([group, groupOrders]) => `
                    <div class="sif-order-group-section">
                        <div class="sif-order-group-heading"><span>${group}</span><b>${groupOrders.length}</b></div>
                        ${groupOrders.map(order => {
                            orderNumber += 1;
                            return `
                            <div class="sif-order">
                                <span class="sif-order-index">${orderNumber}</span>
                                <span class="sif-order-no">${String(order).replace(/[&<>"']/g, '')}</span>
                                <span class="sif-order-status">Tercatat</span>
                            </div>`;
                        }).join('')}
                    </div>`).join('');
        }
        if (count) count.textContent = orders.length;
        renderOrderFilters();
        if (confirmBtn) {
            confirmBtn.setAttribute('aria-disabled', orders.length ? 'false' : 'true');
            confirmBtn.classList.toggle('is-disabled', !orders.length);
        }
    }

    function setCameraStatus(message, error = false) {
        if (!cameraStatus) return;
        cameraStatus.textContent = message;
        cameraStatus.classList.toggle('error', error);
    }

    function loadCameraLibrary() {
        if (window.Html5Qrcode) return Promise.resolve();
        if (cameraLibraryPromise) return cameraLibraryPromise;

        cameraLibraryPromise = new Promise((resolve, reject) => {
            const script = document.createElement('script');
            script.src = 'https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js';
            script.async = true;
            script.onload = () => window.Html5Qrcode ? resolve() : reject(new Error('Library kamera tidak tersedia.'));
            script.onerror = () => reject(new Error('Library kamera gagal dimuat.'));
            document.head.appendChild(script);
        });

        return cameraLibraryPromise;
    }

    async function stopCamera() {
        nativeCameraDetecting = false;
        if (nativeCameraFrame) cancelAnimationFrame(nativeCameraFrame);
        nativeCameraFrame = null;
        if (nativeCameraStream) {
            nativeCameraStream.getTracks().forEach(track => track.stop());
            nativeCameraStream = null;
        }
        if (camera) {
            const activeCamera = camera;
            camera = null;
            try { await activeCamera.stop(); } catch (error) {}
            try { activeCamera.clear(); } catch (error) {}
        }
    }

    async function closeCamera() {
        await stopCamera();
        if (cameraPanel) cameraPanel.hidden = true;
        if (cameraReader) cameraReader.innerHTML = '';
        if (input) input.focus();
    }

    async function handleOrderDetected(decodedText) {
        if (submitting) return;
        const orderNo = String(decodedText || '').trim().toUpperCase();
        if (!orderNo) return;
        await closeCamera();
        await submitOrder(orderNo);
    }

    async function startNativeOrderCamera() {
        const Detector = window.BarcodeDetector;
        if (typeof Detector !== 'function' || typeof Detector.getSupportedFormats !== 'function') return false;

        let supportedFormats = [];
        try { supportedFormats = await Detector.getSupportedFormats(); } catch (error) { return false; }
        if (!Array.isArray(supportedFormats)) return false;
        const wantedFormats = ['code_128', 'code_39', 'code_93', 'codabar', 'ean_13', 'ean_8', 'itf', 'upc_a', 'upc_e', 'qr_code', 'data_matrix'];
        const formats = wantedFormats.filter(format => supportedFormats.includes(format));
        if (!formats.length) return false;

        nativeCameraStream = await navigator.mediaDevices.getUserMedia({
            audio: false,
            video: {
                facingMode: { ideal: 'environment' },
                width: { ideal: 1920 },
                height: { ideal: 1080 },
            },
        });
        const video = document.createElement('video');
        video.autoplay = true;
        video.muted = true;
        video.playsInline = true;
        video.setAttribute('playsinline', '');
        video.srcObject = nativeCameraStream;
        cameraReader.innerHTML = '';
        cameraReader.appendChild(video);
        await video.play();
        await enableOrderAutoFocus(video);

        const detector = new Detector({ formats });
        nativeCameraDetecting = true;
        const detect = async () => {
            if (!nativeCameraDetecting) return;
            try {
                if (video.readyState >= 2) {
                    const results = await detector.detect(video);
                    const value = results?.[0]?.rawValue;
                    if (value) {
                        nativeCameraDetecting = false;
                        await handleOrderDetected(value);
                        return;
                    }
                }
            } catch (error) {}
            if (nativeCameraDetecting) nativeCameraFrame = requestAnimationFrame(detect);
        };
        nativeCameraFrame = requestAnimationFrame(detect);
        return true;
    }

    async function openCamera() {
        if (!cameraBtn || !cameraPanel || cameraLoading || camera) return;

        unlockOrderAudio();
        cameraPanel.hidden = false;
        cameraLoading = true;
        cameraBtn.disabled = true;
        setCameraStatus('Memuat kamera dan meminta izin akses...');

        try {
            if (!navigator.mediaDevices?.getUserMedia) {
                throw new Error('Browser ini tidak mendukung akses kamera. Gunakan Chrome atau Safari versi terbaru.');
            }

            if (await startNativeOrderCamera()) {
                setCameraStatus('Scanner native aktif. Arahkan barcode ke area kamera.');
                return;
            }

            await loadCameraLibrary();
            camera = new window.Html5Qrcode('sifCameraReader');
            const formatNames = [
                'CODE_128', 'CODE_39', 'CODE_93', 'CODABAR',
                'EAN_13', 'EAN_8', 'ITF', 'UPC_A', 'UPC_E',
                'QR_CODE', 'DATA_MATRIX'
            ];
            const supportedFormats = window.Html5QrcodeSupportedFormats || {};
            const formatsToSupport = formatNames
                .map(name => supportedFormats[name])
                .filter(value => Number.isInteger(value));
            const cameraConfig = {
                fps: 12,
                aspectRatio: 1.777778,
                disableFlip: false,
                experimentalFeatures: { useBarCodeDetectorIfSupported: true },
            };
            if (formatsToSupport.length) cameraConfig.formatsToSupport = formatsToSupport;
            await camera.start(
                { facingMode: 'environment' },
                cameraConfig,
                handleOrderDetected,
                () => {}
            );
            await enableOrderAutoFocus();
            setCameraStatus('Deteksi otomatis aktif. Arahkan barcode batang atau QR ke area kamera.');
        } catch (error) {
            await stopCamera();
            orderErrorSound();
            setCameraStatus(error.message || 'Kamera tidak dapat dibuka.', true);
            showToast('Kamera tidak dapat dibuka. Pastikan izin kamera aktif dan gunakan HTTPS.', true);
        } finally {
            cameraLoading = false;
            cameraBtn.disabled = false;
        }
    }

    async function submitOrder(orderNo) {
        const normalizedOrderNo = String(orderNo ?? '').trim().toUpperCase();
        if (!normalizedOrderNo || ['UNDEFINED', 'NULL', 'NAN'].includes(normalizedOrderNo) || submitting) return;

        submitting = true;
        input.disabled = true;
        try {
            const response = await fetch(recordUrl, {
                method: 'POST',
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                body: new URLSearchParams({ _token: csrf, order_no: normalizedOrderNo }),
            });
            const data = await response.json();
            if (!response.ok || data.status !== 'ok') throw new Error(data.message || 'Gagal mencatat order.');
            const isDuplicate = data.duplicate === true || data.created === false;
            if (!orders.includes(normalizedOrderNo)) orders.push(normalizedOrderNo);
            renderOrders();
            input.value = '';
            if (isDuplicate) {
                orderDuplicateSound();
                showToast(data.message || 'Order sudah tercatat.', true);
            } else {
                orderSuccessSound();
                showToast(data.message || 'Order berhasil dicatat.');
            }
        } catch (error) {
            orderErrorSound();
            showToast(error.message || 'Gagal mencatat order.', true);
        } finally {
            input.disabled = false;
            submitting = false;
            input.focus();
        }
    }

    input?.addEventListener('input', () => { input.value = input.value.toUpperCase(); });
    input?.addEventListener('keydown', async function (event) {
        if (event.key !== 'Enter') return;
        event.preventDefault();
        unlockOrderAudio();
        const orderNo = input.value.trim().toUpperCase();
        if (!orderNo) return;
        await submitOrder(orderNo);
    });

    cameraBtn?.addEventListener('click', openCamera);
    cameraClose?.addEventListener('click', closeCamera);
    window.addEventListener('pagehide', stopCamera);

    filterButtons.forEach(button => {
        button.addEventListener('click', function () {
            if (this.disabled) return;
            activeGroup = this.dataset.group || 'all';
            renderOrders();
        });
    });

    confirmBtn?.addEventListener('click', function (event) {
        unlockOrderAudio();
        if (this.getAttribute('aria-disabled') === 'true') {
            event.preventDefault();
            orderErrorSound();
            showToast('Scan minimal satu nomor order terlebih dahulu.', true);
        }
    });

    renderOrders();
})();
</script>
@endpush
@endsection
