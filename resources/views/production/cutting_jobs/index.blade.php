{{-- resources/views/production/cutting_jobs/index.blade.php --}}
@extends('layouts.app')

@section('title', 'Produksi • Cutting Jobs')

@push('head')
<style>
.page-wrap {
    max-width: 1140px;
    margin-inline: auto;
    padding: .75rem .75rem 3rem;
}

/* ── KPI bar ── */
.cj-kpi-row {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(130px, 1fr));
    gap: .5rem;
    margin-bottom: .75rem;
}
.cj-kpi {
    background: var(--card);
    border: 1px solid var(--line);
    border-radius: 12px;
    padding: .55rem .75rem;
}
.cj-kpi-label { font-size: .68rem; text-transform: uppercase; letter-spacing: .07em; color: var(--muted); }
.cj-kpi-value { font-size: 1.35rem; font-weight: 800; line-height: 1.1; font-variant-numeric: tabular-nums; }

/* ── Filter bar ── */
.cj-filter-bar {
    display: flex;
    gap: .4rem;
    flex-wrap: wrap;
    align-items: center;
    margin-bottom: .75rem;
}
.cj-filter-btn {
    border-radius: 999px;
    font-size: .72rem;
    font-weight: 700;
    padding: .2rem .75rem;
    border: 1px solid var(--line);
    background: transparent;
    color: var(--muted);
    cursor: pointer;
    transition: background 100ms, color 100ms, border-color 100ms;
    text-decoration: none;
}
.cj-filter-btn:hover { background: rgba(37,99,235,.07); color: #2563eb; border-color: rgba(37,99,235,.3); }
.cj-filter-btn.active { background: #2563eb; color: #fff; border-color: #2563eb; }
.cj-filter-btn.active-void { background: #dc2626; color: #fff; border-color: #dc2626; }
.cj-filter-btn.active-qc { background: #16a34a; color: #fff; border-color: #16a34a; }
.cj-filter-btn.active-mixed { background: #d97706; color: #fff; border-color: #d97706; }

/* ── Table ── */
.mono { font-variant-numeric: tabular-nums; font-family: ui-monospace, SFMono-Regular, Menlo, Consolas; }
.cj-table thead th {
    font-size: .7rem;
    text-transform: uppercase;
    letter-spacing: .07em;
    color: var(--muted);
    background: rgba(15,23,42,.02);
    border-bottom-width: 1px;
    white-space: nowrap;
}
.cj-table tbody td { vertical-align: middle; border-top-color: rgba(148,163,184,.15); }
.cj-table tbody tr:hover { background: rgba(37,99,235,.03); }

/* ── Status pills ── */
.sp {
    display: inline-flex;
    align-items: center;
    border-radius: 999px;
    padding: .14rem .6rem;
    font-size: .68rem;
    font-weight: 700;
    letter-spacing: .06em;
    text-transform: uppercase;
    white-space: nowrap;
}
.sp-draft    { background: rgba(148,163,184,.15); color: #4b5563; }
.sp-cut      { background: rgba(37,99,235,.12);   color: #1d4ed8; }
.sp-qc       { background: rgba(22,163,74,.12);   color: #15803d; }
.sp-mixed    { background: rgba(245,158,11,.12);  color: #92400e; }
.sp-reject   { background: rgba(239,68,68,.12);   color: #b91c1c; }
.sp-sent     { background: rgba(8,145,178,.12);   color: #0f766e; }
.sp-void     { background: rgba(220,38,38,.1);    color: #dc2626; border: 1px solid rgba(220,38,38,.25); }

/* ── Code pill ── */
.cj-code {
    font-family: ui-monospace, SFMono-Regular, Menlo, Consolas;
    font-size: .75rem;
    font-weight: 700;
    color: #1e40af;
    background: rgba(37,99,235,.07);
    border-radius: 6px;
    padding: .08rem .4rem;
    white-space: nowrap;
}
body[data-theme="dark"] .cj-code { color: #93c5fd; background: rgba(37,99,235,.15); }

/* ── Secondary text ── */
.text-sub { font-size: .72rem; color: var(--muted); margin-top: .1rem; }

/* ── Action buttons ── */
.cj-actions { display: flex; gap: .3rem; align-items: center; justify-content: flex-end; flex-wrap: nowrap; }
.cj-btn {
    border-radius: 999px;
    font-size: .72rem;
    font-weight: 700;
    padding: .22rem .7rem;
    white-space: nowrap;
    border: 1px solid var(--line);
    background: transparent;
    color: var(--muted);
    text-decoration: none;
    transition: background 100ms, color 100ms;
}
.cj-btn:hover  { background: rgba(37,99,235,.08); color: #2563eb; border-color: rgba(37,99,235,.3); }
.cj-btn-primary { background: #2563eb; color: #fff; border-color: #2563eb; }
.cj-btn-primary:hover { background: #1d4ed8; color: #fff; }

/* ── Mobile cards ── */
@media (max-width: 767.98px) {
    .cj-kpi-value { font-size: 1.1rem; }

    .cj-card {
        background: var(--card);
        border: 1px solid var(--line);
        border-radius: 14px;
        padding: .65rem .8rem;
        cursor: pointer;
        transition: transform 80ms, box-shadow 80ms;
    }
    .cj-card:active { transform: scale(.99); }
    .cj-card + .cj-card { margin-top: .5rem; }
    .cj-card-header { display: flex; justify-content: space-between; align-items: center; gap: .4rem; margin-bottom: .35rem; }
    .cj-card-code { font-family: ui-monospace, SFMono-Regular, Menlo, Consolas; font-size: .78rem; font-weight: 700; color: #1e40af; }
    body[data-theme="dark"] .cj-card-code { color: #93c5fd; }
    .cj-card-meta { font-size: .73rem; color: var(--muted); display: flex; flex-wrap: wrap; gap: .2rem .5rem; margin-top: .22rem; }
    .cj-card-fabric {
        font-size: .78rem;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: .25rem;
        background: rgba(15,23,42,.04);
        border: 1px solid rgba(148,163,184,.3);
        border-radius: 8px;
        padding: .1rem .45rem;
        max-width: 100%;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
}
</style>
@endpush

@section('content')
@php
    $user       = auth()->user();
    $role       = $user?->role ?? null;
    $isOwner    = $role === 'owner';

    $currentStatus    = request('status', '');
    $currentWarehouse = request('warehouse_id', '');

    // Status map — shared
    $statusMap = [
        'draft'          => ['label' => 'DRAFT',      'class' => 'sp-draft',  'hint' => 'Belum proses'],
        'cut'            => ['label' => 'CUTTING',    'class' => 'sp-cut',    'hint' => 'Sudah cutting, belum QC'],
        'cut_sent_to_qc' => ['label' => 'KIRIM QC',  'class' => 'sp-sent',   'hint' => 'Menunggu QC'],
        'sent_to_qc'     => ['label' => 'KIRIM QC',  'class' => 'sp-sent',   'hint' => 'Menunggu QC'],
        'qc_ok'          => ['label' => 'QC OK',      'class' => 'sp-qc',     'hint' => 'QC selesai OK'],
        'qc_done'        => ['label' => 'QC SELESAI', 'class' => 'sp-qc',     'hint' => 'QC selesai'],
        'qc_mixed'       => ['label' => 'QC MIXED',   'class' => 'sp-mixed',  'hint' => 'Ada OK & reject'],
        'qc_reject'      => ['label' => 'QC REJECT',  'class' => 'sp-reject', 'hint' => 'Banyak reject'],
        'voided'         => ['label' => 'VOID',        'class' => 'sp-void',   'hint' => 'Dibatalkan'],
    ];

    // KPI — dari $kpis controller (seluruh data, akurat)
    $totalAll     = $jobs->total();
    $totalCut     = ($kpis['cut'] ?? 0) + ($kpis['draft'] ?? 0);
    $totalPending = ($kpis['sent_to_qc'] ?? 0) + ($kpis['cut_sent_to_qc'] ?? 0);
    $totalQcDone  = ($kpis['qc_ok'] ?? 0) + ($kpis['qc_done'] ?? 0) + ($kpis['qc_mixed'] ?? 0) + ($kpis['qc_reject'] ?? 0);
    $totalVoided  = $kpis['voided'] ?? 0;
@endphp

<div class="page-wrap">

    {{-- ── FLASH ── --}}
    @if (session('success'))
        <div class="alert alert-success py-2 px-3 mb-3">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger py-2 px-3 mb-3">{{ session('error') }}</div>
    @endif

    {{-- ── HEADER ── --}}
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
            <h1 class="h5 mb-0">✂️ Cutting Jobs</h1>
            <div class="text-sub">Semua cutting job produksi</div>
        </div>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            @if (auth()->user()?->role === 'owner')
                <form method="POST" action="{{ route('production.cutting_jobs.dev_clean_production') }}" id="cleanProdForm">
                    @csrf
                    <input type="hidden" name="confirm_text" id="cleanProdConfirm">
                    <input type="hidden" name="confirm_password" id="cleanProdPassword">
                    <button type="button" id="cleanProdBtn" class="btn btn-outline-danger btn-sm"
                            style="border-radius:999px;font-weight:700"
                            title="Owner — hapus semua transaksi produksi & hitung ulang stok (ketik frasa + password owner)">
                        🧹 Bersihkan Data Produksi
                    </button>
                </form>
                <script>
                (function () {
                function cleanProdConfirm() {
                    var EXP = @json(\App\Http\Controllers\Production\CuttingJobController::CLEAN_PROD_PHRASE);
                    function go(phrase, pass) {
                        document.getElementById('cleanProdConfirm').value = phrase;
                        document.getElementById('cleanProdPassword').value = pass;
                        document.getElementById('cleanProdForm').submit();
                    }
                    var body = '⚠️ <b>HAPUS SEMUA</b> transaksi produksi (cutting, sewing, QC, finishing, packing, mutasi &amp; jurnal produksi), lalu stok kain &amp; lot dikembalikan seperti sebelum produksi. Master data, GRN, dan stock opname <b>TIDAK</b> disentuh.<br><br>Backup DB dibuat otomatis dulu. <b>Tidak bisa dibatalkan.</b> Ketik <code>' + EXP + '</code> untuk lanjut.';
                    if (!window.Swal) {
                        var t = prompt('Ketik "' + EXP + '" untuk HAPUS SEMUA data transaksi produksi:');
                        if (!t || t.trim().toUpperCase() !== EXP) return;
                        var p = prompt('Masukkan password owner untuk konfirmasi:');
                        if (p) go(t, p);
                        return;
                    }
                    // Langkah 1: ketik frasa
                    window.Swal.fire({
                        icon: 'warning',
                        title: 'Bersihkan Data Produksi?',
                        html: body,
                        input: 'text',
                        inputPlaceholder: EXP,
                        showCancelButton: true,
                        confirmButtonText: 'Lanjut',
                        cancelButtonText: 'Batal',
                        confirmButtonColor: '#dc2626',
                        inputValidator: function (v) {
                            return (String(v || '').trim().toUpperCase() !== EXP)
                                ? ('Ketik persis: ' + EXP) : undefined;
                        },
                    }).then(function (r1) {
                        if (!r1.isConfirmed) return;
                        var phrase = r1.value;
                        // Langkah 2: password owner
                        window.Swal.fire({
                            icon: 'warning',
                            title: 'Konfirmasi Password Owner',
                            text: 'Masukkan password owner untuk menghapus data produksi.',
                            input: 'password',
                            inputPlaceholder: 'Password owner',
                            inputAttributes: { autocomplete: 'off' },
                            showCancelButton: true,
                            confirmButtonText: 'Hapus Sekarang',
                            cancelButtonText: 'Batal',
                            confirmButtonColor: '#dc2626',
                            inputValidator: function (v) {
                                return (!v) ? 'Password wajib diisi.' : undefined;
                            },
                        }).then(function (r2) {
                            if (r2.isConfirmed) go(phrase, r2.value);
                        });
                    });
                }

                // Bind lewat addEventListener (bukan inline onclick) supaya andal
                // walau ada CSP / urutan eksekusi script.
                var _cpb = document.getElementById('cleanProdBtn');
                if (_cpb) _cpb.addEventListener('click', cleanProdConfirm);
                })();
                </script>
            @endif
            <a href="{{ route('production.cutting_jobs.create') }}" class="btn btn-primary btn-sm" style="border-radius:999px;font-weight:700">
                + Cutting Job Baru
            </a>
        </div>
    </div>

    {{-- ── KPI ── --}}
    <div class="cj-kpi-row">
        <div class="cj-kpi">
            <div class="cj-kpi-label">Total</div>
            <div class="cj-kpi-value">{{ number_format($totalAll) }}</div>
        </div>
        <div class="cj-kpi">
            <div class="cj-kpi-label">Cutting</div>
            <div class="cj-kpi-value" style="color:#1d4ed8">{{ $totalCut }}</div>
        </div>
        <div class="cj-kpi">
            <div class="cj-kpi-label">Menunggu QC</div>
            <div class="cj-kpi-value" style="color:#0f766e">{{ $totalPending }}</div>
        </div>
        <div class="cj-kpi">
            <div class="cj-kpi-label">QC Selesai</div>
            <div class="cj-kpi-value" style="color:#15803d">{{ $totalQcDone }}</div>
        </div>
        @if ($totalVoided > 0)
        <div class="cj-kpi">
            <div class="cj-kpi-label">Void</div>
            <div class="cj-kpi-value" style="color:#dc2626">{{ $totalVoided }}</div>
        </div>
        @endif
    </div>

    {{-- ── FILTER BAR ── --}}
    <div class="cj-filter-bar">
        <a href="{{ route('production.cutting_jobs.index', $currentWarehouse ? ['warehouse_id' => $currentWarehouse] : []) }}"
            class="cj-filter-btn {{ $currentStatus === '' ? 'active' : '' }}">
            Semua
        </a>
        <a href="{{ route('production.cutting_jobs.index', array_filter(['status' => 'cut', 'warehouse_id' => $currentWarehouse])) }}"
            class="cj-filter-btn {{ $currentStatus === 'cut' ? 'active' : '' }}">
            Cutting
        </a>
        <a href="{{ route('production.cutting_jobs.index', array_filter(['status' => 'sent_to_qc', 'warehouse_id' => $currentWarehouse])) }}"
            class="cj-filter-btn {{ in_array($currentStatus, ['sent_to_qc','cut_sent_to_qc']) ? 'active' : '' }}">
            Menunggu QC
        </a>
        <a href="{{ route('production.cutting_jobs.index', array_filter(['status' => 'qc_done', 'warehouse_id' => $currentWarehouse])) }}"
            class="cj-filter-btn {{ in_array($currentStatus, ['qc_done','qc_ok','qc_mixed','qc_reject']) ? 'active-qc' : '' }}">
            QC Selesai
        </a>
        <a href="{{ route('production.cutting_jobs.index', array_filter(['status' => 'voided', 'warehouse_id' => $currentWarehouse])) }}"
            class="cj-filter-btn {{ $currentStatus === 'voided' ? 'active-void' : '' }}">
            Void
        </a>

        @if ($warehouses->isNotEmpty())
        <span style="color:var(--line);margin:0 .1rem">|</span>
        <select class="cj-filter-btn" style="padding:.2rem .5rem;cursor:pointer"
            onchange="location.href=this.value">
            <option value="{{ route('production.cutting_jobs.index', array_filter(['status' => $currentStatus])) }}"
                {{ $currentWarehouse === '' ? 'selected' : '' }}>
                Semua Gudang
            </option>
            @foreach ($warehouses as $wh)
            <option value="{{ route('production.cutting_jobs.index', array_filter(['status' => $currentStatus, 'warehouse_id' => $wh->id])) }}"
                {{ $currentWarehouse == $wh->id ? 'selected' : '' }}>
                {{ $wh->code }} — {{ $wh->name }}
            </option>
            @endforeach
        </select>
        @endif
    </div>

    {{-- ── TABLE (DESKTOP) ── --}}
    <div class="d-none d-md-block" style="overflow-x:auto">
        <table class="table table-sm cj-table mono mb-0">
            <thead>
                <tr>
                    <th style="width:145px">Kode</th>
                    <th style="width:110px">Tanggal</th>
                    <th style="width:130px">Operator</th>
                    <th>Item Kain</th>
                    <th style="width:90px" class="text-end">Iket</th>
                    <th style="width:100px" class="text-end">Qty (pcs)</th>
                    <th style="width:120px">Status</th>
                    <th style="width:140px"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($jobs as $job)
                    @php
                        $st  = $job->status ?? 'draft';
                        $cfg = $statusMap[$st] ?? ['label' => strtoupper($st), 'class' => 'sp-draft', 'hint' => ''];
                        $bundleCount = $job->bundles_count ?? 0;
                        $qtyPcs      = (float) ($job->bundles_sum_qty_pcs ?? $job->bundles->sum('qty_pcs'));
                        $isQcDone    = in_array($st, ['qc_ok','qc_done','qc_mixed','qc_reject'], true);
                        $isVoid      = $st === 'voided';
                        $detailUrl   = route('production.cutting_jobs.show', $job);
                        $qcUrl       = Route::has('production.qc.cutting.edit')
                            ? route('production.qc.cutting.edit', $job) : $detailUrl;
                    @endphp
                    <tr class="{{ $isVoid ? 'opacity-50' : '' }}">
                        <td>
                            <span class="cj-code">{{ $job->code }}</span>
                        </td>
                        <td>{{ $job->date?->format('d M Y') ?? '-' }}</td>
                        <td>{{ $job->operator?->name ?? '-' }}</td>
                        <td>
                            {{ $job->lot?->item?->name ?? '-' }}
                            @if($job->lot?->code)
                                <div class="text-sub">{{ $job->lot->code }}</div>
                            @endif
                        </td>
                        <td class="text-end">{{ $bundleCount }}</td>
                        <td class="text-end">{{ $qtyPcs > 0 ? number_format($qtyPcs, 0, ',', '.') : '-' }}</td>
                        <td>
                            <span class="sp {{ $cfg['class'] }}" title="{{ $cfg['hint'] }}">{{ $cfg['label'] }}</span>
                        </td>
                        <td>
                            <div class="cj-actions">
                                <a href="{{ $detailUrl }}" class="cj-btn">Detail</a>
                                @if (!$isVoid && !$isQcDone)
                                    <a href="{{ $qcUrl }}" class="cj-btn cj-btn-primary">Input QC</a>
                                @elseif ($isQcDone)
                                    <a href="{{ $qcUrl }}" class="cj-btn cj-btn-primary">Lihat QC</a>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted py-5 small">
                            Tidak ada cutting job{{ $currentStatus ? ' dengan status ini' : '' }}.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- ── CARD LIST (MOBILE) ── --}}
    <div class="d-block d-md-none">
        @forelse ($jobs as $job)
            @php
                $st          = $job->status ?? 'draft';
                $cfg         = $statusMap[$st] ?? ['label' => strtoupper($st), 'class' => 'sp-draft'];
                $bundleCount = $job->bundles_count ?? 0;
                $qtyPcs      = (float) ($job->bundles_sum_qty_pcs ?? $job->bundles->sum('qty_pcs'));
                $isQcDone    = in_array($st, ['qc_ok','qc_done','qc_mixed','qc_reject'], true);
                $isVoid      = $st === 'voided';
                $href        = (!$isVoid && !$isQcDone && Route::has('production.qc.cutting.edit'))
                    ? route('production.qc.cutting.edit', $job)
                    : route('production.cutting_jobs.show', $job);
            @endphp
            <div class="cj-card {{ $isVoid ? 'opacity-50' : '' }}" onclick="location.href='{{ $href }}'">
                <div class="cj-card-header">
                    <span class="cj-card-code">{{ $job->code }}</span>
                    <span class="sp {{ $cfg['class'] }}">{{ $cfg['label'] }}</span>
                </div>
                @if ($job->lot?->item)
                    <div class="mb-1">
                        <span class="cj-card-fabric">{{ $job->lot->item->name }}</span>
                    </div>
                @endif
                <div class="cj-card-meta">
                    <span>{{ $job->date?->format('d M Y') }}</span>
                    @if ($job->operator)
                        <span>{{ $job->operator->name }}</span>
                    @endif
                    <span>{{ $bundleCount }} iket</span>
                    @if ($qtyPcs > 0)
                        <span>{{ number_format($qtyPcs, 0, ',', '.') }} pcs</span>
                    @endif
                </div>
            </div>
        @empty
            <div class="text-center text-muted small py-4">
                Tidak ada cutting job{{ $currentStatus ? ' dengan status ini' : '' }}.
            </div>
        @endforelse
    </div>

    {{-- ── PAGINATION ── --}}
    <div class="mt-3">
        {{ $jobs->appends(request()->query())->links() }}
    </div>

</div>
@endsection
