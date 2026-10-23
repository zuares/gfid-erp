@extends('layouts.app')
@section('title', 'Marketplace • Payout')

@include('marketplace._shared')

@push('head')
<style>
    .payout-page{display:grid;gap:.9rem}
    .payout-header,.payout-panel,.payout-kpi,.payout-results{border:1px solid var(--shp-border);border-radius:var(--gf-radius-card,16px);background:var(--card);box-shadow:var(--gf-shadow-sm,0 1px 2px rgba(16,24,40,.04))}
    .payout-header{display:flex;justify-content:space-between;align-items:flex-start;gap:1rem;flex-wrap:wrap;padding:1.05rem 1.15rem;border-top:3px solid var(--shp-accent)}
    .payout-eyebrow{display:flex;align-items:center;gap:.4rem;color:var(--shp-accent);font-size:.68rem;font-weight:800;letter-spacing:.04em;text-transform:uppercase}
    .payout-header h1{margin:.22rem 0 .3rem;color:var(--shp-text);font-size:1.25rem;font-weight:850;letter-spacing:-.025em}
    .payout-header p{max-width:48rem;margin:0;color:var(--shp-muted);font-size:.78rem;line-height:1.55}
    .payout-source-pill{display:inline-flex;align-items:center;gap:.4rem;padding:.42rem .65rem;border:1px solid var(--shp-border-strong);border-radius:999px;color:var(--shp-accent);background:color-mix(in srgb,var(--shp-accent) 8%,transparent);font-size:.7rem;font-weight:800;white-space:nowrap}
    .payout-source-pill i{font-size:.52rem}
    .payout-panel{padding:.95rem 1rem}
    .payout-filters{display:grid;grid-template-columns:minmax(220px,1.3fr) repeat(2,minmax(150px,.75fr)) auto;gap:.75rem;align-items:end}
    .payout-field{display:grid;gap:.38rem;min-width:0}.payout-field label{color:var(--shp-muted);font-size:.7rem;font-weight:800}
    .payout-field .form-control,.payout-field .form-select{min-height:40px;border-radius:8px;border-color:var(--shp-border);color:var(--shp-text);font-size:.82rem}
    .payout-filter-actions{display:flex;gap:.5rem;align-items:center}.payout-filter-actions .btn{min-height:40px}
    .payout-help{display:flex;gap:.4rem;align-items:flex-start;margin:.8rem 0 0;color:var(--shp-muted);font-size:.72rem;line-height:1.5}.payout-help i{color:var(--shp-accent);margin-top:.12rem}
    .payout-kpis{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:.7rem}.payout-kpi{min-height:96px;padding:.8rem .9rem}.payout-kpi-label{color:var(--shp-muted);font-size:.66rem;font-weight:800;letter-spacing:.04em;text-transform:uppercase}.payout-kpi-value{margin-top:.25rem;color:var(--shp-text);font-size:1.08rem;font-weight:850;line-height:1.2}.payout-kpi-note{margin-top:.2rem;color:var(--shp-muted);font-size:.69rem;line-height:1.4}
    .payout-results{overflow:hidden}.payout-table-wrap{overflow-x:auto}.payout-table{min-width:980px;margin:0}.payout-table th{padding:.72rem .8rem;border-bottom:1px solid var(--shp-border);color:var(--shp-muted);background:color-mix(in srgb,var(--shp-border) 18%,transparent);font-size:.66rem;font-weight:800;letter-spacing:.04em;text-transform:uppercase;white-space:nowrap}.payout-table td{padding:.72rem .8rem;border-top:1px solid color-mix(in srgb,var(--shp-border) 78%,transparent);color:var(--shp-text);font-size:.76rem;vertical-align:middle;white-space:nowrap}.payout-table tbody tr:hover{background:color-mix(in srgb,var(--shp-accent) 3%,transparent)}
    .payout-muted{color:var(--shp-muted)}.payout-money{color:#047857;font-weight:850;white-space:nowrap}.payout-code{color:var(--shp-text);font-family:ui-monospace,SFMono-Regular,Menlo,monospace;font-size:.72rem;font-weight:800}.payout-empty{text-align:center;padding:2.6rem 1rem;color:var(--shp-muted);font-size:.82rem;line-height:1.5}.payout-alert{display:none;border-radius:10px;padding:.75rem .85rem;font-size:.78rem}.payout-alert.show{display:block}.payout-alert.error{border:1px solid #fecaca;background:#fef2f2;color:#991b1b}.payout-alert.success{border:1px solid #a7f3d0;background:#ecfdf5;color:#065f46}.payout-spinner{display:inline-block;width:14px;height:14px;border:2px solid currentColor;border-right-color:transparent;border-radius:50%;animation:payout-spin .65s linear infinite;vertical-align:-2px}@keyframes payout-spin{to{transform:rotate(360deg)}}
    .payout-actions{display:flex;justify-content:space-between;align-items:center;gap:.65rem;padding:.75rem 1rem;border-top:1px solid var(--shp-border);background:var(--card)}.payout-page-info{color:var(--shp-muted);font-size:.72rem}.payout-actions .btn{min-height:36px}
    .payout-card-list{display:none}.payout-card{display:grid;gap:.65rem;padding:.85rem;border-bottom:1px solid var(--shp-border)}.payout-card:last-child{border-bottom:0}.payout-card-top,.payout-card-meta,.payout-card-actions{display:flex;justify-content:space-between;align-items:flex-start;gap:.75rem}.payout-card-store{color:var(--shp-text);font-size:.78rem;font-weight:800}.payout-card-date,.payout-card-label{color:var(--shp-muted);font-size:.7rem}.payout-card-amount{color:#047857;font-size:.95rem;font-weight:850}.payout-card-id{color:var(--shp-text);font-family:ui-monospace,SFMono-Regular,Menlo,monospace;font-size:.7rem;overflow-wrap:anywhere}.payout-card-actions{align-items:center}
    .payout-modal-backdrop{display:none;position:fixed;inset:0;z-index:1050;align-items:flex-start;justify-content:center;overflow:auto;padding:1rem;background:rgba(15,23,42,.58)}.payout-modal-backdrop.show{display:flex}.payout-modal{width:min(980px,100%);margin:4vh auto;overflow:hidden;border-radius:var(--gf-radius-card,16px);background:var(--card,#fff);box-shadow:0 24px 80px rgba(15,23,42,.3)}.payout-modal-head{display:flex;justify-content:space-between;align-items:flex-start;gap:1rem;padding:1rem;border-bottom:1px solid var(--shp-border)}.payout-modal-title{color:var(--shp-text);font-size:.95rem;font-weight:900}.payout-modal-sub{margin-top:.18rem;color:var(--shp-muted);font-size:.7rem}.payout-close{display:grid;width:40px;height:40px;place-items:center;border:0;border-radius:8px;background:transparent;color:var(--shp-muted);font-size:1.2rem;cursor:pointer}.payout-close:hover{background:color-mix(in srgb,var(--shp-border) 35%,transparent)}.payout-close:focus-visible,.payout-filter-actions .btn:focus-visible,.payout-actions .btn:focus-visible{outline:3px solid color-mix(in srgb,var(--shp-accent) 30%,transparent);outline-offset:2px}.payout-detail-body{padding:1rem}.payout-detail-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:.5rem}.payout-detail-card{padding:.65rem .7rem;border:1px solid var(--shp-border);border-radius:10px}.payout-detail-label{color:var(--shp-muted);font-size:.62rem;font-weight:800;letter-spacing:.04em;text-transform:uppercase}.payout-detail-value{margin-top:.2rem;color:var(--shp-text);font-size:.78rem;font-weight:800;overflow-wrap:anywhere}.payout-detail-section{margin-top:.85rem;padding-top:.85rem;border-top:1px solid var(--shp-border)}.payout-detail-section h3{margin:0 0 .55rem;color:var(--shp-text);font-size:.76rem;font-weight:900}.payout-raw{max-height:300px;margin:0;overflow:auto;border-radius:9px;background:#0f172a;color:#cbd5e1;padding:.75rem;font-size:.68rem;line-height:1.45}
    @media (max-width:960px){.payout-filters{grid-template-columns:repeat(2,minmax(0,1fr))}.payout-store-field{grid-column:1/-1}.payout-filter-actions{grid-column:1/-1;justify-content:flex-end}}
    @media (max-width:767.98px){.payout-page{gap:.75rem}.payout-header{padding:1rem}.payout-header h1{font-size:1.1rem}.payout-source-pill{align-self:flex-start}.payout-panel{padding:.85rem}.payout-filters{grid-template-columns:1fr}.payout-store-field,.payout-filter-actions{grid-column:auto}.payout-filter-actions{display:grid;grid-template-columns:1fr 1fr}.payout-filter-actions .btn{width:100%;min-height:44px;padding-inline:.5rem}.payout-help{font-size:.74rem}.payout-kpis{grid-template-columns:1fr 1fr;gap:.55rem}.payout-kpi{min-height:88px;padding:.7rem}.payout-kpi-value{font-size:.96rem}.payout-table-wrap{overflow:visible}.payout-table{display:none}.payout-card-list{display:block}.payout-card-top,.payout-card-meta{align-items:flex-start}.payout-card-meta{display:grid;grid-template-columns:1fr 1fr}.payout-card-actions{align-items:center}.payout-actions{padding:.7rem}.payout-actions .btn{min-height:42px}.payout-detail-grid{grid-template-columns:1fr}.payout-modal{margin:1vh auto}.payout-detail-body{padding:.85rem}}
    @media (prefers-reduced-motion:reduce){.payout-spinner{animation:none}}
</style>
@endpush

@section('content')
<div class="page-wrap">
    <div class="payout-page">
    <header class="payout-header">
        <div><div class="payout-eyebrow"><i class="bi bi-wallet2"></i> Marketplace finance</div><h1>Penarikan Saldo Shopee</h1><p>Data utama dibaca dari database sebagai Finance settlement. Sumbernya <code>get_wallet_transaction_list</code>; sync hanya mengambil perubahan dari Shopee.</p></div>
        <div class="payout-source-pill"><i class="bi bi-circle-fill"></i> DB-first · database sebagai sumber utama</div>
    </header>
    <div id="payoutAlert" class="payout-alert" role="alert"></div>
    <div class="payout-panel"><div class="payout-filters">
        <div class="payout-field payout-store-field"><label for="payoutStore">Toko Shopee</label><select id="payoutStore" class="form-select"><option value="">Memuat toko…</option></select></div>
        <div class="payout-field"><label for="payoutSource">Sumber daftar</label><select id="payoutSource" class="form-select" aria-label="Pilih sumber daftar payout"><option value="stored">Tersimpan di database</option><option value="live">Live dari Shopee</option></select></div>
        <div class="payout-field"><label for="payoutFrom">Tanggal payout dari</label><input id="payoutFrom" type="date" class="form-control"></div>
        <div class="payout-field"><label for="payoutTo">Tanggal payout sampai</label><input id="payoutTo" type="date" class="form-control"></div>
        <div class="payout-filter-actions"><button id="payoutLoad" type="button" class="btn btn-ship-primary btn-pill"><span class="payout-load-label">Tampilkan tersimpan</span></button><button id="payoutSync" type="button" class="btn btn-ship-outline btn-pill"><span class="payout-sync-label">Sync dari Shopee</span></button></div>
    </div><div class="payout-help"><i class="bi bi-info-circle"></i><span>Untuk seller lokal Shopee Indonesia. Default membaca snapshot database; mode <strong>Live dari Shopee</strong> hanya untuk validasi langsung. Sync menyimpan withdrawal sebagai <strong>Belum verifikasi bank</strong>; tidak membuat jurnal otomatis. Rentang waktu maksimal 15 hari.</span></div></div>
    <div class="payout-kpis"><div class="payout-kpi"><div class="payout-kpi-label">Payout pada halaman</div><div id="payoutCount" class="payout-kpi-value">—</div><div class="payout-kpi-note">mengikuti sumber daftar</div></div><div class="payout-kpi"><div class="payout-kpi-label">Total payout</div><div id="payoutTotal" class="payout-kpi-value">—</div><div class="payout-kpi-note">dihitung terpisah per currency</div></div><div class="payout-kpi"><div class="payout-kpi-label">Mata uang</div><div id="payoutCurrencies" class="payout-kpi-value">—</div><div class="payout-kpi-note">currency yang ditemukan</div></div><div class="payout-kpi"><div class="payout-kpi-label">Status data</div><div id="payoutStatus" class="payout-kpi-value">Memuat…</div><div id="payoutStatusNote" class="payout-kpi-note">Snapshot database dimuat otomatis</div></div></div>
    <section class="payout-results" aria-labelledby="payoutResultsTitle"><h2 id="payoutResultsTitle" class="visually-hidden">Daftar payout</h2><div id="payoutTableWrap" class="payout-table-wrap"><div class="payout-empty"><span class="payout-spinner"></span> Membaca payout tersimpan…</div></div>
    <div id="payoutPagination" class="payout-actions" style="display:none"><button id="payoutPrev" type="button" class="btn btn-sm btn-ship-outline btn-pill">← Sebelumnya</button><span id="payoutPageInfo" class="payout-page-info"></span><button id="payoutNext" type="button" class="btn btn-sm btn-ship-outline btn-pill">Berikutnya →</button></div>
    </section>
    </div>
</div>
<div id="payoutModalBackdrop" class="payout-modal-backdrop" role="dialog" aria-modal="true" aria-labelledby="payoutModalTitle" aria-describedby="payoutModalSub"><div class="payout-modal"><div class="payout-modal-head"><div><div id="payoutModalTitle" class="payout-modal-title">Detail payout tersimpan</div><div id="payoutModalSub" class="payout-modal-sub">Memuat…</div></div><button id="payoutClose" type="button" class="payout-close" aria-label="Tutup detail payout">×</button></div><div id="payoutDetailBody" class="payout-detail-body"><div class="payout-empty"><span class="payout-spinner"></span> Memuat detail tersimpan…</div></div></div></div>
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
        const path = $('payoutSource').value === 'live' ? 'payout-info' : 'payout-records';
        return `/api/marketplace/stores/${encodeURIComponent(store.id)}/${path}?${params}`;
    };
    const syncUrl = (store) => `/api/marketplace/stores/${encodeURIComponent(store.id)}/sync-payouts`;

    const reconciliationLabel = (item) => item.source === 'shopee_live'
        ? '<span class="badge text-bg-info">Live Shopee</span>'
        : item.status === 'void'
        ? '<span class="badge text-bg-danger">Dibatalkan</span>'
        : item.reconciliation_status === 'received'
            ? '<span class="badge text-bg-success">Diterima bank</span>'
            : '<span class="badge text-bg-warning">Belum verifikasi bank</span>';

    const renderRows = (items) => {
        if (!items.length) {
            $('payoutTableWrap').innerHTML = $('payoutSource').value === 'live'
                ? '<div class="payout-empty">Tidak ada payout pada rentang live yang dipilih.</div>'
                : '<div class="payout-empty">Belum ada payout tersimpan pada rentang yang dipilih. Klik <strong>Sync dari Shopee</strong> untuk mengambil data.</div>';
            return;
        }
        const table = `<table class="table payout-table"><thead><tr><th>Toko</th><th>Waktu penarikan</th><th>Nominal wallet</th><th>Status rekonsiliasi</th><th>Transaction ID</th><th>Terakhir sync</th><th>Aksi</th></tr></thead><tbody>${items.map((item, index) => `<tr><td>${esc(storeLabel(item.store_id))}</td><td class="payout-muted">${esc(dateTime(item.payout_at))}</td><td class="payout-money">${money(item.payout_amount, item.payout_currency)} <span class="payout-muted">${esc(item.payout_currency || '')}</span></td><td>${reconciliationLabel(item)}</td><td class="payout-code">${esc(item.transaction_id || '—')}</td><td class="payout-muted">${esc(dateTime(item.stored_at))}</td><td class="text-end"><button type="button" class="btn btn-sm btn-ship-outline btn-pill payout-detail-btn" data-index="${index}"><i class="bi bi-eye me-1"></i>Detail</button></td></tr>`).join('')}</tbody></table>`;
        const cards = `<div class="payout-card-list">${items.map((item, index) => `<article class="payout-card"><div class="payout-card-top"><div><div class="payout-card-store">${esc(storeLabel(item.store_id))}</div><div class="payout-card-date">${esc(dateTime(item.payout_at))}</div></div>${reconciliationLabel(item)}</div><div class="payout-card-meta"><div><div class="payout-card-label">Nominal wallet</div><div class="payout-card-amount">${money(item.payout_amount, item.payout_currency)}</div></div><div><div class="payout-card-label">Transaction ID</div><div class="payout-card-id">${esc(item.transaction_id || '—')}</div></div></div><div class="payout-card-actions"><span class="payout-card-label">Sync ${esc(dateTime(item.stored_at))}</span><button type="button" class="btn btn-sm btn-ship-outline btn-pill payout-detail-btn" data-index="${index}"><i class="bi bi-eye me-1"></i>Detail</button></div></article>`).join('')}</div>`;
        $('payoutTableWrap').innerHTML = table + cards;
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
        $('payoutTableWrap').innerHTML = '<div class="payout-empty"><span class="payout-spinner"></span> ' + ($('payoutSource').value === 'live' ? 'Mengambil payout live dari Shopee…' : 'Membaca payout tersimpan…') + '</div>';
        try {
            const results = await Promise.all(stores.map(fetchStorePage));
            state.items = results.flatMap((result) => result.items).sort((a, b) => (b.payout_time || 0) - (a.payout_time || 0));
            state.more = results.some((result) => result.more);
            renderRows(state.items);
            renderPagination();
            const currencies = [...new Set(state.items.map((item) => item.payout_currency).filter(Boolean))];
            const totalsByCurrency = state.items.reduce((totals, item) => {
                const currency = item.payout_currency || 'IDR';
                totals[currency] = (totals[currency] || 0) + Number(item.payout_amount || 0);
                return totals;
            }, {});
            $('payoutCount').textContent = state.items.length.toLocaleString('id-ID');
            $('payoutTotal').textContent = Object.entries(totalsByCurrency).map(([currency, total]) => money(total, currency)).join(' · ') || '—';
            $('payoutCurrencies').textContent = currencies.join(', ') || '—';
            $('payoutStatus').textContent = $('payoutSource').value === 'live' ? 'Live Shopee' : 'Database';
            const latestSync = results.map((result) => result.lastSyncedAt).filter(Boolean).sort().pop();
            const storeText = $('payoutStore').value === 'all' ? String(stores.length) + ' toko' : selectedStore()?.name || 'Toko';
            $('payoutStatusNote').textContent = $('payoutSource').value === 'live'
                ? storeText + ' · response langsung dari Shopee'
                : storeText + ' · sync ' + (latestSync ? dateTime(latestSync) : 'belum pernah');
        } catch (error) {
            $('payoutTableWrap').innerHTML = '<div class="payout-empty">Tidak dapat membaca data payout ' + ($('payoutSource').value === 'live' ? 'live dari Shopee.' : 'tersimpan.') + '</div>';
            $('payoutPagination').style.display = 'none';
            $('payoutStatus').textContent = 'Gagal';
            $('payoutStatusNote').textContent = 'Periksa koneksi database';
            showMessage(error.message);
        } finally {
            state.loading = false;
            $('payoutLoad').disabled = false;
            $('payoutLoad').querySelector('.payout-load-label').textContent = $('payoutSource').value === 'live' ? 'Tampilkan live' : 'Tampilkan tersimpan';
        }
    };
    const renderDetail = (row) => {
        const raw = row.raw || {};
        const status = row.source === 'shopee_live' ? 'Live Shopee' : row.status === 'void' ? 'Dibatalkan' : row.reconciliation_status === 'received' ? 'Diterima bank' : 'Belum verifikasi bank';
        $('payoutDetailBody').innerHTML = `<div class="payout-detail-grid">${[['Toko', storeLabel(row.store_id)],['Waktu penarikan', dateTime(row.payout_at)],['Nominal penarikan', money(row.payout_amount, row.payout_currency)],['Currency', row.payout_currency || 'IDR'],['Status rekonsiliasi', status],['Status Shopee', row.shopee_status || '—'],['Tipe transaksi', row.transaction_type || '—'],['Transaction ID', row.transaction_id || '—'],['Keterangan', row.reason || '—'],['Finance settlement ID', row.finance_settlement_id || '—']].map(([key, value]) => `<div class="payout-detail-card"><div class="payout-detail-label">${esc(key)}</div><div class="payout-detail-value">${esc(value)}</div></div>`).join('')}</div><div class="payout-detail-section"><h3>Raw response Shopee tersimpan</h3><pre class="payout-raw">${esc(JSON.stringify(raw, null, 2))}</pre></div>`;
    };
    const openDetail = (row) => {
        if (!row) return;
        $('payoutModalBackdrop').classList.add('show');
        $('payoutModalTitle').textContent = `Detail penarikan · ${row.payout_at ? dateTime(row.payout_at) : '—'}`;
        $('payoutModalSub').textContent = 'Toko ' + storeLabel(row.store_id) + ' · ' + (row.source === 'shopee_live' ? 'live dari Shopee' : 'snapshot database');
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
            return state.stores.length > 0;
        } catch (error) {
            $('payoutStore').innerHTML = '<option value="">Gagal memuat toko</option>';
            showMessage(error.message);
            return false;
        }
    };
    $('payoutLoad').addEventListener('click', () => { state.page = 1; loadList(); });
    $('payoutSource').addEventListener('change', () => { state.page = 1; loadList(); });
    $('payoutSync').addEventListener('click', syncFromShopee);
    $('payoutPrev').addEventListener('click', () => { if (state.page > 1) { state.page--; loadList(); } });
    $('payoutNext').addEventListener('click', () => { if (state.more) { state.page++; loadList(); } });
    $('payoutClose').addEventListener('click', () => $('payoutModalBackdrop').classList.remove('show'));
    $('payoutModalBackdrop').addEventListener('click', (event) => { if (event.target === $('payoutModalBackdrop')) $('payoutModalBackdrop').classList.remove('show'); });
    document.addEventListener('keydown', (event) => { if (event.key === 'Escape') $('payoutModalBackdrop').classList.remove('show'); });
    loadStores().then((hasStores) => { if (hasStores) loadList(); });
})();
</script>
@endpush
