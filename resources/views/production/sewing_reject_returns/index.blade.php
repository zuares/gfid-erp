{{-- resources/views/production/sewing_reject_returns/index.blade.php --}}
@extends('layouts.app')

@section('title', 'Produksi • Monitoring Reject Jahit')

@push('head')
    <style>
        .page-wrap { max-width: 1100px; margin-inline: auto; padding: 0 .75rem 6rem; }
        body[data-theme="light"] .page-wrap { background:#f3f4f6; }
        body[data-theme="dark"] .page-wrap { background:#020617; }
        .gf-card { background: var(--card); border: 1px solid rgba(148,163,184,.18); border-radius: 8px; box-shadow: none; }
        .rj-topbar {
            position: sticky;
            top: 0;
            z-index: 260;
            padding: .5rem .85rem;
            background: rgba(248,250,252,.97);
            border-top: 0;
            border-left: 0;
            border-right: 0;
            border-radius: 0;
            backdrop-filter: blur(14px);
            -webkit-backdrop-filter: blur(14px);
        }
        body[data-theme="dark"] .rj-topbar { background: rgba(2,6,23,.96); border-bottom-color: rgba(51,65,85,.8); }
        .rj-content { padding-top: .75rem; }
        .muted { color: var(--muted); }
        .mono { font-variant-numeric: tabular-nums; font-family: ui-monospace, SFMono-Regular, Menlo, Consolas; }
        .page-title { font-size: 1.02rem; font-weight: 650; letter-spacing: 0; margin: 0; }
        .pill { display: inline-flex; align-items: center; gap: .3rem; border-radius: 7px; padding: .18rem .5rem; font-size: .7rem; font-weight: 500; background: rgba(248,250,252,.96); color: var(--muted); border:1px solid rgba(148,163,184,.24); white-space: nowrap; }
        .pill-danger { background: rgba(185,28,28,.08); color: #991b1b; }
        .pill-ok { background: rgba(22,101,52,.08); color: #166534; }
        .pill-blue { background: rgba(51,65,85,.08); color: #475569; }
        .filter-label { font-size: .68rem; text-transform: uppercase; letter-spacing: .04em; font-weight: 550; color: var(--muted); }
        .rj-table-card { overflow:hidden; }
        .rj-table-head {
            display:flex;
            justify-content:space-between;
            align-items:center;
            gap:.75rem;
            flex-wrap:wrap;
            padding:.55rem .75rem;
            border-bottom:1px solid rgba(148,163,184,.14);
        }
        .rj-table-body { padding:0; }
        .table thead th { border-top: 0; font-size: .68rem; text-transform: uppercase; letter-spacing: .04em; color: var(--muted); font-weight: 550; }
        .table tbody td { vertical-align: middle; border-top-color: rgba(148,163,184,.14); }
        .table tbody tr:nth-child(even) { background: rgba(249,250,251,.7); }
        body[data-theme="dark"] .table tbody tr:nth-child(even) { background: rgba(15,23,42,.8); }
        .row-link { cursor: pointer; transition: background .15s ease; }
        .row-link:hover { background: rgba(148,163,184,.06); }
        .source-badge { display:inline-flex; align-items:center; justify-content:center; min-width:32px; border-radius:6px; padding:.12rem .4rem; font-size:.66rem; font-weight:600; background:rgba(51,65,85,.08); color:#475569; }
        .status-badge { display:inline-flex; border-radius:6px; padding:.12rem .45rem; font-size:.66rem; font-weight:600; }
        .status-badge.open { background:rgba(185,28,28,.08); color:#991b1b; }
        .status-badge.partial { background:rgba(245,158,11,.13); color:#b45309; }
        .status-badge.done { background:rgba(22,163,74,.12); color:#16a34a; }
        .btn-rework { border-radius:7px; font-size:.74rem; font-weight:600; padding:.24rem .65rem; background:#334155; border-color:#334155; color:#fff; }
        .btn-rework:hover { background:#1f2937; border-color:#1f2937; color:#fff; }
        .btn-convert { border-radius:7px; font-size:.72rem; font-weight:550; padding:.24rem .6rem; }
        .mobile-card { border: 1px solid rgba(148,163,184,.18); border-radius: 8px; padding: .72rem; background: var(--card); box-shadow: none; }
        .mobile-card:active { transform: scale(.995); }
        .mobile-top { display:flex; justify-content:space-between; gap:.75rem; align-items:flex-start; }
        .sku { font-size:.96rem; font-weight:650; color:#334155; letter-spacing:0; }
        .qty-focus { text-align:right; }
        .qty-focus .num { font-size:1.18rem; font-weight:650; color:#991b1b; line-height:1; }
        .qty-focus .txt { font-size:.6rem; color:var(--muted); font-weight:500; text-transform:uppercase; letter-spacing:.04em; }
        .meta-line { display:flex; gap:.35rem; flex-wrap:wrap; align-items:center; margin-top:.38rem; }
        .head-actions { display:flex; gap:.45rem; flex-wrap:wrap; justify-content:flex-end; }
        .head-primary { background:#334155; border-color:#334155; color:#fff; }
        .head-primary:hover { background:#1f2937; border-color:#1f2937; color:#fff; }
        .compact-summary { display:flex; align-items:center; gap:.38rem; flex-wrap:wrap; margin-top:.45rem; }
        @media (max-width: 767.98px) {
            .page-wrap { padding-inline: .75rem; }
            .rj-topbar { margin-inline:-.75rem; padding:.55rem .75rem; }
            .rj-content { padding-top:.6rem; }
            .gf-card { border-radius: 8px; }
            .head-actions { width: 100%; display:grid; grid-template-columns:1fr 1fr; }
            .head-actions .btn { min-height: 34px; }
            .rj-table-head { padding:.45rem .6rem; }
            .mobile-card { padding:.62rem; }
            .sku { font-size:.92rem; }
            .qty-focus .num { font-size:1.08rem; }
        }
    
        /* === Shipment-aligned header override: Reject Jahit === */
        :root{
            --shp-accent:#334155;
            --shp-accent-2:#1f2937;
            --shp-border:rgba(148,163,184,.18);
            --shp-muted:#64748b;
        }

        .page-wrap{
            max-width:1040px!important;
            margin-inline:auto;
            padding:.75rem .75rem 4rem!important;
            background:transparent!important;
        }

        body[data-theme="light"] .page-wrap,
        body[data-theme="dark"] .page-wrap{
            background:transparent!important;
        }

        .gf-card{
            border-radius:8px!important;
            border:1px solid var(--shp-border)!important;
            box-shadow:none!important;
        }

        .rj-topbar{
            position:sticky;
            top:0;
            z-index:300;
            display:block;
            padding:.45rem .75rem!important;
            margin-inline:-.75rem;
            margin-bottom:.65rem!important;
            background:var(--card,#fff)!important;
            border-top:0!important;
            border-left:0!important;
            border-right:0!important;
            border-radius:0!important;
            border-bottom:1px solid var(--shp-border)!important;
            backdrop-filter:none!important;
            -webkit-backdrop-filter:none!important;
        }

        body[data-theme="dark"] .rj-topbar{
            background:var(--card,#0f172a)!important;
            border-bottom-color:rgba(51,65,85,.85)!important;
        }

        .page-title{
            font-weight:750!important;
            font-size:1rem!important;
            letter-spacing:0!important;
            margin:0!important;
            line-height:1.25!important;
        }

        .compact-summary{
            display:flex!important;
            flex-wrap:wrap;
            gap:.32rem!important;
            margin-top:.35rem!important;
        }

        .pill{
            border-radius:7px!important;
            padding:.2rem .48rem!important;
            font-size:.72rem!important;
            font-weight:650!important;
            border:1px solid rgba(148,163,184,.28)!important;
            background:transparent!important;
            color:var(--shp-accent)!important;
        }

        .pill::before{
            content:'';
            width:7px;
            height:7px;
            border-radius:999px;
            display:inline-block;
            margin-right:.32rem;
            background:rgba(100,116,139,.95);
        }

        .pill-danger{
            color:#991b1b!important;
            border-color:rgba(239,68,68,.30)!important;
            background:rgba(239,68,68,.08)!important;
        }

        .pill-danger::before{
            background:rgba(239,68,68,.95);
        }

        .pill-blue{
            color:#475569!important;
            border-color:rgba(148,163,184,.30)!important;
            background:rgba(148,163,184,.10)!important;
        }

        .pill-blue::before{
            background:rgba(100,116,139,.95);
        }

        .head-actions{
            display:flex!important;
            gap:.5rem!important;
            align-items:center!important;
            flex-wrap:wrap!important;
            justify-content:flex-end!important;
        }

        .head-actions .btn{
            border-radius:7px!important;
            padding:.34rem .78rem!important;
            box-shadow:none!important;
            font-weight:600!important;
            font-size:.82rem!important;
            min-height:32px;
            display:inline-flex;
            align-items:center;
            justify-content:center;
        }

        .head-primary{
            background:var(--shp-accent)!important;
            border-color:var(--shp-accent)!important;
            color:#fff!important;
        }

        .head-primary:hover{
            background:var(--shp-accent-2)!important;
            border-color:var(--shp-accent-2)!important;
            color:#fff!important;
        }

        .head-actions .btn-outline-secondary{
            color:#475569!important;
            background:transparent!important;
            border:1px solid rgba(148,163,184,.35)!important;
        }

        .head-actions .btn-outline-secondary:hover{
            background:rgba(148,163,184,.08)!important;
            color:#111827!important;
        }

        .rj-content{
            padding-top:.65rem!important;
        }

        @media (max-width:767.98px){
            .page-wrap{
                padding:.5rem .5rem 4rem!important;
            }

            .rj-topbar{
                margin-inline:-.5rem!important;
                padding:.5rem .65rem!important;
            }

            .page-title{
                font-size:1.05rem!important;
            }

            .compact-summary{
                display:none!important;
            }

            .head-actions{
                width:100%!important;
                display:grid!important;
                grid-template-columns:1fr 1fr!important;
                gap:.45rem!important;
            }

            .head-actions .btn{
                min-height:40px!important;
                width:100%!important;
            }
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
        $setorRejectUrl = route('production.sewing.returns.create', ['source' => 'reject-sewing']);
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

        <div class="gf-card rj-topbar mb-0">
            <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
                <div>
                    <h1 class="page-title">Reject Jahit</h1>
                    <div class="compact-summary mono">
                        <span class="pill pill-danger">{{ $fmt($totalRemaining) }} pcs sisa</span>
                        <span class="pill pill-blue">{{ $fmt($totalRows) }} baris</span>
                    </div>
                </div>
                <div class="head-actions">
                    <a href="{{ $setorRejectUrl }}" class="btn btn-sm head-primary">Setor Reject</a>
                    <a href="{{ route('production.sewing.returns.create') }}" class="btn btn-sm btn-outline-secondary">Setor Normal</a>
                    <a href="{{ route('production.sewing.returns.index') }}" class="btn btn-sm btn-outline-secondary d-none d-md-inline-flex">Riwayat</a>
                </div>
            </div>
        </div>

        <div class="rj-content">
            <form method="get">
                <div class="gf-card p-2">
                    <div class="row g-2 align-items-end">
                        <div class="col-12 col-md-4">
                            <div class="filter-label mb-1">Operator</div>
                            <select name="operator_id" class="form-select form-select-sm">
                                <option value="">Semua</option>
                                @foreach ($operators as $op)
                                    <option value="{{ $op->id }}" @selected((string) $filters['operator_id'] === (string) $op->id)>
                                        {{ $op->code }} — {{ $op->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12 col-md-5">
                            <div class="filter-label mb-1">Cari</div>
                            <input type="text" name="q" value="{{ $filters['q'] }}" class="form-control form-control-sm" placeholder="SKU / barang / operator">
                        </div>
                        <div class="col-12 col-md-3 d-flex gap-2">
                            <button type="submit" class="btn btn-sm btn-secondary flex-fill">Filter</button>
                            @if (array_filter($filters))
                                <a href="{{ route('production.sewing.reject_returns.index') }}" class="btn btn-sm btn-outline-secondary">Reset</a>
                            @endif
                        </div>
                    </div>
                </div>
            </form>

            <div class="gf-card rj-table-card mt-3">
                @if (($recoverableConversions ?? collect())->isNotEmpty())
                    <div class="rj-table-head">
                        <h2 class="h6 mb-0" style="font-weight:600;">Konversi Reject yang Bisa Dikembalikan</h2>
                        <div class="small muted mono">WH-RTS → REJ-SEW</div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Konversi</th>
                                    <th>SKU Asal</th>
                                    <th>SKU Reject</th>
                                    <th class="text-end">Sisa</th>
                                    <th class="text-end">Qty Kembali</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($recoverableConversions as $conversion)
                                    <tr>
                                        <td class="mono">{{ $conversion->code }}<br><span class="small muted">{{ optional($conversion->date)->format('d M Y') }}</span></td>
                                        <td><strong class="mono">{{ $conversion->item?->code }}</strong><br><span class="small muted">{{ $conversion->item?->name }}</span></td>
                                        <td><strong class="mono">{{ $conversion->rejectItem?->code }}</strong><br><span class="small muted">WH-RTS</span></td>
                                        <td class="text-end mono fw-bold text-danger">{{ $fmt($conversion->remaining_qty) }}</td>
                                        <td class="text-end">
                                            <form method="POST" action="{{ route('production.sewing.reject_returns.recover') }}" class="d-flex justify-content-end gap-1">
                                                @csrf
                                                <input type="hidden" name="conversion_id" value="{{ $conversion->id }}">
                                                <input type="hidden" name="date" value="{{ now()->toDateString() }}">
                                                <input type="number" name="qty" class="form-control form-control-sm mono text-end" style="max-width:110px" min="0.001" max="{{ (float) $conversion->remaining_qty }}" step="0.001" value="{{ (float) $conversion->remaining_qty }}" required>
                                                <button type="submit" class="btn btn-sm btn-warning">Kembalikan</button>
                                            </form>
                                        </td>
                                        <td></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            <div class="gf-card rj-table-card mt-3">
                <div class="rj-table-head">
                    <h2 class="h6 mb-0" style="font-weight:600;">Sisa Reject</h2>
                    <div class="small muted mono d-none d-md-block">{{ $fmt($totalReworked) }} sudah setor</div>
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
                                    <div class="small mono muted">{{ \Carbon\Carbon::parse($r->reject_date)->format('d M Y') }}</div>
                                    <div class="small mono muted text-truncate" style="max-width:110px;">{{ $r->reject_code }}</div>
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
                                    <a href="{{ $url }}" class="btn btn-sm btn-secondary btn-rework" onclick="event.stopPropagation()">Setor</a>
                                    <button type="button"
                                            class="btn btn-sm btn-outline-danger btn-convert ms-1"
                                            data-convert-btn
                                            data-source-kind="{{ $r->source_kind }}"
                                            data-line-id="{{ $r->line_id }}"
                                            data-sku="{{ $r->sku }}"
                                            data-target-sku="{{ $r->reject_sku }}"
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

            <div class="d-grid gap-2 d-md-none p-2">
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
                            <span class="mono">{{ $r->reject_code }}</span> · {{ \Carbon\Carbon::parse($r->reject_date)->format('d M Y') }}
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
                                        data-target-sku="{{ $r->reject_sku }}"
                                        data-remaining="{{ (float) $r->remaining_qty }}"
                                        data-source-label="{{ $r->source_label }}"
                                        onclick="event.stopPropagation()">
                                    Tidak Bisa
                                </button>
                                <a href="{{ $url }}" class="btn btn-sm btn-secondary btn-rework" onclick="event.stopPropagation()">Setor</a>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="text-center text-muted py-4">Tidak ada reject jahit yang perlu disetor ulang.</div>
                @endforelse
            </div>

            @if ($rejects->hasPages())
                <div class="p-3 border-top" style="border-color:rgba(148,163,184,.14)!important;">{{ $rejects->links() }}</div>
            @endif
        </div>
        </div>
    </div>

    <div class="modal fade" id="convertRejectModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <form method="POST" action="{{ route('production.sewing.reject_returns.convert') }}" class="modal-content">
                @csrf
                <div class="modal-header py-2">
                    <h5 class="modal-title mb-0" style="font-size:.95rem; font-weight:600;">Tidak Bisa</h5>
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
                        <div class="small muted mt-1">Target <span class="mono" id="convert-target-sku">-</span> · WH-RTS</div>
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
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-sm btn-danger">Simpan</button>
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
            const targetSkuEl = document.getElementById('convert-target-sku');
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
                    targetSkuEl.textContent = btn.dataset.targetSku || '-';
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
