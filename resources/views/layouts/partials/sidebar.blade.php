{{-- resources/views/layouts/partials/sidebar.blade.php --}}
@php
    use Illuminate\Support\Facades\Cache;
    use Illuminate\Support\Facades\DB;

    $user = auth()->user();
    $role = strtolower((string) ($user->role ?? ''));

    $isOwner     = $role === 'owner';
    $isOperating = $role === 'operating';
    $isAdmin     = $role === 'admin';

    // capability flags
    $canViewRts   = $isOwner || $isAdmin || $isOperating;
    $canManageRts = $isOwner || $isAdmin;

    $router = app('router');

    // =========================================================
    // ROUTE GUARDS
    // =========================================================
    $hasDashboardRoute = $router->has('dashboard');

    // Inventory
    $hasInvStocksItems      = $router->has('inventory.stocks.items');
    $hasInvStocksLots       = $router->has('inventory.stocks.lots');
    $hasInvStockCard        = $router->has('inventory.stock_card.index');
    $hasInvTransfersIndex   = $router->has('inventory.transfers.index');
    $hasInvTransfersCreate  = $router->has('inventory.transfers.create');
    $hasInvAdjustmentsIndex = $router->has('inventory.adjustments.index');

    $hasInvOpnamesIndex  = $router->has('inventory.stock_opnames.index');
    $hasInvOpnamesCreate = $router->has('inventory.stock_opnames.create');

    $hasInvExternalIndex  = $router->has('inventory.external_transfers.index');
    $hasInvExternalCreate = $router->has('inventory.external_transfers.create');

    $hasInvWipAdjIndex = $router->has('inventory.wip_adjustments.index');

    // RTS
    $hasRtsStockReqIndex      = $router->has('rts.stock-requests.index');
    $hasRtsDirectReceiveIndex = $router->has('rts.direct-receives.index');

    // Sales
    $hasSalesShipmentsIndex       = $router->has('sales.shipments.index');
    $hasSalesShipmentReturnsIndex = $router->has('sales.shipment_returns.index');

    // Master
    $hasMasterItemsIndex     = $router->has('master.items.index');
    $hasMasterCustomersIndex = $router->has('master.customers.index');
    $hasMasterSuppliersIndex = $router->has('master.suppliers.index');
    $hasMasterItemBomsIndex  = $router->has('master.item_boms.index');

    // Purchasing
    $hasPoIndex   = $router->has('purchasing.purchase_orders.index');
    $hasPoCreate  = $router->has('purchasing.purchase_orders.create');
    $hasGrnIndex  = $router->has('purchasing.purchase_receipts.index');
    $hasGrnCreate = $router->has('purchasing.purchase_receipts.create');

    // Production (ADMIN: only sewing returns)
    $hasProdSewReturnsIndex  = $router->has('production.sewing.returns.index');
    $hasProdSewReturnsCreate = $router->has('production.sewing.returns.create');

    // Reports (owner)
    $hasProdReportDashboard   = $router->has('production.reports.dashboard');
    $hasProdReportOutstanding = $router->has('production.reports.outstanding');
    $hasProdReportAgingWipSew = $router->has('production.reports.aging_wip_sew');
    $hasProdReportFlow        = $router->has('production.reports.production_flow_dashboard');
    $hasProdReportDaily       = $router->has('production.reports.daily_production');

    // Payroll (owner)
    $hasPieceworkIndex          = $router->has('payroll.piecework.index');
    $hasPieceRatesIndex         = $router->has('payroll.piece_rates.index');
    $hasPayrollReportsOperators = $router->has('payroll.reports.operators');

    // Costing (owner)
    $hasHppIndex             = $router->has('costing.hpp.index');
    $hasProdCostPeriodsIndex = $router->has('costing.production_cost_periods.index');

    // Accounting (owner / operating only, admin disembunyikan nanti di render)
    $hasAccountsIndex             = $router->has('accounting.accounts.index');
    $hasCashExpensesIndex         = $router->has('accounting.cash-expenses.index');
    $hasJournalsIndex             = $router->has('accounting.journals.index');
    $hasOpeningBalancesIndex      = $router->has('accounting.opening-balances.index');
    $hasOpeningBalancesBatchIndex = $router->has('accounting.opening-balances-batch.index');

    // =========================================================
    // OPEN STATES
    // =========================================================
    $open = fn ($pattern) => request()->routeIs($pattern);

    $openMaster = $open('master.*');
    $openPurchasing = $open('purchasing.*');

    $openSales =
        $open('sales.invoices.*') ||
        $open('sales.shipments.*') ||
        $open('sales.shipment_returns.*') ||
        $open('sales.reports.*') ||
        $open('sales.shipments.report');

    $openInventory =
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
        $open('production.sewing.*') ||
        $open('production.reports.*');

    $openAccounting =
        $open('accounting.cash-expenses.*') ||
        $open('accounting.opening-balances.*') ||
        $open('accounting.opening-balances-batch.*') ||
        $open('accounting.journals.*') ||
        $open('accounting.accounts.*');

    $openPayroll =
        $open('payroll.piecework.*') ||
        $open('payroll.piece_rates.*') ||
        $open('payroll.reports.*');

    $openCosting =
        $open('costing.hpp.*') ||
        $open('costing.production_cost_periods.*');

    // =========================================================
    // BADGE COUNTERS (RTS)
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

            return ['rtsNeedReceiveQty' => (float) $rtsNeedReceiveQty];
        });

        $rtsNeedReceiveQty = (float) ($badges['rtsNeedReceiveQty'] ?? 0);
    }

    $hasRtsNeedReceive = $rtsNeedReceiveQty > 0.000001;
    $rtsBadgeTitle = 'Perlu diterima: ' . $fmtQty($rtsNeedReceiveQty);

    // Payroll active helpers (owner)
    $activeModule = request()->route()?->parameter('module');
    $pieceworkCuttingActive = request()->routeIs('payroll.piecework.*') && $activeModule === 'cutting';
    $pieceworkSewingActive  = request()->routeIs('payroll.piecework.*') && $activeModule === 'sewing';

    $subhead = function (string $text) {
        echo '<div class="sidebar-subhead">' . e($text) . '</div>';
    };

    $canShow = fn (...$flags) => collect($flags)->contains(true);
@endphp

<style>
    /* =========================================================
       SIDEBAR MODERN (scoped)
    ========================================================= */
    .sidebar-modern { display: none; }
    @media (min-width: 992px) {
        .sidebar-modern {
            display: flex;
            position: fixed;
            top: 0; left: 0;
            width: 260px;
            height: 100vh;
            padding: 1rem 1rem 1.6rem;
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
        .app-main { margin-left: 260px; }
    }

    .sidebar-modern .sidebar-brand{
        display:flex; align-items:center; justify-content:space-between; gap:.75rem;
        font-size:1.15rem; font-weight:800; padding:.7rem .35rem .9rem;
        color:var(--text); letter-spacing:-.03em;
    }
    .sidebar-modern .role-pill{
        font-size:.7rem; padding:.18rem .55rem; border-radius:999px;
        background: color-mix(in srgb, var(--accent-soft) 18%, var(--card) 82%);
        box-shadow: inset 0 0 0 1px var(--line);
        color: var(--muted);
        text-transform: uppercase;
        letter-spacing:.12em;
    }
    .sidebar-modern .sidebar-nav{ list-style:none; margin:0; padding:0; }

    .sidebar-modern .sidebar-link{
        display:flex; align-items:center; gap:.55rem;
        padding:.6rem .9rem; border-radius:14px;
        color:var(--text); text-decoration:none;
        font-size:.93rem;
        transition: background .18s ease, box-shadow .18s ease, transform .12s ease, color .18s ease;
        position:relative; isolation:isolate;
    }
    .sidebar-modern .sidebar-link .icon{ width:22px; font-size:1.05rem; text-align:center; opacity:.95; }
    .sidebar-modern .sidebar-link:hover{
        background: color-mix(in srgb, var(--accent-soft) 18%, var(--card) 82%);
        box-shadow: inset 0 0 0 1px var(--line);
        transform: translateX(1px);
    }
    .sidebar-modern .sidebar-link.active{
        background: transparent;
        font-weight: 700;
        box-shadow: inset 2px 0 0 var(--accent);
        color: var(--accent);
    }

    .sidebar-modern .sidebar-link-sub{
        position:relative;
        display:flex; align-items:center; gap:.55rem;
        color:var(--text); text-decoration:none;
        font-size:.86rem;
        padding:.42rem .9rem .42rem 2.3rem;
        border-radius:10px; opacity:.95;
        transition: background .18s ease, box-shadow .18s ease, transform .12s ease, color .18s ease;
    }
    .sidebar-modern .sidebar-link-sub:hover{
        background: color-mix(in srgb, var(--accent-soft) 16%, var(--card) 84%);
        box-shadow: inset 0 0 0 1px var(--line);
    }
    .sidebar-modern .sidebar-link-sub.active{
        background: transparent;
        font-weight: 700;
        box-shadow: inset 2px 0 0 var(--accent);
        opacity: 1;
        color: var(--accent);
    }
    .sidebar-modern .sidebar-link-sub.active::before{
        content:''; position:absolute; left:1.4rem; top:50%;
        transform:translateY(-50%);
        width:6px; height:6px; border-radius:999px;
        background: var(--accent);
    }

    .sidebar-modern .sidebar-toggle{ cursor:pointer; border:0; width:100%; background:transparent; text-align:left; }
    .sidebar-modern .sidebar-toggle .chevron{ margin-left:auto; font-size:.8rem; opacity:.8; transition: transform .18s ease; }
    .sidebar-modern .sidebar-toggle[aria-expanded="true"] .chevron{ transform: rotate(90deg); }
    .sidebar-modern .sidebar-toggle.is-open{ background:transparent; box-shadow:none; color:var(--accent); font-weight:700; }

    .sidebar-modern .sidebar-subhead{
        padding:.35rem .9rem .15rem 1.05rem;
        font-size:.68rem; letter-spacing:.12em;
        text-transform:uppercase;
        color:var(--muted); opacity:.9;
    }

    .sidebar-modern .simple-group{ margin-top:.4rem; }

    .sidebar-modern .sidebar-divider{
        height:1px; margin:.65rem .35rem;
        background: color-mix(in srgb, var(--line) 70%, transparent 30%);
        border-radius:999px; opacity:.8;
    }

    .sidebar-modern .nav-dot{
        margin-left:auto;
        width:9px; height:9px; border-radius:999px;
        display:inline-block;
        box-shadow: 0 0 0 2px color-mix(in srgb, var(--card) 70%, transparent 30%);
        opacity:.95;
    }
    .sidebar-modern .nav-dot.warn{ background:#f59e0b; }
    .sidebar-modern .nav-dot.ok{ background:#22c55e; }
    .sidebar-modern .nav-dot.danger{ background:#ef4444; }
    .sidebar-modern .nav-dot.info{ background:#3b82f6; }
    .sidebar-modern .nav-dot.muted{ background:#94a3b8; }
</style>

<aside class="sidebar-modern flex-column">
    <div class="sidebar-brand">
        <div>GFID</div>
        @if ($user)
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
            <div class="sidebar-divider"></div>
        @endif

        {{-- GUEST --}}
        @if (!$user)
            {{-- no menu --}}

        {{-- =========================================================
            ADMIN / OPERATING
            ✅ ADMIN: Marketplace + Imports + Finance DIHILANGKAN
            ✅ ADMIN Production: ONLY Sewing Returns (+ create)
            ✅ OPERATING: tetap seperti sebelumnya (boleh akses ops/marketplace/import/finance sesuai kebutuhan)
        ========================================================= --}}
        @elseif ($isAdmin || $isOperating)

            {{-- OPERATIONS (admin+operating) --}}
            <x-sidebar.label text="Operations" />
            <li class="simple-group">
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

            {{-- SALES (admin only) --}}
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

            {{-- ✅ PRODUCTION
                ADMIN: ONLY Sewing Returns (+ create)
                OPERATING: tidak ditaruh di sini (biar fokus, kalau mau aku bisa tambah lagi)
            --}}
            @if ($isAdmin && ($hasProdSewReturnsIndex || $hasProdSewReturnsCreate))
                <x-sidebar.label text="Production" />
                <li class="simple-group">
                    @if ($hasProdSewReturnsIndex)
                        <x-sidebar.simple-link href="{{ route('production.sewing.returns.index') }}" icon="📥"
                            :active="request()->routeIs('production.sewing.returns.*')">
                            Sewing Returns
                        </x-sidebar.simple-link>
                    @endif

                    @if ($hasProdSewReturnsCreate)
                        <x-sidebar.simple-link href="{{ route('production.sewing.returns.create') }}" icon="＋"
                            :active="request()->routeIs('production.sewing.returns.create')">
                            Sewing Return Baru
                        </x-sidebar.simple-link>
                    @endif
                </li>
            @endif

            {{-- ✅ OPERATING (opsional)
                Kalau operating masih perlu menu production full, bilang aja nanti aku tambah.
                Sekarang: sesuai request, yang dihilangkan hanya untuk ADMIN.
            --}}

        {{-- =========================================================
            OWNER (collapsible, grouped)
        ========================================================= --}}
        @elseif ($isOwner)

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
                            :active="request()->routeIs('master.items.*')">Items</x-sidebar.sub-link>
                    @endif
                    @if ($hasMasterItemBomsIndex)
                        <x-sidebar.sub-link href="{{ route('master.item_boms.index') }}" icon="🧾"
                            :active="request()->routeIs('master.item_boms.*')">BOM SKU</x-sidebar.sub-link>
                    @endif
                    @if ($hasMasterSuppliersIndex)
                        <x-sidebar.sub-link href="{{ route('master.suppliers.index') }}" icon="🏷️"
                            :active="request()->routeIs('master.suppliers.*')">Suppliers</x-sidebar.sub-link>
                    @endif
                    @if ($hasMasterCustomersIndex)
                        <x-sidebar.sub-link href="{{ route('master.customers.index') }}" icon="👤"
                            :active="request()->routeIs('master.customers.*')">Customers</x-sidebar.sub-link>
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
                                :active="request()->routeIs('purchasing.purchase_orders.index')">Daftar PO</x-sidebar.sub-link>
                        @endif
                        @if ($hasPoCreate)
                            <x-sidebar.sub-link href="{{ route('purchasing.purchase_orders.create') }}" icon="＋"
                                :active="request()->routeIs('purchasing.purchase_orders.create')">PO Baru</x-sidebar.sub-link>
                        @endif

                        @php $subhead('Goods Receipts (GRN)'); @endphp
                        @if ($hasGrnIndex)
                            <x-sidebar.sub-link href="{{ route('purchasing.purchase_receipts.index') }}" icon="≡"
                                :active="request()->routeIs('purchasing.purchase_receipts.index')">Daftar GRN</x-sidebar.sub-link>
                        @endif
                        @if ($hasGrnCreate)
                            <x-sidebar.sub-link href="{{ route('purchasing.purchase_receipts.create') }}" icon="＋"
                                :active="request()->routeIs('purchasing.purchase_receipts.create')">GRN Baru</x-sidebar.sub-link>
                        @endif
                    </div>
                </li>
            @endif

            {{-- INVENTORY --}}
            @if ($canShow(
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
                        @php $subhead('Stock'); @endphp
                        @if ($hasInvStocksItems)
                            <x-sidebar.sub-link href="{{ route('inventory.stocks.items') }}" icon="📦"
                                :active="request()->routeIs('inventory.stocks.items')">Stok Barang</x-sidebar.sub-link>
                        @endif
                        @if ($hasInvStocksLots)
                            <x-sidebar.sub-link href="{{ route('inventory.stocks.lots') }}" icon="🎫"
                                :active="request()->routeIs('inventory.stocks.lots')">Stok per LOT</x-sidebar.sub-link>
                        @endif
                        @if ($hasInvStockCard)
                            <x-sidebar.sub-link href="{{ route('inventory.stock_card.index') }}" icon="📋"
                                :active="request()->routeIs('inventory.stock_card.*')">Kartu Stok</x-sidebar.sub-link>
                        @endif
                        @if ($hasInvTransfersIndex)
                            <x-sidebar.sub-link href="{{ route('inventory.transfers.index') }}" icon="🔁"
                                :active="request()->routeIs('inventory.transfers.index')">Daftar Transfer</x-sidebar.sub-link>
                        @endif
                        @if ($hasInvTransfersCreate)
                            <x-sidebar.sub-link href="{{ route('inventory.transfers.create') }}" icon="➕"
                                :active="request()->routeIs('inventory.transfers.create')">Transfer Baru</x-sidebar.sub-link>
                        @endif
                        @if ($hasInvAdjustmentsIndex)
                            <x-sidebar.sub-link href="{{ route('inventory.adjustments.index') }}" icon="⚖️"
                                :active="request()->routeIs('inventory.adjustments.*')">Inventory Adjustments</x-sidebar.sub-link>
                        @endif
                        @if ($hasInvWipAdjIndex)
                            <x-sidebar.sub-link href="{{ route('inventory.wip_adjustments.index') }}" icon="🧾"
                                :active="request()->routeIs('inventory.wip_adjustments.*')">Koreksi WIP</x-sidebar.sub-link>
                        @endif

                        @php $subhead('Opname'); @endphp
                        @if ($hasInvOpnamesIndex)
                            <x-sidebar.sub-link href="{{ route('inventory.stock_opnames.index') }}" icon="📊"
                                :active="request()->routeIs('inventory.stock_opnames.*')">Stock Opname</x-sidebar.sub-link>
                        @endif
                        @if ($hasInvOpnamesCreate)
                            <x-sidebar.sub-link href="{{ route('inventory.stock_opnames.create') }}" icon="＋"
                                :active="request()->routeIs('inventory.stock_opnames.create')">Stock Opname Baru</x-sidebar.sub-link>
                        @endif

                        @php $subhead('External'); @endphp
                        @if ($hasInvExternalIndex)
                            <x-sidebar.sub-link href="{{ route('inventory.external_transfers.index') }}" icon="🚚"
                                :active="request()->routeIs('inventory.external_transfers.index')">Daftar External TF</x-sidebar.sub-link>
                        @endif
                        @if ($hasInvExternalCreate)
                            <x-sidebar.sub-link href="{{ route('inventory.external_transfers.create') }}" icon="➕"
                                :active="request()->routeIs('inventory.external_transfers.create')">External TF Baru</x-sidebar.sub-link>
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

            {{-- PRODUCTION (owner) --}}
            @if ($canShow($hasProdSewReturnsIndex, $hasProdReportDashboard, $hasProdReportOutstanding, $hasProdReportAgingWipSew, $hasProdReportFlow, $hasProdReportDaily))
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
                        @php $subhead('Jobs'); @endphp
                        @if ($hasProdSewReturnsIndex)
                            <x-sidebar.sub-link href="{{ route('production.sewing.returns.index') }}" icon="📥"
                                :active="request()->routeIs('production.sewing.returns.*')">Sewing Returns</x-sidebar.sub-link>
                        @endif

                        @php $subhead('Reports'); @endphp
                        @if ($hasProdReportDashboard)
                            <x-sidebar.sub-link href="{{ route('production.reports.dashboard') }}" icon="📊"
                                :active="request()->routeIs('production.reports.dashboard')">Sewing Dashboard</x-sidebar.sub-link>
                        @endif
                        @if ($hasProdReportOutstanding)
                            <x-sidebar.sub-link href="{{ route('production.reports.outstanding') }}" icon="⏳"
                                :active="request()->routeIs('production.reports.outstanding')">Outstanding WIP-SEW</x-sidebar.sub-link>
                        @endif
                        @if ($hasProdReportAgingWipSew)
                            <x-sidebar.sub-link href="{{ route('production.reports.aging_wip_sew') }}" icon="📆"
                                :active="request()->routeIs('production.reports.aging_wip_sew')">Aging WIP-SEW</x-sidebar.sub-link>
                        @endif
                        @if ($hasProdReportFlow)
                            <x-sidebar.sub-link href="{{ route('production.reports.production_flow_dashboard') }}" icon="🌀"
                                :active="request()->routeIs('production.reports.production_flow_dashboard')">Flow Dashboard</x-sidebar.sub-link>
                        @endif
                        @if ($hasProdReportDaily)
                            <x-sidebar.sub-link href="{{ route('production.reports.daily_production') }}" icon="📅"
                                :active="request()->routeIs('production.reports.daily_production')">Daily Production</x-sidebar.sub-link>
                        @endif
                    </div>
                </li>
            @endif

            {{-- FINANCE (owner) --}}
            @if ($canShow($hasOpeningBalancesIndex, $hasOpeningBalancesBatchIndex, $hasCashExpensesIndex, $hasJournalsIndex, $hasAccountsIndex))
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
                        @if ($hasOpeningBalancesIndex)
                            <x-sidebar.sub-link href="{{ route('accounting.opening-balances.index') }}" icon="🟢"
                                :active="request()->routeIs('accounting.opening-balances.*')">Opening Balances</x-sidebar.sub-link>
                        @endif
                        @if ($hasOpeningBalancesBatchIndex)
                            <x-sidebar.sub-link href="{{ route('accounting.opening-balances-batch.index') }}" icon="🧺"
                                :active="request()->routeIs('accounting.opening-balances-batch.*')">Opening Balances Batch</x-sidebar.sub-link>
                        @endif
                        @if ($hasCashExpensesIndex)
                            <x-sidebar.sub-link href="{{ route('accounting.cash-expenses.index') }}" icon="💸"
                                :active="request()->routeIs('accounting.cash-expenses.*')">Cash Expenses</x-sidebar.sub-link>
                        @endif
                        @if ($hasJournalsIndex)
                            <x-sidebar.sub-link href="{{ route('accounting.journals.index') }}" icon="📓"
                                :active="request()->routeIs('accounting.journals.*')">Journals</x-sidebar.sub-link>
                        @endif
                        @if ($hasAccountsIndex)
                            <x-sidebar.sub-link href="{{ route('accounting.accounts.index') }}" icon="🗂️"
                                :active="request()->routeIs('accounting.accounts.*')">Accounts (COA)</x-sidebar.sub-link>
                        @endif
                    </div>
                </li>
            @endif

            {{-- PAYROLL (owner) --}}
            @if ($canShow($hasPieceworkIndex, $hasPieceRatesIndex, $hasPayrollReportsOperators))
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
                        @if ($hasPieceworkIndex)
                            <x-sidebar.sub-link href="{{ route('payroll.piecework.index', ['module' => 'cutting']) }}" icon="✂️"
                                :active="$pieceworkCuttingActive">Cutting Payroll</x-sidebar.sub-link>

                            <x-sidebar.sub-link href="{{ route('payroll.piecework.index', ['module' => 'sewing']) }}" icon="🧵"
                                :active="$pieceworkSewingActive">Sewing Payroll</x-sidebar.sub-link>
                        @endif

                        @if ($hasPieceRatesIndex)
                            <x-sidebar.sub-link href="{{ route('payroll.piece_rates.index') }}" icon="📑"
                                :active="request()->routeIs('payroll.piece_rates.*')">Piece Rates</x-sidebar.sub-link>
                        @endif

                        @if ($hasPayrollReportsOperators)
                            <x-sidebar.sub-link href="{{ route('payroll.reports.operators') }}" icon="📊"
                                :active="request()->routeIs('payroll.reports.*')">Reports</x-sidebar.sub-link>
                        @endif
                    </div>
                </li>
            @endif

            {{-- COSTING (owner) --}}
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
                                :active="request()->routeIs('costing.hpp.*')">HPP Finished Goods</x-sidebar.sub-link>
                        @endif
                        @if ($hasProdCostPeriodsIndex)
                            <x-sidebar.sub-link href="{{ route('costing.production_cost_periods.index') }}" icon="📆"
                                :active="request()->routeIs('costing.production_cost_periods.*')">Production Cost Periods</x-sidebar.sub-link>
                        @endif
                    </div>
                </li>
            @endif

        @endif
    </ul>
</aside>
