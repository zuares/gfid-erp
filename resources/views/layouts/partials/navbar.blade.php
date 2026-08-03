{{-- resources/views/layouts/partials/navbar.blade.php --}}
@php
use Illuminate\Support\Facades\Cache;

$navUser     = auth()->user();
$navIsOwner  = $navUser && method_exists($navUser, 'isOwner') && $navUser->isOwner();
$navRole     = strtolower((string) ($navUser->role ?? ''));
$navIsAdmin  = $navRole === 'admin';
$navShowBell = $navIsOwner || $navIsAdmin;

$navPendingCount = 0;
$navPendingItems = [];

if ($navShowBell) {
    $navCacheKey = 'navbar_notif_owner_v2_' . (int) $navUser->id;
    $navPending  = Cache::remember($navCacheKey, now()->addSeconds(20), function () {
        $poDrafts = \App\Models\PurchaseOrder::where('status', 'draft')
            ->where('grand_total', '>', 0)
            ->orderByDesc('id')
            ->limit(10)
            ->get(['id', 'code', 'grand_total']);

        $grnDrafts = \App\Models\PurchaseReceipt::where('status', 'draft')
            ->orderByDesc('id')
            ->limit(10)
            ->get(['id', 'code']);

        // ── CRM Storefront notifications ──────────────────────────────────────
        $crmAgingCount = 0;
        $crmNewOrders  = 0;
        $crmProspects  = 0;

        if (class_exists(\App\Models\StorefrontOrder::class)) {
            // Order pending > 24 jam (aging)
            $crmAgingCount = \App\Models\StorefrontOrder::where('status', 'pending')
                ->where('created_at', '<=', now()->subHours(24))
                ->count();

            // Order masuk hari ini (baru)
            $crmNewOrders = \App\Models\StorefrontOrder::whereDate('created_at', today())
                ->where('status', 'pending')
                ->count();
        }

        if (class_exists(\App\Models\StorefrontEvent::class)) {
            // Prospects baru hari ini (add_to_cart tanpa order)
            $orderedToday = \App\Models\StorefrontOrder::whereDate('created_at', today())
                ->pluck('visitor_token')->filter()->unique();
            $crmProspects = \App\Models\StorefrontEvent::where('event_type', 'add_to_cart')
                ->whereDate('created_at', today())
                ->whereNotIn('visitor_token', $orderedToday)
                ->distinct('visitor_token')
                ->count('visitor_token');
        }

        return [
            'po_drafts'      => $poDrafts,
            'grn_drafts'     => $grnDrafts,
            'crm_aging'      => $crmAgingCount,
            'crm_new_orders' => $crmNewOrders,
            'crm_prospects'  => $crmProspects,
        ];
    });

    // PO & GRN hanya untuk owner
    if ($navIsOwner) {
        foreach (($navPending['po_drafts'] ?? collect()) as $po) {
            $navPendingItems[] = [
                'type'  => 'po',
                'label' => $po->code ?? 'PO #' . $po->id,
                'sub'   => 'Menunggu approval',
                'url'   => route('purchasing.purchase_orders.show', $po->id),
                'icon'  => '🧾',
            ];
        }
        foreach (($navPending['grn_drafts'] ?? collect()) as $grn) {
            $navPendingItems[] = [
                'type'  => 'grn',
                'label' => $grn->code ?? 'GRN #' . $grn->id,
                'sub'   => 'Belum di-post',
                'url'   => route('purchasing.purchase_receipts.show', $grn->id),
                'icon'  => '📦',
            ];
        }
    }

    // ── CRM items ─────────────────────────────────────────────────────────────
    if (($navPending['crm_aging'] ?? 0) > 0) {
        $navPendingItems[] = [
            'type'  => 'crm_aging',
            'label' => $navPending['crm_aging'] . ' order pending >24 jam',
            'sub'   => 'Segera konfirmasi ke customer',
            'url'   => route('admin.crm.orders'),
            'icon'  => '⏰',
        ];
    }
    if (($navPending['crm_new_orders'] ?? 0) > 0) {
        $navPendingItems[] = [
            'type'  => 'crm_new_orders',
            'label' => $navPending['crm_new_orders'] . ' order baru hari ini',
            'sub'   => 'Menunggu konfirmasi pembayaran',
            'url'   => route('admin.crm.orders'),
            'icon'  => '🛒',
        ];
    }
    if (($navPending['crm_prospects'] ?? 0) > 0) {
        $navPendingItems[] = [
            'type'  => 'crm_prospects',
            'label' => $navPending['crm_prospects'] . ' prospect baru hari ini',
            'sub'   => 'Add to cart tapi belum checkout',
            'url'   => route('admin.crm.prospects'),
            'icon'  => '👤',
        ];
    }

    $navPendingCount = count($navPendingItems);
}
@endphp

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

        background: var(--card);
        border-bottom: 1px solid var(--line);

        backdrop-filter: none;
        -webkit-backdrop-filter: none;

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
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 11px;
        font-weight: 900;
        letter-spacing: .15em;
        text-transform: uppercase;
        color: var(--text) !important;
        padding: 0;
        margin: 0;
        text-decoration: none;
    }
    .app-navbar .navbar-brand img {
        width: 26px;
        height: 26px;
        object-fit: contain;
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

    /* MOBILE LOGOUT BUTTON */
    .mobile-logout-btn {
        border: 0;
        background: transparent;
        padding: .2rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: #ef4444;
        /* merah nyaman */
        cursor: pointer;
    }

    .mobile-logout-btn svg {
        width: 20px;
        height: 20px;
        stroke-width: 2.1;
        stroke: currentColor;
        fill: none;
    }

    .mobile-logout-btn:active {
        transform: translateY(1px) scale(.96);
        opacity: .9;
    }

    @media (min-width: 768px) {
        .mobile-logout-btn {
            display: none;
        }
    }

    /* ============================
       NOTIFICATION BELL
    ============================ */
    .notif-bell-btn {
        position: relative;
        border: 0;
        background: transparent;
        padding: .22rem .4rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: var(--muted);
        cursor: pointer;
        border-radius: 8px;
        transition: background .15s;
    }
    .notif-bell-btn:hover {
        background: color-mix(in srgb, var(--accent-soft) 60%, transparent 40%);
        color: var(--accent);
    }
    .notif-bell-btn svg {
        width: 20px;
        height: 20px;
        stroke-width: 2;
        stroke: currentColor;
        fill: none;
    }
    .notif-badge {
        position: absolute;
        top: 1px;
        right: 1px;
        min-width: 16px;
        height: 16px;
        padding: 0 4px;
        border-radius: 999px;
        background: #ef4444;
        color: #fff;
        font-size: 10px;
        font-weight: 700;
        line-height: 16px;
        text-align: center;
        pointer-events: none;
    }
    .notif-dropdown {
        position: absolute;
        top: calc(100% + 6px);
        right: 0;
        min-width: 260px;
        background: var(--card);
        border: 1px solid var(--line);
        border-radius: 10px;
        box-shadow: 0 8px 28px rgba(0,0,0,.18);
        z-index: 2000;
        overflow: hidden;
        display: none;
    }
    .notif-dropdown.open { display: block; }
    .notif-dropdown-header {
        padding: .55rem .85rem;
        font-size: .78rem;
        font-weight: 600;
        color: var(--muted);
        border-bottom: 1px solid var(--line);
        text-transform: uppercase;
        letter-spacing: .04em;
    }
    .notif-item {
        display: flex;
        align-items: center;
        gap: .6rem;
        padding: .6rem .85rem;
        text-decoration: none;
        color: var(--text);
        font-size: .875rem;
        border-bottom: 1px solid var(--line);
        transition: background .12s;
    }
    .notif-item:last-child { border-bottom: 0; }
    .notif-item:hover { background: color-mix(in srgb, var(--accent-soft) 50%, var(--card) 50%); }
    .notif-item-icon { font-size: 1.1rem; flex-shrink: 0; }
    .notif-item-label { flex: 1; }
    .notif-item-count {
        background: #ef4444;
        color: #fff;
        font-size: .72rem;
        font-weight: 700;
        border-radius: 999px;
        padding: 1px 7px;
        flex-shrink: 0;
    }
    .notif-empty {
        padding: .8rem .85rem;
        font-size: .85rem;
        color: var(--muted);
        text-align: center;
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
            <img src="{{ asset('images/logo-mark.svg') }}" alt="{{ config('app.name', 'Greatfit') }}">
            <span>{{ config('app.name', 'Greatfit') }}</span>
        </a>

        {{-- RIGHT: mobile cluster (theme + logout + hamburger) + desktop nav --}}
        <div class="d-flex align-items-center gap-2">

            {{-- NOTIFICATION BELL (owner + admin) --}}
            @if ($navShowBell)
            <div style="position:relative;" id="notifWrapper">
                <button type="button" class="notif-bell-btn" id="notifBellBtn" aria-label="Notifikasi">
                    <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                        <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
                    </svg>
                    @if ($navPendingCount > 0)
                        <span class="notif-badge">{{ $navPendingCount > 99 ? '99+' : $navPendingCount }}</span>
                    @endif
                </button>

                <div class="notif-dropdown" id="notifDropdown">
                    <div class="notif-dropdown-header">
                        Perlu Tindakan
                        @if ($navPendingCount > 0)
                            <span style="float:right;background:#ef4444;color:#fff;border-radius:999px;padding:0 7px;font-size:10px;font-weight:700;line-height:16px;">{{ $navPendingCount }}</span>
                        @endif
                    </div>
                    @if (count($navPendingItems) > 0)
                        @foreach ($navPendingItems as $item)
                            <a href="{{ $item['url'] }}" class="notif-item">
                                <span class="notif-item-icon">{{ $item['icon'] }}</span>
                                <span class="notif-item-label">
                                    <span style="display:block;font-weight:600;font-size:.83rem;">{{ $item['label'] }}</span>
                                    <span style="display:block;font-size:.75rem;color:var(--muted);">{{ $item['sub'] }}</span>
                                </span>
                                <svg style="width:14px;height:14px;stroke:var(--muted);fill:none;stroke-width:2;flex-shrink:0;" viewBox="0 0 24 24"><path d="M9 18l6-6-6-6"/></svg>
                            </a>
                        @endforeach
                    @else
                        <div class="notif-empty">Semua beres ✓</div>
                    @endif
                </div>
            </div>
            @endif

            {{-- THEME TOGGLE --}}
            <button type="button" class="theme-toggle-btn" id="themeToggleBtn">
                <span class="icon" id="themeToggleIcon">🌙</span>
                <span class="label muted" id="themeToggleLabel">Mode Gelap</span>
            </button>

            @auth
                {{-- MOBILE LOGOUT ICON (hanya mobile) --}}
                <button type="button" class="mobile-logout-btn d-md-none" id="mobileLogoutBtn" aria-label="Logout">
                    <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path d="M9 5h-2.5A2.5 2.5 0 0 0 4 7.5v9A2.5 2.5 0 0 0 6.5 19H9" />
                        <path d="M15 16l3-4-3-4" />
                        <path d="M18 12H10" />
                    </svg>
                </button>

                {{-- form logout khusus mobile (hidden) --}}
                <form id="mobileLogoutForm" action="{{ route('logout') }}" method="POST" class="d-none">
                    @csrf
                </form>
            @endauth

            {{-- HAMBURGER: buka mobile sidebar --}}
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
                {{-- (kalau nanti mau tambah link menu lain bisa di sini) --}}

                {{-- RIGHT: auth --}}
                <ul class="navbar-nav align-items-center ms-2">
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

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Mobile logout
        const mobileLogoutBtn = document.getElementById('mobileLogoutBtn');
        const mobileLogoutForm = document.getElementById('mobileLogoutForm');
        if (mobileLogoutBtn && mobileLogoutForm) {
            mobileLogoutBtn.addEventListener('click', function() {
                if (confirm('Yakin mau logout?')) {
                    mobileLogoutForm.submit();
                }
            });
        }

        // Notification bell toggle
        const notifBellBtn  = document.getElementById('notifBellBtn');
        const notifDropdown = document.getElementById('notifDropdown');
        if (notifBellBtn && notifDropdown) {
            notifBellBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                notifDropdown.classList.toggle('open');
            });
            // Tutup kalau klik di luar
            document.addEventListener('click', function(e) {
                if (!notifDropdown.contains(e.target) && e.target !== notifBellBtn) {
                    notifDropdown.classList.remove('open');
                }
            });
            // Tutup kalau klik item di dalam (navigasi)
            notifDropdown.querySelectorAll('.notif-item').forEach(function(a) {
                a.addEventListener('click', function() {
                    notifDropdown.classList.remove('open');
                });
            });
        }
    });
</script>
