{{-- resources/views/production/qc/sewing_edit.blade.php --}}
@extends('layouts.app')

@section('title', 'QC Jahit · ' . $sewingReturn->code)

@push('head')
<style>

        :root {
            --r: 14px;
            --b: rgba(148, 163, 184, .22);
            --muted: #6b7280;
            --soft2: rgba(148, 163, 184, .05);
            --accent: #16a34a;
            --ok: #16a34a;
            --rj: #b91c1c;
            --shadow: 0 10px 26px rgba(15, 23, 42, .08), 0 0 0 1px rgba(15, 23, 42, .03);
            --bottom-nav-h: 72px;
            --fab-gap: 12px;
            --fab-bottom: calc(var(--bottom-nav-h) + var(--fab-gap) + env(safe-area-inset-bottom));
            --vv-kbd: 0px;
        }

        .page-wrap { max-width: 980px; margin: 0 auto; padding: 14px 12px 96px; }

        @media(max-width:991.98px) {
            .page-wrap { padding-bottom: calc(var(--bottom-nav-h) + 130px + var(--vv-kbd)); }
            body.keyboard-open .page-wrap { padding-bottom: calc(14rem + var(--vv-kbd)); }
            .modal-dialog { margin: .75rem; }
            .modal-content { border-radius: 16px; }
            .modal-body { max-height: calc(100vh - 210px); overflow: auto; }
        }

        .panel { background: var(--card); border: 1px solid var(--b); border-radius: var(--r); box-shadow: var(--shadow); }
        .panel-h { padding: 9px 12px; border-bottom: 1px solid rgba(148, 163, 184, .12); }
        .panel-b { padding: 10px 12px; }

        .h-title { font-weight: 900; font-size: .95rem; margin: 0; }
        .return-mode-switch {
            display: inline-flex;
            align-items: center;
            gap: 3px;
            padding: 3px;
            border: 1px solid rgba(148, 163, 184, .22);
            border-radius: 10px;
            background: rgba(148, 163, 184, .06);
        }
        .return-mode-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 30px;
            padding: .25rem .7rem;
            border-radius: 8px;
            color: var(--muted);
            font-size: .75rem;
            font-weight: 700;
            text-decoration: none;
            white-space: nowrap;
        }
        .return-mode-link:hover { color: var(--bs-body-color); background: rgba(148, 163, 184, .1); }
        .return-mode-link.active {
            color: #fff;
            background: #334155;
        }
        .return-mode-link.reject.active { background: #7f1d1d; }
        .return-head-actions {
            display: flex;
            align-items: center;
            gap: .45rem;
            flex-wrap: wrap;
            justify-content: flex-end;
        }

        .meta { border: 1px solid rgba(148, 163, 184, .18); border-radius: var(--r); padding: 8px; background: var(--soft2); }
        body[data-theme="dark"] .meta { background: rgba(15, 23, 42, .35); }

        .form-label-sm { font-size: .68rem; font-weight: 800; color: var(--muted); }
        .form-control-sm, .form-select-sm { font-size: .82rem; padding: .28rem .45rem; border-radius: 10px; }

        .return-filter-row { --bs-gutter-x: .5rem; --bs-gutter-y: .45rem; }
        .return-filter-row .form-label-sm {
            display: block;
            margin-bottom: .2rem;
            line-height: 1.05;
            white-space: nowrap;
        }
        .return-dest-pill {
            display: flex;
            align-items: center;
            gap: .3rem;
            overflow: hidden;
            font-weight: 900;
            border-radius: 12px;
        }
        .return-dest-code { flex: 0 0 auto; }
        .return-dest-name {
            min-width: 0;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            color: var(--muted);
            font-family: var(--bs-body-font-family);
            font-size: .78rem;
        }

        @media(max-width:991.98px) {
            .meta { padding: 8px; border-radius: 12px; }
            .return-filter-row { --bs-gutter-x: .35rem; --bs-gutter-y: .35rem; }
            .return-filter-row .form-label-sm {
                font-size: .62rem;
                letter-spacing: .02em;
                margin-bottom: .14rem;
            }
            .return-filter-row .form-control-sm,
            .return-filter-row .form-select-sm {
                min-height: 36px;
                padding: .34rem .45rem;
                border-radius: 10px;
                font-size: .8rem;
            }
            .return-filter-row select.form-select-sm {
                padding-right: 1.7rem;
                background-position: right .45rem center;
                background-size: 12px 9px;
            }
            .return-dest-name { display: none; }
            .return-dest-pill { justify-content: center; padding-left: .35rem; padding-right: .35rem; }
            #q { font-size: .86rem; }
            .return-head-actions {
                width: 100%;
                justify-content: stretch;
            }
            .return-mode-switch {
                flex: 1;
                display: grid;
                grid-template-columns: 1fr 1fr;
            }
            .return-mode-link { padding-inline: .35rem; }
        }

        @media(min-width:768px) and (max-width:991.98px) {
            .return-filter-row .form-control-sm,
            .return-filter-row .form-select-sm { min-height: 38px; }
        }

        .mono { font-variant-numeric: tabular-nums; font-family: ui-monospace, SFMono-Regular, Menlo, Consolas; }

        .list { display: grid; gap: .6rem; margin-top: 12px; }

        .cardx { border: 1px solid rgba(148, 163, 184, .18); border-radius: 12px; background: var(--card); overflow: hidden; }
        .cardx-h { padding: 7px 10px 2px; display: flex; justify-content: space-between; gap: 8px; align-items: flex-start; }

        .cardx-left { display: flex; gap: 8px; align-items: flex-start; min-width: 0; }
        .cardx-left>div { min-width: 0; }

        .chk { width: 16px; height: 16px; border-radius: 5px; cursor: pointer; margin-top: 2px; flex: 0 0 auto; }

        .code { font-weight: 900; letter-spacing: .07em; color: var(--accent); font-size: .92rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 100%; }

        .meta-inline { margin-top: .18rem; font-size: .68rem; color: var(--muted); font-weight: 900; display: flex; align-items: center; gap: .35rem; flex-wrap: wrap; }
        .meta-inline .dot { opacity: .6; }
        .meta-inline .truncate { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 260px; display: inline-block; vertical-align: bottom; }

        @media(max-width:991.98px) { .meta-inline .truncate { max-width: 170px; } }

        .right-metrics { font-size: .75rem; color: var(--muted); font-weight: 900; white-space: nowrap; text-align: right; flex: 0 0 auto; }
        .card-metrics { display: grid; gap: .1rem; min-width: 80px; }
        .metric-main {
            display: inline-flex;
            align-items: baseline;
            gap: .2rem;
            color: #2563eb;
        }
        .metric-main .lbl { font-size: .56rem; line-height: 1; color: var(--muted); letter-spacing: .05em; text-transform: uppercase; }
        .metric-main .val { font-size: .92rem; line-height: 1; font-weight: 950; color: #2563eb; }
        .metric-sub { font-size: .66rem; line-height: 1.1; color: var(--muted); }
        .metric-sub.is-returned { color: #16a34a; }
        .supply-mini-btn {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            margin-top: .3rem;
            max-width: 230px;
            border: 0;
            background: transparent;
            border-radius: 999px;
            padding: .08rem 0;
            text-align: left;
            color: var(--muted);
        }
        .supply-mini-status { font-size: .66rem; font-weight: 900; flex-shrink: 0; }
        .supply-mini-hint { font-size: .66rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; min-width: 0; }
        .supply-mini-action { font-size: .66rem; font-weight: 900; color: #2563eb; flex-shrink: 0; }

        .cardx-b { padding: 5px 10px 8px; display: grid; gap: .38rem; }
        .grid2 { display: grid; grid-template-columns: 1fr 1fr; gap: .4rem; }
        .grid2.single { grid-template-columns: 1fr; }

        .field label { display: block; font-size: .65rem; font-weight: 900; color: var(--muted); text-transform: uppercase; letter-spacing: .07em; margin-bottom: .18rem; }

        .qty { text-align: center !important; font-weight: 900; padding: .32rem .4rem !important; border-radius: 10px; }
        .qty.ok { border: 1px solid rgba(22, 163, 74, .25); background: rgba(22, 163, 74, .04); }
        .qty.rj { border: 1px solid rgba(185, 28, 28, .22); background: rgba(185, 28, 28, .04); }
        .qty:focus { box-shadow: none; }

        .sc-step-hdr { display:flex; justify-content:space-between; align-items:center; gap:.75rem; margin-bottom:.75rem; }
        .sc-step-main { font-size:1.05rem; font-weight:900; letter-spacing:-.01em; font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace; min-width:0; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
        .sc-step-meta { display:flex; gap:.35rem; flex-wrap:wrap; margin-bottom:.65rem; }
        .sc-chip { border:1px solid rgba(148,163,184,.18); border-radius:999px; padding:.16rem .5rem; background:rgba(148,163,184,.06); font-size:.68rem; font-weight:900; color:var(--muted); }
        .sc-row { display:grid; grid-template-columns:24px 1fr auto; align-items:center; gap:.5rem; padding:.45rem .6rem; border:1px solid rgba(148,163,184,.18); border-radius:10px; margin-bottom:.35rem; transition:background .15s; }
        .sc-row.is-ok { background:rgba(22,163,74,.06); border-color:rgba(22,163,74,.25); }
        .sc-row.is-short { background:rgba(239,68,68,.05); border-color:rgba(239,68,68,.25); }
        .sc-chk { width:1.1rem; height:1.1rem; cursor:pointer; accent-color:#2563eb; flex-shrink:0; }
        .sc-label { font-size:.78rem; font-weight:800; line-height:1.15; }
        .sc-sub { font-size:.69rem; color:var(--muted); }
        .sc-input { width:68px; text-align:right; font-weight:900; font-size:.86rem; border-radius:8px; padding:.22rem .35rem; border:1px solid rgba(148,163,184,.4); background:var(--card); color:var(--text); }
        .sc-input:focus { outline:none; border-color:#2563eb; box-shadow:0 0 0 2px rgba(37,99,235,.15); }
        .sc-input:disabled { background:rgba(148,163,184,.10); color:var(--muted); opacity:.7; }

        /* ── Mobile + Tablet optimized card ── */
        @media(max-width:991.98px) {
            .list { gap: .34rem; margin-top: 10px; }

            .cardx {
                border-radius: 10px;
                border-color: rgba(148, 163, 184, .18);
                box-shadow: none;
            }

            .code { font-size: clamp(.84rem, 3.35vw, 1rem); letter-spacing: .05em; }

            /* Header: kode kiri, sisa kanan */
            .cardx-h {
                padding: .44rem .58rem .18rem;
                align-items: center;
                gap: .58rem;
            }
            .cardx-left { gap: .48rem; }
            .chk {
                width: 16px;
                height: 16px;
                border-radius: 5px;
                margin-top: 1px;
            }
            .meta-inline {
                margin-top: .12rem;
                font-size: clamp(.62rem, 2.2vw, .72rem);
                gap: .26rem;
            }
            .right-metrics { font-size: clamp(.66rem, 2.3vw, .78rem); }
            .card-metrics {
                min-width: clamp(68px, 24vw, 84px);
                gap: .04rem;
            }
            .metric-main {
                display: inline-flex;
                align-items: baseline;
                justify-content: flex-end;
                gap: .22rem;
                border: 0;
                background: transparent;
                padding: 0;
                border-radius: 0;
            }
            .metric-main .lbl,
            .metric-sub .lbl { font-size: .52rem; }
            .metric-main .lbl {
                display: inline;
                font-size: .54rem;
                letter-spacing: .04em;
            }
            .metric-main .val {
                display: inline;
                margin-top: 0;
                font-size: clamp(.78rem, 3vw, .9rem);
            }
            .metric-sub { font-size: clamp(.58rem, 2vw, .66rem); line-height: 1.08; }
            .metric-sub.is-returned { display: none; }
            .supply-mini-btn {
                max-width: 170px;
                margin-top: .14rem;
                gap: .24rem;
            }
            .supply-mini-status,
            .supply-mini-action {
                font-size: .56rem;
                border: 1px solid rgba(148, 163, 184, .18);
                border-radius: 999px;
                padding: .06rem .32rem;
                background: rgba(148, 163, 184, .06);
            }
            .supply-mini-hint { display: none; }
            /* Sembunyikan baris WIP */
            .right-metrics .wip-line { display: none; }

            /* Meta: sembunyikan operator, cukup tanggal */
            .meta-inline .op-label { display: none; }
            .meta-inline .op-dot   { display: none; }

            .qty {
                padding: .24rem .5rem !important;
                font-size: clamp(.82rem, 3vw, .94rem) !important;
                border-radius: 8px !important;
                background: transparent !important;
            }
            .qty.ok { border-color: rgba(22, 163, 74, .26) !important; }
            .qty.rj { border-color: rgba(185, 28, 28, .22) !important; }
            .field label {
                font-size: .58rem;
                margin-bottom: .08rem;
            }
            .grid2 { gap: .42rem; }
            .cardx-b {
                padding: .28rem .58rem .48rem;
                gap: .24rem;
            }

            /* Shortage pill: tampilkan detail kurang di baris baru */
            .shortage-pill {
                flex-wrap: wrap !important;
                border-radius: 10px !important;
                gap: .2rem .35rem !important;
            }
            .shortage-label {
                display: block !important;
                white-space: normal !important;
                overflow: visible !important;
                text-overflow: unset !important;
                width: 100%;
                line-height: 1.5;
                padding-top: .05rem;
            }
        }

        .notes { display: none; }
        .notes.is-show { display: block; }
        .notes input { border-radius: 12px; }

        .fab-wrap {
            position: fixed; right: 14px; bottom: var(--fab-bottom);
            z-index: 1090; display: flex; gap: 10px; align-items: center; pointer-events: none;
        }
        .fab-wrap .btn { pointer-events: auto; border-radius: 999px; font-weight: 900; box-shadow: 0 12px 26px rgba(15, 23, 42, .22), 0 4px 10px rgba(15, 23, 42, .14); }
        .fab-back { width: 46px; padding-left: 0; padding-right: 0; }
        .fab-save { width: auto; padding: .62rem 1.05rem; white-space: nowrap; }

        @media(max-width:991.98px) {
            .fab-wrap { transition: transform .15s ease, opacity .15s ease; transform: translateY(0); opacity: 1; }
            body.keyboard-open .fab-wrap { bottom: calc(var(--fab-bottom) + var(--vv-kbd)); }
            .fab-wrap.is-hidden { opacity: 0; transform: translateY(10px); pointer-events: none; }
            body.keyboard-open .fab-wrap .btn { box-shadow: none; }
        }

        .modal { z-index: 3000 !important; }
        .modal-backdrop { z-index: 2990 !important; }

        .top-actions { display: flex; gap: .5rem; flex-wrap: wrap; align-items: center; justify-content: space-between; }
        .pill { border: 1px solid rgba(148, 163, 184, .22); background: rgba(148, 163, 184, .06); border-radius: 999px; padding: .35rem .6rem; font-weight: 900; font-size: .78rem; color: var(--muted); }
        .btn-mini { border-radius: 999px; font-weight: 900; padding: .35rem .6rem; }
        .mini-kpi-row {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            margin-top: .4rem;
            border: 1px solid rgba(148, 163, 184, .18);
            border-radius: 10px;
            overflow: hidden;
            background: rgba(255, 255, 255, .42);
        }
        .mini-kpi-row.kpi-3 { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        .mini-kpi-row.kpi-4 { grid-template-columns: repeat(4, minmax(0, 1fr)); }
        body[data-theme="dark"] .mini-kpi-row { background: rgba(15, 23, 42, .20); }
        .mini-kpi {
            border-right: 1px solid rgba(148, 163, 184, .16);
            padding: .25rem .38rem;
            min-width: 0;
            text-align: center;
        }
        .mini-kpi:last-child { border-right: 0; }
        .mini-kpi .lbl {
            display: block;
            color: var(--muted);
            font-size: .52rem;
            font-weight: 900;
            line-height: 1;
            letter-spacing: .04em;
            text-transform: uppercase;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .mini-kpi .val {
            display: flex;
            align-items: baseline;
            justify-content: center;
            gap: .14rem;
            margin-top: .1rem;
            font-size: .76rem;
            line-height: 1;
            font-weight: 950;
            color: var(--text);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .mini-kpi .unit {
            color: var(--muted);
            font-size: .65em;
            font-weight: 900;
        }
        .mini-kpi.is-main {
            background: rgba(37, 99, 235, .06);
        }
        .mini-kpi.is-main .val { color: #2563eb; }

        @media(max-width:991.98px) {
            .mini-kpi-row { margin-top: .45rem; border-radius: 10px; }
            .mini-kpi { padding: .32rem .34rem; }
            .mini-kpi .lbl { font-size: .5rem; letter-spacing: .03em; }
            .mini-kpi .val { font-size: .78rem; margin-top: .1rem; gap: .1rem; }
            .mini-kpi .unit { font-size: .62em; }
        }

        .sum-box { border: 1px solid rgba(148, 163, 184, .18); border-radius: 14px; padding: 10px 12px; background: rgba(148, 163, 184, .06); }
        body[data-theme="dark"] .sum-box { background: rgba(15, 23, 42, .25); }

        .sum-top { display: flex; justify-content: space-between; gap: 10px; align-items: flex-start; flex-wrap: wrap; }
        .sum-top .ttl { font-weight: 900; }
        .sum-top .sub { color: var(--muted); font-weight: 900; font-size: .82rem; }

        .sum-pillrow { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: .5rem; margin-top: .65rem; }

        .sum-pill { border: 1px solid rgba(148, 163, 184, .18); border-radius: 999px; padding: .35rem .6rem; text-align: center; font-weight: 900; font-size: .82rem; background: rgba(255, 255, 255, .55); }
        body[data-theme="dark"] .sum-pill { background: rgba(15, 23, 42, .15); }
        .sum-pill .lbl { display: block; font-size: .7rem; color: var(--muted); font-weight: 900; letter-spacing: .08em; text-transform: uppercase; }
        .sum-pill .val { display: block; margin-top: .12rem; }

        .acc-op-btn {
            font-weight: 900;
            padding: .58rem .68rem;
            background: transparent;
        }
        .acc-op-btn.accordion-button:not(.collapsed) {
            background: transparent;
            color: var(--text);
            box-shadow: inset 0 -1px 0 rgba(148, 163, 184, .12);
        }
        .acc-op-btn.accordion-button::after {
            width: .65rem;
            height: .65rem;
            background-size: .65rem;
            opacity: .42;
            margin-left: .45rem;
        }
        .acc-op-btn.accordion-button:not(.collapsed)::after { opacity: .62; }
        .acc-op-btn.accordion-button:focus { box-shadow: none; }
        .acc-pill { display: inline-flex; align-items: center; gap: .35rem; padding: .18rem .55rem; border-radius: 999px; border: 1px solid rgba(148, 163, 184, .18); background: rgba(148, 163, 184, .06); font-weight: 900; font-size: .78rem; white-space: nowrap; }
        body[data-theme="dark"] .acc-pill { background: rgba(15, 23, 42, .22); }
        .acc-sub { font-size: .78rem; font-weight: 900; color: var(--muted); }
        .all-card {
            border-radius: 12px;
            overflow: hidden;
            border: 1px solid rgba(148,163,184,.16);
            background: var(--card);
            margin-bottom: .36rem;
        }
        .all-btn-main { display: flex; width: 100%; justify-content: space-between; align-items: center; gap: .62rem; }
        .all-code { font-weight: 950; font-size: .96rem; letter-spacing: .04em; max-width: 48vw; color: var(--accent); }
        .all-metrics { display: flex; align-items: baseline; gap: .22rem; justify-content: flex-end; color: #2563eb; }
        .all-pill-main {
            display: inline-flex;
            align-items: baseline;
            gap: .22rem;
            font-weight: 950;
            line-height: 1;
        }
        .all-pill-main .lbl { font-size: .56rem; color: var(--muted); letter-spacing: .05em; text-transform: uppercase; }
        .all-pill-main .val { font-size: .9rem; color: #2563eb; }
        .all-pill-main .unit { font-size: .62rem; color: var(--muted); }
        .all-op-count { color: var(--muted); font-size: .66rem; font-weight: 900; white-space: nowrap; }
        .all-subline { display: flex; align-items: center; gap: .36rem; flex-wrap: wrap; margin-top: .12rem; }
        .all-detail-list { display: grid; gap: 0; }
        .all-detail-row {
            display: grid;
            grid-template-columns: 1fr auto;
            gap: .55rem;
            align-items: center;
            padding: .46rem 0;
            border-bottom: 1px solid rgba(148,163,184,.12);
            cursor: pointer;
            border-radius: 0;
            transition: background .15s ease;
        }
        .all-detail-row:hover { background: rgba(37, 99, 235, .05); }
        .all-detail-row:last-child { border-bottom: 0; }
        .all-op-name { font-weight: 900; min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .all-detail-right { text-align: right; }
        .all-detail-val { font-weight: 950; text-align: right; color: var(--text); }
        .all-detail-unit { color: var(--muted); font-size: .66rem; font-weight: 900; }
        .all-detail-sub { color: var(--muted); font-size: .64rem; font-weight: 900; text-align: right; margin-top: .05rem; }
        .all-accordion-body { padding: .2rem .68rem .54rem; }

        @media(max-width:991.98px) {
            .sum-box { display: none; }
            .acc-op-btn { padding: .46rem .56rem; }
            .all-card { border-radius: 10px; margin-bottom: .34rem; }
            .all-code { font-size: clamp(.84rem, 3.35vw, 1rem); max-width: 42vw; }
            .all-pill-main .lbl { font-size: .52rem; }
            .all-pill-main .val { font-size: clamp(.78rem, 3vw, .9rem); }
            .all-op-count { font-size: .6rem; }
            .all-subline { gap: .28rem; margin-top: .08rem; }
            .all-accordion-body { padding: .12rem .56rem .42rem; }
            .all-detail-row { gap: .45rem; padding: .4rem 0; }
            .all-op-name { font-size: .76rem; }
            .all-detail-val { font-size: .78rem; }
            .all-detail-sub { display: block; font-size: .58rem; }
        }

    
    .page-wrap { max-width: 980px; margin: 0 auto; padding: 14px 12px 96px; }
    
    @media(max-width:991.98px) {
        .page-wrap { padding-bottom: calc(var(--bottom-nav-h) + 130px + var(--vv-kbd)); }
        body.keyboard-open .page-wrap { padding-bottom: calc(14rem + var(--vv-kbd)); }
    }
</style>
@endpush

@section('content')
@php
    $statusLabel = $hasQcSewing ? 'QC Selesai' : 'Belum QC';
    $totalBundles = count($rows);
    $totalIn = 0;
    $totalOk = 0;
    $totalReject = 0;
    $destinationWarehouses = $destinationWarehouses ?? collect();
    $defaultDestinationWarehouseId = (int) ($defaultDestinationWarehouseId ?? 0);
    $selectedDestinationWarehouseId = (int) old('destination_warehouse_id', $defaultDestinationWarehouseId);
    $selectedDestinationWarehouse = $destinationWarehouses->firstWhere('id', $selectedDestinationWarehouseId);

    foreach ($rows as $idx => $row) {
        $totalIn += (float) $row['qty_max'];
        $totalOk += (float) old("results.{$idx}.qty_ok", $row['qty_ok']);
        $totalReject += (float) old("results.{$idx}.qty_reject_jahit", $row['qty_reject_jahit'] ?? 0) + (float) old("results.{$idx}.qty_reject_bahan", $row['qty_reject_bahan'] ?? 0);
    }
@endphp

<div class="page-wrap">
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="panel mb-2">
        <div class="panel-h">
            <div class="d-flex align-items-start justify-content-between gap-2 flex-wrap">
                <div>
                    <div class="h-title">QC Jahit</div>
                    <div class="text-muted small mt-1">Setor Jahit: {{ $sewingReturn->code }}</div>
                </div>
                <div class="return-head-actions">
                    <a href="{{ route('production.qc.index', ['stage' => 'sewing']) }}"
                       class="btn btn-sm btn-outline-secondary"
                       style="border-radius:8px;">
                        Kembali
                    </a>
                    <a href="{{ route('production.sewing.returns.show', $sewingReturn) }}"
                       class="btn btn-sm btn-outline-success"
                       style="border-radius:8px;">
                        Lihat Setor
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="panel">
        <form id="sewing-qc-form" method="POST" action="{{ route('production.qc.sewing.update', $sewingReturn) }}">
            @csrf
            @method('PUT')
            
            <div class="panel-b">
                <div class="meta">
                    <div class="row align-items-end return-filter-row">
                        <div class="col-5 col-lg-2">
                            <label class="form-label form-label-sm">Tanggal QC</label>
                            <input type="date" name="qc_date"
                                   class="form-control form-control-sm @error('qc_date') is-invalid @enderror"
                                   value="{{ old('qc_date', now()->toDateString()) }}" required>
                        </div>
                        <div class="col-7 col-lg-3">
                            <label class="form-label form-label-sm">QC oleh (login)</label>
                            <input type="hidden" name="operator_id" value="{{ $loginOperator?->id }}">
                            <div class="form-control form-control-sm mono" style="background: rgba(148,163,184,.1);">
                                {{ auth()->user()?->name ?? '-' }}
                            </div>
                            @if ($loginOperator)
                                <div class="text-muted small mt-1">Employee: {{ $loginOperator->name }}</div>
                            @endif
                        </div>
                        <div class="col-12 col-lg-3">
                            <label class="form-label form-label-sm">Gudang OK setelah QC</label>
                            <select name="destination_warehouse_id"
                                    id="destination-warehouse"
                                    class="form-select form-select-sm @error('destination_warehouse_id') is-invalid @enderror"
                                    required>
                                @foreach ($destinationWarehouses as $warehouse)
                                    <option value="{{ $warehouse->id }}"
                                            data-code="{{ $warehouse->code }}"
                                            @selected($selectedDestinationWarehouseId === (int) $warehouse->id)>
                                        {{ $warehouse->code }} — {{ $warehouse->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('destination_warehouse_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-lg-2 d-none d-lg-block">
                            <label class="form-label form-label-sm">Status QC</label>
                            <div class="form-control form-control-sm mono" style="background: rgba(148,163,184,.1);">
                                {{ $statusLabel }}
                            </div>
                        </div>
                        <div class="col-lg-2 d-none d-lg-block text-end">
                            <label class="form-label form-label-sm">Tanggal Setor</label>
                            <div class="form-control form-control-sm mono" style="background: rgba(148,163,184,.1);">
                                {{ $sewingReturn->date?->format('d/m/Y') ?? '-' }}
                            </div>
                        </div>
                    </div>
                    
                    <div class="mini-kpi-row kpi-4">
                        <div class="mini-kpi">
                            <span class="lbl">Bundle</span>
                            <span class="val mono">{{ number_format($totalBundles, 0, ',', '.') }}</span>
                        </div>
                        <div class="mini-kpi">
                            <span class="lbl">Masuk</span>
                            <span class="val mono">
                                <span>{{ number_format($totalIn, 0, ',', '.') }}</span>
                                <span class="unit">pcs</span>
                            </span>
                        </div>
                        <div class="mini-kpi is-main">
                            <span class="lbl">OK</span>
                            <span class="val mono" style="color: #16a34a;">
                                <span id="kpi-ok">{{ number_format($totalOk, 0, ',', '.') }}</span>
                                <span class="unit">pcs</span>
                            </span>
                        </div>
                        <div class="mini-kpi is-main" style="background: rgba(185, 28, 28, .06);">
                            <span class="lbl">Reject</span>
                            <span class="val mono" style="color: #b91c1c;">
                                <span id="kpi-reject">{{ number_format($totalReject, 0, ',', '.') }}</span>
                                <span class="unit">pcs</span>
                            </span>
                        </div>
                    </div>
                </div>

                <div class="list" id="list-byop">
                    @if(empty($rows))
                        <div class="text-center py-4 text-muted">Tidak ada bundle yang bisa di-QC pada Setor Jahit ini.</div>
                    @else
                        @foreach($rows as $i => $row)
                            <div class="cardx mono fin-item"
                                 data-idx="{{ $i }}"
                                 data-max="{{ $row['qty_max'] }}">
                                <div class="cardx-h">
                                    <div class="cardx-left">
                                        <div>
                                            <div class="code" style="font-family: inherit; font-size: 1rem; color: var(--text);">{{ $row['item_name'] }}</div>
                                            <div class="meta-inline" style="margin-top: 0.15rem;">
                                                <span class="truncate text-muted" style="font-size: 0.75rem; opacity: 0.7;">Bundle: {{ $row['bundle_code'] }}</span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="right-metrics card-metrics">
                                        <div class="metric-main">
                                            <span class="lbl">Masuk</span>
                                            <span class="val">{{ number_format($row['qty_max'], 0, ',', '.') }}</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="cardx-b">
                                    <div class="grid3" style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 8px;">
                                        <div class="field">
                                            <label>OK</label>
                                            <input type="number" step="1" min="0" max="{{ $row['qty_max'] }}"
                                                   inputmode="numeric"
                                                   class="form-control form-control-sm qty ok num-input select-all-on-focus qty-ok"
                                                   name="results[{{ $i }}][qty_ok]"
                                                   id="ok_{{ $i }}"
                                                   data-idx="{{ $i }}"
                                                   value="{{ old("results.{$i}.qty_ok", $row['qty_ok']) }}" placeholder="0"
                                                   oninput="syncQty('ok', {{ $i }}, {{ $row['qty_max'] }})">
                                        </div>
                                        <div class="field">
                                            <label>Rj. Jahit</label>
                                            <input type="number" step="1" min="0" max="{{ $row['qty_max'] }}"
                                                   inputmode="numeric"
                                                   class="form-control form-control-sm qty rj num-input select-all-on-focus qty-reject-jahit"
                                                   name="results[{{ $i }}][qty_reject_jahit]"
                                                   id="jahit_{{ $i }}"
                                                   data-idx="{{ $i }}"
                                                   value="{{ old("results.{$i}.qty_reject_jahit", $row['qty_reject_jahit'] ?? 0) }}" placeholder="0"
                                                   oninput="syncQty('jahit', {{ $i }}, {{ $row['qty_max'] }})">
                                        </div>
                                        <div class="field">
                                            <label>Rj. Bahan</label>
                                            <input type="number" step="1" min="0" max="{{ $row['qty_max'] }}"
                                                   inputmode="numeric"
                                                   class="form-control form-control-sm qty rj num-input select-all-on-focus qty-reject-bahan"
                                                   name="results[{{ $i }}][qty_reject_bahan]"
                                                   id="bahan_{{ $i }}"
                                                   data-idx="{{ $i }}"
                                                   value="{{ old("results.{$i}.qty_reject_bahan", $row['qty_reject_bahan'] ?? 0) }}" placeholder="0"
                                                   oninput="syncQty('bahan', {{ $i }}, {{ $row['qty_max'] }})">
                                        </div>
                                    </div>
                                    
                                    <input type="hidden" name="results[{{ $i }}][sewing_return_line_id]" value="{{ $row['sewing_return_line_id'] }}">
                                    <input type="hidden" name="results[{{ $i }}][bundle_id]" value="{{ $row['bundle_id'] }}">
                                </div>
                            </div>
                        @endforeach
                    @endif
                </div>
                
                <div class="fab-wrap" id="fab-wrap">
                    <a href="{{ route('production.sewing.returns.show', $sewingReturn) }}"
                       class="btn btn-sm btn-outline-secondary fab-back">←</a>
                    <button type="button" class="btn btn-sm btn-success fab-save" id="btn-show-confirm">
                        Simpan QC
                    </button>
                </div>
            </div>
        </form>
    </div>

    {{-- Modal Konfirmasi --}}
    <div class="modal fade" id="confirmQcModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border-radius:14px; border:none; box-shadow:0 10px 25px rgba(0,0,0,.1);">
                <div class="modal-header" style="border-bottom:1px solid rgba(148,163,184,.15); padding:1rem 1.25rem;">
                    <h5 class="modal-title" style="font-weight:800; font-size:1.05rem;">Konfirmasi QC</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" style="padding:1.25rem;">
                    <div style="background:rgba(59,130,246,.08); border:1px solid rgba(59,130,246,.2); border-radius:8px; padding:.75rem; margin-bottom:1rem; font-size:.8rem; color:#1e3a8a;">
                        <strong style="display:block; margin-bottom:.3rem;">Informasi Perpindahan Stok:</strong>
                        <div style="display:flex; align-items:center; gap:.4rem;"><span style="color:#16a34a; font-weight:800; min-width:48px;">● OK</span> ➔ Masuk ke gudang <b id="confirm-destination-code">{{ $selectedDestinationWarehouse?->code ?? 'WH-PRD' }}</b></div>
                        <div style="display:flex; align-items:center; gap:.4rem; margin-top:.15rem;"><span style="color:#dc2626; font-weight:800; min-width:48px;">● Reject</span> ➔ Masuk ke gudang <b>REJ-SEW</b></div>
                    </div>
                    <p class="text-muted" style="font-size:.85rem; margin-bottom:.75rem;">Ringkasan hasil QC yang akan disimpan:</p>
                    <div id="confirmSummary" style="display:flex; flex-direction:column; gap:.5rem;"></div>
                </div>
                <div class="modal-footer" style="border-top:1px solid rgba(148,163,184,.15); padding:1rem 1.25rem; background:rgba(148,163,184,.03); border-bottom-left-radius:14px; border-bottom-right-radius:14px;">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal" style="border-radius:8px; font-weight:700;">Batal</button>
                    <button type="button" class="btn btn-success" id="btn-confirm-submit" style="border-radius:8px; font-weight:700;">Ya, Simpan QC</button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    (function initVV() {
        if (!window.visualViewport) return;
        const vv = window.visualViewport;
        const set = () => {
            const kbd = Math.max(0, (window.innerHeight - vv.height - vv.offsetTop));
            document.documentElement.style.setProperty('--vv-kbd', `${kbd}px`);
        };
        vv.addEventListener('resize', set);
        vv.addEventListener('scroll', set);
        set();
    })();

    function numberValue(input) {
        return parseFloat(input.value) || 0;
    }

    function syncQty(changed, idx, max) {
        const okField = document.getElementById('ok_' + idx);
        const jahitField = document.getElementById('jahit_' + idx);
        const bahanField = document.getElementById('bahan_' + idx);

        let ok = numberValue(okField);
        let jahit = numberValue(jahitField);
        let bahan = numberValue(bahanField);

        if (changed === 'ok') {
            const remainder = Math.max(0, max - ok);
            if (jahit + bahan > remainder) {
                if (bahan > remainder) {
                    bahan = remainder;
                    jahit = 0;
                } else {
                    jahit = remainder - bahan;
                }
                jahitField.value = jahit > 0 ? jahit : '';
                bahanField.value = bahan > 0 ? bahan : '';
            }
        } else if (changed === 'jahit') {
            const remainder = Math.max(0, max - jahit);
            if (ok + bahan > remainder) {
                ok = remainder - bahan;
                if (ok < 0) {
                    ok = 0;
                    bahan = remainder;
                    bahanField.value = bahan > 0 ? bahan : '';
                }
                okField.value = ok > 0 ? ok : '';
            }
        } else if (changed === 'bahan') {
            const remainder = Math.max(0, max - bahan);
            if (ok + jahit > remainder) {
                ok = remainder - jahit;
                if (ok < 0) {
                    ok = 0;
                    jahit = remainder;
                    jahitField.value = jahit > 0 ? jahit : '';
                }
                okField.value = ok > 0 ? ok : '';
            }
        }

        updateSummary();
    }

    function updateSummary() {
        const okInputs = document.querySelectorAll('.fin-item:not([style*="display: none"]) .qty-ok, .qty-ok');
        const jahitInputs = document.querySelectorAll('.fin-item:not([style*="display: none"]) .qty-reject-jahit, .qty-reject-jahit');
        const bahanInputs = document.querySelectorAll('.fin-item:not([style*="display: none"]) .qty-reject-bahan, .qty-reject-bahan');
        
        let totalOk = 0;
        let totalReject = 0;
        
        okInputs.forEach(el => totalOk += numberValue(el));
        jahitInputs.forEach(el => totalReject += numberValue(el));
        bahanInputs.forEach(el => totalReject += numberValue(el));

        document.getElementById('kpi-ok').textContent = new Intl.NumberFormat('id-ID').format(totalOk);
        document.getElementById('kpi-reject').textContent = new Intl.NumberFormat('id-ID').format(totalReject);
    }
    
    // auto select all on focus
    document.querySelectorAll('.select-all-on-focus').forEach(input => {
        input.addEventListener('focus', function() {
            this.select();
        });
    });

    // Confirmation Modal Logic
    const btnShowConfirm = document.getElementById('btn-show-confirm');
    const confirmQcModal = new bootstrap.Modal(document.getElementById('confirmQcModal'));
    const confirmSummary = document.getElementById('confirmSummary');
    const form = document.getElementById('sewing-qc-form');
    const destinationWarehouse = document.getElementById('destination-warehouse');

    function syncDestinationLabel() {
        const option = destinationWarehouse?.options[destinationWarehouse.selectedIndex];
        const code = option?.dataset?.code || 'WH-PRD';
        const confirmationCode = document.getElementById('confirm-destination-code');
        if (confirmationCode) confirmationCode.textContent = code;
        document.querySelectorAll('.summary-destination-code').forEach(el => el.textContent = code);
    }

    destinationWarehouse?.addEventListener('change', syncDestinationLabel);
    syncDestinationLabel();
    
    if (btnShowConfirm) {
        btnShowConfirm.addEventListener('click', function() {
            const destinationCode = destinationWarehouse?.options[destinationWarehouse.selectedIndex]?.dataset?.code || 'WH-PRD';
            const itemsMap = {};
            document.querySelectorAll('.fin-item').forEach(el => {
                const itemName = el.querySelector('.code').textContent.trim();
                const ok = numberValue(el.querySelector('.qty-ok'));
                const rjJahit = numberValue(el.querySelector('.qty-reject-jahit'));
                const rjBahan = numberValue(el.querySelector('.qty-reject-bahan'));
                
                if (!itemsMap[itemName]) itemsMap[itemName] = { ok: 0, rjJahit: 0, rjBahan: 0, bundles: 0 };
                itemsMap[itemName].ok += ok;
                itemsMap[itemName].rjJahit += rjJahit;
                itemsMap[itemName].rjBahan += rjBahan;
                itemsMap[itemName].bundles += 1;
            });

            let html = '';
            for (const [name, data] of Object.entries(itemsMap)) {
                const fmt = new Intl.NumberFormat('id-ID').format;
                html += `
                    <div style="border:1px solid rgba(148,163,184,.2); border-radius:10px; padding:.75rem; background:var(--card);">
                        <div style="font-weight:800; font-size:.9rem; margin-bottom:.35rem;">${name} <span style="font-weight:600; font-size:.7rem; color:var(--muted);">(${data.bundles} bundle)</span></div>
                        <div style="display:flex; gap:.6rem; font-size:.8rem; font-family:monospace; flex-wrap:wrap; margin-top:.45rem;">
                            <div style="display:flex; flex-direction:column; gap:.15rem;">
                                <span style="color:#16a34a; font-weight:700; background:rgba(22,163,74,.1); padding:.15rem .45rem; border-radius:6px; border:1px solid rgba(22,163,74,.2);">OK: ${fmt(data.ok)}</span>
                                <span style="font-size:.65rem; color:var(--muted); text-align:center; font-family:var(--bs-body-font-family); letter-spacing:-.02em;">➔ ${destinationCode}</span>
                            </div>
                            ${data.rjJahit > 0 ? `<div style="display:flex; flex-direction:column; gap:.15rem;">
                                <span style="color:#b91c1c; font-weight:700; background:rgba(185,28,28,.1); padding:.15rem .45rem; border-radius:6px; border:1px solid rgba(185,28,28,.2);">Rj Jahit: ${fmt(data.rjJahit)}</span>
                                <span style="font-size:.65rem; color:var(--muted); text-align:center; font-family:var(--bs-body-font-family); letter-spacing:-.02em;">➔ REJ-SEW</span>
                            </div>` : ''}
                            ${data.rjBahan > 0 ? `<div style="display:flex; flex-direction:column; gap:.15rem;">
                                <span style="color:#b91c1c; font-weight:700; background:rgba(185,28,28,.1); padding:.15rem .45rem; border-radius:6px; border:1px solid rgba(185,28,28,.2);">Rj Bahan: ${fmt(data.rjBahan)}</span>
                                <span style="font-size:.65rem; color:var(--muted); text-align:center; font-family:var(--bs-body-font-family); letter-spacing:-.02em;">➔ REJ-SEW</span>
                            </div>` : ''}
                        </div>
                    </div>
                `;
            }
            
            if (Object.keys(itemsMap).length === 0) {
                html = '<div class="text-center text-muted small py-3">Tidak ada data bundle</div>';
            }
            
            confirmSummary.innerHTML = html;
            confirmQcModal.show();
        });
    }

    const btnConfirmSubmit = document.getElementById('btn-confirm-submit');
    if (btnConfirmSubmit) {
        btnConfirmSubmit.addEventListener('click', function() {
            this.disabled = true;
            this.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Menyimpan...';
            form.submit();
        });
    }
</script>
@endpush
