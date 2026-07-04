{{-- resources/views/production/sewing_reject_returns/index.blade.php --}}
@extends('layouts.app')

@section('title', 'Produksi • Reject Jahit Siap Setor')

@push('head')
    <style>
        .page-wrap { max-width: 1080px; margin-inline: auto; padding: .85rem .85rem 5.5rem; }
        .gf-card { background: var(--card); border: 1px solid rgba(148,163,184,.22); border-radius: 14px; box-shadow: 0 10px 26px rgba(15,23,42,.08), 0 0 0 1px rgba(15,23,42,.03); }
        .muted { color: var(--muted); }
        .mono { font-variant-numeric: tabular-nums; font-family: ui-monospace, SFMono-Regular, Menlo, Consolas; }
        .page-title { font-size: 1rem; font-weight: 900; margin: 0; }
        .pill { display: inline-flex; align-items: center; gap: .3rem; border-radius: 999px; padding: .18rem .6rem; font-size: .74rem; font-weight: 900; background: rgba(148,163,184,.12); color: var(--muted); white-space: nowrap; }
        .pill-danger { background: rgba(220,38,38,.12); color: #dc2626; }
        .pill-ok { background: rgba(22,163,74,.12); color: #16a34a; }
        .pill-blue { background: rgba(37,99,235,.10); color: #2563eb; }
        .filter-label { font-size: .74rem; text-transform: uppercase; letter-spacing: .08em; font-weight: 800; color: var(--muted); }
        .mini-kpis { display:grid; grid-template-columns: repeat(4, minmax(0,1fr)); gap:.45rem; margin-top:.65rem; }
        .mini-kpi { border:1px solid rgba(148,163,184,.18); border-radius:12px; padding:.55rem .65rem; background:rgba(148,163,184,.05); min-width:0; }
        .mini-kpi .lbl { display:block; color:var(--muted); font-size:.62rem; font-weight:900; text-transform:uppercase; letter-spacing:.08em; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
        .mini-kpi .val { display:flex; align-items:baseline; gap:.18rem; margin-top:.12rem; font-weight:900; font-size:1rem; white-space:nowrap; }
        .mini-kpi.main .val { color:#dc2626; }
        .unit { color:var(--muted); font-size:.68em; font-weight:900; }
        .table thead th { border-top: 0; font-size: .74rem; text-transform: uppercase; letter-spacing: .06em; color: var(--muted); }
        .table tbody td { vertical-align: middle; border-top-color: rgba(148,163,184,.18); }
        .row-link { cursor: pointer; transition: background .15s ease; }
        .row-link:hover { background: rgba(37,99,235,.05); }
        .source-badge { display:inline-flex; align-items:center; justify-content:center; min-width:34px; border-radius:999px; padding:.12rem .45rem; font-size:.68rem; font-weight:900; background:rgba(37,99,235,.10); color:#2563eb; }
        .status-badge { display:inline-flex; border-radius:999px; padding:.12rem .5rem; font-size:.68rem; font-weight:900; }
        .status-badge.open { background:rgba(220,38,38,.10); color:#dc2626; }
        .status-badge.partial { background:rgba(245,158,11,.13); color:#b45309; }
        .status-badge.done { background:rgba(22,163,74,.12); color:#16a34a; }
        .btn-rework { border-radius:999px; font-size:.76rem; font-weight:900; padding:.24rem .7rem; }
        .btn-convert { border-radius:999px; font-size:.76rem; font-weight:900; padding:.24rem .7rem; }
        .modal-mini-note { border:1px solid rgba(245,158,11,.22); background:rgba(245,158,11,.08); border-radius:12px; padding:.65rem .75rem; color:#92400e; font-size:.82rem; font-weight:700; }
        .mobile-card { border: 1px solid rgba(148,163,184,.2); border-radius: 14px; padding: .75rem; background: var(--card); box-shadow: 0 8px 22px rgba(15,23,42,.08); }
        .mobile-card:active { transform: scale(.995); }
        .mobile-top { display:flex; justify-content:space-between; gap:.75rem; align-items:flex-start; }
        .sku { font-size:.96rem; font-weight:900; color:#2563eb; letter-spacing:.04em; }
        .qty-focus { text-align:right; }
        .qty-focus .num { font-size:1.2rem; font-weight:900; color:#dc2626; line-height:1; }
        .qty-focus .txt { font-size:.62rem; color:var(--muted); font-weight:900; text-transform:uppercase; letter-spacing:.08em; }
        .meta-line { display:flex; gap:.35rem; flex-wrap:wrap; align-items:center; margin-top:.38rem; }
        @media (max-width: 767.98px) {
            .page-wrap { padding-inline: .75rem; }
            .gf-card { border-radius: 12px; }
            .header-actions { width: 100%; }
            .header-actions .btn { flex: 1 1 auto; }
            .mini-kpis { grid-template-columns: repeat(2, minmax(0,1fr)); gap:.38rem; }
            .mini-kpi { padding:.46rem .55rem; }
        }
    </style>
@endpush

@section('content')
    @php
        $fmt = fn($n, $d = 0) => number_format((float) $n, $d, ',', '.');
        $totalReject = (float) ($totalReject ?? 0);
        $totalReworked = (float) ($totalReworked ?? 0);
        $totalConverted = (float) ($totalConverted ?? 0);
        $totalFromFinishing = (int) ($totalFromFinishing ?? 0);
        $totalFromSewing = (int) ($totalFromSewing ?? 0);
        $buildUrl = function ($r) {
            $params = [
                'operator_id' => $r->operator_id,
                'source' => 'reject-sewing',
            ];
            if (($r->source_kind ?? '') === 'finishing') {
                $params['source_finishing_job_line_id'] = $r->line_id;
            } else {
                $params['reject_return_line_id'] = $r->line_id;
            }
            return route('production.sewing.returns.create', $params);
        };
    @endphp

    <div class="page-wrap">
        @if (session('status'))
            <div class="alert alert-success py-2 px-3 small mb-3">{{ session('status') }}</div>
        @endif
        @if ($errors->any())
            <div class="alert alert-danger py-2 px-3 small mb-3">
                {{ $errors->first() }}
            </div>
        @endif

        <div class="gf-card p-3 p-md-4 mb-3">
            <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
                <div>
                    <h1 class="page-title">Reject Jahit</h1>
                    <div class="muted small">Barang di REJ-SEW yang perlu disetor ulang setelah diperbaiki.</div>
                    <div class="d-flex gap-2 flex-wrap mt-2 mono">
                        <span class="pill pill-danger">{{ $fmt($totalRemaining) }} pcs belum selesai</span>
                        <span class="pill pill-blue">{{ $fmt($totalRows) }} baris</span>
                        <span class="pill pill-ok">Setor ulang ke WIP-FIN</span>
                    </div>
                </div>
                <div class="header-actions d-flex gap-2">
                    <a href="{{ route('production.sewing.returns.index') }}" class="btn btn-sm btn-outline-secondary">Riwayat Setor</a>
                    <a href="{{ route('production.dashboard', ['tab' => 'reject']) }}" class="btn btn-sm btn-outline-secondary">Dashboard</a>
                </div>
            </div>

            <div class="mini-kpis">
                <div class="mini-kpi main">
                    <span class="lbl">Sisa</span>
                    <span class="val mono">{{ $fmt($totalRemaining) }} <span class="unit">pcs</span></span>
                </div>
                <div class="mini-kpi">
                    <span class="lbl">Sudah Setor</span>
                    <span class="val mono">{{ $fmt($totalReworked) }} <span class="unit">pcs</span></span>
                </div>
                <div class="mini-kpi">
                    <span class="lbl">Dari Finishing</span>
                    <span class="val mono">{{ $fmt($totalFromFinishing) }} <span class="unit">baris</span></span>
                </div>
                <div class="mini-kpi">
                    <span class="lbl">Dari Setor</span>
                    <span class="val mono">{{ $fmt($totalFromSewing) }} <span class="unit">baris</span></span>
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
                <h2 class="h6 mb-0">Daftar Perlu Disetor Ulang</h2>
                <div class="small muted d-none d-md-block">Klik baris atau tombol Setor Ulang.</div>
            </div>

            <div class="table-responsive d-none d-md-block">
                <table class="table table-sm align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Sumber</th>
                            <th>Barang</th>
                            <th>Operator</th>
                            <th class="text-end">Reject</th>
                            <th class="text-end">Sudah</th>
                            <th class="text-end">Sisa</th>
                            <th>Status</th>
                            <th class="text-end"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rejects as $r)
                            @php
                                $url = $buildUrl($r);
                            @endphp
                            <tr class="row-link" onclick="window.location='{{ $url }}'">
                                <td>
                                    <span class="source-badge mono">{{ $r->source_badge }}</span>
                                    <div class="small muted mt-1">{{ $r->source_label }}</div>
                                    <div class="small mono muted">{{ \Carbon\Carbon::parse($r->reject_date)->format('d M Y') }}</div>
                                    <div class="small mono muted">{{ $r->reject_code }}</div>
                                </td>
                                <td>
                                    <strong class="mono">{{ $r->sku }}</strong>
                                    <div class="small muted">{{ $r->product_name }}</div>
                                    @if (($r->notes ?? '-') !== '-')
                                        <div class="small muted text-truncate" style="max-width:260px;">{{ $r->notes }}</div>
                                    @endif
                                </td>
                                <td><span class="mono">{{ $r->operator_code }}</span><br><span class="small muted">{{ $r->operator_name }}</span></td>
                                <td class="text-end mono">{{ $fmt($r->qty_reject) }}</td>
                                <td class="text-end mono">{{ $fmt($r->qty_reworked) }}</td>
                                <td class="text-end mono fw-bold text-danger">{{ $fmt($r->remaining_qty) }}</td>
                                <td><span class="status-badge {{ $r->status_class }}">{{ $r->status_label }}</span></td>
                                <td class="text-end">
                                    <a href="{{ $url }}" class="btn btn-sm btn-primary btn-rework" onclick="event.stopPropagation()">Setor Ulang</a>
                                    <button type="button"
                                            class="btn btn-sm btn-outline-danger btn-convert ms-1"
                                            data-convert-btn
                                            data-source-kind="{{ $r->source_kind }}"
                                            data-line-id="{{ $r->line_id }}"
                                            data-sku="{{ $r->sku }}"
                                            data-remaining="{{ (float) $r->remaining_qty }}"
                                            data-source-label="{{ $r->source_label }}"
                                            onclick="event.stopPropagation()">
                                        Tidak Bisa
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="text-center text-muted py-4">Tidak ada reject jahit yang perlu disetor ulang.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="d-grid gap-2 d-md-none">
                @forelse ($rejects as $r)
                    @php
                        $url = $buildUrl($r);
                    @endphp
                    <div class="mobile-card" onclick="window.location='{{ $url }}'">
                        <div class="mobile-top">
                            <div class="min-w-0">
                                <div class="sku mono">{{ $r->sku }}</div>
                                <div class="small muted text-truncate">{{ $r->product_name }}</div>
                                <div class="meta-line">
                                    <span class="source-badge mono">{{ $r->source_badge }}</span>
                                    <span class="status-badge {{ $r->status_class }}">{{ $r->status_label }}</span>
                                </div>
                            </div>
                            <div class="qty-focus">
                                <div class="num mono">{{ $fmt($r->remaining_qty) }}</div>
                                <div class="txt">sisa pcs</div>
                            </div>
                        </div>
                        <div class="small muted mt-2">
                            {{ $r->operator_code }} — {{ $r->operator_name }}
                        </div>
                        <div class="small muted">
                            {{ $r->source_label }} · {{ \Carbon\Carbon::parse($r->reject_date)->format('d M Y') }} · {{ $r->reject_code }}
                        </div>
                        <div class="d-flex justify-content-between align-items-center gap-2 mt-2">
                            <div class="small muted mono">Reject {{ $fmt($r->qty_reject) }} · sudah {{ $fmt($r->qty_reworked) }}</div>
                            <div class="d-flex gap-1">
                                <button type="button"
                                        class="btn btn-sm btn-outline-danger btn-convert"
                                        data-convert-btn
                                        data-source-kind="{{ $r->source_kind }}"
                                        data-line-id="{{ $r->line_id }}"
                                        data-sku="{{ $r->sku }}"
                                        data-remaining="{{ (float) $r->remaining_qty }}"
                                        data-source-label="{{ $r->source_label }}"
                                        onclick="event.stopPropagation()">
                                    Tidak Bisa
                                </button>
                                <a href="{{ $url }}" class="btn btn-sm btn-primary btn-rework" onclick="event.stopPropagation()">Setor</a>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="text-center text-muted py-4">Tidak ada reject jahit yang perlu disetor ulang.</div>
                @endforelse
            </div>

            @if ($rejects->hasPages())
                <div class="mt-3">{{ $rejects->links() }}</div>
            @endif
        </div>
    </div>

    <div class="modal fade" id="convertRejectModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <form method="POST" action="{{ route('production.sewing.reject_returns.convert') }}" class="modal-content">
                @csrf
                <div class="modal-header py-2">
                    <h5 class="modal-title mb-0" style="font-size:.95rem; font-weight:900;">Tidak Bisa Diperbaiki</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="source_reject_return_line_id" id="convert-source-reject">
                    <input type="hidden" name="source_finishing_job_line_id" id="convert-source-finishing">

                    <div class="mb-2">
                        <div class="filter-label mb-1">Barang</div>
                        <div class="d-flex justify-content-between align-items-center gap-2">
                            <strong class="mono text-primary" id="convert-sku">-</strong>
                            <span class="pill pill-danger">Sisa <span class="mono" id="convert-remaining">0</span> pcs</span>
                        </div>
                        <div class="small muted mt-1" id="convert-source-label">-</div>
                    </div>

                    <div class="row g-2">
                        <div class="col-5">
                            <label class="filter-label mb-1">Tanggal</label>
                            <input type="date" name="date" class="form-control form-control-sm mono" value="{{ now()->toDateString() }}" required>
                        </div>
                        <div class="col-7">
                            <label class="filter-label mb-1">Qty</label>
                            <input type="number" name="qty" id="convert-qty" class="form-control form-control-sm mono" min="0.001" step="0.001" inputmode="decimal" required>
                        </div>
                    </div>

                    <div class="mt-2">
                        <label class="filter-label mb-1">Catatan</label>
                        <input type="text" name="notes" class="form-control form-control-sm" placeholder="Opsional">
                    </div>

                    <div class="modal-mini-note mt-3">
                        Stok keluar dari REJ-SEW item asli, lalu masuk ke SKU reject kategori.
                    </div>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-sm btn-danger">Ubah ke RJCT</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const modalEl = document.getElementById('convertRejectModal');
            if (!modalEl || !window.bootstrap) return;

            const modal = new bootstrap.Modal(modalEl);
            const rejectInput = document.getElementById('convert-source-reject');
            const finishingInput = document.getElementById('convert-source-finishing');
            const skuEl = document.getElementById('convert-sku');
            const sourceEl = document.getElementById('convert-source-label');
            const remainingEl = document.getElementById('convert-remaining');
            const qtyInput = document.getElementById('convert-qty');
            const fmt = (value) => new Intl.NumberFormat('id-ID', { maximumFractionDigits: 3 }).format(Number(value || 0));

            document.querySelectorAll('[data-convert-btn]').forEach((btn) => {
                btn.addEventListener('click', (event) => {
                    event.preventDefault();
                    event.stopPropagation();

                    const sourceKind = btn.dataset.sourceKind || '';
                    const lineId = btn.dataset.lineId || '';
                    const remaining = Number(btn.dataset.remaining || 0);

                    rejectInput.value = sourceKind === 'finishing' ? '' : lineId;
                    finishingInput.value = sourceKind === 'finishing' ? lineId : '';
                    skuEl.textContent = btn.dataset.sku || '-';
                    sourceEl.textContent = btn.dataset.sourceLabel || '-';
                    remainingEl.textContent = fmt(remaining);
                    qtyInput.value = remaining > 0 ? remaining : '';
                    qtyInput.max = remaining > 0 ? remaining : '';

                    modal.show();
                    setTimeout(() => {
                        qtyInput.focus();
                        qtyInput.select();
                    }, 220);
                });
            });
        });
    </script>
@endpush
