{{-- resources/views/costing/production_cost_periods/show.blade.php --}}
@extends('layouts.app')

@section('title', 'Costing • Periode HPP ' . $period->code)

@push('head')
    <style>
        .page-wrap {
            max-width: 1120px;
            margin-inline: auto;
            padding-bottom: 1rem;
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

        .badge-status {
            border-radius: 999px;
            padding: .16rem .7rem;
            font-size: .72rem;
            font-weight: 600;
            letter-spacing: .05em;
        }

        .badge-status-draft {
            background: rgba(248, 180, 0, .15);
            color: #92400e;
        }

        .badge-status-posted {
            background: rgba(16, 185, 129, .15);
            color: #0f5132;
        }

        .badge-status-inactive {
            background: rgba(148, 163, 184, .18);
            color: #4b5563;
        }

        .pill {
            border-radius: 999px;
            padding: .18rem .7rem;
            border: 1px solid var(--line);
            font-size: .78rem;
        }

        .table-wrap {
            overflow-x: auto;
        }

        @media (max-width: 768px) {
            .page-wrap {
                padding-inline: .5rem;
            }

            .table-wrap {
                font-size: .85rem;
            }
        }
    </style>
@endpush

@section('content')
    @php
        // ringkasan kecil
        $itemCount = $snapshots->count();
        $totalQtyBasis = (float) $snapshots->sum('qty_basis');
        $avgHpp = $itemCount > 0 ? (float) $snapshots->avg('unit_cost') : 0.0;

        $statusBadgeClass = match ($period->status) {
            'posted' => 'badge-status-posted',
            'draft' => 'badge-status-draft',
            default => 'badge-status-inactive',
        };
    @endphp

    <div class="page-wrap">

        {{-- FLASH --}}
        @if (session('status'))
            <div class="alert alert-success alert-dismissible fade show mb-3">
                {{ session('status') }}
                <button class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        {{-- HEADER PERIODE --}}
        <div class="card p-3 mb-3">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">

                <div>
                    <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                        <h1 class="h5 mb-0">
                            Periode HPP
                            <span class="mono">{{ $period->code }}</span>
                        </h1>

                        <span class="badge-status {{ $statusBadgeClass }}">
                            {{ strtoupper($period->status ?? 'draft') }}
                        </span>

                        @if ($period->is_active)
                            <span class="badge bg-success-subtle text-success rounded-pill px-2 py-1">
                                Periode Aktif
                            </span>
                        @endif
                    </div>

                    <div class="help">
                        Nama:
                        <span class="fw-semibold">{{ $period->name }}</span><br>
                        Range:
                        <span class="mono">
                            {{ \Illuminate\Support\Carbon::parse($period->date_from)->format('d/m/Y') }}
                            —
                            {{ \Illuminate\Support\Carbon::parse($period->date_to)->format('d/m/Y') }}
                        </span>
                        · Snapshot:
                        <span class="mono">
                            {{ \Illuminate\Support\Carbon::parse($period->snapshot_date)->format('d/m/Y') }}
                        </span>
                    </div>

                    @if ($period->notes)
                        <div class="small mt-2">
                            <span class="fw-semibold">Catatan:</span>
                            {!! nl2br(e($period->notes)) !!}
                        </div>
                    @endif
                </div>

                <div class="d-flex flex-column align-items-end gap-2">

                    <div class="d-flex gap-2 flex-wrap justify-content-end">
                        <a href="{{ route('costing.production_cost_periods.index') }}"
                            class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-arrow-left me-1"></i> Kembali
                        </a>

                        <a href="{{ route('costing.production_cost_periods.edit', $period) }}"
                            class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-pencil-square me-1"></i> Edit
                        </a>

                        <form action="{{ route('costing.production_cost_periods.generate', $period) }}" method="post"
                            onsubmit="return confirm('Generate ulang HPP final untuk periode ini?\nSnapshot lama dengan reference_type=production_cost_period tetap ada, tapi yang baru akan jadi aktif.');">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-primary">
                                <i class="bi bi-calculator me-1"></i>
                                Generate HPP Final
                            </button>
                        </form>
                    </div>

                    <div class="help mt-1 text-end">
                        @if ($period->cuttingPayrollPeriod)
                            <div>
                                Cutting:
                                <span class="mono">
                                    {{ $period->cuttingPayrollPeriod->code }}
                                </span>
                            </div>
                        @endif
                        @if ($period->sewingPayrollPeriod)
                            <div>
                                Sewing:
                                <span class="mono">
                                    {{ $period->sewingPayrollPeriod->code }}
                                </span>
                            </div>
                        @endif
                        @if ($period->finishingPayrollPeriod)
                            <div>
                                Finishing:
                                <span class="mono">
                                    {{ $period->finishingPayrollPeriod->code }}
                                </span>
                            </div>
                        @endif
                    </div>

                </div>
            </div>
        </div>

        {{-- SUMMARY KECIL --}}
        <div class="card p-3 mb-3">
            <div class="d-flex flex-wrap gap-3 align-items-center justify-content-between">

                <div class="d-flex flex-wrap gap-2">
                    <div class="pill">
                        Item HPP:
                        <span class="mono ms-1">{{ $itemCount }}</span>
                    </div>
                    <div class="pill">
                        Total Qty Basis:
                        <span class="mono ms-1">
                            {{ number_format($totalQtyBasis, 2, ',', '.') }}
                        </span>
                    </div>
                    <div class="pill">
                        Rata-rata HPP/unit:
                        <span class="mono ms-1">
                            {{ number_format($avgHpp, 2, ',', '.') }}
                        </span>
                    </div>
                </div>

                <div class="help">
                    HPP final per item dihitung dari:
                    RM (snapshot RM-only finishing) +
                    Cutting +
                    Sewing +
                    Finishing +
                    Packaging +
                    Overhead.
                </div>
            </div>
        </div>

        {{-- TABEL HPP PER ITEM --}}
        <div class="card p-0 mb-4">
            <div class="px-3 pt-3 pb-2 d-flex justify-content-between">
                <div>
                    <div class="fw-semibold">Detail HPP per Item</div>
                    <div class="help">
                        Berdasarkan snapshot <code>production_cost_period</code> untuk periode ini.
                    </div>
                </div>

                <div class="help">
                    Total baris: {{ $snapshots->count() }}
                </div>
            </div>

            @if ($snapshots->isEmpty())
                <div class="px-3 pb-3">
                    <div class="help">
                        Belum ada snapshot HPP untuk periode ini.
                        Klik <strong>Generate HPP Final</strong> untuk membuat snapshot dari payroll & RM-only.
                    </div>
                </div>
            @else
                <div class="table-wrap">
                    <table class="table table-sm align-middle mono mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 80px;">Kode</th>
                                <th>Item</th>
                                <th class="text-end" style="width: 110px;">Qty Basis</th>
                                <th class="text-end" style="width: 110px;">RM</th>
                                <th class="text-end" style="width: 110px;">Cutting</th>
                                <th class="text-end" style="width: 110px;">Sewing</th>
                                <th class="text-end" style="width: 110px;">Finishing</th>
                                <th class="text-end" style="width: 110px;">Packaging</th>
                                <th class="text-end" style="width: 110px;">Overhead</th>
                                <th class="text-end" style="width: 120px;">HPP / unit</th>
                                <th style="width: 90px;">Aktif?</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($snapshots as $snap)
                                @php
                                    $rm = (float) $snap->rm_unit_cost;
                                    $cutting = (float) $snap->cutting_unit_cost;
                                    $sewing = (float) $snap->sewing_unit_cost;
                                    $finishing = (float) $snap->finishing_unit_cost;
                                    $packaging = (float) $snap->packaging_unit_cost;
                                    $overhead = (float) $snap->overhead_unit_cost;
                                    $hppUnit = (float) $snap->unit_cost;

                                    $qtyBasis = (float) $snap->qty_basis;
                                @endphp
                                <tr>
                                    <td>
                                        @if ($snap->item)
                                            @if (Route::has('items.show'))
                                                <a href="{{ route('items.show', $snap->item_id) }}">
                                                    {{ $snap->item->code }}
                                                </a>
                                            @else
                                                {{ $snap->item->code }}
                                            @endif
                                        @else
                                            ITEM-{{ $snap->item_id }}
                                        @endif
                                    </td>
                                    <td>
                                        @if ($snap->item)
                                            <div class="small fw-semibold">
                                                {{ $snap->item->name }}
                                            </div>
                                            <div class="help">
                                                {{ $snap->item->color ?? '' }}
                                            </div>
                                        @else
                                            <span class="help">Item tidak ditemukan</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        {{ number_format($qtyBasis, 2, ',', '.') }}
                                    </td>
                                    <td class="text-end">
                                        {{ number_format($rm, 2, ',', '.') }}
                                    </td>
                                    <td class="text-end">
                                        {{ number_format($cutting, 2, ',', '.') }}
                                    </td>
                                    <td class="text-end">
                                        {{ number_format($sewing, 2, ',', '.') }}
                                    </td>
                                    <td class="text-end">
                                        {{ number_format($finishing, 2, ',', '.') }}
                                    </td>
                                    <td class="text-end">
                                        {{ number_format($packaging, 2, ',', '.') }}
                                    </td>
                                    <td class="text-end">
                                        {{ number_format($overhead, 2, ',', '.') }}
                                    </td>
                                    <td class="text-end fw-semibold">
                                        {{ number_format($hppUnit, 2, ',', '.') }}
                                    </td>
                                    <td>
                                        @if ($snap->is_active)
                                            <span class="badge bg-success-subtle text-success rounded-pill px-2 py-1">
                                                Aktif
                                            </span>
                                        @else
                                            <span class="badge bg-secondary-subtle text-secondary rounded-pill px-2 py-1">
                                                Hist.
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        @if ($snapshots->isNotEmpty())
                            @php
                                $sumRm = (float) $snapshots->sum('rm_unit_cost');
                                $sumCut = (float) $snapshots->sum('cutting_unit_cost');
                                $sumSew = (float) $snapshots->sum('sewing_unit_cost');
                                $sumFin = (float) $snapshots->sum('finishing_unit_cost');
                                $sumPack = (float) $snapshots->sum('packaging_unit_cost');
                                $sumOh = (float) $snapshots->sum('overhead_unit_cost');
                                $sumHpp = (float) $snapshots->sum('unit_cost');
                                $n = max($snapshots->count(), 1);
                                $avgRm = $sumRm / $n;
                                $avgCut = $sumCut / $n;
                                $avgSew = $sumSew / $n;
                                $avgFin = $sumFin / $n;
                                $avgPack = $sumPack / $n;
                                $avgOh = $sumOh / $n;
                                $avgHppPerRow = $sumHpp / $n;
                            @endphp
                            <tfoot>
                                <tr class="table-light">
                                    <th colspan="2">RATA-RATA</th>
                                    <th></th>
                                    <th class="text-end mono">{{ number_format($avgRm, 2, ',', '.') }}</th>
                                    <th class="text-end mono">{{ number_format($avgCut, 2, ',', '.') }}</th>
                                    <th class="text-end mono">{{ number_format($avgSew, 2, ',', '.') }}</th>
                                    <th class="text-end mono">{{ number_format($avgFin, 2, ',', '.') }}</th>
                                    <th class="text-end mono">{{ number_format($avgPack, 2, ',', '.') }}</th>
                                    <th class="text-end mono">{{ number_format($avgOh, 2, ',', '.') }}</th>
                                    <th class="text-end mono fw-semibold">
                                        {{ number_format($avgHppPerRow, 2, ',', '.') }}
                                    </th>
                                    <th></th>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>
            @endif
        </div>

        {{-- FOOTNOTE --}}
        <div class="help mb-4">
            Dibuat:
            <span class="mono">
                {{ $period->created_at?->format('Y-m-d H:i') }}
            </span>
            · Diupdate:
            <span class="mono">
                {{ $period->updated_at?->format('Y-m-d H:i') }}
            </span>
        </div>
    </div>
@endsection
