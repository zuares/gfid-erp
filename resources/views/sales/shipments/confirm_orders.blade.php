{{-- resources/views/sales/shipments/confirm_orders.blade.php --}}
@extends('layouts.app')
@section('title', 'Konfirmasi Pesanan · ' . $shipment->code)

@push('head')
<style>
.cf-wrap{max-width:980px;margin-inline:auto;padding:.65rem .75rem 4rem}
.cf-topbar{position:sticky;top:0;z-index:300;display:flex;align-items:center;gap:.45rem;flex-wrap:wrap;padding:.5rem .75rem;background:var(--card,#fff);border-bottom:1px solid rgba(148,163,184,.18)}
.cf-code{font-weight:900;font-size:.98rem}
.cf-spacer{flex:1}
.cf-pill,.cf-btn{border-radius:7px;border:1px solid rgba(148,163,184,.3);padding:.22rem .55rem;font-size:.76rem;text-decoration:none;background:transparent;color:#475569}
.cf-primary{background:#334155!important;border-color:#334155!important;color:#fff!important;font-weight:850;min-height:40px;padding:.44rem 1rem!important}
.cf-primary:disabled{opacity:.45;cursor:not-allowed}
.cf-flow{display:flex;align-items:center;gap:.35rem;flex-wrap:wrap;margin:.6rem 0;padding:.45rem .55rem;border:1px solid rgba(148,163,184,.18);border-radius:8px;background:var(--card,#fff)}
.cf-step{border:1px solid rgba(148,163,184,.25);border-radius:7px;padding:.18rem .5rem;font-size:.72rem;font-weight:700;color:#64748b}
.cf-step.done{background:rgba(148,163,184,.08);color:#334155}
.cf-step.active{background:#334155;border-color:#334155;color:#fff}
.cf-sep{color:#cbd5e1;font-size:.72rem}
.cf-card{background:var(--card,#fff);border:1px solid rgba(148,163,184,.18);border-radius:8px;margin-bottom:.65rem}
.cf-card-h{display:flex;align-items:center;gap:.5rem;padding:.7rem .85rem;border-bottom:1px solid rgba(148,163,184,.12)}
.cf-title{font-weight:900;color:#334155}
.cf-muted{color:#64748b;font-size:.8rem}
.cf-body{padding:.75rem .85rem}
.cf-list{display:grid;gap:.45rem}
.cf-order{display:flex;align-items:center;gap:.55rem;justify-content:space-between;border:1px solid rgba(148,163,184,.18);border-radius:8px;padding:.58rem .65rem}
.cf-order-no{font-family:monospace;font-weight:900;color:#111827}
.cf-badge{border-radius:7px;padding:.14rem .45rem;font-size:.68rem;font-weight:800;border:1px solid rgba(148,163,184,.25);color:#64748b}
.cf-badge.pending{background:rgba(245,158,11,.08);color:#92400e;border-color:rgba(245,158,11,.25)}
.cf-badge.skip{background:rgba(148,163,184,.08)}
.cf-empty{padding:2rem 1rem;text-align:center;color:#64748b}
.cf-batch{display:grid;gap:.4rem}
.cf-batch-row{display:grid;grid-template-columns:minmax(100px,150px) 1fr auto;gap:.55rem;align-items:center;border-bottom:1px solid rgba(148,163,184,.1);padding:.42rem 0}
.cf-item-code{font-family:monospace;font-weight:900;color:#334155}
.cf-item-name{color:#64748b;font-size:.8rem;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.cf-item-qty{font-weight:900;color:#334155}
.cf-actions{position:sticky;bottom:.65rem;display:flex;gap:.5rem;justify-content:flex-end;align-items:center;padding:.6rem;background:var(--card,#fff);border:1px solid rgba(148,163,184,.18);border-radius:8px}
@media(max-width:768px){
  .cf-wrap{padding:.5rem .5rem 5rem}
  .cf-topbar{padding:.5rem}
  .cf-spacer,.cf-topbar .cf-pill:not(.cf-pill-main),.cf-flow{display:none}
  .cf-code{flex:1;min-width:140px;font-size:1.02rem}
  .cf-actions{left:.5rem;right:.5rem;bottom:.5rem;flex-direction:column}
  .cf-actions .cf-btn,.cf-actions .cf-primary{width:100%;text-align:center}
  .cf-order{align-items:flex-start;flex-direction:column}
}
</style>
@endpush

@section('content')
@php
    $totalLines = $shipment->lines->count();
    $totalQty = $shipment->lines->sum('qty_scanned');
@endphp

<div class="cf-topbar">
    <a href="{{ route('sales.shipments.rekon', $shipment) }}" class="cf-btn">Scan Pesanan</a>
    <span class="cf-code">{{ $shipment->code }}</span>
    <span class="cf-pill">Draft</span>
    <span class="cf-spacer"></span>
    <span class="cf-pill">Batch <b>{{ $totalLines }}</b> SKU</span>
    <span class="cf-pill cf-pill-main">Qty <b>{{ number_format($totalQty,0,',','.') }}</b></span>
</div>

<div class="cf-wrap">
    <div class="cf-flow">
        <span class="cf-step done">Scan Barang</span><span class="cf-sep">→</span>
        <span class="cf-step done">Scan Pesanan</span><span class="cf-sep">→</span>
        <span class="cf-step active">Konfirmasi Pesanan</span><span class="cf-sep">→</span>
        <span class="cf-step">Simpan &amp; Kurangi Stok</span>
    </div>

    <div class="cf-card">
        <div class="cf-card-h">
            <div>
                <div class="cf-title">Pesanan Siap Dikonfirmasi</div>
                <div class="cf-muted">Nomor pesanan masih mode belum tertaut. Konfirmasi akan mencatat daftar pesanan ke shipment.</div>
            </div>
            <span class="cf-spacer"></span>
            <span class="cf-pill">Pesanan <b id="orderCount">0</b></span>
        </div>
        <div class="cf-body">
            <div id="orderList" class="cf-list"></div>
            <div id="emptyOrders" class="cf-empty">
                Belum ada pesanan yang discan. Kembali ke Scan Pesanan dulu.
            </div>
        </div>
    </div>

    <div class="cf-card">
        <div class="cf-card-h">
            <div>
                <div class="cf-title">Stok Batch</div>
                <div class="cf-muted">Saat ini belum dialokasikan ke pesanan karena tautan invoice/order belum dipakai.</div>
            </div>
        </div>
        <div class="cf-body">
            <div class="cf-batch">
                @forelse($batchPool as $item)
                    <div class="cf-batch-row">
                        <span class="cf-item-code">{{ $item['item_code'] }}</span>
                        <span class="cf-item-name">{{ $item['item_name'] }}</span>
                        <span class="cf-item-qty">{{ number_format($item['qty'],0,',','.') }}</span>
                    </div>
                @empty
                    <div class="cf-empty">Belum ada item batch.</div>
                @endforelse
            </div>
        </div>
    </div>

    <div class="cf-actions">
        <a href="{{ route('sales.shipments.rekon', $shipment) }}" class="cf-btn">Kembali Scan Pesanan</a>
        <button type="button" class="cf-primary" id="confirmBtn" disabled>Konfirmasi Pesanan</button>
    </div>
</div>

<form id="submitForm" method="POST" action="{{ route('sales.shipments.submit', $shipment) }}" style="display:none">
    @csrf
</form>
@endsection

@push('scripts')
<script>
(function(){
    'use strict';
    const CSRF = document.querySelector('meta[name="csrf-token"]')?.content || @json(csrf_token());
    const STORE_KEY = 'rk_state_{{ $shipment->id }}';
    const APPLY_URL = @json(parse_url(route('sales.shipments.rekon_apply', $shipment), PHP_URL_PATH));
    const EDIT_URL = @json(parse_url(route('sales.shipments.edit', $shipment), PHP_URL_PATH));
    const SUBMIT_URL = @json(parse_url(route('sales.shipments.submit', $shipment), PHP_URL_PATH));
    const SERVER_ORDER_SCANS = @json($savedOrderScans ?? []);

    const orderList = document.getElementById('orderList');
    const emptyOrders = document.getElementById('emptyOrders');
    const orderCount = document.getElementById('orderCount');
    const confirmBtn = document.getElementById('confirmBtn');

    function loadOrders() {
        try {
            const raw = localStorage.getItem(STORE_KEY);
            if (!raw) return Array.isArray(SERVER_ORDER_SCANS) ? SERVER_ORDER_SCANS : [];
            const state = JSON.parse(raw);
            const seen = new Set();
            return (state.orders || []).filter(o => {
                const no = String(o.no || '').trim().toUpperCase();
                if (!no || seen.has(no)) return false;
                seen.add(no);
                o.no = no;
                o.decision = o.decision || 'pending';
                return true;
            });
        } catch { return []; }
    }

    function render() {
        const orders = loadOrders();
        orderCount.textContent = orders.length;
        confirmBtn.disabled = orders.length === 0;
        emptyOrders.style.display = orders.length ? 'none' : '';
        orderList.innerHTML = orders.map(o => {
            const action = o.decision || 'pending';
            const label = action === 'skip' ? 'Diabaikan' : 'Ditunda';
            return `<div class="cf-order">
                <div>
                    <div class="cf-order-no">${o.no}</div>
                    <div class="cf-muted">Belum tertaut ke invoice/order</div>
                </div>
                <span class="cf-badge ${action}">${label}</span>
            </div>`;
        }).join('');
    }

    confirmBtn.addEventListener('click', async function() {
        const orders = loadOrders();
        if (!orders.length) return;
        confirmBtn.disabled = true;
        confirmBtn.textContent = 'Menyimpan...';

        try {
            const fd = new FormData();
            fd.append('_token', CSRF);
            orders.forEach((o, i) => {
                fd.append(`decisions[${i}][order_no]`, o.no);
                fd.append(`decisions[${i}][action]`, o.decision || 'pending');
            });

            const res = await fetch(APPLY_URL, {
                method: 'POST',
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                body: fd,
            });
            const data = await res.json();
            if (!res.ok || data.status !== 'ok') throw new Error(data.message || 'Gagal konfirmasi pesanan.');

            const goSubmit = confirm('Pesanan dikonfirmasi.\n\nLanjut Simpan & Kurangi Stok sekarang?');
            if (goSubmit) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = data.submit_url || SUBMIT_URL;
                form.innerHTML = `<input type="hidden" name="_token" value="${CSRF}">`;
                document.body.appendChild(form);
                form.submit();
            } else {
                localStorage.removeItem(STORE_KEY);
                window.location.href = data.edit_url || EDIT_URL;
            }
        } catch (err) {
            alert(err.message);
            confirmBtn.disabled = false;
            confirmBtn.textContent = 'Konfirmasi Pesanan';
        }
    });

    render();
})();
</script>
@endpush
