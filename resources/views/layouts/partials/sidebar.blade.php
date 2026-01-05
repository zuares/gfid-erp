{{-- resources/views/layouts/partials/sidebar.blade.php --}}
@php
    $userRole = auth()->user()->role ?? null;

    $isOwner = $userRole === 'owner';
    $isOperating = $userRole === 'operating';
    $isAdmin = $userRole === 'admin';

    // ===== OWNER collapse open states =====
    $poOpen = request()->routeIs('purchasing.purchase_orders.*');
    $grnOpen = request()->routeIs('purchasing.purchase_receipts.*');

    $masterOpen = request()->routeIs('master.customers.*') || request()->routeIs('master.items.*');

    $marketplaceOpen = request()->routeIs('marketplace.orders.*');

    $salesInvoiceOpen = request()->routeIs('sales.invoices.*');
    $salesShipmentOpen = request()->routeIs('sales.shipments.*');
    $salesShipmentReturnOpen = request()->routeIs('sales.shipment_returns.*');

    // ✅ buka group Sales kalau di halaman laporan pengiriman juga
    $salesReportOpen = request()->routeIs('sales.reports.*') || request()->routeIs('sales.shipments.report');

    $salesOpen = $salesInvoiceOpen || $salesShipmentOpen || $salesShipmentReturnOpen || $salesReportOpen;

    $payrollOpen =
        request()->routeIs('payroll.cutting.*') ||
        request()->routeIs('payroll.sewing.*') ||
        request()->routeIs('payroll.piece_rates.*') ||
        request()->routeIs('payroll.reports.*');

    $costingOpen = request()->routeIs('costing.hpp.*') || request()->routeIs('costing.production_cost_periods.*');
    $financeReportsOpen = $salesReportOpen;

    $invStocksOpen = request()->routeIs('inventory.stocks.*');
    $invOpnameOpen = request()->routeIs('inventory.stock_opnames.*');

    $invOwnerExtrasOpen =
        request()->routeIs('inventory.stock_card.*') ||
        request()->routeIs('inventory.transfers.*') ||
        request()->routeIs('inventory.external_transfers.*') ||
        request()->routeIs('inventory.adjustments.*');

    $invOpen = $invStocksOpen || $invOpnameOpen || ($isOwner && $invOwnerExtrasOpen);

    $stockReqOpen = request()->routeIs('rts.stock-requests.*') || request()->routeIs('prd.stock-requests.*');

    // ===== PRODUCTION flags =====
    $prodCutOpen = request()->routeIs('production.cutting_jobs.*');

    // ✅ UPDATED: semua sewing sekarang ada di production.sewing_*
    $prodSewOpen =
        request()->routeIs('production.sewing.pickups.*') ||
        request()->routeIs('production.sewing.returns.*') ||
        request()->routeIs('production.sewing.adjustments.*') ||
        request()->routeIs('production.sewing.reports.*');

    $prodFinOpen =
        request()->routeIs('production.finishing_jobs.*') ||
        request()->routeIs('production.finishing_jobs.bundles_ready') ||
        request()->routeIs('production.finishing_jobs.report_per_item*');

    $prodPackOpen =
        request()->routeIs('production.packing_jobs.*') || request()->routeIs('production.packing_jobs.ready_items');

    $prodQcOpen = request()->routeIs('production.qc.*');

    // production-wide reports (ProductionReportController) => tetap production.reports.*
    $prodReportOpen =
        request()->routeIs('production.reports.daily_production') ||
        request()->routeIs('production.reports.reject_detail') ||
        request()->routeIs('production.reports.wip_sewing_age') ||
        request()->routeIs('production.reports.sewing_per_item') ||
        request()->routeIs('production.reports.finishing_jobs') ||
        request()->routeIs('production.reports.production_flow_dashboard');

    // agregat: kalau salah satu menu produksi aktif, dropdown Production dibuka
    $prodOpen = $prodCutOpen || $prodSewOpen || $prodFinOpen || $prodPackOpen || $prodQcOpen || $prodReportOpen;
@endphp

<style>
    @media (min-width: 992px) {
        .sidebar-modern {
            position: fixed;
            top: 0;
            left: 0;
            width: 240px;
            height: 100vh;
            padding: 1rem 1rem 2rem;
            display: flex;
            flex-direction: column;
            gap: 1rem;
            background: color-mix(in srgb, var(--card) 92%, var(--bg) 8%);
            backdrop-filter: blur(14px);
            border-right: 1px solid rgba(148, 163, 184, .35);
            box-shadow: 8px 0 24px rgba(15, 23, 42, .05), 2px 0 8px rgba(15, 23, 42, .03);
            border-radius: 0 22px 22px 0;
            z-index: 1030;
            overflow-y: auto;
            scrollbar-width: thin;
            scrollbar-color: rgba(148, 163, 184, .4) transparent;
        }

        .app-main {
            margin-left: 240px;
        }
    }

    .sidebar-modern {
        display: none;
    }

    @media (min-width: 992px) {
        .sidebar-modern {
            display: flex;
        }
    }

    .sidebar-modern::-webkit-scrollbar {
        width: 6px;
    }

    .sidebar-modern::-webkit-scrollbar-thumb {
        background: rgba(148, 163, 184, .35);
        border-radius: 20px;
    }

    .sidebar-brand {
        font-size: 1.3rem;
        font-weight: 700;
        padding: .8rem .3rem 1.1rem;
        color: var(--text);
        letter-spacing: -.03em;
    }

    .menu-label {
        color: var(--muted);
        padding-left: .5rem;
        margin-bottom: .25rem;
        letter-spacing: .08em;
        font-size: .7rem;
    }

    .sidebar-nav {
        list-style: none;
        margin: 0;
        padding: 0;
    }

    .sidebar-link {
        display: flex;
        align-items: center;
        gap: .55rem;
        padding: .6rem .9rem;
        border-radius: 14px;
        color: var(--text);
        text-decoration: none;
        font-size: .93rem;
        transition: background .18s ease, box-shadow .18s ease, transform .12s ease, color .18s ease;
    }

    .sidebar-link .icon {
        width: 22px;
        font-size: 1.05rem;
        text-align: center;
    }

    .sidebar-link:hover {
        background: color-mix(in srgb, var(--accent-soft) 18%, var(--card) 82%);
        box-shadow: inset 0 0 0 1px var(--line);
        transform: translateX(1px);
    }

    .sidebar-link.active {
        background: transparent;
        font-weight: 600;
        box-shadow: inset 2px 0 0 var(--accent);
        color: var(--accent);
    }

    .sidebar-toggle {
        cursor: pointer;
        border: 0;
        width: 100%;
        background: transparent;
        text-align: left;
    }

    .sidebar-toggle .chevron {
        margin-left: auto;
        font-size: .8rem;
        opacity: .8;
        transition: transform .18s ease;
    }

    .sidebar-toggle[aria-expanded="true"] .chevron {
        transform: rotate(90deg);
    }

    .sidebar-toggle.is-open {
        background: transparent;
        box-shadow: none;
        color: var(--accent);
        font-weight: 600;
    }

    .sidebar-toggle.is-open .icon {
        color: var(--accent);
    }

    .sidebar-link-sub {
        position: relative;
        font-size: .86rem;
        padding: .4rem .9rem .4rem 2.3rem;
        opacity: .95;
        border-radius: 10px;
    }

    .sidebar-link-sub .icon {
        width: 18px;
        font-size: .9rem;
    }

    .sidebar-link-sub:hover {
        background: color-mix(in srgb, var(--accent-soft) 16%, var(--card) 84%);
        box-shadow: inset 0 0 0 1px var(--line);
    }

    .sidebar-link-sub.active {
        background: transparent;
        font-weight: 600;
        box-shadow: inset 2px 0 0 var(--accent);
        opacity: 1;
        color: var(--accent);
    }

    .sidebar-link-sub.active::before {
        content: '';
        position: absolute;
        left: 1.4rem;
        top: 50%;
        transform: translateY(-50%);
        width: 6px;
        height: 6px;
        border-radius: 999px;
        background: var(--accent);
    }

    .simple-group {
        margin-top: .4rem;
    }
</style>

<aside class="sidebar-modern flex-column">
    <div class="sidebar-brand">GFID</div>

    <ul class="sidebar-nav">
        {{-- DASHBOARD --}}
        <li>
            <x-sidebar.simple-link href="{{ route('dashboard') }}" icon="🏠" :active="request()->routeIs('dashboard')">
                Dashboard
            </x-sidebar.simple-link>
        </li>

        {{-- ===========================
            ADMIN / OPERATING (NO DROPDOWN)
        ============================ --}}
        @if ($isAdmin || $isOperating)
            <x-sidebar.label text="Inventory" />
            <li class="simple-group">
                <x-sidebar.simple-link href="{{ route('inventory.stocks.items') }}" icon="📦" :active="request()->routeIs('inventory.stocks.items')">
                    Stok Barang
                </x-sidebar.simple-link>

                <x-sidebar.simple-link href="{{ route('inventory.stock_opnames.index') }}" icon="📊"
                    :active="request()->routeIs('inventory.stock_opnames.*')">
                    Stock Opname
                </x-sidebar.simple-link>
            </li>

            <x-sidebar.label text="Stock Requests" />
            <li class="simple-group">
                @if ($isAdmin)
                    <x-sidebar.simple-link href="{{ route('rts.stock-requests.index') }}" icon="🛒"
                        :active="request()->routeIs('rts.stock-requests.*')">
                        Permintaan Stock (RTS)
                    </x-sidebar.simple-link>
                @endif

                @if ($isOperating)
                    <x-sidebar.simple-link href="{{ route('prd.stock-requests.index') }}" icon="🏭"
                        :active="request()->routeIs('prd.stock-requests.*')">
                        Proses Stock Request (PRD)
                    </x-sidebar.simple-link>
                @endif
            </li>

            {{-- ✅ Sales (Admin) --}}
            @if ($isAdmin)
                <x-sidebar.label text="Sales" />
                <li class="simple-group">
                    <x-sidebar.simple-link href="{{ route('sales.shipments.index') }}" icon="🚚" :active="request()->routeIs('sales.shipments.*')">
                        Shipments
                    </x-sidebar.simple-link>

                    <x-sidebar.simple-link href="{{ route('sales.shipment_returns.index') }}" icon="🔁"
                        :active="request()->routeIs('sales.shipment_returns.*')">
                        Retur Shipment
                    </x-sidebar.simple-link>
                </li>
            @endif

            {{-- Production (Operating) --}}
            @if ($isOperating)
                <x-sidebar.label text="Production" />
                <li class="simple-group">
                    <x-sidebar.simple-link href="{{ route('production.cutting_jobs.index') }}" icon="✂️"
                        :active="request()->routeIs('production.cutting_jobs.*')">
                        Daftar Cutting Jobs
                    </x-sidebar.simple-link>

                    {{-- ✅ UPDATED --}}
                    <x-sidebar.simple-link href="{{ route('production.sewing.pickups.index') }}" icon="🧵"
                        :active="request()->routeIs('production.sewing.pickups.*')">
                        Daftar Sewing Pickups
                    </x-sidebar.simple-link>

                    {{-- ✅ UPDATED --}}
                    <x-sidebar.simple-link href="{{ route('production.sewing.returns.index') }}" icon="📥"
                        :active="request()->routeIs('production.sewing.returns.*')">
                        Daftar Sewing Returns
                    </x-sidebar.simple-link>

                    {{-- ✅ NEW --}}
                    <x-sidebar.simple-link href="{{ route('production.sewing.adjustments.index') }}" icon="🧮"
                        :active="request()->routeIs('production.sewing.adjustments.*')">
                        Progress Adjustments
                    </x-sidebar.simple-link>

                    <x-sidebar.simple-link href="{{ route('production.finishing_jobs.index') }}" icon="🧶"
                        :active="request()->routeIs('production.finishing_jobs.*')">
                        Daftar Finishing
                    </x-sidebar.simple-link>

                    <x-sidebar.simple-link href="{{ route('production.qc.index') }}" icon="✅" :active="request()->routeIs('production.qc.*')">
                        QC Cutting
                    </x-sidebar.simple-link>
                </li>
            @endif

            {{-- ===========================
            OWNER (FULL + DROPDOWN)
        ============================ --}}
        @elseif ($isOwner)
            {{-- MASTER --}}
            <x-sidebar.label text="Master Data" />
            <li class="mb-1">
                <button class="sidebar-link sidebar-toggle {{ $masterOpen ? 'is-open' : '' }}" type="button"
                    data-bs-toggle="collapse" data-bs-target="#navMaster"
                    aria-expanded="{{ $masterOpen ? 'true' : 'false' }}" aria-controls="navMaster">
                    <span class="icon">🗂️</span>
                    <span>Master</span>
                    <span class="chevron">▸</span>
                </button>

                <div class="collapse {{ $masterOpen ? 'show' : '' }}" id="navMaster">
                    <a href="{{ route('master.items.index') }}"
                        class="sidebar-link sidebar-link-sub {{ request()->routeIs('master.items.*') ? 'active' : '' }}">
                        <span class="icon">📦</span><span>Items</span>
                    </a>
                    <a href="{{ route('master.customers.index') }}"
                        class="sidebar-link sidebar-link-sub {{ request()->routeIs('master.customers.*') ? 'active' : '' }}">
                        <span class="icon">👤</span><span>Customers</span>
                    </a>
                </div>
            </li>

            {{-- PURCHASING --}}
            <x-sidebar.label text="Purchasing" />
            <li class="mb-1">
                <button class="sidebar-link sidebar-toggle {{ $poOpen ? 'is-open' : '' }}" type="button"
                    data-bs-toggle="collapse" data-bs-target="#navPurchasingPO"
                    aria-expanded="{{ $poOpen ? 'true' : 'false' }}" aria-controls="navPurchasingPO">
                    <span class="icon">🧾</span>
                    <span>Purchase Orders</span>
                    <span class="chevron">▸</span>
                </button>

                <div class="collapse {{ $poOpen ? 'show' : '' }}" id="navPurchasingPO">
                    <a href="{{ route('purchasing.purchase_orders.index') }}"
                        class="sidebar-link sidebar-link-sub {{ request()->routeIs('purchasing.purchase_orders.index') ? 'active' : '' }}">
                        <span class="icon">≡</span><span>Daftar PO</span>
                    </a>
                    <a href="{{ route('purchasing.purchase_orders.create') }}"
                        class="sidebar-link sidebar-link-sub {{ request()->routeIs('purchasing.purchase_orders.create') ? 'active' : '' }}">
                        <span class="icon">＋</span><span>PO Baru</span>
                    </a>
                </div>
            </li>

            <li class="mb-1">
                <button class="sidebar-link sidebar-toggle {{ $grnOpen ? 'is-open' : '' }}" type="button"
                    data-bs-toggle="collapse" data-bs-target="#navPurchasingGRN"
                    aria-expanded="{{ $grnOpen ? 'true' : 'false' }}" aria-controls="navPurchasingGRN">
                    <span class="icon">📥</span>
                    <span>Goods Receipts (GRN)</span>
                    <span class="chevron">▸</span>
                </button>

                <div class="collapse {{ $grnOpen ? 'show' : '' }}" id="navPurchasingGRN">
                    <a href="{{ route('purchasing.purchase_receipts.index') }}"
                        class="sidebar-link sidebar-link-sub {{ request()->routeIs('purchasing.purchase_receipts.index') ? 'active' : '' }}">
                        <span class="icon">≡</span><span>Daftar GRN</span>
                    </a>
                    <a href="{{ route('purchasing.purchase_receipts.create') }}"
                        class="sidebar-link sidebar-link-sub {{ request()->routeIs('purchasing.purchase_receipts.create') ? 'active' : '' }}">
                        <span class="icon">＋</span><span>GRN Baru</span>
                    </a>
                </div>
            </li>

            {{-- SALES & MARKETPLACE --}}
            <x-sidebar.label text="Sales & Marketplace" />
            <li class="mb-1">
                <button class="sidebar-link sidebar-toggle {{ $marketplaceOpen ? 'is-open' : '' }}" type="button"
                    data-bs-toggle="collapse" data-bs-target="#navMarketplace"
                    aria-expanded="{{ $marketplaceOpen ? 'true' : 'false' }}" aria-controls="navMarketplace">
                    <span class="icon">🛒</span>
                    <span>Marketplace Orders</span>
                    <span class="chevron">▸</span>
                </button>

                <div class="collapse {{ $marketplaceOpen ? 'show' : '' }}" id="navMarketplace">
                    <a href="{{ route('marketplace.orders.index') }}"
                        class="sidebar-link sidebar-link-sub {{ request()->routeIs('marketplace.orders.index') ? 'active' : '' }}">
                        <span class="icon">≡</span><span>Daftar Order</span>
                    </a>
                    <a href="{{ route('marketplace.orders.create') }}"
                        class="sidebar-link sidebar-link-sub {{ request()->routeIs('marketplace.orders.create') ? 'active' : '' }}">
                        <span class="icon">＋</span><span>Order Manual</span>
                    </a>
                </div>
            </li>

            <li class="mb-1">
                <button class="sidebar-link sidebar-toggle {{ $salesOpen ? 'is-open' : '' }}" type="button"
                    data-bs-toggle="collapse" data-bs-target="#navSales"
                    aria-expanded="{{ $salesOpen ? 'true' : 'false' }}" aria-controls="navSales">
                    <span class="icon">📑</span>
                    <span>Sales</span>
                    <span class="chevron">▸</span>
                </button>

                <div class="collapse {{ $salesOpen ? 'show' : '' }}" id="navSales">
                    <a href="{{ route('sales.invoices.index') }}"
                        class="sidebar-link sidebar-link-sub {{ request()->routeIs('sales.invoices.index') ? 'active' : '' }}">
                        <span class="icon">≡</span><span>Daftar Invoice</span>
                    </a>
                    <a href="{{ route('sales.invoices.create') }}"
                        class="sidebar-link sidebar-link-sub {{ request()->routeIs('sales.invoices.create') ? 'active' : '' }}">
                        <span class="icon">＋</span><span>Invoice Baru</span>
                    </a>

                    <div class="px-3 pt-2 pb-1 text-uppercase"
                        style="font-size:.68rem; letter-spacing:.12em; color:var(--muted);">
                        Shipments
                    </div>

                    <a href="{{ route('sales.shipments.index') }}"
                        class="sidebar-link sidebar-link-sub {{ request()->routeIs('sales.shipments.index') ? 'active' : '' }}">
                        <span class="icon">🚚</span><span>Daftar Shipment</span>
                    </a>
                    <a href="{{ route('sales.shipments.create') }}"
                        class="sidebar-link sidebar-link-sub {{ request()->routeIs('sales.shipments.create') ? 'active' : '' }}">
                        <span class="icon">＋</span><span>Shipment Baru</span>
                    </a>

                    <div class="px-3 pt-3 pb-1 text-uppercase"
                        style="font-size:.68rem; letter-spacing:.12em; color:var(--muted);">
                        Shipment Returns
                    </div>

                    <a href="{{ route('sales.shipment_returns.index') }}"
                        class="sidebar-link sidebar-link-sub {{ request()->routeIs('sales.shipment_returns.index') ? 'active' : '' }}">
                        <span class="icon">🔁</span><span>Daftar Retur</span>
                    </a>
                    <a href="{{ route('sales.shipment_returns.create') }}"
                        class="sidebar-link sidebar-link-sub {{ request()->routeIs('sales.shipment_returns.create') ? 'active' : '' }}">
                        <span class="icon">＋</span><span>Retur Shipment Baru</span>
                    </a>

                    <div class="px-3 pt-3 pb-1 text-uppercase"
                        style="font-size:.68rem; letter-spacing:.12em; color:var(--muted);">
                        Sales Reports
                    </div>

                    <a href="{{ route('sales.shipments.report') }}"
                        class="sidebar-link sidebar-link-sub {{ request()->routeIs('sales.shipments.report') ? 'active' : '' }}">
                        <span class="icon">📊</span><span>Laporan Pengiriman</span>
                    </a>
                </div>
            </li>

            {{-- INVENTORY (OWNER FULL) --}}
            <x-sidebar.label text="Inventory" />
            <li class="mb-1">
                <button class="sidebar-link sidebar-toggle {{ $invOpen ? 'is-open' : '' }}" type="button"
                    data-bs-toggle="collapse" data-bs-target="#navInventory"
                    aria-expanded="{{ $invOpen ? 'true' : 'false' }}" aria-controls="navInventory">
                    <span class="icon">📦</span>
                    <span>Inventory</span>
                    <span class="chevron">▸</span>
                </button>

                <div class="collapse {{ $invOpen ? 'show' : '' }}" id="navInventory">
                    <div class="px-3 pt-2 pb-1 text-uppercase"
                        style="font-size:.68rem; letter-spacing:.12em; color:var(--muted);">
                        Stock
                    </div>

                    <a href="{{ route('inventory.stocks.items') }}"
                        class="sidebar-link sidebar-link-sub {{ request()->routeIs('inventory.stocks.items') ? 'active' : '' }}">
                        <span class="icon">📦</span><span>Stok Barang</span>
                    </a>
                    <a href="{{ route('inventory.stocks.lots') }}"
                        class="sidebar-link sidebar-link-sub {{ request()->routeIs('inventory.stocks.lots') ? 'active' : '' }}">
                        <span class="icon">🎫</span><span>Stok per LOT</span>
                    </a>
                    <a href="{{ route('inventory.stock_card.index') }}"
                        class="sidebar-link sidebar-link-sub {{ request()->routeIs('inventory.stock_card.*') ? 'active' : '' }}">
                        <span class="icon">📋</span><span>Kartu Stok</span>
                    </a>
                    <a href="{{ route('inventory.transfers.index') }}"
                        class="sidebar-link sidebar-link-sub {{ request()->routeIs('inventory.transfers.index') ? 'active' : '' }}">
                        <span class="icon">🔁</span><span>Daftar Transfer</span>
                    </a>
                    <a href="{{ route('inventory.transfers.create') }}"
                        class="sidebar-link sidebar-link-sub {{ request()->routeIs('inventory.transfers.create') ? 'active' : '' }}">
                        <span class="icon">➕</span><span>Transfer Baru</span>
                    </a>
                    <a href="{{ route('inventory.adjustments.index') }}"
                        class="sidebar-link sidebar-link-sub {{ request()->routeIs('inventory.adjustments.*') ? 'active' : '' }}">
                        <span class="icon">⚖️</span><span>Inventory Adjustments</span>
                    </a>

                    <div class="px-3 pt-3 pb-1 text-uppercase"
                        style="font-size:.68rem; letter-spacing:.12em; color:var(--muted);">
                        Opname
                    </div>

                    <a href="{{ route('inventory.stock_opnames.index') }}"
                        class="sidebar-link sidebar-link-sub {{ request()->routeIs('inventory.stock_opnames.*') ? 'active' : '' }}">
                        <span class="icon">📊</span><span>Stock Opname</span>
                    </a>
                    <a href="{{ route('inventory.stock_opnames.create') }}"
                        class="sidebar-link sidebar-link-sub {{ request()->routeIs('inventory.stock_opnames.create') ? 'active' : '' }}">
                        <span class="icon">＋</span><span>Stock Opname Baru</span>
                    </a>

                    <div class="px-3 pt-3 pb-1 text-uppercase"
                        style="font-size:.68rem; letter-spacing:.12em; color:var(--muted);">
                        External
                    </div>

                    <a href="{{ route('inventory.external_transfers.index') }}"
                        class="sidebar-link sidebar-link-sub {{ request()->routeIs('inventory.external_transfers.index') ? 'active' : '' }}">
                        <span class="icon">🚚</span><span>Daftar External TF</span>
                    </a>
                    <a href="{{ route('inventory.external_transfers.create') }}"
                        class="sidebar-link sidebar-link-sub {{ request()->routeIs('inventory.external_transfers.create') ? 'active' : '' }}">
                        <span class="icon">➕</span><span>External TF Baru</span>
                    </a>
                </div>
            </li>

            {{-- STOCK REQUESTS (OWNER FULL) --}}
            <x-sidebar.label text="Stock Requests" />
            <li class="mb-1">
                <button class="sidebar-link sidebar-toggle {{ $stockReqOpen ? 'is-open' : '' }}" type="button"
                    data-bs-toggle="collapse" data-bs-target="#navInventoryStockRequests"
                    aria-expanded="{{ $stockReqOpen ? 'true' : 'false' }}" aria-controls="navInventoryStockRequests">
                    <span class="icon">📤</span>
                    <span>Stock Requests</span>
                    <span class="chevron">▸</span>
                </button>

                <div class="collapse {{ $stockReqOpen ? 'show' : '' }}" id="navInventoryStockRequests">
                    <a href="{{ route('rts.stock-requests.index') }}"
                        class="sidebar-link sidebar-link-sub {{ request()->routeIs('rts.stock-requests.*') ? 'active' : '' }}">
                        <span class="icon">🛒</span><span>Permintaan Stock (RTS)</span>
                    </a>
                    <a href="{{ route('prd.stock-requests.index') }}"
                        class="sidebar-link sidebar-link-sub {{ request()->routeIs('prd.stock-requests.*') ? 'active' : '' }}">
                        <span class="icon">🏭</span><span>Proses Stock Request (PRD)</span>
                    </a>
                </div>
            </li>

            {{-- PRODUCTION (OWNER) --}}
            <x-sidebar.label text="Production" />
            <li class="mb-1">
                <button class="sidebar-link sidebar-toggle {{ $prodOpen ? 'is-open' : '' }}" type="button"
                    data-bs-toggle="collapse" data-bs-target="#navProduction"
                    aria-expanded="{{ $prodOpen ? 'true' : 'false' }}" aria-controls="navProduction">
                    <span class="icon">🏭</span>
                    <span>Production</span>
                    <span class="chevron">▸</span>
                </button>

                <div class="collapse {{ $prodOpen ? 'show' : '' }}" id="navProduction">
                    <div class="px-3 pt-2 pb-1 text-uppercase"
                        style="font-size:.68rem; letter-spacing:.12em; color:var(--muted);">
                        Jobs
                    </div>

                    <a href="{{ route('production.cutting_jobs.index') }}"
                        class="sidebar-link sidebar-link-sub {{ request()->routeIs('production.cutting_jobs.*') ? 'active' : '' }}">
                        <span class="icon">✂️</span><span>Cutting Jobs</span>
                    </a>

                    {{-- ✅ UPDATED --}}
                    <a href="{{ route('production.sewing.pickups.index') }}"
                        class="sidebar-link sidebar-link-sub {{ request()->routeIs('production.sewing.pickups.*') ? 'active' : '' }}">
                        <span class="icon">🧵</span><span>Sewing Pickups</span>
                    </a>

                    {{-- ✅ UPDATED --}}
                    <a href="{{ route('production.sewing.returns.index') }}"
                        class="sidebar-link sidebar-link-sub {{ request()->routeIs('production.sewing.returns.*') ? 'active' : '' }}">
                        <span class="icon">📥</span><span>Sewing Returns</span>
                    </a>

                    {{-- ✅ NEW --}}
                    <a href="{{ route('production.sewing.adjustments.index') }}"
                        class="sidebar-link sidebar-link-sub {{ request()->routeIs('production.sewing.adjustments.*') ? 'active' : '' }}">
                        <span class="icon">🧮</span><span>Progress Adjustments</span>
                    </a>

                    <a href="{{ route('production.finishing_jobs.index') }}"
                        class="sidebar-link sidebar-link-sub {{ request()->routeIs('production.finishing_jobs.*') ? 'active' : '' }}">
                        <span class="icon">🧶</span><span>Finishing Jobs</span>
                    </a>

                    <a href="{{ route('production.packing_jobs.index') }}"
                        class="sidebar-link sidebar-link-sub {{ request()->routeIs('production.packing_jobs.*') ? 'active' : '' }}">
                        <span class="icon">📦</span><span>Packing Jobs</span>
                    </a>

                    <a href="{{ route('production.qc.index') }}"
                        class="sidebar-link sidebar-link-sub {{ request()->routeIs('production.qc.*') ? 'active' : '' }}">
                        <span class="icon">✅</span><span>QC Cutting</span>
                    </a>

                    {{-- ✅ Sewing Reports (route baru: production.sewing.reports.*) --}}
                    <div class="px-3 pt-3 pb-1 text-uppercase"
                        style="font-size:.68rem; letter-spacing:.12em; color:var(--muted);">
                        Sewing Reports
                    </div>

                    <a href="{{ route('production.sewing.reports.dashboard') }}"
                        class="sidebar-link sidebar-link-sub {{ request()->routeIs('production.sewing.reports.dashboard') ? 'active' : '' }}">
                        <span class="icon">📊</span><span>Sewing Dashboard</span>
                    </a>

                    <a href="{{ route('production.sewing.reports.operators') }}"
                        class="sidebar-link sidebar-link-sub {{ request()->routeIs('production.sewing.reports.operators') ? 'active' : '' }}">
                        <span class="icon">👥</span><span>Operator Summary</span>
                    </a>

                    <a href="{{ route('production.sewing.reports.outstanding') }}"
                        class="sidebar-link sidebar-link-sub {{ request()->routeIs('production.sewing.reports.outstanding') ? 'active' : '' }}">
                        <span class="icon">⏳</span><span>Outstanding WIP-SEW</span>
                    </a>

                    <a href="{{ route('production.sewing.reports.aging_wip_sew') }}"
                        class="sidebar-link sidebar-link-sub {{ request()->routeIs('production.sewing.reports.aging_wip_sew') ? 'active' : '' }}">
                        <span class="icon">📆</span><span>Aging WIP-SEW</span>
                    </a>

                    <a href="{{ route('production.sewing.reports.partial_pickup') }}"
                        class="sidebar-link sidebar-link-sub {{ request()->routeIs('production.sewing.reports.partial_pickup') ? 'active' : '' }}">
                        <span class="icon">🧩</span><span>Partial Pickup</span>
                    </a>

                    <a href="{{ route('production.sewing.reports.productivity') }}"
                        class="sidebar-link sidebar-link-sub {{ request()->routeIs('production.sewing.reports.productivity') ? 'active' : '' }}">
                        <span class="icon">📈</span><span>Productivity</span>
                    </a>

                    <a href="{{ route('production.sewing.reports.reject_analysis') }}"
                        class="sidebar-link sidebar-link-sub {{ request()->routeIs('production.sewing.reports.reject_analysis') ? 'active' : '' }}">
                        <span class="icon">🚫</span><span>Reject Analysis</span>
                    </a>

                    <a href="{{ route('production.sewing.reports.lead_time') }}"
                        class="sidebar-link sidebar-link-sub {{ request()->routeIs('production.sewing.reports.lead_time') ? 'active' : '' }}">
                        <span class="icon">⏱️</span><span>Lead Time</span>
                    </a>

                    <a href="{{ route('production.sewing.reports.operator_behavior') }}"
                        class="sidebar-link sidebar-link-sub {{ request()->routeIs('production.sewing.reports.operator_behavior') ? 'active' : '' }}">
                        <span class="icon">👀</span><span>Operator Behavior</span>
                    </a>

                    {{-- Production-wide reports (tetap) --}}
                    <div class="px-3 pt-3 pb-1 text-uppercase"
                        style="font-size:.68rem; letter-spacing:.12em; color:var(--muted);">
                        Chain / WIP Reports
                    </div>

                    <a href="{{ route('production.reports.production_flow_dashboard') }}"
                        class="sidebar-link sidebar-link-sub {{ request()->routeIs('production.reports.production_flow_dashboard') ? 'active' : '' }}">
                        <span class="icon">🌀</span><span>Flow Dashboard</span>
                    </a>

                    <a href="{{ route('production.reports.daily_production') }}"
                        class="sidebar-link sidebar-link-sub {{ request()->routeIs('production.reports.daily_production') ? 'active' : '' }}">
                        <span class="icon">📅</span><span>Daily Production</span>
                    </a>

                    <a href="{{ route('production.reports.reject_detail') }}"
                        class="sidebar-link sidebar-link-sub {{ request()->routeIs('production.reports.reject_detail') ? 'active' : '' }}">
                        <span class="icon">🧾</span><span>Reject Detail</span>
                    </a>

                    <a href="{{ route('production.reports.wip_sewing_age') }}"
                        class="sidebar-link sidebar-link-sub {{ request()->routeIs('production.reports.wip_sewing_age') ? 'active' : '' }}">
                        <span class="icon">📆</span><span>WIP Sewing Age</span>
                    </a>

                    <a href="{{ route('production.reports.sewing_per_item') }}"
                        class="sidebar-link sidebar-link-sub {{ request()->routeIs('production.reports.sewing_per_item') ? 'active' : '' }}">
                        <span class="icon">🧵</span><span>Sewing per Item</span>
                    </a>

                    <a href="{{ route('production.reports.finishing_jobs') }}"
                        class="sidebar-link sidebar-link-sub {{ request()->routeIs('production.reports.finishing_jobs') ? 'active' : '' }}">
                        <span class="icon">🧶</span><span>Finishing Jobs Report</span>
                    </a>

                    <a href="{{ route('production.finishing_jobs.report_per_item') }}"
                        class="sidebar-link sidebar-link-sub {{ request()->routeIs('production.finishing_jobs.report_per_item*') ? 'active' : '' }}">
                        <span class="icon">📦</span><span>Finishing per Item</span>
                    </a>
                </div>
            </li>

            {{-- FINANCE (OWNER) --}}
            <x-sidebar.label text="Finance" />
            <li class="mb-1">
                <button class="sidebar-link sidebar-toggle {{ $payrollOpen ? 'is-open' : '' }}" type="button"
                    data-bs-toggle="collapse" data-bs-target="#navFinancePayroll"
                    aria-expanded="{{ $payrollOpen ? 'true' : 'false' }}" aria-controls="navFinancePayroll">
                    <span class="icon">💰</span>
                    <span>Payroll</span>
                    <span class="chevron">▸</span>
                </button>

                <div class="collapse {{ $payrollOpen ? 'show' : '' }}" id="navFinancePayroll">
                    <a href="{{ route('payroll.cutting.index') }}"
                        class="sidebar-link sidebar-link-sub {{ request()->routeIs('payroll.cutting.*') ? 'active' : '' }}">
                        <span class="icon">✂️</span><span>Cutting Payroll</span>
                    </a>
                    <a href="{{ route('payroll.sewing.index') }}"
                        class="sidebar-link sidebar-link-sub {{ request()->routeIs('payroll.sewing.*') ? 'active' : '' }}">
                        <span class="icon">🧵</span><span>Sewing Payroll</span>
                    </a>
                    <a href="{{ route('payroll.piece_rates.index') }}"
                        class="sidebar-link sidebar-link-sub {{ request()->routeIs('payroll.piece_rates.*') ? 'active' : '' }}">
                        <span class="icon">📑</span><span>Piece Rates</span>
                    </a>
                </div>
            </li>

            <li class="mb-1">
                <button class="sidebar-link sidebar-toggle {{ $costingOpen ? 'is-open' : '' }}" type="button"
                    data-bs-toggle="collapse" data-bs-target="#navFinanceCosting"
                    aria-expanded="{{ $costingOpen ? 'true' : 'false' }}" aria-controls="navFinanceCosting">
                    <span class="icon">📉</span>
                    <span>Costing &amp; HPP</span>
                    <span class="chevron">▸</span>
                </button>

                <div class="collapse {{ $costingOpen ? 'show' : '' }}" id="navFinanceCosting">
                    <a href="{{ route('costing.hpp.index') }}"
                        class="sidebar-link sidebar-link-sub {{ request()->routeIs('costing.hpp.*') ? 'active' : '' }}">
                        <span class="icon">⚙️</span><span>HPP Finished Goods</span>
                    </a>
                    <a href="{{ route('costing.production_cost_periods.index') }}"
                        class="sidebar-link sidebar-link-sub {{ request()->routeIs('costing.production_cost_periods.*') ? 'active' : '' }}">
                        <span class="icon">📆</span><span>Production Cost Periods</span>
                    </a>
                </div>
            </li>
        @endif
    </ul>
</aside>
