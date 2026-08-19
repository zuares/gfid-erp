{{-- resources/views/layouts/app.blade.php --}}
<!DOCTYPE html>
<html lang="id" data-theme="light">
<head>
  <meta charset="utf-8">

  {{-- viewport: lock zoom/pinch for mobile app workflow --}}
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, minimum-scale=1, user-scalable=no, viewport-fit=cover">

  <meta name="csrf-token" content="{{ csrf_token() }}">

  <title>@yield('title', config('app.name', 'GFID'))</title>

  {{-- Bootstrap 5 --}}
  <link rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH"
        crossorigin="anonymous">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

  {{-- =========================
    ✅ FLATPICKR GLOBAL (CDN)
  ========================= --}}
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

  {{-- =========================
    GLOBAL RESET + MOBILE UX FIX
  ========================= --}}
  <style>
    html{
      box-sizing:border-box;
      -webkit-text-size-adjust:100%;
      height:100%;
      touch-action: pan-x pan-y;
    }
    *,*::before,*::after{ box-sizing:inherit; }

    body{
      overflow-y:auto !important;
      font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
      font-size:14px;
      line-height:1.4;
      margin:0;
      height:100%;
      touch-action: pan-x pan-y;
    }

    /* cegah auto-zoom iOS saat focus input */
    input,select,textarea{ font-size:16px; }

    /* Error modal yang ringkas dan konsisten di tengah layar. */
    .swal2-popup.gf-minimal-error-alert{
      border-radius:12px !important;
      box-shadow:0 18px 48px rgba(15,23,42,.18) !important;
    }
    .swal2-popup.gf-minimal-error-alert .swal2-title{
      margin:.25rem 0 .35rem !important;
      color:#111827 !important;
      font-size:1rem !important;
      font-weight:800 !important;
    }
    .swal2-popup.gf-minimal-error-alert .swal2-html-container{
      margin:.35rem 0 .8rem !important;
      color:#64748b !important;
      font-size:.84rem !important;
      line-height:1.45 !important;
    }
    .swal2-popup.gf-minimal-error-alert .gf-minimal-error-alert-button{
      min-width:72px;
      border-radius:7px !important;
      padding:.45rem 1rem !important;
      font-size:.8rem !important;
      font-weight:750 !important;
    }
    .swal2-popup .gf-confirm-summary{
      width:100%;
      margin:.25rem 0 .65rem;
      border-collapse:collapse;
      border:1px solid rgba(148,163,184,.22);
      border-radius:8px;
      overflow:hidden;
      font-size:.8rem;
    }
    .swal2-popup .gf-confirm-summary th,
    .swal2-popup .gf-confirm-summary td{
      padding:.45rem .6rem;
      border-bottom:1px solid rgba(148,163,184,.16);
    }
    .swal2-popup .gf-confirm-summary tr:last-child th,
    .swal2-popup .gf-confirm-summary tr:last-child td{ border-bottom:0; }
    .swal2-popup .gf-confirm-summary th{ text-align:left; color:#64748b; font-weight:700; }
    .swal2-popup .gf-confirm-summary td{ text-align:right; color:#111827; font-weight:900; }
    .swal2-popup .gf-confirm-summary-note{ color:#64748b; font-size:.72rem; }
    .gf-scan-sound-toggle{ display:inline-flex; align-items:center; justify-content:center; gap:.3rem; min-height:32px; border:1px solid rgba(148,163,184,.3); border-radius:7px; padding:.28rem .55rem; background:transparent; color:#475569; font-size:.7rem; font-weight:750; cursor:pointer; }
    .gf-scan-sound-toggle.is-off{ color:#94a3b8; border-color:rgba(148,163,184,.22); }

    /**
     * ✅ ANDROID FIX:
     * Jangan pakai 100vh/min-vh-100 langsung (sering berubah saat keyboard).
     * Pakai baseline height dari JS -> --app-vh (px)
     */
    :root{
      --app-vh: 100vh; /* fallback */
      --vv-kbd: 0px;   /* estimasi tinggi keyboard untuk dorong bottom nav */
    }

    #app.app-root{ min-height: var(--app-vh); }

    /* ✅ Mobile: anti horizontal scroll + ganjel konten di atas bottom-nav */
    @media (max-width: 767.98px){
      html,body{ overflow-x:hidden; }

      .app-shell,
      .app-main,
      .app-main .page-wrap{
        overflow-x:hidden;
      }

      .app-main .page-wrap{
        padding-bottom: 9rem; /* > tinggi bottom nav */
      }
    }

    /* =========================
      ✅ Flatpickr Global Safety
    ========================= */
    .flatpickr-calendar{
      z-index: 25000 !important; /* di atas modal/sidebar */
      border-radius: 14px !important;
      border: 1px solid rgba(148,163,184,.22) !important;
      box-shadow: 0 18px 48px rgba(15,23,42,.22) !important;
      overflow: hidden;
      font-family: inherit;
    }
    .flatpickr-months .flatpickr-month,
    .flatpickr-weekdays{
      background: transparent;
    }
    .flatpickr-current-month .flatpickr-monthDropdown-months,
    .flatpickr-current-month input.cur-year{
      font-weight: 800;
    }
    .flatpickr-day{
      border-radius: 9px;
    }
    .flatpickr-day.today{
      border-color: rgba(59,130,246,.55);
    }
    .flatpickr-day.selected,
    .flatpickr-day.startRange,
    .flatpickr-day.endRange{
      background: #2563eb;
      border-color: #2563eb;
      color: #fff;
    }
    input.gf-date-input,
    input.flatpickr-input{
      background-color: var(--card) !important;
      cursor: pointer;
    }
    input.gf-date-input[readonly],
    input.flatpickr-input[readonly]{
      background-color: var(--card) !important;
    }
    body[data-theme="dark"] .flatpickr-calendar{
      box-shadow: 0 18px 48px rgba(0,0,0,.55);
      border-color: rgba(148,163,184,.12);
    }
  </style>

  {{-- THEME + GLOBAL STYLES --}}
  @include('layouts.partials.styles')
  <link rel="stylesheet" href="{{ asset('css/light.css') }}">
  <link rel="stylesheet" href="{{ asset('css/dark.css') }}">

  @stack('head')

<style>
    /* OWNER FLOATING TOOLS */
    .gf-owner-floating-tools {
        position: fixed;
        right: 18px;
        top: calc(66px + env(safe-area-inset-top, 0px));
        z-index: 1040;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transform: scale(.88);
        transform-origin: top right;
        opacity: .72;
        transition: opacity .16s ease, transform .16s ease;
    }

    .gf-owner-mode-trigger,
    .gf-owner-worklog-nav {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        min-height: 36px;
        border-radius: 999px;
        padding: 0 12px;
        color: #0f172a;
        background: rgba(255,255,255,.92);
        border: 1px solid rgba(15,23,42,.10);
        box-shadow: 0 14px 34px rgba(15,23,42,.14);
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
        font-size: 11px;
        font-weight: 950;
        text-decoration: none;
        letter-spacing: -.01em;
        transition: .16s ease;
    }

    .gf-owner-mode-trigger:hover,
    .gf-owner-worklog-nav:hover {
        color: #0f172a;
        transform: translateY(-1px);
        box-shadow: 0 18px 40px rgba(15,23,42,.18);
    }

    .gf-owner-floating-tools:focus-within,
    .gf-owner-floating-tools:hover {
        opacity: 1;
        transform: scale(1);
    }

    .gf-owner-worklog-nav.is-active {
        color: #075985;
        background: #e0f2fe;
        border-color: #bae6fd;
    }

    .gf-owner-worklog-dot {
        width: 8px;
        height: 8px;
        border-radius: 999px;
        background: #38bdf8;
        box-shadow: 0 0 0 4px rgba(56,189,248,.16);
        flex: 0 0 auto;
    }

    .gf-owner-worklog-nav.is-active .gf-owner-worklog-dot {
        background: #22c55e;
        box-shadow: 0 0 0 4px rgba(34,197,94,.16);
    }

    .gf-owner-mode-trigger.is-dev {
        color: #075985;
        background: #e0f2fe;
        border-color: #bae6fd;
    }

    .gf-owner-mode-trigger.is-ops {
        color: #166534;
        background: #dcfce7;
        border-color: #bbf7d0;
    }

    .gf-owner-mode-trigger.is-unknown {
        color: #92400e;
        background: #fef3c7;
        border-color: #fde68a;
    }

    .gf-owner-mode-menu {
        min-width: 245px;
        border-radius: 14px;
        border: 1px solid rgba(15,23,42,.10);
        box-shadow: 0 18px 44px rgba(15,23,42,.18);
        padding: .45rem;
        font-size: 12px;
    }

    .gf-owner-mode-menu .dropdown-header {
        font-size: 11px;
        font-weight: 900;
        color: #64748b;
        padding: .35rem .55rem;
    }

    .gf-owner-mode-menu .dropdown-item {
        border-radius: 10px;
        font-weight: 800;
        padding: .52rem .6rem;
    }

    .gf-owner-mode-path {
        font-size: 10px;
        color: #94a3b8;
        padding: .2rem .55rem .35rem;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        max-width: 232px;
    }

    @media (max-width: 768px) {
        .gf-owner-floating-tools {
            left: auto;
            right: 8px;
            top: calc(62px + env(safe-area-inset-top, 0px));
            bottom: auto;
            width: auto;
            justify-content: flex-start;
            gap: 5px;
            transform: scale(.78);
            transform-origin: top right;
            opacity: .55;
        }

        .gf-owner-mode-wrap,
        .gf-owner-worklog-nav {
            flex: 0 0 auto;
        }

        .gf-owner-mode-trigger,
        .gf-owner-worklog-nav {
            width: auto;
            min-width: 0;
            min-height: 32px;
            height: 32px;
            gap: 5px;
            padding: 0 9px;
            font-size: 10px;
            box-shadow: 0 8px 20px rgba(15,23,42,.10);
            border-color: rgba(15,23,42,.08);
        }

        .gf-owner-worklog-nav span {
            display: none;
        }

        .gf-owner-worklog-nav::after {
            content: "Log";
        }

        .gf-owner-worklog-dot {
            width: 6px;
            height: 6px;
            box-shadow: 0 0 0 3px rgba(56,189,248,.13);
        }

        .gf-owner-mode-menu {
            min-width: 210px;
            max-width: calc(100vw - 20px);
            max-height: 55vh;
            overflow-y: auto;
            font-size: 11px;
            border-radius: 12px;
        }

        .gf-owner-mode-path {
            max-width: 194px;
        }

    }
</style>

</head>

<body>
  <div id="app" class="app-root d-flex flex-column">

    {{-- NAVBAR --}}
    @include('layouts.partials.navbar')

    {{-- MOBILE SIDEBAR (drawer) --}}
    @include('layouts.partials.mobile-sidebar')

    {{-- APP SHELL: sidebar + main --}}
    <div class="app-shell">

      {{-- SIDEBAR DESKTOP --}}
      @include('layouts.partials.sidebar')

      {{-- MAIN CONTENT --}}
      <main class="app-main py-3">
        <div class="page-wrap">

          @php
            $errorBag = $errors ?? null;
            $hasValidationErrors = $errorBag instanceof \Illuminate\Support\ViewErrorBag ? $errorBag->any() : false;
          @endphp

          @if ($hasValidationErrors)
            <div class="alert alert-danger mb-3">
              <strong>Terjadi error:</strong>
              <ul class="mb-0">
                @foreach ($errorBag->all() as $error)
                  <li>{{ $error }}</li>
                @endforeach
              </ul>
            </div>
          @endif

          @yield('content')
        </div>
      </main>
    </div>

    {{-- BOTTOM NAV MOBILE --}}
    @auth
      <x-mobile-bottom-nav />
    @endauth
  </div>

  {{-- Bootstrap JS --}}
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
          integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
          crossorigin="anonymous"></script>

  {{-- ✅ Flatpickr GLOBAL --}}
  <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
  <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/id.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

  {{-- ✅ Realtime: Laravel Echo + Reverb/Pusher (WebSocket) --}}
  @if (in_array(config('broadcasting.default'), ['reverb', 'pusher']))
    <script src="https://cdn.jsdelivr.net/npm/pusher-js@8.4.0/dist/web/pusher.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/laravel-echo@2.3.7/dist/echo.iife.js"></script>
    <script>
      (function () {
        try {
          var EchoClass = typeof Echo !== 'undefined' ? (Echo.default || Echo) : window.EchoClass;
          if (!EchoClass) return;

          @if(config('broadcasting.default') === 'pusher')
              window.Echo = new EchoClass({
                  broadcaster: 'pusher',
                  key: @json(config('broadcasting.connections.pusher.key')),
                  cluster: @json(config('broadcasting.connections.pusher.options.cluster')),
                  forceTLS: true
              });
          @elseif(config('broadcasting.default') === 'reverb')
              var host = @json(config('broadcasting.connections.reverb.options.host')) || window.location.hostname;
              var port = Number(@json(config('broadcasting.connections.reverb.options.port', 443)));
              var scheme = @json(config('broadcasting.connections.reverb.options.scheme', 'https'));
              window.Echo = new EchoClass({
                broadcaster: 'reverb',
                key: @json(config('broadcasting.connections.reverb.key')),
                wsHost: host,
                wsPort: port,
                wssPort: port,
                forceTLS: scheme === 'https',
                enabledTransports: ['ws', 'wss'],
              });
          @endif
        } catch (e) {
          console.warn('Echo init failed:', e);
        }

        // Cleanly disconnect Pusher on page unload to prevent "WebSocket is closed before connection is established" console error
        window.addEventListener('beforeunload', function () {
            if (window.Echo && window.Echo.connector && window.Echo.connector.pusher) {
                window.Echo.connector.pusher.disconnect();
            }
        });
      })();
    </script>
  @endif

  {{-- ✅ Badge unread chat di sidebar (realtime + refresh berkala) --}}
  <script>
    (function () {
      var badges = document.querySelectorAll('.sidebarChatBadge');
      
      var previousUnread = -1;

      function playNotificationSound() {
          try {
              var snd = new Audio('data:audio/wav;base64,UklGRnoGAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQoGAACBhYqPb3F0eX2AhIqQc3R4en6BhYuQcnV5e3+ChoyRbnN4e36ChoyRcXR6fIGDh42ScHV6e4CCho2Tb3V7fIGDh4+TbnV6fIGDiI+UbnV6e4KDiY+VbXZ7fIOEiZCWbnZ7fIOEipGWbnZ8fISFipKWbXV8fYWFi5OWbHV8fYWFjJSWbHV8foWGjJWWa3V8foWGjZaWa3R8f4aHjpWYa3R9f4aHjZeXanR9f4eIjpeXanR9f4eIjpeYanN9gIiIjpiYanN+gImJj5iYanN+gYmJj5mYaXN+gYqKkJiZaXJ/gYqKkJmZaXJ/gomKkJqZaHJ/gomKkZqZZnKAgoqLkpqaZnKAgoqLk5qaZXKBg4uMk5qbZXKBg4uMk5qbZHKBg4uNk5ucZHKCg4yOk5ucY3KDhI2Pk5ycYnKDhI2Qk5ydYnKEhY+Qk5ydYXOFhpCRkpydYXOGh5GSk52eYHSGh5GSk52eX3SIiJKSk52eX3SJiZOSk56eX3SJipOSk56eXnaKipSTk56eXnaLi5WTk5+fXnaMjJWTk5+fXnaMjJaTk5+fXXaNjpWTk5+fXXaOj5aTk5+fXXaPj5aTkp+fXHaQkJWUkp+gXHaQkJaUkp+gW3aRkZaVkaCgW3aSkpeVkaCgWnaTk5iWkaCgWnaUlJiXkaGgWnaVlZiXkaGhw==');
              snd.play().catch(function(){}); // Catch autoplay policy block
          } catch(err) {}
      }

      function refreshChatBadge(playSound = true) {
        fetch('/api/marketplace/chat/unread-count', { headers: { 'Accept': 'application/json' } })
          .then(function (r) { return r.ok ? r.json() : null; })
          .then(function (d) {
            if (!d) return;
            var n = d.unread || 0;
            
            // Putar suara jika jumlah pesan baru bertambah, dan bukan saat load pertama kali
            if (playSound && previousUnread !== -1 && n > previousUnread) {
                playNotificationSound();
            }
            previousUnread = n;

            var txt = n > 99 ? '99+' : n;
            var disp = n > 0 ? 'inline-block' : 'none';
            badges.forEach(function(b) {
                b.textContent = txt;
                b.style.display = disp;
            });
          })
          .catch(function () {});
      }
      
      window.refreshChatBadge = refreshChatBadge;

      document.addEventListener('DOMContentLoaded', function() { refreshChatBadge(false); });
      // Auto-refresh setiap 20 detik (selalu bisa play sound jika memang ada yang unread baru)
      setInterval(function() { refreshChatBadge(true); }, 20000);


      // Realtime: pesan baru masuk → update badge seketika
      if (window.Echo) {
        try {
          window.Echo.channel('marketplace')
            .listen('ChatMessageReceived', function(e) {
                // Jika user sedang melihat chat yang sama, tidak perlu bunyi Ting! global
                if (window.activeConversationId && window.activeConversationId == e.conversation_id) {
                    refreshChatBadge(false);
                } else {
                    refreshChatBadge(true);
                }
            });
        } catch (e) {}
      }
    })();
  </script>

  @if (session('success'))
    <script>
      document.addEventListener('DOMContentLoaded', function() {
        if (!window.Swal) return;

        window.Swal.fire({
          toast: true,
          position: 'top-end',
          icon: 'success',
          title: @json(session('success')),
          showConfirmButton: false,
          timer: 2600,
          timerProgressBar: true
        });
      });
    </script>
  @endif

  @if (session('error'))
    <script>
      document.addEventListener('DOMContentLoaded', function() {
        if (!window.Swal) return;

        window.Swal.fire({
          icon: 'error',
          title: 'Gagal',
          text: @json(session('error')),
          confirmButtonText: 'OK',
          confirmButtonColor: '#334155',
          width: 'min(92vw, 360px)',
          padding: '1rem',
          customClass: {
            popup: 'gf-minimal-error-alert',
            confirmButton: 'gf-minimal-error-alert-button'
          }
        });
      });
    </script>
  @endif

  @if (request()->routeIs('sales.shipments.*') && session('status') === 'error' && session('message') && !session('stock_insufficient'))
    <script>
      document.addEventListener('DOMContentLoaded', function() {
        if (!window.Swal) return;

        window.Swal.fire({
          icon: 'error',
          title: 'Gagal',
          text: @json(session('message')),
          confirmButtonText: 'OK',
          confirmButtonColor: '#334155',
          width: 'min(92vw, 360px)',
          padding: '1rem',
          customClass: {
            popup: 'gf-minimal-error-alert',
            confirmButton: 'gf-minimal-error-alert-button'
          }
        });
      });
    </script>
  @endif

  {{-- ⚠️ Peringatan jurnal gagal — sengaja modal (bukan toast) agar tidak terlewat --}}
  @if (session('journal_warning'))
    <script>
      document.addEventListener('DOMContentLoaded', function() {
        if (!window.Swal) return;
        window.Swal.fire({
          icon: 'warning',
          title: 'Jurnal Akuntansi Gagal',
          text: @json(session('journal_warning')),
          confirmButtonText: 'Mengerti',
          confirmButtonColor: '#b45309'
        });
      });
    </script>
  @endif

  {{-- THEME TOGGLER SCRIPT --}}
  @include('layouts.partials.theme-script')

  {{-- =========================
    ✅ GLOBAL HELPERS (bisa dipakai di semua halaman)
    - window.GFID.initDate(selector, options)
    - window.GFID.initDateRange(selector, options)
    - window.GFID.initGreatfitUi(root)
  ========================= --}}
  @php
    $gfidScanSoundMap = json_decode((string) \App\Models\SystemSetting::get(\App\Models\SystemSetting::KEY_SHIPMENT_SCAN_SOUND_MAP, '{}'), true);
    $gfidScanSoundMap = is_array($gfidScanSoundMap) ? $gfidScanSoundMap : [];
    $gfidScanSoundRingtoneUrls = [];
    $gfidRingtoneIds = collect($gfidScanSoundMap)
      ->filter(fn ($value) => is_string($value) && str_starts_with($value, 'ringtone:'))
      ->map(fn ($value) => (int) substr($value, 9))
      ->filter()
      ->unique()
      ->values();
    if ($gfidRingtoneIds->isNotEmpty() && \Illuminate\Support\Facades\Schema::hasTable('shipment_scan_ringtones')) {
      $gfidScanSoundRingtoneUrls = \App\Models\ShipmentScanRingtone::query()
        ->whereIn('id', $gfidRingtoneIds)
        ->get(['id', 'path'])
        ->mapWithKeys(fn ($ringtone) => [
          'ringtone:' . $ringtone->id => \Illuminate\Support\Facades\Storage::disk('public')->url($ringtone->path),
        ])
        ->all();
    }
  @endphp
  <script>
    (function(){
      window.GFID = window.GFID || {};

      function pickEl(selectorOrEl){
        if (!selectorOrEl) return null;
        if (typeof selectorOrEl === 'string') return document.querySelector(selectorOrEl);
        return selectorOrEl;
      }

      function baseOptions(){
        return {
          dateFormat: 'Y-m-d',
          altInput: true,
          altFormat: 'j F Y',
          allowInput: true,
          disableMobile: true,
          monthSelectorType: 'static',
          nextArrow: '&rsaquo;',
          prevArrow: '&lsaquo;',
          locale: (window.flatpickr && window.flatpickr.l10ns && window.flatpickr.l10ns.id) ? 'id' : 'default',
          onReady: function(selectedDates, dateStr, instance){
            instance.input.classList.add('gf-date-input');
            instance.calendarContainer.classList.add('gf-date-calendar');
          }
        };
      }

      window.GFID.initDate = function(selectorOrEl, options = {}){
        const el = pickEl(selectorOrEl);
        if (!el || !window.flatpickr) return null;
        if (el._flatpickr) return el._flatpickr;

        return window.flatpickr(el, Object.assign({}, baseOptions(), options));
      };

      window.GFID.initDateRange = function(selectorOrEl, options = {}){
        const el = pickEl(selectorOrEl);
        if (!el || !window.flatpickr) return null;
        if (el._flatpickr) return el._flatpickr;

        return window.flatpickr(el, Object.assign({}, baseOptions(), {
          mode: 'range'
        }, options));
      };

      window.GFID.toast = function(message, options = {}){
        if (!message || !window.Swal) return null;
        return Swal.fire(Object.assign({
          toast: true,
          position: 'top-end',
          icon: 'success',
          title: message,
          showConfirmButton: false,
          timer: 2600,
          timerProgressBar: true
        }, options));
      };

      window.GFID.errorAlert = function(message, options = {}){
        if (!window.Swal) return null;
        return Swal.fire(Object.assign({
          icon: 'error',
          title: 'Gagal',
          text: message || 'Terjadi kesalahan.',
          confirmButtonText: 'OK',
          confirmButtonColor: '#334155',
          width: 'min(92vw, 360px)',
          padding: '1rem',
          customClass: {
            popup: 'gf-minimal-error-alert',
            confirmButton: 'gf-minimal-error-alert-button'
          }
        }, options));
      };

      window.GFID.scanSoundDefault = @json(
        (string) \App\Models\SystemSetting::get(\App\Models\SystemSetting::KEY_SHIPMENT_SCAN_SOUND, '1') !== '0'
      );
      window.GFID.scanSoundEnabled = window.GFID.scanSoundDefault;
      window.GFID.scanSoundMap = @json($gfidScanSoundMap);
      window.GFID.scanSoundRingtoneUrls = @json($gfidScanSoundRingtoneUrls);
      window.GFID.scanSoundPresets = {
        ok: [{freq:880,duration:.055,gap:0,type:'sine',volume:.25},{freq:1175,duration:.065,gap:.045,type:'sine',volume:.25}],
        item: [{freq:932,duration:.045,gap:0,type:'sine',volume:.25},{freq:1244,duration:.05,gap:.035,type:'sine',volume:.25}],
        order: [{freq:587,duration:.07,gap:0,type:'sine',volume:.25},{freq:784,duration:.08,gap:.045,type:'sine',volume:.25}],
        orderRepeat: [{freq:659,duration:.055,gap:0,type:'sine',volume:.25},{freq:659,duration:.055,gap:.04,type:'sine',volume:.25}],
        next: [{freq:523,duration:.06,gap:0,type:'sine',volume:.22},{freq:698,duration:.06,gap:.035,type:'sine',volume:.22},{freq:880,duration:.075,gap:.035,type:'sine',volume:.22}],
        undo: [{freq:784,duration:.055,gap:0,type:'sine',volume:.25},{freq:523,duration:.075,gap:.04,type:'sine',volume:.25}],
        reset: [{freq:440,duration:.07,gap:0,type:'sine',volume:.25},{freq:330,duration:.07,gap:.05,type:'sine',volume:.25},{freq:220,duration:.09,gap:.05,type:'sine',volume:.25}],
        errorGuard: [{freq:196,duration:.09,gap:0,type:'square',volume:.22},{freq:196,duration:.09,gap:.055,type:'square',volume:.22}],
        errorNoOrder: [{freq:220,duration:.08,gap:0,type:'triangle',volume:.24},{freq:165,duration:.105,gap:.055,type:'triangle',volume:.24}],
        errorNetwork: [{freq:155,duration:.08,gap:0,type:'sawtooth',volume:.22},{freq:120,duration:.08,gap:.05,type:'sawtooth',volume:.22},{freq:100,duration:.11,gap:.05,type:'sawtooth',volume:.22}],
        error: [{freq:180,duration:.09,gap:0,type:'sawtooth',volume:.22},{freq:140,duration:.12,gap:.055,type:'sawtooth',volume:.22}],
        orderReady: [{freq:880,duration:.13,gap:0,type:'square',volume:.28},{freq:1100,duration:.13,gap:.14,type:'square',volume:.28},{freq:1320,duration:.16,gap:.28,type:'square',volume:.28}],
        orderPartial: [{freq:880,duration:.13,gap:0,type:'square',volume:.28},{freq:660,duration:.16,gap:.14,type:'triangle',volume:.28}],
        orderNoMatch: [{freq:660,duration:.18,gap:0,type:'triangle',volume:.25},{freq:500,duration:.18,gap:.19,type:'triangle',volume:.25}],
      };
      window.GFID.isScanSoundEnabled = function(){
        return window.GFID.scanSoundEnabled !== false;
      };
      window.GFID.setScanSoundEnabled = function(enabled){
        window.GFID.scanSoundEnabled = Boolean(enabled);
      };
      window.GFID.playScanSoundPreset = function(presetKey){
        const notes = window.GFID.scanSoundPresets[presetKey];
        if (!Array.isArray(notes)) return false;

        try {
          const Ctx = window.AudioContext || window.webkitAudioContext;
          if (!Ctx) return false;
          const ctx = window.GFID.scanAudioContext || (window.GFID.scanAudioContext = new Ctx());
          const play = () => {
            let cursor = ctx.currentTime;
            notes.forEach(note => {
              cursor += Number(note.gap || 0);
              const osc = ctx.createOscillator();
              const gain = ctx.createGain();
              osc.type = note.type || 'sine';
              osc.frequency.setValueAtTime(Number(note.freq), cursor);
              gain.gain.setValueAtTime(0.0001, cursor);
              gain.gain.exponentialRampToValueAtTime(Number(note.volume || .22), cursor + .01);
              gain.gain.exponentialRampToValueAtTime(.001, cursor + Number(note.duration || .1));
              osc.connect(gain);
              gain.connect(ctx.destination);
              osc.start(cursor);
              osc.stop(cursor + Number(note.duration || .1) + .02);
            });
          };
          if (ctx.state === 'suspended') {
            ctx.resume().then(play).catch(() => {});
          } else {
            play();
          }
          return true;
        } catch (e) {
          return false;
        }
      };
      window.GFID.playScanSound = function(eventKey, fallback){
        if (!window.GFID.isScanSoundEnabled()) return;

        const selected = window.GFID.scanSoundMap[eventKey];
        if (typeof selected === 'string' && selected.startsWith('ringtone:')) {
          const url = window.GFID.scanSoundRingtoneUrls[selected];
          if (url && window.Audio) {
            const audio = new Audio(url);
            audio.preload = 'auto';
            audio.volume = .85;
            const result = audio.play();
            if (result && typeof result.catch === 'function') {
              result.catch(() => typeof fallback === 'function' && fallback());
            }
            return;
          }
        }
        if (typeof selected === 'string' && selected.startsWith('builtin:')) {
          if (window.GFID.playScanSoundPreset(selected.slice(8))) return;
        }
        if (typeof fallback === 'function') fallback();
      };
      window.GFID.bindScanSoundToggle = function(button){
        if (!button) return;

        function render(){
          const enabled = window.GFID.isScanSoundEnabled();
          button.classList.toggle('is-off', !enabled);
          button.setAttribute('aria-pressed', enabled ? 'true' : 'false');
          button.textContent = enabled ? '🔊 Suara ON' : '🔇 Suara OFF';
          button.title = enabled ? 'Matikan suara scan' : 'Nyalakan suara scan';
        }

        render();
        button.addEventListener('click', function(){
          window.GFID.setScanSoundEnabled(!window.GFID.isScanSoundEnabled());
          render();
        });
      };

      function isDateLikeInput(el){
        if (!el || el.tagName !== 'INPUT') return false;
        if (el.dataset.gfDate === 'off') return false;
        if (el.matches('[data-date-range],[data-gf-date-range]')) return false;

        const type = (el.getAttribute('type') || 'text').toLowerCase();
        const name = (el.getAttribute('name') || '').toLowerCase();
        const id = (el.getAttribute('id') || '').toLowerCase();
        const placeholder = (el.getAttribute('placeholder') || '').toLowerCase();
        const marker = [name, id, placeholder].join(' ');

        return el.matches('[data-gf-date],[data-ce-date]')
          || type === 'date'
          || ['date', 'from', 'to', 'date_from', 'date_to', 'period_start', 'period_end', 'ref_date'].includes(name)
          || marker.includes('tanggal');
      }

      function upgradeDateInputs(root){
        const scope = root || document;
        scope.querySelectorAll('input').forEach((el) => {
          if (!isDateLikeInput(el)) return;
          if (el.type === 'date') {
            try { el.type = 'text'; } catch (e) {}
          }
          window.GFID.initDate(el);
        });
      }

      function initConfirmables(root){
        const scope = root || document;

        function confirmBody(el, fallbackText){
          const rawSummary = el.dataset.gfConfirmSummary;
          if (!rawSummary) return { text: fallbackText };

          try {
            const summary = JSON.parse(rawSummary);
            const escapeHtml = (value) => String(value ?? '')
              .replace(/&/g, '&amp;')
              .replace(/</g, '&lt;')
              .replace(/>/g, '&gt;')
              .replace(/"/g, '&quot;')
              .replace(/'/g, '&#039;');
            const rows = [
              ['Order discan', summary.orders ?? 0],
              ['Item / SKU', summary.items ?? 0],
              ['Total qty', summary.qty ?? 0],
            ];

            return {
              html: `<table class="gf-confirm-summary"><tbody>${rows.map(([label, value]) => `
                <tr><th>${escapeHtml(label)}</th><td>${escapeHtml(value)}</td></tr>
              `).join('')}</tbody></table><div class="gf-confirm-summary-note">Periksa kembali sebelum proses dilanjutkan.</div>`
            };
          } catch (error) {
            return { text: fallbackText };
          }
        }

        scope.querySelectorAll('form[data-gf-confirm], form[data-confirm]').forEach((form) => {
          if (form.dataset.gfConfirmBound === '1') return;
          form.dataset.gfConfirmBound = '1';

          form.addEventListener('submit', (event) => {
            if (form.dataset.confirmed === '1' || !window.Swal) return;
            event.preventDefault();

            Swal.fire({
              title: form.dataset.gfConfirmTitle || form.dataset.confirmTitle || 'Lanjutkan?',
              ...confirmBody(form, form.dataset.gfConfirmText || form.dataset.confirmText || 'Pastikan data sudah benar sebelum diproses.'),
              icon: form.dataset.gfConfirmIcon || form.dataset.confirmIcon || 'question',
              showCancelButton: true,
              confirmButtonText: form.dataset.gfConfirmOk || form.dataset.confirmOk || 'Ya, lanjutkan',
              cancelButtonText: form.dataset.gfConfirmCancel || form.dataset.confirmCancel || 'Batal',
            }).then((result) => {
              if (!result.isConfirmed) return;
              form.dataset.confirmed = '1';
              form.submit();
            });
          });
        });

        scope.querySelectorAll('a[data-gf-confirm], button[data-gf-confirm]').forEach((el) => {
          if (el.dataset.gfConfirmBound === '1') return;
          el.dataset.gfConfirmBound = '1';

          el.addEventListener('click', (event) => {
            if (el.dataset.confirmed === '1' || !window.Swal) return;
            event.preventDefault();

            Swal.fire({
              title: el.dataset.gfConfirmTitle || 'Lanjutkan?',
              ...confirmBody(el, el.dataset.gfConfirmText || 'Aksi ini akan diproses.'),
              icon: el.dataset.gfConfirmIcon || 'question',
              showCancelButton: true,
              confirmButtonText: el.dataset.gfConfirmOk || 'Ya, lanjutkan',
              cancelButtonText: el.dataset.gfConfirmCancel || 'Batal',
            }).then((result) => {
              if (!result.isConfirmed) return;
              el.dataset.confirmed = '1';
              if (el.tagName === 'A' && el.href) {
                window.location.href = el.href;
              } else {
                el.click();
              }
            });
          });
        });
      }

      window.GFID.initGreatfitUi = function(root){
        upgradeDateInputs(root || document);
        initConfirmables(root || document);
      };

      document.addEventListener('DOMContentLoaded', function(){
        window.GFID.initGreatfitUi(document);
      });
    })();
  </script>

  {{-- =========================
    ✅ GLOBAL ANDROID KEYBOARD FIX
    - baseline viewport + bottom-nav anti naik
  ========================= --}}
  <script>
  (function(){
    const root = document.documentElement;

    // baseline height (ambil yang terbesar; jangan turun saat keyboard)
    let baselineInnerH = window.innerHeight;
    let baselineVvH = window.visualViewport ? window.visualViewport.height : null;

    function isTextInput(el){
      if (!el) return false;
      const tag = (el.tagName || '').toLowerCase();
      if (tag === 'textarea') return true;
      if (tag !== 'input') return false;
      const type = (el.getAttribute('type') || 'text').toLowerCase();
      return !['checkbox','radio','range','button','submit','reset','file','image','color'].includes(type);
    }

    function isTypingNow(){
      const el = document.activeElement;
      return isTextInput(el) || (el && el.isContentEditable);
    }

    function updateVhAndKeyboard(){
      const typing = isTypingNow();

      // Update baseline hanya saat tidak mengetik
      if (!typing){
        baselineInnerH = Math.max(baselineInnerH, window.innerHeight);
        if (window.visualViewport){
          baselineVvH = Math.max(baselineVvH ?? 0, window.visualViewport.height);
        }
      }

      // 1) app height: pakai baseline (supaya flex container tidak “ketarik” keyboard)
      root.style.setProperty('--app-vh', baselineInnerH + 'px');

      // 2) keyboard height estimate (Android-proof)
      let kbd = 0;
      kbd = Math.max(kbd, baselineInnerH - window.innerHeight);

      if (window.visualViewport && baselineVvH != null){
        kbd = Math.max(kbd, Math.round(baselineVvH - window.visualViewport.height));
      }

      // threshold biar address bar ga dianggap keyboard
      if (kbd < 120) kbd = 0;

      // 3) dorong balik bottom-nav ke bawah sebesar kbd (jadi tidak ikut naik)
      root.style.setProperty('--vv-kbd', kbd + 'px');
    }

    updateVhAndKeyboard();

    window.addEventListener('resize', updateVhAndKeyboard);
    window.addEventListener('orientationchange', function(){
      baselineInnerH = window.innerHeight;
      baselineVvH = window.visualViewport ? window.visualViewport.height : baselineVvH;
      setTimeout(updateVhAndKeyboard, 120);
    });

    if (window.visualViewport){
      window.visualViewport.addEventListener('resize', updateVhAndKeyboard);
      window.visualViewport.addEventListener('scroll', updateVhAndKeyboard);
    }

    document.addEventListener('focusin', function(){ setTimeout(updateVhAndKeyboard, 0); });
    document.addEventListener('focusout', function(){ setTimeout(updateVhAndKeyboard, 120); });
  })();
  </script>

  <script>
  // Lock pinch / double-tap zoom on mobile production screens.
  (function(){
    document.addEventListener('gesturestart', function(e){ e.preventDefault(); }, { passive:false });
    document.addEventListener('gesturechange', function(e){ e.preventDefault(); }, { passive:false });
    document.addEventListener('gestureend', function(e){ e.preventDefault(); }, { passive:false });

    let lastTouchEnd = 0;
    document.addEventListener('touchend', function(e){
      const now = Date.now();
      if (now - lastTouchEnd <= 300) {
        e.preventDefault();
      }
      lastTouchEnd = now;
    }, { passive:false });
  })();
  </script>

  @stack('scripts')

{{-- OWNER FLOATING TOOLS --}}
@auth
    @php
        $gfOwnerUser = auth()->user();
        $gfOwnerEmail = env('OWNER_EMAIL', 'ciciadeliamardani@gmail.com');

        $gfIsOwner =
            (bool) ($gfOwnerUser->is_owner ?? false) ||
            (($gfOwnerUser->role ?? null) === 'owner') ||
            (($gfOwnerUser->email ?? null) === $gfOwnerEmail) ||
            ($gfOwnerUser->isDeveloper());
    @endphp

    @if ($gfIsOwner)
        @php
            $gfDbMode = strtolower((string) env('APP_DB_MODE', ''));
            $gfDbPath = (string) (config('database.connections.sqlite.database') ?: '');
            $gfOpsDbPath = (string) (env('GFID_OPS_DB') ?: database_path('database.sqlite'));
            $gfOpsDbReady =
                \Illuminate\Support\Facades\File::exists($gfOpsDbPath) &&
                \Illuminate\Support\Facades\File::size($gfOpsDbPath) > 0;
            $gfDbMode = in_array($gfDbMode, ['dev', 'ops'], true)
                ? $gfDbMode
                : (str_contains($gfDbPath, 'dev') ? 'dev' : 'unknown');
            $gfDbModeLabel = match ($gfDbMode) {
                'dev' => 'DEV',
                'ops' => 'OPS',
                default => 'DB?',
            };
            $gfShowDevPet = $gfOwnerUser->isDeveloper() || $gfDbMode === 'ops';
        @endphp

        @if ($gfShowDevPet)
            @include('layouts.partials.dev-ai-pet')
        @endif

        <div class="gf-owner-floating-tools">
            <div class="dropdown gf-owner-mode-wrap">
                <button type="button"
                    class="gf-owner-mode-trigger is-{{ $gfDbMode }}"
                    data-bs-toggle="dropdown"
                    aria-expanded="false">
                    <span class="gf-owner-worklog-dot"></span>
                    <span>{{ $gfDbModeLabel }}</span>
                </button>
                <div class="dropdown-menu dropdown-menu-end gf-owner-mode-menu">
                    <div class="dropdown-header">Mode Database</div>
                    <div class="gf-owner-mode-path" title="{{ $gfDbPath }}">{{ basename($gfDbPath) ?: '-' }}</div>
                    @if (!$gfOpsDbReady)
                        <div class="gf-owner-mode-path" style="color:#b45309;" title="{{ $gfOpsDbPath }}">
                            OPS belum disiapkan
                        </div>
                    @endif

                    @if ($gfDbMode !== 'ops')
                    {{-- Quick Snapshot & Rollback — hanya di mode DEV --}}
                    <div class="dropdown-divider"></div>
                    <div class="dropdown-header d-flex justify-content-between align-items-center pe-2">
                        <span>Snapshot</span>
                        <form action="{{ route('owner.snapshots.store') }}" method="POST" class="m-0">
                            @csrf
                            <button type="submit" class="btn btn-warning btn-sm py-0 px-2" style="font-size:11px;">
                                <i class="bi bi-camera"></i> Ambil
                            </button>
                        </form>
                    </div>

                    @if(!empty($gfSnapshots))
                        @foreach($gfSnapshots as $snap)
                            <div class="d-flex align-items-center gap-1 px-2 py-1">
                                <div class="flex-grow-1 overflow-hidden" style="min-width:0;">
                                    <div class="small fw-medium text-truncate" style="font-size:11px;max-width:140px;">
                                        {{ $snap['label'] ?? $snap['date']?->format('d/m H:i') ?? $snap['filename'] }}
                                    </div>
                                    <div class="text-muted" style="font-size:10px;">
                                        {{ $snap['date']?->format('d M H:i') }} · {{ $snap['size_mb'] }} MB
                                    </div>
                                </div>
                                <form action="{{ route('owner.snapshots.restore', $snap['filename']) }}"
                                    method="POST" class="m-0 flex-shrink-0"
                                    data-gf-confirm
                                    data-gf-confirm-title="Rollback ke snapshot ini?"
                                    data-gf-confirm-text="{{ $snap['label'] ?? $snap['date']?->format('d M Y H:i') }} — DB aktif akan ditimpa, backup otomatis dibuat."
                                    data-gf-confirm-icon="warning"
                                    data-gf-confirm-ok="Rollback">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="btn btn-outline-warning btn-sm py-0 px-1" style="font-size:11px;">
                                        <i class="bi bi-arrow-counterclockwise"></i>
                                    </button>
                                </form>
                            </div>
                        @endforeach
                        <a href="{{ route('owner.snapshots.index') }}" class="dropdown-item text-muted" style="font-size:11px;">
                            Semua snapshot →
                        </a>
                    @else
                        <div class="px-3 py-1 text-muted" style="font-size:11px;">Belum ada snapshot</div>
                        <a href="{{ route('owner.snapshots.index') }}" class="dropdown-item text-muted" style="font-size:11px;">
                            Kelola snapshot →
                        </a>
                    @endif
                    @endif {{-- snapshot: hanya DEV --}}

                    {{-- Switch mode — selalu tampil --}}
                    <div class="dropdown-divider"></div>

                    <form action="{{ route('owner.database-mode.switch') }}" method="POST"
                        data-gf-confirm
                        data-gf-confirm-title="Pindah ke mode DEV?"
                        data-gf-confirm-text="Aplikasi akan memakai database development untuk seluruh modul."
                        data-gf-confirm-ok="Pakai DEV">
                        @csrf
                        <input type="hidden" name="mode" value="dev">
                        <button type="submit" class="dropdown-item" @disabled($gfDbMode === 'dev')>
                            DEV / Development
                        </button>
                    </form>

                    @if ($gfOpsDbReady)
                        <form action="{{ route('owner.database-mode.switch') }}" method="POST"
                            data-gf-confirm
                            data-gf-confirm-title="Pindah ke mode OPERASIONAL?"
                            data-gf-confirm-text="Aplikasi akan memakai database operasional untuk seluruh modul."
                            data-gf-confirm-ok="Pakai OPS">
                            @csrf
                            <input type="hidden" name="mode" value="ops">
                            <button type="submit" class="dropdown-item" @disabled($gfDbMode === 'ops')>
                                OPS / Operasional
                            </button>
                        </form>
                    @else
                        <form action="{{ route('owner.database-mode.switch') }}" method="POST"
                            data-gf-confirm
                            data-gf-confirm-title="Siapkan database OPERASIONAL?"
                            data-gf-confirm-text="DB aktif sekarang akan disalin menjadi database operasional. Lakukan hanya jika data aktif ini memang data operasional yang benar."
                            data-gf-confirm-ok="Siapkan OPS">
                            @csrf
                            <input type="hidden" name="mode" value="ops">
                            <input type="hidden" name="action" value="init_from_current">
                            <button type="submit" class="dropdown-item">
                                Siapkan OPS dari DB aktif
                            </button>
                        </form>
                    @endif
                </div>
            </div>

            <a href="{{ route('owner.work-logs.index') }}"
                class="gf-owner-worklog-nav {{ request()->routeIs('owner.work-logs.*') ? 'is-active' : '' }}">
                <span>Owner Log</span>
            </a>
        </div>
    @endif
@endauth


@auth
@if(!in_array(auth()->user()->role, ['owner', 'developer']))
<script>
document.addEventListener('DOMContentLoaded', function() {
    const startTime = Date.now();
    const endpoint = '{{ url('/activity-logs') }}';
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    function sendLog(action, data) {
        const payload = JSON.stringify({
            _token: csrfToken,
            url: window.location.href.substring(0, 500),
            action: action,
            ...data
        });

        fetch(endpoint, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: payload,
            keepalive: true
        }).catch(e => console.error(e));
    }

    // Track clicks on a, button, input, select, and label elements
    document.addEventListener('click', function(e) {
        let el = e.target.closest('a, button, input, select, label, textarea');
        if (!el) return;
        
        let text = '';
        
        // Handle different types of elements
        if (el.tagName.toLowerCase() === 'input') {
            if (el.type === 'radio' || el.type === 'checkbox') {
                text = 'Toggle ' + el.type + (el.value ? ' [' + el.value + ']' : '');
            } else if (el.type === 'submit' || el.type === 'button') {
                text = el.value || 'Submit Button';
            } else {
                text = 'Input Field (' + el.type + ')';
            }
        } else if (el.tagName.toLowerCase() === 'select') {
            text = 'Select Dropdown';
        } else {
            text = el.innerText ? el.innerText.trim().substring(0, 50) : '';
        }

        if (!text && el.title) text = el.title;
        if (!text && el.name) text = el.name;
        if (!text) text = 'Icon/Element';
        
        let identity = el.tagName.toLowerCase();
        if (el.id) identity += '#' + el.id;
        else if (el.className && typeof el.className === 'string') identity += '.' + el.className.split(' ').join('.');
        if (el.name) identity += '[name=' + el.name + ']';
        
        let targetElement = text + ' (' + identity + ')';
        targetElement = targetElement.substring(0, 500);

        sendLog('click', { target_element: targetElement });
    }, { capture: true }); // Use capture to ensure we get it even if stopped propagation

    // Track dwell time
    window.addEventListener('beforeunload', function() {
        const duration = Date.now() - startTime;
        sendLog('visit', { duration_ms: duration });
    });
});
</script>
@endif
@endauth

</body>
</html>




{{-- <input type="text" id="date1" class="form-control" placeholder="Pilih tanggal">
@push('scripts')
<script>
  GFID.initDate('#date1');
</script>
@endpush --}}


{{-- <input type="text" id="range1" class="form-control" placeholder="Pilih rentang tanggal">
@push('scripts')
<script>
  GFID.initDateRange('#range1', { showMonths: 2 });
</script>
@endpush --}}
