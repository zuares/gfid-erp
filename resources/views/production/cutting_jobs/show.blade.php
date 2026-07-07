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

        .cell-ok { font-variant-numeric: tabular-nums }

        .small-muted { font-size: .8rem; color: var(--muted) }
        .badge-soft  { border-radius: 999px; padding: .2rem .6rem; font-size: .75rem }

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
                $canRevertToRaw,
            ) {
                $btn = $isMobile ? 'btn btn-sm' : 'btn btn-sm';
                $wrapClass = $isMobile ? '' : 'cutting-actions';

                echo '<div class="d-flex gap-2 flex-wrap ' . e($wrapClass) . '">';


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

                // Kembalikan ke Bahan Baku (owner, QC sudah diposting, belum ditarik jahit)
                // = Batalkan QC + Void dalam satu klik.
                if ($canRevertToRaw) {
                    echo '<form action="' .
                        e(route('production.cutting_jobs.revert_to_raw', $job)) .
                        '" method="post" class="d-inline"
                            onsubmit="return confirm(\'↩️ Kembalikan ' . e($job->code) . ' ke Bahan Baku?\\n\\nQC dibatalkan, WIP-CUT dibalik, dan kain kembali ke RM/LOT. Jurnal ikut ter-void.\\nDitolak jika bundle sudah ditarik jahit.\')">';
                    echo csrf_field();
                    echo '<button type="submit" class="' . e($btn) . ' btn-outline-danger">↩️ Kembalikan ke Bahan Baku</button>';
                    echo '</form>';
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
                        <div class="sub">
                            {{ $job->lot?->item?->code ?? '-' }} • {{ $job->lot?->code ?? '-' }} • {{ $job->warehouse?->code ?? '-' }}
                            @if ($job->date) • {{ $job->date->format('d/m/Y') }} @endif
                        </div>
                    </div>
                    <div class="hdr-right d-flex align-items-center gap-2 flex-wrap">
                        {{-- Badge tampil di sini pada desktop --}}
                        <span class="badge bg-{{ $statusClass }} d-none d-md-inline-flex">{{ $statusLabel }}</span>
                        <a href="{{ route('production.cutting_jobs.index') }}"
                           class="btn btn-sm btn-outline-secondary btn-header-link">← Kembali</a>
                        {!! $renderActions(false) !!}
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
                        <span>{{ $job->date?->format('d/m/Y') }}</span>
                    @endif
                </div>
            </div>

            @if (!empty($job->notes))
                <div class="mt-2 text-muted small">{{ $job->notes }}</div>
            @endif
        </div>

        {{-- ===========================
            SISA KAIN PER LOT
        ============================ --}}
        @if ($job->lots && $job->lots->count() > 0)
        @php
            $lotsWithSisa = $job->lots->filter(fn($l) => $l->sisa_recorded_at);
            $lotsNeedSisa = $job->lots->filter(fn($l) => !$l->sisa_recorded_at && (float)($l->used_fabric_qty ?? 0) > 0);
        @endphp
        <div class="card p-3 mb-3">
            <h2 class="h6 mb-2">Sisa Kain per LOT</h2>

            {{-- Sudah tercatat --}}
            @if ($lotsWithSisa->isNotEmpty())
            <div class="table-responsive mb-3">
                <table class="table table-sm mb-0">
                    <thead>
                        <tr>
                            <th>LOT</th>
                            <th class="text-end">Terpakai</th>
                            <th class="text-end">Sisa Layak</th>
                            <th class="text-end">Scrap</th>
                            <th>Dicatat</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($lotsWithSisa as $cjl)
                        <tr>
                            <td class="mono">{{ $cjl->lot?->code ?? '-' }}</td>
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

            {{-- Form catat sisa --}}
            @if ($lotsNeedSisa->isNotEmpty())
            <form method="POST" action="{{ route('production.cutting_jobs.sisa_fabric', $job) }}">
                @csrf
                <div class="table-responsive mb-2">
                    <table class="table table-sm mb-0">
                        <thead>
                            <tr>
                                <th>LOT</th>
                                <th class="text-end">Dipakai (kg)</th>
                                <th class="text-end" style="width:150px">Sisa Layak (kg)
                                    <div class="fw-normal text-muted" style="font-size:.62rem;">kembali ke stok</div>
                                </th>
                                <th class="text-end" style="width:150px">Scrap (kg)
                                    <div class="fw-normal text-muted" style="font-size:.62rem;">perca / terbuang</div>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($lotsNeedSisa as $i => $cjl)
                            @php
                                // Kain yang benar-benar jadi potongan (estimasi standar):
                                // Σ (qty_pcs × BOM qty tanpa scrap) untuk bundle di LOT ini.
                                $lotUsed = (float) $cjl->used_fabric_qty;
                                $lotGood = $job->bundles
                                    ->where('lot_id', $cjl->lot_id)
                                    ->sum(function ($b) use ($bomScrapTargets) {
                                        $t = $bomScrapTargets[(int) $b->finished_item_id] ?? null;
                                        return $t ? (float) $b->qty_pcs * (float) $t['bom_qty'] : 0.0;
                                    });
                                // Sisa fisik LOT (ujung gulungan yang masih tercatat sebagai stok)
                                $lotRemnant = max((float) ($cjl->lot?->qty_onhand ?? 0), 0);
                                // Prefill scrap = kelebihan pakai + sisa fisik LOT
                                // (asumsi sisa layak 0) — JS menyesuaikan saat sisa diketik
                                $scrapPrefill = $lotGood > 0
                                    ? round(max($lotUsed - $lotGood, 0) + $lotRemnant, 2)
                                    : ($lotRemnant > 0 ? round($lotRemnant, 2) : null);
                            @endphp
                            <input type="hidden" name="lots[{{ $i }}][lot_id]" value="{{ $cjl->lot_id }}">
                            <tr class="js-sisa-row" data-used="{{ $lotUsed }}" data-good="{{ $lotGood }}" data-remnant="{{ $lotRemnant }}">
                                <td class="mono">{{ $cjl->lot?->code ?? '-' }}
                                    <div class="small text-muted">{{ $cjl->lot?->item?->code ?? '' }}</div>
                                </td>
                                <td class="text-end mono">{{ number_format($lotUsed, 2, ',', '.') }}</td>
                                <td class="text-end">
                                    <input type="number" name="lots[{{ $i }}][qty_sisa]"
                                           class="form-control form-control-sm text-end mono js-sisa-input"
                                           step="0.01" min="0" value="0"
                                           style="max-width:120px;margin-left:auto">
                                </td>
                                <td class="text-end">
                                    <input type="number" name="lots[{{ $i }}][qty_scrap]"
                                           class="form-control form-control-sm text-end mono js-scrap-input"
                                           step="0.01" min="0" placeholder="0"
                                           value="{{ $scrapPrefill !== null ? $scrapPrefill : '' }}"
                                           data-auto="1"
                                           style="max-width:120px;margin-left:auto;border-color:#fca5a5;">
                                    @if ($lotGood > 0 || $lotRemnant > 0)
                                        <div class="text-muted" style="font-size:.62rem;">
                                            auto: kelebihan pakai{{ $lotRemnant > 0 ? ' + sisa LOT ' . number_format($lotRemnant, 2, ',', '.') . ' kg' : '' }}
                                        </div>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="d-flex justify-content-end">
                    <button type="submit" class="btn btn-sm btn-outline-success">
                        <i class="bi bi-box-arrow-in-down me-1"></i>Catat Sisa → Kembalikan ke RM
                    </button>
                </div>
            </form>
            @elseif ($lotsWithSisa->isEmpty())
            <div class="text-muted small">Belum ada pemakaian kain yang tercatat untuk LOT ini.</div>
            @else
            <div class="text-success small"><i class="bi bi-check-circle me-1"></i>Semua sisa kain sudah dicatat.</div>
            @endif
        </div>
        @endif

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
                            <th class="text-end" style="width:100px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse ($job->bundles as $i => $row)
                        @php
                            $qc          = $row->qcResults->where('stage','cutting')->sortByDesc('qc_date')->first();
                            $bundleHasQc = $qc !== null;
                            $canInputThis = $canBundleQcRoute && !$bundleHasQc;

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

                            $rowClass = $isReject || $isMixed ? 'row-has-reject'
                                : ($canInputThis ? 'row-no-qc' : '');
                            $trHide   = $isAllOk ? 'bq-row-ok' : '';

                            if ($qtyRej > 0) $hasAnyReject = true;

                            $bundleStatus = $bundleHasQc
                                ? ($isAllOk ? 'qc_ok' : ($isReject ? 'qc_reject' : 'qc_mixed'))
                                : ($canInputThis ? 'pending' : '—');
                            $statusBadge  = [
                                'qc_ok'     => 'bg-success',
                                'qc_mixed'  => 'bg-warning',
                                'qc_reject' => 'bg-danger',
                                'pending'   => 'bg-secondary',
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
                            <td class="text-end">
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
