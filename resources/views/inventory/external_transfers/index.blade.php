@extends('layouts.app')

@section('title', 'Produksi • External Transfers')

@push('head')
    <style>
        .page-wrap {
            max-width: 1080px;
            margin-inline: auto;
        }

        .card {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 14px;
        }

        .mono {
            font-family: ui-monospace, Menlo, SFMono-Regular;
            font-variant-numeric: tabular-nums;
        }

        .badge-status {
            padding: .15rem .6rem;
            border-radius: 999px;
            font-size: .7rem;
            font-weight: 600;
            display: inline-block;
        }

        .badge-status-sent {
            background: rgba(59, 130, 246, .15);
            color: #2563eb;
            border: 1px solid rgba(59, 130, 246, .45);
        }

        .badge-status-received {
            background: rgba(16, 185, 129, .15);
            color: #059669;
            border: 1px solid rgba(16, 185, 129, .45);
        }

        .badge-status-cancelled {
            background: rgba(239, 68, 68, .15);
            color: #dc2626;
            border: 1px solid rgba(239, 68, 68, .45);
        }

        .chip-warehouse {
            display: inline-flex;
            align-items: center;
            border-radius: 999px;
            padding: .05rem .6rem;
            font-size: .7rem;
            border: 1px solid var(--line);
            gap: .25rem;
            margin-top: .1rem;
        }

        .chip-wh-internal {
            background: rgba(16, 185, 129, .06);
            color: #047857;
        }

        .chip-wh-external {
            background: rgba(129, 140, 248, .08);
            color: #4f46e5;
        }

        .table-sm td,
        .table-sm th {
            padding-block: .45rem;
        }

        @media (max-width: 767.98px) {
            .table-wrap {
                overflow-x: auto;
            }
        }
    </style>
@endpush

@section('content')
    <div class="page-wrap">

        {{-- HEADER --}}
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h4 class="mb-1">External Transfers</h4>
                <div class="text-muted small">Daftar pengiriman LOT ke vendor eksternal.</div>
            </div>
            <div>
                <a href="{{ route('inventory.external_transfers.create') }}" class="btn btn-primary btn-sm">
                    <i class="bi bi-plus-lg me-1"></i> Transfer Baru
                </a>
            </div>
        </div>

        {{-- FILTER --}}
        <div class="card mb-3">
            <div class="card-body">
                <form method="get" action="{{ route('inventory.external_transfers.index') }}"
                    class="row g-2 align-items-end">

                    {{-- DARI TANGGAL --}}
                    <div class="col-6 col-md-3">
                        <label class="form-label small">Dari Tgl</label>
                        <input type="date" name="from_date" value="{{ request('from_date') }}"
                            class="form-control form-control-sm">
                    </div>

                    {{-- SAMPAI TANGGAL --}}
                    <div class="col-6 col-md-3">
                        <label class="form-label small">Sampai Tgl</label>
                        <input type="date" name="to_date" value="{{ request('to_date') }}"
                            class="form-control form-control-sm">
                    </div>

                    {{-- STATUS --}}
                    @php $st = request('status'); @endphp
                    <div class="col-6 col-md-3">
                        <label class="form-label small">Status</label>
                        <select name="status" class="form-select form-select-sm">
                            <option value="">Semua</option>
                            <option value="SENT" {{ $st === 'SENT' ? 'selected' : '' }}>SENT</option>
                            <option value="RECEIVED" {{ $st === 'RECEIVED' ? 'selected' : '' }}>RECEIVED</option>
                            <option value="CANCELLED" {{ $st === 'CANCELLED' ? 'selected' : '' }}>CANCELLED</option>
                        </select>
                    </div>

                    {{-- OPERATOR FILTER --}}
                    <div class="col-6 col-md-3">
                        <label class="form-label small">Operator / Vendor</label>
                        @php $op = request('operator_code'); @endphp
                        <select name="operator_code" class="form-select form-select-sm">
                            <option value="">Semua</option>
                            @foreach ($employees as $emp)
                                <option value="{{ $emp->code }}" {{ $op === $emp->code ? 'selected' : '' }}>
                                    {{ $emp->code }} — {{ $emp->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- FILTER --}}
                    <div class="col-12 col-md-2 mt-1 mt-md-0">
                        <button class="btn btn-outline-secondary btn-sm w-100">
                            <i class="bi bi-funnel me-1"></i> Filter
                        </button>
                    </div>

                </form>
            </div>
        </div>

        {{-- TABEL --}}
        <div class="card">
            <div class="card-body table-wrap">
                <table class="table table-sm align-middle mb-0">
                    <thead>
                        <tr>
                            <th style="width:120px;">Tanggal</th>
                            <th style="width:200px;">Dibuat oleh</th>
                            <th style="width:160px;">Dari Gudang</th>
                            <th style="width:160px;">Ke Gudang</th>
                            <th style="width:90px;" class="text-center">Status</th>
                            <th style="width:60px;"></th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($transfers as $t)
                            @php
                                $status = strtoupper($t->status ?? '');
                                $badgeClass = match ($status) {
                                    'RECEIVED' => 'badge-status badge-status-received',
                                    'CANCELLED' => 'badge-status badge-status-cancelled',
                                    default => 'badge-status badge-status-sent',
                                };

                                $fromType = $t->fromWarehouse?->type;
                                $fromExternal = $fromType === 'external';

                                $toType = $t->toWarehouse?->type;
                                $toExternal = $toType === 'external';
                            @endphp

                            <tr>
                                {{-- DATE --}}
                                <td class="mono small">
                                    {{ $t->date?->format('Y-m-d') }}
                                </td>

                                {{-- CREATED BY --}}
                                <td>
                                    <div class="small">{{ $t->creator?->name ?? '-' }}</div>
                                    <div class="text-muted mono small">
                                        {{ $t->created_at?->format('Y-m-d H:i') }}
                                    </div>
                                </td>

                                {{-- FROM WAREHOUSE --}}
                                <td>
                                    <div class="mono small">
                                        {{ $t->fromWarehouse?->code ?? '-' }}
                                    </div>
                                    @if ($fromType)
                                        <span
                                            class="chip-warehouse {{ $fromExternal ? 'chip-wh-external' : 'chip-wh-internal' }}">
                                            {{ $fromExternal ? 'External' : 'Internal' }}
                                        </span>
                                    @endif
                                </td>

                                {{-- TO WAREHOUSE --}}
                                <td>
                                    <div class="mono small">
                                        {{ $t->toWarehouse?->code ?? '-' }}
                                    </div>
                                    @if ($toType)
                                        <span
                                            class="chip-warehouse {{ $toExternal ? 'chip-wh-external' : 'chip-wh-internal' }}">
                                            {{ $toExternal ? 'External' : 'Internal' }}
                                        </span>
                                    @endif
                                </td>

                                {{-- STATUS --}}
                                <td class="text-center">
                                    <span class="{{ $badgeClass }}">{{ $status }}</span>
                                </td>

                                {{-- ACTION --}}
                                <td class="text-end">
                                    <a href="{{ route('inventory.external_transfers.show', $t->id) }}"
                                        class="btn btn-outline-secondary btn-sm">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </td>
                            </tr>

                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted small">
                                    Belum ada external transfer.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>

                </table>

                <div class="mt-3">
                    {{ $transfers->links() }}
                </div>
            </div>
        </div>

    </div>
@endsection
