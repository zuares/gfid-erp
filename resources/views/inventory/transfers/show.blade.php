{{-- resources/views/inventory/transfers/show.blade.php --}}
@extends('layouts.app')

@section('title', 'Transfer ' . $transfer->code)

@section('content')
    <div class="container py-3">

        {{-- HEADER --}}
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h1 class="h4 mb-0">Detail Transfer Stok</h1>
                <div class="small text-muted">
                    {{ $transfer->code }} &middot; Tanggal: {{ $transfer->date?->format('Y-m-d') }}
                </div>
            </div>
            <a href="{{ route('inventory.transfers.index') }}" class="btn btn-outline-secondary btn-sm">
                &larr; Kembali
            </a>
        </div>

        {{-- ALERTS --}}
        @if (session('success'))
            <div class="alert alert-success py-2">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->has('general'))
            <div class="alert alert-danger py-2">
                {{ $errors->first('general') }}
            </div>
        @endif

        <div class="row g-3 mb-3">
            {{-- INFO HEADER --}}
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-body small">
                        <h6 class="fw-semibold mb-3">Info Transfer</h6>
                        <dl class="row mb-0">
                            <dt class="col-sm-4">Kode</dt>
                            <dd class="col-sm-8">{{ $transfer->code }}</dd>

                            <dt class="col-sm-4">Tanggal</dt>
                            <dd class="col-sm-8">{{ $transfer->date?->format('Y-m-d') }}</dd>

                            <dt class="col-sm-4">Gudang Asal</dt>
                            <dd class="col-sm-8">
                                @if ($transfer->fromWarehouse)
                                    {{ $transfer->fromWarehouse->name }} ({{ $transfer->fromWarehouse->code }})
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </dd>

                            <dt class="col-sm-4">Gudang Tujuan</dt>
                            <dd class="col-sm-8">
                                @if ($transfer->toWarehouse)
                                    {{ $transfer->toWarehouse->name }} ({{ $transfer->toWarehouse->code }})
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </dd>

                            <dt class="col-sm-4">Dibuat oleh</dt>
                            <dd class="col-sm-8">
                                {{ $transfer->creator?->name ?? '-' }}
                            </dd>

                            <dt class="col-sm-4">Catatan Header</dt>
                            <dd class="col-sm-8">
                                {{ $transfer->notes ?: '-' }}
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>

            {{-- RINGKASAN TOTAL --}}
            <div class="col-md-6">
                @php
                    $totalQty = $transfer->lines->sum('qty');
                @endphp
                <div class="card h-100">
                    <div class="card-body small">
                        <h6 class="fw-semibold mb-3">Ringkasan</h6>
                        <dl class="row mb-0">
                            <dt class="col-sm-5">Jumlah Item</dt>
                            <dd class="col-sm-7 text-end">
                                {{ $transfer->lines->count() }}
                            </dd>

                            <dt class="col-sm-5">Total Qty</dt>
                            <dd class="col-sm-7 text-end">
                                {{ number_format($totalQty, 3, ',', '.') }}
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        {{-- DETAIL ITEM --}}
        <div class="card">
            <div class="card-header">
                <span class="fw-semibold">Detail Item yang Dipindahkan</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 40%">Item</th>
                                <th class="text-end" style="width: 15%">Qty</th>
                                <th style="width: 45%">Catatan</th>
                            </tr>
                        </thead>
                        <tbody class="small">
                            @forelse ($transfer->lines as $line)
                                <tr>
                                    <td>
                                        @if ($line->item)
                                            <div class="fw-semibold">{{ $line->item->name }}</div>
                                            <div class="text-muted small">{{ $line->item->code }}</div>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        {{ number_format($line->qty, 3, ',', '.') }}
                                    </td>
                                    <td>
                                        {{ $line->notes ?: '-' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center text-muted py-3">
                                        Tidak ada detail item.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                        <tfoot class="table-light small">
                            <tr>
                                <th class="text-end">Total Qty</th>
                                <th class="text-end">
                                    {{ number_format($totalQty, 3, ',', '.') }}
                                </th>
                                <th></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

    </div>
@endsection
