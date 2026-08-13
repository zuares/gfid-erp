@extends('layouts.app')
@section('title', 'Marketplace • Analytics')

@include('marketplace._shared')

@push('head')
<style>
    .an-shell { display:grid; gap:1rem; }
    .an-toolbar { display:flex; align-items:end; justify-content:space-between; gap:.75rem; flex-wrap:wrap; }
    .an-toolbar-controls { display:flex; gap:.5rem; align-items:end; flex-wrap:wrap; }
    .an-field label { display:block; color:#64748b; font-size:.68rem; font-weight:850; margin:0 0 .25rem; }
    .an-field input, .an-field select { min-height:38px; border:1px solid rgba(15,23,42,.12); border-radius:12px; padding:.45rem .7rem; background:#fff; color:#0f172a; font-size:.78rem; font-weight:700; }
    .an-field input { min-width:190px; }
    .an-btn { min-height:38px; border:1px solid rgba(15,23,42,.1); border-radius:12px; padding:.45rem .8rem; background:#fff; color:#0f172a; font-size:.76rem; font-weight:850; cursor:pointer; }
    .an-btn:hover { background:#f8fafc; }
    .an-btn-dark { background:#0f172a; border-color:#0f172a; color:#fff; }
    .an-btn-dark:hover { background:#1e293b; color:#fff; }
    .an-sync-note { color:#94a3b8; font-size:.7rem; font-weight:700; margin-top:.3rem; }
    .an-kpis { display:grid; grid-template-columns:repeat(6,minmax(0,1fr)); gap:.7rem; }
    .an-kpi { min-height:112px; border:1px solid rgba(15,23,42,.08); border-radius:18px; background:#fff; padding:1rem; display:flex; flex-direction:column; justify-content:space-between; box-shadow:0 8px 22px rgba(15,23,42,.035); }
    .an-kpi.primary { background:#0f172a; color:#fff; border-color:#0f172a; }
    .an-kpi-label { color:#64748b; font-size:.67rem; font-weight:850; text-transform:uppercase; letter-spacing:.06em; }
    .an-kpi.primary .an-kpi-label { color:#94a3b8; }
    .an-kpi-value { color:#0f172a; font-size:1.35rem; font-weight:950; letter-spacing:-.035em; line-height:1.1; }
    .an-kpi.primary .an-kpi-value { color:#fff; }
    .an-kpi-note { color:#94a3b8; font-size:.69rem; font-weight:700; }
    .an-kpi-note.good { color:#16a34a; }
    .an-kpi-note.bad { color:#dc2626; }
    .an-grid-main { display:grid; grid-template-columns:minmax(0,1.6fr) minmax(280px,.9fr); gap:1rem; }
    .an-grid-secondary { display:grid; grid-template-columns:minmax(0,1.2fr) minmax(280px,.8fr); gap:1rem; }
    .an-card { min-width:0; border:1px solid var(--gf-border,#e5e7eb); border-radius:20px; background:#fff; box-shadow:0 8px 24px rgba(15,23,42,.035); overflow:hidden; }
    .an-card-head { padding:1rem 1.15rem .75rem; display:flex; justify-content:space-between; align-items:start; gap:.75rem; }
    .an-card-title { color:#0f172a; font-size:.9rem; font-weight:950; }
    .an-card-sub { color:#94a3b8; font-size:.7rem; font-weight:700; margin-top:.2rem; }
    .an-card-body { padding:0 1.15rem 1.15rem; }
    .an-chart { min-height:250px; position:relative; padding:1rem 0 .35rem; }
    .an-chart-grid { position:absolute; inset:1rem 0 2rem; display:flex; flex-direction:column; justify-content:space-between; pointer-events:none; }
    .an-chart-grid span { border-top:1px dashed #e2e8f0; width:100%; }
    .an-chart-svg { width:100%; height:220px; position:relative; z-index:1; overflow:visible; }
    .an-chart-axis { display:flex; justify-content:space-between; color:#94a3b8; font-size:.64rem; font-weight:750; padding:0 .1rem; }
    .an-legend { display:flex; gap:.85rem; color:#64748b; font-size:.68rem; font-weight:750; }
    .an-legend i { display:inline-block; width:8px; height:8px; border-radius:99px; margin-right:.3rem; background:#0f172a; }
    .an-legend i.green { background:#16a34a; }
    .an-list { display:grid; gap:.3rem; }
    .an-list-row { display:grid; grid-template-columns:minmax(0,1fr) auto; align-items:center; gap:.8rem; padding:.68rem 0; border-bottom:1px solid #f1f5f9; }
    .an-list-row:last-child { border-bottom:0; }
    .an-list-main { min-width:0; }
    .an-list-name { color:#0f172a; font-size:.76rem; font-weight:850; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    .an-list-meta { color:#94a3b8; font-size:.65rem; font-weight:700; margin-top:.15rem; }
    .an-list-value { text-align:right; color:#0f172a; font-size:.75rem; font-weight:900; }
    .an-bar { height:6px; margin-top:.38rem; border-radius:999px; background:#f1f5f9; overflow:hidden; }
    .an-bar > span { display:block; height:100%; border-radius:inherit; background:#0f172a; }
    .an-bar.green > span { background:#16a34a; }
    .an-table-wrap { overflow:auto; }
    .an-table { width:100%; border-collapse:collapse; min-width:560px; }
    .an-table th { padding:.55rem .5rem; text-align:left; border-bottom:1px solid #e2e8f0; color:#94a3b8; font-size:.64rem; font-weight:850; text-transform:uppercase; letter-spacing:.04em; white-space:nowrap; }
    .an-table td { padding:.68rem .5rem; border-bottom:1px solid #f1f5f9; color:#334155; font-size:.73rem; font-weight:700; vertical-align:middle; }
    .an-table th:not(:first-child), .an-table td:not(:first-child) { text-align:right; }
    .an-table tr:last-child td { border-bottom:0; }
    .an-rank { width:24px; height:24px; display:inline-grid; place-items:center; border-radius:8px; background:#f1f5f9; color:#64748b; font-size:.65rem; font-weight:950; }
    .an-product { display:inline-flex; align-items:center; gap:.55rem; min-width:180px; text-align:left; }
    .an-product-copy { min-width:0; }
    .an-product-name { color:#0f172a; font-size:.74rem; font-weight:850; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:250px; }
    .an-product-sku { color:#94a3b8; font-size:.63rem; font-weight:700; margin-top:.1rem; }
    .an-dot { width:8px; height:8px; border-radius:99px; background:#16a34a; flex:0 0 auto; }
    .an-dot.red { background:#ef4444; }
    .an-funnel { display:flex; flex-direction:column; gap:.55rem; }
    .an-funnel-row { display:grid; grid-template-columns:90px 1fr auto; align-items:center; gap:.6rem; font-size:.7rem; font-weight:800; color:#475569; }
    .an-funnel-track { height:28px; border-radius:8px; background:#f1f5f9; overflow:hidden; }
    .an-funnel-track span { display:block; height:100%; border-radius:inherit; background:#16a34a; }
    .an-funnel-row:nth-child(2) .an-funnel-track span { background:#86efac; }
    .an-funnel-row:nth-child(3) .an-funnel-track span { background:#facc15; }
    .an-funnel-row:nth-child(4) .an-funnel-track span { background:#fda4af; }
    .an-funnel-value { color:#0f172a; text-align:right; white-space:nowrap; }
    .an-costs { display:grid; gap:.72rem; }
    .an-cost-row { display:grid; grid-template-columns:1fr auto; gap:.5rem; font-size:.73rem; color:#64748b; font-weight:750; }
    .an-cost-row strong { color:#0f172a; font-weight:900; }
    .an-empty { padding:1.6rem 0; text-align:center; color:#94a3b8; font-size:.75rem; font-weight:750; }
    .an-error { padding:.8rem .9rem; border:1px solid #fecaca; border-radius:12px; background:#fef2f2; color:#b91c1c; font-size:.73rem; font-weight:750; }
    @media (max-width: 1180px) { .an-kpis { grid-template-columns:repeat(3,minmax(0,1fr)); } }
    @media (max-width: 760px) { .an-grid-main, .an-grid-secondary { grid-template-columns:1fr; } .an-kpis { grid-template-columns:repeat(2,minmax(0,1fr)); } .an-kpi-value { font-size:1.12rem; } .an-field input { min-width:150px; } }
    @media (max-width: 420px) { .an-kpis { grid-template-columns:1fr 1fr; gap:.45rem; } .an-kpi { padding:.72rem; min-height:100px; } .an-toolbar, .an-toolbar-controls { align-items:stretch; } .an-field, .an-field input, .an-btn { width:100%; } }
</style>
@endpush

@section('content')
<x-gf.page eyebrow="Marketplace" title="Analytics" description="Pantau omzet, laba, order, dan kesehatan produk dari satu layar.">
    <div class="an-shell">
        <div class="an-toolbar">
            <div>
                <div class="an-sync-note" id="anSyncNote">Memuat data marketplace…</div>
            </div>
            <div class="an-toolbar-controls">
                <div class="an-field"><label for="anStore">Toko</label><select id="anStore"><option value="">Semua toko</option></select></div>
                <div class="an-field"><label for="anDateRange">Periode</label><input type="text" id="anDateRange" autocomplete="off" value="{{ $filters['date_from'] }} — {{ $filters['date_to'] }}"></div>
                <input type="hidden" id="anDateFrom" value="{{ $filters['date_from'] }}"><input type="hidden" id="anDateTo" value="{{ $filters['date_to'] }}"><input type="hidden" id="anToday" value="{{ now()->toDateString() }}">
                <button class="an-btn an-btn-dark" id="anRefresh" type="button">↻ Refresh</button>
            </div>
        </div>

        <div class="an-kpis">
            <div class="an-kpi primary"><span class="an-kpi-label">Omzet hari ini</span><strong class="an-kpi-value" id="kpiRevenue">—</strong><span class="an-kpi-note" id="kpiRevenueNote">harga setelah diskon</span></div>
            <div class="an-kpi"><span class="an-kpi-label">Laba bersih hari ini</span><strong class="an-kpi-value" id="kpiProfit">—</strong><span class="an-kpi-note" id="kpiProfitNote">setelah biaya & HPP</span></div>
            <div class="an-kpi"><span class="an-kpi-label">Order hari ini</span><strong class="an-kpi-value" id="kpiOrders">—</strong><span class="an-kpi-note" id="kpiOrdersNote">semua status</span></div>
            <div class="an-kpi"><span class="an-kpi-label">Rata-rata order</span><strong class="an-kpi-value" id="kpiAov">—</strong><span class="an-kpi-note">omzet diskon / order</span></div>
            <div class="an-kpi"><span class="an-kpi-label">Voucher seller</span><strong class="an-kpi-value" id="kpiSellerVoucher">—</strong><span class="an-kpi-note">ditanggung penjual</span></div>
            <div class="an-kpi"><span class="an-kpi-label">Pembatalan hari ini</span><strong class="an-kpi-value" id="kpiCancelled">—</strong><span class="an-kpi-note bad" id="kpiCancelledNote">cancel rate</span></div>
        </div>

        <div class="an-grid-main">
            <section class="an-card"><div class="an-card-head"><div><div class="an-card-title">Omzet vs laba harian</div><div class="an-card-sub">Order selesai dalam periode terpilih</div></div><div class="an-legend"><span><i></i>Omzet</span><span><i class="green"></i>Laba</span></div></div><div class="an-card-body"><div class="an-chart" id="revenueChart"><div class="an-empty">Memuat grafik…</div></div></div></section>
            <section class="an-card"><div class="an-card-head"><div><div class="an-card-title">Funnel penjualan</div><div class="an-card-sub">Dari order masuk hingga payout</div></div></div><div class="an-card-body"><div class="an-funnel" id="salesFunnel"><div class="an-empty">Memuat…</div></div></div></section>
        </div>

        <div class="an-grid-secondary">
            <section class="an-card"><div class="an-card-head"><div><div class="an-card-title">Performa per toko</div><div class="an-card-sub">Omzet dihitung dari harga item setelah diskon marketplace</div></div></div><div class="an-card-body"><div class="an-table-wrap"><table class="an-table"><thead><tr><th>Toko</th><th>Order</th><th>Selesai</th><th>Cancel</th><th>Omzet marketplace</th><th>Laba</th></tr></thead><tbody id="storeBody"><tr><td colspan="6"><div class="an-empty">Memuat…</div></td></tr></tbody></table></div></div></section>
            <section class="an-card"><div class="an-card-head"><div><div class="an-card-title">Biaya terbesar</div><div class="an-card-sub">Potongan dari settlement tersinkron</div></div></div><div class="an-card-body"><div class="an-costs" id="costBody"><div class="an-empty">Memuat…</div></div></div></section>
        </div>

        <div class="an-grid-secondary">
            <section class="an-card"><div class="an-card-head"><div><div class="an-card-title">Produk paling menguntungkan</div><div class="an-card-sub">Omzet memakai harga item marketplace setelah diskon</div></div></div><div class="an-card-body"><div class="an-table-wrap"><table class="an-table"><thead><tr><th>Produk</th><th>Qty</th><th>Omzet marketplace</th><th>Laba</th><th>Margin</th></tr></thead><tbody id="bestProductBody"><tr><td colspan="5"><div class="an-empty">Memuat…</div></td></tr></tbody></table></div></div></section>
            <section class="an-card"><div class="an-card-head"><div><div class="an-card-title">Produk perlu perhatian</div><div class="an-card-sub">Laba rendah atau data HPP belum lengkap</div></div></div><div class="an-card-body"><div class="an-list" id="worstProductBody"><div class="an-empty">Memuat…</div></div></div></section>
        </div>
    </div>
</x-gf.page>
@endsection

@push('scripts')
<script>
(function () {
    const { api, fmtRp, esc } = window.mpHelpers;
    const $ = id => document.getElementById(id);
    let orders = [], todayOrders = [];
    const from = () => $('anDateFrom').value;
    const to = () => $('anDateTo').value;
    const today = () => $('anToday').value;
    const n = v => Number.parseFloat(v || 0) || 0;
    const status = o => String(o.order_status || o.status || '').toUpperCase();
    const completed = o => ['COMPLETED', 'DELIVERED', 'CLOSED'].includes(status(o));
    const money = v => fmtRp(Math.round(v || 0));
    const pct = (a,b) => b ? (a / b * 100).toFixed(1) + '%' : '0%';
    const dateKey = o => { const d = new Date(o.ordered_at || o.created_at); return Number.isNaN(d.getTime()) ? null : d.toISOString().slice(0,10); };
    const selectedStore = () => $('anStore').value;
    const inRange = o => { const d = dateKey(o); return !d || (d >= from() && d <= to()); };
    const filtered = () => orders.filter(o => inRange(o) && (!selectedStore() || String(o.store_id || o.store?.id) === selectedStore()));
    const filteredToday = () => todayOrders.filter(o => dateKey(o) === today() && (!selectedStore() || String(o.store_id || o.store?.id) === selectedStore()));
    const settlement = o => o.settlement || {};
    const sellerVoucher = o => {
        const s = settlement(o), raw = s.raw_json || {};
        return n(s.seller_voucher || raw.voucher_from_seller || raw.seller_voucher_rebate || raw.seller_voucher);
    };
    const discountedLine = i => {
        const qty = n(i.model_quantity_purchased || i.quantity_purchased || i.qty || i.active_qty || 0);
        const discounted = n(i.model_discounted_price || i.discounted_price || i.price_after_discount);
        if (discounted > 0) return discounted * (qty || 1);
        if (n(i.line_net_amount) > 0) return n(i.line_net_amount);
        if (n(i.line_gross_amount) > 0) return n(i.line_gross_amount);
        const price = n(i.price || i.model_original_price || i.original_price);
        return price * (qty || 1);
    };
    const marketplaceSales = o => {
        const rawItems = Array.isArray(o.raw_json?.item_list) ? o.raw_json.item_list : [];
        const storedItems = Array.isArray(o.items) ? o.items : [];
        const sourceItems = rawItems.length ? rawItems : storedItems;
        const itemTotal = sourceItems.reduce((sum, i) => sum + discountedLine(i), 0);
        return itemTotal > 0 ? itemTotal : n(o.total_amount || o.total_paid_customer);
    };
    const itemRevenue = i => discountedLine(i);
    const itemCost = i => n(i.hpp_total_snapshot) > 0 ? n(i.hpp_total_snapshot) : n(i.hpp_snapshot || i.hpp_unit_snapshot) * n(i.qty || 0);
    const revenue = o => marketplaceSales(o);
    const fees = o => { const s = settlement(o); return ['commission_fee','service_fee','transaction_fee','affiliate_fee','activity_fee','ad_cost','shipping_insurance_fee','reverse_shipping_fee','escrow_tax'].reduce((sum,k) => sum + n(s[k]), 0) + sellerVoucher(o) + n(s.seller_coin_cash_back); };
    const profit = o => { const items = o.items || []; const cost = items.reduce((s,i) => s + itemCost(i), 0); const s = settlement(o); const base = n(s.final_income) > 0 ? n(s.final_income) : revenue(o) - fees(o); return base - cost; };

    function setLoading(message) { $('anSyncNote').textContent = message; }
    function fillStores() {
        const current = selectedStore();
        const unique = new Map();
        [...orders, ...todayOrders].forEach(o => { const id = o.store_id || o.store?.id; if (id && !unique.has(String(id))) unique.set(String(id), o.store?.name || `Toko #${id}`); });
        $('anStore').innerHTML = '<option value="">Semua toko</option>' + [...unique.entries()].sort((a,b) => a[1].localeCompare(b[1])).map(([id,name]) => `<option value="${esc(id)}">${esc(name)}</option>`).join('');
        $('anStore').value = unique.has(current) ? current : '';
    }
    function renderKpis(rows) {
        const daily = filteredToday(), saleRows = daily.filter(o => !['CANCELLED','BATAL','RETURNED'].includes(status(o)));
        const rev = saleRows.reduce((s,o) => s + revenue(o), 0), prof = saleRows.reduce((s,o) => s + profit(o), 0);
        const cancel = daily.filter(o => ['CANCELLED','BATAL','RETURNED'].includes(status(o)));
        const voucher = daily.reduce((s,o) => s + sellerVoucher(o), 0);
        $('kpiRevenue').textContent = money(rev); $('kpiProfit').textContent = money(prof); $('kpiOrders').textContent = daily.length.toLocaleString('id-ID');
        $('kpiAov').textContent = money(saleRows.length ? rev / saleRows.length : 0); $('kpiSellerVoucher').textContent = money(voucher); $('kpiCancelled').textContent = cancel.length.toLocaleString('id-ID');
        $('kpiRevenueNote').textContent = `${saleRows.length} order · harga setelah diskon`; $('kpiProfitNote').textContent = pct(prof, rev) + ' margin'; $('kpiOrdersNote').textContent = 'tanggal ' + today(); $('kpiCancelledNote').textContent = pct(cancel.length, daily.length) + ' cancel rate';
    }
    function renderChart(rows) {
        const done = rows.filter(completed), map = {};
        done.forEach(o => { const k = dateKey(o); if (!k) return; if (!map[k]) map[k] = {rev:0, prof:0}; map[k].rev += revenue(o); map[k].prof += profit(o); });
        const points = Object.entries(map).sort((a,b) => a[0].localeCompare(b[0]));
        if (!points.length) { $('revenueChart').innerHTML = '<div class="an-empty">Belum ada order selesai di periode ini.</div>'; return; }
        const max = Math.max(...points.flatMap(([,v]) => [v.rev,v.prof]), 1), w = 720, h = 210, pad = 12;
        const line = key => points.map(([,v],i) => { const x = pad + (i * (w-pad*2) / Math.max(points.length-1,1)); const y = h-pad - (Math.max(v[key],0) / max) * (h-pad*2); return `${x},${y}`; }).join(' ');
        const labels = points.map(([d]) => `<span>${new Date(d+'T00:00:00').toLocaleDateString('id-ID',{day:'2-digit',month:'short'})}</span>`).join('');
        $('revenueChart').innerHTML = `<div class="an-chart-grid"><span></span><span></span><span></span><span></span></div><svg class="an-chart-svg" viewBox="0 0 ${w} ${h}" preserveAspectRatio="none" role="img" aria-label="Grafik omzet dan laba"><polyline fill="none" stroke="#0f172a" stroke-width="4" stroke-linecap="round" stroke-linejoin="round" points="${line('rev')}"/><polyline fill="none" stroke="#16a34a" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" points="${line('prof')}"/></svg><div class="an-chart-axis">${labels}</div>`;
    }
    function renderFunnel(rows) {
        const done = rows.filter(completed), rev = done.reduce((s,o)=>s+revenue(o),0), payout = done.reduce((s,o)=>s+(n(settlement(o).final_income) > 0 ? n(settlement(o).final_income) : revenue(o) - fees(o)),0), max = Math.max(rev,1);
        const data = [['Order masuk', rows.length, rows.length],['Order selesai', done.length, done.length],['Omzet selesai', money(rev), rev],['Dana setelah biaya', money(Math.max(payout,0)), Math.max(payout,0)]];
        $('salesFunnel').innerHTML = data.map(([label,value,amount]) => `<div class="an-funnel-row"><span>${label}</span><div class="an-funnel-track"><span style="width:${Math.max(5,Math.round(amount / max * 100))}%"></span></div><strong class="an-funnel-value">${typeof value === 'number' ? value.toLocaleString('id-ID') : value}</strong></div>`).join('');
    }
    function renderStores(rows) {
        const map = {}; rows.forEach(o => { const id = String(o.store_id || o.store?.id || '0'); const s = map[id] ||= {name:o.store?.name || 'Tanpa toko',orders:0,done:0,cancel:0,rev:0,prof:0}; const cancelled = ['CANCELLED','BATAL','RETURNED'].includes(status(o)); s.orders++; if (!cancelled) s.rev += revenue(o); if (completed(o)) { s.done++; s.prof += profit(o); } if (cancelled) s.cancel++; });
        const list = Object.values(map).sort((a,b)=>b.rev-a.rev); $('storeBody').innerHTML = list.length ? list.map(s=>`<tr><td style="text-align:left;font-weight:850;color:#0f172a">${esc(s.name)}</td><td>${s.orders}</td><td>${s.done} <small style="color:#94a3b8">(${pct(s.done,s.orders)})</small></td><td style="color:${s.cancel?'#dc2626':'inherit'}">${s.cancel}</td><td style="font-weight:900">${money(s.rev)}</td><td style="font-weight:900;color:${s.prof>=0?'#15803d':'#dc2626'}">${money(s.prof)}</td></tr>`).join('') : '<tr><td colspan="6"><div class="an-empty">Belum ada data toko.</div></td></tr>';
    }
    function renderCosts(rows) {
        const keys = [['commission_fee','Komisi & administrasi'],['service_fee','Biaya layanan'],['transaction_fee','Biaya transaksi'],['affiliate_fee','Affiliate'],['activity_fee','Campaign / aktivitas'],['ad_cost','Iklan'],['seller_voucher','Voucher seller']];
        const data = keys.map(([key,label])=>({label,value:key === 'seller_voucher' ? rows.reduce((s,o)=>s+sellerVoucher(o),0) : rows.reduce((s,o)=>s+n(settlement(o)[key]),0)})).filter(x=>x.value>0).sort((a,b)=>b.value-a.value).slice(0,6); const total=data.reduce((s,x)=>s+x.value,0);
        $('costBody').innerHTML = data.length ? data.map(x=>`<div class="an-cost-row"><span>${x.label}</span><strong>${money(x.value)}</strong><div class="an-bar" style="grid-column:1/-1"><span style="width:${pct(x.value,total)}"></span></div></div>`).join('') : '<div class="an-empty">Belum ada data biaya settlement.</div>';
    }
    function products(rows) {
        const map = {};
        rows.filter(completed).forEach(o => {
            const storedItems = Array.isArray(o.items) ? o.items : [];
            const rawItems = Array.isArray(o.raw_json?.item_list) ? o.raw_json.item_list : [];
            const salesItems = rawItems.length ? rawItems : storedItems;
            salesItems.forEach(i => {
                const key=i.model_sku||i.item_sku||i.external_sku||i.item_name||'—';
                const p=map[key] ||= {sku:key,name:i.variant_name||i.model_name||i.item_name||'Produk',qty:0,rev:0,cost:0,missing:true};
                p.qty += n(i.model_quantity_purchased || i.quantity_purchased || i.qty || i.active_qty);
                p.rev += discountedLine(i);
            });
            storedItems.forEach(i => {
                const key=i.model_sku||i.item_sku||i.external_sku||i.item_name||'—';
                const p=map[key] ||= {sku:key,name:i.variant_name||i.item_name||'Produk',qty:0,rev:0,cost:0,missing:true};
                const cost=itemCost(i); p.cost += cost; p.missing ||= !cost;
            });
        });
        return Object.values(map).map(p=>({...p,profit:p.rev-p.cost}));
    }
    function renderProducts(rows) {
        const list=products(rows), best=[...list].sort((a,b)=>b.profit-a.profit).slice(0,7), worst=[...list].sort((a,b)=>a.profit-b.profit).slice(0,6); const max=Math.max(...best.map(p=>p.profit),1);
        $('bestProductBody').innerHTML=best.length?best.map((p,i)=>`<tr><td><span class="an-product"><span class="an-rank">${i+1}</span><span class="an-product-copy"><span class="an-product-name">${esc(p.name)}</span><span class="an-product-sku">${esc(p.sku)}</span></span></span></td><td>${p.qty}</td><td>${money(p.rev)}</td><td style="color:#15803d;font-weight:900">${money(p.profit)}</td><td>${pct(p.profit,p.rev)}</td></tr>`).join(''):'<tr><td colspan="5"><div class="an-empty">Belum ada item selesai.</div></td></tr>';
        $('worstProductBody').innerHTML=worst.length?worst.map(p=>`<div class="an-list-row"><div class="an-list-main"><div class="an-list-name"><span class="an-dot ${p.profit<0?'red':''}"></span> ${esc(p.name)}</div><div class="an-list-meta">${esc(p.sku)} · ${p.qty} pcs${p.missing?' · HPP belum lengkap':''}</div><div class="an-bar"><span style="width:${Math.max(4,Math.min(100,Math.round(Math.abs(p.profit)/max*100)))}%;background:${p.profit<0?'#ef4444':'#facc15'}"></span></div></div><div class="an-list-value" style="color:${p.profit<0?'#dc2626':'#a16207'}">${money(p.profit)}</div></div>`).join(''):'<div class="an-empty">Belum ada produk untuk ditinjau.</div>';
    }
    function render() { const rows=filtered(); renderKpis(rows); renderChart(rows); renderFunnel(rows); renderStores(rows); renderCosts(rows); renderProducts(rows); $('anSyncNote').textContent=`${rows.length.toLocaleString('id-ID')} order · data lokal tersinkron`; }
    const normalize = payload => Array.isArray(payload) ? payload : (payload.data || []);
    async function load() { setLoading('Mengambil order marketplace…'); $('anRefresh').disabled=true; try { const rangeUrl=`/api/marketplace/local-orders?date_from=${encodeURIComponent(from())}&date_to=${encodeURIComponent(to())}&limit=2000`; const todayUrl=`/api/marketplace/local-orders?date_from=${encodeURIComponent(today())}&date_to=${encodeURIComponent(today())}&limit=2000`; const [rangePayload,todayPayload]=await Promise.all([api(rangeUrl),api(todayUrl)]); orders=normalize(rangePayload); todayOrders=normalize(todayPayload); fillStores(); render(); } catch(e) { $('anSyncNote').textContent='Data gagal dimuat'; document.querySelectorAll('#storeBody,#bestProductBody').forEach(el=>el.innerHTML='<tr><td colspan="6"><div class="an-error">Tidak dapat memuat data analytics. Coba refresh atau periksa koneksi sinkronisasi marketplace.</div></td></tr>'); } finally { $('anRefresh').disabled=false; } }
    $('anRefresh').addEventListener('click',load); $('anStore').addEventListener('change',render);
    if (window.flatpickr) flatpickr($('anDateRange'),{mode:'range',dateFormat:'Y-m-d',defaultDate:[from(),to()],onChange(dates){if(dates.length===2){$('anDateFrom').value=dates[0].toISOString().slice(0,10);$('anDateTo').value=dates[1].toISOString().slice(0,10);$('anDateRange').value=from()+' — '+to();history.replaceState(null,'',location.pathname+'?date_from='+from()+'&date_to='+to());load();}}});
    load();
})();
</script>
@endpush
