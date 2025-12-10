@extends('layouts.app')

@section('title', 'Produksi • Finishing')

@push('head')
    <style>
        :root {
            --fin-card-radius: 14px;
            --fin-border: rgba(148, 163, 184, 0.28);
            --fin-muted: #6b7280;
            --fin-accent: #2563eb;
        }

        .finishing-page {
            min-height: 100vh;
        }

        .finishing-page .page-wrap {
            max-width: 1050px;
            margin-inline: auto;
            padding: 1rem .9rem 3.5rem;
        }

        /* background minimal */
        body[data-theme="light"] .finishing-page .page-wrap {
            background: linear-gradient(to bottom,
                    #f5f6fa 0,
                    #f9fafb 40%,
                    #ffffff 100%);
        }

        body[data-theme="dark"] .finishing-page .page-wrap {
            background: radial-gradient(circle at top left,
                    rgba(37, 99, 235, 0.26) 0,
                    rgba(15, 23, 42, 0.92) 46%,
                    #020617 100%);
        }

        .card-main {
            background: var(--card);
            border-radius: var(--fin-card-radius);
            border: 1px solid rgba(148, 163, 184, 0.2);
            box-shadow:
                0 8px 20px rgba(15, 23, 42, 0.06),
                0 0 0 1px rgba(15, 23, 42, 0.02);
        }

        .card-section {
            padding: .9rem 1rem;
        }

        @media (min-width: 768px) {
            .card-section {
                padding: 1rem 1.2rem;
            }
        }

        .mono {
            font-variant-numeric: tabular-nums;
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas;
        }

        /* ===== HEADER PAGE ala Sewing Return, khas Finishing ===== */
        .header-card {
            border-radius: 14px;
            border: 1px solid rgba(148, 163, 184, 0.3);
            box-shadow:
                0 10px 24px rgba(15, 23, 42, 0.06),
                0 0 0 1px rgba(15, 23, 42, 0.02);
        }

        .header-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: .75rem;
            flex-wrap: wrap;
        }

        .header-icon-circle {
            width: 40px;
            height: 40px;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-right: .75rem;
            background: radial-gradient(circle at 30% 0,
                    rgba(37, 99, 235, 0.25),
                    rgba(59, 130, 246, 0.08),
                    rgba(15, 23, 42, 0.02));
            color: var(--fin-accent);
        }

        body[data-theme="dark"] .header-icon-circle {
            background: radial-gradient(circle at 30% 0,
                    rgba(59, 130, 246, 0.45),
                    rgba(15, 23, 42, 0.9));
            color: #e5e7eb;
        }

        .header-title h1 {
            font-size: 1.25rem;
            font-weight: 600;
            margin: 0;
        }

        .header-subtitle {
            font-size: .82rem;
            color: var(--fin-muted);
        }

        .btn-header-pill {
            border-radius: 999px;
            font-size: .8rem;
            padding-inline: .9rem;
        }

        .btn-header-accent {
            border-color: rgba(37, 99, 235, 0.35);
            color: var(--fin-accent);
            background: rgba(37, 99, 235, 0.03);
        }

        body[data-theme="dark"] .btn-header-accent {
            background: rgba(37, 99, 235, 0.15);
            color: #e5e7eb;
        }

        /* ===== META ROW ===== */
        .meta-row {
            margin-top: .25rem;
            border-radius: 10px;
            padding: .7rem .75rem;
            border: 1px solid rgba(148, 163, 184, 0.2);
            background: rgba(248, 250, 252, 0.8);
        }

        body[data-theme="dark"] .meta-row {
            background: rgba(15, 23, 42, 0.9);
            border-color: rgba(51, 65, 85, 0.9);
        }

        .meta-row .form-label-sm {
            font-size: .76rem;
            font-weight: 500;
            color: var(--muted-foreground);
        }

        .form-control-sm,
        .form-select-sm {
            font-size: .84rem;
            padding: .32rem .48rem;
        }

        /* ===== FILTER BAR ===== */
        .filter-bar {
            display: flex;
            flex-wrap: wrap;
            gap: .45rem .75rem;
            align-items: flex-end;
            justify-content: space-between;
            margin-top: .6rem;
        }

        .filter-bar-left {
            display: flex;
            flex-direction: column;
            gap: .25rem;
        }

        .filter-label {
            font-size: .72rem;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: var(--muted-foreground);
        }

        .filter-row {
            display: flex;
            flex-wrap: wrap;
            gap: .4rem;
        }

        .filter-row .item-code-select-wrap {
            min-width: 180px;
            max-width: 260px;
        }

        .filter-row .item-search-wrap {
            min-width: 180px;
            max-width: 280px;
        }

        .item-search-wrap .form-control {
            font-size: .8rem;
            text-transform: uppercase;
            letter-spacing: .05em;
        }

        .filter-summary {
            font-size: .76rem;
            color: var(--fin-muted);
            display: flex;
            flex-wrap: wrap;
            gap: .25rem .45rem;
            justify-content: flex-end;
        }

        .filter-summary strong {
            font-weight: 600;
        }

        .filter-dot {
            opacity: .7;
        }

        /* ===== TABLE ===== */
        .finishing-table-wrap {
            margin-top: .75rem;
        }

        .finishing-table {
            margin-bottom: 0;
            width: 100%;
        }

        .finishing-table thead th {
            background: rgba(15, 23, 42, 0.02);
            font-size: .74rem;
            text-transform: uppercase;
            color: var(--muted-foreground);
            padding: .5rem .55rem;
            white-space: nowrap;
            border-bottom-color: rgba(148, 163, 184, 0.35);
        }

        body[data-theme="dark"] .finishing-table thead th {
            background: rgba(15, 23, 42, 0.9);
        }

        .finishing-table tbody td {
            padding: .42rem .55rem;
            vertical-align: middle;
            font-size: .86rem;
        }

        .finishing-table tbody tr:nth-child(even) td {
            background: rgba(15, 23, 42, 0.01);
        }

        .finishing-table tbody tr:hover td {
            background: rgba(37, 99, 235, 0.03);
        }

        .item-label-main-desktop {
            font-weight: 600;
        }

        .item-label-main-mobile {
            font-weight: 700;
            font-size: .9rem;
        }

        .wip-badge {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            padding: .18rem .45rem;
            border-radius: 999px;
            background: rgba(16, 185, 129, 0.06);
            color: #047857;
            font-size: .8rem;
            font-weight: 600;
        }

        body[data-theme="dark"] .wip-badge {
            background: rgba(16, 185, 129, 0.18);
            color: #bbf7d0;
        }

        .wip-badge small {
            font-size: .7rem;
            opacity: .85;
        }

        .qty-ok-input,
        .qty-reject-input {
            border-radius: 999px;
            transition: box-shadow 0.15s ease, border-color 0.15s ease, background-color 0.15s ease;
        }

        .qty-ok-input {
            border: 1px solid rgba(16, 185, 129, 0.3);
            background: rgba(16, 185, 129, 0.02);
        }

        .qty-reject-input {
            border: 1px solid rgba(248, 113, 113, 0.4);
            background: rgba(248, 113, 113, 0.02);
        }

        .qty-ok-input:focus {
            box-shadow: 0 0 0 1px rgba(16, 185, 129, 0.7);
            background: rgba(16, 185, 129, 0.08);
        }

        .qty-reject-input:focus {
            box-shadow: 0 0 0 1px rgba(248, 113, 113, 0.75);
            background: rgba(248, 113, 113, 0.1);
        }

        /* Footer actions (desktop static) */
        .footer-actions {
            margin-top: 1rem;
            display: flex;
            justify-content: flex-end;
            gap: .5rem;
        }

        .btn-save {
            border-radius: 999px;
            padding-inline: 1rem;
        }

        /* ===== MOBILE ===== */
        @media (max-width: 767.98px) {
            .finishing-page .page-wrap {
                padding-inline: .8rem;
                padding-bottom: 7.4rem;
            }

            body.keyboard-open .finishing-page .page-wrap {
                padding-bottom: 11.4rem;
            }

            .card-section {
                padding: .8rem .85rem;
            }

            .header-row {
                flex-direction: column;
                align-items: flex-start;
            }

            /* HILANGKAN KOLOM TANGGAL + CATATAN DI MOBILE (tetap kirim default dari server) */
            .meta-row .row.g-3 {
                display: none !important;
            }

            /* FILTER: select & search jadi inline, hierarchy: select lebih dominan, cari kalem */
            .filter-bar {
                margin-top: .4rem;
                gap: .5rem;
            }

            .filter-bar-left {
                width: 100%;
            }

            .filter-row {
                flex-direction: row;
                align-items: center;
                gap: .4rem;
            }

            .item-code-select-wrap {
                flex: 0 0 58%;
            }

            .item-search-wrap {
                flex: 1;
            }

            .item-search-wrap .input-group-text {
                border-radius: 999px 0 0 999px;
                border-right: 0;
                font-size: .7rem;
                padding-inline: .5rem;
                background: transparent;
                border-color: rgba(148, 163, 184, 0.5);
            }

            .item-search-wrap .form-control {
                font-size: .78rem;
                text-transform: none;
                letter-spacing: 0.02em;
                border-radius: 0 999px 999px 0;
                border-left: 0;
                border-color: rgba(148, 163, 184, 0.5);
                background: rgba(15, 23, 42, 0.01);
            }

            .item-search-wrap .form-control::placeholder {
                color: rgba(148, 163, 184, 0.9);
                font-weight: 400;
            }

            /* summary geser ke kiri biar ga terlalu rame di kanan */
            .filter-summary {
                justify-content: flex-start;
            }

            /* Sembunyikan hanya index & alasan; WIP-FIN tetap tampil di mobile */
            th.col-index,
            th.col-reason,
            td.col-index,
            td.col-reason {
                display: none !important;
            }

            /* Item: hanya kode (tanpa nama) di mobile → C5BLK saja */
            .item-label-main-desktop {
                display: none;
            }

            .item-label-main-mobile {
                display: block;
                font-size: .95rem;
                color: var(--fin-accent);
                letter-spacing: .08em;
            }

            /* Input OK/RJ jadi focal point di mobile */
            .qty-ok-input,
            .qty-reject-input {
                text-align: center;
                font-weight: 600;
                padding-top: .45rem;
                padding-bottom: .45rem;
                font-size: .9rem;
            }

            /* Floating footer ala sewing pickup: 2 tombol ngumpul di kanan */
            .footer-actions {
                position: fixed;
                left: .1rem;
                right: .9rem;
                bottom: 6rem;
                z-index: 999;
                justify-content: flex-end;
                gap: .5rem;
            }

            .footer-actions .btn {
                border-radius: 999px;
                box-shadow:
                    0 10px 22px rgba(15, 23, 42, 0.25),
                    0 3px 8px rgba(15, 23, 42, 0.18);
            }

            /* urutan: Simpan (kiri), Kembali (kanan) */
            .footer-actions .btn-save {
                order: 1;
                background: linear-gradient(135deg, #22c55e 0%, #16a34a 60%, #15803d 100%);
                border: none;
            }

            .footer-actions .btn-back {
                order: 2;
            }
        }

        @media (min-width: 768px) {
            .item-label-main-mobile {
                display: none;
            }

            .item-label-main-desktop {
                display: block;
            }
        }
    </style>
@endpush

@section('content')
    <div class="finishing-page">
        <div class="page-wrap">

            {{-- Flash / Errors --}}
            @if (session('status'))
                <div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
                    {{ session('status') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
                    <strong>Oops!</strong> Ada error input, cek form di bawah.
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            {{-- HEADER UTAMA ala Sewing Return, tapi Finishing --}}
            <div class="card mb-2 header-card">
                <div class="card-section">
                    <div class="header-row">
                        <div class="d-flex align-items-center">
                            <div class="header-icon-circle">
                                <i class="bi bi-scissors"></i>
                            </div>
                            <div class="header-title d-flex flex-column gap-1">
                                <h1>Halaman Finishing</h1>
                                <div class="header-subtitle">
                                    Proses WIP-FIN menjadi barang jadi, fokus input per item.
                                </div>
                            </div>
                        </div>

                        <div class="d-flex flex-column flex-md-row gap-2">
                            <a href="{{ route('production.finishing_jobs.index') }}"
                                class="btn btn-sm btn-header-pill btn-header-accent d-flex align-items-center gap-2">
                                <i class="bi bi-list-task"></i>
                                <span>Riwayat Finishing</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card card-main">
                <form id="finishing-form" action="{{ route('production.finishing_jobs.store') }}" method="POST" novalidate>
                    @csrf

                    @php
                        use Illuminate\Support\Str;

                        $userRole = auth()->user()->role ?? null;
                        $dateValue = old('date', $dateDefault ?? now()->toDateString());
                        $defaultOperator = old('operator_global_id') ?? (auth()->user()->employee_id ?? '');
                        $grandWip = collect($lines ?? [])->sum('total_wip');

                        $itemOptions = collect($lines ?? [])
                            ->map(function ($l) {
                                $label = $l['item_label'] ?? 'Item #' . $l['item_id'];
                                $codeOnly = Str::contains($label, ' - ') ? Str::before($label, ' - ') : $label;

                                return [
                                    'id' => $l['item_id'],
                                    'label' => $label,
                                    'code' => $codeOnly,
                                ];
                            })
                            ->unique('id')
                            ->values();
                    @endphp

                    {{-- HIDDEN OPERATOR GLOBAL (diisi dari modal) --}}
                    <input type="hidden" name="operator_global_id" id="operator_global_id_hidden"
                        value="{{ old('operator_global_id', $defaultOperator) }}">

                    {{-- META + FILTER --}}
                    <div class="card-section">
                        <div class="meta-row">
                            {{-- TANGGAL & CATATAN (HILANG DI MOBILE, HANYA DESKTOP) --}}
                            <div class="row g-3 align-items-end">
                                <div class="col-6 col-md-3">
                                    <label class="form-label form-label-sm">Tanggal</label>
                                    <input type="date" name="date"
                                        class="form-control form-control-sm @error('date') is-invalid @enderror"
                                        value="{{ $dateValue }}">
                                    @error('date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-12 col-md-9">
                                    <label class="form-label form-label-sm">Catatan</label>
                                    <textarea name="notes" rows="1" class="form-control form-control-sm" placeholder="Catatan (opsional)">{{ old('notes', '') }}</textarea>
                                    @error('notes')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            {{-- FILTER + SUMMARY --}}
                            <div class="filter-bar">
                                <div class="filter-bar-left">
                                    <div class="filter-label">Filter kode item</div>
                                    <div class="filter-row">
                                        <div class="item-code-select-wrap">
                                            <select id="item-filter-select" class="form-select form-select-sm">
                                                <option value="">Semua kode</option>
                                                @foreach ($itemOptions as $opt)
                                                    <option value="{{ $opt['id'] }}">
                                                        {{ $opt['code'] }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div class="item-search-wrap">
                                            <div class="input-group input-group-sm">
                                                <span class="input-group-text border-end-0 bg-white">
                                                    <i class="bi bi-search text-muted"></i>
                                                </span>
                                                <input type="text" id="item-filter-input"
                                                    class="form-control form-control-sm border-start-0 text-uppercase"
                                                    placeholder="Cari kode item..." autocomplete="off">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="filter-summary">
                                    <span><strong>WIP-FIN:</strong> {{ number_format($grandWip, 0, ',', '.') }} pcs</span>
                                    <span class="filter-dot">•</span>
                                    <span><strong>Item:</strong> {{ count($lines ?? []) }}</span>
                                    <span class="filter-dot">•</span>
                                    <span><strong>Tanggal:</strong>
                                        {{ \Carbon\Carbon::parse($dateValue)->format('d M Y') }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- TABLE --}}
                    <div class="card-section">
                        <div class="finishing-table-wrap">
                            <div class="table-wrap">
                                <table class="table table-sm finishing-table mono">
                                    <thead>
                                        <tr>
                                            <th class="text-center col-index" style="width:5%;">No</th>
                                            <th class="col-item" style="width:32%;">Kode Item</th>
                                            <th class="text-end col-wip" style="width:16%;">Total WIP-FIN</th>
                                            <th class="text-end col-ok" style="width:16%;">OK</th>
                                            <th class="text-end col-reject" style="width:16%;">Reject</th>
                                            <th class="col-reason" style="width:17%;">Alasan Reject</th>
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

                                                $codeOnly = Str::contains($fullLabel, ' - ')
                                                    ? Str::before($fullLabel, ' - ')
                                                    : $fullLabel;
                                            @endphp
                                            <tr data-search="{{ strtolower($codeOnly . ' ' . $itemId) }}"
                                                data-item-id="{{ $itemId }}">
                                                <td class="text-center align-middle col-index">
                                                    {{ $loop->iteration }}
                                                </td>

                                                <td class="col-item">
                                                    <div class="item-label-main-desktop">
                                                        {{ $fullLabel }}
                                                    </div>
                                                    <div class="item-label-main-mobile">
                                                        {{ $codeOnly }}
                                                    </div>
                                                </td>

                                                <td class="text-end align-middle col-wip">
                                                    <span class="wip-badge">
                                                        <i class="bi bi-arrow-up-circle"></i>
                                                        <span>{{ number_format($totalWip, 0, ',', '.') }}</span>
                                                        <small>pcs</small>
                                                    </span>
                                                </td>

                                                {{-- Qty OK pakai x-number-input (integer) --}}
                                                <td class="text-end align-middle col-ok">
                                                    <x-number-input :name="'lines[' . $idx . '][qty_in]'" :value="is_null($qtyIn) ? '' : (int) $qtyIn" mode="integer"
                                                        :min="0" :max="$totalWip"
                                                        class="form-control form-control-sm text-end integer-input qty-ok-input"
                                                        placeholder="OK" />
                                                    @error("lines.$idx.qty_in")
                                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                                    @enderror
                                                </td>

                                                {{-- Qty Reject pakai x-number-input (integer) --}}
                                                <td class="text-end align-middle col-reject">
                                                    <x-number-input :name="'lines[' . $idx . '][qty_reject]'" :value="(int) $qtyReject" mode="integer"
                                                        :min="0" :max="$totalWip"
                                                        class="form-control form-control-sm text-end integer-input qty-reject-input"
                                                        placeholder="RJ" />
                                                    @error("lines.$idx.qty_reject")
                                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                                    @enderror
                                                </td>

                                                <td class="align-middle col-reason">
                                                    <input type="text"
                                                        name="lines[{{ $idx }}][reject_reason]"
                                                        class="form-control form-control-sm"
                                                        placeholder="Alasan (opsional)" value="{{ $rejectReason }}">
                                                    @error("lines.$idx.reject_reason")
                                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                                    @enderror
                                                </td>

                                                {{-- Hidden fields --}}
                                                <input type="hidden" name="lines[{{ $idx }}][item_id]"
                                                    value="{{ $itemId }}">
                                                <input type="hidden" name="lines[{{ $idx }}][total_wip]"
                                                    value="{{ $totalWip }}">
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

                        {{-- Footer actions (desktop + floating mobile) --}}
                        <div class="footer-actions">
                            <a href="{{ route('production.finishing_jobs.index') }}"
                                class="btn btn-sm btn-outline-secondary btn-back">
                                <i class="bi bi-arrow-left"></i>
                            </a>
                            <button type="submit" class="btn btn-sm btn-primary btn-save" disabled>
                                <i class="bi bi-check2-circle"></i>
                                Simpan Finishing
                            </button>
                        </div>
                    </div>

                    {{-- MODAL KONFIRMASI OPERATOR --}}
                    <div class="modal fade" id="finishingOperatorModal" tabindex="-1"
                        aria-labelledby="finishingOperatorModalLabel" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="finishingOperatorModalLabel">
                                        Konfirmasi Finishing
                                    </h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                                        aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="mb-3">
                                        <div class="small text-muted text-uppercase mb-1">
                                            Ringkasan Proses
                                        </div>
                                        <div class="border rounded-3 p-2 bg-light-subtle small">
                                            <div class="d-flex justify-content-between mb-1">
                                                <span>Tanggal</span>
                                                <span id="summary-date">
                                                    {{ \Carbon\Carbon::parse($dateValue)->format('d M Y') }}
                                                </span>
                                            </div>
                                            <div class="d-flex justify-content-between mb-1">
                                                <span>Total OK</span>
                                                <span id="summary-total-ok">0</span>
                                            </div>
                                            <div class="d-flex justify-content-between">
                                                <span>Total Reject</span>
                                                <span id="summary-total-reject">0</span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label form-label-sm">
                                            Operator jahit (global)
                                        </label>
                                        <select id="modal-operator-select"
                                            class="form-select form-select-sm @error('operator_global_id') is-invalid @enderror">
                                            <option value="">- pilih operator (opsional) -</option>
                                            @foreach ($operators as $op)
                                                <option value="{{ $op->id }}" @selected((int) old('operator_global_id', $defaultOperator) === (int) $op->id)>
                                                    {{ $op->code ?? $op->id }} — {{ $op->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('operator_global_id')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                        <div class="form-text small">
                                            Operator ini akan dipakai sebagai operator jahit utama untuk semua baris
                                            finishing.
                                        </div>
                                    </div>

                                    <div class="alert alert-info small mb-0">
                                        Pastikan angka OK dan Reject sudah benar sebelum menyimpan.
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-sm btn-outline-secondary"
                                        data-bs-dismiss="modal">
                                        Batal
                                    </button>
                                    <button type="button" class="btn btn-sm btn-primary" id="modal-confirm-submit">
                                        <i class="bi bi-check2-circle"></i>
                                        Simpan Sekarang
                                    </button>
                                </div>
                            </div>
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
            const table = document.querySelector('.finishing-table');
            const form = document.getElementById('finishing-form');

            const integerInputs = Array.from(document.querySelectorAll('input.integer-input'));
            const filterInput = document.getElementById('item-filter-input');
            const filterSelect = document.getElementById('item-filter-select');
            const saveBtn = document.querySelector('.btn-save');

            const operatorHidden = document.getElementById('operator_global_id_hidden');
            const operatorSelectModal = document.getElementById('modal-operator-select');
            const summaryDateEl = document.getElementById('summary-date');
            const summaryOkEl = document.getElementById('summary-total-ok');
            const summaryRejectEl = document.getElementById('summary-total-reject');
            const modalConfirmBtn = document.getElementById('modal-confirm-submit');
            const operatorModalEl = document.getElementById('finishingOperatorModal');

            /* ========= HELPERS ========= */

            function isRejectInput(el) {
                if (!el) return false;
                if (el.classList.contains('qty-reject-input')) return true;
                const name = el.name || '';
                return name.includes('[qty_reject]');
            }

            function sanitizeToIntString(value, options = {}) {
                const {
                    allowEmpty = false
                } = options;
                let v = (value || '').toString().trim();

                if (v === '') {
                    return allowEmpty ? '' : '0';
                }

                // ganti koma jadi titik lalu ambil integer-nya
                if (/[.,]/.test(v)) {
                    v = v.replace(',', '.');
                    const f = parseFloat(v);
                    if (!isNaN(f)) {
                        return String(Math.max(0, Math.floor(f)));
                    }
                }

                const digits = v.replace(/[^0-9]/g, '');
                if (digits === '') {
                    return allowEmpty ? '' : '0';
                }

                return String(Math.max(0, parseInt(digits, 10)));
            }

            function isPositiveInt(value) {
                const v = (value || '').toString().trim();
                if (v === '') return false;
                const num = parseInt(v, 10);
                return !isNaN(num) && num > 0;
            }

            /* ========= PENOMORAN TABEL ========= */

            function renumberRows() {
                if (!table) return;
                const rows = table.querySelectorAll('tbody tr');
                let counter = 1;

                rows.forEach(row => {
                    if (row.style.display === 'none') {
                        return;
                    }
                    const cell = row.querySelector('td.col-index');
                    if (cell) {
                        cell.textContent = counter++;
                    }
                });
            }

            /* ========= SAVE BUTTON & SUMMARY ========= */

            function updateSaveButtonState() {
                if (!saveBtn) return;

                const hasAnyValue = integerInputs.some(input => isPositiveInt(input.value));
                saveBtn.disabled = !hasAnyValue;
                saveBtn.classList.toggle('disabled', !hasAnyValue);
            }

            function computeSummary() {
                let totalOk = 0;
                let totalReject = 0;

                integerInputs.forEach(i => {
                    const name = i.name || '';
                    const match = name.match(/lines\[(\d+)\]\[(qty_in|qty_reject)\]/);
                    if (!match) return;

                    const val = parseInt(i.value || '0', 10) || 0;
                    if (match[2] === 'qty_in') {
                        totalOk += val;
                    } else if (match[2] === 'qty_reject') {
                        totalReject += val;
                    }
                });

                if (summaryOkEl) summaryOkEl.textContent = totalOk.toString();
                if (summaryRejectEl) summaryRejectEl.textContent = totalReject.toString();

                const dateInput = document.querySelector('input[name="date"]');
                if (dateInput && summaryDateEl && dateInput.value) {
                    summaryDateEl.textContent = dateInput.value;
                }
            }

            /* ========= INPUT HANDLING (OK & REJECT) ========= */

            integerInputs.forEach(el => {
                // input
                el.addEventListener('input', function() {
                    // REJECT: tidak boleh kosong → selalu minimal 0
                    if (isRejectInput(this)) {
                        const normalized = sanitizeToIntString(this.value, {
                            allowEmpty: false
                        });
                        this.value = normalized;
                    } else {
                        // OK: boleh kosong, tapi tetap integer bersih
                        const normalized = sanitizeToIntString(this.value, {
                            allowEmpty: true
                        });
                        this.value = normalized;
                    }

                    updateSaveButtonState();
                    computeSummary();
                });

                // paste
                el.addEventListener('paste', function(e) {
                    e.preventDefault();
                    const paste = (e.clipboardData || window.clipboardData).getData('text') || '';
                    const normalized = sanitizeToIntString(paste, {
                        allowEmpty: !isRejectInput(this)
                    });

                    const start = this.selectionStart || 0;
                    const end = this.selectionEnd || 0;
                    const before = this.value.slice(0, start);
                    const after = this.value.slice(end);

                    const next = before + normalized + after;
                    this.value = sanitizeToIntString(next, {
                        allowEmpty: !isRejectInput(this)
                    });

                    const pos = (before + normalized).length;
                    this.setSelectionRange(pos, pos);
                    this.dispatchEvent(new Event('input', {
                        bubbles: true
                    }));
                });

                // mouse wheel disable
                el.addEventListener('wheel', function(ev) {
                    if (document.activeElement === this) {
                        ev.preventDefault();
                    }
                }, {
                    passive: false
                });
            });

            /* ========= GUARD: OK + REJECT ≤ WIP-FIN ========= */

            if (table) {
                table.addEventListener('input', function(e) {
                    const target = e.target;
                    if (!target.name) return;

                    const match = target.name.match(/lines\[(\d+)\]\[(\w+)\]/);
                    if (!match) return;

                    const idx = match[1];
                    const field = match[2]; // qty_in / qty_reject

                    const qtyInEl = document.querySelector(`[name="lines[${idx}][qty_in]"]`);
                    const qtyRejectEl = document.querySelector(`[name="lines[${idx}][qty_reject]"]`);
                    const totalWipEl = document.querySelector(`[name="lines[${idx}][total_wip]"]`);

                    const totalWip = Math.max(0, parseInt(totalWipEl?.value || 0, 10) || 0);

                    let qtyIn = Math.max(0, parseInt(qtyInEl?.value || 0, 10) || 0);
                    let qtyReject = Math.max(0, parseInt(qtyRejectEl?.value || 0, 10) || 0);

                    // clamp per field ke totalWip
                    if (qtyIn > totalWip) qtyIn = totalWip;
                    if (qtyReject > totalWip) qtyReject = totalWip;

                    // kombinasi OK + Reject tidak boleh > totalWip
                    if (qtyIn + qtyReject > totalWip) {
                        if (field === 'qty_in') {
                            qtyIn = Math.max(0, totalWip - qtyReject);
                        } else if (field === 'qty_reject') {
                            qtyReject = Math.max(0, totalWip - qtyIn);
                        }
                    }

                    if (qtyInEl) qtyInEl.value = qtyIn === 0 ? '' : String(qtyIn);
                    if (qtyRejectEl) qtyRejectEl.value = String(
                        qtyReject); // reject tidak boleh kosong → minimal "0"

                    updateSaveButtonState();
                    computeSummary();
                });
            }

            /* ========= FILTER KODE ITEM ========= */

            function applyItemFilter() {
                if (!table) return;

                const term = (filterInput?.value || '').toLowerCase().trim();
                const selectedId = (filterSelect?.value || '').toString();
                const rows = table.querySelectorAll('tbody tr');

                rows.forEach(row => {
                    const text = (row.dataset.search || '').toLowerCase();
                    const itemId = (row.dataset.itemId || '').toString();

                    const matchText = !term || text.includes(term);
                    const matchSelect = !selectedId || itemId === selectedId;

                    row.style.display = (matchText && matchSelect) ? '' : 'none';
                });

                // setelah filter, update penomoran
                renumberRows();
            }

            if (filterInput) {
                filterInput.addEventListener('input', applyItemFilter);
            }

            if (filterSelect) {
                filterSelect.addEventListener('change', function() {
                    applyItemFilter();

                    const selectedId = (filterSelect.value || '').toString();
                    if (!table || !selectedId) return;

                    const targetRow = table.querySelector(`tbody tr[data-item-id="${selectedId}"]`);
                    if (targetRow) {
                        const qtyInEl = targetRow.querySelector('input[name^="lines"][name$="[qty_in]"]');
                        if (qtyInEl) {
                            qtyInEl.focus();
                            qtyInEl.select();

                            const rect = targetRow.getBoundingClientRect();
                            const offset = rect.top + window.scrollY - 100;
                            window.scrollTo({
                                top: offset,
                                behavior: 'smooth'
                            });
                        }
                    }
                });
            }

            /* ========= MOBILE KEYBOARD HELPER ========= */

            const body = document.body;
            integerInputs.forEach(inp => {
                inp.addEventListener('focus', () => {
                    if (window.innerWidth < 768) {
                        body.classList.add('keyboard-open');
                    }
                });
                inp.addEventListener('blur', () => {
                    body.classList.remove('keyboard-open');
                });
            });

            /* ========= SUBMIT: FINAL SANITIZE & REJECT KOSONG → 0 ========= */

            if (form) {
                form.addEventListener('submit', function() {
                    integerInputs.forEach(i => {
                        const reject = isRejectInput(i);
                        let v = (i.value || '').trim();

                        if (v === '') {
                            if (reject) {
                                i.value = '0';
                            }
                            return;
                        }

                        i.value = sanitizeToIntString(v, {
                            allowEmpty: false
                        });
                    });
                });
            }

            /* ========= MODAL KONFIRMASI OPERATOR ========= */

            if (saveBtn && operatorModalEl && typeof bootstrap !== 'undefined') {
                const bsModal = new bootstrap.Modal(operatorModalEl);

                saveBtn.addEventListener('click', function(e) {
                    if (saveBtn.disabled) {
                        e.preventDefault();
                        return;
                    }
                    e.preventDefault();
                    computeSummary();
                    bsModal.show();
                });

                if (modalConfirmBtn) {
                    modalConfirmBtn.addEventListener('click', function() {
                        if (operatorHidden && operatorSelectModal) {
                            operatorHidden.value = operatorSelectModal.value || '';
                        }
                        bsModal.hide();
                        form.submit();
                    });
                }
            }

            // Init awal
            updateSaveButtonState();
            computeSummary();
            renumberRows();
        });
    </script>
@endpush
