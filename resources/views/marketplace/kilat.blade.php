@extends('layouts.app')

@section('title', 'Marketplace • Pesanan Kilat')

@push('head')
<link rel="stylesheet" href="{{ asset('css/marketplace-orders.css?v=' . time()) }}">
<style>
    /* Keep kilat on the same visual system as Marketplace Orders. */
    .kilat-title-wrap { min-width: 0; }
    .kilat-title-sub { color: var(--shp-muted); font-size: .74rem; margin-top: .12rem; }
    .kilat-store-filter {
        min-height: 32px; max-width: 180px; padding: .28rem 2rem .28rem .65rem;
        border: 1px solid rgba(148,163,184,.35); border-radius: 7px;
        color: #475569; background: var(--card,#fff); font-size: .74rem; font-weight: 600;
    }
    .kilat-store-filter:focus { border-color: #2563eb; outline: 0; box-shadow: 0 0 0 2px rgba(37,99,235,.12); }
    .kilat-subtabs {
        display: none; gap: .25rem; align-items: center; flex-wrap: wrap;
        background: var(--card,#fff); padding: 5px; border-radius: 8px;
        border: 1px solid var(--shp-border); margin-bottom: 1rem; width: fit-content;
    }
    .kilat-placeholder {
        display: inline-flex; align-items: center; gap: .3rem; color: #94a3b8;
        font-size: .68rem; font-style: italic;
    }
    .kilat-empty-state { display: flex; flex-direction: column; align-items: center; gap: .35rem; }
    .kilat-empty-title { color: #334155; font-size: .9rem; font-weight: 800; }
    .kilat-empty-copy { color: #94a3b8; font-size: .75rem; font-weight: 500; }
    .kilat-empty-preview {
        display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: .4rem;
        width: min(100%, 420px); margin-top: .7rem;
    }
    .kilat-empty-preview span {
        min-height: 28px; border: 1px dashed #cbd5e1; border-radius: 6px;
        background: #f8fafc; color: #cbd5e1; font-size: .62rem; padding: .35rem .25rem;
    }
    body[data-theme="dark"] .kilat-store-filter { color: #cbd5e1; background: #0f172a; border-color: rgba(148,163,184,.25); }
    body[data-theme="dark"] .kilat-subtabs { background: #0f172a; }
    body[data-theme="dark"] .kilat-empty-title { color: #f8fafc; }
    body[data-theme="dark"] .kilat-empty-preview span { background: rgba(255,255,255,.03); border-color: rgba(148,163,184,.3); color: #64748b; }
    @media (max-width: 640px) {
        .kilat-title-sub { display: none; }
        .kilat-store-filter { max-width: 145px; }
        .kilat-subtabs { flex-wrap: nowrap; overflow-x: auto; max-width: 100%; }
        .kilat-subtabs .ord-subtab { flex: 0 0 auto !important; }
    }
</style>
@endpush

@section('content')
<div class="page-wrap">
    {{-- ── TOPBAR ── --}}
    <div class="ship-topbar">
        <div class="kilat-title-wrap">
            <h1 class="title">
                ⚡ Pesanan Kilat
                <span class="orders-title-badge">Booking Shopee</span>
            </h1>
            <div class="kilat-title-sub">Kelola booking pengiriman cepat dari Shopee.</div>
        </div>
        <div class="controls">
            <div class="ord-search-bar" style="background:var(--card);">
                <span style="opacity:0.5; font-size:0.9em;">🔍</span>
                <input type="text" id="searchInput" placeholder="Cari SN / Pesanan / Resi..." autocomplete="off">
                <button class="ord-search-clear" id="searchClearBtn" type="button" title="Hapus pencarian">✕</button>
            </div>
            <select id="storeFilter" class="kilat-store-filter mobile-hide" aria-label="Filter toko">
                <option value="">Semua Toko</option>
                @foreach ($stores as $store)
                    <option value="{{ $store->id }}">{{ $store->name }}</option>
                @endforeach
            </select>
            <button class="btn-ship-outline" id="btnRefresh" title="Segarkan Data">🔃 <span class="mobile-hide">Segarkan</span></button>
            <button class="btn-ship-primary" id="btnSync">🔄 <span class="mobile-hide">Sync Pesanan</span></button>
        </div>
    </div>

    {{-- Penjelasan singkat agar owner tidak bingung --}}
    <div id="kiltHelp" class="orders-help">
        <span style="font-size:1rem">💡</span>
        <span>Pesanan <strong>Kilat</strong> dikelola gudang Shopee. Alur: <strong>Perlu Proses Penjual</strong> → <strong>Dikirim ke DC</strong> → <strong>Dikirim ke Pembeli</strong>. Kolom di bawah menampilkan status tiap pesanan secara ringkas.</span>
    </div>

    {{-- TABS (label mengikuti alur Pesanan Kilat, ramah-owner) --}}
    <div class="ord-tabs" id="ordTabs">
        <button class="ord-tab" data-tab="all" onclick="switchTab('all', this)" title="Semua pesanan kilat">
            📋 Semua <span class="ord-badge" id="badge-all">—</span>
        </button>
        <button class="ord-tab active" data-tab="ready" onclick="switchTab('ready', this)" title="Penjual perlu memproses / menyerahkan barang (READY_TO_SHIP/PROCESSED)">
            📦 Perlu Proses Penjual <span class="ord-badge urgent" id="badge-ready">—</span>
        </button>
        <button class="ord-tab" data-tab="shipped" onclick="switchTab('shipped', this)" title="Barang sudah dikirim ke gudang/DC Shopee (SHIPPED/COMPLETED)">
            🚚 Dikirim ke DC <span class="ord-badge" id="badge-shipped" style="background:#eff6ff;color:#2563eb;border-color:#bfdbfe">—</span>
        </button>
        <button class="ord-tab" data-tab="waiting" onclick="switchTab('waiting', this)" title="Barang di gudang Shopee, dalam perjalanan ke pembeli (MATCHED/PENDING)">
            🏠 Dikirim ke Pembeli <span class="ord-badge" id="badge-waiting" style="background:#f0fdf4;color:#16a34a;border-color:#bbf7d0">—</span>
        </button>
        <button class="ord-tab" data-tab="cancelled" onclick="switchTab('cancelled', this)" title="Dibatalkan / gagal (CANCELLED/FAILED)">
            ✖️ Dibatalkan <span class="ord-badge" id="badge-cancelled" style="background:#fef2f2;color:#dc2626;border-color:#fecaca">—</span>
        </button>
    </div>

    {{-- Sub-tab untuk tab "Perlu Proses Penjual" (tampil hanya saat tab itu aktif) --}}
    <div id="subTabReadyContainer" class="kilat-subtabs" style="display:inline-flex">
        <button class="ord-subtab active" data-sub="all" onclick="switchSubTabReady('all', this)">Semua <span class="ord-badge bg-secondary" id="badge-sub-ready-all">—</span></button>
        <button class="ord-subtab" data-sub="to_arrange" onclick="switchSubTabReady('to_arrange', this)">Perlu Diatur <span class="ord-badge bg-secondary urgent" id="badge-sub-ready-arrange">—</span></button>
        <button class="ord-subtab" data-sub="packing" onclick="switchSubTabReady('packing', this)">📦 Sedang Dikemas <span class="ord-badge bg-secondary" id="badge-sub-ready-packing">—</span></button>
        <button class="ord-subtab" data-sub="ready_ship" onclick="switchSubTabReady('ready_ship', this)">Siap Kirim <span class="ord-badge bg-secondary" id="badge-sub-ready-ship">—</span></button>
    </div>

    <div class="card-main">
        <div class="table-responsive">
            <table class="ord-table">
                <colgroup>
                    <col style="width:24%">
                    <col style="width:23%">
                    <col style="width:20%">
                    <col style="width:16%">
                    <col style="width:6%">
                    <col style="width:11%">
                </colgroup>
                <thead>
                    <tr>
                        <th>Order &amp; Pengiriman</th>
                        <th>Item Produk</th>
                        <th>Pembayaran &amp; Penghasilan</th>
                        <th>Voucher &amp; Diskon</th>
                        <th>AMS</th>
                        <th>Pengiriman / Lacak / Cetak</th>
                    </tr>
                </thead>
                <tbody id="kiltBody">
                    <tr>
                        <td colspan="6">
                            <div class="ord-empty">
                                <div class="spinner-border text-primary mb-2" style="width:1.5rem; height:1.5rem; border-width:2px;" role="status"></div>
                                <div>Memuat Data...</div>
                                <div style="font-size:0.8rem; margin-top:4px">Sedang mengambil informasi pesanan kilat.</div>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Detail Booking -->
<div class="modal fade" id="detailModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="padding: 1.25rem 1.4rem .9rem; border-bottom: 1.5px solid #f1f5f9;">
                <h5 class="modal-title fw-bold" style="font-size: 1.05rem; color: #0f172a;">📦 Detail Pesanan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="padding: 0; background: #f8fafc;">
                <div id="detailLoading" class="text-center py-5">
                    <div class="spinner-border text-primary mb-2" style="width:1.5rem; height:1.5rem; border-width:2px;" role="status"></div>
                    <div style="font-size:0.85rem; font-weight:600; color:#475569;">Memuat Detail...</div>
                </div>
                <div id="detailError" style="display:none; padding: 2rem; text-align: center; color: #dc2626;"></div>
                
                <div id="detailContent" style="display:none;">
                    <!-- Info Bar -->
                    <div style="background: #fff; padding: 1rem 1.4rem; border-bottom: 1px solid #e2e8f0; display:flex; flex-wrap:wrap; gap: 1.5rem;">
                        <div>
                            <div style="font-size: .65rem; font-weight: 800; color: #94a3b8; letter-spacing: .07em; text-transform: uppercase;">No. Pesanan / Booking</div>
                            <div id="detSn" class="ord-id" style="font-size: 1rem; margin-top: .15rem;"></div>
                        </div>
                        <div>
                            <div style="font-size: .65rem; font-weight: 800; color: #94a3b8; letter-spacing: .07em; text-transform: uppercase;">Kurir</div>
                            <div id="detCourier" style="font-weight: 700; color: #0f172a; margin-top: .15rem; font-size: .85rem;"></div>
                        </div>
                        <div>
                            <div style="font-size: .65rem; font-weight: 800; color: #94a3b8; letter-spacing: .07em; text-transform: uppercase;">No. Resi / Pelacakan</div>
                            <div id="detTracking" style="font-family: monospace; font-weight: 700; color: var(--shp-accent); margin-top: .15rem; font-size: .95rem;"></div>
                        </div>
                        <div>
                            <div style="font-size: .65rem; font-weight: 800; color: #94a3b8; letter-spacing: .07em; text-transform: uppercase;">Status Kurir (Logistik)</div>
                            <div id="detCourierStatus" style="font-weight: 700; margin-top: .15rem; font-size: .85rem;"></div>
                        </div>
                    </div>
                    
                    <!-- Address & Dropshipper -->
                    <div style="background: #fff; padding: 1rem 1.4rem; border-bottom: 1px solid #e2e8f0; display:flex; flex-wrap:wrap; gap: 1.5rem;">
                        <div style="flex: 1; min-width: 250px;">
                            <div style="font-size: .65rem; font-weight: 800; color: #94a3b8; letter-spacing: .07em; text-transform: uppercase;">Alamat Penerima</div>
                            <div id="detAddress" style="font-size: .85rem; color: #334155; margin-top: .4rem; line-height: 1.4;"></div>
                        </div>
                        <div id="detCancelBox" style="display:none; flex: 1; min-width: 250px;">
                            <div style="font-size: .65rem; font-weight: 800; color: #ef4444; letter-spacing: .07em; text-transform: uppercase;">Info Pembatalan</div>
                            <div style="font-size: .85rem; color: #dc2626; margin-top: .4rem; line-height: 1.4;">
                                Dibatalkan oleh: <span id="detCancelBy" style="font-weight:700"></span><br>
                                Alasan: <span id="detCancelReason" style="font-weight:700"></span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Items -->
                    <div style="padding: 1.4rem;">
                        <h6 style="font-size: .85rem; font-weight: 700; color: #1e293b; margin-bottom: 1rem;">Daftar Produk</h6>
                        <div class="table-responsive" style="background: #fff; border: 1px solid #e2e8f0; border-radius: 8px;">
                            <table class="ord-table" style="margin:0;">
                                <thead>
                                    <tr>
                                        <th>Nama Produk</th>
                                        <th style="text-align:center; width:80px">Qty</th>
                                        <th style="text-align:right">Harga</th>
                                    </tr>
                                </thead>
                                <tbody id="detItems"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer" style="padding: .9rem 1.4rem; border-top: 1.5px solid #f1f5f9; background: #fff;">
                <button type="button" class="btn-ship-outline" id="detTrackBtn" style="display:none">🔎 Lacak Pengiriman</button>
                <button type="button" class="btn-ship-primary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Atur Pengiriman Kilat -->
<div class="modal fade" id="shipModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header" style="padding: 1.25rem 1.4rem .9rem; border-bottom: 1.5px solid #f1f5f9;">
                <h5 class="modal-title fw-bold" style="font-size: 1.05rem; color: #0f172a;">🚚 Atur Pengiriman</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="padding: 1.25rem 1.4rem;">
                <div style="font-size: .65rem; font-weight: 800; color: #94a3b8; letter-spacing: .07em; text-transform: uppercase; margin-bottom: .25rem;">Booking SN</div>
                <div id="shipBookingSn" class="ord-id mb-4" style="font-size:1.1rem"></div>

                <div id="shipLoading" class="text-center py-4">
                    <div class="spinner-border text-primary mb-2" style="width:1.5rem; height:1.5rem; border-width:2px;" role="status"></div>
                    <div style="font-size:0.85rem; font-weight:600;">Mengambil Opsi...</div>
                </div>

                <div id="shipForm" style="display:none">
                    <label style="font-size: .65rem; font-weight: 800; color: #94a3b8; letter-spacing: .07em; text-transform: uppercase; display:block; margin-bottom:.4rem;">Metode Pengiriman</label>
                    <div class="mb-3" id="shipMethods"></div>

                    <div class="mb-3" id="pickupAddrWrap" style="display:none">
                        <label style="font-size: .65rem; font-weight: 800; color: #94a3b8; letter-spacing: .07em; text-transform: uppercase; display:block; margin-bottom:.35rem;">Alamat Pickup</label>
                        <select id="pickupAddr" class="form-select form-control-custom w-100"></select>
                    </div>
                    <div class="mb-3" id="pickupTimeWrap" style="display:none">
                        <label style="font-size: .65rem; font-weight: 800; color: #94a3b8; letter-spacing: .07em; text-transform: uppercase; display:block; margin-bottom:.35rem;">Jadwal Pickup</label>
                        <select id="pickupTime" class="form-select form-control-custom w-100"></select>
                    </div>
                    <div class="mb-3" id="dropoffWrap" style="display:none">
                        <label style="font-size: .65rem; font-weight: 800; color: #94a3b8; letter-spacing: .07em; text-transform: uppercase; display:block; margin-bottom:.35rem;">Titik Dropoff</label>
                        <select id="dropoffBranch" class="form-select form-control-custom w-100"></select>
                    </div>
                    <div id="shipNoParam" style="display:none; background:#eff6ff; border:1px solid #bfdbfe; border-radius:8px; padding:.75rem 1rem; margin-bottom:1rem; align-items:flex-start; gap:.75rem;">
                        <div style="font-size:1.2rem">ℹ️</div>
                        <div>
                            <div style="font-size:.85rem; font-weight:800; color:#1e40af; margin-bottom:.15rem">Kurir Instan/Otomatis</div>
                            <div style="font-size:.78rem; color:#1d4ed8; line-height:1.4">Kurir ini tidak membutuhkan opsi pickup/dropoff. Klik Kirim untuk memproses.</div>
                        </div>
                    </div>
                </div>

                <div id="shipError" style="display:none; background:#fef2f2; border:1px solid #fecaca; border-radius:8px; padding:.75rem 1rem; align-items:flex-start; gap:.75rem;">
                    <div style="font-size:1.2rem">⚠️</div>
                    <div>
                        <div style="font-size:.85rem; font-weight:800; color:#991b1b; margin-bottom:.15rem">Terjadi Kesalahan</div>
                        <div style="font-size:.78rem; color:#b91c1c; line-height:1.4" id="shipErrorText"></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer" style="padding: .9rem 1.4rem; border-top: 1.5px solid #f1f5f9;">
                <button type="button" class="btn-ship-outline" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn-ship-primary" id="shipSubmit" disabled>
                    ✔️ Kirim Sekarang
                </button>
            </div>
        </div>
    </div>
</div>
<!-- Modal Lacak Pengiriman -->
<div class="modal fade" id="trackModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header" style="padding: 1.25rem 1.4rem .9rem; border-bottom: 1.5px solid #f1f5f9;">
                <h5 class="modal-title fw-bold" style="font-size:1.05rem; color:#0f172a;">🔎 Lacak Pengiriman</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="padding:1.25rem 1.4rem;">
                <div style="font-size:.65rem; font-weight:800; color:#94a3b8; letter-spacing:.07em; text-transform:uppercase; margin-bottom:.2rem;">No. Resi</div>
                <div id="trkNo" class="ord-id" style="font-size:1rem; margin-bottom:1rem;">—</div>
                <div id="trkLoading" class="text-center py-4">
                    <div class="spinner-border text-primary mb-2" style="width:1.5rem;height:1.5rem;border-width:2px;" role="status"></div>
                    <div style="font-size:.85rem; font-weight:600;">Memuat pelacakan...</div>
                </div>
                <div id="trkEmpty" style="display:none; text-align:center; color:#64748b; padding:1.5rem; font-size:.85rem;"></div>
                <div id="trkTimeline"></div>
            </div>
            <div class="modal-footer" style="padding:.9rem 1.4rem; border-top:1.5px solid #f1f5f9;">
                <button type="button" class="btn-ship-outline" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const token = document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}';
    const api = async (url, options = {}) => {
        options.headers = Object.assign({ 'Accept': 'application/json' }, options.headers || {});
        if (options.method && options.method !== 'GET') options.headers['X-CSRF-TOKEN'] = token;
        const res = await fetch(url, options);
        let data; try { data = await res.json(); } catch(e) {}
        if (!res.ok) throw new Error(data?.message || data?.error || res.statusText);
        return data;
    };

    const tbody = document.getElementById('kiltBody');
    const btnRefresh = document.getElementById('btnRefresh');
    const btnSync = document.getElementById('btnSync');
    const searchInput = document.getElementById('searchInput');
    const searchClearBtn = document.getElementById('searchClearBtn');
    const storeFilter = document.getElementById('storeFilter');
    let bookings = [];
    let loading = false;
    let currentTab = 'ready';
    let selectedStore = '';
    let subReady = 'all'; // sub-tab di dalam "Perlu Proses Penjual": all | to_arrange | packing

    function fmtDate(ts){
        if(!ts) return '—';
        return new Date(ts*1000).toLocaleString('id-ID', {day:'numeric',month:'short',year:'numeric',hour:'2-digit',minute:'2-digit'});
    }

    // Label status ramah-owner (bukan kode mentah Shopee).
    const STATUS_LABEL = {
        MATCHED:       ['Dikirim ke Pembeli',   'fstatus-done'],
        PENDING:       ['Dikirim ke Pembeli',   'fstatus-done'],
        READY_TO_SHIP: ['Perlu Proses Penjual', 'fstatus-draft'],
        PROCESSED:     ['Sedang Diproses',      'fstatus-draft'],
        SHIPPED:       ['Dikirim ke DC',        'fstatus-pending'],
        COMPLETED:     ['Selesai',              'fstatus-done'],
        CANCELLED:     ['Dibatalkan',           'fstatus-none'],
        FAILED:        ['Gagal',                'fstatus-none'],
    };

    function statusBadge(s){
        s = (s||'').toUpperCase();
        if(!s) return `<span class="fstatus fstatus-none">—</span>`;
        const [label, cls] = STATUS_LABEL[s] || [s.replace(/_/g,' '), 'fstatus-pending'];
        return `<span class="fstatus ${cls}" title="${s}">${label}</span>`;
    }

    // Satu sumber kebenaran pemetaan status → tab, supaya jumlah antar-tab pasti pas.
    function bucketOf(b){
        const s = (b.booking_status||'').toUpperCase();
        if (s === 'CANCELLED' || s === 'FAILED') return 'cancelled';
        if (s === 'SHIPPED' || s === 'COMPLETED') return 'shipped';
        if (s === 'READY_TO_SHIP' || s === 'PROCESSED' || b.needs_shipping) return 'ready';
        return 'waiting'; // MATCHED, PENDING, atau status lain yang belum diproses
    }

    // Bisa dilacak jika barang sudah bergerak (dikirim ke DC / ke pembeli) atau punya resi.
    function isTrackable(b){
        const s = (b.booking_status||'').toUpperCase();
        return ['SHIPPED','COMPLETED','MATCHED'].includes(s) || !!(b.tracking_number);
    }

    // URL pelacakan resmi kurir berdasarkan pola resi. SPX (Shopee Express) → spx.co.id.
    function courierTrackUrl(resi){
        if(!resi) return null;
        const r = String(resi).toUpperCase();
        if(r.startsWith('SPX')) return `https://spx.co.id/#/track?tracking_number=${encodeURIComponent(resi)}`;
        return null; // kurir lain: belum ada deep-link, cukup tombol salin
    }

    // HTML resi + tombol Lacak (SPX) + Salin, dipakai di modal Lacak & Detail.
    function resiActionsHtml(resi){
        if(!resi) return '<em>Belum Ada Resi</em>';
        const url = courierTrackUrl(resi);
        const track = url ? `<a href="${url}" target="_blank" rel="noopener" class="btn-toolbar" style="margin-left:.5rem">🔎 Lacak di SPX</a>` : '';
        const copy  = `<button class="btn-toolbar" style="margin-left:.35rem" onclick="navigator.clipboard.writeText('${resi}').then(()=>{this.textContent='✅ Tersalin'})">📋 Salin</button>`;
        return `<span style="font-family:monospace; font-weight:700">${resi}</span>${track}${copy}`;
    }

    function renderEmpty(filteredCount = 0) {
        if(filteredCount === 0 && bookings.length > 0) {
            return `<tr>
                <td colspan="6">
                    <div class="ord-empty">
                        <div class="ord-empty-icon">📂</div>
                        <div class="kilat-empty-title">Tidak ada pesanan di tampilan ini</div>
                        <div class="kilat-empty-copy">Coba ubah tab, toko, atau kata pencarian.</div>
                    </div>
                </td>
            </tr>`;
        }
        return `<tr>
            <td colspan="6">
                <div class="ord-empty">
                    <div class="ord-empty-icon">⚡</div>
                    <div class="kilat-empty-state">
                        <div class="kilat-empty-title">Belum ada data pesanan kilat</div>
                        <div class="kilat-empty-copy">Data booking akan muncul setelah sinkronisasi Shopee.</div>
                        <div class="kilat-empty-preview" aria-hidden="true">
                            <span>Order &amp; Pengiriman</span><span>Item Produk</span><span>Pembayaran</span>
                            <span>Voucher</span><span>AMS</span><span>Lacak / Cetak</span>
                        </div>
                    </div>
                </div>
            </td>
        </tr>`;
    }
    
    function renderLoading(msg, sub) {
        return `<tr>
            <td colspan="6">
                <div class="ord-empty">
                    <div class="spinner-border text-primary mb-2" style="width:1.5rem; height:1.5rem; border-width:2px;" role="status"></div>
                    <div style="font-weight:600; color:#334155;">${msg}</div>
                    <div style="font-size:0.8rem; margin-top:4px">${sub}</div>
                </div>
            </td>
        </tr>`;
    }

    window.switchTab = function(tabName, el) {
        document.querySelectorAll('.ord-tab').forEach(t => t.classList.remove('active'));
        if(el) el.classList.add('active');
        currentTab = tabName;
        // Sub-tab hanya untuk "Perlu Proses Penjual".
        const sc = document.getElementById('subTabReadyContainer');
        if (sc) sc.style.display = (tabName === 'ready') ? 'inline-flex' : 'none';
        render();
    };

    window.switchSubTabReady = function(sub, el) {
        subReady = sub;
        document.querySelectorAll('#subTabReadyContainer .ord-subtab').forEach(b => b.classList.remove('active'));
        if(el) el.classList.add('active');
        render();
    };

    // Sub-bucket di dalam "Perlu Proses Penjual".
    function readySub(b){
        if (b.fulfillment_status === 'confirmed') return 'ready_ship';               // Siap Kirim
        if (b.needs_shipping) return 'to_arrange';                                  // Perlu Diatur
        if ((b.booking_status||'').toUpperCase() === 'PROCESSED') return 'packing';  // Sedang Dikemas
        return 'to_arrange';
    }

    function filterBookings(arr) {
        let out = selectedStore ? arr.filter(b => String(b.store_id) === String(selectedStore)) : arr;
        if (currentTab === 'all') return out;
        out = out.filter(b => bucketOf(b) === currentTab);
        if (currentTab === 'ready' && subReady !== 'all') {
            out = out.filter(b => readySub(b) === subReady);
        }
        return out;
    }

    function updateBadges() {
        const cnt = { waiting: 0, ready: 0, shipped: 0, cancelled: 0 };
        bookings.forEach(b => { cnt[bucketOf(b)]++; });
        document.getElementById('badge-all').textContent = bookings.length;
        document.getElementById('badge-waiting').textContent = cnt.waiting;
        document.getElementById('badge-ready').textContent = cnt.ready;
        document.getElementById('badge-shipped').textContent = cnt.shipped;
        document.getElementById('badge-cancelled').textContent = cnt.cancelled;

        // Sembunyikan badge "urgent" (merah) di tab Siap Kirim bila memang 0.
        const readyBadge = document.getElementById('badge-ready');
        if (readyBadge) readyBadge.classList.toggle('urgent', cnt.ready > 0);

        // Badge sub-tab "Perlu Proses Penjual".
        let sAll = 0, sArrange = 0, sPacking = 0, sReadyShip = 0;
        bookings.forEach(b => {
            if (bucketOf(b) !== 'ready') return;
            sAll++;
            const sub = readySub(b);
            if (sub === 'packing') sPacking++; 
            else if (sub === 'ready_ship') sReadyShip++;
            else sArrange++;
        });
        const setTxt = (id, v) => { const e = document.getElementById(id); if (e) e.textContent = v; };
        setTxt('badge-sub-ready-all', sAll);
        setTxt('badge-sub-ready-arrange', sArrange);
        setTxt('badge-sub-ready-packing', sPacking);
        setTxt('badge-sub-ready-ship', sReadyShip);
    }

    // Sel AWB ringkas untuk tabel: nomor + ikon lacak SPX bila ada.
    function awbCell(resi){
        if(!resi) return '<span class="kilat-placeholder">Belum ada resi</span>';
        const url = courierTrackUrl(resi);
        const link = url ? ` <a href="${url}" target="_blank" rel="noopener" class="awb-track" title="Lacak di SPX">🔎</a>` : '';
        return `<span style="font-weight:700">${resi}</span>${link}`;
    }

    function render(){
        updateBadges();
        
        const filtered = filterBookings(bookings);

        if(filtered.length === 0){
            tbody.innerHTML = renderEmpty(filtered.length);
            return;
        }
        tbody.innerHTML = '';
        filtered.forEach(b => {
            const store = b.store_name || 'Toko belum terhubung';
            let aksi = `<button class="btn-toolbar" onclick="showDetail('${b.store_id}','${b.booking_sn}')">ℹ️ Detail</button>`;
            if(b.needs_shipping){
                aksi += `<button class="btn-toolbar primary" onclick="arrangeShip('${b.store_id}','${b.booking_sn}')">🚚 Atur Kirim</button>`;
            } else if ((b.booking_status||'').toUpperCase() !== 'CANCELLED') {
                aksi += `<button class="btn-toolbar" onclick="printDocument('${b.store_id}','${b.booking_sn}')">🖨 Cetak Resi</button>`;
            }
            
            if(isTrackable(b)){
                aksi += `<button class="btn-toolbar" onclick="trackShipment('${b.store_id}','${b.booking_sn}')">🔎 Lacak</button>`;
            }
            
            let metaStatusHtml = '';
            if (b.meta) {
                if (b.meta.courier_status) {
                    const cStat = b.meta.courier_status.replace(/_/g, ' ');
                    metaStatusHtml += `<div style="font-size:0.65rem; margin-top:4px; font-weight:700; color:#475569; padding:2px 6px; background:#f1f5f9; border-radius:4px; display:inline-block">🚚 ${cStat}</div>`;
                }
                if (b.meta.booking_shipping_document_status) {
                    const dStat = b.meta.booking_shipping_document_status;
                    const color = dStat === 'READY' ? '#15803d' : '#b91c1c';
                    const bg = dStat === 'READY' ? '#dcfce7' : '#fef2f2';
                    metaStatusHtml += `<div style="font-size:0.65rem; margin-top:4px; font-weight:700; color:${color}; padding:2px 6px; background:${bg}; border-radius:4px; display:inline-block; margin-left:4px">📄 Doc: ${dStat}</div>`;
                }
            }

            const carrierHtml = b.shipping_carrier
                ? `<span class="ord-shipping-carrier">${b.shipping_carrier}</span>`
                : `<span class="kilat-placeholder">Belum dipilih</span>`;
            const trackingHtml = b.tracking_number
                ? `<span class="ord-shipping-awb-value">${awbCell(b.tracking_number)}</span>`
                : `<span class="kilat-placeholder">Belum ada resi</span>`;
            const shippingStatus = b.meta?.courier_status || b.booking_status;
            const shippingStatusHtml = shippingStatus
                ? `<span class="ord-shipping-status fallback">🚚 ${String(shippingStatus).replace(/_/g, ' ')}</span>`
                : `<span class="ord-payment-empty">—</span>`;
            const shippingHtml = `<div class="ord-shipping-stack">
                <span class="ord-shipping-label">Kurir</span>${carrierHtml}
                <span class="ord-shipping-label">No. Resi</span>${trackingHtml}
            </div>`;

            let itemsHtml = '';
            if (b.items && b.items.length > 0) {
                const lines = b.items.map(it => `
                    <div class="ord-item-card">
                        <div class="ord-item-qty">${it.model_quantity_purchased || it.quantity || 1}×</div>
                        <div class="ord-item-body">
                            <div class="ord-item-name">${(it.item_name || it.name || '—')}</div>
                            ${it.model_name ? `<div class="ord-item-variant">${it.model_name}</div>` : ''}
                            ${(it.model_sku || it.item_sku || it.sku) ? `<div class="ord-item-variant" style="color:#94a3b8; font-family:monospace">SKU: ${it.model_sku || it.item_sku || it.sku}</div>` : ''}
                        </div>
                    </div>
                `).join('');
                itemsHtml = `<div class="ord-items-cell" style="margin-top:8px">${lines}</div>`;
            } else {
                itemsHtml = `<div class="ord-items-cell" style="margin-top:8px"><span class="kilat-placeholder">📦 Detail produk belum tersedia</span></div>`;
            }
            
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>
                    <div class="ord-id">${b.booking_sn || '<span class="kilat-placeholder">Nomor booking belum tersedia</span>'}</div>
                    ${(b.order_sn && b.order_sn !== b.booking_sn) ? `<div class="ord-date">📦 ${b.order_sn}</div>` : `<div class="ord-date">Pesanan marketplace belum tertaut</div>`}
                    <div class="ord-order-context">
                        ${statusBadge(b.booking_status)}
                        <span class="ord-order-store">🏪 ${store}</span>
                    </div>
                    <div class="ord-date" style="margin-top:4px">${b.create_time ? fmtDate(b.create_time) : '<span class="kilat-placeholder">Tanggal belum tersedia</span>'}</div>
                </td>`;
            tr.innerHTML += `
                <td>${itemsHtml}</td>
                <td><div class="ord-payment-summary"><span class="ord-payment-empty">Data pembayaran belum tersedia</span><span class="ord-payment-empty">Penghasilan —</span></div></td>
                <td><span class="ord-payment-empty">Voucher belum tersedia</span></td>
                <td class="ord-ams-cell"><span class="ord-payment-empty">—</span></td>
                <td class="ord-track-print-cell"><div class="ord-track-print-stack">${shippingHtml}${shippingStatusHtml}${metaStatusHtml}<div class="ord-action-stack">${aksi}</div></div></td>`;
            tbody.appendChild(tr);
        });
    }

    async function load(){
        if(loading) return;
        loading = true; btnRefresh.disabled = true; btnSync.disabled = true;
        
        if (bookings.length === 0) {
            tbody.innerHTML = renderLoading("Memuat Data...", "Sedang mengambil informasi pesanan kilat.");
        }
        
        try{
            const params = new URLSearchParams();
            if(searchInput.value.trim()) params.set('search', searchInput.value.trim());
            const res = await api('/api/marketplace/bookings/stored?' + params.toString());
            bookings = (res && res.data) ? res.data : [];
            render();
        }catch(e){
            tbody.innerHTML = `<tr>
                <td colspan="6">
                    <div class="ord-empty" style="color:#dc2626">
                        <div class="ord-empty-icon">⚠️</div>
                        <div style="font-weight:600">Gagal memuat data</div>
                        <div style="font-size:0.8rem; margin-top:4px">${e.message}</div>
                    </div>
                </td>
            </tr>`;
        }finally{
            loading = false; btnRefresh.disabled = false; btnSync.disabled = false;
        }
    }

    btnRefresh.addEventListener('click', load);
    btnSync.addEventListener('click', async () => {
        btnSync.disabled = true;
        tbody.innerHTML = renderLoading("Sinkronisasi Shopee...", "Menarik data booking terbaru, mohon bersabar.");
        try{
            await api('/api/marketplace/bookings/sync-all?full=1', { method: 'POST' });
            await load();
        }catch(e){ 
            alert('Gagal sinkron: ' + e.message); 
            btnSync.disabled = false; 
            if(bookings.length > 0) render(); else tbody.innerHTML = renderEmpty();
        }
    });

    let t = null;
    searchInput.addEventListener('input', () => {
        const hasValue = searchInput.value.trim().length > 0;
        searchClearBtn.classList.toggle('visible', hasValue);
        clearTimeout(t);
        t = setTimeout(load, 400);
    });
    searchClearBtn.addEventListener('click', () => {
        searchInput.value = '';
        searchClearBtn.classList.remove('visible');
        load();
    });
    storeFilter.addEventListener('change', () => {
        selectedStore = storeFilter.value;
        render();
    });

    const detailModalEl = document.getElementById('detailModal');
    
    window.showDetail = async (storeId, sn) => {
        document.getElementById('detailLoading').style.display = 'block';
        document.getElementById('detailContent').style.display = 'none';
        document.getElementById('detailError').style.display = 'none';
        
        bootstrap.Modal.getOrCreateInstance(detailModalEl).show();
        
        try {
            const d = await api(`/api/marketplace/stores/${storeId}/bookings/${sn}/detail`);
            const info = d.order_list ? d.order_list[0] : d; // Handle structure
            
            document.getElementById('detailLoading').style.display = 'none';
            document.getElementById('detailContent').style.display = 'block';
            
            // Ambil data lokal sebagai fallback jika API Shopee (getBookingDetail) tidak mereturn field tersebut
            const localBooking = bookings.find(b => b.booking_sn === sn || b.order_sn === sn);
            
            // Resi & kurir bisa berada di package_list (get_order_detail) — bukan hanya top-level.
            const pkg = (info.package_list && info.package_list[0]) ? info.package_list[0] : {};
            const carrier = info.shipping_carrier || pkg.shipping_carrier || (localBooking ? localBooking.shipping_carrier : null) || '—';
            let tracking = info.tracking_no || info.tracking_number || pkg.tracking_number
                || (localBooking ? localBooking.tracking_number : null);
            // OFG… = package_number (nomor paket internal Shopee), BUKAN resi kurir. Jangan tampilkan sebagai resi.
            const packageNo = pkg.package_number || (tracking && String(tracking).toUpperCase().startsWith('OFG') ? tracking : null);
            if (tracking && String(tracking).toUpperCase().startsWith('OFG')) tracking = null;

            document.getElementById('detSn').textContent = info.order_sn || (localBooking ? localBooking.order_sn : sn) || sn;
            document.getElementById('detCourier').textContent = carrier;
            document.getElementById('detTracking').innerHTML = tracking
                ? resiActionsHtml(tracking)
                : (packageNo
                    ? `<em style="color:#94a3b8">Belum ada resi kurir</em><div style="font-size:.7rem; color:#94a3b8; margin-top:2px; font-family:monospace">Paket: ${packageNo}</div>`
                    : '<em>Belum Ada Resi</em>');

            // Tombol Lacak di modal detail — tampil bila pesanan sudah bisa dilacak.
            const detTrackBtn = document.getElementById('detTrackBtn');
            const canTrack = (localBooking && isTrackable(localBooking)) || !!tracking;
            if (canTrack) {
                detTrackBtn.style.display = '';
                detTrackBtn.onclick = () => { bootstrap.Modal.getInstance(detailModalEl)?.hide(); trackShipment(storeId, sn); };
            } else {
                detTrackBtn.style.display = 'none';
            }
            
            // Tampilkan meta logs apabila pesanan sudah diproses webhook
            const courierStatusEl = document.getElementById('detCourierStatus');
            const courierStatus = (localBooking && localBooking.meta && localBooking.meta.courier_status)
                || pkg.logistics_status || info.order_status || (localBooking ? localBooking.booking_status : null);
            if (courierStatus) {
                const cStat = String(courierStatus).replace(/_/g, ' ');
                courierStatusEl.innerHTML = `<span style="color:#2563eb; background:#eff6ff; padding:2px 6px; border-radius:4px;">🚚 ${cStat}</span>`;
            } else {
                courierStatusEl.innerHTML = '<span style="color:#64748b">—</span>';
            }
            
            const tbody = document.getElementById('detItems');
            tbody.innerHTML = '';
            
            // Address & Cancellation info
            const addr = info.recipient_address;
            let addrHtml = '<em>Tidak ada data alamat</em>';
            if (addr) {
                addrHtml = `<strong>${addr.name || ''}</strong> ${addr.phone ? '('+addr.phone+')' : ''}<br>`;
                addrHtml += `${addr.full_address || ''}`;
            }
            if (info.dropshipper && info.dropshipper !== '-') {
                addrHtml += `<div style="margin-top:8px; padding-top:8px; border-top:1px dashed #cbd5e1; color:#0f172a">
                    <strong>Dropshipper:</strong> ${info.dropshipper} ${info.dropshipper_phone ? '('+info.dropshipper_phone+')' : ''}
                </div>`;
            }
            document.getElementById('detAddress').innerHTML = addrHtml;

            const cancelBox = document.getElementById('detCancelBox');
            if (info.booking_status === 'CANCELLED' || info.cancel_by) {
                cancelBox.style.display = 'block';
                document.getElementById('detCancelBy').textContent = info.cancel_by || 'Unknown';
                document.getElementById('detCancelReason').textContent = info.cancel_reason || '—';
            } else {
                cancelBox.style.display = 'none';
            }
            
            // Item list fallback to local DB items if Shopee API doesn't provide it
            const itemsToRender = (info.item_list && info.item_list.length > 0) ? info.item_list : (localBooking ? localBooking.items : []);
            
            if (itemsToRender && itemsToRender.length > 0) {
                itemsToRender.forEach(item => {
                    const price = item.model_discounted_price || item.model_original_price || item.price || 0;
                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td>
                            <div style="font-weight:600; color:#0f172a; margin-bottom:2px;">${item.item_name || item.name || '—'}</div>
                            <div style="font-size:0.7rem; color:#64748b;">${item.model_name || item.variation_name || ''} <span style="margin-left:5px; font-family:monospace; color:#94a3b8">${item.model_sku || item.item_sku || item.sku || ''}</span></div>
                        </td>
                        <td style="text-align:center; font-weight:700;">${item.model_quantity_purchased || 1}</td>
                        <td style="text-align:right; font-weight:600; color:#1e293b;">Rp ${parseInt(price).toLocaleString('id-ID')}</td>
                    `;
                    tbody.appendChild(tr);
                });
            } else {
                tbody.innerHTML = `<tr><td colspan="3" class="text-center" style="padding:2rem; color:#64748b;">Informasi item tidak tersedia untuk pesanan ini.</td></tr>`;
            }
            
        } catch (e) {
            document.getElementById('detailLoading').style.display = 'none';
            const errEl = document.getElementById('detailError');
            errEl.style.display = 'block';
            errEl.innerHTML = `<strong>Gagal mengambil detail pesanan.</strong><br><span style="font-size:0.85rem">${e.message}</span>`;
        }
    };

    // ── Atur Kirim (pilih pickup / dropoff) ──────────────────────────────────
    let shipCtx = { storeId: null, sn: null, method: null };
    const shipModalEl = document.getElementById('shipModal');
    const el = id => document.getElementById(id);

    let shipAddrList = [];

    function resetShipModal(){
        el('shipLoading').style.display = 'block';
        el('shipForm').style.display = 'none';
        el('shipError').style.display = 'none';
        el('pickupAddrWrap').style.display = 'none';
        el('pickupTimeWrap').style.display = 'none';
        el('dropoffWrap').style.display = 'none';
        el('shipNoParam').style.display = 'none';
        el('shipSubmit').disabled = true;
        el('pickupAddr').innerHTML = '';
        el('pickupTime').innerHTML = '';
        el('dropoffBranch').innerHTML = '';
        el('shipMethods').innerHTML = '';
        shipAddrList = [];
    }

    // Tampilkan/sembunyikan bagian sesuai metode yang dipilih user (radio).
    function applyShipMethod(method){
        shipCtx.method = method;
        el('pickupAddrWrap').style.display = (method === 'pickup') ? 'block' : 'none';
        el('pickupTimeWrap').style.display = (method === 'pickup' && el('pickupTime').options.length) ? 'block' : 'none';
        el('dropoffWrap').style.display  = (method === 'dropoff') ? 'block' : 'none';
        el('shipNoParam').style.display  = (method === 'none') ? 'flex' : 'none';
    }

    window.arrangeShip = async (storeId, sn) => {
        shipCtx = { storeId, sn, method: null };
        el('shipBookingSn').textContent = sn;
        resetShipModal();
        bootstrap.Modal.getOrCreateInstance(shipModalEl).show();

        try {
            const p  = await api(`/api/marketplace/stores/${storeId}/bookings/${sn}/shipping-parameter`);
            const rd = p.response || p;
            const info = rd.info_needed || {};

            // Tangani dua kemungkinan bentuk respons Shopee:
            // (a) data di bawah info_needed.pickup/dropoff, atau (b) di top-level pickup/dropoff.
            const pickupData  = (info.pickup && info.pickup.address_list) ? info.pickup : (rd.pickup || null);
            const dropoffData = (info.dropoff && info.dropoff.branch_list) ? info.dropoff : (rd.dropoff || null);
            const addrList   = (pickupData && pickupData.address_list) ? pickupData.address_list : [];
            const branchList = (dropoffData && dropoffData.branch_list) ? dropoffData.branch_list : [];
            const hasPickup  = ('pickup' in info) || addrList.length > 0;
            const hasDropoff = ('dropoff' in info) || branchList.length > 0;

            shipAddrList = addrList;
            el('shipLoading').style.display = 'none';
            el('shipForm').style.display = 'block';

            // Isi dropdown pickup (alamat + jadwal) & dropoff.
            let defaultAddrIdx = 0;
            addrList.forEach((a, i) => {
                const label = [a.address, a.city, a.state, a.zipcode].filter(Boolean).join(', ');
                let isDefault = (a.address_flag && a.address_flag.includes('default_address')) ? 'selected' : '';
                if (isDefault) defaultAddrIdx = i;
                el('pickupAddr').insertAdjacentHTML('beforeend', `<option value="${a.address_id}" data-idx="${i}" ${isDefault}>${label || ('Alamat #'+a.address_id)}</option>`);
            });
            if (addrList.length) {
                fillPickupTimes(addrList, defaultAddrIdx);
                el('pickupAddr').onchange = e => { fillPickupTimes(addrList, e.target.selectedOptions[0].dataset.idx); applyShipMethod('pickup'); };
            }
            branchList.forEach(b => {
                const label = [b.address, b.city, b.state, b.zipcode].filter(Boolean).join(', ');
                el('dropoffBranch').insertAdjacentHTML('beforeend', `<option value="${b.branch_id}">${label || ('Titik #'+b.branch_id)}</option>`);
            });

            // Bangun radio metode (Pickup diutamakan jika ada alamat pickup).
            let radios = '';
            const shouldDefaultToPickup = hasPickup && addrList.length > 0;
            
            if (hasDropoff) {
                radios += `<label style="display:flex; align-items:center; gap:.5rem; padding:.5rem .7rem; border:1px solid #e2e8f0; border-radius:8px; margin-bottom:.4rem; cursor:pointer;">
                    <input type="radio" name="shipMethod" value="dropoff" ${!shouldDefaultToPickup ? 'checked' : ''}>
                    <span><strong>🏪 Drop-off</strong> — antar ke titik/cabang</span></label>`;
            }
            if (hasPickup) {
                radios += `<label style="display:flex; align-items:center; gap:.5rem; padding:.5rem .7rem; border:1px solid #e2e8f0; border-radius:8px; margin-bottom:.4rem; cursor:pointer;">
                    <input type="radio" name="shipMethod" value="pickup" ${shouldDefaultToPickup ? 'checked' : ''}>
                    <span><strong>📦 Pickup</strong> — dijemput kurir</span></label>`;
            }
            el('shipMethods').innerHTML = radios;

            document.querySelectorAll('input[name="shipMethod"]').forEach(r => {
                r.addEventListener('change', e => applyShipMethod(e.target.value));
            });

            // Metode awal sesuai yang tercentang; kalau tak ada opsi → 'none'.
            const initial = document.querySelector('input[name="shipMethod"]:checked');
            applyShipMethod(initial ? initial.value : 'none');
            el('shipSubmit').disabled = false;
        } catch(e) {
            el('shipLoading').style.display = 'none';
            el('shipError').style.display = 'flex';
            el('shipErrorText').textContent = 'Gagal mengambil opsi pengiriman: ' + e.message;
        }
    };

    function fillPickupTimes(addrList, idx){
        const slots = (addrList[idx] && addrList[idx].time_slot_list) || [];
        const sel = el('pickupTime');
        sel.innerHTML = '';
        if (!slots.length) { el('pickupTimeWrap').style.display = 'none'; return; }
        el('pickupTimeWrap').style.display = 'block';
        slots.forEach(s => {
            const d = s.date ? new Date(s.date*1000).toLocaleDateString('id-ID', {weekday:'short',day:'numeric',month:'short'}) : '';
            let isRecommended = (s.flags && s.flags.includes('recommended')) ? 'selected' : '';
            sel.insertAdjacentHTML('beforeend', `<option value="${s.pickup_time_id}" ${isRecommended}>${[d, s.time_text].filter(Boolean).join(' ')}</option>`);
        });
    }

    el('shipSubmit').addEventListener('click', async () => {
        const btn = el('shipSubmit');
        btn.disabled = true;
        const original = btn.innerHTML;
        btn.innerHTML = 'Memproses...';

        const method = document.querySelector('input[name="shipMethod"]:checked')?.value || shipCtx.method;
        const body = {};
        if (method === 'pickup') {
            body.pickup = {};
            if (el('pickupAddr').value) body.pickup.address_id = Number(el('pickupAddr').value) || el('pickupAddr').value;
            if (el('pickupTimeWrap').style.display !== 'none' && el('pickupTime').value) {
                body.pickup.pickup_time_id = el('pickupTime').value;
            }
        } else if (method === 'dropoff') {
            body.dropoff = {};
            if (el('dropoffBranch').value) body.dropoff.branch_id = Number(el('dropoffBranch').value) || el('dropoffBranch').value;
        }

        try {
            const res = await api(`/api/marketplace/stores/${shipCtx.storeId}/bookings/${shipCtx.sn}/ship`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(body),
            });
            bootstrap.Modal.getInstance(shipModalEl).hide();
            alert((res.message || 'Berhasil') + (res.tracking_number ? ('\nResi: ' + res.tracking_number) : ''));
            load();
        } catch(e) {
            el('shipError').style.display = 'flex';
            el('shipErrorText').textContent = 'Gagal atur kirim: ' + e.message;
            btn.disabled = false;
            btn.innerHTML = original;
        }
    });

    // ── Lacak Pengiriman ──────────────────────────────────────────────────────
    const trackModalEl = document.getElementById('trackModal');
    window.trackShipment = async (storeId, sn) => {
        el('trkNo').textContent = '—';
        el('trkLoading').style.display = 'block';
        el('trkTimeline').innerHTML = '';
        el('trkEmpty').style.display = 'none';
        bootstrap.Modal.getOrCreateInstance(trackModalEl).show();
        try {
            const d = await api(`/api/marketplace/stores/${storeId}/bookings/${sn}/tracking`);
            el('trkLoading').style.display = 'none';
            el('trkNo').innerHTML = resiActionsHtml(d.tracking_number);
            const list = (d.tracking_info || []).slice().sort((a,b) => (b.update_time||0) - (a.update_time||0));
            if (!list.length) {
                el('trkEmpty').style.display = 'block';
                el('trkEmpty').textContent = d.message || 'Belum ada data pelacakan.';
                return;
            }
            el('trkTimeline').innerHTML = list.map((t, i) => {
                const dt = t.update_time ? new Date(t.update_time*1000).toLocaleString('id-ID') : '';
                const desc = t.description || String(t.logistics_status || '').replace(/_/g, ' ');
                const active = i === 0;
                return `<div style="display:flex; gap:.7rem;">
                    <div style="display:flex; flex-direction:column; align-items:center;">
                        <div style="width:11px;height:11px;border-radius:50%;background:${active?'#16a34a':'#cbd5e1'};margin-top:4px;flex:none"></div>
                        ${i < list.length-1 ? '<div style="width:2px;flex:1;background:#e2e8f0;margin-top:2px;min-height:14px"></div>' : ''}
                    </div>
                    <div style="padding-bottom:.6rem;">
                        <div style="font-weight:${active?700:600};color:${active?'#0f172a':'#475569'};font-size:.83rem;line-height:1.35">${desc}</div>
                        <div style="font-size:.7rem;color:#94a3b8;margin-top:1px">${dt}</div>
                    </div>
                </div>`;
            }).join('');
        } catch(e) {
            el('trkLoading').style.display = 'none';
            el('trkEmpty').style.display = 'block';
            el('trkEmpty').textContent = 'Gagal memuat pelacakan: ' + e.message;
        }
    };

    // ── Cetak Resi ───────────────────────────────────────────────────────────
    window.printDocument = async function (storeId, bookingSn) {
        const url = `/api/marketplace/stores/${storeId}/bookings/${bookingSn}/document`;
        
        const alertHtml = `<div id="printAlert" style="position:fixed;top:20px;right:20px;background:#3b82f6;color:white;padding:10px 20px;border-radius:8px;z-index:9999;box-shadow:0 4px 6px rgba(0,0,0,0.1)">⏳ Meminta dokumen resi kilat...</div>`;
        document.body.insertAdjacentHTML('beforeend', alertHtml);
        
        window.open(url, '_blank');
        
        setTimeout(() => {
            const el = document.getElementById('printAlert');
            if (el) el.remove();
            load();
        }, 5000);
    };

    // ── Polling (tanpa Reverb) ───────────────────────────────────────────────
    let lastPollAt = Date.now();
    setInterval(() => {
        // Polling setiap 15 detik
        if (Date.now() - lastPollAt >= 15000) {
            lastPollAt = Date.now();
            // Hanya poll jika halaman sedang aktif/terlihat
            if (!document.hidden) {
                load();
            }
        }
    }, 5000);

    load();
</script>
@endpush
