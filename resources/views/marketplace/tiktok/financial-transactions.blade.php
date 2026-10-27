@extends('layouts.app')
@section('title', 'Marketplace • Transaksi Finansial TikTok')

@include('marketplace._shared')

@push('head')
<style>
    .tt-finance-page { max-width: 1280px; margin-inline: auto; padding-bottom: 1.5rem; }
    .tt-finance-hero { display:flex; justify-content:space-between; gap:1rem; align-items:flex-start; flex-wrap:wrap; margin-bottom:.9rem; padding:1.2rem 1.3rem; border-radius:18px; color:#fff; background:linear-gradient(135deg,#111827,#374151 58%,#0f766e); box-shadow:0 14px 34px rgba(15,23,42,.18); }
    .tt-finance-hero h1 { margin:0 0 .35rem; font-size:1.28rem; font-weight:850; letter-spacing:-.03em; }
    .tt-finance-hero p { max-width:54rem; margin:0; color:#d1fae5; font-size:.8rem; line-height:1.5; }
    .tt-finance-badge { display:inline-flex; align-items:center; gap:.45rem; padding:.38rem .72rem; border:1px solid rgba(255,255,255,.25); border-radius:999px; color:#ccfbf1; background:rgba(0,0,0,.18); font-size:.72rem; font-weight:800; white-space:nowrap; }
    .tt-finance-badge i { width:8px; height:8px; border-radius:50%; background:#4ade80; box-shadow:0 0 0 4px rgba(74,222,128,.2); }
    .tt-finance-card { border:1px solid var(--border-color, #e5e7eb); border-radius:14px; background:var(--card, #fff); }
    .tt-finance-filters { display:grid; grid-template-columns:1.25fr 1.25fr .8fr .8fr .8fr; gap:.75rem; align-items:end; padding:1rem; margin-bottom:.9rem; }
    .tt-finance-field label { display:block; margin-bottom:.3rem; color:#64748b; font-size:.68rem; font-weight:850; letter-spacing:.04em; text-transform:uppercase; }
    .tt-finance-field .form-control, .tt-finance-field .form-select { font-size:.82rem; border-radius:9px; }
    .tt-finance-types { grid-column:1 / -1; display:flex; flex-wrap:wrap; gap:.45rem .8rem; padding-top:.2rem; }
    .tt-finance-type { display:inline-flex; align-items:center; gap:.35rem; color:#475569; font-size:.76rem; }
    .tt-finance-actions { grid-column:1 / -1; display:flex; justify-content:space-between; gap:.6rem; flex-wrap:wrap; align-items:center; }
    .tt-finance-actions .btn { border-radius:9px; font-size:.78rem; font-weight:750; }
    .tt-finance-alert { display:none; padding:.7rem .85rem; margin-bottom:.9rem; border:1px solid #fecaca; border-radius:10px; color:#991b1b; background:#fef2f2; font-size:.78rem; }
    .tt-finance-alert.show { display:block; }
    .tt-finance-alert.success { border-color:#bbf7d0; color:#166534; background:#f0fdf4; }
    .tt-finance-storage-status { color:#64748b; font-size:.72rem; }
    .tt-finance-kpis { display:grid; grid-template-columns:repeat(5,minmax(0,1fr)); gap:.7rem; margin-bottom:.9rem; }
    .tt-finance-kpi { padding:.85rem 1rem; }
    .tt-finance-kpi small { display:block; color:#64748b; font-size:.68rem; font-weight:750; }
    .tt-finance-kpi strong { display:block; margin-top:.25rem; color:#111827; font-size:1rem; font-variant-numeric:tabular-nums; }
    .tt-finance-kpi.in strong { color:#047857; }
    .tt-finance-kpi.out strong { color:#b91c1c; }
    .tt-finance-table-wrap { overflow:auto; }
    .tt-finance-table { width:100%; min-width:900px; margin:0; font-size:.78rem; }
    .tt-finance-table th { padding:.72rem .8rem; color:#64748b; background:#f8fafc; font-size:.67rem; letter-spacing:.03em; text-transform:uppercase; white-space:nowrap; }
    .tt-finance-table td { padding:.75rem .8rem; vertical-align:top; border-top:1px solid #eef2f7; }
    .tt-finance-table code { color:#334155; font-size:.72rem; }
    .tt-finance-pill { display:inline-flex; padding:.25rem .5rem; border-radius:999px; font-size:.68rem; font-weight:800; white-space:nowrap; }
    .tt-finance-pill.success { color:#047857; background:#ecfdf5; }
    .tt-finance-pill.primary { color:#1d4ed8; background:#eff6ff; }
    .tt-finance-pill.warning { color:#a16207; background:#fefce8; }
    .tt-finance-pill.danger { color:#b91c1c; background:#fef2f2; }
    .tt-finance-pill.secondary { color:#475569; background:#f1f5f9; }
    .tt-finance-flow-in { color:#047857; font-weight:800; }
    .tt-finance-flow-out { color:#b91c1c; font-weight:800; }
    .tt-finance-flow-unknown { color:#a16207; font-weight:800; }
    .tt-finance-empty { padding:3rem 1rem; color:#64748b; text-align:center; }
    .tt-finance-empty i { display:block; margin-bottom:.5rem; color:#94a3b8; font-size:1.8rem; }
    .tt-finance-pagination { display:flex; align-items:center; justify-content:space-between; gap:.75rem; padding:.8rem 1rem; border-top:1px solid #eef2f7; color:#64748b; font-size:.74rem; }
    .tt-finance-pagination .btn { border-radius:8px; font-size:.75rem; }
    .tt-finance-raw { max-width:280px; margin:.4rem 0 0; padding:.45rem; border-radius:7px; color:#475569; background:#f8fafc; font-size:.65rem; white-space:pre-wrap; word-break:break-word; }
    @media (max-width: 900px) { .tt-finance-filters { grid-template-columns:1fr 1fr; } .tt-finance-kpis { grid-template-columns:repeat(3,minmax(0,1fr)); } }
    @media (max-width: 600px) { .tt-finance-filters { grid-template-columns:1fr; } .tt-finance-kpis { grid-template-columns:1fr 1fr; } .tt-finance-actions { align-items:stretch; } .tt-finance-actions .btn { flex:1; } }
</style>
@endpush

@section('content')
<div class="page-wrap tt-finance-page">
    <section class="tt-finance-hero">
        <div>
            <h1>Transaksi Finansial TikTok Shop</h1>
            <p>Gabungkan mutasi akun seller dengan detail settlement statement TikTok Shop. Data disinkronkan secara idempotent; rincian revenue, ongkir, fee, pajak, reserve, dan adjustment tersedia dari sumber statement.</p>
        </div>
        <span class="tt-finance-badge"><i aria-hidden="true"></i> TikTok Finance · API 202309 + 202501</span>
    </section>

    <div id="ttAlert" class="tt-finance-alert" role="alert"></div>

    <section class="tt-finance-card tt-finance-filters" aria-label="Filter transaksi finansial TikTok">
        <div class="tt-finance-field">
            <label for="ttSource">Sumber data</label>
            <select id="ttSource" class="form-select">
                @foreach ($dataSources as $source)
                    <option value="{{ $source['code'] }}">{{ $source['label'] }}</option>
                @endforeach
            </select>
        </div>
        <div class="tt-finance-field">
            <label for="ttStore">Toko TikTok</label>
            <select id="ttStore" class="form-select">
                @forelse ($stores as $store)
                    <option value="{{ $store->id }}">{{ $store->name }} · TikTok Shop</option>
                @empty
                    <option value="">Tidak ada toko TikTok terhubung</option>
                @endforelse
            </select>
        </div>
        <div class="tt-finance-field"><label for="ttFrom">Tanggal dari</label><input id="ttFrom" type="date" class="form-control"></div>
        <div class="tt-finance-field"><label for="ttTo">Tanggal sampai</label><input id="ttTo" type="date" class="form-control"></div>
        <div class="tt-finance-field"><label for="ttStatus">Status</label><select id="ttStatus" class="form-select"><option value="">Semua status</option>@foreach ($statuses as $status)<option value="{{ $status }}">{{ $status }}</option>@endforeach</select></div>

        <div id="ttTypesField" class="tt-finance-field" style="grid-column:1 / -1">
            <label>Tipe transaksi yang diminta ke TikTok</label>
            <div class="tt-finance-types">
                @foreach ($transactionTypes as $type)
                    <label class="tt-finance-type"><input type="checkbox" class="form-check-input tt-type" value="{{ $type['code'] }}" checked> {{ $type['label'] }} <code>{{ $type['code'] }}</code></label>
                @endforeach
            </div>
            <small id="ttSourceHint" class="text-muted d-block mt-2">Pagination memakai cursor TikTok untuk transaksi level akun.</small>
        </div>

        <div class="tt-finance-actions">
            <small id="ttStorageStatus" class="tt-finance-storage-status">Sumber: database lokal · Belum ada sinkronisasi</small>
            <div class="d-flex gap-2">
                <button id="ttReset" type="button" class="btn btn-outline-secondary"><i class="bi bi-arrow-counterclockwise me-1"></i>Reset</button>
                <button id="ttExport" type="button" class="btn btn-outline-success"><i class="bi bi-file-earmark-spreadsheet me-1"></i>Export CSV</button>
                <button id="ttSync" type="button" class="btn btn-outline-primary"><i class="bi bi-arrow-repeat me-1"></i>Sync sekarang</button>
                <button id="ttLoad" type="button" class="btn btn-primary"><i class="bi bi-database me-1"></i>Muat Data Lokal</button>
            </div>
        </div>
    </section>

    <section class="tt-finance-kpis" aria-label="Ringkasan halaman">
        <div class="tt-finance-card tt-finance-kpi"><small>Total transaksi</small><strong id="ttCount">0</strong></div>
        <div class="tt-finance-card tt-finance-kpi in"><small>Dana masuk</small><strong id="ttIn">—</strong></div>
        <div class="tt-finance-card tt-finance-kpi out"><small>Dana keluar</small><strong id="ttOut">—</strong></div>
        <div class="tt-finance-card tt-finance-kpi"><small>Net terpetakan</small><strong id="ttNet">—</strong></div>
        <div class="tt-finance-card tt-finance-kpi"><small>Arah belum pasti</small><strong id="ttUnknown">0</strong></div>
    </section>

    <section class="tt-finance-card">
        <div id="ttTableWrap" class="tt-finance-table-wrap">
            <div class="tt-finance-empty"><i class="bi bi-database"></i>Pilih toko dan periode, lalu muat transaksi dari database lokal.</div>
        </div>
        <div class="tt-finance-pagination">
            <span id="ttPageInfo">Belum ada data</span>
            <div class="d-flex gap-2"><button id="ttPrev" type="button" class="btn btn-sm btn-outline-secondary" disabled>Awal</button><button id="ttNext" type="button" class="btn btn-sm btn-outline-primary" disabled>Berikutnya <i class="bi bi-arrow-right"></i></button></div>
        </div>
    </section>
</div>

<script>
(() => {
    const $ = (id) => document.getElementById(id);
    const state = { page: 1, lastPage: 1, hasMore: false, items: [], loading: false, syncing: false };
    const today = new Date();
    const iso = (date) => date.toISOString().slice(0, 10);
    const from = new Date(today); from.setDate(from.getDate() - 14);
    $('ttFrom').value = iso(from); $('ttTo').value = iso(today);

    const escapeHtml = (value) => String(value ?? '').replace(/[&<>'"]/g, (char) => ({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#039;','"':'&quot;'}[char]));
    const selectedTypes = () => [...document.querySelectorAll('.tt-type:checked')].map((input) => input.value);
    const money = (value, currency = 'IDR') => value === null || value === undefined || Number.isNaN(Number(value)) ? '—' : `${currency} ${Number(value).toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
    const showMessage = (message, success = false) => { $('ttAlert').textContent = message; $('ttAlert').classList.toggle('success', success); $('ttAlert').classList.add('show'); };
    const showError = (message) => showMessage(message, false);
    const clearError = () => { $('ttAlert').textContent = ''; $('ttAlert').classList.remove('show', 'success'); };

    const validate = () => {
        if (!$('ttStore').value) return 'Belum ada toko TikTok Shop yang aktif.';
        if (!$('ttFrom').value || !$('ttTo').value) return 'Tanggal wajib diisi.';
        if ($('ttFrom').value > $('ttTo').value) return 'Tanggal mulai tidak boleh melewati tanggal akhir.';
        const days = Math.round((new Date(`${$('ttTo').value}T00:00:00`) - new Date(`${$('ttFrom').value}T00:00:00`)) / 86400000) + 1;
        if (days > 60) return `Rentang ${days} hari terlalu panjang. Maksimal 60 hari.`;
        if ($('ttSource').value === 'account' && !selectedTypes().length) return 'Pilih minimal satu tipe transaksi.';
        return null;
    };

    const buildUrl = (exportMode = false) => {
        const path = `/api/marketplace/stores/${encodeURIComponent($('ttStore').value)}/tiktok-financial-transactions${exportMode ? '/export' : ''}`;
        const query = new URLSearchParams({ date_from: $('ttFrom').value, date_to: $('ttTo').value, source: $('ttSource').value, types: selectedTypes().join(',') });
        if ($('ttStatus').value) query.set('status', $('ttStatus').value);
        if (!exportMode) { query.set('page', String(state.page)); query.set('page_size', '100'); }
        return `${path}?${query.toString()}`;
    };

    const render = (items) => {
        if (!items.length) {
            $('ttTableWrap').innerHTML = '<div class="tt-finance-empty"><i class="bi bi-inbox"></i>Tidak ada transaksi pada filter ini.</div>';
            return;
        }
        $('ttTableWrap').innerHTML = `<table class="tt-finance-table"><thead><tr><th>Sumber</th><th>Waktu</th><th>ID</th><th>Statement / Order</th><th>Tipe</th><th>Arah</th><th>Nominal</th><th>Status</th><th>Detail</th></tr></thead><tbody>${items.map((item) => {
            const flowClass = item.money_flow === 'MONEY_IN' ? 'tt-finance-flow-in' : (item.money_flow === 'MONEY_OUT' ? 'tt-finance-flow-out' : 'tt-finance-flow-unknown');
            const flowLabel = item.money_flow === 'MONEY_IN' ? 'Dana masuk' : (item.money_flow === 'MONEY_OUT' ? 'Dana keluar' : 'Belum ditentukan');
            const sourceLabel = item.source === 'statement_transactions' ? 'Statement' : 'Akun';
            const statementOrder = [item.statement_id ? `S: ${escapeHtml(item.statement_id)}` : '', item.order_id ? `O: ${escapeHtml(item.order_id)}` : ''].filter(Boolean).join('<br>') || '—';
            const detailAmounts = item.source === 'statement_transactions' && item.settlement_amount !== null && item.settlement_amount !== undefined
                ? `<small class="d-block text-muted">Settlement: ${escapeHtml(money(item.settlement_amount, item.currency))}</small>` : '';
            return `<tr><td><span class="tt-finance-pill ${item.source === 'statement_transactions' ? 'success' : 'primary'}">${sourceLabel}</span></td><td>${escapeHtml(item.date_formatted)}</td><td><code>${escapeHtml(item.transaction_id)}</code></td><td><code>${statementOrder}</code></td><td><span class="tt-finance-pill ${escapeHtml(item.badge || 'secondary')}">${escapeHtml(item.transaction_type_label)}<br><small>${escapeHtml(item.transaction_type)}</small></span></td><td class="${flowClass}">${flowLabel}</td><td class="${flowClass}">${escapeHtml(item.signed_formatted_amount || item.formatted_amount)}${detailAmounts}</td><td><span class="tt-finance-pill secondary">${escapeHtml(item.status)}</span></td><td>${escapeHtml(item.reason || 'Tidak ada deskripsi')}<details><summary>Raw JSON</summary><pre class="tt-finance-raw">${escapeHtml(JSON.stringify(item.raw_payload || item, null, 2))}</pre></details></td></tr>`;
        }).join('')}</tbody></table>`;
    };

    const load = async () => {
        clearError();
        const error = validate();
        if (error) { showError(error); return; }
        state.loading = true; $('ttLoad').disabled = true; $('ttNext').disabled = true;
        $('ttTableWrap').innerHTML = '<div class="tt-finance-empty"><i class="bi bi-database"></i>Membaca database lokal…</div>';
        try {
            const response = await fetch(buildUrl(), { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
            const json = await response.json().catch(() => ({}));
            if (!response.ok || json.success === false) throw new Error(json.message || `Gagal mengambil data (${response.status})`);
            const data = json.data || {}; const summary = data.summary || {}; const pagination = data.pagination || {};
            state.items = data.items || []; state.lastPage = Number(pagination.last_page || 1); state.hasMore = Boolean(pagination.has_more);
            $('ttCount').textContent = Number(summary.total_count || 0).toLocaleString('id-ID');
            $('ttIn').textContent = money(summary.total_money_in); $('ttOut').textContent = money(summary.total_money_out); $('ttNet').textContent = money(summary.net_amount); $('ttUnknown').textContent = Number(summary.unknown_flow_count || 0).toLocaleString('id-ID');
            render(state.items); $('ttNext').disabled = !state.hasMore; $('ttPrev').disabled = state.page <= 1; $('ttPageInfo').textContent = `Halaman ${state.page} dari ${state.lastPage} · ${state.items.length} baris dari ${Number(pagination.total || summary.total_count || 0).toLocaleString('id-ID')} transaksi`;
            $('ttStorageStatus').textContent = data.last_synced_at ? `Sumber: database lokal · Sync terakhir ${new Date(data.last_synced_at).toLocaleString('id-ID')}` : 'Sumber: database lokal · Belum ada sinkronisasi untuk periode ini';
        } catch (error) {
            showError(error.message); $('ttTableWrap').innerHTML = `<div class="tt-finance-empty text-danger"><i class="bi bi-exclamation-triangle"></i>${escapeHtml(error.message)}</div>`; $('ttPageInfo').textContent = 'Gagal membaca database lokal';
        } finally { state.loading = false; $('ttLoad').disabled = false; }
    };

    const syncStatusUrl = () => `/api/marketplace/stores/${encodeURIComponent($('ttStore').value)}/tiktok-financial-transactions/sync-status`;
    const pollSync = async () => {
        for (let attempt = 0; attempt < 40; attempt++) {
            await new Promise((resolve) => setTimeout(resolve, 3000));
            try {
                const response = await fetch(syncStatusUrl(), { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
                const status = await response.json();
                if (status.label) $('ttStorageStatus').textContent = status.label;
                if (status.status === 'success') { showMessage('Sinkronisasi selesai. Data lokal diperbarui.', true); await load(); return; }
                if (status.status === 'error') { showError(status.message || status.label || 'Sinkronisasi gagal.'); return; }
            } catch (error) { /* polling berikutnya mencoba lagi */ }
        }
        showMessage('Sinkronisasi masih berjalan di background. Muat ulang beberapa saat lagi.', true);
    };

    const syncNow = async () => {
        clearError();
        const error = validate();
        if (error) { showError(error); return; }
        state.syncing = true; $('ttSync').disabled = true; $('ttSync').innerHTML = '<i class="bi bi-hourglass-split me-1"></i>Masuk antrean…';
        try {
            const response = await fetch(`/api/marketplace/stores/${encodeURIComponent($('ttStore').value)}/tiktok-financial-transactions/sync`, {
                method: 'POST',
                headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' },
                body: JSON.stringify({ date_from: $('ttFrom').value, date_to: $('ttTo').value, source: $('ttSource').value }),
            });
            const json = await response.json().catch(() => ({}));
            if (!response.ok || json.success === false) throw new Error(json.message || `Gagal memasukkan sync (${response.status})`);
            showMessage('Sinkronisasi masuk antrean. Halaman akan membaca hasil terbaru setelah selesai.', true);
            pollSync();
        } catch (error) { showError(error.message); }
        finally { state.syncing = false; $('ttSync').disabled = false; $('ttSync').innerHTML = '<i class="bi bi-arrow-repeat me-1"></i>Sync sekarang'; }
    };

    const reset = () => { state.page = 1; state.lastPage = 1; state.hasMore = false; state.items = []; $('ttStatus').value = ''; document.querySelectorAll('.tt-type').forEach((input) => { input.checked = true; }); load(); };
    $('ttLoad').addEventListener('click', () => { state.page = 1; load(); });
    $('ttSync').addEventListener('click', syncNow);
    $('ttNext').addEventListener('click', () => { if (!state.loading && state.hasMore) { state.page++; load(); } });
    $('ttPrev').addEventListener('click', () => { if (!state.loading && state.page > 1) { state.page--; load(); } });
    $('ttReset').addEventListener('click', reset);
    $('ttExport').addEventListener('click', () => { const error = validate(); if (error) { showError(error); return; } window.location.href = buildUrl(true); });
    const updateSourceControls = () => {
        const accountSource = $('ttSource').value === 'account';
        $('ttTypesField').style.opacity = accountSource ? '1' : '.62';
        document.querySelectorAll('.tt-type').forEach((input) => { input.disabled = !accountSource; });
        $('ttSourceHint').textContent = accountSource
            ? 'Data ditampilkan dari tabel lokal; Sync sekarang mengambil mutasi akun dari TikTok.'
            : 'Data ditampilkan dari tabel lokal; Sync sekarang mengambil statement final dari TikTok.';
    };
    ['ttStore', 'ttFrom', 'ttTo', 'ttStatus', 'ttSource'].forEach((id) => $(id).addEventListener('change', () => { state.page = 1; if (id === 'ttSource') updateSourceControls(); }));
    updateSourceControls();
    if ($('ttStore').value) load();
})();
</script>
@endsection
