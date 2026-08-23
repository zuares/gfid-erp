{{-- Halaman khusus: Scan Item Dulu -> Scan No Order --}}
@extends('layouts.app')

@section('title', 'Scan No Order · ' . $shipment->code)

@push('head')
<style>
    .sif-page { max-width: 960px; margin: 0 auto; padding: 0 .75rem 2rem; color: #0f172a; }
    .sif-topbar {
        position: sticky; top: 0; z-index: 300;
        display: flex; align-items: center; gap: .45rem; flex-wrap: wrap;
        margin: 0 -.75rem; padding: .55rem .8rem;
        background: rgba(248,250,252,.94); border-bottom: 1px solid rgba(148,163,184,.18);
        backdrop-filter: blur(10px);
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
    .sif-shell { display: grid; gap: .65rem; margin-top: .65rem; }
    .sif-card { overflow: hidden; border: 1px solid rgba(148,163,184,.18); border-radius: 10px; background: #fff; }
    .sif-card-head { display: flex; align-items: flex-start; justify-content: space-between; gap: .6rem; padding: .8rem .9rem; border-bottom: 1px solid rgba(148,163,184,.12); }
    .sif-title { font-size: .9rem; font-weight: 900; }
    .sif-sub { margin-top: .2rem; color: #64748b; font-size: .76rem; line-height: 1.4; }
    .sif-body { padding: .8rem .9rem; }
    .sif-scan-box { padding: .8rem; border: 1px solid rgba(37,99,235,.18); border-radius: 9px; background: #eff6ff; }
    .sif-label { display: block; margin-bottom: .35rem; color: #1e40af; font-size: .7rem; font-weight: 900; letter-spacing: .05em; text-transform: uppercase; }
    .sif-input { width: 100%; min-height: 58px; border: 2px solid rgba(37,99,235,.28); border-radius: 9px; padding: .65rem .8rem; color: #0f172a; background: #fff; font-size: 1.2rem; font-weight: 850; letter-spacing: .03em; text-transform: uppercase; }
    .sif-input:focus { outline: 0; border-color: #2563eb; box-shadow: 0 0 0 .2rem rgba(37,99,235,.14); }
    .sif-hint { margin-top: .35rem; color: #64748b; font-size: .72rem; }
    .sif-list { display: grid; gap: .4rem; }
    .sif-order { display: flex; align-items: center; gap: .55rem; padding: .6rem .65rem; border: 1px solid rgba(148,163,184,.17); border-radius: 8px; background: #fff; }
    .sif-order-no { flex: 1; min-width: 0; overflow: hidden; color: #0f172a; font-family: ui-monospace,SFMono-Regular,Menlo,monospace; font-size: .86rem; font-weight: 900; text-overflow: ellipsis; white-space: nowrap; }
    .sif-order-status { color: #166534; font-size: .7rem; font-weight: 800; }
    .sif-empty { padding: 1rem; color: #64748b; text-align: center; font-size: .78rem; }
    .sif-toast { position: fixed; left: 50%; bottom: 1.2rem; z-index: 9999; display: none; transform: translateX(-50%); max-width: min(92vw, 520px); padding: .65rem .9rem; border-radius: 999px; color: #fff; background: #0f172a; font-size: .8rem; font-weight: 800; }
    .sif-toast.show { display: block; }
    .sif-toast.error { background: #991b1b; }
    @media (max-width: 640px) {
        .sif-page { padding: 0 .45rem 1.5rem; }
        .sif-topbar { margin: 0 -.45rem; padding: .5rem .55rem; }
        .sif-topbar .sif-btn-primary { width: 100%; order: 5; }
        .sif-card-head, .sif-body { padding: .7rem; }
        .sif-input { min-height: 62px; font-size: 1.08rem; }
    }
</style>
@endpush

@section('content')
@php
  $lines = $shipment->lines ?? collect();
    $orders = ($shipment->orderScans ?? collect())->sortBy('id')->values();
    $totalQty = (int) $lines->sum('qty_scanned');
    $totalLines = (int) $lines->count();
@endphp

<div class="sif-page">
    <div class="sif-topbar">
        <a href="{{ route('sales.shipments.edit', $shipment) }}" class="sif-btn">← Scan Item</a>
        <a href="{{ route('sales.shipments.index') }}" class="sif-btn">Daftar Shipment</a>
        <span class="sif-code">{{ $shipment->code }}</span>
        <span class="sif-spacer"></span>
        <span class="sif-pill">{{ $totalLines }} SKU</span>
        <span class="sif-pill">{{ number_format($totalQty, 0, ',', '.') }} Qty</span>
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
                <span class="sif-pill" id="sifOrderCount">{{ $orders->count() }} order</span>
            </div>
            <div class="sif-body">
                <div class="sif-scan-box">
                    <label class="sif-label" for="sifScanInput">Nomor Order</label>
                    <input type="text" id="sifScanInput" class="sif-input" placeholder="Scan / ketik nomor order lalu Enter" autocomplete="off" autofocus>
                    <div class="sif-hint">Satu order cukup untuk mapping otomatis item yang sudah discan.</div>
                </div>
            </div>
        </div>

        <div class="sif-card">
            <div class="sif-card-head">
                <div>
                    <div class="sif-title">Order Tercatat</div>
                    <div class="sif-sub">Order yang sudah masuk akan dipetakan otomatis saat lanjut ke Cek Shipment.</div>
                </div>
            </div>
            <div class="sif-body">
                <div id="sifOrderList" class="sif-list">
                    @forelse ($orders as $order)
                        <div class="sif-order">
                            <span class="sif-order-no">{{ $order->order_no }}</span>
                            <span class="sif-order-status">Tercatat</span>
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
    const count = document.getElementById('sifOrderCount');
    const confirmBtn = document.getElementById('sifConfirmBtn');
    const toast = document.getElementById('sifToast');
    const recordUrl = @json(route('sales.shipments.scan_order_store', $shipment));
    const csrf = @json(csrf_token());
    let orders = @json($orders->map(fn ($order) => $order->order_no)->values());

    function showToast(message, error = false) {
        if (!toast) return;
        toast.textContent = message;
        toast.className = 'sif-toast show' + (error ? ' error' : '');
        clearTimeout(showToast.timer);
        showToast.timer = setTimeout(() => toast.classList.remove('show'), 2200);
    }

    function renderOrders() {
        if (!list) return;
        if (!orders.length) {
            list.innerHTML = '<div class="sif-empty">Belum ada nomor order yang discan.</div>';
        } else {
            list.innerHTML = orders.map(order => `
                <div class="sif-order">
                    <span class="sif-order-no">${String(order).replace(/[&<>"']/g, '')}</span>
                    <span class="sif-order-status">Tercatat</span>
                </div>`).join('');
        }
        if (count) count.textContent = orders.length + ' order';
        if (confirmBtn) {
            confirmBtn.setAttribute('aria-disabled', orders.length ? 'false' : 'true');
            confirmBtn.classList.toggle('is-disabled', !orders.length);
        }
    }

    input?.addEventListener('input', () => { input.value = input.value.toUpperCase(); });
    input?.addEventListener('keydown', async function (event) {
        if (event.key !== 'Enter') return;
        event.preventDefault();
        const orderNo = input.value.trim().toUpperCase();
        if (!orderNo) return;

        input.disabled = true;
        try {
            const response = await fetch(recordUrl, {
                method: 'POST',
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                body: new URLSearchParams({ _token: csrf, order_no: orderNo }),
            });
            const data = await response.json();
            if (!response.ok || data.status !== 'ok') throw new Error(data.message || 'Gagal mencatat order.');
            if (!orders.includes(orderNo)) orders.push(orderNo);
            renderOrders();
            input.value = '';
            showToast(data.message || 'Order berhasil dicatat.');
        } catch (error) {
            showToast(error.message || 'Gagal mencatat order.', true);
        } finally {
            input.disabled = false;
            input.focus();
        }
    });

    confirmBtn?.addEventListener('click', function (event) {
        if (this.getAttribute('aria-disabled') === 'true') {
            event.preventDefault();
            showToast('Scan minimal satu nomor order terlebih dahulu.', true);
        }
    });
})();
</script>
@endpush
@endsection
