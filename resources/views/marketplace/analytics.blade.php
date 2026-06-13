@extends('layouts.app')
@section('title', 'Marketplace • Sales Analytics')

@include('marketplace._shared')

@push('head')
<style>
/* ── KPI ── */
.an-kpi-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(160px,1fr)); gap:.75rem; margin-bottom:1.5rem; }
.an-kpi-card {
    background:#fff; border:1.5px solid #e2e8f0; border-radius:16px;
    padding:1rem 1.1rem; display:flex; flex-direction:column; gap:.25rem;
}
.an-kpi-label { font-size:.68rem; font-weight:700; color:#94a3b8; text-transform:uppercase; letter-spacing:.04em; }
.an-kpi-value { font-size:1.3rem; font-weight:900; color:#0f172a; line-height:1.1; }
.an-kpi-note  { font-size:.7rem; color:#94a3b8; }
.an-kpi-card.highlight { background:#0f172a; border-color:#0f172a; }
.an-kpi-card.highlight .an-kpi-label,
.an-kpi-card.highlight .an-kpi-value,
.an-kpi-card.highlight .an-kpi-note { color:#fff; }
.an-kpi-card.highlight .an-kpi-note { color:rgba(255,255,255,.5); }

/* ── Tabs ── */
.an-tabs { display:flex; gap:.3rem; margin-bottom:1.25rem; border-bottom:1.5px solid #e2e8f0; padding-bottom:.1rem; }
.an-tab {
    background:none; border:none; padding:.45rem .9rem; font-size:.8rem; font-weight:700;
    color:#64748b; border-radius:8px 8px 0 0; cursor:pointer; border-bottom:2.5px solid transparent;
    margin-bottom:-1.5px;
}
.an-tab.active { color:#0f172a; border-bottom-color:#0f172a; }
.an-tab-panel { display:none; }
.an-tab-panel.active { display:block; }

/* ── Chart placeholder ── */
.an-chart-wrap {
    background:#f8fafc; border:1.5px dashed #e2e8f0; border-radius:14px;
    height:220px; display:flex; align-items:center; justify-content:center;
    color:#94a3b8; font-size:.8rem; font-weight:600; margin-bottom:1.25rem;
}

/* ── Filter bar ── */
.an-filter-bar { display:flex; gap:.6rem; flex-wrap:wrap; align-items:flex-end; margin-bottom:1.25rem; }
.an-filter-bar label { font-size:.68rem; font-weight:700; color:#94a3b8; display:block; margin-bottom:.2rem; }
.an-filter-bar input,
.an-filter-bar select { font-size:.8rem; border-radius:10px; border:1.5px solid #e2e8f0; padding:.3rem .65rem; background:#fff; }

/* ── Table ── */
.an-empty { text-align:center; padding:2.5rem 1rem; color:#94a3b8; font-size:.82rem; }
</style>
@endpush

@section('content')
<x-gf.page eyebrow="Marketplace" title="Sales Analytics"
    description="Gambaran penjualan untuk pengambilan keputusan bisnis.">

    <x-slot:actions>
        {{-- Date range --}}
        <input type="text" id="anDateRange" autocomplete="off"
            value="{{ $filters['date_from'] }} — {{ $filters['date_to'] }}"
            style="min-width:190px;border-radius:999px;font-size:.78rem;font-weight:700;
                   border:1px solid rgba(15,23,42,.1);padding:.35rem .9rem;background:#fff">
        <input type="hidden" id="anDateFrom" value="{{ $filters['date_from'] }}">
        <input type="hidden" id="anDateTo"   value="{{ $filters['date_to'] }}">
        <button class="btn btn-light border btn-sm" style="border-radius:999px;font-size:.78rem;font-weight:700"
            onclick="loadAll()">↻ Refresh</button>
    </x-slot:actions>

    {{-- KPI --}}
    <div class="an-kpi-grid">
        <div class="an-kpi-card highlight">
            <div class="an-kpi-label">Total Revenue</div>
            <div class="an-kpi-value" id="kpiRevenue">—</div>
            <div class="an-kpi-note">yang dibayar konsumen</div>
        </div>
        <div class="an-kpi-card">
            <div class="an-kpi-label">Jumlah Order</div>
            <div class="an-kpi-value" id="kpiOrders">—</div>
            <div class="an-kpi-note">order masuk</div>
        </div>
        <div class="an-kpi-card">
            <div class="an-kpi-label">Rata-rata Transaksi</div>
            <div class="an-kpi-value" id="kpiAov" style="font-size:1rem">—</div>
            <div class="an-kpi-note">revenue / order</div>
        </div>
        <div class="an-kpi-card">
            <div class="an-kpi-label">Order Selesai</div>
            <div class="an-kpi-value" id="kpiCompleted">—</div>
            <div class="an-kpi-note" id="kpiCompletedRate">dari total order</div>
        </div>
        <div class="an-kpi-card">
            <div class="an-kpi-label">Order Dibatal</div>
            <div class="an-kpi-value" id="kpiCancelled" style="color:#ef4444">—</div>
            <div class="an-kpi-note" id="kpiCancelledRate">cancel rate</div>
        </div>
    </div>

    {{-- Tabs --}}
    <div class="an-tabs">
        <button class="an-tab active" onclick="switchTab('overview', this)">Overview</button>
        <button class="an-tab" onclick="switchTab('products', this)">Produk Terlaris</button>
        <button class="an-tab" onclick="switchTab('buyers', this)">Daya Beli</button>
        <button class="an-tab" onclick="switchTab('shipping', this)">Pengiriman</button>
    </div>

    {{-- Tab: Overview --}}
    <div id="tab-overview" class="an-tab-panel active">
        <x-gf.panel title="Tren Penjualan" subtitle="Revenue harian dalam periode terpilih.">
            <div class="an-chart-wrap" id="chartRevenue">
                <span>📊 Grafik akan ditampilkan di sini</span>
            </div>
        </x-gf.panel>

        <x-gf.panel title="Per Toko" subtitle="Breakdown revenue dan order per toko/channel.">
            <div id="storeBreakdownBody">
                <div class="prod-tab-loading"><span class="prod-tab-spinner"></span> Memuat…</div>
            </div>
        </x-gf.panel>
    </div>

    {{-- Tab: Produk Terlaris --}}
    <div id="tab-products" class="an-tab-panel">
        <x-gf.panel title="Produk Terlaris" subtitle="Berdasarkan jumlah qty terjual.">
            <div id="topProductsBody">
                <div class="prod-tab-loading"><span class="prod-tab-spinner"></span> Memuat…</div>
            </div>
        </x-gf.panel>
    </div>

    {{-- Tab: Daya Beli --}}
    <div id="tab-buyers" class="an-tab-panel">
        <x-gf.panel title="Distribusi Nilai Transaksi" subtitle="Berapa yang biasanya dibayar konsumen per order?">
            <div id="buyerPowerBody">
                <div class="prod-tab-loading"><span class="prod-tab-spinner"></span> Memuat…</div>
            </div>
        </x-gf.panel>
    </div>

    {{-- Tab: Pengiriman --}}
    <div id="tab-shipping" class="an-tab-panel">
        <x-gf.panel title="Status Pengiriman" subtitle="Breakdown order berdasarkan status terakhir.">
            <div id="shippingBody">
                <div class="prod-tab-loading"><span class="prod-tab-spinner"></span> Memuat…</div>
            </div>
        </x-gf.panel>
    </div>

</x-gf.page>
@endsection

@push('scripts')
<script>
(function () {
    const { api, fmtDate, fmtRp, esc, channelPill, statusBadge } = window.mpHelpers;
    const $ = id => document.getElementById(id);
    const getFrom = () => $('anDateFrom').value;
    const getTo   = () => $('anDateTo').value;

    let orders = [];

    // ── Flatpickr ─────────────────────────────────────────────────────────
    if (window.flatpickr) {
        flatpickr($('anDateRange'), {
            mode: 'range', dateFormat: 'Y-m-d',
            defaultDate: [getFrom(), getTo()],
            onChange(dates) {
                if (dates.length === 2) {
                    $('anDateFrom').value = dates[0].toISOString().slice(0,10);
                    $('anDateTo').value   = dates[1].toISOString().slice(0,10);
                    $('anDateRange').value = getFrom() + ' — ' + getTo();
                    history.replaceState(null, '', location.pathname + '?date_from=' + getFrom() + '&date_to=' + getTo());
                    render();
                }
            }
        });
    }

    // ── Tab switch ────────────────────────────────────────────────────────
    window.switchTab = function (name, btn) {
        document.querySelectorAll('.an-tab-panel').forEach(p => p.classList.remove('active'));
        document.querySelectorAll('.an-tab').forEach(b => b.classList.remove('active'));
        $('tab-' + name).classList.add('active');
        btn.classList.add('active');
    };

    // ── Load ──────────────────────────────────────────────────────────────
    window.loadAll = async function () {
        orders = await api('/api/marketplace/local-orders').catch(() => []);
        render();
    };

    function inRange(o) {
        if (!o.ordered_at) return true;
        const d = new Date(o.ordered_at);
        return d >= new Date(getFrom() + 'T00:00:00') && d <= new Date(getTo() + 'T23:59:59');
    }

    function render() {
        const rows = orders.filter(inRange);
        renderKpi(rows);
        renderStoreBreakdown(rows);
        renderTopProducts(rows);
        renderBuyerPower(rows);
        renderShipping(rows);
    }

    // ── KPI ───────────────────────────────────────────────────────────────
    function renderKpi(rows) {
        const completed  = rows.filter(o => o.order_status === 'COMPLETED');
        const cancelled  = rows.filter(o => o.order_status === 'CANCELLED');
        const revenue    = completed.reduce((s, o) => s + parseFloat(o.total_amount || 0), 0);
        const aov        = completed.length ? revenue / completed.length : 0;
        const cancelRate = rows.length ? (cancelled.length / rows.length * 100).toFixed(1) : 0;
        const doneRate   = rows.length ? (completed.length / rows.length * 100).toFixed(1) : 0;

        $('kpiRevenue').textContent        = fmtRp(revenue);
        $('kpiOrders').textContent         = rows.length;
        $('kpiAov').textContent            = fmtRp(aov);
        $('kpiCompleted').textContent      = completed.length;
        $('kpiCompletedRate').textContent  = doneRate + '% dari total';
        $('kpiCancelled').textContent      = cancelled.length;
        $('kpiCancelledRate').textContent  = cancelRate + '% cancel rate';
    }

    // ── Store Breakdown ───────────────────────────────────────────────────
    function renderStoreBreakdown(rows) {
        const el = $('storeBreakdownBody');
        const map = {};
        rows.forEach(o => {
            const key  = o.store?.name || '—';
            const ch   = o.store?.channel || '';
            if (!map[key]) map[key] = { name: key, channel: ch, orders: 0, revenue: 0, completed: 0, cancelled: 0 };
            map[key].orders++;
            if (o.order_status === 'COMPLETED') { map[key].revenue += parseFloat(o.total_amount || 0); map[key].completed++; }
            if (o.order_status === 'CANCELLED')   map[key].cancelled++;
        });
        const stores = Object.values(map).sort((a, b) => b.revenue - a.revenue);
        if (!stores.length) { el.innerHTML = '<div class="an-empty">Belum ada data.</div>'; return; }
        el.innerHTML = `
        <div class="gf-table-scroll">
        <table class="gf-clean-table w-100">
            <thead><tr>
                <th>Toko</th>
                <th class="text-end">Order</th>
                <th class="text-end">Selesai</th>
                <th class="text-end">Batal</th>
                <th class="text-end">Revenue</th>
            </tr></thead>
            <tbody>
            ${stores.map(s => `<tr>
                <td>
                    <span class="fw-bold" style="font-size:.82rem">${esc(s.name)}</span>
                    <span class="ms-1">${channelPill(s.channel)}</span>
                </td>
                <td class="text-end" style="font-size:.82rem">${s.orders}</td>
                <td class="text-end" style="font-size:.82rem;color:#166534">${s.completed}</td>
                <td class="text-end" style="font-size:.82rem;color:#b91c1c">${s.cancelled}</td>
                <td class="text-end fw-bold" style="font-size:.82rem">${fmtRp(s.revenue)}</td>
            </tr>`).join('')}
            </tbody>
        </table></div>`;
    }

    // ── Top Products ──────────────────────────────────────────────────────
    function renderTopProducts(rows) {
        const el = $('topProductsBody');
        const map = {};
        rows.filter(o => o.order_status !== 'CANCELLED').forEach(o => {
            (o.items || []).forEach(i => {
                const key = i.model_sku || i.item_sku || '—';
                if (!map[key]) map[key] = { sku: key, name: i.variant_name || i.item_name || '—', qty: 0, revenue: 0 };
                map[key].qty     += parseInt(i.qty || 0);
                map[key].revenue += parseFloat(i.model_discounted_price || i.model_original_price || 0) * parseInt(i.qty || 0);
            });
        });
        const products = Object.values(map).sort((a, b) => b.qty - a.qty).slice(0, 20);
        if (!products.length) { el.innerHTML = '<div class="an-empty">Belum ada data item.</div>'; return; }

        const maxQty = products[0].qty || 1;
        el.innerHTML = `
        <div class="gf-table-scroll">
        <table class="gf-clean-table w-100">
            <thead><tr>
                <th>#</th><th>SKU / Varian</th>
                <th class="text-end">Qty Terjual</th>
                <th class="text-end">Est. Revenue</th>
            </tr></thead>
            <tbody>
            ${products.map((p, idx) => `<tr>
                <td style="color:#94a3b8;font-size:.78rem;font-weight:700">${idx + 1}</td>
                <td>
                    <div class="fw-bold" style="font-size:.8rem">${esc(p.sku)}</div>
                    <div style="font-size:.72rem;color:#64748b">${esc(p.name)}</div>
                    <div style="margin-top:.3rem;height:4px;border-radius:999px;background:#f1f5f9;width:100%;max-width:200px">
                        <div style="height:100%;border-radius:999px;background:#0f172a;width:${Math.round(p.qty/maxQty*100)}%"></div>
                    </div>
                </td>
                <td class="text-end fw-bold" style="font-size:.82rem">${p.qty} pcs</td>
                <td class="text-end" style="font-size:.82rem">${fmtRp(p.revenue)}</td>
            </tr>`).join('')}
            </tbody>
        </table></div>`;
    }

    // ── Buyer Power (distribusi nilai transaksi) ──────────────────────────
    function renderBuyerPower(rows) {
        const el = $('buyerPowerBody');
        const buckets = [
            { label: '< Rp50rb',           min: 0,       max: 50000 },
            { label: 'Rp50rb – 100rb',     min: 50000,   max: 100000 },
            { label: 'Rp100rb – 200rb',    min: 100000,  max: 200000 },
            { label: 'Rp200rb – 500rb',    min: 200000,  max: 500000 },
            { label: 'Rp500rb – 1jt',      min: 500000,  max: 1000000 },
            { label: '> Rp1jt',            min: 1000000, max: Infinity },
        ];
        const completed = rows.filter(o => o.order_status === 'COMPLETED');
        buckets.forEach(b => {
            b.count   = completed.filter(o => { const v = parseFloat(o.total_amount||0); return v >= b.min && v < b.max; }).length;
            b.revenue = completed.filter(o => { const v = parseFloat(o.total_amount||0); return v >= b.min && v < b.max; })
                                 .reduce((s, o) => s + parseFloat(o.total_amount||0), 0);
        });
        const total = completed.length || 1;
        const maxCount = Math.max(...buckets.map(b => b.count), 1);
        if (!completed.length) { el.innerHTML = '<div class="an-empty">Belum ada order selesai di periode ini.</div>'; return; }

        el.innerHTML = `
        <div style="display:flex;flex-direction:column;gap:.6rem;padding:.25rem 0">
        ${buckets.map(b => {
            const pct = Math.round(b.count / total * 100);
            const bar = Math.round(b.count / maxCount * 100);
            return `<div style="display:grid;grid-template-columns:140px 1fr 70px 100px;gap:.75rem;align-items:center">
                <span style="font-size:.78rem;font-weight:700;color:#475569">${b.label}</span>
                <div style="height:8px;border-radius:999px;background:#f1f5f9">
                    <div style="height:100%;border-radius:999px;background:#0f172a;width:${bar}%;transition:width .4s"></div>
                </div>
                <span style="font-size:.78rem;color:#0f172a;font-weight:800;text-align:right">${b.count} <span style="font-weight:400;color:#94a3b8">(${pct}%)</span></span>
                <span style="font-size:.75rem;color:#64748b;text-align:right">${fmtRp(b.revenue)}</span>
            </div>`;
        }).join('')}
        </div>
        <div style="margin-top:1rem;padding-top:.75rem;border-top:1px solid #f1f5f9;font-size:.75rem;color:#94a3b8">
            Berdasarkan ${completed.length} order selesai (COMPLETED) di periode ini.
        </div>`;
    }

    // ── Shipping Status ───────────────────────────────────────────────────
    function renderShipping(rows) {
        const el = $('shippingBody');
        const map = {};
        rows.forEach(o => {
            const s = o.order_status || 'UNKNOWN';
            if (!map[s]) map[s] = { status: s, count: 0, revenue: 0 };
            map[s].count++;
            map[s].revenue += parseFloat(o.total_amount || 0);
        });
        const statuses = Object.values(map).sort((a, b) => b.count - a.count);
        if (!statuses.length) { el.innerHTML = '<div class="an-empty">Belum ada data.</div>'; return; }
        const total = rows.length || 1;
        el.innerHTML = `
        <div class="gf-table-scroll">
        <table class="gf-clean-table w-100">
            <thead><tr>
                <th>Status</th>
                <th class="text-end">Jumlah</th>
                <th class="text-end">%</th>
                <th class="text-end">Total Nilai</th>
            </tr></thead>
            <tbody>
            ${statuses.map(s => `<tr>
                <td>${statusBadge(s.status)}</td>
                <td class="text-end fw-bold" style="font-size:.82rem">${s.count}</td>
                <td class="text-end" style="font-size:.82rem;color:#64748b">${(s.count/total*100).toFixed(1)}%</td>
                <td class="text-end" style="font-size:.82rem">${fmtRp(s.revenue)}</td>
            </tr>`).join('')}
            </tbody>
        </table></div>`;
    }

    loadAll();
})();
</script>
@endpush
