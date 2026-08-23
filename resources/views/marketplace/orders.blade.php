@extends('layouts.app')
@section('title', 'Marketplace • Order Lokal')

@include('marketplace._shared')

@push('head')
<link rel="stylesheet" href="{{ asset('css/marketplace-orders.css?v=' . time()) }}">
@endpush

@section('content')
    @if(isset($isDummy) && $isDummy)
    <div style="background-color: #fff3cd; color: #856404; padding: 10px 15px; border-radius: 6px; border: 1px solid #ffeeba; margin-bottom: 15px; display: flex; align-items: center; justify-content: space-between;">
        <div>
            <strong>🧪 MENGGUNAKAN DUMMY MODE</strong><br>
            <span style="font-size: 0.9em;">Halaman ini sedang menggunakan data pesanan simulasi. Aksi cetak resi dan lainnya tidak akan berdampak pada production/Shopee.</span>
        </div>
        <a href="?" style="background: #856404; color: #fff; padding: 5px 10px; border-radius: 4px; text-decoration: none; font-size: 0.85em; font-weight: bold;">Keluar Dummy</a>
    </div>
    @endif

    {{-- ── TOPBAR ── --}}
    @include('marketplace.partials.orders-topbar')

    @if(auth()->user()?->role === 'owner')
    {{-- ══ DEV TOOLS PANEL ══════════════════════════════════════════════════ --}}
    <div id="devPanel" style="display:none;background:#faf5ff;border:1px solid #ddd6fe;border-radius:8px;padding:1rem 1.25rem;margin-bottom:1rem">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:.85rem">
            <div style="font-size:.8rem;font-weight:800;color:#7c3aed;letter-spacing:.05em">🛠 DEV TOOLS — OWNER ONLY</div>
            <div id="devStats" style="font-size:.73rem;color:#6b7280">—</div>
        </div>
        <div style="display:flex;flex-wrap:wrap;gap:.5rem;align-items:center">
            <button class="btn-ship-outline" id="btnSeedOrders" onclick="devSeedOrders()" style="color:#16a34a!important; border-color:#bbf7d0!important;">📥 Seed Orders</button>
            <button class="btn-ship-outline" id="btnResetFulfillments" onclick="devResetFulfillments()" style="color:#a16207!important; border-color:#fde68a!important;" title="Hapus semua fulfillments, orders tetap ada">🔄 Reset Fulfillments</button>
            <button class="btn-fresh" id="btnFreshOrders" onclick="devFreshOrders()" title="Hapus SEMUA orders + fulfillments">🗑 Fresh All</button>
            <button class="btn-ship-outline" id="btnRemapItems" onclick="devRemapItems()" style="color:#6d28d9!important; border-color:#c4b5fd!important;" title="Re-resolve semua mapping_status + cost_status item berdasarkan SKU Mapping">🔁 Remap Items</button>
            <div style="width:1px;height:20px;background:#e2e8f0;margin:0 .15rem"></div>
            <a href="/sales/shipments" class="btn-ship-outline" style="color:var(--shp-accent)!important; border-color:var(--shp-border)!important; text-decoration:none;">📋 Buka Shipment →</a>
        </div>
    </div>
    @endif

    {{-- Alert SOP Baru --}}
    @if(false)
    <div style="background:#eff6ff; border:1px solid #bfdbfe; border-radius:8px; padding:.75rem 1rem; margin-bottom:1rem; display:flex; gap:.75rem; align-items:flex-start">
        <div style="font-size:1.2rem">📦</div>
        <div>
            <div style="font-size:.85rem; font-weight:800; color:#1e40af; margin-bottom:.15rem">SOP BARU PENGIRIMAN</div>
            <div style="font-size:.78rem; color:#1d4ed8; line-height:1.4">
                Proses packing dan potong stok kini dilakukan lewat menu <strong><a href="/sales/shipments" style="color:#1d4ed8; text-decoration:underline">Shipment</a></strong>. Gunakan modul Shipment untuk membuat Draft (Batch) pengiriman.
            </div>
        </div>
    </div>
    @endif

    {{-- TABS (Replacement for redundant KPI cards) --}}
    @include('marketplace.partials.orders-tabs')
    @include('marketplace.partials.orders-toolbar')

    <div class="card-main">
        <div id="ordersBody">
            <div class="prod-tab-loading"><span class="prod-tab-spinner"></span> Memuat…</div>
        </div>
    </div> <!-- end .card-main -->
    @include('marketplace.partials.orders-modals')
@endsection

@push('scripts')
<script>
    window.IS_DUMMY_MODE = @json($isDummy ?? false);
</script>
<script src="{{ asset('js/marketplace-orders.js?v=' . time()) }}"></script>
@endpush
