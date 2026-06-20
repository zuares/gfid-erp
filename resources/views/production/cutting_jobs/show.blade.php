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

        @media (max-width:767.98px) {
            .table-wrap {
                overflow-x: auto
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

        /* pills + progress */
        .bundle-info-pill {
            font-size: .72rem;
            font-weight: 700;
            border-radius: 999px;
            padding: .16rem .55rem .18rem;
            box-shadow: 0 1px 2px rgba(0, 0, 0, .08), inset 0 0 0 1px rgba(148, 163, 184, .25);
            background: var(--card);
            letter-spacing: .18px
        }

        body[data-theme="dark"] .bundle-info-pill {
            background: rgba(15, 23, 42, .96);
            border-color: rgba(59, 130, 246, .35)
        }

        .pill-primary {
            color: #2563eb
        }

        .pill-warning {
            color: #d97706
        }

        .pill-success {
            color: #059669
        }

        .bundle-info-wrap {
            display: inline-flex;
            flex-wrap: wrap;
            gap: .25rem
        }

        @media (max-width:767.98px) {
            .bundle-info-mobile {
                font-size: .72rem;
                color: var(--muted)
            }
        }

        .bundle-progress {
            margin-top: .18rem
        }

        .bundle-progress-bar {
            position: relative;
            width: 100%;
            max-width: 220px;
            height: 6px;
            border-radius: 999px;
            background: rgba(148, 163, 184, .35);
            overflow: hidden
        }

        .bp-picked,
        .bp-ready {
            position: absolute;
            top: 0;
            bottom: 0;
            left: 0
        }

        .bp-picked {
            background: linear-gradient(to right, #facc15, #eab308);
            opacity: .85
        }

        .bp-ready {
            background: linear-gradient(to right, #22c55e, #16a34a);
            opacity: .95
        }

        @media (max-width:767.98px) {
            .bundle-progress-bar {
                max-width: 100%
            }
        }

        .bundle-progress-legend {
            font-size: .68rem;
            color: var(--muted)
        }

        .legend-box {
            display: inline-block;
            width: 10px;
            height: 6px;
            border-radius: 999px;
            margin-right: .2rem
        }

        .legend-picked {
            background: #eab308
        }

        .legend-ready {
            background: #16a34a
        }
    </style>
@endpush

@section('content')
    @php
        // ========= FLAGS =========
        $userRole = auth()->user()->role ?? null;
        $isOwner = $userRole === 'owner';
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
        if ($hasQcCutting || in_array($status, ['qc_done', 'qc_ok', 'qc_mixed', 'qc_reject'], true)) {
            $stepCurrent = 3;
        }

        $dotClass = fn($n) => $stepCurrent === $n ? 'current' : ($stepCurrent > $n ? 'active' : '');
        $lblClass = fn($n) => $stepCurrent === $n ? 'current' : ($stepCurrent > $n ? 'done' : '');
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
            ) {
                $btn = $isMobile ? 'btn btn-sm' : 'btn btn-sm';
                $wrapClass = $isMobile ? '' : 'cutting-actions';

                echo '<div class="d-flex gap-2 flex-wrap ' . e($wrapClass) . '">';

                // Overproduction (hanya jika QC sudah ada)
                if ($canOverproduction) {
                    echo '<a href="' .
                        e(route('production.cutting_overproduction.create', ['cutting_job_id' => $job->id])) .
                        '"
                            class="' .
                        e($btn) .
                        ' btn-outline-warning"
                            onclick="return confirm(\'Cutting Overproduction?\\n\\nHanya untuk MENAMBAH qty (WIP-CUT).\')">
                            Overproduction
                          </a>';
                }

                // Edit Cutting (boleh sampai sent_to_qc selama QC belum ada)
                if ($canEditCutting) {
                    echo '<a href="' .
                        e(route('production.cutting_jobs.edit', $job)) .
                        '"
                            class="' .
                        e($btn) .
                        ' btn-outline-primary"
                            onclick="return confirm(\'Edit Cutting?\\n\\nStatus: ' .
                        e($status) .
                        '\\nPastikan QC belum diinput.\')">
                            Edit Cutting
                          </a>';
                }

                // Kirim ke QC (draft/cut)
                if ($canSendToQc) {
                    echo '<form action="' .
                        e(route('production.cutting_jobs.send_to_qc', $job)) .
                        '" method="post" class="d-inline">';
                    echo csrf_field();
                    echo '<button type="submit" class="' . e($btn) . ' btn-primary">Kirim QC</button>';
                    echo '</form>';
                }

                // Input/lihat QC (saat terkirim / atau QC sudah ada)
                if ($canInputQc) {
                    $label = $hasQcCutting ? 'Lihat / Edit QC' : 'Input QC';
                    echo '<a href="' .
                        e(route('production.qc.cutting.edit', $job)) .
                        '" class="' .
                        e($btn) .
                        ' btn-primary">' .
                        $label .
                        '</a>';
                }

                if ($canQuickOkCutting) {
                    echo '<form action="' .
                        e(route('production.qc.cutting.quick_ok', $job)) .
                        '" method="post" class="d-inline"
                            onsubmit="return confirm(\'Selesai Cutting & Siap Jahit?\\n\\nSemua bundle akan dianggap OK dan langsung masuk WIP-CUT.\')">';
                    echo csrf_field();
                    echo '<button type="submit" class="' . e($btn) . ' btn-success">Selesai Cutting & Siap Jahit</button>';
                    echo '</form>';
                }

                // Cancel QC (owner + QC ada)
                if ($canCancelQc) {
                    echo '<form action="' .
                        e(route('production.qc.cutting.cancel', $job)) .
                        '" method="post" class="d-inline"
                            onsubmit="return confirm(\'Batalkan QC Cutting?\\n\\nJika sudah dipakai sewing, aksi ini akan ditolak.\')">';
                    echo csrf_field();
                    echo '<button type="submit" class="' . e($btn) . ' btn-outline-danger">Batalkan QC</button>';
                    echo '</form>';
                }

                // Void Cutting Job (owner, belum QC, belum sewing)
                if ($canVoid) {
                    echo '<form action="' .
                        e(route('production.cutting_jobs.void', $job)) .
                        '" method="post" class="d-inline"
                            onsubmit="return confirm(\'⚠️ VOID Cutting Job ' . e($job->code) . '?\\n\\nStok kain akan dikembalikan ke LOT semula.\\nAksi ini tidak bisa dibatalkan.\')">';
                    echo csrf_field();
                    echo '<button type="submit" class="' . e($btn) . ' btn-outline-danger">🗑 Void</button>';
                    echo '</form>';
                }

                echo '</div>';
            };
        @endphp

        {{-- ===========================
            HEADER (DESKTOP)
        ============================ --}}
        <div class="card p-3 mb-3 d-none d-md-block">
            <div class="d-flex justify-content-between align-items-start gap-3">
                <div>
                    <h1 class="h5 mb-1">Cutting Job: {{ $job->code }}</h1>
                    <div class="help">
                        Tanggal: {{ $job->date?->format('Y-m-d') ?? $job->date }} •
                        Lot: {{ $job->lot?->code ?? '-' }} •
                        Gudang: {{ $job->warehouse?->code ?? '-' }}
                    </div>

                    <div class="status-stepper">
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

                <div class="d-flex flex-column align-items-end gap-2">
                    <span class="badge bg-{{ $statusClass }} px-3 py-2">{{ $statusLabel }}</span>

                    <div class="d-flex gap-2 flex-wrap justify-content-end cutting-actions">
                        <a href="{{ route('production.cutting_jobs.index') }}" class="btn btn-sm btn-outline-secondary">
                            Kembali
                        </a>
                        {!! $renderActions(false) !!}
                    </div>

                    @if ($hasQcCutting && $userRole !== 'owner')
                        <div class="help text-end" style="max-width:420px;">
                            QC sudah tersimpan. Jika ada salah input setelah QC done, minta <b>OWNER</b> untuk:
                            <b>Batalkan QC</b> (jika belum dipakai) atau gunakan <b>Overproduction</b> (jika perlu menambah
                            OK).
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- ===========================
            HEADER (MOBILE)
        ============================ --}}
        <div class="card p-2 mb-2 d-block d-md-none">
            <div class="d-flex justify-content-between align-items-center mb-1">
                <div>
                    <div class="small text-muted">Cutting Job</div>
                    <div class="fw-semibold mono">{{ $job->code }}</div>
                </div>
                <span class="badge bg-{{ $statusClass }} px-2 py-1">{{ $statusLabel }}</span>
            </div>

            <div class="help mb-2">
                {{ $job->date?->format('Y-m-d') ?? $job->date }} •
                Lot {{ $job->lot?->code ?? '-' }} •
                {{ $job->warehouse?->code ?? '-' }}
            </div>

            <div class="status-stepper mb-2">
                <div class="status-step">
                    <div class="status-dot {{ $dotClass(1) }}"></div>
                    <div class="status-label {{ $lblClass(1) }}">Cutting</div>
                </div>
                <div class="status-step">
                    <div class="status-dot {{ $dotClass(2) }}"></div>
                    <div class="status-label {{ $lblClass(2) }}">Input QC</div>
                </div>
                <div class="status-step">
                    <div class="status-dot {{ $dotClass(3) }}"></div>
                    <div class="status-label {{ $lblClass(3) }}">QC Cutting</div>
                </div>
            </div>

            <div class="d-flex gap-2 flex-wrap cutting-actions">
                <a href="{{ route('production.cutting_jobs.index') }}" class="btn btn-sm btn-outline-secondary flex-fill">
                    Kembali
                </a>
                {{-- actions utama (tidak perlu flex-fill biar tidak kepotong) --}}
                {!! $renderActions(true) !!}
            </div>
        </div>

        {{-- ===========================
            INFO LOT & OPERATOR
        ============================ --}}
        <div class="card p-3 mb-3">
            <h2 class="h6 mb-2 d-none d-md-block">Informasi Lot & Operator</h2>

            <div class="row g-3">
                <div class="col-md-3 col-12">
                    <div class="help mb-1">LOT</div>
                    <div class="fw-semibold">{{ $job->lot?->code ?? '-' }}</div>
                    <div class="small text-muted">{{ $job->lot?->item?->code ?? '-' }}</div>
                </div>

                <div class="col-md-3 col-6">
                    <div class="help mb-1">Gudang</div>
                    <div class="mono">{{ $job->warehouse?->code }} — {{ $job->warehouse?->name }}</div>
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

            @if (!empty($job->notes))
                <div class="mt-2 text-muted small">Catatan: {{ $job->notes }}</div>
            @endif

            @if ($canOverproduction)
                <div class="mt-2 alert alert-warning py-2 px-3 mb-0" style="font-size:.84rem;">
                    <b>Cutting Overproduction</b> dipakai jika produksi real <i>lebih banyak</i> dari hasil QC/OK.
                    Ini akan menambah stok <b>WIP-CUT</b> dan tercatat sebagai <b>Inventory Adjustment</b>.
                </div>
            @endif
        </div>

        {{-- ===========================
            SUMMARY
        ============================ --}}
        <div class="card p-3 mb-3 d-none d-md-block">
            <h2 class="h6 mb-2">Ringkasan Output</h2>

            <div class="row g-3">
                <div class="col-md-3 col-6">
                    <div class="help mb-1">Jumlah Bundle</div>
                    <div class="mono">{{ $totalBundles }}</div>
                </div>

                <div class="col-md-3 col-6">
                    <div class="help mb-1">Total Qty Cutting (pcs)</div>
                    <div class="mono">{{ number_format($totalQtyPcs, 2, ',', '.') }}</div>
                </div>

                <div class="col-md-3 col-6">
                    <div class="help mb-1">Total Pemakaian Kain</div>
                    <div class="mono">{{ number_format($totalUsedFabric, 2, ',', '.') }}</div>
                </div>

                @if ($hasQcCutting)
                    <div class="col-md-3 col-6">
                        <div class="help mb-1">Total OK Produksi / Reject QC</div>
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

        <div class="card p-2 mb-3 d-block d-md-none">
            <div class="summary-bar-mobile">
                <span>{{ $totalBundles }} bundle</span>
                <span>{{ number_format($totalQtyPcs, 0, ',', '.') }} pcs</span>
                <span>{{ number_format($totalUsedFabric, 2, ',', '.') }} Kg kain</span>
                @if ($hasQcCutting)
                    <span>OK {{ number_format($qcTotalOk, 0, ',', '.') }}</span>
                    <span class="{{ $qcTotalReject > 0 ? 'text-danger fw-semibold' : '' }}">
                        Reject {{ number_format($qcTotalReject, 0, ',', '.') }}
                    </span>
                @endif
            </div>
        </div>

        {{-- ===========================
            DETAIL TABLES (biarkan seperti kamu punya)
        ============================ --}}
        {{-- === DESKTOP TABLE === --}}
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
                                <th style="width:110px;">Cutting Qty</th>
                                <th style="width:110px;">Reject</th>
                                <th style="width:110px;">OK (Basis)</th>
                                <th style="width:260px;">WIP / Sewing</th>
                            </tr>
                        @else
                            <tr>
                                <th style="width:60px;">#</th>
                                <th style="width:160px;">Bundle Code</th>
                                <th style="width:160px;">Item Jadi</th>
                                <th style="width:110px;">Qty (pcs)</th>
                                <th style="width:140px;">Used Fabric</th>
                                <th style="width:260px;">WIP / Sewing</th>
                            </tr>
                        @endif
                    </thead>
                    <tbody>
                        @forelse ($job->bundles as $row)
                            @php
                                $qc = $hasQcCutting
                                    ? $row->qcResults->where('stage', 'cutting')->sortByDesc('qc_date')->first()
                                    : null;

                                $wip = (float) ($row->cut_wip_qty ?? 0);
                                $picked = (float) ($row->sewing_picked_qty ?? 0);

                                $qtyOkAccessor = $row->qty_cutting_ok ?? null;
                                if ($qtyOkAccessor === null) {
                                    $qtyOkAccessor = $qc?->qty_ok ?? ($row->qty_pcs ?? 0);
                                }
                                $qtyOk = (float) $qtyOkAccessor;

                                $effectiveOk = max(0, min($qtyOk, $wip + $picked));
                                $ready =
                                    (float) ($row->qty_ready_for_sewing ?? max(0, min($effectiveOk, $wip) - $picked));

                                $basis = max($effectiveOk, $wip, $picked, $ready);
                                $pickedPercent = $basis > 0 ? max(0, min(100, ($picked / $basis) * 100)) : 0;
                                $readyPercent = $basis > 0 ? max(0, min(100, ($ready / $basis) * 100)) : 0;
                            @endphp

                            @if ($hasQcCutting)
                                <tr class="{{ ((float) ($qc?->qty_reject ?? 0)) > 0 ? 'table-danger-subtle' : '' }}">
                                    <td>{{ $row->bundle_no }}</td>
                                    <td>{{ $row->bundle_code }}</td>
                                    <td>{{ $row->finishedItem?->code ?? '-' }}</td>
                                    <td>{{ number_format((float) ($row->qty_pcs ?? 0), 2, ',', '.') }}</td>

                                    <td
                                        class="{{ ((float) ($qc?->qty_reject ?? 0)) > 0 ? 'text-danger fw-semibold' : '' }}">
                                        {{ $qc ? number_format((float) ($qc->qty_reject ?? 0), 2, ',', '.') : '0,00' }}
                                    </td>

                                    <td>{{ number_format($effectiveOk, 2, ',', '.') }}</td>

                                    <td>
                                        <div class="bundle-info-wrap mb-1">
                                            <span class="bundle-info-pill pill-primary">WIP
                                                {{ number_format($wip, 2, ',', '.') }}</span>
                                            <span class="bundle-info-pill pill-warning">Picked
                                                {{ number_format($picked, 2, ',', '.') }}</span>
                                            <span class="bundle-info-pill pill-success">Ready
                                                {{ number_format($ready, 2, ',', '.') }}</span>
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
                                                    <span class="me-2"><span
                                                            class="legend-box legend-picked"></span>Picked</span>
                                                    <span><span class="legend-box legend-ready"></span>Ready</span>
                                                </div>
                                            </div>
                                        @endif
                                    </td>
                                </tr>
                            @else
                                @php
                                    $okNoQc = max(0, $wip + $picked);
                                @endphp
                                <tr>
                                    <td>{{ $row->bundle_no }}</td>
                                    <td>{{ $row->bundle_code }}</td>
                                    <td>{{ $row->finishedItem?->code ?? '-' }}</td>
                                    <td>{{ number_format((float) ($row->qty_pcs ?? 0), 2, ',', '.') }}</td>
                                    <td>{{ number_format((float) ($row->qty_used_fabric ?? 0), 2, ',', '.') }}</td>
                                    <td>
                                        <div class="bundle-info-wrap mb-1">
                                            <span class="bundle-info-pill pill-primary">WIP
                                                {{ number_format($wip, 2, ',', '.') }}</span>
                                            <span class="bundle-info-pill pill-warning">Picked
                                                {{ number_format($picked, 2, ',', '.') }}</span>
                                            <span class="bundle-info-pill pill-success">Ready
                                                {{ number_format($ready, 2, ',', '.') }}</span>
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
                                                    <span class="me-2"><span
                                                            class="legend-box legend-picked"></span>Picked</span>
                                                    <span><span class="legend-box legend-ready"></span>Ready</span>
                                                </div>
                                            </div>
                                        @endif
                                    </td>
                                </tr>
                            @endif
                        @empty
                            <tr>
                                <td colspan="{{ $hasQcCutting ? 7 : 6 }}" class="text-center text-muted small">Belum ada
                                    data bundle.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- === MOBILE TABLE === --}}
        <div class="card p-3 mb-4 d-block d-md-none">
            <h2 class="h6 mb-2">Detail Bundles</h2>

            <div class="table-wrap">
                <table class="table table-sm align-middle mono">
                    <thead>
                        @if ($hasQcCutting)
                            <tr>
                                <th style="width:50px;">#</th>
                                <th>Kode Barang</th>
                                <th style="width:80px;">OK</th>
                                <th style="width:80px;">Reject</th>
                            </tr>
                        @else
                            <tr>
                                <th style="width:50px;">#</th>
                                <th>Kode Barang</th>
                                <th style="width:110px;">Hasil</th>
                            </tr>
                        @endif
                    </thead>
                    <tbody>
                        @forelse ($job->bundles as $row)
                            @php
                                $qc = $hasQcCutting
                                    ? $row->qcResults->where('stage', 'cutting')->sortByDesc('qc_date')->first()
                                    : null;

                                $wip = (float) ($row->cut_wip_qty ?? 0);
                                $picked = (float) ($row->sewing_picked_qty ?? 0);

                                $qtyOkAccessor = $row->qty_cutting_ok ?? null;
                                if ($qtyOkAccessor === null) {
                                    $qtyOkAccessor = $qc?->qty_ok ?? ($row->qty_pcs ?? 0);
                                }
                                $qtyOk = (float) $qtyOkAccessor;

                                $effectiveOk = max(0, min($qtyOk, $wip + $picked));
                                $ready =
                                    (float) ($row->qty_ready_for_sewing ?? max(0, min($effectiveOk, $wip) - $picked));

                                $basis = max($effectiveOk, $wip, $picked, $ready);
                                $pickedPercent = $basis > 0 ? max(0, min(100, ($picked / $basis) * 100)) : 0;
                                $readyPercent = $basis > 0 ? max(0, min(100, ($ready / $basis) * 100)) : 0;

                                $okNoQc = max(0, $wip + $picked);
                            @endphp

                            @if ($hasQcCutting)
                                <tr class="{{ ((float) ($qc?->qty_reject ?? 0)) > 0 ? 'table-danger-subtle' : '' }}">
                                    <td>{{ $row->bundle_no }}</td>
                                    <td>
                                        {{ $row->finishedItem?->code ?? '-' }}
                                        <div class="bundle-info-mobile mt-1">
                                            WIP {{ number_format($wip, 0, ',', '.') }} • Pick
                                            {{ number_format($picked, 0, ',', '.') }} • Ready
                                            {{ number_format($ready, 0, ',', '.') }}
                                        </div>
                                        @if ($basis > 0)
                                            <div class="bundle-progress mt-1">
                                                <div class="bundle-progress-bar">
                                                    <div class="bp-picked"
                                                        style="width: {{ number_format($pickedPercent, 2, '.', '') }}%;">
                                                    </div>
                                                    <div class="bp-ready"
                                                        style="width: {{ number_format($readyPercent, 2, '.', '') }}%;">
                                                    </div>
                                                </div>
                                            </div>
                                        @endif
                                    </td>
                                    <td>{{ number_format($effectiveOk, 0, ',', '.') }}</td>
                                    <td
                                        class="{{ ((float) ($qc?->qty_reject ?? 0)) > 0 ? 'text-danger fw-semibold' : '' }}">
                                        {{ $qc ? number_format((float) ($qc->qty_reject ?? 0), 0, ',', '.') : '0' }}
                                    </td>
                                </tr>
                            @else
                                <tr>
                                    <td>{{ $row->bundle_no }}</td>
                                    <td>
                                        {{ $row->finishedItem?->code ?? '-' }}
                                        <div class="bundle-info-mobile mt-1">
                                            WIP {{ number_format($wip, 0, ',', '.') }} • Pick
                                            {{ number_format($picked, 0, ',', '.') }} • Ready
                                            {{ number_format($ready, 0, ',', '.') }}
                                        </div>
                                        @if ($basis > 0)
                                            <div class="bundle-progress mt-1">
                                                <div class="bundle-progress-bar">
                                                    <div class="bp-picked"
                                                        style="width: {{ number_format($pickedPercent, 2, '.', '') }}%;">
                                                    </div>
                                                    <div class="bp-ready"
                                                        style="width: {{ number_format($readyPercent, 2, '.', '') }}%;">
                                                    </div>
                                                </div>
                                            </div>
                                        @endif
                                    </td>
                                    <td>{{ number_format($okNoQc, 0, ',', '.') }}</td>
                                </tr>
                            @endif
                        @empty
                            <tr>
                                <td colspan="{{ $hasQcCutting ? 4 : 3 }}" class="text-center text-muted small">Belum ada
                                    data bundle.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- ===========================
            MOBILE FLOATING ACTIONS
        ============================ --}}
        <div class="cutting-mobile-actions d-block d-md-none">
            <div class="cutting-mobile-actions-inner cutting-actions">
                {!! $renderActions(true) !!}
            </div>
        </div>

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
@endsection
