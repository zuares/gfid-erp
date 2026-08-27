{{-- resources/views/layouts/partials/mobile-sidebar.blade.php --}}

<style>
    /* ============================
       MOBILE SIDEBAR (DRAWER)
    ============================ */

    .mobile-sidebar-panel {
        --sidebar-active-blue: #2563eb;
    }

    body[data-theme="dark"] .mobile-sidebar-panel {
        --sidebar-active-blue: #60a5fa;
    }

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
        box-shadow: inset 3px 0 0 var(--sidebar-active-blue);
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
        box-shadow: inset 3px 0 0 var(--sidebar-active-blue);
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
        background: var(--sidebar-active-blue);
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
    if ($user && $user::moduleAccessTableExists()) {
        $user->loadMissing('moduleAccesses');
    }
    $role = strtolower((string) ($user?->role ?? ''));

    $isDev = $user && $user->isDeveloper();
    $isOwner = $role === 'owner' || $isDev;
    $isAdmin = $role === 'admin' && !$isDev;
    $isOperating = $role === 'operating';
    // AI dan komunikasi hanya ditampilkan untuk owner/operating.
    $canOpenAiAgent = $isOwner || $isOperating;

    // operator lapangan
    $isOperatorRole = in_array($role, ['sewing', 'cutting']);

    // capability flags (match desktop)
    $canViewRts = $isOwner || $isAdmin || $isOperating;
    $canManageRts = $isOwner || $isAdmin;

    $router = app('router');

    // ROUTE GUARDS
    $hasDashboardRoute = $router->has('dashboard');
    $hasAiIndexRoute = $router->has('ai.index');
    $hasAiAgentRoute = $router->has('ai.agent');
    $hasAiOpenAiRoute = $router->has('ai.openai.index');
    $hasOwnerAccessControl = $router->has('owner.access-control.index');
    $hasAdminCatalogProducts = $router->has('admin.catalog.products.index');
    $hasAdminCatalogCategories = $router->has('admin.catalog.categories.index');
    $hasAdminCrmDashboard = $router->has('admin.crm.dashboard');
    $hasAdminCrmVisitors = $router->has('admin.crm.visitors');
    $hasAdminCrmOrders = $router->has('admin.crm.orders');
    $hasAdminCrmProspects = $router->has('admin.crm.prospects');
    $hasAdminCrmCustomers = $router->has('admin.crm.customers');
    $hasAdminCrmSegments = $router->has('admin.crm.segments');

    // Data Master
    $hasMasterItemsIndex = $router->has('master.items.index');
    $hasMasterCustomersIndex = $router->has('master.customers.index');
    $hasMasterSuppliersIndex = $router->has('master.suppliers.index');
    $hasMasterItemBomsIndex = $router->has('master.item_boms.index');
    $hasMasterEmployeesIndex = $router->has('master.employees.index');

    // Pembelian
    $hasPurchasingDashboard  = $router->has('purchasing.dashboard');
    $hasPoIndex              = $router->has('purchasing.purchase_orders.index');
    $hasPoCreate             = $router->has('purchasing.purchase_orders.create');
    $hasGrnIndex             = $router->has('purchasing.purchase_receipts.index');
    $hasGrnCreate            = $router->has('purchasing.purchase_receipts.create');
    $hasPurchaseReturnIndex    = $router->has('purchasing.purchase_returns.index');
    $hasSupplierInvoiceIndex   = $router->has('purchasing.supplier_invoices.index');
    $hasPurchasePaymentsIndex  = $router->has('purchasing.purchase_payments.index');
    $hasPrIndex                = $router->has('purchasing.purchase_requests.index');
    $hasMaterialShortageIndex  = $router->has('purchasing.material_shortages.index');
    $hasSupplierItemsIndex     = $router->has('purchasing.supplier_items.index');

    // Toko Online
    $hasMarketplaceCreate = $router->has('marketplace.orders.create');
    $hasMarketplaceToko = $router->has('marketplace.toko');
    $hasMarketplaceOrders = $router->has('marketplace.orders');
    $hasMarketplacePemenuhan = $router->has('marketplace.fulfillment');
    $hasMarketplacePickingBarang = $router->has('marketplace.picking');
    $hasMarketplaceSkuMapping = $router->has('marketplace.sku-mapping');
    $hasMarketplaceSync = $router->has('marketplace.sync');
    $hasMarketplacePromotions = $router->has('marketplace.promotions');
    $hasMarketplacePromotionsSummary = $router->has('marketplace.promotions.summary');
    $hasMarketplacePencairanDana = $router->has('marketplace.settlement');
    $hasMarketplaceIncomeDetail = $router->has('marketplace.income-detail');
    $hasMarketplaceProfit = $router->has('marketplace.profit');
    $hasMarketplaceAds = $router->has('marketplace.ads');
    $hasMarketplaceAnalytics = $router->has('marketplace.analytics');
    $hasMarketplaceIssues = $router->has('marketplace.issues');
    $hasMarketplaceIndex = $router->has('marketplace.orders');
    $hasMarketplaceSalesReport = $router->has('marketplace.reports.sales');
    $hasMarketplaceProfitReport = $isOwner && $router->has('marketplace.reports.profit');
    $hasMarketplaceFinancialStatement = $isOwner && $router->has('marketplace.reports.financial-statement');
    $hasMarketplaceFinancialClosing = $isOwner && $router->has('marketplace.reports.financial-closing');
    $hasMarketplaceFinancialQuality = $isOwner && $router->has('marketplace.reports.financial-quality');
    $hasMarketplaceReconcileQueue = $router->has('marketplace.reconcile.queue');
    $hasMarketplaceReconcileItemsIndex = $router->has('marketplace.reconcile.items');

    // Impor Toko Online
    $hasImportMarketplaceIndex = $router->has('imports.marketplace.index');
    $hasImportMarketplaceDraft = $router->has('imports.marketplace.draft');
    $hasImportMarketplaceIncomeIndex = $router->has('imports.marketplace_income.index');
    $hasImportMarketplaceIncomeDraft = $router->has('imports.marketplace_income.draft');

    // Sales
    $hasSalesInvoicesIndex = $router->has('sales.invoices.index');
    $hasSalesInvoicesCreate = $router->has('sales.invoices.create');
    $hasSalesShipmentsIndex = $router->has('sales.shipments.index');
    $hasSalesShipmentsCreate = $router->has('sales.shipments.create');
    $hasSalesShipmentReturnsIndex = $router->has('sales.shipment_returns.index');
    $hasSalesShipmentReturnsCreate = $router->has('sales.shipment_returns.create');
    $hasSalesOperationalSettings = $isOwner && $router->has('sales.settings.operational');
    $hasSalesShipmentsReport = $router->has('sales.reports.shipment');

    // Sales reports (opsional—kamu punya beberapa, tapi bukan index)
    $hasSalesReportItemProfit = $router->has('sales.reports.item_profit');
    $hasSalesReportChannelProfit = $router->has('sales.reports.channel_profit');
    $hasSalesReportShipmentAnalytics = $router->has('sales.reports.shipment_analytics');

    // Persediaan
    $hasInvIntelligence = $router->has('inventory.intelligence');
    $hasWhIntelligence = $router->has('inventory.warehouse_intelligence');
    $hasInvStocksItems = $router->has('inventory.stocks.items');
    $hasInvStocksLots = $router->has('inventory.stocks.lots');
    $hasInvStockCard = $router->has('inventory.stock_card.index');
    $hasInvBarcodes = $router->has('inventory.barcodes.create') && ($isOwner || $isAdmin);
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

    // Produksi (Jobs)
    $hasProdCuttingJobsIndex = $router->has('production.cutting_jobs.index');
    $hasProdCuttingJobsCreate = $router->has('production.cutting_jobs.create');

    $hasProdSewPickupsIndex = $router->has('production.sewing.pickups.index');
    $hasProdSewPickupsCreate = $router->has('production.sewing.pickups.create');

    $hasProdSewReturnsIndex = $router->has('production.sewing.returns.index');
    $hasProdSewReturnsCreate = $router->has('production.sewing.returns.create');
    $hasProdSewRejectReturnsIndex = $router->has('production.sewing.reject_returns.index');

    $hasProdFinishingJobsIndex = $router->has('production.finishing_jobs.index');
    $hasProdFinishingJobsCreate = $router->has('production.finishing_jobs.create');
    $hasProdFinishingRepairsIndex = $router->has('production.finishing_repairs.index');

    $hasProdQcIndex = $router->has('production.qc.index');
    $prodQcHref = $hasProdQcIndex
        ? route('production.qc.index', (auth()->user()?->role ?? null) === 'admin' ? ['stage' => 'sewing'] : [])
        : '#';
    $prodQcLabel = ($role ?? null) === 'admin' ? 'QC Jahit' : 'QC Produksi';
    $hasProdPackingIndex = $router->has('production.packing_jobs.index');
    $hasProdPriorityIndex = $router->has('production.priority.index');
    $hasProdReportsIndex = $router->has('production.reports.index');

    // Produksi dashboard (konsolidasi semua report)
    $hasProdDashboard = $router->has('production.dashboard');

    // Rekonsiliasi gap cost (owner only)
    $hasProdReconcile = $router->has('production.reconcile.index');

    // Keuangan (Akuntansi)
    $hasCashExpensesIndex = $router->has('accounting.cash-expenses.index');
    $hasCashBasisReportIndex = $router->has('accounting.cash-basis-report.index');
    $hasCashReceiptsIndex = $router->has('accounting.cash-receipts.index');
    $hasMarketplacePayoutsIndex = !$isAdmin && $router->has('accounting.marketplace-payouts.index');
    $hasApReportIndex           = $router->has('accounting.ap-report.index');
    $hasTrialBalanceIndex       = $router->has('accounting.trial-balance.index');
    $hasProfitLossIndex         = $router->has('accounting.profit-loss.index');
    $hasBukuBesarIndex          = $router->has('accounting.buku-besar.index');
    $hasJournalsIndex = $router->has('accounting.journals.index');
    $hasAccountsIndex = $router->has('accounting.accounts.index');
    $hasOpeningBalancesIndex = $router->has('accounting.opening-balances.index');

    // Penggajian
    $hasPayrollDashboard = $router->has('payroll.dashboard');
    $hasPieceworkIndex = $router->has('payroll.piecework.index');
    $hasPieceRatesIndex = $router->has('payroll.piece_rates.index');
    $hasPayrollReportsOperators = $router->has('payroll.reports.operators');
    $hasPayrollReportsOperatorSlips = $router->has('payroll.reports.operator_slips');

    // Biaya Produksi
    $hasHppIndex = $router->has('costing.hpp.index');
    $hasProdCostPeriodsIndex = $router->has('costing.production_cost_periods.index');

    $canModule = fn (string $module) => $user ? $user->canAccessModule($module) : false;
    $hasDashboardRoute = $hasDashboardRoute && $canModule('dashboard');
    $hasCashExpensesIndex = $hasCashExpensesIndex && $canModule('cash-expenses');

    if (!$canModule('master')) {
        // Admin hanya mendapat akses CRUD Master Item, bukan seluruh Master Data.
        $hasMasterItemsIndex = $isAdmin || $isOwner;
        $hasMasterCustomersIndex = false;
        $hasMasterSuppliersIndex = $hasMasterItemBomsIndex = $hasMasterEmployeesIndex = false;
    }

    if (!$canModule('purchasing')) {
        $hasPoIndex = $hasPoCreate = $hasGrnIndex = $hasGrnCreate = false;
        $hasSupplierItemsIndex = false;
    }

    if (!$isAdmin && !$canModule('marketplace')) {
        $hasMarketplaceCreate = false;
        $hasMarketplaceToko = $hasMarketplaceOrders = $hasMarketplacePemenuhan = false;
        $hasMarketplacePickingBarang = $hasMarketplaceSkuMapping = $hasMarketplaceSync = false;
        $hasMarketplacePencairanDana = $hasMarketplaceProfit = $hasMarketplaceAds = false;
        $hasMarketplaceAnalytics = $hasMarketplaceIssues = false;
        $hasMarketplaceIndex = $hasMarketplaceSalesReport = false;
        $hasMarketplaceReconcileQueue = $hasMarketplaceReconcileItemsIndex = false;
    }

    if (!$canModule('imports')) {
        $hasImportMarketplaceIndex = $hasImportMarketplaceDraft = false;
        $hasImportMarketplaceIncomeIndex = $hasImportMarketplaceIncomeDraft = false;
    }

    if (!$canModule('sales')) {
        $hasSalesInvoicesIndex = $hasSalesInvoicesCreate = false;
        $hasSalesShipmentsIndex = $hasSalesShipmentsCreate = false;
        $hasSalesShipmentReturnsIndex = $hasSalesShipmentReturnsCreate = false;
        $hasSalesOperationalSettings = false;
        $hasSalesShipmentsReport = false;
        $hasSalesReportItemProfit = $hasSalesReportChannelProfit = $hasSalesReportShipmentAnalytics = false;
    }

    if (!$canModule('inventory')) {
        $hasInvIntelligence = $hasWhIntelligence = false;
        $hasInvStocksItems = $hasInvStocksLots = $hasInvStockCard = false;
        $hasInvBarcodes = false;
        $hasInvTransfersIndex = $hasInvTransfersCreate = $hasInvAdjustmentsIndex = false;
        $hasInvWipAdjIndex = $hasInvWipCutReconcile = false;
        $hasInvOpnamesIndex = $hasInvOpnamesCreate = false;
        $hasInvExternalIndex = $hasInvExternalCreate = false;
        $hasRtsStockReqIndex = $hasRtsDirectReceiveIndex = false;
    }

    if (!$canModule('production')) {
        $hasProdCuttingJobsIndex = $hasProdCuttingJobsCreate = false;
        $hasProdSewPickupsIndex = $hasProdSewPickupsCreate = false;
        $hasProdSewReturnsIndex = $hasProdSewReturnsCreate = false;
        $hasProdSewRejectReturnsIndex = false;
        $hasProdFinishingJobsIndex = $hasProdFinishingJobsCreate = $hasProdFinishingRepairsIndex = false;
        $hasProdQcIndex = $hasProdPackingIndex = false;
        $hasProdPriorityIndex = $hasProdReportsIndex = false;
        $hasProdDashboard = false;
    }

    if (!$canModule('accounting')) {
        $hasCashBasisReportIndex = $hasCashReceiptsIndex = false;
        $hasJournalsIndex = $hasAccountsIndex = $hasOpeningBalancesIndex = false;
    }

    if (!$canModule('payroll')) {
        $hasPayrollDashboard = $hasPieceworkIndex = $hasPieceRatesIndex = false;
        $hasPayrollReportsOperators = $hasPayrollReportsOperatorSlips = false;
    }

    if (!$canModule('costing')) {
        $hasHppIndex = $hasProdCostPeriodsIndex = false;
    }

    if ($isAdmin) {
        $hasMarketplaceSalesReport = false;
        $hasMarketplaceReconcileQueue = false;
        $hasMarketplaceReconcileItemsIndex = false;
        
        $hasAdminCrmDashboard = false;
        $hasAdminCrmVisitors = false;
        $hasAdminCrmOrders = false;
        $hasAdminCrmProspects = false;
        $hasAdminCrmCustomers = false;
        $hasAdminCrmSegments = false;

        $hasProdSewRejectReturnsIndex = false;

        $hasInvAdjustmentsIndex = $router->has('inventory.adjustments.index');
    }

    // OPEN STATES (match desktop)
    $masterOpen = request()->routeIs('master.*');
    $purchasingOpen = request()->routeIs('purchasing.*');
    $marketplaceOpen =
        request()->routeIs('marketplace.toko') ||
        request()->routeIs('marketplace.settings') ||
        request()->routeIs('marketplace.orders') ||
        request()->routeIs('marketplace.orders.*') ||
        request()->routeIs('marketplace.fulfillment') ||
        request()->routeIs('marketplace.fulfillment.*') ||
        request()->routeIs('marketplace.picking') ||
        request()->routeIs('marketplace.sku-mapping') ||
        request()->routeIs('marketplace.sync') ||
        request()->routeIs('marketplace.promotions') ||
        request()->routeIs('marketplace.promotions.*') ||
        request()->routeIs('marketplace.promotions.summary') ||
        request()->routeIs('marketplace.settlement') ||
        request()->routeIs('marketplace.income-detail') ||
        request()->routeIs('marketplace.profit') ||
        request()->routeIs('marketplace.reports.*') ||
        request()->routeIs('marketplace.ads') ||
        request()->routeIs('marketplace.analytics') ||
        request()->routeIs('marketplace.issues') ||
        request()->routeIs('imports.marketplace.*') ||
        request()->routeIs('imports.marketplace_income.*');

    $openAi = request()->routeIs('ai.*');
    $whatsappOpen = request()->routeIs('whatsapp.*') || request()->routeIs('settings.whatsapp.*');

    $salesInvoiceOpen = request()->routeIs('sales.invoices.*');
    $salesShipmentOpen = request()->routeIs('sales.shipments.*');
    $salesShipmentReturnOpen = request()->routeIs('sales.shipment_returns.*');
    $salesOperationalSettingsOpen = request()->routeIs('sales.settings.*');
    $salesReportOpen = request()->routeIs('sales.reports.*') || request()->routeIs('sales.reports.shipment');
    $salesOpen = $salesInvoiceOpen || $salesShipmentOpen || $salesShipmentReturnOpen || $salesOperationalSettingsOpen || $salesReportOpen;

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
    $websiteOpen = request()->routeIs('admin.catalog.*');
    $crmStorefrontOpen = request()->routeIs('admin.crm.*');

    $prodOpen =
        request()->routeIs('production.cutting_jobs.*') ||
        request()->routeIs('production.sewing.*') ||
        request()->routeIs('production.finishing_jobs.*') ||
        request()->routeIs('production.finishing_repairs.*') ||
        request()->routeIs('production.qc.*') ||
        request()->routeIs('production.dashboard') ||
        request()->routeIs('production.packing_jobs.*') ||
        request()->routeIs('production.priority.*') ||
        request()->routeIs('production.reports.*');

    $accountingOpen =
        request()->routeIs('accounting.cash-basis-report.*') ||
        request()->routeIs('accounting.cash-expenses.*') ||
        request()->routeIs('accounting.cash-receipts.*') ||
        request()->routeIs('accounting.marketplace-payouts.*') ||
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

    // Penggajian active helpers (optional)
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
                                <span class="icon">🏠</span><span>Beranda</span>
                            </a>
                        </li>
                    @endif

                    <div class="mobile-sidebar-section-label">Produksi</div>

                    @if ($hasProdCuttingJobsCreate)
                        <li>
                            <a href="{{ route('production.cutting_jobs.create') }}"
                               class="mobile-sidebar-link {{ request()->routeIs('production.cutting_jobs.create') ? 'active' : '' }}">
                                <span class="icon">✂️</span><span>Potong Baru</span>
                            </a>
                        </li>
                    @endif

                    @if ($hasProdSewPickupsCreate)
                        <li>
                            <a href="{{ route('production.sewing.pickups.create') }}"
                               class="mobile-sidebar-link {{ request()->routeIs('production.sewing.pickups.create') ? 'active' : '' }}">
                                <span class="icon">📤</span><span>Ambil Jahit Baru</span>
                            </a>
                        </li>
                    @endif

                    @if ($hasProdSewReturnsCreate)
                        <li>
                            <a href="{{ route('production.sewing.returns.create') }}"
                               class="mobile-sidebar-link {{ request()->routeIs('production.sewing.returns.create') ? 'active' : '' }}">
                                <span class="icon">📥</span><span>Setoran Jahit Baru</span>
                            </a>
                        </li>
                    @endif

                    @if ($hasProdFinishingJobsCreate)
                        <li>
                            <a href="{{ route('production.finishing_jobs.create') }}"
                               class="mobile-sidebar-link {{ request()->routeIs('production.finishing_jobs.create') ? 'active' : '' }}">
                                <span class="icon">🧶</span><span>Finishing Baru</span>
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
                                <span class="icon">🏠</span><span>Beranda</span>
                            </a>
                        </li>
                    @endif

                    @if (($hasAiIndexRoute || $hasAiAgentRoute) && $canOpenAiAgent)
                        <div class="mobile-sidebar-section-label">AI</div>
                        @if ($hasAiIndexRoute)
                            <li>
                                <a href="{{ route('ai.index') }}"
                                   class="mobile-sidebar-link {{ request()->routeIs('ai.index') ? 'active' : '' }}">
                                    <span class="icon">✨</span><span>AI Studio</span>
                                </a>
                            </li>
                        @endif
                        @if ($hasAiOpenAiRoute)
                            <li>
                                <a href="{{ route('ai.openai.index') }}"
                                   class="mobile-sidebar-link {{ request()->routeIs('ai.openai.*') ? 'active' : '' }}">
                                    <span class="icon">🔌</span><span>Connect OpenAI</span>
                                </a>
                            </li>
                        @endif
                        @if ($hasAiAgentRoute)
                            <li>
                                <a href="{{ route('ai.agent') }}"
                                   class="mobile-sidebar-link {{ $openAi ? 'active' : '' }}">
                                    <span class="icon">💬</span><span>AI Agent</span>
                                </a>
                            </li>
                        @endif
                    @endif

                    @if ($isAdmin && ($hasAdminCrmDashboard || $hasAdminCrmOrders || $hasAdminCrmCustomers || $hasAdminCrmProspects || $hasAdminCrmVisitors || $hasAdminCrmSegments))
                        <div class="mobile-sidebar-section-label">CRM Storefront</div>
                        <li class="mb-1">
                            <button class="mobile-sidebar-link mobile-sidebar-toggle {{ $crmStorefrontOpen ? 'is-open' : '' }}"
                                    type="button"
                                    data-bs-toggle="collapse"
                                    data-bs-target="#navCrmAdminMobile"
                                    aria-expanded="{{ $crmStorefrontOpen ? 'true' : 'false' }}"
                                    aria-controls="navCrmAdminMobile">
                                <span class="icon">📈</span><span>Pesanan & Customer</span><span class="chevron">▸</span>
                            </button>

                            <div class="collapse {{ $crmStorefrontOpen ? 'show' : '' }}" id="navCrmAdminMobile">
                                @if ($hasAdminCrmDashboard)
                                    <a href="{{ route('admin.crm.dashboard') }}"
                                       class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('admin.crm.dashboard') ? 'active' : '' }}">
                                        <span class="icon">📊</span><span>Dasbor</span>
                                    </a>
                                @endif

                                @if ($hasAdminCrmOrders)
                                    <a href="{{ route('admin.crm.orders') }}"
                                       class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('admin.crm.orders*') ? 'active' : '' }}">
                                        <span class="icon">🛒</span><span>Pesanan Website</span>
                                    </a>
                                @endif

                                @if ($hasAdminCrmCustomers)
                                    <a href="{{ route('admin.crm.customers') }}"
                                       class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('admin.crm.customers*') ? 'active' : '' }}">
                                        <span class="icon">👥</span><span>Pelanggan</span>
                                    </a>
                                @endif

                                @if ($hasAdminCrmProspects)
                                    <a href="{{ route('admin.crm.prospects') }}"
                                       class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('admin.crm.prospects*') ? 'active' : '' }}">
                                        <span class="icon">📝</span><span>Prospek</span>
                                    </a>
                                @endif

                                @if ($hasAdminCrmVisitors)
                                    <a href="{{ route('admin.crm.visitors') }}"
                                       class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('admin.crm.visitors*') ? 'active' : '' }}">
                                        <span class="icon">👣</span><span>Pengunjung</span>
                                    </a>
                                @endif

                                @if ($hasAdminCrmSegments)
                                    <a href="{{ route('admin.crm.segments') }}"
                                       class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('admin.crm.segments*') ? 'active' : '' }}">
                                        <span class="icon">🔖</span><span>Segmen</span>
                                    </a>
                                @endif
                            </div>
                        </li>
                    @endif

                    @if ($isAdmin && ($hasMarketplaceIndex || $hasMarketplaceSalesReport || $hasMarketplaceReconcileQueue || $hasMarketplaceReconcileItemsIndex))
                        <div class="mobile-sidebar-section-label">Toko Online</div>
                        <li class="mb-1">
                            <button class="mobile-sidebar-link mobile-sidebar-toggle {{ $marketplaceOpen ? 'is-open' : '' }}"
                                    type="button"
                                    data-bs-toggle="collapse"
                                    data-bs-target="#navMarketplaceAdminMobile"
                                    aria-expanded="{{ $marketplaceOpen ? 'true' : 'false' }}"
                                    aria-controls="navMarketplaceAdminMobile">
                                <span class="icon">🛒</span><span>Toko Online</span><span class="chevron">▸</span>
                            </button>

                            <div class="collapse {{ $marketplaceOpen ? 'show' : '' }}" id="navMarketplaceAdminMobile">
                                @if ($hasMarketplaceIndex)
                                    <a href="{{ route('marketplace.orders') }}"
                                       class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('marketplace.orders') || request()->routeIs('marketplace.orders.*') ? 'active' : '' }}">
                                        <span class="icon">📋</span><span>Pesanan</span>
                                    </a>
                                @endif

                                @if ($router->has('marketplace.products'))
                                    <a href="{{ route('marketplace.products') }}"
                                       class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('marketplace.products') ? 'active' : '' }}">
                                        <span class="icon">🏷</span><span>Produk</span>
                                    </a>
                                @endif

                                @if ($hasMarketplacePromotions)
                                    <a href="{{ route('marketplace.promotions') }}"
                                       class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('marketplace.promotions') ? 'active' : '' }}">
                                        <span class="icon">🏷️</span><span>Promosi</span>
                                    </a>
                                @endif

                                @if ($hasMarketplacePromotionsSummary)
                                    <a href="{{ route('marketplace.promotions.summary') }}"
                                       class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('marketplace.promotions.summary') ? 'active' : '' }}">
                                        <span class="icon">📊</span><span>Summary Promosi</span>
                                    </a>
                                @endif

                                @if ($router->has('marketplace.chat') && $canModule('marketplace'))
                                    <a href="{{ route('marketplace.chat') }}"
                                       class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('marketplace.chat') ? 'active' : '' }}">
                                        <span class="icon">💬</span><span>Chat <span class="sidebarChatBadge badge bg-danger rounded-pill ms-2" style="display:none; font-size:.65rem; padding:.2rem .4rem;"></span></span>
                                    </a>
                                @endif

                                @if ($hasMarketplaceSalesReport)
                                    <a href="{{ route('marketplace.reports.sales') }}"
                                       class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('marketplace.reports.sales') ? 'active' : '' }}">
                                        <span class="icon">📈</span><span>Ringkasan Penjualan</span>
                                    </a>
                                @endif

                                @if ($hasMarketplaceReconcileQueue)
                                    <a href="{{ route('marketplace.reconcile.queue') }}"
                                       class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('marketplace.reconcile.*') || request()->routeIs('marketplace.reconciliations.*') ? 'active' : '' }}">
                                        <span class="icon">🧩</span><span>Antrean Rekonsiliasi</span>
                                    </a>
                                @endif

                                @if ($hasMarketplaceReconcileItemsIndex)
                                    <a href="{{ route('marketplace.reconcile.items') }}"
                                       class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('marketplace.reconcile.items*') ? 'active' : '' }}">
                                        <span class="icon">🧾</span><span>Pencocokan Barang</span>
                                    </a>
                                @endif
                            </div>
                        </li>
                    @endif

                    <div class="mobile-sidebar-section-label">Persediaan</div>

                    <li class="mb-1">
                        <button class="mobile-sidebar-link mobile-sidebar-toggle {{ $invOpen ? 'is-open' : '' }}"
                                type="button"
                                data-bs-toggle="collapse"
                                data-bs-target="#navPersediaanAdminMobile"
                                aria-expanded="{{ $invOpen ? 'true' : 'false' }}"
                                aria-controls="navPersediaanAdminMobile">
                            <span class="icon">📦</span><span>Persediaan</span><span class="chevron">▸</span>
                        </button>

                        <div class="collapse {{ $invOpen ? 'show' : '' }}" id="navPersediaanAdminMobile">
                            <div class="mobile-sidebar-section-label" style="margin-top:.55rem;">Stok</div>

                            @if ($hasInvStocksItems)
                                <a href="{{ route('inventory.stocks.items') }}"
                                   class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('inventory.stocks.items') ? 'active' : '' }}">
                                    <span class="icon">📦</span><span>Stok Barang</span>
                                </a>
                            @endif

                            @if ($hasInvStockCard)
                                <a href="{{ route('inventory.stock_card.index') }}"
                                   class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('inventory.stock_card.*') ? 'active' : '' }}">
                                    <span class="icon">📋</span><span>Kartu Stok</span>
                                </a>
                            @endif

                            <div class="mobile-sidebar-section-label" style="margin-top:.55rem;">Operasional</div>

                            @if ($hasInvOpnamesIndex)
                                <a href="{{ route('inventory.stock_opnames.index') }}"
                                   class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('inventory.stock_opnames.*') ? 'active' : '' }}">
                                    <span class="icon">📊</span><span>Stok Opname</span>
                                </a>
                            @endif

                            @if ($isAdmin && $hasInvTransfersIndex)
                                <a href="{{ route('inventory.transfers.index') }}"
                                   class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('inventory.transfers.index') ? 'active' : '' }}">
                                    <span class="icon">🔁</span><span>Daftar Transfer</span>
                                </a>
                            @endif

                            @if ($isAdmin && $hasInvTransfersCreate)
                                <a href="{{ route('inventory.transfers.create') }}"
                                   class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('inventory.transfers.create') ? 'active' : '' }}">
                                    <span class="icon">➕</span><span>Buat Transfer</span>
                                </a>
                            @endif

                            @if ($hasInvAdjustmentsIndex)
                                <a href="{{ route('inventory.adjustments.index') }}"
                                   class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('inventory.adjustments.*') ? 'active' : '' }}">
                                    <span class="icon">📝</span><span>Koreksi Persediaan</span>
                                </a>
                            @endif

                            @if (!$isAdmin && $hasInvWipAdjIndex)
                                <a href="{{ route('inventory.wip_adjustments.index') }}"
                                   class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('inventory.wip_adjustments.*') ? 'active' : '' }}">
                                    <span class="icon">🧾</span><span>Koreksi WIP</span>
                                </a>
                            @endif
                        </div>
                    </li>

                    {{-- RTS (view for operating, manage for admin/owner) --}}
                    @if ($canViewRts && ($hasRtsStockReqIndex || (!$isAdmin && $hasRtsDirectReceiveIndex)))
                        <div class="mobile-sidebar-section-label">Permintaan Stok</div>

                        <li class="mb-1">
                            <button class="mobile-sidebar-link mobile-sidebar-toggle {{ $stockReqOpen ? 'is-open' : '' }}"
                                    type="button"
                                    data-bs-toggle="collapse"
                                    data-bs-target="#navStockRequestsAdminMobile"
                                    aria-expanded="{{ $stockReqOpen ? 'true' : 'false' }}"
                                    aria-controls="navStockRequestsAdminMobile">
                                <span class="icon">🛒</span><span>Permintaan Stok</span><span class="chevron">▸</span>
                            </button>
                            <div class="collapse {{ $stockReqOpen ? 'show' : '' }}" id="navStockRequestsAdminMobile">
                                @if ($hasRtsStockReqIndex)
                                    <a href="{{ route('rts.stock-requests.index') }}"
                                       class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('rts.stock-requests.*') ? 'active' : '' }}"
                                       @if($canManageRts && $hasRtsNeedReceive) title="{{ $rtsBadgeTitle }}" @endif>
                                        <span class="icon">🛒</span><span>Permintaan Stok (RTS)</span>
                                        @if($canManageRts && $hasRtsNeedReceive)
                                            <span class="ms-dot" aria-hidden="true"></span>
                                        @endif
                                    </a>
                                @endif

                                @if ($isAdmin && $hasRtsDirectReceiveIndex)
                                    <a href="{{ route('rts.direct-receives.index') }}"
                                       class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('rts.direct-receives.*') ? 'active' : '' }}">
                                        <span class="icon">⚡</span><span>Setor Jahit Dadakan</span>
                                    </a>
                                @endif

                                @if (!$isAdmin && $canManageRts && $hasRtsDirectReceiveIndex)
                                    <a href="{{ route('rts.direct-receives.index') }}"
                                       class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('rts.direct-receives.*') ? 'active' : '' }}">
                                        <span class="icon">⚡</span><span>RTS Dadakan</span>
                                    </a>
                                @endif
                            </div>
                        </li>
                    @endif

                    {{-- Sales (admin) --}}
                    @if ($isAdmin)
                        @if ($hasSalesShipmentsIndex || $hasSalesShipmentReturnsIndex)
                            <div class="mobile-sidebar-section-label">Penjualan</div>
                            <li class="mb-1">
                                <button class="mobile-sidebar-link mobile-sidebar-toggle {{ $salesOpen ? 'is-open' : '' }}"
                                        type="button"
                                        data-bs-toggle="collapse"
                                        data-bs-target="#navSalesAdminMobile"
                                        aria-expanded="{{ $salesOpen ? 'true' : 'false' }}"
                                        aria-controls="navSalesAdminMobile">
                                    <span class="icon">🚚</span><span>Penjualan</span><span class="chevron">▸</span>
                                </button>
                                <div class="collapse {{ $salesOpen ? 'show' : '' }}" id="navSalesAdminMobile">
                                    @if ($hasSalesShipmentsIndex)
                                        <a href="{{ route('sales.shipments.index') }}"
                                           class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('sales.shipments.*') ? 'active' : '' }}">
                                            <span class="icon">🚚</span><span>Pengiriman</span>
                                        </a>
                                    @endif

                                    @if ($hasSalesShipmentReturnsIndex)
                                        <a href="{{ route('sales.shipment_returns.index') }}"
                                           class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('sales.shipment_returns.*') ? 'active' : '' }}">
                                            <span class="icon">↩️</span><span>Retur Pengiriman</span>
                                        </a>
                                    @endif
                                </div>
                            </li>
                        @endif

                        @if ($hasPoIndex || $hasPoCreate || $hasGrnIndex || $hasGrnCreate)
                            <div class="mobile-sidebar-section-label">Pengadaan</div>
                            <li class="mb-1">
                                <button class="mobile-sidebar-link mobile-sidebar-toggle {{ $purchasingOpen ? 'is-open' : '' }}"
                                        type="button"
                                        data-bs-toggle="collapse"
                                        data-bs-target="#navPurchasingAdminMobile"
                                        aria-expanded="{{ $purchasingOpen ? 'true' : 'false' }}"
                                        aria-controls="navPurchasingAdminMobile">
                                    <span class="icon">🧾</span><span>Pengadaan</span><span class="chevron">▸</span>
                                </button>
                                <div class="collapse {{ $purchasingOpen ? 'show' : '' }}" id="navPurchasingAdminMobile">
                                    @if ($hasPoIndex)
                                        <a href="{{ route('purchasing.purchase_orders.index') }}"
                                           class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('purchasing.purchase_orders.index') ? 'active' : '' }}">
                                            <span class="icon">🧾</span><span>Daftar PO</span>
                                        </a>
                                    @endif

                                    @if ($hasPoCreate)
                                        <a href="{{ route('purchasing.purchase_orders.create') }}"
                                           class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('purchasing.purchase_orders.create') ? 'active' : '' }}">
                                            <span class="icon">＋</span><span>PO Baru</span>
                                        </a>
                                    @endif

                                    @if ($hasGrnIndex)
                                        <a href="{{ route('purchasing.purchase_receipts.index') }}"
                                           class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('purchasing.purchase_receipts.index') ? 'active' : '' }}">
                                            <span class="icon">🧾</span><span>Daftar Penerimaan</span>
                                        </a>
                                    @endif

                                    @if ($hasGrnCreate)
                                        <a href="{{ route('purchasing.purchase_receipts.create') }}"
                                           class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('purchasing.purchase_receipts.create') ? 'active' : '' }}">
                                            <span class="icon">＋</span><span>Penerimaan Baru</span>
                                        </a>
                                    @endif
                                </div>
                            </li>
                        @endif
                    @endif

                    {{-- Produksi (admin) --}}
                    @if ($isAdmin && ($hasProdSewReturnsCreate || $hasProdSewRejectReturnsIndex || $hasProdQcIndex))
                        <div class="mobile-sidebar-section-label">Produksi</div>
                        <li class="mb-1">
                            <button class="mobile-sidebar-link mobile-sidebar-toggle {{ $prodOpen ? 'is-open' : '' }}"
                                    type="button"
                                    data-bs-toggle="collapse"
                                    data-bs-target="#navProductionAdminMobile"
                                    aria-expanded="{{ $prodOpen ? 'true' : 'false' }}"
                                    aria-controls="navProductionAdminMobile">
                                <span class="icon">⚙️</span><span>Produksi</span><span class="chevron">▸</span>
                            </button>
                            <div class="collapse {{ $prodOpen ? 'show' : '' }}" id="navProductionAdminMobile">
                                @if ($hasProdSewReturnsCreate)
                                    <a href="{{ route('production.sewing.returns.create') }}"
                                       class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('production.sewing.returns.*') ? 'active' : '' }}">
                                        <span class="icon">📥</span><span>Setor Jahit</span>
                                    </a>
                                @endif

                                @if ($hasProdSewRejectReturnsIndex)
                                    <a href="{{ route('production.sewing.reject_returns.index') }}"
                                       class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('production.sewing.reject_returns.*') ? 'active' : '' }}">
                                        <span class="icon">♻️</span><span>Setor Reject Jahit</span>
                                    </a>
                                @endif

                                @if ($hasProdQcIndex)
                                    <a href="{{ $prodQcHref }}"
                                       class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('production.qc.*') ? 'active' : '' }}">
                                        <span class="icon">✅</span><span>{{ $prodQcLabel }}</span>
                                    </a>
                                @endif
                            </div>
                        </li>

                    @endif

                    {{-- Produksi (operating) --}}
                    @if ($isOperating)
                        <div class="mobile-sidebar-section-label">Produksi</div>

                        <li class="mb-1">
                            <button class="mobile-sidebar-link mobile-sidebar-toggle {{ $prodOpen ? 'is-open' : '' }}"
                                    type="button"
                                    data-bs-toggle="collapse"
                                    data-bs-target="#navProductionOperatingMobile"
                                    aria-expanded="{{ $prodOpen ? 'true' : 'false' }}"
                                    aria-controls="navProductionOperatingMobile">
                                <span class="icon">⚙️</span><span>Produksi</span><span class="chevron">▸</span>
                            </button>
                            <div class="collapse {{ $prodOpen ? 'show' : '' }}" id="navProductionOperatingMobile">

                        @if ($hasProdDashboard)
                            <li>
                                <a href="{{ route('production.dashboard') }}"
                                   class="mobile-sidebar-link {{ request()->routeIs('production.dashboard') ? 'active' : '' }}">
                                    <span class="icon"><i class="bi bi-bar-chart"></i></span><span>Beranda Produksi</span>
                                </a>
                            </li>
                        @endif

                        @if ($hasProdCuttingJobsIndex)
                            <li>
                                <a href="{{ route('production.cutting_jobs.index') }}"
                                   class="mobile-sidebar-link {{ request()->routeIs('production.cutting_jobs.*') ? 'active' : '' }}">
                                    <span class="icon"><i class="bi bi-scissors"></i></span><span>Pekerjaan Potong</span>
                                </a>
                            </li>
                        @endif

                        @if ($hasProdSewPickupsIndex)
                            <li>
                                <a href="{{ route('production.sewing.pickups.index') }}"
                                   class="mobile-sidebar-link {{ request()->routeIs('production.sewing.pickups.*') ? 'active' : '' }}">
                                    <span class="icon"><i class="bi bi-send"></i></span><span>Ambil Jahit</span>
                                </a>
                            </li>
                        @endif

                        @if ($hasProdSewReturnsIndex)
                            <li>
                                <a href="{{ route('production.sewing.returns.index') }}"
                                   class="mobile-sidebar-link {{ request()->routeIs('production.sewing.returns.*') ? 'active' : '' }}">
                                    <span class="icon"><i class="bi bi-inbox"></i></span><span>Setoran Jahit</span>
                                </a>
                            </li>
                        @endif

                        @if ($hasProdSewRejectReturnsIndex)
                            <li>
                                <a href="{{ route('production.sewing.reject_returns.index') }}"
                                   class="mobile-sidebar-link {{ request()->routeIs('production.sewing.reject_returns.*') ? 'active' : '' }}">
                                    <span class="icon"><i class="bi bi-arrow-clockwise"></i></span><span>Setor Reject Jahit</span>
                                </a>
                            </li>
                        @endif

                        @if ($hasProdFinishingJobsIndex)
                            <li>
                                <a href="{{ route('production.finishing_jobs.index') }}"
                                   class="mobile-sidebar-link {{ request()->routeIs('production.finishing_jobs.*') ? 'active' : '' }}">
                                    <span class="icon"><i class="bi bi-patch-check"></i></span><span>Pekerjaan Finishing</span>
                                </a>
                            </li>
                        @endif

                        @if ($hasProdFinishingRepairsIndex)
                            <li>
                                <a href="{{ route('production.finishing_repairs.index') }}"
                                   class="mobile-sidebar-link {{ request()->routeIs('production.finishing_repairs.*') ? 'active' : '' }}">
                                    <span class="icon"><i class="bi bi-tools"></i></span><span>Perbaikan Finishing</span>
                                </a>
                            </li>
                        @endif

                        @if ($hasProdQcIndex)
                            <li>
                                <a href="{{ $prodQcHref }}"
                                   class="mobile-sidebar-link {{ request()->routeIs('production.qc.*') ? 'active' : '' }}">
                                    <span class="icon"><i class="bi bi-check-circle"></i></span><span>{{ $prodQcLabel }}</span>
                                </a>
                            </li>
                        @endif

                        @if ($hasProdPackingIndex)
                            <li>
                                <a href="{{ route('production.packing_jobs.index') }}"
                                   class="mobile-sidebar-link {{ request()->routeIs('production.packing_jobs.*') ? 'active' : '' }}">
                                    <span class="icon"><i class="bi bi-box-seam"></i></span><span>Packing</span>
                                </a>
                            </li>
                        @endif

                        @if ($hasProdPriorityIndex)
                            <li>
                                <a href="{{ route('production.priority.index') }}"
                                   class="mobile-sidebar-link {{ request()->routeIs('production.priority.*') ? 'active' : '' }}">
                                    <span class="icon"><i class="bi bi-ui-checks"></i></span><span>Prioritas Produksi</span>
                                </a>
                            </li>
                        @endif

                        @if ($hasProdReportsIndex)
                            <li>
                                <a href="{{ route('production.reports.index') }}"
                                   class="mobile-sidebar-link {{ request()->routeIs('production.reports.*') ? 'active' : '' }}">
                                    <span class="icon"><i class="bi bi-graph-up-arrow"></i></span><span>Laporan Produksi</span>
                                </a>
                            </li>
                        @endif
                            </div>
                        </li>
                    @endif

                    {{-- Komunikasi/WhatsApp tidak ditampilkan untuk admin. --}}

                    {{-- MASTER ITEM (admin) --}}
                    @if ($isAdmin && $hasMasterItemsIndex)
                        <div class="mobile-sidebar-section-label">Data Master</div>
                        <li>
                            <a href="{{ route('master.items.index') }}"
                               class="mobile-sidebar-link {{ request()->routeIs('master.items.*') ? 'active' : '' }}">
                                <span class="icon">📦</span><span>Master Item</span>
                            </a>
                        </li>
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
                                <span class="icon">🏠</span><span>Beranda</span>
                            </a>
                        </li>
                    @endif

                    @if (($hasAiIndexRoute || $hasAiAgentRoute) && $canOpenAiAgent)
                        <div class="mobile-sidebar-section-label">AI</div>
                        @if ($hasAiIndexRoute)
                            <li>
                                <a href="{{ route('ai.index') }}"
                                   class="mobile-sidebar-link {{ request()->routeIs('ai.index') ? 'active' : '' }}">
                                    <span class="icon">✨</span><span>AI Studio</span>
                                </a>
                            </li>
                        @endif
                        @if ($hasAiAgentRoute)
                            <li>
                                <a href="{{ route('ai.agent') }}"
                                   class="mobile-sidebar-link {{ $openAi ? 'active' : '' }}">
                                    <span class="icon">💬</span><span>AI Agent</span>
                                </a>
                            </li>
                        @endif
                    @endif

                    @if ($hasOwnerAccessControl)
                        <li>
                            <a href="{{ route('owner.access-control.index') }}"
                               class="mobile-sidebar-link {{ request()->routeIs('owner.access-control.*') ? 'active' : '' }}">
                                <span class="icon">🔐</span><span>Akses Login</span>
                            </a>
                        </li>
                    @endif

                    @if ($router->has('settings.system.index'))
                        <li>
                            <a href="{{ route('settings.system.index') }}"
                               class="mobile-sidebar-link {{ request()->routeIs('settings.system.*') ? 'active' : '' }}">
                                <span class="icon">⚙️</span><span>Pengaturan Sistem</span>
                            </a>
                        </li>
                    @endif
                    @if ($router->has('whatsapp.index') || $router->has('settings.whatsapp.index'))
                        <div class="mobile-sidebar-section-label">Komunikasi</div>
                        <li class="mb-1">
                            <button class="mobile-sidebar-link mobile-sidebar-toggle {{ $whatsappOpen ? 'is-open' : '' }}"
                                    type="button"
                                    data-bs-toggle="collapse"
                                    data-bs-target="#navCommunicationOwnerMobile"
                                    aria-expanded="{{ $whatsappOpen ? 'true' : 'false' }}"
                                    aria-controls="navCommunicationOwnerMobile">
                                <span class="icon">💬</span><span>WhatsApp</span><span class="chevron">▸</span>
                            </button>
                            <div class="collapse {{ $whatsappOpen ? 'show' : '' }}" id="navCommunicationOwnerMobile">
                                @if ($router->has('whatsapp.index'))
                                    <a href="{{ route('whatsapp.index') }}"
                                       class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('whatsapp.*') ? 'active' : '' }}">
                                        <span class="icon">📨</span><span>WhatsApp Center</span>
                                    </a>
                                @endif
                                @if ($router->has('settings.whatsapp.index'))
                                    <a href="{{ route('settings.whatsapp.index') }}"
                                       class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('settings.whatsapp.*') ? 'active' : '' }}">
                                        <span class="icon">⚙️</span><span>Pengaturan WhatsApp</span>
                                    </a>
                                @endif
                            </div>
                        </li>
                    @endif


                    @if ($hasAdminCatalogProducts || $hasAdminCatalogCategories)
                        <div class="mobile-sidebar-section-label">Website</div>
                        <li class="mb-1">
                            <button class="mobile-sidebar-link mobile-sidebar-toggle {{ $websiteOpen ? 'is-open' : '' }}"
                                    type="button"
                                    data-bs-toggle="collapse"
                                    data-bs-target="#navWebsiteOwnerMobile"
                                    aria-expanded="{{ $websiteOpen ? 'true' : 'false' }}"
                                    aria-controls="navWebsiteOwnerMobile">
                                <span class="icon">🛍️</span><span>Produk Website</span><span class="chevron">▸</span>
                            </button>

                            <div class="collapse {{ $websiteOpen ? 'show' : '' }}" id="navWebsiteOwnerMobile">
                                @if ($hasAdminCatalogProducts)
                                    <a href="{{ route('admin.catalog.products.index') }}"
                                       class="mobile-sidebar-link mobile-sidebar-link-sub {{ (request()->routeIs('admin.catalog.products.index') || request()->routeIs('admin.catalog.products.edit') || request()->routeIs('admin.catalog.products.create')) ? 'active' : '' }}">
                                        <span class="icon">📦</span><span>Katalog Produk</span>
                                    </a>
                                @endif

                                @if ($router->has('admin.catalog.products.ranking'))
                                    <a href="{{ route('admin.catalog.products.ranking') }}"
                                       class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('admin.catalog.products.ranking') ? 'active' : '' }}">
                                        <span class="icon">📊</span><span>Ranking Produk</span>
                                    </a>
                                @endif

                                @if ($router->has('admin.website.settings'))
                                    <a href="{{ route('admin.website.settings') }}"
                                       class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('admin.website.settings') ? 'active' : '' }}">
                                        <span class="icon">⚙️</span><span>Pengaturan Website</span>
                                    </a>
                                @endif

                                @if ($hasAdminCatalogCategories)
                                    <a href="{{ route('admin.catalog.categories.index') }}"
                                       class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('admin.catalog.categories*') ? 'active' : '' }}">
                                        <span class="icon">🏷️</span><span>Kategori Produk</span>
                                    </a>
                                @endif
                            </div>
                        </li>
                    @endif

                    @if ($hasAdminCrmDashboard || $hasAdminCrmOrders || $hasAdminCrmCustomers || $hasAdminCrmProspects || $hasAdminCrmVisitors || $hasAdminCrmSegments)
                        <div class="mobile-sidebar-section-label">CRM Storefront</div>
                        <li class="mb-1">
                            <button class="mobile-sidebar-link mobile-sidebar-toggle {{ $crmStorefrontOpen ? 'is-open' : '' }}"
                                    type="button"
                                    data-bs-toggle="collapse"
                                    data-bs-target="#navCrmOwnerMobile"
                                    aria-expanded="{{ $crmStorefrontOpen ? 'true' : 'false' }}"
                                    aria-controls="navCrmOwnerMobile">
                                <span class="icon">📈</span><span>Pesanan & Customer</span><span class="chevron">▸</span>
                            </button>

                            <div class="collapse {{ $crmStorefrontOpen ? 'show' : '' }}" id="navCrmOwnerMobile">
                                @if ($hasAdminCrmDashboard)
                                    <a href="{{ route('admin.crm.dashboard') }}"
                                       class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('admin.crm.dashboard') ? 'active' : '' }}">
                                        <span class="icon">📊</span><span>Dashboard</span>
                                    </a>
                                @endif

                                @if ($hasAdminCrmOrders)
                                    <a href="{{ route('admin.crm.orders') }}"
                                       class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('admin.crm.orders*') ? 'active' : '' }}">
                                        <span class="icon">🛒</span><span>Pesanan Website</span>
                                    </a>
                                @endif

                                @if ($hasAdminCrmCustomers)
                                    <a href="{{ route('admin.crm.customers') }}"
                                       class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('admin.crm.customers*') ? 'active' : '' }}">
                                        <span class="icon">👥</span><span>Pelanggan</span>
                                    </a>
                                @endif

                                @if ($hasAdminCrmProspects)
                                    <a href="{{ route('admin.crm.prospects') }}"
                                       class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('admin.crm.prospects*') ? 'active' : '' }}">
                                        <span class="icon">📝</span><span>Prospek</span>
                                    </a>
                                @endif

                                @if ($hasAdminCrmVisitors)
                                    <a href="{{ route('admin.crm.visitors') }}"
                                       class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('admin.crm.visitors*') ? 'active' : '' }}">
                                        <span class="icon">👣</span><span>Pengunjung</span>
                                    </a>
                                @endif

                                @if ($hasAdminCrmSegments)
                                    <a href="{{ route('admin.crm.segments') }}"
                                       class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('admin.crm.segments*') ? 'active' : '' }}">
                                        <span class="icon">🔖</span><span>Segmen</span>
                                    </a>
                                @endif
                            </div>
                        </li>
                    @endif

                    {{-- MASTER DATA --}}
                    <div class="mobile-sidebar-section-label">Data Master</div>
                    <li class="mb-1">
                        <button class="mobile-sidebar-link mobile-sidebar-toggle {{ $masterOpen ? 'is-open' : '' }}"
                                type="button"
                                data-bs-toggle="collapse"
                                data-bs-target="#navMasterMobile"
                                aria-expanded="{{ $masterOpen ? 'true' : 'false' }}"
                                aria-controls="navMasterMobile">
                            <span class="icon">🗂️</span><span>Data Master</span><span class="chevron">▸</span>
                        </button>

                        <div class="collapse {{ $masterOpen ? 'show' : '' }}" id="navMasterMobile">
                            @if ($hasMasterItemsIndex)
                                <a href="{{ route('master.items.index') }}"
                                   class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('master.items.*') ? 'active' : '' }}">
                                    <span class="icon">📦</span><span>Barang</span>
                                </a>
                            @endif

                            @if ($router->has('master.item_categories.index'))
                                <a href="{{ route('master.item_categories.index') }}"
                                   class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('master.item_categories.*') ? 'active' : '' }}">
                                    <span class="icon">🗂️</span><span>Kategori Barang</span>
                                </a>
                            @endif

                            @if ($hasMasterItemBomsIndex)
                                <a href="{{ route('master.item_boms.index') }}"
                                   class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('master.item_boms.*') ? 'active' : '' }}">
                                    <span class="icon">🧾</span><span>BOM / Resep</span>
                                </a>
                            @endif

                            @if ($hasMasterSuppliersIndex)
                                <a href="{{ route('master.suppliers.index') }}"
                                   class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('master.suppliers.*') ? 'active' : '' }}">
                                    <span class="icon">🏷️</span><span>Pemasok</span>
                                </a>
                            @endif

                            @if ($hasMasterCustomersIndex)
                                <a href="{{ route('master.customers.index') }}"
                                   class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('master.customers.*') ? 'active' : '' }}">
                                    <span class="icon">👤</span><span>Pelanggan</span>
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
                    @if ($hasPurchasingDashboard || $hasPrIndex || $hasMaterialShortageIndex || $hasSupplierItemsIndex || $hasPoIndex || $hasPoCreate || $hasGrnIndex || $hasGrnCreate || $hasPurchaseReturnIndex || $hasSupplierInvoiceIndex || $hasPurchasePaymentsIndex)
                        <div class="mobile-sidebar-section-label">Pengadaan</div>
                        <li class="mb-1">
                            <button class="mobile-sidebar-link mobile-sidebar-toggle {{ $purchasingOpen ? 'is-open' : '' }}"
                                    type="button"
                                    data-bs-toggle="collapse"
                                    data-bs-target="#navPembelianMobile"
                                    aria-expanded="{{ $purchasingOpen ? 'true' : 'false' }}"
                                    aria-controls="navPembelianMobile">
                                <span class="icon">🧾</span><span>Pengadaan</span><span class="chevron">▸</span>
                            </button>

                            <div class="collapse {{ $purchasingOpen ? 'show' : '' }}" id="navPembelianMobile">
                                @if ($hasPurchasingDashboard)
                                    <div class="mobile-sidebar-section-label" style="margin-top:.55rem;">Ringkasan</div>
                                    <a href="{{ route('purchasing.dashboard') }}"
                                       class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('purchasing.dashboard') ? 'active' : '' }}">
                                        <span class="icon">📊</span><span>Beranda</span>
                                    </a>
                                @endif

                                @if ($hasPrIndex || $hasMaterialShortageIndex || $hasSupplierItemsIndex)
                                    <div class="mobile-sidebar-section-label" style="margin-top:.55rem;">Permintaan Pembelian</div>
                                    @if ($hasMaterialShortageIndex)
                                        <a href="{{ route('purchasing.material_shortages.index') }}"
                                           class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('purchasing.material_shortages.*') ? 'active' : '' }}">
                                            <span class="icon">⚠️</span><span>Kekurangan Bahan</span>
                                        </a>
                                    @endif
                                    @if ($hasPrIndex)
                                        <a href="{{ route('purchasing.purchase_requests.index') }}"
                                           class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('purchasing.purchase_requests.*') ? 'active' : '' }}">
                                            <span class="icon">📋</span><span>Permintaan Beli</span>
                                        </a>
                                    @endif
                                    @if ($hasSupplierItemsIndex)
                                        <a href="{{ route('purchasing.supplier_items.index') }}"
                                           class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('purchasing.supplier_items.*') ? 'active' : '' }}">
                                            <span class="icon">≡</span><span>Daftar Pemasok</span>
                                        </a>
                                    @endif
                                @endif

                                @if ($hasPoIndex || $hasPoCreate)
                                    <div class="mobile-sidebar-section-label" style="margin-top:.55rem;">Purchase Order</div>
                                    @if ($hasPoIndex)
                                        <a href="{{ route('purchasing.purchase_orders.index') }}"
                                           class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('purchasing.purchase_orders.index') ? 'active' : '' }}">
                                            <span class="icon">≡</span><span>Daftar PO</span>
                                        </a>
                                    @endif
                                    @if ($hasPoCreate)
                                        <a href="{{ route('purchasing.purchase_orders.create') }}"
                                           class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('purchasing.purchase_orders.create') ? 'active' : '' }}">
                                            <span class="icon">＋</span><span>Buat PO</span>
                                        </a>
                                    @endif
                                @endif

                                @if ($hasGrnIndex || $hasGrnCreate || $hasPurchaseReturnIndex)
                                    <div class="mobile-sidebar-section-label" style="margin-top:.55rem;">Penerimaan Barang (GRN)</div>
                                    @if ($hasGrnIndex)
                                        <a href="{{ route('purchasing.purchase_receipts.index') }}"
                                           class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('purchasing.purchase_receipts.index') ? 'active' : '' }}">
                                            <span class="icon">≡</span><span>Daftar Penerimaan</span>
                                        </a>
                                    @endif
                                    @if ($hasGrnCreate)
                                        <a href="{{ route('purchasing.purchase_receipts.create') }}"
                                           class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('purchasing.purchase_receipts.create') ? 'active' : '' }}">
                                            <span class="icon">＋</span><span>Buat Penerimaan</span>
                                        </a>
                                    @endif
                                    @if ($hasPurchaseReturnIndex)
                                        <a href="{{ route('purchasing.purchase_returns.index') }}"
                                           class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('purchasing.purchase_returns.*') ? 'active' : '' }}">
                                            <span class="icon">↩</span><span>Retur Pembelian</span>
                                        </a>
                                    @endif
                                @endif

                                @if ($hasSupplierInvoiceIndex && ($isOwner || in_array($role ?? '', ['accounting', 'developer'], true)))
                                    <div class="mobile-sidebar-section-label" style="margin-top:.55rem;">Faktur Supplier</div>
                                    <a href="{{ route('purchasing.supplier_invoices.index') }}"
                                       class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('purchasing.supplier_invoices.*') ? 'active' : '' }}">
                                        <span class="icon">🧾</span><span>Tagihan Pemasok</span>
                                    </a>
                                @endif

                                @if ($hasPurchasePaymentsIndex && $isOwner)
                                    <div class="mobile-sidebar-section-label" style="margin-top:.55rem;">Pembayaran Supplier</div>
                                    <a href="{{ route('purchasing.purchase_payments.index') }}"
                                       class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('purchasing.purchase_payments.*') ? 'active' : '' }}">
                                        <span class="icon">💸</span><span>Bayar Pemasok</span>
                                    </a>
                                @endif
                            </div>
                        </li>
                    @endif

                    {{-- SALES & MARKETPLACE --}}
                    @if ($hasMarketplaceToko || $hasMarketplaceOrders || $hasMarketplaceCreate || $hasMarketplacePemenuhan || $hasMarketplacePickingBarang || $hasMarketplaceSkuMapping || $hasMarketplaceSync || $hasMarketplacePencairanDana || $hasMarketplaceProfit || $hasMarketplaceProfitReport || $hasMarketplaceFinancialStatement || $hasMarketplaceFinancialQuality || $hasMarketplaceAds || $hasMarketplaceAnalytics || $hasMarketplaceIssues || $hasImportMarketplaceIndex || $hasImportMarketplaceDraft || $hasImportMarketplaceIncomeIndex || $hasImportMarketplaceIncomeDraft)
                    <div class="mobile-sidebar-section-label">Toko Online</div>
                    <li class="mb-1">
                        <button class="mobile-sidebar-link mobile-sidebar-toggle {{ $marketplaceOpen ? 'is-open' : '' }}"
                                type="button"
                                data-bs-toggle="collapse"
                                data-bs-target="#navMarketplaceMobile"
                                aria-expanded="{{ $marketplaceOpen ? 'true' : 'false' }}"
                                aria-controls="navMarketplaceMobile">
                            <span class="icon">🛒</span><span>Toko Online</span><span class="chevron">▸</span>
                        </button>

                        <div class="collapse {{ $marketplaceOpen ? 'show' : '' }}" id="navMarketplaceMobile">
                            <div class="mobile-sidebar-section-label" style="margin-top:.55rem;">Operasional</div>

                            @if ($hasMarketplaceToko)
                                <a href="{{ route('marketplace.toko') }}"
                                   class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('marketplace.toko') ? 'active' : '' }}">
                                    <span class="icon">🏪</span><span>Toko & Kanal</span>
                                </a>
                                <a href="{{ route('marketplace.settings') }}"
                                   class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('marketplace.settings') ? 'active' : '' }}">
                                    <span class="icon">⚙️</span><span>Pengaturan Umum</span>
                                </a>
                            @endif

                            @if ($hasMarketplaceOrders)
                                <a href="{{ route('marketplace.orders') }}"
                                   class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('marketplace.orders') ? 'active' : '' }}">
                                    <span class="icon">📋</span><span>Pesanan Lokal</span>
                                </a>
                            @endif

                            @if ($hasMarketplacePromotions)
                                <a href="{{ route('marketplace.promotions') }}"
                                   class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('marketplace.promotions') ? 'active' : '' }}">
                                    <span class="icon">🏷️</span><span>Promosi</span>
                                </a>
                            @endif

                            @if ($hasMarketplacePromotionsSummary)
                                <a href="{{ route('marketplace.promotions.summary') }}"
                                   class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('marketplace.promotions.summary') ? 'active' : '' }}">
                                    <span class="icon">📊</span><span>Summary Promosi</span>
                                </a>
                            @endif

                            @if ($hasMarketplaceCreate)
                                <a href="{{ route('marketplace.orders.create') }}"
                                   class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('marketplace.orders.create') ? 'active' : '' }}">
                                    <span class="icon">＋</span><span>Order Manual</span>
                                </a>
                            @endif

                            @if ($hasMarketplacePemenuhan)
                                <a href="{{ route('marketplace.fulfillment') }}"
                                   class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('marketplace.fulfillment') || request()->routeIs('marketplace.fulfillment.*') ? 'active' : '' }}">
                                    <span class="icon">🚚</span><span>Fulfillment</span>
                                </a>
                            @endif

                            @if ($hasMarketplacePickingBarang)
                                <a href="{{ route('marketplace.picking') }}"
                                   class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('marketplace.picking') ? 'active' : '' }}">
                                    <span class="icon">🧺</span><span>Pengambilan</span>
                                </a>
                            @endif

                            <div class="mobile-sidebar-section-label" style="margin-top:.55rem;">Data & Sinkronisasi</div>

                            @if ($hasMarketplaceSkuMapping)
                                <a href="{{ route('marketplace.sku-mapping') }}"
                                   class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('marketplace.sku-mapping') ? 'active' : '' }}">
                                    <span class="icon">🔗</span><span>Pemetaan SKU</span>
                                </a>
                            @endif

                            @if ($hasMarketplaceSync)
                                <a href="{{ route('marketplace.sync') }}"
                                   class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('marketplace.sync') ? 'active' : '' }}">
                                    <span class="icon">↓</span><span>Sinkron Pesanan</span>
                                </a>
                            @endif

                            @if ($hasMarketplaceIssues)
                                <a href="{{ route('marketplace.issues') }}"
                                   class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('marketplace.issues') ? 'active' : '' }}">
                                    <span class="icon">⚠️</span><span>Perbaikan Data</span>
                                </a>
                            @endif

                            <div class="mobile-sidebar-section-label" style="margin-top:.55rem;">Analisa</div>

                            @if ($hasMarketplacePencairanDana)
                                <a href="{{ route('marketplace.settlement') }}"
                                   class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('marketplace.settlement') ? 'active' : '' }}">
                                    <span class="icon">💰</span><span>Pencairan Dana</span>
                                </a>
                            @endif

                            @if ($hasMarketplaceIncomeDetail)
                                <a href="{{ route('marketplace.income-detail') }}"
                                   class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('marketplace.income-detail') ? 'active' : '' }}">
                                    <span class="icon">🧾</span><span>Rincian Penghasilan</span>
                                </a>
                            @endif

                            @if ($hasMarketplaceProfit)
                                <a href="{{ route('marketplace.profit') }}"
                                   class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('marketplace.profit') ? 'active' : '' }}">
                                    <span class="icon">📈</span><span>Laba Pesanan</span>
                                </a>
                            @endif

                            @if ($hasMarketplaceProfitReport)
                                <a href="{{ route('marketplace.reports.profit') }}"
                                   class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('marketplace.reports.profit*') ? 'active' : '' }}">
                                    <span class="icon">📈</span><span>Laporan Keuntungan</span>
                                </a>
                            @endif

                            @if ($hasMarketplaceFinancialStatement)
                                <a href="{{ route('marketplace.reports.financial-statement') }}"
                                   class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('marketplace.reports.financial-statement*') ? 'active' : '' }}">
                                    <span class="icon">🧾</span><span>Laporan Keuangan</span>
                                </a>
                            @endif

                            @if ($hasMarketplaceFinancialClosing)
                                <a href="{{ route('marketplace.reports.financial-closing') }}"
                                   class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('marketplace.reports.financial-closing*') ? 'active' : '' }}">
                                    <span class="icon">🔒</span><span>Closing &amp; Audit</span>
                                </a>
                            @endif

                            @if ($hasMarketplaceFinancialQuality)
                                <a href="{{ route('marketplace.reports.financial-quality') }}"
                                   class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('marketplace.reports.financial-quality*') ? 'active' : '' }}">
                                    <span class="icon">✅</span><span>Audit Keuangan</span>
                                </a>
                            @endif

                            @if ($hasMarketplaceAds)
                                <a href="{{ route('marketplace.ads.dashboard') }}"
                                   class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('marketplace.ads.dashboard') ? 'active' : '' }}">
                                    <span class="icon">🎯</span><span>Analisa Iklan</span>
                                </a>
                            @endif

                            @if ($hasMarketplaceAnalytics)
                                <a href="{{ route('marketplace.analytics') }}"
                                   class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('marketplace.analytics') ? 'active' : '' }}">
                                    <span class="icon">📊</span><span>Analisa Penjualan</span>
                                </a>
                            @endif

                            @if ($hasImportMarketplaceIndex || $hasImportMarketplaceDraft || $hasImportMarketplaceIncomeIndex || $hasImportMarketplaceIncomeDraft)
                                <div class="mobile-sidebar-section-label" style="margin-top:.55rem;">Impor</div>
                                @if ($hasImportMarketplaceIndex)
                                    <a href="{{ route('imports.marketplace.index') }}"
                                       class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('imports.marketplace.*') ? 'active' : '' }}">
                                        <span class="icon">⬆️</span><span>Impor Pengiriman</span>
                                    </a>
                                @endif
                                @if ($hasImportMarketplaceDraft)
                                    <a href="{{ route('imports.marketplace.draft') }}"
                                       class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('imports.marketplace.draft') ? 'active' : '' }}">
                                        <span class="icon">🕘</span><span>Draft Pengiriman</span>
                                    </a>
                                @endif
                                @if ($hasImportMarketplaceIncomeIndex)
                                    <a href="{{ route('imports.marketplace_income.index') }}"
                                       class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('imports.marketplace_income.*') ? 'active' : '' }}">
                                        <span class="icon">💰</span><span>Impor Toko Online Income</span>
                                    </a>
                                @endif
                                @if ($hasImportMarketplaceIncomeDraft)
                                    <a href="{{ route('imports.marketplace_income.draft') }}"
                                       class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('imports.marketplace_income.draft') ? 'active' : '' }}">
                                        <span class="icon">🕘</span><span>Draft Income</span>
                                    </a>
                                @endif
                            @endif
                        </div>
                    </li>
                    @endif

                    @if ($hasSalesInvoicesIndex || $hasSalesInvoicesCreate || $hasSalesShipmentsIndex || $hasSalesShipmentsCreate || $hasSalesShipmentReturnsIndex || $hasSalesOperationalSettings || $hasSalesShipmentsReport || $hasSalesReportItemProfit || $hasSalesReportChannelProfit || $hasSalesReportShipmentAnalytics)
                    <div class="mobile-sidebar-section-label">Penjualan</div>
                    <li class="mb-1">
                        <button class="mobile-sidebar-link mobile-sidebar-toggle {{ $salesOpen ? 'is-open' : '' }}"
                                type="button"
                                data-bs-toggle="collapse"
                                data-bs-target="#navSalesMobile"
                                aria-expanded="{{ $salesOpen ? 'true' : 'false' }}"
                                aria-controls="navSalesMobile">
                            <span class="icon">📑</span><span>Penjualan</span><span class="chevron">▸</span>
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

                            <div class="mobile-sidebar-section-label" style="margin-top:.55rem;">Pengiriman</div>

                            @if ($hasSalesShipmentsIndex)
                                <a href="{{ route('sales.shipments.index') }}"
                                   class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('sales.shipments.index') ? 'active' : '' }}">
                                    <span class="icon">🚚</span><span>Daftar Pengiriman</span>
                                </a>
                            @endif
                            @if ($hasSalesShipmentsCreate)
                                <a href="{{ route('sales.shipments.create') }}"
                                   class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('sales.shipments.create') ? 'active' : '' }}">
                                    <span class="icon">＋</span><span>Pengiriman Baru</span>
                                </a>
                            @endif
                            @if ($router->has('sales.shipments.manual'))
                                <a href="{{ route('sales.shipments.manual') }}"
                                   class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('sales.shipments.manual') ? 'active' : '' }}">
                                    <span class="icon">📦</span><span>Kirim Paket Manual</span>
                                </a>
                            @endif

                            <div class="mobile-sidebar-section-label" style="margin-top:.55rem;">Retur Pengiriman</div>

                            @if ($hasSalesShipmentReturnsIndex)
                                <a href="{{ route('sales.shipment_returns.index') }}"
                                   class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('sales.shipment_returns.index') ? 'active' : '' }}">
                                    <span class="icon">🔁</span><span>Daftar Retur</span>
                                </a>
                            @endif
                            @if ($hasSalesShipmentReturnsCreate)
                                <a href="{{ route('sales.shipment_returns.create') }}"
                                   class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('sales.shipment_returns.create') ? 'active' : '' }}">
                                    <span class="icon">＋</span><span>Retur Pengiriman Baru</span>
                                </a>
                            @endif

                            @if ($hasSalesOperationalSettings)
                                <div class="mobile-sidebar-section-label" style="margin-top:.55rem;">Pengaturan</div>
                                <a href="{{ route('sales.settings.operational') }}"
                                   class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('sales.settings.operational*') ? 'active' : '' }}">
                                    <span class="icon">⚙️</span><span>Pengaturan Operasional</span>
                                </a>
                            @endif

                            <div class="mobile-sidebar-section-label" style="margin-top:.55rem;">Laporan Penjualan</div>

                            @if ($hasSalesShipmentsReport)
                                <a href="{{ route('sales.reports.shipment') }}"
                                   class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('sales.reports.shipment') ? 'active' : '' }}">
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
                                    <span class="icon">📈</span><span>Analisa Pengiriman</span>
                                </a>
                            @endif
                        </div>
                    </li>
                    @endif

                    {{-- INVENTORY --}}
                    <div class="mobile-sidebar-section-label">Persediaan</div>

                    <li class="mb-1">
                        <button class="mobile-sidebar-link mobile-sidebar-toggle {{ $invOpen ? 'is-open' : '' }}"
                                type="button"
                                data-bs-toggle="collapse"
                                data-bs-target="#navPersediaanMobile"
                                aria-expanded="{{ $invOpen ? 'true' : 'false' }}"
                                aria-controls="navPersediaanMobile">
                            <span class="icon">📦</span><span>Persediaan</span><span class="chevron">▸</span>
                        </button>

                        <div class="collapse {{ $invOpen ? 'show' : '' }}" id="navPersediaanMobile">
                            @if ($hasInvIntelligence || $hasWhIntelligence)
                                <div class="mobile-sidebar-section-label" style="margin-top:.55rem;">Ringkasan</div>
                                @if ($hasInvIntelligence)
                                    <a href="{{ route('inventory.intelligence') }}"
                                       class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('inventory.intelligence') || request()->routeIs('inventory.intelligence.*') ? 'active' : '' }}">
                                        <span class="icon">🧠</span><span>Ringkasan Stok</span>
                                    </a>
                                @endif
                                @if ($hasWhIntelligence)
                                    <a href="{{ route('inventory.warehouse_intelligence') }}"
                                       class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('inventory.warehouse_intelligence') || request()->routeIs('inventory.warehouse_intelligence.*') ? 'active' : '' }}">
                                        <span class="icon">📦</span><span>Stok Gudang</span>
                                    </a>
                                @endif
                            @endif

                            <div class="mobile-sidebar-section-label" style="margin-top:.55rem;">Stok</div>

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

                            @if ($hasInvBarcodes)
                                <a href="{{ route('inventory.barcodes.create') }}"
                                   class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('inventory.barcodes.*') ? 'active' : '' }}">
                                    <span class="icon">🔖</span><span>Cetak Barcode</span>
                                </a>
                            @endif

                            @if ($hasInvTransfersIndex || $hasInvTransfersCreate || $hasInvAdjustmentsIndex)
                                <div class="mobile-sidebar-section-label" style="margin-top:.55rem;">Operasional</div>
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
                                    <span class="icon">➕</span><span>Buat Transfer</span>
                                </a>
                            @endif

                            @if ($hasInvAdjustmentsIndex)
                                <a href="{{ route('inventory.adjustments.index') }}"
                                   class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('inventory.adjustments.*') ? 'active' : '' }}">
                                    <span class="icon">📝</span><span>Koreksi Persediaan</span>
                                </a>
                            @endif

                            @if ($hasInvWipAdjIndex || $hasInvWipCutReconcile)
                                <div class="mobile-sidebar-section-label" style="margin-top:.55rem;">Koreksi</div>
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

                            @if ($hasInvOpnamesIndex || $hasInvOpnamesCreate)
                                <div class="mobile-sidebar-section-label" style="margin-top:.55rem;">Opname</div>

                                @if ($hasInvOpnamesIndex)
                                    <a href="{{ route('inventory.stock_opnames.index') }}"
                                       class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('inventory.stock_opnames.*') ? 'active' : '' }}">
                                        <span class="icon">📊</span><span>Stok Opname</span>
                                    </a>
                                @endif

                                @if ($hasInvOpnamesCreate)
                                    <a href="{{ route('inventory.stock_opnames.create') }}"
                                       class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('inventory.stock_opnames.create') ? 'active' : '' }}">
                                        <span class="icon">＋</span><span>Stok Opname Baru</span>
                                    </a>
                                @endif
                            @endif

                            @if ($hasInvExternalIndex || $hasInvExternalCreate)
                                <div class="mobile-sidebar-section-label" style="margin-top:.55rem;">Eksternal</div>

                                @if ($hasInvExternalIndex)
                                    <a href="{{ route('inventory.external_transfers.index') }}"
                                       class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('inventory.external_transfers.index') ? 'active' : '' }}">
                                        <span class="icon">🚚</span><span>Daftar Transfer Eksternal</span>
                                    </a>
                                @endif

                                @if ($hasInvExternalCreate)
                                    <a href="{{ route('inventory.external_transfers.create') }}"
                                       class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('inventory.external_transfers.create') ? 'active' : '' }}">
                                        <span class="icon">➕</span><span>Transfer Eksternal Baru</span>
                                    </a>
                                @endif
                            @endif
                        </div>
                    </li>

                    {{-- STOCK REQUESTS (OWNER) --}}
                    @if ($canViewRts && ($hasRtsStockReqIndex || $hasRtsDirectReceiveIndex))
                        <div class="mobile-sidebar-section-label">Permintaan Stok</div>

                        <li class="mb-1">
                            <button class="mobile-sidebar-link mobile-sidebar-toggle {{ $stockReqOpen ? 'is-open' : '' }}"
                                    type="button"
                                    data-bs-toggle="collapse"
                                    data-bs-target="#navStockRequestsMobile"
                                    aria-expanded="{{ $stockReqOpen ? 'true' : 'false' }}"
                                    aria-controls="navStockRequestsMobile">
                                <span class="icon">📤</span><span>Permintaan Stok</span><span class="chevron">▸</span>
                            </button>

                            <div class="collapse {{ $stockReqOpen ? 'show' : '' }}" id="navStockRequestsMobile">
                                @if ($hasRtsStockReqIndex)
                                    <a href="{{ route('rts.stock-requests.index') }}"
                                       class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('rts.stock-requests.*') ? 'active' : '' }}"
                                       @if($canManageRts && $hasRtsNeedReceive) title="{{ $rtsBadgeTitle }}" @endif
                                    >
                                        <span class="icon">🛒</span><span>Permintaan Stok (RTS)</span>
                                        @if($canManageRts && $hasRtsNeedReceive)
                                            <span class="ms-dot" aria-hidden="true"></span>
                                        @endif
                                    </a>
                                @endif

                                @if ($canManageRts && $hasRtsDirectReceiveIndex)
                                    <a href="{{ route('rts.direct-receives.index') }}"
                                       class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('rts.direct-receives.*') ? 'active' : '' }}">
                                        <span class="icon">⚡</span><span>Setor Jahit Dadakan</span>
                                    </a>
                                @endif
                            </div>
                        </li>
                    @endif

                    {{-- PRODUCTION --}}
                    <div class="mobile-sidebar-section-label">Produksi</div>

                    <li class="mb-1">
                        <button class="mobile-sidebar-link mobile-sidebar-toggle {{ $prodOpen ? 'is-open' : '' }}"
                                type="button"
                                data-bs-toggle="collapse"
                                data-bs-target="#navProductionMobile"
                                aria-expanded="{{ $prodOpen ? 'true' : 'false' }}"
                                aria-controls="navProductionMobile">
                            <span class="icon">🏭</span><span>Produksi</span><span class="chevron">▸</span>
                        </button>

                        <div class="collapse {{ $prodOpen ? 'show' : '' }}" id="navProductionMobile">
                            <div class="mobile-sidebar-section-label" style="margin-top:.55rem;">Monitoring</div>

                            @if ($hasProdDashboard)
                                <a href="{{ route('production.dashboard') }}"
                                   class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('production.dashboard') ? 'active' : '' }}">
                                    <span class="icon">📊</span><span>Beranda Produksi</span>
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

                            @if ($hasProdReconcile && $isOwner)
                                <a href="{{ route('production.reconcile.index') }}"
                                   class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('production.reconcile.*') ? 'active' : '' }}">
                                    <span class="icon">🔍</span><span>Rekon Biaya</span>
                                </a>
                            @endif

                            <div class="mobile-sidebar-section-label" style="margin-top:.55rem;">Alur Produksi</div>

                            @if ($hasProdCuttingJobsIndex)
                                <a href="{{ route('production.cutting_jobs.index') }}"
                                   class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('production.cutting_jobs.*') ? 'active' : '' }}">
                                    <span class="icon">✂️</span><span>Pekerjaan Potong</span>
                                </a>
                            @endif

                            @if ($hasProdSewPickupsIndex)
                                <a href="{{ route('production.sewing.pickups.index') }}"
                                   class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('production.sewing.pickups.*') ? 'active' : '' }}">
                                    <span class="icon">🧵</span><span>Ambil Jahit</span>
                                </a>
                            @endif

                            @if ($hasProdSewReturnsIndex)
                                <a href="{{ route('production.sewing.returns.index') }}"
                                   class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('production.sewing.returns.*') ? 'active' : '' }}">
                                    <span class="icon">📥</span><span>Setor Jahit</span>
                                </a>
                            @endif

                            @if ($hasProdSewRejectReturnsIndex)
                                <a href="{{ route('production.sewing.reject_returns.index') }}"
                                   class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('production.sewing.reject_returns.*') ? 'active' : '' }}">
                                    <span class="icon">♻️</span><span>Setor Reject Jahit</span>
                                </a>
                            @endif

                            @if ($hasProdQcIndex)
                                <a href="{{ $prodQcHref }}"
                                   class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('production.qc.*') ? 'active' : '' }}">
                                    <span class="icon">✅</span><span>{{ $prodQcLabel }}</span>
                                </a>
                            @endif

                            @if ($hasProdFinishingJobsIndex)
                                <a href="{{ route('production.finishing_jobs.index') }}"
                                   class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('production.finishing_jobs.*') ? 'active' : '' }}">
                                    <span class="icon">🧶</span><span>Finishing</span>
                                </a>
                            @endif

                            @if ($hasProdFinishingRepairsIndex)
                                <a href="{{ route('production.finishing_repairs.index') }}"
                                   class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('production.finishing_repairs.*') ? 'active' : '' }}">
                                    <span class="icon">🩹</span><span>Perbaikan Finishing</span>
                                </a>
                            @endif

                            @if ($hasProdPackingIndex)
                                <a href="{{ route('production.packing_jobs.index') }}"
                                   class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('production.packing_jobs.*') ? 'active' : '' }}">
                                    <span class="icon">📦</span><span>Packing</span>
                                </a>
                            @endif

                        </div>
                    </li>

                    {{-- FINANCE --}}
                    <div class="mobile-sidebar-section-label">Keuangan</div>

                    {{-- Akuntansi --}}
                    <li class="mb-1">
                        <button class="mobile-sidebar-link mobile-sidebar-toggle {{ $accountingOpen ? 'is-open' : '' }}"
                                type="button"
                                data-bs-toggle="collapse"
                                data-bs-target="#navAccountingMobile"
                                aria-expanded="{{ $accountingOpen ? 'true' : 'false' }}"
                                aria-controls="navAccountingMobile">
                            <span class="icon">🧾</span><span>Akuntansi</span><span class="chevron">▸</span>
                        </button>

                        <div class="collapse {{ $accountingOpen ? 'show' : '' }}" id="navAccountingMobile">
                            @if ($hasCashBasisReportIndex)
                                <a href="{{ route('accounting.cash-basis-report.index') }}"
                                   class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('accounting.cash-basis-report.*') ? 'active' : '' }}">
                                    <span class="icon">📊</span><span>Laporan Kas</span>
                                </a>
                            @endif

                            @if ($hasOpeningBalancesIndex)
                                <a href="{{ route('accounting.opening-balances.index') }}"
                                   class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('accounting.opening-balances.*') ? 'active' : '' }}">
                                    <span class="icon">🟢</span><span>Saldo Awal</span>
                                </a>
                            @endif

                            @if ($hasCashExpensesIndex)
                                <a href="{{ route('accounting.cash-expenses.index') }}"
                                   class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('accounting.cash-expenses.*') ? 'active' : '' }}">
                                    <span class="icon">💸</span><span>Pengeluaran Kas</span>
                                </a>
                            @endif

                            @if ($hasCashReceiptsIndex)
                                <a href="{{ route('accounting.cash-receipts.index') }}"
                                   class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('accounting.cash-receipts.*') ? 'active' : '' }}">
                                    <span class="icon">💰</span><span>Penerimaan Kas</span>
                                </a>
                            @endif

                            @if ($hasMarketplacePayoutsIndex)
                                <a href="{{ route('accounting.marketplace-payouts.index') }}"
                                   class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('accounting.marketplace-payouts.*') ? 'active' : '' }}">
                                    <span class="icon">🛒</span><span>Penerimaan Marketplace</span>
                                </a>
                            @endif

                            @if ($hasJournalsIndex)
                                <a href="{{ route('accounting.journals.index') }}"
                                   class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('accounting.journals.*') ? 'active' : '' }}">
                                    <span class="icon">📓</span><span>Jurnal</span>
                                </a>
                            @endif

                            @if ($hasAccountsIndex)
                                <a href="{{ route('accounting.accounts.index') }}"
                                   class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('accounting.accounts.index') ? 'active' : '' }}">
                                    <span class="icon">🗂️</span><span>Akun (COA)</span>
                                </a>
                            @endif
                            @if ($hasBukuBesarIndex ?? false)
                                <a href="{{ route('accounting.buku-besar.index') }}"
                                   class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('accounting.buku-besar.*') || request()->routeIs('accounting.accounts.ledger') ? 'active' : '' }}">
                                    <span class="icon">📒</span><span>Buku Besar</span>
                                </a>
                            @endif
                            @if ($hasTrialBalanceIndex ?? false)
                                <a href="{{ route('accounting.trial-balance.index') }}"
                                   class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('accounting.trial-balance.*') ? 'active' : '' }}">
                                    <span class="icon">⚖️</span><span>Neraca Saldo</span>
                                </a>
                            @endif
                            @if ($hasProfitLossIndex ?? false)
                                <a href="{{ route('accounting.profit-loss.index') }}"
                                   class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('accounting.profit-loss.*') ? 'active' : '' }}">
                                    <span class="icon">📈</span><span>Laba Rugi</span>
                                </a>
                            @endif
                            @if ($hasApReportIndex ?? false)
                                <a href="{{ route('accounting.ap-report.index') }}"
                                   class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('accounting.ap-report.*') ? 'active' : '' }}">
                                    <span class="icon">🧾</span><span>Hutang Dagang</span>
                                </a>
                            @endif
                        </div>
                    </li>

                    {{-- Penggajian --}}
                    <li class="mb-1">
                        <button class="mobile-sidebar-link mobile-sidebar-toggle {{ $payrollOpen ? 'is-open' : '' }}"
                                type="button"
                                data-bs-toggle="collapse"
                                data-bs-target="#navPayrollMobile"
                                aria-expanded="{{ $payrollOpen ? 'true' : 'false' }}"
                                aria-controls="navPayrollMobile">
                            <span class="icon">💰</span><span>Penggajian</span><span class="chevron">▸</span>
                        </button>

                        <div class="collapse {{ $payrollOpen ? 'show' : '' }}" id="navPayrollMobile">
                            @if ($hasPayrollDashboard)
                                <a href="{{ route('payroll.dashboard') }}"
                                   class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('payroll.dashboard*') ? 'active' : '' }}">
                                    <span class="icon">📈</span><span>Beranda</span>
                                </a>
                            @endif

                            @if ($hasPieceworkIndex)
                                <a href="{{ route('payroll.piecework.index', ['module' => 'cutting']) }}"
                                   class="mobile-sidebar-link mobile-sidebar-link-sub {{ $pieceworkCuttingActive ? 'active' : '' }}">
                                    <span class="icon">✂️</span><span>Gaji Potong</span>
                                </a>

                                <a href="{{ route('payroll.piecework.index', ['module' => 'sewing']) }}"
                                   class="mobile-sidebar-link mobile-sidebar-link-sub {{ $pieceworkSewingActive ? 'active' : '' }}">
                                    <span class="icon">🧵</span><span>Gaji Jahit (Ambil)</span>
                                </a>
                            @endif

                            @if ($hasPieceRatesIndex)
                                <a href="{{ route('payroll.piece_rates.index') }}"
                                   class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('payroll.piece_rates.*') ? 'active' : '' }}">
                                    <span class="icon">📑</span><span>Tarif Borongan</span>
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

                    {{-- Biaya Produksi --}}
                    <li class="mb-1">
                        <button class="mobile-sidebar-link mobile-sidebar-toggle {{ $costingOpen ? 'is-open' : '' }}"
                                type="button"
                                data-bs-toggle="collapse"
                                data-bs-target="#navCostingMobile"
                                aria-expanded="{{ $costingOpen ? 'true' : 'false' }}"
                                aria-controls="navCostingMobile">
                            <span class="icon">📉</span><span>Biaya Produksi &amp; HPP</span><span class="chevron">▸</span>
                        </button>

                        <div class="collapse {{ $costingOpen ? 'show' : '' }}" id="navCostingMobile">
                            @if ($hasHppIndex)
                                <a href="{{ route('costing.hpp.index') }}"
                                   class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('costing.hpp.*') ? 'active' : '' }}">
                                    <span class="icon">⚙️</span><span>HPP Barang Jadi</span>
                                </a>
                            @endif

                            @if ($hasProdCostPeriodsIndex)
                                <a href="{{ route('costing.production_cost_periods.index') }}"
                                   class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('costing.production_cost_periods.*') ? 'active' : '' }}">
                                    <span class="icon">📆</span><span>Periode Biaya Produksi</span>
                                </a>
                            @endif
                        </div>
                    </li>

                @endif

                {{-- Tools (owner-only) --}}
                @if ($isOwner && $router->has('tools.pricing-calculator'))
                    <li class="mt-2 text-uppercase small menu-label">Tools</li>
                    <li>
                        <a href="{{ route('tools.pricing-calculator') }}"
                           class="mobile-sidebar-link {{ request()->routeIs('tools.pricing-calculator') ? 'active' : '' }}">
                            <span class="icon">🧮</span><span>Pricing &amp; ROAS Calculator</span>
                        </a>
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
