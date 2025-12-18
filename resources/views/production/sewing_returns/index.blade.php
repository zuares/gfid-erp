{{-- resources/views/production/sewing_returns/index.blade.php --}}
@extends('layouts.app')

@section('title', 'Produksi • Sewing Returns')

@push('head')
    <style>
        .page-wrap {
            max-width: 1100px;
            margin-inline: auto;
        }

        .card {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 16px;
        }

        .mono {
            font-variant-numeric: tabular-nums;
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, "Liberation Mono";
        }

        .help {
            color: var(--muted);
            font-size: .82rem;
        }

        .status-pill {
            border-radius: 999px;
            padding: .15rem .7rem;
            font-size: .72rem;
        }

        .filter-label {
            font-size: .74rem;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: var(--muted);
        }

        .table-wrap {
            overflow-x: auto;
        }

        .table-sewing-return-index th {
            font-size: .74rem;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: var(--muted);
            border-top: none;
        }

        .sr-card-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: .5rem;
            margin-bottom: .2rem;
        }

        .sr-code {
            font-size: .92rem;
            font-weight: 700;
        }

        .sr-meta {
            font-size: .78rem;
            color: var(--muted);
        }

        .sr-meta-inline {
            display: flex;
            flex-wrap: wrap;
            gap: .15rem .6rem;
            margin-top: .15rem;
        }

        .sr-meta-chip {
            font-size: .75rem;
            color: var(--muted);
        }

        .sr-amounts {
            margin-top: .25rem;
            display: flex;
            justify-content: flex-start;
            gap: .75rem;
            font-size: .78rem;
        }

        .sr-amounts span {
            white-space: nowrap;
        }

        @media (max-width: 767.98px) {
            .page-wrap {
                padding-inline: .75rem;
                padding-bottom: 5rem;
            }

            .card {
                border-radius: 14px;
            }

            .header-row {
                flex-direction: column;
                align-items: stretch;
                gap: .6rem;
            }

            .header-actions {
                display: flex;
                gap: .5rem;
            }

            .header-actions .btn {
                flex: 1;
                justify-content: center;
            }

            .filter-row {
                flex-direction: column;
            }

            .table-sewing-return-index thead {
                display: none;
            }

            .table-sewing-return-index {
                border-collapse: separate;
                border-spacing: 0 .55rem;
            }

            .table-sewing-return-index tbody tr {
                display: block;
                border-radius: 12px;
                border: 1px solid var(--line);
                padding: .6rem .7rem;
                background: var(--card);
            }

            .table-sewing-return-index td {
                display: block;
                border: none !important;
                padding: .06rem 0;
                font-size: .8rem;
            }
        }

        @media (min-width: 768px) {
            .filter-row .col-auto {
                display: flex;
                align-items: flex-end;
            }
        }
    </style>
@endpush

@section('content')
    @php
        $statusOptions = [
            '' => 'Semua',
            'posted' => 'Posted',
            'closed' => 'Closed',
            'draft' => 'Draft',
        ];
    @endphp

    <div class="page-wrap py-3 py-md-4">

        {{-- Header + Filter --}}
        <div class="card p-3 p-md-4 mb-3">
            <div class="d-flex justify-content-between align-items-start header-row">
                <div>
                    <h1 class="h5 mb-1">Sewing Returns</h1>
                    <div class="help">Rekap semua setoran hasil jahit dari WIP-SEW ke WIP-FIN.</div>
                </div>

                <div class="header-actions">
                    <a href="{{ route('production.sewing_pickups.index') }}"
                        class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1">
                        <i class="bi bi-arrow-left"></i><span>Ke Sewing Pickup</span>
                    </a>
                    <a href="{{ route('production.sewing_returns.create') }}"
                        class="btn btn-sm btn-success d-inline-flex align-items-center gap-1">
                        <i class="bi bi-plus-lg"></i><span>Setor Jahit</span>
                    </a>
                </div>
            </div>

            <form method="get" class="mt-3">
                <div class="row g-2 filter-row">
                    <div class="col-6 col-md-2">
                        <div class="filter-label mb-1">Dari</div>
                        <input type="date" name="from_date" value="{{ $filters['from_date'] }}"
                            class="form-control form-control-sm">
                    </div>
                    <div class="col-6 col-md-2">
                        <div class="filter-label mb-1">Sampai</div>
                        <input type="date" name="to_date" value="{{ $filters['to_date'] }}"
                            class="form-control form-control-sm">
                    </div>

                    <div class="col-6 col-md-3">
                        <div class="filter-label mb-1">Operator</div>
                        <select name="operator_id" class="form-select form-select-sm">
                            <option value="">Semua operator</option>
                            @foreach ($operators as $op)
                                <option value="{{ $op->id }}"
                                    {{ (string) $filters['operator_id'] === (string) $op->id ? 'selected' : '' }}>
                                    {{ $op->code }} — {{ $op->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-6 col-md-2">
                        <div class="filter-label mb-1">Status</div>
                        <select name="status" class="form-select form-select-sm">
                            @foreach ($statusOptions as $value => $label)
                                <option value="{{ $value }}"
                                    {{ (string) $filters['status'] === (string) $value ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-12 col-md-3">
                        <div class="filter-label mb-1">Cari</div>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-light border-end-0"><i class="bi bi-search"></i></span>
                            <input type="text" name="q" value="{{ $filters['q'] }}"
                                class="form-control border-start-0" placeholder="Kode return / pickup / operator...">
                            @if (array_filter($filters))
                                <button class="btn btn-outline-secondary" type="button"
                                    onclick="window.location='{{ route('production.sewing_returns.index') }}'">
                                    Reset
                                </button>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="mt-2 d-flex justify-content-end">
                    <button type="submit" class="btn btn-sm btn-primary">Terapkan Filter</button>
                </div>
            </form>
        </div>

        {{-- List --}}
        <div class="card p-3 p-md-4 mb-3">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h2 class="h6 mb-0">Daftar Sewing Return</h2>
                <div class="help mb-0">
                    Menampilkan {{ $returns->firstItem() ?? 0 }}–{{ $returns->lastItem() ?? 0 }}
                    dari {{ $returns->total() }} data.
                </div>
            </div>

            <div class="table-wrap">
                <table class="table table-sm align-middle mono table-sewing-return-index mb-0">
                    <thead>
                        <tr>
                            <th style="width:110px;">Tanggal</th>
                            <th style="width:150px;">Kode</th>
                            <th style="width:190px;">Operator</th>
                            <th style="width:170px;">Pickup</th>
                            <th style="width:110px;" class="text-end">Total OK</th>
                            <th style="width:110px;" class="text-end">Total Reject</th>
                            <th style="width:110px;">Status</th>
                            <th style="width:90px;"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($returns as $ret)
                            @php
                                $totalOk = (float) ($ret->total_ok ?? 0);
                                $totalReject = (float) ($ret->total_reject ?? 0);

                                $statusMap = [
                                    'draft' => ['label' => 'Draft', 'class' => 'secondary'],
                                    'posted' => ['label' => 'Posted', 'class' => 'primary'],
                                    'closed' => ['label' => 'Closed', 'class' => 'success'],
                                ];
                                $cfg = $statusMap[$ret->status] ?? [
                                    'label' => strtoupper($ret->status ?? '-'),
                                    'class' => 'secondary',
                                ];

                                $pickupCode = $ret->pickup?->code ?? '-';
                            @endphp

                            {{-- Desktop row --}}
                            <tr class="d-none d-md-table-row">
                                <td>{{ $ret->date?->format('Y-m-d') ?? $ret->date }}</td>
                                <td>
                                    <a href="{{ route('production.sewing_returns.show', $ret) }}">{{ $ret->code }}</a>
                                </td>
                                <td>
                                    @if ($ret->operator)
                                        {{ $ret->operator->code }} — {{ $ret->operator->name }}
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($ret->pickup)
                                        <a
                                            href="{{ route('production.sewing_pickups.show', $ret->pickup) }}">{{ $pickupCode }}</a>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td class="text-end">{{ number_format($totalOk, 2, ',', '.') }}</td>
                                <td class="text-end {{ $totalReject > 0 ? 'text-danger' : '' }}">
                                    {{ number_format($totalReject, 2, ',', '.') }}</td>
                                <td><span class="status-pill bg-{{ $cfg['class'] }} text-light">{{ $cfg['label'] }}</span>
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('production.sewing_returns.show', $ret) }}"
                                        class="btn btn-sm btn-outline-primary">Detail</a>
                                </td>
                            </tr>

                            {{-- Mobile card row --}}
                            <tr class="d-md-none">
                                <td>
                                    <div class="sr-card-header">
                                        <div>
                                            <div class="sr-code">
                                                <a
                                                    href="{{ route('production.sewing_returns.show', $ret) }}">{{ $ret->code }}</a>
                                            </div>
                                            <div class="sr-meta">{{ $ret->date?->format('Y-m-d') ?? $ret->date }}</div>
                                        </div>
                                        <span
                                            class="status-pill bg-{{ $cfg['class'] }} text-light">{{ $cfg['label'] }}</span>
                                    </div>

                                    <div class="sr-meta-inline">
                                        <span class="sr-meta-chip">Op: {{ $ret->operator?->code ?? '-' }}</span>
                                        <span class="sr-meta-chip">Pickup: {{ $pickupCode }}</span>
                                    </div>

                                    <div class="sr-amounts">
                                        <span>OK: {{ number_format($totalOk, 2, ',', '.') }}</span>
                                        <span class="{{ $totalReject > 0 ? 'text-danger' : '' }}">RJ:
                                            {{ number_format($totalReject, 2, ',', '.') }}</span>
                                    </div>

                                    @if ($ret->notes)
                                        <div class="mt-1 text-muted small">
                                            {{ \Illuminate\Support\Str::limit($ret->notes, 80) }}</div>
                                    @endif

                                    <div class="mt-1">
                                        <a href="{{ route('production.sewing_returns.show', $ret) }}" class="small">Lihat
                                            detail →</a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted small py-3">
                                    Belum ada Sewing Return yang tersimpan.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($returns->hasPages())
                <div class="mt-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div class="small text-muted">
                        Halaman {{ $returns->currentPage() }} dari {{ $returns->lastPage() }}
                    </div>
                    <div>{{ $returns->links() }}</div>
                </div>
            @endif
        </div>
    </div>
@endsection
