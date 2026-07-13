@extends('layouts.app')
@section('title', 'Marketplace • Naikkan Produk')

@include('marketplace._shared')

@push('head')
<style>
    .bo-wrap{ max-width:1120px; margin-inline:auto; padding:.75rem .75rem 4rem; }
    .bo-top{ display:flex; justify-content:space-between; align-items:center; gap:.6rem; flex-wrap:wrap; margin-bottom:.85rem; }
    .bo-title{ font-weight:800; font-size:1.05rem; margin:0; }
    .bo-sub{ color:#64748b; font-size:.78rem; }
    .bo-card{ border:1px solid rgba(15,23,42,.08); border-radius:16px; background:#fff; padding:1rem 1.05rem; margin-bottom:1rem; }
    body[data-theme="dark"] .bo-card{ background:#0f172a; border-color:rgba(51,65,85,.6); }
    .bo-card h2{ font-size:.92rem; font-weight:800; margin:0 0 .1rem; display:flex; align-items:center; gap:.4rem; }
    .bo-card .hint{ color:#94a3b8; font-size:.72rem; margin-bottom:.7rem; }
    .bo-slots{ display:flex; gap:.5rem; flex-wrap:wrap; align-items:center; }
    .bo-slot{ width:44px; height:44px; border-radius:12px; border:2px dashed rgba(148,163,184,.4); display:flex; align-items:center; justify-content:center; font-size:1.1rem; color:#cbd5e1; }
    .bo-slot.filled{ border-style:solid; border-color:#ea580c; background:rgba(234,88,12,.08); color:#ea580c; }
    .bo-boosted{ display:flex; gap:.6rem; flex-wrap:wrap; margin-top:.8rem; }
    .bo-chip{ display:flex; align-items:center; gap:.5rem; border:1px solid rgba(15,23,42,.1); border-radius:12px; padding:.4rem .6rem; background:#f8fafc; max-width:280px; }
    body[data-theme="dark"] .bo-chip{ background:#1e293b; border-color:rgba(51,65,85,.6); }
    .bo-chip img{ width:34px; height:34px; border-radius:8px; object-fit:cover; background:#e2e8f0; }
    .bo-chip .nm{ font-size:.76rem; font-weight:700; line-height:1.15; max-width:170px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
    .bo-chip .rm{ font-size:.68rem; color:#ea580c; font-weight:700; }
    .bo-row{ display:flex; align-items:center; gap:.55rem; padding:.5rem 0; border-bottom:1px solid rgba(148,163,184,.14); }
    .bo-row:last-child{ border-bottom:0; }
    .bo-row img{ width:36px; height:36px; border-radius:8px; object-fit:cover; background:#e2e8f0; flex:none; }
    .bo-row .nm{ font-size:.8rem; font-weight:700; line-height:1.2; }
    .bo-row .meta{ font-size:.7rem; color:#94a3b8; }
    .bo-row .grow{ flex:1; min-width:0; }
    .bo-timepill{ display:inline-block; font-size:.72rem; font-weight:800; padding:.14rem .5rem; border-radius:999px; background:rgba(37,99,235,.1); color:#1d4ed8; margin:0 .25rem .25rem 0; }
    .bo-form{ display:flex; gap:.5rem; flex-wrap:wrap; align-items:center; margin-top:.7rem; padding-top:.7rem; border-top:1px dashed rgba(148,163,184,.3); }
    .bo-form input, .bo-form select{ font-size:.8rem; padding:.32rem .6rem; border-radius:9px; border:1px solid #e2e8f0; }
    .bo-form select{ min-width:230px; }
    .bo-times{ display:flex; gap:.35rem; flex-wrap:wrap; align-items:center; }
    .btn-bo{ border-radius:9px; font-size:.78rem; font-weight:700; padding:.32rem .8rem; }
    .btn-bo-primary{ background:#ea580c; border-color:#ea580c; color:#fff; }
    .btn-bo-primary:hover{ background:#c2410c; border-color:#c2410c; color:#fff; }
    .bo-mini{ font-size:.68rem; padding:.16rem .5rem; border-radius:7px; }
    .bo-muted{ color:#94a3b8; font-size:.78rem; padding:.6rem 0; }
    .bo-tag{ font-size:.64rem; font-weight:800; padding:.1rem .45rem; border-radius:6px; text-transform:uppercase; letter-spacing:.03em; }
    .tag-schedule{ background:rgba(37,99,235,.12); color:#1d4ed8; }
    .tag-pool{ background:rgba(22,163,74,.12); color:#15803d; }
    .tag-manual{ background:rgba(100,116,139,.14); color:#475569; }
    .bo-off{ opacity:.5; }
</style>
@endpush

@section('content')
<div class="bo-wrap">
    <div class="bo-top">
        <div>
            <h1 class="bo-title">🚀 Naikkan Produk</h1>
            <div class="bo-sub">Jadwalkan produk mana naik jam berapa, plus rotasi otomatis. Batas Shopee: maks 5 produk aktif, tiap boost 4 jam.</div>
        </div>
        <div style="display:flex; gap:.5rem; align-items:center; flex-wrap:wrap;">
            <select id="boostStore" class="form-select" style="border-radius:10px; font-size:.82rem; min-width:200px;"></select>
            <a href="{{ route('marketplace.products') }}" class="btn btn-outline-secondary btn-bo">← Produk</a>
        </div>
    </div>

    {{-- Status slot --}}
    <div class="bo-card">
        <h2>📊 Status Boost <span id="slotCount" style="font-weight:700;color:#ea580c;font-size:.8rem;"></span>
            <button class="btn btn-outline-secondary bo-mini" style="margin-left:auto" onclick="B.loadStatus()">⟳ Refresh</button>
        </h2>
        <div class="hint">Produk yang sedang tampil teratas di toko sekarang.</div>
        <div class="bo-slots" id="slotBar"></div>
        <div class="bo-boosted" id="boostedList"></div>
        <div class="bo-form">
            <select id="quickPick" class="prod-picker"></select>
            <button class="btn btn-bo btn-bo-primary" onclick="B.quickBoost()">🚀 Naikkan sekarang</button>
            <span class="bo-muted" style="padding:0">— naikkan manual satu produk langsung.</span>
        </div>
    </div>

    {{-- Jadwal jam tetap --}}
    <div class="bo-card">
        <h2>⏰ Jadwal Jam-Tetap</h2>
        <div class="hint">Produk dinaikkan otomatis tiap hari pada jam yang kamu tentukan. Kalau slot penuh saat jamnya tiba, sistem coba lagi begitu ada slot kosong.</div>
        <div id="scheduleList"></div>
        <div class="bo-form">
            <select id="schedPick" class="prod-picker"></select>
            <div class="bo-times" id="schedTimes">
                <input type="time" class="sched-time" value="08:00">
            </div>
            <button class="btn btn-outline-secondary bo-mini" onclick="B.addTimeField()">+ jam</button>
            <button class="btn btn-bo btn-bo-primary" onclick="B.saveSchedule()">Simpan jadwal</button>
        </div>
    </div>

    {{-- Antrian rotasi --}}
    <div class="bo-card">
        <h2>🔁 Antrian Rotasi Otomatis</h2>
        <div class="hint">Kumpulan produk yang digilir mengisi slot kosong tiap 4 jam — yang paling lama belum naik dapat giliran duluan. Cocok untuk “biar toko selalu segar 24 jam”.</div>
        <div id="poolList"></div>
        <div class="bo-form">
            <select id="poolPick" class="prod-picker"></select>
            <button class="btn btn-bo btn-bo-primary" onclick="B.addPool()">+ Tambah ke antrian</button>
        </div>
    </div>

    {{-- Riwayat --}}
    <div class="bo-card">
        <h2>🧾 Riwayat Boost</h2>
        <div class="hint">100 eksekusi terakhir (jadwal / rotasi / manual).</div>
        <div id="logList"></div>
    </div>
</div>
@endsection

@push('scripts')
<script>
window.B = (function () {
    const { api, esc } = window.mpHelpers;
    const $ = id => document.getElementById(id);
    const API = '/api/marketplace';
    let stores = [];
    let products = [];   // produk toko terpilih (untuk picker)

    const storeId = () => $('boostStore').value;

    function remainingLabel(mins) {
        if (mins == null || mins <= 0) return '';
        const h = Math.floor(mins / 60), m = mins % 60;
        return (h ? h + 'j ' : '') + m + 'm lagi';
    }
    function fmtTime(d) {
        if (!d) return '—';
        return new Date(d).toLocaleString('id-ID', { day:'2-digit', month:'short', hour:'2-digit', minute:'2-digit' });
    }

    // ── Init ────────────────────────────────────────────────────────────────
    async function init() {
        stores = await api(`${API}/stores`).catch(() => []);
        const shopee = stores.filter(s => (s.channel?.code || '').toLowerCase().includes('shp') || (s.channel?.code || '').toLowerCase() === 'shopee' || (s.channel?.name || '').toLowerCase().includes('shopee'));
        const list = shopee.length ? shopee : stores;
        const sel = $('boostStore');
        sel.innerHTML = list.map(s => `<option value="${s.id}">${esc(s.name)}</option>`).join('') || '<option value="">— tidak ada toko —</option>';
        sel.addEventListener('change', reloadAll);
        reloadAll();
    }

    async function reloadAll() {
        await loadProducts();
        loadStatus(); loadSchedules(); loadPool(); loadLogs();
    }

    async function loadProducts() {
        const sid = storeId();
        if (!sid) { products = []; fillPickers(); return; }
        try {
            products = await api(`${API}/products?store_id=${sid}`);
        } catch { products = []; }
        fillPickers();
    }

    function fillPickers() {
        const opts = '<option value="">— pilih produk —</option>' +
            products.filter(p => p.item_status === 'NORMAL')
                .map(p => `<option value="${p.id}">${esc((p.item_name || 'item ' + p.item_id).slice(0, 70))}${p.item_sku ? ' · ' + esc(p.item_sku) : ''}</option>`).join('');
        document.querySelectorAll('.prod-picker').forEach(el => el.innerHTML = opts);
    }

    // ── Status ──────────────────────────────────────────────────────────────
    async function loadStatus() {
        $('slotBar').innerHTML = '<span class="bo-muted" style="padding:0">Memuat…</span>';
        $('boostedList').innerHTML = '';
        let d;
        try { d = await api(`${API}/boost/status?store_id=${storeId()}`); }
        catch (e) { $('slotBar').innerHTML = `<span class="text-danger" style="font-size:.78rem">${esc(e.message)}</span>`; return; }

        if (d.error) { $('slotBar').innerHTML = `<span class="text-danger" style="font-size:.78rem">${esc(d.error)}</span>`; $('slotCount').textContent=''; return; }

        const used = d.used || (d.items || []).length, max = d.max || 5;
        $('slotCount').textContent = `${used}/${max} slot terpakai`;
        let bar = '';
        for (let i = 0; i < max; i++) bar += `<div class="bo-slot ${i < used ? 'filled' : ''}">${i < used ? '🚀' : ''}</div>`;
        $('slotBar').innerHTML = bar;

        $('boostedList').innerHTML = (d.items || []).length
            ? d.items.map(it => `
                <div class="bo-chip">
                    ${it.image_url ? `<img src="${esc(it.image_url)}" alt="">` : '<div class="bo-chip"></div>'}
                    <div><div class="nm" title="${esc(it.name)}">${esc(it.name)}</div>
                    <div class="rm">${remainingLabel(it.remaining_minutes) || 'aktif'}</div></div>
                </div>`).join('')
            : '<span class="bo-muted">Belum ada produk yang di-boost.</span>';
    }

    async function quickBoost() {
        const pid = parseInt($('quickPick').value);
        if (!pid) return alert('Pilih produk dulu.');
        try {
            const res = await api(`${API}/boost/now`, { method:'POST', body: JSON.stringify({ store_id: storeId(), product_ids: [pid] }) });
            toast(res.message, res.success ? 'success' : 'warning');
            loadStatus(); loadLogs();
        } catch (e) { toast('Gagal: ' + e.message, 'error'); }
    }

    // ── Jadwal ──────────────────────────────────────────────────────────────
    async function loadSchedules() {
        const c = $('scheduleList');
        c.innerHTML = '<div class="bo-muted">Memuat…</div>';
        let d;
        try { d = await api(`${API}/boost/schedules?store_id=${storeId()}`); }
        catch (e) { c.innerHTML = `<div class="text-danger" style="font-size:.78rem">${esc(e.message)}</div>`; return; }

        // Kelompokkan per produk supaya rapi
        const byProd = {};
        (d.schedules || []).forEach(s => { (byProd[s.product_id] ||= { name:s.product, sku:s.sku, image:s.image_url, rows:[] }).rows.push(s); });
        const keys = Object.keys(byProd);
        c.innerHTML = keys.length ? keys.map(k => {
            const g = byProd[k];
            const pills = g.rows.sort((a,b)=>a.time.localeCompare(b.time)).map(r =>
                `<span class="bo-timepill ${r.is_active ? '' : 'bo-off'}">${r.time}
                    <a href="javascript:B.toggleSchedule(${r.id})" title="aktif/nonaktif" style="text-decoration:none">${r.is_active ? '⏸' : '▶'}</a>
                    <a href="javascript:B.delSchedule(${r.id})" title="hapus" style="text-decoration:none;color:#dc2626">✕</a>
                </span>`).join('');
            return `<div class="bo-row">
                ${g.image ? `<img src="${esc(g.image)}">` : '<img>'}
                <div class="grow"><div class="nm">${esc(g.name || '—')}</div><div class="meta">${esc(g.sku || '')}</div></div>
                <div style="text-align:right">${pills}</div>
            </div>`;
        }).join('') : '<div class="bo-muted">Belum ada jadwal. Tambahkan di bawah.</div>';
    }

    function addTimeField() {
        const el = document.createElement('input');
        el.type = 'time'; el.className = 'sched-time'; el.value = '20:00';
        $('schedTimes').appendChild(el);
    }

    async function saveSchedule() {
        const pid = parseInt($('schedPick').value);
        if (!pid) return alert('Pilih produk dulu.');
        const times = [...document.querySelectorAll('#schedTimes .sched-time')].map(i => i.value).filter(Boolean);
        if (!times.length) return alert('Isi minimal satu jam.');
        try {
            const res = await api(`${API}/boost/schedules`, { method:'POST', body: JSON.stringify({ store_id: storeId(), marketplace_product_id: pid, times }) });
            toast(res.message);
            $('schedPick').value = '';
            $('schedTimes').innerHTML = '<input type="time" class="sched-time" value="08:00">';
            loadSchedules();
        } catch (e) { toast('Gagal: ' + e.message, 'error'); }
    }

    async function toggleSchedule(id) { await api(`${API}/boost/schedules/${id}/toggle`, { method:'POST' }); loadSchedules(); }
    async function delSchedule(id) { if (!confirm('Hapus slot jadwal ini?')) return; await api(`${API}/boost/schedules/${id}`, { method:'DELETE' }); loadSchedules(); }

    // ── Pool ────────────────────────────────────────────────────────────────
    async function loadPool() {
        const c = $('poolList');
        c.innerHTML = '<div class="bo-muted">Memuat…</div>';
        let d;
        try { d = await api(`${API}/boost/pool?store_id=${storeId()}`); }
        catch (e) { c.innerHTML = `<div class="text-danger" style="font-size:.78rem">${esc(e.message)}</div>`; return; }

        c.innerHTML = (d.pool || []).length ? d.pool.map(p => `
            <div class="bo-row ${p.is_active ? '' : 'bo-off'}">
                ${p.image_url ? `<img src="${esc(p.image_url)}">` : '<img>'}
                <div class="grow"><div class="nm">${esc(p.product || '—')}</div>
                    <div class="meta">${esc(p.sku || '')} · terakhir naik: ${p.last_boosted_at ? fmtTime(p.last_boosted_at) : 'belum pernah'}</div></div>
                <button class="btn btn-outline-secondary bo-mini" onclick="B.togglePool(${p.id})">${p.is_active ? '⏸ Jeda' : '▶ Aktif'}</button>
                <button class="btn btn-outline-danger bo-mini" onclick="B.delPool(${p.id})">✕</button>
            </div>`).join('') : '<div class="bo-muted">Antrian kosong. Tambahkan produk untuk dirotasi otomatis.</div>';
    }

    async function addPool() {
        const pid = parseInt($('poolPick').value);
        if (!pid) return alert('Pilih produk dulu.');
        try {
            const res = await api(`${API}/boost/pool`, { method:'POST', body: JSON.stringify({ store_id: storeId(), product_ids: [pid] }) });
            toast(res.message);
            $('poolPick').value = '';
            loadPool();
        } catch (e) { toast('Gagal: ' + e.message, 'error'); }
    }

    async function togglePool(id) { await api(`${API}/boost/pool/${id}/toggle`, { method:'POST' }); loadPool(); }
    async function delPool(id) { if (!confirm('Keluarkan produk dari antrian?')) return; await api(`${API}/boost/pool/${id}`, { method:'DELETE' }); loadPool(); }

    // ── Logs ────────────────────────────────────────────────────────────────
    async function loadLogs() {
        const c = $('logList');
        c.innerHTML = '<div class="bo-muted">Memuat…</div>';
        let d;
        try { d = await api(`${API}/boost/logs?store_id=${storeId()}`); }
        catch (e) { c.innerHTML = `<div class="text-danger" style="font-size:.78rem">${esc(e.message)}</div>`; return; }

        c.innerHTML = (d.logs || []).length ? d.logs.map(l => `
            <div class="bo-row">
                <span class="bo-tag tag-${l.source}">${l.source}</span>
                <div class="grow"><div class="nm">${esc(l.product)}</div>
                    <div class="meta">${l.success ? '' : '⚠ ' + esc(l.message || 'gagal') + ' · '}${fmtTime(l.boosted_at)}${l.expires_at ? ' → ' + fmtTime(l.expires_at) : ''}</div></div>
                <span style="font-size:1rem">${l.success ? '✅' : '❌'}</span>
            </div>`).join('') : '<div class="bo-muted">Belum ada riwayat.</div>';
    }

    function toast(title, icon = 'success') {
        if (window.Swal) Swal.fire({ toast:true, position:'top-end', icon, title, showConfirmButton:false, timer:2800 });
    }

    document.addEventListener('DOMContentLoaded', init);

    return { loadStatus, quickBoost, saveSchedule, addTimeField, toggleSchedule, delSchedule, addPool, togglePool, delPool };
})();
</script>
@endpush
