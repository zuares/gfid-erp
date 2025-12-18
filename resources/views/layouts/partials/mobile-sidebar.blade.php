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

    /* dropdown (owner only) */
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

    $isOwner = $userRole === 'owner';
    $isAdmin = $userRole === 'admin';
    $isOperating = $userRole === 'operating';

    // operator lapangan (menu super ringkas)
    $isOperatorRole = in_array($userRole, ['sewing', 'cutting']);

    // OPEN STATE (OWNER dropdown only)
    $masterOpen = request()->routeIs('master.customers.*') || request()->routeIs('master.items.*');

    $poOpen = request()->routeIs('purchasing.purchase_orders.*');
    $grnOpen = request()->routeIs('purchasing.purchase_receipts.*');

    $marketplaceOpen = request()->routeIs('marketplace.orders.*');

    $salesInvoiceOpen = request()->routeIs('sales.invoices.*');
    $salesShipmentOpen = request()->routeIs('sales.shipments.*');
    $salesReportOpen = request()->routeIs('sales.reports.*');
    $salesOpen = $salesInvoiceOpen || $salesShipmentOpen || $salesReportOpen;

    $invOpen =
        request()->routeIs('inventory.stocks.*') ||
        request()->routeIs('inventory.stock_opnames.*') ||
        request()->routeIs('inventory.adjustments.*') ||
        request()->routeIs('inventory.stock_card.*') ||
        request()->routeIs('inventory.transfers.*') ||
        request()->routeIs('inventory.external_transfers.*');

    $stockReqOpen = request()->routeIs('rts.stock-requests.*') || request()->routeIs('prd.stock-requests.*');

    $prodCutOpen = request()->routeIs('production.cutting_jobs.*');
    $prodSewOpen =
        request()->routeIs('production.sewing_pickups.*') ||
        request()->routeIs('production.sewing_returns.*') ||
        request()->routeIs('production.reports.*');

    $prodFinOpen =
        request()->routeIs('production.finishing_jobs.*') ||
        request()->routeIs('production.finishing_jobs.bundles_ready');
    $prodPackOpen =
        request()->routeIs('production.packing_jobs.*') || request()->routeIs('production.packing_jobs.ready_items');
    $prodQcOpen = request()->routeIs('production.qc.*');
    $prodReportOpen =
        request()->routeIs('production.reports.*') || request()->routeIs('production.finishing_jobs.report_per_item*');

    $payrollOpen =
        request()->routeIs('payroll.cutting.*') ||
        request()->routeIs('payroll.sewing.*') ||
        request()->routeIs('payroll.piece_rates.*') ||
        request()->routeIs('payroll.reports.*');

    $costingOpen = request()->routeIs('costing.hpp.*') || request()->routeIs('costing.production_cost_periods.*');
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
        <button type="button" class="mobile-sidebar-close-btn" id="mobileSidebarCloseBtn">✕</button>
    </div>

    <div class="mobile-sidebar-body">
        <ul class="mobile-sidebar-nav">
            @auth

                {{-- ============================
                    MODE OPERATOR LAPANGAN ONLY
                    (role: sewing / cutting)
                ============================ --}}
                @if ($isOperatorRole)

                    <li>
                        <a href="{{ route('dashboard') }}"
                            class="mobile-sidebar-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                            <span class="icon">🏠</span>
                            <span>Dashboard</span>
                        </a>
                    </li>

                    <div class="mobile-sidebar-section-label">Production</div>

                    <li>
                        <a href="{{ route('production.cutting_jobs.create') }}"
                            class="mobile-sidebar-link {{ request()->routeIs('production.cutting_jobs.create') ? 'active' : '' }}">
                            <span class="icon">✂️</span>
                            <span>Cutting Job Baru</span>
                        </a>
                    </li>

                    <li>
                        <a href="{{ route('production.sewing_pickups.create') }}"
                            class="mobile-sidebar-link {{ request()->routeIs('production.sewing_pickups.create') ? 'active' : '' }}">
                            <span class="icon">📤</span>
                            <span>Sewing Pickup Baru</span>
                        </a>
                    </li>

                    <li>
                        <a href="{{ route('production.sewing_returns.create') }}"
                            class="mobile-sidebar-link {{ request()->routeIs('production.sewing_returns.create') ? 'active' : '' }}">
                            <span class="icon">📥</span>
                            <span>Sewing Return Baru</span>
                        </a>
                    </li>

                    <li>
                        <a href="{{ route('production.finishing_jobs.create') }}"
                            class="mobile-sidebar-link {{ request()->routeIs('production.finishing_jobs.create') ? 'active' : '' }}">
                            <span class="icon">🧶</span>
                            <span>Finishing Job Baru</span>
                        </a>
                    </li>
                @else
                    {{-- DASHBOARD --}}
                    <li>
                        <a href="{{ route('dashboard') }}"
                            class="mobile-sidebar-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                            <span class="icon">🏠</span>
                            <span>Dashboard</span>
                        </a>
                    </li>

                    {{-- =====================================================
                        SIMPLE (NO DROPDOWN): ADMIN + OPERATING
                    ====================================================== --}}
                    @if ($isAdmin || $isOperating)
                        <div class="mobile-sidebar-section-label">Inventory</div>

                        <li>
                            <a href="{{ route('inventory.stocks.items') }}"
                                class="mobile-sidebar-link {{ request()->routeIs('inventory.stocks.items') ? 'active' : '' }}">
                                <span class="icon">📦</span>
                                <span>Stok Barang</span>
                            </a>
                        </li>

                        <li>
                            <a href="{{ route('inventory.stock_opnames.index') }}"
                                class="mobile-sidebar-link {{ request()->routeIs('inventory.stock_opnames.*') ? 'active' : '' }}">
                                <span class="icon">📊</span>
                                <span>Stock Opname</span>
                            </a>
                        </li>

                        {{-- ADMIN tambahan: Shipments --}}
                        @if ($isAdmin)
                            <div class="mobile-sidebar-section-label">Sales</div>

                            <li>
                                <a href="{{ route('sales.shipments.index') }}"
                                    class="mobile-sidebar-link {{ request()->routeIs('sales.shipments.*') ? 'active' : '' }}">
                                    <span class="icon">🚚</span>
                                    <span>Shipments</span>
                                </a>
                            </li>
                        @endif

                        <div class="mobile-sidebar-section-label">Stock Requests</div>

                        @if ($isAdmin)
                            <li>
                                <a href="{{ route('rts.stock-requests.index') }}"
                                    class="mobile-sidebar-link {{ request()->routeIs('rts.stock-requests.*') ? 'active' : '' }}">
                                    <span class="icon">🛒</span>
                                    <span>Permintaan Stock (RTS)</span>
                                </a>
                            </li>
                        @endif

                        @if ($isOperating)
                            <li>
                                <a href="{{ route('prd.stock-requests.index') }}"
                                    class="mobile-sidebar-link {{ request()->routeIs('prd.stock-requests.*') ? 'active' : '' }}">
                                    <span class="icon">🏭</span>
                                    <span>Proses Stock Request (PRD)</span>
                                </a>
                            </li>

                            <div class="mobile-sidebar-section-label">Production</div>

                            <li>
                                <a href="{{ route('production.cutting_jobs.index') }}"
                                    class="mobile-sidebar-link {{ request()->routeIs('production.cutting_jobs.*') ? 'active' : '' }}">
                                    <span class="icon">✂️</span>
                                    <span>Daftar Cutting Jobs</span>
                                </a>
                            </li>

                            <li>
                                <a href="{{ route('production.sewing_pickups.index') }}"
                                    class="mobile-sidebar-link {{ request()->routeIs('production.sewing_pickups.*') ? 'active' : '' }}">
                                    <span class="icon">🧵</span>
                                    <span>Daftar Sewing Pickups</span>
                                </a>
                            </li>

                            <li>
                                <a href="{{ route('production.sewing_returns.index') }}"
                                    class="mobile-sidebar-link {{ request()->routeIs('production.sewing_returns.*') ? 'active' : '' }}">
                                    <span class="icon">📥</span>
                                    <span>Daftar Sewing Returns</span>
                                </a>
                            </li>

                            <li>
                                <a href="{{ route('production.finishing_jobs.index') }}"
                                    class="mobile-sidebar-link {{ request()->routeIs('production.finishing_jobs.*') ? 'active' : '' }}">
                                    <span class="icon">🧶</span>
                                    <span>Daftar Finishing</span>
                                </a>
                            </li>

                            <li>
                                <a href="{{ route('production.qc.index') }}"
                                    class="mobile-sidebar-link {{ request()->routeIs('production.qc.*') ? 'active' : '' }}">
                                    <span class="icon">✅</span>
                                    <span>QC Cutting</span>
                                </a>
                            </li>
                        @endif

                        {{-- =====================================================
                        OWNER (FULL): tetap dropdown lengkap
                    ====================================================== --}}
                    @elseif ($isOwner)
                        {{-- MASTER DATA --}}
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
                                <a href="{{ route('master.items.index') }}"
                                    class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('master.items.*') ? 'active' : '' }}">
                                    <span class="icon">📦</span>
                                    <span>Items</span>
                                </a>

                                <a href="{{ route('master.customers.index') }}"
                                    class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('master.customers.*') ? 'active' : '' }}">
                                    <span class="icon">👤</span>
                                    <span>Customers</span>
                                </a>
                            </div>
                        </li>

                        {{-- PURCHASING --}}
                        <div class="mobile-sidebar-section-label">Purchasing</div>

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

                        {{-- SALES & MARKETPLACE --}}
                        <div class="mobile-sidebar-section-label">Sales &amp; Marketplace</div>

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

                        <li class="mb-1">
                            <button class="mobile-sidebar-link mobile-sidebar-toggle {{ $salesOpen ? 'is-open' : '' }}"
                                type="button" data-bs-toggle="collapse" data-bs-target="#navSalesMobile"
                                aria-expanded="{{ $salesOpen ? 'true' : 'false' }}" aria-controls="navSalesMobile">
                                <span class="icon">📑</span>
                                <span>Sales</span>
                                <span class="chevron">▸</span>
                            </button>

                            <div class="collapse {{ $salesOpen ? 'show' : '' }}" id="navSalesMobile">
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

                                <div class="mobile-sidebar-section-label" style="margin-top:.4rem;">Shipments</div>

                                <a href="{{ route('sales.shipments.index') }}"
                                    class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('sales.shipments.*') ? 'active' : '' }}">
                                    <span class="icon">🚚</span>
                                    <span>Daftar Shipment</span>
                                </a>

                                <a href="{{ route('sales.shipments.create') }}"
                                    class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('sales.shipments.create') ? 'active' : '' }}">
                                    <span class="icon">＋</span>
                                    <span>Shipment Baru</span>
                                </a>

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

                        {{-- INVENTORY (OWNER FULL) --}}
                        <div class="mobile-sidebar-section-label">Inventory</div>

                        <li class="mb-1">
                            <button class="mobile-sidebar-link mobile-sidebar-toggle {{ $invOpen ? 'is-open' : '' }}"
                                type="button" data-bs-toggle="collapse" data-bs-target="#navInventoryMobile"
                                aria-expanded="{{ $invOpen ? 'true' : 'false' }}" aria-controls="navInventoryMobile">
                                <span class="icon">📦</span>
                                <span>Inventory</span>
                                <span class="chevron">▸</span>
                            </button>

                            <div class="collapse {{ $invOpen ? 'show' : '' }}" id="navInventoryMobile">
                                <div class="mobile-sidebar-section-label" style="margin-top:.35rem;">Stock</div>

                                <a href="{{ route('inventory.stocks.items') }}"
                                    class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('inventory.stocks.items') ? 'active' : '' }}">
                                    <span class="icon">📦</span>
                                    <span>Stok Barang</span>
                                </a>

                                <a href="{{ route('inventory.stocks.lots') }}"
                                    class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('inventory.stocks.lots') ? 'active' : '' }}">
                                    <span class="icon">🎫</span>
                                    <span>Stok per LOT</span>
                                </a>

                                <a href="{{ route('inventory.stock_card.index') }}"
                                    class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('inventory.stock_card.*') ? 'active' : '' }}">
                                    <span class="icon">📋</span>
                                    <span>Kartu Stok</span>
                                </a>

                                <a href="{{ route('inventory.transfers.index') }}"
                                    class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('inventory.transfers.*') ? 'active' : '' }}">
                                    <span class="icon">🔁</span>
                                    <span>Transfer</span>
                                </a>

                                <a href="{{ route('inventory.external_transfers.index') }}"
                                    class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('inventory.external_transfers.*') ? 'active' : '' }}">
                                    <span class="icon">🚚</span>
                                    <span>External Transfers</span>
                                </a>

                                <div class="mobile-sidebar-section-label" style="margin-top:.55rem;">Opname</div>

                                <a href="{{ route('inventory.stock_opnames.index') }}"
                                    class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('inventory.stock_opnames.*') ? 'active' : '' }}">
                                    <span class="icon">📊</span>
                                    <span>Stock Opname</span>
                                </a>

                                <a href="{{ route('inventory.adjustments.index') }}"
                                    class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('inventory.adjustments.*') ? 'active' : '' }}">
                                    <span class="icon">⚖️</span>
                                    <span>Adjustments</span>
                                </a>
                            </div>
                        </li>

                        {{-- STOCK REQUESTS (OWNER) --}}
                        <div class="mobile-sidebar-section-label">Stock Requests</div>
                        <li class="mb-1">
                            <button class="mobile-sidebar-link mobile-sidebar-toggle {{ $stockReqOpen ? 'is-open' : '' }}"
                                type="button" data-bs-toggle="collapse" data-bs-target="#navStockRequestsMobile"
                                aria-expanded="{{ $stockReqOpen ? 'true' : 'false' }}"
                                aria-controls="navStockRequestsMobile">
                                <span class="icon">📤</span>
                                <span>Stock Requests</span>
                                <span class="chevron">▸</span>
                            </button>

                            <div class="collapse {{ $stockReqOpen ? 'show' : '' }}" id="navStockRequestsMobile">
                                <a href="{{ route('rts.stock-requests.index') }}"
                                    class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('rts.stock-requests.*') ? 'active' : '' }}">
                                    <span class="icon">🛒</span>
                                    <span>Permintaan Stock (RTS)</span>
                                </a>

                                <a href="{{ route('prd.stock-requests.index') }}"
                                    class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('prd.stock-requests.*') ? 'active' : '' }}">
                                    <span class="icon">🏭</span>
                                    <span>Proses Stock Request (PRD)</span>
                                </a>
                            </div>
                        </li>

                        {{-- PRODUCTION (OWNER) --}}
                        <div class="mobile-sidebar-section-label">Production</div>

                        <li class="mb-1">
                            <button class="mobile-sidebar-link mobile-sidebar-toggle {{ $prodCutOpen ? 'is-open' : '' }}"
                                type="button" data-bs-toggle="collapse" data-bs-target="#navProductionCuttingMobile"
                                aria-expanded="{{ $prodCutOpen ? 'true' : 'false' }}"
                                aria-controls="navProductionCuttingMobile">
                                <span class="icon">✂️</span>
                                <span>Cutting</span>
                                <span class="chevron">▸</span>
                            </button>
                            <div class="collapse {{ $prodCutOpen ? 'show' : '' }}" id="navProductionCuttingMobile">
                                <a href="{{ route('production.cutting_jobs.index') }}"
                                    class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('production.cutting_jobs.*') ? 'active' : '' }}">
                                    <span class="icon">≡</span><span>Daftar Cutting Job</span>
                                </a>
                                <a href="{{ route('production.cutting_jobs.create') }}"
                                    class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('production.cutting_jobs.create') ? 'active' : '' }}">
                                    <span class="icon">＋</span><span>Cutting Job Baru</span>
                                </a>
                            </div>
                        </li>

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
                                    class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('production.sewing_pickups.*') ? 'active' : '' }}">
                                    <span class="icon">📤</span><span>Sewing Pickups</span>
                                </a>
                                <a href="{{ route('production.sewing_returns.index') }}"
                                    class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('production.sewing_returns.*') ? 'active' : '' }}">
                                    <span class="icon">📥</span><span>Sewing Returns</span>
                                </a>
                                <a href="{{ route('production.reports.dashboard') }}"
                                    class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('production.reports.dashboard') ? 'active' : '' }}">
                                    <span class="icon">📋</span><span>Daily Dashboard</span>
                                </a>
                            </div>
                        </li>

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
                                    class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('production.finishing_jobs.*') ? 'active' : '' }}">
                                    <span class="icon">≡</span><span>Daftar Finishing</span>
                                </a>
                                <a href="{{ route('production.finishing_jobs.create') }}"
                                    class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('production.finishing_jobs.create') ? 'active' : '' }}">
                                    <span class="icon">＋</span><span>Finishing Baru</span>
                                </a>
                            </div>
                        </li>

                        <li class="mb-1">
                            <button class="mobile-sidebar-link mobile-sidebar-toggle {{ $prodQcOpen ? 'is-open' : '' }}"
                                type="button" data-bs-toggle="collapse" data-bs-target="#navProductionQcMobile"
                                aria-expanded="{{ $prodQcOpen ? 'true' : 'false' }}"
                                aria-controls="navProductionQcMobile">
                                <span class="icon">✅</span>
                                <span>QC</span>
                                <span class="chevron">▸</span>
                            </button>
                            <div class="collapse {{ $prodQcOpen ? 'show' : '' }}" id="navProductionQcMobile">
                                <a href="{{ route('production.qc.index') }}"
                                    class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('production.qc.*') ? 'active' : '' }}">
                                    <span class="icon">✂️</span><span>QC Cutting</span>
                                </a>
                            </div>
                        </li>

                        {{-- FINANCE (OWNER ONLY) --}}
                        <div class="mobile-sidebar-section-label">Finance</div>

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
                                    <span class="icon">✂️</span><span>Cutting Payroll</span>
                                </a>
                                <a href="{{ route('payroll.sewing.index') }}"
                                    class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('payroll.sewing.*') ? 'active' : '' }}">
                                    <span class="icon">🧵</span><span>Sewing Payroll</span>
                                </a>
                                <a href="{{ route('payroll.piece_rates.index') }}"
                                    class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('payroll.piece_rates.*') ? 'active' : '' }}">
                                    <span class="icon">📑</span><span>Piece Rates</span>
                                </a>
                            </div>
                        </li>

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
                                    <span class="icon">⚙️</span><span>HPP Finished Goods</span>
                                </a>
                                <a href="{{ route('costing.production_cost_periods.index') }}"
                                    class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('costing.production_cost_periods.*') ? 'active' : '' }}">
                                    <span class="icon">📆</span><span>Production Cost Periods</span>
                                </a>
                            </div>
                        </li>
                    @endif
                @endif
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
                sidebar.classList.contains('is-open') ? closeSidebar() : openSidebar();
            });

            closeBtn?.addEventListener('click', closeSidebar);
            overlay.addEventListener('click', closeSidebar);

            // Auto-close ketika klik link menu
            links.forEach(link => link.addEventListener('click', closeSidebar));

            document.addEventListener('keyup', function(e) {
                if (e.key === 'Escape') closeSidebar();
            });
        });
    </script>
@endpush
