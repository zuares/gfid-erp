@extends('layouts.app')

@section('title', 'Marketplace • Retur')

@push('head')
<style>
    :root{
        --shp-accent:#334155;
        --shp-accent-2:#1f2937;
        --shp-border:rgba(148,163,184,.18);
        --shp-border-strong:rgba(148,163,184,.30);
        --shp-muted:#64748b;
    }
    .page-wrap{ max-width:1040px; margin-inline:auto; padding:.75rem .75rem 4rem; background:transparent!important; }

    .card-main{
        background: var(--card);
        border-radius: 8px;
        border: 1px solid var(--shp-border);
        box-shadow: none;
        overflow:hidden;
    }
    body[data-theme="dark"] .card-main{
        border-color: rgba(51,65,85,.85);
        box-shadow: none;
    }

    .ship-topbar{
        position:sticky;
        top:0;
        z-index:300;
        display:flex;
        justify-content:space-between;
        align-items:center;
        gap:.6rem;
        flex-wrap:wrap;
        padding:.45rem .75rem;
        margin-inline:-.75rem;
        margin-bottom:.65rem;
        background: rgba(255,255,255,.8);
        backdrop-filter: blur(8px);
        border-bottom: 1px solid var(--shp-border);
    }
    body[data-theme="dark"] .ship-topbar{
        background: rgba(15,23,42,.8);
    }
    .ship-title { font-size:1.15rem; font-weight:700; color:var(--shp-accent-2); margin:0; letter-spacing:-.3px; }
    body[data-theme="dark"] .ship-title { color:#f1f5f9; }
    
    .ret-table { width: 100%; border-collapse: separate; border-spacing: 0; }
    .ret-table thead th {
        font-size: .75rem; font-weight: 600; color: var(--shp-muted); text-transform: uppercase;
        padding: .6rem .75rem; border-bottom: 1px solid var(--shp-border); background: #f8fafc;
    }
    body[data-theme="dark"] .ret-table thead th { background: #0f172a; border-color: rgba(51,65,85,.85); }
    .ret-table tbody td {
        padding: .75rem; border-bottom: 1px solid var(--shp-border); vertical-align: middle; font-size: .85rem;
    }
    .ret-table tbody tr:hover td { background: #f1f5f9; }
    body[data-theme="dark"] .ret-table tbody tr:hover td { background: #1e293b; }
</style>
@endpush

@section('content')
<div class="page-wrap">
    <div class="ship-topbar">
        <h1 class="ship-title">Retur Marketplace</h1>
        <div class="d-flex gap-2 align-items-center">
            <div class="input-group input-group-sm bg-light rounded-2 border-0" style="min-width: 220px; overflow: hidden;">
                <span class="input-group-text bg-transparent border-0 text-muted ps-3 pe-2"><i class="bi bi-calendar3"></i></span>
                <input type="text" id="dateRange" class="form-control border-0 bg-transparent shadow-none px-1 fw-medium" placeholder="Pilih Tanggal..." readonly style="font-size: 0.85rem; cursor: pointer;">
            </div>
            
            <select id="storeSelect" class="form-select form-select-sm border-0 bg-light" style="min-width: 180px; font-weight:500;">
                <option value="">Semua Toko...</option>
                @foreach($stores as $store)
                    <option value="{{ $store->id }}">{{ $store->name }}</option>
                @endforeach
            </select>
            <button class="btn btn-sm btn-dark" id="btnRefresh" onclick="fetchReturns()" disabled>
                <i class="bi bi-arrow-clockwise"></i>
            </button>
        </div>
    </div>

    <div class="card-main mb-4">
        <div class="table-responsive">
            <table class="ret-table" id="returnsTable">
                <thead>
                    <tr>
                        <th class="ps-4">Tanggal</th>
                        <th>Return & Order SN</th>
                        <th>Barang</th>
                        <th>Tipe</th>
                        <th>Alasan Retur</th>
                        <th>Status</th>
                        <th>Pengembalian</th>
                        <th class="text-center pe-4">Aksi</th>
                    </tr>
                </thead>
                <tbody id="returnsBody">
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">Silakan pilih toko terlebih dahulu.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Konfirmasi -->
<div class="modal fade" id="confirmModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title fw-bold">Terima & Masukkan Gudang</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Anda akan mengkonfirmasi penerimaan paket fisik untuk Retur <strong><span id="c_returnSn" class="text-primary"></span></strong>.</p>
                <div class="alert alert-warning d-flex align-items-center" role="alert">
                    <i class="bi bi-exclamation-triangle-fill fs-4 me-3"></i>
                    <div>
                        Tindakan ini akan <strong>menyetujui retur di Shopee</strong> dan uang akan dikembalikan ke pembeli. Sekaligus, sistem akan membuat <strong>Draf Dokumen Retur</strong> di ERP Anda.
                    </div>
                </div>
                <p class="mb-0 text-muted small">Pastikan barang fisik sudah Anda periksa dan sesuai dengan klaim pembeli.</p>
            </div>
            <div class="modal-footer border-top-0 pt-0">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-success fw-bold" id="btnConfirmSubmit">Ya, Terima & Restock</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Tracking -->
<div class="modal fade" id="trackingModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title fw-bold">Status Pengiriman Retur</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="trackingModalBody">
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status"></div>
                    <div class="mt-2 text-muted">Memuat data pelacakan...</div>
                </div>
            </div>
        </div>
    </div>
</div>
</div> <!-- End container-fluid -->

@endsection

@push('scripts')
<script>
    const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    // MP Helpers dummy implementation if not globally defined
    const api = async (url, options = {}) => {
        options.headers = options.headers || {};
        options.headers['Accept'] = 'application/json';
        if (!options.headers['Content-Type'] && !(options.body instanceof FormData)) {
            options.headers['Content-Type'] = 'application/json';
        }
        if (options.method && options.method !== 'GET') {
            options.headers['X-CSRF-TOKEN'] = token;
        }
        const res = await fetch(url, options);
        let data;
        try { data = await res.json(); } catch(e) {}
        if (!res.ok) throw new Error(data?.message || data?.error || res.statusText);
        return data;
    };

    let selectedStoreId = '';
    let currentReturnSn = '';
    let currentPage = 0;
    let isLoading = false;
    let allReturnsData = [];

    const storeSelect = document.getElementById('storeSelect');
    const btnRefresh = document.getElementById('btnRefresh');
    const tbody = document.getElementById('returnsBody');
    const dateRangeEl = document.getElementById('dateRange');

    // Init Flatpickr untuk rentang 15 hari ke belakang (inklusif = -14 hari kalender)
    const today = new Date();
    const past15 = new Date();
    past15.setDate(today.getDate() - 14);

    let fp;
    if (window.flatpickr) {
        fp = flatpickr(dateRangeEl, {
            mode: "range",
            dateFormat: "Y-m-d",
            defaultDate: [past15, today],
            maxDate: "today",
            onChange: function(selectedDates) {
                if (selectedDates.length === 2) {
                    const diffTime = Math.abs(selectedDates[1] - selectedDates[0]);
                    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
                    if (diffDays > 15) {
                        alert("Rentang waktu maksimal untuk API Shopee adalah 15 hari.");
                        fp.clear();
                        return;
                    }
                    currentPage = 0;
                    fetchReturns(true);
                }
            }
        });
    }

    storeSelect.addEventListener('change', function() {
        selectedStoreId = this.value;
        currentPage = 0;
        fetchReturns(true, false);
    });

    btnRefresh.addEventListener('click', function() {
        currentPage = 0;
        fetchReturns(true, true);
    });

    async function fetchReturns(reset = false, shouldSync = false) {
        if (isLoading) return;
        isLoading = true;
        btnRefresh.disabled = true;        

        if (reset) {
            const syncText = shouldSync ? 'Menyinkronkan data terbaru dengan Marketplace...' : 'Memuat data dari database...';
            tbody.innerHTML = `<tr><td colspan="8" class="text-center py-5"><div class="spinner-border text-primary" role="status"></div><div class="mt-2 text-muted">${syncText}</div></td></tr>`;
            allReturnsData = [];
        }

        try {
            let tsFrom, tsTo;
            if (fp && fp.selectedDates && fp.selectedDates.length === 2) {
                tsFrom = Math.floor(fp.selectedDates[0].getTime() / 1000);
                tsTo = Math.floor(fp.selectedDates[1].getTime() / 1000) + 86399;
            } else {
                // Fallback default jika flatpickr belum me-render atau kosong
                const t = new Date();
                const p = new Date();
                p.setDate(t.getDate() - 14);
                tsFrom = Math.floor(p.getTime() / 1000);
                tsTo = Math.floor(t.getTime() / 1000) + 86399;
            }
            
            if (tsTo - tsFrom > 16 * 86400) {
                alert("Rentang waktu maksimal untuk API Shopee adalah 15 hari.");
                isLoading = false; btnRefresh.disabled = false;
                return;
            }

            let storesToFetch = [];
            if (selectedStoreId) {
                storesToFetch.push(selectedStoreId);
            } else {
                Array.from(storeSelect.options).forEach(opt => {
                    if (opt.value) storesToFetch.push(opt.value);
                });
            }

            // Lakukan sinkronisasi jika diminta (misal tombol refresh ditekan)
            if (shouldSync) {
                const syncPromises = storesToFetch.map(sId => 
                    api(`/api/marketplace/stores/${sId}/returns/sync?create_time_from=${tsFrom}&create_time_to=${tsTo}`, { method: 'POST' })
                    .catch(e => console.warn('Gagal sync toko', sId, e))
                );
                await Promise.all(syncPromises);
                
                // Update teks loading
                if (reset) {
                    tbody.innerHTML = `<tr><td colspan="8" class="text-center py-5"><div class="spinner-border text-primary" role="status"></div><div class="mt-2 text-muted">Menampilkan data retur...</div></td></tr>`;
                }
            }

            let newReturns = [];
            let hasMore = false;

            const promises = storesToFetch.map(sId => 
                api(`/api/marketplace/stores/${sId}/returns/list?page_no=${currentPage}&page_size=40&create_time_from=${tsFrom}&create_time_to=${tsTo}`)
                .then(res => {
                    if (res && res.return) {
                        res.return.forEach(r => r.store_id = sId);
                    }
                    return res;
                })
                .catch(e => { console.warn('Gagal fetch data lokal', sId, e); return null; })
            );

            const results = await Promise.all(promises);
            results.forEach(res => {
                if (res && res.return) {
                    newReturns = newReturns.concat(res.return);
                    if (res.more) hasMore = true;
                }
            });

            allReturnsData = reset ? newReturns : allReturnsData.concat(newReturns);
            allReturnsData.sort((a, b) => b.create_time - a.create_time);

            renderTable(hasMore);

        } catch (e) {
            if (reset) {
                tbody.innerHTML = `<tr><td colspan="8" class="text-center py-5 text-danger"><i class="bi bi-exclamation-triangle fs-3 d-block mb-2"></i>Gagal mengambil data retur: ${e.message}</td></tr>`;
            } else {
                alert('Gagal mengambil data lebih lanjut: ' + e.message);
            }
        } finally {
            isLoading = false;
            btnRefresh.disabled = false;
        }
    }

    function renderTable(hasMore) {
        if (allReturnsData.length === 0) {
            tbody.innerHTML = '<tr><td colspan="8" class="text-center py-5 text-muted">Saat ini tidak ada data retur. Semua aman! 🎉</td></tr>';
            return;
        }

        tbody.innerHTML = '';
        allReturnsData.forEach(r => {
            let typeBadge = '';
            if (r.return_solution === 1) {
                typeBadge = '<span class="badge bg-danger text-white border"><i class="bi bi-cash me-1"></i>Refund Saja</span>';
            } else {
                typeBadge = '<span class="badge bg-primary text-white border"><i class="bi bi-box-seam me-1"></i>Retur & Refund</span>';
            }
            
            let itemsHtml = '';
            if (r.item && r.item.length > 0) {
                itemsHtml = '<div class="d-flex flex-column gap-1">';
                r.item.forEach(itm => {
                    const sku = itm.item_sku || itm.variation_sku || '';
                    const skuBadge = sku ? `<span class="badge bg-light text-dark border me-1">${sku}</span>` : '';
                    itemsHtml += `<div class="d-flex align-items-center gap-2">
                        <img src="${itm.images && itm.images.length ? itm.images[0] : ''}" style="width:30px; height:30px; object-fit:cover; border-radius:4px;" onerror="this.style.display='none'">
                        <div>
                            <div class="small fw-semibold text-wrap" style="max-width:200px; line-height: 1.3;">${itm.internal_name || itm.variation_name || itm.item_name || 'Item'}</div>
                            <div class="small text-muted mt-1">${skuBadge} Qty: ${itm.return_item_quantity || 1}</div>
                        </div>
                    </div>`;
                });
                itemsHtml += '</div>';
            }

            let cleanStatus = '';
            if (r.status === 'COMPLETED' || r.status === 'REFUND_PAID') {
                const recvDate = r.update_time ? new Date(r.update_time * 1000).toLocaleString('id-ID', {day:'numeric', month:'short', year:'numeric'}) : '';
                cleanStatus = `<div class="badge bg-success-subtle text-success border border-success-subtle mb-1 d-block"><i class="bi bi-check-circle me-1"></i>Selesai / Diterima</div><div class="small text-muted text-center">${recvDate}</div>`;
            } else if (r.status === 'WAITING_SELLER_RECEIVE' || r.status === 'SELLER_DISPUTE') {
                cleanStatus = `<span class="badge bg-info-subtle text-info-emphasis border border-info-subtle"><i class="bi bi-truck me-1"></i>Masih Di Jalan</span>`;
            } else if (r.status === 'CANCELLED' || r.status === 'CLOSED') {
                cleanStatus = `<span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle"><i class="bi bi-x-circle me-1"></i>Dibatalkan</span>`;
            } else {
                cleanStatus = `<span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle"><i class="bi bi-hourglass-split me-1"></i>Proses Shopee</span>`;
            }

            let btnHtml = '';
            if (r.status === 'WAITING_SELLER_RECEIVE') {
                btnHtml = `<button class="btn btn-sm btn-success fw-bold shadow-sm d-block w-100 mb-1" onclick="openConfirmModal('${r.return_sn}', '${r.store_id}')"><i class="bi bi-check2-all me-1"></i>Terima & Restock</button>`;
            } else {
                btnHtml = `<button class="btn btn-sm btn-light text-muted border d-block w-100 mb-1" disabled>Menunggu...</button>`;
            }
            
            if (r.needs_logistics && r.tracking_number) {
                btnHtml += `<button class="btn btn-sm btn-outline-info d-block w-100" onclick="trackReturn('${r.return_sn}', '${r.store_id}')"><i class="bi bi-truck me-1"></i>Lacak: ${r.tracking_number}</button>`;
            }

            const createDate = r.create_time ? new Date(r.create_time * 1000).toLocaleString('id-ID', {
                day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit'
            }) : '—';

            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td class="ps-4 text-muted small"><i class="bi bi-calendar3 me-1"></i>${createDate}</td>
                <td>
                    <div class="fw-semibold text-primary mb-1">${r.return_sn}</div>
                    <div class="small text-muted"><i class="bi bi-box-seam me-1"></i>${r.order_sn}</div>
                </td>
                <td>${itemsHtml}</td>
                <td>${typeBadge}</td>
                <td>${r.reason_text_code || r.reason || '—'}</td>
                <td class="text-center align-middle">${cleanStatus}</td>
                <td class="fw-bold">Rp ${Number(r.amount_before_discount || 0).toLocaleString('id-ID')}</td>
                <td class="text-center pe-4">${btnHtml}</td>
            `;
            tbody.appendChild(tr);
        });

        if (hasMore) {
            const trMore = document.createElement('tr');
            trMore.innerHTML = `
                <td colspan="8" class="text-center py-3 bg-light">
                    <button class="btn btn-sm btn-outline-primary fw-bold" onclick="loadMore()">Muat Lebih Banyak</button>
                </td>
            `;
            tbody.appendChild(trMore);
        }
    }

    window.loadMore = function() {
        currentPage += 40;
        fetchReturns(false);
    };

    // Load default
    btnRefresh.disabled = false;
    fetchReturns(true);

    let currentStoreId = '';

    window.openConfirmModal = function(returnSn, storeId) {
        currentReturnSn = returnSn;
        currentStoreId = storeId;
        document.getElementById('c_returnSn').textContent = returnSn;
        bootstrap.Modal.getOrCreateInstance(document.getElementById('confirmModal')).show();
    };

    document.getElementById('btnConfirmSubmit').addEventListener('click', async function() {
        const btn = this;
        const originalText = btn.innerHTML;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Memproses...';
        btn.disabled = true;

        try {
            const res = await api(`/api/marketplace/stores/${currentStoreId}/returns/${currentReturnSn}/confirm`, {
                method: 'POST'
            });

            alert(res.message || 'Sukses!');
            
            // Tutup modal
            bootstrap.Modal.getInstance(document.getElementById('confirmModal')).hide();
            
            // Pindah ke ERP Draft
            if (res.redirect_url) {
                window.location.href = res.redirect_url;
            } else {
                currentPage = 0;
                fetchReturns(true);
            }

        } catch (e) {
            alert('Gagal: ' + e.message);
        } finally {
            btn.innerHTML = originalText;
            btn.disabled = false;
        }
    });

    window.trackReturn = async function(returnSn, storeId) {
        const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('trackingModal'));
        const body = document.getElementById('trackingModalBody');
        
        body.innerHTML = `
            <div class="text-center py-4">
                <div class="spinner-border text-primary" role="status"></div>
                <div class="mt-2 text-muted">Memuat data pelacakan resi untuk retur ${returnSn}...</div>
            </div>
        `;
        modal.show();

        try {
            const data = await api(`/api/marketplace/stores/${storeId}/returns/${returnSn}/tracking`);
            
            if (!data || !data.tracking_info || data.tracking_info.length === 0) {
                body.innerHTML = `<div class="alert alert-warning border-0 shadow-sm d-flex align-items-center mb-0"><i class="bi bi-info-circle fs-4 me-3"></i> ${data.message || 'Belum ada data pelacakan yang tersedia dari logistik Shopee.'}</div>`;
                return;
            }

            let html = '<div class="timeline ps-3 border-start border-2 border-primary" style="margin-left: 10px;">';
            data.tracking_info.forEach(t => {
                const timeStr = new Date(t.update_time * 1000).toLocaleString('id-ID', {
                    day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit'
                });
                
                let imagesHtml = '';
                const imgs = [...(t.epop_image_list || []), ...(t.epod_image_list || [])];
                if (imgs.length > 0) {
                    imagesHtml = '<div class="mt-2 d-flex gap-2 flex-wrap">';
                    imgs.forEach(img => {
                        imagesHtml += `<a href="${img}" target="_blank"><img src="${img}" class="rounded border shadow-sm" style="width: 60px; height: 60px; object-fit: cover;"></a>`;
                    });
                    imagesHtml += '</div>';
                }

                html += `
                    <div class="position-relative mb-4">
                        <div class="position-absolute bg-primary rounded-circle" style="width: 12px; height: 12px; left: -23px; top: 5px;"></div>
                        <div class="fw-bold text-dark" style="font-size: 0.9rem;">${t.tracking_description || t.description || 'Status diperbarui'}</div>
                        <div class="text-muted" style="font-size: 0.8rem;"><i class="bi bi-clock me-1"></i>${timeStr}</div>
                        ${imagesHtml}
                    </div>
                `;
            });
            html += '</div>';

            body.innerHTML = html;
        } catch (e) {
            body.innerHTML = `<div class="alert alert-danger border-0 shadow-sm d-flex align-items-center mb-0"><i class="bi bi-exclamation-triangle fs-4 me-3"></i> Gagal memuat data: ${e.message}</div>`;
        }
    };

</script>
@endpush
