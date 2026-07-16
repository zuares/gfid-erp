@extends('layouts.app')

@section('title','Master • BOM SKU')

@push('head')
<style>
    :root{
        --bom-accent:#334155;
        --bom-accent-2:#1f2937;
        --bom-border:rgba(148,163,184,.18);
        --bom-border-strong:rgba(148,163,184,.30);
        --bom-muted:#64748b;
    }
    .page-wrap{ max-width:1040px; margin-inline:auto; padding:.75rem .75rem 4rem; background:transparent!important; }

    .card-main{
        background: var(--card);
        border-radius: 8px;
        border: 1px solid var(--bom-border);
        box-shadow: none;
        overflow:hidden;
    }
    body[data-theme="dark"] .card-main{
        border-color: rgba(51,65,85,.85);
        box-shadow: none;
    }

    .bom-topbar{
        position:sticky;
        top:0;
        z-index:300;
        display:flex;
        justify-content:space-between;
        align-items:center;
        gap:.6rem;
        flex-wrap:wrap;
        padding:.45rem .75rem;
        margin-inline:-.75rem;
        margin-bottom:.65rem;
        background:var(--card,#fff);
        border-bottom:1px solid var(--bom-border);
    }
    body[data-theme="dark"] .bom-topbar{ background:var(--card,#0f172a); }
    .title{ font-weight: 750; font-size:1rem; letter-spacing: 0; margin:0; }
    .sub{ color:var(--bom-muted); font-size:.78rem; }
    body[data-theme="dark"] .sub{ color:#9ca3af; }

    .kpis{ display:flex; flex-wrap:wrap; gap:.32rem; margin-top:.35rem; }
    .kpi{
        display:inline-flex; align-items:baseline; gap:.45rem;
        border-radius:7px; padding:.2rem .48rem;
        border:1px solid rgba(148,163,184,.28);
        background: transparent;
        font-size:.72rem;
    }
    body[data-theme="dark"] .kpi{
        background: rgba(15, 23, 42, 0.96);
        border-color: rgba(51, 65, 85, 0.85);
    }
    .kpi .lbl{ text-transform:none; letter-spacing:0; font-size:.66rem; color:#94a3b8; }
    body[data-theme="dark"] .kpi .lbl{ color:#6b7280; }
    .kpi .val{ font-weight:650; color:var(--bom-accent); }

    .controls{ display:flex; gap:.5rem; align-items:center; flex-wrap:wrap; }
    .filter-label{ font-size:.8rem; color:#6b7280; }
    body[data-theme="dark"] .filter-label{ color:#9ca3af; }
    .filter-input{ border-radius:7px; padding-left:.75rem; font-size:.82rem; min-height:30px; }
    .btn-pill{ border-radius:7px; padding-inline:.78rem; box-shadow:none!important; font-weight:600; }
    .btn-bom-primary{ background:var(--bom-accent)!important; border-color:var(--bom-accent)!important; color:#fff!important; }
    .btn-bom-primary:hover{ background:var(--bom-accent-2)!important; border-color:var(--bom-accent-2)!important; color:#fff!important; }
    .btn-bom-outline{ color:#475569!important; background:transparent!important; border:1px solid rgba(148,163,184,.35)!important; }
    .btn-bom-outline:hover{ background:rgba(148,163,184,.08)!important; color:#111827!important; }
    body[data-theme="dark"] .btn-bom-outline { color: #cbd5e1!important; }
    body[data-theme="dark"] .btn-bom-outline:hover { color: #f8fafc!important; background: rgba(148,163,184,.15)!important; }

    .table-list{ margin-bottom:0; }
    .table-list thead th{
        border-bottom-width:1px;
        font-size:.68rem;
        text-transform:none;
        letter-spacing:0;
        color:#64748b;
        background: var(--card,#fff);
        padding:.52rem .62rem;
        white-space:nowrap;
        position: sticky;
        top: 0;
        z-index: 10;
    }
    body[data-theme="dark"] .table-list thead th{
        background: rgba(15, 23, 42, 0.98);
        color:#9ca3af;
        border-bottom-color: rgba(30, 64, 175, 0.6);
    }
    .table-list tbody td{
        vertical-align:middle;
        border-top-color: rgba(148, 163, 184, 0.16);
        padding:.52rem .62rem;
    }
    body[data-theme="dark"] .table-list tbody td{ border-top-color: rgba(51, 65, 85, 0.85); }

    .code-link{ font-weight:700; text-decoration:none; color:inherit; }
    .code-link:hover{ text-decoration:underline; }
    .muted{ font-size:.82rem; color:#6b7280; }
    body[data-theme="dark"] .muted{ color:#9ca3af; }
    .bom-name{ font-weight:600; }

    .badge-status{
        border-radius:7px; padding:.16rem .48rem;
        font-size:.68rem; letter-spacing:0; text-transform:none;
        border:1px solid transparent;
        display:inline-flex; align-items:center; gap:.35rem;
        white-space:nowrap;
    }
    .badge-status::before{ content:''; width:7px; height:7px; border-radius:999px; display:inline-block; }

    .st-active{ background: rgba(34, 197, 94, 0.10); color:#166534; border-color: rgba(34, 197, 94, 0.30); }
    .st-active::before{ background: rgba(34, 197, 94, 0.95); }
    .st-inactive{ background: rgba(148, 163, 184, 0.10); color:#475569; border-color: rgba(148, 163, 184, 0.30); }
    .st-inactive::before{ background: rgba(100, 116, 139, 0.95); }
    .st-warning{ background: #fef3c7; color: #92400e; border-color: #fde68a; }

    body[data-theme="dark"] .st-active{ background: rgba(34, 197, 94, 0.20); color:#dcfce7; border-color: rgba(34, 197, 94, 0.55); }
    body[data-theme="dark"] .st-inactive{ background: rgba(148, 163, 184, 0.18); color:#cbd5e1; border-color: rgba(148, 163, 184, 0.55); }

    .empty{ padding:2.2rem 1.25rem; text-align:center; color:#64748b; }
    body[data-theme="dark"] .empty{ color:#9ca3af; }
    .divider{ height:1px; background: rgba(148, 163, 184, 0.20); }
    body[data-theme="dark"] .divider{ background: rgba(51, 65, 85, 0.85); }
    .flash-clean{ border-radius:8px; padding:.62rem .75rem; font-size:.84rem; border:1px solid rgba(148,163,184,.25); }
    
    .gf-live-filter-wrap { position: relative !important; }
    .gf-live-filter-indicator {
        position: absolute !important;
        right: 12px !important;
        top: 50% !important;
        transform: translateY(-50%) !important;
        display: none !important;
        color: #334155 !important;
        background: rgba(255,255,255,.88) !important;
        padding-left: 8px !important;
        font-size: .72rem !important;
        font-weight: 850 !important;
    }
    body[data-theme="dark"] .gf-live-filter-indicator { background: rgba(15, 23, 42, .88)!important; color:#cbd5e1!important; }
    .gf-live-filter-indicator.is-show { display: inline-flex !important; }

    @media (max-width: 768px) {
        .page-wrap{ padding:.5rem .5rem 4rem; }
        .bom-topbar{ margin-inline:-.5rem; padding:.5rem .65rem; }
        .title{ font-size:1.05rem; }
        .sub{ display:none; }
        .controls{ width:100%; align-items:stretch; }
        .controls form{ flex:1 1 100%; }
        .controls .btn,
        .controls form button{ min-height:40px; }
        .kpis{ display:none; }
        .table-responsive{ overflow:visible; }
        .table-list thead{ display:none; }
        .table-list,
        .table-list tbody,
        .table-list tr,
        .table-list td{ display:block; width:100%; }
        .table-list tbody tr{
            padding:.66rem;
            border-top:1px solid rgba(148,163,184,.16);
        }
        .table-list tbody td{
            border:0;
            padding:0;
            margin-bottom:.35rem;
        }
        .table-list tbody td.mobile-hide{ display:none; }
        .bom-row-main{
            display:flex;
            align-items:flex-start;
            justify-content:space-between;
            gap:.75rem;
        }
        .bom-row-action{
            margin-top:.55rem;
        }
        .bom-row-action .btn{
            width:100%;
            min-height:38px;
        }
    }
</style>
@endpush

@section('content')
<div class="page-wrap">
    @if(session('success'))
        <div class="flash-clean alert alert-success mb-2">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="flash-clean alert alert-danger mb-2">{{ session('error') }}</div>
    @endif
    @if($errors->any())
        <div class="flash-clean alert alert-danger mb-2">{{ $errors->first() }}</div>
    @endif

    <div class="bom-topbar">
        <div>
            <div class="title">BOM per SKU</div>
            <div class="sub">1 SKU = 1 BOM (paling cepat jalan). Material ambil dari <span style="font-family: monospace;">items</span>.</div>

            <div class="kpis">
                <span class="kpi"><span class="lbl">Total BOM</span><span class="val">{{ number_format($boms->total(), 0, ',', '.') }}</span></span>
            </div>
        </div>

        <div class="controls">
            <a href="{{ route('master.item_boms.import_form') }}" class="btn btn-sm btn-bom-outline btn-pill">
                <i class="bi bi-upload"></i> Import CSV
            </a>
            <a href="{{ route('master.item_boms.duplicate_form') }}" class="btn btn-sm btn-bom-outline btn-pill">
                <i class="bi bi-files"></i> Duplicate BOM
            </a>
            <a href="{{ route('master.item_boms.create') }}" class="btn btn-sm btn-bom-primary btn-pill">
                BOM Baru
            </a>
        </div>
    </div>

    <form method="get" class="d-flex gap-2 flex-wrap mb-3 align-items-start" style="background: var(--card); padding: 12px 14px; border-radius: 8px; border: 1px solid var(--bom-border);">
        <div style="flex: 1 1 240px;">
            <div class="position-relative gf-live-filter-wrap">
                <i class="bi bi-search position-absolute" style="left: 10px; top: 50%; transform: translateY(-50%); color: var(--bom-muted); font-size: 0.8rem;"></i>
                <input type="search" name="q" class="form-control form-control-sm filter-input w-100"
                    value="{{ request('q') }}" placeholder="Ketik SKU atau Nama Item..." autocomplete="off" autofocus
                    style="padding-left: 32px; font-family: monospace; font-size: 0.88rem;">
            </div>
            <div class="text-muted mt-2" style="font-size: 0.72rem;">Contoh: <span style="font-family: monospace; background: rgba(148,163,184,.1); padding: 2px 4px; border-radius: 4px;">C5BLK</span>, <span style="font-family: monospace; background: rgba(148,163,184,.1); padding: 2px 4px; border-radius: 4px;">J3MST</span>, <span style="font-family: monospace; background: rgba(148,163,184,.1); padding: 2px 4px; border-radius: 4px;">K1NVY</span></div>
        </div>

        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-sm btn-bom-primary btn-pill" style="min-height: 32px; display: inline-flex; align-items: center; gap: 6px;">
                <i class="bi bi-funnel"></i> Cari
            </button>
            @if(request('q'))
                <a href="{{ route('master.item_boms.index') }}" class="btn btn-sm btn-bom-outline btn-pill" style="min-height: 32px; display: inline-flex; align-items: center;">Reset</a>
            @endif
        </div>
    </form>

    <div class="card card-main">
        <div class="card-body p-0">
            @if ($boms->count() === 0)
                <div class="empty">
                    Belum ada BOM.
                    <div class="mt-1">Klik <b>BOM Baru</b> untuk membuat BOM.</div>
                </div>
            @else
                <div class="table-responsive" style="max-height: 65vh; overflow-y: auto;">
                    <table class="table table-hover align-middle table-list">
                        <thead>
                            <tr>
                                <th style="width: 44px;" class="text-center">#</th>
                                <th style="width: 130px;">SKU</th>
                                <th style="min-width: 200px;">Nama</th>
                                <th style="width: 220px;">Struktur BOM</th>
                                <th class="text-center" style="width: 100px;">Status</th>
                                <th class="text-end" style="width: 140px;"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($boms as $i => $b)
                                <tr>
                                    <td class="text-center text-muted mobile-hide">{{ ($boms->currentPage()-1)*$boms->perPage() + $i + 1 }}</td>
                                    <td>
                                        <div class="bom-row-main">
                                            <div class="bom-name" style="font-family: monospace;">{{ $b->item->code }}</div>
                                            <span class="badge-status {{ $b->active ? 'st-active' : 'st-inactive' }} d-md-none">{{ $b->active ? 'Active' : 'Off' }}</span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="bom-name">{{ $b->item->name }}</div>
                                    </td>
                                    <td>
                                        <div class="d-flex flex-wrap gap-1 mb-1">
                                            <span class="badge-status" style="border: 1px solid rgba(148,163,184,.3); color: #475569; background: #f8fafc;">Utama {{ (int) $b->main_material_lines_count }}</span>
                                            <span class="badge-status" style="border: 1px solid rgba(59,130,246,.3); color: #1d4ed8; background: rgba(59,130,246,.1);">Jahit {{ (int) $b->sewing_supply_lines_count }}</span>
                                            <span class="badge-status" style="border: 1px solid rgba(6,182,212,.3); color: #0e7490; background: rgba(6,182,212,.1);">Packing {{ (int) $b->packing_supply_lines_count }}</span>
                                        </div>
                                        <div class="muted">Total {{ (int) $b->lines_count }} material</div>
                                        @php $ub = $bomUsageBadges[$b->id] ?? null; @endphp
                                        @if($ub)
                                            <div class="muted mt-1">
                                                Aktual terakhir:
                                                <span style="font-family: monospace;" class="fw-semibold">{{ number_format($ub['kg_per_pcs'], 4) }}</span>/pcs
                                                <span>· {{ $ub['job_code'] }}</span>
                                                @if($ub['status'] === 'over')
                                                    <span class="badge-status" style="background: rgba(239, 68, 68, 0.1); color: #991b1b; border-color: rgba(239, 68, 68, 0.3);" title="Standar BOM (maks): {{ number_format($ub['std_max'], 4) }} kg/pcs">⚠ melebihi standar</span>
                                                @elseif($ub['status'] === 'under')
                                                    <span class="badge-status" style="background: rgba(34, 197, 94, 0.1); color: #166534; border-color: rgba(34, 197, 94, 0.3);" title="Standar BOM (maks): {{ number_format($ub['std_max'], 4) }} kg/pcs">lebih hemat</span>
                                                @elseif($ub['status'] === 'ok')
                                                    <span class="badge-status" style="background: rgba(148, 163, 184, 0.1); color: #475569; border-color: rgba(148, 163, 184, 0.3);">sesuai standar</span>
                                                @endif
                                            </div>
                                        @endif
                                    </td>
                                    <td class="text-center mobile-hide">
                                        @if($b->active)
                                            <span class="badge-status st-active">Active</span>
                                        @else
                                            <span class="badge-status st-inactive">Off</span>
                                        @endif
                                    </td>
                                    <td class="text-end bom-row-action">
                                        <div class="d-inline-flex gap-1 align-items-center">
                                            <a href="{{ route('master.item_boms.edit', $b) }}" class="btn btn-sm btn-bom-outline btn-pill px-3 fw-bold" style="font-size: 0.78rem;">
                                                Edit
                                            </a>
                                            <form method="post" action="{{ route('master.item_boms.destroy', $b) }}" class="js-delete-bom-form">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm px-2 py-1 shadow-none border-0 text-danger" style="background: transparent;">
                                                    <i class="bi bi-trash3"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="divider"></div>

                <div class="p-3 d-flex justify-content-between align-items-center flex-wrap">
                    <div class="muted">
                        Menampilkan {{ $boms->firstItem() }}–{{ $boms->lastItem() }} dari {{ number_format($boms->total(), 0, ',', '.') }} BOM
                    </div>
                    <div>
                        {{ $boms->links() }}
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.querySelector('input[name="q"]');
    if (!searchInput) return;

    setTimeout(function () {
        searchInput.focus();
        const value = searchInput.value || '';
        try { searchInput.setSelectionRange(value.length, value.length); } catch (e) {}
    }, 120);
});
</script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.querySelector('form[method="get"]');
    if (!form || form.dataset.gfRealtime === '1') return;

    form.dataset.gfRealtime = '1';

    const input = form.querySelector('input[name="q"]');
    const submitBtn = form.querySelector('button[type="submit"]');
    let timer = null;
    let submitting = false;

    if (!input) return;

    let wrap = input.closest('.gf-live-filter-wrap');
    if (!wrap) {
        wrap = document.createElement('div');
        wrap.className = 'gf-live-filter-wrap';
        input.parentNode.insertBefore(wrap, input);
        wrap.appendChild(input);
    }

    let indicator = document.createElement('span');
    indicator.className = 'gf-live-filter-indicator';
    indicator.textContent = 'filter...';
    wrap.appendChild(indicator);

    function submitLive(delay = 450) {
        clearTimeout(timer);

        timer = setTimeout(function () {
            if (submitting) return;
            submitting = true;

            indicator.classList.add('is-show');

            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Cari';
            }

            if (String(input.value || '').trim() === '') {
                input.disabled = true;
            }

            form.requestSubmit ? form.requestSubmit() : form.submit();
        }, delay);
    }

    input.setAttribute('autocomplete', 'off');

    input.addEventListener('input', function () {
        submitLive(450);
    });

    input.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            submitLive(0);
        }
    });
});
</script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.js-delete-bom-form').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            event.preventDefault();

            const submit = () => form.submit();

            if (window.Swal) {
                Swal.fire({
                    title: 'Hapus BOM?',
                    text: 'Semua line material pada BOM ini ikut terhapus.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, hapus',
                    cancelButtonText: 'Batal',
                    confirmButtonColor: '#dc2626',
                }).then(result => {
                    if (result.isConfirmed) submit();
                });
                return;
            }

            if (confirm('Hapus BOM ini? Semua line material ikut terhapus.')) {
                submit();
            }
        });
    });
});
</script>
@endpush
