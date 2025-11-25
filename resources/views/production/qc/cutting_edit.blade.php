@extends('layouts.app')

@section('title', 'Produksi • QC Cutting ' . $job->code)

@push('head')
    <style>
        .page-wrap {
            max-width: 1100px;
            margin-inline: auto;
        }

        .card {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 14px;
        }

        .mono {
            font-variant-numeric: tabular-nums;
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas;
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

            table tbody tr td[data-label]::before {
                content: attr(data-label) " ";
                font-weight: 600;
                display: block;
            }
        }
    </style>
@endpush

@section('content')
    @php
        $lot = $job->lot;
        $warehouse = $job->warehouse;
    @endphp

    <div class="page-wrap">

        {{-- HEADER JOB --}}
        <div class="card p-3 mb-3">
            <div class="d-flex justify-content-between align-items-center gap-3">
                <div>
                    <h1 class="h5 mb-1">QC Cutting: {{ $job->code }}</h1>
                    <div class="help">
                        LOT: {{ $lot?->code ?? '-' }} •
                        Item: {{ $lot?->item?->code ?? '-' }} •
                        Gudang: {{ $warehouse?->code ?? '-' }}
                    </div>
                </div>

                @php
                    $statusClass =
                        [
                            'draft' => 'secondary',
                            'cut' => 'primary',
                            'qc_ok' => 'success',
                            'qc_mixed' => 'warning',
                            'qc_reject' => 'danger',
                        ][$job->status] ?? 'secondary';
                @endphp

                <div class="d-flex flex-column align-items-end">
                    <span class="badge bg-{{ $statusClass }} px-3 py-2 mb-1">
                        {{ strtoupper($job->status) }}
                    </span>
                    <a href="{{ route('production.cutting_jobs.show', $job) }}" class="btn btn-sm btn-outline-secondary">
                        Kembali ke Cutting Job
                    </a>
                </div>
            </div>
        </div>

        <form action="{{ route('production.qc.cutting.update', $job) }}" method="post">
            @csrf
            @method('PUT')

            {{-- HEADER QC --}}
            <div class="card p-3 mb-3">
                <h2 class="h6 mb-2">Header QC Cutting</h2>

                <div class="row g-3">
                    <div class="col-md-3 col-6">
                        <label class="form-label">Tanggal QC</label>
                        <input type="date" name="qc_date" value="{{ old('qc_date', now()->toDateString()) }}"
                            class="form-control @error('qc_date') is-invalid @enderror">
                        @error('qc_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-4 col-12">
                        <label class="form-label">Operator QC</label>
                        <select name="operator_id" class="form-select @error('operator_id') is-invalid @enderror">
                            <option value="">Pilih operator QC…</option>
                            @foreach ($operators as $op)
                                <option value="{{ $op->id }}" @selected(old('operator_id') == $op->id)>
                                    {{ $op->code }} — {{ $op->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('operator_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="help mt-1">
                            Bisa dipakai sama dengan operator cutting dulu; nanti kalau ada role <code>qc</code> tinggal
                            ganti query.
                        </div>
                    </div>

                    <div class="col-12">
                        <label class="form-label">Catatan Umum (opsional)</label>
                        <textarea name="notes_global" rows="2" class="form-control">{{ old('notes_global') }}</textarea>
                    </div>
                </div>
            </div>

            {{-- TABEL BUNDLES --}}
            <div class="card p-3 mb-4">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div>
                        <h2 class="h6 mb-0">QC per Bundle</h2>
                        <div class="help">
                            Input hanya <strong>Qty Reject</strong>. Qty OK otomatis = Qty Bundle − Qty Reject.
                        </div>
                    </div>

                    <div class="help">
                        Total OK: <span class="mono" id="sum-ok">0,00</span> •
                        Total Reject: <span class="mono" id="sum-reject">0,00</span>
                    </div>
                </div>

                <div class="table-wrap">
                    <table class="table table-sm align-middle mono">
                        <thead>
                            <tr>
                                <th>Bundle</th>
                                <th>Item</th>
                                <th class="text-end">Qty Bundle</th>
                                <th class="text-end">Qty OK (auto)</th>
                                <th class="text-end">Qty Reject</th>
                                <th>Status</th>
                                <th>Catatan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($rows as $i => $row)
                                @php
                                    $qtyBundle = (int) $row['qty_pcs'];
                                    $qtyReject = (int) ($row['qty_reject'] ?? 0);
                                    $qtyOk = max($qtyBundle - $qtyReject, 0);
                                @endphp
                                <tr>
                                    {{-- bundle_id --}}
                                    <input type="hidden" name="results[{{ $i }}][bundle_id]"
                                        value="{{ $row['bundle_id'] }}">

                                    {{-- hidden qty_ok (server tetap pakai field ini) --}}
                                    <input type="hidden" name="results[{{ $i }}][qty_ok]"
                                        class="input-ok-hidden" value="{{ old("results.$i.qty_ok", $qtyOk) }}">

                                    <td data-label="Bundle">
                                        <div class="fw-semibold">
                                            {{ $row['bundle_code'] }}
                                        </div>
                                        <div class="small text-muted">
                                            No: {{ $row['bundle_no'] }}
                                        </div>
                                    </td>

                                    <td data-label="Item">
                                        <span>{{ $row['item_code'] }}</span>
                                    </td>

                                    <td data-label="Qty Bundle" class="text-end">
                                        {{ number_format($row['qty_pcs'], 2, ',', '.') }}
                                    </td>

                                    <td data-label="Qty OK (auto)" class="text-end">
                                        <span class="cell-ok">
                                            {{ number_format(old("results.$i.qty_ok", $qtyOk), 2, ',', '.') }}
                                        </span>
                                    </td>

                                    <td data-label="Qty Reject" class="text-end">
                                        <input type="number" step="1" min="0" inputmode="numeric"
                                            pattern="\d*" name="results[{{ $i }}][qty_reject]"
                                            class="form-control form-control-sm text-end input-reject"
                                            value="{{ old("results.$i.qty_reject", $qtyReject) }}"
                                            data-bundle="{{ $qtyBundle }}">
                                    </td>

                                    <td data-label="Status">
                                        @php
                                            $st = $row['status'];
                                            $cls =
                                                [
                                                    'cut' => 'secondary',
                                                    'qc_ok' => 'success',
                                                    'qc_reject' => 'danger',
                                                    'qc_mixed' => 'warning',
                                                ][$st] ?? 'secondary';
                                        @endphp
                                        <span class="badge-soft bg-{{ $cls }}">
                                            {{ $st ?: 'cut' }}
                                        </span>
                                    </td>

                                    <td data-label="Catatan" style="min-width: 160px;">
                                        <input type="text" name="results[{{ $i }}][notes]"
                                            class="form-control form-control-sm"
                                            value="{{ old("results.$i.notes", $row['notes']) }}">
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @error('results')
                    <div class="text-danger small mt-1">{{ $message }}</div>
                @enderror

                <div id="qc-warning" class="text-danger small mt-2" style="display:none;">
                    ⚠️ Ada baris di mana Qty Reject melebihi Qty Bundle. Nilai akan dikunci ke batas maksimum.
                </div>
            </div>

            <div class="d-flex justify-content-end mb-5 gap-2">
                <a href="{{ route('production.cutting_jobs.show', $job) }}" class="btn btn-outline-secondary">
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
        const warningEl = document.getElementById('qc-warning');

        function attachSelectAllOnFocus(input) {
            // saat fokus, select semua isi
            input.addEventListener('focus', function() {
                // delay dikit biar jalan juga di mobile
                setTimeout(() => this.select(), 0);
            });

            // cegah mouseup menghapus seleksi
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
                const maxBundle = parseInt(rejInput.dataset.bundle || '0', 10) || 0;

                // paksa integer
                let rej = parseInt(rejInput.value || '0', 10);
                if (isNaN(rej) || rej < 0) rej = 0;

                // clamp kalau > bundle
                if (rej > maxBundle) {
                    rej = maxBundle;
                    anyOver = true;
                }

                // normalisasi kembali ke input
                rejInput.value = rej;

                // hitung OK = bundle - reject
                const ok = maxBundle - rej;

                if (okHidden) {
                    okHidden.value = ok;
                }

                if (okCell) {
                    okCell.textContent = ok.toFixed(2).replace('.', ',');
                }

                totalOk += ok;
                totalReject += rej;
            });

            if (sumOkSpan) {
                sumOkSpan.textContent = totalOk.toFixed(2).replace('.', ',');
            }
            if (sumRejectSpan) {
                sumRejectSpan.textContent = totalReject.toFixed(2).replace('.', ',');
            }

            if (warningEl) {
                warningEl.style.display = anyOver ? 'block' : 'none';
            }
        }

        // pasang behavior ke semua input reject
        inputsReject.forEach(i => {
            attachSelectAllOnFocus(i);
            i.addEventListener('input', recalcTotals);
        });

        recalcTotals();
    </script>
@endpush
