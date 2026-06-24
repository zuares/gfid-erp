{{-- resources/views/production/sewing_returns/create.blade.php --}}
@extends('layouts.app')

@section('title', 'Produksi • Setoran Jahit')

@php
    $errors = $errors ?? new \Illuminate\Support\ViewErrorBag;
@endphp

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

    </style>
@endpush

@section('content')
@php
    use Carbon\Carbon;

    $dateValue = old('date', now()->toDateString());
    $selectedOperatorId = (string) (request('operator_id', old('operator_id', $operatorId ?? '')) ?? '');
    $selectedPickupDate = (string) (request('pickup_date', '') ?? '');
    $selectedItemCode = strtoupper((string) (request('item', '') ?? ''));

    $isAllMode = $selectedOperatorId === '' || $selectedOperatorId === '0';

    $isRejectReworkMode = (bool) ($isRejectReworkMode ?? false);
    $lines = $lines ?? collect();
    $hasRejectRows = $lines->contains(fn($line) => (int) ($line->source_reject_return_line_id ?? 0) > 0 || (int) ($line->source_finishing_job_line_id ?? 0) > 0);
    $pageTitle = $isRejectReworkMode ? 'Setor Ulang Reject Jahit' : 'Setoran Jahit';
    $sourceStockLabel = $isRejectReworkMode ? 'REJ-SEW' : ($hasRejectRows ? 'STOK' : 'WIP');
    $remainingLabel = $isRejectReworkMode ? 'SISA REJECT' : ($hasRejectRows ? 'SISA' : 'BELUM');

    $itemOptions = $lines
        ->map(fn($l) => strtoupper(optional($l->finishedItem)->code ?? ''))
        ->filter()
        ->unique()
        ->sort()
        ->values();

    $pickupDateOptions = $lines
        ->map(fn($l) => optional($l->sewingPickup)->date)
        ->filter()
        ->map(fn($d) => Carbon::parse($d)->toDateString())
        ->unique()
        ->sortDesc()
        ->values();

    $pickupDateLabel = function ($d) {
        try { return Carbon::parse($d)->locale('id')->translatedFormat('l, d M Y'); }
        catch (\Throwable $e) { return $d; }
    };

    // controller mengirim:
    // - $destinationWarehouses (collection)
    // - $defaultDestWarehouseId (int)
    // - $canChooseDestination (bool)
    $destinationWarehouses = $destinationWarehouses ?? collect();
    $defaultDestWarehouseId = (int) ($defaultDestWarehouseId ?? 0);

    $selectedDestId = (int) old('destination_warehouse_id', $defaultDestWarehouseId);
    $canChooseDestination = (bool) ($canChooseDestination ?? false);

    // ===== ALL MODE: build accordion per item -> operator breakdown (sum remaining)
    $groupsAll = collect();
    if ($isAllMode && $lines->isNotEmpty()) {
        $groupsAll = $lines
            ->map(function ($l) {
                $itemCode = strtoupper(optional($l->finishedItem)->code ?? 'ITEM-' . (int) ($l->finished_item_id ?? 0));

                $pickup = $l->sewingPickup;
                $opCode = $pickup?->operator?->code ?? null;
                $opName = $pickup?->operator?->name ?? null;
                $opId = (int) ($pickup?->operator_id ?? 0);
                $opLabel = trim(($opCode ? $opCode . ' — ' : '') . ($opName ?? ''));
                if ($opLabel === '' && $opId > 0) $opLabel = 'OP-' . $opId;

                $remaining = (float) ($l->remaining_qty ?? 0);
                $wip = (float) ($l->wip_stock ?? 0);

                return [
                    'item_code' => $itemCode,
                    'operator_id' => $opId,
                    'operator_label' => $opLabel,
                    'remaining' => $remaining,
                    'wip' => $wip,
                    'pickup_date' => $pickup?->date ? Carbon::parse($pickup?->date)->toDateString() : '',
                ];
            })
            ->filter(fn($x) => (float) $x['remaining'] > 0.000001)
            ->groupBy('item_code')
            ->map(function ($rows, $itemCode) {
                $rows = collect($rows);

                $ops = $rows
                    ->groupBy(fn($r) => $r['operator_label'] ?: 'OP-' . (int) $r['operator_id'])
                    ->map(function ($opRows, $label) {
                        $opRows = collect($opRows);
                        return [
                            'label' => $label,
                            'operator_id' => (int) ($opRows->first()['operator_id'] ?? 0),
                            'remaining_sum' => (float) $opRows->sum('remaining'),
                            'lines_count' => (int) $opRows->count(),
                        ];
                    })
                    ->values()
                    ->sortBy('label')
                    ->values();

                return [
                    'item_code' => $itemCode,
                    'op_count' => (int) ($ops->count()),
                    'remaining_sum' => (float) $rows->sum('remaining'),
                    'wip' => (float) $rows->max('wip'),
                    'pickup_dates' => $rows->pluck('pickup_date')->filter()->unique()->sortDesc()->values(),
                    'ops' => $ops,
                ];
            })
            ->sortKeys()
            ->values();
    }

    $summaryItems = $groupsAll->count();
    $summaryRemaining = (float) $groupsAll->sum('remaining_sum');
    $summaryOps = $groupsAll->flatMap(fn($g) => collect($g['ops'])->pluck('operator_id'))->filter(fn($id) => (int) $id > 0)->unique()->count();
    $summaryBundles = (int) $groupsAll->sum(fn($g) => collect($g['ops'] ?? [])->sum('lines_count'));

    $lineBundleCount = (int) $lines->count();
    $lineRemainingTotal = (float) $lines->sum(fn($l) => (float) ($l->remaining_qty ?? 0));
    $lineReturnedTotal = (float) $lines->sum(fn($l) => (float) ($l->qty_returned_ok ?? 0) + (float) ($l->qty_returned_reject ?? 0));
    $lineMaxSetorTotal = (float) $lines->sum(function ($l) {
        $remaining = (float) ($l->remaining_qty ?? 0);
        $wip = (float) ($l->wip_stock ?? 0);
        $max = max(0, min($remaining, $wip));
        if ((bool) ($l->supply_partial ?? false)) {
            $max = max(0, min($max, (float) ($l->supply_max_setor ?? 0)));
        }

        return $max;
    });
@endphp

<div class="page-wrap">

    {{-- DEVELOPER MODE — hanya tampil untuk akun developer --}}
    @if (auth()->check() && auth()->user()->isDeveloper())
    <div style="
        background: #0f172a;
        border: 1px solid #334155;
        border-left: 3px solid #6366f1;
        border-radius: 12px;
        padding: 10px 14px;
        margin-bottom: 14px;
        display: flex;
        align-items: center;
        gap: 12px;
        flex-wrap: wrap;
    ">
        {{-- Label --}}
        <div style="display:flex; align-items:center; gap:7px; flex-shrink:0;">
            <span style="font-size:.78rem;">🔧</span>
            <span style="font-weight:800; font-size:.78rem; color:#94a3b8; letter-spacing:.05em;">DEV MODE</span>
        </div>

        {{-- Divider --}}
        <div style="width:1px; height:18px; background:#1e293b; flex-shrink:0;"></div>

        {{-- Dry Run toggle --}}
        <label id="dev-dry-run-label" style="
            display: flex; align-items: center; gap: 7px; cursor: pointer;
            background: #0f2a1a; border: 1px solid #166534;
            border-radius: 8px; padding: 6px 12px;
            transition: all .15s;
        ">
            <input type="checkbox" id="dev-dry-run-chk" checked
                   style="width:15px; height:15px; accent-color:#22c55e; cursor:pointer; flex-shrink:0;">
            <span style="font-size:.8rem; font-weight:700; color:#86efac; white-space:nowrap; user-select:none;">
                Dry Run
                <span style="font-weight:400; color:#4ade80; font-size:.72rem;">— tidak menyimpan data</span>
            </span>
        </label>
        <input type="hidden" name="dry_run" id="dev-dry-run-input" value="1" form="sewing-return-form">
    </div>
    @endif

    {{-- Hasil Dry Run --}}
    @if (session('dev_dry_run'))
    @php $dr = session('dev_dry_run'); @endphp
    <div style="
        background: #0a1f14;
        border: 1px solid #166534;
        border-left: 3px solid #22c55e;
        border-radius: 12px;
        padding: 12px 16px;
        margin-bottom: 14px;
    ">
        <div style="display:flex; align-items:center; gap:8px; margin-bottom:8px;">
            <span style="font-size:.85rem;">🧪</span>
            <span style="font-weight:800; font-size:.85rem; color:#4ade80;">Dry Run Selesai</span>
            <span style="
                background: #14532d; border-radius:6px;
                padding:.15rem .55rem; font-size:.72rem; font-weight:700;
                color: #86efac;
            ">{{ $dr['ok'] ? '✓ Validasi lolos' : '✕ Validasi gagal' }}</span>
            <span style="font-size:.75rem; color:#4b7a5e; margin-left:auto;">Data tidak tersimpan</span>
        </div>

        @if (!empty($dr['code']))
        <div style="
            background:#0f2a1a; border-radius:8px; padding:8px 12px;
            font-size:.8rem; color:#86efac; margin-bottom:8px;
        ">
            <span style="color:#4b7a5e;">Kode SR</span>
            <strong style="font-family:monospace; color:#4ade80; margin:0 6px;">{{ $dr['code'] }}</strong>
            <span style="color:#4b7a5e;">•</span>
            <span style="color:#4b7a5e; margin-left:6px;">Tanggal</span>
            <strong style="font-family:monospace; color:#86efac; margin-left:6px;">{{ $dr['date'] }}</strong>
        </div>
        @endif

        @if (!empty($dr['lines']))
        <div style="display:grid; gap:4px;">
            @foreach ($dr['lines'] as $dl)
            <div style="
                background:#0f2a1a; border-radius:7px; padding:6px 12px;
                display:flex; align-items:center; gap:10px;
                font-size:.78rem; font-family:monospace;
            ">
                <span style="font-weight:800; color:#4ade80; min-width:80px;">{{ $dl['item'] ?? '-' }}</span>
                <span style="color:#4b7a5e;">setor</span>
                <strong style="color:#86efac;">{{ $dl['qty_ok'] }}</strong>
                <span style="color:#334155;">•</span>
                <span style="color:#4b7a5e;">reject</span>
                <strong style="color:#fca5a5;">{{ $dl['qty_reject'] }}</strong>
            </div>
            @endforeach
        </div>
        @endif
    </div>
    @endif

    @if (session('status'))
        <div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
            {{ session('status') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if (isset($errors) && $errors->any())
        <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
            @foreach ($errors->all() as $err)
                <div>⚠ {{ $err }}</div>
            @endforeach
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="panel mb-2">
        <div class="panel-h">
            <div class="d-flex align-items-start justify-content-between gap-2 flex-wrap">
                <div>
                    <div class="h-title">{{ $pageTitle }}</div>
                    @if ($isRejectReworkMode)
                        <div class="text-muted small mt-1">Sumber stok dari REJ-SEW, lalu disetor ulang ke gudang tujuan.</div>
                    @endif
                </div>

                <a href="{{ $isRejectReworkMode ? route('production.sewing.reject_returns.index') : route('production.sewing.pickups.create') }}"
                   class="btn btn-sm btn-outline-success"
                   style="border-radius:999px;">
                    {{ $isRejectReworkMode ? 'List Reject Jahit' : 'Sewing Pickup' }}
                </a>
            </div>
        </div>
    </div>

    <div class="panel">
        <form id="sewing-return-form" action="{{ route('production.sewing.returns.store') }}" method="POST" novalidate>
            @csrf

            <div class="panel-b">

                <div class="meta">
                    <div class="row align-items-end return-filter-row">

                        <div class="col-5 col-lg-2">
                            <label class="form-label form-label-sm">Return</label>
                            <input type="date" name="date"
                                   class="form-control form-control-sm @error('date') is-invalid @enderror"
                                   value="{{ $dateValue }}">
                            @error('date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-7 col-lg-3">
                            <label class="form-label form-label-sm">Operator</label>
                            <select id="operator" name="operator_id"
                                    class="form-select form-select-sm @error('operator_id') is-invalid @enderror">
                                <option value="">SEMUA</option>
                                @foreach ($operators as $op)
                                    <option value="{{ $op->id }}" @selected((string) $selectedOperatorId === (string) $op->id)>
                                        {{ $op->code ? $op->code . ' — ' : '' }}{{ $op->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('operator_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-lg-2 d-none d-lg-block" id="dest-wrap">
                            <label class="form-label form-label-sm">Tujuan</label>
                            <input type="hidden" id="destination" name="destination_warehouse_id" value="{{ (int) $defaultDestWarehouseId }}"
                                   data-label="{{ optional($destinationWarehouses->firstWhere('id', $defaultDestWarehouseId))->code ?? 'WH-PRD' }} — {{ optional($destinationWarehouses->firstWhere('id', $defaultDestWarehouseId))->name ?? 'Gudang Produksi' }}">
                            <div class="form-control form-control-sm mono return-dest-pill">
                                <span class="return-dest-code">{{ optional($destinationWarehouses->firstWhere('id', $defaultDestWarehouseId))->code ?? 'WH-PRD' }}</span>
                                <span class="return-dest-name">— {{ optional($destinationWarehouses->firstWhere('id', $defaultDestWarehouseId))->name ?? 'Gudang Produksi' }}</span>
                            </div>
                            @error('destination_warehouse_id')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-5 col-lg-2">
                            <label class="form-label form-label-sm">Item</label>
                            <select id="item-filter" class="form-select form-select-sm">
                                <option value="">Semua</option>
                                @foreach ($itemOptions as $code)
                                    <option value="{{ $code }}" @selected($selectedItemCode === strtoupper($code))>{{ $code }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-lg-2 d-none d-lg-block">
                            <label class="form-label form-label-sm">Pickup</label>
                            <select id="pickup-date" class="form-select form-select-sm">
                                <option value="">Semua tanggal</option>
                                @foreach ($pickupDateOptions as $d)
                                    <option value="{{ $d }}" @selected($selectedPickupDate === $d)>
                                        {{ $pickupDateLabel($d) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-7 col-lg-1">
                            <label class="form-label form-label-sm">Cari</label>
                            <input type="text" id="q" class="form-control form-control-sm mono"
                                   placeholder="Kode" autocomplete="off">
                        </div>
                    </div>

                    <div class="mini-kpi-row {{ $isAllMode ? 'kpi-4' : 'kpi-3' }}">
                        @if ($isAllMode)
                            <div class="mini-kpi">
                                <span class="lbl">Item</span>
                                <span class="val mono">{{ number_format($summaryItems, 0, ',', '.') }}</span>
                            </div>
                            <div class="mini-kpi">
                                <span class="lbl">Iket</span>
                                <span class="val mono">{{ number_format($summaryBundles, 0, ',', '.') }}</span>
                            </div>
                            <div class="mini-kpi is-main">
                                <span class="lbl">{{ $isRejectReworkMode ? 'Reject' : 'Belum Setor' }}</span>
                                <span class="val mono">
                                    <span>{{ number_format($summaryRemaining, 0, ',', '.') }}</span>
                                    <span class="unit">pcs</span>
                                </span>
                            </div>
                            <div class="mini-kpi">
                                <span class="lbl">Penjahit</span>
                                <span class="val mono">{{ number_format($summaryOps, 0, ',', '.') }}</span>
                            </div>
                        @else
                            <div class="mini-kpi">
                                <span class="lbl">Bundle</span>
                                <span class="val mono">{{ number_format($lineBundleCount, 0, ',', '.') }}</span>
                            </div>
                            <div class="mini-kpi is-main">
                                <span class="lbl">Maks</span>
                                <span class="val mono">
                                    <span>{{ number_format($lineMaxSetorTotal, 0, ',', '.') }}</span>
                                    <span class="unit">pcs</span>
                                </span>
                            </div>
                            <div class="mini-kpi">
                                <span class="lbl">Sudah</span>
                                <span class="val mono">
                                    <span>{{ number_format($lineReturnedTotal, 0, ',', '.') }}</span>
                                    <span class="unit">pcs</span>
                                </span>
                            </div>
                        @endif
                    </div>

                    <div class="top-actions mt-2 d-none d-lg-flex" id="top-actions-input" style="{{ $isAllMode ? 'display:none;' : '' }}">
                        <div class="d-flex gap-2 flex-wrap align-items-center">
                            <span class="pill">Total Iket: <span class="mono" id="stat-total-rows">0</span></span>
                            <span class="pill">Sudah Setor: <span class="mono" id="stat-returned">0,00</span></span>
                            <span class="pill">Di Setor: <span class="mono" id="stat-picked-rows">0</span></span>
                            <span class="pill">{{ $isRejectReworkMode ? 'Total Setor' : 'Total OK' }}: <span class="mono" id="stat-total-ok">0,00</span></span>
                        </div>

                        <div class="d-flex gap-2 align-items-center">
                            <button type="button" class="btn btn-sm btn-outline-secondary btn-mini" id="btn-check-visible">
                                Pilih semua (tampil)
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary btn-mini" id="btn-uncheck-all">
                                Reset semua
                            </button>
                        </div>
                    </div>
                </div>

                {{-- ===== ALL MODE (SEMUA) : ACCORDION ===== --}}
                <div class="list" id="list-all" style="{{ $isAllMode ? '' : 'display:none;' }}">
                    @if ($lines->isEmpty() || $groupsAll->isEmpty())
                        <div class="text-center py-4 text-muted">Tidak ada baris yang bisa disetor.</div>
                    @else
                        <div class="sum-box mb-2">
                            <div class="sum-top">
                                <div><div class="ttl">{{ $isRejectReworkMode ? 'Reject Siap Disetor' : 'Belum Disetor' }}</div></div>
                                <div class="text-end">
                                    <div class="sub">Update: <span class="mono">{{ now()->format('H:i') }}</span></div>
                                </div>
                            </div>
                            <div class="sum-pillrow" style="grid-template-columns: repeat(4, minmax(0, 1fr));">
                                <div class="sum-pill"><span class="lbl">Item</span><span class="val mono">{{ number_format($summaryItems, 0, ',', '.') }}</span></div>
                                <div class="sum-pill"><span class="lbl">Iket</span><span class="val mono">{{ number_format($summaryBundles, 0, ',', '.') }}</span></div>
                                <div class="sum-pill"><span class="lbl">{{ $isRejectReworkMode ? 'Reject' : 'Belum Setor' }}</span><span class="val mono">{{ number_format($summaryRemaining, 0, ',', '.') }}</span></div>
                                <div class="sum-pill"><span class="lbl">Penjahit</span><span class="val mono">{{ number_format($summaryOps, 0, ',', '.') }}</span></div>
                            </div>
                        </div>

                        <div class="accordion" id="all-items-accordion">
                            @php $no = 0; @endphp
                            @foreach ($groupsAll as $gidx => $g)
                                @php
                                    $no++;
                                    $itemCode = $g['item_code'];
                                    $opCount = (int) ($g['op_count'] ?? 0);
                                    $remainingSum = (float) ($g['remaining_sum'] ?? 0);
                                    $wip = (float) ($g['wip'] ?? 0);
                                    $ops = collect($g['ops'] ?? []);
                                    $bundleCount = (int) $ops->sum('lines_count');
                                    $collapseId = 'allItemCollapse-' . $gidx;
                                    $headingId = 'allItemHeading-' . $gidx;
                                @endphp

                                <div class="accordion-item acc-item all-card" data-code="{{ $itemCode }}" data-item="{{ $itemCode }}">
                                    <h2 class="accordion-header" id="{{ $headingId }}">
                                        <button class="accordion-button collapsed acc-op-btn" type="button"
                                                data-bs-toggle="collapse" data-bs-target="#{{ $collapseId }}"
                                                aria-expanded="false" aria-controls="{{ $collapseId }}">
                                            <div class="all-btn-main">
                                                <div class="min-w-0">
                                                    <div class="mono text-truncate all-code">{{ $itemCode }}</div>
                                                    <div class="all-subline">
                                                        <span class="all-op-count">{{ number_format($bundleCount, 0, ',', '.') }} iket</span>
                                                        <span class="all-op-count">{{ number_format($opCount, 0, ',', '.') }} penjahit</span>
                                                    </div>
                                                </div>
                                                <div class="all-metrics">
                                                    <span class="all-pill-main">
                                                        <span class="lbl">{{ $isRejectReworkMode ? 'Reject' : 'Belum' }}</span>
                                                        <span class="val mono">{{ number_format($remainingSum, 0, ',', '.') }}</span>
                                                        <span class="unit">pcs</span>
                                                    </span>
                                                    <span class="acc-pill d-none d-lg-inline-flex">{{ $sourceStockLabel }} <span class="mono">{{ number_format($wip, 0, ',', '.') }}</span></span>
                                                </div>
                                            </div>
                                        </button>
                                    </h2>

                                    <div id="{{ $collapseId }}" class="accordion-collapse collapse"
                                         aria-labelledby="{{ $headingId }}" data-bs-parent="#all-items-accordion">
                                        <div class="accordion-body all-accordion-body">
                                            @if ($ops->isEmpty())
                                                <div class="text-muted text-center py-2">Tidak ada detail operator.</div>
                                            @else
                                                <div class="all-detail-list">
                                                    @foreach ($ops as $op)
                                                        <div class="all-detail-row js-open-operator-item"
                                                             role="button"
                                                             tabindex="0"
                                                             title="Buka {{ $op['label'] ?: 'Penjahit ' . $op['operator_id'] }} - {{ $itemCode }}"
                                                             data-operator-id="{{ (int) ($op['operator_id'] ?? 0) }}"
                                                             data-item-code="{{ $itemCode }}">
                                                            <div class="mono all-op-name">
                                                                {{ $op['label'] ?: 'Penjahit ' . $op['operator_id'] }}
                                                            </div>
                                                            <div class="all-detail-right">
                                                                <div class="mono all-detail-val">
                                                                    {{ number_format((float) $op['remaining_sum'], 0, ',', '.') }}
                                                                    <span class="all-detail-unit">pcs</span>
                                                                </div>
                                                                <div class="all-detail-sub">
                                                                    {{ number_format((int) $op['lines_count'], 0, ',', '.') }} iket
                                                                </div>
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                {{-- ===== BYOP MODE (operator dipilih): LIST INPUT ===== --}}
                <div class="list" id="list-byop" style="{{ $isAllMode ? 'display:none;' : '' }}">
                    @if ($lines->isEmpty())
                        <div class="text-center py-4 text-muted">Tidak ada baris yang bisa disetor.</div>
                    @else
                        @php
                            $linesByCode = $lines->groupBy(
                                fn($l) => strtoupper($l->finishedItem?->code ?? 'ITEM-' . $l->finished_item_id)
                            )->sortKeys();
                        @endphp
                        @foreach ($linesByCode as $groupCode => $groupLines)
                        @foreach ($groupLines as $line)
                            @php $idx = $lines->search(fn($l) => $l->id === $line->id); @endphp
                            @php
                                $item = $line->finishedItem;
                                $pickup = $line->sewingPickup;

                                $code = strtoupper($item?->code ?? 'ITEM-' . $line->finished_item_id);
                                $pickupDateRaw = $pickup?->date ? Carbon::parse($pickup->date)->toDateString() : '';
                                $pickupDateText = $pickup?->date ? Carbon::parse($pickup->date)->locale('id')->translatedFormat('D, d M') : '-';

                                $opCode = $pickup?->operator?->code ?? null;
                                $opName = $pickup?->operator?->name ?? null;
                                $opLabel = trim(($opCode ? $opCode . ' — ' : '') . ($opName ?? ''));

                                $remaining = (float) ($line->remaining_qty ?? 0);
                                $wip = (float) ($line->wip_stock ?? 0);
                                $isRejectLine = (int) ($line->source_reject_return_line_id ?? 0) > 0 || (int) ($line->source_finishing_job_line_id ?? 0) > 0;
                                $rowRemainingLabel = $isRejectLine ? 'SISA REJECT' : 'BELUM';
                                $rowSourceStockLabel = $isRejectLine ? 'REJ-SEW' : 'WIP';

                                $oldRow = old("results.$idx", []);
                                $okVal = $oldRow['qty_ok'] ?? '';
                                $rjVal = $oldRow['qty_reject'] ?? '';
                                $notes = $oldRow['notes'] ?? '';
                                $showNotes = (float) ($rjVal ?: 0) > 0 || trim((string) $notes) !== '';
                                $alreadyReturned = (float) ($line->qty_returned_ok ?? 0) + (float) ($line->qty_returned_reject ?? 0);
                                $supplyIncomplete    = (bool) ($line->supply_incomplete ?? false);
                                $supplyPartial       = (bool) ($line->supply_partial ?? false);
                                $supplyMaxSetor      = (int) ($line->supply_max_setor ?? 0);
                                $maxSetor = max(0, min($remaining, $wip));
                                if (!$isRejectLine && $supplyPartial) {
                                    $maxSetor = max(0, min($maxSetor, $supplyMaxSetor));
                                }
                                $supplyShortageLabel = (string) ($line->supply_shortage_label ?? '');
                                $supplyShortCount    = $supplyShortageLabel !== '' ? count(array_filter(array_map('trim', explode(';', $supplyShortageLabel)))) : 0;
                                $supplyHintText      = $supplyShortCount > 0
                                    ? $supplyShortCount . ' bahan perlu dilengkapi'
                                    : '';
                                $supplyActionText    = $supplyIncomplete ? 'Lengkapi' : ($supplyPartial ? 'Cek' : 'Detail');
                                $supplyUnmigrated    = (bool) ($line->supply_unmigrated ?? false);
                                $isBlocked = $supplyIncomplete; // partial = bisa input, tapi dibatasi
                                $supplyStatusText = $supplyUnmigrated
                                    ? 'Belum dimigrasikan'
                                    : ($supplyIncomplete ? 'Belum lengkap' : ($supplyPartial ? 'Kurang' : 'Lengkap'));
                                $supplyStatusColor = $supplyUnmigrated
                                    ? '#64748b'
                                    : ($supplyIncomplete ? '#dc2626' : ($supplyPartial ? '#b45309' : '#16a34a'));

                                // Prepare back URL untuk link "Isi Kelengkapan"
                                $qs = request()->getQueryString();
                                $backUrl = request()->getPathInfo() . ($qs ? '?' . $qs : '');
                                $suppliesUrl = $line->sewingPickup?->id
                                    ? route('production.sewing.pickups.supplies.edit', $line->sewingPickup->id)
                                        . '?redirect_to=' . urlencode($backUrl)
                                        . '&line_id=' . $line->id
                                    : null;
                                $suppliesUpdateUrl = $line->sewingPickup?->id
                                    ? route('production.sewing.pickups.supplies.update', $line->sewingPickup->id)
                                    : null;
                                $lineSuppliesUpdateUrl = route('production.sewing.pickups.lines.supplies.update', $line->id);
                            @endphp

                            <div class="cardx mono fin-item"
                                 data-code="{{ $code }}"
                                 data-item="{{ $code }}"
                                 data-pickupdate="{{ $pickupDateRaw }}"
                                 data-remaining="{{ $remaining }}"
                                 data-wip="{{ $wip }}"
                                 data-returned="{{ $alreadyReturned }}"
                                 data-supply-blocked="{{ $supplyIncomplete ? 1 : 0 }}"
                                 data-supply-partial="{{ $supplyPartial ? 1 : 0 }}"
                                 data-supply-max-setor="{{ $supplyPartial ? $supplyMaxSetor : '' }}">
                                <div class="cardx-h">
                                    <div class="cardx-left">
                                        <input type="checkbox" class="chk row-check" aria-label="Pilih baris" {{ $isBlocked ? 'disabled' : '' }}>
                                        <div>
                                            <div class="code">{{ $code }}</div>
                                            <div class="meta-inline">
                                                <span class="dot">•</span>
                                                <span>{{ $pickupDateText }}</span>
                                                @if ($opLabel !== '')
                                                    <span class="dot op-dot">•</span>
                                                    <span class="truncate op-label" title="{{ $opLabel }}">OP: {{ $opLabel }}</span>
                                                @endif
                                            </div>
                                            @if (!$isRejectLine)
                                                <button type="button"
                                                        class="js-supply-modal-btn supply-mini-btn"
                                                        data-line-id="{{ $line->id }}"
                                                        data-update-url="{{ $lineSuppliesUpdateUrl }}">
                                                    <span class="supply-mini-status" style="color:{{ $supplyStatusColor }};">
                                                        {{ $supplyStatusText }}
                                                    </span>
                                                    @if ($supplyHintText)
                                                        <span class="supply-mini-hint">
                                                            {{ $supplyHintText }}
                                                        </span>
                                                    @endif
                                                    <span class="supply-mini-action">
                                                        {{ $supplyActionText }}
                                                    </span>
                                                </button>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="right-metrics card-metrics">
                                        <div class="metric-main">
                                            <span class="lbl">Maks</span>
                                            <span class="val">{{ number_format($maxSetor, 0, ',', '.') }}</span>
                                        </div>
                                        <div class="metric-sub">
                                            {{ $rowRemainingLabel }} {{ number_format($remaining, 0, ',', '.') }}
                                        </div>
                                        @if ($alreadyReturned > 0)
                                            <div class="metric-sub is-returned">
                                                Sudah {{ number_format($alreadyReturned, 0, ',', '.') }}
                                            </div>
                                        @endif
                                        <div class="metric-sub wip-line">
                                            {{ $rowSourceStockLabel }} {{ number_format($wip, 0, ',', '.') }}
                                        </div>
                                    </div>
                                </div>

                                <div class="cardx-b">
                                    <div class="grid2">
                                        <div class="field">
                                            <label>{{ $isRejectLine ? 'Setor Ulang' : 'OK' }}</label>
                                            <input type="number" step="1" min="0"
                                                   inputmode="numeric"
                                                   class="form-control form-control-sm qty ok num-input select-all-on-focus {{ $supplyPartial ? 'border-warning' : '' }}"
                                                   name="results[{{ $idx }}][qty_ok]"
                                                   value="{{ $okVal }}" placeholder="0" {{ $isBlocked ? 'disabled' : '' }}>
                                        </div>

                                        @if ($isRejectLine)
                                            <input type="hidden" name="results[{{ $idx }}][qty_reject]" value="0">
                                            <div class="field">
                                                <label>Sumber</label>
                                                <div class="form-control form-control-sm mono" style="border-radius:8px; font-weight:900; text-align:center;">
                                                    {{ $line->reject_code ?? 'REJ-SEW' }}
                                                </div>
                                            </div>
                                        @else
                                            <div class="field">
                                                <label>Reject</label>
                                                <input type="number" step="0.01" min="0" inputmode="decimal"
                                                       class="form-control form-control-sm qty rj num-input select-all-on-focus"
                                                       name="results[{{ $idx }}][qty_reject]"
                                                       value="{{ $rjVal }}" placeholder="0" {{ $isBlocked ? 'disabled' : '' }}>
                                            </div>
                                        @endif
                                    </div>

                                    <div class="notes {{ (!$isRejectLine && $showNotes) ? 'is-show' : '' }}">
                                        <input type="text" class="form-control form-control-sm"
                                               name="results[{{ $idx }}][notes]"
                                               placeholder="Catatan reject (opsional)" value="{{ $notes }}">
                                    </div>

                                    <input type="hidden" name="results[{{ $idx }}][sewing_pickup_line_id]" value="{{ $line->id }}">
                                    @if ((int) ($line->source_reject_return_line_id ?? 0) > 0)
                                        <input type="hidden" name="results[{{ $idx }}][source_reject_return_line_id]" value="{{ (int) $line->source_reject_return_line_id }}">
                                    @endif
                                    @if ((int) ($line->source_finishing_job_line_id ?? 0) > 0)
                                        <input type="hidden" name="results[{{ $idx }}][source_finishing_job_line_id]" value="{{ (int) $line->source_finishing_job_line_id }}">
                                    @endif
                                </div>
                            </div>
                        @endforeach
                        @endforeach {{-- end groupCode --}}
                    @endif
                </div>

                {{-- FAB (hanya mode input) --}}
                <div class="fab-wrap" id="fab-wrap" style="{{ $isAllMode ? 'display:none;' : '' }}">
                    <a href="{{ route('production.sewing.returns.index') }}"
                       class="btn btn-sm btn-outline-secondary fab-back">←</a>
                    <button type="button" class="btn btn-sm btn-success fab-save" id="btn-open-modal" disabled>
                        Simpan
                    </button>
                </div>

            </div>
        </form>
    </div>
</div>

{{-- ══ DATA SUPPLY PER LINE / BUNDLE (untuk modal per card) ══ --}}
@php
$supplyDataByLineId = [];

foreach ($lines as $l) {
    $pu = $l->sewingPickup;
    if (!$pu) continue;

    $lineSupplyLines = $l->supplyLines ?? collect();
    $bundleQty = max((float) ($l->qty_bundle ?? 0), 0);
    $remainingQty = max((float) ($l->remaining_qty ?? 0), 0);
    $pickupDateText = $pu?->date ? \Illuminate\Support\Carbon::parse($pu->date)->format('d/m/Y') : '-';
    $itemCode = strtoupper($l->finishedItem?->code ?? 'ITEM-' . $l->finished_item_id);
    $modalLines = $lineSupplyLines->map(function ($sl) use ($bundleQty) {
        $requiredQty = (float) ($sl->required_qty ?? 0);
        $issuedQty = (float) ($sl->issued_qty ?? 0);
        $qtyPerPcs = $bundleQty > 0.000001 ? ($requiredQty / $bundleQty) : 0;

        return [
            'material_item_id' => (int) $sl->material_item_id,
            'code' => $sl->materialItem?->code ?? 'Bahan',
            'name' => $sl->materialItem?->name ?? '-',
            'required_qty' => $requiredQty,
            'issued_qty' => $issuedQty,
            'required_pcs' => $bundleQty,
            'issued_pcs' => $qtyPerPcs > 0.000001 ? floor(($issuedQty / $qtyPerPcs) + 0.00001) : 0,
            'qty_per_pcs' => $qtyPerPcs,
            'uom' => (string) ($sl->uom ?: $sl->materialItem?->unit ?: ''),
            'notes' => (string) ($sl->notes ?? ''),
        ];
    })->values();

    if ($modalLines->isEmpty()) {
        $pickupBundleLines = ($pu->lines ?? collect())->whereNull('voided_at')->values();
        $totalPickupQty = (float) $pickupBundleLines->sum('qty_bundle');
        $lineFraction = $totalPickupQty > 0.000001 ? ((float) ($l->qty_bundle ?? 0) / $totalPickupQty) : 1.0;

        $modalLines = ($pu->supplyLines ?? collect())->map(fn($sl) => [
            'material_item_id' => (int) $sl->material_item_id,
            'code' => $sl->material?->code ?? 'Bahan',
            'name' => $sl->material?->name ?? '-',
            'required_qty' => round((float) ($sl->required_qty ?? 0) * $lineFraction, 4),
            'issued_qty' => round((float) ($sl->issued_qty ?? 0) * $lineFraction, 4),
            'required_pcs' => $bundleQty,
            'issued_pcs' => round((float) ($sl->issued_pcs ?? 0) * $lineFraction, 4),
            'qty_per_pcs' => $bundleQty > 0.000001
                ? round(((float) ($sl->required_qty ?? 0) * $lineFraction) / $bundleQty, 8)
                : 0,
            'uom' => (string) ($sl->uom ?: $sl->material?->unit ?: ''),
            'notes' => '',
        ])->values();
    }

    $supplyDataByLineId[$l->id] = [
        'code' => $pu->code . ' · ' . $itemCode,
        'pickup_date' => $pickupDateText,
        'item_code' => $itemCode,
        'remaining_qty' => $remainingQty,
        'lines' => $modalLines->all(),
    ];
}
@endphp

{{-- ══ MODAL KELENGKAPAN JAHIT (inline) ══ --}}
<div class="modal fade" id="supplyModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-sm">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h5 class="modal-title mb-0" id="supplyModalTitle">Kelengkapan Jahit</h5>
                <button type="button" class="btn-close ms-2" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="supplyModalBody">
                <div class="text-center text-muted py-3" id="supplyModalLoading">Memuat…</div>
            </div>
            <div class="modal-footer py-2 gap-1">
                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-sm btn-primary" id="supplyModalSave">Simpan</button>
            </div>
        </div>
    </div>
</div>

{{-- MODAL CONFIRM --}}
<div class="modal fade" id="confirmModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content" style="border-radius:16px;">
            <div class="modal-header">
                <h5 class="modal-title">Konfirmasi Simpan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <div class="p-3 border bg-light" style="border-radius:14px;">
                    <div class="d-flex justify-content-between align-items-start gap-2 flex-wrap">
                        <div style="font-weight:900;">Ringkasan</div>
                        <div class="text-muted" style="font-weight:800;font-size:.86rem;">
                            Baris terisi: <span class="mono" id="m-rows">0</span>
                        </div>
                    </div>

                    <div class="mt-2" style="font-size:.90rem;font-weight:800;color:var(--muted);">
                        Operator: <span class="mono" id="m-op">SEMUA</span><br>
                        Tujuan: <span class="mono" id="m-dest">-</span><br>
                        Total OK: <span class="mono" id="m-ok">0,00</span>
                        • Total Reject: <span class="mono" id="m-rj">0,00</span>
                    </div>
                </div>

                <div class="mt-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div style="font-weight:900;">Detail Setor</div>
                        <div class="text-muted" style="font-weight:800;font-size:.86rem;">
                            Item: <span class="mono" id="m-items-count">0</span>
                        </div>
                    </div>

                    <div class="border" style="border-radius:14px; overflow:hidden;">
                        <div class="px-3 py-2"
                             style="background:rgba(148,163,184,.06); border-bottom:1px solid rgba(148,163,184,.18); font-size:.72rem; font-weight:900; color:var(--muted); text-transform:uppercase; letter-spacing:.10em;">
                            <div class="d-grid" style="grid-template-columns: 44px 1fr 120px 120px; gap:.5rem; align-items:center;">
                                <div>No</div><div>Item</div><div class="text-end">OK / Setor</div><div class="text-end">Reject</div>
                            </div>
                        </div>

                        <div id="m-items" style="max-height:40vh; overflow:auto; -webkit-overflow-scrolling:touch;">
                            <div class="text-center text-muted py-3" id="m-empty">Tidak ada item.</div>
                        </div>
                    </div>
                </div>

                <div class="mt-3 d-none" id="modal-fallback-note">
                    <div class="alert alert-warning mb-0">
                        Modal tidak bisa ditampilkan karena Bootstrap JS belum ter-load (bundle). Tombol <b>Simpan</b> akan submit langsung.
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-sm btn-success" id="btn-confirm-submit">Simpan</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('sewing-return-form');

    const listAll = document.getElementById('list-all');
    const listByOp = document.getElementById('list-byop');

    const operator = document.getElementById('operator');
    const pickupDate = document.getElementById('pickup-date');
    const itemFilter = document.getElementById('item-filter');
    const q = document.getElementById('q');

    // ✅ fixed: sekarang id-nya beneran ada
    const destWrap = document.getElementById('dest-wrap');
    const destination = document.getElementById('destination');

    const topActionsInput = document.getElementById('top-actions-input');
    const fabWrap = document.getElementById('fab-wrap');

    const btnOpenModal = document.getElementById('btn-open-modal');
    const modalEl = document.getElementById('confirmModal');
    const btnConfirm = document.getElementById('btn-confirm-submit');

    const btnCheckVisible = document.getElementById('btn-check-visible');
    const btnUncheckAll = document.getElementById('btn-uncheck-all');

    const mOp = document.getElementById('m-op');
    const mDest = document.getElementById('m-dest');
    const mOk = document.getElementById('m-ok');
    const mRj = document.getElementById('m-rj');
    const mRows = document.getElementById('m-rows');
    const mItemsCount = document.getElementById('m-items-count');
    const mItems = document.getElementById('m-items');
    const mEmpty = document.getElementById('m-empty');

    const statTotalRows = document.getElementById('stat-total-rows');
    const statPickedRows = document.getElementById('stat-picked-rows');
    const statTotalOk = document.getElementById('stat-total-ok');
    const statReturned = document.getElementById('stat-returned');

    const fallbackNote = document.getElementById('modal-fallback-note');

    q?.addEventListener('focus', () => {
        setTimeout(() => {
            try { q.select(); } catch (_) {}
        }, 0);
    });

    q?.addEventListener('click', () => {
        setTimeout(() => {
            try { q.select(); } catch (_) {}
        }, 0);
    });

    const body = document.body;
    const $$ = (sel, root = document) => Array.from(root.querySelectorAll(sel));
    const isAllMode = () => !((operator?.value || '').toString().trim());

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

    function reloadWithFilters() {
        const url = new URL(window.location.href);
        const op = (operator?.value || '').toString();
        const pd = (pickupDate?.value || '').toString();
        const item = (itemFilter?.value || '').toString();

        if (op) url.searchParams.set('operator_id', op);
        else url.searchParams.delete('operator_id');

        if (pd) url.searchParams.set('pickup_date', pd);
        else url.searchParams.delete('pickup_date');

        if (item) url.searchParams.set('item', item);
        else url.searchParams.delete('item');

        window.location.href = url.toString();
    }

    operator?.addEventListener('change', reloadWithFilters);
    pickupDate?.addEventListener('change', reloadWithFilters);

    function openOperatorItem(row) {
        const opId = (row?.dataset?.operatorId || '').toString();
        const itemCode = (row?.dataset?.itemCode || '').toString();
        if (!opId || opId === '0') return;

        const url = new URL(window.location.href);
        url.searchParams.set('operator_id', opId);
        if (itemCode) url.searchParams.set('item', itemCode);
        else url.searchParams.delete('item');

        const pd = (pickupDate?.value || '').toString();
        if (pd) url.searchParams.set('pickup_date', pd);
        else url.searchParams.delete('pickup_date');

        window.location.href = url.toString();
    }

    listAll?.addEventListener('click', (e) => {
        const row = e.target.closest('.js-open-operator-item');
        if (!row) return;
        openOperatorItem(row);
    });

    listAll?.addEventListener('keydown', (e) => {
        if (e.key !== 'Enter' && e.key !== ' ') return;
        const row = e.target.closest('.js-open-operator-item');
        if (!row) return;
        e.preventDefault();
        openOperatorItem(row);
    });

    function sanitizeNum(v) {
        v = (v ?? '').toString().trim();
        if (v === '') return '';
        const n = parseFloat(v);
        if (Number.isNaN(n) || n < 0) return '';
        return String(n);
    }

    function getEls(card) {
        return {
            ok: card.querySelector('input[name*="[qty_ok]"]'),
            rj: card.querySelector('input[name*="[qty_reject]"]'),
            notesWrap: card.querySelector('.notes'),
            cb: card.querySelector('.row-check'),
        };
    }

    function isSupplyBlocked(card) {
        return (card?.dataset?.supplyBlocked || '0') === '1';
    }

    function maxSetorForCard(card) {
        const rem = parseFloat(card.dataset.remaining || '0') || 0;
        const wip = parseFloat(card.dataset.wip || '0') || 0;
        const base = Math.max(0, Math.min(rem, wip));
        const supplyMax = parseFloat(card.dataset.supplyMaxSetor || '');

        if ((card.dataset.supplyPartial || '0') === '1' && Number.isFinite(supplyMax)) {
            return Math.max(0, Math.min(base, supplyMax));
        }

        return base;
    }

    function clampCard(card, changed) {
        const maxSetor = maxSetorForCard(card);
        const { ok, rj } = getEls(card);

        let a = parseFloat(ok?.value || '0'); if (!Number.isFinite(a) || a < 0) a = 0;
        let b = parseFloat(rj?.value || '0'); if (!Number.isFinite(b) || b < 0) b = 0;

        if (a + b > maxSetor) {
            const diff = (a + b) - maxSetor;
            if (changed === 'rj') b = Math.max(0, b - diff);
            else a = Math.max(0, a - diff);
        }

        if (ok) ok.value = (a <= 0) ? '' : String(a);
        if (rj) rj.value = (b <= 0) ? '' : String(b);
    }

    function syncNotes(card) {
        const { rj, notesWrap } = getEls(card);
        if (!notesWrap) return;
        const v = parseFloat(rj?.value || '0') || 0;
        if (v > 0) notesWrap.classList.add('is-show');
        else notesWrap.classList.remove('is-show');
    }

    function syncCheck(card) {
        const { ok, rj, cb } = getEls(card);
        const a = parseFloat(ok?.value || '0') || 0;
        const b = parseFloat(rj?.value || '0') || 0;
        if (cb) cb.checked = ((a + b) > 0);
    }

    function autoFillCard(card) {
        if (isSupplyBlocked(card)) return;
        const fill = maxSetorForCard(card);

        const { ok, rj } = getEls(card);
        if (ok) ok.value = (fill > 0) ? String(fill) : '';
        if (rj) rj.value = '';
        clampCard(card, 'ok');
        syncNotes(card);
        syncCheck(card);
    }

    function setModeUI() {
        const all = isAllMode();

        if (listAll) listAll.style.display = all ? '' : 'none';
        if (listByOp) listByOp.style.display = all ? 'none' : '';

        if (topActionsInput) topActionsInput.style.display = all ? 'none' : (window.innerWidth >= 992 ? 'flex' : 'none');
        if (fabWrap) fabWrap.style.display = all ? 'none' : '';

        // tujuan hanya relevan untuk mode input
        if (destWrap) destWrap.style.display = all ? 'none' : (destWrap.style.display || '');

        if (btnOpenModal) btnOpenModal.disabled = all ? true : btnOpenModal.disabled;
    }

    function computeSubmitEnabled() {
        if (!btnOpenModal || !listByOp) return 0;

        if (isAllMode()) { btnOpenModal.disabled = true; return 0; }

        // tujuan wajib ada nilainya (select owner / hidden input non-owner)
        // kalau owner: select exist
        if (destination && destination.value === '') { btnOpenModal.disabled = true; return 0; }

        let total = 0;
        $$('.fin-item', listByOp).forEach(card => {
            if (card.style.display === 'none') return;
            const { ok, rj } = getEls(card);
            const a = parseFloat(ok?.value || '0') || 0;
            const b = parseFloat(rj?.value || '0') || 0;
            total += (a + b);
        });

        btnOpenModal.disabled = total <= 0;
        return total;
    }

    function computeTopSummary() {
        if (isAllMode() || !listByOp) return;

        let totalRows = 0;
        let pickedRows = 0;
        let totalOk = 0;
        let totalReturned = 0;

        $$('.fin-item', listByOp).forEach(card => {
            if (card.style.display === 'none') return;
            totalRows++;

            const ok = parseFloat(card.querySelector('input[name*="[qty_ok]"]')?.value || '0') || 0;
            const rj = parseFloat(card.querySelector('input[name*="[qty_reject]"]')?.value || '0') || 0;

            if ((ok + rj) > 0) {
                pickedRows++;
                totalOk += ok;
            }

            totalReturned += parseFloat(card.dataset.returned || '0') || 0;
        });

        if (statTotalRows) statTotalRows.textContent = totalRows.toLocaleString('id-ID');
        if (statPickedRows) statPickedRows.textContent = pickedRows.toLocaleString('id-ID');
        if (statTotalOk) statTotalOk.textContent = totalOk.toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        if (statReturned) statReturned.textContent = totalReturned.toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function applyFilter() {
        const term = (q?.value || '').toString().trim().toUpperCase();
        const selItem = (itemFilter?.value || '').toString().trim().toUpperCase();

        if (isAllMode()) {
            if (!listAll) return;
            $$('.acc-item', listAll).forEach(card => {
                const code = (card.dataset.code || '').toString().toUpperCase();
                const item = (card.dataset.item || '').toString().toUpperCase();
                const matchSearch = !term || code.includes(term);
                const matchItem = !selItem || item === selItem;
                card.style.display = (matchSearch && matchItem) ? '' : 'none';
            });
            return;
        }

        if (!listByOp) return;
        $$('.fin-item', listByOp).forEach(card => {
            const code = (card.dataset.code || '').toString().toUpperCase();
            const item = (card.dataset.item || '').toString().toUpperCase();
            const pd = (card.dataset.pickupdate || '').toString();
            const selPd = (pickupDate?.value || '').toString();

            const matchSearch = !term || code.includes(term);
            const matchItem = !selItem || item === selItem;
            const matchDate = !selPd || pd === selPd;

            card.style.display = (matchSearch && matchItem && matchDate) ? '' : 'none';
        });

        computeSubmitEnabled();
        computeTopSummary();
    }

    form?.addEventListener('input', (e) => {
        if (isAllMode()) return;

        const t = e.target;
        if (!t.classList?.contains('num-input')) return;

        t.value = sanitizeNum(t.value);

        const card = t.closest('.fin-item');
        if (!card) return;
        if (isSupplyBlocked(card)) {
            t.value = '';
            computeSubmitEnabled();
            computeTopSummary();
            return;
        }

        const changed = (t.name || '').includes('[qty_reject]') ? 'rj' : 'ok';
        clampCard(card, changed);
        syncCheck(card);
        syncNotes(card);

        computeSubmitEnabled();
        computeTopSummary();
    });

    form?.addEventListener('change', (e) => {
        if (isAllMode()) return;

        const t = e.target;

        if (t === destination) {
            computeSubmitEnabled();
            return;
        }

        if (!t.classList?.contains('row-check')) return;

        const card = t.closest('.fin-item');
        if (!card) return;
        if (isSupplyBlocked(card)) {
            t.checked = false;
            return;
        }

        const { ok, rj } = getEls(card);

        // Uncheck: kosongkan nilai
        if (!t.checked) {
            if (ok) ok.value = '';
            if (rj) rj.value = '';
            syncNotes(card);
        }
        // Check: TIDAK auto-fill — hanya fokus input qty_ok supaya keyboard muncul
        else {
            const a = parseFloat(ok?.value || '0') || 0;
            const b = parseFloat(rj?.value || '0') || 0;
            if ((a + b) <= 0 && ok && !ok.disabled) {
                ok.focus();
                setTimeout(() => { try { ok.select(); } catch (_) {} }, 50);
            }
        }

        computeSubmitEnabled();
        computeTopSummary();
    });

    // Tap card (area di luar input/button) → fokus langsung ke input qty_ok
    listByOp?.addEventListener('click', (e) => {
        if (isAllMode()) return;
        if (e.target.tagName === 'INPUT' || e.target.tagName === 'BUTTON' || e.target.closest('button') || e.target.closest('a')) return;

        const card = e.target.closest('.fin-item');
        if (!card || isSupplyBlocked(card)) return;

        const { ok } = getEls(card);
        if (ok && !ok.disabled) {
            ok.focus();
            setTimeout(() => { try { ok.select(); } catch (_) {} }, 50);
        }
    });

    q?.addEventListener('input', () => {
        const up = (q.value || '').toString().toUpperCase();
        if (q.value !== up) q.value = up;
        applyFilter();
    });

    itemFilter?.addEventListener('change', applyFilter);

    form?.addEventListener('focusin', (e) => {
        const t = e.target;
        if (t?.classList?.contains('select-all-on-focus')) {
            setTimeout(() => { try { t.select(); } catch (_) {} }, 0);
        }
        if (window.innerWidth < 768) body.classList.add('keyboard-open');
    });
    form?.addEventListener('focusout', () => body.classList.remove('keyboard-open'));

    btnCheckVisible?.addEventListener('click', () => {
        if (isAllMode() || !listByOp) return;
        let firstOk = null;
        $$('.fin-item', listByOp).forEach(card => {
            if (card.style.display === 'none') return;
            if (isSupplyBlocked(card)) return;
            const { cb, ok } = getEls(card);
            if (cb) cb.checked = true;
            syncCheck(card);
            if (!firstOk && ok && !ok.disabled) firstOk = ok;
        });
        computeSubmitEnabled();
        computeTopSummary();
        applyFilter();
        // Fokus ke input pertama supaya keyboard muncul
        if (firstOk) {
            firstOk.focus();
            setTimeout(() => { try { firstOk.select(); } catch (_) {} }, 50);
        }
    });

    btnUncheckAll?.addEventListener('click', () => {
        if (isAllMode() || !listByOp) return;
        $$('.fin-item', listByOp).forEach(card => {
            const { cb, ok, rj } = getEls(card);
            if (cb) cb.checked = false;
            if (ok) ok.value = '';
            if (rj) rj.value = '';
            syncNotes(card);
        });
        computeSubmitEnabled();
        computeTopSummary();
        applyFilter();
    });

    // ===== Modal =====
    let bsModal = null;
    const hasBootstrap = (typeof window.bootstrap !== 'undefined' && typeof window.bootstrap.Modal !== 'undefined');

    if (modalEl && hasBootstrap) {
        bsModal = window.bootstrap.Modal.getOrCreateInstance(modalEl, { backdrop: true, focus: true });

        modalEl.addEventListener('show.bs.modal', () => fabWrap?.classList.add('is-hidden'));
        modalEl.addEventListener('hidden.bs.modal', () => fabWrap?.classList.remove('is-hidden'));
    } else {
        if (fallbackNote) fallbackNote.classList.remove('d-none');
    }

    function esc(s) {
        return String(s ?? '')
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }

    function rebuildModalItems() {
        if (!mItems || !listByOp) return;
        mItems.innerHTML = '';

        let rows = 0, okSum = 0, rjSum = 0;
        const picked = [];

        $$('.fin-item', listByOp).forEach(card => {
            const { ok, rj } = getEls(card);
            const a = parseFloat(ok?.value || '0') || 0;
            const b = parseFloat(rj?.value || '0') || 0;
            if ((a + b) <= 0) return;

            rows++;
            okSum += a;
            rjSum += b;

            picked.push({ code: (card.dataset.code || '').toString(), ok: a, rj: b });
        });

        picked.sort((x, y) => (x.code || '').localeCompare(y.code || ''));

        if (mRows) mRows.textContent = rows.toLocaleString('id-ID');
        if (mOk) mOk.textContent = okSum.toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        if (mRj) mRj.textContent = rjSum.toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        if (mItemsCount) mItemsCount.textContent = picked.length.toLocaleString('id-ID');

        if (picked.length === 0) {
            if (mEmpty) { mItems.appendChild(mEmpty); mEmpty.style.display = ''; }
            return;
        }
        if (mEmpty) mEmpty.style.display = 'none';

        picked.forEach((it, i) => {
            const row = document.createElement('div');
            row.className = 'px-3 py-2';
            row.style.borderBottom = '1px solid rgba(148,163,184,.12)';
            row.innerHTML = `
                <div class="d-grid" style="grid-template-columns: 44px 1fr 120px 120px; gap:.5rem; align-items:center;">
                    <div class="text-muted" style="font-weight:900;">${i+1}</div>
                    <div style="font-weight:900;" class="mono">${esc(it.code)}</div>
                    <div class="text-end mono" style="font-weight:900;">${it.ok.toLocaleString('id-ID',{minimumFractionDigits:2,maximumFractionDigits:2})}</div>
                    <div class="text-end mono" style="font-weight:900; color: var(--rj);">${it.rj.toLocaleString('id-ID',{minimumFractionDigits:2,maximumFractionDigits:2})}</div>
                </div>
            `;
            mItems.appendChild(row);
        });
    }

    btnOpenModal?.addEventListener('click', (e) => {
        if (isAllMode()) return;
        if (btnOpenModal.disabled) return;

        e.preventDefault();
        e.stopPropagation();

        const opt = operator?.options?.[operator.selectedIndex];
        if (mOp) mOp.textContent = (operator?.value ? (opt ? opt.text : '-') : 'SEMUA');

        const destOpt = destination?.options?.[destination.selectedIndex];
        if (mDest) mDest.textContent = destination?.dataset?.label || (destOpt ? destOpt.text : '-');

        rebuildModalItems();

        if (!bsModal) { form.submit(); return; }

        try { document.activeElement?.blur?.(); } catch (_) {}
        requestAnimationFrame(() => { bsModal.show(); });
    });

    btnConfirm?.addEventListener('click', () => {
        try { bsModal?.hide(); } catch (_) {}
        form.submit();
    });

    // init
    setModeUI();

    if (listByOp) {
        $$('.fin-item', listByOp).forEach(card => {
            syncCheck(card);
            syncNotes(card);
        });
    }

    applyFilter();
    computeSubmitEnabled();
    computeTopSummary();

    if (q) {
        setTimeout(() => {
            try {
                q.scrollIntoView({
                    behavior: 'smooth',
                    block: 'center',
                    inline: 'nearest'
                });
            } catch (_) {}

            setTimeout(() => {
                try {
                    q.focus({ preventScroll: true });
                    q.select();
                } catch (_) {
                    q.focus();
                }
            }, 160);
        }, 80);
    }

    // ── DEVELOPER DRY RUN ─────────────────────────────────────────────
    const dryRunChk   = document.getElementById('dev-dry-run-chk');
    const dryRunInput = document.getElementById('dev-dry-run-input');
    const dryRunLabel = document.getElementById('dev-dry-run-label');
    const submitBtn   = document.querySelector('#sewing-return-form [type="submit"]');
    const submitOrig  = submitBtn?.textContent?.trim() ?? 'Simpan';

    if (dryRunChk && dryRunInput) {
        const applyDryRun = () => {
            const on = dryRunChk.checked;
            dryRunInput.value = on ? '1' : '0';
            if (dryRunLabel) {
                dryRunLabel.style.background    = on ? '#052e16' : '#0f2a1a';
                dryRunLabel.style.borderColor   = on ? '#22c55e' : '#166534';
                dryRunLabel.style.boxShadow     = on ? '0 0 0 3px rgba(34,197,94,.15)' : 'none';
            }
            if (submitBtn) {
                submitBtn.textContent    = on ? '🧪 Dry Run' : submitOrig;
                submitBtn.style.cssText += on
                    ? '; background:#166534 !important; border-color:#22c55e !important;'
                    : '; background:; border-color:;';
            }
        };
        dryRunChk.addEventListener('change', applyDryRun);
        applyDryRun(); // apply state awal (default: checked)
    }
    // ─────────────────────────────────────────────────────────────────

    // ══ MODAL KELENGKAPAN JAHIT ══
    const supplyLineData = @json($supplyDataByLineId ?? []);
    const supplyModalEl   = document.getElementById('supplyModal');
    const supplyModalBody = document.getElementById('supplyModalBody');
    const supplyModalTitle = document.getElementById('supplyModalTitle');
    const supplyModalSave = document.getElementById('supplyModalSave');
    let supplyBsModal = null;
    let supplyUpdateUrl = '';

    if (supplyModalEl && typeof bootstrap !== 'undefined') {
        supplyBsModal = bootstrap.Modal.getOrCreateInstance(supplyModalEl);
    }

    function renderSupplyModal(lineId, updateUrl) {
        const data = supplyLineData[lineId];
        if (!data) { supplyModalBody.innerHTML = '<div class="text-muted text-center py-3">Data tidak tersedia.</div>'; return; }

        supplyUpdateUrl = updateUrl;
        supplyModalTitle.textContent = 'Kelengkapan Jahit';

        const remainingQty = parseFloat(data.remaining_qty || '0') || 0;
        const summary = `
            <div style="display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:.45rem; margin-bottom:.75rem;">
                <div style="border:1px solid rgba(148,163,184,.18); border-radius:12px; padding:.45rem .5rem; background:rgba(148,163,184,.06); min-width:0;">
                    <div style="font-size:.58rem; color:var(--muted); font-weight:900; text-transform:uppercase; letter-spacing:.04em;">Tanggal</div>
                    <div style="font-size:.78rem; font-weight:950; margin-top:.04rem; white-space:nowrap;">${escHtml(data.pickup_date || '-')}</div>
                </div>
                <div style="border:1px solid rgba(148,163,184,.18); border-radius:12px; padding:.45rem .5rem; background:rgba(148,163,184,.06); min-width:0;">
                    <div style="font-size:.58rem; color:var(--muted); font-weight:900; text-transform:uppercase; letter-spacing:.04em;">Kode Item</div>
                    <div style="font-size:.78rem; font-weight:950; margin-top:.04rem; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">${escHtml(data.item_code || '-')}</div>
                </div>
                <div style="border:1px solid rgba(148,163,184,.18); border-radius:12px; padding:.45rem .5rem; background:rgba(148,163,184,.06); min-width:0;">
                    <div style="font-size:.58rem; color:var(--muted); font-weight:900; text-transform:uppercase; letter-spacing:.04em;">Qty</div>
                    <div style="font-size:.78rem; font-weight:950; margin-top:.04rem; white-space:nowrap;">${remainingQty.toLocaleString('id-ID', { maximumFractionDigits: 2 })} pcs</div>
                </div>
            </div>`;

        const rows = (data.lines || []).map(sl => {
            const required = parseFloat(sl.required_qty || '0') || 0;
            const issued = parseFloat(sl.issued_qty || '0') || 0;
            const requiredPcs = parseFloat(sl.required_pcs || '0') || 0;
            const issuedPcs = parseFloat(sl.issued_pcs || '0') || 0;
            const qtyPerPcs = parseFloat(sl.qty_per_pcs || '0') || 0;
            const complete = required > 0 && issued >= required;
            const shortPcs = Math.max(requiredPcs - issuedPcs, 0);
            const shortQty = shortPcs * qtyPerPcs;
            const uom = sl.uom ? ` ${escHtml(sl.uom)}` : '';
            const helperText = complete
                ? `${issuedPcs.toLocaleString('id-ID', { maximumFractionDigits: 2 })}/${requiredPcs.toLocaleString('id-ID', { maximumFractionDigits: 2 })} pcs`
                : `Total kurang ${shortQty.toLocaleString('id-ID', { maximumFractionDigits: 4 })}${uom}`;

            return `
            <div class="sc-row ${complete ? 'is-ok' : (issuedPcs > 0 ? 'is-short' : '')}" data-required-pcs="${requiredPcs}">
                <input type="checkbox" class="sc-chk" ${complete ? 'checked disabled' : 'disabled'}>
                <div>
                    <div class="sc-label">${escHtml(sl.name)}</div>
                    <div class="sc-sub">${requiredPcs.toLocaleString('id-ID', { maximumFractionDigits: 2 })} pcs · ${helperText}</div>
                </div>
                <div>
                    <input type="number" step="1" min="0" max="${requiredPcs}" inputmode="numeric"
                           data-material-id="${sl.material_item_id}"
                           data-required="${required}"
                           data-qty-per-pcs="${qtyPerPcs}"
                           data-required-pcs="${requiredPcs}"
                           class="sc-input js-sm-inp"
                           value="${issuedPcs}"
                           ${complete ? 'disabled' : ''}
                           placeholder="${requiredPcs.toLocaleString('id-ID', { maximumFractionDigits: 0 })}">
                </div>
            </div>`;
        }).join('');

        supplyModalBody.innerHTML = summary + (rows || '<div class="text-muted small py-2">Tidak ada kelengkapan dari BOM untuk bundle ini.</div>');
    }

    supplyModalBody?.addEventListener('focusin', function (e) {
        const input = e.target.closest('.js-sm-inp');
        if (!input || input.disabled) return;
        setTimeout(() => {
            try { input.select(); } catch (_) {}
        }, 0);
    });

    supplyModalBody?.addEventListener('click', function (e) {
        if (e.target.closest('.js-sm-inp')) return;

        const row = e.target.closest('.sc-row');
        if (!row) return;

        const input = row.querySelector('.js-sm-inp');
        const checkbox = row.querySelector('.sc-chk');
        if (!input || input.disabled) return;

        const requiredPcs = parseFloat(input.dataset.requiredPcs || row.dataset.requiredPcs || '0') || 0;
        input.value = requiredPcs > 0 ? String(requiredPcs) : '';

        row.classList.add('is-ok');
        row.classList.remove('is-short');
        if (checkbox) checkbox.checked = true;

        input.focus();
        setTimeout(() => {
            try { input.select(); } catch (_) {}
        }, 0);
    });

    function escHtml(s) {
        return String(s ?? '').replace(/[&<>"']/g, m =>
            ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m]));
    }

    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.js-supply-modal-btn');
        if (!btn) return;
        const lineId  = btn.dataset.lineId;
        const updateUrl = btn.dataset.updateUrl;
        if (!lineId || !supplyBsModal) return;
        renderSupplyModal(lineId, updateUrl);
        supplyBsModal.show();
    });

    supplyModalSave?.addEventListener('click', async function () {
        if (!supplyUpdateUrl) return;
        const inputs = supplyModalBody.querySelectorAll('.js-sm-inp:not([disabled])');
        const body = new FormData();
        body.append('_token', document.querySelector('meta[name="csrf-token"]')?.content || '');
        body.append('_method', 'PATCH');
        inputs.forEach(inp => {
            const materialId = inp.dataset.materialId;
            if (!materialId) return;
            const issuedPcs = parseFloat(inp.value || '0') || 0;
            const qtyPerPcs = parseFloat(inp.dataset.qtyPerPcs || '0') || 0;
            body.append(`supplies[${materialId}][issued_pcs]`, issuedPcs);
            body.append(`supplies[${materialId}][qty_per_pcs]`, qtyPerPcs);
            body.append(`supplies[${materialId}][issued_qty]`, issuedPcs * qtyPerPcs);
            body.append(`supplies[${materialId}][required_qty]`, inp.dataset.required || '0');
        });

        supplyModalSave.disabled = true;
        supplyModalSave.textContent = 'Menyimpan…';

        try {
            const res = await fetch(supplyUpdateUrl, {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                body,
            });
            const json = await res.json();
            if (json.ok) {
                supplyBsModal?.hide();
                window.location.reload();
            } else {
                alert('Gagal menyimpan.');
            }
        } catch (err) {
            alert('Terjadi error: ' + err.message);
        } finally {
            supplyModalSave.disabled = false;
            supplyModalSave.textContent = 'Simpan';
        }
    });
});
</script>
@endpush



{{-- SweetAlert existing error handler --}}
<script id="gf-sweetalert-existing-error">
document.addEventListener('DOMContentLoaded', function () {
    const errorBox = document.querySelector('.alert-danger, .alert.alert-danger, [data-error-alert]');

    if (!errorBox) return;

    const rawText = (errorBox.innerText || errorBox.textContent || '').trim();

    if (!rawText) return;

    const cleanText = rawText
        .replace('Terjadi error:', '')
        .replace('Oops! Ada error input, cek form di bawah.', '')
        .trim();

    const showMessage = cleanText || rawText;

    function showSweetAlert() {
        if (typeof Swal === 'undefined') {
            alert(showMessage);
            return;
        }

        Swal.fire({
            icon: 'error',
            title: 'Data belum bisa disimpan',
            html: showMessage + '<br><br><strong>Hubungi Owner</strong>',
            confirmButtonText: 'Mengerti',
            confirmButtonColor: '#dc2626'
        });
    }

    if (typeof Swal === 'undefined') {
        const cdn = document.createElement('script');
        cdn.src = 'https://cdn.jsdelivr.net/npm/sweetalert2@11';
        cdn.onload = showSweetAlert;
        document.head.appendChild(cdn);
    } else {
        showSweetAlert();
    }

    errorBox.style.display = 'none';
});
</script>
