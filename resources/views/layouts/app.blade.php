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

<style>
    /* OWNER WORK LOG GLOBAL NAV */
    .gf-owner-worklog-nav {
        position: fixed;
        right: 18px;
        bottom: 86px;
        z-index: 1040;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        min-height: 42px;
        border-radius: 999px;
        padding: 0 15px;
        color: #0f172a;
        background: rgba(255,255,255,.92);
        border: 1px solid rgba(15,23,42,.10);
        box-shadow: 0 14px 34px rgba(15,23,42,.14);
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
        font-size: 12px;
        font-weight: 950;
        text-decoration: none;
        letter-spacing: -.01em;
        transition: .16s ease;
    }

    .gf-owner-worklog-nav:hover {
        color: #0f172a;
        transform: translateY(-1px);
        box-shadow: 0 18px 40px rgba(15,23,42,.18);
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

    @media (max-width: 768px) {
        .gf-owner-worklog-nav {
            left: 14px;
            right: 14px;
            bottom: calc(76px + env(safe-area-inset-bottom));
            width: auto;
            min-height: 44px;
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
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

  {{-- THEME TOGGLER SCRIPT --}}
  @include('layouts.partials.theme-script')

  {{-- =========================
    ✅ GLOBAL HELPERS (bisa dipakai di semua halaman)
    - window.GFID.initDate(selector, options)
    - window.GFID.initDateRange(selector, options)
    - window.GFID.initGreatfitUi(root)
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

        scope.querySelectorAll('form[data-gf-confirm], form[data-confirm]').forEach((form) => {
          if (form.dataset.gfConfirmBound === '1') return;
          form.dataset.gfConfirmBound = '1';

          form.addEventListener('submit', (event) => {
            if (form.dataset.confirmed === '1' || !window.Swal) return;
            event.preventDefault();

            Swal.fire({
              title: form.dataset.gfConfirmTitle || form.dataset.confirmTitle || 'Lanjutkan?',
              text: form.dataset.gfConfirmText || form.dataset.confirmText || 'Pastikan data sudah benar sebelum diproses.',
              icon: form.dataset.gfConfirmIcon || form.dataset.confirmIcon || 'question',
              showCancelButton: true,
              confirmButtonText: form.dataset.gfConfirmOk || form.dataset.confirmOk || 'Ya, lanjutkan',
              cancelButtonText: form.dataset.gfConfirmCancel || form.dataset.confirmCancel || 'Batal',
              reverseButtons: true,
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
              text: el.dataset.gfConfirmText || 'Aksi ini akan diproses.',
              icon: el.dataset.gfConfirmIcon || 'question',
              showCancelButton: true,
              confirmButtonText: el.dataset.gfConfirmOk || 'Ya, lanjutkan',
              cancelButtonText: el.dataset.gfConfirmCancel || 'Batal',
              reverseButtons: true,
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

  @stack('scripts')

{{-- OWNER WORK LOG NAVBAR --}}
@auth
    @php
        $gfOwnerUser = auth()->user();
        $gfOwnerEmail = env('OWNER_EMAIL', 'ciciadeliamardani@gmail.com');

        $gfIsOwner =
            (bool) ($gfOwnerUser->is_owner ?? false) ||
            (($gfOwnerUser->role ?? null) === 'owner') ||
            (($gfOwnerUser->email ?? null) === $gfOwnerEmail);
    @endphp

    @if ($gfIsOwner)
        <a href="{{ route('owner.work-logs.index') }}"
            class="gf-owner-worklog-nav {{ request()->routeIs('owner.work-logs.*') ? 'is-active' : '' }}">
            <span class="gf-owner-worklog-dot"></span>
            <span>Owner Log</span>
        </a>
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

