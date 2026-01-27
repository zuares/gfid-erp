{{-- resources/views/layouts/partials/sidebar.blade.php --}}
@php
    use Illuminate\Support\Facades\Cache;
    use Illuminate\Support\Facades\DB;

    $user = auth()->user();
    $role = strtolower((string) ($user->role ?? ''));

    $isOwner = $role === 'owner';
    $isOperating = $role === 'operating';
    $isAdmin = $role === 'admin';

    // ✅ capability flags
    // NOTE: Operating boleh LIHAT RTS, tapi tidak boleh "manage" (create/approve dll) kecuali kamu ubah di controller/policy
    $canViewRts = $isOwner || $isAdmin || $isOperating;
    $canManageRts = $isOwner || $isAdmin;

    $router = app('router');

    // =========================================================
    // ROUTE GUARDS (biar tidak error kalau route belum ada)
    // =========================================================
    $hasDashboardRoute = $router->has('dashboard');

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

    // RTS Stock Requests
    $hasRtsStockReqIndex = $router->has('rts.stock-requests.index');

    // ✅ RTS Direct Receives (Dadakan)
    $hasRtsDirectReceiveIndex = $router->has('rts.direct-receives.index');

    // Sales (admin)
    $hasSalesShipmentsIndex = $router->has('sales.shipments.index');
    $hasSalesShipmentReturnsIndex = $router->has('sales.shipment_returns.index');

    // Master (owner)
    $hasMasterItemsIndex = $router->has('master.items.index');
    $hasMasterCustomersIndex = $router->has('master.customers.index');
    $hasMasterSuppliersIndex = $router->has('master.suppliers.index');
    // ✅ BOM SKU
    $hasMasterItemBomsIndex = $router->has('master.item_boms.index');

    // Purchasing (owner)
    $hasPoIndex = $router->has('purchasing.purchase_orders.index');
    $hasPoCreate = $router->has('purchasing.purchase_orders.create');
    $hasGrnIndex = $router->has('purchasing.purchase_receipts.index');
    $hasGrnCreate = $router->has('purchasing.purchase_receipts.create');

    // Marketplace (owner)
    $hasMarketplaceIndex = $router->has('marketplace.orders.index');
    $hasMarketplaceCreate = $router->has('marketplace.orders.create');

    // Sales owner
    $hasSalesInvoicesIndex = $router->has('sales.invoices.index');
    $hasSalesInvoicesCreate = $router->has('sales.invoices.create');
    $hasSalesShipmentsCreate = $router->has('sales.shipments.create');
    $hasSalesShipmentReturnsCreate = $router->has('sales.shipment_returns.create');
    $hasSalesShipmentsReport = $router->has('sales.shipments.report');

    // ✅ Sales reports routes (guard)
    $hasSalesReportPerformance = $router->has('sales.reports.sales_performance.index');
    $hasSalesReportItemProfit = $router->has('sales.reports.item_profit');
    $hasSalesReportChannelProfit = $router->has('sales.reports.channel_profit');
    $hasSalesReportShipmentAnalytics = $router->has('sales.reports.shipment_analytics');

    // Production (operating/owner)
    $hasProdCuttingJobsIndex = $router->has('production.cutting_jobs.index');
    $hasProdSewPickupsIndex = $router->has('production.sewing.pickups.index');
    $hasProdSewReturnsIndex = $router->has('production.sewing.returns.index');
    $hasProdFinishingJobsIndex = $router->has('production.finishing_jobs.index');
    $hasProdWipFinAdjIndex = $router->has('production.wip-fin-adjustments.index');
    $hasProdQcIndex = $router->has('production.qc.index');

    // Production reports (owner)
    $hasProdReportDashboard = $router->has('production.reports.dashboard');
    $hasProdReportOutstanding = $router->has('production.reports.outstanding');
    $hasProdReportAgingWipSew = $router->has('production.reports.aging_wip_sew');
    $hasProdReportFlow = $router->has('production.reports.production_flow_dashboard');
    $hasProdReportDaily = $router->has('production.reports.daily_production');

    // Finance (admin + owner)
    $hasCashExpensesIndex = $router->has('accounting.cash-expenses.index');
    $hasJournalsIndex = $router->has('accounting.journals.index');
    $hasAccountsIndex = $router->has('accounting.accounts.index');
    $hasOpeningBalancesIndex = $router->has('accounting.opening-balances.index');

    // Payroll (owner)
    $hasPieceworkIndex = $router->has('payroll.piecework.index');
    $hasPieceRatesIndex = $router->has('payroll.piece_rates.index');
    $hasPayrollReportsOperators = $router->has('payroll.reports.operators');

    // Costing (owner)
    $hasHppIndex = $router->has('costing.hpp.index');
    $hasProdCostPeriodsIndex = $router->has('costing.production_cost_periods.index');

    // =========================================================
    // OPEN STATES
    // =========================================================
    $poOpen = request()->routeIs('purchasing.purchase_orders.*');
    $grnOpen = request()->routeIs('purchasing.purchase_receipts.*');

    $masterOpen = request()->routeIs('master.*');
    $marketplaceOpen = request()->routeIs('marketplace.orders.*');

    $salesInvoiceOpen = request()->routeIs('sales.invoices.*');
    $salesShipmentOpen = request()->routeIs('sales.shipments.*');
    $salesShipmentReturnOpen = request()->routeIs('sales.shipment_returns.*');

    // ✅ reports open when any sales report OR shipments.report
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

    // ✅ stock requests open if RTS request OR direct receive
    $stockReqOpen = request()->routeIs('rts.stock-requests.*') || request()->routeIs('rts.direct-receives.*');

    $prodOpen =
        request()->routeIs('production.cutting_jobs.*') ||
        request()->routeIs('production.sewing.*') ||
        request()->routeIs('production.finishing_jobs.*') ||
        request()->routeIs('production.wip-fin-adjustments.*') ||
        request()->routeIs('production.qc.*') ||
        request()->routeIs('production.reports.*') ||
        request()->routeIs('production.packing_jobs.*');

    $accountingOpen =
        request()->routeIs('accounting.cash-expenses.*') ||
        request()->routeIs('accounting.opening-balances.*') ||
        request()->routeIs('accounting.journals.*') ||
        request()->routeIs('accounting.accounts.*');

    $payrollOpen =
        request()->routeIs('payroll.piecework.*') ||
        request()->routeIs('payroll.piece_rates.*') ||
        request()->routeIs('payroll.reports.*');

    $costingOpen = request()->routeIs('costing.hpp.*') || request()->routeIs('costing.production_cost_periods.*');

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
                    'COALESCE(SUM(CASE WHEN (COALESCE(l.qty_dispatched,0) - COALESCE(l.qty_received,0)) > 0 THEN (COALESCE(l.qty_dispatched,0) - COALESCE(l.qty_received,0)) ELSE 0 END),0) as s',
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

    .sidebar-modern { display: none; }
    @media (min-width: 992px) { .sidebar-modern { display: flex; } }

    .sidebar-modern::-webkit-scrollbar { width: 6px; }
    .sidebar-modern::-webkit-scrollbar-thumb { background: rgba(148, 163, 184, .35); border-radius: 20px; }

    .sidebar-brand {
        font-size: 1.3rem;
        font-weight: 700;
        padding: .8rem .3rem 1.1rem;
        color: var(--text);
        letter-spacing: -.03em;
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
        font-weight: 600;
        box-shadow: inset 2px 0 0 var(--accent);
        color: var(--accent);
    }

    .sidebar-toggle { cursor: pointer; border: 0; width: 100%; background: transparent; text-align: left; }
    .sidebar-toggle .chevron { margin-left: auto; font-size: .8rem; opacity: .8; transition: transform .18s ease; }
    .sidebar-toggle[aria-expanded="true"] .chevron { transform: rotate(90deg); }
    .sidebar-toggle.is-open { background: transparent; box-shadow: none; color: var(--accent); font-weight: 600; }
    .sidebar-toggle.is-open .icon { color: var(--accent); }

    .sidebar-link-sub {
        position: relative;
        font-size: .86rem;
        padding: .4rem .9rem .4rem 2.3rem;
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

    .menu-label {
        color: var(--muted);
        padding-left: .5rem;
        margin-bottom: .25rem;
        letter-spacing: .08em;
        font-size: .7rem;
    }

    .simple-group { margin-top: .4rem; }
</style>

<aside class="sidebar-modern flex-column">
    <div class="sidebar-brand">GFID</div>

    <ul class="sidebar-nav">
        {{-- DASHBOARD --}}
        @if ($hasDashboardRoute)
            <li>
                <x-sidebar.simple-link href="{{ route('dashboard') }}" icon="🏠" :active="request()->routeIs('dashboard')">
                    Dashboard
                </x-sidebar.simple-link>
            </li>
        @endif

        {{-- GUEST --}}
        @if (!$user)
            {{-- no menu --}}
        @elseif ($isAdmin || $isOperating)
            {{-- ===========================
                ADMIN / OPERATING
            ============================ --}}

            <x-sidebar.label text="Inventory" />
            <li class="simple-group">
                @if ($hasInvStocksItems)
                    <x-sidebar.simple-link href="{{ route('inventory.stocks.items') }}" icon="📦" :active="request()->routeIs('inventory.stocks.items')">
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

            {{-- ✅ RTS menus: tampil juga di operating --}}
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

                    {{-- ✅ RTS Dadakan (tetap owner/admin only) --}}
                    @if ($canManageRts && $hasRtsDirectReceiveIndex)
                        <x-sidebar.simple-link href="{{ route('rts.direct-receives.index') }}" icon="⚡"
                            :active="request()->routeIs('rts.direct-receives.*')">
                            RTS Dadakan
                        </x-sidebar.simple-link>
                    @endif
                </li>
            @endif

            {{-- Sales (admin) --}}
            @if ($isAdmin)
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

            {{-- Production (operating) --}}
            @if ($isOperating)
                <x-sidebar.label text="Production" />
                <li class="simple-group">
                    @if ($hasProdCuttingJobsIndex)
                        <x-sidebar.simple-link href="{{ route('production.cutting_jobs.index') }}" icon="✂️"
                            :active="request()->routeIs('production.cutting_jobs.*')">
                            Daftar Cutting Jobs
                        </x-sidebar.simple-link>
                    @endif

                    @if ($hasProdSewPickupsIndex)
                        <x-sidebar.simple-link href="{{ route('production.sewing.pickups.index') }}" icon="🧵"
                            :active="request()->routeIs('production.sewing.pickups.*')">
                            Daftar Sewing Pickups
                        </x-sidebar.simple-link>
                    @endif

                    @if ($hasProdSewReturnsIndex)
                        <x-sidebar.simple-link href="{{ route('production.sewing.returns.index') }}" icon="📥"
                            :active="request()->routeIs('production.sewing.returns.*')">
                            Daftar Sewing Returns
                        </x-sidebar.simple-link>
                    @endif

                    @if ($hasProdFinishingJobsIndex)
                        <x-sidebar.simple-link href="{{ route('production.finishing_jobs.index') }}" icon="🧶"
                            :active="request()->routeIs('production.finishing_jobs.*')">
                            Daftar Finishing
                        </x-sidebar.simple-link>
                    @endif

                    @if ($hasProdWipFinAdjIndex)
                        <x-sidebar.simple-link href="{{ route('production.wip-fin-adjustments.index') }}"
                            icon="🧾" :active="request()->routeIs('production.wip-fin-adjustments.*')">
                            Koreksi WIP-FIN
                        </x-sidebar.simple-link>
                    @endif

                    @if ($hasProdQcIndex)
                        <x-sidebar.simple-link href="{{ route('production.qc.index') }}" icon="✅"
                            :active="request()->routeIs('production.qc.*')">
                            QC Cutting
                        </x-sidebar.simple-link>
                    @endif
                </li>
            @endif

            {{-- Finance (admin + operating) --}}
            <x-sidebar.label text="Finance" />
            <li class="simple-group">
                @if ($hasCashExpensesIndex)
                    <x-sidebar.simple-link href="{{ route('accounting.cash-expenses.index') }}" icon="💸"
                        :active="request()->routeIs('accounting.cash-expenses.*')">
                        Cash Expenses
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
            </li>
        @elseif ($isOwner)
            {{-- ===========================
                OWNER
            ============================ --}}

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
                    @if ($hasMasterItemsIndex)
                        <x-sidebar.sub-link href="{{ route('master.items.index') }}" icon="📦" :active="request()->routeIs('master.items.*')">
                            Items
                        </x-sidebar.sub-link>
                    @endif

                    {{-- ✅ BOM SKU --}}
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
                    @if ($hasMarketplaceIndex)
                        <x-sidebar.sub-link href="{{ route('marketplace.orders.index') }}" icon="≡"
                            :active="request()->routeIs('marketplace.orders.index')">
                            Daftar Order
                        </x-sidebar.sub-link>
                    @endif
                    @if ($hasMarketplaceCreate)
                        <x-sidebar.sub-link href="{{ route('marketplace.orders.create') }}" icon="＋"
                            :active="request()->routeIs('marketplace.orders.create')">
                            Order Manual
                        </x-sidebar.sub-link>
                    @endif
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

                    <div class="px-3 pt-2 pb-1 text-uppercase"
                        style="font-size:.68rem; letter-spacing:.12em; color:var(--muted);">
                        Shipments
                    </div>

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

                    <div class="px-3 pt-3 pb-1 text-uppercase"
                        style="font-size:.68rem; letter-spacing:.12em; color:var(--muted);">
                        Shipment Returns
                    </div>

                    @if ($hasSalesShipmentReturnsIndex)
                        <x-sidebar.sub-link href="{{ route('sales.shipment_returns.index') }}" icon="🔁"
                            :active="request()->routeIs('sales.shipment_returns.index')">
                            Daftar Retur
                        </x-sidebar.sub-link>
                    @endif
                    @if ($hasSalesShipmentReturnsCreate)
                        <x-sidebar.sub-link href="{{ route('sales.shipment_returns.create') }}" icon="＋"
                            :active="request()->routeIs('sales.shipment_returns.create')">
                            Retur Shipment Baru
                        </x-sidebar.sub-link>
                    @endif

                    <div class="px-3 pt-3 pb-1 text-uppercase"
                        style="font-size:.68rem; letter-spacing:.12em; color:var(--muted);">
                        Sales Reports
                    </div>

                    {{-- ✅ NEW: Sales Performance --}}
                    @if ($hasSalesReportPerformance)
                        <x-sidebar.sub-link href="{{ route('sales.reports.sales_performance.index') }}" icon="📈"
                            :active="request()->routeIs('sales.reports.sales_performance.*')">
                            Sales Performance
                        </x-sidebar.sub-link>
                    @endif

                    {{-- existing shipment report --}}
                    @if ($hasSalesShipmentsReport)
                        <x-sidebar.sub-link href="{{ route('sales.shipments.report') }}" icon="📊"
                            :active="request()->routeIs('sales.shipments.report')">
                            Laporan Pengiriman
                        </x-sidebar.sub-link>
                    @endif

                    {{-- optional: reports lain (muncul otomatis kalau route ada) --}}
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

            {{-- INVENTORY --}}
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

                    <div class="px-3 pt-3 pb-1 text-uppercase"
                        style="font-size:.68rem; letter-spacing:.12em; color:var(--muted);">
                        Opname
                    </div>

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

                    <div class="px-3 pt-3 pb-1 text-uppercase"
                        style="font-size:.68rem; letter-spacing:.12em; color:var(--muted);">
                        External
                    </div>

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

            {{-- STOCK REQUESTS --}}
            @if ($canViewRts && ($hasRtsStockReqIndex || $hasRtsDirectReceiveIndex))
                <x-sidebar.label text="Stock Requests" />
                <li class="mb-1">
                    <button class="sidebar-link sidebar-toggle {{ $stockReqOpen ? 'is-open' : '' }}" type="button"
                        data-bs-toggle="collapse" data-bs-target="#navInventoryStockRequests"
                        aria-expanded="{{ $stockReqOpen ? 'true' : 'false' }}"
                        aria-controls="navInventoryStockRequests">
                        <span class="icon">📤</span>
                        <span>Stock Requests</span>
                        <span class="chevron">▸</span>
                    </button>

                    <div class="collapse {{ $stockReqOpen ? 'show' : '' }}" id="navInventoryStockRequests">
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

                    @if ($hasProdFinishingJobsIndex)
                        <x-sidebar.sub-link href="{{ route('production.finishing_jobs.index') }}" icon="🧶"
                            :active="request()->routeIs('production.finishing_jobs.*')">
                            Finishing Jobs
                        </x-sidebar.sub-link>
                    @endif

                    @if ($hasProdWipFinAdjIndex)
                        <x-sidebar.sub-link href="{{ route('production.wip-fin-adjustments.index') }}" icon="🧾"
                            :active="request()->routeIs('production.wip-fin-adjustments.*')">
                            Koreksi WIP-FIN
                        </x-sidebar.sub-link>
                    @endif

                    @if ($hasProdQcIndex)
                        <x-sidebar.sub-link href="{{ route('production.qc.index') }}" icon="✅"
                            :active="request()->routeIs('production.qc.*')">
                            QC Cutting
                        </x-sidebar.sub-link>
                    @endif

                    <div class="px-3 pt-3 pb-1 text-uppercase"
                        style="font-size:.68rem; letter-spacing:.12em; color:var(--muted);">
                        Reports
                    </div>

                    @if ($hasProdReportDashboard)
                        <x-sidebar.sub-link href="{{ route('production.reports.dashboard') }}" icon="📊"
                            :active="request()->routeIs('production.reports.dashboard')">
                            Sewing Dashboard
                        </x-sidebar.sub-link>
                    @endif

                    @if ($hasProdReportOutstanding)
                        <x-sidebar.sub-link href="{{ route('production.reports.outstanding') }}" icon="⏳"
                            :active="request()->routeIs('production.reports.outstanding')">
                            Outstanding WIP-SEW
                        </x-sidebar.sub-link>
                    @endif

                    @if ($hasProdReportAgingWipSew)
                        <x-sidebar.sub-link href="{{ route('production.reports.aging_wip_sew') }}" icon="📆"
                            :active="request()->routeIs('production.reports.aging_wip_sew')">
                            Aging WIP-SEW
                        </x-sidebar.sub-link>
                    @endif

                    @if ($hasProdReportFlow)
                        <x-sidebar.sub-link href="{{ route('production.reports.production_flow_dashboard') }}"
                            icon="🌀" :active="request()->routeIs('production.reports.production_flow_dashboard')">
                            Flow Dashboard
                        </x-sidebar.sub-link>
                    @endif

                    @if ($hasProdReportDaily)
                        <x-sidebar.sub-link href="{{ route('production.reports.daily_production') }}" icon="📅"
                            :active="request()->routeIs('production.reports.daily_production')">
                            Daily Production
                        </x-sidebar.sub-link>
                    @endif
                </div>
            </li>

            {{-- FINANCE --}}
            <x-sidebar.label text="Finance" />

            {{-- Accounting --}}
            <li class="mb-1">
                <button class="sidebar-link sidebar-toggle {{ $accountingOpen ? 'is-open' : '' }}" type="button"
                    data-bs-toggle="collapse" data-bs-target="#navAccounting"
                    aria-expanded="{{ $accountingOpen ? 'true' : 'false' }}" aria-controls="navAccounting">
                    <span class="icon">🧾</span>
                    <span>Accounting</span>
                    <span class="chevron">▸</span>
                </button>

                <div class="collapse {{ $accountingOpen ? 'show' : '' }}" id="navAccounting">
                    @if ($hasOpeningBalancesIndex)
                        <x-sidebar.sub-link href="{{ route('accounting.opening-balances.index') }}" icon="🟢"
                            :active="request()->routeIs('accounting.opening-balances.*')">
                            Opening Balances
                        </x-sidebar.sub-link>
                    @endif

                    @if ($hasCashExpensesIndex)
                        <x-sidebar.sub-link href="{{ route('accounting.cash-expenses.index') }}" icon="💸"
                            :active="request()->routeIs('accounting.cash-expenses.*')">
                            Cash Expenses
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
                    @if ($hasPieceworkIndex)
                        <x-sidebar.sub-link href="{{ route('payroll.piecework.index', ['module' => 'cutting']) }}"
                            icon="✂️" :active="$pieceworkCuttingActive">
                            Cutting Payroll
                        </x-sidebar.sub-link>

                        <x-sidebar.sub-link href="{{ route('payroll.piecework.index', ['module' => 'sewing']) }}"
                            icon="🧵" :active="$pieceworkSewingActive">
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

            {{-- Costing --}}
            <li class="mb-1">
                <button class="sidebar-link sidebar-toggle {{ $costingOpen ? 'is-open' : '' }}" type="button"
                    data-bs-toggle="collapse" data-bs-target="#navFinanceCosting"
                    aria-expanded="{{ $costingOpen ? 'true' : 'false' }}" aria-controls="navFinanceCosting">
                    <span class="icon">📉</span>
                    <span>Costing &amp; HPP</span>
                    <span class="chevron">▸</span>
                </button>

                <div class="collapse {{ $costingOpen ? 'show' : '' }}" id="navFinanceCosting">
                    @if ($hasHppIndex)
                        <x-sidebar.sub-link href="{{ route('costing.hpp.index') }}" icon="⚙️"
                            :active="request()->routeIs('costing.hpp.*')">
                            HPP Finished Goods
                        </x-sidebar.sub-link>
                    @endif

                    @if ($hasProdCostPeriodsIndex)
                        <x-sidebar.sub-link href="{{ route('costing.production_cost_periods.index') }}"
                            icon="📆" :active="request()->routeIs('costing.production_cost_periods.*')">
                            Production Cost Periods
                        </x-sidebar.sub-link>
                    @endif
                </div>
            </li>
        @endif
    </ul>
</aside>
