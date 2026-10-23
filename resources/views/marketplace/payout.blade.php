@extends('layouts.app')
@section('title', 'Marketplace • Payout')

@include('marketplace._shared')

@push('head')
<style>
    .payout-hero{display:flex;justify-content:space-between;gap:1rem;align-items:flex-start;flex-wrap:wrap;margin-bottom:.85rem;padding:1rem 1.1rem;border-radius:18px;background:linear-gradient(135deg,#172554,#075985);color:#fff;box-shadow:0 14px 34px rgba(15,23,42,.15)}
    .payout-hero h1{font-size:1.18rem;letter-spacing:-.03em;margin:0 0 .28rem;font-weight:850}.payout-hero p{color:#dbeafe;margin:0;font-size:.78rem;max-width:44rem}
    .payout-live{display:inline-flex;align-items:center;gap:.35rem;border:1px solid rgba(255,255,255,.2);border-radius:999px;padding:.35rem .62rem;font-size:.7rem;font-weight:800;color:#dcfce7;background:rgba(34,197,94,.12);white-space:nowrap}.payout-live i{width:7px;height:7px;border-radius:50%;background:#4ade80;box-shadow:0 0 0 4px rgba(74,222,128,.16)}
    .payout-panel,.payout-kpi,.payout-table-wrap{border:1px solid var(--shp-border);border-radius:14px;background:var(--card)}.payout-panel{padding:.85rem;margin-bottom:.8rem}.payout-filters{display:grid;grid-template-columns:minmax(180px,1.15fr) repeat(2,minmax(145px,.7fr)) auto;gap:.65rem;align-items:end}.payout-field label{display:block;font-size:.67rem;font-weight:850;text-transform:uppercase;letter-spacing:.04em;color:var(--shp-muted);margin-bottom:.25rem}.payout-field .form-control,.payout-field .form-select{font-size:.8rem;border-radius:8px}.payout-filter-actions{display:flex;gap:.45rem;align-items:center}.payout-help{color:var(--shp-muted);font-size:.72rem;margin-top:.7rem}
    .payout-kpis{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:.65rem;margin-bottom:.8rem}.payout-kpi{padding:.75rem .85rem}.payout-kpi-label{color:var(--shp-muted);font-size:.65rem;text-transform:uppercase;letter-spacing:.05em;font-weight:850}.payout-kpi-value{font-size:1.05rem;line-height:1.2;font-weight:900;color:var(--shp-text);margin-top:.2rem}.payout-kpi-note{font-size:.68rem;color:var(--shp-muted);margin-top:.15rem}
    .payout-table-wrap{overflow:auto}.payout-table{margin:0;min-width:1050px}.payout-table th{font-size:.67rem;text-transform:uppercase;letter-spacing:.04em;color:var(--shp-muted);white-space:nowrap;background:rgba(148,163,184,.06);padding:.65rem .75rem}.payout-table td{font-size:.76rem;vertical-align:middle;padding:.62rem .75rem;border-top-color:var(--shp-border);white-space:nowrap}.payout-muted{color:var(--shp-muted)}.payout-money{font-weight:850;color:#047857;white-space:nowrap}.payout-code{font-weight:800;color:var(--shp-text);font-family:ui-monospace,SFMono-Regular,Menlo,monospace;font-size:.72rem}.payout-empty{text-align:center;padding:2rem 1rem;color:var(--shp-muted);font-size:.82rem}.payout-alert{display:none;border-radius:10px;padding:.7rem .8rem;font-size:.78rem;margin-bottom:.8rem}.payout-alert.show{display:block}.payout-alert.error{background:#fef2f2;border:1px solid #fecaca;color:#991b1b}.payout-alert.success{background:#ecfdf5;border:1px solid #a7f3d0;color:#065f46}.payout-spinner{display:inline-block;width:14px;height:14px;border:2px solid currentColor;border-right-color:transparent;border-radius:50%;animation:payout-spin .65s linear infinite;vertical-align:-2px}@keyframes payout-spin{to{transform:rotate(360deg)}}
    .payout-actions{display:flex;justify-content:space-between;align-items:center;gap:.65rem;padding:.75rem;border:1px solid var(--shp-border);border-top:0;border-radius:0 0 14px 14px;background:var(--card)}.payout-page-info{font-size:.72rem;color:var(--shp-muted)}
    .payout-modal-backdrop{display:none;position:fixed;inset:0;z-index:1050;background:rgba(15,23,42,.58);padding:1rem;overflow:auto}.payout-modal-backdrop.show{display:flex;align-items:flex-start;justify-content:center}.payout-modal{width:min(980px,100%);margin:4vh auto;background:var(--card,#fff);border-radius:16px;box-shadow:0 24px 80px rgba(15,23,42,.3);overflow:hidden}.payout-modal-head{display:flex;justify-content:space-between;align-items:flex-start;gap:1rem;padding:1rem;border-bottom:1px solid var(--shp-border)}.payout-modal-title{font-weight:900;color:var(--shp-text);font-size:.95rem}.payout-modal-sub{font-size:.7rem;color:var(--shp-muted);margin-top:.18rem}.payout-close{border:0;background:transparent;color:var(--shp-muted);font-size:1.2rem;cursor:pointer}.payout-detail-body{padding:1rem}.payout-detail-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:.45rem}.payout-detail-card{border:1px solid var(--shp-border);border-radius:10px;padding:.6rem .7rem}.payout-detail-label{font-size:.62rem;text-transform:uppercase;letter-spacing:.04em;color:var(--shp-muted);font-weight:850}.payout-detail-value{font-size:.78rem;font-weight:800;color:var(--shp-text);margin-top:.2rem;word-break:break-word}.payout-detail-section{border-top:1px solid var(--shp-border);padding-top:.8rem;margin-top:.8rem}.payout-detail-section h3{font-size:.76rem;font-weight:900;color:var(--shp-text);margin:0 0 .55rem}.payout-detail-table{width:100%;font-size:.72rem}.payout-detail-table th,.payout-detail-table td{padding:.4rem .45rem;border-bottom:1px solid var(--shp-border);text-align:left}.payout-detail-table th{color:var(--shp-muted);font-weight:800}.payout-raw{max-height:300px;overflow:auto;background:#0f172a;color:#cbd5e1;padding:.75rem;border-radius:9px;font-size:.68rem;line-height:1.45;margin:0}
    @media (max-width:760px){.payout-filters{grid-template-columns:1fr 1fr}.payout-filters .payout-store-field{grid-column:1/-1}.payout-filter-actions{grid-column:1/-1}.payout-filter-actions .btn{flex:1}.payout-kpis{grid-template-columns:1fr 1fr}.payout-detail-grid{grid-template-columns:1fr}.payout-modal{margin:1vh auto}}
</style>
@endpush

@section('content')
<div class="page-wrap">
    <div class="payout-hero"><div><h1>Penarikan Saldo Shopee</h1><p>Daftar penarikan saldo toko lokal dari <code style="color:#e0f2fe">get_wallet_transaction_list</code>, disimpan sebagai Finance settlement.</p></div><div class="payout-live"><i></i> DB-first · sync Shopee</div></div>
    <div id="payoutAlert" class="payout-alert" role="alert"></div>
    <div class="payout-panel"><div class="payout-filters">
        <div class="payout-field payout-store-field"><label for="payoutStore">Toko Shopee</label><select id="payoutStore" class="form-select"><option value="">Memuat toko…</option></select></div>
        <div class="payout-field"><label for="payoutFrom">Tanggal payout dari</label><input id="payoutFrom" type="date" class="form-control"></div>
        <div class="payout-field"><label for="payoutTo">Tanggal payout sampai</label><input id="payoutTo" type="date" class="form-control"></div>
        <div class="payout-filter-actions"><button id="payoutLoad" type="button" class="btn btn-ship-primary btn-pill"><span class="payout-load-label">Tampilkan tersimpan</span></button><button id="payoutSync" type="button" class="btn btn-ship-outline btn-pill"><span class="payout-sync-label">Sync dari Shopee</span></button></div>
    </div><div class="payout-help"><i class="bi bi-info-circle me-1"></i> Untuk seller lokal Shopee Indonesia. Data utama dibaca dari database. Sync hanya menyimpan withdrawal sebagai <strong>Belum verifikasi bank</strong>; tidak membuat jurnal otomatis. Rentang waktu maksimal 15 hari.</div></div>
    <div class="payout-kpis"><div class="payout-kpi"><div class="payout-kpi-label">Payout pada halaman</div><div id="payoutCount" class="payout-kpi-value">—</div><div class="payout-kpi-note">mengikuti cursor API Shopee</div></div><div class="payout-kpi"><div class="payout-kpi-label">Total payout</div><div id="payoutTotal" class="payout-kpi-value">—</div><div class="payout-kpi-note">nilai mata uang payout</div></div><div class="payout-kpi"><div class="payout-kpi-label">Mata uang</div><div id="payoutCurrencies" class="payout-kpi-value">—</div><div class="payout-kpi-note">currency yang ditemukan</div></div><div class="payout-kpi"><div class="payout-kpi-label">Status data</div><div id="payoutStatus" class="payout-kpi-value">Belum dimuat</div><div id="payoutStatusNote" class="payout-kpi-note">Pilih toko untuk mulai</div></div></div>
    <div id="payoutTableWrap" class="payout-table-wrap"><div class="payout-empty">Pilih toko dan rentang tanggal, lalu klik <strong>Tampilkan payout</strong>.</div></div>
    <div id="payoutPagination" class="payout-actions" style="display:none"><button id="payoutPrev" type="button" class="btn btn-sm btn-ship-outline btn-pill">← Sebelumnya</button><span id="payoutPageInfo" class="payout-page-info"></span><button id="payoutNext" type="button" class="btn btn-sm btn-ship-outline btn-pill">Berikutnya →</button></div>
</div>
<div id="payoutModalBackdrop" class="payout-modal-backdrop" role="dialog" aria-modal="true" aria-labelledby="payoutModalTitle"><div class="payout-modal"><div class="payout-modal-head"><div><div id="payoutModalTitle" class="payout-modal-title">Detail payout</div><div id="payoutModalSub" class="payout-modal-sub">Memuat…</div></div><button id="payoutClose" type="button" class="payout-close" aria-label="Tutup">×</button></div><div id="payoutDetailBody" class="payout-detail-body"><div class="payout-empty"><span class="payout-spinner"></span> Memuat detail dari Shopee…</div></div></div></div>
@endsection

@push('scripts')
<script>
(() => {
    const $ = (id) => document.getElementById(id);
    const PAGE_SIZE = 100;
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const state = { stores: [], page: 1, more: false, items: [], cursors: {}, loading: false };
    const today = new Date();
    const iso = (date) => date.toISOString().slice(0, 10);
    const from = new Date(today);
    from.setDate(from.getDate() - 14);
    $('payoutFrom').value = iso(from);
    $('payoutTo').value = iso(today);

    const money = (value, currency = 'IDR') => value === null || value === undefined || value === '' || Number.isNaN(Number(value))
        ? '—'
        : new Intl.NumberFormat('id-ID', { style: 'currency', currency: currency || 'IDR', maximumFractionDigits: 2 }).format(Number(value));
    const dateTime = (value) => {
        if (!value) return '—';
        const date = new Date(value);
        return Number.isNaN(date.getTime()) ? String(value) : date.toLocaleString('id-ID', { dateStyle: 'medium', timeStyle: 'short' });
    };
    const esc = (value) => String(value ?? '').replace(/[&<>'"]/g, (char) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#039;', '"': '&quot;' }[char]));
    const showMessage = (message, type = 'error') => {
        $('payoutAlert').textContent = message;
        $('payoutAlert').className = `payout-alert show ${type}`;
    };
    const hideAlert = () => { $('payoutAlert').className = 'payout-alert'; };
    const api = async (url, options = {}) => {
        const method = String(options.method || 'GET').toUpperCase();
        const headers = { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest', ...(options.headers || {}) };
        if (method !== 'GET' && method !== 'HEAD' && csrf) headers['X-CSRF-TOKEN'] = csrf;
        const response = await fetch(url, { ...options, headers });
        const payload = await response.json().catch(() => ({}));
        if (!response.ok || payload.success === false) throw new Error(payload.message || `Request gagal (${response.status})`);
        return payload;
    };
    const selectedStore = () => state.stores.find((store) => String(store.id) === String($('payoutStore').value));
    const activeStores = () => $('payoutStore').value === 'all' ? state.stores : [selectedStore()].filter(Boolean);
    const storeLabel = (id) => state.stores.find((store) => String(store.id) === String(id))?.name || '—';
    const rangeValid = () => {
        if (!$('payoutFrom').value || !$('payoutTo').value || $('payoutFrom').value > $('payoutTo').value) return false;
        const days = Math.round((new Date(`${$('payoutTo').value}T00:00:00`) - new Date(`${$('payoutFrom').value}T00:00:00`)) / 86400000) + 1;
        return days <= 15;
    };
    const recordsUrl = (store, cursor = '') => {
        const params = new URLSearchParams({ date_from: $('payoutFrom').value, date_to: $('payoutTo').value, page_size: PAGE_SIZE });
        if (cursor) params.set('cursor', cursor);
        return `/api/marketplace/stores/${encodeURIComponent(store.id)}/payout-records?${params}`;
    };
    const syncUrl = (store) => `/api/marketplace/stores/${encodeURIComponent(store.id)}/sync-payouts`;

    const reconciliationLabel = (item) => item.reconciliation_status === 'received'
        ? '<span class="badge text-bg-success">Diterima bank</span>'
        : '<span class="badge text-bg-warning">Belum verifikasi bank</span>';

    const renderRows = (items) => {
        if (!items.length) {
            $('payoutTableWrap').innerHTML = '<div class="payout-empty">Belum ada payout tersimpan pada rentang yang dipilih. Klik <strong>Sync dari Shopee</strong> untuk mengambil data.</div>';
            return;
        }
        $('payoutTableWrap').innerHTML = `<table class="table payout-table"><thead><tr><th>Toko</th><th>Waktu penarikan</th><th>Nominal wallet</th><th>Status rekonsiliasi</th><th>Transaction ID</th><th>Terakhir sync</th><th>Aksi</th></tr></thead><tbody>${items.map((item, index) => `<tr><td>${esc(storeLabel(item.store_id))}</td><td class="payout-muted">${esc(dateTime(item.payout_at))}</td><td class="payout-money">${money(item.payout_amount, item.payout_currency)} <span class="payout-muted">${esc(item.payout_currency || '')}</span></td><td>${reconciliationLabel(item)}</td><td class="payout-code">${esc(item.transaction_id || '—')}</td><td class="payout-muted">${esc(dateTime(item.stored_at))}</td><td class="text-end"><button type="button" class="btn btn-sm btn-ship-outline btn-pill payout-detail-btn" data-index="${index}"><i class="bi bi-eye me-1"></i>Detail</button></td></tr>`).join('')}</tbody></table>`;
        document.querySelectorAll('.payout-detail-btn').forEach((button) => button.addEventListener('click', () => openDetail(items[Number(button.dataset.index)])));
    };
    const renderPagination = () => {
        $('payoutPagination').style.display = 'flex';
        $('payoutPrev').disabled = state.page <= 1;
        $('payoutNext').disabled = !state.more;
        $('payoutPageInfo').textContent = `Halaman ${state.page}${state.more ? ' · masih ada data berikutnya' : ' · halaman terakhir'}`;
    };
    const fetchStorePage = async (store) => {
        const payload = await api(recordsUrl(store, state.cursors[store.id] || ''));
        const items = (payload.data?.items || []).map((item) => ({ ...item, store_id: store.id }));
        state.cursors[store.id] = payload.data?.next_cursor || '';
        return { items, more: Boolean(payload.data?.more), lastSyncedAt: payload.data?.last_synced_at || null };
    };
    const loadList = async () => {
        hideAlert();
        const stores = activeStores();
        if (!stores.length) { showMessage('Tidak ada toko Shopee aktif.'); return; }
        if (!rangeValid()) { showMessage($('payoutFrom').value > $('payoutTo').value ? 'Tanggal mulai tidak boleh melewati tanggal akhir.' : 'Rentang tanggal payout wajib diisi dan maksimal 15 hari.'); return; }
        if (state.page === 1) state.cursors = {};
        state.loading = true;
        $('payoutLoad').disabled = true;
        $('payoutLoad').querySelector('.payout-load-label').innerHTML = '<span class="payout-spinner"></span> Memuat…';
        $('payoutStatus').textContent = 'Memuat…';
        $('payoutTableWrap').innerHTML = '<div class="payout-empty"><span class="payout-spinner"></span> Membaca payout tersimpan…</div>';
        try {
            const results = await Promise.all(stores.map(fetchStorePage));
            state.items = results.flatMap((result) => result.items).sort((a, b) => (b.payout_time || 0) - (a.payout_time || 0));
            state.more = results.some((result) => result.more);
            renderRows(state.items);
            renderPagination();
            const currencies = [...new Set(state.items.map((item) => item.payout_currency).filter(Boolean))];
            $('payoutCount').textContent = state.items.length.toLocaleString('id-ID');
            $('payoutTotal').textContent = state.items.reduce((sum, item) => sum + Number(item.payout_amount || 0), 0).toLocaleString('id-ID', { maximumFractionDigits: 2 });
            $('payoutCurrencies').textContent = currencies.join(', ') || '—';
            $('payoutStatus').textContent = 'Database';
            $('payoutStatusNote').textContent = `${$('payoutStore').value === 'all' ? `${stores.length} toko` : selectedStore()?.name || 'Toko'} · data tersimpan`;
        } catch (error) {
            $('payoutTableWrap').innerHTML = '<div class="payout-empty">Tidak dapat membaca data payout tersimpan.</div>';
            $('payoutPagination').style.display = 'none';
            $('payoutStatus').textContent = 'Gagal';
            $('payoutStatusNote').textContent = 'Periksa koneksi database';
            showMessage(error.message);
        } finally {
            state.loading = false;
            $('payoutLoad').disabled = false;
            $('payoutLoad').querySelector('.payout-load-label').textContent = 'Tampilkan tersimpan';
        }
    };
    const renderDetail = (row) => {
        const raw = row.raw || {};
        const status = row.reconciliation_status === 'received' ? 'Diterima bank' : 'Belum verifikasi bank';
        $('payoutDetailBody').innerHTML = `<div class="payout-detail-grid">${[['Toko', storeLabel(row.store_id)],['Waktu penarikan', dateTime(row.payout_at)],['Nominal penarikan', money(row.payout_amount, row.payout_currency)],['Currency', row.payout_currency || 'IDR'],['Status rekonsiliasi', status],['Status Shopee', row.shopee_status || '—'],['Tipe transaksi', row.transaction_type || '—'],['Transaction ID', row.transaction_id || '—'],['Keterangan', row.reason || '—'],['Finance settlement ID', row.finance_settlement_id || '—']].map(([key, value]) => `<div class="payout-detail-card"><div class="payout-detail-label">${esc(key)}</div><div class="payout-detail-value">${esc(value)}</div></div>`).join('')}</div><div class="payout-detail-section"><h3>Raw response Shopee tersimpan</h3><pre class="payout-raw">${esc(JSON.stringify(raw, null, 2))}</pre></div>`;
    };
    const openDetail = (row) => {
        if (!row) return;
        $('payoutModalBackdrop').classList.add('show');
        $('payoutModalTitle').textContent = `Detail penarikan · ${row.payout_at ? dateTime(row.payout_at) : '—'}`;
        $('payoutModalSub').textContent = `${storeLabel(row.store_id)} · database`;
        renderDetail(row);
    };
    const syncFromShopee = async () => {
        hideAlert();
        const stores = activeStores();
        if (!stores.length) { showMessage('Tidak ada toko Shopee aktif.'); return; }
        if (!rangeValid()) { showMessage('Rentang tanggal payout wajib diisi dan maksimal 15 hari.'); return; }
        const connected = stores.filter((store) => store.connection_status === 'CONNECTED');
        if (!connected.length) { showMessage('Tidak ada toko Shopee yang terhubung untuk disinkronkan.'); return; }
        $('payoutSync').disabled = true;
        $('payoutSync').querySelector('.payout-sync-label').innerHTML = '<span class="payout-spinner"></span> Sync…';
        const totals = { found: 0, synced: 0, created: 0, updated: 0, invalid: 0 };
        const errors = [];
        try {
            for (const store of connected) {
                try {
                    const payload = await api(syncUrl(store), { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ date_from: $('payoutFrom').value, date_to: $('payoutTo').value, page_size: PAGE_SIZE }) });
                    Object.keys(totals).forEach((key) => { totals[key] += Number(payload.data?.[key] || 0); });
                } catch (error) {
                    errors.push(`${store.name}: ${error.message}`);
                }
            }
            state.page = 1;
            state.cursors = {};
            await loadList();
            const resultText = `${totals.created} baru, ${totals.updated} diperbarui, ${totals.invalid} tidak valid`;
            showMessage(errors.length ? `Sync selesai sebagian: ${resultText}. ${errors.join(' | ')}` : `Sync berhasil: ${resultText}. Tidak ada jurnal yang dibuat.`, errors.length ? 'error' : 'success');
        } finally {
            $('payoutSync').disabled = false;
            $('payoutSync').querySelector('.payout-sync-label').textContent = 'Sync dari Shopee';
        }
    };
    const loadStores = async () => {
        try {
            const response = await api('/api/marketplace/stores');
            state.stores = (Array.isArray(response) ? response : response.data || []).filter((store) => ['shopee', 'shp'].includes(String(store.channel?.code || '').toLowerCase()) && store.is_active !== false);
            $('payoutStore').innerHTML = state.stores.length ? `<option value="all">Semua toko Shopee aktif (${state.stores.length})</option>${state.stores.map((store) => `<option value="${esc(store.id)}">${esc(store.name)} · ${store.connection_status === 'CONNECTED' ? 'Connected' : 'Offline'}</option>`).join('')}` : '<option value="">Tidak ada toko Shopee aktif</option>';
        } catch (error) {
            $('payoutStore').innerHTML = '<option value="">Gagal memuat toko</option>';
            showMessage(error.message);
        }
    };
    $('payoutLoad').addEventListener('click', () => { state.page = 1; loadList(); });
    $('payoutSync').addEventListener('click', syncFromShopee);
    $('payoutPrev').addEventListener('click', () => { if (state.page > 1) { state.page--; loadList(); } });
    $('payoutNext').addEventListener('click', () => { if (state.more) { state.page++; loadList(); } });
    $('payoutClose').addEventListener('click', () => $('payoutModalBackdrop').classList.remove('show'));
    $('payoutModalBackdrop').addEventListener('click', (event) => { if (event.target === $('payoutModalBackdrop')) $('payoutModalBackdrop').classList.remove('show'); });
    document.addEventListener('keydown', (event) => { if (event.key === 'Escape') $('payoutModalBackdrop').classList.remove('show'); });
    loadStores();
})();
</script>
@endpush
