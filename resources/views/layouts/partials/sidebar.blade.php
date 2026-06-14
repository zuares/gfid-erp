{{-- resources/views/layouts/partials/sidebar.blade.php --}}
@php
    use Illuminate\Support\Facades\Cache;
    use Illuminate\Support\Facades\DB;

    $user = auth()->user();
    if ($user && $user::moduleAccessTableExists()) {
        $user->loadMissing('moduleAccesses');
    }
    $role = strtolower((string) ($user->role ?? ''));

    $isDev = $user && $user->isDeveloper();
    $isOwner = $role === 'owner' || $isDev;  // developer = akses owner (semua menu)
    $isOperating = $role === 'operating';
    $isAdmin = $role === 'admin' && !$isDev; // developer TIDAK masuk branch admin agar masuk branch owner

    // ✅ capability flags
    $canViewRts = $isOwner || $isAdmin || $isOperating;
    $canManageRts = $isOwner || $isAdmin;

    $router = app('router');

    // =========================================================
    // ROUTE GUARDS (biar tidak error kalau route belum ada)
    // =========================================================
    $hasDashboardRoute = $router->has('dashboard');
    $hasOwnerAccessControl = $router->has('owner.access-control.index');

    // Inventory
    $hasInvStocksItems = $router->has('inventory.stocks.items');
    $hasInvStocksLots = $router->has('inventory.stocks.lots');
    $hasInvStockCard = $router->has('inventory.stock_card.index');
    $hasInvTransfersIndex = $router->has('inventory.transfers.index');
    $hasInvTransfersCreate = $router->has('inventory.transfers.create');
    $hasInvAdjustmentsIndex = $router->has('inventory.adjustments.index');

    $hasInvOpnamesIndex = $router->has('inventory.stock_opnames.index');
    $hasInvOpnamesCreate = $router->has('inventory.stock_opnames.create');

    $hasInvExternalIndex = $router->has('inventory.external_transfers.index');
    $hasInvExternalCreate = $router->has('inventory.external_transfers.create');

    $hasInvWipAdjIndex = $router->has('inventory.wip_adjustments.index');
    $hasInvWipCutReconcile = $router->has('inventory.wip_cut_reconcile.index');

    $hasInvIntelligence = $router->has('inventory.intelligence');

    // RTS
    $hasRtsStockReqIndex = $router->has('rts.stock-requests.index');
    $hasRtsDirectReceiveIndex = $router->has('rts.direct-receives.index');

    // Sales
    $hasSalesShipmentsIndex = $router->has('sales.shipments.index');
    $hasSalesShipmentsCreate = $router->has('sales.shipments.create');
    $hasSalesShipmentReturnsIndex = $router->has('sales.shipment_returns.index');

    $hasSalesInvoicesIndex = $router->has('sales.invoices.index');
    $hasSalesInvoicesCreate = $router->has('sales.invoices.create');

    $hasSalesShipmentsReport = $router->has('sales.shipments.report');

    $hasSalesReportPerformance = $router->has('sales.reports.sales_performance.index');
    $hasSalesReportItemProfit = $router->has('sales.reports.item_profit');
    $hasSalesReportChannelProfit = $router->has('sales.reports.channel_profit');
    $hasSalesReportShipmentAnalytics = $router->has('sales.reports.shipment_analytics');

    // Master
    $hasMasterItemsIndex = $router->has('master.items.index');
    $hasMasterItemCategoriesIndex = $router->has('master.item_categories.index');
    $hasMasterCustomersIndex = $router->has('master.customers.index');
    $hasMasterSuppliersIndex = $router->has('master.suppliers.index');
    $hasMasterItemBomsIndex = $router->has('master.item_boms.index');
    $hasMasterEmployeesIndex = $router->has('master.employees.index');

    // Purchasing
    $hasPoIndex   = $router->has('purchasing.purchase_orders.index');
    $hasPoCreate  = $router->has('purchasing.purchase_orders.create');
    $hasGrnIndex  = $router->has('purchasing.purchase_receipts.index');
    $hasGrnCreate = $router->has('purchasing.purchase_receipts.create');

    // =========================================================
    // Marketplace
    $hasMarketplaceToko        = $router->has('marketplace.toko');
    $hasMarketplaceOrders      = $router->has('marketplace.orders');
    $hasMarketplaceFulfillment = $router->has('marketplace.fulfillment');
    $hasMarketplacePicking     = $router->has('marketplace.picking');
    $hasMarketplaceSkuMapping  = $router->has('marketplace.sku-mapping');
    $hasMarketplaceSync        = $router->has('marketplace.sync');
    $hasMarketplaceSettlement  = $router->has('marketplace.settlement');
    $hasMarketplaceProfit      = $router->has('marketplace.profit');
    $hasMarketplaceAds         = $router->has('marketplace.ads');
    $hasMarketplaceAnalytics   = $router->has('marketplace.analytics');
    $hasMarketplaceIssues      = $router->has('marketplace.issues');

    // Marketplace (UPDATED sesuai routes terbaru yang kamu kasih)
    // =========================================================
    // Marketplace Orders
    $hasMarketplaceIndex = $router->has('marketplace.orders');
    $hasMarketplaceCreate = $router->has('marketplace.orders.create');
    $hasMarketplaceShow = $router->has('marketplace.orders.show');

    // Marketplace Reports
    $hasMarketplaceSalesReport = $router->has('marketplace.reports.sales');
    $hasMarketplaceSalesExport = $router->has('marketplace.reports.sales.export');

    // Marketplace Reconcile
    $hasMarketplaceReconcileQueue = $router->has('marketplace.reconcile.queue');
    $hasMarketplaceReconcileQueueBulk = $router->has('marketplace.reconcile.queue.bulk'); // POST (guard only)
    $hasMarketplaceReconcilePreview = $router->has('marketplace.reconcile.preview'); // POST
    $hasMarketplaceReconcileCommit = $router->has('marketplace.reconcile.commit'); // POST

    $hasMarketplaceReconciliationsResolve = $router->has('marketplace.reconciliations.resolve'); // POST
    $hasMarketplaceReconciliationsDiff = $router->has('marketplace.reconciliations.diff'); // GET

    $hasMarketplaceReconcileItemsIndex = $router->has('marketplace.reconcile.items'); // GET
    $hasMarketplaceReconcileItemsApply = $router->has('marketplace.reconcile.items.apply'); // POST
    $hasMarketplaceReconcileItemsPackets = $router->has('marketplace.reconcile.items.packets'); // GET

    // =========================================================
    // IMPORTS (tetap kamu pakai sebelumnya)
    // =========================================================

    // imports.marketplace.*
    $hasImportsMarketplaceIndex  = $router->has('imports.marketplace.index');
    $hasImportsMarketplaceCreate = $router->has('imports.marketplace.create');
    $hasImportsMarketplaceExport = $router->has('imports.marketplace.export');
    $hasImportsMarketplaceDraft  = $router->has('imports.marketplace.draft');
    $hasImportsMarketplacePreview = $router->has('imports.marketplace.preview');
    $hasImportsMarketplaceCommit  = $router->has('imports.marketplace.commit');
    $hasImportsMarketplaceCancel  = $router->has('imports.marketplace.cancel');
    $hasImportsMarketplaceShow    = $router->has('imports.marketplace.show');

    // imports.marketplace_income.*
    $hasImportsMarketplaceIncomeIndex  = $router->has('imports.marketplace_income.index');
    $hasImportsMarketplaceIncomeCreate = $router->has('imports.marketplace_income.create');
    $hasImportsMarketplaceIncomeDraft  = $router->has('imports.marketplace_income.draft');
    $hasImportsMarketplaceIncomePreview = $router->has('imports.marketplace_income.preview');
    $hasImportsMarketplaceIncomeCommit  = $router->has('imports.marketplace_income.commit');
    $hasImportsMarketplaceIncomeCancel  = $router->has('imports.marketplace_income.cancel');
    $hasImportsMarketplaceIncomeShow    = $router->has('imports.marketplace_income.show');
    $hasImportsMarketplaceIncomeOrderShow = $router->has('imports.marketplace_income.order.show');
    $hasImportsMarketplaceIncomeApply = $router->has('imports.marketplace_income.apply');

    // Production
    $hasProdCuttingJobsIndex = $router->has('production.cutting_jobs.index');
    $hasProdSewPickupsIndex = $router->has('production.sewing.pickups.index');
    $hasProdSewReturnsIndex = $router->has('production.sewing.returns.index');
    $hasProdSewRejectReturnsIndex = $router->has('production.sewing.reject_returns.index');
    $hasProdFinishingJobsIndex = $router->has('production.finishing_jobs.index');
    $hasProdFinishingRepairsIndex = $router->has('production.finishing_repairs.index');
    $hasProdQcIndex = $router->has('production.qc.index');
    $hasProdPackingIndex = $router->has('production.packing_jobs.index');
    $hasProdPriorityIndex = $router->has('production.priority.index');
    $hasProdReportsIndex = $router->has('production.reports.index');

    $hasProdDashboard = $router->has('production.dashboard');

    // Accounting
    $hasAccountsIndex = $router->has('accounting.accounts.index');
    $hasCashExpensesIndex = $router->has('accounting.cash-expenses.index');
    $hasCashBasisReportIndex = $router->has('accounting.cash-basis-report.index');
    $hasCashReceiptsIndex = $router->has('accounting.cash-receipts.index');
    $hasJournalsIndex = $router->has('accounting.journals.index');
    $hasOpeningBalancesIndex = $router->has('accounting.opening-balances.index');
    $hasOpeningBalancesBatchIndex = $router->has('accounting.opening-balances-batch.index');

    // Payroll (owner)
    $hasPayrollDashboard = $router->has('payroll.dashboard');
    $hasPieceworkIndex = $router->has('payroll.piecework.index');
    $hasPieceRatesIndex = $router->has('payroll.piece_rates.index');
    $hasPayrollReportsOperators = $router->has('payroll.reports.operators');

    // Costing (owner)
    $hasHppIndex = $router->has('costing.hpp.index');
    $hasProdCostPeriodsIndex = $router->has('costing.production_cost_periods.index');

    $canModule = fn (string $module) => $user ? $user->canAccessModule($module) : false;
    $hasDashboardRoute = $hasDashboardRoute && $canModule('dashboard');

    if (!$canModule('inventory')) {
        $hasInvStocksItems = $hasInvStocksLots = $hasInvStockCard = false;
        $hasInvTransfersIndex = $hasInvTransfersCreate = $hasInvAdjustmentsIndex = false;
        $hasInvOpnamesIndex = $hasInvOpnamesCreate = false;
        $hasInvExternalIndex = $hasInvExternalCreate = false;
        $hasInvWipAdjIndex = $hasInvWipCutReconcile = $hasInvIntelligence = false;
        $hasRtsStockReqIndex = $hasRtsDirectReceiveIndex = false;
    }

    if (!$canModule('sales')) {
        $hasSalesShipmentsIndex = $hasSalesShipmentsCreate = $hasSalesShipmentReturnsIndex = false;
        $hasSalesInvoicesIndex = $hasSalesInvoicesCreate = $hasSalesShipmentsReport = false;
        $hasSalesReportPerformance = $hasSalesReportItemProfit = false;
        $hasSalesReportChannelProfit = $hasSalesReportShipmentAnalytics = false;
    }

    if (!$canModule('master')) {
        $hasMasterItemsIndex = $hasMasterItemCategoriesIndex = $hasMasterCustomersIndex = false;
        $hasMasterSuppliersIndex = $hasMasterItemBomsIndex = $hasMasterEmployeesIndex = false;
    }

    if (!$canModule('purchasing')) {
        $hasPoIndex = $hasPoCreate = $hasGrnIndex = $hasGrnCreate = false;
    }

    if (!$canModule('marketplace')) {
        $hasMarketplaceToko = $hasMarketplaceOrders = $hasMarketplaceFulfillment = false;
        $hasMarketplacePicking = $hasMarketplaceSkuMapping = $hasMarketplaceSync = false;
        $hasMarketplaceSettlement = $hasMarketplaceProfit = $hasMarketplaceAds = false;
        $hasMarketplaceAnalytics = $hasMarketplaceIssues = false;
        $hasMarketplaceIndex = $hasMarketplaceCreate = $hasMarketplaceShow = false;
        $hasMarketplaceSalesReport = $hasMarketplaceSalesExport = false;
        $hasMarketplaceReconcileQueue = $hasMarketplaceReconcileQueueBulk = false;
        $hasMarketplaceReconcilePreview = $hasMarketplaceReconcileCommit = false;
        $hasMarketplaceReconciliationsResolve = $hasMarketplaceReconciliationsDiff = false;
        $hasMarketplaceReconcileItemsIndex = $hasMarketplaceReconcileItemsApply = false;
        $hasMarketplaceReconcileItemsPackets = false;
    }

    if (!$canModule('imports')) {
        $hasImportsMarketplaceIndex = $hasImportsMarketplaceCreate = $hasImportsMarketplaceExport = false;
        $hasImportsMarketplaceDraft = $hasImportsMarketplacePreview = $hasImportsMarketplaceCommit = false;
        $hasImportsMarketplaceCancel = $hasImportsMarketplaceShow = false;
        $hasImportsMarketplaceIncomeIndex = $hasImportsMarketplaceIncomeCreate = false;
        $hasImportsMarketplaceIncomeDraft = $hasImportsMarketplaceIncomePreview = false;
        $hasImportsMarketplaceIncomeCommit = $hasImportsMarketplaceIncomeCancel = false;
        $hasImportsMarketplaceIncomeShow = $hasImportsMarketplaceIncomeOrderShow = false;
        $hasImportsMarketplaceIncomeApply = false;
    }

    if (!$canModule('production')) {
        $hasProdCuttingJobsIndex = $hasProdSewPickupsIndex = $hasProdSewReturnsIndex = false;
        $hasProdSewRejectReturnsIndex = false;
        $hasProdFinishingJobsIndex = $hasProdFinishingRepairsIndex = $hasProdQcIndex = false;
        $hasProdPackingIndex = $hasProdPriorityIndex = false;
        $hasProdReportsIndex = $hasProdDashboard = false;
    }

    if (!$canModule('accounting')) {
        $hasAccountsIndex = $hasCashExpensesIndex = $hasCashBasisReportIndex = false;
        $hasCashReceiptsIndex = $hasJournalsIndex = $hasOpeningBalancesIndex = false;
        $hasOpeningBalancesBatchIndex = false;
    }

    if (!$canModule('payroll')) {
        $hasPayrollDashboard = $hasPieceworkIndex = $hasPieceRatesIndex = false;
        $hasPayrollReportsOperators = false;
    }

    if (!$canModule('costing')) {
        $hasHppIndex = $hasProdCostPeriodsIndex = false;
    }

    // =========================================================
    // OPEN STATES (untuk collapse)
    // =========================================================
    $open = fn ($pattern) => request()->routeIs($pattern);

    $openMaster = $open('master.*');
    $openPurchasing = $open('purchasing.*');
    $openMarketplaceOrders = $open('marketplace.orders.*');

    $openMarketplaceTools =
        $open('marketplace.toko') ||
        $open('marketplace.orders') ||
        $open('marketplace.orders.*') ||
        $open('marketplace.fulfillment') ||
        $open('marketplace.fulfillment.*') ||
        $open('marketplace.picking') ||
        $open('marketplace.sku-mapping') ||
        $open('marketplace.sync') ||
        $open('marketplace.settlement') ||
        $open('marketplace.profit') ||
        $open('marketplace.ads') ||
        $open('marketplace.analytics') ||
        $open('marketplace.issues') ||
        $open('marketplace.reports.*') ||
        $open('marketplace.reconcile.*') ||
        $open('marketplace.reconciliations.*');

    // ✅ imports group
    $openImports =
        $open('imports.marketplace.*') ||
        $open('imports.marketplace_income.*');

    $openSales =
        $open('sales.invoices.*') ||
        $open('sales.shipments.*') ||
        $open('sales.shipment_returns.*') ||
        $open('sales.reports.*') ||
        $open('sales.shipments.report');

    $openInventory =
        $open('inventory.intelligence') ||
        $open('inventory.intelligence.*') ||
        $open('inventory.stocks.*') ||
        $open('inventory.stock_opnames.*') ||
        $open('inventory.stock_card.*') ||
        $open('inventory.transfers.*') ||
        $open('inventory.external_transfers.*') ||
        $open('inventory.adjustments.*') ||
        $open('inventory.wip_adjustments.*');

    $openStockRequests =
        $open('rts.stock-requests.*') ||
        $open('rts.direct-receives.*');

    $openProduction =
        $open('production.cutting_jobs.*') ||
        $open('production.sewing.*') ||
        $open('production.finishing_jobs.*') ||
        $open('production.finishing_repairs.*') ||
        $open('production.qc.*') ||
        $open('production.dashboard') ||
        $open('production.packing_jobs.*') ||
        $open('production.priority.*') ||
        $open('production.reports.*');

    $openAccounting =
        $open('accounting.cash-basis-report.*') ||
        $open('accounting.cash-expenses.*') ||
        $open('accounting.cash-receipts.*') ||
        $open('accounting.opening-balances.*') ||
        $open('accounting.opening-balances-batch.*') ||
        $open('accounting.journals.*') ||
        $open('accounting.accounts.*');

    $openPayroll =
        $open('payroll.dashboard*') ||
        $open('payroll.piecework.*') ||
        $open('payroll.piece_rates.*') ||
        $open('payroll.reports.*');

    $openCosting =
        $open('costing.hpp.*') ||
        $open('costing.production_cost_periods.*');

    // =========================================================
    // BADGE COUNTERS (dot-only, cached)
    // =========================================================
    $rtsNeedReceiveQty = 0.0;

    $fmtQty = function ($n) {
        $n = (float) $n;
        return rtrim(rtrim(number_format($n, 2, '.', ''), '0'), '.');
    };

    if ($user && $hasRtsStockReqIndex) {
        $cacheKey = 'sidebar_badges_u' . (int) $user->id . '_r' . $role;

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

    // Payroll active helpers
    $activeModule = request()->route()?->parameter('module');
    $pieceworkCuttingActive = request()->routeIs('payroll.piecework.*') && $activeModule === 'cutting';
    $pieceworkSewingActive = request()->routeIs('payroll.piecework.*') && $activeModule === 'sewing';

    // =========================================================
    // QUICK HELPERS
    // =========================================================
    $subhead = function (string $text) {
        echo '<div class="sidebar-subhead">' . e($text) . '</div>';
    };

    $canShow = fn (...$flags) => collect($flags)->contains(true);
@endphp

<style>
    @media (min-width: 992px) {
        .sidebar-modern {
            position: fixed;
            top: 0;
            left: 0;
            width: 260px;
            height: 100vh;
            padding: 1rem 1rem 1.6rem;
            display: flex;
            flex-direction: column;
            gap: 1rem;
            background: color-mix(in srgb, var(--card) 92%, var(--bg) 8%);
            backdrop-filter: blur(14px);
            border-right: 1px solid rgba(148, 163, 184, .35);
            box-shadow: 8px 0 24px rgba(15,23,42,.05), 2px 0 8px rgba(15,23,42,.03);
            border-radius: 0 22px 22px 0;
            z-index: 1030;
            overflow-y: auto;
            scrollbar-width: thin;
            scrollbar-color: rgba(148, 163, 184, .4) transparent;
        }

        .app-main {
            margin-left: 260px;
        }
    }

    .sidebar-modern { display: none; }
    @media (min-width: 992px) { .sidebar-modern { display: flex; } }

    .sidebar-modern::-webkit-scrollbar { width: 6px; }
    .sidebar-modern::-webkit-scrollbar-thumb { background: rgba(148, 163, 184, .35); border-radius: 20px; }

    .sidebar-brand {
        display:flex;
        align-items:center;
        justify-content:space-between;
        gap:.75rem;
        font-size: 1.15rem;
        font-weight: 800;
        padding: .7rem .35rem .9rem;
        color: var(--text);
        letter-spacing: -.03em;
    }

    .sidebar-brand .role-pill{
        font-size:.7rem;
        padding:.18rem .55rem;
        border-radius:999px;
        background: color-mix(in srgb, var(--accent-soft) 18%, var(--card) 82%);
        box-shadow: inset 0 0 0 1px var(--line);
        color: var(--muted);
        text-transform: uppercase;
        letter-spacing:.12em;
    }

    .sidebar-nav { list-style: none; margin: 0; padding: 0; }

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
        position: relative;
    }

    .sidebar-link .icon { width: 22px; font-size: 1.05rem; text-align: center; }

    .sidebar-link:hover {
        background: color-mix(in srgb, var(--accent-soft) 18%, var(--card) 82%);
        box-shadow: inset 0 0 0 1px var(--line);
        transform: translateX(1px);
    }

    .sidebar-link.active {
        background: transparent;
        font-weight: 700;
        box-shadow: inset 2px 0 0 var(--accent);
        color: var(--accent);
    }

    .sidebar-toggle { cursor: pointer; border: 0; width: 100%; background: transparent; text-align: left; }
    .sidebar-toggle .chevron { margin-left: auto; font-size: .8rem; opacity: .8; transition: transform .18s ease; }
    .sidebar-toggle[aria-expanded="true"] .chevron { transform: rotate(90deg); }
    .sidebar-toggle.is-open { background: transparent; box-shadow: none; color: var(--accent); font-weight: 700; }
    .sidebar-toggle.is-open .icon { color: var(--accent); }

    .sidebar-link-sub {
        position: relative;
        font-size: .86rem;
        padding: .42rem .9rem .42rem 2.3rem;
        opacity: .95;
        border-radius: 10px;
        display: flex;
        align-items: center;
        gap: .55rem;
        color: var(--text);
        text-decoration: none;
        transition: background .18s ease, box-shadow .18s ease, transform .12s ease, color .18s ease;
    }

    .sidebar-link-sub .icon { width: 18px; font-size: .9rem; text-align: center; }

    .sidebar-link-sub:hover {
        background: color-mix(in srgb, var(--accent-soft) 16%, var(--card) 84%);
        box-shadow: inset 0 0 0 1px var(--line);
    }

    .sidebar-link-sub.active {
        background: transparent;
        font-weight: 700;
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

    .sidebar-subhead{
        padding: .35rem .9rem .15rem 1.05rem;
        font-size:.68rem;
        letter-spacing:.12em;
        text-transform:uppercase;
        color: var(--muted);
        opacity:.9;
    }

    .simple-group { margin-top: .4rem; }

    .sidebar-divider{
        height:1px;
        margin:.65rem .35rem;
        background: color-mix(in srgb, var(--line) 70%, transparent 30%);
        border-radius:999px;
        opacity:.8;
    }
</style>

<aside class="sidebar-modern flex-column">
    <div class="sidebar-brand">
        <div>GFID</div>
        @if($user)
            <div class="role-pill">{{ $role ?: 'user' }}</div>
        @endif
    </div>

    <ul class="sidebar-nav">

        {{-- DASHBOARD --}}
        @if ($hasDashboardRoute)
            <li>
                <x-sidebar.simple-link href="{{ route('dashboard') }}" icon="🏠" :active="request()->routeIs('dashboard')">
                    Dashboard
                </x-sidebar.simple-link>
            </li>
            @if ($isOwner && $hasOwnerAccessControl)
                <li>
                    <x-sidebar.simple-link href="{{ route('owner.access-control.index') }}" icon="🔐" :active="request()->routeIs('owner.access-control.*')">
                        Akses Login
                    </x-sidebar.simple-link>
                </li>
            @endif
            <div class="sidebar-divider"></div>
        @endif

        {{-- GUEST --}}
        @if (!$user)
            {{-- no menu --}}

        @elseif ($isAdmin || $isOperating)
            {{-- =========================================================
                ADMIN / OPERATING (simple, non-collapsible)
            ========================================================= --}}

            <x-sidebar.label text="Operations" />
            <li class="simple-group">
                @if ($hasInvIntelligence)
                    <x-sidebar.simple-link href="{{ route('inventory.intelligence') }}" icon="🧠"
                        :active="request()->routeIs('inventory.intelligence') || request()->routeIs('inventory.intelligence.*')">
                        Inventory Intelligence
                    </x-sidebar.simple-link>
                @endif

                @if ($hasInvStocksItems)
                    <x-sidebar.simple-link href="{{ route('inventory.stocks.items') }}" icon="📦"
                        :active="request()->routeIs('inventory.stocks.items')">
                        Stok Barang
                    </x-sidebar.simple-link>
                @endif

                @if ($hasInvOpnamesIndex)
                    <x-sidebar.simple-link href="{{ route('inventory.stock_opnames.index') }}" icon="📊"
                        :active="request()->routeIs('inventory.stock_opnames.*')">
                        Stock Opname
                    </x-sidebar.simple-link>
                @endif

                @if ($hasInvWipAdjIndex)
                    <x-sidebar.simple-link href="{{ route('inventory.wip_adjustments.index') }}" icon="🧾"
                        :active="request()->routeIs('inventory.wip_adjustments.*')">
                        Koreksi WIP
                    </x-sidebar.simple-link>
                @endif
            </li>

            {{-- Stock Requests (RTS) --}}
            @if ($canViewRts && ($hasRtsStockReqIndex || $hasRtsDirectReceiveIndex))
                <x-sidebar.label text="Stock Requests" />
                <li class="simple-group">
                    @if ($hasRtsStockReqIndex)
                        <x-sidebar.simple-link href="{{ route('rts.stock-requests.index') }}" icon="🛒"
                            :active="request()->routeIs('rts.stock-requests.*')"
                            :dot-only="$canManageRts && $hasRtsNeedReceive"
                            badge-tone="warn"
                            :badge-title="$rtsBadgeTitle">
                            Permintaan Stock (RTS)
                        </x-sidebar.simple-link>
                    @endif

                    @if ($canManageRts && $hasRtsDirectReceiveIndex)
                        <x-sidebar.simple-link href="{{ route('rts.direct-receives.index') }}" icon="⚡"
                            :active="request()->routeIs('rts.direct-receives.*')">
                            RTS Dadakan
                        </x-sidebar.simple-link>
                    @endif
                </li>
            @endif

            {{-- Marketplace --}}
            @if ($canShow($hasMarketplaceIndex, $hasMarketplaceSalesReport, $hasMarketplaceReconcileQueue, $hasMarketplaceReconcileItemsIndex))
                <x-sidebar.label text="Marketplace" />
                <li class="simple-group">
                    @if ($hasMarketplaceIndex)
                        <x-sidebar.simple-link href="{{ route('marketplace.orders') }}" icon="🛒"
                            :active="request()->routeIs('marketplace.orders') || request()->routeIs('marketplace.orders.*')">
                            Orders
                        </x-sidebar.simple-link>
                    @endif

                    @if ($hasMarketplaceSalesReport)
                        <x-sidebar.simple-link href="{{ route('marketplace.reports.sales') }}" icon="📈"
                            :active="request()->routeIs('marketplace.reports.sales')">
                            Sales Summary
                        </x-sidebar.simple-link>
                    @endif

                    @if ($hasMarketplaceReconcileQueue)
                        <x-sidebar.simple-link href="{{ route('marketplace.reconcile.queue') }}" icon="🧩"
                            :active="request()->routeIs('marketplace.reconcile.*') || request()->routeIs('marketplace.reconciliations.*')">
                            Reconcile Queue
                        </x-sidebar.simple-link>
                    @endif

                    @if ($hasMarketplaceReconcileItemsIndex)
                        <x-sidebar.simple-link href="{{ route('marketplace.reconcile.items') }}" icon="🧾"
                            :active="request()->routeIs('marketplace.reconcile.items*')">
                            Reconcile Items
                        </x-sidebar.simple-link>
                    @endif
                </li>
            @endif

            {{-- Imports (admin only) --}}
            @if ($isAdmin && $canShow(
                $hasImportsMarketplaceIndex,
                $hasImportsMarketplaceDraft,
                $hasImportsMarketplaceCreate,
                $hasImportsMarketplaceExport,
                $hasImportsMarketplaceIncomeCreate,
                $hasImportsMarketplaceIncomeIndex
            ))
                <x-sidebar.label text="Imports" />
                <li class="simple-group">
                    @if ($hasImportsMarketplaceIndex)
                        <x-sidebar.simple-link href="{{ route('imports.marketplace.index') }}" icon="⬆️"
                            :active="request()->routeIs('imports.marketplace.*')">
                            Import Marketplace Shipments
                        </x-sidebar.simple-link>
                    @endif

                    @if ($hasImportsMarketplaceDraft)
                        <x-sidebar.simple-link href="{{ route('imports.marketplace.draft') }}" icon="🕘"
                            :active="request()->routeIs('imports.marketplace.draft')">
                            Draft Shipments
                        </x-sidebar.simple-link>
                    @endif

                    @if ($hasImportsMarketplaceIncomeIndex)
                        <x-sidebar.simple-link href="{{ route('imports.marketplace_income.index') }}" icon="💵"
                            :active="request()->routeIs('imports.marketplace_income.*')">
                            Import Marketplace Income
                        </x-sidebar.simple-link>
                    @endif

                    @if ($hasImportsMarketplaceIncomeDraft)
                        <x-sidebar.simple-link href="{{ route('imports.marketplace_income.draft') }}" icon="🕘"
                            :active="request()->routeIs('imports.marketplace_income.draft')">
                            Draft Income
                        </x-sidebar.simple-link>
                    @endif
                </li>
            @endif

            {{-- Sales (admin only) --}}
            @if ($isAdmin && $canShow($hasSalesShipmentsIndex, $hasSalesShipmentReturnsIndex))
                <x-sidebar.label text="Sales" />
                <li class="simple-group">
                    @if ($hasSalesShipmentsIndex)
                        <x-sidebar.simple-link href="{{ route('sales.shipments.index') }}" icon="🚚"
                            :active="request()->routeIs('sales.shipments.*')">
                            Shipments
                        </x-sidebar.simple-link>
                    @endif

                    @if ($hasSalesShipmentReturnsIndex)
                        <x-sidebar.simple-link href="{{ route('sales.shipment_returns.index') }}" icon="🔁"
                            :active="request()->routeIs('sales.shipment_returns.*')">
                            Retur Shipment
                        </x-sidebar.simple-link>
                    @endif
                </li>
            @endif

            {{-- Production (operating only) --}}
            @if ($isOperating && $canShow(
                $hasProdDashboard,
                $hasProdCuttingJobsIndex,
                $hasProdSewPickupsIndex,
                $hasProdSewReturnsIndex,
                $hasProdFinishingJobsIndex,
                $hasProdFinishingRepairsIndex,
                $hasProdPackingIndex,
                $hasProdQcIndex,
                $hasProdPriorityIndex,
                $hasProdReportsIndex
            ))
                <x-sidebar.label text="Production" />
                <li class="simple-group">
                    @if ($hasProdDashboard)
                        <x-sidebar.simple-link href="{{ route('production.dashboard') }}" icon="📊"
                            :active="request()->routeIs('production.dashboard')">
                            Dashboard Produksi
                        </x-sidebar.simple-link>
                    @endif

                    @if ($hasProdCuttingJobsIndex)
                        <x-sidebar.simple-link href="{{ route('production.cutting_jobs.index') }}" icon="✂️"
                            :active="request()->routeIs('production.cutting_jobs.*')">
                            Cutting Jobs
                        </x-sidebar.simple-link>
                    @endif

                    @if ($hasProdSewPickupsIndex)
                        <x-sidebar.simple-link href="{{ route('production.sewing.pickups.index') }}" icon="🧵"
                            :active="request()->routeIs('production.sewing.pickups.*')">
                            Sewing Pickups
                        </x-sidebar.simple-link>
                    @endif

                    @if ($hasProdSewReturnsIndex)
                        <x-sidebar.simple-link href="{{ route('production.sewing.returns.index') }}" icon="📥"
                            :active="request()->routeIs('production.sewing.returns.*')">
                            Sewing Returns
                        </x-sidebar.simple-link>
                    @endif

                    @if ($hasProdSewRejectReturnsIndex)
                        <x-sidebar.simple-link href="{{ route('production.sewing.reject_returns.index') }}" icon="♻️"
                            :active="request()->routeIs('production.sewing.reject_returns.*')">
                            Setor Reject Jahit
                        </x-sidebar.simple-link>
                    @endif

                    @if ($hasProdQcIndex)
                        <x-sidebar.simple-link href="{{ route('production.qc.index') }}" icon="✅"
                            :active="request()->routeIs('production.qc.*')">
                            QC Produksi
                        </x-sidebar.simple-link>
                    @endif

                    @if ($hasProdFinishingJobsIndex)
                        <x-sidebar.simple-link href="{{ route('production.finishing_jobs.index') }}" icon="🧶"
                            :active="request()->routeIs('production.finishing_jobs.*')">
                            Finishing
                        </x-sidebar.simple-link>
                    @endif

                    @if ($hasProdFinishingRepairsIndex)
                        <x-sidebar.simple-link href="{{ route('production.finishing_repairs.index') }}" icon="🩹"
                            :active="request()->routeIs('production.finishing_repairs.*')">
                            Perbaikan Finishing
                        </x-sidebar.simple-link>
                    @endif

                    @if ($hasProdPackingIndex)
                        <x-sidebar.simple-link href="{{ route('production.packing_jobs.index') }}" icon="📦"
                            :active="request()->routeIs('production.packing_jobs.*')">
                            Packing
                        </x-sidebar.simple-link>
                    @endif

                    @if ($hasProdPriorityIndex)
                        <x-sidebar.simple-link href="{{ route('production.priority.index') }}" icon="🎯"
                            :active="request()->routeIs('production.priority.*')">
                            Prioritas Produksi
                        </x-sidebar.simple-link>
                    @endif

                    @if ($hasProdReportsIndex)
                        <x-sidebar.simple-link href="{{ route('production.reports.index') }}" icon="📈"
                            :active="request()->routeIs('production.reports.*')">
                            Laporan Produksi
                        </x-sidebar.simple-link>
                    @endif

                </li>
            @endif

            {{-- Finance --}}
            @if ($canShow(
                $hasCashExpensesIndex,
                $hasCashReceiptsIndex,
                $hasCashBasisReportIndex,
                $hasJournalsIndex,
                $hasAccountsIndex,
                $hasOpeningBalancesIndex,
                $hasOpeningBalancesBatchIndex
            ))
                <x-sidebar.label text="Finance" />
                <li class="simple-group">
                    @if ($hasCashBasisReportIndex)
                        <x-sidebar.simple-link href="{{ route('accounting.cash-basis-report.index') }}" icon="📊"
                            :active="request()->routeIs('accounting.cash-basis-report.*')">
                            Cash Basis Report
                        </x-sidebar.simple-link>
                    @endif

                    @if ($hasCashExpensesIndex)
                        <x-sidebar.simple-link href="{{ route('accounting.cash-expenses.index') }}" icon="💸"
                            :active="request()->routeIs('accounting.cash-expenses.*')">
                            Cash Expenses
                        </x-sidebar.simple-link>
                    @endif

                    @if ($hasCashReceiptsIndex)
                        <x-sidebar.simple-link href="{{ route('accounting.cash-receipts.index') }}" icon="💰"
                            :active="request()->routeIs('accounting.cash-receipts.*')">
                            Cash Receipts
                        </x-sidebar.simple-link>
                    @endif

                    @if ($hasJournalsIndex)
                        <x-sidebar.simple-link href="{{ route('accounting.journals.index') }}" icon="📓"
                            :active="request()->routeIs('accounting.journals.*')">
                            Journals
                        </x-sidebar.simple-link>
                    @endif

                    @if ($hasAccountsIndex)
                        <x-sidebar.simple-link href="{{ route('accounting.accounts.index') }}" icon="🗂️"
                            :active="request()->routeIs('accounting.accounts.*')">
                            Accounts (COA)
                        </x-sidebar.simple-link>
                    @endif

                    @if ($hasOpeningBalancesIndex)
                        <x-sidebar.simple-link href="{{ route('accounting.opening-balances.index') }}" icon="🟢"
                            :active="request()->routeIs('accounting.opening-balances.*')">
                            Opening Balances
                        </x-sidebar.simple-link>
                    @endif

                    @if ($hasOpeningBalancesBatchIndex)
                        <x-sidebar.simple-link href="{{ route('accounting.opening-balances-batch.index') }}" icon="🧺"
                            :active="request()->routeIs('accounting.opening-balances-batch.*')">
                            Opening Balances Batch
                        </x-sidebar.simple-link>
                    @endif
                </li>
            @endif

        @elseif ($isOwner)
            {{-- =========================================================
                OWNER (collapsible, grouped)
            ========================================================= --}}

            {{-- MASTER DATA --}}
            <x-sidebar.label text="Master" />
            <li class="mb-1">
                <button class="sidebar-link sidebar-toggle {{ $openMaster ? 'is-open' : '' }}" type="button"
                    data-bs-toggle="collapse" data-bs-target="#navMaster"
                    aria-expanded="{{ $openMaster ? 'true' : 'false' }}" aria-controls="navMaster">
                    <span class="icon">🗂️</span>
                    <span>Master Data</span>
                    <span class="chevron">▸</span>
                </button>

                <div class="collapse {{ $openMaster ? 'show' : '' }}" id="navMaster">
                    @if ($hasMasterItemsIndex)
                        <x-sidebar.sub-link href="{{ route('master.items.index') }}" icon="📦"
                            :active="request()->routeIs('master.items.*')">
                            Items
                        </x-sidebar.sub-link>
                    @endif

                    @if ($hasMasterItemCategoriesIndex)
                        <x-sidebar.sub-link href="{{ route('master.item_categories.index') }}" icon="🗂️"
                            :active="request()->routeIs('master.item_categories.*')">
                            Kategori Item
                        </x-sidebar.sub-link>
                    @endif

                    @if ($hasMasterItemBomsIndex)
                        <x-sidebar.sub-link href="{{ route('master.item_boms.index') }}" icon="🧾"
                            :active="request()->routeIs('master.item_boms.*')">
                            BOM SKU
                        </x-sidebar.sub-link>
                    @endif

                    @if ($hasMasterSuppliersIndex)
                        <x-sidebar.sub-link href="{{ route('master.suppliers.index') }}" icon="🏷️"
                            :active="request()->routeIs('master.suppliers.*')">
                            Suppliers
                        </x-sidebar.sub-link>
                    @endif

                    @if ($hasMasterCustomersIndex)
                        <x-sidebar.sub-link href="{{ route('master.customers.index') }}" icon="👤"
                            :active="request()->routeIs('master.customers.*')">
                            Customers
                        </x-sidebar.sub-link>
                    @endif

                    @if ($hasMasterEmployeesIndex)
                        <x-sidebar.sub-link href="{{ route('master.employees.index') }}" icon="🧑‍🏭"
                            :active="request()->routeIs('master.employees.*')">
                            Karyawan
                        </x-sidebar.sub-link>
                    @endif
                </div>
            </li>

            {{-- PURCHASING --}}
            @if ($canShow($hasPoIndex, $hasPoCreate, $hasGrnIndex, $hasGrnCreate))
                <x-sidebar.label text="Procurement" />
                <li class="mb-1">
                    <button class="sidebar-link sidebar-toggle {{ $openPurchasing ? 'is-open' : '' }}" type="button"
                        data-bs-toggle="collapse" data-bs-target="#navPurchasing"
                        aria-expanded="{{ $openPurchasing ? 'true' : 'false' }}" aria-controls="navPurchasing">
                        <span class="icon">🧾</span>
                        <span>Purchasing</span>
                        <span class="chevron">▸</span>
                    </button>

                    <div class="collapse {{ $openPurchasing ? 'show' : '' }}" id="navPurchasing">
                        @php $subhead('Purchase Orders'); @endphp
                        @if ($hasPoIndex)
                            <x-sidebar.sub-link href="{{ route('purchasing.purchase_orders.index') }}" icon="≡"
                                :active="request()->routeIs('purchasing.purchase_orders.index')">
                                Daftar PO
                            </x-sidebar.sub-link>
                        @endif
                        @if ($hasPoCreate)
                            <x-sidebar.sub-link href="{{ route('purchasing.purchase_orders.create') }}" icon="＋"
                                :active="request()->routeIs('purchasing.purchase_orders.create')">
                                PO Baru
                            </x-sidebar.sub-link>
                        @endif

                        @php $subhead('Goods Receipts (GRN)'); @endphp
                        @if ($hasGrnIndex)
                            <x-sidebar.sub-link href="{{ route('purchasing.purchase_receipts.index') }}" icon="≡"
                                :active="request()->routeIs('purchasing.purchase_receipts.index')">
                                Daftar GRN
                            </x-sidebar.sub-link>
                        @endif
                        @if ($hasGrnCreate)
                            <x-sidebar.sub-link href="{{ route('purchasing.purchase_receipts.create') }}" icon="＋"
                                :active="request()->routeIs('purchasing.purchase_receipts.create')">
                                GRN Baru
                            </x-sidebar.sub-link>
                        @endif
                    </div>
                </li>
            @endif

            {{-- MARKETPLACE --}}
            @if ($canShow(
                $hasMarketplaceToko,
                $hasMarketplaceOrders,
                $hasMarketplaceFulfillment,
                $hasMarketplacePicking,
                $hasMarketplaceSkuMapping,
                $hasMarketplaceSync,
                $hasMarketplaceSettlement,
                $hasMarketplaceProfit,
                $hasMarketplaceAds,
                $hasMarketplaceAnalytics,
                $hasMarketplaceIssues
            ))
                <x-sidebar.label text="Marketplace" />
                <li class="mb-1">
                    <button class="sidebar-link sidebar-toggle {{ $openMarketplaceTools ? 'is-open' : '' }}" type="button"
                        data-bs-toggle="collapse" data-bs-target="#navMarketplaceOwner"
                        aria-expanded="{{ $openMarketplaceTools ? 'true' : 'false' }}" aria-controls="navMarketplaceOwner">
                        <span class="icon">🛒</span>
                        <span>Marketplace</span>
                        <span class="chevron">▸</span>
                    </button>

                    <div class="collapse {{ $openMarketplaceTools ? 'show' : '' }}" id="navMarketplaceOwner">
                        @php $subhead('Operasional'); @endphp
                        @if ($hasMarketplaceToko)
                            <x-sidebar.sub-link href="{{ route('marketplace.toko') }}" icon="🏪"
                                :active="request()->routeIs('marketplace.toko')">
                                Toko & Channel
                            </x-sidebar.sub-link>
                        @endif
                        @if ($hasMarketplaceOrders)
                            <x-sidebar.sub-link href="{{ route('marketplace.orders') }}" icon="📋"
                                :active="request()->routeIs('marketplace.orders') || request()->routeIs('marketplace.orders.*')">
                                Order Lokal
                            </x-sidebar.sub-link>
                        @endif
                        @if ($hasMarketplaceFulfillment)
                            <x-sidebar.sub-link href="{{ route('marketplace.fulfillment') }}" icon="🚚"
                                :active="request()->routeIs('marketplace.fulfillment') || request()->routeIs('marketplace.fulfillment.*')">
                                Fulfillment
                            </x-sidebar.sub-link>
                        @endif
                        @if ($hasMarketplacePicking)
                            <x-sidebar.sub-link href="{{ route('marketplace.picking') }}" icon="🧺"
                                :active="request()->routeIs('marketplace.picking')">
                                Picking
                            </x-sidebar.sub-link>
                        @endif

                        @php $subhead('Data & Sinkron'); @endphp
                        @if ($hasMarketplaceSkuMapping)
                            <x-sidebar.sub-link href="{{ route('marketplace.sku-mapping') }}" icon="🔗"
                                :active="request()->routeIs('marketplace.sku-mapping')">
                                SKU Mapping
                            </x-sidebar.sub-link>
                        @endif
                        @if ($hasMarketplaceSync)
                            <x-sidebar.sub-link href="{{ route('marketplace.sync') }}" icon="↓"
                                :active="request()->routeIs('marketplace.sync')">
                                Sync Order
                            </x-sidebar.sub-link>
                        @endif
                        @if ($hasMarketplaceIssues)
                            <x-sidebar.sub-link href="{{ route('marketplace.issues') }}" icon="⚠️"
                                :active="request()->routeIs('marketplace.issues')">
                                Data Perlu Diperbaiki
                            </x-sidebar.sub-link>
                        @endif

                        @php $subhead('Analisa'); @endphp
                        @if ($hasMarketplaceSettlement)
                            <x-sidebar.sub-link href="{{ route('marketplace.settlement') }}" icon="💰"
                                :active="request()->routeIs('marketplace.settlement')">
                                Settlement
                            </x-sidebar.sub-link>
                        @endif
                        @if ($hasMarketplaceProfit)
                            <x-sidebar.sub-link href="{{ route('marketplace.profit') }}" icon="📈"
                                :active="request()->routeIs('marketplace.profit')">
                                Profit Order
                            </x-sidebar.sub-link>
                        @endif
                        @if ($hasMarketplaceAds)
                            <x-sidebar.sub-link href="{{ route('marketplace.ads') }}" icon="🎯"
                                :active="request()->routeIs('marketplace.ads')">
                                Analisa Iklan
                            </x-sidebar.sub-link>
                        @endif
                        @if ($hasMarketplaceAnalytics)
                            <x-sidebar.sub-link href="{{ route('marketplace.analytics') }}" icon="📊"
                                :active="request()->routeIs('marketplace.analytics')">
                                Sales Analytics
                            </x-sidebar.sub-link>
                        @endif
                    </div>
                </li>
            @endif

            {{-- SALES --}}
            @if ($canShow(
                $hasSalesInvoicesIndex,
                $hasSalesInvoicesCreate,
                $hasSalesShipmentsIndex,
                $hasSalesShipmentsCreate,
                $hasSalesShipmentReturnsIndex,
                $hasSalesReportPerformance,
                $hasSalesShipmentsReport,
                $hasSalesReportItemProfit,
                $hasSalesReportChannelProfit,
                $hasSalesReportShipmentAnalytics
            ))
                <li class="mb-1">
                    <button class="sidebar-link sidebar-toggle {{ $openSales ? 'is-open' : '' }}" type="button"
                        data-bs-toggle="collapse" data-bs-target="#navSales"
                        aria-expanded="{{ $openSales ? 'true' : 'false' }}" aria-controls="navSales">
                        <span class="icon">📑</span>
                        <span>Sales Operations</span>
                        <span class="chevron">▸</span>
                    </button>

                    <div class="collapse {{ $openSales ? 'show' : '' }}" id="navSales">
                        @php $subhead('Invoices'); @endphp
                        @if ($hasSalesInvoicesIndex)
                            <x-sidebar.sub-link href="{{ route('sales.invoices.index') }}" icon="≡"
                                :active="request()->routeIs('sales.invoices.index')">
                                Daftar Invoice
                            </x-sidebar.sub-link>
                        @endif
                        @if ($hasSalesInvoicesCreate)
                            <x-sidebar.sub-link href="{{ route('sales.invoices.create') }}" icon="＋"
                                :active="request()->routeIs('sales.invoices.create')">
                                Invoice Baru
                            </x-sidebar.sub-link>
                        @endif

                        @php $subhead('Shipments'); @endphp
                        @if ($hasSalesShipmentsIndex)
                            <x-sidebar.sub-link href="{{ route('sales.shipments.index') }}" icon="🚚"
                                :active="request()->routeIs('sales.shipments.index')">
                                Daftar Shipment
                            </x-sidebar.sub-link>
                        @endif
                        @if ($hasSalesShipmentsCreate)
                            <x-sidebar.sub-link href="{{ route('sales.shipments.create') }}" icon="＋"
                                :active="request()->routeIs('sales.shipments.create')">
                                Shipment Baru
                            </x-sidebar.sub-link>
                        @endif

                        @php $subhead('Returns'); @endphp
                        @if ($hasSalesShipmentReturnsIndex)
                            <x-sidebar.sub-link href="{{ route('sales.shipment_returns.index') }}" icon="🔁"
                                :active="request()->routeIs('sales.shipment_returns.index')">
                                Daftar Retur
                            </x-sidebar.sub-link>
                        @endif

                        @php $subhead('Reports'); @endphp
                        @if ($hasSalesReportPerformance)
                            <x-sidebar.sub-link href="{{ route('sales.reports.sales_performance.index') }}" icon="📈"
                                :active="request()->routeIs('sales.reports.sales_performance.*')">
                                Sales Performance
                            </x-sidebar.sub-link>
                        @endif
                        @if ($hasSalesShipmentsReport)
                            <x-sidebar.sub-link href="{{ route('sales.shipments.report') }}" icon="📊"
                                :active="request()->routeIs('sales.shipments.report')">
                                Laporan Pengiriman
                            </x-sidebar.sub-link>
                        @endif
                        @if ($hasSalesReportItemProfit)
                            <x-sidebar.sub-link href="{{ route('sales.reports.item_profit') }}" icon="💰"
                                :active="request()->routeIs('sales.reports.item_profit')">
                                Profit per Item
                            </x-sidebar.sub-link>
                        @endif
                        @if ($hasSalesReportChannelProfit)
                            <x-sidebar.sub-link href="{{ route('sales.reports.channel_profit') }}" icon="🏷️"
                                :active="request()->routeIs('sales.reports.channel_profit')">
                                Profit per Channel
                            </x-sidebar.sub-link>
                        @endif
                        @if ($hasSalesReportShipmentAnalytics)
                            <x-sidebar.sub-link href="{{ route('sales.reports.shipment_analytics') }}" icon="🧠"
                                :active="request()->routeIs('sales.reports.shipment_analytics')">
                                Shipment Analytics
                            </x-sidebar.sub-link>
                        @endif
                    </div>
                </li>
            @endif

            {{-- INVENTORY --}}
            @if ($canShow(
                $hasInvIntelligence,
                $hasInvStocksItems,
                $hasInvStocksLots,
                $hasInvStockCard,
                $hasInvTransfersIndex,
                $hasInvTransfersCreate,
                $hasInvAdjustmentsIndex,
                $hasInvWipAdjIndex,
                $hasInvOpnamesIndex,
                $hasInvOpnamesCreate,
                $hasInvExternalIndex,
                $hasInvExternalCreate
            ))
                <x-sidebar.label text="Inventory" />
                <li class="mb-1">
                    <button class="sidebar-link sidebar-toggle {{ $openInventory ? 'is-open' : '' }}" type="button"
                        data-bs-toggle="collapse" data-bs-target="#navInventory"
                        aria-expanded="{{ $openInventory ? 'true' : 'false' }}" aria-controls="navInventory">
                        <span class="icon">📦</span>
                        <span>Inventory</span>
                        <span class="chevron">▸</span>
                    </button>

                    <div class="collapse {{ $openInventory ? 'show' : '' }}" id="navInventory">
                        @if ($hasInvIntelligence)
                            @php $subhead('Intelligence'); @endphp
                            <x-sidebar.sub-link href="{{ route('inventory.intelligence') }}" icon="🧠"
                                :active="request()->routeIs('inventory.intelligence') || request()->routeIs('inventory.intelligence.*')">
                                Inventory Intelligence
                            </x-sidebar.sub-link>
                        @endif

                        @php $subhead('Stock'); @endphp
                        @if ($hasInvStocksItems)
                            <x-sidebar.sub-link href="{{ route('inventory.stocks.items') }}" icon="📦"
                                :active="request()->routeIs('inventory.stocks.items')">
                                Stok Barang
                            </x-sidebar.sub-link>
                        @endif
                        @if ($hasInvStocksLots)
                            <x-sidebar.sub-link href="{{ route('inventory.stocks.lots') }}" icon="🎫"
                                :active="request()->routeIs('inventory.stocks.lots')">
                                Stok per LOT
                            </x-sidebar.sub-link>
                        @endif
                        @if ($hasInvStockCard)
                            <x-sidebar.sub-link href="{{ route('inventory.stock_card.index') }}" icon="📋"
                                :active="request()->routeIs('inventory.stock_card.*')">
                                Kartu Stok
                            </x-sidebar.sub-link>
                        @endif
                        @if ($hasInvTransfersIndex)
                            <x-sidebar.sub-link href="{{ route('inventory.transfers.index') }}" icon="🔁"
                                :active="request()->routeIs('inventory.transfers.index')">
                                Daftar Transfer
                            </x-sidebar.sub-link>
                        @endif
                        @if ($hasInvTransfersCreate)
                            <x-sidebar.sub-link href="{{ route('inventory.transfers.create') }}" icon="➕"
                                :active="request()->routeIs('inventory.transfers.create')">
                                Transfer Baru
                            </x-sidebar.sub-link>
                        @endif
                        @if ($hasInvAdjustmentsIndex)
                            <x-sidebar.sub-link href="{{ route('inventory.adjustments.index') }}" icon="⚖️"
                                :active="request()->routeIs('inventory.adjustments.*')">
                                Inventory Adjustments
                            </x-sidebar.sub-link>
                        @endif
                        @if ($hasInvWipAdjIndex)
                            <x-sidebar.sub-link href="{{ route('inventory.wip_adjustments.index') }}" icon="🧾"
                                :active="request()->routeIs('inventory.wip_adjustments.*')">
                                Koreksi WIP
                            </x-sidebar.sub-link>
                        @endif

                        @if ($hasInvWipCutReconcile)
                            <x-sidebar.sub-link href="{{ route('inventory.wip_cut_reconcile.index') }}" icon="🔍"
                                :active="request()->routeIs('inventory.wip_cut_reconcile.*')">
                                Rekonsiliasi WIP-CUT
                            </x-sidebar.sub-link>
                        @endif

                        @php $subhead('Opname'); @endphp
                        @if ($hasInvOpnamesIndex)
                            <x-sidebar.sub-link href="{{ route('inventory.stock_opnames.index') }}" icon="📊"
                                :active="request()->routeIs('inventory.stock_opnames.*')">
                                Stock Opname
                            </x-sidebar.sub-link>
                        @endif
                        @if ($hasInvOpnamesCreate)
                            <x-sidebar.sub-link href="{{ route('inventory.stock_opnames.create') }}" icon="＋"
                                :active="request()->routeIs('inventory.stock_opnames.create')">
                                Stock Opname Baru
                            </x-sidebar.sub-link>
                        @endif

                        @php $subhead('External'); @endphp
                        @if ($hasInvExternalIndex)
                            <x-sidebar.sub-link href="{{ route('inventory.external_transfers.index') }}" icon="🚚"
                                :active="request()->routeIs('inventory.external_transfers.index')">
                                Daftar External TF
                            </x-sidebar.sub-link>
                        @endif
                        @if ($hasInvExternalCreate)
                            <x-sidebar.sub-link href="{{ route('inventory.external_transfers.create') }}" icon="➕"
                                :active="request()->routeIs('inventory.external_transfers.create')">
                                External TF Baru
                            </x-sidebar.sub-link>
                        @endif
                    </div>
                </li>
            @endif

            {{-- STOCK REQUESTS --}}
            @if ($canViewRts && $canShow($hasRtsStockReqIndex, $hasRtsDirectReceiveIndex))
                <x-sidebar.label text="Stock Requests" />
                <li class="mb-1">
                    <button class="sidebar-link sidebar-toggle {{ $openStockRequests ? 'is-open' : '' }}" type="button"
                        data-bs-toggle="collapse" data-bs-target="#navStockRequests"
                        aria-expanded="{{ $openStockRequests ? 'true' : 'false' }}"
                        aria-controls="navStockRequests">
                        <span class="icon">📤</span>
                        <span>Stock Requests</span>
                        <span class="chevron">▸</span>
                    </button>

                    <div class="collapse {{ $openStockRequests ? 'show' : '' }}" id="navStockRequests">
                        @if ($hasRtsStockReqIndex)
                            <x-sidebar.sub-link href="{{ route('rts.stock-requests.index') }}" icon="🛒"
                                :active="request()->routeIs('rts.stock-requests.*')"
                                :dot-only="$canManageRts && $hasRtsNeedReceive"
                                badge-tone="warn"
                                :badge-title="$rtsBadgeTitle">
                                Permintaan Stock (RTS)
                            </x-sidebar.sub-link>
                        @endif

                        @if ($canManageRts && $hasRtsDirectReceiveIndex)
                            <x-sidebar.sub-link href="{{ route('rts.direct-receives.index') }}" icon="⚡"
                                :active="request()->routeIs('rts.direct-receives.*')">
                                RTS Dadakan
                            </x-sidebar.sub-link>
                        @endif
                    </div>
                </li>
            @endif

            {{-- PRODUCTION --}}
            @if ($canShow(
                $hasProdCuttingJobsIndex,
                $hasProdSewPickupsIndex,
                $hasProdSewReturnsIndex,
                $hasProdFinishingJobsIndex,
                $hasProdFinishingRepairsIndex,
                $hasProdPackingIndex,
                $hasProdQcIndex,
                $hasProdDashboard,
                $hasProdPriorityIndex,
                $hasProdReportsIndex
            ))
                <x-sidebar.label text="Production" />
                <li class="mb-1">
                    <button class="sidebar-link sidebar-toggle {{ $openProduction ? 'is-open' : '' }}" type="button"
                        data-bs-toggle="collapse" data-bs-target="#navProduction"
                        aria-expanded="{{ $openProduction ? 'true' : 'false' }}" aria-controls="navProduction">
                        <span class="icon">🏭</span>
                        <span>Production</span>
                        <span class="chevron">▸</span>
                    </button>

                    <div class="collapse {{ $openProduction ? 'show' : '' }}" id="navProduction">
                        @php $subhead('Monitoring'); @endphp
                        @if ($hasProdDashboard)
                            <x-sidebar.sub-link href="{{ route('production.dashboard') }}" icon="📊"
                                :active="request()->routeIs('production.dashboard')">
                                Dashboard Produksi
                            </x-sidebar.sub-link>
                        @endif
                        @if ($hasProdPriorityIndex)
                            <x-sidebar.sub-link href="{{ route('production.priority.index') }}" icon="🎯"
                                :active="request()->routeIs('production.priority.*')">
                                Prioritas Produksi
                            </x-sidebar.sub-link>
                        @endif
                        @if ($hasProdReportsIndex)
                            <x-sidebar.sub-link href="{{ route('production.reports.index') }}" icon="📈"
                                :active="request()->routeIs('production.reports.*')">
                                Laporan Produksi
                            </x-sidebar.sub-link>
                        @endif

                        @php $subhead('Alur Produksi'); @endphp
                        @if ($hasProdCuttingJobsIndex)
                            <x-sidebar.sub-link href="{{ route('production.cutting_jobs.index') }}" icon="✂️"
                                :active="request()->routeIs('production.cutting_jobs.*')">
                                Cutting Jobs
                            </x-sidebar.sub-link>
                        @endif
                        @if ($hasProdSewPickupsIndex)
                            <x-sidebar.sub-link href="{{ route('production.sewing.pickups.index') }}" icon="🧵"
                                :active="request()->routeIs('production.sewing.pickups.*')">
                                Sewing Pickups
                            </x-sidebar.sub-link>
                        @endif
                        @if ($hasProdSewReturnsIndex)
                            <x-sidebar.sub-link href="{{ route('production.sewing.returns.index') }}" icon="📥"
                                :active="request()->routeIs('production.sewing.returns.index')">
                                Sewing Returns
                            </x-sidebar.sub-link>
                        @endif
                        @if ($hasProdSewRejectReturnsIndex)
                            <x-sidebar.sub-link href="{{ route('production.sewing.reject_returns.index') }}" icon="♻️"
                                :active="request()->routeIs('production.sewing.reject_returns.*')">
                                Setor Reject Jahit
                            </x-sidebar.sub-link>
                        @endif
                        @if ($hasProdQcIndex)
                            <x-sidebar.sub-link href="{{ route('production.qc.index') }}" icon="✅"
                                :active="request()->routeIs('production.qc.*')">
                                QC Produksi
                            </x-sidebar.sub-link>
                        @endif
                        @if ($hasProdFinishingJobsIndex)
                            <x-sidebar.sub-link href="{{ route('production.finishing_jobs.index') }}" icon="🧶"
                                :active="request()->routeIs('production.finishing_jobs.*')">
                                Finishing Jobs
                            </x-sidebar.sub-link>
                        @endif
                        @if ($hasProdFinishingRepairsIndex)
                            <x-sidebar.sub-link href="{{ route('production.finishing_repairs.index') }}" icon="🩹"
                                :active="request()->routeIs('production.finishing_repairs.*')">
                                Perbaikan Finishing
                            </x-sidebar.sub-link>
                        @endif
                        @if ($hasProdPackingIndex)
                            <x-sidebar.sub-link href="{{ route('production.packing_jobs.index') }}" icon="📦"
                                :active="request()->routeIs('production.packing_jobs.*')">
                                Packing
                            </x-sidebar.sub-link>
                        @endif
                    </div>
                </li>
            @endif

            {{-- FINANCE --}}
            @if ($canShow($hasOpeningBalancesIndex, $hasOpeningBalancesBatchIndex, $hasCashExpensesIndex, $hasCashReceiptsIndex, $hasCashBasisReportIndex, $hasJournalsIndex, $hasAccountsIndex))
                <x-sidebar.label text="Finance" />
                <li class="mb-1">
                    <button class="sidebar-link sidebar-toggle {{ $openAccounting ? 'is-open' : '' }}" type="button"
                        data-bs-toggle="collapse" data-bs-target="#navAccounting"
                        aria-expanded="{{ $openAccounting ? 'true' : 'false' }}" aria-controls="navAccounting">
                        <span class="icon">🧾</span>
                        <span>Accounting</span>
                        <span class="chevron">▸</span>
                    </button>

                    <div class="collapse {{ $openAccounting ? 'show' : '' }}" id="navAccounting">
                        @if ($hasCashBasisReportIndex)
                            <x-sidebar.sub-link href="{{ route('accounting.cash-basis-report.index') }}" icon="📊"
                                :active="request()->routeIs('accounting.cash-basis-report.*')">
                                Cash Basis Report
                            </x-sidebar.sub-link>
                        @endif

                        @if ($hasOpeningBalancesIndex)
                            <x-sidebar.sub-link href="{{ route('accounting.opening-balances.index') }}" icon="🟢"
                                :active="request()->routeIs('accounting.opening-balances.*')">
                                Opening Balances
                            </x-sidebar.sub-link>
                        @endif

                        @if ($hasOpeningBalancesBatchIndex)
                            <x-sidebar.sub-link href="{{ route('accounting.opening-balances-batch.index') }}" icon="🧺"
                                :active="request()->routeIs('accounting.opening-balances-batch.*')">
                                Opening Balances Batch
                            </x-sidebar.sub-link>
                        @endif

                        @if ($hasCashExpensesIndex)
                            <x-sidebar.sub-link href="{{ route('accounting.cash-expenses.index') }}" icon="💸"
                                :active="request()->routeIs('accounting.cash-expenses.*')">
                                Cash Expenses
                            </x-sidebar.sub-link>
                        @endif

                        @if ($hasCashReceiptsIndex)
                            <x-sidebar.sub-link href="{{ route('accounting.cash-receipts.index') }}" icon="💰"
                                :active="request()->routeIs('accounting.cash-receipts.*')">
                                Cash Receipts
                            </x-sidebar.sub-link>
                        @endif

                        @if ($hasJournalsIndex)
                            <x-sidebar.sub-link href="{{ route('accounting.journals.index') }}" icon="📓"
                                :active="request()->routeIs('accounting.journals.*')">
                                Journals
                            </x-sidebar.sub-link>
                        @endif

                        @if ($hasAccountsIndex)
                            <x-sidebar.sub-link href="{{ route('accounting.accounts.index') }}" icon="🗂️"
                                :active="request()->routeIs('accounting.accounts.*')">
                                Accounts (COA)
                            </x-sidebar.sub-link>
                        @endif
                    </div>
                </li>
            @endif

            {{-- PAYROLL --}}
            @if ($canShow($hasPayrollDashboard, $hasPieceworkIndex, $hasPieceRatesIndex, $hasPayrollReportsOperators))
                <x-sidebar.label text="Payroll" />
                <li class="mb-1">
                    <button class="sidebar-link sidebar-toggle {{ $openPayroll ? 'is-open' : '' }}" type="button"
                        data-bs-toggle="collapse" data-bs-target="#navPayroll"
                        aria-expanded="{{ $openPayroll ? 'true' : 'false' }}" aria-controls="navPayroll">
                        <span class="icon">💰</span>
                        <span>Payroll</span>
                        <span class="chevron">▸</span>
                    </button>

                    <div class="collapse {{ $openPayroll ? 'show' : '' }}" id="navPayroll">
                        @if ($hasPayrollDashboard)
                            <x-sidebar.sub-link href="{{ route('payroll.dashboard') }}" icon="📈"
                                :active="request()->routeIs('payroll.dashboard*')">
                                Dashboard
                            </x-sidebar.sub-link>
                        @endif

                        @if ($hasPieceworkIndex)
                            <x-sidebar.sub-link href="{{ route('payroll.piecework.index', ['module' => 'cutting']) }}" icon="✂️"
                                :active="$pieceworkCuttingActive">
                                Cutting Payroll
                            </x-sidebar.sub-link>

                            <x-sidebar.sub-link href="{{ route('payroll.piecework.index', ['module' => 'sewing']) }}" icon="🧵"
                                :active="$pieceworkSewingActive">
                                Sewing Payroll
                            </x-sidebar.sub-link>
                        @endif

                        @if ($hasPieceRatesIndex)
                            <x-sidebar.sub-link href="{{ route('payroll.piece_rates.index') }}" icon="📑"
                                :active="request()->routeIs('payroll.piece_rates.*')">
                                Piece Rates
                            </x-sidebar.sub-link>
                        @endif

                        @if ($hasPayrollReportsOperators)
                            <x-sidebar.sub-link href="{{ route('payroll.reports.operators') }}" icon="📊"
                                :active="request()->routeIs('payroll.reports.*')">
                                Reports
                            </x-sidebar.sub-link>
                        @endif
                    </div>
                </li>
            @endif

            {{-- COSTING --}}
            @if ($canShow($hasHppIndex, $hasProdCostPeriodsIndex))
                <x-sidebar.label text="Costing" />
                <li class="mb-1">
                    <button class="sidebar-link sidebar-toggle {{ $openCosting ? 'is-open' : '' }}" type="button"
                        data-bs-toggle="collapse" data-bs-target="#navCosting"
                        aria-expanded="{{ $openCosting ? 'true' : 'false' }}" aria-controls="navCosting">
                        <span class="icon">📉</span>
                        <span>Costing &amp; HPP</span>
                        <span class="chevron">▸</span>
                    </button>

                    <div class="collapse {{ $openCosting ? 'show' : '' }}" id="navCosting">
                        @if ($hasHppIndex)
                            <x-sidebar.sub-link href="{{ route('costing.hpp.index') }}" icon="⚙️"
                                :active="request()->routeIs('costing.hpp.*')">
                                HPP Finished Goods
                            </x-sidebar.sub-link>
                        @endif

                        @if ($hasProdCostPeriodsIndex)
                            <x-sidebar.sub-link href="{{ route('costing.production_cost_periods.index') }}" icon="📆"
                                :active="request()->routeIs('costing.production_cost_periods.*')">
                                Production Cost Periods
                            </x-sidebar.sub-link>
                        @endif
                    </div>
                </li>
            @endif

        @endif
    </ul>
</aside>
