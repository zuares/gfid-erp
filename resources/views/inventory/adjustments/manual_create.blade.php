@extends('layouts.app')

@section('title', 'Adjustment Manual')

@push('head')
<style>
    :root{
        --adj-accent:#334155;
        --adj-accent-2:#1f2937;
        --adj-border:rgba(148,163,184,.18);
        --adj-muted:#64748b;
    }

    /* ── Topbar ─────────────────────────────────────────────── */
    .adj-topbar{
        position:sticky;
        top:0;
        z-index:300;
        display:flex;
        align-items:center;
        gap:.45rem;
        flex-wrap:wrap;
        padding:.45rem .75rem;
        background:var(--card,#fff);
        border-bottom:1px solid var(--adj-border);
    }
    body[data-theme="dark"] .adj-topbar{ background:var(--card,#0f172a); }
    .adj-topbar-title{ font-weight:750; font-size:.95rem; letter-spacing:0; white-space:nowrap; }
    .adj-topbar-spacer{ flex:1; min-width:.5rem; }
    .adj-badge{
        border-radius:7px; padding:.16rem .44rem;
        font-size:.68rem; letter-spacing:0; text-transform:none;
        border:1px solid rgba(148,163,184,.28);
        color:var(--adj-muted); background:transparent;
        display:inline-flex; align-items:center; gap:.3rem;
        white-space:nowrap;
    }
    .adj-badge::before{ content:''; width:7px; height:7px; border-radius:999px; background:rgba(100,116,139,.95); display:inline-block; }
    .adj-badge.is-owner{ color:#1d4ed8; border-color:rgba(59,130,246,.30); }
    .adj-badge.is-owner::before{ background:rgba(59,130,246,.95); }
    .btn-adj-outline{
        color:#475569!important; background:transparent!important;
        border:1px solid rgba(148,163,184,.35)!important;
        border-radius:7px!important; font-size:.74rem!important;
        padding:.28rem .62rem!important; box-shadow:none!important;
        text-decoration:none; white-space:nowrap;
    }
    .btn-adj-outline:hover{ background:rgba(148,163,184,.08)!important; color:#111827!important; }
    .btn-adj-primary{
        background:var(--adj-accent)!important; border-color:var(--adj-accent)!important;
        color:#fff!important; border-radius:7px!important; font-weight:600!important;
        box-shadow:none!important;
    }
    .btn-adj-primary:hover{ background:var(--adj-accent-2)!important; border-color:var(--adj-accent-2)!important; color:#fff!important; }

    /* ── Card ────────────────────────────────────────────────── */
    .adj-card{
        background:var(--card);
        border-radius:8px;
        border:1px solid var(--adj-border);
        box-shadow:none;
    }
    .adj-card.is-sticky-step{
        position:sticky;
        top:118px;
        z-index:285;
        scroll-margin-top:132px;
    }
    body[data-theme="dark"] .adj-card{ border-color:rgba(51,65,85,.85); box-shadow:none; }
    .adj-card-body{ padding:.85rem; }
    .adj-section-label{
        font-size:.66rem; font-weight:700; text-transform:uppercase;
        letter-spacing:.04em; color:#94a3b8; margin-bottom:.55rem;
        display:flex; align-items:center; gap:.35rem;
    }
    .adj-section-label::after{ content:''; flex:1; height:1px; background:rgba(148,163,184,.20); }
    body[data-theme="dark"] .adj-section-label::after{ background:rgba(51,65,85,.85); }

    /* ── Form ────────────────────────────────────────────────── */
    .adj-form-row{ --bs-gutter-x:.5rem; --bs-gutter-y:.42rem; }
    .adj-field-label{
        display:block; font-size:.68rem; font-weight:600;
        color:#64748b; margin-bottom:.2rem; white-space:nowrap;
    }
    body[data-theme="dark"] .adj-field-label{ color:#9ca3af; }
    .form-control-sm,.form-select-sm{
        border-radius:8px; border-color:rgba(148,163,184,.35);
        box-shadow:none!important; font-size:.82rem; min-height:34px;
    }
    .form-control-sm:focus,.form-select-sm:focus{
        border-color:rgba(71,85,105,.75); box-shadow:none!important;
    }
    .qty-stepper{
        display:inline-flex;
        align-items:stretch;
        justify-content:flex-end;
        gap:.25rem;
    }
    .qty-stepper-input{
        width:94px;
    }
    .qty-stepper-actions{
        display:flex;
        flex-direction:column;
        gap:2px;
    }
    .qty-stepper-btn{
        width:24px;
        height:16px;
        padding:0;
        border-radius:6px;
        border:1px solid rgba(148,163,184,.35);
        background:rgba(255,255,255,.85);
        color:#334155;
        display:inline-flex;
        align-items:center;
        justify-content:center;
        line-height:1;
        box-shadow:none;
    }
    .qty-stepper-btn:hover{
        background:rgba(148,163,184,.12);
        color:#0f172a;
    }
    .qty-stepper-btn:active{
        transform:translateY(1px);
    }
    body[data-theme="dark"] .qty-stepper-btn{
        background:rgba(15,23,42,.82);
        color:#e2e8f0;
        border-color:rgba(51,65,85,.9);
    }
    body[data-theme="dark"] .qty-stepper-btn:hover{
        background:rgba(30,41,59,.96);
        color:#fff;
    }
    .adj-gate{
        display:flex;
        align-items:flex-start;
        justify-content:space-between;
        gap:1rem;
        flex-wrap:wrap;
    }
    .adj-gate-copy{
        min-width:min(320px,100%);
        max-width:42rem;
    }
    .adj-gate-title{
        font-size:1rem;
        font-weight:800;
        line-height:1.2;
        color:#0f172a;
        margin-bottom:.2rem;
    }
    body[data-theme="dark"] .adj-gate-title{
        color:#f8fafc;
    }
    .adj-gate-note{
        font-size:.8rem;
        color:var(--adj-muted);
        line-height:1.45;
    }
    .adj-gate-hint{
        margin-top:.45rem;
        font-size:.74rem;
        color:#94a3b8;
    }
    .adj-gate-field{
        min-width:min(430px,100%);
        flex:1 1 430px;
    }
    .adj-workflow-bar{
        display:none;
        position:sticky;
        top:56px;
        z-index:290;
        margin-top:.55rem;
        padding:.75rem .85rem;
        border-radius:12px;
        border:1px solid rgba(148,163,184,.22);
        background:linear-gradient(135deg, rgba(37,99,235,.08), rgba(255,255,255,.78));
        backdrop-filter:blur(8px);
    }
    body[data-theme="dark"] .adj-workflow-bar{
        background:linear-gradient(135deg, rgba(15,23,42,.94), rgba(30,41,59,.92));
        border-color:rgba(51,65,85,.82);
    }
    .adj-workflow-bar.is-visible{
        display:flex;
        align-items:center;
        justify-content:space-between;
        gap:.75rem;
        flex-wrap:wrap;
    }
    .adj-workflow-meta{
        min-width:min(320px,100%);
    }
    .adj-workflow-step{
        display:inline-flex;
        align-items:center;
        gap:.35rem;
        font-size:.68rem;
        font-weight:700;
        text-transform:uppercase;
        letter-spacing:.04em;
        color:#64748b;
    }
    body[data-theme="dark"] .adj-workflow-step{
        color:#94a3b8;
    }
    .adj-workflow-selected{
        display:block;
        margin-top:.15rem;
        font-size:.92rem;
        font-weight:800;
        color:#0f172a;
        line-height:1.25;
    }
    body[data-theme="dark"] .adj-workflow-selected{
        color:#f8fafc;
    }
    .adj-workflow-desc{
        margin-top:.2rem;
        font-size:.76rem;
        color:var(--adj-muted);
        line-height:1.35;
    }
    .adj-workflow-actions{
        display:flex;
        align-items:center;
        gap:.5rem;
        flex-wrap:wrap;
    }
    .adj-workflow-actions .btn{
        min-width:110px;
    }
    .adj-item-main{
        display:flex;
        align-items:flex-start;
        gap:.45rem;
        flex-wrap:wrap;
    }
    .adj-item-code{
        display:inline-flex;
        align-items:center;
        min-height:1.2rem;
        font-size:.86rem;
        line-height:1.2;
    }
    .adj-item-meta{
        display:flex;
        flex-direction:column;
        gap:.15rem;
        min-width:0;
    }
    .adj-item-name{
        font-size:.78rem;
        color:#6b7280;
        line-height:1.25;
        word-break:break-word;
    }
    .adj-item-badges{
        display:flex;
        align-items:center;
        flex-wrap:wrap;
        gap:.25rem;
    }

    /* ── KPI mini ────────────────────────────────────────────── */
    .adj-kpi-row{ display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:.4rem; margin-top:.6rem; }
    .adj-kpi{
        border:1px solid rgba(148,163,184,.18); border-radius:8px;
        padding:.4rem .5rem; background:transparent; min-width:0;
    }
    body[data-theme="dark"] .adj-kpi{ background:rgba(15,23,42,.20); }
    .adj-kpi .lbl{ display:block; font-size:.58rem; font-weight:700; color:#94a3b8; line-height:1; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    .adj-kpi .val{ display:block; margin-top:.14rem; font-size:.88rem; font-weight:800; line-height:1; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; font-variant-numeric:tabular-nums; }
    .adj-kpi.is-main .val{ color:#16a34a; }

    /* ── Table ───────────────────────────────────────────────── */
    .adj-table-scroll{
        max-height:calc(100vh - 320px);
        overflow-y:auto;
        overflow-x:hidden;
        overscroll-behavior:contain;
    }
    .adj-table{ margin-bottom:0; }
    .adj-table thead th{
        position:sticky; top:0; z-index:2;
        font-size:.66rem; text-transform:uppercase; letter-spacing:.03em;
        color:#6b7280; background:var(--card,#fff);
        padding:.42rem .5rem; white-space:nowrap; border-bottom-width:1px;
    }
    body[data-theme="dark"] .adj-table thead th{
        background:rgba(15,23,42,.98); color:#9ca3af;
        border-bottom-color:rgba(30,64,175,.6);
    }
    .adj-table tbody td{
        vertical-align:middle; padding:.38rem .5rem;
        border-top-color:rgba(148,163,184,.16);
    }
    body[data-theme="dark"] .adj-table tbody td{ border-top-color:rgba(51,65,85,.85); }
    .adj-row-focus{
        outline:2px solid rgba(59,130,246,.22);
        background:rgba(59,130,246,.05);
        transition:background .18s ease, outline-color .18s ease;
    }
    .text-mono{ font-variant-numeric:tabular-nums; font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,monospace; }
    .diff-plus{ color:#16a34a; }
    .diff-minus{ color:#dc2626; }
    .item-badge{
        display:inline-flex; align-items:center; border-radius:7px;
        padding:.1rem .35rem; font-size:.6rem; font-weight:700;
        border:1px solid rgba(34,197,94,.25); color:#166534;
        background:rgba(34,197,94,.06); margin-left:.3rem;
    }

    /* ── Footer bar ─────────────────────────────────────────── */
    .adj-footer{
        display:flex; justify-content:space-between; align-items:center;
        padding:.6rem 0 0; gap:.5rem; flex-wrap:wrap;
    }
    .adj-footer-hint{ font-size:.76rem; color:#94a3b8; }

    /* ── Divider ────────────────────────────────────────────── */
    .adj-divider{ height:1px; background:rgba(148,163,184,.20); margin:.65rem 0; }
    body[data-theme="dark"] .adj-divider{ background:rgba(51,65,85,.85); }

    /* ── Empty ──────────────────────────────────────────────── */
    .adj-empty{ padding:2rem 1rem; text-align:center; color:#94a3b8; font-size:.84rem; }
    .adj-search-hint{
        margin-top:.45rem;
        font-size:.76rem;
        color:#94a3b8;
    }
    .adj-form-error{
        margin-top:.45rem;
        font-size:.76rem;
        color:#dc2626;
    }

    #workflow-sections{
        scroll-margin-top:132px;
    }

    @media(max-width:768px){
        .adj-topbar{ padding:.5rem; }
        .adj-topbar-title{ font-size:1rem; }
        .adj-topbar{ align-items:stretch; }
        .adj-topbar .btn-adj-outline{ width:100%; text-align:center; }
        .adj-kpi-row{ grid-template-columns:repeat(2,1fr); gap:.32rem; }
        .adj-card-body{ padding:.65rem; }
        .adj-gate,
        .adj-workflow-bar.is-visible{
            flex-direction:column;
            align-items:stretch;
        }
        .adj-gate-field{ min-width:0; width:100%; flex:1 1 auto; }
        .adj-workflow-actions{ width:100%; }
        .adj-workflow-actions .btn{
            width:100%;
            min-width:0;
        }
        .adj-item-main{
            flex-direction:column;
            gap:.25rem;
        }
        .adj-item-meta{
            display:none;
        }
        .adj-item-code{
            font-size:1rem;
            font-weight:800;
        }
        .adj-table-scroll{ max-height:none; overflow:visible; }
        .adj-card.is-sticky-step{
            position:static;
            top:auto;
            z-index:auto;
            scroll-margin-top:100px;
        }
        #workflow-sections{
            scroll-margin-top:100px;
        }
        .adj-table thead{ display:none; }
        .adj-table,.adj-table tbody,.adj-table tr,.adj-table td{ display:block; width:100%; }
        .adj-table tbody tr{
            padding:.7rem .7rem .75rem;
            border-top:1px solid rgba(148,163,184,.16);
            border-radius:12px;
            margin-bottom:.45rem;
            background:rgba(255,255,255,.35);
        }
        body[data-theme="dark"] .adj-table tbody tr{
            background:rgba(15,23,42,.42);
        }
        .adj-table tbody td{ border:0; padding:0; }
        .adj-table tbody td.mobile-hide{ display:none; }
        .adj-row-main{ display:block; }
        .adj-row-meta{ display:none; }
        .qty-stepper{
            width:100%;
            justify-content:space-between;
            align-items:stretch;
            gap:.4rem;
        }
        .qty-stepper-input{
            width:100%;
            min-width:0;
        }
        .qty-stepper-actions{
            flex-direction:row;
            gap:.3rem;
        }
        .qty-stepper-btn{
            width:32px;
            height:32px;
            border-radius:8px;
        }
        .adj-footer{
            flex-direction:column;
            align-items:stretch;
        }
        .adj-footer .btn{
            width:100%;
        }
    }

    /* ── GFID design-system alignment ───────────────────────── */
    .adj-page{
        --adj-brand:var(--gf-color-brand,#5b5ce2);
        --adj-brand-strong:var(--gf-color-brand-strong,#4142bb);
        --adj-accent:var(--gf-color-accent,#13b8c4);
        --adj-ink:var(--gf-color-content,var(--text,#101828));
        --adj-muted:var(--gf-color-muted,var(--muted,#667085));
        --adj-surface:var(--gf-color-surface,var(--card,#fff));
        --adj-surface-muted:var(--gf-color-surface-muted,var(--card-soft,#f2f4f7));
        --adj-line:var(--gf-color-line,var(--line,#e4e7ec));
        --adj-success:var(--gf-color-success,#14945a);
        --adj-danger:var(--gf-color-danger,#d6455d);
        --adj-radius-card:var(--gf-radius-card,16px);
        --adj-radius-control:var(--gf-radius-control,10px);
        --adj-shadow:var(--gf-shadow-sm,0 1px 2px rgba(16,24,40,.04),0 4px 12px rgba(16,24,40,.04));
        color:var(--adj-ink);
    }

    .adj-page .adj-topbar{
        position:relative;
        top:auto;
        z-index:1;
        align-items:flex-start;
        margin:0 0 .8rem;
        padding:1.25rem 1.35rem;
        border:1px solid color-mix(in srgb,var(--adj-brand) 18%,var(--adj-line) 82%);
        border-radius:24px;
        background:linear-gradient(120deg,
            color-mix(in srgb,var(--adj-brand) 9%,var(--adj-surface) 91%) 0%,
            var(--adj-surface) 52%,
            color-mix(in srgb,var(--adj-accent) 8%,var(--adj-surface) 92%) 100%);
        box-shadow:var(--gf-shadow-lg,var(--adj-shadow));
        overflow:hidden;
    }
    .adj-page .adj-topbar::after{
        content:'';
        position:absolute;
        width:180px;
        height:180px;
        right:-55px;
        top:-82px;
        border-radius:50%;
        background:color-mix(in srgb,var(--adj-brand) 12%,transparent);
        pointer-events:none;
    }
    .adj-topbar-copy{ position:relative; z-index:1; min-width:0; }
    .adj-eyebrow{
        display:inline-flex;
        align-items:center;
        gap:.35rem;
        margin-bottom:.35rem;
        color:var(--adj-brand);
        font-size:.64rem;
        font-weight:850;
        letter-spacing:.1em;
        text-transform:uppercase;
    }
    .adj-eyebrow i{ font-size:.78rem; }
    .adj-page .adj-topbar-title{
        display:block;
        margin:0;
        color:var(--adj-ink);
        font-size:clamp(1.3rem,2.2vw,1.8rem);
        font-weight:820;
        letter-spacing:-.045em;
        line-height:1.08;
    }
    .adj-topbar-description{
        max-width:620px;
        margin:.45rem 0 0;
        color:var(--adj-muted);
        font-size:.78rem;
        line-height:1.5;
    }
    .adj-topbar-actions{
        position:relative;
        z-index:1;
        display:flex;
        align-items:center;
        justify-content:flex-end;
        gap:.5rem;
        flex-wrap:wrap;
    }
    .adj-page .adj-badge{
        min-height:26px;
        padding:.28rem .58rem;
        border-radius:999px;
        color:var(--adj-muted);
        background:color-mix(in srgb,var(--adj-surface) 78%,var(--adj-surface-muted) 22%);
        border-color:var(--adj-line);
        font-size:.64rem;
        font-weight:750;
    }
    .adj-page .adj-badge::before{ background:var(--adj-muted); }
    .adj-page .adj-badge.is-owner{
        color:var(--adj-brand-strong);
        border-color:color-mix(in srgb,var(--adj-brand) 24%,var(--adj-line) 76%);
        background:color-mix(in srgb,var(--adj-brand) 10%,var(--adj-surface) 90%);
    }
    .adj-page .adj-badge.is-owner::before{ background:var(--adj-brand); }
    .adj-page .btn-adj-outline,
    .adj-page .btn-adj-primary{
        min-height:36px;
        padding:.45rem .72rem!important;
        border-radius:var(--adj-radius-control)!important;
        font-size:.72rem!important;
        font-weight:750!important;
        transition:color .18s ease,background .18s ease,border-color .18s ease,transform .18s ease,box-shadow .18s ease;
    }
    .adj-page .btn-adj-outline{
        color:var(--adj-muted)!important;
        background:color-mix(in srgb,var(--adj-surface) 86%,var(--adj-surface-muted) 14%)!important;
        border-color:var(--adj-line)!important;
    }
    .adj-page .btn-adj-outline:hover{
        color:var(--adj-brand-strong)!important;
        background:var(--adj-surface)!important;
        border-color:color-mix(in srgb,var(--adj-brand) 35%,var(--adj-line) 65%)!important;
        transform:translateY(-1px);
    }
    .adj-page .btn-adj-primary{
        color:#fff!important;
        background:var(--adj-brand)!important;
        border-color:var(--adj-brand)!important;
        box-shadow:0 7px 14px color-mix(in srgb,var(--adj-brand) 22%,transparent)!important;
    }
    .adj-page .btn-adj-primary:hover{
        color:#fff!important;
        background:var(--adj-brand-strong)!important;
        border-color:var(--adj-brand-strong)!important;
        transform:translateY(-1px);
    }
    .adj-page .btn-adj-outline:focus-visible,
    .adj-page .btn-adj-primary:focus-visible,
    .adj-page .qty-stepper-btn:focus-visible{
        outline:3px solid color-mix(in srgb,var(--adj-brand) 28%,transparent);
        outline-offset:2px;
    }
    .adj-page .btn-adj-outline > *,
    .adj-page .btn-adj-primary > *{ color:inherit; }

    .adj-page .adj-card{
        border:1px solid var(--adj-line);
        border-radius:var(--adj-radius-card);
        background:var(--adj-surface);
        box-shadow:var(--adj-shadow);
        margin-top:0!important;
    }
    .adj-page #workflow-sections > .adj-card{ margin-top:.75rem!important; }
    .adj-page .adj-card-body{ padding:1rem 1.1rem; }
    .adj-page .adj-table-card-body{ padding:.75rem .8rem .85rem; }
    .adj-page .adj-section-label{
        margin-bottom:.72rem;
        color:var(--adj-brand);
        font-size:.63rem;
        font-weight:850;
        letter-spacing:.1em;
    }
    .adj-page .adj-section-label::after{ background:var(--adj-line); }
    .adj-page .adj-field-label{
        margin-bottom:.3rem;
        color:var(--adj-muted);
        font-size:.68rem;
        font-weight:750;
    }
    .adj-page .form-control-sm,
    .adj-page .form-select-sm{
        min-height:38px;
        border:1px solid var(--adj-line);
        border-radius:var(--adj-radius-control);
        background:color-mix(in srgb,var(--adj-surface) 94%,var(--adj-surface-muted) 6%);
        color:var(--adj-ink);
        font-size:.76rem;
        transition:border-color .18s ease,box-shadow .18s ease,background .18s ease;
    }
    .adj-page .form-control-sm:focus,
    .adj-page .form-select-sm:focus{
        border-color:var(--adj-brand);
        box-shadow:0 0 0 3px color-mix(in srgb,var(--adj-brand) 12%,transparent)!important;
        background:var(--adj-surface);
    }
    .adj-page .adj-gate-title{ color:var(--adj-ink); font-size:1.08rem; letter-spacing:-.025em; }
    .adj-page .adj-gate-note,
    .adj-page .adj-gate-hint,
    .adj-page .adj-search-hint,
    .adj-page .adj-footer-hint{ color:var(--adj-muted); }
    .adj-page .adj-gate-note{ font-size:.78rem; }
    .adj-page .adj-gate-hint{ font-size:.7rem; }
    .adj-page .adj-info-hint{ margin-top:0; }
    .adj-page .adj-gate-note,
    .adj-page .adj-workflow-desc,
    .adj-page .adj-info-hint,
    .adj-page .adj-section-subtitle{ display:none; }

    .adj-page .adj-workflow-bar{
        position:static;
        top:calc(var(--app-navbar-offset,58px) + .55rem);
        margin-top:.75rem;
        padding:.78rem .9rem;
        border:1px solid color-mix(in srgb,var(--adj-brand) 18%,var(--adj-line) 82%);
        border-radius:var(--adj-radius-card);
        background:var(--adj-surface);
        box-shadow:var(--adj-shadow);
        backdrop-filter:blur(16px);
    }
    .adj-workflow-leading{ display:flex; align-items:center; gap:.65rem; min-width:0; }
    .adj-workflow-icon{
        display:grid;
        width:34px;
        height:34px;
        flex:0 0 auto;
        place-items:center;
        border-radius:12px;
        color:#fff;
        background:linear-gradient(145deg,var(--adj-brand),var(--adj-accent));
        box-shadow:0 8px 16px color-mix(in srgb,var(--adj-brand) 22%,transparent);
    }
    .adj-page .adj-workflow-step{ color:var(--adj-brand-strong); font-size:.63rem; font-weight:850; }
    .adj-page .adj-workflow-selected{ color:var(--adj-ink); font-size:.9rem; }
    .adj-page .adj-workflow-desc{ color:var(--adj-muted); font-size:.72rem; }
    .adj-page .adj-workflow-bar .adj-workflow-actions{ display:none; }

    .adj-search-control{ position:relative; display:flex; align-items:center; }
    .adj-search-control > i{ position:absolute; left:.72rem; z-index:1; color:#98a2b3; font-size:.78rem; pointer-events:none; }
    .adj-search-control > input{ padding-left:2.1rem!important; }
    .adj-page .adj-search-hint{ margin-top:.55rem; font-size:.68rem; line-height:1.4; }
    .adj-page .adj-search-hint::before{ content:'⌁'; margin-right:.3rem; color:var(--adj-brand); font-weight:900; }

    .adj-section-heading{ display:flex; align-items:center; justify-content:space-between; gap:.75rem; margin-bottom:.55rem; }
    .adj-section-heading .adj-section-label{ margin:0; }
    .adj-section-subtitle{ margin-top:.16rem; color:var(--adj-muted); font-size:.68rem; }
    .adj-section-heading .form-select-sm{ width:132px; min-height:34px; }
    .adj-page .adj-table-scroll{ max-height:calc(100vh - 330px); border:1px solid var(--adj-line); border-radius:12px; }
    .adj-page .adj-table{ font-size:.75rem; }
    .adj-page .adj-table thead th{
        background:var(--adj-surface-muted);
        color:var(--adj-muted);
        border-bottom-color:var(--adj-line);
        font-size:.59rem;
        font-weight:850;
        letter-spacing:.07em;
    }
    .adj-page .adj-table tbody td{ padding:.62rem .58rem; border-top-color:var(--adj-line); }
    .adj-page .adj-table tbody tr:hover{ background:color-mix(in srgb,var(--adj-brand) 4%,var(--adj-surface) 96%); }
    .adj-page .adj-row-focus{ outline:2px solid color-mix(in srgb,var(--adj-brand) 28%,transparent); background:color-mix(in srgb,var(--adj-brand) 6%,var(--adj-surface) 94%); }
    .adj-page .adj-item-code{ color:var(--adj-ink); font-weight:800; }
    .adj-page .adj-item-name{ color:var(--adj-muted); }
    .adj-page .item-badge{ color:var(--adj-success); border-color:color-mix(in srgb,var(--adj-success) 24%,var(--adj-line) 76%); background:color-mix(in srgb,var(--adj-success) 8%,var(--adj-surface) 92%); }
    .adj-page .qty-stepper-input{ width:104px; }
    .adj-page .qty-stepper-btn{
        width:28px;
        min-height:17px;
        border-color:var(--adj-line);
        border-radius:8px;
        color:var(--adj-brand-strong);
        background:var(--adj-surface);
    }
    .adj-page .qty-stepper-btn:hover{ color:var(--adj-brand-strong); background:color-mix(in srgb,var(--adj-brand) 8%,var(--adj-surface) 92%); }
    .adj-page .diff-plus{ color:var(--adj-success); font-weight:800; }
    .adj-page .diff-minus{ color:var(--adj-danger); font-weight:800; }
    .adj-page .adj-divider{ background:var(--adj-line); margin:.8rem 0 .65rem; }
    .adj-page .adj-footer{
        position:sticky;
        bottom:0;
        z-index:285;
        align-items:center;
        justify-content:space-between;
        gap:.75rem;
        margin-top:.75rem;
        padding:.75rem .8rem;
        border:1px solid var(--adj-line);
        border-radius:var(--adj-radius-control);
        background:var(--adj-surface);
        box-shadow:0 -8px 20px rgba(16,24,40,.08);
    }
    .adj-footer-meta{ min-width:0; flex:1 1 auto; }
    .adj-footer-actions{
        display:flex;
        align-items:center;
        justify-content:flex-end;
        gap:.5rem;
        flex-wrap:nowrap;
    }
    .adj-page .adj-footer-actions .btn{
        display:inline-flex;
        align-items:center;
        justify-content:center;
        gap:.35rem;
        min-height:40px;
        border-radius:var(--adj-radius-control)!important;
        font-weight:750!important;
        white-space:nowrap;
    }
    .adj-page .adj-footer-actions .btn-adj-outline{
        background:color-mix(in srgb,var(--adj-surface) 86%,var(--adj-surface-muted) 14%)!important;
        border-color:var(--adj-line)!important;
        color:var(--adj-muted)!important;
        text-decoration:none!important;
    }
    .adj-page .adj-footer-actions .btn-adj-outline:hover{
        color:var(--adj-brand-strong)!important;
        background:var(--adj-surface)!important;
        border-color:color-mix(in srgb,var(--adj-brand) 35%,var(--adj-line) 65%)!important;
    }
    .adj-page .adj-footer-actions .btn:focus-visible{
        outline:3px solid color-mix(in srgb,var(--adj-brand) 28%,transparent);
        outline-offset:2px;
    }
    .adj-page .adj-footer-hint{ color:var(--adj-ink); font-weight:800; }
    .adj-mobile-action-trigger{ display:none!important; }
    .adj-empty-row td{ padding:0!important; border-top:0!important; }
    .adj-page .adj-empty{ display:grid; place-items:center; min-height:160px; padding:1rem; color:var(--adj-muted); text-align:center; }
    .adj-empty-icon{ display:grid; width:36px; height:36px; place-items:center; margin-bottom:.45rem; border-radius:12px; color:var(--adj-brand); background:color-mix(in srgb,var(--adj-brand) 10%,var(--adj-surface) 90%); }
    .adj-empty strong{ color:var(--adj-ink); font-size:.78rem; }
    .adj-empty span{ max-width:300px; margin-top:.2rem; font-size:.68rem; line-height:1.45; }

    .adj-page .modal-content{ border:1px solid var(--adj-line)!important; border-radius:var(--adj-radius-card)!important; background:var(--adj-surface); color:var(--adj-ink); box-shadow:var(--gf-shadow-lg,var(--adj-shadow))!important; }
    .adj-page .modal-header,.adj-page .modal-footer{ border-color:var(--adj-line)!important; }
    .adj-page .modal-title{ color:var(--adj-ink); font-size:.9rem; font-weight:800; }
    .adj-page .modal-body{ font-size:.78rem; }
    .adj-page .modal-body .alert{ border:1px solid color-mix(in srgb,#b7791f 22%,var(--adj-line) 78%); border-radius:var(--adj-radius-control); color:#8a5b12; background:#fffaeb; }
    .adj-page .modal-body strong{ color:var(--adj-ink); }

    @media(max-width:768px){
        .adj-page .adj-topbar{
            margin-bottom:.6rem;
            padding:.85rem 1rem;
            border-radius:18px;
        }
        .adj-page .adj-topbar-description,
        .adj-page .adj-gate-title,
        .adj-page .adj-gate-note,
        .adj-page .adj-workflow-desc,
        .adj-page .adj-info-hint,
        .adj-page .adj-section-subtitle{
            display:none;
        }
        .adj-page .adj-topbar-title{ font-size:1.3rem; }
        .adj-page .adj-topbar-actions{ width:100%; justify-content:flex-start; }
        .adj-page .adj-topbar-actions .btn{ flex:1 1 auto; }
        .adj-page .adj-card-body{ padding:.8rem; }
        .adj-page #workflow-sections > .adj-card{ margin-top:.6rem!important; }
        .adj-page .adj-gate-copy{ min-width:0; max-width:none; }
        .adj-page .adj-gate-hint{ margin-top:.35rem; }
        .adj-workflow-leading{
            width:100%;
            flex:0 0 auto;
            align-items:center;
        }
        .adj-page .adj-workflow-bar{
            flex-direction:column!important;
            align-items:stretch!important;
            top:calc(var(--app-navbar-offset,58px) + .35rem);
            margin-top:.6rem;
            padding:.6rem .7rem;
            gap:.55rem;
        }
        .adj-page .adj-workflow-icon,
        .adj-page .adj-workflow-step{ display:none; }
        .adj-page .adj-workflow-selected{
            margin-top:0;
            overflow:hidden;
            font-size:.76rem;
            text-overflow:ellipsis;
            white-space:nowrap;
        }
        .adj-section-heading{ align-items:flex-start; flex-direction:column; }
        .adj-section-heading .form-select-sm{ width:100%; }
        .adj-page .adj-table-scroll{ border:0; overflow:visible; }
        .adj-page .adj-table tbody tr{
            display:grid;
            grid-template-columns:minmax(0,1fr) minmax(132px,.82fr);
            gap:.55rem .7rem;
            padding:.8rem;
            border:1px solid var(--adj-line);
            box-shadow:var(--adj-shadow);
            background:var(--adj-surface);
        }
        .adj-page .adj-table tbody td{
            display:block;
            width:auto;
            padding:0;
            border:0;
        }
        .adj-page .adj-table tbody td.adj-index-cell{ display:none; }
        .adj-page .adj-table tbody td:nth-child(2){ grid-column:1 / -1; }
        .adj-page .adj-table tbody td.adj-on-hand-cell{
            display:block;
            grid-column:1;
            text-align:left!important;
        }
        .adj-page .adj-table tbody td:nth-child(4){ grid-column:2; }
        .adj-page .adj-table tbody td.adj-diff-cell{
            display:block;
            grid-column:1;
            text-align:left!important;
        }
        .adj-page .adj-table tbody td.adj-notes-cell{ grid-column:1 / -1; }
        .adj-page .adj-table tbody td.adj-on-hand-cell::before,
        .adj-page .adj-table tbody td.adj-diff-cell::before,
        .adj-page .adj-table tbody td.adj-notes-cell::before{
            display:block;
            margin-bottom:.22rem;
            color:var(--adj-muted);
            font-size:.6rem;
            font-weight:850;
            letter-spacing:.06em;
            line-height:1.2;
            text-transform:uppercase;
        }
        .adj-page .adj-table tbody td.adj-on-hand-cell::before{ content:'Stok saat ini'; }
        .adj-page .adj-table tbody td.adj-diff-cell::before{ content:'Selisih'; }
        .adj-page .adj-table tbody td.adj-notes-cell::before{ content:'Alasan perubahan'; }
        .adj-page .adj-item-main{ flex-direction:row; align-items:flex-start; }
        .adj-page .adj-item-meta{ display:flex; }
        .adj-page .adj-item-name{ font-size:.72rem; }
        .adj-page .qty-stepper{ width:100%; }
        .adj-page .qty-stepper-input,
        .adj-page .adj-notes-cell .notes-input{
            min-height:44px;
        }
        .adj-page .qty-stepper-btn{
            width:40px;
            height:40px;
            min-height:40px;
        }
        .adj-page .adj-footer{
            position:fixed;
            left:.55rem;
            right:.55rem;
            bottom:var(--mobile-bottom-nav-clearance,calc(70px + env(safe-area-inset-bottom,0px) + 1rem));
            z-index:1000;
            flex-direction:column;
            align-items:stretch;
            gap:.45rem;
            margin:0;
            padding:.6rem .65rem calc(.6rem + env(safe-area-inset-bottom,0px));
            border-radius:16px;
            box-shadow:0 8px 24px rgba(16,24,40,.18),0 0 0 1px rgba(16,24,40,.06);
        }
        .adj-page .adj-footer-meta,
        .adj-page .adj-footer-actions{ display:none; }
        .adj-page .adj-mobile-action-trigger{
            display:inline-flex!important;
            align-items:center;
            justify-content:center;
            gap:.35rem;
            width:auto;
            min-width:96px;
            min-height:44px;
            margin-left:auto;
            padding:.35rem .75rem!important;
            border-radius:var(--adj-radius-control)!important;
            font-size:.72rem!important;
            font-weight:750!important;
        }
        .adj-page{
            padding-bottom:calc(5rem + var(--mobile-bottom-nav-clearance,calc(70px + env(safe-area-inset-bottom,0px) + 1rem)));
        }
        .adj-page .modal-footer .btn{ min-height:44px; }
        .adj-page .adj-mobile-action-sheet .modal-dialog{
            display:flex;
            align-items:flex-end;
            width:100%;
            max-width:none;
            min-height:100%;
            margin:0;
        }
        .adj-page .adj-mobile-action-sheet .modal-content{
            width:100%;
            border-bottom:0!important;
            border-radius:20px 20px 0 0!important;
            padding-bottom:env(safe-area-inset-bottom,0px);
        }
        .adj-page .adj-mobile-action-sheet .modal-header{
            align-items:flex-start;
            padding:.9rem 1rem .65rem;
        }
        .adj-page .adj-mobile-action-sheet .modal-body{ padding:.6rem 1rem 1rem; }
        .adj-page .adj-mobile-action-sheet .modal-body .btn{
            display:flex;
            align-items:center;
            justify-content:center;
            gap:.35rem;
            width:100%;
            min-height:48px;
            border-radius:var(--adj-radius-control)!important;
            font-weight:750!important;
        }
        .adj-page .adj-mobile-action-sheet .modal-body .btn-adj-outline{
            color:var(--adj-muted)!important;
            background:color-mix(in srgb,var(--adj-surface) 86%,var(--adj-surface-muted) 14%)!important;
            border-color:var(--adj-line)!important;
            text-decoration:none!important;
        }
    }
    @media(prefers-reduced-motion:reduce){
        .adj-page *, .adj-page *::before, .adj-page *::after{ scroll-behavior:auto!important; transition-duration:.01ms!important; animation-duration:.01ms!important; }
    }
</style>
@endpush

@section('content')
    @php
        $user = auth()->user();
        $isOwner = $user && ($user->role ?? null) === 'owner';
        $submitLabel = $isOwner ? 'Simpan Draft' : 'Kirim ke Owner';
    @endphp

    <div class="adj-page">
        {{-- PAGE HEADER --}}
        <header class="adj-topbar">
            <div class="adj-topbar-copy">
                <span class="adj-eyebrow"><i class="bi bi-box-seam" aria-hidden="true"></i>Persediaan · Koreksi stok</span>
                <h1 class="adj-topbar-title">Adjustment manual</h1>
                <p class="adj-topbar-description">Atur stok akhir untuk satu atau beberapa item, lalu simpan sebagai draft atau kirim ke Owner untuk finalisasi.</p>
            </div>
            <div class="adj-topbar-actions">
                <span class="adj-badge {{ $isOwner ? 'is-owner' : '' }}">
                    <i class="bi {{ $isOwner ? 'bi-lightning-charge' : 'bi-shield-check' }}" aria-hidden="true"></i>
                    {{ $isOwner ? 'Langsung koreksi stok' : 'Menunggu Owner' }}
                </span>
            </div>
        </header>

    <form method="POST" action="{{ route('inventory.adjustments.manual.store') }}" id="adj-form">
        @csrf

        {{-- STEP 1: Gudang --}}
        <div class="adj-card">
            <div class="adj-card-body">
                <div class="adj-gate">
                    <div class="adj-gate-copy">
                        <div class="adj-section-label">Pilih Gudang</div>
                        <div class="adj-gate-title">Mulai dari gudang dulu</div>
                        <div class="adj-gate-note">
                            Setelah gudang dipilih, bagian tanggal, catatan, pencarian item, dan tabel stok akan muncul otomatis.
                        </div>
                    </div>
                    <div class="adj-gate-field">
                        <label class="adj-field-label" for="warehouse_id">Gudang</label>
                        <select name="warehouse_id" id="warehouse_id" class="form-select form-select-sm" required>
                            <option value="">Pilih gudang…</option>
                            @foreach ($warehouses as $wh)
                                <option value="{{ $wh->id }}" {{ old('warehouse_id', $prefillWarehouseId ?? null) == $wh->id ? 'selected' : '' }}>{{ $wh->code }} — {{ $wh->name }}</option>
                            @endforeach
                        </select>
                        @error('warehouse_id') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        <div class="adj-gate-hint" id="warehouse-lock-note">Pilih gudang untuk membuka form adjustment.</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="adj-workflow-bar" id="workflow-bar">
            <div class="adj-workflow-leading">
                <span class="adj-workflow-icon" aria-hidden="true"><i class="bi bi-check2-circle"></i></span>
                <div class="adj-workflow-meta">
                    <span class="adj-workflow-step">Gudang siap · langkah berikutnya</span>
                    <span class="adj-workflow-selected" id="selected-warehouse-label">-</span>
                    <div class="adj-workflow-desc">Isi tanggal, catatan, lalu atur stok akhir di tabel. {{ $isOwner ? 'Simpan sebagai draft.' : 'Kirim untuk finalisasi Owner; stok belum berubah.' }}</div>
                </div>
            </div>
        </div>

        <div id="workflow-sections" class="d-none">
            {{-- STEP 2: Informasi Adjustment --}}
            <div class="adj-card">
                <div class="adj-card-body">
                    <div class="adj-section-label">Informasi Adjustment</div>
                    <div class="row adj-form-row">
                        <div class="col-6 col-lg-2">
                            <label class="adj-field-label" for="adjustment-date">Tanggal</label>
                            <input type="date" id="adjustment-date" class="form-control form-control-sm" name="date" value="{{ old('date', now()->toDateString()) }}" required>
                            @error('date') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-12 col-lg-5">
                            <label class="adj-field-label" for="adjustment-notes">Catatan</label>
                            <input type="text" id="adjustment-notes" name="notes" class="form-control form-control-sm" placeholder="Opsional" value="{{ old('notes') }}">
                            @error('notes') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-12 col-lg-5">
                            <label class="adj-field-label">&nbsp;</label>
                            <div class="adj-search-hint adj-info-hint">
                                Tanggal dipakai sebagai tanggal mutasi stok saat draft dipost.
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- STEP 3: Cari Item --}}
            <div class="adj-card is-sticky-step">
                <div class="adj-card-body">
                    <div class="adj-section-label">Cari Item</div>
                    <div class="row adj-form-row align-items-end">
                        <div class="col-12 col-lg-8">
                            <label class="adj-field-label" for="item-search">Cari kode / nama / lot (pisahkan dengan spasi)</label>
                            <div class="adj-search-control">
                                <i class="bi bi-search" aria-hidden="true"></i>
                                <input type="search" id="item-search" class="form-control form-control-sm"
                                    placeholder="Contoh: K001 K002 atau kaos" value="{{ $prefillItemCode ?? '' }}"
                                    autocomplete="off" spellcheck="false" aria-describedby="items-hint">
                            </div>
                        </div>
                        <div class="col-12 col-lg-2">
                            <label class="adj-field-label">&nbsp;</label>
                            <button type="button" class="btn btn-sm btn-adj-primary w-100" id="search-item-btn"><i class="bi bi-search" aria-hidden="true"></i>Cari</button>
                        </div>
                        <div class="col-12 col-lg-2">
                            <label class="adj-field-label">&nbsp;</label>
                            <button type="button" class="btn btn-sm btn-adj-outline w-100" data-bs-toggle="modal" data-bs-target="#quickItemModal"><i class="bi bi-plus-lg" aria-hidden="true"></i>Item baru</button>
                        </div>
                    </div>
                    <div class="adj-search-hint" id="items-hint" role="status" aria-live="polite">Pilih gudang dulu. Setelah itu cari item untuk menyorot baris di tabel.</div>
                </div>
            </div>

            {{-- STEP 4: Tabel --}}
            <div class="adj-card">
                <div class="adj-card-body adj-table-card-body">
                    <div class="adj-section-heading">
                        <div>
                            <div class="adj-section-label">Daftar item</div>
                            <div class="adj-section-subtitle">Masukkan stok akhir. Sistem menghitung selisih otomatis.</div>
                        </div>
                        <select id="show_changed_only" class="form-select form-select-sm" aria-label="Filter daftar item">
                            <option value="0">Semua item</option>
                            <option value="1">Ada selisih</option>
                        </select>
                    </div>

                    <div class="adj-table-scroll">
                        <table class="table table-sm align-middle adj-table" id="lines-table">
                            <thead>
                                <tr>
                                    <th style="width:36px;">#</th>
                                    <th>Item</th>
                                    <th class="text-end" style="width:100px;">Stok Saat Ini</th>
                                    <th class="text-end" style="width:140px;">Stok Akhir</th>
                                    <th class="text-end" style="width:100px;">Selisih</th>
                                    <th style="width:160px;">Alasan</th>
                                </tr>
                            </thead>
                            <tbody>
                                {{-- render via JS --}}
                            </tbody>
                        </table>
                    </div>

                    <div class="adj-divider"></div>
                    <div class="adj-form-error d-none" id="form-error"></div>

                    <div class="adj-footer">
                        <div class="adj-footer-meta">
                            <span class="adj-footer-hint" id="summary-change">Selisih: 0.00</span>
                        </div>
                        <button type="button"
                                class="btn btn-sm btn-adj-outline adj-mobile-action-trigger"
                                id="mobile-action-trigger"
                                data-bs-toggle="modal"
                                data-bs-target="#mobileActionSheet"
                                aria-controls="mobileActionSheet"
                                aria-haspopup="dialog">
                            <i class="bi bi-three-dots-vertical" aria-hidden="true"></i>
                            <span>Aksi</span>
                        </button>
                        <div class="adj-footer-actions" aria-label="Aksi adjustment">
                            <a href="{{ route('inventory.adjustments.index') }}" class="btn btn-sm btn-adj-outline">
                                <i class="bi bi-x-lg" aria-hidden="true"></i>Batal
                            </a>
                            <button type="button" class="btn btn-sm btn-adj-outline" id="clear-all">
                                <i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i>Reset semua
                            </button>
                            <button type="submit" class="btn btn-sm btn-adj-primary">
                                <i class="bi bi-check2" aria-hidden="true"></i>{{ $submitLabel }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>

    {{-- MODAL: Item Baru --}}
    <div class="modal fade adj-modal" id="quickItemModal" tabindex="-1" aria-labelledby="quickItemModalTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header py-2">
                    <h2 class="modal-title mb-0" id="quickItemModalTitle">Item baru</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row adj-form-row">
                        <div class="col-5">
                            <label class="adj-field-label" for="quick-item-code">Kode</label>
                            <input type="text" id="quick-item-code" class="form-control form-control-sm text-mono" autocomplete="off">
                        </div>
                        <div class="col-7">
                            <label class="adj-field-label" for="quick-item-name">Nama</label>
                            <input type="text" id="quick-item-name" class="form-control form-control-sm" autocomplete="off">
                        </div>
                        <div class="col-4">
                            <label class="adj-field-label" for="quick-item-unit">Satuan</label>
                            <input type="text" id="quick-item-unit" class="form-control form-control-sm" value="pcs" autocomplete="off">
                        </div>
                        <div class="col-8">
                            <label class="adj-field-label" for="quick-item-role">Role</label>
                            <select id="quick-item-role" class="form-select form-select-sm">
                                @foreach (($itemRoles ?? collect()) as $role)
                                    <option value="{{ $role->id }}" @selected($role->code === 'RM')>{{ $role->code }} — {{ $role->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="adj-field-label" for="quick-item-category">Kategori</label>
                            <select id="quick-item-category" class="form-select form-select-sm">
                                <option value="">Tanpa kategori</option>
                                @foreach (($itemCategories ?? collect()) as $category)
                                    <option value="{{ $category->id }}">{{ $category->code }} — {{ $category->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="text-danger small mt-2 d-none" id="quick-item-error"></div>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-sm btn-adj-outline" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-sm btn-adj-primary" id="quick-item-save">Buat & Tambah</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade adj-modal" id="confirmSaveModal" tabindex="-1" aria-labelledby="confirmSaveModalTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header py-2">
                    <h2 class="modal-title mb-0" id="confirmSaveModalTitle">Konfirmasi {{ $isOwner ? 'simpan draft' : 'kirim ke Owner' }}</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning py-2 px-3 mb-3">
                        Pastikan data berikut sudah benar sebelum {{ $isOwner ? 'draft disimpan' : 'koreksi dikirim ke Owner' }}.
                    </div>
                    <div class="d-grid gap-2">
                        <div class="d-flex justify-content-between gap-3">
                            <span class="text-muted">Gudang</span>
                            <strong class="text-end" id="confirm-warehouse-label">-</strong>
                        </div>
                        <div class="d-flex justify-content-between gap-3">
                            <span class="text-muted">Tanggal</span>
                            <strong class="text-end" id="confirm-date-label">-</strong>
                        </div>
                        <div class="d-flex justify-content-between gap-3">
                            <span class="text-muted">Item berubah</span>
                            <strong class="text-end" id="confirm-changed-label">0</strong>
                        </div>
                        <div class="d-flex justify-content-between gap-3">
                            <span class="text-muted">Total selisih</span>
                            <strong class="text-end" id="confirm-total-label">0.00</strong>
                        </div>
                        <div class="d-flex justify-content-between gap-3">
                            <span class="text-muted">Catatan</span>
                            <strong class="text-end" id="confirm-notes-label">-</strong>
                        </div>
                    </div>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-sm btn-adj-outline" data-bs-dismiss="modal">Cek lagi</button>
                    <button type="button" class="btn btn-sm btn-adj-primary" id="confirm-save-draft">Ya, {{ $isOwner ? 'simpan draft' : 'kirim ke Owner' }}</button>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade adj-modal adj-mobile-action-sheet" id="mobileActionSheet" tabindex="-1" aria-labelledby="mobileActionSheetTitle" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <div class="adj-section-label mb-1">Adjustment manual</div>
                        <h2 class="modal-title mb-0" id="mobileActionSheetTitle">Aksi adjustment</h2>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup aksi"></button>
                </div>
                <div class="modal-body">
                    <div class="d-grid gap-2">
                        <button type="button" class="btn btn-adj-primary" id="mobile-submit">
                            <i class="bi bi-check2" aria-hidden="true"></i>{{ $submitLabel }}
                        </button>
                        <button type="button" class="btn btn-adj-outline" id="mobile-clear-all">
                            <i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i>Reset semua
                        </button>
                        <a href="{{ route('inventory.adjustments.index') }}" class="btn btn-adj-outline">
                            <i class="bi bi-x-lg" aria-hidden="true"></i>Batal
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </div>
@endsection

@push('scripts')
    <script>
        (function() {
            const warehouseSelect = document.getElementById('warehouse_id');
            const tbody = document.querySelector('#lines-table tbody');
            const itemsHint = document.getElementById('items-hint');
            const itemSearch = document.getElementById('item-search');
            const searchBtn = document.getElementById('search-item-btn');
            const showChangedOnly = document.getElementById('show_changed_only');
            const summaryChange = document.getElementById('summary-change');
            const form = document.getElementById('adj-form');
            const clearAllBtn = document.getElementById('clear-all');
            const formError = document.getElementById('form-error');
            const quickModalEl = document.getElementById('quickItemModal');
            const quickCode = document.getElementById('quick-item-code');
            const quickName = document.getElementById('quick-item-name');
            const quickUnit = document.getElementById('quick-item-unit');
            const quickRole = document.getElementById('quick-item-role');
            const quickCategory = document.getElementById('quick-item-category');
            const quickError = document.getElementById('quick-item-error');
            const quickSave = document.getElementById('quick-item-save');
            const confirmSaveModalEl = document.getElementById('confirmSaveModal');
            const confirmWarehouseLabel = document.getElementById('confirm-warehouse-label');
            const confirmDateLabel = document.getElementById('confirm-date-label');
            const confirmChangedLabel = document.getElementById('confirm-changed-label');
            const confirmTotalLabel = document.getElementById('confirm-total-label');
            const confirmNotesLabel = document.getElementById('confirm-notes-label');
            const confirmSaveBtn = document.getElementById('confirm-save-draft');
            const mobileActionSheetEl = document.getElementById('mobileActionSheet');
            const mobileSubmitBtn = document.getElementById('mobile-submit');
            const mobileClearAllBtn = document.getElementById('mobile-clear-all');
            const workflowBar = document.getElementById('workflow-bar');
            const workflowSections = document.getElementById('workflow-sections');
            const selectedWarehouseLabel = document.getElementById('selected-warehouse-label');
            const warehouseLockNote = document.getElementById('warehouse-lock-note');
            const prefillWarehouseId = @json($prefillWarehouseId ?? null);
            const prefillItemId = @json($prefillItemId ?? null);
            const prefillItemCode = @json($prefillItemCode ?? '');
            const prefillQtyChange = Number(@json($prefillQtyChange ?? 0));
            const prefillLines = @json($prefillLines ?? []);

            const itemsUrl = @json(route('inventory.adjustments.items_for_warehouse', [], false));
            const quickItemUrl = @json(route('inventory.adjustments.items.quick_store', [], false));

            let warehouseItems = [];
            let searchTimer = null;
            let allowNativeSubmit = false;

            function searchTerms(value) {
                return [...new Set(
                    String(value || '')
                        .trim()
                        .toLowerCase()
                        .split(/\s+/)
                        .filter(Boolean)
                )];
            }

            function rowMatchesTerms(tr, terms) {
                const searchKey = tr.dataset.searchKey || '';
                return terms.some(term => searchKey.includes(term));
            }

            function fmt(n) {
                const x = parseFloat(n);
                if (isNaN(x)) return '0.00';
                return x.toFixed(2);
            }

            function getWarehouseLabel() {
                const opt = warehouseSelect?.options?.[warehouseSelect.selectedIndex];
                if (!opt || !opt.value) return '-';
                return (opt.textContent || '').trim() || '-';
            }

            function setWorkflowReady(isReady) {
                workflowBar?.classList.toggle('is-visible', isReady);
                workflowSections?.classList.toggle('d-none', !isReady);
                if (selectedWarehouseLabel) {
                    selectedWarehouseLabel.textContent = isReady ? getWarehouseLabel() : '-';
                }
                if (warehouseLockNote) {
                    warehouseLockNote.textContent = isReady
                        ? 'Gudang dipilih. Silakan isi tanggal, catatan, lalu stok akhir.'
                        : 'Pilih gudang untuk membuka form adjustment.';
                }
            }

            function buildRow(item, idx) {
                const onHand = parseFloat(item.on_hand) || 0;
                const lotId = item.lot_id || '';
                const lotCode = item.lot_code || '';
                const searchKey = [
                    item.code || '',
                    item.name || '',
                    lotCode || ''
                ].join(' ').toLowerCase();

                const tr = document.createElement('tr');
                tr.dataset.code = (item.code || '').toLowerCase();
                tr.dataset.name = (item.name || '').toLowerCase();
                tr.dataset.lotCode = (lotCode || '').toLowerCase();
                tr.dataset.searchKey = searchKey;
                tr.dataset.idx = String(idx);
                tr.dataset.notInWarehouse = item.not_in_warehouse ? '1' : '0';

                tr.innerHTML = `
            <td class="adj-index-cell text-muted small mobile-hide">${idx + 1}</td>
            <td>
                <div class="adj-row-main">
                    <div class="adj-item-main">
                        <span class="adj-item-code text-mono">${item.code ?? ''}</span>
                        <div class="adj-item-meta">
                            <span class="adj-item-name">${item.name ?? ''}</span>
                            <div class="adj-item-badges">
                                ${item.not_in_warehouse ? '<span class="item-badge">baru</span>' : ''}
                                ${lotCode ? `<span class="item-badge bg-secondary text-white border-secondary">Lot: ${lotCode}</span>` : ''}
                            </div>
                        </div>
                    </div>
                    <span class="diff-display mobile-hide text-mono" style="font-size:.8rem;">0.00</span>
                </div>
                <div class="adj-row-meta mobile-hide">
                    <span>Stok: <strong class="text-mono"><span class="on-hand" data-on-hand="${onHand}">${fmt(onHand)}</span></strong></span>
                </div>
                <input type="hidden" class="row-item-id" value="${item.id}">
                <input type="hidden" class="row-lot-id" value="${lotId}">
            </td>
            <td class="adj-on-hand-cell text-end mobile-hide text-mono">
                <span class="on-hand desktop-only" data-on-hand="${onHand}">${fmt(onHand)}</span>
            </td>
            <td class="text-end">
                <div class="qty-stepper">
                    <input type="number" class="form-control form-control-sm text-end physical-input qty-stepper-input" step="0.01" min="0" inputmode="decimal" placeholder="0.00">
                    <div class="qty-stepper-actions">
                        <button type="button" class="qty-stepper-btn qty-step-up" title="Tambah stok" aria-label="Tambah stok">
                            <i class="bi bi-chevron-up"></i>
                        </button>
                        <button type="button" class="qty-stepper-btn qty-step-down" title="Kurangi stok" aria-label="Kurangi stok">
                            <i class="bi bi-chevron-down"></i>
                        </button>
                    </div>
                </div>
                <input type="hidden" class="qty-change-input" value="">
            </td>
            <td class="adj-diff-cell text-end mobile-hide text-mono">
                <span class="diff-display">0.00</span>
            </td>
            <td class="adj-notes-cell">
                <input type="text" class="form-control form-control-sm notes-input" style="font-size:.76rem;" placeholder="Alasan baris">
            </td>
        `;

                const physical = tr.querySelector('.physical-input');
                const notes = tr.querySelector('.notes-input');
                physical.addEventListener('focus', () => physical.select());
                physical.addEventListener('blur', () => {
                    if (physical.value !== '' && !isNaN(parseFloat(physical.value))) {
                        physical.value = fmt(physical.value);
                    }
                });
                physical.addEventListener('input', () => {
                    recalcRow(tr);
                    applyFilter();
                    updateSummary();
                });
                tr.querySelector('.qty-step-up')?.addEventListener('click', () => adjustPhysical(tr, 1));
                tr.querySelector('.qty-step-down')?.addEventListener('click', () => adjustPhysical(tr, -1));
                notes.addEventListener('input', () => {
                    notes.classList.remove('is-invalid');
                    clearFormError();
                });

                return tr;
            }

            function recalcRow(tr) {
                const onHandSpan = tr.querySelector('.on-hand');
                const physicalInp = tr.querySelector('.physical-input');
                const diffSpans = tr.querySelectorAll('.diff-display');
                const qtyChangeInp = tr.querySelector('.qty-change-input');

                const onHand = parseFloat(onHandSpan?.dataset.onHand || '0') || 0;
                const physical = parseFloat(physicalInp.value);

                diffSpans.forEach(s => s.classList.remove('diff-plus', 'diff-minus'));

                if (isNaN(physical)) {
                    diffSpans.forEach(s => s.textContent = '0.00');
                    qtyChangeInp.value = '';
                    tr.dataset.changed = '0';
                    return;
                }

                const diff = physical - onHand;
                qtyChangeInp.value = diff.toFixed(2);
                tr.dataset.changed = (Math.abs(diff) > 0.000001) ? '1' : '0';

                let text = diff.toFixed(2);
                if (diff > 0) { text = '+' + text; diffSpans.forEach(s => s.classList.add('diff-plus')); }
                if (diff < 0) { diffSpans.forEach(s => s.classList.add('diff-minus')); }
                diffSpans.forEach(s => s.textContent = text);
            }

            function adjustPhysical(tr, delta) {
                const physicalInp = tr.querySelector('.physical-input');
                const onHandSpan = tr.querySelector('.on-hand');
                if (!physicalInp) return;

                const onHand = parseFloat(onHandSpan?.dataset.onHand || '0') || 0;
                const current = parseFloat(physicalInp.value);
                const next = (isNaN(current) ? onHand : current) + delta;

                physicalInp.value = fmt(Math.max(0, next));
                physicalInp.dispatchEvent(new Event('input', { bubbles: true }));
                physicalInp.focus();
            }

            function renderRows() {
                tbody.innerHTML = '';
                if (!warehouseItems.length) {
                    tbody.innerHTML = `
                        <tr class="adj-empty-row">
                            <td colspan="6">
                                <div class="adj-empty">
                                    <div class="adj-empty-icon"><i class="bi bi-box-seam" aria-hidden="true"></i></div>
                                    <strong>Belum ada item di daftar</strong>
                                    <span>Cari kode atau nama item untuk menambahkannya ke koreksi stok.</span>
                                </div>
                            </td>
                        </tr>
                    `;
                    updateSummary();
                    return;
                }
                warehouseItems.forEach((item, idx) => { tbody.appendChild(buildRow(item, idx)); });
                applyFilter();
                updateSummary();
            }

            function applyPrefillAdjustment(line = {}) {
                const itemId = line.item_id || prefillItemId;
                const qtyChange = Number(line.qty_change ?? prefillQtyChange);
                if (!itemId || !qtyChange) return;
                const row = Array.from(tbody.querySelectorAll('tr')).find(tr =>
                    String(tr.querySelector('.row-item-id')?.value || '') === String(itemId)
                );
                if (!row) return;

                const physicalInput = row.querySelector('.physical-input');
                const onHand = parseFloat(row.querySelector('.on-hand')?.dataset.onHand || '0') || 0;
                if (physicalInput) {
                    physicalInput.value = fmt(onHand + qtyChange);
                    recalcRow(row);
                }
                const notesInput = row.querySelector('.notes-input');
                if (notesInput && !notesInput.value) {
                    notesInput.value = 'Koreksi dari kekurangan stok shipment';
                }
                applyFilter();
                updateSummary();
                flashRow(row);
            }

            function applyPrefillAdjustments() {
                const lines = Array.isArray(prefillLines) && prefillLines.length
                    ? prefillLines
                    : (prefillItemId && prefillQtyChange
                        ? [{ item_id: prefillItemId, item_code: prefillItemCode, qty_change: prefillQtyChange }]
                        : []);

                return lines.reduce((chain, line) => chain.then(() => {
                    const term = String(line.item_code || '').trim();
                    if (!term) {
                        applyPrefillAdjustment(line);
                        return null;
                    }

                    return fetchSearchItems(term, true).then(() => applyPrefillAdjustment(line));
                }), Promise.resolve()).then(() => {
                    applyFilter();
                    updateSummary();
                });
            }

            function applyFilter() {
                const terms = searchTerms(itemSearch.value);
                const changedOnly = showChangedOnly.value === '1';
                tbody.querySelectorAll('tr').forEach(tr => {
                    const changed = tr.dataset.changed === '1';
                    let ok = true;
                    if (terms.length) ok = rowMatchesTerms(tr, terms);
                    if (ok && changedOnly) ok = changed;
                    tr.style.display = ok ? '' : 'none';
                });
            }

            function updateSummary() {
                let total = 0, changed = 0, newItems = 0;
                tbody.querySelectorAll('tr').forEach(tr => {
                    if (tr.dataset.changed === '1') changed++;
                    if (tr.dataset.notInWarehouse === '1') newItems++;
                });
                tbody.querySelectorAll('.qty-change-input').forEach(inp => {
                    const v = parseFloat(inp.value);
                    if (!isNaN(v)) total += v;
                });
                let text = fmt(total);
                if (total > 0) text = '+' + text;
                summaryChange.textContent = 'Selisih: ' + text;
                if (itemsHint) {
                    if (!warehouseSelect.value) {
                        itemsHint.textContent = 'Pilih gudang dulu.';
                    } else if (!warehouseItems.length) {
                        itemsHint.textContent = 'Belum ada item. Cari kode / nama untuk memuat item.';
                    } else if (changed === 0) {
                        itemsHint.textContent = 'Stok akhir bisa diubah langsung di tabel. Klik panah atas/bawah atau cari item di atas untuk lompat ke baris.';
                    } else {
                        itemsHint.textContent = `${changed} item berubah, termasuk ${newItems} item baru.`;
                    }
                }
            }

            function updateConfirmModal() {
                if (confirmWarehouseLabel) confirmWarehouseLabel.textContent = getWarehouseLabel();
                if (confirmDateLabel) {
                    const dateInput = form.querySelector('input[name="date"]');
                    confirmDateLabel.textContent = dateInput?.value || '-';
                }
                if (confirmChangedLabel) {
                    const changed = tbody.querySelectorAll('tr[data-changed="1"]').length;
                    confirmChangedLabel.textContent = String(changed);
                }
                if (confirmTotalLabel) {
                    let total = 0;
                    tbody.querySelectorAll('.qty-change-input').forEach(inp => {
                        const v = parseFloat(inp.value);
                        if (!isNaN(v)) total += v;
                    });
                    let text = fmt(total);
                    if (total > 0) text = '+' + text;
                    confirmTotalLabel.textContent = text;
                }
                if (confirmNotesLabel) {
                    const notesInput = form.querySelector('input[name="notes"]');
                    const notes = (notesInput?.value || '').trim();
                    confirmNotesLabel.textContent = notes || '-';
                }
            }

            function showConfirmModal() {
                updateConfirmModal();
                bootstrap.Modal.getOrCreateInstance(confirmSaveModalEl).show();
            }

            function clearFormError() {
                if (!formError) return;
                formError.textContent = '';
                formError.classList.add('d-none');
            }

            function showFormError(message) {
                if (!formError) return;
                formError.textContent = message || 'Ada baris yang belum lengkap.';
                formError.classList.remove('d-none');
            }

            function snapshotInputs() {
                const state = {};
                tbody.querySelectorAll('tr').forEach(tr => {
                    const id = tr.querySelector('.row-item-id')?.value;
                    const lotId = tr.querySelector('.row-lot-id')?.value || '';
                    if (!id) return;
                    const key = id + '_' + lotId;
                    state[key] = {
                        physical: tr.querySelector('.physical-input')?.value || '',
                        notes: tr.querySelector('.notes-input')?.value || '',
                    };
                });
                return state;
            }

            function restoreInputs(state) {
                tbody.querySelectorAll('tr').forEach(tr => {
                    const id = tr.querySelector('.row-item-id')?.value;
                    const lotId = tr.querySelector('.row-lot-id')?.value || '';
                    const key = id + '_' + lotId;
                    if (!id || !state[key]) return;
                    const physical = tr.querySelector('.physical-input');
                    const notes = tr.querySelector('.notes-input');
                    if (physical) physical.value = state[key].physical || '';
                    if (notes) notes.value = state[key].notes || '';
                    recalcRow(tr);
                });
            }

            function mergeItems(items) {
                const byId = new Map(warehouseItems.map(item => [String(item.id) + '_' + (item.lot_id||''), item]));
                (items || []).forEach(item => {
                    const key = String(item.id) + '_' + (item.lot_id||'');
                    if (byId.has(key)) { byId.set(key, { ...byId.get(key), ...item }); }
                    else { byId.set(key, item); }
                });
                warehouseItems = Array.from(byId.values()).sort((a, b) => String(a.code || '').localeCompare(String(b.code || '')));
            }

            function flashRow(row) {
                row.classList.add('adj-row-focus');
                window.setTimeout(() => row.classList.remove('adj-row-focus'), 1400);
            }

            function focusItemByTerm(term) {
                const terms = searchTerms(term);
                if (!terms.length) return false;
                const rows = Array.from(tbody.querySelectorAll('tr'));
                // Fokus ke item pertama yang diminta, tetapi semua term tetap
                // ditampilkan oleh filter dan dimuat dari endpoint.
                const row = terms.reduce((found, needle) => found || rows.find(tr => rowMatchesTerms(tr, [needle])), null);
                if (!row) return false;
                row.scrollIntoView({ block: 'center', behavior: 'smooth' });
                flashRow(row);
                const input = row.querySelector('.physical-input');
                if (input) window.setTimeout(() => { input.focus(); input.select(); }, 180);
                return true;
            }

            function fetchSearchItems(term, focusAfter = false) {
                const warehouseId = warehouseSelect.value || '';
                if (!warehouseId) { itemsHint.textContent = 'Pilih gudang dulu.'; return Promise.resolve([]); }
                const terms = searchTerms(term).filter(searchTerm => searchTerm.length >= 2);
                if (!terms.length) { itemsHint.textContent = 'Ketik minimal 2 karakter per pencarian.'; return Promise.resolve([]); }
                const query = terms.join(' ');
                itemsHint.textContent = 'Mencari item…';
                return fetch(itemsUrl + '?warehouse_id=' + encodeURIComponent(warehouseId) + '&q=' + encodeURIComponent(query))
                    .then(res => { if (!res.ok) throw new Error('HTTP ' + res.status); return res.json(); })
                    .then(data => {
                        const state = snapshotInputs();
                        mergeItems(Array.isArray(data) ? data : []);
                        renderRows(); restoreInputs(state); applyFilter(); updateSummary();
                        itemsHint.textContent = data.length
                            ? `${data.length} item ditemukan. Stok akhir bisa diubah langsung di tabel.`
                            : 'Item tidak ditemukan.';
                        if (focusAfter && data.length) focusItemByTerm(term);
                        return data;
                    })
                    .catch(err => { console.error(err); itemsHint.textContent = 'Gagal mencari item.'; return []; });
            }

            function resetQuickItemError() { if (quickError) { quickError.textContent = ''; quickError.classList.add('d-none'); } }
            function showQuickItemError(msg) { if (quickError) { quickError.textContent = msg || 'Data belum bisa disimpan.'; quickError.classList.remove('d-none'); } }

            quickModalEl?.addEventListener('shown.bs.modal', () => {
                resetQuickItemError();
                if (quickCode) { quickCode.value = ''; quickCode.focus(); quickCode.select(); }
            });
            quickCode?.addEventListener('input', () => {
                const s = quickCode.selectionStart, e = quickCode.selectionEnd;
                quickCode.value = quickCode.value.toUpperCase();
                try { quickCode.setSelectionRange(s, e); } catch (_) {}
            });
            quickSave?.addEventListener('click', async () => {
                resetQuickItemError();
                const code = (quickCode?.value || '').trim().toUpperCase();
                const name = (quickName?.value || '').trim();
                const unit = (quickUnit?.value || 'pcs').trim();
                if (!code || !name) { showQuickItemError('Kode dan nama wajib diisi.'); return; }
                quickSave.disabled = true; quickSave.textContent = 'Menyimpan…';
                try {
                    const res = await fetch(quickItemUrl, {
                        method: 'POST',
                        headers: { 'Accept':'application/json','Content-Type':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]')?.content||'','X-Requested-With':'XMLHttpRequest' },
                        body: JSON.stringify({ code, name, unit, item_role_id: quickRole?.value||null, item_category_id: quickCategory?.value||null }),
                    });
                    const json = await res.json();
                    if (!res.ok || !json.ok) { const errors = json.errors||{}; showQuickItemError(Object.values(errors).flat()[0]||json.message||'Data belum bisa disimpan.'); return; }
                    const item = json.item;
                    const state = snapshotInputs();
                    mergeItems([item]);
                    renderRows();
                    restoreInputs(state);
                    itemSearch.value = item.code || '';
                    applyFilter();
                    updateSummary();
                    itemsHint.textContent = 'Item baru dibuat dan masuk ke tabel.';
                    focusItemByTerm(item.code || item.name || '');
                    bootstrap.Modal.getOrCreateInstance(quickModalEl).hide();
                    if (quickName) quickName.value = '';
                    if (quickUnit) quickUnit.value = 'pcs';
                } catch (err) { console.error(err); showQuickItemError('Gagal membuat item baru.'); }
                finally { quickSave.disabled = false; quickSave.textContent = 'Buat & Tambah'; }
            });

            function loadItemsForWarehouse(warehouseId) {
                if (!warehouseId) {
                    warehouseItems = [];
                    tbody.innerHTML = '';
                    if (itemsHint) itemsHint.textContent = 'Pilih gudang dulu.';
                    if (summaryChange) summaryChange.textContent = 'Selisih: 0.00';
                    updateSummary();
                    return;
                }
                itemsHint.textContent = 'Memuat item…';
                return fetch(itemsUrl + '?warehouse_id=' + encodeURIComponent(warehouseId))
                    .then(res => { if (!res.ok) throw new Error('HTTP ' + res.status); return res.json(); })
                    .then(data => {
                        warehouseItems = Array.isArray(data) ? data : [];
                        itemsHint.textContent = warehouseItems.length ? 'Isi stok akhir di tabel untuk setiap item.' : 'Belum ada stok. Cari kode / nama untuk memuat item.';
                        renderRows();
                    })
                    .catch(err => { console.error(err); warehouseItems = []; tbody.innerHTML = ''; itemsHint.textContent = 'Gagal memuat item.'; summaryChange.textContent = 'Selisih: 0.00'; updateSummary(); });
            }

            form.addEventListener('submit', function(e) {
                clearFormError();
                let outIndex = 0;
                let firstInvalidRow = null;
                tbody.querySelectorAll('tr').forEach(tr => {
                    tr.querySelectorAll('[data-named="1"]').forEach(el => el.remove());
                    const qtyChangeInput = tr.querySelector('.qty-change-input');
                    if (!qtyChangeInput) return;
                    const changed = tr.dataset.changed === '1';
                    const qtyChange = parseFloat(qtyChangeInput.value || '0');
                    if (!changed || isNaN(qtyChange) || Math.abs(qtyChange) < 0.000001) return;
                    const itemId = tr.querySelector('.row-item-id').value;
                    const lotId = tr.querySelector('.row-lot-id')?.value || '';
                    const notesInput = tr.querySelector('.notes-input');
                    const notes = (notesInput?.value || '').trim();
                    if (!notes) {
                        notesInput?.classList.add('is-invalid');
                        if (!firstInvalidRow) firstInvalidRow = tr;
                        return;
                    }
                    
                    const fields = [['item_id', itemId], ['qty_change', qtyChange.toFixed(2)], ['notes', notes]];
                    if (lotId) fields.push(['lot_id', lotId]);
                    
                    fields.forEach(([name, value]) => {
                        const h = document.createElement('input');
                        h.type = 'hidden'; h.name = `lines[${outIndex}][${name}]`; h.value = value; h.dataset.named = '1';
                        tr.appendChild(h);
                    });
                    outIndex++;
                });

                if (firstInvalidRow) {
                    e.preventDefault();
                    showFormError('Isi alasan pada baris yang diubah sebelum menyimpan.');
                    firstInvalidRow.scrollIntoView({ block: 'center', behavior: 'smooth' });
                    firstInvalidRow.querySelector('.notes-input')?.focus();
                    return;
                }

                if (!allowNativeSubmit) {
                    e.preventDefault();
                    showConfirmModal();
                }
            });

            clearAllBtn.addEventListener('click', function() {
                tbody.querySelectorAll('tr').forEach(tr => {
                    const inp = tr.querySelector('.physical-input');
                    const notes = tr.querySelector('.notes-input');
                    if (!inp || !notes) return;
                    inp.value = ''; notes.value = ''; recalcRow(tr);
                });
                applyFilter(); updateSummary();
            });

            mobileSubmitBtn?.addEventListener('click', () => {
                bootstrap.Modal.getOrCreateInstance(mobileActionSheetEl).hide();
                window.setTimeout(() => {
                    if (typeof form.requestSubmit === 'function') {
                        form.requestSubmit();
                    } else {
                        form.submit();
                    }
                }, 120);
            });

            mobileClearAllBtn?.addEventListener('click', () => {
                bootstrap.Modal.getOrCreateInstance(mobileActionSheetEl).hide();
                clearAllBtn?.click();
            });

            warehouseSelect.addEventListener('change', () => {
                const warehouseId = warehouseSelect.value || null;
                setWorkflowReady(!!warehouseId);
                loadItemsForWarehouse(warehouseId);
                if (warehouseId && workflowSections) {
                    window.setTimeout(() => workflowSections.scrollIntoView({ behavior: 'smooth', block: 'start' }), 80);
                }
            });
            itemSearch.addEventListener('focus', () => window.setTimeout(() => itemSearch.select(), 0));
            itemSearch.addEventListener('input', () => {
                applyFilter(); clearTimeout(searchTimer);
                const term = (itemSearch.value || '').trim();
                if (!term) {
                    updateSummary();
                    return;
                }
                searchTimer = setTimeout(() => fetchSearchItems(term), 350);
            });
            const runSearch = () => {
                const term = (itemSearch.value || '').trim();
                clearTimeout(searchTimer);
                if (!warehouseSelect.value) {
                    itemsHint.textContent = 'Pilih gudang dulu.';
                    return;
                }
                if (!term) {
                    applyFilter();
                    itemsHint.textContent = 'Ketik kode / nama item untuk mencari.';
                    return;
                }
                // Selalu ambil ulang hasil saat Enter agar term kedua tetap
                // dimuat walaupun term pertama sudah ada di tabel.
                fetchSearchItems(term, true);
                applyFilter();
            };
            itemSearch.addEventListener('keydown', (e) => {
                if (e.key !== 'Enter') return;
                e.preventDefault();
                runSearch();
            });
            searchBtn?.addEventListener('click', runSearch);
            showChangedOnly.addEventListener('change', applyFilter);

            confirmSaveBtn?.addEventListener('click', () => {
                allowNativeSubmit = true;
                bootstrap.Modal.getOrCreateInstance(confirmSaveModalEl).hide();
                window.setTimeout(() => {
                    if (typeof form.requestSubmit === 'function') {
                        form.requestSubmit();
                    } else {
                        form.submit();
                    }
                }, 120);
            });

            confirmSaveModalEl?.addEventListener('hidden.bs.modal', () => {
                allowNativeSubmit = false;
            });

            setWorkflowReady(!!warehouseSelect.value);
            if (warehouseSelect.value) {
                const initialLoad = loadItemsForWarehouse(warehouseSelect.value);
                if (prefillLines.length || prefillItemCode) {
                    Promise.resolve(initialLoad).then(applyPrefillAdjustments);
                }
            } else {
                loadItemsForWarehouse(null);
            }

            if (prefillWarehouseId && !warehouseSelect.value) {
                warehouseSelect.value = String(prefillWarehouseId);
                setWorkflowReady(true);
                const initialLoad = loadItemsForWarehouse(prefillWarehouseId);
                if (prefillLines.length || prefillItemCode) {
                    Promise.resolve(initialLoad).then(applyPrefillAdjustments);
                }
            }
        })();
    </script>
@endpush
