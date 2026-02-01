{{-- resources/views/layouts/app.blade.php --}}
<!DOCTYPE html>
<html lang="id" data-theme="light">
<head>
  <meta charset="utf-8">

  {{-- viewport: standar --}}
  <meta name="viewport" content="width=device-width, initial-scale=1">

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
    }
    *,*::before,*::after{ box-sizing:inherit; }

    body{
      overflow-y:auto !important;
      font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
      font-size:14px;
      line-height:1.4;
      margin:0;
      height:100%;
    }

    /* cegah auto-zoom iOS saat focus input */
    input,select,textarea{ font-size:16px; }

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
      border-radius: 14px;
      border: 1px solid rgba(148,163,184,.18);
      box-shadow: 0 18px 48px rgba(15,23,42,.22);
      overflow: hidden;
    }
    .flatpickr-months .flatpickr-month,
    .flatpickr-weekdays{
      background: transparent;
    }
    body[data-theme="dark"] .flatpickr-calendar{
      box-shadow: 0 18px 48px rgba(0,0,0,.55);
      border-color: rgba(148,163,184,.12);
    }
  </style>

  {{-- THEME + GLOBAL STYLES --}}
  @include('layouts.partials.styles')
  <link rel="stylesheet" href="{{ asset('css/light-minimal.css') }}">
  <link rel="stylesheet" href="{{ asset('css/dark-high-contrast.css') }}">

  @stack('head')
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

          {{-- Flash message --}}
          @if (session('success'))
            <div class="alert alert-success mb-3">{{ session('success') }}</div>
          @endif

          @if (session('error'))
            <div class="alert alert-danger mb-3">{{ session('error') }}</div>
          @endif

          @php
            $hasValidationErrors = $errors instanceof \Illuminate\Support\ViewErrorBag ? $errors->any() : false;
          @endphp

          @if ($hasValidationErrors)
            <div class="alert alert-danger mb-3">
              <strong>Terjadi error:</strong>
              <ul class="mb-0">
                @foreach ($errors->all() as $error)
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

  {{-- THEME TOGGLER SCRIPT --}}
  @include('layouts.partials.theme-script')

  {{-- =========================
    ✅ GLOBAL HELPERS (bisa dipakai di semua halaman)
    - window.GFID.initDate(selector, options)
    - window.GFID.initDateRange(selector, options)
  ========================= --}}
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
          allowInput: true,
          disableMobile: true
        };
      }

      window.GFID.initDate = function(selectorOrEl, options = {}){
        const el = pickEl(selectorOrEl);
        if (!el || !window.flatpickr) return null;

        return window.flatpickr(el, Object.assign({}, baseOptions(), options));
      };

      window.GFID.initDateRange = function(selectorOrEl, options = {}){
        const el = pickEl(selectorOrEl);
        if (!el || !window.flatpickr) return null;

        return window.flatpickr(el, Object.assign({}, baseOptions(), {
          mode: 'range'
        }, options));
      };
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

  @stack('scripts')
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

