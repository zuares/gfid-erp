@extends('layouts.app')

@section('title', 'Toko Marketplace')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex align-items-start justify-content-between gap-3 mb-4 flex-wrap">
        <div>
            <h1 class="h3 fw-bold mb-1">Toko Marketplace</h1>
            <div class="text-muted">
                Kelola toko marketplace, login channel, sync order, dan pantau data penjualan.
            </div>
        </div>

        <div class="d-flex gap-2 flex-wrap">
            <a href="/owner/omnichannel/shopee/connect" class="btn btn-dark">
                Login Shopee
            </a>

            <button class="btn btn-outline-secondary" type="button" onclick="bootstrapChannels()">
                Buat Channel Default
            </button>

            <button class="btn btn-outline-secondary" type="button" onclick="loadAll()">
                Refresh
            </button>
        </div>
    </div>

    <div id="alertBox" class="alert d-none"></div>

    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="text-muted small">Channel</div>
                    <div class="h3 fw-bold mb-0" id="statChannels">0</div>
                </div>
            </div>
        </div>

        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="text-muted small">Toko</div>
                    <div class="h3 fw-bold mb-0" id="statStores">0</div>
                </div>
            </div>
        </div>

        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="text-muted small">Order Lokal</div>
                    <div class="h3 fw-bold mb-0" id="statOrders">0</div>
                </div>
            </div>
        </div>

        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="text-muted small">Last Sync</div>
                    <div class="h5 fw-bold mb-0" id="statSync">-</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white">
            <div class="fw-bold">Daftar Toko</div>
            <div class="text-muted small">Pilih toko lalu cek info atau sync order hari ini.</div>
        </div>

        <div class="card-body">
            <div id="storeEmpty" class="text-center text-muted py-5 d-none">
                Belum ada toko yang terhubung.
                <div class="mt-3">
                    <a href="/owner/omnichannel/shopee/connect" class="btn btn-dark">
                        Login Shopee
                    </a>
                </div>
            </div>

            <div class="table-responsive" id="storeTableWrap">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th>Toko</th>
                            <th>Channel</th>
                            <th>Shop ID</th>
                            <th>Status</th>
                            <th>Last Sync</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="storeTable"></tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white d-flex align-items-center justify-content-between gap-2">
            <div>
                <div class="fw-bold">Order Tersimpan Lokal</div>
                <div class="text-muted small">Order yang sudah disync ke database.</div>
            </div>

            <button class="btn btn-outline-secondary btn-sm" type="button" onclick="loadAll()">
                Refresh Order
            </button>
        </div>

        <div class="card-body">
            <div id="orderEmpty" class="text-center text-muted py-5 d-none">
                Belum ada order tersimpan.
            </div>

            <div class="table-responsive" id="orderTableWrap">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th>Order</th>
                            <th>Toko</th>
                            <th>Status</th>
                            <th>Item</th>
                            <th class="text-end">Total</th>
                        </tr>
                    </thead>
                    <tbody id="orderTable"></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="jsonModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title fw-bold" id="jsonTitle">Response</h5>
                    <div class="text-muted small" id="jsonSubtitle">API response</div>
                </div>
                <button class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <pre class="bg-light rounded p-3 small mb-0" id="jsonOutput" style="max-height:70vh;overflow:auto;"></pre>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
let channels = [];
let stores = [];
let orders = [];

function showAlert(message, type = 'success') {
    const box = document.getElementById('alertBox');
    box.className = `alert alert-${type}`;
    box.textContent = message;
    box.classList.remove('d-none');
    setTimeout(() => box.classList.add('d-none'), 6000);
}

async function api(url, options = {}) {
    const response = await fetch(url, {
        headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            ...(options.headers || {})
        },
        ...options
    });

    let payload = null;

    try {
        payload = await response.json();
    } catch (error) {
        payload = { message: 'Response bukan JSON valid.' };
    }

    if (!response.ok) {
        throw new Error(payload.message || payload.error || 'API error');
    }

    return payload;
}

async function bootstrapChannels() {
    const data = await api('/api/omnichannel/bootstrap', { method: 'POST' });
    showAlert(data.message || 'Channel default berhasil dibuat.');
    await loadAll();
}

async function loadAll() {
    const [channelsResult, storesResult, ordersResult] = await Promise.allSettled([
        api('/api/omnichannel/channels'),
        api('/api/omnichannel/stores'),
        api('/api/omnichannel/local-orders')
    ]);

    channels = channelsResult.status === 'fulfilled' ? channelsResult.value : [];
    stores = storesResult.status === 'fulfilled' ? storesResult.value : [];
    orders = ordersResult.status === 'fulfilled' ? ordersResult.value : [];

    renderStores();
    renderOrders();
    renderStats();
}

function renderStats() {
    document.getElementById('statChannels').textContent = channels.length;
    document.getElementById('statStores').textContent = stores.length;
    document.getElementById('statOrders').textContent = orders.length;

    const last = stores
        .filter(store => store.last_synced_at)
        .sort((a, b) => new Date(b.last_synced_at) - new Date(a.last_synced_at))[0];

    document.getElementById('statSync').textContent = last?.last_synced_at
        ? formatShortDate(last.last_synced_at)
        : '-';
}

function renderStores() {
    const tbody = document.getElementById('storeTable');
    const empty = document.getElementById('storeEmpty');
    const wrap = document.getElementById('storeTableWrap');

    if (!stores.length) {
        tbody.innerHTML = '';
        empty.classList.remove('d-none');
        wrap.classList.add('d-none');
        return;
    }

    empty.classList.add('d-none');
    wrap.classList.remove('d-none');

    tbody.innerHTML = stores.map(store => `
        <tr>
            <td>
                <div class="fw-bold">${escapeHtml(store.name || '-')}</div>
                <div class="text-muted small">${escapeHtml(store.region || 'ID')}</div>
            </td>
            <td>${escapeHtml(store.channel?.name || '-')}</td>
            <td>${escapeHtml(store.external_shop_id || '-')}</td>
            <td><span class="badge bg-success">${escapeHtml(store.status || '-')}</span></td>
            <td>${store.last_synced_at ? formatDateTime(store.last_synced_at) : '-'}</td>
            <td class="text-end">
                <div class="d-flex gap-2 justify-content-end flex-wrap">
                    <button class="btn btn-outline-secondary btn-sm" type="button" onclick="checkStore(${store.id})">
                        Cek
                    </button>
                    <button class="btn btn-dark btn-sm" type="button" onclick="syncToday(${store.id})">
                        Sync Hari Ini
                    </button>
                </div>
            </td>
        </tr>
    `).join('');
}

function renderOrders() {
    const tbody = document.getElementById('orderTable');
    const empty = document.getElementById('orderEmpty');
    const wrap = document.getElementById('orderTableWrap');

    if (!orders.length) {
        tbody.innerHTML = '';
        empty.classList.remove('d-none');
        wrap.classList.add('d-none');
        return;
    }

    empty.classList.add('d-none');
    wrap.classList.remove('d-none');

    tbody.innerHTML = orders.map(order => {
        const items = order.items || [];

        return `
            <tr>
                <td>
                    <div class="fw-bold">${escapeHtml(order.channel_order_id || '-')}</div>
                    <div class="text-muted small">${order.ordered_at ? formatDateTime(order.ordered_at) : '-'}</div>
                </td>
                <td>
                    <div class="fw-semibold">${escapeHtml(order.store?.name || '-')}</div>
                    <div class="text-muted small">${escapeHtml(order.store?.channel?.name || '-')}</div>
                </td>
                <td><span class="badge bg-warning text-dark">${escapeHtml(order.order_status || '-')}</span></td>
                <td>
                    ${items.length ? items.map(item => `
                        <div class="small mb-1">
                            <strong>${escapeHtml(item.model_sku || item.item_sku || '-')}</strong>
                            · ${escapeHtml(item.variant_name || '-')}
                            · ${Number(item.qty || 0)} pcs
                        </div>
                    `).join('') : '<span class="text-muted small">Tidak ada item</span>'}
                </td>
                <td class="text-end fw-bold">${formatRupiah(order.total_amount || 0)}</td>
            </tr>
        `;
    }).join('');
}

async function checkStore(storeId) {
    try {
        showAlert('Mengecek toko...', 'warning');
        const data = await api(`/api/omnichannel/stores/${storeId}/shop-info`);
        showJson('Info Toko', `Store ID: ${storeId}`, data);
    } catch (error) {
        showAlert(error.message, 'danger');
    }
}

async function syncToday(storeId) {
    const now = new Date();
    const start = new Date(now.getFullYear(), now.getMonth(), now.getDate(), 0, 0, 0);
    const end = new Date(now.getFullYear(), now.getMonth(), now.getDate(), 23, 59, 59);

    try {
        showAlert('Sync order hari ini sedang diproses...', 'warning');

        const data = await api(`/api/omnichannel/stores/${storeId}/sync-orders`, {
            method: 'POST',
            body: JSON.stringify({
                time_from: Math.floor(start.getTime() / 1000),
                time_to: Math.floor(end.getTime() / 1000),
                page_size: 50,
            })
        });

        showAlert(data.message || 'Sync selesai.');
        await loadAll();
    } catch (error) {
        showAlert(error.message, 'danger');
    }
}

function showJson(title, subtitle, data) {
    document.getElementById('jsonTitle').textContent = title;
    document.getElementById('jsonSubtitle').textContent = subtitle;
    document.getElementById('jsonOutput').textContent = JSON.stringify(data, null, 2);

    new bootstrap.Modal(document.getElementById('jsonModal')).show();
}

function formatRupiah(value) {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0
    }).format(value || 0);
}

function formatDateTime(value) {
    return new Date(value).toLocaleString('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

function formatShortDate(value) {
    return new Date(value).toLocaleDateString('id-ID', {
        day: '2-digit',
        month: 'short'
    });
}

function escapeHtml(value) {
    const div = document.createElement('div');
    div.innerText = value ?? '';
    return div.innerHTML;
}

const urlParams = new URLSearchParams(window.location.search);

if (urlParams.get('connected')) {
    setTimeout(() => {
        showAlert('Login Shopee berhasil. Toko otomatis tersimpan.');
        window.history.replaceState({}, document.title, '/owner/omnichannel');
    }, 500);
}

loadAll().catch(error => {
    console.error(error);
    showAlert(error.message, 'danger');
});
</script>
@endpush
