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
            padding: 0.7rem 0.85rem;
            border-radius: 14px;
            display: flex;
            flex-direction: column;
            gap: 0.1rem;
            height: 100%;
        }

        .stat-label {
            font-size: .72rem;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: var(--muted);
        }

        .stat-value {
            font-size: 1.25rem;
            font-weight: 650;
            line-height: 1.1;
        }

        .stat-sub {
            font-size: .78rem;
            color: var(--muted);
            line-height: 1.25;
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
            -webkit-overflow-scrolling: touch;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: .5rem;
            margin-bottom: .4rem;
        }

        .section-title {
            font-size: .92rem;
            font-weight: 650;
            display: flex;
            align-items: center;
            gap: .35rem;
        }

        .section-title .icon {
            font-size: 1.05rem;
        }

        .badge-soft {
            border-radius: 999px;
            padding: .15rem .55rem;
            font-size: .72rem;
            background: color-mix(in srgb, var(--card) 72%, var(--line) 28%);
            color: var(--muted);
        }

        /* KPI grid: mobile 2 kolom, desktop 5 kolom */
        .kpi-grid {
            display: grid;
            gap: .55rem;
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        @media (min-width: 768px) {
            .kpi-grid {
                grid-template-columns: repeat(5, minmax(0, 1fr));
            }
        }

        /* Mobile: ringkas spacing */
        @media (max-width: 575.98px) {
            .page-wrap {
                padding-inline: .35rem;
            }

            .stat-card {
                padding: .65rem .75rem;
            }

            .stat-value {
                font-size: 1.15rem;
            }

            .muted {
                font-size: .82rem;
            }

            .table td,
            .table th {
                padding-top: .45rem;
                padding-bottom: .45rem;
            }
        }

        /* Tabs compact */
        .compact-tabs .nav-link {
            padding: .35rem .6rem;
            font-size: .85rem;
        }

        .compact-tabs .nav-link.active {
            font-weight: 600;
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
                <div class="muted">Ringkasan performa jahit & status WIP untuk tanggal yang dipilih.</div>
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
                    <button type="submit" class="btn btn-sm btn-primary flex-grow-1">Terapkan</button>
                    <a href="{{ route('production.reports.dashboard') }}"
                        class="btn btn-sm btn-outline-secondary">Reset</a>
                </div>
            </form>
        </div>
        @if (auth()->check() && auth()->user()->role !== 'operating')
            {{-- KPI Utama (compact) --}}
            <div class="kpi-grid mb-3">
                <div class="card stat-card">
                    <div class="stat-label">Pickup</div>
                    <div class="stat-value mono text-primary">{{ number_format($totalPickupToday) }}</div>
                    <div class="stat-sub">Diambil operator</div>
                </div>

                <div class="card stat-card">
                    <div class="stat-label">Return OK</div>
                    <div class="stat-value mono text-success">{{ number_format($totalReturnOkToday) }}</div>
                    <div class="stat-sub">Selesai OK</div>
                </div>

                <div class="card stat-card">
                    <div class="stat-label">Setor WIP-FIN</div>
                    <div class="stat-value mono text-success">{{ number_format($wipFinInToday ?? 0) }}</div>
                    <div class="stat-sub">Masuk WIP-FIN</div>
                </div>

                <div class="card stat-card">
                    <div class="stat-label">Reject</div>
                    <div class="stat-value mono text-danger">{{ number_format($totalRejectToday) }}</div>
                    <div class="stat-sub">Rework / scrap</div>
                </div>

                <div class="card stat-card">
                    <div class="stat-label">Outstanding</div>
                    <div class="stat-value mono text-warning">{{ number_format($totalOutstanding ?? 0) }}</div>
                    <div class="stat-sub">Masih dipegang</div>
                </div>
            </div>

            {{-- Grafik & highlight --}}
            <div class="row g-3 mb-3">
                {{-- Grafik Line --}}
                <div class="col-12 col-lg-7">
                    <div class="card p-3 h-100">
                        <div class="section-header">
                            <div class="section-title"><span class="icon">📉</span><span>Output OK per Jam</span></div>
                            <span class="muted small d-none d-md-inline">Berdasarkan Sewing Return (non-void)</span>
                        </div>
                        <div style="height: 240px;">
                            <canvas id="sewingHourlyChart"></canvas>
                        </div>
                    </div>
                </div>

                {{-- Top Operator & Aging --}}
                <div class="col-12 col-lg-5 d-flex flex-column gap-3">
                    <div class="card p-3">
                        <div class="section-header">
                            <div class="section-title"><span class="icon">🏅</span><span>Highlight</span></div>
                            <span class="muted small">Hari ini</span>
                        </div>

                        <div class="d-flex flex-column gap-2">
                            {{-- Top Operator --}}
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="fw-semibold">Operator Terbaik</div>
                                    @if ($topOperator)
                                        <div class="muted small">{{ $topOperator->code }} — {{ $topOperator->name }}</div>
                                    @else
                                        <div class="muted small">Belum ada return</div>
                                    @endif
                                </div>
                                <div class="text-end">
                                    <div class="mono fw-semibold text-success">
                                        {{ $topOperator ? number_format($topOperator->total_ok) : '0' }}
                                    </div>
                                    <div class="muted small">OK</div>
                                </div>
                            </div>

                            <hr class="my-1">

                            {{-- Aging --}}
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="fw-semibold">WIP Terlama</div>
                                    @if ($agingWip)
                                        <div class="muted small">{{ $agingWip['operator']->code }} —
                                            {{ $agingWip['operator']->name }}</div>
                                    @else
                                        <div class="muted small">Tidak ada</div>
                                    @endif
                                </div>
                                <div class="text-end">
                                    <div class="mono fw-semibold text-danger">
                                        {{ $agingWip ? $agingWip['aging'] . ' hari' : '-' }}
                                    </div>
                                    <div class="muted small">sejak pickup</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- Outstanding per Operator --}}
        <div class="card p-3 mb-3">
            <div class="section-header">
                <div class="section-title"><span class="icon">🧵</span><span>Belum Setor Jahit</span></div>
                <span class="muted small">Non-void</span>
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
                                <th>Kode</th>
                                <th class="d-none d-md-table-cell">Tanggal Ambil</th>
                                <th class="text-end d-none d-md-table-cell">Pickup</th>
                                <th class="text-end">Belum Setor</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($outstandingDetail as $i => $row)
                                @php $tgl = $row->tanggal_ambil ? \Carbon\Carbon::parse($row->tanggal_ambil) : null; @endphp
                                <tr>
                                    <td class="mono text-muted">{{ $i + 1 }}</td>
                                    <td>
                                        <div class="fw-semibold">{{ $row->operator_code }} —
                                            {{ $row->operator_name }}
                                        </div>
                                        <div class="muted small d-md-none">{{ $row->item_code }} •
                                            {{ $tgl ? id_date($tgl) : '-' }}</div>
                                    </td>
                                    <td class="mono">
                                        <div class="fw-semibold">{{ $row->item_code }}</div>
                                        <div class="muted small d-none d-md-block">{{ $row->item_name }}</div>
                                    </td>
                                    <td class="mono d-none d-md-table-cell">{{ $tgl ? id_date($tgl) : '-' }}</td>
                                    <td class="text-end mono d-none d-md-table-cell">
                                        {{ number_format((int) $row->picked_total) }}</td>
                                    <td class="text-end mono">{{ number_format((int) $row->outstanding) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
        {{-- Breakdown (Pickup / Setor / WIP-FIN) - Compact tabs --}}
        <div class="card p-3">
            <div class="section-header">
                <div class="section-title"><span class="icon">📊</span><span>Breakdown</span></div>
                <span class="muted small d-none d-md-inline">Ringkasan per item</span>
            </div>

            <ul class="nav nav-pills compact-tabs mb-2" id="breakdownTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="tab-pickup" data-bs-toggle="pill" data-bs-target="#pane-pickup"
                        type="button" role="tab" aria-controls="pane-pickup" aria-selected="true">
                        Ambil Jahit
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="tab-setor" data-bs-toggle="pill" data-bs-target="#pane-setor"
                        type="button" role="tab" aria-controls="pane-setor" aria-selected="false">
                        Setor Jahit
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="tab-wipfin" data-bs-toggle="pill" data-bs-target="#pane-wipfin"
                        type="button" role="tab" aria-controls="pane-wipfin" aria-selected="false">
                        Belum Packing
                    </button>
                </li>
            </ul>

            <div class="tab-content" id="breakdownTabsContent">
                {{-- Pickup --}}
                <div class="tab-pane fade show active" id="pane-pickup" role="tabpanel" aria-labelledby="tab-pickup">
                    <div class="muted small mb-2">Tanggal pickup (non-void)</div>
                    <div class="table-wrap">
                        <table class="table table-sm align-middle mb-0">
                            <thead>
                                <tr>
                                    <th style="width:56px;">No</th>
                                    <th>Tgl</th>
                                    <th>Operator</th>
                                    <th>Kode</th>
                                    <th class="text-end">Qty</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse (($pickupBreakdown ?? collect()) as $i => $row)
                                    @php
                                        $tglPickup = $row->tanggal_pickup
                                            ? \Carbon\Carbon::parse($row->tanggal_pickup)
                                            : null;
                                        $pickup = (int) ($row->qty_pickup ?? 0);
                                    @endphp
                                    <tr>
                                        <td class="mono text-muted">{{ $i + 1 }}</td>
                                        <td class="mono">{{ $tglPickup ? $tglPickup->format('d/m') : '-' }}</td>
                                        <td>
                                            <div class="fw-semibold">{{ $row->operator_code ?? '-' }}</div>
                                            <div class="muted small">{{ $row->operator_name ?? '-' }}</div>
                                        </td>
                                        <td>
                                            <div class="fw-semibold mono">{{ $row->item_code ?? '' }}</div>
                                            <div class="muted small d-none d-md-block">{{ $row->item_name ?? '' }}
                                            </div>
                                        </td>
                                        <td class="text-end mono text-primary">{{ number_format($pickup) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-3">
                                            Belum ada Sewing Pickup pada filter ini.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Setor --}}
                <div class="tab-pane fade" id="pane-setor" role="tabpanel" aria-labelledby="tab-setor">
                    <div class="muted small mb-2">Tanggal setor dari created_at return line (non-void)</div>
                    <div class="table-wrap">
                        <table class="table table-sm align-middle mb-0">
                            <thead>
                                <tr>
                                    <th style="width:56px;">No</th>
                                    <th>Tgl</th>
                                    <th>Operator</th>
                                    <th>Kode</th>
                                    <th class="text-end">OK</th>
                                    <th class="text-end text-danger">RJ</th>
                                    <th class="text-end">Tot</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($itemBreakdown as $i => $row)
                                    @php
                                        $tglSetor = $row->tanggal_setor
                                            ? \Carbon\Carbon::parse($row->tanggal_setor)
                                            : null;
                                        $ok = (int) ($row->qty_ok ?? 0);
                                        $reject = (int) ($row->qty_reject ?? 0);
                                        $total = (int) ($row->qty_total ?? $ok + $reject);
                                    @endphp
                                    <tr>
                                        <td class="mono text-muted">{{ $i + 1 }}</td>
                                        <td class="mono">{{ $tglSetor ? $tglSetor->format('d/m') : '-' }}</td>
                                        <td>
                                            <div class="fw-semibold">{{ $row->operator_code ?? '-' }}</div>
                                            <div class="muted small">{{ $row->operator_name ?? '-' }}</div>
                                        </td>
                                        <td>
                                            <div class="fw-semibold mono">{{ $row->item_code ?? '' }}</div>
                                            <div class="muted small d-none d-md-block">{{ $row->item_name ?? '' }}
                                            </div>
                                        </td>
                                        <td class="text-end mono text-success">{{ number_format($ok) }}</td>
                                        <td class="text-end mono text-danger">{{ number_format($reject) }}</td>
                                        <td class="text-end mono">{{ number_format($total) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-3">
                                            Belum ada Sewing Return pada filter ini.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- WIP-FIN --}}
                <div class="tab-pane fade" id="pane-wipfin" role="tabpanel" aria-labelledby="tab-wipfin">
                    <div class="muted small mb-2">Setor OK (non-void) di tanggal terpilih</div>
                    <div class="table-wrap">
                        <table class="table table-sm align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Item</th>
                                    <th class="text-end">Masuk</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($wipFinInBreakdown as $row)
                                    <tr>
                                        <td>
                                            <div class="fw-semibold mono">{{ $row->code ?? '' }}</div>
                                            <div class="muted small">{{ $row->name ?? '' }}</div>
                                        </td>
                                        <td class="text-end mono text-success">{{ number_format((int) $row->qty_in) }}
                                        </td>
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
