@extends('layouts.app')
@section('title', 'Marketplace • Settlement')

@include('marketplace._shared')

@section('content')
<x-gf.page eyebrow="Marketplace" title="Payment & Settlement" description="Dana cair per order — breakdown fee, voucher, ongkir subsidi, dan net payout dari marketplace.">

    {{-- KPI --}}
    <div class="oc-kpi-grid">
        <div class="oc-kpi-card"><div class="oc-kpi-label">Dana Cair</div><div class="oc-kpi-value" id="kpiNetPayout" style="font-size:.9rem">—</div><div class="oc-kpi-note">total final_income</div></div>
        <div class="oc-kpi-card"><div class="oc-kpi-label">Bayar Customer</div><div class="oc-kpi-value" id="kpiBuyerTotal" style="font-size:.9rem">—</div><div class="oc-kpi-note">gross revenue</div></div>
        <div class="oc-kpi-card"><div class="oc-kpi-label">Total Fee</div><div class="oc-kpi-value" id="kpiFeeTotal" style="font-size:.9rem">—</div><div class="oc-kpi-note">komisi + layanan + campaign</div></div>
        <div class="oc-kpi-card"><div class="oc-kpi-label">Tersettlement</div><div class="oc-kpi-value" id="kpiCount">—</div><div class="oc-kpi-note">order dengan data settlement</div></div>
    </div>

    {{-- Filter + Sync --}}
    <x-gf.panel title="Sync Settlement" subtitle="Tarik data escrow / dana cair dari API marketplace per order.">
        <div style="display:flex;gap:1rem;flex-wrap:wrap;align-items:flex-end">
            <div style="flex:1;min-width:180px">
                <label class="form-label fw-bold" style="font-size:.72rem;color:#64748b;text-transform:uppercase">TOKO</label>
                <select class="form-select" id="settlementStoreId" style="border-radius:12px;font-size:.83rem" onchange="filterByStore()">
                    <option value="">Semua Toko</option>
                </select>
            </div>
            <div>
                <button class="btn btn-dark fw-bold" id="runSettlementBtn" style="border-radius:999px;min-width:160px" onclick="runSettlementSync()">
                    ↓ Sync Settlement
                </button>
            </div>
            <div>
                <button class="btn btn-light border btn-sm" style="border-radius:999px;font-size:.78rem;font-weight:700" onclick="loadSettlements()">↻ Refresh</button>
            </div>
        </div>
        <div id="settlementSyncAlert" class="alert d-none mt-3" style="border-radius:12px;font-size:.85rem"></div>
    </x-gf.panel>

    {{-- Settlement Table --}}
    <x-gf.panel title="Detail Settlement Per Order" subtitle="Breakdown pembayaran, fee, dan dana cair.">
        <div id="settlementBody"><div class="prod-tab-loading"><span class="prod-tab-spinner"></span> Memuat…</div></div>
    </x-gf.panel>

</x-gf.page>
@endsection

@push('scripts')
<script>
(function () {
    const { api, fmt, fmtDate, fmtRp, esc, channelPill, statusBadge } = window.mpHelpers;
    let settlements = [], stores = [];
    const $ = id => document.getElementById(id);

    // ── Init ──────────────────────────────────────────────────────────────────
    async function init() {
        stores = await api('/api/marketplace/stores').catch(() => []);
        const sel = $('settlementStoreId');
        stores.forEach(s => {
            const opt = document.createElement('option');
            opt.value = s.id;
            opt.textContent = s.name + ' (' + (s.channel?.name || '?') + ')';
            sel.appendChild(opt);
        });
        loadSettlements();
    }

    // ── Load & Render ─────────────────────────────────────────────────────────
    async function loadSettlements() {
        $('settlementBody').innerHTML = '<div class="prod-tab-loading"><span class="prod-tab-spinner"></span> Memuat…</div>';
        const storeId = $('settlementStoreId').value;
        const url = '/api/marketplace/settlements' + (storeId ? '?store_id=' + storeId : '');
        settlements = await api(url).catch(() => []);
        renderKpi();
        renderTable();
    }

    window.filterByStore = function () { loadSettlements(); };

    function renderKpi() {
        const net      = settlements.reduce((s, r) => s + r.final_income, 0);
        const gross    = settlements.reduce((s, r) => s + r.buyer_payment_amount, 0);
        const fees     = settlements.reduce((s, r) => s + r.commission_fee + r.service_fee + r.transaction_fee + r.activity_fee + r.escrow_tax, 0);
        $('kpiNetPayout').textContent = fmtRp(net);
        $('kpiBuyerTotal').textContent = fmtRp(gross);
        $('kpiFeeTotal').textContent   = fmtRp(fees);
        $('kpiCount').textContent      = settlements.length;
    }

    function renderTable() {
        const body = $('settlementBody');
        if (!settlements.length) {
            body.innerHTML = '<div class="oc-empty">Belum ada data settlement. Klik "Sync Settlement" untuk tarik data dari marketplace.</div>';
            return;
        }

        body.innerHTML = `
        <div class="gf-table-scroll">
        <table class="gf-clean-table w-100">
            <thead>
                <tr>
                    <th>Order</th>
                    <th>Toko</th>
                    <th class="text-end">Bayar Customer</th>
                    <th class="text-end" title="Komisi + Layanan + Campaign">Fee</th>
                    <th class="text-end" title="Voucher seller + Koin">Voucher</th>
                    <th class="text-end" title="Subsidi ongkir platform">Subsidi</th>
                    <th class="text-end">Potongan Lain</th>
                    <th class="text-end" style="color:#16a34a;font-weight:900">Dana Cair</th>
                    <th>Cair</th>
                </tr>
            </thead>
            <tbody>
            ${settlements.map(s => {
                const fee      = s.commission_fee + s.service_fee + s.transaction_fee + s.activity_fee + s.escrow_tax;
                const voucher  = s.seller_voucher + s.seller_coin_cash_back;
                const subsidi  = s.shipping_fee_subsidy;
                const lainnya  = s.drc_adjustable_refund + s.reverse_shipping_fee;

                return `<tr>
                    <td>
                        <div class="fw-bold" style="font-size:.8rem">${esc(s.channel_order_id)}</div>
                        <div class="text-muted" style="font-size:.7rem">${s.order?.order_status ? `<span class="oc-badge oc-badge-muted" style="font-size:.65rem">${esc(s.order.order_status)}</span>` : ''}</div>
                    </td>
                    <td style="font-size:.8rem">${esc(s.store?.name || '—')}</td>
                    <td class="text-end" style="font-size:.8rem;font-weight:700">${fmtRp(s.buyer_payment_amount)}</td>
                    <td class="text-end" style="font-size:.78rem;color:#b91c1c">
                        ${fee ? '−' + fmtRp(fee) : '<span class="text-muted">—</span>'}
                        ${fee ? `<div style="font-size:.68rem;color:#94a3b8">komisi ${fmtRp(s.commission_fee)}</div>` : ''}
                    </td>
                    <td class="text-end" style="font-size:.78rem;color:#b45309">
                        ${voucher ? '−' + fmtRp(voucher) : '<span class="text-muted">—</span>'}
                    </td>
                    <td class="text-end" style="font-size:.78rem;color:#0369a1">
                        ${subsidi ? '+' + fmtRp(subsidi) : '<span class="text-muted">—</span>'}
                    </td>
                    <td class="text-end" style="font-size:.78rem;color:#b91c1c">
                        ${lainnya ? '−' + fmtRp(lainnya) : '<span class="text-muted">—</span>'}
                    </td>
                    <td class="text-end fw-black" style="font-size:.85rem;color:#16a34a">${fmtRp(s.final_income)}</td>
                    <td style="font-size:.75rem;color:var(--gf-muted);white-space:nowrap">
                        ${s.settlement_time ? fmtDate(s.settlement_time) : '<span class="oc-badge oc-badge-amber">Belum Cair</span>'}
                    </td>
                </tr>`;
            }).join('')}
            </tbody>
        </table></div>
        <div class="gf-table-foot"><span class="gf-table-foot-hint">${settlements.length} settlement ditampilkan</span></div>`;
    }

    // ── Sync Settlement ───────────────────────────────────────────────────────
    window.runSettlementSync = async function () {
        const storeId = $('settlementStoreId').value;
        if (!storeId) { alert('Pilih toko dulu sebelum sync settlement.'); return; }

        const btn     = $('runSettlementBtn');
        const alertEl = $('settlementSyncAlert');
        btn.disabled  = true;
        btn.textContent = 'Syncing…';
        alertEl.className = 'alert d-none';

        try {
            const d = await api('/api/marketplace/stores/' + storeId + '/sync-settlements', { method: 'POST' });
            alertEl.className = 'alert alert-success';
            alertEl.innerHTML = `<strong>✓ Settlement sync selesai.</strong><br>
                <small>Synced: <strong>${d.synced}</strong> &nbsp;·&nbsp;
                Skipped: ${d.skipped} &nbsp;·&nbsp;
                Errors: ${d.errors}</small>`;
            btn.textContent = '✓ Selesai';
            loadSettlements();
            setTimeout(() => { btn.disabled = false; btn.textContent = '↓ Sync Settlement'; }, 3000);
        } catch (e) {
            alertEl.className = 'alert alert-danger';
            alertEl.textContent = '✗ ' + e.message;
            btn.disabled = false;
            btn.textContent = '↓ Sync Settlement';
        }
    };

    window.loadSettlements = loadSettlements;
    init();
})();
</script>
@endpush
