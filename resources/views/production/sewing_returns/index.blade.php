{{-- resources/views/production/sewing_returns/index.blade.php --}}
@extends('layouts.app')

@section('title', 'Produksi • Sewing Returns')

@push('head')
    <style>
        .sewing-return-page { min-height: 100vh; }

        .page-wrap { max-width: 1100px; margin-inline: auto; padding: 1rem 1rem 3.5rem; }

        body[data-theme="light"] .page-wrap {
            background: radial-gradient(circle at top left, rgba(59, 130, 246, 0.12) 0, rgba(45, 212, 191, 0.08) 28%, #f9fafb 60%);
        }
        body[data-theme="dark"] .page-wrap {
            background: radial-gradient(circle at top left, rgba(59, 130, 246, 0.25) 0, rgba(45, 212, 191, 0.15) 26%, #020617 60%);
        }

        .card-main {
            background: var(--card); border: 1px solid var(--line); border-radius: 16px;
            box-shadow: 0 12px 30px rgba(15, 23, 42, 0.12), 0 0 0 1px rgba(148, 163, 184, 0.08);
        }
        body[data-theme="dark"] .card-main {
            border-color: rgba(30, 64, 175, 0.55);
            box-shadow: 0 16px 40px rgba(0, 0, 0, 0.78), 0 0 0 1px rgba(15, 23, 42, 0.8);
        }

        .mono { font-variant-numeric: tabular-nums; font-family: ui-monospace, SFMono-Regular, Menlo, Consolas; }
        .help { color: var(--muted); font-size: .82rem; }
        .filter-label { font-size: .74rem; text-transform: uppercase; letter-spacing: .08em; color: var(--muted); }
        .table-wrap { overflow-x: auto; }

        .header-row { display: flex; justify-content: space-between; align-items: flex-start; gap: .75rem; flex-wrap: wrap; }
        .header-actions { display: flex; gap: .4rem; }
        .summary-row { display: flex; flex-wrap: wrap; gap: .35rem; margin-top: .4rem; }
        .summary-pill { border-radius: 999px; padding: .12rem .6rem; font-size: .78rem; background: rgba(148, 163, 184, 0.14); color: var(--muted); }
        .summary-pill-accent { background: rgba(13, 110, 253, 0.12); color: #0d6efd; }

        .table-sewing-return-index thead th {
            font-size: .74rem; text-transform: uppercase; letter-spacing: .06em; color: var(--muted);
            border-top: none; background: rgba(15, 23, 42, 0.02);
        }
        .table-sewing-return-index tbody td { vertical-align: middle; border-top-color: rgba(148, 163, 184, 0.18); }

        .status-pill { border-radius: 999px; padding: .15rem .6rem; font-size: .7rem; }

        /* chips item */
        .item-chips { display: flex; flex-wrap: wrap; gap: .25rem; }
        .item-chip {
            display: inline-flex; align-items: baseline; gap: .25rem;
            font-size: .72rem; line-height: 1.1; padding: .2rem .45rem; border-radius: 999px;
            background: rgba(148, 163, 184, 0.14); border: 1px solid rgba(148, 163, 184, 0.18); white-space: nowrap;
        }
        .item-chip b { font-weight: 700; letter-spacing: .02em; }
        .item-chip .q { color: var(--muted); }
        .item-chip-more { background: transparent; color: var(--muted); }

        /* progress */
        .prog-wrap { min-width: 150px; }
        .prog { height: 6px; border-radius: 999px; overflow: hidden; background: rgba(148, 163, 184, 0.22); }
        .prog > span { display: block; height: 100%; border-radius: 999px; transition: width .3s ease; }
        .fill-done { background: linear-gradient(90deg, #16a34a, #22c55e); }
        .fill-part { background: linear-gradient(90deg, #2563eb, #38bdf8); }
        .fill-zero { background: rgba(148, 163, 184, 0.5); }
        .prog-num { font-size: .72rem; color: var(--muted); margin-top: .25rem; display: flex; justify-content: space-between; gap: .5rem; }
        .prog-num b { color: inherit; font-weight: 700; }

        .ret-row { cursor: pointer; transition: background-color .12s ease, box-shadow .12s ease; }
        .ret-row:hover { background: color-mix(in srgb, var(--card) 82%, #0d6efd 6%); box-shadow: 0 0 0 1px rgba(148, 163, 184, 0.45); }

        @media (max-width: 767.98px) {
            .page-wrap { padding-inline: .75rem; padding-bottom: 5rem; }
            .card-main { border-radius: 14px; }
            .header-row { flex-direction: column; align-items: stretch; gap: .6rem; }
            .header-actions { display: flex; gap: .5rem; }
            .header-actions .btn { flex: 1; justify-content: center; }
            .filter-row { flex-direction: column; }

            .ret-mobile-card {
                border-radius: 16px; padding: .75rem .85rem; cursor: pointer;
                background: radial-gradient(circle at top left, rgba(148, 163, 184, 0.2) 0, color-mix(in srgb, var(--card) 92%, var(--line) 8%) 52%);
                border: 1px solid color-mix(in srgb, var(--line) 75%, transparent 25%);
                box-shadow: 0 10px 25px rgba(15, 23, 42, 0.18), 0 0 0 1px rgba(15, 23, 42, 0.03);
            }
            .ret-mobile-card:active { transform: translateY(1px); }
            .ret-mobile-top { display: flex; justify-content: space-between; align-items: flex-start; gap: .5rem; margin-bottom: .3rem; }
        }
    </style>
@endpush

@section('content')
    @php
        $user = auth()->user();
        $role = $user?->role ?? null;
        $isOperating = $role === 'operating';

        $statusOptions = ['' => 'Semua', 'posted' => 'Posted', 'closed' => 'Closed', 'draft' => 'Draft'];

        $totalReturns = $returns->total();

        $statusMap = [
            'draft' => ['label' => 'Draft', 'class' => 'secondary'],
            'posted' => ['label' => 'Posted', 'class' => 'primary'],
            'closed' => ['label' => 'Closed', 'class' => 'success'],
        ];

        // ===== helper per-return =====
        $calc = function ($ret) {
            $lines = $ret->lines ?? collect();

            $pickup = (float) $lines->sum(fn($l) => (float) (optional($l->sewingPickupLine)->qty_bundle ?? 0));
            $remaining = (float) $lines->sum(function ($l) {
                $pl = $l->sewingPickupLine;
                if (!$pl) return 0;
                $qb = (float) ($pl->qty_bundle ?? 0);
                $ok = (float) ($pl->qty_returned_ok ?? 0);
                $rj = (float) ($pl->qty_returned_reject ?? 0);
                $dp = (float) ($pl->qty_direct_picked ?? 0);
                $pa = (float) ($pl->qty_progress_adjusted ?? 0);
                return max($qb - ($ok + $rj + $dp + $pa), 0);
            });
            $setor = max($pickup - $remaining, 0);
            $pct = $pickup > 0 ? (int) min(100, round($setor / $pickup * 100)) : 0;

            // chip item: qty_ok setor return ini, group per kode item
            $items = $lines
                ->groupBy(fn($l) => optional(optional($l->sewingPickupLine)->finishedItem)->code ?: '—')
                ->map(fn($g) => [
                    'code' => optional(optional($g->first()->sewingPickupLine)->finishedItem)->code ?: '—',
                    'name' => optional(optional($g->first()->sewingPickupLine)->finishedItem)->name ?: '',
                    'qty' => (float) $g->sum(fn($l) => (float) ($l->qty_ok ?? 0)),
                ])
                ->sortByDesc('qty')
                ->values();

            $okSum = (float) $lines->sum(fn($l) => (float) ($l->qty_ok ?? 0));
            $rjSum = (float) $lines->sum(fn($l) => (float) ($l->qty_reject ?? 0));

            return compact('pickup', 'remaining', 'setor', 'pct', 'items', 'okSum', 'rjSum');
        };

        $fillClass = fn($pct) => $pct >= 100 ? 'fill-done' : ($pct > 0 ? 'fill-part' : 'fill-zero');

        // ringkasan halaman
        $sumPickupPage = 0.0; $sumRemainingPage = 0.0;
        foreach ($returns as $ret) { $c = $calc($ret); $sumPickupPage += $c['pickup']; $sumRemainingPage += $c['remaining']; }
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
                                <span class="summary-pill">{{ number_format($sumPickupPage, 0, ',', '.') }} pcs pickup</span>
                                <span class="summary-pill summary-pill-accent">{{ number_format($sumRemainingPage, 0, ',', '.') }} pcs belum setor</span>
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
                                <input type="date" name="from_date" value="{{ $filters['from_date'] }}" class="form-control form-control-sm">
                            </div>
                            <div class="col-6 col-md-2">
                                <div class="filter-label mb-1">Sampai</div>
                                <input type="date" name="to_date" value="{{ $filters['to_date'] }}" class="form-control form-control-sm">
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="filter-label mb-1">Operator</div>
                                <select name="operator_id" class="form-select form-select-sm">
                                    <option value="">Semua operator</option>
                                    @foreach ($operators as $op)
                                        <option value="{{ $op->id }}" {{ (string) $filters['operator_id'] === (string) $op->id ? 'selected' : '' }}>
                                            {{ $op->code }} — {{ $op->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-6 col-md-2">
                                <div class="filter-label mb-1">Status</div>
                                <select name="status" class="form-select form-select-sm">
                                    @foreach ($statusOptions as $value => $label)
                                        <option value="{{ $value }}" {{ (string) $filters['status'] === (string) $value ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12 col-md-3">
                                <div class="filter-label mb-1">Cari</div>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-light border-end-0"><i class="bi bi-search"></i></span>
                                    <input type="text" name="q" value="{{ $filters['q'] }}" class="form-control border-start-0" placeholder="Kode return / operator...">
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
                        Menampilkan {{ $returns->firstItem() ?? 0 }}–{{ $returns->lastItem() ?? 0 }} dari {{ $returns->total() }} data.
                    </div>
                </div>

                {{-- DESKTOP --}}
                <div class="table-wrap d-none d-md-block">
                    <table class="table table-sm align-middle table-sewing-return-index mb-0">
                        <thead>
                            <tr>
                                <th style="width:150px;">Return</th>
                                <th style="width:180px;">Operator</th>
                                <th>Barang (OK)</th>
                                <th style="width:190px;">Progress Setor</th>
                                <th style="width:96px;">Status</th>
                                <th style="width:80px;"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($returns as $ret)
                                @php
                                    $c = $calc($ret);
                                    $cfg = $statusMap[$ret->status] ?? ['label' => strtoupper($ret->status ?? '-'), 'class' => 'secondary'];
                                    $showUrl = route('production.sewing.returns.show', $ret);
                                @endphp
                                <tr class="ret-row" data-href="{{ $showUrl }}">
                                    <td>
                                        <div class="fw-semibold mono">{{ $ret->code }}</div>
                                        <div class="help mono">{{ $ret->date?->format('d M Y') ?? $ret->date }}</div>
                                    </td>
                                    <td>
                                        @if ($ret->operator)
                                            <span class="mono">{{ $ret->operator->code }}</span><br>
                                            <span class="help">{{ $ret->operator->name }}</span>
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
                                            @if ($c['rjSum'] > 0)
                                                <div class="help mt-1 mono text-danger">Reject {{ number_format($c['rjSum'], 0, ',', '.') }}</div>
                                            @endif
                                        @endif
                                    </td>
                                    <td>
                                        <div class="prog-wrap">
                                            <div class="prog"><span class="{{ $fillClass($c['pct']) }}" style="width: {{ $c['pct'] }}%"></span></div>
                                            <div class="prog-num mono">
                                                <span><b>{{ $c['pct'] }}%</b></span>
                                                <span>{{ number_format($c['setor'], 0, ',', '.') }}/{{ number_format($c['pickup'], 0, ',', '.') }}</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td><span class="badge bg-{{ $cfg['class'] }}">{{ $cfg['label'] }}</span></td>
                                    <td class="text-end">
                                        <a href="{{ $showUrl }}" class="btn btn-sm btn-outline-primary" onclick="event.stopPropagation();">Detail</a>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-center text-muted small py-3">Belum ada Sewing Return yang tersimpan.</td></tr>
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
                                    $c = $calc($ret);
                                    $cfg = $statusMap[$ret->status] ?? ['label' => ucfirst($ret->status ?? '-'), 'class' => 'secondary'];
                                    $showUrl = route('production.sewing.returns.show', $ret);
                                @endphp
                                <div class="ret-mobile-card" data-href="{{ $showUrl }}">
                                    <div class="ret-mobile-top">
                                        <div>
                                            <div class="fw-semibold mono">{{ $ret->code }}</div>
                                            <div class="help mono">{{ $ret->date?->format('d M Y') ?? $ret->date }}</div>
                                        </div>
                                        <span class="badge status-pill bg-{{ $cfg['class'] }}">{{ $cfg['label'] }}</span>
                                    </div>

                                    <div class="help mb-2">
                                        @if ($ret->operator)
                                            <span class="mono">{{ $ret->operator->code }}</span> — {{ $ret->operator->name }}
                                        @else Operator: - @endif
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
                                        <div class="prog"><span class="{{ $fillClass($c['pct']) }}" style="width: {{ $c['pct'] }}%"></span></div>
                                        <div class="prog-num mono">
                                            <span><b>{{ $c['pct'] }}%</b> setor</span>
                                            <span>{{ number_format($c['setor'], 0, ',', '.') }}/{{ number_format($c['pickup'], 0, ',', '.') }} pcs</span>
                                        </div>
                                    </div>

                                    <div class="mt-2 text-end">
                                        <a href="{{ $showUrl }}" class="btn btn-sm btn-outline-primary" onclick="event.stopPropagation();">Detail</a>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                @if ($returns->hasPages())
                    <div class="mt-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div class="small text-muted">Halaman {{ $returns->currentPage() }} dari {{ $returns->lastPage() }}</div>
                        <div>{{ $returns->links() }}</div>
                    </div>
                @endif
            </div>

        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            function go(url) { if (url) window.location.assign(url); }
            document.querySelectorAll('.ret-row[data-href], .ret-mobile-card[data-href]').forEach(function(el) {
                el.addEventListener('click', function(e) {
                    if (e.target.closest('a,button,input,label,select,textarea')) return;
                    go(el.dataset.href);
                });
            });
        });
    </script>
@endpush
