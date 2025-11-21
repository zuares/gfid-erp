{{-- resources/views/layouts/partials/navbar.blade.php --}}

<style>
    /* ============================
       GLOBAL NAVBAR LAYOUT
    ============================ */
    .app-navbar {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;

        height: 56px;
        z-index: 1050;

        background: color-mix(in srgb, var(--card) 82%, var(--bg) 18%);
        border-bottom: 1px solid var(--line);

        backdrop-filter: blur(14px) saturate(160%);
        -webkit-backdrop-filter: blur(14px) saturate(160%);

        display: flex;
        align-items: center;
    }

    body {
        /* kasih ruang buat navbar fixed */
        padding-top: 58px;
    }

    .app-navbar .navbar-inner {
        max-width: 100%;
        margin-inline: auto;
        width: 100%;

        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: .5rem;
        padding-inline: .75rem;
    }

    /* BRAND */
    .app-navbar .navbar-brand {
        font-size: 1.02rem;
        font-weight: 600;
        letter-spacing: -0.3px;
        color: var(--text) !important;
        padding: 0;
        margin: 0;
    }

    /* THEME BUTTON */
    .theme-toggle-btn {
        border-radius: 999px;
        border: 1px solid var(--line);
        padding: .26rem .7rem;
        font-size: .8rem;

        background: color-mix(in srgb, var(--card) 90%, var(--accent-soft) 10%);
        color: var(--text);

        display: inline-flex;
        align-items: center;
        gap: .35rem;

        cursor: pointer;
    }

    .theme-toggle-btn .icon {
        font-size: 1rem;
        line-height: 1;
    }

    .theme-toggle-btn .label {
        font-size: .78rem;
    }

    /* MOBILE: sembunyikan label supaya hemat space */
    @media (max-width: 767.98px) {
        .theme-toggle-btn .label {
            display: none;
        }
    }

    /* HAMBURGER */
    .mobile-menu-btn {
        border: 0;
        background: transparent;
        padding: .2rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: var(--muted);
        cursor: pointer;
    }

    .mobile-menu-btn svg {
        width: 22px;
        height: 22px;
        stroke-width: 2.2;
    }

    :root[data-theme="dark"] .mobile-menu-btn {
        color: var(--text);
    }

    /* DESKTOP NAV LINKS */
    .app-navbar .desktop-nav {
        display: none;
    }

    .app-navbar .nav-link {
        color: var(--text) !important;
        font-size: .88rem;
        padding-inline: .75rem;
        opacity: .92;
    }

    .app-navbar .nav-link:hover {
        opacity: 1;
    }

    .app-navbar .nav-link.active {
        font-weight: 600;
        color: var(--accent) !important;
    }

    .app-navbar .dropdown-menu {
        background: var(--card);
        border: 1px solid var(--line);
        box-shadow: 0 8px 25px rgba(15, 23, 42, 0.25);
    }

    .app-navbar .dropdown-item {
        color: var(--text);
    }

    .app-navbar .dropdown-item:hover {
        background: color-mix(in srgb, var(--accent-soft) 80%, var(--card) 20%);
        color: var(--accent);
    }

    @media (min-width: 768px) {
        .app-navbar {
            height: 60px;
        }

        body {
            padding-top: 62px;
        }

        .app-navbar .navbar-inner {
            padding-inline: 1rem;
        }

        .app-navbar .desktop-nav {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
    }
</style>

<nav class="app-navbar">
    <div class="navbar-inner">

        {{-- LEFT: Brand --}}
        <a class="navbar-brand" href="{{ url('/') }}">
            {{ config('app.name', 'GFID') }}
        </a>

        {{-- RIGHT: mobile cluster (theme + hamburger) + desktop nav --}}
        <div class="d-flex align-items-center gap-2">

            {{-- THEME TOGGLE (1 tombol untuk mobile & desktop) --}}
            <button type="button" class="theme-toggle-btn" id="themeToggleBtn">
                <span class="icon" id="themeToggleIcon">🌙</span>
                <span class="label muted" id="themeToggleLabel">Mode Gelap</span>
            </button>

            {{-- HAMBURGER: hanya untuk buka mobile sidebar, bukan collapse --}}
            <button type="button" class="mobile-menu-btn d-md-none" id="mobileSidebarToggle"
                aria-label="Toggle sidebar">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round"
                    stroke-linejoin="round">
                    <path d="M4 6h16" />
                    <path d="M4 12h16" />
                    <path d="M4 18h16" />
                </svg>
            </button>

            {{-- DESKTOP NAVIGATION --}}
            <div class="desktop-nav d-none d-md-flex">


                </ul>

                {{-- RIGHT: auth --}}
                <ul class="navbar-nav align-items-center">
                    @guest
                        @if (Route::has('login'))
                            <li class="nav-item">
                                <a class="btn btn-sm btn-outline-primary" href="{{ route('login') }}">
                                    Login
                                </a>
                            </li>
                        @endif
                    @else
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown"
                                aria-expanded="false">
                                {{ Auth::user()->name ?? Auth::user()->email }}
                            </a>
                            <div class="dropdown-menu dropdown-menu-end">
                                <form action="{{ route('logout') }}" method="POST">
                                    @csrf
                                    <button type="submit" class="dropdown-item">
                                        Logout
                                    </button>
                                </form>
                            </div>
                        </li>
                    @endguest
                </ul>
            </div>
        </div>
    </div>
</nav>
