@extends('layouts.app')

@section('title', 'Inventory • WIP Adjustments')

@section('content')
    @php
        $user = auth()->user();
        $role = $user?->role ?? null;
    @endphp

    <div class="container-xl py-3">

        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h1 class="h5 mb-0">WIP Adjustments</h1>
                <div class="text-muted" style="font-size:.85rem;">
                    Penyesuaian stok gudang WIP (Cut / Sew / Fin)
                </div>
            </div>
            <a href="{{ route('inventory.wip_adjustments.create') }}" class="btn btn-sm btn-primary">
                + Buat WIP Adjustment
            </a>
        </div>

        {{-- FILTER --}}
        <form method="GET" class="card card-body mb-3">
            <div class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label form-label-sm">Gudang WIP</label>
                    <select name="warehouse_id" class="form-select form-select-sm">
                        <option value="">Semua</option>
                        @foreach ($warehouses as $wh)
                            <option value="{{ $wh->id }}" @selected($filters['warehouse_id'] == $wh->id)>
                                {{ $wh->code }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label form-label-sm">Status</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="all">Semua</option>
                        @foreach (['draft', 'pending', 'approved', 'void'] as $st)
                            <option value="{{ $st }}" @selected($filters['status'] === $st)>
                                {{ strtoupper($st) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label form-label-sm">Cari</label>
                    <input type="text" name="q" value="{{ $filters['q'] }}" class="form-control form-control-sm"
                        placeholder="Kode / alasan / catatan">
                </div>

                <div class="col-md-2">
                    <button class="btn btn-sm btn-outline-secondary w-100">
                        Filter
                    </button>
                </div>
            </div>
        </form>

        {{-- TABLE --}}
        <div class="card">
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Kode</th>
                            <th>Tanggal</th>
                            <th>Gudang</th>
                            <th>Item</th>
                            <th>Status</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($adjustments as $adj)
                            <tr>
                                <td class="text-mono fw-semibold">{{ $adj->code }}</td>
                                <td>{{ $adj->date?->format('d M Y') }}</td>
                                <td>{{ $adj->warehouse?->code }}</td>
                                <td>{{ $adj->lines_count }} item</td>
                                <td>
                                    @if ($adj->status === 'approved')
                                        <span class="badge bg-success">APPROVED</span>
                                    @elseif($adj->status === 'pending')
                                        <span class="badge bg-warning text-dark">PENDING</span>
                                    @else
                                        <span class="badge bg-secondary">{{ strtoupper($adj->status) }}</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('inventory.wip_adjustments.show', $adj) }}"
                                        class="btn btn-sm btn-outline-primary">
                                        Lihat
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">
                                    Belum ada WIP Adjustment.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-3">
            {{ $adjustments->links() }}
        </div>
    </div>
@endsection
