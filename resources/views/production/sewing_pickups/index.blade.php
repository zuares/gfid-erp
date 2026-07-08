{{-- resources/views/production/sewing_pickups/index.blade.php --}}
@extends('layouts.app')

@section('title', 'Produksi • Sewing Pickup')

@push('head')
    <style>
        .sewing-pickup-page { min-height: 100vh; }

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

        .mono { font-variant-numeric: tabular-nums; font-family: ui-monospace, SFMono-Regular, Menlo, Consolas; }
        .help { color: var(--muted); font-size: .82rem; }
        .table-wrap { overflow-x: auto; }

        .header-row { display: flex; justify-content: space-between; align-items: center; gap: .75rem; flex-wrap: wrap; }
        .header-main { min-width: 0; }
        .header-actions { display: flex; gap: .4rem; }

        .summary-row { display: flex; flex-wrap: wrap; gap: .35rem; margin-top: .3rem; }
        .summary-pill { border-radius: 999px; padding: .12rem .6rem; font-size: .78rem; background: rgba(148, 163, 184, 0.14); color: var(--muted); }
        .summary-pill-accent { background: rgba(13, 110, 253, 0.12); color: #0d6efd; }

        /* ===== chip item (detail barang) ===== */
        .item-chips { display: flex; flex-wrap: wrap; gap: .25rem; }
        .item-chip {
            display: inline-flex; align-items: baseline; gap: .25rem;
            font-size: .72rem; line-height: 1.1; padding: .2rem .45rem;
            border-radius: 999px; background: rgba(148, 163, 184, 0.14);
            border: 1px solid rgba(148, 163, 184, 0.18); white-space: nowrap;
        }
        .item-chip b { font-weight: 700; letter-spacing: .02em; }
        .item-chip .q { color: var(--muted); }
        .item-chip-more { background: transparent; color: var(--muted); }

        /* ===== progress bar ===== */
        .prog-wrap { min-width: 150px; }
        .prog {
            height: 6px; border-radius: 999px; overflow: hidden;
            background: rgba(148, 163, 184, 0.22);
        }
        .prog > span { display: block; height: 100%; border-radius: 999px; transition: width .3s ease; }
        .fill-done { background: linear-gradient(90deg, #16a34a, #22c55e); }
        .fill-part { background: linear-gradient(90deg, #2563eb, #38bdf8); }
        .fill-zero { background: rgba(148, 163, 184, 0.5); }
        .prog-num { font-size: .72rem; color: var(--muted); margin-top: .25rem; display: flex; justify-content: space-between; gap: .5rem; }
        .prog-num b { color: inherit; font-weight: 700; }

        .pickup-row { cursor: pointer; transition: background-color .12s ease, box-shadow .12s ease; }
        .pickup-row:hover {
            background: color-mix(in srgb, var(--card) 82%, #0d6efd 6%);
            box-shadow: 0 0 0 1px rgba(148, 163, 184, 0.45);
        }
        .table-pickups thead th {
            border-bottom-width: 1px; font-size: .75rem; text-transform: uppercase;
            letter-spacing: .06em; color: #6b7280; background: rgba(15, 23, 42, 0.02);
        }
        .table-pickups tbody td { vertical-align: middle; border-top-color: rgba(148, 163, 184, 0.18); }

        @media (max-width: 767.98px) {
            .page-wrap { padding-inline: .75rem; padding-bottom: 4.5rem; }
            .header-row { flex-direction: column; align-items: stretch; }
            .header-actions { width: 100%; }
            .header-actions a { flex: 1; justify-content: center; }

            .pickup-mobile-list { display: flex; flex-direction: column; gap: .6rem; }
            .pickup-mobile-card {
                border-radius: 16px; padding: .75rem .85rem;
                background:
                    radial-gradient(circle at top left,
                        rgba(148, 163, 184, 0.22) 0,
                        color-mix(in srgb, var(--card) 92%, var(--line) 8%) 52%);
                border: 1px solid color-mix(in srgb, var(--line) 75%, transparent 25%);
                box-shadow: 0 10px 25px rgba(15, 23, 42, 0.18), 0 0 0 1px rgba(15, 23, 42, 0.03);
                cursor: pointer;
                transition: transform 90ms ease-out, box-shadow 90ms ease-out;
            }
            body[data-theme="dark"] .pickup-mobile-card {
                box-shadow: 0 14px 40px rgba(0, 0, 0, 0.78), 0 0 0 1px rgba(15, 23, 42, 0.7);
            }
            .pickup-mobile-card:active { transform: translateY(1px); }

            .pickup-mobile-top { display: flex; justify-content: space-between; align-items: flex-start; gap: .5rem; margin-bottom: .3rem; }
            .pickup-mobile-code { font-weight: 700; font-size: .92rem; }
            .pickup-mobile-date-pill {
                font-size: .74rem; border-radius: 999px; padding: .08rem .55rem;
                background: color-mix(in srgb, var(--card) 92%, var(--line) 8%);
                border: 1px solid color-mix(in srgb, var(--line) 80%, transparent 20%);
            }
            .pickup-mobile-status-badge { font-size: .7rem; padding: .12rem .5rem; border-radius: 999px; }
            .pickup-mobile-middle { font-size: .78rem; color: var(--muted); margin-bottom: .4rem; }
            .pickup-mobile-bottom { display: flex; justify-content: space-between; align-items: center; margin-top: .5rem; font-size: .8rem; }
            .btn-detail-mobile { padding-block: .2rem; padding-inline: .6rem; font-size: .78rem; border-radius: 999px; }
        }
    </style>
@endpush

@section('content')
    @php
        // ===== helper kalkulasi per-pickup (dipakai desktop & mobile) =====
        $calc = function ($pickup) {
            $lines = $pickup->lines ?? collect();
            $totalQty = (float) $lines->sum('qty_bundle');
            $doneQty = (float) $lines->sum(function ($l) {
                return (float) ($l->qty_returned_ok ?? 0)
                    + (float) ($l->qty_returned_reject ?? 0)
                    + (float) ($l->qty_direct_picked ?? 0)
                    + (float) ($l->qty_progress_adjusted ?? 0)
                    // ✅ qty_closed = qty yang sudah di-cancel/settle/write-off via WIP cleanup;
                    // dianggap tuntas agar pickup tidak tampil "sisa" padahal sudah dibatalkan.
                    + (float) ($l->qty_closed ?? 0);
            });
            $doneQty = min($doneQty, $totalQty);
            $remaining = max($totalQty - $doneQty, 0);
            $pct = $totalQty > 0 ? (int) min(100, round($doneQty / $totalQty * 100)) : 0;

            $items = $lines
                ->groupBy(fn($l) => optional($l->finishedItem)->code ?: '—')
                ->map(fn($g) => [
                    'code' => optional($g->first()->finishedItem)->code ?: '—',
                    'name' => optional($g->first()->finishedItem)->name ?: '',
                    'qty' => (float) $g->sum('qty_bundle'),
                ])
                ->sortByDesc('qty')
                ->values();

            return [
                'bundles' => $lines->count(),
                'total_qty' => $totalQty,
                'done_qty' => $doneQty,
                'remaining' => $remaining,
                'pct' => $pct,
                'items' => $items,
            ];
        };

        $statusMap = [
            'draft' => ['label' => 'Draft', 'class' => 'secondary'],
            'partial' => ['label' => 'Partial', 'class' => 'warning'],
            'completed' => ['label' => 'Completed', 'class' => 'success'],
            'posted' => ['label' => 'Posted', 'class' => 'primary'],
            'closed' => ['label' => 'Closed', 'class' => 'success'],
        ];

        $fillClass = fn($pct) => $pct >= 100 ? 'fill-done' : ($pct > 0 ? 'fill-part' : 'fill-zero');
    @endphp

    <div class="sewing-pickup-page">
        <div class="page-wrap py-3 py-md-4">

            @php
                $totalBundlesPage = 0;
                $totalQtyPage = 0;
                $todayPickups = 0;
                $todayDate = now()->toDateString();

                foreach ($pickups as $p) {
                    $totalBundlesPage += $p->lines?->count() ?? 0;
                    $totalQtyPage += (float) ($p->lines?->sum('qty_bundle') ?? 0);
                    if (($p->date?->format('Y-m-d') ?? (string) $p->date) === $todayDate) {
                        $todayPickups++;
                    }
                }

                $totalPickups = $pickups instanceof \Illuminate\Pagination\AbstractPaginator
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
                                <span class="summary-pill mono">{{ number_format($totalPickups, 0, ',', '.') }} pickup</span>
                                <span class="summary-pill mono">{{ number_format($totalBundlesPage, 0, ',', '.') }} bundle</span>
                                <span class="summary-pill mono">{{ number_format($totalQtyPage, 0, ',', '.') }} pcs</span>
                                @if ($todayPickups > 0)
                                    <span class="summary-pill summary-pill-accent mono">{{ number_format($todayPickups, 0, ',', '.') }} hari ini</span>
                                @endif
                            </div>
                        @else
                            <div class="help mt-1">Belum ada sewing pickup tercatat.</div>
                        @endif
                    </div>

                    <div class="header-actions">
                        <a href="{{ route('production.sewing.pickups.bundles_ready') }}"
                            class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center justify-content-center">
                            Bundles Ready
                        </a>
                        <a href="{{ route('production.sewing.pickups.create') }}"
                            class="btn btn-sm btn-primary d-inline-flex align-items-center justify-content-center">
                            + Sewing Pickup
                        </a>
                    </div>
                </div>
            </div>

            {{-- LIST CARD --}}
            <div class="card-main p-3">
                <h2 class="h6 mb-2">Daftar Sewing Pickup</h2>

                {{-- DESKTOP: TABLE --}}
                <div class="table-wrap d-none d-md-block">
                    <table class="table table-sm align-middle table-pickups mb-0">
                        <thead>
                            <tr>
                                <th style="width: 40px;">#</th>
                                <th style="width: 150px;">Pickup</th>
                                <th style="width: 170px;">Operator</th>
                                <th>Barang</th>
                                <th style="width: 190px;">Progress Setor</th>
                                <th style="width: 96px;">Status</th>
                                <th style="width: 80px;"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($pickups as $pickup)
                                @php
                                    $c = $calc($pickup);
                                    $st = (string) ($pickup->status ?? 'draft');
                                    $cfg = $statusMap[$st] ?? ['label' => ucfirst($st ?: '-'), 'class' => 'secondary'];
                                    $showUrl = route('production.sewing.pickups.show', $pickup);
                                @endphp

                                <tr class="pickup-row" data-href="{{ $showUrl }}">
                                    <td class="mono">
                                        @if ($pickups instanceof \Illuminate\Pagination\AbstractPaginator)
                                            {{ $loop->iteration + ($pickups->currentPage() - 1) * $pickups->perPage() }}
                                        @else
                                            {{ $loop->iteration }}
                                        @endif
                                    </td>
                                    <td>
                                        <div class="fw-semibold mono">{{ $pickup->code }}</div>
                                        <div class="help mono">{{ $pickup->date?->format('d M Y') ?? $pickup->date }}</div>
                                    </td>
                                    <td>
                                        @if ($pickup->operator)
                                            <span class="mono">{{ $pickup->operator->code }}</span><br>
                                            <span class="help">{{ $pickup->operator->name }}</span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($c['items']->isEmpty())
                                            <span class="text-muted small">-</span>
                                        @else
                                            <div class="item-chips">
                                                @foreach ($c['items']->take(4) as $it)
                                                    <span class="item-chip" title="{{ $it['name'] }}">
                                                        <b>{{ $it['code'] }}</b>
                                                        <span class="q mono">{{ number_format($it['qty'], 0, ',', '.') }}</span>
                                                    </span>
                                                @endforeach
                                                @if ($c['items']->count() > 4)
                                                    <span class="item-chip item-chip-more mono">+{{ $c['items']->count() - 4 }}</span>
                                                @endif
                                            </div>
                                            <div class="help mt-1 mono">{{ number_format($c['bundles'], 0, ',', '.') }} bundle</div>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="prog-wrap">
                                            <div class="prog">
                                                <span class="{{ $fillClass($c['pct']) }}" style="width: {{ $c['pct'] }}%"></span>
                                            </div>
                                            <div class="prog-num mono">
                                                <span><b>{{ $c['pct'] }}%</b></span>
                                                <span>{{ number_format($c['done_qty'], 0, ',', '.') }}/{{ number_format($c['total_qty'], 0, ',', '.') }}</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $cfg['class'] }}">{{ $cfg['label'] }}</span>
                                    </td>
                                    <td class="text-end">
                                        <a href="{{ $showUrl }}" class="btn btn-sm btn-outline-primary"
                                            onclick="event.stopPropagation();">Detail</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted small py-3">Belum ada Sewing Pickup.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- MOBILE: CARD LIST --}}
                <div class="d-block d-md-none">
                    @if ($pickups->count() === 0)
                        <div class="text-center text-muted small py-3">Belum ada Sewing Pickup.</div>
                    @else
                        <div class="pickup-mobile-list">
                            @foreach ($pickups as $pickup)
                                @php
                                    $c = $calc($pickup);
                                    $st = (string) ($pickup->status ?? 'draft');
                                    $cfg = $statusMap[$st] ?? ['label' => ucfirst($st ?: '-'), 'class' => 'secondary'];
                                    $showUrl = route('production.sewing.pickups.show', $pickup);
                                @endphp

                                <div class="pickup-mobile-card" data-href="{{ $showUrl }}">
                                    <div class="pickup-mobile-top">
                                        <div>
                                            <div class="pickup-mobile-code mono">{{ $pickup->code }}</div>
                                            <div class="pickup-mobile-date-pill mono">
                                                {{ $pickup->date?->format('d M Y') ?? $pickup->date }}
                                            </div>
                                        </div>
                                        <span class="badge pickup-mobile-status-badge bg-{{ $cfg['class'] }}">{{ $cfg['label'] }}</span>
                                    </div>

                                    <div class="pickup-mobile-middle">
                                        @if ($pickup->operator)
                                            <span class="mono">{{ $pickup->operator->code }}</span>
                                            <span>— {{ $pickup->operator->name }}</span>
                                        @else
                                            <span class="text-muted">Operator: -</span>
                                        @endif
                                    </div>

                                    @if ($c['items']->isNotEmpty())
                                        <div class="item-chips">
                                            @foreach ($c['items']->take(3) as $it)
                                                <span class="item-chip" title="{{ $it['name'] }}">
                                                    <b>{{ $it['code'] }}</b>
                                                    <span class="q mono">{{ number_format($it['qty'], 0, ',', '.') }}</span>
                                                </span>
                                            @endforeach
                                            @if ($c['items']->count() > 3)
                                                <span class="item-chip item-chip-more mono">+{{ $c['items']->count() - 3 }}</span>
                                            @endif
                                        </div>
                                    @endif

                                    <div class="prog-wrap mt-2">
                                        <div class="prog">
                                            <span class="{{ $fillClass($c['pct']) }}" style="width: {{ $c['pct'] }}%"></span>
                                        </div>
                                        <div class="prog-num mono">
                                            <span><b>{{ $c['pct'] }}%</b> disetor</span>
                                            <span>{{ number_format($c['done_qty'], 0, ',', '.') }}/{{ number_format($c['total_qty'], 0, ',', '.') }} pcs</span>
                                        </div>
                                    </div>

                                    <div class="pickup-mobile-bottom">
                                        <div class="help mono">{{ number_format($c['bundles'], 0, ',', '.') }} bundle</div>
                                        <a href="{{ $showUrl }}" class="btn btn-sm btn-outline-primary btn-detail-mobile"
                                            onclick="event.stopPropagation();">Detail</a>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                @if ($pickups instanceof \Illuminate\Pagination\AbstractPaginator)
                    <div class="mt-2">{{ $pickups->links() }}</div>
                @endif
            </div>

        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            function go(url) { if (url) window.location.assign(url); }

            document.querySelectorAll('.pickup-row[data-href], .pickup-mobile-card[data-href]').forEach(function(el) {
                el.addEventListener('click', function(e) {
                    if (e.target.closest('a,button,input,label,select,textarea')) return;
                    go(el.dataset.href);
                });
            });
        });
    </script>
@endpush
