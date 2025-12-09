@extends('layouts.app')

@section('title', 'Produksi • Finishing')

@push('head')
    <style>
        .finishing-create-page {
            min-height: 100vh;
        }

        .finishing-create-page .page-wrap {
            max-width: 1150px;
            margin-inline: auto;
            padding: 1rem 1rem 4rem;
        }

        body[data-theme="light"] .finishing-create-page .page-wrap {
            background:
                radial-gradient(circle at top left,
                    rgba(59, 130, 246, 0.12) 0,
                    rgba(45, 212, 191, 0.10) 26%,
                    #f9fafb 60%);
        }

        body[data-theme="dark"] .finishing-create-page .page-wrap {
            background:
                radial-gradient(circle at top left,
                    rgba(59, 130, 246, 0.25) 0,
                    rgba(45, 212, 191, 0.15) 26%,
                    #020617 60%);
        }

        .finishing-create-page .card-main {
            background: var(--card);
            border-radius: 16px;
            border: 1px solid rgba(148, 163, 184, 0.35);
            box-shadow:
                0 14px 45px rgba(15, 23, 42, 0.22),
                0 10px 18px rgba(15, 23, 42, 0.18);
        }

        .finishing-create-page .card-header-bar {
            padding: 0.85rem 1.1rem;
            border-bottom: 1px solid rgba(148, 163, 184, 0.35);
            display: flex;
            align-items: center;
            gap: .75rem;
            justify-content: space-between;
        }

        .finishing-create-page .card-header-title {
            display: flex;
            flex-direction: column;
            gap: .15rem;
        }

        .finishing-create-page .card-header-title h1 {
            font-size: 1.15rem;
            font-weight: 600;
            letter-spacing: .02em;
            margin: 0;
        }

        .finishing-create-page .card-header-subtitle {
            font-size: .77rem;
            color: var(--muted-foreground);
        }

        .finishing-create-page .badge-soft-info {
            font-size: .7rem;
            border-radius: 999px;
            padding: .25rem .55rem;
            background: rgba(37, 99, 235, 0.08);
            border: 1px solid rgba(59, 130, 246, 0.25);
            color: #1d4ed8;
            display: inline-flex;
            align-items: center;
            gap: .25rem;
        }

        .finishing-create-page .card-body-main {
            padding: 1rem 1.1rem 1.1rem;
        }

        .finishing-create-page .section-title {
            font-size: .8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .12em;
            color: var(--muted-foreground);
            margin-bottom: .45rem;
        }

        .finishing-create-page .summary-pill {
            border-radius: 999px;
            border: 1px dashed rgba(148, 163, 184, 0.7);
            font-size: .75rem;
            padding: .3rem .75rem;
            display: inline-flex;
            align-items: center;
            gap: .25rem;
            background: rgba(15, 23, 42, 0.02);
        }

        .finishing-create-page .summary-pill strong {
            font-weight: 600;
        }

        /* TABLE */
        .finishing-table-wrap {
            border-radius: 12px;
            border: 1px solid rgba(148, 163, 184, 0.35);
            overflow: hidden;
            background: var(--card);
        }

        .finishing-table {
            margin-bottom: 0;
        }

        .finishing-table thead th {
            background: rgba(15, 23, 42, 0.03);
            font-size: .75rem;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: var(--muted-foreground);
            border-bottom-width: 1px;
            padding-block: .55rem;
            white-space: nowrap;
        }

        .finishing-table tbody td {
            vertical-align: middle;
            padding-block: .4rem;
            font-size: .8rem;
        }

        .finishing-table tbody tr:nth-child(even) {
            background: rgba(148, 163, 184, 0.07);
        }

        .finishing-create-page .item-label-main {
            font-weight: 600;
            font-size: .82rem;
        }

        .finishing-create-page .item-label-sub {
            font-size: .72rem;
            color: var(--muted-foreground);
        }

        .finishing-create-page .wip-badge {
            display: inline-flex;
            align-items: center;
            gap: .3rem;
            padding: .2rem .5rem;
            border-radius: 999px;
            font-size: .72rem;
            background: rgba(16, 185, 129, 0.08);
            color: #047857;
        }

        .finishing-create-page .wip-badge span {
            font-weight: 600;
        }

        .finishing-create-page .form-control-sm,
        .finishing-create-page .form-select-sm {
            font-size: .8rem;
            padding-block: .25rem;
        }

        .finishing-create-page .btn-save {
            border-radius: 999px;
            padding-inline: 1.5rem;
            box-shadow:
                0 10px 25px rgba(34, 197, 94, .35);
            font-weight: 600;
            letter-spacing: .03em;
        }

        .finishing-create-page .footer-actions {
            display: flex;
            justify-content: flex-end;
            gap: .75rem;
            margin-top: 1rem;
            padding-top: .75rem;
            border-top: 1px dashed rgba(148, 163, 184, 0.55);
        }

        @media (max-width: 767.98px) {
            .finishing-table-wrap {
                border-radius: 10px;
            }

            .finishing-table thead th {
                font-size: .68rem;
                padding-block: .4rem;
            }

            .finishing-table tbody td {
                font-size: .72rem;
                padding-block: .3rem;
            }

            .finishing-create-page .item-label-main {
                font-size: .78rem;
            }

            .finishing-create-page .item-label-sub {
                font-size: .68rem;
            }

            .finishing-create-page .wip-badge {
                font-size: .68rem;
                padding: .18rem .45rem;
            }

            .finishing-create-page .card-header-bar {
                flex-direction: column;
                align-items: flex-start;
            }

            .finishing-create-page .footer-actions {
                flex-direction: column-reverse;
                align-items: stretch;
            }

            .finishing-create-page .btn-save {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
@endpush

@section('content')
    <div class="finishing-create-page">
        <div class="page-wrap">

            {{-- FLASH & ERROR --}}
            @if (session('status'))
                <div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
                    {{ session('status') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
                    <strong>Oops!</strong> Ada beberapa error input. Silakan cek kembali form di bawah.
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <div class="card card-main">
                {{-- HEADER --}}
                <div class="card-header-bar">
                    <div class="card-header-title">
                        <h1>Finishing</h1>
                        <div class="card-header-subtitle">
                            Proses hasil jahit per item dari gudang WIP-FIN menjadi barang jadi (WH-PRD) &amp; reject.
                        </div>
                    </div>

                    <div class="d-flex flex-column flex-sm-row gap-2 align-items-sm-center">
                        <div class="badge-soft-info">
                            <i class="bi bi-info-circle"></i>
                            <span>Input per item, sistem akan pecah otomatis ke bundle.</span>
                        </div>
                    </div>
                </div>

                <form action="{{ route('production.finishing_jobs.store') }}" method="post">
                    @csrf

                    <div class="card-body-main">
                        @php
                            // fallback date
                            $dateDefault = old('date', $date ?? now()->toDateString());
                            $defaultGlobalOperator = old('operator_global_id') ?? (auth()->user()->employee_id ?? null);
                            $userRole = auth()->user()->role ?? null;
                            $linesOld = old('lines', []);
                        @endphp

                        {{-- HEADER FORM: Tanggal + Operator + Catatan (desktop only, non-operating) --}}
                        <div class="row g-3 mb-3">
                            <div class="col-6 col-md-3">
                                <label class="form-label form-label-sm mb-1">Tanggal</label>
                                <input type="date" name="date"
                                    class="form-control form-control-sm @error('date') is-invalid @enderror"
                                    value="{{ $dateDefault }}">
                                @error('date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            @if ($userRole !== 'operating')
                                <div class="col-6 col-md-3">
                                    <label class="form-label form-label-sm mb-1">Operator jahit</label>
                                    <select name="operator_global_id" id="operator_global_id"
                                        class="form-select form-select-sm @error('operator_global_id') is-invalid @enderror">
                                        <option value="">- pilih operator -</option>
                                        @foreach ($operators as $op)
                                            <option value="{{ $op->id }}" @selected((int) $defaultGlobalOperator === (int) $op->id)>
                                                {{ $op->code ?? $op->id }} — {{ $op->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('operator_global_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-12 col-md-6 d-none d-md-block">
                                    <label class="form-label form-label-sm mb-1">Catatan</label>
                                    <textarea name="notes" rows="1" class="form-control form-control-sm @error('notes') is-invalid @enderror"
                                        placeholder="Contoh: Finishing batch TSHIRT hitam &amp; HOODIE abu.">{{ old('notes') }}</textarea>
                                    @error('notes')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            @else
                                {{-- operating: tidak perlu operator_global / notes --}}
                                <input type="hidden" name="operator_global_id" value="">
                            @endif
                        </div>

                        {{-- SUMMARY WIP --}}
                        @php
                            $grandWip = collect($lines ?? [])->sum('total_wip');
                            $itemCount = count($lines ?? []);
                        @endphp
                        <div class="mb-3 d-flex flex-wrap gap-2 align-items-center">
                            <div class="section-title mb-0">Ringkasan WIP-FIN</div>

                            <div class="summary-pill">
                                <i class="bi bi-collection"></i>
                                <span>Total saldo WIP-FIN:</span>
                                <strong>{{ number_format($grandWip, 0, ',', '.') }} pcs</strong>
                            </div>

                            <div class="summary-pill">
                                <i class="bi bi-box-seam"></i>
                                <span>Total item siap Finishing:</span>
                                <strong>{{ $itemCount }} item</strong>
                            </div>
                        </div>

                        {{-- TABEL (DESKTOP + MOBILE) --}}
                        <div class="finishing-table-wrap mb-2">
                            <div class="table-responsive">
                                <table class="table table-sm finishing-table mb-0">
                                    <thead>
                                        <tr>
                                            <th style="width: 6%;" class="text-center">No</th>
                                            <th style="width: 30%;">Item</th>
                                            <th style="width: 16%;" class="text-end">Total WIP-FIN</th>
                                            <th style="width: 16%;" class="text-end">Qty Proses</th>
                                            <th style="width: 16%;" class="text-end">Qty Reject</th>
                                            <th style="width: 16%;">Alasan Reject</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($lines as $idx => $line)
                                            @php
                                                // gunakan old values bila ada
                                                $oldLine = $linesOld[$idx] ?? [];

                                                $itemId = $oldLine['item_id'] ?? ($line['item_id'] ?? null);
                                                $totalWip = $oldLine['total_wip'] ?? ($line['total_wip'] ?? 0);
                                                $qtyIn = $oldLine['qty_in'] ?? ($line['qty_in'] ?? $totalWip);
                                                $qtyReject = $oldLine['qty_reject'] ?? ($line['qty_reject'] ?? 0);
                                                $rejectReason =
                                                    $oldLine['reject_reason'] ?? ($line['reject_reason'] ?? '');

                                                $fullLabel =
                                                    $line['item_label'] ?? ($line['item_name'] ?? 'Item #' . $itemId);
                                                $codeOnly = $fullLabel;
                                                if (strpos($fullLabel, '—') !== false) {
                                                    [$codePart] = explode('—', $fullLabel, 2);
                                                    $codeOnly = trim($codePart);
                                                }
                                            @endphp
                                            <tr>
                                                {{-- NO --}}
                                                <td class="text-center align-middle">
                                                    {{ $loop->iteration }}
                                                </td>

                                                {{-- ITEM LABEL --}}
                                                <td>
                                                    <div class="item-label-main">
                                                        <span class="d-inline d-md-none">{{ $codeOnly }}</span>
                                                        <span class="d-none d-md-inline">{{ $fullLabel }}</span>
                                                    </div>
                                                    <div class="item-label-sub d-none d-md-block">
                                                        Item ID: {{ $itemId ?? '-' }}
                                                    </div>

                                                    <input type="hidden" name="lines[{{ $idx }}][item_id]"
                                                        value="{{ $itemId }}">
                                                    <input type="hidden" name="lines[{{ $idx }}][total_wip]"
                                                        value="{{ $totalWip }}">
                                                </td>

                                                {{-- TOTAL WIP --}}
                                                <td class="text-end">
                                                    <span class="wip-badge">
                                                        <i class="bi bi-arrow-up-circle"></i>
                                                        <span>{{ number_format($totalWip, 0, ',', '.') }}</span>
                                                        <small>pcs</small>
                                                    </span>
                                                    @error("lines.$idx.item_id")
                                                        <div class="text-danger small mt-1">{{ $message }}</div>
                                                    @enderror
                                                </td>

                                                {{-- QTY PROSES (input number native) --}}
                                                <td class="text-end">
                                                    <input type="number" name="lines[{{ $idx }}][qty_in]"
                                                        class="form-control form-control-sm text-end @error("lines.$idx.qty_in") is-invalid @enderror"
                                                        value="{{ $qtyIn }}" min="0" step="1"
                                                        max="{{ $totalWip }}">
                                                    @error("lines.$idx.qty_in")
                                                        <div class="invalid-feedback d-block text-start">{{ $message }}
                                                        </div>
                                                    @enderror
                                                </td>

                                                {{-- QTY REJECT --}}
                                                <td class="text-end">
                                                    <input type="number" name="lines[{{ $idx }}][qty_reject]"
                                                        class="form-control form-control-sm text-end @error("lines.$idx.qty_reject") is-invalid @enderror"
                                                        value="{{ $qtyReject }}" min="0" step="1">
                                                    @error("lines.$idx.qty_reject")
                                                        <div class="invalid-feedback d-block text-start">{{ $message }}
                                                        </div>
                                                    @enderror
                                                </td>

                                                {{-- ALASAN REJECT --}}
                                                <td>
                                                    <input type="text"
                                                        name="lines[{{ $idx }}][reject_reason]"
                                                        class="form-control form-control-sm @error("lines.$idx.reject_reason") is-invalid @enderror"
                                                        placeholder="Alasan singkat (opsional)"
                                                        value="{{ $rejectReason }}">
                                                    @error("lines.$idx.reject_reason")
                                                        <div class="invalid-feedback">{{ $message }}</div>
                                                    @enderror
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="6" class="text-center py-4 text-muted">
                                                    Tidak ada WIP-FIN yang siap finishing.
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        {{-- FOOTER ACTIONS --}}
                        <div class="footer-actions">
                            <a href="{{ route('production.finishing_jobs.index') }}"
                                class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-arrow-left"></i>
                                <span class="ms-1">Kembali</span>
                            </a>
                            <button type="submit"
                                class="btn btn-sm btn-success d-inline-flex align-items-center gap-1 btn-save">
                                <i class="bi bi-check2-circle"></i>
                                <span class="text-white">Simpan</span>
                            </button>
                        </div>
                    </div>
                </form>
            </div>

        </div>
    </div>
@endsection
