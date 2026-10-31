<style>
    /* Selaras dengan halaman Shipments: radius kecil, tipografi ringan,
       palet slate netral, badge bertitik, tanpa shadow berat, dukung dark mode. */
    :root {
        --dsh-accent: var(--gf-color-brand-strong, #4142bb);
        --dsh-accent-2: var(--gf-color-content, #101828);
        --dsh-border: color-mix(in srgb, var(--gf-color-line, #e4e7ec) 72%, transparent);
        --dsh-border-strong: color-mix(in srgb, var(--gf-color-line, #e4e7ec) 100%, transparent);
        --dsh-muted: var(--gf-color-muted, #667085);
        --dsh-surface: var(--gf-color-surface, #fff);
        --dsh-surface-muted: var(--gf-color-surface-muted, #f2f4f7);
        --dsh-soft: var(--gf-color-surface-muted, #f2f4f7);
        --dsh-shadow: var(--gf-shadow-sm, 0 1px 2px rgba(16,24,40,.04), 0 4px 12px rgba(16,24,40,.04));
        --dsh-radius: var(--gf-radius-card, 16px);
        --dsh-control-radius: var(--gf-radius-control, 10px);
    }
    body[data-theme="dark"] {
        --dsh-border: var(--gf-color-line, #1f2937);
        --dsh-border-strong: color-mix(in srgb, var(--gf-color-line, #1f2937) 100%, transparent);
        --dsh-muted: var(--gf-color-muted, #9ca3af);
    }

    .dash { display: grid; gap: .9rem; width: 100%; max-width: var(--gf-content-max, var(--app-content-max-width, 1280px)); margin-inline: auto; }
    .dash > * { min-width: 0; }
    .dash-priority-stack { display: grid; gap: .75rem; }

    /* ---------- Header (mirip ship-topbar) ---------- */
    .dash-hero {
        display: flex; align-items: center; justify-content: space-between; gap: 1rem;
        flex-wrap: wrap;
        background: var(--card, #fff);
        border: 1px solid var(--dsh-border);
        border-radius: var(--dsh-radius); padding: 1rem 1.15rem;
        box-shadow: var(--dsh-shadow);
    }
    .dash-hero h1 { font-size: 1.1rem; font-weight: 750; margin: 0; line-height: 1.25; color: var(--text, #0f172a); }
    .dash-hero .sub { color: var(--dsh-muted); font-size: .8rem; margin-top: .2rem; font-weight: 500; }
    .dash-hero .role-chip {
        display: inline-flex; align-items: center; gap: .35rem; margin-top: .5rem;
        border: 1px solid var(--dsh-border-strong); border-radius: 7px; padding: .16rem .5rem;
        font-size: .68rem; font-weight: 600; color: var(--dsh-muted); text-transform: none;
    }
    .dash-hero .hero-date { color: var(--dsh-muted); font-weight: 500; font-size: .78rem; white-space: nowrap; }

    /* ---------- System alert ---------- */
    .dash-alert {
        display: flex; align-items: center; justify-content: space-between; gap: .8rem;
        margin-bottom: .1rem; padding: .75rem .85rem; border: 1px solid transparent;
        border-radius: var(--dsh-control-radius); font-size: .78rem;
        box-shadow: var(--dsh-shadow);
    }
    .dash-alert-danger { color: #991b1b; background: #fff5f5; border-color: #fecaca; }
    .dash-alert-content { display: flex; align-items: flex-start; gap: .6rem; min-width: 0; }
    .dash-alert-content > .bi { flex: none; margin-top: .05rem; font-size: 1.05rem; }
    .dash-alert-content div { display: grid; gap: .12rem; min-width: 0; }
    .dash-alert-content strong { font-weight: 800; }
    .dash-alert-content span { color: inherit; }
    .dash-alert-content small { color: #b91c1c; font-size: .7rem; }
    .dash-alert-content code { color: inherit; font-size: .68rem; }
    body[data-theme="dark"] .dash-alert-danger { color: #fecaca; background: rgba(127,29,29,.28); border-color: rgba(248,113,113,.35); }
    body[data-theme="dark"] .dash-alert-content small { color: #fca5a5; }
    .dash-alert-action {
        display: inline-flex; align-items: center; justify-content: center; gap: .35rem;
        flex: none; min-height: 40px; padding: .45rem .7rem; border: 1px solid #dc2626;
        border-radius: var(--dsh-control-radius); color: #fff; background: #dc2626; font-size: .72rem;
        font-weight: 800; cursor: pointer;
    }
    .dash-alert-action:hover { background: #b91c1c; border-color: #b91c1c; }
    .dash-alert-action:disabled { cursor: wait; opacity: .7; }
    .dash-alert-feedback { color: #991b1b; font-size: .68rem; font-weight: 700; }
    .spin-icon { animation: dash-spin .8s linear infinite; }
    @keyframes dash-spin { to { transform: rotate(360deg); } }
    .dash a:focus-visible, .dash button:focus-visible {
        outline: 2px solid #2563eb; outline-offset: 2px;
    }

    /* ---------- Section title ---------- */
    .dash-sec {
        font-size: 0.95rem; font-weight: 700; text-transform: none; letter-spacing: -0.01em;
        color: var(--text); margin: 1rem 0 .55rem; padding: .25rem 0 .45rem;
        border-bottom: 1px solid var(--dsh-border);
        display: flex; align-items: center; gap: 0.5rem;
        scroll-margin-top: 4rem;
    }
    .dash-sec .bi { font-size: 1.1rem; color: var(--dsh-accent); }

    /* ---------- Marketplace stock alerts ---------- */
    .dash-stock-alert-grid { display: grid; gap: .7rem; grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .dash-stock-alert {
        display: grid; grid-template-columns: auto minmax(0, 1fr) auto auto; align-items: center; gap: .7rem;
        min-width: 0; min-height: 82px; padding: .8rem .9rem; border: 1px solid var(--dsh-border);
        border-radius: var(--dsh-radius); background: var(--dsh-surface, #fff); box-shadow: var(--dsh-shadow);
        color: var(--text, #0f172a); text-decoration: none;
        transition: border-color .18s ease, background .18s ease, transform .18s ease, box-shadow .18s ease;
    }
    a.dash-stock-alert:hover { color: var(--text, #0f172a); transform: translateY(-1px); box-shadow: 0 8px 20px rgba(48,49,125,.1); }
    .dash-stock-alert-warning { border-color: color-mix(in srgb, #f59e0b 35%, var(--dsh-border)); }
    .dash-stock-alert-danger { border-color: color-mix(in srgb, #ef4444 35%, var(--dsh-border)); }
    .dash-stock-alert-icon { display: grid; place-items: center; width: 34px; height: 34px; flex: none; border-radius: 10px; font-size: 1rem; }
    .dash-stock-alert-warning .dash-stock-alert-icon { color: #b45309; background: rgba(245,158,11,.14); }
    .dash-stock-alert-danger .dash-stock-alert-icon { color: #dc2626; background: rgba(239,68,68,.12); }
    .dash-stock-alert-copy { display: grid; gap: .18rem; min-width: 0; }
    .dash-stock-alert-copy strong { min-width: 0; color: var(--text, #0f172a); font-size: .8rem; line-height: 1.25; }
    .dash-stock-alert-copy small { color: var(--dsh-muted); font-size: .68rem; line-height: 1.3; }
    .dash-stock-alert-value { color: var(--text, #0f172a); font-size: 1.55rem; font-weight: 850; line-height: 1; font-variant-numeric: tabular-nums; }
    .dash-stock-alert-warning .dash-stock-alert-value { color: #b45309; }
    .dash-stock-alert-danger .dash-stock-alert-value { color: #dc2626; }
    .dash-stock-alert-arrow { color: var(--dsh-muted); font-size: .8rem; }
    body[data-theme="dark"] a.dash-stock-alert:hover { color: var(--text, #f8fafc); }
    body[data-theme="dark"] .dash-stock-alert-copy strong { color: var(--text, #f8fafc); }
    body[data-theme="dark"] .dash-stock-alert-warning .dash-stock-alert-value { color: #fcd34d; }
    body[data-theme="dark"] .dash-stock-alert-danger .dash-stock-alert-value { color: #fca5a5; }

    /* ---------- KPI grid ---------- */
    .dash-grid { display: grid; gap: .7rem; align-items: stretch; grid-template-columns: repeat(auto-fit, minmax(min(100%, 230px), 1fr)); }
    .kpi {
        display: flex; flex-direction: column; height: 100%; text-decoration: none; color: inherit;
        background: var(--dsh-surface, #fff); border: 1px solid var(--dsh-border); border-radius: var(--dsh-radius);
        padding: 1rem 1.05rem; position: relative; min-width: 0; min-height: 170px; overflow: hidden;
        box-shadow: var(--dsh-shadow);
        transition: border-color .18s ease, background .18s ease, transform .18s ease, box-shadow .18s ease;
    }
    .kpi::after { content: ''; position: absolute; width: 92px; height: 92px; top: -42px; right: -34px; border-radius: 50%; background: color-mix(in srgb, var(--gf-color-brand, #5b5ce2) 8%, transparent); pointer-events: none; }
    .kpi-featured {
        border-color: color-mix(in srgb, var(--gf-color-brand, #5b5ce2) 38%, var(--dsh-border));
        background: linear-gradient(145deg, color-mix(in srgb, var(--gf-color-brand, #5b5ce2) 9%, var(--dsh-surface, #fff)), var(--dsh-surface, #fff) 68%);
    }
    .kpi-featured .kpi-value { font-size: 1.65rem; letter-spacing: -.055em; }
    .kpi-benchmark { background: linear-gradient(145deg, color-mix(in srgb, var(--dsh-surface-muted, #f2f4f7) 72%, var(--dsh-surface, #fff)), var(--dsh-surface, #fff) 72%); }
    body[data-theme="dark"] .kpi-featured { border-color: color-mix(in srgb, var(--gf-color-brand, #5b5ce2) 55%, var(--dsh-border)); background: linear-gradient(145deg, color-mix(in srgb, var(--gf-color-brand, #5b5ce2) 22%, var(--dsh-surface, #0f172a)), var(--dsh-surface, #0f172a) 72%); }
    body[data-theme="dark"] .kpi-benchmark { background: linear-gradient(145deg, color-mix(in srgb, var(--dsh-surface-muted, #1f2937) 72%, var(--dsh-surface, #0f172a)), var(--dsh-surface, #0f172a) 72%); }
    a.kpi:hover { border-color: color-mix(in srgb, var(--gf-color-brand, #5b5ce2) 38%, var(--dsh-border-strong)); background: var(--dsh-surface, #fff); color: inherit; transform: translateY(-1px); box-shadow: 0 8px 20px rgba(48,49,125,.1); }
    .kpi-label { color: var(--dsh-muted); font-size: .7rem; font-weight: 750; display: flex; align-items: center; gap: .48rem; min-width: 0; min-height: 2.5em; line-height: 1.25; overflow-wrap: anywhere; }
    .kpi-label-text { min-width: 0; }
    .kpi-label .ico {
        width: 28px; height: 28px; flex: none; border-radius: 9px; display: grid; place-items: center;
        font-size: .82rem; background: color-mix(in srgb, var(--gf-color-brand, #5b5ce2) 10%, transparent); color: var(--dsh-accent);
    }
    body[data-theme="dark"] .kpi-label .ico { color: #cbd5e1; }
    .kpi.green  .ico { background: rgba(34,197,94,.12);  color: #16a34a; }
    .kpi.blue   .ico { background: rgba(59,130,246,.12); color: #2563eb; }
    .kpi.amber  .ico { background: rgba(245,158,11,.14); color: #b45309; }
    .kpi.red    .ico { background: rgba(239,68,68,.12);  color: #dc2626; }
    .kpi.violet .ico { background: rgba(139,92,246,.14); color: #7c3aed; }
    .kpi-value { display: flex; align-items: center; min-height: 1.6em; font-size: 1.65rem; font-weight: 850; color: var(--gf-color-content, var(--text, #0f172a)); line-height: 1.05; margin-top: .65rem; letter-spacing: -.06em; font-variant-numeric: tabular-nums; overflow-wrap: anywhere; }
    .kpi-value.sm { font-size: 1.18rem; letter-spacing: -.04em; }
    .kpi-sub { color: var(--dsh-muted); font-size: .69rem; font-weight: 500; margin-top: .3rem; min-height: 2.6em; line-height: 1.35; overflow-wrap: anywhere; }
    .kpi-comparisons { display: grid; gap: .35rem; margin-top: auto; padding-top: .6rem; border-top: 1px solid var(--dsh-border); min-width: 0; }
    .kpi-compare { display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 48%); align-items: start; gap: .5rem; color: var(--dsh-muted); font-size: .66rem; font-weight: 600; min-width: 0; line-height: 1.25; }
    .kpi-compare > span { min-width: 0; overflow-wrap: anywhere; }
    .kpi-compare strong { display: flex; flex-direction: column; align-items: flex-end; min-width: 0; max-width: 100%; color: var(--gf-color-content, var(--text, #0f172a)); font-weight: 800; text-align: right; white-space: normal; overflow-wrap: anywhere; line-height: 1.2; font-variant-numeric: tabular-nums; }
    .kpi-compare-delta { display: block; margin-top: .1rem; font-size: .61rem; font-weight: 800; }
    .kpi-compare-delta.up { color: #16a34a; }
    .kpi-compare-delta.down { color: #dc2626; }
    .kpi-compare-delta.muted { color: var(--dsh-muted); }
    body[data-theme="dark"] .kpi-compare-delta.up { color: #86efac; }
    body[data-theme="dark"] .kpi-compare-delta.down { color: #fca5a5; }
    .kpi-compare.up strong { color: #16a34a; }
    .kpi-compare.down strong { color: #dc2626; }
    .kpi-compare.muted strong { color: var(--dsh-muted); }
    body[data-theme="dark"] .kpi-compare.up strong { color: #86efac; }
    body[data-theme="dark"] .kpi-compare.down strong { color: #fca5a5; }
    .kpi-cta { color: var(--dsh-accent); font-size: .69rem; font-weight: 750; margin-top: .5rem; display: inline-flex; align-items: center; gap: .15rem; }
    body[data-theme="dark"] .kpi-cta { color: #93c5fd; }
    .kpi.red .kpi-value { color: #dc2626; }
    .kpi.amber .kpi-value { color: #b45309; }
    body[data-theme="dark"] .kpi.red .kpi-value { color: #fca5a5; }
    body[data-theme="dark"] .kpi.amber .kpi-value { color: #fcd34d; }

    /* ---------- Action buttons ---------- */
    .dash-actions { display: grid; gap: .5rem; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); }
    .act {
        display: flex; align-items: center; gap: .6rem; text-decoration: none;
        background: var(--dsh-surface, #fff); border: 1px solid var(--dsh-border); border-radius: var(--dsh-control-radius);
        padding: .6rem .7rem; color: var(--text, #0f172a); font-weight: 600; font-size: .86rem;
        box-shadow: var(--dsh-shadow); transition: border-color .18s ease, background .18s ease, transform .18s ease;
    }
    .act:hover { border-color: color-mix(in srgb, var(--gf-color-brand, #5b5ce2) 38%, var(--dsh-border-strong)); background: var(--dsh-surface, #fff); color: var(--text, #0f172a); transform: translateY(-1px); }
    .act .ico {
        width: 34px; height: 34px; flex: none; border-radius: 7px;
        display: grid; place-items: center; font-size: 1rem; background: rgba(148,163,184,.14); color: var(--dsh-accent);
    }
    body[data-theme="dark"] .act .ico { color: #cbd5e1; }
    .act .ico.green  { background: rgba(34,197,94,.12);  color: #16a34a; }
    .act .ico.blue   { background: rgba(59,130,246,.12); color: #2563eb; }
    .act .ico.amber  { background: rgba(245,158,11,.14); color: #b45309; }
    .act .ico.violet { background: rgba(139,92,246,.14); color: #7c3aed; }
    .act .ico.red    { background: rgba(239,68,68,.12);  color: #dc2626; }
    .act .ico.slate  { background: rgba(148,163,184,.16); color: var(--dsh-accent); }
    .act .ico.indigo { background: #e0e7ff; color: #4f46e5; }
    body[data-theme="dark"] .act .ico.indigo { background: rgba(79,70,229,.28); color: #c7d2fe; }
    .act .t small { display: block; color: var(--dsh-muted); font-size: .7rem; font-weight: 500; margin-top: .05rem; }

    /* ---------- List panel (mirip card-main) ---------- */
    .dash-panels { display: grid; gap: .8rem; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); }
    .dash-ads-grid { grid-template-columns: minmax(240px, 1fr) minmax(0, 2fr); gap: 1rem; }
    .dpanel { background: var(--dsh-surface, #fff); border: 1px solid var(--dsh-border); border-radius: var(--dsh-radius); box-shadow: var(--dsh-shadow); overflow: hidden; }
    .dpanel-head { display: flex; align-items: center; justify-content: space-between;
        min-height: 52px; padding: .55rem 1rem; border-bottom: 1px solid var(--dsh-border); }
    .dpanel-head .t { font-weight: 700; color: var(--text, #0f172a); font-size: .88rem; display: flex; align-items: center; gap: .4rem; }
    .dpanel-head .t .bi { color: var(--dsh-muted); font-size: .85rem; }
    .dpanel-head a { display: inline-flex; align-items: center; min-height: 40px; font-size: .74rem; font-weight: 600; color: var(--dsh-accent); text-decoration: none; white-space: nowrap; }
    body[data-theme="dark"] .dpanel-head a { color: #93c5fd; }
    .dpanel-head a:hover { text-decoration: underline; }
    .dpanel-body { padding: .2rem .3rem; }
    .dash-chart-card { padding: .85rem; }
    .dash-chart-card-title { color: var(--text); font-size: .85rem; font-weight: 700; margin-bottom: .35rem; }
    .dash-chart-card-subtitle { color: var(--dsh-muted); font-size: .72rem; line-height: 1.4; margin-bottom: .8rem; }
    .dash-chart-wrap { position: relative; width: 100%; }
    .dash-chart-wrap-pie { height: 250px; display: flex; align-items: center; justify-content: center; }
    .dash-chart-wrap-heatmap { height: 250px; }
    .dash-chart-empty { color: var(--dsh-muted); font-size: .8rem; text-align: center; }
    .drow { display: flex; align-items: center; gap: .6rem; padding: .5rem .6rem; }
    .drow + .drow { border-top: 1px solid var(--dsh-border); }
    .drow:hover { background: rgba(148,163,184,.05); }
    .drow .main { min-width: 0; flex: 1; }
    .drow .name { font-weight: 650; color: var(--text, #0f172a); font-size: .84rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .drow .meta { color: var(--dsh-muted); font-size: .72rem; font-weight: 500; margin-top: .1rem; }
    .drow .val { font-weight: 650; font-size: .82rem; white-space: nowrap; color: var(--text, #0f172a); }
    .drow .val.red { color: #dc2626; }
    .drow-actions { flex: none; min-width: 0; text-align: right; }
    body[data-theme="dark"] .drow .val.red { color: #fca5a5; }
    .order-products { display: grid; gap: .35rem; margin-top: .45rem; }
    .order-product { display: flex; align-items: center; gap: .55rem; padding: .35rem .45rem; border: 1px solid var(--dsh-border); border-radius: 6px; background: rgba(148,163,184,.04); }
    .order-product-main { min-width: 0; flex: 1; }
    .order-product-name { color: var(--text, #0f172a); font-size: .76rem; font-weight: 650; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .order-product .meta { font-size: .66rem; }
    .order-stock { display: grid; gap: .05rem; min-width: 70px; text-align: right; color: var(--dsh-muted); font-size: .61rem; line-height: 1.1; }
    .order-stock strong { color: #166534; font-size: .77rem; }
    .order-stock.short strong { color: #dc2626; }
    body[data-theme="dark"] .order-stock strong { color: #bbf7d0; }
    body[data-theme="dark"] .order-stock.short strong { color: #fca5a5; }
    .order-items-empty { margin-top: .35rem; font-style: italic; }
    .ditem-table-wrap { overflow-x: auto; }
    .ditem-table { width: 100%; border-collapse: collapse; min-width: 520px; }
    .ditem-table th { padding: .45rem .55rem; color: var(--dsh-muted); font-size: .65rem; font-weight: 700; text-align: left; border-bottom: 1px solid var(--dsh-border); white-space: nowrap; }
    .ditem-table td { padding: .52rem .55rem; border-bottom: 1px solid var(--dsh-border); vertical-align: middle; }
    .ditem-table tr:last-child td { border-bottom: 0; }
    .ditem-table .numeric { text-align: right; white-space: nowrap; }
    .ditem-summary { display: grid; grid-template-columns: repeat(5, minmax(0, 1fr)); gap: .45rem; margin-bottom: .6rem; }
    .ditem-summary-item { display: flex; align-items: center; justify-content: space-between; gap: .5rem; padding: .45rem .55rem; border: 1px solid var(--dsh-border); border-radius: var(--dsh-control-radius); color: var(--dsh-muted); background: var(--dsh-surface, #fff); font-size: .68rem; }
    .ditem-summary-item span { display: inline-flex; align-items: center; gap: .25rem; }
    .ditem-summary-item strong { color: var(--text, #0f172a); font-size: .84rem; }
    .ditem-summary-gap.safe { color: #166534 !important; }
    .ditem-summary-gap.short { color: #b91c1c !important; }
    .ditem-summary-gap.unknown { color: #64748b !important; }
    .ditem-category-row td { padding: .48rem .55rem .28rem; color: var(--dsh-accent); background: var(--dsh-soft, #f8fafc); border-bottom: 1px solid var(--dsh-border); font-size: .66rem; font-weight: 800; letter-spacing: .04em; text-transform: uppercase; }
    .ditem-category-name { color: var(--dsh-accent); font-weight: 800; }
    .ditem-category-name .bi { margin-right: .25rem; }
    .ditem-category-total { color: var(--dsh-muted); font-weight: 650; }
    .ditem-category-row .ditem-stock { color: var(--dsh-muted); }
    .ditem-category-gap-value { font-weight: 700; }
    .ditem-category-gap-value.safe { color: #166534; }
    .ditem-category-gap-value.short { color: #b91c1c; }
    .ditem-category-gap-value.unknown { color: #64748b; }
    .ditem-number { color: var(--dsh-muted); font-size: .74rem; font-weight: 650; width: 32px; }
    .ditem-code { color: var(--text, #0f172a); font-size: .8rem; font-weight: 700; font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; }
    .ditem-table .meta { font-size: .65rem; }
    .ditem-demand { color: var(--text, #0f172a); font-size: .78rem; font-weight: 650; }
    .ditem-stock { color: #166534; font-size: .78rem; font-weight: 700; }
    .ditem-stock.short { color: #dc2626; }
    .ditem-production-stock { color: var(--dsh-muted); font-size: .78rem; font-weight: 700; }
    .ditem-wip-breakdown { margin-top: .18rem; color: var(--dsh-muted); font-size: .56rem; font-weight: 600; line-height: 1.25; white-space: nowrap; }
    .ditem-production-indicator { display: inline-flex; align-items: center; gap: .32rem; padding: .22rem .45rem; border-radius: 999px; color: #9a3412; background: #ffedd5; font-size: .78rem; font-weight: 800; line-height: 1; }
    .ditem-production-icon { font-size: .82rem; }
    .ditem-gap { width: 112px; }
    .ditem-gap-indicator { display: inline-flex; align-items: center; justify-content: flex-end; gap: .32rem; min-width: 72px; padding: .22rem .45rem; border-radius: 999px; font-size: .78rem; font-weight: 800; line-height: 1; }
    .ditem-gap-indicator.safe { color: #166534; background: #dcfce7; }
    .ditem-gap-indicator.short { color: #b91c1c; background: #fee2e2; }
    .ditem-gap-indicator.unknown { color: #64748b; background: #f1f5f9; }
    .ditem-gap-icon { font-size: .82rem; }
    body[data-theme="dark"] .ditem-stock { color: #bbf7d0; }
    body[data-theme="dark"] .ditem-stock.short { color: #fca5a5; }
    body[data-theme="dark"] .ditem-production-stock { color: #cbd5e1; }
    body[data-theme="dark"] .ditem-production-indicator { color: #fed7aa; background: rgba(154, 52, 18, .38); }
    body[data-theme="dark"] .ditem-gap-indicator.safe { color: #bbf7d0; background: rgba(22, 101, 52, .35); }
    body[data-theme="dark"] .ditem-gap-indicator.short { color: #fca5a5; background: rgba(185, 28, 28, .35); }
    body[data-theme="dark"] .ditem-gap-indicator.unknown { color: #cbd5e1; background: rgba(100, 116, 139, .3); }
    body[data-theme="dark"] .ditem-category-row td { background: rgba(59, 130, 246, .1); }
    body[data-theme="dark"] .ditem-summary-item strong { color: #e2e8f0; }
    body[data-theme="dark"] .ditem-summary-gap.safe, body[data-theme="dark"] .ditem-category-gap-value.safe { color: #bbf7d0 !important; }
    body[data-theme="dark"] .ditem-summary-gap.short, body[data-theme="dark"] .ditem-category-gap-value.short { color: #fca5a5 !important; }
    body[data-theme="dark"] .ditem-summary-gap.unknown, body[data-theme="dark"] .ditem-category-gap-value.unknown { color: #cbd5e1 !important; }
    @media (max-width: 720px) {
        .ditem-summary { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    }
    .drepeat-table-wrap { width: 100%; max-width: 100%; overflow-x: hidden; }
    .drepeat-panel { grid-column: 1 / -1; width: 100%; min-width: 0; border-color: color-mix(in srgb, var(--gf-color-brand, #5b5ce2) 28%, var(--dsh-border)); }
    .drepeat-panel .dpanel-head { background: linear-gradient(90deg, color-mix(in srgb, var(--gf-color-brand, #5b5ce2) 8%, var(--dsh-surface, #fff)), var(--dsh-surface-muted, #f2f4f7)); border-bottom-color: color-mix(in srgb, var(--gf-color-brand, #5b5ce2) 18%, var(--dsh-border)); }
    .drepeat-panel .dpanel-head .t { color: var(--dsh-accent); }
    .drepeat-panel .dpanel-head .t .bi { color: var(--gf-color-brand, #5b5ce2); }
    .drepeat-panel .dpanel-body { padding-left: .08rem; padding-right: .15rem; }
    .drepeat-table { display: table; width: 100%; min-width: 100%; max-width: 100%; box-sizing: border-box; border-collapse: collapse; table-layout: fixed; }
    .drepeat-table th:nth-child(1), .drepeat-table td:nth-child(1) { width: 4%; }
    .drepeat-table th:nth-child(2), .drepeat-table td:nth-child(2) { width: 10%; }
    .drepeat-table th:nth-child(3), .drepeat-table td:nth-child(3) { width: 12%; }
    .drepeat-table th:nth-child(4), .drepeat-table td:nth-child(4) { width: 13%; }
    .drepeat-table th:nth-child(5), .drepeat-table td:nth-child(5) { width: 6%; }
    .drepeat-table th:nth-child(6), .drepeat-table td:nth-child(6) { width: 17%; }
    .drepeat-table th:nth-child(7), .drepeat-table td:nth-child(7) { width: 13%; }
    .drepeat-table th:nth-child(8), .drepeat-table td:nth-child(8) { width: 6%; }
    .drepeat-table th:nth-child(9), .drepeat-table td:nth-child(9) { width: 10%; }
    .drepeat-table th:nth-child(10), .drepeat-table td:nth-child(10) { width: 9%; }
    .drepeat-table thead { background: rgba(239,246,255,.58); }
    .drepeat-table th { padding: .35rem .4rem; color: #475569; font-size: .62rem; font-weight: 750; text-align: left; border-bottom: 1px solid rgba(59,130,246,.18); white-space: normal; }
    .drepeat-table td { padding: .4rem .4rem; border-bottom: 1px solid var(--dsh-border); vertical-align: middle; font-size: .7rem; overflow-wrap: anywhere; }
    .drepeat-table th:first-child, .drepeat-table td:first-child { padding-left: .2rem; }
    .drepeat-table tbody tr { transition: background-color .12s ease; }
    .drepeat-table tbody tr:hover { background: rgba(239,246,255,.62); }
    .drepeat-table tr:last-child td { border-bottom: 0; }
    .drepeat-table .numeric { text-align: right; white-space: nowrap; }
    .drepeat-buyer { color: var(--text, #0f172a); font-weight: 650; }
    .drepeat-order { color: var(--dsh-muted); font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; font-size: .69rem !important; }
    .drepeat-order-link { color: var(--dsh-accent); text-decoration: none; }
    .drepeat-order-link:hover { text-decoration: underline; }
    .drepeat-order-meta { margin-top: .18rem; color: var(--dsh-muted); font-family: inherit; font-size: .58rem; white-space: normal; }
    .drepeat-order-meta code { color: var(--text, #0f172a); font-family: inherit; font-size: inherit; }
    .drepeat-items { color: var(--dsh-muted); font-size: .7rem !important; }
    .drepeat-items code { color: var(--text, #0f172a); font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; font-size: .69rem; }
    .drepeat-item-line { line-height: 1.3; white-space: normal; overflow-wrap: anywhere; }
    .drepeat-date { color: var(--dsh-muted); white-space: nowrap; font-size: .72rem !important; }
    body[data-theme="dark"] .drepeat-panel { border-color: color-mix(in srgb, var(--gf-color-brand, #5b5ce2) 52%, var(--dsh-border)); }
    body[data-theme="dark"] .drepeat-panel .dpanel-head { background: linear-gradient(90deg, color-mix(in srgb, var(--gf-color-brand, #5b5ce2) 22%, var(--dsh-surface, #0f172a)), var(--dsh-surface, #0f172a)); border-bottom-color: color-mix(in srgb, var(--gf-color-brand, #5b5ce2) 30%, var(--dsh-border)); }
    body[data-theme="dark"] .drepeat-panel .dpanel-head .t { color: var(--gf-color-brand, #bfdbfe); }
    body[data-theme="dark"] .drepeat-panel .dpanel-head .t .bi { color: var(--gf-color-accent, #93c5fd); }
    body[data-theme="dark"] .drepeat-table thead { background: rgba(30,58,138,.2); }
    body[data-theme="dark"] .drepeat-table th { color: #cbd5e1; border-bottom-color: rgba(96,165,250,.3); }
    body[data-theme="dark"] .drepeat-table tbody tr:hover { background: rgba(30,58,138,.18); }

    /* ---------- Badge (mirip badge-status + dot) ---------- */
    .pill { display: inline-flex; align-items: center; gap: .32rem; border-radius: 7px; padding: .14rem .45rem;
        font-size: .66rem; font-weight: 600; text-transform: none; letter-spacing: 0;
        border: 1px solid transparent; white-space: nowrap; margin-top: .25rem; }
    .pill::before { content: ''; width: 6px; height: 6px; border-radius: 999px; display: inline-block; background: currentColor; opacity: .9; }
    .pill.slate { background: rgba(148,163,184,.10); color: #475569; border-color: rgba(148,163,184,.30); }
    .pill.amber { background: rgba(245,158,11,.10); color: #b45309; border-color: rgba(245,158,11,.30); }
    .pill.blue  { background: rgba(59,130,246,.10); color: #1d4ed8; border-color: rgba(59,130,246,.30); }
    .pill.green { background: rgba(34,197,94,.10);  color: #166534; border-color: rgba(34,197,94,.30); }
    body[data-theme="dark"] .pill.amber { color: #fcd34d; border-color: rgba(245,158,11,.5); }
    body[data-theme="dark"] .pill.blue  { color: #bfdbfe; border-color: rgba(59,130,246,.5); }
    body[data-theme="dark"] .pill.green { color: #bbf7d0; border-color: rgba(34,197,94,.5); }
    body[data-theme="dark"] .pill.slate { color: #cbd5e1; }

    .dash-empty { text-align: center; color: var(--dsh-muted); font-weight: 500; padding: 1.5rem; font-size: .82rem; }
    .dash-empty-spaced { margin-top: 1rem; }
    .dash-empty .bi { display: block; font-size: 1.4rem; opacity: .5; margin-bottom: .25rem; }

    @media (max-width: 640px) {
        .dash { gap: .7rem; }
        .dash-hero { padding: .75rem .85rem; } .dash-hero h1 { font-size: 1rem; }
        .hero-date { display: none; }
        .kpi { min-height: 164px; }
        .kpi-value { font-size: 1.25rem; }
        .dash-sec { font-size: .88rem; margin-top: .8rem; }
        .dash-ads-grid { grid-template-columns: 1fr; gap: .7rem; }
        .dash-stock-alert-grid { grid-template-columns: 1fr; }
        .dash-stock-alert { min-height: 76px; padding: .7rem .75rem; }
        .dash-chart-card { padding: .75rem; }
        .dash-chart-wrap-pie, .dash-chart-wrap-heatmap { height: 210px; }

        .dash-alert { align-items: stretch; flex-direction: column; padding: .7rem .75rem; }
        .dash-alert-action { width: 100%; }
        .dash-alert-feedback { min-height: 1em; }

        .ditem-table-wrap, .drepeat-table-wrap { overflow-x: hidden; }
        .ditem-table { min-width: 0; table-layout: fixed; }
        .ditem-table th, .ditem-table td { padding: .34rem .3rem; font-size: .66rem; }
        .ditem-table th:nth-child(1), .ditem-table td:nth-child(1) { width: 8%; }
        .ditem-table th:nth-child(2), .ditem-table td:nth-child(2) { width: 47%; }
        .ditem-table th:nth-child(3), .ditem-table td:nth-child(3) { width: 23%; }
        .ditem-table th:nth-child(7), .ditem-table td:nth-child(7) { width: 22%; }
        .ditem-table .ditem-number { width: 24px; }
        .ditem-code { font-size: .68rem; overflow-wrap: anywhere; }
        .ditem-gap { width: auto; }
        .ditem-gap-indicator { min-width: 0; padding: .18rem .3rem; font-size: .68rem; }
        .ditem-gap-icon { font-size: .72rem; }
        .ditem-mobile-secondary { display: none !important; }
        .ditem-category-row td:nth-child(2) { padding-left: .3rem; }
        .ditem-summary { gap: .3rem; }
        .ditem-summary-item { padding: .38rem .4rem; font-size: .62rem; }
        .ditem-summary-item strong { font-size: .76rem; }

        .drepeat-table th, .drepeat-table td { padding: .32rem .26rem; font-size: .64rem; }
        .drepeat-table th:nth-child(1), .drepeat-table td:nth-child(1) { width: 6%; }
        .drepeat-table th:nth-child(2), .drepeat-table td:nth-child(2) { width: 17%; }
        .drepeat-table th:nth-child(3), .drepeat-table td:nth-child(3) { width: 18%; }
        .drepeat-table th:nth-child(4), .drepeat-table td:nth-child(4) { width: 22%; }
        .drepeat-table th:nth-child(5), .drepeat-table td:nth-child(5) { width: 9%; }
        .drepeat-table th:nth-child(6), .drepeat-table td:nth-child(6) { width: 19%; }
        .drepeat-table th:nth-child(10), .drepeat-table td:nth-child(10) { width: 9%; }
        .drepeat-table .ditem-number { width: 22px; }
        .drepeat-order { font-size: .6rem !important; }
        .drepeat-items { font-size: .62rem !important; }
        .drepeat-items code { font-size: .6rem; }
        .drepeat-buyer { overflow-wrap: anywhere; }
        .drepeat-order-meta { font-size: .5rem; }
        .drepeat-mobile-secondary { display: none !important; }
        .drepeat-panel .dpanel-head { padding: .55rem .65rem; }
        .drepeat-panel .dpanel-head .t { font-size: .78rem; }
    }

    @media (max-width: 380px) {
        .ditem-summary-item { flex-direction: column; align-items: flex-start; gap: .15rem; }
        .ditem-summary-item strong { align-self: flex-end; }
        .drepeat-table th, .drepeat-table td { padding-inline: .2rem; }
    }

    @media (prefers-reduced-motion: reduce) {
        .dash *, .dash *::before, .dash *::after {
            animation-duration: .01ms !important;
            animation-iteration-count: 1 !important;
            scroll-behavior: auto !important;
            transition-duration: .01ms !important;
        }
    }
</style>
