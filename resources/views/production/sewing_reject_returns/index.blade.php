{{-- resources/views/production/sewing_reject_returns/index.blade.php --}}
@extends('layouts.app')

@section('title', 'Produksi • Reject Jahit Siap Setor')

@push('head')
    <style>
        .page-wrap { max-width: 1080px; margin-inline: auto; padding: 1rem 1rem 5rem; }
        .gf-card { background: var(--card); border: 1px solid var(--line); border-radius: 14px; box-shadow: 0 12px 30px rgba(15,23,42,.10); }
        .muted { color: var(--muted); }
        .mono { font-variant-numeric: tabular-nums; font-family: ui-monospace, SFMono-Regular, Menlo, Consolas; }
        .pill { display: inline-flex; align-items: center; gap: .3rem; border-radius: 999px; padding: .18rem .6rem; font-size: .78rem; font-weight: 800; background: rgba(148,163,184,.12); color: var(--muted); }
        .pill-danger { background: rgba(220,38,38,.12); color: #dc2626; }
        .pill-ok { background: rgba(22,163,74,.12); color: #16a34a; }
        .filter-label { font-size: .74rem; text-transform: uppercase; letter-spacing: .08em; font-weight: 800; color: var(--muted); }
        .table thead th { border-top: 0; font-size: .74rem; text-transform: uppercase; letter-spacing: .06em; color: var(--muted); }
        .table tbody td { vertical-align: middle; border-top-color: rgba(148,163,184,.18); }
        .row-link { cursor: pointer; }
        .row-link:hover { background: rgba(22,163,74,.06); }
        .mobile-card { border: 1px solid rgba(148,163,184,.2); border-radius: 14px; padding: .8rem; background: var(--card); box-shadow: 0 10px 24px rgba(15,23,42,.10); }
        @media (max-width: 767.98px) {
            .page-wrap { padding-inline: .75rem; }
            .header-actions { width: 100%; }
            .header-actions .btn { flex: 1 1 auto; }
        }
    </style>
@endpush

@section('content')
    @php
        $fmt = fn($n, $d = 0) => number_format((float) $n, $d, ',', '.');
    @endphp

    <div class="page-wrap">
        @if (session('status'))
            <div class="alert alert-success py-2 px-3 small mb-3">{{ session('status') }}</div>
        @endif

        <div class="gf-card p-3 p-md-4 mb-3">
            <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
                <div>
                    <h1 class="h5 mb-1">Reject Jahit Siap Setor</h1>
                    <div class="muted small">Barang reject jahit yang masih ada di REJ-SEW dan bisa disetor ulang.</div>
                    <div class="d-flex gap-2 flex-wrap mt-2 mono">
                        <span class="pill">{{ $fmt($totalRows) }} baris</span>
                        <span class="pill pill-danger">{{ $fmt($totalRemaining) }} pcs di REJ-SEW</span>
                        <span class="pill pill-ok">Tujuan setor: WIP-FIN</span>
                    </div>
                </div>
                <div class="header-actions d-flex gap-2">
                    <a href="{{ route('production.sewing.returns.index') }}" class="btn btn-sm btn-outline-secondary">Setor Jahit</a>
                    <a href="{{ route('production.dashboard', ['tab' => 'reject']) }}" class="btn btn-sm btn-outline-secondary">Dashboard</a>
                </div>
            </div>

            <form method="get" class="mt-3">
                <div class="row g-2 align-items-end">
                    <div class="col-12 col-md-4">
                        <div class="filter-label mb-1">Operator</div>
                        <select name="operator_id" class="form-select form-select-sm">
                            <option value="">Semua operator</option>
                            @foreach ($operators as $op)
                                <option value="{{ $op->id }}" @selected((string) $filters['operator_id'] === (string) $op->id)>
                                    {{ $op->code }} — {{ $op->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 col-md-5">
                        <div class="filter-label mb-1">Cari</div>
                        <input type="text" name="q" value="{{ $filters['q'] }}" class="form-control form-control-sm" placeholder="SKU / produk / operator / catatan">
                    </div>
                    <div class="col-12 col-md-3 d-flex gap-2">
                        <button type="submit" class="btn btn-sm btn-primary flex-fill">Filter</button>
                        @if (array_filter($filters))
                            <a href="{{ route('production.sewing.reject_returns.index') }}" class="btn btn-sm btn-outline-secondary">Reset</a>
                        @endif
                    </div>
                </div>
            </form>
        </div>

        <div class="gf-card p-3 p-md-4">
            <div class="d-flex justify-content-between align-items-center gap-2 mb-2 flex-wrap">
                <h2 class="h6 mb-0">Daftar Reject</h2>
                <div class="small muted">Klik baris untuk setor ulang.</div>
            </div>

            <div class="table-responsive d-none d-md-block">
                <table class="table table-sm align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Tanggal Reject</th>
                            <th>Operator</th>
                            <th>Barang</th>
                            <th class="text-end">Sisa REJ-SEW</th>
                            <th>Catatan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rejects as $r)
                            @php
                                $params = [
                                    'operator_id' => $r->operator_id,
                                    'source' => 'reject-sewing',
                                ];
                                if (($r->source_kind ?? '') === 'finishing') {
                                    $params['source_finishing_job_line_id'] = $r->line_id;
                                } else {
                                    $params['reject_return_line_id'] = $r->line_id;
                                }
                                $url = route('production.sewing.returns.create', $params);
                            @endphp
                            <tr class="row-link" onclick="window.location='{{ $url }}'">
                                <td class="mono">{{ \Carbon\Carbon::parse($r->reject_date)->format('d M Y') }}<br><span class="small muted">{{ $r->reject_code }}</span></td>
                                <td><span class="mono">{{ $r->operator_code }}</span><br><span class="small muted">{{ $r->operator_name }}</span></td>
                                <td><strong class="mono">{{ $r->sku }}</strong><br><span class="small muted">{{ $r->product_name }}</span></td>
                                <td class="text-end mono fw-bold">{{ $fmt($r->remaining_qty) }}</td>
                                <td class="small muted">{{ $r->notes }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted py-4">Tidak ada reject jahit yang bisa disetor ulang.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="d-grid gap-2 d-md-none">
                @forelse ($rejects as $r)
                    @php
                        $params = [
                            'operator_id' => $r->operator_id,
                            'source' => 'reject-sewing',
                        ];
                        if (($r->source_kind ?? '') === 'finishing') {
                            $params['source_finishing_job_line_id'] = $r->line_id;
                        } else {
                            $params['reject_return_line_id'] = $r->line_id;
                        }
                        $url = route('production.sewing.returns.create', $params);
                    @endphp
                    <div class="mobile-card" onclick="window.location='{{ $url }}'">
                        <div class="d-flex justify-content-between gap-2">
                            <div class="min-w-0">
                                <div class="mono fw-bold text-success">{{ $r->sku }}</div>
                                <div class="small muted">{{ $r->operator_code }} — {{ $r->operator_name }}</div>
                            </div>
                            <div class="text-end">
                                <div class="mono fw-bold text-danger">{{ $fmt($r->remaining_qty) }}</div>
                                <div class="small muted">pcs</div>
                            </div>
                        </div>
                        <div class="small muted mt-2">{{ \Carbon\Carbon::parse($r->reject_date)->format('d M Y') }} · {{ $r->reject_code }}</div>
                    </div>
                @empty
                    <div class="text-center text-muted py-4">Tidak ada reject jahit yang bisa disetor ulang.</div>
                @endforelse
            </div>

            @if ($rejects->hasPages())
                <div class="mt-3">{{ $rejects->links() }}</div>
            @endif
        </div>
    </div>
@endsection
