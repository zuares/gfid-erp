@extends('layouts.app')
@section('title', 'Marketplace • Profit Order')

@include('marketplace._shared')

@section('content')
<x-gf.page eyebrow="Marketplace" title="Profit per Order" description="Omzet dikurangi HPP, fee marketplace, voucher, dan biaya iklan — profit bersih per order.">

    {{-- KPI --}}
    <div class="oc-kpi-grid">
        <div class="oc-kpi-card"><div class="oc-kpi-label">Total Omzet</div><div class="oc-kpi-value" id="kpiOmzet" style="font-size:.9rem">—</div><div class="oc-kpi-note">buyer_payment_amount</div></div>
        <div class="oc-kpi-card"><div class="oc-kpi-label">Dana Cair</div><div class="oc-kpi-value" id="kpiIncome" style="font-size:.9rem">—</div><div class="oc-kpi-note">final_income (setelah fee + voucher)</div></div>
        <div class="oc-kpi-card"><div class="oc-kpi-label">Total HPP</div><div class="oc-kpi-value" id="kpiHpp" style="font-size:.9rem">—</div><div class="oc-kpi-note">dari snapshot HPP aktif</div></div>
        <div class="oc-kpi-card"><div class="oc-kpi-label">Profit Bersih</div><div class="oc-kpi-value" id="kpiProfit" style="font-size:.9rem">—</div><div class="oc-kpi-note">income − HPP − iklan</div></div>
        <div class="oc-kpi-card"><div class="oc-kpi-label">Margin</div><div class="oc-kpi-value" id="kpiMargin">—</div><div class="oc-kpi-note">profit / omzet</div></div>
    </div>

    {{-- Filter --}}
    <x-gf.panel title="Filter" subtitle="Tampilkan profit per toko.">
        <div style="display:flex;gap:1rem;flex-wrap:wrap;align-items:flex-end">
            <div style="flex:1;min-width:180px">
                <label class="form-label fw-bold" style="font-size:.72rem;color:#64748b;text-transform:uppercase">TOKO</label>
                <select class="form-select" id="profitStoreId" style="border-radius:12px;font-size:.83rem" onchange="loadProfits()">
                    <option value="">Semua Toko</option>
                </select>
            </div>
            <div>
                <button class="btn btn-light border btn-sm" style="border-radius:999px;font-size:.78rem;font-weight:700" onclick="loadProfits()">↻ Refresh</button>
            </div>
        </div>
        <div id="profitHppWarning" class="alert alert-warning d-none mt-3" style="border-radius:12px;font-size:.83rem">
            ⚠️ Beberapa order tidak memiliki mapping SKU → Item, sehingga HPP-nya <strong>0</strong>. Lengkapi <a href="{{ route('marketplace.sku-mapping') }}">SKU Mapping</a> dan pastikan sudah ada <em>HPP Snapshot</em> aktif.
        </div>
    </x-gf.panel>

    {{-- Profit Table --}}
    <x-gf.panel title="Detail Profit per Order" subtitle="Klik kolom Iklan untuk edit biaya iklan per order.">
        <div id="profitBody"><div class="prod-tab-loading"><span class="prod-tab-spinner"></span> Memuat…</div></div>
    </x-gf.panel>

</x-gf.page>
@endsection

@push('scripts')
<script>
(function () {
    const { api, fmt, fmtDate, fmtRp, esc, channelPill, statusBadge } = window.mpHelpers;
    let rows = [], stores = [];
    const $ = id => document.getElementById(id);

    // ── Init ──────────────────────────────────────────────────────────────────
    async function init() {
        stores = await api('/api/marketplace/stores').catch(() => []);
        const sel = $('profitStoreId');
        stores.forEach(s => {
            const opt = document.createElement('option');
            opt.value = s.id;
            opt.textContent = s.name + ' (' + (s.channel?.name || '?') + ')';
            sel.appendChild(opt);
        });
        loadProfits();
    }

    // ── Load ──────────────────────────────────────────────────────────────────
    window.loadProfits = async function () {
        $('profitBody').innerHTML = '<div class="prod-tab-loading"><span class="prod-tab-spinner"></span> Memuat…</div>';
        const storeId = $('profitStoreId').value;
        const url = '/api/marketplace/order-profits' + (storeId ? '?store_id=' + storeId : '');
        rows = await api(url).catch(() => []);
        renderKpi();
        renderTable();
    };

    function renderKpi() {
        const omzet  = rows.reduce((s, r) => s + r.buyer_payment_amount, 0);
        const income = rows.reduce((s, r) => s + r.final_income, 0);
        const hpp    = rows.reduce((s, r) => s + r.hpp_total, 0);
        const profit = rows.reduce((s, r) => s + r.profit_net, 0);
        const margin = omzet > 0 ? (profit / omzet * 100).toFixed(1) : '—';
        const hasUnmapped = rows.some(r => !r.hpp_mapped);

        $('kpiOmzet').textContent  = fmtRp(omzet);
        $('kpiIncome').textContent = fmtRp(income);
        $('kpiHpp').textContent    = fmtRp(hpp);
        $('kpiProfit').textContent = fmtRp(profit);
        $('kpiMargin').textContent = omzet > 0 ? margin + '%' : '—';

        $('profitHppWarning').className = 'alert alert-warning mt-3' + (hasUnmapped ? '' : ' d-none');
    }

    function renderTable() {
        const body = $('profitBody');
        if (!rows.length) {
            body.innerHTML = '<div class="oc-empty">Belum ada data profit. Pastikan sudah ada settlement dan HPP aktif.</div>';
            return;
        }

        body.innerHTML = `
        <div class="gf-table-scroll">
        <table class="gf-clean-table w-100">
            <thead>
                <tr>
                    <th>Order</th>
                    <th>Toko</th>
                    <th class="text-end">Omzet</th>
                    <th class="text-end">Dana Cair</th>
                    <th class="text-end" title="HPP dari snapshot aktif">HPP</th>
                    <th class="text-end" title="Klik untuk edit biaya iklan">Iklan ✎</th>
                    <th class="text-end" style="color:#16a34a;font-weight:900">Profit</th>
                    <th class="text-end">Margin</th>
                    <th>Cair</th>
                </tr>
            </thead>
            <tbody>
            ${rows.map((r, idx) => {
                const profitColor = r.profit_net >= 0 ? '#16a34a' : '#b91c1c';
                const marginColor = (r.margin_pct ?? 0) >= 0 ? '#16a34a' : '#b91c1c';
                const hppLabel = r.hpp_mapped
                    ? fmtRp(r.hpp_total)
                    : `<span style="color:#b91c1c" title="SKU belum ter-mapping">${fmtRp(r.hpp_total)} ⚠</span>`;

                return `<tr>
                    <td>
                        <div class="fw-bold" style="font-size:.8rem">${esc(r.channel_order_id)}</div>
                        <div class="text-muted" style="font-size:.7rem">${r.order?.order_status
                            ? `<span class="oc-badge oc-badge-muted" style="font-size:.65rem">${esc(r.order.order_status)}</span>`
                            : ''}</div>
                    </td>
                    <td style="font-size:.8rem">${esc(r.store?.name || '—')}</td>
                    <td class="text-end" style="font-size:.8rem;font-weight:700">${fmtRp(r.buyer_payment_amount)}</td>
                    <td class="text-end" style="font-size:.8rem;color:#0369a1">${fmtRp(r.final_income)}</td>
                    <td class="text-end" style="font-size:.78rem;color:#b45309">${hppLabel}
                        ${r.hpp_total > 0 ? `<div style="font-size:.66rem;color:#94a3b8">HPP unit tersimpan</div>` : ''}
                    </td>
                    <td class="text-end" style="font-size:.78rem">
                        <span class="profit-ad-value" data-id="${r.id}" data-val="${r.ad_cost}"
                            style="cursor:pointer;text-decoration:underline dotted;color:#0369a1"
                            onclick="editAdCost(${idx})"
                            title="Klik untuk edit biaya iklan">${r.ad_cost > 0 ? '−' + fmtRp(r.ad_cost) : '<span class="text-muted">—</span>'}</span>
                    </td>
                    <td class="text-end fw-black" style="font-size:.88rem;color:${profitColor}">${fmtRp(r.profit_net)}</td>
                    <td class="text-end" style="font-size:.82rem;font-weight:700;color:${marginColor}">
                        ${r.margin_pct !== null ? r.margin_pct + '%' : '—'}
                    </td>
                    <td style="font-size:.75rem;color:var(--gf-muted);white-space:nowrap">
                        ${r.settlement_time ? fmtDate(r.settlement_time) : '<span class="oc-badge oc-badge-amber">Belum Cair</span>'}
                    </td>
                </tr>`;
            }).join('')}
            </tbody>
        </table></div>
        <div class="gf-table-foot"><span class="gf-table-foot-hint">${rows.length} order ditampilkan</span></div>`;
    }

    // ── Edit Ad Cost (inline) ─────────────────────────────────────────────────
    window.editAdCost = function (idx) {
        const r   = rows[idx];
        const cur = r.ad_cost || 0;
        const val = prompt(`Biaya iklan untuk order ${r.channel_order_id}:`, cur);
        if (val === null) return;
        const num = parseFloat(val);
        if (isNaN(num) || num < 0) { alert('Masukkan angka ≥ 0'); return; }

        api('/api/marketplace/settlements/' + r.id + '/ad-cost', {
            method: 'PATCH',
            body: JSON.stringify({ ad_cost: num }),
        }).then(res => {
            rows[idx].ad_cost    = res.ad_cost;
            rows[idx].profit_net = rows[idx].final_income - rows[idx].hpp_total - res.ad_cost;
            rows[idx].margin_pct = rows[idx].buyer_payment_amount > 0
                ? Math.round(rows[idx].profit_net / rows[idx].buyer_payment_amount * 1000) / 10
                : null;
            renderKpi();
            renderTable();
        }).catch(e => alert('Gagal simpan: ' + e.message));
    };

    init();
})();
</script>
@endpush
