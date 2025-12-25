{{-- resources/views/production/qc/index.blade.php --}}
@extends('layouts.app')

@section('title', 'Produksi • QC Overview')

@push('head')
    <style>
        .qc-overview-page {
            min-height: 100vh;
        }

        .page-wrap {
            max-width: 1100px;
            margin-inline: auto;
            padding: 1rem 1rem 3rem;
        }

        body[data-theme="light"] .page-wrap {
            background:
                radial-gradient(circle at top left,
                    rgba(59, 130, 246, 0.12) 0,
                    rgba(45, 212, 191, 0.10) 26%,
                    #f9fafb 60%);
        }

        body[data-theme="dark"] .page-wrap {
            background:
                radial-gradient(circle at top left,
                    rgba(59, 130, 246, 0.25) 0,
                    rgba(45, 212, 191, 0.15) 26%,
                    #020617 60%);
        }

        .card {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 14px;
        }

        .mono {
            font-variant-numeric: tabular-nums;
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, "Liberation Mono";
        }

        .help {
            color: var(--muted);
            font-size: .85rem;
        }

        .nav-qc .nav-link {
            border-radius: 999px;
            padding-inline: 1rem;
        }

        .table-wrap {
            overflow-x: auto;
        }

        .badge-soft {
            border-radius: 999px;
            padding: .17rem .5rem;
            font-size: .7rem;
        }

        .qc-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: .75rem;
        }

        .qc-header-title {
            display: flex;
            flex-direction: column;
            gap: .1rem;
        }

        .qc-header-title h1 {
            font-size: 1.1rem;
            margin: 0;
        }

        .qc-header-sub {
            font-size: .8rem;
            color: var(--muted);
        }

        .qc-header-stage {
            font-size: .75rem;
            color: var(--muted);
        }

        @media (max-width: 767.98px) {
            .page-wrap {
                padding-inline: .75rem;
            }

            .qc-header {
                align-items: center;
            }

            .qc-header-title {
                flex: 1;
            }

            .qc-header-title h1 {
                font-size: 1rem;
                display: flex;
                align-items: center;
                gap: .35rem;
            }

            .qc-header-sub {
                font-size: .75rem;
            }

            .qc-header-pill {
                padding: .2rem .7rem;
                border-radius: 999px;
                border: 1px solid color-mix(in srgb, var(--line) 80%, transparent 20%);
                font-size: .7rem;
                color: var(--muted);
                white-space: nowrap;
                background: color-mix(in srgb, var(--card) 90%, var(--line) 10%);
            }

            .nav-qc {
                margin-top: .75rem !important;
                gap: .35rem;
                overflow-x: auto;
                flex-wrap: nowrap;
            }

            .nav-qc .nav-link {
                font-size: .8rem;
                padding-inline: .8rem;
                padding-block: .25rem;
                white-space: nowrap;
            }
        }

        /* ============================
                                       MOBILE: QC CUTTING LIST
                                    ============================ */
        @media (max-width: 767.98px) {
            .qc-mobile-secondary {
                font-size: .75rem;
                color: var(--muted);
            }

            /* chip item */
            .chip-soft {
                display: inline-flex;
                align-items: center;
                border-radius: 999px;
                padding: .08rem .5rem;
                border: 1px solid color-mix(in srgb, var(--line) 85%, transparent 15%);
                background: color-mix(in srgb, var(--card) 90%, var(--line) 10%);
                font-size: .72rem;
                max-width: 100%;
                white-space: nowrap;
                text-overflow: ellipsis;
                overflow: hidden;
            }

            .chip-soft+.chip-soft {
                margin-left: .25rem;
            }

            .chip-soft .qty {
                opacity: .8;
                margin-left: .2rem;
            }

            .qc-mobile-list {
                display: flex;
                flex-direction: column;
                gap: .6rem;
            }

            .qc-mobile-card {
                border-radius: 16px;
                padding: .7rem .8rem;
                background:
                    radial-gradient(circle at top left,
                        color-mix(in srgb, var(--line) 18%, transparent 82%) 0,
                        color-mix(in srgb, var(--card) 94%, var(--line) 6%) 55%);
                border: 1px solid color-mix(in srgb, var(--line) 75%, transparent 25%);
                box-shadow:
                    0 10px 25px rgba(15, 23, 42, 0.18),
                    0 0 0 1px rgba(15, 23, 42, 0.03);
                cursor: pointer;
                transition: transform 90ms ease-out, box-shadow 90ms ease-out, background 120ms ease-out;
            }

            body[data-theme="dark"] .qc-mobile-card {
                box-shadow:
                    0 14px 40px rgba(0, 0, 0, 0.75),
                    0 0 0 1px rgba(15, 23, 42, 0.7);
            }

            .qc-mobile-card:hover {
                transform: translateY(-1px);
                box-shadow:
                    0 14px 32px rgba(15, 23, 42, 0.22),
                    0 0 0 1px rgba(15, 23, 42, 0.06);
            }

            .qc-mobile-card:active {
                transform: translateY(1px);
                box-shadow:
                    0 6px 16px rgba(15, 23, 42, 0.25),
                    0 0 0 1px rgba(15, 23, 42, 0.09);
            }

            .qc-mobile-card-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                gap: .5rem;
                margin-bottom: .35rem;
            }

            .qc-mobile-date-pill {
                display: inline-flex;
                align-items: center;
                border-radius: 999px;
                padding: .08rem .6rem;
                font-size: .75rem;
                font-weight: 600;
                background: color-mix(in srgb, var(--card) 92%, var(--line) 8%);
                border: 1px solid color-mix(in srgb, var(--line) 80%, transparent 20%);
            }

            .qc-mobile-status-pill {
                font-size: .7rem;
                border-radius: 999px;
                padding: .12rem .6rem;
            }

            .qc-mobile-card-body {
                display: flex;
                flex-direction: column;
                gap: .18rem;
                font-size: .78rem;
            }

            .qc-mobile-row-line {
                display: flex;
                justify-content: space-between;
                align-items: center;
                gap: .5rem;
                flex-wrap: wrap;
            }

            .qc-mobile-row-main {
                font-weight: 500;
            }

            .qc-mobile-metadata {
                font-size: .76rem;
                font-weight: 600;
            }
        }


        @media (max-width: 767.98px) {

            /* Kunci halaman di X seperti Sewing Return */
            html,
            body {
                overflow-x: hidden;
            }

            .qc-overview-page,
            .page-wrap {
                overflow-x: hidden;
            }

            /* Matikan scroll horizontal wrapper */
            .table-wrap,
            .table-responsive {
                overflow-x: visible;
            }

            /* Biar gesture fokus ke scroll atas-bawah */
            .qc-overview-page table tbody tr,
            .qc-mobile-card {
                touch-action: pan-y;
            }
        }
    </style>
@endpush

@section('content')
    <div class="qc-overview-page">
        <div class="page-wrap">

            {{-- HEADER --}}
            <div class="card p-3 mb-3">
                <div class="qc-header">
                    <div class="qc-header-title">
                        <h1>
                            QC Overview
                        </h1>
                        <div class="qc-header-sub">
                            Monitoring QC untuk Cutting, Sewing, dan Packing.
                        </div>
                    </div>

                    {{-- Info stage aktif --}}
                    <div class="d-none d-md-block qc-header-stage text-end">
                        @if ($stage === \App\Models\QcResult::STAGE_CUTTING)
                            Stage: QC Cutting
                        @elseif ($stage === \App\Models\QcResult::STAGE_SEWING)
                            Stage: QC Sewing
                        @else
                            Stage: QC Packing
                        @endif
                    </div>

                    <div class="d-block d-md-none">
                        <div class="qc-header-pill">
                            @if ($stage === \App\Models\QcResult::STAGE_CUTTING)
                                Stage: QC Cutting
                            @elseif ($stage === \App\Models\QcResult::STAGE_SEWING)
                                Stage: QC Sewing
                            @else
                                Stage: QC Packing
                            @endif
                        </div>
                    </div>
                </div>

                {{-- TAB STAGE --}}
                <ul class="nav nav-pills nav-qc mt-3">
                    <li class="nav-item">
                        <a class="nav-link {{ $stage === 'cutting' ? 'active' : '' }}"
                            href="{{ route('production.qc.index', ['stage' => 'cutting']) }}">
                            QC Cutting
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ $stage === 'sewing' ? 'active' : '' }}"
                            href="{{ route('production.qc.index', ['stage' => 'sewing']) }}">
                            QC Sewing
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ $stage === 'packing' ? 'active' : '' }}"
                            href="{{ route('production.qc.index', ['stage' => 'packing']) }}">
                            QC Packing
                        </a>
                    </li>
                </ul>
            </div>

            {{-- ISI TABEL PER STAGE --}}
            <div class="card p-3">

                {{-- =======================
                     TAB QC CUTTING
                ======================== --}}
                @if ($stage === 'cutting')
                    <h2 class="h6 mb-2">Daftar QC Cutting</h2>

                    {{-- DESKTOP VERSION (LENGKAP: tabel) --}}
                    <div class="table-wrap d-none d-md-block">
                        <table class="table table-sm align-middle mono">
                            <thead>
                                <tr>
                                    <th style="width: 40px;">#</th>
                                    <th style="width: 110px;">Tanggal</th>
                                    <th style="width: 200px;">Lot</th>
                                    <th style="width: 160px;">Bundles (Qty)</th>
                                    <th style="width: 120px;">Status</th>
                                    <th style="width: 90px;"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($records as $job)
                                    @php
                                        $totalBundles = $job->bundles->count();
                                        $totalQty = $job->bundles->sum('qty_pcs');
                                        $totalFabric = $job->bundles->sum('qty_used_fabric');

                                        $rawStatus = $job->status ?? '-';

                                        $map = [
                                            'draft' => ['DRAFT', 'secondary'],
                                            'cut' => ['CUT', 'primary'],
                                            'sent_to_qc' => ['BELUM QC', 'warning'],
                                            'qc_done' => ['QC DONE', 'success'],
                                            'qc_ok' => ['QC OK', 'success'],
                                            'qc_mixed' => ['QC MIXED', 'warning'],
                                            'qc_reject' => ['QC REJECT', 'danger'],
                                        ];

                                        $cfg = $map[$rawStatus] ?? [strtoupper($rawStatus), 'secondary'];
                                        [$statusLabel, $statusClass] = $cfg;
                                    @endphp
                                    <tr>
                                        <td>{{ $loop->iteration + ($records->currentPage() - 1) * $records->perPage() }}
                                        </td>
                                        <td>{{ $job->date?->format('Y-m-d') ?? $job->date }}</td>
                                        <td>
                                            {{ $job->lot?->item?->code ?? '-' }}
                                            @if ($job->lot)
                                                <span class="badge-soft bg-light border text-muted">
                                                    {{ $job->lot->code }}
                                                </span>
                                            @endif
                                        </td>
                                        <td>
                                            {{ $totalBundles }} bundle /
                                            {{ number_format($totalQty, 2, ',', '.') }} pcs
                                        </td>
                                        <td>
                                            <span class="badge bg-{{ $statusClass }}">
                                                {{ $statusLabel }}
                                            </span>
                                        </td>
                                        <td>
                                            <a href="{{ route('production.qc.cutting.edit', $job) }}"
                                                class="btn btn-sm btn-outline-primary">
                                                QC
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted small">
                                            Belum ada data QC Cutting.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{-- MOBILE VERSION: LIST 3 BARIS --}}
                    <div class="d-block d-md-none">
                        @if ($records->isEmpty())
                            <div class="text-center text-muted small py-2">
                                Belum ada data QC Cutting.
                            </div>
                        @else
                            <div class="qc-mobile-list mono">
                                @foreach ($records as $job)
                                    @php
                                        $totalBundles = $job->bundles->count();
                                        $totalQty = $job->bundles->sum('qty_pcs');
                                        $totalFabric = $job->bundles->sum('qty_used_fabric');

                                        $rawStatus = $job->status ?? '-';

                                        $map = [
                                            'draft' => ['Draft', 'secondary'],
                                            'cut' => ['Cut', 'primary'],
                                            'sent_to_qc' => ['Belum QC', 'warning'],
                                            'qc_done' => ['QC Done', 'success'],
                                            'qc_ok' => ['QC OK', 'success'],
                                            'qc_mixed' => ['QC Mixed', 'warning'],
                                            'qc_reject' => ['QC Reject', 'danger'],
                                        ];

                                        $cfg = $map[$rawStatus] ?? [ucfirst($rawStatus), 'secondary'];
                                        [$statusLabel, $statusClass] = $cfg;

                                        $finishedSummary = $job->bundles
                                            ->groupBy('finished_item_id')
                                            ->map(function ($group) {
                                                $bundle = $group->first();
                                                $item = $bundle?->finishedItem;
                                                return [
                                                    'code' => $item?->code ?? '-',
                                                    'qty' => $group->sum('qty_pcs'),
                                                ];
                                            })
                                            ->values();
                                    @endphp

                                    <div class="qc-mobile-card"
                                        data-href="{{ route('production.qc.cutting.edit', $job) }}">
                                        {{-- Baris 1: TANGGAL --}}
                                        <div class="qc-mobile-card-header">
                                            <div class="qc-mobile-date-pill">
                                                {{ $job->date?->format('Y-m-d') ?? $job->date }}
                                            </div>
                                        </div>

                                        <div class="qc-mobile-card-body">
                                            {{-- Baris 2: KAIN (KG)   - STATUS --}}
                                            <div class="qc-mobile-row-line">
                                                <div>
                                                    @if ($job->lot && $job->lot->item)
                                                        <span class="chip-soft">
                                                            {{ $job->lot->item->code }}
                                                            @if ($totalFabric > 0)
                                                                <span class="qty">
                                                                    ({{ number_format($totalFabric, 2, ',', '.') }} kg)
                                                                </span>
                                                            @endif
                                                        </span>
                                                    @else
                                                        <span class="qc-mobile-secondary">Kain tidak diketahui</span>
                                                    @endif
                                                </div>
                                                <div>
                                                    <span class="badge qc-mobile-status-pill bg-warning text-white">
                                                        {{ $statusLabel }}
                                                    </span>
                                                </div>
                                            </div>

                                            {{-- Baris 3: KODE ITEM JADI (PCS)   - JUMLAH IKET --}}
                                            <div class="qc-mobile-row-line">
                                                <div>
                                                    @if ($finishedSummary->isEmpty())
                                                        <span class="qc-mobile-secondary">Tidak ada item jadi</span>
                                                    @else
                                                        @php
                                                            $fi = $finishedSummary->first();
                                                        @endphp
                                                        <span class="chip-soft">
                                                            {{ $fi['code'] }}
                                                            <span class="qty">
                                                                ({{ number_format($fi['qty'], 0, ',', '.') }} pcs)
                                                            </span>
                                                        </span>
                                                    @endif
                                                </div>
                                                <div class="qc-mobile-metadata text-muted">
                                                    {{ $totalBundles }} iket •
                                                    {{ number_format($totalQty, 0, ',', '.') }} pcs
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            @if ($records instanceof \Illuminate\Pagination\AbstractPaginator)
                                <div class="mt-2">
                                    {{ $records->links() }}
                                </div>
                            @endif
                        @endif
                    </div>

                    @if ($records instanceof \Illuminate\Pagination\AbstractPaginator && !request()->ajax())
                        <div class="d-none d-md-block mt-2">
                            {{ $records->links() }}
                        </div>
                    @endif

                    {{-- =======================
                     TAB QC SEWING
                ======================== --}}
                @elseif ($stage === 'sewing')
                    <h2 class="h6 mb-2">Daftar QC Sewing</h2>

                    <div class="table-wrap">
                        <table class="table table-sm align-middle mono">
                            <thead>
                                <tr>
                                    <th style="width: 40px;">#</th>
                                    <th style="width: 130px;">Return Code</th>
                                    <th style="width: 110px;">Tanggal</th>
                                    <th style="width: 140px;">Pickup Code</th>
                                    <th style="width: 160px;">Gudang Sewing</th>
                                    <th style="width: 170px;">Operator Jahit</th>
                                    <th style="width: 130px;">Bundles</th>
                                    <th style="width: 130px;">Qty OK</th>
                                    <th style="width: 130px;">Qty Reject</th>
                                    <th style="width: 110px;">Status</th>
                                    <th style="width: 90px;"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($records as $ret)
                                    @php
                                        $lines = $ret->lines;
                                        $totalBundles = $lines->count();
                                        $qtyOk = $lines->sum('qty_ok');
                                        $qtyReject = $lines->sum('qty_reject');

                                        $firstLine = $lines->first();
                                        $pickupLine = $firstLine?->pickupLine;
                                        $pickup = $pickupLine?->pickup;
                                        $warehouse = $pickup?->warehouse;

                                        $statusMap = [
                                            'draft' => ['DRAFT', 'secondary'],
                                            'posted' => ['POSTED', 'primary'],
                                            'closed' => ['CLOSED', 'success'],
                                        ];
                                        $cfg = $statusMap[$ret->status] ?? [
                                            strtoupper($ret->status ?? '-'),
                                            'secondary',
                                        ];
                                    @endphp
                                    <tr>
                                        <td>{{ $loop->iteration + ($records->currentPage() - 1) * $records->perPage() }}
                                        </td>
                                        <td>{{ $ret->code }}</td>
                                        <td>{{ $ret->date?->format('Y-m-d') ?? $ret->date }}</td>
                                        <td>{{ $pickup?->code ?? '-' }}</td>
                                        <td>
                                            {{ $warehouse?->code ?? '-' }}
                                            @if ($warehouse)
                                                <span class="badge-soft bg-light border text-muted">
                                                    {{ $warehouse->name }}
                                                </span>
                                            @endif
                                        </td>
                                        <td>
                                            @if ($ret->operator)
                                                {{ $ret->operator->code }} — {{ $ret->operator->name }}
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td>{{ $totalBundles }} bundle</td>
                                        <td>{{ number_format($qtyOk, 2, ',', '.') }}</td>
                                        <td>{{ number_format($qtyReject, 2, ',', '.') }}</td>
                                        <td>
                                            <span class="badge bg-{{ $cfg[1] }}">
                                                {{ $cfg[0] }}
                                            </span>
                                        </td>
                                        <td>
                                            @if (Route::has('production.sewing_returns.show'))
                                                <a href="{{ route('production.sewing_returns.show', $ret) }}"
                                                    class="btn btn-sm btn-outline-primary">
                                                    Detail
                                                </a>
                                            @elseif ($pickup && Route::has('production.sewing_pickups.show'))
                                                <a href="{{ route('production.sewing_pickups.show', $pickup) }}"
                                                    class="btn btn-sm btn-outline-primary">
                                                    Pickup
                                                </a>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="11" class="text-center text-muted small">
                                            Belum ada data QC Sewing.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if ($records instanceof \Illuminate\Pagination\AbstractPaginator)
                        <div class="mt-2">
                            {{ $records->links() }}
                        </div>
                    @endif

                    {{-- =======================
                     TAB QC PACKING
                ======================== --}}
                @elseif ($stage === 'packing')
                    <h2 class="h6 mb-2">Daftar QC Packing</h2>
                    <p class="small text-muted mb-0">
                        Modul QC Packing belum diimplementasikan. Nanti bisa mengikuti pola QC Sewing dengan model
                        <code>PackingReturn</code> atau sejenisnya.
                    </p>
                @endif
            </div>

        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // MOBILE: Klik card → masuk ke halaman input QC Cutting
            const cards = document.querySelectorAll('.qc-mobile-card');
            cards.forEach(card => {
                card.addEventListener('click', function() {
                    const href = this.getAttribute('data-href');
                    if (href) {
                        window.location.href = href;
                    }
                });
            });
        });
    </script>
@endpush
