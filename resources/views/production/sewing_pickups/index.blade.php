@extends('layouts.app')

@section('title', 'Produksi • Sewing Pickup')

@push('head')
    <style>
        .sewing-pickup-page {
            min-height: 100vh;
        }

        .page-wrap {
            max-width: 1100px;
            margin-inline: auto;
            padding: 1rem 1rem 3.5rem;
        }

        body[data-theme="light"] .page-wrap {
            background:
                radial-gradient(circle at top left,
                    rgba(59, 130, 246, 0.12) 0,
                    rgba(45, 212, 191, 0.08) 28%,
                    #f9fafb 60%);
        }

        body[data-theme="dark"] .page-wrap {
            background:
                radial-gradient(circle at top left,
                    rgba(59, 130, 246, 0.25) 0,
                    rgba(45, 212, 191, 0.15) 26%,
                    #020617 60%);
        }

        .card-main {
            background: var(--card);
            border-radius: 14px;
            border: 1px solid var(--line);
            box-shadow:
                0 12px 30px rgba(15, 23, 42, 0.12),
                0 0 0 1px rgba(148, 163, 184, 0.08);
        }

        body[data-theme="dark"] .card-main {
            border-color: rgba(30, 64, 175, 0.55);
            box-shadow:
                0 16px 40px rgba(0, 0, 0, 0.78),
                0 0 0 1px rgba(15, 23, 42, 0.8);
        }

        .mono {
            font-variant-numeric: tabular-nums;
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas;
        }

        .help {
            color: var(--muted);
            font-size: .82rem;
        }

        .table-wrap {
            overflow-x: auto;
        }

        .header-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: .75rem;
            flex-wrap: wrap;
        }

        .header-main {
            min-width: 0;
        }

        .header-actions {
            display: flex;
            gap: .4rem;
        }

        /* Mini summary */
        .summary-row {
            display: flex;
            flex-wrap: wrap;
            gap: .35rem;
            margin-top: .3rem;
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

        .pickup-row {
            cursor: pointer;
            transition: background-color .12s ease, box-shadow .12s ease;
        }

        .pickup-row:hover {
            background: color-mix(in srgb, var(--card) 82%, #0d6efd 6%);
            box-shadow: 0 0 0 1px rgba(148, 163, 184, 0.45);
        }

        .table-pickups thead th {
            border-bottom-width: 1px;
            font-size: .75rem;
            text-transform: uppercase;
            letter-spacing: .06em;
            color: #6b7280;
            background: rgba(15, 23, 42, 0.02);
        }

        .table-pickups tbody td {
            vertical-align: middle;
            border-top-color: rgba(148, 163, 184, 0.18);
        }

        @media (max-width: 767.98px) {
            .page-wrap {
                padding-inline: .75rem;
                padding-bottom: 4.5rem;
            }

            .header-row {
                flex-direction: column;
                align-items: stretch;
            }

            .header-actions {
                width: 100%;
            }

            .header-actions a {
                flex: 1;
                justify-content: center;
            }
        }

        /* ================= MOBILE CARD LIST ================= */
        @media (max-width: 767.98px) {
            .pickup-mobile-list {
                display: flex;
                flex-direction: column;
                gap: .6rem;
            }

            .pickup-mobile-card {
                border-radius: 16px;
                padding: .7rem .8rem;
                background:
                    radial-gradient(circle at top left,
                        rgba(148, 163, 184, 0.22) 0,
                        color-mix(in srgb, var(--card) 92%, var(--line) 8%) 52%);
                border: 1px solid color-mix(in srgb, var(--line) 75%, transparent 25%);
                box-shadow:
                    0 10px 25px rgba(15, 23, 42, 0.18),
                    0 0 0 1px rgba(15, 23, 42, 0.03);
                cursor: pointer;
                transition: transform 90ms ease-out, box-shadow 90ms ease-out, background 120ms ease-out;
            }

            body[data-theme="dark"] .pickup-mobile-card {
                box-shadow:
                    0 14px 40px rgba(0, 0, 0, 0.78),
                    0 0 0 1px rgba(15, 23, 42, 0.7);
            }

            .pickup-mobile-card:hover {
                transform: translateY(-1px);
                box-shadow:
                    0 14px 32px rgba(15, 23, 42, 0.22),
                    0 0 0 1px rgba(15, 23, 42, 0.06);
            }

            .pickup-mobile-card:active {
                transform: translateY(1px);
                box-shadow:
                    0 6px 16px rgba(15, 23, 42, 0.25),
                    0 0 0 1px rgba(15, 23, 42, 0.09);
            }

            .pickup-mobile-top {
                display: flex;
                justify-content: space-between;
                align-items: flex-start;
                gap: .5rem;
                margin-bottom: .25rem;
            }

            .pickup-mobile-code {
                font-weight: 700;
                font-size: .9rem;
            }

            .pickup-mobile-date-pill {
                font-size: .75rem;
                border-radius: 999px;
                padding: .08rem .55rem;
                background: color-mix(in srgb, var(--card) 92%, var(--line) 8%);
                border: 1px solid color-mix(in srgb, var(--line) 80%, transparent 20%);
            }

            .pickup-mobile-status-badge {
                font-size: .7rem;
                padding: .08rem .45rem;
                border-radius: 999px;
            }

            .pickup-mobile-middle {
                font-size: .78rem;
                color: var(--muted);
                margin-bottom: .15rem;
            }

            .pickup-mobile-middle span.mono {
                font-size: .8rem;
            }

            .pickup-mobile-bottom {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-top: .1rem;
                font-size: .8rem;
            }

            .btn-detail-mobile {
                padding-block: .2rem;
                padding-inline: .6rem;
                font-size: .78rem;
                border-radius: 999px;
            }
        }
    </style>
@endpush

@section('content')
    <div class="sewing-pickup-page">
        <div class="page-wrap py-3 py-md-4">

            @php
                // mini summary (berdasarkan data di halaman ini)
                $totalBundlesPage = 0;
                $totalQtyPage = 0;
                $todayPickups = 0;
                $todayDate = now()->toDateString();

                foreach ($pickups as $p) {
                    $totalBundlesPage += $p->lines->count();
                    $totalQtyPage += $p->lines->sum('qty_bundle');
                    if (optional($p->date)?->format('Y-m-d') === $todayDate) {
                        $todayPickups++;
                    }
                }

                $totalPickups =
                    $pickups instanceof \Illuminate\Pagination\AbstractPaginator
                        ? $pickups->total()
                        : $pickups->count();
            @endphp

            {{-- HEADER CARD --}}
            <div class="card-main p-3 mb-3">
                <div class="header-row">
                    <div class="header-main">
                        <h1 class="h5 mb-0">Sewing Pickup</h1>

                        @if ($totalPickups > 0)
                            <div class="summary-row">
                                <span class="summary-pill mono">
                                    {{ number_format($totalPickups, 0, ',', '.') }} pickup
                                </span>
                                <span class="summary-pill mono">
                                    {{ number_format($totalBundlesPage, 0, ',', '.') }} bundle
                                </span>
                                <span class="summary-pill mono">
                                    {{ number_format($totalQtyPage, 2, ',', '.') }} pcs
                                </span>
                                @if ($todayPickups > 0)
                                    <span class="summary-pill summary-pill-accent mono">
                                        {{ number_format($todayPickups, 0, ',', '.') }} hari ini
                                    </span>
                                @endif
                            </div>
                        @else
                            <div class="help mt-1">
                                Belum ada sewing pickup tercatat.
                            </div>
                        @endif
                    </div>

                    <div class="header-actions">
                        <a href="{{ route('production.sewing_pickups.bundles_ready') }}"
                            class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center justify-content-center">
                            Bundles Ready
                        </a>
                        <a href="{{ route('production.sewing_pickups.create') }}"
                            class="btn btn-sm btn-primary d-inline-flex align-items-center justify-content-center">
                            + Sewing Pickup
                        </a>
                    </div>
                </div>
            </div>

            {{-- LIST CARD --}}
            <div class="card-main p-3">
                <h2 class="h6 mb-2">Daftar Sewing Pickup</h2>

                {{-- DESKTOP: TABEL --}}
                <div class="table-wrap d-none d-md-block">
                    <table class="table table-sm align-middle mono table-pickups mb-0">
                        <thead>
                            <tr>
                                <th style="width: 40px;">#</th>
                                <th style="width: 130px;">Code</th>
                                <th style="width: 100px;">Tanggal</th>
                                <th style="width: 170px;">Operator</th>
                                <th style="width: 150px;">Bundle / Qty</th>
                                <th style="width: 110px;">Status</th>
                                <th style="width: 90px;"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($pickups as $pickup)
                                @php
                                    $totalBundlesPickup = $pickup->lines->count();
                                    $totalQtyPickup = $pickup->lines->sum('qty_bundle');

                                    $statusMap = [
                                        'draft' => ['label' => 'DRAFT', 'class' => 'secondary'],
                                        'posted' => ['label' => 'POSTED', 'class' => 'primary'],
                                        'closed' => ['label' => 'CLOSED', 'class' => 'success'],
                                    ];

                                    $cfg = $statusMap[$pickup->status] ?? [
                                        'label' => strtoupper($pickup->status ?? '-'),
                                        'class' => 'secondary',
                                    ];

                                    $showUrl = Route::has('production.sewing_pickups.show')
                                        ? route('production.sewing_pickups.show', $pickup)
                                        : null;
                                @endphp
                                <tr class="pickup-row"
                                    @if ($showUrl) data-href="{{ $showUrl }}" @endif>
                                    <td>
                                        {{ $loop->iteration + ($pickups->currentPage() - 1) * $pickups->perPage() }}
                                    </td>
                                    <td>
                                        {{ $pickup->code }}
                                    </td>
                                    <td>
                                        {{ $pickup->date?->format('Y-m-d') ?? $pickup->date }}
                                    </td>
                                    <td>
                                        @if ($pickup->operator)
                                            {{ $pickup->operator->code }} — {{ $pickup->operator->name }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td>
                                        {{ $totalBundlesPickup }} bundle /
                                        {{ number_format($totalQtyPickup, 2, ',', '.') }} pcs
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $cfg['class'] }}">
                                            {{ $cfg['label'] }}
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        @if ($showUrl)
                                            <a href="{{ $showUrl }}" class="btn btn-sm btn-outline-primary">
                                                Detail
                                            </a>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted small">
                                        Belum ada Sewing Pickup.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- MOBILE: CARD LIST --}}
                <div class="d-block d-md-none mono">
                    @if ($pickups->isEmpty())
                        <div class="text-center text-muted small py-3">
                            Belum ada Sewing Pickup.
                        </div>
                    @else
                        <div class="pickup-mobile-list">
                            @foreach ($pickups as $pickup)
                                @php
                                    $totalBundlesPickup = $pickup->lines->count();
                                    $totalQtyPickup = $pickup->lines->sum('qty_bundle');

                                    $statusMap = [
                                        'draft' => ['label' => 'Draft', 'class' => 'secondary'],
                                        'posted' => ['label' => 'Posted', 'class' => 'primary'],
                                        'closed' => ['label' => 'Closed', 'class' => 'success'],
                                    ];

                                    $cfg = $statusMap[$pickup->status] ?? [
                                        'label' => ucfirst($pickup->status ?? '-'),
                                        'class' => 'secondary',
                                    ];

                                    $showUrl = Route::has('production.sewing_pickups.show')
                                        ? route('production.sewing_pickups.show', $pickup)
                                        : null;
                                @endphp

                                <div class="pickup-mobile-card"
                                    @if ($showUrl) data-href="{{ $showUrl }}" @endif>
                                    <div class="pickup-mobile-top">
                                        <div>
                                            <div class="pickup-mobile-code">
                                                {{ $pickup->code }}
                                            </div>
                                            <div class="pickup-mobile-date-pill">
                                                {{ $pickup->date?->format('Y-m-d') ?? $pickup->date }}
                                            </div>
                                        </div>
                                        <div>
                                            <span class="badge pickup-mobile-status-badge bg-{{ $cfg['class'] }}">
                                                {{ $cfg['label'] }}
                                            </span>
                                        </div>
                                    </div>

                                    <div class="pickup-mobile-middle">
                                        @if ($pickup->operator)
                                            <span class="mono">{{ $pickup->operator->code }}</span>
                                            <span>— {{ $pickup->operator->name }}</span>
                                        @else
                                            <span class="text-muted">Operator: -</span>
                                        @endif
                                    </div>

                                    <div class="pickup-mobile-bottom">
                                        <div>
                                            <span class="mono">
                                                {{ $totalBundlesPickup }} bundle /
                                                {{ number_format($totalQtyPickup, 2, ',', '.') }} pcs
                                            </span>
                                        </div>

                                        @if ($showUrl)
                                            <a href="{{ $showUrl }}"
                                                class="btn btn-sm btn-outline-primary btn-detail-mobile"
                                                onclick="event.stopPropagation();">
                                                Detail
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                @if ($pickups instanceof \Illuminate\Pagination\AbstractPaginator)
                    <div class="mt-2">
                        {{ $pickups->links() }}
                    </div>
                @endif
            </div>

        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // DESKTOP: klik baris tabel
            document.querySelectorAll('.pickup-row[data-href]').forEach(function(row) {
                row.addEventListener('click', function(e) {
                    if (e.target.closest('a, button')) {
                        return;
                    }
                    const url = row.dataset.href;
                    if (url) {
                        window.location.href = url;
                    }
                });
            });

            // MOBILE: klik card
            document.querySelectorAll('.pickup-mobile-card[data-href]').forEach(function(card) {
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
