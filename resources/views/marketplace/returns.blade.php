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
        background:var(--card,#fff);
        border-bottom:1px solid var(--shp-border);
    }
    body[data-theme="dark"] .ship-topbar{ background:var(--card,#0f172a); }
    .title{ font-weight: 750; font-size:1rem; letter-spacing: 0; margin:0; }
    .sub{ color:var(--shp-muted); font-size:.78rem; }
    body[data-theme="dark"] .sub{ color:#9ca3af; }
    
    .controls{ display:flex; align-items:center; gap:.5rem; flex-wrap:wrap; }

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

    /* Modern Tab Toggle */
    .btn-check:checked + .btn {
        background-color: #ffffff !important;
        color: var(--shp-accent-2) !important;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }
    body[data-theme="dark"] .btn-check:checked + .btn {
        background-color: #334155 !important;
        color: #ffffff !important;
    }
</style>
@endpush

@section('content')
<div class="page-wrap">
    
    <div class="ship-topbar flex-column align-items-stretch gap-3">
        <!-- Baris Pertama: Title & Date Picker -->
        <div class="d-flex justify-content-between align-items-start w-100">
            <div>
                <div class="title">Retur Marketplace</div>
                <div class="sub">Kelola retur pembeli dan pesanan gagal kirim (RTS).</div>
            </div>
            
            <!-- Date Picker (Kanan Atas Sejajar dengan Title) -->
            <div class="input-group input-group-sm border bg-light rounded overflow-hidden shadow-sm" style="width: 220px; flex-shrink: 0;">
                <span class="input-group-text bg-transparent border-0 text-muted ps-2 pe-1"><i class="bi bi-calendar3"></i></span>
                <input type="text" id="dateRange" class="form-control border-0 bg-transparent shadow-none px-2 fw-medium" placeholder="Semua Waktu" readonly style="font-size: 0.85rem; cursor: pointer;">
            </div>
        </div>

        <!-- Baris Kedua: Controls (Filter & Action) -->
        <div class="controls justify-content-end w-100 mt-1">
            <!-- Tab Toggle -->
            <div class="btn-group bg-light rounded p-1 border shadow-sm" role="group">
                <input type="radio" class="btn-check" name="returnType" id="typeReturn" value="return" checked onchange="currentPage=0; fetchReturns(true, false)">
                <label class="btn btn-sm fw-semibold border-0 text-muted px-3 mb-0" for="typeReturn" style="border-radius:4px;">Retur</label>

                <input type="radio" class="btn-check" name="returnType" id="typeRts" value="rts" onchange="currentPage=0; fetchReturns(true, false)">
                <label class="btn btn-sm fw-semibold border-0 text-muted px-3 mb-0" for="typeRts" style="border-radius:4px;">RTS (Gagal)</label>
            </div>

            <!-- Store Select -->
            <select id="storeSelect" class="form-select form-select-sm border bg-light rounded fw-medium px-3 shadow-sm" style="width: 150px; outline:none; box-shadow:none;">
                <option value="">Pilih Toko...</option>
                @foreach($stores as $store)
                    <option value="{{ $store->id }}">{{ $store->name }}</option>
                @endforeach
            </select>

            <!-- Refresh & Last Sync -->
            <span id="lastSyncTime" class="d-none d-md-inline" style="font-size: 0.75rem; color: #6c757d; margin-right: 0.5rem; align-self: center; font-weight: 500;"></span>
            <button class="btn btn-sm btn-dark rounded shadow-sm" id="btnRefresh" onclick="fetchReturns()" disabled>
                <i class="bi bi-arrow-clockwise"></i>
            </button>
            
            <button class="btn btn-sm btn-outline-primary fw-medium rounded px-3 shadow-sm" id="btnSyncHistorical" onclick="triggerHistoricalBackfill()" disabled>
                <i class="bi bi-cloud-download me-1"></i> Tarik Histori
            </button>
        </div>
    </div>

    <!-- KPI Section -->
    <div class="row g-3 mb-3">
        <div class="col-md-3 col-6">
            <div class="card-main p-3 text-center h-100" style="background: var(--card);">
                <div class="text-muted small fw-bold text-uppercase mb-1">Total Data</div>
                <div class="fs-4 fw-bolder text-dark" id="kpiTotal">0</div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card-main p-3 text-center h-100" style="background: var(--card);">
                <div class="text-muted small fw-bold text-uppercase mb-1">Perlu Diproses</div>
                <div class="fs-4 fw-bolder text-warning" id="kpiPending">0</div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card-main p-3 text-center h-100" style="background: var(--card);">
                <div class="text-muted small fw-bold text-uppercase mb-1">Selesai</div>
                <div class="fs-4 fw-bolder text-success" id="kpiCompleted">0</div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card-main p-3 text-center h-100" style="background: var(--card);">
                <div class="text-muted small fw-bold text-uppercase mb-1">Total Nilai (Rp)</div>
                <div class="fs-5 fw-bolder text-primary mt-1" id="kpiValue">0</div>
            </div>
        </div>
    </div>

    <!-- Separate Search Section -->
    <div class="card-main mb-3" style="background: var(--card);">
        <div class="p-2">
            <div class="input-group input-group-lg border-0 bg-light rounded overflow-hidden">
                <span class="input-group-text bg-transparent border-0 text-muted ps-4 pe-2"><i class="bi bi-search fs-5"></i></span>
                <input type="text" id="searchInput" class="form-control border-0 bg-transparent shadow-none px-2 fw-bold" placeholder="Scan Barcode / Ketik Nomor Pesanan / Resi (AWB)..." autofocus autocomplete="off" style="font-size: 1.05rem; color: var(--shp-accent-2);">
            </div>
        </div>
    </div>

    <div class="card-main mb-4">
        <div class="table-responsive">
            <table class="ret-table" id="returnsTable">
                <thead>
                    <tr>
                        <th class="ps-4">Tanggal</th>
                        <th>Return & Order SN</th>
                        <th>Tipe</th>
                        <th>Alasan Retur</th>
                        <th>Status</th>
                        <th>Pengembalian</th>
                        <th class="text-center pe-4">Aksi</th>
                    </tr>
                </thead>
                <tbody id="returnsBody">
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">Silakan pilih toko terlebih dahulu.</td>
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

<!-- Modal Detail -->
<div class="modal fade" id="detailModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title fw-bold">Detail Retur & Refund</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="detailModalBody">
                <!-- Content injected via JS -->
            </div>
            <div class="modal-footer border-top-0 pt-0">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Tutup</button>
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
            onChange: function(selectedDates, dateStr, instance) {
                if (selectedDates.length === 2) {
                    currentPage = 0;
                    fetchReturns(true, false);
                }
            },
            onClose: function(selectedDates, dateStr, instance) {
                instance.set("maxDate", "today");
            }
        });
    }

    const btnSyncHistorical = document.getElementById('btnSyncHistorical');

    storeSelect.addEventListener('change', function() {
        selectedStoreId = this.value;
        btnSyncHistorical.disabled = !selectedStoreId;
        currentPage = 0;
        fetchReturns(true, false);
    });

    btnRefresh.addEventListener('click', function() {
        currentPage = 0;
        fetchReturns(true, true);
    });

    let searchTimeout = null;
    const searchInput = document.getElementById('searchInput');
    searchInput.addEventListener('keyup', function(e) {
        clearTimeout(searchTimeout);
        // Jika menekan enter, langsung fetch tanpa delay
        if (e.key === 'Enter') {
            currentPage = 0;
            fetchReturns(true, false);
        } else {
            // Debounce 500ms
            searchTimeout = setTimeout(() => {
                currentPage = 0;
                fetchReturns(true, false);
            }, 500);
        }
    });

    function triggerHistoricalBackfill() {
        if (!selectedStoreId) return alert('Pilih toko terlebih dahulu!');
        
        let year = prompt('Tarik histori mundur sampai tahun berapa? (Contoh: 2022)', '2022');
        if (!year) return;
        
        if (confirm(`Peringatan: Sistem akan menyedot histori Pesanan & Retur dari tahun ${year} hingga hari ini untuk toko yang dipilih.\n\nProses ini berjalan di latar belakang (Background Job) dan akan memakan waktu sesuai banyaknya pesanan Anda.\n\nLanjutkan?`)) {
            api(`/api/marketplace/stores/${selectedStoreId}/sync-historical`, {
                method: 'POST',
                body: JSON.stringify({ year: year })
            }).then(res => {
                alert(res.message || 'Tugas diletakkan di latar belakang!');
            }).catch(err => {
                alert('Gagal memicu mesin waktu: ' + err.message);
            });
        }
    }

    async function fetchReturns(reset = false, forceSync = false) {
        if (isLoading) return;
        isLoading = true;
        btnRefresh.disabled = true;        

        if (reset) {
            tbody.innerHTML = `<tr><td colspan="7" class="text-center py-5"><div class="spinner-border text-primary" role="status"></div><div class="mt-2 text-muted">Memuat data...</div></td></tr>`;
            allReturnsData = [];
        }

        try {
            let tsFrom, tsTo;
            if (fp && fp.selectedDates && fp.selectedDates.length === 2) {
                tsFrom = Math.floor(fp.selectedDates[0].getTime() / 1000);
                tsTo = Math.floor(fp.selectedDates[1].getTime() / 1000) + 86399;
            } else {
                const t = new Date();
                const p = new Date();
                p.setDate(t.getDate() - 14);
                tsFrom = Math.floor(p.getTime() / 1000);
                tsTo = Math.floor(t.getTime() / 1000) + 86399;
            }
            
            // Note: Tidak ada lagi error batasan 15 hari dari flatpickr 
            // Karena UI sekarang bebas menarik range manapun dari Database lokal!
            if (selectedStoreId === 'all') {
                storesToFetch = @json($stores->pluck('id'));
            } else {
                storesToFetch = selectedStoreId ? [parseInt(selectedStoreId)] : @json($stores->pluck('id'));
            }

            // Update teks loading jika manual refresh
            if (reset && forceSync) {
                tbody.innerHTML = `<tr><td colspan="7" class="text-center py-5"><div class="spinner-border text-primary" role="status"></div><div class="mt-2 text-muted">Menarik data langsung dari server Shopee...</div></td></tr>`;
            }

            // Sinkronisasi paksa jika tombol Refresh ditekan
            if (forceSync) {
                const syncPromises = storesToFetch.map(sId => 
                    api(`/api/marketplace/stores/${sId}/returns/sync?create_time_from=${tsFrom}&create_time_to=${tsTo}`, { method: 'POST' })
                    .catch(e => console.warn('Gagal sync toko', sId, e))
                );
                await Promise.all(syncPromises);
                shouldSync = false; // Hindari sinkronisasi berulang yang tidak perlu
            }

            let newReturns = [];
            let hasMore = false;
            
            const returnType = document.querySelector('input[name="returnType"]:checked')?.value || 'return';
            const searchQuery = document.getElementById('searchInput').value.trim();

            const promises = storesToFetch.map(sId => 
                api(`/api/marketplace/stores/${sId}/returns/list?page_no=${currentPage}&page_size=40&create_time_from=${tsFrom}&create_time_to=${tsTo}&type=${returnType}&search=${encodeURIComponent(searchQuery)}`)
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
                tbody.innerHTML = `<tr><td colspan="7" class="text-center py-5 text-danger"><i class="bi bi-exclamation-triangle fs-3 d-block mb-2"></i>Gagal mengambil data retur: ${e.message}</td></tr>`;
            } else {
                alert('Gagal mengambil data lebih lanjut: ' + e.message);
            }
        } finally {
            isLoading = false;
            btnRefresh.disabled = false;
            updateLastSyncTime();
        }
    }

    function updateLastSyncTime() {
        const el = document.getElementById('lastSyncTime');
        if (el) {
            const now = new Date();
            el.innerText = 'Terakhir diperbarui: ' + now.getHours().toString().padStart(2, '0') + ':' + now.getMinutes().toString().padStart(2, '0') + ':' + now.getSeconds().toString().padStart(2, '0');
        }
    }

    function renderTable(hasMore) {
        // Update KPIs
        let totalPending = 0;
        let totalCompleted = 0;
        let totalValue = 0;
        
        allReturnsData.forEach(r => {
            if (r.status === 'WAITING_SELLER_RECEIVE' || r.status === 'SELLER_DISPUTE' || r.status === 'PROCESSING') {
                totalPending++;
            }
            if (r.status === 'COMPLETED' || r.status === 'REFUND_PAID') {
                totalCompleted++;
            }
            totalValue += Number(r.amount_before_discount || 0);
        });

        document.getElementById('kpiTotal').innerText = allReturnsData.length;
        document.getElementById('kpiPending').innerText = totalPending;
        document.getElementById('kpiCompleted').innerText = totalCompleted;
        document.getElementById('kpiValue').innerText = 'Rp ' + totalValue.toLocaleString('id-ID');

        if (allReturnsData.length === 0) {
            tbody.innerHTML = '<tr><td colspan="7" class="text-center py-5 text-muted">Saat ini tidak ada data retur. Semua aman! 🎉</td></tr>';
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

            let btnHtml = `<button class="btn btn-sm btn-outline-primary fw-bold shadow-sm d-block w-100 mb-1" onclick="openDetailModal('${r.return_sn}', '${r.store_id}')"><i class="bi bi-info-circle me-1"></i>Detail</button>`;
            if (r.status === 'WAITING_SELLER_RECEIVE') {
                btnHtml += `<button class="btn btn-sm btn-success fw-bold shadow-sm d-block w-100 mb-1" onclick="openConfirmModal('${r.return_sn}', '${r.store_id}')"><i class="bi bi-check2-all me-1"></i>Terima & Restock</button>`;
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
                <td colspan="7" class="text-center py-3 bg-light">
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

    window.openDetailModal = async function(returnSn, storeId) {
        const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('detailModal'));
        const body = document.getElementById('detailModalBody');
        
        body.innerHTML = `
            <div class="text-center py-5">
                <div class="spinner-border text-primary" role="status"></div>
                <div class="mt-2 text-muted">Memuat detail retur...</div>
            </div>
        `;
        modal.show();

        try {
            const data = await api(`/api/marketplace/stores/${storeId}/returns/${returnSn}/detail`);
            
            let html = `<div class="mb-3">
                <div class="fw-bold mb-1">Status Retur (Shopee)</div>
                <div class="badge bg-secondary mb-2">${data.status || 'UNKNOWN'}</div>
                <div><strong>Alasan:</strong> ${data.reason_text_code || data.reason || '-'}</div>
            </div>`;

            if (data.item && data.item.length > 0) {
                html += `<div class="fw-bold mb-2 mt-4 border-bottom pb-1">Daftar Barang yang Diretur</div>`;
                data.item.forEach(itm => {
                    const sku = itm.item_sku || itm.variation_sku || '';
                    html += `
                        <div class="d-flex align-items-center gap-3 mb-3 border p-2 rounded bg-light">
                            <img src="${itm.images && itm.images.length ? itm.images[0] : ''}" style="width:60px; height:60px; object-fit:cover; border-radius:6px;" onerror="this.style.display='none'">
                            <div>
                                <div class="fw-semibold" style="line-height:1.2;">${itm.item_name || 'Item'}</div>
                                <div class="small text-muted mt-1">Variasi: ${itm.variation_name || '-'}</div>
                                <div class="mt-1">
                                    <span class="badge bg-white text-dark border border-secondary shadow-sm">SKU: ${sku}</span>
                                    <span class="badge bg-info text-white shadow-sm ms-1">Qty: ${itm.return_item_quantity || 1}</span>
                                </div>
                            </div>
                        </div>
                    `;
                });
            }

            body.innerHTML = html;
        } catch(e) {
            body.innerHTML = `<div class="alert alert-danger"><i class="bi bi-exclamation-triangle-fill me-2"></i>Gagal memuat detail: ${e.message}</div>`;
        }
    };
</script>
@endpush
