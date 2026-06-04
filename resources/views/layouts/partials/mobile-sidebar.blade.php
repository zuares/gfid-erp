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
        font-weight: 700;
        font-size: 1rem;
        letter-spacing: -.01em;
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
        position: relative;
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

    /* dropdown */
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

    /* dot badge (warn) */
    .ms-dot {
        margin-left: auto;
        width: 10px;
        height: 10px;
        border-radius: 999px;
        background: rgba(245, 158, 11, 1);
        box-shadow: 0 0 0 3px rgba(245, 158, 11, .18);
    }

    @media (min-width: 768px) {
        .mobile-sidebar-overlay,
        .mobile-sidebar-panel {
            display: none;
        }
    }
</style>

@php
    use Illuminate\Support\Facades\Cache;
    use Illuminate\Support\Facades\DB;

    $user = auth()->user();
    $role = strtolower((string) ($user?->role ?? ''));

    $isOwner = $role === 'owner';
    $isAdmin = $role === 'admin';
    $isOperating = $role === 'operating';

    // operator lapangan
    $isOperatorRole = in_array($role, ['sewing', 'cutting']);

    // capability flags (match desktop)
    $canViewRts = $isOwner || $isAdmin || $isOperating;
    $canManageRts = $isOwner || $isAdmin;

    $router = app('router');

    // ROUTE GUARDS
    $hasDashboardRoute = $router->has('dashboard');

    // Master
    $hasMasterItemsIndex = $router->has('master.items.index');
    $hasMasterCustomersIndex = $router->has('master.customers.index');
    $hasMasterSuppliersIndex = $router->has('master.suppliers.index');
    $hasMasterItemBomsIndex = $router->has('master.item_boms.index');
    $hasMasterEmployeesIndex = $router->has('master.employees.index');

    // Purchasing
    $hasPoIndex = $router->has('purchasing.purchase_orders.index');
    $hasPoCreate = $router->has('purchasing.purchase_orders.create');
    $hasGrnIndex = $router->has('purchasing.purchase_receipts.index');
    $hasGrnCreate = $router->has('purchasing.purchase_receipts.create');

    // Marketplace
    $hasMarketplaceIndex = $router->has('marketplace.orders.index');
    $hasMarketplaceCreate = $router->has('marketplace.orders.create');

    // Sales
    $hasSalesInvoicesIndex = $router->has('sales.invoices.index');
    $hasSalesInvoicesCreate = $router->has('sales.invoices.create');
    $hasSalesShipmentsIndex = $router->has('sales.shipments.index');
    $hasSalesShipmentsCreate = $router->has('sales.shipments.create');
    $hasSalesShipmentReturnsIndex = $router->has('sales.shipment_returns.index');
    $hasSalesShipmentReturnsCreate = $router->has('sales.shipment_returns.create');
    $hasSalesShipmentsReport = $router->has('sales.shipments.report');

    // Sales reports (opsional—kamu punya beberapa, tapi bukan index)
    $hasSalesReportItemProfit = $router->has('sales.reports.item_profit');
    $hasSalesReportChannelProfit = $router->has('sales.reports.channel_profit');
    $hasSalesReportShipmentAnalytics = $router->has('sales.reports.shipment_analytics');

    // Inventory
    $hasInvStocksItems = $router->has('inventory.stocks.items');
    $hasInvStocksLots = $router->has('inventory.stocks.lots');
    $hasInvStockCard = $router->has('inventory.stock_card.index');
    $hasInvTransfersIndex = $router->has('inventory.transfers.index');
    $hasInvTransfersCreate = $router->has('inventory.transfers.create');
    $hasInvAdjustmentsIndex = $router->has('inventory.adjustments.index');
    $hasInvWipAdjIndex = $router->has('inventory.wip_adjustments.index');
    $hasInvWipCutReconcile = $router->has('inventory.wip_cut_reconcile.index');
    $hasInvOpnamesIndex = $router->has('inventory.stock_opnames.index');
    $hasInvOpnamesCreate = $router->has('inventory.stock_opnames.create');
    $hasInvExternalIndex = $router->has('inventory.external_transfers.index');
    $hasInvExternalCreate = $router->has('inventory.external_transfers.create');

    // RTS
    $hasRtsStockReqIndex = $router->has('rts.stock-requests.index');
    $hasRtsDirectReceiveIndex = $router->has('rts.direct-receives.index');

    // Production (Jobs)
    $hasProdCuttingJobsIndex = $router->has('production.cutting_jobs.index');
    $hasProdCuttingJobsCreate = $router->has('production.cutting_jobs.create');

    $hasProdSewPickupsIndex = $router->has('production.sewing.pickups.index');
    $hasProdSewPickupsCreate = $router->has('production.sewing.pickups.create');

    $hasProdSewReturnsIndex = $router->has('production.sewing.returns.index');
    $hasProdSewReturnsCreate = $router->has('production.sewing.returns.create');

    $hasProdFinishingJobsIndex = $router->has('production.finishing_jobs.index');
    $hasProdFinishingJobsCreate = $router->has('production.finishing_jobs.create');

    $hasProdWipFinAdjIndex = $router->has('production.wip-fin-adjustments.index');
    $hasProdQcIndex = $router->has('production.qc.index');
    $hasProdPackingIndex = $router->has('production.packing_jobs.index');
    $hasProdMovementsIndex = $router->has('production.movements.index');
    $hasProdPriorityIndex = $router->has('production.priority.index');
    $hasProdReportsIndex = $router->has('production.reports.index');

    // Production dashboard (konsolidasi semua report)
    $hasProdDashboard = $router->has('production.dashboard');

    // Finance (Accounting)
    $hasCashExpensesIndex = $router->has('accounting.cash-expenses.index');
    $hasJournalsIndex = $router->has('accounting.journals.index');
    $hasAccountsIndex = $router->has('accounting.accounts.index');
    $hasOpeningBalancesIndex = $router->has('accounting.opening-balances.index');

    // Payroll
    $hasPayrollDashboard = $router->has('payroll.dashboard');
    $hasPieceworkIndex = $router->has('payroll.piecework.index');
    $hasPieceRatesIndex = $router->has('payroll.piece_rates.index');
    $hasPayrollReportsOperators = $router->has('payroll.reports.operators');
    $hasPayrollReportsOperatorSlips = $router->has('payroll.reports.operator_slips');

    // Costing
    $hasHppIndex = $router->has('costing.hpp.index');
    $hasProdCostPeriodsIndex = $router->has('costing.production_cost_periods.index');

    // OPEN STATES (match desktop)
    $masterOpen = request()->routeIs('master.*');
    $poOpen = request()->routeIs('purchasing.purchase_orders.*');
    $grnOpen = request()->routeIs('purchasing.purchase_receipts.*');
    $marketplaceOpen = request()->routeIs('marketplace.orders.*');

    $salesInvoiceOpen = request()->routeIs('sales.invoices.*');
    $salesShipmentOpen = request()->routeIs('sales.shipments.*');
    $salesShipmentReturnOpen = request()->routeIs('sales.shipment_returns.*');
    $salesReportOpen = request()->routeIs('sales.reports.*') || request()->routeIs('sales.shipments.report');
    $salesOpen = $salesInvoiceOpen || $salesShipmentOpen || $salesShipmentReturnOpen || $salesReportOpen;

    $invStocksOpen = request()->routeIs('inventory.stocks.*');
    $invOpnameOpen = request()->routeIs('inventory.stock_opnames.*');
    $invOwnerExtrasOpen =
        request()->routeIs('inventory.stock_card.*') ||
        request()->routeIs('inventory.transfers.*') ||
        request()->routeIs('inventory.external_transfers.*') ||
        request()->routeIs('inventory.adjustments.*') ||
        request()->routeIs('inventory.wip_adjustments.*');
    $invOpen = $invStocksOpen || $invOpnameOpen || $invOwnerExtrasOpen;

    $stockReqOpen = request()->routeIs('rts.stock-requests.*') || request()->routeIs('rts.direct-receives.*');

    $prodOpen =
        request()->routeIs('production.cutting_jobs.*') ||
        request()->routeIs('production.sewing.*') ||
        request()->routeIs('production.finishing_jobs.*') ||
        request()->routeIs('production.wip-fin-adjustments.*') ||
        request()->routeIs('production.qc.*') ||
        request()->routeIs('production.dashboard') ||
        request()->routeIs('production.packing_jobs.*') ||
        request()->routeIs('production.movements.*') ||
        request()->routeIs('production.priority.*') ||
        request()->routeIs('production.reports.*');

    $accountingOpen =
        request()->routeIs('accounting.cash-expenses.*') ||
        request()->routeIs('accounting.opening-balances.*') ||
        request()->routeIs('accounting.journals.*') ||
        request()->routeIs('accounting.accounts.*');

    $payrollOpen =
        request()->routeIs('payroll.dashboard*') ||
        request()->routeIs('payroll.piecework.*') ||
        request()->routeIs('payroll.piece_rates.*') ||
        request()->routeIs('payroll.reports.*');

    $costingOpen = request()->routeIs('costing.hpp.*') || request()->routeIs('costing.production_cost_periods.*');

    // BADGE (dot-only) – match desktop
    $rtsNeedReceiveQty = 0.0;
    $fmtQty = function ($n) {
        $n = (float) $n;
        return rtrim(rtrim(number_format($n, 2, '.', ''), '0'), '.');
    };

    if ($user && $hasRtsStockReqIndex) {
        $cacheKey = 'mobile_sidebar_badges_u' . (int) $user->id . '_r' . $role;

        $badges = Cache::remember($cacheKey, now()->addSeconds(20), function () {
            $rtsNeedReceiveQty = (float) DB::table('stock_request_lines as l')
                ->join('stock_requests as r', 'r.id', '=', 'l.stock_request_id')
                ->where('r.purpose', 'rts_replenish')
                ->whereIn('r.status', ['submitted', 'shipped', 'partial'])
                ->selectRaw(
                    'COALESCE(SUM(CASE WHEN (COALESCE(l.qty_dispatched,0) - COALESCE(l.qty_received,0)) > 0 THEN (COALESCE(l.qty_dispatched,0) - COALESCE(l.qty_received,0)) ELSE 0 END),0) as s'
                )
                ->value('s');

            return [
                'rtsNeedReceiveQty' => (float) $rtsNeedReceiveQty,
            ];
        });

        $rtsNeedReceiveQty = (float) ($badges['rtsNeedReceiveQty'] ?? 0);
    }

    $hasRtsNeedReceive = $rtsNeedReceiveQty > 0.000001;
    $rtsBadgeTitle = 'Perlu diterima: ' . $fmtQty($rtsNeedReceiveQty);

    // Payroll active helpers (optional)
    $activeModule = request()->route()?->parameter('module');
    $pieceworkCuttingActive = request()->routeIs('payroll.piecework.*') && $activeModule === 'cutting';
    $pieceworkSewingActive = request()->routeIs('payroll.piecework.*') && $activeModule === 'sewing';
@endphp

{{-- OVERLAY --}}
<div id="mobileSidebarOverlay" class="mobile-sidebar-overlay"></div>

{{-- PANEL --}}
<aside id="mobileSidebarPanel" class="mobile-sidebar-panel">
    <div class="mobile-sidebar-header">
        <div class="mobile-sidebar-title">{{ config('app.name', 'GFID') }}</div>
        <button type="button" class="mobile-sidebar-close-btn" id="mobileSidebarCloseBtn">✕</button>
    </div>

    <div class="mobile-sidebar-body">
        <ul class="mobile-sidebar-nav">
            @auth

                {{-- ============================
                    1) OPERATOR (sewing / cutting)
                ============================= --}}
                @if ($isOperatorRole)

                    @if ($hasDashboardRoute)
                        <li>
                            <a href="{{ route('dashboard') }}"
                               class="mobile-sidebar-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                                <span class="icon">🏠</span><span>Dashboard</span>
                            </a>
                        </li>
                    @endif

                    <div class="mobile-sidebar-section-label">Production</div>

                    @if ($hasProdCuttingJobsCreate)
                        <li>
                            <a href="{{ route('production.cutting_jobs.create') }}"
                               class="mobile-sidebar-link {{ request()->routeIs('production.cutting_jobs.create') ? 'active' : '' }}">
                                <span class="icon">✂️</span><span>Cutting Job Baru</span>
                            </a>
                        </li>
                    @endif

                    @if ($hasProdSewPickupsCreate)
                        <li>
                            <a href="{{ route('production.sewing.pickups.create') }}"
                               class="mobile-sidebar-link {{ request()->routeIs('production.sewing.pickups.create') ? 'active' : '' }}">
                                <span class="icon">📤</span><span>Sewing Pickup Baru</span>
                            </a>
                        </li>
                    @endif

                    @if ($hasProdSewReturnsCreate)
                        <li>
                            <a href="{{ route('production.sewing.returns.create') }}"
                               class="mobile-sidebar-link {{ request()->routeIs('production.sewing.returns.create') ? 'active' : '' }}">
                                <span class="icon">📥</span><span>Sewing Return Baru</span>
                            </a>
                        </li>
                    @endif

                    @if ($hasProdFinishingJobsCreate)
                        <li>
                            <a href="{{ route('production.finishing_jobs.create') }}"
                               class="mobile-sidebar-link {{ request()->routeIs('production.finishing_jobs.create') ? 'active' : '' }}">
                                <span class="icon">🧶</span><span>Finishing Job Baru</span>
                            </a>
                        </li>
                    @endif


                {{-- ============================
                    2) ADMIN / OPERATING
                ============================= --}}
                @elseif ($isAdmin || $isOperating)

                    @if ($hasDashboardRoute)
                        <li>
                            <a href="{{ route('dashboard') }}"
                               class="mobile-sidebar-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                                <span class="icon">🏠</span><span>Dashboard</span>
                            </a>
                        </li>
                    @endif

                    <div class="mobile-sidebar-section-label">Inventory</div>

                    @if ($hasInvStocksItems)
                        <li>
                            <a href="{{ route('inventory.stocks.items') }}"
                               class="mobile-sidebar-link {{ request()->routeIs('inventory.stocks.items') ? 'active' : '' }}">
                                <span class="icon">📦</span><span>Stok Barang</span>
                            </a>
                        </li>
                    @endif

                    @if ($hasInvOpnamesIndex)
                        <li>
                            <a href="{{ route('inventory.stock_opnames.index') }}"
                               class="mobile-sidebar-link {{ request()->routeIs('inventory.stock_opnames.*') ? 'active' : '' }}">
                                <span class="icon">📊</span><span>Stock Opname</span>
                            </a>
                        </li>
                    @endif

                    @if ($hasInvWipAdjIndex)
                        <li>
                            <a href="{{ route('inventory.wip_adjustments.index') }}"
                               class="mobile-sidebar-link {{ request()->routeIs('inventory.wip_adjustments.*') ? 'active' : '' }}">
                                <span class="icon">🧾</span><span>Koreksi WIP</span>
                            </a>
                        </li>
                    @endif

                    {{-- RTS (view for operating, manage for admin/owner) --}}
                    @if ($canViewRts && ($hasRtsStockReqIndex || $hasRtsDirectReceiveIndex))
                        <div class="mobile-sidebar-section-label">Stock Requests</div>

                        @if ($hasRtsStockReqIndex)
                            <li>
                                <a href="{{ route('rts.stock-requests.index') }}"
                                   class="mobile-sidebar-link {{ request()->routeIs('rts.stock-requests.*') ? 'active' : '' }}"
                                   @if($canManageRts && $hasRtsNeedReceive) title="{{ $rtsBadgeTitle }}" @endif
                                >
                                    <span class="icon">🛒</span><span>Permintaan Stock (RTS)</span>
                                    @if($canManageRts && $hasRtsNeedReceive)
                                        <span class="ms-dot" aria-hidden="true"></span>
                                    @endif
                                </a>
                            </li>
                        @endif

                        @if ($canManageRts && $hasRtsDirectReceiveIndex)
                            <li>
                                <a href="{{ route('rts.direct-receives.index') }}"
                                   class="mobile-sidebar-link {{ request()->routeIs('rts.direct-receives.*') ? 'active' : '' }}">
                                    <span class="icon">⚡</span><span>RTS Dadakan</span>
                                </a>
                            </li>
                        @endif
                    @endif

                    {{-- Sales (admin) --}}
                    @if ($isAdmin)
                        <div class="mobile-sidebar-section-label">Sales</div>

                        @if ($hasSalesShipmentsIndex)
                            <li>
                                <a href="{{ route('sales.shipments.index') }}"
                                   class="mobile-sidebar-link {{ request()->routeIs('sales.shipments.*') ? 'active' : '' }}">
                                    <span class="icon">🚚</span><span>Shipments</span>
                                </a>
                            </li>
                        @endif
                    @endif

                    {{-- Production (operating) --}}
                    @if ($isOperating)
                        <div class="mobile-sidebar-section-label">Production</div>

                        @if ($hasProdDashboard)
                            <li>
                                <a href="{{ route('production.dashboard') }}"
                                   class="mobile-sidebar-link {{ request()->routeIs('production.dashboard') ? 'active' : '' }}">
                                    <span class="icon">📊</span><span>Dashboard Produksi</span>
                                </a>
                            </li>
                        @endif

                        @if ($hasProdCuttingJobsIndex)
                            <li>
                                <a href="{{ route('production.cutting_jobs.index') }}"
                                   class="mobile-sidebar-link {{ request()->routeIs('production.cutting_jobs.*') ? 'active' : '' }}">
                                    <span class="icon">✂️</span><span>Daftar Cutting Jobs</span>
                                </a>
                            </li>
                        @endif

                        @if ($hasProdSewPickupsIndex)
                            <li>
                                <a href="{{ route('production.sewing.pickups.index') }}"
                                   class="mobile-sidebar-link {{ request()->routeIs('production.sewing.pickups.*') ? 'active' : '' }}">
                                    <span class="icon">🧵</span><span>Daftar Sewing Pickups</span>
                                </a>
                            </li>
                        @endif

                        @if ($hasProdSewReturnsIndex)
                            <li>
                                <a href="{{ route('production.sewing.returns.index') }}"
                                   class="mobile-sidebar-link {{ request()->routeIs('production.sewing.returns.*') ? 'active' : '' }}">
                                    <span class="icon">📥</span><span>Daftar Sewing Returns</span>
                                </a>
                            </li>
                        @endif

                        @if ($hasProdFinishingJobsIndex)
                            <li>
                                <a href="{{ route('production.finishing_jobs.index') }}"
                                   class="mobile-sidebar-link {{ request()->routeIs('production.finishing_jobs.*') ? 'active' : '' }}">
                                    <span class="icon">🧶</span><span>Daftar Finishing</span>
                                </a>
                            </li>
                        @endif

                        @if ($hasProdWipFinAdjIndex)
                            <li>
                                <a href="{{ route('production.wip-fin-adjustments.index') }}"
                                   class="mobile-sidebar-link {{ request()->routeIs('production.wip-fin-adjustments.*') ? 'active' : '' }}">
                                    <span class="icon">🧾</span><span>Koreksi WIP-FIN</span>
                                </a>
                            </li>
                        @endif

                        @if ($hasProdQcIndex)
                            <li>
                                <a href="{{ route('production.qc.index') }}"
                                   class="mobile-sidebar-link {{ request()->routeIs('production.qc.*') ? 'active' : '' }}">
                                    <span class="icon">✅</span><span>QC Produksi</span>
                                </a>
                            </li>
                        @endif

                        @if ($hasProdPackingIndex)
                            <li>
                                <a href="{{ route('production.packing_jobs.index') }}"
                                   class="mobile-sidebar-link {{ request()->routeIs('production.packing_jobs.*') ? 'active' : '' }}">
                                    <span class="icon">📦</span><span>Packing</span>
                                </a>
                            </li>
                        @endif

                        @if ($hasProdMovementsIndex)
                            <li>
                                <a href="{{ route('production.movements.index') }}"
                                   class="mobile-sidebar-link {{ request()->routeIs('production.movements.*') ? 'active' : '' }}">
                                    <span class="icon">🔄</span><span>Mutasi Produksi</span>
                                </a>
                            </li>
                        @endif

                        @if ($hasProdPriorityIndex)
                            <li>
                                <a href="{{ route('production.priority.index') }}"
                                   class="mobile-sidebar-link {{ request()->routeIs('production.priority.*') ? 'active' : '' }}">
                                    <span class="icon">🎯</span><span>Prioritas Produksi</span>
                                </a>
                            </li>
                        @endif

                        @if ($hasProdReportsIndex)
                            <li>
                                <a href="{{ route('production.reports.index') }}"
                                   class="mobile-sidebar-link {{ request()->routeIs('production.reports.*') ? 'active' : '' }}">
                                    <span class="icon">📈</span><span>Laporan Produksi</span>
                                </a>
                            </li>
                        @endif
                    @endif


                {{-- ============================
                    3) OWNER (FULL like desktop)
                ============================= --}}
                @elseif ($isOwner)

                    {{-- DASHBOARD --}}
                    @if ($hasDashboardRoute)
                        <li>
                            <a href="{{ route('dashboard') }}"
                               class="mobile-sidebar-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                                <span class="icon">🏠</span><span>Dashboard</span>
                            </a>
                        </li>
                    @endif

                    {{-- MASTER DATA --}}
                    <div class="mobile-sidebar-section-label">Master Data</div>
                    <li class="mb-1">
                        <button class="mobile-sidebar-link mobile-sidebar-toggle {{ $masterOpen ? 'is-open' : '' }}"
                                type="button"
                                data-bs-toggle="collapse"
                                data-bs-target="#navMasterMobile"
                                aria-expanded="{{ $masterOpen ? 'true' : 'false' }}"
                                aria-controls="navMasterMobile">
                            <span class="icon">🗂️</span><span>Master</span><span class="chevron">▸</span>
                        </button>

                        <div class="collapse {{ $masterOpen ? 'show' : '' }}" id="navMasterMobile">
                            @if ($hasMasterItemsIndex)
                                <a href="{{ route('master.items.index') }}"
                                   class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('master.items.*') ? 'active' : '' }}">
                                    <span class="icon">📦</span><span>Items</span>
                                </a>
                            @endif

                            @if ($router->has('master.item_categories.index'))
                                <a href="{{ route('master.item_categories.index') }}"
                                   class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('master.item_categories.*') ? 'active' : '' }}">
                                    <span class="icon">🗂️</span><span>Kategori Item</span>
                                </a>
                            @endif

                            @if ($hasMasterItemBomsIndex)
                                <a href="{{ route('master.item_boms.index') }}"
                                   class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('master.item_boms.*') ? 'active' : '' }}">
                                    <span class="icon">🧾</span><span>BOM SKU</span>
                                </a>
                            @endif

                            @if ($hasMasterSuppliersIndex)
                                <a href="{{ route('master.suppliers.index') }}"
                                   class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('master.suppliers.*') ? 'active' : '' }}">
                                    <span class="icon">🏷️</span><span>Suppliers</span>
                                </a>
                            @endif

                            @if ($hasMasterCustomersIndex)
                                <a href="{{ route('master.customers.index') }}"
                                   class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('master.customers.*') ? 'active' : '' }}">
                                    <span class="icon">👤</span><span>Customers</span>
                                </a>
                            @endif

                            @if ($hasMasterEmployeesIndex)
                                <a href="{{ route('master.employees.index') }}"
                                   class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('master.employees.*') ? 'active' : '' }}">
                                    <span class="icon">🧑‍🏭</span><span>Karyawan</span>
                                </a>
                            @endif
                        </div>
                    </li>

                    {{-- PURCHASING --}}
                    <div class="mobile-sidebar-section-label">Purchasing</div>

                    <li class="mb-1">
                        <button class="mobile-sidebar-link mobile-sidebar-toggle {{ $poOpen ? 'is-open' : '' }}"
                                type="button"
                                data-bs-toggle="collapse"
                                data-bs-target="#navPurchasingPoMobile"
                                aria-expanded="{{ $poOpen ? 'true' : 'false' }}"
                                aria-controls="navPurchasingPoMobile">
                            <span class="icon">🧾</span><span>Purchase Orders</span><span class="chevron">▸</span>
                        </button>
                        <div class="collapse {{ $poOpen ? 'show' : '' }}" id="navPurchasingPoMobile">
                            @if ($hasPoIndex)
                                <a href="{{ route('purchasing.purchase_orders.index') }}"
                                   class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('purchasing.purchase_orders.index') ? 'active' : '' }}">
                                    <span class="icon">≡</span><span>Daftar PO</span>
                                </a>
                            @endif
                            @if ($hasPoCreate)
                                <a href="{{ route('purchasing.purchase_orders.create') }}"
                                   class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('purchasing.purchase_orders.create') ? 'active' : '' }}">
                                    <span class="icon">＋</span><span>PO Baru</span>
                                </a>
                            @endif
                        </div>
                    </li>

                    <li class="mb-1">
                        <button class="mobile-sidebar-link mobile-sidebar-toggle {{ $grnOpen ? 'is-open' : '' }}"
                                type="button"
                                data-bs-toggle="collapse"
                                data-bs-target="#navPurchasingGrnMobile"
                                aria-expanded="{{ $grnOpen ? 'true' : 'false' }}"
                                aria-controls="navPurchasingGrnMobile">
                            <span class="icon">📥</span><span>Goods Receipts (GRN)</span><span class="chevron">▸</span>
                        </button>
                        <div class="collapse {{ $grnOpen ? 'show' : '' }}" id="navPurchasingGrnMobile">
                            @if ($hasGrnIndex)
                                <a href="{{ route('purchasing.purchase_receipts.index') }}"
                                   class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('purchasing.purchase_receipts.index') ? 'active' : '' }}">
                                    <span class="icon">≡</span><span>Daftar GRN</span>
                                </a>
                            @endif
                            @if ($hasGrnCreate)
                                <a href="{{ route('purchasing.purchase_receipts.create') }}"
                                   class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('purchasing.purchase_receipts.create') ? 'active' : '' }}">
                                    <span class="icon">＋</span><span>GRN Baru</span>
                                </a>
                            @endif
                        </div>
                    </li>

                    {{-- SALES & MARKETPLACE --}}
                    <div class="mobile-sidebar-section-label">Sales & Marketplace</div>

                    <li class="mb-1">
                        <button class="mobile-sidebar-link mobile-sidebar-toggle {{ $marketplaceOpen ? 'is-open' : '' }}"
                                type="button"
                                data-bs-toggle="collapse"
                                data-bs-target="#navMarketplaceMobile"
                                aria-expanded="{{ $marketplaceOpen ? 'true' : 'false' }}"
                                aria-controls="navMarketplaceMobile">
                            <span class="icon">🛒</span><span>Marketplace Orders</span><span class="chevron">▸</span>
                        </button>

                        <div class="collapse {{ $marketplaceOpen ? 'show' : '' }}" id="navMarketplaceMobile">
                            @if ($hasMarketplaceIndex)
                                <a href="{{ route('marketplace.orders.index') }}"
                                   class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('marketplace.orders.index') ? 'active' : '' }}">
                                    <span class="icon">≡</span><span>Daftar Order</span>
                                </a>
                            @endif
                            @if ($hasMarketplaceCreate)
                                <a href="{{ route('marketplace.orders.create') }}"
                                   class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('marketplace.orders.create') ? 'active' : '' }}">
                                    <span class="icon">＋</span><span>Order Manual</span>
                                </a>
                            @endif
                        </div>
                    </li>

                    <li class="mb-1">
                        <button class="mobile-sidebar-link mobile-sidebar-toggle {{ $salesOpen ? 'is-open' : '' }}"
                                type="button"
                                data-bs-toggle="collapse"
                                data-bs-target="#navSalesMobile"
                                aria-expanded="{{ $salesOpen ? 'true' : 'false' }}"
                                aria-controls="navSalesMobile">
                            <span class="icon">📑</span><span>Sales</span><span class="chevron">▸</span>
                        </button>

                        <div class="collapse {{ $salesOpen ? 'show' : '' }}" id="navSalesMobile">
                            @if ($hasSalesInvoicesIndex)
                                <a href="{{ route('sales.invoices.index') }}"
                                   class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('sales.invoices.index') ? 'active' : '' }}">
                                    <span class="icon">≡</span><span>Daftar Invoice</span>
                                </a>
                            @endif
                            @if ($hasSalesInvoicesCreate)
                                <a href="{{ route('sales.invoices.create') }}"
                                   class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('sales.invoices.create') ? 'active' : '' }}">
                                    <span class="icon">＋</span><span>Invoice Baru</span>
                                </a>
                            @endif

                            <div class="mobile-sidebar-section-label" style="margin-top:.55rem;">Shipments</div>

                            @if ($hasSalesShipmentsIndex)
                                <a href="{{ route('sales.shipments.index') }}"
                                   class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('sales.shipments.index') ? 'active' : '' }}">
                                    <span class="icon">🚚</span><span>Daftar Shipment</span>
                                </a>
                            @endif
                            @if ($hasSalesShipmentsCreate)
                                <a href="{{ route('sales.shipments.create') }}"
                                   class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('sales.shipments.create') ? 'active' : '' }}">
                                    <span class="icon">＋</span><span>Shipment Baru</span>
                                </a>
                            @endif

                            <div class="mobile-sidebar-section-label" style="margin-top:.55rem;">Shipment Returns</div>

                            @if ($hasSalesShipmentReturnsIndex)
                                <a href="{{ route('sales.shipment_returns.index') }}"
                                   class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('sales.shipment_returns.index') ? 'active' : '' }}">
                                    <span class="icon">🔁</span><span>Daftar Retur</span>
                                </a>
                            @endif
                            @if ($hasSalesShipmentReturnsCreate)
                                <a href="{{ route('sales.shipment_returns.create') }}"
                                   class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('sales.shipment_returns.create') ? 'active' : '' }}">
                                    <span class="icon">＋</span><span>Retur Shipment Baru</span>
                                </a>
                            @endif

                            <div class="mobile-sidebar-section-label" style="margin-top:.55rem;">Sales Reports</div>

                            @if ($hasSalesShipmentsReport)
                                <a href="{{ route('sales.shipments.report') }}"
                                   class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('sales.shipments.report') ? 'active' : '' }}">
                                    <span class="icon">📊</span><span>Laporan Pengiriman</span>
                                </a>
                            @endif

                            {{-- Optional reports --}}
                            @if ($hasSalesReportItemProfit)
                                <a href="{{ route('sales.reports.item_profit') }}"
                                   class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('sales.reports.item_profit') ? 'active' : '' }}">
                                    <span class="icon">📦</span><span>Item Profit</span>
                                </a>
                            @endif

                            @if ($hasSalesReportChannelProfit)
                                <a href="{{ route('sales.reports.channel_profit') }}"
                                   class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('sales.reports.channel_profit') ? 'active' : '' }}">
                                    <span class="icon">🏷️</span><span>Channel Profit</span>
                                </a>
                            @endif

                            @if ($hasSalesReportShipmentAnalytics)
                                <a href="{{ route('sales.reports.shipment_analytics') }}"
                                   class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('sales.reports.shipment_analytics') ? 'active' : '' }}">
                                    <span class="icon">📈</span><span>Shipment Analytics</span>
                                </a>
                            @endif
                        </div>
                    </li>

                    {{-- INVENTORY --}}
                    <div class="mobile-sidebar-section-label">Inventory</div>

                    <li class="mb-1">
                        <button class="mobile-sidebar-link mobile-sidebar-toggle {{ $invOpen ? 'is-open' : '' }}"
                                type="button"
                                data-bs-toggle="collapse"
                                data-bs-target="#navInventoryMobile"
                                aria-expanded="{{ $invOpen ? 'true' : 'false' }}"
                                aria-controls="navInventoryMobile">
                            <span class="icon">📦</span><span>Inventory</span><span class="chevron">▸</span>
                        </button>

                        <div class="collapse {{ $invOpen ? 'show' : '' }}" id="navInventoryMobile">
                            <div class="mobile-sidebar-section-label" style="margin-top:.55rem;">Stock</div>

                            @if ($hasInvStocksItems)
                                <a href="{{ route('inventory.stocks.items') }}"
                                   class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('inventory.stocks.items') ? 'active' : '' }}">
                                    <span class="icon">📦</span><span>Stok Barang</span>
                                </a>
                            @endif

                            @if ($hasInvStocksLots)
                                <a href="{{ route('inventory.stocks.lots') }}"
                                   class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('inventory.stocks.lots') ? 'active' : '' }}">
                                    <span class="icon">🎫</span><span>Stok per LOT</span>
                                </a>
                            @endif

                            @if ($hasInvStockCard)
                                <a href="{{ route('inventory.stock_card.index') }}"
                                   class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('inventory.stock_card.*') ? 'active' : '' }}">
                                    <span class="icon">📋</span><span>Kartu Stok</span>
                                </a>
                            @endif

                            @if ($hasInvTransfersIndex)
                                <a href="{{ route('inventory.transfers.index') }}"
                                   class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('inventory.transfers.index') ? 'active' : '' }}">
                                    <span class="icon">🔁</span><span>Daftar Transfer</span>
                                </a>
                            @endif

                            @if ($hasInvTransfersCreate)
                                <a href="{{ route('inventory.transfers.create') }}"
                                   class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('inventory.transfers.create') ? 'active' : '' }}">
                                    <span class="icon">➕</span><span>Transfer Baru</span>
                                </a>
                            @endif

                            @if ($hasInvAdjustmentsIndex)
                                <a href="{{ route('inventory.adjustments.index') }}"
                                   class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('inventory.adjustments.*') ? 'active' : '' }}">
                                    <span class="icon">⚖️</span><span>Inventory Adjustments</span>
                                </a>
                            @endif

                            @if ($hasInvWipAdjIndex)
                                <a href="{{ route('inventory.wip_adjustments.index') }}"
                                   class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('inventory.wip_adjustments.*') ? 'active' : '' }}">
                                    <span class="icon">🧾</span><span>Koreksi WIP</span>
                                </a>
                            @endif

                            @if ($hasInvWipCutReconcile)
                                <a href="{{ route('inventory.wip_cut_reconcile.index') }}"
                                   class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('inventory.wip_cut_reconcile.*') ? 'active' : '' }}">
                                    <span class="icon">🔍</span><span>Rekonsiliasi WIP-CUT</span>
                                </a>
                            @endif

                            <div class="mobile-sidebar-section-label" style="margin-top:.55rem;">Opname</div>

                            @if ($hasInvOpnamesIndex)
                                <a href="{{ route('inventory.stock_opnames.index') }}"
                                   class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('inventory.stock_opnames.*') ? 'active' : '' }}">
                                    <span class="icon">📊</span><span>Stock Opname</span>
                                </a>
                            @endif

                            @if ($hasInvOpnamesCreate)
                                <a href="{{ route('inventory.stock_opnames.create') }}"
                                   class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('inventory.stock_opnames.create') ? 'active' : '' }}">
                                    <span class="icon">＋</span><span>Stock Opname Baru</span>
                                </a>
                            @endif

                            <div class="mobile-sidebar-section-label" style="margin-top:.55rem;">External</div>

                            @if ($hasInvExternalIndex)
                                <a href="{{ route('inventory.external_transfers.index') }}"
                                   class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('inventory.external_transfers.index') ? 'active' : '' }}">
                                    <span class="icon">🚚</span><span>Daftar External TF</span>
                                </a>
                            @endif

                            @if ($hasInvExternalCreate)
                                <a href="{{ route('inventory.external_transfers.create') }}"
                                   class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('inventory.external_transfers.create') ? 'active' : '' }}">
                                    <span class="icon">➕</span><span>External TF Baru</span>
                                </a>
                            @endif
                        </div>
                    </li>

                    {{-- STOCK REQUESTS (OWNER) --}}
                    @if ($canViewRts && ($hasRtsStockReqIndex || $hasRtsDirectReceiveIndex))
                        <div class="mobile-sidebar-section-label">Stock Requests</div>

                        <li class="mb-1">
                            <button class="mobile-sidebar-link mobile-sidebar-toggle {{ $stockReqOpen ? 'is-open' : '' }}"
                                    type="button"
                                    data-bs-toggle="collapse"
                                    data-bs-target="#navStockRequestsMobile"
                                    aria-expanded="{{ $stockReqOpen ? 'true' : 'false' }}"
                                    aria-controls="navStockRequestsMobile">
                                <span class="icon">📤</span><span>Stock Requests</span><span class="chevron">▸</span>
                            </button>

                            <div class="collapse {{ $stockReqOpen ? 'show' : '' }}" id="navStockRequestsMobile">
                                @if ($hasRtsStockReqIndex)
                                    <a href="{{ route('rts.stock-requests.index') }}"
                                       class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('rts.stock-requests.*') ? 'active' : '' }}"
                                       @if($canManageRts && $hasRtsNeedReceive) title="{{ $rtsBadgeTitle }}" @endif
                                    >
                                        <span class="icon">🛒</span><span>Permintaan Stock (RTS)</span>
                                        @if($canManageRts && $hasRtsNeedReceive)
                                            <span class="ms-dot" aria-hidden="true"></span>
                                        @endif
                                    </a>
                                @endif

                                @if ($canManageRts && $hasRtsDirectReceiveIndex)
                                    <a href="{{ route('rts.direct-receives.index') }}"
                                       class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('rts.direct-receives.*') ? 'active' : '' }}">
                                        <span class="icon">⚡</span><span>RTS Dadakan</span>
                                    </a>
                                @endif
                            </div>
                        </li>
                    @endif

                    {{-- PRODUCTION --}}
                    <div class="mobile-sidebar-section-label">Production</div>

                    <li class="mb-1">
                        <button class="mobile-sidebar-link mobile-sidebar-toggle {{ $prodOpen ? 'is-open' : '' }}"
                                type="button"
                                data-bs-toggle="collapse"
                                data-bs-target="#navProductionMobile"
                                aria-expanded="{{ $prodOpen ? 'true' : 'false' }}"
                                aria-controls="navProductionMobile">
                            <span class="icon">🏭</span><span>Production</span><span class="chevron">▸</span>
                        </button>

                        <div class="collapse {{ $prodOpen ? 'show' : '' }}" id="navProductionMobile">
                            <div class="mobile-sidebar-section-label" style="margin-top:.55rem;">Monitoring</div>

                            @if ($hasProdDashboard)
                                <a href="{{ route('production.dashboard') }}"
                                   class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('production.dashboard') ? 'active' : '' }}">
                                    <span class="icon">📊</span><span>Dashboard Produksi</span>
                                </a>
                            @endif

                            @if ($hasProdPriorityIndex)
                                <a href="{{ route('production.priority.index') }}"
                                   class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('production.priority.*') ? 'active' : '' }}">
                                    <span class="icon">🎯</span><span>Prioritas Produksi</span>
                                </a>
                            @endif

                            @if ($hasProdReportsIndex)
                                <a href="{{ route('production.reports.index') }}"
                                   class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('production.reports.*') ? 'active' : '' }}">
                                    <span class="icon">📈</span><span>Laporan Produksi</span>
                                </a>
                            @endif

                            <div class="mobile-sidebar-section-label" style="margin-top:.55rem;">Alur Produksi</div>

                            @if ($hasProdCuttingJobsIndex)
                                <a href="{{ route('production.cutting_jobs.index') }}"
                                   class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('production.cutting_jobs.*') ? 'active' : '' }}">
                                    <span class="icon">✂️</span><span>Cutting Jobs</span>
                                </a>
                            @endif

                            @if ($hasProdSewPickupsIndex)
                                <a href="{{ route('production.sewing.pickups.index') }}"
                                   class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('production.sewing.pickups.*') ? 'active' : '' }}">
                                    <span class="icon">🧵</span><span>Sewing Pickups</span>
                                </a>
                            @endif

                            @if ($hasProdSewReturnsIndex)
                                <a href="{{ route('production.sewing.returns.index') }}"
                                   class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('production.sewing.returns.*') ? 'active' : '' }}">
                                    <span class="icon">📥</span><span>Sewing Returns</span>
                                </a>
                            @endif

                            @if ($hasProdQcIndex)
                                <a href="{{ route('production.qc.index') }}"
                                   class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('production.qc.*') ? 'active' : '' }}">
                                    <span class="icon">✅</span><span>QC Produksi</span>
                                </a>
                            @endif

                            @if ($hasProdFinishingJobsIndex)
                                <a href="{{ route('production.finishing_jobs.index') }}"
                                   class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('production.finishing_jobs.*') ? 'active' : '' }}">
                                    <span class="icon">🧶</span><span>Finishing Jobs</span>
                                </a>
                            @endif

                            @if ($hasProdPackingIndex)
                                <a href="{{ route('production.packing_jobs.index') }}"
                                   class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('production.packing_jobs.*') ? 'active' : '' }}">
                                    <span class="icon">📦</span><span>Packing</span>
                                </a>
                            @endif

                            <div class="mobile-sidebar-section-label" style="margin-top:.55rem;">Stok & Koreksi</div>

                            @if ($hasProdMovementsIndex)
                                <a href="{{ route('production.movements.index') }}"
                                   class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('production.movements.*') ? 'active' : '' }}">
                                    <span class="icon">🔄</span><span>Mutasi Produksi</span>
                                </a>
                            @endif

                            @if ($hasProdWipFinAdjIndex)
                                <a href="{{ route('production.wip-fin-adjustments.index') }}"
                                   class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('production.wip-fin-adjustments.*') ? 'active' : '' }}">
                                    <span class="icon">🧾</span><span>Koreksi WIP-FIN</span>
                                </a>
                            @endif
                        </div>
                    </li>

                    {{-- FINANCE --}}
                    <div class="mobile-sidebar-section-label">Finance</div>

                    {{-- Accounting --}}
                    <li class="mb-1">
                        <button class="mobile-sidebar-link mobile-sidebar-toggle {{ $accountingOpen ? 'is-open' : '' }}"
                                type="button"
                                data-bs-toggle="collapse"
                                data-bs-target="#navAccountingMobile"
                                aria-expanded="{{ $accountingOpen ? 'true' : 'false' }}"
                                aria-controls="navAccountingMobile">
                            <span class="icon">🧾</span><span>Accounting</span><span class="chevron">▸</span>
                        </button>

                        <div class="collapse {{ $accountingOpen ? 'show' : '' }}" id="navAccountingMobile">
                            @if ($hasOpeningBalancesIndex)
                                <a href="{{ route('accounting.opening-balances.index') }}"
                                   class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('accounting.opening-balances.*') ? 'active' : '' }}">
                                    <span class="icon">🟢</span><span>Opening Balances</span>
                                </a>
                            @endif

                            @if ($hasCashExpensesIndex)
                                <a href="{{ route('accounting.cash-expenses.index') }}"
                                   class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('accounting.cash-expenses.*') ? 'active' : '' }}">
                                    <span class="icon">💸</span><span>Cash Expenses</span>
                                </a>
                            @endif

                            @if ($hasJournalsIndex)
                                <a href="{{ route('accounting.journals.index') }}"
                                   class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('accounting.journals.*') ? 'active' : '' }}">
                                    <span class="icon">📓</span><span>Journals</span>
                                </a>
                            @endif

                            @if ($hasAccountsIndex)
                                <a href="{{ route('accounting.accounts.index') }}"
                                   class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('accounting.accounts.*') ? 'active' : '' }}">
                                    <span class="icon">🗂️</span><span>Accounts (COA)</span>
                                </a>
                            @endif
                        </div>
                    </li>

                    {{-- Payroll --}}
                    <li class="mb-1">
                        <button class="mobile-sidebar-link mobile-sidebar-toggle {{ $payrollOpen ? 'is-open' : '' }}"
                                type="button"
                                data-bs-toggle="collapse"
                                data-bs-target="#navPayrollMobile"
                                aria-expanded="{{ $payrollOpen ? 'true' : 'false' }}"
                                aria-controls="navPayrollMobile">
                            <span class="icon">💰</span><span>Payroll</span><span class="chevron">▸</span>
                        </button>

                        <div class="collapse {{ $payrollOpen ? 'show' : '' }}" id="navPayrollMobile">
                            @if ($hasPayrollDashboard)
                                <a href="{{ route('payroll.dashboard') }}"
                                   class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('payroll.dashboard*') ? 'active' : '' }}">
                                    <span class="icon">📈</span><span>Dashboard</span>
                                </a>
                            @endif

                            @if ($hasPieceworkIndex)
                                <a href="{{ route('payroll.piecework.index', ['module' => 'cutting']) }}"
                                   class="mobile-sidebar-link mobile-sidebar-link-sub {{ $pieceworkCuttingActive ? 'active' : '' }}">
                                    <span class="icon">✂️</span><span>Cutting Payroll</span>
                                </a>

                                <a href="{{ route('payroll.piecework.index', ['module' => 'sewing']) }}"
                                   class="mobile-sidebar-link mobile-sidebar-link-sub {{ $pieceworkSewingActive ? 'active' : '' }}">
                                    <span class="icon">🧵</span><span>Sewing Payroll</span>
                                </a>
                            @endif

                            @if ($hasPieceRatesIndex)
                                <a href="{{ route('payroll.piece_rates.index') }}"
                                   class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('payroll.piece_rates.*') ? 'active' : '' }}">
                                    <span class="icon">📑</span><span>Piece Rates</span>
                                </a>
                            @endif

                            @if ($hasPayrollReportsOperators)
                                <a href="{{ route('payroll.reports.operators') }}"
                                   class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('payroll.reports.operators') ? 'active' : '' }}">
                                    <span class="icon">📊</span><span>Operator Summary</span>
                                </a>
                            @endif

                            @if ($hasPayrollReportsOperatorSlips)
                                <a href="{{ route('payroll.reports.operator_slips') }}"
                                   class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('payroll.reports.operator_slips') ? 'active' : '' }}">
                                    <span class="icon">🧾</span><span>Operator Slips</span>
                                </a>
                            @endif
                        </div>
                    </li>

                    {{-- Costing --}}
                    <li class="mb-1">
                        <button class="mobile-sidebar-link mobile-sidebar-toggle {{ $costingOpen ? 'is-open' : '' }}"
                                type="button"
                                data-bs-toggle="collapse"
                                data-bs-target="#navCostingMobile"
                                aria-expanded="{{ $costingOpen ? 'true' : 'false' }}"
                                aria-controls="navCostingMobile">
                            <span class="icon">📉</span><span>Costing &amp; HPP</span><span class="chevron">▸</span>
                        </button>

                        <div class="collapse {{ $costingOpen ? 'show' : '' }}" id="navCostingMobile">
                            @if ($hasHppIndex)
                                <a href="{{ route('costing.hpp.index') }}"
                                   class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('costing.hpp.*') ? 'active' : '' }}">
                                    <span class="icon">⚙️</span><span>HPP Finished Goods</span>
                                </a>
                            @endif

                            @if ($hasProdCostPeriodsIndex)
                                <a href="{{ route('costing.production_cost_periods.index') }}"
                                   class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('costing.production_cost_periods.*') ? 'active' : '' }}">
                                    <span class="icon">📆</span><span>Production Cost Periods</span>
                                </a>
                            @endif
                        </div>
                    </li>

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

    // Auto-close saat klik link menu
    links.forEach(link => link.addEventListener('click', closeSidebar));

    document.addEventListener('keyup', function(e) {
        if (e.key === 'Escape') closeSidebar();
    });
});
</script>
@endpush
