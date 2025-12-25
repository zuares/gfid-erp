{{-- resources/views/production/finishing_jobs/create.blade.php --}}
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

        .filter-summary {
            font-size: .76rem;
            color: var(--fin-muted);
            display: flex;
            flex-wrap: wrap;
            gap: .25rem .45rem;
            justify-content: flex-end;
            margin-top: .4rem;
        }

        .filter-summary strong {
            font-weight: 600;
        }

        .filter-dot {
            opacity: .7;
        }

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

        /* HEADER ala Sewing Return */
        .fin-header .header-icon-circle {
            width: 42px;
            height: 42px;
            margin-right: .75rem;
        }

        .fin-header .header-title h1 {
            font-size: 1.15rem;
            font-weight: 700;
            margin: 0;
        }

        .fin-header .header-subtitle {
            font-size: .82rem;
            color: var(--fin-muted);
        }

        @media (max-width: 767.98px) {
            .finishing-page .page-wrap {
                padding-inline: .8rem;
                /* ruang untuk bottom-nav + tombol floating */
                padding-bottom: calc(9.4rem + var(--vv-kbd));
            }

            /* Saat keyboard terbuka, tambah ruang scroll */
            body.keyboard-open .finishing-page .page-wrap {
                padding-bottom: calc(11.4rem + var(--vv-kbd));
            }

            .card-section {
                padding: .8rem .85rem;
            }

            .fin-header .header-row {
                flex-direction: column;
                align-items: flex-start;
                gap: .6rem;
            }

            .fin-header .header-title h1 {
                font-size: 1rem;
            }

            .fin-header .header-subtitle {
                font-size: .78rem;
            }

            /* Sembunyikan CATATAN & SUMMARY di mobile */
            .fin-notes-col {
                display: none !important;
            }

            .filter-summary {
                display: none !important;
            }

            /* Grid meta: tanggal + select sejajar, cari kode di baris bawah */
            .meta-row .meta-grid .col-date,
            .meta-row .meta-grid .col-select {
                flex: 0 0 50%;
                max-width: 50%;
            }

            .meta-row .meta-grid .col-search {
                flex: 0 0 100%;
                max-width: 100%;
                margin-top: .25rem;
            }

            .meta-row .form-label-sm {
                font-size: .75rem;
            }

            /* TABLE MOBILE FRIENDLY */
            .finishing-table thead th {
                font-size: .7rem;
                padding: .4rem .4rem;
            }

            .finishing-table tbody td {
                font-size: .8rem;
                padding: .35rem .4rem;
            }

            /* Hilangkan kolom Alasan/Catatan di mobile saja */
            th.col-reason,
            td.col-reason {
                display: none !important;
            }

            /* NOMOR BARIS MOBILE */
            td.col-index {
                width: 1%;
                white-space: nowrap;
            }

            td.col-index span.mobile-index-pill {
                display: inline-block;
                min-width: 20px;
                height: 20px;
                padding: 0 .25rem;
                border-radius: 999px;
                background: rgba(59, 130, 246, .12);
                border: 1px solid rgba(59, 130, 246, .35);
                color: #1d4ed8;
                font-size: .72rem;
                font-weight: 700;
                line-height: 18px;
                text-align: center;
            }

            body[data-theme="dark"] td.col-index span.mobile-index-pill {
                background: rgba(59, 130, 246, .25);
                color: #e5edff;
            }

            /* Kode item: tampil kode saja (label panjang disembunyikan) */
            .item-label-main-desktop {
                display: none;
            }

            .item-label-main-mobile {
                display: block;
                font-size: .95rem;
                color: var(--fin-accent);
                letter-spacing: .08em;
            }

            .qty-ok-input,
            .qty-reject-input {
                text-align: center;
                font-weight: 600;
                padding-top: .4rem;
                padding-bottom: .4rem;
                font-size: .88rem;
            }

            .finishing-table tbody tr td:first-child {
                border-left: 0;
            }

            .finishing-table tbody tr td:last-child {
                border-right: 0;
            }

            .finishing-table tbody tr+tr td {
                border-top: 1px solid rgba(148, 163, 184, 0.35);
            }

            /* Floating footer-actions: aman dari bottom-nav */
            .footer-actions {
                position: fixed;
                left: .9rem;
                right: .9rem;
                bottom: calc(5.6rem + var(--vv-kbd));
                z-index: 999;
                justify-content: flex-end;
                gap: .5rem;
            }

            /* Saat keyboard terbuka: jangan menghalangi input, ubah jadi layout biasa (non-fixed) */
            body.keyboard-open .footer-actions {
                position: static;
                margin-top: 1.25rem;
                box-shadow: none;
            }

            .footer-actions .btn {
                border-radius: 999px;
                box-shadow:
                    0 10px 22px rgba(15, 23, 42, 0.25),
                    0 3px 8px rgba(15, 23, 42, 0.18);
            }

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

            {{-- HEADER --}}
            <div class="card mb-2 header-card fin-header">
                <div class="card-section">
                    <div class="header-row">
                        <div class="d-flex align-items-center">
                            <div class="header-icon-circle">
                                <i class="bi bi-scissors"></i>
                            </div>
                            <div class="header-title d-flex flex-column gap-1">
                                <h1>Finishing</h1>
                                <div class="header-subtitle">
                                    Proses stok <b>WIP-FIN</b> menjadi barang jadi. Isi Qty Proses & Reject per item.
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

            {{-- MAIN CARD --}}
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

                    {{-- HIDDEN GLOBAL OPERATOR --}}
                    <input type="hidden" name="operator_global_id" id="operator_global_id_hidden"
                        value="{{ old('operator_global_id', $defaultOperator) }}">

                    {{-- META + FILTER --}}
                    <div class="card-section">
                        <div class="meta-row">
                            <div class="row g-2 g-md-3 align-items-end meta-grid">
                                {{-- Tanggal --}}
                                <div class="col-6 col-md-3 col-date">
                                    <label class="form-label form-label-sm">Tanggal</label>
                                    <input type="date" name="date"
                                        class="form-control form-control-sm @error('date') is-invalid @enderror"
                                        value="{{ $dateValue }}">
                                    @error('date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                {{-- Filter kode item (select) --}}
                                <div class="col-6 col-md-3 col-select">
                                    <label class="form-label form-label-sm d-none d-md-block">Filter kode item</label>
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
                                </div>

                                {{-- Cari kode (input) --}}
                                <div class="col-12 col-md-3 col-search">
                                    <label class="form-label form-label-sm d-none d-md-block">Cari kode</label>
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

                                {{-- Catatan global (desktop, mobile hide) --}}
                                <div class="col-12 col-md-3 fin-notes-col">
                                    <label class="form-label form-label-sm">Catatan</label>
                                    <textarea name="notes" rows="1" class="form-control form-control-sm" placeholder="Catatan finishing (opsional)">{{ old('notes', '') }}</textarea>
                                    @error('notes')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            {{-- Summary (desktop only, WIP-FIN disembunyikan untuk operating) --}}
                            <div class="filter-summary">
                                @if ($userRole !== 'operating')
                                    <span><strong>WIP-FIN:</strong> {{ number_format($grandWip, 0, ',', '.') }} pcs</span>
                                    <span class="filter-dot">•</span>
                                @endif
                                <span><strong>Item:</strong> {{ count($lines ?? []) }}</span>
                                <span class="filter-dot">•</span>
                                <span><strong>Tanggal:</strong>
                                    {{ \Carbon\Carbon::parse($dateValue)->format('d M Y') }}</span>
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
                                            @if ($userRole !== 'operating')
                                                <th class="text-end col-wip" style="width:16%;">Total WIP-FIN</th>
                                            @endif
                                            <th class="text-end col-ok" style="width:16%;">Proses</th>
                                            <th class="text-end col-reject" style="width:16%;">Reject</th>
                                            <th class="col-reason" style="width:17%;">Alasan / Catatan</th>
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
                                                $rejectNotes =
                                                    $oldLine['reject_notes'] ?? ($line['reject_notes'] ?? '');
                                                $fullLabel = $line['item_label'] ?? 'Item #' . $itemId;
                                                $codeOnly = Str::contains($fullLabel, ' - ')
                                                    ? Str::before($fullLabel, ' - ')
                                                    : $fullLabel;
                                            @endphp
                                            <tr data-search="{{ strtolower($codeOnly . ' ' . $itemId) }}"
                                                data-item-id="{{ $itemId }}">
                                                <td class="text-center align-middle col-index">
                                                    <span class="mobile-index-pill">
                                                        {{ $loop->iteration }}
                                                    </span>
                                                </td>

                                                <td class="col-item">
                                                    <div class="item-label-main-desktop">
                                                        {{ $fullLabel }}
                                                    </div>
                                                    <div class="item-label-main-mobile">
                                                        {{ $codeOnly }}
                                                    </div>
                                                </td>

                                                @if ($userRole !== 'operating')
                                                    <td class="text-end align-middle col-wip">
                                                        <span class="wip-badge">
                                                            <i class="bi bi-arrow-up-circle"></i>
                                                            <span>{{ number_format($totalWip, 0, ',', '.') }}</span>
                                                            <small>pcs</small>
                                                        </span>
                                                    </td>
                                                @endif

                                                {{-- Qty Proses (qty_in) --}}
                                                <td class="text-end align-middle col-ok">
                                                    <x-number-input :name="'lines[' . $idx . '][qty_in]'" :value="is_null($qtyIn) ? '' : (int) $qtyIn" mode="integer"
                                                        :min="0" :max="$totalWip"
                                                        class="form-control form-control-sm text-end integer-input qty-ok-input"
                                                        placeholder="Proses" />
                                                    @error("lines.$idx.qty_in")
                                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                                    @enderror
                                                </td>

                                                {{-- Qty Reject --}}
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
                                                        class="form-control form-control-sm mb-1"
                                                        placeholder="Alasan (opsional)" value="{{ $rejectReason }}">
                                                    @error("lines.$idx.reject_reason")
                                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                                    @enderror

                                                    <input type="text" name="lines[{{ $idx }}][reject_notes]"
                                                        class="form-control form-control-sm"
                                                        placeholder="Catatan reject (opsional)"
                                                        value="{{ $rejectNotes }}">
                                                    @error("lines.$idx.reject_notes")
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

                        {{-- Footer actions --}}
                        <div class="footer-actions">
                            <a href="{{ route('production.finishing_jobs.index') }}"
                                class="btn btn-sm btn-outline-secondary btn-back">
                                <i class="bi bi-arrow-left"></i>
                            </a>
                            {{-- pakai button biasa, submit lewat modal --}}
                            <button type="button" class="btn btn-sm btn-primary btn-save" disabled>
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
                                                <span>Total Proses</span>
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
                                        Pastikan <strong>Qty Proses</strong> dan <strong>Qty Reject</strong> sudah benar.
                                        Qty OK akan dihitung otomatis sebagai Proses - Reject per item.
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

            function renumberRows() {
                if (!table) return;
                const rows = table.querySelectorAll('tbody tr');
                let counter = 1;

                rows.forEach(row => {
                    if (row.style.display === 'none') {
                        return;
                    }
                    const cell = row.querySelector('td.col-index span.mobile-index-pill');
                    if (cell) {
                        cell.textContent = counter++;
                    }
                });
            }

            function updateSaveButtonState() {
                if (!saveBtn) return;

                const hasAnyValue = integerInputs.some(input => isPositiveInt(input.value));
                saveBtn.disabled = !hasAnyValue;
                saveBtn.classList.toggle('disabled', !hasAnyValue);
            }

            function computeSummary() {
                let totalProses = 0;
                let totalReject = 0;

                integerInputs.forEach(i => {
                    const name = i.name || '';
                    const match = name.match(/lines\[(\d+)\]\[(qty_in|qty_reject)\]/);
                    if (!match) return;

                    const val = parseInt(i.value || '0', 10) || 0;
                    if (match[2] === 'qty_in') {
                        totalProses += val;
                    } else if (match[2] === 'qty_reject') {
                        totalReject += val;
                    }
                });

                if (summaryOkEl) summaryOkEl.textContent = totalProses.toString();
                if (summaryRejectEl) summaryRejectEl.textContent = totalReject.toString();

                const dateInput = document.querySelector('input[name="date"]');
                if (dateInput && summaryDateEl && dateInput.value) {
                    summaryDateEl.textContent = dateInput.value;
                }
            }

            integerInputs.forEach(el => {
                el.addEventListener('input', function() {
                    if (isRejectInput(this)) {
                        const normalized = sanitizeToIntString(this.value, {
                            allowEmpty: false
                        });
                        this.value = normalized;
                    } else {
                        const normalized = sanitizeToIntString(this.value, {
                            allowEmpty: true
                        });
                        this.value = normalized;
                    }

                    updateSaveButtonState();
                    computeSummary();
                });

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

                el.addEventListener('wheel', function(ev) {
                    if (document.activeElement === this) {
                        ev.preventDefault();
                    }
                }, {
                    passive: false
                });
            });

            if (table) {
                table.addEventListener('input', function(e) {
                    const target = e.target;
                    if (!target.name) return;

                    const match = target.name.match(/lines\[(\d+)\]\[(\w+)\]/);
                    if (!match) return;

                    const idx = match[1];
                    const field = match[2];

                    const qtyInEl = document.querySelector(`[name="lines[${idx}][qty_in]"]`);
                    const qtyRejectEl = document.querySelector(`[name="lines[${idx}][qty_reject]"]`);
                    const totalWipEl = document.querySelector(`[name="lines[${idx}][total_wip]"]`);

                    const totalWip = Math.max(0, parseInt(totalWipEl?.value || 0, 10) || 0);

                    let qtyIn = Math.max(0, parseInt(qtyInEl?.value || 0, 10) || 0);
                    let qtyReject = Math.max(0, parseInt(qtyRejectEl?.value || 0, 10) || 0);

                    if (qtyIn > totalWip) qtyIn = totalWip;
                    if (qtyReject > totalWip) qtyReject = totalWip;

                    if (qtyIn + qtyReject > totalWip) {
                        if (field === 'qty_in') {
                            qtyIn = Math.max(0, totalWip - qtyReject);
                        } else if (field === 'qty_reject') {
                            qtyReject = Math.max(0, totalWip - qtyIn);
                        }
                    }

                    if (qtyInEl) qtyInEl.value = qtyIn === 0 ? '' : String(qtyIn);
                    if (qtyRejectEl) qtyRejectEl.value = String(qtyReject);

                    updateSaveButtonState();
                    computeSummary();
                });
            }

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

            updateSaveButtonState();
            computeSummary();
            renumberRows();
        });
    </script>
@endpush
