@extends('layouts.app')
@section('title', 'Marketplace • Sync Order')

@include('marketplace._shared')

@section('content')
<x-gf.page eyebrow="Marketplace" title="Sync Order" description="Tarik order dari marketplace, simpan ke database beserta buyer, kurir, resi, dan total bayar.">

    {{-- KPI --}}
    <div class="oc-kpi-grid">
        <div class="oc-kpi-card"><div class="oc-kpi-label">Total Sync</div><div class="oc-kpi-value" id="kpiTotalSync">—</div><div class="oc-kpi-note">semua waktu</div></div>
        <div class="oc-kpi-card"><div class="oc-kpi-label">Berhasil</div><div class="oc-kpi-value" id="kpiSuccess">—</div><div class="oc-kpi-note">status success</div></div>
        <div class="oc-kpi-card"><div class="oc-kpi-label">Error</div><div class="oc-kpi-value" id="kpiError">—</div><div class="oc-kpi-note">status failed</div></div>
        <div class="oc-kpi-card"><div class="oc-kpi-label">Last Sync</div><div class="oc-kpi-value" id="kpiLastSync" style="font-size:.85rem">—</div><div class="oc-kpi-note">waktu terakhir</div></div>
    </div>

    {{-- Trigger Panel --}}
    <x-gf.panel title="Tarik Order Baru" subtitle="Pilih toko dan rentang tanggal, lalu jalankan sync.">
        <div style="display:flex;gap:1rem;flex-wrap:wrap;align-items:flex-end">
            <div style="flex:1;min-width:180px">
                <label class="form-label fw-bold" style="font-size:.72rem;color:#64748b;text-transform:uppercase">TOKO</label>
                <select class="form-select" id="syncStoreId" style="border-radius:12px;font-size:.83rem">
                    <option value="">— Memuat toko… —</option>
                </select>
            </div>
            <div style="flex:0 0 150px">
                <label class="form-label fw-bold" style="font-size:.72rem;color:#64748b;text-transform:uppercase">DARI</label>
                <input type="date" class="form-control" id="syncFrom" style="border-radius:12px;font-size:.83rem">
            </div>
            <div style="flex:0 0 150px">
                <label class="form-label fw-bold" style="font-size:.72rem;color:#64748b;text-transform:uppercase">SAMPAI</label>
                <input type="date" class="form-control" id="syncTo" style="border-radius:12px;font-size:.83rem">
            </div>
            <div>
                <button class="btn btn-dark fw-bold" id="runSyncBtn" style="border-radius:999px;min-width:130px" onclick="runSync()">
                    ↓ Jalankan Sync
                </button>
            </div>
        </div>
        <div id="syncResultAlert" class="alert d-none mt-3" style="border-radius:12px;font-size:.85rem"></div>

        {{-- Live progress --}}
        <div id="syncProgress" style="display:none;margin-top:1rem">
            <div style="background:#f1f5f9;border-radius:12px;padding:.85rem 1rem;font-size:.82rem;color:#334155">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span class="prod-tab-spinner"></span>
                    <span class="fw-bold" id="syncProgressLabel">Menghubungi Shopee…</span>
                </div>
                <div id="syncProgressDetail" class="text-muted" style="font-size:.78rem"></div>
            </div>
        </div>
    </x-gf.panel>

    {{-- Sync History --}}
    <x-gf.panel title="Riwayat Sync" subtitle="100 sync terakhir dari semua toko.">
        <x-slot:actions>
            <button type="button" class="btn btn-light border btn-sm"
                style="border-radius:999px;font-size:.75rem;font-weight:700"
                onclick="loadLogs()">↻ Refresh</button>
        </x-slot:actions>
        <div id="logsBody"><div class="prod-tab-loading"><span class="prod-tab-spinner"></span> Memuat…</div></div>
    </x-gf.panel>

</x-gf.page>
@endsection

@push('scripts')
<script>
(function () {
    const { api, fmt, esc, statusBadge } = window.mpHelpers;
    let logs = [], stores = [];

    const $ = id => document.getElementById(id);

    // ── Init ──────────────────────────────────────────────────────────────────
    async function init() {
        // Set default dates
        const today = new Date().toISOString().slice(0,10);
        const week  = new Date(Date.now() - 6*864e5).toISOString().slice(0,10);
        $('syncFrom').value = week;
        $('syncTo').value   = today;

        // Load stores
        stores = await api('/api/marketplace/stores').catch(() => []);
        const sel = $('syncStoreId');
        sel.innerHTML = '<option value="">— Pilih Toko —</option>'
            + stores.map(s => `<option value="${s.id}">${esc(s.name)} (${esc(s.channel?.name || '?')})</option>`).join('');

        // If only one store, auto-select
        if (stores.length === 1) sel.value = stores[0].id;

        loadLogs();
    }

    // ── Sync Logs ─────────────────────────────────────────────────────────────
    async function loadLogs() {
        logs = await api('/api/marketplace/sync-logs').catch(() => []);
        renderKpi();
        renderLogs();
    }

    function renderKpi() {
        $('kpiTotalSync').textContent = logs.length;
        $('kpiSuccess').textContent   = logs.filter(l => l.status === 'success').length;
        $('kpiError').textContent     = logs.filter(l => l.status === 'failed').length;
        const last = logs[0];
        $('kpiLastSync').textContent  = last?.created_at ? fmt(last.created_at) : '—';
    }

    function renderLogs() {
        const body = $('logsBody');
        if (!logs.length) { body.innerHTML = '<div class="oc-empty">Belum ada riwayat sync.</div>'; return; }

        body.innerHTML = `
        <div class="gf-table-scroll">
        <table class="gf-clean-table w-100">
            <thead><tr>
                <th>Waktu</th><th>Toko</th><th>Action</th><th>Status</th>
                <th class="text-center">Ditemukan</th><th class="text-center">Disync</th>
                <th>Pesan</th>
            </tr></thead>
            <tbody>
            ${logs.map(l => `<tr>
                <td style="font-size:.78rem;white-space:nowrap;color:var(--gf-muted)">${l.created_at ? fmt(l.created_at) : '—'}</td>
                <td style="font-size:.82rem">${esc(l.store?.name || '—')}</td>
                <td><code style="font-size:.75rem">${esc(l.action)}</code></td>
                <td>${syncStatusBadge(l.status)}</td>
                <td class="text-center" style="font-size:.82rem;font-weight:700">${l.found ?? '—'}</td>
                <td class="text-center" style="font-size:.82rem;font-weight:700">${l.synced ?? '—'}</td>
                <td style="font-size:.78rem;color:#475569;max-width:260px">${esc(l.message || '—')}</td>
            </tr>`).join('')}
            </tbody>
        </table></div>
        <div class="gf-table-foot"><span class="gf-table-foot-hint">${logs.length} log ditampilkan</span></div>`;
    }

    function syncStatusBadge(status) {
        const map = { success: 'oc-badge-green', failed: 'oc-badge-red', warning: 'oc-badge-amber' };
        const lbl = { success: 'Berhasil', failed: 'Error', warning: 'Peringatan' };
        return `<span class="oc-badge ${map[status] || 'oc-badge-muted'}">${lbl[status] || esc(status)}</span>`;
    }

    // ── Run Sync ──────────────────────────────────────────────────────────────
    window.runSync = async function () {
        const storeId = $('syncStoreId').value;
        if (!storeId) { alert('Pilih toko dulu.'); return; }

        const from = new Date($('syncFrom').value + 'T00:00:00');
        const to   = new Date($('syncTo').value   + 'T23:59:59');

        if (isNaN(from) || isNaN(to)) { alert('Isi rentang tanggal.'); return; }

        const btn     = $('runSyncBtn');
        const alertEl = $('syncResultAlert');
        const prog    = $('syncProgress');

        btn.disabled = true;
        btn.textContent = 'Syncing…';
        alertEl.className = 'alert d-none';
        prog.style.display = 'block';
        $('syncProgressLabel').textContent = 'Menghubungi API marketplace…';
        $('syncProgressDetail').textContent = `Rentang: ${$('syncFrom').value} s/d ${$('syncTo').value}`;

        try {
            const d = await api('/api/marketplace/stores/' + storeId + '/sync-orders', {
                method: 'POST',
                body: JSON.stringify({
                    time_from: Math.floor(from / 1000),
                    time_to:   Math.floor(to / 1000),
                    page_size: 50,
                }),
            });

            prog.style.display = 'none';
            alertEl.className = 'alert alert-success';
            alertEl.innerHTML = `
                <strong>✓ Sync selesai.</strong> ${esc(d.message)}<br>
                <small class="text-muted">Ditemukan: ${d.found} order &nbsp;·&nbsp; Disync: ${d.synced} order</small>`;
            btn.textContent = '✓ Selesai';
            loadLogs();
            setTimeout(() => { btn.disabled = false; btn.textContent = '↓ Jalankan Sync'; }, 3000);
        } catch (e) {
            prog.style.display = 'none';
            alertEl.className = 'alert alert-danger';
            alertEl.textContent = '✗ ' + e.message;
            btn.disabled = false;
            btn.textContent = '↓ Jalankan Sync';
            loadLogs(); // log error juga tetap disimpan
        }
    };

    window.loadLogs = loadLogs;
    init();
})();
</script>
@endpush
