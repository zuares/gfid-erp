{{-- resources/views/layouts/partials/sidebar.blade.php --}}
@php
    // Flag untuk buka/tutup collapse per grup
    $poOpen = request()->routeIs('purchasing.purchase_orders.*');
    $grnOpen = request()->routeIs('purchasing.purchase_receipts.*');

    // Inventory internal (stock card + transfers + stock items/lots)
    $invOpen =
        request()->routeIs('inventory.stock_card.*') ||
        request()->routeIs('inventory.transfers.*') ||
        request()->routeIs('inventory.stocks.*');

    // Inventory external transfers
    $extInvOpen = request()->routeIs('inventory.external_transfers.*');

    // Production Cutting Jobs
    $prodCutOpen = request()->routeIs('production.cutting_jobs.*');

    // Production Sewing (pickups + returns)
    $prodSewOpen =
        request()->routeIs('production.sewing_pickups.*') || request()->routeIs('production.sewing_returns.*');

    // Production Finishing
    $prodFinOpen =
        request()->routeIs('production.finishing_jobs.*') ||
        request()->routeIs('production.finishing_jobs.bundles_ready');

    // Production Packing
    $prodPackOpen =
        request()->routeIs('production.packing_jobs.*') || request()->routeIs('production.packing.fg_ready');

    // Production QC (cutting / sewing nanti)
    $prodQcOpen = request()->routeIs('production.qc.*');

    // Production Reports (saat ini: operator sewing + finishing per item)
    $prodReportOpen =
        request()->routeIs('production.sewing_returns.report_operators') ||
        request()->routeIs('production.finishing_jobs.report_per_item') ||
        request()->routeIs('production.finishing_jobs.report_per_item_detail');
@endphp

<style>
    /* =======================================
       SIDEBAR FIXED MODERN (desktop only)
    ======================================= */

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

        {{-- PURCHASING --}}
        <li class="mt-2 text-uppercase small menu-label">Purchasing</li>

        {{-- Purchase Orders --}}
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

        {{-- Goods Receipts --}}
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

        {{-- INVENTORY --}}
        <li class="mt-2 text-uppercase small menu-label">Inventory</li>

        {{-- Inventory Internal --}}
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
            </div>
        </li>

        {{-- External Transfers --}}
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

        {{-- PRODUCTION --}}
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

                <a href="{{ route('production.packing.fg_ready') }}"
                    class="sidebar-link sidebar-link-sub {{ request()->routeIs('production.packing.fg_ready') ? 'active' : '' }}">
                    <span class="icon">📦</span>
                    <span>FG Ready to Pack</span>
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
                {{-- nanti: QC Sewing, QC Packing --}}
            </div>
        </li>

        {{-- Laporan Produksi (yang route-nya sudah ada) --}}
        <li class="mb-1">
            <button class="sidebar-link sidebar-toggle {{ $prodReportOpen ? 'is-open' : '' }}" type="button"
                data-bs-toggle="collapse" data-bs-target="#navProductionReports"
                aria-expanded="{{ $prodReportOpen ? 'true' : 'false' }}" aria-controls="navProductionReports">
                <span class="icon">📈</span>
                <span>Laporan Produksi</span>
                <span class="chevron">▸</span>
            </button>

            <div class="collapse {{ $prodReportOpen ? 'show' : '' }}" id="navProductionReports">
                <a href="{{ route('production.sewing_returns.report_operators') }}"
                    class="sidebar-link sidebar-link-sub {{ request()->routeIs('production.sewing_returns.report_operators') ? 'active' : '' }}">
                    <span class="icon">🧍</span>
                    <span>Performa Operator Jahit</span>
                </a>

                <a href="{{ route('production.finishing_jobs.report_per_item') }}"
                    class="sidebar-link sidebar-link-sub {{ request()->routeIs('production.finishing_jobs.report_per_item') || request()->routeIs('production.finishing_jobs.report_per_item_detail') ? 'active' : '' }}">
                    <span class="icon">📦</span>
                    <span>Finishing per Item (WIP-FIN → FG)</span>
                </a>
            </div>
        </li>

        {{-- FINANCE --}}
        <li class="mt-2 text-uppercase small menu-label">Finance</li>
        <li>
            <a href="#" class="sidebar-link">
                <span class="icon">💰</span>
                <span>Payroll</span>
            </a>
        </li>

        <li>
            <a href="#" class="sidebar-link">
                <span class="icon">📊</span>
                <span>Reports</span>
            </a>
        </li>
    </ul>
</aside>
