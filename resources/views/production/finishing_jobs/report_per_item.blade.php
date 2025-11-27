{{-- resources/views/production/finishing_jobs/report_per_item.blade.php --}}
@extends('layouts.app')

@section('title', 'Report • Finishing per Item')

@push('head')
    <style>
        .page-wrap {
            max-width: 1100px;
            margin-inline: auto;
            padding-block: .75rem 1.5rem;
        }

        .card {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 16px;
        }

        .mono {
            font-variant-numeric: tabular-nums;
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas;
        }

        .help {
            color: var(--muted);
            font-size: .84rem;
        }

        .page-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .page-title-wrap {
            display: flex;
            align-items: center;
            gap: .9rem;
        }

        .page-icon {
            width: 38px;
            height: 38px;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: color-mix(in srgb, var(--primary, #0d6efd) 10%, var(--card) 90%);
            border: 1px solid color-mix(in srgb, var(--primary, #0d6efd) 30%, var(--line) 70%);
            font-size: 1.1rem;
        }

        .page-title {
            margin: 0;
            font-size: 1.1rem;
            font-weight: 600;
        }

        .page-subtitle {
            font-size: .85rem;
            color: var(--muted);
        }

        .filters-row {
            display: flex;
            flex-wrap: wrap;
            gap: .75rem;
            align-items: flex-end;
        }

        .filters-row .form-label {
            font-size: .8rem;
            color: var(--muted);
            margin-bottom: .15rem;
        }

        .table-wrap {
            overflow-x: auto;
        }

        .table-report {
            margin-bottom: 0;
        }

        .table-report thead th {
            font-size: .78rem;
            text-transform: uppercase;
            letter-spacing: .04em;
        }

        .badge-item {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            padding: .1rem .6rem;
            border-radius: 999px;
            font-size: .78rem;
            background: color-mix(in srgb, var(--card) 70%, var(--line) 30%);
        }

        .badge-item .mono {
            font-size: .78rem;
        }

        @media (max-width: 767.98px) {
            .page-wrap {
                padding-inline: .5rem;
            }

            .table-wrap {
                font-size: .86rem;
            }

            .page-title {
                font-size: 1rem;
            }

            .page-subtitle {
                font-size: .8rem;
            }
        }
    </style>
@endpush

@section('content')
    <div class="page-wrap">

        {{-- HEADER --}}
        <div class="card p-3 mb-3">
            <div class="page-header">
                <div class="page-title-wrap">
                    <div class="page-icon">
                        <i class="bi bi-clipboard-data"></i>
                    </div>
                    <div>
                        <h1 class="page-title">Report Finishing per Item</h1>
                        <div class="page-subtitle">
                            Rekap hasil finishing (FG & Reject) yang sudah <strong>posted</strong>, dikelompokkan per item.
                            Klik nama item untuk melihat detail Finishing Job yang membentuk angka tersebut.
                        </div>
                    </div>
                </div>

                <div class="d-flex flex-column align-items-end gap-2">
                    <a href="{{ route('production.finishing_jobs.index') }}" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-list-ul me-1"></i> Semua Finishing Job
                    </a>
                </div>
            </div>
        </div>

        {{-- FILTERS + SUMMARY --}}
        <div class="card p-3 mb-3">
            <form method="get" class="mb-3">
                <div class="filters-row">
                    <div>
                        <label class="form-label">Dari tanggal</label>
                        <input type="date" name="date_from" class="form-control form-control-sm"
                            value="{{ $dateFrom }}">
                    </div>
                    <div>
                        <label class="form-label">Sampai tanggal</label>
                        <input type="date" name="date_to" class="form-control form-control-sm"
                            value="{{ $dateTo }}">
                    </div>
                    <div class="flex-grow-1"></div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-sm btn-primary">
                            <i class="bi bi-funnel me-1"></i> Terapkan
                        </button>
                        <a href="{{ route('production.finishing_jobs.report_per_item') }}"
                            class="btn btn-sm btn-outline-secondary">
                            Reset
                        </a>
                    </div>
                </div>
            </form>

            @php
                /** @var \Illuminate\Support\Collection $rows */
                $totalRows = $rows->count();
            @endphp

            <div class="row g-3">
                <div class="col-md-4">
                    <div class="small text-muted mb-1">Total Item</div>
                    <div class="h5 mb-0 mono">{{ $totalRows }}</div>
                    <div class="help">
                        Jumlah item yang punya aktivitas finishing pada periode ini.
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="small text-muted mb-1">Total OK (FG)</div>
                    <div class="h5 mb-0 mono text-success">{{ number_format($grandTotalOk) }}</div>
                    <div class="help">
                        Total pcs masuk gudang FG (semua item).
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="small text-muted mb-1">Total Reject</div>
                    <div class="h5 mb-0 mono text-danger">{{ number_format($grandTotalReject) }}</div>
                    <div class="help">
                        Total pcs masuk gudang REJECT (semua item).
                    </div>
                </div>
            </div>
        </div>

        {{-- TABEL REKAP PER ITEM --}}
        <div class="card p-0 mb-4">
            <div class="px-3 pt-3 pb-2 d-flex justify-content-between align-items-center">
                <div>
                    <div class="fw-semibold">Rekap per Item</div>
                    <div class="help">
                        Klik nama item untuk drilldown ke list Finishing Job yang berisi item tersebut.
                    </div>
                </div>
                <div class="help">
                    Total baris: {{ $rows->count() }}
                </div>
            </div>

            <div class="table-wrap">
                <table class="table table-sm align-middle mb-0 table-report">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 1%;">#</th>
                            <th>Item</th>
                            <th class="text-end">Qty In</th>
                            <th class="text-end text-success">OK (FG)</th>
                            <th class="text-end text-danger">Reject</th>
                            <th class="text-end">Reject %</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rows as $i => $row)
                            @php
                                // Asumsi controller: $rows adalah koleksi FinishingJobLine agregat
                                // dengan relasi item sudah di-with('item').
                                $item = $row->item ?? null;
                                $rejectPct = $row->total_in > 0 ? ($row->total_reject / $row->total_in) * 100 : 0;
                            @endphp
                            <tr>
                                <td class="text-muted small">{{ $i + 1 }}</td>

                                {{-- ITEM (klik → drilldown detail) --}}
                                <td>
                                    @if ($item)
                                        <div class="small fw-semibold">
                                            <a
                                                href="{{ route('production.finishing_jobs.report_per_item_detail', [
                                                    'item' => $item->id,
                                                    'date_from' => $dateFrom,
                                                    'date_to' => $dateTo,
                                                ]) }}">
                                                {{ $item->code ?? '' }} — {{ $item->name ?? '' }}
                                            </a>
                                        </div>
                                        <div class="small text-muted">
                                            {{ $item->color ?? '' }}
                                        </div>
                                    @else
                                        <span class="text-muted small">Item tidak ditemukan</span>
                                    @endif
                                </td>

                                {{-- QTY IN --}}
                                <td class="text-end mono">
                                    {{ number_format($row->total_in) }}
                                </td>

                                {{-- OK --}}
                                <td class="text-end mono text-success">
                                    {{ number_format($row->total_ok) }}
                                </td>

                                {{-- REJECT --}}
                                <td class="text-end mono text-danger">
                                    {{ number_format($row->total_reject) }}
                                </td>

                                {{-- REJECT % --}}
                                <td class="text-end mono">
                                    {{ $rejectPct > 0 ? number_format($rejectPct, 2) . ' %' : '-' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">
                                    Belum ada data finishing untuk periode dan filter yang dipilih.
                                </td>
                            </tr>
                        @endforelse
                        @if ($rows->isNotEmpty())
                    <tfoot>
                        <tr class="table-light">
                            <th colspan="2" class="text-end">TOTAL</th>
                            <th class="text-end mono">{{ number_format($grandTotalIn) }}</th>
                            <th class="text-end mono text-success">{{ number_format($grandTotalOk) }}</th>
                            <th class="text-end mono text-danger">{{ number_format($grandTotalReject) }}</th>
                            <th></th>
                        </tr>
                    </tfoot>
                    @endif
                    </tbody>
                </table>
            </div>
        </div>

        {{-- FOOT NOTE --}}
        <div class="help mb-4">
            Data diambil dari Finishing Job dengan status <strong>posted</strong>.
        </div>
    </div>
@endsection
