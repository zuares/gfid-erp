{{-- resources/views/production/cutting_jobs/index.blade.php --}}
@extends('layouts.app')

@section('title', 'Produksi • Cutting Jobs')

@push('head')
<style>
    :root{
        --shp-accent:#334155;
        --shp-accent-2:#1f2937;
        --shp-border:rgba(148,163,184,.18);
        --shp-border-strong:rgba(148,163,184,.30);
        --shp-muted:#64748b;
    }
    .page-wrap{ max-width:1040px; margin-inline:auto; padding:.75rem .75rem 4rem; background:transparent!important; }

    .card-main{
        background: var(--card);
        border-radius: 8px;
        border: 1px solid var(--shp-border);
        box-shadow: none;
        overflow:hidden;
    }
    body[data-theme="dark"] .card-main{ border-color: rgba(51,65,85,.85); box-shadow:none; }

    .ship-topbar{
        position:sticky; top:0; z-index:300;
        display:flex; justify-content:space-between; align-items:flex-start;
        gap:.6rem; flex-wrap:wrap;
        padding:.45rem .75rem; margin-inline:-.75rem; margin-bottom:.65rem;
        background:var(--card,#fff); border-bottom:1px solid var(--shp-border);
    }
    body[data-theme="dark"] .ship-topbar{ background:var(--card,#0f172a); }
    .title{ font-weight:750; font-size:1rem; letter-spacing:0; margin:0; }
    .sub{ color:var(--shp-muted); font-size:.78rem; }
    body[data-theme="dark"] .sub{ color:#9ca3af; }

    .kpis{ display:flex; flex-wrap:wrap; gap:.32rem; margin-top:.35rem; }
    .kpi{
        display:inline-flex; align-items:baseline; gap:.45rem;
        border-radius:7px; padding:.2rem .48rem;
        border:1px solid rgba(148,163,184,.28); background:transparent; font-size:.72rem;
    }
    body[data-theme="dark"] .kpi{ background:rgba(15,23,42,.96); border-color:rgba(51,65,85,.85); }
    .kpi .lbl{ text-transform:none; letter-spacing:0; font-size:.66rem; color:#94a3b8; }
    body[data-theme="dark"] .kpi .lbl{ color:#6b7280; }
    .kpi .val{ font-weight:650; color:var(--shp-accent); }
    body[data-theme="dark"] .kpi .val{ color:#cbd5e1; }
    .kpi .val.is-cut{ color:#1d4ed8; }
    .kpi .val.is-wait{ color:#0f766e; }
    .kpi .val.is-qc{ color:#15803d; }
    .kpi .val.is-void{ color:#dc2626; }

    .controls{ display:flex; gap:.5rem .6rem; align-items:center; flex-wrap:wrap; justify-content:flex-end; }
    .control-filters{ display:flex; gap:.4rem; align-items:center; flex-wrap:wrap; }
    .control-actions{ display:flex; gap:.4rem; align-items:center; flex-wrap:wrap; }
    .filter-label{ font-size:.8rem; color:#6b7280; }
    body[data-theme="dark"] .filter-label{ color:#9ca3af; }
    .filter-select{ width:auto; min-width:150px; border-radius:7px; padding-left:.7rem; padding-right:2rem; font-size:.82rem; }
    .btn-pill{ border-radius:7px; padding-inline:.78rem; box-shadow:none!important; font-weight:600; }
    .btn-ico{ display:inline-flex; align-items:center; justify-content:center; gap:.3rem; padding-inline:.6rem; min-width:34px; }
    .btn-ico i{ font-size:.95rem; line-height:1; }
    .btn-ship-primary{ background:var(--shp-accent)!important; border-color:var(--shp-accent)!important; color:#fff!important; }
    .btn-ship-primary:hover{ background:var(--shp-accent-2)!important; border-color:var(--shp-accent-2)!important; color:#fff!important; }
    .btn-ship-outline{ color:#475569!important; background:transparent!important; border:1px solid rgba(148,163,184,.35)!important; }
    .btn-ship-outline:hover{ background:rgba(148,163,184,.08)!important; color:#111827!important; }
    .btn-fresh{ border-color:#fecaca; color:#b91c1c; background:transparent; }
    .btn-fresh:hover{ background:#fef2f2; color:#991b1b; border-color:#fca5a5; }

    .table-list{ margin-bottom:0; }
    .table-list thead th{
        border-bottom-width:1px; font-size:.68rem; text-transform:none; letter-spacing:0;
        color:#64748b; background:var(--card,#fff); padding:.52rem .62rem; white-space:nowrap;
    }
    body[data-theme="dark"] .table-list thead th{
        background:rgba(15,23,42,.98); color:#9ca3af; border-bottom-color:rgba(30,64,175,.6);
    }
    .table-list tbody td{ vertical-align:middle; border-top-color:rgba(148,163,184,.16); padding:.52rem .62rem; }
    body[data-theme="dark"] .table-list tbody td{ border-top-color:rgba(51,65,85,.85); }
    .table-list tbody tr.is-void{ opacity:.55; }

    /* Header tabel sticky + area tabel bisa di-scroll (desktop) */
    @media (min-width: 769px){
        .table-scroll{ max-height:calc(100vh - 190px); overflow-y:auto; }
        .table-list thead th{ position:sticky; top:0; z-index:2; box-shadow:inset 0 -1px 0 var(--shp-border); }
        body[data-theme="dark"] .table-list thead th{ box-shadow:inset 0 -1px 0 rgba(30,64,175,.6); }
    }

    /* LOT sebagai badge kecil */
    .lot-badge{
        display:inline-block; margin-top:.18rem;
        font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;
        font-size:.62rem; font-weight:600; color:#475569;
        background:rgba(148,163,184,.14); border:1px solid rgba(148,163,184,.28);
        border-radius:5px; padding:.05rem .34rem; white-space:nowrap; line-height:1.5;
    }
    body[data-theme="dark"] .lot-badge{ color:#cbd5e1; background:rgba(51,65,85,.5); border-color:rgba(51,65,85,.9); }

    .code-link{ font-weight:700; text-decoration:none; color:inherit; font-variant-numeric:tabular-nums; }
    .code-link:hover{ text-decoration:underline; }
    .muted{ font-size:.82rem; color:#6b7280; }
    body[data-theme="dark"] .muted{ color:#9ca3af; }
    .fabric-name{ font-weight:600; }
    .num{ font-variant-numeric:tabular-nums; font-weight:600; }

    .badge-status{
        border-radius:7px; padding:.16rem .48rem; font-size:.68rem;
        letter-spacing:0; text-transform:none; border:1px solid transparent;
        display:inline-flex; align-items:center; gap:.35rem; white-space:nowrap;
    }
    .badge-status::before{ content:''; width:7px; height:7px; border-radius:999px; display:inline-block; }

    .st-draft{ background:rgba(148,163,184,.10); color:#475569; border-color:rgba(148,163,184,.30); }
    .st-draft::before{ background:rgba(100,116,139,.95); }
    .st-cut{ background:rgba(59,130,246,.10); color:#1d4ed8; border-color:rgba(59,130,246,.30); }
    .st-cut::before{ background:rgba(59,130,246,.95); }
    .st-sent{ background:rgba(13,148,136,.10); color:#0f766e; border-color:rgba(13,148,136,.30); }
    .st-sent::before{ background:rgba(13,148,136,.95); }
    .st-qc{ background:rgba(34,197,94,.10); color:#166534; border-color:rgba(34,197,94,.30); }
    .st-qc::before{ background:rgba(34,197,94,.95); }
    .st-mixed{ background:rgba(245,158,11,.12); color:#92400e; border-color:rgba(245,158,11,.30); }
    .st-mixed::before{ background:rgba(245,158,11,.95); }
    .st-reject{ background:rgba(239,68,68,.10); color:#991b1b; border-color:rgba(239,68,68,.30); }
    .st-reject::before{ background:rgba(239,68,68,.95); }
    .st-void{ background:rgba(239,68,68,.10); color:#991b1b; border-color:rgba(239,68,68,.30); }
    .st-void::before{ background:rgba(239,68,68,.95); }

    body[data-theme="dark"] .st-cut{ background:rgba(59,130,246,.20); color:#dbeafe; border-color:rgba(59,130,246,.55); }
    body[data-theme="dark"] .st-sent{ background:rgba(13,148,136,.20); color:#ccfbf1; border-color:rgba(13,148,136,.55); }
    body[data-theme="dark"] .st-qc{ background:rgba(34,197,94,.20); color:#dcfce7; border-color:rgba(34,197,94,.55); }
    body[data-theme="dark"] .st-mixed{ background:rgba(245,158,11,.20); color:#fde68a; border-color:rgba(245,158,11,.55); }
    body[data-theme="dark"] .st-reject,
    body[data-theme="dark"] .st-void{ background:rgba(239,68,68,.18); color:#fecaca; border-color:rgba(239,68,68,.55); }

    .empty{ padding:2.2rem 1.25rem; text-align:center; color:#64748b; }
    body[data-theme="dark"] .empty{ color:#9ca3af; }
    .divider{ height:1px; background:rgba(148,163,184,.20); }
    body[data-theme="dark"] .divider{ background:rgba(51,65,85,.85); }
    .flash-clean{ border-radius:8px; padding:.62rem .75rem; font-size:.84rem; border:1px solid rgba(148,163,184,.25); }

    @media (max-width: 768px){
        .page-wrap{ padding:.5rem .5rem 4rem; }
        .ship-topbar{ margin-inline:-.5rem; padding:.5rem .65rem; }
        .title{ font-size:1.05rem; }
        .sub{ display:none; }
        .controls{ width:100%; align-items:stretch; }
        .control-filters, .control-actions{ width:100%; }
        .control-filters > form, .control-filters > select{ flex:1 1 0; min-width:0; }
        .control-actions > form, .control-actions > a{ flex:1 1 0; }
        .filter-select{ width:100%; min-width:0; min-height:40px; }
        .controls .btn, .controls form button{ width:100%; min-height:40px; }
        .kpis{ display:none; }
        .table-responsive{ overflow:visible; }
        .table-list thead{ display:none; }
        .table-list, .table-list tbody, .table-list tr, .table-list td{ display:block; width:100%; }
        .table-list tbody tr{ padding:.66rem; border-top:1px solid rgba(148,163,184,.16); }
        .table-list tbody td{ border:0; padding:0; }
        .table-list tbody td.mobile-hide{ display:none; }
        .cj-row-main{ display:flex; align-items:flex-start; justify-content:space-between; gap:.75rem; }
        .cj-row-meta{ display:flex; align-items:center; gap:.45rem; flex-wrap:wrap; margin-top:.35rem; color:#64748b; font-size:.78rem; }
        .cj-row-action{ margin-top:.55rem; }
        .cj-row-action .btn{ width:100%; min-height:38px; }
    }
</style>
@endpush

@section('content')
<div class="page-wrap">
    @php
        $user    = auth()->user();
        $isOwner = ($user?->role ?? null) === 'owner';

        $currentStatus    = request('status', '');
        $currentWarehouse = request('warehouse_id', '');

        $statusMap = [
            'draft'          => ['label' => 'Draft',      'class' => 'st-draft',  'hint' => 'Belum proses'],
            'cut'            => ['label' => 'Cutting',    'class' => 'st-cut',    'hint' => 'Sudah cutting, belum QC'],
            'cut_sent_to_qc' => ['label' => 'Kirim QC',   'class' => 'st-sent',   'hint' => 'Menunggu QC'],
            'sent_to_qc'     => ['label' => 'Kirim QC',   'class' => 'st-sent',   'hint' => 'Menunggu QC'],
            'qc_ok'          => ['label' => 'QC OK',      'class' => 'st-qc',     'hint' => 'QC selesai OK'],
            'qc_done'        => ['label' => 'QC Selesai', 'class' => 'st-qc',     'hint' => 'QC selesai'],
            'qc_mixed'       => ['label' => 'QC Mixed',   'class' => 'st-mixed',  'hint' => 'Ada OK & reject'],
            'qc_reject'      => ['label' => 'QC Reject',  'class' => 'st-reject', 'hint' => 'Banyak reject'],
            'voided'         => ['label' => 'Void',       'class' => 'st-void',   'hint' => 'Dibatalkan'],
        ];

        $totalAll     = $jobs->total();
        $totalCut     = ($kpis['cut'] ?? 0) + ($kpis['draft'] ?? 0);
        $totalPending = ($kpis['sent_to_qc'] ?? 0) + ($kpis['cut_sent_to_qc'] ?? 0);
        $totalQcDone  = ($kpis['qc_ok'] ?? 0) + ($kpis['qc_done'] ?? 0) + ($kpis['qc_mixed'] ?? 0) + ($kpis['qc_reject'] ?? 0);
        $totalVoided  = $kpis['voided'] ?? 0;

        // Nilai <select> status disederhanakan jadi grup (selaras filter shipments)
        $statusGroup = $currentStatus === '' ? 'all'
            : (in_array($currentStatus, ['sent_to_qc','cut_sent_to_qc']) ? 'sent_to_qc'
            : (in_array($currentStatus, ['qc_done','qc_ok','qc_mixed','qc_reject']) ? 'qc_done'
            : $currentStatus));
    @endphp

    @if (session('success'))
        <div class="flash-clean alert alert-success mb-2">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="flash-clean alert alert-danger mb-2">{{ session('error') }}</div>
    @endif

    <div class="ship-topbar">
        <div>
            <div class="title">Cutting Jobs</div>

            <div class="kpis">
                <span class="kpi"><span class="lbl">Total</span><span class="val">{{ number_format($totalAll, 0, ',', '.') }}</span></span>
                <span class="kpi"><span class="lbl">Cutting</span><span class="val is-cut">{{ number_format($totalCut, 0, ',', '.') }}</span></span>
                <span class="kpi"><span class="lbl">Menunggu QC</span><span class="val is-wait">{{ number_format($totalPending, 0, ',', '.') }}</span></span>
                <span class="kpi"><span class="lbl">QC Selesai</span><span class="val is-qc">{{ number_format($totalQcDone, 0, ',', '.') }}</span></span>
                @if ($totalVoided > 0)
                    <span class="kpi"><span class="lbl">Void</span><span class="val is-void">{{ number_format($totalVoided, 0, ',', '.') }}</span></span>
                @endif
            </div>
        </div>

        <div class="controls">
            {{-- Grup filter --}}
            <div class="control-filters">
                <form method="GET" class="m-0">
                    @if ($currentWarehouse !== '')
                        <input type="hidden" name="warehouse_id" value="{{ $currentWarehouse }}">
                    @endif
                    <select name="status" class="form-select form-select-sm filter-select" onchange="this.form.submit()">
                        <option value="" {{ $statusGroup === 'all' ? 'selected' : '' }}>Status</option>
                        <option value="cut" {{ $statusGroup === 'cut' ? 'selected' : '' }}>Cutting</option>
                        <option value="sent_to_qc" {{ $statusGroup === 'sent_to_qc' ? 'selected' : '' }}>Menunggu QC</option>
                        <option value="qc_done" {{ $statusGroup === 'qc_done' ? 'selected' : '' }}>QC Selesai</option>
                        <option value="voided" {{ $statusGroup === 'voided' ? 'selected' : '' }}>Void</option>
                    </select>
                </form>

                @if ($warehouses->isNotEmpty())
                    <select class="form-select form-select-sm filter-select" onchange="location.href=this.value">
                        <option value="{{ route('production.cutting_jobs.index', array_filter(['status' => $currentStatus])) }}"
                            {{ $currentWarehouse === '' ? 'selected' : '' }}>Gudang</option>
                        @foreach ($warehouses as $wh)
                            <option value="{{ route('production.cutting_jobs.index', array_filter(['status' => $currentStatus, 'warehouse_id' => $wh->id])) }}"
                                {{ $currentWarehouse == $wh->id ? 'selected' : '' }}>{{ $wh->code }}</option>
                        @endforeach
                    </select>
                @endif
            </div>

            {{-- Grup aksi --}}
            <div class="control-actions">
                @if ($isOwner)
                    <form method="POST" action="{{ route('production.cutting_jobs.dev_clean_production') }}" id="cleanProdForm" class="m-0">
                        @csrf
                        <input type="hidden" name="confirm_text" id="cleanProdConfirm">
                        <input type="hidden" name="confirm_password" id="cleanProdPassword">
                        <button type="button" id="cleanProdBtn" class="btn btn-sm btn-outline-danger btn-pill btn-fresh btn-ico"
                                title="Bersihkan Data Produksi (owner)" aria-label="Bersihkan Data Produksi">
                            <i class="bi bi-trash3"></i>
                        </button>
                    </form>
                @endif

                <a href="{{ route('production.cutting_jobs.create') }}" class="btn btn-sm btn-ship-primary btn-pill btn-ico"
                   title="Cutting Job Baru" aria-label="Cutting Job Baru">
                    <i class="bi bi-plus-lg"></i>
                </a>
            </div>
        </div>
    </div>

    <div class="card card-main">
        <div class="card-body p-0">
            @if ($jobs->count() === 0)
                <div class="empty">
                    Tidak ada cutting job{{ $currentStatus ? ' dengan status ini' : '' }}.
                    <div class="mt-1">Klik <b>Cutting Job Baru</b> untuk mulai.</div>
                </div>
            @else
                <div class="table-responsive table-scroll">
                    <table class="table table-hover align-middle table-list">
                        <thead>
                            <tr>
                                <th style="width:46px;">#</th>
                                <th style="width:120px;">Tanggal</th>
                                <th style="width:200px;">Cutting</th>
                                <th>Item Kain</th>
                                <th style="width:150px;">Operator</th>
                                <th class="text-end" style="width:80px;">Iket</th>
                                <th class="text-end" style="width:100px;">Qty (pcs)</th>
                                <th style="width:130px;">Status</th>
                                <th style="width:110px;"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($jobs as $job)
                                @php
                                    $st  = $job->status ?? 'draft';
                                    $cfg = $statusMap[$st] ?? ['label' => ucfirst($st), 'class' => 'st-draft', 'hint' => ''];
                                    $bundleCount = $job->bundles_count ?? 0;
                                    $qtyPcs      = (float) ($job->bundles_sum_qty_pcs ?? $job->bundles->sum('qty_pcs'));
                                    $isQcDone    = in_array($st, ['qc_ok','qc_done','qc_mixed','qc_reject'], true);
                                    $isVoid      = $st === 'voided';
                                    $detailUrl   = route('production.cutting_jobs.show', $job);
                                    $qcUrl       = Route::has('production.qc.cutting.edit')
                                        ? route('production.qc.cutting.edit', $job) : $detailUrl;
                                    $actionUrl   = (!$isVoid && !$isQcDone) ? $qcUrl : ($isQcDone ? $qcUrl : $detailUrl);
                                    $actionLabel = (!$isVoid && !$isQcDone) ? 'Input QC' : ($isQcDone ? 'Lihat QC' : 'Detail');
                                @endphp

                                <tr class="{{ $isVoid ? 'is-void' : '' }}">
                                    <td class="text-muted small mobile-hide">
                                        {{ ($jobs->currentPage() - 1) * $jobs->perPage() + $loop->iteration }}
                                    </td>

                                    <td class="small mobile-hide">{{ $job->date?->format('d M Y') ?? '-' }}</td>

                                    <td>
                                        <div class="cj-row-main">
                                            <div>
                                                <a class="code-link" href="{{ $detailUrl }}">{{ $job->code }}</a>
                                                <div class="cj-row-meta d-md-none">
                                                    <span>{{ $job->date?->format('d M Y') ?? '-' }}</span>
                                                    @if ($job->operator)<span>{{ $job->operator->name }}</span>@endif
                                                    <span>{{ $bundleCount }} iket</span>
                                                    @if ($qtyPcs > 0)<span>{{ number_format($qtyPcs, 0, ',', '.') }} pcs</span>@endif
                                                </div>
                                            </div>
                                            <span class="badge-status {{ $cfg['class'] }} d-md-none">{{ $cfg['label'] }}</span>
                                        </div>
                                    </td>

                                    <td>
                                        <div class="fabric-name mono">{{ $job->lot?->item?->code ?? '-' }}</div>
                                        @if ($job->lot?->code)
                                            <span class="lot-badge">{{ $job->lot->code }}</span>
                                        @endif
                                    </td>

                                    <td class="mobile-hide">{{ $job->operator?->name ?? '-' }}</td>

                                    <td class="text-end mobile-hide"><span class="num">{{ number_format($bundleCount, 0, ',', '.') }}</span></td>

                                    <td class="text-end mobile-hide">
                                        <span class="num">{{ $qtyPcs > 0 ? number_format($qtyPcs, 0, ',', '.') : '-' }}</span>
                                    </td>

                                    <td class="mobile-hide">
                                        <span class="badge-status {{ $cfg['class'] }}" title="{{ $cfg['hint'] }}">{{ $cfg['label'] }}</span>
                                    </td>

                                    <td class="text-end cj-row-action">
                                        <a href="{{ $actionUrl }}" class="btn btn-sm {{ $actionLabel === 'Input QC' ? 'btn-ship-primary' : 'btn-ship-outline' }} btn-pill">
                                            {{ $actionLabel }}
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="divider"></div>

                <div class="p-3">
                    {{ $jobs->appends(request()->query())->links() }}
                </div>
            @endif
        </div>
    </div>
</div>

@if ($isOwner)
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
        window.Swal.fire({
            icon: 'warning', title: 'Bersihkan Data Produksi?', html: body,
            input: 'text', inputPlaceholder: EXP, showCancelButton: true,
            confirmButtonText: 'Lanjut', cancelButtonText: 'Batal', confirmButtonColor: '#dc2626',
            inputValidator: function (v) {
                return (String(v || '').trim().toUpperCase() !== EXP) ? ('Ketik persis: ' + EXP) : undefined;
            },
        }).then(function (r1) {
            if (!r1.isConfirmed) return;
            var phrase = r1.value;
            window.Swal.fire({
                icon: 'warning', title: 'Konfirmasi Password Owner',
                text: 'Masukkan password owner untuk menghapus data produksi.',
                input: 'password', inputPlaceholder: 'Password owner',
                inputAttributes: { autocomplete: 'off' }, showCancelButton: true,
                confirmButtonText: 'Hapus Sekarang', cancelButtonText: 'Batal', confirmButtonColor: '#dc2626',
                inputValidator: function (v) { return (!v) ? 'Password wajib diisi.' : undefined; },
            }).then(function (r2) {
                if (r2.isConfirmed) go(phrase, r2.value);
            });
        });
    }
    var _cpb = document.getElementById('cleanProdBtn');
    if (_cpb) _cpb.addEventListener('click', cleanProdConfirm);
})();
</script>
@endif
@endsection
