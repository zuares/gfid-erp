@extends('layouts.app')
@section('title', 'Marketplace • Order Lokal')

@include('owner.marketplace._shared')

@section('content')
<x-gf.page eyebrow="Marketplace" title="Order Lokal" description="Order yang sudah disync dari marketplace ke database lokal.">
    <x-slot:actions>
        <input type="text" id="mpDateRange" autocomplete="off" aria-label="Rentang tanggal"
            value="{{ $filters['date_from'] }} — {{ $filters['date_to'] }}"
            style="min-width:190px;border-radius:999px;font-size:.78rem;font-weight:700;border:1px solid rgba(15,23,42,.1);padding:.35rem .9rem;background:#fff;box-shadow:none">
        <input type="hidden" id="mpDateFrom" value="{{ $filters['date_from'] }}">
        <input type="hidden" id="mpDateTo"   value="{{ $filters['date_to'] }}">
        <button type="button" class="btn btn-light border btn-sm"
            style="border-radius:999px;font-size:.78rem;font-weight:700"
            onclick="loadOrders()">↻ Refresh</button>
    </x-slot:actions>

    {{-- KPI --}}
    <div class="oc-kpi-grid">
        <div class="oc-kpi-card"><div class="oc-kpi-label">Total Order</div><div class="oc-kpi-value" id="kpiTotal">—</div><div class="oc-kpi-note" id="kpiPeriod">periode ini</div></div>
        <div class="oc-kpi-card"><div class="oc-kpi-label">Completed</div><div class="oc-kpi-value" id="kpiCompleted">—</div><div class="oc-kpi-note">COMPLETED</div></div>
        <div class="oc-kpi-card"><div class="oc-kpi-label">Cancelled</div><div class="oc-kpi-value" id="kpiCancelled">—</div><div class="oc-kpi-note">CANCELLED</div></div>
        <div class="oc-kpi-card"><div class="oc-kpi-label">Total GMV</div><div class="oc-kpi-value" id="kpiGmv" style="font-size:.88rem">—</div><div class="oc-kpi-note">total_amount</div></div>
    </div>

    <x-gf.panel title="Order Tersimpan" subtitle="Order yang sudah disync ke database.">
        <div id="ordersBody"><div class="prod-tab-loading"><span class="prod-tab-spinner"></span> Memuat…</div></div>
    </x-gf.panel>
</x-gf.page>
@endsection

@push('scripts')
<script>
(function () {
    const { api, fmtDate, fmtRp, esc, channelPill, statusBadge } = window.mpHelpers;
    let orders = [];

    const $ = id => document.getElementById(id);
    const getFrom = () => $('mpDateFrom').value;
    const getTo   = () => $('mpDateTo').value;

    // Flatpickr
    if (window.flatpickr) {
        flatpickr($('mpDateRange'), {
            mode: 'range', dateFormat: 'Y-m-d',
            defaultDate: [getFrom(), getTo()],
            onChange(dates) {
                if (dates.length === 2) {
                    $('mpDateFrom').value = dates[0].toISOString().slice(0,10);
                    $('mpDateTo').value   = dates[1].toISOString().slice(0,10);
                    $('mpDateRange').value = $('mpDateFrom').value + ' — ' + $('mpDateTo').value;
                    const p = new URLSearchParams({ date_from: getFrom(), date_to: getTo() });
                    history.replaceState(null, '', location.pathname + '?' + p);
                    renderOrders();
                }
            }
        });
    }

    async function loadOrders() {
        $('ordersBody').innerHTML = '<div class="prod-tab-loading"><span class="prod-tab-spinner"></span> Memuat…</div>';
        orders = await api('/api/omnichannel/local-orders').catch(() => []);
        renderKpi();
        renderOrders();
    }

    function renderKpi() {
        const from = new Date(getFrom() + 'T00:00:00'), to = new Date(getTo() + 'T23:59:59');
        const f = orders.filter(o => { if (!o.ordered_at) return true; const d = new Date(o.ordered_at); return d >= from && d <= to; });
        $('kpiTotal').textContent     = f.length;
        $('kpiCompleted').textContent = f.filter(o => o.order_status === 'COMPLETED').length;
        $('kpiCancelled').textContent = f.filter(o => o.order_status === 'CANCELLED').length;
        $('kpiGmv').textContent       = fmtRp(f.reduce((s,o) => s + parseFloat(o.total_amount||0), 0));
        $('kpiPeriod').textContent    = getFrom() + ' s/d ' + getTo();
    }

    function renderOrders() {
        const body = $('ordersBody');
        const from = new Date(getFrom() + 'T00:00:00'), to = new Date(getTo() + 'T23:59:59');
        const rows = orders.filter(o => { if (!o.ordered_at) return true; const d = new Date(o.ordered_at); return d >= from && d <= to; });

        if (!rows.length) { body.innerHTML = '<div class="oc-empty">Belum ada order di rentang ini.</div>'; return; }

        body.innerHTML = `
        <div class="gf-table-scroll">
        <table class="gf-clean-table w-100">
            <thead><tr><th>Order</th><th>Toko</th><th>Status</th><th>Item</th><th class="text-end">Total</th></tr></thead>
            <tbody>
            ${rows.map(o => {
                const items = o.items || [];
                return `<tr>
                    <td>
                        <div class="fw-bold" style="font-size:.8rem">${esc(o.channel_order_id||'—')}</div>
                        <div class="text-muted" style="font-size:.7rem">${o.ordered_at ? fmtDate(o.ordered_at) : '—'}</div>
                    </td>
                    <td>
                        <div class="fw-semibold" style="font-size:.82rem">${esc(o.store?.name||'—')}</div>
                        <div>${channelPill(o.store?.channel)}</div>
                    </td>
                    <td>${statusBadge(o.order_status)}</td>
                    <td style="font-size:.78rem">
                        ${items.length ? items.slice(0,2).map(i =>
                            `<div class="mb-1"><span class="fw-bold">${esc(i.model_sku||i.item_sku||'—')}</span> · ${esc(i.variant_name||'—')} · ${i.qty} pcs</div>`
                        ).join('') + (items.length > 2 ? `<div class="text-muted">+${items.length-2} lainnya</div>` : '') : '<span class="text-muted">—</span>'}
                    </td>
                    <td class="text-end fw-bold" style="white-space:nowrap">${fmtRp(o.total_amount)}</td>
                </tr>`;
            }).join('')}
            </tbody>
        </table></div>
        <div class="gf-table-foot"><span class="gf-table-foot-hint">${rows.length} order ditampilkan</span></div>`;
    }

    window.loadOrders = loadOrders;
    loadOrders();
})();
</script>
@endpush
