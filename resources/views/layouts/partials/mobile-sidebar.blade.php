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
    // flag active biar mirip desktop
    $poOpen = request()->routeIs('purchasing.purchase_orders.*');
    $grnOpen = request()->routeIs('purchasing.purchase_receipts.*');

    $invOpen =
        request()->routeIs('inventory.stock_card.*') ||
        request()->routeIs('inventory.transfers.*') ||
        request()->routeIs('inventory.stocks.*');

    $extInvOpen = request()->routeIs('inventory.external_transfers.*');

    $prodCutOpen = request()->routeIs('production.cutting_jobs.*');
    $prodQcOpen = request()->routeIs('production.qc.*');

    $prodSewOpen =
        request()->routeIs('production.sewing_pickups.*') || request()->routeIs('production.sewing_returns.*');

    $prodFinOpen =
        request()->routeIs('production.finishing_jobs.*') ||
        request()->routeIs('production.finishing_jobs.bundles_ready');

    // Production Reports (SAAT INI: hanya operator sewing + finishing per item)
    $prodReportOpen =
        request()->routeIs('production.sewing_returns.report_operators') ||
        request()->routeIs('production.finishing_jobs.report_per_item') ||
        request()->routeIs('production.finishing_jobs.report_per_item_detail');
@endphp

{{-- OVERLAY --}}
<div id="mobileSidebarOverlay" class="mobile-sidebar-overlay"></div>

{{-- PANEL --}}
<aside id="mobileSidebarPanel" class="mobile-sidebar-panel">
    <div class="mobile-sidebar-header">
        <div class="mobile-sidebar-title">
            {{ config('app.name', 'GFID') }}
        </div>
        <button type="button" class="mobile-sidebar-close-btn" id="mobileSidebarCloseBtn">
            ✕
        </button>
    </div>

    <div class="mobile-sidebar-body">
        <ul class="mobile-sidebar-nav">
            @auth
                {{-- DASHBOARD --}}
                <li>
                    <a href="{{ route('dashboard') }}"
                        class="mobile-sidebar-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                        <span class="icon">🏠</span>
                        <span>Dashboard</span>
                    </a>
                </li>

                {{-- PURCHASING --}}
                <div class="mobile-sidebar-section-label">Purchasing</div>

                {{-- Purchase Orders --}}
                <li class="mb-1">
                    <button class="mobile-sidebar-link mobile-sidebar-toggle {{ $poOpen ? 'is-open' : '' }}" type="button"
                        data-bs-toggle="collapse" data-bs-target="#navPurchasingPOmobile"
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

                {{-- Goods Receipts --}}
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

                {{-- INVENTORY --}}
                <div class="mobile-sidebar-section-label">Inventory</div>

                {{-- Inventory Internal --}}
                <li class="mb-1">
                    <button class="mobile-sidebar-link mobile-sidebar-toggle {{ $invOpen ? 'is-open' : '' }}"
                        type="button" data-bs-toggle="collapse" data-bs-target="#navInventoryMobile"
                        aria-expanded="{{ $invOpen ? 'true' : 'false' }}" aria-controls="navInventoryMobile">
                        <span class="icon">📦</span>
                        <span>Inventory</span>
                        <span class="chevron">▸</span>
                    </button>

                    <div class="collapse {{ $invOpen ? 'show' : '' }}" id="navInventoryMobile">
                        <a href="{{ route('inventory.stocks.items') }}"
                            class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('inventory.stocks.items') ? 'active' : '' }}">
                            <span class="icon">📦</span>
                            <span>Stok per Item</span>
                        </a>

                        <a href="{{ route('inventory.stocks.lots') }}"
                            class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('inventory.stocks.lots') ? 'active' : '' }}">
                            <span class="icon">🎫</span>
                            <span>Stok per LOT</span>
                        </a>

                        <a href="{{ route('inventory.stock_card.index') }}"
                            class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('inventory.stock_card.index') ? 'active' : '' }}">
                            <span class="icon">📋</span>
                            <span>Kartu Stok</span>
                        </a>

                        <a href="{{ route('inventory.transfers.index') }}"
                            class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('inventory.transfers.index') ? 'active' : '' }}">
                            <span class="icon">🔁</span>
                            <span>Daftar Transfer</span>
                        </a>

                        <a href="{{ route('inventory.transfers.create') }}"
                            class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('inventory.transfers.create') ? 'active' : '' }}">
                            <span class="icon">➕</span>
                            <span>Transfer Baru</span>
                        </a>
                    </div>
                </li>

                {{-- External Transfers --}}
                <li class="mb-1">
                    <button class="mobile-sidebar-link mobile-sidebar-toggle {{ $extInvOpen ? 'is-open' : '' }}"
                        type="button" data-bs-toggle="collapse" data-bs-target="#navInventoryExternalMobile"
                        aria-expanded="{{ $extInvOpen ? 'true' : 'false' }}" aria-controls="navInventoryExternalMobile">
                        <span class="icon">🚚</span>
                        <span>External Transfers</span>
                        <span class="chevron">▸</span>
                    </button>

                    <div class="collapse {{ $extInvOpen ? 'show' : '' }}" id="navInventoryExternalMobile">
                        <a href="{{ route('inventory.external_transfers.index') }}"
                            class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('inventory.external_transfers.index') ? 'active' : '' }}">
                            <span class="icon">≡</span>
                            <span>Daftar External TF</span>
                        </a>

                        <a href="{{ route('inventory.external_transfers.create') }}"
                            class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('inventory.external_transfers.create') ? 'active' : '' }}">
                            <span class="icon">➕</span>
                            <span>External TF Baru</span>
                        </a>
                    </div>
                </li>

                {{-- PRODUCTION --}}
                <div class="mobile-sidebar-section-label">Production</div>

                {{-- Cutting Jobs --}}
                <li class="mb-1">
                    <button class="mobile-sidebar-link mobile-sidebar-toggle {{ $prodCutOpen ? 'is-open' : '' }}"
                        type="button" data-bs-toggle="collapse" data-bs-target="#navProductionCuttingMobile"
                        aria-expanded="{{ $prodCutOpen ? 'true' : 'false' }}" aria-controls="navProductionCuttingMobile">
                        <span class="icon">✂️</span>
                        <span>Cutting Jobs</span>
                        <span class="chevron">▸</span>
                    </button>

                    <div class="collapse {{ $prodCutOpen ? 'show' : '' }}" id="navProductionCuttingMobile">
                        <a href="{{ route('production.cutting_jobs.index') }}"
                            class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('production.cutting_jobs.index') ? 'active' : '' }}">
                            <span class="icon">≡</span>
                            <span>Daftar Cutting Job</span>
                        </a>

                        <a href="{{ route('production.cutting_jobs.create') }}"
                            class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('production.cutting_jobs.create') ? 'active' : '' }}">
                            <span class="icon">＋</span>
                            <span>Cutting Job Baru</span>
                        </a>
                    </div>
                </li>

                {{-- Sewing --}}
                <li class="mb-1">
                    <button class="mobile-sidebar-link mobile-sidebar-toggle {{ $prodSewOpen ? 'is-open' : '' }}"
                        type="button" data-bs-toggle="collapse" data-bs-target="#navProductionSewingMobile"
                        aria-expanded="{{ $prodSewOpen ? 'true' : 'false' }}" aria-controls="navProductionSewingMobile">
                        <span class="icon">🧵</span>
                        <span>Sewing</span>
                        <span class="chevron">▸</span>
                    </button>

                    <div class="collapse {{ $prodSewOpen ? 'show' : '' }}" id="navProductionSewingMobile">
                        <a href="{{ route('production.sewing_pickups.index') }}"
                            class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('production.sewing_pickups.index') ? 'active' : '' }}">
                            <span class="icon">📤</span>
                            <span>Sewing Pickups</span>
                        </a>

                        <a href="{{ route('production.sewing_pickups.create') }}"
                            class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('production.sewing_pickups.create') ? 'active' : '' }}">
                            <span class="icon">＋</span>
                            <span>Pickup Baru</span>
                        </a>

                        <a href="{{ route('production.sewing_returns.index') }}"
                            class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('production.sewing_returns.index') ? 'active' : '' }}">
                            <span class="icon">📥</span>
                            <span>Sewing Returns</span>
                        </a>

                        <a href="{{ route('production.sewing_returns.create') }}"
                            class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('production.sewing_returns.create') ? 'active' : '' }}">
                            <span class="icon">＋</span>
                            <span>Return Baru</span>
                        </a>
                    </div>
                </li>

                {{-- Finishing --}}
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
                            class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('production.finishing_jobs.index') ? 'active' : '' }}">
                            <span class="icon">≡</span>
                            <span>Daftar Finishing Job</span>
                        </a>

                        <a href="{{ route('production.finishing_jobs.create') }}"
                            class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('production.finishing_jobs.create') ? 'active' : '' }}">
                            <span class="icon">＋</span>
                            <span>Finishing Job Baru</span>
                        </a>

                        <a href="{{ route('production.finishing_jobs.bundles_ready') }}"
                            class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('production.finishing_jobs.bundles_ready') ? 'active' : '' }}">
                            <span class="icon">📦</span>
                            <span>Bundles Ready for Finishing</span>
                        </a>
                    </div>
                </li>

                {{-- QC --}}
                <li class="mb-1">
                    <button class="mobile-sidebar-link mobile-sidebar-toggle {{ $prodQcOpen ? 'is-open' : '' }}"
                        type="button" data-bs-toggle="collapse" data-bs-target="#navProductionQcMobile"
                        aria-expanded="{{ $prodQcOpen ? 'true' : 'false' }}" aria-controls="navProductionQcMobile">
                        <span class="icon">✅</span>
                        <span>Quality Control</span>
                        <span class="chevron">▸</span>
                    </button>

                    <div class="collapse {{ $prodQcOpen ? 'show' : '' }}" id="navProductionQcMobile">
                        <a href="{{ route('production.qc.index') }}"
                            class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('production.qc.index') || request()->routeIs('production.qc.cutting.*') ? 'active' : '' }}">
                            <span class="icon">✂️</span>
                            <span>QC Cutting</span>
                        </a>
                    </div>
                </li>

                {{-- Laporan Produksi (match desktop: hanya route yang ada) --}}
                <li class="mb-1">
                    <button class="mobile-sidebar-link mobile-sidebar-toggle {{ $prodReportOpen ? 'is-open' : '' }}"
                        type="button" data-bs-toggle="collapse" data-bs-target="#navProductionReportsMobile"
                        aria-expanded="{{ $prodReportOpen ? 'true' : 'false' }}"
                        aria-controls="navProductionReportsMobile">
                        <span class="icon">📈</span>
                        <span>Laporan Produksi</span>
                        <span class="chevron">▸</span>
                    </button>

                    <div class="collapse {{ $prodReportOpen ? 'show' : '' }}" id="navProductionReportsMobile">
                        <a href="{{ route('production.sewing_returns.report_operators') }}"
                            class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('production.sewing_returns.report_operators') ? 'active' : '' }}">
                            <span class="icon">🧍</span>
                            <span>Performa Operator Jahit</span>
                        </a>

                        <a href="{{ route('production.finishing_jobs.report_per_item') }}"
                            class="mobile-sidebar-link mobile-sidebar-link-sub {{ request()->routeIs('production.finishing_jobs.report_per_item') || request()->routeIs('production.finishing_jobs.report_per_item_detail') ? 'active' : '' }}">
                            <span class="icon">📦</span>
                            <span>Finishing per Item (WIP-FIN → FG)</span>
                        </a>
                    </div>
                </li>

                {{-- FINANCE --}}
                <div class="mobile-sidebar-section-label">Finance</div>

                <li>
                    <a href="#" class="mobile-sidebar-link">
                        <span class="icon">💰</span>
                        <span>Payroll</span>
                    </a>
                </li>

                <li>
                    <a href="#" class="mobile-sidebar-link">
                        <span class="icon">📊</span>
                        <span>Reports</span>
                    </a>
                </li>
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
                if (sidebar.classList.contains('is-open')) {
                    closeSidebar();
                } else {
                    openSidebar();
                }
            });

            closeBtn?.addEventListener('click', closeSidebar);
            overlay.addEventListener('click', closeSidebar);

            // Auto-close tiap kali user klik link menu
            links.forEach(link => {
                link.addEventListener('click', () => {
                    closeSidebar();
                });
            });

            document.addEventListener('keyup', function(e) {
                if (e.key === 'Escape') {
                    closeSidebar();
                }
            });
        });
    </script>
@endpush
