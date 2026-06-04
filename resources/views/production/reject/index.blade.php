{{-- resources/views/production/reject/index.blade.php --}}
@extends('layouts.app')

@section('title', 'Produksi • Reject')

@push('head')
    <style>
        .reject-page { min-height: 100vh; }

        .page-wrap { max-width: 1100px; margin-inline: auto; padding: 1rem 1rem 3.5rem; }

        body[data-theme="light"] .page-wrap {
            background: radial-gradient(circle at top left, rgba(239, 68, 68, 0.12) 0, rgba(249, 115, 22, 0.08) 28%, #f9fafb 60%);
        }
        body[data-theme="dark"] .page-wrap {
            background: radial-gradient(circle at top left, rgba(239, 68, 68, 0.25) 0, rgba(249, 115, 22, 0.15) 26%, #020617 60%);
        }

        .card-main {
            background: var(--card); border: 1px solid var(--line); border-radius: 16px;
            box-shadow: 0 12px 30px rgba(15, 23, 42, 0.12), 0 0 0 1px rgba(148, 163, 184, 0.08);
        }
        body[data-theme="dark"] .card-main {
            border-color: rgba(127, 29, 29, 0.55);
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
        .summary-pill-accent { background: rgba(220, 38, 38, 0.12); color: #dc2626; }

        .table-reject-index thead th {
            font-size: .74rem; text-transform: uppercase; letter-spacing: .06em; color: var(--muted);
            border-top: none; background: rgba(15, 23, 42, 0.02);
        }
        .table-reject-index tbody td { vertical-align: middle; border-top-color: rgba(148, 163, 184, 0.18); }
        .table-reject-index .num { text-align: right; font-variant-numeric: tabular-nums; }

        .stage-pill { border-radius: 999px; padding: .15rem .55rem; font-size: .7rem; font-weight: 600; }
        .stage-cutting { background: rgba(37, 99, 235, 0.14); color: #2563eb; }
        .stage-jahit { background: rgba(217, 119, 6, 0.16); color: #d97706; }

        .item-chip {
            display: inline-flex; align-items: baseline; gap: .25rem;
            font-size: .72rem; line-height: 1.1; padding: .2rem .45rem; border-radius: 999px;
            background: rgba(148, 163, 184, 0.14); border: 1px solid rgba(148, 163, 184, 0.18); white-space: nowrap;
        }
        .item-chip b { font-weight: 700; letter-spacing: .02em; }

        .reason-text { color: var(--muted); font-size: .8rem; }

        .fix-badge {
            display: inline-flex; align-items: center; gap: .2rem;
            border-radius: 999px; padding: .08rem .45rem; font-size: .66rem; font-weight: 700;
            background: rgba(22, 163, 74, 0.15); color: #16a34a; white-space: nowrap;
        }
        .remain-badge {
            display: inline-flex; align-items: center; gap: .2rem;
            border-radius: 999px; padding: .08rem .45rem; font-size: .66rem; font-weight: 700;
            background: rgba(220, 38, 38, 0.12); color: #dc2626; white-space: nowrap;
        }
        .btn-void-repair { --bs-btn-padding-y: .05rem; --bs-btn-padding-x: .4rem; --bs-btn-font-size: .68rem; }

        @media (max-width: 767.98px) {
            .page-wrap { padding-inline: .75rem; padding-bottom: 5rem; }
            .card-main { border-radius: 14px; }
            .header-row { flex-direction: column; align-items: stretch; gap: .6rem; }
            .filter-row { flex-direction: column; }

            .rj-mobile-card {
                border-radius: 16px; padding: .75rem .85rem;
                background: radial-gradient(circle at top left, rgba(148, 163, 184, 0.2) 0, color-mix(in srgb, var(--card) 92%, var(--line) 8%) 52%);
                border: 1px solid color-mix(in srgb, var(--line) 75%, transparent 25%);
                box-shadow: 0 10px 25px rgba(15, 23, 42, 0.18), 0 0 0 1px rgba(15, 23, 42, 0.03);
            }
            .rj-mobile-top { display: flex; justify-content: space-between; align-items: flex-start; gap: .5rem; margin-bottom: .3rem; }
        }
    </style>
@endpush

@section('content')
    @php
        $fmt = fn($n, $d = 0) => number_format((float) $n, $d, ',', '.');
        $rp = fn($n) => 'Rp ' . number_format((float) $n, 0, ',', '.');
        $stageClass = fn($s) => $s === 'Cutting' ? 'stage-cutting' : 'stage-jahit';

        $stageOptions = ['' => 'Semua Tahap', 'Cutting' => 'Cutting', 'Jahit' => 'Jahit'];
        $totalEvents = $rejects->total();
    @endphp

    <div class="reject-page">
        <div class="page-wrap py-3 py-md-4">

            @if (session('status'))
                <div class="alert alert-success py-2 px-3 small mb-3">{{ session('status') }}</div>
            @endif
            @if ($errors->any())
                <div class="alert alert-danger py-2 px-3 small mb-3">{{ $errors->first() }}</div>
            @endif

            {{-- HEADER + FILTER --}}
            <div class="card-main p-3 p-md-4 mb-3">
                <div class="header-row">
                    <div>
                        <h1 class="h5 mb-1">Reject Produksi</h1>
                        <div class="help">Barang gagal QC dari tahap cutting &amp; jahit — per kejadian.</div>

                        @if ($totalEvents > 0)
                            <div class="summary-row mono">
                                <span class="summary-pill">{{ $fmt($totalEvents) }} kejadian</span>
                                <span class="summary-pill summary-pill-accent">{{ $fmt($totalReject) }} pcs reject</span>
                                <span class="summary-pill">{{ $rp($totalHpp) }} nilai HPP</span>
                                <span class="summary-pill">Cutting {{ $fmt($rejectCutting) }} · Jahit {{ $fmt($rejectSewing) }}</span>
                            </div>
                        @else
                            <div class="help mt-1">Belum ada reject tercatat pada periode ini.</div>
                        @endif
                    </div>

                    <div class="header-actions">
                        <a href="{{ route('production.dashboard') }}"
                            class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1">
                            <i class="bi bi-arrow-left"></i><span>Ke Dashboard</span>
                        </a>
                        <a href="{{ route('production.reject.create') }}"
                            class="btn btn-sm btn-danger d-inline-flex align-items-center gap-1">
                            <i class="bi bi-plus-lg"></i><span class="text-white">Catat Reject</span>
                        </a>
                    </div>
                </div>

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
                            <div class="filter-label mb-1">Tahap</div>
                            <select name="stage" class="form-select form-select-sm">
                                @foreach ($stageOptions as $value => $label)
                                    <option value="{{ $value }}" {{ (string) $filters['stage'] === (string) $value ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12 col-md-3">
                            <div class="filter-label mb-1">Cari</div>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-light border-end-0"><i class="bi bi-search"></i></span>
                                <input type="text" name="q" value="{{ $filters['q'] }}" class="form-control border-start-0" placeholder="SKU / produk / operator / alasan...">
                                @if (array_filter($filters))
                                    <button class="btn btn-outline-secondary" type="button"
                                        onclick="window.location='{{ route('production.reject.index') }}'">Reset</button>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="mt-2 d-flex justify-content-end">
                        <button type="submit" class="btn btn-sm btn-primary">Terapkan Filter</button>
                    </div>
                </form>
            </div>

            {{-- LIST --}}
            <div class="card-main p-3 p-md-4 mb-3">
                <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-1">
                    <h2 class="h6 mb-0">Daftar Reject</h2>
                    <div class="help mb-0">
                        Menampilkan {{ $rejects->firstItem() ?? 0 }}–{{ $rejects->lastItem() ?? 0 }} dari {{ $rejects->total() }} data.
                    </div>
                </div>

                {{-- DESKTOP --}}
                <div class="table-wrap d-none d-md-block">
                    <table class="table table-sm align-middle table-reject-index mb-0">
                        <thead>
                            <tr>
                                <th style="width:110px;">Tanggal</th>
                                <th style="width:90px;">Tahap</th>
                                <th style="width:170px;">Operator</th>
                                <th>Barang</th>
                                <th style="width:90px;" class="num">Reject</th>
                                <th style="width:130px;" class="num">Nilai HPP</th>
                                <th style="width:220px;">Alasan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($rejects as $r)
                                <tr>
                                    <td class="mono">{{ \Carbon\Carbon::parse($r->date)->format('d M Y') }}</td>
                                    <td><span class="stage-pill {{ $stageClass($r->stage) }}">{{ $r->stage }}</span></td>
                                    <td>
                                        @if ($r->operator_code !== '-')
                                            <span class="mono">{{ $r->operator_code }}</span><br>
                                            <span class="help">{{ $r->operator_name }}</span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="item-chip" title="{{ $r->product_name }}"><b>{{ $r->sku }}</b></span>
                                        <div class="help mt-1">{{ $r->product_name }}</div>
                                    </td>
                                    <td class="num">
                                        <b class="text-danger mono">{{ $fmt($r->qty) }}</b>
                                        @if (($r->repaired_qty ?? 0) > 0)
                                            <div class="mt-1 d-flex flex-column align-items-end gap-1">
                                                <span class="fix-badge"><i class="bi bi-check2"></i>{{ $fmt($r->repaired_qty) }} diperbaiki</span>
                                                @if (($r->remaining_qty ?? 0) > 0)
                                                    <span class="remain-badge">sisa {{ $fmt($r->remaining_qty) }}</span>
                                                @endif
                                                @if (!empty($r->line_id))
                                                    <form method="post" action="{{ route('production.reject.void_repair', $r->line_id) }}"
                                                        onsubmit="return confirm('Batalkan perbaikan {{ $fmt($r->repaired_qty) }} pcs dan kembalikan ke gudang REJECT?');">
                                                        @csrf
                                                        <button type="submit" class="btn btn-outline-danger btn-void-repair">Batalkan</button>
                                                    </form>
                                                @endif
                                            </div>
                                        @endif
                                    </td>
                                    <td class="num mono">{{ $rp($r->hpp_total) }}</td>
                                    <td><span class="reason-text">{{ $r->reason }}</span></td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="text-center text-muted small py-3">Tidak ada reject pada periode ini.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- MOBILE --}}
                <div class="d-block d-md-none">
                    @if ($rejects->isEmpty())
                        <div class="text-center text-muted small py-3">Tidak ada reject pada periode ini.</div>
                    @else
                        <div class="d-flex flex-column gap-2">
                            @foreach ($rejects as $r)
                                <div class="rj-mobile-card">
                                    <div class="rj-mobile-top">
                                        <div>
                                            <span class="item-chip" title="{{ $r->product_name }}"><b>{{ $r->sku }}</b></span>
                                            <div class="help mono mt-1">{{ \Carbon\Carbon::parse($r->date)->format('d M Y') }}</div>
                                        </div>
                                        <span class="stage-pill {{ $stageClass($r->stage) }}">{{ $r->stage }}</span>
                                    </div>

                                    <div class="help mb-1">{{ $r->product_name }}</div>

                                    <div class="help mb-2">
                                        @if ($r->operator_code !== '-')
                                            <span class="mono">{{ $r->operator_code }}</span> — {{ $r->operator_name }}
                                        @else Operator: - @endif
                                    </div>

                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="mono">Reject <b class="text-danger">{{ $fmt($r->qty) }}</b> pcs</span>
                                        <span class="mono help">{{ $rp($r->hpp_total) }}</span>
                                    </div>

                                    @if (($r->repaired_qty ?? 0) > 0)
                                        <div class="d-flex align-items-center flex-wrap gap-1 mt-2">
                                            <span class="fix-badge"><i class="bi bi-check2"></i>{{ $fmt($r->repaired_qty) }} diperbaiki</span>
                                            @if (($r->remaining_qty ?? 0) > 0)
                                                <span class="remain-badge">sisa {{ $fmt($r->remaining_qty) }}</span>
                                            @endif
                                            @if (!empty($r->line_id))
                                                <form method="post" action="{{ route('production.reject.void_repair', $r->line_id) }}" class="ms-auto"
                                                    onsubmit="return confirm('Batalkan perbaikan {{ $fmt($r->repaired_qty) }} pcs dan kembalikan ke gudang REJECT?');">
                                                    @csrf
                                                    <button type="submit" class="btn btn-outline-danger btn-void-repair">Batalkan</button>
                                                </form>
                                            @endif
                                        </div>
                                    @endif

                                    @if ($r->reason !== '-')
                                        <div class="reason-text mt-1">{{ $r->reason }}</div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                @if ($rejects->hasPages())
                    <div class="mt-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div class="small text-muted">Halaman {{ $rejects->currentPage() }} dari {{ $rejects->lastPage() }}</div>
                        <div>{{ $rejects->links() }}</div>
                    </div>
                @endif
            </div>

        </div>
    </div>
@endsection
