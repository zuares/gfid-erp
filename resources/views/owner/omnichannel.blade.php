@extends('layouts.app')

@section('title', 'Marketplace • Toko')

@php
    $tabs = [
        'toko'        => 'Toko & Channel',
        'orders'      => 'Order Lokal',
        'fulfillment' => 'Fulfillment',
        'sku-mapping' => 'SKU Mapping',
    ];

    $tabDesc = [
        'toko'        => 'Kelola toko marketplace yang terhubung dan login channel baru.',
        'orders'      => 'Order yang sudah disync dari marketplace ke database lokal.',
        'fulfillment' => 'Review dan konfirmasi order sebelum stok dipotong.',
        'sku-mapping' => 'Petakan SKU marketplace ke item internal untuk auto-fulfillment.',
    ];
@endphp

@push('head')
    @include('production.dashboard.partials._gf-styles')
    <style>
        .prod-tab-loading {
            display: flex; align-items: center; justify-content: center;
            gap: .6rem; color: var(--gf-muted); font-size: .85rem; padding: 2.4rem 1rem;
        }
        .prod-tab-spinner {
            width: 18px; height: 18px; border-radius: 50%;
            border: 2px solid rgba(148,163,184,.35); border-top-color: #2563eb;
            animation: ocspin .7s linear infinite;
        }
        @keyframes ocspin { to { transform: rotate(360deg); } }

        .gf-filter-toggle { display: none; }

        .gf-header-select {
            min-height: 38px; max-width: 168px; border-radius: 999px !important;
            font-size: .78rem; font-weight: 700; padding-left: .85rem; padding-right: 1.9rem;
            border-color: rgba(15,23,42,.10); box-shadow: none !important;
        }

        /* KPI grid */
        .oc-kpi-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: .65rem;
            margin-bottom: .9rem;
        }
        .oc-kpi-card {
            border: 1px solid rgba(15,23,42,.075);
            border-radius: 16px;
            padding: .82rem .9rem;
            background: linear-gradient(180deg, #fff 0%, #fcfcfd 100%);
        }
        .oc-kpi-label {
            color: #64748b; font-size: .66rem; font-weight: 950;
            text-transform: uppercase; letter-spacing: .04em; margin-bottom: .18rem;
        }
        .oc-kpi-value {
            color: #0f172a; font-size: 1.25rem; font-weight: 950;
            line-height: 1.15; letter-spacing: -.02em;
        }
        .oc-kpi-note { color: #94a3b8; font-size: .7rem; font-weight: 800; margin-top: .2rem; }

        /* Channel pill */
        .oc-channel-pill {
            display: inline-flex; align-items: center; gap: .3rem;
            font-size: .72rem; font-weight: 800; padding: .15rem .55rem;
            border-radius: 999px; background: #f1f5f9; border: 1px solid #e2e8f0;
            color: #475569; white-space: nowrap;
        }
        .oc-channel-shopee   { background: rgba(238,77,45,.08);  border-color: rgba(238,77,45,.2);  color: #b91c1c; }
        .oc-channel-tokopedia{ background: rgba(0,177,79,.08);   border-color: rgba(0,177,79,.2);   color: #166534; }
        .oc-channel-tiktok   { background: rgba(0,0,0,.06);      border-color: rgba(0,0,0,.12);     color: #1e293b; }
        .oc-channel-lazada   { background: rgba(0,51,204,.07);   border-color: rgba(0,51,204,.18);  color: #1d4ed8; }
        .oc-channel-offline  { background: rgba(100,116,139,.08);border-color: rgba(100,116,139,.2);color: #475569; }

        /* Status badge */
        .oc-badge {
            display: inline-block; font-size: .68rem; font-weight: 800;
            padding: .14rem .5rem; border-radius: 999px; white-space: nowrap;
        }
        .oc-badge-green { background: rgba(34,197,94,.14); color: #166534; }
        .oc-badge-amber { background: rgba(245,158,11,.16); color: #b45309; }
        .oc-badge-muted { background: rgba(148,163,184,.16); color: #64748b; }
        .oc-badge-red   { background: rgba(239,68,68,.14);  color: #b91c1c; }

        /* Recommendation chips */
        .oc-reco-chip {
            display: inline-flex; flex-direction: column;
            padding: .42rem .72rem; border-radius: 12px;
            border: 1.5px solid rgba(15,23,42,.1); background: #f8fafc;
            cursor: pointer; transition: border-color .15s, background .15s;
            text-align: left; max-width: 160px;
        }
        .oc-reco-chip:hover { border-color: #2563eb; background: #eff6ff; }
        .oc-reco-chip.is-selected { border-color: #16a34a; background: #f0fdf4; }
        .oc-reco-chip-code { font-size: .78rem; font-weight: 900; color: #0f172a; }
        .oc-reco-chip-name { font-size: .68rem; color: #64748b; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

        /* Empty state */
        .oc-empty {
            text-align: center; color: var(--gf-muted);
            font-size: .85rem; padding: 2.4rem 1rem;
        }

        /* Sync modal date row */
        .oc-sync-row { display: flex; gap: .65rem; align-items: flex-end; flex-wrap: wrap; }
        .oc-sync-row .form-control { border-radius: 12px; font-size: .85rem; min-height: 38px; }

        @media (max-width: 576px) {
            .oc-kpi-grid { grid-template-columns: repeat(2, 1fr); }
            .gf-filter-toggle {
                display: inline-flex; align-items: center; justify-content: center;
                width: 40px; height: 40px; flex: 0 0 auto; border-radius: 999px;
                border: 1px solid rgba(15,23,42,.12); background: #fff; color: #0f172a; cursor: pointer;
            }
            .gf-dashboard-header-actions { position: relative; justify-content: flex-end !important; }
            .gf-dashboard-header-actions.is-open { z-index: 2000; }
            .gf-dashboard-header-filter {
                display: none !important; position: absolute; top: calc(100% + .45rem); right: 0;
                z-index: 2001; grid-template-columns: 1fr !important;
                width: min(88vw, 320px); padding: .8rem; gap: .55rem; background: #fff;
                border: 1px solid rgba(15,23,42,.1); border-radius: 16px;
                box-shadow: 0 16px 36px rgba(15,23,42,.16);
            }
            .gf-dashboard-header-actions.is-open .gf-dashboard-header-filter { display: grid !important; }
            .gf-master-eyebrow { display: none !important; }
        }
    </style>
@endpush

@section('content')
<x-gf.page
    eyebrow="Marketplace"
    title="Toko Marketplace"
    :description="$tabDesc[$initialTab]">

    <x-slot:actions>
        <div class="gf-dashboard-header-actions d-flex align-items-center gap-2 flex-wrap" id="ocFilterWrap">
            <button type="button" class="gf-filter-toggle" id="ocFilterToggle" aria-label="Filter" aria-expanded="false">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 3H2l8 9.46V19l4 2v-8.54L22 3z"/></svg>
            </button>

            <div class="gf-dashboard-header-filter d-flex align-items-center gap-2 flex-wrap" id="ocFilterBar">
                <select class="form-select gf-header-select" id="ocPeriod" aria-label="Periode" style="min-width:110px">
                    <option value="7">7 Hari</option>
                    <option value="30">30 Hari</option>
                    <option value="month">Bulan Ini</option>
                    <option value="custom">Custom</option>
                </select>

                <input type="text" class="form-control gf-header-date-input" id="ocDateRange"
                    autocomplete="off" aria-label="Rentang tanggal"
                    value="{{ $filters['date_from'] }} — {{ $filters['date_to'] }}"
                    style="min-width:190px; border-radius:999px; font-size:.78rem; font-weight:700; border-color:rgba(15,23,42,.10); box-shadow:none">

                <input type="hidden" id="ocDateFrom" value="{{ $filters['date_from'] }}">
                <input type="hidden" id="ocDateTo"   value="{{ $filters['date_to'] }}">

                <a href="{{ route('owner.omnichannel') }}" class="btn btn-light border"
                    style="border-radius:999px; font-size:.78rem; font-weight:700; min-height:38px; padding:.35rem .9rem">
                    Reset
                </a>

                <a href="{{ route('owner.omnichannel.shopee.connect') }}" class="btn btn-dark"
                    style="border-radius:999px; font-size:.78rem; font-weight:700; min-height:38px; padding:.35rem .9rem; white-space:nowrap">
                    + Login Shopee
                </a>
            </div>
        </div>
    </x-slot:actions>

    <div class="gf-marketplace-dashboard gf-marketplace-clean-ui" id="ocRoot">

        {{-- TABS --}}
        <div class="gf-marketplace-sticky-head">
            <div class="gf-marketplace-tabs" role="tablist" id="ocTabs">
                @foreach ($tabs as $key => $label)
                    <button type="button" class="gf-marketplace-tab {{ $key === $initialTab ? 'is-active' : '' }}"
                        data-tab-target="{{ $key }}">{{ $label }}</button>
                @endforeach
            </div>
        </div>

        {{-- PANES --}}
        @foreach ($tabs as $key => $label)
            <section class="gf-marketplace-tab-panel" data-tab-panel="{{ $key }}"
                @if($key !== $initialTab) hidden @endif>

                @if ($key === 'toko')
                    {{-- KPI --}}
                    <div class="oc-kpi-grid">
                        <div class="oc-kpi-card">
                            <div class="oc-kpi-label">Channel</div>
                            <div class="oc-kpi-value" id="kpiChannels">—</div>
                            <div class="oc-kpi-note">aktif</div>
                        </div>
                        <div class="oc-kpi-card">
                            <div class="oc-kpi-label">Toko</div>
                            <div class="oc-kpi-value" id="kpiStores">—</div>
                            <div class="oc-kpi-note">terhubung</div>
                        </div>
                        <div class="oc-kpi-card">
                            <div class="oc-kpi-label">Token Exp.</div>
                            <div class="oc-kpi-value" id="kpiTokenExp">—</div>
                            <div class="oc-kpi-note">toko aktif</div>
                        </div>
                        <div class="oc-kpi-card">
                            <div class="oc-kpi-label">Last Sync</div>
                            <div class="oc-kpi-value" id="kpiLastSync" style="font-size:.9rem">—</div>
                            <div class="oc-kpi-note">dari toko manapun</div>
                        </div>
                    </div>

                    {{-- Stores panel --}}
                    <x-gf.panel title="Daftar Toko" subtitle="Pilih toko untuk cek info atau sync order.">
                        <x-slot:actions>
                            <button type="button" class="btn btn-light border btn-sm"
                                style="border-radius:999px;font-size:.75rem;font-weight:700"
                                onclick="ocBootstrap()">Buat Channel Default</button>
                        </x-slot:actions>
                        <div id="storeBody">
                            <div class="prod-tab-loading"><span class="prod-tab-spinner"></span> Memuat…</div>
                        </div>
                    </x-gf.panel>

                @elseif ($key === 'fulfillment')
                    {{-- KPI fulfillment --}}
                    <div class="oc-kpi-grid">
                        <div class="oc-kpi-card">
                            <div class="oc-kpi-label">Perlu Review</div>
                            <div class="oc-kpi-value" id="kpiFulfillPending">—</div>
                            <div class="oc-kpi-note">draft / pending</div>
                        </div>
                        <div class="oc-kpi-card">
                            <div class="oc-kpi-label">Unresolved SKU</div>
                            <div class="oc-kpi-value" id="kpiFulfillUnresolved">—</div>
                            <div class="oc-kpi-note">butuh mapping manual</div>
                        </div>
                        <div class="oc-kpi-card">
                            <div class="oc-kpi-label">Stok Kurang</div>
                            <div class="oc-kpi-value" id="kpiFulfillShortage">—</div>
                            <div class="oc-kpi-note">ada kekurangan stok</div>
                        </div>
                        <div class="oc-kpi-card">
                            <div class="oc-kpi-label">Siap Konfirmasi</div>
                            <div class="oc-kpi-value" id="kpiFulfillReady">—</div>
                            <div class="oc-kpi-note">semua resolved & stok cukup</div>
                        </div>
                    </div>

                    {{-- Warning banner unmapped SKU --}}
                    <div id="fulfillUnmappedBanner" style="display:none;margin-bottom:.75rem">
                        <div style="background:rgba(245,158,11,.1);border:1.5px solid rgba(245,158,11,.35);border-radius:14px;padding:.75rem 1rem;display:flex;align-items:center;justify-content:space-between;gap:.75rem;flex-wrap:wrap">
                            <div style="display:flex;align-items:center;gap:.55rem">
                                <span style="font-size:1rem">⚠️</span>
                                <div>
                                    <span class="fw-bold" style="font-size:.82rem;color:#92400e">Ada <span id="bannerUnmappedCount">?</span> item belum dipetakan (SKU belum di-mapping).</span>
                                    <span style="font-size:.78rem;color:#b45309"> Tambahkan mapping dulu agar item bisa di-resolve otomatis.</span>
                                </div>
                            </div>
                            <button type="button" class="btn btn-warning btn-sm fw-bold"
                                style="border-radius:999px;font-size:.75rem;white-space:nowrap"
                                onclick="activate('sku-mapping')">→ Buka SKU Mapping</button>
                        </div>
                    </div>

                    <x-gf.panel title="Fulfillment Queue" subtitle="Order yang perlu dikonfirmasi sebelum stok dipotong.">
                        <x-slot:actions>
                            <button type="button" class="btn btn-light border btn-sm"
                                style="border-radius:999px;font-size:.75rem;font-weight:700"
                                onclick="ocLoadFulfillments()">↻ Refresh</button>
                            <button type="button" class="btn btn-warning btn-sm" id="remapAllBtn"
                                style="border-radius:999px;font-size:.75rem;font-weight:700"
                                onclick="ocRemapAll()">⚡ Apply Mapping</button>
                        </x-slot:actions>
                        <div id="fulfillBody">
                            <div class="prod-tab-loading"><span class="prod-tab-spinner"></span> Memuat…</div>
                        </div>
                    </x-gf.panel>

                @elseif ($key === 'sku-mapping')
                    {{-- KPI sku-mapping --}}
                    <div class="oc-kpi-grid">
                        <div class="oc-kpi-card">
                            <div class="oc-kpi-label">Belum Dimapping</div>
                            <div class="oc-kpi-value" id="kpiUnmapped" style="color:#dc2626">—</div>
                            <div class="oc-kpi-note">SKU dari marketplace</div>
                        </div>
                        <div class="oc-kpi-card">
                            <div class="oc-kpi-label">Sudah Dimapping</div>
                            <div class="oc-kpi-value" id="kpiMapped">—</div>
                            <div class="oc-kpi-note">mapping aktif</div>
                        </div>
                        <div class="oc-kpi-card">
                            <div class="oc-kpi-label">Total SKU</div>
                            <div class="oc-kpi-value" id="kpiTotalSku">—</div>
                            <div class="oc-kpi-note">unik dari order</div>
                        </div>
                        <div class="oc-kpi-card">
                            <div class="oc-kpi-label">Coverage</div>
                            <div class="oc-kpi-value" id="kpiSkuCoverage">—</div>
                            <div class="oc-kpi-note">% SKU terpetakan</div>
                        </div>
                    </div>

                    {{-- Unmapped SKUs --}}
                    <x-gf.panel title="SKU Belum Dipetakan" subtitle="SKU dari order marketplace yang belum punya item internal.">
                        <x-slot:actions>
                            <button type="button" class="btn btn-light border btn-sm"
                                style="border-radius:999px;font-size:.75rem;font-weight:700"
                                onclick="ocLoadMappings()">↻ Refresh</button>
                        </x-slot:actions>
                        <div id="unmappedBody">
                            <div class="prod-tab-loading"><span class="prod-tab-spinner"></span> Memuat…</div>
                        </div>
                    </x-gf.panel>

                    {{-- Existing mappings --}}
                    <x-gf.panel title="Mapping Terdaftar" subtitle="Petakan marketplace_sku → item internal. Dipakai saat auto-fulfillment.">
                        <x-slot:actions>
                            <button type="button" class="btn btn-dark btn-sm"
                                style="border-radius:999px;font-size:.75rem;font-weight:700"
                                onclick="ocOpenAddMapping()">+ Tambah Mapping</button>
                        </x-slot:actions>
                        <div id="mappingBody">
                            <div class="prod-tab-loading"><span class="prod-tab-spinner"></span> Memuat…</div>
                        </div>
                    </x-gf.panel>

                @elseif ($key === 'orders')
                    {{-- KPI orders --}}
                    <div class="oc-kpi-grid">
                        <div class="oc-kpi-card">
                            <div class="oc-kpi-label">Total Order</div>
                            <div class="oc-kpi-value" id="kpiTotalOrders">—</div>
                            <div class="oc-kpi-note" id="kpiOrderPeriod">periode ini</div>
                        </div>
                        <div class="oc-kpi-card">
                            <div class="oc-kpi-label">Completed</div>
                            <div class="oc-kpi-value" id="kpiCompleted">—</div>
                            <div class="oc-kpi-note">COMPLETED</div>
                        </div>
                        <div class="oc-kpi-card">
                            <div class="oc-kpi-label">Cancelled</div>
                            <div class="oc-kpi-value" id="kpiCancelled">—</div>
                            <div class="oc-kpi-note">CANCELLED</div>
                        </div>
                        <div class="oc-kpi-card">
                            <div class="oc-kpi-label">Total GMV</div>
                            <div class="oc-kpi-value" id="kpiGmv" style="font-size:.88rem">—</div>
                            <div class="oc-kpi-note">total_amount</div>
                        </div>
                    </div>

                    {{-- Orders panel --}}
                    <x-gf.panel title="Order Tersimpan" subtitle="Order yang sudah disync ke database.">
                        <x-slot:actions>
                            <button type="button" class="btn btn-light border btn-sm"
                                style="border-radius:999px;font-size:.75rem;font-weight:700"
                                onclick="ocLoadOrders(true)">↻ Refresh</button>
                        </x-slot:actions>
                        <div id="ordersBody">
                            <div class="prod-tab-loading"><span class="prod-tab-spinner"></span> Memuat…</div>
                        </div>
                    </x-gf.panel>
                @endif

            </section>
        @endforeach
    </div>
</x-gf.page>

{{-- Sync Modal --}}
<div class="modal fade" id="syncModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:20px;border:1px solid var(--gf-border)">
            <div class="modal-header border-0 pb-0">
                <div>
                    <h5 class="modal-title fw-black" id="syncModalTitle">Sync Order</h5>
                    <div class="text-muted" style="font-size:.8rem" id="syncModalSub">Pilih rentang tanggal</div>
                </div>
                <button class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="syncAlert" class="alert d-none mb-3" style="border-radius:12px;font-size:.85rem"></div>
                <div class="oc-sync-row mb-3">
                    <div class="flex-fill">
                        <label class="form-label fw-bold" style="font-size:.75rem;color:#64748b">DARI</label>
                        <input type="date" class="form-control" id="syncFrom">
                    </div>
                    <div class="flex-fill">
                        <label class="form-label fw-bold" style="font-size:.75rem;color:#64748b">SAMPAI</label>
                        <input type="date" class="form-control" id="syncTo">
                    </div>
                </div>
                <div class="d-flex justify-content-end gap-2">
                    <button class="btn btn-light border" style="border-radius:999px" data-bs-dismiss="modal">Batal</button>
                    <button class="btn btn-dark" style="border-radius:999px" id="syncBtn" onclick="ocDoSync()">Sync</button>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Shop Info Modal --}}
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

{{-- Fulfillment Detail Modal --}}
<div class="modal fade" id="fulfillModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content" style="border-radius:20px">
            <div class="modal-header border-0 pb-0">
                <div>
                    <h5 class="modal-title fw-black" id="fulfillModalTitle">Detail Fulfillment</h5>
                    <div class="text-muted" style="font-size:.8rem" id="fulfillModalSub"></div>
                </div>
                <button class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="fulfillModalBody">
                <div class="prod-tab-loading"><span class="prod-tab-spinner"></span> Memuat…</div>
            </div>
            <div class="modal-footer border-0">
                <div id="fulfillModalAlert" class="alert d-none w-100 mb-0" style="border-radius:12px;font-size:.85rem"></div>
                <button class="btn btn-light border" style="border-radius:999px" data-bs-dismiss="modal">Tutup</button>
                <button class="btn btn-success" style="border-radius:999px;font-weight:700" id="fulfillConfirmBtn">
                    ✓ Konfirmasi & Potong Stok
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Add SKU Mapping Modal --}}
<div class="modal fade" id="mappingModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:20px">
            <div class="modal-header border-0">
                <h5 class="modal-title fw-black">Tambah SKU Mapping</h5>
                <button class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-bold" style="font-size:.75rem;color:#64748b">MARKETPLACE SKU</label>
                    <input type="text" class="form-control" id="mapSku" placeholder="cth: K3BLK-1" style="border-radius:12px" autocomplete="off">
                </div>

                {{-- Rekomendasi item internal --}}
                <div id="mapRecommendations" style="display:none;margin-bottom:1rem">
                    <div class="fw-bold mb-1" style="font-size:.72rem;color:#64748b;text-transform:uppercase;letter-spacing:.04em">
                        REKOMENDASI ITEM INTERNAL
                    </div>
                    <div id="mapRecoList" class="d-flex flex-wrap gap-2"></div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold" style="font-size:.75rem;color:#64748b">CHANNEL (opsional)</label>
                    <select class="form-select" id="mapChannel" style="border-radius:12px">
                        <option value="">Semua Channel</option>
                        <option value="shopee">Shopee</option>
                        <option value="tiktok">TikTok</option>
                        <option value="tokopedia">Tokopedia</option>
                        <option value="lazada">Lazada</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold" style="font-size:.75rem;color:#64748b">ITEM INTERNAL</label>
                    <input type="text" class="form-control mb-1" id="mapItemSearch" placeholder="Cari kode atau nama item…" style="border-radius:12px" autocomplete="off">
                    <div id="mapItemResults" class="border rounded" style="border-radius:12px;overflow:hidden;display:none;max-height:200px;overflow-y:auto"></div>
                    <input type="hidden" id="mapItemId">
                    <div id="mapItemSelected" class="mt-1 text-success fw-bold" style="font-size:.8rem"></div>
                </div>

                {{-- Form buat item varian baru (muncul kalau tidak ada rekomendasi) --}}
                <div id="mapNewItemForm" style="display:none;border:1.5px dashed rgba(37,99,235,.3);border-radius:14px;padding:.85rem 1rem;background:rgba(239,246,255,.6);margin-bottom:1rem">
                    <div class="fw-bold mb-2" style="font-size:.78rem;color:#1d4ed8">
                        ✦ Buat Item Varian Baru
                        <span class="text-muted fw-normal" style="font-size:.72rem"> — tidak ditemukan di database</span>
                    </div>
                    <div class="mb-2">
                        <label class="form-label fw-bold" style="font-size:.72rem;color:#64748b;margin-bottom:.2rem">KODE ITEM</label>
                        <input type="text" class="form-control form-control-sm" id="newItemCode" placeholder="cth: K5BLK" style="border-radius:10px;text-transform:uppercase">
                    </div>
                    <div class="mb-2">
                        <label class="form-label fw-bold" style="font-size:.72rem;color:#64748b;margin-bottom:.2rem">NAMA ITEM</label>
                        <input type="text" class="form-control form-control-sm" id="newItemName" placeholder="cth: Kaos Polos Hitam Size L" style="border-radius:10px">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold" style="font-size:.72rem;color:#64748b;margin-bottom:.2rem">SATUAN</label>
                        <input type="text" class="form-control form-control-sm" id="newItemUnit" placeholder="pcs" value="pcs" style="border-radius:10px;max-width:100px">
                    </div>
                    <div id="mapNewItemAlert" class="alert d-none mb-2" style="border-radius:10px;font-size:.8rem;padding:.45rem .75rem"></div>
                    <button type="button" class="btn btn-primary btn-sm fw-bold" style="border-radius:999px;font-size:.75rem"
                        onclick="ocQuickCreateItem()">+ Buat & Pilih Item Ini</button>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold" style="font-size:.75rem;color:#64748b">CATATAN</label>
                    <input type="text" class="form-control" id="mapNotes" style="border-radius:12px">
                </div>
                <div class="d-flex justify-content-end gap-2">
                    <button class="btn btn-light border" style="border-radius:999px" data-bs-dismiss="modal">Batal</button>
                    <button class="btn btn-dark" style="border-radius:999px;font-weight:700" onclick="ocSaveMapping()">Simpan</button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    // ── State ─────────────────────────────────────────────────────────────
    const TAB_DESC = @json($tabDesc);
    const INITIAL_TAB = @json($initialTab);
    const KEY = 'ocTab';

    let channels = [], stores = [], orders = [], warehouses = [];
    let syncStoreId = null, syncStoreName = '';

    // ── Helpers ───────────────────────────────────────────────────────────
    const $ = id => document.getElementById(id);
    const descEl = document.querySelector('.gf-master-desc');
    const setDesc = t => { if (descEl && TAB_DESC[t]) descEl.textContent = TAB_DESC[t]; };

    function fmt(d) {
        if (!d) return '—';
        return new Date(d).toLocaleString('id-ID', { day:'2-digit', month:'short', year:'numeric', hour:'2-digit', minute:'2-digit' });
    }
    function fmtDate(d) {
        if (!d) return '—';
        return new Date(d).toLocaleDateString('id-ID', { day:'2-digit', month:'short', year:'numeric' });
    }
    function fmtRp(v) {
        return new Intl.NumberFormat('id-ID', { style:'currency', currency:'IDR', maximumFractionDigits:0 }).format(v||0);
    }
    function esc(s) { const d = document.createElement('div'); d.innerText = s??''; return d.innerHTML; }

    function channelPill(ch) {
        if (!ch) return '<span class="oc-channel-pill">—</span>';
        const cls = { shopee:'oc-channel-shopee', tokopedia:'oc-channel-tokopedia', tiktok:'oc-channel-tiktok', lazada:'oc-channel-lazada', offline:'oc-channel-offline' }[ch.code] || '';
        return `<span class="oc-channel-pill ${cls}">${esc(ch.name)}</span>`;
    }
    function statusBadge(s) {
        const map = { active:'oc-badge-green', inactive:'oc-badge-muted', expired:'oc-badge-red', COMPLETED:'oc-badge-green', CANCELLED:'oc-badge-red', READY_TO_SHIP:'oc-badge-amber', PROCESSED:'oc-badge-amber' };
        return `<span class="oc-badge ${map[s]||'oc-badge-muted'}">${esc(s||'—')}</span>`;
    }

    async function api(url, opts = {}) {
        const r = await fetch(url, {
            headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', ...(opts.headers||{}) },
            ...opts
        });
        const payload = await r.json().catch(() => ({ message: 'Response tidak valid.' }));
        if (!r.ok) throw new Error(payload.message || payload.error || 'API error');
        return payload;
    }

    // ── URL / filter ──────────────────────────────────────────────────────
    function getDateFrom() { return $('ocDateFrom').value; }
    function getDateTo()   { return $('ocDateTo').value; }
    function activeTab()   { return document.querySelector('.gf-marketplace-tab.is-active')?.dataset.tabTarget || INITIAL_TAB; }

    function syncUrl(tab) {
        const p = new URLSearchParams({ tab: tab||activeTab(), date_from: getDateFrom(), date_to: getDateTo() });
        history.replaceState(null, '', location.pathname + '?' + p.toString());
    }

    // ── Tabs ──────────────────────────────────────────────────────────────
    const tabBtns = Array.from(document.querySelectorAll('#ocTabs .gf-marketplace-tab'));
    const panes   = Array.from(document.querySelectorAll('[data-tab-panel]'));

    window.activate = function activate(name) {
        tabBtns.forEach(b => b.classList.toggle('is-active', b.dataset.tabTarget === name));
        panes.forEach(p => p.hidden = (p.dataset.tabPanel !== name));
        setDesc(name);
        try { localStorage.setItem(KEY, name); } catch(e){}
        syncUrl(name);
        if (name === 'toko')        ocLoadStores();
        if (name === 'orders')      ocLoadOrders();
        if (name === 'fulfillment') ocLoadFulfillments();
        if (name === 'sku-mapping') ocLoadMappings();
    }

    tabBtns.forEach(b => b.addEventListener('click', () => activate(b.dataset.tabTarget)));

    // ── Mobile filter toggle ──────────────────────────────────────────────
    const filterWrap   = $('ocFilterWrap');
    const filterToggle = $('ocFilterToggle');
    if (filterToggle) {
        filterToggle.addEventListener('click', e => {
            e.stopPropagation();
            const open = filterWrap.classList.toggle('is-open');
            filterToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        });
        document.addEventListener('click', e => {
            if (filterWrap.classList.contains('is-open') && !filterWrap.contains(e.target)) {
                filterWrap.classList.remove('is-open');
                filterToggle.setAttribute('aria-expanded', 'false');
            }
        });
    }

    // ── Period / date-range picker ────────────────────────────────────────
    (function () {
        const period   = $('ocPeriod');
        const rangeInp = $('ocDateRange');
        const fromInp  = $('ocDateFrom');
        const toInp    = $('ocDateTo');

        function applyPeriod(val) {
            const today = new Date();
            let from = new Date(today), to = new Date(today);
            if (val === '7')     { from.setDate(today.getDate()-6); }
            else if (val === '30'){ from.setDate(today.getDate()-29); }
            else if (val === 'month'){ from = new Date(today.getFullYear(), today.getMonth(), 1); }
            else return; // custom — jangan override
            fromInp.value = from.toISOString().slice(0,10);
            toInp.value   = to.toISOString().slice(0,10);
            rangeInp.value = fromInp.value + ' — ' + toInp.value;
            syncUrl();
            if (activeTab() === 'orders') ocLoadOrders(true);
        }

        period.addEventListener('change', () => applyPeriod(period.value));

        // Init flatpickr if available
        if (window.flatpickr) {
            flatpickr(rangeInp, {
                mode: 'range', dateFormat: 'Y-m-d', locale: 'id',
                defaultDate: [fromInp.value, toInp.value],
                onChange(dates) {
                    if (dates.length === 2) {
                        fromInp.value = dates[0].toISOString().slice(0,10);
                        toInp.value   = dates[1].toISOString().slice(0,10);
                        rangeInp.value = fromInp.value + ' — ' + toInp.value;
                        period.value = 'custom';
                        syncUrl();
                        if (activeTab() === 'orders') ocLoadOrders(true);
                    }
                }
            });
        }
    })();

    // ── STORES ────────────────────────────────────────────────────────────
    async function ocLoadStores() {
        const [cRes, sRes, wRes] = await Promise.allSettled([
            api('/api/omnichannel/channels'),
            api('/api/omnichannel/stores'),
            api('/api/omnichannel/warehouses'),
        ]);
        channels   = cRes.status === 'fulfilled' ? cRes.value : [];
        stores     = sRes.status === 'fulfilled' ? sRes.value : [];
        warehouses = wRes.status === 'fulfilled' ? wRes.value : [];
        renderKpiToko();
        renderStores();
    }

    function renderKpiToko() {
        $('kpiChannels').textContent = channels.length;
        $('kpiStores').textContent   = stores.length;

        const valid = stores.filter(s => s.token_expires_at && new Date(s.token_expires_at) > new Date()).length;
        $('kpiTokenExp').textContent = valid;

        const last = stores.filter(s => s.last_synced_at).sort((a,b) => new Date(b.last_synced_at)-new Date(a.last_synced_at))[0];
        $('kpiLastSync').textContent = last?.last_synced_at ? fmtDate(last.last_synced_at) : '—';
    }

    function renderStores() {
        const body = $('storeBody');
        if (!stores.length) {
            body.innerHTML = `<div class="oc-empty">Belum ada toko terhubung.<br>
                <a href="/owner/omnichannel/shopee/connect" class="btn btn-dark btn-sm mt-3" style="border-radius:999px">+ Login Shopee</a></div>`;
            return;
        }
        const whOptions = warehouses.map(w =>
            `<option value="${w.id}">${esc(w.code)} — ${esc(w.name)}</option>`
        ).join('');

        body.innerHTML = `
        <div class="gf-table-scroll">
        <table class="gf-clean-table w-100">
            <thead><tr>
                <th>Toko</th><th>Channel</th><th>Shop ID</th>
                <th>Gudang Default</th>
                <th>Last Sync</th><th>Status</th>
                <th class="text-end">Aksi</th>
            </tr></thead>
            <tbody>
            ${stores.map(s => `
                <tr>
                    <td><span class="fw-bold">${esc(s.name||'—')}</span><br>
                        <span class="text-muted" style="font-size:.72rem">${esc(s.region||'ID')}</span></td>
                    <td>${channelPill(s.channel)}</td>
                    <td><code style="font-size:.78rem">${esc(s.external_shop_id||'—')}</code></td>
                    <td>
                        <select class="form-select form-select-sm" style="border-radius:10px;font-size:.75rem;min-width:160px"
                            onchange="ocSetWarehouse(${s.id}, this.value, this)">
                            <option value="">— Pilih Gudang —</option>
                            ${warehouses.map(w =>
                                `<option value="${w.id}" ${s.default_warehouse_id == w.id ? 'selected' : ''}>${esc(w.code)} — ${esc(w.name)}</option>`
                            ).join('')}
                        </select>
                    </td>
                    <td style="font-size:.78rem;color:var(--gf-muted)">${s.last_synced_at ? fmt(s.last_synced_at) : '—'}</td>
                    <td>${statusBadge(s.status)}</td>
                    <td class="text-end">
                        <div class="d-flex gap-2 justify-content-end">
                            <button class="btn btn-light border btn-sm" style="border-radius:999px;font-size:.73rem;font-weight:700"
                                onclick="ocCheckStore(${s.id}, '${esc(s.name)}')">Cek</button>
                            <button class="btn btn-dark btn-sm" style="border-radius:999px;font-size:.73rem;font-weight:700"
                                onclick="ocOpenSync(${s.id}, '${esc(s.name)}')">↓ Sync</button>
                        </div>
                    </td>
                </tr>
            `).join('')}
            </tbody>
        </table>
        </div>`;
    }

    // ── ORDERS ────────────────────────────────────────────────────────────
    async function ocLoadOrders(force = false) {
        const body = $('ordersBody');
        body.innerHTML = '<div class="prod-tab-loading"><span class="prod-tab-spinner"></span> Memuat…</div>';

        orders = await api('/api/omnichannel/local-orders').catch(() => []);
        renderKpiOrders();
        renderOrders();
    }

    function renderKpiOrders() {
        const from = new Date(getDateFrom() + 'T00:00:00');
        const to   = new Date(getDateTo()   + 'T23:59:59');
        const filtered = orders.filter(o => {
            if (!o.ordered_at) return true;
            const d = new Date(o.ordered_at);
            return d >= from && d <= to;
        });

        $('kpiTotalOrders').textContent = filtered.length;
        $('kpiCompleted').textContent   = filtered.filter(o => o.order_status === 'COMPLETED').length;
        $('kpiCancelled').textContent   = filtered.filter(o => o.order_status === 'CANCELLED').length;
        $('kpiGmv').textContent = fmtRp(filtered.reduce((s,o) => s + parseFloat(o.total_amount||0), 0));
        $('kpiOrderPeriod').textContent = getDateFrom() + ' s/d ' + getDateTo();
    }

    function renderOrders() {
        const body = $('ordersBody');
        const from = new Date(getDateFrom() + 'T00:00:00');
        const to   = new Date(getDateTo()   + 'T23:59:59');
        const rows = orders.filter(o => {
            if (!o.ordered_at) return true;
            const d = new Date(o.ordered_at);
            return d >= from && d <= to;
        });

        if (!rows.length) {
            body.innerHTML = '<div class="oc-empty">Belum ada order di rentang tanggal ini.</div>';
            return;
        }

        body.innerHTML = `
        <div class="gf-table-scroll gf-table-scroll-sticky">
        <table class="gf-clean-table gf-sticky-table w-100">
            <thead><tr>
                <th>Order</th><th>Toko</th><th>Status</th>
                <th>Item</th><th class="text-end">Total</th>
            </tr></thead>
            <tbody>
            ${rows.map(o => {
                const items = o.items||[];
                return `
                <tr>
                    <td>
                        <div class="fw-bold" style="font-size:.8rem;font-variant-numeric:tabular-nums">${esc(o.channel_order_id||'—')}</div>
                        <div class="text-muted" style="font-size:.7rem">${o.ordered_at ? fmtDate(o.ordered_at) : '—'}</div>
                    </td>
                    <td>
                        <div class="fw-semibold" style="font-size:.82rem">${esc(o.store?.name||'—')}</div>
                        <div>${channelPill(o.store?.channel)}</div>
                    </td>
                    <td>${statusBadge(o.order_status)}</td>
                    <td style="font-size:.78rem">
                        ${items.length ? items.slice(0,2).map(i =>
                            `<div class="mb-1"><span class="fw-bold">${esc(i.model_sku||i.item_sku||'—')}</span> · ${esc(i.variant_name||'—')} · ${i.qty} pcs</div>`
                        ).join('') + (items.length>2 ? `<div class="text-muted">+${items.length-2} lainnya</div>` : '')
                        : '<span class="text-muted">—</span>'}
                    </td>
                    <td class="text-end fw-bold" style="white-space:nowrap;font-variant-numeric:tabular-nums">${fmtRp(o.total_amount)}</td>
                </tr>`;
            }).join('')}
            </tbody>
        </table>
        </div>
        <div class="gf-table-foot"><span class="gf-table-foot-hint">${rows.length} order ditampilkan</span></div>`;
    }

    // ── Actions ───────────────────────────────────────────────────────────
    window.ocBootstrap = async function () {
        try {
            const d = await api('/api/omnichannel/bootstrap', { method: 'POST' });
            await ocLoadStores();
        } catch (e) { alert(e.message); }
    };

    window.ocSetWarehouse = async function (storeId, warehouseId, selectEl) {
        selectEl.disabled = true;
        try {
            const d = await api('/api/omnichannel/stores/' + storeId, {
                method: 'PATCH',
                body: JSON.stringify({ default_warehouse_id: warehouseId || null }),
            });
            // Tunjukkan feedback sebentar
            const orig = selectEl.style.borderColor;
            selectEl.style.borderColor = '#16a34a';
            setTimeout(() => { selectEl.style.borderColor = orig; }, 1500);
            // Kalau ada fulfillment yang di-update, reload tab fulfillment juga
            if (d.fulfillments_updated > 0) {
                await ocLoadFulfillments();
            }
        } catch (e) {
            alert('Gagal simpan gudang: ' + e.message);
        } finally {
            selectEl.disabled = false;
        }
    };

    window.ocCheckStore = async function (id, name) {
        $('infoModalTitle').textContent = 'Info Toko — ' + name;
        $('infoOutput').textContent = 'Memuat…';
        new bootstrap.Modal($('infoModal')).show();
        try {
            const d = await api('/api/omnichannel/stores/' + id + '/shop-info');
            $('infoOutput').textContent = JSON.stringify(d, null, 2);
        } catch (e) {
            $('infoOutput').textContent = 'Error: ' + e.message;
        }
    };

    window.ocOpenSync = function (id, name) {
        syncStoreId = id; syncStoreName = name;
        $('syncModalTitle').textContent = 'Sync Order — ' + name;
        $('syncModalSub').textContent   = 'Pilih rentang tanggal yang akan di-sync.';
        $('syncAlert').className = 'alert d-none';
        $('syncFrom').value = getDateFrom();
        $('syncTo').value   = getDateTo();
        $('syncBtn').disabled = false;
        $('syncBtn').textContent = 'Sync';
        new bootstrap.Modal($('syncModal')).show();
    };

    window.ocDoSync = async function () {
        if (!syncStoreId) return;
        const from = new Date($('syncFrom').value + 'T00:00:00');
        const to   = new Date($('syncTo').value   + 'T23:59:59');
        const btn = $('syncBtn');
        btn.disabled = true; btn.textContent = 'Syncing…';

        const alert = $('syncAlert');
        alert.className = 'alert alert-warning';
        alert.textContent = 'Sedang sync order…';

        try {
            const d = await api('/api/omnichannel/stores/' + syncStoreId + '/sync-orders', {
                method: 'POST',
                body: JSON.stringify({
                    time_from: Math.floor(from.getTime()/1000),
                    time_to:   Math.floor(to.getTime()/1000),
                    page_size: 50,
                })
            });
            alert.className = 'alert alert-success';
            alert.textContent = d.message || 'Sync selesai.';
            btn.textContent = 'Selesai';
            await ocLoadStores();
            if (activeTab() === 'orders') await ocLoadOrders(true);
        } catch (e) {
            alert.className = 'alert alert-danger';
            alert.textContent = e.message;
            btn.disabled = false; btn.textContent = 'Coba Lagi';
        }
    };

    // ── FULFILLMENT ───────────────────────────────────────────────────────
    let fulfillments = [];
    let currentFulfillId = null;

    async function ocLoadFulfillments() {
        const body = $('fulfillBody');
        body.innerHTML = '<div class="prod-tab-loading"><span class="prod-tab-spinner"></span> Memuat…</div>';
        fulfillments = await api('/api/fulfillments').catch(() => []);
        renderKpiFulfillment();
        renderFulfillments();
    }

    function renderKpiFulfillment() {
        const pending    = fulfillments.length;
        const unresolved = fulfillments.filter(f => !f.all_resolved).length;
        const shortage   = fulfillments.filter(f => f.has_shortage).length;
        const ready      = fulfillments.filter(f => f.all_resolved && !f.has_shortage).length;
        $('kpiFulfillPending').textContent    = pending;
        $('kpiFulfillUnresolved').textContent = unresolved;
        $('kpiFulfillShortage').textContent   = shortage;
        $('kpiFulfillReady').textContent      = ready;

        // Hitung total lines unmapped di semua fulfillment
        const unmappedLines = fulfillments.reduce((n, f) => n + (f.lines_count - f.lines_resolved), 0);
        const banner = $('fulfillUnmappedBanner');
        if (unmappedLines > 0) {
            $('bannerUnmappedCount').textContent = unmappedLines;
            banner.style.display = 'block';
        } else {
            banner.style.display = 'none';
        }
    }

    function renderFulfillments() {
        const body = $('fulfillBody');
        if (!fulfillments.length) {
            body.innerHTML = '<div class="oc-empty">Tidak ada order yang perlu dikonfirmasi.</div>';
            return;
        }
        body.innerHTML = `
        <div class="gf-table-scroll gf-table-scroll-sticky">
        <table class="gf-clean-table gf-sticky-table w-100">
            <thead><tr>
                <th>Order</th><th>Toko</th><th>Status</th>
                <th>Item</th><th>Stok</th><th class="text-end">Aksi</th>
            </tr></thead>
            <tbody>
            ${fulfillments.map(f => {
                const stockCls = !f.all_resolved ? 'oc-badge-red' : f.has_shortage ? 'oc-badge-amber' : 'oc-badge-green';
                const stockLbl = !f.all_resolved ? 'Belum Mapped' : f.has_shortage ? 'Stok Kurang' : 'Siap';
                return `<tr>
                    <td>
                        <div class="fw-bold" style="font-size:.8rem">${esc(f.order?.channel_order_id||'—')}</div>
                        <div class="text-muted" style="font-size:.7rem">${f.order?.ordered_at ? fmtDate(f.order.ordered_at) : '—'}</div>
                    </td>
                    <td>
                        <div style="font-size:.82rem">${esc(f.order?.store?.name||'—')}</div>
                        <div class="text-muted" style="font-size:.72rem">${esc(f.order?.store?.channel||'—')}</div>
                    </td>
                    <td>${statusBadge(f.status)}</td>
                    <td style="font-size:.78rem">${f.lines_resolved}/${f.lines_count} item resolved</td>
                    <td><span class="oc-badge ${stockCls}">${stockLbl}</span></td>
                    <td class="text-end">
                        <button class="btn btn-dark btn-sm" style="border-radius:999px;font-size:.73rem;font-weight:700"
                            onclick="ocOpenFulfillment(${f.id})">Review →</button>
                    </td>
                </tr>`;
            }).join('')}
            </tbody>
        </table>
        </div>
        <div class="gf-table-foot"><span class="gf-table-foot-hint">${fulfillments.length} order pending</span></div>`;
    }

    window.ocOpenFulfillment = async function (id) {
        currentFulfillId = id;
        $('fulfillModalTitle').textContent = 'Fulfillment #' + id;
        $('fulfillModalSub').textContent   = 'Review item, edit jika perlu, lalu konfirmasi.';
        $('fulfillModalBody').innerHTML    = '<div class="prod-tab-loading"><span class="prod-tab-spinner"></span> Memuat…</div>';
        $('fulfillModalAlert').className   = 'alert d-none w-100 mb-0';
        $('fulfillConfirmBtn').disabled    = false;
        $('fulfillConfirmBtn').textContent = '✓ Konfirmasi & Potong Stok';
        new bootstrap.Modal($('fulfillModal')).show();

        const f = await api('/api/fulfillments/' + id).catch(e => null);
        if (!f) { $('fulfillModalBody').innerHTML = '<div class="oc-empty text-danger">Gagal memuat data.</div>'; return; }

        renderFulfillmentDetail(f);
    };

    function renderFulfillmentDetail(f) {
        $('fulfillModalTitle').textContent = 'Fulfillment — ' + (f.order?.channel_order_id || '#' + f.id);
        $('fulfillModalSub').textContent   = `${f.order?.store?.name} · ${f.order?.store?.channel} · ${f.warehouse?.name || 'Belum ada gudang'}`;

        $('fulfillModalBody').innerHTML = `
        <div class="table-responsive">
        <table class="table align-middle" style="font-size:.85rem">
            <thead class="table-light">
                <tr>
                    <th>SKU Marketplace</th><th>Nama Item</th>
                    <th>Item Internal</th><th>Lot</th>
                    <th class="text-center">Dipesan</th><th class="text-center">Dipenuhi</th>
                    <th>Stok</th><th></th>
                </tr>
            </thead>
            <tbody id="fulfillLinesBody">
            ${(f.lines||[]).map(l => renderFulfillLine(l)).join('')}
            </tbody>
        </table>
        </div>`;
    }

    function renderFulfillLine(l) {
        const statusMap = { ok:'oc-badge-green', low:'oc-badge-amber', empty:'oc-badge-red', unresolved:'oc-badge-red' };
        const statusLbl = { ok:'Cukup', low:'Kurang', empty:'Habis', unresolved:'Belum Mapped' };
        return `<tr id="fline-${l.id}">
            <td><code style="font-size:.78rem">${esc(l.marketplace_sku||'—')}</code></td>
            <td style="max-width:180px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">${esc(l.marketplace_item_name||'—')}</td>
            <td>
                ${l.item
                    ? `<span class="fw-bold">${esc(l.item.code)}</span><br><span class="text-muted" style="font-size:.72rem">${esc(l.item.name)}</span>`
                    : `<span class="text-danger" style="font-size:.78rem">Belum dipetakan</span>`}
                ${l.substituted ? '<span class="oc-badge oc-badge-amber ms-1">Diganti</span>' : ''}
            </td>
            <td style="font-size:.78rem;color:var(--gf-muted)">${l.lot ? esc(l.lot.code) : '—'}</td>
            <td class="text-center fw-bold">${l.qty_ordered}</td>
            <td class="text-center">
                <input type="number" class="form-control form-control-sm text-center"
                    style="width:70px;border-radius:8px;display:inline-block"
                    value="${l.qty_fulfilled}" min="0" max="${l.qty_ordered}"
                    onchange="ocUpdateLineQty(${l.id}, this.value)">
            </td>
            <td>
                <span class="oc-badge ${statusMap[l.stock_status]||'oc-badge-muted'}">${statusLbl[l.stock_status]||l.stock_status}</span>
                <div class="text-muted" style="font-size:.7rem">${l.stock_available} tersedia</div>
            </td>
            <td>
                <button class="btn btn-light border btn-sm" style="border-radius:999px;font-size:.72rem"
                    onclick="ocEditLine(${l.id}, '${esc(l.marketplace_sku||'')}')">Edit</button>
            </td>
        </tr>`;
    }

    window.ocUpdateLineQty = async function (lineId, qty) {
        await api(`/api/fulfillments/${currentFulfillId}/lines/${lineId}`, {
            method: 'PATCH', body: JSON.stringify({ qty_fulfilled: parseInt(qty) })
        }).catch(e => alert(e.message));
    };

    window.ocEditLine = function (lineId, sku) {
        $('mapSku').value = sku;
        $('mapItemId').value = '';
        $('mapItemSelected').textContent = '';
        $('mapItemSearch').value = '';
        // Simpan lineId untuk update setelah mapping
        $('mappingModal').dataset.editLineId = lineId;
        $('mappingModal').dataset.editFulfillId = currentFulfillId;
        new bootstrap.Modal($('mappingModal')).show();
    };

    $('fulfillConfirmBtn').addEventListener('click', async () => {
        if (!currentFulfillId) return;
        const btn = $('fulfillConfirmBtn');
        btn.disabled = true; btn.textContent = 'Mengkonfirmasi…';
        const alert = $('fulfillModalAlert');
        try {
            const d = await api(`/api/fulfillments/${currentFulfillId}/confirm`, { method: 'POST' });
            alert.className = 'alert alert-success w-100 mb-0';
            alert.textContent = d.message;
            btn.textContent = '✓ Selesai';
            await ocLoadFulfillments();
        } catch (e) {
            alert.className = 'alert alert-danger w-100 mb-0';
            alert.textContent = e.message;
            btn.disabled = false; btn.textContent = '✓ Konfirmasi & Potong Stok';
        }
    });

    // ── SKU MAPPING ───────────────────────────────────────────────────────
    let mappings = [], unmappedSkus = [];

    async function ocLoadMappings() {
        $('mappingBody').innerHTML  = '<div class="prod-tab-loading"><span class="prod-tab-spinner"></span> Memuat…</div>';
        $('unmappedBody').innerHTML = '<div class="prod-tab-loading"><span class="prod-tab-spinner"></span> Memuat…</div>';

        [mappings, unmappedSkus] = await Promise.all([
            api('/api/sku-mappings').catch(() => []),
            api('/api/sku-mappings/unmapped-skus').catch(() => []),
        ]);

        renderKpiSku();
        renderUnmapped();
        renderMappings();
    }

    function renderKpiSku() {
        const total    = unmappedSkus.length + mappings.length;
        const coverage = total ? Math.round((mappings.length / total) * 100) : 100;
        $('kpiUnmapped').textContent     = unmappedSkus.length;
        $('kpiMapped').textContent       = mappings.length;
        $('kpiTotalSku').textContent     = total;
        $('kpiSkuCoverage').textContent  = coverage + '%';
        $('kpiUnmapped').style.color     = unmappedSkus.length > 0 ? '#dc2626' : '#16a34a';
    }

    function renderUnmapped() {
        const body = $('unmappedBody');
        if (!unmappedSkus.length) {
            body.innerHTML = '<div class="oc-empty" style="color:#16a34a">✓ Semua SKU sudah dipetakan.</div>';
            return;
        }
        body.innerHTML = `
        <div class="gf-table-scroll">
        <table class="gf-clean-table w-100">
            <thead><tr>
                <th>Marketplace SKU</th><th>Nama Item (Marketplace)</th>
                <th class="text-center">Jumlah Order</th><th class="text-end">Aksi</th>
            </tr></thead>
            <tbody>
            ${unmappedSkus.map(u => `<tr>
                <td><code class="fw-bold" style="color:#dc2626">${esc(u.sku)}</code></td>
                <td style="font-size:.82rem;color:var(--gf-muted)">${esc(u.item_name||'—')}</td>
                <td class="text-center">
                    <span class="oc-badge oc-badge-amber">${u.order_count} order</span>
                </td>
                <td class="text-end">
                    <button class="btn btn-dark btn-sm" style="border-radius:999px;font-size:.73rem;font-weight:700"
                        onclick="ocOpenAddMapping('${esc(u.sku)}')">+ Map</button>
                </td>
            </tr>`).join('')}
            </tbody>
        </table>
        </div>
        <div class="gf-table-foot"><span class="gf-table-foot-hint">${unmappedSkus.length} SKU belum dipetakan</span></div>`;
    }

    function renderMappings() {
        const body = $('mappingBody');
        if (!mappings.length) {
            body.innerHTML = '<div class="oc-empty">Belum ada mapping. Klik "+ Tambah Mapping" untuk mulai.</div>';
            return;
        }
        body.innerHTML = `
        <div class="gf-table-scroll">
        <table class="gf-clean-table w-100">
            <thead><tr>
                <th>Marketplace SKU</th><th>Channel</th><th>Item Internal</th><th>Catatan</th><th></th>
            </tr></thead>
            <tbody>
            ${mappings.map(m => `<tr>
                <td><code class="fw-bold">${esc(m.marketplace_sku)}</code></td>
                <td>${m.channel_code ? channelPill({code:m.channel_code, name:m.channel_code}) : '<span class="text-muted" style="font-size:.78rem">Semua</span>'}</td>
                <td>
                    ${m.item ? `<span class="fw-bold">${esc(m.item.code)}</span> <span class="text-muted" style="font-size:.78rem">${esc(m.item.name)}</span>` : '—'}
                </td>
                <td style="font-size:.78rem;color:var(--gf-muted)">${esc(m.notes||'')}</td>
                <td class="text-end">
                    <button class="btn btn-light border btn-sm" style="border-radius:999px;font-size:.72rem;color:#dc2626"
                        onclick="ocDeleteMapping(${m.id})">Hapus</button>
                </td>
            </tr>`).join('')}
            </tbody>
        </table>
        </div>
        <div class="gf-table-foot"><span class="gf-table-foot-hint">${mappings.length} mapping</span></div>`;
    }

    window.ocOpenAddMapping = function (prefillSku = '') {
        $('mapSku').value = prefillSku; $('mapChannel').value = '';
        $('mapItemId').value = ''; $('mapItemSelected').textContent = '';
        $('mapItemSearch').value = ''; $('mapNotes').value = '';
        $('mapRecommendations').style.display = 'none';
        $('mapRecoList').innerHTML = '';
        $('mapNewItemForm').style.display = 'none';
        $('mapNewItemAlert').className = 'alert d-none';
        delete $('mappingModal').dataset.editLineId;
        new bootstrap.Modal($('mappingModal')).show();
        if (prefillSku) setTimeout(() => ocFetchRecommendations(prefillSku), 200);
    };

    // Rekomendasi item berdasarkan SKU
    async function ocFetchRecommendations(sku) {
        $('mapNewItemForm').style.display = 'none';
        if (!sku || sku.length < 2) {
            $('mapRecommendations').style.display = 'none';
            return;
        }

        // Coba beberapa variasi query: "K3BLK-1" → "K3BLK", strip trailing digits, dll
        const queries = new Set();
        queries.add(sku);
        queries.add(sku.split('-')[0]);
        queries.add(sku.replace(/[-_]\d+$/, ''));
        queries.add(sku.replace(/\d+$/, ''));

        let results = [];
        for (const q of queries) {
            if (!q || q.length < 2) continue;
            const items = await api('/api/sku-mappings/search-items?q=' + encodeURIComponent(q)).catch(() => []);
            items.forEach(i => { if (!results.find(r => r.id === i.id)) results.push(i); });
            if (results.length >= 6) break;
        }

        const box  = $('mapRecommendations');
        const list = $('mapRecoList');

        if (!results.length) {
            box.style.display = 'none';
            // Tidak ada item ditemukan → tampilkan form buat item baru
            const prefix = sku.split('-')[0].replace(/\d+$/, '');
            $('newItemCode').value = prefix.toUpperCase();
            $('newItemName').value = '';
            $('mapNewItemAlert').className = 'alert d-none';
            $('mapNewItemForm').style.display = 'block';
            return;
        }

        $('mapNewItemForm').style.display = 'none';
        box.style.display = 'block';
        list.innerHTML = results.slice(0, 6).map(i => `
            <button type="button" class="oc-reco-chip" onclick="ocSelectRecoItem(${i.id},'${esc(i.code)}','${esc(i.name)}')">
                <span class="oc-reco-chip-code">${esc(i.code)}</span>
                <span class="oc-reco-chip-name">${esc(i.name)}</span>
            </button>
        `).join('');
    }

    window.ocSelectRecoItem = function (id, code, name) {
        $('mapItemId').value = id;
        $('mapItemSearch').value = code;
        $('mapItemSelected').textContent = '✓ ' + code + ' — ' + name;
        $('mapItemResults').style.display = 'none';
        $('mapNewItemForm').style.display = 'none';
        // Highlight chip yang dipilih
        document.querySelectorAll('#mapRecoList .oc-reco-chip').forEach(c => {
            c.classList.toggle('is-selected', c.querySelector('.oc-reco-chip-code')?.textContent === code);
        });
    };

    window.ocQuickCreateItem = async function () {
        const code = $('newItemCode').value.trim().toUpperCase();
        const name = $('newItemName').value.trim();
        const unit = $('newItemUnit').value.trim() || 'pcs';
        const alertEl = $('mapNewItemAlert');

        if (!code || !name) {
            alertEl.className = 'alert alert-warning';
            alertEl.textContent = 'Isi kode dan nama item.';
            return;
        }

        alertEl.className = 'alert d-none';
        try {
            const item = await api('/api/sku-mappings/quick-create-item', {
                method: 'POST',
                body: JSON.stringify({ code, name, unit }),
            });
            // Pilih item yang baru dibuat
            $('mapItemId').value = item.id;
            $('mapItemSearch').value = item.code;
            $('mapItemSelected').textContent = '✓ ' + item.code + ' — ' + item.name;
            $('mapNewItemForm').style.display = 'none';
            alertEl.className = 'alert d-none';
        } catch (e) {
            alertEl.className = 'alert alert-danger';
            alertEl.textContent = e.message;
        }
    };

    // Juga trigger rekomendasi saat mapSku diketik manual
    let skuRecoTimer;
    $('mapSku').addEventListener('input', function () {
        clearTimeout(skuRecoTimer);
        skuRecoTimer = setTimeout(() => ocFetchRecommendations(this.value.trim()), 400);
    });

    // Item search autocomplete
    let searchTimer;
    $('mapItemSearch').addEventListener('input', function () {
        clearTimeout(searchTimer);
        const q = this.value.trim();
        if (q.length < 2) { $('mapItemResults').style.display='none'; return; }
        searchTimer = setTimeout(async () => {
            const items = await api('/api/sku-mappings/search-items?q=' + encodeURIComponent(q)).catch(() => []);
            const box = $('mapItemResults');
            if (!items.length) { box.style.display='none'; return; }
            box.style.display = 'block';
            box.innerHTML = items.map(i =>
                `<div class="p-2 border-bottom" style="cursor:pointer;font-size:.82rem"
                    onmousedown="ocSelectItem(${i.id},'${esc(i.code)}','${esc(i.name)}')">
                    <strong>${esc(i.code)}</strong> — ${esc(i.name)}
                </div>`
            ).join('');
        }, 250);
    });

    window.ocSelectItem = function (id, code, name) {
        $('mapItemId').value = id;
        $('mapItemSearch').value = code;
        $('mapItemSelected').textContent = '✓ ' + code + ' — ' + name;
        $('mapItemResults').style.display = 'none';
    };

    window.ocSaveMapping = async function () {
        const sku    = $('mapSku').value.trim();
        const itemId = $('mapItemId').value;
        const editLineId = $('mappingModal').dataset.editLineId;

        if (!sku || !itemId) { alert('Isi SKU dan pilih item.'); return; }

        try {
            await api('/api/sku-mappings', {
                method: 'POST',
                body: JSON.stringify({
                    marketplace_sku: sku,
                    channel_code: $('mapChannel').value || null,
                    item_id: parseInt(itemId),
                    notes: $('mapNotes').value || null,
                })
            });

            // Jika dibuka dari edit line fulfillment, update line sekalian
            if (editLineId && $('mappingModal').dataset.editFulfillId) {
                await api(`/api/fulfillments/${$('mappingModal').dataset.editFulfillId}/lines/${editLineId}`, {
                    method: 'PATCH', body: JSON.stringify({ item_id: parseInt(itemId) })
                });
                // Reload detail fulfillment
                const f = await api('/api/fulfillments/' + $('mappingModal').dataset.editFulfillId);
                renderFulfillmentDetail(f);
            }

            bootstrap.Modal.getInstance($('mappingModal'))?.hide();
            await ocLoadMappings();
            // Auto re-apply mapping ke fulfillment yang masih unmapped
            api('/api/fulfillments/remap-all', { method: 'POST' })
                .then(d => { if (d.resolved > 0) ocLoadFulfillments(); })
                .catch(() => {});
        } catch (e) { alert(e.message); }
    };

    window.ocDeleteMapping = async function (id) {
        if (!confirm('Hapus mapping ini?')) return;
        await api('/api/sku-mappings/' + id, { method: 'DELETE' }).catch(e => alert(e.message));
        await ocLoadMappings();
    };

    window.ocRemapAll = async function () {
        const btn = $('remapAllBtn');
        if (btn) { btn.disabled = true; btn.textContent = '⚡ Memproses…'; }
        try {
            const d = await api('/api/fulfillments/remap-all', { method: 'POST' });
            await ocLoadFulfillments();
            if (btn) {
                btn.textContent = `✓ ${d.resolved} resolved`;
                setTimeout(() => { btn.disabled = false; btn.textContent = '⚡ Apply Mapping'; }, 2500);
            }
        } catch (e) {
            if (btn) { btn.disabled = false; btn.textContent = '⚡ Apply Mapping'; }
            alert(e.message);
        }
    };

    // ── Init ──────────────────────────────────────────────────────────────
    if (INITIAL_TAB === 'toko')        ocLoadStores();
    if (INITIAL_TAB === 'orders')      ocLoadOrders();
    if (INITIAL_TAB === 'fulfillment') ocLoadFulfillments();
    if (INITIAL_TAB === 'sku-mapping') ocLoadMappings();

})();
</script>
@endpush
