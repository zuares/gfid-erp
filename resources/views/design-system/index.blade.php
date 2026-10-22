@extends('layouts.app')

@section('title', 'GFID Design System')

@push('head')
    <style>
        :root {
            --ds-ink: #101828;
            --ds-slate: #667085;
            --ds-line: #e4e7ec;
            --ds-surface: #ffffff;
            --ds-surface-muted: #f8fafc;
            --ds-brand: #5b5ce2;
            --ds-brand-strong: #4142bb;
            --ds-brand-soft: #eeefff;
            --ds-cyan: #13b8c4;
            --ds-success: #14945a;
            --ds-warning: #b7791f;
            --ds-danger: #d6455d;
            --ds-shadow-sm: 0 1px 2px rgba(16, 24, 40, .04), 0 4px 12px rgba(16, 24, 40, .04);
            --ds-shadow-lg: 0 24px 56px rgba(48, 49, 125, .14);
        }

        body.ds-preview {
            background: #f5f7fb !important;
        }

        .ds-shell {
            --ds-text: var(--ds-ink);
            --ds-muted: var(--ds-slate);
            color: var(--ds-text);
            display: grid;
            grid-template-columns: 238px minmax(0, 1fr);
            gap: 1.2rem;
            max-width: 1380px;
            margin: -.55rem auto 2rem;
            padding: 0 .1rem 2rem;
        }

        .ds-sidebar {
            position: sticky;
            top: 1rem;
            align-self: start;
            padding: .65rem;
            border: 1px solid rgba(228, 231, 236, .88);
            border-radius: 22px;
            background: rgba(255, 255, 255, .8);
            box-shadow: var(--ds-shadow-sm);
            backdrop-filter: blur(18px);
        }

        .ds-mark {
            display: flex;
            align-items: center;
            gap: .7rem;
            padding: .7rem .72rem 1rem;
        }

        .ds-mark-icon {
            display: grid;
            width: 34px;
            height: 34px;
            place-items: center;
            border-radius: 12px;
            color: #fff;
            font-weight: 900;
            background: linear-gradient(145deg, var(--ds-brand), var(--ds-cyan));
            box-shadow: 0 8px 16px rgba(91, 92, 226, .24);
        }

        .ds-mark strong { display: block; font-size: .86rem; letter-spacing: -.02em; }
        .ds-mark small { display: block; margin-top: .08rem; color: var(--ds-muted); font-size: .66rem; }
        .ds-nav-label { padding: .7rem .75rem .35rem; color: #98a2b3; font-size: .62rem; font-weight: 800; letter-spacing: .11em; text-transform: uppercase; }
        .ds-nav { display: grid; gap: .18rem; }
        .ds-nav a {
            display: flex;
            align-items: center;
            gap: .62rem;
            padding: .62rem .72rem;
            border-radius: 11px;
            color: #667085;
            font-size: .78rem;
            font-weight: 650;
            text-decoration: none;
            transition: .18s ease;
        }
        .ds-nav a:hover, .ds-nav a.is-active { color: var(--ds-brand-strong); background: var(--ds-brand-soft); }
        .ds-nav a i { width: 16px; text-align: center; font-size: .85rem; }
        .ds-sidebar-foot { margin: 1.1rem .35rem .25rem; padding: .75rem; border: 1px solid #dfe1ff; border-radius: 14px; background: linear-gradient(140deg, #f6f6ff, #edfdff); }
        .ds-sidebar-foot p { margin: 0; color: #4c4e91; font-size: .68rem; line-height: 1.5; }

        .ds-main { min-width: 0; }
        .ds-hero {
            position: relative;
            overflow: hidden;
            padding: 1.55rem 1.7rem;
            border: 1px solid #e0e2fb;
            border-radius: 24px;
            background: linear-gradient(120deg, #f4f3ff 0%, #fff 48%, #edfcff 100%);
            box-shadow: var(--ds-shadow-lg);
        }
        .ds-hero::after { content: ''; position: absolute; width: 210px; height: 210px; right: -65px; top: -92px; border-radius: 50%; background: rgba(91, 92, 226, .1); filter: blur(2px); }
        .ds-hero-grid { position: relative; z-index: 1; display: flex; align-items: flex-end; justify-content: space-between; gap: 1rem; }
        .ds-eyebrow { display: inline-flex; align-items: center; gap: .42rem; margin-bottom: .62rem; color: var(--ds-brand-strong); font-size: .67rem; font-weight: 850; letter-spacing: .12em; text-transform: uppercase; }
        .ds-eyebrow span { width: 7px; height: 7px; border-radius: 50%; background: var(--ds-cyan); box-shadow: 0 0 0 4px rgba(19, 184, 196, .12); }
        .ds-hero h1 { max-width: 680px; margin: 0; color: var(--ds-ink); font-size: clamp(1.6rem, 2.7vw, 2.55rem); font-weight: 820; letter-spacing: -.055em; line-height: 1.04; }
        .ds-hero p { max-width: 640px; margin: .7rem 0 0; color: #667085; font-size: .86rem; line-height: 1.6; }
        .ds-hero-actions { display: flex; flex-wrap: wrap; justify-content: flex-end; gap: .5rem; }
        .ds-btn { display: inline-flex; align-items: center; justify-content: center; gap: .42rem; min-height: 36px; padding: 0 .78rem; border: 1px solid transparent; border-radius: 10px; font-size: .74rem; font-weight: 750; cursor: pointer; transition: .18s ease; }
        .ds-btn:hover { transform: translateY(-1px); }
        .ds-btn-primary { color: #fff; background: var(--ds-brand); box-shadow: 0 7px 14px rgba(91, 92, 226, .2); }
        .ds-btn-primary:hover { color: #fff; background: var(--ds-brand-strong); }
        .ds-btn-ghost { color: #475467; border-color: #d0d5dd; background: rgba(255,255,255,.75); }
        .ds-btn-ghost:hover { color: var(--ds-brand-strong); border-color: #b9baf4; background: #fff; }
        .ds-btn-dark { color: #fff; background: #101828; }
        .ds-btn-danger { color: #fff; background: var(--ds-danger); }
        .ds-btn-sm { min-height: 30px; padding-inline: .62rem; font-size: .68rem; border-radius: 8px; }

        .ds-meta-row { display: flex; flex-wrap: wrap; gap: .42rem; margin-top: 1.15rem; }
        .ds-chip { display: inline-flex; align-items: center; gap: .36rem; padding: .32rem .55rem; border: 1px solid rgba(208, 213, 221, .84); border-radius: 999px; color: #667085; background: rgba(255,255,255,.66); font-size: .66rem; font-weight: 700; }
        .ds-chip i { color: var(--ds-brand); }
        .ds-section { scroll-margin-top: 1rem; margin-top: 1.15rem; }
        .ds-section-head { display: flex; align-items: flex-end; justify-content: space-between; gap: 1rem; margin: 1.45rem .2rem .72rem; }
        .ds-section-head h2 { margin: 0; color: var(--ds-ink); font-size: 1.05rem; font-weight: 800; letter-spacing: -.035em; }
        .ds-section-head p { margin: .2rem 0 0; color: #98a2b3; font-size: .72rem; }
        .ds-section-count { color: #98a2b3; font-size: .65rem; font-weight: 750; }
        .ds-grid { display: grid; gap: .85rem; }
        .ds-grid-4 { grid-template-columns: repeat(4, minmax(0, 1fr)); }
        .ds-grid-3 { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        .ds-grid-2 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .ds-panel { min-width: 0; padding: 1rem; border: 1px solid var(--ds-line); border-radius: 17px; background: var(--ds-surface); box-shadow: var(--ds-shadow-sm); }
        .ds-panel-title { display: flex; align-items: center; justify-content: space-between; gap: .6rem; margin-bottom: .78rem; }
        .ds-panel-title h3 { margin: 0; color: var(--ds-ink); font-size: .78rem; font-weight: 800; }
        .ds-panel-title span { color: #98a2b3; font-family: ui-monospace, monospace; font-size: .61rem; }
        .ds-kpi { position: relative; overflow: hidden; }
        .ds-kpi::before { content: ''; position: absolute; width: 65px; height: 65px; right: -22px; top: -20px; border-radius: 50%; background: var(--ds-brand-soft); }
        .ds-kpi small { position: relative; color: #667085; font-size: .67rem; font-weight: 700; }
        .ds-kpi strong { position: relative; display: block; margin: .5rem 0 .18rem; color: var(--ds-ink); font-size: 1.45rem; font-weight: 850; letter-spacing: -.06em; }
        .ds-kpi p { position: relative; margin: 0; color: var(--ds-success); font-size: .66rem; font-weight: 700; }
        .ds-swatch { min-height: 86px; padding: .75rem; border-radius: 13px; color: #fff; box-shadow: inset 0 -24px 36px rgba(0,0,0,.08); }
        .ds-swatch small { display: block; opacity: .78; font-size: .62rem; }
        .ds-swatch strong { display: block; margin-top: 1.45rem; font-size: .71rem; }
        .ds-swatch-brand { background: linear-gradient(135deg, #6f70eb, #4142bb); }
        .ds-swatch-cyan { background: linear-gradient(135deg, #20cad0, #078b9c); }
        .ds-swatch-ink { background: #101828; }
        .ds-swatch-soft { color: #344054; background: #f2f4f7; box-shadow: none; }
        .ds-type-sample { padding: .5rem 0; border-bottom: 1px solid #f0f1f3; }
        .ds-type-sample:last-child { border-bottom: 0; }
        .ds-type-sample small { display: block; color: #98a2b3; font-family: ui-monospace, monospace; font-size: .61rem; }
        .ds-type-display { margin-top: .25rem; color: var(--ds-ink); font-size: 1.5rem; font-weight: 850; letter-spacing: -.06em; }
        .ds-type-body { margin-top: .25rem; color: #475467; font-size: .8rem; line-height: 1.5; }
        .ds-type-caption { margin-top: .25rem; color: #667085; font-size: .67rem; }
        .ds-control { display: grid; gap: .32rem; margin-bottom: .7rem; }
        .ds-control:last-child { margin-bottom: 0; }
        .ds-control label { color: #475467; font-size: .68rem; font-weight: 750; }
        .ds-control input, .ds-control select { width: 100%; min-height: 37px; padding: 0 .68rem; border: 1px solid #d0d5dd; border-radius: 9px; color: #344054; outline: none; background: #fff; font-size: .76rem; transition: .18s ease; }
        .ds-control input:focus, .ds-control select:focus { border-color: var(--ds-brand); box-shadow: 0 0 0 3px rgba(91, 92, 226, .12); }
        .ds-control input[aria-invalid="true"] { border-color: var(--ds-danger); }
        .ds-hint { color: #98a2b3; font-size: .63rem; }
        .ds-error { color: var(--ds-danger); font-size: .63rem; font-weight: 650; }
        .ds-action-row { display: flex; flex-wrap: wrap; align-items: center; gap: .42rem; }
        .ds-badge { display: inline-flex; align-items: center; min-height: 22px; padding: 0 .52rem; border-radius: 999px; font-size: .63rem; font-weight: 800; }
        .ds-badge-success { color: #067647; background: #ecfdf3; }
        .ds-badge-warning { color: #a15c07; background: #fffaeb; }
        .ds-badge-danger { color: #b42318; background: #fef3f2; }
        .ds-badge-neutral { color: #475467; background: #f2f4f7; }
        .ds-alert { display: flex; align-items: flex-start; gap: .65rem; padding: .75rem; border: 1px solid; border-radius: 12px; font-size: .71rem; line-height: 1.45; }
        .ds-alert i { margin-top: .05rem; font-size: .9rem; }
        .ds-alert-info { color: #175cd3; border-color: #b2ddff; background: #eff8ff; }
        .ds-alert-success { color: #067647; border-color: #abefc6; background: #ecfdf3; }
        .ds-alert-warning { color: #b54708; border-color: #fedf89; background: #fffaeb; }
        .ds-tabs { display: flex; gap: .15rem; padding: .2rem; border-radius: 10px; background: #f2f4f7; }
        .ds-tab { flex: 1; padding: .45rem .55rem; border: 0; border-radius: 7px; color: #667085; background: transparent; font-size: .68rem; font-weight: 750; cursor: pointer; }
        .ds-tab.is-active { color: var(--ds-brand-strong); background: #fff; box-shadow: 0 1px 4px rgba(16,24,40,.08); }
        .ds-tab-panel { display: none; padding-top: .75rem; color: #667085; font-size: .7rem; line-height: 1.5; }
        .ds-tab-panel.is-active { display: block; }
        .ds-table-wrap { overflow: auto; }
        .ds-table { width: 100%; min-width: 500px; border-collapse: collapse; }
        .ds-table th, .ds-table td { padding: .64rem .55rem; border-bottom: 1px solid #f0f1f3; text-align: left; font-size: .69rem; white-space: nowrap; }
        .ds-table th { color: #98a2b3; font-size: .6rem; font-weight: 800; letter-spacing: .04em; text-transform: uppercase; }
        .ds-table td { color: #475467; }
        .ds-table td strong { color: var(--ds-ink); font-weight: 750; }
        .ds-empty { display: grid; place-items: center; min-height: 160px; padding: 1rem; text-align: center; border: 1px dashed #d0d5dd; border-radius: 13px; background: #fcfcfd; }
        .ds-empty-icon { display: grid; width: 34px; height: 34px; place-items: center; margin-bottom: .5rem; border-radius: 11px; color: var(--ds-brand); background: var(--ds-brand-soft); }
        .ds-empty strong { display: block; color: var(--ds-ink); font-size: .75rem; }
        .ds-empty p { max-width: 260px; margin: .22rem 0 .7rem; color: #98a2b3; font-size: .65rem; line-height: 1.45; }
        .ds-skeleton { display: grid; gap: .5rem; }
        .ds-skeleton span { display: block; height: 10px; border-radius: 999px; background: linear-gradient(90deg, #eef0f3, #f8f9fa, #eef0f3); background-size: 180% 100%; animation: ds-shimmer 1.3s ease-in-out infinite; }
        .ds-skeleton span:nth-child(2) { width: 78%; }
        .ds-skeleton span:nth-child(3) { width: 56%; }
        @keyframes ds-shimmer { from { background-position: 180% 0; } to { background-position: -20% 0; } }
        .ds-layout-card { min-height: 170px; padding: .7rem; border: 1px solid #e4e7ec; border-radius: 14px; background: #fcfcfd; }
        .ds-layout-top { height: 15px; margin-bottom: .55rem; border-radius: 6px; background: #dfe1ff; }
        .ds-layout-body { display: grid; grid-template-columns: 28% 1fr; gap: .55rem; height: 112px; }
        .ds-layout-side { border-radius: 7px; background: #eef0ff; }
        .ds-layout-content { display: grid; grid-template-rows: 20% 1fr 20%; gap: .45rem; }
        .ds-layout-content span { border-radius: 7px; background: #f0f1f3; }
        .ds-layout-content span:nth-child(2) { background: #fff; border: 1px solid #e4e7ec; }
        .ds-layout-label { margin-top: .55rem; color: #475467; font-size: .68rem; font-weight: 800; }
        .ds-layout-note { margin-top: .13rem; color: #98a2b3; font-size: .63rem; line-height: 1.4; }
        .ds-token-row { display: flex; align-items: center; justify-content: space-between; gap: .75rem; padding: .58rem 0; border-bottom: 1px solid #f0f1f3; }
        .ds-token-row:last-child { border-bottom: 0; }
        .ds-token-row code { color: #475467; font-size: .66rem; }
        .ds-token-value { color: #98a2b3; font-size: .62rem; }
        .ds-toast { position: fixed; right: 1.2rem; bottom: 1.2rem; z-index: 1100; display: flex; align-items: center; gap: .6rem; padding: .7rem .85rem; border: 1px solid #d0d5dd; border-radius: 12px; color: #344054; background: #fff; box-shadow: 0 16px 35px rgba(16,24,40,.17); font-size: .72rem; opacity: 0; transform: translateY(8px); pointer-events: none; transition: .2s ease; }
        .ds-toast.is-visible { opacity: 1; transform: translateY(0); }
        .ds-toast i { color: var(--ds-success); }

        @media (max-width: 1100px) { .ds-shell { grid-template-columns: 190px minmax(0, 1fr); } .ds-grid-4 { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
        @media (max-width: 760px) { .ds-shell { display: block; margin-top: -.1rem; } .ds-sidebar { position: static; display: flex; align-items: center; gap: .75rem; margin-bottom: .85rem; overflow-x: auto; padding: .45rem; } .ds-mark, .ds-sidebar-foot, .ds-nav-label { display: none; } .ds-nav { display: flex; flex: 1; gap: .25rem; min-width: max-content; } .ds-nav a { padding: .55rem .65rem; } .ds-nav a i { display: none; } .ds-hero { padding: 1.2rem; border-radius: 18px; } .ds-hero-grid { display: block; } .ds-hero-actions { justify-content: flex-start; margin-top: 1rem; } .ds-grid-3, .ds-grid-2 { grid-template-columns: 1fr; } .ds-grid-4 { grid-template-columns: repeat(2, minmax(0, 1fr)); } .ds-section-head { align-items: flex-start; } }
        @media (max-width: 420px) { .ds-grid-4 { grid-template-columns: 1fr; } }
        @media (prefers-reduced-motion: reduce) { *, *::before, *::after { scroll-behavior: auto !important; animation-duration: .01ms !important; animation-iteration-count: 1 !important; transition-duration: .01ms !important; } }
    </style>
@endpush

@section('content')
    <div class="ds-shell" id="design-system">
        <aside class="ds-sidebar" aria-label="Design system navigation">
            <div class="ds-mark">
                <div class="ds-mark-icon">G</div>
                <div><strong>GFID UI</strong><small>Design system · v0.1</small></div>
            </div>
            <div class="ds-nav-label">Explore</div>
            <nav class="ds-nav">
                <a class="is-active" href="#overview"><i class="bi bi-grid-1x2-fill"></i> Overview</a>
                <a href="#foundations"><i class="bi bi-palette-fill"></i> Foundations</a>
                <a href="#components"><i class="bi bi-boxes"></i> Components</a>
                <a href="#patterns"><i class="bi bi-layout-text-window-reverse"></i> Patterns</a>
                <a href="#layouts"><i class="bi bi-columns-gap"></i> Layouts</a>
            </nav>
            <div class="ds-sidebar-foot">
                <p><strong>Migration ready</strong><br>Setiap contoh punya token dan kontrak yang bisa dipindahkan ke React.</p>
            </div>
        </aside>

        <main class="ds-main">
            <section class="ds-hero" id="overview">
                <div class="ds-hero-grid">
                    <div>
                        <div class="ds-eyebrow"><span></span> GFID operating system</div>
                        <h1>One visual language for every flow.</h1>
                        <p>Ruang kerja untuk merancang, menguji, dan menyepakati komponen sebelum dipakai di halaman Laravel maupun React.</p>
                        <div class="ds-meta-row">
                            <span class="ds-chip"><i class="bi bi-check2-circle"></i> Accessible by default</span>
                            <span class="ds-chip"><i class="bi bi-braces"></i> Token-driven</span>
                            <span class="ds-chip"><i class="bi bi-phone"></i> Responsive</span>
                        </div>
                    </div>
                    <div class="ds-hero-actions">
                        <button class="ds-btn ds-btn-ghost" type="button" id="ds-theme-toggle"><i class="bi bi-moon-stars"></i> Preview mode</button>
                        <button class="ds-btn ds-btn-primary" type="button" data-ds-toast="Komponen siap dipakai di halaman baru."><i class="bi bi-plus-lg"></i> Add component</button>
                    </div>
                </div>
            </section>

            <section class="ds-section" id="foundations">
                <div class="ds-section-head"><div><h2>Foundations</h2><p>Token dasar yang menjadi sumber kebenaran visual.</p></div><span class="ds-section-count">04 groups</span></div>
                <div class="ds-grid ds-grid-4">
                    <article class="ds-panel ds-kpi"><small>Components ready</small><strong>24</strong><p><i class="bi bi-arrow-up-right"></i> +6 this sprint</p></article>
                    <article class="ds-panel ds-kpi"><small>Design tokens</small><strong>68</strong><p><i class="bi bi-check2"></i> synced with CSS</p></article>
                    <article class="ds-panel ds-kpi"><small>Coverage</small><strong>72%</strong><p><i class="bi bi-graph-up-arrow"></i> target 90%</p></article>
                    <article class="ds-panel ds-kpi"><small>Migration stage</small><strong>01</strong><p><i class="bi bi-circle-half"></i> Blade-first</p></article>
                </div>
                <div class="ds-grid ds-grid-2" style="margin-top:.85rem">
                    <article class="ds-panel">
                        <div class="ds-panel-title"><h3>Color roles</h3><span>color.*</span></div>
                        <div class="ds-grid ds-grid-4">
                            <div class="ds-swatch ds-swatch-brand"><small>brand.primary</small><strong>#5B5CE2</strong></div>
                            <div class="ds-swatch ds-swatch-cyan"><small>brand.accent</small><strong>#13B8C4</strong></div>
                            <div class="ds-swatch ds-swatch-ink"><small>content.strong</small><strong>#101828</strong></div>
                            <div class="ds-swatch ds-swatch-soft"><small>surface.muted</small><strong>#F2F4F7</strong></div>
                        </div>
                    </article>
                    <article class="ds-panel">
                        <div class="ds-panel-title"><h3>Typography scale</h3><span>type.*</span></div>
                        <div class="ds-type-sample"><small>display / 40 · 850</small><div class="ds-type-display">Make it clear.</div></div>
                        <div class="ds-type-sample"><small>body / 14 · 400</small><div class="ds-type-body">Good interfaces make the next action obvious.</div></div>
                        <div class="ds-type-sample"><small>caption / 11 · 650</small><div class="ds-type-caption">LAST SYNCED 2 MIN AGO</div></div>
                    </article>
                </div>
            </section>

            <section class="ds-section" id="components">
                <div class="ds-section-head"><div><h2>Components</h2><p>Primitives yang harus terasa sama di seluruh domain.</p></div><span class="ds-section-count">Atoms → molecules</span></div>
                <div class="ds-grid ds-grid-2">
                    <article class="ds-panel">
                        <div class="ds-panel-title"><h3>Actions & status</h3><span>Button / Badge</span></div>
                        <div class="ds-action-row" style="margin-bottom:.85rem">
                            <button class="ds-btn ds-btn-primary" type="button" data-ds-toast="Primary action berhasil dijalankan."><i class="bi bi-lightning-charge-fill"></i> Primary</button>
                            <button class="ds-btn ds-btn-ghost" type="button">Secondary</button>
                            <button class="ds-btn ds-btn-dark" type="button"><i class="bi bi-download"></i> Export</button>
                            <button class="ds-btn ds-btn-danger ds-btn-sm" type="button">Delete</button>
                        </div>
                        <div class="ds-action-row"><span class="ds-badge ds-badge-success"><i class="bi bi-check2"></i>&nbsp; Active</span><span class="ds-badge ds-badge-warning">Needs review</span><span class="ds-badge ds-badge-danger">Failed</span><span class="ds-badge ds-badge-neutral">Draft</span></div>
                    </article>
                    <article class="ds-panel">
                        <div class="ds-panel-title"><h3>Feedback</h3><span>Alert / Toast</span></div>
                        <div class="ds-alert ds-alert-info" style="margin-bottom:.5rem"><i class="bi bi-info-circle-fill"></i><div><strong>Heads up.</strong> Data akan diperbarui setelah sinkronisasi berikutnya.</div></div>
                        <div class="ds-alert ds-alert-success"><i class="bi bi-check-circle-fill"></i><div><strong>All good.</strong> Perubahan berhasil disimpan.</div></div>
                    </article>
                </div>
                <div class="ds-grid ds-grid-2" style="margin-top:.85rem">
                    <article class="ds-panel">
                        <div class="ds-panel-title"><h3>Form controls</h3><span>Input / Select / Error</span></div>
                        <div class="ds-control"><label for="ds-name">Workspace name</label><input id="ds-name" type="text" value="Greatfit Operations" placeholder="Masukkan nama workspace"><span class="ds-hint">Nama ini terlihat oleh seluruh tim.</span></div>
                        <div class="ds-grid ds-grid-2"><div class="ds-control"><label for="ds-module">Module</label><select id="ds-module"><option>Marketplace</option><option>Production</option><option>Finance</option></select></div><div class="ds-control"><label for="ds-invalid">Validation state</label><input id="ds-invalid" aria-invalid="true" value="Invalid value"><span class="ds-error">Gunakan format yang benar.</span></div></div>
                    </article>
                    <article class="ds-panel">
                        <div class="ds-panel-title"><h3>Navigation</h3><span>Tabs / Segmented</span></div>
                        <div class="ds-tabs" role="tablist"><button class="ds-tab is-active" type="button" role="tab" aria-selected="true" data-ds-tab="summary">Summary</button><button class="ds-tab" type="button" role="tab" aria-selected="false" data-ds-tab="activity">Activity</button><button class="ds-tab" type="button" role="tab" aria-selected="false" data-ds-tab="settings">Settings</button></div>
                        <div class="ds-tab-panel is-active" data-ds-panel="summary">Summary menyajikan angka penting tanpa mengalihkan fokus pengguna.</div>
                        <div class="ds-tab-panel" data-ds-panel="activity">Activity memperlihatkan perubahan terbaru secara kronologis.</div>
                        <div class="ds-tab-panel" data-ds-panel="settings">Settings menampung preferensi yang jarang diubah.</div>
                    </article>
                </div>
            </section>

            <section class="ds-section" id="patterns">
                <div class="ds-section-head"><div><h2>Patterns</h2><p>Komposisi yang berulang untuk pekerjaan sehari-hari.</p></div><span class="ds-section-count">04 patterns</span></div>
                <div class="ds-grid ds-grid-2">
                    <article class="ds-panel">
                        <div class="ds-panel-title"><h3>Data table</h3><span>Table / Row actions</span></div>
                        <div class="ds-table-wrap"><table class="ds-table"><thead><tr><th>Order</th><th>Channel</th><th>Status</th><th>Total</th></tr></thead><tbody><tr><td><strong>#GF-10284</strong></td><td>Shopee</td><td><span class="ds-badge ds-badge-success">Paid</span></td><td><strong>Rp 1.240.000</strong></td></tr><tr><td><strong>#GF-10283</strong></td><td>Website</td><td><span class="ds-badge ds-badge-warning">Packing</span></td><td><strong>Rp 865.000</strong></td></tr><tr><td><strong>#GF-10282</strong></td><td>TikTok</td><td><span class="ds-badge ds-badge-neutral">Draft</span></td><td><strong>Rp 420.000</strong></td></tr></tbody></table></div>
                    </article>
                    <article class="ds-panel">
                        <div class="ds-panel-title"><h3>Empty & loading</h3><span>System states</span></div>
                        <div class="ds-grid ds-grid-2"><div class="ds-empty"><div><div class="ds-empty-icon"><i class="bi bi-inbox"></i></div><strong>Belum ada data</strong><p>Mulai dengan menambahkan item pertama.</p><button class="ds-btn ds-btn-primary ds-btn-sm" type="button">Add item</button></div></div><div class="ds-empty"><div class="ds-skeleton" aria-label="Loading"><span></span><span></span><span></span></div><p style="margin-bottom:0">Loading state</p></div></div>
                    </article>
                </div>
            </section>

            <section class="ds-section" id="layouts">
                <div class="ds-section-head"><div><h2>Layouts</h2><p>Kerangka halaman untuk menjaga ritme dan hierarchy tetap konsisten.</p></div><span class="ds-section-count">03 templates</span></div>
                <div class="ds-grid ds-grid-3">
                    <article class="ds-panel"><div class="ds-layout-card"><div class="ds-layout-top"></div><div class="ds-layout-body"><div class="ds-layout-side"></div><div class="ds-layout-content"><span></span><span></span><span></span></div></div></div><div class="ds-layout-label">Workspace shell</div><div class="ds-layout-note">Sidebar + top navigation + content canvas untuk modul utama.</div></article>
                    <article class="ds-panel"><div class="ds-layout-card"><div class="ds-layout-top" style="width:65%"></div><div class="ds-layout-body" style="grid-template-columns:1fr"><div class="ds-layout-content"><span></span><span></span><span></span></div></div></div><div class="ds-layout-label">Dashboard overview</div><div class="ds-layout-note">KPI, insight, lalu detail data untuk keputusan cepat.</div></article>
                    <article class="ds-panel"><div class="ds-layout-card"><div class="ds-layout-top" style="width:45%"></div><div class="ds-layout-body" style="grid-template-columns:1fr 38%"><div class="ds-layout-content"><span></span><span></span><span></span></div><div class="ds-layout-side"></div></div></div><div class="ds-layout-label">List + detail</div><div class="ds-layout-note">Master list di kiri, konteks dan action di panel detail.</div></article>
                </div>
                <div class="ds-panel" style="margin-top:.85rem"><div class="ds-panel-title"><h3>Implementation tokens</h3><span>contract</span></div><div class="ds-token-row"><code>--gf-color-brand</code><span class="ds-token-value">#5B5CE2 · primary action</span></div><div class="ds-token-row"><code>--gf-radius-card</code><span class="ds-token-value">16px · cards and panels</span></div><div class="ds-token-row"><code>--gf-space-section</code><span class="ds-token-value">24px · section rhythm</span></div><div class="ds-token-row"><code>--gf-content-max</code><span class="ds-token-value">1380px · workspace canvas</span></div></div>
            </section>
        </main>
    </div>

    <div class="ds-toast" id="ds-toast" role="status" aria-live="polite"><i class="bi bi-check-circle-fill"></i><span></span></div>
@endsection

@push('scripts')
    <script>
        (() => {
            const root = document.querySelector('#design-system');
            if (!root) return;

            const toast = document.querySelector('#ds-toast');
            let toastTimer;
            const showToast = (message) => {
                toast.querySelector('span').textContent = message;
                toast.classList.add('is-visible');
                clearTimeout(toastTimer);
                toastTimer = setTimeout(() => toast.classList.remove('is-visible'), 2600);
            };

            root.querySelectorAll('[data-ds-toast]').forEach((button) => {
                button.addEventListener('click', () => showToast(button.dataset.dsToast));
            });

            const sections = [...root.querySelectorAll('.ds-section, #overview')];
            const navLinks = [...root.querySelectorAll('.ds-nav a')];
            const setActiveNav = () => {
                const current = sections.reduce((active, section) => section.getBoundingClientRect().top <= 150 ? section : active, sections[0]);
                navLinks.forEach((link) => link.classList.toggle('is-active', link.getAttribute('href') === `#${current.id}`));
            };
            window.addEventListener('scroll', setActiveNav, { passive: true });
            navLinks.forEach((link) => link.addEventListener('click', () => navLinks.forEach((item) => item.classList.toggle('is-active', item === link))));

            root.querySelectorAll('[data-ds-tab]').forEach((tab) => {
                tab.addEventListener('click', () => {
                    const target = tab.dataset.dsTab;
                    root.querySelectorAll('[data-ds-tab]').forEach((item) => { item.classList.toggle('is-active', item === tab); item.setAttribute('aria-selected', item === tab ? 'true' : 'false'); });
                    root.querySelectorAll('[data-ds-panel]').forEach((panel) => panel.classList.toggle('is-active', panel.dataset.dsPanel === target));
                });
            });

            document.querySelector('#ds-theme-toggle')?.addEventListener('click', (event) => {
                const isAlt = document.body.classList.toggle('ds-preview');
                event.currentTarget.innerHTML = isAlt ? '<i class="bi bi-sun"></i> Reset canvas' : '<i class="bi bi-moon-stars"></i> Preview mode';
                showToast(isAlt ? 'Preview canvas diaktifkan.' : 'Canvas dikembalikan ke default.');
            });
        })();
    </script>
@endpush
