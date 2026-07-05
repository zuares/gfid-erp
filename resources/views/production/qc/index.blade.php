{{-- resources/views/production/qc/index.blade.php --}}
@extends('layouts.app')

@section('title', 'Produksi • QC Overview')

@push('head')
    <style>
        .qc-overview-page {
            min-height: 100vh;
        }

        .page-wrap {
            max-width: 1100px;
            margin-inline: auto;
            padding: 1rem 1rem 3rem;
        }

        body[data-theme="light"] .page-wrap {
            background:
                radial-gradient(circle at top left,
                    rgba(59, 130, 246, 0.12) 0,
                    rgba(45, 212, 191, 0.10) 26%,
                    #f9fafb 60%);
        }

        body[data-theme="dark"] .page-wrap {
            background:
                radial-gradient(circle at top left,
                    rgba(59, 130, 246, 0.25) 0,
                    rgba(45, 212, 191, 0.15) 26%,
                    #020617 60%);
        }

        .card {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 14px;
        }

        .mono {
            font-variant-numeric: tabular-nums;
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, "Liberation Mono";
        }

        .help {
            color: var(--muted);
            font-size: .85rem;
        }

        .nav-qc .nav-link {
            border-radius: 999px;
            padding-inline: 1rem;
        }

        .table-wrap {
            overflow-x: auto;
        }

        .badge-soft {
            border-radius: 999px;
            padding: .17rem .5rem;
            font-size: .7rem;
        }

        /* chips item (global) */
        .item-chips {
            display: flex;
            flex-wrap: wrap;
            gap: .25rem;
        }

        .item-chip {
            display: inline-flex;
            align-items: baseline;
            gap: .25rem;
            font-size: .72rem;
            line-height: 1.1;
            padding: .2rem .45rem;
            border-radius: 999px;
            background: rgba(148, 163, 184, 0.14);
            border: 1px solid rgba(148, 163, 184, 0.18);
            white-space: nowrap;
        }

        .item-chip b {
            font-weight: 700;
            letter-spacing: .02em;
        }

        .item-chip .q {
            color: var(--muted);
        }

        .item-chip-more {
            background: transparent;
            color: var(--muted);
        }

        /* progress (global) */
        .prog-wrap {
            min-width: 150px;
        }

        .prog {
            height: 6px;
            border-radius: 999px;
            overflow: hidden;
            background: rgba(148, 163, 184, 0.22);
        }

        .prog>span {
            display: block;
            height: 100%;
            border-radius: 999px;
            transition: width .3s ease;
        }

        .fill-done {
            background: linear-gradient(90deg, #16a34a, #22c55e);
        }

        .fill-part {
            background: linear-gradient(90deg, #2563eb, #38bdf8);
        }

        .fill-rej {
            background: linear-gradient(90deg, #e11d48, #fb7185);
        }

        .fill-zero {
            background: rgba(148, 163, 184, 0.5);
        }

        .prog-num {
            font-size: .72rem;
            color: var(--muted);
            margin-top: .25rem;
            display: flex;
            justify-content: space-between;
            gap: .5rem;
        }

        .prog-num b {
            color: inherit;
            font-weight: 700;
        }

        .qc-row {
            cursor: pointer;
            transition: background-color .12s ease;
        }

        .qc-row:hover {
            background: color-mix(in srgb, var(--card) 84%, #3b82f6 6%);
        }

        .qc-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: .75rem;
        }

        .qc-header-title {
            display: flex;
            flex-direction: column;
            gap: .1rem;
        }

        .qc-header-title h1 {
            font-size: 1.1rem;
            margin: 0;
        }

        .qc-header-sub {
            font-size: .8rem;
            color: var(--muted);
        }

        .qc-header-stage {
            font-size: .75rem;
            color: var(--muted);
        }

        @media (max-width: 767.98px) {
            .page-wrap {
                padding-inline: .75rem;
            }

            .qc-header {
                align-items: center;
            }

            .qc-header-title {
                flex: 1;
            }

            .qc-header-title h1 {
                font-size: 1rem;
                display: flex;
                align-items: center;
                gap: .35rem;
            }

            .qc-header-sub {
                font-size: .75rem;
            }

            .qc-header-pill {
                padding: .2rem .7rem;
                border-radius: 999px;
                border: 1px solid color-mix(in srgb, var(--line) 80%, transparent 20%);
                font-size: .7rem;
                color: var(--muted);
                white-space: nowrap;
                background: color-mix(in srgb, var(--card) 90%, var(--line) 10%);
            }

            .nav-qc {
                margin-top: .75rem !important;
                gap: .35rem;
                overflow-x: auto;
                flex-wrap: nowrap;
            }

            .nav-qc .nav-link {
                font-size: .8rem;
                padding-inline: .8rem;
                padding-block: .25rem;
                white-space: nowrap;
            }
        }

        /* ============================
                                       MOBILE: QC CUTTING LIST
                                    ============================ */
        @media (max-width: 767.98px) {
            .qc-mobile-secondary {
                font-size: .75rem;
                color: var(--muted);
            }

            /* chip item */
            .chip-soft {
                display: inline-flex;
                align-items: center;
                border-radius: 999px;
                padding: .08rem .5rem;
                border: 1px solid color-mix(in srgb, var(--line) 85%, transparent 15%);
                background: color-mix(in srgb, var(--card) 90%, var(--line) 10%);
                font-size: .72rem;
                max-width: 100%;
                white-space: nowrap;
                text-overflow: ellipsis;
                overflow: hidden;
            }

            .chip-soft+.chip-soft {
                margin-left: .25rem;
            }

            .chip-soft .qty {
                opacity: .8;
                margin-left: .2rem;
            }

            .qc-mobile-list {
                display: flex;
                flex-direction: column;
                gap: .6rem;
            }

            .qc-mobile-card {
                border-radius: 16px;
                padding: .7rem .8rem;
                background:
                    radial-gradient(circle at top left,
                        color-mix(in srgb, var(--line) 18%, transparent 82%) 0,
                        color-mix(in srgb, var(--card) 94%, var(--line) 6%) 55%);
                border: 1px solid color-mix(in srgb, var(--line) 75%, transparent 25%);
                box-shadow:
                    0 10px 25px rgba(15, 23, 42, 0.18),
                    0 0 0 1px rgba(15, 23, 42, 0.03);
                cursor: pointer;
                transition: transform 90ms ease-out, box-shadow 90ms ease-out, background 120ms ease-out;
            }

            body[data-theme="dark"] .qc-mobile-card {
                box-shadow:
                    0 14px 40px rgba(0, 0, 0, 0.75),
                    0 0 0 1px rgba(15, 23, 42, 0.7);
            }

            .qc-mobile-card:hover {
                transform: translateY(-1px);
                box-shadow:
                    0 14px 32px rgba(15, 23, 42, 0.22),
                    0 0 0 1px rgba(15, 23, 42, 0.06);
            }

            .qc-mobile-card:active {
                transform: translateY(1px);
                box-shadow:
                    0 6px 16px rgba(15, 23, 42, 0.25),
                    0 0 0 1px rgba(15, 23, 42, 0.09);
            }

            .qc-mobile-card-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                gap: .5rem;
                margin-bottom: .35rem;
            }

            .qc-mobile-date-pill {
                display: inline-flex;
                align-items: center;
                border-radius: 999px;
                padding: .08rem .6rem;
                font-size: .75rem;
                font-weight: 600;
                background: color-mix(in srgb, var(--card) 92%, var(--line) 8%);
                border: 1px solid color-mix(in srgb, var(--line) 80%, transparent 20%);
            }

            .qc-mobile-status-pill {
                font-size: .7rem;
                border-radius: 999px;
                padding: .12rem .6rem;
            }

            .qc-mobile-card-body {
                display: flex;
                flex-direction: column;
                gap: .18rem;
                font-size: .78rem;
            }

            .qc-mobile-row-line {
                display: flex;
                justify-content: space-between;
                align-items: center;
                gap: .5rem;
                flex-wrap: wrap;
            }

            .qc-mobile-row-main {
                font-weight: 500;
            }

            .qc-mobile-metadata {
                font-size: .76rem;
                font-weight: 600;
            }
        }


        @media (max-width: 767.98px) {

            /* Kunci halaman di X seperti Sewing Return */
            html,
            body {
                overflow-x: hidden;
            }

            .qc-overview-page,
            .page-wrap {
                overflow-x: hidden;
            }

            /* Matikan scroll horizontal wrapper */
            .table-wrap,
            .table-responsive {
                overflow-x: visible;
            }

            /* Biar gesture fokus ke scroll atas-bawah */
            .qc-overview-page table tbody tr,
            .qc-mobile-card {
                touch-action: pan-y;
            }
        }

        /* === GreatFit / Shipments aligned header override === */
        :root{
            --shp-accent:#334155;
            --shp-accent-2:#1f2937;
            --shp-border:rgba(148,163,184,.18);
            --shp-border-strong:rgba(148,163,184,.30);
            --shp-muted:#64748b;
        }

        .qc-overview-page{ min-height:100vh; background:transparent!important; }

        .page-wrap{
            max-width:1040px!important;
            margin-inline:auto;
            padding:.75rem .75rem 4rem!important;
            background:transparent!important;
        }

        body[data-theme="light"] .page-wrap,
        body[data-theme="dark"] .page-wrap{ background:transparent!important; }

        .qc-main-card{
            border-radius:8px!important;
            border:1px solid var(--shp-border)!important;
            box-shadow:none!important;
            background:var(--card)!important;
        }

        body[data-theme="dark"] .qc-main-card{ border-color:rgba(51,65,85,.85)!important; }

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

        body[data-theme="dark"] .ship-topbar{ background:var(--card,#0f172a); }

        .title{ font-weight:750; font-size:1rem; letter-spacing:0; margin:0; }
        .sub{ color:var(--shp-muted); font-size:.78rem; }
        body[data-theme="dark"] .sub{ color:#9ca3af; }

        .kpis{ display:flex; flex-wrap:wrap; gap:.32rem; margin-top:.35rem; }

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

        body[data-theme="dark"] .kpi{
            background:rgba(15,23,42,.96);
            border-color:rgba(51,65,85,.85);
        }

        .kpi .lbl{
            text-transform:none;
            letter-spacing:0;
            font-size:.66rem;
            color:#94a3b8;
        }

        .kpi .val{ font-weight:650; color:var(--shp-accent); }
        body[data-theme="dark"] .kpi .val{ color:#e5e7eb; }

        .controls{
            display:flex;
            gap:.5rem;
            align-items:center;
            flex-wrap:wrap;
            justify-content:flex-end;
        }

        .nav-qc{
            display:flex;
            gap:.35rem;
            flex-wrap:wrap;
            margin:0!important;
        }

        .nav-qc .nav-link{
            border-radius:7px!important;
            padding:.34rem .75rem!important;
            font-size:.82rem;
            font-weight:600;
            color:#475569;
            border:1px solid rgba(148,163,184,.35);
            background:transparent;
        }

        .nav-qc .nav-link:hover{
            background:rgba(148,163,184,.08);
            color:#111827;
        }

        .nav-qc .nav-link.active{
            background:var(--shp-accent)!important;
            border-color:var(--shp-accent)!important;
            color:#fff!important;
        }

        body[data-theme="dark"] .nav-qc .nav-link{
            color:#cbd5e1;
            border-color:rgba(51,65,85,.85);
        }

        @media (max-width:767.98px){
            .page-wrap{ padding:.5rem .5rem 4rem!important; }
            .ship-topbar{ margin-inline:-.5rem; padding:.5rem .65rem; }
            .title{ font-size:1.05rem; }
            .sub{ display:none; }

            .controls{
                width:100%;
                justify-content:flex-start;
                overflow-x:auto;
                flex-wrap:nowrap;
                padding-bottom:.1rem;
            }

            .nav-qc{
                flex-wrap:nowrap!important;
                overflow-x:auto;
                width:100%;
                padding-bottom:.1rem;
            }

            .nav-qc .nav-link{
                white-space:nowrap;
                min-height:38px;
                display:inline-flex;
                align-items:center;
            }

            .kpis{ display:none; }
            .qc-main-card{ border-radius:8px!important; }
        }

    </style>
@endpush

@section('content')
    <div class="qc-overview-page">
        <div class="page-wrap">

            {{-- HEADER --}}
            @php
                $stageLabel = $stage === \App\Models\QcResult::STAGE_CUTTING
                    ? 'QC Cutting'
                    : ($stage === \App\Models\QcResult::STAGE_SEWING ? 'QC Sewing' : 'QC Packing');

                $totalRows = method_exists($records, 'total')
                    ? (int) $records->total()
                    : (method_exists($records, 'count') ? (int) $records->count() : 0);

                $pageRows = method_exists($records, 'count') ? (int) $records->count() : 0;
            @endphp

            <div class="ship-topbar">
                <div>
                    <div class="title">QC Produksi</div>
                    <div class="sub">Monitoring QC Cutting, Sewing, dan Packing.</div>

                    <div class="kpis">
                        <span class="kpi"><span class="lbl">Stage</span><span class="val">{{ $stageLabel }}</span></span>
                        <span class="kpi"><span class="lbl">Total</span><span class="val">{{ number_format($totalRows, 0, ',', '.') }}</span></span>
                        <span class="kpi"><span class="lbl">Halaman</span><span class="val">{{ number_format($pageRows, 0, ',', '.') }}</span></span>
                        <span class="kpi"><span class="lbl">Role</span><span class="val">{{ strtoupper($userRole ?? '-') }}</span></span>
                    </div>
                </div>

                <div class="controls">
                    <ul class="nav nav-pills nav-qc">
                        @if (($userRole ?? null) !== 'admin')
                            <li class="nav-item">
                                <a class="nav-link {{ $stage === 'cutting' ? 'active' : '' }}"
                                    href="{{ route('production.qc.index', ['stage' => 'cutting']) }}">
                                    QC Cutting
                                </a>
                            </li>
                        @endif

                        <li class="nav-item">
                            <a class="nav-link {{ $stage === 'sewing' ? 'active' : '' }}"
                                href="{{ route('production.qc.index', ['stage' => 'sewing']) }}">
                                QC Sewing
                            </a>
                        </li>

                        @if (($userRole ?? null) !== 'admin')
                            <li class="nav-item">
                                <a class="nav-link {{ $stage === 'packing' ? 'active' : '' }}"
                                    href="{{ route('production.qc.index', ['stage' => 'packing']) }}">
                                    QC Packing
                                </a>
                            </li>
                        @endif
                    </ul>
                </div>
            </div>

            {{-- ISI TABEL PER STAGE --}}
            <div class="card qc-main-card p-3">

                {{-- =======================
                     TAB QC CUTTING
                ======================== --}}
                @if ($stage === 'cutting')
                    <h2 class="h6 mb-2">Daftar QC Cutting</h2>

                    {{-- DESKTOP VERSION (LENGKAP: tabel) --}}
                    <div class="table-wrap d-none d-md-block">
                        <table class="table table-sm align-middle mono">
                            <thead>
                                <tr>
                                    <th style="width: 170px;">Cutting</th>
                                    <th style="width: 130px;">Status</th>
                                    <th>Barang</th>
                                    <th style="width: 200px;">Progress QC</th>
                                    <th style="width: 90px;"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($records as $job)
                                    @php
                                        $totalBundles = $job->bundles->count();
                                        $totalQty = $job->bundles->sum('qty_pcs');

                                        // progress QC: bundle yang sudah punya qcResult (stage cutting)
                                        $qcdBundles = $job->bundles->filter(fn($b) => $b->qcResults->isNotEmpty())->count();
                                        $pct = $totalBundles > 0 ? (int) round($qcdBundles / $totalBundles * 100) : 0;
                                        $fill = $totalBundles <= 0 ? 'fill-zero' : ($pct >= 100 ? 'fill-done' : 'fill-part');

                                        // chip barang jadi
                                        $items = $job->bundles
                                            ->groupBy('finished_item_id')
                                            ->map(fn($g) => [
                                                'code' => optional($g->first()->finishedItem)->code ?? '—',
                                                'name' => optional($g->first()->finishedItem)->name ?? '',
                                                'qty' => (int) $g->sum('qty_pcs'),
                                            ])
                                            ->sortByDesc('qty')
                                            ->values();

                                        $rawStatus = $job->status ?? '-';

                                        $map = [
                                            'draft' => ['DRAFT', 'secondary'],
                                            'cut' => ['CUT', 'primary'],
                                            'sent_to_qc' => ['BELUM QC', 'warning'],
                                            'qc_done' => ['QC DONE', 'success'],
                                            'qc_ok' => ['QC OK', 'success'],
                                            'qc_mixed' => ['QC MIXED', 'warning'],
                                            'qc_reject' => ['QC REJECT', 'danger'],
                                        ];

                                        $cfg = $map[$rawStatus] ?? [strtoupper($rawStatus), 'secondary'];
                                        [$statusLabel, $statusClass] = $cfg;
                                        $href = route('production.qc.cutting.edit', $job);
                                    @endphp
                                    <tr class="qc-row" data-href="{{ $href }}">
                                        <td>
                                            <div class="fw-bold">{{ $job->lot?->item?->code ?? '-' }}</div>
                                            <div class="text-muted" style="font-size:.74rem;">
                                                {{ $job->date?->format('d M Y') ?? $job->date }}
                                                @if ($job->lot)
                                                    • {{ $job->lot->code }}
                                                @endif
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-{{ $statusClass }}">{{ $statusLabel }}</span>
                                        </td>
                                        <td>
                                            @if ($items->isEmpty())
                                                <span class="text-muted small">-</span>
                                            @else
                                                <div class="item-chips">
                                                    @foreach ($items->take(4) as $it)
                                                        <span class="item-chip" title="{{ $it['name'] }}">
                                                            <b>{{ $it['code'] }}</b>
                                                            <span class="q">{{ number_format($it['qty'], 0, ',', '.') }}</span>
                                                        </span>
                                                    @endforeach
                                                    @if ($items->count() > 4)
                                                        <span class="item-chip item-chip-more">+{{ $items->count() - 4 }}</span>
                                                    @endif
                                                </div>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="prog-wrap">
                                                <div class="prog"><span class="{{ $fill }}" style="width: {{ $pct }}%"></span></div>
                                                <div class="prog-num">
                                                    <span><b>{{ $pct }}%</b></span>
                                                    <span>{{ $qcdBundles }}/{{ $totalBundles }} iket • {{ number_format($totalQty, 0, ',', '.') }} pcs</span>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <a href="{{ $href }}" class="btn btn-sm btn-outline-primary"
                                                onclick="event.stopPropagation();">QC</a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted small">
                                            Belum ada data QC Cutting.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{-- MOBILE VERSION: LIST 3 BARIS --}}
                    <div class="d-block d-md-none">
                        @if ($records->isEmpty())
                            <div class="text-center text-muted small py-2">
                                Belum ada data QC Cutting.
                            </div>
                        @else
                            <div class="qc-mobile-list mono">
                                @foreach ($records as $job)
                                    @php
                                        $totalBundles = $job->bundles->count();
                                        $totalQty = $job->bundles->sum('qty_pcs');
                                        $totalFabric = $job->bundles->sum('qty_used_fabric');

                                        $qcdBundles = $job->bundles->filter(fn($b) => $b->qcResults->isNotEmpty())->count();
                                        $pct = $totalBundles > 0 ? (int) round($qcdBundles / $totalBundles * 100) : 0;
                                        $fill = $totalBundles <= 0 ? 'fill-zero' : ($pct >= 100 ? 'fill-done' : 'fill-part');

                                        $rawStatus = $job->status ?? '-';

                                        $map = [
                                            'draft' => ['Draft', 'secondary'],
                                            'cut' => ['Cut', 'primary'],
                                            'sent_to_qc' => ['Belum QC', 'warning'],
                                            'qc_done' => ['QC Done', 'success'],
                                            'qc_ok' => ['QC OK', 'success'],
                                            'qc_mixed' => ['QC Mixed', 'warning'],
                                            'qc_reject' => ['QC Reject', 'danger'],
                                        ];

                                        $cfg = $map[$rawStatus] ?? [ucfirst($rawStatus), 'secondary'];
                                        [$statusLabel, $statusClass] = $cfg;

                                        $finishedSummary = $job->bundles
                                            ->groupBy('finished_item_id')
                                            ->map(function ($group) {
                                                $bundle = $group->first();
                                                $item = $bundle?->finishedItem;
                                                return [
                                                    'code' => $item?->code ?? '-',
                                                    'qty' => $group->sum('qty_pcs'),
                                                ];
                                            })
                                            ->values();
                                    @endphp

                                    <div class="qc-mobile-card"
                                        data-href="{{ route('production.qc.cutting.edit', $job) }}">
                                        {{-- Baris 1: TANGGAL --}}
                                        <div class="qc-mobile-card-header">
                                            <div class="qc-mobile-date-pill">
                                                {{ $job->date?->format('Y-m-d') ?? $job->date }}
                                            </div>
                                        </div>

                                        <div class="qc-mobile-card-body">
                                            {{-- Baris 2: KAIN (KG)   - STATUS --}}
                                            <div class="qc-mobile-row-line">
                                                <div>
                                                    @if ($job->lot && $job->lot->item)
                                                        <span class="chip-soft">
                                                            {{ $job->lot->item->code }}
                                                            @if ($totalFabric > 0)
                                                                <span class="qty">
                                                                    ({{ number_format($totalFabric, 2, ',', '.') }} kg)
                                                                </span>
                                                            @endif
                                                        </span>
                                                    @else
                                                        <span class="qc-mobile-secondary">Kain tidak diketahui</span>
                                                    @endif
                                                </div>
                                                <div>
                                                    <span class="badge qc-mobile-status-pill bg-warning text-white">
                                                        {{ $statusLabel }}
                                                    </span>
                                                </div>
                                            </div>

                                            {{-- Baris 3: KODE ITEM JADI (PCS)   - JUMLAH IKET --}}
                                            <div class="qc-mobile-row-line">
                                                <div>
                                                    @if ($finishedSummary->isEmpty())
                                                        <span class="qc-mobile-secondary">Tidak ada item jadi</span>
                                                    @else
                                                        @php
                                                            $fi = $finishedSummary->first();
                                                        @endphp
                                                        <span class="chip-soft">
                                                            {{ $fi['code'] }}
                                                            <span class="qty">
                                                                ({{ number_format($fi['qty'], 0, ',', '.') }} pcs)
                                                            </span>
                                                        </span>
                                                    @endif
                                                </div>
                                                <div class="qc-mobile-metadata text-muted">
                                                    {{ $totalBundles }} iket •
                                                    {{ number_format($totalQty, 0, ',', '.') }} pcs
                                                </div>
                                            </div>

                                            {{-- Progress QC --}}
                                            <div class="prog-wrap mt-1" style="min-width:0;">
                                                <div class="prog"><span class="{{ $fill }}" style="width: {{ $pct }}%"></span></div>
                                                <div class="prog-num">
                                                    <span><b>{{ $pct }}%</b> QC</span>
                                                    <span>{{ $qcdBundles }}/{{ $totalBundles }} iket</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            @if ($records instanceof \Illuminate\Pagination\AbstractPaginator)
                                <div class="mt-2">
                                    {{ $records->links() }}
                                </div>
                            @endif
                        @endif
                    </div>

                    @if ($records instanceof \Illuminate\Pagination\AbstractPaginator && !request()->ajax())
                        <div class="d-none d-md-block mt-2">
                            {{ $records->links() }}
                        </div>
                    @endif

                    {{-- =======================
                     TAB QC SEWING
                ======================== --}}
                @elseif ($stage === 'sewing')
                    <h2 class="h6 mb-2">Daftar QC Sewing</h2>

                    @php
                        $sewStatusMap = [
                            'draft' => ['DRAFT', 'secondary'],
                            'posted' => ['POSTED', 'primary'],
                            'closed' => ['CLOSED', 'success'],
                        ];

                        // chip barang per return (group by finished item code, qty = qty_ok)
                        $sewItems = function ($ret) {
                            return collect($ret->lines)
                                ->groupBy(fn($l) => optional(optional($l->pickupLine)?->bundle?->finishedItem)->code ?: '—')
                                ->map(fn($g) => [
                                    'code' => optional(optional($g->first()->pickupLine)?->bundle?->finishedItem)->code ?: '—',
                                    'name' => optional(optional($g->first()->pickupLine)?->bundle?->finishedItem)->name ?: '',
                                    'qty' => (int) $g->sum(fn($l) => (int) ($l->qty_ok ?? 0)),
                                ])
                                ->sortByDesc('qty')
                                ->values();
                        };

                        // Helper: ambil pickup date dari return
                        $getPickupDate = function ($ret) {
                            $firstLine = $ret->lines->first();
                            return $firstLine?->pickupLine?->pickup?->date ?? null;
                        };
                    @endphp

                    {{-- DESKTOP TABLE --}}
                    <div class="table-wrap d-none d-md-block">
                        <table class="table table-sm align-middle mono">
                            <thead>
                                <tr>
                                    <th>Operator</th>
                                    <th style="width: 100px;">Tgl Ambil</th>
                                    <th style="width: 100px;">Tgl Setor</th>
                                    <th>Barang</th>
                                    <th style="width: 100px;">Status</th>
                                    <th style="width: 100px;"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($records as $ret)
                                    @php
                                        $lines = $ret->lines;
                                        $totalBundles = $lines->count();
                                        $qtyOk = (int) $lines->sum('qty_ok');
                                        $qtyReject = (int) $lines->sum('qty_reject');

                                        $firstLine = $lines->first();
                                        $pickup = $firstLine?->pickupLine?->pickup;
                                        $pickupDate = $getPickupDate($ret);

                                        $cfg = $sewStatusMap[$ret->status] ?? [strtoupper($ret->status ?? '-'), 'secondary'];
                                        $items = $sewItems($ret);

                                        $isVoided = !empty($ret->voided_at);
                                        $hasQc = \App\Models\QcResult::where('stage', \App\Models\QcResult::STAGE_SEWING)
                                            ->where('sewing_job_id', $ret->id)->exists();

                                        $detailHref = Route::has('production.sewing.returns.show')
                                            ? route('production.sewing.returns.show', $ret)
                                            : ($pickup && Route::has('production.sewing.pickups.show') ? route('production.sewing.pickups.show', $pickup) : null);
                                        $href = Route::has('production.qc.sewing.edit')
                                            ? route('production.qc.sewing.edit', $ret)
                                            : $detailHref;
                                    @endphp
                                    <tr class="{{ $href ? 'qc-row' : '' }}" @if ($href) data-href="{{ $href }}" @endif>
                                        <td>
                                            <div class="fw-semibold">{{ $ret->operator?->name ?? '-' }}</div>
                                            <div class="text-muted" style="font-size:.72rem;">{{ $ret->code }} · {{ $totalBundles }} bundle</div>
                                        </td>
                                        <td>
                                            <span class="text-muted" style="font-size:.82rem;">{{ $pickupDate?->format('d M Y') ?? '-' }}</span>
                                        </td>
                                        <td>
                                            <span style="font-size:.82rem;">{{ $ret->date?->format('d M Y') ?? '-' }}</span>
                                        </td>
                                        <td>
                                            @if ($items->isEmpty())
                                                <span class="text-muted small">-</span>
                                            @else
                                                <div class="item-chips">
                                                    @foreach ($items->take(4) as $it)
                                                        <span class="item-chip" title="{{ $it['name'] }}">
                                                            <b>{{ $it['code'] }}</b>
                                                            <span class="q">{{ number_format($it['qty'], 0, ',', '.') }}</span>
                                                        </span>
                                                    @endforeach
                                                    @if ($items->count() > 4)
                                                        <span class="item-chip item-chip-more">+{{ $items->count() - 4 }}</span>
                                                    @endif
                                                </div>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="d-flex flex-wrap gap-1">
                                                <span class="badge bg-{{ $cfg[1] }}">{{ $cfg[0] }}</span>
                                                @if ($qtyReject > 0)
                                                    <span class="badge bg-danger">R {{ $qtyReject }}</span>
                                                @endif
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex gap-1 flex-wrap">
                                            @if (Route::has('production.qc.sewing.edit'))
                                                <a href="{{ route('production.qc.sewing.edit', $ret) }}"
                                                   class="btn btn-sm {{ $hasQc ? 'btn-outline-secondary' : 'btn-outline-primary' }}"
                                                   onclick="event.stopPropagation();"
                                                   title="Form QC Jahit">
                                                   {{ $hasQc ? 'Lihat' : 'Input QC' }}
                                                </a>
                                            @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted small">
                                            Belum ada data QC Sewing.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{-- MOBILE LIST --}}
                    <div class="d-block d-md-none">
                        @if ($records->isEmpty())
                            <div class="text-center text-muted small py-2">Belum ada data QC Sewing.</div>
                        @else
                            <div class="qc-mobile-list mono">
                                @foreach ($records as $ret)
                                    @php
                                        $lines = $ret->lines;
                                        $totalBundles = $lines->count();
                                        $qtyOk = (int) $lines->sum('qty_ok');
                                        $qtyReject = (int) $lines->sum('qty_reject');

                                        $firstLine = $lines->first();
                                        $pickup = $firstLine?->pickupLine?->pickup;
                                        $pickupDate = $getPickupDate($ret);
                                        $cfg = $sewStatusMap[$ret->status] ?? [strtoupper($ret->status ?? '-'), 'secondary'];
                                        $items = $sewItems($ret);

                                        $isVoided = !empty($ret->voided_at);
                                        $hasQc = \App\Models\QcResult::where('stage', \App\Models\QcResult::STAGE_SEWING)
                                            ->where('sewing_job_id', $ret->id)->exists();

                                        $detailHref = Route::has('production.sewing.returns.show')
                                            ? route('production.sewing.returns.show', $ret)
                                            : ($pickup && Route::has('production.sewing.pickups.show') ? route('production.sewing.pickups.show', $pickup) : null);
                                        $href = Route::has('production.qc.sewing.edit')
                                            ? route('production.qc.sewing.edit', $ret)
                                            : $detailHref;
                                    @endphp
                                    <div class="qc-mobile-card" @if ($href) data-href="{{ $href }}" @endif>
                                        <div class="qc-mobile-card-header">
                                            <div>
                                                <div class="fw-semibold" style="font-size:.88rem;">{{ $ret->operator?->name ?? '-' }}</div>
                                                <div class="text-muted" style="font-size:.7rem;">{{ $ret->code }} · {{ $totalBundles }} bundle</div>
                                            </div>
                                            <div class="d-flex gap-1 align-items-center">
                                                <span class="badge qc-mobile-status-pill bg-{{ $cfg[1] }}">{{ $cfg[0] }}</span>
                                                @if ($qtyReject > 0)
                                                    <span class="badge qc-mobile-status-pill bg-danger">R {{ $qtyReject }}</span>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="qc-mobile-card-body">
                                            {{-- Tanggal Ambil & Setor --}}
                                            <div class="qc-mobile-row-line" style="font-size:.76rem; color:#64748b;">
                                                <span>Ambil {{ $pickupDate?->format('d M Y') ?? '-' }}</span>
                                                <span>Setor {{ $ret->date?->format('d M Y') ?? '-' }}</span>
                                            </div>

                                            {{-- Barang --}}
                                            @if ($items->isNotEmpty())
                                                <div class="item-chips" style="margin-top:.18rem;">
                                                    @foreach ($items->take(4) as $it)
                                                        <span class="item-chip" title="{{ $it['name'] }}">
                                                            <b>{{ $it['code'] }}</b>
                                                            <span class="q">{{ number_format($it['qty'], 0, ',', '.') }}</span>
                                                        </span>
                                                    @endforeach
                                                    @if ($items->count() > 4)
                                                        <span class="item-chip item-chip-more">+{{ $items->count() - 4 }}</span>
                                                    @endif
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    @if ($records instanceof \Illuminate\Pagination\AbstractPaginator)
                        <div class="mt-2">
                            {{ $records->links() }}
                        </div>
                    @endif

                    {{-- =======================
                     TAB QC PACKING
                ======================== --}}
                @elseif ($stage === 'packing')
                    <h2 class="h6 mb-2">Daftar QC Packing</h2>
                    <p class="small text-muted mb-0">
                        Modul QC Packing belum diimplementasikan. Nanti bisa mengikuti pola QC Sewing dengan model
                        <code>PackingReturn</code> atau sejenisnya.
                    </p>
                @endif
            </div>

        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Klik card mobile / baris desktop → ke halaman detail/QC
            document.querySelectorAll('.qc-mobile-card[data-href], .qc-row[data-href]').forEach(el => {
                el.addEventListener('click', function(e) {
                    if (e.target.closest('a,button')) return;
                    const href = this.getAttribute('data-href');
                    if (href) window.location.href = href;
                });
            });
        });
    </script>
@endpush
