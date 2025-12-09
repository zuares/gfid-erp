{{-- resources/views/production/sewing_pickups/create.blade.php --}}
@extends('layouts.app')

@section('title', 'Produksi • Sewing Pickup')

@push('head')
    <style>
        .sewing-pickup-page {
            min-height: 100vh;
        }

        .sewing-pickup-page .page-wrap {
            max-width: 1150px;
            margin-inline: auto;
            padding: 1rem 1rem 4rem;
        }

        body[data-theme="light"] .sewing-pickup-page .page-wrap {
            background:
                radial-gradient(circle at top left,
                    rgba(59, 130, 246, 0.12) 0,
                    rgba(45, 212, 191, 0.10) 26%,
                    #f9fafb 60%);
        }

        body[data-theme="dark"] .sewing-pickup-page .page-wrap {
            background:
                radial-gradient(circle at top left,
                    rgba(59, 130, 246, 0.25) 0,
                    rgba(45, 212, 191, 0.15) 26%,
                    #020617 60%);
        }

        .card-main {
            background: var(--card);
            border-radius: 18px;
            border: 1px solid var(--line);
            box-shadow:
                0 18px 45px rgba(15, 23, 42, 0.28),
                0 0 0 1px rgba(15, 23, 42, 0.05);
            overflow: hidden;
        }

        body[data-theme="dark"] .card-main {
            box-shadow:
                0 18px 55px rgba(0, 0, 0, 0.9),
                0 0 0 1px rgba(15, 23, 42, 0.9);
        }

        .card-section {
            padding: 1rem 1.25rem;
        }

        @media (min-width: 768px) {
            .card-section {
                padding: 1rem 1.5rem;
            }
        }

        /* HEADER STYLE – mirip Sewing Return */
        .header-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: .75rem;
        }

        .header-icon-circle {
            width: 40px;
            height: 40px;
            border-radius: 999px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.15rem;
            margin-right: .75rem;
        }

        .pickup-icon-circle {
            background: rgba(37, 99, 235, 0.10);
            color: #2563eb;
            border: 1px solid rgba(37, 99, 235, 0.25);
        }

        body[data-theme="dark"] .pickup-icon-circle {
            background: rgba(37, 99, 235, 0.25);
            border-color: rgba(147, 197, 253, 0.45);
            color: #bfdbfe;
        }

        .header-title h1 {
            font-size: 1.1rem;
            margin: 0;
        }

        .header-subtitle {
            font-size: .82rem;
            color: var(--muted);
        }

        .btn-header-pill {
            border-radius: 999px;
            padding: .32rem .9rem;
            font-size: .78rem;
            font-weight: 600;
        }

        .btn-header-accent {
            background: rgba(59, 130, 246, .12);
            border: 1px solid rgba(59, 130, 246, .35);
            color: #1d4ed8;
        }

        body[data-theme="dark"] .btn-header-accent {
            background: rgba(59, 130, 246, .22);
            border-color: rgba(147, 197, 253, .48);
            color: #bfdbfe;
        }

        .btn-header-secondary {
            border-radius: 999px;
            font-size: .8rem;
            padding-inline: .85rem;
            padding-block: .25rem;
        }

        .btn-header-secondary i {
            font-size: .9rem;
        }

        .field-block {
            margin-bottom: .5rem;
        }

        .field-label {
            font-size: .78rem;
            font-weight: 500;
            color: var(--muted);
            margin-bottom: .15rem;
        }

        .field-input-sm {
            font-size: .85rem;
        }

        .field-static {
            font-size: .85rem;
            display: flex;
            flex-wrap: wrap;
            gap: .35rem;
            align-items: baseline;
        }

        .field-static .code {
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, "Liberation Mono";
            font-size: .82rem;
        }

        .field-static .name {
            font-size: .82rem;
            color: var(--muted);
        }

        .form-footer {
            margin-top: 1rem;
            padding-top: .75rem;
            border-top: 1px dashed rgba(148, 163, 184, 0.45);
        }

        .form-footer .btn {
            min-width: 80px;
        }

        #btn-submit-main[disabled] {
            opacity: .85;
            cursor: not-allowed;
        }

        @media (max-width: 767.98px) {
            .sewing-pickup-page .page-wrap {
                padding-inline: .75rem;
            }

            .header-row {
                flex-direction: column;
                align-items: flex-start;
            }

            .header-title h1 {
                font-size: 1rem;
            }

            /* BUTTON SETOR JAHIT FULL WIDTH DI MOBILE */
            .header-row .btn-header-pill {
                align-self: stretch;
                width: 100%;
                display: inline-flex;
                justify-content: center;
            }

            .form-footer {
                flex-direction: column-reverse;
                align-items: stretch !important;
                gap: .5rem;
            }

            .form-footer .btn {
                width: 100%;
            }

            .form-footer .btn-outline-secondary span {
                display: inline;
            }
        }

        /* kecilkan alert sedikit di halaman ini */
        .sewing-pickup-page .alert {
            font-size: .82rem;
        }

        .spin-slow {
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }
    </style>
@endpush

@section('content')
    <div class="sewing-pickup-page">
        <div class="page-wrap">

            {{-- FLASH & ERROR GLOBAL --}}
            @if (session('status'))
                <div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
                    {{ session('status') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
                    <strong>Periksa lagi isian ambil jahit.</strong>
                    <ul class="mb-0 small">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @php
                $defaultWarehouseId = old('warehouse_id') ?: optional($warehouses->firstWhere('code', 'WIP-SEW'))->id;
                $defaultWarehouse = $defaultWarehouseId ? $warehouses->firstWhere('id', $defaultWarehouseId) : null;
            @endphp

            {{-- HEADER – mirip Sewing Return, tapi khas Sewing Pickup --}}
            <div class="card mb-2">
                <div class="card-section">
                    <div class="header-row">

                        {{-- KIRI: ICON + TITLE --}}
                        <div class="d-flex align-items-center">
                            <div class="header-icon-circle pickup-icon-circle">
                                <i class="bi bi-bag-plus"></i>
                            </div>

                            <div class="header-title d-flex flex-column gap-1">
                                <h1>Halaman Ambil Jahit</h1>
                                <div class="header-subtitle">
                                    Pilih bundle dari WIP Cutting dan masukkan jumlah yang akan dibawa untuk proses jahit.
                                </div>
                            </div>
                        </div>

                        {{-- KANAN: Aksi cepat --}}
                        <div class="d-flex flex-column flex-md-row gap-2">
                            <a href="{{ route('production.sewing_returns.create') }}"
                                class="btn btn-sm btn-header-pill btn-header-accent d-flex align-items-center gap-2">
                                <i class="bi bi-clipboard-check"></i>
                                <span>Setor Jahit</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            @php
                // default operator (diset otomatis, bisa diubah dari modal)
                $autoDefaultOperatorId = (int) old('operator_id') ?: optional($operators->first())->id;
                $selectedOperator = $operators->firstWhere('id', $autoDefaultOperatorId);
            @endphp

            <form id="sewing-pickup-form" action="{{ route('production.sewing_pickups.store') }}" method="post"
                data-mobile-primary-form="1">
                @csrf

                {{-- Hidden operator_id (di-set lewat modal), dan gudang --}}
                <input type="hidden" name="operator_id" id="operator_id_hidden" value="{{ $autoDefaultOperatorId }}">
                <input type="hidden" name="warehouse_id" value="{{ $defaultWarehouseId }}">

                <div class="card-main mb-3">
                    {{-- HEADER FORM: TANGGAL + GUDANG --}}
                    <div class="card-section">
                        <div class="row g-3 align-items-end">
                            <div class="col-12 col-md-3">
                                <div class="field-block">
                                    <div class="field-label">Tanggal ambil</div>
                                    <input type="date" name="date"
                                        class="form-control form-control-sm field-input-sm @error('date') is-invalid @enderror"
                                        value="{{ old('date', now()->format('Y-m-d')) }}">
                                    @error('date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-12 col-md-4 gudang-section">
                                <div class="field-block">
                                    <div class="field-label">Gudang tujuan</div>
                                    <div class="field-static">
                                        @if ($defaultWarehouse)
                                            <span class="code">{{ $defaultWarehouse->code }}</span>
                                            <span class="name">— {{ $defaultWarehouse->name }}</span>
                                        @else
                                            <span class="text-danger small">Gudang WIP-SEW belum diset.</span>
                                        @endif
                                    </div>
                                    @error('warehouse_id')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            {{-- kolom kanan dikosongkan agar simetris --}}
                            <div class="col-12 col-md-5"></div>
                        </div>
                    </div>
                </div>

                {{-- LIST BUNDLE + FILTER + MOBILE CARD --}}
                @include('production.sewing_pickups._bundle_picker', [
                    'bundles' => $bundles,
                ])

                {{-- SUBMIT --}}
                <div class="d-flex justify-content-between align-items-center mb-5 form-footer">
                    <a href="{{ url()->previous() }}" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-arrow-left"></i>
                        <span class="d-none d-sm-inline">Batal</span>
                    </a>

                    <button type="submit" class="btn btn-sm btn-primary" id="btn-submit-main" disabled>
                        <i class="bi bi-check2-circle" id="btn-submit-icon"></i>
                        <span class="text-light" id="btn-submit-label">Belum Ambil</span>
                    </button>
                </div>
            </form>

            {{-- Modal pilih operator --}}
            @include('production.sewing_pickups._operator_modal', [
                'operators' => $operators,
            ])
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const form = document.getElementById('sewing-pickup-form');
            const btn = document.getElementById('btn-submit-main');
            const icon = document.getElementById('btn-submit-icon');
            const label = document.getElementById('btn-submit-label');

            if (!form || !btn || !icon || !label) return;

            form.addEventListener('submit', () => {
                if (btn.disabled) return;

                btn.disabled = true;
                icon.classList.remove('bi-check2-circle');
                icon.classList.add('bi-arrow-repeat', 'spin-slow');
                label.textContent = 'Menyimpan...';
            });
        });
    </script>
@endpush
