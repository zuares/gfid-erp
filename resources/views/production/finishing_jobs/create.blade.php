@extends('layouts.app')

@section('title', 'Produksi • Finishing')

@push('head')
    <style>
        /* GFID-like padding & polished card */
        .finishing-create-page {
            min-height: 100vh;
            padding-block: 2.25rem;
            background: linear-gradient(180deg, rgba(250, 250, 252, 1) 0%, rgba(245, 247, 250, 1) 100%);
        }

        .finishing-create-page .page-wrap {
            max-width: 1200px;
            margin-inline: auto;
            padding: 1.5rem;
        }

        .card-main {
            background: var(--card);
            border-radius: 14px;
            border: 1px solid rgba(148, 163, 184, 0.18);
            box-shadow:
                0 10px 30px rgba(2, 6, 23, 0.08),
                0 4px 12px rgba(2, 6, 23, 0.04);
            overflow: hidden;
        }

        .card-header-bar {
            padding: 1rem 1.25rem;
            border-bottom: 1px solid rgba(148, 163, 184, 0.06);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
        }

        .card-header-title h1 {
            font-size: 1.15rem;
            margin: 0;
            font-weight: 600;
        }

        .card-header-subtitle {
            font-size: .82rem;
            color: var(--muted-foreground);
        }

        .card-body-main {
            padding: 1.25rem;
        }

        .section-title {
            font-size: .78rem;
            font-weight: 700;
            text-transform: uppercase;
            color: var(--muted-foreground);
        }

        .summary-pill {
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            padding: .35rem .65rem;
            border-radius: 999px;
            background: rgba(15, 23, 42, 0.03);
            font-size: .82rem;
            border: 1px dashed rgba(148, 163, 184, 0.12);
        }

        .finishing-table-wrap {
            margin-top: .75rem;
            border-radius: 10px;
            overflow: hidden;
            border: 1px solid rgba(148, 163, 184, 0.06);
            background: var(--card);
        }

        .finishing-table thead th {
            background: rgba(15, 23, 42, 0.02);
            font-size: .75rem;
            text-transform: uppercase;
            color: var(--muted-foreground);
            padding: .6rem;
            white-space: nowrap;
        }

        .finishing-table tbody td {
            padding: .5rem .6rem;
            vertical-align: middle;
            font-size: .88rem;
        }

        .wip-badge {
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            padding: .25rem .45rem;
            border-radius: 999px;
            background: rgba(16, 185, 129, 0.06);
            color: #064e3b;
            font-weight: 600;
            font-size: .85rem;
        }

        .form-control-sm,
        .form-select-sm {
            font-size: .88rem;
            padding: .35rem .5rem;
        }

        .btn-save {
            border-radius: 999px;
            padding: .45rem 1rem;
        }

        @media (max-width:767.98px) {
            .card-header-bar {
                flex-direction: column;
                align-items: flex-start;
                gap: .5rem;
            }

            .page-wrap {
                padding: 1rem;
            }
        }
    </style>
@endpush

@section('content')
    <div class="finishing-create-page">
        <div class="page-wrap">

            {{-- Flash / Errors --}}
            @if (session('status'))
                <div class="alert alert-success">{{ session('status') }}</div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger">
                    <strong>Oops!</strong> Ada error input, cek form.
                </div>
            @endif

            <div class="card card-main">
                <div class="card-header-bar">
                    <div class="card-header-title">
                        <h1>Finishing</h1>
                        <div class="card-header-subtitle">Proses hasil jahit per item dari WIP-FIN → Finished / Reject</div>
                    </div>

                    <div class="d-flex gap-2 align-items-center">
                        <div class="summary-pill">
                            <i class="bi bi-info-circle"></i>
                            <span>Input angka harus bulat (pcs)</span>
                        </div>
                    </div>
                </div>

                <form id="finishing-form" action="{{ route('production.finishing_jobs.store') }}" method="POST" novalidate>
                    @csrf

                    <div class="card-body-main">
                        @php
                            $userRole = auth()->user()->role ?? null;
                            $dateValue = old('date', $dateDefault ?? now()->toDateString());
                            $defaultOperator = old('operator_global_id') ?? (auth()->user()->employee_id ?? '');
                        @endphp

                        <div class="row g-3 mb-3">
                            <div class="col-6 col-md-3">
                                <label class="form-label form-label-sm">Tanggal</label>
                                <input type="date" name="date"
                                    class="form-control form-control-sm @error('date') is-invalid @enderror"
                                    value="{{ $dateValue }}">
                                @error('date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            @if ($userRole !== 'operating')
                                <div class="col-6 col-md-3">
                                    <label class="form-label form-label-sm">Operator jahit</label>
                                    <select name="operator_global_id"
                                        class="form-select form-select-sm @error('operator_global_id') is-invalid @enderror">
                                        <option value="">- pilih operator -</option>
                                        @foreach ($operators as $op)
                                            <option value="{{ $op->id }}" @selected((int) $defaultOperator === (int) $op->id)>
                                                {{ $op->code ?? $op->id }} — {{ $op->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('operator_global_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-12 col-md-6">
                                    <label class="form-label form-label-sm">Catatan</label>
                                    <textarea name="notes" rows="1" class="form-control form-control-sm">@json(old('notes', ''))</textarea>
                                    @error('notes')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            @else
                                <input type="hidden" name="operator_global_id" value="">
                            @endif
                        </div>

                        {{-- Summary --}}
                        @php $grandWip = collect($lines ?? [])->sum('total_wip'); @endphp
                        <div class="mb-3 d-flex flex-wrap gap-2 align-items-center">
                            <div class="section-title">Ringkasan WIP-FIN</div>
                            <div class="summary-pill"><i class="bi bi-collection"></i>
                                <strong>{{ number_format($grandWip, 0, ',', '.') }}</strong> pcs</div>
                            <div class="summary-pill"><i class="bi bi-box-seam"></i>
                                <strong>{{ count($lines ?? []) }}</strong> item</div>
                        </div>

                        {{-- Table --}}
                        <div class="finishing-table-wrap">
                            <div class="table-responsive">
                                <table class="table table-sm finishing-table mb-0">
                                    <thead>
                                        <tr>
                                            <th class="text-center" style="width:6%;">No</th>
                                            <th style="width:30%;">Item</th>
                                            <th class="text-end" style="width:16%;">Total WIP-FIN</th>
                                            <th class="text-end" style="width:16%;">Qty Proses</th>
                                            <th class="text-end" style="width:16%;">Qty Reject</th>
                                            <th style="width:16%;">Alasan Reject</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($lines as $idx => $line)
                                            @php
                                                $oldLines = old('lines', []);
                                                $oldLine = $oldLines[$idx] ?? [];
                                                $itemId = $oldLine['item_id'] ?? $line['item_id'];
                                                $totalWip = (int) ($oldLine['total_wip'] ?? ($line['total_wip'] ?? 0));
                                                $qtyIn = isset($oldLine['qty_in'])
                                                    ? (int) $oldLine['qty_in']
                                                    : $line['qty_in'] ?? null;
                                                $qtyReject = isset($oldLine['qty_reject'])
                                                    ? (int) $oldLine['qty_reject']
                                                    : $line['qty_reject'] ?? 0;
                                                $rejectReason =
                                                    $oldLine['reject_reason'] ?? ($line['reject_reason'] ?? '');
                                                $fullLabel = $line['item_label'] ?? 'Item #' . $itemId;
                                            @endphp
                                            <tr>
                                                <td class="text-center align-middle">{{ $loop->iteration }}</td>
                                                <td>
                                                    <div class="fw-600">{{ $fullLabel }}</div>
                                                    <div class="text-muted small">Item ID: {{ $itemId }}</div>

                                                    <input type="hidden" name="lines[{{ $idx }}][item_id]"
                                                        value="{{ $itemId }}">
                                                    <input type="hidden" name="lines[{{ $idx }}][total_wip]"
                                                        value="{{ $totalWip }}">
                                                </td>

                                                <td class="text-end align-middle">
                                                    <span class="wip-badge">
                                                        <i class="bi bi-arrow-up-circle"></i>
                                                        <span>{{ number_format($totalWip, 0, ',', '.') }}</span>
                                                        <small>pcs</small>
                                                    </span>
                                                </td>

                                                <td class="text-end align-middle">
                                                    <input type="number" name="lines[{{ $idx }}][qty_in]"
                                                        class="form-control form-control-sm text-end integer-input"
                                                        value="{{ is_null($qtyIn) ? '' : (int) $qtyIn }}" min="0"
                                                        max="{{ $totalWip }}" step="1" inputmode="numeric"
                                                        pattern="\d*">
                                                    @error("lines.$idx.qty_in")
                                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                                    @enderror
                                                </td>

                                                <td class="text-end align-middle">
                                                    <input type="number" name="lines[{{ $idx }}][qty_reject]"
                                                        class="form-control form-control-sm text-end integer-input"
                                                        value="{{ (int) $qtyReject }}" min="0" step="1"
                                                        inputmode="numeric" pattern="\d*">
                                                    @error("lines.$idx.qty_reject")
                                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                                    @enderror
                                                </td>

                                                <td class="align-middle">
                                                    <input type="text" name="lines[{{ $idx }}][reject_reason]"
                                                        class="form-control form-control-sm"
                                                        placeholder="Alasan singkat (opsional)"
                                                        value="{{ $rejectReason }}">
                                                    @error("lines.$idx.reject_reason")
                                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                                    @enderror
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="6" class="text-center py-4 text-muted">Tidak ada WIP-FIN
                                                    yang siap finishing.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        {{-- Footer actions --}}
                        <div class="footer-actions mt-3 d-flex justify-content-end gap-2">
                            <a href="{{ route('production.finishing_jobs.index') }}"
                                class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-arrow-left"></i> Kembali
                            </a>
                            <button type="submit" class="btn btn-sm btn-success btn-save">
                                <i class="bi bi-check2-circle"></i> Simpan
                            </button>
                        </div>
                    </div>
                </form>
            </div>

        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Semua input integer memiliki kelas .integer-input
            const integerInputs = document.querySelectorAll('input.integer-input');

            // Helper: clean pasted text -> integer (1.5 -> 1)
            function pasteToInteger(text) {
                if (!text) return '';
                // if contains comma, normalize to dot
                const normalized = text.replace(',', '.').trim();
                // try parseFloat first (handles "1.5", " 2.0", etc)
                const num = parseFloat(normalized);
                if (!isNaN(num)) {
                    // floor to integer, but not negative
                    return Math.max(0, Math.floor(num)).toString();
                }
                // fallback: strip non-digits
                const digits = normalized.replace(/[^0-9]/g, '');
                return digits;
            }

            integerInputs.forEach(el => {
                // sanitize on input: keep only integer part, remove all non-digit
                el.addEventListener('input', function(e) {
                    const v = this.value;
                    // if includes dot/comma, keep integer part before separator
                    if (/[.,]/.test(v)) {
                        const intPart = v.split(/[.,]/)[0].replace(/[^0-9]/g, '') || '0';
                        if (this.value !== intPart) this.value = intPart;
                        return;
                    }
                    // otherwise remove non-digit characters
                    const clean = v.replace(/[^0-9]/g, '');
                    if (this.value !== clean) this.value = clean;
                });

                // handle paste (smart)
                el.addEventListener('paste', function(e) {
                    e.preventDefault();
                    const paste = (e.clipboardData || window.clipboardData).getData('text');
                    const cleaned = pasteToInteger(paste);
                    // insert cleaned at cursor position (no selection handling complexity here)
                    const start = this.selectionStart || 0;
                    const end = this.selectionEnd || 0;
                    const before = this.value.slice(0, start);
                    const after = this.value.slice(end);
                    this.value = (before + cleaned + after).replace(/[^0-9]/g, '');
                    // move caret after inserted
                    const pos = (before + cleaned).length;
                    this.setSelectionRange(pos, pos);
                    // trigger input event to cascade validations
                    this.dispatchEvent(new Event('input', {
                        bubbles: true
                    }));
                });

                // prevent mouse wheel changing the number while focused (annoyance)
                el.addEventListener('wheel', function(ev) {
                    if (document.activeElement === this) ev.preventDefault();
                }, {
                    passive: false
                });
            });

            // Guard: qty_reject must not exceed qty_in for each row
            const table = document.querySelector('.finishing-table');
            if (table) {
                table.addEventListener('input', function(e) {
                    const el = e.target;
                    if (!el.name) return;
                    const m = el.name.match(/lines\[(\d+)\]\[(\w+)\]/);
                    if (!m) return;
                    const idx = m[1];
                    const field = m[2];

                    const qtyInEl = document.querySelector(`[name="lines[${idx}][qty_in]"]`);
                    const qtyRejectEl = document.querySelector(`[name="lines[${idx}][qty_reject]"]`);

                    const qtyIn = Math.max(0, parseInt(qtyInEl?.value || 0));
                    let qtyReject = Math.max(0, parseInt(qtyRejectEl?.value || 0));

                    if (qtyReject > qtyIn) {
                        qtyReject = qtyIn;
                        if (qtyRejectEl) qtyRejectEl.value = qtyReject;
                    }

                    // enforce max on qty_in if present
                    if (field === 'qty_in' && qtyInEl) {
                        const max = parseInt(qtyInEl.getAttribute('max') || 0);
                        if (max > 0 && qtyIn > max) {
                            qtyInEl.value = max;
                            if (qtyRejectEl && parseInt(qtyRejectEl.value || 0) > max) {
                                qtyRejectEl.value = max;
                            }
                        }
                    }
                });
            }

            // Final sweep before submit: floor all integer inputs and ensure numeric-only
            const form = document.getElementById('finishing-form');
            if (form) {
                form.addEventListener('submit', function(e) {
                    integerInputs.forEach(i => {
                        let v = i.value || '';
                        // normalize comma -> dot and try parse float
                        if (/[.,]/.test(v)) {
                            v = v.replace(',', '.');
                            const f = parseFloat(v);
                            if (!isNaN(f)) {
                                i.value = Math.max(0, Math.floor(f));
                                return;
                            }
                        }
                        // otherwise keep digits only and floor
                        const digits = v.replace(/[^0-9]/g, '') || '0';
                        i.value = Math.max(0, Math.floor(parseInt(digits, 10))).toString();
                    });
                    // allow submit to continue (server will validate again)
                });
            }
        });
    </script>
@endpush
