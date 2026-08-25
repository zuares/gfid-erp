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

    .dash { display: grid; gap: .9rem; max-width: 1100px; margin-inline: auto; }

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
    .dash-grid { display: grid; gap: .6rem; grid-template-columns: repeat(auto-fit, minmax(185px, 1fr)); }
    .kpi {
        display: block; text-decoration: none; color: inherit;
        background: var(--card, #fff); border: 1px solid var(--dsh-border); border-radius: 8px;
        padding: .7rem .8rem; position: relative; transition: border-color .12s ease, background .12s ease;
    }
    a.kpi:hover { border-color: var(--dsh-border-strong); background: rgba(148,163,184,.05); color: inherit; }
    .kpi-label { color: var(--dsh-muted); font-size: .72rem; font-weight: 600; display: flex; align-items: center; gap: .4rem; }
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
    .kpi-value { font-size: 1.4rem; font-weight: 700; color: var(--text, #0f172a); line-height: 1.15; margin-top: .35rem; }
    .kpi-value.sm { font-size: 1.1rem; }
    .kpi-sub { color: var(--dsh-muted); font-size: .72rem; font-weight: 500; margin-top: .2rem; }
    .kpi-comparisons { display: grid; gap: .14rem; margin-top: .42rem; padding-top: .35rem; border-top: 1px dashed var(--dsh-border); }
    .kpi-compare { display: flex; align-items: center; justify-content: space-between; gap: .5rem; color: var(--dsh-muted); font-size: .66rem; font-weight: 600; }
    .kpi-compare strong { font-weight: 750; white-space: nowrap; }
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
        .kpi-value { font-size: 1.25rem; }
    }
</style>
