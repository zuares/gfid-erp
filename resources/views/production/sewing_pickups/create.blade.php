{{-- resources/views/production/sewing_pickups/create.blade.php --}}
@extends('layouts.app')

@section('title', 'Produksi • Sewing Pickup')

@section('content')
    <div class="page-wrap py-3 py-md-4">

        {{-- HEADER PAGE --}}
        <div class="card mb-3">
            <div class="card-section">
                <div class="header-row">
                    <div class="header-title">
                        <h1>Halaman Ambil Jahit</h1>
                        <div class="header-subtitle">
                            Pilih bundle dan isi qty pickup. Operator jahit dipilih saat menekan <strong>Simpan</strong>.
                        </div>
                    </div>

                    <a href="{{ route('production.sewing_returns.create') }}"
                        class="btn btn-sm btn-outline-primary d-inline-flex align-items-center gap-1 btn-header-secondary">
                        <i class="bi bi-box-seam"></i>
                        <span>Setor Jahit</span>
                    </a>
                </div>
            </div>
        </div>

        <form id="sewing-pickup-form" action="{{ route('production.sewing_pickups.store') }}" method="post">
            @csrf

            @php
                $defaultWarehouseId = old('warehouse_id') ?: optional($warehouses->firstWhere('code', 'WIP-SEW'))->id;
                $defaultWarehouse = $defaultWarehouseId ? $warehouses->firstWhere('id', $defaultWarehouseId) : null;

                // default operator (hidden, dipilih / diubah di modal)
                $autoDefaultOperatorId = (int) old('operator_id') ?: optional($operators->first())->id;
            @endphp

            {{-- Hidden operator_id (di-set lewat modal), dan gudang --}}
            <input type="hidden" name="operator_id" id="operator_id_hidden" value="{{ $autoDefaultOperatorId }}">
            <input type="hidden" name="warehouse_id" value="{{ $defaultWarehouseId }}">

            {{-- HEADER FORM: TANGGAL + GUDANG (desktop only) --}}
            <div class="card mb-3">
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

                        <div class="col-12 col-md-5 gudang-section">
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

                        <div class="col-12 col-md-4 d-none d-md-block">
                            <div class="help">
                                Operator jahit akan dipilih di langkah konfirmasi saat menyimpan transaksi.
                            </div>
                        </div>
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
                    <span class="text-light" id="btn-submit-label">Simpan</span>
                </button>
            </div>
        </form>
    </div>

    {{-- Modal konfirmasi + pilih operator (dipisah ke partial) --}}
    @include('production.sewing_pickups._operator_modal', [
        'operators' => $operators,
    ])
@endsection
