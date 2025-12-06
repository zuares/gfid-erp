{{-- resources/views/production/qc/cutting_edit.blade.php --}}
@extends('layouts.app')

@section('title', 'Produksi • QC Cutting ' . $cuttingJob->code)

@push('head')
    <style>
        .page-wrap {
            max-width: 1080px;
            margin-inline: auto;
        }

        .card {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 16px;
        }

        .card-soft {
            background: color-mix(in srgb, var(--card) 84%, var(--line) 16%);
        }

        .mono {
            font-variant-numeric: tabular-nums;
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas;
        }

        .pill {
            display: inline-flex;
            align-items: center;
            gap: .25rem;
            padding: .15rem .6rem;
            border-radius: 999px;
            font-size: .78rem;
            border: 1px solid var(--line);
        }

        .badge-soft {
            border-radius: 999px;
            padding: .2rem .6rem;
            font-size: .75rem;
        }

        .section-title {
            font-size: .88rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .04em;
        }

        .field-label {
            font-size: .8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .04em;
        }

        .small-muted {
            font-size: .8rem;
            color: var(--muted);
        }

        .qc-table thead th {
            font-size: .8rem;
            text-transform: uppercase;
            letter-spacing: .04em;
        }

        /* STATUS STEPPER (hierarchy status cutting → kirim QC → hasil QC) */
        .status-stepper {
            display: flex;
            align-items: center;
            gap: .75rem;
            font-size: .78rem;
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

        /* Highlight baris yang ada reject */
        .row-has-reject {
            background: rgba(248, 113, 113, 0.03);
        }

        .row-has-reject .input-reject {
            border-color: rgba(248, 113, 113, 0.8);
            background-color: rgba(248, 113, 113, 0.08);
        }

        .row-has-reject .qc-card-header {
            border-left: 3px solid rgba(248, 113, 113, 0.7);
            padding-left: .45rem;
        }

        @media (max-width: 767.98px) {

            .page-wrap {
                padding-inline: .35rem;
            }

            .header-grid {
                display: grid;
                grid-template-columns: minmax(0, 1.8fr) minmax(0, 1.3fr);
                gap: .5rem;
            }

            .table-wrap {
                overflow: visible;
            }

            .qc-table-mobile {
                border-collapse: separate;
                border-spacing: 0 .5rem;
            }

            .qc-table-mobile thead {
                display: none;
            }

            .qc-table-mobile tbody tr {
                display: block;
                background: var(--card);
                border-radius: 14px;
                border: 1px solid var(--line);
                padding: .55rem .7rem .6rem;
            }

            .qc-table-mobile tbody tr td {
                display: block;
                border: 0;
                padding: .15rem 0;
            }

            /* header kecil di dalam card: bundle no + code */
            .qc-table-mobile tbody tr td.qc-card-header {
                padding-bottom: .3rem;
                margin-bottom: .2rem;
                border-bottom: 1px dashed var(--line);
            }

            .qc-table-mobile tbody tr td[data-label]::before {
                content: attr(data-label);
                display: block;
                font-size: .75rem;
                font-weight: 600;
                text-transform: uppercase;
                letter-spacing: .05em;
                margin-bottom: .05rem;
            }

            .qc-table-mobile {
                font-size: .85rem;
            }

            .qc-table-mobile input.form-control-sm {
                font-size: .8rem;
                padding-top: .15rem;
                padding-bottom: .15rem;
            }

            .qc-summary-inline {
                display: flex;
                flex-wrap: wrap;
                gap: .25rem .4rem;
                font-size: .78rem;
            }

            .status-stepper {
                flex-direction: column;
                align-items: flex-start;
            }

            .status-separator {
                width: 22px;
            }
        }
    </style>
@endpush

@section('content')
    @php
        $lot = $cuttingJob->lot;
        $warehouse = $cuttingJob->warehouse;

        // Default operator dari user login (kalau ada)
        $defaultOperatorId = old('operator_id', $loginOperator->id ?? null);
        $defaultOperatorLabel = $loginOperator
            ? ($loginOperator->code ?? 'OP') . ' — ' . ($loginOperator->name ?? 'Operator')
            : 'User login';

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

        // mapping status ke step
        // step 1: cutting selesai, step 2: dikirim QC, step 3: hasil QC
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
    @endphp

    <div class="page-wrap">

        {{-- =======================
             HEADER JOB
        ======================== --}}
        <div class="card card-soft p-3 mb-3">
            <div class="d-none d-md-flex justify-content-between align-items-center gap-3">
                <div>
                    <div class="section-title mb-1">QC Cutting</div>
                    <h1 class="h5 mb-1 mono">{{ $cuttingJob->code }}</h1>
                    <div class="small-muted">
                        LOT {{ $lot?->code ?? '-' }} • {{ $lot?->item?->code ?? '-' }} •
                        Gudang {{ $warehouse?->code ?? '-' }}
                    </div>

                    {{-- status stepper: fokus hierarchy proses --}}
                    <div class="status-stepper mt-2">
                        <div class="status-step">
                            <div
                                class="status-dot {{ $step1State === 'current' ? 'current' : ($step1State === 'done' ? 'active' : '') }}">
                            </div>
                            <div
                                class="status-label {{ $step1State === 'current' ? 'current' : ($step1State === 'done' ? 'done' : '') }}">
                                Cutting Selesai
                            </div>
                        </div>
                        <div class="status-separator"></div>
                        <div class="status-step">
                            <div
                                class="status-dot {{ $step2State === 'current' ? 'current' : ($step2State === 'done' ? 'active' : '') }}">
                            </div>
                            <div
                                class="status-label {{ $step2State === 'current' ? 'current' : ($step2State === 'done' ? 'done' : '') }}">
                                Dikirim ke QC
                            </div>
                        </div>
                        <div class="status-separator"></div>
                        <div class="status-step">
                            <div
                                class="status-dot {{ $step3State === 'current' ? 'current' : ($step3State === 'done' ? 'active' : '') }}">
                            </div>
                            <div
                                class="status-label {{ $step3State === 'current' ? 'current' : ($step3State === 'done' ? 'done' : '') }}">
                                Hasil QC
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex flex-column align-items-end gap-2">
                    <span class="badge bg-{{ $statusClass }} px-3 py-2">
                        {{ strtoupper($cuttingJob->status) }}
                    </span>

                    <div class="d-flex gap-2">
                        <a href="{{ route('production.cutting_jobs.show', $cuttingJob) }}"
                            class="btn btn-sm btn-outline-secondary">
                            Kembali
                        </a>
                    </div>
                </div>
            </div>

            {{-- MOBILE HEADER --}}
            <div class="d-block d-md-none">
                <div class="header-grid align-items-start mb-2">
                    <div>
                        <div class="section-title mb-1">QC Cutting</div>
                        <div class="fw-semibold mono">{{ $cuttingJob->code }}</div>
                        <div class="small-muted mt-1">
                            LOT {{ $lot?->code ?? '-' }} • {{ $lot?->item?->code ?? '-' }}
                        </div>
                    </div>
                    <div class="text-end">
                        <span class="badge bg-{{ $statusClass }} px-2 py-1 mb-2">
                            {{ strtoupper($cuttingJob->status) }}
                        </span>
                        <div>
                            <a href="{{ route('production.cutting_jobs.show', $cuttingJob) }}"
                                class="btn btn-sm btn-outline-secondary">
                                Kembali
                            </a>
                        </div>
                    </div>
                </div>

                {{-- status stepper mobile --}}
                <div class="status-stepper mt-1">
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
                            Hasil QC
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <form action="{{ route('production.qc.cutting.update', $cuttingJob) }}" method="post">
            @csrf
            @method('PUT')

            {{-- =======================
                 HEADER QC
            ======================== --}}
            <div class="card p-3 mb-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div class="section-title mb-0">Header QC</div>

                    <div class="d-none d-md-flex gap-2">
                        <div class="pill">
                            <span>Total OK</span>
                            <span class="mono" id="sum-ok">0,00</span>
                        </div>
                        <div class="pill">
                            <span>Reject</span>
                            <span class="mono" id="sum-reject">0,00</span>
                        </div>
                    </div>

                    {{-- Mobile summary inline --}}
                    <div class="d-block d-md-none qc-summary-inline">
                        <span>Total OK <span class="mono" id="sum-ok-mobile">0,00</span></span>
                        <span>Reject <span class="mono" id="sum-reject-mobile">0,00</span></span>
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-md-3 col-6">
                        <label class="field-label mb-1">Tanggal QC</label>
                        <input type="date" name="qc_date" value="{{ old('qc_date', now()->toDateString()) }}"
                            class="form-control @error('qc_date') is-invalid @enderror">
                        @error('qc_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-4 col-12">
                        <label class="field-label mb-1">Operator QC</label>

                        {{-- hidden supaya operator_id tetap terkirim --}}
                        <input type="hidden" name="operator_id" value="{{ $defaultOperatorId }}">

                        {{-- tampilan hanya-baca --}}
                        <input type="text" class="form-control @error('operator_id') is-invalid @enderror"
                            value="{{ $defaultOperatorLabel }}" disabled>

                        @error('operator_id')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12">
                        <label class="field-label mb-1">Catatan Umum</label>
                        <textarea name="notes_global" rows="2" class="form-control"
                            placeholder="Opsional. Mis: hasil QC cutting lot ini secara umum.">{{ old('notes_global') }}</textarea>
                    </div>
                </div>
            </div>

            {{-- =======================
                 TABEL / CARD BUNDLES
            ======================== --}}
            <div class="card p-3 mb-4">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div class="section-title mb-0">QC per Bundle</div>
                    {{-- Fokus ke reject --}}
                    <div class="small-muted">
                        Isi <strong>Reject</strong> hanya jika ada cacat. Baris dengan reject akan di-highlight.
                    </div>
                </div>

                <div class="table-wrap">
                    @php
                        $hasAnyReject = false;
                    @endphp
                    <table class="table table-sm align-middle mono qc-table qc-table-mobile">
                        <thead>
                            <tr>
                                <th style="width:70px;">Bundle</th>
                                <th>Item</th>
                                <th class="text-end" style="width:120px;">Cutting</th>
                                <th class="text-end" style="width:120px;">OK</th>
                                <th class="text-end" style="width:120px;">Reject</th>
                                <th style="width:180px;">Alasan</th>
                                <th class="d-none d-md-table-cell" style="width:110px;">Status</th>
                                <th class="d-none d-md-table-cell" style="width:160px;">Catatan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($rows as $i => $row)
                                @php
                                    $qtyBundle = (float) $row['qty_pcs'];
                                    $qtyRejectOld = old("results.$i.qty_reject", $row['qty_reject'] ?? 0);
                                    $qtyReject = (float) $qtyRejectOld;
                                    if ($qtyReject < 0) {
                                        $qtyReject = 0;
                                    }
                                    if ($qtyReject > $qtyBundle) {
                                        $qtyReject = $qtyBundle;
                                    }
                                    $qtyOk = max($qtyBundle - $qtyReject, 0);
                                    if ($qtyReject > 0) {
                                        $hasAnyReject = true;
                                    }
                                @endphp
                                <tr class="{{ $qtyReject > 0 ? 'row-has-reject' : '' }}">
                                    {{-- cutting_job_bundle_id --}}
                                    <input type="hidden" name="results[{{ $i }}][cutting_job_bundle_id]"
                                        value="{{ $row['cutting_job_bundle_id'] }}">

                                    {{-- hidden qty_ok --}}
                                    <input type="hidden" name="results[{{ $i }}][qty_ok]"
                                        class="input-ok-hidden" value="{{ old("results.$i.qty_ok", $qtyOk) }}">

                                    {{-- Header card (mobile) / Bundle (desktop) --}}
                                    <td class="qc-card-header" data-label="Bundle">
                                        <div class="fw-semibold mono">
                                            #{{ $row['bundle_no'] ?? '-' }}
                                        </div>
                                        <div class="small-muted mono">
                                            {{ $row['bundle_code'] ?? '' }}
                                        </div>
                                    </td>

                                    {{-- Item --}}
                                    <td data-label="Item">
                                        <div>{{ $row['item_code'] }}</div>
                                    </td>

                                    {{-- Cutting --}}
                                    <td data-label="Cutting" class="text-end">
                                        {{ number_format($qtyBundle, 2, ',', '.') }}
                                    </td>

                                    {{-- OK (auto) --}}
                                    <td data-label="OK" class="text-end">
                                        <span class="cell-ok">
                                            {{ number_format(old("results.$i.qty_ok", $qtyOk), 2, ',', '.') }}
                                        </span>
                                    </td>

                                    {{-- Reject input --}}
                                    <td data-label="Reject" class="text-end">
                                        <input type="number" step="1" min="0" inputmode="numeric"
                                            pattern="\d*" name="results[{{ $i }}][qty_reject]"
                                            class="form-control form-control-sm text-end input-reject @error("results.$i.qty_reject") is-invalid @enderror"
                                            value="{{ old("results.$i.qty_reject", $qtyReject) }}"
                                            data-bundle="{{ $qtyBundle }}">
                                        @error("results.$i.qty_reject")
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </td>

                                    {{-- Alasan Reject --}}
                                    <td data-label="Alasan">
                                        <input type="text" name="results[{{ $i }}][reject_reason]"
                                            class="form-control form-control-sm @error("results.$i.reject_reason") is-invalid @enderror"
                                            value="{{ old("results.$i.reject_reason", $row['reject_reason'] ?? '') }}"
                                            placeholder="mis: bolong, kotor">
                                        @error("results.$i.reject_reason")
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </td>

                                    {{-- Status (desktop saja) --}}
                                    <td data-label="Status" class="d-none d-md-table-cell">
                                        @php
                                            $st = $row['status'] ?: 'cut';
                                            $cls =
                                                [
                                                    'cut' => 'secondary',
                                                    'qc_ok' => 'success',
                                                    'qc_reject' => 'danger',
                                                    'qc_mixed' => 'warning',
                                                ][$st] ?? 'secondary';
                                        @endphp
                                        <span class="badge-soft bg-{{ $cls }}">
                                            {{ $st }}
                                        </span>
                                    </td>

                                    {{-- Catatan (desktop saja) --}}
                                    <td data-label="Catatan" class="d-none d-md-table-cell">
                                        <input type="text" name="results[{{ $i }}][notes]"
                                            class="form-control form-control-sm @error("results.$i.notes") is-invalid @enderror"
                                            value="{{ old("results.$i.notes", $row['notes'] ?? '') }}">
                                        @error("results.$i.notes")
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @error('results')
                    <div class="text-danger small mt-2">{{ $message }}</div>
                @enderror

                <div id="qc-warning" class="text-danger small mt-2" style="display:none;">
                    ⚠️ Qty Reject tidak boleh melebihi Qty Cutting. Nilai otomatis dikunci ke batas maksimum.
                </div>

                @if ($hasAnyReject)
                    <div class="text-warning small mt-2">
                        ⚠️ Terdapat bundle dengan reject. Pastikan alasan reject sudah terisi dengan jelas.
                    </div>
                @endif
            </div>

            {{-- =======================
                 ACTION BUTTON
            ======================== --}}
            <div class="d-flex justify-content-end mb-5 gap-2">
                <a href="{{ route('production.cutting_jobs.show', $cuttingJob) }}" class="btn btn-outline-secondary">
                    Batal
                </a>
                <button type="submit" class="btn btn-primary">
                    Simpan Hasil QC
                </button>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
    <script>
        const inputsReject = document.querySelectorAll('.input-reject');
        const sumOkSpan = document.getElementById('sum-ok');
        const sumRejectSpan = document.getElementById('sum-reject');

        // mirror ke mobile summary
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
                }

                rejInput.value = rej;

                const ok = maxBundle - rej;

                if (okHidden) {
                    okHidden.value = ok;
                }

                if (okCell) {
                    okCell.textContent = ok.toFixed(2).replace('.', ',');
                }

                totalOk += ok;
                totalReject += rej;

                // toggle highlight per-row
                if (rej > 0) {
                    tr.classList.add('row-has-reject');
                } else {
                    tr.classList.remove('row-has-reject');
                }
            });

            const okText = totalOk.toFixed(2).replace('.', ',');
            const rejText = totalReject.toFixed(2).replace('.', ',');

            if (sumOkSpan) sumOkSpan.textContent = okText;
            if (sumRejectSpan) sumRejectSpan.textContent = rejText;

            if (sumOkMobileSpan) sumOkMobileSpan.textContent = okText;
            if (sumRejectMobileSpan) sumRejectMobileSpan.textContent = rejText;

            if (warningEl) {
                warningEl.style.display = anyOver ? 'block' : 'none';
            }
        }

        inputsReject.forEach(i => {
            attachSelectAllOnFocus(i);
            i.addEventListener('input', recalcTotals);
        });

        // inisialisasi awal
        recalcTotals();

        // FOCAL POINT: kalau ada reject, scroll dan fokus ke input pertama yang > 0
        window.addEventListener('load', () => {
            const firstWithReject = Array.from(inputsReject).find(i => {
                const v = parseFloat(i.value || '0');
                return !isNaN(v) && v > 0;
            });

            if (firstWithReject) {
                firstWithReject.scrollIntoView({
                    behavior: 'smooth',
                    block: 'center'
                });
                firstWithReject.focus();
            }
        });
    </script>
@endpush
