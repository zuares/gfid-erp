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
    // AI dan komunikasi hanya ditampilkan untuk owner/operating.
    $canOpenAiAgent = $isOwner || $isOperating;

    // ✅ capability flags
    $canViewRts = $isOwner || $isAdmin || $isOperating;
    $canManageRts = $isOwner || $isAdmin;

    $router = app('router');

    // =========================================================
    // ROUTE GUARDS (biar tidak error kalau route belum ada)
    // =========================================================
    $hasDashboardRoute = $router->has('dashboard');
    $hasAiIndexRoute = $router->has('ai.index');
    $hasAiAgentRoute = $router->has('ai.agent');
    $hasAiOpenAiRoute = $router->has('ai.openai.index');
    $hasOwnerAccessControl = $router->has('owner.access-control.index');

    // Persediaan
    $hasInvStocksItems = $router->has('inventory.stocks.items');
    $hasInvStocksLots = $router->has('inventory.stocks.lots');
    $hasInvStockCard = $router->has('inventory.stock_card.index');
    $hasInvBarcodes = $router->has('inventory.barcodes.create') && ($isOwner || $isAdmin);
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
    $hasWhIntelligence = $router->has('inventory.warehouse_intelligence');

    // RTS
    $hasRtsStockReqIndex = $router->has('rts.stock-requests.index');
    $hasRtsDirectReceiveIndex = $router->has('rts.direct-receives.index');

    // Sales
    $hasSalesShipmentsIndex = $router->has('sales.shipments.index');
    $hasSalesShipmentsCreate = $router->has('sales.shipments.create');
    $hasSalesShipmentReturnsIndex = $router->has('sales.shipment_returns.index');
    $hasSalesOperationalSettings = $isOwner && $router->has('sales.settings.operational');

    $hasSalesInvoicesIndex = $router->has('sales.invoices.index');
    $hasSalesInvoicesCreate = $router->has('sales.invoices.create');

    $hasSalesShipmentsReport = $router->has('sales.reports.shipment');

    $hasSalesReportPerformance = $router->has('sales.reports.sales_performance.index');
    $hasSalesReportItemProfit = $router->has('sales.reports.item_profit');
    $hasSalesReportChannelProfit = $router->has('sales.reports.channel_profit');
    $hasSalesReportShipmentAnalytics = $router->has('sales.reports.shipment_analytics');

    // Data Master
    $hasMasterItemsIndex = $router->has('master.items.index');
    $hasMasterItemCategoriesIndex = $router->has('master.item_categories.index');
    $hasMasterCustomersIndex = $router->has('master.customers.index');
    $hasMasterSuppliersIndex = $router->has('master.suppliers.index');
    $hasMasterItemBomsIndex = $router->has('master.item_boms.index');
    $hasMasterEmployeesIndex = $router->has('master.employees.index');

    // Pembelian
    $hasPurchasingDashboard  = $router->has('purchasing.dashboard');
    $hasPoIndex   = $router->has('purchasing.purchase_orders.index');
    $hasPoCreate  = $router->has('purchasing.purchase_orders.create');
    $hasGrnIndex  = $router->has('purchasing.purchase_receipts.index');
    $hasGrnCreate = $router->has('purchasing.purchase_receipts.create');
    $hasPurchaseReturnIndex    = $router->has('purchasing.purchase_returns.index');
    $hasSupplierInvoiceIndex   = $router->has('purchasing.supplier_invoices.index');
    $hasPurchasePaymentsIndex  = $router->has('purchasing.purchase_payments.index');
    $hasPrIndex                = $router->has('purchasing.purchase_requests.index');
    $hasMaterialShortageIndex  = $router->has('purchasing.material_shortages.index');
    $hasSupplierItemsIndex     = $router->has('purchasing.supplier_items.index');

    // =========================================================
    // Toko Online
    $hasMarketplaceToko        = $router->has('marketplace.toko');
    $hasMarketplaceOrders      = $router->has('marketplace.orders');
    $hasMarketplacePemenuhan = $router->has('marketplace.fulfillment');
    $hasMarketplacePickingBarang     = $router->has('marketplace.picking');
    $hasMarketplaceSkuMapping  = $router->has('marketplace.sku-mapping');
    $hasMarketplaceSync        = $router->has('marketplace.sync');
    $hasMarketplacePromotions  = $router->has('marketplace.promotions');
    $hasMarketplacePromotionsSummary  = $router->has('marketplace.promotions.summary');
    $hasMarketplacePencairanDana  = $router->has('marketplace.settlement');
    $hasMarketplaceIncomeDetail = $router->has('marketplace.income-detail');
    $hasMarketplaceEscrow = $router->has('marketplace.escrow');
    $hasMarketplaceProfit      = $router->has('marketplace.profit');
    $hasMarketplaceAds         = $router->has('marketplace.ads');
    $hasMarketplaceAnalytics   = $router->has('marketplace.analytics');
    $hasMarketplaceIssues      = $router->has('marketplace.issues');

    // Toko Online (UPDATED sesuai routes terbaru yang kamu kasih)
    // =========================================================
    // Toko Online Orders
    $hasMarketplaceIndex = $router->has('marketplace.orders');
    $hasMarketplaceCreate = $router->has('marketplace.orders.create');
    $hasMarketplaceShow = $router->has('marketplace.orders.show');

    // Toko Online Laporan
    $hasMarketplaceSalesReport = !$isAdmin && $router->has('marketplace.reports.sales');
    $hasMarketplaceSalesExport = $router->has('marketplace.reports.sales.export');
    $hasMarketplaceProfitReport = $isOwner && $router->has('marketplace.reports.profit');
    $hasMarketplaceFinancialStatement = $isOwner && $router->has('marketplace.reports.financial-statement');
    $hasMarketplaceFinancialClosing = $isOwner && $router->has('marketplace.reports.financial-closing');
    $hasMarketplaceFinancialQuality = $isOwner && $router->has('marketplace.reports.financial-quality');

    // Toko Online Reconcile
    $hasMarketplaceReconcileQueue = !$isAdmin && $router->has('marketplace.reconcile.queue');
    $hasMarketplaceReconcileQueueBulk = $router->has('marketplace.reconcile.queue.bulk'); // POST (guard only)
    $hasMarketplaceReconcilePreview = $router->has('marketplace.reconcile.preview'); // POST
    $hasMarketplaceReconcileCommit = $router->has('marketplace.reconcile.commit'); // POST

    $hasMarketplaceReconciliationsResolve = $router->has('marketplace.reconciliations.resolve'); // POST
    $hasMarketplaceReconciliationsDiff = $router->has('marketplace.reconciliations.diff'); // GET

    $hasMarketplaceReconcileItemsIndex = !$isAdmin && $router->has('marketplace.reconcile.items'); // GET
    $hasMarketplaceReconcileItemsApply = $router->has('marketplace.reconcile.items.apply'); // POST
    $hasMarketplaceReconcileItemsPackets = $router->has('marketplace.reconcile.items.packets'); // GET

    // =========================================================
    // IMPORTS (tetap kamu pakai sebelumnya)
    // =========================================================

    // imports.marketplace.*
    $hasImportMarketplaceIndex  = $router->has('imports.marketplace.index');
    $hasImportMarketplaceCreate = $router->has('imports.marketplace.create');
    $hasImportMarketplaceExport = $router->has('imports.marketplace.export');
    $hasImportMarketplaceDraft  = $router->has('imports.marketplace.draft');
    $hasImportMarketplacePreview = $router->has('imports.marketplace.preview');
    $hasImportMarketplaceCommit  = $router->has('imports.marketplace.commit');
    $hasImportMarketplaceCancel  = $router->has('imports.marketplace.cancel');
    $hasImportMarketplaceShow    = $router->has('imports.marketplace.show');

    // imports.marketplace_income.*
    $hasImportMarketplaceIncomeIndex  = $router->has('imports.marketplace_income.index');
    $hasImportMarketplaceIncomeCreate = $router->has('imports.marketplace_income.create');
    $hasImportMarketplaceIncomeDraft  = $router->has('imports.marketplace_income.draft');
    $hasImportMarketplaceIncomePreview = $router->has('imports.marketplace_income.preview');
    $hasImportMarketplaceIncomeCommit  = $router->has('imports.marketplace_income.commit');
    $hasImportMarketplaceIncomeCancel  = $router->has('imports.marketplace_income.cancel');
    $hasImportMarketplaceIncomeShow    = $router->has('imports.marketplace_income.show');
    $hasImportMarketplaceIncomeOrderShow = $router->has('imports.marketplace_income.order.show');
    $hasImportMarketplaceIncomeApply = $router->has('imports.marketplace_income.apply');

    // Produksi
    $hasProdCuttingJobsIndex = $router->has('production.cutting_jobs.index');
    $hasProdSewPickupsIndex = $router->has('production.sewing.pickups.index');
    $hasProdSewReturnsIndex = $router->has('production.sewing.returns.index');
    $hasProdSewRejectReturnsIndex = $router->has('production.sewing.reject_returns.index');
    $hasProdFinishingJobsIndex = $router->has('production.finishing_jobs.index');
    $hasProdFinishingRepairsIndex = $router->has('production.finishing_repairs.index');
    $hasProdQcIndex = $router->has('production.qc.index');
    $prodQcHref = $hasProdQcIndex
        ? route('production.qc.index', (auth()->user()?->role ?? null) === 'admin' ? ['stage' => 'sewing'] : [])
        : '#';
    $prodQcLabel = ($role ?? null) === 'admin' ? 'QC Jahit' : 'QC Produksi';
    $hasProdPackingIndex = $router->has('production.packing_jobs.index');
    $hasProdPriorityIndex = $router->has('production.priority.index');
    $hasProdReportsIndex = $router->has('production.reports.index');

    $hasProdDashboard  = $router->has('production.dashboard');
    $hasProdReconcile  = $router->has('production.reconcile.index');
    $hasProdWipNormalization = $router->has('production.wip_normalization.index');
    $hasProdWipCleanup       = $router->has('production.wip_cleanup.index');
    $hasProdLog              = $router->has('production.log.index');
    $hasProdBundleAssemblies = $router->has('production.bundle_assemblies.index');

    // Akuntansi
    $hasAccountsIndex = $router->has('accounting.accounts.index');
    $hasCashExpensesIndex = $router->has('accounting.cash-expenses.index');
    $hasCashBasisReportIndex = $router->has('accounting.cash-basis-report.index');
    $hasCashReceiptsIndex = $router->has('accounting.cash-receipts.index');
    $hasJournalsIndex = $router->has('accounting.journals.index');
    $hasOpeningBalancesIndex = $router->has('accounting.opening-balances.index');
    $hasOpeningBalancesBatchIndex = $router->has('accounting.opening-balances-batch.index');
    $hasMarketplacePayoutsIndex = !$isAdmin && $router->has('accounting.marketplace-payouts.index');
    $hasApReportIndex           = $router->has('accounting.ap-report.index');
    $hasTrialBalanceIndex       = $router->has('accounting.trial-balance.index');
    $hasProfitLossIndex         = $router->has('accounting.profit-loss.index');
    $hasBukuBesarIndex          = $router->has('accounting.buku-besar.index');

    // Penggajian (owner)
    $hasPayrollDashboard = $router->has('payroll.dashboard');
    $hasPieceworkIndex = $router->has('payroll.piecework.index');
    $hasPieceRatesIndex = $router->has('payroll.piece_rates.index');
    $hasPayrollReportsOperators = $router->has('payroll.reports.operators');

    // Biaya Produksi (owner)
    $hasHppIndex = $router->has('costing.hpp.index');
    $hasProdCostPeriodsIndex = $router->has('costing.production_cost_periods.index');

    $canModule = fn (string $module) => $user ? $user->canAccessModule($module) : false;
    $hasDashboardRoute = $hasDashboardRoute && $canModule('dashboard');
    $hasCashExpensesIndex = $hasCashExpensesIndex && $canModule('cash-expenses');

    if (!$canModule('inventory')) {
        $hasInvStocksItems = $hasInvStocksLots = $hasInvStockCard = false;
        $hasInvTransfersIndex = $hasInvTransfersCreate = $hasInvAdjustmentsIndex = false;
        $hasInvOpnamesIndex = $hasInvOpnamesCreate = false;
        $hasInvExternalIndex = $hasInvExternalCreate = false;
        $hasInvWipAdjIndex = $hasInvWipCutReconcile = $hasInvIntelligence = $hasWhIntelligence = false;
        $hasRtsStockReqIndex = $hasRtsDirectReceiveIndex = false;
    }

    if (!$canModule('sales')) {
        $hasSalesShipmentsIndex = $hasSalesShipmentsCreate = $hasSalesShipmentReturnsIndex = false;
        $hasSalesOperationalSettings = false;
        $hasSalesInvoicesIndex = $hasSalesInvoicesCreate = $hasSalesShipmentsReport = false;
        $hasSalesReportPerformance = $hasSalesReportItemProfit = false;
        $hasSalesReportChannelProfit = $hasSalesReportShipmentAnalytics = false;
    }

    if (!$canModule('master')) {
        // Admin hanya mendapat akses CRUD Master Item, bukan seluruh Master Data.
        $hasMasterItemsIndex = $isAdmin || $isOwner;
        $hasMasterItemCategoriesIndex = $hasMasterCustomersIndex = false;
        $hasMasterSuppliersIndex = $hasMasterItemBomsIndex = $hasMasterEmployeesIndex = false;
    }

    if (!$canModule('purchasing')) {
        $hasPoIndex = $hasPoCreate = $hasGrnIndex = $hasGrnCreate = false;
        $hasSupplierItemsIndex = false;
    }

    if (!$isAdmin && !$canModule('marketplace')) {
        $hasMarketplaceToko = $hasMarketplaceOrders = $hasMarketplacePemenuhan = false;
        $hasMarketplacePickingBarang = $hasMarketplaceSkuMapping = $hasMarketplaceSync = false;
        $hasMarketplacePencairanDana = $hasMarketplaceProfit = $hasMarketplaceAds = false;
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
        $hasImportMarketplaceIndex = $hasImportMarketplaceCreate = $hasImportMarketplaceExport = false;
        $hasImportMarketplaceDraft = $hasImportMarketplacePreview = $hasImportMarketplaceCommit = false;
        $hasImportMarketplaceCancel = $hasImportMarketplaceShow = false;
        $hasImportMarketplaceIncomeIndex = $hasImportMarketplaceIncomeCreate = false;
        $hasImportMarketplaceIncomeDraft = $hasImportMarketplaceIncomePreview = false;
        $hasImportMarketplaceIncomeCommit = $hasImportMarketplaceIncomeCancel = false;
        $hasImportMarketplaceIncomeShow = $hasImportMarketplaceIncomeOrderShow = false;
        $hasImportMarketplaceIncomeApply = false;
    }

    // Impor disembunyikan dari navigasi admin untuk sementara.
    if ($isAdmin) {
        $hasImportMarketplaceIndex = $hasImportMarketplaceCreate = $hasImportMarketplaceExport = false;
        $hasImportMarketplaceDraft = false;
        $hasImportMarketplaceIncomeIndex = $hasImportMarketplaceIncomeCreate = false;
        $hasImportMarketplaceIncomeDraft = false;
    }

    if (!$canModule('production')) {
        $hasProdCuttingJobsIndex = $hasProdSewPickupsIndex = $hasProdSewReturnsIndex = false;
        $hasProdSewRejectReturnsIndex = false;
        $hasProdFinishingJobsIndex = $hasProdFinishingRepairsIndex = $hasProdQcIndex = false;
        $hasProdPackingIndex = $hasProdPriorityIndex = false;
        $hasProdReportsIndex = $hasProdDashboard = false;
    }

    if (!$canModule('accounting')) {
        $hasAccountsIndex = $hasCashBasisReportIndex = false;
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
    $openPembelian = $open('purchasing.*');
    $openMarketplaceOrders = $open('marketplace.orders.*');

    $openMarketplaceTools =
        $open('marketplace.toko') ||
        $open('marketplace.returns') ||
        $open('marketplace.kilat') ||
        $open('marketplace.settings') ||
        $open('marketplace.orders') ||
        $open('marketplace.orders.*') ||
        $open('marketplace.fulfillment') ||
        $open('marketplace.fulfillment.*') ||
        $open('marketplace.picking') ||
        $open('marketplace.sku-mapping') ||
        $open('marketplace.sync') ||
        $open('marketplace.promotions') ||
        $open('marketplace.promotions.*') ||
        $open('marketplace.promotions.summary') ||
        $open('marketplace.settlement') ||
        $open('marketplace.income-detail') ||
        $open('marketplace.escrow') ||
        $open('marketplace.profit') ||
        $open('marketplace.ads') ||
        $open('marketplace.analytics') ||
        $open('marketplace.issues') ||
        $open('marketplace.reports.*') ||
        $open('marketplace.reconcile.*') ||
        $open('marketplace.reconciliations.*') ||
        $open('imports.marketplace.*') ||
        $open('imports.marketplace_income.*');

    $openSales =
        $open('sales.invoices.*') ||
        $open('sales.shipments.*') ||
        $open('sales.shipment_returns.*') ||
        $open('sales.settings.*') ||
        $open('sales.reports.*') ||
        $open('sales.reports.shipment');

    $openPersediaan =
        $open('inventory.intelligence') ||
        $open('inventory.intelligence.*') ||
        $open('inventory.warehouse_intelligence') ||
        $open('inventory.warehouse_intelligence.*') ||
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
        $open('accounting.marketplace-payouts.*') ||
        $open('accounting.ap-report.*') ||
        $open('accounting.trial-balance.*') ||
        $open('accounting.profit-loss.*') ||
        $open('accounting.buku-besar.*') ||
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

    $openWebsite =
        $open('admin.catalog.products.*') ||
        $open('admin.catalog.categories.*') ||
        $open('admin.website.*');

    $openCrm = $open('admin.crm.*');
    $openAi = $open('ai.*');
    $openCommunication = $open('whatsapp.*') || $open('settings.whatsapp.*');

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

    // Penggajian active helpers
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
    .sidebar-modern {
        --sidebar-active-blue: #2563eb;
    }

    body[data-theme="dark"] .sidebar-modern {
        --sidebar-active-blue: #60a5fa;
    }

    /* Tablet (768px–991px): sidebar 180px — sempit tapi full navigasi */
    @media (min-width: 768px) {
        .sidebar-modern {
            position: fixed;
            top: 0;
            left: 0;
            width: 180px;
            height: 100vh;
            padding: .8rem .65rem 1.4rem;
            display: flex;
            flex-direction: column;
            gap: .75rem;
            background: color-mix(in srgb, var(--card) 92%, var(--bg) 8%);
            backdrop-filter: blur(14px);
            border-right: 1px solid rgba(148, 163, 184, .35);
            box-shadow: 8px 0 24px rgba(15,23,42,.05), 2px 0 8px rgba(15,23,42,.03);
            border-radius: 0 18px 18px 0;
            z-index: 1030;
            overflow-y: auto;
            overflow-x: hidden;
            scrollbar-width: thin;
            scrollbar-color: rgba(148, 163, 184, .35) transparent;
        }

        .app-main { margin-left: 180px; }

        /* Kecilkan font dan padding sedikit agar muat di 180px */
        .sidebar-modern .sidebar-brand {
            font-size: 1rem;
            padding: .55rem .2rem .75rem;
            gap: .5rem;
        }
        .sidebar-modern .sidebar-brand .role-pill {
            font-size: .62rem;
            padding: .12rem .4rem;
        }
        .sidebar-modern .sidebar-subhead {
            font-size: .6rem;
            letter-spacing: .1em;
        }
        .sidebar-modern .sidebar-link,
        .sidebar-modern .sidebar-toggle {
            font-size: .82rem;
            padding: .52rem .7rem;
            gap: .4rem;
        }
        .sidebar-modern .sidebar-link .icon,
        .sidebar-modern .sidebar-toggle .icon {
            width: 18px;
            font-size: .95rem;
        }
        .sidebar-modern .sidebar-link-sub {
            font-size: .78rem;
            padding: .38rem .65rem .38rem 1.6rem;
        }
        .sidebar-modern .sidebar-link-sub .icon {
            width: 14px;
            font-size: .8rem;
        }
    }

    /* Desktop (992px+): sidebar penuh 260px */
    @media (min-width: 992px) {
        .sidebar-modern {
            width: 260px;
            padding: 1rem 1rem 1.6rem;
            gap: 1rem;
            border-radius: 0 22px 22px 0;
        }

        .app-main { margin-left: 260px; }

        /* Kembalikan ukuran font/padding ke default */
        .sidebar-modern .sidebar-brand {
            font-size: 1.15rem;
            padding: .7rem .35rem .9rem;
            gap: .75rem;
        }
        .sidebar-modern .sidebar-brand .role-pill {
            font-size: .7rem;
            padding: .18rem .55rem;
        }
        .sidebar-modern .sidebar-subhead {
            font-size: .65rem;
            letter-spacing: .12em;
        }
        .sidebar-modern .sidebar-link,
        .sidebar-modern .sidebar-toggle {
            font-size: .93rem;
            padding: .6rem .9rem;
            gap: .55rem;
        }
        .sidebar-modern .sidebar-link .icon,
        .sidebar-modern .sidebar-toggle .icon {
            width: 22px;
            font-size: 1.05rem;
        }
        .sidebar-modern .sidebar-link-sub {
            font-size: .86rem;
            padding: .42rem .9rem .42rem 2.3rem;
        }
        .sidebar-modern .sidebar-link-sub .icon {
            width: 18px;
            font-size: .9rem;
        }
    }

    .sidebar-modern { display: none; }
    @media (min-width: 768px) { .sidebar-modern { display: flex; } }

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
        padding: .48rem .85rem;
        border-radius: 12px;
        color: var(--text);
        text-decoration: none;
        font-size: .93rem;
        transition: background .1s ease-out, box-shadow .1s ease-out, transform .1s ease-out, color .1s ease-out;
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
        box-shadow: inset 3px 0 0 var(--sidebar-active-blue);
        color: var(--accent);
    }

    .sidebar-toggle { cursor: pointer; border: 0; width: 100%; background: transparent; text-align: left; }
    .sidebar-toggle .chevron { margin-left: auto; font-size: .8rem; opacity: .8; transition: transform .12s ease-out; }
    .sidebar-toggle[aria-expanded="true"] .chevron { transform: rotate(90deg); }
    .sidebar-toggle.is-open { background: transparent; box-shadow: none; color: var(--accent); font-weight: 700; }
    .sidebar-toggle.is-open .icon { color: var(--accent); }

    .sidebar-link-sub {
        position: relative;
        font-size: .86rem;
        padding: .38rem .85rem .38rem 2.15rem;
        opacity: .95;
        border-radius: 8px;
        display: flex;
        align-items: center;
        gap: .55rem;
        color: var(--text);
        text-decoration: none;
        transition: background .1s ease-out, box-shadow .1s ease-out, transform .1s ease-out, color .1s ease-out;
    }

    .sidebar-link-sub .icon { width: 18px; font-size: .9rem; text-align: center; }

    .sidebar-link-sub:hover {
        background: color-mix(in srgb, var(--accent-soft) 16%, var(--card) 84%);
        box-shadow: inset 0 0 0 1px var(--line);
    }

    .sidebar-link-sub.active {
        background: transparent;
        font-weight: 700;
        box-shadow: inset 3px 0 0 var(--sidebar-active-blue);
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
        background: var(--sidebar-active-blue);
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
    body[data-theme="dark"] .sidebar-divider{
        background: color-mix(in srgb, rgba(255,255,255,.05) 90%, transparent 10%);
    }

    .sidebar-modern .collapsing {
        transition: height .15s ease-out !important;
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
                <x-sidebar.simple-link href="{{ route('dashboard') }}" icon="bi bi-house" :active="request()->routeIs('dashboard')">
                    Beranda
                </x-sidebar.simple-link>
            </li>
            @if (($hasAiIndexRoute || $hasAiAgentRoute) && $canOpenAiAgent)
                <x-sidebar.label text="AI" />
                <li>
                    <x-sidebar.simple-link href="{{ route('ai.index') }}" icon="bi bi-stars" :active="request()->routeIs('ai.index')">
                        AI Studio
                    </x-sidebar.simple-link>
                </li>
                @if ($hasAiOpenAiRoute)
                    <li>
                        <x-sidebar.simple-link href="{{ route('ai.openai.index') }}" icon="bi bi-plug" :active="request()->routeIs('ai.openai.*')">
                            Connect OpenAI
                        </x-sidebar.simple-link>
                    </li>
                @endif
                <li>
                    <x-sidebar.simple-link href="{{ route('ai.agent') }}" icon="bi bi-chat-square-dots" :active="$openAi">
                        AI Agent
                    </x-sidebar.simple-link>
                </li>
            @endif
            @if ($isOwner && $hasOwnerAccessControl)
                <li>
                    <x-sidebar.simple-link href="{{ route('owner.access-control.index') }}" icon="bi bi-shield-lock" :active="request()->routeIs('owner.access-control.*')">
                        Akses Login
                    </x-sidebar.simple-link>
                </li>
            @endif
            @if ($isOwner && $router->has('owner.activity-logs.index'))
                <li>
                    <x-sidebar.simple-link href="{{ route('owner.activity-logs.index') }}" icon="bi bi-activity" :active="request()->routeIs('owner.activity-logs.*')">
                        Log Aktivitas
                    </x-sidebar.simple-link>
                </li>
            @endif
            @if ($isOwner && ($router->has('whatsapp.index') || $router->has('settings.whatsapp.index')))
                <x-sidebar.label text="Komunikasi" />
                <li class="mb-1">
                    <button class="sidebar-link sidebar-toggle {{ $openCommunication ? 'is-open' : '' }}" type="button"
                        data-bs-toggle="collapse" data-bs-target="#navCommunication"
                        aria-expanded="{{ $openCommunication ? 'true' : 'false' }}" aria-controls="navCommunication">
                        <span class="icon"><i class="bi bi-whatsapp"></i></span>
                        <span>WhatsApp</span>
                        <span class="chevron">▸</span>
                    </button>
                    <div class="collapse {{ $openCommunication ? 'show' : '' }}" id="navCommunication">
                        @if ($router->has('whatsapp.index'))
                            <x-sidebar.sub-link href="{{ route('whatsapp.index') }}" icon="bi bi-chat-left-text"
                                :active="request()->routeIs('whatsapp.*')">
                                WhatsApp Center
                            </x-sidebar.sub-link>
                        @endif
                        @if ($isOwner && $router->has('settings.whatsapp.index'))
                            <x-sidebar.sub-link href="{{ route('settings.whatsapp.index') }}" icon="bi bi-gear"
                                :active="request()->routeIs('settings.whatsapp.*')">
                                Pengaturan WhatsApp
                            </x-sidebar.sub-link>
                        @endif
                    </div>
                </li>
            @endif
            @if ($isOwner && $router->has('settings.system.index'))
                <li>
                    <x-sidebar.simple-link href="{{ route('settings.system.index') }}" icon="bi bi-gear" :active="request()->routeIs('settings.system.*')">
                        Pengaturan Sistem
                    </x-sidebar.simple-link>
                </li>
            @endif
            @if ($isOwner && $router->has('admin.catalog.products.index'))
                <x-sidebar.label text="Website" />
                <li class="mb-1">
                    <button class="sidebar-link sidebar-toggle {{ $openWebsite ? 'is-open' : '' }}" type="button"
                        data-bs-toggle="collapse" data-bs-target="#navWebsite"
                        aria-expanded="{{ $openWebsite ? 'true' : 'false' }}" aria-controls="navWebsite">
                        <span class="icon"><i class="bi bi-globe"></i></span>
                        <span>Website</span>
                        <span class="chevron">▸</span>
                    </button>
                    <div class="collapse {{ $openWebsite ? 'show' : '' }}" id="navWebsite">
                        <x-sidebar.sub-link href="{{ route('admin.catalog.products.index') }}" icon="bi bi-box-seam"
                            :active="request()->routeIs('admin.catalog.products.index') || request()->routeIs('admin.catalog.products.edit') || request()->routeIs('admin.catalog.products.create')">
                            Produk Website
                        </x-sidebar.sub-link>
                        @if($router->has('admin.catalog.products.ranking'))
                        <x-sidebar.sub-link href="{{ route('admin.catalog.products.ranking') }}" icon="bi bi-bar-chart-steps"
                            :active="request()->routeIs('admin.catalog.products.ranking')">
                            Ranking Produk
                        </x-sidebar.sub-link>
                        @endif
                        @if($router->has('admin.website.settings'))
                        <x-sidebar.sub-link href="{{ route('admin.website.settings') }}" icon="bi bi-sliders"
                            :active="request()->routeIs('admin.website.settings')">
                            Pengaturan Website
                        </x-sidebar.sub-link>
                        @endif
                        @if($router->has('admin.catalog.categories.index'))
                        <x-sidebar.sub-link href="{{ route('admin.catalog.categories.index') }}" icon="bi bi-tag"
                            :active="request()->routeIs('admin.catalog.categories*')">
                            Kategori Produk
                        </x-sidebar.sub-link>
                        @endif
                    </div>
                </li>
            @endif
            @if ($isOwner && $router->has('admin.crm.dashboard'))
                <x-sidebar.label text="CRM Storefront" />
                <li class="mb-1">
                    <button class="sidebar-link sidebar-toggle {{ $openCrm ? 'is-open' : '' }}" type="button"
                        data-bs-toggle="collapse" data-bs-target="#navCrm"
                        aria-expanded="{{ $openCrm ? 'true' : 'false' }}" aria-controls="navCrm">
                        <span class="icon"><i class="bi bi-people"></i></span>
                        <span>CRM Storefront</span>
                        <span class="chevron">▸</span>
                    </button>
                    <div class="collapse {{ $openCrm ? 'show' : '' }}" id="navCrm">
                        <x-sidebar.sub-link href="{{ route('admin.crm.dashboard') }}" icon="bi bi-bar-chart-line"
                            :active="request()->routeIs('admin.crm.dashboard')">
                            Beranda
                        </x-sidebar.sub-link>
                        <x-sidebar.sub-link href="{{ route('admin.crm.visitors') }}" icon="bi bi-person-video3"
                            :active="request()->routeIs('admin.crm.visitors*')">
                            Visitors
                        </x-sidebar.sub-link>
                        <x-sidebar.sub-link href="{{ route('admin.crm.orders') }}" icon="bi bi-bag-check"
                            :active="request()->routeIs('admin.crm.orders*')">
                            Pesanan
                        </x-sidebar.sub-link>
                        <x-sidebar.sub-link href="{{ route('admin.crm.prospects') }}" icon="bi bi-person-lines-fill"
                            :active="request()->routeIs('admin.crm.prospects*')">
                            Prospects
                        </x-sidebar.sub-link>
                        <x-sidebar.sub-link href="{{ route('admin.crm.customers') }}" icon="bi bi-people"
                            :active="request()->routeIs('admin.crm.customers*')">
                            Pelanggan
                        </x-sidebar.sub-link>
                        <x-sidebar.sub-link href="{{ route('admin.crm.segments') }}" icon="bi bi-diagram-3"
                            :active="request()->routeIs('admin.crm.segments*')">
                            Segments
                        </x-sidebar.sub-link>
                    </div>
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

            @if ($canShow(
                $hasInvIntelligence,
                $hasWhIntelligence,
                $hasInvStocksItems,
                $hasInvStockCard,
                $hasInvBarcodes,
                $hasInvOpnamesIndex,
                $hasInvTransfersIndex,
                $hasInvTransfersCreate,
                (!$isAdmin && $hasInvWipAdjIndex)
            ))
                <x-sidebar.label :text="$isAdmin ? 'Persediaan' : 'Operasional'" />
                <li class="mb-1">
                    <button class="sidebar-link sidebar-toggle {{ $openPersediaan ? 'is-open' : '' }}" type="button"
                        data-bs-toggle="collapse" data-bs-target="#navPersediaanAdmin"
                        aria-expanded="{{ $openPersediaan ? 'true' : 'false' }}" aria-controls="navPersediaanAdmin">
                        <span class="icon"><i class="bi bi-box-seam"></i></span>
                        <span>Persediaan</span>
                        <span class="chevron">▸</span>
                    </button>

                    <div class="collapse {{ $openPersediaan ? 'show' : '' }}" id="navPersediaanAdmin">
                        @if ($hasInvIntelligence || $hasWhIntelligence)
                            @php $subhead('Ringkasan'); @endphp
                            @if ($hasInvIntelligence)
                                <x-sidebar.sub-link href="{{ route('inventory.intelligence') }}" icon="bi bi-cpu"
                                    :active="request()->routeIs('inventory.intelligence') || request()->routeIs('inventory.intelligence.*')">
                                    Ringkasan Stok
                                </x-sidebar.sub-link>
                            @endif
                            @if ($hasWhIntelligence)
                                <x-sidebar.sub-link href="{{ route('inventory.warehouse_intelligence') }}" icon="bi bi-box-seam"
                                    :active="request()->routeIs('inventory.warehouse_intelligence') || request()->routeIs('inventory.warehouse_intelligence.*')">
                                    Stok Gudang
                                </x-sidebar.sub-link>
                            @endif
                        @endif

                        @if ($hasInvStocksItems || $hasInvStockCard || $hasInvBarcodes)
                            @php $subhead('Stok'); @endphp
                            @if ($hasInvStocksItems)
                                <x-sidebar.sub-link href="{{ route('inventory.stocks.items') }}" icon="bi bi-box-seam"
                                    :active="request()->routeIs('inventory.stocks.items')">
                                    Stok Barang
                                </x-sidebar.sub-link>
                            @endif
                            @if ($hasInvStockCard)
                                <x-sidebar.sub-link href="{{ route('inventory.stock_card.index') }}" icon="bi bi-list-ul"
                                    :active="request()->routeIs('inventory.stock_card.*')">
                                    Kartu Stok
                                </x-sidebar.sub-link>
                            @endif
                            @if ($hasInvBarcodes)
                                <x-sidebar.sub-link href="{{ route('inventory.barcodes.create') }}" icon="bi bi-upc-scan"
                                    :active="request()->routeIs('inventory.barcodes.*')">
                                    Cetak Barcode
                                </x-sidebar.sub-link>
                            @endif
                        @endif

                        @if ($hasInvOpnamesIndex || ($isAdmin && ($hasInvTransfersIndex || $hasInvTransfersCreate)) || (!$isAdmin && $hasInvWipAdjIndex))
                            @php $subhead($isAdmin ? 'Operasional' : 'Koreksi'); @endphp
                            @if ($hasInvOpnamesIndex)
                                <x-sidebar.sub-link href="{{ route('inventory.stock_opnames.index') }}" icon="bi bi-bar-chart"
                                    :active="request()->routeIs('inventory.stock_opnames.*')">
                                    Stok Opname
                                </x-sidebar.sub-link>
                            @endif
                            @if ($isAdmin && $hasInvTransfersIndex)
                                <x-sidebar.sub-link href="{{ route('inventory.transfers.index') }}" icon="bi bi-arrow-repeat"
                                    :active="request()->routeIs('inventory.transfers.index')">
                                    Daftar Transfer
                                </x-sidebar.sub-link>
                            @endif
                            @if ($isAdmin && $hasInvTransfersCreate)
                                <x-sidebar.sub-link href="{{ route('inventory.transfers.create') }}" icon="bi bi-plus-circle"
                                    :active="request()->routeIs('inventory.transfers.create')">
                                    Buat Transfer
                                </x-sidebar.sub-link>
                            @endif
                            @if (!$isAdmin && $hasInvWipAdjIndex)
                                <x-sidebar.sub-link href="{{ route('inventory.wip_adjustments.index') }}" icon="bi bi-receipt"
                                    :active="request()->routeIs('inventory.wip_adjustments.*')">
                                    Koreksi WIP
                                </x-sidebar.sub-link>
                            @endif
                        @endif
                    </div>
                </li>
            @endif

            {{-- Permintaan Stok (RTS) --}}
            @if ($canViewRts && ($hasRtsStockReqIndex || (!$isAdmin && $hasRtsDirectReceiveIndex)))
                <x-sidebar.label text="Permintaan Stok" />
                <li class="mb-1">
                    <button class="sidebar-link sidebar-toggle {{ $openStockRequests ? 'is-open' : '' }}" type="button"
                        data-bs-toggle="collapse" data-bs-target="#navStockRequestsAdmin"
                        aria-expanded="{{ $openStockRequests ? 'true' : 'false' }}" aria-controls="navStockRequestsAdmin">
                        <span class="icon"><i class="bi bi-cart3"></i></span>
                        <span>Permintaan Stok</span>
                        <span class="chevron">▸</span>
                    </button>
                    <div class="collapse {{ $openStockRequests ? 'show' : '' }}" id="navStockRequestsAdmin">
                        <div class="sidebar-subhead">Permintaan</div>
                        @if ($hasRtsStockReqIndex)
                            <x-sidebar.sub-link href="{{ route('rts.stock-requests.index') }}" icon="bi bi-cart3"
                                :active="request()->routeIs('rts.stock-requests.*')"
                                :dot-only="$canManageRts && $hasRtsNeedReceive"
                                badge-tone="warn"
                                :badge-title="$rtsBadgeTitle">
                                Permintaan Stok (RTS)
                            </x-sidebar.sub-link>
                        @endif

                        @if ($isAdmin && $hasRtsDirectReceiveIndex)
                            <x-sidebar.sub-link href="{{ route('rts.direct-receives.index') }}" icon="bi bi-lightning"
                                :active="request()->routeIs('rts.direct-receives.*')">
                                Setor Jahit Dadakan
                            </x-sidebar.sub-link>
                        @endif

                        @if (!$isAdmin && $canManageRts && $hasRtsDirectReceiveIndex)
                            <x-sidebar.sub-link href="{{ route('rts.direct-receives.index') }}" icon="bi bi-lightning"
                                :active="request()->routeIs('rts.direct-receives.*')">
                                RTS Dadakan
                            </x-sidebar.sub-link>
                        @endif
                    </div>
                </li>
            @endif

            {{-- Toko Online --}}
            @if ($canShow($hasMarketplaceIndex, $hasMarketplaceSalesReport, $hasMarketplaceReconcileQueue, $hasMarketplaceReconcileItemsIndex))
                <x-sidebar.label text="Toko Online" />
                <li class="mb-1">
                    <button class="sidebar-link sidebar-toggle {{ $openMarketplaceTools ? 'is-open' : '' }}" type="button"
                        data-bs-toggle="collapse" data-bs-target="#navMarketplaceAdmin"
                        aria-expanded="{{ $openMarketplaceTools ? 'true' : 'false' }}" aria-controls="navMarketplaceAdmin">
                        <span class="icon"><i class="bi bi-cart3"></i></span>
                        <span>Toko Online</span>
                        <span class="chevron">▸</span>
                    </button>
                    <div class="collapse {{ $openMarketplaceTools ? 'show' : '' }}" id="navMarketplaceAdmin">
                        <div class="sidebar-subhead">Operasional</div>
                        <div class="simple-group">
                    @if ($hasMarketplaceIndex)
                        <x-sidebar.simple-link href="{{ route('marketplace.orders') }}" icon="bi bi-cart3"
                            :active="request()->routeIs('marketplace.orders') || request()->routeIs('marketplace.orders.*')">
                            Pesanan
                        </x-sidebar.simple-link>
                    @endif

                    @if ($router->has('marketplace.products'))
                        <x-sidebar.simple-link href="{{ route('marketplace.products') }}" icon="bi bi-tags"
                            :active="request()->routeIs('marketplace.products')">
                            Produk
                        </x-sidebar.simple-link>
                    @endif

                    @if (!$isAdmin && $router->has('marketplace.boost'))
                        <x-sidebar.simple-link href="{{ route('marketplace.boost') }}" icon="bi bi-rocket-takeoff"
                            :active="request()->routeIs('marketplace.boost')">
                            Naikkan Produk
                        </x-sidebar.simple-link>
                    @endif

                    @if ($router->has('marketplace.promotions'))
                        <x-sidebar.simple-link href="{{ route('marketplace.promotions') }}" icon="bi bi-percent"
                            :active="request()->routeIs('marketplace.promotions')">
                            Promosi
                        </x-sidebar.simple-link>
                    @endif
                    @if ($hasMarketplacePromotionsSummary)
                        <x-sidebar.simple-link href="{{ route('marketplace.promotions.summary') }}" icon="bi bi-grid-3x3-gap"
                            :active="request()->routeIs('marketplace.promotions.summary')">
                            Summary Promosi
                        </x-sidebar.simple-link>
                    @endif

                    @if ($router->has('marketplace.chat') && $canModule('marketplace'))
                        <x-sidebar.simple-link href="{{ route('marketplace.chat') }}" icon="bi bi-chat-dots"
                            :active="request()->routeIs('marketplace.chat')">
                            Chat <span class="sidebarChatBadge badge bg-danger rounded-pill ms-2" style="display:none;"></span>
                        </x-sidebar.simple-link>
                    @endif

                    @if ($hasMarketplaceSalesReport)
                        <x-sidebar.simple-link href="{{ route('marketplace.reports.sales') }}" icon="bi bi-graph-up"
                            :active="request()->routeIs('marketplace.reports.sales')">
                            Laporan Penjualan
                        </x-sidebar.simple-link>
                    @endif

                    @if ($hasMarketplaceFinancialQuality)
                        <x-sidebar.simple-link href="{{ route('marketplace.reports.financial-quality') }}" icon="bi bi-clipboard2-data"
                            :active="request()->routeIs('marketplace.reports.financial-quality*')">
                            Audit Keuangan (Owner)
                        </x-sidebar.simple-link>
                    @endif

                    @if ($hasMarketplaceReconcileQueue)
                        <x-sidebar.simple-link href="{{ route('marketplace.reconcile.queue') }}" icon="bi bi-puzzle"
                            :active="request()->routeIs('marketplace.reconcile.*') || request()->routeIs('marketplace.reconciliations.*')">
                            Antrean Rekonsiliasi
                        </x-sidebar.simple-link>
                    @endif

                    @if ($hasMarketplaceReconcileItemsIndex)
                        <x-sidebar.simple-link href="{{ route('marketplace.reconcile.items') }}" icon="bi bi-receipt"
                            :active="request()->routeIs('marketplace.reconcile.items*')">
                            Rekonsiliasi Barang
                        </x-sidebar.simple-link>
                    @endif
                        </div>
                    </div>
                </li>
            @endif

            {{-- CRM Storefront --}}
            @if ($isOwner)
                <x-sidebar.label text="CRM Storefront" />
                <li class="mb-1">
                    <button class="sidebar-link sidebar-toggle {{ $openCrm ? 'is-open' : '' }}" type="button"
                        data-bs-toggle="collapse" data-bs-target="#navCrmAdmin"
                        aria-expanded="{{ $openCrm ? 'true' : 'false' }}" aria-controls="navCrmAdmin">
                        <span class="icon"><i class="bi bi-people"></i></span>
                        <span>CRM Storefront</span>
                        <span class="chevron">▸</span>
                    </button>
                    <div class="collapse {{ $openCrm ? 'show' : '' }}" id="navCrmAdmin">
                        <x-sidebar.sub-link href="{{ route('admin.crm.dashboard') }}" icon="bi bi-bar-chart-line"
                            :active="request()->routeIs('admin.crm.dashboard')">
                            Beranda
                        </x-sidebar.sub-link>
                        <x-sidebar.sub-link href="{{ route('admin.crm.visitors') }}" icon="bi bi-person-video3"
                            :active="request()->routeIs('admin.crm.visitors*')">
                            Visitors
                        </x-sidebar.sub-link>
                        <x-sidebar.sub-link href="{{ route('admin.crm.orders') }}" icon="bi bi-bag-check"
                            :active="request()->routeIs('admin.crm.orders*')">
                            Pesanan
                        </x-sidebar.sub-link>
                        <x-sidebar.sub-link href="{{ route('admin.crm.prospects') }}" icon="bi bi-person-lines-fill"
                            :active="request()->routeIs('admin.crm.prospects*')">
                            Prospects
                        </x-sidebar.sub-link>
                        <x-sidebar.sub-link href="{{ route('admin.crm.customers') }}" icon="bi bi-people"
                            :active="request()->routeIs('admin.crm.customers*')">
                            Pelanggan
                        </x-sidebar.sub-link>
                        <x-sidebar.sub-link href="{{ route('admin.crm.segments') }}" icon="bi bi-diagram-3"
                            :active="request()->routeIs('admin.crm.segments*')">
                            Segments
                        </x-sidebar.sub-link>
                    </div>
                </li>
            @endif

            {{-- Impor (admin only) --}}
            @if ($isAdmin && $canShow(
                $hasImportMarketplaceIndex,
                $hasImportMarketplaceDraft,
                $hasImportMarketplaceCreate,
                $hasImportMarketplaceExport,
                $hasImportMarketplaceIncomeCreate,
                $hasImportMarketplaceIncomeIndex
            ))
                <x-sidebar.label text="Impor" />
                <li class="simple-group">
                    @if ($hasImportMarketplaceIndex)
                        <x-sidebar.simple-link href="{{ route('imports.marketplace.index') }}" icon="bi bi-upload"
                            :active="request()->routeIs('imports.marketplace.*')">
                            Impor Pengiriman
                        </x-sidebar.simple-link>
                    @endif

                    @if ($hasImportMarketplaceDraft)
                        <x-sidebar.simple-link href="{{ route('imports.marketplace.draft') }}" icon="bi bi-clock-history"
                            :active="request()->routeIs('imports.marketplace.draft')">
                            Draft Pengiriman
                        </x-sidebar.simple-link>
                    @endif

                    @if ($hasImportMarketplaceIncomeIndex)
                        <x-sidebar.simple-link href="{{ route('imports.marketplace_income.index') }}" icon="bi bi-cash-stack"
                            :active="request()->routeIs('imports.marketplace_income.*')">
                            Import Toko Online Income
                        </x-sidebar.simple-link>
                    @endif

                    @if ($hasImportMarketplaceIncomeDraft)
                        <x-sidebar.simple-link href="{{ route('imports.marketplace_income.draft') }}" icon="bi bi-clock-history"
                            :active="request()->routeIs('imports.marketplace_income.draft')">
                            Draft Income
                        </x-sidebar.simple-link>
                    @endif
                </li>
            @endif

            {{-- Sales (admin only) --}}
            @if ($isAdmin && $canShow($hasSalesShipmentsIndex, $hasSalesShipmentReturnsIndex))
                <x-sidebar.label text="Penjualan" />
                <li class="mb-1">
                    <button class="sidebar-link sidebar-toggle {{ $openSales ? 'is-open' : '' }}" type="button"
                        data-bs-toggle="collapse" data-bs-target="#navSalesAdmin"
                        aria-expanded="{{ $openSales ? 'true' : 'false' }}" aria-controls="navSalesAdmin">
                        <span class="icon"><i class="bi bi-truck"></i></span>
                        <span>Penjualan</span>
                        <span class="chevron">▸</span>
                    </button>
                    <div class="collapse {{ $openSales ? 'show' : '' }}" id="navSalesAdmin">
                        <div class="sidebar-subhead">Pengiriman</div>
                        <div class="simple-group">
                    @if ($hasSalesShipmentsIndex)
                        <x-sidebar.simple-link href="{{ route('sales.shipments.index') }}" icon="bi bi-truck"
                            :active="request()->routeIs('sales.shipments.*')">
                            Pengiriman
                        </x-sidebar.simple-link>
                    @endif

                    @if ($hasSalesShipmentReturnsIndex)
                        <x-sidebar.simple-link href="{{ route('sales.shipment_returns.index') }}" icon="bi bi-arrow-repeat"
                            :active="request()->routeIs('sales.shipment_returns.*')">
                            Retur Pengiriman
                        </x-sidebar.simple-link>
                    @endif
                        </div>
                    </div>
                </li>
            @endif

            {{-- Produksi (admin only) — Setor Jahit + Dadakan + Reject Jahit + QC Jahit --}}
            @php
                $adminHasSewReturns  = $isAdmin && $router->has('production.sewing.returns.create');
                $adminHasRejectReturns = $isAdmin && $hasProdSewRejectReturnsIndex;
                $adminHasQc          = $isAdmin && $hasProdQcIndex;
            @endphp
            @if ($adminHasSewReturns || $adminHasRejectReturns || $adminHasQc)
                <x-sidebar.label text="Produksi" />
                <li class="mb-1">
                    <button class="sidebar-link sidebar-toggle {{ $openProduction ? 'is-open' : '' }}" type="button"
                        data-bs-toggle="collapse" data-bs-target="#navProductionAdmin"
                        aria-expanded="{{ $openProduction ? 'true' : 'false' }}" aria-controls="navProductionAdmin">
                        <span class="icon"><i class="bi bi-gear-wide-connected"></i></span>
                        <span>Produksi</span>
                        <span class="chevron">▸</span>
                    </button>
                    <div class="collapse {{ $openProduction ? 'show' : '' }}" id="navProductionAdmin">
                        <div class="sidebar-subhead">Operasional</div>
                        <div class="simple-group">
                    @if ($adminHasSewReturns)
                        <x-sidebar.simple-link href="{{ route('production.sewing.returns.create') }}" icon="bi bi-inbox"
                            :active="request()->routeIs('production.sewing.returns.*')">
                            Setor Jahit
                        </x-sidebar.simple-link>
                    @endif

                    @if ($adminHasRejectReturns)
                        <x-sidebar.simple-link href="{{ route('production.sewing.reject_returns.index') }}" icon="bi bi-arrow-clockwise"
                            :active="request()->routeIs('production.sewing.reject_returns.*')">
                            Setor Reject Jahit
                        </x-sidebar.simple-link>
                    @endif

                    @if ($adminHasQc)
                        <x-sidebar.simple-link href="{{ $prodQcHref }}" icon="bi bi-check-circle"
                            :active="request()->routeIs('production.qc.*')">
                            {{ $prodQcLabel }}
                        </x-sidebar.simple-link>
                    @endif

                        </div>
                    </div>
                </li>
            @endif


            {{-- Pembelian (admin only) --}}
            @if ($isAdmin && $canShow($hasPoIndex, $hasPoCreate, $hasGrnIndex, $hasGrnCreate))
                <x-sidebar.label text="Pengadaan" />
                <li class="mb-1">
                    <button class="sidebar-link sidebar-toggle {{ $openPembelian ? 'is-open' : '' }}" type="button"
                        data-bs-toggle="collapse" data-bs-target="#navPurchasingAdmin"
                        aria-expanded="{{ $openPembelian ? 'true' : 'false' }}" aria-controls="navPurchasingAdmin">
                        <span class="icon"><i class="bi bi-cart-check"></i></span>
                        <span>Pengadaan</span>
                        <span class="chevron">▸</span>
                    </button>
                    <div class="collapse {{ $openPembelian ? 'show' : '' }}" id="navPurchasingAdmin">
                        <div class="sidebar-subhead">PO & Penerimaan</div>
                        <div class="simple-group">
                    @if ($hasPoIndex)
                        <x-sidebar.simple-link href="{{ route('purchasing.purchase_orders.index') }}" icon="bi bi-list-ul"
                            :active="request()->routeIs('purchasing.purchase_orders.index')">
                            Daftar PO
                        </x-sidebar.simple-link>
                    @endif
                    @if ($hasPoCreate)
                        <x-sidebar.simple-link href="{{ route('purchasing.purchase_orders.create') }}" icon="bi bi-plus-circle"
                            :active="request()->routeIs('purchasing.purchase_orders.create')">
                            Buat PO
                        </x-sidebar.simple-link>
                    @endif
                    @if ($hasGrnIndex)
                        <x-sidebar.simple-link href="{{ route('purchasing.purchase_receipts.index') }}" icon="bi bi-list-ul"
                            :active="request()->routeIs('purchasing.purchase_receipts.index')">
                            Daftar Penerimaan
                        </x-sidebar.simple-link>
                    @endif
                    @if ($hasGrnCreate)
                        <x-sidebar.simple-link href="{{ route('purchasing.purchase_receipts.create') }}" icon="bi bi-box-seam"
                            :active="request()->routeIs('purchasing.purchase_receipts.create')">
                            Buat Penerimaan
                        </x-sidebar.simple-link>
                    @endif
                        </div>
                    </div>
                </li>
            @endif

            {{-- Master Item (admin dapat mengelola item tanpa membuka seluruh Master Data) --}}
            @if ($isAdmin && $hasMasterItemsIndex)
                <x-sidebar.label text="Data Master" />
                <li class="simple-group">
                    <x-sidebar.simple-link href="{{ route('master.items.index') }}" icon="bi bi-box-seam"
                        :active="request()->routeIs('master.items.*')">
                        Master Item
                    </x-sidebar.simple-link>
                </li>
            @endif

            {{-- Produksi (operating only) --}}
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
                <x-sidebar.label text="Produksi" />
                <li class="mb-1">
                    <button class="sidebar-link sidebar-toggle {{ $openProduction ? 'is-open' : '' }}" type="button"
                        data-bs-toggle="collapse" data-bs-target="#navProductionOperating"
                        aria-expanded="{{ $openProduction ? 'true' : 'false' }}" aria-controls="navProductionOperating">
                        <span class="icon"><i class="bi bi-gear-wide-connected"></i></span>
                        <span>Produksi</span>
                        <span class="chevron">▸</span>
                    </button>
                    <div class="collapse {{ $openProduction ? 'show' : '' }}" id="navProductionOperating">
                        <div class="sidebar-subhead">Operasional</div>
                        <div class="simple-group">
                    @if ($hasProdDashboard)
                        <x-sidebar.simple-link href="{{ route('production.dashboard') }}" icon="bi bi-bar-chart"
                            :active="request()->routeIs('production.dashboard')">
                            Beranda Produksi
                        </x-sidebar.simple-link>
                    @endif

                    @if ($hasProdCuttingJobsIndex)
                        <x-sidebar.simple-link href="{{ route('production.cutting_jobs.index') }}" icon="bi bi-scissors"
                            :active="request()->routeIs('production.cutting_jobs.*')">
                            Potong
                        </x-sidebar.simple-link>
                    @endif

                    @if ($hasProdSewPickupsIndex)
                        <x-sidebar.simple-link href="{{ route('production.sewing.pickups.index') }}" icon="bi bi-send"
                            :active="request()->routeIs('production.sewing.pickups.*')">
                            Ambil Jahit
                        </x-sidebar.simple-link>
                    @endif

                    @if ($hasProdSewReturnsIndex)
                        <x-sidebar.simple-link href="{{ route('production.sewing.returns.index') }}" icon="bi bi-inbox"
                            :active="request()->routeIs('production.sewing.returns.*')">
                            Setoran Jahit
                        </x-sidebar.simple-link>
                    @endif

                    @if ($hasProdSewRejectReturnsIndex)
                        <x-sidebar.simple-link href="{{ route('production.sewing.reject_returns.index') }}" icon="bi bi-arrow-clockwise"
                            :active="request()->routeIs('production.sewing.reject_returns.*')">
                            Setor Reject Jahit
                        </x-sidebar.simple-link>
                    @endif

                    @if ($hasProdQcIndex)
                        <x-sidebar.simple-link href="{{ $prodQcHref }}" icon="bi bi-check-circle"
                            :active="request()->routeIs('production.qc.*')">
                            {{ $prodQcLabel }}
                        </x-sidebar.simple-link>
                    @endif

                    @if ($hasProdFinishingJobsIndex)
                        <x-sidebar.simple-link href="{{ route('production.finishing_jobs.index') }}" icon="bi bi-patch-check"
                            :active="request()->routeIs('production.finishing_jobs.*')">
                            Finishing
                        </x-sidebar.simple-link>
                    @endif

                    @if ($hasProdFinishingRepairsIndex)
                        <x-sidebar.simple-link href="{{ route('production.finishing_repairs.index') }}" icon="bi bi-tools"
                            :active="request()->routeIs('production.finishing_repairs.*')">
                            Perbaikan Finishing
                        </x-sidebar.simple-link>
                    @endif

                    @if ($hasProdPackingIndex)
                        <x-sidebar.simple-link href="{{ route('production.packing_jobs.index') }}" icon="bi bi-box-seam"
                            :active="request()->routeIs('production.packing_jobs.*')">
                            Packing
                        </x-sidebar.simple-link>
                    @endif

                    @if ($hasProdWipNormalization)
                        <x-sidebar.simple-link href="{{ route('production.wip_normalization.index') }}" icon="bi bi-clipboard-check"
                            :active="request()->routeIs('production.wip_normalization.*')">
                            Normalisasi WIP
                        </x-sidebar.simple-link>
                    @endif

                    @if ($hasProdBundleAssemblies)
                        <x-sidebar.simple-link href="{{ route('production.bundle_assemblies.index') }}" icon="bi bi-boxes"
                            :active="request()->routeIs('production.bundle_assemblies.*')">
                            Assembly Bundle
                        </x-sidebar.simple-link>
                    @endif

                    @if ($hasProdWipCleanup)
                        <x-sidebar.simple-link href="{{ route('production.wip_cleanup.index') }}" icon="bi bi-eraser"
                            :active="request()->routeIs('production.wip_cleanup.*')">
                            Bersihkan WIP
                        </x-sidebar.simple-link>
                    @endif

                    @if ($hasProdPriorityIndex)
                        <x-sidebar.simple-link href="{{ route('production.priority.index') }}" icon="bi bi-bullseye"
                            :active="request()->routeIs('production.priority.*')">
                            Prioritas Produksi
                        </x-sidebar.simple-link>
                    @endif

                    @if ($hasProdReportsIndex)
                        <x-sidebar.simple-link href="{{ route('production.reports.index') }}" icon="bi bi-graph-up"
                            :active="request()->routeIs('production.reports.*')">
                            Laporan Produksi
                        </x-sidebar.simple-link>
                    @endif

                    @if ($hasProdLog)
                        <x-sidebar.simple-link href="{{ route('production.log.index') }}" icon="bi bi-list-columns-reverse"
                            :active="request()->routeIs('production.log.*')">
                            Log Produksi
                        </x-sidebar.simple-link>
                    @endif

                    @if ($hasProdReconcile && auth()->user()?->hasRole('owner'))
                        <x-sidebar.simple-link href="{{ route('production.reconcile.index') }}" icon="bi bi-search"
                            :active="request()->routeIs('production.reconcile.*')">
                            Rekon Biaya
                        </x-sidebar.simple-link>
                    @endif

                        </div>
                    </div>
                </li>
            @endif

            {{-- Keuangan --}}
            @if ($canShow(
                $hasCashExpensesIndex,
                $hasCashReceiptsIndex,
                $hasCashBasisReportIndex,
                $hasJournalsIndex,
                $hasAccountsIndex,
                $hasOpeningBalancesIndex,
                $hasOpeningBalancesBatchIndex
            ))
                <x-sidebar.label text="Keuangan" />
                <li class="mb-1">
                    <button class="sidebar-link sidebar-toggle {{ $openAccounting ? 'is-open' : '' }}" type="button"
                        data-bs-toggle="collapse" data-bs-target="#navAccountingAdmin"
                        aria-expanded="{{ $openAccounting ? 'true' : 'false' }}" aria-controls="navAccountingAdmin">
                        <span class="icon"><i class="bi bi-calculator"></i></span>
                        <span>Keuangan</span>
                        <span class="chevron">▸</span>
                    </button>
                    <div class="collapse {{ $openAccounting ? 'show' : '' }}" id="navAccountingAdmin">
                        <div class="sidebar-subhead">Akuntansi</div>
                        <div class="simple-group">
                    @if ($hasCashBasisReportIndex)
                        <x-sidebar.simple-link href="{{ route('accounting.cash-basis-report.index') }}" icon="bi bi-bar-chart"
                            :active="request()->routeIs('accounting.cash-basis-report.*')">
                            Laporan Kas
                        </x-sidebar.simple-link>
                    @endif

                    @if ($hasCashExpensesIndex)
                        <x-sidebar.simple-link href="{{ route('accounting.cash-expenses.index') }}" icon="bi bi-cash"
                            :active="request()->routeIs('accounting.cash-expenses.*')">
                            Pengeluaran Kas
                        </x-sidebar.simple-link>
                    @endif

                    @if ($hasCashReceiptsIndex)
                        <x-sidebar.simple-link href="{{ route('accounting.cash-receipts.index') }}" icon="bi bi-coin"
                            :active="request()->routeIs('accounting.cash-receipts.*')">
                            Penerimaan Kas
                        </x-sidebar.simple-link>
                    @endif

                    @if ($hasMarketplacePayoutsIndex)
                        <x-sidebar.simple-link href="{{ route('accounting.marketplace-payouts.index') }}" icon="bi bi-cart3"
                            :active="request()->routeIs('accounting.marketplace-payouts.*')">
                            Penerimaan Marketplace
                        </x-sidebar.simple-link>
                    @endif

                    @if ($hasJournalsIndex)
                        <x-sidebar.simple-link href="{{ route('accounting.journals.index') }}" icon="bi bi-journal-text"
                            :active="request()->routeIs('accounting.journals.*')">
                            Jurnal
                        </x-sidebar.simple-link>
                    @endif

                    @if ($hasAccountsIndex)
                        <x-sidebar.simple-link href="{{ route('accounting.accounts.index') }}" icon="bi bi-folder2-open"
                            :active="request()->routeIs('accounting.accounts.*')">
                            Akun (COA)
                        </x-sidebar.simple-link>
                    @endif

                    @if ($hasOpeningBalancesIndex)
                        <x-sidebar.simple-link href="{{ route('accounting.opening-balances.index') }}" icon="bi bi-circle-fill"
                            :active="request()->routeIs('accounting.opening-balances.*')">
                            Saldo Awal
                        </x-sidebar.simple-link>
                    @endif

                    @if ($hasOpeningBalancesBatchIndex)
                        <x-sidebar.simple-link href="{{ route('accounting.opening-balances-batch.index') }}" icon="bi bi-basket"
                            :active="request()->routeIs('accounting.opening-balances-batch.*')">
                            Batch Saldo Awal
                        </x-sidebar.simple-link>
                    @endif
                        </div>
                    </div>
                </li>
            @endif

        @elseif ($isOwner)
            {{-- =========================================================
                OWNER (collapsible, grouped)
            ========================================================= --}}

            {{-- MARKETPLACE --}}
            @if ($canShow(
                $hasMarketplaceToko,
                $hasMarketplaceOrders,
                $hasMarketplacePemenuhan,
                $hasMarketplacePickingBarang,
                $hasMarketplaceSkuMapping,
                $hasMarketplaceSync,
                $hasMarketplacePencairanDana,
                $hasMarketplaceIncomeDetail,
                $hasMarketplaceProfit,
                $hasMarketplaceSalesReport,
                $hasMarketplaceProfitReport,
                $hasMarketplaceFinancialStatement,
                $hasMarketplaceFinancialQuality,
                $hasMarketplaceAds,
                $hasMarketplaceAnalytics,
                $hasMarketplaceIssues,
                $hasImportMarketplaceIndex,
                $hasImportMarketplaceDraft,
                $hasImportMarketplaceIncomeIndex,
                $hasImportMarketplaceIncomeDraft
            ))
                <x-sidebar.label text="Toko Online" />
                <li class="mb-1">
                    <button class="sidebar-link sidebar-toggle {{ $openMarketplaceTools ? 'is-open' : '' }}" type="button"
                        data-bs-toggle="collapse" data-bs-target="#navMarketplaceOwner"
                        aria-expanded="{{ $openMarketplaceTools ? 'true' : 'false' }}" aria-controls="navMarketplaceOwner">
                        <span class="icon"><i class="bi bi-cart3"></i></span>
                        <span>Toko Online</span>
                        <span class="chevron">▸</span>
                    </button>

                    <div class="collapse {{ $openMarketplaceTools ? 'show' : '' }}" id="navMarketplaceOwner">
                        @php $subhead('Operasional'); @endphp
                        @if ($hasMarketplaceToko)
                            <x-sidebar.sub-link href="{{ route('marketplace.toko') }}" icon="bi bi-shop"
                                :active="request()->routeIs('marketplace.toko')">
                                Toko & Kanal
                            </x-sidebar.sub-link>

                            <x-sidebar.sub-link href="{{ route('marketplace.returns') }}" icon="bi bi-arrow-return-left"
                                :active="request()->routeIs('marketplace.returns')">
                                Retur
                            </x-sidebar.sub-link>

                            <x-sidebar.sub-link href="{{ route('marketplace.kilat') }}" icon="bi bi-lightning-charge"
                                :active="request()->routeIs('marketplace.kilat')">
                                Pesanan Kilat
                            </x-sidebar.sub-link>

                            <x-sidebar.sub-link href="{{ route('marketplace.settings') }}" icon="bi bi-gear"
                                :active="request()->routeIs('marketplace.settings')">
                                Pengaturan Umum
                            </x-sidebar.sub-link>
                        @endif
                        @if ($hasMarketplaceOrders)
                            <x-sidebar.sub-link href="{{ route('marketplace.orders') }}" icon="bi bi-list-ul"
                                :active="request()->routeIs('marketplace.orders') || request()->routeIs('marketplace.orders.*')">
                                Pesanan Lokal
                            </x-sidebar.sub-link>
                        @endif
                        @if ($router->has('marketplace.products'))
                            <x-sidebar.sub-link href="{{ route('marketplace.products') }}" icon="bi bi-tags"
                                :active="request()->routeIs('marketplace.products')">
                                Produk
                            </x-sidebar.sub-link>
                        @endif
                        @if ($router->has('marketplace.boost'))
                            <x-sidebar.sub-link href="{{ route('marketplace.boost') }}" icon="bi bi-rocket-takeoff"
                                :active="request()->routeIs('marketplace.boost')">
                                Naikkan Produk
                            </x-sidebar.sub-link>
                        @endif
                        @if ($router->has('marketplace.promotions'))
                            <x-sidebar.sub-link href="{{ route('marketplace.promotions') }}" icon="bi bi-percent"
                                :active="request()->routeIs('marketplace.promotions')">
                                Promosi
                            </x-sidebar.sub-link>
                        @endif
                        @if ($hasMarketplacePromotionsSummary)
                            <x-sidebar.sub-link href="{{ route('marketplace.promotions.summary') }}" icon="bi bi-grid-3x3-gap"
                                :active="request()->routeIs('marketplace.promotions.summary')">
                                Summary Promosi
                            </x-sidebar.sub-link>
                        @endif
                        @if ($router->has('marketplace.chat') && $canModule('marketplace'))
                            <x-sidebar.sub-link href="{{ route('marketplace.chat') }}" icon="bi bi-chat-dots"
                                :active="request()->routeIs('marketplace.chat')">
                                Chat <span class="sidebarChatBadge badge bg-danger rounded-pill ms-2" style="display:none;"></span>
                            </x-sidebar.sub-link>
                        @endif
                        @if ($hasMarketplacePemenuhan)
                            <x-sidebar.sub-link href="{{ route('marketplace.fulfillment') }}" icon="bi bi-truck"
                                :active="request()->routeIs('marketplace.fulfillment') || request()->routeIs('marketplace.fulfillment.*')">
                                Fulfillment
                            </x-sidebar.sub-link>
                        @endif
                        @if ($hasMarketplacePickingBarang)
                            <x-sidebar.sub-link href="{{ route('marketplace.picking') }}" icon="bi bi-basket"
                                :active="request()->routeIs('marketplace.picking')">
                                Pengambilan
                            </x-sidebar.sub-link>
                        @endif

                        @php $subhead('Data & Sinkronisasi'); @endphp
                        @if ($hasMarketplaceSkuMapping)
                            <x-sidebar.sub-link href="{{ route('marketplace.sku-mapping') }}" icon="bi bi-link-45deg"
                                :active="request()->routeIs('marketplace.sku-mapping')">
                                Pemetaan SKU
                            </x-sidebar.sub-link>
                        @endif
                        @if ($hasMarketplaceSync)
                            <x-sidebar.sub-link href="{{ route('marketplace.sync') }}" icon="bi bi-arrow-down-circle"
                                :active="request()->routeIs('marketplace.sync')">
                                Sinkron Pesanan
                            </x-sidebar.sub-link>
                        @endif
                        @if ($hasMarketplaceIssues)
                            <x-sidebar.sub-link href="{{ route('marketplace.issues') }}" icon="bi bi-exclamation-triangle"
                                :active="request()->routeIs('marketplace.issues')">
                                Perbaikan Data
                            </x-sidebar.sub-link>
                        @endif

                        @php $subhead('Analisa'); @endphp
                        @if ($hasMarketplacePencairanDana)
                            <x-sidebar.sub-link href="{{ route('marketplace.settlement') }}" icon="bi bi-coin"
                                :active="request()->routeIs('marketplace.settlement')">
                                Pencairan Dana
                            </x-sidebar.sub-link>
                        @endif
                        @if ($hasMarketplaceIncomeDetail)
                            <x-sidebar.sub-link href="{{ route('marketplace.income-detail') }}" icon="bi bi-journal-text"
                                :active="request()->routeIs('marketplace.income-detail')">
                                Rincian Penghasilan
                            </x-sidebar.sub-link>
                        @endif
                        @if ($hasMarketplaceEscrow)
                            <x-sidebar.sub-link href="{{ route('marketplace.escrow') }}" icon="bi bi-safe2"
                                :active="request()->routeIs('marketplace.escrow')">
                                Escrow Shopee
                            </x-sidebar.sub-link>
                        @endif
                        @if ($hasMarketplaceProfit)
                            <x-sidebar.sub-link href="{{ route('marketplace.profit') }}" icon="bi bi-graph-up"
                                :active="request()->routeIs('marketplace.profit')">
                                Laba Pesanan
                            </x-sidebar.sub-link>
                        @endif
                        @if ($hasMarketplaceProfitReport)
                            <x-sidebar.sub-link href="{{ route('marketplace.reports.profit') }}" icon="bi bi-graph-up-arrow"
                                :active="request()->routeIs('marketplace.reports.profit*')">
                                Laporan Keuntungan
                            </x-sidebar.sub-link>
                        @endif
                        @if ($hasMarketplaceFinancialStatement)
                            <x-sidebar.sub-link href="{{ route('marketplace.reports.financial-statement') }}" icon="bi bi-file-earmark-spreadsheet"
                                :active="request()->routeIs('marketplace.reports.financial-statement*')">
                                Laporan Keuangan
                                <span class="badge rounded-pill text-bg-info ms-1">Owner</span>
                            </x-sidebar.sub-link>
                        @endif
                        @if ($hasMarketplaceFinancialClosing)
                            <x-sidebar.sub-link href="{{ route('marketplace.reports.financial-closing') }}" icon="bi bi-lock-fill"
                                :active="request()->routeIs('marketplace.reports.financial-closing*')">
                                Closing & Audit
                                <span class="badge rounded-pill text-bg-warning ms-1">Owner</span>
                            </x-sidebar.sub-link>
                        @endif
                        @if ($hasMarketplaceSalesReport)
                            <x-sidebar.sub-link href="{{ route('marketplace.reports.sales') }}" icon="bi bi-bar-chart-line"
                                :active="request()->routeIs('marketplace.reports.sales')">
                                Laporan Penjualan
                            </x-sidebar.sub-link>
                        @endif
                        @if ($hasMarketplaceFinancialQuality)
                            <x-sidebar.sub-link href="{{ route('marketplace.reports.financial-quality') }}" icon="bi bi-clipboard2-data"
                                :active="request()->routeIs('marketplace.reports.financial-quality*')">
                                Audit Keuangan
                                <span class="badge rounded-pill text-bg-warning ms-1">Owner</span>
                            </x-sidebar.sub-link>
                        @endif
                        @if ($hasMarketplaceAds)
                            <x-sidebar.sub-link href="{{ route('marketplace.ads.dashboard') }}" icon="bi bi-bullseye"
                                :active="request()->routeIs('marketplace.ads.dashboard')">
                                Analisa Iklan
                            </x-sidebar.sub-link>
                        @endif
                        @if ($hasMarketplaceAnalytics)
                            <x-sidebar.sub-link href="{{ route('marketplace.analytics') }}" icon="bi bi-bar-chart"
                                :active="request()->routeIs('marketplace.analytics')">
                                Analisa Penjualan
                            </x-sidebar.sub-link>
                        @endif

                        @if ($hasImportMarketplaceIndex || $hasImportMarketplaceDraft || $hasImportMarketplaceIncomeIndex || $hasImportMarketplaceIncomeDraft)
                            @php $subhead('Impor'); @endphp
                            @if ($hasImportMarketplaceIndex)
                                <x-sidebar.sub-link href="{{ route('imports.marketplace.index') }}" icon="bi bi-upload"
                                    :active="request()->routeIs('imports.marketplace.*')">
                                    Impor Pengiriman
                                </x-sidebar.sub-link>
                            @endif
                            @if ($hasImportMarketplaceDraft)
                                <x-sidebar.sub-link href="{{ route('imports.marketplace.draft') }}" icon="bi bi-clock-history"
                                    :active="request()->routeIs('imports.marketplace.draft')">
                                    Draft Pengiriman
                                </x-sidebar.sub-link>
                            @endif
                            @if ($hasImportMarketplaceIncomeIndex)
                                <x-sidebar.sub-link href="{{ route('imports.marketplace_income.index') }}" icon="bi bi-cash-stack"
                                    :active="request()->routeIs('imports.marketplace_income.*')">
                                    Impor Toko Online Income
                                </x-sidebar.sub-link>
                            @endif
                            @if ($hasImportMarketplaceIncomeDraft)
                                <x-sidebar.sub-link href="{{ route('imports.marketplace_income.draft') }}" icon="bi bi-clock-history"
                                    :active="request()->routeIs('imports.marketplace_income.draft')">
                                    Draft Income
                                </x-sidebar.sub-link>
                            @endif
                        @endif
                            <x-sidebar.sub-link href="{{ route('marketplace.shopee-api-logs') }}" icon="bi bi-hdd-network"
                                :active="request()->routeIs('marketplace.shopee-api-logs')">
                                Log API Shopee
                            </x-sidebar.sub-link>
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
                $hasSalesOperationalSettings,
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
                        <span class="icon"><i class="bi bi-file-earmark-text"></i></span>
                        <span>Operasional Penjualan</span>
                        <span class="chevron">▸</span>
                    </button>

                    <div class="collapse {{ $openSales ? 'show' : '' }}" id="navSales">
                        @php $subhead('Invoice'); @endphp
                        @if ($hasSalesInvoicesIndex)
                            <x-sidebar.sub-link href="{{ route('sales.invoices.index') }}" icon="bi bi-list"
                                :active="request()->routeIs('sales.invoices.index')">
                                Daftar Tagihan
                            </x-sidebar.sub-link>
                        @endif
                        @if ($hasSalesInvoicesCreate)
                            <x-sidebar.sub-link href="{{ route('sales.invoices.create') }}" icon="bi bi-plus-circle"
                                :active="request()->routeIs('sales.invoices.create')">
                                Tagihan Baru
                            </x-sidebar.sub-link>
                        @endif

                        @php $subhead('Pengiriman'); @endphp
                        @if ($hasSalesShipmentsIndex)
                            <x-sidebar.sub-link href="{{ route('sales.shipments.index') }}" icon="bi bi-truck"
                                :active="request()->routeIs('sales.shipments.index')">
                                Daftar Pengiriman
                            </x-sidebar.sub-link>
                        @endif
                        @if ($hasSalesShipmentsCreate)
                            <x-sidebar.sub-link href="{{ route('sales.shipments.create') }}" icon="bi bi-plus-circle"
                                :active="request()->routeIs('sales.shipments.create')">
                                Kirim Baru
                            </x-sidebar.sub-link>
                        @endif
                        @if ($router->has('sales.shipments.manual'))
                            <x-sidebar.sub-link href="{{ route('sales.shipments.manual') }}" icon="bi bi-box-seam"
                                :active="request()->routeIs('sales.shipments.manual')">
                                Kirim Paket Manual
                            </x-sidebar.sub-link>
                        @endif

                        @php $subhead('Returns'); @endphp
                        @if ($hasSalesShipmentReturnsIndex)
                            <x-sidebar.sub-link href="{{ route('sales.shipment_returns.index') }}" icon="bi bi-arrow-repeat"
                                :active="request()->routeIs('sales.shipment_returns.index')">
                                Daftar Retur
                            </x-sidebar.sub-link>
                        @endif

                        @if ($hasSalesOperationalSettings)
                            @php $subhead('Pengaturan'); @endphp
                            <x-sidebar.sub-link href="{{ route('sales.settings.operational') }}" icon="bi bi-sliders"
                                :active="request()->routeIs('sales.settings.operational*')">
                                Pengaturan Operasional
                            </x-sidebar.sub-link>
                        @endif

                        @php $subhead('Laporan'); @endphp
                        @if ($hasSalesReportPerformance)
                            <x-sidebar.sub-link href="{{ route('sales.reports.sales_performance.index') }}" icon="bi bi-graph-up"
                                :active="request()->routeIs('sales.reports.sales_performance.*')">
                                Kinerja Penjualan
                            </x-sidebar.sub-link>
                        @endif
                        @if ($hasSalesShipmentsReport)
                            <x-sidebar.sub-link href="{{ route('sales.reports.shipment') }}" icon="bi bi-bar-chart"
                                :active="request()->routeIs('sales.reports.shipment')">
                                Laporan Pengiriman
                            </x-sidebar.sub-link>
                        @endif
                        @if ($hasSalesReportItemProfit)
                            <x-sidebar.sub-link href="{{ route('sales.reports.item_profit') }}" icon="bi bi-coin"
                                :active="request()->routeIs('sales.reports.item_profit')">
                                Laba per Barang
                            </x-sidebar.sub-link>
                        @endif
                        @if ($hasSalesReportChannelProfit)
                            <x-sidebar.sub-link href="{{ route('sales.reports.channel_profit') }}" icon="bi bi-tag"
                                :active="request()->routeIs('sales.reports.channel_profit')">
                                Laba per Kanal
                            </x-sidebar.sub-link>
                        @endif
                        @if ($hasSalesReportShipmentAnalytics)
                            <x-sidebar.sub-link href="{{ route('sales.reports.shipment_analytics') }}" icon="bi bi-cpu"
                                :active="request()->routeIs('sales.reports.shipment_analytics')">
                                Analisa Pengiriman
                            </x-sidebar.sub-link>
                        @endif
                    </div>
                </li>
            @endif

            {{-- INVENTORY --}}
            @if ($canShow(
                $hasInvIntelligence,
                $hasWhIntelligence,
                $hasInvStocksItems,
                $hasInvStocksLots,
                $hasInvStockCard,
                $hasInvTransfersIndex,
                $hasInvTransfersCreate,
                $hasInvAdjustmentsIndex,
                $hasInvWipAdjIndex,
                $hasInvWipCutReconcile,
                $hasInvOpnamesIndex,
                $hasInvOpnamesCreate,
                $hasInvExternalIndex,
                $hasInvExternalCreate
            ))
                <x-sidebar.label text="Persediaan" />
                <li class="mb-1">
                    <button class="sidebar-link sidebar-toggle {{ $openPersediaan ? 'is-open' : '' }}" type="button"
                        data-bs-toggle="collapse" data-bs-target="#navPersediaan"
                        aria-expanded="{{ $openPersediaan ? 'true' : 'false' }}" aria-controls="navPersediaan">
                        <span class="icon"><i class="bi bi-box-seam"></i></span>
                        <span>Persediaan</span>
                        <span class="chevron">▸</span>
                    </button>

                    <div class="collapse {{ $openPersediaan ? 'show' : '' }}" id="navPersediaan">
                        @if ($hasInvIntelligence || $hasWhIntelligence)
                            @php $subhead('Ringkasan'); @endphp
                            @if ($hasInvIntelligence)
                                <x-sidebar.sub-link href="{{ route('inventory.intelligence') }}" icon="bi bi-cpu"
                                    :active="request()->routeIs('inventory.intelligence') || request()->routeIs('inventory.intelligence.*')">
                                    Ringkasan Stok
                                </x-sidebar.sub-link>
                            @endif
                            @if ($hasWhIntelligence)
                                <x-sidebar.sub-link href="{{ route('inventory.warehouse_intelligence') }}" icon="bi bi-box-seam"
                                    :active="request()->routeIs('inventory.warehouse_intelligence') || request()->routeIs('inventory.warehouse_intelligence.*')">
                                    Stok Gudang
                                </x-sidebar.sub-link>
                            @endif
                        @endif

                        @php $subhead('Stok'); @endphp
                        @if ($hasInvStocksItems)
                            <x-sidebar.sub-link href="{{ route('inventory.stocks.items') }}" icon="bi bi-box-seam"
                                :active="request()->routeIs('inventory.stocks.items')">
                                Stok Barang
                            </x-sidebar.sub-link>
                        @endif
                        @if ($hasInvStocksLots)
                            <x-sidebar.sub-link href="{{ route('inventory.stocks.lots') }}" icon="bi bi-ticket"
                                :active="request()->routeIs('inventory.stocks.lots')">
                                Stok per LOT
                            </x-sidebar.sub-link>
                        @endif
                        @if ($hasInvStockCard)
                            <x-sidebar.sub-link href="{{ route('inventory.stock_card.index') }}" icon="bi bi-list-ul"
                                :active="request()->routeIs('inventory.stock_card.*')">
                                Kartu Stok
                            </x-sidebar.sub-link>
                        @endif
                        @if ($hasInvBarcodes)
                            <x-sidebar.sub-link href="{{ route('inventory.barcodes.create') }}" icon="bi bi-upc-scan"
                                :active="request()->routeIs('inventory.barcodes.*')">
                                Cetak Barcode
                            </x-sidebar.sub-link>
                        @endif

                        @if ($hasInvTransfersIndex || $hasInvTransfersCreate || $hasInvAdjustmentsIndex)
                            @php $subhead('Operasional'); @endphp
                        @endif
                        @if ($hasInvTransfersIndex)
                            <x-sidebar.sub-link href="{{ route('inventory.transfers.index') }}" icon="bi bi-arrow-repeat"
                                :active="request()->routeIs('inventory.transfers.index')">
                                Daftar Transfer
                            </x-sidebar.sub-link>
                        @endif
                        @if ($hasInvTransfersCreate)
                            <x-sidebar.sub-link href="{{ route('inventory.transfers.create') }}" icon="bi bi-plus-circle"
                                :active="request()->routeIs('inventory.transfers.create')">
                                Buat Transfer
                            </x-sidebar.sub-link>
                        @endif
                        @if ($hasInvAdjustmentsIndex)
                            <x-sidebar.sub-link href="{{ route('inventory.adjustments.index') }}" icon="bi bi-scale"
                                :active="request()->routeIs('inventory.adjustments.*')">
                                Koreksi Persediaan
                            </x-sidebar.sub-link>
                        @endif

                        @if ($hasInvWipAdjIndex || $hasInvWipCutReconcile)
                            @php $subhead('Koreksi'); @endphp
                        @endif
                        @if ($hasInvWipAdjIndex)
                            <x-sidebar.sub-link href="{{ route('inventory.wip_adjustments.index') }}" icon="bi bi-receipt"
                                :active="request()->routeIs('inventory.wip_adjustments.*')">
                                Koreksi WIP
                            </x-sidebar.sub-link>
                        @endif

                        @if ($hasInvWipCutReconcile)
                            <x-sidebar.sub-link href="{{ route('inventory.wip_cut_reconcile.index') }}" icon="bi bi-search"
                                :active="request()->routeIs('inventory.wip_cut_reconcile.*')">
                                Rekonsiliasi WIP-CUT
                            </x-sidebar.sub-link>
                        @endif

                        @php $subhead('Opname'); @endphp
                        @if ($hasInvOpnamesIndex)
                            <x-sidebar.sub-link href="{{ route('inventory.stock_opnames.index') }}" icon="bi bi-bar-chart"
                                :active="request()->routeIs('inventory.stock_opnames.*')">
                                Stok Opname
                            </x-sidebar.sub-link>
                        @endif
                        @if ($hasInvOpnamesCreate)
                            <x-sidebar.sub-link href="{{ route('inventory.stock_opnames.create') }}" icon="bi bi-plus-circle"
                                :active="request()->routeIs('inventory.stock_opnames.create')">
                                Stok Opname Baru
                            </x-sidebar.sub-link>
                        @endif

                        @php $subhead('Eksternal'); @endphp
                        @if ($hasInvExternalIndex)
                            <x-sidebar.sub-link href="{{ route('inventory.external_transfers.index') }}" icon="bi bi-truck"
                                :active="request()->routeIs('inventory.external_transfers.index')">
                                Daftar Transfer Eksternal
                            </x-sidebar.sub-link>
                        @endif
                        @if ($hasInvExternalCreate)
                            <x-sidebar.sub-link href="{{ route('inventory.external_transfers.create') }}" icon="bi bi-plus-circle"
                                :active="request()->routeIs('inventory.external_transfers.create')">
                                Transfer Eksternal Baru
                            </x-sidebar.sub-link>
                        @endif
                    </div>
                </li>
            @endif

            {{-- STOCK REQUESTS --}}
            @if ($canViewRts && $canShow($hasRtsStockReqIndex, $hasRtsDirectReceiveIndex))
                <x-sidebar.label text="Permintaan Stok" />
                <li class="mb-1">
                    <button class="sidebar-link sidebar-toggle {{ $openStockRequests ? 'is-open' : '' }}" type="button"
                        data-bs-toggle="collapse" data-bs-target="#navStockRequests"
                        aria-expanded="{{ $openStockRequests ? 'true' : 'false' }}"
                        aria-controls="navStockRequests">
                        <span class="icon"><i class="bi bi-box-arrow-up-right"></i></span>
                        <span>Permintaan Stok</span>
                        <span class="chevron">▸</span>
                    </button>

                    <div class="collapse {{ $openStockRequests ? 'show' : '' }}" id="navStockRequests">
                        @if ($hasRtsStockReqIndex)
                            <x-sidebar.sub-link href="{{ route('rts.stock-requests.index') }}" icon="bi bi-cart3"
                                :active="request()->routeIs('rts.stock-requests.*')"
                                :dot-only="$canManageRts && $hasRtsNeedReceive"
                                badge-tone="warn"
                                :badge-title="$rtsBadgeTitle">
                                Permintaan Stok (RTS)
                            </x-sidebar.sub-link>
                        @endif

                        @if ($canManageRts && $hasRtsDirectReceiveIndex)
                            <x-sidebar.sub-link href="{{ route('rts.direct-receives.index') }}" icon="bi bi-lightning"
                                :active="request()->routeIs('rts.direct-receives.*')">
                                Setor Jahit Dadakan
                            </x-sidebar.sub-link>
                        @endif
                    </div>
                </li>
            @endif

            {{-- PURCHASING --}}
            @if ($canShow($hasPoIndex, $hasPoCreate, $hasGrnIndex, $hasGrnCreate, $hasPurchaseReturnIndex, $hasPrIndex, $hasMaterialShortageIndex, $hasSupplierItemsIndex))
                <x-sidebar.label text="Pengadaan" />
                <li class="mb-1">
                    <button class="sidebar-link sidebar-toggle {{ $openPembelian ? 'is-open' : '' }}" type="button"
                        data-bs-toggle="collapse" data-bs-target="#navPembelian"
                        aria-expanded="{{ $openPembelian ? 'true' : 'false' }}" aria-controls="navPembelian">
                        <span class="icon"><i class="bi bi-receipt"></i></span>
                        <span>Pengadaan</span>
                        <span class="chevron">▸</span>
                    </button>

                    <div class="collapse {{ $openPembelian ? 'show' : '' }}" id="navPembelian">
                        @if ($hasPurchasingDashboard && ($isOwner || in_array($role ?? '', ['admin', 'accounting'], true)))
                            <x-sidebar.sub-link href="{{ route('purchasing.dashboard') }}" icon="bi bi-bar-chart"
                                :active="request()->routeIs('purchasing.dashboard')">
                                Beranda
                            </x-sidebar.sub-link>
                        @endif

                        @php $subhead('Permintaan Pembelian'); @endphp
                        @if ($hasMaterialShortageIndex)
                            <x-sidebar.sub-link href="{{ route('purchasing.material_shortages.index') }}" icon="bi bi-exclamation-triangle"
                                :active="request()->routeIs('purchasing.material_shortages.*')">
                                Kekurangan Bahan
                            </x-sidebar.sub-link>
                        @endif
                        @if ($hasPrIndex)
                            <x-sidebar.sub-link href="{{ route('purchasing.purchase_requests.index') }}" icon="bi bi-list-ul"
                                :active="request()->routeIs('purchasing.purchase_requests.*')">
                                Permintaan Beli
                            </x-sidebar.sub-link>
                        @endif
                        @if ($hasSupplierItemsIndex)
                            <x-sidebar.sub-link href="{{ route('purchasing.supplier_items.index') }}" icon="bi bi-list"
                                :active="request()->routeIs('purchasing.supplier_items.*')">
                                Daftar Pemasok
                            </x-sidebar.sub-link>
                        @endif

                        @php $subhead('Purchase Order'); @endphp
                        @if ($hasPoIndex)
                            <x-sidebar.sub-link href="{{ route('purchasing.purchase_orders.index') }}" icon="bi bi-list"
                                :active="request()->routeIs('purchasing.purchase_orders.index')">
                                Daftar PO
                            </x-sidebar.sub-link>
                        @endif
                        @if ($hasPoCreate)
                            <x-sidebar.sub-link href="{{ route('purchasing.purchase_orders.create') }}" icon="bi bi-plus-circle"
                                :active="request()->routeIs('purchasing.purchase_orders.create')">
                                Buat PO
                            </x-sidebar.sub-link>
                        @endif

                        @php $subhead('Penerimaan Barang (GRN)'); @endphp
                        @if ($hasGrnIndex)
                            <x-sidebar.sub-link href="{{ route('purchasing.purchase_receipts.index') }}" icon="bi bi-list"
                                :active="request()->routeIs('purchasing.purchase_receipts.index')">
                                Daftar Penerimaan
                            </x-sidebar.sub-link>
                        @endif
                        @if ($hasGrnCreate)
                            <x-sidebar.sub-link href="{{ route('purchasing.purchase_receipts.create') }}" icon="bi bi-plus-circle"
                                :active="request()->routeIs('purchasing.purchase_receipts.create')">
                                Buat Penerimaan
                            </x-sidebar.sub-link>
                        @endif
                        @if ($hasPurchaseReturnIndex)
                            <x-sidebar.sub-link href="{{ route('purchasing.purchase_returns.index') }}" icon="bi bi-arrow-return-left"
                                :active="request()->routeIs('purchasing.purchase_returns.*')">
                                Retur Pembelian
                            </x-sidebar.sub-link>
                        @endif

                        @if ($hasSupplierInvoiceIndex && ($isOwner || in_array($role ?? '', ['accounting', 'developer'])))
                            @php $subhead('Faktur Supplier'); @endphp
                            <x-sidebar.sub-link href="{{ route('purchasing.supplier_invoices.index') }}" icon="bi bi-receipt"
                                :active="request()->routeIs('purchasing.supplier_invoices.*')">
                                Tagihan Pemasok
                            </x-sidebar.sub-link>
                        @endif

                        @if ($hasPurchasePaymentsIndex && $isOwner)
                            @php $subhead('Pembayaran Supplier'); @endphp
                            <x-sidebar.sub-link href="{{ route('purchasing.purchase_payments.index') }}" icon="bi bi-cash"
                                :active="request()->routeIs('purchasing.purchase_payments.*')">
                                Bayar Pemasok
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
                <x-sidebar.label text="Produksi" />
                <li class="mb-1">
                    <button class="sidebar-link sidebar-toggle {{ $openProduction ? 'is-open' : '' }}" type="button"
                        data-bs-toggle="collapse" data-bs-target="#navProduction"
                        aria-expanded="{{ $openProduction ? 'true' : 'false' }}" aria-controls="navProduction">
                        <span class="icon"><i class="bi bi-building"></i></span>
                        <span>Produksi</span>
                        <span class="chevron">▸</span>
                    </button>

                    <div class="collapse {{ $openProduction ? 'show' : '' }}" id="navProduction">
                        @php $subhead('Monitoring'); @endphp
                        @if ($hasProdDashboard)
                            <x-sidebar.sub-link href="{{ route('production.dashboard') }}" icon="bi bi-bar-chart"
                                :active="request()->routeIs('production.dashboard')">
                                Beranda Produksi
                            </x-sidebar.sub-link>
                        @endif
                        @if ($hasProdPriorityIndex)
                            <x-sidebar.sub-link href="{{ route('production.priority.index') }}" icon="bi bi-bullseye"
                                :active="request()->routeIs('production.priority.*')">
                                Prioritas Produksi
                            </x-sidebar.sub-link>
                        @endif
                        @if ($hasProdReportsIndex)
                            <x-sidebar.sub-link href="{{ route('production.reports.index') }}" icon="bi bi-graph-up"
                                :active="request()->routeIs('production.reports.*')">
                                Laporan Produksi
                            </x-sidebar.sub-link>
                        @endif

                        @if ($hasProdLog)
                            <x-sidebar.sub-link href="{{ route('production.log.index') }}" icon="bi bi-list-columns-reverse"
                                :active="request()->routeIs('production.log.*')">
                                Log Produksi
                            </x-sidebar.sub-link>
                        @endif

                        @if ($hasProdReconcile)
                            <x-sidebar.sub-link href="{{ route('production.reconcile.index') }}" icon="bi bi-search"
                                :active="request()->routeIs('production.reconcile.*')">
                                Rekon Biaya
                            </x-sidebar.sub-link>
                        @endif

                        @php $subhead('Alur Produksi'); @endphp
                        @if ($hasProdCuttingJobsIndex)
                            <x-sidebar.sub-link href="{{ route('production.cutting_jobs.index') }}" icon="bi bi-scissors"
                                :active="request()->routeIs('production.cutting_jobs.*')">
                                Pekerjaan Potong
                            </x-sidebar.sub-link>
                        @endif
                        @if ($hasProdSewPickupsIndex)
                            <x-sidebar.sub-link href="{{ route('production.sewing.pickups.index') }}" icon="bi bi-send"
                                :active="request()->routeIs('production.sewing.pickups.*')">
                                Ambil Jahit
                            </x-sidebar.sub-link>
                        @endif
                        @if ($hasProdSewReturnsIndex)
                            <x-sidebar.sub-link href="{{ route('production.sewing.returns.index') }}" icon="bi bi-inbox"
                                :active="request()->routeIs('production.sewing.returns.index')">
                                Setor Jahit
                            </x-sidebar.sub-link>
                        @endif
                        @if ($hasProdSewRejectReturnsIndex)
                            <x-sidebar.sub-link href="{{ route('production.sewing.reject_returns.index') }}" icon="bi bi-arrow-clockwise"
                                :active="request()->routeIs('production.sewing.reject_returns.*')">
                                Setor Reject Jahit
                            </x-sidebar.sub-link>
                        @endif
                        @if ($hasProdQcIndex)
                            <x-sidebar.sub-link href="{{ $prodQcHref }}" icon="bi bi-check-circle"
                                :active="request()->routeIs('production.qc.*')">
                                {{ $prodQcLabel }}
                            </x-sidebar.sub-link>
                        @endif
                        @if ($hasProdFinishingJobsIndex)
                            <x-sidebar.sub-link href="{{ route('production.finishing_jobs.index') }}" icon="bi bi-patch-check"
                                :active="request()->routeIs('production.finishing_jobs.*')">
                                Finishing
                            </x-sidebar.sub-link>
                        @endif
                        @if ($hasProdFinishingRepairsIndex)
                            <x-sidebar.sub-link href="{{ route('production.finishing_repairs.index') }}" icon="bi bi-tools"
                                :active="request()->routeIs('production.finishing_repairs.*')">
                                Perbaikan Finishing
                            </x-sidebar.sub-link>
                        @endif
                        @if ($hasProdPackingIndex)
                            <x-sidebar.sub-link href="{{ route('production.packing_jobs.index') }}" icon="bi bi-box-seam"
                                :active="request()->routeIs('production.packing_jobs.*')">
                                Packing
                            </x-sidebar.sub-link>
                        @endif

                        @if ($hasProdWipNormalization || $hasProdWipCleanup)
                            @php $subhead('Normalisasi & Cleanup WIP'); @endphp
                        @endif
                        @if ($hasProdWipNormalization)
                            <x-sidebar.sub-link href="{{ route('production.wip_normalization.index') }}" icon="bi bi-clipboard-check"
                                :active="request()->routeIs('production.wip_normalization.*')">
                                Normalisasi WIP
                            </x-sidebar.sub-link>
                        @endif
                        @if ($hasProdWipCleanup)
                            <x-sidebar.sub-link href="{{ route('production.wip_cleanup.index') }}" icon="bi bi-eraser"
                                :active="request()->routeIs('production.wip_cleanup.*')">
                                Bersihkan WIP
                            </x-sidebar.sub-link>
                        @endif
                    </div>
                </li>
            @endif

            {{-- FINANCE --}}
            @if ($canShow($hasOpeningBalancesIndex, $hasOpeningBalancesBatchIndex, $hasCashExpensesIndex, $hasCashReceiptsIndex, $hasCashBasisReportIndex, $hasJournalsIndex, $hasAccountsIndex))
                <x-sidebar.label text="Keuangan" />
                <li class="mb-1">
                    <button class="sidebar-link sidebar-toggle {{ $openAccounting ? 'is-open' : '' }}" type="button"
                        data-bs-toggle="collapse" data-bs-target="#navAccounting"
                        aria-expanded="{{ $openAccounting ? 'true' : 'false' }}" aria-controls="navAccounting">
                        <span class="icon"><i class="bi bi-receipt"></i></span>
                        <span>Akuntansi</span>
                        <span class="chevron">▸</span>
                    </button>

                    <div class="collapse {{ $openAccounting ? 'show' : '' }}" id="navAccounting">
                        @if ($hasCashBasisReportIndex)
                            <x-sidebar.sub-link href="{{ route('accounting.cash-basis-report.index') }}" icon="bi bi-bar-chart"
                                :active="request()->routeIs('accounting.cash-basis-report.*')">
                                Laporan Kas
                            </x-sidebar.sub-link>
                        @endif

                        @if ($hasOpeningBalancesIndex)
                            <x-sidebar.sub-link href="{{ route('accounting.opening-balances.index') }}" icon="bi bi-circle-fill"
                                :active="request()->routeIs('accounting.opening-balances.*')">
                                Saldo Awal
                            </x-sidebar.sub-link>
                        @endif

                        @if ($hasOpeningBalancesBatchIndex)
                            <x-sidebar.sub-link href="{{ route('accounting.opening-balances-batch.index') }}" icon="bi bi-basket"
                                :active="request()->routeIs('accounting.opening-balances-batch.*')">
                                Batch Saldo Awal
                            </x-sidebar.sub-link>
                        @endif

                        @if ($hasCashExpensesIndex)
                            <x-sidebar.sub-link href="{{ route('accounting.cash-expenses.index') }}" icon="bi bi-cash"
                                :active="request()->routeIs('accounting.cash-expenses.*')">
                                Pengeluaran Kas
                            </x-sidebar.sub-link>
                        @endif

                        @if ($hasCashReceiptsIndex)
                            <x-sidebar.sub-link href="{{ route('accounting.cash-receipts.index') }}" icon="bi bi-coin"
                                :active="request()->routeIs('accounting.cash-receipts.*')">
                                Penerimaan Kas
                            </x-sidebar.sub-link>
                        @endif

                        @if ($hasMarketplacePayoutsIndex)
                            <x-sidebar.sub-link href="{{ route('accounting.marketplace-payouts.index') }}" icon="bi bi-cart3"
                                :active="request()->routeIs('accounting.marketplace-payouts.*')">
                                Penerimaan Marketplace
                            </x-sidebar.sub-link>
                        @endif

                        @if ($hasJournalsIndex)
                            <x-sidebar.sub-link href="{{ route('accounting.journals.index') }}" icon="bi bi-journal-text"
                                :active="request()->routeIs('accounting.journals.*')">
                                Jurnal
                            </x-sidebar.sub-link>
                        @endif

                        @if ($hasAccountsIndex)
                            <x-sidebar.sub-link href="{{ route('accounting.accounts.index') }}" icon="bi bi-folder2-open"
                                :active="request()->routeIs('accounting.accounts.index') || request()->routeIs('accounting.accounts.create') || request()->routeIs('accounting.accounts.edit') || request()->routeIs('accounting.accounts.show')">
                                Akun (COA)
                            </x-sidebar.sub-link>
                        @endif

                        @if ($hasBukuBesarIndex)
                            <x-sidebar.sub-link href="{{ route('accounting.buku-besar.index') }}" icon="bi bi-book"
                                :active="request()->routeIs('accounting.buku-besar.*') || request()->routeIs('accounting.accounts.ledger')">
                                Buku Besar
                            </x-sidebar.sub-link>
                        @endif

                        @if ($hasTrialBalanceIndex)
                            <x-sidebar.sub-link href="{{ route('accounting.trial-balance.index') }}" icon="bi bi-scale"
                                :active="request()->routeIs('accounting.trial-balance.*')">
                                Neraca Saldo
                            </x-sidebar.sub-link>
                        @endif

                        @if ($hasProfitLossIndex)
                            <x-sidebar.sub-link href="{{ route('accounting.profit-loss.index') }}" icon="bi bi-graph-up"
                                :active="request()->routeIs('accounting.profit-loss.*')">
                                Laba Rugi
                            </x-sidebar.sub-link>
                        @endif

                        @if ($hasApReportIndex)
                            <x-sidebar.sub-link href="{{ route('accounting.ap-report.index') }}" icon="bi bi-receipt"
                                :active="request()->routeIs('accounting.ap-report.*')">
                                Hutang Dagang
                            </x-sidebar.sub-link>
                        @endif
                    </div>
                </li>
            @endif

            {{-- COSTING --}}
            @if ($canShow($hasHppIndex, $hasProdCostPeriodsIndex))
                <x-sidebar.label text="Biaya Produksi" />
                <li class="mb-1">
                    <button class="sidebar-link sidebar-toggle {{ $openCosting ? 'is-open' : '' }}" type="button"
                        data-bs-toggle="collapse" data-bs-target="#navCosting"
                        aria-expanded="{{ $openCosting ? 'true' : 'false' }}" aria-controls="navCosting">
                        <span class="icon"><i class="bi bi-graph-down"></i></span>
                        <span>Biaya Produksi &amp; HPP</span>
                        <span class="chevron">▸</span>
                    </button>

                    <div class="collapse {{ $openCosting ? 'show' : '' }}" id="navCosting">
                        @if ($hasHppIndex)
                            <x-sidebar.sub-link href="{{ route('costing.hpp.index') }}" icon="bi bi-gear"
                                :active="request()->routeIs('costing.hpp.*')">
                                HPP Barang Jadi
                            </x-sidebar.sub-link>
                        @endif

                        @if ($hasProdCostPeriodsIndex)
                            <x-sidebar.sub-link href="{{ route('costing.production_cost_periods.index') }}" icon="bi bi-calendar3"
                                :active="request()->routeIs('costing.production_cost_periods.*')">
                                Periode Produksi
                            </x-sidebar.sub-link>
                        @endif
                    </div>
                </li>
            @endif

            {{-- TOOLS (owner-only) --}}
            @if ($isOwner && $router->has('tools.pricing-calculator'))
                <x-sidebar.label text="Tools" />
                <li>
                    <x-sidebar.simple-link href="{{ route('tools.pricing-calculator') }}" icon="bi bi-calculator"
                        :active="request()->routeIs('tools.pricing-calculator')">
                        Pricing &amp; ROAS Calculator
                    </x-sidebar.simple-link>
                </li>
            @endif

{{-- PAYROLL --}}
            @if ($canShow($hasPayrollDashboard, $hasPieceworkIndex, $hasPieceRatesIndex, $hasPayrollReportsOperators))
                <x-sidebar.label text="Penggajian" />
                <li class="mb-1">
                    <button class="sidebar-link sidebar-toggle {{ $openPayroll ? 'is-open' : '' }}" type="button"
                        data-bs-toggle="collapse" data-bs-target="#navPayroll"
                        aria-expanded="{{ $openPayroll ? 'true' : 'false' }}" aria-controls="navPayroll">
                        <span class="icon"><i class="bi bi-coin"></i></span>
                        <span>Penggajian</span>
                        <span class="chevron">▸</span>
                    </button>

                    <div class="collapse {{ $openPayroll ? 'show' : '' }}" id="navPayroll">
                        @if ($hasPayrollDashboard)
                            <x-sidebar.sub-link href="{{ route('payroll.dashboard') }}" icon="bi bi-graph-up"
                                :active="request()->routeIs('payroll.dashboard*')">
                                Beranda
                            </x-sidebar.sub-link>
                        @endif

                        @if ($hasPieceworkIndex)
                            <x-sidebar.sub-link href="{{ route('payroll.piecework.index', ['module' => 'cutting']) }}" icon="bi bi-scissors"
                                :active="$pieceworkCuttingActive">
                                Gaji Potong
                            </x-sidebar.sub-link>

                            <x-sidebar.sub-link href="{{ route('payroll.piecework.index', ['module' => 'sewing']) }}" icon="bi bi-send"
                                :active="$pieceworkSewingActive">
                                Gaji Jahit (Ambil)
                            </x-sidebar.sub-link>
                        @endif

                        @if ($hasPieceRatesIndex)
                            <x-sidebar.sub-link href="{{ route('payroll.piece_rates.index') }}" icon="bi bi-file-earmark-text"
                                :active="request()->routeIs('payroll.piece_rates.*')">
                                Tarif Borongan
                            </x-sidebar.sub-link>
                        @endif

                        @if ($hasPayrollReportsOperators)
                            <x-sidebar.sub-link href="{{ route('payroll.reports.operators') }}" icon="bi bi-bar-chart"
                                :active="request()->routeIs('payroll.reports.*')">
                                Laporan
                            </x-sidebar.sub-link>
                        @endif
                    </div>
                </li>
            @endif

            {{-- MASTER DATA --}}
            <x-sidebar.label text="Data Master" />
            <li class="mb-1">
                <button class="sidebar-link sidebar-toggle {{ $openMaster ? 'is-open' : '' }}" type="button"
                    data-bs-toggle="collapse" data-bs-target="#navMaster"
                    aria-expanded="{{ $openMaster ? 'true' : 'false' }}" aria-controls="navMaster">
                    <span class="icon"><i class="bi bi-folder2-open"></i></span>
                    <span>Data Master</span>
                    <span class="chevron">▸</span>
                </button>

                <div class="collapse {{ $openMaster ? 'show' : '' }}" id="navMaster">
                    @if ($hasMasterItemsIndex)
                        <x-sidebar.sub-link href="{{ route('master.items.index') }}" icon="bi bi-box-seam"
                            :active="request()->routeIs('master.items.*')">
                            Barang
                        </x-sidebar.sub-link>
                    @endif

                    @if ($hasMasterItemCategoriesIndex)
                        <x-sidebar.sub-link href="{{ route('master.item_categories.index') }}" icon="bi bi-folder2-open"
                            :active="request()->routeIs('master.item_categories.*')">
                            Kategori Barang
                        </x-sidebar.sub-link>
                    @endif

                    @if ($hasMasterItemBomsIndex)
                        <x-sidebar.sub-link href="{{ route('master.item_boms.index') }}" icon="bi bi-receipt"
                            :active="request()->routeIs('master.item_boms.*')">
                            BOM / Resep
                        </x-sidebar.sub-link>
                    @endif

                    @if ($hasMasterSuppliersIndex)
                        <x-sidebar.sub-link href="{{ route('master.suppliers.index') }}" icon="bi bi-tag"
                            :active="request()->routeIs('master.suppliers.*')">
                            Pemasok
                        </x-sidebar.sub-link>
                    @endif

                    @if ($hasMasterCustomersIndex)
                        <x-sidebar.sub-link href="{{ route('master.customers.index') }}" icon="bi bi-person"
                            :active="request()->routeIs('master.customers.*')">
                            Pelanggan
                        </x-sidebar.sub-link>
                    @endif

                    @if ($hasMasterEmployeesIndex)
                        <x-sidebar.sub-link href="{{ route('master.employees.index') }}" icon="bi bi-person-fill-gear"
                            :active="request()->routeIs('master.employees.*')">
                            Karyawan
                        </x-sidebar.sub-link>
                    @endif
                </div>
            </li>

                    @endif
    </ul>
</aside>
