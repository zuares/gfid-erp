{{-- resources/views/layouts/partials/sidebar.blade.php --}}
@php
    // Flag untuk buka/tutup collapse
    $poOpen = request()->routeIs('purchasing.purchase_orders.*');
    $grnOpen = request()->routeIs('purchasing.purchase_receipts.*');
    $invOpen = request()->routeIs('inventory.*');
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

        /* Biar konten geser ke kanan 240px */
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
        transition: background .18s ease, box-shadow .18s ease, transform .12s ease;
    }

    .sidebar-link .icon {
        width: 22px;
        font-size: 1.05rem;
        text-align: center;
    }

    .sidebar-link:hover {
        background: color-mix(in srgb, var(--accent-soft) 30%, var(--card) 70%);
        box-shadow: inset 0 0 0 1px var(--line);
        transform: translateX(1px);
    }

    /* ACTIVE UTAMA: tanpa geser padding kiri */
    .sidebar-link.active {
        background: color-mix(in srgb, var(--accent-soft) 60%, var(--card) 40%);
        font-weight: 600;
        box-shadow: inset 2px 0 0 var(--accent);
    }

    /* GROUP TOGGLE (header collapse) */
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

    /* Saat group open, kasih indikasi halus (bukan active penuh) */
    .sidebar-toggle.is-open {
        background: color-mix(in srgb, var(--accent-soft) 25%, var(--card) 75%);
        box-shadow: inset 0 0 0 1px rgba(148, 163, 184, .6);
    }

    /* SUB LINK: List & Create di dalam collapse */
    .sidebar-link-sub {
        font-size: .86rem;
        padding: .4rem .9rem .4rem 2.3rem;
        opacity: .9;
        border-radius: 10px;
    }

    .sidebar-link-sub .icon {
        width: 18px;
        font-size: .9rem;
    }

    /* Hover: lebih transparan dan TIDAK geser */
    .sidebar-link-sub:hover {
        background: color-mix(in srgb, var(--accent-soft) 24%, var(--card) 76%);
        box-shadow: inset 0 0 0 1px var(--line);
        transform: none;
    }

    /* Active SUBMENU: hanya submenu yang “nyala”, parent tidak */
    .sidebar-link-sub.active {
        background: color-mix(in srgb, var(--accent-soft) 70%, var(--card) 30%);
        font-weight: 600;
        box-shadow: inset 2px 0 0 var(--accent);
        opacity: 1;
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

        {{-- GROUP: Purchase Orders --}}
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

        {{-- GROUP: Goods Receipts (GRN) --}}
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

        {{-- WAREHOUSE / INVENTORY --}}
        <li class="mt-2 text-uppercase small menu-label">Inventory</li>

        {{-- GROUP: Inventory (Stock Card + Transfers) --}}
        <li class="mb-1">
            <button class="sidebar-link sidebar-toggle {{ $invOpen ? 'is-open' : '' }}" type="button"
                data-bs-toggle="collapse" data-bs-target="#navInventory"
                aria-expanded="{{ $invOpen ? 'true' : 'false' }}" aria-controls="navInventory">
                <span class="icon">📦</span>
                <span>Inventory</span>
                <span class="chevron">▸</span>
            </button>

            <div class="collapse {{ $invOpen ? 'show' : '' }}" id="navInventory">
                {{-- Kartu Stok --}}
                <a href="{{ route('inventory.stock_card.index') }}"
                    class="sidebar-link sidebar-link-sub {{ request()->routeIs('inventory.stock_card.index') ? 'active' : '' }}">
                    <span class="icon">📋</span>
                    <span>Kartu Stok</span>
                </a>

                {{-- Daftar Transfer --}}
                <a href="{{ route('inventory.transfers.index') }}"
                    class="sidebar-link sidebar-link-sub {{ request()->routeIs('inventory.transfers.index') ? 'active' : '' }}">
                    <span class="icon">🔁</span>
                    <span>Daftar Transfer</span>
                </a>

                {{-- Transfer Baru --}}
                <a href="{{ route('inventory.transfers.create') }}"
                    class="sidebar-link sidebar-link-sub {{ request()->routeIs('inventory.transfers.create') ? 'active' : '' }}">
                    <span class="icon">➕</span>
                    <span>Transfer Baru</span>
                </a>
            </div>
        </li>

        {{-- PRODUCTION --}}
        <li class="mt-2 text-uppercase small menu-label">Production</li>
        <li>
            <a href="#" class="sidebar-link">
                <span class="icon">✂️</span>
                <span>Cutting &amp; Sewing</span>
            </a>
        </li>

        <li>
            <a href="#" class="sidebar-link">
                <span class="icon">🧵</span>
                <span>Finishing</span>
            </a>
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
