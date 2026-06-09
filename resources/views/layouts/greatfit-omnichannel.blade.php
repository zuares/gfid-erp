<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'GreatFit Omnichannel')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root{--gf-bg:#f5f7fb;--gf-dark:#111827;--gf-soft:#1f2937;--gf-border:#e5e7eb;--gf-muted:#6b7280;--gf-accent:#f59e0b;--gf-radius:18px}
        body{background:var(--gf-bg);font-family:Inter,system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;color:#111827}
        .gf-shell{min-height:100vh;display:flex}.gf-sidebar{width:280px;background:linear-gradient(180deg,var(--gf-dark),var(--gf-soft));color:#fff;position:fixed;top:0;bottom:0;left:0;padding:22px 18px;overflow-y:auto;z-index:30}
        .gf-brand{display:flex;gap:12px;align-items:center;padding:10px 10px 22px;border-bottom:1px solid rgba(255,255,255,.08);margin-bottom:18px}.gf-brand-logo{width:44px;height:44px;border-radius:15px;background:#fff;color:#111827;display:grid;place-items:center;font-weight:900}
        .gf-nav-label{color:rgba(255,255,255,.44);font-size:11px;text-transform:uppercase;letter-spacing:.12em;padding:10px 12px}.gf-nav-link{display:flex;align-items:center;gap:12px;color:rgba(255,255,255,.78);text-decoration:none;padding:12px 13px;border-radius:14px;margin-bottom:6px}.gf-nav-link:hover,.gf-nav-link.active{background:rgba(255,255,255,.1);color:#fff}
        .gf-content{flex:1;margin-left:280px;padding:24px}.gf-topbar{background:rgba(255,255,255,.82);border:1px solid var(--gf-border);border-radius:var(--gf-radius);padding:16px 18px;display:flex;justify-content:space-between;align-items:center;box-shadow:0 12px 30px rgba(15,23,42,.05);margin-bottom:22px}
        .gf-page-title{font-weight:850;letter-spacing:-.04em;margin:0}.gf-page-subtitle{color:var(--gf-muted);margin:3px 0 0;font-size:14px}.gf-card,.gf-stat{background:#fff;border:1px solid var(--gf-border);border-radius:var(--gf-radius);box-shadow:0 12px 30px rgba(15,23,42,.05)}
        .gf-card-header{padding:18px 20px;border-bottom:1px solid var(--gf-border);display:flex;align-items:center;justify-content:space-between;gap:12px}.gf-card-body{padding:20px}.gf-stat{padding:18px;height:100%}.gf-stat-icon{width:42px;height:42px;border-radius:14px;display:grid;place-items:center;background:#fff7e6;color:var(--gf-accent);font-size:20px}.gf-stat-value{font-weight:850;letter-spacing:-.04em;font-size:28px;margin-top:12px}.gf-stat-label{color:var(--gf-muted);font-size:13px}
        .btn-gf-dark{background:#111827;color:#fff;border:0;border-radius:14px;padding:10px 15px;font-weight:700}.btn-gf-dark:hover{background:#000;color:#fff}.btn-gf-soft{background:#f3f4f6;color:#111827;border:1px solid #e5e7eb;border-radius:14px;padding:10px 15px;font-weight:700}.gf-input{border-radius:14px;border:1px solid var(--gf-border);padding:10px 13px}.gf-badge{border-radius:999px;padding:7px 10px;font-size:12px;font-weight:750}.gf-badge-green{background:#dcfce7;color:#166534}.gf-badge-yellow{background:#fef3c7;color:#92400e}.gf-badge-gray{background:#f3f4f6;color:#374151}.gf-empty{border:1px dashed #d1d5db;border-radius:var(--gf-radius);padding:36px;text-align:center;color:var(--gf-muted);background:#fafafa}.table>:not(caption)>*>*{padding:14px 12px;vertical-align:middle}
        @media(max-width:992px){.gf-sidebar{display:none}.gf-content{margin-left:0;padding:16px}.gf-topbar{align-items:flex-start;gap:14px;flex-direction:column}}
    </style>
    @stack('styles')
</head>
<body>
<div class="gf-shell">
    <aside class="gf-sidebar">
        <div class="gf-brand">
            <div class="gf-brand-logo">GF</div>
            <div><div class="fw-bold">GreatFit</div><small class="text-white-50">Good Fit, Good Feel.</small></div>
        </div>
        <div class="gf-nav-label">Marketplace</div>
        <a href="/marketplace/toko" class="gf-nav-link active"><i class="bi bi-shop-window"></i><span>Toko & Channel</span></a>
        <a href="/marketplace/shopee/connect" class="gf-nav-link"><i class="bi bi-box-arrow-in-right"></i><span>Login Shopee</span></a>
    </aside>
    <main class="gf-content">
        <div class="gf-topbar">
            <div>
                <h1 class="gf-page-title">@yield('page_title', 'Dashboard')</h1>
                <p class="gf-page-subtitle">@yield('page_subtitle', 'GreatFit internal system')</p>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span class="gf-badge gf-badge-green">Live</span>
                <span class="gf-badge gf-badge-gray">{{ now()->format('d M Y') }}</span>
            </div>
        </div>
        @yield('content')
    </main>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
@stack('scripts')
</body>
</html>
