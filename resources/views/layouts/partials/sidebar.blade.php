{{-- resources/views/layouts/partials/sidebar.blade.php --}}
@php
    $userRole = auth()->user()->role ?? null;

    // Flag untuk buka/tutup collapse per grup (tetap seperti sebelumnya)
    $poOpen = request()->routeIs('purchasing.purchase_orders.*');
    $grnOpen = request()->routeIs('purchasing.purchase_receipts.*');

    $invOpen =
        request()->routeIs('inventory.stock_card.*') ||
        request()->routeIs('inventory.transfers.*') ||
        request()->routeIs('inventory.stocks.*') ||
        request()->routeIs('inventory.stock_opnames.*') ||
        request()->routeIs('inventory.adjustments.*');

    $extInvOpen = request()->routeIs('inventory.external_transfers.*');

    $stockReqOpen = request()->routeIs('rts.stock-requests.*') || request()->routeIs('prd.stock-requests.*');

    $prodCutOpen = request()->routeIs('production.cutting_jobs.*');

    $prodSewOpen =
        request()->routeIs('production.sewing_pickups.*') ||
        request()->routeIs('production.sewing_returns.*') ||
        request()->routeIs('production.reports.operators') ||
        request()->routeIs('production.reports.outstanding') ||
        request()->routeIs('production.reports.aging_wip_sew') ||
        request()->routeIs('production.reports.productivity') ||
        request()->routeIs('production.reports.partial_pickup') ||
        request()->routeIs('production.reports.report_reject') ||
        request()->routeIs('production.reports.dashboard') ||
        request()->routeIs('production.reports.lead_time') ||
        request()->routeIs('production.reports.operator_behavior');

    $prodFinOpen =
        request()->routeIs('production.finishing_jobs.*') ||
        request()->routeIs('production.finishing_jobs.bundles_ready');

    $prodPackOpen =
        request()->routeIs('production.packing_jobs.*') || request()->routeIs('production.packing_jobs.ready_items');

    $prodQcOpen = request()->routeIs('production.qc.*');

    $prodReportOpen =
        request()->routeIs('production.reports.daily_production') ||
        request()->routeIs('production.reports.reject_detail') ||
        request()->routeIs('production.reports.wip_sewing_age') ||
        request()->routeIs('production.reports.sewing_per_item') ||
        request()->routeIs('production.reports.finishing_jobs') ||
        request()->routeIs('production.finishing_jobs.report_per_item') ||
        request()->routeIs('production.finishing_jobs.report_per_item_detail');

    $payrollOpen =
        request()->routeIs('payroll.cutting.*') ||
        request()->routeIs('payroll.sewing.*') ||
        request()->routeIs('payroll.piece_rates.*') ||
        request()->routeIs('payroll.reports.*');

    $costingOpen = request()->routeIs('costing.hpp.*') || request()->routeIs('costing.production_cost_periods.*');

    $masterOpen = request()->routeIs('master.customers.*') || request()->routeIs('master.items.*');

    $marketplaceOpen = request()->routeIs('marketplace.orders.*');

    $salesInvoiceOpen = request()->routeIs('sales.invoices.*');
    $salesShipmentOpen = request()->routeIs('sales.shipments.*');
    $salesReportOpen = request()->routeIs('sales.reports.*');
    $salesOpen = $salesInvoiceOpen || $salesShipmentOpen || $salesReportOpen;

    $financeReportsOpen = $salesReportOpen;
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
            box-shadow:
                8px 0 24px rgba(15, 23, 42, .05),
                2px 0 8px rgba(15, 23, 42, .03);
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
        transform: none;
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
</style>

<aside class="sidebar-modern flex-column">
    <div class="sidebar-brand">
        GFID
    </div>

    <ul class="sidebar-nav">
        {{-- DASHBOARD --}}
        <li>
            <a href="{{ route('dashboard') }}" class="sidebar-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <span class="icon">🏠</span>
                <span>Dashboard</span>
            </a>
        </li>

        {{-- MASTER DATA (boleh semua yang login) --}}
        <li class="mt-2 text-uppercase small menu-label">Master Data</li>

        <li class="mb-1">
            <button class="sidebar-link sidebar-toggle {{ $masterOpen ? 'is-open' : '' }}" type="button"
                data-bs-toggle="collapse" data-bs-target="#navMaster"
                aria-expanded="{{ $masterOpen ? 'true' : 'false' }}" aria-controls="navMaster">
                <span class="icon">🗂️</span>
                <span>Master</span>
                <span class="chevron">▸</span>
            </button>

            <div class="collapse {{ $masterOpen ? 'show' : '' }}" id="navMaster">
                {{-- Items --}}
                <a href="{{ route('master.items.index') }}"
                    class="sidebar-link sidebar-link-sub {{ request()->routeIs('master.items.*') ? 'active' : '' }}">
                    <span class="icon">📦</span>
                    <span>Items</span>
                </a>

                {{-- Customers --}}
                <a href="{{ route('master.customers.index') }}"
                    class="sidebar-link sidebar-link-sub {{ request()->routeIs('master.customers.*') ? 'active' : '' }}">
                    <span class="icon">👤</span>
                    <span>Customers</span>
                </a>
            </div>
        </li>

        {{-- PURCHASING (owner + admin) --}}
        @if (in_array($userRole, ['owner', 'admin']))
            <li class="mt-2 text-uppercase small menu-label">Purchasing</li>

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
                        <span class="icon">≡</span>
                        <span>Daftar PO</span>
                    </a>

                    <a href="{{ route('purchasing.purchase_orders.create') }}"
                        class="sidebar-link sidebar-link-sub {{ request()->routeIs('purchasing.purchase_orders.create') ? 'active' : '' }}">
                        <span class="icon">＋</span>
                        <span>PO Baru</span>
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
                        <span class="icon">≡</span>
                        <span>Daftar GRN</span>
                    </a>

                    <a href="{{ route('purchasing.purchase_receipts.create') }}"
                        class="sidebar-link sidebar-link-sub {{ request()->routeIs('purchasing.purchase_receipts.create') ? 'active' : '' }}">
                        <span class="icon">＋</span>
                        <span>GRN Baru</span>
                    </a>
                </div>
            </li>
        @endif

        {{-- SALES & MARKETPLACE (owner + admin) --}}
        @if (in_array($userRole, ['owner', 'admin']))
            <li class="mt-2 text-uppercase small menu-label">Sales &amp; Marketplace</li>

            {{-- Marketplace Orders --}}
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
                        <span class="icon">≡</span>
                        <span>Daftar Order</span>
                    </a>

                    <a href="{{ route('marketplace.orders.create') }}"
                        class="sidebar-link sidebar-link-sub {{ request()->routeIs('marketplace.orders.create') ? 'active' : '' }}">
                        <span class="icon">＋</span>
                        <span>Order Manual</span>
                    </a>
                </div>
            </li>

            {{-- Sales (Invoices + Shipments + Reports) --}}
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
                        <span class="icon">≡</span>
                        <span>Daftar Invoice</span>
                    </a>

                    <a href="{{ route('sales.invoices.create') }}"
                        class="sidebar-link sidebar-link-sub {{ request()->routeIs('sales.invoices.create') ? 'active' : '' }}">
                        <span class="icon">＋</span>
                        <span>Invoice Baru</span>
                    </a>

                    <div class="px-3 pt-2 pb-1 text-uppercase"
                        style="font-size:.68rem; letter-spacing:.12em; color:var(--muted);">
                        Shipments
                    </div>

                    <a href="{{ route('sales.shipments.index') }}"
                        class="sidebar-link sidebar-link-sub {{ request()->routeIs('sales.shipments.index') ? 'active' : '' }}">
                        <span class="icon">🚚</span>
                        <span>Daftar Shipment</span>
                    </a>

                    <a href="{{ route('sales.shipments.create') }}"
                        class="sidebar-link sidebar-link-sub {{ request()->routeIs('sales.shipments.create') ? 'active' : '' }}">
                        <span class="icon">＋</span>
                        <span>Shipment Baru</span>
                    </a>

                    <div class="px-3 pt-2 pb-1 text-uppercase"
                        style="font-size:.68rem; letter-spacing:.12em; color:var(--muted);">
                        Sales Reports
                    </div>

                    <a href="{{ route('sales.reports.item_profit') }}"
                        class="sidebar-link sidebar-link-sub {{ request()->routeIs('sales.reports.item_profit') ? 'active' : '' }}">
                        <span class="icon">💹</span>
                        <span>Laba Rugi per Item</span>
                    </a>

                    <a href="{{ route('sales.reports.channel_profit') }}"
                        class="sidebar-link sidebar-link-sub {{ request()->routeIs('sales.reports.channel_profit') ? 'active' : '' }}">
                        <span class="icon">🏬</span>
                        <span>Laba Rugi per Channel</span>
                    </a>

                    <a href="{{ route('sales.reports.shipment_analytics') }}"
                        class="sidebar-link sidebar-link-sub {{ request()->routeIs('sales.reports.shipment_analytics') ? 'active' : '' }}">
                        <span class="icon">📦</span>
                        <span>Shipment Analytics</span>
                    </a>
                </div>
            </li>
        @endif

        {{-- INVENTORY (owner + operating sebagian, owner + admin untuk RTS) --}}
        @if (in_array($userRole, ['owner', 'admin', 'operating']))
            <li class="mt-2 text-uppercase small menu-label">Inventory</li>

            {{-- Inventory internal & external (owner + operating) --}}
            @if (in_array($userRole, ['owner', 'operating']))
                <li class="mb-1">
                    <button class="sidebar-link sidebar-toggle {{ $invOpen ? 'is-open' : '' }}" type="button"
                        data-bs-toggle="collapse" data-bs-target="#navInventory"
                        aria-expanded="{{ $invOpen ? 'true' : 'false' }}" aria-controls="navInventory">
                        <span class="icon">📦</span>
                        <span>Inventory</span>
                        <span class="chevron">▸</span>
                    </button>

                    <div class="collapse {{ $invOpen ? 'show' : '' }}" id="navInventory">
                        <a href="{{ route('inventory.stocks.items') }}"
                            class="sidebar-link sidebar-link-sub {{ request()->routeIs('inventory.stocks.items') ? 'active' : '' }}">
                            <span class="icon">📦</span>
                            <span>Stok per Item</span>
                        </a>

                        <a href="{{ route('inventory.stocks.lots') }}"
                            class="sidebar-link sidebar-link-sub {{ request()->routeIs('inventory.stocks.lots') ? 'active' : '' }}">
                            <span class="icon">🎫</span>
                            <span>Stok per LOT</span>
                        </a>

                        <a href="{{ route('inventory.stock_card.index') }}"
                            class="sidebar-link sidebar-link-sub {{ request()->routeIs('inventory.stock_card.index') ? 'active' : '' }}">
                            <span class="icon">📋</span>
                            <span>Kartu Stok</span>
                        </a>

                        <a href="{{ route('inventory.transfers.index') }}"
                            class="sidebar-link sidebar-link-sub {{ request()->routeIs('inventory.transfers.index') ? 'active' : '' }}">
                            <span class="icon">🔁</span>
                            <span>Daftar Transfer</span>
                        </a>

                        <a href="{{ route('inventory.transfers.create') }}"
                            class="sidebar-link sidebar-link-sub {{ request()->routeIs('inventory.transfers.create') ? 'active' : '' }}">
                            <span class="icon">➕</span>
                            <span>Transfer Baru</span>
                        </a>

                        <a href="{{ route('inventory.stock_opnames.index') }}"
                            class="sidebar-link sidebar-link-sub {{ request()->routeIs('inventory.stock_opnames.index') ? 'active' : '' }}">
                            <span class="icon">📊</span>
                            <span>Daftar Stock Opname</span>
                        </a>

                        <a href="{{ route('inventory.stock_opnames.create') }}"
                            class="sidebar-link sidebar-link-sub {{ request()->routeIs('inventory.stock_opnames.create') ? 'active' : '' }}">
                            <span class="icon">＋</span>
                            <span>Stock Opname Baru</span>
                        </a>

                        <a href="{{ route('inventory.adjustments.index') }}"
                            class="sidebar-link sidebar-link-sub {{ request()->routeIs('inventory.adjustments.index') ? 'active' : '' }}">
                            <span class="icon">⚖️</span>
                            <span>Daftar Adjustment</span>
                        </a>

                        <a href="{{ route('inventory.adjustments.manual.create') }}"
                            class="sidebar-link sidebar-link-sub {{ request()->routeIs('inventory.adjustments.manual.create') ? 'active' : '' }}">
                            <span class="icon">✏️</span>
                            <span>Manual Adjustment</span>
                        </a>
                    </div>
                </li>

                <li class="mb-1">
                    <button class="sidebar-link sidebar-toggle {{ $extInvOpen ? 'is-open' : '' }}" type="button"
                        data-bs-toggle="collapse" data-bs-target="#navInventoryExternal"
                        aria-expanded="{{ $extInvOpen ? 'true' : 'false' }}" aria-controls="navInventoryExternal">
                        <span class="icon">🚚</span>
                        <span>External Transfers</span>
                        <span class="chevron">▸</span>
                    </button>

                    <div class="collapse {{ $extInvOpen ? 'show' : '' }}" id="navInventoryExternal">
                        <a href="{{ route('inventory.external_transfers.index') }}"
                            class="sidebar-link sidebar-link-sub {{ request()->routeIs('inventory.external_transfers.index') ? 'active' : '' }}">
                            <span class="icon">≡</span>
                            <span>Daftar External TF</span>
                        </a>

                        <a href="{{ route('inventory.external_transfers.create') }}"
                            class="sidebar-link sidebar-link-sub {{ request()->routeIs('inventory.external_transfers.create') ? 'active' : '' }}">
                            <span class="icon">➕</span>
                            <span>External TF Baru</span>
                        </a>
                    </div>
                </li>
            @endif

            {{-- Stock Requests (RTS & PRD) --}}
            <li class="mb-1">
                <button class="sidebar-link sidebar-toggle {{ $stockReqOpen ? 'is-open' : '' }}" type="button"
                    data-bs-toggle="collapse" data-bs-target="#navInventoryStockRequests"
                    aria-expanded="{{ $stockReqOpen ? 'true' : 'false' }}" aria-controls="navInventoryStockRequests">
                    <span class="icon">📤</span>
                    <span>Stock Requests</span>
                    <span class="chevron">▸</span>
                </button>

                <div class="collapse {{ $stockReqOpen ? 'show' : '' }}" id="navInventoryStockRequests">
                    @if (in_array($userRole, ['owner', 'admin']))
                        <a href="{{ route('rts.stock-requests.index') }}"
                            class="sidebar-link sidebar-link-sub {{ request()->routeIs('rts.stock-requests.*') ? 'active' : '' }}">
                            <span class="icon">🛒</span>
                            <span>Permintaan Stok RTS</span>
                        </a>
                    @endif

                    @if (in_array($userRole, ['owner', 'operating']))
                        <a href="{{ route('prd.stock-requests.index') }}"
                            class="sidebar-link sidebar-link-sub {{ request()->routeIs('prd.stock-requests.*') ? 'active' : '' }}">
                            <span class="icon">🏭</span>
                            <span>Proses Stok Request PRD</span>
                        </a>
                    @endif
                </div>
            </li>
        @endif

        {{-- PRODUCTION (owner + operating) --}}
        @if (in_array($userRole, ['owner', 'operating']))
            <li class="mt-2 text-uppercase small menu-label">Production</li>

            {{-- Cutting Jobs --}}
            <li class="mb-1">
                <button class="sidebar-link sidebar-toggle {{ $prodCutOpen ? 'is-open' : '' }}" type="button"
                    data-bs-toggle="collapse" data-bs-target="#navProductionCutting"
                    aria-expanded="{{ $prodCutOpen ? 'true' : 'false' }}" aria-controls="navProductionCutting">
                    <span class="icon">✂️</span>
                    <span>Cutting Jobs</span>
                    <span class="chevron">▸</span>
                </button>

                <div class="collapse {{ $prodCutOpen ? 'show' : '' }}" id="navProductionCutting">
                    <a href="{{ route('production.cutting_jobs.index') }}"
                        class="sidebar-link sidebar-link-sub {{ request()->routeIs('production.cutting_jobs.index') ? 'active' : '' }}">
                        <span class="icon">≡</span>
                        <span>Daftar Cutting Job</span>
                    </a>

                    <a href="{{ route('production.cutting_jobs.create') }}"
                        class="sidebar-link sidebar-link-sub {{ request()->routeIs('production.cutting_jobs.create') ? 'active' : '' }}">
                        <span class="icon">＋</span>
                        <span>Cutting Job Baru</span>
                    </a>
                </div>
            </li>

            {{-- Sewing --}}
            <li class="mb-1">
                <button class="sidebar-link sidebar-toggle {{ $prodSewOpen ? 'is-open' : '' }}" type="button"
                    data-bs-toggle="collapse" data-bs-target="#navProductionSewing"
                    aria-expanded="{{ $prodSewOpen ? 'true' : 'false' }}" aria-controls="navProductionSewing">
                    <span class="icon">🧵</span>
                    <span>Sewing</span>
                    <span class="chevron">▸</span>
                </button>

                <div class="collapse {{ $prodSewOpen ? 'show' : '' }}" id="navProductionSewing">
                    <a href="{{ route('production.sewing_pickups.index') }}"
                        class="sidebar-link sidebar-link-sub {{ request()->routeIs('production.sewing_pickups.index') ? 'active' : '' }}">
                        <span class="icon">📤</span>
                        <span>Sewing Pickups</span>
                    </a>

                    <a href="{{ route('production.sewing_pickups.create') }}"
                        class="sidebar-link sidebar-link-sub {{ request()->routeIs('production.sewing_pickups.create') ? 'active' : '' }}">
                        <span class="icon">＋</span>
                        <span>Pickup Baru</span>
                    </a>

                    <a href="{{ route('production.sewing_returns.index') }}"
                        class="sidebar-link sidebar-link-sub {{ request()->routeIs('production.sewing_returns.index') ? 'active' : '' }}">
                        <span class="icon">📥</span>
                        <span>Sewing Returns</span>
                    </a>

                    <a href="{{ route('production.sewing_returns.create') }}"
                        class="sidebar-link sidebar-link-sub {{ request()->routeIs('production.sewing_returns.create') ? 'active' : '' }}">
                        <span class="icon">＋</span>
                        <span>Return Baru</span>
                    </a>

                    <div class="px-3 pt-2 pb-1 text-uppercase"
                        style="font-size:.68rem; letter-spacing:.12em; color:var(--muted);">
                        Sewing Reports
                    </div>

                    <a href="{{ route('production.reports.dashboard') }}"
                        class="sidebar-link sidebar-link-sub {{ request()->routeIs('production.reports.dashboard') ? 'active' : '' }}">
                        <span class="icon">📋</span>
                        <span>Daily Dashboard</span>
                    </a>

                    <a href="{{ route('production.reports.operators') }}"
                        class="sidebar-link sidebar-link-sub {{ request()->routeIs('production.reports.operators') ? 'active' : '' }}">
                        <span class="icon">👥</span>
                        <span>Operator Summary</span>
                    </a>

                    <a href="{{ route('production.reports.outstanding') }}"
                        class="sidebar-link sidebar-link-sub {{ request()->routeIs('production.reports.outstanding') ? 'active' : '' }}">
                        <span class="icon">⏳</span>
                        <span>Outstanding</span>
                    </a>

                    <a href="{{ route('production.reports.aging_wip_sew') }}"
                        class="sidebar-link sidebar-link-sub {{ request()->routeIs('production.reports.aging_wip_sew') ? 'active' : '' }}">
                        <span class="icon">📊</span>
                        <span>Aging WIP Sewing</span>
                    </a>

                    <a href="{{ route('production.reports.productivity') }}"
                        class="sidebar-link sidebar-link-sub {{ request()->routeIs('production.reports.productivity') ? 'active' : '' }}">
                        <span class="icon">📈</span>
                        <span>Productivity</span>
                    </a>

                    <a href="{{ route('production.reports.partial_pickup') }}"
                        class="sidebar-link sidebar-link-sub {{ request()->routeIs('production.reports.partial_pickup') ? 'active' : '' }}">
                        <span class="icon">🧩</span>
                        <span>Partial Pickup</span>
                    </a>

                    <a href="{{ route('production.reports.report_reject') }}"
                        class="sidebar-link sidebar-link-sub {{ request()->routeIs('production.reports.report_reject') ? 'active' : '' }}">
                        <span class="icon">⚠️</span>
                        <span>Reject Analysis</span>
                    </a>

                    <a href="{{ route('production.reports.lead_time') }}"
                        class="sidebar-link sidebar-link-sub {{ request()->routeIs('production.reports.lead_time') ? 'active' : '' }}">
                        <span class="icon">⏱️</span>
                        <span>Lead Time</span>
                    </a>

                    <a href="{{ route('production.reports.operator_behavior') }}"
                        class="sidebar-link sidebar-link-sub {{ request()->routeIs('production.reports.operator_behavior') ? 'active' : '' }}">
                        <span class="icon">👀</span>
                        <span>Operator Behavior</span>
                    </a>
                </div>
            </li>

            {{-- Finishing --}}
            <li class="mb-1">
                <button class="sidebar-link sidebar-toggle {{ $prodFinOpen ? 'is-open' : '' }}" type="button"
                    data-bs-toggle="collapse" data-bs-target="#navProductionFinishing"
                    aria-expanded="{{ $prodFinOpen ? 'true' : 'false' }}" aria-controls="navProductionFinishing">
                    <span class="icon">🧶</span>
                    <span>Finishing</span>
                    <span class="chevron">▸</span>
                </button>

                <div class="collapse {{ $prodFinOpen ? 'show' : '' }}" id="navProductionFinishing">
                    <a href="{{ route('production.finishing_jobs.index') }}"
                        class="sidebar-link sidebar-link-sub {{ request()->routeIs('production.finishing_jobs.index') ? 'active' : '' }}">
                        <span class="icon">≡</span>
                        <span>Daftar Finishing Job</span>
                    </a>

                    <a href="{{ route('production.finishing_jobs.create') }}"
                        class="sidebar-link sidebar-link-sub {{ request()->routeIs('production.finishing_jobs.create') ? 'active' : '' }}">
                        <span class="icon">＋</span>
                        <span>Finishing Job Baru</span>
                    </a>

                    <a href="{{ route('production.finishing_jobs.bundles_ready') }}"
                        class="sidebar-link sidebar-link-sub {{ request()->routeIs('production.finishing_jobs.bundles_ready') ? 'active' : '' }}">
                        <span class="icon">📦</span>
                        <span>Bundles Ready for Finishing</span>
                    </a>
                </div>
            </li>

            {{-- Packing --}}
            <li class="mb-1">
                <button class="sidebar-link sidebar-toggle {{ $prodPackOpen ? 'is-open' : '' }}" type="button"
                    data-bs-toggle="collapse" data-bs-target="#navProductionPacking"
                    aria-expanded="{{ $prodPackOpen ? 'true' : 'false' }}" aria-controls="navProductionPacking">
                    <span class="icon">📦</span>
                    <span>Packing</span>
                    <span class="chevron">▸</span>
                </button>

                <div class="collapse {{ $prodPackOpen ? 'show' : '' }}" id="navProductionPacking">
                    <a href="{{ route('production.packing_jobs.index') }}"
                        class="sidebar-link sidebar-link-sub {{ request()->routeIs('production.packing_jobs.index') ? 'active' : '' }}">
                        <span class="icon">≡</span>
                        <span>Daftar Packing Job</span>
                    </a>

                    <a href="{{ route('production.packing_jobs.create') }}"
                        class="sidebar-link sidebar-link-sub {{ request()->routeIs('production.packing_jobs.create') ? 'active' : '' }}">
                        <span class="icon">＋</span>
                        <span>Packing Job Baru</span>
                    </a>

                    <a href="{{ route('production.packing_jobs.ready_items') }}"
                        class="sidebar-link sidebar-link-sub {{ request()->routeIs('production.packing_jobs.ready_items') ? 'active' : '' }}">
                        <span class="icon">📦</span>
                        <span>Ready Items (WH-PRD)</span>
                    </a>
                </div>
            </li>

            {{-- Quality Control --}}
            <li class="mb-1">
                <button class="sidebar-link sidebar-toggle {{ $prodQcOpen ? 'is-open' : '' }}" type="button"
                    data-bs-toggle="collapse" data-bs-target="#navProductionQc"
                    aria-expanded="{{ $prodQcOpen ? 'true' : 'false' }}" aria-controls="navProductionQc">
                    <span class="icon">✅</span>
                    <span>Quality Control</span>
                    <span class="chevron">▸</span>
                </button>

                <div class="collapse {{ $prodQcOpen ? 'show' : '' }}" id="navProductionQc">
                    <a href="{{ route('production.qc.index') }}"
                        class="sidebar-link sidebar-link-sub {{ request()->routeIs('production.qc.index') || request()->routeIs('production.qc.cutting.*') ? 'active' : '' }}">
                        <span class="icon">✂️</span>
                        <span>QC Cutting</span>
                    </a>
                </div>
            </li>

            {{-- Laporan Produksi --}}
            <li class="mb-1">
                <button class="sidebar-link sidebar-toggle {{ $prodReportOpen ? 'is-open' : '' }}" type="button"
                    data-bs-toggle="collapse" data-bs-target="#navProductionReports"
                    aria-expanded="{{ $prodReportOpen ? 'true' : 'false' }}" aria-controls="navProductionReports">
                    <span class="icon">📈</span>
                    <span>Laporan Produksi</span>
                    <span class="chevron">▸</span>
                </button>

                <div class="collapse {{ $prodReportOpen ? 'show' : '' }}" id="navProductionReports">
                    <a href="{{ route('production.reports.daily_production') }}"
                        class="sidebar-link sidebar-link-sub {{ request()->routeIs('production.reports.daily_production') ? 'active' : '' }}">
                        <span class="icon">📆</span>
                        <span>Daily Production Summary</span>
                    </a>

                    <a href="{{ route('production.reports.wip_sewing_age') }}"
                        class="sidebar-link sidebar-link-sub {{ request()->routeIs('production.reports.wip_sewing_age') ? 'active' : '' }}">
                        <span class="icon">⏳</span>
                        <span>WIP Sewing Age (Report)</span>
                    </a>

                    <a href="{{ route('production.reports.sewing_per_item') }}"
                        class="sidebar-link sidebar-link-sub {{ request()->routeIs('production.reports.sewing_per_item') ? 'active' : '' }}">
                        <span class="icon">🧵</span>
                        <span>Sewing per Item</span>
                    </a>

                    <a href="{{ route('production.reports.reject_detail') }}"
                        class="sidebar-link sidebar-link-sub {{ request()->routeIs('production.reports.reject_detail') ? 'active' : '' }}">
                        <span class="icon">⚠️</span>
                        <span>Reject Detail</span>
                    </a>

                    <a href="{{ route('production.reports.finishing_jobs') }}"
                        class="sidebar-link sidebar-link-sub {{ request()->routeIs('production.reports.finishing_jobs') ? 'active' : '' }}">
                        <span class="icon">🧶</span>
                        <span>Finishing Jobs Summary</span>
                    </a>

                    <a href="{{ route('production.finishing_jobs.report_per_item') }}"
                        class="sidebar-link sidebar-link-sub {{ request()->routeIs('production.finishing_jobs.report_per_item') || request()->routeIs('production.finishing_jobs.report_per_item_detail') ? 'active' : '' }}">
                        <span class="icon">📦</span>
                        <span>Finishing per Item</span>
                    </a>
                </div>
            </li>
        @endif

        {{-- FINANCE (owner only) --}}
        @if ($userRole === 'owner')
            <li class="mt-2 text-uppercase small menu-label">Finance</li>

            {{-- Payroll --}}
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
                        <span class="icon">✂️</span>
                        <span>Cutting Payroll</span>
                    </a>

                    <a href="{{ route('payroll.sewing.index') }}"
                        class="sidebar-link sidebar-link-sub {{ request()->routeIs('payroll.sewing.*') ? 'active' : '' }}">
                        <span class="icon">🧵</span>
                        <span>Sewing Payroll</span>
                    </a>

                    <a href="{{ route('payroll.piece_rates.index') }}"
                        class="sidebar-link sidebar-link-sub {{ request()->routeIs('payroll.piece_rates.*') ? 'active' : '' }}">
                        <span class="icon">📑</span>
                        <span>Master Piece Rates</span>
                    </a>

                    <div class="px-3 pt-2 pb-1 text-uppercase"
                        style="font-size:.68rem; letter-spacing:.12em; color:var(--muted);">
                        Payroll Reports
                    </div>

                    <a href="{{ route('payroll.reports.operators') }}"
                        class="sidebar-link sidebar-link-sub {{ request()->routeIs('payroll.reports.operators') || request()->routeIs('payroll.reports.operator_detail') ? 'active' : '' }}">
                        <span class="icon">📊</span>
                        <span>Rekap per Operator</span>
                    </a>

                    <a href="{{ route('payroll.reports.operator_slips') }}"
                        class="sidebar-link sidebar-link-sub {{ request()->routeIs('payroll.reports.operator_slips') ? 'active' : '' }}">
                        <span class="icon">🧾</span>
                        <span>Slip Borongan (All)</span>
                    </a>
                </div>
            </li>

            {{-- Costing / HPP --}}
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
                        <span class="icon">⚙️</span>
                        <span>HPP Finished Goods</span>
                    </a>

                    <a href="{{ route('costing.production_cost_periods.index') }}"
                        class="sidebar-link sidebar-link-sub {{ request()->routeIs('costing.production_cost_periods.*') ? 'active' : '' }}">
                        <span class="icon">📆</span>
                        <span>Production Cost Periods</span>
                    </a>
                </div>
            </li>

            {{-- Finance Reports --}}
            <li class="mb-1">
                <button class="sidebar-link sidebar-toggle {{ $financeReportsOpen ? 'is-open' : '' }}"
                    type="button" data-bs-toggle="collapse" data-bs-target="#navFinanceReports"
                    aria-expanded="{{ $financeReportsOpen ? 'true' : 'false' }}" aria-controls="navFinanceReports">
                    <span class="icon">📊</span>
                    <span>Finance Reports</span>
                    <span class="chevron">▸</span>
                </button>

                <div class="collapse {{ $financeReportsOpen ? 'show' : '' }}" id="navFinanceReports">
                    <a href="{{ route('sales.reports.item_profit') }}"
                        class="sidebar-link sidebar-link-sub {{ request()->routeIs('sales.reports.item_profit') ? 'active' : '' }}">
                        <span class="icon">💹</span>
                        <span>Laba Rugi per Item</span>
                    </a>

                    <a href="{{ route('sales.reports.channel_profit') }}"
                        class="sidebar-link sidebar-link-sub {{ request()->routeIs('sales.reports.channel_profit') ? 'active' : '' }}">
                        <span class="icon">🏬</span>
                        <span>Laba Rugi per Channel</span>
                    </a>

                    <a href="{{ route('sales.reports.shipment_analytics') }}"
                        class="sidebar-link sidebar-link-sub {{ request()->routeIs('sales.reports.shipment_analytics') ? 'active' : '' }}">
                        <span class="icon">📦</span>
                        <span>Shipment Analytics</span>
                    </a>
                </div>
            </li>
        @endif
    </ul>
</aside>
