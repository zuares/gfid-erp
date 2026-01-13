{{-- resources/views/production/qc/cutting_edit.blade.php --}}
@extends('layouts.app')

@section('title', 'Produksi • QC Cutting ' . $cuttingJob->code)

@push('head')
    <style>
        .qc-cutting-page {
            min-height: 100vh
        }

        .qc-cutting-page .page-wrap {
            max-width: 1080px;
            margin-inline: auto;
            padding: 1rem 1rem 4rem
        }

        body[data-theme="light"] .qc-cutting-page .page-wrap {
            background: radial-gradient(circle at top left, rgba(59, 130, 246, .12) 0, rgba(45, 212, 191, .10) 26%, #f9fafb 60%)
        }

        body[data-theme="dark"] .qc-cutting-page .page-wrap {
            background: radial-gradient(circle at top left, rgba(59, 130, 246, .20) 0, rgba(45, 212, 191, .18) 30%, #020617 70%)
        }

        .card {
            background: var(--card);
            border-radius: 16px;
            border: 1px solid rgba(148, 163, 184, .35);
            box-shadow: 0 12px 30px rgba(15, 23, 42, .20)
        }

        .card-soft {
            background: color-mix(in srgb, var(--card) 84%, var(--line) 16%)
        }

        .mono {
            font-variant-numeric: tabular-nums;
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas
        }

        .pill {
            display: inline-flex;
            align-items: center;
            gap: .25rem;
            padding: .15rem .6rem;
            border-radius: 999px;
            font-size: .78rem;
            border: 1px solid var(--line);
            background: rgba(15, 23, 42, .01)
        }

        .badge-soft {
            border-radius: 999px;
            padding: .2rem .6rem;
            font-size: .75rem
        }

        .section-title {
            font-size: .88rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .04em
        }

        .field-label {
            font-size: .8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .04em
        }

        .small-muted {
            font-size: .8rem;
            color: var(--muted)
        }

        .qc-table thead th {
            font-size: .8rem;
            text-transform: uppercase;
            letter-spacing: .04em
        }

        /* STATUS STEPPER */
        .status-stepper {
            display: flex;
            align-items: center;
            gap: .75rem;
            font-size: .78rem
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
            background: #22c55e33;
            border-color: #22c55e;
            box-shadow: 0 0 0 1px #22c55e44
        }

        .status-dot.current {
            background: #2563eb33;
            border-color: #2563eb;
            box-shadow: 0 0 0 1px #2563eb55
        }

        .status-label {
            text-transform: uppercase;
            letter-spacing: .08em;
            font-size: .72rem;
            color: #6b7280
        }

        .status-label.current {
            color: #2563eb;
            font-weight: 600
        }

        .status-label.done {
            color: #16a34a;
            font-weight: 600
        }

        .status-separator {
            flex: 0 0 26px;
            height: 1px;
            background: linear-gradient(to right, rgba(148, 163, 184, .7), transparent)
        }

        /* Highlight baris yang ada reject */
        .row-has-reject {
            background: rgba(248, 113, 113, .03)
        }

        .row-has-reject .input-reject {
            border-color: rgba(248, 113, 113, .8);
            background-color: rgba(248, 113, 113, .08)
        }

        .row-has-reject .qc-card-header {
            border-left: 3px solid rgba(248, 113, 113, .7);
            padding-left: .45rem
        }

        .table-wrap {
            overflow-x: auto
        }

        .lot-usage-table tbody tr {
            background: #fff
        }

        /* Adjust modal helper */
        .modal .help-soft {
            font-size: .82rem;
            color: var(--muted)
        }

        .modal .hint-box {
            border: 1px solid rgba(148, 163, 184, .35);
            border-radius: 12px;
            padding: .6rem .75rem;
            background: color-mix(in srgb, var(--card) 90%, var(--line) 10%)
        }

        @media (max-width: 767.98px) {
            .qc-cutting-page .page-wrap {
                padding-inline: .5rem
            }

            .qc-table-mobile {
                font-size: .8rem;
                white-space: nowrap
            }

            .qc-table-mobile th,
            .qc-table-mobile td {
                padding: .35rem .4rem
            }

            .qc-summary-inline {
                display: flex;
                flex-wrap: wrap;
                gap: .25rem .6rem;
                font-size: .78rem
            }

            .status-stepper {
                flex-wrap: wrap;
                gap: .4rem .75rem
            }

            .status-separator {
                display: none
            }

            .lot-usage-table tbody tr {
                background: #fff
            }

            .lot-usage-table .input-lot-used[readonly] {
                background: #f3f4f6;
                border-color: rgba(148, 163, 184, .9);
                cursor: default
            }
        }
    </style>
@endpush

@section('content')
    @php
        $lot = $cuttingJob->lot;
        $warehouse = $cuttingJob->warehouse;
        $jobLots = $cuttingJob->lots ?? collect();

        $defaultOperatorId = old('operator_id', $loginOperator->id ?? null);
        $defaultOperatorLabel = $loginOperator
            ? ($loginOperator->code ?? 'OP') . ' — ' . ($loginOperator->name ?? 'Operator')
            : 'User login';

        $userRole = auth()->user()->role ?? null;
        $isOwner = $userRole === 'owner';
        $isErrorBag = $errors instanceof \Illuminate\Support\ViewErrorBag;

        $defaultQcDate = old('qc_date', optional($cuttingJob->qc_date ?? ($cuttingJob->date ?? now()))->toDateString());

        $statusClass =
            [
                'draft' => 'secondary',
                'cut' => 'primary',
                'qc_ok' => 'success',
                'qc_mixed' => 'warning',
                'qc_reject' => 'danger',
                'sent_to_qc' => 'info',
                'qc_done' => 'success',
            ][$cuttingJob->status] ?? 'secondary';

        $status = $cuttingJob->status;
        $stepCurrent = 1;
        if ($status === 'sent_to_qc') {
            $stepCurrent = 2;
        } elseif (in_array($status, ['qc_ok', 'qc_mixed', 'qc_reject', 'qc_done'])) {
            $stepCurrent = 3;
        }

        $step1State = $stepCurrent >= 1 ? ($stepCurrent === 1 ? 'current' : 'done') : '';
        $step2State = $stepCurrent >= 2 ? ($stepCurrent === 2 ? 'current' : 'done') : '';
        $step3State = $stepCurrent >= 3 ? ($stepCurrent === 3 ? 'current' : 'done') : '';

        // deteksi sudah ada QC
        $hasExistingQc = in_array($cuttingJob->status, ['qc_ok', 'qc_mixed', 'qc_reject', 'qc_done']);
        if (!$hasExistingQc && isset($rows) && is_array($rows)) {
            foreach ($rows as $r) {
                $st = $r['status'] ?? null;
                $ok = (float) ($r['qty_ok'] ?? 0);
                $rej = (float) ($r['qty_reject'] ?? 0);
                if (in_array($st, ['qc_ok', 'qc_mixed', 'qc_reject']) || $ok > 0 || $rej > 0) {
                    $hasExistingQc = true;
                    break;
                }
            }
        }

        $canCancelQc = $isOwner && $hasExistingQc && Route::has('production.qc.cutting.cancel');

        /**
         * ✅ IMPORTANT: Route yang BENAR untuk view ini
         * Pakai yang sudah kamu set:
         * Route::post('/cutting/{cuttingJob}/bundles/{bundle}/adjust', ...)->name('production.qc.cutting.bundle_adjust');
         *
         * Maka Route::has(...) dan route(...) HARUS pakai:
         *  - production.qc.cutting.bundle_adjust
         */
        $canAdjustQc =
            $isOwner &&
            $hasExistingQc &&
            in_array($cuttingJob->status, ['qc_done', 'qc_ok', 'qc_mixed', 'qc_reject'], true) &&
            Route::has('production.qc.cutting.bundle_adjust');
    @endphp

    <div class="qc-cutting-page">
        <div class="page-wrap">

            {{-- HEADER JOB --}}
            <div class="card card-soft p-3 mb-3">
                {{-- DESKTOP --}}
                <div class="d-none d-md-flex justify-content-between align-items-center gap-3">
                    <div>
                        <div class="section-title mb-1">QC Cutting</div>
                        <h1 class="h5 mb-1 mono">{{ $cuttingJob->code }}</h1>
                        <div class="small-muted">
                            LOT {{ $lot?->code ?? '-' }} • {{ $lot?->item?->code ?? '-' }} • Gudang
                            {{ $warehouse?->code ?? '-' }}
                        </div>

                        @if ($jobLots->count() > 0)
                            <div class="mt-1 small-muted">
                                LOT dipakai:
                                @foreach ($jobLots as $jl)
                                    <span class="pill mono">
                                        {{ $jl->lot?->code ?? 'LOT?' }}
                                        (rencana {{ number_format($jl->planned_fabric_qty, 2, ',', '.') }})
                                    </span>
                                @endforeach
                            </div>
                        @endif

                        <div class="status-stepper mt-2">
                            <div class="status-step">
                                <div
                                    class="status-dot {{ $step1State === 'current' ? 'current' : ($step1State === 'done' ? 'active' : '') }}">
                                </div>
                                <div
                                    class="status-label {{ $step1State === 'current' ? 'current' : ($step1State === 'done' ? 'done' : '') }}">
                                    Cutting Selesai</div>
                            </div>
                            <div class="status-separator"></div>
                            <div class="status-step">
                                <div
                                    class="status-dot {{ $step2State === 'current' ? 'current' : ($step2State === 'done' ? 'active' : '') }}">
                                </div>
                                <div
                                    class="status-label {{ $step2State === 'current' ? 'current' : ($step2State === 'done' ? 'done' : '') }}">
                                    Dikirim ke QC</div>
                            </div>
                            <div class="status-separator"></div>
                            <div class="status-step">
                                <div
                                    class="status-dot {{ $step3State === 'current' ? 'current' : ($step3State === 'done' ? 'active' : '') }}">
                                </div>
                                <div
                                    class="status-label {{ $step3State === 'current' ? 'current' : ($step3State === 'done' ? 'done' : '') }}">
                                    Hasil QC</div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex flex-column align-items-end gap-2">
                        <span class="badge bg-{{ $statusClass }} px-3 py-2">{{ strtoupper($cuttingJob->status) }}</span>

                        <div class="d-flex gap-2 flex-wrap justify-content-end">
                            <a href="{{ route('production.cutting_jobs.show', $cuttingJob) }}"
                                class="btn btn-sm btn-outline-secondary">
                                Kembali
                            </a>

                            @if ($canCancelQc)
                                <form action="{{ route('production.qc.cutting.cancel', $cuttingJob) }}" method="post"
                                    onsubmit="return confirm('Batalkan QC Cutting? Sistem akan reversal mutasi QC dan QC harus diinput ulang.')">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Batalkan QC</button>
                                </form>
                            @endif
                        </div>

                        @if ($hasExistingQc && !$isOwner)
                            <div class="small-muted text-end" style="max-width:420px;">
                                QC sudah tersimpan. Jika ada salah input setelah QC done, minta <b>OWNER</b> untuk
                                <b>Batalkan QC</b> lalu input QC ulang.
                            </div>
                        @endif
                    </div>
                </div>

                {{-- MOBILE --}}
                <div class="d-block d-md-none">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <div>
                            <div class="small text-muted">QC Cutting</div>
                            <div class="fw-semibold mono">{{ $cuttingJob->code }}</div>
                        </div>
                        <span class="badge bg-{{ $statusClass }} px-2 py-1">{{ strtoupper($cuttingJob->status) }}</span>
                    </div>

                    <div class="small-muted mb-2">
                        LOT {{ $lot?->code ?? '-' }} • {{ $lot?->item?->code ?? '-' }}
                        @if ($warehouse)
                            • Gudang {{ $warehouse->code }}
                        @endif
                    </div>

                    <div class="status-stepper mb-2">
                        <div class="status-step">
                            <div
                                class="status-dot {{ $step1State === 'current' ? 'current' : ($step1State === 'done' ? 'active' : '') }}">
                            </div>
                            <div
                                class="status-label {{ $step1State === 'current' ? 'current' : ($step1State === 'done' ? 'done' : '') }}">
                                Cutting</div>
                        </div>
                        <div class="status-step">
                            <div
                                class="status-dot {{ $step2State === 'current' ? 'current' : ($step2State === 'done' ? 'active' : '') }}">
                            </div>
                            <div
                                class="status-label {{ $step2State === 'current' ? 'current' : ($step2State === 'done' ? 'done' : '') }}">
                                Kirim QC</div>
                        </div>
                        <div class="status-step">
                            <div
                                class="status-dot {{ $step3State === 'current' ? 'current' : ($step3State === 'done' ? 'active' : '') }}">
                            </div>
                            <div
                                class="status-label {{ $step3State === 'current' ? 'current' : ($step3State === 'done' ? 'done' : '') }}">
                                Hasil QC</div>
                        </div>
                    </div>

                    <div class="d-flex gap-2 flex-wrap">
                        <a href="{{ route('production.cutting_jobs.show', $cuttingJob) }}"
                            class="btn btn-sm btn-outline-secondary flex-fill">
                            Kembali
                        </a>

                        @if ($canCancelQc)
                            <form action="{{ route('production.qc.cutting.cancel', $cuttingJob) }}" method="post"
                                class="flex-fill"
                                onsubmit="return confirm('Batalkan QC Cutting? Sistem akan reversal mutasi QC dan QC harus diinput ulang.')">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-outline-danger w-100">Batalkan QC</button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>

            {{-- =========================
                 FORM QC NORMAL (PUT)
                 ========================= --}}
            <form action="{{ route('production.qc.cutting.update', $cuttingJob) }}" method="post">
                @csrf
                @method('PUT')

                @if (in_array($userRole, ['operating', 'produksi'], true))
                    <input type="hidden" name="qc_date" value="{{ $defaultQcDate }}">
                    <input type="hidden" name="operator_id" value="{{ $defaultOperatorId }}">
                    <input type="hidden" name="notes_global" value="{{ old('notes_global') }}">
                @endif

                {{-- HEADER QC --}}
                @if (!in_array($userRole, ['operating', 'produksi'], true))
                    <div class="card p-3 mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div class="section-title mb-0">Header QC</div>

                            <div class="d-none d-md-flex gap-2">
                                <div class="pill"><span>Total OK</span> <span class="mono" id="sum-ok">0</span></div>
                                <div class="pill"><span>Reject</span> <span class="mono" id="sum-reject">0</span></div>
                            </div>

                            <div class="d-block d-md-none text-end">
                                <div class="qc-summary-inline justify-content-end">
                                    <span>Total OK <span class="mono" id="sum-ok-mobile">0</span></span>
                                    <span>Reject <span class="mono" id="sum-reject-mobile">0</span></span>
                                </div>
                            </div>
                        </div>

                        <div class="row g-3">
                            @php
                                $qcDateError = $isErrorBag ? $errors->first('qc_date') : null;
                                $operatorError = $isErrorBag ? $errors->first('operator_id') : null;
                            @endphp

                            <div class="col-md-3 col-6">
                                <label class="field-label mb-1">Tanggal QC</label>
                                <input type="date" name="qc_date" value="{{ $defaultQcDate }}"
                                    class="form-control {{ $qcDateError ? 'is-invalid' : '' }}">
                                @if ($qcDateError)
                                    <div class="invalid-feedback">{{ $qcDateError }}</div>
                                @endif
                            </div>

                            <div class="col-md-4 col-12">
                                <label class="field-label mb-1">Operator QC</label>
                                <input type="hidden" name="operator_id" value="{{ $defaultOperatorId }}">
                                <input type="text" class="form-control {{ $operatorError ? 'is-invalid' : '' }}"
                                    value="{{ $defaultOperatorLabel }}" disabled>
                                @if ($operatorError)
                                    <div class="invalid-feedback d-block">{{ $operatorError }}</div>
                                @endif
                            </div>

                            <div class="col-12">
                                <label class="field-label mb-1">Catatan Umum</label>
                                <textarea name="notes_global" rows="2" class="form-control" placeholder="Opsional.">{{ old('notes_global') }}</textarea>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- QC per bundle --}}
                <div class="card p-3 mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div class="section-title mb-0">QC per Bundle</div>

                        @if ($canAdjustQc)
                            <div class="small-muted d-none d-md-block text-end" style="max-width:520px;">
                                Owner dapat <b>Adjust</b> per bundle (buat audit trail adjustment). Adjustment seharusnya
                                menolak jika WIP sudah kepakai sewing.
                            </div>
                        @endif
                    </div>

                    <div class="table-wrap">
                        @php $hasAnyReject = false; @endphp

                        <table class="table table-sm align-middle mono qc-table qc-table-mobile">
                            <thead>
                                <tr>
                                    <th style="width:80px;">No</th>
                                    <th>Item</th>
                                    <th class="text-end" style="width:120px;">Cut</th>
                                    <th class="text-end" style="width:120px;">OK</th>
                                    <th class="text-center" style="width:120px;">Reject</th>
                                    <th class="d-none d-md-table-cell" style="width:180px;">Alasan</th>
                                    <th class="d-none d-md-table-cell" style="width:110px;">Status</th>
                                    <th class="d-none d-md-table-cell" style="width:160px;">Catatan</th>
                                    @if ($canAdjustQc)
                                        <th class="text-end" style="width:120px;">Aksi</th>
                                    @endif
                                </tr>
                            </thead>

                            <tbody>
                                @foreach ($rows as $i => $row)
                                    @php
                                        $bundleId = (int) $row['cutting_job_bundle_id'];
                                        $bundleQty = (float) $row['qty_pcs'];

                                        $qtyOkExisting = (float) ($row['qty_ok'] ?? 0);
                                        $qtyRejectExisting = (float) ($row['qty_reject'] ?? 0);

                                        // QC normal: input reject, OK dihitung otomatis
                                        $qtyRejectOld = old("results.$i.qty_reject", $qtyRejectExisting);
                                        $qtyReject = (float) $qtyRejectOld;
                                        if ($qtyReject < 0) {
                                            $qtyReject = 0;
                                        }
                                        if ($qtyReject > $bundleQty) {
                                            $qtyReject = $bundleQty;
                                        }
                                        $qtyOkCalc = max($bundleQty - $qtyReject, 0);

                                        if ($qtyReject > 0) {
                                            $hasAnyReject = true;
                                        }

                                        $fieldReject = "results.$i.qty_reject";
                                        $fieldReason = "results.$i.reject_reason";
                                        $fieldNotes = "results.$i.notes";

                                        $rejectError = $isErrorBag ? $errors->first($fieldReject) : null;
                                        $reasonError = $isErrorBag ? $errors->first($fieldReason) : null;
                                        $notesError = $isErrorBag ? $errors->first($fieldNotes) : null;

                                        $st = $row['status'] ?: 'cut';
                                        $cls =
                                            [
                                                'cut' => 'secondary',
                                                'qc_ok' => 'success',
                                                'qc_reject' => 'danger',
                                                'qc_mixed' => 'warning',
                                                'qc_done' => 'success',
                                            ][$st] ?? 'secondary';

                                        $modalId = 'qcAdjustModal_' . $bundleId;
                                    @endphp

                                    <tr class="{{ $qtyReject > 0 ? 'row-has-reject' : '' }}">
                                        <input type="hidden" name="results[{{ $i }}][cutting_job_bundle_id]"
                                            value="{{ $bundleId }}">
                                        <input type="hidden" name="results[{{ $i }}][qty_ok]"
                                            class="input-ok-hidden" value="{{ old("results.$i.qty_ok", $qtyOkCalc) }}">

                                        <td class="qc-card-header">
                                            <div class="fw-semibold mono">#{{ $i + 1 }}</div>
                                            <div class="small-muted mono d-none d-md-block">
                                                Bundle #{{ $row['bundle_no'] ?? '-' }}
                                                {{ $row['bundle_code'] ? '· ' . $row['bundle_code'] : '' }}
                                            </div>
                                        </td>

                                        <td>
                                            <div class="d-none d-md-block">
                                                <div class="fw-semibold mono">{{ $row['item_code'] }}</div>
                                                @if (!empty($row['item_name']))
                                                    <div class="small-muted">{{ $row['item_name'] }}</div>
                                                @endif
                                            </div>
                                            <div class="d-block d-md-none">{{ $row['item_code'] }}</div>
                                        </td>

                                        <td class="text-end">{{ number_format($bundleQty, 0, ',', '.') }}</td>

                                        <td class="text-end">
                                            <span
                                                class="cell-ok">{{ number_format(old("results.$i.qty_ok", $qtyOkCalc), 0, ',', '.') }}</span>
                                        </td>

                                        <td class="text-center">
                                            <input type="number" step="1" min="0" inputmode="numeric"
                                                pattern="\d*" name="results[{{ $i }}][qty_reject]"
                                                class="form-control form-control-sm text-center input-reject {{ $rejectError ? 'is-invalid' : '' }}"
                                                value="{{ old("results.$i.qty_reject", $qtyReject) }}"
                                                data-bundle="{{ $bundleQty }}">
                                            @if ($rejectError)
                                                <div class="invalid-feedback">{{ $rejectError }}</div>
                                            @endif
                                        </td>

                                        <td class="d-none d-md-table-cell">
                                            <input type="text" name="results[{{ $i }}][reject_reason]"
                                                class="form-control form-control-sm {{ $reasonError ? 'is-invalid' : '' }}"
                                                value="{{ old("results.$i.reject_reason", $row['reject_reason'] ?? '') }}"
                                                placeholder="mis: bolong, kotor">
                                            @if ($reasonError)
                                                <div class="invalid-feedback">{{ $reasonError }}</div>
                                            @endif
                                        </td>

                                        <td class="d-none d-md-table-cell">
                                            <span class="badge-soft bg-{{ $cls }}">{{ $st }}</span>
                                        </td>

                                        <td class="d-none d-md-table-cell">
                                            <input type="text" name="results[{{ $i }}][notes]"
                                                class="form-control form-control-sm {{ $notesError ? 'is-invalid' : '' }}"
                                                value="{{ old("results.$i.notes", $row['notes'] ?? '') }}">
                                            @if ($notesError)
                                                <div class="invalid-feedback">{{ $notesError }}</div>
                                            @endif
                                        </td>

                                        {{-- OWNER ADJUST ACTION --}}
                                        @if ($canAdjustQc)
                                            <td class="text-end">
                                                <button type="button" class="btn btn-sm btn-outline-warning"
                                                    data-bs-toggle="modal" data-bs-target="#{{ $modalId }}">
                                                    Adjust
                                                </button>

                                                <div class="modal fade" id="{{ $modalId }}" tabindex="-1"
                                                    aria-hidden="true">
                                                    <div class="modal-dialog modal-dialog-centered">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <div>
                                                                    <div class="fw-semibold">QC Adjust • Bundle
                                                                        #{{ $row['bundle_no'] ?? '-' }}</div>
                                                                    <div class="small-muted mono">
                                                                        {{ $row['bundle_code'] ?? '' }} • Cut
                                                                        {{ number_format($bundleQty, 0, ',', '.') }} pcs
                                                                    </div>
                                                                </div>
                                                                <button type="button" class="btn-close"
                                                                    data-bs-dismiss="modal" aria-label="Close"></button>
                                                            </div>

                                                            {{-- ✅ route parameter HARUS Model binding: $bundle (CuttingJobBundle) --}}
                                                            <form
                                                                action="{{ route('production.qc.cutting.bundle_adjust', [$cuttingJob, $bundleId]) }}"
                                                                method="post" class="qc-adjust-form">
                                                                @csrf

                                                                <div class="modal-body">
                                                                    <div class="hint-box mb-3">
                                                                        <div class="help-soft">
                                                                            Aturan: <b>OK + Reject ≤ Cut</b>.
                                                                            Adjustment ini untuk koreksi QC yang sudah
                                                                            terlanjur <b>qc_done</b>.
                                                                            Sistem idealnya <b>menolak</b> jika WIP sudah
                                                                            kepakai sewing.
                                                                        </div>
                                                                    </div>

                                                                    <input type="hidden" name="qc_date"
                                                                        value="{{ $defaultQcDate }}">

                                                                    <div class="row g-3">
                                                                        <div class="col-6">
                                                                            <label class="field-label mb-1">Qty OK</label>
                                                                            <input type="number"
                                                                                class="form-control input-adjust-ok"
                                                                                name="qty_ok" min="0"
                                                                                step="1" inputmode="numeric"
                                                                                value="{{ (int) $qtyOkExisting }}"
                                                                                data-max="{{ $bundleQty }}">
                                                                            <div class="help-soft mt-1">Default dari QC
                                                                                terakhir.</div>
                                                                        </div>

                                                                        <div class="col-6">
                                                                            <label class="field-label mb-1">Qty
                                                                                Reject</label>
                                                                            <input type="number"
                                                                                class="form-control input-adjust-reject"
                                                                                name="qty_reject" min="0"
                                                                                step="1" inputmode="numeric"
                                                                                value="{{ (int) $qtyRejectExisting }}"
                                                                                data-max="{{ $bundleQty }}">
                                                                            <div class="help-soft mt-1">Sisa dari Cut.
                                                                            </div>
                                                                        </div>
                                                                    </div>

                                                                    <div class="mt-3">
                                                                        <label class="field-label mb-1">Catatan Adjust
                                                                            (opsional)
                                                                        </label>
                                                                        <input type="text" class="form-control"
                                                                            name="notes"
                                                                            placeholder="mis: salah input qty OK"
                                                                            value="">
                                                                    </div>

                                                                    <div class="text-danger small mt-2 qc-adjust-warning"
                                                                        style="display:none;">
                                                                        ⚠️ Nilai OK+Reject melebihi Cut. Sistem akan
                                                                        mengunci ke batas maksimum.
                                                                    </div>
                                                                </div>

                                                                <div class="modal-footer">
                                                                    <button type="button"
                                                                        class="btn btn-outline-secondary"
                                                                        data-bs-dismiss="modal">Batal</button>
                                                                    <button type="submit" class="btn btn-warning"
                                                                        onclick="return confirm('Simpan QC Adjust untuk bundle ini? Pastikan WIP belum kepakai sewing.')">
                                                                        Simpan Adjust
                                                                    </button>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                        @endif
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @php $resultsError = $isErrorBag ? $errors->first('results') : null; @endphp
                    @if ($resultsError)
                        <div class="text-danger small mt-2">{{ $resultsError }}</div>
                    @endif

                    <div id="qc-warning" class="text-danger small mt-2" style="display:none;">
                        ⚠️ Qty Reject tidak boleh melebihi Qty Cutting. Nilai otomatis dikunci ke batas maksimum.
                    </div>

                    @if ($hasAnyReject)
                        <div class="text-warning small mt-2">
                            ⚠️ Terdapat bundle dengan reject. Pastikan alasan reject sudah terisi dengan jelas (cek di
                            desktop jika perlu).
                        </div>
                    @endif
                </div>

                {{-- MULTI-LOT --}}
                @if ($jobLots->count() > 0)
                    <div class="card p-3 mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div class="section-title mb-0">Pemakaian Kain per LOT</div>
                        </div>

                        <div class="table-wrap">
                            <table class="table table-sm align-middle mono lot-usage-table">
                                <thead>
                                    <tr>
                                        <th style="width: 150px;">LOT</th>
                                        <th class="d-none d-md-table-cell">Item</th>
                                        <th class="text-end" style="width: 130px;">Rencana</th>
                                        <th class="text-end" style="width: 150px;">Dipakai (QC)</th>
                                        <th class="text-end d-none d-md-table-cell" style="width: 130px;">Estimasi Sisa
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($jobLots as $j => $jobLot)
                                        @php
                                            $lotModel = $jobLot->lot;
                                            $planned = (float) $jobLot->planned_fabric_qty;

                                            $usedOld = old(
                                                "lots.$j.used_fabric_qty",
                                                $jobLot->used_fabric_qty ?: $planned,
                                            );
                                            $used = (float) $usedOld;
                                            if ($used < 0) {
                                                $used = 0;
                                            }
                                            if ($planned > 0 && $used > $planned) {
                                                $used = $planned;
                                            }
                                            $balance = $planned - $used;

                                            $fieldUsed = "lots.$j.used_fabric_qty";
                                            $usedError = $isErrorBag ? $errors->first($fieldUsed) : null;
                                        @endphp

                                        <tr>
                                            <input type="hidden" name="lots[{{ $j }}][id]"
                                                value="{{ $jobLot->id }}">

                                            <td>
                                                <div class="fw-semibold">{{ $lotModel?->code ?? 'LOT ?' }}</div>
                                                <div class="small-muted d-none d-md-block">
                                                    {{ $lotModel?->item?->code ?? '-' }}</div>
                                            </td>

                                            <td class="d-none d-md-table-cell">
                                                <div>{{ $lotModel?->item?->name ?? '-' }}</div>
                                                <div class="small-muted">Gudang {{ $warehouse?->code ?? '-' }}</div>
                                            </td>

                                            <td class="text-end">{{ number_format($planned, 2, ',', '.') }}</td>

                                            <td class="text-end">
                                                <x-number-input name="lots[{{ $j }}][used_fabric_qty]"
                                                    mode="decimal" :value="$used" decimals="2" min="0"
                                                    class="form-control form-control-sm text-end input-lot-used {{ $usedError ? 'is-invalid' : '' }}"
                                                    data-planned="{{ $planned }}" />
                                                @if ($usedError)
                                                    <div class="invalid-feedback">{{ $usedError }}</div>
                                                @endif
                                            </td>

                                            <td class="text-end d-none d-md-table-cell">
                                                <span
                                                    class="lot-balance-desktop">{{ number_format($balance, 2, ',', '.') }}</span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        @php $lotsError = $isErrorBag ? $errors->first('lots') : null; @endphp
                        @if ($lotsError)
                            <div class="text-danger small mt-2">{{ $lotsError }}</div>
                        @endif

                        <div id="lot-warning" class="text-danger small mt-2" style="display:none;">
                            ⚠️ Pemakaian per LOT tidak boleh melebihi qty rencana. Nilai otomatis dikunci ke batas maksimum.
                        </div>
                    </div>
                @endif

                {{-- ACTIONS --}}
                <div class="d-none d-md-flex justify-content-end mb-5 gap-2 flex-wrap">
                    <a href="{{ route('production.cutting_jobs.show', $cuttingJob) }}"
                        class="btn btn-outline-secondary">Batal</a>
                    <button type="submit" class="btn btn-primary">Simpan Hasil QC</button>
                </div>

                <div class="d-block d-md-none mb-5">
                    <div class="d-grid gap-2">
                        <a href="{{ route('production.cutting_jobs.show', $cuttingJob) }}"
                            class="btn btn-outline-secondary w-100">Batal</a>
                        <button type="submit" class="btn btn-primary w-100">Simpan Hasil QC</button>
                    </div>
                </div>
            </form>

        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const inputsReject = document.querySelectorAll('.input-reject');
            const sumOkSpan = document.getElementById('sum-ok');
            const sumRejectSpan = document.getElementById('sum-reject');
            const sumOkMobileSpan = document.getElementById('sum-ok-mobile');
            const sumRejectMobileSpan = document.getElementById('sum-reject-mobile');
            const warningEl = document.getElementById('qc-warning');

            function attachSelectAllOnFocus(input) {
                input.addEventListener('focus', function() {
                    setTimeout(() => this.select(), 0);
                });
                input.addEventListener('mouseup', function(e) {
                    e.preventDefault();
                });
            }

            function formatInt(num) {
                return num.toLocaleString('id-ID');
            }

            // ===== QC NORMAL: reject -> ok auto =====
            function recalcTotals() {
                let totalOk = 0;
                let totalReject = 0;
                let anyOver = false;

                inputsReject.forEach(rejInput => {
                    const tr = rejInput.closest('tr');
                    const okHidden = tr.querySelector('.input-ok-hidden');
                    const okCell = tr.querySelector('.cell-ok');
                    const maxBundle = parseFloat(rejInput.dataset.bundle || '0') || 0;

                    let rej = parseFloat(rejInput.value || '0');
                    if (isNaN(rej) || rej < 0) rej = 0;

                    if (rej > maxBundle) {
                        rej = maxBundle;
                        anyOver = true;
                        rejInput.value = rej;
                    }

                    const ok = maxBundle - rej;

                    if (okHidden) okHidden.value = ok;
                    if (okCell) okCell.textContent = formatInt(Math.round(ok));

                    totalOk += ok;
                    totalReject += rej;

                    if (rej > 0) tr.classList.add('row-has-reject');
                    else tr.classList.remove('row-has-reject');
                });

                const okInt = Math.round(totalOk);
                const rejInt = Math.round(totalReject);

                if (sumOkSpan) sumOkSpan.textContent = formatInt(okInt);
                if (sumRejectSpan) sumRejectSpan.textContent = formatInt(rejInt);
                if (sumOkMobileSpan) sumOkMobileSpan.textContent = formatInt(okInt);
                if (sumRejectMobileSpan) sumRejectMobileSpan.textContent = formatInt(rejInt);

                if (warningEl) warningEl.style.display = anyOver ? 'block' : 'none';
            }

            inputsReject.forEach(i => {
                attachSelectAllOnFocus(i);
                i.addEventListener('input', recalcTotals);
                i.addEventListener('focus', () => {
                    i.scrollIntoView({
                        behavior: 'smooth',
                        block: 'center'
                    });
                });
            });
            recalcTotals();

            // ===== MULTI-LOT =====
            const lotInputs = document.querySelectorAll('.input-lot-used');
            const lotWarningEl = document.getElementById('lot-warning');

            function recalcLotBalances() {
                let anyOver = false;

                lotInputs.forEach(input => {
                    const planned = parseFloat(input.dataset.planned || '0') || 0;
                    let used = parseFloat(input.value || '0');
                    if (isNaN(used) || used < 0) used = 0;

                    if (planned > 0 && used > planned) {
                        used = planned;
                        anyOver = true;
                        input.value = used;
                    }

                    const balance = planned - used;
                    const tr = input.closest('tr');
                    if (!tr) return;

                    const balDesktop = tr.querySelector('.lot-balance-desktop');
                    const text = balance.toFixed(2).replace('.', ',');

                    if (balDesktop) balDesktop.textContent = text;
                });

                if (lotWarningEl) lotWarningEl.style.display = anyOver ? 'block' : 'none';
            }

            lotInputs.forEach(input => {
                attachSelectAllOnFocus(input);
                input.addEventListener('input', recalcLotBalances);
            });
            recalcLotBalances();

            // mobile: readonly lot usage
            const isMobile = window.matchMedia('(max-width: 767.98px)').matches;
            if (isMobile) lotInputs.forEach(input => input.readOnly = true);

            // ===== QC ADJUST (owner modal): clamp ok+reject <= cut =====
            document.querySelectorAll('.qc-adjust-form').forEach(form => {
                const okInput = form.querySelector('.input-adjust-ok');
                const rejInput = form.querySelector('.input-adjust-reject');
                const warn = form.querySelector('.qc-adjust-warning');
                if (!okInput || !rejInput) return;

                attachSelectAllOnFocus(okInput);
                attachSelectAllOnFocus(rejInput);

                function clampAdjust() {
                    const max = parseFloat(okInput.dataset.max || '0') || 0;

                    let ok = parseFloat(okInput.value || '0');
                    let rej = parseFloat(rejInput.value || '0');

                    if (isNaN(ok) || ok < 0) ok = 0;
                    if (isNaN(rej) || rej < 0) rej = 0;

                    if (ok > max) ok = max;
                    if (rej > max) rej = max;

                    if (ok + rej > max) {
                        // turunkan reject dulu
                        rej = Math.max(0, max - ok);
                        if (warn) warn.style.display = 'block';
                    } else {
                        if (warn) warn.style.display = 'none';
                    }

                    okInput.value = Math.round(ok);
                    rejInput.value = Math.round(rej);
                }

                okInput.addEventListener('input', clampAdjust);
                rejInput.addEventListener('input', clampAdjust);
                clampAdjust();
            });
        });
    </script>
@endpush
