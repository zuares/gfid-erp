{{-- resources/views/layouts/partials/mobile-sidebar.blade.php --}}

<style>
    /* ============================
       MOBILE SIDEBAR (DRAWER)
    ============================ */

    .mobile-sidebar-overlay {
        position: fixed;
        inset: 0;
        background: rgba(15, 23, 42, 0.45);
        z-index: 1040;
        opacity: 0;
        visibility: hidden;
        pointer-events: none;
        transition: opacity .18s ease, visibility .18s ease;
    }

    .mobile-sidebar-overlay.is-open {
        opacity: 1;
        visibility: visible;
        pointer-events: auto;
    }

    .mobile-sidebar-panel {
        position: fixed;
        top: 0;
        left: 0;
        bottom: 0;
        width: 270px;
        max-width: 84%;
        background: var(--card);
        border-right: 1px solid var(--line);
        box-shadow:
            10px 0 30px rgba(15, 23, 42, 0.35),
            0 0 0 1px rgba(15, 23, 42, .15);
        z-index: 1051;
        transform: translateX(-100%);
        transition: transform .22s ease-out;
        display: flex;
        flex-direction: column;
        padding: .75rem .9rem 1.1rem;
        padding-bottom: max(1rem, env(safe-area-inset-bottom, 0px));
    }

    .mobile-sidebar-panel.is-open {
        transform: translateX(0);
    }

    .mobile-sidebar-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: .5rem;
        margin-bottom: .35rem;
    }

    .mobile-sidebar-title {
        font-weight: 600;
        font-size: 1rem;
    }

    .mobile-sidebar-close-btn {
        border-radius: 999px;
        border: 1px solid var(--line);
        width: 30px;
        height: 30px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: color-mix(in srgb, var(--card) 90%, var(--bg) 10%);
        cursor: pointer;
        font-size: 1.1rem;
    }

    .mobile-sidebar-body {
        margin-top: .25rem;
        flex: 1 1 auto;
        min-height: 0;
        overflow-y: auto;
        padding-right: .15rem;
    }

    .mobile-sidebar-body::-webkit-scrollbar {
        width: 6px;
    }

    .mobile-sidebar-body::-webkit-scrollbar-thumb {
        background: rgba(148, 163, 184, .35);
        border-radius: 999px;
    }

    .mobile-sidebar-nav {
        list-style: none;
        padding: 0;
        margin: .25rem 0 0;
    }

    .mobile-sidebar-link {
        display: flex;
        align-items: center;
        gap: .55rem;
        padding: .65rem .45rem;
        border-radius: 12px;
        text-decoration: none;
        color: var(--text);
        font-size: .92rem;
        margin-bottom: .15rem;
        transition: background .16s ease, color .16s ease, transform .08s ease;
    }

    .mobile-sidebar-link span.icon {
        font-size: 1.15rem;
        width: 24px;
        text-align: center;
    }

    .mobile-sidebar-link:hover {
        background: color-mix(in srgb, var(--accent-soft) 70%, var(--card) 30%);
    }

    .mobile-sidebar-link:active {
        transform: translateY(1px);
    }

    .mobile-sidebar-link.active {
        background: color-mix(in srgb, var(--accent-soft) 80%, var(--card) 20%);
        color: var(--accent);
        font-weight: 600;
    }

    .mobile-sidebar-section-label {
        font-size: .74rem;
        text-transform: uppercase;
        letter-spacing: .08em;
        color: var(--muted);
        margin-top: .9rem;
        margin-bottom: .2rem;
        padding-inline: .2rem;
    }

    .mobile-sidebar-footer {
        margin-top: .6rem;
        font-size: .75rem;
        color: var(--muted);
        padding-top: .6rem;
        border-top: 1px solid var(--line);
    }

    .mono {
        font-variant-numeric: tabular-nums;
        font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, "Liberation Mono";
    }

    .mobile-sidebar-toggle {
        cursor: pointer;
        border: 0;
        width: 100%;
        background: transparent;
        text-align: left;
    }

    .mobile-sidebar-toggle .chevron {
        margin-left: auto;
        font-size: .8rem;
        opacity: .8;
        transition: transform .18s ease;
    }

    .mobile-sidebar-toggle[aria-expanded="true"] .chevron {
        transform: rotate(90deg);
    }

    .mobile-sidebar-toggle.is-open {
        color: var(--accent);
        font-weight: 600;
    }

    .mobile-sidebar-toggle.is-open .icon {
        color: var(--accent);
    }

    .mobile-sidebar-link-sub {
        position: relative;
        font-size: .86rem;
        padding: .5rem .5rem .5rem 2.4rem;
        margin-bottom: .1rem;
    }

    .mobile-sidebar-link-sub .icon {
        width: 18px;
        font-size: .95rem;
    }

    .mobile-sidebar-link-sub.active {
        background: transparent;
        font-weight: 600;
        color: var(--accent);
    }

    .mobile-sidebar-link-sub.active::before {
        content: '';
        position: absolute;
        left: 1.3rem;
        top: 50%;
        transform: translateY(-50%);
        width: 6px;
        height: 6px;
        border-radius: 999px;
        background: var(--accent);
    }

    @media (min-width: 768px) {

        .mobile-sidebar-overlay,
        .mobile-sidebar-panel {
            display: none;
        }
    }
</style>

@php
    $userRole = auth()->user()->role ?? null;

    // ROLE OPERATOR LAPANGAN (menu super ringkas)
    $isOperatorRole = in_array($userRole, ['sewing', 'cutting']);

    // Flag open mirroring desktop
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

{{-- OVERLAY --}}
<div id="mobileSidebarOverlay" class="mobile-sidebar-overlay"></div>

{{-- PANEL --}}
<aside id="mobileSidebarPanel" class="mobile-sidebar-panel">
    <div class="mobile-sidebar-header">
        <div class="mobile-sidebar-title">
            {{ config('app.name', 'GFID') }}
        </div>
        <button type="button" class="mobile-sidebar-close-btn" id="mobileSidebarCloseBtn">
            ✕
        </button>
    </div>

    <div class="mobile-sidebar-body">
        <ul class="mobile-sidebar-nav">
            @auth

                {{-- ============================
                     MODE OPERATOR LAPANGAN ONLY
                     (role: sewing / cutting)
                     Shortcut: Cutting Create, Sewing Pickup Create, Sewing Return Create, Finishing Create
                 ============================ --}}
                @if ($isOperatorRole)
                    {{-- DASHBOARD (boleh, biar tetap ada landing) --}}
                    <li>
                        <a href="{{ route('dashboard') }}"
                            class="mobile-sidebar-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                            <span class="icon">🏠</span>
                            <span>Dashboard</span>
                        </a>
                    </li>

                    <div class="mobile-sidebar-section-label">Production</div>

                    {{-- Cutting Job Baru --}}
                    <li>
                        <a href="{{ route('production.cutting_jobs.create') }}"
                            class="mobile-sidebar-link {{ request()->routeIs('production.cutting_jobs.create') ? 'active' : '' }}">
                            <span class="icon">✂️</span>
                            <span>Cutting Job Baru</span>
                        </a>
                    </li>

                    {{-- Sewing Pickup Baru --}}
                    <li>
                        <a href="{{ route('production.sewing_pickups.create') }}"
                            class="mobile-sidebar-link {{ request()->routeIs('production.sewing_pickups.create') ? 'active' : '' }}">
                            <span class="icon">📤</span>
                            <span>Sewing Pickup Baru</span>
                        </a>
                    </li>

                    {{-- Sewing Return Baru --}}
                    <li>
                        <a href="{{ route('production.sewing_returns.create') }}"
                            class="mobile-sidebar-link {{ request()->routeIs('production.sewing_returns.create') ? 'active' : '' }}">
                            <span class="icon">📥</span>
                            <span>Sewing Return Baru</span>
                        </a>
                    </li>

                    {{-- Finishing Job Baru --}}
                    <li>
                        <a href="{{ route('production.finishing_jobs.create') }}"
                            class="mobile-sidebar-link {{ request()->routeIs('production.finishing_jobs.create') ? 'active' : '' }}">
                            <span class="icon">🧶</span>
                            <span>Finishing Job Baru</span>
                        </a>
                    </li>
                @else
                    {{-- ============================
                         OWNER / ADMIN / OPERATING MENU LENGKAP
                     ============================ --}}

                    {{-- DASHBOARD --}}
                    <li>
                        <a href="{{ route('dashboard') }}"
                            class="mobile-sidebar-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                            <span class="icon">🏠</span>
                            <span>Dashboard</span>
                        </a>
                    </li>

                    {{-- MASTER DATA (boleh semua yang login non-operator) --}}
                    <div class="mobile-sidebar-section-label">Master Data</div>

                    <li class="mb-1">
                        <button class="mobile-sidebar-link mobile-sidebar-toggle {{ $masterOpen ? 'is-open' : '' }}"
                            type="button" data-bs-toggle="collapse" data-bs-target="#navMasterMobile"
                            aria-expanded="{{ $masterOpen ? 'true' : 'false' }}" aria-controls="navMasterMobile">
                            <span class="icon">🗂️</span>
                            <span>Master</span>
                            <span class="chevron">▸</span>
                        </button>

                        <div class="collapse {{ $masterOpen ? 'show' : '' }}" id="navMasterMobile">
                            <a href="{{ route('master.customers.index') }}"
                                class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('master.customers.*') ? 'active' : '' }}">
                                <span class="icon">👤</span>
                                <span>Customers</span>
                            </a>
                            {{-- nanti: Items --}}
                        </div>
                    </li>

                    {{-- PURCHASING (owner + admin) --}}
                    @if (in_array($userRole, ['owner', 'admin']))
                        <div class="mobile-sidebar-section-label">Purchasing</div>

                        {{-- Purchase Orders --}}
                        <li class="mb-1">
                            <button class="mobile-sidebar-link mobile-sidebar-toggle {{ $poOpen ? 'is-open' : '' }}"
                                type="button" data-bs-toggle="collapse" data-bs-target="#navPurchasingPOmobile"
                                aria-expanded="{{ $poOpen ? 'true' : 'false' }}" aria-controls="navPurchasingPOmobile">
                                <span class="icon">🧾</span>
                                <span>Purchase Orders</span>
                                <span class="chevron">▸</span>
                            </button>

                            <div class="collapse {{ $poOpen ? 'show' : '' }}" id="navPurchasingPOmobile">
                                <a href="{{ route('purchasing.purchase_orders.index') }}"
                                    class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('purchasing.purchase_orders.index') ? 'active' : '' }}">
                                    <span class="icon">≡</span>
                                    <span>Daftar PO</span>
                                </a>

                                <a href="{{ route('purchasing.purchase_orders.create') }}"
                                    class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('purchasing.purchase_orders.create') ? 'active' : '' }}">
                                    <span class="icon">＋</span>
                                    <span>PO Baru</span>
                                </a>
                            </div>
                        </li>

                        {{-- Goods Receipts --}}
                        <li class="mb-1">
                            <button class="mobile-sidebar-link mobile-sidebar-toggle {{ $grnOpen ? 'is-open' : '' }}"
                                type="button" data-bs-toggle="collapse" data-bs-target="#navPurchasingGRNmobile"
                                aria-expanded="{{ $grnOpen ? 'true' : 'false' }}" aria-controls="navPurchasingGRNmobile">
                                <span class="icon">📥</span>
                                <span>Goods Receipts (GRN)</span>
                                <span class="chevron">▸</span>
                            </button>

                            <div class="collapse {{ $grnOpen ? 'show' : '' }}" id="navPurchasingGRNmobile">
                                <a href="{{ route('purchasing.purchase_receipts.index') }}"
                                    class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('purchasing.purchase_receipts.index') ? 'active' : '' }}">
                                    <span class="icon">≡</span>
                                    <span>Daftar GRN</span>
                                </a>

                                <a href="{{ route('purchasing.purchase_receipts.create') }}"
                                    class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('purchasing.purchase_receipts.create') ? 'active' : '' }}">
                                    <span class="icon">＋</span>
                                    <span>GRN Baru</span>
                                </a>
                            </div>
                        </li>
                    @endif

                    {{-- SALES & MARKETPLACE (owner + admin) --}}
                    @if (in_array($userRole, ['owner', 'admin']))
                        <div class="mobile-sidebar-section-label">Sales &amp; Marketplace</div>

                        {{-- Marketplace Orders --}}
                        <li class="mb-1">
                            <button
                                class="mobile-sidebar-link mobile-sidebar-toggle {{ $marketplaceOpen ? 'is-open' : '' }}"
                                type="button" data-bs-toggle="collapse" data-bs-target="#navMarketplaceMobile"
                                aria-expanded="{{ $marketplaceOpen ? 'true' : 'false' }}"
                                aria-controls="navMarketplaceMobile">
                                <span class="icon">🛒</span>
                                <span>Marketplace Orders</span>
                                <span class="chevron">▸</span>
                            </button>

                            <div class="collapse {{ $marketplaceOpen ? 'show' : '' }}" id="navMarketplaceMobile">
                                <a href="{{ route('marketplace.orders.index') }}"
                                    class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('marketplace.orders.index') ? 'active' : '' }}">
                                    <span class="icon">≡</span>
                                    <span>Daftar Order</span>
                                </a>

                                <a href="{{ route('marketplace.orders.create') }}"
                                    class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('marketplace.orders.create') ? 'active' : '' }}">
                                    <span class="icon">＋</span>
                                    <span>Order Manual</span>
                                </a>
                            </div>
                        </li>

                        {{-- Sales (Invoices + Shipments + Reports) --}}
                        <li class="mb-1">
                            <button class="mobile-sidebar-link mobile-sidebar-toggle {{ $salesOpen ? 'is-open' : '' }}"
                                type="button" data-bs-toggle="collapse" data-bs-target="#navSalesMobile"
                                aria-expanded="{{ $salesOpen ? 'true' : 'false' }}" aria-controls="navSalesMobile">
                                <span class="icon">📑</span>
                                <span>Sales</span>
                                <span class="chevron">▸</span>
                            </button>

                            <div class="collapse {{ $salesOpen ? 'show' : '' }}" id="navSalesMobile">
                                {{-- Invoices --}}
                                <a href="{{ route('sales.invoices.index') }}"
                                    class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('sales.invoices.index') ? 'active' : '' }}">
                                    <span class="icon">≡</span>
                                    <span>Daftar Invoice</span>
                                </a>

                                <a href="{{ route('sales.invoices.create') }}"
                                    class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('sales.invoices.create') ? 'active' : '' }}">
                                    <span class="icon">＋</span>
                                    <span>Invoice Baru</span>
                                </a>

                                {{-- Shipments --}}
                                <div class="mobile-sidebar-section-label" style="margin-top:.4rem;">Shipments</div>

                                <a href="{{ route('sales.shipments.index') }}"
                                    class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('sales.shipments.index') ? 'active' : '' }}">
                                    <span class="icon">🚚</span>
                                    <span>Daftar Shipment</span>
                                </a>

                                <a href="{{ route('sales.shipments.create') }}"
                                    class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('sales.shipments.create') ? 'active' : '' }}">
                                    <span class="icon">＋</span>
                                    <span>Shipment Baru</span>
                                </a>

                                {{-- Sales Reports --}}
                                <div class="mobile-sidebar-section-label" style="margin-top:.4rem;">Sales Reports</div>

                                <a href="{{ route('sales.reports.item_profit') }}"
                                    class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('sales.reports.item_profit') ? 'active' : '' }}">
                                    <span class="icon">💹</span>
                                    <span>Laba Rugi per Item</span>
                                </a>

                                <a href="{{ route('sales.reports.channel_profit') }}"
                                    class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('sales.reports.channel_profit') ? 'active' : '' }}">
                                    <span class="icon">🏬</span>
                                    <span>Laba Rugi per Channel</span>
                                </a>

                                <a href="{{ route('sales.reports.shipment_analytics') }}"
                                    class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('sales.reports.shipment_analytics') ? 'active' : '' }}">
                                    <span class="icon">📦</span>
                                    <span>Shipment Analytics</span>
                                </a>
                            </div>
                        </li>
                    @endif

                    {{-- INVENTORY (owner + admin + operating) --}}
                    @if (in_array($userRole, ['owner', 'admin', 'operating']))
                        <div class="mobile-sidebar-section-label">Inventory</div>

                        {{-- Inventory internal: stocks, stock card, stock opnames, transfers, adjustments --}}
                        <li class="mb-1">
                            <button class="mobile-sidebar-link mobile-sidebar-toggle {{ $invOpen ? 'is-open' : '' }}"
                                type="button" data-bs-toggle="collapse" data-bs-target="#navInventoryMobile"
                                aria-expanded="{{ $invOpen ? 'true' : 'false' }}" aria-controls="navInventoryMobile">
                                <span class="icon">📦</span>
                                <span>Inventory</span>
                                <span class="chevron">▸</span>
                            </button>

                            <div class="collapse {{ $invOpen ? 'show' : '' }}" id="navInventoryMobile">
                                <a href="{{ route('inventory.stocks.items') }}"
                                    class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('inventory.stocks.items') ? 'active' : '' }}">
                                    <span class="icon">📦</span>
                                    <span>Stok per Item</span>
                                </a>

                                <a href="{{ route('inventory.stocks.lots') }}"
                                    class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('inventory.stocks.lots') ? 'active' : '' }}">
                                    <span class="icon">🎫</span>
                                    <span>Stok per LOT</span>
                                </a>

                                <a href="{{ route('inventory.stock_card.index') }}"
                                    class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('inventory.stock_card.index') ? 'active' : '' }}">
                                    <span class="icon">📋</span>
                                    <span>Kartu Stok</span>
                                </a>

                                <a href="{{ route('inventory.transfers.index') }}"
                                    class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('inventory.transfers.index') ? 'active' : '' }}">
                                    <span class="icon">🔁</span>
                                    <span>Daftar Transfer</span>
                                </a>

                                <a href="{{ route('inventory.transfers.create') }}"
                                    class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('inventory.transfers.create') ? 'active' : '' }}">
                                    <span class="icon">➕</span>
                                    <span>Transfer Baru</span>
                                </a>

                                <a href="{{ route('inventory.stock_opnames.index') }}"
                                    class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('inventory.stock_opnames.index') ? 'active' : '' }}">
                                    <span class="icon">📊</span>
                                    <span>Daftar Stock Opname</span>
                                </a>

                                <a href="{{ route('inventory.stock_opnames.create') }}"
                                    class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('inventory.stock_opnames.create') ? 'active' : '' }}">
                                    <span class="icon">＋</span>
                                    <span>Stock Opname Baru</span>
                                </a>

                                <a href="{{ route('inventory.adjustments.index') }}"
                                    class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('inventory.adjustments.index') ? 'active' : '' }}">
                                    <span class="icon">⚖️</span>
                                    <span>Daftar Adjustment</span>
                                </a>

                                <a href="{{ route('inventory.adjustments.manual.create') }}"
                                    class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('inventory.adjustments.manual.create') ? 'active' : '' }}">
                                    <span class="icon">✏️</span>
                                    <span>Manual Adjustment</span>
                                </a>
                            </div>
                        </li>

                        {{-- External Transfers --}}
                        <li class="mb-1">
                            <button class="mobile-sidebar-link mobile-sidebar-toggle {{ $extInvOpen ? 'is-open' : '' }}"
                                type="button" data-bs-toggle="collapse" data-bs-target="#navInventoryExternalMobile"
                                aria-expanded="{{ $extInvOpen ? 'true' : 'false' }}"
                                aria-controls="navInventoryExternalMobile">
                                <span class="icon">🚚</span>
                                <span>External Transfers</span>
                                <span class="chevron">▸</span>
                            </button>

                            <div class="collapse {{ $extInvOpen ? 'show' : '' }}" id="navInventoryExternalMobile">
                                <a href="{{ route('inventory.external_transfers.index') }}"
                                    class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('inventory.external_transfers.index') ? 'active' : '' }}">
                                    <span class="icon">≡</span>
                                    <span>Daftar External TF</span>
                                </a>

                                <a href="{{ route('inventory.external_transfers.create') }}"
                                    class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('inventory.external_transfers.create') ? 'active' : '' }}">
                                    <span class="icon">➕</span>
                                    <span>External TF Baru</span>
                                </a>
                            </div>
                        </li>

                        {{-- Stock Requests (RTS & PRD) --}}
                        <li class="mb-1">
                            <button class="mobile-sidebar-link mobile-sidebar-toggle {{ $stockReqOpen ? 'is-open' : '' }}"
                                type="button" data-bs-toggle="collapse"
                                data-bs-target="#navInventoryStockRequestsMobile"
                                aria-expanded="{{ $stockReqOpen ? 'true' : 'false' }}"
                                aria-controls="navInventoryStockRequestsMobile">
                                <span class="icon">📤</span>
                                <span>Stock Requests</span>
                                <span class="chevron">▸</span>
                            </button>

                            <div class="collapse {{ $stockReqOpen ? 'show' : '' }}" id="navInventoryStockRequestsMobile">
                                @if (in_array($userRole, ['owner', 'admin']))
                                    <a href="{{ route('rts.stock-requests.index') }}"
                                        class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('rts.stock-requests.*') ? 'active' : '' }}">
                                        <span class="icon">🛒</span>
                                        <span>Permintaan Stok RTS</span>
                                    </a>
                                @endif

                                @if (in_array($userRole, ['owner', 'operating']))
                                    <a href="{{ route('prd.stock-requests.index') }}"
                                        class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('prd.stock-requests.*') ? 'active' : '' }}">
                                        <span class="icon">🏭</span>
                                        <span>Proses Stok Request PRD</span>
                                    </a>
                                @endif
                            </div>
                        </li>
                    @endif

                    {{-- PRODUCTION (owner + operating) --}}
                    @if (in_array($userRole, ['owner', 'operating']))
                        <div class="mobile-sidebar-section-label">Production</div>

                        {{-- Cutting Jobs --}}
                        <li class="mb-1">
                            <button class="mobile-sidebar-link mobile-sidebar-toggle {{ $prodCutOpen ? 'is-open' : '' }}"
                                type="button" data-bs-toggle="collapse" data-bs-target="#navProductionCuttingMobile"
                                aria-expanded="{{ $prodCutOpen ? 'true' : 'false' }}"
                                aria-controls="navProductionCuttingMobile">
                                <span class="icon">✂️</span>
                                <span>Cutting Jobs</span>
                                <span class="chevron">▸</span>
                            </button>

                            <div class="collapse {{ $prodCutOpen ? 'show' : '' }}" id="navProductionCuttingMobile">
                                <a href="{{ route('production.cutting_jobs.index') }}"
                                    class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('production.cutting_jobs.index') ? 'active' : '' }}">
                                    <span class="icon">≡</span>
                                    <span>Daftar Cutting Job</span>
                                </a>

                                <a href="{{ route('production.cutting_jobs.create') }}"
                                    class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('production.cutting_jobs.create') ? 'active' : '' }}">
                                    <span class="icon">＋</span>
                                    <span>Cutting Job Baru</span>
                                </a>
                            </div>
                        </li>

                        {{-- Sewing --}}
                        <li class="mb-1">
                            <button class="mobile-sidebar-link mobile-sidebar-toggle {{ $prodSewOpen ? 'is-open' : '' }}"
                                type="button" data-bs-toggle="collapse" data-bs-target="#navProductionSewingMobile"
                                aria-expanded="{{ $prodSewOpen ? 'true' : 'false' }}"
                                aria-controls="navProductionSewingMobile">
                                <span class="icon">🧵</span>
                                <span>Sewing</span>
                                <span class="chevron">▸</span>
                            </button>

                            <div class="collapse {{ $prodSewOpen ? 'show' : '' }}" id="navProductionSewingMobile">
                                <a href="{{ route('production.sewing_pickups.index') }}"
                                    class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('production.sewing_pickups.index') ? 'active' : '' }}">
                                    <span class="icon">📤</span>
                                    <span>Sewing Pickups</span>
                                </a>

                                <a href="{{ route('production.sewing_pickups.create') }}"
                                    class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('production.sewing_pickups.create') ? 'active' : '' }}">
                                    <span class="icon">＋</span>
                                    <span>Pickup Baru</span>
                                </a>

                                <a href="{{ route('production.sewing_returns.index') }}"
                                    class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('production.sewing_returns.index') ? 'active' : '' }}">
                                    <span class="icon">📥</span>
                                    <span>Sewing Returns</span>
                                </a>

                                <a href="{{ route('production.sewing_returns.create') }}"
                                    class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('production.sewing_returns.create') ? 'active' : '' }}">
                                    <span class="icon">＋</span>
                                    <span>Return Baru</span>
                                </a>

                                <div class="mobile-sidebar-section-label" style="margin-top:.4rem;">Sewing Reports</div>

                                <a href="{{ route('production.reports.dashboard') }}"
                                    class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('production.reports.dashboard') ? 'active' : '' }}">
                                    <span class="icon">📋</span>
                                    <span>Daily Dashboard</span>
                                </a>

                                <a href="{{ route('production.reports.operators') }}"
                                    class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('production.reports.operators') ? 'active' : '' }}">
                                    <span class="icon">🧍</span>
                                    <span>Operator Summary</span>
                                </a>

                                <a href="{{ route('production.reports.outstanding') }}"
                                    class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('production.reports.outstanding') ? 'active' : '' }}">
                                    <span class="icon">📋</span>
                                    <span>Outstanding</span>
                                </a>

                                <a href="{{ route('production.reports.aging_wip_sew') }}"
                                    class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('production.reports.aging_wip_sew') ? 'active' : '' }}">
                                    <span class="icon">⏳</span>
                                    <span>Aging WIP Sewing</span>
                                </a>

                                <a href="{{ route('production.reports.productivity') }}"
                                    class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('production.reports.productivity') ? 'active' : '' }}">
                                    <span class="icon">⚡</span>
                                    <span>Productivity</span>
                                </a>

                                <a href="{{ route('production.reports.partial_pickup') }}"
                                    class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('production.reports.partial_pickup') ? 'active' : '' }}">
                                    <span class="icon">📊</span>
                                    <span>Partial Pickup</span>
                                </a>

                                <a href="{{ route('production.reports.report_reject') }}"
                                    class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('production.reports.report_reject') ? 'active' : '' }}">
                                    <span class="icon">⚠️</span>
                                    <span>Reject Analysis</span>
                                </a>

                                <a href="{{ route('production.reports.lead_time') }}"
                                    class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('production.reports.lead_time') ? 'active' : '' }}">
                                    <span class="icon">⏱️</span>
                                    <span>Lead Time</span>
                                </a>

                                <a href="{{ route('production.reports.operator_behavior') }}"
                                    class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('production.reports.operator_behavior') ? 'active' : '' }}">
                                    <span class="icon">👀</span>
                                    <span>Operator Behavior</span>
                                </a>
                            </div>
                        </li>

                        {{-- Finishing --}}
                        <li class="mb-1">
                            <button class="mobile-sidebar-link mobile-sidebar-toggle {{ $prodFinOpen ? 'is-open' : '' }}"
                                type="button" data-bs-toggle="collapse" data-bs-target="#navProductionFinishingMobile"
                                aria-expanded="{{ $prodFinOpen ? 'true' : 'false' }}"
                                aria-controls="navProductionFinishingMobile">
                                <span class="icon">🧶</span>
                                <span>Finishing</span>
                                <span class="chevron">▸</span>
                            </button>

                            <div class="collapse {{ $prodFinOpen ? 'show' : '' }}" id="navProductionFinishingMobile">
                                <a href="{{ route('production.finishing_jobs.index') }}"
                                    class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('production.finishing_jobs.index') ? 'active' : '' }}">
                                    <span class="icon">≡</span>
                                    <span>Daftar Finishing Job</span>
                                </a>

                                <a href="{{ route('production.finishing_jobs.create') }}"
                                    class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('production.finishing_jobs.create') ? 'active' : '' }}">
                                    <span class="icon">＋</span>
                                    <span>Finishing Job Baru</span>
                                </a>

                                <a href="{{ route('production.finishing_jobs.bundles_ready') }}"
                                    class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('production.finishing_jobs.bundles_ready') ? 'active' : '' }}">
                                    <span class="icon">📦</span>
                                    <span>Bundles Ready for Finishing</span>
                                </a>
                            </div>
                        </li>

                        {{-- Packing --}}
                        <li class="mb-1">
                            <button
                                class="mobile-sidebar-link mobile-sidebar-toggle {{ $prodPackOpen ? 'is-open' : '' }}"
                                type="button" data-bs-toggle="collapse" data-bs-target="#navProductionPackingMobile"
                                aria-expanded="{{ $prodPackOpen ? 'true' : 'false' }}"
                                aria-controls="navProductionPackingMobile">
                                <span class="icon">📦</span>
                                <span>Packing</span>
                                <span class="chevron">▸</span>
                            </button>

                            <div class="collapse {{ $prodPackOpen ? 'show' : '' }}" id="navProductionPackingMobile">
                                <a href="{{ route('production.packing_jobs.index') }}"
                                    class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('production.packing_jobs.index') ? 'active' : '' }}">
                                    <span class="icon">≡</span>
                                    <span>Daftar Packing Job</span>
                                </a>

                                <a href="{{ route('production.packing_jobs.create') }}"
                                    class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('production.packing_jobs.create') ? 'active' : '' }}">
                                    <span class="icon">＋</span>
                                    <span>Packing Job Baru</span>
                                </a>

                                <a href="{{ route('production.packing_jobs.ready_items') }}"
                                    class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('production.packing_jobs.ready_items') ? 'active' : '' }}">
                                    <span class="icon">📦</span>
                                    <span>Ready Items (WH-PRD)</span>
                                </a>
                            </div>
                        </li>

                        {{-- QC --}}
                        <li class="mb-1">
                            <button class="mobile-sidebar-link mobile-sidebar-toggle {{ $prodQcOpen ? 'is-open' : '' }}"
                                type="button" data-bs-toggle="collapse" data-bs-target="#navProductionQcMobile"
                                aria-expanded="{{ $prodQcOpen ? 'true' : 'false' }}"
                                aria-controls="navProductionQcMobile">
                                <span class="icon">✅</span>
                                <span>Quality Control</span>
                                <span class="chevron">▸</span>
                            </button>

                            <div class="collapse {{ $prodQcOpen ? 'show' : '' }}" id="navProductionQcMobile">
                                <a href="{{ route('production.qc.index') }}"
                                    class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('production.qc.index') || request()->routeIs('production.qc.cutting.*') ? 'active' : '' }}">
                                    <span class="icon">✂️</span>
                                    <span>QC Cutting</span>
                                </a>
                            </div>
                        </li>

                        {{-- Laporan Produksi --}}
                        <li class="mb-1">
                            <button
                                class="mobile-sidebar-link mobile-sidebar-toggle {{ $prodReportOpen ? 'is-open' : '' }}"
                                type="button" data-bs-toggle="collapse" data-bs-target="#navProductionReportsMobile"
                                aria-expanded="{{ $prodReportOpen ? 'true' : 'false' }}"
                                aria-controls="navProductionReportsMobile">
                                <span class="icon">📈</span>
                                <span>Laporan Produksi</span>
                                <span class="chevron">▸</span>
                            </button>

                            <div class="collapse {{ $prodReportOpen ? 'show' : '' }}" id="navProductionReportsMobile">
                                <a href="{{ route('production.reports.daily_production') }}"
                                    class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('production.reports.daily_production') ? 'active' : '' }}">
                                    <span class="icon">📆</span>
                                    <span>Daily Production Summary</span>
                                </a>

                                <a href="{{ route('production.reports.wip_sewing_age') }}"
                                    class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('production.reports.wip_sewing_age') ? 'active' : '' }}">
                                    <span class="icon">⏳</span>
                                    <span>WIP Sewing Age</span>
                                </a>

                                <a href="{{ route('production.reports.sewing_per_item') }}"
                                    class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('production.reports.sewing_per_item') ? 'active' : '' }}">
                                    <span class="icon">🧵</span>
                                    <span>Sewing per Item</span>
                                </a>

                                <a href="{{ route('production.reports.reject_detail') }}"
                                    class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('production.reports.reject_detail') ? 'active' : '' }}">
                                    <span class="icon">⚠️</span>
                                    <span>Reject Detail</span>
                                </a>

                                <a href="{{ route('production.reports.finishing_jobs') }}"
                                    class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('production.reports.finishing_jobs') ? 'active' : '' }}">
                                    <span class="icon">🧶</span>
                                    <span>Finishing Jobs Summary</span>
                                </a>

                                <a href="{{ route('production.finishing_jobs.report_per_item') }}"
                                    class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('production.finishing_jobs.report_per_item') || request()->routeIs('production.finishing_jobs.report_per_item_detail') ? 'active' : '' }}">
                                    <span class="icon">📦</span>
                                    <span>Finishing per Item</span>
                                </a>
                            </div>
                        </li>
                    @endif

                    {{-- FINANCE (owner only) --}}
                    @if ($userRole === 'owner')
                        <div class="mobile-sidebar-section-label">Finance</div>

                        {{-- Payroll --}}
                        <li class="mb-1">
                            <button class="mobile-sidebar-link mobile-sidebar-toggle {{ $payrollOpen ? 'is-open' : '' }}"
                                type="button" data-bs-toggle="collapse" data-bs-target="#navFinancePayrollMobile"
                                aria-expanded="{{ $payrollOpen ? 'true' : 'false' }}"
                                aria-controls="navFinancePayrollMobile">
                                <span class="icon">💰</span>
                                <span>Payroll</span>
                                <span class="chevron">▸</span>
                            </button>

                            <div class="collapse {{ $payrollOpen ? 'show' : '' }}" id="navFinancePayrollMobile">
                                <a href="{{ route('payroll.cutting.index') }}"
                                    class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('payroll.cutting.*') ? 'active' : '' }}">
                                    <span class="icon">✂️</span>
                                    <span>Cutting Payroll</span>
                                </a>

                                <a href="{{ route('payroll.sewing.index') }}"
                                    class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('payroll.sewing.*') ? 'active' : '' }}">
                                    <span class="icon">🧵</span>
                                    <span>Sewing Payroll</span>
                                </a>

                                <a href="{{ route('payroll.piece_rates.index') }}"
                                    class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('payroll.piece_rates.*') ? 'active' : '' }}">
                                    <span class="icon">📑</span>
                                    <span>Master Piece Rates</span>
                                </a>

                                <div class="mobile-sidebar-section-label" style="margin-top:.4rem;">Payroll Reports</div>

                                <a href="{{ route('payroll.reports.operators') }}"
                                    class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('payroll.reports.operators') || request()->routeIs('payroll.reports.operator_detail') ? 'active' : '' }}">
                                    <span class="icon">📊</span>
                                    <span>Rekap per Operator</span>
                                </a>

                                <a href="{{ route('payroll.reports.operator_slips') }}"
                                    class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('payroll.reports.operator_slips') ? 'active' : '' }}">
                                    <span class="icon">🧾</span>
                                    <span>Slip Borongan (All)</span>
                                </a>
                            </div>
                        </li>

                        {{-- Costing / HPP --}}
                        <li class="mb-1">
                            <button class="mobile-sidebar-link mobile-sidebar-toggle {{ $costingOpen ? 'is-open' : '' }}"
                                type="button" data-bs-toggle="collapse" data-bs-target="#navFinanceCostingMobile"
                                aria-expanded="{{ $costingOpen ? 'true' : 'false' }}"
                                aria-controls="navFinanceCostingMobile">
                                <span class="icon">📉</span>
                                <span>Costing &amp; HPP</span>
                                <span class="chevron">▸</span>
                            </button>

                            <div class="collapse {{ $costingOpen ? 'show' : '' }}" id="navFinanceCostingMobile">
                                <a href="{{ route('costing.hpp.index') }}"
                                    class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('costing.hpp.*') ? 'active' : '' }}">
                                    <span class="icon">⚙️</span>
                                    <span>HPP Finished Goods</span>
                                </a>

                                <a href="{{ route('costing.production_cost_periods.index') }}"
                                    class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('costing.production_cost_periods.*') ? 'active' : '' }}">
                                    <span class="icon">📆</span>
                                    <span>Production Cost Periods</span>
                                </a>
                            </div>
                        </li>

                        {{-- Finance Reports --}}
                        <li class="mb-1">
                            <button
                                class="mobile-sidebar-link mobile-sidebar-toggle {{ $financeReportsOpen ? 'is-open' : '' }}"
                                type="button" data-bs-toggle="collapse" data-bs-target="#navFinanceReportsMobile"
                                aria-expanded="{{ $financeReportsOpen ? 'true' : 'false' }}"
                                aria-controls="navFinanceReportsMobile">
                                <span class="icon">📊</span>
                                <span>Finance Reports</span>
                                <span class="chevron">▸</span>
                            </button>

                            <div class="collapse {{ $financeReportsOpen ? 'show' : '' }}" id="navFinanceReportsMobile">
                                <a href="{{ route('sales.reports.item_profit') }}"
                                    class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('sales.reports.item_profit') ? 'active' : '' }}">
                                    <span class="icon">💹</span>
                                    <span>Laba Rugi per Item</span>
                                </a>

                                <a href="{{ route('sales.reports.channel_profit') }}"
                                    class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('sales.reports.channel_profit') ? 'active' : '' }}">
                                    <span class="icon">🏬</span>
                                    <span>Laba Rugi per Channel</span>
                                </a>

                                <a href="{{ route('sales.reports.shipment_analytics') }}"
                                    class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('sales.reports.shipment_analytics') ? 'active' : '' }}">
                                    <span class="icon">📦</span>
                                    <span>Shipment Analytics</span>
                                </a>
                            </div>
                        </li>
                    @endif {{-- end owner --}}
                @endif {{-- end non-operator --}}
            @endauth
        </ul>
    </div>

    <div class="mobile-sidebar-footer">
        <div class="d-flex justify-content-between">
            <span>{{ now()->format('d/m/Y') }}</span>
            <span class="mono">{{ Auth::user()->name ?? '' }}</span>
        </div>
    </div>
</aside>

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const toggleBtn = document.getElementById('mobileSidebarToggle');
            const closeBtn = document.getElementById('mobileSidebarCloseBtn');
            const sidebar = document.getElementById('mobileSidebarPanel');
            const overlay = document.getElementById('mobileSidebarOverlay');
            const links = sidebar ? sidebar.querySelectorAll('.mobile-sidebar-link[href]') : [];

            if (!toggleBtn || !sidebar || !overlay) return;

            function openSidebar() {
                sidebar.classList.add('is-open');
                overlay.classList.add('is-open');
                document.body.dataset.prevOverflow = document.body.style.overflow || '';
                document.body.style.overflow = 'hidden';
            }

            function closeSidebar() {
                sidebar.classList.remove('is-open');
                overlay.classList.remove('is-open');
                document.body.style.overflow = document.body.dataset.prevOverflow || '';
            }

            toggleBtn.addEventListener('click', function() {
                if (sidebar.classList.contains('is-open')) {
                    closeSidebar();
                } else {
                    openSidebar();
                }
            });

            closeBtn?.addEventListener('click', closeSidebar);
            overlay.addEventListener('click', closeSidebar);

            // Auto-close ketika klik link menu
            links.forEach(link => {
                link.addEventListener('click', () => {
                    closeSidebar();
                });
            });

            document.addEventListener('keyup', function(e) {
                if (e.key === 'Escape') {
                    closeSidebar();
                }
            });
        });
    </script>
@endpush
