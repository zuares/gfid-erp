<style>
    /* Selaras dengan halaman Shipments: radius kecil, tipografi ringan,
       palet slate netral, badge bertitik, tanpa shadow berat, dukung dark mode. */
    :root {
        --dsh-accent: #334155;
        --dsh-accent-2: #1f2937;
        --dsh-border: rgba(148,163,184,.18);
        --dsh-border-strong: rgba(148,163,184,.32);
        --dsh-muted: #64748b;
    }
    body[data-theme="dark"] {
        --dsh-border: rgba(51,65,85,.85);
        --dsh-border-strong: rgba(51,65,85,1);
        --dsh-muted: #9ca3af;
    }

    .dash { display: grid; gap: .9rem; max-width: var(--app-content-max-width, 1100px); margin-inline: auto; }

    /* ---------- Header (mirip ship-topbar) ---------- */
    .dash-hero {
        display: flex; align-items: center; justify-content: space-between; gap: 1rem;
        flex-wrap: wrap;
        background: var(--card, #fff);
        border: 1px solid var(--dsh-border);
        border-radius: 8px; padding: .85rem 1rem;
    }
    .dash-hero h1 { font-size: 1.1rem; font-weight: 750; margin: 0; line-height: 1.25; color: var(--text, #0f172a); }
    .dash-hero .sub { color: var(--dsh-muted); font-size: .8rem; margin-top: .2rem; font-weight: 500; }
    .dash-hero .role-chip {
        display: inline-flex; align-items: center; gap: .35rem; margin-top: .5rem;
        border: 1px solid var(--dsh-border-strong); border-radius: 7px; padding: .16rem .5rem;
        font-size: .68rem; font-weight: 600; color: var(--dsh-muted); text-transform: none;
    }
    .dash-hero .hero-date { color: var(--dsh-muted); font-weight: 500; font-size: .78rem; white-space: nowrap; }

    /* ---------- Section title ---------- */
    .dash-sec {
        font-size: 0.95rem; font-weight: 700; text-transform: none; letter-spacing: -0.01em;
        color: var(--text); margin: 1.5rem 0 1rem; padding-bottom: 0.5rem;
        border-bottom: 1px solid var(--dsh-border);
        display: flex; align-items: center; gap: 0.5rem;
    }
    .dash-sec .bi { font-size: 1.1rem; color: var(--dsh-accent); }

    /* ---------- KPI grid ---------- */
    .dash-grid { display: grid; gap: .6rem; align-items: stretch; grid-template-columns: repeat(auto-fit, minmax(min(100%, 220px), 1fr)); }
    .kpi {
        display: flex; flex-direction: column; height: 100%; text-decoration: none; color: inherit;
        background: var(--card, #fff); border: 1px solid var(--dsh-border); border-radius: 8px;
        padding: .7rem .8rem; position: relative; min-width: 0; min-height: 176px; overflow: hidden;
        transition: border-color .12s ease, background .12s ease;
    }
    .kpi-featured {
        border-color: rgba(37,99,235,.38);
        background: linear-gradient(145deg, rgba(239,246,255,.92), var(--card, #fff) 68%);
    }
    .kpi-featured .kpi-value { font-size: 1.55rem; letter-spacing: -.025em; }
    .kpi-benchmark { background: linear-gradient(145deg, rgba(248,250,252,.9), var(--card, #fff) 72%); }
    body[data-theme="dark"] .kpi-featured { border-color: rgba(96,165,250,.5); background: linear-gradient(145deg, rgba(30,58,138,.28), var(--card, #0f172a) 72%); }
    body[data-theme="dark"] .kpi-benchmark { background: linear-gradient(145deg, rgba(30,41,59,.75), var(--card, #0f172a) 72%); }
    a.kpi:hover { border-color: var(--dsh-border-strong); background: rgba(148,163,184,.05); color: inherit; }
    .kpi-label { color: var(--dsh-muted); font-size: .72rem; font-weight: 600; display: flex; align-items: flex-start; gap: .4rem; min-width: 0; min-height: 2.5em; line-height: 1.25; overflow-wrap: anywhere; }
    .kpi-label .ico {
        width: 22px; height: 22px; flex: none; border-radius: 6px; display: grid; place-items: center;
        font-size: .8rem; background: rgba(148,163,184,.14); color: var(--dsh-accent);
    }
    body[data-theme="dark"] .kpi-label .ico { color: #cbd5e1; }
    .kpi.green  .ico { background: rgba(34,197,94,.12);  color: #16a34a; }
    .kpi.blue   .ico { background: rgba(59,130,246,.12); color: #2563eb; }
    .kpi.amber  .ico { background: rgba(245,158,11,.14); color: #b45309; }
    .kpi.red    .ico { background: rgba(239,68,68,.12);  color: #dc2626; }
    .kpi.violet .ico { background: rgba(139,92,246,.14); color: #7c3aed; }
    .kpi-value { display: flex; align-items: center; min-height: 1.6em; font-size: 1.4rem; font-weight: 700; color: var(--text, #0f172a); line-height: 1.15; margin-top: .35rem; overflow-wrap: anywhere; }
    .kpi-value.sm { font-size: 1.1rem; }
    .kpi-sub { color: var(--dsh-muted); font-size: .72rem; font-weight: 500; margin-top: .2rem; min-height: 2.6em; line-height: 1.3; overflow-wrap: anywhere; }
    .kpi-comparisons { display: grid; gap: .3rem; margin-top: auto; padding-top: .42rem; border-top: 1px dashed var(--dsh-border); min-width: 0; }
    .kpi-compare { display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 48%); align-items: start; gap: .5rem; color: var(--dsh-muted); font-size: .66rem; font-weight: 600; min-width: 0; line-height: 1.25; }
    .kpi-compare > span { min-width: 0; overflow-wrap: anywhere; }
    .kpi-compare strong { display: flex; flex-direction: column; align-items: flex-end; min-width: 0; max-width: 100%; font-weight: 750; text-align: right; white-space: normal; overflow-wrap: anywhere; line-height: 1.2; }
    .kpi-compare-delta { display: block; margin-top: .1rem; font-size: .62rem; font-weight: 800; }
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
    .kpi-cta { color: var(--dsh-accent); font-size: .72rem; font-weight: 650; margin-top: .4rem; display: inline-flex; align-items: center; gap: .15rem; }
    body[data-theme="dark"] .kpi-cta { color: #93c5fd; }
    .kpi.red .kpi-value { color: #dc2626; }
    .kpi.amber .kpi-value { color: #b45309; }
    body[data-theme="dark"] .kpi.red .kpi-value { color: #fca5a5; }
    body[data-theme="dark"] .kpi.amber .kpi-value { color: #fcd34d; }

    /* ---------- Action buttons ---------- */
    .dash-actions { display: grid; gap: .5rem; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); }
    .act {
        display: flex; align-items: center; gap: .6rem; text-decoration: none;
        background: var(--card, #fff); border: 1px solid var(--dsh-border); border-radius: 8px;
        padding: .6rem .7rem; color: var(--text, #0f172a); font-weight: 600; font-size: .86rem;
        transition: border-color .12s ease, background .12s ease;
    }
    .act:hover { border-color: var(--dsh-border-strong); background: rgba(148,163,184,.05); color: var(--text, #0f172a); }
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
    .act .t small { display: block; color: var(--dsh-muted); font-size: .7rem; font-weight: 500; margin-top: .05rem; }

    /* ---------- List panel (mirip card-main) ---------- */
    .dash-panels { display: grid; gap: .8rem; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); }
    .dpanel { background: var(--card, #fff); border: 1px solid var(--dsh-border); border-radius: 8px; overflow: hidden; }
    .dpanel-head { display: flex; align-items: center; justify-content: space-between;
        padding: .65rem .85rem; border-bottom: 1px solid var(--dsh-border); }
    .dpanel-head .t { font-weight: 700; color: var(--text, #0f172a); font-size: .88rem; display: flex; align-items: center; gap: .4rem; }
    .dpanel-head .t .bi { color: var(--dsh-muted); font-size: .85rem; }
    .dpanel-head a { font-size: .74rem; font-weight: 600; color: var(--dsh-accent); text-decoration: none; white-space: nowrap; }
    body[data-theme="dark"] .dpanel-head a { color: #93c5fd; }
    .dpanel-head a:hover { text-decoration: underline; }
    .dpanel-body { padding: .2rem .3rem; }
    .drow { display: flex; align-items: center; gap: .6rem; padding: .5rem .6rem; }
    .drow + .drow { border-top: 1px solid var(--dsh-border); }
    .drow:hover { background: rgba(148,163,184,.05); }
    .drow .main { min-width: 0; flex: 1; }
    .drow .name { font-weight: 650; color: var(--text, #0f172a); font-size: .84rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .drow .meta { color: var(--dsh-muted); font-size: .72rem; font-weight: 500; margin-top: .1rem; }
    .drow .val { font-weight: 650; font-size: .82rem; white-space: nowrap; color: var(--text, #0f172a); }
    .drow .val.red { color: #dc2626; }
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
    .ditem-summary { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: .45rem; margin-bottom: .6rem; }
    .ditem-summary-item { display: flex; align-items: center; justify-content: space-between; gap: .5rem; padding: .45rem .55rem; border: 1px solid var(--dsh-border); border-radius: 7px; color: var(--dsh-muted); font-size: .68rem; }
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
    .drepeat-table-wrap { overflow-x: auto; }
    .drepeat-table { width: 100%; border-collapse: collapse; min-width: 1400px; }
    .drepeat-table th { padding: .45rem .55rem; color: var(--dsh-muted); font-size: .65rem; font-weight: 700; text-align: left; border-bottom: 1px solid var(--dsh-border); white-space: nowrap; }
    .drepeat-table td { padding: .52rem .55rem; border-bottom: 1px solid var(--dsh-border); vertical-align: middle; font-size: .75rem; }
    .drepeat-table tr:last-child td { border-bottom: 0; }
    .drepeat-table .numeric { text-align: right; white-space: nowrap; }
    .drepeat-buyer { color: var(--text, #0f172a); font-weight: 650; }
    .drepeat-order { color: var(--dsh-muted); font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; font-size: .69rem !important; }
    .drepeat-order-link { color: var(--dsh-accent); text-decoration: none; }
    .drepeat-order-link:hover { text-decoration: underline; }
    .drepeat-order-meta { margin-top: .18rem; color: var(--dsh-muted); font-family: inherit; font-size: .62rem; white-space: nowrap; }
    .drepeat-order-meta code { color: var(--text, #0f172a); font-family: inherit; font-size: inherit; }
    .drepeat-items { color: var(--dsh-muted); font-size: .7rem !important; }
    .drepeat-items code { color: var(--text, #0f172a); font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; font-size: .69rem; }
    .drepeat-item-line { line-height: 1.35; white-space: nowrap; }
    .drepeat-date { color: var(--dsh-muted); white-space: nowrap; font-size: .72rem !important; }

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
    .dash-empty .bi { display: block; font-size: 1.4rem; opacity: .5; margin-bottom: .25rem; }

    @media (max-width: 640px) {
        .dash-hero { padding: .75rem .85rem; } .dash-hero h1 { font-size: 1rem; }
        .hero-date { display: none; }
        .kpi { min-height: 164px; }
        .kpi-value { font-size: 1.25rem; }
    }
</style>
