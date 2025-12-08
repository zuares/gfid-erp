{{-- resources/views/components/mobile-bottom-nav.blade.php --}}
@php
    $userRole = auth()->user()->role ?? null;

    // Flag aktif per tab umum (default)
    $isDashboard = request()->routeIs('dashboard');
    $isProduction = request()->routeIs('production.*');
    $isInventory = request()->routeIs('inventory.*');
    $isProfile = request()->routeIs('profile.*') || request()->routeIs('settings.*');

    // Khusus operating: tab Cutting, QC, Sewing, Finishing
    $isCuttingTab = request()->routeIs('production.cutting_jobs.*');
    $isQcTab = request()->routeIs('production.qc.*');
    $isSewingTab =
        request()->routeIs('production.sewing_pickups.*') || request()->routeIs('production.sewing_returns.*');
    $isFinishingTab = request()->routeIs('production.finishing_jobs.*');

    // Dashboard tab khusus operating → pakai halaman Sewing Operator Summary
    if ($userRole === 'operating') {
        $isDashboardTab = request()->routeIs('production.reports.operators');
    } else {
        $isDashboardTab = $isDashboard;
    }
@endphp

<style>
    .mobile-bottom-nav {
        position: fixed;
        left: 0;
        right: 0;
        bottom: 0;

        height: calc(66px + env(safe-area-inset-bottom));
        padding: .35rem .85rem calc(.55rem + env(safe-area-inset-bottom));

        display: flex;
        flex-wrap: nowrap;
        justify-content: space-between;
        align-items: center;
        gap: .25rem;

        background:
            linear-gradient(to top,
                color-mix(in srgb, var(--card) 94%, var(--bg) 6%) 0%,
                color-mix(in srgb, var(--card) 86%, var(--bg) 14%) 100%);
        border-top: 1px solid color-mix(in srgb, var(--line) 76%, transparent 24%);
        border-top-left-radius: 18px;
        border-top-right-radius: 18px;
        box-shadow:
            0 -12px 30px rgba(15, 23, 42, 0.32),
            0 0 0 1px rgba(15, 23, 42, 0.02);
        backdrop-filter: blur(16px);
        -webkit-backdrop-filter: blur(16px);
        z-index: 9999;
        transform: translateZ(0);
        will-change: transform;
    }

    .mobile-bottom-nav .nav-item {
        position: relative;
        flex: 0 0 auto;
        min-width: 54px;

        text-align: center;
        padding-top: .2rem;
        padding-bottom: .2rem;
        color: var(--muted);
        font-size: .72rem;
        font-weight: 500;
        text-decoration: none;

        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: .16rem;

        transition:
            color .18s ease,
            transform .14s ease;
    }

    .mobile-bottom-nav .nav-item::before {
        content: '';
        position: absolute;
        inset: .12rem .32rem;
        border-radius: 999px;
        background: radial-gradient(circle at 30% 0%,
                color-mix(in srgb, var(--accent) 26%, transparent 74%) 0,
                transparent 62%);
        opacity: 0;
        transform: scale(.9);
        transition:
            opacity .18s ease,
            transform .18s ease;
        z-index: 0;
    }

    .mobile-bottom-nav .nav-item.active::before {
        opacity: 1;
        transform: scale(1);
    }

    .mobile-bottom-nav .nav-item .icon,
    .mobile-bottom-nav .nav-item .label {
        position: relative;
        z-index: 1;
    }

    .mobile-bottom-nav .nav-item .label {
        color: inherit;
        letter-spacing: .01em;
        line-height: 1.1;
    }

    .mobile-bottom-nav .nav-item.active {
        color: var(--accent);
    }

    .mobile-bottom-nav .nav-item:active {
        transform: translateY(1px) scale(.98);
    }

    .mobile-bottom-nav .icon svg {
        width: 22px;
        height: 22px;
        stroke-width: 2.15;
        stroke: currentColor;
        fill: none;
    }

    /* FAB tengah */
    .mobile-bottom-nav .center-btn {
        position: relative;
        top: -18px;
        flex: 0 0 auto;
    }

    .mobile-bottom-nav .center-icon {
        width: 56px;
        height: 56px;
        border-radius: 999px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: radial-gradient(circle at 30% 0%,
                color-mix(in srgb, var(--accent) 12%, #ffffff 88%) 0,
                color-mix(in srgb, var(--accent) 90%, #000000 10%) 70%);
        color: #fff;
        border: 1px solid color-mix(in srgb, var(--accent) 45%, #000 55%);
        box-shadow:
            0 12px 28px rgba(15, 23, 42, .28),
            0 0 0 1px rgba(15, 23, 42, .08);
        transition:
            transform .12s ease,
            box-shadow .12s ease,
            filter .12s ease;
    }

    .mobile-bottom-nav .center-icon svg {
        width: 26px;
        height: 26px;
        stroke-width: 2.2;
        stroke: currentColor;
        fill: none;
    }

    .mobile-bottom-nav .center-icon:active {
        transform: scale(.94) translateY(1px);
        box-shadow: 0 6px 16px rgba(15, 23, 42, .22);
        filter: brightness(.96);
    }

    /* TWEAK KHUSUS ROLE OPERATING: lebih mini supaya muat di Android kecil */
    .mobile-bottom-nav--operating .nav-item {
        font-size: .68rem;
        min-width: 50px;
        gap: .12rem;
    }

    .mobile-bottom-nav--operating .icon svg {
        width: 20px;
        height: 20px;
    }

    .mobile-bottom-nav--operating .center-icon {
        width: 52px;
        height: 52px;
    }

    @media (max-width: 400px) {
        .mobile-bottom-nav {
            padding-inline: .55rem;
            gap: .18rem;
        }

        .mobile-bottom-nav .nav-item {
            font-size: .66rem;
            min-width: 46px;
        }

        .mobile-bottom-nav .icon svg {
            width: 19px;
            height: 19px;
        }

        .mobile-bottom-nav--operating .center-icon {
            width: 48px;
            height: 48px;
        }
    }

    @media (min-width: 768px) {
        .mobile-bottom-nav {
            display: none;
        }
    }
</style>

<div class="mobile-bottom-nav {{ $userRole === 'operating' ? 'mobile-bottom-nav--operating' : '' }}">
    @if ($userRole === 'operating')
        {{-- ====== VARIAN UNTUK ROLE OPERATING ====== --}}
        @php
            $dashboardHref = Route::has('production.reports.operators')
                ? route('production.reports.operators')
                : (Route::has('dashboard')
                    ? route('dashboard')
                    : '#');

            $cuttingCreateHref = Route::has('production.cutting_jobs.create')
                ? route('production.cutting_jobs.create')
                : '#';

            $qcIndexHref = Route::has('production.qc.index') ? route('production.qc.index') : '#';

            $sewingCreateHref = Route::has('production.sewing_returns.create')
                ? route('production.sewing_returns.create')
                : '#';

            $finishingCreateHref = Route::has('production.finishing_jobs.create')
                ? route('production.finishing_jobs.create')
                : '#';
        @endphp

        {{-- CUTTING (Create) --}}
        <a href="{{ $cuttingCreateHref }}" class="nav-item {{ $isCuttingTab ? 'active' : '' }}">
            <span class="icon">
                <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <circle cx="7" cy="8" r="2.5" />
                    <circle cx="7" cy="16" r="2.5" />
                    <path d="M9 14 17.5 5.5" />
                    <path d="M9 10 17.5 18.5" />
                    <path d="M18 5.5 20 3.5" />
                    <path d="M18 18.5 20 20.5" />
                </svg>
            </span>
            <span class="label">Cutting</span>
        </a>

        {{-- QC (Index) --}}
        <a href="{{ $qcIndexHref }}" class="nav-item {{ $isQcTab ? 'active' : '' }}">
            <span class="icon">
                <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path d="M12 3 5 6v6c0 4 3 6.5 7 9 4-2.5 7-5 7-9V6z" />
                    <path d="M9 12.5 11 14.5 15 10.5" />
                </svg>
            </span>
            <span class="label">QC</span>
        </a>

        {{-- HOME (Dashboard / Operator Summary) --}}
        <a href="{{ $dashboardHref }}" class="nav-item center-btn {{ $isDashboardTab ? 'active' : '' }}">
            <div class="center-icon">
                <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path d="M4 10.5 11.24 4.6a1.1 1.1 0 0 1 1.52 0L20 10.5" />
                    <path d="M6.5 9.5V18a1.5 1.5 0 0 0 1.5 1.5h8a1.5 1.5 0 0 0 1.5-1.5V9.5" />
                    <path d="M10 19.5V13.5a2 2 0 0 1 2-2 2 2 0 0 1 2 2v6" />
                </svg>
            </div>
        </a>

        {{-- SEWING (Create Return) --}}
        <a href="{{ $sewingCreateHref }}" class="nav-item {{ $isSewingTab ? 'active' : '' }}">
            <span class="icon">
                <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path d="M9 4.5c0-1.1.9-2 2-2s2 .9 2 2c0 .7-.4 1.3-.9 1.7L9 10l3 3" />
                    <path d="M7 21 17 11" />
                    <path d="M5 19c2 1.5 4.5 2 7 2 2.1 0 4.2-.4 6-1.2" />
                </svg>
            </span>
            <span class="label">Sewing</span>
        </a>

        {{-- FINISHING (Create) --}}
        <a href="{{ $finishingCreateHref }}" class="nav-item {{ $isFinishingTab ? 'active' : '' }}">
            <span class="icon">
                <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path d="M12 4.5a1.75 1.75 0 0 1 1.7 2.3L12 8.5l-1 1" />
                    <path d="M4 19.5 5.5 11 12 8.5 18.5 11 20 19.5" />
                    <path d="M4 19.5h16" />
                </svg>
            </span>
            <span class="label">Finishing</span>
        </a>
    @else
        {{-- ====== VARIAN DEFAULT (owner/admin, dll) ====== --}}

        <a href="{{ route('dashboard') }}" class="nav-item {{ $isDashboard ? 'active' : '' }}">
            <span class="icon">
                <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path d="M4 10.5 11.24 4.6a1.1 1.1 0 0 1 1.52 0L20 10.5" />
                    <path d="M6.5 9.5V18a1.5 1.5 0 0 0 1.5 1.5h8a1.5 1.5 0 0 0 1.5-1.5V9.5" />
                    <path d="M10 19.5V13.5a2 2 0 0 1 2-2 2 2 0 0 1 2 2v6" />
                </svg>
            </span>
            <span class="label">Home</span>
        </a>

        @php
            $prodHref = Route::has('production.cutting_jobs.index') ? route('production.cutting_jobs.index') : '#';
        @endphp

        <a href="{{ $prodHref }}" class="nav-item {{ $isProduction ? 'active' : '' }}">
            <span class="icon">
                <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <circle cx="7" cy="8" r="2.5" />
                    <circle cx="7" cy="16" r="2.5" />
                    <path d="M9 14 17.5 5.5" />
                    <path d="M9 10 17.5 18.5" />
                    <path d="M18 5.5 20 3.5" />
                    <path d="M18 18.5 20 20.5" />
                </svg>
            </span>
            <span class="label">Prod</span>
        </a>

        @php
            $fabHref = Route::has('production.sewing_pickups.create') ? route('production.sewing_pickups.create') : '#';
        @endphp

        <a href="{{ $fabHref }}" class="nav-item center-btn">
            <div class="center-icon">
                <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path d="M12 6v12" />
                    <path d="M6 12h12" />
                </svg>
            </div>
        </a>

        @if (in_array($userRole, ['owner', 'admin', 'operating']))
            <a href="{{ Route::has('inventory.stock_card.index') ? route('inventory.stock_card.index') : '#' }}"
                class="nav-item {{ $isInventory ? 'active' : '' }}">
                <span class="icon">
                    <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path d="M4.5 9 12 4.5 19.5 9" />
                        <path d="M5 9.5v6L12 20l7-4.5v-6" />
                        <path d="M9 11.5 15 15" />
                        <path d="M15 11.5 9 15" />
                    </svg>
                </span>
                <span class="label">Stok</span>
            </a>
        @endif

        <a href="{{ Route::has('profile.edit') ? route('profile.edit') : '#' }}"
            class="nav-item {{ $isProfile ? 'active' : '' }}">
            <span class="icon">
                <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path d="M12 12.5a3.5 3.5 0 1 0-0.01-7 3.5 3.5 0 0 0 0.01 7Z" />
                    <path d="M5.5 19.5a6.5 6.5 0 0 1 13 0" />
                </svg>
            </span>
            <span class="label">Profil</span>
        </a>
    @endif
</div>
