{{-- resources/views/production/cutting_jobs/create.blade.php --}}
@extends('layouts.app')

@section('title', 'Cutting Job Baru')

@push('head')
    <style>
        .cutting-create-page {
            min-height: 100vh;
        }

        .cutting-create-page .page-wrap {
            max-width: 1150px;
            margin-inline: auto;
            padding: 1rem 1rem 3rem;
        }

        body[data-theme="light"] .cutting-create-page .page-wrap {
            background: radial-gradient(circle at top left,
                    rgba(59, 130, 246, 0.12) 0,
                    rgba(45, 212, 191, 0.10) 26%,
                    #f9fafb 60%);
        }

        .cutting-card {
            background: var(--card);
            border-radius: 16px;
            border: 1px solid rgba(148, 163, 184, 0.35);
            box-shadow:
                0 10px 40px rgba(15, 23, 42, 0.08),
                0 0 0 1px rgba(148, 163, 184, 0.18);
            margin-bottom: 1rem;
        }

        .cutting-card-header {
            padding: .75rem 1rem;
            border-bottom: 1px solid rgba(148, 163, 184, 0.35);
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: .5rem;
        }

        .cutting-card-header h5 {
            margin: 0;
            font-size: .95rem;
            font-weight: 600;
            letter-spacing: .02em;
            text-transform: uppercase;
        }

        .cutting-card-body {
            padding: .9rem 1rem 1rem;
        }

        .badge-soft {
            border-radius: 999px;
            padding: .1rem .6rem;
            font-size: .7rem;
            border: 1px solid rgba(148, 163, 184, 0.5);
            background: rgba(148, 163, 184, 0.09);
            white-space: nowrap;
        }

        .mono {
            font-variant-numeric: tabular-nums;
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, "Liberation Mono";
        }

        .lot-list-table {
            width: 100%;
            font-size: .8rem;
        }

        .lot-list-table th,
        .lot-list-table td {
            padding: .35rem .45rem;
        }

        .lot-list-table thead th {
            border-bottom: 1px solid rgba(148, 163, 184, 0.5);
            white-space: nowrap;
        }

        .lot-list-table tbody tr:nth-child(odd) {
            background: rgba(148, 163, 184, 0.05);
        }

        .lot-list-table tbody tr.lot-hidden {
            display: none;
        }

        .bundles-table-wrap {
            overflow-x: auto;
        }

        .bundles-table {
            width: 100%;
            font-size: .82rem;
            border-collapse: separate;
            border-spacing: 0;
        }

        .bundles-table thead th {
            position: sticky;
            top: 0;
            background: var(--card);
            z-index: 1;
            padding: .45rem .5rem;
            border-bottom: 1px solid rgba(148, 163, 184, 0.7);
            white-space: nowrap;
        }

        .bundles-table tbody td {
            padding: .35rem .5rem;
            vertical-align: middle;
        }

        .bundles-table tbody tr:nth-child(odd) {
            background: rgba(148, 163, 184, 0.04);
        }

        .bundles-table tfoot td {
            padding: .4rem .5rem;
            border-top: 1px solid rgba(148, 163, 184, 0.5);
        }

        .bundle-row-deleted {
            opacity: .4;
        }

        .lot-summary-list {
            list-style: none;
            padding-left: 0;
            margin: 0;
            font-size: .8rem;
        }

        .lot-summary-list li {
            display: flex;
            justify-content: space-between;
            gap: .5rem;
            padding: .15rem 0;
        }

        .lot-summary-list li.over {
            color: #b91c1c;
            font-weight: 600;
        }

        .btn-pill-sm {
            border-radius: 999px;
            font-size: .78rem;
            padding-inline: .8rem;
            padding-block: .15rem;
        }

        /* Qty default right, di mobile jadi center */
        .bundle-qty-pcs {
            text-align: right;
        }

        /* LOT kolom di bundles disembunyikan di semua device (desktop+mobile) */
        .bundles-table thead th.bundle-lot-col,
        .bundles-table tbody td.bundle-lot-col {
            display: none;
        }

        /* Wrapper konten utama (info job, bundles, summary) disembunyikan dulu */
        .cutting-main-content.d-none {
            display: none !important;
        }

        /* ========== MOBILE TWEAKS ========== */
        @media (max-width: 767.98px) {
            .cutting-create-page .page-wrap {
                padding: .5rem .75rem 2rem;
            }

            .cutting-card {
                border-radius: 14px;
                box-shadow:
                    0 8px 28px rgba(15, 23, 42, 0.08),
                    0 0 0 1px rgba(148, 163, 184, 0.18);
            }

            .cutting-card-header {
                flex-direction: column;
                align-items: flex-start;
                gap: .25rem;
            }

            .cutting-card-header h5 {
                font-size: .9rem;
            }

            .cutting-card-body {
                padding: .75rem .85rem .85rem;
            }

            .badge-soft {
                font-size: .68rem;
                padding-inline: .5rem;
            }

            .lot-list-table {
                font-size: .75rem;
            }

            /* Sembunyikan kolom Item & Gudang di LOT list (mobile) */
            .lot-list-table thead th:nth-child(3),
            .lot-list-table thead th:nth-child(5),
            .lot-list-table tbody td:nth-child(3),
            .lot-list-table tbody td:nth-child(5) {
                display: none;
            }

            .cutting-card-body .mb-2.d-flex {
                flex-direction: column;
                align-items: flex-start;
                gap: .35rem;
            }

            .cutting-card-body .mb-2 .d-flex.gap-1 {
                width: 100%;
                justify-content: flex-start;
                flex-wrap: wrap;
            }

            .btn-pill-sm {
                font-size: .72rem;
                padding-block: .2rem;
            }

            .bundles-table {
                font-size: .78rem;
            }

            .bundles-table thead th {
                font-size: .75rem;
            }

            /* Sembunyikan kolom catatan (info + bundles) di mobile */
            .cutting-notes-field {
                display: none;
            }

            .bundles-table thead th.bundle-notes-header,
            .bundles-table tbody td.bundle-notes-cell {
                display: none;
            }

            .lot-summary-list {
                font-size: .78rem;
            }

            .lot-summary-list li {
                flex-direction: column;
                align-items: flex-start;
            }

            .lot-summary-list li span:last-child {
                margin-top: .05rem;
            }

            /* Qty center di mobile */
            .bundle-qty-pcs {
                text-align: center;
            }

            /* Tombol bawah ditumpuk & full width */
            .cutting-actions {
                flex-direction: column;
                align-items: stretch;
                gap: .5rem;
            }

            .cutting-actions .btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
@endpush

@section('content')
    <div class="cutting-create-page">
        <div class="page-wrap">
            {{-- FLASH --}}
            @if (session('success'))
                <div class="alert alert-success py-2 px-3 mb-2">
                    {{ session('success') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger py-2 px-3 mb-2">
                    <div class="small fw-semibold mb-1">Terjadi kesalahan:</div>
                    <ul class="mb-0 small">
                        @foreach ($errors->all() as $err)
                            <li>{{ $err }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- FORM DIPISAH KE PARTIAL --}}
            @include('production.cutting_jobs._form')
        </div>
    </div>
@endsection
