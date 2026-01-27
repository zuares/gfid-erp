{{-- resources/views/layouts/partials/styles.blade.php --}}
<style>
  :root{
    color-scheme: light dark;

    /* ========= PALETTE DASAR ========= */
    /* LIGHT */
    --bg-light:#f4f5fb;
    --bg-soft-light:#e5e9f5;
    --card-light:#ffffff;
    --card-soft-light:#f9fafb;
    --sidebar-light:#f3f4ff;

    --text-light:#111827;
    --muted-light:#6b7280;
    --line-light:#d4d7e3;

    --accent-light:#2563eb;
    --accent-soft-light:#dbeafe;

    --danger-light:#dc2626;
    --danger-soft-light:#fee2e2;

    --success-light:#16a34a;
    --success-soft-light:#dcfce7;

    /* DARK (base) */
    --bg-dark:#020617;
    --bg-soft-dark:#020b1b;
    --card-dark:#020b1b;
    --card-soft-dark:#020a16;
    --sidebar-dark:#020817;

    --text-dark:#e5e7eb;
    --muted-dark:#9ca3af;
    --line-dark:#1f2937;

    --accent-dark:#60a5fa;
    --accent-soft-dark:#1d283a;

    --danger-dark:#f87171;
    --danger-soft-dark:#451a1a;

    --success-dark:#4ade80;
    --success-soft-dark:#064e3b;

    /* Android baseline viewport helper (dipakai layout kamu) */
    --app-vh: 100vh;
    --vv-kbd: 0px;
  }

  /* ========= THEME RESOLVER ========= */
  [data-theme="light"]{
    --bg:var(--bg-light);
    --bg-soft:var(--bg-soft-light);
    --card:var(--card-light);
    --card-soft:var(--card-soft-light);
    --sidebar:var(--sidebar-light);

    --text:var(--text-light);
    --muted:var(--muted-light);
    --line:var(--line-light);

    --accent:var(--accent-light);
    --accent-soft:var(--accent-soft-light);

    --danger:var(--danger-light);
    --danger-soft:var(--danger-soft-light);

    --success:var(--success-light);
    --success-soft:var(--success-soft-light);

    --primary:var(--accent);
    --primary-soft:var(--accent-soft);

    --muted-rgb:107,114,128;
    --line-rgb:212,215,227;
    --sidebar-rgb:243,244,255;

    --icon-center-bg:#0f5054;
    --icon-center-border:#2c8bbe;
    --icon-center-fg:#0f172a;
  }

  [data-theme="dark"]{
    --bg:#0a1a2b;
    --bg-soft:#0f2238;
    --card:#11263f;
    --card-soft:#132a45;
    --sidebar:#0f2238;

    --text:#f8fafc;
    --muted:#cbd5e1;
    --line:#1e3a5f;

    --accent:#60a5fa;
    --accent-soft:#1e40af;

    --danger:#fb7185;
    --danger-soft:#4c0519;

    --success:#4ade80;
    --success-soft:#064e3b;

    --primary:var(--accent);
    --primary-soft:var(--accent-soft);

    --muted-rgb:203,213,225;
    --line-rgb:30,58,95;
    --sidebar-rgb:15,34,56;

    --item-suggest-bg:#0f2238;
    --item-suggest-border:#1e3a5f;
    --item-suggest-text:#f8fafc;
    --item-suggest-muted:#94a3b8;
    --item-suggest-hover-bg:rgba(96,165,250,.20);
    --item-suggest-error:#fca5a5;
  }

  /* ========= GLOBAL RESET ========= */
  html{
    box-sizing:border-box;
    -webkit-text-size-adjust:100%;
    height:100%;
  }
  *,*::before,*::after{ box-sizing:inherit; }

  body{
    margin:0;
    min-height:100%;
    font-family:system-ui,-apple-system,BlinkMacSystemFont,"SF Pro Text","Segoe UI",sans-serif;
    font-size:14px;
    line-height:1.4;
    color:var(--text);
    background:radial-gradient(circle at top,
      color-mix(in srgb, var(--bg) 90%, #ffffff 10%),
      var(--bg)
    );
    overscroll-behavior-y:contain;
  }

  /* IMPORTANT: biar input tidak auto-zoom iOS */
  input, select, textarea{ font-size:16px; }

  a, .link-primary{ color:var(--accent); }
  a:hover{ color:var(--accent); opacity:.9; }

  h1,h2,h3,h4,h5,h6,.h1,.h2,.h3,.h4,.h5,.h6{
    color:var(--text);
    margin-bottom:.35rem;
  }
  p,span,label,small,.form-label{ color:var(--text); }
  .text-muted,.muted{ color:var(--muted)!important; }

  .mono{
    font-variant-numeric:tabular-nums;
    font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,"Liberation Mono";
  }

  .help{ color:var(--muted); font-size:.85rem; }

  /* ========= FLEX LAYOUT SCROLL FIX (ANDROID) =========
     Karena #app kamu flex-column + min-height var(--app-vh),
     scroll HARUS terjadi di .app-main (bukan body).
  */
  #app.app-root{ min-height: var(--app-vh); }
  .app-shell{ min-height:0; }
  /* .app-main{
    min-height:0;
    overflow-y:auto;
    -webkit-overflow-scrolling:touch;
    overscroll-behavior: none;
  } */

  /* Prevent horizontal scroll */
  html, body{ overflow-x:hidden; }

  .page-wrap{
    max-width:1080px;
    margin-inline:auto;
    padding-inline:.75rem;
  }

  @media (max-width: 767.98px){
    .app-main .page-wrap{
      /* ruang aman dari bottom nav */
      padding-bottom: 9rem;
    }
  }

  /* ========= CARD ========= */
  .card{
    background:var(--card);
    border:1px solid var(--line);
    border-radius:14px;
    box-shadow:0 18px 45px rgba(15,23,42,.12),0 1px 0 rgba(15,23,42,.04);
    color:var(--text);
  }

  .card-header{
    background:color-mix(in srgb, var(--card) 85%, var(--bg) 15%);
    border-bottom:1px solid var(--line);
    color:var(--text)!important;
  }

  .card-header h1,.card-header h2,.card-header h3,.card-header h4,.card-header h5,.card-header h6,.card-title{
    color:var(--text)!important;
    margin-bottom:0;
  }

  .card-body{ color:var(--text); }

  /* ========= TABLE ========= */
  .table{ color:var(--text); font-size:.86rem; }
  .table > :not(caption) > * > *{
    background-color:transparent;
    border-bottom-color:var(--line);
  }
  .table thead th{
    background:color-mix(in srgb, var(--card) 85%, var(--bg) 15%);
    border-bottom:1px solid var(--line);
    color:var(--text)!important;
    font-weight:600;
  }
  .table tfoot th,.table tfoot td{
    border-top:1px solid var(--line);
    background:color-mix(in srgb, var(--card) 90%, var(--bg) 10%);
  }

  /* ========= FORM ========= */
  .form-control, .form-select{
    background-color:color-mix(in srgb, var(--card) 95%, var(--bg) 5%);
    border-color:var(--line);
    color:var(--text);
  }
  .form-control:focus, .form-select:focus{
    border-color:var(--accent);
    box-shadow:0 0 0 1px color-mix(in srgb, var(--accent-soft) 70%, var(--accent) 30%);
  }
  .form-control::placeholder{ color:var(--muted); }

  /* ========= BUTTONS (baseline) ========= */
  .btn-primary{
    background:var(--accent);
    border-color:var(--accent);
    box-shadow:0 8px 25px rgba(37,99,235,.35);
  }
  .btn-primary:hover{ filter:brightness(1.05); }
  .btn-outline-primary{ color:var(--accent); border-color:var(--accent); }
  .btn-outline-primary:hover{
    background:color-mix(in srgb, var(--accent-soft) 70%, var(--accent) 30%);
    color:var(--accent);
  }
  .btn-soft{
    background:var(--accent-soft);
    color:var(--accent);
    border:1px solid color-mix(in srgb, var(--accent) 10%, var(--line) 90%);
  }

  /* ========= TAG / CHIP ========= */
  .tag-soft{
    display:inline-flex; align-items:center; gap:.25rem;
    border-radius:999px;
    padding:.1rem .55rem;
    font-size:.7rem;
    border:1px solid var(--line);
    background:var(--accent-soft);
    color:var(--accent);
  }

  /* ========= FOOTER ========= */
  .app-footer{
    border-top:1px solid var(--line);
    background:var(--card);
    color:var(--muted);
    font-size:.85rem;
    padding:.75rem 1rem 1rem;
  }

  @media (max-width: 576px){
    .app-footer{ padding-bottom:4.5rem; }

    /* ✅ Anti double-tap zoom: JANGAN di html/body (bisa matiin scroll Android)
       taruh di element interaktif saja */
    button, .btn, a{
      touch-action: manipulation;
    }
  }

  /* ========= ITEM SUGGEST ========= */
  .item-suggest{ position:relative; }
  .item-suggest-dropdown{
    position:absolute;
    z-index:30;
    top:100%; left:0; right:0;
    margin-top:2px;
    background:var(--card, #fff);
    border-radius:10px;
    border:1px solid rgba(148,163,184,.5);
    box-shadow:0 12px 30px rgba(15,23,42,.18), 0 0 0 1px rgba(15,23,42,.04);
    max-height:260px;
    overflow-y:auto;
    padding:2px;
    font-size:.82rem;
  }
  .item-suggest-item{
    padding:.25rem .45rem;
    border-radius:8px;
    cursor:pointer;
    display:flex;
    flex-direction:column;
    gap:1px;
  }
  .item-suggest-item strong{ font-size:.84rem; }
  .item-suggest-item small{ font-size:.74rem; }
  .item-suggest-item:hover, .item-suggest-item--active{
    background:rgba(59,130,246,.14);
  }
  .item-suggest-empty{
    padding:.35rem .5rem;
    font-size:.78rem;
    color:#94a3b8;
  }
  @media (max-width: 767.98px){
    .item-suggest-dropdown{ max-height:220px; }
  }

  /* ========= OPTIONAL: AMOLED OVERRIDE (kalau kamu pakai file terpisah, biarkan di sana) =========
     Kalau kamu memang mau pure black, lebih aman taruh di css file override (dark-high-contrast.css),
     bukan dicampur di sini. Tapi kalau mau tetap di sini, kamu bisa aktifkan di bawah.
  */
</style>
