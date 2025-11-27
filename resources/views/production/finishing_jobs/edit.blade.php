{{-- resources/views/production/finishing_jobs/edit.blade.php --}}
@extends('layouts.app')

@section('title', 'Produksi • Edit Finishing Job ' . $job->code)

@push('head')
    <style>
        .page-wrap {
            max-width: 1100px;
            margin-inline: auto;
            padding-block: 0.75rem 1.5rem;
        }

        .card {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 16px;
            box-shadow:
                0 14px 30px rgba(15, 23, 42, 0.06),
                0 1px 0 rgba(15, 23, 42, 0.04);
        }

        .mono {
            font-variant-numeric: tabular-nums;
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas;
        }

        .help {
            color: var(--muted);
            font-size: .84rem;
        }

        .page-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .page-title-wrap {
            display: flex;
            align-items: center;
            gap: .9rem;
        }

        .page-icon {
            width: 38px;
            height: 38px;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: color-mix(in srgb, var(--primary, #0d6efd) 10%, var(--card) 90%);
            border: 1px solid color-mix(in srgb, var(--primary, #0d6efd) 30%, var(--line) 70%);
            font-size: 1.1rem;
        }

        .page-title {
            margin: 0;
            font-size: 1.1rem;
            font-weight: 600;
        }

        .page-subtitle {
            font-size: .85rem;
            color: var(--muted);
        }

        .badge-step {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            padding: .08rem .55rem;
            border-radius: 999px;
            background: color-mix(in srgb, var(--card) 60%, var(--line) 40%);
            font-size: .75rem;
            color: var(--muted);
        }

        .badge-step span {
            display: inline-flex;
            width: 16px;
            height: 16px;
            border-radius: 999px;
            align-items: center;
            justify-content: center;
            font-size: .7rem;
            background: rgba(13, 110, 253, 0.12);
            color: #0d6efd;
        }

        .section-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: .75rem;
            margin-bottom: .75rem;
        }

        .section-title {
            font-size: .95rem;
            font-weight: 600;
            margin-bottom: .25rem;
        }

        .section-actions {
            display: flex;
            align-items: center;
            gap: .5rem;
        }

        .table-wrap {
            overflow-x: auto;
            padding-inline: .25rem;
            padding-bottom: .25rem;
        }

        .table-finishing {
            margin-bottom: 0;
            border-collapse: separate;
            border-spacing: 0;
        }

        .table-finishing thead th {
            font-size: .78rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .04em;
            border-bottom-width: 1px;
            background: color-mix(in srgb, var(--card) 80%, var(--line) 20%);
            position: sticky;
            top: 0;
            z-index: 2;
        }

        .table-finishing tbody tr.line-row {
            transition: background-color .12s ease, box-shadow .12s ease, transform .08s ease;
        }

        .table-finishing tbody tr.line-row:hover {
            background: color-mix(in srgb, var(--card) 80%, var(--line) 20%);
        }

        .table-finishing tbody tr.line-row:focus-within {
            outline: 2px solid rgba(13, 110, 253, 0.25);
            outline-offset: -1px;
        }

        .is-soft-invalid {
            border-color: #f59f00 !important;
            background-color: rgba(245, 159, 0, 0.06) !important;
        }

        .footer-note {
            display: flex;
            flex-wrap: wrap;
            gap: .5rem;
            align-items: center;
            font-size: .84rem;
            color: var(--muted);
        }

        .pill-info {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            padding: .1rem .55rem;
            border-radius: 999px;
            font-size: .78rem;
            background: color-mix(in srgb, var(--card) 70%, var(--line) 30%);
        }

        .pill-info i {
            font-size: .9rem;
        }

        @media (max-width: 767.98px) {
            .page-wrap {
                padding-inline: .65rem;
            }

            .card {
                border-radius: 14px;
                box-shadow:
                    0 10px 25px rgba(15, 23, 42, 0.06),
                    0 1px 0 rgba(15, 23, 42, 0.04);
            }

            .page-title {
                font-size: 1rem;
            }

            .page-subtitle {
                font-size: .8rem;
            }

            .table-wrap {
                padding-inline: 0;
            }

            .table-finishing {
                border-spacing: 0 0.6rem;
            }

            .table-finishing thead {
                display: none;
            }

            .table-finishing tbody tr.line-row {
                background: var(--card);
                border-radius: 14px;
                border: 1px solid var(--line);
                box-shadow:
                    0 10px 24px rgba(15, 23, 42, 0.04),
                    0 0 0 1px rgba(15, 23, 42, 0.02);
            }

            .table-finishing tbody tr.line-row>td {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: .28rem .75rem;
                border-top: none !important;
            }

            .table-finishing tbody tr.line-row>td:first-child {
                padding-top: .55rem;
            }

            .table-finishing tbody tr.line-row>td:last-child {
                padding-bottom: .45rem;
            }

            .table-finishing tbody tr.line-row>td[data-label]:before {
                content: attr(data-label);
                font-size: .78rem;
                color: var(--muted);
                margin-right: .75rem;
                flex: 0 0 38%;
                max-width: 42%;
            }

            .table-finishing tbody tr.line-row>td[data-label].td-actions {
                justify-content: flex-end;
            }

            .table-finishing .item-label {
                max-width: 100%;
                white-space: normal;
            }

            .table-finishing .form-control-sm,
            .table-finishing .form-select-sm {
                font-size: .82rem;
                padding-block: .18rem;
                padding-inline: .35rem;
            }

            .footer-note {
                flex-direction: column;
                align-items: flex-start;
            }
        }
    </style>
@endpush

@section('content')
    <div class="page-wrap">

        {{-- VALIDATION ERROR --}}
        @if ($errors->any())
            <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
                <div class="fw-semibold mb-1">Terjadi kesalahan input:</div>
                <ul class="mb-0 small">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        {{-- HEADER --}}
        <div class="card p-3 p-md-3 mb-3">
            <div class="page-header">
                <div class="page-title-wrap">
                    <div class="page-icon">
                        <i class="bi bi-scissors"></i>
                    </div>
                    <div>
                        <h1 class="page-title">
                            Edit Finishing Job
                            <span class="mono">{{ $job->code }}</span>
                        </h1>
                        <div class="page-subtitle">
                            Ubah hasil finishing sebelum diposting / setelah di-unpost.
                        </div>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <a href="{{ route('production.finishing_jobs.show', $job->id) }}"
                        class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-arrow-left me-1"></i> Kembali ke Detail
                    </a>
                </div>
            </div>
        </div>

        {{-- FORM --}}
        <form action="{{ route('production.finishing_jobs.update', $job->id) }}" method="post">
            @csrf
            @method('PUT')

            {{-- HEADER FORM --}}
            <div class="card p-3 p-md-3 mb-3">
                <div class="section-header">
                    <div>
                        <div class="badge-step mb-2">
                            <span>1</span> Info utama
                        </div>
                        <div class="section-title">Detail Finishing</div>
                        <div class="help">
                            Sesuaikan tanggal proses & catatan jika diperlukan.
                        </div>
                    </div>
                </div>

                <div class="row g-3 mt-1">
                    <div class="col-md-4">
                        <label class="form-label small mb-1">Tanggal proses</label>
                        <input type="date" name="date" class="form-control form-control-sm"
                            value="{{ old('date', $job->date->format('Y-m-d')) }}" required>
                        <div class="help mt-1">
                            Biasanya tanggal fisik barang diproses di finishing.
                        </div>
                    </div>
                    <div class="col-md-8">
                        <label class="form-label small mb-1">Catatan (opsional)</label>
                        <textarea name="notes" class="form-control form-control-sm" rows="2"
                            placeholder="Misal: koreksi reject, revisi data.">{{ old('notes', $job->notes) }}</textarea>
                    </div>
                </div>
            </div>

            {{-- DETAIL LINES --}}
            <div class="card p-3 p-md-3 mb-3">
                <div class="section-header">
                    <div>
                        <div class="badge-step mb-2">
                            <span>2</span> Detail bundle
                        </div>
                        <div class="section-title">Bundle dari WIP-FIN</div>
                        <div class="help">
                            Qty In & Qty OK otomatis dari saldo WIP-FIN + Qty Reject.
                            Kamu hanya mengubah Qty Reject & alasan reject.
                        </div>
                    </div>
                    <div class="section-actions">
                        <button type="button" class="btn btn-sm btn-outline-primary" id="btn-add-line">
                            <i class="bi bi-plus-lg me-1"></i>
                            Tambah baris
                        </button>
                    </div>
                </div>

                @php
                    // Data untuk form:
                    // 1) kalau ada old('lines') -> pakai itu
                    // 2) kalau tidak, map dari $job->lines
                    $formLines = old('lines');
                    if (!$formLines) {
                        $formLines = $job->lines
                            ->map(function ($line) use ($bundles) {
                                $bundleModel = $bundles->firstWhere('id', $line->bundle_id);
                                $itemModel = $bundleModel?->finishedItem ?? ($bundleModel?->lot?->item ?? $line->item);

                                $itemLabel = $itemModel
                                    ? trim(
                                        ($itemModel->code ?? '') .
                                            ' — ' .
                                            ($itemModel->name ?? '') .
                                            ' ' .
                                            ($itemModel->color ?? ''),
                                    )
                                    : '';

                                $wipBalance = (float) ($bundleModel->wip_qty ?? 0);

                                return [
                                    'bundle_id' => $line->bundle_id,
                                    'item_id' => $itemModel?->id,
                                    'item_label' => $itemLabel,
                                    'wip_balance' => $wipBalance,
                                    'operator_id' => $line->operator_id,
                                    'qty_in' => $line->qty_in,
                                    'qty_ok' => $line->qty_ok,
                                    'qty_reject' => $line->qty_reject,
                                    'reject_reason' => $line->reject_reason,
                                    'reject_notes' => $line->reject_notes,
                                ];
                            })
                            ->toArray();
                    }
                @endphp

                <div class="table-wrap">
                    <table class="table table-sm align-middle table-finishing" id="lines-table">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 18%;">Bundle</th>
                                <th style="width: 20%;">Item</th>
                                <th style="width: 15%;">Operator</th>
                                <th class="text-end" style="width: 9%;">Saldo WIP-FIN</th>
                                <th class="text-end" style="width: 9%;">Qty In</th>
                                <th class="text-end" style="width: 9%;">OK</th>
                                <th class="text-end" style="width: 9%;">Reject</th>
                                <th style="width: 15%;">Alasan Reject</th>
                                <th style="width: 1%;"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($formLines as $i => $line)
                                @php
                                    $bundleModel = $bundles->firstWhere('id', $line['bundle_id'] ?? null);
                                    $itemModel = $bundleModel?->finishedItem ?? $bundleModel?->lot?->item;

                                    $itemId = $line['item_id'] ?? $itemModel?->id;

                                    $itemLabel =
                                        $line['item_label'] ??
                                        ($itemModel
                                            ? trim(
                                                ($itemModel->code ?? '') .
                                                    ' — ' .
                                                    ($itemModel->name ?? '') .
                                                    ' ' .
                                                    ($itemModel->color ?? ''),
                                            )
                                            : '');

                                    $wipBalance = $line['wip_balance'] ?? (float) ($bundleModel->wip_qty ?? 0);

                                    $qtyIn = $wipBalance; // behavior: selalu proses full saldo
                                    $qtyReject = $line['qty_reject'] ?? 0;
                                    $qtyOk = max(0, $qtyIn - $qtyReject);
                                @endphp

                                <tr class="line-row">
                                    {{-- BUNDLE --}}
                                    <td data-label="Bundle">
                                        <select name="lines[{{ $i }}][bundle_id]"
                                            class="form-select form-select-sm bundle-select"
                                            data-line-index="{{ $i }}">
                                            <option value="">Pilih bundle...</option>
                                            @foreach ($bundles as $bundle)
                                                @php
                                                    $optItem = $bundle->finishedItem ?? $bundle->lot?->item;
                                                    $optLabel = $optItem
                                                        ? trim(
                                                            ($optItem->code ?? '') .
                                                                ' — ' .
                                                                ($optItem->name ?? '') .
                                                                ' ' .
                                                                ($optItem->color ?? ''),
                                                        )
                                                        : '';
                                                    $optWip = (float) ($bundle->wip_qty ?? 0);
                                                    $optItemId =
                                                        $bundle->finished_item_id ?? ($bundle->lot?->item_id ?? '');
                                                @endphp
                                                <option value="{{ $bundle->id }}" data-item-id="{{ $optItemId }}"
                                                    data-item-label="{{ $optLabel }}"
                                                    data-wip-balance="{{ $optWip }}" @selected(($line['bundle_id'] ?? null) == $bundle->id)>
                                                    {{ $bundle->bundle_code }} ({{ number_format($optWip) }} pcs)
                                                </option>
                                            @endforeach
                                        </select>

                                        <input type="hidden" name="lines[{{ $i }}][item_id]"
                                            class="item-id-input" value="{{ $itemId }}">
                                    </td>

                                    {{-- ITEM --}}
                                    <td data-label="Item">
                                        <div class="small mono item-label">
                                            {{ $itemLabel }}
                                        </div>
                                        <input type="hidden" name="lines[{{ $i }}][item_label]"
                                            value="{{ $itemLabel }}">
                                        <input type="hidden" name="lines[{{ $i }}][wip_balance]"
                                            value="{{ $wipBalance }}">
                                    </td>

                                    {{-- OPERATOR --}}
                                    <td data-label="Operator">
                                        <select name="lines[{{ $i }}][operator_id]"
                                            class="form-select form-select-sm">
                                            <option value="">-</option>
                                            @foreach ($operators as $op)
                                                <option value="{{ $op->id }}" @selected(($line['operator_id'] ?? null) == $op->id)>
                                                    {{ $op->code ?? '' }} — {{ $op->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </td>

                                    {{-- SALDO WIP-FIN --}}
                                    <td class="text-end mono" data-label="Saldo WIP-FIN">
                                        <span class="wip-balance-label">
                                            {{ number_format($wipBalance) }}
                                        </span>
                                    </td>

                                    {{-- QTY IN --}}
                                    <td class="text-end" data-label="Qty In">
                                        <input type="number" min="0" step="1"
                                            name="lines[{{ $i }}][qty_in]"
                                            class="form-control form-control-sm text-end qty-in-input"
                                            value="{{ $qtyIn }}" readonly tabindex="-1">
                                    </td>

                                    {{-- QTY OK --}}
                                    <td class="text-end" data-label="OK">
                                        <input type="number" min="0" step="1"
                                            name="lines[{{ $i }}][qty_ok]"
                                            class="form-control form-control-sm text-end qty-ok-input"
                                            value="{{ $qtyOk }}" readonly tabindex="-1">
                                    </td>

                                    {{-- QTY REJECT --}}
                                    <td class="text-end" data-label="Reject">
                                        <input type="number" min="0" step="1"
                                            name="lines[{{ $i }}][qty_reject]"
                                            class="form-control form-control-sm text-end qty-reject-input"
                                            value="{{ $qtyReject }}">
                                    </td>

                                    {{-- ALASAN REJECT --}}
                                    <td data-label="Alasan Reject">
                                        <input type="text" name="lines[{{ $i }}][reject_reason]"
                                            class="form-control form-control-sm"
                                            value="{{ $line['reject_reason'] ?? '' }}" placeholder="Opsional">
                                    </td>

                                    {{-- DELETE --}}
                                    <td class="text-center td-actions" data-label="">
                                        <button type="button" class="btn btn-sm btn-link text-danger btn-remove-line"
                                            tabindex="-1">
                                            <i class="bi bi-x-lg"></i>
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center text-muted py-4">
                                        Tidak ada detail finishing untuk job ini.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="pt-2 mt-1 border-top">
                    <div class="help mb-1">
                        Qty In = saldo WIP-FIN, Qty OK = Qty In - Qty Reject.
                        Qty Reject tidak boleh lebih besar dari saldo WIP-FIN.
                    </div>
                </div>
            </div>

            {{-- FOOTER BUTTONS --}}
            <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
                <div class="footer-note">
                    <span>
                        Perubahan akan disimpan sebagai <strong>draft</strong>.
                        Stok baru bergerak setelah kamu klik <em>Posting</em> di halaman detail.
                    </span>
                    <span class="pill-info">
                        <i class="bi bi-shield-check"></i>
                        Data masih bisa diubah lagi sebelum diposting.
                    </span>
                </div>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        Simpan Perubahan
                    </button>
                </div>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
    <script>
        (function() {
            const tableBody = document.querySelector('#lines-table tbody');
            const btnAdd = document.getElementById('btn-add-line');

            function reindexLines() {
                const rows = tableBody.querySelectorAll('.line-row');
                rows.forEach((row, index) => {
                    row.querySelectorAll('select, input').forEach(input => {
                        const name = input.getAttribute('name');
                        if (!name) return;
                        const newName = name.replace(/lines\[\d+]/, 'lines[' + index + ']');
                        input.setAttribute('name', newName);
                    });

                    const bundleSelect = row.querySelector('.bundle-select');
                    if (bundleSelect) {
                        bundleSelect.dataset.lineIndex = index;
                    }
                });
            }

            // === LOGIKA: semua qty auto dari saldo & reject ===
            function sanitizeRow(row) {
                const qtyInInput = row.querySelector('.qty-in-input');
                const qtyOkInput = row.querySelector('.qty-ok-input');
                const qtyRejectInput = row.querySelector('.qty-reject-input');
                const hiddenWipBalance = row.querySelector('input[name*="[wip_balance]"]');

                if (!qtyInInput || !qtyOkInput || !qtyRejectInput || !hiddenWipBalance) {
                    return;
                }

                let wipBal = parseFloat(hiddenWipBalance.value || '0');
                if (isNaN(wipBal) || wipBal < 0) wipBal = 0;

                let qtyReject = parseFloat(qtyRejectInput.value || '0');
                if (isNaN(qtyReject)) qtyReject = 0;
                qtyReject = Math.max(0, qtyReject);

                // Reject tidak boleh melebihi saldo WIP-FIN
                if (qtyReject > wipBal) {
                    qtyReject = wipBal;
                }

                const qtyIn = wipBal;
                const qtyOk = Math.max(0, qtyIn - qtyReject);

                qtyInInput.value = qtyIn;
                qtyOkInput.value = qtyOk;
                qtyRejectInput.value = qtyReject;

                const hasOver = (qtyOk + qtyReject) > qtyIn + 0.000001;
                [qtyInInput, qtyOkInput, qtyRejectInput].forEach(el => {
                    if (!el) return;
                    el.classList.toggle('is-soft-invalid', hasOver);
                });
            }

            function handleBundleChange(e) {
                const select = e.target;
                const row = select.closest('.line-row');
                const option = select.selectedOptions[0];
                if (!option || !row) return;

                const itemIdInput = row.querySelector('.item-id-input');
                const itemLabelEl = row.querySelector('.item-label');
                const wipBalanceEl = row.querySelector('.wip-balance-label');
                const qtyRejectInput = row.querySelector('.qty-reject-input');

                const itemId = option.getAttribute('data-item-id') || '';
                const itemLabel = option.getAttribute('data-item-label') || '';
                const wipBalStr = option.getAttribute('data-wip-balance') || '0';

                if (itemIdInput) itemIdInput.value = itemId;
                if (itemLabelEl) itemLabelEl.textContent = itemLabel;
                if (wipBalanceEl) wipBalanceEl.textContent = wipBalStr;

                const hiddenItemLabel = row.querySelector('input[name*="[item_label]"]');
                const hiddenWipBalance = row.querySelector('input[name*="[wip_balance]"]');
                if (hiddenItemLabel) hiddenItemLabel.value = itemLabel;
                if (hiddenWipBalance) hiddenWipBalance.value = wipBalStr;

                // Default: proses semua, tidak ada reject
                if (qtyRejectInput) qtyRejectInput.value = 0;

                sanitizeRow(row);
            }

            function addLineRow() {
                const lastRow = tableBody.querySelector('.line-row:last-child');
                if (!lastRow) return;

                const newRow = lastRow.cloneNode(true);

                newRow.querySelectorAll('input').forEach(input => {
                    if (input.type === 'hidden') {
                        if (input.name && input.name.includes('[wip_balance]')) {
                            input.value = '0';
                        } else {
                            input.value = '';
                        }
                        return;
                    }

                    if (['number', 'text'].includes(input.type)) {
                        if (input.classList.contains('qty-reject-input')) {
                            input.value = 0;
                        } else {
                            input.value = 0;
                        }
                    }
                });

                newRow.querySelectorAll('select').forEach(select => {
                    select.selectedIndex = 0;
                });

                const itemLabelEl = newRow.querySelector('.item-label');
                const wipBalanceEl = newRow.querySelector('.wip-balance-label');
                if (itemLabelEl) itemLabelEl.textContent = '';
                if (wipBalanceEl) wipBalanceEl.textContent = '0';

                tableBody.appendChild(newRow);
                reindexLines();
                sanitizeRow(newRow);
            }

            if (btnAdd) {
                btnAdd.addEventListener('click', addLineRow);
            }

            tableBody.addEventListener('click', function(e) {
                if (e.target.closest('.btn-remove-line')) {
                    const rows = tableBody.querySelectorAll('.line-row');
                    if (rows.length <= 1) return;
                    e.target.closest('.line-row').remove();
                    reindexLines();
                }
            });

            tableBody.addEventListener('change', function(e) {
                if (e.target.classList.contains('bundle-select')) {
                    handleBundleChange(e);
                }
            });

            tableBody.addEventListener('input', function(e) {
                if (e.target.classList.contains('qty-reject-input')) {
                    const row = e.target.closest('.line-row');
                    if (!row) return;
                    sanitizeRow(row);
                }
            });

            // Init: biar auto jalan pas buka halaman edit
            tableBody.querySelectorAll('.line-row').forEach(row => sanitizeRow(row));
        })();
    </script>
@endpush
