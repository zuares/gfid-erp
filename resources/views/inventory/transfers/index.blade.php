{{-- resources/views/inventory/transfers/index.blade.php --}}
@extends('layouts.app')

@section('title', 'Transfer Stok')

@section('content')
    <div class="container py-3">

        {{-- HEADER --}}
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
            <div>
                <h1 class="h4 mb-0">Transfer Stok Antar Gudang</h1>
                <div class="small text-muted">
                    Riwayat mutasi stok dari satu gudang ke gudang lain (multi-item).
                </div>
            </div>
            <a href="{{ route('inventory.transfers.create') }}" class="btn btn-primary">
                + Transfer Baru
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

        {{-- FILTER --}}
        <div class="card mb-3">
            <div class="card-body">
                <form method="GET" class="row g-2 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label small">Gudang Asal</label>
                        <select name="from_warehouse_id" class="form-select form-select-sm">
                            <option value="">Semua</option>
                            @foreach ($warehouses as $wh)
                                <option value="{{ $wh->id }}" @selected(request('from_warehouse_id') == $wh->id)>
                                    {{ $wh->code }} - {{ $wh->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label small">Gudang Tujuan</label>
                        <select name="to_warehouse_id" class="form-select form-select-sm">
                            <option value="">Semua</option>
                            @foreach ($warehouses as $wh)
                                <option value="{{ $wh->id }}" @selected(request('to_warehouse_id') == $wh->id)>
                                    {{ $wh->code }} - {{ $wh->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label small">Item (di detail)</label>
                        <select name="item_id" class="form-select form-select-sm">
                            <option value="">Semua</option>
                            @foreach ($items as $item)
                                <option value="{{ $item->id }}" @selected(request('item_id') == $item->id)>
                                    {{ $item->code }} - {{ $item->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-1">
                        <label class="form-label small">Dari</label>
                        <input type="date" name="from_date" class="form-control form-control-sm"
                            value="{{ request('from_date') }}">
                    </div>
                    <div class="col-md-1">
                        <label class="form-label small">Sampai</label>
                        <input type="date" name="to_date" class="form-control form-control-sm"
                            value="{{ request('to_date') }}">
                    </div>

                    <div class="col-md-1 d-flex gap-1">
                        <button type="submit" class="btn btn-primary btn-sm w-100">
                            Filter
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- TABLE --}}
        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 11%">Tanggal</th>
                                <th style="width: 13%">Kode</th>
                                <th style="width: 19%">Gudang Asal</th>
                                <th style="width: 19%">Gudang Tujuan</th>
                                <th style="width: 23%">Item</th>
                                <th class="text-end" style="width: 10%">Total Qty</th>
                                <th class="text-end" style="width: 5%"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($transfers as $trf)
                                @php
                                    $firstLine = $trf->lines->first();
                                    $lineCount = $trf->lines->count();
                                    $totalQty = $trf->lines->sum('qty');
                                @endphp

                                <tr>
                                    <td>{{ $trf->date?->format('Y-m-d') }}</td>
                                    <td>
                                        <a href="{{ route('inventory.transfers.show', $trf->id) }}">
                                            {{ $trf->code }}
                                        </a>
                                    </td>
                                    <td>
                                        @if ($trf->fromWarehouse)
                                            <div class="fw-semibold">{{ $trf->fromWarehouse->code }}</div>
                                            <div class="small text-muted">{{ $trf->fromWarehouse->name }}</div>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($trf->toWarehouse)
                                            <div class="fw-semibold">{{ $trf->toWarehouse->code }}</div>
                                            <div class="small text-muted">{{ $trf->toWarehouse->name }}</div>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($firstLine && $firstLine->item)
                                            <div class="fw-semibold">{{ $firstLine->item->name }}</div>
                                            <div class="small text-muted">
                                                {{ $firstLine->item->code }}
                                                @if ($lineCount > 1)
                                                    &middot; +{{ $lineCount - 1 }} item lain
                                                @endif
                                            </div>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        {{ number_format($totalQty, 3, ',', '.') }}
                                    </td>
                                    <td class="text-end">
                                        <a href="{{ route('inventory.transfers.show', $trf->id) }}"
                                            class="btn btn-outline-secondary btn-sm">
                                            Detail
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-3">
                                        Belum ada transfer stok.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($transfers instanceof \Illuminate\Pagination\AbstractPaginator)
                    <div class="p-2">
                        {{ $transfers->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
