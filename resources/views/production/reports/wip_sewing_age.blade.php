@extends('layouts.app')

@section('title', 'Laporan • Ageing WIP Sewing')

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

        .badge-soft {
            border-radius: 999px;
            padding: .15rem .5rem;
            font-size: .7rem;
        }

        .table-wrap {
            overflow-x: auto;
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
                    <h1 class="h5 mb-1">Ageing WIP Sewing</h1>
                    <div class="help">
                        Daftar bundle yang masih di operator (status in_progress), dengan sisa qty &amp; umur (hari).
                    </div>
                </div>
            </div>

            {{-- FILTERS --}}
            <form method="get" class="row g-2 mt-3">
                <div class="col-md-4 col-8">
                    <div class="help mb-1">Operator Jahit</div>
                    <select name="operator_id" class="form-select form-select-sm">
                        <option value="">-- Semua Operator --</option>
                        @foreach ($operators as $op)
                            <option value="{{ $op->id }}"
                                {{ isset($filters['operator_id']) && (int) $filters['operator_id'] === $op->id ? 'selected' : '' }}>
                                {{ $op->code }} — {{ $op->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-4 col-4 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-sm btn-primary">Filter</button>
                    <a href="{{ route('production.reports.wip_sewing_age') }}"
                        class="btn btn-sm btn-outline-secondary">Reset</a>
                </div>
            </form>
        </div>

        {{-- TABLE --}}
        <div class="card p-3">
            <h2 class="h6 mb-2">Bundle Masih Proses</h2>

            <div class="table-wrap">
                <table class="table table-sm align-middle mono">
                    <thead>
                        <tr>
                            <th style="width:40px;">#</th>
                            <th style="width:130px;">Pickup Code</th>
                            <th style="width:110px;">Tgl Pickup</th>
                            <th style="width:170px;">Operator</th>
                            <th style="width:170px;">Item</th>
                            <th style="width:220px;">Lot / Bundle</th>
                            <th style="width:110px;">Qty Pickup</th>
                            <th style="width:110px;">Sudah Return</th>
                            <th style="width:110px;">Sisa</th>
                            <th style="width:90px;">Umur (hari)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($lines as $line)
                            @php
                                $pickup = $line->sewingPickup;
                                $bundle = $line->bundle;
                                $lot = $bundle?->cuttingJob?->lot;
                                $fabricCode = $lot?->item?->code;
                            @endphp
                            <tr>
                                <td>{{ $loop->iteration }}</td>

                                {{-- 🔗 DRILLDOWN: SEWING PICKUP (SWP-...) --}}
                                <td>
                                    @if ($pickup)
                                        <a href="{{ route('production.sewing_pickups.show', $pickup) }}"
                                            class="text-decoration-none fw-semibold">
                                            {{ $pickup->code }}
                                        </a>
                                    @else
                                        -
                                    @endif
                                </td>

                                <td>
                                    {{ $pickup?->date ? \Illuminate\Support\Carbon::parse($pickup->date)->format('Y-m-d') : '-' }}
                                </td>

                                <td>
                                    @if ($pickup?->operator)
                                        {{ $pickup->operator->code }} — {{ $pickup->operator->name }}
                                    @else
                                        -
                                    @endif
                                </td>

                                <td>{{ $bundle?->finishedItem?->code ?? '-' }}</td>

                                {{-- 🔗 INFO LOT + OPTIONAL DRILLDOWN CUTTING JOB --}}
                                <td>
                                    {{-- Nama item kain (misal FLC280BLK) --}}
                                    {{ $fabricCode ?? '-' }}

                                    {{-- Badge kecil kode lot --}}
                                    @if ($lot?->code)
                                        <span class="badge-soft bg-light border text-muted ms-1">
                                            {{ $lot->code }}
                                        </span>
                                    @endif

                                    {{-- Bundle code + link ke Cutting Job kalau ada --}}
                                    <div class="small">
                                        @if ($bundle?->cuttingJob)
                                            {{-- 🔗 DRILLDOWN: CUTTING JOB (CUT-...) --}}
                                            <a href="{{ route('production.cutting_jobs.show', $bundle->cuttingJob) }}"
                                                class="text-decoration-none">
                                                {{ $bundle->cuttingJob->code }}
                                            </a>
                                            &middot;
                                        @endif
                                        {{ $bundle?->bundle_code ?? '-' }}
                                    </div>
                                </td>

                                <td>{{ number_format($line->qty_bundle ?? 0, 2, ',', '.') }}</td>
                                <td>{{ number_format($line->used_qty ?? 0, 2, ',', '.') }}</td>
                                <td class="{{ ($line->remaining_qty ?? 0) > 0 ? 'text-warning' : '' }}">
                                    {{ number_format($line->remaining_qty ?? 0, 2, ',', '.') }}
                                </td>
                                <td class="{{ ($line->age_days ?? 0) >= 7 ? 'text-danger' : '' }}">
                                    {{ $line->age_days ?? '-' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center text-muted small">
                                    Tidak ada WIP Sewing dengan status in_progress.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        </div>
    </div>
@endsection
