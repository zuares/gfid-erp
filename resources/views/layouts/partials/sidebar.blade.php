{{-- resources/views/layouts/partials/sidebar.blade.php --}}
@php
    use Illuminate\Support\Facades\DB;

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
        request()->routeIs('production.reports.*');

    // ✅ NEW: WIP-FIN Adjustments (koreksi hasil hitung)
    $prodWipFinAdjOpen = request()->routeIs('production.wip-fin-adjustments.*');

    $prodFinOpen =
        request()->routeIs('production.finishing_jobs.*') ||
        request()->routeIs('production.finishing_jobs.bundles_ready') ||
        request()->routeIs('production.finishing_jobs.report_per_item*') ||
        $prodWipFinAdjOpen; // ✅ include

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

    // =========================================================
    // ✅ BADGE COUNTERS (DOT-ONLY + TOOLTIP ANGKA)
    // =========================================================
    // RTS: "Perlu diterima" = outstanding di TRANSIT (dispatched - received), untuk request yang belum completed
    $rtsNeedReceiveQty = (float) DB::table('stock_request_lines as l')
        ->join('stock_requests as r', 'r.id', '=', 'l.stock_request_id')
        ->where('r.purpose', 'rts_replenish')
        ->whereIn('r.status', ['submitted', 'shipped', 'partial'])
        ->selectRaw(
            'COALESCE(SUM(CASE WHEN (COALESCE(l.qty_dispatched,0) - COALESCE(l.qty_received,0)) > 0 THEN (COALESCE(l.qty_dispatched,0) - COALESCE(l.qty_received,0)) ELSE 0 END),0) as s',
        )
        ->value('s');

    // PRD: "Perlu diproses" = outstanding PRD untuk dispatch (request - dispatched - received - picked)
    // (picked dihitung supaya PRD tahu kebutuhan sudah terpenuhi via pickup)
    $prdNeedProcessQty = (float) DB::table('stock_request_lines as l')
        ->join('stock_requests as r', 'r.id', '=', 'l.stock_request_id')
        ->where('r.purpose', 'rts_replenish')
        ->whereIn('r.status', ['submitted', 'shipped', 'partial'])
        ->selectRaw(
            'COALESCE(SUM(CASE WHEN (COALESCE(l.qty_request,0) - COALESCE(l.qty_dispatched,0) - COALESCE(l.qty_received,0) - COALESCE(l.qty_picked,0)) > 0 THEN (COALESCE(l.qty_request,0) - COALESCE(l.qty_dispatched,0) - COALESCE(l.qty_received,0) - COALESCE(l.qty_picked,0)) ELSE 0 END),0) as s',
        )
        ->value('s');

    $hasRtsNeedReceive = $rtsNeedReceiveQty > 0.000001;
    $hasPrdNeedProcess = $prdNeedProcessQty > 0.000001;

    $fmtQty = function ($n) {
        $n = (float) $n;
        return rtrim(rtrim(number_format($n, 2, '.', ''), '0'), '.');
    };

    $rtsBadgeTitle = 'Perlu diterima: ' . $fmtQty($rtsNeedReceiveQty);
    $prdBadgeTitle = 'Perlu diproses: ' . $fmtQty($prdNeedProcessQty);
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
        position: relative;
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
        display: flex;
        align-items: center;
        gap: .55rem;
        color: var(--text);
        text-decoration: none;
        transition: background .18s ease, box-shadow .18s ease, transform .12s ease, color .18s ease;
    }

    .sidebar-link-sub .icon {
        width: 18px;
        font-size: .9rem;
        text-align: center;
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

    /* ✅ DOT-ONLY BADGE (dipakai oleh component) */
    .nav-dot {
        width: 9px;
        height: 9px;
        border-radius: 999px;
        display: inline-block;
        margin-left: auto;
        flex: 0 0 auto;
        box-shadow: 0 0 0 2px color-mix(in srgb, var(--card) 70%, transparent 30%);
        opacity: .95;
    }

    .nav-dot.warn {
        background: rgba(245, 158, 11, 1);
    }

    .nav-dot.ok {
        background: rgba(16, 185, 129, 1);
    }

    .nav-dot.danger {
        background: rgba(239, 68, 68, 1);
    }

    .nav-dot.info {
        background: rgba(59, 130, 246, 1);
    }

    .nav-dot.muted {
        background: rgba(100, 116, 139, 1);
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
                        :active="request()->routeIs('rts.stock-requests.*')" :dot-only="$hasRtsNeedReceive" badge-tone="warn" :badge-title="$rtsBadgeTitle">
                        Permintaan Stock (RTS)
                    </x-sidebar.simple-link>
                @endif

                @if ($isOperating)
                    <x-sidebar.simple-link href="{{ route('prd.stock-requests.index') }}" icon="🏭"
                        :active="request()->routeIs('prd.stock-requests.*')" :dot-only="$hasPrdNeedProcess" badge-tone="warn" :badge-title="$prdBadgeTitle">
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

                    <x-sidebar.simple-link href="{{ route('production.sewing.pickups.index') }}" icon="🧵"
                        :active="request()->routeIs('production.sewing.pickups.*')">
                        Daftar Sewing Pickups
                    </x-sidebar.simple-link>

                    <x-sidebar.simple-link href="{{ route('production.sewing.returns.index') }}" icon="📥"
                        :active="request()->routeIs('production.sewing.returns.*')">
                        Daftar Sewing Returns
                    </x-sidebar.simple-link>


                    <x-sidebar.simple-link href="{{ route('production.finishing_jobs.index') }}" icon="🧶"
                        :active="request()->routeIs('production.finishing_jobs.*')">
                        Daftar Finishing
                    </x-sidebar.simple-link>

                    {{-- ✅ NEW: Koreksi WIP-FIN --}}
                    <x-sidebar.simple-link href="{{ route('production.wip-fin-adjustments.index') }}" icon="🧾"
                        :active="request()->routeIs('production.wip-fin-adjustments.*')">
                        Koreksi WIP-FIN
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
                    <x-sidebar.sub-link href="{{ route('master.items.index') }}" icon="📦" :active="request()->routeIs('master.items.*')">
                        Items
                    </x-sidebar.sub-link>
                    <x-sidebar.sub-link href="{{ route('master.customers.index') }}" icon="👤" :active="request()->routeIs('master.customers.*')">
                        Customers
                    </x-sidebar.sub-link>
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
                    <x-sidebar.sub-link href="{{ route('purchasing.purchase_orders.index') }}" icon="≡"
                        :active="request()->routeIs('purchasing.purchase_orders.index')">
                        Daftar PO
                    </x-sidebar.sub-link>
                    <x-sidebar.sub-link href="{{ route('purchasing.purchase_orders.create') }}" icon="＋"
                        :active="request()->routeIs('purchasing.purchase_orders.create')">
                        PO Baru
                    </x-sidebar.sub-link>
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
                    <x-sidebar.sub-link href="{{ route('purchasing.purchase_receipts.index') }}" icon="≡"
                        :active="request()->routeIs('purchasing.purchase_receipts.index')">
                        Daftar GRN
                    </x-sidebar.sub-link>
                    <x-sidebar.sub-link href="{{ route('purchasing.purchase_receipts.create') }}" icon="＋"
                        :active="request()->routeIs('purchasing.purchase_receipts.create')">
                        GRN Baru
                    </x-sidebar.sub-link>
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
                    <x-sidebar.sub-link href="{{ route('marketplace.orders.index') }}" icon="≡"
                        :active="request()->routeIs('marketplace.orders.index')">
                        Daftar Order
                    </x-sidebar.sub-link>
                    <x-sidebar.sub-link href="{{ route('marketplace.orders.create') }}" icon="＋"
                        :active="request()->routeIs('marketplace.orders.create')">
                        Order Manual
                    </x-sidebar.sub-link>
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
                    <x-sidebar.sub-link href="{{ route('sales.invoices.index') }}" icon="≡" :active="request()->routeIs('sales.invoices.index')">
                        Daftar Invoice
                    </x-sidebar.sub-link>
                    <x-sidebar.sub-link href="{{ route('sales.invoices.create') }}" icon="＋"
                        :active="request()->routeIs('sales.invoices.create')">
                        Invoice Baru
                    </x-sidebar.sub-link>

                    <div class="px-3 pt-2 pb-1 text-uppercase"
                        style="font-size:.68rem; letter-spacing:.12em; color:var(--muted);">
                        Shipments
                    </div>

                    <x-sidebar.sub-link href="{{ route('sales.shipments.index') }}" icon="🚚"
                        :active="request()->routeIs('sales.shipments.index')">
                        Daftar Shipment
                    </x-sidebar.sub-link>
                    <x-sidebar.sub-link href="{{ route('sales.shipments.create') }}" icon="＋"
                        :active="request()->routeIs('sales.shipments.create')">
                        Shipment Baru
                    </x-sidebar.sub-link>

                    <div class="px-3 pt-3 pb-1 text-uppercase"
                        style="font-size:.68rem; letter-spacing:.12em; color:var(--muted);">
                        Shipment Returns
                    </div>

                    <x-sidebar.sub-link href="{{ route('sales.shipment_returns.index') }}" icon="🔁"
                        :active="request()->routeIs('sales.shipment_returns.index')">
                        Daftar Retur
                    </x-sidebar.sub-link>
                    <x-sidebar.sub-link href="{{ route('sales.shipment_returns.create') }}" icon="＋"
                        :active="request()->routeIs('sales.shipment_returns.create')">
                        Retur Shipment Baru
                    </x-sidebar.sub-link>

                    <div class="px-3 pt-3 pb-1 text-uppercase"
                        style="font-size:.68rem; letter-spacing:.12em; color:var(--muted);">
                        Sales Reports
                    </div>

                    <x-sidebar.sub-link href="{{ route('sales.shipments.report') }}" icon="📊"
                        :active="request()->routeIs('sales.shipments.report')">
                        Laporan Pengiriman
                    </x-sidebar.sub-link>
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

                    <x-sidebar.sub-link href="{{ route('inventory.stocks.items') }}" icon="📦"
                        :active="request()->routeIs('inventory.stocks.items')">
                        Stok Barang
                    </x-sidebar.sub-link>
                    <x-sidebar.sub-link href="{{ route('inventory.stocks.lots') }}" icon="🎫"
                        :active="request()->routeIs('inventory.stocks.lots')">
                        Stok per LOT
                    </x-sidebar.sub-link>
                    <x-sidebar.sub-link href="{{ route('inventory.stock_card.index') }}" icon="📋"
                        :active="request()->routeIs('inventory.stock_card.*')">
                        Kartu Stok
                    </x-sidebar.sub-link>
                    <x-sidebar.sub-link href="{{ route('inventory.transfers.index') }}" icon="🔁"
                        :active="request()->routeIs('inventory.transfers.index')">
                        Daftar Transfer
                    </x-sidebar.sub-link>
                    <x-sidebar.sub-link href="{{ route('inventory.transfers.create') }}" icon="➕"
                        :active="request()->routeIs('inventory.transfers.create')">
                        Transfer Baru
                    </x-sidebar.sub-link>
                    <x-sidebar.sub-link href="{{ route('inventory.adjustments.index') }}" icon="⚖️"
                        :active="request()->routeIs('inventory.adjustments.*')">
                        Inventory Adjustments
                    </x-sidebar.sub-link>

                    <div class="px-3 pt-3 pb-1 text-uppercase"
                        style="font-size:.68rem; letter-spacing:.12em; color:var(--muted);">
                        Opname
                    </div>

                    <x-sidebar.sub-link href="{{ route('inventory.stock_opnames.index') }}" icon="📊"
                        :active="request()->routeIs('inventory.stock_opnames.*')">
                        Stock Opname
                    </x-sidebar.sub-link>
                    <x-sidebar.sub-link href="{{ route('inventory.stock_opnames.create') }}" icon="＋"
                        :active="request()->routeIs('inventory.stock_opnames.create')">
                        Stock Opname Baru
                    </x-sidebar.sub-link>

                    <div class="px-3 pt-3 pb-1 text-uppercase"
                        style="font-size:.68rem; letter-spacing:.12em; color:var(--muted);">
                        External
                    </div>

                    <x-sidebar.sub-link href="{{ route('inventory.external_transfers.index') }}" icon="🚚"
                        :active="request()->routeIs('inventory.external_transfers.index')">
                        Daftar External TF
                    </x-sidebar.sub-link>
                    <x-sidebar.sub-link href="{{ route('inventory.external_transfers.create') }}" icon="➕"
                        :active="request()->routeIs('inventory.external_transfers.create')">
                        External TF Baru
                    </x-sidebar.sub-link>
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
                    <x-sidebar.sub-link href="{{ route('rts.stock-requests.index') }}" icon="🛒"
                        :active="request()->routeIs('rts.stock-requests.*')" :dot-only="$hasRtsNeedReceive" badge-tone="warn" :badge-title="$rtsBadgeTitle">
                        Permintaan Stock (RTS)
                    </x-sidebar.sub-link>

                    <x-sidebar.sub-link href="{{ route('prd.stock-requests.index') }}" icon="🏭"
                        :active="request()->routeIs('prd.stock-requests.*')" :dot-only="$hasPrdNeedProcess" badge-tone="warn" :badge-title="$prdBadgeTitle">
                        Proses Stock Request (PRD)
                    </x-sidebar.sub-link>
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

                    <x-sidebar.sub-link href="{{ route('production.cutting_jobs.index') }}" icon="✂️"
                        :active="request()->routeIs('production.cutting_jobs.*')">
                        Cutting Jobs
                    </x-sidebar.sub-link>

                    <x-sidebar.sub-link href="{{ route('production.sewing.pickups.index') }}" icon="🧵"
                        :active="request()->routeIs('production.sewing.pickups.*')">
                        Sewing Pickups
                    </x-sidebar.sub-link>

                    <x-sidebar.sub-link href="{{ route('production.sewing.returns.index') }}" icon="📥"
                        :active="request()->routeIs('production.sewing.returns.*')">
                        Sewing Returns
                    </x-sidebar.sub-link>



                    <x-sidebar.sub-link href="{{ route('production.finishing_jobs.index') }}" icon="🧶"
                        :active="request()->routeIs('production.finishing_jobs.*')">
                        Finishing Jobs
                    </x-sidebar.sub-link>

                    {{-- ✅ NEW: Koreksi WIP-FIN --}}
                    <x-sidebar.sub-link href="{{ route('production.wip-fin-adjustments.index') }}" icon="🧾"
                        :active="request()->routeIs('production.wip-fin-adjustments.*')">
                        Koreksi WIP-FIN
                    </x-sidebar.sub-link>


                    <x-sidebar.sub-link href="{{ route('production.packing_jobs.index') }}" icon="📦"
                        :active="request()->routeIs('production.packing_jobs.*')">
                        Packing Jobs
                    </x-sidebar.sub-link>

                    <x-sidebar.sub-link href="{{ route('production.qc.index') }}" icon="✅" :active="request()->routeIs('production.qc.*')">
                        QC Cutting
                    </x-sidebar.sub-link>

                    {{-- Sewing Reports --}}
                    <div class="px-3 pt-3 pb-1 text-uppercase"
                        style="font-size:.68rem; letter-spacing:.12em; color:var(--muted);">
                        Sewing Reports
                    </div>

                    <x-sidebar.sub-link href="{{ route('production.reports.dashboard') }}" icon="📊"
                        :active="request()->routeIs('production.reports.dashboard')">
                        Sewing Dashboard
                    </x-sidebar.sub-link>

                    <x-sidebar.sub-link href="{{ route('production.reports.operators') }}" icon="👥"
                        :active="request()->routeIs('production.reports.operators')">
                        Operator Summary
                    </x-sidebar.sub-link>

                    <x-sidebar.sub-link href="{{ route('production.reports.outstanding') }}" icon="⏳"
                        :active="request()->routeIs('production.reports.outstanding')">
                        Outstanding WIP-SEW
                    </x-sidebar.sub-link>

                    <x-sidebar.sub-link href="{{ route('production.reports.aging_wip_sew') }}" icon="📆"
                        :active="request()->routeIs('production.reports.aging_wip_sew')">
                        Aging WIP-SEW
                    </x-sidebar.sub-link>

                    <x-sidebar.sub-link href="{{ route('production.reports.partial_pickup') }}" icon="🧩"
                        :active="request()->routeIs('production.reports.partial_pickup')">
                        Partial Pickup
                    </x-sidebar.sub-link>

                    <x-sidebar.sub-link href="{{ route('production.reports.productivity') }}" icon="📈"
                        :active="request()->routeIs('production.reports.productivity')">
                        Productivity
                    </x-sidebar.sub-link>

                    <x-sidebar.sub-link href="{{ route('production.reports.reject_analysis') }}" icon="🚫"
                        :active="request()->routeIs('production.reports.reject_analysis')">
                        Reject Analysis
                    </x-sidebar.sub-link>

                    <x-sidebar.sub-link href="{{ route('production.reports.lead_time') }}" icon="⏱️"
                        :active="request()->routeIs('production.reports.lead_time')">
                        Lead Time
                    </x-sidebar.sub-link>

                    <x-sidebar.sub-link href="{{ route('production.reports.operator_behavior') }}" icon="👀"
                        :active="request()->routeIs('production.reports.operator_behavior')">
                        Operator Behavior
                    </x-sidebar.sub-link>

                    {{-- Chain / WIP Reports --}}
                    <div class="px-3 pt-3 pb-1 text-uppercase"
                        style="font-size:.68rem; letter-spacing:.12em; color:var(--muted);">
                        Chain / WIP Reports
                    </div>

                    <x-sidebar.sub-link href="{{ route('production.reports.production_flow_dashboard') }}"
                        icon="🌀" :active="request()->routeIs('production.reports.production_flow_dashboard')">
                        Flow Dashboard
                    </x-sidebar.sub-link>

                    <x-sidebar.sub-link href="{{ route('production.reports.daily_production') }}" icon="📅"
                        :active="request()->routeIs('production.reports.daily_production')">
                        Daily Production
                    </x-sidebar.sub-link>

                    <x-sidebar.sub-link href="{{ route('production.reports.reject_detail') }}" icon="🧾"
                        :active="request()->routeIs('production.reports.reject_detail')">
                        Reject Detail
                    </x-sidebar.sub-link>

                    <x-sidebar.sub-link href="{{ route('production.reports.wip_sewing_age') }}" icon="📆"
                        :active="request()->routeIs('production.reports.wip_sewing_age')">
                        WIP Sewing Age
                    </x-sidebar.sub-link>

                    <x-sidebar.sub-link href="{{ route('production.reports.sewing_per_item') }}" icon="🧵"
                        :active="request()->routeIs('production.reports.sewing_per_item')">
                        Sewing per Item
                    </x-sidebar.sub-link>

                    <x-sidebar.sub-link href="{{ route('production.reports.finishing_jobs') }}" icon="🧶"
                        :active="request()->routeIs('production.reports.finishing_jobs')">
                        Finishing Jobs Report
                    </x-sidebar.sub-link>

                    <x-sidebar.sub-link href="{{ route('production.finishing_jobs.report_per_item') }}"
                        icon="📦" :active="request()->routeIs('production.finishing_jobs.report_per_item*')">
                        Finishing per Item
                    </x-sidebar.sub-link>
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
                    <x-sidebar.sub-link href="{{ route('payroll.cutting.index') }}" icon="✂️"
                        :active="request()->routeIs('payroll.cutting.*')">
                        Cutting Payroll
                    </x-sidebar.sub-link>
                    <x-sidebar.sub-link href="{{ route('payroll.sewing.index') }}" icon="🧵" :active="request()->routeIs('payroll.sewing.*')">
                        Sewing Payroll
                    </x-sidebar.sub-link>
                    <x-sidebar.sub-link href="{{ route('payroll.piece_rates.index') }}" icon="📑"
                        :active="request()->routeIs('payroll.piece_rates.*')">
                        Piece Rates
                    </x-sidebar.sub-link>
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
                    <x-sidebar.sub-link href="{{ route('costing.hpp.index') }}" icon="⚙️" :active="request()->routeIs('costing.hpp.*')">
                        HPP Finished Goods
                    </x-sidebar.sub-link>
                    <x-sidebar.sub-link href="{{ route('costing.production_cost_periods.index') }}" icon="📆"
                        :active="request()->routeIs('costing.production_cost_periods.*')">
                        Production Cost Periods
                    </x-sidebar.sub-link>
                </div>
            </li>

        @endif
    </ul>
</aside>
