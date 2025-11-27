@extends('layouts.app')

@section('title', isset($job) ? 'Produksi • Edit Packing ' . $job->code : 'Produksi • Packing Baru')

@push('head')
    <style>
        .page-wrap {
            max-width: 1100px;
            margin-inline: auto;
            padding-block: .75rem 1.5rem;
        }

        .card {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 16px;
        }

        .mono {
            font-variant-numeric: tabular-nums;
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas;
        }

        .help {
            color: var(--muted);
            font-size: .84rem;
        }

        .table-wrap {
            overflow-x: auto;
            padding-inline: .25rem;
        }

        .table-packing {
            margin-bottom: 0;
            border-collapse: separate;
            border-spacing: 0;
        }

        .table-packing thead th {
            font-size: .78rem;
            text-transform: uppercase;
            letter-spacing: .04em;
        }

        .table-packing tbody tr.line-row {
            transition: background-color .12s ease;
        }

        .table-packing tbody tr.line-row:hover {
            background: color-mix(in srgb, var(--card) 82%, var(--line) 18%);
        }

        .is-soft-invalid {
            border-color: #f59f00 !important;
            background-color: rgba(245, 159, 0, 0.06) !important;
        }

        @media (max-width: 767.98px) {
            .page-wrap {
                padding-inline: .65rem;
            }

            .table-packing thead {
                display: none;
            }

            .table-packing {
                border-spacing: 0 .6rem;
            }

            .table-packing tbody tr.line-row {
                background: var(--card);
                border-radius: 14px;
                border: 1px solid var(--line);
            }

            .table-packing tbody tr.line-row>td {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: .3rem .75rem;
                border-top: none !important;
            }

            .table-packing tbody tr.line-row>td[data-label]:before {
                content: attr(data-label);
                font-size: .78rem;
                color: var(--muted);
                margin-right: .75rem;
                flex: 0 0 40%;
                max-width: 42%;
            }

            .table-packing .form-control-sm,
            .table-packing .form-select-sm {
                font-size: .82rem;
                padding-block: .18rem;
                padding-inline: .35rem;
            }
        }
    </style>
@endpush

@section('content')
    <div class="page-wrap">
        {{-- ERROR --}}
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
        <div class="card p-3 mb-3">
            <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
                <div>
                    <h1 class="h5 mb-1">
                        {{ isset($job) ? 'Edit Packing ' . $job->code : 'Packing Job Baru' }}
                    </h1>
                    <div class="help">
                        Pindahkan stok FG (K7BLK, dst) ke gudang PACKED.
                        Qty FG akan mengikuti saldo, kamu cukup isi Qty Packed.
                    </div>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('production.packing_jobs.index') }}" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-arrow-left me-1"></i> Kembali
                    </a>
                    @isset($job)
                        @if ($job->status === 'draft')
                            <a href="{{ route('production.packing_jobs.show', $job) }}" class="btn btn-sm btn-outline-primary">
                                Lihat Detail
                            </a>
                        @endif
                    @endisset
                </div>
            </div>
        </div>

        {{-- FORM --}}
        <form
            action="{{ isset($job) ? route('production.packing_jobs.update', $job) : route('production.packing_jobs.store') }}"
            method="post">
            @csrf
            @isset($job)
                @method('PUT')
            @endisset

            {{-- HEADER FORM --}}
            <div class="card p-3 mb-3">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label small mb-1">Tanggal</label>
                        <input type="date" name="date" class="form-control form-control-sm"
                            value="{{ old('date', $date ?? now()->toDateString()) }}" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small mb-1">Channel (opsional)</label>
                        <input type="text" name="channel" class="form-control form-control-sm"
                            value="{{ old('channel', $job->channel ?? '') }}" placeholder="Shopee / Toko / dll">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small mb-1">Referensi (opsional)</label>
                        <input type="text" name="reference" class="form-control form-control-sm"
                            value="{{ old('reference', $job->reference ?? '') }}" placeholder="SO-001 / DO-xxx">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small mb-1">Catatan (opsional)</label>
                        <input type="text" name="notes" class="form-control form-control-sm"
                            value="{{ old('notes', $job->notes ?? '') }}" placeholder="Packing untuk order...">
                    </div>
                </div>
            </div>

            {{-- DETAIL LINES --}}
            <div class="card p-3 mb-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div>
                        <div class="fw-semibold">Detail Item (FG → PACKED)</div>
                        <div class="help">
                            Pilih item FG (K7BLK, dst). Saldo FG akan terisi otomatis.
                            Kamu cukup mengisi <strong>Qty Packed</strong>.
                        </div>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="btn-add-line">
                        <i class="bi bi-plus-lg me-1"></i> Tambah baris
                    </button>
                </div>

                <div class="table-wrap">
                    <table class="table table-sm align-middle table-packing" id="lines-table">
                        <thead class="table-light">
                            <tr>
                                <th>Item (FG)</th>
                                <th class="text-end">Saldo FG</th>
                                <th class="text-end">Qty Packed</th>
                                <th>Catatan</th>
                                <th style="width: 1%;"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $formLines = old('lines', $lines ?? []);
                            @endphp

                            @if (!empty($formLines))
                                @foreach ($formLines as $i => $line)
                                    @php
                                        $itemId = $line['item_id'] ?? null;
                                        $fgBalance = (float) ($line['fg_balance'] ?? ($line['qty_fg'] ?? 0));
                                        $qtyPacked = $line['qty_packed'] ?? $fgBalance;
                                    @endphp
                                    <tr class="line-row">
                                        {{-- ITEM --}}
                                        <td data-label="Item (FG)">
                                            <select name="lines[{{ $i }}][item_id]"
                                                class="form-select form-select-sm item-select">
                                                <option value="">Pilih item...</option>
                                                @foreach ($stocks as $stock)
                                                    @php
                                                        $it = $stock->item;
                                                        $optQty = (float) ($stock->qty ?? 0);
                                                        $optLabel = $it
                                                            ? trim(
                                                                ($it->code ?? '') .
                                                                    ' — ' .
                                                                    ($it->name ?? '') .
                                                                    ' ' .
                                                                    ($it->color ?? ''),
                                                            )
                                                            : '';
                                                    @endphp
                                                    <option value="{{ $it->id ?? '' }}"
                                                        data-fg-balance="{{ $optQty }}"
                                                        data-item-label="{{ $optLabel }}" @selected($itemId == ($it->id ?? null))>
                                                        {{ $optLabel }} ({{ number_format($optQty) }} pcs)
                                                    </option>
                                                @endforeach
                                            </select>
                                            <input type="hidden" name="lines[{{ $i }}][fg_balance]"
                                                value="{{ $fgBalance }}">
                                        </td>

                                        {{-- SALDO FG --}}
                                        <td data-label="Saldo FG" class="text-end mono">
                                            <span class="fg-balance-label">{{ number_format($fgBalance) }}</span>
                                        </td>

                                        {{-- QTY PACKED --}}
                                        <td data-label="Qty Packed" class="text-end">
                                            <input type="number" min="0" step="1"
                                                name="lines[{{ $i }}][qty_packed]"
                                                class="form-control form-control-sm text-end qty-packed-input"
                                                value="{{ $qtyPacked }}">
                                        </td>

                                        {{-- NOTES --}}
                                        <td data-label="Catatan">
                                            <input type="text" name="lines[{{ $i }}][notes]"
                                                class="form-control form-control-sm" value="{{ $line['notes'] ?? '' }}"
                                                placeholder="Opsional">
                                        </td>

                                        {{-- DELETE --}}
                                        <td data-label="" class="text-center">
                                            <button type="button" class="btn btn-sm btn-link text-danger btn-remove-line">
                                                <i class="bi bi-x-lg"></i>
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            @else
                                {{-- 1 baris default --}}
                                <tr class="line-row">
                                    <td data-label="Item (FG)">
                                        <select name="lines[0][item_id]" class="form-select form-select-sm item-select">
                                            <option value="">Pilih item...</option>
                                            @foreach ($stocks as $stock)
                                                @php
                                                    $it = $stock->item;
                                                    $optQty = (float) ($stock->qty ?? 0);
                                                    $optLabel = $it
                                                        ? trim(
                                                            ($it->code ?? '') .
                                                                ' — ' .
                                                                ($it->name ?? '') .
                                                                ' ' .
                                                                ($it->color ?? ''),
                                                        )
                                                        : '';
                                                @endphp
                                                <option value="{{ $it->id ?? '' }}"
                                                    data-fg-balance="{{ $optQty }}"
                                                    data-item-label="{{ $optLabel }}">
                                                    {{ $optLabel }} ({{ number_format($optQty) }} pcs)
                                                </option>
                                            @endforeach
                                        </select>
                                        <input type="hidden" name="lines[0][fg_balance]" value="0">
                                    </td>
                                    <td data-label="Saldo FG" class="text-end mono">
                                        <span class="fg-balance-label">0</span>
                                    </td>
                                    <td data-label="Qty Packed" class="text-end">
                                        <input type="number" min="0" step="1" name="lines[0][qty_packed]"
                                            class="form-control form-control-sm text-end qty-packed-input" value="0">
                                    </td>
                                    <td data-label="Catatan">
                                        <input type="text" name="lines[0][notes]" class="form-control form-control-sm"
                                            placeholder="Opsional">
                                    </td>
                                    <td data-label="" class="text-center">
                                        <button type="button" class="btn btn-sm btn-link text-danger btn-remove-line">
                                            <i class="bi bi-x-lg"></i>
                                        </button>
                                    </td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>

                <div class="pt-2 mt-1 border-top">
                    <div class="help">
                        Qty Packed tidak boleh melebihi saldo FG.
                        Form ini akan mengoreksi otomatis di sisi UI, tapi validasi akhir tetap di server.
                    </div>
                </div>
            </div>

            {{-- FOOTER --}}
            <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
                <div class="help">
                    Packing Job akan disimpan sebagai <strong>draft</strong>.
                    Stok baru bergerak setelah kamu klik <em>Posting</em> di halaman detail.
                </div>
                <div>
                    <button type="submit" class="btn btn-primary">
                        {{ isset($job) ? 'Update Draft' : 'Simpan Draft' }}
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

            const nf = new Intl.NumberFormat('id-ID');

            function reindexLines() {
                const rows = tableBody.querySelectorAll('.line-row');
                rows.forEach((row, index) => {
                    row.querySelectorAll('select, input').forEach(input => {
                        const name = input.getAttribute('name');
                        if (!name) return;
                        const newName = name.replace(/lines\[\d+]/, 'lines[' + index + ']');
                        input.setAttribute('name', newName);
                    });
                });
            }

            function sanitizeRow(row) {
                const fgHidden = row.querySelector('input[name*="[fg_balance]"]');
                const fgLabel = row.querySelector('.fg-balance-label');
                const qtyInput = row.querySelector('.qty-packed-input');

                if (!fgHidden || !fgLabel || !qtyInput) return;

                let fgBalance = parseFloat(fgHidden.value || '0');
                if (isNaN(fgBalance) || fgBalance < 0) fgBalance = 0;

                let qty = parseFloat(qtyInput.value || '0');
                if (isNaN(qty) || qty < 0) qty = 0;

                if (qty > fgBalance) qty = fgBalance;

                fgLabel.textContent = nf.format(fgBalance);
                qtyInput.value = qty;

                const over = qty > fgBalance + 0.000001;
                qtyInput.classList.toggle('is-soft-invalid', over);
            }

            function handleItemChange(e) {
                const select = e.target;
                const row = select.closest('.line-row');
                if (!row) return;

                const option = select.selectedOptions[0];
                if (!option) return;

                const fgHidden = row.querySelector('input[name*="[fg_balance]"]');
                const fgLabel = row.querySelector('.fg-balance-label');
                const qtyInput = row.querySelector('.qty-packed-input');

                const fgBalStr = option.getAttribute('data-fg-balance') || '0';
                const fgBal = parseFloat(fgBalStr) || 0;

                if (fgHidden) fgHidden.value = fgBalStr;
                if (fgLabel) fgLabel.textContent = nf.format(fgBal);
                if (qtyInput) qtyInput.value = fgBalStr;

                sanitizeRow(row);
            }

            function addLineRow() {
                const lastRow = tableBody.querySelector('.line-row:last-child');
                if (!lastRow) return;

                const newRow = lastRow.cloneNode(true);

                newRow.querySelectorAll('input').forEach(input => {
                    if (input.type === 'hidden') {
                        // reset fg_balance ke 0
                        if (input.name && input.name.includes('[fg_balance]')) {
                            input.value = '0';
                        } else {
                            input.value = '';
                        }
                        return;
                    }

                    if (input.type === 'number') {
                        input.value = 0;
                    } else if (input.type === 'text') {
                        input.value = '';
                    }
                });

                newRow.querySelectorAll('select').forEach(select => {
                    select.selectedIndex = 0;
                });

                const fgLabel = newRow.querySelector('.fg-balance-label');
                if (fgLabel) fgLabel.textContent = '0';

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
                    if (rows.length <= 1) return; // minimal 1 baris
                    e.target.closest('.line-row').remove();
                    reindexLines();
                }
            });

            tableBody.addEventListener('change', function(e) {
                if (e.target.classList.contains('item-select')) {
                    handleItemChange(e);
                }
            });

            tableBody.addEventListener('input', function(e) {
                if (e.target.classList.contains('qty-packed-input')) {
                    const row = e.target.closest('.line-row');
                    if (!row) return;
                    sanitizeRow(row);
                }
            });

            // init semua baris awal
            tableBody.querySelectorAll('.line-row').forEach(row => sanitizeRow(row));
        })();
    </script>
@endpush
