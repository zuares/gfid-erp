{{-- resources/views/production/cutting_jobs/show.blade.php --}}
@extends('layouts.app')

@section('title', 'Produksi • Cutting Job ' . $job->code)

@push('head')
    <style>
        .page-wrap {
            max-width: 1100px;
            margin-inline: auto;
            padding: .75rem .75rem 4.5rem;
            /* space bawah untuk floating actions + bottom nav */
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

        .badge-soft {
            border-radius: 999px;
            padding: .2rem .6rem;
            font-size: .75rem;
        }

        @media (max-width: 767.98px) {
            .table-wrap {
                overflow-x: auto;
            }

            /* Ringkasan 1 baris di mobile */
            .summary-bar-mobile {
                font-size: .85rem;
                display: flex;
                flex-wrap: wrap;
                gap: .25rem .5rem;
            }

            .summary-bar-mobile span::after {
                content: "•";
                margin: 0 .25rem;
                color: var(--muted);
            }

            .summary-bar-mobile span:last-child::after {
                content: "";
                margin: 0;
            }
        }

        /* ============================
                   STATUS STEPPER DINAMIS
               ============================ */
        .status-stepper {
            display: flex;
            align-items: center;
            gap: .75rem;
            font-size: .78rem;
            margin-top: .35rem;
        }

        .status-step {
            display: flex;
            align-items: center;
            gap: .35rem;
        }

        .status-dot {
            width: 18px;
            height: 18px;
            border-radius: 999px;
            border: 2px solid rgba(148, 163, 184, 0.7);
            background: transparent;
        }

        .status-dot.active {
            background: #22c55e33;
            border-color: #22c55e;
            box-shadow: 0 0 0 1px #22c55e44;
        }

        .status-dot.current {
            background: #2563eb33;
            border-color: #2563eb;
            box-shadow: 0 0 0 1px #2563eb55;
        }

        .status-label {
            text-transform: uppercase;
            letter-spacing: .08em;
            font-size: .72rem;
            color: #6b7280;
        }

        .status-label.current {
            color: #2563eb;
            font-weight: 600;
        }

        .status-label.done {
            color: #16a34a;
            font-weight: 600;
        }

        .status-separator {
            flex: 0 0 26px;
            height: 1px;
            background: linear-gradient(to right, rgba(148, 163, 184, 0.7), transparent);
        }

        @media (max-width: 767.98px) {
            .status-stepper {
                flex-wrap: wrap;
                gap: .4rem .75rem;
            }

            .status-separator {
                display: none;
            }
        }

        /* ============================
                   DESKTOP ACTIONS STYLING
               ============================ */
        .cutting-actions-desktop .btn {
            border-radius: 999px;
        }

        .cutting-actions-desktop .btn-primary {
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            border: none;
            box-shadow: 0 10px 24px rgba(37, 99, 235, 0.35);
        }

        .cutting-actions-desktop .btn-primary:hover {
            filter: brightness(1.03);
        }

        .cutting-actions-desktop .btn-outline-primary {
            border-color: rgba(37, 99, 235, 0.45);
            color: #2563eb;
        }

        .cutting-actions-desktop .btn-outline-primary:hover {
            background: rgba(37, 99, 235, 0.06);
        }

        .cutting-actions-desktop .btn-outline-secondary {
            border-color: rgba(148, 163, 184, 0.7);
        }

        /* ============================
                   MOBILE FLOATING ACTIONS (RIGHT)
               ============================ */
        @media (max-width: 767.98px) {
            .cutting-mobile-actions {
                position: fixed;
                right: .9rem;
                bottom: calc(env(safe-area-inset-bottom, 0px) + 88px);
                /* di atas mobile bottom-nav */
                z-index: 1040;
                pointer-events: none;
                /* container tidak terima klik */
            }

            .cutting-mobile-actions-inner {
                pointer-events: auto;
                /* isi bisa di-klik */
                background: color-mix(in srgb, var(--card) 92%, rgba(15, 23, 42, 0.08));
                border-radius: 18px;
                box-shadow:
                    0 12px 28px rgba(15, 23, 42, 0.35),
                    0 0 0 1px rgba(148, 163, 184, 0.45);
                padding: .38rem .45rem;
                display: flex;
                gap: .3rem;
                align-items: center;
                backdrop-filter: blur(10px);
                max-width: 78vw;
            }

            .cutting-mobile-actions-inner .btn {
                border-radius: 999px;
                white-space: nowrap;
                flex: 0 0 auto;
                /* tidak full width */
            }

            .cutting-mobile-actions-inner .btn-primary {
                box-shadow: 0 6px 18px rgba(37, 99, 235, 0.45);
            }
        }

        /* ============================
                   BUNDLE INFO PILLS
               ============================ */
        .bundle-info-pill {
            font-size: .72rem;
            font-weight: 700;
            border-radius: 999px;
            padding: .16rem .55rem .18rem;
            box-shadow:
                0 1px 2px rgba(0, 0, 0, .08),
                inset 0 0 0 1px rgba(148, 163, 184, .25);
            background: var(--card);
            letter-spacing: .18px;
        }

        body[data-theme="dark"] .bundle-info-pill {
            background: rgba(15, 23, 42, 0.96);
            border-color: rgba(59, 130, 246, 0.35);
        }

        .pill-primary {
            color: #2563eb;
        }

        .pill-warning {
            color: #d97706;
        }

        .pill-success {
            color: #059669;
        }

        .bundle-info-wrap {
            display: inline-flex;
            flex-wrap: wrap;
            gap: .25rem;
        }

        @media (max-width: 767.98px) {
            .bundle-info-mobile {
                font-size: .72rem;
                color: var(--muted);
            }
        }

        /* ============================
                   BUNDLE PROGRESS BAR
               ============================ */
        .bundle-progress {
            margin-top: .18rem;
        }

        .bundle-progress-bar {
            position: relative;
            width: 100%;
            max-width: 220px;
            height: 6px;
            border-radius: 999px;
            background: rgba(148, 163, 184, 0.35);
            overflow: hidden;
        }

        .bp-picked,
        .bp-ready {
            position: absolute;
            top: 0;
            bottom: 0;
            left: 0;
        }

        .bp-picked {
            background: linear-gradient(to right, #facc15, #eab308);
            opacity: .85;
        }

        .bp-ready {
            background: linear-gradient(to right, #22c55e, #16a34a);
            opacity: .95;
        }

        .bundle-progress-legend {
            font-size: .68rem;
            color: var(--muted);
        }

        .legend-box {
            display: inline-block;
            width: 10px;
            height: 6px;
            border-radius: 999px;
            margin-right: .2rem;
        }

        .legend-picked {
            background: #eab308;
        }

        .legend-ready {
            background: #16a34a;
        }

        @media (max-width: 767.98px) {
            .bundle-progress-bar {
                max-width: 100%;
            }
        }
    </style>
@endpush

@section('content')
    @php
        // Ambil operator cutting dari bundle pertama
        $firstBundle = $job->bundles->first();
        $bundleOperator = $firstBundle?->operator;

        // Pakai flag dari controller, kalau tidak ada hitung sendiri
        $hasQcCutting = isset($hasQcCutting)
            ? $hasQcCutting
            : $job->bundles->contains(function ($b) {
                return $b->qcResults->where('stage', 'cutting')->isNotEmpty();
            });

        // Cari satu operator QC (ambil dari qc_results pertama yang ada)
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

        // Ringkasan cutting
        $totalBundles = $job->bundles->count();
        $totalQtyPcs = $job->bundles->sum('qty_pcs');
        $totalUsedFabric = $job->bundles->sum('qty_used_fabric');

        // Ringkasan QC (kalau sudah ada QC)
        $qcTotalOk = 0;
        $qcTotalReject = 0;

        if ($hasQcCutting) {
            foreach ($job->bundles as $b) {
                $qc = $b->qcResults->where('stage', 'cutting')->sortByDesc('qc_date')->first();

                if ($qc) {
                    $qcTotalOk += $qc->qty_ok ?? 0;
                    $qcTotalReject += $qc->qty_reject ?? 0;
                }
            }
        }

        // mapping status badge lama
        if ($hasQcCutting) {
            $statusMap = [
                'qc_done' => ['label' => 'QC CUTTING SELESAI', 'class' => 'info'],
                'sent_to_qc' => ['label' => 'SEDANG DI QC', 'class' => 'success'],
                'qc_mixed' => ['label' => 'QC MIXED', 'class' => 'warning'],
                'qc_reject' => ['label' => 'QC REJECT', 'class' => 'danger'],
            ];

            $cfg = $statusMap[$job->status] ?? ['label' => 'QC CUTTING', 'class' => 'info'];

            $statusLabel = $cfg['label'];
            $statusClass = $cfg['class'];
        } else {
            $statusLabel = strtoupper($job->status ?? 'draft');
            $statusClass =
                [
                    'draft' => 'secondary',
                    'cut' => 'primary',
                    'cut_sent_to_qc' => 'info',
                    'sent_to_qc' => 'info',
                    'posted' => 'primary',
                ][$job->status] ?? 'secondary';
        }

        // STEP DINAMIS CUTTING → KIRIM QC → QC CUTTING
        // step 1: Cutting, step 2: Dikirim ke QC, step 3: QC Selesai
        $status = $job->status;
        $stepCurrent = 1;

        if (in_array($status, ['cut', 'cut_sent_to_qc', 'sent_to_qc'])) {
            $stepCurrent = 2;
        }

        if ($hasQcCutting || in_array($status, ['qc_done', 'qc_ok', 'qc_mixed', 'qc_reject'])) {
            $stepCurrent = 3;
        }

        $step1State = $stepCurrent >= 1 ? ($stepCurrent === 1 ? 'current' : 'done') : '';
        $step2State = $stepCurrent >= 2 ? ($stepCurrent === 2 ? 'current' : 'done') : '';
        $step3State = $stepCurrent >= 3 ? ($stepCurrent === 3 ? 'current' : 'done') : '';
    @endphp

    <div class="page-wrap">

        {{-- =========================
             HEADER DESKTOP
        ========================== --}}
        <div class="card p-3 mb-3 d-none d-md-block">
            <div class="d-flex justify-content-between align-items-start gap-3">
                <div>
                    <h1 class="h5 mb-1">Cutting Job: {{ $job->code }}</h1>
                    <div class="help">
                        Tanggal: {{ $job->date?->format('Y-m-d') ?? $job->date }} •
                        Lot: {{ $job->lot?->code ?? '-' }} •
                        Gudang: {{ $job->warehouse?->code ?? '-' }}
                    </div>

                    {{-- STATUS STEPPER DINAMIS --}}
                    <div class="status-stepper">
                        <div class="status-step">
                            <div
                                class="status-dot {{ $step1State === 'current' ? 'current' : ($step1State === 'done' ? 'active' : '') }}">
                            </div>
                            <div
                                class="status-label {{ $step1State === 'current' ? 'current' : ($step1State === 'done' ? 'done' : '') }}">
                                Cutting
                            </div>
                        </div>
                        <div class="status-separator"></div>
                        <div class="status-step">
                            <div
                                class="status-dot {{ $step2State === 'current' ? 'current' : ($step2State === 'done' ? 'active' : '') }}">
                            </div>
                            <div
                                class="status-label {{ $step2State === 'current' ? 'current' : ($step2State === 'done' ? 'done' : '') }}">
                                Kirim ke QC
                            </div>
                        </div>
                        <div class="status-separator"></div>
                        <div class="status-step">
                            <div
                                class="status-dot {{ $step3State === 'current' ? 'current' : ($step3State === 'done' ? 'active' : '') }}">
                            </div>
                            <div
                                class="status-label {{ $step3State === 'current' ? 'current' : ($step3State === 'done' ? 'done' : '') }}">
                                QC Cutting
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex flex-column align-items-end gap-2">
                    <span class="badge bg-{{ $statusClass }} px-3 py-2">
                        {{ $statusLabel }}
                    </span>

                    <div class="d-flex gap-2 cutting-actions-desktop">
                        <a href="{{ route('production.cutting_jobs.index') }}" class="btn btn-sm btn-outline-secondary">
                            Kembali
                        </a>

                        {{-- ACTION DINAMIS BERDASARKAN STATUS --}}
                        @if (!$hasQcCutting)
                            @if (in_array($job->status, ['draft', 'cut']))
                                {{-- Belum QC, belum dikirim ke QC --}}
                                <a href="{{ route('production.cutting_jobs.edit', $job) }}"
                                    class="btn btn-sm btn-outline-primary">
                                    Edit Cutting
                                </a>

                                <form action="{{ route('production.cutting_jobs.send_to_qc', $job) }}" method="post"
                                    class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-primary">
                                        Kirim ke QC Cutting
                                    </button>
                                </form>
                            @elseif (in_array($job->status, ['cut_sent_to_qc', 'sent_to_qc']))
                                {{-- SUDAH DIKIRIM KE QC TAPI BELUM ADA INPUT QC --}}
                                @if (Route::has('production.qc.cutting.edit'))
                                    <a href="{{ route('production.qc.cutting.edit', $job) }}"
                                        class="btn btn-sm btn-primary">
                                        Input QC Cutting
                                    </a>
                                @else
                                    <button class="btn btn-sm btn-warning" disabled>
                                        Menunggu hasil QC…
                                    </button>
                                @endif
                            @else
                                <button class="btn btn-sm btn-warning" disabled>
                                    Menunggu proses QC…
                                </button>
                            @endif
                        @else
                            {{-- SUDAH ADA QC CUTTING --}}
                            @if (Route::has('production.qc.cutting.edit'))
                                <a href="{{ route('production.qc.cutting.edit', $job) }}" class="btn btn-sm btn-primary">
                                    Lihat / Edit QC Cutting
                                </a>
                            @else
                                <button class="btn btn-sm btn-primary" disabled>
                                    QC Cutting Tersimpan
                                </button>
                            @endif
                        @endif
                    </div>

                </div>
            </div>
        </div>

        {{-- =========================
             HEADER MOBILE (minimalis)
        ========================== --}}
        <div class="card p-2 mb-2 d-block d-md-none">
            <div class="d-flex justify-content-between align-items-center mb-1">
                <div>
                    <div class="small text-muted">Cutting Job</div>
                    <div class="fw-semibold mono">{{ $job->code }}</div>
                </div>
                <span class="badge bg-{{ $statusClass }} px-2 py-1">
                    {{ $statusLabel }}
                </span>
            </div>

            <div class="help mb-2">
                {{ $job->date?->format('Y-m-d') ?? $job->date }} •
                Lot {{ $job->lot?->code ?? '-' }} •
                {{ $job->warehouse?->code ?? '-' }}
            </div>

            {{-- Status stepper versi singkat --}}
            <div class="status-stepper mb-2">
                <div class="status-step">
                    <div
                        class="status-dot {{ $step1State === 'current' ? 'current' : ($step1State === 'done' ? 'active' : '') }}">
                    </div>
                    <div
                        class="status-label {{ $step1State === 'current' ? 'current' : ($step1State === 'done' ? 'done' : '') }}">
                        Cutting
                    </div>
                </div>
                <div class="status-step">
                    <div
                        class="status-dot {{ $step2State === 'current' ? 'current' : ($step2State === 'done' ? 'active' : '') }}">
                    </div>
                    <div
                        class="status-label {{ $step2State === 'current' ? 'current' : ($step2State === 'done' ? 'done' : '') }}">
                        Kirim QC
                    </div>
                </div>
                <div class="status-step">
                    <div
                        class="status-dot {{ $step3State === 'current' ? 'current' : ($step3State === 'done' ? 'active' : '') }}">
                    </div>
                    <div
                        class="status-label {{ $step3State === 'current' ? 'current' : ($step3State === 'done' ? 'done' : '') }}">
                        QC Cutting
                    </div>
                </div>
            </div>

            {{-- Tombol kembali di header --}}
            <div class="d-flex gap-2 flex-wrap">
                <a href="{{ route('production.cutting_jobs.index') }}" class="btn btn-sm btn-outline-secondary flex-fill">
                    Kembali
                </a>
            </div>
        </div>

        {{-- =========================
             INFORMASI LOT & OPERATOR
        ========================== --}}
        <div class="card p-3 mb-3">
            <h2 class="h6 mb-2 d-none d-md-block">Informasi Lot & Operator</h2>

            <div class="row g-3">
                <div class="col-md-3 col-12">
                    <div class="help mb-1">LOT</div>
                    <div class="fw-semibold">
                        {{ $job->lot?->code ?? '-' }}
                    </div>
                    <div class="small text-muted">
                        {{ $job->lot?->item?->code ?? '-' }}
                    </div>
                </div>

                <div class="col-md-3 col-6">
                    <div class="help mb-1">Gudang</div>
                    <div class="mono">
                        {{ $job->warehouse?->code }} — {{ $job->warehouse?->name }}
                    </div>
                </div>

                <div class="col-md-3 col-6">
                    <div class="help mb-1">Operator Cutting</div>
                    <div class="mono">
                        {{ $bundleOperator?->code ? $bundleOperator->code . ' — ' . $bundleOperator->name : '-' }}
                    </div>
                </div>

                <div class="col-md-3 col-6">
                    <div class="help mb-1">Operator QC Cutting</div>
                    <div class="mono">
                        {{ $qcOperator?->code ? $qcOperator->code . ' — ' . $qcOperator->name : '-' }}
                    </div>
                </div>
            </div>

            @if ($job->notes)
                <div class="mt-2 text-muted small">
                    Catatan: {{ $job->notes }}
                </div>
            @endif
        </div>

        {{-- =========================
             RINGKASAN DESKTOP
        ========================== --}}
        <div class="card p-3 mb-3 d-none d-md-block">
            <h2 class="h6 mb-2">Ringkasan Output</h2>

            <div class="row g-3">
                <div class="col-md-3 col-6">
                    <div class="help mb-1">Jumlah Bundle</div>
                    <div class="mono">{{ $totalBundles }}</div>
                </div>

                <div class="col-md-3 col-6">
                    <div class="help mb-1">Total Qty Cutting (pcs)</div>
                    <div class="mono">
                        {{ number_format($totalQtyPcs, 2, ',', '.') }}
                    </div>
                </div>

                <div class="col-md-3 col-6">
                    <div class="help mb-1">Total Pemakaian Kain</div>
                    <div class="mono">
                        {{ number_format($totalUsedFabric, 2, ',', '.') }}
                    </div>
                </div>

                @if ($hasQcCutting)
                    <div class="col-md-3 col-6">
                        <div class="help mb-1">Total QC (OK / Reject)</div>
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

        {{-- =========================
             RINGKASAN MOBILE (1 baris)
        ========================== --}}
        <div class="card p-2 mb-3 d-block d-md-none">
            <div class="summary-bar-mobile">
                <span>{{ $totalBundles }} bundle</span>
                <span>{{ number_format($totalQtyPcs, 0, ',', '.') }} pcs</span>
                <span>{{ number_format($totalUsedFabric, 2, ',', '.') }} Kg kain</span>
                @if ($hasQcCutting)
                    <span>QC OK {{ number_format($qcTotalOk, 0, ',', '.') }}</span>
                    <span class="{{ $qcTotalReject > 0 ? 'text-danger fw-semibold' : '' }}">
                        Reject {{ number_format($qcTotalReject, 0, ',', '.') }}
                    </span>
                @endif
            </div>
        </div>

        {{-- =========================
             TABEL BUNDLES DESKTOP
        ========================== --}}
        <div class="card p-3 mb-4 d-none d-md-block">
            <h2 class="h6 mb-2">Detail Bundles</h2>

            <div class="table-wrap">
                <table class="table table-sm align-middle mono">
                    <thead>
                        @if ($hasQcCutting)
                            <tr>
                                <th style="width:60px;">#</th>
                                <th style="width:160px;">Bundle Code</th>
                                <th style="width:160px;">Item Jadi</th>
                                <th style="width:110px;">Cutting (Qty)</th>
                                <th style="width:110px;">Cutting (Reject)</th>
                                <th style="width:110px;">Cutting (Ok)</th>
                                <th style="width:260px;">WIP / Sewing</th>
                            </tr>
                        @else
                            <tr>
                                <th style="width:60px;">#</th>
                                <th style="width:160px;">Bundle Code</th>
                                <th style="width:160px;">Item Jadi</th>
                                <th style="width:110px;">Qty (pcs)</th>
                                <th style="width:140px;">Qty Used Fabric</th>
                                <th style="width:260px;">WIP / Sewing</th>
                            </tr>
                        @endif
                    </thead>
                    <tbody>
                        @forelse ($job->bundles as $row)
                            @php
                                $qc = null;
                                if ($hasQcCutting) {
                                    $qc = $row->qcResults->where('stage', 'cutting')->sortByDesc('qc_date')->first();
                                }

                                $wip = (float) ($row->wip_qty ?? 0);
                                $picked = (float) ($row->sewing_picked_qty ?? 0);

                                // gunakan accessor kalau ada, fallback ke qc/qty_pcs
                                $qtyOkAccessor = $row->qty_cutting_ok ?? null;
                                if ($qtyOkAccessor === null) {
                                    $qtyOkAccessor = $qc?->qty_ok ?? ($row->qty_pcs ?? 0);
                                }
                                $qtyOk = (float) $qtyOkAccessor;

                                $readyAccessor = $row->qty_ready_for_sewing ?? null;
                                if ($readyAccessor === null) {
                                    $readyAccessor = max(0, min($qtyOk, $wip) - $picked);
                                }
                                $ready = (float) $readyAccessor;

                                $basis = max($qtyOk, $wip, $picked, $ready);

                                if ($basis <= 0) {
                                    $pickedPercent = 0;
                                    $readyPercent = 0;
                                } else {
                                    $pickedPercent = max(0, min(100, ($picked / $basis) * 100));
                                    $readyPercent = max(0, min(100, ($ready / $basis) * 100));
                                }
                            @endphp

                            @if ($hasQcCutting)
                                <tr class="{{ ($qc?->qty_reject ?? 0) > 0 ? 'table-danger-subtle' : '' }}">
                                    <td>{{ $row->bundle_no }}</td>
                                    <td>{{ $row->bundle_code }}</td>
                                    <td>{{ $row->finishedItem?->code ?? '-' }}</td>
                                    <td>{{ number_format($row->qty_pcs, 2, ',', '.') }}</td>
                                    <td class="{{ ($qc?->qty_reject ?? 0) > 0 ? 'text-danger fw-semibold' : '' }}">
                                        {{ $qc ? number_format($qc->qty_reject ?? 0, 2, ',', '.') : '0,00' }}
                                    </td>
                                    <td>
                                        {{ $qc ? number_format($qc->qty_ok ?? 0, 2, ',', '.') : '0,00' }}
                                    </td>
                                    <td>
                                        <div class="bundle-info-wrap mb-1">
                                            <span class="bundle-info-pill pill-primary">
                                                WIP {{ number_format($wip, 2, ',', '.') }}
                                            </span>
                                            <span class="bundle-info-pill pill-warning">
                                                Picked {{ number_format($picked, 2, ',', '.') }}
                                            </span>
                                            <span class="bundle-info-pill pill-success">
                                                Ready {{ number_format($ready, 2, ',', '.') }}
                                            </span>
                                        </div>

                                        @if ($basis > 0)
                                            <div class="bundle-progress">
                                                <div class="bundle-progress-bar">
                                                    <div class="bp-picked"
                                                        style="width: {{ number_format($pickedPercent, 2, '.', '') }}%;">
                                                    </div>
                                                    <div class="bp-ready"
                                                        style="width: {{ number_format($readyPercent, 2, '.', '') }}%;">
                                                    </div>
                                                </div>
                                                <div class="bundle-progress-legend mt-1">
                                                    <span class="me-2">
                                                        <span class="legend-box legend-picked"></span>Picked
                                                    </span>
                                                    <span>
                                                        <span class="legend-box legend-ready"></span>Ready
                                                    </span>
                                                </div>
                                            </div>
                                        @endif
                                    </td>
                                </tr>
                            @else
                                <tr>
                                    <td>{{ $row->bundle_no }}</td>
                                    <td>{{ $row->bundle_code }}</td>
                                    <td>{{ $row->finishedItem?->code ?? '-' }}</td>
                                    <td>{{ number_format($row->qty_pcs, 2, ',', '.') }}</td>
                                    <td>{{ number_format($row->qty_used_fabric ?? 0, 2, ',', '.') }}</td>
                                    <td>
                                        <div class="bundle-info-wrap mb-1">
                                            <span class="bundle-info-pill pill-primary">
                                                WIP {{ number_format($wip, 2, ',', '.') }}
                                            </span>
                                            <span class="bundle-info-pill pill-warning">
                                                Picked {{ number_format($picked, 2, ',', '.') }}
                                            </span>
                                            <span class="bundle-info-pill pill-success">
                                                Ready {{ number_format($ready, 2, ',', '.') }}
                                            </span>
                                        </div>

                                        @if ($basis > 0)
                                            <div class="bundle-progress">
                                                <div class="bundle-progress-bar">
                                                    <div class="bp-picked"
                                                        style="width: {{ number_format($pickedPercent, 2, '.', '') }}%;">
                                                    </div>
                                                    <div class="bp-ready"
                                                        style="width: {{ number_format($readyPercent, 2, '.', '') }}%;">
                                                    </div>
                                                </div>
                                                <div class="bundle-progress-legend mt-1">
                                                    <span class="me-2">
                                                        <span class="legend-box legend-picked"></span>Picked
                                                    </span>
                                                    <span>
                                                        <span class="legend-box legend-ready"></span>Ready
                                                    </span>
                                                </div>
                                            </div>
                                        @endif
                                    </td>
                                </tr>
                            @endif
                        @empty
                            <tr>
                                <td colspan="{{ $hasQcCutting ? 7 : 6 }}" class="text-center text-muted small">
                                    Belum ada data bundle.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- =========================
             TABEL BUNDLES MOBILE
        ========================== --}}
        <div class="card p-3 mb-4 d-block d-md-none">
            <h2 class="h6 mb-2">Detail Bundles</h2>

            <div class="table-wrap">
                <table class="table table-sm align-middle mono">
                    <thead>
                        @if ($hasQcCutting)
                            {{-- MOBILE: sudah QC → tampilkan kode + hasil OK/Reject --}}
                            <tr>
                                <th style="width:50px;">#</th>
                                <th>Kode Barang</th>
                                <th style="width:80px;">OK</th>
                                <th style="width:80px;">Reject</th>
                            </tr>
                        @else
                            {{-- MOBILE: BELUM QC → #, Kode Barang, Hasil (Cutting) --}}
                            <tr>
                                <th style="width:50px;">#</th>
                                <th>Kode Barang</th>
                                <th style="width:110px;">Hasil (Cutting)</th>
                            </tr>
                        @endif
                    </thead>
                    <tbody>
                        @forelse ($job->bundles as $row)
                            @php
                                $qc = null;
                                if ($hasQcCutting) {
                                    $qc = $row->qcResults->where('stage', 'cutting')->sortByDesc('qc_date')->first();
                                }

                                $wipM = (float) ($row->wip_qty ?? 0);
                                $pickedM = (float) ($row->sewing_picked_qty ?? 0);

                                $qtyOkAccM = $row->qty_cutting_ok ?? null;
                                if ($qtyOkAccM === null) {
                                    $qtyOkAccM = $qc?->qty_ok ?? ($row->qty_pcs ?? 0);
                                }
                                $qtyOkM = (float) $qtyOkAccM;

                                $readyAccM = $row->qty_ready_for_sewing ?? null;
                                if ($readyAccM === null) {
                                    $readyAccM = max(0, min($qtyOkM, $wipM) - $pickedM);
                                }
                                $readyM = (float) $readyAccM;

                                $basisM = max($qtyOkM, $wipM, $pickedM, $readyM);

                                if ($basisM <= 0) {
                                    $pickedPercentM = 0;
                                    $readyPercentM = 0;
                                } else {
                                    $pickedPercentM = max(0, min(100, ($pickedM / $basisM) * 100));
                                    $readyPercentM = max(0, min(100, ($readyM / $basisM) * 100));
                                }
                            @endphp

                            @if ($hasQcCutting)
                                <tr class="{{ ($qc?->qty_reject ?? 0) > 0 ? 'table-danger-subtle' : '' }}">
                                    <td>{{ $row->bundle_no }}</td>
                                    <td>
                                        {{ $row->finishedItem?->code ?? '-' }}
                                        <div class="bundle-info-mobile mt-1">
                                            WIP {{ number_format($wipM, 0, ',', '.') }}
                                            • Pick {{ number_format($pickedM, 0, ',', '.') }}
                                            • Ready {{ number_format($readyM, 0, ',', '.') }}
                                        </div>
                                        @if ($basisM > 0)
                                            <div class="bundle-progress mt-1">
                                                <div class="bundle-progress-bar">
                                                    <div class="bp-picked"
                                                        style="width: {{ number_format($pickedPercentM, 2, '.', '') }}%;">
                                                    </div>
                                                    <div class="bp-ready"
                                                        style="width: {{ number_format($readyPercentM, 2, '.', '') }}%;">
                                                    </div>
                                                </div>
                                            </div>
                                        @endif
                                    </td>
                                    <td>{{ $qc ? number_format($qc->qty_ok ?? 0, 0, ',', '.') : '0' }}</td>
                                    <td class="{{ ($qc?->qty_reject ?? 0) > 0 ? 'text-danger fw-semibold' : '' }}">
                                        {{ $qc ? number_format($qc->qty_reject ?? 0, 0, ',', '.') : '0' }}
                                    </td>
                                </tr>
                            @else
                                <tr>
                                    <td>{{ $row->bundle_no }}</td>
                                    <td>
                                        {{ $row->finishedItem?->code ?? '-' }}
                                        <div class="bundle-info-mobile mt-1">
                                            WIP {{ number_format($wipM, 0, ',', '.') }}
                                            • Pick {{ number_format($pickedM, 0, ',', '.') }}
                                            • Ready {{ number_format($readyM, 0, ',', '.') }}
                                        </div>
                                        @if ($basisM > 0)
                                            <div class="bundle-progress mt-1">
                                                <div class="bundle-progress-bar">
                                                    <div class="bp-picked"
                                                        style="width: {{ number_format($pickedPercentM, 2, '.', '') }}%;">
                                                    </div>
                                                    <div class="bp-ready"
                                                        style="width: {{ number_format($readyPercentM, 2, '.', '') }}%;">
                                                    </div>
                                                </div>
                                            </div>
                                        @endif
                                    </td>
                                    <td>{{ number_format($row->qty_pcs, 0, ',', '.') }}</td>
                                </tr>
                            @endif
                        @empty
                            <tr>
                                <td colspan="{{ $hasQcCutting ? 4 : 3 }}" class="text-center text-muted small">
                                    Belum ada data bundle.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- =========================
             MOBILE: AKSI FLOATING DI KANAN BAWAH
        ========================== --}}
        @if (!$hasQcCutting)
            <div class="cutting-mobile-actions d-block d-md-none">
                <div class="cutting-mobile-actions-inner">
                    @if (in_array($job->status, ['draft', 'cut']))
                        <a href="{{ route('production.cutting_jobs.edit', $job) }}"
                            class="btn btn-sm btn-outline-primary">
                            Edit
                        </a>

                        <form action="{{ route('production.cutting_jobs.send_to_qc', $job) }}" method="post">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-primary">
                                Kirim QC
                            </button>
                        </form>
                    @elseif (in_array($job->status, ['cut_sent_to_qc', 'sent_to_qc']))
                        @if (Route::has('production.qc.cutting.edit'))
                            <a href="{{ route('production.qc.cutting.edit', $job) }}" class="btn btn-sm btn-primary">
                                Input QC
                            </a>
                        @else
                            <button type="button" class="btn btn-sm btn-warning" disabled>
                                Menunggu QC…
                            </button>
                        @endif
                    @else
                        <button type="button" class="btn btn-sm btn-warning" disabled>
                            Menunggu proses QC…
                        </button>
                    @endif
                </div>
            </div>
        @endif

    </div>
@endsection

@push('scripts')
    {{-- Tidak perlu JS khusus --}}
@endpush
