{{-- resources/views/production/reports/sewing/dashboard.blade.php --}}
@extends('layouts.app')

@section('title', 'Dashboard Harian Sewing')

@push('head')
    <style>
        .page-wrap {
            max-width: 1120px;
            margin-inline: auto;
        }

        .card {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 16px;
        }

        .stat-card {
            padding: 0.75rem 0.9rem;
            border-radius: 14px;
            display: flex;
            flex-direction: column;
            gap: 0.15rem;
            height: 100%;
        }

        .stat-label {
            font-size: .78rem;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: var(--muted);
        }

        .stat-value {
            font-size: 1.4rem;
            font-weight: 600;
        }

        .stat-sub {
            font-size: .8rem;
            color: var(--muted);
        }

        .mono {
            font-variant-numeric: tabular-nums;
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, "Liberation Mono", monospace;
        }

        .muted {
            color: var(--muted);
            font-size: .85rem;
        }

        .chip {
            padding: .18rem .7rem;
            border-radius: 999px;
            font-size: .75rem;
            background: color-mix(in srgb, var(--card) 75%, var(--line) 25%);
            color: var(--muted);
            display: inline-flex;
            align-items: center;
            gap: .3rem;
        }

        .chip-dot {
            width: 6px;
            height: 6px;
            border-radius: 999px;
            background: var(--accent);
        }

        .table-wrap {
            overflow-x: auto;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: .5rem;
            margin-bottom: .4rem;
        }

        .section-title {
            font-size: .9rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: .35rem;
        }

        .section-title span.icon {
            font-size: 1.1rem;
        }

        .badge-soft {
            border-radius: 999px;
            padding: .15rem .55rem;
            font-size: .72rem;
            background: color-mix(in srgb, var(--card) 72%, var(--line) 28%);
            color: var(--muted);
        }

        .kpi-grid {
            display: grid;
            gap: .55rem;
        }

        @media (min-width: 768px) {
            .kpi-grid {
                grid-template-columns: repeat(5, minmax(0, 1fr));
            }
        }
    </style>

    {{-- Chart.js via CDN --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
@endpush

@section('content')
    <div class="page-wrap py-3 py-md-4">

        {{-- Header --}}
        <div class="d-flex justify-content-between align-items-start mb-3 gap-2">
            <div>
                <div class="d-flex align-items-center gap-2 mb-1">
                    <h1 class="h5 mb-0">Dashboard Harian Sewing</h1>
                    <span class="badge-soft mono">{{ id_date($selectedDate) }}</span>
                </div>
                <div class="muted">
                    Ringkasan performa jahit & status WIP untuk tanggal yang dipilih.
                </div>
            </div>
            <div class="text-end">
                <div class="chip mb-1">
                    <span class="chip-dot"></span>
                    <span class="mono small">Auto-refresh 10 menit</span>
                </div>
                <div class="muted small">Update terakhir: {{ now()->format('H:i') }}</div>
            </div>
        </div>

        {{-- Filter bar --}}
        <div class="card mb-3 p-3">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-12 col-md-3">
                    <label class="form-label small mb-1">Tanggal</label>
                    <input type="date" name="date" class="form-control form-control-sm"
                        value="{{ request('date', $selectedDate->toDateString()) }}">
                </div>

                <div class="col-12 col-md-3">
                    <label class="form-label small mb-1">Operator Sewing</label>
                    <select name="operator_id" class="form-select form-select-sm">
                        <option value="">Semua operator</option>
                        @foreach ($operators as $op)
                            <option value="{{ $op->id }}"
                                {{ (string) $op->id === (string) $selectedOperatorId ? 'selected' : '' }}>
                                {{ $op->code }} — {{ $op->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-12 col-md-3">
                    <label class="form-label small mb-1">Item</label>
                    <select name="item_id" class="form-select form-select-sm">
                        <option value="">Semua item</option>
                        @foreach ($items as $it)
                            <option value="{{ $it->id }}"
                                {{ (string) $it->id === (string) $selectedItemId ? 'selected' : '' }}>
                                {{ $it->code }} — {{ $it->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-12 col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-sm btn-primary flex-grow-1">Terapkan Filter</button>
                    <a href="{{ route('production.reports.dashboard') }}"
                        class="btn btn-sm btn-outline-secondary">Reset</a>
                </div>
            </form>
        </div>

        {{-- KPI Utama --}}
        <div class="kpi-grid mb-3">
            <div class="card stat-card">
                <div class="stat-label">Pickup</div>
                <div class="stat-value mono text-primary">{{ number_format($totalPickupToday) }}</div>
                <div class="stat-sub">Bundle/pcs diambil operator sewing</div>
            </div>

            <div class="card stat-card">
                <div class="stat-label">Return OK</div>
                <div class="stat-value mono text-success">{{ number_format($totalReturnOkToday) }}</div>
                <div class="stat-sub">Selesai jahit & dinyatakan OK</div>
            </div>

            <div class="card stat-card">
                <div class="stat-label">Masuk WIP-FIN (Setor)</div>
                <div class="stat-value mono text-success">{{ number_format($wipFinInToday ?? 0) }}</div>
                <div class="stat-sub">Barang OK yang disetor ke WIP-FIN</div>
            </div>

            <div class="card stat-card">
                <div class="stat-label">Reject</div>
                <div class="stat-value mono text-danger">{{ number_format($totalRejectToday) }}</div>
                <div class="stat-sub">Butuh rework / scrap</div>
            </div>

            <div class="card stat-card">
                <div class="stat-label">Outstanding WIP</div>
                <div class="stat-value mono text-warning">{{ number_format($totalOutstanding ?? 0) }}</div>
                <div class="stat-sub">Masih dipegang operator (akumulasi)</div>
            </div>
        </div>

        {{-- Grafik & highlight --}}
        <div class="row g-3 mb-3">
            {{-- Grafik Line --}}
            <div class="col-12 col-lg-7">
                <div class="card p-3 h-100">
                    <div class="section-header">
                        <div class="section-title">
                            <span class="icon">📉</span>
                            <span>Output OK per Jam</span>
                        </div>
                        <span class="muted small">Berdasarkan Sewing Return (non-void)</span>
                    </div>
                    <div style="height: 260px;">
                        <canvas id="sewingHourlyChart"></canvas>
                    </div>
                </div>
            </div>

            {{-- Top Operator & Aging --}}
            <div class="col-12 col-lg-5 d-flex flex-column gap-3">
                <div class="card p-3">
                    <div class="section-header">
                        <div class="section-title">
                            <span class="icon">🏅</span>
                            <span>Operator Terbaik</span>
                        </div>
                    </div>

                    @if ($topOperator)
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="fw-semibold">{{ $topOperator->code }} — {{ $topOperator->name }}</div>
                                <div class="muted small">Output OK terbanyak di tanggal ini</div>
                            </div>
                            <div class="text-end">
                                <div class="stat-value mono text-success" style="font-size: 1.3rem;">
                                    {{ number_format($topOperator->total_ok) }}
                                </div>
                                <div class="muted small">pcs</div>
                            </div>
                        </div>
                    @else
                        <div class="text-muted small">Belum ada Sewing Return pada filter ini.</div>
                    @endif
                </div>

                <div class="card p-3">
                    <div class="section-header">
                        <div class="section-title">
                            <span class="icon">⏳</span>
                            <span>WIP Terlama</span>
                        </div>
                    </div>

                    @if ($agingWip)
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="fw-semibold">
                                    {{ $agingWip['operator']->code }} — {{ $agingWip['operator']->name }}
                                </div>
                                <div class="muted small">Masih memegang WIP sewing</div>
                            </div>
                            <div class="text-end">
                                <div class="stat-value mono text-danger" style="font-size: 1.3rem;">
                                    {{ $agingWip['aging'] }} hari
                                </div>
                                <div class="muted small">sejak tanggal pickup</div>
                            </div>
                        </div>
                    @else
                        <div class="text-muted small">Tidak ada WIP menggantung yang terdeteksi.</div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Outstanding per Operator (detail barang + tanggal + pickup) --}}
        <div class="card p-3 mb-3">
            <div class="section-header">
                <div class="section-title">
                    <span class="icon">🧵</span>
                    <span>Outstanding WIP per Operator</span>
                </div>
                <span class="muted small">Tidak termasuk data void</span>
            </div>

            @if (($outstandingDetail ?? collect())->isEmpty())
                <div class="text-muted small">Tidak ada WIP yang outstanding.</div>
            @else
                <div class="table-wrap">
                    <table class="table table-sm align-middle mb-0">
                        <thead>
                            <tr>
                                <th style="width:56px;">No</th>
                                <th>Operator</th>
                                <th>Kode Barang</th>
                                <th>Tanggal Ambil Jahit</th>
                                <th class="text-end">Pickup</th>
                                <th class="text-end">Outstanding (pcs)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($outstandingDetail as $i => $row)
                                @php
                                    $tgl = $row->tanggal_ambil ? \Carbon\Carbon::parse($row->tanggal_ambil) : null;
                                @endphp
                                <tr>
                                    <td class="mono text-muted">{{ $i + 1 }}</td>
                                    <td>
                                        <div class="fw-semibold">{{ $row->operator_code }} — {{ $row->operator_name }}
                                        </div>
                                    </td>
                                    <td>
                                        <div class="fw-semibold mono">{{ $row->item_code }}</div>
                                        <div class="muted small">{{ $row->item_name }}</div>
                                    </td>
                                    <td class="mono">{{ $tgl ? id_date($tgl) : '-' }}</td>
                                    <td class="text-end mono">{{ number_format((int) $row->picked_total) }}</td>
                                    <td class="text-end mono">{{ number_format((int) $row->outstanding) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        {{-- Breakdown per Item (OK & Reject) --}}
        <div class="card p-3 mb-3">
            <div class="section-header">
                <div class="section-title">
                    <span class="icon">📦</span>
                    <span>Breakdown per Item</span>
                </div>
                <span class="muted small">Rekap OK & reject item (non-void) di tanggal terpilih</span>
            </div>

            <div class="table-wrap">
                <table class="table table-sm align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th class="text-end">OK (pcs)</th>
                            <th class="text-end text-danger">Reject (pcs)</th>
                            <th class="text-end">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($itemBreakdown as $row)
                            @php
                                $total = (int) $row->total_ok + (int) $row->total_reject;
                            @endphp
                            <tr>
                                <td>
                                    <div class="fw-semibold mono">{{ $row->code ?? '' }}</div>
                                    <div class="muted small">{{ $row->name ?? '' }}</div>
                                </td>
                                <td class="text-end mono text-success">{{ number_format((int) $row->total_ok) }}</td>
                                <td class="text-end mono text-danger">{{ number_format((int) $row->total_reject) }}</td>
                                <td class="text-end mono">{{ number_format($total) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted py-3">
                                    Belum ada Sewing Return pada filter ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Masuk WIP-FIN per Item (Setor OK) --}}
        <div class="card p-3">
            <div class="section-header">
                <div class="section-title">
                    <span class="icon">📥</span>
                    <span>Masuk WIP-FIN per Item</span>
                </div>
                <span class="muted small">Setor OK (non-void) di tanggal terpilih</span>
            </div>

            <div class="table-wrap">
                <table class="table table-sm align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th class="text-end">Masuk WIP-FIN (pcs)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($wipFinInBreakdown as $row)
                            <tr>
                                <td>
                                    <div class="fw-semibold mono">{{ $row->code ?? '' }}</div>
                                    <div class="muted small">{{ $row->name ?? '' }}</div>
                                </td>
                                <td class="text-end mono text-success">{{ number_format((int) $row->qty_in) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="2" class="text-center text-muted py-3">
                                    Belum ada setor WIP-FIN pada filter ini.
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
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // === Auto refresh setiap 10 menit ===
            setInterval(function() {
                window.location.reload();
            }, 10 * 60 * 1000);

            // === Chart: Output OK per Jam ===
            const ctx = document.getElementById('sewingHourlyChart');
            if (ctx) {
                const hourlyData = @json($hourlyOutput);

                const labels = hourlyData.map(d => d.label);
                const dataOk = hourlyData.map(d => d.qty_ok);

                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'OK (pcs)',
                            data: dataOk,
                            borderWidth: 2,
                            tension: 0.35,
                            fill: true,
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            x: {
                                ticks: {
                                    maxRotation: 0,
                                    autoSkip: true,
                                    maxTicksLimit: 8
                                }
                            },
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    precision: 0
                                }
                            }
                        },
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(ctx) {
                                        return 'OK: ' + ctx.parsed.y + ' pcs';
                                    }
                                }
                            }
                        },
                        elements: {
                            point: {
                                radius: 2
                            }
                        }
                    }
                });
            }
        });
    </script>
@endpush
