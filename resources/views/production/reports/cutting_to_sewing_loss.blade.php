@extends('layouts.app')

@section('title', 'Laporan • Cutting → Sewing Loss')

@push('head')
    <style>
        .page-wrap {
            max-width: 1100px;
            margin-inline: auto;
        }

        .card {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 14px;
        }

        .mono {
            font-variant-numeric: tabular-nums;
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas;
        }

        .help {
            color: var(--muted);
            font-size: .85rem;
        }

        .table-wrap {
            overflow-x: auto;
        }

        .badge-soft {
            border-radius: 999px;
            padding: .15rem .5rem;
            font-size: .7rem;
            border: 1px solid var(--line);
        }
    </style>
@endpush

@section('content')
    @php $filters = $filters ?? []; @endphp

    <div class="page-wrap">

        {{-- HEADER --}}
        <div class="card p-3 mb-3">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h1 class="h5 mb-1">Cutting → Sewing Loss</h1>
                    <div class="help">
                        Perbandingan hasil QC Cutting vs yang masuk Sewing vs hasil OK Sewing, per Cutting Job.
                    </div>
                </div>
            </div>

            {{-- FILTERS --}}
            <form method="get" class="row g-2 mt-3">
                <div class="col-md-3 col-6">
                    <div class="help mb-1">Tanggal Cutting dari</div>
                    <input type="date" name="date_from" class="form-control form-control-sm"
                        value="{{ $filters['date_from'] ?? '' }}">
                </div>

                <div class="col-md-3 col-6">
                    <div class="help mb-1">Tanggal Cutting sampai</div>
                    <input type="date" name="date_to" class="form-control form-control-sm"
                        value="{{ $filters['date_to'] ?? '' }}">
                </div>

                <div class="col-md-3 col-6">
                    <div class="help mb-1">Gudang Cutting</div>
                    <select name="warehouse_id" class="form-select form-select-sm">
                        <option value="">-- Semua Gudang --</option>
                        @foreach ($warehouses as $wh)
                            <option value="{{ $wh->id }}"
                                {{ isset($filters['warehouse_id']) && (int) $filters['warehouse_id'] === $wh->id ? 'selected' : '' }}>
                                {{ $wh->code }} — {{ $wh->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3 col-6">
                    <div class="help mb-1">Kain LOT (Item)</div>
                    <select name="fabric_item_id" class="form-select form-select-sm">
                        <option value="">-- Semua Kain --</option>
                        @foreach ($fabricItems as $it)
                            <option value="{{ $it->id }}"
                                {{ isset($filters['fabric_item_id']) && (int) $filters['fabric_item_id'] === $it->id ? 'selected' : '' }}>
                                {{ $it->code }}{{ $it->name ? ' — ' . $it->name : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-12 d-flex justify-content-end align-items-end gap-2 mt-2">
                    <button type="submit" class="btn btn-sm btn-primary">Filter</button>
                    <a href="{{ route('production.reports.cutting_to_sewing_loss') }}"
                        class="btn btn-sm btn-outline-secondary">Reset</a>
                </div>
            </form>
        </div>

        {{-- TABLE --}}
        <div class="card p-3">
            <h2 class="h6 mb-2">Rekap per Cutting Job</h2>

            <div class="table-wrap">
                <table class="table table-sm align-middle mono">
                    <thead>
                        <tr>
                            <th style="width:40px;">#</th>
                            <th style="width:150px;">Cutting Job</th>
                            <th style="width:110px;">Tgl Cutting</th>
                            <th style="width:190px;">LOT / Kain</th>
                            <th style="width:100px;">Gudang</th>
                            <th style="width:120px;">Cutting OK</th>
                            <th style="width:120px;">Pickup ke Sewing</th>
                            <th style="width:120px;">Sewing OK</th>
                            <th style="width:120px;">Sewing Reject</th>
                            <th style="width:130px;">Loss (Cut→Pickup)</th>
                            <th style="width:130px;">Loss (Sewing)</th>
                            <th style="width:130px;">Total Loss vs Cut</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($jobs as $job)
                            @php
                                $cutOk = (float) ($job->qty_cut_ok ?? 0);
                                $picked = (float) ($job->qty_picked ?? 0);
                                $sewOk = (float) ($job->qty_sewing_ok ?? 0);
                                $sewReject = (float) ($job->qty_sewing_reject ?? 0);

                                $lossCutToPickup = $cutOk - $picked; // teorinya: sisa di WIP-CUT
                                $lossSewProcess = $picked - ($sewOk + $sewReject); // hilang di proses sewing
                                $totalLossVsCut = $cutOk - $sewOk;

                                // Biar nggak ada minus aneh kalau data belum lengkap
                                if ($lossCutToPickup < 0) {
                                    $lossCutToPickup = 0;
                                }
                                if ($lossSewProcess < 0) {
                                    $lossSewProcess = 0;
                                }
                                if ($totalLossVsCut < 0) {
                                    $totalLossVsCut = 0;
                                }

                                $lot = $job->lot;
                                $fabricItem = $lot?->item;
                            @endphp
                            <tr>
                                {{-- # --}}
                                <td>
                                    {{ $loop->iteration + ($jobs->currentPage() - 1) * $jobs->perPage() }}
                                </td>

                                {{-- Cutting Job (drilldown link) --}}
                                <td>
                                    <a href="{{ route('production.cutting_jobs.show', $job) }}"
                                        class="text-decoration-none fw-semibold">
                                        {{ $job->code }}
                                    </a>
                                </td>

                                {{-- Tanggal Cutting --}}
                                <td>
                                    {{ $job->date?->format('Y-m-d') ?? $job->date }}
                                </td>

                                {{-- LOT / Kain --}}
                                <td>
                                    @if ($fabricItem)
                                        {{ $fabricItem->code }}
                                        <span class="badge-soft bg-light text-muted ms-1">
                                            {{ $lot?->code ?? '-' }}
                                        </span>
                                    @else
                                        {{ $lot?->code ?? '-' }}
                                    @endif
                                </td>

                                {{-- Gudang --}}
                                <td>
                                    {{ $job->warehouse?->code ?? '-' }}
                                </td>

                                {{-- Cutting OK --}}
                                <td>
                                    {{ number_format($cutOk, 2, ',', '.') }}
                                </td>

                                {{-- Pickup ke Sewing --}}
                                <td class="{{ $picked == 0 && $cutOk > 0 ? 'text-warning' : '' }}">
                                    {{ number_format($picked, 2, ',', '.') }}
                                </td>

                                {{-- Sewing OK --}}
                                <td>
                                    {{ number_format($sewOk, 2, ',', '.') }}
                                </td>

                                {{-- Sewing Reject --}}
                                <td class="{{ $sewReject > 0 ? 'text-danger' : '' }}">
                                    {{ number_format($sewReject, 2, ',', '.') }}
                                </td>

                                {{-- Loss Cut → Pickup --}}
                                <td class="{{ $lossCutToPickup > 0 ? 'text-warning' : '' }}">
                                    {{ number_format($lossCutToPickup, 2, ',', '.') }}
                                </td>

                                {{-- Loss di proses Sewing --}}
                                <td class="{{ $lossSewProcess > 0 ? 'text-danger' : '' }}">
                                    {{ number_format($lossSewProcess, 2, ',', '.') }}
                                </td>

                                {{-- Total Loss vs Cutting OK --}}
                                <td class="{{ $totalLossVsCut > 0 ? 'text-danger fw-semibold' : '' }}">
                                    {{ number_format($totalLossVsCut, 2, ',', '.') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="12" class="text-center text-muted small">
                                    Tidak ada data Cutting → Sewing untuk filter yang dipilih.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($jobs instanceof \Illuminate\Pagination\AbstractPaginator)
                <div class="mt-2">
                    {{ $jobs->links() }}
                </div>
            @endif
        </div>

    </div>
@endsection
