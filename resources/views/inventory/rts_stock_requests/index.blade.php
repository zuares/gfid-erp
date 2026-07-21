@extends('layouts.app')

@section('title', 'RTS • Permintaan')

@push('head')

<style>
    .page-wrap {
        max-width: 960px;
        margin-inline: auto;
        padding: 1rem .9rem 5rem;
    }

    body[data-theme="light"] .page-wrap {
        background: radial-gradient(circle at top left,
            rgba(59,130,246,.08) 0, rgba(45,212,191,.10) 30%, #f9fafb 65%);
    }

    /* ── filter bar ── */
    .filter-bar {
        display: flex;
        flex-wrap: wrap;
        gap: .5rem;
        align-items: center;
        margin-bottom: .7rem;
    }

    .f-input {
        height: 34px;
        padding: 0 .65rem;
        border-radius: 9px;
        border: 1px solid rgba(148,163,184,.30);
        background: var(--card);
        color: inherit;
        font-size: .80rem;
        outline: none;
        transition: border-color .12s, box-shadow .12s;
    }
    .f-input:focus {
        border-color: rgba(45,212,191,.55);
        box-shadow: 0 0 0 2px rgba(45,212,191,.12);
    }

    #inp-search { width: 190px; }

    /* ── date section ── */
    .date-section {
        display: inline-flex;
        align-items: center;
        border: 1px solid rgba(148,163,184,.30);
        border-radius: 10px;
        overflow: hidden;
        background: var(--card);
        height: 34px;
    }

    .date-section .ds-presets {
        display: flex;
        align-items: center;
        border-right: 1px solid rgba(148,163,184,.20);
    }

    .ds-preset-btn {
        height: 34px;
        padding: 0 .55rem;
        font-size: .74rem;
        font-weight: 700;
        border: none;
        border-right: 1px solid rgba(148,163,184,.14);
        background: transparent;
        color: inherit;
        cursor: pointer;
        opacity: .60;
        white-space: nowrap;
        transition: opacity .10s, background .10s;
    }
    .ds-preset-btn:last-child { border-right: none; }
    .ds-preset-btn:hover { opacity: 1; background: rgba(45,212,191,.08); }
    .ds-preset-btn.active { opacity: 1; background: rgba(45,212,191,.14); color: rgba(45,212,191,1); }

    .ds-divider {
        width: 1px; height: 20px;
        background: rgba(148,163,184,.22);
        flex-shrink: 0;
    }

    #inp-date {
        height: 34px;
        min-width: 130px;
        padding: 0 .6rem;
        border: none;
        background: transparent;
        color: inherit;
        font-size: .78rem;
        outline: none;
        cursor: pointer;
    }

    .ds-clear {
        padding: 0 .5rem;
        height: 34px;
        border: none;
        background: transparent;
        color: inherit;
        cursor: pointer;
        opacity: .35;
        font-size: .78rem;
    }
    .ds-clear:hover { opacity: .8; color: #ef4444; }

    .f-select {
        height: 34px;
        padding: 0 .6rem;
        border-radius: 9px;
        border: 1px solid rgba(148,163,184,.30);
        background: var(--card);
        color: inherit;
        font-size: .78rem;
        outline: none;
        min-width: 130px;
    }
    .f-select:focus { border-color: rgba(45,212,191,.55); }

    .btn-reset {
        height: 34px;
        padding: 0 .65rem;
        border-radius: 9px;
        border: 1px solid rgba(148,163,184,.28);
        background: transparent;
        color: inherit;
        font-size: .76rem;
        cursor: pointer;
        opacity: .65;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
    }
    .btn-reset:hover { opacity: 1; border-color: rgba(239,68,68,.45); color: #ef4444; }

    /* table */
    .tbl-wrap {
        border: 1px solid rgba(148,163,184,.22);
        border-radius: 14px;
        overflow: auto;
        background: var(--card);
        max-height: calc(100vh - 220px);
    }

    table { width: 100%; border-collapse: collapse; }

    thead tr {
        background: rgba(148,163,184,.10);
        border-bottom: 1px solid rgba(148,163,184,.20);
    }

    th {
        position: sticky; top: 0; z-index: 2;
        background: rgba(148,163,184,.10);
        backdrop-filter: none;
        padding: .48rem .75rem;
        font-size: .67rem; font-weight: 900; opacity: .65;
        text-transform: uppercase; letter-spacing: .09em;
        text-align: left; white-space: nowrap;
        box-shadow: 0 1px 0 rgba(148,163,184,.20);
    }

    tbody tr {
        border-bottom: 1px solid rgba(148,163,184,.11);
        cursor: pointer;
        transition: background .10s;
    }
    tbody tr:last-child { border-bottom: 0; }
    tbody tr:hover { background: rgba(45,212,191,.05); }

    td { padding: .52rem .75rem; vertical-align: middle; font-size: .85rem; }

    .mono { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; }

    .col-date { width: 90px; white-space: nowrap; }
    .col-item { }
    .col-qty  { width: 80px; text-align: right; }
    .col-st   { width: 105px; }

    .doc-label {
        font-size: .66rem; opacity: .45;
        font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
        font-weight: 700; margin-bottom: .2rem;
        display: block; letter-spacing: .02em;
    }

    .chips { display: flex; flex-wrap: wrap; gap: .22rem; }

    .ic {
        display: inline-flex; align-items: center; gap: .2rem;
        padding: .11rem .36rem; border-radius: 6px;
        border: 1px solid rgba(148,163,184,.20);
        background: rgba(148,163,184,.07);
        font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
        font-size: .72rem; font-weight: 900; white-space: nowrap;
    }
    .ic span { font-weight: 400; opacity: .58; }

    .more-btn {
        font-size: .68rem; opacity: .50; cursor: pointer;
        padding: .11rem .36rem; border-radius: 6px;
        border: 1px solid rgba(148,163,184,.20);
        background: transparent; color: inherit; font-family: inherit;
    }

    .badge {
        display: inline-flex; padding: .15rem .50rem;
        border-radius: 999px; font-size: .70rem; font-weight: 800;
        border: 1px solid rgba(148,163,184,.35);
        background: rgba(148,163,184,.12); color: #475569; white-space: nowrap;
    }
    .badge.ok     { border-color: rgba(16,185,129,.40);  background: rgba(16,185,129,.12); color: #059669; }
    .badge.warn   { border-color: rgba(245,158,11,.40);  background: rgba(245,158,11,.12); color: #d97706; }
    .badge.danger { border-color: rgba(239,68,68,.40);   background: rgba(239,68,68,.12);  color: #dc2626; }

    .empty-row td { text-align: center; padding: 2.5rem; opacity: .45; font-size: .86rem; }

    /* flatpickr override */
    .flatpickr-input { background: var(--card) !important; color: inherit !important; }

        /* === Shipment-aligned UI override: RTS Stock Requests === */
        :root{
            --shp-accent:#334155;
            --shp-accent-2:#1f2937;
            --shp-border:rgba(148,163,184,.18);
            --shp-border-strong:rgba(148,163,184,.30);
            --shp-muted:#64748b;
        }

        .page-wrap{
            max-width:1040px!important;
            margin-inline:auto!important;
            padding:.75rem .75rem 4rem!important;
            background:transparent!important;
            border-radius:0!important;
        }

        body[data-theme="light"] .page-wrap,
        body[data-theme="dark"] .page-wrap{
            background:transparent!important;
        }

        .card,
        .card-main,
        .gf-card{
            border-radius:8px!important;
            border:1px solid var(--shp-border)!important;
            box-shadow:none!important;
            background:var(--card)!important;
        }

        body[data-theme="dark"] .card,
        body[data-theme="dark"] .card-main,
        body[data-theme="dark"] .gf-card{
            border-color:rgba(51,65,85,.85)!important;
        }

        .ship-topbar{
            position:sticky;
            top:0;
            z-index:300;
            display:flex;
            justify-content:space-between;
            align-items:center;
            gap:.6rem;
            flex-wrap:wrap;
            padding:.45rem .75rem;
            margin-inline:-.75rem;
            margin-bottom:.65rem;
            background:var(--card,#fff);
            border-bottom:1px solid var(--shp-border);
        }

        body[data-theme="dark"] .ship-topbar{
            background:var(--card,#0f172a);
        }

        .ship-title,
        .title{
            font-weight:750!important;
            font-size:1rem!important;
            letter-spacing:0!important;
            margin:0!important;
            line-height:1.25!important;
        }

        .ship-sub,
        .sub,
        .meta{
            color:var(--shp-muted)!important;
            font-size:.78rem!important;
            opacity:1!important;
        }

        body[data-theme="dark"] .ship-sub,
        body[data-theme="dark"] .sub,
        body[data-theme="dark"] .meta{
            color:#9ca3af!important;
        }

        .ship-kpis,
        .kpis{
            display:flex;
            flex-wrap:wrap;
            gap:.32rem;
            margin-top:.35rem;
        }

        .ship-kpi,
        .kpi{
            display:inline-flex;
            align-items:baseline;
            gap:.45rem;
            border-radius:7px;
            padding:.2rem .48rem;
            border:1px solid rgba(148,163,184,.28);
            background:transparent;
            font-size:.72rem;
        }

        body[data-theme="dark"] .ship-kpi,
        body[data-theme="dark"] .kpi{
            background:rgba(15,23,42,.96);
            border-color:rgba(51,65,85,.85);
        }

        .ship-kpi .lbl,
        .kpi .lbl{
            text-transform:none;
            letter-spacing:0;
            font-size:.66rem;
            color:#94a3b8;
        }

        .ship-kpi .val,
        .kpi .val{
            font-weight:650;
            color:var(--shp-accent);
        }

        body[data-theme="dark"] .ship-kpi .val,
        body[data-theme="dark"] .kpi .val{
            color:#e5e7eb;
        }

        .ship-controls,
        .actions,
        .btns{
            display:flex!important;
            gap:.5rem!important;
            align-items:center!important;
            flex-wrap:wrap!important;
            justify-content:flex-end!important;
        }

        .btn,
        .btn-outline,
        .btn-primary{
            border-radius:7px!important;
            padding:.34rem .78rem!important;
            box-shadow:none!important;
            font-weight:600!important;
            font-size:.82rem!important;
            min-height:32px;
            display:inline-flex;
            align-items:center;
            justify-content:center;
            text-decoration:none!important;
        }

        .btn-primary,
        .btn-ship-primary{
            background:var(--shp-accent)!important;
            border-color:var(--shp-accent)!important;
            color:#fff!important;
        }

        .btn-primary:hover,
        .btn-ship-primary:hover{
            background:var(--shp-accent-2)!important;
            border-color:var(--shp-accent-2)!important;
            color:#fff!important;
        }

        .btn-outline,
        .btn-ship-outline{
            color:#475569!important;
            background:transparent!important;
            border:1px solid rgba(148,163,184,.35)!important;
        }

        .btn-outline:hover,
        .btn-ship-outline:hover{
            background:rgba(148,163,184,.08)!important;
            color:#111827!important;
        }

        .header-row{
            position:sticky!important;
            top:0!important;
            z-index:300!important;
            display:flex!important;
            justify-content:space-between!important;
            align-items:center!important;
            gap:.6rem!important;
            flex-wrap:wrap!important;
            padding:.45rem .75rem!important;
            margin-inline:-.75rem!important;
            margin-bottom:.65rem!important;
            background:var(--card,#fff)!important;
            border-bottom:1px solid var(--shp-border)!important;
        }

        body[data-theme="dark"] .header-row{
            background:var(--card,#0f172a)!important;
        }

        .stats{
            gap:.42rem!important;
        }

        .stat{
            border-radius:8px!important;
            box-shadow:none!important;
            background:transparent!important;
            border:1px solid rgba(148,163,184,.22)!important;
            padding:.42rem .55rem!important;
        }

        .stat .k{
            font-size:.68rem!important;
            color:#94a3b8!important;
            opacity:1!important;
        }

        .stat .v{
            font-size:.95rem!important;
            font-weight:700!important;
            color:var(--shp-accent)!important;
        }

        .table-wrap{
            border-radius:8px!important;
            border:1px solid var(--shp-border)!important;
            background:transparent!important;
        }

        .tbl thead th,
        table thead th,
        th{
            font-size:.68rem!important;
            text-transform:none!important;
            letter-spacing:0!important;
            font-weight:650!important;
            color:#64748b!important;
        }

        .tbl th,
        .tbl td,
        th,
        td{
            padding:.52rem .62rem!important;
        }

        .item-code{
            font-weight:700!important;
            letter-spacing:0!important;
        }

        input[type="date"],
        input[type="number"],
        input[type="text"],
        textarea,
        select{
            border-radius:7px!important;
            font-size:.86rem!important;
        }

        @media(max-width:767.98px){
            .page-wrap{
                padding:.5rem .5rem 4rem!important;
            }

            .ship-topbar,
            .header-row{
                margin-inline:-.5rem!important;
                padding:.5rem .65rem!important;
            }

            .ship-title,
            .title{
                font-size:1.05rem!important;
            }

            .ship-sub,
            .sub{
                display:none!important;
            }

            .ship-kpis,
            .kpis{
                display:none!important;
            }

            .ship-controls,
            .actions,
            .btns{
                width:100%!important;
                justify-content:flex-start!important;
            }

            .ship-controls .btn,
            .actions .btn,
            .btns .btn{
                min-height:40px!important;
            }

            .card{
                border-radius:8px!important;
            }
        }

    
    /* === FINAL Shipment-consistent index: RTS Stock Requests === */
    :root{
        --ship-ink:#334155;
        --ship-ink-2:#1f2937;
        --ship-muted:#64748b;
        --ship-line:rgba(148,163,184,.18);
        --ship-line-2:rgba(148,163,184,.30);
    }

    .page-wrap{
        max-width:1040px!important;
        margin-inline:auto!important;
        padding:.75rem .75rem 4rem!important;
        background:transparent!important;
        border-radius:0!important;
    }

    body[data-theme="light"] .page-wrap,
    body[data-theme="dark"] .page-wrap{
        background:transparent!important;
    }

    .ship-topbar{
        position:sticky!important;
        top:0!important;
        z-index:310!important;
        display:flex!important;
        align-items:center!important;
        justify-content:space-between!important;
        gap:.6rem!important;
        flex-wrap:wrap!important;
        padding:.45rem .75rem!important;
        margin-inline:-.75rem!important;
        margin-bottom:.65rem!important;
        background:var(--card,#fff)!important;
        border-bottom:1px solid var(--ship-line)!important;
        border-radius:0!important;
        box-shadow:none!important;
    }

    body[data-theme="dark"] .ship-topbar{
        background:var(--card,#0f172a)!important;
        border-bottom-color:rgba(51,65,85,.85)!important;
    }

    .ship-title,
    .title{
        font-size:1rem!important;
        line-height:1.25!important;
        font-weight:750!important;
        letter-spacing:0!important;
        margin:0!important;
        color:inherit!important;
    }

    .ship-sub,
    .sub,
    .meta{
        font-size:.78rem!important;
        line-height:1.35!important;
        color:var(--ship-muted)!important;
        opacity:1!important;
        margin-top:.12rem!important;
    }

    .ship-kpis,
    .kpis{
        display:flex!important;
        flex-wrap:wrap!important;
        gap:.32rem!important;
        margin-top:.35rem!important;
    }

    .ship-kpi,
    .kpi{
        display:inline-flex!important;
        align-items:baseline!important;
        gap:.45rem!important;
        border-radius:7px!important;
        padding:.2rem .48rem!important;
        border:1px solid rgba(148,163,184,.28)!important;
        background:transparent!important;
        font-size:.72rem!important;
        font-weight:500!important;
        line-height:1.2!important;
        color:inherit!important;
    }

    .ship-kpi .lbl,
    .kpi .lbl{
        font-size:.66rem!important;
        color:#94a3b8!important;
        letter-spacing:0!important;
        text-transform:none!important;
        font-weight:500!important;
    }

    .ship-kpi .val,
    .kpi .val{
        color:var(--ship-ink)!important;
        font-weight:650!important;
    }

    .ship-controls,
    .actions,
    .btns{
        display:flex!important;
        align-items:center!important;
        justify-content:flex-end!important;
        gap:.5rem!important;
        flex-wrap:wrap!important;
    }

    .btn,
    .btn-primary,
    .btn-outline,
    .btn-reset{
        border-radius:7px!important;
        min-height:32px!important;
        padding:.34rem .78rem!important;
        font-size:.82rem!important;
        font-weight:600!important;
        line-height:1.15!important;
        box-shadow:none!important;
        text-decoration:none!important;
        display:inline-flex!important;
        align-items:center!important;
        justify-content:center!important;
        gap:.35rem!important;
    }

    .btn-primary{
        background:var(--ship-ink)!important;
        border-color:var(--ship-ink)!important;
        color:#fff!important;
    }

    .btn-primary:hover{
        background:var(--ship-ink-2)!important;
        border-color:var(--ship-ink-2)!important;
        color:#fff!important;
    }

    .btn-outline,
    .btn-reset{
        background:transparent!important;
        color:#475569!important;
        border:1px solid rgba(148,163,184,.35)!important;
        opacity:1!important;
    }

    .btn-outline:hover,
    .btn-reset:hover{
        background:rgba(148,163,184,.08)!important;
        color:#111827!important;
        border-color:rgba(148,163,184,.45)!important;
    }

    .filter-bar{
        display:flex!important;
        flex-wrap:wrap!important;
        gap:.45rem!important;
        align-items:center!important;
        margin:.65rem 0!important;
        padding:.55rem!important;
        border:1px solid var(--ship-line)!important;
        border-radius:8px!important;
        background:var(--card,#fff)!important;
        box-shadow:none!important;
    }

    body[data-theme="dark"] .filter-bar{
        background:var(--card,#0f172a)!important;
        border-color:rgba(51,65,85,.85)!important;
    }

    .f-input,
    .f-select,
    #inp-date,
    .date-section{
        height:32px!important;
        min-height:32px!important;
        border-radius:7px!important;
        font-size:.82rem!important;
        border-color:rgba(148,163,184,.32)!important;
        background:var(--card,#fff)!important;
        color:inherit!important;
        box-shadow:none!important;
    }

    .f-input:focus,
    .f-select:focus,
    .rts-date-picker.flatpickr-input:focus{
        border-color:rgba(100,116,139,.55)!important;
        box-shadow:0 0 0 2px rgba(100,116,139,.10)!important;
    }

    .ds-preset-btn{
        height:32px!important;
        font-size:.7rem!important;
        font-weight:600!important;
        opacity:.72!important;
    }

    .ds-preset-btn:hover,
    .ds-preset-btn.active{
        background:rgba(148,163,184,.10)!important;
        color:var(--ship-ink)!important;
        opacity:1!important;
    }

    .tbl-wrap,
    .table-wrap{
        border-radius:8px!important;
        border:1px solid var(--ship-line)!important;
        background:var(--card,#fff)!important;
        box-shadow:none!important;
        max-height:calc(100vh - 190px)!important;
        overflow:auto!important;
    }

    body[data-theme="dark"] .tbl-wrap,
    body[data-theme="dark"] .table-wrap{
        background:var(--card,#0f172a)!important;
        border-color:rgba(51,65,85,.85)!important;
    }

    table{
        width:100%!important;
        border-collapse:collapse!important;
    }

    thead tr{
        background:transparent!important;
        border-bottom:1px solid var(--ship-line)!important;
    }

    th{
        position:sticky!important;
        top:0!important;
        z-index:2!important;
        background:var(--card,#fff)!important;
        padding:.48rem .62rem!important;
        font-size:.68rem!important;
        font-weight:650!important;
        color:#64748b!important;
        opacity:1!important;
        text-transform:none!important;
        letter-spacing:0!important;
        box-shadow:none!important;
        white-space:nowrap!important;
    }

    body[data-theme="dark"] th{
        background:var(--card,#0f172a)!important;
        color:#94a3b8!important;
    }

    td{
        padding:.50rem .62rem!important;
        font-size:.84rem!important;
        border-bottom:1px solid rgba(148,163,184,.12)!important;
        vertical-align:middle!important;
    }

    tbody tr{
        border-bottom:0!important;
        cursor:pointer!important;
        transition:background .10s ease!important;
    }

    tbody tr:hover{
        background:rgba(148,163,184,.06)!important;
    }

    .doc-label{
        font-size:.64rem!important;
        font-weight:600!important;
        opacity:.55!important;
        letter-spacing:0!important;
        margin-bottom:.12rem!important;
        text-transform:none!important;
    }

    .ic,
    .more-btn,
    .badge{
        border-radius:7px!important;
        font-size:.70rem!important;
        font-weight:650!important;
        padding:.13rem .42rem!important;
        letter-spacing:0!important;
    }

    .badge{
        border:1px solid rgba(148,163,184,.28)!important;
        background:rgba(148,163,184,.08)!important;
    }

    .badge.ok{
        border-color:rgba(16,185,129,.30)!important;
        background:rgba(16,185,129,.08)!important;
    }

    .badge.warn{
        border-color:rgba(245,158,11,.34)!important;
        background:rgba(245,158,11,.08)!important;
    }

    .badge.danger{
        border-color:rgba(239,68,68,.34)!important;
        background:rgba(239,68,68,.07)!important;
    }

    @media(max-width:767.98px){
        .page-wrap{
            padding:.5rem .5rem 4rem!important;
        }

        .ship-topbar{
            margin-inline:-.5rem!important;
            padding:.5rem .65rem!important;
            align-items:flex-start!important;
        }

        .ship-title,
        .title{
            font-size:1.05rem!important;
        }

        .ship-sub,
        .sub{
            display:none!important;
        }

        .ship-kpis,
        .kpis{
            display:none!important;
        }

        .ship-controls,
        .actions,
        .btns{
            width:100%!important;
            justify-content:flex-start!important;
        }

        .ship-controls .btn,
        .actions .btn,
        .btns .btn{
            min-height:40px!important;
            flex:1 1 auto!important;
        }

        .filter-bar{
            padding:.5rem!important;
            gap:.4rem!important;
        }

        #inp-search,
        .f-input,
        .f-select,
        .date-section{
            width:100%!important;
            min-width:0!important;
        }

        .tbl-wrap,
        .table-wrap{
            max-height:none!important;
            border-radius:8px!important;
        }
    }


    /* === Header Shipment style: RTS Stock Requests index === */
    .ship-topbar{
        position:sticky!important;
        top:0!important;
        z-index:310!important;
        display:flex!important;
        justify-content:space-between!important;
        align-items:center!important;
        gap:.6rem!important;
        flex-wrap:wrap!important;
        padding:.45rem .75rem!important;
        margin-inline:-.75rem!important;
        margin-bottom:.65rem!important;
        background:var(--card,#fff)!important;
        border-bottom:1px solid rgba(148,163,184,.18)!important;
        border-radius:0!important;
        box-shadow:none!important;
    }

    body[data-theme="dark"] .ship-topbar{
        background:var(--card,#0f172a)!important;
        border-bottom-color:rgba(51,65,85,.85)!important;
    }

    .ship-title{
        font-size:1rem!important;
        line-height:1.25!important;
        font-weight:750!important;
        letter-spacing:0!important;
        margin:0!important;
    }

    .ship-sub{
        color:#64748b!important;
        font-size:.78rem!important;
        line-height:1.35!important;
        margin-top:.12rem!important;
        opacity:1!important;
    }

    .ship-kpis{
        display:flex!important;
        flex-wrap:wrap!important;
        gap:.32rem!important;
        margin-top:.35rem!important;
    }

    .ship-kpi{
        display:inline-flex!important;
        align-items:baseline!important;
        gap:.45rem!important;
        border-radius:7px!important;
        padding:.2rem .48rem!important;
        border:1px solid rgba(148,163,184,.28)!important;
        background:transparent!important;
        font-size:.72rem!important;
    }

    .ship-kpi .lbl{
        font-size:.66rem!important;
        color:#94a3b8!important;
        font-weight:500!important;
    }

    .ship-kpi .val{
        color:#334155!important;
        font-weight:650!important;
    }

    body[data-theme="dark"] .ship-kpi .val{
        color:#e5e7eb!important;
    }

    .ship-controls{
        display:flex!important;
        align-items:center!important;
        justify-content:flex-end!important;
        gap:.5rem!important;
        flex-wrap:wrap!important;
    }

    .btn-ship-primary{
        border-radius:7px!important;
        min-height:32px!important;
        padding:.34rem .78rem!important;
        font-size:.82rem!important;
        font-weight:600!important;
        background:#334155!important;
        border-color:#334155!important;
        color:#fff!important;
        box-shadow:none!important;
        display:inline-flex!important;
        align-items:center!important;
        justify-content:center!important;
        text-decoration:none!important;
    }

    .btn-ship-primary:hover{
        background:#1f2937!important;
        border-color:#1f2937!important;
        color:#fff!important;
    }

    @media(max-width:767.98px){
        .ship-topbar{
            margin-inline:-.5rem!important;
            padding:.5rem .65rem!important;
            align-items:flex-start!important;
        }

        .ship-title{
            font-size:1.05rem!important;
        }

        .ship-sub,
        .ship-kpis{
            display:none!important;
        }

        .ship-controls{
            width:100%!important;
            justify-content:flex-start!important;
        }

        .ship-controls .btn{
            width:100%!important;
            min-height:40px!important;
        }
    }


    /* === Fix RTS date picker double input === */
    .rts-date-picker.flatpickr-input{
        width:150px!important;
        min-width:150px!important;
        height:32px!important;
        border:0!important;
        background:transparent!important;
        border-radius:0!important;
        padding:0 .6rem!important;
        font-size:.82rem!important;
        box-shadow:none!important;
        cursor:pointer!important;
    }



    @media(max-width:767.98px){
        .rts-date-picker.flatpickr-input{
            width:100%!important;
            min-width:0!important;
        }

        .date-section{
            width:100%!important;
        }
    }


    /* === CLEAN FINAL RTS index, closest to Shipments === */
    .page-wrap{
        max-width:1040px!important;
        padding:.65rem .75rem 4rem!important;
        background:transparent!important;
    }

    .ship-topbar{
        position:sticky!important;
        top:0!important;
        z-index:340!important;
        margin:-.65rem -.75rem .65rem!important;
        padding:.52rem .78rem!important;
        background:var(--card,#fff)!important;
        border-bottom:1px solid rgba(148,163,184,.18)!important;
        border-radius:0!important;
        box-shadow:none!important;
    }

    .ship-title{
        font-size:1rem!important;
        font-weight:700!important;
        letter-spacing:0!important;
        line-height:1.25!important;
    }

    .ship-sub{
        font-size:.76rem!important;
        color:#64748b!important;
        opacity:1!important;
        margin-top:.1rem!important;
    }

    .ship-kpis{
        margin-top:.32rem!important;
        gap:.3rem!important;
    }

    .ship-kpi{
        border-radius:7px!important;
        padding:.18rem .45rem!important;
        font-size:.7rem!important;
        background:transparent!important;
        border:1px solid rgba(148,163,184,.24)!important;
    }

    .ship-kpi .lbl{
        font-size:.64rem!important;
        color:#94a3b8!important;
        font-weight:500!important;
    }

    .ship-kpi .val{
        color:#334155!important;
        font-weight:650!important;
    }

    .btn-ship-primary,
    .btn-primary{
        background:#334155!important;
        border-color:#334155!important;
        color:#fff!important;
        border-radius:7px!important;
        font-size:.8rem!important;
        font-weight:600!important;
        min-height:32px!important;
        padding:.34rem .75rem!important;
        box-shadow:none!important;
    }

    .filter-bar{
        margin:.55rem 0 .65rem!important;
        padding:.48rem!important;
        border:1px solid rgba(148,163,184,.18)!important;
        border-radius:8px!important;
        background:var(--card,#fff)!important;
        box-shadow:none!important;
    }

    .date-section{
        height:32px!important;
        min-height:32px!important;
        border-radius:7px!important;
        overflow:hidden!important;
    }

    .rts-date-picker.flatpickr-input{
        width: 170px !important; flex: 1;
        min-width: 170px !important; flex: 1;
        height:32px!important;
        border:0!important;
        background:transparent!important;
        border-radius:0!important;
        padding:0 .58rem!important;
        font-size:.8rem!important;
        cursor:pointer!important;
    }


    .f-input,
    .f-select{
        height:32px!important;
        min-height:32px!important;
        border-radius:7px!important;
        font-size:.8rem!important;
        box-shadow:none!important;
    }

    .tbl-wrap{
        border-radius:8px!important;
        border:1px solid rgba(148,163,184,.18)!important;
        background:var(--card,#fff)!important;
        box-shadow:none!important;
    }

    th{
        font-size:.67rem!important;
        font-weight:650!important;
        letter-spacing:0!important;
        text-transform:none!important;
        color:#64748b!important;
        background:var(--card,#fff)!important;
    }

    td{
        font-size:.83rem!important;
    }

    @media(max-width:767.98px){
        .ship-topbar{
            margin:-.5rem -.5rem .55rem!important;
            padding:.55rem .65rem!important;
        }

        .ship-sub,
        .ship-kpis{
            display:none!important;
        }

        .ship-controls{
            width:100%!important;
        }

        .ship-controls .btn{
            width:100%!important;
            min-height:40px!important;
        }

        #inp-date.rts-date-picker,
        .date-section{
            width:100%!important;
            min-width:0!important;
        }
    }


    /* === Force hide RTS hidden date fields === */
    #hid-from,
    #hid-to,
    input[name="date_from"]#hid-from,
    input[name="date_to"]#hid-to{
        display:none!important;
        width:0!important;
        height:0!important;
        min-width:0!important;
        min-height:0!important;
        padding:0!important;
        margin:0!important;
        border:0!important;
        opacity:0!important;
        position:absolute!important;
        pointer-events:none!important;
    }

</style>
@endpush

@section('content')
@php
    $role      = strtolower((string)(auth()->user()?->role ?? ''));
    $canManage = in_array($role, ['owner','admin'], true);
    $statusNow = $statusFilter ?? 'all';
    $periodNow = $period ?? 'all';
    $searchNow = $search ?? '';
    $dateFromNow = $dateFrom ?? '';
    $dateToNow   = $dateTo   ?? '';
    $fmt = fn($n) => rtrim(rtrim(number_format((float)$n, 2, '.', ''), '0'), '.');
    $THRESHOLD = 3;
@endphp

<div class="page-wrap">

    {{-- Header --}}
    <div class="ship-topbar">
        <div>
            <div class="ship-title">RTS • Permintaan Stok</div>
            <div class="ship-sub">Permintaan barang jadi dari PRD ke RTS.</div>

            <div class="ship-kpis">
                <span class="ship-kpi">
                    <span class="lbl">Total</span>
                    <span class="val">{{ number_format(isset($stockRequests) && method_exists($stockRequests, 'total') ? $stockRequests->total() : (isset($stockRequests) ? $stockRequests->count() : 0), 0, ',', '.') }}</span>
                </span>
                <span class="ship-kpi">
                    <span class="lbl">Halaman</span>
                    <span class="val">{{ number_format(isset($stockRequests) ? $stockRequests->count() : 0, 0, ',', '.') }}</span>
                </span>
                <span class="ship-kpi">
                    <span class="lbl">Gudang</span>
                    <span class="val">RTS</span>
                </span>
            </div>
        </div>

        @if ($canManage)
            <div class="ship-controls">
                <a href="{{ route('rts.stock-requests.create') }}" class="btn btn-sm btn-ship-primary">+ Buat Permintaan</a>
            </div>
        @endif
    </div>


    {{-- Filter bar --}}
    <form method="GET" action="{{ route('rts.stock-requests.index') }}" id="filterForm">
        <input type="hidden" name="date_from" id="hid-from" value="{{ $dateFromNow }}" data-gf-date="off">
        <input type="hidden" name="date_to" id="hid-to" value="{{ $dateToNow }}" data-gf-date="off">
        <input type="hidden" name="period"     id="hid-period" value="{{ $periodNow }}">

        <div class="filter-bar">

            {{-- Search --}}
            <input type="text" id="inp-search" name="search" class="f-input"
                value="{{ $searchNow }}" placeholder="Cari kode / item…" autocomplete="off">

            {{-- Date section: preset pills + range picker in one box --}}
            <div class="date-section">
                <div class="ds-presets">
                    <button type="button" class="ds-preset-btn {{ $periodNow==='today' ? 'active':'' }}"
                        data-period="today">Hari ini</button>
                    <button type="button" class="ds-preset-btn {{ $periodNow==='week' ? 'active':'' }}"
                        data-period="week">Minggu ini</button>
                    <button type="button" class="ds-preset-btn {{ $periodNow==='month' ? 'active':'' }}"
                        data-period="month">Bulan ini</button>
                </div>
                <div class="ds-divider"></div>
                <div style="display: flex; align-items: center; padding-left: .65rem; color: #94a3b8;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                </div>
                <input type="text" id="inp-date" class="f-input rts-date-picker" placeholder="Pilih tanggal…" readonly autocomplete="off" data-gf-date="off">
                @if($dateFromNow || $dateToNow || $periodNow !== 'all')
                    <button type="button" class="ds-clear" id="btn-clear-date" title="Hapus filter tanggal">✕</button>
                @endif
            </div>

            {{-- Status --}}
            <select name="status" class="f-select" onchange="this.form.submit()">
                <option value="all"       {{ $statusNow==='all'       ? 'selected':'' }}>Semua status</option>
                <option value="submitted" {{ $statusNow==='submitted' ? 'selected':'' }}>Menunggu</option>
                <option value="partial"   {{ $statusNow==='partial'   ? 'selected':'' }}>Sebagian</option>
                <option value="completed" {{ $statusNow==='completed' ? 'selected':'' }}>Selesai</option>
            </select>

            {{-- Reset --}}
            @if($searchNow || $dateFromNow || $dateToNow || $statusNow !== 'all' || $periodNow !== 'all')
                <a href="{{ route('rts.stock-requests.index') }}" class="btn-reset">✕ Reset</a>
            @endif
        </div>
    </form>

    {{-- Table --}}
    <div class="tbl-wrap">
        <table>
            <thead>
                <tr>
                    <th class="col-date">Tanggal</th>
                    <th class="col-item">Item</th>
                    <th class="col-qty">Qty</th>
                    <th class="col-st">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($stockRequests as $sr)
                @php
                    $req  = (float)($sr->total_requested_qty ?? 0);
                    $recv = (float)($sr->total_received_qty  ?? 0);
                    $pick = (float)($sr->total_picked_qty    ?? 0);
                    $sisa = max($req - $recv - $pick, 0);
                    $lines = $sr->lines ?? collect();
                    $url  = route('rts.stock-requests.show', $sr);

                    $statusMap = [
                        'completed' => ['ok',      'Selesai'],
                        'partial'   => ['warn',    'Sebagian'],
                        'submitted' => ['danger',  'Menunggu'],
                        'shipped'   => ['warn',    'Dikirim'],
                        'cancelled' => ['',        'Dibatal'],
                    ];
                    [$badgeType, $badgeText] = $statusMap[$sr->status] ?? ['', ucfirst($sr->status ?? '-')];
                @endphp
                <tr onclick="window.location='{{ $url }}'">
                    <td class="col-date mono" style="opacity:.62;font-size:.76rem">
                        {{ optional($sr->date)->format('d M Y') }}
                    </td>
                    <td class="col-item">
                        <span class="doc-label">{{ $sr->code }}</span>
                        <div class="chips">
                            @foreach($lines->take($THRESHOLD) as $ln)
                                <span class="ic">{{ $ln->item?->code ?? '—' }}<span>{{ $fmt($ln->qty_request) }}</span></span>
                            @endforeach
                            @if($lines->count() > $THRESHOLD)
                                @foreach($lines->skip($THRESHOLD) as $ln)
                                    <span class="ic" data-extra="{{ $sr->id }}" style="display:none">
                                        {{ $ln->item?->code ?? '—' }}<span>{{ $fmt($ln->qty_request) }}</span>
                                    </span>
                                @endforeach
                                <button class="more-btn" data-id="{{ $sr->id }}"
                                    onclick="event.stopPropagation();toggleMore(this)">
                                    +{{ $lines->count() - $THRESHOLD }}
                                </button>
                            @endif
                        </div>
                    </td>
                    <td class="col-qty mono" style="font-weight:900">{{ $fmt($req) }}</td>
                    <td class="col-st">
                        <span class="badge {{ $badgeType }}">{{ $badgeText }}</span>
                    </td>
                </tr>
                @empty
                <tr class="empty-row"><td colspan="4">Belum ada permintaan RTS.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div style="margin-top:1rem">{{ $stockRequests->links() }}</div>
</div>


<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
<script>
const hidFrom   = document.getElementById('hid-from');
const hidTo     = document.getElementById('hid-to');
const hidPeriod = document.getElementById('hid-period');
const form      = document.getElementById('filterForm');

function submitDate({ from='', to='', period='all' } = {}) {
    hidFrom.value   = from;
    hidTo.value     = to;
    hidPeriod.value = period;
    form.submit();
}

// ── Flatpickr (range + single) ────────────────────────────
const fp = flatpickr('#inp-date', {
    mode: 'range',
    dateFormat: 'j M Y',
    locale: 'id',
    altInput: false,
    defaultDate: [hidFrom.value, hidTo.value].filter(Boolean),
    onClose(dates) {
        if (dates.length === 1) {
            // single date → same from & to
            const d = flatpickr.formatDate(dates[0], 'Y-m-d');
            submitDate({ from: d, to: d });
        } else if (dates.length === 2) {
            submitDate({
                from: flatpickr.formatDate(dates[0], 'Y-m-d'),
                to:   flatpickr.formatDate(dates[1], 'Y-m-d'),
            });
        }
    },
});

// ── Preset pill buttons ───────────────────────────────────
document.querySelectorAll('.ds-preset-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        submitDate({ period: btn.dataset.period });
    });
});

// ── Clear date ────────────────────────────────────────────
document.getElementById('btn-clear-date')?.addEventListener('click', () => {
    submitDate({ period: 'all' });
});

// set display label for active preset
const periodNow = hidPeriod.value;
if (periodNow && periodNow !== 'all') {
    const labels = { today: 'Hari ini', week: 'Minggu ini', month: 'Bulan ini' };
    const pickerInput = document.getElementById('inp-date');
    if (pickerInput) pickerInput.value = labels[periodNow] || '';
}


// search: submit on Enter
document.getElementById('inp-search')?.addEventListener('keydown', e => {
    if (e.key === 'Enter') { e.preventDefault(); form.submit(); }
});
// debounce 500ms
let _st;
document.getElementById('inp-search')?.addEventListener('input', () => {
    clearTimeout(_st);
    _st = setTimeout(() => form.submit(), 500);
});

// ── Expand chips ──────────────────────────────────────────
function toggleMore(btn) {
    const id = btn.dataset.id;
    const extras = document.querySelectorAll(`[data-extra="${id}"]`);
    const isHidden = extras[0]?.style.display === 'none';
    extras.forEach(el => el.style.display = isHidden ? 'inline-flex' : 'none');
    btn.textContent = isHidden ? '−' : '+' + extras.length;
}
</script>
@endsection
