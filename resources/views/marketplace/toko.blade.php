@extends('layouts.app')
@section('title', 'Marketplace • Toko & Channel')

@include('marketplace._shared')

@push('head')
<style>
.store-card {
    background: #fff;
    border: 1.5px solid #e2e8f0;
    border-radius: 18px;
    padding: 1.25rem 1.4rem 1rem;
    transition: box-shadow .15s;
}
.store-card:hover { box-shadow: 0 4px 20px rgba(0,0,0,.07); }
.store-card-head { display: flex; align-items: center; gap: .6rem; margin-bottom: .5rem; }
.store-card-name { font-weight: 800; font-size: .97rem; color: #0f172a; }
.rename-btn { background:none;border:none;padding:0 .2rem;cursor:pointer;color:#94a3b8;font-size:.85rem;line-height:1; }
.rename-btn:hover { color:#0f172a; }
.rename-inline { display:none;align-items:center;gap:.4rem; }
.rename-inline input { font-size:.85rem;font-weight:700;border:1.5px solid #cbd5e1;border-radius:8px;padding:.2rem .5rem;width:160px; }
.rename-inline .btn-save-name { font-size:.7rem;font-weight:700;border-radius:7px;padding:.2rem .6rem; }
.store-card-meta { font-size: .72rem; color: #64748b; margin-bottom: .75rem; }
.store-stats { display: flex; gap: .4rem; flex-wrap: wrap; margin-bottom: .85rem; }
.store-stat {
    display: inline-flex; align-items: center; gap: .3rem;
    font-size: .72rem; font-weight: 700; padding: .22rem .7rem;
    border-radius: 999px; border: 1px solid #e2e8f0; background: #f8fafc; color: #475569;
}
.store-stat.warn { background: rgba(245,158,11,.08); border-color: rgba(245,158,11,.3); color: #b45309; }
.store-stat.err  { background: rgba(239,68,68,.08);  border-color: rgba(239,68,68,.25); color: #b91c1c; }
.store-stat.ok   { background: rgba(22,163,74,.07);  border-color: rgba(22,163,74,.25);  color: #166534; }
.store-actions { display: flex; gap: .5rem; flex-wrap: wrap; }
.store-actions .btn { font-size: .72rem; font-weight: 700; border-radius: 999px; padding: .3rem .85rem; }


/* Sync result modal */
.sync-row { display: flex; justify-content: space-between; align-items: center;
    padding: .45rem 0; border-bottom: 1px solid #f1f5f9; font-size: .85rem; }
.sync-row:last-child { border-bottom: none; }
.sync-row .label { color: #475569; }
.sync-row .val   { font-weight: 800; font-size: .95rem; }
.sync-row .val.ok  { color: #16a34a; }
.sync-row .val.err { color: #b91c1c; }
.sync-row .val.warn{ color: #b45309; }

.store-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(340px,1fr)); gap: 1rem; }
</style>
@endpush

@section('content')
<x-gf.page eyebrow="Marketplace" title="Toko & Channel" description="Kelola toko marketplace, sync order, dan pantau data bermasalah.">
    <x-slot:actions>
        <a href="{{ route('marketplace.shopee.connect') }}" class="btn btn-dark btn-sm"
            style="border-radius:999px;font-size:.78rem;font-weight:700;min-height:36px">
            + Login Shopee
        </a>
        <a href="{{ route('marketplace.tiktok.connect') }}" class="btn btn-sm"
            style="border-radius:999px;font-size:.78rem;font-weight:700;min-height:36px;background:#fe2c55;color:#fff;border:none">
            + Login TikTok Shop
        </a>
    </x-slot:actions>

    {{-- KPI --}}
    <div class="oc-kpi-grid">
        <div class="oc-kpi-card"><div class="oc-kpi-label">Channel</div><div class="oc-kpi-value" id="kpiChannels">—</div><div class="oc-kpi-note">aktif</div></div>
        <div class="oc-kpi-card"><div class="oc-kpi-label">Toko</div><div class="oc-kpi-value" id="kpiStores">—</div><div class="oc-kpi-note">terhubung</div></div>
        <div class="oc-kpi-card"><div class="oc-kpi-label">Token Valid</div><div class="oc-kpi-value" id="kpiTokenExp">—</div><div class="oc-kpi-note">toko aktif</div></div>
        <div class="oc-kpi-card"><div class="oc-kpi-label">Last Sync</div><div class="oc-kpi-value" id="kpiLastSync" style="font-size:.9rem">—</div><div class="oc-kpi-note">dari toko manapun</div></div>
    </div>

    <x-gf.panel title="Daftar Toko" subtitle="Sync order, pantau status, dan kelola data bermasalah per toko.">
        <x-slot:actions>
            <button type="button" class="btn btn-light border btn-sm"
                style="border-radius:999px;font-size:.75rem;font-weight:700"
                onclick="loadAll()">↻ Refresh</button>
            <button type="button" class="btn btn-light border btn-sm"
                style="border-radius:999px;font-size:.75rem;font-weight:700"
                onclick="bootstrap_()">Buat Channel Default</button>
        </x-slot:actions>
        <div id="storeBody"><div class="prod-tab-loading"><span class="prod-tab-spinner"></span> Memuat…</div></div>
    </x-gf.panel>

    {{-- Sync Log --}}
    <x-gf.panel title="Riwayat Sync" subtitle="10 sync terakhir dari semua toko.">
        <x-slot:actions>
            <button class="btn btn-light border btn-sm" style="border-radius:999px;font-size:.75rem;font-weight:700"
                onclick="loadSyncLogs()">↻ Refresh</button>
        </x-slot:actions>
        <div id="syncLogBody"><div class="prod-tab-loading"><span class="prod-tab-spinner"></span> Memuat…</div></div>
    </x-gf.panel>
</x-gf.page>

{{-- ── Sync Date Modal ────────────────────────────────────────────────────── --}}
<div class="modal fade" id="syncModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered" style="max-width:400px">
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
                <div class="mb-3">
                    <div class="form-check form-switch" title="Jalankan sync tanpa menyimpan ke database">
                        <input class="form-check-input" type="checkbox" role="switch" id="tokoSyncDryRun" style="cursor:pointer">
                        <label class="form-check-label fw-bold" for="tokoSyncDryRun" style="font-size:.75rem;color:#64748b;cursor:pointer">Mode Dry Run</label>
                    </div>
                </div>
                <div class="d-flex justify-content-end gap-2">
                    <button class="btn btn-light border" style="border-radius:999px" data-bs-dismiss="modal">Batal</button>
                    <button class="btn btn-dark" style="border-radius:999px" id="syncBtn" onclick="doSync()">↓ Sync</button>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ── Sync Result Summary Modal ──────────────────────────────────────────── --}}
<div class="modal fade" id="syncResultModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered" style="max-width:480px">
        <div class="modal-content" style="border-radius:20px">
            <div class="modal-header border-0 pb-0">
                <div>
                    <h5 class="modal-title fw-black">✅ Sync Selesai</h5>
                    <div class="text-muted" style="font-size:.8rem" id="syncResultSub">—</div>
                </div>
                <button class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="syncResultBody">
                {{-- diisi oleh JS --}}
            </div>
            <div class="modal-footer border-0 pt-0" id="syncResultFooter">
                {{-- diisi oleh JS --}}
            </div>
        </div>
    </div>
</div>

{{-- ── Shop Info Modal ────────────────────────────────────────────────────── --}}
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
    let channels = [], stores = [], warehouses = [], storeStats = {};
    let syncStoreId = null, syncStoreName = '';
    const $ = id => document.getElementById(id);

    // ── Format helpers ────────────────────────────────────────────────────
    const fmtRp = n => n > 0 ? n.toLocaleString('id') : '—';

    // ── Load everything ───────────────────────────────────────────────────
    async function loadAll() {
        $('storeBody').innerHTML = `<div class="prod-tab-loading"><span class="prod-tab-spinner"></span> Memuat…</div>`;
        const [cRes, sRes, wRes, statRes] = await Promise.allSettled([
            api('/api/marketplace/channels'),
            api('/api/marketplace/stores'),
            api('/api/marketplace/warehouses'),
            api('/api/marketplace/stores-summary'),
        ]);
        channels   = cRes.value   || [];
        stores     = sRes.value   || [];
        warehouses = wRes.value   || [];
        storeStats = statRes.value || {};
        renderKpi();
        renderStoreCards();
    }

    function renderKpi() {
        $('kpiChannels').textContent = channels.length;
        $('kpiStores').textContent   = stores.length;
        const valid = stores.filter(s => s.token_expires_at && new Date(s.token_expires_at) > new Date()).length;
        $('kpiTokenExp').textContent = valid;
        const last = stores.filter(s => s.last_synced_at).sort((a,b) => new Date(b.last_synced_at)-new Date(a.last_synced_at))[0];
        $('kpiLastSync').textContent = last?.last_synced_at ? fmtDate(last.last_synced_at) : '—';
    }

    function renderStoreCards() {
        const body = $('storeBody');
        if (!stores.length) {
            body.innerHTML = `<div class="oc-empty">Belum ada toko.<br><a href="{{ route('marketplace.shopee.connect') }}" class="btn btn-dark btn-sm mt-2" style="border-radius:999px">+ Login Shopee</a></div>`;
            return;
        }

        body.innerHTML = `<div class="store-grid">${stores.map(s => {
            const stats = storeStats[String(s.id)] || {};
            const issues    = stats.issues    || 0;
            const orders    = stats.orders_today || 0;
            const unfulfil  = stats.unfulfilled  || 0;
            const tokenOk   = s.token_expires_at && new Date(s.token_expires_at) > new Date();

            const statsPills = [
                `<span class="store-stat">${orders} order hari ini</span>`,
                unfulfil  > 0 ? `<span class="store-stat warn">⏳ ${unfulfil} belum fulfillment</span>` : '',
                issues    > 0 ? `<span class="store-stat err">⚠ ${issues} data bermasalah</span>`
                              : `<span class="store-stat ok">✓ Data valid</span>`,
            ].filter(Boolean).join('');

            const wh = warehouses.find(w => w.id == s.default_warehouse_id);
            const whLabel = wh ? `${esc(wh.code)} — ${esc(wh.name)}` : '— Pilih Gudang —';

            return `<div class="store-card">
                <div class="store-card-head">
                    ${channelPill(s.channel)}
                    <span class="store-card-name" id="store-name-${s.id}">${esc(s.name || '—')}</span>
                    <button class="rename-btn" title="Ganti nama toko" onclick="startRename(${s.id})">✎</button>
                    <span class="rename-inline" id="rename-inline-${s.id}">
                        <input type="text" id="rename-input-${s.id}" value="${esc(s.name || '')}">
                        <button class="btn btn-dark btn-save-name" onclick="saveRename(${s.id})">Simpan</button>
                        <button class="btn btn-light border btn-save-name" onclick="cancelRename(${s.id})">✕</button>
                    </span>
                    <span class="ms-auto">${statusBadge(tokenOk ? 'active' : 'inactive')}</span>
                </div>
                <div class="store-card-meta">
                    Shop ID: <code>${esc(s.external_shop_id || '—')}</code>
                    · Last sync: ${s.last_synced_at ? fmtDate(s.last_synced_at) : 'Belum pernah'}
                    · ${esc(s.region || 'ID')}
                </div>
                <div class="store-stats">${statsPills}</div>
                <div class="mb-3">
                    <label style="font-size:.68rem;font-weight:700;color:#94a3b8;display:block;margin-bottom:.25rem">GUDANG DEFAULT</label>
                    <select class="form-select form-select-sm" style="border-radius:10px;font-size:.75rem"
                        onchange="setWarehouse(${s.id}, this.value, this)">
                        <option value="">— Pilih Gudang —</option>
                        ${warehouses.map(w => `<option value="${w.id}" ${s.default_warehouse_id == w.id ? 'selected' : ''}>${esc(w.code)} — ${esc(w.name)}</option>`).join('')}
                    </select>
                </div>
                <div class="store-actions">
                    <button class="btn btn-dark" onclick="openSync(${s.id},'${esc(s.name)}')">↓ Sync Order</button>
                    <a href="/marketplace/orders" class="btn btn-light border">📋 Lihat Order</a>
                    ${issues > 0
                        ? `<a href="/marketplace/issues?store_id=${s.id}" class="btn btn-warning" style="font-weight:700">⚠ Perbaiki Data (${issues})</a>`
                        : `<a href="/marketplace/issues?store_id=${s.id}" class="btn btn-light border">🔍 Cek Data</a>`}
                    <a href="/marketplace/fulfillment" class="btn btn-light border">📦 Fulfillment</a>
                </div>
            </div>`;
        }).join('')}</div>`;
    }

    // ── Sync Log ──────────────────────────────────────────────────────────
    async function loadSyncLogs() {
        $('syncLogBody').innerHTML = `<div class="prod-tab-loading"><span class="prod-tab-spinner"></span> Memuat…</div>`;
        try {
            const logs = await api('/api/marketplace/sync-logs');
            if (!logs.length) {
                $('syncLogBody').innerHTML = `<div class="oc-empty">Belum ada riwayat sync.</div>`;
                return;
            }
            $('syncLogBody').innerHTML = `
            <div class="gf-table-scroll">
            <table class="gf-clean-table w-100">
                <thead><tr>
                    <th>Toko</th><th>Waktu</th><th>Status</th>
                    <th class="text-end">Baru</th><th class="text-end">Update</th>
                    <th class="text-end">SKU Kosong</th><th class="text-end">Belum Mapping</th>
                    <th class="text-end">HPP Kosong</th><th class="text-end">Siap Fulfillment</th>
                    <th class="text-end">Belum Lengkap</th><th>Keterangan</th>
                </tr></thead>
                <tbody>
                ${logs.filter(l => l.action === 'sync_orders').slice(0, 20).map(l => {
                    const p = l.payload || {};
                    const isOk = l.status === 'success';
                    return `<tr>
                        <td style="font-size:.78rem">${esc(l.store_name || '—')}</td>
                        <td style="font-size:.78rem;white-space:nowrap">${fmtDate(l.created_at)}</td>
                        <td>${isOk
                            ? `<span class="badge bg-success-subtle text-success" style="border-radius:999px">✓ Sukses</span>`
                            : `<span class="badge bg-danger-subtle text-danger" style="border-radius:999px">✗ Gagal</span>`}</td>
                        <td class="text-end" style="font-size:.8rem">${p.new ?? '—'}</td>
                        <td class="text-end" style="font-size:.8rem">${p.updated ?? '—'}</td>
                        <td class="text-end" style="font-size:.8rem;color:${(p.sku_empty||0)>0?'#b91c1c':'inherit'}">${p.sku_empty ?? '—'}</td>
                        <td class="text-end" style="font-size:.8rem;color:${(p.mapping_not_found||0)>0?'#b45309':'inherit'}">${p.mapping_not_found ?? '—'}</td>
                        <td class="text-end" style="font-size:.8rem;color:${(p.missing_hpp||0)>0?'#1d4ed8':'inherit'}">${p.missing_hpp ?? '—'}</td>
                        <td class="text-end" style="font-size:.8rem;color:${(p.ready||0)>0?'#166534':'inherit'}">${p.ready ?? '—'}</td>
                        <td class="text-end" style="font-size:.8rem;color:${(p.incomplete||0)>0?'#b45309':'inherit'}">${p.incomplete ?? '—'}</td>
                        <td style="font-size:.75rem;max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="${esc(l.message||'')}">
                            ${esc(l.message || '—')}
                        </td>
                    </tr>`;
                }).join('')}
                </tbody>
            </table></div>`;
        } catch (e) {
            $('syncLogBody').innerHTML = `<div class="oc-empty text-danger">Gagal memuat log: ${e.message}</div>`;
        }
    }

    // ── Sync flow ─────────────────────────────────────────────────────────
    window.openSync = function (id, name) {
        syncStoreId = id;
        syncStoreName = name;
        $('syncModalTitle').textContent = 'Sync Order — ' + name;
        $('syncAlert').className = 'alert d-none';
        const today = new Date().toISOString().slice(0,10);
        const week  = new Date(Date.now() - 6*864e5).toISOString().slice(0,10);
        $('syncFrom').value = week; $('syncTo').value = today;
        $('syncBtn').disabled = false; $('syncBtn').textContent = '↓ Sync';
        new bootstrap.Modal($('syncModal')).show();
    };

    window.doSync = async function () {
        if (!syncStoreId) return;
        const from = new Date($('syncFrom').value + 'T00:00:00');
        const to   = new Date($('syncTo').value   + 'T23:59:59');
        const btn  = $('syncBtn'), alertEl = $('syncAlert');
        btn.disabled = true; btn.textContent = '⏳ Syncing…';
        alertEl.className = 'alert alert-warning'; alertEl.textContent = 'Sedang mengambil order dari marketplace…';

        try {
            const d = await api('/api/marketplace/stores/' + syncStoreId + '/sync-orders', {
                method: 'POST',
                body: JSON.stringify({
                    time_from: Math.floor(from.getTime()/1000),
                    time_to:   Math.floor(to.getTime()/1000),
                    page_size: 50,
                    dry_run: $('tokoSyncDryRun').checked ? 1 : 0
                })
            });

            // Tutup sync modal, buka result modal
            bootstrap.Modal.getInstance($('syncModal')).hide();
            showSyncResult(d, syncStoreName);
            loadAll();
            loadSyncLogs();
        } catch (e) {
            alertEl.className = 'alert alert-danger'; alertEl.textContent = e.message;
            btn.disabled = false; btn.textContent = '↓ Coba Lagi';
        }
    };

    function showSyncResult(d, storeName) {
        $('syncResultSub').textContent = storeName + ' — ' + (d.message || '');

        const rows = [
            { label: '📦 Order ditemukan',       val: d.found            ?? 0,  style: '' },
            { label: '✅ Order baru',             val: d.new              ?? 0,  style: 'ok' },
            { label: '🔄 Order diperbarui',       val: d.updated          ?? 0,  style: '' },
            { label: '⊘ SKU marketplace kosong', val: d.sku_empty        ?? 0,  style: (d.sku_empty||0)>0 ? 'err' : '' },
            { label: '❓ SKU belum dimapping',    val: d.mapping_not_found ?? 0, style: (d.mapping_not_found||0)>0 ? 'warn' : '' },
            { label: '⚠ HPP belum diisi',        val: d.missing_hpp      ?? 0,  style: (d.missing_hpp||0)>0 ? 'warn' : '' },
            { label: '✓ Siap fulfillment',        val: d.ready            ?? 0,  style: (d.ready||0)>0 ? 'ok' : '' },
            { label: '⚠ Data belum lengkap',      val: d.incomplete       ?? 0,  style: (d.incomplete||0)>0 ? 'warn' : '' },
        ];

        $('syncResultBody').innerHTML = rows.map(r =>
            `<div class="sync-row">
                <span class="label">${r.label}</span>
                <span class="val ${r.style}">${r.val}</span>
            </div>`
        ).join('');

        const hasIssues = (d.sku_empty||0) + (d.mapping_not_found||0) + (d.missing_hpp||0) > 0;
        const storeQs = syncStoreId ? `?store_id=${syncStoreId}` : '';

        $('syncResultFooter').innerHTML = `
            <div class="d-flex flex-wrap gap-2 w-100">
                ${hasIssues
                    ? `<a href="/marketplace/issues${storeQs}" class="btn btn-warning fw-bold" style="border-radius:999px;font-size:.8rem" data-bs-dismiss="modal">
                        ⚠ Perbaiki Data Bermasalah (${d.total_issues||0})
                       </a>`
                    : ''}
                ${(d.ready||0) > 0
                    ? `<a href="/marketplace/fulfillment" class="btn btn-success fw-bold" style="border-radius:999px;font-size:.8rem" data-bs-dismiss="modal">
                        📦 Lanjut ke Fulfillment
                       </a>`
                    : ''}
                <a href="/marketplace/orders" class="btn btn-light border fw-bold" style="border-radius:999px;font-size:.8rem" data-bs-dismiss="modal">
                    📋 Lihat Semua Order
                </a>
                <button class="btn btn-light border fw-bold ms-auto" style="border-radius:999px;font-size:.8rem" data-bs-dismiss="modal">
                    Kembali ke Toko
                </button>
            </div>`;

        new bootstrap.Modal($('syncResultModal')).show();
    }

    // ── Store actions ─────────────────────────────────────────────────────
    window.bootstrap_ = async function () {
        await api('/api/marketplace/bootstrap', { method: 'POST' }).catch(e => alert(e.message));
        loadAll();
    };

    window.setWarehouse = async function (storeId, warehouseId, sel) {
        sel.disabled = true;
        try {
            await api('/api/marketplace/stores/' + storeId, {
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
            const d = await api('/api/marketplace/stores/' + id + '/shop-info');
            $('infoOutput').textContent = JSON.stringify(d, null, 2);
        } catch (e) { $('infoOutput').textContent = 'Error: ' + e.message; }
    };

    window.startRename = function (storeId) {
        document.getElementById('store-name-' + storeId).style.display = 'none';
        const row = document.getElementById('rename-inline-' + storeId);
        row.style.display = 'flex';
        document.getElementById('rename-input-' + storeId).focus();
    };

    window.cancelRename = function (storeId) {
        document.getElementById('rename-inline-' + storeId).style.display = 'none';
        document.getElementById('store-name-' + storeId).style.display = '';
    };

    window.saveRename = async function (storeId) {
        const input = document.getElementById('rename-input-' + storeId);
        const name = input.value.trim();
        if (!name) return;
        try {
            await api('/api/marketplace/stores/' + storeId, {
                method: 'PATCH',
                body: JSON.stringify({ name }),
            });
            document.getElementById('store-name-' + storeId).textContent = name;
            cancelRename(storeId);
        } catch (e) {
            alert('Gagal menyimpan nama: ' + e.message);
        }
    };

    window.loadAll = loadAll;
    window.loadSyncLogs = loadSyncLogs;
    loadAll();
    loadSyncLogs();
})();
</script>
@endpush
