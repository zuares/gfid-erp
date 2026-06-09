@extends('layouts.app')
@section('title', 'Marketplace • Toko & Channel')

@include('owner.marketplace._shared')

@section('content')
<x-gf.page eyebrow="Marketplace" title="Toko & Channel" description="Kelola toko marketplace yang terhubung dan set gudang default.">
    <x-slot:actions>
        <a href="{{ route('owner.omnichannel.shopee.connect') }}" class="btn btn-dark btn-sm"
            style="border-radius:999px;font-size:.78rem;font-weight:700;min-height:36px">
            + Login Shopee
        </a>
    </x-slot:actions>

    {{-- KPI --}}
    <div class="oc-kpi-grid">
        <div class="oc-kpi-card"><div class="oc-kpi-label">Channel</div><div class="oc-kpi-value" id="kpiChannels">—</div><div class="oc-kpi-note">aktif</div></div>
        <div class="oc-kpi-card"><div class="oc-kpi-label">Toko</div><div class="oc-kpi-value" id="kpiStores">—</div><div class="oc-kpi-note">terhubung</div></div>
        <div class="oc-kpi-card"><div class="oc-kpi-label">Token Valid</div><div class="oc-kpi-value" id="kpiTokenExp">—</div><div class="oc-kpi-note">toko aktif</div></div>
        <div class="oc-kpi-card"><div class="oc-kpi-label">Last Sync</div><div class="oc-kpi-value" id="kpiLastSync" style="font-size:.9rem">—</div><div class="oc-kpi-note">dari toko manapun</div></div>
    </div>

    <x-gf.panel title="Daftar Toko" subtitle="Pilih gudang default per toko, lalu sync order.">
        <x-slot:actions>
            <button type="button" class="btn btn-light border btn-sm"
                style="border-radius:999px;font-size:.75rem;font-weight:700"
                onclick="loadStores()">↻ Refresh</button>
            <button type="button" class="btn btn-light border btn-sm"
                style="border-radius:999px;font-size:.75rem;font-weight:700"
                onclick="bootstrap_()">Buat Channel Default</button>
        </x-slot:actions>
        <div id="storeBody"><div class="prod-tab-loading"><span class="prod-tab-spinner"></span> Memuat…</div></div>
    </x-gf.panel>
</x-gf.page>

{{-- Sync Modal --}}
<div class="modal fade" id="syncModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:20px">
            <div class="modal-header border-0 pb-0">
                <div>
                    <h5 class="modal-title fw-black" id="syncModalTitle">Sync Order</h5>
                    <div class="text-muted" style="font-size:.8rem" id="syncModalSub">Pilih rentang tanggal</div>
                </div>
                <button class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="syncAlert" class="alert d-none mb-3" style="border-radius:12px;font-size:.85rem"></div>
                <div class="d-flex gap-3 mb-3">
                    <div class="flex-fill">
                        <label class="form-label fw-bold" style="font-size:.75rem;color:#64748b">DARI</label>
                        <input type="date" class="form-control" id="syncFrom" style="border-radius:12px">
                    </div>
                    <div class="flex-fill">
                        <label class="form-label fw-bold" style="font-size:.75rem;color:#64748b">SAMPAI</label>
                        <input type="date" class="form-control" id="syncTo" style="border-radius:12px">
                    </div>
                </div>
                <div class="d-flex justify-content-end gap-2">
                    <button class="btn btn-light border" style="border-radius:999px" data-bs-dismiss="modal">Batal</button>
                    <button class="btn btn-dark" style="border-radius:999px" id="syncBtn" onclick="doSync()">Sync</button>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Shop Info Modal --}}
<div class="modal fade" id="infoModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content" style="border-radius:20px">
            <div class="modal-header border-0">
                <h5 class="modal-title fw-black" id="infoModalTitle">Info Toko</h5>
                <button class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <pre class="bg-light rounded p-3 small mb-0" id="infoOutput" style="max-height:70vh;overflow:auto"></pre>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const { api, fmt, fmtDate, esc, channelPill, statusBadge } = window.mpHelpers;
    let channels = [], stores = [], warehouses = [];
    let syncStoreId = null;

    const $ = id => document.getElementById(id);

    async function loadStores() {
        const [cRes, sRes, wRes] = await Promise.allSettled([
            api('/api/omnichannel/channels'),
            api('/api/omnichannel/stores'),
            api('/api/omnichannel/warehouses'),
        ]);
        channels   = cRes.value || [];
        stores     = sRes.value || [];
        warehouses = wRes.value || [];
        renderKpi();
        renderStores();
    }

    function renderKpi() {
        $('kpiChannels').textContent = channels.length;
        $('kpiStores').textContent   = stores.length;
        const valid = stores.filter(s => s.token_expires_at && new Date(s.token_expires_at) > new Date()).length;
        $('kpiTokenExp').textContent = valid;
        const last = stores.filter(s => s.last_synced_at).sort((a,b) => new Date(b.last_synced_at)-new Date(a.last_synced_at))[0];
        $('kpiLastSync').textContent = last?.last_synced_at ? fmtDate(last.last_synced_at) : '—';
    }

    function renderStores() {
        const body = $('storeBody');
        if (!stores.length) {
            body.innerHTML = `<div class="oc-empty">Belum ada toko. <a href="{{ route('owner.omnichannel.shopee.connect') }}" class="btn btn-dark btn-sm mt-2" style="border-radius:999px">+ Login Shopee</a></div>`;
            return;
        }
        body.innerHTML = `
        <div class="gf-table-scroll">
        <table class="gf-clean-table w-100">
            <thead><tr>
                <th>Toko</th><th>Channel</th><th>Shop ID</th>
                <th>Gudang Default</th><th>Last Sync</th><th>Status</th>
                <th class="text-end">Aksi</th>
            </tr></thead>
            <tbody>
            ${stores.map(s => `<tr>
                <td><span class="fw-bold">${esc(s.name||'—')}</span><br>
                    <span class="text-muted" style="font-size:.72rem">${esc(s.region||'ID')}</span></td>
                <td>${channelPill(s.channel)}</td>
                <td><code style="font-size:.78rem">${esc(s.external_shop_id||'—')}</code></td>
                <td>
                    <select class="form-select form-select-sm" style="border-radius:10px;font-size:.75rem;min-width:160px"
                        onchange="setWarehouse(${s.id}, this.value, this)">
                        <option value="">— Pilih Gudang —</option>
                        ${warehouses.map(w => `<option value="${w.id}" ${s.default_warehouse_id == w.id ? 'selected' : ''}>${esc(w.code)} — ${esc(w.name)}</option>`).join('')}
                    </select>
                </td>
                <td style="font-size:.78rem;color:var(--gf-muted)">${s.last_synced_at ? fmt(s.last_synced_at) : '—'}</td>
                <td>${statusBadge(s.status)}</td>
                <td class="text-end">
                    <div class="d-flex gap-2 justify-content-end">
                        <button class="btn btn-light border btn-sm" style="border-radius:999px;font-size:.73rem;font-weight:700"
                            onclick="checkStore(${s.id},'${esc(s.name)}')">Cek</button>
                        <button class="btn btn-dark btn-sm" style="border-radius:999px;font-size:.73rem;font-weight:700"
                            onclick="openSync(${s.id},'${esc(s.name)}')">↓ Sync</button>
                    </div>
                </td>
            </tr>`).join('')}
            </tbody>
        </table></div>`;
    }

    window.bootstrap_ = async function () {
        await api('/api/omnichannel/bootstrap', { method: 'POST' }).catch(e => alert(e.message));
        loadStores();
    };

    window.setWarehouse = async function (storeId, warehouseId, sel) {
        sel.disabled = true;
        try {
            await api('/api/omnichannel/stores/' + storeId, {
                method: 'PATCH', body: JSON.stringify({ default_warehouse_id: warehouseId || null }),
            });
            sel.style.borderColor = '#16a34a';
            setTimeout(() => { sel.style.borderColor = ''; }, 1500);
        } catch (e) { alert('Gagal: ' + e.message); }
        finally { sel.disabled = false; }
    };

    window.checkStore = async function (id, name) {
        $('infoModalTitle').textContent = 'Info Toko — ' + name;
        $('infoOutput').textContent = 'Memuat…';
        new bootstrap.Modal($('infoModal')).show();
        try {
            const d = await api('/api/omnichannel/stores/' + id + '/shop-info');
            $('infoOutput').textContent = JSON.stringify(d, null, 2);
        } catch (e) { $('infoOutput').textContent = 'Error: ' + e.message; }
    };

    window.openSync = function (id, name) {
        syncStoreId = id;
        $('syncModalTitle').textContent = 'Sync Order — ' + name;
        $('syncAlert').className = 'alert d-none';
        const today = new Date().toISOString().slice(0,10);
        const week  = new Date(Date.now() - 6*864e5).toISOString().slice(0,10);
        $('syncFrom').value = week; $('syncTo').value = today;
        $('syncBtn').disabled = false; $('syncBtn').textContent = 'Sync';
        new bootstrap.Modal($('syncModal')).show();
    };

    window.doSync = async function () {
        if (!syncStoreId) return;
        const from = new Date($('syncFrom').value + 'T00:00:00');
        const to   = new Date($('syncTo').value   + 'T23:59:59');
        const btn  = $('syncBtn'), alertEl = $('syncAlert');
        btn.disabled = true; btn.textContent = 'Syncing…';
        alertEl.className = 'alert alert-warning'; alertEl.textContent = 'Sedang sync…';
        try {
            const d = await api('/api/omnichannel/stores/' + syncStoreId + '/sync-orders', {
                method: 'POST',
                body: JSON.stringify({ time_from: Math.floor(from/1000), time_to: Math.floor(to/1000), page_size: 50 }),
            });
            alertEl.className = 'alert alert-success'; alertEl.textContent = d.message;
            btn.textContent = '✓ Selesai';
            loadStores();
        } catch (e) {
            alertEl.className = 'alert alert-danger'; alertEl.textContent = e.message;
            btn.disabled = false; btn.textContent = 'Coba Lagi';
        }
    };

    window.loadStores = loadStores;
    loadStores();
})();
</script>
@endpush
