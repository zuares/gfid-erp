@extends('layouts.app')

@section('title', 'Produksi • Rekap Harian Produksi')

@push('head')
    <style>
        .page-wrap {
            max-width: 1100px;
            margin-inline: auto;
        }

        .table-wrap {
            overflow-x: auto;
        }

        .mono {
            font-variant-numeric: tabular-nums;
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, "Liberation Mono";
        }

        .badge-link {
            font-size: .75rem;
            padding: .2rem .45rem;
        }
    </style>

    {{-- Flatpickr CSS --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
@endpush

@section('content')
    <div class="page-wrap">

        <div class="card p-3 mb-3">
            <h1 class="h5 mb-2">Rekap Harian Produksi</h1>
            <div class="text-muted small">
                Ringkasan output Cutting & Sewing per hari + total reject.
            </div>

            {{-- PRESET BUTTONS --}}
            <div class="mt-3 d-flex flex-wrap gap-2">
                @php
                    $presetNow = $preset ?? null;
                @endphp

                <a href="{{ route('production.reports.daily_production', ['preset' => 'today']) }}"
                    class="btn btn-sm {{ $presetNow === 'today' ? 'btn-primary' : 'btn-outline-primary' }}">
                    Hari Ini
                </a>

                <a href="{{ route('production.reports.daily_production', ['preset' => 'yesterday']) }}"
                    class="btn btn-sm {{ $presetNow === 'yesterday' ? 'btn-primary' : 'btn-outline-primary' }}">
                    Kemarin
                </a>

                <a href="{{ route('production.reports.daily_production', ['preset' => '7days']) }}"
                    class="btn btn-sm {{ $presetNow === '7days' ? 'btn-primary' : 'btn-outline-primary' }}">
                    7 Hari Terakhir
                </a>

                <a href="{{ route('production.reports.daily_production', ['preset' => 'thismonth']) }}"
                    class="btn btn-sm {{ $presetNow === 'thismonth' ? 'btn-primary' : 'btn-outline-primary' }}">
                    Bulan Ini
                </a>
            </div>

            {{-- CUSTOM DATE-RANGE (Flatpickr) --}}
            <form method="get" class="row g-2 mt-3 align-items-end" id="customRangeForm">
                <div class="col-md-4 col-12">
                    <label class="form-label small mb-1">Periode (Custom)</label>
                    <input type="text" id="dateRangeInput" class="form-control" placeholder="Pilih rentang tanggal">
                    {{-- Hidden real values yang dikirim ke server --}}
                    <input type="hidden" name="date_from" id="dateFrom" value="{{ $dateFrom }}">
                    <input type="hidden" name="date_to" id="dateTo" value="{{ $dateTo }}">
                </div>

                <div class="col-auto">
                    <button type="submit" class="btn btn-dark">
                        Tampilkan
                    </button>
                </div>
            </form>
        </div>

        <div class="card p-3">
            <div class="table-wrap">
                <table class="table table-sm align-middle mono">
                    <thead>
                        <tr>
                            <th style="width:120px;">Tanggal</th>
                            <th style="width:170px;">Cutting OK</th>
                            <th style="width:170px;">Sewing OK</th>
                            <th style="width:170px;">Reject Total</th>
                            <th>Detail</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($records as $row)
                            <tr>
                                <td>{{ $row['date'] }}</td>

                                {{-- Cutting OK + link ke CUT --}}
                                <td>
                                    {{ number_format($row['cutting_ok'], 2, ',', '.') }}
                                    @foreach ($row['cutting_jobs'] as $cid)
                                        <a href="{{ route('production.cutting_jobs.show', $cid) }}"
                                            class="badge bg-primary badge-link text-decoration-none">
                                            CUT-{{ $cid }}
                                        </a>
                                    @endforeach
                                </td>

                                {{-- Sewing OK + link ke SWR --}}
                                <td>
                                    {{ number_format($row['sewing_ok'], 2, ',', '.') }}
                                    @foreach ($row['sewing_returns'] as $rid)
                                        <a href="{{ route('production.sewing_returns.show', $rid) }}"
                                            class="badge bg-success badge-link text-decoration-none">
                                            SWR-{{ $rid }}
                                        </a>
                                    @endforeach
                                </td>

                                <td>{{ number_format($row['reject_total'], 2, ',', '.') }}</td>

                                <td>
                                    <a href="{{ route('production.reports.reject_detail', ['date' => $row['date']]) }}"
                                        class="btn btn-sm btn-outline-dark">
                                        Lihat Reject
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted small">
                                    Tidak ada data pada periode ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>
@endsection

@push('scripts')
    {{-- Flatpickr JS --}}
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const dateFrom = document.getElementById('dateFrom');
            const dateTo = document.getElementById('dateTo');
            const rangeInput = document.getElementById('dateRangeInput');

            // Inisialisasi default text di input, kalau sudah ada dateFrom/dateTo
            if (dateFrom.value && dateTo.value) {
                if (dateFrom.value === dateTo.value) {
                    rangeInput.value = dateFrom.value;
                } else {
                    rangeInput.value = dateFrom.value + ' s/d ' + dateTo.value;
                }
            }

            flatpickr(rangeInput, {
                mode: 'range',
                dateFormat: 'Y-m-d',
                defaultDate: (dateFrom.value && dateTo.value) ?
                    [dateFrom.value, dateTo.value] :
                    null,
                onClose: function(selectedDates, dateStr, instance) {
                    if (selectedDates.length === 2) {
                        const [start, end] = selectedDates;
                        const toYmd = (d) => d.toISOString().slice(0, 10);

                        dateFrom.value = toYmd(start);
                        dateTo.value = toYmd(end);

                        rangeInput.value = dateFrom.value + ' s/d ' + dateTo.value;
                    } else if (selectedDates.length === 1) {
                        const toYmd = (d) => d.toISOString().slice(0, 10);
                        const single = toYmd(selectedDates[0]);

                        dateFrom.value = single;
                        dateTo.value = single;

                        rangeInput.value = single;
                    }
                },
            });
        });
    </script>
@endpush
