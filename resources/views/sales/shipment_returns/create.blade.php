{{-- resources/views/sales/shipment_returns/create.blade.php --}}
@extends('layouts.app')

@section('title', 'Sales • Retur Shipment Baru')

@push('head')
    <style>
        :root {
            --ret-main: rgba(59, 130, 246, 1);
            --ret-soft: rgba(59, 130, 246, .12);
        }

        .page-wrap {
            max-width: 900px;
            margin-inline: auto;
            padding: 1rem .9rem 4.5rem;
        }

        body[data-theme="light"] .page-wrap {
            background: radial-gradient(circle at top left,
                    rgba(59, 130, 246, .10) 0,
                    rgba(45, 212, 191, .06) 30%,
                    #f9fafb 70%);
        }

        .card {
            background: var(--card);
            border-radius: 14px;
            border: 1px solid var(--line);
            box-shadow:
                0 18px 45px rgba(15, 23, 42, 0.08),
                0 0 0 1px rgba(15, 23, 42, 0.02);
        }

        body[data-theme="light"] .card {
            background: #ffffff;
        }

        .card-header-bar {
            padding: .9rem 1rem;
            border-bottom: 1px solid rgba(148, 163, 184, .35);
        }

        .card-title-main {
            font-size: 1rem;
            font-weight: 600;
            letter-spacing: .04em;
            text-transform: uppercase;
            color: var(--muted);
        }

        .card-body {
            padding: 1rem;
        }

        .field-label {
            font-size: .8rem;
            font-weight: 500;
            color: var(--muted);
            margin-bottom: .25rem;
        }

        .field-help {
            font-size: .78rem;
            color: var(--muted);
        }

        .form-control,
        .form-select,
        textarea {
            border-radius: 10px;
            border: 1px solid rgba(148, 163, 184, .7);
            font-size: .9rem;
        }

        .form-control:focus,
        .form-select:focus,
        textarea:focus {
            border-color: var(--ret-main);
            box-shadow: 0 0 0 1px color-mix(in srgb, var(--ret-main) 80%, transparent);
        }

        .btn-main {
            border-radius: 999px;
            border: 1px solid transparent;
            padding: .5rem 1.2rem;
            font-size: .9rem;
            font-weight: 500;
            background: linear-gradient(135deg, var(--ret-main), #22c55e);
            color: #ffffff;
            display: inline-flex;
            align-items: center;
            gap: .35rem;
        }

        .btn-main:hover {
            filter: brightness(1.05);
            color: #ffffff;
        }

        .badge-info-shipment {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            border-radius: 999px;
            padding: .2rem .7rem;
            font-size: .78rem;
            background: rgba(234, 179, 8, .14);
            color: #92400e;
        }

        .badge-info-shipment .mono {
            font-variant-numeric: tabular-nums;
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, "Liberation Mono";
        }
    </style>
@endpush

@section('content')
    <div class="page-wrap">
        <div class="card">
            <div class="card-header-bar">
                <div class="card-title-main">Retur Shipment Baru</div>
                <div style="font-size:.85rem; color:var(--muted); margin-top:.15rem;">
                    Buat header retur pengiriman sebelum scan barang yang kembali.
                </div>
            </div>

            <div class="card-body">
                @if ($errors->any())
                    <div class="alert alert-danger small">
                        <div style="font-weight:600; margin-bottom:.25rem;">Terjadi kesalahan:</div>
                        <ul class="mb-0 ps-3">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                {{-- Info shipment asal (opsional) --}}
                @if ($shipment)
                    <div class="mb-3">
                        <div class="field-label">Shipment Asal</div>
                        <div class="badge-info-shipment">
                            <span>Retur dari</span>
                            <span class="mono">{{ $shipment->code }}</span>
                            @if ($shipment->store)
                                <span>| {{ $shipment->store->code ?? '' }} - {{ $shipment->store->name ?? '' }}</span>
                            @endif
                        </div>
                    </div>
                @endif

                <form action="{{ route('sales.shipment_returns.store') }}" method="POST" autocomplete="off">
                    @csrf

                    @if ($shipment)
                        <input type="hidden" name="shipment_id" value="{{ $shipment->id }}">
                    @endif

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="field-label">Store</label>
                            <select name="store_id" class="form-select" required>
                                <option value="">Pilih store...</option>
                                @foreach ($stores as $store)
                                    <option value="{{ $store->id }}" @selected(old('store_id', $shipment->store_id ?? null) == $store->id)>
                                        {{ $store->code ?? '-' }} — {{ $store->name }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="field-help mt-1">
                                Store yang mengirimkan retur kembali ke WH-RTS.
                            </div>
                        </div>

                        <div class="col-md-3">
                            <label class="field-label">Tanggal</label>
                            <input type="date" name="date" class="form-control"
                                value="{{ old('date', now()->toDateString()) }}" required>
                        </div>

                        <div class="col-md-12">
                            <label class="field-label">Alasan Retur (opsional)</label>
                            <textarea name="reason" rows="2" class="form-control"
                                placeholder="Contoh: cancel order, salah kirim, reject QC store, dsb.">{{ old('reason') }}</textarea>
                        </div>

                        <div class="col-md-12">
                            <label class="field-label">Catatan Tambahan (opsional)</label>
                            <textarea name="notes" rows="2" class="form-control" placeholder="Catatan internal jika diperlukan.">{{ old('notes') }}</textarea>
                        </div>
                    </div>

                    <div class="mt-4 d-flex justify-content-between align-items-center">
                        <a href="{{ route('sales.shipment_returns.index') }}" class="btn btn-sm btn-outline-secondary">
                            ← Kembali
                        </a>

                        <button type="submit" class="btn-main">
                            <span>Simpan & Lanjut Scan</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
