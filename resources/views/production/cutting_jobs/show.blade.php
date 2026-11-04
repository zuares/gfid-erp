{{-- resources/views/production/cutting_jobs/show.blade.php --}}
@extends('layouts.app')

@section('title', 'Produksi • Cutting Job ' . $job->code)

@push('head')
    <style>
        .page-wrap {
            max-width: 1100px;
            margin-inline: auto;
            padding: .75rem .75rem 4.5rem
        }

        .card {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 14px
        }

        .mono {
            font-variant-numeric: tabular-nums;
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, "Liberation Mono"
        }

        .help {
            color: var(--muted);
            font-size: .85rem
        }

        /* ── Header style (selaras sewing pickup) ── */
        .card-section { padding: 1rem 1.25rem }
        @media (min-width: 768px) { .card-section { padding: 1rem 1.5rem } }

        .hdr {
            display: flex;
            justify-content: space-between;
            gap: .75rem;
            flex-wrap: wrap;
            align-items: center
        }
        .hdr h1 {
            font-size: 1.02rem;
            font-weight: 900;
            margin: 0;
            letter-spacing: -.01em;
            font-variant-numeric: tabular-nums;
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas
        }
        .sub {
            font-size: .8rem;
            color: var(--muted);
            line-height: 1.35;
            margin-top: .15rem
        }
        .btn-header-link {
            border-radius: 999px;
            padding: .32rem .9rem;
            font-size: .78rem;
            font-weight: 600
        }
        /* Semua tombol di hdr-right seragam (pill style) */
        .hdr-right .btn {
            border-radius: 999px;
            font-size: .78rem;
            font-weight: 600;
            padding: .32rem .9rem;
        }

        @media (max-width: 767.98px) {
            .hdr { flex-direction: column; align-items: flex-start; gap: .5rem }
            .hdr h1 { font-size: 1rem }
            .hdr-right { flex-wrap: wrap; gap: .4rem }
            .hdr-right .btn { font-size: .75rem; padding: .3rem .6rem }
        }

        .summary-bar-mobile {
            font-size: .85rem;
            display: flex;
            flex-wrap: wrap;
            gap: .25rem .5rem
        }

        .summary-bar-mobile span::after {
            content: "•";
            margin: 0 .25rem;
            color: var(--muted)
        }

        .summary-bar-mobile span:last-child::after {
            content: "";
            margin: 0
        }

        /* ── Bundle table ── */
        .bq-table thead th {
            font-size: .78rem;
            text-transform: uppercase;
            letter-spacing: .04em;
            font-weight: 600
        }

        .row-has-reject { background: rgba(248,113,113,.04) }
        .row-has-reject .cell-reject { color: #dc2626; font-weight: 600 }
        .row-no-qc { background: rgba(148,163,184,.04) }
        .row-corrected { background: rgba(245,158,11,.07) }
        .row-corrected td { color: var(--muted) }
        .row-corrected .cell-corrected { color: #b45309; font-weight: 700 }

        .cell-ok { font-variant-numeric: tabular-nums }

        .small-muted { font-size: .8rem; color: var(--muted) }
        .badge-soft  { border-radius: 999px; padding: .2rem .6rem; font-size: .75rem }

        .accounting-trace-table { font-size: .78rem }
        .accounting-trace-table th { white-space: nowrap; color: var(--muted); font-weight: 700 }
        .accounting-trace-table td, .accounting-trace-table th { padding: .42rem .5rem }
        .accounting-trace-table .trace-time { white-space: nowrap; color: var(--muted) }
        .accounting-trace-note { display: block; margin-top: .12rem; color: var(--muted); font-size: .7rem; line-height: 1.35 }
        .accounting-trace-explainer { border: 1px solid rgba(37, 99, 235, .15); border-radius: 9px; background: rgba(239, 246, 255, .55); color: var(--muted); font-size: .74rem; line-height: 1.45; padding: .5rem .65rem; margin-bottom: .65rem }
        body[data-theme="dark"] .accounting-trace-explainer { background: rgba(30, 58, 138, .18); border-color: rgba(96, 165, 250, .25) }

        .correction-section {
            border-color: rgba(245, 158, 11, .25);
            background: linear-gradient(135deg, rgba(255, 251, 235, .72), rgba(248, 250, 252, .94));
        }

        .correction-section > summary {
            background: rgba(245, 158, 11, .06);
            border-bottom: 1px solid rgba(245, 158, 11, .16);
        }

        .correction-section .production-financial-title { color: #b45309; }
        .correction-section > summary::after { color: #b45309; }
        .correction-table { font-size: .76rem; }
        .correction-table th { color: var(--muted); font-size: .68rem; text-transform: uppercase; white-space: nowrap; }
        .correction-table td, .correction-table th { padding: .42rem .45rem; vertical-align: middle; }
        .correction-note { font-size: .72rem; color: var(--muted); line-height: 1.4; }

        body[data-theme="dark"] .correction-section {
            border-color: rgba(245, 158, 11, .35);
            background: linear-gradient(135deg, rgba(120, 53, 15, .25), rgba(15, 23, 42, .7));
        }

        /* Accordion financial sections: konsisten dengan form Cutting Job. */
        .production-financial-section {
            overflow: hidden;
            padding: 0;
        }

        .production-financial-section > summary {
            list-style: none;
            cursor: pointer;
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: .75rem;
            padding: 1rem 1.25rem;
        }

        .production-financial-section > summary::-webkit-details-marker {
            display: none;
        }

        .production-financial-section > summary::after {
            content: '⌄';
            margin-left: auto;
            color: #0f766e;
            font-size: 1rem;
            font-weight: 800;
            line-height: 1;
            transition: transform .15s ease;
        }

        .production-financial-section[open] > summary::after {
            transform: rotate(180deg);
        }

        .production-financial-section-body {
            padding: 0 1.25rem 1rem;
        }

        .production-financial-title {
            color: #0f766e;
            font-size: .86rem;
            font-weight: 800;
        }

        .production-financial-subtitle {
            color: var(--muted);
            font-size: .76rem;
            line-height: 1.35;
            margin-top: .15rem;
        }

        .production-financial-section.hpp-section {
            border-color: rgba(37, 99, 235, .2);
            background: linear-gradient(135deg, rgba(239, 246, 255, .62), rgba(248, 250, 252, .9));
        }

        .production-financial-section.hpp-section > summary {
            background: rgba(37, 99, 235, .04);
            border-bottom: 1px solid rgba(37, 99, 235, .12);
        }

        .production-financial-section.summary-section > summary {
            background: rgba(148, 163, 184, .045);
            border-bottom: 1px solid rgba(148, 163, 184, .16);
        }

        .summary-section .production-financial-title {
            color: var(--text);
        }

        .summary-output-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(145px, 1fr));
            gap: .85rem 1rem;
        }

        .summary-output-item .help {
            margin-bottom: .18rem;
        }

        .summary-output-item .mono {
            font-size: .9rem;
            font-weight: 700;
        }

        .production-financial-section.fabric-remnant-section > summary {
            background: rgba(245, 158, 11, .045);
            border-bottom: 1px solid rgba(245, 158, 11, .16);
        }

        .fabric-remnant-section .production-financial-title {
            color: #b45309;
        }

        .hpp-section > summary::after {
            color: #1d4ed8;
        }

        .hpp-section .production-financial-title {
            color: #1d4ed8;
        }

        .hpp-summary-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: .5rem;
            margin-bottom: .55rem;
        }

        .hpp-summary-item {
            min-width: 0;
            padding: .45rem .55rem;
            border: 1px solid rgba(148, 163, 184, .2);
            border-radius: 9px;
            background: rgba(255, 255, 255, .62);
        }

        .hpp-summary-item span {
            display: block;
            color: var(--muted);
            font-size: .64rem;
            font-weight: 700;
            text-transform: uppercase;
        }

        .hpp-summary-item strong {
            display: block;
            color: var(--text);
            font-size: .82rem;
            margin-top: .08rem;
        }

        .hpp-summary-item.is-total {
            border-color: rgba(59, 130, 246, .25);
            background: rgba(219, 234, 254, .68);
        }

        .hpp-summary-item.is-total strong {
            color: #1d4ed8;
        }

        .hpp-detail-table {
            font-size: .76rem;
        }

        .hpp-detail-table th {
            color: var(--muted);
            font-size: .68rem;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .hpp-detail-table td,
        .hpp-detail-table th {
            padding: .4rem .45rem;
            vertical-align: middle;
        }

        .hpp-unit-cost {
            color: var(--muted);
            font-size: .64rem;
            font-weight: 500;
            line-height: 1.25;
            white-space: nowrap;
        }

        .hpp-warning {
            color: #92400e;
            background: rgba(254, 243, 199, .72);
            border: 1px solid rgba(245, 158, 11, .35);
            border-radius: 8px;
            padding: .38rem .55rem;
            font-size: .72rem;
            line-height: 1.4;
        }

        body[data-theme="dark"] .production-financial-section.hpp-section {
            border-color: rgba(96, 165, 250, .35);
            background: linear-gradient(135deg, rgba(23, 37, 84, .48), rgba(15, 23, 42, .68));
        }

        body[data-theme="dark"] .production-financial-section.hpp-section > summary {
            background: rgba(37, 99, 235, .12);
            border-bottom-color: rgba(96, 165, 250, .25);
        }

        body[data-theme="dark"] .hpp-summary-item {
            background: rgba(15, 23, 42, .42);
            border-color: rgba(148, 163, 184, .2);
        }

        body[data-theme="dark"] .hpp-summary-item strong {
            color: #e2e8f0;
        }

        body[data-theme="dark"] .production-financial-section.fabric-remnant-section > summary {
            background: rgba(245, 158, 11, .1);
            border-bottom-color: rgba(245, 158, 11, .25);
        }

        @media (max-width: 767.98px) {
            .production-financial-section > summary {
                padding: .8rem .9rem;
            }

            .production-financial-section-body {
                padding: 0 .9rem .8rem;
            }

            .hpp-summary-grid {
                gap: .3rem;
            }

            .hpp-summary-item {
                padding: .35rem .4rem;
            }

            .hpp-summary-item strong {
                font-size: .7rem;
            }

            .hpp-detail-table {
                min-width: 610px;
            }

            .summary-output-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: .7rem .8rem;
            }
        }

        /* WIP pills (same as edit page) */
        .wip-inline { display: flex; flex-wrap: wrap; gap: .18rem; margin-top: .18rem }
        .wp { display: inline-flex; align-items: center; padding: .08rem .38rem;
              border-radius: 999px; font-size: .68rem; font-weight: 600;
              border: 1px solid rgba(148,163,184,.3); background: var(--card) }
        .wp-wip   { color: #2563eb }
        .wp-pick  { color: #d97706 }
        .wp-ready { color: #059669 }

        .bp-bar { position: relative; width: 100%; height: 4px; border-radius: 999px;
                  background: rgba(148,163,184,.3); overflow: hidden; margin-top: .25rem }
        .bp-picked { position: absolute; top: 0; bottom: 0; left: 0;
                     background: linear-gradient(to right,#facc15,#eab308); opacity: .85 }
        .bp-ready  { position: absolute; top: 0; bottom: 0; left: 0;
                     background: linear-gradient(to right,#22c55e,#16a34a); opacity: .9 }

        .bq-hide-ok .bq-row-ok { display: none }

        /* input di tabel */
        .bq-table .input-ok,
        .bq-table .input-reject {
            width: 68px !important;
            text-align: center;
            padding: .2rem .3rem;
            font-size: .8rem
        }

        .bq-table .input-reason {
            min-width: 120px;
            font-size: .8rem;
            padding: .2rem .35rem
        }

        @media (max-width: 767.98px) {
            .bq-table th.sticky-col-right, 
            .bq-table td.sticky-col-right {
                position: sticky;
                right: 0;
                background-color: var(--card, #fff);
                z-index: 2;
                box-shadow: -3px 0 6px rgba(0,0,0,0.03);
            }
            .row-no-qc td.sticky-col-right {
                background-color: #f8fafc;
            }
            .row-has-reject td.sticky-col-right {
                background-color: #fef2f2;
            }
        }

        @media (max-width: 767.98px) {
            .table-wrap { overflow-x: auto }
            .bq-table { font-size: .8rem; white-space: nowrap }
            .bq-table th, .bq-table td { padding: .35rem .4rem }
            .bq-table .input-ok,
            .bq-table .input-reject { width: 56px !important; font-size: .78rem }
        }

        /* stepper */
        .status-stepper {
            display: flex;
            align-items: center;
            gap: .75rem;
            font-size: .78rem;
            margin-top: .35rem
        }

        .status-step {
            display: flex;
            align-items: center;
            gap: .35rem
        }

        .status-dot {
            width: 18px;
            height: 18px;
            border-radius: 999px;
            border: 2px solid rgba(148, 163, 184, .7);
            background: transparent
        }

        .status-dot.active {
            background: rgba(34, 197, 94, .18);
            border-color: #22c55e;
            box-shadow: 0 0 0 1px rgba(34, 197, 94, .25)
        }

        .status-dot.current {
            background: rgba(37, 99, 235, .18);
            border-color: #2563eb;
            box-shadow: 0 0 0 1px rgba(37, 99, 235, .35)
        }

        .status-label {
            text-transform: uppercase;
            letter-spacing: .08em;
            font-size: .72rem;
            color: #6b7280
        }

        .status-label.current {
            color: #2563eb;
            font-weight: 700
        }

        .status-label.done {
            color: #16a34a;
            font-weight: 700
        }

        .status-separator {
            flex: 0 0 26px;
            height: 1px;
            background: linear-gradient(to right, rgba(148, 163, 184, .7), transparent)
        }

        @media (max-width:767.98px) {
            .status-stepper {
                flex-wrap: wrap;
                gap: .4rem .75rem
            }

            .status-separator {
                display: none
            }
        }

        /* actions */
        .cutting-actions .btn {
            border-radius: 999px
        }

        .cutting-actions .btn-primary {
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            border: none;
            box-shadow: 0 10px 24px rgba(37, 99, 235, .35)
        }

        .cutting-actions .btn-primary:hover {
            filter: brightness(1.03)
        }

        .cutting-actions .btn-outline-primary {
            border-color: rgba(37, 99, 235, .45);
            color: #2563eb
        }

        .cutting-actions .btn-outline-primary:hover {
            background: rgba(37, 99, 235, .06)
        }

        .cutting-actions .btn-outline-secondary {
            border-color: rgba(148, 163, 184, .7)
        }

        .cutting-actions .btn-outline-danger {
            border-color: rgba(220, 38, 38, .55);
            color: rgb(220, 38, 38)
        }

        .cutting-actions .btn-outline-danger:hover {
            background: rgba(220, 38, 38, .06)
        }

        .cutting-actions .btn-outline-warning {
            border-color: rgba(245, 158, 11, .55);
            color: #b45309
        }

        .cutting-actions .btn-outline-warning:hover {
            background: rgba(245, 158, 11, .08)
        }

        .cutting-confirm-card {
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            padding: .85rem 1rem;
            border-color: rgba(34, 197, 94, .28);
            background: linear-gradient(135deg, rgba(240, 253, 244, .9), rgba(248, 250, 252, .94));
        }

        .cutting-confirm-card-title {
            color: #166534;
            font-size: .84rem;
            font-weight: 800;
        }

        .cutting-confirm-card-help {
            color: var(--muted);
            font-size: .74rem;
            line-height: 1.35;
            margin-top: .12rem;
        }

        .cutting-confirm-card .btn-success {
            flex-shrink: 0;
            border-radius: 999px;
            padding-inline: 1rem;
            box-shadow: 0 7px 16px rgba(22, 163, 74, .22);
        }

        body[data-theme="dark"] .cutting-confirm-card {
            border-color: rgba(74, 222, 128, .32);
            background: linear-gradient(135deg, rgba(20, 83, 45, .35), rgba(15, 23, 42, .7));
        }

        body[data-theme="dark"] .cutting-confirm-card-title {
            color: #86efac;
        }

        /* mobile floating */
        @media (max-width:767.98px) {
            .cutting-mobile-actions {
                position: fixed;
                right: .9rem;
                bottom: calc(env(safe-area-inset-bottom, 0px) + 88px);
                z-index: 1040;
                pointer-events: none
            }

            .cutting-mobile-actions-inner {
                pointer-events: auto;
                background: color-mix(in srgb, var(--card) 92%, rgba(15, 23, 42, .08));
                border-radius: 18px;
                box-shadow: 0 12px 28px rgba(15, 23, 42, .35), 0 0 0 1px rgba(148, 163, 184, .45);
                padding: .38rem .45rem;
                display: flex;
                gap: .3rem;
                align-items: center;
                backdrop-filter: blur(10px);
                max-width: 78vw;
                flex-wrap: wrap
            }

            .cutting-mobile-actions-inner .btn {
                border-radius: 999px;
                white-space: nowrap;
                flex: 0 0 auto
            }

            .cutting-mobile-actions-inner .btn-primary {
                box-shadow: 0 6px 18px rgba(37, 99, 235, .45)
            }
        }

        @media (max-width: 767.98px) {
            .mobile-sticky-actions {
                position: fixed;
                bottom: 0;
                left: 0;
                right: 0;
                background: #ffffff;
                border-top: 1px solid rgba(148,163,184,0.2);
                padding: 0.75rem 1rem;
                z-index: 1020;
                box-shadow: 0 -4px 12px rgba(0,0,0,0.08);
                display: block;
            }
            body[data-theme="dark"] .mobile-sticky-actions {
                background: #1e293b;
                border-top-color: rgba(255,255,255,0.1);
            }
            /* Reduce mb-2 margin inside sticky bar */
            .mobile-sticky-actions .mb-2 {
                margin-bottom: 0.5rem !important;
            }
            .mobile-sticky-actions .mb-2:last-child {
                margin-bottom: 0 !important;
            }
            /* Add padding to page-wrap to prevent overlap */
            .page-wrap {
                padding-bottom: 6rem !important;
            }
        }
    </style>
@endpush

@section('content')
    @php
        // ========= FLAGS =========
        $user = auth()->user();
        $userRole = $user->role ?? null;
        $isOwner = $user?->isOwner() ?? false;
        $canSeeProductionAccounting = $isOwner;
        $status = $job->status ?? 'draft';

        $firstBundle = $job->bundles->first();
        $bundleOperator = $firstBundle?->operator;

        $hasQcCutting = isset($hasQcCutting)
            ? (bool) $hasQcCutting
            : $job->bundles->contains(fn($b) => $b->qcResults->where('stage', 'cutting')->isNotEmpty());

        $routes = [
            'edit_cut' => Route::has('production.cutting_jobs.edit'),
            'send_qc' => Route::has('production.cutting_jobs.send_to_qc'),
            'qc_edit' => Route::has('production.qc.cutting.edit'),
            'qc_quick_ok' => Route::has('production.qc.cutting.quick_ok'),
            'qc_cancel' => Route::has('production.qc.cutting.cancel'),
            'overprod' => Route::has('production.cutting_overproduction.create'),
        ];

        // ========= PERMISSION RULES =========
        // Edit Cutting boleh selama QC belum ada, walaupun sudah sent_to_qc.
        $canEditCutting =
            $isOwner &&
            !$hasQcCutting &&
            $routes['edit_cut'] &&
            in_array($status, ['draft', 'cut', 'cut_sent_to_qc', 'sent_to_qc'], true);

        // Flow ringkas: operator langsung input QC dari job cutting.
        // Route kirim QC tetap ada, tapi tidak lagi jadi langkah utama di UI.
        $canSendToQc = false;

        // Input QC boleh langsung dari draft/cut/sent_to_qc. Saat simpan, sistem tetap membuat WIP-CUT.
        $canInputQc =
            $routes['qc_edit'] &&
            ((!$hasQcCutting && in_array($status, ['draft', 'cut', 'cut_sent_to_qc', 'sent_to_qc'], true)) || $hasQcCutting); // view/edit QC

        $canQuickOkCutting =
            !$hasQcCutting &&
            $routes['qc_quick_ok'] &&
            in_array($status, ['draft', 'cut', 'cut_sent_to_qc', 'sent_to_qc'], true);

        $canCancelQc = $isOwner && $hasQcCutting && $routes['qc_cancel'];
        $canOverproduction = $isOwner && $hasQcCutting && $routes['overprod'];

        // Void: owner, belum QC, belum WIP posted, belum ada sewing pickup
        // Status 'cut_sent_to_qc'/'sent_to_qc' tetap bisa di-void selama QC belum diinput
        $hasWipPosted    = $job->bundles->contains(fn ($b) => ! empty($b->wip_posted_at));
        $hasSewingPickup = $job->bundles->contains(fn ($b) => ((float) ($b->sewing_picked_qty ?? 0)) > 0);
        $canVoid = $isOwner
            && in_array($status, ['draft', 'cut', 'cut_sent_to_qc', 'sent_to_qc'], true)
            && ! $hasQcCutting
            && ! $hasWipPosted
            && ! $hasSewingPickup
            && Route::has('production.cutting_jobs.void');

        // Kembalikan ke Bahan Baku: owner, QC sudah diposting, tapi belum ditarik jahit.
        // Ini kasus di mana Void biasa diblok (harus Batalkan QC dulu) — aksi ini
        // menggabungkan Batalkan QC + Void jadi satu klik.
        $canRevertToRaw = $isOwner
            && $status !== 'voided'
            && ($hasQcCutting || $hasWipPosted)
            && ! $hasSewingPickup
            && Route::has('production.cutting_jobs.revert_to_raw');

        // ========= QC operator (1 orang utk header) =========
        $qcOperator = null;
        if ($hasQcCutting) {
            foreach ($job->bundles as $b) {
                $qc = $b->qcResults->where('stage', 'cutting')->sortByDesc('qc_date')->first();
                if ($qc && $qc->operator) {
                    $qcOperator = $qc->operator;
                    break;
                }
            }
        }

        // ========= SUMMARY =========
        $totalBundles = $job->bundles->count();
        $totalQtyPcs = (float) $job->bundles->sum('qty_pcs');
        $totalUsedFabric = (float) $job->bundles->sum('qty_used_fabric');
        $fabricPerPcs = $totalQtyPcs > 0 ? $totalUsedFabric / $totalQtyPcs : 0.0;

        $qcTotalOk = 0.0;
        $qcTotalReject = 0.0;

        if ($hasQcCutting) {
            foreach ($job->bundles as $b) {
                $qc = $b->qcResults->where('stage', 'cutting')->sortByDesc('qc_date')->first();
                $wipB = (float) ($b->wip_qty ?? 0);
                $pickedB = (float) ($b->sewing_picked_qty ?? 0);
                $qcOkB = (float) ($b->qty_cutting_ok ?? ($qc?->qty_ok ?? ($b->qty_pcs ?? 0)));
                $qcRejectB = (float) ($qc?->qty_reject ?? 0);

                $effectiveOkB = max(0, min($qcOkB, $wipB + $pickedB));
                $qcTotalOk += $effectiveOkB;
                $qcTotalReject += $qcRejectB;
            }
        }

        // ========= STATUS LABEL =========
        $statusMapQc = [
            'qc_done' => ['label' => 'QC CUTTING SELESAI', 'class' => 'info'],
            'qc_ok' => ['label' => 'QC OK', 'class' => 'success'],
            'qc_mixed' => ['label' => 'QC MIXED', 'class' => 'warning'],
            'qc_reject' => ['label' => 'QC REJECT', 'class' => 'danger'],
            'qc_pending_owner_review' => ['label' => 'MENUNGGU KOREKSI OWNER', 'class' => 'warning'],
            'sent_to_qc' => ['label' => 'SEDANG DI QC', 'class' => 'info'],
            'cut_sent_to_qc' => ['label' => 'SEDANG DI QC', 'class' => 'info'],
        ];

        $statusMapNoQc = [
            'draft'         => ['label' => 'DRAFT',      'class' => 'secondary'],
            'cut'           => ['label' => 'CUTTING',    'class' => 'primary'],
            'cut_sent_to_qc'=> ['label' => 'KIRIM QC',  'class' => 'info'],
            'sent_to_qc'    => ['label' => 'KIRIM QC',  'class' => 'info'],
            'posted'        => ['label' => 'POSTED',     'class' => 'primary'],
            'voided'        => ['label' => 'VOID',       'class' => 'danger'],
        ];

        $cfg = $hasQcCutting
            ? $statusMapQc[$status] ?? ['label' => 'QC CUTTING', 'class' => 'info']
            : $statusMapNoQc[$status] ?? ['label' => strtoupper($status), 'class' => 'secondary'];

        $statusLabel = $cfg['label'];
        $statusClass = $cfg['class'];

        // ========= STEPPER =========
        $stepCurrent = 1;
        if (in_array($status, ['cut', 'cut_sent_to_qc', 'sent_to_qc'], true)) {
            $stepCurrent = 2;
        }
        if (($hasQcCutting && $status !== 'qc_pending_owner_review') || in_array($status, ['qc_done', 'qc_ok', 'qc_mixed', 'qc_reject'], true)) {
            $stepCurrent = 3;
        }

        $dotClass = fn($n) => $stepCurrent === $n ? 'current' : ($stepCurrent > $n ? 'active' : '');
        $lblClass = fn($n) => $stepCurrent === $n ? 'current' : ($stepCurrent > $n ? 'done' : '');

        $accountingEntries = $accountingJournalEntries ?? collect();
        $bundleCorrections = $bundleCorrections ?? collect();
        $correctionByBundleId = $bundleCorrections->keyBy(fn ($correction) => (int) $correction->cutting_job_bundle_id);
        $hasBundleCorrections = $bundleCorrections->isNotEmpty();
        $accountingIsInternalTrace = fn ($journal): bool => str_starts_with((string) $journal->source_type, 'cutting_wip');
        $accountingInternalEntries = $accountingEntries->filter($accountingIsInternalTrace)->values();
        $accountingFinancialEntries = $accountingEntries->reject($accountingIsInternalTrace)->values();
        $accountingActiveEntries = $accountingFinancialEntries->whereNull('voided_at');
        $accountingInternalActiveEntries = $accountingInternalEntries->whereNull('voided_at');
        $accountingPostedTotalDebit = (float) $accountingActiveEntries->sum(fn ($journal) => $journal->lines->sum('debit'));
        $accountingPostedTotalCredit = (float) $accountingActiveEntries->sum(fn ($journal) => $journal->lines->sum('credit'));
        $accountingTypeLabel = function (string $sourceType): string {
            return match (true) {
                str_starts_with($sourceType, 'cutting_job_wage') => 'Upah cutting',
                str_starts_with($sourceType, 'cutting_wip') => 'QC → WIP cutting',
                str_starts_with($sourceType, 'cutting_qc_sewing_supply') => 'Auto BOM kelengkapan jahit',
                str_starts_with($sourceType, 'cutting_job') => 'Bahan baku → WIP',
                $sourceType === 'cutting_bundle_material_return' => 'Koreksi → bahan baku',
                $sourceType === 'cutting_bundle_correction_wip_void' => 'Reversal WIP-CUT',
                $sourceType === 'cutting_bundle_correction_wage_void' => 'Reversal upah cutting',
                default => $sourceType,
            };
        };
    @endphp

    <div class="page-wrap">
        @if (session('material_shortage_count', 0) > 0)
            <div class="alert alert-warning d-flex justify-content-between align-items-center gap-2 flex-wrap">
                <div>
                    <strong>{{ session('material_shortage_count') }} material produksi masih kurang.</strong>
                    Kebutuhan dari hasil QC ini sudah otomatis masuk perhitungan Material Shortage.
                </div>
                <a href="{{ route('purchasing.material_shortages.index') }}" class="btn btn-sm btn-warning">
                    Lihat Kekurangan Material
                </a>
            </div>
        @endif

        {{-- ====== ACTIONS SNIPPET (dipakai desktop & mobile) ====== --}}
        @php
            $renderActions = function ($isMobile = false) use (
                $job,
                $status,
                $hasQcCutting,
                $canEditCutting,
                $canSendToQc,
                $canInputQc,
                $canQuickOkCutting,
                $canCancelQc,
                $canOverproduction,
                $canVoid,
                $canRevertToRaw,
            ) {
                $btn = $isMobile ? 'btn btn-sm w-100 mb-2' : 'btn btn-sm';
                $wrapClass = $isMobile ? 'd-block' : 'd-flex gap-2 align-items-center';

                echo '<div class="' . e($wrapClass) . '">';

                // Selesai Cutting (Quick OK)
                if ($canQuickOkCutting && $isMobile) {
                    echo '<button type="button" class="' . e($btn) . ' btn-success" data-bs-toggle="modal" data-bs-target="#modalSelesaiCutting"><i class="bi bi-check-circle me-1"></i>Simpan & Konfirmasi</button>';
                }

                // Input QC / Kirim QC
                if ($canInputQc) {
                    $label = $hasQcCutting ? 'Lihat / Edit QC' : 'Input QC';
                    echo '<a href="' . e(route('production.qc.cutting.edit', $job)) . '" class="' . e($btn) . ' btn-primary">' . $label . '</a>';
                } elseif ($canSendToQc) {
                    echo '<form action="' . e(route('production.cutting_jobs.send_to_qc', $job)) . '" method="post" class="d-inline m-0 p-0">';
                    echo csrf_field();
                    echo '<button type="submit" class="' . e($btn) . ' btn-primary">Kirim QC</button>';
                    echo '</form>';
                }

                // Edit Cutting
                if ($canEditCutting && !$hasQcCutting) {
                    echo '<a href="' . e(route('production.cutting_jobs.edit', $job)) . '" class="' . e($btn) . ' btn-outline-primary" onclick="return confirm(\'Edit Cutting?\\n\\nStatus: ' . e($status) . '\\nPastikan QC belum diinput.\')">Edit Cutting</a>';
                }

                // Aksi Lainnya (Dropdown)
                $hasSecondary = $canCancelQc || $canVoid || $canRevertToRaw;
                if ($hasSecondary) {
                    if (!$isMobile) {
                        echo '<div class="dropdown d-inline-block ms-1">';
                        echo '<button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown"><i class="bi bi-three-dots"></i></button>';
                        echo '<ul class="dropdown-menu dropdown-menu-end shadow-sm">';
                    } else {
                        echo '<div class="w-100 my-1"></div>'; // Break line in flex
                        echo '<div class="w-100 text-muted small fw-bold mb-1 mt-3">Aksi Lainnya:</div>';
                    }

                    if ($canCancelQc) {
                        $el = '<form action="' . e(route('production.qc.cutting.cancel', $job)) . '" method="post" class="m-0 p-0" onsubmit="return confirm(\'Batalkan QC Cutting?\\n\\nJika sudah dipakai sewing, aksi ini akan ditolak.\')">' . csrf_field() . '<button type="submit" class="dropdown-item text-danger">Batalkan QC</button></form>';
                        if ($isMobile) { $el = str_replace('dropdown-item', 'btn btn-sm btn-outline-danger w-100 mb-1', $el); }
                        echo $isMobile ? $el : '<li>' . $el . '</li>';
                    }

                    if ($canVoid) {
                        $el = '<form action="' . e(route('production.cutting_jobs.void', $job)) . '" method="post" class="m-0 p-0" onsubmit="return confirm(\'⚠️ VOID Cutting Job ' . e($job->code) . '?\\n\\nStok kain akan dikembalikan ke LOT semula.\\nAksi ini tidak bisa dibatalkan.\')">' . csrf_field() . '<button type="submit" class="dropdown-item text-danger">🗑 Void</button></form>';
                        if ($isMobile) { $el = str_replace('dropdown-item', 'btn btn-sm btn-outline-danger w-100 mb-1', $el); }
                        echo $isMobile ? $el : '<li>' . $el . '</li>';
                    }

                    if ($canRevertToRaw) {
                        $el = '<form action="' . e(route('production.cutting_jobs.revert_to_raw', $job)) . '" method="post" class="m-0 p-0" onsubmit="return confirm(\'↩️ Kembalikan ' . e($job->code) . ' ke Bahan Baku?\\n\\nQC dibatalkan, WIP-CUT dibalik, dan kain kembali ke RM/LOT. Jurnal ikut ter-void.\\nDitolak jika bundle sudah ditarik jahit.\')">' . csrf_field() . '<button type="submit" class="dropdown-item text-danger">↩️ Kembalikan ke Bahan Baku</button></form>';
                        if ($isMobile) { $el = str_replace('dropdown-item', 'btn btn-sm btn-outline-danger w-100 mb-1', $el); }
                        echo $isMobile ? $el : '<li>' . $el . '</li>';
                    }

                    if (!$isMobile) {
                        echo '</ul></div>';
                    }
                }

                echo '</div>';
            };
        @endphp

        {{-- ===========================
            HEADER (unified)
        ============================ --}}
        <div class="card mb-3">
            <div class="card-section">
                <div class="hdr">
                    <div>
                        <div class="d-flex align-items-center gap-2 w-100">
                            <h1>{{ $job->code }}</h1>
                            {{-- Badge tampil di sini pada mobile --}}
                            <span class="badge bg-{{ $statusClass }} d-md-none"
                                  style="font-size:.68rem;white-space:normal;line-height:1.3;max-width:100px;text-align:center;margin-left:auto">{{ $statusLabel }}</span>
                        </div>
                        <div class="sub" style="display: flex; align-items: center; gap: 0.35rem; flex-wrap: wrap;">
                            <span>{{ $job->lot?->item?->code ?? '-' }} • {{ $job->lot?->code ?? '-' }} • {{ $job->warehouse?->code ?? '-' }}</span>
                            @if ($job->date) 
                                <span class="badge" style="background: rgba(37, 99, 235, 0.1); color: #2563eb; border: 1px solid rgba(37, 99, 235, 0.2); font-size: 0.75rem; padding: 0.2rem 0.5rem; border-radius: 6px;">
                                    <i class="bi bi-calendar3 me-1"></i>{{ $job->date->format('d M Y') }}
                                </span>
                            @endif
                            @if ($hasBundleCorrections)
                                <span class="badge bg-warning-subtle text-warning-emphasis border" style="font-size:.68rem;">
                                    <i class="bi bi-arrow-counterclockwise me-1"></i>{{ $bundleCorrections->count() }} koreksi bahan
                                </span>
                            @endif
                        </div>
                    </div>
                    <div class="hdr-right d-flex align-items-center gap-2 flex-wrap">
                        {{-- Badge tampil di sini pada desktop --}}
                        <span class="badge bg-{{ $statusClass }} d-none d-md-inline-flex">{{ $statusLabel }}</span>
                        <a href="{{ route('production.cutting_jobs.index') }}"
                           class="btn btn-sm btn-outline-secondary btn-header-link">← Kembali</a>
                        {{-- Role 'operating' hanya boleh melihat (read-only): sembunyikan semua tombol aksi, sisakan Kembali. --}}
                        @if (strtolower((string) (auth()->user()->role ?? '')) !== 'operating')
                            {!! $renderActions(false) !!}
                        @endif
                    </div>
                </div>
                <div class="status-stepper mt-2">
                    <div class="status-step">
                        <div class="status-dot {{ $dotClass(1) }}"></div>
                        <div class="status-label {{ $lblClass(1) }}">Cutting</div>
                    </div>
                    <div class="status-separator"></div>
                    <div class="status-step">
                        <div class="status-dot {{ $dotClass(2) }}"></div>
                        <div class="status-label {{ $lblClass(2) }}">Input QC</div>
                    </div>
                    <div class="status-separator"></div>
                    <div class="status-step">
                        <div class="status-dot {{ $dotClass(3) }}"></div>
                        <div class="status-label {{ $lblClass(3) }}">QC Cutting</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ===========================
            INFO LOT & OPERATOR
        ============================ --}}
        <div class="card p-3 mb-3">
            {{-- Desktop: grid 4 kolom --}}
            <div class="d-none d-md-flex gap-4 flex-wrap">
                <div>
                    <div class="help mb-1">LOT</div>
                    <div class="fw-semibold mono">{{ $job->lot?->code ?? '-' }}</div>
                    <div class="small-muted">{{ $job->lot?->item?->code ?? '-' }}</div>
                </div>
                <div>
                    <div class="help mb-1">Gudang</div>
                    <div class="mono">{{ $job->warehouse?->code }} — {{ $job->warehouse?->name }}</div>
                </div>
                <div>
                    <div class="help mb-1">Operator Cutting</div>
                    <div class="mono">{{ $bundleOperator?->code ? $bundleOperator->code . ' — ' . $bundleOperator->name : '-' }}</div>
                </div>
                <div>
                    <div class="help mb-1">Operator QC</div>
                    <div class="mono">{{ $qcOperator?->code ? $qcOperator->code . ' — ' . $qcOperator->name : '-' }}</div>
                </div>
            </div>

            {{-- Mobile: satu baris compact --}}
            <div class="d-flex d-md-none flex-column gap-1" style="font-size:.82rem">
                <div class="d-flex gap-2 flex-wrap">
                    <span class="fw-semibold mono">{{ $job->lot?->code ?? '-' }}</span>
                    <span class="text-muted">{{ $job->lot?->item?->code ?? '-' }}</span>
                    <span class="text-muted">•</span>
                    <span class="mono">{{ $job->warehouse?->code ?? '-' }}</span>
                </div>
                <div class="d-flex gap-2 flex-wrap text-muted">
                    <span>Cut: {{ $bundleOperator?->code ?? '-' }}</span>
                    @if ($qcOperator)
                        <span>•</span>
                        <span>QC: {{ $qcOperator->code }}</span>
                    @endif
                    @if ($job->date)
                        <span>•</span>
                        <span style="color: #2563eb; font-weight: 700; background: rgba(37, 99, 235, 0.1); padding: 0.15rem 0.4rem; border-radius: 6px; border: 1px solid rgba(37, 99, 235, 0.2);">
                            <i class="bi bi-calendar3" style="margin-right: 3px;"></i>{{ $job->date?->format('d M Y') }}
                        </span>
                    @endif
                </div>
            </div>

            @if (!empty($job->notes))
                <div class="mt-2 text-muted small">{{ $job->notes }}</div>
            @endif
        </div>

        {{-- ===========================
            DETAIL BUNDLES
        ============================ --}}
        @php
            // Per-bundle QC: boleh jika status job mengizinkan (tanpa syarat $hasQcCutting global)
            $canBundleQcRoute = Route::has('production.qc.cutting.bundle_quick_ok')
                && in_array($status, ['draft','cut','cut_sent_to_qc','sent_to_qc','qc_done','qc_ok','qc_mixed'], true);
            $canAmbilJahit = Route::has('production.sewing.pickups.create');
            $hasAnyReject  = false;
        @endphp

        {{-- Hidden forms untuk per-bundle submit (di luar tabel agar valid HTML) --}}
        @foreach ($job->bundles as $row)
            @if (!$row->qcResults->where('stage','cutting')->count())
                <form id="bqf-{{ $row->id }}" method="POST"
                      action="{{ route('production.qc.cutting.bundle_quick_ok', [$job, $row]) }}"
                      style="display:none">
                    @csrf
                    <input type="hidden" name="qty_ok"       id="bqf-ok-{{ $row->id }}">
                    <input type="hidden" name="qty_reject"   id="bqf-rej-{{ $row->id }}">
                    <input type="hidden" name="reject_reason" id="bqf-rsn-{{ $row->id }}">
                </form>
            @endif
        @endforeach

        <div class="card p-3 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h2 class="h6 mb-0">Detail Bundles</h2>
                <button type="button" id="btnHideOk"
                        class="btn btn-outline-secondary btn-sm"
                        style="font-size:.72rem;padding:.2rem .55rem;display:none">
                    Sembunyikan ✓
                </button>
            </div>

            <div class="table-wrap">
                <table class="table table-sm align-middle mono bq-table" id="bqTable">
                    <thead>
                        <tr>
                            <th style="width:80px;">No</th>
                            <th>Item</th>
                            <th class="text-end d-none d-md-table-cell" style="width:80px;">Cut</th>
                            <th class="text-end" style="width:90px;">OK</th>
                            <th class="text-center" style="width:90px;">Reject</th>
                            <th class="d-none d-md-table-cell" style="width:160px;">Alasan</th>
                            <th class="d-none d-md-table-cell" style="width:160px;">WIP / Sewing</th>
                            <th style="width:90px;">Status</th>
                            <th class="text-end sticky-col-right" style="width:100px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse ($job->bundles as $i => $row)
                        @php
                            $qc          = $row->qcResults->where('stage','cutting')->sortByDesc('qc_date')->first();
                            $bundleHasQc = $qc !== null;
                            $bundleCorrection = $correctionByBundleId->get((int) $row->id);
                            $canInputThis = $canBundleQcRoute && !$bundleHasQc && !$bundleCorrection;

                            $qtyPcs  = (float) ($row->qty_pcs ?? 0);
                            $qtyOk   = $bundleHasQc
                                ? (float) ($row->qty_cutting_ok ?? $qc->qty_ok ?? $qtyPcs)
                                : $qtyPcs; // default input = semua OK
                            $qtyRej  = $bundleHasQc ? (float) ($qc->qty_reject ?? 0) : 0.0;
                            $rejectReason = $bundleHasQc ? ($qc->reject_reason ?? '') : '';

                            $wip    = (float) ($row->cut_wip_qty ?? 0);
                            $picked = (float) ($row->sewing_picked_qty ?? 0);
                            $effectiveOk = max(0, min($qtyOk, $wip + $picked));
                            $ready  = (float) ($row->qty_ready_for_sewing ?? max(0, min($effectiveOk, $wip) - $picked));
                            $basis  = max($effectiveOk, $wip, $picked, $ready);
                            $pickedPct = $basis > 0 ? min(100, ($picked / $basis) * 100) : 0;
                            $readyPct  = $basis > 0 ? min(100, ($ready  / $basis) * 100) : 0;

                            $isAllOk  = $bundleHasQc && $qtyRej == 0 && $qtyOk > 0;
                            $isReject = $bundleHasQc && $qtyRej > 0 && $qtyOk <= 0;
                            $isMixed  = $bundleHasQc && $qtyRej > 0 && $qtyOk > 0;

                            $rowClass = $bundleCorrection ? 'row-corrected'
                                : ($isReject || $isMixed ? 'row-has-reject'
                                : ($canInputThis ? 'row-no-qc' : ''));
                            $trHide   = $isAllOk ? 'bq-row-ok' : '';

                            if ($qtyRej > 0) $hasAnyReject = true;

                            $bundleStatus = $bundleCorrection ? 'corrected' : ($bundleHasQc
                                ? ($isAllOk ? 'qc_ok' : ($isReject ? 'qc_reject' : 'qc_mixed'))
                                : ($canInputThis ? 'pending' : '—'));
                            $statusBadge  = [
                                'qc_ok'     => 'bg-success',
                                'qc_mixed'  => 'bg-warning',
                                'qc_reject' => 'bg-danger',
                                'pending'   => 'bg-secondary',
                                'corrected' => 'bg-warning text-dark',
                            ][$bundleStatus] ?? 'bg-secondary';
                        @endphp
                        <tr class="{{ $rowClass }} {{ $trHide }}" data-bundle-id="{{ $row->id }}">
                            {{-- No --}}
                            <td>
                                <div class="fw-semibold">#{{ $row->bundle_no }}</div>
                                <div class="small-muted d-none d-md-block" style="font-size:.68rem">{{ $row->bundle_code }}</div>
                            </td>

                            {{-- Item --}}
                            <td>
                                <div class="fw-bold" style="font-size:.92rem;letter-spacing:.01em;">{{ $row->finishedItem?->code ?? '-' }}</div>
                                <div class="small-muted d-none d-md-block">{{ $row->finishedItem?->name ?? '' }}</div>
                                @if ($bundleCorrection)
                                    <div class="cell-corrected small" style="font-size:.68rem;">
                                        Dikoreksi → {{ $bundleCorrection->replacementItem?->code ?? $bundleCorrection->metadata['replacement_item_code'] ?? '-' }}
                                    </div>
                                @endif
                            </td>

                            {{-- Cut --}}
                            <td class="text-end d-none d-md-table-cell">{{ number_format($qtyPcs, 0, ',', '.') }}</td>

                            {{-- OK --}}
                            <td class="text-end">
                                @if ($canInputThis)
                                    <input type="number" step="1" min="0" max="{{ (int)$qtyPcs }}"
                                           class="form-control form-control-sm text-center input-ok"
                                           value="{{ (int)$qtyPcs }}"
                                           data-bundle="{{ (int)$qtyPcs }}"
                                           data-id="{{ $row->id }}"
                                           style="width:68px;display:inline-block">
                                @else
                                    <span class="cell-ok {{ $qtyOk > 0 ? 'text-success fw-semibold' : 'text-muted' }}">
                                        {{ $bundleHasQc ? number_format($qtyOk, 0, ',', '.') : '—' }}
                                    </span>
                                @endif
                            </td>

                            {{-- Reject --}}
                            <td class="text-center">
                                @if ($canInputThis)
                                    <input type="number" step="1" min="0" max="{{ (int)$qtyPcs }}"
                                           class="form-control form-control-sm text-center input-reject"
                                           value="0"
                                           data-bundle="{{ (int)$qtyPcs }}"
                                           data-id="{{ $row->id }}"
                                           style="width:68px;display:inline-block">
                                @else
                                    <span class="{{ $qtyRej > 0 ? 'cell-reject' : 'text-muted' }}">
                                        {{ $bundleHasQc ? number_format($qtyRej, 0, ',', '.') : '—' }}
                                    </span>
                                @endif
                            </td>

                            {{-- Alasan (desktop) --}}
                            <td class="d-none d-md-table-cell">
                                @if ($canInputThis)
                                    <input type="text" class="form-control form-control-sm input-reason"
                                           data-id="{{ $row->id }}"
                                           placeholder="mis: bolong, kotor">
                                @else
                                    <span class="small-muted">{{ $rejectReason ?: ($bundleHasQc ? '—' : '') }}</span>
                                @endif
                            </td>

                            {{-- WIP / Sewing (desktop) --}}
                            <td class="d-none d-md-table-cell">
                                @if ($wip > 0 || $picked > 0 || $ready > 0)
                                    <div class="wip-inline">
                                        <span class="wp wp-wip">WIP {{ number_format($wip, 0, ',', '.') }}</span>
                                        <span class="wp wp-pick">Pick {{ number_format($picked, 0, ',', '.') }}</span>
                                        <span class="wp wp-ready">Ready {{ number_format($ready, 0, ',', '.') }}</span>
                                    </div>
                                    @if ($basis > 0)
                                        <div class="bp-bar" style="max-width:140px;">
                                            <div class="bp-picked" style="width:{{ number_format($pickedPct,2,'.',''). '%' }}"></div>
                                            <div class="bp-ready"  style="width:{{ number_format($readyPct,2,'.',''). '%' }}"></div>
                                        </div>
                                    @endif
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>

                            {{-- Status --}}
                            <td class="text-center">
                                @php
                                    $statusIcon = match($bundleStatus) {
                                        'qc_ok'     => ['icon' => 'bi-check-circle-fill',        'color' => '#22c55e', 'title' => 'QC OK'],
                                        'qc_reject' => ['icon' => 'bi-x-circle-fill',            'color' => '#ef4444', 'title' => 'QC Reject'],
                                        'qc_mixed'  => ['icon' => 'bi-exclamation-triangle-fill', 'color' => '#f59e0b', 'title' => 'QC Mixed'],
                                        'pending'   => ['icon' => 'bi-hourglass-split',           'color' => '#94a3b8', 'title' => 'Pending'],
                                        'corrected' => ['icon' => 'bi-arrow-counterclockwise',     'color' => '#d97706', 'title' => 'Dikoreksi ke bahan baku'],
                                        default     => null,
                                    };
                                @endphp
                                @if ($statusIcon)
                                    <i class="bi {{ $statusIcon['icon'] }}"
                                       style="font-size:1.1rem; color:{{ $statusIcon['color'] }};"
                                       title="{{ $statusIcon['title'] }}"></i>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>

                            {{-- Aksi --}}
                            <td class="text-end sticky-col-right">
                                <div class="d-flex gap-1 justify-content-end align-items-center">
                                    @if ($canInputThis)
                                        <button type="button"
                                                class="btn btn-icon btn-save-bundle"
                                                data-id="{{ $row->id }}"
                                                data-pcs="{{ (int)$qtyPcs }}"
                                                data-code="{{ $row->bundle_code }}"
                                                title="Simpan QC"
                                                style="width:30px;height:30px;padding:0;border-radius:8px;border:1px solid #22c55e;color:#22c55e;background:transparent;display:inline-flex;align-items:center;justify-content:center;">
                                            <i class="bi bi-floppy" style="font-size:.95rem;"></i>
                                        </button>
                                    @endif
                                    @if ($canAmbilJahit && $ready > 0)
                                        <a href="{{ route('production.sewing.pickups.create') }}?sku={{ urlencode($row->finishedItem?->code ?? '') }}"
                                           class="btn btn-icon"
                                           title="Ambil Jahit"
                                           style="width:30px;height:30px;padding:0;border-radius:8px;border:1px solid #3b82f6;color:#3b82f6;background:transparent;display:inline-flex;align-items:center;justify-content:center;">
                                            <i class="bi bi-scissors" style="font-size:.95rem;"></i>
                                        </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted small py-3">
                                Belum ada data bundle.
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            @if ($hasAnyReject)
                <div class="text-warning small mt-2">
                    ⚠️ Terdapat bundle dengan reject. Pastikan alasan reject sudah diisi (kolom Alasan di desktop).
                </div>
            @endif
        </div>

        @if ($canQuickOkCutting)
            {{-- CTA utama ditempatkan setelah tabel agar mengikuti alur input QC. --}}
            <div class="card cutting-confirm-card mb-4 d-none d-md-flex">
                <div>
                    <div class="cutting-confirm-card-title">
                        <i class="bi bi-check-circle me-1" aria-hidden="true"></i>
                        Semua data bundle sudah siap?
                    </div>
                    <div class="cutting-confirm-card-help">
                        Simpan hasil cutting, catat sisa kain per LOT, lalu masukkan seluruh bundle ke WIP-CUT.
                    </div>
                </div>
                <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalSelesaiCutting">
                    <i class="bi bi-check-circle me-1" aria-hidden="true"></i>
                    Simpan &amp; Konfirmasi
                </button>
            </div>

            @if (($autoBomLotPreview['ready'] ?? null) === false)
                @php $autoBomDetail = $autoBomLotPreview['detail'] ?? []; @endphp
                <div class="alert alert-warning mb-4" role="alert">
                    <div class="fw-semibold mb-1"><i class="bi bi-exclamation-triangle me-1" aria-hidden="true"></i>Auto BOM perlu koreksi</div>
                    <ul class="mb-2 ps-3 small">
                        <li><strong>Item:</strong> {{ $autoBomDetail['item'] ?? '-' }}</li>
                        <li><strong>Kebutuhan:</strong> {{ $autoBomDetail['required'] ?? '-' }}</li>
                        <li><strong>Tersedia (lot):</strong> {{ $autoBomDetail['available'] ?? '-' }}</li>
                        <li><strong>Kekurangan:</strong> {{ $autoBomDetail['shortage'] ?? '-' }}</li>
                    </ul>
                    <div class="small">Lengkapi stok lot atau sesuaikan Qty OK/BOM di Input QC.</div>
                </div>
            @elseif (($autoBomLotPreview['ready'] ?? null) === true)
                <div class="alert alert-success mb-4 small" role="status">
                    <i class="bi bi-check-circle me-1" aria-hidden="true"></i>Auto BOM tervalidasi: saldo lot mencukupi untuk seluruh Qty OK.
                </div>
            @endif
        @endif

        @if ($canSeeProductionAccounting && $hasBundleCorrections)
        {{-- ===========================
            KOREKSI BUNDLE
        ============================ --}}
        <details class="card mb-4 production-financial-section correction-section">
            <summary>
                <div>
                    <div class="production-financial-title">
                        <i class="bi bi-arrow-counterclockwise me-1" aria-hidden="true"></i> Koreksi Bundle
                    </div>
                    <div class="production-financial-subtitle">
                        Bahan yang dikembalikan ke bahan baku, item pengganti, dan reversal biaya produksi.
                    </div>
                </div>
                <span class="badge bg-warning-subtle text-warning-emphasis border">
                    {{ $bundleCorrections->count() }} dikoreksi
                </span>
            </summary>
            <div class="production-financial-section-body">
                <div class="table-wrap">
                    <table class="table table-sm align-middle correction-table mb-0">
                        <thead>
                            <tr>
                                <th>Bundle</th>
                                <th>Item lama → pengganti</th>
                                <th class="text-end">Bahan kembali</th>
                                <th>LOT / gudang</th>
                                <th>Audit biaya</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($bundleCorrections as $correction)
                                @php
                                    $correctionMeta = is_array($correction->metadata) ? $correction->metadata : [];
                                    $oldItemCode = $correction->bundle?->finishedItem?->code
                                        ?? ($correctionMeta['original_bundle_item_code'] ?? '-');
                                    $newItemCode = $correction->replacementItem?->code
                                        ?? ($correctionMeta['replacement_item_code'] ?? '-');
                                    $wageReversalAmount = (float) ($correctionMeta['wage_reversal_amount'] ?? 0);
                                    $wageReversalStatus = (string) ($correctionMeta['wage_reversal_status'] ?? '');
                                    $wipReversalStatus = (string) ($correctionMeta['wip_reversal_status'] ?? '');
                                @endphp
                                <tr>
                                    <td>
                                        <div class="fw-semibold mono">{{ $correction->bundle?->bundle_code ?? ('Bundle #' . $correction->cutting_job_bundle_id) }}</div>
                                        <div class="correction-note">Koreksi #{{ $correction->id }}</div>
                                    </td>
                                    <td>
                                        <div class="mono">{{ $oldItemCode }} <span class="text-muted">→</span> <strong>{{ $newItemCode }}</strong></div>
                                        @if (!empty($correction->reason))
                                            <div class="correction-note">{{ $correction->reason }}</div>
                                        @endif
                                    </td>
                                    <td class="text-end mono">
                                        <div class="fw-semibold">{{ number_format((float) $correction->return_fabric_qty, 4, ',', '.') }} Kg</div>
                                        <div class="correction-note">Rp {{ number_format((float) $correction->return_cost, 2, ',', '.') }}</div>
                                    </td>
                                    <td>
                                        <div class="mono">{{ $correction->bundle?->lot?->code ?? '-' }}</div>
                                        <div class="correction-note">Kembali ke gudang bahan baku</div>
                                    </td>
                                    <td>
                                        <div class="correction-note">
                                            @if ($wipReversalStatus === 'posted' || !empty($correctionMeta['wip_reversal_journal_id']))
                                                <span class="text-success">WIP-CUT dibalik</span>
                                            @else
                                                <span class="text-success">Saldo WIP-CUT 0</span>
                                            @endif
                                        </div>
                                        @if ($wageReversalStatus === 'posted' && $wageReversalAmount > 0)
                                            <div class="correction-note text-success">Upah dibalik Rp {{ number_format($wageReversalAmount, 2, ',', '.') }}</div>
                                        @elseif ($wageReversalStatus === 'not_required')
                                            <div class="correction-note">Upah tidak perlu dibalik</div>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge bg-warning text-dark">{{ strtoupper((string) $correction->status) }}</span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="correction-note mt-2">
                    Koreksi ini hanya menambah jejak reversal di akuntansi baru; jurnal legacy tetap dipertahankan sebagai audit trail.
                </div>
            </div>
        </details>
        @endif


        {{-- ===========================
            JEJAK AKUNTANSI BARU
        ============================ --}}
        @if ($canSeeProductionAccounting)
        <details class="card mb-4 production-financial-section accounting-section">
            <summary>
                <div>
                    <div class="production-financial-title">
                        <i class="bi bi-journal-check me-1" aria-hidden="true"></i> Akuntansi Baru
                    </div>
                    <div class="production-financial-subtitle">Jejak jurnal Cutting Job ini, termasuk repost dan reversal.</div>
                </div>
                @if ($accountingActiveEntries->isNotEmpty() || $accountingInternalActiveEntries->isNotEmpty())
                    <span class="d-flex gap-1 flex-wrap justify-content-end">
                        @if ($accountingActiveEntries->isNotEmpty())
                            <span class="badge bg-success">{{ $accountingActiveEntries->count() }} JURNAL FINANSIAL</span>
                        @endif
                        @if ($accountingInternalActiveEntries->isNotEmpty())
                            <span class="badge bg-secondary">{{ $accountingInternalActiveEntries->count() }} JEJAK INTERNAL</span>
                        @endif
                    </span>
                @else
                    <span class="badge bg-warning text-dark">BELUM POSTING</span>
                @endif
            </summary>
            <div class="production-financial-section-body">

            @if ($accountingActiveEntries->isNotEmpty())
                <div class="small-muted mb-1">Total jurnal finansial aktif</div>
                <div class="d-flex gap-3 flex-wrap small-muted mb-2 mono">
                    <span>Debit Rp {{ number_format($accountingPostedTotalDebit, 2, ',', '.') }}</span>
                    <span>Kredit Rp {{ number_format($accountingPostedTotalCredit, 2, ',', '.') }}</span>
                    <span class="{{ abs($accountingPostedTotalDebit - $accountingPostedTotalCredit) <= 0.01 ? 'text-success' : 'text-danger' }}">
                        {{ abs($accountingPostedTotalDebit - $accountingPostedTotalCredit) <= 0.01 ? 'Balance' : 'Perlu review' }}
                    </span>
                </div>
                <div class="accounting-trace-explainer">
                    <strong>Total di atas hanya jurnal finansial.</strong>
                    Jejak <em>QC → WIP cutting</em> dipisahkan karena debit dan kreditnya memakai akun WIP yang sama,
                    sehingga efek saldonya netto Rp0. Auto BOM kelengkapan jahit tetap dicatat sebagai jurnal finansial terpisah.
                </div>
            @endif

            @if ($accountingFinancialEntries->isEmpty() && $accountingInternalEntries->isEmpty())
                <div class="small-muted">Belum ada jurnal akuntansi baru untuk dokumen ini.</div>
            @elseif ($accountingFinancialEntries->isNotEmpty())
                <div class="table-wrap">
                    <table class="table table-sm align-middle accounting-trace-table mb-0">
                        <thead>
                            <tr>
                                <th>Waktu</th>
                                <th>Transaksi</th>
                                <th>Referensi</th>
                                <th class="text-end">Debit</th>
                                <th class="text-end">Kredit</th>
                                <th>Status</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($accountingFinancialEntries as $journal)
                                @php
                                    $journalDebit = (float) $journal->lines->sum('debit');
                                    $journalCredit = (float) $journal->lines->sum('credit');
                                    $isVoided = !empty($journal->voided_at);
                                @endphp
                                <tr class="{{ $isVoided ? 'text-muted' : '' }}">
                                    <td class="trace-time mono">{{ optional($journal->posted_at)->format('d/m/Y H:i') ?? '-' }}</td>
                                    <td>
                                        <div>{{ $accountingTypeLabel((string) $journal->source_type) }}</div>
                                        @if (str_starts_with((string) $journal->source_type, 'cutting_qc_sewing_supply'))
                                            <small class="accounting-trace-note">Konsumsi BOM · masuk ke nilai WIP</small>
                                        @endif
                                    </td>
                                    <td class="mono">{{ $journal->reference ?? '-' }}</td>
                                    <td class="text-end mono">Rp {{ number_format($journalDebit, 2, ',', '.') }}</td>
                                    <td class="text-end mono">Rp {{ number_format($journalCredit, 2, ',', '.') }}</td>
                                    <td>
                                        <span class="badge {{ $isVoided ? 'bg-secondary' : 'bg-success' }}">
                                            {{ $isVoided ? 'VOID' : 'POSTED' }}
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        @if (Route::has('standalone-accounting.journals.show'))
                                            <a href="{{ route('standalone-accounting.journals.show', $journal) }}" class="btn btn-sm btn-outline-secondary">Detail</a>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            @if ($accountingInternalEntries->isNotEmpty())
                <div class="mt-3 pt-3 border-top">
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <strong class="small">Jejak produksi internal</strong>
                        <span class="badge bg-secondary-subtle text-secondary border">Tidak memengaruhi saldo</span>
                    </div>
                    <div class="small-muted mb-2">QC → WIP Cutting hanya menjadi jejak perpindahan proses karena debit dan kredit memakai akun WIP yang sama.</div>
                    <div class="table-wrap">
                        <table class="table table-sm align-middle accounting-trace-table mb-0">
                            <thead>
                                <tr><th>Waktu</th><th>Transaksi</th><th>Referensi</th><th class="text-end">Debit</th><th class="text-end">Kredit</th><th>Status</th><th></th></tr>
                            </thead>
                            <tbody>
                                @foreach ($accountingInternalEntries as $journal)
                                    @php
                                        $journalDebit = (float) $journal->lines->sum('debit');
                                        $journalCredit = (float) $journal->lines->sum('credit');
                                        $isVoided = !empty($journal->voided_at);
                                    @endphp
                                    <tr class="{{ $isVoided ? 'text-muted' : '' }}">
                                        <td class="trace-time mono">{{ optional($journal->posted_at)->format('d/m/Y H:i') ?? '-' }}</td>
                                        <td><div>QC → WIP cutting</div><small class="accounting-trace-note">Transfer internal · efek saldo netto Rp0</small></td>
                                        <td class="mono">{{ $journal->reference ?? '-' }}</td>
                                        <td class="text-end mono">Rp {{ number_format($journalDebit, 2, ',', '.') }}</td>
                                        <td class="text-end mono">Rp {{ number_format($journalCredit, 2, ',', '.') }}</td>
                                        <td><span class="badge {{ $isVoided ? 'bg-secondary' : 'bg-success' }}">{{ $isVoided ? 'VOID' : 'POSTED' }}</span></td>
                                        <td class="text-end">
                                            @if (Route::has('standalone-accounting.journals.show'))
                                                <a href="{{ route('standalone-accounting.journals.show', $journal) }}" class="btn btn-sm btn-outline-secondary">Detail</a>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
            </div>
        </details>
        @endif

        @if ($canSeeProductionAccounting)
        {{-- ===========================
            ESTIMASI HPP CUTTING
        ============================ --}}
        <details class="card mb-4 production-financial-section hpp-section">
            <summary>
                <div>
                    <div class="production-financial-title">
                        <i class="bi bi-calculator me-1" aria-hidden="true"></i> Estimasi HPP Cutting
                    </div>
                    <div class="production-financial-subtitle">
                        Estimasi bahan + upah yang masuk ke WIP Cutting, bukan HPP penjualan.
                    </div>
                </div>
                <span class="badge bg-primary-subtle text-primary border">Read-only</span>
            </summary>
            <div class="production-financial-section-body">
                <div class="hpp-summary-grid">
                    <div class="hpp-summary-item">
                        <span>Bahan</span>
                        <strong class="mono">Rp {{ number_format((float) ($cuttingHppTotals['material'] ?? 0), 2, ',', '.') }}</strong>
                    </div>
                    <div class="hpp-summary-item">
                        <span>Upah</span>
                        <strong class="mono">Rp {{ number_format((float) ($cuttingHppTotals['wage'] ?? 0), 2, ',', '.') }}</strong>
                    </div>
                    <div class="hpp-summary-item is-total">
                        <span>Total HPP</span>
                        <strong class="mono">Rp {{ number_format((float) ($cuttingHppTotals['total'] ?? 0), 2, ',', '.') }}</strong>
                    </div>
                </div>

                @php
                    $hppHasMissingMapping = collect($cuttingHppRows ?? [])->contains(
                        fn ($row) => $row['material_cost_missing'] || $row['wage_rate_missing']
                    );
                @endphp
                @if ($hppHasMissingMapping)
                    <div class="hpp-warning mb-2">
                        Sebagian biaya belum dapat dihitung karena biaya LOT atau tarif upah belum tersedia.
                        Lengkapi master biaya sebelum menjadikan angka ini dasar posting.
                    </div>
                @endif

                @if (collect($cuttingHppRows ?? [])->isEmpty())
                    <div class="small-muted">Belum ada bundle untuk dihitung.</div>
                @else
                    <div class="table-wrap">
                        <table class="table table-sm align-middle hpp-detail-table mb-0">
                            <thead>
                                <tr>
                                    <th>Bundle / Item</th>
                                    <th class="text-end">Qty</th>
                                    <th class="text-end">Bahan</th>
                                    <th class="text-end">Upah</th>
                                    <th class="text-end">HPP / pcs</th>
                                    <th class="text-end">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($cuttingHppRows as $hppRow)
                                    <tr>
                                        <td>
                                            <div class="fw-semibold">{{ $hppRow['item_code'] }}</div>
                                            <div class="small-muted">{{ $hppRow['bundle_code'] }}</div>
                                        </td>
                                        <td class="text-end mono">{{ number_format($hppRow['qty_pcs'], 2, ',', '.') }}</td>
                                        <td class="text-end mono">
                                            <div>Rp {{ number_format($hppRow['material_cost'], 2, ',', '.') }}</div>
                                            <div class="hpp-unit-cost">@ Rp {{ number_format($hppRow['material_unit_cost'], 2, ',', '.') }}/Kg</div>
                                        </td>
                                        <td class="text-end mono">
                                            <div>Rp {{ number_format($hppRow['wage_cost'], 2, ',', '.') }}</div>
                                            <div class="hpp-unit-cost">@ Rp {{ number_format($hppRow['wage_rate'], 2, ',', '.') }}/pcs</div>
                                        </td>
                                        <td class="text-end mono">Rp {{ number_format($hppRow['unit_cost'], 2, ',', '.') }}</td>
                                        <td class="text-end mono fw-semibold">Rp {{ number_format($hppRow['total_cost'], 2, ',', '.') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </details>
        @endif

        {{-- ===========================
            SUMMARY
        ============================ --}}
        <details class="card mb-3 production-financial-section summary-section">
            <summary>
                <div>
                    <div class="production-financial-title">
                        <i class="bi bi-bar-chart-line me-1" aria-hidden="true"></i> Ringkasan Output
                    </div>
                    <div class="production-financial-subtitle">
                        Ringkasan kuantitas hasil cutting dan pemakaian kain.
                    </div>
                </div>
                <span class="badge bg-secondary-subtle text-secondary border">{{ $totalBundles }} bundle</span>
            </summary>
            <div class="production-financial-section-body">
                <div class="summary-output-grid">
                    <div class="summary-output-item">
                        <div class="help">Jumlah Bundle</div>
                        <div class="mono">{{ $totalBundles }}</div>
                    </div>

                    <div class="summary-output-item">
                        <div class="help">Total Qty Cutting</div>
                        <div class="mono">{{ number_format($totalQtyPcs, 2, ',', '.') }} pcs</div>
                    </div>

                    <div class="summary-output-item">
                        <div class="help">Total Pemakaian Kain</div>
                        <div class="mono">{{ number_format($totalUsedFabric, 2, ',', '.') }} Kg</div>
                    </div>

                    <div class="summary-output-item">
                        <div class="help">Pemakaian Kain / pcs</div>
                        <div class="mono">{{ number_format($fabricPerPcs, 4, ',', '.') }} Kg/pcs</div>
                    </div>

                    @if ($hasQcCutting)
                        <div class="summary-output-item">
                            <div class="help">Total OK / Reject QC</div>
                            <div class="mono">
                                OK: {{ number_format($qcTotalOk, 2, ',', '.') }}
                                /
                                <span class="{{ $qcTotalReject > 0 ? 'text-danger fw-semibold' : '' }}">
                                    Reject: {{ number_format($qcTotalReject, 2, ',', '.') }}
                                </span>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </details>

        {{-- ===========================
            SISA KAIN PER LOT
        ============================ --}}
        @if ($job->lots && $job->lots->count() > 0)
        @php
            $lotsWithSisa = $job->lots->filter(fn($l) => $l->sisa_recorded_at);
            $lotsNeedSisa = $job->lots->filter(fn($l) => !$l->sisa_recorded_at && (float)($l->used_fabric_qty ?? 0) > 0);
        @endphp
        <details class="card mb-3 production-financial-section fabric-remnant-section">
            <summary>
                <div>
                    <div class="production-financial-title">
                        <i class="bi bi-box-seam me-1" aria-hidden="true"></i> Sisa Kain per LOT
                    </div>
                    <div class="production-financial-subtitle">
                        Rekap sisa layak, scrap, dan status pencatatan setiap LOT.
                    </div>
                </div>
                @if ($lotsNeedSisa->isNotEmpty())
                    <span class="badge bg-warning-subtle text-warning-emphasis border">
                        {{ $lotsNeedSisa->count() }} perlu dicatat
                    </span>
                @else
                    <span class="badge bg-success-subtle text-success border">
                        Selesai
                    </span>
                @endif
            </summary>
            <div class="production-financial-section-body">

            {{-- Sudah tercatat --}}
            @if ($lotsWithSisa->isNotEmpty())
            <div class="table-responsive mb-3">
                <table class="table table-sm mb-0">
                    <thead>
                        <tr>
                            <th>Kain / LOT</th>
                            <th class="text-end">Total Pemakaian
                                <div class="fw-normal text-muted" style="font-size:.62rem;">kain terpakai (kg)</div>
                            </th>
                            <th class="text-end">Sisa Layak</th>
                            <th class="text-end">Scrap</th>
                            <th>Dicatat</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($lotsWithSisa as $cjl)
                        <tr>
                            <td>
                                <div class="mono fw-semibold">{{ $cjl->lot?->item?->code ?? '-' }}</div>
                                <div class="small text-muted mono">{{ $cjl->lot?->code ?? '-' }}</div>
                            </td>
                            <td class="text-end mono">{{ number_format((float)$cjl->used_fabric_qty, 2, ',', '.') }}</td>
                            <td class="text-end mono fw-semibold text-success">{{ number_format((float)$cjl->qty_sisa_fabric, 2, ',', '.') }}</td>
                            <td class="text-end mono fw-semibold {{ (float)($cjl->qty_scrap ?? 0) > 0 ? 'text-danger' : 'text-muted' }}">{{ number_format((float)($cjl->qty_scrap ?? 0), 2, ',', '.') }}</td>
                            <td class="small text-muted">{{ \Carbon\Carbon::parse($cjl->sisa_recorded_at)->format('d/m/Y H:i') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- ── EVALUASI SCRAP% AKTUAL vs BOM ── --}}
            @php
                $evalUsed  = $lotsWithSisa->sum(fn($l) => (float) $l->used_fabric_qty);
                $evalSisa  = $lotsWithSisa->sum(fn($l) => (float) $l->qty_sisa_fabric);
                $evalScrap = $lotsWithSisa->sum(fn($l) => (float) ($l->qty_scrap ?? 0));
                $evalNet   = $evalUsed - $evalSisa;          // benar-benar terkonsumsi
                $evalGood  = $evalNet - $evalScrap;          // jadi potongan
                $scrapActualPct = $evalGood > 0.0001 ? round($evalScrap / $evalGood * 100, 2) : null;
            @endphp
            @if ($evalScrap > 0.0001 && $scrapActualPct !== null && !empty($bomScrapTargets ?? []))
            <div class="mb-3" style="background:#fffbeb;border:1px solid #fde68a;border-radius:10px;padding:.7rem .9rem;">
                <div style="font-size:.78rem;font-weight:800;color:#92400e;">
                    Scrap aktual job ini: <span class="mono">{{ number_format($scrapActualPct, 2) }}%</span>
                    <span class="text-muted fw-normal">({{ number_format($evalScrap, 2, ',', '.') }} kg dari {{ number_format($evalGood, 2, ',', '.') }} kg jadi potongan)</span>
                </div>
                <div class="d-flex align-items-center gap-2 flex-wrap mt-1">
                    @foreach ($bomScrapTargets as $tItemId => $t)
                        <div class="d-inline-flex align-items-center gap-1" style="font-size:.74rem;">
                            <span class="mono">{{ $t['item_code'] }}</span>
                            <span class="text-muted">scrap BOM {{ number_format($t['scrap_pct'], 2) }}%</span>
                            @if (abs($t['scrap_pct'] - $scrapActualPct) > 0.05)
                                <button type="button" class="btn btn-sm btn-outline-warning btn-update-scrap py-0"
                                        style="font-size:.7rem;"
                                        data-url="{{ $t['quick_url'] }}"
                                        data-material="{{ (int) $job->fabric_item_id }}"
                                        data-scrap="{{ $scrapActualPct }}">
                                    → {{ number_format($scrapActualPct, 2) }}%
                                </button>
                            @else
                                <span class="badge bg-success-subtle text-success border">sesuai</span>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
            @endif
            @endif

            @if ($lotsWithSisa->isEmpty())
            <div class="text-muted small">Kain sisa belum dicatat atau digunakan penuh. Sisa kain bisa dicatat melalui tombol Simpan & Konfirmasi.</div>
            @endif
            </div>
        </details>
        @endif

        {{-- =========================================================
            MODAL KONFIRMASI SELESAI CUTTING + CATAT SISA (wajib)
        ========================================================= --}}
        @if (($canQuickOkCutting ?? false))
        {{-- Form finalisasi tetap berada di DOM karena dipanggil setelah AJAX sisa kain sukses. --}}
        <form id="formQuickOk" action="{{ route('production.qc.cutting.quick_ok', $job) }}" method="post" style="display:none;">
            @csrf
        </form>

        <div class="modal fade" id="modalSelesaiCutting" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Selesai Cutting — {{ $job->code }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="text-muted small mb-3">Periksa detail bundle &amp; catat sisa kain per LOT sebelum menyelesaikan cutting. Semua bundle akan dianggap OK dan masuk WIP-CUT.</div>

                        <h6 class="mb-2">Detail Bundle</h6>
                        <div class="table-responsive mb-3">
                            <table class="table table-sm mb-0">
                                <thead>
                                    <tr>
                                        <th>Kain / Item</th>
                                        <th>LOT</th>
                                        <th class="text-end">Qty (pcs)</th>
                                        <th class="text-end">Kain terpakai</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($job->bundles as $b)
                                    <tr>
                                        <td class="mono fw-semibold">{{ $b->finishedItem?->code ?? '-' }}</td>
                                        <td class="mono small text-muted">{{ $b->lot?->code ?? '-' }}</td>
                                        <td class="text-end mono">{{ number_format((float) $b->qty_pcs, 0, ',', '.') }}</td>
                                        <td class="text-end mono">{{ number_format((float) ($b->qty_used_fabric ?? 0), 2, ',', '.') }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        @php $modalLots = $lotsNeedSisa ?? collect(); @endphp
                        @if ($modalLots->isNotEmpty())
                        <h6 class="mb-1">Catat Sisa Kain <span class="text-danger">*</span></h6>
                        <div class="text-muted mb-2" style="font-size:.72rem;">Total kain sebelum dipotong = pemakaian + sisa layak + scrap.</div>
                        <div class="table-responsive">
                            <table class="table table-sm mb-0 align-middle">
                                <thead>
                                    <tr>
                                        <th>Kain / LOT</th>
                                        <th class="text-end">Pemakaian</th>
                                        <th class="text-end" style="width:110px">Sisa Layak</th>
                                        <th class="text-end" style="width:110px">Scrap</th>
                                        <th class="text-end">Total sblm potong</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($modalLots as $cjl)
                                    @php
                                        $lotUsed = (float) $cjl->used_fabric_qty;
                                        $lotGood = $job->bundles->where('lot_id', $cjl->lot_id)->sum(function ($b) use ($bomScrapTargets) {
                                            $t = ($bomScrapTargets ?? [])[(int) $b->finished_item_id] ?? null;
                                            return $t ? (float) $b->qty_pcs * (float) $t['bom_qty'] : 0.0;
                                        });
                                        $lotRemnant = max((float) ($cjl->lot?->qty_onhand ?? 0), 0);
                                        $lotExcess  = $lotGood > 0 ? round(max($lotUsed - $lotGood, 0), 2) : 0.0;
                                        $mSisa  = $lotRemnant > 0 ? round($lotRemnant, 2) : 0;
                                        $mScrap = $lotExcess > 0 ? $lotExcess : 0;
                                    @endphp
                                    <tr class="modal-sisa-row" data-lot="{{ $cjl->lot_id }}" data-used="{{ $lotUsed }}">
                                        <td>
                                            <div class="mono fw-semibold">{{ $cjl->lot?->item?->code ?? '-' }}</div>
                                            <div class="small text-muted mono">{{ $cjl->lot?->code ?? '-' }}</div>
                                        </td>
                                        <td class="text-end mono">{{ number_format($lotUsed, 2, ',', '.') }}</td>
                                        <td class="text-end">
                                            <input type="number" class="form-control form-control-sm text-end mono m-sisa" step="0.01" min="0" value="{{ $mSisa }}" style="max-width:100px;margin-left:auto;border-color:#86efac;" onfocus="this.select()">
                                        </td>
                                        <td class="text-end">
                                            <input type="number" class="form-control form-control-sm text-end mono m-scrap" step="0.01" min="0" value="{{ $mScrap }}" style="max-width:100px;margin-left:auto;border-color:#fca5a5;" onfocus="this.select()">
                                        </td>
                                        <td class="text-end mono fw-semibold m-total">0</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @else
                        <div class="text-success small"><i class="bi bi-check-circle me-1"></i>Semua sisa kain LOT sudah dicatat.</div>
                        @endif

                        <div id="modalSisaError" class="alert alert-danger py-2 px-3 mt-3 mb-0" style="display:none;font-size:.8rem;"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="button" id="btnConfirmSelesai" class="btn btn-sm btn-success">Simpan &amp; Selesai Cutting</button>
                    </div>
                </div>
            </div>
        </div>

        @push('scripts')
        <script>
        (function () {
            const modal = document.getElementById('modalSelesaiCutting');
            if (!modal) return;
            const sisaUrl = "{{ route('production.cutting_jobs.sisa_fabric', $job) }}";
            const token = "{{ csrf_token() }}";
            const errBox = document.getElementById('modalSisaError');
            const btn = document.getElementById('btnConfirmSelesai');
            const quickForm = document.getElementById('formQuickOk');

            function showErr(m) { errBox.textContent = m; errBox.style.display = 'block'; }
            function clearErr() { errBox.style.display = 'none'; }

            modal.querySelectorAll('.modal-sisa-row').forEach(function (row) {
                const used = parseFloat(row.dataset.used || '0') || 0;
                const sisaI = row.querySelector('.m-sisa');
                const scrapI = row.querySelector('.m-scrap');
                const totalC = row.querySelector('.m-total');
                function recalc() {
                    const s = parseFloat(sisaI.value || '0') || 0;
                    const k = parseFloat(scrapI.value || '0') || 0;
                    totalC.textContent = (used + s + k).toFixed(2);
                }
                sisaI.addEventListener('input', recalc);
                scrapI.addEventListener('input', recalc);
                recalc();
            });

            btn.addEventListener('click', async function () {
                clearErr();
                if (!quickForm) {
                    showErr('Form finalisasi tidak tersedia. Refresh halaman lalu coba lagi.');
                    return;
                }

                const rows = Array.from(modal.querySelectorAll('.modal-sisa-row'));
                for (const r of rows) {
                    const s = r.querySelector('.m-sisa').value;
                    const k = r.querySelector('.m-scrap').value;
                    if (s === '' || k === '') { showErr('Isi Sisa Layak & Scrap untuk semua LOT (boleh 0).'); return; }
                }
                btn.disabled = true;
                try {
                    if (rows.length) {
                        const lots = rows.map(function (r) {
                            return {
                                lot_id: r.dataset.lot,
                                qty_sisa: parseFloat(r.querySelector('.m-sisa').value || '0'),
                                qty_scrap: parseFloat(r.querySelector('.m-scrap').value || '0')
                            };
                        });
                        const res = await fetch(sisaUrl, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': token,
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                            body: JSON.stringify({ lots: lots })
                        });
                        if (!res.ok) {
                            let msg = 'Gagal mencatat sisa kain.';
                            try {
                                const j = await res.json();
                                msg = j.message || (j.errors ? Object.values(j.errors).flat().join(' ') : msg);
                            } catch (e) {}
                            showErr(msg);
                            btn.disabled = false;
                            return;
                        }
                    }
                    quickForm.submit();
                } catch (e) {
                    console.error('Finalisasi Cutting gagal:', e);
                    showErr('Gagal menyelesaikan Cutting. Periksa koneksi lalu coba lagi.');
                    btn.disabled = false;
                }
            });
        })();
        </script>
        @endpush
        @endif

    </div>

    {{-- Cancel QC error UI (tetap) --}}
    @if (session('qc_cancel_ui'))
        @php($ui = session('qc_cancel_ui'))
        @php($action = $ui['action'] ?? null)

        <div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 2000;">
            <div id="qcCancelToast" class="toast align-items-center text-bg-danger border-0" role="alert"
                aria-live="assertive" aria-atomic="true" data-bs-autohide="false">
                <div class="d-flex">
                    <div class="toast-body">
                        <div class="fw-semibold">{{ $ui['toast'] ?? 'Cancel QC gagal.' }}</div>
                        <div class="small opacity-75">Klik Detail untuk lihat penyebab & solusi.</div>
                    </div>

                    <div class="d-flex align-items-center gap-2 pe-2">
                        @if ($action)
                            <a href="{{ route($action['route'], ...$action['params']) }}" class="btn btn-sm btn-light">
                                Buka Sewing Pickup
                            </a>
                        @endif

                        <button type="button" class="btn btn-sm btn-outline-light" data-bs-toggle="modal"
                            data-bs-target="#qcCancelDetailModal">
                            Detail
                        </button>

                        <button type="button" class="btn-close btn-close-white me-1 m-auto" data-bs-dismiss="toast"
                            aria-label="Close"></button>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="qcCancelDetailModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ $ui['title'] ?? 'Detail Cancel QC' }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body">
                        <ul class="mb-0">
                            @foreach ($ui['lines'] ?? [] as $line)
                                <li>{{ $line }}</li>
                            @endforeach
                        </ul>
                    </div>

                    <div class="modal-footer">
                        @if ($action)
                            <a href="{{ route($action['route'], ...$action['params']) }}" class="btn btn-primary">
                                Buka Sewing Pickup
                            </a>
                        @endif
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Tutup</button>
                    </div>
                </div>
            </div>
        </div>

        @push('scripts')
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    const el = document.getElementById('qcCancelToast');
                    if (!el) return;
                    bootstrap.Toast.getOrCreateInstance(el).show();
                });
            </script>
        @endpush
    @endif
    
    {{-- MOBILE ACTIONS (Sticky Bottom Bar) --}}
    <div class="d-block d-md-none mobile-sticky-actions">
        @if (strtolower((string) (auth()->user()->role ?? '')) !== 'operating')
            {!! $renderActions(true) !!}
        @endif
    </div>

@push('scripts')
<script>
// ── Autofill scrap di form sisa kain: scrap = dipakai − sisa layak − potongan ──
(function () {
    document.querySelectorAll('.js-sisa-row').forEach(function (row) {
        const used    = parseFloat(row.dataset.used || '0');
        const good    = parseFloat(row.dataset.good || '0');
        const remnant = parseFloat(row.dataset.remnant || '0');
        const sisaI = row.querySelector('.js-sisa-input');
        const scrapI = row.querySelector('.js-scrap-input');
        if (!sisaI || !scrapI || (good <= 0 && remnant <= 0)) return;

        function recalcScrap() {
            if (scrapI.dataset.auto === '0') return; // sudah diedit manual
            const sisa = parseFloat(sisaI.value || '0');
            // scrap = kelebihan pemakaian + sisa fisik LOT − yang diambil sebagai sisa layak
            const excess = good > 0 ? Math.max(used - good, 0) : 0;
            scrapI.value = Math.max(excess + remnant - sisa, 0).toFixed(2);
        }

        sisaI.addEventListener('input', recalcScrap);
        scrapI.addEventListener('input', function () {
            scrapI.dataset.auto = '0'; // user ambil alih — stop autofill
        });
    });
})();

// ── Update scrap% BOM dari evaluasi sisa kain ──
(function () {
    document.querySelectorAll('.btn-update-scrap').forEach(function (btn) {
        btn.addEventListener('click', async function () {
            btn.disabled = true;
            const orig = btn.textContent;
            btn.textContent = '⏳';
            try {
                const resp = await fetch(btn.dataset.url, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        material_item_id: parseInt(btn.dataset.material, 10),
                        scrap_pct: parseFloat(btn.dataset.scrap),
                    }),
                });
                const json = await resp.json();
                if (json.success) {
                    btn.outerHTML = '<span class="badge bg-success-subtle text-success border">✓ scrap ' + Number(json.new_scrap).toFixed(2) + '%</span>';
                } else {
                    btn.textContent = '✗ gagal';
                    btn.disabled = false;
                }
            } catch (e) {
                btn.textContent = orig;
                btn.disabled = false;
            }
        });
    });
})();
</script>
<script>
(function () {
    const table   = document.getElementById('bqTable');
    const btnHide = document.getElementById('btnHideOk');
    if (!table) return;

    // ── Hide-OK toggle ──────────────────────────────────────
    if (btnHide) {
        const okRows = () => table.querySelectorAll('tbody tr.bq-row-ok');
        let hiding = false;

        function updateHideBtn() {
            const n = okRows().length;
            if (n > 0) btnHide.style.display = '';
            btnHide.textContent = hiding
                ? 'Tampilkan ✓ (' + n + ')'
                : 'Sembunyikan ✓ (' + n + ')';
            btnHide.disabled = n === 0;
        }

        btnHide.addEventListener('click', function () {
            hiding = !hiding;
            table.classList.toggle('bq-hide-ok', hiding);
            updateHideBtn();
        });

        updateHideBtn();
    }

    // ── Inline QC inputs ────────────────────────────────────
    // Reject → auto-calc OK = qty - reject
    table.addEventListener('input', function (e) {
        const id = e.target.dataset.id;
        if (!id) return;

        if (e.target.classList.contains('input-reject')) {
            const qty  = parseInt(e.target.dataset.bundle) || 0;
            let   rej  = Math.max(0, parseInt(e.target.value) || 0);
            if (rej > qty) { rej = qty; e.target.value = rej; }
            const okEl = table.querySelector('.input-ok[data-id="' + id + '"]');
            if (okEl) { okEl.value = qty - rej; }
        }

        if (e.target.classList.contains('input-ok')) {
            const qty = parseInt(e.target.dataset.bundle) || 0;
            let   ok  = Math.max(0, parseInt(e.target.value) || 0);
            if (ok > qty) { ok = qty; e.target.value = ok; }
            const rejEl = table.querySelector('.input-reject[data-id="' + id + '"]');
            if (rejEl) {
                let rej = qty - ok;
                if (rej < 0) rej = 0;
                rejEl.value = rej;
            }
        }
    });

    // Select-all on focus
    table.addEventListener('focus', function (e) {
        if (e.target.matches('.input-ok,.input-reject')) {
            setTimeout(() => e.target.select(), 0);
        }
    }, true);

    // ── Simpan button ────────────────────────────────────────
    table.addEventListener('click', function (e) {
        const btn = e.target.closest('.btn-save-bundle');
        if (!btn) return;

        const id   = btn.dataset.id;
        const pcs  = parseInt(btn.dataset.pcs) || 0;
        const code = btn.dataset.code || '';

        const okEl  = table.querySelector('.input-ok[data-id="' + id + '"]');
        const rejEl = table.querySelector('.input-reject[data-id="' + id + '"]');
        const rsnEl = table.querySelector('.input-reason[data-id="' + id + '"]');

        const ok  = parseInt(okEl  ? okEl.value  : pcs) || 0;
        const rej = parseInt(rejEl ? rejEl.value : 0)   || 0;
        const rsn = rsnEl ? rsnEl.value.trim() : '';

        if (rej > 0 && !rsn) {
            const fill = confirm('Bundle ' + code + ':\nReject ' + rej + ' pcs tapi alasan kosong.\nLanjutkan?');
            if (!fill) { if (rsnEl) rsnEl.focus(); return; }
        }

        const msg = 'Simpan QC Bundle ' + code + '?\nOK: ' + ok + '  Reject: ' + rej;
        if (!confirm(msg)) return;

        // Isi hidden form lalu submit
        const form    = document.getElementById('bqf-' + id);
        const okHid   = document.getElementById('bqf-ok-'  + id);
        const rejHid  = document.getElementById('bqf-rej-' + id);
        const rsnHid  = document.getElementById('bqf-rsn-' + id);

        if (!form) { alert('Form tidak ditemukan.'); return; }
        if (okHid)  okHid.value  = ok;
        if (rejHid) rejHid.value = rej;
        if (rsnHid) rsnHid.value = rsn;

        btn.disabled = true;
        btn.textContent = '⏳';
        form.submit();
    });
})();
</script>
@endpush
@endsection
