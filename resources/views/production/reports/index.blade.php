@extends('layouts.app')

@section('title', 'Produksi • Laporan Produksi')

@push('head')
    <style>
        .pr-wrap {
            max-width: 1280px;
            margin: 0 auto;
            padding: 14px 12px 96px;
        }

        .pr-card {
            background: var(--card);
            border: 1px solid rgba(148, 163, 184, .22);
            border-radius: 14px;
            box-shadow: 0 10px 26px rgba(15, 23, 42, .08);
        }

        .pr-input {
            padding: .45rem .6rem;
            border: 1px solid rgba(148, 163, 184, .35);
            border-radius: 10px;
            background: var(--card);
            color: inherit;
            font-size: .85rem;
            width: 100%;
        }

        .pr-label {
            font-size: .72rem;
            font-weight: 700;
            color: #6b7280;
            margin-bottom: .15rem;
            display: block;
        }

        .pr-pill {
            font-size: .68rem;
            font-weight: 800;
            padding: .12rem .5rem;
            border-radius: 999px;
            background: rgba(148, 163, 184, .16);
            white-space: nowrap;
        }

        .pr-kpi {
            border: 1px solid rgba(148, 163, 184, .22);
            border-radius: 12px;
            padding: .7rem .9rem;
            background: var(--card);
        }

        .pr-kpi .v {
            font-size: 1.25rem;
            font-weight: 800;
            line-height: 1.1;
        }

        .pr-kpi .l {
            font-size: .7rem;
            font-weight: 700;
            color: #6b7280;
            text-transform: uppercase;
        }

        .pr-kpi.a-blue {
            border-left: 3px solid #2563eb;
        }

        .pr-kpi.a-amber {
            border-left: 3px solid #d97706;
        }

        .pr-kpi.a-violet {
            border-left: 3px solid #7c3aed;
        }

        .pr-kpi.a-green {
            border-left: 3px solid #059669;
        }
    </style>
@endpush

@section('content')
    <div class="pr-wrap">

        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <h1 class="h5 mb-0 fw-bold">Laporan Produksi</h1>
            <a href="{{ route('production.dashboard') }}" class="btn btn-sm btn-outline-secondary">← Dashboard</a>
        </div>

        {{-- FILTER --}}
        <div class="pr-card p-3 mb-3">
            <form method="GET" id="prFilter" class="row g-2 align-items-end">
                <div class="col-6 col-md-2">
                    <label class="pr-label">Dari</label>
                    <input type="date" name="date_from" class="pr-input" value="{{ $filters['date_from'] }}">
                </div>
                <div class="col-6 col-md-2">
                    <label class="pr-label">Sampai</label>
                    <input type="date" name="date_to" class="pr-input" value="{{ $filters['date_to'] }}">
                </div>
                <div class="col-6 col-md-2">
                    <label class="pr-label">Ke status</label>
                    <select name="to_status" class="pr-input">
                        <option value="">Semua</option>
                        @foreach ($statuses as $slug => $s)
                            <option value="{{ $slug }}" @selected($filters['to_status'] === $slug)>{{ $s['label'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="pr-label">Produk (Kategori)</label>
                    <select name="category_id" class="pr-input">
                        <option value="">Semua</option>
                        @foreach ($categoryOptions as $cat)
                            <option value="{{ $cat->id }}" @selected($filters['category_id'] == $cat->id)>{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="pr-label">SKU (Varian)</label>
                    <select name="item_id" class="pr-input">
                        <option value="">Semua</option>
                        @foreach ($itemOptions as $it)
                            <option value="{{ $it->id }}" @selected($filters['item_id'] == $it->id)>{{ $it->code }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="pr-label">Penjahit</label>
                    <select name="operator_id" class="pr-input">
                        <option value="">Semua</option>
                        @foreach ($operatorOptions as $op)
                            <option value="{{ $op->id }}" @selected($filters['operator_id'] == $op->id)>{{ $op->code }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 d-flex gap-1 flex-wrap">
                    <button type="submit" class="btn btn-outline-primary">Terapkan Filter</button>
                    <a href="{{ route('production.reports.index') }}" class="btn btn-outline-secondary">Reset</a>
                    <span class="ms-auto"></span>
                    <a id="btnCsv" href="{{ route('production.reports.export', array_merge($filters, ['format' => 'csv'])) }}"
                        class="btn btn-success">⤓ CSV</a>
                    <a id="btnXlsx" href="{{ route('production.reports.export', array_merge($filters, ['format' => 'xlsx'])) }}"
                        class="btn btn-outline-success">⤓ XLSX</a>
                </div>
            </form>
        </div>

        {{-- KPI TOTAL PERIODE --}}
        <div class="row g-2 mb-3">
            <div class="col-6 col-md-3">
                <div class="pr-kpi a-amber">
                    <div class="v">{{ number_format($totals['siap_jahit'], 0, ',', '.') }}</div>
                    <div class="l">→ Siap Jahit</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="pr-kpi a-blue">
                    <div class="v">{{ number_format($totals['sedang_jahit'], 0, ',', '.') }}</div>
                    <div class="l">→ Sedang Jahit</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="pr-kpi a-violet">
                    <div class="v">{{ number_format($totals['wh_prd'], 0, ',', '.') }}</div>
                    <div class="l">→ WH-PRD</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="pr-kpi a-green">
                    <div class="v">{{ number_format($totals['ready'], 0, ',', '.') }}</div>
                    <div class="l">→ Ready Stock</div>
                </div>
            </div>
        </div>

        {{-- TABEL REKAP --}}
        <div class="pr-card p-3">
            <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
                <h2 class="h6 mb-0">Rekap Throughput per SKU ({{ $recap->count() }} SKU)</h2>
                <span class="small text-muted">
                    {{ \Carbon\Carbon::parse($filters['date_from'])->format('d M Y') }}
                    – {{ \Carbon\Carbon::parse($filters['date_to'])->format('d M Y') }}
                    · {{ number_format($totals['moves'], 0, ',', '.') }} mutasi
                </span>
            </div>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead>
                        <tr class="text-uppercase small text-muted">
                            <th style="width:42px">#</th>
                            <th>SKU / Produk</th>
                            <th class="text-end">→ Siap Jahit</th>
                            <th class="text-end">→ Sedang Jahit</th>
                            <th class="text-end">→ WH-PRD</th>
                            <th class="text-end">→ Ready</th>
                            <th class="text-end">Total Qty</th>
                            <th class="text-end">Mutasi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($recap as $i => $r)
                            <tr>
                                <td class="text-muted">{{ $i + 1 }}</td>
                                <td>
                                    <span class="pr-pill">{{ $r->sku }}</span>
                                    <div class="small text-muted">{{ $r->product }}</div>
                                    <div class="small text-muted">{{ $r->category }}</div>
                                </td>
                                <td class="text-end">{{ number_format((float) $r->to_siap_jahit, 0, ',', '.') }}</td>
                                <td class="text-end">{{ number_format((float) $r->to_sedang_jahit, 0, ',', '.') }}</td>
                                <td class="text-end">{{ number_format((float) $r->to_wh_prd, 0, ',', '.') }}</td>
                                <td class="text-end">{{ number_format((float) $r->to_ready, 0, ',', '.') }}</td>
                                <td class="text-end fw-bold">{{ number_format((float) $r->total_qty, 0, ',', '.') }}</td>
                                <td class="text-end text-muted">{{ number_format((int) $r->moves, 0, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">
                                    Belum ada mutasi produksi pada periode/filter ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if ($recap->isNotEmpty())
                        <tfoot>
                            <tr class="fw-bold border-top">
                                <td></td>
                                <td>TOTAL</td>
                                <td class="text-end">{{ number_format($totals['siap_jahit'], 0, ',', '.') }}</td>
                                <td class="text-end">{{ number_format($totals['sedang_jahit'], 0, ',', '.') }}</td>
                                <td class="text-end">{{ number_format($totals['wh_prd'], 0, ',', '.') }}</td>
                                <td class="text-end">{{ number_format($totals['ready'], 0, ',', '.') }}</td>
                                <td class="text-end">{{ number_format($totals['total_qty'], 0, ',', '.') }}</td>
                                <td class="text-end">{{ number_format($totals['moves'], 0, ',', '.') }}</td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
            <div class="text-muted small mt-2">
                Sumber: mutasi produksi (production_movements). Tombol CSV/XLSX mengekspor detail per mutasi sesuai filter aktif.
            </div>
        </div>

    </div>

    {{-- Sinkronkan tautan export dengan filter terbaru tanpa harus submit dulu --}}
    <script>
        (function () {
            const form = document.getElementById('prFilter');
            const btnCsv = document.getElementById('btnCsv');
            const btnXlsx = document.getElementById('btnXlsx');
            if (!form || !btnCsv || !btnXlsx) return;

            function syncExport() {
                const params = new URLSearchParams(new FormData(form));
                const base = '{{ route('production.reports.export') }}';
                params.set('format', 'csv');
                btnCsv.href = base + '?' + params.toString();
                params.set('format', 'xlsx');
                btnXlsx.href = base + '?' + params.toString();
            }
            form.addEventListener('change', syncExport);
            syncExport();
        })();
    </script>
@endsection
