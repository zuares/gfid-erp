{{-- resources/views/production/sewing.returns/index.blade.php --}}
@extends('layouts.app')

@section('title', 'Produksi • Sewing Returns')

@push('head')
    <style>
        .sewing-return-page {
            min-height: 100vh;
        }

        .page-wrap {
            max-width: 1100px;
            margin-inline: auto;
            padding: 1rem 1rem 3.5rem;
        }

        body[data-theme="light"] .page-wrap {
            background: radial-gradient(circle at top left, rgba(59, 130, 246, 0.12) 0, rgba(45, 212, 191, 0.08) 28%, #f9fafb 60%);
        }

        body[data-theme="dark"] .page-wrap {
            background: radial-gradient(circle at top left, rgba(59, 130, 246, 0.25) 0, rgba(45, 212, 191, 0.15) 26%, #020617 60%);
        }

        .card-main {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 16px;
            box-shadow: 0 12px 30px rgba(15, 23, 42, 0.12), 0 0 0 1px rgba(148, 163, 184, 0.08);
        }

        body[data-theme="dark"] .card-main {
            border-color: rgba(30, 64, 175, 0.55);
            box-shadow: 0 16px 40px rgba(0, 0, 0, 0.78), 0 0 0 1px rgba(15, 23, 42, 0.8);
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

        .table-sewing-return-index td {
            vertical-align: middle;
        }

        .header-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: .75rem;
            flex-wrap: wrap;
        }

        .header-actions {
            display: flex;
            gap: .4rem;
        }

        .summary-row {
            display: flex;
            flex-wrap: wrap;
            gap: .35rem;
            margin-top: .4rem;
        }

        .summary-pill {
            border-radius: 999px;
            padding: .12rem .6rem;
            font-size: .78rem;
            background: rgba(148, 163, 184, 0.14);
            color: var(--muted);
        }

        .summary-pill-accent {
            background: rgba(13, 110, 253, 0.12);
            color: #0d6efd;
        }

        .sr-card-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: .5rem;
            margin-bottom: .2rem;
        }

        .sr-code {
            font-size: .9rem;
            font-weight: 600;
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

            .card-main {
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
        $user = auth()->user();
        $role = $user?->role ?? null;
        $isOperating = $role === 'operating';

        $statusOptions = [
            '' => 'Semua',
            'posted' => 'Posted',
            'closed' => 'Closed',
            'draft' => 'Draft',
        ];

        $totalReturns = $returns->total();

        // Ringkasan halaman: total pickup & total belum setor
        $sumPickupPage = 0.0;
        $sumRemainingPage = 0.0;

        foreach ($returns as $ret) {
            $lines = $ret->lines ?? collect();

            $totalPickupRow = $lines->sum(function ($line) {
                $pl = $line->sewingPickupLine;
                return (float) ($pl->qty_bundle ?? 0);
            });

            $totalRemainingRow = $lines->sum(function ($line) {
                $pl = $line->sewingPickupLine;
                if (!$pl) {
                    return 0;
                }

                $qtyBundle = (float) ($pl->qty_bundle ?? 0);
                $returnedOk = (float) ($pl->qty_returned_ok ?? 0);
                $returnedRej = (float) ($pl->qty_returned_reject ?? 0);
                $directPick = (float) ($pl->qty_direct_picked ?? 0);
                $progressAdj = (float) ($pl->qty_progress_adjusted ?? 0); // ✅ NEW

                return max($qtyBundle - ($returnedOk + $returnedRej + $directPick + $progressAdj), 0);
            });

            $sumPickupPage += $totalPickupRow;
            $sumRemainingPage += $totalRemainingRow;
        }
    @endphp

    <div class="sewing-return-page">
        <div class="page-wrap py-3 py-md-4">

            {{-- HEADER + FILTER --}}
            <div class="card-main p-3 p-md-4 mb-3">
                <div class="header-row">
                    <div>
                        <h1 class="h5 mb-1">Setoran Jahit</h1>
                        <div class="help">Rekap setoran jahit per operator, per hari.</div>

                        @if ($totalReturns > 0)
                            <div class="summary-row mono">
                                <span class="summary-pill">{{ number_format($totalReturns, 0, ',', '.') }} return</span>
                                <span class="summary-pill">{{ number_format($sumPickupPage, 2, ',', '.') }} pcs Pickup
                                    (halaman ini)</span>
                                <span
                                    class="summary-pill summary-pill-accent">{{ number_format($sumRemainingPage, 2, ',', '.') }}
                                    pcs Belum Setor (halaman ini)</span>
                            </div>
                        @else
                            <div class="help mt-1">Belum ada Sewing Return tercatat.</div>
                        @endif
                    </div>

                    <div class="header-actions">
                        <a href="{{ route('production.sewing.pickups.index') }}"
                            class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1">
                            <i class="bi bi-arrow-left"></i><span>Ke Sewing Pickup</span>
                        </a>
                        <a href="{{ route('production.sewing.returns.create') }}"
                            class="btn btn-sm btn-success d-inline-flex align-items-center gap-1">
                            <i class="bi bi-plus-lg"></i><span class="text-white">Setor Jahit</span>
                        </a>
                    </div>
                </div>

                @if (!$isOperating)
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
                                        class="form-control border-start-0" placeholder="Kode return / operator...">
                                    @if (array_filter($filters))
                                        <button class="btn btn-outline-secondary" type="button"
                                            onclick="window.location='{{ route('production.sewing.returns.index') }}'">Reset</button>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="mt-2 d-flex justify-content-end">
                            <button type="submit" class="btn btn-sm btn-primary">Terapkan Filter</button>
                        </div>
                    </form>
                @endif
            </div>

            {{-- LIST --}}
            <div class="card-main p-3 p-md-4 mb-3">
                <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-1">
                    <h2 class="h6 mb-0">Daftar Sewing Return</h2>
                    <div class="help mb-0">
                        Menampilkan {{ $returns->firstItem() ?? 0 }}–{{ $returns->lastItem() ?? 0 }}
                        dari {{ $returns->total() }} data.
                    </div>
                </div>

                {{-- DESKTOP --}}
                <div class="table-wrap d-none d-md-block">
                    <table class="table table-sm align-middle mono table-sewing-return-index mb-0">
                        <thead>
                            <tr>
                                <th style="width:110px;">Tanggal</th>
                                <th style="width:220px;">Operator</th>
                                <th class="text-end" style="width:140px;">Total Pickup</th>
                                <th class="text-end" style="width:140px;">Belum Setor</th>
                                <th style="width:90px;"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($returns as $ret)
                                @php
                                    $lines = $ret->lines ?? collect();

                                    $totalPickupRow = $lines->sum(
                                        fn($line) => (float) (optional($line->sewingPickupLine)->qty_bundle ?? 0),
                                    );

                                    $totalRemainingRow = $lines->sum(function ($line) {
                                        $pl = $line->sewingPickupLine;
                                        if (!$pl) {
                                            return 0;
                                        }

                                        $qtyBundle = (float) ($pl->qty_bundle ?? 0);
                                        $returnedOk = (float) ($pl->qty_returned_ok ?? 0);
                                        $returnedRej = (float) ($pl->qty_returned_reject ?? 0);
                                        $directPick = (float) ($pl->qty_direct_picked ?? 0);
                                        $progressAdj = (float) ($pl->qty_progress_adjusted ?? 0);

                                        return max(
                                            $qtyBundle - ($returnedOk + $returnedRej + $directPick + $progressAdj),
                                            0,
                                        );
                                    });

                                    $statusMap = [
                                        'draft' => ['label' => 'Draft', 'class' => 'secondary'],
                                        'posted' => ['label' => 'Posted', 'class' => 'primary'],
                                        'closed' => ['label' => 'Closed', 'class' => 'success'],
                                    ];
                                    $cfg = $statusMap[$ret->status] ?? [
                                        'label' => strtoupper($ret->status ?? '-'),
                                        'class' => 'secondary',
                                    ];
                                @endphp

                                <tr>
                                    <td>
                                        <div>{{ $ret->date?->format('Y-m-d') ?? $ret->date }}</div>
                                        <div class="small text-muted">{{ $ret->code }}</div>
                                    </td>
                                    <td>
                                        @if ($ret->operator)
                                            {{ $ret->operator->code }} — {{ $ret->operator->name }}
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                        <div>
                                            <span
                                                class="status-pill bg-{{ $cfg['class'] }} text-light">{{ $cfg['label'] }}</span>
                                        </div>
                                    </td>
                                    <td class="text-end">{{ number_format($totalPickupRow, 2, ',', '.') }}</td>
                                    <td class="text-end {{ $totalRemainingRow > 0 ? 'text-warning' : 'text-muted' }}">
                                        {{ number_format($totalRemainingRow, 2, ',', '.') }}
                                    </td>
                                    <td class="text-end">
                                        <a href="{{ route('production.sewing.returns.show', $ret) }}"
                                            class="btn btn-sm btn-outline-primary">Detail</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted small py-3">Belum ada Sewing Return
                                        yang tersimpan.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- MOBILE --}}
                <div class="d-block d-md-none">
                    @if ($returns->isEmpty())
                        <div class="text-center text-muted small py-3">Belum ada Sewing Return yang tersimpan.</div>
                    @else
                        <div class="d-flex flex-column gap-2">
                            @foreach ($returns as $ret)
                                @php
                                    $lines = $ret->lines ?? collect();

                                    $totalPickupRow = $lines->sum(
                                        fn($line) => (float) (optional($line->sewingPickupLine)->qty_bundle ?? 0),
                                    );

                                    $totalRemainingRow = $lines->sum(function ($line) {
                                        $pl = $line->sewingPickupLine;
                                        if (!$pl) {
                                            return 0;
                                        }

                                        $qtyBundle = (float) ($pl->qty_bundle ?? 0);
                                        $returnedOk = (float) ($pl->qty_returned_ok ?? 0);
                                        $returnedRej = (float) ($pl->qty_returned_reject ?? 0);
                                        $directPick = (float) ($pl->qty_direct_picked ?? 0);
                                        $progressAdj = (float) ($pl->qty_progress_adjusted ?? 0);

                                        return max(
                                            $qtyBundle - ($returnedOk + $returnedRej + $directPick + $progressAdj),
                                            0,
                                        );
                                    });

                                    $statusMap = [
                                        'draft' => ['label' => 'Draft', 'class' => 'secondary'],
                                        'posted' => ['label' => 'Posted', 'class' => 'primary'],
                                        'closed' => ['label' => 'Closed', 'class' => 'success'],
                                    ];
                                    $cfg = $statusMap[$ret->status] ?? [
                                        'label' => ucfirst($ret->status ?? '-'),
                                        'class' => 'secondary',
                                    ];
                                @endphp

                                <div class="card-main p-2">
                                    <div class="sr-card-header">
                                        <div>
                                            <div class="sr-code">
                                                <a
                                                    href="{{ route('production.sewing.returns.show', $ret) }}">{{ $ret->code }}</a>
                                            </div>
                                            <div class="sr-meta">{{ $ret->date?->format('Y-m-d') ?? $ret->date }}</div>
                                        </div>
                                        <span
                                            class="status-pill bg-{{ $cfg['class'] }} text-light">{{ $cfg['label'] }}</span>
                                    </div>

                                    <div class="sr-meta-inline">
                                        <span class="sr-meta-chip">Op: {{ $ret->operator?->code ?? '-' }}</span>
                                        <span class="sr-meta-chip">{{ $ret->operator?->name ?? '' }}</span>
                                    </div>

                                    <div class="sr-amounts">
                                        <span>Pickup: {{ number_format($totalPickupRow, 2, ',', '.') }}</span>
                                        <span class="{{ $totalRemainingRow > 0 ? 'text-warning' : 'text-muted' }}">
                                            Belum setor: {{ number_format($totalRemainingRow, 2, ',', '.') }}
                                        </span>
                                    </div>

                                    @if ($ret->notes)
                                        <div class="mt-1 text-muted small">
                                            {{ \Illuminate\Support\Str::limit($ret->notes, 80) }}</div>
                                    @endif

                                    <div class="mt-1">
                                        <a href="{{ route('production.sewing.returns.show', $ret) }}"
                                            class="small">Lihat detail →</a>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                @if ($returns->hasPages())
                    <div class="mt-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div class="small text-muted">Halaman {{ $returns->currentPage() }} dari
                            {{ $returns->lastPage() }}</div>
                        <div>{{ $returns->links() }}</div>
                    </div>
                @endif
            </div>

        </div>
    </div>
@endsection
